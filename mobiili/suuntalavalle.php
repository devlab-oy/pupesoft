<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;
$valinta = "Etsi";

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");

if (empty($tilausrivi) or empty($saapuminen)) {
  exit("Virheelliset parametrit (tilausrivi: $tilausrivi, saapuminen: $saapuminen");
}

if (!isset($saapumisnro_haku)) $saapumisnro_haku = '';

// Virheet
$errors = array();

/* ostotilausten_kohdistus.inc rivit 1541-1567
* Haetaan "sopivat" suuntalavat
*/
$query = "SELECT tuote.keraysvyohyke, tilausrivi.*
          FROM tilausrivi
          JOIN tuote ON tuote.tuoteno=tilausrivi.tuoteno AND tuote.yhtio=tilausrivi.yhtio
          WHERE tilausrivi.tunnus = '{$tilausrivi}'
          AND tilausrivi.yhtio='{$kukarow['yhtio']}'";
$result = pupe_query($query);
$tilausrivi = mysql_fetch_assoc($result);

$alkuperainen_saapuminen = $saapuminen;

// K�sitell��n eri saapumista
if (!empty($tilausrivi['uusiotunnus'])) {
  $saapuminen = $tilausrivi['uusiotunnus'];
}

if (empty($tullaan)) $tullaan = '';

$sscchaku = !empty($sscc) ? "and suuntalavat.sscc = '".mysql_escape_string($sscc)."'" : "";

// Etsit��n sopivat suuntalavat
$query = "(SELECT DISTINCT suuntalavat.tunnus, suuntalavat.sscc, suuntalavat.tila, suuntalavat.kaytettavyys, suuntalavat.keraysvyohyke, suuntalavat.tyyppi
           FROM suuntalavat
           JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus AND suuntalavat_saapuminen.saapuminen = '{$saapuminen}')
           WHERE suuntalavat.yhtio            = '{$kukarow['yhtio']}'
           AND (suuntalavat.keraysvyohyke = '{$tilausrivi['keraysvyohyke']}' OR suuntalavat.usea_keraysvyohyke = 'K')
           AND suuntalavat.tila               = ''
           {$sscchaku})
           UNION
           (SELECT DISTINCT suuntalavat.tunnus, suuntalavat.sscc, suuntalavat.tila, suuntalavat.kaytettavyys, suuntalavat.keraysvyohyke, suuntalavat.tyyppi
           FROM suuntalavat
           WHERE suuntalavat.yhtio            = '{$kukarow['yhtio']}'
           AND suuntalavat.keraysvyohyke      = '{$tilausrivi['keraysvyohyke']}'
           AND suuntalavat.tila               = ''
           AND suuntalavat.kaytettavyys       = 'L'
           {$sscchaku})
           UNION
           (SELECT DISTINCT suuntalavat.tunnus, suuntalavat.sscc, suuntalavat.tila, suuntalavat.kaytettavyys, suuntalavat.keraysvyohyke, suuntalavat.tyyppi
           FROM suuntalavat
           WHERE suuntalavat.yhtio            = '{$kukarow['yhtio']}'
           AND suuntalavat.usea_keraysvyohyke = 'K'
           AND suuntalavat.tila               = ''
           AND suuntalavat.kaytettavyys       = 'L'
           {$sscchaku})
           ORDER BY sscc, tunnus";
$suuntalavat_res = pupe_query($query);

if (isset($submit) and $tullaan != 'pre_vahvista_kerayspaikka') {
  if (empty($suuntalava)) $errors[] = t("Valitse suuntalava");

  if (!empty($sscc) and $submit == "hae") {
    if (mysql_num_rows($suuntalavat_res) == 1) {
      $_chk_row = mysql_fetch_assoc($suuntalavat_res);
      $suuntalava = $_chk_row['tunnus'];
      $submit = "ok";
      $errors = array();
    }
  }

  // Rivi suuntalavalle
  if (($submit == 'ok' or $submit == 'siirtovalmis' or $submit == 'suoraan_hyllyyn') and count($errors) == 0) {

    // Kohdista rivi(t)
    $query    = "SELECT * FROM lasku WHERE tunnus = '{$saapuminen}' AND yhtio = '{$kukarow['yhtio']}'";
    $result   = pupe_query($query);
    $laskurow = mysql_fetch_array($result);

    require "../inc/keikan_toiminnot.inc"; // T�� koittaa heti hakea uudelleen $laskurown ja nollaa siis edellisen haun??!?

    // Tarkistetaan m��r� ja splittaillaan jos tarvetta
    if ($hyllytetty < $tilausrivi['varattu']) {
      // P�ivitet��n alkuper�isen rivin kpl
      $ok = paivita_tilausrivin_kpl($tilausrivi['tunnus'], ($tilausrivi['varattu'] - $hyllytetty));
      $uusi_tilausrivi = splittaa_tilausrivi($tilausrivi['tunnus'], $hyllytetty, TRUE, FALSE);

      kohdista_rivi($laskurow, $uusi_tilausrivi, $tilausrivi['otunnus'], $saapuminen, $suuntalava);
    }
    elseif ($hyllytetty > $tilausrivi['varattu']) {
      $poikkeukset = array("tilausrivi.varattu" => ($hyllytetty-$tilausrivi['varattu']));
      $uusi_tilausrivi = kopioi_tilausrivi($tilausrivi['tunnus'], $poikkeukset);

      // Kohdistetaan molemmat rivit
      kohdista_rivi($laskurow, $tilausrivi['tunnus'], $tilausrivi['otunnus'], $saapuminen, $suuntalava);
      kohdista_rivi($laskurow, $uusi_tilausrivi, $tilausrivi['otunnus'], $saapuminen, $suuntalava);
    }
    else {
      kohdista_rivi($laskurow, $tilausrivi['tunnus'], $tilausrivi['otunnus'], $saapuminen, $suuntalava);
    }

    // Laitetaanko lava siirtovalmiiksi
    if ($submit == 'siirtovalmis' or $submit == 'suoraan_hyllyyn') {
      echo "Suuntalava $suuntalava siirtovalmiiksi<br>";

      // Suuntalavan k�sittelytapa (Suoraan (H)yllyyn)
      if ($submit == 'suoraan_hyllyyn') {
        echo "K�sittelytapa suoraan hyllyyn";
        $query = "UPDATE suuntalavat SET kasittelytapa='H' WHERE tunnus='{$suuntalava}'";
        $result = pupe_query($query);
      }

      // Suuntalava siirtovalmiiksi
      $suuntalavat_ei_kayttoliittymaa = "KYLLA";
      $tee = 'siirtovalmis';
      $suuntalavan_tunnus = $suuntalava;
      require "../tilauskasittely/suuntalavat.inc";
    }

    if ($riveja > 0) {
      $url = "tuotteella_useita_tilauksia.php";
    }
    elseif ($tullaan == 'vahvista_kerayspaikka') {
      $url = "suuntalavan_tuotteet.php?alusta_tunnus={$tilausrivi['suuntalava']}&liitostunnus={$laskurow['liitostunnus']}&oletuspaikat=true";
    }
    else {
      $url = "ostotilaus.php";
    }

    // Kaikki ok
    echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL={$url}?ostotilaus={$tilausrivi['otunnus']}&saapumisnro_haku={$saapumisnro_haku}&manuaalisesti_syotetty_ostotilausnro={$manuaalisesti_syotetty_ostotilausnro}&ennaltakohdistettu={$ennaltakohdistettu}'>";
    exit();
  }
}

$url = array (
  'ostotilaus' => $tilausrivi['otunnus'],
  'tilausrivi' => $tilausrivi['tunnus'],
  'saapuminen' => $alkuperainen_saapuminen,
  'tilausten_lukumaara' => $riveja,
  'manuaalisesti_syotetty_ostotilausnro' => $manuaalisesti_syotetty_ostotilausnro,
  'tuotenumero' => $tuotenumero,
  'alusta_tunnus' => $alusta_tunnus,
  'liitostunnus' => $liitostunnus,
  'ennaltakohdistettu' => $ennaltakohdistettu,
  'saapumisnro_haku' => $saapumisnro_haku,
);

if (!is_numeric($hyllytetty)) {
  echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=hyllytys.php?".http_build_query($url)."&virhe'>";
}

$tullaan = $tullaan == 'pre_vahvista_kerayspaikka' ? 'vahvista_kerayspaikka' : $tullaan;

echo "<div class='header'>";

if ($tullaan == 'vahvista_kerayspaikka') echo "<button onclick='window.location.href=\"vahvista_kerayspaikka.php?".http_build_query($url)."\"' class='button left'><img src='back2.png'></button>";
else echo "<button onclick='window.location.href=\"hyllytys.php?".http_build_query($url)."\"' class='button left'><img src='back2.png'></button>";

echo "<h1>", t("SUUNTALAVALLE"), "</h1></div>";
echo "<div class='main'>
<form method='post' action=''>

<input type='hidden' name='hyllytetty' value='{$hyllytetty}' />
<input type='hidden' name='saapuminen' value='{$alkuperainen_saapuminen}' />
<input type='hidden' name='tilausten_lukumaara' value='{$riveja}' />
<input type='hidden' name='tilausrivi' value='{$tilausrivi['tunnus']}' />
<input type='hidden' name='tullaan' value='{$tullaan}' />
<input type='hidden' name='alusta_tunnus' value='{$alusta_tunnus}' />
<input type='hidden' name='liitostunnus' value='{$liitostunnus}' />
<input type='hidden' name='saapumisnro_haku' value='{$saapumisnro_haku}' />

<table>
    <tr>
        <th><label for='sscc'>", t("Hae suuntalava"), "</label></th>
        <td>
            <input type='text' id='sscc' name='sscc' value='$hae'/>
        </td>
        <td>
            <button name='submit' id='hakunappi' class='button' value='hae' onclick='submit();'>", t("Etsi"), "</button>
        </td>
    </tr>
</table>

<table>
    <tr>
        <th></th>
        <th>", t("SSCC"), "</th>
        <th>", t("Ker.vy�hyk."), "</th>
        <th>", t("Rivej�"), "</th>
        <th>", t("Tyyppi"), "</th>
    </tr>";

$loytyiko = false;
while ($row = mysql_fetch_assoc($suuntalavat_res)) {

  if ($tilausrivi['suuntalava'] > 0 and $row['tunnus'] != $tilausrivi['suuntalava'] and $row['kaytettavyys'] != 'L' and $row['tila'] != '') {
    continue;
  }
  if ($row['tila'] == 'S') continue;
  if ($row['tila'] == 'P' and $tilausrivi["varastossa_kpl"] == 0) continue;
  //if ($sscc != '' and $row['sscc'] != $sscc) continue;
  if (!empty($sscc) and stristr($row['sscc'], $sscc) == false) continue;

  $loytyiko = true;

  $pakkaus_query = "SELECT pakkaus FROM pakkaus WHERE tunnus='{$row['tyyppi']}'";
  $tyyppi = mysql_fetch_assoc(pupe_query($pakkaus_query));

  $keraysvyohyke_query = "SELECT nimitys FROM keraysvyohyke WHERE tunnus='{$row['keraysvyohyke']}'";
  $keraysvyohyke = mysql_fetch_assoc(pupe_query($keraysvyohyke_query));

  //Haetaan suuntalavan tilausrivit
  $query = "SELECT tunnus FROM tilausrivi WHERE suuntalava='{$row['tunnus']}' AND yhtio='{$yhtiorow['yhtio']}'";
  $rivit = mysql_num_rows(pupe_query($query));

  echo "<tr>
        <td><input class='radio' id='suuntalava' type='radio' name='suuntalava' value='{$row['tunnus']}' />
        <td>{$row['sscc']}</td>
        <td>{$keraysvyohyke['nimitys']}</td>
        <td>{$rivit}</td>
        <td>{$tyyppi['pakkaus']}</td>
        <td>{$row['tila']}</td>
    </tr>";
}

if (!$loytyiko) $errors[] = t("Suuntalavaa ei l�ytynyt");

echo "</table></div>";
echo "<div class='controls'>
    <button name='submit' class='button' id='submit' value='ok' onclick='submit();'>", t("OK"), "</button>
    <button name='submit' class='button right' id='submit' value='siirtovalmis' onclick='submit();'>", t("Siirtovalmis (normaali)"), "</button>
    <button name='submit' class='button right' id='submit' value='suoraan_hyllyyn' onclick='submit();'>", t("Siirtovalmis (suoraan hyllyyn)"), "</button>
</div>
</form>
";
echo "<div class='error'>";
foreach ($errors as $error) {
  echo $error."</br>";
}
echo "</div>";

// Autofocus opera mobileen
echo "<input type='button' id='myHiddenButton' visible='false' onclick='javascript:doFocus();' width='1px' style='display:none'>";
echo "<script type='text/javascript'>

  $(document).ready(function() {
    $('#sscc').on('keyup', function() {
      // Autosubmit vain jos on sy�tetty tarpeeksi pitk� viivakoodi
      if ($('#sscc').val().length > 6) {
        document.getElementById('hakunappi').click();
      }
    });
  });

  function doFocus() {
          var focusElementId = 'alusta';
          var textBox = document.getElementById(focusElementId);
          textBox.focus();
      }

  function clickButton() {
     document.getElementById('myHiddenButton').click();
  }

  if(!document.getElementById('saapumiset')) {
       setTimeout('clickButton()', 500);
  }

</script>
";
