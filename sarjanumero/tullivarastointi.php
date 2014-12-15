<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");
require '../inc/edifact_functions.inc';

if (!isset($view)) {
  $view = 'saapumiskoodi';
}

echo "<meta name='viewport' content='width=device-width, maximum-scale=1.0' />\n";
echo "<link rel='stylesheet' type='text/css' href='ipad.css' />\n";
echo "<body>";

if (!isset($errors)) $errors = array();
if (!isset($viestit)) $viestit = array();

if (isset($submit) and $submit == 'saapumiskoodi') {

  if (empty($saapumiskoodi)) {
    $errors[] = t("Syötä saapumiskoodi");
  }
  else {
    $saapumistiedot = hae_saapumistiedot($saapumiskoodi);

    if(!$saapumistiedot) {
      $errors[] = t("Koodilla ei löytynyt mitään.");
    }
  }

  if (count($errors) == 0) {
    $view = 'tiedot';
  }
  else {
    $view = 'saapumiskoodi';
  }
}

echo "<div class='header'>";

echo "<div class='header_left'>";
echo "<a href='index.php' class='button header_button'>";
echo t("Päävalikko");
echo "</a>";
echo "</div>";

echo "<div class='header_center'>";
echo "<h1>";
echo t("Tullivarastointi");
echo "</h1>";
echo "</div>";

echo "<div class='header_right'>";
echo "<a href='{$palvelin2}logout.php?location={$palvelin2}sarjanumero' class='button header_button'>";
echo t("Kirjaudu&nbsp;ulos");
echo "</a>";
echo "</div>";

echo "</div>";

if ($view == 'saapumiskoodi') {

  echo "
  <form method='post' action=''>
    <div style='text-align:center;padding:10px;'>
      <label for='saapumiskoodi'>", t("Syötä saapumiskoodi"), "</label><br>
      <input type='text' id='saapumiskoodi' name='saapumiskoodi' style='margin:10px;' />
      <br>
      <button name='submit' value='saapumiskoodi' onclick='submit();' class='button'>", t("OK"), "</button>
    </div>
  </form>

  <script type='text/javascript'>
    $(document).on('touchstart', function(){
      $('#saapumiskoodi').focus();
    });
  </script>";

}

if ($view == 'tiedot') {
  echo "<div style='text-align:center'>";
  echo "<h2>tiedot tähän</h2>";
  print_r($saapumistiedot);
  echo "<div>";
}

if (count($viestit) > 0) {
  echo "<div class='viesti' style='text-align:center'>";
  foreach ($viestit as $viesti) {
    echo $viesti."<br>";
  }
  echo "</div>";
}

if (count($errors) > 0) {
  echo "<div class='error' style='text-align:center'>";
  foreach ($errors as $error) {
    echo $error."<br>";
  }
  echo "</div>";
}

require 'inc/footer.inc';
