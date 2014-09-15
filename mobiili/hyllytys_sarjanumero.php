<?php
$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;
$valinta = "Etsi";

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");

if (!isset($errors)) $errors = array();

$query =   "SELECT tilausrivi.tunnus, tilausrivi.otunnus
            FROM sarjanumeroseuranta
            JOIN tilausrivi ON tilausrivi.yhtio = sarjanumeroseuranta.yhtio
            AND tilausrivi.tunnus = sarjanumeroseuranta.ostorivitunnus
            AND tilausrivi.tyyppi='O'
            AND tilausrivi.varattu != 0
            AND (tilausrivi.uusiotunnus = 0 OR tilausrivi.suuntalava = 0)
            WHERE sarjanumeroseuranta.yhtio = '{$kukarow['yhtio']}'
            AND sarjanumeroseuranta.sarjanumero = '{$sarjanumero}'";
$result = pupe_query($query);
$row = mysql_fetch_assoc($result);

$tilausrivi = $row['tunnus'];
$ostotilaus = $row['otunnus'];

if (empty($ostotilaus) or empty($tilausrivi)) {
  echo "<META HTTP-EQUIV='Refresh'CONTENT='2;URL=ostotilaus_sarjanumero.php?virhe'>"; exit();
}

// Haetaan tilausrivin ja laskun tiedot
/* Ostotilausten_kohdistus rivi 847 - 895 */
$query = "SELECT
          tilausrivi.varattu+tilausrivi.kpl AS siskpl,
          tilausrivi.tuoteno,
          round((tilausrivi.varattu+tilausrivi.kpl) *
            if (tuotteen_toimittajat.tuotekerroin <= 0
              OR tuotteen_toimittajat.tuotekerroin is null, 1, tuotteen_toimittajat.tuotekerroin),
            2) AS ulkkpl,
          tuotteen_toimittajat.toim_tuoteno,
          tuotteen_toimittajat.tuotekerroin,
          concat_ws(' ',
            tuotepaikat.hyllyalue,
            tuotepaikat.hyllynro,
            tuotepaikat.hyllyvali,
            tuotepaikat.hyllytaso) AS kerayspaikka,
          tilausrivi.varattu,
          tilausrivi.yksikko,
          tilausrivi.suuntalava,
          tilausrivi.uusiotunnus,
          lasku.liitostunnus,
          toimi.selaus,
          IFNULL(tilausrivin_lisatiedot.suoraan_laskutukseen, 'NORM') AS tilausrivi_tyyppi,
          IFNULL(tilausrivin_lisatiedot.tilausrivitunnus, 0) AS tilausrivitunnus
          FROM lasku
          JOIN tilausrivi
            ON (tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus                      = lasku.tunnus
              AND tilausrivi.tyyppi='O')
          JOIN tuotteen_toimittajat
            ON (tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
              AND tuotteen_toimittajat.yhtio              = tilausrivi.yhtio)
          JOIN toimi
            ON (toimi.yhtio = tuotteen_toimittajat.yhtio
              AND toimi.tunnus                            = tuotteen_toimittajat.liitostunnus)
          LEFT JOIN tilausrivin_lisatiedot
            ON (tilausrivin_lisatiedot.yhtio = lasku.yhtio
              AND tilausrivin_lisatiedot.tilausrivilinkki = tilausrivi.tunnus)
          JOIN tuotepaikat
            ON (tuotepaikat.yhtio = lasku.yhtio
              AND tuotepaikat.tuoteno                     = tilausrivi.tuoteno
              AND tuotepaikat.oletus                      = 'X')
          WHERE tilausrivi.tunnus                         = '{$tilausrivi}'
          AND tilausrivi.yhtio                            = '{$kukarow['yhtio']}'
          AND lasku.tunnus                                = '{$ostotilaus}'
          AND lasku.vanhatunnus                           = '{$kukarow['toimipaikka']}'";
$result = pupe_query($query);
$row = mysql_fetch_assoc($result);

if (!$row) {
  exit("Virhe, riviä ei löydy");
}

// Haetaan toimittajan tiedot
$toimittaja_query = "SELECT * FROM toimi WHERE tunnus='{$row['liitostunnus']}'";
$toimittaja = mysql_fetch_assoc(pupe_query($toimittaja_query));

// Jos saapumista ei ole setattu, tehdään uusi saapuminen haetulle toimittajalle
if (empty($saapuminen)) {
  $saapuminen = uusi_saapuminen($toimittaja, $kukarow['toimipaikka']);
  $update_kuka = "UPDATE kuka SET kesken={$saapuminen} WHERE yhtio='{$kukarow['yhtio']}' AND kuka='{$kukarow['kuka']}'";
  $updated = pupe_query($update_kuka);
}
// Jos saapuminen on niin tarkistetaan että se on samalle toimittajalle
else {
  // Haetaan saapumisen toimittaja tunnus
  $saapuminen_query = "SELECT liitostunnus
                       FROM lasku
                       WHERE tunnus='{$saapuminen}'";
  $saapumisen_toimittaja = mysql_fetch_assoc(pupe_query($saapuminen_query));

  // jos toimittaja ei ole sama kuin tilausrivin niin tehdään uusi saapuminen
  if ($saapumisen_toimittaja['liitostunnus'] != $row['liitostunnus']) {

    // Haetaan toimittajan tiedot uudestaan ja tehdään uudelle toimittajalle saapuminen
    $toimittaja_query = "SELECT * FROM toimi WHERE tunnus='{$row['liitostunnus']}'";
    $toimittaja = mysql_fetch_assoc(pupe_query($toimittaja_query));
    $saapuminen = uusi_saapuminen($toimittaja, $kukarow['toimipaikka']);
  }
}

// Kontrolleri
if (isset($submit)) {

  $tuotepaikka = urlencode($tuotepaikka);

  echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=kasittely_sarjanumero.php?ostotilaus={$ostotilaus}&saapuminen={$saapuminen}&tilausrivi={$tilausrivi}&tuotepaikka={$tuotepaikka}&tuotenumero={$tuotenumero}'>"; exit();
}

//####### UI ##########
// Otsikko
echo "<div class='header'>";
echo "<button onclick='window.location.href=\"index.php\"' class='button left'><img src='back2.png'></button>";
echo "<h1>", t("HYLLYTYS")."</h1>";
echo "</div>";

// Main
echo "<div class='main'>
<form name='f1' method='post' action=''>
<input type='hidden' name='tilausten_lukumaara' value='{$tilausten_lukumaara}' />
<input type='hidden' name='manuaalisesti_syotetty_ostotilausnro' value='{$manuaalisesti_syotetty_ostotilausnro}' />
<input type='hidden' name='tuotenumero' value='{$row['tuoteno']}' />
<div class='main' style='text-align:center;padding:10px;'>
<label for='tuotepaikka'>", t("Tuotepaikka"), "</label><br>
<input type='text' id='tuotepaikka' name='tuotepaikka' />
</div>
<div class='controls' style='text-align:center'>
<button name='submit' id='haku_nappi' value='ok' onclick='submit();' class='button'>", t("OK"), "</button>
</div>
</form>";

echo "<div class='error'>";
foreach ($errors as $virhe) {
  echo $virhe."<br>";
}
echo "</div>";

echo "<script type='text/javascript'>

    $(document).ready(function() {
      var focusElementId = 'tuotepaikka';
      var textBox = document.getElementById(focusElementId);
      textBox.focus();
    });

</script>";
