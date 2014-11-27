<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");

echo "<meta name='viewport' content='width=device-width, maximum-scale=1.0' />\n";
echo "<link rel='stylesheet' type='text/css' href='ipad.css' />\n";
echo "<body>";

echo "<div class='header'>";
echo "<h1>";
echo t("P&Auml;&Auml;VALIKKO");
echo "</h1>";
echo "</div>";

echo "<div class='main valikko'>";

if (tarkista_oikeus("sarjanumero/kuittaa.php")) {
  echo "<p><a href='kuittaa.php' class='button'>", t("Kuittaa rahti vastaanotetuksi"), "</a></p>";
}

if (tarkista_oikeus("sarjanumero/tuloutus.php")) {
  echo "<p><a href='tuloutus.php' class='button'>", t("Tulouta"), "</a></p>";
}

if (tarkista_oikeus("sarjanumero/kontitus.php")) {
  echo "<p><a href='kontitus.php' class='button'>", t("Kontitus"), "</a></p>";
}

if (tarkista_oikeus("sarjanumero/lusaus.php")) {
  echo "<p><a href='lusaus.php' class='button'>", t("Suorita lusaus"), "</a></p>";
}

if (tarkista_oikeus("sarjanumero/hylky.php")) {
  echo "<p><a href='hylky.php' class='button'>Hylk&auml;&auml; rulla</a></p>";
}

if (tarkista_oikeus("sarjanumero/hae_tiedot.php")) {
  echo "<p><a href='hae_tiedot.php' class='button'>", t("Hae tiedot"), "</a></p>";
}

echo "<p><a href='{$palvelin2}logout.php?location={$palvelin2}sarjanumero' class='button'>", t("Kirjaudu ulos"), "</a></p>";

echo "</div>";
echo "</body>";

require 'inc/footer.inc';
