<?php

/*
 * Siirretään asiakastiedot
*/

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

if (!isset($argv[1]) or $argv[1] == '') {
  die("Yhtiö on annettava!!");
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

// Yhtiö
$yhtio = mysql_real_escape_string($argv[1]);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

// Tallennetaan rivit tiedostoon
$filepath = "/tmp/customers_{$yhtio}_".date("Y-m-d").".csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus epäonnistui: $filepath\n");
}

// Otsikkotieto
$header  = "Customer Type;";
$header .= "Status;";
$header .= "VAT Nr;";
$header .= "OVT-Code;";
$header .= "Name;";
$header .= "Name, Line 2;";
$header .= "Street Address;";
$header .= "Postal Code;";
$header .= "City;";
$header .= "Location;";
$header .= "Municipality;";
$header .= "County;";
$header .= "Country;";
$header .= "Nationality;";
$header .= "Employer;";
$header .= "Title;";
$header .= "Email;";
$header .= "Email (Invoicing);";
$header .= "Email (Economics);";
$header .= "Telephone;";
$header .= "Mobile;";
$header .= "Work Phonenr;";
$header .= "Fax;";
$header .= "Delivery address OVT-number;";
$header .= "Delivery Name;";
$header .= "Delivery Name2;";
$header .= "Delivery Address;";
$header .= "Delivery Post Code;";
$header .= "Delivery City;";
$header .= "Delivery Country;";
$header .= "Customer Name Invoicing Address;";
$header .= "Customer Name Invoicing Address 2;";
$header .= "Invoicing Address;";
$header .= "Invoicing Address Postal Code;";
$header .= "Invoicing Address City;";
$header .= "Invoicing Country;";
$header .= "Maksukehotuksen Osoitetiedot;";
$header .= "Customer Group Company;";
$header .= "Customer Number;";
$header .= "Gegraphical Region;";
$header .= "Customer Group;";
$header .= "Customer Segment;";
$header .= "EInvoice Nr;";
$header .= "Language;";
$header .= "Invoice channeling;";
$header .= "Own corporation;";
$header .= "Customer Detail (Int);";
$header .= "Comment 1 (Tilausvahvistus + Keräyslista + Valmistus + Työmääräys + Lähete);";
$header .= "Comment 2 (Keräyslista + Valmistus);";
$header .= "Comment 3 (Invoice);";
$header .= "Reference (Tilausvahvistus + Keräyslista + Valmistus + Työmääräys + Lähete + Lasku + Tarjous);";
$header .= "Delivery Instruction;";
$header .= "Search By;";
$header .= "VAT Handling;";
$header .= "Currency;";
$header .= "Terms Of Payment;";
$header .= "Freight Method;";
$header .= "Free Of Carriage;";
$header .= "Min Value For Freight-Free;";
$header .= "Kuljetusvakuutus;";
$header .= "Delivery Term;";
$header .= "Order Confirmation;";
$header .= "Back Order Confirmation;";
$header .= "JT-Rivien Toimitusajan Vahvistussähköposti;";
$header .= "Delivery Confirmation;";
$header .= "Picking Anomality Message;";
$header .= "Electronic Packing Slip;";
$header .= "Electronic Packing Slip email address;";
$header .= "Packing Slip Type;";
$header .= "Orderline Sequence On Packing Slip;";
$header .= "Orderline Sequence On Packing Slip;";
$header .= "Invoice Type;";
$header .= "Invoice Weekday;";
$header .= "Orderline Sequence On Invoice;";
$header .= "Orderline Sequence On Invoice;";
$header .= "Export;";
$header .= "Order Chaining;";
$header .= "Group invoice type;";
$header .= "Priority;";
$header .= "B.O.-Handling;";
$header .= "Näytetäänkö JT-Rivit Myyjälle;";
$header .= "Salesrep Nr;";
$header .= "Special Discount;";
$header .= "Sales prohibited;";
$header .= "Show in sales report;";
$header .= "Limit Value Credit control;";
$header .= "Default Expense%;";
$header .= "Hour price;";
$header .= "Hour multiplier;";
$header .= "Multiplier;";
$header .= "Small article surcharge;";
$header .= "Fair Out;";
$header .= "Billing Fee;";
$header .= "Deposit;";
$header .= "Account, Sales (Bookkeeping);";
$header .= "Account, EU Sales (Bookkeeping);";
$header .= "Account, Export Sales (Bookkeeping);";
$header .= "Account reverse VAT (Bookkeeping);";
$header .= "Account Used Procucts, Sales (Bookkeeping);";
$header .= "Account Used Procucts, Purchase (Bookkeeping);";
$header .= "Account triangulation (Bookkeeping);";
$header .= "Cost Center (Bookkeeping);";
$header .= "Profit Center (Bookkeeping);";
$header .= "Project (Bookkeeping);";
$header .= "Created By;";
$header .= "Created;";
$header .= "Time Of Change;";
$header .= "Updated By;";
$header .= "Edit 1;";
$header .= "Edit 2;";
$header .= "Edit 3;";
$header .= "Edit 4;";
$header .= "Default Destination Country;";
$header .= "Default Inland Shipping;";
$header .= "Default Inland Shipment Nationality;";
$header .= "Default Shipping Method Inland;";
$header .= "Default Container;";
$header .= "Default Active Transport;";
$header .= "Default Active Transport Nationality;";
$header .= "Default Sales type;";
$header .= "Default Means Of Transport;";
$header .= "Customs;";
$header .= "Herminator;";
$header .= "Herminator1;";
$header .= "Herminator2;";
$header .= "Herminator3;";
$header .= "Herminator4;";
$header .= "Id";
$header .= "\n";
$header .= "Laji;";
$header .= "Tila;";
$header .= "Ytunnus;";
$header .= "Ovttunnus;";
$header .= "Nimi;";
$header .= "Nimitark;";
$header .= "Osoite;";
$header .= "Postino;";
$header .= "Postitp;";
$header .= "Toimipaikka;";
$header .= "Kunta;";
$header .= "Laani;";
$header .= "Maa;";
$header .= "Kansalaisuus;";
$header .= "Tyonantaja;";
$header .= "Ammatti;";
$header .= "Email;";
$header .= "Lasku_email;";
$header .= "Talhal_email;";
$header .= "Puhelin;";
$header .= "Gsm;";
$header .= "Tyopuhelin;";
$header .= "Fax;";
$header .= "Toim_ovttunnus;";
$header .= "Toim_nimi;";
$header .= "Toim_nimitark;";
$header .= "Toim_osoite;";
$header .= "Toim_postino;";
$header .= "Toim_postitp;";
$header .= "Toim_maa;";
$header .= "Laskutus_nimi;";
$header .= "Laskutus_nimitark;";
$header .= "Laskutus_osoite;";
$header .= "Laskutus_postino;";
$header .= "Laskutus_postitp;";
$header .= "Laskutus_maa;";
$header .= "Maksukehotuksen_osoitetiedot;";
$header .= "Konserni;";
$header .= "Asiakasnro;";
$header .= "Piiri;";
$header .= "Ryhma;";
$header .= "Osasto;";
$header .= "Verkkotunnus;";
$header .= "Kieli;";
$header .= "Chn;";
$header .= "Konserniyhtio;";
$header .= "Fakta;";
$header .= "Myynti_kommentti1;";
$header .= "Sisviesti2;";
$header .= "Sisviesti1;";
$header .= "Tilaus_viesti;";
$header .= "Kuljetusohje;";
$header .= "Selaus;";
$header .= "Alv;";
$header .= "Valkoodi;";
$header .= "Maksuehto;";
$header .= "Toimitustapa;";
$header .= "Rahtivapaa;";
$header .= "Rahtivapaa_alarajasumma;";
$header .= "Kuljetusvakuutus_tyyppi;";
$header .= "Toimitusehto;";
$header .= "Tilausvahvistus;";
$header .= "Tilausvahvistus_jttoimituksista;";
$header .= "Jt_toimitusaika_email_vahvistus;";
$header .= "Toimitusvahvistus;";
$header .= "Kerayspoikkeama;";
$header .= "Keraysvahvistus_lahetys;";
$header .= "Keraysvahvistus_email;";
$header .= "Lahetetyyppi;";
$header .= "Lahetteen_jarjestys;";
$header .= "Lahetteen_jarjestys_suunta;";
$header .= "Laskutyyppi;";
$header .= "Laskutusvkopv;";
$header .= "Laskun_jarjestys;";
$header .= "Laskun_jarjestys_suunta;";
$header .= "Vienti;";
$header .= "Ketjutus;";
$header .= "Koontilaskut_yhdistetaan;";
$header .= "Luokka;";
$header .= "Jtkielto;";
$header .= "Jtrivit;";
$header .= "Myyjanro;";
$header .= "Erikoisale;";
$header .= "Myyntikielto;";
$header .= "Myynninseuranta;";
$header .= "Luottoraja;";
$header .= "Kuluprosentti;";
$header .= "Tuntihinta;";
$header .= "Tuntikerroin;";
$header .= "Hintakerroin;";
$header .= "Pientarvikelisa;";
$header .= "Laskunsummapyoristys;";
$header .= "Laskutuslisa;";
$header .= "Panttitili;";
$header .= "Tilino;";
$header .= "Tilino_eu;";
$header .= "Tilino_ei_eu;";
$header .= "Tilino_kaanteinen;";
$header .= "Tilino_marginaali;";
$header .= "Tilino_osto_marginaali;";
$header .= "Tilino_triang;";
$header .= "Kustannuspaikka;";
$header .= "Kohde;";
$header .= "Projekti;";
$header .= "Laatija;";
$header .= "Luontiaika;";
$header .= "Muutospvm;";
$header .= "Muuttaja;";
$header .= "Flag_1;";
$header .= "Flag_2;";
$header .= "Flag_3;";
$header .= "Flag_4;";
$header .= "Maa_maara;";
$header .= "Sisamaan_kuljetus;";
$header .= "Sisamaan_kuljetus_kansallisuus;";
$header .= "Sisamaan_kuljetusmuoto;";
$header .= "Kontti;";
$header .= "Aktiivinen_kuljetus;";
$header .= "Aktiivinen_kuljetus_kansallisuus;";
$header .= "Kauppatapahtuman_luonne;";
$header .= "Kuljetusmuoto;";
$header .= "Poistumistoimipaikka_koodi;";
$header .= "Herminator;";
$header .= "Herminator1;";
$header .= "Herminator2;";
$header .= "Herminator3;";
$header .= "Herminator4;";
$header .= "Tunnus";
$header .= "\n";

fwrite($fp, $header);

// Haetaan asiakkaat
$query = "SELECT *
          FROM asiakas
          WHERE asiakas.yhtio = '{$yhtio}'
          AND asiakas.laji NOT IN ('P','R')
          AND asiakas.myynninseuranta = ''
          ORDER BY asiakas.tunnus";
$res = pupe_query($query);

// Kerrotaan montako riviä käsitellään
$rows = mysql_num_rows($res);

echo "Asiakasrivejä {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysql_fetch_assoc($res)) {

  $rivi  = pupesoft_csvstring($row["laji"]).";";
  $rivi .= pupesoft_csvstring($row["tila"]).";";
  $rivi .= pupesoft_csvstring($row["ytunnus"]).";";
  $rivi .= pupesoft_csvstring($row["ovttunnus"]).";";
  $rivi .= pupesoft_csvstring($row["nimi"]).";";
  $rivi .= pupesoft_csvstring($row["nimitark"]).";";
  $rivi .= pupesoft_csvstring($row["osoite"]).";";
  $rivi .= pupesoft_csvstring($row["postino"]).";";
  $rivi .= pupesoft_csvstring($row["postitp"]).";";
  $rivi .= pupesoft_csvstring($row["toimipaikka"]).";";
  $rivi .= pupesoft_csvstring($row["kunta"]).";";
  $rivi .= pupesoft_csvstring($row["laani"]).";";
  $rivi .= pupesoft_csvstring($row["maa"]).";";
  $rivi .= pupesoft_csvstring($row["kansalaisuus"]).";";
  $rivi .= pupesoft_csvstring($row["tyonantaja"]).";";
  $rivi .= pupesoft_csvstring($row["ammatti"]).";";
  $rivi .= pupesoft_csvstring($row["email"]).";";
  $rivi .= pupesoft_csvstring($row["lasku_email"]).";";
  $rivi .= pupesoft_csvstring($row["talhal_email"]).";";
  $rivi .= pupesoft_csvstring($row["puhelin"]).";";
  $rivi .= pupesoft_csvstring($row["gsm"]).";";
  $rivi .= pupesoft_csvstring($row["tyopuhelin"]).";";
  $rivi .= pupesoft_csvstring($row["fax"]).";";
  $rivi .= pupesoft_csvstring($row["toim_ovttunnus"]).";";
  $rivi .= pupesoft_csvstring($row["toim_nimi"]).";";
  $rivi .= pupesoft_csvstring($row["toim_nimitark"]).";";
  $rivi .= pupesoft_csvstring($row["toim_osoite"]).";";
  $rivi .= pupesoft_csvstring($row["toim_postino"]).";";
  $rivi .= pupesoft_csvstring($row["toim_postitp"]).";";
  $rivi .= pupesoft_csvstring($row["toim_maa"]).";";
  $rivi .= pupesoft_csvstring($row["laskutus_nimi"]).";";
  $rivi .= pupesoft_csvstring($row["laskutus_nimitark"]).";";
  $rivi .= pupesoft_csvstring($row["laskutus_osoite"]).";";
  $rivi .= pupesoft_csvstring($row["laskutus_postino"]).";";
  $rivi .= pupesoft_csvstring($row["laskutus_postitp"]).";";
  $rivi .= pupesoft_csvstring($row["laskutus_maa"]).";";

  if ($row["maksukehotuksen_osoitetiedot"] == "A") {
    $rivi .= pupesoft_csvstring($row["maksukehotuksen_osoitetiedot"])." - Käytetään asiakkaan virallista osoitetta;";
  }
  elseif ($row["maksukehotuksen_osoitetiedot"] == "B") {
    $rivi .= pupesoft_csvstring($row["maksukehotuksen_osoitetiedot"])." - Käytetään asiakkaan laskutusosoitetta;";
  }
  else {
    $rivi .= pupesoft_csvstring($row["maksukehotuksen_osoitetiedot"]).";";
  }

  $rivi .= pupesoft_csvstring($row["konserni"]).";";
  $rivi .= pupesoft_csvstring($row["asiakasnro"]).";";

  $osre = t_avainsana("PIIRI", "", "and avainsana.selite  = '{$row['piiri']}'");
  $osrow = mysql_fetch_assoc($osre);
  $rivi .= pupesoft_csvstring($row['piiri']." - ".$osrow['selitetark']).";";

  $osre = t_avainsana("ASIAKASRYHMA", "", "and avainsana.selite  = '{$row['ryhma']}'");
  $osrow = mysql_fetch_assoc($osre);
  $rivi .= pupesoft_csvstring($row['ryhma']." - ".$osrow['selitetark']).";";

  $osre = t_avainsana("ASIAKASOSASTO", "", "and avainsana.selite  = '{$row['osasto']}'");
  $osrow = mysql_fetch_assoc($osre);
  $rivi .= pupesoft_csvstring($row['osasto']." - ".$osrow['selitetark']).";";

  $rivi .= pupesoft_csvstring($row["verkkotunnus"]).";";
  $rivi .= pupesoft_csvstring($row["kieli"]).";";

  $chn = hae_chn_teksti($row["chn"]);
  $rivi .= pupesoft_csvstring($row["chn"]." - ".$chn).";";

  $rivi .= pupesoft_csvstring($row["konserniyhtio"]).";";
  $rivi .= pupesoft_csvstring($row["fakta"]).";";
  $rivi .= pupesoft_csvstring($row["myynti_kommentti1"]).";";
  $rivi .= pupesoft_csvstring($row["sisviesti2"]).";";
  $rivi .= pupesoft_csvstring($row["sisviesti1"]).";";
  $rivi .= pupesoft_csvstring($row["tilaus_viesti"]).";";
  $rivi .= pupesoft_csvstring($row["kuljetusohje"]).";";
  $rivi .= pupesoft_csvstring($row["selaus"]).";";
  $rivi .= pupesoft_csvstring($row["alv"]).";";
  $rivi .= pupesoft_csvstring($row["valkoodi"]).";";

  $query = "SELECT *
            FROM maksuehto
            WHERE tunnus = $row[maksuehto]";
  $masres = pupe_query($query);
  $masrow = mysql_fetch_assoc($masres);

  $rivi .= pupesoft_csvstring($masrow["teksti"]).";";

  $rivi .= pupesoft_csvstring($row["toimitustapa"]).";";

  if (!empty($row["rahtivapaa"])) {
    $row["rahtivapaa"] .= " - Rahtivapaa";
  }

  $rivi .= pupesoft_csvstring($row["rahtivapaa"]).";";
  $rivi .= pupesoft_csvstring($row["rahtivapaa_alarajasumma"]).";";

  if (!empty($row["kuljetusvakuutus_tyyppi"])) {
    $row["kuljetusvakuutus_tyyppi"] .= " - Ei kuljetusvakuutusta";
  }

  $rivi .= pupesoft_csvstring($row["kuljetusvakuutus_tyyppi"]).";";
  $rivi .= pupesoft_csvstring($row["toimitusehto"]).";";

  $osre = t_avainsana("TV", "", "and avainsana.selite  = '{$row['tilausvahvistus']}'");
  $osrow = mysql_fetch_assoc($osre);
  $rivi .= pupesoft_csvstring($row['tilausvahvistus']." - ".$osrow['selitetark']).";";

  $rivi .= pupesoft_csvstring($row["tilausvahvistus_jttoimituksista"]).";";
  $rivi .= pupesoft_csvstring($row["jt_toimitusaika_email_vahvistus"]).";";
  $rivi .= pupesoft_csvstring($row["toimitusvahvistus"]).";";

  if ($row["kerayspoikkeama"] == "0") {
    $rivi .= pupesoft_csvstring("$row[kerayspoikkeama] - Lähetetään asiakkaalle ja myyjälle").";";
  }
  elseif ($row["kerayspoikkeama"] == "1") {
    $rivi .= pupesoft_csvstring("$row[kerayspoikkeama] - Ei lähetetä ollenkaan").";";
  }
  else {
    $rivi .= pupesoft_csvstring("$row[kerayspoikkeama] - Lähetetään vain myyjälle").";";
  }

  if ($row["keraysvahvistus_lahetys"] == "E") {
    $rivi .= pupesoft_csvstring("$row[keraysvahvistus_lahetys] - Keräysvahvistusta/sähköistä lähetettä ei lähetetä").";";
  }
  elseif ($row["keraysvahvistus_lahetys"] == "o") {
    $rivi .= pupesoft_csvstring("$row[keraysvahvistus_lahetys] - Keräysvahvistus/sähköinen lähete lähetetään asiakkaalle jokaisesta toimituksesta erikseen").";";
  }
  else {
    $rivi .= "$row[keraysvahvistus_lahetys];";
  }

  $rivi .= pupesoft_csvstring($row["keraysvahvistus_email"]).";";

  $osre = t_avainsana("LAHETETYYPPI", "", "and avainsana.selite  = '{$row['lahetetyyppi']}'");
  $osrow = mysql_fetch_assoc($osre);
  $rivi .= pupesoft_csvstring($row['lahetetyyppi']." - ".$osrow['selitetark']).";";

  if ($row["lahetteen_jarjestys"] == "0") {
    $rivi .= pupesoft_csvstring("$row[lahetteen_jarjestys] - Varastopaikkajärjestys, tuoteperheet pidetään yhdessä, erikoistuotteet loppuun").";";
  }
  elseif ($row["lahetteen_jarjestys"] == "4") {
    $rivi .= pupesoft_csvstring("$row[lahetteen_jarjestys] - Tuotenumerojärjestys, tuoteperheet pidetään yhdessä, erikoistuotteet loppuun").";";
  }
  else {
    $rivi .= "$row[lahetteen_jarjestys];";
  }

  $rivi .= pupesoft_csvstring($row["lahetteen_jarjestys_suunta"]).";";

  if ($row["laskutyyppi"] == "-9") {
    $rivi .= pupesoft_csvstring("$row[laskutyyppi] - Yhtiön oletus").";";
  }
  elseif ($row["laskutyyppi"] == "99") {
    $rivi .= pupesoft_csvstring("$row[laskutyyppi] - Normaali laskupohja").";";
  }
  else {
    $rivi .= "$row[laskutyyppi];";
  }

  $teksti = "";
  if ($row["laskutusvkopv"] == 0)     $teksti = "Kaikki";
  elseif ($row["laskutusvkopv"] == 2) $teksti = "Maanantai";
  elseif ($row["laskutusvkopv"] == 3) $teksti = "Tiistai";
  elseif ($row["laskutusvkopv"] == 4) $teksti = "Keskiviikko";
  elseif ($row["laskutusvkopv"] == 5) $teksti = "Torstai";
  elseif ($row["laskutusvkopv"] == 6) $teksti = "Perjantai";
  elseif ($row["laskutusvkopv"] == 7) $teksti = "Lauantai";
  elseif ($row["laskutusvkopv"] == 1) $teksti = "Sunnuntai";

  $rivi .= pupesoft_csvstring($row["laskutusvkopv"])." - $teksti;";

  if ($row["laskun_jarjestys"] == "0") {
    $rivi .= pupesoft_csvstring("$row[laskun_jarjestys] - Varastopaikkajärjestys, tuoteperheet pidetään yhdessä, erikoistuotteet loppuun").";";
  }
  elseif ($row["laskun_jarjestys"] == "4") {
    $rivi .= pupesoft_csvstring("$row[laskun_jarjestys] - Tuotenumerojärjestys, tuoteperheet pidetään yhdessä, erikoistuotteet loppuun").";";
  }
  else {
    $rivi .= "$row[laskun_jarjestys];";
  }

  $rivi .= pupesoft_csvstring($row["laskun_jarjestys_suunta"]).";";
  $rivi .= pupesoft_csvstring($row["vienti"]).";";

  if ($row["ketjutus"] == "E") {
    $rivi .= pupesoft_csvstring("$row[ketjutus] - Ei ketjuteta laskuja").";";
  }
  else {
    $rivi .= pupesoft_csvstring("Laskut saa ketjuttaa").";";
  }

  if ($row["koontilaskut_yhdistetaan"] == "A") {
    $rivi .= pupesoft_csvstring("$row[koontilaskut_yhdistetaan] - Reklamaatio-/takuutilauksia ei jaeta omalle laskulle").";";
  }
  elseif ($row["koontilaskut_yhdistetaan"] == "U") {
    $rivi .= pupesoft_csvstring("$row[koontilaskut_yhdistetaan] - Reklamaatio-/takuutilaukset laskutetaan omalla laskulla").";";
  }
  else {
    $rivi .= "$row[koontilaskut_yhdistetaan];";
  }

  $osre = t_avainsana("ASIAKASLUOKKA", "", "and avainsana.selite  = '{$row['luokka']}'");
  $osrow = mysql_fetch_assoc($osre);
  $rivi .= pupesoft_csvstring($row['luokka']." - ".$osrow['selitetark']).";";

  $rivi .= pupesoft_csvstring($row["jtkielto"]).";";
  $rivi .= pupesoft_csvstring($row["jtrivit"]).";";

  $query = "SELECT nimi
            FROM kuka
            WHERE yhtio = '{$yhtio}'
            AND myyja = '$row[myyjanro]'";
  $masres = pupe_query($query);
  $masrow = mysql_fetch_assoc($masres);

  $rivi .= pupesoft_csvstring("$row[myyjanro] - $masrow[nimi]").";";
  $rivi .= pupesoft_csvstring($row["erikoisale"]).";";

  if (!empty($row["myyntikielto"])) {
    $row["myyntikielto"] .= " - Asiakas on myyntikiellossa";
  }

  $rivi .= pupesoft_csvstring($row["myyntikielto"]).";";
  $rivi .= pupesoft_csvstring($row["myynninseuranta"]).";";
  $rivi .= pupesoft_csvstring($row["luottoraja"]).";";
  $rivi .= pupesoft_csvstring($row["kuluprosentti"]).";";
  $rivi .= pupesoft_csvstring($row["tuntihinta"]).";";
  $rivi .= pupesoft_csvstring($row["tuntikerroin"]).";";
  $rivi .= pupesoft_csvstring($row["hintakerroin"]).";";
  $rivi .= pupesoft_csvstring($row["pientarvikelisa"]).";";
  $rivi .= pupesoft_csvstring($row["laskunsummapyoristys"]).";";
  $rivi .= pupesoft_csvstring($row["laskutuslisa"]).";";
  $rivi .= pupesoft_csvstring($row["panttitili"]).";";
  $rivi .= pupesoft_csvstring($row["tilino"]).";";
  $rivi .= pupesoft_csvstring($row["tilino_eu"]).";";
  $rivi .= pupesoft_csvstring($row["tilino_ei_eu"]).";";
  $rivi .= pupesoft_csvstring($row["tilino_kaanteinen"]).";";
  $rivi .= pupesoft_csvstring($row["tilino_marginaali"]).";";
  $rivi .= pupesoft_csvstring($row["tilino_osto_marginaali"]).";";
  $rivi .= pupesoft_csvstring($row["tilino_triang"]).";";

  $query = "SELECT nimi
            FROM kustannuspaikka
            WHERE yhtio = '{$yhtio}'
            and kaytossa != 'E'
            and tyyppi    = 'K'
            and tunnus    = '$row[kustannuspaikka]'";
  $masres = pupe_query($query);
  $masrow = mysql_fetch_assoc($masres);

  $rivi .= pupesoft_csvstring($masrow["nimi"]).";";
  $rivi .= pupesoft_csvstring($row["kohde"]).";";
  $rivi .= pupesoft_csvstring($row["projekti"]).";";
  $rivi .= pupesoft_csvstring($row["laatija"]).";";
  $rivi .= pupesoft_csvstring($row["luontiaika"]).";";
  $rivi .= pupesoft_csvstring($row["muutospvm"]).";";
  $rivi .= pupesoft_csvstring($row["muuttaja"]).";";
  $rivi .= pupesoft_csvstring($row["flag_1"]).";";
  $rivi .= pupesoft_csvstring($row["flag_2"]).";";
  $rivi .= pupesoft_csvstring($row["flag_3"]).";";
  $rivi .= pupesoft_csvstring($row["flag_4"]).";";
  $rivi .= pupesoft_csvstring($row["maa_maara"]).";";
  $rivi .= pupesoft_csvstring($row["sisamaan_kuljetus"]).";";
  $rivi .= pupesoft_csvstring($row["sisamaan_kuljetus_kansallisuus"]).";";
  $rivi .= pupesoft_csvstring($row["sisamaan_kuljetusmuoto"]).";";
  $rivi .= pupesoft_csvstring($row["kontti"]).";";
  $rivi .= pupesoft_csvstring($row["aktiivinen_kuljetus"]).";";
  $rivi .= pupesoft_csvstring($row["aktiivinen_kuljetus_kansallisuus"]).";";
  $rivi .= pupesoft_csvstring($row["kauppatapahtuman_luonne"]).";";
  $rivi .= pupesoft_csvstring($row["kuljetusmuoto"]).";";
  $rivi .= pupesoft_csvstring($row["poistumistoimipaikka_koodi"]).";";
  $rivi .= pupesoft_csvstring($row["herminator"]).";";
  $rivi .= pupesoft_csvstring($row["herminator1"]).";";
  $rivi .= pupesoft_csvstring($row["herminator2"]).";";
  $rivi .= pupesoft_csvstring($row["herminator3"]).";";
  $rivi .= pupesoft_csvstring($row["herminator4"]).";";
  $rivi .= pupesoft_csvstring($row["tunnus"]);
  $rivi .= "\n";

  fwrite($fp, $rivi);

  $k_rivi++;

  if ($k_rivi % 1000 == 0) {
    echo "Käsitellään riviä {$k_rivi}\n";
  }
}

fclose($fp);

if (!empty($scp_siirto)) {
  // Siirretään toiselle palvelimelle
  system("scp {$filepath} $scp_siirto");
}

echo "Valmis.\n";
