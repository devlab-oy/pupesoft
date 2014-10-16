<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  echo "CLI only!";
  exit(1);
}

date_default_timezone_set('Europe/Helsinki');

// otetaan includepath aina rootista
$pupe_root_polku = dirname(__FILE__);

ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupe_root_polku.PATH_SEPARATOR."/usr/share/pear");
error_reporting(E_ALL);
ini_set("display_errors", 1);

// otetaan tietokanta connect
require "inc/connect.inc";
require "inc/functions.inc";

// Sallitaan vain yksi instanssi tästä skriptistä kerrallaan
pupesoft_flock();

// jos verkkolaskun lähetys on feilannut niin koitetaan lähettää verkkolasku-tiedosto uudelleen
// PUPEVOICE
$kansio = "{$pupe_root_polku}/dataout/pupevoice_error/";

if ($handle = opendir($kansio)) {
  while (($lasku = readdir($handle)) !== FALSE) {

    // Ei käsitellä kun Pupevoice tiedostoja
    if (!preg_match("/laskutus\-(.*?)\-2/", $lasku, $yhtio)) {
      continue;
    }

    $yhtio = $yhtio[1];
    $yhtiorow = hae_yhtion_parametrit($yhtio);
    $kukarow = hae_kukarow('admin', $yhtio);

    // Jos lasku on liian vanha, ei käsitellä, lähetetään maililla
    if (onko_lasku_liian_vanha($kansio.$lasku)) {
      continue;
    }

    $ftphost = (isset($verkkohost_lah) and trim($verkkohost_lah) != '') ? $verkkohost_lah : "ftp.verkkolasku.net";
    $ftpuser = $yhtiorow['verkkotunnus_lah'];
    $ftppass = $yhtiorow['verkkosala_lah'];
    $ftppath = (isset($verkkopath_lah) and trim($verkkopath_lah) != '') ? $verkkopath_lah : "out/einvoice/data/";
    $ftpfile = $kansio.$lasku;
    $ftpsucc = "{$pupe_root_polku}/dataout/";

    $tulos_ulos = "";

    require "inc/ftp-send.inc";
  }

  closedir($handle);
}

// IPOST FINVOICE
$kansio = "{$pupe_root_polku}/dataout/ipost_error/";

if ($handle = opendir($kansio)) {
  while (($lasku = readdir($handle)) !== FALSE) {

    // Ei käsitellä kun ipost tiedostoja
    if (!preg_match("/TRANSFER_IPOST\-(.*?)\-2/", $lasku, $yhtio)) {
      continue;
    }

    $yhtio = $yhtio[1];
    $yhtiorow = hae_yhtion_parametrit($yhtio);
    $kukarow = hae_kukarow('admin', $yhtio);

    // Jos lasku on liian vanha, ei käsitellä, lähetetään maililla
    if (onko_lasku_liian_vanha($kansio.$lasku)) {
      continue;
    }

    $ftphost     = "ftp.itella.net";
    $ftpuser     = $yhtiorow['verkkotunnus_lah'];
    $ftppass     = $yhtiorow['verkkosala_lah'];
    $ftppath     = "out/finvoice/data/";
    $ftpfile     = $kansio.$lasku;
    $renameftpfile   = str_replace("TRANSFER_IPOST", "DELIVERED_IPOST", $lasku);
    $ftpsucc     = "{$pupe_root_polku}/dataout/";

    $tulos_ulos = "";

    require "inc/ftp-send.inc";
  }

  closedir($handle);
}

// ELMAEDI
$kansio = "{$pupe_root_polku}/dataout/elmaedi_error/";

if ($handle = opendir($kansio)) {
  while (($lasku = readdir($handle)) !== FALSE) {

    // Ei käsitellä kun EDI tiedostoja
    if (!preg_match("/laskutus\-(.*?)\-2/", $lasku, $yhtio)) {
      continue;
    }

    $yhtio = $yhtio[1];
    $yhtiorow = hae_yhtion_parametrit($yhtio);
    $kukarow = hae_kukarow('admin', $yhtio);

    // Jos lasku on liian vanha, ei käsitellä, lähetetään maililla
    if (onko_lasku_liian_vanha($kansio.$lasku)) {
      continue;
    }

    $ftphost = $edi_ftphost;
    $ftpuser = $edi_ftpuser;
    $ftppass = $edi_ftppass;
    $ftppath = $edi_ftppath;
    $ftpfile = $kansio.$lasku;
    $ftpsucc = "{$pupe_root_polku}/dataout/";

    $tulos_ulos = "";

    require "inc/ftp-send.inc";
  }

  closedir($handle);
}

// PUPESOFT-FINVOICE
$kansio = "{$pupe_root_polku}/dataout/sisainenfinvoice_error/";

if ($handle = opendir($kansio)) {
  while (($lasku = readdir($handle)) !== FALSE) {

    // Ei käsitellä kun Finvoice tiedostoja
    if (!preg_match("/laskutus\-(.*?)\-2/", $lasku, $yhtio)) {
      continue;
    }

    $yhtio = $yhtio[1];
    $yhtiorow = hae_yhtion_parametrit($yhtio);
    $kukarow = hae_kukarow('admin', $yhtio);

    // Jos lasku on liian vanha, ei käsitellä, lähetetään maililla
    if (onko_lasku_liian_vanha($kansio.$lasku)) {
      continue;
    }

    $ftphost = $sisainenfoinvoice_ftphost;
    $ftpuser = $sisainenfoinvoice_ftpuser;
    $ftppass = $sisainenfoinvoice_ftppass;
    $ftppath = $sisainenfoinvoice_ftppath;
    $ftpfile = $kansio.$lasku;
    $ftpsucc = "{$pupe_root_polku}/dataout/";

    $tulos_ulos = "";

    require "inc/ftp-send.inc";
  }

  closedir($handle);
}

// MAVENTA
$kansio = "{$pupe_root_polku}/dataout/maventa_error/";

if ($handle = opendir($kansio)) {
  while (($lasku = readdir($handle)) !== FALSE) {

    // Ei käsitellä kun Maventa tiedostoja
    if (!preg_match("/laskutus\-(.*?)\-2[0-9]{7,7}\-([0-9]*?)\-serialized.txt/", $lasku, $matsit)) {
      continue;
    }

    $yhtio = $matsit[1];
    $yhtiorow = hae_yhtion_parametrit($yhtio);
    $kukarow = hae_kukarow('admin', $yhtio);

    // Jos lasku on liian vanha, ei käsitellä, lähetetään maililla
    if (onko_lasku_liian_vanha($kansio.$lasku)) {
      continue;
    }

    // Täytetään api_keys, näillä kirjaudutaan Maventaan
    $api_keys = array();
    $api_keys["user_api_key"]   = $yhtiorow['maventa_api_avain'];
    $api_keys["vendor_api_key"] = $yhtiorow['maventa_ohjelmisto_api_avain'];

    // Vaihtoehtoinen company_uuid
    if ($yhtiorow['maventa_yrityksen_uuid'] != "") {
      $api_keys["company_uuid"] = $yhtiorow['maventa_yrityksen_uuid'];
    }

    try {
      // Testaus
      //$client = new SoapClient('https://testing.maventa.com/apis/bravo/wsdl');

      // Tuotanto
      $client = new SoapClient('https://secure.maventa.com/apis/bravo/wsdl/');

      // Haetaan tarvittavat tiedot filestä
      $files_out = unserialize(file_get_contents($kansio.$lasku));

      $status = maventa_invoice_put_file($client, $api_keys, $matsit[2], "", $kukarow['kieli'], $files_out);

      // Siirretään dataout kansioon jos kaikki meni ok
      rename($kansio.$lasku, "{$pupe_root_polku}/dataout/$lasku");

      echo  "Maventa-lasku $matsit[2]: $status<br>\n";
    }
    catch (Exception $exVirhe) {
      echo "VIRHE: Yhteys Maventaan epäonnistui: ".$exVirhe->getMessage()."\n";
    }
  }

  closedir($handle);
}

// APIX
$kansio = "{$pupe_root_polku}/dataout/apix_error/";

if ($handle = opendir($kansio)) {
  while (($lasku = readdir($handle)) !== FALSE) {

    // Ei käsitellä kun Apix tiedostoja
    if (!preg_match("/Apix_(.*?)_invoices_/", $lasku, $matsit)) {
      continue;
    }

    $yhtio = $matsit[1];
    $yhtiorow = hae_yhtion_parametrit($yhtio);
    $kukarow = hae_kukarow('admin', $yhtio);

    // Jos lasku on liian vanha, ei käsitellä, lähetetään maililla
    if (onko_lasku_liian_vanha($kansio.$lasku)) {
      continue;
    }

    $status = apix_invoice_put_file(TRUE, "", "", $lasku);
    echo "APIX-lähetys $status<br>\n";
  }

  closedir($handle);
}

function onko_lasku_liian_vanha($filename) {
  global $kukarow, $yhtiorow;

  // Otetaan filen koko polku
  $filename = realpath($filename);

  // Jos file ollut alle vuorokauden error kansiossa, niin ei ole liian vanha
  if (time() - filemtime($filename) < 86400) {
    return false;
  }

  // Muuten on liian vanha ja lähetetään meili
  $parametri = array(
    "to"           => $yhtiorow["talhal_email"],
    "subject"      => t("Laskujen uudelleenlähetys"),
    "ctype"        => "text",
    "body"         => t("Laskujen uudelleenlähetys epäonnistunut yli vuorokauden."),
    "attachements" => array(0 =>
      array(
        "filename" => $filename,
      )),
  );

  $boob = pupesoft_sahkoposti($parametri);

  // Poistetaan lasku hakemistosta jos sähköpostin lähetys onnistui
  if ($boob) {
    unlink($filename);
  }

  return true;
}
