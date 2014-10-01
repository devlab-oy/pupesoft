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
    if (empty($rahtikirjanumero)) {
      $errors[] = t("Syötä rahtikirjanumero");
      break;
    }

    require 'generoi_edifact.inc';
    $parametrit = kuittaus_parametrit($rahtikirjanumero);

    if ($parametrit) {
      $sanoma = laadi_edifact_sanoma($parametrit);
    }
    else{
      $errors[] = t("Rahtikirjaa ei löytynyt!");
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

//## UI ###
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

if (isset($sanoma)) {
  echo "<div>";
  echo $sanoma;
  echo "</div>";
}

echo "
<form method='post' action=''>
  <div style='text-align:center;padding:10px;'>
    <label for='rahtikirjanumero'>", t("Rahtikirjanumero"), "</label><br>
    <input type='text' id='rahtikirjanumero' name='rahtikirjanumero' style='margin:10px;' />
    <br>
    <button name='submit' value='rahtikirjanumero' onclick='submit();' class='button'>", t("OK"), "</button>
  </div>
</form>

<script type='text/javascript'>
  $(document).ready(function() {
    var focusElementId = 'rahtikirjanumero';
    var textBox = document.getElementById(focusElementId);
    textBox.focus();
  });
</script>
";
require 'inc/footer.inc';
