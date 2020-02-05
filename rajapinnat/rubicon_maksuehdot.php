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

// T‰m‰ vaatii paljon muistia
error_reporting(E_ALL);
ini_set("memory_limit", "5G");
ini_set("display_errors", 1);
unset($pupe_query_debug);

$query = "SELECT *
          FROM maksuehto
          WHERE yhtio = 'atarv'";
$query_result = mysql_query($query) or pupe_error($query);

while ($query_row = mysql_fetch_assoc($query_result)) {
  $query = "SELECT *
            FROM maksuehto
            WHERE yhtio        = 'artr' AND
            rel_pvm            = '{$query_row['rel_pvm']}' AND
            abs_pvm            = '{$query_row['abs_pvm']}' AND
            kassa_relpvm       = '{$query_row['kassa_relpvm']}' AND
            kassa_abspvm       = '{$query_row['kassa_abspvm']}' AND
            kassa_alepros      = '{$query_row['kassa_alepros']}' AND
            osamaksuehto1      = '{$query_row['osamaksuehto1']}' AND
            osamaksuehto2      = '{$query_row['osamaksuehto2']}' AND
            summanjakoprososa2 = '{$query_row['summanjakoprososa2']}' AND
            jv                 = '{$query_row['jv']}' AND
            kateinen           = '{$query_row['kateinen']}' AND
            suoraveloitus      = '{$query_row['suoraveloitus']}' AND
            factoring          = '{$query_row['factoring']}' AND
            pankkiyhteystiedot = '{$query_row['pankkiyhteystiedot']}' AND
            itsetulostus       = '{$query_row['itsetulostus']}' AND
            jaksotettu         = '{$query_row['jaksotettu']}' AND
            erapvmkasin        = '{$query_row['erapvmkasin']}' AND
            sallitut_maat      = '{$query_row['sallitut_maat']}' AND
            kaytossa           = '{$query_row['kaytossa']}'";
  $query_result2 = mysql_query($query) or pupe_error($query);
  if (mysql_num_rows($query_result2) > 0) {

    // vaikka lˆytyisikin useampi, niin otetaan vaan eka eik‰ k‰pistell‰ yht‰‰n artr maksuehtoja
    $query_row2 = mysql_fetch_assoc($query_result2);

    // pit‰isi updatee joka paikka jossa on maksuehtoja niin $query_row2['tunnus'] ja sitten deletˆid‰ $query_row['tunnus']
    // asiakas.maksuehto
    // lasku.maksuehto
    // eip‰ kai muita?
    // asiakkaat ja laskut siirtyy toisaalla atarvi -> artr, joten t‰ss‰ pistet‰‰n vaan maksuehdon relaatio valmiiksi kuntoon

    $query = "UPDATE lasku SET maksuehto = '{$query_row2['tunnus']}' WHERE yhtio = 'atarv' AND tila in ('N','L','C','T') AND maksuehto = '{$query_row['tunnus']}'";
    $query_result3 = mysql_query($query) or pupe_error($query);

    $query = "UPDATE asiakas SET maksuehto = '{$query_row2['tunnus']}' WHERE yhtio = 'atarv' AND maksuehto = '{$query_row['tunnus']}'";
    $query_result3 = mysql_query($query) or pupe_error($query);

    $query = "DELETE FROM maksuehto WHERE yhtio = 'atarv' AND tunnus = '{$query_row['tunnus']}'";
    $query_result3 = mysql_query($query) or pupe_error($query);
  }
  else {
    // siirret‰‰n atarv maksuehto artr semmoisenaan
    $query = "UPDATE maksuehto SET yhtio = 'artr' WHERE yhtio = 'atarv' AND tunnus = '{$query_row['tunnus']}'";
    $query_result3 = mysql_query($query) or pupe_error($query);
  }
}
