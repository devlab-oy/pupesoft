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
    $errors[] = t("Sy�t� sarjanumero");
  }
  else{

    require 'generoi_edifact.inc';
    $parametrit = hylky_lusaus_parametrit($sarjanumero);

    if ($parametrit) {
      $parametrit['laji'] = 'hylky';
      $sanoma = laadi_edifact_sanoma($parametrit);
    }
    else{
      $errors[] = t("Sarjanumerolla ei l�ytynyt mit��n.");
    }

    if ($sanoma) {
      if (laheta_sanoma($sanoma)) {

        $lahetys = 'OK';
        $viesti = "Sarjanumero $sarjanumero on hyl�tty.";

        $query = "UPDATE sarjanumeroseuranta
                  SET lisatieto = 'Hyl�tty'
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND sarjanumero = '{$sarjanumero}'";
        pupe_query($query);

      }
      else{
        $errors[] = t("L�hetys ei onnistunut");
      }
    }
    else{
      $errors[] = t("Ei sanomaa");
    }



  }
}


echo "
<div class='header'>
  <h1>", t("Rullan hylk��minen"), "</h1>
</div>";

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
