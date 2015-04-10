<?php

require "inc/parametrit.inc";
require 'inc/edifact_functions.inc';

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
            AND tunnus = '{$toimittajatunnus}'";echo $query;
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
      $viesti = t("Valitse kuukausi");
      break;

    case 'purku':
      $viesti = t("Syötä tulonumero");
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

if (!empty($message)) {
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

}

if (isset($view) and $view == "tulli") {

}

if (isset($view) and $view == "purku") {

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
      </select>
    </td><td class='back'></td>
  </tr>
  </table>
  </form>";
}

require "inc/footer.inc";
