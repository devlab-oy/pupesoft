<?php

	echo "<font class='head'>".t("XML vienti-ilmoitus")."</font><hr>";

	function party ($xml, $object, $params) {
		$Party = $object->appendChild($xml->createElement('Party'));
			$Party->appendChild($xml->createElement('identity', 					$params['identity'])); 					//Y-tunnus (tunnistenumero erotettuna väliviivalla, tunnuksen edessä maakoodi)
			$Party->appendChild($xml->createElement('identityExtension', 			$params['identityExtension'])); 		// Ilmoitetaan Tullin antama viejän tunnisteen viennin jatko-osa, mikäli viejä on rekisteröitynyt Tullin vientiasiakkaaksi.
			$Party->appendChild($xml->createElement('codeListResponsibleAgency',	$params['codeListResponsibleAgency']));
			$Party->appendChild($xml->createElement('name1', 						$params['name1']));
			$Party->appendChild($xml->createElement('name2', 						$params['name2']));
			$Adress = $Party->appendChild($xml->createElement('Adress'));
				$Adress->appendChild($xml->createElement('line', 					$params['line']));
				$Adress->appendChild($xml->createElement('city', 					$params['city']));
				$Adress->appendChild($xml->createElement('postCode', 				$params['postCode']));
				$Adress->appendChild($xml->createElement('country', 				$params['country']));
			$Contact = $Party->appendChild($xml->createElement('Contact'));
				$Contact->appendChild($xml->createElement('name', 					$params['name']));
			$Communication = $Party->appendChild($xml->createElement('Communication'));
				$Communication->appendChild($xml->createElement('telephone', 		$params['telephone']));
				$Communication->appendChild($xml->createElement('telefax', 			$params['telefax']));
				$Communication->appendChild($xml->createElement('email', 			$params['email']));
	}

	function packaging ($xml, $object, $params) {
		$Packaging = $object->appendChild($xml->createElement('Packaging'));
			$Packaging->appendChild($xml->createElement('marksNumber', 	$params['marksNumber']));

			if ($params['packages'] != "") $Packaging->appendChild($xml->createElement('packages', 	$params['packages']));
			else $Packaging->appendChild($xml->createElement('pieces', 	$params['pieces']));

			$Packaging->appendChild($xml->createElement('packageType', 	$params['packageType']));
	}

	function location ($xml, $object, $params) {
		$location = $object->appendChild($xml->createElement('location'));
			$location->appendChild($xml->createElement('qualifier', $params['qualifier']));

			if ($params['identification'] != "") $location->appendChild($xml->createElement('identification', $params['identification']));
			else $location->appendChild($xml->createElement('name', $params['name']));
	}

	function document ($xml, $object, $params) {
		$document = $object->appendChild($xml->createElement('document'));
			$document->appendChild($xml->createElement('type', 		$params['type']));
			$document->appendChild($xml->createElement('reference', $params['reference']));
			$document->appendChild($xml->createElement('issue', 	$params['issue']));
	}

	function transportmeans ($xml, $object, $params) {
		$transportmeans = $object->appendChild($xml->createElement('TransportMeans'));
			$transportmeans->appendChild($xml->createElement('identity', 				$params['identity']));
			$transportmeans->appendChild($xml->createElement('identifier', 				$params['identifier']));
			$transportmeans->appendChild($xml->createElement('modeAndType', 			$params['modeAndType']));
			$transportmeans->appendChild($xml->createElement('registrationNationality', $params['registrationNationality']));
	}

	//hetaan kaikki otunnukset jotka löytyvät tän uusiotunnuksen alta
	$query = "	SELECT group_concat(distinct otunnus) tilaukset
				FROM tilausrivi
				WHERE uusiotunnus = '$laskurow[tunnus]'
				and yhtio = '$kukarow[yhtio]'";
	$uresult = mysql_query($query) or pupe_error($query);

	// varmistetaan, että löydettiin otsikoita
	if (mysql_num_rows($uresult) > 0) {

		$urow = mysql_fetch_assoc($uresult);

		$otunnarit = explode(",", $urow["tilaukset"]);

		$query = "	SELECT *
					FROM laskun_lisatiedot
					WHERE laskun_lisatiedot.otunnus = '$otunnarit[0]'
					and laskun_lisatiedot.yhtio='$kukarow[yhtio]'";
		$lisres = mysql_query($query) or pupe_error($query);
		$lisrow = mysql_fetch_assoc($lisres);


		//haetaan kollimäärä ja bruttopaino
		$query = "	SELECT pakkaus, pakkauskuvaus, pakkauskuvaustark, sum(kilot) kilot, sum(kollit) kollit
					FROM rahtikirjat
					WHERE otsikkonro in ($urow[tilaukset])
					and yhtio='$kukarow[yhtio]'
					group by pakkaus, pakkauskuvaus, pakkauskuvaustark
					having kollit > 0";
		$rahtiresult = mysql_query($query) or pupe_error($query);

		$kilot  = 0;
		$kollit = 0;

		$pakkaus = array();
		$pakkaus_maara = array();
		$pakkaus_kuvaus = array();

		$pakkauslask = 1;

		while($rahtirow = mysql_fetch_assoc($rahtiresult)) {
			// Pakkaukset
			$pakkaus[$pakkauslask] = $rahtirow["pakkaus"];
			$pakkaus_maara[$pakkauslask] = $rahtirow["kollit"];
			$pakkaus_kuvaus[$pakkauslask] = $rahtirow["pakkauskuvaustark"];

			//Rahtikirjan mukaiset kilot ja kollimäärät
			$kilot  += $rahtirow["kilot"];
			$kollit += $rahtirow["kollit"];

			$pakkauslask++;
		}

		if ($laskurow["bruttopaino"] > 0) {
			$kilot = round($laskurow["bruttopaino"],3);
		}

		$kilot = round($kilot,3);
		$kollit = round($kollit);


		$query = "	SELECT osoite
					FROM varastopaikat
					WHERE tunnus = '$laskurow[varasto]'
					and yhtio = '$yhtiorow[yhtio]'
					and osoite != '' AND varasto_status != 'P'";
		$varresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($varresult) > 0) {
			$varrow = mysql_fetch_assoc($varresult);
			$sijaintipaikka = $varrow["osoite"];
		}
		else {
			$sijaintipaikka = $yhtiorow["osoite"];
		}

		//ylimääräisten erien vaikutus
		$extrat = abs($laskurow["lisattava_era"])-abs($laskurow["vahennettava_era"]);

		$query = "	SELECT
					tuote.tullinimike1,
					tuote.tullinimike2,
					tuote.tullikohtelu,
					(SELECT alkuperamaa FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.alkuperamaa!='' LIMIT 1) alkuperamaa,
					tullinimike.su,
					tullinimike.su_vientiilmo,
					tullinimike.dm,
					min(tilausrivi.nimitys) nimitys,
					GROUP_CONCAT(distinct trim(tuote.nimitys) ORDER BY tuote.nimitys SEPARATOR '##') nimitys_hieno,
					sum(tilausrivi.rivihinta) rivihinta,
					sum(tilausrivi.kpl) kpl,
					round(sum(if(tuote.tuotemassa!=0, tilausrivi.kpl*tuote.tuotemassa, (tilausrivi.rivihinta/$laskurow[summa])*$kilot)), 3) nettop
					FROM tilausrivi
					JOIN tuote ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.ei_saldoa = ''
					JOIN tullinimike ON tuote.tullinimike1 = tullinimike.cn and tullinimike.kieli = '$yhtiorow[kieli]' and tuote.tullinimike1!=''
					WHERE tilausrivi.uusiotunnus	= '$uusiotunnus'
					and tilausrivi.yhtio			= '$kukarow[yhtio]'
					and tilausrivi.kpl				> 0
					GROUP BY tuote.tullinimike1, tuote.tullinimike2, tuote.tullikohtelu, alkuperamaa, tullinimike.su, tullinimike.su_vientiilmo, tullinimike.dm";
		$riviresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($riviresult) > 0) {

			$tavaraerittelyt = mysql_num_rows($riviresult);
			$tavaraera = 1;

			// Tehään xml entiteetit
			foreach ($laskurow as $ind => $val) {
				$laskurow[$ind] = htmlspecialchars($val);
			}
			// Tehään xml entiteetit
			foreach ($yhtiorow as $ind => $val) {
				$yhtiorow[$ind] = htmlspecialchars($val);
			}
			// Tehään xml entiteetit
			foreach ($kukarow as $ind => $val) {
				$kukarow[$ind] = htmlspecialchars($val);
			}

			// create a new XML document
			$xml = new DomDocument('1.0');
			$xml->encoding = 'UTF-8';

			// Vienti-ilmoituksen rootti
			$FIExportDeclaration = $xml->createElement('FIExportDeclaration');
			$FIExportDeclaration = $xml->appendChild($FIExportDeclaration);

				$Message = $FIExportDeclaration->appendChild($xml->createElement('Message'));
					$Message->appendChild($xml->createElement('sender', substr($yhtiorow['ovttunnus'], 0, 12)));
					$Message->appendChild($xml->createElement('recipient', '003702454428-TESTI'));
					$Message->appendChild($xml->createElement('issue', date('Y-m-d')."T".date('H:i:s').".0Z"));
					$Message->appendChild($xml->createElement('reference', $laskurow["tunnus"]));
					//$Message->appendChild($xml->createElement('controlReference', ''));
					//$Message->appendChild($xml->createElement('version', '1.0'));

				$Declaration = $FIExportDeclaration->appendChild($xml->createElement('Declaration'));
					$Exporter =	$Declaration->appendChild($xml->createElement('Exporter'));
						$params = array();
						$params['identity'] 					= tulosta_ytunnus(substr(str_replace("0037", "", $yhtiorow["ovttunnus"]), 0, 8),"","V");
						$params['identityExtension']			= substr(str_replace("0037", "", $yhtiorow["ovttunnus"]), 8);
						$params['name1'] 						= $yhtiorow["nimi"];
						//$params['name2'] 						= $yhtiorow["nimitark"];
						$params['line'] 						= $yhtiorow["osoite"];
						$params['city'] 						= $yhtiorow["postitp"];
						$params['postCode'] 					= $yhtiorow["postino"];
						$params['country'] 						= $yhtiorow["maa"];

						party ($xml, $Exporter, $params);

					$Agent = $Declaration->appendChild($xml->createElement('Agent'));
						$params = array();
						$params['identity'] 				 	= tulosta_ytunnus(substr(str_replace("0037", "", $lisrow["kolm_ovttunnus"]), 0, 8),"","V");
						$params['identityExtension'] 			= substr(str_replace("0037", "", $lisrow["kolm_ovttunnus"]), 8);
						$params['name1']   					 	= $lisrow["kolm_nimi"];
						$params['name2']   					 	= $lisrow["kolm_nimitark"];
						$params['line']    					 	= $lisrow["kolm_osoite"];
						$params['city']    					 	= $lisrow["kolm_postitp"];
						$params['postCode']					 	= $lisrow["kolm_postino"];
						$params['country'] 						= $lisrow["kolm_maa"];

						party ($xml, $Agent, $params);
						if ($lisrow["kolm_ovttunnus"] != "") $Agent->appendChild($xml->createElement('status', '3'));

					$CurrencyExchange = $Declaration->appendChild($xml->createElement('CurrencyExchange'));
						$CurrencyExchange->appendChild($xml->createElement('currencyType', 'EUR'));

					$RepresentativePerson = $Declaration->appendChild($xml->createElement('RepresentativePerson'));
						$params = array();
						$params['name1'] 						= $kukarow["nimi"];
						$params['telephone'] 					= $kukarow["puhno"];
						$params['email'] 						= $kukarow["eposti"];

						party ($xml, $RepresentativePerson, $params);

					$DeclarationPackaging =  $Declaration->appendChild($xml->createElement('DeclarationPackaging'));
						$params = array();
						//$params['marksNumber'] 	= "";
						$params['packages'] 		= $kollit;
						$params['packageType'] 		= "";

						packaging ($xml, $DeclarationPackaging, $params);

					$Declaration->appendChild($xml->createElement('issue', date("Y-m-d")));
					$Declaration->appendChild($xml->createElement('function', $laskurow["sanoman_tarkoitus"]));
					$Declaration->appendChild($xml->createElement('customsReference', ''));
					$Declaration->appendChild($xml->createElement('typePartOne', $laskurow["ilmoitustyyppi"]));
					$Declaration->appendChild($xml->createElement('typePartTwo', $laskurow["ilmoitus"]));
					$Declaration->appendChild($xml->createElement('goodsItem', $tavaraerittelyt));
					$Declaration->appendChild($xml->createElement('totalGrossMass', $kilot));
					//$Declaration->appendChild($xml->createElement('sealsAffixed', ''));


					if ($laskurow["ilmoitus"] == "D" or $laskurow["ilmoitus"] == "E" or $laskurow["ilmoitus"] == "M") {
						$Declaration->appendChild($xml->createElement('predeclarationDateTime', substr($laskurow["ennakkoilm_pvm"],0,10)."T".substr($laskurow["ennakkoilm_pvm"],11,8).".0"));
					}

					//$Declaration->appendChild($xml->createElement('exportPointDate', ''));
					$Declaration->appendChild($xml->createElement('language', 'FI'));

				$GoodsShipment = $FIExportDeclaration->appendChild($xml->createElement('GoodsShipment'));
					$Consignee = $GoodsShipment->appendChild($xml->createElement('Consignee'));
						$params = array();
						//$params['identity'] 				 	= "";
						//$params['codeListResponsibleAgency'] 	= "";
						$params['name1']   					 	= $laskurow["nimi"];
						$params['name2']   					 	= $laskurow["nimitark"];
						$params['line']    					 	= $laskurow["osoite"];
						$params['city']    					 	= $laskurow["postitp"];
						$params['postCode']					 	= $laskurow["postino"];
						$params['country'] 						= $laskurow["maa"];

						party ($xml, $Consignee, $params);

					$DeliveryDestination = $GoodsShipment->appendChild($xml->createElement('DeliveryDestination'));
						$DeliveryDestination->appendChild($xml->createElement('country', $laskurow["maa_maara"]));

					$UCR = $GoodsShipment->appendChild($xml->createElement('UCR'));
						$UCR->appendChild($xml->createElement('traderReference', $laskurow["laskunro"]));
						$UCR->appendChild($xml->createElement('additionalTraderReference', substr($laskurow["viesti"], 0, 35)));

					$TradeTerm = $GoodsShipment->appendChild($xml->createElement('TradeTerm'));
						$TradeTerm->appendChild($xml->createElement('condition', trim(substr($laskurow["toimitusehto"],0,3))));
						$TradeTerm->appendChild($xml->createElement('locationName', trim(substr($laskurow["toimitusehto"],4))));

					$ExitCustomsOffice = $GoodsShipment->appendChild($xml->createElement('ExitCustomsOffice'));
						$params = array();
						//$params['qualifier']   	= "";
						$params['identification']   = $laskurow["poistumistoimipaikka_koodi"];

						location ($xml, $ExitCustomsOffice, $params);

					//$Loading = $GoodsShipment->appendChild($xml->createElement('Loading'));
						//$params = array();
						//$params['qualifier']   	= "";
						//$params['name']   		= "";
						//$params['identification']	= $laskurow["poistumistoimipaikka_koodi"];

						//location ($xml, $Loading, $params);

					$AdditionalDocument = $GoodsShipment->appendChild($xml->createElement('AdditionalDocument'));
						$params = array();
						$params['type']			= "N380"; // Kauppalasku
						$params['reference']	= $laskurow["laskunro"];
						$params['issue']		= $laskurow["tapvm"];

						document ($xml, $AdditionalDocument, $params);

					// Muut liitetiedostot
					$liitteet = explode("\n", $laskurow["liiteasiakirja"]);

					for ($li=1; $li<count($liitteet); $li++) {
						if ($liitteet[$li-1] != "") {
							list($a,$l,$p) = explode("#!#", $liitteet[$li-1]);

							if ($a != "" and $l != "" and $p != "") {
									$params = array();
									$params['type']			= $a;
									$params['reference']	= $l;
									$params['issue']		= $p;

									document ($xml, $AdditionalDocument, $params);
							}
						}
					}

					$AdditionalInformation = $GoodsShipment->appendChild($xml->createElement('AdditionalInformation'));
						$AdditionalInformation->appendChild($xml->createElement('statement', '30400'));
						//$AdditionalInformation->appendChild($xml->createElement('statementDescription', ''));

					//$Warehouse = $GoodsShipment->appendChild($xml->createElement('Warehouse'));
						//$Warehouse->appendChild($xml->createElement('type', ''));
						//$Warehouse->appendChild($xml->createElement('identification', ''));
						//$Warehouse->appendChild($xml->createElement('identitificationExtension', ''));
						//$Warehouse->appendChild($xml->createElement('warehouseType', ''));
						//$Warehouse->appendChild($xml->createElement('country', ''));

					$GoodsShipment->appendChild($xml->createElement('transactionNature', $laskurow["kauppatapahtuman_luonne"]));
					$GoodsShipment->appendChild($xml->createElement('exportationCountry', 'FI'));

			while ($rivirow = mysql_fetch_assoc($riviresult)) {

				// Tehään xml entiteetit
				foreach ($rivirow as $ind => $val) {
					$rivirow[$ind] = htmlspecialchars($val);
				}

				$CustomsGoodsItem = $FIExportDeclaration->appendChild($xml->createElement('CustomsGoodsItem'));
					$CustomsGoodsItem->appendChild($xml->createElement('sequence', $tavaraera));

					$Commodity = $CustomsGoodsItem->appendChild($xml->createElement('Commodity'));
						$Commodity->appendChild($xml->createElement('description', $rivirow["nimitys"]));
						$Commodity->appendChild($xml->createElement('tariffClassification', $rivirow["tullinimike1"]));
						if ($rivirow["tullinimike2"] != "00") $Commodity->appendChild($xml->createElement('tariffClassificationExtension', $rivirow["tullinimike2"]));
						//$Commodity->appendChild($xml->createElement('nationalTariffClassificationExtension', ''));
						//$Commodity->appendChild($xml->createElement('UNDG', ''));

					$Origin = $CustomsGoodsItem->appendChild($xml->createElement('Origin'));
						$Origin->appendChild($xml->createElement('originCountry', $rivirow["alkuperamaa"]));

					$CustomsProcedure = $CustomsGoodsItem->appendChild($xml->createElement('CustomsProcedure'));
						$CustomsProcedure->appendChild($xml->createElement('currentAndPrevious', $rivirow["tullikohtelu"]));
						//$CustomsProcedure->appendChild($xml->createElement('national', ''));

					//$Consignee = $CustomsGoodsItem->appendChild($xml->createElement('Consignee'));
						//$params = array();
						//$params['identity'] 				 	= "";
						//$params['codeListResponsibleAgency'] 	= "";
						//$params['name1']   					= "";
						//$params['name2']   					= "";
						//$params['line']    					= "";
						//$params['city']    					= "";
						//$params['postCode']					= "";
						//$params['country'] 					= "";

						//party ($xml, $Consignee, $params);

					$GoodsPackaging = $CustomsGoodsItem->appendChild($xml->createElement('GoodsPackaging'));
						$params = array();
						$params['marksNumber'] 	= $pakkaus_kuvaus[$tavaraera];
						$params['packages'] 	= $pakkaus_maara[$tavaraera];
						$params['packageType'] 	= $pakkaus[$tavaraera];

						packaging ($xml, $GoodsPackaging, $params);

					//$PreviousCustomsDocument = $CustomsGoodsItem->appendChild($xml->createElement('PreviousCustomsDocument'));
						//$params = array();
						//$params['type']		= "";
						//$params['reference']	= "";
						//$params['issue']		= "";

						//document ($xml, $PreviousCustomsDocument, $params);

					//$AdditionalDocument = $CustomsGoodsItem->appendChild($xml->createElement('AdditionalDocument'));
						//$params = array();
						//$params['type']		= "";
						//$params['reference']	= "";
						//$params['issue']		= "";

						//document ($xml, $AdditionalDocument, $params);

					//$AdditionalInformation = $CustomsGoodsItem->appendChild($xml->createElement('AdditionalInformation'));
						//$AdditionalInformation->appendChild($xml->createElement('statement', ''));
						//$AdditionalInformation->appendChild($xml->createElement('statementDescription', ''));

					$GoodsLocation = $CustomsGoodsItem->appendChild($xml->createElement('GoodsLocation'));
						$params = array();
						$params['qualifier']	= substr($laskurow["tavaran_sijaintipaikka"],0,1);
						$params['name']   		= trim(substr($laskurow["tavaran_sijaintipaikka"],1));

						location ($xml, $GoodsLocation, $params);

					$GoodsMeasure = $CustomsGoodsItem->appendChild($xml->createElement('GoodsMeasure'));
						//$GoodsMeasure->appendChild($xml->createElement('grossMass', ''));
						$GoodsMeasure->appendChild($xml->createElement('netNetWeight', $rivirow["nettop"]));

					if ($rivirow["su"] != '') {
						$GoodsMeasure->appendChild($xml->createElement('tariff', $rivirow["kpl"]));
						$GoodsMeasure->appendChild($xml->createElement('measurementUnit', $rivirow["su_vientiilmo"]));
						//$GoodsMeasure->appendChild($xml->createElement('measurementUnitQualifier', ''));
					}

					//tullausarvi lisäerineen
					if ($extrat != 0 and $laskurow["summa"] != 0) {
						$tullarvo = round(($rivirow["rivihinta"]/$laskurow["summa"]*$extrat)+$rivirow["rivihinta"],2);
					}
					else {
						$tullarvo = round($rivirow["rivihinta"],2);
					}

					$CustomsGoodsItem->appendChild($xml->createElement('statisticalValue', $tullarvo));

					$tavaraera++;
			}
				$Consignment = $FIExportDeclaration->appendChild($xml->createElement('Consignment'));
					$BorderTransportMeans = $Consignment->appendChild($xml->createElement('BorderTransportMeans'));
						$params = array();
						$params['identity']					= $laskurow["aktiivinen_kuljetus"];
						//$params['identifier']				= "";
						$params['modeAndType']				= $laskurow["kuljetusmuoto"];
						$params['registrationNationality'] 	= $laskurow["aktiivinen_kuljetus_kansallisuus"];

						transportmeans ($xml, $BorderTransportMeans, $params);

					$DepartureTransportMeans = $Consignment->appendChild($xml->createElement('DepartureTransportMeans'));
						$params = array();
						$params['identity']					= $laskurow["sisamaan_kuljetus"];
						//$params['identifier']				= "";
						$params['modeAndType']				= $laskurow["sisamaan_kuljetusmuoto"];
						//$params['registrationNationality'] 	= "";

						transportmeans ($xml, $DepartureTransportMeans, $params);

					//$EquipmentIdentification = $Consignment->appendChild($xml->createElement('EquipmentIdentification'));
						//$EquipmentIdentification->appendChild($xml->createElement('sequence', ''));
						//$EquipmentIdentification->appendChild($xml->createElement('identification', ''));

					//$FreightPayment = $Consignment->appendChild($xml->createElement('FreightPayment'));
						//$FreightPayment->appendChild($xml->createElement('method', ''));

					//$TransportEquipment = $Consignment->appendChild($xml->createElement('TransportEquipment'));
						//$TransportEquipment->appendChild($xml->createElement('seal', ''));

					$Consignment->appendChild($xml->createElement('containerIndicator', $laskurow["kontti"]));
					//$Consignment->appendChild($xml->createElement('routingCountry', ''));
		}
	}

	/*
	if(!$xml->schemaValidate('/Users/juppe/Desktop/xsd/FIExportDeclaration.xsd')) {
		echo "NOT VALID XML!";
		die();
	}
	*/

	$out = $xml->saveXML();

	$out = preg_replace("/<[a-z0-9]*?> *?<\/[a-z0-9]*?>/i", "", $out);
	$out = preg_replace("/<[a-z0-9]*?> *?<\/[a-z0-9]*?>/i", "", $out);
	$out = preg_replace("/></", ">\n<", $out);

	file_put_contents("/Users/juppe/Desktop/PUPE.xml" , utf8_encode($out));

	echo "<pre>".htmlentities($out)."</pre>";

?>