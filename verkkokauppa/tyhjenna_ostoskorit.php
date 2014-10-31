<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

if (!isset($argv[1]) or $argv[1] == '') {
  echo "Anna yhtiö!!!\n";
  die;
}

require "../inc/connect.inc";
require "../inc/functions.inc";

// Logitetaan ajo
cron_log();

$yhtio = mysql_real_escape_string($argv[1]);

//  Poistetaan rivi
$query = "DELETE FROM tilausrivi WHERE tyyppi = 'B' and yhtio = '$yhtio'";
$delres = pupe_query($query);
