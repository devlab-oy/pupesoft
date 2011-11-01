<?php

/* Pupesoft -> UPS datasiirto
 * rahtikirja_ups_siirto.inc
 *************************************************************/

//jos on laitettu kollikenttään 0 niin ei yritetä luoda siirtotiedostoa
if ($kollityht > 0) {

	// katsotaan varaston tulostusalueista mihin pakkaamoon kyseinen tulostusalue kuuluu (pakkaamo ylä tai pakkaamo ala)
	// tämä tarkistus ohjaa ups-muuttujia
	$query = "	SELECT pakkaamo
				FROM varaston_tulostimet
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND varasto = '{$laskurow['varasto']}'
				AND nimi = '{$laskurow['tulostusalue']}'
				LIMIT 1";
	$tulostusalue_check_res = mysql_query($query) or pupe_error($query);
	$tulostusalue_check_row = mysql_fetch_assoc($tulostusalue_check_res);

	$ups_host_x = ($laskurow['pakkaamo'] == 0 or !isset($ups_host[strtolower($tulostusalue_check_row['pakkaamo'])])) ? $ups_host['oletus'] : $ups_host[strtolower($tulostusalue_check_row['pakkaamo'])];
	$ups_user_x = ($laskurow['pakkaamo'] == 0 or !isset($ups_user[strtolower($tulostusalue_check_row['pakkaamo'])])) ? $ups_user['oletus'] : $ups_user[strtolower($tulostusalue_check_row['pakkaamo'])];
	$ups_pass_x = ($laskurow['pakkaamo'] == 0 or !isset($ups_pass[strtolower($tulostusalue_check_row['pakkaamo'])])) ? $ups_pass['oletus'] : $ups_pass[strtolower($tulostusalue_check_row['pakkaamo'])];
	$ups_path_x = ($laskurow['pakkaamo'] == 0 or !isset($ups_path[strtolower($tulostusalue_check_row['pakkaamo'])])) ? $ups_path['oletus'] : $ups_path[strtolower($tulostusalue_check_row['pakkaamo'])];

	if ($ups_host_x != "" and $ups_user_x != "" and $ups_pass_x != "" and $ups_path_x != "") {

		// tarvitaan tilauksien tunnukset muuttujassa $otunnukset
		// tarvitaan tilauksen tunnus muuttujassa $tunnus
		// printterin komento muuttujassa $oslapp
		// $kukarow[yhtio] jostain saadaan yhtio
		// $yhtiorow array josta saada lähettäjän tiedot

		// nyt on kaikki tiedot rahtikirjaa varten haettu..
		//
		// arrayt:
		// toitarow, lotsikot, pakkaus, kilot, kollit, kuutiot, lavametri, vakit
		// $rakir_row:sta löytyy asiakkaan tiedot ja $rivi:stä ytunnus
		//
		// muuttujat:
		// otunnukset, rahdinmaksaja, rahtihinta, pvm, toimitustapa, kolliyht, kilotyht, kuutiotyht, kirjoitin
		// mehto sisältää maksuehdon tiedot
		// jv tapauksissa on myös yhteensa, summa, jvhinta, lasno ja viite muuttujat
		//
		// tulostetaan rahtikirja

		//Näin se oli muuallakin onkai se sitten oikein?
		$rahtikirjanro = $lotsikot[0];

		if ($phpnimi == "rahtikirja_custom.php") {
			$ups_laskurow = $osoitelappurow;
			$rakir_row = $osoitelappurow;
		}

		//$apu_kollityht = $kollityht;

		/******************************************************************************
		Shipment Options
		Valid Values	Description
		{Blank}			Standard Outbound
		RS				Return Services
		EU				European Union
		SC				Single Country
		LT				LTL
		SP				Small Package
		*******************************************************************************
		The Shipment Option code will be included in your <OpenShipment> block.
		You must use one of these codes. If the Shipment Option is blank,
		the shipment will default to Standard Outbound.
		******************************************************************************/
		$shipmentoption = '';
		/*
		<ShipTo></ShipTo>
		<ShipFrom></ShipFrom>
		<Producer></Producer>
		<UltimateConsignee></UltimateConsignee>
		<Importer></Importer>
		<ThirdParty></ThirdParty>
		<ThirdPartyReceiver></ThirdPartyReceiver>
		<ShipmentInformation></ShipmentInformation>
		<Package></Package>
		<InternationalDocumentation></InternationalDocumentation>
		<Goods></Goods>
		*/
		$xmlstring = "<?xml version='1.0' encoding='windows-1252'?>
<OpenShipments xmlns='x-schema:OpenShipments.xdr'>
	<OpenShipment ShipmentOption='$shipmentoption' ProcessStatus=''>
	</OpenShipment>
</OpenShipments>";

		$xml = simplexml_load_string($xmlstring);

		//	Haetaan otsikkotiedot pohjalle
		$query = "	SELECT lasku.*, laskun_lisatiedot.*,
					lasku.tunnus laskutunnus,
					LEFT(lasku.ytunnus, 35) ytunnus,
					LEFT(lasku.nimi, 35) nimi,
					LEFT(lasku.osoite, 35) osoite,
					LEFT(lasku.maa, 30) maa,
					LEFT(lasku.postino, 9) postino,
					LEFT(lasku.postitp, 30) postitp,
					LEFT(asiakas.puhelin, 15) puhelin,
					LEFT(asiakas.gsm, 15) gsm,
					LEFT(asiakas.tyopuhelin, 15) tyopuhelin,
					LEFT(asiakas.fax, 15) fax,
					LEFT(asiakas.email, 50) email,
					LEFT(lasku.yhtio_nimi, 35) yhtio_nimi,
					LEFT(lasku.toimitustapa, 25) toimitustapa
					FROM lasku
					JOIN asiakas ON (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus)
					LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio AND laskun_lisatiedot.otunnus = lasku.tunnus)
					WHERE lasku.yhtio = '{$kukarow['yhtio']}'
					AND lasku.tunnus in ($otunnukset)
					ORDER BY lasku.tunnus limit 1";
		$ups_laskures = mysql_query($query) or pupe_error($query);
		$ups_laskurow = mysql_fetch_array($ups_laskures);

		/*
		R = Required
		O = Optional
		C = Conditional
		*/

		$query = "	SELECT *
					FROM yhteyshenkilo
					WHERE yhtio = '$kukarow[yhtio]'
					AND liitostunnus = '$laskurow[liitostunnus]'
					AND nimi = '$laskurow[tilausyhteyshenkilo]'";
		$yhteyshenkiloresult = mysql_query($query) or pupe_error($query);
		$yhteyshenkiloasiakas = mysql_fetch_array($yhteyshenkiloresult);

		$asiakkaan_sahkopostiosoite = $yhteyshenkiloasiakas['email'] != '' ? $yhteyshenkiloasiakas['email'] : $ups_laskurow['email'];
		$asiakkaan_sahkopostiosoite_nimi = $yhteyshenkiloasiakas['nimi'] != '' ? $yhteyshenkiloasiakas['nimi'] : $ups_laskurow['nimi'];

		// SHIP TO
		$shipto						= $xml->OpenShipment->addChild('ShipTo');
//		$customerid 				= $shipto->addChild('CustomerID', $ups_laskurow['ytunnus']); // max 35 merkkiä, R
		$companyorname 				= $shipto->addChild('CompanyOrName', utf8_encode($ups_laskurow['toim_nimi'])); // max 35 merkkiä, R

		if ($yhteyshenkiloasiakas['nimi'] != '') {
			$attention = $shipto->addChild('Attention', substr(utf8_encode($yhteyshenkiloasiakas['nimi']), 0, 35)); // max 35 merkkiä, R (riippuu maasta)
		}
		elseif ($ups_laskurow['yhteyshenkilo_kaupallinen'] != '') {
			$query = "	SELECT *
						FROM yhteyshenkilo
						WHERE yhteyshenkilo.yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$ups_laskurow['yhteyshenkilo_kaupallinen']}'";
			$yhteyshenkilo_kaupallinen_res = mysql_query($query) or pupe_error($query);
			$yhteyshenkilo_kaupallinen_row = mysql_fetch_assoc($yhteyshenkilo_kaupallinen_res);
			if ($yhteyshenkilo_kaupallinen_row['nimi'] == '') {
				$yhteyshenkilo_kaupallinen_row['nimi'] = '.';
			}
			$attention = $shipto->addChild('Attention', substr(utf8_encode($yhteyshenkilo_kaupallinen_row['nimi']), 0, 35)); // max 35 merkkiä, R (riippuu maasta)
		}
		elseif ($ups_laskurow['yhteyshenkilo_tekninen'] != '') {
			$query = "	SELECT *
						FROM yhteyshenkilo
						WHERE yhteyshenkilo.yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$ups_laskurow['yhteyshenkilo_tekninen']}'";
			$yhteyshenkilo_tekninen_res = mysql_query($query) or pupe_error($query);
			$yhteyshenkilo_tekninen_row = mysql_fetch_assoc($yhteyshenkilo_tekninen_res);
			if ($yhteyshenkilo_tekninen_row['nimi'] == '') {
				$yhteyshenkilo_tekninen_row['nimi'] = '.';
			}
			$attention = $shipto->addChild('Attention', substr(utf8_encode($yhteyshenkilo_tekninen_row['nimi']), 0, 35)); // max 35 merkkiä, R (riippuu maasta)
		}
		else {
			$attention = $shipto->addChild('Attention', '.'); // max 35 merkkiä, R (riippuu maasta)
		}

		$address1					= $shipto->addChild('Address1', utf8_encode($ups_laskurow['toim_osoite'])); // max 35 merkkiä, R
		$countryterritory 			= $shipto->addChild('CountryTerritory', $ups_laskurow['toim_maa']); // max 30 merkkiä, R
		$postalcode					= $shipto->addChild('PostalCode', $ups_laskurow['toim_postino']); // max 9 merkkiä, C (riippuu maasta)
		$cityortown					= $shipto->addChild('CityOrTown', utf8_encode($ups_laskurow['toim_postitp'])); // max 30 merkkiä, R
		$telephone					= $ups_laskurow['puhelin'] != '' ? $shipto->addChild('Telephone', $ups_laskurow['puhelin']) :
										($ups_laskurow['gsm'] != '' ? $shipto->addChild('Telephone', $ups_laskurow['gsm']) :
										($ups_laskurow['tyopuhelin'] != '' ? $shipto->addChild('Telephone', $ups_laskurow['tyopuhelin']) : $shipto->addChild('Telephone', '.'))); // max 15 merkkiä, C
//		$stateprovincecounty 		= $shipto->addChild('StateProvinceCounty', ''); // max 5 merkkiä, C (tarvitaan vain US & CA tapauksissa)
//		$address2					= $shipto->addChild('Address2', ''); // max 35 merkkiä, O
//		$address3					= $shipto->addChild('Address3', ''); // max 35 merkkiä, O
//		$faxnumber					= $shipto->addChild('FaxNumber', $ups_laskurow['fax']); // max 15 merkkiä, O
//		$emailaddress				= $shipto->addChild('EmailAddress', $ups_laskurow['email']); // max 50 merkkiä, O
//		$taxidnumber				= $shipto->addChild('TaxIDNumber', ''); // max 15 merkkiä, O
//		$receiverupsaccountnumber 	= $shipto->addChild('ReceiverUpsAccountNumber', ''); // max 10 merkkiä, O
//		$locationid					= $shipto->addChild('LocationID', ''); // 3 - 10 merkkiä, O
//		$residentialindicator		= $shipto->addChild('ResidentialIndicator', ''); // 1 merkki, O

		// SHIP FROM
		// laitetaan veikkolan varaston tiedot
		$shipfrom					= $xml->OpenShipment->addChild('ShipFrom');
		$customerid 				= $shipfrom->addChild('CustomerID', $yhtiorow['ytunnus']); // max 35 merkkiä, R
		$companyorname 				= $shipfrom->addChild('CompanyOrName', utf8_encode($yhtiorow['nimi'])); // max 35 merkkiä, R
		$attention 					= $shipfrom->addChild('Attention', substr('.', 0, 35)); // max 35 merkkiä, R (riippuu maasta)
		$address1					= $shipfrom->addChild('Address1', "Teollisuustie 8-10"); // max 35 merkkiä, R
		$countryterritory 			= $shipfrom->addChild('CountryTerritory', $yhtiorow['maa']); // max 30 merkkiä, R
		$postalcode					= $shipfrom->addChild('PostalCode', "02880"); // max 9 merkkiä, C (riippuu maasta)
		$cityortown					= $shipfrom->addChild('CityOrTown', "Veikkola"); // max 30 merkkiä, R
		$telephone					= $shipfrom->addChild('Telephone', "020 7188 450"); // max 15 merkkiä, C
//		$address2					= $shipfrom->addChild('Address2', ''); // max 35 merkkiä, O
//		$address3					= $shipfrom->addChild('Address3', ''); // max 35 merkkiä, O
//		$stateprovincecounty 		= $shipfrom->addChild('StateProvinceCounty', ''); // max 5 merkkiä, C (tarvitaan vain US & CA tapauksissa)
//		$faxnumber					= $shipfrom->addChild('FaxNumber', $yhtiorow['fax']); // max 15 merkkiä, O
//		$emailaddress				= $shipfrom->addChild('EmailAddress', $yhtiorow['email']); // max 50 merkkiä, O
//		$taxidnumber				= $shipfrom->addChild('TaxIDNumber', ''); // max 15 merkkiä, O
//		$taxidtype					= $shipfrom->addChild('TaxIDType', ''); // max 15 merkkiä, O
//		$upsaccountnumber			= $shipfrom->addChild('UpsAccountNumber', ''); // 6 tai 10 merkkiä, C
//		$residentialindicator		= $shipfrom->addChild('ResidentialIndicator', ''); // 1 merkki, O

		/*
		// PRODUCER
		$producer					= $xml->OpenShipment->addChild('Producer');
		$customerid 				= $producer->addChild('CustomerID', ''); // max 35 merkkiä, R
		$companyorname 				= $producer->addChild('CompanyOrName', ''); // max 35 merkkiä, R
		$attention 					= $producer->addChild('Attention', ''); // max 35 merkkiä, R (riippuu maasta)
		$address1					= $producer->addChild('Address1', ''); // max 35 merkkiä, R
		$address2					= $producer->addChild('Address2', ''); // max 35 merkkiä, O
		$address3					= $producer->addChild('Address3', ''); // max 35 merkkiä, O
		$countryterritory 			= $producer->addChild('CountryTerritory', ''); // max 30 merkkiä, R
		$postalcode					= $producer->addChild('PostalCode', ''); // max 9 merkkiä, C (riippuu maasta)
		$cityortown					= $producer->addChild('CityOrTown', ''); // max 30 merkkiä, R
		$stateprovincecounty 		= $producer->addChild('StateProvinceCounty', ''); // max 5 merkkiä, C (tarvitaan vain US & CA tapauksissa)
		$telephone					= $producer->addChild('Telephone', ''); // max 15 merkkiä, C
		$taxidnumber				= $producer->addChild('TaxIDNumber', ''); // max 15 merkkiä, O

		// ULTIMATE CONSIGNEE
		$ultimateconsignee			= $xml->OpenShipment->addChild('UltimateConsignee');
		$customerid 				= $ultimateconsignee->addChild('CustomerID', ''); // max 35 merkkiä, R
		$companyorname 				= $ultimateconsignee->addChild('CompanyOrName', ''); // max 35 merkkiä, R
		$attention 					= $ultimateconsignee->addChild('Attention', ''); // max 35 merkkiä, R (riippuu maasta)
		$address1					= $ultimateconsignee->addChild('Address1', ''); // max 35 merkkiä, R
		$address2					= $ultimateconsignee->addChild('Address2', ''); // max 35 merkkiä, O
		$address3					= $ultimateconsignee->addChild('Address3', ''); // max 35 merkkiä, O
		$countryterritory 			= $ultimateconsignee->addChild('CountryTerritory', ''); // max 30 merkkiä, R
		$postalcode					= $ultimateconsignee->addChild('PostalCode', ''); // max 9 merkkiä, C (riippuu maasta)
		$cityortown					= $ultimateconsignee->addChild('CityOrTown', ''); // max 30 merkkiä, R
		$stateprovincecounty 		= $ultimateconsignee->addChild('StateProvinceCounty', ''); // max 5 merkkiä, C (tarvitaan vain US & CA tapauksissa)
		$telephone					= $ultimateconsignee->addChild('Telephone', ''); // max 15 merkkiä, C
		$taxidnumber				= $ultimateconsignee->addChild('TaxIDNumber', ''); // max 15 merkkiä, C

		// IMPORTER
		$importer					= $xml->OpenShipment->addChild('Importer');
		$customerid 				= $importer->addChild('CustomerID', ''); // max 35 merkkiä, R
		$companyorname 				= $importer->addChild('CompanyOrName', ''); // max 35 merkkiä, R
		$attention 					= $importer->addChild('Attention', ''); // max 35 merkkiä, R (riippuu maasta)
		$address1					= $importer->addChild('Address1', ''); // max 35 merkkiä, R
		$address2					= $importer->addChild('Address2', ''); // max 35 merkkiä, O
		$address3					= $importer->addChild('Address3', ''); // max 35 merkkiä, O
		$countryterritory 			= $importer->addChild('CountryTerritory', ''); // max 30 merkkiä, R
		$postalcode					= $importer->addChild('PostalCode', ''); // max 9 merkkiä, C (riippuu maasta)
		$cityortown					= $importer->addChild('CityOrTown', ''); // max 30 merkkiä, R
		$stateprovincecounty 		= $importer->addChild('StateProvinceCounty', ''); // max 5 merkkiä, C (tarvitaan vain US & CA tapauksissa)
		$telephone					= $importer->addChild('Telephone', ''); // max 15 merkkiä, C
		$taxidnumber				= $importer->addChild('TaxIDNumber', ''); // max 15 merkkiä, C
		$upsaccountnumber			= $importer->addChild('UpsAccountNumber', ''); // 6 tai 10 merkkiä, C

		// THIRD PARTY
		$thirdparty					= $xml->OpenShipment->addChild('ThirdParty');
		$customerid 				= $thirdparty->addChild('CustomerID', ''); // max 35 merkkiä, R
		$companyorname 				= $thirdparty->addChild('CompanyOrName', ''); // max 35 merkkiä, R
		$attention 					= $thirdparty->addChild('Attention', ''); // max 35 merkkiä, R (riippuu maasta)
		$address1					= $thirdparty->addChild('Address1', ''); // max 35 merkkiä, R
		$address2					= $thirdparty->addChild('Address2', ''); // max 35 merkkiä, O
		$address3					= $thirdparty->addChild('Address3', ''); // max 35 merkkiä, O
		$countryterritory 			= $thirdparty->addChild('CountryTerritory', ''); // max 50 merkkiä, R
		$postalcode					= $thirdparty->addChild('PostalCode', ''); // max 9 merkkiä, C (riippuu maasta)
		$cityortown					= $thirdparty->addChild('CityOrTown', ''); // max 30 merkkiä, R
		$stateprovincecounty 		= $thirdparty->addChild('StateProvinceCounty', ''); // max 5 merkkiä, C (tarvitaan vain US & CA tapauksissa)
		$residentialindicator		= $thirdparty->addChild('ResidentialIndicator', ''); // 1 merkki, O
		$telephone					= $thirdparty->addChild('Telephone', ''); // max 15 merkkiä, C
		$faxnumber					= $thirdparty->addChild('FaxNumber', ''); // max 15 merkkiä, O
		$taxidnumber				= $thirdparty->addChild('TaxIDNumber', ''); // max 15 merkkiä, C
		$upsaccountnumber			= $thirdparty->addChild('UpsAccountNumber', ''); // 6 tai 10 merkkiä, C

		// THIRD PARTY RECEIVER
		$thirdpartyreceiver			= $xml->OpenShipment->addChild('ThirdPartyReceiver');
		$customerid 				= $thirdpartyreceiver->addChild('CustomerID', ''); // max 30 merkkiä, R
		$companyorname 				= $thirdpartyreceiver->addChild('CompanyOrName', ''); // max 35 merkkiä, R
		$attention 					= $thirdpartyreceiver->addChild('Attention', ''); // max 35 merkkiä, R (riippuu maasta)
		$address1					= $thirdpartyreceiver->addChild('Address1', ''); // max 35 merkkiä, R
		$address2					= $thirdpartyreceiver->addChild('Address2', ''); // max 35 merkkiä, O
		$address3					= $thirdpartyreceiver->addChild('Address3', ''); // max 35 merkkiä, O
		$countryterritory 			= $thirdpartyreceiver->addChild('CountryTerritory', ''); // max 50 merkkiä, R
		$postalcode					= $thirdpartyreceiver->addChild('PostalCode', ''); // max 9 merkkiä, C (riippuu maasta)
		$cityortown					= $thirdpartyreceiver->addChild('CityOrTown', ''); // max 30 merkkiä, R
		$stateprovincecounty 		= $thirdpartyreceiver->addChild('StateProvinceCounty', ''); // max 5 merkkiä, C (tarvitaan vain US & CA tapauksissa)
		$telephone					= $thirdpartyreceiver->addChild('Telephone', ''); // max 15 merkkiä, C
		$faxnumber					= $thirdpartyreceiver->addChild('FaxNumber', ''); // max 15 merkkiä, O
		$upsaccountnumber			= $thirdpartyreceiver->addChild('UpsAccountNumber', ''); // 6 tai 10 merkkiä, C
		*/

		$query ="	SELECT merahti,
					sum(kollit) kollit,
					sum(kilot) kilot,
					sum(kuutiot) kuutiot,
					sum(lavametri) lavametri,
					min(pakkauskuvaus) pakkauskuvaus,
					min(pakkauskuvaustark) pakkauskuvaustark
					from rahtikirjat use index (otsikko_index)
					where yhtio = '$kukarow[yhtio]'
					and otsikkonro = '$ups_laskurow[laskutunnus]'
					and rahtikirjanro = '$rahtikirjanro'
					and pakkaus = '$pakkaus[0]'
					GROUP BY 1
					ORDER BY 1";
		//and pakkauskuvaus = '$row[selitetark]'";
		$ups_rakires = mysql_query($query) or pupe_error($query);
		$ups_rakirow = mysql_fetch_array($ups_rakires);

		if ($ups_rakirow['kollit'] > 999) {
			die("Liikaa kolleja! (max 999 kpl)"); // kollit ei saa olla yli 3 numeroinen luku...
		}





		// SHIPMENT INFORMATION
		// ******************************************************************************
		// ******************************************************************************
		$shipmentinformation 	= $xml->OpenShipment->addChild('ShipmentInformation');
		$voidindicator 			= $shipmentinformation->addChild('VoidIndicator', ''); // 1 merkki

		/******************************************************************************
		Service Type
		Valid Values	Description
		EP				Express Plus
		XDM				Express Early AM
		ES				Express
		EX				Expedited
		3DS				3 Day Select to the United States
		ST				Standard to the United States
		ST				Standard
		EP				Worldwide Express Plus
		ES				Worldwide Express
		EX				Worldwide Expedited
		SV				Worldwide Saver
		******************************************************************************/
		// toimitustavat tulee olla mallia 'UPS EP'
		list($ups, $st) = explode(' ', $ups_laskurow['toimitustapa']);
		$servicetype = $shipmentinformation->addChild('ServiceType', strtoupper($st)); // max 25 merkkiä, R

		/******************************************************************************
		Package Type
		Valid Values	Description
		CP				Package
		EE				UPS Express Envelope
		PK				UPS Express Pak
		EB				UPS Express Box
		TB				UPS Express Tube
		10				UPS 10 KG Box
		25				UPS 25 KG Box
		30				Pallet (UK / PL only)
		******************************************************************************/
		$packagetype					= $shipmentinformation->addChild('PackageType', $pakkaus[0]); // max 2 merkkiä, R
		$numberofpackages				= $shipmentinformation->addChild('NumberOfPackages', $ups_rakirow['kollit']); // max	3 merkkiä, R
		$shipmentactualweight			= $shipmentinformation->addChild('ShipmentActualWeight', round($ups_rakirow['kilot'], 1)); // max 12,1 merkkiä, R  // Unless it is an ENVELOP
		$shipmentdimensionalweight 		= $shipmentinformation->addChild('ShipmentDimensionalWeight', ''); // max 12,1 merkkiä, O
		$descriptionofgoods				= $shipmentinformation->addChild('DescriptionOfGoods', substr($ups_rakirow['pakkauskuvaustark'], 0, 50)); // max 50 merkkiä, C
		$reference1						= $shipmentinformation->addChild('Reference1', $ups_laskurow['laskutunnus']); // max 35 merkkiä, O
		$reference2						= $shipmentinformation->addChild('Reference2', ''); // max 35 merkkiä, O
		$reference3						= $shipmentinformation->addChild('Reference3', ''); // max 35 merkkiä, O
		$reference4						= $shipmentinformation->addChild('Reference4', ''); // max 35 merkkiä, O
		$reference5						= $shipmentinformation->addChild('Reference5', ''); // max 35 merkkiä, O

		// Note: The two fields - Documents Only and Goods Not In Free Circulation - are mutually exclusive;
		// That is, if you select one, you cannot select the other.
		// However, both fields can be deselected (contain a value of '0')

		$documentonly					= $shipmentinformation->addChild('DocumentOnly', ''); // 1 = select document only, 0 = deselect document only, C   // International Shipment
//		$goodsnotinfreecirculation		= $shipmentinformation->addChild('GoodsNotInFreeCirculation', ''); // 1 merkki, C
//		$specialinstructionforshipment	= $shipmentinformation->addChild('SpecialInstructionForShipment', ''); // max 69 merkkiä, R
		$shippernumber					= $shipmentinformation->addChild('ShipperNumber', ''); // 6 tai 10 merkkiä, R

		/******************************************************************************
		Billing Options
		Valid Values	Description
		PP				Bill Shipper's UPS Account
		TP				Bill Third Party
		TF				Bill Third Party: Freight, Duty, Taxes
		FB				FOB
		CF				C&F
		BR				Bill Receiver
		DP				Delivery Duty Paid, Tax Unpaid
		BD				Delivery Duty, Tax and Shipping Charges Paid by Shipper
		CB				Consignee
		*******************************************************************************
		LTL	LTL shipments and consolidated movements are always Domestic or intra-EU with no GNIC.
		LTL will allow all billing options except Consignee billing.

		WorldEase	WorldEase will allow all billing options except Consignee billing.
		******************************************************************************/

//			$billingoption			= $shipmentinformation->addChild('BillingOption', 'PP'); // max 3 merkkiä, R

		/******************************************************************************
		Bill Transportation To
		Valid Values	Description
		SHP				Shipper
		REC				Receiver
		TP				Third Party
		*******************************************************************************
		WorldEase	Bill Transportation to indicates the party who paid for Master Shipment
		******************************************************************************/
		// rahtikirjan merahti == '' --> Käytetään vastaanottajan rahtisopimusnumeroa, K = Käytetään lähettäjän rahtisopimusnumeroa
		$rahdinmaksaja 			= $ups_rakirow['merahti'] == 'K' ? 'SHP' : 'REC';
		$billtransportationto	= $shipmentinformation->addChild('BillTransportationTo', "$rahdinmaksaja"); // max 3 merkkiä, C

		/******************************************************************************
		Bill Duty & Tax To
		Valid Values	Description
		SHP				Shipper
		REC				Receiver
		TP				Third Party
		*******************************************************************************
		WorldWase	Bill Duty and Tax To indicates the party who paid for the Duty and
		Tax of the Master Shipment.
		******************************************************************************/
		// jos vienti != '' --> vastaanottaja maksaa veron, muuten lähettäjä
//		$vienninmaksaja 	= $ups_laskurow['vienti'] == '' ? 'SHP' : 'REC';
		$vienninmaksaja		= 'REC'; // pakko kovakoodaa
		$billdutytaxto		= $shipmentinformation->addChild('BillDutyTaxTo', "$vienninmaksaja");

		/******************************************************************************
		Split Duty & Tax To
		Valid Values	Description
		SHP				Shipper
		REC				Receiver
		TP				Third Party
		*******************************************************************************
		WorldEase	When Split Duty and Tax is selected for Master Shipment, WS 10.0
		will translate the code and print the label Bill Transportation and Duty Tax To.
		******************************************************************************/
//		$splitdutyandtax	= $shipmentinformation->addChild('SplitDutyAndTax', ''); // 1 merkki, C
//		$saturdaydelivery	= $shipmentinformation->addChild('SaturdayDelivery', ''); // max 2 merkkiä, O    // Note: Leave out if this accessorial is not used.
//		$saturdaypickup		= $shipmentinformation->addChild('SaturdayPickUp', ''); // max 2 merkkiä, O

		// Declared Value
//		$declaredvalue	= $shipmentinformation->addChild('DeclaredValue');
//		$amount			= $declaredvalue->addChild('Amount', ''); // max 9,2 merkkiä, O
//		$shipperpaid	= $declaredvalue->addChild('ShipperPaid', ''); // 1 merkki, O

//		$merchandisedescriptionforpackage = $shipmentinformation->addChild('MerchandiseDescriptionForPackage', ''); // max 35 merkkiä, O

		// Additional Handling
//		$additionalhandling 		= $shipmentinformation->addChild('AdditionalHandling');
//		$numberofadditionalhandling = $additionalhandling->addChild('NumberOfAdditionalHandling', ''); // max 4 merkkiä, O

		// COD (if selected, all elements are required)
//		$cod 									= $shipmentinformation->addChild('COD');
//		$cashonly								= $cod->addChild('CashOnly', ''); // 1 merkki, R
//		$cashiercheckormoneyorderonlyindicator 	= $cod->addChild('CashierCheckorMoneyOrderOnlyIndicator', ''); // 1 merkki, R
//		$addshippingchargestocodindicator 		= $cod->addChild('AddShippingChargesToCODIndicator', ''); // 1 merkki, R
//		$amount 								= $cod->addChild('Amount', ''); // max 9,2 merkkiä, R
//		$currency 								= $cod->addChild('Currency', ''); // max 3 merkkiä, R

//		$returnofdocument = $shipmentinformation->addChild('ReturnOfDocument', ''); // 1 merkki, C  // poland only

	if ($asiakkaan_sahkopostiosoite != '') {
		// QVN Option
		$qvnoption							= $shipmentinformation->addChild('QVNOption');

		// vastaanottaja
		$qvnrecipientandnotificationtypes	= $qvnoption->addChild('QVNRecipientAndNotificationTypes');
		$companyorname						= $qvnrecipientandnotificationtypes->addChild('CompanyOrName', ''); // WS 10.0 will populate ShipTo information
		$contactname						= $qvnrecipientandnotificationtypes->addChild('ContactName', utf8_encode($asiakkaan_sahkopostiosoite_nimi));
		$emailaddress						= $qvnrecipientandnotificationtypes->addChild('EMailAddress', utf8_encode($asiakkaan_sahkopostiosoite));
		$ship								= $qvnrecipientandnotificationtypes->addChild('Ship', 'Y'); // lähetetään meili kun paketti lähtee
		$exception							= $qvnrecipientandnotificationtypes->addChild('Exception', 'N'); // ??
		$delivery							= $qvnrecipientandnotificationtypes->addChild('Delivery', 'N'); // ??

		/*
		// lähettäjä
		$qvnrecipientandnotificationtypes2 	= $qvnoption->addChild('QVNRecipientAndNotificationTypes');
		$companyorname						= $qvnrecipientandnotificationtypes2->addChild('CompanyOrName', $ups_laskurow['yhtio_nimi']);
		$contactname						= $qvnrecipientandnotificationtypes2->addChild('ContactName', $yhtiorow['postittaja_email']);
		$emailaddress						= $qvnrecipientandnotificationtypes2->addChild('EMailAddress', $yhtiorow['postittaja_email']);
		$ship								= $qvnrecipientandnotificationtypes2->addChild('Ship', '');
		$exception							= $qvnrecipientandnotificationtypes2->addChild('Exception', '');
		$delivery							= $qvnrecipientandnotificationtypes2->addChild('Delivery', '');

		$shipfromcompanyorname				= $qvnoption->addChild('ShipFromCompanyOrName', $ups_laskurow['yhtio_nimi']);
		$failedemailaddress					= $qvnoption->addChild('FailedEMailAddress', substr($yhtiorow['postittaja_email'], 0, 50));
		*/


		/******************************************************************************
		Subject Line Options
		Valid Values	Description
		0				1Z Tracking Number
		1				Reference 1
		2				Reference 2
		******************************************************************************/
		$subjectline						= $qvnoption->addChild('SubjectLine', ''); // max 75 merkkiä, R
		$memo								= $qvnoption->addChild('Memo', substr('', 0, 150));
	}

		/*
		// Special Commodities (if selected, you must choose at least one element)
		$specialcommodities 	= $shipmentinformation->addChild('SpecialCommodities');
		$commoditiesdetails 	= $specialcommodities->addChild('CommoditiesDetails', ''); // max 4 merkkiä
		$iscalcoholicbeverages 	= $specialcommodities->addChild('ISC-AlcoholicBeverages', ''); // 1 merkki
		$iscdiagnosticspecimens = $specialcommodities->addChild('ISC-DiagnosticSpecimens', ''); // 1 merkki
		$iscperishables 		= $specialcommodities->addChild('ISC-Perishables', ''); // 1 merkki
		$iscplants 				= $specialcommodities->addChild('ISC-Plants', ''); // 1 merkki
		$iscseeds 				= $specialcommodities->addChild('ISC-Seeds', ''); // 1 merkki
		$iscspecialexception 	= $specialcommodities->addChild('ISC-SpecialException', ''); // 1 merkki
		$isctobacco 			= $specialcommodities->addChild('ISC-Tobacco', ''); // 1 merkki
		*/

		/*
		// Delivery Confirmation (if selected, you must choose at least one element)
		$deliveryconfirmation 	= $shipmentinformation->addChild('DeliveryConfirmation');
		$signaturerequired 		= $deliveryconfirmation->addChild('SignatureRequired', ''); // 1 merkki
		$adultsignaturerequired = $deliveryconfirmation->addChild('AdultSignatureRequired', ''); // 1 merkki

		$userdefinedid = $shipmentinformation->addChild('UserDefinedID', ''); // O
		*/
		/******************************************************************************
		Return Services
		Valid Values	Description
		RS1				UPS 1 Attempt Returns Plus
		RS3				UPS 3 Attempts Returns Plus
		PRL				UPS Print Return Label
		PNM				UPS Prints and Posts Return Label
		ERL				UPS Electronic Return Label
		******************************************************************************/
//		$returnservice 					= $shipmentinformation->addChild('ReturnService'); // if selected, you must choose at least one element and place a value in OPTIONS
//		$options 						= $returnservice->addChild('Options', ''); // max 3 merkkiä, R

		/******************************************************************************
		Print Address Only Invoice
		Valid Values	Description
		1				Print invoices according to User defaults
		2				Print "Address Only Invoice"
		<blank>			Print invoices according to User defaults if invoice information is provided; otherwise, do not print invoices
		******************************************************************************/
//		$printaddressonlyinvoice 		= $returnservice->addChild('PrintAddressOnlyInvoice', ''); // 1 merkki, O
//		$sendercompanyname 				= $returnservice->addChild('SenderCompanyName', ''); // max 35 merkkiä, O
//		$recipientemailaddress 			= $returnservice->addChild('RecipientEmailAddress', ''); // max 50 merkkiä, O
//		$failureofdeliveryemailaddress 	= $returnservice->addChild('FailureOfDeliveryEmailAddress', ''); // max 50 merkkiä, O
//
		/******************************************************************************
		Instruction and Receipt Language
		Valid Values	 Description
		EN				 English
		FR				 French
		DE				 German
		IT				 Italian
		NO				 Norwegian
		PT				 Portuguese
		ES				 Spanish
		DK				 Danish
		FI				 Finnish
		NL				 Dutch
		SE				 Swedish
		******************************************************************************/
		/*
		$instructionandreceiptlanguage 	= $returnservice->addChild('InstructionAndReceiptLanguage', ''); // max 2 merkkiä, C
		$merchandisedescofpackage 		= $returnservice->addChild('MerchandiseDescOfPackage', ''); // max 150 merkkiä, R

		// Notify Before Del Option
		$notifybeforedeloption 	= $shipmentinformation->addChild('NotifyBeforeDelOption'); // if selected, all elements are required
		$contactperson 			= $notifybeforedeloption->addChild('ContactPerson', ''); // max 35 merkkiä, R
		$phone 					= $notifybeforedeloption->addChild('Phone', ''); // max 15 merkkiä, R

		// Handling Charge Option
		$handlingchargeoption 			= $shipmentinformation->addChild('HandlingChargeOption');
		$handlingchargetype 			= $handlingchargeoption->addChild('HandlingChargeType', ''); // 1 merkki, O
		$hcamountincreasedecreasetype 	= $handlingchargeoption->addChild('HCAmountIncreaseDecreaseType', ''); // 1 merkki, O
		$handlingchargeflatrate 		= $handlingchargeoption->addChild('HandlingChargeFlatRate', ''); // max 4 merkkiä, O
		$handlingchargepercentage 		= $handlingchargeoption->addChild('HandlingChargePercentage', ''); // max 4 merkkiä, O

		$fooditems 						= $shipmentinformation->addChild('FoodItems', ''); // O
		$hundredweightpricingapplied 	= $shipmentinformation->addChild('HundredWeightPricingApplied', ''); // 1 merkki, O
		$usi 							= $shipmentinformation->addChild('USI', ''); // max 50 merkkiä, R (required if Trade Direct)
		$subpronumber 					= $shipmentinformation->addChild('SubProNumber', '');  // max 19 merkkiä, O
		$length 						= $shipmentinformation->addChild('Length', ''); // max 4 merkkiä, O
		$width 							= $shipmentinformation->addChild('Width', ''); // max 4 merkkiä, O
		$height 						= $shipmentinformation->addChild('Height', ''); // max 4 merkkiä, O
		$additionaldocuments 			= $shipmentinformation->addChild('AdditionalDocuments', ''); // 1 merkki, O
		$proactiveresponseoption 		= $shipmentinformation->addChild('ProactiveResponseOption', ''); // 1 merkki, O
		*/




		// Package
		// ******************************************************************************
		// ******************************************************************************
		/*
		$package					= $xml->OpenShipment->addChild('Package');
		$packagetype 				= $package->addChild('PackageType', ''); // max 35 merkkiä, R
		$weight 					= $package->addChild('Weight', ''); // max 4 merkkiä, O
		$trackingnumber 			= $package->addChild('TrackingNumber', ''); // O
		$largepackageindicator 		= $package->addChild('LargePackageIndicator', ''); // max 10 merkkiä, O
		$reference1 				= $package->addChild('Reference1', ''); // max 35 merkkiä, O
		$reference2 				= $package->addChild('Reference2', ''); // max 35 merkkiä, O
		$reference3 				= $package->addChild('Reference3', ''); // max 35 merkkiä, O
		$reference4 				= $package->addChild('Reference4', ''); // max 35 merkkiä, O
		$reference5 				= $package->addChild('Reference5', ''); // max 35 merkkiä, O
		$additionalhandlingoption 	= $package->addChild('AdditionalHandlingOption', ''); // 1 merkki, O
		$shipperreleaseoption 		= $package->addChild('ShipperReleaseOption', '');  // 1 merkki, O

		// COD
		$cod 									= $package->addChild('COD'); // if selected, all elements are required
		$cashonly 								= $cod->addChild('CashOnly', ''); // 1 merkki, R
		$cashiercheckormoneyorderonlyindicator 	= $cod->addChild('CashierCheckorMoneyOrderOnlyIndicator', ''); // 1 merkki, R
		$addshippingchargestocodindicator 		= $cod->addChild('AddShippingChargesToCODIndicator', ''); // 1 merkki, R
		$amount 								= $cod->addChild('Amount', ''); // max 9,2 merkkiä, R
		$currency 								= $cod->addChild('Currency', ''); // max 3 merkkiä, R

		// Declared Value
		$declaredvalue	= $package->addChild('DeclaredValue'); // if selected, you must enter an AMOUNT
		$amount			= $declaredvalue->addChild('Amount', ''); // max 9,2 merkkiä, R
		$shipperpaid	= $declaredvalue->addChild('ShipperPaid', ''); // 1 merkki, O

		// Delivery Confirmation
		$deliveryconfirmation 	= $package->addChild('DeliveryConfirmation'); // if selected, you must choose at least one element
		$signaturerequired 		= $deliveryconfirmation->addChild('SignatureRequired', ''); // 1 merkki
		$adultsignaturerequired = $deliveryconfirmation->addChild('AdultSignatureRequired', ''); // 1 merkki

		// QVN Or Return Notification Option
		$qvnorreturnnotificationoption 		= $package->addChild('QVNOrReturnNotificationOption');
		$qvnrecipientandnotificationtypes 	= $qvnorreturnnotificationoption->addChild('QVNRecipientAndNotificationTypes');
		$companyorname 						= $qvnrecipientandnotificationtypes->addChild('CompanyOrName', ''); // 1 merkki
		$contactname 						= $qvnrecipientandnotificationtypes->addChild('ContactName', ''); // max 35 merkkiä
		$emailaddress 						= $qvnrecipientandnotificationtypes->addChild('EmailAddress', ''); // max 50 merkkiä, R
		$ship 								= $qvnrecipientandnotificationtypes->addChild('Ship', ''); // 1 merkki
		$exception 							= $qvnrecipientandnotificationtypes->addChild('Recipient', ''); // 1 merkki
		$delivery 							= $qvnrecipientandnotificationtypes->addChild('Delivery', ''); // 1 merkki
		$shipfromcompanyorname				= $qvnorreturnnotificationoption->addChild('ShipFromCompanyOrName', ''); // max 35 merkkiä
		$failedemailaddress					= $qvnorreturnnotificationoption->addChild('FailedEmailAddress', ''); // max 50 merkkiä
		*/

		/******************************************************************************
		Subject Line Options
		Valid Values	Description
		0				1Z Tracking Number
		1				Reference 1
		2				Reference 2
		******************************************************************************/
		/*
		$subjectline						= $qvnorreturnnotificationoption->addChild('SubjectLine', ''); // max 75 merkkiä, R
		$memo								= $qvnorreturnnotificationoption->addChild('Memo', ''); // max 150 merkkiä

		// Verbal Confirmation Option
		$verbalconfirmationoption 		= $package->addChild('VerbalConfirmationOption'); // if selected, all elements are required
		$verbalconfirmationcontactname 	= $verbalconfirmationoption->addChild('VerbalConfirmationContactName', ''); // max 35 merkkiä
		$verbalconfirmationtelephone 	= $verbalconfirmationoption->addChild('VerbalConfirmationTelephone', ''); // max 15 merkkiä

		$dangerousgoodsoption 	= $package->addChild('DangerousGoodsOption', ''); // 1 merkki, O
		$length					= $package->addChild('Length', ''); // max 4 merkkiä, O
		$width 					= $package->addChild('Width', ''); // max 4 merkkiä, O
		$height 				= $package->addChild('Height', ''); // max 4 merkkiä, O
		$merchandisedescription = $package->addChild('MerchandiseDescription', ''); // max 105 merkkiä, R

		// Flexible Parcel Insurance Option
		$flexibleparcelinsuranceoption 	= $package->addChild('FlexibleParcelInsuranceOption');
		$flexibleparcelinsurancetype 	= $flexibleparcelinsuranceoption->addChild('FlexibleParcelInsuranceType', ''); // max 20 merkkiä, R
		$flexibleinsurancevalue 		= $flexibleparcelinsuranceoption->addChild('FlexibleInsuranceValue', ''); // max 4 merkkiä, R

		$proactiveresponseoption = $package->addChild('ProactiveResponseOption', ''); // 1 merkki, O

		// Dry Ice Option
		$dryiceoption 			= $package->addChild('DryIceOption'); // 1 merkki, O
		$weight 				= $dryiceoption->addChild('Weight', ''); // max 5 merkkiä, O
		*/

		/******************************************************************************
		Unit of Measures
		Valid Values	Description
		BG				Bag
		BA				Barrel
		BT				Bolt
		BX				Box
		BH				Bunch
		BE				Bundle
		BU				Butt
		CI				Canister
		CM				Centimeter
		CT				Carton
		CS				Case
		CON				Container
		CR				Crate
		CY				Cylinder
		DOZ				Dozen
		EA				Each
		EN				Envelope
		FT				Feet
		KG				Kilogram
		KGS				Kilograms
		LB				Pound
		LBS				Pounds
		L				Liter
		M				Meter
		PK				Package
		PA				Packet
		PAL				Pallet
		PR				Pair
		PRS				Pairs
		PC				Piece
		PCS				Pieces
		PF				Proof Liters
		ROL				Roll
		SET				Set
		SME				Square Meters
		SYD				Square Yard
		TU				Tube
		YD				Yard
		******************************************************************************/
//		$unitofmeasure 			= $dryiceoption->addChild('UnitOfMeasure', ''); // max 10 merkkiä, O
//		$regulationset 			= $dryiceoption->addChild('RegulationSet', ''); // max 5 merkkiä, O
//		$ismedical 				= $dryiceoption->addChild('IsMedical', ''); // 1 merkki, O
//		$dryicepackagingtype 	= $dryiceoption->addChild('DryIcePackagingType', ''); // 1 merkki, O






		// International Documentation
		// ******************************************************************************
		// ******************************************************************************
		/*
		Dependent upon business rule certain documents are required
		*/
//		$internationaldocumentation = $xml->OpenShipment->addChild('InternationalDocumentation');
//		$invoiceimportersameasshipto = $internationaldocumentation->addChild('InvoiceImporterSameAsShipTo', ''); // 1 merkki, C

		/******************************************************************************
		Term of Sale
		Valid Values	Description
		CIP				Carriage and Insurance Paid
		CPT				Carriage Paid To
		CFR				Cost and Freight
		CIF				Cost, Insurance and Freight
		DAF				Delivered at Frontier
		DDP				Delivered Duty Paid
		DDU				Delivered Duty Unpaid
		DEQ				Delivered Ex Quay
		DES				Delivered Ex Ship
		EXW				Ex Works
		FAS				Free Along Side
		FCA				Free Carrier
		FOB				Free On Board
		UNK				Unknown
		******************************************************************************/
//		$invoicetermofsale = $internationaldocumentation->addChild('InvoiceTermOfSale', ''); // max 255 merkkiä, C

		/******************************************************************************
		Reason For Export
		Valid Values	Description
		02				Sale
		03				Repair
		05				Gift
		06				Sample
		07				Return
		08				Intercompany Data
		09				Other
		******************************************************************************/
		/*
		$invoicereasonforexport 					= $internationaldocumentation->addChild('InvoiceReasonForExport', ''); // max 255 merkkiä, C
		$invoicedeclarationstatement 				= $internationaldocumentation->addChild('InvoiceDeclarationStatement', ''); // max 255 merkkiä, C
		$invoiceadditionalcomments 					= $internationaldocumentation->addChild('InvoiceAdditionalComments', ''); // max 300 merkkiä, C
		$invoicecurrencycode 						= $internationaldocumentation->addChild('InvoiceCurrencyCode', ''); // max 3 merkkiä, C
		$invoicediscount 							= $internationaldocumentation->addChild('InvoiceDiscount', ''); // max 4 merkkiä, C
		$invoicechargesfreight 						= $internationaldocumentation->addChild('InvoiceChargesFreight', ''); // max 4 merkkiä, C
		$invoicechargesinsurance 					= $internationaldocumentation->addChild('InvoiceChargesInsurance', ''); // max 4 merkkiä, C
		$invoicechargesother 						= $internationaldocumentation->addChild('InvoiceChargesOther', ''); // max 4 merkkiä, C
		$invoicelinetotal 							= $internationaldocumentation->addChild('InvoiceLineTotal', ''); // system generated, C
		$customsvaluetotal 							= $internationaldocumentation->addChild('CustomsValueTotal', ''); // system generated, C
		$customsvaluecurrencycode 					= $internationaldocumentation->addChild('CustomsValueCurrencyCode', ''); // max 3 merkkiä, C
		$customsvalueorigin 						= $internationaldocumentation->addChild('CustomsValueOrigin', ''); // max 255 merkkiä, C
		$sedcode 									= $internationaldocumentation->addChild('SED-Code', ''); // max 2 merkkiä, C
		$sedpartiestotransaction 					= $internationaldocumentation->addChild('SED-PartiesToTransaction', ''); // max 255 merkkiä, C
		$sedultimateconsigneesameasshipto 			= $internationaldocumentation->addChild('SED-UltimateConsigneeSameAsShipTo', ''); // 1 merkki, C
		$sedcountryterritoryofultimatedestination 	= $internationaldocumentation->addChild('SED-CountryTerritoryOfUltimateDestination', ''); // max 3 merkkiä, C
		$sedaestransactionnumber 					= $internationaldocumentation->addChild('SED-AESTransactionNumber', ''); // max 17 merkkiä, C
		$sedaestransactionnumbertype 				= $internationaldocumentation->addChild('SED-AESTransactionNumberType', ''); // max 7 merkkiä, C
		$sedroutedtransaction 						= $internationaldocumentation->addChild('SED-RoutedTransaction', ''); // 1 merkki, C
		$sedcurrencyconversion 						= $internationaldocumentation->addChild('SED-CurrencyConversion', ''); // max 12 merkkiä, C
		$sedexportcode 								= $internationaldocumentation->addChild('SED-ExportCode', ''); // max 2 merkkiä, C
		$coupsprepareco 							= $internationaldocumentation->addChild('CO-UPSPrepare-CO', ''); // 1 merkki, C
		$coowneroragent 							= $internationaldocumentation->addChild('CO-OwnerOrAgent', ''); // max 255 merkkiä, C
		$naftaproducer 								= $internationaldocumentation->addChild('NAFTA-Producer', ''); // max 255 merkkiä, C
		$naftablanketperiodstart 					= $internationaldocumentation->addChild('NAFTA-BlanketPeriodStart', ''); // max 9 merkkiä, C
		$naftablanketperiodend 						= $internationaldocumentation->addChild('NAFTA-BlanketPeriodEnd', ''); // max 9 merkkiä, C
		*/





		// Goods
		// ******************************************************************************
		// ******************************************************************************
		/*
		Dependent upon business rule certain documents are required
		*/
		/*
		$goods									= $xml->OpenShipment->addChild('Goods');
		$partnumber 							= $goods->addChild('PartNumber', ''); // max 35 merkkiä
		$descriptionofgood 						= $goods->addChild('DescriptionOfGood', ''); // max 105 merkkiä
		$invnaftatariffcode 					= $goods->addChild('Inv-NAFTA-TariffCode', ''); // max 105 merkkiä
		$invnaftacocountryterritoryoforigin 	= $goods->addChild('Inv-NAFTA-CO-CountryTerritoryOfOrigin', ''); // max 105 merkkiä
		$invoiceunits 							= $goods->addChild('InvoiceUnits', ''); // max 4 merkkiä
		*/

		/******************************************************************************
		Unit of Measures
		Valid Values	Description
		BG				Bag
		BA				Barrel
		BT				Bolt
		BX				Box
		BH				Bunch
		BE				Bundle
		BU				Butt
		CI				Canister
		CM				Centimeter
		CT				Carton
		CS				Case
		CON				Container
		CR				Crate
		CY				Cylinder
		DOZ				Dozen
		EA				Each
		EN				Envelope
		FT				Feet
		KG				Kilogram
		KGS				Kilograms
		LB				Pound
		LBS				Pounds
		L				Liter
		M				Meter
		PK				Package
		PA				Packet
		PAL				Pallet
		PR				Pair
		PRS				Pairs
		PC				Piece
		PCS				Pieces
		PF				Proof Liters
		ROL				Roll
		SET				Set
		SME				Square Meters
		SYD				Square Yard
		TU				Tube
		YD				Yard
		******************************************************************************/
		/*
		$invoiceunitofmeasure 						= $goods->addChild('InvoiceUnitOfMeasure', ''); // max 50 merkkiä
		$invoicesedunitprice 						= $goods->addChild('Invoice-SED-UnitPrice', ''); // max 19 merkkiä
		$invoicecurrencycode 						= $goods->addChild('InvoiceCurrencyCode', ''); // 1 merkki
		$invoicenetweight 							= $goods->addChild('InvoiceNetWeight', '');
		$sedcogrossweight 							= $goods->addChild('SED-CO-GrossWeight', ''); // max 4 merkkiä
		$sedlbsorkgs 								= $goods->addChild('SED-LbsOrKgs', ''); // max 3 merkkiä
		$sedprintgoodonsed 							= $goods->addChild('SED-PrintGoodOn-SED', ''); // 1 merkki
		$invoicesedcomarksandnumbers 				= $goods->addChild('Invoice-SED-CO-MarksAndNumbers', ''); // max 105 merkkiä
		$sedschedulenbnumber 						= $goods->addChild('SED-ScheduleBNumber', ''); // max 10 merkkiä
		$sedschedulebunits1 						= $goods->addChild('SED-ScheduleBUnits1', ''); // max 10 merkkiä
		$sedschedulebunits2 						= $goods->addChild('SED-ScheduleBUnits2', ''); // max 10 merkkiä
		$sedunitsofmeasure1 						= $goods->addChild('SED-UnitsOfMeasure1', ''); // max 50 merkkiä
		$sedunitsofmeasure2 						= $goods->addChild('SED-UnitsOfMeasure2', ''); // max 50 merkkiä
		$sedeccn 									= $goods->addChild('SED-ECCN', ''); // max 105 merkkiä
		$sedexception 								= $goods->addChild('SED-Exception', ''); // max 24 merkkiä
		$sedlicencenumber 							= $goods->addChild('SED-LicenceNumber', ''); // max 15 merkkiä
		$sedlicencenumberexpirationdate 			= $goods->addChild('SED-LicenceNumberExpirationDate', ''); // max 10 merkkiä
		$naftaprintgoodonnafta 						= $goods->addChild('NAFTA-PrintGoodOn-NAFTA', ''); // 1 merkki
		$naftapreferencecriterion 					= $goods->addChild('NAFTA-PreferenceCriterion', ''); // 2 merkkiä
		$naftaproducer 								= $goods->addChild('NAFTA-Producer', ''); // max 255 merkkiä
		$naftamultiplecountriesterritoriesoforigin 	= $goods->addChild('NAFTA-MultipleCountriesTerritoriesOfOrigin', ''); // 1 merkki
		$naftanetcostmethod							= $goods->addChild('NAFTA-NetCostMethod', ''); // 1 merkki
		$naftanetcostperiodstartdate 				= $goods->addChild('NAFTA-NetCostPeriodStartDate', ''); // max 10 merkkiä
		$naftanetcostperiodenddate 					= $goods->addChild('NAFTA-NetCostPeriodEndDate', ''); // max 10 merkkiä
		$coprintgoodonco 							= $goods->addChild('CO-PrintGoodOn-CO', ''); // 1 merkki
		$conumberofpackagecontaininggood 			= $goods->addChild('CO-NumberOfPackageContainingGood', ''); // max 4 merkkiä
		*/

		// Muunnetaan merkistö Windows ANSI
		iconv("ISO-8859-1", "Windows-1252", $xml);

		$xml_output = $xml->asXML();

		$filenimi = "dataout/ups_import-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true)).".xml";

		//kirjoitetaan faili levylle..
		if (file_put_contents($filenimi, $xml_output) === FALSE) {
			echo "<br><font class='error'>".t("tiedoston kirjoitus EPÄONNISTUI")." ($filenimi)</font><br>";
		}

		// tarvitaan  $ftphost $ftpuser $ftppass $ftppath $ftpfile
		// palautetaan $palautus ja $syy
		$ftphost = $ups_host_x;
		$ftpuser = $ups_user_x;
		$ftppass = $ups_pass_x;
		$ftppath = $ups_path_x;
		$ftpfile = realpath($filenimi);
		require ("inc/ftp-send.inc");
	}
	else {
		echo "<br><font class='error'>".t("UPS aineiston luontiin tarvittavia parametrejä puuttuu!")."</font><br>";
	}

}

?>