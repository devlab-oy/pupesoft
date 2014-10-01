<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");

$errors = array();

if (isset($submit)) {

  switch ($submit) {
  case 'sarjanumero':
    if (empty($sarjanumero)) {
      $errors[] = t("Syötä sarjanumero");
    }
    else {
      // Katsotaan löytyykö sarjanumero
      $query = "SELECT *
                FROM sarjanumeroseuranta
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND sarjanumero = '{$sarjanumero}'";
      $result = pupe_query($query);

      if (mysql_num_rows($result) == 0) {
        $errors[] = t("Syötettyä sarjanumeroa ei löydy");
      }
      else{
        $pakkaus = mysql_fetch_assoc($result);
        if ($pakkaus['varasto'] != null) {
          $errors[] = t("Pakkaus on jo varastopaikalla") . " {$pakkaus['hyllyalue']}-{$pakkaus['hyllynro']}-{$pakkaus['hyllyvali']}-{$pakkaus['hyllytaso']}";
        }
      }
    }
    if (count($errors) == 0) {
      $view = 'tuotepaikka';
    }
    else{
      $view = 'sarjanumero';
    }
    break;
  case 'sarjanumero_tuotepaikka':
    // Haetaan tilausrivi
    $query =   "SELECT tr.*
                FROM sarjanumeroseuranta AS ss
                JOIN tilausrivi AS tr ON tr.yhtio = ss.yhtio AND tr.tunnus = ss.ostorivitunnus
                WHERE ss.yhtio = '{$kukarow['yhtio']}'
                AND ss.sarjanumero = '{$sarjanumero}'";
    $result = pupe_query($query);
    $tilausrivi = mysql_fetch_assoc($result);

    sscanf($tilausrivi['kommentti'], 'rahtikirjanumero:%d', $rahtikirjanumero);

    // katsotaan onko saman rahdin paketteja laitettu saapumisella
    $query = "SELECT uusiotunnus
              FROM tilausrivi
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND kommentti LIKE 'rahtikirjanumero:{$rahtikirjanumero}%'
              AND uusiotunnus != 0
              LIMIT 1";
    $result = pupe_query($query);

    if (mysql_num_rows($result) > 0) {
      $saapuminen = mysql_result($result, 0);
    }
    else {
      $query = "SELECT toimi.*
                FROM tilausrivi
                JOIN lasku ON lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus
                JOIN toimi ON toimi.yhtio = lasku.yhtio AND toimi.tunnus = lasku.liitostunnus
                WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                AND tilausrivi.tunnus='{$tilausrivi['tunnus']}'";
      $result = pupe_query($query);
      $toimittaja = mysql_fetch_assoc($result);

      $saapuminen = uusi_saapuminen($toimittaja, $kukarow['toimipaikka']);
      $update_kuka = "UPDATE kuka SET kesken={$saapuminen} WHERE yhtio='{$kukarow['yhtio']}' AND kuka='{$kukarow['kuka']}'";
      $updated = pupe_query($update_kuka);
    }

    // Parsitaan uusi tuotepaikka
    // Jos tuotepaikka on luettu viivakoodina, muotoa (C21 045) tai (21C 03V)
    if (preg_match('/^([a-zåäö#0-9]{2,4} [a-zåäö#0-9]{2,4})/i', $tuotepaikka)) {

      // Pilkotaan viivakoodilla luettu tuotepaikka välilyönnistä
      list($alku, $loppu) = explode(' ', $tuotepaikka);

      // Mätsätään numerot ja kirjaimet erilleen
      preg_match_all('/([0-9]+)|([a-z]+)/i', $alku, $alku);
      preg_match_all('/([0-9]+)|([a-z]+)/i', $loppu, $loppu);

      // Hyllyn tiedot oikeisiin muuttujiin
      $hyllyalue = $alku[0][0];
      $hyllynro  = $alku[0][1];
      $hyllyvali = $loppu[0][0];
      $hyllytaso = $loppu[0][1];

      // Kaikkia tuotepaikkoja ei pystytä parsimaan
      if ($hyllyalue == '' or $hyllynro == '' or $hyllyvali == '' or $hyllytaso == '') {
        $errors[] = t("Tuotepaikan haussa virhe, yritä syöttää tuotepaikka käsin") . " ($tuotepaikka)";
      }
    }
    // Tuotepaikka syötetty manuaalisesti (C-21-04-5) tai (C 21 04 5)
    elseif (strstr($tuotepaikka, '-') or strstr($tuotepaikka, ' ')) {
      // Parsitaan tuotepaikka omiin muuttujiin (eroteltu välilyönnillä)
      if (preg_match('/\w+\s\w+\s\w+\s\w+/i', $tuotepaikka)) {
        list($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso) = explode(' ', $tuotepaikka);
      }
      // (eroteltu väliviivalla)
      elseif (preg_match('/\w+-\w+-\w+-\w+/i', $tuotepaikka)) {
        list($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso) = explode('-', $tuotepaikka);
      }
      // Ei saa olla tyhjiä kenttiä
      if ($hyllyalue == '' or $hyllynro == '' or $hyllyvali == '' or $hyllytaso == '') {
        $errors[] = t("Virheellinen tuotepaikka") . ". ($tuotepaikka)";
      }
    }
    else {
      $errors[] = t("Virheellinen tuotepaikka, yritä syöttää tuotepaikka käsin") . " ($tuotepaikka)";
    }

    if (count($errors) == 0) {

      $hylly = array(
        "hyllyalue" => $hyllyalue,
        "hyllynro"   => $hyllynro,
        "hyllyvali" => $hyllyvali,
        "hyllytaso" => $hyllytaso
      );

      // Tarkistetaan onko syötetty hyllypaikka jo tälle tuotteelle
      $tuotteen_oma_hyllypaikka = "SELECT * FROM tuotepaikat
                                   WHERE tuoteno = '{$tilausrivi['tuoteno']}'
                                   AND yhtio     = '{$kukarow['yhtio']}'
                                   AND hyllyalue = '$hyllyalue'
                                   AND hyllynro  = '$hyllynro'
                                   AND hyllyvali = '$hyllyvali'
                                   AND hyllytaso = '$hyllytaso'";
      $oma_paikka = pupe_query($tuotteen_oma_hyllypaikka);

      // Jos syötettyä paikkaa ei ole tämän tuotteen, lisätään uusi tuotepaikka
      if (mysql_num_rows($oma_paikka) == 0) {

        $_viesti = 'Saapumisessa';

        lisaa_tuotepaikka($tilausrivi['tuoteno'], $hyllyalue, $hyllynro, $hyllyvali, $hyllytaso, $_viesti, '', $halytysraja, $tilausmaara);
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

      paivita_tilausrivit_ja_sarjanumeroseuranta($tilausrivi['tunnus'], $hylly);

      $query = "UPDATE tilausrivi SET
                uusiotunnus = '{$saapuminen}'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$tilausrivi['tunnus']}'";
      pupe_query($query);

      $kukarow['ei_echoa'] = 'joo';
      vie_varastoon($saapuminen, 0, $hylly, $tilausrivi['tunnus']);
      unset($kukarow['ei_echoa']);

      $info = "Sarjanumero {$sarjanumero} vietiin varastopaikalle {$hylly['hyllyalue']}-{$hylly['hyllynro']}-{$hylly['hyllyvali']}-{$hylly['hyllytaso']}";

      // tutkitaan rahdin ja tilauksen tulouttamisen tila
      $query = "SELECT
                sum(CASE WHEN kommentti LIKE 'rahtikirjanumero:{$rahtikirjanumero}%'
                  AND uusiotunnus = 0
                  THEN 1 ELSE 0 END) viemattomia_rahdin_riveja,
                sum(CASE WHEN otunnus = '{$tilausrivi['otunnus']}'
                  AND uusiotunnus = 0
                  THEN 1 ELSE 0 END) viemattomia_tilauksen_riveja
                FROM tilausrivi
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tyyppi = 'O'";
      $result = pupe_query($query);
      $tilanne = mysql_fetch_assoc($result);

      $view = 'sarjanumero';
    }
    break;
  case 'takaisin':
    echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=index.php'>";
    die;
  default:
    $errors[] = 'error';
  }
}
else {
  $view = 'sarjanumero';
}

echo "
<div class='header'>
  <button onclick='window.location.href=\"index.php\"' class='button left'><img src='back2.png'></button>
  <h1>", t("Tuloutus sarjanumerolla"), "</h1>
</div>";

echo "<div class='error' style='text-align:center'>";
foreach ($errors as $error) {
  echo $error."<br>";
}
echo "</div>";

if ($view == 'sarjanumero') {

  echo "
  <form method='post' action=''>
    <div style='text-align:center;padding:10px;'>
      <label for='sarjanumero'>", t("Sarjanumero"), "</label><br>
      <input type='text' id='sarjanumero' name='sarjanumero' style='margin:10px;' />
      <br>
      <button name='submit' value='sarjanumero' onclick='submit();' class='button'>", t("OK"), "</button>
    </div>
  </form>";

  if (isset($info)) {
    echo "
    <div class='main' style='text-align:center;padding:5px;'>
      {$info}
    </div>";
  }

  if (isset($tilanne)) {
    echo "
    <div class='main' style='text-align:center;padding:5px;'>
      koko tilauksesta tulouttamatta {$tilanne['viemattomia_tilauksen_riveja']} pakkausta
      <br>
      koko rahdista tulouttamatta {$tilanne['viemattomia_rahdin_riveja']} pakkausta
    </div>";
  }

echo "
  <div style='text-align:center;padding:10px;'>
    <button onclick='window.location.href=\"lusaus.php\"' class='button'>", t("Suorita lusaus"), "</button>
    <br><br>
    <button onclick='window.location.href=\"hylky.php\"' class='button'>", t("Hylkää rulla"), "</button>
  </div>

  <script type='text/javascript'>
    $(document).ready(function() {
      var focusElementId = 'sarjanumero';
      var textBox = document.getElementById(focusElementId);
      textBox.focus();
    });
  </script>";
}

if ($view == 'tuotepaikka') {

  echo "
  <form method='post' action=''>
    <div style='text-align:center;padding:10px;'>
    <input type='hidden' name='sarjanumero' value='{$sarjanumero}' />
      <label for='sarjanumero'>", t("Tuotepaikka"), "</label><br>
      <input type='text' id='tuotepaikka' name='tuotepaikka' style='margin:10px;' />
      <br>
      <button name='submit' value='sarjanumero_tuotepaikka' onclick='submit();' class='button'>", t("OK"), "</button>
    </div>
  </form>";
}

echo "
<script type='text/javascript'>
  $(document).ready(function() {
    var focusElementId = '{$view}';
    var textBox = document.getElementById(focusElementId);
    textBox.focus();
  });
</script>";

require 'inc/footer.inc';

function paivita_tilausrivit_ja_sarjanumeroseuranta($ostorivitunnus, $hylly) {
  global $kukarow;

  $query = "SELECT myyntirivitunnus
            FROM sarjanumeroseuranta
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND ostorivitunnus = '{$ostorivitunnus}'";
  $result = pupe_query($query);
  $myyntirivitunnus = mysql_result($result,0);

  $query = "SELECT varasto
            FROM tilausrivi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$ostorivitunnus}'";
  $result = pupe_query($query);
  $varasto = mysql_result($result,0);

  $hyllyalue = strtoupper($hylly['hyllyalue']);
  $hyllynro  = strtoupper($hylly['hyllynro']);
  $hyllyvali = strtoupper($hylly['hyllyvali']);
  $hyllytaso = strtoupper($hylly['hyllytaso']);

  $query = "UPDATE tilausrivi SET
            hyllyalue = '{$hyllyalue}',
            hyllynro = '{$hyllynro}',
            hyllyvali = '{$hyllyvali}',
            hyllytaso = '{$hyllytaso}'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$ostorivitunnus}'";
  $result = pupe_query($query);

  $query = "UPDATE tilausrivi SET
            hyllyalue = '{$hyllyalue}',
            hyllynro = '{$hyllynro}',
            hyllyvali = '{$hyllyvali}',
            hyllytaso = '{$hyllytaso}'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$myyntirivitunnus}'";
  $result = pupe_query($query);

  $query = "UPDATE sarjanumeroseuranta SET
            hyllyalue = '{$hyllyalue}',
            hyllynro = '{$hyllynro}',
            hyllyvali = '{$hyllyvali}',
            hyllytaso = '{$hyllytaso}',
            varasto = '{$varasto}'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND ostorivitunnus = '{$ostorivitunnus}'";
  $result = pupe_query($query);

}


