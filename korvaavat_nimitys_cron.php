<?php

/*
- Loopataan kaikki ketjut l�pi
- Jos ketjun tuotteella saldo 0, tuote ei ole ketjun ykk�stuote = laitetaan j�rjestykselt� pienempi nimitykseen, esim "KORVAAVA A2".
- Jos j�rjestys on kuitenkin 0, niin laitetaan "Korvaava "ykk�stuote"". Eli p��tuote ketjusta menee silloin nimitykseen.
- Tehd��n tsekki, ett� jos tuotteella on nimitys jo muutettu, niin ei teh� mit��n.
*/

// Kutsutaanko CLI:st�
if (php_sapi_name() != 'cli') {
  die("T�t� scripti� voi ajaa vain komentorivilt�\n");
}

// Yhti� annettava parametriksi
if (!isset($argv[1])) {
  echo "K�ytt�: {$_SERVER['SCRIPT_NAME']} [yhtion_nimi]\n";
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

// Laskurimuuttujat
$yhteensa = 0;
$muutettu = 0;
$poistettu = 0;

echo date("d.m.Y @ G:i:s") . ": P�ivitet��n korvaavuusketjun nimitykset ...\n";

// Haetaan tuoteketjut
$select = "SELECT distinct id
           FROM korvaavat
           WHERE yhtio='{$kukarow['yhtio']}'";
$result = pupe_query($select);

// Loopataan ketjut l�pi
while ($ketju = mysql_fetch_assoc($result)) {

  // Haetaan ketjun tuotteet
  $tuotteet_query = "SELECT korvaavat.id, korvaavat.tuoteno, korvaavat.jarjestys, tuote.nimitys
                     FROM korvaavat
                     JOIN tuote ON (tuote.yhtio = korvaavat.yhtio AND tuote.tuoteno = korvaavat.tuoteno)
                     WHERE korvaavat.yhtio='{$kukarow['yhtio']}'
                     AND korvaavat.id='{$ketju['id']}'
                     ORDER BY if(korvaavat.jarjestys=0, 9999, korvaavat.jarjestys), korvaavat.tuoteno";
  $tuotteet_result = pupe_query($tuotteet_query);

  // Poislukien p��tuote
  $paa_tuote = mysql_fetch_assoc($tuotteet_result);
  $edellinen_tuote = $paa_tuote;

  // Loopataan ketjun muut tuotteet l�pi
  while ($tuote = mysql_fetch_assoc($tuotteet_result)) {

    // Muutetaan tuotteen nimitys
    // Jos tuotteen j�rjestys on 0, laitetaan p��tuote, muuten edellinen
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

        // Ajetaan p�ivitysquery ja poistetaan tuote vastaavuusketjuista
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

echo date("d.m.Y @ G:i:s") . ": Yhteens� $yhteensa sopivaa tuotetta, joista muutettiin $muutettu nimityst�\n";
echo date("d.m.Y @ G:i:s") . ": Vastaavat ketjuista poistettiin $poistettu tuotetta\n\n";
