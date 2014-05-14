<?php

require("../inc/parametrit.inc");
require("inc/functions.inc");

echo "<font class='head'>".t("Jälkilaske tapahtuma")."</font><hr>";

if ($kukarow["kuka"] != "admin") {
  echo "<br><font class='error'>".t("HUOM: Tämä ohjelma sekoittaa kirjanpidon/logistiikan varastonarvon. Tätä EI saa käyttää!")."</font>";
  $tee = "DONTDOIT!!";
}

if ($tee == "KORJAA") {
  // takasin tuotteen valintaan
  $tee = "";

  $query = "  SELECT *
        FROM tapahtuma
        WHERE yhtio = '{$kukarow["yhtio"]}'
        AND tunnus = '$rivitunnus'";
  $res = pupe_query($query);

  if (mysql_num_rows($res) == 1) {
    $taparow = mysql_fetch_assoc($res);
    $tuoteno = $taparow["tuoteno"];
    $pvm = $taparow["laadittu"];
    $uusihinta = $taparow["kplhinta"];
    $rivitunnus = $taparow["rivitunnus"];
    $tapahtumatunnus = $taparow["tunnus"];

    echo "<font class='message'>Korjataan raaka-aineen $tuoteno tapahtumat $pvm ($rivitunnus) lähtien.</font><br>";

    jalkilaskentafunktio($tuoteno, $pvm, $uusihinta, $rivitunnus, $tapahtumatunnus);

    echo $jalkilaskenta_debug_text;
  }
  else {
    echo "<font class='error'>Tapahtumaa ei löydy!</font><br><br>";
  }
}

if ($tee == "VALITSE") {
  $query = "  SELECT *
        FROM tuote
        WHERE yhtio = '{$kukarow["yhtio"]}'
        AND tuoteno = '$tuoteno'";
  $res = pupe_query($query);

  if (mysql_num_rows($res) == 0) {
    echo "<font class='error'>Tuotetta ei löytynyt</font><br><br>";
    $tee = "";
  }
  else {
    $tuoterow = mysql_fetch_array($res);

    // näytetään tuotteen tapahtumat
    $query = "  SELECT *
          FROM tapahtuma use index (yhtio_tuote_laadittu)
          WHERE yhtio = '{$kukarow["yhtio"]}'
          and tuoteno = '{$tuoterow["tuoteno"]}'
          and laadittu >= '{$yhtiorow["tilikausi_alku"]}'
          ORDER BY laadittu DESC";
    $res = pupe_query($query);

    echo "<form method='post'>";
    echo "<input type='hidden' name='tee' value='KORJAA'>";

    echo "<table>";

    echo "<tr>";
    echo "<th>tuoteno</th>";
    echo "<th>laji</th>";
    echo "<th>kpl</th>";
    echo "<th>kplhinta</th>";
    echo "<th>kehahin</th>";
    echo "<th>selite</th>";
    echo "<th>laatija</th>";
    echo "<th>laadittu</th>";
    echo "<th>kääntöpiste</th>";
    echo "</tr>";

    $selected = "";

    while ($rivi = mysql_fetch_array($res)) {

      echo "<tr>";
      echo "<td>{$rivi["tuoteno"]}</td>";
      echo "<td>{$rivi["laji"]}</td>";
      echo "<td>{$rivi["kpl"]}</td>";
      echo "<td>{$rivi["kplhinta"]}</td>";
      echo "<td>{$rivi["hinta"]}</td>";
      echo "<td width='300'>{$rivi["selite"]}</td>";
      echo "<td>{$rivi["laatija"]}</td>";
      echo "<td>{$rivi["laadittu"]}</td>";
      echo "<td>";

      if ($rivi["laji"] == "tulo" or $rivi['laji'] == 'valmistus') {

        $value = "";
        if ($selected == "") {
          $value = "CHECKED";
          $selected = "X";
        }

        if ($rivi["rivitunnus"] != 0) {
          echo "<input type='radio' name='rivitunnus' $value value='{$rivi["tunnus"]}'>";
        }
        else {
          echo "<input type='radio' name='rivitunnus' $value value='{$rivi["tunnus"]}'>";
          echo "<font class='error'>Rivitunnus missing!</font>";
        }
      }

      echo "</td>";
      echo "</tr>";
    }

    echo "</table>";
    echo "<br><input type='Submit' value='".t("Korjaa tulo")."'>";
    echo "</form>";
  }
}

// meillä ei ole valittua tilausta
if ($tee == "") {
  $formi  = "find";
  $kentta = "tuoteno";

  // tehdään etsi valinta
  echo "<form name='find' method='post'>";
  echo t("Etsi tuotenumero").": ";
  echo "<input type='hidden' name='tee' value='VALITSE'>";
  echo "<input type='text' name='tuoteno'>";
  echo "<input type='Submit' value='".t("Etsi")."'>";
  echo "</form>";
}

require ("inc/footer.inc");
