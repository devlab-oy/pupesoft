<?php


echo "	<SCRIPT LANGUAGE=JAVASCRIPT>
			function submit_tallenna(){
				document.paaformi.action='$PHP_SELF';
				document.paaformi.submit();
			}
			function submit_kohd_kaikki(){
				document.paaformi.action='$PHP_SELF?kohdista_koko_sivu=1';
				document.paaformi.submit();
			}
			function submit_kohd_eimit(){
				document.paaformi.action='$PHP_SELF?ala_kohdista_mitaan=1';
				document.paaformi.submit();
			}

		</SCRIPT>";
		
// haetaan keikan tiedot
$query    = "SELECT * FROM lasku WHERE tunnus='$otunnus' AND yhtio ='$kukarow[yhtio]'";
$result   = mysql_query($query) or pupe_error($query);
$laskurow = mysql_fetch_array($result);


//Kukarow kesken p‰ivitet‰‰n kohdistuksessa jotta kukaan ei voi samalla vied‰ varaston ja tehd‰ kohdistuksia
if ($kukarow["kesken"] == 0 or $kukarow["kesken"] != $laskurow["tunnus"]) {
	$query = "	UPDATE kuka
				SET kesken = '$laskurow[tunnus]'
				WHERE yhtio = '$kukarow[yhtio]' AND
				kuka = '$kukarow[kuka]' AND
				session = '$session'";
	$result = mysql_query($query) or pupe_error($query);
}

// katsotaan onko t‰lle keikalle jo liitetty vaihto-omaisuuslaskuja (kotimaa, eu tai ei-eu)
$query = "	SELECT sum(summa) summa, valkoodi, vienti
			FROM lasku
			WHERE yhtio='$kukarow[yhtio]'
			and tila='K'
			and laskunro='$laskurow[laskunro]'
			and vanhatunnus<>0
			and vienti in ('C','F','I','J','K','L')
			GROUP BY valkoodi, vienti";
$result = mysql_query($query) or pupe_error($query);

// jos on, haetaan liitettyjen laskujen
if (mysql_num_rows($result) == 1) {
	$kulurow = mysql_fetch_array($result);
}
else {
	$kulurow = array(); // muuten tyhj‰‰
}

//nyt kohdistus on onnistunut sentilleen. Ollan valmiita ja tulostetaan purkulistat
if ($tila == 'valmis') {

	// p‰ivitet‰‰n kohdistettu=K kun kohdistus on pennilleen ok...
	if ($laskunsumma == 0 and $laskurow["summa"] <> 0) {
		$query = "	UPDATE lasku
					SET kohdistettu='K'
					WHERE tunnus='$otunnus'
					and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
	}

	$query = "	UPDATE tilausrivi set toimaika='$laskurow[toimaika]' WHERE otunnus='$otunnus' and tyyppi='O' and yhtio='$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);

	//Vapautetaan 'tilaus'
	$query = "update kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
	$result = mysql_query($query) or pupe_error($query);

	$otunnus  = "";
	$toiminto = "";
	$tee 	  = "";
	$ytunnus  = $laskurow["ytunnus"];
}

//muokataan tilausrivi‰
if ($tila == 'muokkaa_rivi' and $tee != 'TY' and $tee != 'TI') {

	$query = "	SELECT tilausrivi.*, tuote.sarjanumeroseuranta
				FROM tilausrivi use index (PRIMARY)
				LEFT JOIN tuote use index (tuoteno_index) ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
				WHERE tilausrivi.tunnus = '$riviid'
				and tilausrivi.yhtio	= '$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		echo t("Tilausrivi‰ ei lˆydy")."! $query";
		exit;
	}
	$trow = mysql_fetch_array($result);

	$query = "	DELETE from tilausrivi
				WHERE tunnus = '$riviid' and yhtio='$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);

	// Tehd‰‰n pari juttua jos tuote on sarjanuerosaurannassa
	if($trow["sarjanumeroseuranta"] != '') {

		//Nollataan sarjanumero
		if ($trow["varattu"] > 0) {
			$tunken = "ostorivitunnus";
		}
		else {
			$tunken = "myyntirivitunnus";
		}

		$query = "	SELECT group_concat(tunnus) tunnukset
					FROM sarjanumeroseuranta
					WHERE yhtio = '$kukarow[yhtio]'
					and tuoteno = '$trow[tuoteno]'
					and $tunken = '$trow[tunnus]'";
		$sarjares = mysql_query($query) or pupe_error($query);
		$sarjarow = mysql_fetch_array($sarjares);

		//Pidet‰‰n sarjatunnus muistissa
		$osto_sarjatunnus = $sarjarow["tunnukset"];

		$query = "	UPDATE sarjanumeroseuranta
					SET $tunken = 0
					WHERE yhtio = '$kukarow[yhtio]'
					and tuoteno = '$trow[tuoteno]'
					and $tunken = '$trow[tunnus]'";
		$sarjares = mysql_query($query) or pupe_error($query);
	}


	$tuoteno 		= $trow["tuoteno"];
	$hinta 			= $trow["hinta"];
	$kpl 			= $trow["varattu"];
	$ale 			= $trow["ale"];
	$toimaika 		= $trow["toimaika"];
	$kerayspvm		= $trow["kerayspvm"];
	$alv 			= $trow["alv"];
	$var	 		= $trow["var"];
	$perheid2		= $trow["perheid2"];
	$kommentti 		= $trow["kommentti"];
	$rivinotunnus	= $trow["otunnus"];
	$rivitunnus		= $riviid;

	echo t("Muuta rivi‰").":<br>";

	//pidet‰‰n kaikki muuttujat tallessa
	$muut_siirrettavat = $alku."/".$nayta_kohdistetut."/".$ojarj."/".$nayta_kaikki."/".$suoratoimitukset."/".$toimittajaid;

	for($i = 0; $i < 10; $i++) {
		if ($haku[$i] != '') {
			$muut_siirrettavat .= "/$i#".$haku[$i];
		}
	}

	//rivien splittausvaihtoehtot n‰kyviin
	$automatiikka = 'ON';

	require('syotarivi_ostotilaus.inc');
	require('../inc/footer.inc');
	exit;
}

//Lis‰t‰‰n sarjanumeroita
if ($tila == "sarjanumerot") {
	//pidet‰‰n kaikki muuttujat tallessa
	$muut_siirrettavat = $alku."/".$nayta_kohdistetut."/".$ojarj."/".$nayta_kaikki."/".$suoratoimitukset."/".$toimittajaid;

	for($i = 0; $i < 10; $i++) {
		if ($haku[$i] != '') {
			$muut_siirrettavat .= "/$i#".$haku[$i];
		}
	}

	$PHP_SELF = "sarjanumeroseuranta.php";

	require("sarjanumeroseuranta.php");
	exit;
}


//kohdistetaan tiedostosta
if ($tila == 'FAILISTA') {
	require('ostotilausten_rivien_kohdistus_tiedostosta.inc');
}

//annetaan mahis tehd‰ kokonaan uusi rivi
if ($tila == 'uusirivi') {

	// Liitet‰‰n rivi t‰h‰n is‰riviin
	if ($perheid2 > 0) {
		$query = "UPDATE tilausrivi SET perheid2='$perheid2' where yhtio='$kukarow[yhtio]' and tunnus='$perheid2' and tyyppi='O'";
		$result = mysql_query($query) or pupe_error($query);
	}

	echo t("Tee uusi rivi").":<br>";

	//pidet‰‰n kaikki muuttujat tallessa
	$muut_siirrettavat = $alku."/".$nayta_kohdistetut."/".$ojarj."/".$nayta_kaikki."/".$suoratoimitukset."/".$toimittajaid;

	if(is_array($haku)) {
		foreach($haku as $i => $hakusana) {
			if ($haku[$i] != '') {
				$muut_siirrettavat .= "/$i#".$haku[$i];
			}
		}
	}

	$rivinotunnus = $otunnus;
	$toimaika = date('Y')."-".date('m')."-".date('d');

	require('syotarivi_ostotilaus.inc');
	require('../inc/footer.inc');
	exit;
}

//tarkastetaan tilausrivi
if ($tee == 'TI' or $tee == "Y") {
	// Parametreja joita tarkistarivi tarvitsee
	$laskurow["tila"] 	= "O";
	$toim_tarkistus 	= "KYLLA!";
	$prow["tuoteno"] 	= $tuoteno;
	$prow["var"]		= $rivinvar;
	$kukarow["kesken"]	= $rivinotunnus;

	if (checkdate($toimkka,$toimppa,$toimvva)) {
		$toimaika = $toimvva."-".$toimkka."-".$toimppa;
	}

	if ($hinta == "") {
		$query = "	SELECT *
					FROM tuotteen_toimittajat
					WHERE tuoteno		= '$prow[tuoteno]'
					and yhtio			= '$kukarow[yhtio]'
					and liitostunnus 	= '$laskurow[liitostunnus]'";
		$rarres1 = mysql_query($query) or pupe_error($query);
		$hinrow1 = mysql_fetch_array($rarres1);

		$prow["hinta"] = $hinrow1["ostohinta"];
	}
	else {
		$prow["hinta"] 		= $hinta;
	}

	$multi = "";
	require("../inc/tuotehaku.inc");
	$prow["tuoteno"] = $tuoteno;

	require('tarkistarivi_ostotilaus.inc');

	//n‰ytet‰‰n virhe ja annetaan mahis korjata se
	if ($varaosavirhe != '' or $tuoteno == "") {

		//rivien splittausvaihtoehtot n‰kyviin
		$automatiikka = 'ON';

		echo t("Muuta rivi‰").":<br>";
		require('syotarivi_ostotilaus.inc');
		require('../inc/footer.inc');
		exit;
	}

}

//rivi on tarkistettu ja se lisataan tietokantaan
if ($varaosavirhe == '' and ($tee == "TI" or $tee == "Y") and $tuoteno != '') {
	$laskurow["tila"] = "O";
	$kukarow["kesken"] = $rivinotunnus;
	//$trow saadaan tuota tarkistarivist‰

	require('lisaarivi.inc');

	$tuoteno 	= '';
	$tila 		= '';
	$tee 		= '';
}

//poistetaan rivi, tyhjennet‰‰n muuttujat, n‰ytet‰‰n lista
if ($tee == 'TY') {
	$tuoteno 	= '';
	$tila 		= '';
}

//korjataan siirrett‰v‰t muuttujat taas talteen
if (isset($muut_siirrettavat)) {
	$muut = explode('/',$muut_siirrettavat);

	$alku 				= $muut[0];
	$nayta_kohdistetut 	= $muut[1];
	$ojarj 				= $muut[2];
	$nayta_kaikki 		= $muut[3];
	$suoratoimitukset	= $muut[4];
	$toimittajaid 		= $muut[5];

	for($i = 4; $i < count($muut); $i++) {
		list($h,$s) = split('#', $muut[$i]);
		$haku[$h] = $s;
	}
}

if(isset($lisaa_lisavarusteita) and $lisaa_lisavarusteita > 0) {
	$query = "UPDATE tilausrivi set perheid2=0 WHERE tyyppi='O' and yhtio='$kukarow[yhtio]' and tunnus='$lisaa_lisavarusteita'";
	$result = mysql_query($query) or pupe_error($query);
}

//kohdistetaan rivej‰ laskulle
if ($tila == 'tee_kohdistus') {

	if ($ala_kohdista_mitaan != "") {
		unset($rivi_valitut_tunnukset);
	}

	if ($kohdista_koko_sivu != "") {
		$rivi_valitut_tunnukset = $rivi_kaikki_tunnukset;
	}

	//Kohdistetaan kaikki rivit
	if(count($rivi_kaikki_tunnukset) > 0) {
		for ($i=0; $i<sizeof($rivi_kaikki_tunnukset); $i++) {
				$tunnukset2 .= "'".$rivi_kaikki_tunnukset[$i]."',";
		}
		$tunnukset2 = substr($tunnukset2,0,-1);

		$query = "UPDATE tilausrivi use index (PRIMARY) SET uusiotunnus='0', kerattyaika='0', keratty='' WHERE tunnus in ($tunnukset2) AND yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
	}

	//Kohdistetaan yksi rivi
	if(is_array($rivi_valitut_tunnukset)) {
		foreach ($rivi_valitut_tunnukset as $valittu) {

			list($valittutunnus, $valittuotunnus) = explode("###", $valittu);

			$query = "UPDATE tilausrivi use index (yhtio_otunnus) SET uusiotunnus='$otunnus', kerattyaika=now(), keratty='$kukarow[kuka]' WHERE yhtio='$kukarow[yhtio]' and otunnus in ('$valittuotunnus','$otunnus') and (tunnus = '$valittutunnus' or perheid2 = '$valittutunnus')";
			$result = mysql_query($query) or pupe_error($query);
		}
	}
	$tila = '';

	// katotaan ollaanko muutettu hintaa tai kappaleita ja p‰ivitet‰‰n ne
	if(is_array($rivi_hinta)) {
		foreach ($rivi_hinta as $tunnus => $hinta) {
			$hinta  = str_replace(",",".", $hinta);
			$query  = "update tilausrivi set hinta='$hinta' where yhtio='$kukarow[yhtio]' and tunnus='$tunnus' and hinta<>'$hinta'";
			$result = mysql_query($query) or pupe_error($query);
		}
	}

	// katotaan ollaanko muutettualeja ja p‰ivitet‰‰n ne
	if(is_array($rivi_ale)) {
		foreach ($rivi_ale as $tunnus => $ale) {
			$ale  = str_replace(",",".", $ale);
			$query  = "update tilausrivi set ale='$ale' where yhtio='$kukarow[yhtio]' and tunnus='$tunnus' and ale<>'$ale'";
			$result = mysql_query($query) or pupe_error($query);
		}
	}

	if(is_array($rivi_kuluhinta)) {
		// katotaan ollaanko muutettu kuluhintaa tai kappaleita ja p‰ivitet‰‰n ne
		foreach ($rivi_kuluhinta as $tunnus => $kulu) {
			$kulu   = str_replace(",",".", $kulu);
			$query  = "update tilausrivi set kate='$kulu' where yhtio='$kukarow[yhtio]' and tunnus='$tunnus' and kate<>'$kulu'";
			$result = mysql_query($query) or pupe_error($query);
		}
	}

	if(is_array($rivi_kpl)) {
		foreach ($rivi_kpl as $tunnus => $kpl) {
			$kpl    = str_replace(",",".", $kpl);
			$query  = "	SELECT kpl, varattu, tuoteno
						FROM tilausrivi
						WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tunnus'";
			$result = mysql_query($query) or pupe_error($query);
			$rivi	= mysql_fetch_array($result);

			// jos t‰t‰ rivi‰ ei ole viel‰ viety varastoon ni p‰ivitet‰‰n varattu kentt‰‰
			if ($rivi["kpl"] == 0 and $rivi["varattu"] != 0 and $kpl != 0) {
				$query = "	UPDATE tilausrivi
							set varattu='$kpl'
							where yhtio='$kukarow[yhtio]'
							and tunnus='$tunnus'
							and varattu<>'$kpl'";
				$result = mysql_query($query) or pupe_error($query);
			}

			if($rivi["kpl"] == 0 and strtoupper($kpl) == "DEL") {
				$query = "	DELETE FROM tilausrivi
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus = '$tunnus'
							and tyyppi = 'O'";
				$result = mysql_query($query) or pupe_error($query);

				//Nollataan sarjanumero
				if ($rivi["varattu"] > 0) {
					$tunken = "ostorivitunnus";
				}
				else {
					$tunken = "myyntirivitunnus";
				}

				$query = "	UPDATE sarjanumeroseuranta
							SET $tunken = 0
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$rivi[tuoteno]'
							and $tunken = '$tunnus'";
				$sarjares = mysql_query($query) or pupe_error($query);
			}
		}
	}

	//Tehd‰‰n uusia lis‰varusterivej‰
	if(is_array($lisavarusterivi_kpl)) {
		foreach ($lisavarusterivi_kpl as $tunnus => $kpl) {
			$kpl	  = str_replace(",",".", $kpl);
			$perheid2 = substr($tunnus,0,strpos($tunnus,'##'));
			$tuoteno  = substr($tunnus,  strpos($tunnus,'##')+2, strpos($tunnus,'#*#')-strpos($tunnus,'##')-2);
			$hinta 	  = str_replace(",", ".", $lisavarusterivi_hinta[$tunnus]);
			$potunnus = substr($tunnus, strpos($tunnus,'#*#')+3);

			if (abs($kpl) > 0 or strtoupper($kpl) == "DEL") {
				if (abs($kpl) > 0) {
					$query  = "	SELECT *
								FROM tuote
								LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio=tuote.yhtio and tuotteen_toimittajat.tuoteno=tuote.tuoteno and tuotteen_toimittajat.liitostunnus='$toimittajaid'
								where tuote.yhtio = '$kukarow[yhtio]'
								and tuote.tuoteno = '$tuoteno'";
					$lisaresult = mysql_query($query) or pupe_error($query);
					$lisarow = mysql_fetch_array($lisaresult);

					if ($hinta == "") {
						$hinta = $lisarow["ostohinta"];
					}

					$query = "	insert into tilausrivi
								(perheid2, hinta, nimitys, tuoteno, tilkpl, varattu, otunnus, yhtio, tyyppi, laatija, laadittu) values
								('$perheid2','$hinta','$lisarow[nimitys]','$tuoteno', '$kpl', '$kpl', '$potunnus', '$kukarow[yhtio]', 'O', '$kukarow[kuka]', now())";
					$updre = mysql_query($query) or pupe_error($query);
				}
			}
		}

		$query = "UPDATE tilausrivi SET perheid2='$perheid2' where yhtio='$kukarow[yhtio]' and tunnus='$perheid2' and tyyppi='O'";
		$result = mysql_query($query) or pupe_error($query);
	}
}

//n‰ytet‰‰n rivilista ja ruksataan kohdistettavia rivej‰
if ($tila == '') {

	// jos erikoislale niin otetaan sit‰ kanssa huomioon
	if ($laskurow['erikoisale']<>0) {
		$erikoisale = 1-($laskurow['erikoisale']/100);
	}
	else {
		$erikoisale=1;
	}

	// jos ollaan liitetty jo vaihto-omaisuuslasku, k‰ytet‰‰n sen vientikentt‰‰
	if ($kulurow["vienti"] != "") $laskurow["vienti"] = $kulurow["vienti"];

	$alvti = " tuote.alv ";

	// jos kysess‰ on kotimainen vaihto-omaisuuslasku, pit‰‰ lis‰t‰ tuotteen hintaan alvi
	if ($laskurow['vienti'] == 'C' or $laskurow['vienti'] == 'J') {

		$alvit = "tuote.alv";

		if ($laskurow["maa"] != "" and $laskurow["maa"] != $yhtiorow["maa"]) {
			// tutkitaan ollaanko siell‰ alv-rekisterˆity
			$alhqur = "select * from yhtion_toimipaikat where yhtio='$kukarow[yhtio]' and maa='$laskurow[maa]' and vat_numero != ''";
			$alhire = mysql_query($alhqur) or pupe_error($alhqur);

			// ollaan alv-rekisterˆity
			if (mysql_num_rows($alhire) == 1) {
				$alvit = "tuotteen_alv.alv";
				$alvti = " ifnull(tuotteen_alv.alv, 0) ";
			}
		}
	}
	else {
		$alvit = 0;
	}

	$query = "	SELECT sum((tilausrivi.varattu+tilausrivi.kpl)*if(tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin) * tilausrivi.hinta * (1-(tilausrivi.ale/100)) * $erikoisale * 
				(1+(if((SELECT max(kaytetty) kaytetty 
						FROM sarjanumeroseuranta
						WHERE sarjanumeroseuranta.yhtio=tilausrivi.yhtio
						and sarjanumeroseuranta.tuoteno=tilausrivi.tuoteno 
						and ((tilausrivi.varattu+tilausrivi.kpl < 0 and sarjanumeroseuranta.myyntirivitunnus=tilausrivi.tunnus) or (tilausrivi.varattu+tilausrivi.kpl > 0 and sarjanumeroseuranta.ostorivitunnus=tilausrivi.tunnus))) = 'K', 0, $alvit)/100))) hinta
				FROM tilausrivi use index (uusiotunnus_index)
				JOIN tuote use index (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
				JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno AND tuotteen_toimittajat.liitostunnus = '$toimittajaid')
				LEFT JOIN tuotteen_alv ON (tuotteen_alv.yhtio = tilausrivi.yhtio AND tuotteen_alv.tuoteno = tilausrivi.tuoteno AND tuotteen_alv.maa = '$laskurow[maa]')
				WHERE tilausrivi.yhtio ='$kukarow[yhtio]'
				AND tilausrivi.uusiotunnus = '$otunnus'
				AND tilausrivi.tyyppi != 'D'";
	$result   = mysql_query($query) or pupe_error($query);
	$hintarow = mysql_fetch_array($result);

	// p‰ivitet‰‰n samantien t‰m‰ tieto laskulle
	$query   = "update lasku set summa='$hintarow[hinta]', kohdistettu='' where yhtio ='$kukarow[yhtio]' and tunnus='$otunnus'";
	$hinresu = mysql_query($query) or pupe_error($query);


	//Jos k‰ytt‰j‰ on syˆtt‰nyt alvillisen hinnan niin lis‰t‰‰n alvi
	if ((int) $laskurow["rahti_etu_alv"] != 0) {
		$laskurow["rahti_etu"] = round($laskurow["rahti_etu"]*(1+($laskurow["rahti_etu_alv"]/100)), 2);
	}
	
	$valittusumma 			= sprintf("%.2f", round($hintarow["hinta"],2));
	$laskunsumma  			= sprintf("%.2f", round($kulurow["summa"]-$laskurow["rahti_etu"]-$valittusumma, 2));
	$checksumma   			= $kulurow["summa"]-$laskurow["rahti_etu"]-$hintarow["hinta"];
	$kohdistettava_summa 	= sprintf("%.2f", round($kulurow["summa"] - $laskurow["rahti_etu"], 2));

	if ($laskunsumma != 0.00 and $checksumma < 0.01 and $checksumma > -0.01 and $kohdistettava_summa == $valittusumma) {
		$laskunsumma = '0.00';
	}

	//Tietokannan kent‰t‰ on m‰‰ritelt‰v‰ n‰in jotta meid‰n dynaamiset haut toimisivat
	$kentat_array = array(	"tilausrivi.otunnus otunnus",
							"tilausrivi.tuoteno tuoteno",
							"GROUP_CONCAT(DISTINCT tuotteen_toimittajat.toim_tuoteno ORDER BY toim_tuoteno SEPARATOR ',') toim_tuoteno",
							"tuote.nimitys nimitys",
							"tilausrivi.varattu+tilausrivi.kpl siskpl",
							"round((tilausrivi.varattu+tilausrivi.kpl)*if(tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin),4) ulkkpl",
							"tilausrivi.hinta hinta",
							"tilausrivi.ale ale",
							"$alvti alv",
							"round((tilausrivi.varattu+tilausrivi.kpl) * (tilausrivi.hinta * (1-(tilausrivi.ale/100))) * if(tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin),'$yhtiorow[hintapyoristys]') rivihinta",
							"round((tilausrivi.varattu+tilausrivi.kpl) * if(tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin) * (tilausrivi.hinta * (1-(tilausrivi.ale/100))) * $erikoisale * 
							(1+(if((SELECT max(kaytetty) kaytetty 
									FROM sarjanumeroseuranta
									WHERE sarjanumeroseuranta.yhtio=tilausrivi.yhtio
									and sarjanumeroseuranta.tuoteno=tilausrivi.tuoteno 
									and ((tilausrivi.varattu+tilausrivi.kpl < 0 and sarjanumeroseuranta.myyntirivitunnus=tilausrivi.tunnus) or (tilausrivi.varattu+tilausrivi.kpl > 0 and sarjanumeroseuranta.ostorivitunnus=tilausrivi.tunnus))) = 'K', 0, $alvit)/100)) ,'$yhtiorow[hintapyoristys]') alerivihinta",
							"tilausrivi.kate kuluhinta",
							"tilausrivi.toimaika toimaika",
							"tilausrivi.kommentti kommentti");

	$kennim_array = array(t('Tilaus'),t('Tuoteno'),t('Toim_tuoteno'),t('Nimitys'),t('Kpl'), t('Kpl/Ulk') ,t('Hinta'),t('Ale'),t('Alv'),t('Rivihinta'),t('+Alv -Ale'),t('Extrakulu'),t('Toimaika'), t('Kommentti'));
	$kenhak_array = array('otunnus','tuoteno','toim_tuoteno','nimitys','siskpl', 'ulkkpl' ,'hinta','ale','alv','rivihinta','alerivihinta','kuluhinta','toimaika', 'toimaika');
	$kenkok_array = array(6,15,15,17,7,7,7,4,4,8,8,4,12,12);

	if ($kukarow["resoluutio"] != 'I') {
		unset($kentat_array[3]);
		unset($kennim_array[3]);
		unset($kenhak_array[3]);
		unset($kenkok_array[3]);
	}

	foreach ($kenhak_array as $i => $kentta) {
		// tarkastetaan onko hakukent‰ss‰ jotakin
		if (strlen($haku[$i]) > 0) {
			$haku[$i] = str_replace(",",".",$haku[$i]);
			$lisa1 .= " $kentta like '%$haku[$i]%' and";

			//pidet‰‰n muistissa linkkej‰ varten
			$ulisa .= "&haku[$i]=$haku[$i]";

			//pidet‰‰n muistissa formeja varten
			$hiddenit .= "<input type='hidden' name='haku[$i]' value='$haku[$i]'>\n";
		}
	}

	//Kipan sivunvaihto
 	if (!isset($alku) or $alku == '') {
 		$alku = 0;
 	}

	$jarjestys = "";

	if (strlen($ojarj) > 0) {
		$jarjestys = $kenhak_array[$ojarj].", ";
	}

	$jarjestys .= ' tilausrivi.perheid2, tilausrivi.tunnus';

	$limit = '';
	$lisa2 = '';
	
	$alku = (int) $alku;
	
	if ($alku < 0) {
		$alku = 0;
	}
	
	if ($nayta_kohdistetut == "jees") {
		$limit = "";
		$lisa2 = " and tilausrivi.uusiotunnus in ('$otunnus') ";
	}
	elseif ($nayta_kohdistetut == "eitod") {
		$limit = " LIMIT $alku, 50 ";
		$lisa2 = " and tilausrivi.uusiotunnus in ('0') ";
	}
	else {
		$limit = " LIMIT $alku, 50 ";
		$lisa2 = " and tilausrivi.uusiotunnus in ('$otunnus','0') ";
	}

	if ($nayta_kaikki == "kaikki") {
		$limit = "";
	}

	if ($suoratoimitukset == "vaan") {
		$tilaajanrivinro = " and tilausrivi.tilaajanrivinro > 0 ";
	}

	$kentat = "";
	foreach($kentat_array as $kentta) {
		$kentat .= $kentta.", ";
	}

	if ($lisa1 != '') {
		$lisa1 = " HAVING ".substr($lisa1, 0, -3);
		$limit = "";
		$alku = 0;
	}

	$query = "	SELECT $kentat
				varattu,
				tilausrivi.perheid,
				tilausrivi.perheid2,
				tilausrivi.kpl varastossa_kpl,
				tilausrivi.tunnus rivitunnus,
				tilausrivi.uusiotunnus
				FROM lasku use index (yhtio_tila_liitostunnus_tapvm)
				JOIN tilausrivi use index (yhtio_otunnus) ON tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi='O' $tilaajanrivinro $lisa2
				JOIN tuote use index (tuoteno_index) ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
				JOIN tuotteen_toimittajat use index (yhtio_tuoteno) ON tuotteen_toimittajat.yhtio=tuote.yhtio and tuotteen_toimittajat.tuoteno=tuote.tuoteno and tuotteen_toimittajat.liitostunnus=lasku.liitostunnus
				LEFT JOIN tuotteen_alv ON (tuotteen_alv.yhtio = tilausrivi.yhtio AND tuotteen_alv.tuoteno = tilausrivi.tuoteno AND tuotteen_alv.maa = '$laskurow[maa]')
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				and lasku.liitostunnus = '$laskurow[liitostunnus]'
				and lasku.tila in ('O','K')
				GROUP BY tilausrivi.tunnus
				$lisa1
				ORDER BY $jarjestys
				$limit";
	$result = mysql_query($query) or pupe_error($query);

	echo "<table><tr><td class='back'>";

	// aloitellaan laskun otsikko taulu
	$chk1 = "";
	$chk2 = "";
	$chk3 = "";
	$chk4 = "";
	$chk5 = "";
	$chk6 = "";

	if ($nayta_kohdistetut == "jees") {
		$chk1 = "checked";
	}

	if ($nayta_kohdistetut == "eitod") {
		$chk2 = "checked";
	}

	if ($nayta_kaikki == "kaikki") {
		$chk3 = "checked";
	}

	if ($nayta_kohdistetut == "") {
		$chk4 = "checked";
	}

	if ($suoratoimitukset != "") {
		$chk6 = "checked";
	}

	if ($laskunsumma == 0 and $valittusumma <> 0) { // jos ollaan saatu kohdistus nollaan ja ollaan valittu jotain
		$ok = " color:#00FF00;";
	}
	else {
		$ok = "";
	}

	// jos kululaskua ei ole viel‰ liitetty, oletetaan ett‰ k‰ytet‰‰n toimittajan oletus valuuttaa
	if ($kulurow["valkoodi"] == "") $kulurow["valkoodi"] = $laskurow["valkoodi"];

	$query  = "select sum(kpl+varattu) kappaleet, count(*) rivit from tilausrivi where yhtio='$kukarow[yhtio]' and uusiotunnus='$laskurow[tunnus]' and tyyppi='O'";
	$kappaleetres = mysql_query($query) or pupe_error($query);
	$kappaleetrow = mysql_fetch_array($kappaleetres);
		
	//n‰ytet‰‰n mitk‰ n‰ytet‰‰n
	echo "<form action='$PHP_SELF' name='formi' method='post'>";
	echo "<input type='hidden' name='toiminto' value='kohdista'>";
	echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
	echo "<input type='hidden' name='alku' value='$alku'>";
	echo "<input type='hidden' name='otunnus' value='$otunnus'>";
	echo "<input type='hidden' name='tee' value='K'>";
	echo $hiddenit;
	echo "<input type='hidden' name='ojarj' value=\"$ojarj\">";

	echo "<table cellpadding='2'>
	<tr>
		<th>".t("Keikka")."</th><td>$laskurow[laskunro]</td>
		<th>".t("Laskun summa")."</th><td style='text-align:right'>$kulurow[summa] $kulurow[valkoodi]</td>
		<th>".t("Perusn‰kym‰").":</th><td><INPUT type='radio' $chk4 name='nayta_kohdistetut' value='' onclick='submit()'></td>
	</tr>
	<tr>
		<th>".t("Toimittaja")."</th><td>$laskurow[nimi]</td>
		<th>".t("Eturahti")."</th><td style='text-align:right'>$laskurow[rahti_etu] $kulurow[valkoodi]</td>
		<th>".t("N‰yt‰ kaikki kohdistetut").":</th><td><INPUT type='radio' $chk1 name='nayta_kohdistetut' value='jees' onclick='submit()'></td>
	</tr>
	<tr>
		<th>".t("N‰yt‰ lasku").":</th><td>";


	js_popup();

	$query = "	select l2.ebid, l2.nimi, l2.summa, l2.tunnus
				FROM lasku l1
				JOIN lasku l2 ON l1.yhtio=l2.yhtio and l1.vanhatunnus=l2.tunnus and l2.ebid!=''
				WHERE l1.yhtio		= '$kukarow[yhtio]'
				and l1.tila			= 'K'
				and l1.laskunro		= '$laskurow[laskunro]'
				and l1.vanhatunnus  > 0";
	$muutkeikres = mysql_query($query) or pupe_error($query);

	while ($muutkeikrow = mysql_fetch_array($muutkeikres)) {
		$ebid = $muutkeikrow['ebid'];
		echo ebid($muutkeikrow['tunnus']);
	}

	echo "</td>
		<th>".t("Kohdistettava summa")."</th><td style='text-align:right'>$kohdistettava_summa $kulurow[valkoodi]</td>
		<th>".t("N‰yt‰ vain kohdistamattomat").":</th><td><INPUT type='radio' $chk2 name='nayta_kohdistetut' value='eitod' onclick='submit()'></td>
	</tr>
	<tr>
		<th>".t("Erikoisalennus")."</th><td>$laskurow[erikoisale]</td>
		<th>".t("Kohdistettu summa (riv/kpl)")."</th><td style='text-align:right;$ok'> $valittusumma $kulurow[valkoodi] ($kappaleetrow[rivit]/".(float) $kappaleetrow['kappaleet'].")</td>
		<th>".t("N‰yt‰ kaikki yhdell‰ sivulla").":</th><td><INPUT type='checkbox' $chk3 name='nayta_kaikki' value='kaikki' onclick='submit()'></td>
	</tr>";

	echo "<tr><th>".t("N‰ytet‰‰n rivit")."</th>";


	if ($chk3 == "checked"){
			$sivu  = 1;
			$alku  = 0;
			$loppu = mysql_num_rows($result);
	}
	else {
		$loppu = $alku+50;
		$sivu = round($loppu/50,0);
	}

 	echo "<td>$alku - $loppu  ".t("Sivu").": $sivu</td>";

	echo "<th>".t("J‰ljell‰")."</th><td style='text-align:right;$ok'> $laskunsumma $kulurow[valkoodi]</td>";

	echo "<th>".t("N‰yt‰ vain suoratoimitukset").":</th><td><INPUT type='checkbox' $chk6 name='suoratoimitukset' value='vaan' onclick='submit()'></td>";
	echo "</form>";

	echo "</tr>";

	echo "</table>";

	// otsikko valmis
	
	echo "<table>";
	echo "<tr>";

	echo "<td class='back'><input type='button' onclick='submit_tallenna();' value='".t("Tallenna muutokset")."'></td>";
	echo "<td class='back'><input type='button' onclick='submit_kohd_kaikki();' value='".t("Kohdista kaikki rivit")."'></td>";
	echo "<td class='back'><input type='button' onclick='submit_kohd_eimit();' value='".t("Poista kaikki kohdistukset")."'></td>";

	echo "<form action='$PHP_SELF' method='post'>";
	echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
	echo "<input type='hidden' name='toiminto' value='kohdista'>";
	echo "<input type='hidden' name='tila' value='uusirivi'>";
	echo "<input type='hidden' name='tee' value='K'>";
	echo "<input type='hidden' name='alku' value='$alku'>";
	echo "<input type='hidden' name='nayta_kohdistetut' value='$nayta_kohdistetut'>";
	echo "<input type='hidden' name='nayta_kaikki' value='$nayta_kaikki'>";
	echo "<input type='hidden' name='suoratoimitukset' value='$suoratoimitukset'>";
	echo $hiddenit;
	echo "<input type='hidden' name='ojarj' value=\"$ojarj\">";
	echo "<input type='hidden' name='otunnus' value='$otunnus'>";
	echo "<td class='back'><input type='submit' value='".t("Tee uusi rivi")."'></td></form>";

	//n‰ytet‰‰n mitk‰ n‰ytet‰‰n
	echo "<form action='$PHP_SELF' name='formi' method='post'>";
	echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
	echo "<input type='hidden' name='toiminto' value='kohdista'>";
	echo "<input type='hidden' name='alku' value='$alku'>";
	echo "<input type='hidden' name='otunnus' value='$otunnus'>";
	echo "<input type='hidden' name='tee' value='$tee'>";
	echo "<input type='hidden' name='tila' value='FAILISTA'>";
	echo $hiddenit;
	echo "<input type='hidden' name='ojarj' value=\"$ojarj\">";
	//echo "<th>".t("Kohdista tiedostosta").":</th><td><input type='submit' value='".t("Tee se")."'></td></tr>";
	echo "<td class='back' colspan='2' align='right'><input type='submit' value='".t("Kohdista tiedostosta")."'></td>";
	echo "</form>";

	echo "<form action='$PHP_SELF' method='post'>";
	echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
	echo "<input type='hidden' name='toiminto' value='kohdista'>";
	echo "<input type='hidden' name='laskunsumma' value='$laskunsumma'>";
	echo "<input type='hidden' name='tee' value='K'>";
	echo "<input type='hidden' name='tila' value='valmis'>";
	echo "<input type='hidden' name='otunnus' value='$otunnus'>";
	echo "<td class='back'><input type='submit' value='".t("Kohdistus valmis")."'></td></form>";
	
	echo "</tr></table><br>";
	
	echo "<table>";
	
	echo "<tr>";

	//Sivunvaihdot
 	$seuraava  = $alku + 50;
 	$edellinen = $alku - 50;

 	if ($edellinen < 0) {
 		$edellinen = 0;
 	}

	$viimenen_sivu = mysql_num_rows($result)+$alku-50;

	echo 	"<td class='back' colspan='12' align='center'>
			<a href='$PHP_SELF?toiminto=kohdista&toimittajaid=$toimittajaid&tee=K&otunnus=$otunnus&alku=0&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&tila=$tila&ojarj=$ojarj$ulisa&suoratoimitukset=$suoratoimitukset'><<<</a>
			&nbsp;&nbsp;
			<a href='$PHP_SELF?toiminto=kohdista&toimittajaid=$toimittajaid&tee=K&otunnus=$otunnus&alku=$edellinen&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&suoratoimitukset=$suoratoimitukset&tila=$tila&ojarj=$ojarj$ulisa'><< ".t("Edellinen sivu")."</a>
			<a href='$PHP_SELF?toiminto=kohdista&toimittajaid=$toimittajaid&tee=K&otunnus=$otunnus&alku=$seuraava&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&suoratoimitukset=$suoratoimitukset&tila=$tila&ojarj=$ojarj$ulisa'>".t("Seuraava sivu")." >></a>
			&nbsp;&nbsp;
			<a href='$PHP_SELF?toiminto=kohdista&toimittajaid=$toimittajaid&tee=K&otunnus=$otunnus&alku=$viimenen_sivu&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&suoratoimitukset=$suoratoimitukset&tila=$tila&ojarj=$ojarj$ulisa'>>>></a>
			</td>";

	echo "</tr>";	
	
	echo "<tr></tr><tr><th>x</th>";

	//hakukent‰t
	echo "<form action='$PHP_SELF' method='post'>";
	echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
	echo "<input type='hidden' name='otunnus' value='$otunnus'>";
	echo "<input type='hidden' name='toiminto' value='kohdista'>";
	echo "<input type='hidden' name='alku' value='$alku'>";
	echo "<input type='hidden' name='tila' value='$tila'>";
	echo "<input type='hidden' name='tee' value='$tee'>";
	echo "<input type='hidden' name='nayta_kohdistetut' value='$nayta_kohdistetut'>";
	echo "<input type='hidden' name='nayta_kaikki' value='$nayta_kaikki'>";
	echo "<input type='hidden' name='suoratoimitukset' value='$suoratoimitukset'>";
	echo "<input type='hidden' name='ojarj' value='$ojarj'>";

	$href = "$PHP_SELF?toiminto=kohdista&toimittajaid=$toimittajaid&tee=K&otunnus=$otunnus&alku=$alku&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&suoratoimitukset=$suoratoimitukset&tila=$tila&ojarj";

	echo "<th nowrap valign='top'><a href='$href=0".$ulisa."'>$kennim_array[0]</a></th>";
	echo "<th nowrap valign='top'><a href='$href=1".$ulisa."'>$kennim_array[1]</a><br><a href='$href=2".$ulisa."'>$kennim_array[2]</a></th>";

	if ($kukarow["resoluutio"] == 'I') {
		echo "<th nowrap valign='top'><a href='$href=3".$ulisa."'>$kennim_array[3]</a></th>";
	}

	echo "<th nowrap valign='top'><a href='$href=4".$ulisa."'>$kennim_array[4]</a><br><a href='$href=5".$ulisa."'>$kennim_array[5]</a></th>";
	echo "<th nowrap valign='top'><a href='$href=6".$ulisa."'>$kennim_array[6]</a><br><a href='$href=6".$ulisa."'>".t("Valuutta")."</a></th>";
	echo "<th nowrap valign='top'><a href='$href=7".$ulisa."'>$kennim_array[7]</a><br><a href='$href=8".$ulisa."'>$kennim_array[8]</a></th>";
	echo "<th nowrap valign='top'><a href='$href=9".$ulisa."'>$kennim_array[9]</a><br><a href='$href=10".$ulisa."'>$kennim_array[10]</a></th>";
	echo "<th nowrap valign='top'><a href='$href=11".$ulisa."'>$kennim_array[11]</a><br><a href='$href=11".$ulisa."'>".t("Valuutta")."</a></th>";
	echo "<th nowrap valign='top'><a href='$href=12".$ulisa."'>$kennim_array[12]</a><br><a href='$href=13".$ulisa."'>$kennim_array[13]</a></th>";
	echo "<th></th>";
	echo "</tr>";
	echo "<tr><td></td>";

	echo "<td nowrap valign='top'><input type='text' size='$kenkok_array[0]' name='haku[0]' value='$haku[0]'></td>";
	echo "<td nowrap valign='top'><input type='text' size='$kenkok_array[1]' name='haku[1]' value='$haku[1]'><br><input type='text' size='$kenkok_array[2]' name='haku[2]' value='$haku[2]'></td>";

	if ($kukarow["resoluutio"] == 'I') {
		echo "<td nowrap valign='top'><input type='text' size='$kenkok_array[3]' name='haku[3]' value='$haku[3]'></td>";
	}

	echo "<td nowrap valign='top' align='right'><input type='text' size='$kenkok_array[4]' name='haku[4]' value='$haku[4]'><br><input type='text' size='$kenkok_array[5]' name='haku[5]' value='$haku[5]'></td>";
	echo "<td nowrap valign='top' align='right'><input type='text' size='$kenkok_array[6]' name='haku[6]' value='$haku[6]'></td>";
	echo "<td nowrap valign='top' align='right'><input type='text' size='$kenkok_array[7]' name='haku[7]' value='$haku[7]'><br><input type='text' size='$kenkok_array[8]' name='haku[8]' value='$haku[8]'></td>";
	echo "<td nowrap valign='top' align='right'><input type='text' size='$kenkok_array[9]' name='haku[9]' value='$haku[9]'><br><input type='text' size='$kenkok_array[10]' name='haku[10]' value='$haku[10]'></td>";
	echo "<td nowrap valign='top' align='right'><input type='text' size='$kenkok_array[11]' name='haku[11]' value='$haku[11]'></td>";
	echo "<td nowrap valign='top'><input type='text' size='$kenkok_array[12]' name='haku[12]' value='$haku[12]'><br><input type='text' size='$kenkok_array[13]' name='haku[13]' value='$haku[13]'></td>";


	echo "<td></td>";
	echo "<td class='back'><input type='submit' value='".t("Etsi")."'></td>";
	echo "</tr></form>";

	//kahtsotaan lˆytyikˆ yht‰‰n tilausrivi‰
	if (mysql_num_rows($result) == 0) {
		echo "<tr><td colspan='5' class='back'>".t("Yht‰‰n kohdistettavaa rivi‰ ei lˆydy")."!</td></tr>";
	}

	//itse rivit
	echo "	<SCRIPT LANGUAGE=JAVASCRIPT>
				function submit_juppe(ankkuri){
					document.paaformi.action='$PHP_SELF#'+ankkuri;
					document.paaformi.submit();
				}
			</SCRIPT>";

	echo "<form name='paaformi' method='post'>";
	echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
	echo "<input type='hidden' name='toiminto' value='kohdista'>";
	echo "<input type='hidden' name='otunnus' value='$otunnus'>";
	echo "<input type='hidden' name='alku' value='$alku'>";
	echo "<input type='hidden' name='tee' value='K'>";
	echo "<input type='hidden' name='nayta_kohdistetut' value='$nayta_kohdistetut'>";
	echo "<input type='hidden' name='nayta_kaikki' value='$nayta_kaikki'>";
	echo "<input type='hidden' name='suoratoimitukset' value='$suoratoimitukset'>";
	echo $hiddenit;
	echo "<input type='hidden' name='ojarj' value=\"$ojarj\">";
	echo "<input type='hidden' name='tila' value='tee_kohdistus'>\n\n";

	$rivilask = 0;

	while ($rivirow = mysql_fetch_array ($result)) {

		echo "<tr class='aktiivi'>";

		if ($rivirow["uusiotunnus"] > 0) {
			$chk = "checked";
			$td  = "td class='tumma'";
		}
		else {
			$chk = '';

			if ($rivirow["perheid2"] > 0 and $rivirow["perheid2"] != $rivirow["rivitunnus"]) {
				$td = "td class='spec'";
			}
			else {
				$td = "td";
			}
		}

		if ($rivirow["perheid"] > 0 or $rivirow["perheid2"] > 0) {
			if ($rivirow["perheid"] == 0) {
				$pklisa = " and perheid2 = '$rivirow[perheid2]'";
			}
			else {
				$pklisa = " and (perheid = '$rivirow[perheid]' or perheid2 = '$rivirow[perheid]')";
			}
		}

		if ($rivirow["rivitunnus"] == $rivirow["perheid2"] and $rivilask != 0) {
			echo "<tr><td class='back'><br></td></tr>";
		}

		$rivilask++;

		// Tuoteperheiden lapsille ei n‰ytet‰ rivinumeroa
		if ($rivirow["perheid"] == $rivirow["rivitunnus"] or ($rivirow["perheid2"] == $rivirow["rivitunnus"] and $rivirow["perheid"] == 0)) {
			$query = "	select count(*)
						from tilausrivi
						where yhtio = '$kukarow[yhtio]'
						and otunnus in ($rivirow[otunnus],$otunnus)
						$pklisa";
			$pkres = mysql_query($query) or pupe_error($query);
			$pkrow = mysql_fetch_array($pkres);

			$pknum 		= $pkrow[0];
			$borderlask = $pkrow[0];

		}
		elseif($rivirow["perheid"] == 0 and $rivirow["perheid2"] == 0) {
			$pknum 		= 0;
			$borderlask = 0;
		}


		$classlisa 	= "";
		$class 		= "";
		$classeka	= "";

		if($borderlask == 1 and $pknum == 1) {
			$classeka  = " style='border-top: 1px solid; border-left: 1px solid; border-bottom: 1px solid;' ";
			$classlisa = " style='border-top: 1px solid; border-bottom: 1px solid; border-right: 1px solid;' ";
			$class    .= " style=' border-top: 1px solid; border-bottom: 1px solid;' ";

			$borderlask--;
		}
		elseif($borderlask == $pknum and $pknum > 0) {
			$classeka  = " style='border-top: 1px solid; border-left: 1px solid; ";
			$classlisa = " style='border-top: 1px solid; border-right: 1px solid;' ";
			$class    .= " style='border-top: 1px solid;' ";
			$borderlask--;
		}
		elseif($borderlask == 1) {
			if ($pknum > 1) {
				$classeka  = " style='border-bottom: 1px solid; border-left: 1px solid; ";
				$classlisa =" style='font-style:italic; border-bottom: 1px solid; border-right: 1px solid;' ";
				$class    .= " style='font-style:italic; border-bottom: 1px solid;' ";
			}
			else {
				$classeka  = " style='border-bottom: 1px solid; border-left: 1px solid; ";
				$classlisa = $class." style='border-bottom: 1px solid; border-right: 1px solid;' ";
				$class    .= " style='border-bottom: 1px solid;' ";
			}

			$borderlask--;
		}
		elseif($borderlask > 0 and $borderlask < $pknum) {
			$classeka  = " style='border-left: 1px solid;' ";
			$classlisa = $class." style='font-style:italic; border-right: 1px solid;' ";
			$class    .= " style='font-style:italic;' ";
			$borderlask--;
		}

		// n‰ytet‰‰n vaan tsekpoksi jos rivi‰ saa viel‰ ru(n)kata...
		if ($rivirow["varastossa_kpl"] == 0) {
			echo "<$td $classeka valign='top'>";
			echo "<a name='$rivirow[rivitunnus]'>";

			//tsekataan is‰n status
			$query = "	select kpl
						from tilausrivi
						where yhtio = '$kukarow[yhtio]'
						and tunnus = '$rivirow[perheid2]'";
			$isapkres = mysql_query($query) or pupe_error($query);
			$isapkrow = mysql_fetch_array($isapkres);

			if ($rivirow["rivitunnus"] == $rivirow["perheid2"] or $rivirow["perheid2"] == 0 or $isapkrow["kpl"] != 0) {
				echo "<input type='checkbox' name='rivi_valitut_tunnukset[]' value='$rivirow[rivitunnus]###$rivirow[otunnus]' onclick='submit_juppe($rivirow[rivitunnus])' $chk>";
			}

			echo "<input type='hidden'   name='rivi_kaikki_tunnukset[]'  value='$rivirow[rivitunnus]###$rivirow[otunnus]'>";
			echo "<input type='hidden'   name='rivi_summa' value='$rivirow[alerivihinta]'>";
			echo "</$td>";

		}
		elseif($rivirow["varastossa_kpl"] != 0) {
			echo "<$td $classeka valign='top'>x</$td>";
		}
		else {
			echo "<$td $classeka valign='top'>KALA</$td>";
		}

		echo "<$td $class valign='top'>$rivirow[otunnus]</$td>";
		echo "<$td $class valign='top' nowrap>";

		// jos rivi‰ ei ole viel‰ viety varastoon niin rivi‰ voi muokata
		if ($rivirow["varastossa_kpl"] == 0) {
			echo "<a href='$PHP_SELF?toiminto=kohdista&toimittajaid=$toimittajaid&tee=K&otunnus=$otunnus&alku=$alku&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&suoratoimitukset=$suoratoimitukset&tila=$tila&ojarj=$ojarj$ulisa&riviid=$rivirow[rivitunnus]&tila=muokkaa_rivi'>$rivirow[tuoteno]</a>";
		}
		else {
			echo "$rivirow[tuoteno]";
		}

		echo "<br>$rivirow[toim_tuoteno]";

		$query = "	SELECT sarjanumeroseuranta
					FROM tuote
					WHERE yhtio = '$kukarow[yhtio]'
					and tuoteno = '$rivirow[tuoteno]'";
		$sarjares = mysql_query($query) or pupe_error($query);
		$sarjarow = mysql_fetch_array($sarjares);

		if ($sarjarow["sarjanumeroseuranta"] != "") {
			if ($rivirow["siskpl"] < 0) {
				$tunken = "myyntirivitunnus";
				$tunind = "yhtio_myyntirivi";
			}
			else {
				$tunken = "ostorivitunnus";
				$tunind = "yhtio_ostorivi";
			}

			if ($sarjarow["sarjanumeroseuranta"] == "S" or $sarjarow["sarjanumeroseuranta"] == "T") {
				$query = "	SELECT count(distinct sarjanumero) kpl, min(sarjanumero) sarjanumero
							FROM sarjanumeroseuranta
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$rivirow[tuoteno]'
							and $tunken = '$rivirow[rivitunnus]'";
			
				$snro_ok = t("S:nro ok");
				$snro	 = t("S:nro");
			}
			else {
				$query = "	SELECT sum(abs(era_kpl)) kpl, min(sarjanumero) sarjanumero
							FROM sarjanumeroseuranta
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$rivirow[tuoteno]'
							and $tunken = '$rivirow[rivitunnus]'";
			
				$snro_ok = t("E:nro ok");
				$snro	 = t("E:nro");
			}
						
			$sarjares = mysql_query($query) or pupe_error($query);
			$sarjarow = mysql_fetch_array($sarjares);

			if ($sarjarow["kpl"] == abs($rivirow["siskpl"])) {
				echo "<br>(<a href='$PHP_SELF?tila=sarjanumerot&toiminto=kohdista&toimittajaid=$toimittajaid&tuoteno=$rivirow[tuoteno]&ostorivitunnus=$rivirow[rivitunnus]&from=kohdista&tee=K&otunnus=$otunnus&alku=$alku&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&suoratoimitukset=$suoratoimitukset&ojarj=$ojarj$ulisa#$sarjarow[sarjanumero]' style='color:00FF00'>$snro_ok</font></a>)";
			}
			else {
				echo "<br>(<a href='$PHP_SELF?tila=sarjanumerot&toiminto=kohdista&toimittajaid=$toimittajaid&tuoteno=$rivirow[tuoteno]&ostorivitunnus=$rivirow[rivitunnus]&from=kohdista&tee=K&otunnus=$otunnus&alku=$alku&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&suoratoimitukset=$suoratoimitukset&ojarj=$ojarj$ulisa#$sarjarow[sarjanumero]' style='color:DD0000'>$snro</a>)";
			}
		}
		echo "</$td>";


		if ($kukarow["resoluutio"] == 'I') {
			echo "<$td $class valign='top'>".asana('nimitys_',$rivirow['tuoteno'],$rivirow['nimitys'])."</$td>";
		}

		// jos rivi‰ ei ole viel‰ viety varastoon, voidaan muuttaa kappaleita
		if ($rivirow["varattu"] != 0) {
			echo "<$td $class valign='top' align='right'><input size='5' type='text' name='rivi_kpl[$rivirow[rivitunnus]]' value='". (float) $rivirow["siskpl"]."' style='text-align:right'><br>". (float) $rivirow["ulkkpl"]."</$td>";
		}
		else {
			echo "<$td $class valign='top' align='right'>". (float) $rivirow["siskpl"]."<br>". (float) $rivirow["ulkkpl"]."</$td>";
		}

		echo "<$td $class valign='top' align='right'><input size='7' type='text' name='rivi_hinta[$rivirow[rivitunnus]]' value='$rivirow[hinta]' style='text-align:right'><br>$kulurow[valkoodi]</$td>";
		echo "<$td $class valign='top' align='right'><input size='4' type='text' name='rivi_ale[$rivirow[rivitunnus]]' value='". (float) $rivirow["ale"]."' style='text-align:right'>";

		if ($laskurow['vienti']=='C' or $laskurow['vienti']=='J') {
			echo "<br>". (float) $rivirow["alv"];
		}
		else {
			echo "<br>0";
		}
		echo "</$td>";


		echo "<$td $class valign='top' align='right'>". (float) $rivirow["rivihinta"]."<br>". (float) $rivirow["alerivihinta"]."</$td>";
		echo "<$td $class valign='top' align='right'><input size='4' type='text' name='rivi_kuluhinta[$rivirow[rivitunnus]]' value='$rivirow[kuluhinta]' style='text-align:right'><br>$yhtiorow[valkoodi]</$td>";
		echo "<$td $class valign='top'>";

		if ($rivirow["toimaika"] != "0000-00-00") {
			echo tv1dateconv($rivirow["toimaika"]);
		}
		
		//Tutkitaan lˆytyykˆ JT-rivi joka m‰pp‰ytyy t‰h‰n ostoriviin
	   	$query = "	SELECT tilausrivi.otunnus, lasku.nimi, tilausrivin_lisatiedot.suoraan_laskutukseen
	   				FROM tilausrivin_lisatiedot
	   				JOIN tilausrivi ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus)
					JOIN lasku ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus)
	   				WHERE tilausrivi.yhtio = '$kukarow[yhtio]' 
	   				and tilausrivin_lisatiedot.tilausrivilinkki  = '$rivirow[rivitunnus]'";
		$varastoon_result = mysql_query($query) or pupe_error($query);
		
		if ($varastoon_row = mysql_fetch_array($varastoon_result)) {
			if($varastoon_row["suoraan_laskutukseen"] != "") {
				$rivirow["kommentti"] .= "<br>".t("Suoratoimitus asiakkaalle").": ".$varastoon_row["nimi"];
			}
			else {
				$rivirow["kommentti"] .= "<br>".t("Tilattu asiakkaalle").": ".$varastoon_row["nimi"];
			}
		}
		
		echo "<br>$rivirow[kommentti]</$td>";

		//Tutkitaan tuotteiden lis‰varusteita
		$query  = "	SELECT *
					FROM tuoteperhe
					JOIN tuote ON tuoteperhe.yhtio=tuote.yhtio and tuoteperhe.tuoteno=tuote.tuoteno
				 	JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio=tuote.yhtio and tuotteen_toimittajat.tuoteno=tuote.tuoteno and tuotteen_toimittajat.liitostunnus='$toimittajaid'
					where tuoteperhe.yhtio = '$kukarow[yhtio]'
					and tuoteperhe.isatuoteno = '$rivirow[tuoteno]'
					and tuoteperhe.tyyppi = 'L'
					order by tuoteperhe.tuoteno";
		$lisaresult = mysql_query($query) or pupe_error($query);

		echo "<$td $classlisa valign='top'>";

		if ($rivirow["rivitunnus"] == $rivirow["perheid2"] and mysql_num_rows($lisaresult) > 0) {
			echo "<a href='$PHP_SELF?toiminto=kohdista&toimittajaid=$toimittajaid&tee=K&otunnus=$otunnus&alku=$alku&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&suoratoimitukset=$suoratoimitukset&tila=$tila&ojarj=$ojarj$ulisa&lisaa_lisavarusteita=$rivirow[rivitunnus]'>".t("Lis‰varusteet")."</a><br>";
		}

		if ($rivirow["rivitunnus"] == $rivirow["perheid2"] or $rivirow["perheid2"] == 0) {
			echo "<a href='$PHP_SELF?toiminto=kohdista&toimittajaid=$toimittajaid&tee=K&otunnus=$otunnus&alku=$alku&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&suoratoimitukset=$suoratoimitukset&tila=$tila&ojarj=$ojarj$ulisa&riviid=$rivirow[rivitunnus]&tila=uusirivi&perheid2=$rivirow[rivitunnus]'>".t("Liit‰ rivi")."</a>";
		}

		echo "</$td>";

		if (mysql_num_rows($lisaresult) > 0 and $rivirow["perheid2"] == 0) {

			echo "</tr>\n";

			while($lisavar_row = mysql_fetch_array($lisaresult)) {
				echo "<tr>
						<$td $class valign='top' colspan='2' class='back'></$td>
						<$td $class valign='top'>$lisavar_row[tuoteno]<br>$lisavar_row[toim_tuoteno]</$td>";

				if ($kukarow["resoluutio"] == 'I') {
					echo "<$td $class valign='top'>".asana('nimitys_',$lisavar_row['tuoteno'],$lisavar_row['nimitys'])."</$td>";
				}

				echo "<$td $class valign='top' align='right'><input size='5' type='text' name='lisavarusterivi_kpl[$rivirow[rivitunnus]##$lisavar_row[tuoteno]#*#$rivirow[otunnus]]' style='text-align:right'></$td>";
				echo "<$td $class valign='top' align='right'><input size='7' type='text' name='lisavarusterivi_hinta[$rivirow[rivitunnus]##$lisavar_row[tuoteno]#*#$rivirow[otunnus]]' style='text-align:right'><br>$kulurow[valkoodi]</$td>";
				echo "</tr>";
			}
		}
	}

 	echo "<tr><td class='back' colspan='12' align='center'>";
	echo "<a href='$PHP_SELF?toiminto=kohdista&toimittajaid=$toimittajaid&tee=K&otunnus=$otunnus&alku=$edellinen&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&suoratoimitukset=$suoratoimitukset&tila=$tila&ojarj=$ojarj$ulisa'><< ".t("Edellinen sivu")."</a> ";
	echo "&nbsp;&nbsp;";
 	echo "<a href='$PHP_SELF?toiminto=kohdista&toimittajaid=$toimittajaid&tee=K&otunnus=$otunnus&alku=$seuraava&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&suoratoimitukset=$suoratoimitukset&tila=$tila&ojarj=$ojarj$ulisa'>".t("Seuraava sivu")." >></a>";
	echo "</th></tr>";
	echo "</table>";

	echo "<br>";
	

	echo "<table>";
	echo "<tr>";

	echo "<td class='back'><input type='submit' value='".t("Tallenna muutokset")."'></td>";
	echo "<td class='back'><input type='submit' name='kohdista_koko_sivu' onclick='submit();' value='".t("Kohdista kaikki rivit")."'></td>";
	echo "<td class='back'><input type='submit' name='ala_kohdista_mitaan' value='".t("Poista kaikki kohdistukset")."'></td>";

	echo "</form>";


	echo "<form action='$PHP_SELF' method='post'>";
	echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
	echo "<input type='hidden' name='toiminto' value='kohdista'>";
	echo "<input type='hidden' name='tila' value='uusirivi'>";
	echo "<input type='hidden' name='tee' value='K'>";
	echo "<input type='hidden' name='alku' value='$alku'>";
	echo "<input type='hidden' name='nayta_kohdistetut' value='$nayta_kohdistetut'>";
	echo "<input type='hidden' name='nayta_kaikki' value='$nayta_kaikki'>";
	echo "<input type='hidden' name='suoratoimitukset' value='$suoratoimitukset'>";
	echo $hiddenit;
	echo "<input type='hidden' name='ojarj' value=\"$ojarj\">";
	echo "<input type='hidden' name='otunnus' value='$otunnus'>";
	echo "<td class='back'><input type='submit' value='".t("Tee uusi rivi")."'></td></form>";
	
	//n‰ytet‰‰n mitk‰ n‰ytet‰‰n
	echo "<form action='$PHP_SELF' name='formi' method='post'>";
	echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
	echo "<input type='hidden' name='toiminto' value='kohdista'>";
	echo "<input type='hidden' name='alku' value='$alku'>";
	echo "<input type='hidden' name='otunnus' value='$otunnus'>";
	echo "<input type='hidden' name='tee' value='$tee'>";
	echo "<input type='hidden' name='tila' value='FAILISTA'>";
	echo $hiddenit;
	echo "<input type='hidden' name='ojarj' value=\"$ojarj\">";
	//echo "<th>".t("Kohdista tiedostosta").":</th><td><input type='submit' value='".t("Tee se")."'></td></tr>";
	echo "<td class='back' colspan='2' align='right'><input type='submit' value='".t("Kohdista tiedostosta")."'></td>";
	echo "</form>";

	echo "<form action='$PHP_SELF' method='post'>";
	echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
	echo "<input type='hidden' name='toiminto' value='kohdista'>";
	echo "<input type='hidden' name='laskunsumma' value='$laskunsumma'>";
	echo "<input type='hidden' name='tee' value='K'>";
	echo "<input type='hidden' name='tila' value='valmis'>";
	echo "<input type='hidden' name='otunnus' value='$otunnus'>";
	echo "<td class='back'><input type='submit' value='".t("Kohdistus valmis")."'></td></form>";

	$tila="eityhja";

	echo "</tr></table>";
}

?>
