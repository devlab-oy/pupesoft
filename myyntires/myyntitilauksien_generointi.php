<?php

require "../inc/parametrit.inc";
require "../inc/pupeExcel.inc";
require "../inc/functions.inc";

echo "<font class='head'>".t('Myyntitilaus generaattori')."</font><hr>";

$myyntitilaukset = hae_tamanpaivan_webstore_myyntitilaukset();

if(!empty($myyntitilaukset)) {
	$excel_file_path = generoi_excel_tiedosto($myyntitilaukset);

	$excel_file_path = "/tmp/".$excel_file_path;

	laheta_sahkoposti($excel_file_path);
}
else {
	echo "<font class='message'>".t("Myyntitilauksien ei löytynyt tälle päivälle")."</font>";
}

function hae_tamanpaivan_webstore_myyntitilaukset() {
	global $kukarow;
	
	$now = date('Y-m-d') . ' 00:00:00';

	//for debug
	$now = date('Y-m-d', strtotime($now .' -1 day')) . ' 00:00:00';

	$query .= "	SELECT lasku.tunnus as 'Sales order number Makia',
				lasku.asiakkaan_tilausnumero as 'Customer Order reference',
				lasku.toimaika as 'Requested Ship Date',
				lasku.toimitustapa as 'Incoterms???',
				asiakas.tunnus as 'Sold To Customer Code???',
				lasku.nimi as 'Ship To Customer Name',
				lasku.nimi as 'Ship to Customer Contact Information',
				lasku.toim_osoite as 'Ship To Customer Street',
				lasku.toim_osoite as 'Ship To Customer House Number???',
				lasku.toim_postino as 'Ship To Customer Postal Code',
				lasku.toim_postitp as 'Ship To Customer City',
				lasku.toim_maa as 'Ship to Customer Country',
				'' as 'Ship To Customer Phone Number???',
				'' as 'Ship to Customer Email Address???',
				tilausrivi.tilaajanrivinro as 'Sales Order Line number',
				tilausrivi.tuoteno as 'Part number',
				tilausrivi.tilkpl as 'Quantity (pieces)',
				'normal' as 'Stock status???',
				rivihinta_valuutassa as 'SalesPrice',
				lasku.valkoodi as 'Currency'
				FROM lasku
				JOIN tilausrivi
				ON ( tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus )
				JOIN asiakas
				ON ( asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus AND asiakas.ytunnus = 'WEBSTORE' )
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				AND lasku.luontiaika > '{$now}'";
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
	$subject = "Makia Webstore Sales Orders " . date('Y-m-d');
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

function generoi_excel_tiedosto($rivit) {
	$xls = new pupeExcel();
	$rivi = 0;
	$sarake = 0;

	xls_headerit($xls, $rivit, $rivi, $sarake);

	xls_rivit($xls, $rivit, $rivi, $sarake);

	$xls_tiedosto = $xls->close();

	return $xls_tiedosto;
}

function xls_headerit(pupeExcel &$xls, &$rivit, &$rivi, &$sarake) {
	foreach ($rivit[0] as $header_text => $value) {
		kirjoita_solu($xls, $header_text, $rivi, $sarake);
	}
	$rivi++;
	$sarake = 0;
}

function xls_rivit(pupeExcel &$xls, &$rivit, &$rivi, &$sarake) {
	foreach ($rivit as $matkalasku_rivi) {
		foreach ($matkalasku_rivi as $solu) {
			kirjoita_solu($xls, $solu, $rivi, $sarake);
		}
		$rivi++;
		$sarake = 0;
	}
}

function kirjoita_solu(&$xls, $string, &$rivi, &$sarake) {
	//substr($string, 0, 1) != '0' on postinumeroita varten --> postinumerot kirjotetaan stringinä, että leading nollat tulee oikein
	if (is_numeric($string) and substr($string, 0, 1) != '0') {
		$xls->writeNumber($rivi, $sarake, $string);
	}
	else if (valid_date($string) != 0 and valid_date($string) !== false) {
		$xls->writeDate($rivi, $sarake, $string);
	}
	else {
		$xls->write($rivi, $sarake, $string);
	}
	$sarake++;
}

function valid_date($date) {
	//date pitää olla muodossa Y-m-d. lopussa saa olla myös kellonaika
	//preg_match() returns 1 if the pattern matches given subject, 0 if it does not, or FALSE if an error occurred.
	return (preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $date));
}

?>
