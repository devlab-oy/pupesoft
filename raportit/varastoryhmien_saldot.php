<?php

/**
 * Varastoryhmien saldojenlukuscripti
 *
 */

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

// lisätään includepathiin pupe-root
$pupe_root_path = dirname(dirname(__FILE__));
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupe_root_path);
ini_set("display_errors", 1);
error_reporting(E_ALL);

require 'inc/connect.inc';
require 'inc/functions.inc';

if (empty($argv[1])) {
  die("Parametrit puuttuu: yhtio\n");
}

$yhtio = mysql_real_escape_string(trim($argv[1]));
$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow = hae_kukarow('admin', $yhtio);

if (!isset($kukarow)) {
  exit("VIRHE: Admin käyttäjä ei löydy!\n");
}

// Logitetaan ajo
cron_log();

echo date("d.m.Y @ G:i:s")." - Varastoryhmien päivitys\n";

// poistetaan kaikki varastoryhma-tuotteen_avainsanat
$query = "DELETE FROM tuotteen_avainsanat
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND laji    like 'VARASTORYHMA%'";
$tuotteen_avainsana_res = pupe_query($query);

$query = "SELECT *
          FROM avainsana
          WHERE yhtio     = '{$kukarow['yhtio']}'
          AND laji        = 'VARASTORYHMA'
          AND selitetark != ''";
$avainsana_res = pupe_query($query);

if (mysql_num_rows($avainsana_res) == 0) {
  echo date("d.m.Y @ G:i:s")." - Varastoryhmiä ei ole perustettu.\n";
}
else {
  $query = "SELECT tuote.tuoteno, ifnull((SELECT isatuoteno FROM tuoteperhe WHERE tuoteperhe.yhtio = tuote.yhtio AND tuoteperhe.isatuoteno = tuote.tuoteno AND tuoteperhe.tyyppi = 'P' LIMIT 1), '') isa
            FROM tuote
            WHERE tuote.yhtio = '$kukarow[yhtio]'";
  $res = pupe_query($query);

  echo date("d.m.Y @ G:i:s")." - Aloitetaan ".mysql_num_rows($res)." tuotteen päivitys. ($kukarow[yhtio])\n";

  while ($row = mysql_fetch_assoc($res)) {

    mysql_data_seek($avainsana_res, 0);

    while ($avainsana_row = mysql_fetch_assoc($avainsana_res)) {
      $varastot = explode(',', $avainsana_row['selitetark']);

      $myytavissa = 0;

      if ($row['isa'] != '') {
        $saldot = tuoteperhe_myytavissa($row["tuoteno"], '', '', $varastot);

        foreach ($saldot as $varasto => $myytavissa_apu) {
          $myytavissa += $myytavissa_apu;
        }
      }
      else {
        list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"], '', $varastot);
      }

      if ($myytavissa > 0) {
        $query = "INSERT INTO tuotteen_avainsanat SET
                  yhtio      = '{$kukarow['yhtio']}',
                  tuoteno    = '{$row['tuoteno']}',
                  kieli      = '{$avainsana_row['kieli']}',
                  laji       = 'VARASTORYHMA_$avainsana_row[selite]',
                  selite     = '$myytavissa',
                  laatija    = '{$kukarow['kuka']}',
                  luontiaika = now(),
                  muutospvm  = now(),
                  muuttaja   = '{$kukarow['kuka']}'";
        $tuotteen_avainsana_res = pupe_query($query);
      }
    }
  }
}
echo date("d.m.Y @ G:i:s")." - Varastoryhmien päivitys. Done!\n\n";
