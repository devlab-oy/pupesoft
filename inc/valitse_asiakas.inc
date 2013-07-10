<?php

require('tilauskasittely/luo_myyntitilausotsikko.inc');
echo "<font class='head'>" . t("Ext-Tarjoukset") . "</font><hr>";
?>

<style>

</style>
<script>
$(document).ready(function() {
	bind_kaikki_asiakkaat_checkbox_click();
});
function bind_kaikki_asiakkaat_checkbox_click() {
    $('#valitse_kaikki').on('click', function() {
        
        if ($(this).is(':checked')) {
            $(this).parent().parent().parent().parent().find('.valitut_asiakastunnukset').each(function(index, value) {
                $(value).attr('checked', 'checked');
            });
		}
		else {
			$(this).parent().parent().parent().parent().find('.valitut_asiakastunnukset').each(function(index, value) {
                $(value).removeAttr('checked');
            });
		}
    });
}
</script>
<?php

$request = array(
    "hakuehto" => $hakuehto,
    "mul_asiakasosasto" => $mul_asiakasosasto,
    "mul_asiakasryhma" => $mul_asiakasryhma,
    "mul_asiakasmyyja" => $mul_asiakasmyyja,
    "ext_asiakas_haku_tehty" => $ext_asiakas_haku_tehty,
    "tarjous_tee" => $tarjous_tee,
    "tilausnumero" => $tilausnumero,
    "action" => $action,
    "valitut_asiakastunnukset" => $valitut_asiakastunnukset,
);


$asiakasid = $kukarow['tunnus'] * -1;

if ($request['action'] == 'liita_asiakkaat') {
    luo_myyntitilausotsikko('TARJOUS', $asiakasid, $request['tilausnumero']);
} elseif ($request['action'] == 'poista_asiakkaat') {
    poista_asiakkaita_dummytarjoukselta($request['tilausnumero'], $request['valitut_asiakastunnukset']);
}

$query = "  select lasku.*, laskun_lisatiedot.sopimus_lisatietoja
            from lasku
            join laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio and laskun_lisatiedot.otunnus = lasku.tunnus)
            where lasku.yhtio='{$kukarow['yhtio']}'
            and lasku.tila='T'
            and lasku.alatila=''
            and lasku.liitostunnus='{$asiakasid}'";
$result = pupe_query($query);

if (mysql_num_rows($result) > 0) {
    $dummy_tarjous_row = mysql_fetch_assoc($result);
    $request['tilausnumero'] = $dummy_tarjous_row['tunnus'];
    $request['dummytarjous'] = $dummy_tarjous_row;
} else {
    $request['tilausnumero'] = luo_myyntitilausotsikko('TARJOUS', $asiakasid);
}



//var_dump($request);
if ($request['tarjous_tee'] == 'nayta_poimitut_asiakkaat') {
    if (!empty($request['dummytarjous']['sopimus_lisatietoja'])) {
        $request['asiakkaat'] = hae_poimitut_asiakkaat($request['dummytarjous']['sopimus_lisatietoja']);
        echo_asiakas_table($request);
    } else {
        echo "<font class='message'>" . t("Tarjoukselle ei ole valittu yht‰‰n asiakasta") . "</font>";
        echo "<br>";
        echo_asiakas_table($request);
    }
} else {
    if (isset($request['ext_asiakas_haku_tehty'])) {

        $request['asiakkaat'] = hae_asiakkaat_joilla_extranet_kayttajatunnus($request);
    }

    echo_asiakas_lisays_form($request);
}

require ('inc/footer.inc');
exit;

function echo_asiakas_lisays_form($request) {

    global $kukarow, $yhtiorow, $orig_tila, $orig_alatila;

    echo "<div id='asiakasvalinta_wrapper'>";

    echo_asiakas_rajaus($request);
    echo_asiakas_table($request);

    echo "</div>";
}

function echo_asiakas_rajaus($request) {

    global $kukarow, $yhtiorow, $mul_asiakasosasto, $mul_asiakasryhma, $mul_asiakasmyyja;
    echo "<form method='post' action='tilaus_myynti.php'>";
    echo "<input type='hidden' name='tarjous_tee' value='nayta_asiakas_valinta' />";
    echo "<input type='hidden' name='toim' value='EXTTARJOUS' />";
    echo "<input type='hidden' name='ext_asiakas_haku_tehty' value='1' />";
    echo "<div id='infomessage'>";
    if (!empty($request['dummytarjous']['sopimus_lisatietoja'])) {
        $liitettyjen_asiakkaiden_lkm = count(explode(',', $request['dummytarjous']['sopimus_lisatietoja']));
    } else {
        $liitettyjen_asiakkaiden_lkm = 0;
    }
    echo t("Sinulla on") . ' ' . $liitettyjen_asiakkaiden_lkm . ' ' . t("poimittua asiakasta");
    echo "</div>";

    echo "<div id='hakuehdot_wrapper'>";
    $monivalintalaatikot = array("ASIAKASOSASTO", "ASIAKASRYHMA", "ASIAKASMYYJA");
    $monivalintalaatikot_normaali = array();



    require ("tilauskasittely/monivalintalaatikot.inc");
    
    echo t("Anna y-tunnus, nimi tai asiakasnumero");
    echo "<input type='text' name='hakuehto' value='{$request['hakuehto']}'>";
    echo "<input type='submit' value='" . t("Hae") . "'>";
    echo "</div>";
    echo "</form>";
}

function hae_poimitut_asiakkaat($asiakastunnukset) {

    global $kukarow, $yhtiorow;

    $query = " select * from asiakas
                where yhtio = '{$kukarow['yhtio']}'
                and tunnus IN ({$asiakastunnukset})";

    $result = pupe_query($query);
    $asiakkaat = array();

    while ($asiakas = mysql_fetch_assoc($result)) {
        $asiakkaat[] = $asiakas;
    }
    return $asiakkaat;
}

function echo_asiakas_table($request) {

    global $kukarow, $yhtiorow, $orig_tila, $orig_alatila;

    $raaka_arska = explode(",", $request['dummytarjous']['sopimus_lisatietoja']);
    
    if (count($request['asiakkaat']) > 0) {

        echo "<form method='post' action='tilaus_myynti.php'>";
        echo "<input type='hidden' name='tilausnumero' value='{$request['tilausnumero']}'/>";
        echo "<input type='hidden' name='toim' value='EXTTARJOUS'/>";
        //FUNTSI
        echo "<input type='hidden' name='tarjous_tee' value='nayta_poimitut_asiakkaat'/>";
        if ($request['tarjous_tee'] == 'nayta_asiakas_valinta') {
            echo "<input type='hidden' name='action' value='liita_asiakkaat'/>";
        } else {
            echo "<input type='hidden' name='action' value='poista_asiakkaat'/>";
        }
        echo "<input type='hidden' name='orig_tila' value='T' />";
        echo "<input type='hidden' name='orig_alatila' value='{$orig_alatila}' />";
        echo "<table id='asiakkaat'>";

        echo "<tr>";
        echo "<th><input type='checkbox' id='valitse_kaikki'></th>";
        echo "<th>", t("Nimi"), "</th>";
        echo "<tr>";

        foreach ($request['asiakkaat'] as $asiakas) {
            echo "<tr class='aktiivi'>";
            echo "<td>";
            if (in_array($asiakas['tunnus'], $raaka_arska) and $request['tarjous_tee'] != 'nayta_poimitut_asiakkaat') {
                echo "X";
            } else {
                echo "<input type='checkbox' class='valitut_asiakastunnukset' name='valitut_asiakastunnukset[]' value='{$asiakas['tunnus']}'>";
            }
            echo "</td>";
            echo "<td>";
            echo $asiakas['nimi'];
            echo "</td>";
            echo "<tr>";
        }

        echo "</table>";
        echo "<br>";
        if ($request['tarjous_tee'] == 'nayta_asiakas_valinta') {
            $submitvalue = t("Liit‰ asiakkaat");
        } else {
            $submitvalue = t("Poista asiakkaat");
        }
        echo "<input type='submit' value='{$submitvalue}'/>";
        echo "</form>";
        echo "<br>";
    }
    echo "<form method='post' action='tilaus_myynti.php'>";
    echo "<input type='hidden' name='toim' value='EXTTARJOUS'/>";
    echo "<input type='hidden' name='kaytiin_otsikolla' value='NOJOO!' />";
    echo "<input type='hidden' name='tee' value='OTSIK' />";
    echo "<input type='hidden' name='tarjous_tee' value='luo_dummy_tarjous' />";
    echo "<input type='hidden' name='tilausnumero' value='{$request['tilausnumero']}' />";
    echo "<input type='submit' id='luo_tarjous' value='" . t("Luo tarjous") . "'/>";
    echo "</form>";


    if ($request['tarjous_tee'] == 'nayta_poimitut_asiakkaat') {
        echo "<form method='post' action='tilaus_myynti.php'>";
        echo "<input type='hidden' name='toim' value='EXTTARJOUS'/>";
        echo "<input type='hidden' name='tarjous_tee' value='nayta_asiakas_valinta' />";
        echo "<input type='submit' id='takaisin_asiakasvalintaan' value ='" . t("Takaisin asiakasvalintaan") . "'/>";
        echo "</form>";
    } else {
        echo "<form method='post' action='tilaus_myynti.php'>";
        echo "<input type='hidden' name='toim' value='EXTTARJOUS'/>";
        echo "<input type='hidden' name='tarjous_tee' value='nayta_poimitut_asiakkaat' />";
        echo "<input type='submit' id='nayta_poimitut' value ='" . t("N‰yt‰ poimitut") . "'/>";
        echo "</form>";
    }
}

function poista_asiakkaita_dummytarjoukselta($tilausnumero, $valitut_asiakastunnukset) {

    global $kukarow, $yhtiorow;

    //haetaan dummytarjoukselle jo lis‰tyt asiakkaat jotta niist‰ voidaan poistaa valitut asiakkaat
    $query = "  select sopimus_lisatietoja from laskun_lisatiedot
                where yhtio='{$kukarow['yhtio']}'
                and otunnus='{$tilausnumero}'";

    $result = pupe_query($query);
    $dummytarjoukselta_loytyvat_asiakastunnukset = mysql_fetch_assoc($result);
    $dummytarjoukselta_loytyvat_asiakastunnukset = explode(',', $dummytarjoukselta_loytyvat_asiakastunnukset['sopimus_lisatietoja']);
    $poiston_jalkeiset_asiakkaat = array_diff($dummytarjoukselta_loytyvat_asiakastunnukset, $valitut_asiakastunnukset);

    $query = "  update laskun_lisatiedot
                set sopimus_lisatietoja = '" . implode(",", $poiston_jalkeiset_asiakkaat) . "'
                where yhtio='{$kukarow['yhtio']}'
                and otunnus='{$tilausnumero}'";
    pupe_query($query);
}

function hae_asiakkaat_joilla_extranet_kayttajatunnus($request) {

    global $kukarow, $yhtiorow;
    $where = "AND asiakas.laji NOT IN ( 'P' )";

    if (!empty($request['mul_asiakasosasto'])) {
        $where .= " AND asiakas.osasto IN ('" . implode("','", $request['mul_asiakasosasto']) . "')";
    }
    if (!empty($request['mul_asiakasryhma'])) {
        $where .= " AND asiakas.ryhma IN ('" . implode("','", $request['mul_asiakasryhma']) . "')";
    }
    if (!empty($request['mul_asiakasmyyja'])) {
        $where .= " AND asiakas.myyjanro IN ('" . implode("','", $request['mul_asiakasmyyja']) . "')";
    }
    if (empty($request['hakuehto'])) {
        $query = "SELECT asiakas.*
                    FROM asiakas
                     JOIN kuka ON ( kuka.yhtio = asiakas.yhtio AND kuka.oletus_asiakas = asiakas.tunnus AND kuka.extranet = 'X' )
                    WHERE asiakas.yhtio = '{$kukarow['yhtio']}'
                     {$where}
					ORDER BY nimi";
    } else {
        $query = "	(SELECT asiakas.*
						FROM asiakas
						JOIN yhtio on asiakas.yhtio=yhtio.yhtio
                        JOIN kuka ON ( kuka.yhtio = asiakas.yhtio AND kuka.oletus_asiakas = asiakas.tunnus AND kuka.extranet = 'X' )
						WHERE asiakas.yhtio = '{$kukarow['yhtio']}'
						and REPLACE(REPLACE(asiakas.ytunnus, '-', ''), '+', '') = '" . str_replace(array('-', '+'), '', $request['hakuehto']) . "'
						and asiakas.ytunnus!=''
                        {$where})
					UNION
					(SELECT asiakas.*
						FROM asiakas
						JOIN yhtio on asiakas.yhtio=yhtio.yhtio
                        JOIN kuka ON ( kuka.yhtio = asiakas.yhtio AND kuka.oletus_asiakas = asiakas.tunnus AND kuka.extranet = 'X' )
						WHERE asiakas.yhtio = '{$kukarow['yhtio']}'
						and asiakas.asiakasnro = '{$request['hakuehto']}'
						and asiakas.asiakasnro not in ('0','')
                        {$where})
					UNION
					(SELECT asiakas.*
						FROM asiakas
						JOIN yhtio on asiakas.yhtio=yhtio.yhtio
                        JOIN kuka ON ( kuka.yhtio = asiakas.yhtio AND kuka.oletus_asiakas = asiakas.tunnus AND kuka.extranet = 'X' )
						WHERE asiakas.yhtio = '{$kukarow['yhtio']}'
						and REPLACE(REPLACE(asiakas.ovttunnus, '-', ''), '+', '') = '" . str_replace(array('-', '+'), '', $request['hakuehto']) . "'
						and asiakas.ovttunnus!=''
                        {$where})
					UNION
					(SELECT asiakas.*
						FROM asiakas
						JOIN yhtio on asiakas.yhtio=yhtio.yhtio
                        JOIN kuka ON ( kuka.yhtio = asiakas.yhtio AND kuka.oletus_asiakas = asiakas.tunnus AND kuka.extranet = 'X' )
						WHERE asiakas.yhtio = '{$kukarow['yhtio']}'
						and asiakas.toim_ovttunnus = '{$request['hakuehto']}'
						and asiakas.toim_ovttunnus!=''
                        {$where})
					UNION
					(SELECT asiakas.*
						FROM asiakas
						JOIN asiakkaan_avainsanat ON (asiakas.yhtio=asiakkaan_avainsanat.yhtio and asiakas.tunnus=asiakkaan_avainsanat.liitostunnus and asiakkaan_avainsanat.laji='kantaasiakastunnus')
						JOIN yhtio on asiakas.yhtio=yhtio.yhtio
                        JOIN kuka ON ( kuka.yhtio = asiakas.yhtio AND kuka.oletus_asiakas = asiakas.tunnus AND kuka.extranet = 'X' )
						WHERE asiakas.yhtio = '{$kukarow['yhtio']}'
						and asiakkaan_avainsanat.avainsana = '{$request['hakuehto']}'
						and asiakkaan_avainsanat.avainsana!=''
                        {$where})
                    UNION
                    (SELECT asiakas.*
                    FROM asiakas
                     JOIN kuka ON ( kuka.yhtio = asiakas.yhtio AND kuka.oletus_asiakas = asiakas.tunnus AND kuka.extranet = 'X' )
                    WHERE asiakas.yhtio = '{$kukarow['yhtio']}'
                    AND asiakas.nimi LIKE '%{$request['hakuehto']}%'
                     {$where})
					ORDER BY nimi";
    }
    $result = pupe_query($query);

    $asiakkaat = array();
    while ($asiakas = mysql_fetch_assoc($result)) {
        $asiakkaat[] = $asiakas;
    }
    return $asiakkaat;
}