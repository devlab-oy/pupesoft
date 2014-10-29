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

  if (empty($sarjanumero)) {
    $errors[] = t("Syötä sarjanumero");
  }
  else{

    require 'generoi_edifact.inc';
    $parametrit = hylky_lusaus_parametrit($sarjanumero);

    if ($parametrit) {
      $parametrit['laji'] = 'hylky';
      $sanoma = laadi_edifact_sanoma($parametrit);
    }
    else{
      $errors[] = t("Sarjanumerolla ei löytynyt mitään.");
    }

    if ($sanoma) {
      if (laheta_sanoma($sanoma)) {

        $lahetys = 'OK';
        $viesti = "Sarjanumero $sarjanumero on hylätty.";

        $query = "UPDATE sarjanumeroseuranta
                  SET lisatieto = 'Hylätty'
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

echo "<div class='header'>";

echo "<div class='header_left'>";
echo "<a href='index.php' class='button header_button'>";
echo "<span>";
echo t("Päävalikko");
echo "</span>";
echo "</a>";
echo "</div>";

echo "<div class='header_center'>";
echo "<h1>";
echo t("RULLAN HYLKÄÄMINEN");
echo "</h1>";
echo "</div>";

echo "<div class='header_right'>";
echo "<a href='{$palvelin2}logout.php?location={$palvelin2}sarjanumero' class='button header_button'>";
echo "<span>";
echo t("Kirjaudu ulos");
echo "</span>";
echo "</a>";
echo "</div>";

echo "</div>";

if (count($errors) > 0) {
  echo "<div class='error' style='text-align:center'>";
  foreach ($errors as $error) {
    echo $error."<br>";
  }
  echo "</div>";
}


if ($lahetys == 'OK') {
  echo "<div style='text-align:center;'>";
  echo $viesti;
  echo "</div>";
}


echo "
<form method='post' action='hylky.php'>
  <div style='text-align:center;padding:10px;'>
    <label for='sarjanumero'>", t("Sarjanumero"), "</label><br>
    <input type='text' id='sarjanumero' name='sarjanumero' style='margin:10px;' />
    <br>
    <button name='submit' value='sarjanumero' onclick='submit();' class='button'>", t("OK"), "</button>
  </div>
</form>

<script type='text/javascript'>
  $(document).on('touchstart', function(){
    $('#sarjanumero').focus();
  });

</script>";

require 'inc/footer.inc';
