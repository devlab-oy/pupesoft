<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");

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

      $query = "SELECT
                lasku.sisviesti1 AS ohje,
                laskun_lisatiedot.konttityyppi AS tyyppi,
                tilausrivi.toimitettu,
                group_concat(tilausrivi.var) AS status
                FROM laskun_lisatiedot
                JOIN lasku
                  ON lasku.yhtio = laskun_lisatiedot.yhtio
                  AND lasku.tunnus = laskun_lisatiedot.otunnus
                JOIN tilausrivi
                  ON tilausrivi.yhtio = lasku.yhtio
                  AND tilausrivi.otunnus = lasku.tunnus
                WHERE laskun_lisatiedot.yhtio = '{$kukarow['yhtio']}'
                AND laskun_lisatiedot.konttiviite = '{$konttiviite}'
                GROUP BY tilausrivi.toimitettu
                ORDER BY tilausrivi.toimitettu ASC";
      $result = pupe_query($query);

      $info  = mysql_fetch_assoc($result);

      // katsotaan onko kaikki rullat varastossa
      $status = explode(",", $info['status']);

      if (!$info) {
        $errors[] = t("Ei löytynyt kontitettavia rullia.");
        $view = 'konttiviite';
      }
      elseif (in_array('P', $status)) {
        $errors[] = t("Kaikkia rullia ei ole tuloutettu.");
        $view = 'konttiviite';
      }
      elseif ($info['toimitettu'] != '') {
        $errors[] = t("Rullat on jo kontitettu.");
        $view = 'konttiviite';
      }
      else{

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

      $kontit_rullineen = kontit_rullineen($konttiviite, $maxkg);

      if ($kontit_rullineen === false) {
        $errors[] = t("Tilausnumerolla ei löydy tilausta.");
        $view = 'tilausnumero';
      }
      elseif(count($kontit_rullineen) == 0) {
        $errors[] = t("Tilausksella ei ole kontitettavia rullia.");
        $view = 'tilausnumero';
      }
      else{
        $view = 'kontituslista';
      }
    }
    break;
  case 'kontitus':
    $query = "UPDATE tilausrivi SET
              keratty = '{$kukarow['kuka']}',
              kerattyaika = NOW()
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus = '{$rivitunnus}'";
    pupe_query($query);
    $kontit_rullineen = kontit_rullineen($konttiviite, $maxkg);
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

    $kontit_rullineen = kontit_rullineen($konttiviite, $maxkg);
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
    $kontit_rullineen = kontit_rullineen($konttiviite, $maxkg);
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

echo "
<div class='header'>
  <button onclick='window.location.href=\"index.php\"' class='button left'><img src='back2.png'></button>
  <h1>", t("Kontitus"), "</h1>
</div>";

echo "
<div class='error' style='text-align:center'>";
foreach ($errors as $error) {
  echo $error."<br>";
}
echo "</div>";

if ($view == 'konttiviite') {
  echo "
  <form method='post' action=''>
    <div style='text-align:center;padding:10px;'>
      <label for='konttiviite'>", t("Konttiviite"), "</label><br>
      <input type='text' id='konttiviite' name='konttiviite' style='margin:10px;' />
      <br>
      <button name='submit' value='konttiviite' onclick='submit();' class='button'>", t("OK"), "</button>
    </div>
  </form>

  <script type='text/javascript'>
    $(document).on('touchstart', function(){
      $('#konttiviite').focus();
    });
  </script>";
}

if ($view == 'konttiviite_maxkg') {

  if (!empty($info)) {

    echo "<div style='text-align:center;padding:10px; margin:0 auto; width:500px;'>";

    echo "<p>Konttiviite: {$konttiviite}</p>";
    echo "<p>Konttityyppi {$info['tyyppi']}</p>";
    echo "<p>Maksimikapasitetti: {$info['maxkg']} kg</p>";
    echo "<p>{$info['ohje']}</p>";

    echo "</div>";

  }

  echo "
  <form method='post' action=''>
    <div style='text-align:center;padding:10px;'>
      <label for='konttiviite'>", t("Konttien maksimi kilomäärä"), "</label><br>
      <input type='hidden' name='konttiviite' value='{$konttiviite}' />
      <input type='text' id='maxkg' name='maxkg' style='margin:10px;' value='{$info['maxkg']}' />
      <br>
      <button name='submit' value='konttiviite_maxkg' onclick='submit();' class='button'>", t("OK"), "</button>
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
    <div style='text-align:center;padding:10px;'>
      <label for='sarjanumero'>", t("Sarjanumero"), "</label><br>
      <input type='text' id='sarjanumero' name='sarjanumero' style='margin:10px;' />
      <input type='hidden' name='konttiviite' value='{$konttiviite}' />
      <input type='hidden' name='maxkg' value='{$maxkg}' />
      <br>
      <button name='submit' value='sarjanumero' onclick='submit();' class='button'>", t("OK"), "</button>
    </div>
  </form>

  <script type='text/javascript'>
    $(document).on('touchstart', function(){
      $('#sarjanumero').focus();
    });
  </script>";

  echo "<div style='text-align:center;padding:10px;'>";

  foreach ($kontit_rullineen as $key => $kontti) {

    echo "<a name='{$key}'><h1>{$key}</h1></a><br>";

    $keraamattomat = 0;
    $rullat_kontissa = '';
    $kontitettu = true;
    $kontin_kilot = 0;

    foreach ($kontti as $rulla) {
      if ($rulla['keratty'] == '') {
        echo "
        Rulla  {$rulla['sarjanumero']}
        <form method='post' action='kontitus.php#{$key}'>
          <input type='hidden' name='rivitunnus' value='{$rulla['tunnus']}' />
          <input type='hidden' name='konttiviite' value='{$konttiviite}' />
          <input type='hidden' name='maxkg' value='{$maxkg}' />
          <button name='submit' value='kontitus' onclick='submit();' class='button'>", t("Kontita"), "</button>
        </form><br>";
        $keraamattomat++;
      }
      else {
        echo  "Rulla  {$rulla['sarjanumero']} Kerätty!<br>";
        $rullat_kontissa .= $rulla['tunnus'] . ',';
      }
      if ($rulla['konttinumero'] == '') {
        $kontitettu = false;
      }
      $kontin_kilot = $kontin_kilot + $rulla['paino'];
    }

    if ($keraamattomat == 0 and $kontitettu === false) {
      echo "
      <br>
      Kaikki kontin rullat kerätty. Syötä kontin tiedot:<br>

      <form method='post' action=''>
        <div style='text-align:center;padding:10px;'>
          <label for='konttinumero'>", t("Konttinumero"), "</label><br>
          <input type='text' id='konttinumero' name='konttinumero' value='{$konttinumero}' style='margin:10px;' /><br>
          <label for='sinettinumero'>", t("Sinettinumero"), "</label><br>
          <input type='text' id='sinettinumero' name='sinettinumero' value='{$sinettinumero}' style='margin:10px;' /><br>
          <input type='hidden' name='maxkg' value='{$maxkg}' />
          <input type='hidden' name='konttiviite' value='{$konttiviite}' />
          <input type='hidden' name='rullat_kontissa' value='{$rullat_kontissa}' />
          <input type='hidden' name='kontin_kilot' value='{$kontin_kilot}' />
          <button name='submit' value='konttitiedot' onclick='submit();' class='button'>", t("Lähetä kontitussanoma"), "</button>
        </div>
      </form>";
    }
    elseif ($keraamattomat == 0 and $kontitettu === true) {
      echo "
      <br>
      Kaikki kontin rullat kerätty ja kontitettu.
      <br>
      Kontitussanoma lähetetty.
      <br>
      Kontti#: {$kontti[$key]['toimitettu']}
      <br><br>";
    }
    echo "<hr>";
  }
  echo "</div>";
}

if ($view == 'lahetetty') {
  echo "<div style='text-align:center;'>";
  echo "Sanoma lähetetty.";
  echo "</div>";
}

require 'inc/footer.inc';
