<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;
$valinta = "Etsi";

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");

if (!isset($errors)) $errors = array();

// Jos haulla ei löytyny mitään, ollaan palattu tälle sivulle virheparametrilla.
if (isset($virhe)) {
  $errors[] = t("Ei löytynyt. Hae uudestaan.");
}

if (isset($submit)) {
  switch ($submit) {
  case 'ok':
    if (empty($sarjanumero)) {
      $errors[] = t("Syötä sarjanumero");
      break;
    }
    $query_string = "?sarjanumero={$sarjanumero}&saapuminen={$saapuminen}";

    echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=hyllytys_sarjanumero.php{$query_string}'>"; exit();
    break;
  case 'takaisin':
    echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=index.php'>"; exit();
    break;
  default:
    $errors[] = t("Yllättävä virhe");
    break;
  }
}

$ostotilaus = (!empty($ostotilaus)) ? $ostotilaus : '';

//## UI ###
echo "
<div class='header'>
  <button onclick='window.location.href=\"index.php\"' class='button left'><img src='back2.png'></button>
  <h1>", t("RAHDIN KUITTAUS VASTAANOTETUKSI"), "</h1>
</div>";


echo "<div class='error' style='text-align:center'>";
foreach ($errors as $error) {
  echo $error."<br>";
}
echo "</div>";


echo "<div class='main' style='text-align:center;padding:10px;'>
  <form method='post' action=''>
  <label for='rahtikirjanumero'>", t("Syötä rahtikirjanumero"), "</label><br>
  <input type='text' id='rahtikirjanumero' name='rahtikirjanumero' />
  </div>
  <div class='controls' style='text-align:center'>
  <button name='submit' id='haku_nappi' value='ok' onclick='submit();' class='button'>", t("OK"), "</button>
</form>
</div>
<script type='text/javascript'>
  $(document).ready(function() {
    var focusElementId = 'sarjanumero';
    var textBox = document.getElementById(focusElementId);
    textBox.focus();
  });
</script>
";
require 'inc/footer.inc';
