<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

// lisätään includepathiin pupe-root
$pupe_root_path = dirname(dirname(__FILE__));
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupe_root_path);
ini_set("display_errors", 1);
error_reporting(E_ALL);

// otetaan tietokanta connect
require "inc/connect.inc";
require "inc/functions.inc";
require "pdflib/phppdflib.class.php";
require "raportit/jt-raportti_pdf.inc";

// Logitetaan ajo
cron_log();

$query = "SELECT DISTINCT yhtio FROM yhtio";
$yhtio_result = pupe_query($query);

while ($yrow = mysql_fetch_array($yhtio_result)) {
  $yhtio = $yrow['yhtio'];
  $yhtiorow = hae_yhtion_parametrit($yhtio);
  $kukarow = hae_kukarow('admin', $yhtio);

  if (!isset($kukarow)) {
    echo "VIRHE: Admin käyttäjä ei löydy yrityksestä {$yhtio}!\n";
    continue;
  }

  $toimquery = "SELECT *
                FROM yhtion_toimipaikat
                WHERE yhtio                        = '$yhtiorow[yhtio]'
                AND toim_automaattinen_jtraportti != ''";
  $toimresult = pupe_query($toimquery);

  while ($toimrow = mysql_fetch_array($toimresult)) {
    if ($toimrow["toim_automaattinen_jtraportti"] == "pv") {
      // annetaan mennä läpi, koska ajetaan joka päivä
    }
    elseif ($toimrow["toim_automaattinen_jtraportti"] == "vk") {
      // ajetaan joka sunnuntai
      if (date('N') != 7) {
        continue;
      }
    }
    elseif ($toimrow["toim_automaattinen_jtraportti"] == "2vk") {
      // ajetaan joka toinen viikko
      if (date('N') != 15) {
        continue;
      }
    }
    elseif ($toimrow["toim_automaattinen_jtraportti"] == "kk" or $toimrow["toim_automaattinen_jtraportti"] == "2vk") {
      // ajetaan kuun 1. pv
      if (date('j') != 1) {
        continue;
      }
    }
    else {
      continue;
    }

    $lisavarattu = "";
    $laskulisa = "";

    if ($yhtiorow["varaako_jt_saldoa"] != "") {
      $lisavarattu = " + tilausrivi.varattu";
    }
    else {
      $lisavarattu = "";
    }

    $liitostunnus_query = "SELECT DISTINCT lasku.liitostunnus FROM tilausrivi
                           JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus AND lasku.yhtio_toimipaikka = $toimrow[tunnus])
                           WHERE tilausrivi.yhtio     = '$yhtiorow[yhtio]'
                           AND tilausrivi.tyyppi      = 'L'
                           AND tilausrivi.var         = 'J'
                           AND tilausrivi.keratty     = ''
                           AND tilausrivi.uusiotunnus = 0
                           AND tilausrivi.kpl         = 0
                           AND tilausrivi.jt $lisavarattu  > 0";
    $liitostunnus_result = pupe_query($liitostunnus_query);

    while ($liitostunnus_row = mysql_fetch_array($liitostunnus_result)) {

      $asiakasquery = "SELECT nimi, osoite, postino, postitp, maa, ytunnus, email, kieli, tunnus FROM asiakas WHERE yhtio='{$yhtiorow['yhtio']}' AND tunnus=$liitostunnus_row[liitostunnus]";
      $asiakasresult = pupe_query($asiakasquery);
      $asiakasrow = mysql_fetch_array($asiakasresult);
      $kukarow["kieli"] = strtolower($asiakasrow["kieli"]);

      if ($asiakasrow["email"] != "") {
        $jtquery = "SELECT tilausrivi.nimitys, tilausrivi.otunnus, tilausrivi.tuoteno, tilausrivi.laadittu, tilausrivi.tilkpl
                    FROM tilausrivi USE INDEX (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
                    JOIN lasku USE INDEX (PRIMARY) ON (lasku.yhtio = tilausrivi.yhtio and lasku.yhtio_toimipaikka = $toimrow[tunnus] and lasku.tunnus = tilausrivi.otunnus and lasku.osatoimitus = '' AND lasku.liitostunnus = '{$asiakasrow['tunnus']}')
                    WHERE tilausrivi.yhtio     = '$yhtiorow[yhtio]'
                    AND tilausrivi.tyyppi      = 'L'
                    AND tilausrivi.var         = 'J'
                    AND tilausrivi.keratty     = ''
                    AND tilausrivi.uusiotunnus = 0
                    AND tilausrivi.kpl         = 0
                    AND tilausrivi.jt $lisavarattu  > 0
                    ORDER BY tilausrivi.otunnus";
        $jtresult = pupe_query($jtquery);

        if (mysql_num_rows($jtresult) > 0) {
          $pdf = new pdffile();

          $pdf->set_default('margin-top',    0);
          $pdf->set_default('margin-bottom', 0);
          $pdf->set_default('margin-left',   0);
          $pdf->set_default('margin-right',  0);

          list($page, $kalakorkeus) = alku($pdf);

          while ($jtrow = mysql_fetch_array($jtresult)) {
            list($page, $kalakorkeus) = rivi($pdf, $page, $kalakorkeus, $jtrow);
          }

          print_pdf($pdf, 1);
        }
      }
    }
  }
}
