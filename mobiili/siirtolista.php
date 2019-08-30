<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;
$valinta = "Etsi";

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");

if (!isset($errors)) $errors = array();

// Jos uusi parametri on setattu nollataan kuka.kesken
if (isset($uusi) and !isset($virhe)) {
  $nollaus_query = "UPDATE kuka SET kesken = 0 WHERE yhtio = '{$kukarow['yhtio']}' AND kuka = '{$kukarow['kuka']}'";
  $result = pupe_query($nollaus_query);
}
// Katsotaan onko käyttäjälle keskeneräistä siirtolistaa
elseif (!isset($virhe) and !isset($movingback)) {
  $query = "SELECT kesken
            FROM kuka
            JOIN lasku ON (kuka.yhtio = lasku.yhtio AND kuka.kesken = lasku.tunnus AND lasku.tila = 'G' AND lasku.alatila IN ('C','B', 'D'))
            WHERE kuka.kuka = '{$kukarow['kuka']}'
              AND kuka.yhtio = '{$kukarow['yhtio']}'";
  $result = pupe_query($query);
  $kesken_row = mysql_fetch_assoc($result);
  $kesken = 0;

  // Jos käyttäjällä ei ole keskeneräistä siirtolistaa, haetaan käyttäjän viimeisimmäksi luotu siirtolista ja jatketaan sitä
  if ($kesken_row['kesken'] == 0) {
    // Haetaan käyttäjän uusin siirtolistan tunnus ja setataan se kesken kolumniin
    $query = "SELECT *
              FROM lasku
              WHERE yhtio = '{$kukarow['yhtio']}'
                AND laatija = '{$kukarow['kuka']}'
                AND tila    = 'G'
                AND alatila IN ('C','B', 'D')
              ORDER BY luontiaika DESC
              LIMIT 1";
    $result = pupe_query($query);
    $saapuminen_row = mysql_fetch_assoc($result);
    $kesken = $saapuminen_row['tunnus'];

    $kesken_query = "UPDATE kuka
                     SET kesken = '{$kesken}'
                     WHERE yhtio = '{$kukarow['yhtio']}'
                       AND kuka  = '{$kukarow['kuka']}'";
    pupe_query($kesken_query);
  }
  else {
    $kesken = $kesken_row['kesken'];
  }

  if ($kesken != 0) {
    echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=siirtolistalla_useita_tuotteita.php?siirtolista={$kesken}'>";
    exit();
  }
}

// Jos haulla ei löytyny mitään, ollaan palattu tälle sivulle virheparametrilla.
if (isset($virhe)) {
  $errors[] = t("Ei löytynyt. Hae uudestaan.");
}

if (isset($submit)) {
  switch ($submit) {
    case 'ok':
      $_empty_data = empty($data['viivakoodi']);
      $_empty_data = ($_empty_data and empty($data['siirtolista']));

      // Haettu vähintään yhdellä kentällä
      if ($_empty_data) {
        $errors[] = t("Vähintään yksi kenttä on syötettävä");
        break;
      }

      // Rakennetaan parametrit kentistä
      $url = http_build_query($data);

      echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=siirtolistalla_useita_tuotteita.php?{$url}'>"; exit();
      break;

    case 'takaisin':
      echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=tulouta.php'>"; exit();
      break;

    default:
      $errors[] = t("Yllättävä virhe");
      break;
  }
}

$siirtolista = (!empty($siirtolista)) ? $siirtolista : '';

//## UI ###
echo "
<div class='header'>
  <button onclick='window.location.href=\"tulouta.php\"' class='button left'><img src='back2.png'></button>
  <h1>", t("SIIRTOLISTA"), "</h1>
</div>";

echo "<div class='main'>
<form method='post' action=''>
<table>
  <!--
  <tr>
    <th><label for='viivakoodi'>", t("Viivakoodi"), "</label></th>
    <td><input type='text' id='viivakoodi' name='data[viivakoodi]' value='{$viivakoodi}'/><td>
  </tr>
  -->
  <tr>
    <th><label for='siirtolista'>", t("Siirtolista"), "</label></th>
    <td><input type='text' id='siirtolista' name='data[siirtolista]' value='{$siirtolista}'/><td>
  </tr>
</table>
</div>";

echo "<div class='controls'>
  <button name='submit' id='haku_nappi' value='ok' onclick='submit();' class='button'>", t("OK"), "</button>
</form>
</div>";

echo "<div class='error'>";
foreach ($errors as $error) {
  echo $error."<br>";
}
echo "</div>";

echo "<input type='button' id='myHiddenButton' visible='false' onclick='javascript:doFocus();' width='1px' style='display:none'>";
echo "<script type='text/javascript'>

  // katotaan onko mobile
  var is_mobile = navigator.userAgent.match(/Opera Mob/i) != null;

  $(document).ready(function() {
    $('#viivakoodi').on('keyup', function() {
      // Autosubmit vain jos on syötetty tarpeeksi pitkä viivakoodi
      if (is_mobile && $('#viivakoodi').val().length > 8) {
        document.getElementById('haku_nappi').click();
      }
    });
  });

  function doFocus() {
      var focusElementId = 'viivakoodi';
      var textBox = document.getElementById(focusElementId);
      textBox.focus();
    }

  function clickButton() {
     document.getElementById('myHiddenButton').click();
  }

  setTimeout('clickButton()', 1000);

</script>
";
require 'inc/footer.inc';
