<?php

if (php_sapi_name() != 'cli') {
  die('clionly!');
}
else {
  // otetaan includepath aina rootista
  $pupe_root_polku = dirname(dirname(__FILE__));
  // $pupe_root_polku = "/Users/satu/Sites/pupesoft";
}

if (!is_dir($pupe_root_polku) or !is_file("{$pupe_root_polku}/inc/salasanat.php")) {
  echo "Pupesoft root missing!";
  exit(1);
}

// Pupesoft root include_pathiin
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupe_root_polku.PATH_SEPARATOR."/usr/share/pear");

// Otetaan tietokanta connect
require "inc/connect.inc";
require "inc/functions.inc";
 
// Tämä vaatii paljon muistia
error_reporting(E_ALL);
ini_set("memory_limit", "5G");
ini_set("display_errors", 1);
unset($pupe_query_debug);

#$filenimi = "/home/devlab/puuttuvat_tilioinnit.txt";
$filenimi = "/tmp/puuttuvat_tilioinnit.txt";
$toot = fopen($filenimi, "w+");

$dbhost='10.0.1.10';
$dbuser='pupesoft';
$dbpass='pupe1';
$dbkanta = 'orum';
$dbarkisto = 'orumarkistostatic';
$yhtio = 'artr';

$arkistolink = mysql_connect($dbhost, $dbuser, $dbpass) or die ("Ongelma tietokantapalvelimessa $dbhost");
  mysql_select_db($dbarkisto, $arkistolink) or die ("Tietokantaa $dbarkisto ei löydy palvelimelta $dbhost! (connect.inc)");
  mysql_set_charset("latin1", $arkistolink);
  mysql_query("set group_concat_max_len=1000000", $arkistolink);

mysql_select_db($dbkanta, $masterlink) or die ("Tietokantaa $dbkanta ei löydy palvelimelta $dbhost! (connect.inc)");

$query = "(SELECT tunnus, laskunro, laskutettu
           FROM lasku
           WHERE lasku.yhtio = 'artr'
           AND lasku.tila in ('H','Y','M','P','Q','X') 
           AND lasku.mapvm >= '2008-01-01' 
           AND lasku.mapvm <= '2011-12-31')
          UNION 
          (SELECT tunnus, laskunro, laskutettu
            FROM lasku
            WHERE lasku.yhtio = 'artr'
            AND lasku.tila = 'U' AND lasku.alatila ='X' 
            AND lasku.mapvm  > 0 
            AND lasku.tapvm  >= '2008-01-01' 
            AND lasku.tapvm   <= '2011-31-31')";
$query_result = pupe_query($query, $masterlink);
  
while ($laskurow = mysql_fetch_assoc($query_result)) {
  
  $query = "SELECT * FROM tiliointi
            WHERE tiliointi.yhtio = '{$yhtio}'
            AND tiliointi.ltunnus = '{$laskurow['tunnus']}'";
  $query_haku = pupe_query($query, $arkistolink);        
  
  while ($tiliointirow = mysql_fetch_assoc($query_haku)) {

    $query = "SELECT * FROM tiliointi
              WHERE tiliointi.yhtio = '{$yhtio}'
              AND tiliointi.tunnus = '{$tiliointirow['tunnus']}'";
    $queryres = pupe_query($query, $masterlink);        
    if (mysql_num_rows($queryres) == 0 ) {
      $puuttuvarivi = mysql_fetch_assoc($queryres);
      
      $ulos = "INSERT INTO tiliointi SET yhtio = 'artr'";
      $ulos .= ", laatija = '".$tiliointirow['laatija']."'";
      $ulos .= ", laadittu = '".$tiliointirow['laadittu']."'";
      $ulos .= ", ltunnus = ".$tiliointirow['ltunnus'];
      $ulos .= ", liitos = '".$tiliointirow['liitos']."'";
      $ulos .= ", liitostunnus = ".$tiliointirow['liitostunnus'];
      $ulos .= ", tilino = '".$tiliointirow['tilino']."'";
      $ulos .= ", kustp = ".$tiliointirow['kustp'];
      $ulos .= ", kohde = ".$tiliointirow['kohde'];
      $ulos .= ", projekti = ".$tiliointirow['projekti'];
      $ulos .= ", tapvm = '".$tiliointirow['tapvm']."'";
      $ulos .= ", summa = '".$tiliointirow['summa']."'";
      $ulos .= ", summa_valuutassa = '".$tiliointirow['summa_valuutassa']."'";
      $ulos .= ", valkoodi = '".$tiliointirow['valkoodi']."'";
      $ulos .= ", selite = '".$tiliointirow['selite']."'";
      $ulos .= ", vero = '".$tiliointirow['vero']."'";
      $ulos .= ", lukko = '".$tiliointirow['lukko']."'";
      $ulos .= ", korjattu = '".$tiliointirow['korjattu']."'";
      $ulos .= ", korjausaika = '".$tiliointirow['korjausaika']."'";
      $ulos .= ", tosite = ".$tiliointirow['tosite'];
      $ulos .= ", aputunnus = ".$tiliointirow['aputunnus'];
      $ulos .= ", tapahtumatunnus = ".$tiliointirow['tapahtumatunnus'];
      $ulos .= ", tunnus = ".$tiliointirow['tunnus'].";";
      
      fputs($toot, $ulos . "\r\n");   
    }
  } 
          
}
if (is_resource($toot)) {
  fclose($toot);
}