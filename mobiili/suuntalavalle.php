<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;
$valinta = "Etsi";

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

if (empty($tilausrivi) or empty($saapuminen)) {
    exit("Virheelliset parametrit (tilausrivi: $tilausrivi, saapuminen: $saapuminen");
}

# Virheet
$errors = array();

/* ostotilausten_kohdistus.inc rivit 1541-1567
* Haetaan "sopivat" suuntalavat
*/
$query = "  SELECT tuote.keraysvyohyke, tilausrivi.*
            FROM tilausrivi
            JOIN tuote ON tuote.tuoteno=tilausrivi.tuoteno AND tuote.yhtio=tilausrivi.yhtio
            WHERE tilausrivi.tunnus = '{$tilausrivi}'
            AND tilausrivi.yhtio='{$kukarow['yhtio']}'";
$result = pupe_query($query);
$tilausrivi = mysql_fetch_assoc($result);

$alkuperainen_saapuminen = $saapuminen;

# Käsitellään eri saapumista
if (!empty($tilausrivi['uusiotunnus'])) {
    $saapuminen = $tilausrivi['uusiotunnus'];
}

# Etsitään sopivat suuntalavat
$query = "  (SELECT DISTINCT suuntalavat.tunnus, suuntalavat.sscc, suuntalavat.tila, suuntalavat.kaytettavyys, suuntalavat.keraysvyohyke, suuntalavat.tyyppi
            FROM suuntalavat
            JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus AND suuntalavat_saapuminen.saapuminen = '{$saapuminen}')
            WHERE suuntalavat.yhtio = '{$kukarow['yhtio']}'
            AND (suuntalavat.keraysvyohyke = '{$tilausrivi['keraysvyohyke']}' OR suuntalavat.usea_keraysvyohyke = 'K')
            AND suuntalavat.tila IN ('', 'S', 'P'))
            UNION
            (SELECT DISTINCT suuntalavat.tunnus, suuntalavat.sscc, suuntalavat.tila, suuntalavat.kaytettavyys, suuntalavat.keraysvyohyke, suuntalavat.tyyppi
            FROM suuntalavat
            WHERE suuntalavat.yhtio = '{$kukarow['yhtio']}'
            AND suuntalavat.tila IN ('', 'S', 'P')
            AND suuntalavat.tunnus = '{$tilausrivi['suuntalava']}')
            UNION
            (SELECT DISTINCT suuntalavat.tunnus, suuntalavat.sscc, suuntalavat.tila, suuntalavat.kaytettavyys, suuntalavat.keraysvyohyke, suuntalavat.tyyppi
            FROM suuntalavat
            WHERE suuntalavat.yhtio = '{$kukarow['yhtio']}'
            AND suuntalavat.keraysvyohyke = '{$tilausrivi['keraysvyohyke']}'
            AND suuntalavat.tila = ''
            AND suuntalavat.kaytettavyys = 'L')
            UNION
            (SELECT DISTINCT suuntalavat.tunnus, suuntalavat.sscc, suuntalavat.tila, suuntalavat.kaytettavyys, suuntalavat.keraysvyohyke, suuntalavat.tyyppi
            FROM suuntalavat
            WHERE suuntalavat.yhtio = '{$kukarow['yhtio']}'
            AND suuntalavat.usea_keraysvyohyke = 'K'
            AND suuntalavat.tila IN ('', 'S', 'P')
            AND suuntalavat.kaytettavyys = 'L')
            ORDER BY sscc, tunnus";

$suuntalavat_res = pupe_query($query);

if (isset($submit)) {
    switch($submit) {
        case 'hae':
            break;
        case 'ok':
            if(empty($suuntalava)) {
                $errors[] = t("Valitse suuntalava.");
                break;
            }

            # Kohdista rivi(t)
            $query    = "SELECT * FROM lasku WHERE tunnus = '{$saapuminen}' AND yhtio = '{$kukarow['yhtio']}'";
            $result   = pupe_query($query);
            $laskurow = mysql_fetch_array($result);

            require("../inc/keikan_toiminnot.inc"); # Tää koittaa heti hakea uudelleen $laskurown ja nollaa siis edellisen haun??!?

            if ($hyllytetty < $tilausrivi['varattu']) {
                # Päivitetään alkuperäisen rivin kpl
                $ok = paivita_tilausrivin_kpl($tilausrivi['tunnus'], ($tilausrivi['varattu'] - $hyllytetty));

                $uusi_tilausrivi = splittaa_tilausrivi($tilausrivi['tunnus'], $hyllytetty, false, false);

                kohdista_rivi($laskurow, $uusi_tilausrivi, $tilausrivi['otunnus'], $saapuminen, $suuntalava);
            }
            else if ($hyllytetty >$tilausrivi['varattu']) {
                $poikkeukset = array("tilausrivi.varattu" => ($hyllytetty-$tilausrivi['varattu']));
                $uusi_tilausrivi = kopioi_tilausrivi($tilausrivi['tunnus'], $poikkeukset);

                # Kohdistetaan molemmat rivit
                kohdista_rivi($laskurow, $tilausrivi['tunnus'], $tilausrivi['otunnus'], $saapuminen, $suuntalava);
                kohdista_rivi($laskurow, $uusi_tilausrivi, $tilausrivi['otunnus'], $saapuminen, $suuntalava);
            }
            else {
                kohdista_rivi($laskurow, $tilausrivi['tunnus'], $tilausrivi['otunnus'], $saapuminen, $suuntalava);
            }

            # Kaikki ok
            echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=ostotilaus.php?ostotilaus={$tilausrivi['otunnus']}'>"; exit();
            break;

        case 'lopeta':
            $url = array (
                        'ostotilaus' => $tilausrivi['otunnus'],
                        'tilausrivi' => $tilausrivi['tunnus'],
                        'saapuminen' => $alkuperainen_saapuminen
                    );

            echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=hyllytys.php?".http_build_query($url)."'>"; exit();
            break;
        case 'suuntalavalle':
            if(empty($suuntalava)) {
                $errors[] = t("Valitse suuntalava.");
                break;
            }
            echo "varmistus";
            break;
        case 'hyllytyskierrokselle':
            if(is_numeric($korkeus)) echo " korkeus ok";
            break;
        case 'hyllyyn':
            if(is_numeric($korkeus)) echo " korkeus ok";
            break;
        default:
            echo "VIRHE";
            break;
    }
}

echo "<div class='header'><h1>",t("SUUNTALAVALLE"), "</h1></div>";

echo "<div class='main'>
<form method='post' action=''>
<table>
    <tr>
        <th><label for='sscc'>",t("Hae suuntalava"),"</label></th>
        <td>
            <input type='text' id='sscc' name='sscc' value='$hae'/>
        </td>
        <td>
            <button name='submit' value='hae' onclick='submit();'>",t("Etsi"),"</button>
        </td>
    </tr>
</table>
</form>

<form method='post' action=''>
<table>
    <tr>
        <th></th>
        <th>",t("SSCC"),"</th>
        <th>",t("Ker.vyöhyk."),"</th>
        <th>",t("Rivejä"),"</th>
        <th>",t("Tyyppi"),"</th>
    </tr>";

$loytyiko = false;
while($row = mysql_fetch_assoc($suuntalavat_res)) {

    if ($tilausrivi['suuntalava'] > 0 and $row['tunnus'] != $tilausrivi['suuntalava'] and $row['kaytettavyys'] != 'L' and $row['tila'] != '') {
        continue;
    }
    if($row['tila'] == 'S') continue;
    if ($row['tila'] == 'P' and $tilausrivi["varastossa_kpl"] == 0) continue;
    #if ($sscc != '' and $row['sscc'] != $sscc) continue;
    if (!empty($sscc) and stristr($row['sscc'], $sscc) == false) continue;

    $loytyiko = true;

    $pakkaus_query = "SELECT pakkaus FROM pakkaus WHERE tunnus='{$row['tyyppi']}'";
    $tyyppi = mysql_fetch_assoc(pupe_query($pakkaus_query));

    $keraysvyohyke_query = "SELECT nimitys FROM keraysvyohyke WHERE tunnus='{$row['keraysvyohyke']}'";
    $keraysvyohyke = mysql_fetch_assoc(pupe_query($keraysvyohyke_query));

    #Haetaan suuntalavan tilausrivit
    $query =    "   SELECT tilausrivi.*
                    FROM tilausrivi
                    JOIN suuntalavat ON (suuntalavat.yhtio = tilausrivi.yhtio AND suuntalavat.tila = '') AND suuntalavat.tunnus = tilausrivi.suuntalava and suuntalavat.tunnus = {$row['tunnus']}
                    JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus AND suuntalavat_saapuminen.saapuminen = tilausrivi.uusiotunnus)
                    JOIN tuote use index (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
                    LEFT JOIN tuotteen_toimittajat use index (yhtio_tuoteno) ON (tuotteen_toimittajat.yhtio = tuote.yhtio AND tuotteen_toimittajat.tuoteno = tuote.tuoteno)
                    WHERE tilausrivi.yhtio = '{$yhtiorow['yhtio']}'
                    AND tilausrivi.tyyppi = 'O'
                    AND tilausrivi.kpl = 0
                    AND tilausrivi.suuntalava > 0;";
    $rivit = mysql_num_rows(pupe_query($query));

    echo "<tr>
        <td><input class='radio' id='suuntalava' type='radio' name='suuntalava' value='{$row['tunnus']}' />
        <td>{$row['sscc']}</td>
        <td>{$keraysvyohyke['nimitys']}</td>
        <td>{$rivit}</td>
        <td>{$tyyppi['pakkaus']}</td>
        <td>{$row['tila']}</td>
        <td><input type='hidden' name='hyllytetty' value='{$hyllytetty}' /></td>
        <td><input type='hidden' name='saapuminen' value='{$alkuperainen_saapuminen}' /></td>
    </tr>";
}

if (!$loytyiko) $errors[] = t("Suuntalavaa ei löytynyt");

echo "</table></div>";
echo "<div class='controls'>
    <button name='submit' id='submit' value='ok' onclick='submit();'>",t("OK"),"</button>
    <button name='submit' id='submit' value='lopeta' onclick='submit();'>",t("Lopeta"),"</button>
    <button name='submit' id='submit' value='suuntalavalle' onclick='submit();'>",t("OK Valmis"),"</button>
</div>
</form>
";
echo "<div class='error'>";
    foreach($errors as $error) {
        echo $error."</br>";
    }
echo "</div>";
