<?php

// haetaan keikan tiedot
$query    = "SELECT * FROM lasku WHERE tunnus='$otunnus' AND yhtio ='$kukarow[yhtio]'";
$result   = mysql_query($query) or pupe_error($query);
$laskurow = mysql_fetch_array($result);

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
		$query = "	UPDATE lasku set kohdistettu='K'
					WHERE tunnus='$otunnus' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
	}

	$query = "	UPDATE tilausrivi set toimaika='$laskurow[toimaika]' WHERE otunnus='$otunnus' and tyyppi='O' and yhtio='$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);

	$otunnus  = "";
	$toiminto = "";
	$tee 	  = "";
	$ytunnus  = $laskurow["ytunnus"];
}

//muokataan tilausrivi‰
if ($tila == 'muokkaa_rivi') {
	$query = "	SELECT *
				FROM tilausrivi
				WHERE tunnus = '$riviid' and yhtio='$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		echo t("Tilausrivi‰ ei lˆydy")."! $query";
		exit;
	}
	$trow = mysql_fetch_array($result);

	$query = "	DELETE from tilausrivi
				WHERE tunnus = '$riviid' and yhtio='$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);

	$tuoteno 		= $trow["tuoteno"];
	$hinta 			= $trow["hinta"];
	$kpl 			= $trow["varattu"];
	$ale 			= $trow["ale"];
	$toimaika 		= $trow["toimaika"];
	$kerayspvm		= $trow["kerayspvm"];
	$alv 			= $trow["alv"];
	$var	 		= $trow["var"];
	$perheid		= $trow["perheid"];
	$kommentti 		= $trow["kommentti"];
	$rivinotunnus	= $trow["otunnus"];
	$rivitunnus		= $riviid;

	echo t("Muuta rivi‰").":<br>";

	//pidet‰‰n kaikki muuttujat tallessa
	$muut_siirrettavat = $alku."/".$nayta_kohdistetut."/".$ojarj."/".$nimitykset."/".$nayta_kaikki."/".$toimittajaid;

	for($i = 0; $i < 10; $i++) {
		if ($haku[$i] != '') {
			$muut_siirrettavat .= "/$i#".$haku[$i];
		}
	}

	//rivien splittausvaihtoehtot n‰kyviin
	$automatiikka = 'ON';

	require('syotarivi_ostotilaus.inc');
	exit;
}

//Lis‰t‰‰n sarjanumeroita
if ($tila == "sarjanumerot") {
	//pidet‰‰n kaikki muuttujat tallessa
	$muut_siirrettavat = $alku."/".$nayta_kohdistetut."/".$ojarj."/".$nimitykset."/".$nayta_kaikki."/".$toimittajaid;

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

	echo t("Tee uusi rivi").":<br>";

	//pidet‰‰n kaikki muuttujat tallessa
	$muut_siirrettavat = $alku."/".$nayta_kohdistetut."/".$ojarj."/".$nimitykset."/".$nayta_kaikki."/".$toimittajaid;

	if(is_array($haku)) {
		foreach($haku as $i => $hakusana) {
			if ($haku[$i] != '') {
				$muut_siirrettavat .= "/$i#".$haku[$i];
			}
		}
	}

	if ($urotunnus != 0) {
		$rivinotunnus = $urotunnus;
	}
	else {
		$rivinotunnus = $otunnus;
	}
	$toimaika = date('Y')."-".date('n')."-".date('d');

	require('syotarivi_ostotilaus.inc');
	require('../inc/footer.inc');
	exit;
}

//tarkastetaan tilausrivi
if ($tee == 'TI' or $tee == "Y") {
	// Parametreja joita tarkistarivi tarvitsee
	$laskurow["tila"] 	= "O";
//	$toim_tarkistus 	= "EI"; // miksi ei haluttaisi tarkistaa???
	$toim_tarkistus 	= "KYLLA!";
	$prow["tuoteno"] 	= $tuoteno;
	$prow["var"]		= $rivinvar;
	$kukarow["kesken"]	= $rivinotunnus;

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

	require('tarkistarivi_ostotilaus.inc');

	//n‰ytet‰‰n virhe ja annetaan mahis korjata se
	if ($varaosavirhe != '') {

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
	$nimitykset 		= $muut[3];
	$nayta_kaikki 		= $muut[4];
	$toimittajaid 		= $muut[5];

	for($i = 5; $i < count($muut); $i++) {
		list($h,$s) = split('#', $muut[$i]);
		$haku[$h] = $s;
	}
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

		$query = "UPDATE tilausrivi SET uusiotunnus='0' WHERE tunnus in ($tunnukset2)  AND yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
	}

	//Kohdistetaan yksi rivi
	if(count($rivi_valitut_tunnukset) > 0) {
		for ($i=0; $i<sizeof($rivi_valitut_tunnukset); $i++) {
			$tunnukset .= "'".$rivi_valitut_tunnukset[$i]."',";
		}
		$tunnukset = substr($tunnukset,0,-1);

		$query = "UPDATE tilausrivi SET uusiotunnus='$otunnus' WHERE tunnus IN ($tunnukset) AND yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
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
			$query  = "select kpl, varattu from tilausrivi where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
			$result = mysql_query($query) or pupe_error($query);
			$rivi	= mysql_fetch_array($result);

			// jos t‰t‰ rivi‰ ei ole viel‰ viety varastoon ni p‰ivitet‰‰n varattu kentt‰‰
			if ($rivi["kpl"] == 0 and $rivi["varattu"] != 0 and $kpl != 0) {
				$query = "update tilausrivi set varattu='$kpl' where yhtio='$kukarow[yhtio]' and tunnus='$tunnus' and varattu<>'$kpl'";
				$result = mysql_query($query) or pupe_error($query);
			}

			// jos t‰m‰ rivi on jo viety varastoon ni p‰ivitet‰‰n kpl kentt‰‰
			if ($rivi["kpl"] != 0 and $rivi["varattu"] == 0 and $kpl != 0) {
				$query = "update tilausrivi set kpl='$kpl' where yhtio='$kukarow[yhtio]' and tunnus='$tunnus' and kpl<>'$kpl'";
				$result = mysql_query($query) or pupe_error($query);
			}
			
			if(strtoupper($kpl) == "DEL") {
				$query = "DELETE FROM tilausrivi WHERE yhtio='$kukarow[yhtio]' and tunnus='$tunnus' and tyyppi='O'";
				$result = mysql_query($query) or pupe_error($query);
			}
		}
	}
	
	
	if(is_array($lisavarusterivi_kpl)) {
		foreach ($lisavarusterivi_kpl as $tunnus => $kpl) {
			$kpl	  = str_replace(",",".", $kpl);			
			$perheid  = substr($tunnus,0,strpos($tunnus,'##'));
			$tuoteno  = substr($tunnus,  strpos($tunnus,'##')+2, strpos($tunnus,'#*#')-strpos($tunnus,'##')-2);
			$hinta 	  = $lisavarusterivi_hinta[$tunnus];
			$potunnus = substr($tunnus, strpos($tunnus,'#*#')+3);
			
			if ($kpl > 0 or strtoupper($kpl) == "DEL") {				
				if ($kpl > 0) {
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
								(perheid, hinta, nimitys, tuoteno, tilkpl, varattu, otunnus, yhtio, tyyppi) values
								('$perheid','$hinta','$lisarow[nimitys]','$tuoteno', '$kpl', '$kpl', '$potunnus', '$kukarow[yhtio]', 'O')";
					$updre = mysql_query($query) or pupe_error($query);
				}
				
				$query = "UPDATE tilausrivi SET perheid='$perheid' where yhtio='$kukarow[yhtio]' and tunnus='$perheid' and tyyppi='O'";
				$result = mysql_query($query) or pupe_error($query);
			}			
		}
	}
			
	if(is_array($lisaa_lisavarusteita)) {
		foreach ($lisaa_lisavarusteita as $tunnus => $nimi) {
			$query = "	UPDATE tilausrivi set perheid=0 WHERE tyyppi='O' and yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
			$result = mysql_query($query) or pupe_error($query);
		}
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

	// jos kysess‰ on kotimainen vaihto-omaisuuslasku, pit‰‰ lis‰t‰ tuotteen hintaan alvi
	if ($laskurow['vienti']=='C' or $laskurow['vienti']=='J') {
		$alvit = " * (1+tuote.alv/100) ";
	}
	else {
		$alvit = "";
	}

	$query    = "SELECT sum((tilausrivi.varattu+tilausrivi.kpl)*if(tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin) * tilausrivi.hinta * (1-(tilausrivi.ale/100)) * $erikoisale $alvit) hinta
				FROM tilausrivi use index (uusiotunnus_index), tuote use index (tuoteno_index), tuotteen_toimittajat
				WHERE uusiotunnus='$otunnus' AND tilausrivi.yhtio ='$kukarow[yhtio]' AND liitostunnus = '$toimittajaid'
				AND tuote.yhtio=tilausrivi.yhtio AND tilausrivi.yhtio = tuotteen_toimittajat.yhtio AND tuote.tuoteno=tilausrivi.tuoteno AND tilausrivi.tuoteno = tuotteen_toimittajat.tuoteno";
	$result   = mysql_query($query) or pupe_error($query);
	$hintarow = mysql_fetch_array($result);

	// p‰ivitet‰‰n samantien t‰m‰ tieto laskulle
	$query   = "update lasku set summa='$hintarow[hinta]', kohdistettu='' where yhtio ='$kukarow[yhtio]' and tunnus='$otunnus'";
	$hinresu = mysql_query($query) or pupe_error($query);

	$valittusumma = $hintarow["hinta"];
	$laskunsumma  = sprintf("%.2f",round($kulurow["summa"]-$laskurow["rahti_etu"]-$valittusumma,2));
	$checksumma   = $kulurow["summa"]-$laskurow["rahti_etu"]-$hintarow["hinta"];
	$kohdistettava_summa = sprintf("%.2f",round($kulurow["summa"] - $laskurow["rahti_etu"],2));
	$valittusumma = sprintf("%.2f",round($valittusumma,2));
	
	if ($laskunsumma != 0.00 and $checksumma < 0.01 and $checksumma > -0.01 and $kohdistettava_summa == $valittusumma) {
		$laskunsumma = '0.00';
	}
	
	//Tietokannan kent‰t‰ obn m‰‰ritelt‰v‰ n‰in jotta meid‰n dynaamiset haut toimisivat
	$kentat_array = array(	"tilausrivi.otunnus otunnus",
							"tilausrivi.tuoteno tuoteno",
							"GROUP_CONCAT(DISTINCT tuotteen_toimittajat.toim_tuoteno ORDER BY toim_tuoteno SEPARATOR ',') toim_tuoteno",
							"tuote.nimitys nimitys",
							"tilausrivi.varattu+tilausrivi.kpl siskpl",
							"round((tilausrivi.varattu+tilausrivi.kpl)*if(tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin),4) ulkkpl",
							"tilausrivi.hinta hinta",
							"tilausrivi.ale ale",
							"tuote.alv alv",
							"round((tilausrivi.varattu+tilausrivi.kpl) * (tilausrivi.hinta * (1-(tilausrivi.ale/100))) * if(tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin),2) rivihinta",
							"round((tilausrivi.varattu+tilausrivi.kpl) * if(tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin) * (tilausrivi.hinta * (1-(tilausrivi.ale/100))) * $erikoisale $alvit ,2) alerivihinta",
							"tilausrivi.kate kuluhinta",
							"tilausrivi.toimaika toimaika",
							"tilausrivi.kommentti kommentti");

	$kennim_array = array(t('Tilaus'),t('Tuoteno'),t('Toim_tuoteno'),t('Nimitys'),t('Kpl'), t('Kpl/Ulk') ,t('Hinta'),t('Ale'),t('Alv'),t('Rivihinta'),t('+Alv -Ale'),t('Extrakulu'),t('Toimaika'), t('Kommentti'));
	$kenhak_array = array('otunnus','tuoteno','toim_tuoteno','nimitys','siskpl', 'ulkkpl' ,'hinta','ale','alv','rivihinta','alerivihinta','kuluhinta','toimaika', 'toimaika');
	$kenkok_array = array(6,12,10,17,5,4,7,4,4,8,8,4,7,8);

	if ($nimitykset == "") {
		unset($kentat_array[3]);
		unset($kennim_array[3]);
		unset($kenhak_array[3]);
		unset($kenkok_array[3]);
	}

	foreach ($kenhak_array as $i => $kentta) {
		// tarkastetaan onko hakukent‰ss‰ jotakin
		if (strlen($haku[$i]) > 0) {
			$haku[$i] = str_replace(",",".",$haku[$i]);
			$lisa .= " $kentta like '%$haku[$i]%' and";

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

	if (strlen($ojarj) > 0) {
		$jarjestys = $kenhak_array[$ojarj].", ";
	}
	$jarjestys .= ' tilausrivi.tunnus';

	$limit = '';
	$lisa2 = '';

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

	$kentat = "";
	foreach($kentat_array as $kentta) {
		$kentat .= $kentta.", ";
	}

	if ($lisa != '') {
		$lisa = " HAVING ".substr($lisa, 0, -3);
	}

	// T‰ll‰ queryll‰ saadaan vain tuoteperheiden is‰t
	$query = "	SELECT $kentat
				varattu,
				tilausrivi.perheid,
				tilausrivi.tunnus rivitunnus,
				tilausrivi.uusiotunnus
				FROM lasku use index (yhtio_tila_liitostunnus_tapvm)
				JOIN tilausrivi use index (yhtio_otunnus) ON tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi='O' and (tilausrivi.perheid=0 or tilausrivi.perheid=tilausrivi.tunnus)
				JOIN tuote use index (tuoteno_index) ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
				JOIN tuotteen_toimittajat use index (yhtio_tuoteno) ON tuotteen_toimittajat.yhtio=tuote.yhtio and tuotteen_toimittajat.tuoteno=tuote.tuoteno and tuotteen_toimittajat.liitostunnus=lasku.liitostunnus
				WHERE lasku.liitostunnus='$laskurow[liitostunnus]'
				and lasku.yhtio = '$kukarow[yhtio]'
				and lasku.tila in ('O','K')
				and lasku.alatila not in ('X','B')
				$lisa2
				GROUP BY tilausrivi.tunnus
				$lisa
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

	if ($nimitykset != "") {
		$chk5 = "checked";
	}

	if ($laskunsumma == 0 and $valittusumma <> 0) { // jos ollaan saatu kohdistus nollaan ja ollaan valittu jotain
		$ok1 = "<font color='#00FF00'>";
		$ok2 = "</font>";
	}
	else {
		$ok1 = "";
		$ok2 = "";
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
		<th>".t("Laskunumero")."</th><td>$laskurow[tunnus] / $laskurow[laskunro]</td>
		<th>".t("Laskun summa")."</th><td style='text-align:right'>$kulurow[summa] $kulurow[valkoodi]</td>
		<th>".t("Perusn‰kym‰").":</th><td><INPUT type='radio' $chk4 name='nayta_kohdistetut' value='' onclick='submit()'></td>
	</tr>
	<tr>
		<th>".t("Toimittaja")."</th><td>$laskurow[nimi]</td>
		<th>".t("Eturahti")."</th><td style='text-align:right'>$laskurow[rahti_etu] $kulurow[valkoodi]</td>
		<th>".t("N‰yt‰ kaikki kohdistetut").":</th><td><INPUT type='radio' $chk1 name='nayta_kohdistetut' value='jees' onclick='submit()'></td>
	</tr>
	<tr>
		<th>".t("N‰yt‰ nimitykset").":</th><td><INPUT type='checkbox' $chk5 name='nimitykset' onclick='submit()'></td>
		<th>".t("Kohdistettava summa")."</th><td style='text-align:right'>$kohdistettava_summa $kulurow[valkoodi]</td>
		<th>".t("N‰yt‰ vain kohdistamattomat").":</th><td><INPUT type='radio' $chk2 name='nayta_kohdistetut' value='eitod' onclick='submit()'></td>
	</tr>
	<tr>
		<th>".t("Erikoisalennus")."</th><td>$laskurow[erikoisale]</td>
		<th>".t("Kohdistettu summa (riv/kpl)")."</th><td style='text-align:right'>$ok1 $valittusumma $kulurow[valkoodi] ($kappaleetrow[rivit]/".str_replace('.00','',$kappaleetrow['kappaleet']).") $ok2</td>
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

	echo "<th>".t("J‰ljell‰")."</th><td style='text-align:right'>$ok1 $laskunsumma $kulurow[valkoodi] $ok2</td>";
	echo "</form>";

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
	echo "<th>".t("Kohdista tiedostosta").":</th><td><input type='submit' value='".t("Tee se")."'></td></tr>";
	echo "</form>";
	echo "</table>";

	// otsikko valmis

	//Sivunvaihdot
 	$seuraava  = $alku + 50;
 	$edellinen = $alku - 50;

 	if ($edellinen < 0) {
 		$edellinen = 0;
 	}

	$viimenen_sivu = mysql_num_rows($result)+$alku-50;

	echo "<table>";
	echo "<tr><td class='back' colspan='6' align='right'>
	<a href='$PHP_SELF?toiminto=kohdista&toimittajaid=$toimittajaid&tee=K&otunnus=$otunnus&alku=0&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&tila=$tila&ojarj=$ojarj$ulisa&nimitykset=$nimitykset'><<<</a>
	&nbsp;&nbsp;
	<a href='$PHP_SELF?toiminto=kohdista&toimittajaid=$toimittajaid&tee=K&otunnus=$otunnus&alku=$edellinen&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&tila=$tila&ojarj=$ojarj$ulisa&nimitykset=$nimitykset'><< ".t("Edellinen sivu")."</a>
	</th>";
 	echo "<td class='back' colspan='6' align='left'>
	<a href='$PHP_SELF?toiminto=kohdista&toimittajaid=$toimittajaid&tee=K&otunnus=$otunnus&alku=$seuraava&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&tila=$tila&ojarj=$ojarj$ulisa&nimitykset=$nimitykset'>".t("Seuraava sivu")." >></a>
	&nbsp;&nbsp;
	<a href='$PHP_SELF?toiminto=kohdista&toimittajaid=$toimittajaid&tee=K&otunnus=$otunnus&alku=$viimenen_sivu&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&tila=$tila&ojarj=$ojarj$ulisa&nimitykset=$nimitykset'>>>></a>
	</th></tr><tr><th>x</th>";

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
	echo "<input type='hidden' name='ojarj' value='$ojarj'>";
	echo "<input type='hidden' name='nimitykset' value='$nimitykset'>";

	foreach ($kennim_array as $i => $kentta) {
		echo "<th><a href='$PHP_SELF?toiminto=kohdista&toimittajaid=$toimittajaid&tee=K&otunnus=$otunnus&alku=$alku&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&tila=$tila&ojarj=".$i.$ulisa."&nimitykset=$nimitykset'>$kentta</a></th>";
	}

	echo "</tr>";
	echo "<tr><td></td>";

	foreach ($kennim_array as $i => $kentta) {
		echo "<td><input type='text' size='$kenkok_array[$i]' name='haku[$i]' value='$haku[$i]'></td>";
	}

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
	echo "<input type='hidden' name='nimitykset' value='$nimitykset'>";
	echo "<input type='hidden' name='nayta_kohdistetut' value='$nayta_kohdistetut'>";
	echo "<input type='hidden' name='nayta_kaikki' value='$nayta_kaikki'>";
	echo $hiddenit;
	echo "<input type='hidden' name='ojarj' value=\"$ojarj\">";
	echo "<input type='hidden' name='tila' value='tee_kohdistus'>\n\n";

	while ($rivirow = mysql_fetch_array ($result)) {
		if ($rivirow["uusiotunnus"] > 0) {
			$chk = "checked";
			$td = "th";
		}
		else {
			$chk = '';
			$td = "td";
		}

		echo "<tr>";

		// n‰ytet‰‰n vaan tsekpoksi jos rivi‰ saa viel‰ ru(n)kata...
		if ($rivirow["varattu"] != 0 and ($rivirow["perheid"] == 0 or $rivirow["perheid"] == $rivirow["rivitunnus"])) {
			echo "<$td>";
			echo "<a name='$rivirow[rivitunnus]'>";
			echo "<input type='checkbox' name='rivi_valitut_tunnukset[]' value='$rivirow[rivitunnus]' onclick='submit_juppe($rivirow[rivitunnus])' $chk>";
			echo "<input type='hidden' name='rivi_kaikki_tunnukset[]' value='$rivirow[rivitunnus]'>";
			echo "<input type='hidden' name='rivi_summa' value='$rivirow[alerivihinta]'>";
			echo "</$td>";
			echo "<$td>$rivirow[otunnus]</$td>";
		}
		elseif($rivirow["varattu"] == 0) {
			echo "<$td>x</$td>";
			echo "<$td>$rivirow[otunnus]</$td>";
		}
		else {
			echo "<td colspan='2' class='back'></td>";
		}

		echo "<$td>";

		// jos rivi‰ ei ole viel‰ viety varastoon niin rivi‰ voi muokata
		if ($rivirow["varattu"] != 0) {
			echo "<a href='$PHP_SELF?toiminto=kohdista&toimittajaid=$toimittajaid&tee=K&otunnus=$otunnus&alku=$alku&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&tila=$tila&ojarj=$ojarj$ulisa&riviid=$rivirow[rivitunnus]&tila=muokkaa_rivi&nimitykset=$nimitykset'>$rivirow[tuoteno]</a>";
		}
		else {
			echo "$rivirow[tuoteno]";
		}

		$query = "select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$rivirow[tuoteno]'";
		$sarjares = mysql_query($query) or pupe_error($query);
		$sarjarow = mysql_fetch_array($sarjares);

		if ($sarjarow["sarjanumeroseuranta"] != "") {
			$query = "select * from sarjanumeroseuranta where yhtio='$kukarow[yhtio]' and tuoteno='$rivirow[tuoteno]' and ostorivitunnus='$rivirow[rivitunnus]'";
			$sarjares = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($sarjares) == $rivirow["siskpl"]) {
				echo " (<a href='$PHP_SELF?tila=sarjanumerot&toiminto=kohdista&toimittajaid=$toimittajaid&tuoteno=$rivirow[tuoteno]&ostorivitunnus=$rivirow[rivitunnus]&from=kohdista&tee=K&otunnus=$otunnus&alku=$alku&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&ojarj=$ojarj$ulisa&nimitykset=$nimitykset'>sarjanro OK</font></a>)";
			}
			else {
				echo " (<a href='$PHP_SELF?tila=sarjanumerot&toiminto=kohdista&toimittajaid=$toimittajaid&tuoteno=$rivirow[tuoteno]&ostorivitunnus=$rivirow[rivitunnus]&from=kohdista&tee=K&otunnus=$otunnus&alku=$alku&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&ojarj=$ojarj$ulisa&nimitykset=$nimitykset'>sarjanro</a>)";
			}
		}

		echo "</$td>";

		echo "<$td>$rivirow[toim_tuoteno]</$td>";

		if ($nimitykset != '') {
			echo "<$td>$rivirow[nimitys]</$td>";
		}

		// jos rivi‰ ei ole viel‰ viety varastoon, voidaan muuttaa kappaleita
		if ($rivirow["varattu"] != 0) {
			echo "<$td><input size='5' type='text' name='rivi_kpl[$rivirow[rivitunnus]]' value='$rivirow[siskpl]'></$td>";
		}
		else {
			echo "<$td>$rivirow[siskpl]</$td>";
		}

		echo "<$td>$rivirow[ulkkpl]</$td>";
		echo "<$td><input size='7' type='text' name='rivi_hinta[$rivirow[rivitunnus]]' value='$rivirow[hinta]'>$kulurow[valkoodi]</$td>";
		echo "<$td><input size='4' type='text' name='rivi_ale[$rivirow[rivitunnus]]' value='$rivirow[ale]'></$td>";

		if ($laskurow['vienti']=='C' or $laskurow['vienti']=='J') {
			echo "<$td>$rivirow[alv]</$td>";
		}
		else {
			echo "<$td>0.00</$td>";
		}


		echo "<$td>$rivirow[rivihinta]</$td>";
		echo "<$td>$rivirow[alerivihinta]</$td>";
		echo "<$td><input size='4' type='text' name='rivi_kuluhinta[$rivirow[rivitunnus]]' value='$rivirow[kuluhinta]'>$yhtiorow[valkoodi]</$td>";
		echo "<$td>$rivirow[toimaika]</$td>";
		echo "<$td>$rivirow[kommentti]</$td>";
		
		//Tutkitaan tuotteiden lis‰varusteita
		$query  = "	select *
					from tuoteperhe
					JOIN tuote ON tuoteperhe.yhtio=tuote.yhtio and tuoteperhe.tuoteno=tuote.tuoteno
					LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio=tuote.yhtio and tuotteen_toimittajat.tuoteno=tuote.tuoteno and tuotteen_toimittajat.liitostunnus='$toimittajaid'
					where tuoteperhe.yhtio = '$kukarow[yhtio]'
					and tuoteperhe.isatuoteno = '$rivirow[tuoteno]'
					and tuoteperhe.tyyppi = 'L'
					order by tuoteperhe.tuoteno";
		$lisaresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($lisaresult) > 0 and $rivirow["perheid"] == 0) {
			
			echo "</tr>\n";
			
			while($lisavar_row = mysql_fetch_array($lisaresult)) {
				echo "<tr>
						<td colspan='2' class='back'></td>
						<$td>$lisavar_row[tuoteno]</$td>
						<$td>$lisavar_row[toim_tuoteno]</$td>";

				if ($nimitykset != '') {
					echo "<$td>$lisavar_row[nimitys]</$td>";
				}

				echo "<$td><input size='5' type='text' name='lisavarusterivi_kpl[$rivirow[rivitunnus]##$lisavar_row[tuoteno]#*#$rivirow[otunnus]]'></$td>";
				echo "<$td></$td>";
				echo "<$td><input size='7' type='text' name='lisavarusterivi_hinta[$rivirow[rivitunnus]##$lisavar_row[tuoteno]#*#$rivirow[otunnus]]'>$kulurow[valkoodi]</$td>";
				echo "</tr>";
			}
		}		
		elseif ($rivirow["rivitunnus"] == $rivirow["perheid"]) {
			$query  = "	SELECT $kentat
						varattu,
						tilausrivi.perheid,
						tilausrivi.tunnus rivitunnus,
						tilausrivi.uusiotunnus
						FROM tilausrivi use index (yhtio_otunnus)
						JOIN lasku ON lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
						JOIN tuote use index (tuoteno_index) ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
						JOIN tuotteen_toimittajat use index (yhtio_tuoteno) ON tuotteen_toimittajat.yhtio=tuote.yhtio and tuotteen_toimittajat.tuoteno=tuote.tuoteno and tuotteen_toimittajat.liitostunnus=lasku.liitostunnus
						where tilausrivi.yhtio	= '$kukarow[yhtio]'
						and tilausrivi.otunnus	= '$rivirow[otunnus]'
						and tilausrivi.perheid = '$rivirow[perheid]'
						and tilausrivi.tunnus != '$rivirow[rivitunnus]'
						GROUP BY tilausrivi.tuoteno
						ORDER BY tilausrivi.tunnus";
			$lisavar_result = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($lisavar_result) > 0) {
				
				echo "</tr>\n";
				
				while($lisavar_row = mysql_fetch_array($lisavar_result)) {
					echo "<tr>
							<td colspan='2' class='back'></td>
							<$td>$lisavar_row[tuoteno]</$td>
							<$td>$lisavar_row[toim_tuoteno]</$td>";
	
					if ($nimitykset != '') {
						echo "<$td>$lisavar_row[nimitys]</$td>";
					}
	
					// jos rivi‰ ei ole viel‰ viety varastoon, voidaan muuttaa kappaleita
					if ($lisavar_row["varattu"] != 0) {
						echo "<$td><input size='5' type='text' name='rivi_kpl[$lisavar_row[rivitunnus]]' value='$lisavar_row[varattu]'></$td>";
					}
					else {
						echo "<$td>$lisavar_row[siskpl]</$td>";
					}
					echo "<$td>$lisavar_row[ulkkpl]</$td>";
					echo "<$td><input size='7' type='text' name='rivi_hinta[$lisavar_row[rivitunnus]]' value='$lisavar_row[hinta]'>$kulurow[valkoodi]</$td>";
					echo "</tr>";
				}
			}
			else {
				echo "<td><input type='checkbox' name='lisaa_lisavarusteita[$rivirow[rivitunnus]]' value='$rivirow[rivitunnus]' onclick='submit()'> ".t("Lis‰‰ lis‰varusteita tuotteelle")."</td>";
				echo "</tr>\n";
			}

		}
		//otetaan vika otunnus muttujaan ja jos perustetaan uusia rivej‰ niin k‰ytet‰‰n t‰t‰
		$urotunnus = $rivirow["otunnus"];
	}

 	echo "<tr><td class='back' colspan='12' align='center'>";
	echo "<a href='$PHP_SELF?toiminto=kohdista&toimittajaid=$toimittajaid&tee=K&otunnus=$otunnus&alku=$edellinen&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&tila=$tila&ojarj=$ojarj$ulisa&nimitykset=$nimitykset'><< ".t("Edellinen sivu")."</a> ";
	echo "&nbsp;&nbsp;";
 	echo "<a href='$PHP_SELF?toiminto=kohdista&toimittajaid=$toimittajaid&tee=K&otunnus=$otunnus&alku=$seuraava&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&tila=$tila&ojarj=$ojarj$ulisa&nimitykset=$nimitykset'>".t("Seuraava sivu")." >></a>";
	echo "</th></tr>";
	echo "</table>";



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
	echo "<input type='hidden' name='nimitykset' value='$nimitykset'>";
	echo "<input type='hidden' name='nayta_kohdistetut' value='$nayta_kohdistetut'>";
	echo "<input type='hidden' name='nayta_kaikki' value='$nayta_kaikki'>";
	echo $hiddenit;
	echo "<input type='hidden' name='ojarj' value=\"$ojarj\">";
	echo "<input type='hidden' name='otunnus' value='$otunnus'>";
	echo "<input type='hidden' name='urotunnus' value='$urotunnus'>";
	echo "<td class='back'><input type='submit' value='".t("Tee uusi rivi")."'></td></form>";

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