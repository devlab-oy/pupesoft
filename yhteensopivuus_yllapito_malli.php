<?php

require "inc/parametrit.inc";

echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
    <!--

    function toggleAll(toggleBox) {

      var currForm = toggleBox.form;
      var isChecked = toggleBox.checked;
      var nimi = toggleBox.name;

      for (var elementIdx=0; elementIdx<currForm.elements.length; elementIdx++) {
        if (currForm.elements[elementIdx].type == 'checkbox') {
          currForm.elements[elementIdx].checked = isChecked;
        }
      }
    }

    //-->
    </script>";
// && currForm.elements[elementIdx].name.substring(0,6) == valinta) {

$tyyppi = $table == 'CV' ? "RA" : "HA";
$type = $table == 'CV' ? 'cv' : 'pc';
$reksuodatus = $type == 'cv' ? false : true;

// Mercantile AS: ei rekisterisuodatusta!
if ($yhtiorow['yhtio'] == 'mergr') {
  $reksuodatus = false;
}

require "inc/tecdoc.class.php";
$td = new tecdoc($type, $reksuodatus);


if (!isset($tuoteno)) $tuoteno = "";
if (!isset($tee)) $tee = "";
if (!isset($lopetus)) $lopetus = "";

echo "<font class='head'>", t("Yhteensopivuusylläpito malleittain"), "</font><hr>";

// perusnäkymä

// etsitään tuotenumerolla, tarkistetaan löytyykö tuotetta tuotetaulusta
if ($tuoteno != "") {
  $query = "SELECT tunnus
            FROM tuote
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tuoteno = '$tuoteno'";

  $resul = mysql_query($query, $link) or pupe_error($query);

  if (mysql_num_rows($resul) != 1) {
    echo "<font class='error'>", t("Tuotetta"), " $tuoteno ", t("ei löydy"), "!</font><br><br>";
    $tuoteno = "";
    $tee = "";
  }
}

// poistetaan soveltuvuudet
if ($tee == "poista") {

  if (count($atunnus) != 0 and count($poistatuote) != 0) {
    foreach ($atunnus as $tunnus) {
      foreach ($poistatuote as $tuoteno) {
        $query = "DELETE FROM yhteensopivuus_tuote
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tuoteno = '$tuoteno'
                  AND atunnus = '$tunnus'
                  AND tyyppi  = '$tyyppi'";
        $resul = mysql_query($query, $link) or pupe_error($query);
      }
      $valinta[] = $tunnus;
    }
  }
  $tee = "";

}

// lisätään soveltuvuuksia
if ($tee == "lisaa") {

  $aputunnus = $tunnus;
  $tunnus = '';

  $extra = trim($extra);
  $extra2 = trim($extra2);
  $extra3 = trim($extra3);
  $solu = trim($solu);

  if (count($valinta) != 0) {
    $atunnus = $valinta;
  }

  if (count($atunnus) != 0 and $tuoteno != "") {
    foreach ($atunnus as $tunnus) {
      $query = "SELECT tunnus
                FROM yhteensopivuus_tuote
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tuoteno = '$tuoteno'
                AND atunnus = '$tunnus'
                AND tyyppi  = '$tyyppi'";
      $resul = mysql_query($query, $link) or pupe_error($query);

      if (mysql_num_rows($resul) == 0) {
        $query = "INSERT INTO yhteensopivuus_tuote SET
                  yhtio   = '{$kukarow['yhtio']}',
                  tuoteno = '$tuoteno',
                  atunnus = '$tunnus',
                  extra   = '$extra',
                  extra2  = '$extra2',
                  extra3  = '$extra3',
                  solu    = '$solu',
                  tyyppi  = '$tyyppi'";
      }
      else {
        $query = "UPDATE yhteensopivuus_tuote SET
                  extra       = '$extra',
                  extra2      = '$extra2',
                  extra3      = '$extra3',
                  solu        = '$solu'
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tuoteno = '$tuoteno'
                  AND atunnus = '$tunnus'
                  AND tyyppi  = '$tyyppi'";
      }

      $resul = mysql_query($query, $link) or pupe_error($query);
      $valinta[] = $tunnus;
    }
  }
  $tee = "";

  $tunnus = $aputunnus;

}

echo "  <form method='post'>
    <input type='hidden' name='toim' value='{$toim}'>";

echo "<table>";
echo "<tr>";
echo "<th>".t("Tunnus").":</th><td><input type='text' name='tunnus' value='{$tunnus}' onSubmit='submit();'></td>";

// jos on syötetty autoid
if (trim($tunnus) != '') {

  $params = array(
    'tyyppi' => $type,
    'autoid' => $tunnus
  );

  $version = $td->getVersion($tunnus);

  $merkki = $version['manuid'];
  $malli = $version['modelno'];
}

$brands = $td->getBrands($merkki);

echo "<td><select name='merkki' onchange='submit()'><option value=''>".t("Valitse merkki")."</option>";

foreach ($brands as $brand) {
  $sel = $merkki == $brand['manuid'] ? ' selected' : '';
  echo "<option value='{$brand['manuid']}'{$sel}>{$brand['name']}</option>";
}

echo "</select></td>";

echo "<td><select name='malli' onchange='submit()'>";
echo "<option value=''>".t("Valitse malli")."</option>";

if ($merkki != '') {

  $models = $td->getModels($merkki);

  foreach ($models as $model) {
    $sel = $malli == $model['modelno'] ? ' selected' : '';
    echo "<option value='{$model['modelno']}'{$sel}>{$model['modelname']} {$model['year_txt']}</option>";
  }
}

echo "</select></td>";
echo "<td>";
echo "<input type='hidden' name='osasto' value='{$osasto}'>";
echo "<input type='hidden' name='tuoteryhma' value='{$tuoteryhma}'>";
?>
  <select name='table'>
    <option <?php if($table == "PC") { ?>selected<?php } ?> value="PC">PC</option>
    <option <?php if($table == "CV") { ?>selected<?php } ?> value="CV">CV</option>
  </select>
<?php
echo "<input type='submit' value='".t("Hae")."'></td>";
echo "</tr>";
echo "</table>";

echo "</form>\n";

if (trim($tunnus) != '') {
  if (!isset($atunnus) or !is_array($atunnus) or count($atunnus) == 0) {
    $atunnus = array($tunnus);
  }
}

if ((trim($merkki) != '' and trim($malli) != '') or count($atunnus) > 0) {

  if (count($atunnus) > 0) {
    $valinta = $atunnus;
  }

  // suodatetaan autolista valittujen tunnusten mukaan, jos valintoja on tehty

  if (count($valinta) > 0) {
    $versionlist = $td->getVersions($malli, $valinta);
  }
  else {
    $versionlist = $td->getVersions($malli);
  }


  if (count($versionlist) > 0) {
    echo "<form method='post'>
      <input type='hidden' name='toim' value='{$toim}'>
      <input type='hidden' name='merkki' value='{$merkki}'>
      <input type='hidden' name='malli' value='{$malli}'>
      <input type='hidden' name='osasto' value='{$osasto}'>
      <input type='hidden' name='tuoteryhma' value='{$tuoteryhma}'>
      <input type='hidden' name='table' value='{$table}' />";

    echo "<br><br><table>";
    echo "<tr>
        <th>".t("Autoid")."</th>
        <th>".t("Merkki ja malli")."</th>
        <th>".t("tilavuus")."</th>
        <th>".t("cc")."</th>
        <th>".t("Vuosimalli")."</th>
        <th>".t("kw")."</th>
        <th>".t("hp")."</th>";

    if ($type == 'pc') {
      echo "<th>".t("sylinterit")."</th>
          <th>".t("venttiilit")."</th>
          <th>".t("voimansiirto")."</th>
          <th>".t("polttoaine")."</th>
          <th>".t("moottorit")."</th>";
    }
    else {
      echo "<th>".t("Korityyppi")."</th>
          <th>".t("Moottorityyppi")."</th>
          <th>".t("Tonnit")."</th>
          <th>".t("Akselit")."</th>";
    }

    echo "<th>", t("Moottorikoodit"), "</th>
        <th>".t("rek. määrä")."</th>
        <th>".t("valitse")."</th>
      </tr>";

    foreach ($versionlist as $row) {
      echo "<tr>";
      echo "<td valing='top'>{$row['autoid']}</td>";
      echo "<td valing='top'>{$row['manu']} {$row['model']} {$row['version']}</td>";
      echo "<td valing='top'>{$row['capltr']}</td>";
      echo "<td valing='top'>{$row['cc']}</td>";
      echo "<td valing='top'>{$row['year_txt']}</td>";

      if ($type == 'pc') {
        echo "<td valing='top'>{$row['kw']}</td>";
        echo "<td valing='top'>{$row['hp']}</td>";
      }
      else {
        if ($row['kwl'] == 0) {
          echo "<td valing='top'>{$row['kwa']}</td>";
        }
        else {
          echo "<td valing='top'>{$row['kwa']} - {$row['kwl']}</td>";
        }

        if ($row['hpl'] == 0) {
          echo "<td valing='top'>{$row['hpa']}</td>";
        }
        else {
          echo "<td valing='top'>{$row['hpa']} - {$row['hpl']}</td>";
        }
      }

      if ($type == 'pc') {
        echo "<td valing='top'>{$row['cyl']}</td>
            <td valing='top'>{$row['valves']}</td>
            <td valing='top'>{$row['drivetype']}</td>
            <td valing='top'>{$row['fueltype']}</td>
            <td valing='top'>{$row['enginetype']}</td>";
      }
      else {
        echo "<td valing='top'>{$row['bodytype']}</td>
            <td valing='top'>{$row['enginetype']}</td>
            <td valing='top'>{$row['tons']}</td>
            <td valing='top'>{$row['axles']}</td>";
      }

      echo "<td valing='top'>{$row['mcodes']}</td>";
      echo "<td valing='top'>{$row['rekmaara']}</td>";

      if (in_array($row['autoid'], $valinta)) {
        $checked = "checked";
      } else {
        $checked = "";
      }

      echo "<td><input type='checkbox' name='valinta[]' value='{$row['autoid']}' {$checked}></td>";
      echo "</tr>";
    }

    $colspani = $type == 'cv' ? 13 : 14;

    echo "<tr><td colspan='{$colspani}' align='right'>", t("Valitse kaikki"), " --></td><td><input type='checkbox' name='valkaikki' value='yes' onchange='toggleAll(this)'></td></tr>";
    echo "<tr><td colspan='{$colspani}' align='left'>&nbsp;</td><td align='left'><input type='submit' value='", t("Valitse"), "'></td></tr>";

    echo "</form></table>";

  }

  echo "<br><font class='head'>".t("Soveltuvat tuotteet")."</font><hr>";

  echo "<form method='post'>
      <input type='hidden' name='toim' value='$toim'>
      <input type='hidden' name='tee' value='poista'>
      <input type='hidden' name='toim' value='$toim'>
      <input type='hidden' name='osasto' value='$osasto'>
      <input type='hidden' name='tuoteryhma' value='$tuoteryhma'>
      <input type='hidden' name='merkki' value='$merkki'>
      <input type='hidden' name='malli' value='$malli'>
      <input type='hidden' name='table' value='{$table}' />";

  if ($lopetus != '') {
    echo "<input type='hidden' name='lopetus' value='$lopetus'>";
  }

  $atunnukset = "";
  $htmlulos   = "";

  foreach ($versionlist as $row) {
    // otetaan atunnukset talteen mysql muotoon
    $atunnukset .= "'{$row['autoid']}',";
    // tehään nää muuttujaan, koska tarvitaan alla uudestaan
    $htmlulos .= "<input type='hidden' name='atunnus[]' value='$row[autoid]'>\n";
  }

  // vika pilkku pois
  $atunnukset = substr($atunnukset, 0, -1);
  // echotaan hidden kentät
  echo $htmlulos;

  if ($atunnukset != "") {

    $autoid = $atunnukset;

    $monivalintalaatikot = array("DYNAAMINEN_TUOTE");
    $monivalintalaatikot_normaali = array();

    if (file_exists("tilauskasittely/monivalintalaatikot.inc")) {
      require "tilauskasittely/monivalintalaatikot.inc";
    }
    else {
      require "monivalintalaatikot.inc";
    }

    echo "<br><table>\n";

    // katotaan mitä tuotteita löytyy näille malleille
    $query  = "SELECT DISTINCT yhteensopivuus_tuote.tuoteno, extra, extra2, extra3, solu, atunnus
               FROM yhteensopivuus_tuote
               JOIN tuote ON (tuote.yhtio = yhteensopivuus_tuote.yhtio AND tuote.tuoteno = yhteensopivuus_tuote.tuoteno)
               WHERE yhteensopivuus_tuote.yhtio = '{$kukarow['yhtio']}'
               AND yhteensopivuus_tuote.atunnus IN ($atunnukset)
               AND yhteensopivuus_tuote.tyyppi  = '{$tyyppi}'
               $lisa
               ORDER BY tuoteno";
    $apures = mysql_query($query, $link) or pupe_error($query);

    if (mysql_num_rows($result) < 50 or $nayta_kaikkirivit == 2) {

      echo "<tr>
        <th>".t("tuoteno")."</th>
        <th>".t("tuotemerkki")."</th>
        <th>".t("trynimi")."</th>
        <th>".t("nimitys")."</th>
        <th>".t("extra")."</th>
        <th>".t("extra2")."</th>
        <th>".t("extra3")."</th>
        <th>".t("solu")."</th>
        <th>".t("atunnus")."</th>
        <th>".t("poista")."</th>
        </tr>";

      while ($tuoterivi = mysql_fetch_assoc($apures)) {

        $query = "SELECT tuote.nimitys, tuote.tuotemerkki, avainsana.selitetark trynimi
                  FROM tuote
                  LEFT JOIN avainsana ON (avainsana.yhtio = tuote.yhtio AND avainsana.selite = tuote.try AND avainsana.laji = 'TRY' AND avainsana.kieli = '{$yhtiorow['kieli']}')
                  WHERE tuote.yhtio = '{$kukarow['yhtio']}'
                  AND tuote.tuoteno = '{$tuoterivi['tuoteno']}'
                  GROUP BY tuote.tuoteno
                  ORDER BY tuote.nimitys";
        $ressu = mysql_query($query, $link) or die($query);
        $tuote = mysql_fetch_assoc($ressu);

        echo "<tr>
          <td>{$tuoterivi['tuoteno']}</td>
          <td>{$tuote['tuotemerkki']}</td>
          <td>{$tuote['trynimi']}</td>
          <td>{$tuote['nimitys']}</td>
          <td>{$tuoterivi['extra']}</td>
          <td>{$tuoterivi['extra2']}</td>
          <td>{$tuoterivi['extra3']}</td>
          <td>{$tuoterivi['solu']}</td>
          <td>{$tuoterivi['atunnus']}</td>
          <td><input type='checkbox' name='poistatuote[]' value='{$tuoterivi['tuoteno']}'></td>
        </tr>";
      }

      echo "</table>";
      echo "<br><input type='submit' value='", t("Poista valitut soveltuvuudet"), "'>";

    }
    else {
      echo "<font class='message'>", t("Haulla löytyi"), " ".mysql_num_rows($result)." ", t("tuotetta"), ", ", t("tulosta ei näytetä"), ". </font> <input type='submit' value='", t("Näytä kaikki tuotteet"), "'><br>";
      echo "<input type='hidden' name='nayta_kai  kkirivit' value='2'>";
    }

  }

  echo "</form>";

  echo "<br><br><font class='head'>", t("Lisää"), "/", t("muokkaa"), " ", t("tuote"), "</font><hr>";

  echo "<form method='post'>
    <input type='hidden' name='toim' value='$toim'>
    <input type='hidden' name='tee' value='lisaa'>
    <input type='hidden' name='merkki' value='$merkki'>
    <input type='hidden' name='malli' value='$malli'>
    <input type='hidden' name='osasto' value='$osasto'>
    <input type='hidden' name='tuoteryhma' value='$tuoteryhma'>
    <input type='hidden' name='table' value='{$table}' />";

  if ($lopetus != '') {
    echo "<input type='hidden' name='lopetus' value='$lopetus'>";
  }

  echo $htmlulos;

  echo "<table>
      <tr>
        <th>", t("Anna tuotenumero"), ": </th>
        <td><input type='text' name='tuoteno' size='20'></td>
        <td class='back'></td>
      </tr>
      <tr>
        <th>", t("Extratieto"), ": </th>
        <td><input type='text' name='extra' size='20'></td>
      </tr>
      <tr>
        <th>", t("Extra2tieto"), ": </th>
        <td><input type='text' name='extra2' size='20'></td>
      </tr>
      <tr>
        <th>", t("Extra3tieto"), ": </th>
        <td><input type='text' name='extra3' size='20'></td>
      </tr>
      <tr>
        <th>", t("Solutieto"), ": </th>
        <td><input type='text' name='solu' size='20'></td>
        <td class='back'><input type='submit' value='", t("Lisää"), "/", t("muokkaa"), " ", t("tuote"), "'></td>
      </tr>
      </table>
    </form>";

}

require "inc/footer.inc";
