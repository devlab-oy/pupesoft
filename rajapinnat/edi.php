<?php

class Edi {

	/**
	 * Luo edi tilauksen
	 *
	 * @param  array $order  Tilauksen tiedot ja tilauserivit
	 * @return true/false
	 */
	static function create($order) {

		global $magentoEdiPolku;

		// require 'magento_salasanat.php' muuttujat
		$verkkokauppa_asiakasnro	= "WEBSTORE";
		$rahtikulu_tuoteno			= "Cargo";
		$rahtikulu_nimitys			= "Shipping";

		// Miten storen nimi?
		#$storenimi = (isset($_COOKIE["store_name"])) ? $_COOKIE["store_name"] : "";
		$storenimi = 'default';

	   	//tilauksen otsikko
	   	//return $order->getUpdatedAt();
	   	$edi_order  = "*IS from:721111720-1 to:IKH,ORDERS*id:".$order['increment_id']." version:AFP-1.0 *MS\n";
	   	$edi_order .= "*MS ".$order['increment_id']."\n";
	   	$edi_order .= "*RS OSTOTIL\n";
	   	$edi_order .= "OSTOTIL.OT_NRO:".$order['increment_id']."\n";
		//Yrityksen ovt_tunnus MUISTA MUUTTAA
	   	$edi_order .= "OSTOTIL.OT_TOIMITTAJANRO:".$ovt_tunnus."\n";
		$edi_order .= "OSTOTIL.OT_TILAUSTYYPPI:$pupesoft_tilaustyyppi\n";
		$edi_order .= "OSTOTIL.OT_TILAUSAIKA:\n";
		$edi_order .= "OSTOTIL.OT_KASITTELIJA:\n";
		$edi_order .= "OSTOTIL.OT_TOIMITUSAIKA:\n";
		$edi_order .= "OSTOTIL.OT_TOIMITUSTAPA:".$order['shipping_description']."\n";
		$edi_order .= "OSTOTIL.OT_TOIMITUSEHTO:\n";
		//Onko tilaus maksettu = processing vai jälkvaatimus = pending_cashondelivery_asp
		$edi_order .= "OSTOTIL.OT_MAKSETTU:".$order['status']."\n";
		$edi_order .= "OSTOTIL.OT_MAKSUEHTO:".strip_tags($order['payment']['method'])."\n";
		$edi_order .= "OSTOTIL.OT_VIITTEEMME:\n";
		$edi_order .= "OSTOTIL.OT_VIITTEENNE:$storenimi\n";
		$edi_order .= "OSTOTIL.OT_SUMMA:".$order['grand_total']."\n";
		$edi_order .= "OSTOTIL.OT_VALUUTTAKOODI:".$order['order_currency_code']."\n";
		$edi_order .= "OSTOTIL.OT_KLAUSUULI1:\n";
		$edi_order .= "OSTOTIL.OT_KLAUSUULI2:\n";
		$edi_order .= "OSTOTIL.OT_KULJETUSOHJE:\n";
		$edi_order .= "OSTOTIL.OT_LAHETYSTAPA:\n";
		$edi_order .= "OSTOTIL.OT_VAHVISTUS_FAKSILLA:\n";
		$edi_order .= "OSTOTIL.OT_FAKSI:\n";

		$billingadress = $order['billing_address']['street'];
		$shippingadress = $order['shipping_address']['street'];

		//Asiakkaan ovt_tunnus MUISTA MUUTTAA
		$edi_order .= "OSTOTIL.OT_ASIAKASNRO:".$verkkokauppa_asiakasnro."\n";
		$edi_order .= "OSTOTIL.OT_YRITYS:".$order['billing_address']['company']."\n";
		$edi_order .= "OSTOTIL.OT_KATUOSOITE:".$billingadress."\n";
		$edi_order .= "OSTOTIL.OT_POSTITOIMIPAIKKA:".$order['billing_address']['city']."\n";
		$edi_order .= "OSTOTIL.OT_POSTINRO:".$order['billing_address']['postcode']."\n";
		$edi_order .= "OSTOTIL.OT_YHTEYSHENKILO:".$order['billing_address']['lastname']." ".$order['billing_address']['firstname']."\n";
		$edi_order .= "OSTOTIL.OT_YHTEYSHENKILONPUH:".$order['billing_address']['telephone']."\n";
		$edi_order .= "OSTOTIL.OT_YHTEYSHENKILONFAX:".$order['billing_address']['fax']."\n";
		$edi_order .= "OSTOTIL.OT_MYYNTI_YRITYS:\n";
		$edi_order .= "OSTOTIL.OT_MYYNTI_KATUOSOITE:\n";
		$edi_order .= "OSTOTIL.OT_MYYNTI_POSTITOIMIPAIKKA:\n";
		$edi_order .= "OSTOTIL.OT_MYYNTI_POSTINRO:\n";
		$edi_order .= "OSTOTIL.OT_MYYNTI_MAAKOODI:\n";
		$edi_order .= "OSTOTIL.OT_MYYNTI_YHTEYSHENKILO:\n";
		$edi_order .= "OSTOTIL.OT_MYYNTI_YHTEYSHENKILONPUH:\n";
		$edi_order .= "OSTOTIL.OT_MYYNTI_YHTEYSHENKILONFAX:\n";
		$edi_order .= "OSTOTIL.OT_TOIMITUS_YRITYS:".$order['shipping_address']['company']."\n";
		$edi_order .= "OSTOTIL.OT_TOIMITUS_NIMI:".$order['shipping_address']['lastname']." ".$order['shipping_address']['firstname']."\n";
		$edi_order .= "OSTOTIL.OT_TOIMITUS_KATUOSOITE:".$shippingadress."\n";
		$edi_order .= "OSTOTIL.OT_TOIMITUS_POSTITOIMIPAIKKA:".$order['shipping_address']['city']."\n";
		$edi_order .= "OSTOTIL.OT_TOIMITUS_POSTINRO:".$order['shipping_address']['postcode']."\n";
		$edi_order .= "OSTOTIL.OT_TOIMITUS_MAAKOODI:".$order['shipping_address']['country_id']."\n";
	   	$edi_order .= "*RE OSTOTIL\n";

		#$items = $order->getItemsCollection();

	   	$i = 1;
	   	foreach ($order['items'] as $item) {
	   		$product_id = $item['product_id'];

			if ($item['product_type'] != "configurable") {

				// Tuoteno
				$tuoteno = $item['sku'];

				// Nimitys
				$nimitys = $item['name'];

				// Määrä
				$kpl = $item['qty_ordered']*1;

				// Hinta pitää hakea isältä
				if ($_item = $item['parent_item_id']); # Tää ei palauta parent itemiä?
				else $_item = $item;

				// Veroton yksikköhinta
				$hinta = $_item['price'];

				// Rivin alennusmäärä
				$ale = $_item['discount_amount'];

				// Rivin veronmäärä
				$alveur = $_item['tax_amount'];

				// Rivin veronmäärä ennen alennusta
				$alveur_ea = $_item['base_tax_amount'];

				// Verollinen rivihinta
				$rivihinta_veroll = ($hinta * $kpl) - $ale + $alveur;

				// Veroprossa
				$alvpros = $_item['tax_percent'];

				// Veroton rivihinta
				$rivihinta = round($rivihinta_veroll / (1+($alvpros/100)),2);

				// Aleprossa
				if ($hinta != 0) $alepros = round((1 - $rivihinta / ($hinta*$kpl)) * 100, 2);
				else $alepros = 0;

	   			$edi_order .= "*RS OSTOTILRIV $i\n";
	    		$edi_order .= "OSTOTILRIV.OTR_NRO:".$order['increment_id']."\n";
	    		$edi_order .= "OSTOTILRIV.OTR_RIVINRO:$i\n";
	    		$edi_order .= "OSTOTILRIV.OTR_TOIMITTAJANRO:\n";
	    		$edi_order .= "OSTOTILRIV.OTR_TUOTEKOODI:$tuoteno\n";
	    		$edi_order .= "OSTOTILRIV.OTR_NIMI:$nimitys\n";

				$edi_order .= "OSTOTILRIV.OTR_TILATTUMAARA:$kpl\n";

				// Verottomat hinnat
	    		$edi_order .= "OSTOTILRIV.OTR_RIVISUMMA:$rivihinta\n";
	    		$edi_order .= "OSTOTILRIV.OTR_OSTOHINTA:$hinta\n";
	    		$edi_order .= "OSTOTILRIV.OTR_ALENNUS:$alepros\n";
				$edi_order .= "OSTOTILRIV.OTR_VEROKANTA:$alvpros\n";

				$edi_order .= "OSTOTILRIV.OTR_VIITE:\n";
	    		$edi_order .= "OSTOTILRIV.OTR_OSATOIMITUSKIELTO:\n";
	    		$edi_order .= "OSTOTILRIV.OTR_JALKITOIMITUSKIELTO:\n";
	    		$edi_order .= "OSTOTILRIV.OTR_YKSIKKO:\n";

				#$stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product_id);


	    		#if ($stock->getQty() - $item->getQtyOrdered() <= 0 && $stock->getBackorders() != 0) {
	    		#	$edi_order .= "OSTOTILRIV.OTR_SALLITAANJT:1\n";
	    		#}
	    		#else {
	    			$edi_order .= "OSTOTILRIV.OTR_SALLITAANJT:0\n";
	    		#}

	    		$edi_order .= "*RE  OSTOTILRIV $i\n";
	    		$i++;
			}
		}


		// // Rahtikulu, veroton
		$rahti_veroton = $order['shipping_amount'];

		if ($rahti_veroton != 0) {
			// Rahtikulu, verollinen
			$rahti = $order['shipping_amount'] + $order['shipping_tax_amount'];

			// Rahtin alviprossa
			$rahti_alvpros = round((($rahti / $rahti_veroton) - 1) * 100);

			$edi_order .= "*RS OSTOTILRIV $i\n";
		   	$edi_order .= "OSTOTILRIV.OTR_NRO:".$order['increment_id']."\n";
		   	$edi_order .= "OSTOTILRIV.OTR_RIVINRO:$i\n";
		   	$edi_order .= "OSTOTILRIV.OTR_TOIMITTAJANRO:\n";
		   	$edi_order .= "OSTOTILRIV.OTR_TUOTEKOODI:$rahtikulu_tuoteno\n";
		   	$edi_order .= "OSTOTILRIV.OTR_NIMI:$rahtikulu_nimitys\n";
		   	$edi_order .= "OSTOTILRIV.OTR_TILATTUMAARA:1\n";
		   	$edi_order .= "OSTOTILRIV.OTR_RIVISUMMA:$rahti_veroton\n";
		   	$edi_order .= "OSTOTILRIV.OTR_OSTOHINTA:$rahti_veroton\n";
			$edi_order .= "OSTOTILRIV.OTR_ALENNUS:0\n";
		   	$edi_order .= "OSTOTILRIV.OTR_VEROKANTA:$rahti_alvpros\n";
		   	$edi_order .= "OSTOTILRIV.OTR_VIITE:\n";
		   	$edi_order .= "OSTOTILRIV.OTR_OSATOIMITUSKIELTO:\n";
		   	$edi_order .= "OSTOTILRIV.OTR_JALKITOIMITUSKIELTO:\n";
		   	$edi_order .= "OSTOTILRIV.OTR_YKSIKKO:\n";
		   	$edi_order .= "*RE  OSTOTILRIV $i\n";
		}

	   	$edi_order .= "*ME\n";
	   	$edi_order .= "*IE";

	   	$edi_order = iconv("UTF-8", "ISO-8859-1", $edi_order);

		$filename = $magentoEdiPolku."/magento-order".date("Ymd")."-".md5(uniqid(rand(),true)).".txt";
		file_put_contents($filename, $edi_order);

		return true;
	}
}