<?php
echo "<font class='head'>" . t("Ext-Tarjoukset") . "</font><hr>";
?>

<style>

</style>
<script>

</script>
<?php

$request = array(
    "hakuehto" => $hakuehto,
    "mul_asiakasosasto" => $mul_asiakasosasto,
    "mul_asiakasryhma" => $mul_asiakasryhma,
    "mul_asiakasmyyja" => $mul_asiakasmyyja,
    "ext_asiakas_haku_tehty" => $ext_asiakas_haku_tehty,
);

$tee = 'nayta_asiakas_valinta';

if ($tee == 'nayta_asiakas_valinta') {
    if (isset($request['ext_asiakas_haku_tehty'])) {
        
        $request['asiakkaat'] = hae_asiakkaat_joilla_extranet_kayttajatunnus($request);
        var_dump($request);
    }
    
    echo_asiakas_valinta($request);
} 
else {
    echo_poimitut_asiakkaat($request);
}

require ('inc/footer.inc');
exit;

function echo_asiakas_valinta($request) {

    echo "<div id='asiakasvalinta_wrapper'>";

    echo_asiakas_rajaus($request);
    echo_asiakas_table($request);

    echo "</div>";
}

function echo_asiakas_rajaus($request) {

    global $kukarow, $yhtiorow, $mul_asiakasosasto, $mul_asiakasryhma, $mul_asiakasmyyja;
    echo "<form method='post' action='tilaus_myynti.php'>";
    echo "<input type='hidden' name='tee' value='nayta_asiakas_valinta'>";
    echo "<input type='hidden' name='toim' value='EXTTARJOUS'>";
    echo "<input type='hidden' name='ext_asiakas_haku_tehty' value='1'>";
    echo "<div id='infomessage'>";

    echo "</div>";

    echo "<div id='hakuehdot_wrapper'>";
    $monivalintalaatikot = array("ASIAKASOSASTO", "ASIAKASRYHMA", "ASIAKASMYYJA");
    $monivalintalaatikot_normaali = array();
    
    

    require ("tilauskasittely/monivalintalaatikot.inc");
    
    echo "<input type='text' name='hakuehto' value='{$request['hakuehto']}'>";
    echo "<input type='submit' value='".t("Hae")."'>";
    echo "</div>";
    echo "</form>";
}

function echo_poimitut_asiakkaat($request) {
    echo_asiakas_table($request);
}

function echo_asiakas_table($request) {
    
}

function hae_asiakkaat_joilla_extranet_kayttajatunnus($request) {
    
    global $kukarow, $yhtiorow;
    $where = "AND asiakas.laji NOT IN ( 'P' )";
    
    if (!empty($request['mul_asiakasosasto'])){
        $where .= " AND asiakas.osasto IN ('".  implode("','", $request['mul_asiakasosasto'])."')";
    }
    if (!empty($request['mul_asiakasryhma'])){
        $where .= " AND asiakas.ryhma IN ('".  implode("','", $request['mul_asiakasryhma'])."')";
    }
    if (!empty($request['mul_asiakasmyyja'])){
        $where .= " AND asiakas.myyjanro IN ('".  implode("','", $request['mul_asiakasmyyja'])."')";
    }
    $query	= "	(SELECT asiakas.*
						FROM asiakas
						JOIN yhtio on asiakas.yhtio=yhtio.yhtio
                        JOIN kuka ON ( kuka.yhtio = asiakas.yhtio AND kuka.oletus_asiakas = asiakas.tunnus AND kuka.extranet = 'X' )
						WHERE asiakas.yhtio = '{$kukarow['yhtio']}'
						and REPLACE(REPLACE(asiakas.ytunnus, '-', ''), '+', '') = '".str_replace(array('-','+'), '', $request['hakuehto'])."'
						and asiakas.ytunnus!=''
						$lajilisa $asiakasrajaus
                        {$where})
					UNION
					(SELECT asiakas.*
						FROM asiakas
						JOIN yhtio on asiakas.yhtio=yhtio.yhtio
                        JOIN kuka ON ( kuka.yhtio = asiakas.yhtio AND kuka.oletus_asiakas = asiakas.tunnus AND kuka.extranet = 'X' )
						WHERE asiakas.yhtio = '{$kukarow['yhtio']}'
						and asiakas.asiakasnro = '{$request['hakuehto']}'
						and asiakas.asiakasnro not in ('0','')
						$lajilisa $asiakasrajaus
                        {$where})
					UNION
					(SELECT asiakas.*
						FROM asiakas
						JOIN yhtio on asiakas.yhtio=yhtio.yhtio
                        JOIN kuka ON ( kuka.yhtio = asiakas.yhtio AND kuka.oletus_asiakas = asiakas.tunnus AND kuka.extranet = 'X' )
						WHERE asiakas.yhtio = '{$kukarow['yhtio']}'
						and REPLACE(REPLACE(asiakas.ovttunnus, '-', ''), '+', '') = '".str_replace(array('-','+'), '', $request['hakuehto'])."'
						and asiakas.ovttunnus!=''
						$lajilisa $asiakasrajaus
                        {$where})
					UNION
					(SELECT asiakas.*
						FROM asiakas
						JOIN yhtio on asiakas.yhtio=yhtio.yhtio
                        JOIN kuka ON ( kuka.yhtio = asiakas.yhtio AND kuka.oletus_asiakas = asiakas.tunnus AND kuka.extranet = 'X' )
						WHERE asiakas.yhtio = '{$kukarow['yhtio']}'
						and asiakas.toim_ovttunnus = '{$request['hakuehto']}'
						and asiakas.toim_ovttunnus!=''
						$lajilisa $asiakasrajaus
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
						$lajilisa $asiakasrajaus
                        {$where})
                    UNION
                    (SELECT asiakas.*
                    FROM asiakas
                     JOIN kuka ON ( kuka.yhtio = asiakas.yhtio AND kuka.oletus_asiakas = asiakas.tunnus AND kuka.extranet = 'X' )
                    WHERE asiakas.yhtio = '{$kukarow['yhtio']}'
                    AND asiakas.nimi LIKE '%{$request['hakuehto']}%'
                     {$where})
					ORDER BY nimi";
    $result = pupe_query($query);
    
    $asiakkaat = array();
    while ($asiakas = mysql_fetch_assoc($result)){
        $asiakkaat[] = $asiakas;
    }
    return $asiakkaat;
}