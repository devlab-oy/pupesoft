<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");

echo "<meta name='viewport' content='width=device-width, maximum-scale=1.0' />\n";
echo "<link rel='stylesheet' type='text/css' href='ipad.css' />\n";
echo "<body>";

require 'generoi_edifact.inc';

$errors = array();

if (isset($submit)) {

  switch ($submit) {
  case 'konttiviite':
    if (empty($konttiviite)) {
      $errors[] = t("Syötä konttiviite");
      $view = 'konttiviite';
    }
    else {

      $query = "SELECT lasku.sisviesti1 AS ohje,
                laskun_lisatiedot.konttityyppi,
                tilausrivi.toimitettu,
                tilausrivi.tunnus,
                tilausrivi.var,
                trlt.konttinumero
                FROM laskun_lisatiedot
                JOIN lasku
                  ON lasku.yhtio = laskun_lisatiedot.yhtio
                  AND lasku.tunnus = laskun_lisatiedot.otunnus
                JOIN tilausrivi
                  ON tilausrivi.yhtio = lasku.yhtio
                  AND tilausrivi.otunnus = lasku.tunnus
                JOIN tilausrivin_lisatiedot AS trlt
                  ON trlt.yhtio = tilausrivi.yhtio
                  AND trlt.tilausrivitunnus = tilausrivi.tunnus
                WHERE laskun_lisatiedot.yhtio = '{$kukarow['yhtio']}'
                AND laskun_lisatiedot.konttiviite = '{$konttiviite}'";


      if ($muutos == 'muutos' ) {

        $result = pupe_query($query);

        while ($rulla = mysql_fetch_assoc($result)) {

          if ($rulla['toimitettu'] == '') {

            $uquery = "UPDATE tilausrivi SET
                      keratty = '',
                      kerattyaika = '0000-00-00 00:00:00'
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tunnus = '{$rulla['tunnus']}'";
            pupe_query($uquery);

            $uquery = "UPDATE tilausrivin_lisatiedot SET
                      konttinumero = ''
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tilausrivitunnus = '{$rulla['tunnus']}'";
            pupe_query($uquery);

          }
        }
      }

      $yliajo = false;

      $tuloutettu = true;
      $rullia_loytyy = true;
      $kontissa = false;
      $ei_kontissa = false;
      $kontitettu = false;

      $result = pupe_query($query);

      if (mysql_num_rows($result) == 0) {
        $rullia_loytyy = false;
      }
      else{

        $rivitunnukset = '';

        while ($rulla = mysql_fetch_assoc($result)) {

          if ($rulla['var'] == 'P') {
            $tuloutettu = false;
          }

          if ($rulla['toimitettu'] != '') {
            $kontitettu = true;
          }

          if ($rulla['konttinumero'] != '') {
            $kontissa = true;
          }
          else{
            $ei_kontissa = true;
          }

          $kontitusohje = $rulla['ohje'];
          $tyyppi = $rulla['konttityyppi'];

          $rivitunnukset .= $rulla['tunnus'] . ',';
        }
      }

      $rivitunnukset = rtrim($rivitunnukset, ',');

      if ($rullia_loytyy == false) {
        $errors[] = t("Ei löytynyt kontitettavia rullia.");
        $view = 'konttiviite';
      }
      elseif ($tuloutettu == false) {
        $errors[] = t("Kaikkia rullia ei ole tuloutettu.");
        $view = 'konttiviite';
      }
      elseif ($kontitettu == true) {
        $errors[] = t("Rullat on jo kontitettu ja kontti sinetöity.");
        $view = 'konttiviite';
      }
      elseif ($kontissa == true and $ei_kontissa == false) {
        $errors[] = t("Kaikki viitteen alaiset rullat on jo kontitettu.");
        $yliajo = true;
        $view = 'konttiviite';
      }
      elseif ($kontissa == true and $ei_kontissa == true) {
        $errors[] = t("Osa viitteen alaisista rullista on jo kontitettu.");
        $yliajo = 'X';
        $view = 'konttiviite';
      }
      else{

        $info = array(
          'kontitusohje' => $kontitusohje,
          'tyyppi' => $tyyppi
          );

        // kovakoodatut max-kilot...
        switch ($info['tyyppi']) {
        case 'C20':
        case 'C20OP':
          $info['maxkg'] = 22000;
          break;
        case 'C40':
        case 'C40OP':
        case 'C40HC':
          $info['maxkg'] = 27000;
          break;
        default:
          $info['maxkg'] = 22000;
        }

        $query = "SELECT concat(hyllyalue, '-', hyllynro) AS hylly
                  FROM sarjanumeroseuranta
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND myyntirivitunnus IN ({$rivitunnukset})";
        $result = pupe_query($query);

        $rullat_varastossa = array();

        while ($rulla = mysql_fetch_assoc($result)) {
          if (!isset($rullat_varastossa[$rulla['hylly']])) {
            $rullat_varastossa[$rulla['hylly']] = 1;
          }
          else {
            $rullat_varastossa[$rulla['hylly']]++;
          }
        }

        $view = 'konttiviite_maxkg';
      }
    }
    break;
  case 'konttiviite_maxkg':

    if (empty($maxkg)) {
      $errors[] = t("Syötä kilomäärä");
      $view = 'konttiviite_maxkg';
    }
    else {

      $rullat_ja_kontit = rullat_ja_kontit($konttiviite, $maxkg);

      $kontittamattomat = $rullat_ja_kontit['kontittamattomat'];
      $kontitetut = $rullat_ja_kontit['kontitetut'];
      $kontit = $rullat_ja_kontit['kontit'];
      $konttimaara = count($kontit);

      $aktiivinen_kontti = 1;

      if ($rullat_ja_kontit === false) {
        $errors[] = t("Tilausnumerolla ei löydy tilausta.");
        $view = 'tilausnumero';
      }
      elseif(count($kontittamattomat) == 0 and count($kontitetut) == 0) {
        $errors[] = t("Tilauksella ei ole kontitettavia rullia.");
        $view = 'tilausnumero';
      }
      else{
        $view = 'kontituslista';
      }
    }
    break;
  case 'jatka':
    $query = "SELECT trlt.konttinumero
              FROM laskun_lisatiedot
              JOIN lasku
                ON lasku.yhtio = laskun_lisatiedot.yhtio
                AND lasku.tunnus = laskun_lisatiedot.otunnus
              JOIN tilausrivi
                ON tilausrivi.yhtio = lasku.yhtio
                AND tilausrivi.otunnus = lasku.tunnus
              JOIN tilausrivin_lisatiedot AS trlt
                ON trlt.yhtio = tilausrivi.yhtio
                AND trlt.tilausrivitunnus = tilausrivi.tunnus
              WHERE laskun_lisatiedot.yhtio = '{$kukarow['yhtio']}'
              AND laskun_lisatiedot.konttiviite = '{$konttiviite}'";
    $result = pupe_query($query);
    $konttiinfo = mysql_fetch_assoc($result);
    $konttiinfo = $konttiinfo['konttinumero'];
    $konttiinfo = explode("/", $konttiinfo);

    $maxkg = $konttiinfo[2];

    $rullat_ja_kontit = rullat_ja_kontit($konttiviite, $maxkg);

    $kontittamattomat = $rullat_ja_kontit['kontittamattomat'];
    $kontitetut = $rullat_ja_kontit['kontitetut'];
    $kontit = $rullat_ja_kontit['kontit'];
    $konttimaara = count($kontit);

    $aktiivinen_kontti = 1;

    if ($rullat_ja_kontit === false) {
      $errors[] = t("Tilausnumerolla ei löydy tilausta.");
      $view = 'tilausnumero';
    }
    elseif(count($kontittamattomat) == 0 and count($kontitetut) == 0) {
      $errors[] = t("Tilauksella ei ole kontitettavia rullia.");
      $view = 'tilausnumero';
    }
    else{
      $view = 'kontituslista';
    }

    break;

  case 'konttivalinta':
    if (!isset($aktiivinen_kontti)) {
      $aktiivinen_kontti = 1;
    }

    $rullat_ja_kontit = rullat_ja_kontit($konttiviite, $maxkg);
    $kontittamattomat = $rullat_ja_kontit['kontittamattomat'];
    $kontitetut = $rullat_ja_kontit['kontitetut'];
    $kontit = $rullat_ja_kontit['kontit'];
    $konttimaara = count($kontit);
    $view = 'kontituslista';
    break;
  case 'sarjanumero':
    $query = "SELECT myyntirivitunnus
              FROM sarjanumeroseuranta
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND sarjanumero = '{$sarjanumero}'";
    $result = pupe_query($query);
    $rivitunnus = mysql_result($result, 0);

    $query = "UPDATE tilausrivi SET
              keratty = '{$kukarow['kuka']}',
              kerattyaika = NOW()
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus = '{$rivitunnus}'";
    pupe_query($query);

    $temp_konttinumero = $aktiivinen_kontti . "/" . $konttimaara . "/" . $maxkg;

    $query = "UPDATE tilausrivin_lisatiedot SET
              konttinumero = '{$temp_konttinumero}'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tilausrivitunnus = '{$rivitunnus}'";
    pupe_query($query);

    $rullat_ja_kontit = rullat_ja_kontit($konttiviite, $maxkg);

    $kontittamattomat = $rullat_ja_kontit['kontittamattomat'];
    $kontitetut = $rullat_ja_kontit['kontitetut'];
    $kontit = $rullat_ja_kontit['kontit'];
    $view = 'kontituslista';
    break;

  case 'vahvista':
    $view = 'vahvistus';
    break;

  case 'konttitiedot':
    if (!empty($sinettinumero) and !empty($konttinumero)) {

      $rullat_kontissa = rtrim($rullat_kontissa,",");

      $query = "UPDATE tilausrivi SET
                toimitettu = '{$kukarow['kuka']}',
                toimitettuaika = NOW()
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus IN ({$rullat_kontissa})";
      pupe_query($query);

      $query = "UPDATE tilausrivin_lisatiedot SET
                konttinumero = '{$konttinumero}',
                sinettinumero = '{$sinettinumero}',
                kontin_kilot = '{$kontin_kilot}'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tilausrivitunnus IN ({$rullat_kontissa})";
      pupe_query($query);

      /* // todo katso onko jonkin tilauksen vika rulla
      $query = "SELECT
                FROM tilausrivi
                JOIN tilausrivin_lisatiedot
                  ON tilausrivin_lisatiedot.tila ";

      */


      $parametrit = kontitus_parametrit($rullat_kontissa);

      if ($parametrit) {
        $parametrit['kontitus_info']['konttinumero'] = $konttinumero;
        $parametrit['kontitus_info']['sinettinumero'] = $sinettinumero;
        $sanoma = laadi_edifact_sanoma($parametrit);
      }
      else {
        $errors[] = t("Tilausta ei löytynyt!");
      }

      if ($sanoma) {
        $lahetys = 'X';
        if (laheta_sanoma($sanoma)) {
          $lahetys = 'OK';
        }
        else {
          $errors[] = t("Lähetys ei onnistunut");
        }
      }
      else {
        $errors[] = t("Ei sanomaa");
      }
    }
    else {
      $errors[] = t("Syötä konttitiedot");
    }
    $rullat_ja_kontit = rullat_ja_kontit($konttiviite, $maxkg);
    $view = 'kontituslista';
    break;

  case 'takaisin':
    echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=index.php'>";
    die;

  default:
    $errors[] = 'error';
  }
}
else {
  $view = 'konttiviite';
}

echo "<div class='header'>";

echo "<div class='header_left'>";
echo "<a href='index.php' class='button header_button'>";
echo t("Päävalikko");
echo "</a>";
echo "</div>";

echo "<div class='header_center'>";
echo "<h1>";
echo t("KONTITUS");
echo "</h1>";
echo "</div>";

echo "<div class='header_right'>";
echo "<a href='{$palvelin2}logout.php?location={$palvelin2}sarjanumero' class='button header_button'>";
echo t("Kirjaudu ulos");
echo "</a>";
echo "</div>";

echo "</div>";

echo "<div style='text-align:center;padding:10px; margin:0 auto; width:750px;'>";

echo "<div class='error center'>";

foreach ($errors as $error) {
  echo $error."<br>";
}
echo "</div>";

if ($view == 'konttiviite') {

  if ($yliajo) {

    echo "
      <div style='display:inline-block; margin:6px;'>
      <form method='post' action=''>
        <input type='hidden' name='konttiviite' value='{$konttiviite}' />
        <input type='hidden' name='muutos' value='muutos' />
        <button name='submit' value='konttiviite' onclick='submit();' class='{$luokka}'>" . t("Muuta kontitusta") . "</button>
      </form>
      </div>";
  }

  if ($yliajo === "X") {

    echo "
      <div style='display:inline-block; margin:6px;'>
      <form method='post' action=''>
        <input type='hidden' name='konttiviite' value='{$konttiviite}' />
        <button name='submit' value='jatka' onclick='submit();' class='{$luokka}'>" . t("Jatka kontitusta") . "</button>
      </form>
      </div>";
  }



  echo "
  <form method='post' action=''>
      <label for='konttiviite'>", t("Konttiviite"), "</label><br>
      <input type='text' id='konttiviite' name='konttiviite' style='margin:10px;' />
      <br>
      <button name='submit' value='konttiviite' onclick='submit();' class='button'>", t("OK"), "</button>
  </form>

  <script type='text/javascript'>
    $(document).on('touchstart', function(){
      $('#konttiviite').focus();
    });
  </script>";
}

if ($view == 'konttiviite_maxkg') {

  echo "<div style='text-align:center;padding:10px; margin:0 auto; width:500px;'>";
  echo "<table border='0'>";

  echo "<tr>";
  echo "<td style='text-align:right; width:50%'>Konttiviite: </td>";
  echo "<td style='text-align:left; width:50%'>{$konttiviite}</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td style='text-align:right;'>Konttityyppi: </td>";
  echo "<td style='text-align:left;'>{$info['tyyppi']}</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td style='text-align:right;'>Max-kapasiteetti: </td>";
  echo "<td style='text-align:left;'>{$info['maxkg']}</td>";
  echo "</tr>";

  if ($info['kontitusohje'] != '') {

    echo "<tr>";
    echo "<td colspan='2' style='padding:8px 0'>";

    echo "<div class='ohjediv'>";
    echo "Bookkaussanoman kontitusohje:<br><br>";
    echo $info['kontitusohje'];
    echo "</div>";

    echo "</td>";
    echo "</tr>";

  }

  echo "<tr>";
  echo "<td colspan='2'  style='padding:8px 0'>Rullien sijainnit:</td>";
  echo "</tr>";


  foreach ($rullat_varastossa as $hylly => $maara) {
    echo "<tr>";
    echo "<td style='text-align:right; width:50%'>{$hylly}: </td>";
    echo "<td style='text-align:left; width:50%'> {$maara} kpl.</td>";
    echo "</tr>";
  }

  echo "<tr>";
  echo "<td align='right'>Yhteensä: </td>";
  echo "<td align='left'> " . array_sum($rullat_varastossa) . " kpl.</td>";
  echo "</tr>";

  echo "</table>";
  echo "</div>";

  echo "
  <form method='post' action=''>
    <div style='text-align:center;padding:10px;'>
      <label for='konttiviite'>", t("Konttien maksimi kilomäärä"), "</label><br>
      <input type='hidden' name='konttiviite' value='{$konttiviite}' />
      <input type='text' id='maxkg' name='maxkg' style='margin:10px;' value='{$info['maxkg']}' />
      <br>
      <button name='submit' value='konttiviite_maxkg' onclick='submit();' class='button'>", t("Jatka"), "</button>
    </div>
  </form>

  <script type='text/javascript'>
    $(document).on('touchstart', function(){
      $('#maxkg').focus();
    });
  </script>";
}

if ($view == 'kontituslista') {

  echo "
  <form method='post' action=''>
      <label for='sarjanumero'>", t("Sarjanumero"), "</label><br>
      <input type='text' id='sarjanumero' name='sarjanumero' style='margin:10px;' />
      <input type='hidden' name='konttiviite' value='{$konttiviite}' />
      <input type='hidden' name='maxkg' value='{$maxkg}' />
      <input type='hidden' name='konttimaara' value='{$konttimaara}' />
      <input type='hidden' name='aktiivinen_kontti' value='{$aktiivinen_kontti}' />
      <br>
      <button name='submit' value='sarjanumero' onclick='submit();' class='button'>", t("OK"), "</button>
  </form>

  <script type='text/javascript'>
    $(document).on('touchstart', function(){
      $('#sarjanumero').focus();
    });
  </script>";


  $tarvittava_maara = count($kontit);

  echo "<div style='text-align:center; padding:10px; width:700px; margin:0 auto; overflow:auto;'>";


  foreach ($kontitetut as $rulla) {
    $kontitusinfo = explode("/", $rulla['konttinumero']);
    $konttinumero = $kontitusinfo[0];
    $kontit[$konttinumero] = $kontit[$konttinumero] + $rulla['paino'];
  }

  foreach ($kontit as $key => $kontti) {

    if ($key == $aktiivinen_kontti) {
      $luokka = "button aktiivi";
    }
    else {
      $luokka = "button";
    }

    echo "
      <div style='display:inline-block; margin:6px;'>
      <form method='post' action=''>
        <input type='hidden' name='aktiivinen_kontti' value='{$key}' />
        <input type='hidden' name='konttiviite' value='{$konttiviite}' />
        <input type='hidden' name='maxkg' value='{$maxkg}' />
        <button name='submit' value='konttivalinta' onclick='submit();' class='{$luokka}'>" . t("Kontti") ."-". $key . " (" . $kontti . "kg)</button>
      </form>
      </div>";
    }


    foreach ($kontitetut as $rulla) {

      $kontitusinfo = explode("/", $rulla['konttinumero']);
      $konttinumero = $kontitusinfo[0];

      echo "<div class='listadiv kontissa'>";
      echo "Rulla " . $rulla['sarjanumero'] . " kontissa " . $konttinumero;
      echo "</div>";
    }

    foreach ($kontittamattomat as $rulla) {
      echo "<div class='listadiv'>";
      echo "Rulla " . $rulla['sarjanumero'] . " kontittamatta";
      echo "</div>";
    }

  echo "</div>";
}

echo "</div>";

require 'inc/footer.inc';
