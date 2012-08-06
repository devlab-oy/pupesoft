<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;
$valinta = "Etsi";

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

# Rajataan sallitut get parametrit
$sallitut_parametrit = array('viivakoodi', 'tuotenumero', 'ostotilaus', 'tilausrivi');

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

#### Testausta
$query = "select * from tilausrivi where tunnus='{$tilausrivi}'";
$tilausrivi = pupe_query($query);
$tilausrivi = mysql_fetch_assoc($tilausrivi);

# $otunnus = tilausrivi.tunnus vai .uusiotunnus?
$otunnus = $tilausrivi['tunnus'];
# $rivirow['suuntalava'] = tilausrivi.suuntalava?
$rivirow['suuntalava'] = $tilausrivi['suuntalava'];
# $rivirow['keraysvyohyke'] = tuote.kerahyvyohyke
$rivirow['keraysvyohyke'] = 5;
# $rivirow['varastossa_kpl'] = tilausrivi.kpl
$rivirow['varastossa_kpl'] = $tilausrivi['kpl'];
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

            # echo "haetaan sscc: $sscc";
            $suuntalavat = etsi_suuntalava_sscc($sscc);
            if(count($suuntalavat) == 0) {
                $errors['virhe'] = "Ei löytynyt sopivaa suuntalavaa. Hae uudestaan";
            }

            break;
        case 'ok':
            # keikan_toiminnot (91-144)
            # Tarkistetaan onko suuntalava siirtovalmis
            $query = "  SELECT tila
                        FROM suuntalavat
                        WHERE yhtio = '{$kukarow['yhtio']}'
                        AND tunnus = '{$suuntalava}'";
            $tila_chk_res = pupe_query($query);
            $tila_chk_row = mysql_fetch_assoc($tila_chk_res);

            if ($tila_chk_row['tila'] == 'S') {
                $errors['suuntalava'] = "Suuntalavan tila on S";
            }
            else {
                # Päivitetään tilausrivin suuntalava
                $query = "  UPDATE tilausrivi SET
                            suuntalava = '{$suuntalava}'
                            WHERE yhtio = '{$kukarow['yhtio']}'
                            AND tunnus = '{$selected_row}'";
                $update_res = pupe_query($query);

                $query = "  SELECT uusiotunnus
                            FROM tilausrivi
                            WHERE yhtio = '{$kukarow['yhtio']}'
                            AND tunnus = '{$selected_row}'";
                $otunnus_fetch_res = pupe_query($query);
                $otunnus_fetch_row = mysql_fetch_assoc($otunnus_fetch_res);

                $uusiotunnus = $otunnus_fetch_row['uusiotunnus'];

                if ($suuntalava != 0) {
                    $query = "  SELECT saapuminen
                                FROM suuntalavat_saapuminen
                                WHERE yhtio = '{$kukarow['yhtio']}'
                                AND suuntalava = '{$suuntalava}'
                                AND saapuminen = '{$uusiotunnus}'";
                    $fetch_res = pupe_query($query);

                    if (mysql_num_rows($fetch_res) == 0) {
                        $query = "  INSERT INTO suuntalavat_saapuminen SET
                                    yhtio = '{$kukarow['yhtio']}',
                                    suuntalava = '{$suuntalava}',
                                    saapuminen = '{$uusiotunnus}',
                                    laatija = '{$kukarow['kuka']}',
                                    luontiaika = now(),
                                    muutospvm = now(),
                                    muuttaja = '{$kukarow['kuka']}'";
                        $insert_res = pupe_query($query);
                    }
                }
                ###

                # Kaikki ok
                echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=ostotilaus.php?'>"; exit();
            }
            break;
        case 'lopeta':
            echo "lopeta";
            echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=hyllytys.php?".http_build_query($url_array)."'>"; exit();
            break;
        case 'suuntalavalle':
            echo "suuntalavalle";
            break;
        default:
            echo "VIRHE";
            break;
    }
}

include("kasipaate.css");

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

<form method='post' action=''>
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
        <td><input class='radio' id='selected_row' type='radio' name='selected_row' value='{$tilausrivi['tunnus']}' />
        <td><label for='selected_row'>{$row['sscc']}</label></td>
        <td>{$keraysvyohyke['nimitys']}</td>
        <td>{$rivit['rivit']}</td>
        <td><label for='selected_row'>{$tyyppi['pakkaus']}</label></td>
        <td>{$row['tila']}</td>
        <td>{$rivirow['varastossa_kpl']}</td>
        <td><input type='hidden' name='suuntalava' value='{$row['tunnus']}' /></td>
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