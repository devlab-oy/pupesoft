<?php

	// Tarvitsemme:
	// $trow			 --> jossa on tuotteen kaikki tiedot
	// $rivinumero 		 --> kent‰ss‰ on joko tilaajan rivinumero tai konserninsis‰isiss‰ kaupoissa sis‰inen toimittajanumero
	// $laskurow		 --> jossa on laskun kaikki tiedot
	// $kukarow[kesken]  --> jossa on k‰ytt‰j‰ll‰ keskenoleva tilausnumero
	// $kpl 			 --> jossa on tilattu kappalem‰‰r‰
	// $tuoteno 		 --> jossa on tilattava tuotenumero
	// $toimaika		 --> arvioitu toimitusaika
	// $kerayspvm		 --> toivottu ker‰ysaika
	// $hinta 			 --> k‰ytt‰j‰n syˆtt‰m‰ hinta
	// $netto 			 --> k‰ytt‰j‰n syˆtt‰m‰ netto
	// $ale 			 --> k‰ytt‰j‰n syˆtt‰m‰ ale
	// $var 			 --> H,J,P varrit
	// $varasto 		 --> myyd‰‰n vain t‰st‰/n‰ist‰ varastosta
	// $paikka 			 --> myyd‰‰n vain t‰lt‰ paikalta
	// $rivitunnus		 --> tietokannan tunnus jolle rivi lis‰t‰‰n
	// $korvaavakielto	 --> Jos erisuuri kuin tyhj‰ niin ei myyd‰ korvaavia
	// $perhekielto      --> Jos erisuuri kuin tyhj‰ niin ei etsit‰ ollenkaan perheit‰
	// $varataan_saldoa  --> Jos == EI niin ei varata saldoa (tietyiss‰ keisseiss‰), tai siis ei ainakan tehd‰ saldotsekki‰
	// $kutsuja			 --> Kuka t‰t‰ skripti‰ kutsuu
	// $myy_sarjatunnus  --> Jos halutaan automaattisesti linkata joku sarjanumero-olio tilausriviin
	// $osto_sarjatunnus --> Jos halutaan automaattisesti linkata joku sarjanumero-olio tilausriviin
	// $jaksotettu		 --> Kuuluuko tilausrivi mukaan jaksotukseen
	// $perheid			 --> Tuoteperheen perheid
	// $perheid2		 --> Lis‰varusteryhm‰n perheid2
	// $orvoteikiinnosta --> Meit‰ ei kiinnosta orvot jos t‰m‰ ei ole tyhj‰.
	// $osatoimkielto	 --> Jos saldo ei riit‰ koko riville niin ei lis‰t‰ rivi‰ ollenkaan
	// $olpaikalta		 --> pakotetaan myym‰‰n oletuspaikalta

	// Palauttaa arrayn $lisatyt_rivit1 jossa on kaikkien rivien tunnukset jotka t‰‰ systeemi lis‰si myyntihaarassa
	// Palauttaa arrayn $lisatyt_rivit2 jossa on kaikkien rivien tunnukset jotka t‰‰ systeemi lis‰si puute-jt-osto-haarassa

	//Jos saamme vain yksi valittu varasto, niin tehd‰‰n siit‰ yhteensopiva array
	if (!is_array($varasto) and (int) $varasto > 0) {
		$varasto = array($varasto);
	}

	//katotaan oletuspaikka ja myyd‰‰n tietenkin sielt‰ jos niin halutaan
	if ($olpaikalta != '') {
		$olquery = "select concat_ws('#',hyllyalue,hyllynro,hyllyvali,hyllytaso) olpaikka
					from tuotepaikat
					where yhtio = '$kukarow[yhtio]'
					and tuoteno = '$trow[tuoteno]'
					and oletus != ''
					limit 1";
		$olpares = mysql_query($olquery) or pupe_error($olquery);
		$olparow = mysql_fetch_array($olpares);

		$paikka	= $olparow["olpaikka"];
	}
	
	// P‰iv‰m‰‰rien oikeellisuustsekki
	if (!checkdate(substr($toimaika,5,2), substr($toimaika,8,2), substr($toimaika,0,4))) {
		$toimaika = date("Y-m-d");
	}
	if (!checkdate(substr($kerayspvm,5,2), substr($kerayspvm,8,2), substr($kerayspvm,0,4))) {
		$kerayspvm = date("Y-m-d");
	}

	// Jos yhtiˆn saldok‰sittely on ajasta riippuvainen
	if ($yhtiorow["saldo_kasittely"] == "T") {
		$saldoaikalisa = $kerayspvm;
	}
	else {
		$saldoaikalisa = "";
	}

	//muutetaan pilkkuja pisteiksi
	$kpl 	= str_replace(',','.',$kpl);
	$hinta 	= str_replace(',','.',$hinta);
	$ale 	= str_replace(',','.',$ale);

	$alkupera_trow			= $trow; 				// alkuper‰inen tuoterow talteen..
	$saldoyhteensa			= 0; 					// t‰h‰n summaillaan saldoa yhteens‰
	$lapsiyhteensa			= 0;
	$korvaavan_isaa_myyty	= 0;
	$suoratoimeikiitos  	= '';
	$lop					= "";
	$tuoteperhe				= array(); 				// Tuoteperheiden lapset menev‰t t‰nne
	$kaikkituotteet			= array();  			// t‰h‰n arrayseen lis‰t‰‰n kaikki tuotteet ketjusta
	$kaikkituotteet[]		= strtoupper($tuoteno); // ekaksi lis‰t‰‰n ketjuun kysytty tuote
	$var					= strtoupper($var); 	// varri isoks
	$netto					= strtoupper($netto); 	// netto isoks
	$toimittamatta			= $kpl;					//N‰in paljon ollaan toimittamassa

	// n‰ihin arraysiin tulee sitten myyt‰viss‰ olevat tuotteet, kappaleet ja varastopaikat
	$myy_tuoteno    = array();
	$myy_kpl        = array();
	$myy_hyllyalue  = array();
	$myy_hyllynro   = array();
	$myy_hyllytaso  = array();
	$myy_hyllyvali  = array();
	$myy_varasto    = array();
	$myy_kpl_myyty  = array();
	$myy_hintakerr  = array();
	$myy_alekerroi  = array();
	$myy_era  		= array();
	$myy_lapsi_ein 	= array();
	$lisatyt_rivit1 = array();
	$lisatyt_rivit2 = array();
	$korkieltokommi = array();
	
	// debuggia varten, laita 1 jos haluut messuja
	$lisaarivi_debug = 0;
	
	if ($laskurow['tila'] == 'O') {
		$rivityyppi = 'O';	// Ostotilausrivi
	}
	elseif ($laskurow["tilaustyyppi"] == 'E') {
		$rivityyppi = 'E'; // Ennakkotilausrivi
	}
	elseif ($laskurow["tila"] == 'T') {
		$rivityyppi = 'T'; // Tarjousrivi
	}
	elseif($laskurow["tila"] == 'G') {
		$rivityyppi = 'G';	// Siirtolistatilausrivi
	}
	elseif($laskurow["tila"] == 'S') {
		$rivityyppi = 'G';	// Sis‰inen tyˆm‰‰r‰ysrivi
	}
	elseif($laskurow["tila"] == 'V' and $perheid2 == "W") {
		$rivityyppi = 'W';	// Valmistustilausrivi (Valmiste)
	}
	elseif($laskurow["tila"] == 'V') {
		$rivityyppi = 'V';	// Valmistustilausrivi
	}
	elseif($laskurow["tila"] == 'Z') {
		$rivityyppi = 'Z';	// Terminaalitoimitus
	}
	elseif($laskurow["tila"] == 'B') {
		$rivityyppi = 'B';	// Ostoskori
	}
	elseif($laskurow["tila"] == 'F') {
		$rivityyppi = 'F';	// Tallennett web-tilaus/-tarjous
	}
	else {
		$rivityyppi = 'L'; // Myyntitilausrivi
	}

	// Jos kyseess‰ on rivi joka halutaan tilata ihan normaalisti joltain toimittajalta
	if (substr($paikka,0,3) == "°°°") {
		$var 		 		= "T";
		$toimittajan_tunnus	= substr($paikka, 3);
		$paikka		 		= "#";	
	}
	elseif ($var == "T" and substr($paikka,0,3) != "°°°" and substr($paikka,0,3) != "!!!") {
		$var 				= "";
		$toimittajan_tunnus	= "";
	}

	if (substr($paikka,0,3) == "!!!") {
		$var 		 		= "U";
		$toimittajan_tunnus	= substr($paikka, 3);
		$paikka		 		= "#";
	}
	elseif ($var == "U" and substr($paikka,0,3) != "!!!") {
		$var 				= "";
		$toimittajan_tunnus	= "";
	}

	// Jos kyseess‰ on suoratoimitus joka tilataan toisesta firmasta suoraan
	if (substr($paikka,0,3) == "@@@") {

		if (strpos($paikka, "&&&") !== FALSE) {
			$superpaikka = substr($paikka, 0, strpos($paikka, "&&&"));
			$loppupaikka = substr($paikka, strpos($paikka, "&&&")+3);

			$lop = "LOP#".$toimittamatta;
			$toimittamatta = $loppupaikka;
		}
		else {
			$superpaikka = $paikka;
		}

		$var 		 = "S";
		$paikka 	 = substr($superpaikka, strpos($superpaikka,'#')+1);
		$rivinumero  = substr($superpaikka, 3, strpos($superpaikka,'#')-3);
	}
	elseif ($var == "S" and substr($paikka,0,3) != "@@@") {
		$var 		= "";
		$rivinumero	= "";
		$suoratoimeikiitos = "ei";
	}

	$perhelisa  = "";

	//Haetaan myˆs tuoteperheet tai reseptit, t‰ss‰ parametrit niille.
	if ($laskurow['tila'] == 'V' and $var != "W") {
		$perhelisa = " and tuoteperhe.tyyppi='R' ";
	}
	elseif ($var == "W" and $laskurow["tila"] == 'V' and $laskurow["tilaustyyppi"] == 'V') {
		// Asiaakkaallevalmistus ja halutaan myyd‰ rivi varastosta
		$perhelisa  = " and tuoteperhe.tyyppi in ('P','') ";
		$var 		= "";
		$rivityyppi = 'L'; // Myyntitilausrivi
	}
	else {
		$perhelisa = " and tuoteperhe.tyyppi in ('P','') ";
	}

	// vientikieltok‰sittely:
	// +maa tarkoittaa ett‰ myynti on kielletty t‰h‰n maahan ja sallittu kaikkiin muihin
	// -maa tarkoittaa ett‰ ainoastaan t‰h‰n maahan saa myyd‰
	// eli n‰ytet‰‰n vaan tuotteet jossa vienti kent‰ss‰ on tyhj‰‰ tai -maa.. ja se ei saa olla +maa
	$kieltolisa = "";

	if ($laskurow["toim_maa"] != "" and $laskurow["tila"] != 'V') {
		$kieltolisa = " and (tuote.vienti = '' or tuote.vienti like '%-$laskurow[toim_maa]%' or tuote.vienti like '%+%') and tuote.vienti not like '%+$laskurow[toim_maa]%' ";
	}

	//Tutkitaan onko t‰m‰ hyvitysrivi
	if ((float) $kpl < 0 and trim($var) == "") {
		//Laitetaan varriksi Y jotta ei tarvitse menn‰ myyntihaaraan
		$var = "Y";
	}
	
	if ($laskurow['tila'] != "O" and trim($var) == "") {

		if ($lisaarivi_debug==1) echo "Menn‰‰n myyntihaaraan: $tuoteno<br>";

		// Etsit‰‰n korvaavia tuotteita
		if ($trow["ei_saldoa"] == "" and $korvaavakielto == "" and $rivityyppi == 'L') {

			// Tutkitaan ensin saako t‰lle asiakkaalle myyd‰ korvaavia t‰st‰ tuoteryhm‰st‰
			$query = "  SELECT yhtio
						from korvaavat_kiellot
						where yhtio = '$kukarow[yhtio]'
						and ytunnus in ('$laskurow[ytunnus]','*')
						and osasto  in ('$trow[osasto]','*')
						and try     in ('$trow[try]','*')
						and laji    = 'K'";
			$kkres  = mysql_query($query) or die($query);

			if (mysql_num_rows($kkres) > 0) {
				$korvaavat_kiellot = "KIELTO";
			}
			else {
				$korvaavat_kiellot = "";
			}
			
			// Otetaan alkuper‰isen tuotteen hinta talteen
			list($korva_isa_hinta, , $korva_isa_ale, , ) = alehinta($laskurow, $trow, $kpl, '', '', '');

			// Tehd‰‰n homma siten, ett‰ etsit‰‰n kaikki korvaavat jo t‰ss‰ vaiheessa arrayseen
			$query = "	SELECT * 
						from korvaavat 
						where tuoteno	= '$tuoteno' 
						and yhtio		= '$kukarow[yhtio]'";
			$kres1 = mysql_query($query) or die($query);

			while ($krow1 = mysql_fetch_array($kres1)) {
				// K‰yd‰‰n l‰pi kaikki korvaavat...
				$query = "	SELECT tuote.*
							FROM korvaavat
							JOIN tuote ON korvaavat.yhtio = tuote.yhtio and korvaavat.tuoteno = tuote.tuoteno
							WHERE korvaavat.id	='$krow1[id]' 
							and korvaavat.yhtio	='$kukarow[yhtio]'
							order by korvaavat.jarjestys, korvaavat.tuoteno";
				$kres2  = mysql_query($query) or die($query);

				while ($krow2 = mysql_fetch_array($kres2)) {
											
					list(, , $krmyytavissa) = saldo_myytavissa($krow2["tuoteno"], $jtspec, '', '', '', '', '', '', '', $saldoaikalisa);
					list($korva_hinta, , $korva_ale, , ) = alehinta($laskurow, $krow2, $kpl, '', '', '');
										
					if ($korvaavat_kiellot == "KIELTO") {
						//Korvaavien myynti kielletty korvaavat_kiellot-taulussa													
						if ($krmyytavissa > 0) {
							$korkieltokommi[] = strtoupper($krow2["tuoteno"]." ($krow2[myyntihinta])");
						}
					}
					elseif($yhtiorow["korvaavan_hinta_ylaraja"] != '' and (($korva_hinta*(1-($korva_ale/100))) / ($korva_isa_hinta*(1-($korva_isa_ale/100)))) > (1+($yhtiorow["korvaavan_hinta_ylaraja"]/100))) {
						//Korvaavien myynti kielletty koska korvaava tuote on liian kallis							
						if ($krmyytavissa > 0) {
							$korkieltokommi[] = strtoupper($krow2["tuoteno"]." ($krow2[myyntihinta])");
						}
					}						
					else {								
						//Yritet‰‰n myyd‰ korvaava tuote
						if ($krmyytavissa > 0) {
							$korkieltokommi[] = strtoupper($krow2["tuoteno"]." ($krow2[myyntihinta])"); // lis‰t‰‰n tuoteno kommenttin
						}
												
						if (!in_array(strtoupper($krow2["tuoteno"]), $kaikkituotteet)) $kaikkituotteet[]=strtoupper($krow2["tuoteno"]); // lis‰t‰‰n tuoteno arrayseen
					}
				}
			} // end k‰yd‰‰n l‰pi korvaavat
			
			if (count($korkieltokommi) == 1) {
				$korkieltokommi = t("Voit tilata korvaavaa tuotetta").": ".$korkieltokommi[0];
			}
			elseif (count($korkieltokommi) > 1) {
				$korkieltokommi = t("Voit tilata korvaavia tuotteita").": ".implode(", ",$korkieltokommi);
			}
		} // end etsit‰‰nkˆ korvaavia

		// Haetaan tuoteperheet, jos $paikka on erisuuri kuin tyhj‰ niin vaihdetaan vain jonkun tuotteen paikkaa eik‰ p‰ivitet‰ tuoteperheit‰
		// Tai jos muokataan tuoteperheen lasta ei menn‰ uudestaan t‰h‰n haaraan
		if ($paikka == "" and $perheid == "" and $perhekielto == "") {
			foreach($kaikkituotteet as $isatuoteno) {
				$query = "	SELECT tuoteperhe.*
							FROM tuoteperhe, tuote
							WHERE tuoteperhe.yhtio = tuote.yhtio
							and tuoteperhe.tuoteno = tuote.tuoteno
							and isatuoteno = '$isatuoteno'
							and tuoteperhe.yhtio = '$kukarow[yhtio]'
							$perhelisa
							$kieltolisa
							ORDER by tuoteno";
				$perheresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($perheresult) > 0) {
					while ($perherow = mysql_fetch_array($perheresult)) {
						
						if ($lisaarivi_debug==1) echo "Tuoteperheen lapsi: $perherow[tuoteno]<br>";
						
						//T‰t‰ tuoteperhett‰ halutaan myyd‰
						if (!in_array(strtoupper($perherow["tuoteno"]), $tuoteperhe)) $tuoteperhe[]=strtoupper($perherow["tuoteno"]); // lis‰t‰‰n tuoteno arrayseen
						if (!in_array(strtoupper($perherow["tuoteno"]), $kaikkituotteet)) $kaikkituotteet[]=strtoupper($perherow["tuoteno"]); // lis‰t‰‰n tuoteno arrayseen
					}
				}
				elseif($laskurow["tila"] == 'V' and $laskurow["tilaustyyppi"] == 'V' and $rivityyppi == "V") {
					// Asiakkaallevalmistuskeissi, resepit‰ ei lˆydu joten teemme t‰st‰ myyntirivin
					$rivityyppi = 'L'; // Myyntitilausrivi
				}
			}
		}

		//Kaikkien arrayden indeksien ‰iti
		$i = 0;

		// Sitten k‰yd‰‰n kaikkien tuotteiden saldot l‰pi kaikilta varastopaikoilta
		foreach ($kaikkituotteet as $tuoteno) {

			$query = "SELECT ei_saldoa from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
			$zres = mysql_query($query) or pupe_error($query);
			$zrow = mysql_fetch_array($zres);

			if ($zrow["ei_saldoa"] == "") {

				if ($orvoteikiinnosta != '') {
					$orvot = 0;
				}
				else {
					// katotaan jos meill‰ on tuotteita varaamassa saldoa joiden varastopaikkaa ei en‰‰ ole olemassa...
					list($saldo, $hyllyssa, $orvot) = saldo_myytavissa($tuoteno, 'ORVOT', '', '', '', '', '', '', $laskurow["toim_maa"], $saldoaikalisa);
					
					$orvot *= -1;
				}

				// K‰yd‰‰n l‰pi tuotepaikat		
				if (isset($paikka) and (($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F") and ($paikka != "EIERAAVIELA#EIEI"))) {
					$query = "	SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa, 
								tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
								sarjanumeroseuranta.sarjanumero era,
								concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0')) sorttauskentta,
								varastopaikat.nimitys, if(varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
					 			FROM tuote
								JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
								JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
								and concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
								and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
								JOIN sarjanumeroseuranta ON sarjanumeroseuranta.yhtio = tuote.yhtio 
								and sarjanumeroseuranta.tuoteno = tuote.tuoteno
								and sarjanumeroseuranta.hyllyalue = tuotepaikat.hyllyalue
								and sarjanumeroseuranta.hyllynro  = tuotepaikat.hyllynro
								and sarjanumeroseuranta.hyllyvali = tuotepaikat.hyllyvali
								and sarjanumeroseuranta.hyllytaso = tuotepaikat.hyllytaso
								and sarjanumeroseuranta.myyntirivitunnus = 0
								and sarjanumeroseuranta.era_kpl != 0
								WHERE tuote.yhtio = '$kukarow[yhtio]'
								and tuote.tuoteno = '$tuoteno'
								GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15
								ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";			
				}
				else {
					$query = "	SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa, 
								tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
								concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta,
								varastopaikat.nimitys, if(varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
					 			FROM tuote
								JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
								JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
								and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
								and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
								WHERE tuote.yhtio = '$kukarow[yhtio]'
								and tuote.tuoteno = '$tuoteno'
								ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";	
				}
				$zres = mysql_query($query) or pupe_error($query);

				//N‰hd‰‰n vaivaa jottaa saadaan paikat sortattua oletuspaikka ensin ja sitten myyt‰viss‰olevan m‰‰r‰n mukaan laskevassa j‰rjestyksess‰
				$zzrow 		= array();
				$zzrowlask 	= 0;

				while ($zrow = mysql_fetch_array($zres)) {

					// Saldolliset tuotteet
					list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($tuoteno, $jtspec, '', '', $zrow["hyllyalue"], $zrow["hyllynro"], $zrow["hyllyvali"], $zrow["hyllytaso"], $laskurow["toim_maa"], $saldoaikalisa, $zrow["era"]);

					if ($lisaarivi_debug==1) echo "Saldo_myyt‰viss‰ funktio sanoo $tuoteno: $zrow[hyllyalue], $zrow[hyllynro], $zrow[hyllyvali], $zrow[hyllytaso]: $saldo, $hyllyssa, $myytavissa kappaletta (Er‰: $zrow[era]) PVM: $saldoaikalisa<br>";

					//Tutkitaan myyd‰‰nkˆ tietty‰ jo varattua lis‰varustetta
					if ($perheid2 > 0 and $perheid2!=$rivitunnus and strtoupper($zrow["hyllyalue"]."#".$zrow["hyllynro"]."#".$zrow["hyllyvali"]."#".$zrow["hyllytaso"]) == strtoupper($paikka)) {
						$myytavissa += $toimittamatta;
					}

					// hoidetaan pois problematiikka jos meill‰ on orpoja (tuotepaikattomia) tuotteita varaamassa saldoa
					if ($orvot > 0) {
						if ($myytavissa >= $orvot) {
					    	// poistaan orpojen varaamat tuotteet t‰lt‰ paikalta
					    	$myytavissa = $myytavissa - $orvot;
					    	$orvot = 0;
						}
						elseif ($orvot > $myytavissa) {
					    	// poistetaan niin paljon orpojen saldoa ku voidaan
					    	$orvot = $orvot - $myytavissa;
					    	$myytavissa = 0;
						}
					}

					$zzrow[$zzrowlask]["myytavissa"]	= $myytavissa;
					$zzrow[$zzrowlask]["ei_saldoa"]		= $zrow["ei_saldoa"];
					$zzrow[$zzrowlask]["oletus"]		= $zrow["oletus"];
					$zzrow[$zzrowlask]["varasto"]		= $zrow["varasto"];
					$zzrow[$zzrowlask]["era"]			= $zrow["era"];
					$zzrow[$zzrowlask]["varastomaa"]	= $zrow["varastomaa"];
					$zzrow[$zzrowlask]["varastotyyppi"]	= $zrow["varastotyyppi"];
					$zzrow[$zzrowlask]["hyllyalue"]		= $zrow["hyllyalue"];
					$zzrow[$zzrowlask]["hyllynro"]		= $zrow["hyllynro"];
					$zzrow[$zzrowlask]["hyllyvali"]		= $zrow["hyllyvali"];
					$zzrow[$zzrowlask]["hyllytaso"]		= $zrow["hyllytaso"];

					if ($lisaarivi_debug==1) echo "Myyt‰viss‰ $tuoteno paikalta $zrow[hyllyalue]: $myytavissa kappaletta<br>";

					// tilatulla tuotteella oli saldoa.. laitetaan lippu p‰‰lle, ett‰ huomataan osatoimituskeisi oikein..
					if ($tuoteno == $alkupera_trow["tuoteno"]) {
						$korvaavan_isaa_myyty = 1; // laitetaan lippu p‰‰lle ett‰ tiedet‰‰n ett‰ meneekˆ tilattua tuotetta ja korvaavaa tuotetetta samalla tilauksella
					}

					$zzrowlask++;
				}

				if (!function_exists('zzrowcmp')) {
					function zzrowcmp($a, $b) {
						if (strcmp($a["oletus"], $b["oletus"]) < 0) {
							return 1;
						}
						elseif (strcmp($a["oletus"], $b["oletus"]) > 0) {
							return -1;
						}
						elseif ($a["myytavissa"] < $b["myytavissa"]) {
							return 1;
						}
						elseif ($a["myytavissa"] > $b["myytavissa"]) {
							return -1;
						}
						else {
							return 0;
						}
					}
				}

				// sortataan varastopaikkojen saldot by ostajan maa
				$oma_maa = array();
				$muu_maa = array();

				foreach ($zzrow as $zzrow_rivi) {
					// jos lˆydet‰‰n varasto toimitusoosoitteen maasta, k‰ytet‰‰n sit‰ eka
					if ($laskurow["toim_maa"] == $zzrow_rivi["varastomaa"] and $laskurow["toim_maa"] != "" and $zzrow_rivi["varastomaa"] != "") {
						$oma_maa[] = $zzrow_rivi;
					}
					else {
						$muu_maa[] = $zzrow_rivi;
					}
				}

				// sortataan ostajan maan varastopaikat saldo j‰rkk‰‰n
				usort($oma_maa, "zzrowcmp");
				// sortataan muiden maiden varastopaikat saldo j‰rkk‰‰n
				usort($muu_maa, "zzrowcmp");

				// yhdistet‰‰n arrayt
				$zzrow = array_merge($oma_maa, $muu_maa);

				// n‰in tehtiin vaan ennen
				// usort($zzrow, "zzrowcmp");
				
				
				if(count($zzrow) > 0) {
					// Jos tuotteella on tuotepaikkoja
					foreach($zzrow as $zrow) {
						if ($yhtiorow["puute_jt_oletus"] == "O" or $varataan_saldoa == "EI" or ($zrow["myytavissa"] > 0 and ($zrow["varastotyyppi"] == '' or $zrow["varastotyyppi"] == 'V' or $laskurow["varasto"] == $zrow["varasto"]))) {

							if ($lisaarivi_debug==1) echo "Myyd‰‰n $tuoteno paikalta $zrow[hyllyalue]: $zrow[myytavissa] kappaletta<br>";

							// lis‰t‰‰n tuotteen tiedot arrayseen, koska t‰m‰n tuotteen voi myyd‰
							$myy_tuoteno[$i]   = strtoupper($tuoteno);
							$myy_kpl[$i]       = $zrow["myytavissa"];
							$myy_hyllyalue[$i] = $zrow["hyllyalue"];
							$myy_hyllynro[$i]  = $zrow["hyllynro"];
							$myy_hyllyvali[$i] = $zrow["hyllyvali"];
							$myy_hyllytaso[$i] = $zrow["hyllytaso"];
							$myy_varasto[$i]   = $zrow["varasto"];
							$myy_era[$i]   	   = $zrow["era"];

							if (!in_array(strtoupper($tuoteno),$tuoteperhe)) {
								$myy_tyyppi[$i] = "NORMAALI";
								$saldoyhteensa += $zrow["myytavissa"]; // summaillaan kaikkien isien saldoa yhteens‰.
							}
							else {
								$myy_tyyppi[$i] = "LAPSITUOTE";
								$lapsiyhteensa += $zrow["myytavissa"]; // summaillaan kaikkien lapsien saldoa yhteens‰.
							}
							//Kasvatetaan indeksi‰
							$i++;
						}
					} // end foreach tuotepaikat
				}
				elseif ($yhtiorow["puute_jt_oletus"] == "O" or $varataan_saldoa == "EI") {
					// Jos tuotteella taas ei ole ollenkaan tuotepaikkoja ja myyd‰‰n kuitenkin (eli ei piitata saldoista)
					
					if ($lisaarivi_debug==1) echo "Tuotteella $tuoteno ei ole yht‰‰n tuotepaikkaa! Myyd‰‰n silti koska saldoista ei nyt piitata!<br>";
					
					$myy_tuoteno[$i]   = strtoupper($tuoteno);
					$myy_kpl[$i]       = $toimittamatta;
					$myy_hyllyalue[$i] = "";
					$myy_hyllynro[$i]  = "";
					$myy_hyllyvali[$i] = "";
					$myy_hyllytaso[$i] = "";
					$myy_varasto[$i]   = "";
					$myy_era[$i]   	   = "";
					
					if (!in_array(strtoupper($tuoteno),$tuoteperhe)) {
						$myy_tyyppi[$i] = "NORMAALI";
					}
					else {
						$myy_tyyppi[$i] = "LAPSITUOTE";
					}
				}
			}
			else {
				// Saldottomat tuotteet
				// lis‰t‰‰n tuotteen tiedot arrayseen, koska t‰m‰n tuotteen voi myyd‰
				$myy_tuoteno[$i]   = strtoupper($tuoteno);
				$myy_kpl[$i]       = $toimittamatta;
				$myy_hyllyalue[$i] = "";
				$myy_hyllynro[$i]  = "";
				$myy_hyllyvali[$i] = "";
				$myy_hyllytaso[$i] = "";
				$myy_varasto[$i]   = "";
				$myy_era[$i]   	   = "";

				if (!in_array(strtoupper($tuoteno),$tuoteperhe)) {
					$myy_tyyppi[$i] = "NORMAALI";
					$saldoyhteensa += $toimittamatta; // summaillaan kaikkien isien saldoa yhteens‰.
					if ($lisaarivi_debug==1) echo "$tuoteno on is‰tuote/normituote<br>";
				}
				else {
					$myy_tyyppi[$i] = "LAPSITUOTE";
					$lapsiyhteensa += $toimittamatta; // summaillaan kaikkien lapsien saldoa yhteens‰.
					if ($lisaarivi_debug==1) echo "$tuoteno on lapsituote<br>";
				}

				//Kasvatetaan ‰iti-indeksi‰
				$i++;
			}
		} // end foreach myyt‰v‰t tuotteet

		if ($lisaarivi_debug==1) echo "Kaikkiaan myyt‰viss‰: $saldoyhteensa kappaletta<br>";

		//tutkitaan ensin saako t‰lle asiakkaalle myyd‰ osatoimituksen t‰st‰ tuoteryhm‰st‰
		$query = "  SELECT yhtio
					from korvaavat_kiellot
					where yhtio = '$kukarow[yhtio]'
					and ytunnus in ('$laskurow[ytunnus]','*')
					and osasto  in ('$trow[osasto]','*')
					and try     in ('$trow[try]','*')
					and laji    = 'O'";
		$kkres  = mysql_query($query) or die($query);

		// jos result on nolla, saadaan myyd‰ osatoimitus tai jos meill‰ on saldoa tarpeeksi
		if ($yhtiorow["puute_jt_oletus"] == "O" or (mysql_num_rows($kkres) == 0 and $osatoimkielto == "") or $saldoyhteensa >= $toimittamatta or $varataan_saldoa == "EI") {

			if ($lisaarivi_debug==1) echo "Saadaan myyd‰ osatoimitus tai jos meill‰ on saldoa tarpeeksi. Saldoa $saldoyhteensa Toimittamatta $toimittamatta<br>";

			// nyt meill‰ on $myy_ arrayperheess‰ kaikki tuotteet ja varastopaikat jolta voidaan myyd‰ jotain..
			// loopataan kaikki tuotteet l‰pi

			//Myyd‰‰n tietylt‰ paikalta
			if ($paikka != '') {
				$paikat = split('#',$paikka);

				if ($lisaarivi_debug==1) echo "Halutaan myyd‰ paikalta $paikka<br>";

				foreach ($myy_tuoteno as $i => $tuoteno) {					
					if ($yhtiorow["puute_jt_oletus"] == "O" or 
						($trow["sarjanumeroseuranta"] != "E" and $trow["sarjanumeroseuranta"] != "F" and $myy_kpl[$i] >= $toimittamatta and $paikat[0] == $myy_hyllyalue[$i] and $paikat[1]  == $myy_hyllynro[$i] and $paikat[2] == $myy_hyllyvali[$i] and $paikat[3] == $myy_hyllytaso[$i]) or
						(($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F") and $myy_era[$i] == $paikat[4] and $myy_kpl[$i] >= $toimittamatta and $paikat[0] == $myy_hyllyalue[$i] and $paikat[1]  == $myy_hyllynro[$i] and $paikat[2] == $myy_hyllyvali[$i] and $paikat[3] == $myy_hyllytaso[$i])) {
												
						//Otetaan t‰m‰
						$myy_tuoteno   	= array();
						$myy_kpl       	= array();
						$myy_hyllyalue 	= array();
						$myy_hyllynro  	= array();
						$myy_hyllytaso 	= array();
						$myy_hyllyvali 	= array();
						$myy_varasto   	= array();
						$myy_kpl_myyty 	= array();
						$myy_era		= array();
						
						$myy_tuoteno[]   = strtoupper($tuoteno);
						$myy_kpl[]       = $toimittamatta;
						$myy_hyllyalue[] = $paikat[0];
						$myy_hyllynro[]  = $paikat[1];
						$myy_hyllyvali[] = $paikat[2];
						$myy_hyllytaso[] = $paikat[3];
						$myy_era[] 		 = $paikat[4];
						
						break;
					}
				}
			}

			// Myyd‰‰n tietyst‰ varastosta
			if (is_array($varasto) and $paikka == '') {

				if ($lisaarivi_debug==1) echo "Halutaan myyd‰ varastoista ", var_dump($varasto).".<br>";

				foreach ($myy_tuoteno as $i => $tuoteno) {
					if (!in_array($myy_varasto[$i], $varasto) and $myy_varasto[$i] != "") {

						if ($myy_tyyppi[$i] == "NORMAALI") {
							$saldoyhteensa -= $myy_kpl[$i];
						}
						else {
							$lapsiyhteensa -= $myy_kpl[$i];
						}

						unset($myy_tuoteno[$i]);
						unset($myy_kpl[$i]);
						unset($myy_hyllyalue[$i]);
						unset($myy_hyllynro[$i]);
						unset($myy_hyllyvali[$i]);
						unset($myy_hyllytaso[$i]);
						unset($myy_varasto[$i]);
						unset($myy_tyyppi[$i]);
						unset($myy_era[$i]);
					}
				}
			}

			foreach ($myy_tuoteno as $i => $tuoteno) {
				//T‰ss‰ loopataan vain is‰/normaleja tuotteita

				if ($lisaarivi_debug==1) echo "Loopataan $tuoteno<br>";

				if ($myy_tyyppi[$i] == "NORMAALI") {
					// jos ei olla viel‰ toimitettu kaikkea, niin lis‰t‰‰n tilausrivi
					if ($toimittamatta > 0) {

						if ($lisaarivi_debug==1) echo "Normaalituote $tuoteno, toimittamatta $toimittamatta<br>";

						if ($yhtiorow["puute_jt_oletus"] == "O" or $myy_kpl[$i] >= $toimittamatta or $varataan_saldoa == "EI") {
							$myy_kpl[$i] = $toimittamatta; // jos t‰ll‰ paikalla on saldoa enemm‰n kun tarvitaan, otetaan sitten vaan tarvittava m‰‰r‰
						}

						$myy_kpl_myyty[$i] += $myy_kpl[$i];

						// status on lis‰t‰‰n is‰ ja perhe jos virheit‰ ei tule
						$perheok 	= 0;

						//Is‰ halutaan nyt ainakin myyd‰
						$myynama 	= array();
						$myynama[] 	= $i;

						if (count($tuoteperhe) > 0) {
							//riitt‰‰kˆ lasten saldot
							$query = "	SELECT tuoteperhe.*
										FROM tuoteperhe, tuote
										WHERE tuoteperhe.yhtio = tuote.yhtio
										and tuoteperhe.tuoteno = tuote.tuoteno
										and isatuoteno = '$tuoteno'
										and tuoteperhe.yhtio = '$kukarow[yhtio]'
										$perhelisa
										$perhelisa2
										$kieltolisa
										ORDER by tuoteno";
							$perheresult = mysql_query($query) or pupe_error($query);

							while($perherow = mysql_fetch_array($perheresult)) {

								$lapsitoimittamatta = $perherow["kerroin"]*$myy_kpl[$i];

								foreach ($myy_tuoteno as $j => $lapsituoteno) {

									if(strtoupper($lapsituoteno) == strtoupper($perherow["tuoteno"]) and $myy_tyyppi[$j] == "LAPSITUOTE") {

										// laitetaan lasten kertoimet kuntoon
										$myy_hintakerr[$j] = $perherow["hintakerroin"];
										$myy_alekerroi[$j] = $perherow["alekerroin"];
										$myy_lapsi_ein[$j] = $perherow["ei_nayteta"];

										// jos ei olla viel‰ toimitettu kaikkea, niin lis‰t‰‰n tilausrivi
										if ($lapsitoimittamatta > 0 and (($myy_kpl[$j] - $myy_kpl_myyty[$j]) > 0 or $varataan_saldoa == "EI")) {

											if (($myy_kpl[$j] - $myy_kpl_myyty[$j] >= $lapsitoimittamatta) or $varataan_saldoa == "EI") {
												// jos t‰ll‰ paikalla on saldoa enemm‰n kun tarvitaan, otetaan sitten vaan tarvittava m‰‰r‰
												// ja splitataan loput omaan paikaan arrayhin jotta voidaan sit myyd‰ ne seuraavalla kiekalla jos on tarvis
												$myy_tuoteno[]	 = $myy_tuoteno[$j];
												$myy_kpl[]		 = $myy_kpl[$j] - $lapsitoimittamatta;
												$myy_hyllyalue[] = $myy_hyllyalue[$j];
												$myy_hyllynro[]	 = $myy_hyllynro[$j];
												$myy_hyllyvali[] = $myy_hyllyvali[$j];
												$myy_hyllytaso[] = $myy_hyllytaso[$j];
												$myy_varasto[]	 = $myy_varasto[$j];
												$myy_tyyppi[]	 = $myy_tyyppi[$j];
												$myy_era[] 	 	 = $myy_era[$j];

												$myy_kpl[$j] = $lapsitoimittamatta;
											}

											$myy_kpl_myyty[$j] += $myy_kpl[$j];

											$myynama[] = $j;

											// hoidellaan saldojen summailua kuntoon
											$lapsitoimittamatta -= $myy_kpl[$j];
										}
									}
								}

								if ($lapsitoimittamatta > 0) {
									//t‰m‰n lapsen saldo ei riit‰
									$perheok++;
								}
							}
						}

						//Jos homma on ok niin lis‰t‰‰n rivi
						if ($perheok == 0) {

							foreach($myynama as $indeksi) {
								//K‰sinsyˆtetyt hinnat ja alennukset eiv‰t p‰de korvaaviin tuotteisiin, eik‰ tuoteperheiden lapsiin
								if (strtoupper($myy_tuoteno[$indeksi]) != strtoupper($alkupera_trow["tuoteno"])) {
									$hinta	= "";
									$ale	= "";
									$netto	= "";
									$alv 	= "";
								}

								// Haetaan k‰sitelt‰v‰n tuotteen tiedot
								$query = "	SELECT *
											FROM tuote
											WHERE yhtio='$kukarow[yhtio]' and tuoteno='$myy_tuoteno[$indeksi]'";
								$tres = mysql_query($query) or die($query);
								$trow = mysql_fetch_array($tres);

								list($lis_hinta, $lis_netto, $lis_ale, $alehinta_alv, $alehinta_val) = alehinta($laskurow, $trow, $myy_kpl[$indeksi], $netto, $hinta, $ale);			
								list($lis_hinta, $lis_alv) = alv($laskurow, $trow, $lis_hinta, $alv, $alehinta_alv);

								// jos ollaan laittamassa muuta tuotetta kun mit‰ tilattiin, lis‰t‰‰n rivikommentti
								if (strtoupper($myy_tuoteno[$indeksi]) != strtoupper($alkupera_trow["tuoteno"]) and $myy_tyyppi[$indeksi] == "NORMAALI") {
									$kommentti .= " ".t("Korvaa tuotteen")." $alkupera_trow[tuoteno]";

									// oletetaan, ett‰ meill‰ on $edi_tyyppi muuttuja viritetty editilaus_in.inc failista
									if ((int) $rivinumero != 0 and $edi_tyyppi != "" and $korvaavan_isaa_myyty == 1) {
										$rivinumero = 0; // jos myyd‰‰n korvaava tuote ja ollaan myyty jo tilattuakin tuotetta, niin ei pistet‰ samaa rivinumeroa..
									}
								}

								if ($kutsuja == "EDITILAUS" or $lisaarivi_debug == 1) {
									$edi_ulos .= " --> ".t("Myyd‰‰n")." $myy_tuoteno[$indeksi] ".t("paikalta")." $myy_hyllyalue[$indeksi]-$myy_hyllynro[$indeksi]-$myy_hyllyvali[$indeksi]-$myy_hyllytaso[$indeksi]: $myy_kpl[$indeksi] ".t("kpl")." ".t("Ale")." $lis_ale% ".t("Hinta")." $lis_hinta $aperuste ".t("ALV")." $lis_alv% $rivikomm\n";
									if ($lisaarivi_debug == 1) echo "$edi_ulos<br>";
								}

								// Tilatut ja varatut kappaleet
								$tilattu = $myy_kpl[$indeksi];
								$varattu = $myy_kpl[$indeksi];

								// Jos hinta ja alekertoimet on setattu
								if (isset($myy_hintakerr[$indeksi])) {
									$lis_hinta = round($lis_hinta * $myy_hintakerr[$indeksi],$yhtiorow['hintapyoristys']);
								}

								if (isset($myy_alekerroi[$indeksi])) {
									if ((int) $myy_alekerroi[$indeksi] == -1) {
										$lis_ale = 100.00;
									}
									else {
										$lis_ale = round($lis_ale * $myy_alekerroi[$indeksi],$yhtiorow['hintapyoristys']);
									}
								}

								//Jos ei varata saldoa niin ei varata saldoa
								if($varataan_saldoa == "EI" and $rivityyppi == 'G') {
									$varattu = 0;
								}
								
								if ($yhtiorow['rivinumero_syotto'] != '') {
									if (!isset($tilrivinumero) or (int) $tilrivinumero == 0) {
										$query = "SELECT max(tilaajanrivinro) as trivinro from tilausrivi where yhtio = '$kukarow[yhtio]' and otunnus = '$kukarow[kesken]'";
										$triviresult = mysql_query($query) or pupe_error($query);
										$trivinrow = mysql_fetch_array($triviresult);
										
										$rivinumero = $trivinrow["trivinro"]+1;
										$tilrivinumero = $rivinumero;
									}
									else {
										$rivinumero = $tilrivinumero;
									}									
								}
								
								// jos olemme saaneet tuotenimityksen syotarivist‰ ja sallimme nimityksen muutoksen
								if ($yhtiorow["nimityksen_muutos_tilauksella"] == "Y" and trim($tuotenimitys != "")) {
									// lis‰t‰‰n saatu nimitys kaikille tuotteille
									$trow["nimitys"] = addslashes($tuotenimitys);
								}
								
								if ($trow["tilausrivi_kommentti"] != "" and (int) $rivitunnus == 0) {
									$kommentti = $trow["tilausrivi_kommentti"]."\n".$kommentti;
								}

								$query = "	INSERT into tilausrivi set
											hyllyalue   = '$myy_hyllyalue[$indeksi]',
											hyllynro    = '$myy_hyllynro[$indeksi]',
											hyllytaso   = '$myy_hyllytaso[$indeksi]',
											hyllyvali   = '$myy_hyllyvali[$indeksi]',
											tilaajanrivinro = '$rivinumero',
											laatija 	= '$kukarow[kuka]',
											laadittu 	= now(),
											yhtio 		= '$kukarow[yhtio]',
											tuoteno 	= '$myy_tuoteno[$indeksi]',
											varattu 	= '$varattu',
											yksikko 	= '$trow[yksikko]',
											kpl 		= '0',
											tilkpl 		= '$tilattu',
											ale 		= '$lis_ale',
											alv 		= '$lis_alv',
											netto		= '$lis_netto',
											hinta 		= '$lis_hinta',
											kerayspvm 	= '$kerayspvm',
											otunnus 	= '$kukarow[kesken]',
											tyyppi 		= '$rivityyppi',
											toimaika 	= '$toimaika',
											kommentti 	= '$kommentti',
											var 		= '',
											try			= '$trow[try]',
											osasto		= '$trow[osasto]',
											perheid		= '$perheid',
											perheid2	= '$perheid2',
											tunnus 		= '$rivitunnus',
											nimitys 	= '$trow[nimitys]',
											jaksotettu	= '$jaksotettu'";

								if ($trow['ei_saldoa'] != '' and $yhtiorow["kerataanko_saldottomat"] == '') {
									$query .= ", keratty = 'saldoton', kerattyaika = now()";
								}

								$result = mysql_query($query) or die($query.mysql_error());
								$lisatty_tun = mysql_insert_id();
								
								@include("trivityyppivalinta.inc");								
								
								$query  = "	SELECT *
											FROM tilausrivin_lisatiedot
											WHERE yhtio			 = '$kukarow[yhtio]'
											and tilausrivitunnus = '$lisatty_tun'";
								$lisatied_res = mysql_query($query) or pupe_error($query);
																														
								if (mysql_num_rows($lisatied_res) > 0) {
									$lisatied_row = mysql_fetch_array($lisatied_res);

									$query = "	UPDATE tilausrivin_lisatiedot
												SET 
												positio = if (positio = '', '$trivtyrow[selite]', positio),
												tilausrivilinkki	= '$tilausrivilinkki',
												toimittajan_tunnus	= '$toimittajan_tunnus',
												muutospvm			= now(),
												muuttaja			= '$kukarow[kuka]'
												WHERE yhtio			 = '$kukarow[yhtio]'
												and tilausrivitunnus = '$lisatty_tun'";
									$result = mysql_query($query) or pupe_error($query);
								}
								else {
									if($yhtiorow["tilauksen_jarjestys"] == "M") {
										$jarjestys = $lisatty_tun;
									}
									else {
										$jarjestys = 0;
									}
									
									$query = "	INSERT INTO tilausrivin_lisatiedot
												SET yhtio			= '$kukarow[yhtio]',
												positio 			= '$trivtyrow[selite]',
												tilausrivilinkki	= '$tilausrivilinkki',
												toimittajan_tunnus	= '$toimittajan_tunnus',
												tilausrivitunnus	= '$lisatty_tun',
												jarjestys			= '$jarjestys',
												luontiaika			= now(),
												laatija 			= '$kukarow[kuka]'";
									
									if ($trow["sarjanumeroseuranta"] == "S" and $varattu < 0) {
										$query .= " , osto_vai_hyvitys='O' ";	
									}		
										
									$result = mysql_query($query) or pupe_error($query);
								}
																
								$lisatyt_rivit1[] = $lisatty_tun;

								if ($myy_tyyppi[$indeksi] == "NORMAALI") {
									// lapsille oikea perheid
									$perheid_lisaarivi = $lisatty_tun;

									// hoidellaan saldojen summailua kuntoon
									$toimittamatta -= $myy_kpl[$indeksi];
								}

								if ($trow["sarjanumeroseuranta"] == "S" and $myy_tyyppi[$indeksi] == "NORMAALI" and $myy_sarjatunnus != '') {
									if ($varattu > 0) {
										$tunken = "myyntirivitunnus";																				
									}
									else {
										$tunken = "ostorivitunnus";
									}
									
									$query = "	SELECT tunnus, kaytetty
												FROM sarjanumeroseuranta
												WHERE tunnus	IN ($myy_sarjatunnus)
												and tuoteno		= '$myy_tuoteno[$indeksi]'
												ORDER BY tunnus
												LIMIT ". (int) abs($varattu);
									$sres = mysql_query($query) or pupe_error($query);																
																		
									while ($srow = mysql_fetch_array($sres)) {														
										$query = "	UPDATE sarjanumeroseuranta
													SET $tunken 		 = '$lisatty_tun'
													WHERE yhtio			 = '$kukarow[yhtio]'
													and tunnus			 = '$srow[tunnus]'
													and tuoteno			 = '$myy_tuoteno[$indeksi]'
													and $tunken 		 = 0";
										$sarjares = mysql_query($query) or pupe_error($query);
									
										if ($srow["kaytetty"] == 'K') {
											$query = "	UPDATE tilausrivi
														SET alv=alv+500
														WHERE yhtio = '$kukarow[yhtio]'
														and tunnus  = '$lisatty_tun'
														and alv < 500";
											$sarjares = mysql_query($query) or pupe_error($query);
										}
									}
									
									$myy_sarjatunnus = "";
								}
								
								if (($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F") and $myy_era[$indeksi] != "") {
									
									if ($varattu > 0) {
										$tunken = "myyntirivitunnus";
										
										$query = "	SELECT ostorivitunnus
													FROM sarjanumeroseuranta
													WHERE yhtio		= '$kukarow[yhtio]'
													and tuoteno		= '$myy_tuoteno[$indeksi]'
													and hyllyalue   = '$myy_hyllyalue[$indeksi]'
													and hyllynro    = '$myy_hyllynro[$indeksi]'
													and hyllytaso   = '$myy_hyllytaso[$indeksi]'
													and hyllyvali   = '$myy_hyllyvali[$indeksi]'
													and sarjanumero = '$myy_era[$indeksi]'";
										$lisa_res = mysql_query($query) or pupe_error($query);
										$lisa_row = mysql_fetch_array($lisa_res);
										
										$oslisa = " ostorivitunnus ='$lisa_row[ostorivitunnus]', ";
									}
									else {
										$tunken = "ostorivitunnus";
										$oslisa = "";
									}
									
									$query = "	INSERT into sarjanumeroseuranta 
												SET yhtio 	= '$kukarow[yhtio]',
												tuoteno		= '$myy_tuoteno[$indeksi]',
												lisatieto 	= '$lisa_row[lisatieto]',
												$tunken 	= '$lisatty_tun', 
												$oslisa
												kaytetty	= '$lisa_row[kaytetty]',
												era_kpl		= '',
												laatija		= '$kukarow[kuka]', 
												luontiaika	= now(),
												takuu_alku 	= '$lisa_row[takuu_alku]',
												takuu_loppu	= '$lisa_row[takuu_loppu]',
												hyllyalue   = '$myy_hyllyalue[$indeksi]',
												hyllynro    = '$myy_hyllynro[$indeksi]',
												hyllytaso   = '$myy_hyllytaso[$indeksi]',
												hyllyvali   = '$myy_hyllyvali[$indeksi]',
												sarjanumero = '$myy_era[$indeksi]'";								
									$lisa_res = mysql_query($query) or pupe_error($query);									
								}

								if ($myy_tyyppi[$indeksi] == "LAPSITUOTE") {
									//P‰ivitet‰‰n is‰n perhetunnus
									if ($rivityyppi == 'V') {

										$query  = "	SELECT tuoteno
													FROM tilausrivi
													WHERE yhtio	= '$kukarow[yhtio]'
													and tunnus	= $perheid_lisaarivi
													and tyyppi	= 'V'";
										$lisa_res = mysql_query($query) or pupe_error($query);
										$lisa_row = mysql_fetch_array($lisa_res);

										if (isset($lapsenlap_array[$lisa_row["tuoteno"]]) and strtoupper($lapsenlap_array[$lisa_row["tuoteno"]]) == strtoupper($lisa_row["tuoteno"])) {
											$valmistetyyppi = "M";
										}
										else {
											$valmistetyyppi = "W";
										}

										//jos on valmistusprosessi kyseess‰ niin is‰n tyyppi vaihdetaan W:ksi koska sen ei pid‰ varata saldoa!
										$query = "UPDATE tilausrivi SET perheid='$perheid_lisaarivi', tyyppi='$valmistetyyppi' where yhtio='$kukarow[yhtio]' and tunnus=$perheid_lisaarivi and tyyppi='V'";
										$result = mysql_query($query) or pupe_error($query);

										$query = "UPDATE tilausrivi SET perheid='$perheid_lisaarivi' where yhtio='$kukarow[yhtio]' and tunnus=$lisatty_tun";
										$result = mysql_query($query) or pupe_error($query);
									}
									else {
										//Hoidetaan saldottomat is‰t samaan varastoon kuin lapsetkin jotta varastosplittaukset eiv‰t menisi hassusti
										$query = "UPDATE tilausrivi SET hyllyalue='$myy_hyllyalue[$indeksi]' where yhtio='$kukarow[yhtio]' and tunnus=$perheid_lisaarivi and hyllyalue=''";
										$result = mysql_query($query) or pupe_error($query);

										$query = "UPDATE tilausrivi SET perheid='$perheid_lisaarivi' where yhtio='$kukarow[yhtio]' and tunnus in ('$perheid_lisaarivi','$lisatty_tun')";
										$result = mysql_query($query) or pupe_error($query);
									}

									if ($myy_lapsi_ein[$indeksi] != "") {
										$query = "	UPDATE tilausrivin_lisatiedot
													SET ei_nayteta 	= '$myy_lapsi_ein[$indeksi]', 
													muutospvm		= now(),
													muuttaja		= '$kukarow[kuka]'
													WHERE tilausrivitunnus 	= '$lisatty_tun'";
										$result = mysql_query($query) or pupe_error($query);
									}
								}

								$rivitunnus		= "";
								$kommentti 		= "";
								$tuotenimitys 	= "";
								
							} // T‰ll‰ rundilla myydyt
						} // Jos tuoteperhett‰ lˆytyi riitt‰v‰sti tai myytiin pelk‰‰ is‰‰
					} // Jos vil‰ on toimittamatta kappaleita
				} // end if toimittamatta > 0
			} // end foreach myy_tuoteno
		} // end saadaanko myyd‰ osatoimitus
	}

	// jos tultiin ulos loopista, ett‰ meill‰ on toimittamattomia rivej‰, tehd‰‰n niist‰ puuterivi
	// tai jos tullaan niin, ett‰ var-kent‰ss‰ on J tai P tai jotain
	if ($toimittamatta > 0 or trim($var) != '' or $laskurow['tila'] == 'O') {

		if ($lisaarivi_debug==1) echo "Menn‰‰n puutehaaraan: $tuoteno hinta $hinta<br>";
		
		//Tyhjennet‰‰n apumuuttuja
		if ($var == "Y")	{
			$var = "";
		}

		$puu_tuoteno 	= array();
		$puu_kpl 		= array();
		$puu_hintake	= array();
		$puu_alekerr	= array();
		$puu_var		= array();

		//Tehd‰‰n suoratoimitusautomaatio jos kyseess‰ on extranet tai ediasiakas
		if ($yhtiorow["suoratoim_automaatio"] != '' and $var == "" and $toimittamatta > 0 and $suoratoimeikiitos == "" and $rivityyppi != 'E') {

			if ($lisaarivi_debug==1) echo "Tehd‰‰n suoratoimitusautomaatio<br>";

			$suora_tuoteno 	= $alkupera_trow["tuoteno"];
			$suora_kpl		= $toimittamatta;

			require("suoratoimitusvalinta.inc");

			if (count($suoratoim_array) > 0) {
				if (strpos($suoratoim_array[0], "&&&") !== FALSE) {
					$superpaikka = substr($suoratoim_array[0], 0, strpos($suoratoim_array[0], "&&&"));
					$loppupaikka = substr($suoratoim_array[0], strpos($suoratoim_array[0], "&&&")+3);

					$lop = "LOP#".$toimittamatta;
					$toimittamatta = $loppupaikka;
				}
				else {
					$superpaikka = $suoratoim_array[0];
				}

				$var 		 = "S";
				$paikka 	 = substr($superpaikka, strpos($superpaikka,'#')+1);
				$rivinumero  = substr($superpaikka, 3, strpos($superpaikka,'#')-3);
			}
		}


		if (substr($var,0,4) == "LOJ#" or substr($var,0,4) == "LOP#" or substr($lop,0,4) == "LOP#") {

			if ($lisaarivi_debug==1) echo "Laitetaan loput rivit jtksi tai puutteeksi (".substr($var,0,4).") (".substr($lop,0,4).")<br>";
			
			//Lis‰t‰‰n syˆtetyt ja laitetaan loput JT:seen tai puutteiksi
			$puu_tuoteno[]	= $alkupera_trow["tuoteno"];
			$puu_kpl[] 		= $toimittamatta;
			$puu_hintake[]	= "X";
			$puu_alekerr[]	= "X";
			$puu_var[]		= $var;

			if (substr($var,0,4) == "LOJ#") {
				$puu_tuoteno[]	= $alkupera_trow["tuoteno"];
				$puu_kpl[] 		= substr($var,4) - $toimittamatta;
				$puu_hintake[]	= "X";
				$puu_alekerr[]	= "X";
				$puu_var[]		= "";
			}
			elseif(substr($lop,0,4) == "LOP#") {
				$puu_tuoteno[]	= $alkupera_trow["tuoteno"];
				$puu_kpl[] 		= substr($lop,4) - $toimittamatta;
				$puu_hintake[]	= "X";
				$puu_alekerr[]	= "X";
				$puu_var[]		= "P";
			}
		}
		else {
			
			if ($lisaarivi_debug==1) echo "Kaikki puutteeksi var $var<br>";
			
			$puu_tuoteno[]	= $alkupera_trow["tuoteno"];
			$puu_kpl[]	 	= $toimittamatta;
			$puu_hintake[]	= "X";
			$puu_alekerr[]	= "X";
			$puu_var[]		= $var;
		}

		if ($laskurow['tila'] != 'O') {
			$query = "	SELECT tuoteperhe.*
						FROM tuoteperhe, tuote
						WHERE tuoteperhe.yhtio = tuote.yhtio
						and tuoteperhe.tuoteno = tuote.tuoteno
						and isatuoteno = '$alkupera_trow[tuoteno]'
						and tuoteperhe.yhtio = '$kukarow[yhtio]'
						$perhelisa
						$perhelisa2
						$kieltolisa
						ORDER by tuoteno";
			$perheresult = mysql_query($query) or pupe_error($query);

			while ($perherow = mysql_fetch_array($perheresult)) {
				$puu_tuoteno[]	= $perherow["tuoteno"];
				$puu_kpl[] 		= $toimittamatta*$perherow["kerroin"];
				$puu_hintake[]	= $perherow["hintakerroin"];
				$puu_alekerr[]	= $perherow["alekerroin"];
				$puu_var[]		= $var;
			}
		}

		foreach($puu_tuoteno as $i => $tuoteno) {
			$kpl = $puu_kpl[$i];
			$var = $puu_var[$i];

			$query = "	SELECT *
						FROM tuote
						WHERE tuoteno = '$tuoteno' and yhtio='$kukarow[yhtio]'";
			$tuoteresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($tuoteresult) == 0) {
				// tuotetta ei lˆydy, lis‰t‰‰n silti tuoteno kantaan?
				$trow["tuoteno"] = $kaikkituotteet[0];
			}
			else {
				$trow = mysql_fetch_array($tuoteresult);
			}

			if ($yhtiorow["suoratoim_automaatio"] != '' and $rivityyppi != 'E' and $var == "X") {

				$var 			= "";
				$suora_tuoteno 	= $trow["tuoteno"];
				$suora_kpl		= $kpl;

				require("suoratoimitusvalinta.inc");

				if (count($suoratoim_array) > 0) {
					if (strpos($suoratoim_array[0], "&&&") !== FALSE) {
						$superpaikka = substr($suoratoim_array[0], 0, strpos($suoratoim_array[0], "&&&"));
						$loppupaikka = substr($suoratoim_array[0], strpos($suoratoim_array[0], "&&&")+3);

						$lop = "LOP#".$toimittamatta;
						$toimittamatta = $loppupaikka;
					}
					else {
						$superpaikka = $suoratoim_array[0];
					}

					$var 		 = "S";
					$paikka 	 = substr($superpaikka, strpos($superpaikka,'#')+1);
					$rivinumero  = substr($superpaikka, 3, strpos($superpaikka,'#')-3);
				}
			}
			
			if ($yhtiorow['rivinumero_syotto'] != '') {
				if (!isset($tilrivinumero) or (int) $tilrivinumero == 0) {
					$query = "SELECT max(tilaajanrivinro) as trivinro from tilausrivi where yhtio = '$kukarow[yhtio]' and otunnus = '$kukarow[kesken]'";
					$triviresult = mysql_query($query) or pupe_error($query);
					$trivinrow = mysql_fetch_array($triviresult);
					
					$rivinumero = $trivinrow["trivinro"]+1;
				}
				else {
					$rivinumero = $tilrivinumero;
				}									
			}

			//K‰sinsyˆteyt hinnat nollataan kun lis‰t‰‰n tuoteperheiden lapsia
			//Ostossa splittaukset menee aina samalla hinnalla
			if ($i > 0 and $laskurow['tila'] != 'O') {
				$hinta		= "";
				$ale		= "";
				$netto		= "";
				$alv 		= "";

				if ($var != "S") {
					$rivinumero = "";
				}
			}

			if ($laskurow['tila'] == 'O' and $hinta == "") {
				$query = "	SELECT ostohinta
							FROM tuotteen_toimittajat
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$trow[tuoteno]'
							and liitostunnus = '$laskurow[liitostunnus]'
							LIMIT 1";
				$rarres = mysql_query($query) or pupe_error($query);
				$rarrow	 = mysql_fetch_array($rarres);

				$lis_hinta = $rarrow["ostohinta"];
			}
			elseif ($laskurow['tila'] == 'O') {
				$lis_hinta = $hinta;
			}
			
			if ($laskurow['tila'] == 'O' and $ale == "") {
				$query = "	SELECT alennus
							FROM tuotteen_toimittajat
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$trow[tuoteno]'
							and liitostunnus = '$laskurow[liitostunnus]'
							LIMIT 1";
				$rarres = mysql_query($query) or pupe_error($query);
				$rarrow	 = mysql_fetch_array($rarres);

				$lis_ale = $rarrow["alennus"];
			}
			elseif ($laskurow['tila'] == 'O') {
				$lis_ale = $ale;
			}

			if ($laskurow['tila'] != 'O') {
				list($lis_hinta, $lis_netto, $lis_ale, $alehinta_alv, $alehinta_val) = alehinta($laskurow, $trow, $kpl, $netto, $hinta, $ale);			
				list($lis_hinta, $lis_alv) = alv($laskurow, $trow, $lis_hinta, $alv, $alehinta_alv);
			}

			if ($laskurow['tila'] != 'O') {
				switch ($var) {
					case 'J' : // J‰lkitoimitukseen
						// Yhtiˆn parametri: varaako JT-rivi saldoja
						if ($yhtiorow["varaako_jt_saldoa"] == "") {
							$varattu 	= 0;
							$jt 		= $kpl;
						}
						else {
							$varattu 	= $kpl;
							$jt 		= 0;
						}																		
						break;
					case 'P' : // Puute
						$varattu 	= 0;
						$jt 		= 0;
						break;
					case 'H' : // Pakkohyv‰ksynt‰
						$varattu 	= $kpl;
						$jt 		= 0;
						break;
					case 'O' : // Ookooaus-hyv‰ksynt‰
						$varattu 	= $kpl;
						$jt 		= 0;
						break;
					case 'V' : // Tehdaslis‰varuste
						$varattu 	= $kpl;
						$jt 		= 0;
						break;
					case 'T' : // Tilataan toimittajalta
						$varattu 	= 0;
						$jt 		= $kpl;
						break;
					case 'U' : // Tilataan toimittajalta asiakkaalle
						$varattu 	= 0;
						$jt 		= $kpl;
						break;
					case 'S' : // Suoratoimitus-JT
						$varattu 	= 0;
						$jt 		= $kpl;
						break;
					case '' :
						$varattu 	= 0;
						$jt 		= 0;

						// Sirtolistoissa defaultti on JT jos saldo ei riit‰. (jos yhtio ei v‰lit‰ saldoista ei v‰litet‰ t‰‰ll‰k‰‰n..)
						// Tai jos yhtiˆlle on konffattu, ett‰ se haluaa ylimemen‰t jteiksi
						// Sis‰isiss‰ tyˆm‰‰r‰ykiss‰ ei haluta JT-rivej‰
						if ((($rivityyppi == 'G' and $yhtiorow["puute_jt_oletus"] != "H") or ($yhtiorow["puute_jt_oletus"] == "J" and $kpl > 0)) and $laskurow["tila"] != 'S') {
							if ($yhtiorow["varaako_jt_saldoa"] == "") {
								$varattu 	= 0;
								$jt 		= $kpl;
							}
							else {
								$varattu 	= $kpl;
								$jt 		= 0;
							}
							$var	= "J";
						}
						// Terminaalitoimitukset tai ostoskorit; var tyhj‰‰ ja varattu normisti
						elseif ($rivityyppi == 'Z' or $rivityyppi == 'B' or $rivityyppi == 'E' or $rivityyppi == 'F') {
							$var 	= "";
							$varattu = $kpl;
						}
						// Halutaan myyd‰ saldoista juurikaan mit‰‰n v‰litt‰m‰tt‰!
						elseif ($yhtiorow["puute_jt_oletus"] == "H") {
							$var 	= "";
							$varattu = $kpl;
						}
						// Hyvitysrivit menee normaaleina
						elseif($kpl < 0) {
							$var 	= "";
							$varattu = $kpl;
						}
						// Normaalisti kaikki menee puuteeksi.
						else {
							$var 	= "P";
						}
						break;
					default :
						echo t("VIRHE: Virheellinen arvo kent‰ss‰ hyvaksy")." '$var'";
				}
				if (trim($var) == 'P' and $kutsuja == "EDITILAUS") {
					$kommentti .= t("Tuote Loppu.");
				}
			}
			else {
				$var 		= "";
				$varattu 	= $kpl;
			}

			$p_hyllyalue	= "";
			$p_hyllynro		= "";
			$p_hyllyvali	= "";
			$p_hyllytaso	= "";

			if($paikka != '') {
				//K‰ytt‰j‰n syˆtt‰m‰ paikka
				$paikat = split('#',$paikka);

				$p_hyllyalue	= $paikat[0];
				$p_hyllynro		= $paikat[1];
				$p_hyllyvali	= $paikat[2];
				$p_hyllytaso	= $paikat[3];
			}
			else {
				// haetaan viel‰ tuotteen oletuspaikka puuteriville, jotta seuranta ois helpompaa
				//jos laskurow[varasto] on setattu niin yritet‰‰n lˆyt‰‰ paikka siit‰ varastosta
				$query  = "	SELECT tuotepaikat.*, varastopaikat.tunnus varastotunnus
							FROM tuotepaikat
							LEFT JOIN varastopaikat ON tuotepaikat.yhtio = varastopaikat.yhtio
							and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
							and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
							where tuotepaikat.yhtio = '$kukarow[yhtio]'
							and tuotepaikat.tuoteno = '$trow[tuoteno]'
							ORDER BY tuotepaikat.oletus DESC";
				$result = mysql_query($query) or die($query);

				$loydetytpaikat = 0;

				if (mysql_num_rows($result) != 0) {

					//Yritet‰‰n lˆyt‰‰ paikka laskurow[varasto]:sta
					while($olerow = mysql_fetch_array($result)) {
						if ($olerow["varastotunnus"] == $laskurow["varasto"]) {
							$p_hyllyalue	= $olerow["hyllyalue"];
							$p_hyllynro		= $olerow["hyllynro"];
							$p_hyllyvali	= $olerow["hyllyvali"];
							$p_hyllytaso	= $olerow["hyllytaso"];

							$loydetytpaikat++;
							break;
						}
					}
					//Jos ei lˆytynyt niin myyd‰‰n oletuspaikalta
					if ($loydetytpaikat == 0) {

						mysql_data_seek($result,0);
						$olerow = mysql_fetch_array($result);

						$p_hyllyalue	= $olerow["hyllyalue"];
						$p_hyllynro		= $olerow["hyllynro"];
						$p_hyllyvali	= $olerow["hyllyvali"];
						$p_hyllytaso	= $olerow["hyllytaso"];
					}
				}
			}

			if ($kutsuja == "EDITILAUS" or $lisaarivi_debug == 1) {
				if ($var == "J") {
					$edi_ulos .= " --> ".t("JT")." $trow[tuoteno] ".t("paikalta")." $p_hyllyalue-$p_hyllynro-$p_hyllyvali-$p_hyllytaso: $lis_kpl ".t("kpl")." ".t("Ale")." $lis_ale ".t("Hinta")." $lis_hinta $aperuste $rivikomm\n";
				}
				elseif($var == "P") {
					$edi_ulos .= " --> ".t("PUUTE")." $trow[tuoteno] ".t("paikalta")." $p_hyllyalue-$p_hyllynro-$p_hyllyvali-$p_hyllytaso: $lis_kpl ".t("kpl")." ".t("Ale")." $lis_ale ".t("Hinta")." $lis_hinta $aperuste $rivikomm\n";
				}
				elseif($var == "S") {
					$edi_ulos .= " --> ".t("SUORATOIMITUS")." $trow[tuoteno] ".t("toimittajalta")." $rivinumero: $kpl ".t("kpl")." ".t("Ale")." $lis_ale ".t("Hinta")." $lis_hinta $aperuste $rivikomm\n";
				}

				if ($lisaarivi_debug == 1) echo "$edi_ulos<br>";
			}


			// Jos hinta ja alekertoimet on setattu
			if ($puu_hintake[$i] != "X") {
				$lis_hinta = round($lis_hinta * $puu_hintake[$i],$yhtiorow['hintapyoristys']);
			}

			if ($puu_alekerr[$i] != "X") {
				if ((int) $puu_alekerr[$i] == -1) {
					$lis_ale = 100.00;
				}
				else {
					$lis_ale = round($lis_ale * $puu_alekerr[$i],$yhtiorow['hintapyoristys']);
				}
			}

			if (!is_array($korkieltokommi) and $korkieltokommi != '') {
				$kommentti .= $korkieltokommi;
			}

			$trow["tuoteno"] = strtoupper($trow["tuoteno"]);

			// jos olemme saaneet tuotenimityksen syotarivist‰ ja sallimme nimityksen muutoksen
			if ($yhtiorow["nimityksen_muutos_tilauksella"] == "Y" and trim($tuotenimitys != "")) {
				// lis‰t‰‰n saatu nimitys kaikille tuotteille
				$trow["nimitys"] = addslashes($tuotenimitys);
			}
			
			// JT-rivikommentti
			if ((int) $rivitunnus == 0 and $var == "J"and strpos($kommentti, "(".t("J‰lkitoimitus").":") === false) {
				//$kommentti = "(".t("J‰lkitoimitus").": ".tv1dateconv(date("Y-m-d")).") \n".$kommentti;
			}
			
			if ($trow["tilausrivi_kommentti"] != "" and (int) $rivitunnus == 0) {
				$kommentti = $trow["tilausrivi_kommentti"]."\n".$kommentti;
			}

			// lis‰t‰‰n puuterivi
			$query = "	INSERT into tilausrivi set
						hyllyalue   = '$p_hyllyalue',
						hyllynro    = '$p_hyllynro',
						hyllyvali   = '$p_hyllyvali',
						hyllytaso   = '$p_hyllytaso',
						tilaajanrivinro = '$rivinumero',
						laatija 	= '$kukarow[kuka]',
						laadittu 	= now(),
						yhtio 		= '$kukarow[yhtio]',
						tuoteno 	= '$trow[tuoteno]',
						varattu 	= '$varattu',
						yksikko 	= '$trow[yksikko]',
						kpl 		= '0',
						tilkpl 		= '$kpl',
						jt			= '$jt',
						ale 		= '$lis_ale',
						alv 		= '$lis_alv',
						netto		= '$lis_netto',
						hinta 		= '$lis_hinta',
						kerayspvm 	= '$kerayspvm',
						otunnus 	= '$kukarow[kesken]',
						tyyppi 		= '$rivityyppi',
						toimaika 	= '$toimaika',
						kommentti 	= '$kommentti',
						var 		= '$var',
						try			= '$trow[try]',
						osasto		= '$trow[osasto]',
						perheid		= '$perheid',
						perheid2	= '$perheid2',
						tunnus 		= '$rivitunnus',
						nimitys 	= '$trow[nimitys]',
						jaksotettu	= '$jaksotettu'";

			if ($trow['ei_saldoa'] != '' and $yhtiorow["kerataanko_saldottomat"] == '') {
				$query .= ", kerattyaika = now()";
			}
			$result = mysql_query($query) or die($query);
			$lisatty_tun = mysql_insert_id();
						
			@include("trivityyppivalinta.inc");	
			
			$query  = "	SELECT *
						FROM tilausrivin_lisatiedot
						WHERE yhtio			 = '$kukarow[yhtio]'
						and tilausrivitunnus = '$lisatty_tun'";
			$lisatied_res = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($lisatied_res) > 0) {
				$lisatied_row = mysql_fetch_array($lisatied_res);

				$query = "	UPDATE tilausrivin_lisatiedot
							SET 
							positio = if (positio = '', '$trivtyrow[selite]', positio),
							tilausrivilinkki	= '$tilausrivilinkki',
							toimittajan_tunnus	= '$toimittajan_tunnus',
							muutospvm			= now(),
							muuttaja			= '$kukarow[kuka]'
							WHERE yhtio			 = '$kukarow[yhtio]'
							and tilausrivitunnus = '$lisatty_tun'";
				$result = mysql_query($query) or pupe_error($query);
			}
			else {
				if($yhtiorow["tilauksen_jarjestys"] == "M") {
					$jarjestys = $lisatty_tun;
				}
				else {
					$jarjestys = 0;
				}
				$query = "	INSERT INTO tilausrivin_lisatiedot
							SET yhtio			= '$kukarow[yhtio]',
							positio 			= '$trivtyrow[selite]',
							tilausrivilinkki	= '$tilausrivilinkki',
							toimittajan_tunnus	= '$toimittajan_tunnus',
							tilausrivitunnus	= '$lisatty_tun',
							jarjestys			= '$jarjestys',
							luontiaika			= now(),
							laatija 			= '$kukarow[kuka]'";
				
				if ($trow["sarjanumeroseuranta"] == "S" and $kpl < 0) {
					$query .= " , osto_vai_hyvitys='O' ";	
				}
							
				$result = mysql_query($query) or pupe_error($query);
			}
			
			$lisatyt_rivit2[] = $lisatty_tun;

			if ($i == 0) {
				// lapsille oikea perheid
				$perheid_lisaarivi = $lisatty_tun;

				if ($varattu+$jt != 0 and ($myy_sarjatunnus != "" or $osto_sarjatunnus != "") and strtoupper($trow["tuoteno"]) == strtoupper($alkupera_trow["tuoteno"])) {

					if ($osto_sarjatunnus != "" or $varattu+$jt < 0) {
						$tunken = "ostorivitunnus";

						if ($osto_sarjatunnus != "") {
							$myy_sarjatunnus = $osto_sarjatunnus;
						}
					}
					else {
						$tunken = "myyntirivitunnus";
					}

					$query = "	SELECT yhtio, lisatieto, sarjanumero, kaytetty, tunnus
								FROM sarjanumeroseuranta
								WHERE tunnus 	IN ($myy_sarjatunnus)
								and tuoteno		= '$trow[tuoteno]'
								ORDER BY tunnus
								LIMIT ". (int) abs($varattu+$jt);
					$sres = mysql_query($query) or pupe_error($query);

					while($srow = mysql_fetch_array($sres)) {
						if ($var == "S") {
							// jos syˆtet‰‰n sarjanumertollinen tuote suoratoimitukseen niin liitet‰‰n t‰m‰ myyntirivi siihen kiinni
							$query = "	INSERT INTO sarjanumeroseuranta
										SET
										yhtio				= '$kukarow[yhtio]',
										tuoteno				= '$trow[tuoteno]',
										lisatieto			= 'Suoratoimitetaan yhtiˆst‰: $srow[yhtio]. $srow[lisatieto]',
										sarjanumero			= '$srow[sarjanumero]',
										kaytetty			= '$srow[kaytetty]',
										myyntirivitunnus 	= '$lisatty_tun'";
							$sarjares = mysql_query($query) or pupe_error($query);
						}
						else {
							$query = "	UPDATE sarjanumeroseuranta
										SET $tunken 		 = '$lisatty_tun'
										WHERE yhtio			 = '$kukarow[yhtio]'
										and tunnus			 = '$srow[tunnus]'
										and tuoteno			 = '$trow[tuoteno]'
										and $tunken 		 = 0";
							$sarjares = mysql_query($query) or pupe_error($query);
						}
						
						
						if ($srow["kaytetty"] == 'K') {
							$query = "	UPDATE tilausrivi
										SET alv=alv+500
										WHERE yhtio = '$kukarow[yhtio]'
										and tunnus  = '$lisatty_tun'
										and alv < 500";
							$sarjares = mysql_query($query) or pupe_error($query);
						}
					}

					$osto_sarjatunnus = "";
					$myy_sarjatunnus  = "";
				}
			}

			if (count($puu_tuoteno) > 1 and $laskurow['tila'] != 'O' and $lop == "") {
				//P‰ivitet‰‰n is‰n perhetunnus
				if ($rivityyppi == 'V') {
					$query  = "	SELECT tuoteno
								FROM tilausrivi
								WHERE yhtio	= '$kukarow[yhtio]'
								and tunnus	= $perheid_lisaarivi
								and tyyppi	= 'V'";
					$lisa_res = mysql_query($query) or pupe_error($query);
					$lisa_row = mysql_fetch_array($lisa_res);

					if (isset($lapsenlap_array[$lisa_row["tuoteno"]]) and strtoupper($lapsenlap_array[$lisa_row["tuoteno"]]) == strtoupper($lisa_row["tuoteno"])) {
						$valmistetyyppi = "M";
					}
					else {
						$valmistetyyppi = "W";
					}

					//jos on valmistusprosessi kyseess‰ niin is‰n tyyppi vaihdetaan W:ksi koska sen ei pid‰ varata saldoa!
					$query = "UPDATE tilausrivi SET perheid='$perheid_lisaarivi', tyyppi='W' where yhtio='$kukarow[yhtio]' and tunnus=$perheid_lisaarivi";
					$result = mysql_query($query) or pupe_error($query);

					$query = "UPDATE tilausrivi SET perheid='$perheid_lisaarivi' where yhtio='$kukarow[yhtio]' and tunnus=$lisatty_tun";
					$result = mysql_query($query) or pupe_error($query);
				}
				else {
					//Hoidetaan saldottomat is‰t samaan varastoon kuin lapsetkin jotta varastosplittaukset eiv‰t menisi hassusti
					$query = "UPDATE tilausrivi SET hyllyalue='$p_hyllyalue' where yhtio='$kukarow[yhtio]' and tunnus=$perheid_lisaarivi and hyllyalue=''";
					$result = mysql_query($query) or pupe_error($query);

					$query = "UPDATE tilausrivi SET perheid='$perheid_lisaarivi' where yhtio='$kukarow[yhtio]' and tunnus in ('$perheid_lisaarivi','$lisatty_tun')";
					$result = mysql_query($query) or pupe_error($query);
				}
			}
			$rivitunnus		= "";
			$kommentti 		= "";
			$tuotenimitys 	= "";
			$rivinumero		= "";
			$tilrivinumero	= "";
		}
	}
?>
