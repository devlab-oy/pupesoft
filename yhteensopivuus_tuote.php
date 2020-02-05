<?php

require "inc/parametrit.inc";
require "inc/tecdoc.inc";

$tyyppi = "HA";

echo "<font class='head'>".t("Yhteensopivuus tuotteittain")."</font><hr>";

echo "<form method='post'>";
echo "<input type='hidden' name='toim' value='{$toim}'>";
echo "<input type='hidden' name='tee' value='etsi'>";
echo "<table>";
echo "<tr>";
echo "<th>".t("Anna tuotenumero").": </th>";
echo "<td><input type='text' name='tuoteno' size='20' value='{$tuoteno}'></td>";
echo "<td class='back'><input type='submit' value='".t("Hae")."'></td>";
echo "</tr>";
echo "</table>";
echo "</form>";

if ($toim != '') {
  $tyyppi = $toim;
}

if ($tuoteno != "") {

  $query = "SELECT *
            FROM tuote
            WHERE yhtio = '$kukarow[yhtio]'
            AND tuoteno = '$tuoteno'";
  $resul = mysql_query($query) or pupe_error($query);

  if (mysql_num_rows($resul) != 1) {
    echo "<font class='error'>".t("Tuotetta")." $tuoteno ".t("ei löydy")."!</font><br>";
    $tuoteno = "";
    $tee = "";
  }
}

// perusnäkymä
if ($tee == "etsi" and $tuoteno != "") {

  // listataan tuotteen tämänhetkinen soveltuvuus
  echo "<br>".t("Tuotteen soveltuvuudet")."<hr>";

  $lisa = " and tuoteno = '$tuoteno' ";

  $query = "SELECT isatuoteno
            FROM tuoteperhe
            WHERE yhtio = '$kukarow[yhtio]'
            AND tuoteno = '$tuoteno'";
  $tuoteperhe_result = pupe_query($query);

  if (mysql_num_rows($tuoteperhe_result) > 0) {
    $lisa = " and tuoteno in ('$tuoteno',";
  }

  while ($tuoteperhe_row = mysql_fetch_assoc($tuoteperhe_result)) {
    $lisa .= "'$tuoteperhe_row[isatuoteno]',";
  }

  if (mysql_num_rows($tuoteperhe_result) > 0) {
    $lisa = substr($lisa, 0, -1);
    $lisa .= ") ";
  }

  $query = "SELECT *
            FROM yhteensopivuus_tuote
            WHERE yhtio = '$kukarow[yhtio]'
            $lisa
            AND tyyppi  = '$tyyppi'";
  $result = pupe_query($query);

  // jos tuotteita löytyi, näytetään ne
  if (mysql_num_rows($result) != 0) {

    $atunnukset = array();

    while ($rivi = mysql_fetch_array($result)) {
      $atunnukset[] = $rivi['atunnus'];
    }

    echo "<form method='post'>";
    echo "<input type='hidden' name='toim' value='$toim'>";
    echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";

    echo "<table>";

    echo "<tr>
        <th>".t("autoid")."</th>
        <th>".t("Merkki")."</th>
        <th>".t("Malli")."</th>
        <th>".t("Tilavuus")."</th>
        <th>".t("cc")."</th>
        <th>".t("kw")."</th>
        <th>".t("hp")."</th>
        <th>".t("sylinterit")."</th>
        <th>".t("venttiilit")."</th>
        <th>".t("polttoaine")."</th>
        <th>".t("voimansiirto")."</th>
        <th>".t("vuosimalli")."</th>
        <th>".t("Rekisteriöntimäärä")."</th>
        <th>".t("Moottorikoodit")."</th>
      </tr>";

    // haetaan autolista
    if ($tyyppi == "HA") {
      $re = td_getversion(array('tyyppi' => 'pc', 'autoid' => $atunnukset));
    }  
    else {
      $re = td_getversion(array('tyyppi' => 'cv', 'autoid' => $atunnukset));
    } 
    
    while ($row = mysql_fetch_assoc($re)) {

      echo "<tr class='aktiivi'>
        <td>{$row['autoid']}</td>
        <td>{$row['manu']}</td>
        <td>{$row['model']} {$row['version']}</td>
        <td>{$row['capltr']}</td>
        <td>{$row['cc']}</td>
        <td>{$row['kw']}</td>
        <td>{$row['hp']}</td>
        <td>{$row['cyl']}</td>
        <td>{$row['valves']}</td>
        <td>{$row['fueltype']}</td>
        <td>{$row['drivetype']}</td>
        <td align='right'>".td_niceyear($row['vma'], $row['vml'])."</td>
        <td align='right'>{$row['rekmaara']}</td>
        <td>{$row['mcodes']}</td>
      </tr>";
    }

    echo "</table>";
    echo "</form>";
  }
  else {
    echo "<font class='message'>".t("Ei soveltuvuuksia")."</font>";
  }
}

if (file_exists("inc/footer.inc")) {
  require "inc/footer.inc";
}
else {
  require "footer.inc";
}
