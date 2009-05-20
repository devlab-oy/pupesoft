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

		if ($tultiin == "futur") {
			$kori_polku .= "?ostoskori=".$ostoskori."&tultiin=".$tultiin;
		}
	}

	if (function_exists("js_popup")) {
		echo js_popup(-100);
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
						window.open('$PHP_SELF?tuoteno='+tuoteno+'&ohje=off&toiminto=avaa_kuva&tunnus='+tuote_tunnus+'&laji=tuotekuva', '_blank' ,'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,left=0,top = 0, width='+myWidth+', height='+myHeight);
					}
					else {
						window.open('$PHP_SELF?tuoteno='+tuoteno+'&ohje=off&toiminto=avaa_kuva&&maxi='+maxwidth+'&tunnus='+tuote_tunnus+'&laji=tuotekuva', '_blank' ,'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,left=0,top = 0, width='+maxwidth+', height='+totalheight);
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

	if (!isset($toiminto)) {
		$toiminto = '';
	}

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
		$query = "	SELECT tunnus, selite, filetype
		 			FROM liitetiedostot
					WHERE yhtio='$kukarow[yhtio]'
					AND liitos='tuote'
					AND kayttotarkoitus not in ('thumb','TH')
					AND liitostunnus='$tunnus'
					ORDER BY kayttotarkoitus, jarjestys, filename";

		$kuvares = mysql_query($query) or pupe_error($query);

		echo "<table border='0' cellspacing='5' align='center'";
		if ($maxwidth) {
			echo "width='$maxwidth'";
		}
		echo ">";

		while ($kuvarow = mysql_fetch_array($kuvares)) {
			echo "<tr><td class='back' align='center' valign='top'>";

			if ($kuvarow["filetype"] == "application/pdf") {
				echo "<a href='".$palvelin2."view.php?id=$kuvarow[tunnus]' target='_top'>".t("Avaa pdf")."</a></td></tr>";
			}
			else {
				if ($maxi > 0) {
					$maxi = "width = '$maxi' ";
				}
				echo "<img $maxi src='".$palvelin2."view.php?id=$kuvarow[tunnus]'></td></tr>";
			}

			echo "<tr><td class='back' align='center' valign='top'>$kuvarow[selite]</td></tr>";
			echo "<tr><td class='back'><br></td></tr>";
		}
		echo "</table>";
		exit;
	}

	echo "<font class='head'>".t("Etsi ja selaa tuotteita").":</font><hr>";

	if (!isset($toim_kutsu)) {
		$toim_kutsu = '';
	}

	if ($toim_kutsu == "") {
		$toim_kutsu = "RIVISYOTTO";
	}

	$query    = "SELECT * from lasku where tunnus='$kukarow[kesken]' and yhtio='$kukarow[yhtio]'";
	$result   = mysql_query($query) or pupe_error($query);
	$laskurow = mysql_fetch_array($result);

	if (!isset($ostoskori)) {
		$ostoskori = '';
	}

	if (is_numeric($ostoskori)) {
		echo "<table><tr><td class='back'>";
		echo "	<form method='post' action='$kori_polku'>
				<input type='hidden' name='tee' value='poistakori'>
				<input type='hidden' name='ostoskori' value='$ostoskori'>
				<input type='hidden' name='pyytaja' value='haejaselaa'>
				<input type='submit' value='".t("Tyhjenn‰ ostoskori")."'>
				</form>";
		echo "</td><td class='back'>";
		echo "	<form method='post' action='$kori_polku'>
				<input type='hidden' name='tee' value=''>
				<input type='hidden' name='ostoskori' value='$ostoskori'>
				<input type='hidden' name='pyytaja' value='haejaselaa'>
				<input type='submit' value='".t("N‰yt‰ ostoskori")."'>
				</form>";
		echo "</td></tr></table>";
	}
	elseif ($kukarow["kuka"] != "" and ($laskurow["tila"] == "L" or $laskurow["tila"] == "N" or $laskurow["tila"] == "T" or $laskurow["tila"] == "A" or $laskurow["tila"] == "S")) {

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
	elseif ($kukarow["kuka"] != "" and $laskurow["tila"] == "O") {

		echo "	<form method='post' action='tilaus_osto.php'>
				<input type='hidden' name='aktivoinnista' value='true'>
				<input type='hidden' name='tee' value='AKTIVOI'>
				<input type='hidden' name='tilausnumero' value='$kukarow[kesken]'>
				<input type='submit' value='".t("Takaisin tilaukselle")."'>
				</form><br><br>";
	}
	
	if (!isset($tee)) {
		$tee = '';
	}

	if (!isset($orderlisa)) {
		$orderlisa = '';
	}

	// automotivelle purkkafixi jotta saadaan kauniimmat dropdownit
	if (!isset($yhtiorow['naytetaan_kaunis_os_try']) and $kukarow['yhtio'] == 'artr') {
		$yhtiorow['naytetaan_kaunis_os_try'] = 'K';
		$orderlisa = "ORDER BY avainsana.selitetark";
	}
	else {
		$yhtiorow['naytetaan_kaunis_os_try'] = '';
		$orderlisa = "ORDER BY avainsana.jarjestys, avainsana.selite+0";
	} 

	// Tarkistetaan tilausrivi
	if (($tee == 'TI' or is_numeric($ostoskori)) and isset($tilkpl)) {

		if (is_numeric($ostoskori)) {
			$kori = check_ostoskori($ostoskori,$kukarow["oletus_asiakas"]);
			$kukarow["kesken"] = $kori["tunnus"];
		}

		// haetaan avoimen tilauksen otsikko
		if ($kukarow["kesken"] != 0) {
			$query    = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
			$laskures = mysql_query($query);
		}
		else {
			// Luodaan uusi myyntitilausotsikko
			if ($kukarow["extranet"] == "") {
				require_once("tilauskasittely/luo_myyntitilausotsikko.inc");
				$tilausnumero = luo_myyntitilausotsikko(0);
				$kukarow["kesken"] = $tilausnumero;
				$kaytiin_otsikolla = "NOJOO!";
			}
			else {
				require_once("luo_myyntitilausotsikko.inc");
				$tilausnumero = luo_myyntitilausotsikko($kukarow["oletus_asiakas"]);
				$kukarow["kesken"] = $tilausnumero;
				$kaytiin_otsikolla = "NOJOO!";
			}

			// haetaan avoimen tilauksen otsikko
			$query    = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
			$laskures = mysql_query($query);
		}

		if ($kukarow["kesken"] != 0 and $laskures != '') {
			// tilauksen tiedot
			$laskurow = mysql_fetch_array($laskures);
		}

		if (is_numeric($ostoskori)) {
			echo "<font class='message'>Lis‰t‰‰n tuotteita ostoskoriin $ostoskori.</font><br>";
		}
		else {
			echo "<font class='message'>Lis‰t‰‰n tuotteita tilaukselle $kukarow[kesken].</font><br>";
		}

		// K‰yd‰‰n l‰pi formin kaikki rivit
		foreach ($tilkpl as $yht_i => $kpl) {

			if ((float) $kpl > 0 or ($kukarow["extranet"] == "" and (float) $kpl < 0)) {

				// haetaan tuotteen tiedot
				$query    = "select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tiltuoteno[$yht_i]'";
				$tuoteres = mysql_query($query);

				if (mysql_num_rows($tuoteres) == 0) {
					echo "<font class='error'>Tuotetta $tiltuoteno[$yht_i] ei lˆydy!</font><br>";
				}
				else {
					// tuote lˆytyi ok, lis‰t‰‰n rivi
					$trow = mysql_fetch_array($tuoteres);

					$ytunnus         = $laskurow["ytunnus"];
					$kpl             = (float) $kpl;
					$kpl_echo 		 = (float) $kpl;
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
					$jtkielto 		 = $laskurow['jtkielto'];
					$varataan_saldoa = "";
					$myy_sarjatunnus = $tilsarjatunnus[$yht_i];

					if ($tilpaikka[$yht_i] != '') {
						$paikka	= $tilpaikka[$yht_i];
					}
					else {
						$paikka	= "";
					}

					// jos meill‰ on ostoskori muuttujassa numero, niin halutaan lis‰t‰ tuotteita siihen ostoskoriin
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

					echo "<font class='message'>".t("Lis‰ttiin")." $kpl_echo ".ta($kieli, "Y", $trow["yksikko"])." ".t("tuotetta")." $tiltuoteno[$yht_i].</font><br>";

					//Hanskataan sarjanumerollisten tuotteiden lis‰varusteet
					if ($tilsarjatunnus[$yht_i] > 0 and $lisatty_tun > 0) {
						require("sarjanumeron_lisavarlisays.inc");

						lisavarlisays($tilsarjatunnus[$yht_i], $lisatty_tun);
					}
				} // tuote ok else
			} // end kpl > 0
		} // end foreach

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

	$jarjestys = "tuote.tuoteno";

	$lisa  				= "";
	$ulisa 				= "";
	$toimtuotteet 		= "";
	$origtuotteet 		= "";
	$poislisa_mulsel 	= "";
	
	if (!isset($ojarj)) {
		$ojarj = '';
	}

	if (strlen($ojarj) > 0) {
		$ojarj = trim(mysql_real_escape_string($ojarj));

		if ($ojarj == 'tuoteno') {
			$jarjestys = 'tuote.tuoteno';
		}
		elseif ($ojarj == 'toim_tuoteno') {
			$jarjestys = 'tuote.tuoteno';
		}
		elseif ($ojarj == 'nimitys') {
			$jarjestys = 'tuote.nimitys';
		}
		elseif ($ojarj == 'osasto') {
			$jarjestys = 'tuote.osasto';
		}
		elseif ($ojarj == 'try') {
			$jarjestys = 'tuote.try';
		}
		elseif ($ojarj == 'hinta') {
			$jarjestys = 'tuote.myyntihinta';
		}
		elseif ($ojarj == 'nettohinta') {
			$jarjestys = 'tuote.nettohinta';
		}
		elseif ($ojarj == 'aleryhma') {
			$jarjestys = 'tuote.aleryhma';
		}
		elseif ($ojarj == 'status') {
			$jarjestys = 'tuote.status';
		}
		else {
			$jarjestys = 'tuote.tuoteno';
		}
	}

	if (!isset($poistetut)) {
		$poistetut = '';
	}
	
	if ($poistetut != "") {
		
		$poischeck = "CHECKED";
		$ulisa .= "&poistetut=checked";
		
		if ($kukarow["extranet"] != "") {
			// N‰ytet‰‰n vain poistettuja tuotteita
			$poislisa  			= " and tuote.status in ('P','X') 
									and (SELECT sum(saldo) 
										 FROM tuotepaikat																				
										 JOIN varastopaikat on (varastopaikat.yhtio=tuotepaikat.yhtio 
										 and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0')) 
										 and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
										 and varastopaikat.tyyppi = '')																		
										 WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0 ";
			
			$poislisa_mulsel	= " and tuote.status in ('P','X') ";
		}
		else {
			$poislisa  		 	= "";
			//$poislisa_mulsel	= "";
		}
	}
	else {
		$poislisa  = " and (tuote.status not in ('P','X') 
						or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0) ";
		//$poislisa_mulsel  = " and tuote.status not in ('P','X') ";
		$poischeck = "";
	}

	if (!isset($lisatiedot)) {
		$lisatiedot = '';
	}

	if ($lisatiedot != "") {
		$lisacheck = "CHECKED";
		$ulisa .= "&lisatiedot=checked";
	}
	else {
		$lisacheck = "";
	}

	if ($kukarow["extranet"] != "") {
		
		// K‰ytet‰‰n alempana
		$query = "SELECT * from asiakas where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[oletus_asiakas]'";
		$oleasres = mysql_query($query) or pupe_error($query);
		$oleasrow = mysql_fetch_array($oleasres);
		
		$query = "SELECT * from valuu where yhtio='$kukarow[yhtio]' and nimi='$oleasrow[valkoodi]'";
		$olhires = mysql_query($query) or pupe_error($query);
		$olhirow = mysql_fetch_array($olhires);
		
		$extra_poislisa = " and tuote.hinnastoon != 'E' ";
		$avainlisa = " and avainsana.jarjestys < 10000";
	}
	else {
		$extra_poislisa = "";
		$avainlisa = "";
	}

	if (!isset($nimitys)) {
		$nimitys = '';
	}

	if (trim($nimitys) != '') {
		$nimitys = mysql_real_escape_string(trim($nimitys));
		$lisa .= " and tuote.nimitys like '%$nimitys%' ";
		$ulisa .= "&nimitys=$nimitys";
	}

	if (!isset($tuotenumero)) {
		$tuotenumero = '';
	}

	if (trim($tuotenumero) != '') {
		$tuotenumero = mysql_real_escape_string(trim($tuotenumero));
		$lisa .= " and tuote.tuoteno like '%$tuotenumero%' ";
		$ulisa .= "&tuotenumero=$tuotenumero";
	}

	if (!isset($toim_tuoteno)) {
		$toim_tuoteno = '';
	}

	if (trim($toim_tuoteno) != '') {
		$toim_tuoteno = mysql_real_escape_string(trim($toim_tuoteno));

		//Otetaan konserniyhtiˆt hanskaan
		$query	= "	SELECT distinct tuoteno
					FROM tuotteen_toimittajat
					WHERE yhtio = '$kukarow[yhtio]'
					and toim_tuoteno like '%".$toim_tuoteno."%'
					LIMIT 500";
		$pres = mysql_query($query) or pupe_error($query);

		while($prow = mysql_fetch_array($pres)) {
			$toimtuotteet .= "'".$prow["tuoteno"]."',";
		}

		$toimtuotteet = substr($toimtuotteet, 0, -1);

		if ($toimtuotteet != "") {
			$lisa .= " and tuote.tuoteno in ($toimtuotteet) ";
		}

		$ulisa .= "&toim_tuoteno=$toim_tuoteno";
	}

	if (!isset($alkuperaisnumero)) {
		$alkuperaisnumero = '';
	}

	if (trim($alkuperaisnumero) != '') {
		$alkuperaisnumero = mysql_real_escape_string(trim($alkuperaisnumero));

		$query	= "	SELECT distinct tuoteno
					FROM tuotteen_orginaalit
					WHERE yhtio = '$kukarow[yhtio]'
					and orig_tuoteno like '%$alkuperaisnumero%'
					LIMIT 500";
		$pres = mysql_query($query) or pupe_error($query);	

		while($prow = mysql_fetch_assoc($pres)) {
			$origtuotteet .= "'".$prow["tuoteno"]."',";
		}

		$origtuotteet = substr($origtuotteet, 0, -1);

		if ($origtuotteet != "") {
			$lisa .= " and tuote.tuoteno in ($origtuotteet) ";
		}

		$ulisa .= "&alkuperaisnumero=$alkuperaisnumero";		
	}

	// vientikieltok‰sittely:
	// +maa tarkoittaa ett‰ myynti on kielletty t‰h‰n maahan ja sallittu kaikkiin muihin
	// -maa tarkoittaa ett‰ ainoastaan t‰h‰n maahan saa myyd‰
	// eli n‰ytet‰‰n vaan tuotteet jossa vienti kent‰ss‰ on tyhj‰‰ tai -maa.. ja se ei saa olla +maa
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
	
	if (file_exists('sarjanumeron_lisatiedot_popup.inc')) {
		require("sarjanumeron_lisatiedot_popup.inc");
	}

	echo "<form action = '$PHP_SELF?toim_kutsu=$toim_kutsu' method = 'post'>";
	echo "<input type='hidden' name='ostoskori' value='$ostoskori'>";

	if (!isset($tultiin)) {
		$tultiin = '';
	}

	if ($tultiin == "futur") {
		echo " <input type='hidden' name='tultiin' value='$tultiin'>";
	}
	
	echo "<table style='display:inline;' valign='top'>";
	echo "<tr><th>",t("Tuotenumero"),"</th><td nowrap valign='top' colspan='2'><input type='text' size='25' name='tuotenumero' id='tuotenumero' value = '$tuotenumero'></td></tr>";
	echo "<tr><th>",t("Toim tuoteno"),"</th><td nowrap valign='top' colspan='2'><input type='text' size='25' name = 'toim_tuoteno' id='toim_tuoteno' value = '$toim_tuoteno'></td></tr>";
	
	$orginaaalit = FALSE;
	
	if (table_exists("tuotteen_orginaalit")) {
		$query	= "	SELECT tunnus
					FROM tuotteen_orginaalit
					WHERE yhtio = '$kukarow[yhtio]'
					LIMIT 1";
		$orginaaleja_res = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($orginaaleja_res) > 0) {
			echo "<tr><th>",t("Alkuper‰isnumero"),"</th><td nowrap valign='top' colspan='2'><input type='text' size='25' name = 'alkuperaisnumero' id='alkuperaisnumero' value = '$alkuperaisnumero'></td></tr>";
		
			$orginaaalit = TRUE;
		}
	}

	echo "<tr><th>",t("Nimitys"),"</th><td nowrap valign='top' colspan='2'><input type='text' size='25' name='nimitys' id='nimitys' value = '$nimitys'></td></tr>";
	
	if ($kukarow["extranet"] != "") {
		echo "<tr><th>",t("Tarjoustuotteet"),"</th><td nowrap valign='top' colspan='2'><input type='checkbox' name='poistetut' id='poistetut' $poischeck></td></tr>";
	}
	else {
		echo "<tr><th>",t("Poistetut"),"</th><td nowrap valign='top' colspan='2'><input type='checkbox' name='poistetut' id='poistetut' $poischeck></td></tr>";
	}	
	
	echo "<tr><th>",t("Lis‰tiedot"),"</th><td nowrap valign='top' colspan='2'><input type='checkbox' name='lisatiedot' id='lisatiedot' $lisacheck></td></tr>";
	echo "</table>";

	$lisa_haku_osasto 		 = "";
	$lisa_haku_try 			 = "";
	$lisa_haku_tme 			 = "";
	$lisa_haku_malli 		 = "";
	$lisa_haku_mallitarkenne = "";

	if (!isset($mul_osasto)) {
		$mul_osasto = array();
	}

	if (!isset($mul_try)) {
		$mul_try = array();
	}

	if (!isset($mul_tme)) {
		$mul_tme = array();
	}

	// jos on valittu jotakin dropdowneista (muu kuin osasto) niin tehd‰‰n niill‰ rajaukset muihin dropdowneihin
	if (count($mul_osasto) > 0 or count($mul_try) > 0 or count($mul_tme) > 0) {
		if (count($mul_osasto) > 0) {
			$osastot = '';

			foreach ($mul_osasto as $osx) {
				if (trim($osx) != '') {
					if (trim($osx) != "PUPEKAIKKIMUUT") {
						$osx = trim(mysql_real_escape_string($osx));
						$osastot .= "'$osx',";
					}					
				}
			}

			$osastot = substr($osastot, 0, -1);
			
			if (trim($osastot) != '') {
				$lisa_haku_osasto = " and tuote.osasto in ($osastot) ";
				$lisa .= $lisa_haku_osasto;
				$ulisa .= "&mul_osasto[]=".urlencode($osastot);
			}
		}

		if (count($mul_try) > 0) {
			$tryt = '';

			foreach ($mul_try as $tryx) {
				if (trim($tryx) != '') {
					if (trim($tryx) != "PUPEKAIKKIMUUT") {
						$tryx = trim(mysql_real_escape_string($tryx));
						$tryt .= "'$tryx',";
					}					
				}
			}

			$tryt = substr($tryt, 0, -1);
			
			if (trim($tryt) != '') {
				$lisa_haku_try = " and tuote.try in ($tryt) ";
				$lisa .= $lisa_haku_try;
				$ulisa .= "&mul_try[]=".urlencode($tryt);
			}
		}

		if (count($mul_tme) > 0) {
			$tmet = '';

			foreach ($mul_tme as $tmex) {
				if (trim($tmex) != '') {
					if (trim($tmex) != "PUPEKAIKKIMUUT") {
						$tmex = trim(mysql_real_escape_string(urldecode($tmex)));
						$tmet .= "'$tmex',";
					}					
				}
			}

			$tmet = substr($tmet, 0, -1);
			
			if (trim($tmet) != '') {
				$lisa_haku_tme = " and tuote.tuotemerkki in ($tmet) ";
				$lisa .= $lisa_haku_tme;
				$ulisa .= "&mul_tme[]=".urlencode($tmet);
			}
		}

		if (count($mul_malli) > 0) {
			$mallit = '';

			foreach ($mul_malli as $mallix) {
				if (trim($mallix) != '') {
					if (count($_GET['mul_malli']) > 0) {
						$mallix = rawurldecode($mallix);
					}
					
					if (trim($mallix) != "PUPEKAIKKIMUUT") {
						$mallit .= "'".mysql_real_escape_string($mallix)."',";
						$ulisa .= "&mul_malli[]=".rawurlencode($mallix);
					}					
				}
			}

			$mallit = substr($mallit, 0, -1);
			
			if (trim($mallit) != '') {
				$lisa_haku_malli = " and tuote.malli in ($mallit) ";
				$lisa .= $lisa_haku_malli;
			}
		}

		if (count($mul_mallitarkenne) > 0) {
			$mallitarkenteet = '';

			foreach ($mul_mallitarkenne as $mallitarkennex) {
				if (trim($mallitarkennex) != '') {
					if (count($_GET['mul_mallitarkenne']) > 0) {
						$mallitarkennex = rawurldecode($mallitarkennex);
					}
					
					if (trim($mallitarkennex) != "PUPEKAIKKIMUUT") {
						$mallitarkenteet .= "'".mysql_real_escape_string($mallitarkennex)."',";
						$ulisa .= "&mul_mallitarkenne[]=".rawurlencode($mallitarkennex);
					}					
				}
			}

			$mallitarkenteet = substr($mallitarkenteet, 0, -1);
			
			if (trim($mallitarkenteet) != '') {
				$lisa_haku_mallitarkenne = " and tuote.mallitarkenne in ($mallitarkenteet) ";
				$lisa .= $lisa_haku_mallitarkenne;
			}
		}
	}

	$query = "	SELECT DISTINCT avainsana.selite,
	            IFNULL((SELECT avainsana_kieli.selitetark
	            FROM avainsana as avainsana_kieli
	            WHERE avainsana_kieli.yhtio = avainsana.yhtio
	            and avainsana_kieli.laji = avainsana.laji
	            and avainsana_kieli.selite = avainsana.selite
	            and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selitetark) selitetark
	            FROM avainsana
	            WHERE avainsana.yhtio = '$kukarow[yhtio]'
	            and avainsana.laji = 'OSASTO'
	            and avainsana.kieli in ('$yhtiorow[kieli]', '')
	            $avainlisa
	            $orderlisa";
	$sresult = mysql_query($query) or pupe_error($query);
	
	echo "<table style='display:inline;'>";
	echo "<tr><th>",t("Osasto"),"</th></tr>";
	echo "<tr><td nowrap valign='top' class='back'><select name='mul_osasto[]' multiple size='7' onchange='submit();'>";
	$mul_check = "";
	if ($mul_try!="") {
		if (in_array("PUPEKAIKKIMUUT", $mul_osasto)) {
			$mul_check = 'SELECTED';
		}
	}
		
	echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("N‰yt‰ kaikki")."</option>";
	echo "<option value=''>".t("Ei valintaa")."</option>";
	
	while($sxrow = mysql_fetch_array ($sresult)){
		$sel = '';

		if (count($mul_osasto) > 0) {
			if (in_array(trim($sxrow['selite']), $mul_osasto)) {
				$sel = 'SELECTED';
			}
		}
		
		echo "<option value='$sxrow[selite]' $sel>";
		if ($yhtiorow['naytetaan_kaunis_os_try'] == '') {
			echo $sxrow['selite']." ";
		}
		echo "$sxrow[selitetark]</option>";
	}
	echo "</select></td>";
	echo "</tr></table>";

	if ($lisa_haku_osasto == "") {
		$query = "	SELECT DISTINCT avainsana.selite,
		            IFNULL((SELECT avainsana_kieli.selitetark
		            FROM avainsana as avainsana_kieli
		            WHERE avainsana_kieli.yhtio = avainsana.yhtio
		            and avainsana_kieli.laji = avainsana.laji
		            and avainsana_kieli.selite = avainsana.selite
		            and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selitetark) selitetark
		            FROM avainsana
		            WHERE avainsana.yhtio = '$kukarow[yhtio]'
		            and avainsana.laji = 'TRY'
		            and avainsana.kieli in ('$yhtiorow[kieli]', '')
		            $avainlisa
		            $orderlisa";
	}
	else {
		$query = "	SELECT distinct avainsana.selite,
					IFNULL((SELECT avainsana_kieli.selitetark
			        FROM avainsana as avainsana_kieli
			        WHERE avainsana_kieli.yhtio = avainsana.yhtio
			        and avainsana_kieli.laji = avainsana.laji
			        and avainsana_kieli.selite = avainsana.selite
			        and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selitetark) selitetark
					FROM tuote
					JOIN avainsana ON (avainsana.yhtio = tuote.yhtio and tuote.try = avainsana.selite and avainsana.laji = 'TRY' and avainsana.kieli in ('$yhtiorow[kieli]', '') $avainlisa)
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					$lisa_haku_osasto
					$kieltolisa
					$extra_poislisa
					$poislisa_mulsel
					$orderlisa";
	}
	
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<table style='display:inline;'><tr><th>",t("Tuoteryhm‰"),"</th></tr>";
	echo "<tr><td nowrap valign='top' class='back'><select name='mul_try[]' onchange='submit();' multiple='TRUE' size='7'>";
	$mul_check = '';
	if ($mul_try!="") {
		if (in_array("PUPEKAIKKIMUUT", $mul_try)) {
			$mul_check = 'SELECTED';
		}
	}
	echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("N‰yt‰ kaikki")."</option>";
	echo "<option value=''>".t("Ei valintaa")."</option>";

	while($srow = mysql_fetch_array ($sresult)){
		$sel = '';

		if (count($mul_try) > 0 and in_array(trim($srow['selite']), $mul_try)) {
			$sel = 'SELECTED';
		}

		echo "<option value='$srow[selite]' $sel>";
		if ($yhtiorow['naytetaan_kaunis_os_try'] == '') {
			echo $srow['selite']." ";
		}
		echo "$srow[selitetark]</option>";
	}
	echo "</select></td>";
	echo "</tr></table>";

	if (($lisa_haku_osasto != "" and $lisa_haku_try != "") or ($lisa_haku_osasto != "" and in_array("PUPEKAIKKIMUUT", $mul_try)) or ($lisa_haku_try != "" and in_array("PUPEKAIKKIMUUT", $mul_osasto))) {
		
		$query = "	SELECT distinct avainsana.selite, avainsana.selitetark
					FROM tuote
					JOIN avainsana ON (avainsana.yhtio = tuote.yhtio and tuote.tuotemerkki = avainsana.selite and avainsana.laji = 'TUOTEMERKKI' $avainlisa)
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					$lisa_haku_osasto
					$lisa_haku_try
					$kieltolisa
					$extra_poislisa
					$poislisa_mulsel
					ORDER BY avainsana.jarjestys, avainsana.selite";
	
		$sresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($sresult) > 0) {
			echo "<table style='display:inline;'><tr><th>",t("Tuotemerkki"),"</th></tr>";
			echo "<tr><td nowrap valign='top' class='back'>";
			echo "<select name='mul_tme[]' multiple='TRUE' size='7' onchange='submit();'>";
			$mul_check = '';
			if ($mul_tme!="") {
				if (in_array("PUPEKAIKKIMUUT", $mul_tme)) {
					$mul_check = 'SELECTED';
				}
			}
			echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("N‰yt‰ kaikki")."</option>";
			echo "<option value=''>",t("Ei valintaa"),"</option>";

			while($srow = mysql_fetch_array ($sresult)){
				$sel = '';

				if (count($mul_tme) > 0 and in_array(trim($srow['selite']), $mul_tme)) {
					$sel = 'SELECTED';
				}

				echo "<option value='$srow[selite]' $sel>$srow[selite]</option>";
			}

			echo "</select></td>";
			echo "</tr></table>";
		}

		// malli ja mallitarkenne dropdownit
		$query = "	SELECT DISTINCT tuote.malli
					FROM tuote
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					and tuote.malli != ''
					$lisa_haku_osasto
					$lisa_haku_try
					$lisa_haku_tme
					$kieltolisa
					$extra_poislisa
					$poislisa_mulsel
					ORDER BY malli";
		$sxresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($sxresult) > 0) {
			echo "<table style='display:inline;'><tr><th>",t("Malli"),"</th></tr>";
			echo "<tr><td nowrap valign='top' class='back'>";
			echo "<select name='mul_malli[]' multiple='TRUE' size='7' onchange='submit();'>";
			$mul_check = '';
			if ($mul_malli!="") {
				if (in_array("PUPEKAIKKIMUUT", $mul_malli)) {
					$mul_check = 'SELECTED';
				}
			}
			echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("N‰yt‰ kaikki")."</option>";
			echo "<option value=''>",t("Ei valintaa"),"</option>";

			while($mallirow = mysql_fetch_array ($sxresult)){
				$sel = '';

				if (count($mul_malli) > 0 and in_array(trim($mallirow['malli']), $mul_malli)) {
					$sel = 'SELECTED';
				}

				echo "<option value='$mallirow[malli]' $sel>$mallirow[malli]</option>";
			}

			echo "</select>";
			echo "</td>";
			echo "</tr></table>";
		}

		$query = "	SELECT DISTINCT tuote.mallitarkenne
					FROM tuote
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					and tuote.mallitarkenne != ''
					$lisa_haku_osasto
					$lisa_haku_try
					$lisa_haku_tme
					$lisa_haku_malli
					$kieltolisa
					$extra_poislisa
					$poislisa_mulsel
					ORDER BY mallitarkenne";
		$sxresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($sxresult) > 0) {
			echo "<table style='display:inline;'><tr><th>",t("Mallitarkenne"),"</th></tr>";
			echo "<tr><td nowrap valign='top' class='back'>";
			echo "<select name='mul_mallitarkenne[]' multiple='TRUE' size='7' onchange='submit();'>";
			$mul_check = '';
			if ($mul_mallitarkenne!="") {
				if (in_array("PUPEKAIKKIMUUT", $mul_mallitarkenne)) {
					$mul_check = 'SELECTED';
				}
			}
			echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("N‰yt‰ kaikki")."</option>";
			echo "<option value=''>",t("Ei valintaa"),"</option>";

			while($mallitarkennerow = mysql_fetch_array ($sxresult)){
				$sel = '';

				if (count($mul_mallitarkenne) > 0 and in_array(trim($mallitarkennerow['mallitarkenne']), $mul_mallitarkenne)) {
					$sel = 'SELECTED';
				}

				echo "<option value='$mallitarkennerow[mallitarkenne]' $sel>$mallitarkennerow[mallitarkenne]</option>";
			}

			echo "</select>";
			echo "</td>";
			echo "</tr></table>";
		}
	}

	echo "<input type='hidden' name='ostoskori' value='$ostoskori'>";

	if ($tultiin == "futur") {
		echo " <input type='hidden' name='tultiin' value='$tultiin'>";
	}
	
	echo "<br>";
	echo "<input type='Submit' name='submit_button' id='submit_button' value = '".t("Etsi")."'></form>";
	echo "<form><input type='submit' name='submit_button2' id='submit_button2' value = '".t("Tyhjenn‰")."'></form></td></tr></table><br>";

	// Halutaanko saldot koko konsernista?
	$query = "	SELECT *
				FROM yhtio
				WHERE konserni='$yhtiorow[konserni]' and konserni != ''";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0 and $yhtiorow["haejaselaa_konsernisaldot"] == "K") {
		$yhtiot = array();

		while ($row = mysql_fetch_array($result)) {
			$yhtiot[] = $row["yhtio"];
		}
	}
	else {
		$yhtiot = array();
		$yhtiot[] = $kukarow["yhtio"];
	}

	if (isset($sort) and $sort != '') {
		$sort = trim(mysql_real_escape_string($sort));
	}

	if (!isset($sort)) {
		$sort = '';
	}

	if ($sort == 'asc') {
		$sort = 'desc';
		$edsort = 'asc';
	}
	else {
		$sort = 'asc';
		$edsort = 'desc';
	}

	if (!isset($submit_button)) {
		$submit_button = '';
	}

	if ($submit_button != '' and ($lisa != '' or ($toim_kutsu != '' and $lisa != '' and $url == 'y'))) {
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
					tuote.status
					FROM tuote use index (tuoteno, nimitys)
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					$kieltolisa
					$lisa
					$extra_poislisa
					$poislisa
					ORDER BY $jarjestys $sort
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
								tuote.status
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

					if(!function_exists("tuoteselaushaku_tuoteperhe")) {
						function tuoteselaushaku_tuoteperhe($esiisatuoteno, $tuoteno, $isat_array, $kaikki_array, $rows) {
							global $kukarow, $kieltolisa, $poislisa;

							if (in_array($tuoteno, $isat_array)) {
								//echo "FUULI! TEET IKUISEN LUUPIN!!!!!!!!<br>";
							}
							else {
								$isat_array[] = $tuoteno;

								$query = "	SELECT
				 							'$esiisatuoteno' tuoteperhe,
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
											tuote.status
											FROM tuoteperhe
											JOIN tuote ON tuote.yhtio=tuoteperhe.yhtio and tuote.tuoteno=tuoteperhe.tuoteno
											WHERE tuoteperhe.yhtio 	  = '$kukarow[yhtio]'
											and tuoteperhe.isatuoteno = '$tuoteno'
											$kieltolisa
											$poislisa
											ORDER BY tuoteperhe.tuoteno";
								$kores = mysql_query($query) or pupe_error($query);

								while ($krow = mysql_fetch_array($kores)) {
									$rows[$krow["tuoteperhe"].$krow["tuoteno"]] = $krow;
									$kaikki_array[]	= $krow["tuoteno"];
								}
							}

							return array($isat_array, $kaikki_array, $rows);
						}
					}

					if ($mrow["tuoteperhe"] == $mrow["tuoteno"]) {
						$riikoko 		= 1;
						$isat_array 	= array();
						$kaikki_array 	= array($mrow["tuoteno"]);

						for($isa=0; $isa < $riikoko; $isa++) {
							list($isat_array, $kaikki_array, $rows) = tuoteselaushaku_tuoteperhe($mrow["tuoteno"], $kaikki_array[$isa], $isat_array, $kaikki_array, $rows);

							if ($yhtiorow["rekursiiviset_tuoteperheet"] == "Y") {
								$riikoko = count($kaikki_array);
							}
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
			echo "<br/>";
			echo "<table>";
			echo "<tr>";
			
			echo "<th><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&url=y&sort=$sort&ojarj=tuoteno$ulisa'>",t("Tuoteno"),"</a>";

			if ($lisatiedot != "") {
				echo "<br/><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&url=y&sort=$sort&ojarj=toim_tuoteno$ulisa'>",t("Toim Tuoteno");
			}

			echo "</th>";

			echo "<th><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&url=y&sort=$sort&ojarj=nimitys$ulisa'>",t("Nimitys")."</th>";
			echo "<th><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&url=y&sort=$sort&ojarj=osasto$ulisa'>",t("Osasto");
			echo "<br><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&url=y&sort=$sort&ojarj=try$ulisa'>",t("Try"),"</th>";
			echo "<th><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&url=y&sort=$sort&ojarj=hinta$ulisa'>",t("Hinta");

			if ($lisatiedot != "" and $kukarow["extranet"] == "") {
				echo "<br/><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&url=y&sort=$sort&ojarj=nettohinta$ulisa'>",t("Nettohinta");
			}

			echo "<th><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&url=y&sort=$sort&ojarj=aleryhma$ulisa'>",t("Aleryhm‰");

			if ($lisatiedot != "" and $kukarow["extranet"] == "") {
				echo "<br/><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&url=y&sort=$sort&ojarj=status$ulisa'>",t("Status");
			}

			echo "</th>";

			echo "<th>",t("Myyt‰viss‰"),"</th>";

			if ($lisatiedot != "" and $kukarow["extranet"] == "") {
				echo "<th>".t("Hyllyss‰")."</th>";
			}

			if ($oikeurow["paivitys"] == 1 and ($kukarow["kuka"] != "" or is_numeric($ostoskori))) {
				echo "<th>&nbsp;</th>";
			}

			echo "</tr>";

			$edtuoteno = "";

			$yht_i = 0; // t‰‰ on mei‰n indeksi

			echo "<form action='?submit_button=1&sort=$edsort&ojarj=$ojarj$ulisa' name='lisaa' method='post'>";
			echo "<input type='hidden' name='tee' value = 'TI'>";
			echo "<input type='hidden' name='toim_kutsu' value='$toim_kutsu'>";
			echo "<input type='hidden' name='ostoskori' value='$ostoskori'>";

			if ($tultiin == "futur") {
				echo " <input type='hidden' name='tultiin' value='$tultiin'>";
			}

			$alask = 0;

			foreach($rows as $ind => $row) {
				// Sarjanumerollisille tuotteille haetaan nimitys ostopuolen tilausrivilt‰
				if ($row["sarjanumeroseuranta"] == "S" and ($row["tuoteperhe"] == "" or $row["tuoteperhe"] == $row["tuoteno"])) {
					$query	= "	SELECT sarjanumeroseuranta.*, tilausrivi_osto.nimitys nimitys, tilausrivi_myynti.tyyppi, lasku_myynti.nimi myynimi, lasku_myynti.tunnus myytunnus
								FROM sarjanumeroseuranta
								LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
								LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
								LEFT JOIN lasku lasku_myynti use index (PRIMARY) ON lasku_myynti.yhtio=sarjanumeroseuranta.yhtio and lasku_myynti.tunnus=tilausrivi_myynti.otunnus
								WHERE sarjanumeroseuranta.yhtio in ('".implode("','", $yhtiot)."')
								and sarjanumeroseuranta.tuoteno = '$row[tuoteno]'
								and sarjanumeroseuranta.myyntirivitunnus != -1
								and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.tyyppi='T')
								and tilausrivi_osto.laskutettuaika != '0000-00-00'
								order by nimitys";
					$sarjares = mysql_query($query) or pupe_error($query);

					// Sarjanumerollisille tuotteille haetaan nimitys ostopuolen tilausrivilt‰
					$sarjalask = 0;

					if(mysql_num_rows($sarjares) > 0) {

						while ($sarjarow = mysql_fetch_array($sarjares)) {
							if($sarjarow["nimitys"] != "") {
								$row["nimitys"] = $sarjarow["nimitys"];
							}

							if ($sarjarow["yhtio"] != $kukarow["yhtio"]) {
								$row["sarjanumero"] = $sarjarow["sarjanumero"]." ($sarjarow[yhtio])";
							}
							else {
								$row["sarjanumero"] = $sarjarow["sarjanumero"];
							}

							$row["sarjatunnus"] = $sarjarow["tunnus"];
							$row["sarjayhtio"] = $sarjarow["yhtio"];

							if ($sarjalask > 0) {
								$row["korvaavat"] = $ind.$sarjalask;
								array_splice($rows, $alask, 0, array($ind.$sarjalask => $row));
					 		}
							else {
								$rows[$ind] = $row;
							}

							$sarjalask++;
						}
					}
				}

				$alask++;
			}

			foreach($rows as $row) {
				
				if ($kukarow['extranet'] != '') {
					$query    = "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]'";
					$tuotetempres = mysql_query($query);
					$temptrow = mysql_fetch_array($tuotetempres);

					$temp_laskurowwi = $laskurow;
					
					if (!is_array($temp_laskurowwi)) {
						$query = "SELECT * FROM asiakas where yhtio = '$kukarow[yhtio]' and tunnus = '$kukarow[oletus_asiakas]'";
						$asiakastempres = mysql_query($query);
						$asiakastemprow = mysql_fetch_array($asiakastempres);
												
						$temp_laskurowwi['liitostunnus']	= $asiakastemprow['tunnus'];
						$temp_laskurowwi['ytunnus']			= $asiakastemprow['ytunnus'];
						$temp_laskurowwi['valkoodi']		= $asiakastemprow['valkoodi'];
						$temp_laskurowwi['maa']				= $asiakastemprow['maa'];
					}
				
					$hinnat = alehinta($temp_laskurowwi, $temptrow, 1, '', '', '', "hintaperuste,aleperuste");
					
					if 	($temptrow["hinnastoon"] == "V" and ($hinnat["hintaperuste"] < 2 or $hinnat["hintaperuste"] > 12) and ($hinnat["aleperuste"] < 5 or $hinnat["aleperuste"] > 8)) {
						continue;
					}
				}
				
				if ($row["sarjatunnus"] > 0 and $kukarow["extranet"] == "" and function_exists("sarjanumeronlisatiedot_popup")) {
					if ($lisatiedot != "") {
						echo "<tr><td colspan='7' class='back'><br></td></tr>";
					}
					else {
						echo "<tr><td colspan='8' class='back'><br></td></tr>";
					}
				}

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

				$linkkilisa = "";

				//	Liitet‰‰n originaalitietoja
				if($orginaaalit === TRUE) {
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
					echo "<td valign='top' class='$vari' $classleft>$lisakala $row[tuoteno] $linkkilisa ";
				}
				else {
					echo "<td valign='top' class='$vari' $classleft><a href='../tuote.php?tuoteno=$row[tuoteno]&tee=Z'>$lisakala $row[tuoteno]</a>$linkkilisa ";
				}

				if ($lisatiedot != "") {
					echo "<br>$row[toim_tuoteno]";
				}

				echo "</td>";

				echo "<td valign='top' class='$vari' $classmidl>".asana('nimitys_',$row['tuoteno'],$row['nimitys'])."</td>";
				echo "<td valign='top' class='$vari' $classmidl>$row[osasto]<br>$row[try]</td>";

				$myyntihinta = sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["myyntihinta"]). " $yhtiorow[valkoodi]";

				// jos kyseess‰ on extranet asiakas yritet‰‰n n‰ytt‰‰ kaikki hinnat oikeassa valuutassa
				if ($kukarow["extranet"] != "") {
					if ($oleasrow["valkoodi"] != $yhtiorow["valkoodi"]) {

						$myyntihinta = sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["myyntihinta"])." $yhtiorow[valkoodi]";

						$query = "	SELECT *
									from hinnasto
									where yhtio = '$kukarow[yhtio]'
									and tuoteno = '$row[tuoteno]'
									and valkoodi = '$oleasrow[valkoodi]'
									and laji = ''
									and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
									order by ifnull(to_days(current_date)-to_days(alkupvm),9999999999999)
									limit 1";
						$olhires = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($olhires) == 1) {
							$olhirow = mysql_fetch_array($olhires);
							$myyntihinta = sprintf("%.".$yhtiorow['hintapyoristys']."f", $olhirow["hinta"])." $olhirow[valkoodi]";
						}
						elseif ($olhirow["kurssi"] != 0) {
							$myyntihinta = sprintf("%.".$yhtiorow['hintapyoristys']."f", yhtioval($row["myyntihinta"], $olhirow["kurssi"])). " $oleasrow[valkoodi]";					
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
									and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
									order by ifnull(to_days(current_date)-to_days(alkupvm),9999999999999)
									limit 1";
						$hintaresult = mysql_query($query) or pupe_error($query);

						while ($hintarow = mysql_fetch_array($hintaresult)) {
							$myyntihinta .= "<br>$hintarow[maa]: ".sprintf("%.".$yhtiorow['hintapyoristys']."f", $hintarow["hinta"])." $hintarow[valkoodi]";
						}
					}
				}

				echo "<td valign='top' class='$vari' align='right' $classmidl nowrap>$myyntihinta";

				if ($lisatiedot != "" and $kukarow["extranet"] == "") {
					echo "<br>".sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["nettohinta"])." $yhtiorow[valkoodi]";
				}

				echo "</td>";

				echo "<td valign='top' class='$vari' $classmidl>$row[aleryhma]";

				if ($lisatiedot != "" and $kukarow["extranet"] == "") {
					echo "<br>$row[status]";
				}

				echo "</td>";

				$edtuoteno = $row["korvaavat"];

				if ($row["tuoteperhe"] == $row["tuoteno"]) {
					// Tuoteperheen is‰
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

						$ei_tyhja = "";
						foreach ($saldot as $varaso => $saldo) {
							if ($saldo != 0) {
								$ei_tyhja = 'yes';
								echo "<tr><td class='$vari' nowrap>$varaso</td><td class='$vari' align='right' nowrap>".sprintf("%.2f", $saldo)." ".ta($kieli, "Y", $row["yksikko"])."</td></tr>";
							}
						}

						if ($ei_tyhja == '') {
							echo "<tr><td class='$vari' nowrap colspan='2'>",t("Tuotteella ei ole saldoa"),"</td></tr>";
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
				elseif ($row["sarjanumeroseuranta"] == "S" and ($row["tuoteperhe"] == "" or $row["tuoteperhe"] == $row["tuoteno"])) {
					if ($kukarow["extranet"] != "") {
						echo "<td valign='top' class='$vari' $classrigh>$row[sarjanumero] ";
					}
					else {
						echo "<td valign='top' class='$vari' $classrigh><a onClick=\"javascript:sarjanumeronlisatiedot_popup('$row[sarjatunnus]')\">$row[sarjanumero]</a> ";
					}

					if ($row["sarjayhtio"] == $kukarow["yhtio"] and ($kukarow["kuka"] != "" or is_numeric($ostoskori))) {
						echo "<input type='hidden' name='tiltuoteno[$yht_i]' value = '$row[tuoteno]'>";
						echo "<input type='hidden' name='tilsarjatunnus[$yht_i]' value = '$row[sarjatunnus]'>";
						echo "<input type='checkbox' name='tilkpl[$yht_i]' value='1'> ";
						$yht_i++;
					}

					echo "</td>";

					if ($lisatiedot != "" and $kukarow["extranet"] == "") {
						echo "<td class='$vari' $classrigh></td>";
					}
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

					// K‰yd‰‰n l‰pi tuotepaikat
					if ($row["sarjanumeroseuranta"] == "E" or $row["sarjanumeroseuranta"] == "F" or $row["sarjanumeroseuranta"] == "G") {
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
									WHERE tuote.yhtio in ('".implode("','", $yhtiot)."')
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
									WHERE tuote.yhtio in ('".implode("','", $yhtiot)."')
									and tuote.tuoteno = '$row[tuoteno]'
									ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
					}
					$varresult = mysql_query($query) or pupe_error($query);

					echo "<td valign='top' $classrigh>";

					if (mysql_num_rows($varresult) > 0) {
						$hyllylisa = "";
						echo "<table width='100%'>";

						// katotaan jos meill‰ on tuotteita varaamassa saldoa joiden varastopaikkaa ei en‰‰ ole olemassa...
						list($saldo, $hyllyssa, $orvot) = saldo_myytavissa($row["tuoteno"], 'ORVOT', '', '', '', '', '', '', '', $saldoaikalisa);
						$orvot *= -1;

						while ($saldorow = mysql_fetch_array ($varresult)) {

							list($saldo, $hyllyssa, $myytavissa, $sallittu) = saldo_myytavissa($saldorow["tuoteno"], '', '', $saldorow["yhtio"], $saldorow["hyllyalue"], $saldorow["hyllynro"], $saldorow["hyllyvali"], $saldorow["hyllytaso"], $laskurow["toim_maa"], $saldoaikalisa, $saldorow["era"]);

							//	Listataan vain varasto jo se ei ole kielletty
							if($sallittu === TRUE) {
								// hoidetaan pois problematiikka jos meill‰ on orpoja (tuotepaikattomia) tuotteita varaamassa saldoa
								if ($orvot > 0) {
									if ($myytavissa >= $orvot and $saldorow["yhtio"] == $kukarow["yhtio"]) {
								    	// poistaan orpojen varaamat tuotteet t‰lt‰ paikalta
								    	$myytavissa = $myytavissa - $orvot;
								    	$orvot = 0;
									}
									elseif ($orvot > $myytavissa and $saldorow["yhtio"] == $kukarow["yhtio"]) {
								    	// poistetaan niin paljon orpojen saldoa ku voidaan
								    	$orvot = $orvot - $myytavissa;
								    	$myytavissa = 0;
									}
								}

								if ($myytavissa != 0 or ($lisatiedot != "" and $kukarow["extranet"] == "" and $hyllyssa != 0)) {
									echo "	<tr>
											<td class='$vari' nowrap>$saldorow[nimitys] $saldorow[tyyppi]</td>
											<td class='$vari' align='right' nowrap>".sprintf("%.2f", $myytavissa)." ".ta($kieli, "Y", $row["yksikko"])."</td>
											</tr>";
								}

								if ($lisatiedot != "" and $kukarow["extranet"] == "" and $hyllyssa != 0) {
									$hyllylisa .= "	<tr>
													<td class='$vari' align='right' nowrap>".sprintf("%.2f", $hyllyssa)."</td>
													</tr>";
								}
							}
						}
						echo "</table></td>";
					}
					echo "</td>";


					if ($lisatiedot != "" and $kukarow["extranet"] == "") {
						echo "<td valign='top' $classrigh>";

						if (mysql_num_rows($varresult) > 0 and $hyllylisa != "") {

							echo "<table width='100%'>";
							echo "$hyllylisa";
							echo "</table></td>";
						}
						echo "</td>";
					}
				}

				if ($oikeurow["paivitys"] == 1 and ($kukarow["kuka"] != "" or is_numeric($ostoskori))) {
					echo "<td valign='top' align='right' class='$vari' nowrap>";

					if ($tultiin == "futur") {
						echo " <input type='hidden' name='tultiin' value='$tultiin'>";
					}

					echo "<input type='hidden' name='tiltuoteno[$yht_i]' value = '$row[tuoteno]'>";
					echo "<input type='text' size='3' name='tilkpl[$yht_i]'> ";
					echo "<input type='submit' value = '".t("Lis‰‰")."'>";
					echo "</td>";
					$yht_i++;
				}

				unset($images_exist);
				unset($pdf_exist);
				unset($filetype);

				$filetype_query = "	SELECT * 
									FROM liitetiedostot 
									WHERE yhtio		 = '{$kukarow['yhtio']}' 
									and liitos		 = 'tuote' 
									and liitostunnus = '{$row['tunnus']}' 
									ORDER BY kayttotarkoitus, jarjestys, filename
									LIMIT 1";
				$filetype_result = mysql_query($filetype_query) or pupe_error($filetype_query);
				$filetype_row = mysql_fetch_assoc($filetype_result);

				if (mysql_num_rows($filetype_result) > 0) {
					if (in_array("image/jpeg", $filetype_row) or in_array("image/jpg", $filetype_row) or in_array("image/gif", $filetype_row) or in_array("image/png", $filetype_row) or in_array("image/bmp", $filetype_row)) {
						list ($prefix, $filetype) = explode("/", $filetype_row["filetype"]);
						$filetype = strtolower($filetype);

						if ($filetype == "jpeg" or $filetype == "jpg" or $filetype == "gif" or $filetype == "png" or $filetype == "bmp") {
							$query = "	SELECT MAX(image_width) AS max_width, SUM(image_height) AS total_height, count(liitostunnus) AS kpl
							 			FROM liitetiedostot
										WHERE yhtio='$kukarow[yhtio]'
										AND liitos='tuote'
										AND liitostunnus='$row[tunnus]'";
							$kuvares = mysql_query($query) or pupe_error($query);

							$apurow = mysql_fetch_array($kuvares);
							$maxwidth = $apurow["max_width"] + 30;
							if ($maxwidth > 640) {
								$maxwidth = 640;
							}
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

				if (isset($images_exist) or isset($pdf_exist)) {
					echo "<td class='back' valign='top'><input type='button' value='";

					if ($pdf_exist) {
						echo t("Pdf");
					}
					else {
						echo t("Kuva");
					}
					echo "' onClick=\"javascript:picture_popup('$row[tunnus]', '$maxwidth', '$totalheight', '$row[tuoteno]')\"></td>";
				}

				echo "</tr>";

				if ($row["sarjatunnus"] > 0 and $kukarow["extranet"] == "" and function_exists("sarjanumeronlisatiedot_popup")) {
					list($kommentit, $text_output, $kuvalisa_bin, $ostohinta, $tuotemyyntihinta) = sarjanumeronlisatiedot_popup($row["sarjatunnus"], $row["sarjayhtio"], '', '', '100%', '');

					if ($lisatiedot != "") {
						echo "<tr><td colspan='7'>$kommentit</td></tr>";
					}
					else {
						echo "<tr><td colspan='6'>$kommentit</td></tr>";
					}
				}
			}

			echo "</form>";
			echo "</table>";

		}
		else {
			echo t("Yht‰‰n tuotetta ei lˆytynyt")."!";
		}

		if(mysql_num_rows($result) == 500) {
			echo "<br><br><font class='message'>".t("Lˆytyi yli 500 tuotetta, tarkenna hakuasi")."!</font>";
		}
	}

	if (file_exists("../inc/footer.inc")) {
		require ("../inc/footer.inc");
	}
	else {
		require ("footer.inc");
	}
?>
