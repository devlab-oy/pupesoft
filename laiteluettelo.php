<?php

if (isset($_COOKIE['laiteluettelo_keksi'])) $valitut_sarakkeet = unserialize(urldecode($_COOKIE['laiteluettelo_keksi']));

if (isset($_POST['piirtele_laiteluettelo'])) {

  // Piirrellään laiteluettelo-valikko
  echo "<br><br>";

  $ruksattavat_kentat = array();

  // Haetaan avainsanoista laiteluettelon laitekohtaiset palveluhinnat
  // sekä sopimuskohtaiset lisätyöt hardcoded selitteellä "NAYTATYOT"
  $res = t_avainsana("SOP_LAITLUET");
  $lisatuotenumerot = '';

  while ($avainsanarivi = mysql_fetch_assoc($res)) {
    $ruksattavat_kentat[$avainsanarivi['selite']] = $avainsanarivi['selitetark'];
    // Tallennetaan myös selectilisa
    $lisatuotenumerot .= "'".$avainsanarivi['selite']."',";
  }

  $lisatuotenumerot = rtrim($lisatuotenumerot, ',');
  $lisatuotenumerot = base64_encode($lisatuotenumerot);

  echo "<form name='aja_ja_tallenna' method='post'>";
  echo "<table width='600'>";
  echo "<tr><th>".t("Valitse laitekohtaiset sarakkeet")."</th></tr>";

  $secretcounter = 0;
  $eka_ajo = true;

  foreach ($ruksattavat_kentat as $key => $selite) {

    $nollaus = false;
    if ($secretcounter == 0) echo "<tr>";
    $tsekk = "";

    foreach ($valitut_sarakkeet as $osuma) {
      if ($osuma == $key) {
        $tsekk = "CHECKED";
        break;
      }
    }

    echo "<td align='left' valign='top' nowrap><input type='checkbox' class='sarakeboksi' name='valitut_sarakkeet[]' value='{$key}' $tsekk>{$selite}";
    if ($key == "NAYTATYOT") {
      echo "<br><textarea rows='5' cols='40' maxlength='1000' name='hintahopinat' placeholder='".t("Vapaa teksti")."'>";
      echo "</textarea>";
    }
    echo "</td>";

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
        <input type='hidden' name='lisatuotenumerot' value='$lisatuotenumerot'>
        <input type='submit' name='aja_ja_tallenna' value='".t("Valmis")."'>
        </td>
      </tr>";
  echo "</table>";
  echo "</form>";
}
elseif (isset($valitut_sarakkeet) and count($valitut_sarakkeet) > 0) {
  // täällä ajellaan rapsa ja tallennetaan henkseliin
  $lisatuotenumerot = base64_decode($lisatuotenumerot);
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
              AND lasku.yhtio  = '{$kukarow['yhtio']}'";
  $laiteresult = pupe_query($query);

  $query_ale_lisa = generoi_alekentta('M');

  // Haetaan sopimuskohtaiset tiedot
  $query = "SELECT
            lasku.tunnus,
            tilausrivi.nimitys,
            round(tilausrivi.hinta * (tilausrivi.varattu) * {$query_ale_lisa}, $yhtiorow[hintapyoristys]) rivihinta,
            lasku.asiakkaan_tilausnumero,
            lasku.alv,
            concat(lasku.toim_nimi,'\n',lasku.toim_osoite,'\n',lasku.toim_postino,' ',
              lasku.toim_postitp,'\n',lasku.toim_maa) toimitusosoite,
            concat(lasku.nimi,'\n',lasku.osoite,'\n',lasku.postino,' ',
              lasku.postitp,'\n',lasku.maa) laskutusosoite
            FROM lasku
            JOIN tilausrivi ON lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus
            WHERE lasku.tunnus = '{$tilausnumero}'
              AND lasku.yhtio  = '{$kukarow['yhtio']}'";
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

  if (in_array("NAYTATYOT", $valitut_sarakkeet) and !empty($hintahopinat)) {
    $lisatyosarakeq = "SELECT selitetark
                       FROM avainsana
                       WHERE yhtio = '{$kukarow['yhtio']}'
                       AND laji    = 'SOP_LAITLUET'
                       AND selite  = 'NAYTATYOT'";
    $lisatyosarakeres = pupe_query($lisatyosarakeq);
    $lisatyosarakerivi = mysql_fetch_assoc($lisatyosarakeres);
    $worksheet->write($excelrivi++, $excelsarake, $lisatyosarakerivi['selitetark'], $format_bold);
    $worksheet->write($excelrivi++, $excelsarake, $hintahopinat);
    $excelsarake = 0;
  }

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

  // Poistetaan 'NAYTATYOT' valituista sarakkeista että saadaan oikeat sarakkeet laitekohtaisille
  // riveille resetoimalla arrayn indeksit
  $valikey = array_search('NAYTATYOT', $valitut_sarakkeet);
  unset($valitut_sarakkeet[$valikey]);
  $valitut_sarakkeet = array_values($valitut_sarakkeet);

  // Piirretään sarakeotsikot valituille sarakkeille
  foreach ($valitut_sarakkeet as $key => $value) {
    $sarakeotsikkoquery = "SELECT selitetark
                           FROM avainsana
                           WHERE yhtio = '{$kukarow['yhtio']}'
                           AND laji    = 'SOP_LAITLUET'
                           AND selite  = '{$value}'";
    $sarakeotsikkoresult = pupe_query($sarakeotsikkoquery);
    $sarakeotsikkorivi = mysql_fetch_assoc($sarakeotsikkoresult);
    $worksheet->write($excelrivi, $excelsarake++, $sarakeotsikkorivi['selitetark'], $format_bold);
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
                   AND laite.tunnus       = '{$row['laitetunnus']}'";

    $laiteres = pupe_query($laitequery);

    $laiterow = mysql_fetch_assoc($laiteres);

    $query_ale_lisa = generoi_alekentta('M');

    // Haetaan laitteen palvelut joiden hinnat ovat laitteiden lukumäärästä riippuvaisia
    // ja ne on valittu käyttöliittymästä
    $palveluquery = "SELECT
                     tuoteno,
                     nimitys,
                     round(tilausrivi.hinta * {$query_ale_lisa}, $yhtiorow[hintapyoristys]) hinta
                     FROM tilausrivi
                     JOIN laitteen_sopimukset ON tilausrivi.yhtio = laitteen_sopimukset.yhtio
                       AND tilausrivi.tunnus                   = laitteen_sopimukset.sopimusrivin_tunnus
                     WHERE tilausrivi.tuoteno                  IN ({$lisatuotenumerot})
                       AND laitteen_sopimukset.laitteen_tunnus = '{$row['laitetunnus']}'
                       AND NOT EXISTS (SELECT *
                         FROM tuotteen_avainsanat
                         WHERE yhtio                           = '{$kukarow['yhtio']}'
                           AND tuoteno                         = tilausrivi.tuoteno
                           AND laji                            = 'laatuluokka'
                           AND selitetark                      = 'rivikohtainen');";
    $palveluresult = pupe_query($palveluquery);

    $worksheet->write($excelrivi, $excelsarake++, $laiterow['valmistaja']);
    $worksheet->write($excelrivi, $excelsarake++, $laiterow['malli']);
    $worksheet->write($excelrivi, $excelsarake++, $laiterow['sarjanro']);
    $worksheet->write($excelrivi, $excelsarake++, $laiterow['sd_sla']);

    $hinnat_jarjestyksessa = array();
    // Sortataan laiterivikohtaisten palveluiden hinnat
    while ($rivi = mysql_fetch_assoc($palveluresult)) {
      $oikea_sarake = array_search($rivi['tuoteno'], $valitut_sarakkeet);
      if ($oikea_sarake === false) continue;
      $hinnat_jarjestyksessa[$oikea_sarake] = hintapyoristys($rivi['hinta']);
    }

    $tracker = 0;
    while ($tracker < count($valitut_sarakkeet)) {
      if (array_key_exists($tracker, $hinnat_jarjestyksessa)) {
        $worksheet->write($excelrivi, $excelsarake++, $hinnat_jarjestyksessa[$tracker]);
      }
      else {
        $worksheet->write($excelrivi, $excelsarake++, '');
      }
      $tracker++;
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
