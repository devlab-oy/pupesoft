<?php

// Kutsutaanko CLI:st�
if (php_sapi_name() != 'cli') {
  die ("T�t� scripti� voi ajaa vain komentorivilt�!\n");
}

date_default_timezone_set('Europe/Helsinki');

if (trim($argv[1]) == '') {
  die ("Et antanut yhti�t�!\n");
}

// lis�t��n includepathiin pupe-root
ini_set("include_path", ini_get("include_path") . PATH_SEPARATOR . dirname(__FILE__));

// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";

// Logitetaan ajo
cron_log();

// Sallitaan vain yksi instanssi t�st� skriptist� kerrallaan
pupesoft_flock();

$yhtio    = mysql_real_escape_string(trim($argv[1]));
$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

$query = "SELECT tuote.tuoteno
          FROM tuote
          JOIN tuotepaikat ON (tuotepaikat.yhtio = tuote.yhtio
            AND tuotepaikat.tuoteno = tuote.tuoteno)
          WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
          AND tuote.status != 'P'
          AND tuote.hinnastoon != 'E'";

$result = pupe_query($query);

while ($row = mysql_fetch_assoc($result)) {
  $saldo_myytavissa = saldo_myytavissa($row["tuoteno"]);
  $saldo_myytavissa = $saldo_myytavissa[2];

  $query = "UPDATE tuotepaikat SET
            myytavissa_static = '{$saldo_myytavissa}'
            WHERE yhtio = '{$kukarow["yhtio"]}'
            AND tuoteno = '{$row["tuoteno"]}'";

  pupe_query($query);
}
