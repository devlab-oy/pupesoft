<?php

require_once("connect.inc");
require_once("functions.inc");

if (!isset($tuotenumero)) $tuotenumero = '';
if (!isset($nimitys)) $nimitys = '';

if (isset($_POST["tuotenumero"])) $tuotenumero = mysql_real_escape_string(trim($_POST["tuotenumero"]));
if (isset($_POST["nimitys"])) $nimitys = mysql_real_escape_string(trim($_POST["nimitys"]));

$lisa = "";

if (trim($tuotenumero) != '') {
  $lisa .= " and tuote.tuoteno like '%$tuotenumero%' ";
}

if (trim($nimitys) != '') {
  $lisa .= " and tuote.nimitys like '%$nimitys%' ";
}

$ei_try = '';

if (isset($tuotehaku_params)) {
  $kukarow["yhtio"] = $tuotehaku_params["yhtio"];
  $varastot = $tuotehaku_params["varastot"];
  $ei_try = " and try not in ('".implode("','", $tuotehaku_params["ei_try"])."')";
}
else {
  exit;
}

$yhtiorow = hae_yhtion_parametrit($kukarow["yhtio"]);

echo "<font class='head'>".t("Tuotekysely")."</font><hr>";

echo "<form action = 'tuotehaku.php' method = 'post'>";
echo "<table style='display:inline-table; padding-right:4px; padding-top:4px;' valign='top'>";
echo "<tr><th>Tuotenumero</th><td><input type='text' size='25' name='tuotenumero' id='tuotenumero' value = '$tuotenumero'></td>";
echo "<tr><th>Nimitys</th><td><input type='text' size='25' name='nimitys' id='nimitys' value = '$nimitys'></td>";
echo "<td><input type='Submit' name='submit_button' id='submit_button' value = 'Etsi'></td>";
echo "</tr>";
echo "</table><br/>";
echo "</form>";

if ($lisa != "") {

  $query = "SELECT
            tuoteno,
            nimitys,
            myyntihinta
            FROM tuote
            WHERE yhtio      = '{$kukarow["yhtio"]}'
            {$lisa}
            AND (status not in ('P','X') or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0)
            and tuotetyyppi  NOT IN ('A', 'B')
            and ei_saldoa    = ''
            and hinnastoon  != 'E'
            {$ei_try}
            ORDER BY tuoteno, nimitys
            Limit 500";
  $tuoteres = pupe_query($query);

  if (mysql_num_rows($tuoteres) > 0) {
    echo "<table>";
    echo "<tr>";
    echo "<th>Tuotekoodi</th>";
    echo "<th>Nimitys</th>";
    echo "<th>Hinta</th>";
    echo "<th>Saldo</th>";
    echo "</tr>";

    while ($tuoterow = mysql_fetch_assoc($tuoteres)) {
      list(, , $myytavissa) = saldo_myytavissa($tuoterow["tuoteno"], "", $varastot);

      echo "<tr>";
      echo "<td>{$tuoterow["tuoteno"]}</td>";
      echo "<td>{$tuoterow["nimitys"]}</td>";
      echo "<td align='right'>".sprintf('%0.2f', round($tuoterow["myyntihinta"], $yhtiorow["hintapyoristys"]))."</td>";
      echo "<td align='right'>{$myytavissa}</td>";
      echo "</tr>";
    }

    echo "</table>";
  }
  else {
    echo "Ei tuotteita!";
  }
}
else {
  echo "Anna hakuehto!";
}
