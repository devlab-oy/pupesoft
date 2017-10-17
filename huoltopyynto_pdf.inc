<?php

// tarvitaan työmääräyksen tunnus muuttujassa $tyom_tunnus -> voidaan tätä kautta hakea myös laitteen tiedot
// $kukarow array jostain saadaan yhtio sekä huoltopyynnön luonut henkilö sekä oletusasiakas
// $yhtiorow array josta saada lähettäjän tiedot

//PDF:n luonti ja defaultit
require_once "pdflib/phppdflib.class.php";

//PDF parametrit
$pdf = new pdffile;
$pdf->set_default('margin-top',   0);
$pdf->set_default('margin-bottom',   0);
$pdf->set_default('margin-left',   0);
$pdf->set_default('margin-right',   0);
$rectparam["width"] = 0.3;

//fontit
$otsik["height"] = 9;
$otsik["font"] = "Helvetica";

$pieni["height"] = 10;
$pieni["font"] = "Helvetica";

$norm["height"] = 10;
$norm["font"] = "Helvetica-Bold";

$piennorm["height"] = 9;
$piennorm["font"] = "Helvetica-Bold";

$kirj["height"] = 12;
$kirj["font"] = "Helvetica-Bold";

$iso["height"] = 16;
$iso["font"] = "Helvetica-Bold";

$huge["height"] = 20;
$huge["font"] = "Helvetica-Bold";

$tulostettavat_tiedot = hae_tiedot($tyom_tunnus);

// Tehdään uusi sivu
$firstpage = $pdf->new_page("a4");

tulosta_logo_pdf($pdf, $firstpage, "");

// Otsikko
$pdf->draw_text(210, 760, t("Huoltopyyntö", $kieli), $firstpage, $iso);

// Huoltopyynnön tiedot
$pdf->draw_text(50, 710, t("Huoltopyyntönumero", $kieli).": {$tulostettavat_tiedot['huoltopyynto']}",   $firstpage, $norm);
$pdf->draw_text(50, 690, t("Asiakas", $kieli).": {$tulostettavat_tiedot['asiakas']['nimi']}",   $firstpage, $norm);
$pdf->draw_text(50, 670, t("Huoltopyynnön luonut", $kieli).": {$kukarow['nimi']}",   $firstpage, $norm);
$pdf->draw_text(50, 650, t("Luontiaika", $kieli).": {$tulostettavat_tiedot['tyomaarays']['luontiaika']}",   $firstpage, $norm);

// Laitteen tiedot
$pdf->draw_text(50, 610, t("Huollettava laite", $kieli),   $firstpage, $norm);
$pdf->draw_text(50, 590, t("Valmistaja", $kieli).": {$tulostettavat_tiedot['tyomaarays']['merkki']}",   $firstpage, $pieni);
$pdf->draw_text(50, 570, t("Malli", $kieli).": {$tulostettavat_tiedot['tyomaarays']['mallivari']}",   $firstpage, $pieni);
$pdf->draw_text(50, 550, t("Sarjanumero", $kieli).": {$tulostettavat_tiedot['tyomaarays']['valmnro']}",   $firstpage, $pieni);

$viankuvausteksti = t("Viankuvaus", $kieli).": {$tulostettavat_tiedot['tyomaarays']['komm1']}";
$viankuvausteksti = substr($viankuvausteksti, 0, 170);
$viankuvausrivit = explode("\n", wordwrap($viankuvausteksti, 50));

$viankuvausrivi = 530;
foreach ($viankuvausrivit as $rivi) {
  $pdf->draw_text(50, $viankuvausrivi, $rivi, $firstpage, $pieni);
  $viankuvausrivi -= 11;
}

$pdf->draw_rectangle(490, 40, 630, 300, $firstpage, $rectparam);

if (empty($tulostettavat_tiedot['laite'])) {
  $pdf->draw_text(50, 380, t("Laitetta ei löytynyt laiterekisteristä", $kieli)."!",   $firstpage, $norm);
}

// Yhtiön osoitetiedot
$pdf->draw_text(50, 280, t("Vioittuneen laitteen lähetysosoite", $kieli).":",     $firstpage, $norm);
$pdf->draw_text(50, 260, $yhtiorow["nimi"],    $firstpage, $pieni);
$pdf->draw_text(50, 249, $yhtiorow["osoite"],  $firstpage, $pieni);
$pdf->draw_text(50, 238, $yhtiorow["postino"]."  ".$yhtiorow["postitp"],  $firstpage, $pieni);
$pdf->draw_text(50, 227, $yhtiorow["maa"],    $firstpage, $pieni);

$infoteksti = t("Lähetäthän laitteen mukana tulosteen tästä dokumentista, tai vaihtoehtoisesti kirjoitat mukaan huoltopyynnön numeron, kiitos", $kieli);
$infotekstirivit = explode("\n", wordwrap($infoteksti, 85));

$inforivi = 200;
foreach ($infotekstirivit as $rivi) {
  $pdf->draw_text(50, $inforivi, $rivi,    $firstpage, $piennorm);
  $inforivi -= 11;
}

// Toimitusosoite
$pdf->draw_text(50, 140, t("Palautusosoite", $kieli).":",     $firstpage, $norm);
$pdf->draw_text(50, 120, $tulostettavat_tiedot['lasku']['toim_nimi'],    $firstpage, $pieni);
$pdf->draw_text(50, 109, $tulostettavat_tiedot['lasku']['toim_osoite'],  $firstpage, $pieni);
$pdf->draw_text(50, 98, $tulostettavat_tiedot['lasku']['toim_postino']."  ".$tulostettavat_tiedot['lasku']['toim_postitp'],  $firstpage, $pieni);
$pdf->draw_text(50, 87, $tulostettavat_tiedot['lasku']['toim_maa'],    $firstpage, $pieni);

//keksitään uudelle failille joku varmasti uniikki nimi:
list($usec, $sec) = explode(' ', microtime());
mt_srand((float) $sec + ((float) $usec * 100000));
$asiakasnimi = str_replace(" ", "", $tulostettavat_tiedot['asiakas']['nimi']);
$pdffilenimi = "/tmp/".t("huoltopyynto", $kieli)."-{$tulostettavat_tiedot['huoltopyynto']}-{$asiakasnimi}.pdf";

//kirjoitetaan pdf faili levylle..
$fh = fopen($pdffilenimi, "w");
if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
fclose($fh);

function hae_tiedot($tyomaaraystunnus) {
  global $kukarow;

  $tiedot = array();

  $tiedot['huoltopyynto'] = $tyomaaraystunnus;

  // Haetaan asiakastiedot
  $asiakasquery = "SELECT asiakas.*
                   FROM asiakas
                   WHERE asiakas.yhtio = '{$kukarow['yhtio']}'
                   AND asiakas.tunnus  = '{$kukarow['oletus_asiakas']}'";
  $asiakasresult = pupe_query($asiakasquery);
  $asiakasrivi = mysql_fetch_assoc($asiakasresult);
  $tiedot['asiakas'] = $asiakasrivi;

  // Haetaan työmääräyksen tiedot
  $tyomaaraysquery = "SELECT *
                      FROM tyomaarays
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND otunnus = '{$tyomaaraystunnus}'";
  $tyomaaraysresult = pupe_query($tyomaaraysquery);
  $tyomaaraysrivi = mysql_fetch_assoc($tyomaaraysresult);
  $tiedot['tyomaarays'] = $tyomaaraysrivi;

  // Haetaan lasku ja laskun lisätiedot
  $laskuquery = "SELECT lasku.*
                 FROM lasku
                 JOIN laskun_lisatiedot ON (lasku.yhtio = laskun_lisatiedot.yhtio
                   AND lasku.tunnus = laskun_lisatiedot.otunnus)
                 WHERE lasku.yhtio  = '{$kukarow['yhtio']}'
                 AND lasku.tunnus   = '{$tyomaaraystunnus}'";
  $laskuresult = pupe_query($laskuquery);
  $laskurivi = mysql_fetch_assoc($laskuresult);
  $tiedot['lasku'] = $laskurivi;

  // Haetaan laitetiedot
  $laitequery = "SELECT *
                 FROM laite
                 WHERE yhtio  = '{$kukarow['yhtio']}'
                 AND sarjanro = '{$tyomaaraysrivi['valmnro']}'
                 AND tuoteno  = '{$tyomaaraysrivi['mallivari']}'";
  $laiteresult = pupe_query($laitequery);
  $laiterivi = mysql_fetch_assoc($laiteresult);
  $tiedot['laite'] = $laiterivi;

  return $tiedot;
}
