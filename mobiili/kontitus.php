<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");

$errors = array();

if (isset($submit)) {

  switch ($submit) {
  case 'tilausnumero':
    if (empty($tilausnumero)) {
      $errors[] = t("Syötä tilausnumero");
      $view = 'tilausnumero';
    }
    else {

      $tilauksen_rullat = tilauksen_rullat($tilausnumero);

      if ($tilauksen_rullat === false) {
        $errors[] = t("Tilausnumerolla ei löydy tilausta.");
        $view = 'tilausnumero';
      }
      elseif(count($tilauksen_rullat) == 0) {
        $errors[] = t("Tilausksella ei ole kontitettavia rullia.");
        $view = 'tilausnumero';
      }
      else{
        $view = 'kontituslista';
      }
    }
    break;
  case 'kontitus':
    $query = "UPDATE tilausrivi SET
              keratty = '{$kukarow['kuka']}',
              kerattyaika = NOW()
              WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$rivitunnus}'";
    pupe_query($query);

    $tilauksen_rullat = tilauksen_rullat($tilausnumero);

    if(count($tilauksen_rullat) == 0) {
      $view = 'vahvistus';
    }
    else{
      $view = 'kontituslista';
    }
    break;
  case 'sarjanumero':
    $query = "SELECT myyntirivitunnus
              FROM sarjanumeroseuranta
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND sarjanumero = '{$sarjanumero}'";
    $result = pupe_query($query);
    $rivitunnus = mysql_result($result, 0);

    $query = "UPDATE tilausrivi SET
              keratty = '{$kukarow['kuka']}',
              kerattyaika = NOW()
              WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$rivitunnus}'";
    pupe_query($query);

    $tilauksen_rullat = tilauksen_rullat($tilausnumero);

    if(count($tilauksen_rullat) == 0) {
      $view = 'vahvistus';
    }
    else{
      $view = 'kontituslista';
    }
    break;
  case 'vahvista':
    $view = 'vahvistus';
    break;
  case 'takaisin':
    echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=index.php'>";
    die;
  default:
    $errors[] = 'error';
  }
}
else {
  $view = 'tilausnumero';
}

echo "
<div class='header'>
  <button onclick='window.location.href=\"index.php\"' class='button left'><img src='back2.png'></button>
  <h1>", t("Kontitus"), "</h1>
</div>";

echo "
<div class='error' style='text-align:center'>";
foreach ($errors as $error) {
  echo $error."<br>";
}
echo "</div>";

if ($view == 'tilausnumero') {
  echo "
  <form method='post' action=''>
    <div style='text-align:center;padding:10px;'>
      <label for='tilausnumero'>", t("Tilausnumero"), "</label><br>
      <input type='text' id='tilausnumero' name='tilausnumero' style='margin:10px;' />
      <br>
      <button name='submit' value='tilausnumero' onclick='submit();' class='button'>", t("OK"), "</button>
    </div>
  </form>

  <script type='text/javascript'>
    $(document).ready(function() {
      $('#tilausnumero').focus();
    });
  </script>";
}

if ($view == 'vahvistus') {
  echo "
  <form method='post' action=''>
    <div style='text-align:center;padding:10px;'>
      <label for='konttinumero'>", t("Konttinumero"), "</label><br>
      <input type='text' id='konttinumero' name='konttinumero' style='margin:10px;' /><br>
      <label for='sinettinumero'>", t("Sinettinumero"), "</label><br>
      <input type='text' id='sinettinumero' name='sinettinumero' style='margin:10px;' /><br>
      <button name='submit' value='vahvista' onclick='submit();' class='button'>", t("Vahvista"), "</button>
    </div>
  </form>";
}

if ($view == 'kontituslista') {


  echo "
  <form method='post' action=''>
    <div style='text-align:center;padding:10px;'>
      <label for='sarjanumero'>", t("Sarjanumero"), "</label><br>
      <input type='text' id='sarjanumero' name='sarjanumero' style='margin:10px;' />
      <input type='hidden' name='tilausnumero' value='{$tilausnumero}' />
      <br>
      <button name='submit' value='sarjanumero' onclick='submit();' class='button'>", t("OK"), "</button>
    </div>
  </form>

  <script type='text/javascript'>
    $(document).ready(function() {
      $('#sarjanumero').focus();
    });
  </script>";



  echo "<div style='text-align:center;padding:10px;'>";

  $keraamattomat = 0;

  foreach ($tilauksen_rullat as $key => $lista) {
    echo 'Tilauksen ' . $key . ' rullat :<br><br>';
    foreach ($lista as $rulla) {

      if ($rulla['keratty'] == '') {
        echo  "Rulla  {$rulla['sarjanumero']}  ({$rulla['tunnus']})
        <form method='post' action='kontitus.php'>
          <input type='hidden' name='rivitunnus' value='{$rulla['tunnus']}' />
          <input type='hidden' name='tilausnumero' value='{$tilausnumero}' />
          <button name='submit' value='kontitus' onclick='submit();' class='button'>", t("Kontita"), "</button>
        </form><br>";

        $keraamattomat++;
      }
      else{
        echo  "Rulla  {$rulla['sarjanumero']}  ({$rulla['tunnus']}) Kerätty!<br>";
      }
    }
  }

  if ($keraamattomat == 0) {
    echo "
    <br>
    Kaikki tilauksen rullat kerätty.<br>
    <form method='post' action='kontitus.php'>
      <input type='hidden' name='tilausnumero' value='{$tilausnumero}' />
      <button name='submit' value='vahvista' onclick='submit();' class='button'>", t("Vahvista"), "</button>
    </form>";
  }

  echo "</div>";
}

require 'inc/footer.inc';


function tilauksen_rullat($tilausnumero) {
  global $kukarow;

  // Katsotaan löytyykö tilaus
  $query = "SELECT *
            FROM lasku
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tila = 'L'
            AND alatila = 'A'
            AND asiakkaan_tilausnumero = '{$tilausnumero}'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    return false;
  }
  else{

    while($tilaus = mysql_fetch_assoc($result)){
      $tilaukset[] = $tilaus;
    }

    foreach ($tilaukset as $tilaus) {

      $query = "SELECT ss.sarjanumero, tr.tunnus, tr.keratty
                FROM lasku AS la
                JOIN tilausrivi AS tr
                  ON tr.yhtio = la.yhtio AND tr.otunnus = la.tunnus
                JOIN sarjanumeroseuranta AS ss
                  ON ss.yhtio = tr.yhtio AND ss.myyntirivitunnus = tr.tunnus
                WHERE la.yhtio = '{$kukarow['yhtio']}'
                AND la.tunnus = '{$tilaus['tunnus']}'";
      $result = pupe_query($query);

      $tilauksen_rullat = array();

      while ($row = mysql_fetch_assoc($result)) {
        $tilauksen_rullat[$tilaus['asiakkaan_tilausnumero']][] = $row;
      }
    }
    return $tilauksen_rullat;
  }
}
