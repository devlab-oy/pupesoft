<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");

if (!isset($errors)) $errors = array();

if (isset($submit)) {

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
      $vanhapaino = (int) $sarjanumerotieto['massa'];
      $tilaus_id = $sarjanumerotieto['asiakkaan_tilausnumero'];
    }
  }

  if (isset($uusipaino)) {

    require 'generoi_edifact.inc';
    $parametrit = hylky_lusaus_parametrit($sarjanumero);

    if ($parametrit) {
      $parametrit['poistettu_paino'] = $vanhapaino - $uusipaino;
      $parametrit['paino'] = $uusipaino;
      $parametrit['laji'] = 'lusaus';
      $sanoma = laadi_edifact_sanoma($parametrit);
    }
    else{
      $errors[] = t("Sarjanumerolla ei löytynyt mitään.");
    }

    $query = "UPDATE sarjanumeroseuranta
              SET massa = '{$uusipaino}'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND sarjanumero = '{$sarjanumero}'";
    pupe_query($query);

    $query_string = "?sarjanumero={$sarjanumero}&submit=sarjanumero";
    echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=hyllytys_sarjanumero.php{$query_string}'>";
    die;
  }
}

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

    <div style='text-align:center;padding:10px;'>",
    t("Vanha paino:"), "<br>
      {$vanha_paino} kg
    </div>

    <div style='text-align:center;padding:10px;'>
    <label for='uusipaino'>", t("Uusi paino (kg)"), "</label><br>
    <input type='text' id='uusipaino' name='uusipaino' />
    </div>

    <div style='text-align:center'>
      <button name='submit' id='haku_nappi' value='ok' onclick='submit();' class='button'>", t("OK"), "</button>
    </div>
    <input type='hidden' name='sarjanumero' value='{$sarjanumero}' />
    <input type='hidden' name='vanha_paino' value='{$vanha_paino}' />
  </form>";

  $input = "uusipaino";

}
else {

    echo "
    <form method='post' action=''>
      <div style='text-align:center;padding:10px;'>
        <label for='sarjanumero'>", t("Sarjanumero"), "</label><br>
        <input type='text' id='sarjanumero' name='sarjanumero' style='margin:10px;' />
        <br>
        <button name='submit' value='sarjanumero' onclick='submit();' class='button'>", t("OK"), "</button>
      </div>
    </form>";

    $input = "sarjanumero";
}

if (isset($r) and isset($t)) {

  echo "
  <div class='main' style='text-align:center;padding:5px;'>
    koko tilauksesta tulouttamatta {$t} pakkausta
  </div>
  <div class='main' style='text-align:center;padding:5px;'>
    koko rahdista tulouttamatta {$r} pakkausta
  </div>";
}

echo "
<script type='text/javascript'>
  $(document).ready(function() {
    var focusElementId = '{$input}';
    var textBox = document.getElementById(focusElementId);
    textBox.focus();
  });
</script>";

require 'inc/footer.inc';
