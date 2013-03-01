<?php
/*
 * HOW TO CLI:
 * php myyntitilauksien_generointi.php yhtio to@example.com from@example.com
 */

require "../inc/functions.inc";
require "../inc/pupeExcel.inc";

if (php_sapi_name() == 'cli') {
	require "../inc/connect.inc";
	$php_cli = true;
	$yhtio = $argv[1];
	$email = $argv[2];
	$from = $argv[3];
	$pupe_root_polku = dirname(dirname(__FILE__));

	//yhtiötä tai sähköpostia ei ole annettu
	if(empty($yhtio) and empty($email)) {
		$php_cli = false;
	}
}
else {
	require "../inc/parametrit.inc";

	echo "<font class='head'>".t('Keräysaineiston generointi')."</font><hr>";
	
	$php_cli = false;
	$yhtio = '';
	$email = '';
}

if($tee == 'hae_keraysaineisto' or $php_cli == true) {
	$myyntitilaukset = hae_tamanpaivan_webstore_myyntitilaukset($yhtio);
	if(!empty($myyntitilaukset)) {
		$excel_file_path = generoi_excel_tiedosto($myyntitilaukset);

		$excel_file_path = "/tmp/".$excel_file_path;

		//jos ei ajeta cronista
		if($tee == 'hae_keraysaineisto') {
			$email = $vastaanottava_email;
		}

		$email_ok = laheta_sahkoposti($excel_file_path, $email, $yhtio, $from);

		if($email_ok) {
			merkkaa_myyntitilaukset_lahetetyksi($myyntitilaukset, $yhtio);
			echo "<font class='message'>".t("Sähköposti lähetetty onnistuneesti")."</font>";
		}
	}
	else {
		echo t("Myyntitilauksia ei löytynyt tälle päivälle");
	}
}
else {
	echo_kayttoliittyma();
}


function hae_tamanpaivan_webstore_myyntitilaukset($yhtio = '') {
	global $kukarow;

	//jos ajetaan cronista
	if(!empty($yhtio)) {
		$kukarow['yhtio'] = $yhtio;
	}
	
	$now = date('Y-m-d') . ' 00:00:00';

	//for debug
	$now = date('Y-m-d', strtotime($now .' -1 day')) . ' 00:00:00';


	$query .= "	SELECT lasku.tunnus as 'Sales order numbers',
				lasku.asiakkaan_tilausnumero as 'Customer Order reference',
				lasku.toimaika as 'Requested Ship Date',
				substring(lasku.toimitusehto, 1, 3) as 'Incoterms',
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

function laheta_sahkoposti($excel_file_path, $email_address, $yhtio = '', $postittaja = '') {
	global $yhtiorow;

	if($yhtio != '') {
		$yhtiorow['nimi'] = $yhtio;
	}

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

	if(php_sapi_name() == 'cli') {
		$params['yhtio'] = $yhtio;
		$params['postittaja_email'] = $postittaja;
	}

	$liitetiedosto = array(
		'filename'		 => $excel_file_path,
		'newfilename'	 => $new_file_name,
		'ctype'			 => $ctype,
	);

	$params['attachements'][] = $liitetiedosto;

	$email_ok = pupesoft_sahkoposti($params);

	return $email_ok;
}

function merkkaa_myyntitilaukset_lahetetyksi($myyntitilaukset, $yhtio = '') {
    global $kukarow;

	if($yhtio != '') {
		$yhtiorow['yhtio'] = $yhtio;
	}

    $myyntitilaus_tunnukset = '';
    foreach ($myyntitilaukset as $myyntitilaus) {
        $myyntitilaus_tunnukset .= $myyntitilaus['Sales order numbers'] . ',';
    }
    $myyntitilaus_tunnukset = substr($myyntitilaus_tunnukset, 0, -1);

    $query = "  UPDATE lasku
                SET noutaja = 'X'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus IN ({$myyntitilaus_tunnukset})";
    pupe_query($query);
}

function echo_kayttoliittyma() {
	echo "<form method='POST' action=''>";
	echo "<table>";
	echo "<th>".t("Vastaanottava sähköposti")."</th>";
	echo "<td>";
	echo "<input type='hidden' name='tee' value='hae_keraysaineisto' />";
	echo "<input type='text' name='vastaanottava_email' />";
	echo "</td>";
	echo "</table>";
	echo "<br/>";
	echo "<input type='submit' value='".t("Lähetä")."'/>";
	echo "</form>";
}

require '../inc/footer.inc';
?>
