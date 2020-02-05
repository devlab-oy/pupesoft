<?php

require "inc/parametrit.inc";
require "inc/tecdoc.inc";

$tyyppi = $table == 'CV' ? "RA" : "HA";
$type = $table == 'CV' ? 'cv' : 'pc';
$reksuodatus = $type == 'cv' ? false : true;

if (!isset($toim)) $toim = '';
if (!isset($tuoteno)) $tuoteno = '';
if (!isset($tee)) $tee = '';

echo "<font class='head'>", t("Yhteensopivuusylläpito tuotteittain"), "</font><hr>";

echo "<form method='post'>";
echo "<input type='hidden' name='toim' value='{$toim}'>";
echo "<input type='hidden' name='tee' value='etsi'>";
echo "<table>";
echo "<tr>";
echo "<th>", t("Anna tuotenumero"), ": </th>";
echo "<td><input type='text' name='tuoteno' size='20' value='{$tuoteno}'></td>";
echo "<td class='back'><input type='submit' value='Hae'></td>";
echo "</tr>";
echo "</table>";
echo "</form>";

if ($tuoteno != "") {

  $query = "SELECT *
            FROM tuote
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tuoteno = '{$tuoteno}'";
  $resul = mysql_query($query, $link) or pupe_error($query);;

  if (mysql_num_rows($resul) != 1) {
    echo "<font class='error'>", t("Tuotetta"), " {$tuoteno} ", t("ei löydy"), "!</font><br>";
    $tuoteno = "";
    $tee = "";
  }
}

// poistetaan soveltuvuudet
if ($tee == "poista") {

  if (count($poista) != 0) {
    foreach ($poista as $tunnus) {
      $query = "DELETE FROM yhteensopivuus_tuote
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tuoteno = '{$tuoteno}'
                AND atunnus = '{$tunnus}'
                AND tyyppi  = '{$tyyppi}'";
      $resul = mysql_query($query, $link) or pupe_error($query);;
    }
  }

  $tee = "etsi";
}

// lisätään soveltuvuuksia
if ($tee == "lisaa") {

  if (count($lisaa) != 0) {
    foreach ($lisaa as $tunnus) {
      $query = "INSERT INTO yhteensopivuus_tuote SET
                yhtio      = '{$kukarow['yhtio']}',
                tuoteno    = '{$tuoteno}',
                atunnus    = '{$tunnus}',
                tyyppi     = '{$tyyppi}',
                laatija    = '{$kukarow['kuka']}',
                luontiaika = now(),
                muutospvm  = now(),
                muuttaja   = '{$kukarow['kuka']}'";
      $resul = mysql_query($query, $link) or pupe_error($query);
    }
  }

  $tee = "etsi";
}

// perusnäkymä
if ($tee == "etsi" and $tuoteno != "") {

  if (!isset($merkki)) $merkki = '';
  if (!isset($malli)) $malli = '';

  // tehdään dropdownit, jolla voi lisätä uusia soveltuvuuksia
  echo "<br><font class='head'>", t("Lisää soveltuvuus"), "</font><hr>";

  echo "<form method='post'>";
  echo "<input type='hidden' name='toim' value='{$toim}'>";
  echo "<input type='hidden' name='tee' value='etsi'>";
  echo "<input type='hidden' name='tuoteno' value='{$tuoteno}'>";

  $params = array(
    'tyyppi' => $type,
    'reksuodatus' => $reksuodatus
  );

  $result = td_getbrands($params);

  echo "<select name='merkki' onchange='submit()'>";
  echo "<option value=''>", t("Valitse merkki"), "</option>";

  while ($rivi = mysql_fetch_array($result)) {
    $selected = '';

    if ($merkki == '' and $rekrow['merkki'] != '' and $rivi['manuid'] == $rekrow['merkki']) {
      $merkki = $rekrow['merkki'];
    }

    if ($merkki == $rivi["manuid"]) $selected = 'SELECTED';

    echo "<option value='{$rivi['manuid']}'{$selected}>{$rivi['name']}</option>";
  }

  echo "</select>";

  echo "<select name='malli' onchange='submit()'>";
  echo "<option value=''>", t("Valitse malli"), "</option>";

  if ($merkki != '') {

    $params = array(
      'tyyppi' => $type,
      'merkkino' => $merkki
    );

    $result = td_getmodels($params);

    while ($malli_row = mysql_fetch_assoc($result)) {

      $sel = $malli == $malli_row['modelno'] ? ' SELECTED' : '';

      echo "<option value='{$malli_row['modelno']}'{$sel}>{$malli_row['modelname']} ";
      echo td_niceyear($malli_row['vma'], $malli_row['vml']);
      echo "</option>";

    }
  }

  echo "</select>";

  echo "<input type='hidden' name='dropista' value='joo'>";
  echo "</form><br><br>\n";

  // listataan sopivat automallit
  $lisa = "";

  if (trim($merkki) != '' and trim($malli) != '') {

    $joinlisa = '';

    $params = array(
      'tyyppi' => $type,
      'mallino' => $malli,
      'merkkino' => $merkki
    );

    $result = td_getversion($params);

    echo "<form method='post'>";
    echo "<input type='hidden' name='toim' value='{$toim}'>";
    echo "<input type='hidden' name='tee' value='lisaa'>";
    echo "<input type='hidden' name='tuoteno' value='{$tuoteno}'>";
    echo "<input type='hidden' name='merkki' value='{$merkki}'>";
    echo "<input type='hidden' name='malli' value='{$malli}'>";

    echo "<table>";

    echo "<tr>";
    echo "<th>", t("Autoid"), "</th>";
    echo "<th>", t("Merkki ja malli"), "</th>";
    echo "<th>", t("tilavuus"), "</th>";
    echo "<th>", t("cc"), "</th>";
    echo "<th>", t("vm alku"), "</th>";
    echo "<th>", t("vm loppu"), "</th>";
    echo "<th>", t("kw"), "</th>";
    echo "<th>", t("hp"), "</th>";

    if ($type == 'pc') {
      echo "<th>", t("sylinterit"), "</th>";
      echo "<th>", t("venttiilit"), "</th>";
      echo "<th>", t("voimansiirto"), "</th>";
      echo "<th>", t("polttoaine"), "</th>";
      echo "<th>", t("moottorit"), "</th>";
    }
    else {
      echo "<th>", t("Korityyppi"), "</th>";
      echo "<th>", t("Moottorityyppi"), "</th>";
      echo "<th>", t("Tonnit"), "</th>";
      echo "<th>", t("Akselit"), "</th>";
    }

    echo "<th>", t("Moottorikoodit"), "</th>";

    echo "<th>lisää</th>";

    echo "</tr>";

    while ($autorivi = mysql_fetch_assoc($result)) {

      // katotaan löytyykö yhteensopivuus jo tälle mallille
      $query  = "SELECT *
                 FROM yhteensopivuus_tuote
                 WHERE yhtio = '{$kukarow['yhtio']}'
                 AND tuoteno = '{$tuoteno}'
                 AND atunnus = '{$autorivi['autoid']}'
                 AND tyyppi  = '{$tyyppi}'";
      $apures = mysql_query($query, $link) or pupe_error($query);

      // jos ei löydy niin listataan rivi
      if (mysql_num_rows($apures) == 0) {
        echo "<tr>";

        echo "<td valing='top'>{$autorivi['autoid']}</td>";
        echo "<td valing='top'>{$autorivi['manu']} {$autorivi['model']} {$autorivi['version']}</td>";
        echo "<td valing='top'>{$autorivi['capltr']}</td>";
        echo "<td valing='top'>{$autorivi['cc']}</td>";
        echo "<td valing='top'>", td_cleanyear($autorivi['vma']), "</td>";
        echo "<td valing='top'>", td_cleanyear($autorivi['vml']), "</td>";

        if ($type == 'pc') {
          echo "<td valing='top'>{$autorivi['kw']}</td>";
          echo "<td valing='top'>{$autorivi['hp']}</td>";
        }
        else {
          if ($row['kwl'] == 0) {
            echo "<td valing='top'>{$autorivi['kwa']}</td>";
          }
          else {
            echo "<td valing='top'>{$autorivi['kwa']} - {$autorivi['kwl']}</td>";
          }

          if ($rivi['hpl'] == 0) {
            echo "<td valing='top'>{$autorivi['hpa']}</td>";
          }
          else {
            echo "<td valing='top'>{$autorivi['hpa']} - {$autorivi['hpl']}</td>";
          }
        }

        if ($type == 'pc') {
          echo "<td valing='top'>{$autorivi['cyl']}</td>";
          echo "<td valing='top'>{$autorivi['valves']}</td>";
          echo "<td valing='top'>{$autorivi['drivetype']}</td>";
          echo "<td valing='top'>{$autorivi['fueltype']}</td>";
          echo "<td valing='top'>{$autorivi['enginetype']}</td>";
        }
        else {
          echo "<td valing='top'>{$autorivi['bodytype']}</td>";
          echo "<td valing='top'>{$autorivi['enginetype']}</td>";
          echo "<td valing='top'>{$autorivi['tons']}</td>";
          echo "<td valing='top'>{$autorivi['axles']}</td>";
        }

        echo "<td valing='top'>{$autorivi['mcodes']}</td>";

        echo "<td><input type='checkbox' value='{$autorivi['autoid']}' name='lisaa[]'></td>";
        echo "</tr>";
      }
    }

    echo "</table>";

    echo "<br><input type='submit' value='", t("Lisää valitut soveltuvuudet"), "'>";
    echo "</form><br><br>";
  }


  // listataan tuotteen tämänhetkinen soveltuvuus
  echo "<font class='head'>", t("Tuotteen soveltuvuudet"), "</font><hr>";

  $query = "SELECT *
            FROM yhteensopivuus_tuote
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tuoteno = '{$tuoteno}'
            AND tyyppi  = '{$tyyppi}'";
  $result = mysql_query($query, $link) or pupe_error($query);

  // jos tuotteita löytyi, näytetään ne
  if (mysql_num_rows($result) != 0) {

    $atunnukset = array();
    while ($rivi = mysql_fetch_assoc($result)) {
      $atunnukset[] = $rivi['atunnus'];
    }

    echo "<form method='post'>";
    echo "<input type='hidden' name='toim' value='{$toim}'>";
    echo "<input type='hidden' name='tee' value='poista'>";
    echo "<input type='hidden' name='tuoteno' value='{$tuoteno}'>";
    echo "<input type='hidden' name='merkki' value='{$merkki}'>";
    echo "<input type='hidden' name='malli' value='{$malli}'>";

    echo "<table>";

    echo "<tr>";
    echo "<th>", t("Autoid"), "</th>";
    echo "<th>", t("Merkki ja malli"), "</th>";
    echo "<th>", t("tilavuus"), "</th>";
    echo "<th>", t("cc"), "</th>";
    echo "<th>", t("vm alku"), "</th>";
    echo "<th>", t("vm loppu"), "</th>";
    echo "<th>", t("kw"), "</th>";
    echo "<th>", t("hp"), "</th>";

    if ($type == 'pc') {
      echo "<th>", t("sylinterit"), "</th>";
      echo "<th>", t("venttiilit"), "</th>";
      echo "<th>", t("voimansiirto"), "</th>";
      echo "<th>", t("polttoaine"), "</th>";
      echo "<th>", t("moottorit"), "</th>";
    }
    else {
      echo "<th>", t("Korityyppi"), "</th>";
      echo "<th>", t("Moottorityyppi"), "</th>";
      echo "<th>", t("Tonnit"), "</th>";
      echo "<th>", t("Akselit"), "</th>";
    }

    echo "<th>", t("Moottorikoodit"), "</th>";

    echo "<th>poista</th>";

    echo "</tr>";

    $params = array(
      'tyyppi' => $type,
      'mallino' => $malli,
      'merkkino' => $merkki,
      'autoid' => $atunnukset
    );

    $autores = td_getversion($params);

    while ($autorivi = mysql_fetch_assoc($autores)) {

      echo "<tr>";

      echo "<td valing='top'>{$autorivi['autoid']}</td>";
      echo "<td valing='top'>{$autorivi['manu']} {$autorivi['model']} {$autorivi['version']}</td>";
      echo "<td valing='top'>{$autorivi['capltr']}</td>";
      echo "<td valing='top'>{$autorivi['cc']}</td>";
      echo "<td valing='top'>", td_cleanyear($autorivi['vma']), "</td>";
      echo "<td valing='top'>", td_cleanyear($autorivi['vml']), "</td>";

      if ($type == 'pc') {
        echo "<td valing='top'>{$autorivi['kw']}</td>";
        echo "<td valing='top'>{$autorivi['hp']}</td>";
      }
      else {
        if ($row['kwl'] == 0) {
          echo "<td valing='top'>{$autorivi['kwa']}</td>";
        }
        else {
          echo "<td valing='top'>{$autorivi['kwa']} - {$autorivi['kwl']}</td>";
        }

        if ($rivi['hpl'] == 0) {
          echo "<td valing='top'>{$autorivi['hpa']}</td>";
        }
        else {
          echo "<td valing='top'>{$autorivi['hpa']} - {$autorivi['hpl']}</td>";
        }
      }

      if ($type == 'pc') {
        echo "<td valing='top'>{$autorivi['cyl']}</td>";
        echo "<td valing='top'>{$autorivi['valves']}</td>";
        echo "<td valing='top'>{$autorivi['drivetype']}</td>";
        echo "<td valing='top'>{$autorivi['fueltype']}</td>";
        echo "<td valing='top'>{$autorivi['enginetype']}</td>";
      }
      else {
        echo "<td valing='top'>{$autorivi['bodytype']}</td>";
        echo "<td valing='top'>{$autorivi['enginetype']}</td>";
        echo "<td valing='top'>{$autorivi['tons']}</td>";
        echo "<td valing='top'>{$autorivi['axles']}</td>";
      }

      echo "<td valing='top'>{$autorivi['mcodes']}</td>";

      echo "<td><input type='checkbox' value='{$autorivi['autoid']}' name='poista[]'></td>";
      echo "</tr>";

    }

    echo "</table>";

    echo "<br><input type='submit' value='", t("Poista valitut soveltuvuudet"), "'>";
    echo "</form>";
  }
  else {
    echo "<font class='message'>", t("Ei soveltuvuuksia"), "</font>";
  }

}

require "inc/footer.inc";
