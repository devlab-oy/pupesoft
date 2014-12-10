<?php

if (isset($_COOKIE['laiteluettelo_keksi'])) $valitut_sarakkeet = unserialize(urldecode($_COOKIE['laiteluettelo_keksi']));

if (isset($_POST['piirtele_laiteluettelo'])) {

  // Piirrell��n laiteluettelo-valikko
  echo "<br><br>";

  // M��ritell��n jostain halutut kent�t
  $kiissit = array(
    // Sopimuskohtaiset -pit�isik� erotella valikossa jotenkin?
    "asiakastiedot",
    "sopimus_lisatietoja",
    "sopimus_lisatietoja2",
    // Laite- ja palvelu(tilausrivi)kohtaiset
    "laitteen_tunnus",
    "sarjanro",
    "tuoteno",
    "tuotemerkki",
    "valmistaja",
    "sla",
    "nimitys",
    "palvelu_alkaa",
    "palvelu_loppuu",
    "hinta",
    "palvelukohtainen_hinta",
    "valmistajan_sopimusnumero",
    "valmistajan_sopimus_paattymispaiva",
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
      if ($osuma == $selite) {
        $tsekk = "CHECKED";
        break;
      }
    }

    echo "<td align='left' valign='top' nowrap><input type='checkbox' class='sarakeboksi' name='valitut_sarakkeet[]' value='{$selite}' $tsekk>{$selite}</td>";

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
  // t��ll� ajellaan rapsa ja tallennetaan henkseliin

  include 'inc/pupeExcel.inc';

  $worksheet    = new pupeExcel();
  $format_bold = array("bold" => TRUE);
  $excelrivi    = 0;
  $excelsarake = 0;

  $sopimuskohtaiset = array(
    "asiakastiedot",
    "sopimus_lisatietoja",
    "sopimus_lisatietoja2"
  );
  // Haetaan sopimuskent�t, tilausrivien(palveluiden) ja valittavat laitetiedot
  $query = "SELECT
            lasku.tunnus,
            concat(lasku.toim_nimi,'\n',
            lasku.toim_osoite,'\n',
            lasku.toim_postitp) asiakastiedot,
            laitteen_sopimukset.laitteen_tunnus,
            laite.sla,
            laite.sarjanro,
            laite.tuoteno,
            laite.valmistajan_sopimusnumero,
            laite.valmistajan_sopimus_paattymispaiva,
            tuote.tuotemerkki,
            avainsana.selitetark valmistaja,
            tilausrivi.nimitys,
            tilausrivi.hinta,
            tilausrivi.netto,
            laskun_lisatiedot.sopimus_lisatietoja,
            laskun_lisatiedot.sopimus_lisatietoja2,
            tilausrivin_lisatiedot.sopimus_alkaa palvelu_alkaa,
            tilausrivin_lisatiedot.sopimus_loppuu palvelu_loppuu,
            laskun_lisatiedot.sopimus_alkupvm,
            laskun_lisatiedot.sopimus_loppupvm
            FROM laitteen_sopimukset
            JOIN laite ON laite.tunnus = laitteen_sopimukset.laitteen_tunnus
            JOIN tilausrivi ON tilausrivi.tunnus = laitteen_sopimukset.sopimusrivin_tunnus
              AND tilausrivi.yhtio                        = '{$kukarow['yhtio']}'
            JOIN tuote ON tuote.yhtio = tilausrivi.yhtio
              AND tuote.tuoteno                           = laite.tuoteno
            JOIN avainsana ON avainsana.yhtio = tuote.yhtio
              AND avainsana.laji                          = 'TRY'
              AND avainsana.selite                        = tuote.try
            JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
              AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
            JOIN lasku ON lasku.yhtio = tilausrivi.yhtio
              AND lasku.tunnus                            = tilausrivi.otunnus
            JOIN laskun_lisatiedot ON laskun_lisatiedot.yhtio = lasku.yhtio
              AND laskun_lisatiedot.otunnus               = lasku.tunnus
            WHERE lasku.tunnus                            = '{$tilausnumero}'
            ORDER BY laitteen_tunnus, nimitys";
  $result = pupe_query($query);

  $eka_ajo = true;
  $eka_laiterivi = true;
  $kokonaishinta = 0;
  $lisainffot = '';
  // Rustaillaan henkseliin kaikki valitut sarakkeet
  while ($row = mysql_fetch_assoc($result)) {

    // Sopimuskohtaiset kent�t
    if ($eka_ajo) {
      // Defaultkent�t:
      $worksheet->write($excelrivi, $excelsarake, t("Sopimusnumero"),       $format_bold);
      $worksheet->write($excelrivi+1, $excelsarake++, $row['tunnus']);
      $worksheet->write($excelrivi, $excelsarake, t("Sopimus alkaa"),       $format_bold);
      $worksheet->write($excelrivi+1, $excelsarake++, $row['sopimus_alkupvm']);
      $worksheet->write($excelrivi, $excelsarake, t("Sopimus loppuu"),     $format_bold);
      $worksheet->write($excelrivi+1, $excelsarake++, $row['sopimus_loppupvm']);

      // Valinnaiset sopimuskohtaiset kent�t:
      // Sopimuslisatietoja 1/2
      // Asiakastiedot
      foreach ($row as $key => $value) {
        if (in_array($key, $sopimuskohtaiset) and in_array($key, $valitut_sarakkeet)) {
          $worksheet->write($excelrivi, $excelsarake, t("{$key}"),     $format_bold);
          $worksheet->write($excelrivi+1, $excelsarake++, $value);
        }
      }
      $excelrivi+=3;
      $excelsarake = 0;
      $eka_ajo = false;
    }

    // Laite-/palvelukohtaiset valinnaiset kent�t

    // Laitetunnus
    // Sarjanumero
    // Tuotenumero
    // Tuotemerkki
    // Valmistaja
    // SLA
    // VC numero / End date

    // Nimitys(palvelu)
    // Palvelu alku/loppu
    // Kplhinta

    foreach ($row as $key => $value) {
      if (in_array($key, $valitut_sarakkeet) and !in_array($key, $sopimuskohtaiset)) {

        if (is_numeric($value) and $key == "hinta") {
          $value = str_replace(".", ",", hintapyoristys($value))." ".t("e / kk");
          // Jos laitteiden kappalem��r� ei vaikuta palvelun hintaan
          if ($row['netto'] != '') {
            $value .= " **";
            $lisainffot = "".t("** Palvelukohtainen hinta");
          }
        }

        if ($eka_laiterivi) {
          // Valittujen laiterivien headerit
          $worksheet->write($excelrivi, $excelsarake, t("{$key}"),     $format_bold);
          $worksheet->write($excelrivi+1, $excelsarake++, $value);
        }
        else {
          $worksheet->write($excelrivi, $excelsarake++, $value);
        }

      }
    }
    $excelsarake = 0;
    $excelrivi++;
    $eka_laiterivi = false;
  }
  $worksheet->write($excelrivi, $excelsarake, $lisainffot);
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
    echo t("Tallennus ep�onnistui")."!<br>";
  }
}
