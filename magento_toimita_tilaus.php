<?php

# Otetaan sisään:
# $magento_api_met = Toimitustapa
# $magento_api_rak = Rahtikirjanro
# $magento_api_ord = Asiakkaan_tilausnumero

$magento_api_ord = (int) $magento_api_ord;

if ($magento_api_url != "" and $magento_api_usr != "" and  $magento_api_pas != "" and $magento_api_ord > 0) {

	$proxy = new SoapClient($magento_api_url);
	$sessionId = $proxy->login($magento_api_usr, $magento_api_pas);

	$canShip 	= TRUE;
	$canInvoice = TRUE;
	$magLinkurl = "";
	// Create new shipment
	try {

		if (stripos($magento_api_rak,"JJFI") !== FALSE) {
			$magLinkurl = "Tracking number: ";

			preg_match_all("/JJFI ?[0-9]{6} ?[0-9]{11}/", $magento_api_rak, $match);

			foreach ($match[0] as $nro) {
				$nro = str_replace(" ", "", $nro);
				$magLinkurl .= "<a target=newikkuna href='http://www.verkkoposti.com/e3/TrackinternetServlet?lang=fi&LOTUS_hae=Hae&LOTUS_side=1&LOTUS_trackId={$nro}&LOTUS_hae=Hae'>{$nro}</a><br>";
			}

			$magLinkurl = substr($magLinkurl, 0, -4); // vika br pois
		}

		$comment = "Your order is shipped!<br><br>$magLinkurl";

		$newShipmentId = $proxy->call($sessionId, 'sales_order_shipment.create', array($magento_api_ord, array(), $comment, true, true));
	}
	catch (Exception $e) {
		$canShip = FALSE;

		echo $e->faultstring."\n";
		echo $e->faultcode."\n";
	}

	if ($canShip) {
		// Add tracking
		$newTrackId = $proxy->call($sessionId, 'sales_order_shipment.addTrack', array($newShipmentId, "custom", $magento_api_met, $magento_api_rak));
	}

	// Create new invoice
	try {
		$newInvoiceId = $proxy->call($sessionId, 'sales_order_invoice.create', array($magento_api_ord, array(), 'Invoice Created', false, false));
	}
	catch(Exception $e) {
		$canInvoice = FALSE;

		echo $e->faultstring."\n";
		echo $e->faultcode."\n";
	}

	if ($canInvoice) {
		$proxy->call($sessionId, 'sales_order_invoice.capture', $newInvoiceId);
	}
}
