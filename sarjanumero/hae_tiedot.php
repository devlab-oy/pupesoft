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
    $errors[] = t("Sy�t� hakukoodi");
  }
  else{

    $hakukoodi = trim($hakukoodi);

    // katsotaan ekana onko kyseess� konttiviite
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
     $errors[] = t("Sy�tetyll� haulla ei l�ytynyt mit��n");
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
echo t("P��valikko");
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
      <label for='sarjanumero'>", t("Sy�t� sarjanumero, konttiviite, tai tilausnumero."), "</label><br>
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

  foreach ($tiedot['konttiviitteet'] as $konttiviite) {

    if ($tiedot['rullien_maara'][$konttiviite] == 0) {

      foreach ($tiedot['tilaukset'][$konttiviite] as $tilausnumero => $tilaus_tiedot) {
        $tiedot[$konttiviite]['rullien_maara'] += $tilaus_tiedot['rullien_maara_arvio'];
      }
      $tiedot['rullien_paino'][$konttiviite] = 'Ei tietoa';

    }
  }

  echo "
    <div style='text-align:center; margin-top:30px;'>
    <a href='hae_tiedot.php' class='button'>", t("Uusi haku"), "</a></div>";

  foreach ($tiedot['konttiviitteet'] as $konttiviite) {

    echo "<div class='alue_0'>";
    echo "<div class='alue alue_1'>";

    echo "
      <div style='overflow:auto; margin-bottom:25px;'>

      <div style='float:left; width:226px; text-align:left;'>
      Konttiviite<br>{$konttiviite}
      </div>

      <div style='float:left; width:226px; text-align:center;'>
      Rullia: {$tiedot['rullien_maara'][$konttiviite]} kpl<br>
      Paino: {$tiedot['rullien_paino'][$konttiviite]} kg
      </div>

      <div style='float:left; width:226px; text-align:right;'>
      L�ht�aika<br>";

      echo date("d.m.Y", strtotime($tiedot['menoaika'][$konttiviite]));

    echo "
      </div>
      </div>";

    foreach ($tiedot['tilaukset'][$konttiviite] as $tilausnumero => $tilaus_tiedot) {

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

        foreach ($tiedot['rullat'][$konttiviite] as $rulla) {
          if ($rulla['asiakkaan_tilausnumero'] == $tilausnumero) {

            if ($rulla['konttinumero'] != '') {

              if ($rulla['sinettinumero'] == 'X') {
                $vp = 'Kontitettu';
              }
              else {
                $vp = 'Toimitettu';
              }

            }
            elseif ($rulla['hyllyalue'] == '') {
              $vp = t("Ei paikkaa");
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

    $konttiviitteet = array();

    while ($konttiviite = mysql_fetch_assoc($result)) {
      $konttiviitteet[] = $konttiviite['konttiviite'];
    }

    $konttiviite = implode("','", $konttiviitteet);
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
            IF(sarjanumeroseuranta.lisatieto IS NULL, 'Normaali', sarjanumeroseuranta.lisatieto) AS lisatieto,
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
            AND laskun_lisatiedot.konttiviite IN ('{$konttiviite}')";
  $result = pupe_query($query);

  $konttiviitteet = array();
  $rullat = array();
  $tilaukset = array();
  $tilauksen_rivit = array();
  $rullien_paino = array();
  $konttiviitteet = array();
  $menoaika = array();

  while ($row = mysql_fetch_assoc($result)) {

    if (!in_array($row['konttiviite'], $konttiviitteet)) {
      $konttiviitteet[] = $row['konttiviite'];
    }

    $menoaika[$row['konttiviite']] = $row['toimaika'];

    $rullien_paino[$row['konttiviite']] += $row['massa'];

    $rullien_maara[$row['konttiviite']]++;

    $tilaukset[$row['konttiviite']][$row['asiakkaan_tilausnumero']]['rullien_paino'] += $row['massa'];

    $tilaukset[$row['konttiviite']][$row['asiakkaan_tilausnumero']]['rullien_maara_arvio'] = $row['rullamaara'];

    if (!isset($tilaukset[$row['konttiviite']][$row['asiakkaan_tilausnumero']]['rullien_maara'])) {
      $tilaukset[$row['konttiviite']][$row['asiakkaan_tilausnumero']]['rullien_maara'] = 0;
    }

    if ($row['sarjanumero'] != false) {

      $tilaukset[$row['konttiviite']][$row['asiakkaan_tilausnumero']]['rullien_maara']++;
      $tilaukset[$row['konttiviite']][$row['asiakkaan_tilausnumero']]['rivinumerot'][$row['asiakkaan_rivinumero']]['rullien_maara']++;

      if ($tyyppi == 'sarjanumero' and $row['sarjanumero'] == $hakukoodi) {
        $row['haettu'] = 1;
      }
      else {
        $row['haettu'] = 0;
      }

      $rullat[$row['konttiviite']][] = $row;
    }

    $tilaukset[$row['konttiviite']][$row['asiakkaan_tilausnumero']]['rivinumerot'][$row['asiakkaan_rivinumero']]['rullien_paino'] += $row['massa'];
  }

  $tiedot = array(
    'konttiviitteet' => $konttiviitteet,
    'menoaika' => $menoaika,
    'rullien_paino' => $rullien_paino,
    'rullien_maara' => $rullien_maara,
    'tilaukset' => $tilaukset,
    'rullat' => $rullat
  );

  return $tiedot;
}
