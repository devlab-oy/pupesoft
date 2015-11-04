<?php

/*
 * Siirretään kirjanpidon tiliöinnit
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

$scp_siirto = "";

if (!empty($argv[2])) {
  $scp_siirto = $argv[2];
}

ini_set("memory_limit", "5G");

// Otetaan includepath aina rootista
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))));

require 'inc/connect.inc';
require 'inc/functions.inc';

// Logitetaan ajo
cron_log();

// Yhtiö
$yhtio = mysql_real_escape_string($argv[1]);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

// Tallennetaan rivit tiedostoon
$filepath = "/tmp/general_ledger_{$yhtio}_".date("Y-m-d").".csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus epäonnistui: $filepath\n");
}

// Otsikkotieto
$header  = "Add/Del;";
$header .= "Date;";
$header .= "Sum;";
$header .= "Account number;";
$header .= "Cost center;";
$header .= "Target;";
$header .= "Project;";
$header .= "Description;";
$header .= "VAT percent;";
$header .= "Voucher id;";
$header .= "Voucher type;";
$header .= "Created;";
$header .= "Created at;";
$header .= "Deleted;";
$header .= "Deleted at";
$header .= "\n";

fwrite($fp, $header);

$rajaus1 = " AND tiliointi.laadittu >= '2013-01-01 00:00:00'";
$rajaus2 = " AND tiliointi.korjausaika >= '2013-01-01 00:00:00'";

// Haetaan aika jolloin tämä skripti on viimeksi ajettu
$datetime_checkpoint = cron_aikaleima("CGNS_LEDG_CRON");

// Otetaan mukaan vain edellisen ajon jälkeen tehdyt tapahtumat
if ($datetime_checkpoint != "") {
  $rajaus1 = " AND tiliointi.laadittu > '$datetime_checkpoint'";
  $rajaus2 = " AND tiliointi.korjausaika > '$datetime_checkpoint'";
}

// Haetaan tiliöinnit
$query = "(SELECT
           'ADD' tyyppi,
           tiliointi.laatija,
           tiliointi.laadittu,
           tiliointi.ltunnus,
           tiliointi.tilino,
           k1.nimi kustp,
           k2.nimi kohde,
           k3.nimi projekti,
           tiliointi.tapvm,
           tiliointi.summa,
           tiliointi.selite,
           tiliointi.vero,
           tiliointi.korjattu,
           if(tiliointi.korjausaika=0, '', tiliointi.korjausaika) korjausaika,
           lasku.tila,
           lasku.alatila,
           lasku.tunnus
           FROM tiliointi
           LEFT JOIN kustannuspaikka as k1 ON (k1.yhtio = tiliointi.yhtio AND k1.tunnus = tiliointi.kustp)
           LEFT JOIN kustannuspaikka as k2 ON (k2.yhtio = tiliointi.yhtio AND k2.tunnus = tiliointi.kohde)
           LEFT JOIN kustannuspaikka as k3 ON (k3.yhtio = tiliointi.yhtio AND k3.tunnus = tiliointi.projekti)
           JOIN lasku on (tiliointi.yhtio = lasku.yhtio and tiliointi.ltunnus = lasku.tunnus)
           WHERE tiliointi.yhtio = '{$yhtio}'
           {$rajaus1}
           AND korjattu          = '')

           UNION

           (SELECT
           'DELETE' tyyppi,
           tiliointi.laatija,
           tiliointi.laadittu,
           tiliointi.ltunnus,
           tiliointi.tilino,
           k1.nimi kustp,
           k2.nimi kohde,
           k2.nimi projekti,
           tiliointi.tapvm,
           tiliointi.summa,
           tiliointi.selite,
           tiliointi.vero,
           tiliointi.korjattu,
           if(tiliointi.korjausaika=0, '', tiliointi.korjausaika) korjausaika,
           lasku.tila,
           lasku.alatila,
           lasku.tunnus
           FROM tiliointi
           LEFT JOIN kustannuspaikka as k1 ON (k1.yhtio = tiliointi.yhtio AND k1.tunnus = tiliointi.kustp)
           LEFT JOIN kustannuspaikka as k2 ON (k2.yhtio = tiliointi.yhtio AND k2.tunnus = tiliointi.kohde)
           LEFT JOIN kustannuspaikka as k3 ON (k3.yhtio = tiliointi.yhtio AND k3.tunnus = tiliointi.projekti)
           JOIN lasku on (tiliointi.yhtio = lasku.yhtio and tiliointi.ltunnus = lasku.tunnus)
           WHERE tiliointi.yhtio = '{$yhtio}'
           {$rajaus2})";
$res = pupe_query($query);

$datetime_checkpoint_uusi = date('Y-m-d H:i:s');

// Tallennetaan aikaleima
cron_aikaleima("CGNS_LEDG_CRON", $datetime_checkpoint_uusi);

// Kerrotaan montako riviä käsitellään
$rows = mysql_num_rows($res);

echo "Tiliöintirivejä {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysql_fetch_assoc($res)) {

  $laskutyyppi = $row["tila"];
  $alatila     = $row["alatila"];

  // tehdään selväkielinen tila/alatila
  require "inc/laskutyyppi.inc";

  if (in_array($row["tila"], array("H", "Y", "M", "P", "Q"))) {
    $laskutyyppi = "Ostolasku ".$laskutyyppi;
  }

  if ($alatila != "") {
    $laskutyyppi .= " / $alatila";
  }

  $rivi  = $row['tyyppi'].";";
  $rivi .= $row['tapvm'].";";
  $rivi .= $row['summa'].";";
  $rivi .= pupesoft_csvstring($row['tilino']).";";
  $rivi .= pupesoft_csvstring($row['kustp']).";";
  $rivi .= pupesoft_csvstring($row['kohde']).";";
  $rivi .= pupesoft_csvstring($row['projekti']).";";
  $rivi .= pupesoft_csvstring($row['selite']).";";
  $rivi .= $row['vero'].";";
  $rivi .= $row['tunnus'].";";
  $rivi .= pupesoft_csvstring($laskutyyppi).";";
  $rivi .= $row['laatija'].";";
  $rivi .= $row['laadittu'].";";
  $rivi .= $row['korjattu'].";";
  $rivi .= $row['korjausaika'].";";
  $rivi .= "\n";

  fwrite($fp, $rivi);

  $k_rivi++;

  if ($k_rivi % 1000 == 0) {
    echo "Käsitellään riviä {$k_rivi}\n";
  }
}

fclose($fp);

if (!empty($scp_siirto)) {
  // Siirretään toiselle palvelimelle
  system("scp {$filepath} $scp_siirto");
}

echo "Valmis.\n";
