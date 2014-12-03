<?php

// Kutsutaanko CLI:st‰
if (php_sapi_name() != 'cli') {
  echo "T‰t‰ scripti‰ voi ajaa vain komentorivilt‰!";
  exit(1);
}

// komentorivilt‰ pit‰‰ tulla parametrin‰ yhtio
if (!isset($argv[1]) or trim($argv[1]) == '') {
  echo "Ensimm‰inen parametri yhtio!";
  exit(1);
}

date_default_timezone_set('Europe/Helsinki');

// otetaan tietokanta connect
require "inc/connect.inc";
require "inc/functions.inc";

// Logitetaan ajo
cron_log();

// Yhtiˆ komentorivilt‰
$yhtio = mysql_real_escape_string($argv[1]);
$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow = hae_kukarow('admin', $yhtiorow['yhtio']);

if (!isset($yhtiorow['extranet_tilaus_varaa_saldoa'])) {
  die ("Yhtiˆll‰ ei ole parametria extranet_tilaus_varaa_saldoa!\n");
}

$laskuri_jt = 0;
$laskuri_norm = 0;

$query = "SELECT lasku.tunnus laskutunnus, asiakas.extranet_tilaus_varaa_saldoa
          FROM lasku
          JOIN kuka ON (kuka.yhtio = lasku.yhtio
            AND kuka.kuka          = lasku.laatija
            AND kuka.extranet     != '')
          JOIN asiakas ON (asiakas.yhtio = lasku.yhtio
            AND asiakas.tunnus     = lasku.liitostunnus)
          WHERE lasku.yhtio        = '{$kukarow['yhtio']}'
          AND lasku.tila           = 'N'
          AND lasku.tilaustyyppi  != 'H'
          AND lasku.alatila        = ''
          AND lasku.clearing    NOT IN ('EXTENNAKKO','EXTTARJOUS')";
$result = pupe_query($query);

while ($row = mysql_fetch_assoc($result)) {

  $aikaraja = 0;

  // tyhj‰ = Yhtiˆn oletus
  // X = Extranet-tilaus varaa saldoa
  // numero = Extranet-tilaus varaa saldoa x tuntia
  // E = Extranet-tilaus ei varaa saldoa
  // E-asetuksella  annetaan kuitenkin tunti varausaikaa siivouksen yhteydess‰

  $asiakkaan_asetus = $row['extranet_tilaus_varaa_saldoa'];
  $yhtion_asetus = $yhtiorow['extranet_tilaus_varaa_saldoa'];

  if ($asiakkaan_asetus != 'X') {
    if ($asiakkaan_asetus == 'E') {
      $aikaraja = 1;
    }
    elseif ($asiakkaan_asetus == '') {
      if ($yhtion_asetus == 'E') {
        $aikaraja = 1;
      }
      else {
        $aikaraja = (int) $yhtion_asetus;
       }
    }
    else {
      $aikaraja = (int) $asiakkaan_asetus;
    }
  }
  else {
    continue;
  }

  if ($aikaraja <= 0) {
    continue;
  }

  // laitetaan kaikki poimitut extranet jt-rivit takaisin omille vanhoille tilauksille
  // laitetaan myˆs ei poimitut extranet jt-rivit varattu = 0
  $query = "SELECT tilausrivi.tunnus,
            tilausrivin_lisatiedot.vanha_otunnus,
            tilausrivin_lisatiedot.positio,
            tilausrivi.var,
            sum(if(tilausrivi.laadittu < DATE_SUB(now(), INTERVAL $aikaraja HOUR), 0, 1)) laskuri
            FROM tilausrivi
            LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
              AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
            WHERE tilausrivi.yhtio                        = '{$kukarow['yhtio']}'
            AND tilausrivi.otunnus                        = '{$row['laskutunnus']}'
            AND tilausrivi.varattu                        > 0
            GROUP BY 1, 2, 3, 4
            HAVING laskuri = 0";
  $rivi_check_res = pupe_query($query);

  $jt_saldo_lisa = $yhtiorow["varaako_jt_saldoa"] == "" ? ", jt = varattu, varattu = 0 " : '';

  while ($rivi_check_row = mysql_fetch_assoc($rivi_check_res)) {

    if ($rivi_check_row['positio'] == 'JT') {
      $query = "UPDATE tilausrivi SET
                otunnus     = '{$rivi_check_row['vanha_otunnus']}',
                var         = 'J'
                $jt_saldo_lisa
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$rivi_check_row['tunnus']}'";
      $rivi_res = pupe_query($query);
      $laskuri_jt++;
    }
    else {
      $query = "UPDATE tilausrivi SET
                varattu     = 0
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$rivi_check_row['tunnus']}'";
      $rivi_res = pupe_query($query);

      $query = "UPDATE tilausrivin_lisatiedot SET
                positio              = 'Ei varaa saldoa'
                WHERE yhtio          = '{$kukarow['yhtio']}'
                AND tilausrivitunnus = '{$rivi_check_row['tunnus']}'";
      $rivi_res = pupe_query($query);
      $laskuri_norm++;
    }
  }
}

if ($laskuri_jt > 0 or $laskuri_norm > 0) {
  echo date("Y-m-d H:i:s");
  echo ": Palautettiin $laskuri_jt jt-rivi‰ alkuper‰isille extranet-tilauksille";
  echo " ja $laskuri_norm tilausrivi‰ ei en‰‰ varaa saldoa.\n";
}
