<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");

echo "<meta name='viewport' content='width=device-width, maximum-scale=1.0' />\n";
echo "<link rel='stylesheet' type='text/css' href='ipad.css' />\n";
echo "<body>";

if (!isset($errors)) $errors = array();

if (isset($submit)) {

  $lahetys = 'X';

  if (empty($sarjanumero)) {
    $errors[] = t("Syötä sarjanumero");
  }
  else{
    $query = "SELECT ss.massa, la.asiakkaan_tilausnumero
              FROM sarjanumeroseuranta AS ss
              JOIN tilausrivi AS tr ON tr.yhtio = ss.yhtio AND tr.tunnus = ss.ostorivitunnus
              JOIN lasku AS la ON la.yhtio = tr.yhtio AND la.tunnus = tr.otunnus
              WHERE ss.yhtio = '{$kukarow['yhtio']}'
              AND ss.sarjanumero = '{$sarjanumero}'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 0) {
      $errors[] = t("Sarjanumerolla ei löytynyt mitään.");
    }
    else{
      $sarjanumerotieto = mysql_fetch_assoc($result);
      $vanha_paino = (int) $sarjanumerotieto['massa'];
      $tilaus_id = $sarjanumerotieto['asiakkaan_tilausnumero'];
    }
  }

  if (isset($uusi_paino)) {
    if ($uusi_paino > $vanha_paino) {
      $errors[] = t("Uusi paino ei voi olla suurempi kuin alkuperäinen.");
    }
    else {
      require 'generoi_edifact.inc';
      $parametrit = hylky_lusaus_parametrit($sarjanumero);

      if ($parametrit) {
        $parametrit['poistettu_paino'] = $vanha_paino - $uusi_paino;
        $parametrit['paino'] = $uusi_paino;
        $parametrit['laji'] = 'lusaus';
        $sanoma = laadi_edifact_sanoma($parametrit);
      }
      else{
        $errors[] = t("Sarjanumerolla ei löytynyt mitään.");
      }

      if ($sanoma) {
        if (laheta_sanoma($sanoma)) {

          $lahetys = 'OK';
          $viesti = "Sarjanumeron $sarjanumero uudeksi painoksi on päivitetty $uusi_paino kg.";

          $query = "UPDATE sarjanumeroseuranta
                    SET massa = '{$uusi_paino}'
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND sarjanumero = '{$sarjanumero}'";
          pupe_query($query);

        }
        else{
          $errors[] = t("Lähetys ei onnistunut");
        }
      }
      else{
        $errors[] = t("Ei sanomaa");
      }
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
echo t("RULLAN LUSAUS");
echo "</h1>";
echo "</div>";

echo "<div class='header_right'>";
echo "<a href='{$palvelin2}logout.php?location={$palvelin2}sarjanumero' class='button header_button'>";
echo t("Kirjaudu ulos");
echo "</a>";
echo "</div>";

echo "</div>";

echo "<div class='error' style='text-align:center'>";
foreach ($errors as $error) {
  echo $error."<br>";
}
echo "</div>";

if (isset($sarjanumero) and $lahetys != 'OK') {

  echo "
  <form method='post' action='lusaus.php'>

    <div style='text-align:center;padding:10px;'>",
    t("Syötä uusi paino sarjanumerolle:"), "<br>
      {$sarjanumero}
    </div>

    <div style='text-align:center;padding:10px;'>",
    t("Vanha paino:"), "<br>
      {$vanha_paino} kg
    </div>

    <div style='text-align:center;padding:10px;'>
    <label for='uusi_paino'>", t("Uusi paino (kg)"), "</label><br>
    <input type='text' id='uusi_paino' name='uusi_paino' />
    </div>

    <div style='text-align:center'>
      <button name='submit' id='haku_nappi' value='ok' onclick='submit();' class='button'>", t("OK"), "</button>
    </div>
    <input type='hidden' name='sarjanumero' value='{$sarjanumero}' />
    <input type='hidden' name='vanha_paino' value='{$vanha_paino}' />
  </form>";
  $input = "uusi_paino";

}
else {

  if ($lahetys == 'OK') {
    echo "<div style='text-align:center;'>";
    echo $viesti;
    echo "</div>";
  }


  echo "
  <form method='post' action='lusaus.php'>
    <div style='text-align:center;padding:10px;'>
      <label for='sarjanumero'>", t("Sarjanumero"), "</label><br>
      <input type='text' id='sarjanumero' name='sarjanumero' style='margin:10px;' />
      <br>
      <button name='submit' value='sarjanumero' onclick='submit();' class='button'>", t("OK"), "</button>
    </div>
  </form>";
  $input = "sarjanumero";
}

echo "
<script type='text/javascript'>
  $(document).on('touchstart', function(){
    $('#sarjanumero').focus();
  });
</script>";

require 'inc/footer.inc';
