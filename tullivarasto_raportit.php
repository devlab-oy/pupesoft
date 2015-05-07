<?php

if (isset($_POST['task']) and (strpos($_POST['task'], "_pdf") !== false)) {
  $no_head = "yes";
}

require "inc/parametrit.inc";
require 'inc/edifact_functions.inc';

if (isset($task) and ($task == 'purkuraportti_pdf' or $task == 'lastausraportti_pdf')) {

  if ($tyyppi == 'purku') {

    $query = "SELECT tunnus
              FROM lasku
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND asiakkaan_tilausnumero = '{$tunniste}'
              AND viesti = 'tullivarasto'";
  }
  else {

    $query = "SELECT tunnus
              FROM lasku
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus = '{$tunniste}'
              AND viesti = 'tullivarastotoimitus'";
  }

  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=tullivarasto_raportit.php?raporttierror=1&tulonumero={$tulonumero}'>";
    die;
  }
  else{

    if ($tyyppi == 'purku') {
      $tunniste = mysql_result($result, 0);
    }

    $pdf_data = purkuraportti_parametrit($tunniste);

    $logo_info = pdf_logo();
    $pdf_data['logodata'] = $logo_info['logodata'];
    $pdf_data['scale'] = $logo_info['scale'];

    $pdf_tiedosto = purkuraportti_pdf($pdf_data);

    header("Content-type: application/pdf");
    header("Content-Disposition:attachment;filename='{$tyyppi}raportti_{$tunniste}.pdf'");
    echo file_get_contents($pdf_tiedosto);
    die;
  }
}

if (isset($task) and $task == 'tuloraportti_pdf') {

  $pdf_data = tulonumeron_historia($tulonumero);
  $logo_info = pdf_logo();
  $pdf_data['varastotilanne'] = $varastotilanne;
  $pdf_data['logodata'] = $logo_info['logodata'];
  $pdf_data['scale'] = $logo_info['scale'];

  $pdf_tiedosto = tuloraportti_pdf($pdf_data);

  header("Content-type: application/pdf");
  header("Content-Disposition:attachment;filename='tuloraportti_{$tulonumero}.pdf'");
  echo file_get_contents($pdf_tiedosto);
  die;
}

if (isset($task) and $task == 'tulliraportti_pdf') {

  $varastotilanne = unserialize(base64_decode($varastotilanne));
  $logo_info = pdf_logo();
  $pdf_data['varastotilanne'] = $varastotilanne;
  $pdf_data['logodata'] = $logo_info['logodata'];
  $pdf_data['scale'] = $logo_info['scale'];

  $pdf_tiedosto = tulliraportti_pdf($pdf_data);

  header("Content-type: application/pdf");
  header("Content-Disposition:attachment;filename='tulliraportti.pdf'");
  echo file_get_contents($pdf_tiedosto);
  die;
}

if (isset($raporttierror)) {
  $tulonumeroerror = t("Tarkista tulonumero!");
  $task = "valitse_raporttityyppi";
  $raporttityyppi = "purku";
}
else {
  $tulonumeroerror = '';
}

if (isset($task) and $task == 'tuloraportti_tiedot') {

  $tulonumeron_historia = tulonumeron_historia($tulonumero);

  $otsikko = t("Tuloraportti") . ' - ' . $tulonumero;
  $view = 'tuloraportti_tiedot';
}

if (isset($task) and $task == 'nayta_tiedot') {

  $vuosi_kuu = date("Y-m", $kuukausi);
  $raporttikuun_alku = $vuosi_kuu."-01";

  $parametrit = array(
    'asiakastunnus' => $toimittajatunnus,
    'raporttikuun_alku' => $raporttikuun_alku
  );

  $tiedot = tullivarasto_laskutustiedot($parametrit);

  extract($tiedot);

  $otsikko = t("Laskutusraportti");
  $view = 'laskutus_data';
}

if (isset($task) and $task == 'valitse_asiakas') {

  $kuukaudet = laskutusraportti_kuukaudet($toimittajatunnus);

  $query = "SELECT nimi
            FROM toimi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$toimittajatunnus}'";
  $result = pupe_query($query);
  $asiakas_nimi = mysql_result($result, 0);

  $otsikko = t("Laskutusraportti");
  $view = 'valitse_kuukausi';
}

if (isset($task) and $task == 'valitse_raporttityyppi') {

  switch ($raporttityyppi) {

    case 'laskutus':
      $viesti = t("Valitse asiakas");
      $asiakkaat = toimittajat();
      break;

    case 'tulo':
      $viesti = t("Syötä tulonumero");
      break;

    case 'tulli':
      $varastotilanne = tulliraportti_tiedot();
      $pdf_varastotilanne = serialize($varastotilanne);
      $pdf_varastotilanne = base64_encode($pdf_varastotilanne);
      break;

    case 'purku':
      $viesti = t("Syötä tulonumero");
      break;

    case 'lastaus':
      $viesti = t("Syötä toimitusnumero");
      break;

    default:
      # code...
      break;
  }

  $view = $raporttityyppi;
  $otsikko = t("Tullivarastoraportit");
}

if (!isset($task)) {
  $otsikko = t("Tullivarastoraportit");
  $view = 'perus';
  $viesti = t("Valitse raporttityyppi");
}

echo "<font class='head'>{$otsikko}</font><hr><br>";

if (!empty($viesti)) {
  echo "<font class='message'>{$viesti}</font><br><br>";
}

if (isset($view) and $view == "valitse_kuukausi") {

  echo "<table>";

  echo "<tr>";
  echo "<th>";
  echo t("Raporttityyppi");
  echo "</th>";
  echo "<td>";
  echo t("Laskutusraportti");
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>";
  echo t("Asiakas");
  echo "</th>";
  echo "<td>";
  echo $asiakas_nimi;
  echo "</td>";
  echo "</tr>";

  echo "
  <form action='tullivarasto_raportit.php' method='post'>
  <input type='hidden' name='task' value='nayta_tiedot' />
  <input type='hidden' name='toimittajatunnus' value='{$toimittajatunnus}' />
  <tr>
    <th>" . t("Kuukausi") . "</th>
    <td>
      <select name='kuukausi' onchange='submit();'>
        <option selected disabled>" . t("Valitse kuukausi") ."</option>";

        foreach ($kuukaudet as $kuu => $aikaleima) {
          echo "<option value='{$aikaleima}'>{$kuu}</option>";
        }

    echo "</select></td><td class='back'></td>
  </tr>
  </table>
  </form>";

}

if (isset($view) and $view == "laskutus") {

  echo "<table><tr><th>";
  echo t("Raporttityyppi");
  echo "</th><td>".t("Laskutusraportti")."</td></tr>
  <form action='tullivarasto_raportit.php' method='post'>
  <input type='hidden' name='task' value='valitse_asiakas' />
  <tr>
    <th>" . t("Asiakas") . "</th>
    <td>
      <select name='toimittajatunnus' onchange='submit();'>
        <option selected disabled>" . t("Valitse asiakas") ."</option>";

        foreach ($asiakkaat as $tunnus => $nimi) {
          echo "<option value='{$tunnus}'>{$nimi}</option>";
        }

    echo "</select></td><td class='back'></td>
  </tr>
  </table>
  </form>";
}

if (isset($view) and $view == "tulo") {

  if (!empty($tulonumeroerror)) {
    echo "<font class='error'>{$tulonumeroerror}</font><br><br>";
  }

  echo "<form method='post' action='tullivarasto_raportit.php' >";
  echo "<input type='hidden' name='task' value='tuloraportti_tiedot' />";
  echo "<table>";
  echo "<tr>";
  echo "<th>";
  echo t("Tulonumero") . ": ";
  echo "</th>";
  echo "<td>";
  echo "<input type='text' name='tulonumero' value='{$tulonumero}' />";
  echo "</td>";
  echo "<td class='back'>";
  echo "<input type='submit' value='". t("Näytä tiedot")."' />";
  echo "</td>";
  echo "</tr>";
  echo "</table>";
  echo "</form>";




}

if (isset($view) and $view == "tulli") {

  if (count($varastotilanne) > 0) {

    echo "
    <form method='post' class='multisubmit' action='tullivarasto_raportit'>
    <input type='hidden' name='varastotilanne' value='{$pdf_varastotilanne}' />
    <input type='hidden' name='task' value='tulliraportti_pdf' />
    <input type='submit' value='" . t("Lataa PDF") . "' />
    </form><br><br>";

    echo "<table>";

    foreach ($varastotilanne as $tulonumero => $tiedot) {

      if (empty($tiedot['tulotiedot']['konttinumero'])) {
        $kuljetusotsikko = t("Rekisterinumero");
        $kuljetustieto = $tiedot['tulotiedot']['rekisterinumero'];
      }
      else {
        $kuljetusotsikko = t("Konttinumero");
        $kuljetustieto = $tiedot['tulotiedot']['konttinumero'];
      }

      echo "<tr>";
      echo "<th>" . t("Tulonumero") . "</th>";
      echo "<th>{$kuljetusotsikko}</th>";
      echo "<th>" . t("Sinettinumero") . "</th>";
      echo "<th>" . t("Edeltävä asiakirja") . "</th>";
      echo "</tr>";

      echo "<tr>";
      echo "<td>{$tulonumero}</td>";
      echo "<td>{$kuljetustieto}</td>";
      echo "<td>{$tiedot['tulotiedot']['sinettinumero']}</td>";
      echo "<td>{$tiedot['tulotiedot']['edeltava_asiakirja']}</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<th>" . t("Nimitys") . "</th>";
      echo "<th>" . t("Malli") . "</th>";
      echo "<th>" . t("Paino (kg.)") . "</th>";
      echo "<th>" . t("Varastosaldo") . "</th>";
      echo "</tr>";

      foreach ($tiedot['tuotetiedot'] as $key => $value) {
        echo "<tr>";
        echo "<td>{$value['nimitys']}</td>";
        echo "<td>{$value['malli']}</td>";
        echo "<td>{$value['paino']}</td>";
        echo "<td>{$value['kpl']}</td>";
        echo "</tr>";
      }

      echo "<tr>";
      echo "<th class='back' colspan='4'></th>";
      echo "</tr>";

    }

    echo "</table>";

  }

}

if (isset($view) and ($view == "purku" or $view == "lastaus")) {

  if (!empty($tulonumeroerror)) {
    echo "<font class='error'>{$tulonumeroerror}</font><br><br>";
  }

  if ($view == 'purku') {
    $tunnusteksti = t("Tulonumero");
  }
  else {
    $tunnusteksti = t("Toimitusumero");
  }


  echo "<form method='post' class='multisubmit' action='tullivarasto_raportit.php' >";
  echo "<input type='hidden' name='task' value='{$view}raportti_pdf' />";
  echo "<input type='hidden' name='tyyppi' value='{$view}' />";
  echo "<table>";
  echo "<tr>";
  echo "<th>";
  echo $tunnusteksti . ": ";
  echo "</th>";
  echo "<td>";
  echo "<input type='text' name='tunniste' value='{$tunniste}' />";
  echo "</td>";
  echo "<td class='back'>";
  echo "<input type='submit' value='". t("Lataa raportti")."' />";
  echo "</td>";
  echo "</tr>";
  echo "</table>";
  echo "</form>";

}

if (isset($view) and $view == "laskutus_data") {

  echo "<table>";
  echo "<tr>";
  echo "<th>";
  echo t("Raporttityyppi");
  echo "</th>";
  echo "<td>";
  echo t("Laskutusraportti");
  echo "</td>";
  echo "</tr>";
  echo "<tr>";
  echo "<th>";
  echo t("Asiakas");
  echo "</th>";
  echo "<td>";
  echo $toimittajatunnus;
  echo "</td>";
  echo "</tr>";
  echo "<tr>";
  echo "<th>";
  echo t("Kuukausi");
  echo "</th>";
  echo "<td>";
  echo date("m.Y",$kuukausi);
  echo "</td>";
  echo "</tr>";
  echo "</table><br>";
  echo "<font class='message'>" . t("Varastointikaudet") . "</font><br><br>";
  echo "<table>";
  echo "<tr>";
  echo "<th>". t("Tuote") ."</th>";
  echo "<th>". t("Tulonumero") ."</th>";
  echo "<th>". t("kpl") ."</th>";
  echo "<th>". t("Paino") ."</th>";
  echo "<th>". t("Tilavuus") ."</th>";
  echo "<th>". t("Laskutuksen alku") ."</th>";
  echo "<th>". t("Laskutuksen loppu") ."</th>";
  echo "<th>". t("Päivät") ."</th>";
  echo "<th>". t("Hinta") . " €</th>";
  echo "</tr>";

  foreach ($tuotekaudet as $tuote => $kaudet) {
    foreach ($kaudet as $kausi) {

      echo "<tr>";
      echo "<td>{$kausi['nimitys_malli']}</td>";
      echo "<td>{$kausi['tulonumero']}</td>";
      echo "<td>{$kausi['kpl']}</td>";
      echo "<td>{$kausi['tonnit']}</td>";
      echo "<td>{$kausi['tilavuus']}</td>";
      echo "<td>{$kausi['sisaan']}</td>";
      echo "<td>{$kausi['ulos']}</td>";
      echo "<td>{$kausi['paivat']}</td>";
      echo "<td>{$kausi['hinta']}</td>";
      echo "</tr>";
    }
  }
  echo "</table>";

  if (count($nimikkeet) > 0) {

    echo "<br><font class='message'>" . t("Työnimikkeet") . "</font><br><br>";
    echo "<table>";
    echo "<tr>";
    echo "<th>". t("Nimike") ."</th>";
    echo "<th>". t("kpl") ."</th>";
    echo "<th>". t("Toimitusnumero") ."</th>";
    echo "<th>". t("Hinta") . " €</th>";
    echo "</tr>";

    foreach ($nimikkeet as $nimike => $tiedot) {

      echo "<tr>";
      echo "<td>{$nimike}</td>";
      echo "<td>{$tiedot['kpl']}</td>";
      echo "<td>{$tiedot['toimitusnumero']}</td>";
      echo "<td>{$tiedot['hinta']}</td>";
      echo "</tr>";

    }
    echo "</table>";
  }
}

if (isset($view) and $view == "tuloraportti_tiedot") {

  echo "
  <form method='post' class='multisubmit' action='tullivarasto_raportit'>
  <input type='hidden' name='tulonumero' value='{$tulonumero}' />
  <input type='hidden' name='task' value='tuloraportti_pdf' />
  <input type='submit' value='" . t("Lataa PDF") . "' />
  </form><br><br>";

  echo "<font class='message'>" . t("Tulonumeron") .' '. $tulonumero .' '. t("tiedot") . "</font><br><br>";

  if (empty($tulonumeron_historia['tulonumeron_perustiedot']['konttinumero'])) {
    $kuljetusotsikko = t("Rekisterinumero");
    $kuljetustieto = $tulonumeron_historia['tulonumeron_perustiedot']['kuljetuksen_rekno'];
  }
  else {
    $kuljetusotsikko = t("Konttinumero");
    $kuljetustieto = $tulonumeron_historia['tulonumeron_perustiedot']['konttinumero'];
  }

  echo "<table>";
  echo "<tr>";
  echo "<th>" . t("Toimittaja") . "</th>";
  echo "<td>{$tulonumeron_historia['tulonumeron_perustiedot']['toimittaja']}</td>";
  echo "</tr>";
  echo "<tr>";
  echo "<th>" . t("Edeltävä asiakirja") . "</th>";
  echo "<td>{$tulonumeron_historia['tulonumeron_perustiedot']['kontin_mrn']}</td>";
  echo "</tr>";
  echo "<th>{$kuljetusotsikko}</th>";
  echo "<td>{$kuljetustieto}</td>";
  echo "</tr>";
  echo "<tr>";
  echo "<th>" . t("Sinettinumero") . "</th>";
  echo "<td>{$tulonumeron_historia['tulonumeron_perustiedot']['sinettinumero']}</td>";
  echo "</tr>";
  echo "<tr>";
  echo "<th>" . t("Tulopäivä") . "</th>";
  echo "<td>" . date("d.m.Y", strtotime($tulonumeron_historia['tulonumeron_perustiedot']['tulopaiva'])) . "</td>";
  echo "</tr>";
  echo "</table>";

  echo '<br>';

  echo "<font class='message'>" . t("Tulonumeron") .' '. $tulonumero .' '. t("tuotteet") . "</font><br><br>";
  echo "<table>";
  echo "<tr>";
  echo "<th>". t("Tuote") ."</th>";
  echo "<th>". t("Malli") ."</th>";
  echo "<th>". t("Paino") ."</th>";
  echo "<th>". t("Tilavuus") . "</th>";
  echo "<th>". t("Pakkauksia") . "</th>";
  echo "<th>". t("Kpl / pakkaus") . "</th>";
  echo "<th>". t("Varastopaikka") . "</th>";
  echo "<th>". t("Purkuaika") . "</th>";
  echo "</tr>";

  foreach ($tulonumeron_historia['omat_rivit'] as $tuoterivi) {

    echo "<tr>";
    echo "<td>{$tuoterivi['nimitys']}</td>";
    echo "<td>{$tuoterivi['malli']}</td>";
    echo "<td>{$tuoterivi['tuotemassa']}</td>";
    echo "<td>{$tuoterivi['tuotetilavuus']}</td>";
    echo "<td>{$tuoterivi['kpl']}</td>";
    echo "<td>{$tuoterivi['pakkauskpl']}</td>";
    echo "<td>{$tuoterivi['hyllyalue']}{$tuoterivi['hyllynro']}</td>";
    echo "<td>{$tuoterivi['kerattyaika']}</td>";
    echo "</tr>";

  }
  echo "</table><br>";

  if (count($tulonumeron_historia['tuotesiirrot']) > 0) {

    foreach ($tulonumeron_historia['tuotesiirrot'] as $tyyppi => $aika) {

      echo "<font class='message'>";


      if ($tyyppi == 'eu') {
        echo t("Siirretty EU-numerolle tulonumerolta") . ': ' . $tulonumeron_historia['alkuperainen_tulonumero'] . ': ' . $aika;
      }

      if ($tyyppi == 'tulli') {
        echo t("Siirretty tullinumerolle tulonumerolta") . ': ' . $tulonumeron_historia['alkuperainen_tulonumero'] . ': ' . $aika;
      }
      echo '</font><br><br>';
    }
  }

  if($tulonumeron_historia['alkuperaiset_rivit'] != $tulonumeron_historia['omat_rivit']) {

    echo "<font class='message'>" . t("Alkuperäisen tulon") .' ('. $tulonumeron_historia['alkuperainen_tulonumero'] .') '. t("tuotteet") . "</font><br><br>";
    echo "<table>";
    echo "<tr>";
    echo "<th>". t("Tuote") ."</th>";
    echo "<th>". t("Malli") ."</th>";
    echo "<th>". t("Paino") ."</th>";
    echo "<th>". t("Tilavuus") . "</th>";
    echo "<th>". t("Pakkauksia") . "</th>";
    echo "<th>". t("Kpl / pakkaus") . "</th>";
    echo "<th>". t("Varastopaikka") . "</th>";
    echo "<th>". t("Purkuaika") . "</th>";
    echo "</tr>";

    foreach ($tulonumeron_historia['alkuperaiset_rivit'] as $tuoterivi) {

      echo "<tr>";
      echo "<td>{$tuoterivi['nimitys']}</td>";
      echo "<td>{$tuoterivi['malli']}</td>";
      echo "<td>{$tuoterivi['tuotemassa']}</td>";
      echo "<td>{$tuoterivi['tuotetilavuus']}</td>";
      echo "<td>{$tuoterivi['kpl']}</td>";
      echo "<td>{$tuoterivi['pakkauskpl']}</td>";
      echo "<td>{$tuoterivi['hyllyalue']}{$tuoterivi['hyllynro']}</td>";
      echo "<td>{$tuoterivi['kerattyaika']}</td>";
      echo "</tr>";

    }
    echo "</table><br>";

    if ($tulonumeron_historia['eu_numerolle_siirretyt_rivit']) {

      echo "<font class='message'>" . t("EU-numerolle siirretyt tuotteet") . "</font><br><br>";
      echo "<table>";
      echo "<tr>";
      echo "<th>". t("Tuote") ."</th>";
      echo "<th>". t("Malli") ."</th>";
      echo "<th>". t("Paino") ."</th>";
      echo "<th>". t("Tilavuus") . "</th>";
      echo "<th>". t("Pakkauksia") . "</th>";
      echo "<th>". t("Kpl / pakkaus") . "</th>";
      echo "<th>". t("Varastopaikka") . "</th>";
      echo "<th>". t("Purkuaika") . "</th>";
      echo "</tr>";

      foreach ($tulonumeron_historia['eu_numerolle_siirretyt_rivit'] as $tuoterivi) {

        echo "<tr>";
        echo "<td>{$tuoterivi['nimitys']}</td>";
        echo "<td>{$tuoterivi['malli']}</td>";
        echo "<td>{$tuoterivi['tuotemassa']}</td>";
        echo "<td>{$tuoterivi['tuotetilavuus']}</td>";
        echo "<td>{$tuoterivi['kpl']}</td>";
        echo "<td>{$tuoterivi['pakkauskpl']}</td>";
        echo "<td>{$tuoterivi['hyllyalue']}{$tuoterivi['hyllynro']}</td>";
        echo "<td>{$tuoterivi['kerattyaika']}</td>";
        echo "</tr>";

      }
      echo "</table><br>";
    }
  }

  if (count($tulonumeron_historia['toimitukset']) > 0) {

    echo "<font class='message'>" . t("Tulonumerolta") .' '. $tulonumero .' '.   t("toimituksiin liitetyt tuotteet") . "</font><br><br>";

    echo "<table>";
    echo "<tr>";
    echo "<th>". t("Toimitus#") ."</th>";
    echo "<th>". t("Tuote") ."</th>";
    echo "<th>". t("Malli") ."</th>";
    echo "<th>". t("Paino") ."</th>";
    echo "<th>". t("Tilavuus") . "</th>";
    echo "<th>". t("Pakkauksia") . "</th>";
    echo "<th>". t("Kpl / pakkaus") . "</th>";
    echo "<th>". t("Status") . "</th>";
    echo "</tr>";

    $toimitustunnus = $tulonumeron_historia['toimitukset'][0]['toimitustunnus'];

    foreach ($tulonumeron_historia['toimitukset'] as $tuoterivi) {

      if ($tuoterivi['toimitustunnus'] != $toimitustunnus) {

        echo "<tr>";
        echo "<th colspan='10'></th>";
        echo "</tr>";
      }

      echo "<tr>";
      echo "<td>{$tuoterivi['toimitustunnus']}</td>";
      echo "<td>{$tuoterivi['nimitys']}</td>";
      echo "<td>{$tuoterivi['malli']}</td>";
      echo "<td>{$tuoterivi['tuotemassa']}</td>";
      echo "<td>{$tuoterivi['tuotetilavuus']}</td>";
      echo "<td>{$tuoterivi['kpl']}</td>";
      echo "<td>{$tuoterivi['pakkauskpl']}</td>";

      if ($tuoterivi['kerattyaika'] == '0000-00-00 00:00:00') {
        $status = t("Ei vielä kerätty");
      }
      else {
        $status = t("Kerätty") . ': ' . $tuoterivi['kerattyaika'];
      }

      if ($tuoterivi['toimitettuaika'] != '0000-00-00 00:00:00') {
        $status = t("Toimitettu") . ': ' . $tuoterivi['toimitettuaika'];
      }

      echo "<td>{$status}</td>";
      echo "</tr>";

      $toimitustunnus = $tuoterivi['toimitustunnus'];
    }
    echo "</table><br>";
  }
}

if (isset($view) and $view == "perus") {

  echo "
  <form action='tullivarasto_raportit.php' method='post'>
  <input type='hidden' name='task' value='valitse_raporttityyppi' />
  <table>
  <tr>
    <th>" . t("Raporttityyppi") . "</th>
    <td>
      <select name='raporttityyppi' onchange='submit();'>
        <option selected disabled>" . t("Valitse") ."</option>
        <option value='laskutus'>".t("Laskutusraportti")."</option>
        <option value='tulo'>".t("Tuloraportti")."</option>
        <option value='tulli'>".t("Tulliraportti")."</option>
        <option value='purku'>".t("Purkuraportti")."</option>
        <option value='lastaus'>".t("Lastausraportti")."</option>
      </select>
    </td><td class='back'></td>
  </tr>
  </table>
  </form>";
}

require "inc/footer.inc";
