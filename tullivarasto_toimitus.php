<?php
require "inc/parametrit.inc";
require 'inc/edifact_functions.inc';

$errors = array();

if (!isset($task)) {
  $otsikko = t("Toimituksen kokoaminen") . " - " . t("Valitse toimittaja");
  $view = "perus";
}

if (isset($task) and $task == 'toimittajavalinta') {
  $view = 'perus';
  $otsikko = t("Toimituksen kokoaminen") . " - " . t("Syˆt‰ hakusana");
}

if (isset($task) and $task == 'etsi') {

  $hakulisa = '';

  if (!empty($hakusana_tulonumero)) {
    $hakusana_tulonumero = mysql_real_escape_string($hakusana_tulonumero);
    $hakulisa = "lasku.asiakkaan_tilausnumero LIKE '%{$hakusana_tulonumero}%'";
  }

  if (!empty($hakusana_nimitys)) {
    $hakusana_nimitys = mysql_real_escape_string($hakusana_nimitys);
    if (!empty($hakulisa)) {
      $hakulisa .= " OR ";
    }
    $hakulisa .= "tilausrivi.nimitys LIKE '%{$hakusana_nimitys}%'";
  }

  if (!empty($hakusana_malli)) {
    $hakusana_malli = mysql_real_escape_string($hakusana_malli);
    if (!empty($hakulisa)) {
      $hakulisa .= " OR ";
    }
    $hakulisa .= "tuote.malli LIKE '%{$hakusana_malli}%'";
  }

  if (!empty($hakulisa)) {
    $hakulisa = "AND (". $hakulisa .")";
  }



  $query = "SELECT tilausrivi.*,
            CONCAT(SUBSTRING(tilausrivi.hyllyalue, 3, 4), tilausrivi.hyllynro) AS varastopaikka,
            lasku.asiakkaan_tilausnumero AS tulonumero,
            varastopaikat.nimitys AS varastonimi,
            tuote.malli,
            lasku.nimi AS toimittajanimi
            FROM tilausrivi
            JOIN lasku
             ON lasku.yhtio = tilausrivi.yhtio
             AND lasku.tunnus = tilausrivi.otunnus
            JOIN varastopaikat
            ON varastopaikat.yhtio = lasku.yhtio
            AND varastopaikat.tunnus = lasku.varasto
            JOIN tuote
              ON tuote.yhtio = lasku.yhtio
              AND tuote.tuoteno = tilausrivi.tuoteno
            WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
            AND lasku.viesti = 'tullivarasto'
            AND lasku.liitostunnus = '{$toimittaja}'
            AND tilausrivi.hyllyalue != ''
            AND tilausrivi.otunnus != 0
            {$hakulisa}";
  $result = pupe_query($query);

  $rivit = array();

  while ($rivi = mysql_fetch_assoc($result)) {
    $rivit[] = $rivi;
  }

  $view = 'perus';
  $otsikko = t("Toimituksen kokoaminen");
}


echo "<font class='head'>{$otsikko}</font><hr><br>";

if ($view == 'perus') {

  if (!isset($toimittaja)) {
    $toimittaja = '';
    $task = 'toimittajavalinta';
  }
  else {
    $task = 'etsi';
  }

  echo "<form method='post'>";
  echo "<input type='hidden' name='task' value='$task' />";
  echo "<input type='hidden' name='toimittaja' value='$toimittaja' />";
  echo "<table><tr>";
  echo "<th>" .t("Valitse toimittaja"). "</th>";
  echo "<td>";

  $toimittajat = toimittajat(TRUE);

  echo "<select name='toimittaja' onchange='submit();'>
      <option value='X'>" . t("Valitse toimittaja") ."</option>";

      foreach ($toimittajat as $tunnus => $nimi) {

        if ($tunnus == $toimittaja) {
          $selected = 'selected';
        }
        else {
          $selected = '';
        }

        echo "<option value='{$tunnus}' {$selected}>{$nimi}</option>";
      }

      echo "</select>";


  echo "</td><td class='back'></td></tr>";

  if (!empty($toimittaja)) {

    echo "<tr>";
    echo "<th>" .t("Tulonumero"). "</th>";
    echo "<td><input type='text' name='hakusana_tulonumero' value='{$hakusana_tulonumero}'></td>";
    echo "<td class='back'></td>";
    echo "</tr>";

    echo "<tr>";
    echo "<th>" .t("Nimitys"). "</th>";
    echo "<td><input type='text' name='hakusana_nimitys' value='{$hakusana_nimitys}'></td>";
    echo "<td class='back'></td>";
    echo "</tr>";

    echo "<tr>";
    echo "<th>" .t("Malli"). "</th>";
    echo "<td><input type='text' name='hakusana_malli' value='{$hakusana_malli}'></td>";
    echo "<td class='back'><input type='submit' value='" . t("Etsi") . "'></td>";
    echo "</tr>";
  }

  echo "</table>";

  echo "<br>";

  echo "</form>";

  if (isset($rivit) and count($rivit) > 0) {

    echo "<table>";

    echo "
    <tr>
      <th>" . t("Tomittaja") ."</th>
      <th>" . t("Saapumiskoodi") ."</th>
      <th>" . t("Nimitys") ."</th>
      <th>" . t("Malli") ."</th>
      <th>" . t("Kpl") ."</th>
      <th>" . t("Varasto") ."</th>
      <th>" . t("Varastopaikka") ."</th>
      <th>" . t("Tomitukseen lis‰‰xminen") ."</th>
    </tr>";

    foreach ($rivit as $rivi) {

      $nimitys = $rivi['nimitys'];
      $tulonumero = $rivi['tulonumero'];
      $varastopaikka = $rivi['varastopaikka'];
      $malli = $rivi['malli'];

      $tulonumero = preg_replace("/".preg_quote($hakusana_tulonumero, "/")."/i", "<span style='background: white;'>$0</span>", $tulonumero);
      $nimitys = preg_replace("/".preg_quote($hakusana_nimitys, "/")."/i", "<span style='background: white;'>$0</span>", $nimitys);
      $malli = preg_replace("/".preg_quote($hakusana_malli, "/")."/i", "<span style='background: white;'>$0</span>", $malli);


      echo "
        <tr>
        <td>" . $rivi['toimittajanimi'] ."</td>
        <td>" . $tulonumero ."</td>
        <td>" . $nimitys . "</td>
        <td>" . $malli ."</td>
        <td>" . (int) $rivi['tilkpl'] ."</td>
        <td>" . $rivi['varastonimi'] ."</td>
        <td align='center'>" . $varastopaikka ."</td>
        <td>";

        if (!empty($rivi['varastopaikka'])) {

          if (isset(${$rivi['tunnus'].'kpl'})) {
            $kpl = ${$rivi['tunnus'].'kpl'};
          }
          else {
            $kpl = (int) $rivi['tilkpl'];
          }

          echo "
            <form method='post'>
            <input type='text' name='{$rivi['tunnus']}_kpl' value='{$kpl}' />
            <input type='hidden' name='hakusana_nimitys' value='{$hakusana_nimitys}'>
            <input type='hidden' name='hakusana_tulonumero' value='{$hakusana_tulonumero}'>
            <input type='hidden' name='hakusana_mali' value='{$hakusana_mali}'>
            <input type='hidden' name='toimittaja' value='$toimittaja' />
            <input type='submit' value='" . t("Lis&auml;&auml; toimitukseen") . "'/>
            </form>";

        }

      echo "
        </td>
      </tr>";

    }
  echo "</table>";
  }

  if (count($errors) > 0) {
    echo "<div class='error' style='text-align:center'>";
    foreach ($errors as $error) {
      echo $error."<br>";
    }
    echo "</div>";
  }

}







require "inc/footer.inc";




