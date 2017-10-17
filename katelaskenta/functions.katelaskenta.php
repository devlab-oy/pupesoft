<?php
/*
 * functions.katelaskenta.php
 *
 * Tiedosto pitää sisällään funktioita katelaskenta toiminnon
 * suorittamiseen.
 */


/**
 * Funktio laskee uuden hinnan kateprosentilla.
 *
 * Kateprosentti annetaan prosenteissa, ei desimaaleissa.
 * Voi olla 0-100 väliltä.
 */


function lisaa_hintaan_kate($keskihankintahinta, $kateprosentti) {

  $keskihankintahinta = (float)$keskihankintahinta;
  $kateprosentti = (float)$kateprosentti;

  return $keskihankintahinta / ( 1 - ( $kateprosentti / 100 ) );
}


/**
 * Funktio tarkistaa, että kateprosentti on sallitulla välillä.
 *
 * Kateprosentti ei voi olla yli 100 tai alle 0. Funktio palauttaa
 * true, jos prosentissa ei ole mitään vikaa.
 */
function tarkista_kateprosentti($kateprosentti) {
  if (!is_numeric($kateprosentti))
    return false;

  $kateprosentti = (float)$kateprosentti;
  if ($kateprosentti >= 100 || $kateprosentti < 0)
    return false;

  return true;
}


/**
 * Funktio tarkistaa syötteet, joita katelaskentaohjelma lähettää.
 *
 * Funktio on kooste pienemmistä toimenpiteistä. Jos virheitä ilmenee
 * tarkistusten aikana, lisätään ongelmarivit virhe-taulukkoon.
 * Lopuksi palautetaan taulukko, jossa on kaksi sisäkkäistä taulukkoa.
 * "kunnossa" -taulukko sisältää tarkistuksista läpäisseet syötteet
 * ja "Virheelliset" -taulukko ne rivit, joissa oli ongelmia.
 *
 * Taulukon rakenne on seuraavanlainen:
 * [avain] => [tunnus, myyntikate, myymalakate, nettokate, keskihankintahinta]
 */
function tarkista_katelaskenta_syotteet($taulukko) {

  // Luodaan uusi virhe-taulukko, johon kerätään mahdolliset
  // virheelliset rivit.
  $virherivit = array();

  // Käydään läpi valitut rivit käyttäjän syötteistä.
  foreach ($taulukko["kunnossa"] as &$rivi) {

    // Jos kateprosentti on virheellinen, lisätään rivi
    // virhe-taulukkoon ja hypätään seuraavaan riviin.
    if (!tarkista_kateprosentti($rivi[1])) {
      $virherivit["'" . $rivi[0] . "'"] = $rivi;
      continue;
    }

    if (!tarkista_kateprosentti($rivi[2])) {
      $virherivit["'" . $rivi[0] . "'"] = $rivi;
      continue;
    }

    if (!tarkista_kateprosentti($rivi[3])) {
      $virherivit["'" . $rivi[0] . "'"] = $rivi;
      continue;
    }
  }

  // Tallennetaan virheelliset rivit omaan alkioonsa.
  $taulukko["virheelliset"] = $virherivit;

  // Siivotaan alkuperäisistä riveistä virherivit
  $taulukko["kunnossa"] = array_diff_key($taulukko["kunnossa"], $virherivit);

  // Palautetaan taulukko, joka pitää sisällään kunnossa olevat
  // ja virheelliset rivit.
  return $taulukko;
}


/**
 * Luo parametrina annetusta taulukosta sql-update komennot
 * uusien hintojen päivittämistä varten.
 *
 * Taulukon rakenne on seuraavanlainen:
 * [avain] => [tunnus, myyntikate, myymalakate, nettokate, keskihankintahinta]
 */
function luo_katelaskenta_update_komennot($taulukko) {
  global $kukarow;

  // Luodaan update-komennoille taulukko, johon kaikki komennot
  // kootaan.
  $update_komennot = array();
  $sql_komento_alku = "UPDATE tuote SET ";
  $sql_komento_loppu = " WHERE yhtio = '{$kukarow['yhtio']}' and tunnus = ";

  // Käydään läpi jokainen valittu tuoterivi ja muodostetaan
  // ehtojen mukaan oikeanlainen update-komento.
  foreach ($taulukko as $rivi) {

    $rivin_tunnus = $rivi[0];
    $rivin_myyntikate = $rivi[1];
    $rivin_myymalakate = $rivi[2];
    $rivin_nettokate = $rivi[3];
    $rivin_keskihankintahinta = $rivi[4];

    $update_kysely = "";
    $update_kysely .= $sql_komento_alku;

    // Jos komennossa m, lasketaan myyntihinta.
    if ($rivin_myyntikate > 0 and $rivin_keskihankintahinta > 0) {
      $uusi_hinta = lisaa_hintaan_kate($rivin_keskihankintahinta, $rivin_myyntikate);
      $uusi_hinta = hintapyoristys($uusi_hinta, 2);
      $update_kysely .= "myyntihinta = {$uusi_hinta}, ";
    }
    // Jos komennossa y, lasketaan myymalahinta.
    if ($rivin_myymalakate > 0 and $rivin_keskihankintahinta > 0) {
      $uusi_hinta = lisaa_hintaan_kate($rivin_keskihankintahinta, $rivin_myymalakate);
      $uusi_hinta = hintapyoristys($uusi_hinta, 2);
      $update_kysely .= "myymalahinta = {$uusi_hinta}, ";
    }
    // Jos komennossa n, lasketaan nettohinta.
    if ($rivin_nettokate > 0 and $rivin_keskihankintahinta > 0) {
      $uusi_hinta = lisaa_hintaan_kate($rivin_keskihankintahinta, $rivin_nettokate);
      $uusi_hinta = hintapyoristys($uusi_hinta, 2);
      $update_kysely .= "nettohinta = {$uusi_hinta}, ";
    }

    // Lisätään kyselyyn pakolliset kentät, jotka tulee jokaiseen
    // komennon lopuksi mukaan.
    $update_kysely .= "myyntikate = {$rivin_myyntikate}, ";
    $update_kysely .= "myymalakate = {$rivin_myymalakate}, ";
    $update_kysely .= "nettokate = {$rivin_nettokate}";

    // Kyselyn where -ehdon lisääminen.
    $update_kysely .= $sql_komento_loppu . $rivin_tunnus;

    // Lisätään valmisteltu kysely taulukkoon.
    array_push($update_komennot, $update_kysely);
  }

  return $update_komennot;
}


/**
 * Pääasiallinen funktio uusien katetietojen tallentamiseen.
 *
 * Tarkistetaan ja siistitään syötetyt tiedot, luodaan päivitys
 * komennot tietokantaa varten ja päivitetään muutokset.
 *
 * Jos virheitä ilmenee, palautetaan ne taulukkona virheriveineen.
 * Kunnossa olleet rivit tallennetaan siitä huolimatta.
 */
function tallenna_valitut_katemuutokset($data) {

  // Luodaan yhdistelmätaulukko, jossa eritellään virheelliset
  // rivit ja kunnossa olevat. Virheelliset taulukkoon lisätään
  // ne rivit, joiden syötteissä prosessin aikana ilmenee ongelmia.
  $yhdistetyt_tuoterivit = array();
  $yhdistetyt_tuoterivit["virheelliset"] = array();
  $yhdistetyt_tuoterivit["kunnossa"] = array();

  // Siivotaan valitut tuoterivit tyhjistä avain => arvo pareista
  $valitut_tuoterivit = array_filter($data["valitutrivit"]);

  // Siivotaan valitut kateprosentit valittujen tuoterivien
  // perusteella. Jäljelle jää vain valitut tuotteet taulukosta.
  $valitut_tuoterivit_myyntikate = array_intersect_key($data["myyntikate"], $valitut_tuoterivit);
  $valitut_tuoterivit_myymalakate = array_intersect_key($data["myymalakate"], $valitut_tuoterivit);
  $valitut_tuoterivit_nettokate = array_intersect_key($data["nettokate"], $valitut_tuoterivit);

  // Siivotaan valitut keskihankintahinnat valittujen tuoterivien
  // perusteella. Jäljelle jää vain valitut tuotteet taulukosta.
  $valitut_tuoterivit_keskihankintahinnat = array_intersect_key($data["valitutkeskihankintahinnat"], $valitut_tuoterivit);


  // Array_merge_recursive -funktiolla taulut yhdistetään yhdeksi kokonaisuudeksi.
  // Funktio käyttää taulukon avaimia, joilla tiedot koostetaan yksinkertaisemmaksi
  // taulukoksi.
  //
  // Tulevan taulun rakenne on seuraava:
  // [avain] => [tunnus, myyntikate, myymalakate, nettokate, keskihankintahinta]
  $yhdistetyt_tuoterivit["kunnossa"] = array_merge_recursive($valitut_tuoterivit,
    $valitut_tuoterivit_myyntikate,
    $valitut_tuoterivit_myymalakate,
    $valitut_tuoterivit_nettokate,
    $valitut_tuoterivit_keskihankintahinnat);


  // Tarkistetaan syötteet ja funktio palauttaa taulukon, jossa
  // on eritelty kunnossa olleet rivit virheellisistä.
  $yhdistetyt_tuoterivit = tarkista_katelaskenta_syotteet($yhdistetyt_tuoterivit);

  // Update_komennot -taulukko sisältää valmistellus update -sql komennot
  // Komennot luodaan erillisessä funktiossa, jonne annetaan parametrina
  // jo tarkistetut tiedot. Virheellisille riveille ei tehdä mitään.
  $update_komennot = array();
  $update_komennot = luo_katelaskenta_update_komennot($yhdistetyt_tuoterivit["kunnossa"]);


  // Ajetaan päivityskomennot tietokantaan.
  foreach ($update_komennot as $updatesql) {
    pupe_query($updatesql);
  }

  // Palautetaan virheelliset tuotteet.
  // count() == 0 jos virheitä ei ollut.
  return $yhdistetyt_tuoterivit["virheelliset"];
}
