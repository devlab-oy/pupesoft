<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");

echo "<meta name='viewport' content='width=device-width, maximum-scale=1.0' />\n";
echo "<link rel='stylesheet' type='text/css' href='ipad.css' />\n";
echo "<body>";

if (!isset($errors)) $errors = array();

$view = 'syotto';

if (isset($submit)) {

  if (empty($hakukoodi)) {
    $errors[] = t("Syötä hakukoodi");
  }
  else{

    $hakukoodi = trim($hakukoodi);

    // katsotaan ekana onko kyseessä konttiviite
    $query = "SELECT tunnus
              FROM laskun_lisatiedot
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND konttiviite = '{$hakukoodi}'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) > 0) {
      $tyyppi = "konttiviite";
    }
    else {
      // sitten katsotaan onko se tilausnumero
      $query = "SELECT tunnus
                FROM lasku
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND asiakkaan_tilausnumero = '{$hakukoodi}'";
      $result = pupe_query($query);

      if (mysql_num_rows($result) > 0) {
        $tyyppi = "tilausnumero";
      }
      else {
        // sitten katsotaan onko se sarjanumero
        $query = "SELECT tunnus
                  FROM sarjanumeroseuranta
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND sarjanumero = '{$hakukoodi}'";
        $result = pupe_query($query);

        if (mysql_num_rows($result) > 0) {
          $tyyppi = "sarjanumero";
        }
      }
    }

    if (!isset($tyyppi)) {
     $errors[] = t("Syötetyllä haulla ei löytynyt mitään");
     $view = 'syotto';
    }
    else {
      $tiedot = hae_tiedot($hakukoodi, $tyyppi);
      $view = 'tiedot';
    }
  }
}

echo "<div class='header'>";

echo "<div class='header_left'>";
echo "<a href='index.php' class='button header_button'>";
echo t("Päävalikko");
echo "</a>";
echo "</div>";

echo "<div class='header_center'>";
echo "<h1>";
echo t("TIEDON HAKU");
echo "</h1>";
echo "</div>";

echo "<div class='header_right'>";
echo "<a href='{$palvelin2}logout.php?location={$palvelin2}sarjanumero' class='button header_button'>";
echo t("Kirjaudu ulos");
echo "</a>";
echo "</div>";

echo "</div>";

if ($view == 'syotto') {

  echo "
  <form method='post' action='hae_tiedot.php'>
    <div style='text-align:center;padding:10px;'>
      <label for='sarjanumero'>", t("Syötä sarjanumero, konttiviite, tai tilausnumero."), "</label><br>
      <input type='text' id='hakukoodi' name='hakukoodi' style='margin:10px;' />
      <br>
      <button name='submit' value='haku' onclick='submit();' class='button'>", t("Hae tiedot"), "</button>
    </div>
  </form>

  <script type='text/javascript'>
    $(document).on('touchstart', function(){
      $('#hakukoodi').focus();
    });

  </script>";

  if (count($viestit) > 0) {
    echo "<div class='viesti' style='text-align:center'>";
    foreach ($viestit as $viesti) {
      echo $viesti."<br>";
    }
    echo "</div>";
  }

  if (count($errors) > 0) {
    echo "<div class='error' style='text-align:center'>";
    foreach ($errors as $error) {
      echo $error."<br>";
    }
    echo "</div>";
  }
}

if ($view == 'tiedot') {

  if ($tiedot['rullien_maara'] == 0) {
    foreach ($tiedot['tilaukset'] as $tilausnumero => $tilaus_tiedot) {
      $tiedot['rullien_maara'] += $tilaus_tiedot['rullien_maara_arvio'];
    }
    $tiedot['rullien_paino'] = 'Ei tietoa';
  }

  echo "
    <div style='text-align:center; margin-top:30px;'>
    <a href='hae_tiedot.php' class='button'>", t("Uusi haku"), "</a></div>";


  echo "<div class='alue_0'>";
  echo "<div class='alue alue_1'>";

  echo "
    <div style='overflow:auto; margin-bottom:25px;'>

    <div style='float:left; width:226px; text-align:left;'>
    Konttiviite<br>{$tiedot['konttiviite']}
    </div>

    <div style='float:left; width:226px; text-align:center;'>
    Rullia: {$tiedot['rullien_maara']} kpl<br>
    Paino: {$tiedot['rullien_paino']}
    </div>

    <div style='float:left; width:226px; text-align:right;'>
    Lähtöaika<br>";

    echo date("d.m.Y", strtotime($tiedot['menoaika']));

  echo "
    </div>
    </div>";



  foreach ($tiedot['tilaukset'] as $tilausnumero => $tilaus_tiedot) {

    $haetun_vp = array();
    $haetun_st = array();

    if ($tilaus_tiedot['rullien_maara'] == 0) {
      $rulla_kpl = $tilaus_tiedot['rullien_maara_arvio'] . ' kpl';
      $paino = 'Ei painotietoa';
    }
    else {
      $rulla_kpl = $tilaus_tiedot['rullien_maara'] . ' kpl';
      $paino = $tilaus_tiedot['rullien_paino'] . ' kg';
    }

    echo "
      <div style='padding:10px 0; margin:4px 0;'class='tilaus_alue'>
      <div style='overflow:auto;'>
      <div style='float:left; width:226px; text-align:left; padding-left:10px;' >
        {$tilausnumero}
      </div>

      <div style='float:left; width:226px; text-align:center;'>
        {$rulla_kpl}
      </div>

      <div style='float:left; width:226px; text-align:right;'>
        {$paino}
      </div>
      </div>";

    if (count($tiedot['rullat']) > 0) {

      echo "<div style=' padding:10px; text-align:center'>";

      $statukset = array();
      $varastopaikat = array();

      foreach ($tiedot['rullat'] as $rulla) {
        if ($rulla['asiakkaan_tilausnumero'] == $tilausnumero) {

          if ($rulla['konttinumero'] != '') {
            $vp = 'Toimitettu';
          }
          else {
          $vp = $rulla['hyllyalue'].'-'.$rulla['hyllynro'];
          }

          $statukset[$rulla['lisatieto']]++;
          $varastopaikat[$vp]++;

          if ($rulla['haettu'] == 1) {
            $haetun_vp[$vp][] = 'X';
            $haetun_st[$rulla['lisatieto']][] = 'X';
          }
          else {
            $haetun_vp[$vp][] = '0';
            $haetun_st[$rulla['lisatieto']][] = '0';
          }

        }
      }

      $_statukset = "";
      foreach ($statukset as $status => $kpl) {
        if (in_array('X', $haetun_st[$status])) {
          $luokka = 'haettu';
          $pic = "<img src='{$palvelin2}pics/lullacons/arrow-single-right-green.png'/>&nbsp;";
        }
        else {
          $luokka = '';
          $pic = '';
        }
        $_statukset .= "<div class='{$luokka}'>{$pic}" . $status . ' ' . $kpl . ' kpl</div>';
      }

      $_varastopaikat = "";
      foreach ($varastopaikat as $vp => $kpl) {
        if (in_array('X', $haetun_vp[$vp])) {
          $luokka = 'haettu';
          $pic = "<img src='{$palvelin2}pics/lullacons/arrow-single-right-green.png'/>&nbsp;";
        }
        else {
          $luokka = '';
          $pic = '';
        }
        $_varastopaikat .= "<div class='{$luokka}'>{$pic}" . $vp . ' ' . $kpl . ' kpl</div>';
      }

      echo "
        <div style='overflow:auto;'>
        <div style='float:left; width:226px; text-align:left;' >
        <br>
        </div>

        <div style='float:left; width:226px; text-align:center;'>
        {$_statukset}
        </div>

        <div style='float:left; width:226px; text-align:right;'>
        {$_varastopaikat}
        </div>
        </div>";
      echo "</div>";
    }
    echo "</div>";
  }
echo "</div>";
echo "</div>";
}


require 'inc/footer.inc';


function hae_tiedot($hakukoodi, $tyyppi) {
  global $kukarow;

  if ($tyyppi == 'sarjanumero') {

    $query = "SELECT laskun_lisatiedot.konttiviite
              FROM sarjanumeroseuranta
              JOIN tilausrivi
                ON tilausrivi.yhtio = sarjanumeroseuranta.yhtio
                AND tilausrivi.tunnus = sarjanumeroseuranta.myyntirivitunnus
              JOIN lasku
                ON lasku.yhtio = sarjanumeroseuranta.yhtio
                AND lasku.tunnus = tilausrivi.otunnus
              JOIN laskun_lisatiedot
                ON laskun_lisatiedot.yhtio = lasku.yhtio
                AND laskun_lisatiedot.otunnus = lasku.tunnus
              WHERE sarjanumeroseuranta.yhtio = '{$kukarow['yhtio']}'
              AND sarjanumeroseuranta.sarjanumero = '{$hakukoodi}'";
    $result = pupe_query($query);
    $konttiviite = mysql_result($result, 0);
  }

  if ($tyyppi == 'tilausnumero') {

    $query = "SELECT laskun_lisatiedot.konttiviite
              FROM lasku
              JOIN laskun_lisatiedot
                ON laskun_lisatiedot.yhtio = lasku.yhtio
                AND laskun_lisatiedot.otunnus = lasku.tunnus
              WHERE lasku.yhtio = '{$kukarow['yhtio']}'
              AND lasku.asiakkaan_tilausnumero = '{$hakukoodi}'";
    $result = pupe_query($query);
    $konttiviite = mysql_result($result, 0);
  }

  if ($tyyppi == 'konttiviite') {

    $konttiviite = $hakukoodi;
  }

  $query = "SELECT laskun_lisatiedot.konttiviite,
            laskun_lisatiedot.satamavahvistus_pvm,
            laskun_lisatiedot.rullamaara,
            lasku.asiakkaan_tilausnumero,
            lasku.alatila,
            lasku.toimaika,
            tilausrivin_lisatiedot.konttinumero,
            tilausrivin_lisatiedot.sinettinumero,
            tilausrivin_lisatiedot.kontin_kilot,
            tilausrivin_lisatiedot.kontin_taarapaino,
            tilausrivin_lisatiedot.kontin_isokoodi,
            tilausrivin_lisatiedot.asiakkaan_rivinumero,
            sarjanumeroseuranta.sarjanumero,
            sarjanumeroseuranta.lisatieto,
            sarjanumeroseuranta.massa,
            sarjanumeroseuranta.hyllyalue,
            sarjanumeroseuranta.hyllynro,
            tilausrivi.toimitettuaika,
            ostotilausrivi.toimitettuaika
            FROM laskun_lisatiedot
            LEFT JOIN lasku
              ON lasku.yhtio = laskun_lisatiedot.yhtio
              AND lasku.tunnus = laskun_lisatiedot.otunnus
            LEFT JOIN tilausrivi
              ON tilausrivi.yhtio = laskun_lisatiedot.yhtio
              AND tilausrivi.otunnus = lasku.tunnus
            LEFT JOIN tilausrivin_lisatiedot
              ON tilausrivin_lisatiedot.yhtio = laskun_lisatiedot.yhtio
              AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
            LEFT JOIN sarjanumeroseuranta
              ON sarjanumeroseuranta.yhtio = laskun_lisatiedot.yhtio
              AND sarjanumeroseuranta.myyntirivitunnus = tilausrivi.tunnus
            LEFT JOIN tilausrivi AS ostotilausrivi
              ON ostotilausrivi.yhtio = laskun_lisatiedot.yhtio
              AND ostotilausrivi.tunnus = sarjanumeroseuranta.ostorivitunnus
            WHERE laskun_lisatiedot.yhtio = '{$kukarow['yhtio']}'
            AND laskun_lisatiedot.konttiviite = '{$konttiviite}'";
  $result = pupe_query($query);

  $rullat = array();
  $tilaukset = array();
  $tilauksen_rivit = array();
  $rullien_paino = 0;

  while ($row = mysql_fetch_assoc($result)) {

    $konttiviite = $row['konttiviite'];
    $menoaika = $row['toimaika'];
    $rullien_paino = $rullien_paino + $row['massa'] . ' kg';

    $tilaukset[$row['asiakkaan_tilausnumero']]['rullien_paino'] += $row['massa'];

    $tilaukset[$row['asiakkaan_tilausnumero']]['rullien_maara_arvio'] = $row['rullamaara'];

    if (!isset($tilaukset[$row['asiakkaan_tilausnumero']]['rullien_maara'])) {
      $tilaukset[$row['asiakkaan_tilausnumero']]['rullien_maara'] = 0;
    }

    if ($row['sarjanumero'] != false) {

      $tilaukset[$row['asiakkaan_tilausnumero']]['rullien_maara']++;
      $tilaukset[$row['asiakkaan_tilausnumero']]['rivinumerot'][$row['asiakkaan_rivinumero']]['rullien_maara']++;

      if ($tyyppi == 'sarjanumero' and $row['sarjanumero'] == $hakukoodi) {
        $row['haettu'] = 1;
      }
      else {
        $row['haettu'] = 0;
      }

      $rullat[] = $row;
    }

    $tilaukset[$row['asiakkaan_tilausnumero']]['rivinumerot'][$row['asiakkaan_rivinumero']]['rullien_paino'] += $row['massa'];

  }

  $tiedot = array(
    'konttiviite' => $konttiviite,
    'menoaika' => $menoaika,
    'rullien_paino' => $rullien_paino,
    'rullien_maara' => count($rullat),
    'tilaukset' => $tilaukset,
    'rullat' => $rullat
  );

  return $tiedot;

}
