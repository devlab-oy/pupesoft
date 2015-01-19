<?php

if (isset($_COOKIE['laiteluettelo_keksi'])) $valitut_sarakkeet = unserialize(urldecode($_COOKIE['laiteluettelo_keksi']));

if (isset($_POST['piirtele_laiteluettelo'])) {

  // Piirrellään laiteluettelo-valikko
  echo "<br><br>";

  // Määritellään jostain halutut kentät
  $kiissit = array(
    "SP-FSNN-CO-NOC" => "SignalONE NOC", 
    "SP-FSNN-CO-MDM" => "SignalONE Mobile Device Management",
    "SP-FSNN-CO-MAINTENANCE" => "SignalControl OnSite Preventive Maintenance",
    "SP-FSNN-SE-LCC" => "LifecycleCare",
    "SP-FSNN-SE-LCM" => "LifecycleManagement"
  );

  echo "<form name='aja_ja_tallenna' method='post'>";
  echo "<table width='600'>";
  echo "<tr><th>".t("Valitse sarakkeet")."</th></tr>";

  $secretcounter = 0;
  $eka_ajo = true;

  foreach ($kiissit as $i => $selite) {
    $nollaus = false;
    if ($secretcounter == 0) echo "<tr>";
    $tsekk = "";

    foreach ($valitut_sarakkeet as $osuma) {
      if ($osuma == $i) {
        $tsekk = "CHECKED";
        break;
      }
    }

    echo "<td align='left' valign='top' nowrap><input type='checkbox' class='sarakeboksi' name='valitut_sarakkeet[]' value='{$i}' $tsekk>{$selite}</td>";

    if ($secretcounter == 2) {
      echo "</tr>";
      $secretcounter = 0;
      $nollaus = true;
    }

    $eka_ajo = false;
    if (!$nollaus) $secretcounter++;
  }

  echo "<tr>
        <td align='left' class='back' valign='top'><br><br>
        <input type='hidden' name='lopetus' value='$lopetus'>
        <input type='hidden' name='otunnus' value='$tilausnumero'>
        <input type='hidden' name='tilausnumero' value='$tilausnumero'>
        <input type='hidden' name='mista' value = '$mista'>
        <input type='hidden' name='toim_nimitykset' value='$toim_nimitykset'>
        <input type='hidden' name='toim' value='$toim'>
        <input type='hidden' name='naantali' value='EIENAA'>
        <input type='submit' name='aja_ja_tallenna' value='".t("Valmis")."'>
        </td>
      </tr>";
  echo "</table>";
  echo "</form>";
}
elseif (isset($valitut_sarakkeet) and count($valitut_sarakkeet) > 0) {
  // täällä ajellaan rapsa ja tallennetaan henkseliin

  include 'inc/pupeExcel.inc';

  $worksheet    = new pupeExcel();
  $format_bold = array("bold" => TRUE);
  $excelrivi    = 0;
  $excelsarake = 0;

  // Haetaan sopimuksen laitteet
  $query = "SELECT DISTINCT laitteen_sopimukset.laitteen_tunnus laitetunnus
            FROM laitteen_sopimukset
            JOIN lasku ON lasku.yhtio = laitteen_sopimukset.yhtio
              AND lasku.tunnus
            WHERE lasku.tunnus = '{$tilausnumero}'
              AND lasku.yhtio = '{$kukarow['yhtio']}'";
  $laiteresult = pupe_query($query);
                                           
  // Haetaan sopimuskohtaiset tiedot
  $query = "SELECT lasku.tunnus, tilausrivi.nimitys, tilausrivi.hinta * tilausrivi.tilkpl AS rivihinta,
            lasku.asiakkaan_tilausnumero,
            lasku.alv,
            concat(lasku.toim_nimi,'\n',lasku.toim_osoite,'\n',lasku.toim_postino,' ',
              lasku.toim_postitp,'\n',lasku.toim_maa) toimitusosoite,
            concat(lasku.nimi,'\n',lasku.osoite,'\n',lasku.postino,' ',
              lasku.postitp,'\n',lasku.maa) laskutusosoite
            FROM lasku
            JOIN tilausrivi ON lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus
            WHERE lasku.tunnus = '{$tilausnumero}'
              AND lasku.yhtio = '{$kukarow['yhtio']}'";
  $sopimuskohtaisetresult = pupe_query($query);
  $sopimuskohtaisetrivi = mysql_fetch_assoc($sopimuskohtaisetresult);

  // Alkuun yhteenvetorivit
  $worksheet->write($excelrivi++, $excelsarake, t("Sopimusnumero").": ".$sopimuskohtaisetrivi['asiakkaan_tilausnumero'] , $format_bold);
  $worksheet->write($excelrivi, $excelsarake, t("Tuote"), $format_bold);
  $worksheet->write($excelrivi, $excelsarake+1, t("e / kk summa"), $format_bold); 
  $sopimus_alv = $sopimuskohtaisetrivi['alv'];
  $laskutusosoite = $sopimuskohtaisetrivi['laskutusosoite'];
  $toimitusosoite = $sopimuskohtaisetrivi['toimitusosoite'];
  $excelrivi++;

  mysql_data_seek($sopimuskohtaisetresult, 0);
  $totalvalue = 0;
  while ($sopimusrivi = mysql_fetch_assoc($sopimuskohtaisetresult)) {
    $worksheet->write($excelrivi, $excelsarake++, $sopimusrivi['nimitys']);
    $value = str_replace(".", ",", hintapyoristys($sopimusrivi['rivihinta']));
    $worksheet->write($excelrivi, $excelsarake++, $value);
    $totalvalue += $sopimusrivi['rivihinta'];
    $excelrivi++;
    $excelsarake = 0;
  }

  // Hardcoded stuff
  $worksheet->write($excelrivi++, $excelsarake, t("Erillisveloitettavat työt (Muutoksenhallinta, Häiriönselvitys ja muu erillislaskutettava työ)"), $format_bold);
  $worksheet->write($excelrivi, $excelsarake++, t("Työpäivä"));
  $worksheet->write($excelrivi++, $excelsarake, t("900 e / päivä"));
  $excelsarake = 0;
  $worksheet->write($excelrivi, $excelsarake++, t("Työtunti"));
  $worksheet->write($excelrivi++, $excelsarake++, t("125 e / tunti"));
  $excelsarake = 0;
  $worksheet->write($excelrivi, $excelsarake++, t("Työt arkena 16:00 - 22:00"));
  $worksheet->write($excelrivi++, $excelsarake++, t("+50 %"));
  $excelsarake = 0;
  $worksheet->write($excelrivi, $excelsarake++, t("Työt 22-08 arkena, lauantait ja pyhät"));
  $worksheet->write($excelrivi++, $excelsarake++, t("+100 %"));
  $excelsarake = 0;
  $worksheet->write($excelrivi, $excelsarake++, t("Matkatunnit"));
  $worksheet->write($excelrivi++, $excelsarake++, t("75 e / tunti"));
  $excelsarake = 0;                                                   

  $worksheet->write($excelrivi, $excelsarake++, t("Yhteensä").": (ALV {$sopimus_alv} %)", $format_bold);
  $totalvalue = str_replace(".", ",", hintapyoristys($totalvalue));
  $worksheet->write($excelrivi++, $excelsarake++, $totalvalue);
  $excelsarake = 0;

  $worksheet->write($excelrivi++, $excelsarake, t("Toimitusosoite").":", $format_bold);
  $worksheet->write($excelrivi++, $excelsarake, $toimitusosoite);
  $worksheet->write($excelrivi++, $excelsarake, t("Laskutusosoite").":", $format_bold);
  $worksheet->write($excelrivi++, $excelsarake, $laskutusosoite);

  $worksheet->write($excelrivi++, $excelsarake, t("Laitelista").":", $format_bold);

  $worksheet->write($excelrivi, $excelsarake++, t("Laitevalmistaja"), $format_bold);
  $worksheet->write($excelrivi, $excelsarake++, t("Malli"), $format_bold);
  $worksheet->write($excelrivi, $excelsarake++, t("Sarjanumero"), $format_bold);
  $worksheet->write($excelrivi, $excelsarake++, t("Service Desk SLA"), $format_bold);

  if (in_array("SP-FSNN-CO-NOC", $valitut_sarakkeet)) {
    $worksheet->write($excelrivi, $excelsarake++, t("NOC e/kk"), $format_bold);
  }
  if (in_array("SP-FSNN-CO-MDM", $valitut_sarakkeet)) {
    $worksheet->write($excelrivi, $excelsarake++, t("MDM e/kk"), $format_bold);
  }
  if (in_array("SP-FSNN-CO-MAINTENANCE", $valitut_sarakkeet)) {
    $worksheet->write($excelrivi, $excelsarake++, t("OnSite Preventive Maintenance"), $format_bold);
  }
  if (in_array("SP-FSNN-SE-LCC", $valitut_sarakkeet) or in_array("SP-FSNN-SE-LCM", $valitut_sarakkeet)) { 
    $worksheet->write($excelrivi, $excelsarake++, t("LCC/LCM e/kk"), $format_bold);
  }
  $worksheet->write($excelrivi, $excelsarake++, t("LCC/LCM SLA"), $format_bold);
  $worksheet->write($excelrivi, $excelsarake++, t("LCC Päättymispäivä"), $format_bold);

  $excelrivi++;
  $excelsarake = 0;

  // Kirjoitetaan laiterivit
  while ($row = mysql_fetch_assoc($laiteresult)) {

    // Haetaan laitekohtaiset tiedot
    $laitequery = "SELECT
                   laite.*,
                   avainsana.selitetark valmistaja,
                   tuote.tuotemerkki malli
                   FROM laite
                   LEFT JOIN tuote on tuote.yhtio = laite.yhtio
                     AND tuote.tuoteno    = laite.tuoteno
                   LEFT JOIN avainsana on avainsana.yhtio = tuote.yhtio
                     AND avainsana.laji   = 'TRY'
                     AND avainsana.selite = tuote.try
                   WHERE laite.yhtio      = '{$kukarow['yhtio']}'
                   AND laite.tunnus = '{$row['laitetunnus']}'";

    $laiteres = pupe_query($laitequery);

    $laiterow = mysql_fetch_assoc($laiteres);

    // Haetaan laitteen palvelut joiden hinnat ovat laitteiden lukumäärästä riippuvaisia
    $palveluquery = "SELECT nimitys, hinta
                     FROM tilausrivi
                     JOIN laitteen_sopimukset ON tilausrivi.yhtio = laitteen_sopimukset.yhtio
                       AND tilausrivi.tunnus = laitteen_sopimukset.sopimusrivin_tunnus
                     WHERE tilausrivi.tuoteno = 'SP-FSNN-CO-MDM'
                       AND laitteen_sopimukset.laitteen_tunnus = '{$row['laitetunnus']}'
                       AND NOT EXISTS (SELECT *
                         FROM tuotteen_avainsanat
                         WHERE yhtio    = '{$kukarow['yhtio']}'
                         AND tuoteno    = tilausrivi.tuoteno
                         AND laji       = 'laatuluokka'
                         AND selitetark = 'rivikohtainen')

                     UNION

                     SELECT nimitys, hinta
                     FROM tilausrivi
                     JOIN laitteen_sopimukset ON tilausrivi.yhtio = laitteen_sopimukset.yhtio
                       AND tilausrivi.tunnus = laitteen_sopimukset.sopimusrivin_tunnus
                     WHERE tilausrivi.tuoteno = 'SP-FSNN-SU-SD'
                       AND laitteen_sopimukset.laitteen_tunnus = '{$row['laitetunnus']}'
                       AND NOT EXISTS (SELECT *
                          FROM tuotteen_avainsanat
                          WHERE yhtio    = '{$kukarow['yhtio']}'
                          AND tuoteno    = tilausrivi.tuoteno
                          AND laji       = 'laatuluokka'
                          AND selitetark = 'rivikohtainen')

                     UNION

                     SELECT nimitys, hinta
                     FROM tilausrivi
                     JOIN laitteen_sopimukset ON tilausrivi.yhtio = laitteen_sopimukset.yhtio
                       AND tilausrivi.tunnus = laitteen_sopimukset.sopimusrivin_tunnus
                     WHERE tilausrivi.tuoteno = 'SP-FSNN-CO-NOC'
                       AND laitteen_sopimukset.laitteen_tunnus = '{$row['laitetunnus']}'
                       AND NOT EXISTS (SELECT *
                          FROM tuotteen_avainsanat
                          WHERE yhtio    = '{$kukarow['yhtio']}'
                          AND tuoteno    = tilausrivi.tuoteno
                          AND laji       = 'laatuluokka'
                          AND selitetark = 'rivikohtainen')

                     UNION

                     SELECT nimitys, hinta
                     FROM tilausrivi
                     JOIN laitteen_sopimukset ON tilausrivi.yhtio = laitteen_sopimukset.yhtio
                       AND tilausrivi.tunnus = laitteen_sopimukset.sopimusrivin_tunnus
                     WHERE tilausrivi.tuoteno = 'SP-FSNN-CO-MAINTENANCE'
                       AND laitteen_sopimukset.laitteen_tunnus = '{$row['laitetunnus']}'
                       AND NOT EXISTS (SELECT *
                          FROM tuotteen_avainsanat
                          WHERE yhtio    = '{$kukarow['yhtio']}'
                          AND tuoteno    = tilausrivi.tuoteno
                          AND laji       = 'laatuluokka'
                          AND selitetark = 'rivikohtainen')

                     UNION

                     SELECT nimitys, hinta
                     FROM tilausrivi
                     JOIN laitteen_sopimukset ON tilausrivi.yhtio = laitteen_sopimukset.yhtio
                       AND tilausrivi.tunnus = laitteen_sopimukset.sopimusrivin_tunnus
                     WHERE tilausrivi.tuoteno = 'SP-FSNN-SE-LCC'
                       AND laitteen_sopimukset.laitteen_tunnus = '{$row['laitetunnus']}'
                       AND NOT EXISTS (SELECT *
                          FROM tuotteen_avainsanat
                          WHERE yhtio    = '{$kukarow['yhtio']}'
                          AND tuoteno    = tilausrivi.tuoteno
                          AND laji       = 'laatuluokka'
                          AND selitetark = 'rivikohtainen')

                     UNION
                                          
                     SELECT nimitys, hinta
                     FROM tilausrivi
                     JOIN laitteen_sopimukset ON tilausrivi.yhtio = laitteen_sopimukset.yhtio
                       AND tilausrivi.tunnus = laitteen_sopimukset.sopimusrivin_tunnus
                     WHERE tilausrivi.tuoteno = 'SP-FSNN-SE-LCM'
                       AND laitteen_sopimukset.laitteen_tunnus = '{$row['laitetunnus']}'
                       AND NOT EXISTS (SELECT *
                          FROM tuotteen_avainsanat
                          WHERE yhtio    = '{$kukarow['yhtio']}'
                          AND tuoteno    = tilausrivi.tuoteno
                          AND laji       = 'laatuluokka'
                          AND selitetark = 'rivikohtainen')";

    $palveluresult = pupe_query($palveluquery);
    //$palvelurivit = mysql_fetch_assoc($palveluresult);

    $worksheet->write($excelrivi, $excelsarake++, $laiterow['valmistaja']);
    $worksheet->write($excelrivi, $excelsarake++, $laiterow['malli']);
    $worksheet->write($excelrivi, $excelsarake++, $laiterow['sarjanro']);
    $worksheet->write($excelrivi, $excelsarake++, $laiterow['sd_sla']);

    $mdmvalue = '';
    $sdvalue = '';
    $nocvalue = '';
    $maintvalue = '';
    $lccvalue = '';
    $lcmvalue = '';
    while ($rivi = mysql_fetch_assoc($palveluresult)) {
      if (empty($rivi['hinta'])) continue;
      if ($rivi['nimitys'] == "SignalONE Mobile Device Management") {
        $mdmvalue = str_replace(".", ",", hintapyoristys($rivi['hinta']));
      }
      elseif ($rivi['nimitys'] == "SignalONE NOC") {
        $nocvalue = str_replace(".", ",", hintapyoristys($rivi['hinta']));
      } 
      elseif ($rivi['nimitys'] == "SignalControl OnSite Preventive Maintenance") {
        $maintvalue = str_replace(".", ",", hintapyoristys($rivi['hinta']));
      }
      elseif ($rivi['nimitys'] == "LifecycleCare") {
        $lccvalue = str_replace(".", ",", hintapyoristys($rivi['hinta']));
      }
      elseif ($rivi['nimitys'] == "LifecycleManagement") {
        $lcmvalue = str_replace(".", ",", hintapyoristys($rivi['hinta']));
      }
    }

    // Palvelut
    if (in_array("SP-FSNN-CO-NOC", $valitut_sarakkeet)){
      $worksheet->write($excelrivi, $excelsarake++, $nocvalue);
    }
    if (in_array("SP-FSNN-CO-MDM", $valitut_sarakkeet)){
      $worksheet->write($excelrivi, $excelsarake++, $mdmvalue);
    }
    if (in_array("SP-FSNN-CO-MAINTENANCE", $valitut_sarakkeet)){ 
      $worksheet->write($excelrivi, $excelsarake++, $maintvalue);
    }
    if (in_array("SP-FSNN-SE-LCC", $valitut_sarakkeet) or in_array("SP-FSNN-SE-LCM", $valitut_sarakkeet) ){ 
      $arvo = (!empty($lccvalue) and !empty($lcmvalue)) ? $lccvalue."/".$lcmvalue : $lccvalue.$lcmvalue;
      $worksheet->write($excelrivi, $excelsarake++, $arvo);
    }
    $worksheet->write($excelrivi, $excelsarake++, $laiterow['sla']);
    $worksheet->write($excelrivi, $excelsarake++, $laiterow['valmistajan_sopimus_paattymispaiva']);

    $excelsarake = 0;
    $excelrivi++;
  }

  $excelnimi = $worksheet->close();

  if (isset($excelnimi)) {
    echo "<br><br><table>";
    echo "<tr><th>", t("Tallenna tulos"), ":</th>";
    echo "<form method='post' class='multisubmit'>";
    echo "<input type='hidden' name='tappi' value='lataa_tiedosto'>";
    echo "<input type='hidden' name='kaunisnimi' value='Laiteluettelo.xlsx'>";
    echo "<input type='hidden' name='tmpfilenimi' value='{$excelnimi}'>";
    echo "<td class='back'><input type='submit' value='", t("Tallenna"), "'></td></tr></form>";
    echo "</table><br>";
  }
  else {
    echo t("Tallennus epäonnistui")."!<br>";
  }
}
