<?php
$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;
$valinta = "Etsi";

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

if (!isset($errors)) $errors = array();

if (empty($ostotilaus) or empty($tilausrivi)) {
    exit("Virheelliset parametrit");
}
# Haetaan tilausrivin ja laskun tiedot
/* Ostotilausten_kohdistus rivi 847 - 895 */
$query = "  SELECT
            tilausrivi.varattu+tilausrivi.kpl siskpl,
            tilausrivi.tuoteno,
            round((tilausrivi.varattu+tilausrivi.kpl) * if (tuotteen_toimittajat.tuotekerroin<=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin),2) ulkkpl,
            tuotteen_toimittajat.toim_tuoteno,
            tuotteen_toimittajat.tuotekerroin,
            concat_ws(' ',tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso) as kerayspaikka,
            tilausrivi.varattu,
            tilausrivi.yksikko,
            tilausrivi.suuntalava,
            tilausrivi.uusiotunnus,
            lasku.liitostunnus,
			IFNULL(tilausrivin_lisatiedot.suoraan_laskutukseen, 'NORM') as tilausrivi_tyyppi
            FROM lasku
            JOIN tilausrivi ON tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi='O'
            JOIN tuotteen_toimittajat on (tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.yhtio=tilausrivi.yhtio)
			LEFT JOIN tilausrivin_lisatiedot
			ON ( tilausrivin_lisatiedot.yhtio = lasku.yhtio AND tilausrivin_lisatiedot.tilausrivilinkki = tilausrivi.tunnus )
            WHERE tilausrivi.tunnus='{$tilausrivi}'
            AND tilausrivi.yhtio='{$kukarow['yhtio']}'
            AND lasku.tunnus='{$ostotilaus}'";
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
    $saapuminen = uusi_saapuminen($toimittaja);
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
        $toimittaja_query = "SELECT * FROM toimi WHERE tunnus='{$saapumisen_toimittaja['liitostunnus']}'";
        $toimittaja = mysql_fetch_assoc(pupe_query($toimittaja_query));
        $saapuminen = uusi_saapuminen($toimittaja);

        // Päivitetään kuka.kesken
        $update_kuka = "UPDATE kuka SET kesken={$saapuminen} WHERE yhtio='{$kukarow['yhtio']}' AND kuka='{$kukarow['kuka']}'";
        $updated = pupe_query($update_kuka);
    }
}

# Kontrolleri
if (isset($submit)) {

    switch($submit) {
        case 'ok':
            # Vahvista keräyspaikka
            echo "<META HTTP-EQUIV='Refresh'CONTENT='1;URL=vahvista_kerayspaikka.php?hyllytys&".http_build_query($url_array)."&alusta_tunnus={$row['suuntalava']}&liitostunnus={$row['liitostunnus']}'>"; exit();
            break;
        case 'suuntalavalle':
            if (!is_numeric($hyllytetty) or $hyllytetty < 0) {
                $errors[] = t("Hyllytetyn määrän on oltava numero");
                break;
            }

            # Lisätään rivi suuntalavalle
            echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=suuntalavalle.php?tilausrivi={$tilausrivi}&saapuminen={$saapuminen}'>"; exit();

            break;
        case 'kerayspaikka':
            # Parametrit $alusta_tunnus, $liitostunnus, $tilausrivi
            echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=uusi_kerayspaikka.php?hyllytys&ostotilaus={$ostotilaus}&saapuminen={$saapuminen}&tilausrivi={$tilausrivi}'>"; exit();
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
elseif($row['tilausrivi_tyyppi'] == '') {
    //linkitetty osto / myyntitilaus varastoon
    $row['tilausrivi_tyyppi'] = 'JT';
}

######## UI ##########
# Otsikko
echo "<div class='header'>";
echo "<button onclick='window.location.href=\"ostotilaus.php?ostotilaus={$ostotilaus}\"' class='button left'><img src='back2.png'></button>";
echo "<h1>",t("HYLLYTYS")."</h1>";
echo "</div>";

# Main
echo "<div class='main'>
<form name='f1' method='post' action=''>
<table>
    <tr>
        <th>",t("Tilattu määrä"),"</th>
        <td>{$row['siskpl']}</td>
        <td>({$row['ulkkpl']})</td>
    </tr>
    <tr>
        <th>",t("Hyllytetty määrä"), "</th>
        <td><input id='numero' class='numero' type='text' name='hyllytetty' value='{$row['siskpl']}' onchange='update_label()'></input> {$row['tilausrivi_tyyppi']}</td>
        <td><span id='hylytetty_label'>{$row['ulkkpl']}</span></td>
    </tr>
    <tr>
        <th>",t("Tuote"),"</th>
        <td>{$row['tuoteno']}</td>
    </tr>
    <tr>
        <th>",t("Toim. Tuotekoodi"),"</th>
        <td>{$row['toim_tuoteno']}</td>
    </tr>
    <tr>
        <th>",t("Keräyspaikka"),"</th>
        <td>{$row['kerayspaikka']}</td>
        <td>({$row['varattu']} {$row['yksikko']})</td>
    </tr>
    <tr>
        <th>",t("Ostotilaus"),"</th>
        <td>{$ostotilaus}</td>
        <td><input type='hidden' name='ostotilaus' value='$ostotilaus'></td>
    </tr>
    <td><input type='hidden' name='saapuminen' value='$saapuminen'></td>
</table>
</div>";

# Napit
echo "
<div class='controls'>
<button type='submit' class='button left' onclick=\"f1.action='vahvista_kerayspaikka.php?hyllytys&alusta_tunnus={$row['suuntalava']}&liitostunnus={$row['liitostunnus']}&tilausrivi={$tilausrivi}'\">",t("OK"),"</button>
<button name='submit' class='button right' id='submit' value='kerayspaikka' onclick='submit();'>",t("KERÄYSPAIKKA"),"</button>
<button type='submit' class='button right' onclick=\"f1.action='suuntalavalle.php?tilausrivi={$tilausrivi}&saapuminen={$saapuminen}'\">",t("SUUNTALAVALLE"),"</button>
</div>
</form>";

echo "<div class='error'>";
    foreach($errors as $virhe) {
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
