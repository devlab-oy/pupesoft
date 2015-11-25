<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");

require '../inc/edifact_functions.inc';

$viestit = array();

if (!isset($task)) {
  $view = 'aloitus';
  $otsikko = t("Toimitettavien tuotteiden ker‰‰minen");
}

if (isset($submit) and $submit == 'haku') {

  $hakutieto = mysql_real_escape_string($hakutieto);

  $hakutieto = str_replace("+","-", $hakutieto);

  if (strpos($hakutieto, '-') === false) {

    $toimitustunnus = $hakutieto;
    $task = 'tietojen_haku';
  }
  else {

    $query = "SELECT otunnus
              FROM tilausrivi
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tyyppi = 'L'
              AND (tuoteno = '{$hakutieto}' OR kommentti = '{$hakutieto}')";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 0) {
      $viestit[] = t("Ei osumia");

    }
    elseif (mysql_num_rows($result) > 1) {

    }
    else {

      $toimitustunnus = mysql_result($result, 0);
      $luettu_tuotenumero = $hakutieto;
      $task = 'tietojen_haku';
    }
  }
}

if (isset($task) and $task == 'kontitus') {

  $query = "UPDATE tilausrivi SET
            kerattyaika = NOW(),
            keratty = '{$kukarow['kuka']}',
            varattu = varattu - '{$kpl[$rivitunnus]}'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$rivitunnus}'";
  pupe_query($query);

  $query = "SELECT tuoteno, hyllyalue, hyllynro
            FROM tilausrivi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$rivitunnus}'
            AND tyyppi != 'D'";
  $result = pupe_query($query);
  $tuoteinfo = mysql_fetch_assoc($result);

  $query = "UPDATE tuotepaikat SET
            saldo = saldo - '{$kpl[$rivitunnus]}'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tuoteno = '{$tuoteinfo['tuoteno']}'
            AND hyllyalue = '{$tuoteinfo['hyllyalue']}'
            AND hyllynro = '{$tuoteinfo['hyllynro']}'";
  pupe_query($query);

  $query = "SELECT *
            FROM tilausrivi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND otunnus = '{$toimitustunnus}'
            AND keratty = ''
            AND tyyppi != 'D'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {

    $query = "UPDATE lasku SET
              kerayspvm = NOW(),
              alatila = 'C'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus = '{$toimitustunnus}'";
    pupe_query($query);

  }

  $task = 'tietojen_haku';
}

if (isset($task) and $task == 'tietojen_haku') {

  $query = "SELECT
            tilausrivi.tuoteno,
            tilausrivi.nimitys,
            tilausrivi.tunnus,
            tilausrivi.hyllyalue,
            tilausrivi.hyllynro,
            tilausrivi.keratty,
            tuote.malli,
            FLOOR(tilausrivi.tilkpl) AS kpl,
            lasku.tunnus AS toimitustunnus,
            lasku.nimi,
            lasku.varasto
            FROM lasku
            JOIN tilausrivi
              ON tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus = lasku.tunnus
            JOIN tuote
              ON tuote.yhtio = lasku.yhtio
              AND tuote.tuoteno = tilausrivi.tuoteno
            JOIN laskun_lisatiedot
              ON laskun_lisatiedot.yhtio = lasku.yhtio
              AND laskun_lisatiedot.otunnus = lasku.tunnus
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND lasku.viesti = 'tullivarastotoimitus'
            AND lasku.tila = 'L'
            AND lasku.alatila = 'A'
            AND tuote.mallitarkenne != 'varastointinimike'
            AND lasku.tunnus = '{$toimitustunnus}'";echo $query;
  $result = pupe_query($query);

  while ($rivi = mysql_fetch_assoc($result)) {

    if (isset($luettu_tuotenumero)) {
      if (empty($rivi['keratty']) and $luettu_tuotenumero == $rivi['tuoteno']) {
        $aktiivinen_tuotenumero = $luettu_tuotenumero;
      }
    }

    if (!isset($aktiivinen_tuotenumero) and empty($rivi['keratty'])) {
      $aktiivinen_tuotenumero = $rivi['tuoteno'];
    }

    $saldot = saldo_myytavissa(
        $rivi['tuoteno'],
        '',
        $rivi['varasto'],
        '',
        $rivi['hyllyalue'],
        $rivi['hyllynro'],
        0,
        0
    );

    $saldo = $saldot[2];

    $rivi['saldo'] = $saldo;

    $toimitukset[$rivi['toimitustunnus']]['asiakas'] = $rivi['nimi'];
    $toimitukset[$rivi['toimitustunnus']]['rivit'][] = $rivi;
  }

  $otsikko = t("Tuotteiden ker‰‰minen");
  $view = 'kerays';

}


echo "<meta name='viewport' content='width=device-width, maximum-scale=1.0' />\n";
echo "<link rel='stylesheet' type='text/css' href='ipad.css' />\n";
echo "<body>";

echo "<div class='header'>";

echo "<div class='header_left'>";
echo "<a href='index.php' class='button header_button'>";
echo t("P‰‰valikko");
echo "</a>";
echo "</div>";

echo "<div class='header_center'>";
echo "<h1>";
echo $otsikko;
echo "</h1>";
echo "</div>";

echo "<div class='header_right'>";
echo "<a href='{$palvelin2}logout.php?location={$palvelin2}sarjanumero' class='button header_button'>";
echo t("Kirjaudu ulos");
echo "</a>";
echo "</div>";

echo "</div>";
echo "<div style='text-align:center;padding:10px 0 0 0; margin:0 auto;'>";
echo "<div style='text-align:center;width: 700px; margin:20px auto;'>";


if (count($viestit) > 0) {
  echo "<div class='viesti' style='text-align:center'>";
  foreach ($viestit as $viesti) {
    echo $viesti."<br>";
  }
  echo "<br></div>";
}



if ($view == 'aloitus') {

  echo "
  <form method='post' action=''>
    <div style='text-align:center;padding:10px;'>
      <label for='hakutieto'>", t("Syˆt‰ toimitusnumero tai lue viivakoodi"), "</label><br>
      <input type='text' id='hakutieto' name='hakutieto' style='margin:10px;' autofocus/>
      <br>
      <button name='submit' value='haku' onclick='submit();' class='button'>", t("OK"), "</button>
    </div>
  </form>

  <script type='text/javascript'>
    $(document).on('touchstart', function(){
      $('#hakutieto').focus();
    });
  </script>";

}


if ($view == 'kerays') {

  if (count($toimitukset) > 0) {

    echo "
    <form method='post' action=''>
      <div style='text-align:center;padding:10px;'>
        <label for='hakutieto'>", t("Lue viivakoodi"), "</label><br>
        <input type='text' id='hakutieto' name='hakutieto' style='margin:10px;' autofocus/>
        <br>
        <button name='submit' value='haku' onclick='submit();' class='button'>", t("OK"), "</button>
      </div>
    </form>

    <script type='text/javascript'>
      $(document).on('touchstart', function(){
        $('#hakutieto').focus();
      });
    </script>";


    foreach ($toimitukset as $toimitustunnus => $toimitus) {

      echo "<form method='post' action=''><div style='margin-bottom:10px; background:silver;   border-radius: 5px;'>";
      echo "<input type='hidden' name='task' value='kontitus' >";
      echo "<input type='hidden' name='toimitustunnus' value='{$toimitustunnus}' >";
      echo "<table border='0' cellspacing='5' cellpadding='0'>";
      echo "<tr>";
      echo "<td valign='top' style='background:white; padding:5px; margin:5px; width:190px;  border-radius: 3px; text-align:left; line-height:20px;'>";
      echo $toimitus['asiakas'] . "<br>";
      echo t("Numero: ") . $toimitustunnus . "<br>";
      echo "</td>";
      echo "<td valign='top' style=' padding:0px; margin:0px; width:430px; border-radius: 3px;'>";

      $rivitunnukset = '';

      foreach ($toimitus['rivit'] as $toimitusrivi) {

        $paikka = substr($toimitusrivi['hyllyalue'], 1) . $toimitusrivi['hyllynro'];

        echo "<div style='text-align:left; padding:10px; background:#e7e7e7; border-radius: 3px; margin:3px; height:40px; overflow:auto;'>";

        echo "<div style='text-align:left; float:left; width:150px;' class='pystykeski'>";
        echo $toimitusrivi['kpl'];
        echo "&nbsp;" . t("kpl");
        echo "<input type='hidden' name='kpl[".$toimitusrivi['tunnus']."]' value='" . (int) $toimitusrivi['kpl'] . "' />";
        echo "</div>";

        echo "<div style='text-align:left; float:left; margin-right:20px;' class='pystykeski'>";
        echo $toimitusrivi['nimitys'] . ' - ' . $toimitusrivi['malli'] . ' - ' . $paikka;
        echo "</div>";

        if (!empty($toimitusrivi['keratty'])) {
          echo "<div style='text-align:left; float:right; margin-right:5px;' class='pystykeski'>";
          echo t("Ker‰tty");
          echo "</div>";
        }
        elseif ($toimitusrivi['tuoteno'] == $aktiivinen_tuotenumero) {
          echo "<div style='text-align:left; float:right; margin-right:5px;' class='pystykeski'>";
          echo "<button name='rivitunnus' value='{$toimitusrivi['tunnus']}' type='submit' class='button'>&#10145</button>";
          echo "</div>";
        }

        echo "</div>";

        $rivitunnukset .= $toimitusrivi['tunnus'] . ',';
      }
      echo "</td>";

/*

      echo "<td style='background:silver; padding:5px; margin:5px; border-radius: 3px;'>";

      $rivitunnukset = rtrim($rivitunnukset, ',');

      echo "
          <input type='hidden' name='rivitunnukset' value='{$rivitunnukset}' />
          <input type='hidden' name='toimitustunnus' value='{$toimitustunnus}' />
          <input type='hidden' name='konttimaara' value='{$toimitus['konttimaara']}' />
          <input type ='hidden'name='task' value='kontitus' >
          <input type='submit' class='button' value='&#10145' />
        </form>";
      echo "</td>";

*/

      echo "</tr>";
      echo "</table>";
      echo "</form>";
      echo "</div>";
    }
    echo "</div>";
  }
  else {
    echo "<div class='error' style='text-align:center'>";
    echo t("Ei ker‰tt‰v‰‰");
    echo "<div>";
  }
}

require 'inc/footer.inc';
