<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");

if (!isset($errors)) $errors = array();

if (isset($submit)) {

  if (empty($sarjanumero)) {
    $errors[] = t("Syˆt‰ sarjanumero");
  }
  else{

    require 'generoi_edifact.inc';
    $parametrit = hylky_lusaus_parametrit($sarjanumero);

    if ($parametrit) {
      $parametrit['laji'] = 'hylky';
      $sanoma = laadi_edifact_sanoma($parametrit);

      echo $sanoma;die;
    }
    else{
      $errors[] = t("Sarjanumerolla ei lˆytynyt mit‰‰n.");
    }

  }
}


echo "
<div class='header'>
  <button onclick='window.location.href=\"index.php\"' class='button left'><img src='back2.png'></button>
  <h1>", t("Rullan hylk‰‰minen"), "</h1>
</div>";

if (count($errors) > 0) {
  echo "<div class='error' style='text-align:center'>";
  foreach ($errors as $error) {
    echo $error."<br>";
  }
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
  $(document).ready(function() {
    $('#sarjanumero').focus();
  });
</script>";

require 'inc/footer.inc';
