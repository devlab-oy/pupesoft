<?php

require "inc/parametrit.inc";

print "<font class='head'>".t("Avaa saapuminen")."</font><hr>";

if ($tee == "avaa") {

  // Nollataan mapvm, koska se ylikirjataan joka tapauksessa kun keikka loppulasketaan uudestaan
  $query = "UPDATE lasku
            SET alatila = '',
            kohdistettu     = 'K',
            mapvm           = '0000-00-00'
            WHERE yhtio     = '$kukarow[yhtio]'
            and tila        = 'K'
            and laskunro    = '$keikka'
            and tunnus      = '$tunnus'
            and vanhatunnus = 0";
  $res = pupe_query($query);

  if (mysql_affected_rows() != 1) {
    echo "<font class='error'>".t("Saapumisen avaus epäonnistui")."!</font>";
    $tee = "etsi";
  }
  else {
    echo "<font class='message'>".t("Saapuminen avattu")."!</font>";
    $tee = "";
  }

  echo "<br><br>";
}

if ($tee == "etsi") {

  $query = "SELECT *
            FROM lasku
            WHERE yhtio     = '$kukarow[yhtio]'
            and tila        = 'K'
            and laskunro    = '$keikka'
            and vanhatunnus = 0
            and alatila     = 'X'
            and kohdistettu = 'X'";
  $res = pupe_query($query);

  if (mysql_num_rows($res) == 1) {

    $row = mysql_fetch_array($res);

    echo "<form method='post'>";
    echo "<input type='hidden' name='tee' value='avaa'>";
    echo "<input type='hidden' name='tunnus' value='$row[tunnus]'>";
    echo "<input type='hidden' name='keikka' value='$row[laskunro]'>";

    echo "<table>";

    echo "<tr>";
    echo "<th>".t("saapuminen")."</th>";
    echo "<th>".t("ytunnus")."</th>";
    echo "<th>".t("nimi")."</th>";
    echo "<th>".t("tapvm")."</th>";
    echo "<th></th>";
    echo "</tr>";

    echo "<tr>";
    echo "<td>$row[laskunro]</td>";
    echo "<td>$row[ytunnus]</td>";
    echo "<td>$row[nimi]</td>";
    echo "<td>$row[tapvm]</td>";
    echo "<td><input type='submit' value='".t("Avaa")."'></td>";
    echo "</tr>";

    echo "</table><br>";

    echo "</form>";
  }
  else {
    echo "<font class='error'>".t("Saapumista ei löytynyt").": $keikka!</font>";
  }
}

echo "<form method='post'>";
echo "<input type='hidden' name='tee' value='etsi'>";

echo "<table>";
echo "<tr>";
echo "<th>".t("Syötä saapumisnumero").":</th>";
echo "<td><input type='text' name='keikka'></td>";
echo "</tr>";
echo "</table>";

echo "<br><input type='submit' value='".t("Etsi saapuminen")."'>";
echo "</form>";

require "inc/footer.inc";
