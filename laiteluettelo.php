<?php

if (isset($_COOKIE['laiteluettelo_keksi'])) $valitut_sarakkeet = unserialize(urldecode($_COOKIE['laiteluettelo_keksi']));

if (isset($_POST['piirtele_laiteluettelo'])) {
  echo "<SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">

    $(function() {
      console.log('funkka');
      $('.check_all').on('click', function() {
        console.log('klikki');
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

  // Piirrellään laiteluettelo-valikko
  echo "<br><br>";

  // Määritellään jostain halutut kentät
  $kiissit = array(
    // Sopimuskohtaiset -pitäisikö erotella valikossa jotenkin?
    "asiakastiedot",
    "sopimus_lisatietoja",
    "sopimus_lisatietoja2",
    // Laite- ja palvelu(tilausrivi)kohtaiset
    "laitteen_tunnus",
    "sarjanro",
    "tuotemerkki",
    "valmistaja",
    "sla",
    "nimitys",
    "palvelu_alkaa",
    "palvelu_loppuu",
    "hinta",
    "palvelukohtainen_hinta",
    "valmistajan_sopimusnumero",
    "valmistajan_sopimus_paattyy",
  );

  echo "<form name='aja_ja_tallenna' method='post'>";
  echo "<table border='0' cellpadding='5' cellspacing='0' width='600'>";
  echo "<tr><th>".t("Valitse sarakkeet")."<br><br><input type='checkbox' class='check_all' value='sarakeboksi'>".t("Valitse Kaikki")."</th></tr>";

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
  // täällä ajellaan rapsa ja tallennetaan henkseliin
  
  include ('inc/pupeExcel.inc');

  $worksheet    = new pupeExcel();
  $format_bold = array("bold" => TRUE);
  $excelrivi    = 0;
  $excelsarake = 0;

  $sopimuskohtaiset = array(
    "asiakastiedot",
    "sopimus_lisatietoja",
    "sopimus_lisatietoja2"
  );
  // Haetaan sopimuskentät, tilausrivien(palveluiden) ja valittavat laitetiedot
  $query = "SELECT
            lasku.tunnus,
            concat(lasku.toim_nimi,'\n',
            lasku.toim_osoite,'\n',
            lasku.toim_postitp) asiakastiedot,
            laitteen_sopimukset.laitteen_tunnus,
            laite.sarjanro,
            laite.tuoteno,
            tuote.tuotemerkki,
            avainsana.selitetark valmistaja,
            laite.sla,
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
              AND tilausrivi.yhtio = '{$kukarow['yhtio']}'
            JOIN tuote ON tuote.yhtio = tilausrivi.yhtio 
              AND tuote.tuoteno = laite.tuoteno
            JOIN avainsana ON avainsana.yhtio = tuote.yhtio 
              AND avainsana.laji = 'TRY' 
              AND avainsana.selite = tuote.try
            JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio 
              AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
            JOIN lasku ON lasku.yhtio = tilausrivi.yhtio 
              AND lasku.tunnus = tilausrivi.otunnus
            JOIN laskun_lisatiedot ON laskun_lisatiedot.yhtio = lasku.yhtio 
              AND laskun_lisatiedot.otunnus = lasku.tunnus
            WHERE lasku.tunnus = '{$tilausnumero}' 
            ORDER BY laitteen_tunnus, nimitys";
  $result = pupe_query($query);

  $eka_ajo = true;
  $eka_laiterivi = true;
  $kokonaishinta = 0;
  $lisainffot = '';
  // Rustaillaan henkseliin kaikki valitut sarakkeet (osan vois laittaa pakollisiks tietenkin)
  while ($row = mysql_fetch_assoc($result)) {
    
    // Sopimuskohtaiset kentät
    if ($eka_ajo) {
      // Defaultkentät:
      // Sopimusnumero
      // Sopimus alkaa/loppuu
      $worksheet->write($excelrivi, $excelsarake, t("Sopimusnumero"),       $format_bold);
      $worksheet->write($excelrivi+1, $excelsarake++, $row['tunnus']);
      $worksheet->write($excelrivi, $excelsarake, t("Sopimus alkaa"),       $format_bold);
      $worksheet->write($excelrivi+1, $excelsarake++, $row['sopimus_alkupvm']);
      $worksheet->write($excelrivi, $excelsarake, t("Sopimus loppuu"),     $format_bold);
      $worksheet->write($excelrivi+1, $excelsarake++, $row['sopimus_loppupvm']);
      
      // Valinnaiset sopimuskohtaiset kentät:
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

    // Laite-/palvelukohtaiset valinnaiset kentät
    
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
    echo t("Tallennus epäonnistui")."!<br>";
  }
}
