<?php

$_GET['ohje'] = 'off';
$_GET['no_css'] = 'yes';

$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

if(!isset($errors)) $errors = array();
if(!isset($tee)) $tee = '';

# Uusi suuntalava
# form.php / uusi
if (isset($uusi)) {
  $title = t("Uusi suuntalava");

  # Haetaan tyypit
  $query = "SELECT *
            FROM pakkaus
            WHERE yhtio = '{$kukarow['yhtio']}'";
  $pakkaus_result = pupe_query($query);

  while($rivi = mysql_fetch_assoc($pakkaus_result)) {
    $pakkaukset[] = $rivi;
  }

  # Haetaan ker�ysvy�hykkeet
  $keraysvyohyke_query = "SELECT tunnus, nimitys
                          FROM keraysvyohyke
                          WHERE yhtio = '{$kukarow['yhtio']}' AND nimitys != ''";
  $keraysvyohyke_result = pupe_query($keraysvyohyke_query);

  while($rivi = mysql_fetch_assoc($keraysvyohyke_result)) {
    $keraysvyohykkeet[] = $rivi;
  }

  # Kirjoittimet
  $query = "SELECT *
            FROM kirjoittimet
            WHERE yhtio  = '{$kukarow['yhtio']}'
            #AND komento != 'email'
            AND komento != 'edi'
            ORDER BY kirjoitin";
  $kires = mysql_query($query) or pupe_error($query);

  $kirjoittimet = array();
  $kirjoittimet[] = array('tunnus' => '', 'kirjoitin' => 'ei kirjoitinta');
  while($kirjoitin = mysql_fetch_assoc($kires)) {
    $kirjoittimet[] = $kirjoitin;
  }

  # Uuden suuntalavan luominen
  if (isset($uusi) and $post=='OK') {

    # Tarkistetaan parametrit
    if(!isset($kaytettavyys) or !isset($terminaalialue) or !isset($sallitaanko)) {
       $errors[] = "Virheelliset parametrit";
    }
    if(empty($tulostin)) {
      $errors[] = "Valitse kirjoitin";
    }

    # Jos ei virheit� luodaan uusi suuntalava
    if(count($errors) == 0) {
      # TODO: Suuntalavan luominen ilman saapumista
      $otunnus = "";

      $tee = "eihalutamitankayttoliittymaapliis";

      # Kirjoittimen komento
      $query = "SELECT komento
                FROM kirjoittimet
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$tulostin}'
                ORDER BY kirjoitin";
      $kires = mysql_query($query) or pupe_error($query);
      $kirow = mysql_fetch_assoc($kires);
      $komento = $kirow['komento'];

      if (!empty($komento)) {
        $uusi_sscc = tulosta_sscc($komento);
      }

      if (!empty($uusi_sscc)) {
        #$temp_sscc = "tmp_".substr(sha1(time()), 0, 6);
        # Suuntalavan parametrit
        $params = array(
            'sscc'           => $uusi_sscc,
            'alkuhyllyalue'       => $alkuhyllyalue,
            'alkuhyllynro'       => $alkuhyllynro,
            'alkuhyllyvali'       => $alkuhyllyvali,
            'alkuhyllytaso'       => $alkuhyllytaso,
            'loppuhyllyalue'    => $loppuhyllyalue,
            'loppuhyllynro'       => $loppuhyllynro,
            'loppuhyllyvali'     => $loppuhyllyvali,
            'loppuhyllytaso'     => $loppuhyllytaso,
            'tyyppi'         => $tyyppi,
            'keraysvyohyke'      => $keraysvyohyke,
            'kaytettavyys'       => $kaytettavyys,
            'usea_keraysvyohyke'   => $sallitaanko,
            'terminaalialue'     => $terminaalialue
          );

        # Lis�� suuntalava -funktio
        require ("../tilauskasittely/suuntalavat.inc");

        $uusi_suuntalava = lisaa_suuntalava($otunnus, $params);
        echo "<br>Lis�ttiin lava! ".$uusi_sscc;

        # Takaisin suuntalavat listaan
        echo "<META HTTP-EQUIV='Refresh'CONTENT='3;URL=suuntalavat.php'>";
        exit();
      }
    }
  }

  include('views/suuntalavat/form.php');
}
# P�ivitet��n suuntalava
# form.php / update
elseif (isset($muokkaa) and is_numeric($muokkaa)) {
  $title = t("Suuntalavan muokkaus");

  # Tyyppi
  $query = "SELECT *
            FROM pakkaus
            WHERE yhtio = '{$kukarow['yhtio']}'";
  $pakkaus_result = pupe_query($query);

  while($rivi = mysql_fetch_assoc($pakkaus_result)) {
    $pakkaukset[] = $rivi;
  }

  # Ker�ysvy�hyke
  $keraysvyohyke_query = "SELECT tunnus, nimitys
                          FROM keraysvyohyke
                          WHERE yhtio = '{$kukarow['yhtio']}' AND nimitys != ''";
  $keraysvyohyke_result = pupe_query($keraysvyohyke_query);

  while($rivi = mysql_fetch_assoc($keraysvyohyke_result)) {
    $keraysvyohykkeet[] = $rivi;
  }

  # Jos suuntalavalle on ehditty list�t� tuotteita, disabloidaan ker�ysvy�hyke ja hyllyalue
  $query = "SELECT tunnus FROM tilausrivi WHERE suuntalava = '{$muokkaa}' and yhtio='{$kukarow['yhtio']}'";
  $result = pupe_query($query);
  $disabled = (mysql_num_rows($result) != 0) ? ' disabled' : '';

  # Suuntalavan tiedot
  $query = "SELECT
            suuntalavat.*,
            pakkaus.pakkaus,
            pakkaus.tunnus as ptunnus
            FROM suuntalavat
            LEFT JOIN pakkaus on (pakkaus.tunnus=suuntalavat.tyyppi)
            WHERE suuntalavat.tunnus='$muokkaa' and suuntalavat.yhtio='{$kukarow['yhtio']}'";
  $result = pupe_query($query);
  if(!$suuntalava = mysql_fetch_assoc($result)) exit("Virheellinen suuntalavan tunnus");

  # Siirtovalmis nappi ja poista nappi
  $rivit_query = "SELECT * FROM tilausrivi WHERE yhtio='{$kukarow['yhtio']}' and suuntalava='{$suuntalava['tunnus']}'";
  $rivit = mysql_num_rows(pupe_query($rivit_query));
  $disable_siirtovalmis = ($rivit == 0) ? ' disabled' : '';  # Rivej� ei l�ydy, disabloidaan siirtovalmis nappi
  $disable_poista = ($rivit != 0) ? ' disabled' : '';      # Rivej� l�ytyy, disabloidaan poista nappi

  # Suuntalavan p�ivitys
  if (isset($post) and is_numeric($muokkaa)) {

    # Tarkistetaan parametrit
    if(!isset($kaytettavyys) or !isset($terminaalialue) or !isset($sallitaanko)) {
       $errors[] = "Virheelliset parametrit";
    }

    # Jos ei virheit� niin p�ivitet��n suuntalava
    if(count($errors) == 0) {

      $keraysvyohyke = (isset($keraysvyohyke)) ? $keraysvyohyke : $suuntalava['keraysvyohyke'];

      # Tehd��n uusi suuntalava
      $params = array(
          'suuntalavan_tunnus'  => $suuntalava['tunnus'],
          'sscc'          => $suuntalava['sscc'],
          'alkuhyllyalue'       => $alkuhyllyalue,
          'alkuhyllynro'       => $alkuhyllynro,
          'alkuhyllyvali'       => $alkuhyllyvali,
          'alkuhyllytaso'       => $alkuhyllytaso,
          'loppuhyllyalue'    => $loppuhyllyalue,
          'loppuhyllynro'       => $loppuhyllynro,
          'loppuhyllyvali'     => $loppuhyllyvali,
          'loppuhyllytaso'     => $loppuhyllytaso,
          'tyyppi'        => $tyyppi,
          'keraysvyohyke'       => $keraysvyohyke,
          'kaytettavyys'       => $kaytettavyys,
          'terminaalialue'     => $terminaalialue,
          'korkeus'         => '',
          'paino'          => '',
          'usea_keraysvyohyke'  => $sallitaanko
        );

      # TODO: Saapumisen hallinta
      #$otunnus = hae_saapumiset($suuntalava['tunnus']);

      # Ei tarvita k�ytt�liittym��
      $suuntalavat_ei_kayttoliittymaa = 'KYLLA';
      $tee = "eihalutamitankayttoliittymaapliis";
      $otunnus = '';

      require ("../tilauskasittely/suuntalavat.inc");
      echo "<br>P�ivitetiin suuntalava";
      lisaa_suuntalava($otunnus, $params);

      # Takaisin suuntalavat listaan
      echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=suuntalavat.php'>";
      exit();
    }
  }
  include('views/suuntalavat/form.php');
}

# Suuntalava siirtovalmiiksi (normaali)
elseif ($tee == 'siirtovalmis' or $tee == 'suoraan_hyllyyn' and isset($suuntalava)) {
  $title = t("Suuntalava siirtovalmiiksi");

  echo "Suuntalava $suuntalava siirtovalmiiksi<br>";

  # Suuntalavan k�sittelytapa (Suoraan (H)yllyyn)
  if ($tee == 'suoraan_hyllyyn') {
    echo "K�sittelytapa suoraan hyllyyn";
    $query = "UPDATE suuntalavat SET kasittelytapa='H' WHERE tunnus='{$suuntalava}' and yhtio='{$kukarow['yhtio']}'";
    $result = pupe_query($query);
  }

  # Suuntalava siirtovalmiiksi
  $suuntalavat_ei_kayttoliittymaa = "KYLLA";
  $tee = 'siirtovalmis';
  $suuntalavan_tunnus = $suuntalava;
  require ("../tilauskasittely/suuntalavat.inc");

  # Takaisin suuntalavat listaan
  echo "<META HTTP-EQUIV='Refresh'CONTENT='3;URL=suuntalavat.php'>";
  exit();
}
elseif ($tee == 'poista') {

  # Varmistetaan ett� suuntalava on tyhj�
  $query = "SELECT * FROM tilausrivi WHERE yhtio='{$kukarow['yhtio']}' AND suuntalava='{$suuntalava}'";
  $suuntalavan_rivit = mysql_num_rows(pupe_query($query));

  if ($suuntalavan_rivit == 0) {
    # Poistetaan suuntalava_saapumiset
    $query = "DELETE FROM suuntalavat_saapuminen WHERE yhtio='{$kukarow['yhtio']}' AND suuntalava='{$suuntalava}'";
    $poista_result = pupe_query($query);

    # Poistetaan suuntalava
    $query = "DELETE FROM suuntalavat WHERE yhtio='{$kukarow['yhtio']}' AND tunnus='{$suuntalava}'";
    $poista_result = pupe_query($query);

    echo "Suuntalava $suuntalava poistettu.";
  }
  else {
    echo "Suuntalava ei ollut tyhj� ja sit� ei voida poistaa";
  }


  # Takaisin suuntalavat listaan
  echo "<META HTTP-EQUIV='Refresh'CONTENT='2;URL=suuntalavat.php'>";
  exit();

}
# Lista suuntalavoista
# index.php
else {

  $title = t("Suuntalavat");
  $suuntalavat = array();

  $hakuehto = !empty($hae) ? "and suuntalavat.sscc = '".mysql_real_escape_string($hae)."'" : "";

  # Haetaan 'validit' suuntalavat
  # suuntalavat.tila=''
  $query = "SELECT
            suuntalavat.sscc,
            ifnull(keraysvyohyke.nimitys, '-') as keraysvyohyke,
            ifnull(pakkaus.pakkaus, '-') as tyyppi,
            count(tilausrivi.tunnus) as rivit,
            suuntalavat.tunnus
            FROM suuntalavat
            LEFT JOIN tilausrivi on (tilausrivi.yhtio = suuntalavat.yhtio and tilausrivi.suuntalava = suuntalavat.tunnus)
            LEFT JOIN pakkaus on (pakkaus.tunnus = suuntalavat.tyyppi)
            LEFT JOIN keraysvyohyke on (keraysvyohyke.tunnus = suuntalavat.keraysvyohyke)
            WHERE suuntalavat.tila='' and suuntalavat.sscc!='Suoratoimitus' $hakuehto and suuntalavat.yhtio='{$kukarow['yhtio']}'
            GROUP BY 1,2,3
            ORDER BY suuntalavat.tunnus DESC";
  $result = pupe_query($query);

  while($rivi = mysql_fetch_assoc($result)) {
    $suuntalavat[] = $rivi;
  }

  if (empty($suuntalavat)) {
    $errors[] = "Suuntalavaa ei l�ytynyt.";
  }

  include('views/suuntalavat/index.php');
}

# Virheet
if (isset($errors)) {
  echo "<span class='error'>";
  foreach($errors as $virhe) {
    echo "{$virhe}<br>";
  }
  echo "</span>";
}

require('inc/footer.inc');
