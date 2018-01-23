<?php

/*
 * Siirret‰‰n asiakastiedot Relexiin
 * 5.3 CUSTOMER DATA
*/

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
$useslave = 1;

// Kutsutaanko CLI:st‰
if (php_sapi_name() != 'cli') {
  die ("T‰t‰ scripti‰ voi ajaa vain komentorivilt‰!");
}

if (!isset($argv[1]) or $argv[1] == '') {
  die("Yhtiˆ on annettava!!");
}

ini_set("memory_limit", "5G");

// Otetaan includepath aina rootista
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))).PATH_SEPARATOR."/usr/share/pear");

require 'inc/connect.inc';
require 'inc/functions.inc';

// Logitetaan ajo
cron_log();

$ajopaiva  = date("Y-m-d");
$paiva_ajo = FALSE;
$weekly_ajo = FALSE;
$ajotext = "";
$ftppath = "/data/input";
$extra_asiakastiedot = false;
$crm_asiakastiedot = false;

if (isset($argv[2]) and $argv[2] != '') {

  if (strpos($argv[2], "-") !== FALSE) {
    list($y, $m, $d) = explode("-", $argv[2]);
    if (is_numeric($y) and is_numeric($m) and is_numeric($d) and checkdate($m, $d, $y)) {
      $ajopaiva = $argv[2];
    }
  }

  if (strtoupper($argv[2]) == 'WEEKLY') {
    $weekly_ajo = TRUE;
    $ajotext = "weekly_";
  }
  else {
    $paiva_ajo = TRUE;
  }
}

if (isset($argv[3]) and trim($argv[3]) != '') {
  $ftppath = trim($argv[3]);
}

if (isset($argv[4]) and trim($argv[4]) == 'extra') {
  $extra_asiakastiedot = true;
}

if (isset($argv[4]) and trim($argv[4]) == 'crm') {
  $crm_asiakastiedot = true;
}

// Yhtiˆ
$yhtio = mysql_real_escape_string($argv[1]);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

// Tallennetaan rivit tiedostoon
$filepath = "/tmp/customer_update_{$yhtio}_{$ajotext}{$ajopaiva}.csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus ep‰onnistui: $filepath\n");
}

// Otsikkotieto
if ($extra_asiakastiedot) {
  $header = "code;name;customer_group;custnro;sellerid\n";
}
if ($crm_asiakastiedot) {
  $header = "y-tunnus;nimi;katuosoite;postinumero;postitoimipaikka;maa;s‰hkˆpostiosoite;puhelin;asiakasnumero;asiakasryhm‰ ja ryhm‰n selitys;maksuehto;toimitusehto;myyj‰n nimi tai myyj‰n numero;toimitusosoitteen postitoimipaikka\n";
}
else {
  $header = "code;name;customer_group\n";
}

fwrite($fp, $header);

$asiakasrajaus = "";

// Haetaan aika jolloin t‰m‰ skripti on viimeksi ajettu
$datetime_checkpoint = cron_aikaleima("RELEX_CUST_CRON");

// Otetaan mukaan vain edellisen ajon j‰lkeen muuttuneet
if ($paiva_ajo and $datetime_checkpoint != "") {
  $asiakasrajaus = " AND (asiakas.muutospvm > '$datetime_checkpoint' or asiakas.luontiaika > '$datetime_checkpoint')";
}
elseif ($paiva_ajo) {
  $asiakasrajaus = " AND (asiakas.muutospvm >= date_sub(now(), interval 24 HOUR) or asiakas.luontiaika >= date_sub(now(), interval 24 HOUR))";
}

$asiakastiedot_lisa = "";
if ($extra_asiakastiedot) {
  $asiakastiedot_lisa = ", asiakas.asiakasnro, asiakas.myyjanro";
}
if ($crm_asiakastiedot) {
  $asiakastiedot_lisa = ", asiakas.ytunnus, asiakas.osoite, asiakas.postino, asiakas.postitp, asiakas.maa, asiakas.email, asiakas.puhelin, asiakas.asiakasnro, asiakas.ryhma, asiakas.maksuehto, asiakas.toimitusehto, asiakas.myyjanro, asiakas.toim_postitp";
}

// Haetaan asiakkaat
$query = "SELECT
          yhtio.maa,
          asiakas.tunnus,
          concat_ws(' ', asiakas.nimi, asiakas.nimitark) nimi,
          asiakas.ryhma
          {$asiakastiedot_lisa}
          FROM asiakas
          JOIN yhtio ON (asiakas.yhtio = yhtio.yhtio)
          WHERE asiakas.yhtio = '$yhtio'
          AND asiakas.laji    not in ('P','R')
          {$asiakasrajaus}
          ORDER BY asiakas.tunnus";
$res = pupe_query($query);

// Tallennetaan aikaleima
cron_aikaleima("RELEX_CUST_CRON", date('Y-m-d H:i:s'));

// Kerrotaan montako rivi‰ k‰sitell‰‰n
$rows = mysql_num_rows($res);

echo date("d.m.Y @ G:i:s") . ": Relex asiakasrivej‰ {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysql_fetch_assoc($res)) {

  if (!$crm_asiakastiedot) {
    $rivi  = "{$row['maa']}-{$row['tunnus']};";
    $rivi .= pupesoft_csvstring($row['nimi']).";";
    $rivi .= pupesoft_csvstring($row['ryhma']);
  }

  if ($extra_asiakastiedot) {
    $rivi .= ";".pupesoft_csvstring($row['asiakasnro']);
    $rivi .= ";".pupesoft_csvstring($row['myyjanro']);
  }

  if ($crm_asiakastiedot) {
    $rivi .= pupesoft_csvstring($row['ytunnus']);
    $rivi .= ";".pupesoft_csvstring($row['nimi']);
    $rivi .= ";".pupesoft_csvstring($row['osoite']);
    $rivi .= ";".pupesoft_csvstring($row['postino']);
    $rivi .= ";".pupesoft_csvstring($row['postitp']);
    $rivi .= ";".pupesoft_csvstring($row['maa']);
    $rivi .= ";".pupesoft_csvstring($row['email']);
    $rivi .= ";".pupesoft_csvstring($row['puhelin']);
    $rivi .= ";".pupesoft_csvstring($row['asiakasnro']);
    $rivi .= ";".pupesoft_csvstring($row['ryhma']);
    $rivi .= ";".pupesoft_csvstring($row['maksuehto']);
    $rivi .= ";".pupesoft_csvstring($row['toimitusehto']);
    $rivi .= ";".pupesoft_csvstring($row['myyjanro']);
    $rivi .= ";".pupesoft_csvstring($row['toim_postitp']);
  }

  $rivi .= "\n";

  fwrite($fp, $rivi);

  $k_rivi++;
}

fclose($fp);

// Tehd‰‰n FTP-siirto
if (($paiva_ajo or $weekly_ajo) and !empty($relex_ftphost)) {
  $ftphost = $relex_ftphost;
  $ftpuser = $relex_ftpuser;
  $ftppass = $relex_ftppass;
  $ftpfile = $filepath;
  require "inc/ftp-send.inc";
}

echo date("d.m.Y @ G:i:s") . ": Relex asiakkaat valmis.\n\n";
