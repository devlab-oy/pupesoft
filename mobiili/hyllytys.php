<?php
$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;
$valinta = "Etsi";

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

# Rajataan sallitut get parametrit
$sallitut_parametrit = array('viivakoodi', 'tuotenumero', 'ostotilaus');

# Rakkentaan parametreistä url_taulukko
$url_array = array();
foreach($sallitut_parametrit as $parametri) {
    if(!empty($$parametri)) {
        $url_array[$parametri] = $$parametri;
    }
}

# Kontrolleri
if (isset($submit)) {
    switch($submit) {
        case 'ok':
            echo "OK";
            var_dump($_POST);
            #echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=vahvista_kerayspaikka.php'>"; exit();
            #exit;
            break;
        case 'lopeta':
            # TODO: tämän pitäis palata ostotilaus.php:lle
            echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=tuotteella_useita_tilauksia.php?".http_build_query($url_array)."'>"; exit();
            break;
        default:
            $errors['virhe'] = "Error";
            break;
    }
}

/*
* Ostotilausten_kohdistus rivi 847 - 895...
*/
$query = "  SELECT
            tilausrivi.tilkpl,
            tilausrivi.tuoteno,
            round((tilausrivi.varattu+tilausrivi.kpl) * if (tuotteen_toimittajat.tuotekerroin<=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin),2) ulkkpl,
            tuotteen_toimittajat.toim_tuoteno,
            concat_ws(' ',tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllytaso, tilausrivi.hyllyvali) as kerayspaikka,
            tilausrivi.varattu,
            tilausrivi.yksikko
            FROM tilausrivi
            JOIN tuotteen_toimittajat on (tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.yhtio=tilausrivi.yhtio)
            WHERE tilausrivi.tunnus='{$tilausrivi}'
            AND tilausrivi.yhtio='{$kukarow['yhtio']}'";

$result = pupe_query($query);
$row = mysql_fetch_assoc($result);

######## UI ##########
include("kasipaate.css");

# Otsikko
echo "<div class='header'>";
echo "<h1>",t("HYLLYTYS", $browkieli)."</h1>";
echo "</div>";

# Main
echo "<div class='main'>";

echo "
<form method='post' action=''>
<table>
    <tr>
        <th>Tilattu määrä</th>
        <td>{$row['tilkpl']}</td>
        <td>({$row['ulkkpl']})</td>
    </tr>
    <tr>
        <th>Hyllytetty määrä</th>
        <td><input type='text' name='hyllytetty' value='{$row['tilkpl']}'></input></td>
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
        <th>Keräyspaikka</th>
        <td>{$row['kerayspaikka']}</td>
        <td>({$row['varattu']} {$row['yksikko']})</td>
    </tr>
    <tr>
        <th>Ostotilaus</th>
        <td>{$ostotilaus}</td>
    </tr>
</table>
";

echo "</div>";

# Napit
echo "
<div class='controls'>
<button name='submit' class='left' id='submit' value='ok' onclick='submit();'>",t("OK", $browkieli),"</button>
<button name='submit' class='right' id='submit' value='kerayspaikka' onclick='submit();'>",t("KERÄYSPAIKKA", $browkieli),"</button>
<button name='submit' class='left' id='submit' value='suuntalavalle' onclick='submit();'>",t("SUUNTALAVALLE", $browkieli),"</button>
<button name='submit' class='right' id='submit' value='lopeta' onclick='submit();'>",t("LOPETA", $browkieli),"</button>
</div>
</form>";

echo "<div class='error'>";
    foreach($errors as $virhe => $selite) {
        echo strtoupper($virhe).": ".$selite;
    }
echo "</div>";

// echo "<br><br><hr><pre>";
// var_dump($_GET);
// echo "<pre>";
// echo $query;
