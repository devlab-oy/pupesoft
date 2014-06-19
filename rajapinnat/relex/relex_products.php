<?php

/*
 * Siirret‰‰n tuotemasterdata Relexiin
 * 4.2 PRODUCT MASTER DATA
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

// Yhtiˆ
$yhtio = mysql_real_escape_string($argv[1]);

// Tallannetan rivit tiedostoon
$filepath = "/tmp/product_update_{$yhtio}_".date("Y-m-d").".csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus ep‰onnistui: $filepath\n");
}

// Otsikkotieto
$header = "code;name;supplier;group;order_quantity;purchase_price;suppliers_code;unit;ean;flag;product_height;product_width;product_length;product_weight\n";
fwrite($fp, $header);

// Haetaan tuotteet
$query = "SELECT
          yhtio.maa,
          tuote.tuoteno,
          tuote.nimitys,
          tuote.try,
          tuote.kehahin,
          upper(tuote.yksikko) yksikko,
          tuote.eankoodi,
          tuote.tahtituote,
          tuote.tuotekorkeus,
          tuote.tuoteleveys,
          tuote.tuotesyvyys,
          tuote.tuotemassa
          FROM tuote
          JOIN yhtio ON (tuote.yhtio = yhtio.yhtio)
          WHERE tuote.yhtio     = '$yhtio'
          AND tuote.status     != 'P'
          AND tuote.ei_saldoa   = ''
          AND tuote.tuotetyyppi = ''
          AND tuote.ostoehdotus = ''
          ORDER BY tuote.tuoteno";
$res = pupe_query($query);

// Kerrotaan montako rivi‰ k‰sitell‰‰n
$rows = mysql_num_rows($res);

echo "Tuoterivej‰ {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysql_fetch_assoc($res)) {
  // Tuotteen p‰‰toimittaja
  $tulossa_query = "SELECT tuotteen_toimittajat.liitostunnus,
                    tuotteen_toimittajat.toim_tuoteno,
                    tuotteen_toimittajat.osto_era,
                    tuotteen_toimittajat.pakkauskoko,
                    tuotteen_toimittajat.ostohinta,
                    tuotteen_toimittajat.alennus,
                    tuotteen_toimittajat.toim_tuoteno
                    FROM tuotteen_toimittajat
                    WHERE tuotteen_toimittajat.yhtio = '{$yhtio}'
                    AND tuotteen_toimittajat.tuoteno = '{$row['tuoteno']}'
                    ORDER BY if(tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys)
                    LIMIT 1";
  $toimres = pupe_query($tulossa_query);
  $toimrow = mysql_fetch_assoc($toimres);

  if ($toimrow['liitostunnus'] > 0) {
     $toimrow['liitostunnus'] = $row['maa']."-".$toimrow['liitostunnus'];
  }

  $rivi  = pupesoft_csvstring($row['tuoteno']).";";
  $rivi .= pupesoft_csvstring($row['nimitys']).";";
  $rivi .= "{$toimrow['liitostunnus']};";
  $rivi .= pupesoft_csvstring($row['try']).";";
  $rivi .= "{$toimrow['osto_era']};";
  $rivi .= "{$row['kehahin']};";
  $rivi .= "{$toimrow['toim_tuoteno']};";
  $rivi .= "{$row['yksikko']};";
  $rivi .= "{$row['eankoodi']};";
  $rivi .= "{$row['tahtituote']};";
  $rivi .= "{$row['tuotekorkeus']};";
  $rivi .= "{$row['tuoteleveys']};";
  $rivi .= "{$row['tuotesyvyys']};";
  $rivi .= "{$row['tuotemassa']}";
  $rivi .= "\n";

  fwrite($fp, $rivi);

  $k_rivi++;

  if ($k_rivi % 1000 == 0) {
    echo "K‰sitell‰‰n rivi‰ {$k_rivi}\n";
  }
}

fclose($fp);

echo "Valmis.\n";
