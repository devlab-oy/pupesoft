<?php

// Kutsutaanko CLI:st‰
if (php_sapi_name() != 'cli') {
  die("T‰t‰ scripti‰ voi ajaa vain komentorivilt‰!");
}

$pupe_root_polku = dirname(dirname(__FILE__));

ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupe_root_polku);
ini_set("display_errors", 1);
ini_set("max_execution_time", 0); // unlimited execution time
ini_set("memory_limit", "2G");
error_reporting(E_ALL);
date_default_timezone_set('Europe/Helsinki');

require "inc/connect.inc";
require "inc/functions.inc";
require "rajapinnat/magento_client.php";
require "rajapinnat/tuote_export_functions.php";

if (empty($argv[1])) {
  die ("Et antanut yhtiˆt‰.\n");
}

// ensimm‰inen parametri yhtiˆ
$yhtio = mysql_real_escape_string($argv[1]);
$yhtiorow = hae_yhtion_parametrit($yhtio);

if (empty($yhtiorow)) {
  die("Yhtiˆ ei lˆydy.");
}

$kukarow = hae_kukarow('admin', $yhtio);

if (empty($kukarow)) {
  die("Admin -k‰ytt‰j‰ ei lˆydy.");
}

$verkkokauppatyyppi = isset($argv[2]) ? trim($argv[2]) : "";

if ($verkkokauppatyyppi != "magento" and $verkkokauppatyyppi != "anvia") {
  die("Et antanut verkkokaupan tyyppi‰.\n");
}

$ajetaanko_kaikki = empty($argv[3]) ? "NO" : "YES";

if ($verkkokauppatyyppi == "magento") {
  // Varmistetaan, ett‰ kaikki muuttujat on kunnossa
  if (empty($magento_api_te_url) or empty($magento_api_te_usr) or empty($magento_api_te_pas) or empty($magento_tax_class_id)) {
    die("Magento parametrit puuttuu, p‰ivityst‰ ei voida ajaa.");
  }
}

if ($verkkokauppatyyppi == "anvia") {
  if (empty($anvia_ftphost) or empty($anvia_ftpuser) or empty($anvia_ftppass) or empty($anvia_ftppath)) {
    die("Anvia parametrit puuttuu, p‰ivityst‰ ei voida ajaa.");
  }
}

if (empty($verkkokauppa_saldo_varasto)) {
  $verkkokauppa_saldo_varasto = array();
}

if (!is_array($verkkokauppa_saldo_varasto)) {
  die("verkkokauppa_saldo_varasto pit‰‰ olla array!");
}

// Haetaan timestamp
$datetime_checkpoint_res = t_avainsana("TUOTE_EXP_CRON");

if (mysql_num_rows($datetime_checkpoint_res) != 1) {
  die("VIRHE: Timestamp ei lˆydy avainsanoista!\n");
}

if (empty($magento_ajolista)) {
  $magento_ajolista = array(
    'tuotteet',
    'lajitelmatuotteet',
    'tuoteryhmat',
    'asiakkaat',
    'hinnastot',
    'saldot'
  );
}

$datetime_checkpoint_row = mysql_fetch_assoc($datetime_checkpoint_res);
$datetime_checkpoint = $datetime_checkpoint_row['selite']; // Mik‰ tilanne on jo k‰sitelty
$datetime_checkpoint_uusi = date('Y-m-d H:i:s'); // Timestamp nyt
$tuote_export_error_count = 0;

$lock_params = array(
  "locktime" => 5400
);

// Logitetaan ajo
cron_log();

// Sallitaan vain yksi instanssi t‰st‰ skriptist‰ kerrallaan
pupesoft_flock($lock_params);

echo date("d.m.Y @ G:i:s")." - Aloitetaan tuote-export.\n";

if (in_array('tuotteet', $magento_ajolista)) {
  echo date("d.m.Y @ G:i:s")." - Haetaan tuotetiedot.\n";

  $params = array(
    "ajetaanko_kaikki"                     => $ajetaanko_kaikki,
    "datetime_checkpoint"                  => $datetime_checkpoint,
    "magento_asiakaskohtaiset_tuotehinnat" => $magento_asiakaskohtaiset_tuotehinnat,
    "tuotteiden_asiakashinnat_magentoon"   => $tuotteiden_asiakashinnat_magentoon,
    "verkkokauppatyyppi"                   => $verkkokauppatyyppi,
  );

  $dnstuote = tuote_export_hae_tuotetiedot($params);

  // Magentoa varten pit‰‰ hakea kaikki tuotteet, jotta voidaan poistaa ne jota ei ole olemassa
  if ($verkkokauppatyyppi == 'magento') {
    echo date("d.m.Y @ G:i:s")." - Haetaan poistettavat tuotteet.\n";

    $response = tuote_export_hae_poistettavat_tuotteet();
    $kaikki_tuotteet     = $response['kaikki'];
    $individual_tuotteet = $response['individual'];
  }
}

if (in_array('saldot', $magento_ajolista)) {
  echo date("d.m.Y @ G:i:s")." - Haetaan saldot.\n";

  $params = array(
    "ajetaanko_kaikki"           => $ajetaanko_kaikki,
    "datetime_checkpoint"        => $datetime_checkpoint,
    "verkkokauppa_saldo_varasto" => $verkkokauppa_saldo_varasto,
  );

  $dnstock = tuote_export_hae_saldot($params);
}

if (in_array('tuoteryhmat', $magento_ajolista)) {
  echo date("d.m.Y @ G:i:s")." - Haetaan osastot/tuoteryhm‰t.\n";

  $params = array(
    "ajetaanko_kaikki"    => $ajetaanko_kaikki,
    "datetime_checkpoint" => $datetime_checkpoint,
  );

  $response = tuote_export_hae_tuoteryhmat($params);
  $dnsryhma      = $response['dnsryhma'];
  $dnstuoteryhma = $response['dnstuoteryhma'];
}

if (in_array('asiakkaat', $magento_ajolista)) {
  echo date("d.m.Y @ G:i:s")." - Haetaan asiakkaat.\n";

  $params = array(
    "ajetaanko_kaikki"             => $ajetaanko_kaikki,
    "datetime_checkpoint"          => $datetime_checkpoint,
    "magento_siirretaan_asiakkaat" => $magento_siirretaan_asiakkaat,
  );

  $dnsasiakas = tuote_export_hae_asiakkaat($params);
}

if (in_array('hinnastot', $magento_ajolista)) {
  echo date("d.m.Y @ G:i:s")." - Haetaan hinnastot.\n";

  $params = array(
    "ajetaanko_kaikki"    => $ajetaanko_kaikki,
    "datetime_checkpoint" => $datetime_checkpoint,
    "verkkokauppatyyppi"  => $verkkokauppatyyppi,
  );

  $dnshinnasto = tuote_export_hae_hinnastot($params);
}

if (in_array('lajitelmatuotteet', $magento_ajolista)) {
  echo date("d.m.Y @ G:i:s")." - Haetaan tuotteiden variaatiot.\n";

  $params = array(
    "ajetaanko_kaikki"    => $ajetaanko_kaikki,
    "datetime_checkpoint" => $datetime_checkpoint,
    "verkkokauppatyyppi"  => $verkkokauppatyyppi,
  );

  $dnslajitelma = tuote_export_hae_lajitelmatuotteet($params);
}

echo date("d.m.Y @ G:i:s")." - Aloitetaan p‰ivitys verkkokauppaan.\n";

if ($verkkokauppatyyppi == "magento") {
  $time_start = microtime(true);

  $magento_client = new MagentoClient($magento_api_te_url, $magento_api_te_usr, $magento_api_te_pas);

  if ($magento_client->getErrorCount() > 0) {
    exit;
  }

  // tax_class_id, magenton API ei anna hakea t‰t‰ mist‰‰n. Pit‰‰ k‰yd‰ katsomassa magentosta
  $magento_client->setTaxClassID($magento_tax_class_id);

  // Verkkokaupan "root" kategorian tunnus, magenton API ei anna hakea t‰t‰ mist‰‰n. Pit‰‰ k‰yd‰ katsomassa magentosta
  if (isset($magento_parent_id)) $magento_client->setParentID($magento_parent_id);

  // Verkkokaupanhintakentt‰, joko myyntihinta tai myymalahinta
  if (isset($magento_hintakentta)) $magento_client->setHintakentta($magento_hintakentta);

  // K‰ytet‰‰nkˆ tuoteryhmin‰ tuoteryhmi‰(default) vai tuotepuuta
  if (isset($magento_kategoriat)) $magento_client->setKategoriat($magento_kategoriat);

  // Onko "Category access control"-moduli on asennettu
  if (isset($categoryaccesscontrol)) $magento_client->setCategoryaccesscontrol($categoryaccesscontrol);

  // Mit‰ tuotteen kentt‰‰ k‰ytet‰‰n configurable-tuotteen nimityksen‰
  if (isset($magento_configurable_tuote_nimityskentta) and !empty($magento_configurable_tuote_nimityskentta)) {
    $magento_client->setConfigurableNimityskentta($magento_configurable_tuote_nimityskentta);
  }

  // Miten configurable-tuotteen lapsituotteet n‰kyv‰t verkkokaupassa.
  // Vaihtoehdot: NOT_VISIBLE_INDIVIDUALLY, CATALOG, SEARCH, CATALOG_SEARCH
  // Default on NOT_VISIBLE_INDIVIDUALLY
  if (isset($magento_configurable_lapsituote_nakyvyys) and !empty($magento_configurable_lapsituote_nakyvyys)) {
    $magento_configurable_lapsituote_nakyvyys = strtoupper($magento_configurable_lapsituote_nakyvyys);
    $magento_client->setConfigurableLapsituoteNakyvyys($magento_configurable_lapsituote_nakyvyys);
  }

  // Asetetaan custom simple-tuotekent‰t jotka eiv‰t tule dynaamisista parametreist‰. Array joka sis‰lt‰‰ jokaiselle erikoisparametrille
  // array ('nimi' =>'magento_parametrin_nimi', 'arvo' = 'tuotteen_kent‰n_nimi_mist‰_arvo_halutaan') esim. array ('nimi' => 'manufacturer', 'arvo' => 'tuotemerkki')
  if (isset($verkkokauppatuotteet_erikoisparametrit) and count($verkkokauppatuotteet_erikoisparametrit) > 0) {
    $magento_client->setVerkkokauppatuotteetErikoisparametrit($verkkokauppatuotteet_erikoisparametrit);
  }
  // Asetetaan custom asiakaskent‰t. Array joka sis‰lt‰‰ jokaiselle erikoisparametrille
  // array ('nimi' =>'magento_parametrin_nimi', 'arvo' = 'asiakkaan_kent‰n_nimi_mist‰ arvo_halutaan') esim. array ('nimi' => 'lastname', 'arvo' => 'yhenk_sukunimi')
  // n‰ill‰ arvoilla ylikirjoitetaan asiakkaan tiedot sek‰ laskutus/toimitusosoitetiedot
  if (isset($asiakkaat_erikoisparametrit) and count($asiakkaat_erikoisparametrit) > 0) {
    $magento_client->setAsiakkaatErikoisparametrit($asiakkaat_erikoisparametrit);
  }
  // Magentossa k‰sin hallitut kategoriat jotka s‰ilytet‰‰n aina tuotep‰ivityksess‰
  if (isset($magento_sticky_kategoriat) and count($magento_sticky_kategoriat) > 0) {
    $magento_client->setStickyKategoriat($magento_sticky_kategoriat);
  }
  // Halutaanko est‰‰ tilausten tuplasis‰‰nluku, eli jos tilaushistoriasta lˆytyy k‰sittely
  // 'processing_pupesoft'-tilassa niin tilausta ei lueta sis‰‰n jos sis‰‰nluvun esto on p‰‰ll‰
  // Default on: YES
  if (isset($magento_sisaanluvun_esto) and !empty($magento_sisaanluvun_esto)) {
    $magento_client->setSisaanluvunEsto($magento_sisaanluvun_esto);
  }

  // Halutaanko merkata kaikki uudet tuotteet aina samaan tuoteryhm‰‰n ja
  // est‰‰ tuoteryhm‰n yliajo tuotep‰ivityksess‰
  if (isset($magento_universal_tuoteryhma) and !empty($magento_universal_tuoteryhma)) {
    $magento_client->setUniversalTuoteryhma($magento_universal_tuoteryhma);
  }

  // Aktivoidaanko asiakas luonnin yhteydess‰ Magentoon
  //   HUOM! Vaatii Magenton customointia
  if (isset($magento_asiakas_aktivointi) and !empty($magento_asiakas_aktivointi)) {
    $magento_client->setAsiakasAktivointi($magento_asiakas_aktivointi);
  }

  // Aktivoidaanko asiakaskohtaiset tuotehinnat
  //   HUOM! Vaatii Magenton customointia
  if (isset($magento_asiakaskohtaiset_tuotehinnat) and !empty($magento_asiakaskohtaiset_tuotehinnat)) {
    $magento_client->setAsiakaskohtaisetTuotehinnat($magento_asiakaskohtaiset_tuotehinnat);
  }

  // Poistetaanko/yliajetaanko Magenton default-tuoteparametrej‰
  if (isset($magento_poista_defaultit) and !empty($magento_poista_defaultit)) {
    $magento_client->setPoistaDefaultTuoteparametrit($magento_poista_defaultit);
  }

  // Poistetaanko/yliajetaanko Magenton default-asiakasparametrej‰
  if (isset($magento_poista_asiakasdefaultit) and !empty($magento_poista_asiakasdefaultit)) {
    $magento_client->setPoistaDefaultAsiakasparametrit($magento_poista_asiakasdefaultit);
  }

  // Tuoteparametrit, joita k‰ytet‰‰n url_key:n‰. url_key generoidaan tuotteen nimityksest‰
  // sek‰ annetuista parametreist‰ ja niiden arvoista.
  //
  // $magento_url_key_attributes = array('vari', 'koko');
  // => "T-PAITA-vari-BLACK-koko-XL"
  if (isset($magento_url_key_attributes) and !empty($magento_url_key_attributes)) {
    $magento_client->setUrlKeyAttributes($magento_url_key_attributes);
  }

  // lisaa_kategoriat
  if (count($dnstuoteryhma) > 0) {
    echo date("d.m.Y @ G:i:s")." - P‰ivitet‰‰n tuotekategoriat\n";
    $count = $magento_client->lisaa_kategoriat($dnstuoteryhma);
    echo date("d.m.Y @ G:i:s")." - P‰ivitettiin $count kategoriaa\n";
  }

  // P‰ivitetaan magento-asiakkaat ja osoitetiedot kauppaan
  if (count($dnsasiakas) > 0 and isset($magento_siirretaan_asiakkaat)) {
    echo date("d.m.Y @ G:i:s")." - P‰ivitet‰‰n asiakkaat\n";
    $count = $magento_client->lisaa_asiakkaat($dnsasiakas);
    echo date("d.m.Y @ G:i:s")." - P‰ivitettiin $count asiakkaan tiedot\n";
  }

  // Tuotteet (Simple)
  if (count($dnstuote) > 0) {
    echo date("d.m.Y @ G:i:s")." - P‰ivitet‰‰n simple tuotteet\n";
    $count = $magento_client->lisaa_simple_tuotteet($dnstuote, $individual_tuotteet);
    echo date("d.m.Y @ G:i:s")." - P‰ivitettiin $count tuotetta (simple)\n";
  }

  // Tuotteet (Configurable)
  if (count($dnslajitelma) > 0) {
    echo date("d.m.Y @ G:i:s")." - P‰ivitet‰‰n configurable tuotteet\n";
    $count = $magento_client->lisaa_configurable_tuotteet($dnslajitelma);
    echo date("d.m.Y @ G:i:s")." - P‰ivitettiin $count tuotetta (configurable)\n";
  }

  // Saldot
  if (count($dnstock) > 0) {
    echo date("d.m.Y @ G:i:s")." - P‰ivitet‰‰n tuotteiden saldot\n";
    $count = $magento_client->paivita_saldot($dnstock);
    echo date("d.m.Y @ G:i:s")." - P‰ivitettiin $count tuotteen saldot\n";
  }

  // Poistetaan tuotteet jota ei ole kaupassa
  if (count($kaikki_tuotteet) > 0 and !isset($magento_esta_tuotepoistot)) {
    echo date("d.m.Y @ G:i:s")." - Poistetaan ylim‰‰r‰iset tuotteet\n";
    // HUOM, t‰h‰n passataan **KAIKKI** verkkokauppatuotteet, methodi katsoo ett‰ kaikki n‰m‰ on kaupassa, muut paitsi gifcard-tuotteet dellataan!
    $count = $magento_client->poista_poistetut($kaikki_tuotteet, true);
    echo date("d.m.Y @ G:i:s")." - Poistettiin $count tuotetta\n";
  }

  $tuote_export_error_count = $magento_client->getErrorCount();

  if ($tuote_export_error_count != 0) {
    echo date("d.m.Y @ G:i:s")." - P‰ivityksess‰ tapahtui {$tuote_export_error_count} virhett‰!\n";
  }

  $time_end = microtime(true);
  $time = round($time_end - $time_start);

  echo date("d.m.Y @ G:i:s")." - Tuote-export valmis! (Magento API {$time} sekuntia)\n";
}
elseif ($verkkokauppatyyppi == "anvia") {
  $ftphost = $anvia_ftphost;
  $ftpuser = $anvia_ftpuser;
  $ftppass = $anvia_ftppass;
  $ftppath = $anvia_ftppath;
  $tulos_ulos = "";

  if (count($dnstuote) > 0) {
    require "rajapinnat/tuotexml.inc";
  }

  if (count($dnstock) > 0) {
    require "rajapinnat/varastoxml.inc";
  }

  if (count($dnsryhma) > 0) {
    require "rajapinnat/ryhmaxml.inc";
  }

  if (count($dnsasiakas) > 0) {
    require "rajapinnat/asiakasxml.inc";
  }

  if (count($dnshinnasto) > 0) {
    require "rajapinnat/hinnastoxml.inc";
  }

  if (count($dnslajitelma) > 0) {
    require "rajapinnat/lajitelmaxml.inc";
  }
}

// Kun kaikki onnistui, p‰ivitet‰‰n lopuksi timestamppi talteen
tuote_export_paivita_avainsana($datetime_checkpoint_uusi);
