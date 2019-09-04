<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");

echo "<div class='header'>
<button onclick='window.location.href=\"index.php\"' class='button left'><img src='back2.png'></button>
<h1>TULOUTA</h1></div>";

echo "<div class='main'>";
echo "<b>", t("TULOTYYPPI"), "</b>";

echo "<ul>";

if ($yhtiorow['suuntalavat'] != "") {
  echo "<li><a href='alusta.php' class='button'>", t("ASN / Suuntalava"), "</a></li>";
}

echo "<li><a href='ostotilaus.php?uusi' class='button'>", t("Ostotilaus"), "</a>";

echo "<a href='ostotilaus.php' class='button'><font color='chucknorris'>(".t("Jatka edellistä").")</font></a>";

echo "</li>";

echo "<li><a href='siirtolista.php?uusi' class='button'>", t("Siirtolista"), "</a>";

echo "<a href='siirtolista.php' class='button'><font color='chucknorris'>(".t("Jatka edellistä").")</font></a>";

echo "</li>";

if ($yhtiorow['suuntalavat'] != "") {
  echo "<br>";
  echo "<li><a href='suuntalavat.php' class='button'>", t("Suuntalavat"), "</a></li>";
}

echo "<ul>";
echo "</div>";

echo "<div class='controls'>";
echo "</div>";

require 'inc/footer.inc';
