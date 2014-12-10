<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

// otetaan includepath aina rootista
$pupe_root_polku = dirname(__FILE__);

ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupe_root_polku.PATH_SEPARATOR."/usr/share/pear");

require "inc/connect.inc";
require "inc/functions.inc";

// Logitetaan ajo
cron_log();

$xml = @simplexml_load_file("http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml");

if ($xml !== FALSE) {

  $pvm = tv1dateconv($xml->Cube->Cube->attributes()->time);
  $pvm_mysql = $xml->Cube->Cube->attributes()->time;

  foreach ($xml->Cube->Cube->Cube as $valuutta) {

    $valkoodi = (string) $valuutta->attributes()->currency;
    $kurssi   = (float)  $valuutta->attributes()->rate;

    $query = "UPDATE valuu, yhtio SET
              valuu.kurssi       = round(1 / $kurssi, 9),
              valuu.muutospvm    = now(),
              valuu.muuttaja     = 'crond'
              WHERE valuu.nimi   = '$valkoodi'
              AND yhtio.yhtio    = valuu.yhtio
              AND yhtio.valkoodi = 'EUR'";
    $result = pupe_query($query);

    $query = "INSERT INTO valuu_historia (kotivaluutta, valuutta, kurssi, kurssipvm)
              VALUES ('EUR', '$valkoodi', round(1 / $kurssi, 9), '$pvm_mysql')
                ON DUPLICATE KEY UPDATE kurssi = round(1 / $kurssi, 9)";
    $result = pupe_query($query);
  }

  echo date("d.m.Y @ G:i:s").": Eurokurssit päivitetty $pvm\n\n";
}
else {
  echo date("d.m.Y @ G:i:s").": Valuuttakurssien haku epäonnistui!\n\n";
}
