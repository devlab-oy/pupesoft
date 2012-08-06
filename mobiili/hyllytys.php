<?php
$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;
$valinta = "Etsi";

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

if(!isset($errors)) $errors = array();

# Rajataan sallitut get parametrit
$sallitut_parametrit = array('viivakoodi', 'tuotenumero', 'ostotilaus', 'tilausrivi');

# Rakkentaan parametreist‰ url_taulukko
$url_array = array();
foreach($sallitut_parametrit as $parametri) {
    if(!empty($$parametri)) {
        $url_array[$parametri] = $$parametri;
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


# Kontrolleri
if (isset($submit)) {
    switch($submit) {
        case 'ok':
            echo "OK";
            #echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=vahvista_kerayspaikka.php?selected_row={$tilausrivi}&alusta_tunnus={$dummy_lava}&liitostunnus=6690653'>"; exit();
            break;
        case 'lopeta':
            # TODO: t‰m‰n pit‰is palata ostotilaus.php:lle
            echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=tuotteella_useita_tilauksia.php?".http_build_query($url_array)."'>"; exit();
            break;
        case 'suuntalavalle':
            if (!is_numeric($hyllytetty) or $hyllytetty < 0) {
                $errors['m‰‰r‰'] = "Hyllytetyn m‰‰r‰n on oltava numero";
                break;
            }
            # Jos hyllytetty‰ m‰‰r‰‰ ollaan pienennetty
            if ($hyllytetty < $row['tilkpl']) {
                echo "Hyllytety m‰‰r‰ on pienempi kuin tilattujen (splitataan rivi)";
                # Poikkeuksena asetetaan tilausrivin.varattu hyllytetyksi m‰‰r‰ksi
                # mik‰ on pienempi kuin alkuper‰isen rivin tilkpl.
                # P‰ivitet‰‰n kopioitava rivi.
                $poikkeukset = array("tilausrivi.varattu" => $hyllytetty);
                $uuden_rivin_id = kopioi_tilausrivi($tilausrivi, $poikkeukset);
            }
            # Jos hyllytetty‰ m‰‰r‰‰ ollaan nostettu
            elseif ($hyllytetty > $row['tilkpl']) {
                echo "Hyllytetty m‰‰r‰ on suurempi kuin tilattujen (insertti erotukselle)";
                $poikkeukset = array("tilausrivi.varattu" => $hyllytetty);
                $uuden_rivin_id = kopioi_tilausrivi($tilausrivi, $poikkeukset);
            }
            else {
                echo "Hyllytetty ja tilattujen m‰‰r‰ on sama";
            }
            # Jos hyllytetty m‰‰r‰ on sama kuin tilattu
            #echo "<META HTTP-EQUIV='Refresh'CONTENT='3;URL=suuntalavalle.php?".http_build_query($url_array)."'>"; exit();
            break;
        default:
            $errors['virhe'] = "Error";
            break;
    }
}

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
        <th>Tilattu m‰‰r‰</th>
        <td>{$row['tilkpl']}</td>
        <td>({$row['ulkkpl']})</td>
    </tr>
    <tr>
        <th>Hyllytetty m‰‰r‰</th>
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
        <th>Ker‰yspaikka</th>
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
<button name='submit' class='right' id='submit' value='kerayspaikka' onclick='submit();'>",t("KERƒYSPAIKKA", $browkieli),"</button>
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
