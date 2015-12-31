<?php

// otetaan includepath aina rootista
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear");
//error_reporting(E_ALL);
//ini_set("display_errors", 1);

// otetaan tietokanta connect
require "inc/connect.inc";
require "inc/functions.inc";

// Pupeasennuksen root
$pupe_root_polku = dirname(__FILE__);
//$pupe_root_polku = "/Users/satu/Dropbox/Sites/pupesoft/";

if (!isset($argv[1]) or !isset($argv[2]) or !isset($argv[3])) {
  echo utf8_encode("VIRHE: pakollisia parametreja puuttu!")."\n";
  exit;
}

// Yhtiö, toimipaikkanumero, toimittajan ytunnus
$yhtio = $argv[1];
$tp = $argv[2];
$ytunnus = $argv[3];
$kpl = 0;

$korjaa = FALSE;
$kukamuuttaa = "kulukorj";
/*
if (isset($argv[4]) and $argv[4] != "") {
  $korjaa = TRUE;
}
*/
// Kirjanputokauden ensimmäinen avoin päivä. Jos laskun tapvm on tätä pienempi, niin siiretään korjaus kpitokauden ekalle päivälle.
$kpitokausi_auki = 00000000;
$kpitokausi_auki_pvm = "0000-00-00";

if (isset($argv[5]) and $argv[5] != "") {
  $kpitokausi_auki = $argv[5];

  if (strlen($kpitokausi_auki) != 8) {
    die("Päivämäärä muodossa vvvvkkpp");
  }

  $kpitokausi_auki_pvm = substr($kpitokausi_auki, 0, 4)."-".substr($kpitokausi_auki, 4, 2)."-".substr($kpitokausi_auki, 6, 2);
}

$tanaan = date("d.m.Y");

require "inc/connect.inc";
require "inc/functions.inc";

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow = hae_kukarow('admin', $yhtiorow['yhtio']);

if (!isset($kukarow)) {
  echo utf8_encode("VIRHE: admin-käyttäjää ei löydy!")."\n";
  exit;
}

// haetaan halutut varastotaphtumat
$query  = "SELECT laskunro, tunnus, date_format(tapvm, '%Y%m%d') tapvm, month(tapvm) KUUKAUSI,
           osto_rahti, osto_kulu, osto_rivi_kulu, nimi, ytunnus, yhtio_toimipaikka
                FROM lasku
                WHERE yhtio = '$kukarow[yhtio]'
                and ytunnus = $ytunnus
                and tila    in ('H','M','P','Q','Y')
                #and yhtio_toimipaikka  = $tp
                and (osto_rahti != 0 or osto_kulu != 0 or osto_rivi_kulu != 0)";
$result = pupe_query($query);

if (mysql_num_rows($result) > 0) {

  while ($laskurow = mysql_fetch_assoc($result)) {

    $query  = "SELECT laadittu, sum(summa) summa
               from tiliointi
               where yhtio   = '{$kukarow['yhtio']}'
               and ltunnus   = '{$laskurow['tunnus']}'
               and tilino    = '{$yhtiorow['alv']}'
               and korjattu != ''
               group by 1
               order by 1 asc";

    $old_alv_chk_res = pupe_query($query);
    $old_alv_chk_row = mysql_fetch_assoc($old_alv_chk_res);

    $query = "SELECT sum(summa) summa, group_concat(tunnus) tunnukset
              from tiliointi
              where yhtio           = '{$kukarow['yhtio']}'
              and ltunnus           = '{$laskurow['tunnus']}'
              and tilino            = '{$yhtiorow['alv']}'
              and korjattu          = ''
              and summa_valuutassa != 0";

    $new_alv_chk_res = pupe_query($query);
    $new_alv_chk_row = mysql_fetch_assoc($new_alv_chk_res);

    if (mysql_num_rows($old_alv_chk_res) > 0 and mysql_num_rows($new_alv_chk_res) > 0) {

      $old_alv = (int) ($old_alv_chk_row['summa'] * 10000);
      $new_alv = (int) ($new_alv_chk_row['summa'] * 10000);

      if ($old_alv != $new_alv) {
        $alvin_ero = round($old_alv_chk_row['summa'] - $new_alv_chk_row['summa'], 2);

        $query = "SELECT *
                  from tiliointi
                  where yhtio  = '{$kukarow['yhtio']}'
                  and ltunnus  = '{$laskurow['tunnus']}'
                  and tilino   IN ('{$yhtiorow['osto_rahti']}', '{$yhtiorow['osto_kulu']}', '{$yhtiorow['osto_rivi_kulu']}')
                  and korjattu = ''";
        $kulu_chk_res = pupe_query($query);
        $kulu_chk_row = mysql_fetch_assoc($kulu_chk_res);

        $kulu_chk_row['summa'] -= $alvin_ero;
        list($_tunnus, ) = explode(',', $new_alv_chk_row['tunnukset']);
        echo utf8_encode("$laskurow[laskunro], $laskurow[tapvm], $laskurow[tunnus], $laskurow[nimi], $laskurow[yhtio_toimipaikka], $old_alv_chk_row[summa], $new_alv_chk_row[summa], $_tunnus, ero: $alvin_ero")."\n";
        $kpl += $kpl;

        kopioitiliointi($kulu_chk_row['tunnus'], $kukamuuttaa);

        $query = "UPDATE tiliointi SET
                  summa       = '{$kulu_chk_row['summa']}',
                  laadittu    = now(),
                  laatija     = '$kukamuuttaa'
                  where yhtio = '{$kukarow['yhtio']}'
                  and tunnus  = '{$kulu_chk_row['tunnus']}'";
        $upd_kulut = pupe_query($query);

        list($_tunnus, ) = explode(',', $new_alv_chk_row['tunnukset']);

        kopioitiliointi($_tunnus, $kukamuuttaa);

        $query = "UPDATE tiliointi SET
                  summa        = summa + $alvin_ero,
                  laadittu     = now(),
                  laatija      = '$kukamuuttaa'
                  where yhtio  = '{$kukarow['yhtio']}'
                  and ltunnus  = '{$laskurow['tunnus']}'
                  and tilino   = '{$yhtiorow['alv']}'
                  and korjattu = ''
                  and tunnus   = '{$_tunnus}'";
        $upd_kulut = pupe_query($query);
      }
    }
  }

  echo utf8_encode("Korjattuja laskuja yhteensä: $kpl")."\n";
  echo "\n";
  flush();
}
