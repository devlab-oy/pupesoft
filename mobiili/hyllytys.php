<?php
$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;
$valinta = "Etsi";

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");

if (!isset($errors)) $errors = array();
if (!isset($saapumisnro_haku)) $saapumisnro_haku = '';

if (empty($ostotilaus) or empty($tilausrivi)) {
  exit("Virheelliset parametrit");
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
          if(tuotepaikat.varasto = tilausrivi.varasto,
            concat_ws(' ',
              tuotepaikat.hyllyalue,
              tuotepaikat.hyllynro,
              tuotepaikat.hyllyvali,
              tuotepaikat.hyllytaso),
            concat_ws(' ',
              tilausrivi.hyllyalue,
              tilausrivi.hyllynro,
              tilausrivi.hyllyvali,
              tilausrivi.hyllytaso)) AS kerayspaikka,
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
              AND tilausrivin_lisatiedot.tilausrivilinkki = tilausrivi.tunnus
              AND tilausrivin_lisatiedot.tilausrivilinkki <> 0)
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
  exit("Virhe, rivi‰ ei lˆydy");
}

// Haetaan toimittajan tiedot
$toimittaja_query = "SELECT * FROM toimi WHERE tunnus='{$row['liitostunnus']}'";
$toimittaja = mysql_fetch_assoc(pupe_query($toimittaja_query));

// Jos saapumista ei ole setattu, tehd‰‰n uusi saapuminen haetulle toimittajalle
if (empty($saapuminen)) {
  $saapuminen = uusi_saapuminen($toimittaja, $kukarow['toimipaikka']);
  $update_kuka = "UPDATE kuka SET kesken={$saapuminen} WHERE yhtio='{$kukarow['yhtio']}' AND kuka='{$kukarow['kuka']}'";
  $updated = pupe_query($update_kuka);
}
// Jos saapuminen on niin tarkistetaan ett‰ se on samalle toimittajalle
else {
  // Haetaan saapumisen toimittaja tunnus
  $saapuminen_query = "SELECT liitostunnus
                       FROM lasku
                       WHERE tunnus='{$saapuminen}'";
  $saapumisen_toimittaja = mysql_fetch_assoc(pupe_query($saapuminen_query));

  // jos toimittaja ei ole sama kuin tilausrivin niin tehd‰‰n uusi saapuminen
  if ($saapumisen_toimittaja['liitostunnus'] != $row['liitostunnus']) {

    // Haetaan toimittajan tiedot uudestaan ja tehd‰‰n uudelle toimittajalle saapuminen
    $toimittaja_query = "SELECT * FROM toimi WHERE tunnus='{$row['liitostunnus']}'";
    $toimittaja = mysql_fetch_assoc(pupe_query($toimittaja_query));
    $saapuminen = uusi_saapuminen($toimittaja, $kukarow['toimipaikka']);
  }

  //jos ollaan ennaltakohdistetussa (mutta ei tuloutetussa) riviss‰, niin se on poikkeustapaus, jolloin kukarow.kesken tietoa ei tule p‰ivitt‰‰ -> muuten seuraavien rivien p‰ivitys menee sekaisin ja tuloutetaan rivej‰ v‰‰r‰lle saapumiselle
  if (!isset($ennaltakohdistettu) or !$ennaltakohdistettu) {
    // P‰ivitet‰‰n kuka.kesken
    $update_kuka = "UPDATE kuka SET kesken={$saapuminen} WHERE yhtio='{$kukarow['yhtio']}' AND kuka='{$kukarow['kuka']}'";
    $updated = pupe_query($update_kuka);
  }
}

// Kontrolleri
if (isset($submit)) {

  $url = "&viivakoodi={$viivakoodi}&tilausten_lukumaara={$tilausten_lukumaara}&saapumisnro_haku={$saapumisnro_haku}&manuaalisesti_syotetty_ostotilausnro={$manuaalisesti_syotetty_ostotilausnro}&ennaltakohdistettu={$ennaltakohdistettu}&tuotenumero=".urlencode($tuotenumero);

  switch ($submit) {
  case 'ok':
    // Vahvista ker‰yspaikka
    echo "<META HTTP-EQUIV='Refresh'CONTENT='1;URL=vahvista_kerayspaikka.php?hyllytys&".http_build_query($url_array)."{$url}&saapuminen={$saapuminen}&alusta_tunnus={$row['suuntalava']}&liitostunnus={$row['liitostunnus']}'>"; exit();
    break;
  case 'suuntalavalle':
    if (!is_numeric($hyllytetty) or $hyllytetty < 0) {
      $errors[] = t("Hyllytetyn m‰‰r‰n on oltava numero");
      break;
    }

    // Lis‰t‰‰n rivi suuntalavalle
    echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=suuntalavalle.php?tilausrivi={$tilausrivi}&saapumisnro_haku={$saapumisnro_haku}&saapuminen={$saapuminen}{$url}'>"; exit();

    break;
  case 'kerayspaikka':
    // Parametrit $alusta_tunnus, $liitostunnus, $tilausrivi
    echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=uusi_kerayspaikka.php?hyllytys&saapumisnro_haku={$saapumisnro_haku}&ostotilaus={$ostotilaus}{$url}&saapuminen={$saapuminen}&tilausrivi={$tilausrivi}'>"; exit();
    break;
  default:
    $errors[] = "Error";
    break;
  }
}

$suuntalava = $row['suuntalava'] ? : "Ei ole";

if ($row['tilausrivi_tyyppi'] == 'o') {
  //suoratoimitus asiakkaalle
  $row['tilausrivi_tyyppi'] = 'JTS';
}
elseif ($row['tilausrivi_tyyppi'] == '') {
  //linkitetty osto / myyntitilaus varastoon
  $row['tilausrivi_tyyppi'] = 'JT';
}

$url_prelisa = $tilausten_lukumaara < 2 ? "ostotilaus.php" : "tuotteella_useita_tilauksia.php";
$url_lisa = $manuaalisesti_syotetty_ostotilausnro ? "ostotilaus={$ostotilaus}" : "";
$url_lisa .= ($viivakoodi != "" and $tilausten_lukumaara > 1) ? "&viivakoodi={$viivakoodi}" : "";
$url_lisa .= "&tuotenumero=".urlencode($tuotenumero);
$url_lisa .= "&manuaalisesti_syotetty_ostotilausnro={$manuaalisesti_syotetty_ostotilausnro}";
$url_lisa .= "&ennaltakohdistettu={$ennaltakohdistettu}";
$url_lisa .= "&saapumisnro_haku={$saapumisnro_haku}";

//####### UI ##########
// Otsikko
echo "<div class='header'>";
echo "<button onclick='window.location.href=\"{$url_prelisa}?{$url_lisa}\"' class='button left'><img src='back2.png'></button>";
echo "<h1>", t("HYLLYTYS")."</h1>";
echo "</div>";

// Main
echo "<div class='main'>
<form name='f1' method='post' action=''>
<input type='hidden' name='tilausten_lukumaara' value='{$tilausten_lukumaara}' />
<input type='hidden' name='manuaalisesti_syotetty_ostotilausnro' value='{$manuaalisesti_syotetty_ostotilausnro}' />
<input type='hidden' name='tuotenumero' value='{$tuotenumero}' />
<input type='hidden' name='saapumisnro_haku' value='{$saapumisnro_haku}' />
<table>
    <tr>
        <th>", t("Tilattu m‰‰r‰"), "</th>
        <td>{$row['siskpl']}</td>
        <td>({$row['ulkkpl']})</td>
    </tr>
    <tr>
        <th>", t("Hyllytetty m‰‰r‰"), "</th>
        <td><input id='numero' class='numero' type='text' name='hyllytetty' value='{$row['siskpl']}' onchange='update_label()'></input> {$row['tilausrivi_tyyppi']}</td>
        <td><span id='hylytetty_label'>{$row['ulkkpl']}</span></td>
    </tr>
    <tr>
        <th>", t("Tuote"), "</th>
        <td>{$row['tuoteno']}</td>
    </tr>";

echo "<tr>";

if (trim($row['selaus']) != "") {
  echo "<th>{$row['selaus']}</th>";
}
else {
  echo "<th>", t("Toim. Tuotekoodi"), "</th>";
}

echo "<td>{$row['toim_tuoteno']}</td>";
echo "</tr>";

echo "<tr>
        <th>", t("Ker‰yspaikka"), "</th>
        <td>{$row['kerayspaikka']}</td>
        <td>({$row['varattu']} {$row['yksikko']})</td>
    </tr>";

if ($row['tilausrivitunnus'] != 0) {

  $query = "SELECT tilausrivi.otunnus, lasku.nimi
            FROM tilausrivi
            JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus)
            WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
            AND tilausrivi.tunnus  = '{$row['tilausrivitunnus']}'";
  $tilausrivitunnus_res = pupe_query($query);
  $tilausrivitunnus_row = mysql_fetch_assoc($tilausrivitunnus_res);

  echo "<tr>
            <td colspan='3' align='center'><font color='chucknorris'>", t("Myyntitilaus"), " {$tilausrivitunnus_row['otunnus']} - {$tilausrivitunnus_row['nimi']}</font>
            <input type='hidden' name='ostotilaus' value='{$ostotilaus}'></td>
        </tr>";
}
else {
  echo "<tr>
            <th>", t("Ostotilaus"), "</th>
            <td>{$ostotilaus}</td>
            <td><input type='hidden' name='ostotilaus' value='$ostotilaus'></td>
        </tr>";
}

echo "<td><input type='hidden' name='saapuminen' value='$saapuminen'></td>
</table>
</div>";

$url = "&viivakoodi={$viivakoodi}&saapumisnro_haku={$saapumisnro_haku}&tilausten_lukumaara={$tilausten_lukumaara}&manuaalisesti_syotetty_ostotilausnro={$manuaalisesti_syotetty_ostotilausnro}&tuotenumero=".urlencode($tuotenumero);

// Napit
echo "<div class='controls'>";
echo "<button type='submit' class='button left' onclick=\"f1.action='vahvista_kerayspaikka.php?hyllytys{$url}&alusta_tunnus={$row['suuntalava']}&liitostunnus={$row['liitostunnus']}&tilausrivi={$tilausrivi}&ostotilaus={$ostotilaus}&ennaltakohdistettu={$ennaltakohdistettu}'\">", t("OK"), "</button>";
echo "<button name='submit' class='button right' id='submit' value='kerayspaikka' onclick='submit();'>", t("UUSI KERƒYSPAIKKA"), "</button>";

if ($yhtiorow['suuntalavat'] != "") {
  echo "<button type='submit' class='button right' onclick=\"f1.action='suuntalavalle.php?tilausrivi={$tilausrivi}{$url}&saapuminen={$saapuminen}&ennaltakohdistettu={$ennaltakohdistettu}'\">", t("SUUNTALAVALLE"), "</button>";

}

echo "</div>";
echo "</form>";

echo "<div class='error'>";
foreach ($errors as $virhe) {
  echo $virhe."<br>";
}
echo "</div>";

echo "<script type='text/javascript'>
    function update_label(numero) {
        var hyllytetty = document.getElementById('numero').value;
        var tuotekerroin = {$row['tuotekerroin']} * hyllytetty;
        var label = document.getElementById('hylytetty_label');
        label.innerHTML = tuotekerroin;
    }
</script>";
