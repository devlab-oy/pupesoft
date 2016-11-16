<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

//kayttaja on syottanyt tietonsa login formiin
if (isset($_REQUEST["user"]) and $_REQUEST["user"] != '') {

  $login = "yes";

  if (@include_once "../inc/parametrit.inc");
  elseif (@include_once "inc/parametrit.inc");

  if (!isset($salamd5)) $salamd5 = '';
  if (!isset($mikayhtio)) $mikayhtio = '';
  if (!isset($uusi1)) $uusi1 = '';
  if (!isset($uusi2)) $uusi2 = '';
  if (!isset($yhtio)) $yhtio = '';

  $params = array(
    'user' => $user,
    'salasana' => $salasana,
    'salamd5' => $salamd5,
    'mikayhtio' => $mikayhtio,
    'uusi1' => $uusi1,
    'uusi2' => $uusi2,
    'yhtio' => $yhtio,
    'browkieli' => $browkieli,
    'palvelin' => $palvelin,
    'palvelin2' => $palvelin2,
    'mobile' => $mobile
  );

  $return = pupesoft_login($params);
}
else {
  if (@include_once "../inc/parametrit.inc");
  elseif (@include_once "inc/parametrit.inc");
}

$formi = "login"; // Kursorin ohjaus
$kentta = "user";

if (!headers_sent()) {
  header("Content-Type: text/html; charset=iso-8859-1");
  header("Pragma: public");
  header("Expires: 0");
  header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
  header("Cache-Control: no-store, no-cache, must-revalidate");
  header("Cache-Control: post-check=0, pre-check=0", false);
  header("Pragma: no-cache");

  echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Frameset//EN\"\n\"http://www.w3.org/TR/html4/frameset.dtd\">";
}

echo "
<html>
  <head>
  <title>Login</title>
  <meta name='viewport' content='width=250,height=246, user-scalable=no, maximum-scale=1'>
  <meta http-equiv='Pragma' content='no-cache'>
  <meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1'>
  <link rel='stylesheet' type='text/css' href='kasipaate.css' />
  </head>
";

echo "
<body>
<div class='header'><h1>", t("SISÄÄNKIRJAUTUMINEN", $browkieli), "</h1></div>";

if (isset($return['usea_yhtio']) and $return['usea_yhtio'] == 1) {

  if (count($return['usea']) == 0) {
    echo t("Sinulle löytyi monta käyttäjätunnusta, muttei yhtään yritystä", $browkieli), "!";
    exit;
  }

  echo "<table class='login'>";
  echo "<tr><td>", t("Valitse yritys", $browkieli), ":</td></tr>";

  foreach ($return['usea'] as $_yhtio => $_yhtionimi) {

    echo "<form action = '' method='post'>";
    echo "<tr>";

    echo "<td>";

    if (isset($return['error'])) {
      echo "<input type='hidden' name='return[error]' value='{$return['error']}'>";
    }

    echo "<input type='hidden' name='user'     value='{$user}'>";
    echo "<input type='hidden' name='salamd5' value='{$return['vertaa']}'>";
    echo "<input type='hidden' name='yhtio'    value='{$_yhtio}'>";
    echo "<input type='submit' value='{$_yhtionimi}'>";
    echo "</td>";
    echo "</tr>";
    echo "<tr><td>&nbsp;</td></tr>";
    echo "</form>";
  }

  echo "</table><br />";

  if (isset($return['error']) and $return['error'] != "") {
    echo "<font class='error'>{$return['error']}</font><br /><br />";
  }
}
else {

  echo "<table class='login'>
      <form name='login' target='_top' action='' method='post'>

      <tr><th>", t("Käyttäjätunnus", $browkieli), ":</th><td><input type='text' value='' name='user' size='15' maxlength='50'></td></tr>
      <tr><th>", t("Salasana", $browkieli), ":</th><td><input type='password' name='salasana' size='15' maxlength='30'></td></tr>
    </table>";

  if (isset($return['error']) and $return['error'] != "") {
    echo "<br /><font class='error'>{$return['error']}</font><br />";
  }

  echo "  <br /><input type='submit' class='button' value='", t("Sisään", $browkieli), "'>
      </form>";

  echo "<script LANGUAGE='JavaScript'>window.document.{$formi}.{$kentta}.focus();</script>";
}

echo "</td></tr></table>";
echo "</body></html>";

require 'inc/footer.inc';
