<?php
/**
 * p‰ivitet‰‰n korvaavuusketjun p‰‰tuotteen tarvittaessa hinnastoon kyll‰ksi
 * - jos lˆytyy tuote jonka myyt‰viss‰ on nolla
 * - jos tuotteen hinnastoon='K' hinnastoon='' ja hinnastoon='W'
 * - tuote on korvaavuusketjussa
 * - tuote ei ole korvaavuusketjun p‰‰tuote
 * - korvaavuusketjun p‰‰tuotteella on saldoa
 * - korvaavuusketjun p‰‰tuottella on hinnastoon ei
 */

// Kutsutaanko CLI:st‰
if (php_sapi_name() != 'cli') {
  die("T‰t‰ scripti‰ voi ajaa vain komentorivilt‰!\n");
}

// Yhtiˆ annettava parametriksi
if (trim($argv[1]) == '') {
  echo "K‰yttˆ: {$_SERVER['SCRIPT_NAME']} [yhtion_nimi]\n";
  exit;
}

require "inc/connect.inc";
require "inc/functions.inc";

// Logitetaan ajo
cron_log();

$kukarow['yhtio'] = (string) $argv[1];
$kukarow['kuka']  = 'admin';
$kukarow['kieli'] = 'fi';
$kukarow['extranet'] = '';

echo "P‰ivitet‰‰n korvaavuusketjun p‰‰tuotteet hinnastoon...\n\n";

// Haetaan kaikki korvaavuusketjujen id:t
$select = "SELECT DISTINCT(id) FROM korvaavat WHERE yhtio='{$kukarow['yhtio']}'";
$result = pupe_query($select);

// Laskurimuuttujat
$yhteensa = 0;
$muutettu = 0;

// Loopataan kaikki ketjut l‰pi ja etsit‰‰n p‰ivitett‰vi‰ tuotteita
while ($ketju = mysql_fetch_assoc($result)) {

  // Haetaan ketjun tuotteet
  $tuotteet_query = "SELECT korvaavat.id, korvaavat.tuoteno, korvaavat.jarjestys, tuote.hinnastoon
                     FROM korvaavat
                     JOIN tuote on (tuote.yhtio=korvaavat.yhtio and tuote.tuoteno=korvaavat.tuoteno)
                     WHERE korvaavat.yhtio='{$kukarow['yhtio']}'
                     AND id='{$ketju['id']}'
                     ORDER BY if(jarjestys=0, 9999, jarjestys), tuoteno;";
  $tuotteet_result = pupe_query($tuotteet_query);

  // Eka tuote on AINA ketjun p‰‰tuote
  $paa_tuote = mysql_fetch_assoc($tuotteet_result);

  // Jos p‰‰tuotteen tuote.hinnastoon on 'E'
  if ($paa_tuote['hinnastoon'] == 'E') {

    $myytavissa = saldo_myytavissa($paa_tuote['tuoteno']);

    // Jos p‰‰tuotteella on saldoa
    if ($myytavissa[0] > 0) {
      $yhteensa += 1;

      $paivitetaanko = false;

      // Loopataan ketjun tuotteet
      while ($ketjun_tuote = mysql_fetch_assoc($tuotteet_result)) {
        $tuote_myytavissa = saldo_myytavissa($ketjun_tuote['tuoteno']);

        // P‰ivitet‰‰n p‰‰tuote hinnastoon jos ketjussa on tuotteita joiden myytavissa on 0,
        // ja hinnastoon KYLLƒ
        if ($tuote_myytavissa[2] == 0 and $ketjun_tuote['hinnastoon'] != 'E') {
          $paivitetaanko = true;
        }
      }

      // Jos hinnastoon on setattu, niin ketjun p‰‰tuote pit‰‰ p‰ivitt‰‰
      if ($paivitetaanko) {

        // P‰ivitet‰‰n tuote.hinnastoon
        $query = "UPDATE tuote SET hinnastoon='' WHERE yhtio='{$kukarow['yhtio']}' AND tuoteno='{$paa_tuote['tuoteno']}'";
        if ($result2 = pupe_query($query)) {
          $muutettu += 1;
        }

        echo "Ketjun $paa_tuote[id] p‰‰tuote $paa_tuote[tuoteno] p‰ivitetty hinnastoon kyll‰ksi.\n";
      }
    }
  }
}
echo "\nYhteens‰ $yhteensa sopivaa ketjua, joista muutettiin $muutettu tuotetta hinnastoon.\n";
