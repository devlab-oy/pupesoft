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
    if(empty($suuntalava)) $errors[] = t("Valitse suuntalava");

    # Rivi suuntalavalle
    if (($submit == 'ok' or $submit == 'siirtovalmis' or $submit == 'suoraan_hyllyyn') and count($errors) == 0) {

        # Kohdista rivi(t)
        $query    = "SELECT * FROM lasku WHERE tunnus = '{$saapuminen}' AND yhtio = '{$kukarow['yhtio']}'";
        $result   = pupe_query($query);
        $laskurow = mysql_fetch_array($result);

        require("../inc/keikan_toiminnot.inc"); # Tää koittaa heti hakea uudelleen $laskurown ja nollaa siis edellisen haun??!?

        # Tarkistetaan määrä ja splittaillaan jos tarvetta
        if ($hyllytetty < $tilausrivi['varattu']) {
            # Päivitetään alkuperäisen rivin kpl
            $ok = paivita_tilausrivin_kpl($tilausrivi['tunnus'], ($tilausrivi['varattu'] - $hyllytetty));
            $uusi_tilausrivi = splittaa_tilausrivi($tilausrivi['tunnus'], $hyllytetty, false, false);

            kohdista_rivi($laskurow, $uusi_tilausrivi, $tilausrivi['otunnus'], $saapuminen, $suuntalava);
        }
        else if ($hyllytetty > $tilausrivi['varattu']) {
            $poikkeukset = array("tilausrivi.varattu" => ($hyllytetty-$tilausrivi['varattu']));
            $uusi_tilausrivi = kopioi_tilausrivi($tilausrivi['tunnus'], $poikkeukset);

            # Kohdistetaan molemmat rivit
            kohdista_rivi($laskurow, $tilausrivi['tunnus'], $tilausrivi['otunnus'], $saapuminen, $suuntalava);
            kohdista_rivi($laskurow, $uusi_tilausrivi, $tilausrivi['otunnus'], $saapuminen, $suuntalava);
        }
        else {
            kohdista_rivi($laskurow, $tilausrivi['tunnus'], $tilausrivi['otunnus'], $saapuminen, $suuntalava);
        }

        # Laitetaanko lava siirtovalmiiksi
        if ($submit == 'siirtovalmis' or $submit == 'suoraan_hyllyyn') {
            echo "Suuntalava $suuntalava siirtovalmiiksi<br>";

            # Suuntalavan käsittelytapa (Suoraan (H)yllyyn)
            if ($submit == 'suoraan_hyllyyn') {
                echo "Käsittelytapa suoraan hyllyyn";
                $query = "UPDATE suuntalavat SET kasittelytapa='H' WHERE tunnus='{$suuntalava}'";
                $result = pupe_query($query);
            }

            # Suuntalava siirtovalmiiksi
            $suuntalavat_ei_kayttoliittymaa = "KYLLA";
            $tee = 'siirtovalmis';
            $suuntalavan_tunnus = $suuntalava;
            require ("../tilauskasittely/suuntalavat.inc");
        }

        # Kaikki ok
        echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=ostotilaus.php?ostotilaus={$tilausrivi['otunnus']}'>";
        exit();
    }
}

$url = array (
            'ostotilaus' => $tilausrivi['otunnus'],
            'tilausrivi' => $tilausrivi['tunnus'],
            'saapuminen' => $alkuperainen_saapuminen
        );

echo "<div class='header'>";
echo "<button onclick='window.location.href=\"hyllytys.php?".http_build_query($url)."\"' class='button left'><img src='back2.png'></button>";
echo "<h1>",t("SUUNTALAVALLE"), "</h1></div>";

echo "<div class='main'>
<form method='post' action=''>

<input type='hidden' name='hyllytetty' value='{$hyllytetty}' />
<input type='hidden' name='saapuminen' value='{$alkuperainen_saapuminen}' />

<table>
    <tr>
        <th><label for='sscc'>",t("Hae suuntalava"),"</label></th>
        <td>
            <input type='text' id='sscc' name='sscc' value='$hae'/>
        </td>
        <td>
            <button name='submit' class='button' value='hae' onclick='submit();'>",t("Etsi"),"</button>
        </td>
    </tr>
</table>

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

if (!$loytyiko) $errors[] = t("Suuntalavaa ei löytynyt");

echo "</table></div>";
echo "<div class='controls'>
    <button name='submit' class='button' id='submit' value='ok' onclick='submit();'>",t("OK"),"</button>
    <button name='submit' class='button right' id='submit' value='siirtovalmis' onclick='submit();'>",t("Siirtovalmis (normaali)"),"</button>
    <button name='submit' class='button right' id='submit' value='suoraan_hyllyyn' onclick='submit();'>",t("Siirtovalmis (suoraan hyllyyn)"),"</button>
</div>
</form>
";
echo "<div class='error'>";
    foreach($errors as $error) {
        echo $error."</br>";
    }
echo "</div>";
