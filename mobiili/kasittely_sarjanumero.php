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

      paivita_tilausrivit_ja_sarjanumeroseuranta($tilausrivi, $hylly);

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

      sscanf($row['kommentti'], 'rahtikirjanumero:%d', $rahtikirjanumero);

      $query = "SELECT
                sum(CASE WHEN kommentti LIKE 'rahtikirjanumero:{$rahtikirjanumero}%'
                  AND uusiotunnus = 0
                  THEN 1 ELSE 0 END) viemattomia_rahdin_riveja,
                sum(CASE WHEN otunnus = '{$ostotilaus}'
                  AND uusiotunnus = 0
                  THEN 1 ELSE 0 END) viemattomia_tilauksen_riveja
                FROM tilausrivi
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tyyppi = 'O'";
      $result = pupe_query($query);
      $tilanne = mysql_fetch_assoc($result);

      echo t("Odota hetki...");
      echo "<META HTTP-EQUIV='Refresh'CONTENT='1;URL=ostotilaus_sarjanumero.php?saapuminen={$saapuminen}&r={$tilanne['viemattomia_rahdin_riveja']}&t={$tilanne['viemattomia_tilauksen_riveja']}'>"; exit();
    }
}

require 'inc/footer.inc';

function paivita_tilausrivit_ja_sarjanumeroseuranta($ostorivitunnus, $hylly) {
  global $kukarow;

  $query = "SELECT myyntirivitunnus
            FROM sarjanumeroseuranta
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND ostorivitunnus = '{$ostorivitunnus}'";
  $result = pupe_query($query);
  $myyntirivitunnus = mysql_result($result,0);

  $query = "SELECT varasto
            FROM tilausrivi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$ostorivitunnus}'";
  $result = pupe_query($query);
  $varasto = mysql_result($result,0);

  $hyllyalue = strtoupper($hylly['hyllyalue']);
  $hyllynro  = strtoupper($hylly['hyllynro']);
  $hyllyvali = strtoupper($hylly['hyllyvali']);
  $hyllytaso = strtoupper($hylly['hyllytaso']);

  $query = "UPDATE tilausrivi SET
            hyllyalue = '{$hyllyalue}',
            hyllynro = '{$hyllynro}',
            hyllyvali = '{$hyllyvali}',
            hyllytaso = '{$hyllytaso}'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$ostorivitunnus}'";
  $result = pupe_query($query);

  $query = "UPDATE tilausrivi SET
            hyllyalue = '{$hyllyalue}',
            hyllynro = '{$hyllynro}',
            hyllyvali = '{$hyllyvali}',
            hyllytaso = '{$hyllytaso}'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$myyntirivitunnus}'";
  $result = pupe_query($query);

  $query = "UPDATE sarjanumeroseuranta SET
            hyllyalue = '{$hyllyalue}',
            hyllynro = '{$hyllynro}',
            hyllyvali = '{$hyllyvali}',
            hyllytaso = '{$hyllytaso}',
            varasto = '{$varasto}'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND ostorivitunnus = '{$ostorivitunnus}'";
  $result = pupe_query($query);

}
