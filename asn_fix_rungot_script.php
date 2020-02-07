<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

if (trim($argv[1]) == '') {
  echo "Et antanut yhtiötä!\n";
  exit;
}

require "inc/connect.inc";
require "inc/functions.inc";

// Logitetaan ajo
cron_log();

$kukarow['yhtio'] = (string) $argv[1];
$kukarow['kuka']  = 'admin';

$query = "SELECT DISTINCT tilausrivi.tunnus,
          tilausrivi.tuoteno,
          tilausrivi.tilkpl,
          tilausrivi.varattu,
          tilausrivi.kpl,
          tilausrivi.perheid,
          tilausrivi.uusiotunnus,
          tilausrivi.hinta,
          tilausrivi.tilaajanrivinro,
          t.tilaajanrivinro as tilaajanrivinro2
          FROM tilausrivi
          JOIN tuoteperhe ON (tuoteperhe.yhtio = tilausrivi.yhtio AND tilausrivi.tuoteno = tuoteperhe.tuoteno AND tuoteperhe.tyyppi IN ('P','') AND tuoteperhe.ohita_kerays != '')
          JOIN tilausrivi t on (t.yhtio = tilausrivi.yhtio and t.tunnus = tilausrivi.perheid and t.tilaajanrivinro != 0)
          WHERE tilausrivi.yhtio='artr'
          AND tilausrivi.tyyppi       = 'O'
          AND tilausrivi.perheid      > 0
          AND tilausrivi.tunnus      != tilausrivi.perheid
          AND tilausrivi.uusiotunnus  > 0";
$res = pupe_query($query);

echo "\n";

while ($row = mysql_fetch_assoc($res)) {

  echo "Etsittävä: $row[tuoteno] ==> ";

  $query = "SELECT *
            FROM asn_sanomat
            WHERE yhtio      = '{$kukarow['yhtio']}'
            AND laji         = 'asn'
            AND tuoteno      = '{$row['tuoteno']}'
            AND kappalemaara = ($row[varattu] + $row[kpl])
            AND tilausrivi   LIKE '%{$row['tunnus']}%'
            LIMIT 1";
  $asnres = pupe_query($query);

  if (mysql_num_rows($asnres) == 0) {

    $query = "SELECT tuoteno
              FROM tilausrivi
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$row['perheid']}'";
    $tuotenores = pupe_query($query);
    $tuotenorow = mysql_fetch_assoc($tuotenores);

    $query = "SELECT *
              FROM asn_sanomat
              WHERE yhtio    = '{$kukarow['yhtio']}'
              AND laji       = 'asn'
              AND (tuoteno = '{$tuotenorow['tuoteno']}' OR toim_tuoteno = '{$tuotenorow['tuoteno']}' OR toim_tuoteno2 = '{$tuotenorow['tuoteno']}')
              AND tilausrivi LIKE '%{$row['perheid']}%'";
    $isa_chk_res = pupe_query($query);

    echo "(".mysql_num_rows($isa_chk_res).") ";
    $isa_chk_row = mysql_fetch_assoc($isa_chk_res);

    echo "ISA LOYTYI $isa_chk_row[tuoteno] ";

    // Tehdään uusi rivi, jossa on jäljelle jääneet kappaleet
    $fields = "yhtio";
    $values = "'{$kukarow['yhtio']}'";

    // Ei monisteta tunnusta
    for ($ii = 1; $ii < mysql_num_fields($isa_chk_res) - 1; $ii++) {

      $fieldname = mysql_field_name($isa_chk_res, $ii);

      $fields .= ", ".$fieldname;

      switch ($fieldname) {
      case 'tilausrivi':
        $values .= ", '{$row['tunnus']}'";
        break;
      case 'tuoteno':
      case 'toim_tuoteno':
      case 'toim_tuoteno2':
        $values .= ", '{$row['tuoteno']}'";
        break;
      case 'hinta':
        $values .= ", '{$row['hinta']}'";
        break;
      default:
        $values .= ", '".$isa_chk_row[$fieldname]."'";
      }
    }

    $kysely  = "INSERT INTO asn_sanomat ({$fields}) VALUES ({$values})";
    echo " ==> $kysely\n";
    //$uusires = pupe_query($kysely);

  }

  while ($asnrow = mysql_fetch_assoc($asnres)) {
    echo "$asnrow[tuoteno]";
  }

  echo "\n";


}
echo "\n";
