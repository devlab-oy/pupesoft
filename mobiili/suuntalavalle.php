<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;
$valinta = "Etsi";

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

# Rajataan sallitut get parametrit
$sallitut_parametrit = array('viivakoodi', 'tuotenumero', 'ostotilaus', 'tilausrivi', 'saapuminen');

# Rakkentaan parametreistä url_taulukko
$url_array = array();
foreach($sallitut_parametrit as $parametri) {
    if(!empty($$parametri)) {
        $url_array[$parametri] = $$parametri;
    }
}

/* ostotilausten_kohdistus.inc rivit 1541-1567
* Haetaan "sopivat" suuntalavat
*/
# TODO: Jos rivi on splitattu, niin laitetaanko molemmat samalle suuntalavalle
$query = "select * from tilausrivi where tunnus='{$tilausrivi}'";
$tilausrivi = pupe_query($query);
$tilausrivi = mysql_fetch_assoc($tilausrivi);
#echo "<pre>";
#var_dump($tilausrivi);
#exit();

# $otunnus = tilausrivi.tunnus vai .uusiotunnus?
# $otunnus = $tilausrivi['tunnus'];
# $rivirow['suuntalava'] = tilausrivi.suuntalava?
# $rivirow['suuntalava'] = $tilausrivi['suuntalava'];

# $rivirow['keraysvyohyke'] = tuote.kerahyvyohyke
$rivirow['keraysvyohyke'] = 5;
# $rivirow['varastossa_kpl'] = tilausrivi.kpl
$rivirow['varastossa_kpl'] = $tilausrivi['kpl'];

# Etsitään sopivat suuntalavat
$query = "  (SELECT DISTINCT suuntalavat.tunnus, suuntalavat.sscc, suuntalavat.tila, suuntalavat.kaytettavyys, suuntalavat.keraysvyohyke, suuntalavat.tyyppi
            FROM suuntalavat
            JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus AND suuntalavat_saapuminen.saapuminen = '{$otunnus}')
            WHERE suuntalavat.yhtio = '{$kukarow['yhtio']}'
            AND (suuntalavat.keraysvyohyke = '{$rivirow['keraysvyohyke']}' OR suuntalavat.usea_keraysvyohyke = 'K')
            AND suuntalavat.tila IN ('', 'S', 'P'))
            UNION
            (SELECT DISTINCT suuntalavat.tunnus, suuntalavat.sscc, suuntalavat.tila, suuntalavat.kaytettavyys, suuntalavat.keraysvyohyke, suuntalavat.tyyppi
            FROM suuntalavat
            WHERE suuntalavat.yhtio = '{$kukarow['yhtio']}'
            AND suuntalavat.tila IN ('', 'S', 'P')
            AND suuntalavat.tunnus = '{$rivirow['suuntalava']}')
            UNION
            (SELECT DISTINCT suuntalavat.tunnus, suuntalavat.sscc, suuntalavat.tila, suuntalavat.kaytettavyys, suuntalavat.keraysvyohyke, suuntalavat.tyyppi
            FROM suuntalavat
            WHERE suuntalavat.yhtio = '{$kukarow['yhtio']}'
            AND suuntalavat.keraysvyohyke = '{$rivirow['keraysvyohyke']}'
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
            $suuntalavat = etsi_suuntalava_sscc($sscc);
            if(count($suuntalavat) == 0) {
                $errors['virhe'] = "Ei löytynyt sopivaa suuntalavaa. Hae uudestaan";
            }
            break;
        case 'ok':
            # 26525174###6690659 ($tilausrivi###$ostotilaus)
            $valittu = $tilausrivi."###".$ostotilaus;

            # Kohdista rivi
            $query    = "SELECT * FROM lasku WHERE tunnus = '{$saapuminen}' AND yhtio = '{$kukarow['yhtio']}'";
            $result   = pupe_query($query);
            $laskurow = mysql_fetch_array($result);

            require("../inc/keikan_toiminnot.inc");
            $kohdista_status = kohdista_rivi($laskurow, $tilausrivi['tunnus'], $ostotilaus, $saapuminen, $suuntalava);

            echo "kohdista_rivi({$tilausrivi['tunnus']}, $ostotilaus, $saapuminen, $suuntalava, $suoratoimitusrivi)";
            echo "<br>Kohdista_status: ";
            var_dump($kohdista_status);

            # Kaikki ok
            # echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=ostotilaus.php?'>"; exit();
            break;
        case 'lopeta':
            echo "lopeta";
            echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=hyllytys.php?".http_build_query($url_array)."'>"; exit();
            break;
        case 'suuntalavalle':
            // echo "varmistus";
            // echo "<META HTTP-EQUIV='Refresh'CONTENT='1;URL=varmistus.php?".http_build_query($url_array)."'>"; exit();
            // break;
            $varmistus = true;
            break;
        case 'hyllytyskierrokselle':
            echo "hyllytyskierros";
            if(is_numeric($korkeus)) echo " korkeus ok";
            break;
        case 'hyllyyn':
            echo "suoraan hyllyyn";
            if(is_numeric($korkeus)) echo " korkeus ok";
            break;
        case 'takaisin':
            #echo "<META HTTP-EQUIV='Refresh'CONTENT='1;URL=suuntalavalle.php?".http_build_query($url_array)."'>"; exit();
            $varmistus = false;
            break;
        default:
            echo "VIRHE";
            break;
    }
}

include("kasipaate.css");

# Varmistuskysymys
if ($varmistus) {
    include('varmistus.php');
    exit();
}

echo "<div class='header'><h1>",t("SUUNTALAVALLE", $browkieli), "</h1></div>";

echo "<div class='main'>
<form method='post' action=''>
<table>
    <tr>
        <th><label for='sscc'>Hae suuntalava:</label></th>
        <td>
            <input type='text' id='sscc' name='sscc' />
        </td>
        <td>
            <button name='submit' value='hae' onclick='submit();'>",t("Etsi", $browkieli),"</button>
        </td>
    </tr>
</table>
</form>

<form name='kissa' method='post' action=''>
<table>
    <tr>
        <th></th>
        <th>Suuntalavan nro</th>
        <th>Ker.vyöhyk.</th>
        <th>Rivejä</th>
        <th>Tyyppi</th>
    </tr>";


while($row = mysql_fetch_assoc($suuntalavat_res)) {

    if ($rivirow['suuntalava'] > 0 and $row['tunnus'] != $rivirow['suuntalava'] and $row['kaytettavyys'] != 'L' and $row['tila'] != '') {
        continue;
    }
    if($row['tila'] == 'S') continue;
    if ($row['tila'] == 'P' and $rivirow["varastossa_kpl"] == 0) continue;
    if ($sscc != '' and $row['sscc'] != $sscc) continue;

    $pakkaus_query = "select pakkaus from pakkaus where tunnus='{$row['tyyppi']}'";
    $tyyppi = mysql_fetch_assoc(pupe_query($pakkaus_query));

    $keraysvyohyke_query = "select nimitys from keraysvyohyke where tunnus='{$row['keraysvyohyke']}'";
    $keraysvyohyke = mysql_fetch_assoc(pupe_query($keraysvyohyke_query));

    # Haetaan suuntalavan tilausrivit, HIDAS query
    // if ($disabled == '') {
    //     $rivit_query = "SELECT count(*) rivit FROM tilausrivi WHERE suuntalava='{$row['tunnus']}' and yhtio='{$kukarow['yhtio']}'";
    //     #echo $rivit_query;
    //     $rivit = mysql_fetch_assoc(pupe_query($rivit_query));
    // }
    echo "<tr>
        <td><input class='radio' id='suuntalava' type='radio' name='suuntalava' value='{$row['tunnus']}' />
        <td>{$row['sscc']}</td>
        <td>{$keraysvyohyke['nimitys']}</td>
        <td>{$rivit['rivit']}</td>
        <td>{$tyyppi['pakkaus']}</td>
        <td>{$row['tila']}</td>
        <td>{$rivirow['varastossa_kpl']}</td>
        <td><input type='hidden' name='tilausrivi' value='{$tilausrivi['tunnus']}' /></td>
    </tr>";
}
echo "</table></div>";

echo "<div class='controls'>
    <button name='submit' id='submit' value='ok' onclick='submit();'>",t("OK", $browkieli),"</button>
    <button name='submit' id='submit' value='lopeta' onclick='submit();'>",t("Lopeta", $browkieli),"</button>
    <button name='submit' id='submit' value='suuntalavalle' onclick='submit();'>",t("OK Valmis", $browkieli),"</button>
</div>
</form>
";
echo "<div class='error'>";
    foreach($errors as $virhe => $selite) {
        echo strtoupper($virhe).": ".$selite;
    }
echo "</div>";

echo "<pre>";
var_dump($tilausrivi);
echo "</pre>";