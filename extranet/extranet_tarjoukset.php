<?php

require ("../inc/parametrit.inc");

enable_ajax();

echo "<font class='head'>" . t("Ext-tarjoukset") . "</font><hr>";
?>
<style>

</style>
<script>

</script>
<?php

$request = array(
    "kayttajaan_liitetty_asiakas" => $kayttajaan_liitetty_asiakas,
    "asiakkaan_tarjoukset" => $asiakkaan_tarjoukset,
    "action" => $action,
    "valittu_tarjous_tunnus" => $valittu_tarjous_tunnus,
);

$request['kayttajaan_liitetty_asiakas'] = hae_extranet_kayttajaan_liitetty_asiakas();

//$request['asiakkaan_tarjoukset'] = hae_extranet_tarjoukset($request['kayttajaan_liitetty_asiakas']['tunnus']);
$request['asiakkaan_tarjoukset'] = hae_extranet_tarjoukset(57620);

if ($request['action'] == 'nayta_tarjous') {
    nayta_tarjous($request['valittu_tarjous_tunnus']);
}
else {
    $header_values = array(
        'nimi' => t('Nimi'),
        'hinta' => t('Tarjouksen hinta'),
        'toimaika' => t('Viimeinen voimasssaolop‰iv‰m‰‰r‰'),
    );

    echo_rows_in_table($request['asiakkaan_tarjoukset'], $header_values, array(), 'tee_tarjouksen_nimesta_linkki');
}

function hae_extranet_tarjoukset($asiakasid) {
    global $kukarow, $yhtiorow;

    $query = "  SELECT concat_ws('!!!', lasku.tunnus, lasku.nimi) AS nimi,
                lasku.hinta,
                lasku.toimaika
                FROM lasku
                WHERE lasku.yhtio = '{$kukarow['yhtio']}'
                AND lasku.liitostunnus = '{$asiakasid}'
                AND lasku.tila = 'T'
                /*AND lasku.clearing = 'EXTTARJOUS'*/";
    $result = pupe_query($query);

    $haetut_tarjoukset = array();

    while ($haettu_tarjous = mysql_fetch_assoc($result)) {
        $haetut_tarjoukset[] = $haettu_tarjous;
    }
    return $haetut_tarjoukset;
}

function hae_extranet_kayttajaan_liitetty_asiakas() {
    global $kukarow, $yhtiorow;

    $query = "  SELECT asiakas.*
                FROM asiakas
                JOIN kuka ON ( kuka.yhtio = asiakas.yhtio AND kuka.oletus_asiakas = asiakas.tunnus AND kuka.extranet = 'X' AND kuka.kuka = '{$kukarow['kuka']}' )
                WHERE asiakas.yhtio = '{$kukarow['yhtio']}'
                ";
    $result = pupe_query($query);

    return mysql_fetch_assoc($result);
}

function tee_tarjouksen_nimesta_linkki($header, $solu, $force_to_string) {
    global $kukarow, $yhtiorow;

    if ($header == 'nimi') {
        $tunnus_nimi_array = explode('!!!', $solu);
        echo "<td>";
        echo "<a href='extranet_tarjoukset.php?action=nayta_tarjous&valittu_tarjous_tunnus={$tunnus_nimi_array[0]}'>{$tunnus_nimi_array[1]}</a>";
        echo "</td>";
    }
    else {
        echo "<td>";
        echo $solu;
        echo "</td>";
    }
}

function nayta_tarjous($valittu_tarjous_tunnus) {
    global $kukarow, $yhtiorow;
    
    $tarjous = hae_tarjous($valittu_tarjous_tunnus);
    
    echo_tarjouksen_otsikko($tarjous);
    echo_tarjouksen_tilausrivit($tarjous);
}

function hae_tarjous($valittu_tarjous_tunnus) {
    global $kukarow, $yhtiorow;
    
    $query = "  SELECT *
                FROM lasku
                WHERE lasku.yhtio = '{$kukarow['yhtio']}'
                AND tunnus = '{$valittu_tarjous_tunnus}'";
    $result = pupe_query($query);
    
    $tarjous = mysql_fetch_assoc($result);
    $tarjous['tilausrivit'] = hae_tarjouksen_tilausrivit($valittu_tarjous_tunnus);
    
    return $tarjous;
}

function hae_tarjouksen_tilausrivit($valittu_tarjous_tunnus) {
    global $kukarow, $yhtiorow;
    
    $query = "  SELECT *
                FROM tilausrivi
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND otunnus = '{$valittu_tarjous_tunnus}'";
    $result = pupe_query($query);
    
    $tilausrivit = array();
    
    while($tilausrivi = mysql_fetch_assoc($result)) {
        $tilausrivit[] = $tilausrivi;
    }
    
    return $tilausrivit;
}

function echo_tarjouksen_otsikko($tarjous) {
    global $kukarow, $yhtiorow; 
    
    echo "<a href=extranet_tarjoukset.php>".t("Palaa takaisin")."</a>";
    echo "<table>";
    
    echo "<tr>";
    echo "<th>".t("Nimi")."</th>";
    echo "<th>".t("Viimeinen voimassaolopvm")."</th>";
    echo "<tr>";
    echo "<td>";
    echo "{$tarjous['nimi']}";
    echo "</td>";
    echo "<td>";
    echo "{$tarjous['toimaika']}";
    echo "</td>";
    
    echo "</table>";
    echo "<br>";
}

function echo_tarjouksen_tilausrivit($tarjous) {
    global $kukarow, $yhtiorow;
    
    echo "<font class='message'>".t("Tilausrivit")."</font>";
    echo "<br>";
    echo "<br>";
    echo "<br>";
    echo "<form>";
    $header_values = array(
        'tuotenro' => t('Tuotenro'),
        'nimi' => t('Nimi'),
        'kpl' => t('Kpl'),
        'rivihinta' => t('Rivihinta'),
        'alv' => t('Alv'),
    );
    echo_rows_in_table($tarjous['tilausrivit'], $header_values);
    echo "</form>";
}

require ("inc/footer.inc");