<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");
require '../inc/edifact_functions.inc';

if (!isset($view)) {
  $view = 'tulonumero';
}

if (isset($view) and $view == 'splittaus') {
  $submit = '';
}

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

  header("Location: tullivarastointi.php?tulonumero={$tulonumero}&submit=tulonumero");
}

echo "<meta name='viewport' content='width=device-width, maximum-scale=1.0' />\n";
echo "<link rel='stylesheet' type='text/css' href='ipad.css' />\n";
echo "<body>";

if (isset($task) and $task == 'vie_varastoon') {

  $hylly = hae_hylly($valittu_varastopaikka, true);

  $hylly['hyllyalue'] = $varastokirjain.$hylly['hyllyalue'];

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
  $tulonumero = $sk_osat[0] . '-' . $sk_osat[1] . '-' . $sk_osat[2];

  // katsotaan onko saman rahdin paketteja laitettu saapumisella
  $query = "SELECT uusiotunnus
            FROM tilausrivi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tuoteno LIKE '{$tulonumero}-%'
            AND uusiotunnus != ''";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    $tulo = mysql_result($result, 0);
  }
  else {

    $tulo = uusi_saapuminen($toimittaja);
    $update_kuka = "UPDATE kuka SET kesken={$tulo} WHERE yhtio='{$kukarow['yhtio']}' AND kuka='{$kukarow['kuka']}'";
    $updated = pupe_query($update_kuka);
  }

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
              AND hyllyalue = '{$hyllyalue}'
              AND hyllynro  = '{$hyllynro}'
              AND hyllyvali = '{$hyllyvali}'
              AND hyllytaso = '{$hyllytaso}'";
    pupe_query($query);
  }

  // katsotaan onko samaa tuotetta jo samalla paikalla...
  $query = "SELECT tunnus
            FROM tilausrivi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tuoteno = '{$tilausrivi['tuoteno']}'
            AND hyllyalue = '{$hyllyalue}'
            AND hyllynro  = '{$hyllynro}'";
  $result = pupe_query($query);

  // ...jos on niin lisätään kpl olemassa olevaan riviin ja poistetaan toinen
  if (mysql_num_rows($result) > 0) {

    $olemassa_oleva_rivi = mysql_fetch_assoc($result);

    $kpl = $tilausrivi['tilkpl'];

    $query = "UPDATE tilausrivi SET
              kpl = kpl + {$kpl},
              tilkpl = tilkpl + {$kpl}
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$olemassa_oleva_rivi['tunnus']}'";
    pupe_query($query);

    $query = "UPDATE tuotepaikat SET
              saldo = saldo + {$kpl}
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tuoteno = '{$tilausrivi['tuoteno']}'
              AND hyllyalue = '{$hyllyalue}'
              AND hyllynro  = '{$hyllynro}'";
    pupe_query($query);

    $query = "DELETE FROM tilausrivi
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus = '{$tilausrivi['tunnus']}'";
    pupe_query($query);
  }
  else {

    $query = "UPDATE tilausrivi SET
              uusiotunnus = '{$tulo}'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$tilausrivi['tunnus']}'";
    pupe_query($query);

    $kukarow['ei_echoa'] = 'joo';
    vie_varastoon($tulo, 0, $hylly, $tilausrivi['tunnus']);
    unset($kukarow['ei_echoa']);

    $query = "UPDATE tilausrivi SET
              hyllyalue = '{$hyllyalue}',
              hyllynro = '{$hyllynro}',
              hyllyvali = '{$hyllyvali}',
              hyllytaso = '{$hyllytaso}'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus = '{$tilausrivi['tunnus']}'";
    $result = pupe_query($query);
  }

  $submit = 'tulonumero';
}

if (!isset($errors)) $errors = array();
if (!isset($viestit)) $viestit = array();

if (isset($submit) and $submit == 'varastovalinta') {
  $view = 'tiedot';
  $saapumistiedot = hae_saapumistiedot($tulonumero);
}

if (isset($submit) and $submit == 'tulonumero') {

  if (empty($tulonumero)) {
    $errors[] = t("Syötä tulonumero");
  }
  else {
    $saapumistiedot = hae_saapumistiedot($tulonumero);

    if(!$saapumistiedot) {
      $errors[] = t("Koodilla ei löytynyt mitään.");
    }
  }

  if (count($errors) == 0) {
    $view = 'tiedot';
  }
  else {
    $view = 'tulonumero';
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

if ($view == 'tulonumero') {

  echo "
  <form method='post' action=''>
    <div style='text-align:center;padding:10px;'>
      <label for='tulonumero'>", t("Syötä tulonumero"), "</label><br>
      <input type='text' id='tulonumero' name='tulonumero' style='margin:10px;' />
      <br>
      <button name='submit' value='tulonumero' onclick='submit();' class='button'>", t("OK"), "</button>
    </div>
  </form>

  <script type='text/javascript'>
    $(document).on('touchstart', function(){
      $('#tulonumero').focus();
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

  echo "<div style='margin:0 0 15px 0; text-align:left;'>";
  echo t("Saapumiserän jakaminen eri varastopaikoille");
  echo "</div>";

  echo '<hr>';

  echo "<div class='tilaus_alue' style='overflow:auto'>";

  echo "<input type='hidden' id='hidden_vanhakpl' name='vanhakpl' value='{$tilausrivi['tilkpl']}' />";
  echo "<input type='hidden' id='hidden_uusikpl' name='uusikpl' value='0' />";
  echo "<input type='hidden' name='task' value='split' />";
  echo "<input type='hidden' name='rivitunnus' value='{$rivitunnus}' />";
  echo "<input type='hidden' name='tulonumero' value='{$tulonumero}' />";

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

  echo "<div style='overflow:auto; position:relative;'>";
  echo "<h1 style='margin: 10px 0'>" . $tulonumero . "</h1>";

  if (!$saapumistiedot['kaikki_viety']) {

    echo "<div id='piiloon_div' style='position:absolute; right:0px; top:0px;'>";
    echo "<button id='piiloon' type='button' style='padding:10px; color:red; border:1px solid red;' class='button'>";
    echo " &#9650; ";
    echo "</button>";
    echo "</div>";

    echo "<div id='esiin_div' style='position:absolute; right:0px; top:0px; display:none;'>";
    echo "<button id='esiin' type='button' style='padding:10px; color:green; border:1px solid green;' class='button'>";
    echo " &#9660; ";
    echo "</button>";
    echo "</div>";
  }

  echo "</div>";
  echo '<hr>';


  if (!$saapumistiedot['kaikki_viety']) {

    list($koodi, $juoksu, $vuosi) = explode("-", $tulonumero);

    switch ($koodi) {

      case 'EU':
        $nimilisa = ' - EU';
        break;

      case 'ROTV':
      case 'RP':
        $nimilisa = ' - tulli';
        break;

      case 'ROVV':
      case 'VRP':
        $nimilisa = ' - väliaikainen';
        break;

      default:
        # code...
        break;
    }

    $query = "SELECT *
              FROM varastopaikat
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus = '{$saapumistiedot['varasto']}'";
    $result = pupe_query($query);
    $varasto = mysql_fetch_assoc($result);

    $query = "SELECT CONCAT(hyllyalue, hyllynro) AS paikka
              FROM tuotepaikat
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tuoteno LIKE '{$tulonumero}-%'
              GROUP BY paikka";
    $result = pupe_query($query);

    $varastopaikat = array();

    if (mysql_num_rows($result) == 0) {
      $varastopaikat[] = $saapumistiedot['varastokirjain'].'A1';
      if (!isset($valittu_varastopaikka)) {
        $valittu_varastopaikka = 'A1';
      }
    }
    else {
      while ($paikka = mysql_fetch_assoc($result)) {
        $varastopaikat[] = $paikka['paikka'];
      }
    }

    if (isset($uusi_varastopaikka)) {

      $hylly = hae_hylly($uusi_varastopaikka, true);

      if ($hylly) {
        $uusi_varastopaikka = strtoupper($uusi_varastopaikka);
        $varastopaikat[] = $saapumistiedot['varastokirjain'].$uusi_varastopaikka;
        $valittu_varastopaikka = $uusi_varastopaikka;
      }
      else {
        $uusi_varastopaikka_error = t("Epäkelpo varastopaikka");
        unset($uusi_varastopaikka);
      }
      unset($hylly);
    }

    if (!isset($valittu_varastopaikka) and !isset($uusi_varastopaikka)) {
      $valittu_varastopaikka = substr($varastopaikat[0], 2);
    }

    echo "<div>";

    echo t("Varasto: ");
    echo $varasto['nimitys'] . $nimilisa;

    echo "&nbsp&#124;&nbsp;";

    echo t("Viedään paikalle: ");
    echo $valittu_varastopaikka;

    echo "</div>";

    echo "<span id='alue'>";
    echo '<hr>';

    sort($varastopaikat);
    $varastopaikat = array_unique($varastopaikat);

    foreach ($varastopaikat as $vp) {

      $vp = substr($vp, 1);

      if ($vp == $valittu_varastopaikka) {
        $luokka = 'aktiivi';
      }
      else {
       $luokka = '';
      }

      echo "<div style='display:inline-block; margin:5px;'>";
      echo "<form method='post'>";
      echo "<input type='hidden' name='valittu_varastopaikka' value='" . $vp . "' />";
      echo "<input type='hidden' name='tulonumero' value='{$tulonumero}' />";
      echo "<button name='submit' value='varastovalinta' style='padding:10px' class='button {$luokka}'>";
      echo $vp;
      echo "</button>";
      echo "</form>";
      echo "</div>";
    }

    echo '<hr>';

    echo "<div>";
    echo "<div id='lisaysnappi' style='display:inline-block; margin:5px;'>";
    echo "<button name='submit' style='padding:10px' class='button'>";
    echo t("Uusi paikka");
    echo "</button>";
    if (isset($uusi_varastopaikka_error)) {
      echo "<font id='varastopaikka_error' class='error'>{$uusi_varastopaikka_error} {$task}</font>";
    }
    echo "</div>";

    echo "<div id='lisaysformi' style='display:none; margin:5px; position:relative; '>";
    echo "<form method='post'>";
    echo "<input id='lisaysinput' style='position:relative; top:4px; height:23px; margin-right:6px;'  type='text' size='4' name='uusi_varastopaikka' value='' />";
    echo "<input type='hidden' name='tulonumero' value='{$tulonumero}' />";
    echo "<button name='submit' value='varastovalinta' style='padding:10px' class='button aktiivi'>";
    echo t("Ok");
    echo "</button>";
    echo '&nbsp;';
    echo "<button id='perumisnappi' name='submit' type='button' style='padding:10px; color:red; border:1px solid red;' class='button'>";
    echo t("Peru");
    echo "</button>";
    echo "</form>";
    echo "</div>";
    echo "</div>";

    echo "</span>";

    echo "
    <script type='text/javascript'>

      $('#lisaysnappi').click(function() {
        $('#lisaysformi').css('display', 'inline-block');
        $('#lisaysnappi').css('display', 'none');
      });

      $('#perumisnappi').click(function() {
        $('#lisaysinput').val('');
        $('#lisaysformi').css('display', 'none');
        $('#lisaysnappi').css('display', 'inline-block');
        $('#varastopaikka_error').html('');
      });

      $('#piiloon').click(function() {
        $('#alue').css('display', 'none');
        $('#piiloon_div').css('display', 'none');
        $('#esiin_div').css('display', 'block');
      });

    $('#esiin').click(function() {
      $('#alue').css('display', 'block');
      $('#piiloon_div').css('display', 'block');
      $('#esiin_div').css('display', 'none');
    });

    </script>";

    echo '<hr>';

  }

  foreach ($saapumistiedot['rivit'] as $rivi) {

    $kpl = number_format($rivi['tilkpl']);

    echo "<div class='tilaus_alue' style='overflow:auto; height:55px; padding:0 10px;'>";

    echo "<div style='float:left; position: relative; top: 50%; transform: translateY(-50%); max-width:300px; overflow:hidden; margin-right:10px;'>" . $rivi['nimitys'] . "</div>";
    echo "<div style='float:left; position: relative; top: 50%; transform: translateY(-50%);'>" . $kpl . " kpl</div>";

    if ($rivi['varattu'] != 0) {

      if (isset($uusi_varastopaikka)) {
        $valittu_varastopaikka = $uusi_varastopaikka;
      }

      echo "
        <div style='float:right; position: relative; top: 50%; transform: translateY(-50%);'>
          <form method='post'>
          <input type='hidden' name='valittu_varastopaikka' value='{$valittu_varastopaikka}' />
          <input type='hidden' name='varastokirjain' value='{$saapumistiedot['varastokirjain']}' />
          <input type='hidden' name='rivitunnus' value='{$rivi['tunnus']}' />
          <input type='hidden' name='varasto' value='{$rivi['varasto']}' />
          <input type='hidden' name='toimittajatunnus' value='{$rivi['liitostunnus']}' />
          <input type='hidden' name='tulonumero' value='{$tulonumero}' />
          <input type='hidden' name='task' value='vie_varastoon' />
          <input type='submit' value='".t("Varastoon")."'>
          </form>
        </div>";

        if ($kpl > 1) {
          echo "
            <div style='float:right; margin:0 10px 0 0; position: relative; top: 50%; transform: translateY(-50%);'>
              <form method='post'>
              <input type='hidden' name='rivitunnus' value='{$rivi['tunnus']}' />
              <input type='hidden' name='toimittajatunnus' value='{$rivi['liitostunnus']}' />
              <input type='hidden' name='tulonumero' value='{$tulonumero}' />
              <input type='hidden' name='view' value='splittaus' />
              <input type='submit' value='".t("Jako")."'>
              </form>
            </div>";
        }
    }
    else {

      $paikka = substr($rivi['hyllyalue'], 1) . $rivi['hyllynro'];

      echo "<div style='float:right; position: relative; top: 50%; transform: translateY(-50%);'>";
      echo t("Varastopaikka: ");
      echo $paikka;
      echo "</div>";
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
