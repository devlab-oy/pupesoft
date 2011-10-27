<?php
define("VERKKOLASKUT_POLKU", "/tmp/verkkolaskut/");
// Mistä lähtien uudet laskut noudetaan, "YYYYMMDDHHMMSS" 
// Tyhjä hakee vain uudet
define("TIMESTAMP", ""); 

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
	die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

// otetaan tietokanta connect
require ("inc/connect.inc");

$client = new SoapClient('https://testing.maventa.com/apis/bravo/wsdl');

// Haetaan api_keyt yhtion_parametrit taulusta
// Kaikki yritykset joilla on api_avain ja ohjelmisto_api_avain kenttää täytettynä. Yrityksen_uuid on vaihtoehtoinen kenttä.
$sql_query = "SELECT maventa_api_avain, maventa_ohjelmisto_api_avain, maventa_yrityksen_uuid, nimi FROM yhtio JOIN yhtion_parametrit USING (yhtio) 
		WHERE maventa_api_avain <> '' AND maventa_ohjelmisto_api_avain <> ''";

$maventa_result = mysql_query($sql_query) or die("Error in query");

if (mysql_num_rows($maventa_result) == 0) {
	echo "Maventaa käyttäviä yrityksiä ei löytynyt.\n";
	exit;
}

while($maventa_keys = mysql_fetch_assoc($maventa_result)) {
	echo $maventa_keys['nimi']."\n";
	//echo "api_avain: ".$maventa_keys['maventa_api_avain']."\n";
	echo "UUID: ".$maventa_keys['maventa_yrityksen_uuid'];
	echo ", API_KEY: ".$maventa_keys['maventa_ohjelmisto_api_avain']."\n";
	
	echo "Haetaan laskuja...\n";

	// Täytetään api_keys
	$api_keys = array();
	$api_keys["user_api_key"] = 	$maventa_keys['maventa_api_avain'];
	$api_keys["vendor_api_key"] = 	$maventa_keys['maventa_ohjelmisto_api_avain'];
	// Vaihtoehtoinen company_uuid
	if (isset($maventa_keys['maventa_yrityksen_uuid'])) {
		$api_keys["company_uuid"] = 	$maventa_keys['maventa_yrityksen_uuid'];
	}

	// Used for getting list of outbound invoices.
	// Haetaan lista kaikista inbound invoiceista, listaa vain _unseen_ laskut!
	$uudet_laskut = $client->invoice_list_inbound($api_keys, TIMESTAMP);

	// Jos uusia laskuja ei löydy
	if (!$uudet_laskut) {
		echo "Ei uusia laskuja!\n";
		exit;
	}

	// Haetaan uudet laskut ja niiden liitteet
	echo "Loytyi ".count($uudet_laskut)." laskua.\n";
	foreach($uudet_laskut as $lasku) {
		//echo "Status: ".$lasku->status." ID: ".$lasku->id." Invoice_nr: ".$lasku->invoice_nr." Date: ".$lasku->date."\n";

		// Haetaan tiedostot ID:n mukaan
		$invoice = $client->inbound_invoice_show($api_keys, $lasku->id, true, "finvoice");

		// Loopataan kaikki liitteet läpi
		foreach($invoice->attachments as $liite) {
			//XML
			// Tiedoston nimeen joku hash ettei tule samoja nimiä.
			if($liite->attachment_type == "FINVOICE") {
				$tiedosto = VERKKOLASKUT_POLKU;
				$tiedosto .= "maventa_".md5(uniqid(mt_rand(), true))."_".$liite->filename;
				$fd = fopen($tiedosto, "w") or die("Tiedostoa ei voitu tallentaa\n");
				fwrite($fd, base64_decode($liite->file));
				fclose($fd);
				echo "Liite '".$tiedosto."' tallennettu!\n";

			}
			// jos muita tiedostotyyppiejä
			//if($liite->attachment_type == "TYYPPI") {}

		}
	}
	
}


?>