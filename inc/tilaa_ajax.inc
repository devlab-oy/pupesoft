<?php

$maara = (float) str_replace(",", ".", $maara);

// Lopetetaan jos tilattava määrä on nolla
if ($maara > 0) {

  $tuoteno = utf8_decode($tuoteno);

  //päivitetään kukarow[kesken] kun käyttäjä tekee uutta tilausta
  $query = "UPDATE kuka
            SET kesken = 0
            WHERE session = '$session'";
  $result = pupe_query($query);

  $kukarow['kesken'] = 0;
  $tilausnumero      = 0;

  $query  = "SELECT *, tunnus liitostunnus
             from toimi
             where yhtio = '$kukarow[yhtio]'
             and tunnus  = '$toimittaja'";
  $result = pupe_query($query);
  $srow   = mysql_fetch_array($result);

  // oletuksia
  $varasto    = 0;
  $toimipiste = 0;

  if (isset($valittuvarasto) and $valittuvarasto > 0) {
    $varasto = $valittuvarasto;
  }

  // tarvittavat muuttujat otsikolle
  $ytunnus   = $srow['ytunnus'];
  $ovttunnus = $srow["ovttunnus"];
  $nimi      = $srow["nimi"];
  $nimitark  = $srow["nimitark"];
  $osoite    = $srow["osoite"];
  $postino   = $srow["postino"];
  $postitp   = $srow["postitp"];
  $maa       = $srow["maa"];
  $liitostunnus = $toimittaja;
  $maksuteksti  = $srow["maksuteksti"];
  $kuljetus     = $srow["kuljetus"];
  $tnimi        = "";

  $query  = "SELECT nimi
             from yhteyshenkilo
             where yhtio              = '$kukarow[yhtio]'
             and tyyppi               = 'T'
             and tilausyhteyshenkilo != ''
             and liitostunnus         = '$toimittaja'";
  $result = pupe_query($query);
  $yhrow   = mysql_fetch_array($result);

  $tilausyhteyshenkilo = $yhrow['nimi'];
  $verkkotunnus = $srow["ovttunnus"];
  $valkoodi = $srow["oletus_valkoodi"];

  //keräyspvm pitäisi olla -
  if ($kukarow['kesken'] == 0 and (!in_array($yhtiorow['ostotilaukseen_toimittajan_toimaika'], array("2","3")) or empty($srow["oletus_toimaika"]))) {
    $toimpp = date("j");
    $toimkk = date("n");
    $toimvv = date("Y");
  }
  elseif ($kukarow['kesken'] == 0 and in_array($yhtiorow['ostotilaukseen_toimittajan_toimaika'], array("2","3"))) {
    $toimittajan_toimaika = date('Y-m-d', time() + $srow["oletus_toimaika"] * 24 * 60 * 60);
    list($toimvv, $toimkk, $toimpp) = explode('-', $toimittajan_toimaika);
  }
  else {
    list($toimvv, $toimkk, $toimpp) = explode('-', $srow["toimaika"]);
    $toimpp = substr($toimpp, 0, 2);
  }

  //voidaan tarvita
  if ($toimvv == '') {
    $toimpp = date("j");
    $toimkk = date("n");
    $toimvv = date("Y");
  }

  $maksaja   = $srow["toimitusehto"];
  $myyja    = $kukarow["tunnus"];
  $comments  = "";

  $jatka = "jatka";

  $query = "SELECT tunnus, toimaika
            FROM lasku
            WHERE yhtio      = '$kukarow[yhtio]'
            AND tila         = 'O'
            AND alatila      = ''
            AND liitostunnus = '$toimittaja'
            AND myyja        = '$kukarow[tunnus]'
            ORDER BY tunnus DESC
            LIMIT 1";
  $lasres = pupe_query($query);
  $lasrow = mysql_fetch_array($lasres);

  if ($lasrow['tunnus'] == 0) {
    require "../tilauskasittely/otsik_ostotilaus.inc";
    $rivi = 1;
  }
  else {
    $tilausnumero = $lasrow['tunnus'];
    $otstoimaika  = $lasrow['toimaika'];

    $query = "SELECT max(tilaajanrivinro) tilaajanrivinro
              FROM tilausrivi
              WHERE yhtio = '$kukarow[yhtio]'
              AND tyyppi  = 'O'
              AND otunnus = '$tilausnumero'";
    $trivires = pupe_query($query);
    $trivirow = mysql_fetch_array($trivires);

    $rivi = $trivirow['tilaajanrivinro'] + 1;
  }

  $query = "SELECT *
            FROM lasku
            WHERE yhtio = '$kukarow[yhtio]'
            AND tunnus  = '$tilausnumero'";
  $lasres = pupe_query($query);
  $lasrow = mysql_fetch_assoc($lasres);

  unset($jtsre);

  // Jos varasto on syötetty niin ostetaan siihen varastoon
  if ($varasto > 0) {
    $query = "SELECT tuotepaikat.*
              FROM tuotepaikat
              WHERE tuotepaikat.yhtio = '$kukarow[yhtio]'
              and tuotepaikat.varasto = $varasto
              and tuotepaikat.tuoteno = '$tuoteno'";
    $jtsre = pupe_query($query);
    $jtstu = mysql_fetch_array($jtsre);
  }

  if (!isset($jtsre) or mysql_num_rows($jtsre) == 0) {
    // haetaan oletuspaikan tiedot niin laitetaan se riville
    $query = "SELECT *
              FROM tuotepaikat
              WHERE yhtio = '$kukarow[yhtio]'
              AND tuoteno = '$tuoteno'
              AND oletus!=''";
    $jtsre = pupe_query($query);
    $jtstu = mysql_fetch_array($jtsre);
  }

  //haetaan tuotteen ostohinta
  $query = "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
  $tuotere = pupe_query($query);
  $tuoterow = mysql_fetch_array($tuotere);

  list($lis_hinta, $lis_netto, $lis_ale_kaikki, $alehinta_val) = alehinta_osto($lasrow, $tuoterow, $maara, "", "", "");

  //otetaan tuoteen kertoimet huomioon - eli jaetaan myyntihinta kertoimella, jotta saadaan yksikköhinta
  if ($tuoterow["myyntihinta_maara"] != 0 and $lis_hinta != 0) {
    $lis_hinta = hintapyoristys($lis_hinta / $tuoterow["myyntihinta_maara"]);
  }

  $query_insert_ale_lisa = "";

  for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
    $query_insert_ale_lisa .= " ale{$alepostfix} = '".$lis_ale_kaikki['ale'.$alepostfix]."',";
  }

  $toimaika = "";
  $toimvva  = "";
  $toimkka  = "";
  $toimppa  = "";

  if ($yhtiorow["ostotilaukseen_toimittajan_toimaika"] == '3') {
    $ttquery = "SELECT if (tuotteen_toimittajat.toimitusaika > 0, tuotteen_toimittajat.toimitusaika, toimi.oletus_toimaika) toimaika
                FROM tuotteen_toimittajat
                JOIN toimi ON (toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.tunnus = tuotteen_toimittajat.liitostunnus)
                WHERE tuotteen_toimittajat.yhtio = '{$kukarow['yhtio']}'
                AND tuotteen_toimittajat.tuoteno = '{$tuoteno}'
                AND tuotteen_toimittajat.liitostunnus = {$lasrow['liitostunnus']}
                AND (tuotteen_toimittajat.toimitusaika > 0 or toimi.oletus_toimaika > 0)";
    $ttres = pupe_query($ttquery);

    if ($ttrow = mysql_fetch_assoc($ttres)) {
      $toimittajan_toimaika = date('Y-m-d', time() + $ttrow["toimaika"] * 24 * 60 * 60);

      list($toimvva, $toimkka, $toimppa) = explode('-', $toimittajan_toimaika);
    }
  }

  if (checkdate($toimkka, $toimppa, $toimvva)) {
    $toimaika = $toimvva."-".$toimkka."-".$toimppa;
  }

  if ($toimaika == "" or $toimaika == "0000-00-00") {
    $toimaika = $otstoimaika;
  }

  // lisätään ostotilausrivi
  $query = "INSERT into tilausrivi SET
            hinta           = '$lis_hinta',
            {$query_insert_ale_lisa}
            nimitys         = '$tuoterow[nimitys]',
            tuoteno         = '$tuoteno',
            try             = '$tuoterow[try]',
            osasto          = '$tuoterow[osasto]',
            tilkpl          = '$maara',
            varattu         = '$maara',
            yksikko         = '$tuoterow[yksikko]',
            otunnus         = '$tilausnumero',
            yhtio           = '$kukarow[yhtio]',
            tyyppi          = 'O',
            kommentti       = '',
            toimaika        = '$toimaika',
            kerayspvm       = now(),
            hyllyalue       = '$jtstu[hyllyalue]',
            hyllynro        = '$jtstu[hyllynro]',
            hyllyvali       = '$jtstu[hyllyvali]',
            hyllytaso       = '$jtstu[hyllytaso]',
            tilaajanrivinro = '$rivi',
            laatija         = '$kukarow[kuka]',
            laadittu        = now()";
  $updre = pupe_query($query);
}

echo json_encode('ok');

// Footteri hiljaiseksi
$no_head = "yes";

require "inc/footer.inc";
exit;
