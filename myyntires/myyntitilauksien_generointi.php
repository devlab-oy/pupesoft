<?php

require "../inc/parametrit.inc";
require "../inc/pupeExcel.inc";
require "../inc/functions.inc";

$myyntitilaukset = hae_tamanpaivan_webstore_myyntitilaukset();

if(!empty($myyntitilaukset)) {
	$excel_file_path = generoi_excel_tiedosto($myyntitilaukset);

	$excel_file_path = "/tmp/".$excel_file_path;

	laheta_sahkoposti($excel_file_path);
    
    merkkaa_myyntitilaukset_lahetetyksi($myyntitilaukset);
}
else {
	echo t("Myyntitilauksia ei löytynyt tälle päivälle");
}

function hae_tamanpaivan_webstore_myyntitilaukset() {
	global $kukarow;
	
	$now = date('Y-m-d') . ' 00:00:00';

	$query .= "	SELECT lasku.tunnus as 'Sales order numbers',
				lasku.asiakkaan_tilausnumero as 'Customer Order reference',
				lasku.toimaika as 'Requested Ship Date',
				substring(toimitusehto, 1, 3) as 'Incoterms',
				'' as 'Sold To Customer Code',
				lasku.nimi as 'Ship To Customer Name',
				lasku.nimi as 'Ship to Customer Contact Information',
				lasku.toim_osoite as 'Ship To Customer Street',
				'' as 'Ship To Customer House Number',
				lasku.toim_postino as 'Ship To Customer Postal Code',
				lasku.toim_postitp as 'Ship To Customer City',
				lasku.toim_maa as 'Ship to Customer Country',
				'' as 'Ship To Customer Phone Number',
				'' as 'Ship to Customer Email Address',
				tilausrivi.tilaajanrivinro as 'Sales Order Line number',
				tilausrivi.tuoteno as 'Part number',
				tilausrivi.tilkpl as 'Quantity (pieces)',
				'normal' as 'Stock status',
				rivihinta_valuutassa as 'SalesPrice',
				lasku.valkoodi as 'Currency'
				FROM lasku
				JOIN tilausrivi
				ON ( tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus )
				JOIN asiakas
				ON ( asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus AND asiakas.ytunnus = 'WEBSTORE' )
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				AND lasku.luontiaika > '{$now}'
                AND noutaja != 'X'";
	$result = pupe_query($query);

	$myyntilaskut = array();
	while ($myyntilasku = mysql_fetch_assoc($result)) {
		$myyntilaskut[] = $myyntilasku;
	}

	return $myyntilaskut;
}

function laheta_sahkoposti($excel_file_path) {
	global $yhtiorow;

	$email_address = "joonas@devlab.fi";
	$subject = "{$yhtiorow['nimi']} Webstore Sales Orders " . date('Y-m-d');
	$body = "You can find the sales orders as and excel attachment";
	$new_file_name = "SalesOrders.xlsx";
	$ctype = "excel";
	
	$params = array(
		"to"			 => $email_address,
		"subject"		 => $subject,
		"ctype"			 => "html",
		"body"			 => $body,
		"attachements"	 => array()
	);

	$liitetiedosto = array(
		'filename'		 => $excel_file_path,
		'newfilename'	 => $new_file_name,
		'ctype'			 => $ctype,
	);

	$params['attachements'][] = $liitetiedosto;

	pupesoft_sahkoposti($params);
}

function merkkaa_myyntitilaukset_lahetetyksi($myyntitilaukset) {
    global $kukarow;
    $myyntitilaus_tunnukset = '';
    foreach ($myyntitilaukset as $myyntitilaus) {
        $myyntitilaus_tunnukset .= $myyntitilaus['Sales order number Makia'] . ',';
    }
    $myyntitilaus_tunnukset = substr($myyntitilaus_tunnukset, 0, -1);

    $query = "  UPDATE lasku
                SET noutaja = 'X'
                WHERE yhtio = '{$kukarow}'
                AND tunnus IN ({$myyntitilaus_tunnukset})";
    pupe_query($query);
}
?>
