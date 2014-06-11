<?php

/*
 * Siirretään tuotemasterdata Relexiin
 * 4.2 PRODUCT MASTER DATA
*/

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

if (!isset($argv[1]) or $argv[1] == '') {
  die("Yhtiö on annettava!!");
}

ini_set("memory_limit", "5G");

// Otetaan includepath aina rootista
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))).PATH_SEPARATOR."/usr/share/pear");

require 'inc/connect.inc';
require 'inc/functions.inc';

// Yhtiö
$yhtio = mysql_real_escape_string($argv[1]);

// Tallannetan rivit tiedostoon
$filepath = "/tmp/product_update_{$yhtio}_".date("Y-m-d").".csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus epäonnistui: $filepath\n");
}

// Otsikkotieto
$header = "code;name;supplier;group;order_quantity;purchase_price\n";
fwrite($fp, $header);

// Haetaan tuotteet
$query = "SELECT
          tuote.tuoteno,
          tuote.nimitys,
          tuote.try,
          tuote.kehahin
          FROM tuote
          JOIN tuotepaikat ON (tuote.tuoteno = tuotepaikat.tuoteno and tuote.yhtio = tuotepaikat.yhtio)
          WHERE tuote.yhtio     = '$yhtio'
          AND tuote.status     != 'P'
          AND tuote.ei_saldoa   = ''
          AND tuote.tuotetyyppi = ''
          AND tuote.ostoehdotus = ''
          GROUP BY 1,2
          ORDER BY 1,2";
$res = pupe_query($query);

// Kerrotaan montako riviä käsitellään
$rows = mysql_num_rows($res);

echo "Tuoterivejä {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysql_fetch_assoc($res)) {
  // Tuotteen päätoimittaja
  $tulossa_query = "SELECT tuotteen_toimittajat.liitostunnus,
                    tuotteen_toimittajat.toim_tuoteno,
                    tuotteen_toimittajat.osto_era,
                    tuotteen_toimittajat.pakkauskoko
                    FROM tuotteen_toimittajat
                    WHERE tuotteen_toimittajat.yhtio = '{$yhtio}'
                    AND tuotteen_toimittajat.tuoteno = '{$row['tuoteno']}'
                    ORDER BY if(tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys)
                    LIMIT 1";
  $toimres = pupe_query($tulossa_query);
  $toimrow = mysql_fetch_assoc($toimres);

  $rivi  = "{$row['tuoteno']};";
  $rivi .= "{$row['nimitys']};";
  $rivi .= "{$toimrow['liitostunnus']};";
  $rivi .= "{$row['try']};";
  $rivi .= "{$toimrow['osto_era']};";
  $rivi .= "{$row['kehahin']}";
  $rivi .= "\n";

  fwrite($fp, $rivi);

  $k_rivi++;

  if ($k_rivi % 1000 == 0) {
    echo "Käsitellään riviä {$k_rivi}\n";
  }
}

fclose($fp);

echo "Valmis.\n";
