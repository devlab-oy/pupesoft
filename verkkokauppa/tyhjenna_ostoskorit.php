<?php

// Kutsutaanko CLI:st�
if (php_sapi_name() != 'cli') {
  die ("T�t� scripti� voi ajaa vain komentorivilt�!");
}

if (!isset($argv[1]) or $argv[1] == '') {
  echo "Anna yhti�!!!\n";
  die;
}

require "../inc/connect.inc";

$yhtio = mysql_real_escape_string($argv[1]);

//  Poistetaan rivi
$query = "DELETE FROM tilausrivi WHERE tyyppi = 'B' and yhtio = '$yhtio'";
$delres = mysql_query($query) or pupe_error($query);
