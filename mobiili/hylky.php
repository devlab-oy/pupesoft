<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");

if (!isset($errors)) $errors = array();

// Jos haulla ei lˆytyny mit‰‰n, ollaan palattu t‰lle sivulle virheparametrilla.
if (isset($virhe)) {
  $errors[] = t("Ei lˆytynyt. Hae uudestaan.");
}

if (isset($submit)) {
  switch ($submit) {
  case 'ok':
    if (empty($sarjanumero)) {
      $errors[] = t("Syˆt‰ sarjanumero");
      break;
    }
    $query_string = "?sarjanumero={$sarjanumero}&saapuminen={$saapuminen}";

    echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=hyllytys_sarjanumero.php{$query_string}'>"; exit();
    break;
  case 'takaisin':
    echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=index.php'>"; exit();
    break;
  default:
    $errors[] = t("Yll‰tt‰v‰ virhe");
    break;
  }
}

$ostotilaus = (!empty($ostotilaus)) ? $ostotilaus : '';

//## UI ###
echo "
<div class='header'>
  <button onclick='window.location.href=\"index.php\"' class='button left'><img src='back2.png'></button>
  <h1>", t("Rullan hylk‰‰minen"), "</h1>
</div>";


echo "<div class='error' style='text-align:center'>";
foreach ($errors as $error) {
  echo $error."<br>";
}
echo "</div>";

echo "
  <div style='text-align:center;padding:10px;'>
    <form method='post' action=''>
    <label for='sarjanumero'>", t("Sarjanumero"), "</label><br>
    <input type='text' id='sarjanumero' name='sarjanumero' />
  </div>
  <div style='text-align:center'>
    <button name='submit' id='haku_nappi' value='ok' onclick='submit();' class='button'>", t("OK"), "</button>
    </form>
    <br><br>
    <a href='lusaus.php' class='button'>", t("Suorita lusaus", $browkieli), "</a>
    <br><br>
    <a href='hylky.php' class='button'>", t("Hylk‰‰ rulla", $browkieli), "</a>
  </div>";

if (isset($r) and isset($t)) {
  echo "<div class='main' style='text-align:center;padding:5px;'>
    koko tilauksesta tulouttamatta {$t} pakkausta</div>";

  echo "<div class='main' style='text-align:center;padding:5px;'>
    koko rahdista tulouttamatta {$r} pakkausta</div>";
}

echo "<script type='text/javascript'>
  $(document).ready(function() {
    var focusElementId = 'sarjanumero';
    var textBox = document.getElementById(focusElementId);
    textBox.focus();
  });
</script>
";
require 'inc/footer.inc';
