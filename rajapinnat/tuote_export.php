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

// toinen parametri verkkokauppa tyyppi
$verkkokauppatyyppi = isset($argv[2]) ? trim($argv[2]) : "";

if ($verkkokauppatyyppi != "magento" and $verkkokauppatyyppi != "anvia") {
  die("Et antanut verkkokaupan tyyppi‰.\n");
}

// kolmas parametri ajettavat exportit
if (!empty($argv[3])) {
  $magento_ajolista = explode(',', $argv[3]);
}
elseif (empty($magento_ajolista)) {
  $magento_ajolista = array(
    'tuotteet',
    'lajitelmatuotteet',
    'tuoteryhmat',
    'asiakkaat',
    'hinnastot',
    'saldot'
  );
}

// nelj‰s parametri haetaanko kaikki, vai vain muutokset viimeisest‰ ajosta
$ajetaanko_kaikki = empty($argv[4]) ? false : true;

// Tehd‰‰n lukkofile riippuen siit‰, mit‰ ajetaan. Tilauksien haulla pit‰‰ olla oma lukko.
if (count($magento_ajolista) == 1 and $magento_ajolista[0] == 'tilaukset') {
  $lockfile = 'tuote_export-tilaukset-flock.lock';
}
else {
  $lockfile = 'tuote_export-flock.lock';
}

// Pupesoftin varastojen tunnukset, joista lasketaan saldot. Nolla on kaikki varastot.
if (empty($verkkokauppa_saldo_varasto)) {
  $verkkokauppa_saldo_varasto = array(0);
}

if ($verkkokauppatyyppi == "magento") {
  // T‰ss‰ kaikki magentorajapinnan configurointimuuttujat

  // Varmistetaan, ett‰ pakolliset muuttujat on asetettu
  if (empty($magento_api_te_url) or empty($magento_api_te_usr) or empty($magento_api_te_pas)) {
    die("Magento parametrit puuttuu, p‰ivityst‰ ei voida ajaa.");
  }

  // Soap Clientin extra optiot
  if (empty($magento_client_options)) {
    $magento_client_options = array(
      // 'login'    => 'http_basic_user',
      // 'password' => 'http_basic_pass',
    );
  }

  // Lokitetaanko debug -tietoa lokitiedostoon
  if (empty($magento_debug)) {
    $magento_debug = false;
  }

  // Verkkokaupan "tax_class_id" tunnus
  if (empty($magento_tax_class_id)) {
    $magento_tax_class_id = 1;
  }

  // Verkkokaupan "root" kategorian tunnus
  if (empty($magento_parent_id)) {
    $magento_parent_id = 1;
  }

  // Magento websiten ID, joka laitetaan asiakashaussa mukaan resulttiin
  if (empty($magento_website_id)) {
    $magento_website_id = null;
  }

  // Verkkokaupan hinta -kentt‰, joko "myyntihinta" tai "myymalahinta"
  if (empty($magento_hintakentta)) {
    $magento_hintakentta = "myyntihinta";
  }

  // K‰ytet‰‰nkˆ tuoteryhmin‰ "tuoteryhm‰" tai "tuotepuu"
  if (empty($magento_kategoriat)) {
    $magento_kategoriat = "tuoteryhma";
  }

  // Onko "Category access control"-moduli on asennettu
  if (empty($categoryaccesscontrol)) {
    $categoryaccesscontrol = false;
  }

  // Poistetaanko tuotteita
  if (empty($magento_salli_tuotepoistot)) {
    $magento_salli_tuotepoistot = false;
  }

  // Lis‰t‰‰nkˆ tuotekuvat
  if (!isset($magento_lisaa_tuotekuvat)) {
    $magento_lisaa_tuotekuvat = true;
  }

  // Mit‰ tuotteen kentt‰‰ k‰ytet‰‰n simple-tuotteen nimityksess‰
  if (empty($magento_simple_tuote_nimityskentta)) {
    $magento_simple_tuote_nimityskentta = 'nimitys';
  }

  // Mit‰ tuotteen kentt‰‰ k‰ytet‰‰n configurable-tuotteen nimityksen‰
  if (empty($magento_configurable_tuote_nimityskentta)) {
    $magento_configurable_tuote_nimityskentta = "nimitys";
  }

  // Miten configurable-tuotteen lapsituotteet n‰kyv‰t verkkokaupassa.
  // Vaihtoehdot: NOT_VISIBLE_INDIVIDUALLY, CATALOG, SEARCH, CATALOG_SEARCH
  if (empty($magento_configurable_lapsituote_nakyvyys)) {
    $magento_configurable_lapsituote_nakyvyys = "NOT_VISIBLE_INDIVIDUALLY";
  }

  // Custom simple-tuotekent‰t, jotka eiv‰t tule dynaamisista parametreist‰.
  if (empty($verkkokauppatuotteet_erikoisparametrit)) {
    $verkkokauppatuotteet_erikoisparametrit = array(
      // array('nimi' => 'manufacturer', 'arvo' => 'tuotemerkki'),
      // array('nimi' => 'description',  'arvo' => 'lyhytkuvaus'),
    );
  }

  // Custom asiakaskent‰t.
  // N‰ill‰ arvoilla ylikirjoitetaan asiakkaan tiedot sek‰ laskutus/toimitusosoitetiedot
  if (empty($asiakkaat_erikoisparametrit)) {
    $asiakkaat_erikoisparametrit = array(
      // array('nimi' => 'lastname', 'arvo' => 'yhenk_sukunimi'),
    );
  }

  // Magentossa k‰sin hallitut kategoriatm jotka s‰ilytet‰‰n aina tuotep‰ivityksess‰
  if (empty($magento_sticky_kategoriat)) {
    $magento_sticky_kategoriat = array();
  }

  // Miss‰ tilassa olevat tilaukset haetaan Magentosta
  if (empty($magento_tilaushaku)) {
    $magento_tilaushaku = 'Processing';
  }

  // Halutaanko est‰‰ tilausten tuplasis‰‰nluku. Jos tilaushistoriasta lˆytyy tilaus
  // 'processing_pupesoft' -tilassa, niin tilausta ei lueta sis‰‰n, jos sis‰‰nluvun esto on p‰‰ll‰
  if (empty($magento_sisaanluvun_esto)) {
    $magento_sisaanluvun_esto = 'YES';
  }

  // Halutaanko merkata kaikki uudet tuotteet aina samaan tuoteryhm‰‰n ja
  // est‰‰ tuoteryhm‰n yliajo tuotep‰ivityksess‰
  if (empty($magento_universal_tuoteryhma)) {
    $magento_universal_tuoteryhma = '';
  }

  // Halutaanko perustaa tuotteet aina 'disabled' -tilaan
  if (empty($magento_perusta_disabled)) {
    $magento_perusta_disabled = false;
  }

  // Aktivoidaanko asiakas luonnin yhteydess‰ Magentoon
  // HUOM! Vaatii Magenton customointia
  if (empty($magento_asiakas_aktivointi)) {
    $magento_asiakas_aktivointi = false;
  }

  // Aktivoidaanko asiakaskohtaiset tuotehinnat
  // HUOM! Vaatii Magenton customointia
  if (empty($magento_asiakaskohtaiset_tuotehinnat)) {
    $magento_asiakaskohtaiset_tuotehinnat = false;
  }

  // Poistetaanko/yliajetaanko Magenton default-tuoteparametrej‰
  if (empty($magento_poista_defaultit)) {
    $magento_poista_defaultit = array();
  }

  // Poistetaanko/yliajetaanko Magenton default-asiakasparametrej‰
  if (empty($magento_poista_asiakasdefaultit)) {
    $magento_poista_asiakasdefaultit = array();
  }

  // Tuoteparametrit, joita k‰ytet‰‰n url_key:n‰. url_key generoidaan tuotteen nimityksest‰
  // sek‰ annetuista parametreist‰ ja niiden arvoista.
  if (empty($magento_url_key_attributes)) {
    $magento_url_key_attributes = array(
      // 'vari',
      // 'koko',
    );
    // => "T-PAITA-vari-BLACK-koko-XL"
  }

  // Siirret‰‰nkˆ asiakashinnta Magentoon
  if (empty($tuotteiden_asiakashinnat_magentoon)) {
    $tuotteiden_asiakashinnat_magentoon = false;
  }

  // Mihin hakemistoon EDI-tilaukset siirret‰‰n
  if (empty($magento_api_ht_edi)) {
    $magento_api_ht_edi = '/tmp';
  }

  // Vaihoehtoisia OVT-tunnuksia EDI-tilaukselle
  if (empty($verkkokauppa_erikoiskasittely)) {
    $verkkokauppa_erikoiskasittely = array();
  }

  // Korvaavia Maksuehtoja Magenton maksuehdoille
  if (empty($magento_maksuehto_ohjaus)) {
    $magento_maksuehto_ohjaus = array();
  }

  // Mille yritykselle tilaukset luetaan Pupesoftissa sis‰‰n
  if (empty($ovt_tunnus)) {
    $ovt_tunnus = $yhtiorow['ovttunnus'];
  }

  // Mik‰ on luodussa EDI-tilauksessa tilaustyyppi
  if (empty($pupesoft_tilaustyyppi)) {
    $pupesoft_tilaustyyppi = '';
  }

  // Mit‰ tuotetta k‰ytet‰‰n rahtikuluna
  if (empty($rahtikulu_tuoteno)) {
    $rahtikulu_tuoteno = $yhtiorow['rahti_tuotenumero'];
  }

  // Mik‰ nimitys laitetaan rahtikululle Pupesoftin tilaukselle
  if (empty($rahtikulu_nimitys)) {
    $rahtikulu_nimitys = 'L‰hetyskulut';
  }

  // Lis‰t‰‰nkˆ lapsituotteen nimitykseen parametrien arvot
  // Esim. nimitys: Kettlebell, parametri_paino: 10 kg --> nimitys: Kettlebell - 10 kg
  if (empty($magento_nimitykseen_parametrien_arvot)) {
    $magento_nimitykseen_parametrien_arvot = false;
  }

  // Mik‰ on verkkokauppa -asiakkaan ovttunnus/ytunnus/asiakasnumero Pupesoftissa
  // Pakollinen tieto
  if (empty($verkkokauppa_asiakasnro)) {
    $verkkokauppa_asiakasnro = null;
  }
}

if ($verkkokauppatyyppi == "anvia") {
  // T‰ss‰ kaikki anviarajapinnan configurointimuuttujat

  if (empty($anvia_ftphost) or empty($anvia_ftpuser) or empty($anvia_ftppass) or empty($anvia_ftppath)) {
    die("Anvia parametrit puuttuu, p‰ivityst‰ ei voida ajaa.");
  }
}

// Haetaan timestamp
$datetime_checkpoint_res = t_avainsana("TUOTE_EXP_CRON");

if (mysql_num_rows($datetime_checkpoint_res) != 1) {
  die("VIRHE: Timestamp ei lˆydy avainsanoista!\n");
}

$datetime_checkpoint_row = mysql_fetch_assoc($datetime_checkpoint_res);
$datetime_checkpoint = $datetime_checkpoint_row['selite']; // Mik‰ tilanne on jo k‰sitelty
$datetime_checkpoint_uusi = date('Y-m-d H:i:s'); // Timestamp nyt
$tuote_export_error_count = 0;

$lock_params = array(
  "lockfile" => $lockfile,
  "locktime" => 5400,
);

// alustetaan arrayt
$dnsasiakas = array();
$dnshinnasto = array();
$dnslajitelma = array();
$dnsryhma = array();
$dnstock = array();
$dnstuote = array();
$dnstuoteryhma = array();
$individual_tuotteet = array();
$kaikki_tuotteet = array();

// Logitetaan ajo
cron_log();

// Sallitaan vain yksi instanssi t‰st‰ skriptist‰ kerrallaan
pupesoft_flock($lock_params);

tuote_export_echo("Aloitetaan tuote-export.");

if (in_array('tuotteet', $magento_ajolista)) {
  tuote_export_echo("Haetaan tuotetiedot.");

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
    tuote_export_echo("Haetaan poistettavat tuotteet.");

    $response = tuote_export_hae_poistettavat_tuotteet();
    $kaikki_tuotteet     = $response['kaikki'];
    $individual_tuotteet = $response['individual'];
  }
}

if (in_array('saldot', $magento_ajolista)) {
  tuote_export_echo("Haetaan saldot.");

  $params = array(
    "ajetaanko_kaikki"           => $ajetaanko_kaikki,
    "datetime_checkpoint"        => $datetime_checkpoint,
    "verkkokauppa_saldo_varasto" => $verkkokauppa_saldo_varasto,
  );

  $dnstock = tuote_export_hae_saldot($params);
}

if (in_array('tuoteryhmat', $magento_ajolista)) {
  tuote_export_echo("Haetaan osastot/tuoteryhm‰t.");

  $params = array(
    "ajetaanko_kaikki"    => $ajetaanko_kaikki,
    "datetime_checkpoint" => $datetime_checkpoint,
  );

  $response = tuote_export_hae_tuoteryhmat($params);
  $dnsryhma      = $response['dnsryhma'];
  $dnstuoteryhma = $response['dnstuoteryhma'];
}

if (in_array('asiakkaat', $magento_ajolista)) {
  tuote_export_echo("Haetaan asiakkaat.");

  $params = array(
    "ajetaanko_kaikki"    => $ajetaanko_kaikki,
    "datetime_checkpoint" => $datetime_checkpoint,
    "magento_website_id"  => $magento_website_id,
    "verkkokauppatyyppi"  => $verkkokauppatyyppi,
  );

  $dnsasiakas = tuote_export_hae_asiakkaat($params);
}

if (in_array('hinnastot', $magento_ajolista)) {
  tuote_export_echo("Haetaan hinnastot.");

  $params = array(
    "ajetaanko_kaikki"    => $ajetaanko_kaikki,
    "datetime_checkpoint" => $datetime_checkpoint,
    "verkkokauppatyyppi"  => $verkkokauppatyyppi,
  );

  $dnshinnasto = tuote_export_hae_hinnastot($params);
}

if (in_array('lajitelmatuotteet', $magento_ajolista)) {
  tuote_export_echo("Haetaan tuotteiden variaatiot.");

  $params = array(
    "ajetaanko_kaikki"    => $ajetaanko_kaikki,
    "datetime_checkpoint" => $datetime_checkpoint,
    "verkkokauppatyyppi"  => $verkkokauppatyyppi,
  );

  $dnslajitelma = tuote_export_hae_lajitelmatuotteet($params);
}

tuote_export_echo("Aloitetaan p‰ivitys verkkokauppaan.");

if ($verkkokauppatyyppi == "magento") {
  $time_start = microtime(true);

  $magento_client = new MagentoClient(
    $magento_api_te_url,
    $magento_api_te_usr,
    $magento_api_te_pas,
    $magento_client_options,
    $magento_debug
  );

  $magento_client->set_magento_lisaa_tuotekuvat($magento_lisaa_tuotekuvat);
  $magento_client->set_magento_perusta_disabled($magento_perusta_disabled);
  $magento_client->set_magento_simple_tuote_nimityskentta($magento_simple_tuote_nimityskentta);
  $magento_client->setAsiakasAktivointi($magento_asiakas_aktivointi);
  $magento_client->setAsiakaskohtaisetTuotehinnat($magento_asiakaskohtaiset_tuotehinnat);
  $magento_client->setAsiakkaatErikoisparametrit($asiakkaat_erikoisparametrit);
  $magento_client->setCategoryaccesscontrol($categoryaccesscontrol);
  $magento_client->setConfigurableLapsituoteNakyvyys($magento_configurable_lapsituote_nakyvyys);
  $magento_client->setConfigurableNimityskentta($magento_configurable_tuote_nimityskentta);
  $magento_client->setHintakentta($magento_hintakentta);
  $magento_client->setKategoriat($magento_kategoriat);
  $magento_client->setParentID($magento_parent_id);
  $magento_client->setPoistaDefaultAsiakasparametrit($magento_poista_asiakasdefaultit);
  $magento_client->setPoistaDefaultTuoteparametrit($magento_poista_defaultit);
  $magento_client->setRemoveProducts($magento_salli_tuotepoistot);
  $magento_client->setStickyKategoriat($magento_sticky_kategoriat);
  $magento_client->setTaxClassID($magento_tax_class_id);
  $magento_client->setUniversalTuoteryhma($magento_universal_tuoteryhma);
  $magento_client->setUrlKeyAttributes($magento_url_key_attributes);
  $magento_client->setVerkkokauppatuotteetErikoisparametrit($verkkokauppatuotteet_erikoisparametrit);

  if ($magento_client->getErrorCount() > 0) {
    exit;
  }

  // lisaa_kategoriat
  if (count($dnstuoteryhma) > 0) {
    tuote_export_echo("P‰ivitet‰‰n tuotekategoriat");
    $count = $magento_client->lisaa_kategoriat($dnstuoteryhma);
    tuote_export_echo("P‰ivitettiin $count kategoriaa");
  }

  // P‰ivitetaan magento-asiakkaat ja osoitetiedot kauppaan
  if (count($dnsasiakas) > 0) {
    tuote_export_echo("P‰ivitet‰‰n asiakkaat");
    $count = $magento_client->lisaa_asiakkaat($dnsasiakas);
    tuote_export_echo("P‰ivitettiin $count asiakkaan tiedot");
  }

  // Tuotteet (Simple)
  if (count($dnstuote) > 0) {
    tuote_export_echo("P‰ivitet‰‰n simple tuotteet");
    $count = $magento_client->lisaa_simple_tuotteet($dnstuote, $individual_tuotteet);
    tuote_export_echo("P‰ivitettiin $count tuotetta (simple)");
  }

  // Tuotteet (Configurable)
  if (count($dnslajitelma) > 0) {
    tuote_export_echo("P‰ivitet‰‰n configurable tuotteet");
    $count = $magento_client->lisaa_configurable_tuotteet($dnslajitelma);
    tuote_export_echo("P‰ivitettiin $count tuotetta (configurable)");
  }

  // Saldot
  if (count($dnstock) > 0) {
    tuote_export_echo("P‰ivitet‰‰n tuotteiden saldot");
    $count = $magento_client->paivita_saldot($dnstock);
    tuote_export_echo("P‰ivitettiin $count tuotteen saldot");
  }

  // Poistetaan tuotteet jota ei ole kaupassa
  if (count($kaikki_tuotteet) > 0) {
    tuote_export_echo("Poistetaan ylim‰‰r‰iset tuotteet");
    $count = $magento_client->poista_poistetut($kaikki_tuotteet, true);
    tuote_export_echo("Poistettiin $count tuotetta");
  }

  if (in_array('tilaukset', $magento_ajolista)) {
    tuote_export_echo("Haetaan tilaukset");

    $magento_tilaus_client = new MagentoClient(
      $magento_api_ht_url,
      $magento_api_ht_usr,
      $magento_api_ht_pas,
      $magento_client_options,
      $magento_debug
    );

    // editilauksen tallentamiseen tarvittavat parametrit
    $magento_tilaus_client->set_edi_polku($magento_api_ht_edi);
    $magento_tilaus_client->set_magento_erikoiskasittely($verkkokauppa_erikoiskasittely);
    $magento_tilaus_client->set_magento_fetch_order_status($magento_tilaushaku);
    $magento_tilaus_client->set_magento_maksuehto_ohjaus($magento_maksuehto_ohjaus);
    $magento_tilaus_client->set_ovt_tunnus($ovt_tunnus);
    $magento_tilaus_client->set_pupesoft_tilaustyyppi($pupesoft_tilaustyyppi);
    $magento_tilaus_client->set_rahtikulu_nimitys($rahtikulu_nimitys);
    $magento_tilaus_client->set_rahtikulu_tuoteno($rahtikulu_tuoteno);
    $magento_tilaus_client->set_verkkokauppa_asiakasnro($verkkokauppa_asiakasnro);
    $magento_tilaus_client->setSisaanluvunEsto($magento_sisaanluvun_esto);

    $magento_tilaus_client->tallenna_tilaukset();
  }

  $tuote_export_error_count = $magento_client->getErrorCount();

  if ($tuote_export_error_count != 0) {
    tuote_export_echo("P‰ivityksess‰ tapahtui {$tuote_export_error_count} virhett‰!");
  }

  $time_end = microtime(true);
  $time = round($time_end - $time_start);

  tuote_export_echo("Tuote-export valmis! (Magento API {$time} sekuntia)");
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
