<?php

// verkkolasku.php
//
// tarvitaan $kukarow ja $yhtiorow
//
// P�ivitet��n laskutettujen myyntien kesken j��neet varastosiirrot vastaanotetuiksi
//
// jos ollaan saatu komentorivilt? parametrej?
// $yhtio ja $laskutusp�iv� --> komentorivilt? pit?? tulla parametrein?

//$silent = '';
// Pit�isik� laittaa  menusta ajettavaksi?

ini_set("memory_limit", "5G");

// Kutsutaanko CLI:st?, jos setataan force_web saadaan aina "ei cli" -versio
$force_web = (isset($force_web) and $force_web === true);
$php_cli = ((php_sapi_name() == 'cli' or isset($editil_cli)) and $force_web === false);

if ($php_cli) {

  if (empty($argv[1])) {
    echo "Anna yhti�!!!\n";
    exit(1);
  }

  // otetaan includepath aina rootista
  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(__FILE__)).PATH_SEPARATOR."/usr/share/pear");
  error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
  ini_set("display_errors", 0);

  // otetaan tietokanta connect
  require "inc/connect.inc";
  require "inc/functions.inc";

  // Logitetaan ajo
  cron_log();

  $_yhtio   = pupesoft_cleanstring($argv[1]);
  $yhtiorow = hae_yhtion_parametrit($_yhtio);

  if (!isset($kukarow)) {
    $kukarow = hae_kukarow('admin', $yhtiorow['yhtio']);

    // Komentorivilt� ku ajetaan, niin ei haluta posteja admin-k�ytt�j�lle
    $kukarow["eposti"] = "";
  }

  if (empty($argv[2])) {
    echo "Anna p�iv�m��r�, jota vanhemmat kirjanpidolliset varastosiirrot vied��n loppuun!!!\n";
    exit(1);
  }

  $_pvm = pupesoft_cleanstring($argv[2]);
  $_pvm .= ' 99:99:99';
  $_poikkeavalaskutuspvm = '';

}
else {
    exit(1);
}

$lkm = 0;
require 'tilauskasittely/tilauksesta_varastosiirto.inc';

$query = "SELECT myynti.tunnus mytunnus, myynti.laskunro mylaskunro, SUBSTRING(lasku.viesti, 38) nro, 
          lasku.tunnus, myynti.varastosiirto_tunnus, lasku.* 
          from lasku 
	        join lasku as myynti on myynti.yhtio = lasku.yhtio 
	        and myynti.tunnus = SUBSTRING(lasku.viesti, 38)
          and myynti.tila = 'L'
          and myynti.alatila = 'X'
	        where lasku.yhtio = '{$yhtiorow['yhtio']}'
          and lasku.tila = 'G' 
	        and lasku.alatila = ''
          and lasku.luontiaika <= '{$_pvm}'
	        and lasku.chn='KIR'";
$varres = pupe_query($query);
          
while ($varastosiirto = mysql_fetch_assoc($varres)) {

    $query = "SELECT * from lasku 
  	        where lasku.yhtio = '{$yhtiorow['yhtio']}' 
            and lasku.tunnus = '$varastosiirto[mytunnus]'";

    $myyres = pupe_query($query);
    if (mysql_num_rows($myyres) > 0) {
      $myyntitilaus = mysql_fetch_assoc($myyres);
      $varastosiirtorivit = hae_tilausrivit($varastosiirto['tunnus'], 'K', false);
 
      $varastosiirtorivit = aseta_varastosiirto_vastaanotetuksi($varastosiirto, $varastosiirtorivit, $_poikkeavalaskutuspvm);
            
      #echo "Varastosiirto $varastosiirto[tunnus] vastaanotettu (Myynti: $varastosiirto[mytunnus], $varastosiirto[mylaskunro])! \n\n";
      $lkm += 1;
    }  
}

echo "Varastosiirtoja vastaanotettu $lkm kappaletta! \n\n";
