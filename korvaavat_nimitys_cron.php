<?php

/*
- Loopataan kaikki ketjut läpi
- Jos ketjun tuotteella saldo 0, tuote ei ole ketjun ykköstuote = laitetaan järjestykseltä pienempi nimitykseen, esim "KORVAAVA A2".
- Jos järjestys on kuitenkin 0, niin laitetaan "Korvaava "ykköstuote"". Eli päätuote ketjusta menee silloin nimitykseen.
- Tehdään tsekki, että jos tuotteella on nimitys jo muutettu, niin ei tehä mitään.
*/

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die("Tätä scriptiä voi ajaa vain komentoriviltä\n");
}

// Yhtiö annettava parametriksi
if (!isset($argv[1])) {
  echo "Käyttö: {$_SERVER['SCRIPT_NAME']} [yhtion_nimi]\n";
  exit;
}

require "inc/connect.inc";
require "inc/functions.inc";

$kukarow['yhtio'] = (string) $argv[1];
$kukarow['kuka']  = 'admin';
$kukarow['kieli'] = 'fi';
$kukarow['extranet'] = '';

// Laskurimuuttujat
$yhteensa = 0;
$muutettu = 0;
$poistettu = 0;

echo "Päivitetään korvaavuusketjun nimitykset ...\n\n";

// Haetaan tuoteketjut
$select = "SELECT distinct id
 FROM korvaavat
 WHERE yhtio='{$kukarow['yhtio']}'";
$result = pupe_query($select);

// Loopataan ketjut läpi
while($ketju = mysql_fetch_assoc($result)) {

  // Haetaan ketjun tuotteet
  $tuotteet_query = "SELECT korvaavat.id, korvaavat.tuoteno, korvaavat.jarjestys, tuote.nimitys
                     FROM korvaavat
                     JOIN tuote ON (tuote.yhtio = korvaavat.yhtio AND tuote.tuoteno = korvaavat.tuoteno)
                     WHERE korvaavat.yhtio='{$kukarow['yhtio']}'
                     AND korvaavat.id='{$ketju['id']}'
                     ORDER BY if(korvaavat.jarjestys=0, 9999, korvaavat.jarjestys), korvaavat.tuoteno";
  $tuotteet_result = pupe_query($tuotteet_query);

  // Poislukien päätuote
  $paa_tuote = mysql_fetch_assoc($tuotteet_result);
  $edellinen_tuote = $paa_tuote;

  // Loopataan ketjun muut tuotteet läpi
  while ($tuote = mysql_fetch_assoc($tuotteet_result)) {

    // Muutetaan tuotteen nimitys
    // Jos tuotteen järjestys on 0, laitetaan päätuote, muuten edellinen
    if ($tuote['jarjestys'] == 0) $tuoteno = $paa_tuote['tuoteno'];
    else $tuoteno = $edellinen_tuote['tuoteno'];

    $uusi_nimitys = "KORVAAVA " . $tuoteno;

    if ($uusi_nimitys != $tuote['nimitys']) {

      // Tuotteen saldo
      $myytavissa = saldo_myytavissa($tuote['tuoteno']);

      // Huomioidaan vain tuotteet joilla saldo on nolla
      if ($myytavissa[0] == 0) {

        $muutos_query = "UPDATE tuote SET
                         nimitys='$uusi_nimitys'
                         WHERE yhtio='{$kukarow['yhtio']}'
                         AND tuoteno='{$tuote['tuoteno']}'";

        // Ajetaan päivitysquery ja poistetaan tuote vastaavuusketjuista
        if (pupe_query($muutos_query)) {
          $muutettu++;

          $poista_vastaavat_query = "DELETE FROM vastaavat
                                     WHERE yhtio='{$kukarow['yhtio']}'
                                     AND tuoteno='{$tuote['tuoteno']}'";

          if (pupe_query($poista_vastaavat_query)) {
             $poistettu++;
          }
        }
      }
    }

    // Laitetaan ketjun edellinen tuote talteen
    $edellinen_tuote = $tuote;
  }

  $yhteensa++;
}

echo "\nYhteensä $yhteensa sopivaa tuotetta, joista muutettiin $muutettu nimitystä\n";
echo "Vastaavat ketjuista poistettiin $poistettu tuotetta\n";
