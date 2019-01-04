<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 2;

// Ei käytetä pakkausta
$compression = FALSE;

if (isset($_POST["tee_lataa"])) {
  if ($_POST["tee_lataa"] == 'lataa_tiedosto') $lataa_tiedosto = 1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

if (@include "../inc/parametrit.inc");
elseif (@include "parametrit.inc");
else exit;

if (isset($tee_lataa)) {
  if ($tee_lataa == "lataa_tiedosto") {
    readfile("/tmp/".basename($tmpfilenimi));
    exit;
  }
}
else {

  function hinnastohinnat($tuoteno, $valkoodi, $maa = "", $kurssi = 1, $laji = "") {
    global $kukarow;

    $query =  "SELECT *
               FROM hinnasto
               WHERE yhtio   = '$kukarow[yhtio]'
               and tuoteno   = '$tuoteno'
               and tuoteno  != ''
               and laji      = '{$laji}'
               and valkoodi  = '{$valkoodi}'
               and maa       in ('$maa','')
               and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
               and ((minkpl <= '1' and maxkpl >= '1') or (minkpl = 0 and maxkpl = 0))
               ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), maa DESC
               LIMIT 1";
    $hresult = pupe_query($query);

    $hinta = "";
    $alv = "";

    if (mysql_num_rows($hresult) == 1) {
      $hrow   = mysql_fetch_assoc($hresult);
      $hinta  = $hrow["hinta"];
      $alv    = $hrow["alv"];
    }
    elseif ($laskurow['valkoodi'] != $yhtiorow['valkoodi']) {
      // toistaiseksi jos ei ole, niin ei ole
      #$rrow["myymalahinta"] = hintapyoristys($rrow["myymalahinta"] / $kurssi["kurssi"]);
      #$rrow["myyntihinta"]  = hintapyoristys($rrow["myyntihinta"]  / $kurssi["kurssi"]);
    }
    return array($hrow["hinta"], $hrow["alv"]);
  }

  echo "<font class='head'>".t("Masterdata excel")."</font><hr>";

  $ytunnus = trim($ytunnus);
  if (isset($masterdataexcel_datatyyppi)) $datatyyppi = $masterdataexcel_datatyyppi;

  $jointilausrivi = "";

  // tarkistetaan tilausnumero, jos annettu
  if (isset($tilausnumero) and !empty($tilausnumero)) {

    $query = "  SELECT tunnus
                FROM lasku
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus = '$tilausnumero'
                ";
    $tilres = pupe_query($query);

    if (mysql_num_rows($tilres) == 1) {
      $jointilausrivi = " JOIN tilausrivi ON (tilausrivi.yhtio = tuote.yhtio AND tilausrivi.otunnus = '$tilausnumero' AND tilausrivi.tuoteno = tuote.tuoteno AND tilausrivi.tyyppi = 'L' AND tilausrivi.var not in ('O','S'))";
    }
    else {
      $tee = "";
      echo "<br><br><font class='error'>".t("Tilausnumerolla ei löytynyt yhtään tilausta!")."</font><br>";
    }
  }
  else {
    $tilausnumero = "";
  }

  if ($tee != '' and $kukarow["extranet"] == '' and isset($ajahinnasto) and empty($jointilausrivi)) {

    if (isset($muutparametrit)) {
      $muutparametrit  = unserialize(urldecode($muutparametrit));
      $mul_osasto      = $muutparametrit[0];
      $mul_try         = $muutparametrit[1];
      $mul_tme         = $muutparametrit[2];
    }

    $muutparametrit = array($mul_osasto, $mul_try, $mul_tme);
    $muutparametrit = urlencode(serialize($muutparametrit));

    require "inc/asiakashaku.inc";

    $asiakas = $asiakasrow["tunnus"];
    $ytunnus = $asiakasrow["ytunnus"];
  }
  elseif ($kukarow["extranet"] != '' and isset($ajahinnasto)) {

    echo t("VIRHE: Tämä ohjelma ei toimi extranetissä.")."<br><br>";
    exit;
  }

  // jos tultiin asiakashausta ja jouduttiin valitsemaan asiakas monesta
  if (!isset($asiakasrow["tunnus"]) and isset($asiakasid)) $asiakas = $asiakasid;

  //Käyttöliittymä
  echo "<br>";
  echo "<table><form method='post'>";
  echo "<input type='hidden' name='tee' value='kaikki'>";

  echo "<tr><th>".t("Datatyyppi").":</th><td><select name='datatyyppi' onchange='submit()'>";
  $sel = "";
  if ($datatyyppi == "orderform") {
    $sel = "SELECTED";
  }
  echo "<option value='masterdata'>".t("Masterdata")."</option>";
  echo "<option value='orderform' $sel>".t("Orderform")."</option>";
  echo "</select></td></tr>";

  echo "<tr><th>".t("Kieli").":</th><td><select name='hinkieli'>";

  foreach ($GLOBALS["sanakirja_kielet"] as $sanakirja_kieli => $sanakirja_kieli_nimi) {
    if (strlen($sanakirja_kieli) == 2) {
      $sel = "";

      if ($hinkieli == $sanakirja_kieli) {
        $sel = "SELECTED";
      }
      elseif ($asiakasrow["kieli"] == $sanakirja_kieli and $hinkieli == "") {
        $sel = "SELECTED";
      }

      echo "<option value='$sanakirja_kieli' $sel>".t($sanakirja_kieli_nimi)."</option>";
    }
  }

  echo "</select></td></tr>";

  if (!isset($hinkieli)) {
    $hinkieli = $yhtiorow['kieli'];
  }

  if ($datatyyppi == "orderform") {
    if ($asiakas > 0) {
      echo "<tr><th>".t("Asiakas").":</th><td><input type='hidden' name='ytunnus' value='$ytunnus'>$ytunnus $asiakasrow[nimi]</td></tr>";

      echo "<input type='hidden' name='asiakasid' value='$asiakas'></td></tr>";
    }
    else {
      echo "<tr><th>".t("Asiakas").":</th><td><input type='text' name='ytunnus' size='15' value=''></td></tr>";
    }
  }
  else {
    echo "<tr><th>".t("Tilausnumero").":<br>(".t("rajaus tilauksen tuotteilla, valinnainen").")</th><td><input type='text' name='tilausnumero' size='15' value='$tilausnumero'>$tilausnumero</td></tr>";
  }

  // Monivalintalaatikot (osasto, try tuotemerkki...)
  // Määritellään mitkä latikot halutaan mukaan
  $monivalintalaatikot = array("OSASTO", "TRY", "TUOTEMERKKI");

  echo "<tr><th>".t("Osasto")." / ".t("tuoteryhmä").":</th><td nowrap>";

  if (@include "tilauskasittely/monivalintalaatikot.inc");
  elseif (@include "monivalintalaatikot.inc");

  echo "</td></tr>";

  echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
        $(function() {
          $('.check_all').on('click', function() {
            var id = $(this).val();

            if ($(this).is(':checked')) {
              $('.'+id).attr('checked', true);
            }
            else {
              $('.'+id).attr('checked', false);
            }
          });
        });
      </script>";

  // piirrellään tuotteen tiedot-valikko
  echo "<br><br>";
  echo "</table><br>";

  echo "<input type='submit' name='ajahinnasto' value='".t("Aja hinnasto")."'>";
  echo "</form>";

  if ($asiakas > 0) {
    echo "<form method='post'>";
    echo "<input type='submit' value='Valitse uusi asiakas'>";
    echo "<input type='hidden' name='datatyyppi' value='$datatyyppi'></td></tr>";
    echo "</form>";
  }

  if ($tee != '' and (($asiakas > 0 and $datatyyppi == 'orderform') or ($asiakas == 0 and $datatyyppi == 'masterdata')) and isset($ajahinnasto)) {

    $kieltolisa   = '';
    $sallitut_maat = '';

    if ($asiakas > 0) {
      $sallitut_maat   = $asiakasrow["toim_maa"] != '' ? $asiakasrow["toim_maa"] : $asiakasrow["maa"];
    }

    if ($sallitut_maat != "") {
      $kieltolisa = " and (tuote.vienti = '' or tuote.vienti like '%-$sallitut_maat%' or tuote.vienti like '%+%') and tuote.vienti not like '%+$sallitut_maat%' ";
    }

    if ($mul_osasto) {
      $osastolisa = " and tuote.osasto in (".implode(",", $mul_osasto).")";
    }

    if ($mul_try) {
      $trylisa = " and tuote.try in (".implode(",", $mul_try).")";
    }

    if ($mul_tme) {
      $tmelisa = " and tuote.tuotemerkki in ('".implode("','", $mul_tme)."')";
    }

    $valkoodi = $asiakasrow['valkoodi'] ? $asiakasrow['valkoodi'] : $yhtiorow['valkoodi'];

    $query = "SELECT kurssi
              FROM valuu
              WHERE nimi = '$valkoodi'
              and yhtio  = '$kukarow[yhtio]'";
    $asres = pupe_query($query);
    $kurssi = mysql_fetch_assoc($asres);

    $orderlisa = "";
    if ($datatyyppi == 'orderform') {
      $orderlisa = " ORDER BY tuotemerkki";
    }

    $query = "SELECT tuote.*
              FROM tuote
              {$jointilausrivi}
              WHERE tuote.yhtio      = '{$kukarow['yhtio']}'
              and tuote.status       NOT IN ('P','X')
              and tuote.tuotetyyppi  NOT IN ('A', 'B')
              and tuote.hinnastoon  != 'E'
              {$osastolisa}
              {$trylisa}
              {$tmelisa}
              {$kieltolisa}
              GROUP BY tuote.tunnus
              {$orderlisa}
              ";
    $rresult = pupe_query($query);

    if (mysql_num_rows($rresult) == 0) {
      $osuma = false;
    }
    else {
      // KAUTTALASKUTUSKIKKARE
      if (isset($GLOBALS['eta_yhtio']) and $GLOBALS['eta_yhtio'] != '') {
        // haetaan etäyhtiön tiedot
        $yhtiorow_eta = $yhtiorow = hae_yhtion_parametrit($GLOBALS['eta_yhtio']);
      }

      echo "<br><br><font class='message'>".t("Asiakashinnastoa luodaan...")."</font><br>";
      flush();

      if (@include_once "inc/ProgressBar.class.php");
      elseif (@include_once "ProgressBar.class.php");

      $bar = new ProgressBar();

      $elements = mysql_num_rows($rresult); // total number of elements to process

      $bar->initialize($elements); // print the empty bar

      if (@include "inc/pupeExcel.inc");
      elseif (@include "pupeExcel.inc");

      $worksheet    = new pupeExcel();

      $format_bold  = array("bold" => TRUE);
      $excelrivi    = 0;
      $excelsarake  = 0;
      $excelsheet   = 1;

      if (isset($worksheet) and $datatyyppi == 'masterdata') {
        $worksheet->writeString($excelrivi,  $excelsarake, t("Tuotenimi", $hinkieli), $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi,  $excelsarake, t("Tuotekoodi", $hinkieli), $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, t("EAN-koodi", $hinkieli), $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, t("Tullinimike", $hinkieli), $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, t("Alkuperämaa", $hinkieli), $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, t("Listahinta EUR", $hinkieli), $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, t("OVH EUR", $hinkieli), $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, t("Listahinta SEK", $hinkieli), $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, t("RRP SEK", $hinkieli), $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, t("Listahinta NOK", $hinkieli), $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, t("RRP NOK", $hinkieli), $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, t("Listahinta DKK", $hinkieli), $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, t("RRP DKK", $hinkieli), $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, t("Tuotekorkeus (cm)", $hinkieli), $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, t("Tuoteleveys (cm)", $hinkieli), $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, t("Tuotesyvyys (cm)", $hinkieli), $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, t("Tuotepaino (g)", $hinkieli), $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, t("Tuotteen sisältö", $hinkieli), $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, t("Myyntierän EAN", $hinkieli), $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, t("Myyntierä", $hinkieli), $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, t("Myyntierän korkeus (mm)", $hinkieli), $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, t("Myyntierän leveys (mm)", $hinkieli), $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, t("Myyntierän syvyys (mm)", $hinkieli), $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, t("Myyntierän nettopaino (g)", $hinkieli), $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, t("Myyntierän bruttopaino (g)", $hinkieli), $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, t("Mainosteksti EN", $hinkieli), $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, t("Mainosteksti FI", $hinkieli), $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, t("Mainosteksti SE", $hinkieli), $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, t("Mainosteksti NO", $hinkieli), $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, t("Incilista", $hinkieli), $format_bold);
        $excelsarake++;

        $excelrivi++;
      }

      $rivit = array();

      while ($rrow = mysql_fetch_assoc($rresult)) {
        $rivit[] = $rrow;
        $tuotemerkki_count[$rrow['tuotemerkki']] = $rrow['tuotemerkki'];
      }

      if (isset($worksheet) and $datatyyppi == 'orderform') {
        for ($i = 1; $i <= count($tuotemerkki_count); $i++) {

          $excelrivi    = 0;
          $excelsarake  = 0;

          $worksheet->writeString($excelrivi,  $excelsarake, t("Tuotekoodi", $hinkieli), $format_bold, $i);
          $excelsarake++;

          $worksheet->writeString($excelrivi,  $excelsarake, t("Tuotenimi", $hinkieli), $format_bold, $i);
          $excelsarake++;

          $worksheet->writeString($excelrivi,  $excelsarake, t("EAN-koodi", $hinkieli), $format_bold, $i);
          $excelsarake++;

          $worksheet->writeString($excelrivi,  $excelsarake, t("Kuvaus", $hinkieli), $format_bold, $i);
          $excelsarake++;

          $worksheet->writeString($excelrivi,  $excelsarake, t("", $hinkieli), $format_bold, $i);
          $excelsarake++;

          $worksheet->writeString($excelrivi,  $excelsarake, t("Kuvan linkki", $hinkieli), $format_bold, $i);
          $excelsarake++;

          $worksheet->writeString($excelrivi,  $excelsarake, t("Minimi tilausmäärä", $hinkieli), $format_bold, $i);
          $excelsarake++;

          $worksheet->writeString($excelrivi,  $excelsarake, t("Myyntihinta EUR", $hinkieli), $format_bold, $i);
          $excelsarake++;

          $worksheet->writeString($excelrivi,  $excelsarake, t("Myyntihinta SEK", $hinkieli), $format_bold, $i);
          $excelsarake++;

          $worksheet->writeString($excelrivi,  $excelsarake, t("RRP EUR", $hinkieli), $format_bold, $i);
          $excelsarake++;

          $worksheet->writeString($excelrivi,  $excelsarake, t("RRP EUR Baltics", $hinkieli), $format_bold, $i);
          $excelsarake++;

          $worksheet->writeString($excelrivi,  $excelsarake, t("RRP SEK", $hinkieli), $format_bold, $i);
          $excelsarake++;

          $worksheet->writeString($excelrivi,  $excelsarake, t("RRP NOK", $hinkieli), $format_bold, $i);
          $excelsarake++;

          $worksheet->writeString($excelrivi,  $excelsarake, t("RRP DKK", $hinkieli), $format_bold, $i);
          $excelsarake++;

          $worksheet->writeString($excelrivi,  $excelsarake, t("Tilausmäärä", $hinkieli), $format_bold, $i);
          $excelsarake++;

          $worksheet->writeString($excelrivi,  $excelsarake, t("Summa", $hinkieli), $format_bold, $i);
          $excelsarake++;
          $excelrivi++;
        }
      }

      $tuotemerkki_check = array();
      $sheet = 0;

      foreach ($rivit as $rrow) {
        $bar->increase();

        if (isset($GLOBALS['eta_yhtio']) and $GLOBALS['eta_yhtio'] != '' and $GLOBALS['koti_yhtio'] == $kukarow['yhtio']) {
          $query = "SELECT *
                    FROM tuote
                    WHERE yhtio = '{$GLOBALS["eta_yhtio"]}'
                    AND tuoteno = '$rrow[tuoteno]'";
          $tres_eta = pupe_query($query);
          $alehinrrow = mysql_fetch_assoc($tres_eta);
          $yhtiorow = $yhtiorow_eta;
        }
        else {
          $alehinrrow = $rrow;
        }

        // Haetaan asiakkaan oma hinta
        $laskurow["ytunnus"]           = $asiakasrow["ytunnus"];
        $laskurow["liitostunnus"]      = $asiakasrow["tunnus"];
        $laskurow["vienti"]            = $asiakasrow["vienti"];
        $laskurow["alv"]               = $asiakasrow["alv"];
        $laskurow["valkoodi"]          = $asiakasrow["valkoodi"];
        $laskurow["vienti_kurssi"]     = $kurssi['kurssi'];
        $laskurow["maa"]               = $asiakasrow["maa"];
        $laskurow['toim_ovttunnus']    = $asiakasrow["toim_ovttunnus"];
        $laskurow['yhtio_toimipaikka'] = $asiakasrow['toimipaikka'];

        $palautettavat_kentat = "hinta,netto,alehinta_alv,alehinta_val,hintaperuste,aleperuste";

        for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
          $palautettavat_kentat .= ",ale{$alepostfix}";
        }

        $hinnat = alehinta($laskurow, $alehinrrow, 1, '', '', '', $palautettavat_kentat, $GLOBALS['eta_yhtio']);

        // Kauttalaskutuksessa pitää otaa etäyhtiön tiedot
        if (isset($GLOBALS['eta_yhtio']) and $GLOBALS['eta_yhtio'] != '' and $GLOBALS['koti_yhtio'] == $kukarow['yhtio']) {
          $yhtiorow = $yhtiorow_eta;
        }

        // Otetaan erikoisalennus pois asiakashinnastosta
        // $hinnat['erikoisale'] = $asiakasrow["erikoisale"];
        $hinnat['erikoisale'] = 0;

        $hinta = $hinnat["hinta"];
        $netto = $hinnat["netto"];

        for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
          ${'ale'.$alepostfix} = $hinnat["ale{$alepostfix}"];
        }

        $alehinta_alv  = $hinnat["alehinta_alv"];
        $alehinta_val  = $hinnat["alehinta_val"];

        list($hinta, $lis_alv) = alv($laskurow, $rrow, $hinta, '', $alehinta_alv);

        if ((float) $hinta == 0) {
          $hinta = $rrow["myyntihinta"];
        }

        $hinta = laskuval($hinta, $laskurow["vienti_kurssi"]);

        $onko_asiakkaalla_alennuksia = FALSE;

        for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
          if (isset($hinnat["aleperuste"]["ale".$alepostfix]) and $hinnat["aleperuste"]["ale".$alepostfix] !== FALSE and $hinnat["aleperuste"]["ale".$alepostfix] < 13) {
            $onko_asiakkaalla_alennuksia = TRUE;
            break;
          }
        }

        // Jos tuote näytetään vain jos asiakkaalla on asiakasalennus tai asiakahinta niin skipataan se jos alea tai hintaa ei löydy
        if ($rrow["hinnastoon"] == "V" and (($hinnat["hintaperuste"] > 13 or $hinnat["hintaperuste"] === FALSE) and $onko_asiakkaalla_alennuksia === FALSE)) {
          continue;
        }
        else {
          $osuma = true;
        }

        if ($netto == "") {
          $alennukset = generoi_alekentta_php($hinnat, 'M', 'kerto');

          $asiakashinta = hintapyoristys($hinta * $alennukset);
        }
        else {
          $asiakashinta = hintapyoristys($hinta);
        }

        $asiakashinta_veroton    = 0;
        $asiakashinta_verollinen = 0;

        if ($yhtiorow["alv_kasittely"] == "") {
          // Hinnat sisältävät arvonlisäveron
          $asiakashinta_veroton     = round(($asiakashinta/(1+$lis_alv/100)), 2);
          $asiakashinta_verollinen  = $asiakashinta;
        }
        else {
          // Hinnat ovat nettohintoja joihin lisätään arvonlisävero
          $asiakashinta_veroton    = $asiakashinta;
          $asiakashinta_verollinen = round(($asiakashinta*(1+$lis_alv/100)), 2);
        }

        // listahinta EUR
        list($listahinta_eur, $listahinta_eur_alv) = hinnastohinnat($rrow['tuoteno'], "EUR", $laskurow["maa"], $laskurow["vienti_kurssi"], "");
        if (empty($listahinta_eur)) $listahinta_eur = $rrow['myyntihinta'];

        // suositushinta EUR
        list($suositushinta_eur, $suositushinta_eur_alv) = hinnastohinnat($rrow['tuoteno'], "EUR", $laskurow["maa"], $laskurow["vienti_kurssi"], "K");
        if (empty($suositushinta_eur)) $suositushinta_eur = $rrow['myymalahinta'];

        // listahinta SEK
        list($listahinta_sek, $listahinta_sek_alv) = hinnastohinnat($rrow['tuoteno'], "SEK", $laskurow["maa"], $laskurow["vienti_kurssi"], "");

        // suositushinta SEK
        list($suositushinta_sek, $suositushinta_sek_alv) = hinnastohinnat($rrow['tuoteno'], "SEK", $laskurow["maa"], $laskurow["vienti_kurssi"], "K");

        // listahinta NOK
        list($listahinta_nok, $listahinta_nok_alv) = hinnastohinnat($rrow['tuoteno'], "NOK", $laskurow["maa"], $laskurow["vienti_kurssi"], "");

        // suositushinta NOK
        list($suositushinta_nok, $suositushinta_nok_alv) = hinnastohinnat($rrow['tuoteno'], "NOK", $laskurow["maa"], $laskurow["vienti_kurssi"], "K");

        // listahinta DKK
        list($listahinta_dkk, $listahinta_dkk_alv) = hinnastohinnat($rrow['tuoteno'], "DKK", $laskurow["maa"], $laskurow["vienti_kurssi"], "");

        // suositushinta DKK
        list($suositushinta_dkk, $suositushinta_dkk_alv) = hinnastohinnat($rrow['tuoteno'], "DKK", $laskurow["maa"], $laskurow["vienti_kurssi"], "K");

        $rrow['tuotekorkeus']  = $rrow['tuotekorkeus'] * 100;
        $rrow['tuoteleveys']   = $rrow['tuoteleveys'] * 100;
        $rrow['tuotesyvyys']   = $rrow['tuotesyvyys'] * 100;
        $rrow['tuotemassa']    = $rrow['tuotemassa'] * 1000;

        $mainosteksti_fi = t_tuotteen_avainsanat($rrow, 'mainosteksti', "fi");
        $mainosteksti_en = t_tuotteen_avainsanat($rrow, 'mainosteksti', "en");
        $mainosteksti_se = t_tuotteen_avainsanat($rrow, 'mainosteksti', "se");
        $mainosteksti_no = t_tuotteen_avainsanat($rrow, 'mainosteksti', "no");

        // jos ei löydy, niin ei listata kotikielelläkään (fi)
        if ($mainosteksti_en == $rrow['mainosteksti']) $mainosteksti_en = "";
        if ($mainosteksti_se == $rrow['mainosteksti']) $mainosteksti_se = "";
        if ($mainosteksti_no == $rrow['mainosteksti']) $mainosteksti_no = "";

        $tuotteen_sisalto   = t_tuotteen_avainsanat($rrow, 'lisatieto_Tuotteen sisältö', $hinkieli);
        $myyntieran_ean     = t_tuotteen_avainsanat($rrow, 'lisatieto_Myyntierän EAN', $hinkieli);
        $myyntiera          = !empty($rrow['myynti_era']) ? $rrow['myynti_era'] : "";
        $myyntieran_korkeus = t_tuotteen_avainsanat($rrow, 'lisatieto_Myyntierän korkeus', $hinkieli);
        $myyntieran_leveys  = t_tuotteen_avainsanat($rrow, 'lisatieto_Myyntierän leveys', $hinkieli);
        $myyntieran_syvyys  = t_tuotteen_avainsanat($rrow, 'lisatieto_Myyntierän syvyys', $hinkieli);
        $myyntieran_npaino  = t_tuotteen_avainsanat($rrow, 'lisatieto_Myyntierän nettopaino', $hinkieli);
        $myyntieran_bpaino  = t_tuotteen_avainsanat($rrow, 'lisatieto_Myyntierän bruttopaino', $hinkieli);
        $incilista          = t_tuotteen_avainsanat($rrow, 'lisatieto_Incilista', $hinkieli);

        if ($tuotteen_sisalto   == 'lisatieto_Tuotteen sisältö')        $tuotteen_sisalto = "";
        if ($myyntieran_ean     == 'lisatieto_Myyntierän EAN')          $myyntieran_ean = "";
        if ($myyntieran_korkeus == 'lisatieto_Myyntierän korkeus')      $myyntieran_korkeus = "";
        if ($myyntieran_leveys  == 'lisatieto_Myyntierän leveys')       $myyntieran_leveys = "";
        if ($myyntieran_syvyys  == 'lisatieto_Myyntierän syvyys')       $myyntieran_syvyys = "";
        if ($myyntieran_npaino  == 'lisatieto_Myyntierän nettopaino')   $myyntieran_npaino = "";
        if ($myyntieran_bpaino  == 'lisatieto_Myyntierän bruttopaino')  $myyntieran_bpaino = "";
        if ($incilista          == 'lisatieto_Incilista')               $incilista = "";

        $query = "SELECT *
                  FROM tuotteen_toimittajat
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND tuoteno = '$rrow[tuoteno]'
                  ORDER BY if (jarjestys = 0, 9999, jarjestys) limit 1";
        $tuotetoim_res = pupe_query($query);
        $tuotetoimrow = mysql_fetch_assoc($tuotetoim_res);

        $rrow["eankoodi"] = empty($rrow["eankoodi"]) ? $tuotetoimrow["viivakoodi"] : $rrow["eankoodi"];
        $alkuperamaa = $tuotetoimrow['alkuperamaa'];

        if (isset($worksheet) and $datatyyppi == 'masterdata') {

          $excelsarake = 0;

          $worksheet->writeString($excelrivi, $excelsarake, $rrow["nimitys"]);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $rrow["tuoteno"]);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $rrow["eankoodi"]);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $rrow["tullinimike1"]);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $alkuperamaa);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $listahinta_eur);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $suositushinta_eur);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $listahinta_sek);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $suositushinta_sek);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $listahinta_nok);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $suositushinta_nok);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $listahinta_dkk);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $suositushinta_dkk);
          $excelsarake++;

          $worksheet->writeString($excelrivi, $excelsarake, $rrow['tuotekorkeus']);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $rrow['tuoteleveys'] );
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $rrow['tuotesyvyys']);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $rrow['tuotemassa']);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $tuotteen_sisalto);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $myyntieran_ean);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $myyntiera);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $myyntieran_korkeus);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $myyntieran_leveys);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $myyntieran_syvyys);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $myyntieran_npaino);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $myyntieran_bpaino);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $mainosteksti_en);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $mainosteksti_fi);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $mainosteksti_se);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $mainosteksti_no);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $incilista);
          $excelsarake++;

          $excelrivi++;
        }

        if (isset($worksheet) and $datatyyppi == 'orderform') {

          if (!in_array($rrow["tuotemerkki"], $tuotemerkki_check)) {
            array_push($tuotemerkki_check, $rrow["tuotemerkki"]);
            $sheet = $sheet + 1;
            $excelrivi = 1;
          }

          $excelsarake = 0;

          $worksheet->writeString($excelrivi, $excelsarake, $rrow["tuoteno"], "", $sheet, $rrow["tuotemerkki"]);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $rrow["nimitys"], "", $sheet, $rrow["tuotemerkki"]);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $rrow["eankoodi"], "", $sheet, $rrow["tuotemerkki"]);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $rrow["mainosteksti"], "", $sheet, $rrow["tuotemerkki"]);
          $excelsarake++;

          $excelsarake++; // tässä ilman otsikkoa tietoa koska tulossa, mistä?
          $excelsarake++; //tässä kuvan url, mistä?

          $worksheet->writeNumber($excelrivi, $excelsarake, $rrow["minimi_era"], "", $sheet, $rrow["tuotemerkki"]);
          $excelsarake++;

          $worksheet->writeNumber($excelrivi, $excelsarake, $asiakashinta_veroton, "", $sheet, $rrow["tuotemerkki"]);
          $excelsarake++;
          $worksheet->writeNumber($excelrivi, $excelsarake, $listahinta_sek, "", $sheet, $rrow["tuotemerkki"]);
          $excelsarake++;
          $worksheet->writeNumber($excelrivi, $excelsarake, $suositushinta_eur, "", $sheet, $rrow["tuotemerkki"]);
          $excelsarake++;

          $excelsarake++; //suositushinta eur baltics

          $worksheet->writeNumber($excelrivi, $excelsarake, $suositushinta_sek, "", $sheet, $rrow["tuotemerkki"]);
          $excelsarake++;
          $worksheet->writeNumber($excelrivi, $excelsarake, $suositushinta_nok, "", $sheet, $rrow["tuotemerkki"]);
          $excelsarake++;
          $worksheet->writeNumber($excelrivi, $excelsarake, $suositushinta_dkk, "", $sheet, $rrow["tuotemerkki"]);
          $excelsarake++;

          $excelsarake++; //order qty, jätetään tyhjäksi

          $kaavarivi = $excelrivi + 1;

          $worksheet->writeFormula($excelrivi, $excelsarake, "=H{$kaavarivi}*O{$kaavarivi}", "", $sheet, $rrow["tuotemerkki"]);
          $excelsarake++;

          $excelrivi++;
        }
      }
    }


    if ($osuma == false) {

      echo "<br><br><font class='error'>".t("Valitulla rajauksella ei löydy tuotteita!")."</font><br>";

    }
    elseif (isset($worksheet)) {

      $excelnimi = $worksheet->close();

      echo "<br><br><table>";
      echo "<tr><th>".t("Tallenna hinnasto").":</th>";
      echo "<form method='post' class='multisubmit'>";
      echo "<input type='hidden' name='tee_lataa' value='lataa_tiedosto'>";
      echo "<input type='hidden' name='kaunisnimi' value='".t("Asiakashinnasto").".xlsx'>";
      echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
      echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
      echo "</table><br>";
    }
  }

  if (@include "inc/footer.inc");
  elseif (@include "footer.inc");
  else exit;
}
