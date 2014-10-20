<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;
$valinta = "Etsi";

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");

require 'generoi_edifact.inc';

if (!isset($errors)) $errors = array();

// Jos haulla ei löytyny mitään, ollaan palattu tälle sivulle virheparametrilla.
if (isset($virhe)) {
  $errors[] = t("Ei löytynyt. Hae uudestaan.");
}

if (isset($submit)) {
  switch ($submit) {
  case 'rahtikirjanumero':
  case 'sarjanumero':

    if (empty($rahtikirjanumero) and empty($sarjanumero)) {
      $errors[] = t("Syötä rahtikirjanumero tai sarjanumero");
      break;
    }
    elseif (empty($rahtikirjanumero)) {
      $parametrit = kuittaus_parametrit($sarjanumero, 'S');
    }
    else {
      $parametrit = kuittaus_parametrit($rahtikirjanumero, 'R');
    }

    if (is_string($parametrit)) {
      $errors[] = t("Rahtikirja: {$parametrit} on jo kuitattu.");
    }
    elseif ($parametrit) {
      $sanoma = laadi_edifact_sanoma($parametrit);
    }
    else{
      $errors[] = t("Rahtikirjaa ei löytynyt!");
    }
    if ($sanoma) {
      $lahetys = 'X';
      if (laheta_sanoma($sanoma)) {
        $lahetys = 'OK';
      }
      else{
        $errors[] = t("Lähetys ei onnistunut");
      }
    }
    else{
      $errors[] = t("Ei sanomaa");
    }
    break;

  case 'takaisin':
    echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=index.php'>"; exit();
    break;
  default:
    $errors[] = t("Yllättävä virhe");
    break;
  }
}

echo "
<div class='header'>
  <button onclick='window.location.href=\"index.php\"' class='button left'><img src='back2.png'></button>
  <h1>", t("Rahdin kuittaus vastaanotetuksi"), "</h1>
</div>";

echo "<div class='error' style='text-align:center'>";
foreach ($errors as $error) {
  echo $error."<br>";
}
echo "</div>";

if ($lahetys == 'OK') {
  echo "<div style='text-align:center;'>";
  echo "Sanoma lähetetty!";

echo '<hr>', $sanoma, '<hr>';

  echo "</div>";
}

echo "
<form method='post' action=''>
  <div style='text-align:center;padding:10px;'>
    <label for='rahtikirjanumero'>", t("Syötä rahtikirjanumero"), "</label><br>
    <input type='text' id='rahtikirjanumero' name='rahtikirjanumero' style='margin:10px;' />
    <br>
    <button name='submit' value='rahtikirjanumero' onclick='submit();' class='button'>", t("OK"), "</button>
  </div>
</form>

<form method='post' action=''>
  <div style='text-align:center;padding:10px;'>
    <label for='sarjanumero'>", t("Tai lue jokin rahdin sarjanumero"), "</label><br>
    <input type='text' id='sarjanumero' name='sarjanumero' style='margin:10px;' />
    <br>
    <button name='submit' value='sarjanumero' onclick='submit();' class='button'>", t("OK"), "</button>
  </div>
</form>

<script type='text/javascript'>
  $(document).ready(function() {
    $('#sarjanumero').focus();
  });
</script>";

require 'inc/footer.inc';
