<?php
	
	
	if (file_exists("../inc/parametrit.inc")) {
		require ("../inc/parametrit.inc");
		require ("../verkkokauppa/ostoskori.inc");
		$kori_polku = "../verkkokauppa/ostoskori.php";
	}
	else {
		require ("parametrit.inc");
		require ("ostoskori.inc");
		
		$kori_polku = "ostoskori.php";
		
		if ($toim == "FUTURSOFT") {
			$kori_polku .= "?toim=".$toim."&ostoskori=".$ostoskori;
		}		
	}

	echo "<SCRIPT type='text/javascript'>
			<!--
				function picture_popup(tuote_tunnus, maxwidth, totalheight, tuoteno) {
					var myWidth = 0, myHeight = 0;
					if (typeof(window.innerWidth ) == 'number') {
						//Non-IE
						myWidth = window.innerWidth;
						myHeight = window.innerHeight;
					} else if (document.documentElement && (document.documentElement.clientWidth || document.documentElement.clientHeight)) {
						//IE 6+ in 'standards compliant mode'
						myWidth = document.documentElement.clientWidth;
						myHeight = document.documentElement.clientHeight;
					} else if (document.body && (document.body.clientWidth || document.body.clientHeight)) {
						//IE 4 compatible
						myWidth = document.body.clientWidth;
						myHeight = document.body.clientHeight;
					}

					if (maxwidth == '0' && totalheight == '0') {
						window.open('$PHP_SELF.php?tuoteno='+tuoteno+'&ohje=off&toiminto=avaa_kuva&tunnus='+tuote_tunnus+'&laji=tuotekuva', '_blank' ,'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,left=0,top = 0, width='+myWidth+', height='+myHeight);			
					}
					else {
						window.open('$PHP_SELF?tuoteno='+tuoteno+'&ohje=off&toiminto=avaa_kuva&tunnus='+tuote_tunnus+'&laji=tuotekuva', '_blank' ,'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,left=0,top = 0, width='+maxwidth+', height='+totalheight);
					}
				}
			//-->
			</SCRIPT>";
	
	echo "<SCRIPT type='text/javascript'>
			<!--
				function sarjanumeronlisatiedot_popup(tunnus) {
					window.open('$PHP_SELF?tunnus='+tunnus+'&toiminto=sarjanumeronlisatiedot_popup', '_blank' ,'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,left=0,top=0,width=800,height=600');
				}
			//-->
			</SCRIPT>";
	
	if ($toiminto == "sarjanumeronlisatiedot_popup") {
		@include('sarjanumeron_lisatiedot_popup.inc');
		
		if ($kukarow["extranet"] != "") {
			$hinnat = 'MY';
		}
		else {
			$hinnat = '';
		}
		
		list($divitx, , , ,) = sarjanumeronlisatiedot_popup($tunnus, '', '', $hinnat, '');		
		echo "$divitx";		
		exit;
	}
	
	if ($toiminto == "avaa_kuva") {

		$query = "	SELECT *
					FROM tuote
					WHERE yhtio='$kukarow[yhtio]'
					AND tuoteno='$tuoteno'";
		$tuotekuvausres = mysql_query($query) or pupe_error($query);

		$query = "	SELECT tunnus
		 			FROM liitetiedostot 
					WHERE yhtio='$kukarow[yhtio]' 
					AND liitos='tuote' 
					AND liitostunnus='$tunnus'";

		$kuvares = mysql_query($query) or pupe_error($query);

		echo "<table border='0' cellspacing='5' align='center'";
		if ($maxwidth) {
			echo "width='$maxwidth'";
		}
		echo ">";
		while ($kuvarow = mysql_fetch_array($kuvares)) {
			echo "<tr><td class='back' align='center' valign='top'>";

			$tuotekuvausrow = mysql_fetch_array($tuotekuvausres);
			
			if ($kuvarow["filetype"] == "application/pdf") {
				echo "<a href='view.php?id=$kuvarow[tunnus]' target='_top'>".t("Avaa pdf")."</a></td></tr>";
			}
			else {
				echo "<img src='view.php?id=$kuvarow[tunnus]'></td></tr>";
			}

			echo "<tr><td class='back' align='center' valign='top'>$kuvarow[selite]</td></tr>";
			echo "<tr><td class='back'><br></td></tr>";
		}
		echo "</table>";
		exit;
	}

	echo "<font class='head'>".t("Etsi ja selaa tuotteita").":</font><hr>";

	if ($toim_kutsu == "") {
		$toim_kutsu = "RIVISYOTTO";
	}

	$query    = "SELECT * from lasku where tunnus='$kukarow[kesken]' and yhtio='$kukarow[yhtio]'";
	$result   = mysql_query($query) or pupe_error($query);
	$laskurow = mysql_fetch_array($result);

	if (is_numeric($ostoskori)) {
		echo "<table><tr><td class='back'>";
		echo "	<form method='post' action='$kori_polku'>
				<input type='hidden' name='tee' value='poistakori'>
				<input type='hidden' name='ostoskori' value='$ostoskori'>
				<input type='hidden' name='pyytaja' value='haejaselaa'>
				<input type='submit' value='".t("Tyhjennä ostoskori")."'>
				</form>";
		echo "</td><td class='back'>";
		echo "	<form method='post' action='$kori_polku'>
				<input type='hidden' name='tee' value=''>
				<input type='hidden' name='ostoskori' value='$ostoskori'>
				<input type='hidden' name='pyytaja' value='haejaselaa'>
				<input type='submit' value='".t("Näytä ostoskori")."'>
				</form>";
		echo "</td></tr></table>";
	}
	elseif ($kukarow["kesken"] != 0 and ($laskurow["tila"] == "L" or $laskurow["tila"] == "N" or $laskurow["tila"] == "T" or $laskurow["tila"] == "A" or $laskurow["tila"] == "S")) {

		if ($kukarow["extranet"] != "") {
			$toim_kutsu = "EXTRANET";
		}

		echo "	<form method='post' action='tilaus_myynti.php'>
				<input type='hidden' name='toim' value='$toim_kutsu'>
				<input type='hidden' name='aktivoinnista' value='true'>
				<input type='hidden' name='tilausnumero' value='$kukarow[kesken]'>
				<input type='submit' value='".t("Takaisin tilaukselle")."'>
				</form><br><br>";
	}
	
	// Tarkistetaan tilausrivi
	if ($tee == 'TI' and ($kukarow["kesken"] != 0 or is_numeric($ostoskori))) {

		if (is_numeric($ostoskori)) {
			$kori = check_ostoskori($ostoskori,$kukarow["oletus_asiakas"]);
			$kukarow["kesken"] = $kori["tunnus"];
		}

		// haetaan avoimen tilauksen otsikko
		$query    = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
		$laskures = mysql_query($query);

		if (mysql_num_rows($laskures) == 0) {
			echo "<font class='error'>Sinulla ei ole avointa tilausta!</font><br>";
		}
		else {

			// tilauksen tiedot
			$laskurow = mysql_fetch_array($laskures);

			if (is_numeric($ostoskori)) {
				echo "<font class='message'>Lisätään tuotteita ostoskoriin $ostoskori.</font><br>";
			}
			else {
				echo "<font class='message'>Lisätään tuotteita tilaukselle $kukarow[kesken].</font><br>";
			}

			// Käydään läpi formin kaikki rivit
			foreach ($tilkpl as $yht_i => $kpl) {

				if ((float) $kpl > 0 or ($kukarow["extranet"] == "" and (float) $kpl < 0)) {

					// haetaan tuotteen tiedot
					$query    = "select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tiltuoteno[$yht_i]'";
					$tuoteres = mysql_query($query);

					if (mysql_num_rows($tuoteres) == 0) {
						echo "<font class='error'>Tuotetta $tiltuoteno[$yht_i] ei löydy!</font><br>";
					}
					else {
						// tuote löytyi ok, lisätään rivi
						$trow = mysql_fetch_array($tuoteres);

						$ytunnus         = $laskurow["ytunnus"];
						$kpl             = (float) $kpl;
						$tuoteno         = $trow["tuoteno"];
						$toimaika 	     = $laskurow["toimaika"];
						$kerayspvm	     = $laskurow["kerayspvm"];
						$hinta 		     = "";
						$netto 		     = "";
						$ale 		     = "";
						$alv		     = "";
						$var			 = "";
						$varasto 	     = "";
						$rivitunnus		 = "";
						$korvaavakielto	 = "";
						$varataan_saldoa = "";
						$myy_sarjatunnus = $tilsarjatunnus[$yht_i];
						
						if ($tilpaikka[$yht_i] != '') {
							$paikka	= $tilpaikka[$yht_i];
						}
						else {
							$paikka	= "";
						}

						// jos meillä on ostoskori muuttujassa numero, niin halutaan lisätä tuotteita siihen ostoskoriin
						if (is_numeric($ostoskori)) {
							lisaa_ostoskoriin ($ostoskori, $laskurow["liitostunnus"], $tuoteno, $kpl);
							$kukarow["kesken"] = "";
						}
						elseif (file_exists("../tilauskasittely/lisaarivi.inc")) {
							require ("../tilauskasittely/lisaarivi.inc");
						}
						else {
							require ("lisaarivi.inc");
						}

						echo "<font class='message'>Lisättiin $kpl kpl tuotetta $trow[tuoteno].</font><br>";

						//Hanskataan sarjanumerollisten tuotteiden lisävarusteet
						if ($tilsarjatunnus[$yht_i] > 0 and $lisatty_tun > 0) {
							require("sarjanumeron_lisavarlisays.inc");

							lisavarlisays($tilsarjatunnus[$yht_i], $lisatty_tun);
						}
					} // tuote ok else
				} // end kpl > 0
			} // end foreach
		} // end tuotelöytyi else

		echo "<br>";

		$trow			 = "";
		$ytunnus         = "";
		$kpl             = "";
		$tuoteno         = "";
		$toimaika 	     = "";
		$kerayspvm	     = "";
		$hinta 		     = "";
		$netto 		     = "";
		$ale 		     = "";
		$alv		     = "";
		$var			 = "";
		$varasto 	     = "";
		$rivitunnus		 = "";
		$korvaavakielto	 = "";
		$varataan_saldoa = "";
		$myy_sarjatunnus = "";
		$paikka			 = "";
		$tee 			 = "";
	}


	$kentat	= "tuote.tuoteno,toim_tuoteno,tuote.nimitys,tuote.osasto,tuote.try,tuote.tuotemerkki";
	$nimet	= "Tuotenumero,Toim tuoteno,Nimitys,Osasto,Tuoteryhmä,Tuotemerkki";

	$jarjestys = "tuote.tuoteno";

	$array = split(",", $kentat);
	$arraynimet = split(",", $nimet);

	$lisa  = "";
	$ulisa = "";

	$count = count($array);

	/*

	match againstit ei toimi!!! Kokeile hakusanalla prt firmassa allr

	if (strlen($haku[0]) > 0) {
		$lisa .= " and match (tuote.tuoteno) against ('$haku[0]*' IN BOOLEAN MODE) ";
		$ulisa .= "&haku[".$i."]=".$haku[$i];
	}
	if (strlen($haku[1]) > 0) {
		$lisa .= " and toim_tuoteno like '%$haku[1]%' ";
		$ulisa .= "&haku[".$i."]=".$haku[$i];
	}
	if (strlen($haku[2]) > 0) {
		$lisa .= " and match (tuote.nimitys) against ('$haku[2]*' IN BOOLEAN MODE) ";
		$ulisa .= "&haku[".$i."]=".$haku[$i];
	}

	for ($i=3; $i<=$count; $i++) {
		if (strlen($haku[$i]) > 0) {
			$lisa .= " and ".$array[$i]."='".$haku[$i]."'";
			$ulisa .= "&haku[".$i."]=".$haku[$i];
		}
	}
	*/

	for ($i=0; $i<=$count; $i++) {
		
		if (strlen($haku[$i]) > 0 && $i == 0) {
			$lisa .= " and ".$array[$i]." like '%".$haku[$i]."%'";
			$ulisa .= "&haku[".$i."]=".$haku[$i];
		}
		elseif (strlen($haku[$i]) > 0 && $i == 1) {
			
			//Otetaan konserniyhtiöt hanskaan
			$query	= "	SELECT distinct tuoteno
						FROM tuotteen_toimittajat
						WHERE yhtio = '$kukarow[yhtio]' 
						and toim_tuoteno like '%".$haku[$i]."%'
						LIMIT 500";
			$pres = mysql_query($query) or pupe_error($query);
			
			$toimtuotteet = "";
			
			while($prow = mysql_fetch_array($pres)) {
				$toimtuotteet .= "'".$prow["tuoteno"]."',";
			}
			
			$toimtuotteet = substr($toimtuotteet, 0, -1);
			
			if ($toimtuotteet != "") {
				$lisa .= " and tuote.tuoteno in ($toimtuotteet) ";		
			}
			
			$ulisa .= "&haku[".$i."]=".$haku[$i];
		}
		elseif (strlen($haku[$i]) > 0 && $i == 2) {
			$lisa .= " and ".$array[$i]." like '%".$haku[$i]."%'";
			$ulisa .= "&haku[".$i."]=".$haku[$i];
		}
		elseif (strlen($haku[$i]) > 0) {
			$lisa .= " and ".$array[$i]."='".$haku[$i]."'";
			$ulisa .= "&haku[".$i."]=".$haku[$i];
		}
	}

	if (strlen($ojarj) > 0) {
		$jarjestys = $ojarj;
	}

	if ($poistetut != "") {
		$poislisa  = "";
		$poischeck = "CHECKED";
	}
	else {
		$poislisa  = " HAVING (tuote.status not in ('P','X') or saldo > 0) ";
		$poischeck = "";
	}
	
	/*
	if ($poistuvat != "" or (! isset($submit) and $yhtiorow['poistuvat_tuotteet'] == 'X')) {
		$kohtapoislisa  = "";
		$kohtapoischeck = "CHECKED";
	}
	else {
		$kohtapoislisa  = " and status != 'X' ";
		$kohtapoischeck = "";
	}
	*/
	
	
	if ($lisatiedot != "") {
		$lisacheck = "CHECKED";
	}
	else {
		$lisacheck = "";
	}

	if ($kukarow["extranet"] != "") {
		$extra_poislisa = " and tuote.hinnastoon != 'E' ";
		$avainlisa = " and avainsana.jarjestys < 10000";
	}
	else {
		$extra_poislisa = "";
		$avainlisa = "";
	}
	
	
	// vientikieltokäsittely:
	// +maa tarkoittaa että myynti on kielletty tähän maahan ja sallittu kaikkiin muihin
	// -maa tarkoittaa että ainoastaan tähän maahan saa myydä
	// eli näytetään vaan tuotteet jossa vienti kentässä on tyhjää tai -maa.. ja se ei saa olla +maa
	$kieltolisa = "";
	unset($vierow);
	
	if ($kukarow["kesken"] > 0) {
		$query  = "	SELECT if(toim_maa != '', toim_maa, maa) maa
					FROM lasku
					WHERE yhtio	= '$kukarow[yhtio]'
					and tunnus  = '$kukarow[kesken]'";
		$vieres = mysql_query($query) or pupe_error($query);
		$vierow = mysql_fetch_array($vieres);
	}
	elseif($verkkokauppa != "") {
		$vierow = array();
		
		if ($maa != "") {
			$vierow["maa"] = $maa;
		}
		else {
			$vierow["maa"] = $yhtiorow["maa"];
		}			
	}
	elseif($kukarow["extranet"] != "") {
		$query  = "	SELECT if(toim_maa != '', toim_maa, maa) maa
					FROM asiakas
					WHERE yhtio	= '$kukarow[yhtio]'
					and tunnus  = '$kukarow[oletus_asiakas]'";
		$vieres = mysql_query($query) or pupe_error($query);
		$vierow = mysql_fetch_array($vieres);
	}

	if (isset($vierow) and $vierow["maa"] != "") {
		$kieltolisa = " and (tuote.vienti = '' or tuote.vienti like '%-$vierow[maa]%' or tuote.vienti like '%+%') and tuote.vienti not like '%+$vierow[maa]%' ";
	}

	echo "<table><tr>
			<form action = '$PHP_SELF?toim_kutsu=$toim_kutsu' method = 'post'>";
	echo "<input type='hidden' name='ostoskori' value='$ostoskori'>";
	if ($toim == "FUTURSOFT") {
		echo "<input type='hidden' name='toim' value = '$toim'>";
	}
	

	echo "<th nowrap valign='top'>
		<a href = '$PHP_SELF?toim_kutsu=$toim_kutsu&ojarj=$array[0]$ulisa'>".t("$arraynimet[0]")."</a><br>
		<a href = '$PHP_SELF?toim_kutsu=$toim_kutsu&ojarj=$array[1]$ulisa'>".t("$arraynimet[1]")."</a></th>";

	echo "<th nowrap valign='top'><br><a href = '$PHP_SELF?toim_kutsu=$toim_kutsu&ojarj=$array[2]$ulisa'>".t("$arraynimet[2]")."</a></th>";

	echo "<th nowrap valign='top'>
		<a href = '$PHP_SELF?toim_kutsu=$toim_kutsu&ojarj=$array[3]$ulisa'>".t("$arraynimet[3]")."</a><br>
		<a href = '$PHP_SELF?toim_kutsu=$toim_kutsu&ojarj=$array[4]$ulisa'>".t("$arraynimet[4]")."</a></th>";

	echo "<th nowrap valign='top'>";
	echo "<a href = '$PHP_SELF?toim_kutsu=$toim_kutsu&ojarj=$array[5]$ulisa'>".t("$arraynimet[5]")."</a>";

	if ($kukarow["extranet"] == "") {
		echo "<br><a href = '$PHP_SELF?toim_kutsu=$toim_kutsu&ojarj=$array[5]$ulisa'>".t("Näytä poistuvat")." / ".t("lisätiedot")."</a>";
	}
	echo "</th>";

	echo "</tr><tr>";


	echo "<td nowrap valign='top'>";
	echo "<input type='text' size='10' name = 'haku[0]' value = '$haku[0]'><br>";
	echo "<input type='text' size='10' name = 'haku[1]' value = '$haku[1]'>";
	echo "</td>";

	echo "<td nowrap valign='top'>";
	echo "<input type='text' size='10' name = 'haku[2]' value = '$haku[2]'>";
	echo "</td>";

	$query = "	SELECT distinct avainsana.selite, ".avain('select')."
				FROM avainsana
				".avain('join','OSASTO_')."
				WHERE avainsana.yhtio = '$kukarow[yhtio]'
				and avainsana.laji = 'OSASTO'
				$avainlisa
				ORDER BY avainsana.jarjestys, avainsana.selite";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<td nowrap valign='top'><select name='haku[3]'>";
	echo "<option value='' $sel>".t("Ei valintaa")."</option>";

	while($srow = mysql_fetch_array ($sresult)){
		if($haku[3] == $srow[0]) {
			$sel = "SELECTED";
		}
		else {
			$sel = '';
		}
		echo "<option value='$srow[0]' $sel>$srow[0] $srow[1]</option>";
	}
	echo "</select><br>";

	$query = "	SELECT distinct avainsana.selite, ".avain('select')."
				FROM avainsana
				".avain('join','TRY_')."
				WHERE avainsana.yhtio='$kukarow[yhtio]'
				and avainsana.laji='TRY'
				$avainlisa
				ORDER BY avainsana.jarjestys, avainsana.selite";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<select name='haku[4]'>";
	echo "<option value='' $sel>".t("Ei valintaa")."</option>";

	while($srow = mysql_fetch_array ($sresult)){
		if($haku[4] == $srow[0]) {
			$sel = "SELECTED";
		}
		else {
			$sel = '';
		}
		echo "<option value='$srow[0]' $sel>$srow[0] $srow[1]</option>";
	}
	echo "</select></td>";

	$query = "	SELECT distinct tuotemerkki
				FROM tuote use index (yhtio_tuotemerkki)
				WHERE yhtio='$kukarow[yhtio]'
				and tuotemerkki != ''
				$kieltolisa
				ORDER BY tuotemerkki";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<td nowrap valign='top'>";
	echo "<select name='haku[5]'>";
	echo "<option value='' $sel>".t("Ei valintaa")."</option>";

	while($srow = mysql_fetch_array ($sresult)){
		if($haku[5] == $srow[0]) {
			$sel = "SELECTED";
		}
		else {
			$sel = '';
		}
		echo "<option value='$srow[0]' $sel>$srow[0]</option>";
	}

	echo "</select><br>";

	if ($kukarow["extranet"] == "") {
		//echo t("Poistuvat")."<input type='checkbox' name='poistuvat' value='X' $kohtapoischeck>";
		echo t("Poistetut").": <input type='checkbox' name='poistetut' $poischeck> ";
	}

	echo t("Lisätiedot").": <input type='checkbox' name='lisatiedot' $lisacheck> ";
	echo "</td>";


	echo "<td class='back' valign='bottom' nowrap><input type='Submit' name='submit' value = '".t("Etsi")."'></td></form></tr>";
	echo "</table><br>";

	// Ei listata mitään jos käyttäjä ei ole tehnyt mitään rajauksia
	if($lisa == "") {
		if (file_exists("../inc/footer.inc")) {
			require ("../inc/footer.inc");
		}
		else {
			require ("footer.inc");
		}

		exit;
	}
	
	$query = "	SELECT 
				ifnull((SELECT isatuoteno FROM tuoteperhe use index (yhtio_tyyppi_isatuoteno) where tuoteperhe.yhtio=tuote.yhtio and tuoteperhe.tyyppi='P' and tuoteperhe.isatuoteno=tuote.tuoteno LIMIT 1), '') tuoteperhe,
				ifnull((SELECT id FROM korvaavat use index (yhtio_tuoteno) where korvaavat.yhtio=tuote.yhtio and korvaavat.tuoteno=tuote.tuoteno LIMIT 1), tuote.tuoteno) korvaavat,
				tuote.tuoteno, 
				tuote.nimitys, 
				tuote.osasto, 
				tuote.try, 
				tuote.myyntihinta,
				tuote.nettohinta, 
				tuote.aleryhma, 
				tuote.status, 
				tuote.ei_saldoa, 
				tuote.yksikko,
				tuote.tunnus,
				(SELECT group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') FROM tuotteen_toimittajat use index (yhtio_tuoteno) WHERE tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno) toim_tuoteno,
				tuote.sarjanumeroseuranta,
				tuote.status,
				(SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno) saldo				
				FROM tuote use index (tuoteno, nimitys)
				WHERE tuote.yhtio = '$kukarow[yhtio]'
				$kieltolisa
				$lisa
				$extra_poislisa
				$poislisa				
				ORDER BY tuote.tuoteno
				LIMIT 500";
	$result = mysql_query($query) or pupe_error($query);
			
	if (mysql_num_rows($result) > 0) {
		
		$rows = array();
		
		// Rakennetaan array ja laitetaan korvaavat mukaan
		while ($mrow = mysql_fetch_array($result)) {
			if ($mrow["korvaavat"] != $mrow["tuoteno"]) {
				$query = "	SELECT
							ifnull((SELECT isatuoteno FROM tuoteperhe use index (yhtio_tyyppi_isatuoteno) where tuoteperhe.yhtio=tuote.yhtio and tuoteperhe.tyyppi='P' and tuoteperhe.isatuoteno=tuote.tuoteno LIMIT 1), '') tuoteperhe,
							'$mrow[tuoteno]' korvaavat,
							tuote.tuoteno, 
							tuote.nimitys, 
							tuote.osasto, 
							tuote.try, 
							tuote.myyntihinta,
							tuote.nettohinta, 
							tuote.aleryhma, 
							tuote.status, 
							tuote.ei_saldoa, 
							tuote.yksikko,
							tuote.tunnus,
							(SELECT group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') FROM tuotteen_toimittajat use index (yhtio_tuoteno) WHERE tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno) toim_tuoteno,
							tuote.sarjanumeroseuranta,
							tuote.status,
							(SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno) saldo
							FROM korvaavat
							JOIN tuote ON tuote.yhtio=korvaavat.yhtio and tuote.tuoteno=korvaavat.tuoteno
							WHERE korvaavat.yhtio = '$kukarow[yhtio]'
							and korvaavat.id = '$mrow[korvaavat]'
							$kieltolisa
							$poislisa 							
							ORDER BY korvaavat.jarjestys, korvaavat.tuoteno";
				$kores = mysql_query($query) or pupe_error($query);
				
				$ekakorva = "";
						
				while ($krow = mysql_fetch_array($kores)) {
					if ($ekakorva == "") $ekakorva = $krow["tuoteno"];
					if(!isset($rows[$ekakorva.$krow["tuoteno"]])) $rows[$ekakorva.$krow["tuoteno"]] = $krow;
				}								
			}
			else {
				$rows[$mrow["tuoteno"]] = $mrow;
				
				if($mrow["tuoteperhe"] == $mrow["tuoteno"]) {

					$query = "	SELECT
	 							'$mrow[tuoteno]' tuoteperhe,
								tuote.tuoteno korvaavat,
								tuote.tuoteno, 
								tuote.nimitys, 
								tuote.osasto, 
								tuote.try, 
								tuote.myyntihinta,
								tuote.nettohinta, 
								tuote.aleryhma, 
								tuote.status, 
								tuote.ei_saldoa, 
								tuote.yksikko,
								tuote.tunnus,
								(SELECT group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') FROM tuotteen_toimittajat use index (yhtio_tuoteno) WHERE tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno) toim_tuoteno,
								tuote.sarjanumeroseuranta,
								tuote.status,
								(SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno) saldo
								FROM tuoteperhe
								JOIN tuote ON tuote.yhtio=tuoteperhe.yhtio and tuote.tuoteno=tuoteperhe.tuoteno
								WHERE tuoteperhe.yhtio 	  = '$kukarow[yhtio]'
								and tuoteperhe.isatuoteno = '$mrow[tuoteperhe]'
								$kieltolisa
								$poislisa 								
								ORDER BY tuoteperhe.tuoteno";
					$kores = mysql_query($query) or pupe_error($query);

					while ($krow = mysql_fetch_array($kores)) {
						$rows[$krow["tuoteperhe"].$krow["tuoteno"]] = $krow;
					}				
				}
			}
		}
		
		if ($yhtiorow["saldo_kasittely"] == "T") {
			$saldoaikalisa = date("Y-m-d");
		}
		else {
			$saldoaikalisa = "";
		}
		
		echo "<table>";

		echo "<tr>";
		echo "<th>".t("Tuoteno")."</th>";
		echo "<th>".t("Nimitys")."</th>";

		if ($lisatiedot != "") {
			echo "<th>".t("Toim Tuoteno")."</th>";
		}

		echo "<th>".t("Osasto")."</th>";
		echo "<th>".t("Try")."</th>";
		echo "<th>".t("Hinta")."</th>";
		echo "<th>".t("Aleryhmä")."</th>";

		if ($lisatiedot != "" and $kukarow["extranet"] == "") {
			echo "<th>".t("Nettohinta")."</th>";
			echo "<th>".t("Status")."</th>";
		}

		echo "<th>".t("Myytävissä")."</th>";

        if ($kukarow["kesken"] != 0 or is_numeric($ostoskori)) {
			echo "<th></th>";
		}
		
		echo "</tr>";

		$edtuoteno = "";

		$yht_i = 0; // tää on meiän indeksi

		echo "<form action='$PHP_SELF' name='lisaa' method='post'>";
		echo "<input type='hidden' name='haku[0]' value = '$haku[0]'>";
		echo "<input type='hidden' name='haku[1]' value = '$haku[1]'>";
		echo "<input type='hidden' name='haku[2]' value = '$haku[2]'>";
		echo "<input type='hidden' name='haku[3]' value = '$haku[3]'>";
		echo "<input type='hidden' name='haku[4]' value = '$haku[4]'>";
		echo "<input type='hidden' name='haku[5]' value = '$haku[5]'>";
		echo "<input type='hidden' name='tee' value = 'TI'>";
		echo "<input type='hidden' name='toim_kutsu' value='$toim_kutsu'>";
		echo "<input type='hidden' name='ostoskori' value='$ostoskori'>";
		if ($toim == "FUTURSOFT") {
			echo "<input type='hidden' name='toim' value = '$toim'>";
		}

		foreach($rows as $row) {

			echo "<tr>";

			if (strtoupper($row["status"]) == "P") {
				$vari = "tumma";
			}
			else {
				$vari = "";
			}

			$lisakala = "";

			if ($row["korvaavat"] == $edtuoteno) {
				$lisakala = "* ";

				if ($vari == "") {
					$vari = 'spec';
				}
			}
			
			// Peek ahead
			$row_seuraava = next($rows); 
						
			if ($row["tuoteperhe"] == $row["tuoteno"] and $row["tuoteperhe"] != $row_seuraava["tuoteperhe"]) {
				$classleft = "";
				$classmidl = ""; 
				$classrigh = "";
			}			
			elseif($row["tuoteperhe"] == $row["tuoteno"]) {
				$classleft = "style='border-top: 1px solid; border-left: 1px solid;' ";
				$classmidl = "style='border-top: 1px solid;' ";
				$classrigh = "style='border-top: 1px solid; border-right: 1px solid;' ";
			}
			elseif($row["tuoteperhe"] != "" and $row["tuoteperhe"] != $row_seuraava["tuoteperhe"]) {
				$classleft = "style='border-bottom: 1px solid; border-left: 1px solid;' ";
				$classmidl = "style='border-bottom: 1px solid;' ";
				$classrigh = "style='border-bottom: 1px solid; border-right: 1px solid;' ";
			}
			elseif($row["tuoteperhe"] != '') {
				$classleft = "style='border-left: 1px solid;' ";
				$classmidl = "";
				$classrigh = "style='border-right: 1px solid;' ";
			}
			else {
				$classleft = "";
				$classmidl = ""; 
				$classrigh = "";
			}

			// Sarjanumerollisille tuotteille haetaan nimitys ostopuolen tilausriviltä
			if ($row["sarjanumeroseuranta"] != "") {
				$query	= "	SELECT sarjanumeroseuranta.*, tilausrivi_osto.nimitys nimitys, tilausrivi_myynti.tyyppi, lasku_myynti.nimi myynimi, lasku_myynti.tunnus myytunnus
							FROM sarjanumeroseuranta
							LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
							LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
							LEFT JOIN lasku lasku_myynti use index (PRIMARY) ON lasku_myynti.yhtio=sarjanumeroseuranta.yhtio and lasku_myynti.tunnus=tilausrivi_myynti.otunnus						
							WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
							and sarjanumeroseuranta.tuoteno = '$row[tuoteno]'
							and sarjanumeroseuranta.myyntirivitunnus != -1
							and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.tyyppi='T')
							and tilausrivi_osto.laskutettuaika != '0000-00-00'
							order by nimitys";
				$sarjares = mysql_query($query) or pupe_error($query);
								
				// Sarjanumerollisille tuotteille haetaan nimitys ostopuolen tilausriviltä
				if ($row["sarjanumeroseuranta"] == "S") {
					$nimitys = "";
					$nimilask = 1;
					
					if(mysql_num_rows($sarjares) > 0) {
						$nimitys .= "<table width='100%' valign='top'>";

						while ($sarjarow = mysql_fetch_array($sarjares)) {
							if($sarjarow["nimitys"] != "") {
								$nimitys .= "<tr><td valign='top'>$nimilask $sarjarow[nimitys]</td></tr>";
							}
							else {
								$nimitys .= "<tr><td valign='top'>$nimilask $row[nimitys]</td></tr>";
							}
							$nimilask++;
						}
					
						$nimitys .= "</table>";
					
						$row["nimitys"] = $nimitys;
					}
				}				
			}
			
			if(!isset($originaalit)) {
				$orginaaalit = table_exists("tuotteen_orginaalit");
			}
			
			$linkkilisa = "";
			
			//	Liitetään originaalitietoja
			if($orginaaalit === true) {
				$id = md5(uniqid());
				
				$query = "	SELECT * 
							FROM tuotteen_orginaalit
							WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$row[tuoteno]'";
				$orgres = mysql_query($query) or pupe_error($query);

				if(mysql_num_rows($orgres)>0) {
					$linkkilisa = "<div id='$id' class='popup' style='width: 300px'>
					<table width='300px' align='center'>
					<caption><font class='head'>Tuotteen originaalit</font></caption>
					<tr>
						<th>".t("Tuotenumero")."</th>
						<th>".t("Merkki")."</th>						
						<th>".t("Hinta")."</th>
					</tr>";

					while($orgrow = mysql_fetch_array($orgres)) {

						$linkkilisa .= "<tr>
								<td>{$orgrow["orig_tuoteno"]}</td>
								<td>{$orgrow["merkki"]}</td>
								<td>{$orgrow["orig_hinta"]}</td>								
							</tr>";
					}
					
					$linkkilisa .= "</table></div>";
					
					if($kukarow["extranet"] != "") {
						$linkkilisa .= "&nbsp;&nbsp;<a src='#' onmouseover=\"popUp(event, '$id');\" onmouseout=\"popUp(event, '$id');\"><img src='pics/lullacons/info.png' height='13'></a>";
					}
					else {
						$linkkilisa .= "&nbsp;&nbsp;<a src='#' onmouseover=\"popUp(event, '$id');\" onmouseout=\"popUp(event, '$id');\"><img src='../pics/lullacons/info.png' height='13'></a>";
					}
				}				
			}
			
			if ($kukarow["extranet"] != "") {
				echo "<td valign='top' class='$vari' $classleft>$lisakala $row[tuoteno] $linkkilisa</td>";
			}
			else {
				echo "<td valign='top' class='$vari' $classleft><a href='../tuote.php?tuoteno=$row[tuoteno]&tee=Z'>$lisakala $row[tuoteno]</a>$linkkilisa</td>";
			}
			
			echo "<td valign='top' class='$vari' $classmidl>".asana('nimitys_',$row['tuoteno'],$row['nimitys'])."</td>";

			if ($lisatiedot != "") {
				echo "<td valign='top' class='$vari' $classmidl>$row[toim_tuoteno]</td>";
			}

			echo "<td valign='top' class='$vari' $classmidl>$row[osasto]</td>";
			echo "<td valign='top' class='$vari' $classmidl>$row[try]</td>";

			$myyntihinta = sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["myyntihinta"]). " $yhtiorow[valkoodi]";

			// jos kyseessä on extranet asiakas yritetään näyttää kaikki hinnat oikeassa valuutassa
			if ($kukarow["extranet"] != "") {

				$query = "SELECT * from asiakas where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[oletus_asiakas]'";
				$oleasres = mysql_query($query) or pupe_error($query);
				$oleasrow = mysql_fetch_array($oleasres);

				if ($oleasrow["valkoodi"] != $yhtiorow["valkoodi"]) {

					$myyntihinta = sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["myyntihinta"])." $yhtiorow[valkoodi]";

					$query = "	SELECT *
								from hinnasto
								where yhtio = '$kukarow[yhtio]'
								and tuoteno = '$row[tuoteno]'
								and valkoodi = '$oleasrow[valkoodi]'
								and laji = ''
								and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
								order by ifnull(to_days(current_date)-to_days(alkupvm),9999999999999)
								limit 1";
					$olhires = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($olhires) == 1) {
						$olhirow = mysql_fetch_array($olhires);
						$myyntihinta = sprintf("%.".$yhtiorow['hintapyoristys']."f", $olhirow["hinta"])." $olhirow[valkoodi]";
					}
					else {
						$query = "SELECT * from valuu where yhtio='$kukarow[yhtio]' and nimi='$oleasrow[valkoodi]'";
						$olhires = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($oleasres) == 1) {
							$olhirow = mysql_fetch_array($olhires);
							$myyntihinta = sprintf("%.".$yhtiorow['hintapyoristys']."f", yhtioval($row["myyntihinta"], $olhirow["kurssi"])). " $oleasrow[valkoodi]";
						}
					}
				}

			}
			else {
				$query = "	SELECT distinct valkoodi, maa 
							from hinnasto
							where yhtio = '$kukarow[yhtio]'
							and tuoteno = '$row[tuoteno]'
							and laji = ''
							order by maa, valkoodi";
				$hintavalresult = mysql_query($query) or pupe_error($query);

				while ($hintavalrow = mysql_fetch_array($hintavalresult)) {

					// katotaan onko tuotteelle valuuttahintoja
					$query = "	SELECT *
								from hinnasto
								where yhtio = '$kukarow[yhtio]'
								and tuoteno = '$row[tuoteno]'
								and valkoodi = '$hintavalrow[valkoodi]'
								and maa = '$hintavalrow[maa]'
								and laji = ''
								and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
								order by ifnull(to_days(current_date)-to_days(alkupvm),9999999999999)
								limit 1";
					$hintaresult = mysql_query($query) or pupe_error($query);

					while ($hintarow = mysql_fetch_array($hintaresult)) {
						$myyntihinta .= "<br>$hintarow[maa]: ".sprintf("%.".$yhtiorow['hintapyoristys']."f", $hintarow["hinta"])." $hintarow[valkoodi]";
					}
				}
			}

			echo "<td valign='top' class='$vari' align='right' $classmidl>$myyntihinta</td>";
			echo "<td valign='top' class='$vari' $classmidl>$row[aleryhma]</td>";

			if ($lisatiedot != "" and $kukarow["extranet"] == "") {
				echo "<td valign='top' class='$vari' $classmidl>".sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["nettohinta"])."</td>";
				echo "<td valign='top' class='$vari' $classmidl>$row[status]</td>";
			}

			$edtuoteno = $row["korvaavat"];

			
			if ($row["tuoteperhe"] == $row["tuoteno"]) {
				// Tuoteperheen isä
				if ($kukarow["extranet"] != "") {
					$saldot = tuoteperhe_myytavissa($row["tuoteno"], "KAIKKI", "", 0, "", "", "", "", "", $laskurow["toim_maa"], $saldoaikalisa);
					
					$kokonaismyytavissa = 0;
					 
					foreach ($saldot as $varasto => $myytavissa) {								
						$kokonaismyytavissa += $myytavissa;								
					}

					if ($kokonaismyytavissa > 0) {
						echo "<td valign='top' class='green' $classrigh>".t("On")."</td>";
					}
					else {
						echo "<td valign='top' class='red' $classrigh>".t("Ei")."</td>";
					}					
				}
				else {
					$saldot = tuoteperhe_myytavissa($row["tuoteno"], "", "KAIKKI", 0, "", "", "", "", "", $laskurow["toim_maa"], $saldoaikalisa);
			
					echo "<td valign='top' $classrigh>";
					echo "<table width='100%'>";
				
					foreach ($saldot as $varaso => $saldo) {					
						echo "<tr><td class='$vari' nowrap>$varaso</td><td class='$vari' align='right' nowrap>".sprintf("%.2f", $saldo)." $row[yksikko]</td></tr>";
					}
					echo "</table></td>";
				}
			}			
			elseif ($row['ei_saldoa'] != '') {
				if ($kukarow["extranet"] != "") {
					echo "<td valign='top' class='green' $classrigh>".t("On")."</td>";
				}
				else {
					echo "<td valign='top' class='green' $classrigh>".t("Saldoton")."</td>";
				}
			}
			elseif ($row["sarjanumeroseuranta"] == "S") {

				if (is_resource($sarjares) and mysql_num_rows($sarjares)) {
					mysql_data_seek($sarjares, 0);
				}

				echo "<td valign='top' $csp $classrigh><table width='100%'>";
				
				$nimilask = 1;

				while ($sarjarow = mysql_fetch_array($sarjares)) {
					
					if ($kukarow["extranet"] == "") {

						echo "<tr><td class='$vari' nowrap>$nimilask <a onClick=\"javascript:sarjanumeronlisatiedot_popup('$sarjarow[tunnus]')\">$sarjarow[sarjanumero]</a>";
					
						if ($sarjarow["tyyppi"] == "T") {
							echo "<br><font class='message'>(".t("Tarjous").": $sarjarow[myytunnus] $sarjarow[myynimi])</font>";
						}
						
						echo "</td>";
					}
					else {
						echo "<tr><td class='$vari' nowrap>$nimilask <a onClick=\"javascript:sarjanumeronlisatiedot_popup('$sarjarow[tunnus]')\">$sarjarow[sarjanumero]</a></td>";
					}
					
					$nimilask++;

					if ($kukarow["kesken"] != 0 or is_numeric($ostoskori)) {
						echo "<td valign='top' class='$vari' nowrap>";
						echo "<input type='hidden' name='tiltuoteno[$yht_i]' value = '$row[tuoteno]'>";
						echo "<input type='hidden' name='tilsarjatunnus[$yht_i]' value = '$sarjarow[tunnus]'>";
						echo "<input type='checkbox' name='tilkpl[$yht_i]' value='1'> ";
						echo "</td>";
						$yht_i++;
					}
					echo "</tr>";

				}
				echo "</table>";

				if ($kukarow["kesken"] != 0 or is_numeric($ostoskori)) {
					echo "<td valign='top' align='right' class='$vari' nowrap>";
					if ($toim == "FUTURSOFT") {
						echo "<input type='hidden' name='toim' value = '$toim'>";
					}
					echo "<input type='hidden' name='tiltuoteno[$yht_i]' value = '$row[tuoteno]'>";
					echo "<input type='text' size='3' name='tilkpl[$yht_i]'> ";
					echo "<input type='submit' value = '".t("Lisää")."'>";
					echo "</td>";
					$yht_i++;	
				}
				
				echo "</td>";
			}
			elseif ($kukarow["extranet"] != "") {

				list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"], "", 0, "", "", "", "", "", $laskurow["toim_maa"], $saldoaikalisa);

				if ($myytavissa > 0) {
					echo "<td valign='top' class='green' $classrigh>".t("On")."</td>";
				}
				else {
					$tulossa_query = " 	SELECT IFNULL(MIN(toimaika),'') paivamaara
					 					FROM tilausrivi
										WHERE yhtio='{$kukarow['yhtio']}' AND tuoteno='{$row['tuoteno']}' AND varattu > 0 AND tyyppi='O'";
					$tulossa_result = mysql_query($tulossa_query) or pupe_error($tulossa_query);
					$tulossa_row = mysql_fetch_array($tulossa_result);

					if (mysql_num_rows($tulossa_result) > 0 and $tulossa_row["paivamaara"] != '') {
						echo "<td valign='top' $classrigh>".t("Tulossa")." ".tv1dateconv($tulossa_row['paivamaara'])."</td>";
					}
					else {
						echo "<td valign='top' class='red' $classrigh>".t("Ei")."</td>";
					}
				}
			}
			else {

				if ($laskurow["toim_maa"] != '') {
					$sallitut_maat_lisa = " and (varastopaikat.sallitut_maat like '%$laskurow[toim_maa]%' or varastopaikat.sallitut_maat = '') ";
				}

				// Käydään läpi tuotepaikat
				if ($row["sarjanumeroseuranta"] == "E" or $row["sarjanumeroseuranta"] == "F") {
					$query = "	SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa, 
								tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
								sarjanumeroseuranta.sarjanumero era,
								concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0')) sorttauskentta,
								varastopaikat.nimitys, if(varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
					 			FROM tuote
								JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
								JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
								$sallitut_maat_lisa
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
								and tuote.tuoteno = '$row[tuoteno]'
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
								$sallitut_maat_lisa
								and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
								and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
								WHERE tuote.yhtio = '$kukarow[yhtio]'
								and tuote.tuoteno = '$row[tuoteno]'
								ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
				}
				$varresult = mysql_query($query) or pupe_error($query);

				echo "<td valign='top' $classrigh>";

				if (mysql_num_rows($varresult) > 0) {

					echo "<table width='100%'>";

					// katotaan jos meillä on tuotteita varaamassa saldoa joiden varastopaikkaa ei enää ole olemassa...
					list($saldo, $hyllyssa, $orvot) = saldo_myytavissa($row["tuoteno"], 'ORVOT', '', '', '', '', '', '', '', $saldoaikalisa);
					$orvot *= -1;

					while ($saldorow = mysql_fetch_array ($varresult)) {

						list($saldo, $hyllyssa, $myytavissa, $sallittu) = saldo_myytavissa($saldorow["tuoteno"], '', '', $saldorow["yhtio"], $saldorow["hyllyalue"], $saldorow["hyllynro"], $saldorow["hyllyvali"], $saldorow["hyllytaso"], $laskurow["toim_maa"], $saldoaikalisa, $saldorow["era"]);

						//	Listataan vain varasto jo se ei ole kielletty
						if($sallittu === TRUE) {
							// hoidetaan pois problematiikka jos meillä on orpoja (tuotepaikattomia) tuotteita varaamassa saldoa
							if ($orvot > 0) {
								if ($myytavissa >= $orvot and $saldorow["yhtio"] == $kukarow["yhtio"]) {
							    	// poistaan orpojen varaamat tuotteet tältä paikalta
							    	$myytavissa = $myytavissa - $orvot;
							    	$orvot = 0;
								}
								elseif ($orvot > $myytavissa and $saldorow["yhtio"] == $kukarow["yhtio"]) {
							    	// poistetaan niin paljon orpojen saldoa ku voidaan
							    	$orvot = $orvot - $myytavissa;
							    	$myytavissa = 0;
								}
							}

							echo "	<tr>
									<td class='$vari' nowrap>$saldorow[nimitys] $saldorow[tyyppi]</td>
									<td class='$vari' align='right' nowrap>".sprintf("%.2f", $myytavissa)." $row[yksikko]</td>
									</tr>";
						}
					}
					echo "</table></td>";
				}
				echo "</td>";
			}

			if (($row["sarjanumeroseuranta"] == "" or $row["sarjanumeroseuranta"] == "E"  or $row["sarjanumeroseuranta"] == "F") and ($kukarow["kesken"] != 0 or is_numeric($ostoskori))) {
				echo "<td valign='top' align='right' class='$vari' nowrap>";
				if ($toim == "FUTURSOFT") {
					echo "<input type='hidden' name='toim' value = '$toim'>";
				}
				echo "<input type='hidden' name='tiltuoteno[$yht_i]' value = '$row[tuoteno]'>";
				echo "<input type='text' size='3' name='tilkpl[$yht_i]'> ";
				echo "<input type='submit' value = '".t("Lisää")."'>";
				echo "</td>";
				$yht_i++;
			}

			unset($images_exist);
			unset($pdf_exist);
			unset($filetype);
			
			$filetype_query = "	SELECT * FROM liitetiedostot WHERE yhtio='{$kukarow['yhtio']}' and liitos='tuote' AND liitostunnus='{$row['tunnus']}'";
			$filetype_result = mysql_query($filetype_query) or pupe_error($filetype_query);
			
			$filetype_row = mysql_fetch_array($filetype_result);

			if (mysql_num_rows($filetype_result) > 0) {
				if (in_array("image/jpeg", $filetype_row) or in_array("image/jpg", $filetype_row) or in_array("image/gif", $filetype_row) or in_array("image/png", $filetype_row)) {						
					list ($prefix, $filetype) = explode("/", $filetype_row["filetype"]);				
					$filetype = strtolower($filetype);

					if ($filetype == "jpeg" or $filetype == "jpg" or $filetype == "gif" or $filetype == "png") {
						$query = "	SELECT MAX(image_width) AS max_width, SUM(image_height) AS total_height, count(liitostunnus) AS kpl
						 			FROM liitetiedostot 
									WHERE yhtio='$kukarow[yhtio]' 
									AND liitos='tuote' 
									AND liitostunnus='$row[tunnus]'";
						$kuvares = mysql_query($query) or pupe_error($query);

						$apurow = mysql_fetch_array($kuvares);
						$maxwidth = $apurow["max_width"] + 30;
						$totalheight = $apurow["total_height"] + 60;
					
						if ($apurow["kpl"] > 0) {
							$images_exist = 1;
						}
					}
				}
				else if (in_array("application/pdf", $filetype_row)) {
					$maxwidth = 0;
					$totalheight = 0;
					$pdf_exist = 1;
				}
			}
				
			if ($lisatiedot != "" and (isset($images_exist) or isset($pdf_exist))) {
				echo "<td class='back'><input type='button' style='width: 55px; height: 20px' value='";
				if ($pdf_exist) {
					echo t("Pdf");
				}
				else {
					echo t("Kuva");
				}
				echo "' onClick=\"javascript:picture_popup('$row[tunnus]', '$maxwidth', '$totalheight', '$row[tuoteno]')\"></td>";				
			}

			echo "</tr>";
		}

		echo "</form>";
		echo "</table>";

	}
	else {
		echo t("Yhtään tuotetta ei löytynyt")."!";
	}

	if(mysql_num_rows($result) == 500) {
		echo "<br><br><font class='message'>".t("Löytyi yli 500 tuotetta, tarkenna hakuasi")."!</font>";
	}

	if (file_exists("../inc/footer.inc")) {
		require ("../inc/footer.inc");
	}
	else {
		require ("footer.inc");
	}
?>
