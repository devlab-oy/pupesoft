<?php
$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;
$valinta = "Etsi";

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

if (!isset($errors)) $errors = array();

if (empty($ostotilaus) or empty($tilausrivi) or empty($saapuminen)) {
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
            lasku.liitostunnus
            FROM lasku
            JOIN tilausrivi ON tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi='O'
            JOIN tuotteen_toimittajat on (tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.yhtio=tilausrivi.yhtio)
            WHERE tilausrivi.tunnus='{$tilausrivi}'
            AND tilausrivi.yhtio='{$kukarow['yhtio']}'
            AND lasku.tunnus='{$ostotilaus}'";
$result = pupe_query($query);
$row = mysql_fetch_assoc($result);

if (!$row) {
    exit("Virhe, riviä ei löydy");
}

# Kontrolleri
if (isset($submit)) {

    switch($submit) {
        case 'takaisin':
            echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=ostotilaus.php?ostotilaus={$ostotilaus}'>"; exit();
            break;
        case 'ok':
            # Vahvista keräyspaikka
            echo "<META HTTP-EQUIV='Refresh'CONTENT='1;URL=vahvista_kerayspaikka.php?hyllytys&".http_build_query($url_array)."&alusta_tunnus={$row['suuntalava']}&liitostunnus={$row['liitostunnus']}'>"; exit();
            break;
        case 'suuntalavalle':
            if (!is_numeric($hyllytetty) or $hyllytetty < 0) {
                $errors[] = "Hyllytetyn määrän on oltava numero";
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

######## UI ##########
# Otsikko
echo "<div class='header'>";
echo "<h1>",t("HYLLYTYS", $browkieli)."</h1>";
echo "</div>";

# Main
echo "<div class='main'>
<form name='f1' method='post' action=''>
<table>
    <tr>
        <th>Tilattu määrä</th>
        <td>{$row['siskpl']}</td>
        <td>({$row['ulkkpl']})</td>
    </tr>
    <tr>
        <th>Hyllytetty määrä</th>
        <td><input id='numero' class='numero' type='text' name='hyllytetty' value='{$row['siskpl']}' onchange='update_label()'></input></td>
        <td><span id='hylytetty_label'>{$row['ulkkpl']}</span></td>
    </tr>
    <tr>
        <th>Tuote</th>
        <td>{$row['tuoteno']}</td>
    </tr>
    <tr>
        <th>Toim. Tuotekoodi</th>
        <td>{$row['toim_tuoteno']}</td>
    </tr>
    <tr>
        <th>Keräyspaikka</th>
        <td>{$row['kerayspaikka']}</td>
        <td>({$row['varattu']} {$row['yksikko']})</td>
    </tr>
    <tr>
        <th>Ostotilaus</th>
        <td>{$ostotilaus}</td>
        <td><input type='hidden' name='ostotilaus' value='$ostotilaus'></td>
    </tr>
    <td><input type='hidden' name='saapuminen' value='$saapuminen'></td>
</table>
</div>";

# Napit
echo "
<div class='controls'>
<button type='submit' class='left' onclick=\"f1.action='vahvista_kerayspaikka.php?hyllytys&alusta_tunnus={$row['suuntalava']}&liitostunnus={$row['liitostunnus']}&tilausrivi={$tilausrivi}'\">",t("OK", $browkieli),"</button>
<button name='submit' class='right' id='submit' value='kerayspaikka' onclick='submit();'>",t("KERÄYSPAIKKA", $browkieli),"</button>
<button type='submit' class='left' onclick=\"f1.action='suuntalavalle.php?tilausrivi={$tilausrivi}&saapuminen={$saapuminen}'\">",t("SUUNTALAVALLE", $browkieli),"</button>
<button name='submit' class='right' id='submit' value='takaisin' onclick='submit();'>",t("TAKAISIN", $browkieli),"</button>
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
