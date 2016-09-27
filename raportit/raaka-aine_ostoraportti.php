<?php

require "../inc/parametrit.inc";

// Salitaan vain numeroita ja piste/pilkku input kentiss‰
echo '<script language="javascript">
$(document).ready(function() {
    $("#vain_numeroita").keydown(function(event) {
        // sallitaan backspace (8) ja delete (46)
        if ( event.keyCode == 46 || event.keyCode == 8 ) {
            // anna sen vaan tapahtua...
        }
        else {
            // 48-57 on normin‰pp‰imistˆn numerot, numpad numerot on 96-105, piste on 190 ja pilkku 188
            if ((event.keyCode < 48 || event.keyCode > 57) && (event.keyCode < 96 || event.keyCode > 105 ) && event.keyCode != 188 && event.keyCode != 190) {
                event.preventDefault();
            }
        }
    });
  $("a.toggle_rivit").click(function(event) {
    event.preventDefault();
    $("tr.togglettava_rivi_"+$(this).attr("id")).toggle();
  });
});
</script>';

echo "<font class='head'>".t("Raaka-aineiden ostoraportti")."</font><hr>";

// org_rajausta tarvitaan yhdess‰ selectiss‰ joka triggerˆi taas toisen asian.
$org_rajaus = isset($abcrajaus) ? $abcrajaus : '';
$abcrajaus = isset($abcrajaus) ? $abcrajaus : '';

if ($abcrajaus != '') {
  list($abcrajaus, $abcrajaustapa) = explode("##", $abcrajaus);
}

if (!isset($abcrajaustapa)) $abcrajaustapa = "TK";

list($ryhmanimet, $ryhmaprossat, , , , ) = hae_ryhmanimet($abcrajaustapa);

// Ehdotetaan oletuksena ehdotusta ensikuun myynnille sek‰ siit‰ plus 3 kk
if (!isset($ppa1)) $ppa1 = date("d", mktime(0, 0, 0, date("m")+1, 1, date("Y")));
if (!isset($kka1)) $kka1 = date("m", mktime(0, 0, 0, date("m")+1, 1, date("Y")));
if (!isset($vva1)) $vva1 = date("Y", mktime(0, 0, 0, date("m")+1, 1, date("Y")));
if (!isset($ppl1)) $ppl1 = date("d", mktime(0, 0, 0, date("m")+4, 0, date("Y")));
if (!isset($kkl1)) $kkl1 = date("m", mktime(0, 0, 0, date("m")+4, 0, date("Y")));
if (!isset($vvl1)) $vvl1 = date("Y", mktime(0, 0, 0, date("m")+4, 0, date("Y")));

// P‰iv‰m‰‰r‰tarkistus
if (!checkdate($kka1, $ppa1, $vva1)) {
  echo "<font class='error'>".t("Virheellinen alkup‰iv‰!")."</font><br>";
  $tee = "";
  $ehdotusnappi = "";
}
else {
  $nykyinen_alku  = date("Y-m-d", mktime(0, 0, 0, $kka1, $ppa1, $vva1));
}

if (!checkdate($kkl1, $ppl1, $vvl1)) {
  echo "<font class='error'>".t("Virheellinen loppup‰iv‰!")."</font><br>";
  $tee = "";
  $ehdotusnappi = "";
}
else {
  $nykyinen_loppu  = date("Y-m-d", mktime(0, 0, 0, $kkl1, $ppl1, $vvl1));
}

if (isset($nykyinen_alku) and isset($nykyinen_loppu) and $nykyinen_alku > $nykyinen_loppu) {
  echo "<font class='error'>".t("Virheelliset kaudet!")." $nykyinen_alku > $nykyinen_loppu</font><br>";
  $tee = "";
  $ehdotusnappi = "";
}

// Muuttujia
$ytunnus = isset($ytunnus) ? trim($ytunnus) : "";
$toimittajaid = isset($toimittajaid) ? trim($toimittajaid) : "";
$tilaustuotteiden_kasittely = isset($tilaustuotteiden_kasittely) ? $tilaustuotteiden_kasittely : 'A';
$multi_status = isset($multi_status) ? $multi_status : array();

// T‰m‰ palauttaa yhden tuotteen ostosuosituksen tiedot
function teerivi($tuoteno, $valittu_toimittaja) {

  // Kukarow ja p‰iv‰m‰‰r‰t globaaleina
  global $kukarow, $nykyinen_alku, $nykyinen_loppu, $tilaustuotteiden_kasittely, $multi_status;

  // Tehd‰‰n kaudet p‰iv‰m‰‰rist‰
  $alku_kausi = substr(str_replace("-", "", $nykyinen_alku), 0, 8);
  $loppu_kausi = substr(str_replace("-", "", $nykyinen_loppu), 0, 8);

  // Haetaan raaka-aineen varastosaldo
  $query = "SELECT ifnull(sum(saldo), 0) saldo
            FROM tuotepaikat
            WHERE tuotepaikat.yhtio = '{$kukarow["yhtio"]}'
            AND tuotepaikat.tuoteno = '$tuoteno'";
  $result = pupe_query($query);
  $row = mysql_fetch_assoc($result);
  $lapsi_saldo = $row['saldo'];

  // Haetaan raaka-aineen vuosikulutus (rullaava 12 kk)
  $query = "SELECT ifnull(sum(tilausrivi.kpl), 0) vuosikulutus
            FROM tilausrivi
            WHERE tilausrivi.yhtio        = '{$kukarow["yhtio"]}'
            AND tilausrivi.tyyppi         = 'V'
            AND tilausrivi.tuoteno        = '$tuoteno'
            AND tilausrivi.toimitettuaika >= DATE_SUB(now(), INTERVAL 1 YEAR)";
  $result = pupe_query($query);
  $row = mysql_fetch_assoc($result);
  $lapsi_vuosikulutus = $row['vuosikulutus'];

  // Haetaan raaka-aineen ostettu, varattu, ennakkotilattu sek‰ valmistuksessa oleva m‰‰r‰
  $query = "SELECT
            ifnull(sum(if(tilausrivi.tyyppi = 'O', tilausrivi.varattu, 0)), 0) tilattu,
            ifnull(sum(if(tilausrivi.tyyppi = 'L', tilausrivi.varattu, 0)), 0) varattu,
            ifnull(sum(if(tilausrivi.tyyppi = 'E' and tilausrivi.var != 'O', tilausrivi.varattu, 0)), 0) ennakko,
            ifnull(sum(if(tilausrivi.tyyppi in ('V','W'), tilausrivi.varattu, 0)), 0) valmistuksessa
            FROM tilausrivi
            WHERE tilausrivi.yhtio  = '{$kukarow["yhtio"]}'
            AND tilausrivi.tyyppi   in ('O', 'L', 'E', 'V','W')
            AND tilausrivi.tuoteno  = '$tuoteno'
            AND tilausrivi.varattu != 0";
  $result = pupe_query($query);
  $row = mysql_fetch_assoc($result);
  $lapsi_varattu = $row['varattu'];
  $lapsi_tilattu = $row['tilattu'];
  $lapsi_ennakko = $row['ennakko'];
  $lapsi_valmistuksessa = $row['valmistuksessa'];

  // Haetaan raaka-aineen toimittajatiedot
  $query = "SELECT if(tuotteen_toimittajat.toimitusaika > 0, tuotteen_toimittajat.toimitusaika, toimi.oletus_toimaika) toimitusaika,
            if(tuotteen_toimittajat.pakkauskoko > 0, tuotteen_toimittajat.pakkauskoko, 1) pakkauskoko,
            toimi.ytunnus,
            tuotteen_toimittajat.ostohinta,
            toimi.nimi,
            toimi.tunnus,
            tuote.tuotemassa,
            tuote.tuotekorkeus * tuote.tuoteleveys * tuote.tuotesyvyys as tilavuus
            FROM tuotteen_toimittajat
            JOIN toimi ON (toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.tunnus = tuotteen_toimittajat.liitostunnus and toimi.tunnus = '$valittu_toimittaja')
            JOIN tuote ON ( tuote.yhtio = tuotteen_toimittajat.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno )
            WHERE tuotteen_toimittajat.yhtio = '{$kukarow["yhtio"]}'
            AND tuotteen_toimittajat.tuoteno = '$tuoteno'
            ORDER BY if(jarjestys = 0, 9999, jarjestys)
            LIMIT 1";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 1) {
    $toimittajarow = mysql_fetch_assoc($result);
  }
  else {
    // Toimittajaa ei lˆydy -> alustetaan defaulttiarvot (lis‰‰ t‰h‰n jos muutat query‰)
    $toimittajarow = array(
      "toimitusaika" => 0,
      "pakkauskoko" => 1,
      "toimittaja" => "",
      "nimi" => t("Ei toimittajaa"),
      "tunnus" => 0,
      "ostohinta" => 0,
      "tuotemassa" => 0,
      "tilavuus" => 0,
    );
  }

  if (isset($multi_status) and count($multi_status) > 0) {
    $tuote_where = " AND tuote.status in ('".implode("','", $multi_status)."')";
  }
  else {
    $tuote_where = " ";
  }

  // Loopataan l‰pi raaka-aineen is‰tuotteet ja lasketaan ostoehdotukset
  $query = "SELECT isatuoteno, kerroin
            FROM tuoteperhe
            JOIN tuote ON (tuote.yhtio = tuoteperhe.yhtio
              AND tuote.tuoteno    = tuoteperhe.isatuoteno
              {$tuote_where})
            WHERE tuoteperhe.yhtio = '{$kukarow["yhtio"]}'
            AND tuoteperhe.tuoteno = '{$tuoteno}'
            AND tuoteperhe.tyyppi  = 'R'";
  $isatuote_result = pupe_query($query);

  // While loopissa k‰ytett‰v‰t muuttujat
  $lapsi_kulutus = 0;
  $budjetin_peruste = array();

  while ($isatuote_row = mysql_fetch_assoc($isatuote_result)) {

    // Haetaan is‰tuotteen budjetoitu myynti
    $params = array(
      'tuoteno'                    => $isatuote_row["isatuoteno"],
      'pvm_alku'                   => $nykyinen_alku,
      'pvm_loppu'                  => $nykyinen_loppu,
      'tilaustuotteiden_kasittely' => $tilaustuotteiden_kasittely,
    );

    list($isa_budjetoitu_myynti, $budjetin_peruste[]) = tuotteen_budjetoitu_myynti($params);

    // Haetaan is‰tuotteiden varastosaldo
    $query = "SELECT ifnull(sum(saldo), 0) saldo
              FROM tuotepaikat
              WHERE tuotepaikat.yhtio = '{$kukarow["yhtio"]}'
              AND tuotepaikat.tuoteno = '{$isatuote_row["isatuoteno"]}'";
    $result = pupe_query($query);
    $row = mysql_fetch_assoc($result);
    $isa_saldo = $row['saldo'];

    // Haetaan is‰tuotteen ostettu, varattu, ennakkotilattu sek‰ valmistuksessa m‰‰r‰
    $query = "SELECT
              ifnull(sum(if(tilausrivi.tyyppi = 'O', tilausrivi.varattu, 0)), 0) tilattu,
              ifnull(sum(if(tilausrivi.tyyppi = 'L', tilausrivi.varattu, 0)), 0) varattu,
              ifnull(sum(if(tilausrivi.tyyppi = 'E' and tilausrivi.var != 'O', tilausrivi.varattu, 0)), 0) ennakko,
              ifnull(sum(if(tilausrivi.tyyppi in ('V','W'), tilausrivi.varattu, 0)), 0) valmistuksessa
              FROM tilausrivi
              WHERE tilausrivi.yhtio  = '{$kukarow["yhtio"]}'
              AND tilausrivi.tyyppi   in ('O', 'L', 'E', 'V','W')
              AND tilausrivi.tuoteno  = '{$isatuote_row["isatuoteno"]}'
              AND tilausrivi.varattu != 0";
    $result = pupe_query($query);
    $row = mysql_fetch_assoc($result);
    $isa_tilattu = $row['tilattu'];
    $isa_varattu = $row['varattu'];
    $isa_ennakko = $row['ennakko'];
    $isa_valmistuksessa = $row['valmistuksessa'];

    // Is‰tuotteen reaalisaldo
    $isa_reaalisaldo = $isa_saldo + $isa_tilattu - $isa_varattu - $isa_ennakko - $isa_valmistuksessa;

    // Is‰tuotteen myyntiennuste
    $isa_myyntiennuste = $isa_budjetoitu_myynti - $isa_reaalisaldo;

    // Jos myyntiennuste on miinusta, nollataan ennuste, ettei se v‰henn‰ raaka-aine tarvetta (jo valmistetuista tuotteista ei voida k‰ytt‰‰ raaka-aineita)
    $isa_myyntiennuste = ($isa_myyntiennuste < 0) ? 0 : $isa_myyntiennuste;

    // Lapsen kulutusennuste
    $lapsi_kerroin = $isatuote_row["kerroin"];
    $lapsi_kulutus += ($isa_myyntiennuste * $lapsi_kerroin);
  }

  // Lasketaan raaka-aineen reaalisaldo
  $lapsi_reaalisaldo = $lapsi_saldo + $lapsi_tilattu - $lapsi_varattu - $lapsi_ennakko - $lapsi_valmistuksessa;

  // Lasketaan raaka-aineen p‰iv‰kulutus
  $lapsi_paivakulutus = round($lapsi_vuosikulutus / 240, 6);

  // Lasketaan raaka-aineen riittop‰iv‰t
  $lapsi_riittopv = ($lapsi_paivakulutus == 0) ? t("Ei tiedossa") : floor($lapsi_reaalisaldo / $lapsi_paivakulutus);

  // Lasketaan raaka-aineen kulutusennuste
  $lapsi_kulutusennuste = $lapsi_kulutus + ($lapsi_paivakulutus * $toimittajarow['toimitusaika']);

  // Lasketaan raaka-aineen ostosuositus
  $lapsi_ostosuositus = round($lapsi_kulutusennuste - $lapsi_reaalisaldo);

  // Pyˆristet‰‰n raaka-aineen ostosuositus toimittajan pakkauskokoon
  $lapsi_ostettavamaara = round($lapsi_ostosuositus / $toimittajarow['pakkauskoko']) * $toimittajarow['pakkauskoko'];

  // Palautettava array
  $tuoterivi = array();
  $tuoterivi['reaalisaldo']              = $lapsi_reaalisaldo;
  $tuoterivi['varastosaldo']             = $lapsi_saldo;
  $tuoterivi['tilattu']                  = $lapsi_tilattu;
  $tuoterivi['varattu']                  = $lapsi_varattu;
  $tuoterivi['ennakko']                  = $lapsi_ennakko;
  $tuoterivi['valmistuksessa']           = $lapsi_valmistuksessa;
  $tuoterivi['paivakulutus']             = $lapsi_paivakulutus;
  $tuoterivi['vuosikulutus']             = $lapsi_vuosikulutus;
  $tuoterivi['riittopv']                 = $lapsi_riittopv;
  $tuoterivi['kulutusennuste']           = $lapsi_kulutusennuste;
  $tuoterivi['kulutus']                  = $lapsi_kulutus;
  $tuoterivi['toimitusaika']             = $toimittajarow['toimitusaika'];
  $tuoterivi['ostosuositus']             = $lapsi_ostosuositus;
  $tuoterivi['ostoeramaara']             = $lapsi_ostettavamaara;
  $tuoterivi['pakkauskoko']              = $toimittajarow['pakkauskoko'];
  $tuoterivi['toimittajan_tunnus']       = $toimittajarow['tunnus'];
  $tuoterivi['toimittajan_ytunnus']      = $toimittajarow['ytunnus'];
  $tuoterivi['toimittajan_nimi']         = $toimittajarow['nimi'];
  $tuoterivi['ostohinta']                = $toimittajarow['ostohinta'];
  $tuoterivi['ostosuosituksen_arvo']     = $toimittajarow['ostohinta'] * $lapsi_ostosuositus;
  $tuoterivi['ostosuosituksen_paino']    = $toimittajarow['tuotemassa'] * $lapsi_ostosuositus;
  $tuoterivi['ostosuosituksen_tilavuus'] = $toimittajarow['tilavuus'] * $lapsi_ostosuositus;
  $tuoterivi['budjetin_peruste']         = $budjetin_peruste;

  return $tuoterivi;
}

// Jos saadaan muut parametrit tehd‰‰n niist‰ muuttujat
if (isset($muutparametrit)) {
  foreach (explode("##", $muutparametrit) as $muutparametri) {
    list($a, $b) = explode("=", $muutparametri);
    ${$a} = $b;
  }
  $tee = "";
}

// Toimittajahaku
if ($ytunnus != "" and $toimittajaid == "") {

  // Tehd‰‰n muut parametrit
  $muutparametrit = "";
  unset($_POST["toimittajaid"]);

  foreach ($_POST as $key => $value) {
    $muutparametrit .= $key."=".$value."##";
  }

  require "inc/kevyt_toimittajahaku.inc";

  if ($toimittajaid == 0) {
    $tee = "ƒLƒMEEMIHINKƒƒN";
    $ehdotusnappi = "";
  }
  else {
    $tee = "";
    $ehdotusnappi = "";
  }
}

// Tehd‰‰n ostotilaukset
if (isset($tee) and $tee == "TEE_OSTOTILAUKSET") {

  $rows = 0;
  $edellinen_toimittaja = false;

  foreach ($ostettava_maara as $index => $maara) {

    $maara = (float) str_replace(",", ".", $maara);
    $toimittaja = mysql_real_escape_string($ostettava_toimittaja[$index]);
    $tuoteno = mysql_real_escape_string($ostettava_tuoteno[$index]);

    // Oikellisuustarkastus hoidetaan javascriptill‰, ei voi tulla kun numeroita!
    if ($maara != 0) {

      if ($edellinen_toimittaja != $toimittaja) {

        $query = "SELECT *
                  FROM toimi
                  WHERE toimi.yhtio = '{$kukarow["yhtio"]}'
                  AND toimi.tunnus  = '$toimittaja'";
        $result = pupe_query($query);
        $toimittajarow = mysql_fetch_assoc($result);

        $query = "INSERT INTO lasku SET
                  yhtio               = '{$kukarow["yhtio"]}',
                  yhtio_nimi          = '{$yhtiorow["nimi"]}',
                  yhtio_osoite        = '{$yhtiorow["osoite"]}',
                  yhtio_postino       = '{$yhtiorow["postino"]}',
                  yhtio_postitp       = '{$yhtiorow["postitp"]}',
                  yhtio_maa           = '{$yhtiorow["maa"]}',
                  toim_nimi           = '{$yhtiorow["nimi"]}',
                  toim_osoite         = '{$yhtiorow["osoite"]}',
                  toim_postino        = '{$yhtiorow["postino"]}',
                  toim_postitp        = '{$yhtiorow["postitp"]}',
                  toim_maa            = '{$yhtiorow["maa"]}',
                  nimi                = '{$toimittajarow["nimi"]}',
                  osoite              = '{$toimittajarow["osoite"]}',
                  postino             = '{$toimittajarow["postino"]}',
                  postitp             = '{$toimittajarow["postitp"]}',
                  maa                 = '{$toimittajarow["maa"]}',
                  valkoodi            = '{$toimittajarow["oletus_valkoodi"]}',
                  toimaika            = now(),
                  laatija             = '{$kukarow["kuka"]}',
                  luontiaika          = now(),
                  tila                = 'O',
                  toimitusehto        = '{$toimittajarow["toimitusehto"]}',
                  liitostunnus        = '{$toimittajarow["tunnus"]}',
                  ytunnus             = '{$toimittajarow["ytunnus"]}',
                  ovttunnus           = '{$toimittajarow["ovttunnus"]}',
                  tilausyhteyshenkilo = '{$toimittajarow["yhteyshenkilo"]}'";
        $result = pupe_query($query);
        $otunnus = mysql_insert_id($GLOBALS["masterlink"]);

        $rows++;
        $edellinen_toimittaja = $toimittaja;
      }

      $query = "SELECT tuote.try,
                tuote.osasto,
                tuote.nimitys,
                tuote.yksikko,
                tuotteen_toimittajat.ostohinta
                FROM tuote
                JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tuote.yhtio
                  AND tuotteen_toimittajat.tuoteno      = tuote.tuoteno
                  AND tuotteen_toimittajat.liitostunnus = '{$toimittajarow["tunnus"]}')
                WHERE tuote.yhtio                       = '{$kukarow["yhtio"]}'
                AND tuote.tuoteno                       = '$tuoteno'";
      $result = pupe_query($query);
      $tuoterow = mysql_fetch_assoc($result);

      $query = "INSERT INTO tilausrivi SET
                yhtio     = '{$kukarow["yhtio"]}',
                tyyppi    = 'O',
                toimaika  = now(),
                kerayspvm = now(),
                otunnus   = '$otunnus',
                tuoteno   = '$tuoteno',
                try       = '{$tuoterow["try"]}',
                osasto    = '{$tuoterow["osasto"]}',
                nimitys   = '{$tuoterow["nimitys"]}',
                tilkpl    = '$maara',
                yksikko   = '{$tuoterow["yksikko"]}',
                varattu   = '$maara',
                hinta     = '{$tuoterow["ostohinta"]}',
                laatija   = '{$kukarow["kuka"]}',
                laadittu  = now()";
      $result = pupe_query($query);
    }
  }

  echo $rows." ".t('ostotilausta muodostettu.');
  echo "<br><br>";
  $tee = "";
}

// Tehd‰‰n raportti
if (isset($ehdotusnappi) and $ehdotusnappi != "") {

  $tuote_where       = ""; // tuote-rajauksia
  $toimittaja_join   = ""; // toimittaja-rajauksia
  $toimittaja_select = ""; // toimittaja-rajauksia
  $abc_join          = ""; // abc-rajauksia
  $toggle_counter    = 0;

  if (isset($mul_osasto) and count($mul_osasto) > 0) {
    $tuote_where .= " and tuote.osasto in (".implode(",", $mul_osasto).")";
  }

  if (isset($mul_try) and count($mul_try) > 0) {
    $tuote_where .= " and tuote.try in (".implode(",", $mul_try).")";
  }

  if (isset($mul_tme) and count($mul_tme) > 0) {
    $tuote_where .= " and tuote.tuotemerkki in ('".implode("','", $mul_tme)."')";
  }

  if ($toimittajaid != '') {
    // Jos ollaan rajattu toimittaja, niin otetaan vain sen toimittajan tuotteet ja laitetaan mukaan selectiin
    $toimittaja_join = "JOIN tuotteen_toimittajat ON (tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno and liitostunnus = '$toimittajaid')";
    $toimittaja_select = "tuotteen_toimittajat.liitostunnus toimittaja";
  }
  else {
    // Jos toimittajaa ei olla rajattu, haetaan tuotteen oletustoimittaja subqueryll‰
    $toimittaja_select = "(SELECT liitostunnus FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio = tuote.yhtio AND tuotteen_toimittajat.tuoteno = tuote.tuoteno ORDER BY if(jarjestys = 0, 9999, jarjestys), tunnus LIMIT 1) toimittaja";
  }

  if ($abcrajaus != "") {

    if ($yhtiorow["varaako_jt_saldoa"] != "") {
      $lisavarattu = " + tilausrivi.varattu";
    }
    else {
      $lisavarattu = "";
    }

    // katotaan JT:ss‰ olevat tuotteet ABC-analyysi‰ varten, koska ne pit‰‰ includata aina!
    $query = "SELECT group_concat(distinct concat(\"'\",tilausrivi.tuoteno,\"'\") separator ',')
              FROM tilausrivi USE INDEX (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
              JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio
                AND tuote.tuoteno      = tilausrivi.tuoteno
                AND tuote.ei_saldoa    = ''
                AND tuote.status      != 'P'
                AND tuote.ostoehdotus != 'E'
                $tuote_where)
              WHERE tilausrivi.yhtio   = '{$kukarow["yhtio"]}'
              AND tilausrivi.tyyppi    IN  ('L','G')
              AND tilausrivi.var       = 'J'
              AND tilausrivi.jt $lisavarattu > 0";
    $vtresult = pupe_query($query);
    $vrow = mysql_fetch_array($vtresult);

    $jt_tuotteet = "''";

    if ($vrow[0] != "") {
      $jt_tuotteet = $vrow[0];
    }

    // joinataan ABC-aputaulu katteen mukaan lasketun luokan perusteella
    $abc_join = " JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (abc_aputaulu.yhtio = tuote.yhtio
          AND abc_aputaulu.tuoteno = tuote.tuoteno
          AND abc_aputaulu.tyyppi = '$abcrajaustapa'
          AND (luokka <= '$abcrajaus' or luokka_osasto <= '$abcrajaus' or luokka_try <= '$abcrajaus' or tuote_luontiaika >= date_sub(current_date, interval 12 month) or abc_aputaulu.tuoteno in ($jt_tuotteet))) ";
  }

  // Haetaan raaka-aineet, jotka osuvat hakuehtoihin
  $query = "SELECT DISTINCT
            tuote.tuoteno,
            tuote.nimitys,
            $toimittaja_select
            FROM tuote
            JOIN tuoteperhe ON (tuote.tuoteno = tuoteperhe.tuoteno AND tuote.yhtio = tuoteperhe.yhtio AND tuoteperhe.tyyppi = 'R')
            $toimittaja_join
            $abc_join
            WHERE tuote.yhtio      = '{$kukarow["yhtio"]}'
            AND tuote.ei_saldoa    = ''
            AND tuote.status      != 'P'
            AND tuote.ostoehdotus != 'E'
            $tuote_where
            ORDER BY toimittaja, tuote.try, tuote.tuoteno";
  $res = pupe_query($query);

  echo t("Tuotteita")." ".mysql_num_rows($res)." ".t("kpl").".<br>\n";
  echo t("N‰ytet‰‰n ostotarve aikav‰lille").": $nykyinen_alku - $nykyinen_loppu";

  $ostoehdotus_riveja = 0;

  if (mysql_num_rows($res) > 0) {

    // Kootaan raportti
    echo "<form method='post'>";
    echo "<table>";

    $EDtoimittaja = false;
    $toimittaja_header_piirretty = false;
    $formin_pointteri = 0;

    // loopataan tuotteet l‰pi
    while ($row = mysql_fetch_assoc($res)) {

      // Haetaan tuotteen tiedot
      $tuoterivi = teerivi($row["tuoteno"], $row["toimittaja"]);

      // Toimittaja vaihtuu
      if ($tuoterivi['toimittajan_tunnus'] != $EDtoimittaja or $EDtoimittaja === false) {
        $toimittaja_header = "<tr>";
        $toimittaja_header .= "<td class='back' colspan='8'><font class='head'><br>{$tuoterivi["toimittajan_ytunnus"]} {$tuoterivi["toimittajan_nimi"]}</font></td>";
        $toimittaja_header .= "</tr>";
        $toimittaja_header .= "<tr>";
        $toimittaja_header .= "<th>".t("Tuotenumero")."</th>";
        $toimittaja_header .= "<th>".t("Nimitys")."</th>";
        $toimittaja_header .= "<th>".t("Kulutusennuste")."</th>";
        $toimittaja_header .= "<th>".t("Vuosikulutus")."</th>";
        $toimittaja_header .= "<th>".t("Riitto Pv")."</th>";
        $toimittaja_header .= "<th>".t("Reaalisaldo")."</th>";
        $toimittaja_header .= "<th>".t("Ostosuositus")."</th>";
        $toimittaja_header .= "<th>".t("Ostohinta")."</th>";
        $toimittaja_header .= "<th>".t("Ostosuosituksen arvo")."</th>";
        $toimittaja_header .= "<th>".t("Ostosuosituksen paino")."</th>";
        $toimittaja_header .= "<th>".t("Ostosuosituksen tilavuus")."</th>";
        $toimittaja_header .= "<th></th>";
        $toimittaja_header .= "<th>".t("Ostoer‰m‰‰r‰")."</th>";
        $toimittaja_header .= "</tr>";
        $toimittaja_header_piirretty = false;
      }

      $EDtoimittaja = $tuoterivi['toimittajan_tunnus'];
      $EDtoimittaja_ytunnus = $tuoterivi['toimittajan_ytunnus'];
      $EDtoimittaja_nimi = $tuoterivi['toimittajan_nimi'];

      // Jos tuotetta ei tarvitse ostaa, ei n‰ytet‰ sit‰ ostoehdotuksessa
      if ($tuoterivi['ostoeramaara'] <= 0) {
        continue;
      }

      $ostoehdotus_riveja++;

      // Pit‰‰ s‰ilytt‰‰ table-headeria muuttujassa, sill‰ voi olla ett‰ toimittajalle ei tule yht‰‰n tuoterivi‰ ehdotukseen (eik‰ haluta piirt‰‰ turhaa headeri‰)
      if ($toimittaja_header_piirretty == false) {
        echo $toimittaja_header;
        $toimittaja_header_piirretty = true;
      }

      echo "<tr class='aktiivi'>";
      echo "<td>{$row["tuoteno"]}</td>";
      echo "<td>{$row["nimitys"]}</td>";
      echo "<td style='text-align: right;'>{$tuoterivi["kulutusennuste"]}</td>";
      echo "<td style='text-align: right;'>{$tuoterivi["vuosikulutus"]}</td>";
      echo "<td style='text-align: right;'>{$tuoterivi["riittopv"]}</td>";
      echo "<td style='text-align: right;'>{$tuoterivi["reaalisaldo"]}</td>";
      echo "<td style='text-align: right;'>{$tuoterivi["ostosuositus"]}</td>";
      echo "<td style='text-align: right;'>{$tuoterivi["ostohinta"]}</td>";
      echo "<td style='text-align: right;'>{$tuoterivi["ostosuosituksen_arvo"]}</td>";
      echo "<td style='text-align: right;'>{$tuoterivi["ostosuosituksen_paino"]}</td>";
      echo "<td style='text-align: right;'>{$tuoterivi["ostosuosituksen_tilavuus"]}</td>";

      // Tehd‰‰n Toggle-nappi, jolla voidaan n‰ytt‰‰ matikkainfo alla
      $toggle_counter++;
      echo "<td>";
      echo "<a href='#' style='text-decoration:none;' class='toggle_rivit' id='$toggle_counter'><img src='{$palvelin}pics/lullacons/info.png'></a>";
      echo "</td>";

      // Tuotteella ei ole toimittajaa
      if (empty($EDtoimittaja)) {
        echo "<td style='text-align: right;'>{$tuoterivi["ostoeramaara"]}</td>";
      }
      else {
        echo "<td style='text-align: right;'>";
        echo "<input size='8' style='text-align: right;' type='text' name='ostettava_maara[$formin_pointteri]' value='{$tuoterivi["ostoeramaara"]}' id='vain_numeroita'>";
        echo "<input type='hidden' name='ostettava_tuoteno[$formin_pointteri]' value='{$row["tuoteno"]}'>";
        echo "<input type='hidden' name='ostettava_toimittaja[$formin_pointteri]' value='{$tuoterivi["toimittajan_tunnus"]}'>";
        echo "</td>";
        $formin_pointteri++;
      }
      echo "</tr>";

      // Tehd‰‰n yks hidden rivi t‰h‰n alle, jossa on kaikki luvut ja kaavat, jota on tehty valmistustarpeen laskemiseksi
      echo "<tr class='togglettava_rivi_$toggle_counter' style='display: none;'>";
      echo "<td colspan='3'>";

      echo "<table>";
      echo "<tr><td>".t("Reaalisaldo")."      </td><td>{$tuoterivi['reaalisaldo']}    </td></tr>";
      echo "<tr><td>".t("Varastosaldo")."      </td><td>{$tuoterivi['varastosaldo']}    </td></tr>";
      echo "<tr><td>".t("Tilattu")."        </td><td>{$tuoterivi['tilattu']}      </td></tr>";
      echo "<tr><td>".t("Varattu")."        </td><td>{$tuoterivi['varattu']}      </td></tr>";
      echo "<tr><td>".t("Ennakkotilaukset")."    </td><td>{$tuoterivi['ennakko']}      </td></tr>";
      echo "<tr><td>".t("Valmistuksessa")."    </td><td>{$tuoterivi['valmistuksessa']}    </td></tr>";
      echo "<tr><td>".t("P‰iv‰kulutus")."      </td><td>{$tuoterivi['paivakulutus']}    </td></tr>";
      echo "<tr><td>".t("Vuosikulutus")."         </td><td>{$tuoterivi['vuosikulutus']} (".t("rullaava 12kk").")</td></tr>";
      echo "<tr><td>".t("Riitto p‰iv‰t")."    </td><td>{$tuoterivi['riittopv']}      </td></tr>";
      echo "<tr><td>".t("Kulutusennuste")."    </td><td>{$tuoterivi['kulutusennuste']}    </td></tr>";
      echo "<tr><td>".t("Budjetoitu kulutus")."  </td><td>{$tuoterivi['kulutus']}      </td></tr>";
      echo "<tr><td>".t("Toimitusaika")."      </td><td>{$tuoterivi['toimitusaika']}    </td></tr>";
      echo "<tr><td>".t("Ostosuositus")."      </td><td>{$tuoterivi['ostosuositus']}    </td></tr>";
      echo "<tr><td>".t("Ostoer‰m‰‰r‰")."      </td><td>{$tuoterivi['ostoeramaara']}    </td></tr>";
      echo "<tr><td>".t("Pakkauskoko")."      </td><td>{$tuoterivi['pakkauskoko']}    </td></tr>";
      echo "</table>";

      echo "</td><td colspan='10'>";

      echo t("Reaalisaldo")." = ".t("Varastosaldo")." + ".t("Tilattu")." - ".t("Varattu")." - ".t("Ennakkotilaukset")." - ".t("Valmistuksessa")."<br>";
      echo "{$tuoterivi["reaalisaldo"]} = {$tuoterivi["varastosaldo"]} + {$tuoterivi["tilattu"]} - {$tuoterivi["varattu"]} - {$tuoterivi["ennakko"]} - {$tuoterivi["valmistuksessa"]}<br><br>";
      echo t("P‰iv‰kulutus")." = round(".t("Vuosikulutus")." / 240)<br>";
      echo "{$tuoterivi['paivakulutus']} = round({$tuoterivi['vuosikulutus']} / 240)<br><br>";
      echo t("Riitto P‰iv‰t")." = floor(".t("Reaalisaldo")." / ".t("P‰iv‰kulutus").")<br>";
      echo "{$tuoterivi['riittopv']} = floor({$tuoterivi['reaalisaldo']} / {$tuoterivi['paivakulutus']})<br><br>";

      echo "<font class='info'>";
      echo t("Budjetoitu kulutus").":<br><br>";

      foreach ($tuoterivi['budjetin_peruste'] as $budjetin_perusteet) {
        foreach ($budjetin_perusteet as $budjetin_peruste) {
          echo "Valmiste: ".$budjetin_peruste['tuote']."<br>";
          echo "Tuotteen status: ".$budjetin_peruste['status']."<br>";
          echo "Budjetin peruste: ".$budjetin_peruste['syy']."<br>";
          echo "Budjetoitu kulutus: ".$budjetin_peruste['budjetoitu_myynti']."<br><br>";
        }
      }

      echo "</font>";

      echo t("Kulutusennuste")." = ".t("Budjetoitu kulutus")." + (".t("P‰iv‰kulutus")." * ".t("Toimitusaika").")<br>";
      echo "{$tuoterivi['kulutusennuste']} = {$tuoterivi['kulutus']} + ({$tuoterivi['paivakulutus']} * {$tuoterivi['toimitusaika']})<br><br>";
      echo t("Ostosuositus")." = round(".t("Kulutusennuste")." - ".t("Reaalisaldo").")<br>";
      echo "{$tuoterivi['ostosuositus']} = round({$tuoterivi['kulutusennuste']} - {$tuoterivi['reaalisaldo']})<br><br>";
      echo t("Ostettava m‰‰r‰")." = round(".t("Ostosuositus")." / ".t("Pakkauskoko").") * ".t("Pakkauskoko")."<br>";
      echo "{$tuoterivi['ostoeramaara']} = round({$tuoterivi['ostosuositus']} / {$tuoterivi['pakkauskoko']}) * {$tuoterivi['pakkauskoko']}<br><br>";

      echo "</td></tr>";
    }

    echo "</table>";

    if ($ostoehdotus_riveja > 0) {
      echo "<br>";
      echo "<input type='hidden' name='tee' value='TEE_OSTOTILAUKSET' />";
      echo "<input type='submit' name='muodosta_ostotilaukset' value='".t('Muodosta ostotilaukset')."' />";
      echo "<br><br>";
    }

    echo "</form>";
    $tee = "";
  }

  if ($ostoehdotus_riveja == 0) {
    echo "<br><br>";
    echo "<font class='error'>Antamallasi rajauksella ei lˆydy yht‰‰n tuotetta ehdotukseen.</font><br>";
    echo "<br>";
    $tee = "";
  }
}

// N‰ytet‰‰n k‰yttˆliittym‰
if (!isset($tee) or $tee == "") {

  echo "<form method='post' autocomplete='off'>";

  echo "<table>";
  echo "<tr>";
  echo "<th>".t("Tuoterajaus")."</th>";
  echo "<td>";

  $monivalintalaatikot = array('OSASTO', 'TRY', 'TUOTEMERKKI');
  $monivalintalaatikot_normaali = array();
  require "tilauskasittely/monivalintalaatikot.inc";

  echo "</td>";
  echo "</tr>";

  $sel = array_fill_keys($multi_status, 'selected');

  echo "<tr>";
  echo "<th>".t("Valmisteen status")."</th>";
  echo "<td>";
  echo "<select multiple='multiple' class='multipleselect' name='multi_status[]' onchange='submit();'>";
  echo "<option value=''>".t("Ei valintaa")."</option>";
  echo product_status_options($sel);
  echo "</select>";

  echo "</td>";
  echo "</tr>";

  echo "<tr><th>".t("ABC-luokkarajaus ja rajausperuste")."</th><td>";

  echo "<select name='abcrajaus'>";
  echo "<option  value=''>".t("Valitse")."</option>";

  $teksti = "";
  for ($i=0; $i < count($ryhmaprossat); $i++) {
    $selabc = "";

    if ($i > 0) $teksti = t("ja paremmat");
    if ($org_rajaus == "{$i}##TM") $selabc = "SELECTED";

    echo "<option  value='$i##TM' $selabc>".t("Myynti").": {$ryhmanimet[$i]} $teksti</option>";
  }

  $teksti = "";
  for ($i=0; $i < count($ryhmaprossat); $i++) {
    $selabc = "";

    if ($i > 0) $teksti = t("ja paremmat");
    if ($org_rajaus == "{$i}##TK") $selabc = "SELECTED";

    echo "<option  value='$i##TK' $selabc>".t("Myyntikate").": {$ryhmanimet[$i]} $teksti</option>";
  }

  $teksti = "";
  for ($i=0; $i < count($ryhmaprossat); $i++) {
    $selabc = "";

    if ($i > 0) $teksti = t("ja paremmat");
    if ($org_rajaus == "{$i}##TR") $selabc = "SELECTED";

    echo "<option  value='$i##TR' $selabc>".t("Myyntirivit").": {$ryhmanimet[$i]} $teksti</option>";
  }

  $teksti = "";
  for ($i=0; $i < count($ryhmaprossat); $i++) {
    $selabc = "";

    if ($i > 0) $teksti = t("ja paremmat");
    if ($org_rajaus == "{$i}##TP") $selabc = "SELECTED";

    echo "<option  value='$i##TP' $selabc>".t("Myyntikappaleet").": {$ryhmanimet[$i]} $teksti</option>";
  }

  echo "</select>";

  echo "<tr><th>".t("Toimittaja")."</th><td>";
  if ($toimittajaid == "") {
    echo "<input type='text' size='20' name='ytunnus' value='$ytunnus'>";
  }
  else {
    $query = "SELECT *
              from toimi
              where yhtio = '{$kukarow["yhtio"]}'
              and tunnus  = '$toimittajaid'";
    $result = pupe_query($query);
    $toimittaja = mysql_fetch_assoc($result);

    echo "$toimittaja[nimi] $toimittaja[nimitark]";
    echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
  }
  echo "</td></tr>";

  echo "<tr>";
  echo "<th>".t("Alkup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>";
  echo "<td>";
  echo "<input type='text' name='ppa1' value='$ppa1' size='5'>";
  echo "<input type='text' name='kka1' value='$kka1' size='5'>";
  echo "<input type='text' name='vva1' value='$vva1' size='5'>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Loppup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>";
  echo "<td>";
  echo "<input type='text' name='ppl1' value='$ppl1' size='5'>";
  echo "<input type='text' name='kkl1' value='$kkl1' size='5'>";
  echo "<input type='text' name='vvl1' value='$vvl1' size='5'>";
  echo "</td>";
  echo "</tr>";

  $sel = array(
    'A' => $tilaustuotteiden_kasittely == 'A' ? 'SELECTED' : '',
    'B' => $tilaustuotteiden_kasittely == 'B' ? 'SELECTED' : '',
    'C' => $tilaustuotteiden_kasittely == 'C' ? 'SELECTED' : '',
  );

  echo "<tr><th>".t("Tilaustuotteiden k‰sittely")."</th><td>";
  echo "<select name='tilaustuotteiden_kasittely'>";
  echo "<option value='A' {$sel['A']}>".t("Tilaustuotteiden kulutusennuste on j‰lkitoimitusrivit")."</option>";
  echo "<option value='B' {$sel['B']}>".t("Tilaustuotteiden kulutusennuste on budjetti/myynti")."</option>";
  echo "<option value='C' {$sel['C']}>".t("Tilaustuotteiden kulutusennuste on j‰lkitoimitusrivit + budjetti/myynti")."</option>";
  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "</table>";

  echo "<br><input type='submit' name='ehdotusnappi' value = '".t("Aja ostoehdotus")."'></form>";
}

require "inc/footer.inc";
