<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!\n");
}

date_default_timezone_set('Europe/Helsinki');

if (trim($argv[1]) == '') {
  die ("Et antanut yhtiötä!\n");
}

if (trim($argv[2]) == '') {
  die ("Et antanut luettavien tiedostojen polkua!\n");
}

// lisätään includepathiin pupe-root
ini_set("include_path", ini_get("include_path") . PATH_SEPARATOR . dirname(__FILE__));

// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";
require "rajapinnat/edi.php";

// Logitetaan ajo
cron_log();

// Sallitaan vain yksi instanssi tästä skriptistä kerrallaan
pupesoft_flock();

$yhtio    = mysql_real_escape_string(trim($argv[1]));
$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtio);

$path = trim($argv[2]);
$path = substr($path, -1) != '/' ? $path . '/' : $path;

$files = glob("{$path}/*.xml");

foreach ($files as $file) {
  $file = file_get_contents($file);
  $xml = simplexml_load_string($file);

  require "inc/verkkolasku-in-finvoice.inc";

  $laskuttajan_ovt = isset($laskuttajan_ovt) ? $laskuttajan_ovt : "";

  if ($laskuttajan_ovt == "037nnnnnnn999") {
    $rtuoteno = isset($rtuoteno) ? $rtuoteno : array();
    $laskun_summa_eur = isset($laskun_summa_eur) ? $laskun_summa_eur : "";
    $laskuttajan_valkoodi = isset($laskuttajan_valkoodi) ? $laskuttajan_valkoodi : "";
    $toim_asiakkaantiedot = isset($toim_asiakkaantiedot) ? $toim_asiakkaantiedot : array();
    $laskun_numero = isset($laskun_numero) ? $laskun_numero : "";
    $ostaja_asiakkaantiedot = isset($ostaja_asiakkaantiedot) ? $ostaja_asiakkaantiedot : array();
    $tilausyhteyshenkilo = isset($tilausyhteyshenkilo) ? $tilausyhteyshenkilo : "";
    $kohde = isset($kohde) ? $kohde : "";

    $items = array();

    foreach ($rtuoteno as $tilausrivi) {
      $item = array(
        "sku" => $tilausrivi["tuoteno"],
        "qty_ordered" => $tilausrivi["kpl"],
        "tax_percent" => $tilausrivi["alv"],
        "price" => $tilausrivi["hinta"]
      );

      array_push($items, $item);
    }

    $order = array(
      "increment_id" => $laskun_asiakkaan_tilausnumero,
      "grand_total" => $laskun_summa_eur,
      "order_currency_code" => $laskuttajan_valkoodi,
      "items" => $items,
      "laskuttajan_ovt" => $ostaja_asiakkaantiedot["toim_ovttunnus"],
      "toim_ovttunnus" => $toim_asiakkaantiedot["toim_ovttunnus"],
      "laskun_numero" => $laskun_numero,
      "tilausyhteyshenkilo" => $tilausyhteyshenkilo,
      "target" => $kohde
    );

    Edi::create($order, "finvoice");
  }
}
