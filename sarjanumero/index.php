<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");


if (!isset($view)) {
  $view = 'rulla';
}

echo "<meta name='viewport' content='width=device-width, maximum-scale=1.0' />\n";
echo "<link rel='stylesheet' type='text/css' href='ipad.css' />\n";
echo "<body>";

echo "<div class='header'>";
echo "<h1>";
echo "<a href='index.php'>";
echo t("P&Auml;&Auml;VALIKKO");
echo "</a>";
echo "</h1>";
echo "</div>";

echo "<div class='main valikko'>";

if ($view == 'top') {

  echo "<p><a href='index.php?view=tulli' class='button index_button'>", t("Tullivarasto tuloutus"), "</a>";
  echo "<a href='index.php?view=rulla' class='button index_button'>", t("Paperirullien käsittely"), "</a></p>";

}
elseif ($view == 'tulli') {

  if (tarkista_oikeus("sarjanumero/kuittaa.php")) {
    echo "<p><a href='tullivarastointi.php' class='button index_button'>", t("Tullivarasto juttu"), "</a></p>";
  }

}
elseif ($view == 'rulla') {

  if (tarkista_oikeus("sarjanumero/kuittaa.php")) {
    echo "<p><a href='kuittaa.php' class='button index_button'>", t("Kuittaa rahti"), "</a></p>";
  }

  if (tarkista_oikeus("sarjanumero/tuloutus.php")) {
    echo "<p><a href='tuloutus.php' class='button index_button'>", t("Varastoon vienti"), "</a></p>";
  }

  if (tarkista_oikeus("sarjanumero/kontitus.php")) {
    echo "<p><a href='kontitus.php' class='button index_button'>", t("Kontitus"), "</a></p>";
  }

  $lus = $hyl = $yli = false;

  if (tarkista_oikeus("sarjanumero/lusaus.php")) {
  $lus = true;
  }

  if (tarkista_oikeus("sarjanumero/hylky.php")) {
  $hyl = true;
  }

  if (tarkista_oikeus("sarjanumero/ylijaama.php")) {
  $yli = true;
  }

  if ($yli or $hyl or $lus) {
    echo "<p>";
    if ($lus) {
      echo "<a href='lusaus.php' class='button index_button'>", t("Lusaus"), "</a>";
    }
    if ($hyl) {
      echo "<a href='hylky.php' class='button index_button'>Hylk&auml;ys</a>";
    }
    if ($yli) {
      echo "<a href='ylijaama.php' class='button index_button'>Ylij&auml;&auml;m&auml;</a>";
    }
    echo "</p>";
  }

  if (tarkista_oikeus("sarjanumero/hae_tiedot.php")) {
    echo "<p><a href='hae_tiedot.php' class='button index_button'>", t("Hae tiedot"), "</a></p>";
  }

}



echo "<p><a href='{$palvelin2}logout.php?location={$palvelin2}sarjanumero' class='button index_button logout'>", t("Kirjaudu ulos"), "</a></p>";

echo "</div>";
echo "</body>";

require 'inc/footer.inc';
