<?php
    //
	// Pupekarhu (eKarhu)
	//
	require('../inc/parametrit.inc');

	//----
	// eKarhu parameters - EDIT!
    // user ja pass on tod n‰k samat kuin verkkolaskuissa

	$karhuUser="USER";
	$karhuPass="PASS";
	$karhuHost="ftp.verkkolasku.net";
	$karhuDirr="out/reminder/data/";

	//vain n‰in monta p‰iv‰‰ sitten karhutut
	$karhuLastReminderDays=7;

	//----

	// t‰‰ll‰ k‰yt‰mme ytunnusta jonka saamme jostain! oikeuksien tarkistus.
	if (!isset($karhuttavanYTunnus)) {
		die("AAAARGH! karhuttavanYTunnus ei asetettu!");
	}

    function karhu_begin_work() {
		$query = "LOCK TABLES yriti READ, yhtio READ, tili READ, lasku READ, tilausrivi READ, karhukierros WRITE, karhu_lasku WRITE, sanakirja WRITE";
		$result = mysql_query($query) or pupe_error($query);

	}

	function karhu_commit() {
		$query = "UNLOCK TABLES";
		$result = mysql_query($query) or pupe_error($query);
	}

	function karhu_rollback($msg) {
		$query = "UNLOCK TABLES";
		$result = mysql_query($query) or pupe_error($query);
		die("virhe: $msg, lis‰ksi rollback ei implementoitu! $karhukierros");
	}

	function uusi_karhukierros($yhtio) {
		$query = "SELECT tunnus FROM karhukierros where pvm=current_date and yhtio='$yhtio'";
		$result = mysql_query($query) or karhu_rollback($query);
		$array = mysql_fetch_array($result);
		if (!mysql_num_rows($result)) {
			$query = "INSERT INTO karhukierros (pvm,yhtio) values (current_date,'$yhtio')";
			$result = mysql_query($query) or karhu_rollback($query);
			$query = "SELECT LAST_INSERT_ID() FROM karhukierros";
			$result = mysql_query($query) or karhu_rollback($query);
			$array = mysql_fetch_array($result);
		}
		$out = $array[0];
		return $out;

	}

	function liita_lasku($ktunnus,$ltunnus) {
		/*
		//tarkistetaan, onko jo karhuttu t‰ll‰ kierroksella
		//XXX hoidetaan k‰liss‰
		$query = "SELECT ktunnus FROM karhu_lasku WHERE ktunnus=$ktunnus AND ltunnus=$ltunnus";
		$result = mysql_query($query) or karhu_rollback($query);
		if (mysql_num_rows($result)) {
			echo "lasku on jo karhuttu t‰ll‰ kierroksella: $ltunnus<br/>";
			karhu_rollback($query);
		}
		*/
		$query = "INSERT INTO karhu_lasku (ktunnus,ltunnus) values ($ktunnus,$ltunnus)";
		$result = mysql_query($query) or karhu_rollback($query);
	}
	
	function karhutiedot($yhtio,$karhuttavanYTunnus) {
		$query = "
			SELECT summa
			FROM suoritus s, asiakas a
			WHERE s.yhtio='$yhtio' AND s.kohdpvm<>0 AND s.asiakas_tunnus=a.tunnus AND
			a.yhtio='$yhtio' AND a.ytunnus='$karhuttavanYTunnus'";
	
		$result = mysql_query($query) or karhu_rollback($query);
		$array = mysql_fetch_array($result);
	
		return $array[0];
	}
	
	function xml_add ($joukko, $tieto, $handle)
	{
		$ulos = "<$joukko>";
		if (strlen($tieto) > 0)
			$ulos .= $tieto;
		$ulos .= "</$joukko>\n";
	
		fputs($handle, $ulos);
	}
	
	function dateconv ($date)
	{
		//k‰‰nt‰‰ mysqln vvvv-kk-mm muodon muotoon vvvvkkmm
		return substr($date,0,4).substr($date,5,2).substr($date,8,2);
	}
	
	//tiedoston polku ja nimi
	//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
	list($usec, $sec) = explode(' ', microtime());
	mt_srand((float) $sec + ((float) $usec * 100000));
	$nimi = "/tmp/$kukarow[yhtio]-".md5(uniqid(mt_rand(), true)).".xml";
	
	
	//avataan tiedosto
	$toot = fopen($nimi,"w+");
	
	//karhu_begin_work();
	
	$karhukierros=uusi_karhukierros($kukarow['yhtio']);
	
	//tehd‰‰n verkkolasku oliot
	fputs($toot, "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n");
	fputs($toot, "<PupeReminder Version=\"0.1\">\n");
	
	//haetaan kaikki karhuttavat laskut
	
	$epvm_aikaa=3;
	$kpvm_aikaa=7;
	
	$query = "	SELECT l.ytunnus AS ytunnus, laskunro, summa, tapvm, erpcm, l.tunnus tunnus,
				max(kk.pvm) LastReminderDate,
				coalesce(count(distinct kl.ktunnus),0) RemindersSent
				FROM lasku l
				LEFT OUTER JOIN karhu_lasku as kl on kl.ltunnus=l.tunnus
				LEFT OUTER JOIN karhukierros as kk on kl.ktunnus=kk.tunnus
				WHERE l.yhtio='$kukarow[yhtio]' 
				AND l.tila = 'U' 
				AND l.mapvm='0000-00-00' 
				AND l.erpcm < date_sub(current_date, interval $epvm_aikaa day)
				and l.ytunnus='$karhuttavanYTunnus'
				GROUP BY l.ytunnus
				HAVING (LastReminderDate is null) or (LastReminderDate < date_sub(current_date, interval $kpvm_aikaa day))";	
	$lasresult = mysql_query($query) or pupe_error($query);
	
	
	// Karhukirjeen saajan tiedot
	$query = "select * from asiakas where yhtio='$kukarow[yhtio]' and ytunnus='$karhuttavanYTunnus'";
	$asiresult = mysql_query($query) or pupe_error($query);
	if (mysql_num_rows($asiresult) == 0) die ("Asiakas '$karhuttavanYTunnus' katosi!");
	$asirow=mysql_fetch_array($asiresult);
	
	fputs($toot, "<Reminder>\n");
	fputs($toot, "<CHN>\n");
	
	xml_add("Paper", 								substr($asirow['chn'], 0, 1), 	$toot);
	xml_add("Einvoice", 							substr($asirow['chn'], 1, 1), 	$toot);
	xml_add("Edi", 									substr($asirow['chn'], 2, 1), 	$toot);
	xml_add("Email", 								$yhtiorow['admin_email'], 		$toot);
	
	fputs($toot, "</CHN>\n");
	fputs($toot, "<SellerPartyInformation>\n");
	
	xml_add("SellerPartyIdentifier", 				$yhtiorow['ytunnus'], 		$toot);
	xml_add("SellerPartyDomicile", 					$yhtiorow['kotipaikka'], 	$toot);
	xml_add("SellerOrganisationName", 				$yhtiorow['nimi'], 			$toot);
	xml_add("SellerStreetName", 					$yhtiorow['osoite'], 		$toot);
	xml_add("SellerPostCode", 						$yhtiorow['postino'], 		$toot);
	xml_add("SellerTownName", 						$yhtiorow['postitp'], 		$toot);
	xml_add("SellerCountryName", 					$yhtiorow['maa'], 			$toot);
	xml_add("SellerPhoneNumber", 					$yhtiorow['puhelin'], 		$toot);
	xml_add("SellerFaxNumber", 						$yhtiorow['fax'], 			$toot);
	xml_add("SellerAccountName1", 					$yhtiorow['pankkinimi1'],	$toot);
	xml_add("SellerAccountID1", 					$yhtiorow['pankkitili1'],	$toot);
	xml_add("SellerAccountName2", 					$yhtiorow['pankkinimi2'],	$toot);
	xml_add("SellerAccountID2", 					$yhtiorow['pankkitili2'],	$toot);
	xml_add("SellerAccountName3", 					$yhtiorow['pankkinimi3'],	$toot);
	xml_add("SellerAccountID3", 					$yhtiorow['pankkitili3'],	$toot);
	xml_add("SellerVatRegistrationText", 			"Alv.Rek", 					$toot);
	
	fputs($toot, "</SellerPartyInformation>\n");
	fputs($toot, "<ReminderDetails>\n");
	
	
	xml_add("ReminderType",							"",			 									$toot);
	xml_add("InvoicedPartyIdentifier",				$asirow['ytunnus'], 							$toot);
	xml_add("InvoicedPartyOVT",						$asirow['ovttunnus'],							$toot);
	xml_add("InvoicedPartyEBA",						$asirow['verkkotunnus'], 						$toot);
	xml_add("InvoicedOrganisationName",				$asirow['nimi'] . " " . $asirow['nimitark'], 	$toot);
	xml_add("InvoicedStreetName",					$asirow['osoite'], 								$toot);
	xml_add("InvoicedPostCode",						$asirow['postino'],								$toot);
	xml_add("InvoicedTownName",						$asirow['postitp'],								$toot);
	xml_add("InvoicedCountryName",					$asirow['maa'], 								$toot);
	xml_add("ReminderCurrency",						$yhtiorow['valkoodi'],								$toot);
	xml_add("PaymentsReceived",						karhutiedot($laskurow['yhtio'],$laskurow['yunnus']), $toot);
	
	// kirjoitetaan rivitietoja karhuttavista laskuista
	
	while ($laskurow = mysql_fetch_array($lasresult))
	{
		liita_lasku($karhukierros,$laskurow["tunnus"]);
		fputs($toot, "<ReminderRow>\n");
		xml_add("InvoiceIdentifier",				$laskurow['laskunro'], 			$toot);
		xml_add("InvoiceDate",						dateconv($laskurow['tapvm']),	 	$toot);
		xml_add("InvoiceDueDate",					dateconv($laskurow['erpcm']), 	$toot);
		xml_add("InvoiceAmount",					$laskurow['summa'],				$toot);
		xml_add("LastReminderDate",					dateconv($laskurow['LastReminderDate']),	$toot);
		xml_add("RemindersSent",					$laskurow['RemindersSent'], 				$toot);
		fputs($toot, "</ReminderRow>\n");
		$lask++;
	}
	
	fputs($toot, "</ReminderDetails>\n");
	fputs($toot, "</Reminder>\n");
	
	// suljetaan pupevoice olio
	fputs($toot, "</PupeReminder>\n");
	
	// suljetaan faili
	fclose($toot);
	
	
	//tulostetaan xml tiedosto ruudulle.. debuggausta varten
	if ($lask > 0) {
		$handle = fopen($nimi,"r");
	
		$contents = "";
		while (!feof ($handle)) {
			$buffer = fgets($handle, 4096);
			$contents .= $buffer;
		}
		fclose ($handle);
		echo "<pre>".htmlentities($contents)."</pre><br><br>";
	
	
		//siirretaan laskutiedosto operaattorille
	#	$cmd = "ncftpput -u ".$yhtiorow['verkkotunnus_lah']." -p ".$yhtiorow['verkkosala_lah']." ftp.verkkolasku.net out/einvoice/data/ ".$nimi;
	#	$palautus = exec($cmd);
	#	echo "<br>Siirtokomento palautti: $palautus<br>";
	
		//poistetaan xml faili
		$palautus = exec("rm -f $nimi");
	}
	else {
		echo "<br><br>Sinulla ei ollut yht&auml;&auml;n laskua siirrett&auml;v&auml;n&auml;!<br>";
	}

/*


$tieto1 = "InvoiceDetails";
$kentat1 = "if(arvo >= 0, '380', '381') InvoiceType,
	ytunnus InvoicedPartyIdentifier,
	ovttunnus InvoicedPartyOVT,
	verkkotunnus InvoicedPartyEBA,
	concat(nimi,' ' ,nimitark) InvoicedOrganisationName,
	osoite InvoicedStreetName,
	postino InvoicedPostCode,
	postitp InvoicedTownName,
	maa InvoicedCountryName,
	laskunro InvoiceNumber,
	'EUR' InvoiceCurrency,
	viite InvoicePaymentReference,
	DATE_FORMAT(tapvm, '%Y%m%d') InvoiceDate,
	lasku.tunnus OrderIdentifier,
	concat(lasku.toim_nimi, ' ', lasku.toim_nimitark) DeliveredPartyName,
	toim_osoite DeliveredPartyStreetName,
	toim_postino DeliveredPartyPostCode,
	toim_postitp DeliveredPartyTownName,
	toim_maa DeliveredPartyCountryName,
	toim_ovttunnus DeliveredPartyOVT,
	DATE_FORMAT(erpcm, '%Y%m%d') DueDate,
	arvo InvoiceTotalVatExcludedAmount,
	summa-arvo InvoiceTotalVatAmount,
	summa InvoiceTotalVatrequiredAmount,
	maksuehto.teksti PaymentTerms,
	viikorkopros PaymentOverDueFinePercent,
	viesti InvoiceReferenceFreeText,
	comments InvoiceFreeText,
	max(karhukierros.pvm) LastReminderDate,
	coalesce(count(distinct karhu_lasku.ktunnus),0) RemindersSent";
$taulu1 = "lasku left outer join karhu_lasku on (lasku.tunnus=karhu_lasku.ltunnus) left outer join karhukierros on (karhukierros.tunnus=karhu_lasku.ktunnus), maksuehto";
$mitka1 = "lasku.yhtio='$kukarow[yhtio]' and tila='U' and maksuehto.tunnus=lasku.maksuehto";
	$mitka1 .= " and ytunnus='$karhuttavanYTunnus' and erpcm < now() AND mapvm='0000-00-00'";
	$mitka1 .= " group by lasku.tunnus having LastReminderDate is null or to_days(LastReminderDate < date_sub(current_date, interval $karhuLastReminderDays day))";

	$tieto12 = "VatSpecificationDetails";
	$kentat12 = "alv VatRatePercent,
				round(sum(rivihinta),2) VatBaseAmount,
				round(sum(alv/100*rivihinta),2) VatRateAmount";
	$taulu12 = "tilausrivi";
	$mitka12 = "otunnus=";
	$ryhma12 = "GROUP BY alv";

	$tieto2 = "Row";
	$kentat2 = "tuoteno ArticleIdentifier,
				nimitys ArticleName,
				kpl DeliveredQuantity,
				yksikko DeliveredQuantityUnitCode,
				DATE_FORMAT(tilausrivi.toimaika, '%Y%m%d') DeliveryDate,
				tilausrivi.hinta UnitPrice,
				tilausrivi.tunnus RowIdentifier,
				ale RowDiscountPercent,
				lasku.alv RowVatRatePercent,
				round(tilausrivi.hinta*kpl-rivihinta, 2) RowVatAmount,
				rivihinta RowTotalVatExcludedAmount,
				round(tilausrivi.hinta*kpl, 2) RowTotalVatrequiredAmount,
				kommentti RowFreeText";
	$taulu2 = "tilausrivi, lasku";
	$mitka2 = "tilausrivi.otunnus=lasku.tunnus and tilausrivi.otunnus=";

	//muodostetaan eKarhu


	// <InvoiceDetails>
	$query = "SELECT $kentat1
			  FROM   $taulu1
			  WHERE  $mitka1";
	$result = mysql_query($query)
		or die ("Kysely ei onnistu $query");
	//die("<br><br>$query<br><br>");

	$num_rows = mysql_num_rows($result);

	if ($num_rows == 0) {
		die ("Ei laskurivej‰! $query");
	}

	//karhu_begin_work();

	$karhukierros=uusi_karhukierros($kukarow['yhtio']);

	fputs($toot, "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n");
	b("Pupekarhu Version=\"0.01\"", "", $toot);

	while ($row=mysql_fetch_array($result)) {

		$karhutiedot=karhutiedot($kukarow['yhtio'],$karhuttavanYTunnus,$row["OrderIdentifier"]);
		liita_lasku($karhukierros,$row["OrderIdentifier"]);

		b("Invoice", "", $toot);
		// <CHN>
		a($tieto0, $kentat0, $taulu0, $mitka0, $row["OrderIdentifier"], $toot, '');
		a($tieto, $kentat, $taulu, $mitka, '', $toot, '');

		$ulos = "<$tieto1>\n";
		fputs($toot, $ulos);

		for ($i = 0; $i < mysql_num_fields($result); $i++) {
			$ulos = "<" . mysql_field_name($result,$i).">";
			$ulos .= $row[$i];
			$ulos .= "</" . mysql_field_name($result,$i).">\n";
			fputs($toot,$ulos);
		}

		// <VatSpecificationDetails>
		a($tieto12, $kentat12, $taulu12, $mitka12, $row["OrderIdentifier"], $toot, $ryhma12);	//alv erittelyt

		fputs($toot,$karhutiedot);

		$viim = $row["OrderIdentifier"];
		if (strlen($tieto2) > 0) { // Alikysely
			$mitka3 = $mitka2;
			a($tieto2, $kentat2, $taulu2, $mitka3, $viim, $toot, '');

		}
		$ulos = "</$tieto1>\n";
		fputs($toot, $ulos);
		b("/Invoice", "", $toot);
	}

	karhu_commit();

	b("/Pupekarhu", "", $toot);
	fclose($toot);


	//lahetetaan faili ftp.verkkolasku.nettiin

	$handle = fopen('ekarhut/'.$nimi,"r");

	$contents = "";
	while (!feof ($handle)) {
		$buffer = fgets($handle, 4096);
		$contents .= $buffer;
	}
	fclose ($handle);
	echo "<pre>".htmlentities($contents)."</pre><br><br>";

	//exec("ncftpput -u $karhuUser -p $karhuPass $karhuHost $karhuDire ekarhut/".$nimi);

?>
*/

?>
