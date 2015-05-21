<?php

// Kutsutaanko CLI:st‰
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
  $php_cli = TRUE;
}

date_default_timezone_set('Europe/Helsinki');

// Kutsutaanko CLI:st‰
if (!$php_cli) {
  die ("T‰t‰ scripti‰ voi ajaa vain komentorivilt‰!");
}

$pupe_root_polku = dirname(dirname(__FILE__));

require "{$pupe_root_polku}/inc/connect.inc";
require "{$pupe_root_polku}/inc/functions.inc";

$lock_params = array(
  "locktime" => 5400
);

// Logitetaan ajo
cron_log();

// Sallitaan vain yksi instanssi t‰st‰ skriptist‰ kerrallaan
pupesoft_flock($lock_params);

// Laitetaan unlimited execution time
ini_set("max_execution_time", 0);

if (trim($argv[1]) != '') {
  $yhtio = mysql_real_escape_string($argv[1]);
  $yhtiorow = hae_yhtion_parametrit($yhtio);
  $kukarow = hae_kukarow('admin', $yhtio);

  if ($kukarow === null) {
    die ("\n");
  }
}
else {
  die ("Et antanut yhtiˆt‰.\n");
}

$ajetaanko_kaikki = (isset($argv[2]) and trim($argv[2]) != '') ? "YES" : "NO";

if (!isset($verkkokauppa_saldo_varasto)) $verkkokauppa_saldo_varasto = array();

if (count($verkkokauppa_saldo_varasto) == 0) {
  echo "verkkokauppa_saldo_varasto pit‰‰ m‰‰ritell‰!\n";
  exit;
}

// Haetaan aika jolloin t‰m‰ skripti on viimeksi ajettu
$datetime_checkpoint = cron_aikaleima("MYY_STATIC_CRON");

echo date("d.m.Y @ G:i:s")." - Aloitetaan myytavissa_static-p‰ivitys.\n";
echo date("d.m.Y @ G:i:s")." - Haetaan saldot.\n";

if ($datetime_checkpoint != "" and $ajetaanko_kaikki == "NO") {
  $muutoslisa1 = "AND tapahtuma.laadittu  >= '{$datetime_checkpoint}'";
  $muutoslisa2 = "AND tilausrivi.laadittu >= '{$datetime_checkpoint}'";
  $muutoslisa3 = "AND tuote.muutospvm     >= '{$datetime_checkpoint}'";

  // Haetaan saldot tuotteille, joille on tehty tunnin sis‰ll‰ tilausrivi tai tapahtuma
  $query =  "(SELECT tapahtuma.tuoteno
              FROM tapahtuma
              JOIN tuote ON (tuote.yhtio = tapahtuma.yhtio
                AND tuote.tuoteno      = tapahtuma.tuoteno
                AND tuote.status      != 'P'
                AND tuote.tuotetyyppi  NOT in ('A','B')
                AND tuote.tuoteno     != ''
                AND tuote.nakyvyys    != '')
              WHERE tapahtuma.yhtio    = '{$kukarow["yhtio"]}'
              $muutoslisa1)

              UNION

              (SELECT tilausrivi.tuoteno
              FROM tilausrivi
              JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio
                AND tuote.tuoteno      = tilausrivi.tuoteno
                AND tuote.status      != 'P'
                AND tuote.tuotetyyppi  NOT in ('A','B')
                AND tuote.tuoteno     != ''
                AND tuote.nakyvyys    != '')
              WHERE tilausrivi.yhtio   = '{$kukarow["yhtio"]}'
              $muutoslisa2)

              UNION

              (SELECT tuote.tuoteno
              FROM tuote
              WHERE tuote.yhtio        = '{$kukarow["yhtio"]}'
              AND tuote.status        != 'P'
              AND tuote.tuotetyyppi    NOT in ('A','B')
              AND tuote.tuoteno       != ''
              AND tuote.nakyvyys      != ''
              $muutoslisa3)

              ORDER BY 1";
}
else {
  $query =  " SELECT tuote.tuoteno
              FROM tuote
              WHERE tuote.yhtio      = '{$kukarow["yhtio"]}'
              AND tuote.status      != 'P'
              AND tuote.tuotetyyppi NOT in ('A','B')
              AND tuote.tuoteno     != ''
              AND tuote.nakyvyys    != ''";
}

$result = pupe_query($query);

while ($row = mysql_fetch_assoc($result)) {
  foreach($verkkokauppa_saldo_varasto as $varasto) {
    $query =  " SELECT hyllyalue, hyllynro, hyllyvali, hyllytaso, tunnus
                FROM tuotepaikat
                WHERE yhtio = '{$kukarow["yhtio"]}'
                AND tuoteno = '{$row["tuoteno"]}'
                AND varasto = '$varasto'";
    $tpres = pupe_query($query);

    while ($tprow = mysql_fetch_assoc($tpres)) {
      list(, , $myytavissa) = saldo_myytavissa($row["tuoteno"], '', '', '', $tprow["hyllyalue"], $tprow["hyllynro"], $tprow["hyllyvali"], $tprow["hyllytaso"]);

      $query =  " UPDATE tuotepaikat
                  SET myytavissa_static = '$myytavissa'
                  WHERE tunnus = '{$tprow["tunnus"]}'";
      pupe_query($query);
    }
  }
}

// Kun kaikki onnistui, p‰ivitet‰‰n lopuksi timestamppi talteen
cron_aikaleima("MYY_STATIC_CRON", date('Y-m-d H:i:s'));
