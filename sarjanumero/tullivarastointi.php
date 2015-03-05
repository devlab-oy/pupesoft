<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");
require '../inc/edifact_functions.inc';

if (!isset($view)) {
  $view = 'saapumiskoodi';
}

echo "<meta name='viewport' content='width=device-width, maximum-scale=1.0' />\n";
echo "<link rel='stylesheet' type='text/css' href='ipad.css' />\n";
echo "<body>";

if (isset($task) and $task == 'split') {

  if ($uusikpl > 0) {

    $uusitunnus = splittaa_tilausrivi($rivitunnus, $uusikpl);

    $query = "UPDATE tilausrivi SET
              tilkpl = {$uusikpl}
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$uusitunnus}'";
    pupe_query($query);

    $query = "UPDATE tilausrivi SET
              tilkpl = tilkpl - {$uusikpl},
              varattu = varattu - {$uusikpl}
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$rivitunnus}'";
    pupe_query($query);
  }


  $submit = 'saapumiskoodi';

}

if (isset($task) and $task == 'vie_varastoon') {

  $query = "SELECT *
            FROM toimi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus='{$toimittajatunnus}'";
  $result = pupe_query($query);
  $toimittaja = mysql_fetch_assoc($result);

  $query = "SELECT *
            FROM tilausrivi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$rivitunnus}'";
  $result = pupe_query($query);
  $tilausrivi = mysql_fetch_assoc($result);

  $tuotenumero = $tilausrivi['tuoteno'];
  $sk_osat = explode("-", $tuotenumero);
  $saapumiskoodi = $sk_osat[0] . '-' . $sk_osat[1] . '-' . $sk_osat[2];

  // katsotaan onko saman rahdin paketteja laitettu saapumisella
  $query = "SELECT uusiotunnus
            FROM tilausrivi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tuoteno LIKE '{$saapumiskoodi}-%'
            AND uusiotunnus != ''";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    $saapuminen = mysql_result($result, 0);
  }
  else {

    $saapuminen = uusi_saapuminen($toimittaja);
    $update_kuka = "UPDATE kuka SET kesken={$saapuminen} WHERE yhtio='{$kukarow['yhtio']}' AND kuka='{$kukarow['kuka']}'";
    $updated = pupe_query($update_kuka);
  }

  $hylly = hae_hylly($vp);

  $hyllyalue = $hylly['hyllyalue'];
  $hyllynro = $hylly['hyllynro'];
  $hyllyvali = $hylly['hyllyvali'];
  $hyllytaso = $hylly['hyllytaso'];;

  // Tarkistetaan onko syötetty hyllypaikka jo tälle tuotteelle
  $tuotteen_oma_hyllypaikka = "SELECT * FROM tuotepaikat
                               WHERE tuoteno = '{$tilausrivi['tuoteno']}'
                               AND yhtio     = '{$kukarow['yhtio']}'
                               AND hyllyalue = '{$hyllyalue}'
                               AND hyllynro  = '{$hyllynro}'
                               AND hyllyvali = '{$hyllyvali}'
                               AND hyllytaso = '{$hyllytaso}'";
  $oma_paikka = pupe_query($tuotteen_oma_hyllypaikka);

  // Jos syötettyä paikkaa ei ole tämän tuotteen, lisätään uusi tuotepaikka
  if (mysql_num_rows($oma_paikka) == 0) {
    lisaa_tuotepaikka($tilausrivi['tuoteno'], $hyllyalue, $hyllynro, $hyllyvali, $hyllytaso, "Saapumisessa");
  }
  else {
    // Nollataan poistettava kenttä varmuuden vuoksi
    $query = "UPDATE tuotepaikat SET
              poistettava   = ''
              WHERE tuoteno = '{$tilausrivi['tuoteno']}'
              AND yhtio     = '{$kukarow['yhtio']}'
              AND hyllyalue = '$hyllyalue'
              AND hyllynro  = '$hyllynro'
              AND hyllyvali = '$hyllyvali'
              AND hyllytaso = '$hyllytaso'";
    pupe_query($query);
  }

  $query = "UPDATE tilausrivi SET
            uusiotunnus = '{$saapuminen}'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  = '{$tilausrivi['tunnus']}'";
  pupe_query($query);

  $kukarow['ei_echoa'] = 'joo';
  vie_varastoon($saapuminen, 0, $hylly, $tilausrivi['tunnus']);
  unset($kukarow['ei_echoa']);

  $submit = 'saapumiskoodi';
}

if (!isset($errors)) $errors = array();
if (!isset($viestit)) $viestit = array();

if (isset($submit) and $submit == 'saapumiskoodi') {

  if (empty($saapumiskoodi)) {
    $errors[] = t("Syötä saapumiskoodi");
  }
  else {
    $saapumistiedot = hae_saapumistiedot($saapumiskoodi);

    if(!$saapumistiedot) {
      $errors[] = t("Koodilla ei löytynyt mitään.");
    }
  }

  if (count($errors) == 0) {
    $view = 'tiedot';
  }
  else {
    $view = 'saapumiskoodi';
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
echo t("Tullivarastointi");
echo "</h1>";
echo "</div>";

echo "<div class='header_right'>";
echo "<a href='{$palvelin2}logout.php?location={$palvelin2}sarjanumero' class='button header_button'>";
echo t("Kirjaudu&nbsp;ulos");
echo "</a>";
echo "</div>";

echo "</div>";

if ($view == 'saapumiskoodi') {

  echo "
  <form method='post' action=''>
    <div style='text-align:center;padding:10px;'>
      <label for='saapumiskoodi'>", t("Syötä saapumiskoodi"), "</label><br>
      <input type='text' id='saapumiskoodi' name='saapumiskoodi' style='margin:10px;' />
      <br>
      <button name='submit' value='saapumiskoodi' onclick='submit();' class='button'>", t("OK"), "</button>
    </div>
  </form>

  <script type='text/javascript'>
    $(document).on('touchstart', function(){
      $('#saapumiskoodi').focus();
    });
  </script>";

}

if ($view == 'splittaus') {

  $query = "SELECT *
            FROM tilausrivi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$rivitunnus}'";
  $result = pupe_query($query);
  $tilausrivi = mysql_fetch_assoc($result);

  echo "<form method='post'>";
  echo "<div class='alue_0'>";
  echo "<div class='alue_1 alue' style='text-align:center;'>";

  echo "<div style='margin:0 0 15px 0'>";
  echo t("Saapumiserän jakaminen eri varastopaikoille");
  echo "</div>";

  echo "<div class='tilaus_alue' style='overflow:auto'>";

  echo "<input type='hidden' id='hidden_vanhakpl' name='vanhakpl' value='{$tilausrivi['tilkpl']}' />";
  echo "<input type='hidden' id='hidden_uusikpl' name='uusikpl' value='0' />";
  echo "<input type='hidden' name='task' value='split' />";
  echo "<input type='hidden' name='rivitunnus' value='{$rivitunnus}' />";
  echo "<input type='hidden' name='saapumiskoodi' value='{$saapumiskoodi}' />";

  echo "<div style='float:left; width:200px; text-align:left;'>";
  echo t("Alkuperäinen erä");
  echo '<br>';
  echo "<span id='vanhakpl'>" . $tilausrivi['tilkpl'] . "</span>";
  echo "</div>";

  echo "<div style='float:left; width:280px; text-align:center;'>";
  echo "<button id='vanhaan' type='button' class='button'>&larr;</button>";
  echo "&nbsp;";
  echo "<button id='uuteen' type='button'  class='button'>&rarr;</button>";
  echo "</div>";

  echo "<div style='float:right; width:200px;  text-align:right;'>";
  echo t("Uusi erä");
  echo '<br>';
  echo "<span id='uusikpl'>0</span>";
  echo "</div>";

  echo "</div>";
  echo "<input type='submit' class='button' style='margin-top:10px;' value='" . t("Vahvista") . "'>";
  echo "</div>";
  echo "</div>";
  echo "</form>";

  echo "
  <script type='text/javascript'>

    $('#uuteen').click(function() {

      var vkpl = $('#hidden_vanhakpl').val();
      var ukpl = $('#hidden_uusikpl').val();

      var ukpl = Number(ukpl);
      var vkpl = Number(vkpl);

      if (vkpl > 1) {
        $('#vanhakpl').html(vkpl-1);
        $('#hidden_vanhakpl').val(vkpl-1);
        $('#uusikpl').html(ukpl+1);
        $('#hidden_uusikpl').val(ukpl+1);
      }
    });

    $('#vanhaan').click(function() {

      var vkpl = $('#hidden_vanhakpl').val();
      var ukpl = $('#hidden_uusikpl').val();

      var ukpl = Number(ukpl);
      var vkpl = Number(vkpl);

      if (ukpl > 0) {
        $('#vanhakpl').html(vkpl+1);
        $('#hidden_vanhakpl').val(vkpl+1);
        $('#uusikpl').html(ukpl-1);
        $('#hidden_uusikpl').val(ukpl-1);
      }


    });

  </script>";

}





if ($view == 'tiedot') {
  echo "<div class='alue_0'>";
  echo "<div class='alue_1 alue'>";

  $varastotunnus = $saapumistiedot[0]['varasto'];

  $query = "SELECT CONCAT(hyllyalue, hyllynro) AS paikka
            FROM tuotepaikat
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND varasto = '{$varastotunnus}'
            GROUP BY paikka"; echo $query;
  $result = pupe_query($query);

  $varastopaikat = array();

  while ($paikka = mysql_fetch_assoc($result)) {
    $varastopaikat[] = $paikka;
  }

  foreach ($varastopaikat as  $vp) {

    echo "<div style='display:inline-block; margin:6px;'>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='paikka' value='" . $vp['paikka'] . "' />";
    echo "<input type='hidden name='view' value='tiedot' />";
    echo "<button name='submit' value='konttivahvistus' style='padding:10px' class='button aktiivi'>";
    echo $vp['paikka'];
    echo "</button>";
    echo "</form>";
    echo "</div>";

  }




  foreach ($saapumistiedot as $rivi) {

    $kpl = number_format($rivi['tilkpl']);

    echo "<div class='tilaus_alue' style='overflow:auto'>";

    echo "<div style='float:left'>" . $rivi['nimitys'] . " - " . $kpl . " kpl</div>";

    if ($rivi['varattu'] != 0) {

      if (!isset($vvp)) {
        $vvp = 'B1';
      }

      echo "
        <div style='float:right;'>
        <form method='post'>
        <input type='hidden' name='vp' value='{$vvp}' />
        <input type='hidden' name='rivitunnus' value='{$rivi['tunnus']}' />
        <input type='hidden' name='toimittajatunnus' value='{$rivi['liitostunnus']}' />
        <input type='hidden' name='task' value='vie_varastoon' />
        <input type='submit' value='vie varastoon'>
        </form>
        </div>";

        echo "
          <div style='float:right; margin:0 10px 0 0''>
          <form method='post'>
          <input type='hidden' name='rivitunnus' value='{$rivi['tunnus']}' />
          <input type='hidden' name='toimittajatunnus' value='{$rivi['liitostunnus']}' />
          <input type='hidden' name='saapumiskoodi' value='{$saapumiskoodi}' />
          <input type='hidden' name='view' value='splittaus' />
          <input type='submit' value='splittaus'>
          </form>
          </div>";
    }
    else {
      echo "
        <div style='float:right'>
        Viety varastoon
        </div>";
    }
    echo "</div>";
  }

  echo "</div>";
  echo "</div>";
}

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
