<?php

/*
 * Siirret‰‰n toimittajatiedot
*/

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
$useslave = 1;

// Kutsutaanko CLI:st‰
if (php_sapi_name() != 'cli') {
  die ("T‰t‰ scripti‰ voi ajaa vain komentorivilt‰!");
}

if (!isset($argv[1]) or $argv[1] == '') {
  die("Yhtiˆ on annettava!!");
}

$scp_siirto = "";

if (!empty($argv[2])) {
  $scp_siirto = $argv[2];
}

ini_set("memory_limit", "5G");

// Otetaan includepath aina rootista
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))));

require 'inc/connect.inc';
require 'inc/functions.inc';

// Logitetaan ajo
cron_log();

// Yhtiˆ
$yhtio = mysql_real_escape_string($argv[1]);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

// Tallennetaan rivit tiedostoon
$filepath = "/tmp/suppliers_{$yhtio}_".date("Y-m-d").".csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus ep‰onnistui: $filepath\n");
}

// Otsikkotieto
$header  = "Name;";
$header .= "Name, Line 2;";
$header .= "Address;";
$header .= "Address Line 2;";
$header .= "Postal Code;";
$header .= "City;";
$header .= "Country;";
$header .= "Phone;";
$header .= "Fax;";
$header .= "Email;";
$header .= "Delivery;";
$header .= "Terms Of Delivery;";
$header .= "Carrier;";
$header .= "Payment Text;";
$header .= "Distribution;";
$header .= "Contact Person;";
$header .= "Language;";
$header .= "Fact;";
$header .= "Poikkeava Toimittajan Nimi Maksuaineistoon;";
$header .= "Account Number (Domestic);";
$header .= "Foreign Bank contact details 1;";
$header .= "Foreign Bank contact details 2;";
$header .= "Foreign Bank contact details 3;";
$header .= "Foreign Bank contact details 4;";
$header .= "Payment instructions (Foreign);";
$header .= "Bank contact country;";
$header .= "IBAN;";
$header .= "SWIFT/BIC;";
$header .= "Clearing;";
$header .= "Currency;";
$header .= "Due Date (days);";
$header .= "Delivery time (days);";
$header .= "Order interval (days);";
$header .= "Direct Debit;";
$header .= "Bank Account For Direct Debit;";
$header .= "Cash Discount Date (days);";
$header .= "Cash Discount %;";
$header .= "Acceptor 1;";
$header .= "Acceptor 2;";
$header .= "Acceptor 3;";
$header .= "Acceptor 4;";
$header .= "Acceptor 5;";
$header .= "May The Acceptor List Be Edited?;";
$header .= "Invoice Type;";
$header .= "Shipping Country;";
$header .= "Purchase Type (Import);";
$header .= "Transport Method;";
$header .= "Expense %;";
$header .= "Add expense % to purchase price;";
$header .= "Edi Server;";
$header .= "Edi User;";
$header .= "Edi Password;";
$header .= "Edi Description;";
$header .= "Edi Path;";
$header .= "ASN-message type;";
$header .= "Own corporation;";
$header .= "Account (Bookkeeping);";
$header .= "Cost Center (Bookkeeping);";
$header .= "Profit Center (Bookkeeping);";
$header .= "Project (Bookkeeping);";
$header .= "VAT Account;";
$header .= "Foreign suppliers VAT duty;";
$header .= "Search By;";
$header .= "VAT Nr;";
$header .= "OVT Code;";
$header .= "Supplier number;";
$header .= "Payment forbiddance;";
$header .= "Invoice Specification;";
$header .= "Supplier Type;";
$header .= "Supplier Type, Specification;";
$header .= "Allow quick product creation;";
$header .= "Purchase price update interval (days);";
$header .= "Direct Delivery;";
$header .= "Supplier inventory check;";
$header .= "Automatic inventory arrivals;";
$header .= "Ostotilauksen_kasittely;";
$header .= "Created By;";
$header .= "Date Created;";
$header .= "Edit Date;";
$header .= "Updated By;";
$header .= "Herminator";
$header .= "\n";
$header .= "Nimi;";
$header .= "Nimitarkenne;";
$header .= "Osoite;";
$header .= "Osoitteen Tarkenne;";
$header .= "Postinumero;";
$header .= "Postitoimipaikka;";
$header .= "Maa;";
$header .= "Puhelin;";
$header .= "Fax;";
$header .= "S‰hkˆpostiosoite;";
$header .= "Kuljetus (Ostotilaus);";
$header .= "Toimitusehto (Ostotilaus);";
$header .= "Huolitsija (Ostotilaus);";
$header .= "Maksuteksti (Ostotilaus);";
$header .= "Jakelu (Ostotilaus);";
$header .= "Yhteyshenkilˆ;";
$header .= "Kieli;";
$header .= "Fakta;";
$header .= "Poikkeava Toimittajan Nimi Maksuaineistoon;";
$header .= "Tilinumero (Kotimaa);";
$header .= "Saajan Pankin Nimi Ja Paikkakunta 1 (Ulkomaa);";
$header .= "Saajan Pankin Nimi Ja Paikkakunta 2 (Ulkomaa);";
$header .= "Saajan Pankin Nimi Ja Paikkakunta 3 (Ulkomaa);";
$header .= "Saajan Pankin Nimi Ja Paikkakunta 4 (Ulkomaa);";
$header .= "Ohjeita Pankille, Maksuaineiston Lis‰tietue (Ulkomaa);";
$header .= "Tilinumeron Poikkeava Maa;";
$header .= "IBAN / Tilinumero (Ulkomaa);";
$header .= "SWIFT / BIC;";
$header .= "Clearing;";
$header .= "Valuutta;";
$header .= "Er‰p‰iv‰;";
$header .= "Toimitusaika;";
$header .= "Tilausv‰li;";
$header .= "Suoraveloitus;";
$header .= "Suoraveloituksen Pankkitili;";
$header .= "Kassa-Alennusp‰iv‰;";
$header .= "Kassa-Alennusprosentti;";
$header .= "Hyv‰ksyj‰ 1;";
$header .= "Hyv‰ksyj‰ 2;";
$header .= "Hyv‰ksyj‰ 3;";
$header .= "Hyv‰ksyj‰ 4;";
$header .= "Hyv‰ksyj‰ 5;";
$header .= "Sallitaanko Hyv‰ksynt‰ketjun Muutos;";
$header .= "Laskun Tyyppi;";
$header .= "Tavaran L‰hetysmaa;";
$header .= "Kauppatapahtuman Luonne;";
$header .= "Kuljetusmuoto;";
$header .= "Kuluprosentti;";
$header .= "Kulujen_laskeminen_hintoihin;";
$header .= "Edi-Palvelin (Ostotilaus);";
$header .= "Edi-Kayttaja (Ostotilaus);";
$header .= "Edi-Salasana (Ostotilaus);";
$header .= "Edi-Kuvaus (Ostotilaus);";
$header .= "Edi-Polku (Ostotilaus);";
$header .= "ASN-Sanomatyyppi;";
$header .= "Toimittaja Kuuluu Omaan Konserniimme;";
$header .= "Tili (Kirjanpito);";
$header .= "Kustannuspaikka (Kirjanpito);";
$header .= "Kohde (Kirjanpito);";
$header .= "Projekti (Kirjanpito);";
$header .= "ALV-Tili;";
$header .= "Ulkomaisen Toimittajan Verovelvollisuus;";
$header .= "Selausnimi;";
$header .= "Y-Tunnus;";
$header .= "OVT-Tunnus;";
$header .= "Toimittajanumero;";
$header .= "Maksukielto;";
$header .= "Laskun Erittely;";
$header .= "Toimittajatyyppi;";
$header .= "Toimittajatyypin Lis‰teito;";
$header .= "Pikaperustus;";
$header .= "Ostohintojen P‰ivitysv‰li;";
$header .= "Suoratoimitus;";
$header .= "Tehdas_saldo_tarkistus;";
$header .= "Sahkoinen_automaattituloutus;";
$header .= "Ostotilauksen_kasittely;";
$header .= "Laatija ouki;";
$header .= "Luontiaika;";
$header .= "Muutospvm;";
$header .= "Muuttaja;";
$header .= "Herminator;";
$header .= "\n";

fwrite($fp, $header);

// Haetaan asiakkaat
$query = "SELECT toimi.*,
          k1.nimi kustannuspaikka,
          k2.nimi kohde,
          k3.nimi projekti
          FROM toimi
          LEFT JOIN kustannuspaikka as k1 ON (k1.yhtio = toimi.yhtio AND k1.tunnus = toimi.kustannuspaikka)
          LEFT JOIN kustannuspaikka as k2 ON (k2.yhtio = toimi.yhtio AND k2.tunnus = toimi.kohde)
          LEFT JOIN kustannuspaikka as k3 ON (k3.yhtio = toimi.yhtio AND k3.tunnus = toimi.projekti)
          WHERE toimi.yhtio = '{$yhtio}'
          AND toimi.tyyppi != 'P'
          ORDER BY toimi.tunnus";
$res = pupe_query($query);

// Kerrotaan montako rivi‰ k‰sitell‰‰n
$rows = mysql_num_rows($res);

echo "Toimittajarivej‰ {$rows} kappaletta.\n";

$k_rivi = 0;

$viennit = array(
  ""  => "",
  "A" => "Kotimaa",
  "B" => "Kotimaa huolinta/rahti",
  "C" => "Kotimaa vaihto-omaisuus",
  "J" => "Kotimaa raaka-aine",
  "D" => "EU",
  "E" => "EU huolinta/rahti",
  "F" => "EU vaihto-omaisuus",
  "K" => "EU raaka-aine",
  "G" => "ei-EU",
  "H" => "ei-EU huolinta/rahti",
  "I" => "ei-EU vaihto-omaisuus",
  "L" => "ei-EU raaka-aine",
);

while ($row = mysql_fetch_assoc($res)) {

  $rivi  = pupesoft_csvstring($row["nimi"]).";";
  $rivi .= pupesoft_csvstring($row["nimitark"]).";";
  $rivi .= pupesoft_csvstring($row["osoite"]).";";
  $rivi .= pupesoft_csvstring($row["osoitetark"]).";";
  $rivi .= pupesoft_csvstring($row["postino"]).";";
  $rivi .= pupesoft_csvstring($row["postitp"]).";";
  $rivi .= pupesoft_csvstring($row["maa"]).";";
  $rivi .= pupesoft_csvstring($row["puhelin"]).";";
  $rivi .= pupesoft_csvstring($row["fax"]).";";
  $rivi .= pupesoft_csvstring($row["email"]).";";
  $rivi .= pupesoft_csvstring($row["kuljetus"]).";";
  $rivi .= pupesoft_csvstring($row["toimitusehto"]).";";
  $rivi .= pupesoft_csvstring($row["huolitsija"]).";";
  $rivi .= pupesoft_csvstring($row["maksuteksti"]).";";
  $rivi .= pupesoft_csvstring($row["jakelu"]).";";
  $rivi .= pupesoft_csvstring($row["yhteyshenkilo"]).";";
  $rivi .= pupesoft_csvstring($row["kieli"]).";";
  $rivi .= pupesoft_csvstring($row["fakta"]).";";
  $rivi .= pupesoft_csvstring($row["pankki_haltija"]).";";
  $rivi .= pupesoft_csvstring($row["tilinumero"]).";";
  $rivi .= pupesoft_csvstring($row["pankki1"]).";";
  $rivi .= pupesoft_csvstring($row["pankki2"]).";";
  $rivi .= pupesoft_csvstring($row["pankki3"]).";";
  $rivi .= pupesoft_csvstring($row["pankki4"]).";";
  $rivi .= pupesoft_csvstring($row["ohjeitapankille"]).";";
  $rivi .= pupesoft_csvstring($row["ultilno_maa"]).";";
  $rivi .= pupesoft_csvstring($row["ultilno"]).";";
  $rivi .= pupesoft_csvstring($row["swift"]).";";
  $rivi .= pupesoft_csvstring($row["clearing"]).";";
  $rivi .= pupesoft_csvstring($row["oletus_valkoodi"]).";";
  $rivi .= pupesoft_csvstring($row["oletus_erapvm"]).";";
  $rivi .= pupesoft_csvstring($row["oletus_toimaika"]).";";
  $rivi .= pupesoft_csvstring($row["oletus_tilausvali"]).";";
  $rivi .= pupesoft_csvstring($row["oletus_suoraveloitus"]).";";
  $rivi .= pupesoft_csvstring($row["oletus_suoravel_pankki"]).";";
  $rivi .= pupesoft_csvstring($row["oletus_kapvm"]).";";
  $rivi .= pupesoft_csvstring($row["oletus_kapro"]).";";
  $rivi .= pupesoft_csvstring($row["oletus_hyvak1"]).";";
  $rivi .= pupesoft_csvstring($row["oletus_hyvak2"]).";";
  $rivi .= pupesoft_csvstring($row["oletus_hyvak3"]).";";
  $rivi .= pupesoft_csvstring($row["oletus_hyvak4"]).";";
  $rivi .= pupesoft_csvstring($row["oletus_hyvak5"]).";";

  $hmuutos = "NO";

  if (!empty($row["oletus_hyvaksynnanmuutos"])) {
    $hmuutos = "YES";
  }

  $rivi .= pupesoft_csvstring($hmuutos).";";
  $rivi .= pupesoft_csvstring($viennit[$row["oletus_vienti"]]).";";
  $rivi .= pupesoft_csvstring($row["maa_lahetys"]).";";
  $rivi .= pupesoft_csvstring($row["kauppatapahtuman_luonne"]).";";
  $rivi .= pupesoft_csvstring($row["kuljetusmuoto"]).";";
  $rivi .= pupesoft_csvstring($row["oletus_kulupros"]).";";
  $rivi .= pupesoft_csvstring($row["kulujen_laskeminen_hintoihin"]).";";
  $rivi .= pupesoft_csvstring($row["edi_palvelin"]).";";
  $rivi .= pupesoft_csvstring($row["edi_kayttaja"]).";";
  $rivi .= pupesoft_csvstring($row["edi_salasana"]).";";
  $rivi .= pupesoft_csvstring($row["edi_kuvaus"]).";";
  $rivi .= pupesoft_csvstring($row["edi_polku"]).";";
  $rivi .= pupesoft_csvstring($row["asn_sanomat"]).";";
  $rivi .= pupesoft_csvstring($row["konserniyhtio"]).";";
  $rivi .= pupesoft_csvstring($row["tilino"]).";";
  $rivi .= pupesoft_csvstring($row["kustannuspaikka"]).";";
  $rivi .= pupesoft_csvstring($row["kohde"]).";";
  $rivi .= pupesoft_csvstring($row["projekti"]).";";
  $rivi .= pupesoft_csvstring($row["tilino_alv"]).";";
  $rivi .= pupesoft_csvstring($row["verovelvollinen"]).";";
  $rivi .= pupesoft_csvstring($row["selaus"]).";";
  $rivi .= pupesoft_csvstring($row["ytunnus"]).";";
  $rivi .= pupesoft_csvstring($row["ovttunnus"]).";";
  $rivi .= pupesoft_csvstring($row["toimittajanro"]).";";
  $rivi .= pupesoft_csvstring($row["maksukielto"]).";";
  $rivi .= pupesoft_csvstring($row["laskun_erittely"]).";";
  $rivi .= pupesoft_csvstring($row["tyyppi"]).";";
  $rivi .= pupesoft_csvstring($row["tyyppi_tieto"]).";";
  $rivi .= pupesoft_csvstring($row["pikaperustus"]).";";
  $rivi .= pupesoft_csvstring($row["hintojenpaivityssykli"]).";";
  $rivi .= pupesoft_csvstring($row["suoratoimitus"]).";";
  $rivi .= pupesoft_csvstring($row["tehdas_saldo_tarkistus"]).";";
  $rivi .= pupesoft_csvstring($row["sahkoinen_automaattituloutus"]).";";
  $rivi .= pupesoft_csvstring($row["ostotilauksen_kasittely"]).";";
  $rivi .= pupesoft_csvstring($row["laatija"]).";";
  $rivi .= pupesoft_csvstring($row["luontiaika"]).";";
  $rivi .= pupesoft_csvstring($row["muutospvm"]).";";
  $rivi .= pupesoft_csvstring($row["muuttaja"]).";";
  $rivi .= pupesoft_csvstring($row["herminator"])."";
  $rivi .= "\n";

  fwrite($fp, $rivi);

  $k_rivi++;

  if ($k_rivi % 1000 == 0) {
    echo "K‰sitell‰‰n rivi‰ {$k_rivi}\n";
  }
}

fclose($fp);

if (!empty($scp_siirto)) {
  // Siirret‰‰n toiselle palvelimelle
  system("scp {$filepath} $scp_siirto");
}

echo "Valmis.\n";
