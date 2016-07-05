<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");

// Nämä on pakollisia
if (!isset($alusta_tunnus, $liitostunnus, $tilausrivi)) exit;

if (!isset($saapumisnro_haku)) $saapumisnro_haku = '';

$alusta_tunnus = (int) $alusta_tunnus;
$liitostunnus = (int) $liitostunnus;
$tilausrivi = (int) $tilausrivi;

// Urlin rakennus
$data = array(
  'alusta_tunnus' => $alusta_tunnus,
  'liitostunnus' => $liitostunnus,
  'tilausrivi' => $tilausrivi,
  'saapumisnro_haku' => $saapumisnro_haku
);
$url = http_build_query($data);

// Haetaan suuntalavan tuotteet
if (!empty($alusta_tunnus)) {
  $res = suuntalavan_tuotteet(array($alusta_tunnus), $liitostunnus, "", "", "", $tilausrivi);
  $row = mysql_fetch_assoc($res);
}

// Jos suuntalavan_tuotteet() ei löytynyt mitään
if (!isset($row)) {
  $query = "SELECT
            tilausrivi.*,
            tuotepaikat.hyllyalue AS tuotepaikat_hyllyalue,
            tuotepaikat.hyllynro AS tuotepaikat_hyllynro,
            tuotepaikat.hyllyvali AS tuotepaikat_hyllyvali,
            tuotepaikat.hyllytaso AS tuotepaikat_hyllytaso,
            tuotepaikat.varasto AS tuotepaikat_varasto,
            tuotteen_toimittajat.toim_tuoteno
            FROM tilausrivi
            LEFT JOIN tuotteen_toimittajat
              ON (tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno
                AND tuotteen_toimittajat.yhtio=tilausrivi.yhtio)
            JOIN tuotepaikat
              ON (tuotepaikat.yhtio = tilausrivi.yhtio
                AND tuotepaikat.tuoteno = tilausrivi.tuoteno
                AND tuotepaikat.oletus  = 'X')
            WHERE tilausrivi.tunnus='{$tilausrivi}'
            AND tilausrivi.yhtio='{$kukarow['yhtio']}'";
  $row = mysql_fetch_assoc(pupe_query($query));
}

// Jos parametrina hylly, eli ollaan muutettu tuotteen keräyspaikkaa
if (isset($hylly)) {
  $hylly = explode(",", $hylly);
  $row['hyllyalue'] = $hylly[0];
  $row['hyllynro'] = $hylly[1];
  $row['hyllyvali'] = $hylly[2];
  $row['hyllytaso'] = $hylly[3];
}
elseif ($row['varasto'] == $row['tuotepaikat_varasto']) {
  // käytetään oletuspaikan varastossa aina oletuspaikkaa
  $row['hyllyalue'] = $row['tuotepaikat_hyllyalue'];
  $row['hyllynro']  = $row['tuotepaikat_hyllynro'];
  $row['hyllyvali'] = $row['tuotepaikat_hyllyvali'];
  $row['hyllytaso'] = $row['tuotepaikat_hyllytaso'];
}

$_varasto = kuuluukovarastoon($row['hyllyalue'], $row['hyllynro']);
$onko_varaston_hyllypaikat_kaytossa = onko_varaston_hyllypaikat_kaytossa($_varasto);

// Alkuperäinen saapuminen talteen
$alkuperainen_saapuminen = $saapuminen;

// Tullaan nappulasta
if (isset($submit_button) and trim($submit_button) != '') {
  // Vaan yks varastoonvienti kerrallaan voi olla käynnissä per firma
  $lock_params = array(
  "locktime"    => 600,
  "lockfile"    => "$kukarow[yhtio]-keikka.lock",
  "filecontent" => "$otunnus;{$kukarow['kuka']};".date("Y-m-d H:i:s")
  );

  pupesoft_flock($lock_params);

  // Virheet
  $errors = array();

  switch ($submit_button) {

  case 'new':
    echo "<META HTTP-EQUIV='Refresh' CONTENT='0; URL=uusi_kerayspaikka.php?{$url}'>";
    exit;
    break;
  case 'submit':

    // Tarkistetaan määrä
    if (!is_numeric($maara) or $maara < 1) {
      $errors[] = t("Virheellinen määrä");
    }

    if ($onko_varaston_hyllypaikat_kaytossa) {

      // Tarkistetaan koodi
      $options = array('varmistuskoodi' => $koodi);
      if (!is_numeric($koodi) or !tarkista_varaston_hyllypaikka($row['hyllyalue'], $row['hyllynro'], $row['hyllyvali'], $row['hyllytaso'], $options)) {
        $errors[] = t("Virheellinen varmistuskoodi");
      }

      // Setataan viimeinen muuttuja jos lavalla vain yksi rivi jäjellä
      if (!empty($alusta_tunnus)) {
        $query = "SELECT * FROM tilausrivi WHERE suuntalava = '{$alusta_tunnus}' AND yhtio='{$kukarow['yhtio']}'";
        $rivit_result = pupe_query($query);
        $rivit = mysql_num_rows($rivit_result);
      }
    }

    $viimeinen = (isset($rivit) and $rivit == 1) ? true : false;

    // Jos ei virheitä
    if (count($errors) == 0) {
      $tilausrivit = array();

      // Jos rivi on jo kohdistettu eri saapumiselle
      if (!empty($row['uusiotunnus'])) {
        $saapuminen = $row['uusiotunnus'];
      }
      elseif ($yhtiorow['suuntalavat'] == "" and $saapuminen != 0) {
        // Jos yhtiö ei käytä suuntalavaa ja rivi ei ole saapumisella
        $query = "UPDATE tilausrivi SET
                  uusiotunnus = '{$saapuminen}'
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  = '{$row['tunnus']}'";
        pupe_query($query);
      }

      //tarkistetaan vielä ettei riviä ole jo viety varastoon
      $viety_query = "SELECT uusiotunnus
                      FROM tilausrivi
                      WHERE yhtio         = '{$kukarow['yhtio']}'
                      AND tunnus          = '{$row['tunnus']}'
                      AND laskutettuaika != '0000-00-00'";
      $viety = pupe_query($viety_query);

      if (mysql_num_rows($viety) == 0) {
        // Tarkastetaan syötetyt määrät, eli tarviiko tilausrivia splittailla tai kopioida
        if ($maara < $row['varattu']) {
          // Syötetty määrä on pienempi kuin tilausrivilla oleva määrä.
          // Splitataan rivi ja siirretään ylijääneet uudellele tilausriville.
          splittaa_tilausrivi($tilausrivi, ($row['varattu'] - $maara), TRUE, FALSE);

          // Alkuperäinen viedään varastoon, splitattu jää jâljelle
          $ok = paivita_tilausrivin_kpl($tilausrivi, $maara);
          $tilausrivit[] = $tilausrivi;

          // Ei voi olla viimeinen rivi jos rivi on splitattu
          $viimeinen = false;
        }
        elseif ($maara == $row['varattu']) {
          $tilausrivit[] = $tilausrivi;
        }
        else {
          // Tehdään insertti erotukselle
          $kopioitu_tilausrivi = kopioi_tilausrivi($tilausrivi);

          // Päivitä kopioidun kpl (maara - varattu)
          paivita_tilausrivin_kpl($kopioitu_tilausrivi, ($maara - $row['varattu']));

          $tilausrivit = array($tilausrivi, $kopioitu_tilausrivi);
        }
      }
      else {
        echo t("Tuote oli jo viety varastoon! Ei viedä tuotetta uudestaan varastoon!");
      }


      $temppi_lava = false;

      // Viedään varastoon temppi lavalla
      if ($yhtiorow['suuntalavat'] != "" and (($alusta_tunnus == 0 && $saapuminen != 0) || ($alusta_tunnus != 0 && $row['uusiotunnus'] == 0))) {
        $temppi_lava = true;
        // Tarkottaa että on tultu ostotilauksen tuloutuksesta ilman että kyseisellä
        // tilauksella on suuntalavaa. Ratkaisuna tehdään väliaikainen lava.
        $tee = "eihalutamitankayttoliittymaapliis";
        $suuntalavat_ei_kayttoliittymaa = "KYLLA";
        $otunnus = $saapuminen;
        require "../tilauskasittely/suuntalavat.inc";

        // Suuntalavalle nimi, temp_timestamp+kuka hash
        $hash = "temp_".substr(sha1(time().$kukarow['kuka']), 0, 8);

        $params = array(
          'sscc' => $hash,
          'tyyppi' => 0,
          'keraysvyohyke' => $hash,
          'usea_keraysvyohyke' => 'K',
          'kaytettavyys' => 'Y',
          'terminaalialue' => $hash,
          'korkeus' => 0,
          'paino' => 0,
          'alkuhyllyalue' => "",
          'alkuhyllynro' => "",
          'alkuhyllyvali' => "",
          'alkuhyllytaso' => "",
          'loppuhyllyalue' => "",
          'loppuhyllynro' => "",
          'loppuhyllyvali' => "",
          'loppuhyllytaso' => "",
          'suuntalavat_ei_kayttoliittymaa' => "KYLLA",
          'valittutunnus' => $tilausrivi
        );

        $alusta_tunnus = lisaa_suuntalava($saapuminen, $params);

        // Saapumisen tiedot
        $query    = "SELECT * FROM lasku WHERE tunnus = '{$saapuminen}' AND yhtio = '{$kukarow['yhtio']}'";
        $result   = pupe_query($query);
        $laskurow = mysql_fetch_array($result);

        // Ei voi kohdistaa ennen kuin tilausrivi on splitattu
        require "../inc/keikan_toiminnot.inc";
        foreach ($tilausrivit as $rivi) {
          $kohdista_status = kohdista_rivi($laskurow, $rivi, $row['otunnus'], $saapuminen, $alusta_tunnus);
        }

        // Suuntalava siirtovalmiiksi
        $otunnus = $saapuminen;
        $suuntalavan_tunnus = $alusta_tunnus;
        $tee = 'siirtovalmis';
        $suuntalavat_ei_kayttoliittymaa = "KYLLA";
        require "../tilauskasittely/suuntalavat.inc";
      }

      // Kun splittaukset ja alustat on selvitelty, voidaan kamat viedään varastoon.
      // Hylly array
      $hylly = array(
        "hyllyalue" => $row['hyllyalue'],
        "hyllynro"   => $row['hyllynro'],
        "hyllyvali" => $row['hyllyvali'],
        "hyllytaso" => $row['hyllytaso']);

      // Saapumiset
      if ($yhtiorow['suuntalavat'] != "") {
        $saapumiset = hae_saapumiset($alusta_tunnus);
      }
      else {
        $saapumiset = array($saapuminen);
      }

      // Viimeisellä rivillä viedään koko suuntalava, jolloin lava merkataan puretuksi
      if ($viimeinen) {
        vie_varastoon($saapumiset[0], $alusta_tunnus, $hylly);
      }
      else {
        foreach ($tilausrivit as $rivi) {
          vie_varastoon($saapumiset[0], $alusta_tunnus, $hylly, $rivi);
        }
      }

      // Jos temppi lava niin merkataan suoraan puretuksi
      if ($temppi_lava) {
        $query = "UPDATE suuntalavat SET
                  tila        = 'P'
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  = '{$alusta_tunnus}'";
        $tila_res = pupe_query($query);
      }

      echo t("Odota hetki...");

      // Redirectit ostotilaukseen tai suuntalavan_tuotteet?
      if (isset($hyllytys)) {
        $ostotilaus_urliin = $manuaalisesti_syotetty_ostotilausnro ? $row['otunnus'] : "";
        $tilausten_lukumaara--;
        if ($tilausten_lukumaara < 1) {
          echo "<META HTTP-EQUIV='Refresh' CONTENT='3; URL=ostotilaus.php?saapumisnro_haku={$saapumisnro_haku}'>";
        }
        else {
          $url = "&tilausten_lukumaara={$tilausten_lukumaara}&saapumisnro_haku={$saapumisnro_haku}&manuaalisesti_syotetty_ostotilausnro={$manuaalisesti_syotetty_ostotilausnro}&viivakoodi={$viivakoodi}&tuotenumero=".urlencode($tuotenumero);
          echo "<META HTTP-EQUIV='Refresh' CONTENT='3; URL=tuotteella_useita_tilauksia.php?ostotilaus={$ostotilaus_urliin}{$url}'>";
        }
      }
      else {
        echo "<META HTTP-EQUIV='Refresh' CONTENT='3; URL=suuntalavan_tuotteet.php?{$url}'>";
      }
    }
    break;
  default:
    $errors[] = t("Odottamaton virhe");
    break;
  }
}


if ($row['tilausrivi_tyyppi'] == 'o') {
  //suoratoimitus asiakkaalle
  $row['tilausrivi_tyyppi'] = 'JTS';
}
elseif ($row['tilausrivi_tyyppi'] == '') {
  //linkitetty osto / myyntitilaus varastoon
  $row['tilausrivi_tyyppi'] = 'JT';
}

// Asetetaan määrä varattu kentän arvoksi jos sitä ei ole setattu
$maara = (empty($maara)) ? $row['varattu'] : $maara;

// Jos ollaan tultu ostotilausten tuloutuksesta, on näkymä hieman erilainen kuin asn-tuloutuksessa
if (isset($ostotilaus)) {
  $disabled = "readonly";
  $hidden = "hidden";
}

if (isset($hyllytetty)) {
  $maara = $hyllytetty;
}

echo "
  <script type='text/javascript'>
    function vahvista_formin_submittaus() {
      var maara = document.getElementById('maara').value;
      var row_varattu = parseInt(document.getElementById('row_varattu').innerHTML);
      if(maara > row_varattu) {
        return confirm('Olet tulouttamassa enemmän kuin rivillä alunperin oli. Oletko varma?');
      }
      else return true;
    }

    function doFocus() {
          var focusElementId = 'koodi'
          var textBox = document.getElementById(focusElementId);
          textBox.focus();
      }

    function clickButton() {
       document.getElementById('myHiddenButton').click();
    }

     setTimeout('clickButton()', 1000);

    $(document).ready(function() {
      $('#koodi').on('keyup', function() {
        // Autosubmit vain jos on syötetty tarpeeksi pitkä viivakoodi
        if ($('#koodi').val().length > 1) {
          document.getElementById('vahvista').click();
        }
      });
    });

  </script>
";

// Asn-tuloutus -> suuntalava
$paluu_url = "suuntalavan_tuotteet.php?$url";
// Ostotilaus -> hyllytykseen
if (isset($hyllytys)) {
  $urlilisa = "&viivakoodi={$viivakoodi}&saapumisnro_haku={$saapumisnro_haku}&tilausten_lukumaara={$tilausten_lukumaara}&manuaalisesti_syotetty_ostotilausnro={$manuaalisesti_syotetty_ostotilausnro}&tuotenumero=&ennaltakohdistettu={$ennaltakohdistettu}".urlencode($tuotenumero);
  $paluu_url = "hyllytys.php?ostotilaus={$row['otunnus']}&tilausrivi={$tilausrivi}&saapuminen={$saapuminen}{$urlilisa}";
}

echo "<div class='header'>";
echo "<button onclick='window.location.href=\"$paluu_url\"' class='button left'><img src='back2.png'></button>";
echo "<h1>", t("VAHVISTA KERÄYSPAIKKA"), "</h1></div>";

// Virheet
if (isset($errors)) {
  echo "<span class='error'>";
  foreach ($errors as $virhe) {
    echo "{$virhe}<br>";
  }
  echo "</span>";
}

echo "<input type='button' id='myHiddenButton' visible='false' onclick='javascript:doFocus();' width='1px' style='display:none'>";
echo "<div class='main'>
<form name='vahvistaformi' method='post' action=''>
<table>
  <tr>
    <th>", t("Tuote"), "</th>
    <td colspan='2'>{$row['tuoteno']}</td>
  </tr>
  <tr>
    <th>", t("Toim. Tuotekoodi"), "</th>
    <td colspan='2'>{$row['toim_tuoteno']}</td>
  </tr>
  <tr>
    <th>", t("Määrä"), "</th>
    <td><input type='text' id='maara' name='maara' value='{$maara}' size='7' $disabled/> {$row['tilausrivi_tyyppi']}</td>
    <td><span id='row_varattu' $hidden>{$row['varattu']}</span><span id='yksikko'>{$row['yksikko']}</span></td>
  </tr>
  <tr>
    <th>", t("Keräyspaikka"), "</th>
    <td colspan='2'>{$row['hyllyalue']} {$row['hyllynro']} {$row['hyllyvali']} {$row['hyllytaso']}</td>
  </tr>";

if ($onko_varaston_hyllypaikat_kaytossa) {
  echo "<tr>
      <th>", t("Koodi"), "</th>
      <td colspan='2'><input type='text' name='koodi' id='koodi' value='' size='7' />
    </tr>";
}

echo "<tr>
    <td><input type='hidden' name='saapuminen' value='{$saapuminen}' /></td>
  </tr>
</table>
</div>";

echo "<div class='controls'>
  <button name='submit_button' class='button' value='submit' id='vahvista' onclick='return vahvista_formin_submittaus();'>", t("Vahvista"), "</button>";

// Jos hyllytyksestä niin tämä piiloon
if (!isset($hyllytys)) {
  echo "<button class='button right' name='submit_button' value='new'>", t("Uusi keräyspaikka"), "</button>";

  $saapuminen = !isset($saapuminen) ? $row['uusiotunnus'] : $saapuminen;

  echo "<button type='submit' class='button right' onclick=\"vahvistaformi.action='suuntalavalle.php'\">", t("SUUNTALAVALLE"), "</button>";
  echo "<input type='hidden' name='tullaan' value='pre_vahvista_kerayspaikka' />";
  echo "<input type='hidden' name='hyllytetty' value='{$maara}' />";
}


echo "
  <input type='hidden' name='alusta_tunnus' value='{$alusta_tunnus}' />
  <input type='hidden' name='saapumisnro_haku' value='{$saapumisnro_haku}' />
  <input type='hidden' name='liitostunnus' value='{$liitostunnus}' />
  <input type='hidden' name='tilausrivi' value='{$tilausrivi}' />
  <input type='hidden' name='saapuminen' value='{$saapuminen}' />
  <input type='hidden' name='tilausten_lukumaara' value='{$tilausten_lukumaara}' />
  <input type='hidden' name='manuaalisesti_syotetty_ostotilausnro' value='{$manuaalisesti_syotetty_ostotilausnro}' />
  <input type='hidden' name='viivakoodi' value='{$viivakoodi}' />
  <input type='hidden' name='tuotenumero' value='{$tuotenumero}' />
</form>
";

require 'inc/footer.inc';
