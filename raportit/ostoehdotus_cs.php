<?php

// Tämä skripti käyttää slave-tietokantapalvelinta
$useslave = 1;

if (isset($_POST["tee"])) {
	if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto = 1;
	if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
}

require("../inc/parametrit.inc");

if (isset($tee) and $tee == "lataa_tiedosto") {
	readfile("/tmp/".$tmpfilenimi);
	exit;
}

require_once ('inc/ProgressBar.class.php');

echo "<font class='head'>".t("Ostoehdotus")."</font><hr>";

$useampi_yhtio = 0;
if (is_array($valitutyhtiot)) {
	foreach ($valitutyhtiot as $yhtio) {
		$yhtiot .= "'$yhtio',";
		$useampi_yhtio++;
	}
	$yhtiot = substr($yhtiot, 0, -1);
}

if ($yhtiot == "") $yhtiot = "'$kukarow[yhtio]'";

function myynnit($myynti_varasto = '', $myynti_maa = '') {

	// otetaan kaikki muuttujat mukaan funktioon mitä on failissakin
	extract($GLOBALS);

	$laskuntoimmaa = "";
	$riviheaderi   = "Total";
	$returnstring1 = 0;
	$returnstring2 = 0;

	$varastotapa = " JOIN varastopaikat USE INDEX (PRIMARY) ON varastopaikat.yhtio = tilausrivi.yhtio
						and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tilausrivi.hyllyalue) ,5,'0'),lpad(upper(tilausrivi.hyllynro) ,5,'0'))
						and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tilausrivi.hyllyalue) ,5,'0'),lpad(upper(tilausrivi.hyllynro) ,5,'0'))";

	if ($myynti_varasto != "") {
		$varastotapa .= " and varastopaikat.tunnus = '$myynti_varasto' ";

		$query = "select nimitys from varastopaikat where yhtio in ($yhtiot) and tunnus = '$myynti_varasto'";
		$result   = mysql_query($query) or pupe_error($query);
		$laskurow = mysql_fetch_array($result);
		$riviheaderi = $laskurow["nimitys"];
	}
	elseif ($erikoisvarastot != "") {
		$varastotapa .= " and varastopaikat.tyyppi = '' ";
	}
	else {
		$varastotapa = "";
	}

	if ($myynti_maa != "") {
		$laskuntoimmaa = " and lasku.toim_maa = '$myynti_maa' ";
		$riviheaderi = $myynti_maa;
	}

	// tutkaillaan myynti
	$query = "	SELECT	
				sum(if (tilausrivi.tyyppi = 'L' and laskutettuaika >= '$vva4-$kka4-$ppa4' and laskutettuaika <= '$vvl4-$kkl4-$ppl4' ,kpl,0)) kpl4,
				sum(if (tilausrivi.tyyppi = 'L' or tilausrivi.tyyppi = 'V', tilausrivi.varattu, 0)) ennpois,
				sum(if (tilausrivi.tyyppi = 'L', tilausrivi.jt, 0)) jt,
				sum(if (tilausrivi.tyyppi = 'E', tilausrivi.varattu, 0)) ennakko
				FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
				JOIN lasku USE INDEX (PRIMARY) on (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus $laskuntoimmaa)
				JOIN asiakas USE INDEX (PRIMARY) on (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus $lisaa3)
				$varastotapa
				WHERE tilausrivi.yhtio in ($yhtiot)
				and tilausrivi.tyyppi in ('L','V','E')
				and tilausrivi.tuoteno = '$row[tuoteno]'
				and ((tilausrivi.laskutettuaika >= '$vva4-$kka4-$ppa4' and tilausrivi.laskutettuaika <= '$vvl4-$kkl4-$ppl4') or tilausrivi.laskutettuaika = '0000-00-00')";
	$result   = mysql_query($query) or pupe_error($query);
	$laskurow = mysql_fetch_array($result);

	// Myydyt kappaleet
	$returnstring2 += (float) $laskurow['kpl4'];
	$returnstring1 += (float) ($laskurow['ennpois'] + $laskurow['jt']);

	return array($returnstring1, $returnstring2);
}

function saldot($myynti_varasto = '', $myynti_maa = '') {

		// otetaan kaikki muuttujat mukaan funktioon mitä on failissakin
		extract($GLOBALS);

		$varastotapa  = "";
		$riviheaderi  = "";
		$returnstring = 0;

		if ($myynti_varasto != "") {
			$varastotapa = " and varastopaikat.tunnus = '$myynti_varasto' ";

			$query    = "select nimitys from varastopaikat where yhtio in ($yhtiot) and tunnus = '$myynti_varasto'";
			$result   = mysql_query($query) or pupe_error($query);
			$laskurow = mysql_fetch_array($result);
			$riviheaderi = $laskurow["nimitys"];
		}
		elseif ($erikoisvarastot != "") {
			$varastotapa .= " and varastopaikat.tyyppi = '' ";
		}

		if ($myynti_maa != "") {
			$varastotapa .= " and varastopaikat.maa = '$myynti_maa' ";
			$riviheaderi = $myynti_maa;
		}

		// Kaikkien valittujen varastojen saldo per maa
		$query = "	SELECT ifnull(sum(saldo),0) saldo, ifnull(sum(halytysraja),0) halytysraja
					FROM tuotepaikat
					JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
					and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
					and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
					$varastotapa
					WHERE tuotepaikat.yhtio in ($yhtiot)
					and tuotepaikat.tuoteno = '$row[tuoteno]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {
			while ($varrow = mysql_fetch_array($result)) {
				$returnstring += (float) $varrow['saldo'];
			}
		}
		else {
			$returnstring = 0;
		}

		return $returnstring;
}

function ostot($myynti_varasto = '', $myynti_maa = '') {

		// otetaan kaikki muuttujat mukaan funktioon mitä on failissakin
		extract($GLOBALS);

		$varastotapa  = "";
		$riviheaderi  = "Total";
		$returnstring = 0;

		if ($myynti_varasto != "") {
			$varastotapa = " and lasku.varasto = '$myynti_varasto' ";
			$query    = "select nimitys from varastopaikat where yhtio in ($yhtiot) and tunnus = '$myynti_varasto'";
			$result   = mysql_query($query) or pupe_error($query);
			$laskurow = mysql_fetch_array($result);
			$riviheaderi = $laskurow["nimitys"];
		}
		elseif ($erikoisvarastot != "" and $myynti_maa == "") {
			$query    = "select group_concat(tunnus) from varastopaikat where yhtio in ($yhtiot) and varastopaikat.tyyppi = ''";
			$result   = mysql_query($query) or pupe_error($query);
			$laskurow = mysql_fetch_array($result);

			if ($laskurow[0] != "") {
				$varastotapa = " and lasku.varasto in ($laskurow[0]) ";
				$riviheaderi = $myynti_maa;
			}
		}

		if ($myynti_maa != "") {
			$query    = "select group_concat(tunnus) from varastopaikat where yhtio in ($yhtiot) and maa = '$myynti_maa'";

			if ($erikoisvarastot != "") {
				$query .= " and varastopaikat.tyyppi = '' ";
			}

			$result   = mysql_query($query) or pupe_error($query);
			$laskurow = mysql_fetch_array($result);

			if ($laskurow[0] != "") {
				$varastotapa = " and lasku.varasto in ($laskurow[0]) ";
				$riviheaderi = $myynti_maa;
			}
		}

		//tilauksessa
		$query = "	SELECT sum(tilausrivi.varattu) tilattu
					FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
					JOIN lasku USE INDEX (PRIMARY) on (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus $varastotapa)					
					WHERE tilausrivi.yhtio in ($yhtiot)
					and tilausrivi.tyyppi = 'O'
					and tilausrivi.tuoteno = '$row[tuoteno]'
					and tilausrivi.varattu > 0";
		$result = mysql_query($query) or pupe_error($query);
		$ostorow = mysql_fetch_array($result);

		// tilattu kpl
		$returnstring += (float) $ostorow['tilattu'];

		return $returnstring;
}

// Haetaan abc-parametrit
$query = "	SELECT * 
			FROM abc_parametrit
			WHERE yhtio = '$kukarow[yhtio]'
			and tyyppi 	= '$abcrajaustapa'
			ORDER by luokka";
$res = mysql_query($query) or pupe_error($query);

$ryhmanimet   					= array();	
$ryhmaprossat					= array();
$kiertonopeus_tavoite 			= array();	       
$palvelutaso_tavoite 			= array();	        
$varmuusvarasto_pv   			= array();	        
$toimittajan_toimitusaika_pv 	= array();	

while ($row = mysql_fetch_array($res)) {
	$ryhmanimet[] 					= $row["luokka"];
	$ryhmaprossat[] 				= $row["osuusprosentti"];
	$kiertonopeus_tavoite[] 		= $row["kiertonopeus_tavoite"];	       
	$palvelutaso_tavoite[] 			= $row["palvelutaso_tavoite"];	        
	$varmuusvarasto_pv[]   			= $row["varmuusvarasto_pv"];	        
	$toimittajan_toimitusaika_pv[] 	= $row["toimittajan_toimitusaika_pv"];
}

// Tarvittavat päivämäärät
if (!isset($kka4)) $kka4 = date("m",mktime(0, 0, 0, date("m")-12, date("d"), date("Y")));
if (!isset($vva4)) $vva4 = date("Y",mktime(0, 0, 0, date("m")-12, date("d"), date("Y")));
if (!isset($ppa4)) $ppa4 = date("d",mktime(0, 0, 0, date("m")-12, date("d"), date("Y")));
if (!isset($kkl4)) $kkl4 = date("m");
if (!isset($vvl4)) $vvl4 = date("Y");
if (!isset($ppl4)) $ppl4 = date("d");

// katsotaan tarvitaanko mennä toimittajahakuun
if (($ytunnus != "" and $toimittajaid == "") or ($edytunnus != $ytunnus)) {
	if ($edytunnus != $ytunnus) $toimittajaid = "";
	require ("inc/kevyt_toimittajahaku.inc");
	$ytunnus = $toimittajarow["ytunnus"];
	$tee = "";
}

// tehdään itse raportti
if ($tee == "RAPORTOI" and isset($ehdotusnappi)) {

	// haetaan nimitietoa
	if ($tuoryh != '') {
		$query = "	SELECT distinct avainsana.selite, ".avain('select')."
					FROM avainsana
					".avain('join','TRY_')."
					WHERE avainsana.yhtio in ($yhtiot) and avainsana.laji = 'TRY'
					and avainsana.selite  = '$tuoryh'";
		$sresult = mysql_query($query) or pupe_error($query);
		$trow1 = mysql_fetch_array($sresult);
	}
	if ($osasto != '') {
		$query = "	SELECT distinct avainsana.selite, ".avain('select')."
					FROM avainsana
					".avain('join','OSASTO_')."
					WHERE avainsana.yhtio in ($yhtiot) and avainsana.laji='OSASTO'
					and avainsana.selite  = '$osasto'";
		$sresult = mysql_query($query) or pupe_error($query);
		$trow2 = mysql_fetch_array($sresult);
	}
	if ($toimittajaid != '') {
		$query = "	SELECT nimi
					FROM toimi
					WHERE yhtio in ($yhtiot) and tunnus='$toimittajaid'";
		$sresult = mysql_query($query) or pupe_error($query);
		$trow3 = mysql_fetch_array($sresult);
	}

	$lisaa  = ""; // tuote-rajauksia
	$lisaa2 = ""; // toimittaja-rajauksia
	$lisaa3 = ""; // asiakas-rajauksia

	if ($osasto != '') {
		$lisaa .= " and tuote.osasto = '$osasto' ";
	}
	if ($tuoryh != '') {
		$lisaa .= " and tuote.try = '$tuoryh' ";
	}
	if ($tuotemerkki != '') {
		$lisaa .= " and tuote.tuotemerkki = '$tuotemerkki' ";
	}
	if ($poistetut != '') {
		$lisaa .= " and tuote.status != 'P' ";
	}
	if ($poistuva != '') {
		$lisaa .= " and tuote.status != 'X' ";
	}
	if ($eihinnastoon != '') {
		$lisaa .= " and tuote.hinnastoon != 'E' ";
	}
	if ($vainuudet != '') {
		$lisaa .= " and tuote.luontiaika >= date_sub(current_date, interval 12 month) ";
	}
	if ($eiuusia != '') {
		$lisaa .= " and tuote.luontiaika < date_sub(current_date, interval 12 month) ";
	}
	if ($toimittajaid != '') {
		$lisaa2 .= " JOIN tuotteen_toimittajat ON (tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno and liitostunnus = '$toimittajaid') ";
	}
	if ($eliminoikonserni != '') {
		$lisaa3 .= " and asiakas.konserniyhtio = '' ";
	}

	// katsotaan valitut varastot
	$query = "	SELECT *
				FROM varastopaikat
				WHERE yhtio in ($yhtiot)";
	$vtresult = mysql_query($query) or pupe_error($query);

	$varastot 			  = "";
	$varastot_paikoittain = "";

	if (is_array($valitutvarastot)) {
		while ($vrow = mysql_fetch_array($vtresult)) {
			if (in_array($vrow["tunnus"], $valitutvarastot)) {
				$varastot .= "'".$vrow["tunnus"]."',";
				$varastot_paikoittain = "KYLLA";
			}
		}
	}

	$varastot 		 = substr($varastot,0,-1);
	$varastot_yhtiot = substr($varastot_yhtiot,0,-1);

	$maa_varastot 			= "";
	$varastot_maittain		= "";

	mysql_data_seek($vtresult,0);

	if (is_array($valitutmaat)) {
		while ($vrow = mysql_fetch_array($vtresult)) {
			if (in_array($vrow["maa"], $valitutmaat)) {
				$maa_varastot .= "'".$vrow["tunnus"]."',";
				$varastot_maittain = "KYLLA";
			}
		}
	}

	$maa_varastot 		 = substr($maa_varastot,0,-1);
	$maa_varastot_yhtiot = substr($maa_varastot_yhtiot,0,-1);

	// katotaan JT:ssä olevat tuotteet ABC-analyysiä varten, koska ne pitää includata aina!
	$query = "	SELECT group_concat(distinct concat(\"'\",tilausrivi.tuoteno,\"'\") separator ',')
				FROM tilausrivi USE INDEX (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
				JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno $lisaa)
				WHERE tilausrivi.yhtio in ($yhtiot)
				and tyyppi IN  ('L','G')
				and var = 'J'
				and jt > 0";
	$vtresult = mysql_query($query) or pupe_error($query);
	$vrow = mysql_fetch_array($vtresult);

	$jt_tuotteet = "''";
	if ($vrow[0] != "") {
		$jt_tuotteet = $vrow[0];
	}

	if ($abcrajaus != "") {
		// joinataan ABC-aputaulu katteen mukaan lasketun luokan perusteella
		$abcjoin = " 	JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (abc_aputaulu.yhtio = tuote.yhtio
						and abc_aputaulu.tuoteno = tuote.tuoteno
						and abc_aputaulu.tyyppi = '$abcrajaustapa'
						and (luokka <= '$abcrajaus' or luokka_osasto <= '$abcrajaus' or luokka_try <= '$abcrajaus' or tuote_luontiaika >= date_sub(current_date, interval 12 month) or abc_aputaulu.tuoteno in ($jt_tuotteet))) ";	
	}
	else {
		$abcjoin = " LEFT JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (abc_aputaulu.yhtio = tuote.yhtio and abc_aputaulu.tuoteno = tuote.tuoteno and abc_aputaulu.tyyppi = '$abcrajaustapa') ";
	}

	// tässä haetaan sitten listalle soveltuvat tuotteet
	$query = "	select
				group_concat(tuote.yhtio) yhtio,
				tuote.tuoteno,
				tuote.halytysraja,
				tuote.tahtituote,
				tuote.status,
				tuote.nimitys,
				tuote.myynti_era,
				tuote.myyntihinta,
				tuote.epakurantti25pvm,
				tuote.epakurantti50pvm,
				tuote.epakurantti75pvm,
				tuote.epakurantti100pvm,
				tuote.tuotemerkki,
				tuote.osasto,
				tuote.try,
				tuote.aleryhma,
				tuote.kehahin,
				tuote.varmuus_varasto,
				if(tuote.ei_varastoida='', '".t("Varastoitava")."','".t("Ei varastoida")."') ei_varastoida,
				abc_aputaulu.luokka abcluokka,
				tuote.luontiaika
				FROM tuote
				$lisaa2
				$abcjoin
				LEFT JOIN korvaavat ON (tuote.yhtio = korvaavat.yhtio and tuote.tuoteno = korvaavat.tuoteno)
				WHERE
				tuote.yhtio in ($yhtiot)
				$lisaa
				and tuote.ei_saldoa = ''
				GROUP BY tuote.tuoteno
				ORDER BY id, tuote.tuoteno, yhtio";
	$res = mysql_query($query) or pupe_error($query);

	flush();
	
	echo "<table>";
	echo "<tr>";
	
	if ($useampi_yhtio > 1) {
		echo "<th valign='top'>Yhtiö</th>";
	}
	
	echo "<th valign='top'>".t("Tuoteno")."<br>".t("Nimitys")."</th>";
	//echo "<th valign='top'>".t("")."<br>".t("")."</th>";
	echo "<th valign='top'>".t("Varmuusvarasto")."<br>".t("Tilauspiste")."</th>";
	echo "<th valign='top'>".t("Saldo")."</th>";
	echo "<th valign='top'>".t("Tilattu")."<br>".t("Varattu")."</th>";
	echo "<th valign='top'>".t("Ostoehdotus")."<br>".t("Vuosikulutus")."</th>";
	echo "<th valign='top'>".t("Pakkauskoko")."<br>".t("Varastoitava")."</th>";
	echo "<th valign='top'>".t("Toimaika")."</th>";
	echo "</tr>";
	
	$btl = " style='border-top: 1px solid; border-left: 1px solid;' ";
	$btr = " style='border-top: 1px solid; border-right: 1px solid;' ";
	$bt  = " style='border-top: 1px solid;' ";
	$bb  = " style='border-bottom: 1px solid; margin-bottom: 20px;' ";
	$bbr = " style='border-bottom: 1px solid; border-right: 1px solid; margin-bottom: 20px;' ";
	$bbl = " style='border-bottom: 1px solid; border-left: 1px solid; margin-bottom: 20px;' ";


	// loopataan tuotteet läpi
	while ($row = mysql_fetch_array($res)) {

		$toimilisa = "";
		if ($toimittajaid != '') $toimilisa = " and liitostunnus = '$toimittajaid' ";

		// haetaan tuotteen toimittajatietoa
		$query = "	SELECT group_concat(tuotteen_toimittajat.toimittaja     order by tuotteen_toimittajat.tunnus separator '/') toimittaja,
					group_concat(distinct tuotteen_toimittajat.osto_era     order by tuotteen_toimittajat.tunnus separator '/') osto_era,
					group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '/') toim_tuoteno,
					group_concat(distinct tuotteen_toimittajat.toim_nimitys order by tuotteen_toimittajat.tunnus separator '/') toim_nimitys,
					group_concat(distinct tuotteen_toimittajat.ostohinta    order by tuotteen_toimittajat.tunnus separator '/') ostohinta,
					group_concat(distinct tuotteen_toimittajat.tuotekerroin order by tuotteen_toimittajat.tunnus separator '/') tuotekerroin,
					group_concat(distinct tuotteen_toimittajat.toimitusaika order by tuotteen_toimittajat.tunnus separator '/') toimitusaika
					FROM tuotteen_toimittajat
					WHERE yhtio in ($yhtiot)
					and tuoteno = '$row[tuoteno]'
					$toimilisa";
		$result   = mysql_query($query) or pupe_error($query);
		$toimirow = mysql_fetch_array($result);

		// kaunistellaan kenttiä
		if ($row["luontiaika"] == "0000-00-00 00:00:00")	$row["luontiaika"] = "";
		if ($row['epakurantti25pvm'] == '0000-00-00')    	$row['epakurantti25pvm'] = "";
		if ($row['epakurantti50pvm'] == '0000-00-00')     	$row['epakurantti50pvm'] = "";
		if ($row['epakurantti75pvm'] == '0000-00-00')     	$row['epakurantti75pvm'] = "";
		if ($row['epakurantti100pvm'] == '0000-00-00')     	$row['epakurantti50pvm'] = "";

		// haetaan abc luokille nimet
		$abcnimi  = $ryhmanimet[$row["abcluokka"]];
		$abcnimi2 = $ryhmanimet[$row["abcluokka_osasto"]];
		$abcnimi3 = $ryhmanimet[$row["abcluokka_try"]];	
		
		/*
		// rullataan läpä maittain
		if ($varastot_maittain == "KYLLA") {
			foreach ($valitutmaat as $maa) {
				echo saldot('', $maa);
			}
		}

		// sitten rullataan läpi varastoittain
		if ($varastot_paikoittain == "KYLLA") {
			foreach ($valitutvarastot as $varastotunnus) {
				echo saldot($varastotunnus);
			}
		}
		*/
		
		// sitte vielä totalit
		$saldot = saldot();
		
		
		/*
		// rullataan läpä maittain
		if ($varastot_maittain == "KYLLA") {
			foreach ($valitutmaat as $maa) {
				echo ostot('', $maa);
			}
		}

		// sitten rullataan läpi varastoittain
		if ($varastot_paikoittain == "KYLLA") {
			foreach ($valitutvarastot as $varastotunnus) {
				echo ostot($varastotunnus);
			}
		}
		*/
		
		// sitte vielä totalit
		$ostot = ostot();
				
		/*
		// rullataan läpi maittain
		if ($varastot_maittain == "KYLLA") {
			foreach ($valitutmaat as $maa) {
				list($enparray[], $vkuarray[]) = myynnit('', $maa);
			}
		}

		// sitten rullataan läpi varastoittain
		if ($varastot_paikoittain == "KYLLA") {
			foreach ($valitutvarastot as $varastotunnus) {
				list($enparray[], $vkuarray[]) = myynnit($varastotunnus);
			}
		}
		*/
		
		// sitte vielä totalit
		list($enp, $vku) = myynnit();
		
		if (($saldot - $enp + $ostot) <= $row["halytysraja"]) {
						
			if ((float) $kiertonopeus_tavoite[$row["abcluokka"]] == 0) $kiertonopeus_tavoite[$row["abcluokka"]] = 1;
			
			// Lisätään varatut tilaukseen ja verrataan tilauspistettä vapaasaldoon
			$vapaasaldo = ($saldot - $enp + $ostot);
			
			$lisa = (float) $row["halytysraja"] - $vapaasaldo;
			
			$ostoehdotus = round($lisa + (2 * (($vku / $kiertonopeus_tavoite[$row["abcluokka"]]) - $row["varmuus_varasto"])),2);			
		}
		else {
			$ostoehdotus = 0;
		}
		
		if ($ostoehdotus > 0) {
			echo "<tr>";
		
			if ($useampi_yhtio > 1) {
				echo "<td>$row[yhtio]</td>";
			}
		
			echo "<td valign='top' $btl>$row[tuoteno]</td>";
			//echo "<td valign='top' $bt  align='right'></td>";
			echo "<td valign='top' $bt  align='right'>".(float) $row["varmuus_varasto"]."</td>";
			echo "<td valign='top' $bt  align='right'>".(float) $saldot."</td>";
			echo "<td valign='top' $bt  align='right'>".(float) $ostot."</td>";
			echo "<td valign='top' $bt  align='right'><font style='color: 00FF00;'>$ostoehdotus</font></td>";
			echo "<td valign='top' $bt  align='right'>".(float) $toimirow["osto_era"]."</td>";
			echo "<td valign='top' $btr align='right'>".(float) $toimirow["toimitusaika"]." ".t("pva")."</td>";
			echo "</tr>";
		
			echo "<tr>";
		
			if ($useampi_yhtio > 1) {
				echo "<td>$row[yhtio]</td>";
			}
		
			echo "<td valign='top' $bbl>$row[nimitys]</td>";
			//echo "<td valign='top' $bb align='right'>".(float) $kiertonopeus_tavoite[$row["abcluokka"]]."</td>";
			echo "<td valign='top' $bb align='right'>".(float) $row["halytysraja"]."</td>";
			echo "<td valign='top' $bb></td>";
			echo "<td valign='top' $bb align='right'>".(float) $enp."</td>";
			echo "<td valign='top' $bb align='right'>".(float) $vku."</td>";
			echo "<td valign='top' $bb>$row[ei_varastoida]</td>";
			echo "<td valign='top' $bbr>".tv1dateconv(date("Y-m-d",mktime(0, 0, 0, date("m"), date("d")+$toimirow["toimitusaika"], date("Y"))))."</td>";
			echo "</tr>";
		
			echo "<tr style='height: 5px;'></tr>";
		}
	}
	
	echo "</table>";
}

// näytetään käyttöliittymä..
if ($tee == "" or !isset($ehdotusnappi)) {

	$abcnimi = $ryhmanimet[$abcrajaus];

	echo "	<form action='$PHP_SELF' method='post' autocomplete='off'>
			<input type='hidden' name='tee' value='RAPORTOI'>

			<table>";

	echo "<tr><th>".t("Osasto")."</th><td colspan='3'>";

	$query = "	SELECT distinct avainsana.selite, group_concat(".avain('selectcon')." SEPARATOR ' / ') selitetark
				FROM avainsana
				".avain('join','OSASTO_')."
				WHERE avainsana.yhtio in ($yhtiot) and avainsana.laji='OSASTO'
				GROUP BY avainsana.selite
				ORDER BY avainsana.selite+0";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<select name='osasto'>";
	echo "<option value=''>".t("Näytä kaikki")."</option>";

	while ($srow = mysql_fetch_array($sresult)) {
		$sel = '';
		if ($osasto == $srow["selite"]) {
			$sel = "selected";
		}
		echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
	}
	echo "</select>";

	echo "</td></tr>
			<tr><th>".t("Tuoteryhmä")."</th><td colspan='3'>";

	//Tehdään osasto & tuoteryhmä pop-upit
	$query = "	SELECT distinct avainsana.selite, group_concat(".avain('selectcon')." SEPARATOR ' / ') selitetark
				FROM avainsana
				".avain('join','TRY_')."
				WHERE avainsana.yhtio in ($yhtiot) and avainsana.laji = 'TRY'
				GROUP BY avainsana.selite
				ORDER BY avainsana.selite+0";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<select name='tuoryh'>";
	echo "<option value=''>".t("Näytä kaikki")."</option>";

	while ($srow = mysql_fetch_array($sresult)) {
		$sel = '';
		if ($tuoryh == $srow["selite"]) {
			$sel = "selected";
		}
		echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
	}
	echo "</select>";

	echo "</td></tr>
			<tr><th>".t("Tuotemerkki")."</th><td colspan='3'>";

	//Tehdään osasto & tuoteryhmä pop-upit
	$query = "	SELECT distinct tuotemerkki
				FROM tuote
				WHERE yhtio in ($yhtiot) and tuotemerkki != ''
				ORDER BY tuotemerkki";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<select name='tuotemerkki'>";
	echo "<option value=''>".t("Näytä kaikki")."</option>";

	while ($srow = mysql_fetch_array($sresult)) {
		$sel = '';
		if ($tuotemerkki == $srow["tuotemerkki"]) {
			$sel = "selected";
		}
		echo "<option value='$srow[tuotemerkki]' $sel>$srow[tuotemerkki]</option>";
	}
	echo "</select>";

	echo "</td></tr>";

	// katotaan onko abc aputaulu rakennettu
	$query  = "select count(*) from abc_aputaulu where yhtio in ($yhtiot) and tyyppi in ('TK','TR','TP')";
	$abcres = mysql_query($query) or pupe_error($query);
	$abcrow = mysql_fetch_array($abcres);

	// jos on niin näytetään tällänen vaihtoehto
	if ($abcrow[0] > 0) {
		echo "<tr><th>".t("ABC-luokkarajaus/rajausperuste")."</th><td colspan='3'>";

		$sel = array();
		$sel[$abcrajaus] = "SELECTED";

		echo "<select name='abcrajaus' onchange='submit()'>
		<option value=''>".t("Ei rajausta")."</option>
		<option $sel[0] value='0'>".t("Luokka A-30")."</option>
		<option $sel[1] value='1'>".t("Luokka B-20 ja paremmat")."</option>
		<option $sel[2] value='2'>".t("Luokka C-15 ja paremmat")."</option>
		<option $sel[3] value='3'>".t("Luokka D-15 ja paremmat")."</option>
		<option $sel[4] value='4'>".t("Luokka E-10 ja paremmat")."</option>
		<option $sel[5] value='5'>".t("Luokka F-05 ja paremmat")."</option>
		<option $sel[6] value='6'>".t("Luokka G-03 ja paremmat")."</option>
		<option $sel[7] value='7'>".t("Luokka H-02 ja paremmat")."</option>
		<option $sel[8] value='8'>".t("Luokka I-00 ja paremmat")."</option>
		</select>";

		$sel = array();
		$sel[$abcrajaustapa] = "SELECTED";

		echo "<select name='abcrajaustapa'>
		<option $sel[TK] value='TK'>".t("Myyntikate")."</option>
		<option $sel[TM] value='TM'>".t("Myynti")."</option>
		<option $sel[TR] value='TR'>".t("Myyntirivit")."</option>
		<option $sel[TP] value='TP'>".t("Myyntikappaleet")."</option>
		</select>
		</td></tr>";
	}

	echo "<tr><th>".t("Toimittaja")."</th><td colspan='3'><input type='text' size='20' name='ytunnus' value='$ytunnus'></td></tr>";
	echo "<input type='hidden' name='edytunnus' value='$ytunnus'>";
	echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";

	echo "</table><table><br>";

	echo "	<tr>
			<th></th><th colspan='3'>".t("Alkupäivämäärä (pp-kk-vvvv)")."</th>
			<th></th><th colspan='3'>".t("Loppupäivämäärä (pp-kk-vvvv)")."</th>
			</tr>";
/*
	echo "	<tr><th>".t("Kausi 1")."</th>
			<td><input type='text' name='ppa1' value='$ppa1' size='5'></td>
			<td><input type='text' name='kka1' value='$kka1' size='5'></td>
			<td><input type='text' name='vva1' value='$vva1' size='5'></td>
			<td class='back'>&nbsp;-&nbsp;</td>
			<td><input type='text' name='ppl1' value='$ppl1' size='5'></td>
			<td><input type='text' name='kkl1' value='$kkl1' size='5'></td>
			<td><input type='text' name='vvl1' value='$vvl1' size='5'></td>
			</tr>";

	echo "	<tr><th>".t("Kausi 2")."</th>
			<td><input type='text' name='ppa2' value='$ppa2' size='5'></td>
			<td><input type='text' name='kka2' value='$kka2' size='5'></td>
			<td><input type='text' name='vva2' value='$vva2' size='5'></td>
			<td class='back'>&nbsp;-&nbsp;</td>
			<td><input type='text' name='ppl2' value='$ppl2' size='5'></td>
			<td><input type='text' name='kkl2' value='$kkl2' size='5'></td>
			<td><input type='text' name='vvl2' value='$vvl2' size='5'></td>
			</tr>";

	echo "	<tr><th>".t("Kausi 3")."</th>
			<td><input type='text' name='ppa3' value='$ppa3' size='5'></td>
			<td><input type='text' name='kka3' value='$kka3' size='5'></td>
			<td><input type='text' name='vva3' value='$vva3' size='5'></td>
			<td class='back'>&nbsp;-&nbsp;</td>
			<td><input type='text' name='ppl3' value='$ppl3' size='5'></td>
			<td><input type='text' name='kkl3' value='$kkl3' size='5'></td>
			<td><input type='text' name='vvl3' value='$vvl3' size='5'></td>
			</tr>";
*/
	echo "	<tr><th>".t("Kausi")."</th>
			<td><input type='text' name='ppa4' value='$ppa4' size='5'></td>
			<td><input type='text' name='kka4' value='$kka4' size='5'></td>
			<td><input type='text' name='vva4' value='$vva4' size='5'></td>
			<td class='back'>&nbsp;-&nbsp;</td>
			<td><input type='text' name='ppl4' value='$ppl4' size='5'></td>
			<td><input type='text' name='kkl4' value='$kkl4' size='5'></td>
			<td><input type='text' name='vvl4' value='$vvl4' size='5'></td>
			</tr>";

	echo "</table><table><br>";

	$chk = "";
	if ($eliminoi != "") $chk = "checked";
	echo "<tr><th>".t("Älä huomioi konsernimyyntiä")."</th><td colspan='3'><input type='checkbox' name='eliminoi' $chk></td></tr>";

	$chk = "";
	if ($erikoisvarastot != "") $chk = "checked";
	echo "<tr><th>".t("Älä huomioi erikoisvarastoja")."</th><td colspan='3'><input type='checkbox' name='erikoisvarastot' $chk></td></tr>";

	$chk = "";
	if ($poistetut != "") $chk = "checked";
	echo "<tr><th>".t("Älä näytä poistettuja tuotteita")."</th><td colspan='3'><input type='checkbox' name='poistetut' $chk></td></tr>";

	$chk = "";
	if ($poistuva != "") $chk = "checked";
	echo "<tr><th>".t("Älä näytä poistuvia tuotteita")."</th><td colspan='3'><input type='checkbox' name='poistuva' $chk></td></tr>";

	$chk = "";
	if ($eihinnastoon != "") $chk = "checked";
	echo "<tr><th>".t("Älä näytä tuotteita joita ei näytetä hinnastossa")."</th><td colspan='3'><input type='checkbox' name='eihinnastoon' $chk></td></tr>";

	if ($abcrajaus != "") {
		echo "<tr><td class='back'><br></td></tr>";
		echo "<tr><th colspan='4'>".t("ABC-rajaus")." $ryhmanimet[$abcrajaus]</th></tr>";

		$chk = "";
		if ($eiuusia != "") $chk = "checked";
		echo "<tr><th>".t("Älä listaa 12kk sisällä perustettuja tuotteita")."</th><td colspan='3'><input type='checkbox' name='eiuusia' $chk></td></tr>";

		$chk = "";
		if ($vainuudet != "") $chk = "checked";
		echo "<tr><th>".t("Listaa vain 12kk sisällä perustetut tuotteet")."</th><td colspan='3'><input type='checkbox' name='vainuudet' $chk></td></tr>";
	}

	echo "</table><table><br>";

	// yhtiövalinnat
	$query	= "	SELECT distinct yhtio, nimi
				from yhtio
				where konserni = '$yhtiorow[konserni]' and konserni != ''";
	$presult = mysql_query($query) or pupe_error($query);

	$vlask 		= 0;
	$useampi_yhtio = 0;

	if (mysql_num_rows($presult) > 0) {

		$yhtiot = "";

		while ($prow = mysql_fetch_array($presult)) {

			$chk = "";
			if (is_array($valitutyhtiot)) {
				if (in_array($prow["yhtio"], $valitutyhtiot) != '') {
					$chk = "CHECKED";
					$yhtiot .= "'$prow[yhtio]',";
					$useampi_yhtio++;
				}
			}
			elseif ($prow["yhtio"] == $kukarow["yhtio"]) {
				$chk = "CHECKED";
			}

			if ($vlask == 0) {
				echo "<tr><th rowspan='".mysql_num_rows($presult)."'>Huomioi yhtiön saldot, myynnit ja ostot:</th>";
			}
			else {
				echo "<tr>";
			}

			echo "<td colspan='3'><input type='checkbox' name='valitutyhtiot[]' value='$prow[yhtio]' $chk onClick='submit();'> $prow[nimi]</td></tr>";

			$vlask++;
		}

		$yhtiot = substr($yhtiot,0,-1);

		if ($yhtiot == "") $yhtiot = "'$kukarow[yhtio]'";

		echo "</table><table><br>";

	}

	// katsotaan onko firmalla varastoja useassa maassa
	$query = "select distinct maa from varastopaikat where maa != '' and yhtio in ($yhtiot) order by yhtio, maa";
	$vtresult = mysql_query($query) or pupe_error($query);

	$useampi_maa = 0;

	// useampi maa löytyy, annetaan mahdollisuus tutkailla saldoja per maa
	if (mysql_num_rows($vtresult) > 1) {

		$useampi_maa = 1;

		// katsotaan onko firmalla varastoja useassa maassa
		$query = "select distinct maa from varastopaikat where maa != '' and yhtio in ($yhtiot) order by yhtio, maa";
		$vtresult = mysql_query($query) or pupe_error($query);
		$vlask = 0;

		while ($vrow = mysql_fetch_array($vtresult)) {

			$chk = "";
			if (is_array($valitutmaat)) {
				if (in_array($vrow["maa"], $valitutmaat) != '') {
					$chk = "CHECKED";
				}
			}

			if ($vlask == 0) {
				echo "<tr><th rowspan='".mysql_num_rows($vtresult)."'>".t("Huomioi saldot, myynnit ja ostot maittain:")."</th>";
			}
			else {
				echo "<tr>";
			}

			echo "<td colspan='3'><input type='checkbox' name='valitutmaat[]' value='$vrow[maa]' $chk>$vrow[maa] - ".maa($vrow["maa"])."</td></tr>";

			$vlask++;
		}

		echo "</table><table><br>";

	}

	//Valitaan varastot joiden saldot huomioidaan
	$query = "	SELECT *
				FROM varastopaikat
				WHERE yhtio in ($yhtiot)
				ORDER BY yhtio, nimitys";
	$vtresult = mysql_query($query) or pupe_error($query);

	$vlask = 0;

	if (mysql_num_rows($vtresult) > 1) {
		while ($vrow = mysql_fetch_array($vtresult)) {

			$chk = "";
			if (is_array($valitutvarastot)) {
				if (in_array($vrow["tunnus"], $valitutvarastot) != '') {
					$chk = "CHECKED";
				}
			}

			if ($vlask == 0) {
				echo "<tr><th rowspan='".mysql_num_rows($vtresult)."'>".t("Huomioi saldot, myynnit ja ostot varastoittain:")."</th>";
			}
			else {
				echo "<tr>";
			}

			echo "<td colspan='3'><input type='checkbox' name='valitutvarastot[]' value='$vrow[tunnus]' $chk>";

			if ($useampi_yhtio > 1) {
				$query = "select nimi from yhtio where yhtio='$vrow[yhtio]'";
				$yhtres = mysql_query($query) or pupe_error($query);
				$yhtrow = mysql_fetch_array($yhtres);
				echo "$yhtrow[nimi]: ";
			}

			echo "$vrow[nimitys] ";

			if ($vrow["tyyppi"] != "") {
				echo " *$vrow[tyyppi]* ";
			}
			if ($useampi_maa == 1) {
				echo "(".maa($vrow["maa"]).")";
			}

			echo "</td></tr>";

			$vlask++;
		}
	}
	elseif (mysql_num_rows($vtresult) == 1) {
		$vrow = mysql_fetch_array($vtresult);
		echo "<input type='hidden' name='valitutvarastot[]' value='$vrow[tunnus]'>";
	}
	else {
		echo "<font class='error'>Yhtään varastoa ei löydy, raporttia ei voida ajaa!</font>";
		exit;
	}

	echo "</table>";

	echo "<br><input type='submit' name='ehdotusnappi' value = '".t("Aja ostoehdotus")."'>
		</form>";

}

require ("../inc/footer.inc");

?>