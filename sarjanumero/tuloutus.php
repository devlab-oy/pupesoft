<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");

require '../inc/edifact_functions.inc';

echo "<meta name='viewport' content='width=device-width, maximum-scale=1.0' />\n";
echo "<link rel='stylesheet' type='text/css' href='ipad.css' />\n";
echo "<body>";

$errors = array();

if (isset($submit)) {

  switch ($submit) {
  case 'sarjanumero':

    $vaihto = false;
    $ylijaamasiirto = false;
    $kuittaus = true;

    if (empty($sarjanumero)) {
      $errors[] = t("Sy�t� sarjanumero");
    }
    else {
      if (!$result = tarkista_sarjanumero($sarjanumero)) {
        $errors[] = t("Sy�tetty� sarjanumeroa ei l�ydy");
      }
      else{
        $rulla = mysql_fetch_assoc($result);

        if ($rulla['lisatieto'] == 'Ylijaama') {
          $viestit[] = t("Rulla on merkitty ylij��m�ksi");
          $ylijaamasiirto = true;
        }

        if ($rulla['toimitettuaika'] == "0000-00-00 00:00:00") {
          $errors[] = t("Rahtia ei ole viel� kuitattu vastaanotetuksi.");
          $kuittaus = false;
        }
        elseif ($rulla['varasto'] != null) {

          if ($sarjanumero == $vaihdettava_sarjanumero) {

            $hylly = array(
              "hyllyalue" => '',
              "hyllynro"   => '',
              "hyllyvali" => '',
              "hyllytaso" => ''
            );

            paivita_tilausrivit_ja_sarjanumeroseuranta($rulla['ostorivitunnus'], $hylly, true);

          }
          else {

            $error  = t("Rulla on jo varastopaikalla");
            $error .= " {$rulla['hyllyalue']}-{$rulla['hyllynro']}";
            $errors[] = $error;
            $errors[] = t("Jos haluat vaihtaa varastopaikkaa, lue sarjanumero uudestaan.");
            $vaihto = true;

          }
        }

        if ($kuittaus) {
          if ($ylijaamasiirto) {
            $rullat = array($rulla);
          }
          else {

            $query = "SELECT trlt.rahtikirja_id, tr.tunnus
                      FROM sarjanumeroseuranta AS ss
                      JOIN tilausrivi AS tr
                        ON tr.yhtio = ss.yhtio
                        AND tr.tunnus = ss.ostorivitunnus
                        AND tr.tyyppi = 'O'
                      JOIN tilausrivin_lisatiedot AS trlt
                        ON trlt.yhtio = tr.yhtio
                        AND trlt.tilausrivitunnus = tr.tunnus
                      WHERE ss.yhtio = '{$kukarow['yhtio']}'
                      AND ss.sarjanumero = '{$sarjanumero}'";
            $result = pupe_query($query);
            $tilausrivi = mysql_fetch_assoc($result);
            $rahtikirja_id = $tilausrivi['rahtikirja_id'];

            $rullat = hae_rullat($rahtikirja_id);
          }
        }
        else {
          $rullat = null;
        }
      }
    }
    if (count($errors) == 0) {
      $view = 'tuotepaikka';
    }
    else{
      $view = 'sarjanumero';
    }
    break;
  case 'tuotepaikka':
    $rullat = hae_rullat($rahtikirja_id);

    if ($hylly = hae_hylly($tuotepaikka)) {
      $tuotepaikka = $hylly['hyllyalue'] . "-" . $hylly['hyllynro'];
      $aktiivinen_paikka = $tuotepaikka;
      $view = 'sarjanumero';
    }
    else {
      $errors[] = t("Ep�kelpo tuotepaikka!");
      $view = "tuotepaikka";
    }
    break;
  case 'aktiivipaikan_vaihto':
    $rullat = hae_rullat($rahtikirja_id);
    $view = 'tuotepaikka';
    break;
  case 'sarjanumero_tuotepaikka':

    if (isset($aktiivinen_paikka) and $aktiivinen_paikka != '') {
      $tuotepaikka = $aktiivinen_paikka;
    }

    if (tarkista_sarjanumero($sarjanumero) == false) {
      $errors[] = t("Sy�tetty� sarjanumeroa ei l�ydy");
      $view = 'sarjanumero';
      $rullat = hae_rullat($rahtikirja_id);
    }
    elseif (empty($tuotepaikka)) {
      $errors[] = t("Sy�t� tuotepaikka");
      $view = 'tuotepaikka';
      $rullat = hae_rullat($rahtikirja_id);
    }
    else{

      // Haetaan tilausrivi
      $query =   "SELECT tr.*
                  FROM sarjanumeroseuranta AS ss
                  JOIN tilausrivi AS tr
                    ON tr.yhtio = ss.yhtio
                    AND tr.tunnus = ss.ostorivitunnus
                    AND tr.tyyppi = 'O'
                  WHERE ss.yhtio = '{$kukarow['yhtio']}'
                  AND ss.sarjanumero = '{$sarjanumero}'";
      $result = pupe_query($query);
      $tilausrivi = mysql_fetch_assoc($result);

      // katsotaan onko saman rahdin paketteja laitettu saapumisella
      $query = "SELECT tilausrivi.uusiotunnus
                FROM tilausrivin_lisatiedot AS trlt
                JOIN tilausrivi
                  ON tilausrivi.yhtio = trlt.yhtio
                  AND tilausrivi.tunnus = trlt.tilausrivitunnus
                  AND tilausrivi.tyyppi != 'D'
                WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                AND trlt.rahtikirja_id = '{$rahtikirja_id}'
                AND tilausrivi.uusiotunnus != 0
                LIMIT 1";
      $result = pupe_query($query);

      if (mysql_num_rows($result) > 0) {
        $saapuminen = mysql_result($result, 0);
      }
      else {
        $query = "SELECT toimi.*
                  FROM tilausrivi
                  JOIN lasku ON lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus
                  JOIN toimi ON toimi.yhtio = lasku.yhtio AND toimi.tunnus = lasku.liitostunnus
                  WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                  AND tilausrivi.tunnus='{$tilausrivi['tunnus']}'";
        $result = pupe_query($query);
        $toimittaja = mysql_fetch_assoc($result);

        $saapuminen = uusi_saapuminen($toimittaja, $kukarow['toimipaikka']);
        $update_kuka = "UPDATE kuka SET kesken={$saapuminen} WHERE yhtio='{$kukarow['yhtio']}' AND kuka='{$kukarow['kuka']}'";
        $updated = pupe_query($update_kuka);
      }

      if ($hylly = hae_hylly($tuotepaikka)) {

        $tuotepaikka = $hylly['hyllyalue'] . "-" . $hylly['hyllynro'];

        $hyllyalue = $hylly['hyllyalue'];
        $hyllynro  = $hylly['hyllynro'];
        $hyllyvali = $hylly['hyllyvali'];
        $hyllytaso = $hylly['hyllytaso'];

        // Tarkistetaan onko sy�tetty hyllypaikka jo t�lle tuotteelle
        $tuotteen_oma_hyllypaikka = "SELECT * FROM tuotepaikat
                                     WHERE tuoteno = '{$tilausrivi['tuoteno']}'
                                     AND yhtio     = '{$kukarow['yhtio']}'
                                     AND hyllyalue = '$hyllyalue'
                                     AND hyllynro  = '$hyllynro'
                                     AND hyllyvali = '$hyllyvali'
                                     AND hyllytaso = '$hyllytaso'";
        $oma_paikka = pupe_query($tuotteen_oma_hyllypaikka);

        // Jos sy�tetty� paikkaa ei ole t�m�n tuotteen, lis�t��n uusi tuotepaikka
        if (mysql_num_rows($oma_paikka) == 0) {
          $_viesti = 'Saapumisessa';
          lisaa_tuotepaikka($tilausrivi['tuoteno'], $hyllyalue, $hyllynro, $hyllyvali, $hyllytaso, $_viesti);
        }
        else {
          // Nollataan poistettava kentt� varmuuden vuoksi
          $query = "UPDATE tuotepaikat SET
                    poistettava   = ''
                    WHERE tuoteno = '{$tilausrivi['tuoteno']}'
                    AND yhtio     = '{$kukarow['yhtio']}'
                    AND hyllyalue = '$hyllyalue'
                    AND hyllynro  = '$hyllynro'
                    AND hyllyvali = '$hyllyvali'
                    AND hyllytaso = '$hyllytaso'";
          pupe_query($query);
        }

        paivita_tilausrivit_ja_sarjanumeroseuranta($tilausrivi['tunnus'], $hylly);

        $query = "UPDATE tilausrivi SET
                  uusiotunnus = '{$saapuminen}'
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  = '{$tilausrivi['tunnus']}'";
        pupe_query($query);

        $kukarow['ei_echoa'] = 'joo';
        vie_varastoon($saapuminen, 0, $hylly, $tilausrivi['tunnus']);
        unset($kukarow['ei_echoa']);

        $rullat = hae_rullat($rahtikirja_id);

        // tarkistetaan oliko tuloutettu rulla jonkin myyntitilauksen vika rulla
        $query = "SELECT myyntirivitunnus
                  FROM sarjanumeroseuranta
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND ostorivitunnus = '{$tilausrivi['tunnus']}'";
        $result = pupe_query($query);
        $myyntirivitunnus = mysql_result($result,0);

        $query = "SELECT tilausrivin_lisatiedot.asiakkaan_tilausnumero
                  FROM tilausrivin_lisatiedot
                  JOIN tilausrivi
                    ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio
                    AND tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
                    AND tilausrivi.tyyppi = 'L')
                  WHERE tilausrivin_lisatiedot.yhtio = '{$kukarow['yhtio']}'
                  AND tilausrivitunnus = '{$myyntirivitunnus}'";
        $result = pupe_query($query);
        $asiakkaan_tilausnumero = mysql_result($result,0);

        $query = "SELECT *
                  FROM tilausrivi
                  JOIN tilausrivin_lisatiedot
                    ON tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
                    AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
                  WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                  AND tilausrivi.tyyppi = 'L'
                  AND tilausrivi.var = 'P'
                  AND tilausrivin_lisatiedot.asiakkaan_tilausnumero = '{$asiakkaan_tilausnumero}'";
        $result = pupe_query($query);

        if (mysql_num_rows($result) == 0) {

          $query = "UPDATE lasku SET
                    alatila = 'A'
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tila = 'W'
                    AND alatila NOT IN ('T', 'TX')
                    AND asiakkaan_tilausnumero  = '{$asiakkaan_tilausnumero}'";
          pupe_query($query);
        }

        $view = 'sarjanumero';
        $aktiivinen_paikka = $tuotepaikka;
      }
      else {
        $errors[] = t("Ep�kelpo tuotepaikka!");
        $view = "tuotepaikka";
        $rullat = hae_rullat($rahtikirja_id);
      }
    }
    break;
  default:
    $errors[] = 'error';
  }
}
else {
  $view = 'sarjanumero';
}

echo "<div class='header'>";

echo "<div class='header_left'>";
echo "<a href='index.php' class='button header_button'>";
echo t("P��valikko");
echo "</a>";
echo "</div>";

echo "<div class='header_center'>";
echo "<h1>";
echo t("VARASTOON VIENTI");
echo "</h1>";
echo "</div>";

echo "<div class='header_right'>";
echo "<a href='{$palvelin2}logout.php?location={$palvelin2}sarjanumero' class='button header_button'>";
echo t("Kirjaudu ulos");
echo "</a>";
echo "</div>";

echo "</div>";

if ($view == 'sarjanumero') {

  $vaihtoinput = "";

  if ($vaihto) {
    $vaihtoinput = "<input type='hidden' name='vaihdettava_sarjanumero' value='{$sarjanumero}' />";
  }

  if (isset($aktiivinen_paikka) and $aktiivinen_paikka != '') {
    $submit = "sarjanumero_tuotepaikka";
  }
  else {
    $submit = "sarjanumero";
  }

  echo "
  <form method='post' action='tuloutus.php'>
    <div style='text-align:center;padding:10px;'>
      <label for='sarjanumero'>", t("Sarjanumero"), "</label><br>
      <input type='hidden' name='rahtikirja_id' value='{$rahtikirja_id}' />
      <input type='hidden' name='aktiivinen_paikka' value='{$aktiivinen_paikka}' />
      {$vaihtoinput}
      <input type='text' id='sarjanumero' name='sarjanumero' style='margin:10px;' />
      <br>
      <button name='submit' value='{$submit}' onclick='submit();' class='button'>", t("OK"), "</button>
    </div>
  </form>";
}

if ($view == 'tuotepaikka') {

  if (isset($sarjanumero) and $sarjanumero != '') {
    $submit = "sarjanumero_tuotepaikka";
  }
  else {
    $submit = "tuotepaikka";
  }

  echo "
  <form method='post' action='tuloutus.php'>
    <div style='text-align:center;padding:10px;'>
    <input type='hidden' name='sarjanumero' value='{$sarjanumero}' />
    <input type='hidden' name='rahtikirja_id' value='{$rahtikirja_id}' />
      <label for='sarjanumero'>", t("Tuotepaikka"), "</label><br>
      <input type='text' id='tuotepaikka' name='tuotepaikka' style='margin:10px;' />
      <br>
      <button name='submit' value='{$submit}' onclick='submit();' class='button'>", t("OK"), "</button>
    </div>
  </form>";
}

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

if (isset($aktiivinen_paikka) and $aktiivinen_paikka != '') {

  echo "<div style='text-align:center; padding:10px; width:700px; margin:0 auto; overflow:auto;'>";

  echo "<div style='display:inline-block; margin:6px;'>";
  echo "<button name='submit' value='aktiivipaikan_vaihto'  class='aktiivi'>";
  echo t("Vied��n paikalle: ") . $aktiivinen_paikka;
  echo "</button></div>";

  echo "<div style='display:inline-block; margin:6px;'>";
  echo "<form method='post' action=''>";
  echo "<input type='hidden' name='rahtikirja_id' value='{$rahtikirja_id}' />";
  echo "<button name='submit' value='aktiivipaikan_vaihto' onclick='submit();'>";
  echo t("Vaihda");
  echo "</button></form></div>";

  echo "</div>";

}

foreach ($rullat as $rulla) {

  $paino = (int) $rulla['paino'];
  echo "<div class='main' style='text-align:center;'>";

  if ($rulla['uusiotunnus'] == 0) {
    if ($rulla['sarjanumero'] == $sarjanumero) {
      echo "<div class='listadiv vietava'>UIB: {$rulla['sarjanumero']} ({$paino} kg)</div>";
    }
    else{
      echo "<div class='listadiv'>UIB: {$rulla['sarjanumero']} ({$paino} kg) tulouttamatta</div>";
    }
  }
  else {
    if ($rulla['sarjanumero'] == $sarjanumero and !$vaihto) {
      $luokka = "vietava";
    }
    else{
     $luokka = "viety";
    }
    $paikka = "{$rulla['alue']}-{$rulla['nro']}";
    echo "<div class='listadiv {$luokka}' >UIB: {$rulla['sarjanumero']} ({$paino} kg) on paikalla {$paikka}</div>";
  }

  echo "</div>";
}

echo "
<script type='text/javascript'>

  $( document ).ready(function() {
    $('#{$view}').focus();
  });

  $(document).on('touchstart', function(){
    $('#{$view}').focus();
  });

</script>";

require 'inc/footer.inc';
