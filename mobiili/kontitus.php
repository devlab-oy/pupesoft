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

      $kontit_rullineen = kontit_rullineen($konttiviite);

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
    $kontit_rullineen = kontit_rullineen($konttiviite);
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

    $kontit_rullineen = kontit_rullineen($konttiviite);
    $view = 'kontituslista';
    break;

  case 'vahvista':
    $view = 'vahvistus';
    break;

  case 'konttitiedot':
    if (isset($sinettinumero) and isset($konttinumero)) {

      $rullat_kontissa = rtrim($rullat_kontissa,",");

      $query = "UPDATE tilausrivi
                SET toimitettu = '{$konttinumero}'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus IN ({$rullat_kontissa})";
      pupe_query($query);


      $parametrit = kontitus_parametrit($rullat_kontissa);

      if ($parametrit) {
        $parametrit['laji'] = 'kontitus';
        $parametrit['kontitus_info']['konttinumero'] = $konttinumero;
        $parametrit['kontitus_info']['sinettinumero'] = $sinettinumero;
        $sanoma = laadi_edifact_sanoma($parametrit);

        echo $sanoma;die;


      }
      else {
        $errors[] = t("Tilausta ei löytynyt!");
      }

      if ($sanoma) {
        $lahetys = 'X';
        if (laheta_sanoma($sanoma)) {
          $lahetys = 'OK';
          $view = 'lahetetty';
        }
        else {
          $errors[] = t("Lähetys ei onnistunut");
          $view = 'kontituslista';
        }
      }
      else {
        $errors[] = t("Ei sanomaa");
        $view = 'kontituslista';
      }


    }
    else {
      $errors[] = t("Syötä konttitiedot");
    }
    $kontit_rullineen = kontit_rullineen($konttiviite);
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
    $(document).ready(function() {
      $('#konttiviite').focus();
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
      <br>
      <button name='submit' value='sarjanumero' onclick='submit();' class='button'>", t("OK"), "</button>
    </div>
  </form>

  <script type='text/javascript'>
    $(document).ready(function() {
      $('#sarjanumero').focus();
    });
  </script>";

  echo "<div style='text-align:center;padding:10px;'>";



    foreach ($kontit_rullineen as $key => $kontti) {

      echo "<a name='{$key}'><h1>{$key}</h1></a><br>";

      $keraamattomat = 0;
      $rullat_kontissa = '';
      $kontitettu = true;

      foreach ($kontti as $rulla) {
        if ($rulla['keratty'] == '') {
          echo "
          Rulla  {$rulla['sarjanumero']}
          <form method='post' action='kontitus.php#{$key}'>
            <input type='hidden' name='rivitunnus' value='{$rulla['tunnus']}' />
            <input type='hidden' name='konttiviite' value='{$konttiviite}' />
            <button name='submit' value='kontitus' onclick='submit();' class='button'>", t("Kontita"), "</button>
          </form><br>";
          $keraamattomat++;
        }
        else{
          echo  "Rulla  {$rulla['sarjanumero']} Kerätty!<br>";
          $rullat_kontissa .= $rulla['tunnus'] . ',';
        }
        if ($rulla['toimitettu'] == '') {
          $kontitettu = false;
        }
      }

      if ($keraamattomat == 0 and $kontitettu === false) {
        echo "
        <br>
        Kaikki kontin rullat kerätty. Syötä kontin tiedot:<br>

        <form method='post' action=''>
          <div style='text-align:center;padding:10px;'>
            <label for='konttinumero'>", t("Konttinumero"), "</label><br>
            <input type='text' id='konttinumero' name='konttinumero' style='margin:10px;' /><br>
            <label for='sinettinumero'>", t("Sinettinumero"), "</label><br>
            <input type='text' id='sinettinumero' name='sinettinumero' style='margin:10px;' /><br>
            <input type='hidden' name='konttiviite' value='{$konttiviite}' />
            <input type='hidden' name='rullat_kontissa' value='{$rullat_kontissa}' />
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

