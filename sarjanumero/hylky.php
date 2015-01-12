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

    $query = "SELECT trlt.sinettinumero,
              ss.lisatieto
              FROM sarjanumeroseuranta AS ss
              JOIN tilausrivi AS tr
                ON tr.yhtio = ss.yhtio
                AND tr.tunnus = ss.myyntirivitunnus
              JOIN tilausrivin_lisatiedot AS trlt
                ON trlt.yhtio = ss.yhtio
                AND trlt.tilausrivitunnus = tr.tunnus
              WHERE ss.yhtio = '{$kukarow['yhtio']}'
              AND ss.sarjanumero = '{$sarjanumero}'";
    $result = pupe_query($query);

    $tiedot = mysql_fetch_assoc($result);

    if (!$tiedot) {
      $errors[] = t("Sarjanumerolla ei löytynyt mitään.");
    }
    elseif ($tiedot['sinettinumero'] != '') {
      $errors[] = t("Rulla on jo kontitettu ja kontti sinetöity.");
    }
    elseif ($tiedot['lisatieto'] == 'Hylätty') {
      $errors[] = t("Rulla on jo merkitty hylätyksi.");
    }
    else{

      $query = "UPDATE sarjanumeroseuranta
                SET lisatieto = 'Hylättävä'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND sarjanumero = '{$sarjanumero}'";
      $result = pupe_query($query);

      $viestit[] = t("Sarjanumero merkitty hylättäväksi.");

    }
  }
}

echo "<div class='header'>";

echo "<div class='header_left'>";
echo "<a href='index.php' class='button header_button'>";
echo t("Päävalikko");
echo "</a>";
echo "</div>";

echo "<div class='header_center'>";
echo "<h1>";
echo t("RULLAN HYLKÄYS");
echo "</h1>";
echo "</div>";

echo "<div class='header_right'>";
echo "<a href='{$palvelin2}logout.php?location={$palvelin2}sarjanumero' class='button header_button'>";
echo t("Kirjaudu ulos");
echo "</a>";
echo "</div>";

echo "</div>";

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

if (count($viestit) > 0) {
  echo "<div class='viesti' style='text-align:center'>";
  foreach ($viestit as $viesti) {
    echo $viesti."<br>";
  }
  echo "</div>";
}

if (count($errors) > 0) {
  echo "<div class='error' style='text-align:center'>";
  foreach ($errors as $error) {
    echo $error."<br>";
  }
  echo "</div>";
}

require 'inc/footer.inc';
