<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die("Tätä scriptiä voi ajaa vain komentoriviltä!");
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
  die ("Et antanut yhtiötä.\n");
}

// ensimmäinen parametri yhtiö
$yhtio = mysql_real_escape_string($argv[1]);
$yhtiorow = hae_yhtion_parametrit($yhtio);

if (empty($yhtiorow)) {
  die("Yhtiö ei löydy.");
}

$kukarow = hae_kukarow('admin', $yhtio);

if (empty($kukarow)) {
  die("Admin -käyttäjä ei löydy.");
}

$verkkokauppatyyppi = isset($argv[2]) ? trim($argv[2]) : "";

if ($verkkokauppatyyppi != "magento" and $verkkokauppatyyppi != "anvia") {
  die("Et antanut verkkokaupan tyyppiä.\n");
}

if ($verkkokauppatyyppi == "magento") {
  // Varmistetaan, että kaikki muuttujat on kunnossa
  if (empty($magento_api_te_url) or empty($magento_api_te_usr) or empty($magento_api_te_pas) or empty($magento_tax_class_id)) {
    die("Magento parametrit puuttuu, päivitystä ei voida ajaa.");
  }
}

$ajetaanko_kaikki = empty($argv[3]) ? "NO" : "YES";

if (empty($verkkokauppa_saldo_varasto)) {
  $verkkokauppa_saldo_varasto = array();
}

if (!is_array($verkkokauppa_saldo_varasto)) {
  die("verkkokauppa_saldo_varasto pitää olla array!");
  exit;
}

// Haetaan timestamp
$datetime_checkpoint_res = t_avainsana("TUOTE_EXP_CRON");

if (mysql_num_rows($datetime_checkpoint_res) != 1) {
  die("VIRHE: Timestamp ei löydy avainsanoista!\n");
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
$datetime_checkpoint = $datetime_checkpoint_row['selite']; // Mikä tilanne on jo käsitelty
$datetime_checkpoint_uusi = date('Y-m-d H:i:s'); // Timestamp nyt

$lock_params = array(
  "locktime" => 5400
);

// Logitetaan ajo
cron_log();

// Sallitaan vain yksi instanssi tästä skriptistä kerrallaan
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

  // Magentoa varten pitää hakea kaikki tuotteet, jotta voidaan poistaa ne jota ei ole olemassa
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
  echo date("d.m.Y @ G:i:s")." - Haetaan osastot/tuoteryhmät.\n";

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

$tuote_export_error_count = 0;

echo date("d.m.Y @ G:i:s")." - Aloitetaan päivitys verkkokauppaan.\n";

if (isset($verkkokauppatyyppi) and $verkkokauppatyyppi == "magento") {

  $time_start = microtime(true);

  $magento_client = new MagentoClient($magento_api_te_url, $magento_api_te_usr, $magento_api_te_pas);

  if ($magento_client->getErrorCount() > 0) {
    exit;
  }

  // tax_class_id, magenton API ei anna hakea tätä mistään. Pitää käydä katsomassa magentosta
  $magento_client->setTaxClassID($magento_tax_class_id);

  // Verkkokaupan "root" kategorian tunnus, magenton API ei anna hakea tätä mistään. Pitää käydä katsomassa magentosta
  if (isset($magento_parent_id)) $magento_client->setParentID($magento_parent_id);

  // Verkkokaupanhintakenttä, joko myyntihinta tai myymalahinta
  if (isset($magento_hintakentta)) $magento_client->setHintakentta($magento_hintakentta);

  // Käytetäänkö tuoteryhminä tuoteryhmiä(default) vai tuotepuuta
  if (isset($magento_kategoriat)) $magento_client->setKategoriat($magento_kategoriat);

  // Onko "Category access control"-moduli on asennettu
  if (isset($categoryaccesscontrol)) $magento_client->setCategoryaccesscontrol($categoryaccesscontrol);

  // Mitä tuotteen kenttää käytetään configurable-tuotteen nimityksenä
  if (isset($magento_configurable_tuote_nimityskentta) and !empty($magento_configurable_tuote_nimityskentta)) {
    $magento_client->setConfigurableNimityskentta($magento_configurable_tuote_nimityskentta);
  }

  // Miten configurable-tuotteen lapsituotteet näkyvät verkkokaupassa.
  // Vaihtoehdot: NOT_VISIBLE_INDIVIDUALLY, CATALOG, SEARCH, CATALOG_SEARCH
  // Default on NOT_VISIBLE_INDIVIDUALLY
  if (isset($magento_configurable_lapsituote_nakyvyys) and !empty($magento_configurable_lapsituote_nakyvyys)) {
    $magento_configurable_lapsituote_nakyvyys = strtoupper($magento_configurable_lapsituote_nakyvyys);
    $magento_client->setConfigurableLapsituoteNakyvyys($magento_configurable_lapsituote_nakyvyys);
  }

  // Asetetaan custom simple-tuotekentät jotka eivät tule dynaamisista parametreistä. Array joka sisältää jokaiselle erikoisparametrille
  // array ('nimi' =>'magento_parametrin_nimi', 'arvo' = 'tuotteen_kentän_nimi_mistä_arvo_halutaan') esim. array ('nimi' => 'manufacturer', 'arvo' => 'tuotemerkki')
  if (isset($verkkokauppatuotteet_erikoisparametrit) and count($verkkokauppatuotteet_erikoisparametrit) > 0) {
    $magento_client->setVerkkokauppatuotteetErikoisparametrit($verkkokauppatuotteet_erikoisparametrit);
  }
  // Asetetaan custom asiakaskentät. Array joka sisältää jokaiselle erikoisparametrille
  // array ('nimi' =>'magento_parametrin_nimi', 'arvo' = 'asiakkaan_kentän_nimi_mistä arvo_halutaan') esim. array ('nimi' => 'lastname', 'arvo' => 'yhenk_sukunimi')
  // näillä arvoilla ylikirjoitetaan asiakkaan tiedot sekä laskutus/toimitusosoitetiedot
  if (isset($asiakkaat_erikoisparametrit) and count($asiakkaat_erikoisparametrit) > 0) {
    $magento_client->setAsiakkaatErikoisparametrit($asiakkaat_erikoisparametrit);
  }
  // Magentossa käsin hallitut kategoriat jotka säilytetään aina tuotepäivityksessä
  if (isset($magento_sticky_kategoriat) and count($magento_sticky_kategoriat) > 0) {
    $magento_client->setStickyKategoriat($magento_sticky_kategoriat);
  }
  // Halutaanko estää tilausten tuplasisäänluku, eli jos tilaushistoriasta löytyy käsittely
  // 'processing_pupesoft'-tilassa niin tilausta ei lueta sisään jos sisäänluvun esto on päällä
  // Default on: YES
  if (isset($magento_sisaanluvun_esto) and !empty($magento_sisaanluvun_esto)) {
    $magento_client->setSisaanluvunEsto($magento_sisaanluvun_esto);
  }

  // Halutaanko merkata kaikki uudet tuotteet aina samaan tuoteryhmään ja
  // estää tuoteryhmän yliajo tuotepäivityksessä
  if (isset($magento_universal_tuoteryhma) and !empty($magento_universal_tuoteryhma)) {
    $magento_client->setUniversalTuoteryhma($magento_universal_tuoteryhma);
  }

  // Aktivoidaanko asiakas luonnin yhteydessä Magentoon
  //   HUOM! Vaatii Magenton customointia
  if (isset($magento_asiakas_aktivointi) and !empty($magento_asiakas_aktivointi)) {
    $magento_client->setAsiakasAktivointi($magento_asiakas_aktivointi);
  }

  // Aktivoidaanko asiakaskohtaiset tuotehinnat
  //   HUOM! Vaatii Magenton customointia
  if (isset($magento_asiakaskohtaiset_tuotehinnat) and !empty($magento_asiakaskohtaiset_tuotehinnat)) {
    $magento_client->setAsiakaskohtaisetTuotehinnat($magento_asiakaskohtaiset_tuotehinnat);
  }

  // Poistetaanko/yliajetaanko Magenton default-tuoteparametrejä
  if (isset($magento_poista_defaultit) and !empty($magento_poista_defaultit)) {
    $magento_client->setPoistaDefaultTuoteparametrit($magento_poista_defaultit);
  }

  // Poistetaanko/yliajetaanko Magenton default-asiakasparametrejä
  if (isset($magento_poista_asiakasdefaultit) and !empty($magento_poista_asiakasdefaultit)) {
    $magento_client->setPoistaDefaultAsiakasparametrit($magento_poista_asiakasdefaultit);
  }

  // Tuoteparametrit, joita käytetään url_key:nä. url_key generoidaan tuotteen nimityksestä
  // sekä annetuista parametreistä ja niiden arvoista.
  //
  // $magento_url_key_attributes = array('vari', 'koko');
  // => "T-PAITA-vari-BLACK-koko-XL"
  if (isset($magento_url_key_attributes) and !empty($magento_url_key_attributes)) {
    $magento_client->setUrlKeyAttributes($magento_url_key_attributes);
  }

  // lisaa_kategoriat
  if (count($dnstuoteryhma) > 0) {
    echo date("d.m.Y @ G:i:s")." - Päivitetään tuotekategoriat\n";
    $count = $magento_client->lisaa_kategoriat($dnstuoteryhma);
    echo date("d.m.Y @ G:i:s")." - Päivitettiin $count kategoriaa\n";
  }

  // Päivitetaan magento-asiakkaat ja osoitetiedot kauppaan
  if (count($dnsasiakas) > 0 and isset($magento_siirretaan_asiakkaat)) {
    echo date("d.m.Y @ G:i:s")." - Päivitetään asiakkaat\n";
    $count = $magento_client->lisaa_asiakkaat($dnsasiakas);
    echo date("d.m.Y @ G:i:s")." - Päivitettiin $count asiakkaan tiedot\n";
  }

  // Tuotteet (Simple)
  if (count($dnstuote) > 0) {
    echo date("d.m.Y @ G:i:s")." - Päivitetään simple tuotteet\n";
    $count = $magento_client->lisaa_simple_tuotteet($dnstuote, $individual_tuotteet);
    echo date("d.m.Y @ G:i:s")." - Päivitettiin $count tuotetta (simple)\n";
  }

  // Tuotteet (Configurable)
  if (count($dnslajitelma) > 0) {
    echo date("d.m.Y @ G:i:s")." - Päivitetään configurable tuotteet\n";
    $count = $magento_client->lisaa_configurable_tuotteet($dnslajitelma);
    echo date("d.m.Y @ G:i:s")." - Päivitettiin $count tuotetta (configurable)\n";
  }

  // Saldot
  if (count($dnstock) > 0) {
    echo date("d.m.Y @ G:i:s")." - Päivitetään tuotteiden saldot\n";
    $count = $magento_client->paivita_saldot($dnstock);
    echo date("d.m.Y @ G:i:s")." - Päivitettiin $count tuotteen saldot\n";
  }

  // Poistetaan tuotteet jota ei ole kaupassa
  if (count($kaikki_tuotteet) > 0 and !isset($magento_esta_tuotepoistot)) {
    echo date("d.m.Y @ G:i:s")." - Poistetaan ylimääräiset tuotteet\n";
    // HUOM, tähän passataan **KAIKKI** verkkokauppatuotteet, methodi katsoo että kaikki nämä on kaupassa, muut paitsi gifcard-tuotteet dellataan!
    $count = $magento_client->poista_poistetut($kaikki_tuotteet, true);
    echo date("d.m.Y @ G:i:s")." - Poistettiin $count tuotetta\n";
  }

  $tuote_export_error_count = $magento_client->getErrorCount();

  if ($tuote_export_error_count != 0) {
    echo date("d.m.Y @ G:i:s")." - Päivityksessä tapahtui {$tuote_export_error_count} virhettä!\n";
  }

  $time_end = microtime(true);
  $time = round($time_end - $time_start);

  echo date("d.m.Y @ G:i:s")." - Tuote-export valmis! (Magento API {$time} sekuntia)\n";
}
elseif (isset($verkkokauppatyyppi) and $verkkokauppatyyppi == "anvia") {

  if (isset($anvia_ftphost, $anvia_ftpuser, $anvia_ftppass, $anvia_ftppath)) {
    $ftphost = $anvia_ftphost;
    $ftpuser = $anvia_ftpuser;
    $ftppass = $anvia_ftppass;
    $ftppath = $anvia_ftppath;
  }
  else {
    $ftphost = "";
    $ftpuser = "";
    $ftppass = "";
    $ftppath = "";
  }

  $tulos_ulos = "";

  if (count($dnstuote) > 0) {
    require "{$pupe_root_polku}/rajapinnat/tuotexml.inc";
  }

  if (count($dnstock) > 0) {
    require "{$pupe_root_polku}/rajapinnat/varastoxml.inc";
  }

  if (count($dnsryhma) > 0) {
    require "{$pupe_root_polku}/rajapinnat/ryhmaxml.inc";
  }

  if (count($dnsasiakas) > 0) {
    require "{$pupe_root_polku}/rajapinnat/asiakasxml.inc";
  }

  if (count($dnshinnasto) > 0) {
    require "{$pupe_root_polku}/rajapinnat/hinnastoxml.inc";
  }

  if (count($dnslajitelma) > 0) {
    require "{$pupe_root_polku}/rajapinnat/lajitelmaxml.inc";
  }
}

// Otetaan tietokantayhteys uudestaan (voi olla timeoutannu)
unset($link);
$link = mysql_connect($dbhost, $dbuser, $dbpass, true) or die ("Ongelma tietokantapalvelimessa $dbhost (tuote_export)");
mysql_select_db($dbkanta, $link) or die ("Tietokantaa $dbkanta ei löydy palvelimelta $dbhost! (tuote_export)");
mysql_set_charset("latin1", $link);
mysql_query("set group_concat_max_len=1000000", $link);

// Kun kaikki onnistui, päivitetään lopuksi timestamppi talteen
$query = "UPDATE avainsana SET
          selite      = '{$datetime_checkpoint_uusi}'
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND laji    = 'TUOTE_EXP_CRON'";
pupe_query($query);

if (mysql_affected_rows() != 1) {
  echo "Timestamp päivitys epäonnistui!\n";
}
