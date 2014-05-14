<?php

// otetaan includepath aina rootista
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear");
error_reporting(E_ALL);
ini_set("display_errors", 1);

// otetaan tietokanta connect
require("inc/connect.inc");
require("inc/functions.inc");

// Pupeasennuksen root
$pupe_root_polku = dirname(dirname(__FILE__));

if (!isset($argv[1]) or !isset($argv[2]) or !isset($argv[3])) {
  echo utf8_encode("VIRHE: pakollisia parametreja puuttu!")."\n";
  exit;
}

// Yhtiö
$yhtio = $argv[1];

// Tästä eteenpäin tapahtumia korjataan
$accident_date     = $argv[2];
$accident_date_end = $argv[3];

$korjaa = FALSE;

if (isset($argv[4]) and $argv[4] != "") {
  $korjaa = TRUE;
}

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

require("inc/connect.inc");
require("inc/functions.inc");

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow = hae_kukarow('admin', $yhtiorow['yhtio']);

if (!isset($kukarow)) {
  echo utf8_encode("VIRHE: admin-käyttäjää ei löydy!")."\n";
  exit;
}

// haetaan halutut varastotaphtumat
$query  = "  SELECT yhtio, tunnus, date_format(tapvm, '%Y%m%d') tapvm, month(tapvm) KUUKAUSI
      FROM lasku
      WHERE yhtio  = '$kukarow[yhtio]'
      and tila     = 'U'
      and alatila  = 'X'
      and tapvm   >= '$accident_date'
      and tapvm   <= '$accident_date_end'
      and laskunro > 0
      ORDER BY tapvm";
$result = pupe_query($query);

if (mysql_num_rows($result) > 0) {

  $eroyht = 0;
  $edkuukausi = 0;

  while ($laskurow = mysql_fetch_assoc($result)) {

    $query  = "  SELECT round(sum(tilausrivi.rivihinta-ifnull(tilausrivi.kate_korjattu, tilausrivi.kate)), 2) varmuutos, group_concat(tilausrivi.tunnus) tunnukset
          FROM tilausrivi
          JOIN tuote ON (tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno and tuote.ei_saldoa = '')
          WHERE tilausrivi.yhtio     = '$kukarow[yhtio]'
          and tilausrivi.tyyppi      = 'L'
          and tilausrivi.uusiotunnus = {$laskurow['tunnus']}";
    $rivires = pupe_query($query);
    $rivirow = mysql_fetch_assoc($rivires);

    if ($rivirow['tunnukset'] != "") {

      $query  = "  SELECT sum(summa) varmuutos, count(*) varmuma, group_concat(tunnus) varmuutokset,
            max(tapvm) maxtapvm,
            if(max(tapvm) >= '$kpitokausi_auki_pvm', 1, 0) crossyearerror,
            group_concat(if(tapvm >= '$kpitokausi_auki_pvm', tunnus, 0)) tanvuodentunnarit,
            group_concat(if(tapvm  < '$kpitokausi_auki_pvm', tunnus, 0)) viimevuodentunnarit
            FROM tiliointi
            WHERE yhtio  = '$kukarow[yhtio]'
            and ltunnus  = $laskurow[tunnus]
            and korjattu = ''
            and tilino   in ('$yhtiorow[varastonmuutos]','$yhtiorow[raaka_ainevarastonmuutos]')";
      $tilires = pupe_query($query);
      $tilirow = mysql_fetch_assoc($tilires);

      $query  = "  SELECT sum(summa) varasto, group_concat(tunnus) varastot,
            max(tapvm) maxtapvm,
            if(max(tapvm) >= '$kpitokausi_auki_pvm', 1, 0) crossyearerror,
            group_concat(if(tapvm >= '$kpitokausi_auki_pvm', tunnus, 0)) tanvuodentunnarit,
            group_concat(if(tapvm  < '$kpitokausi_auki_pvm', tunnus, 0)) viimevuodentunnarit
            FROM tiliointi
            WHERE yhtio  = '$kukarow[yhtio]'
            and ltunnus  = $laskurow[tunnus]
            and korjattu = ''
            and tilino   in ('$yhtiorow[varasto]','$yhtiorow[raaka_ainevarasto]')";
      $varares = pupe_query($query);
      $vararow = mysql_fetch_assoc($varares);

      $query  = "  SELECT sum(tapahtuma.hinta * tapahtuma.kpl) * -1 varmuutos
            FROM tapahtuma
            JOIN tuote ON (tapahtuma.yhtio = tuote.yhtio and tapahtuma.tuoteno = tuote.tuoteno and tuote.ei_saldoa = '')
            WHERE tapahtuma.yhtio = '$kukarow[yhtio]'
            and tapahtuma.laji    = 'laskutus'
            and tapahtuma.rivitunnus in ({$rivirow['tunnukset']})";
      $tapares = pupe_query($query);
      $taparow = mysql_fetch_assoc($tapares);

      // Kirjanpito - Tilausrivi
      $ero1 = round($tilirow['varmuutos']-$rivirow["varmuutos"], 2);

      // Tapahtuma - Kirjanpito
      $ero2 = round($tilirow['varmuutos']-$taparow["varmuutos"], 2);

      // Tapahtuma - Tilausrivi
      $ero3 = round($taparow['varmuutos']-$rivirow["varmuutos"], 2);

      if (abs($ero1) > 0.5 or abs($ero2) > 0.5 or abs($ero3) > 0.5) {

        if ($korjaa and (int) $laskurow['tapvm'] < $kpitokausi_auki) {
          
          if ($tilirow['crossyearerror'] == 1) {
             // Uusin varastonmuutos
             $maxmuutos = explode(",", $tilirow["tanvuodentunnarit"]);
            $tapvm     = $tilirow["maxtapvm"];
          }
          else {
              // Uusin varastonmuutos viime vuoden puolelta
              $maxmuutos = explode(",", $tilirow["viimevuodentunnarit"]);
            $tapvm     = $kpitokausi_auki_pvm;
          }

          sort($maxmuutos);

          $varmuutun = array_pop($maxmuutos);

          // Tehdään uusi varastonmuutostiliöinti
          $params = array(
            'summa'     => round($tilirow["varmuutos"] * -1, 2),
            'tapvm'      => $tapvm,
            'korjattu'     => '',
            'korjausaika'   => '',
            'selite'     => "Korjausajo $tanaan",
            'laatija'     => $kukarow['kuka'],
            'laadittu'     => date('Y-m-d H:i:s'),
          );

          // Tehdään vastakirjaus alkuperäiselle varastonmuutostiliöinnille
          kopioitiliointi($varmuutun, "", $params);

          // Tehdään uusi varastonmuutostiliöinti
          $params = array(
            'summa'     => round($rivirow["varmuutos"], 2),
            'tapvm'      => $tapvm,
            'korjattu'     => '',
            'korjausaika'   => '',
            'selite'     => "Korjausajo $tanaan",
            'laatija'     => $kukarow['kuka'],
            'laadittu'     => date('Y-m-d H:i:s'),
          );

          // Tehdään vastakirjaus alkuperäiselle varastonmuutostiliöinnille
          kopioitiliointi($varmuutun, "", $params);

          ################################################################################################

           if ($tilirow['crossyearerror'] == 1) {
             // Uusin varasto
             $maxmuutos = explode(",", $vararow["tanvuodentunnarit"]);
            $tapvm     = $vararow["maxtapvm"];
           }
           else {
             // Uusin varasto viime vuoden puolelta
             $maxmuutos = explode(",", $vararow["viimevuodentunnarit"]);
            $tapvm     = $kpitokausi_auki_pvm;
           }

          sort($maxmuutos);

          $vartun = array_pop($maxmuutos);

          // Tehdään uusi varastonmuutostiliöinti
          $params = array(
            'summa'     => round($vararow["varasto"] * -1, 2),
            'tapvm'      => $tapvm,
            'korjattu'     => '',
            'korjausaika'   => '',
            'selite'     => "Korjausajo $tanaan",
            'laatija'     => $kukarow['kuka'],
            'laadittu'     => date('Y-m-d H:i:s'),
          );

          // Tehdään vastakirjaus alkuperäiselle varastonmuutostiliöinnille
          kopioitiliointi($vartun, "", $params);

          // Tehdään uusi varastonmuutostiliöinti
          $params = array(
            'summa'     => round($rivirow["varmuutos"]*-1, 2),
            'tapvm'      => $tapvm,
            'korjattu'     => '',
            'korjausaika'   => '',
            'selite'     => "Korjausajo $tanaan",
            'laatija'     => $kukarow['kuka'],
            'laadittu'     => date('Y-m-d H:i:s'),
          );

          // Tehdään vastakirjaus alkuperäiselle varastonmuutostiliöinnille
          kopioitiliointi($vartun, "", $params);
        }
        elseif ($korjaa) {
          // Korjataan tositteet
          if ($tilirow['varmuutokset'] != "") {

            // Tehdään uusi varastonmuutostiliöinti
            $params = array(
              'summa'     => round($rivirow["varmuutos"], 2),
              'korjattu'     => '',
              'korjausaika'   => '',
              'selite'     => "Korjausajo $tanaan",
              'laatija'     => $kukarow['kuka'],
              'laadittu'     => date('Y-m-d H:i:s'),
            );

            $ekamuutos = explode(",", $tilirow['varmuutokset']);

            // Tehdään vastakirjaus alkuperäiselle varastonmuutostiliöinnille
            kopioitiliointi($ekamuutos[0], "", $params);

            // Yliviivataan alkuperäiset varastonmuutostiliöinnit
            $query = "  UPDATE tiliointi
                  SET korjattu = '{$kukarow['kuka']}', korjausaika = now()
                  WHERE yhtio  = '$kukarow[yhtio]'
                  and ltunnus  = $laskurow[tunnus]
                  and korjattu = ''
                  and tilino   in ('$yhtiorow[varastonmuutos]','$yhtiorow[raaka_ainevarastonmuutos]')
                  and tunnus   in ({$tilirow['varmuutokset']})";
            pupe_query($query);
          }

          if ($vararow['varastot'] != "") {

            // Tehdään uusi varastonmuutostiliöinti
            $params = array(
              'summa'     => round($rivirow["varmuutos"] * -1, 2),
              'korjattu'     => '',
              'korjausaika'   => '',
              'selite'     => "Korjausajo $tanaan",
              'laatija'     => $kukarow['kuka'],
              'laadittu'     => date('Y-m-d H:i:s'),
            );

            $ekamuutos = explode(",", $vararow['varastot']);

            // Tehdään vastakirjaus alkuperäiselle varastonmuutostiliöinnille
            kopioitiliointi($ekamuutos[0], "", $params);

            // Yliviivataan alkuperäiset varastonmuutostiliöinnit
            $query = "  UPDATE tiliointi
                  SET korjattu = '{$kukarow['kuka']}', korjausaika = now()
                  WHERE yhtio  = '$kukarow[yhtio]'
                  and ltunnus  = $laskurow[tunnus]
                  and korjattu = ''
                  and tilino   in ('$yhtiorow[varasto]','$yhtiorow[raaka_ainevarasto]')
                  and tunnus   in ({$vararow['varastot']})";
            pupe_query($query);
          }
        }

        $eroyht += $ero1;
      }
    }

    if ($laskurow["KUUKAUSI"] != $edkuukausi and $edkuukausi != 0) {
      if ($eroyht != 0) echo utf8_encode("$yhtiorow[nimi] / $edkuukausi ero yhteensä: $eroyht")."\n";
      flush();
      $eroyht = 0;
    }

    $edkuukausi = $laskurow["KUUKAUSI"];
  }

  if ($eroyht != 0) echo utf8_encode("$yhtiorow[nimi] / $edkuukausi ero yhteensä: $eroyht")."\n";
  echo "\n";
  flush();
}
