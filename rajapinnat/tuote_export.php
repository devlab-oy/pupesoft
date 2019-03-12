<?php

// Kutsutaanko CLI:st�
if (php_sapi_name() != 'cli') {
  die("T�t� scripti� voi ajaa vain komentorivilt�!");
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
  die ("Et antanut yhti�t�.\n");
}

// ensimm�inen parametri yhti�
$yhtio = mysql_real_escape_string($argv[1]);
$yhtiorow = hae_yhtion_parametrit($yhtio);

if (empty($yhtiorow)) {
  die("Yhti� ei l�ydy.");
}

$kukarow = hae_kukarow('admin', $yhtio);

if (empty($kukarow)) {
  die("Admin -k�ytt�j� ei l�ydy.");
}

// toinen parametri verkkokauppa tyyppi (t�t� ei k�ytet�, j�tetty taaksep�in yhteensopivuuden vuoksi)
$verkkokauppatyyppi = isset($argv[2]) ? trim($argv[2]) : "";

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
    'saldot'
  );
}

// nelj�s parametri haetaanko kaikki, vai vain muutokset viimeisest� ajosta
$ajetaanko_kaikki = empty($argv[4]) ? false : true;

// T�ss� kaikki magentorajapinnan configurointimuuttujat

// Varmistetaan, ett� pakolliset muuttujat on asetettu
if (empty($magento_api_te_url) or empty($magento_api_te_usr) or empty($magento_api_te_pas)) {
  die("Magento parametrit puuttuu, p�ivityst� ei voida ajaa.");
}

// Pupesoftin varastojen tunnukset, joista lasketaan saldot. Nolla on kaikki varastot.
if (empty($verkkokauppa_saldo_varasto)) {
  $verkkokauppa_saldo_varasto = array(0);
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

// Verkkokaupan hinta -kentt�, joko "myyntihinta" tai "myymalahinta"
if (empty($magento_hintakentta)) {
  $magento_hintakentta = "myyntihinta";
}

// K�ytet��nk� tuoteryhmin� "tuoteryhm�" tai "tuotepuu"
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

// Lis�t��nk� tuotekuvat
if (!isset($magento_lisaa_tuotekuvat)) {
  $magento_lisaa_tuotekuvat = true;
}

// Mit� tuotteen kentt�� k�ytet��n simple-tuotteen nimityksess�
if (empty($magento_simple_tuote_nimityskentta)) {
  $magento_simple_tuote_nimityskentta = 'nimitys';
}

// Milt� lapsituotteelta otetaan configurable (is�) tuotteen tiedot
// Vaihtoehdot: first tai cheapest
if (empty($magento_configurable_tuotetiedot)) {
  $magento_configurable_tuotetiedot = "first";
}

// Mit� tuotteen kentt�� k�ytet��n configurable-tuotteen nimityksen�
if (empty($magento_configurable_tuote_nimityskentta)) {
  $magento_configurable_tuote_nimityskentta = "nimitys";
}

// Miten configurable-tuotteen lapsituotteet n�kyv�t verkkokaupassa.
// Vaihtoehdot: NOT_VISIBLE_INDIVIDUALLY, CATALOG, SEARCH, CATALOG_SEARCH
if (empty($magento_configurable_lapsituote_nakyvyys)) {
  $magento_configurable_lapsituote_nakyvyys = "NOT_VISIBLE_INDIVIDUALLY";
}

// Custom simple-tuotekent�t, jotka eiv�t tule dynaamisista parametreist�.
if (empty($verkkokauppatuotteet_erikoisparametrit)) {
  $verkkokauppatuotteet_erikoisparametrit = array(
    // array('nimi' => 'manufacturer', 'arvo' => 'tuotemerkki'),
    // array('nimi' => 'description',  'arvo' => 'lyhytkuvaus'),
    // array('nimi' => 'kieliversiot', 'arvo' => array('en' => array(4, 13))),
  );
}

// Custom asiakaskent�t.
// N�ill� arvoilla ylikirjoitetaan asiakkaan tiedot sek� laskutus/toimitusosoitetiedot
if (empty($asiakkaat_erikoisparametrit)) {
  $asiakkaat_erikoisparametrit = array(
    // array('nimi' => 'lastname', 'arvo' => 'yhenk_sukunimi'),
  );
}

// Magentossa k�sin hallitut kategoriatm jotka s�ilytet��n aina tuotep�ivityksess�
if (empty($magento_sticky_kategoriat)) {
  $magento_sticky_kategoriat = array();
}

// Miss� tilassa olevat tilaukset haetaan Magentosta
if (empty($magento_tilaushaku)) {
  $magento_tilaushaku = 'Processing';
}

// Halutaanko est�� tilausten tuplasis��nluku. Jos tilaushistoriasta l�ytyy tilaus
// 'processing_pupesoft' -tilassa, niin tilausta ei lueta sis��n, jos sis��nluvun esto on p��ll�
if (empty($magento_sisaanluvun_esto)) {
  $magento_sisaanluvun_esto = 'YES';
}

// Halutaanko merkata kaikki uudet tuotteet aina samaan tuoteryhm��n ja
// est�� tuoteryhm�n yliajo tuotep�ivityksess�
if (empty($magento_universal_tuoteryhma)) {
  $magento_universal_tuoteryhma = '';
}

// Halutaanko perustaa tuotteet aina 'disabled' -tilaan
if (empty($magento_perusta_disabled)) {
  $magento_perusta_disabled = false;
}

// Aktivoidaanko asiakas luonnin yhteydess� Magentoon
// HUOM! Vaatii Magenton customointia
if (empty($magento_asiakas_aktivointi)) {
  $magento_asiakas_aktivointi = false;
}

// Aktivoidaanko asiakaskohtaiset tuotehinnat
// T�h�n tulee antaa Magenton 'websiteCode', johon asiakashinnat liitet��n
// HUOM! Vaatii Magenton customointia
if (empty($magento_asiakaskohtaiset_tuotehinnat)) {
  // $magento_asiakaskohtaiset_tuotehinnat = 'pro_shop';
  $magento_asiakaskohtaiset_tuotehinnat = false;
}

// Poistetaanko/yliajetaanko Magenton default-tuoteparametrej�
if (empty($magento_poista_defaultit)) {
  $magento_poista_defaultit = array();
}

// Poistetaanko/yliajetaanko Magenton default-asiakasparametrej�
if (empty($magento_poista_asiakasdefaultit)) {
  $magento_poista_asiakasdefaultit = array();
}

// Tuoteparametrit, joita k�ytet��n url_key:n�. url_key generoidaan tuotteen nimityksest�
// sek� annetuista parametreist� ja niiden arvoista.
if (empty($magento_url_key_attributes)) {
  $magento_url_key_attributes = array(
    // 'vari',
    // 'koko',
  );
  // => "T-PAITA-vari-BLACK-koko-XL"
}

// Siirret��nk� asiakashinnta Magentoon
if (empty($tuotteiden_asiakashinnat_magentoon)) {
  $tuotteiden_asiakashinnat_magentoon = false;
}

// Mihin hakemistoon EDI-tilaukset siirret��n
if (empty($magento_api_ht_edi)) {
  $magento_api_ht_edi = '/tmp';
}

// Vaihoehtoisia OVT-tunnuksia EDI-tilaukselle
if (empty($verkkokauppa_erikoiskasittely)) {
  // Avaimet
  // 0 = Verkkokaupan nimi
  // 1 = Editilaus_tilaustyyppi
  // 2 = Tilaustyyppilisa
  // 3 = Myyjanumero
  // 4 = Vaihtoehtoinen ovttunnus OSTOTIL.OT_TOIMITTAJANRO -kentt��n EDI tiedostossa
  // 5 = Rahtivapaus, jos 'E', niin k�ytet��n asiakkaan 'rahtivapaa' -oletusta
  // 6 = Tyhjennet��nk� OSTOTIL.OT_MAKSETTU EDI tiedostossa (tyhj� ei, kaikki muut arvot kyll�)
  // 7 = Vaihtoehtoinen asiakasnro
  $verkkokauppa_erikoiskasittely = array(
    // array('Suomi',  'K', '2', '100', '',      'E', ''     ),
    // array('Ruotsi', 'E', 'E', '',    'SE123', '',  'kylla'),
    // array('Norja', '', '', '',    '', '',  '', '123'),
  );
}

// Korvaavia Maksuehtoja Magenton maksuehdoille
if (empty($magento_maksuehto_ohjaus)) {
  $magento_maksuehto_ohjaus = array();
}

// Mille yritykselle tilaukset luetaan Pupesoftissa sis��n
if (empty($ovt_tunnus)) {
  $ovt_tunnus = $yhtiorow['ovttunnus'];
}

// Mik� on luodussa EDI-tilauksessa tilaustyyppi
if (empty($pupesoft_tilaustyyppi)) {
  $pupesoft_tilaustyyppi = '';
}

// Mit� tuotetta k�ytet��n rahtikuluna
if (empty($rahtikulu_tuoteno)) {
  $rahtikulu_tuoteno = $yhtiorow['rahti_tuotenumero'];
}

// Mik� nimitys laitetaan rahtikululle Pupesoftin tilaukselle
if (empty($rahtikulu_nimitys)) {
  $rahtikulu_nimitys = 'L�hetyskulut';
}

// Lis�t��nk� lapsituotteen nimitykseen parametrien arvot
// Esim. nimitys: Kettlebell, parametri_paino: 10 kg --> nimitys: Kettlebell - 10 kg
if (empty($magento_nimitykseen_parametrien_arvot)) {
  $magento_nimitykseen_parametrien_arvot = false;
}

// Mik� on verkkokauppa -asiakkaan ovttunnus/ytunnus/asiakasnumero Pupesoftissa
// Pakollinen tieto
if (empty($verkkokauppa_asiakasnro)) {
  $verkkokauppa_asiakasnro = null;
}

// Estet��nk� asiakkaiden p�ivitys
if (empty($magento_asiakaspaivitysesto)) {
  $magento_asiakaspaivitysesto = 'NO';
}

// Vaihtoehtoinen varastosaldo Magenton tuotekentt��n
// Array jossa avaimena Magenton tuotekent�n nimi, arvona Array jossa varastojen tunnukset
if (empty($magento_saldot_tuotekenttaan)) {
  $magento_saldot_tuotekenttaan = array(
    // 'hki_myymala' => array(117,118),
    // 'tku_myymala' => array(10),
  );
}

// N�ytet��nk� Magentossa tuotteet aina tilassa "varastossa" saldosta riippumatta
if (empty($magento_tuote_aina_varastossa)) {
  $magento_tuote_aina_varastossa = false;
}

if (empty($magento_tehdas_saldot)) {
  $magento_tehdas_saldot = false;
}

// Tehd��n lukkofile riippuen siit�, mit� ajetaan. Tilauksien haulla pit�� olla oma lukko.
if (count($magento_ajolista) == 1 and $magento_ajolista[0] == 'tilaukset') {
  $lockfile = 'tuote_export-tilaukset-flock.lock';
}
else {
  $lockfile = 'tuote_export-flock.lock';
}

$lock_params = array(
  "lockfile" => $lockfile,
  "locktime" => 5400,
);

// Sallitaan vain yksi instanssi t�st� skriptist� kerrallaan
pupesoft_flock($lock_params);

tuote_export_echo("Aloitetaan Magento-siirto.");

$magento_client = new MagentoClient(
  $magento_api_te_url,
  $magento_api_te_usr,
  $magento_api_te_pas,
  $magento_client_options,
  $magento_debug
);

$magento_client->set_configurable_tuotetiedot($magento_configurable_tuotetiedot);
$magento_client->set_magento_lisaa_tuotekuvat($magento_lisaa_tuotekuvat);
$magento_client->set_magento_nimitykseen_parametrien_arvot($magento_nimitykseen_parametrien_arvot);
$magento_client->set_magento_perusta_disabled($magento_perusta_disabled);
$magento_client->set_magento_simple_tuote_nimityskentta($magento_simple_tuote_nimityskentta);
$magento_client->set_magento_tuote_aina_varastossa($magento_tuote_aina_varastossa);
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

foreach ($verkkokauppatuotteet_erikoisparametrit as $erikoisparametri) {
  $key = $erikoisparametri['nimi'];

  // Kieliversiot
  // poimitaan talteen koska niit� k�ytet��n toisaalla
  if ($key == 'kieliversiot') {
    $tuetut_kieliversiot = $erikoisparametri['arvo'];
    break;
  }
}

$kieliversiot = array("fi"); //oletuskieli

if (isset($tuetut_kieliversiot)) {
  $magento_client->setTuetutKieliversiot($tuetut_kieliversiot);

  // tuotteiden hakuun tehd��n valmis taulukko kielist�

  foreach ($tuetut_kieliversiot as $kieli => $bar) {
    $kieliversiot[] = $kieli;
  }
}

if (in_array('tuotteet', $magento_ajolista)) {
  tuote_export_echo("Haetaan tuotetiedot.");

  $params = array(
    "ajetaanko_kaikki"                     => $ajetaanko_kaikki,
    "kieliversiot"                         => $kieliversiot, //parametrien muut kielik��nn�kset, oletus fi haetaan aina
    "magento_asiakaskohtaiset_tuotehinnat" => $magento_asiakaskohtaiset_tuotehinnat,
    "tuotteiden_asiakashinnat_magentoon"   => $tuotteiden_asiakashinnat_magentoon,
  );

  $dnstuote = tuote_export_hae_tuotetiedot($params);

  // Magentoa varten pit�� hakea kaikki tuotteet, jotta voidaan poistaa ne jota ei ole olemassa
  tuote_export_echo("Haetaan poistettavat tuotteet.");

  $response = tuote_export_hae_poistettavat_tuotteet();
  $kaikki_tuotteet     = $response['kaikki'];
  $individual_tuotteet = $response['individual'];

  tuote_export_echo("P�ivitet��n simple tuotteet");
  $magento_client->lisaa_simple_tuotteet($dnstuote, $individual_tuotteet);

  tuote_export_echo("Poistetaan ylim��r�iset tuotteet");
  $magento_client->poista_poistetut($kaikki_tuotteet, true);
}

if (in_array('saldot', $magento_ajolista)) {
  tuote_export_echo("Haetaan saldot.");

  $params = array(
    "ajetaanko_kaikki"           => $ajetaanko_kaikki,
    "verkkokauppa_saldo_varasto" => $verkkokauppa_saldo_varasto,
    "vaihtoehtoiset_saldot"      => $magento_saldot_tuotekenttaan,
    "tehdas_saldot"              => $magento_tehdas_saldot,
  );

  $dnstock = tuote_export_hae_saldot($params);

  // Saldot
  tuote_export_echo("P�ivitet��n tuotteiden saldot");
  $magento_client->paivita_saldot($dnstock);
}

if (in_array('tuoteryhmat', $magento_ajolista)) {
  tuote_export_echo("Haetaan osastot/tuoteryhm�t.");

  $params = array(
    "ajetaanko_kaikki" => $ajetaanko_kaikki,
  );

  $dnstuoteryhma = tuote_export_hae_tuoteryhmat($params);

  tuote_export_echo("P�ivitet��n tuotekategoriat");
  $magento_client->lisaa_kategoriat($dnstuoteryhma);
}

if (in_array('asiakkaat', $magento_ajolista)) {
  tuote_export_echo("Haetaan asiakkaat.");

  $params = array(
    "ajetaanko_kaikki"    => $ajetaanko_kaikki,
    "magento_website_id"  => $magento_website_id,
  );

  $dnsasiakas = tuote_export_hae_asiakkaat($params);

  tuote_export_echo("P�ivitet��n asiakkaat");
  $magento_client->setAsiakasPaivitysEsto($magento_asiakaspaivitysesto);
  $magento_client->lisaa_asiakkaat($dnsasiakas);
}

if (in_array('lajitelmatuotteet', $magento_ajolista)) {
  tuote_export_echo("Haetaan tuotteiden variaatiot.");

  $params = array(
    "ajetaanko_kaikki" => $ajetaanko_kaikki,
    "kieliversiot" => $kieliversiot, //parametrien muut kielik��nn�kset, oletus fi haetaan aina
  );

  $dnslajitelma = tuote_export_hae_lajitelmatuotteet($params);

  // Tuotteet (Configurable)
  tuote_export_echo("P�ivitet��n configurable tuotteet");
  $magento_client->lisaa_configurable_tuotteet($dnslajitelma);
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
  $magento_tilaus_client->set_verollisen_hinnan_kentta($verkkokauppa_verollisen_hinnan_kentta);
  $magento_tilaus_client->setSisaanluvunEsto($magento_sisaanluvun_esto);

  $magento_tilaus_client->tallenna_tilaukset();
}

tuote_export_echo("Magento-siirto valmis.");
