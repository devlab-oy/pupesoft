<?php

if (php_sapi_name() != 'cli') {
  die('clionly!');
}

if (!isset($argv[1])) {
  echo "Anna Puperoot\n";
  exit(1);
}

$pupesoft_root = $argv[1];

if (!is_dir($pupesoft_root) or !is_file("{$pupesoft_root}/inc/salasanat.php")) {
  echo "Pupesoft root missing!";
  exit(1);
}

// Pupesoft root include_pathiin
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupesoft_root);

// Otetaan tietokanta connect
require "inc/connect.inc";
require "inc/functions.inc";

// Logitetaan ajo
cron_log();

// Tämä vaatii paljon muistia
error_reporting(E_ALL);
ini_set("memory_limit", "5G");
ini_set("display_errors", 1);
unset($pupe_query_debug);

$query = "SELECT *
          FROM yhtion_parametrit
          WHERE yhtio = 'atarv'";
$query_result = mysql_query($query) or pupe_error($query);
$query_row = mysql_fetch_assoc($query_result);

$query2 = "SELECT *
           FROM yhtion_parametrit
           WHERE yhtio = 'artr'";
$query_result2 = mysql_query($query2) or pupe_error($query2);
$query_row2 = mysql_fetch_assoc($query_result2);

$query3 = "SELECT *
           FROM yhtion_toimipaikat
           WHERE yhtio = 'artr'";
$query_result3 = mysql_query($query3) or pupe_error($query3);

$poikkeukset = array(
  'yhtio',
  'alert_varasto_kayttajat',
  'maventa_aikaleima',
  'luontiaika',
  'muutospvm',
  'muuttaja',
  'tunnus',
  'tuotteen_oletuspaikka',
  'kasittelykulu_tuotenumero',
  'takuuvarasto',
  'admin_email',
  'alert_email',
  'talhal_email',
  'myyntitilauksen_toimipaikka',
  'reklamaation_vastaanottovarasto',
  'toimipaikkakasittely',
  'myynnin_alekentat',
  'myynnin_alekentat_muokkaus',
);

while ($query_row3 = mysql_fetch_assoc($query_result3)) {
  for ($i = 0; $i < count($query_row); $i++) {
    $otsikko = mysql_field_name($query_result2, $i);

    if ($query_row[$otsikko] != $query_row2[$otsikko] and !in_array($otsikko, $poikkeukset)) {

      if ($query_row3['tunnus'] == 27 and $otsikko == 'kerayserat') continue;
      if ($query_row3['tunnus'] == 27 and $otsikko == 'pakollinen_varasto') continue;

      $query4 = "INSERT INTO yhtion_toimipaikat_parametrit SET
                 yhtio       = 'artr',
                 toimipaikka = '$query_row3[tunnus]',
                 parametri   = '$otsikko',
                 arvo        = '$query_row[$otsikko]',
                 laatija     = 'konversio',
                 luontiaika  = now()";
      mysql_query($query4) or pupe_error($query4);
    }
  }

  // lisäksi postittaja_email toimipaikan takaa toimipaikan parametreihin!
  if ($query_row3['postittaja_email'] != '') {

    $query4 = "INSERT INTO yhtion_toimipaikat_parametrit SET
               yhtio       = 'artr',
               toimipaikka = '{$query_row3['tunnus']}',
               parametri   = 'postittaja_email',
               arvo        = '{$query_row3['postittaja_email']}',
               laatija     = 'konversio',
               luontiaika  = now()";
    mysql_query($query4) or pupe_error($query4);
  }

  // lisäksi muutetaan toimipaikan nimi
  $vaihda = array(
    "Merca-Autoasi Oy /",
    "Merca-Autoasi Oy/",
  );

  $uusi_nimi = trim(str_replace($vaihda, '', $query_row3['nimi']));

  $query4 = "UPDATE yhtion_toimipaikat SET
             nimi        = '$uusi_nimi'
             WHERE yhtio = 'artr'
             AND tunnus  = '{$query_row3['tunnus']}'";
  mysql_query($query4) or pupe_error($query4);
}
