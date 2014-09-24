<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");

if (!isset($errors)) $errors = array();

// Jos haulla ei löytyny mitään, ollaan palattu tälle sivulle virheparametrilla.
if (isset($virhe)) {
  $errors[] = t("Ei löytynyt. Hae uudestaan.");
}

if (isset($submit)) {

    if (empty($sarjanumero)) {
      $errors[] = t("Syötä sarjanumero");
    }

    if (isset($uusipaino)) {
      $query = "UPDATE sarjanumeroseuranta
                SET masssa = '{$uusipaino}'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND sarjanumero = '{$sarjanumero}'";
      pupe_query($query);

      $query_string = "?sarjanumero={$sarjanumero}&saapuminen={$saapuminen}";
      echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=hyllytys_sarjanumero.php{$query_string}'>"; exit();
    }

}

$ostotilaus = (!empty($ostotilaus)) ? $ostotilaus : '';

//## UI ###
echo "
<div class='header'>
  <button onclick='window.location.href=\"index.php\"' class='button left'><img src='back2.png'></button>
  <h1>", t("Rullan lusaus"), "</h1>
</div>";


echo "<div class='error' style='text-align:center'>";
foreach ($errors as $error) {
  echo $error."<br>";
}
echo "</div>";


if (isset($sarjanumero) and count($errors) < 1) {

  echo "
  <form method='post' action=''>

    <div style='text-align:center;padding:10px;'>",
    t("Syötä uusi paino sarjanumerolle:"), "<br>
      {$sarjanumero}
    </div>

    <div style='text-align:center;padding:10px;'>
    <label for='uusipaino'>", t("Paino (kg)"), "</label><br>
    <input type='text' id='uusipaino' name='uusipaino' />
    </div>

    <div style='text-align:center'>
      <button name='submit' id='haku_nappi' value='ok' onclick='submit();' class='button'>", t("OK"), "</button>
    </div>
    <input type='hidden' name='sarjanumero' value='{$sarjanumero}' />
    <input type='hidden' name='sarjanumero' value='{$saapuminen}' />
  </form>";

}
else {

  echo "
    <div style='text-align:center;padding:10px;'>
      <form method='post' action=''>
      <label for='sarjanumero'>", t("Sarjanumero"), "</label><br>
      <input type='text' id='sarjanumero' name='sarjanumero' />
    </div>
    <div style='text-align:center'>
      <button name='submit' id='haku_nappi' value='ok' onclick='submit();' class='button'>", t("OK"), "</button>
      </form>
    </div>";

}



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
