<?php

//Luodaan tarvittavat muuttujat rahtikirja_postitarra_pdf.inc

if ($phpnimi == "rahtikirja_custom.php") {
  $postirow = $osoitelappurow;
  $rakir_row = $osoitelappurow;
}
else {
  $query = "SELECT * from lasku where yhtio = '$kukarow[yhtio]' and tunnus in ($otunnukset) order by tunnus limit 1";
  $tempr = pupe_query($query);
  $postirow = mysql_fetch_array($tempr);
}

// haetaan varaston osoitetiedot, käytetään niitä lähetystietoina
$query = "SELECT nimi, nimitark, osoite, postino, postitp, maa
          FROM varastopaikat
          WHERE yhtio = '$kukarow[yhtio]'
          AND tunnus  = '$postirow[varasto]'";
$tempr = pupe_query($query);
$postirow_varasto = mysql_fetch_array($tempr);

// jos varastolle on annettu joku osoite, käytetään sitä
if ($postirow_varasto["nimi"] != "") {
  $postirow["yhtio_nimi"]     = $postirow_varasto["nimi"];
  $postirow['yhtio_nimitark']  = $postirow_varasto["nimitark"];
  $postirow["yhtio_osoite"]   = $postirow_varasto["osoite"];
  $postirow["yhtio_postino"]  = $postirow_varasto["postino"];
  $postirow["yhtio_postitp"]  = $postirow_varasto["postitp"];
  $postirow["yhtio_maa"]      = $postirow_varasto["maa"];
}

// jos meillä on printterin takana joku spessu osoitetieto niin käytetään sen tietoja lähettäjän tietoina
if ($print["nimi"] != "") {
  $postirow["yhtio_nimi"]    = $print["nimi"];
  $postirow["yhtio_osoite"]  = $print["osoite"];
  $postirow["yhtio_postino"] = $print["postino"];
  $postirow["yhtio_postitp"] = $print["postitp"];
}

if ($postirow['rahtisopimus']=='') $postirow['rahtisopimus'] = "000000";
if ($toitarow['sopimusnro']=='')   $toitarow['sopimusnro']   = "000000";

// postiennakko
if ($rakir_row["jv"] != '' or $mehto['jv'] != '') {
  $postiennakkomaara  = "$yhteensa $postirow[valkoodi]";
  if ($yhtiorow["pankkiiban1"] != '') {
    $postiennakkotilino = "$yhtiorow[pankkiiban1]";
  }
  else {
    $postiennakkotilino = "$yhtiorow[pankkitili1]";
  }
  $postiennakkobic  = "$yhtiorow[pankkiswift1]";
  $postiennakkoviite  = "$viite";
}
else {
  $postiennakkomaara  = "";
  $postiennakkotilino = "";
  $postiennakkobic  = "";
  $postiennakkoviite  = "";
}

// Lisäpalvelut
// 1 = Postiennakko
// 3 = Maksaja muu kuin lähettäjä
// 4 = Särkyvä
// 6 = Lauantaijakelu

$x = $x_tekstit = $x_tekstit_koko = array();

// Postiennakko
if ($yhteensa != '') {
  $x[] = '1';
  $x_tekstit[1] = 'POSTIENNAKKO';
  $x_tekstit_koko[1] = 'POSTIENNAKKO';
}

// Maksaja muu kuin lähettäjä
if ($rakir_row['merahti'] != 'K') {
  $x[] = '3';
  $x_tekstit[3] = 'MAKSAJA MUU';
  $x_tekstit_koko[3] = 'MAKSAJA MUU KUIN LÄHETTÄJÄ';
}

// Särkyvä
if ($toitarow['erilliskasiteltavakulu'] != 0) {
  $x[] = '4';
  $x_tekstit[4] = 'SÄRKYVÄ';
  $x_tekstit_koko[4] = 'SÄRKYVÄ';
}

// Lauantaijakelu
if ($toitarow['lauantai'] != '') {
  $x[] = '6';
  $x_tekstit[6] = 'LAUANTAIJAKELU';
  $x_tekstit_koko[6] = 'LAUANTAIJAKELU';
}

// Asetetaan rahtikirjan tiedot tulostettavaan muotoon
// tuoteviiva1 = postin palvelukoodi, joka tulostetaan viivakoodina rahtikirjaan
// $keltainen  = toimitustavan nimi, joka tulostetaan ensimmäiselle riville
// $kuljetus   = toimitustavan nimen lisätieto, joka tulostetaan toiselle riville
// $keku       = toimitustavan numero, joka tulostetaan isolla fontilla nimen viereen
switch ($toitarow['virallinen_selite']) {
  case 'Pikkupaketti':
    $tuoteviiva1 = '2W2461';
    $keltainen   = 'Pikkupaketti';
    $kuljetus    = '';
    $keku        = 'P';

    break;
  case 'Postipaketti':
    $tuoteviiva1 = '2W2103';
    $keltainen   = 'Postipaketti';
    $kuljetus    = '';
    $keku        = '16';

    break;
  case 'Kotipaketti':
    $tuoteviiva1 = '2W2104';
    $keltainen   = 'Kotipaketti';
    $kuljetus    = '';
    $keku        = '21';

    break;
  case 'Express-paketti':
    $tuoteviiva1 = '2W2102';
    $keltainen   = 'Express-paketti';
    $kuljetus    = '';
    $keku        = '14';

    break;
  case 'Express-paketti Aamuksi 09':
    $tuoteviiva1 = '2W2101';
    $keltainen   = 'Express-paketti';
    $kuljetus    = 'Aamuksi';
    $keku        = '9';

    break;
  case 'Express-paketti Samana Päivänä 00':
    $tuoteviiva1 = '2W2124';
    $keltainen   = 'Express-paketti';
    $kuljetus    = 'Samana Päivänä';
    $keku        = '00';

    break;
  case 'Express-rahti':
    $tuoteviiva1 = '2W2144';
    $keltainen   = 'Express-rahti';
    $kuljetus    = '';
    $keku        = '14';

    break;
  case 'Express-rahti Aamuksi 09':
    $tuoteviiva1 = '2W2143';
    $keltainen   = 'Express-rahti';
    $kuljetus    = 'Aamuksi';
    $keku        = '9';

    break;
  case 'Express-rahti Illaksi 21':
    $tuoteviiva1 = '2W2145';
    $keltainen   = 'Express-rahti';
    $kuljetus    = 'Illaksi';
    $keku        = '21';

    break;
  case 'Express-rahti Samana Päivänä 00':
    $tuoteviiva1 = '2W2142';
    $keltainen   = 'Express-rahti';
    $kuljetus    = 'Samana Päivänä';
    $keku        = '00';

    break;
  case 'Priority':
    $tuoteviiva1 = '2W2015';
    $keltainen   = 'Priority';
    $kuljetus    = '';
    $keku        = '';

    break;
  default:
    // Oletuksena: Posti Express-paketti 14
    $tuoteviiva1 = '2W2102';
    $keltainen   = 'Express-paketti';
    $kuljetus    = '';
    $keku        = '14';

    break;
}

if (count($vakit) > 0) {
  // Tämä tyhjennetään kun tulostetaan VAK/ADR rahtikirja
  $keku = "";
}

if ($rahdinmaksaja == 'Lähettäjä') {
  $rahdinmaksaja    = "";
  $rahdinmaksajan_nr   = "";
}
elseif (trim($rakir_row['rahtisopimus']) != "") {
  $rahdinmaksaja    = "Maksaja muu kuin lähettäjä. ";
  $rahdinmaksajan_nr   = $rakir_row['rahtisopimus'];
}
else {
  $rahdinmaksaja    = "Maksaja muu kuin lähettäjä. ";
  $rahdinmaksajan_nr   = "";
}

// Ulkomaan lähetykissä tarvitaan myös nelinumeroinen, asiakaskohtainen tunnushallintanumero
if (strpos($toitarow['sopimusnro'], "@") !== FALSE) {
  list($toitarow['sopimusnro'], $toitarow['tunhalnro']) = explode("@", $toitarow['sopimusnro']);
}

$toitarow['sopimusnro'] = sprintf("%06s", $toitarow['sopimusnro']);  // sopimunumeron tulee olla kuus pitkä
$sopnro  = $toitarow['sopimusnro']; // käytetään edi sanomassa
