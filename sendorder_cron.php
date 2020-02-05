<?php

// Kutsutaanko CLI:stä
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
  $php_cli = TRUE;
}

date_default_timezone_set('Europe/Helsinki');

// Kutsutaanko CLI:stä
if (!$php_cli) {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!\n");
}

if (trim($argv[1]) == '') {
  die ("Et antanut lähettävää yhtiötä!\n");
}

if (trim($argv[2]) == '') {
  die ("Et antanut sähköpostiosoitetta!\n");
}

// lisätään includepathiin pupe-root
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__));
error_reporting(E_ALL);
ini_set("display_errors", 0);
ini_set("memory_limit", "2G");

// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";
require "inc/sahkoinen_tilausliitanta.inc";

// Logitetaan ajo
cron_log();

// Sallitaan vain yksi instanssi tästä skriptistä kerrallaan
pupesoft_flock();

$yhtio = mysql_escape_string(trim($argv[1]));
$yhtiorow = hae_yhtion_parametrit($yhtio);

$error_email = mysql_escape_string(trim($argv[2]));

if (!isset($futursoft_rest_http) or !isset($futursoft_rest_http[$yhtio])) {
  die ("Tarvittavia parametrejä puuttuu salasanat.php:sta (futursoft_rest_http)\n");
}

if (!isset($futursoft_rest_folder) or !isset($futursoft_rest_folder[$yhtio]) or !isset($futursoft_rest_folder[$yhtio]['ok']) or !isset($futursoft_rest_folder[$yhtio]['error'])) {
  die ("Tarvittavia parametrejä puuttuu salasanat.php:sta (futursoft_rest_folder)\n");
}

$path = $futursoft_rest_folder[$yhtio]['ok'];
$path = strrpos($path, '/', -1) === false ? $path.'/' : $path;

$error_path = $futursoft_rest_folder[$yhtio]['error'];
$error_path = strrpos($error_path, '/', -1) === false ? $error_path.'/' : $error_path;

if ($handle = opendir($path)) {
  while (false !== ($file = readdir($handle))) {

    if ($file == '.' or $file == '..' or $file == '.DS_Store' or is_dir($path.$file)) continue;

    $path_parts = pathinfo($file);
    $ext = strtoupper($path_parts['extension']);

    if ($ext == 'XML') {

      $filehandle = fopen($path.$file, "r");
      $contents = fread($filehandle, filesize($path.$file));

      // Jos 15min vanha file, siirretään se error-kansioon
      if ((time() + (15 * 60)) > filemtime($path.$file)) {
        rename($path.$file, $error_path.$file);

        $body = t("Futursoft SendOrder epäonnistui, ostotilausta ei pystytty lähettämään. Ostotilauksen aineisto on tallennettu kansioon %s", "", $error_path)."\n\n";
        $body .= t("Terveisin Pupesoft");

        $params = array(
          'to' => $error_email,
          'cc' => '',
          'subject' => t("Futursoft Sendorder epäonnistui")." - ".date('d.m.Y H:i'),
          'ctype' => 'html',
          'body' => $body,
          'attachements'  => array(0   => array(
              "filename"    => $error_path.$file,
              "newfilename"  => "Futur_sendorder_".date('d.m.Y H:i').".xml",
              "ctype"      => "XML"
            )
          )

        );

        pupesoft_sahkoposti($params);
      }
      else {

        $headers = array(
          "method" => "POST",
          "url" => $futursoft_rest_http[$yhtio],
          "headers" => array('Accept:text/xml', 'Content-Type: text/xml'),
          "posttype" => "xml",
          "data" => array($contents),
        );

        if (!GetServerInfo($headers)) continue;

        $return = pupesoft_rest($headers);

        $xml = simplexml_load_string(utf8_encode($return[1]));

        // Jos koodi on 200 eli success
        if ($return[0] == 200 and isset($xml->StatusCode) and $xml->StatusCode == 0) {
          // tiedoston lähetys onnistui
          unlink($path.$file);
        }
        else {
          // tuli erroria
        }
      }
    }
  }

  closedir($handle);
}
