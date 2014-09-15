<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");

$tilausrivi = (int) $tilausrivi;

// Virheet
$errors = array();
if (!isset($tuotepaikka)) $tuotepaikka = '';

$onko_varaston_hyllypaikat_kaytossa = onko_varaston_hyllypaikat_kaytossa();

if (isset($tuotepaikka)) {

  $tuotepaikka = urldecode($tuotepaikka);

  // Parsitaan uusi tuotepaikka
  // Jos tuotepaikka on luettu viivakoodina, muotoa (C21 045) tai (21C 03V)
  if (preg_match('/^([a-zåäö#0-9]{2,4} [a-zåäö#0-9]{2,4})/i', $tuotepaikka)) {

      // Pilkotaan viivakoodilla luettu tuotepaikka välilyönnistä
      list($alku, $loppu) = explode(' ', $tuotepaikka);

      // Mätsätään numerot ja kirjaimet erilleen
      preg_match_all('/([0-9]+)|([a-z]+)/i', $alku, $alku);
      preg_match_all('/([0-9]+)|([a-z]+)/i', $loppu, $loppu);

      // Hyllyn tiedot oikeisiin muuttujiin
      $hyllyalue = $alku[0][0];
      $hyllynro  = $alku[0][1];
      $hyllyvali = $loppu[0][0];
      $hyllytaso = $loppu[0][1];

      // Kaikkia tuotepaikkoja ei pystytä parsimaan
      if ($hyllyalue == '' or $hyllynro == '' or $hyllyvali == '' or $hyllytaso == '') {
        $errors[] = t("Tuotepaikan haussa virhe, yritä syöttää tuotepaikka käsin") . " ($tuotepaikka)";
      }
    }
    // Tuotepaikka syötetty manuaalisesti (C-21-04-5) tai (C 21 04 5)
    elseif (strstr($tuotepaikka, '-') or strstr($tuotepaikka, ' ')) {
      // Parsitaan tuotepaikka omiin muuttujiin (erotelto välilyönnillä)
      if (preg_match('/\w+\s\w+\s\w+\s\w+/i', $tuotepaikka)) {
        list($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso) = explode(' ', $tuotepaikka);
      }
      // (erotelto väliviivalla)
      elseif (preg_match('/\w+-\w+-\w+-\w+/i', $tuotepaikka)) {
        list($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso) = explode('-', $tuotepaikka);
      }

      // Ei saa olla tyhjiä kenttiä
      if ($hyllyalue == '' or $hyllynro == '' or $hyllyvali == '' or $hyllytaso == '') {
        $errors[] = t("Virheellinen tuotepaikka") . ". ($tuotepaikka)";
      }
    }
    else {
      $errors[] = t("Virheellinen tuotepaikka, yritä syöttää tuotepaikka käsin") . " ($tuotepaikka)";
    }

    // Tarkistetaan että tuotepaikka on olemassa
    if ($onko_varaston_hyllypaikat_kaytossa and count($errors) == 0 and !tarkista_varaston_hyllypaikka($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso)) {
      $errors[] = t("Varaston tuotepaikkaa ($hyllyalue-$hyllynro-$hyllyvali-$hyllytaso) ei ole perustettu").'.';
    }

    if (count($errors) == 0) {

      $hylly = array(
        "hyllyalue" => $hyllyalue,
        "hyllynro"   => $hyllynro,
        "hyllyvali" => $hyllyvali,
        "hyllytaso" => $hyllytaso
      );

      $query = "SELECT * FROM tilausrivi
                WHERE tunnus = '{$tilausrivi}'
                AND yhtio = '{$kukarow['yhtio']}'";
      $row = mysql_fetch_assoc(pupe_query($query));

      // Tarkistetaan onko syötetty hyllypaikka jo tälle tuotteelle
      $tuotteen_oma_hyllypaikka = "SELECT * FROM tuotepaikat
                                   WHERE tuoteno = '{$row['tuoteno']}'
                                   AND yhtio     = '{$kukarow['yhtio']}'
                                   AND hyllyalue = '$hyllyalue'
                                   AND hyllynro  = '$hyllynro'
                                   AND hyllyvali = '$hyllyvali'
                                   AND hyllytaso = '$hyllytaso'";
      $oma_paikka = pupe_query($tuotteen_oma_hyllypaikka);

      // Jos syötettyä paikkaa ei ole tämän tuotteen, lisätään uusi tuotepaikka
      if (mysql_num_rows($oma_paikka) == 0) {

        $_viesti = 'Saapumisessa';

        lisaa_tuotepaikka($row['tuoteno'], $hyllyalue, $hyllynro, $hyllyvali, $hyllytaso, $_viesti, '', $halytysraja, $tilausmaara);
      }
      else {
        // Nollataan poistettava kenttä varmuuden vuoksi
        $query = "UPDATE tuotepaikat SET
                  poistettava   = ''
                  WHERE tuoteno = '{$row['tuoteno']}'
                  AND yhtio     = '{$kukarow['yhtio']}'
                  AND hyllyalue = '$hyllyalue'
                  AND hyllynro  = '$hyllynro'
                  AND hyllyvali = '$hyllyvali'
                  AND hyllytaso = '$hyllytaso'";
        pupe_query($query);
      }

      paivita_tilausrivin_hylly($tilausrivi, $hylly);

      $tilausrivit = array();

      // Jos rivi on jo kohdistettu eri saapumiselle
      if (!empty($row['uusiotunnus'])) {
        $saapuminen = $row['uusiotunnus'];
      }
      elseif ($yhtiorow['suuntalavat'] == "" and $saapuminen != 0) {
        // Jos yhtiö ei käytä suuntalavaa ja rivi ei ole saapumisella
        $query = "UPDATE tilausrivi SET
                  uusiotunnus = '{$saapuminen}'
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  = '{$row['tunnus']}'";
        pupe_query($query);
      }

      vie_varastoon($saapuminen, 0, $hylly, $row['tunnus']);

      // katsotaan onko tilauksen rivejä vielä hyllyttämättä
      $query = "SELECT COUNT(tunnus)
                FROM tilausrivi
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND otunnus = '{$ostotilaus}'
                AND uusiotunnus = 0";
      $result = pupe_query($query);
      $viemattomia = mysql_result($result, 0);

      echo t("Odota hetki...");
      echo "<META HTTP-EQUIV='Refresh'CONTENT='1;URL=ostotilaus_sarjanumero.php?saapuminen={$saapuminen}&v={$viemattomia}'>"; exit();
    }
}

require 'inc/footer.inc';
