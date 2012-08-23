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
            concat_ws(' ',tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso) as kerayspaikka,
            tilausrivi.varattu,
            tilausrivi.yksikko,
            tilausrivi.suuntalava,
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
    exit("Virhe, rivi‰ ei lˆydy");
}

# Kontrolleri
if (isset($submit)) {

    switch($submit) {
        case 'takaisin':
            echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=ostotilaus.php'>"; exit();
            break;
        case 'ok': # Vahvista ker‰yspaikka
            # M‰‰r‰‰ pienennet‰‰n
            if ($hyllytetty < $row['siskpl']) {
                # Splitataan rivi, $pois_suuntalavalta = false
                #$uuden_rivin_id = splittaa_tilausrivi($tilausrivi, $hyllytetty, false, false);
                # P‰ivitet‰‰n vanhan kpl
                #$ok = paivita_tilausrivin_kpl($tilausrivi, ($row['siskpl'] - $hyllytetty));

                # Varastoon viet‰v‰ rivi
                #$url_array['tilausrivi'] = $uuden_rivin_id;
            }
            # M‰‰r‰‰ nostetaan
            elseif ($hyllytetty > $row['siskpl']) {
                #echo "splitataan rivi";
                # Miten molemmat rivit vahvista_kerayspaikka.php:lle?

            }
            # Tilattu == Hyllytetty
            else {
            }

            # Parametrit $alusta_tunnus=tilausrivi.suuntalava & lasku.liitostunnus=liitostunnus
            #echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=vahvista_kerayspaikka.php?ostotilaus={$ostotilaus}&tilausrivi={$tilausrivi}&alusta_tunnus={$row['suuntalava']}&liitostunnus={$row['liitostunnus']}'>"; exit();
            echo "<META HTTP-EQUIV='Refresh'CONTENT='1;URL=vahvista_kerayspaikka.php?hyllytys&".http_build_query($url_array)."&alusta_tunnus={$row['suuntalava']}&liitostunnus={$row['liitostunnus']}'>"; exit();
            break;
        case 'suuntalavalle':
            if (!is_numeric($hyllytetty) or $hyllytetty < 0) {
                $errors[] = "Hyllytetyn m‰‰r‰n on oltava numero";
                break;
            }

            # Lis‰t‰‰n rivi suuntalavalle
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
include("kasipaate.css");

# Otsikko
echo "<div class='header'>";
echo "<h1>",t("HYLLYTYS", $browkieli)."</h1>";
echo "</div>";

# Main
echo "<div class='main'>
<form name='f1' method='post' action=''>
<table>
    <tr>
        <th>Tilattu m‰‰r‰</th>
        <td>{$row['siskpl']}</td>
        <td>({$row['ulkkpl']})</td>
    </tr>
    <tr>
        <th>Hyllytetty m‰‰r‰</th>
        <td><input type='text' name='hyllytetty' value='{$row['siskpl']}'></input></td>
        <td>(?)</td>
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
        <th>Ker‰yspaikka</th>
        <td>{$row['kerayspaikka']}</td>
        <td>({$row['varattu']} {$row['yksikko']})</td>
    </tr>
    <tr>
        <th>Ostotilaus</th>
        <td>{$ostotilaus}</td>
        <td><input type='hidden' name='ostotilaus' value='$ostotilaus'></td>
    </tr>
    <tr>
        <th>Saapuminen</th>
        <td>$saapuminen</td>
        <td><input type='hidden' name='saapuminen' value='$saapuminen'></td>
    </tr>
    <tr>
        <th>Suuntalava</th>
        <td>$suuntalava</td>
    </tr>
</table>
</div>";

# Napit
echo "
<div class='controls'>
<button type='submit' class='left' onclick=\"f1.action='vahvista_kerayspaikka.php?hyllytys&alusta_tunnus={$row['suuntalava']}&liitostunnus={$row['liitostunnus']}&tilausrivi={$tilausrivi}'\">",t("OK", $browkieli),"</button>
<button type='submit' class='left' onclick=\"f1.action='suuntalavalle.php?tilausrivi={$tilausrivi}&saapuminen={$saapuminen}'\">",t("SUUNTALAVALLE", $browkieli),"</button>
<button name='submit' class='right' id='submit' value='kerayspaikka' onclick='submit();'>",t("KERƒYSPAIKKA", $browkieli),"</button>
<button name='submit' class='right' id='submit' value='takaisin' onclick='submit();'>",t("TAKAISIN", $browkieli),"</button>
</div>
</form>";

echo "<div class='error'>";
    foreach($errors as $virhe) {
        echo $virhe."<br>";
    }
echo "</div>";
