<?php	

if($_REQUEST["tee"] == "logout") {
	
	
	ob_start();
	require ("parametrit.inc");
	
	
	$query = "UPDATE kuka set session='', kesken='' where session='$session'";
	$result = mysql_query($query) or pupe_error($query);
	$bool = setcookie("pupesoft_session", "", time()-43200);
	ob_end_flush();
		
	setcookie("pupesoft_session", "", time()-432000);
	echo "<META HTTP-EQUIV='Refresh' CONTENT='2;URL=".$palvelin2."verkkokauppa.php'>";
	exit;
}

$_GET["ohje"] = "off";

require ("parametrit.inc");

if(!function_exists("menu")) {
	function menu($osasto="", $merkki="") {
		global $yhtiorow, $kukarow;
		
		$val = "<a href=\"javascript:sndReq('menu', 'verkkokauppa.php?tee=menu&osasto=&try=&merkki=')\">nollaa meny</a><br>";
		
		$query = "	SELECT selite osasto, selitetark nimi
		 			FROM avainsana
					WHERE yhtio='{$kukarow["yhtio"]}' and laji = 'OSASTO'
					ORDER BY selite+0";
		$ores = mysql_query($query) or pupe_error($query);
		while($orow = mysql_fetch_array($ores)) {			
				
			$val .=  "<a href='javascript:sndReq(\"menu\", \"verkkokauppa.php?tee=menu&osasto={$orow["osasto"]}\")'>{$orow["nimi"]}</a><br>";		
			//	Piilotetaan ne joilla EI OLE siisitä nimeä
			//	Laajennetaan merkki
			if($orow["osasto"] == $osasto) {
				$query = "	SELECT distinct(tuotemerkki) merkki
				 			FROM tuote
							WHERE tuote.yhtio='{$kukarow["yhtio"]}' and osasto = '$osasto' and tuotemerkki != '' and status != 'P'";
				$merkkires = mysql_query($query) or pupe_error($query);
				while($merkkirow = mysql_fetch_array($merkkires)) {
					$val .=  "&nbsp;&nbsp;&nbsp;&nbsp;<a href='javascript:sndReq(\"selain\", \"verkkokauppa.php?tee=selaa&osasto={$orow["osasto"]}&merkki={$merkkirow["merkki"]}\");'>{$merkkirow["merkki"]}</a><br>";
				}
			}					
		}
		
		return $val;
	}
}

if($tee == "menu") {
	die(menu($osasto, $merkki));
}

if($tee == "poistakori") {
	$query = "	SELECT tunnus
				FROM lasku
				WHERE yhtio = '{$kukarow["yhtio"]}' and
				tila = 'N' and
				tunnus = '{$kukarow["kesken"]}' and
				alatila=''";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 1) {
		// löyty vaan yks dellataan se
		//$ostoskori = mysql_fetch_array($result);
		$kalakori = mysql_fetch_array($result);

		$query = "	delete from tilausrivi
					where yhtio = '$kukarow[yhtio]' and
					tyyppi = 'L' and
					otunnus = '$kalakori[tunnus]'";
		$result = mysql_query($query) or pupe_error($query);

		$query = "	DELETE FROM lasku
					WHERE yhtio = '{$kukarow["yhtio"]}' and
					tila = 'N' and
					tunnus = '{$kukarow["kesken"]}' and
					alatila=''";
		$result = mysql_query($query) or pupe_error($query);

		$query = "	delete from lasku
					where yhtio = '$kukarow[yhtio]' and
					tila = 'N' and
					tunnus = '$kalakori[tunnus]'";
		$result = mysql_query($query) or pupe_error($query);
		
		$query = "	UPDATE kuka SET kesken = '' WHERE yhtio ='{$kukarow["yhtio"]}' and kuka = '{$kukarow["kuka"]}'";
		$result = mysql_query($query) or pupe_error($query);
	}

	$tee = "tilatut";	
}

if($tee == "poistarivi") {
	$query = "	SELECT tunnus
				FROM lasku
				WHERE yhtio = '{$kukarow["yhtio"]}' and
				tila = 'N' and
				tunnus = '{$kukarow["kesken"]}' and
				alatila=''";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 1) {
		// löyty vaan yks dellataan siitä rivi
		//$ostoskori = mysql_fetch_array($result);
		$kalakori = mysql_fetch_array($result);

		$query = "	DELETE FROM tilausrivi
					WHERE yhtio = '{$kukarow["yhtio"]}' and tyyppi = 'L' and tunnus = '$rivitunnus' LIMIT 1";
		$result = mysql_query($query) or pupe_error($query);
	}

	$tee = "tilatut";	
}

if($tee == "tilaa") {
	$query = "	SELECT tunnus
				FROM lasku
				WHERE yhtio = '{$kukarow["yhtio"]}' and
				tila = 'N' and
				tunnus = '{$kukarow["kesken"]}' and
				alatila=''";
	$result = mysql_query($query) or pupe_error($query);
	if(mysql_num_rows($result) == 1) {
		
		$laskurow = mysql_fetch_array($result);
		
		//	Hyväksynnän kautta
		if ($kukarow["taso"] == 2) {
			$query  = "	UPDATE lasku set
						tila = 'N',
						alatila='F'
						WHERE yhtio='$kukarow[yhtio]'
						and tunnus='$kukarow[kesken]'
						and tila = 'N'
						and alatila = ''";
			$result = mysql_query($query) or pupe_error($query);
		}
		else {
			// tulostetaan lähetteet ja tilausvahvistukset tai sisäinen lasku..
			$silent = "JOO";
			require("tilaus-valmis.inc");
		}
		
		// tilaus ei enää kesken...
		$query	= "update kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
		$result = mysql_query($query) or pupe_error($query);
	}
	
	$ulos = "<font class='head'>Kiitos tilauksesta</font><br>";
	die($ulos);
}


if($tee == "tilatut") {
	
	$ulos = "<font class='head'>".t("Tilauksen tuotteet")." {$kukarow["kesken"]}</font><br>
			<a href=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=selaa&osasto=$osasto&merkki=$merkki')\">Takaisin selaimelle</a>&nbsp;&nbsp;";
			
	$query = "	SELECT count(*) rivei
				FROM tilausrivi 
				WHERE yhtio = '{$kukarow["yhtio"]}' and otunnus = '{$kukarow["kesken"]}' and tyyppi = 'L'";
	$result = mysql_query($query) or pupe_error($query);
	$row = mysql_fetch_array($result);
	
	if($row["rivei"] > 0) {
		$ulos .= "<a href=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=tilaa')\";>Tilaa tuotteet</a>&nbsp;&nbsp;";
		$ulos .= "<a href=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=poistakori&osasto=$osasto&merkki=$merkki')\" disabled>Mitätöi tilaus</a>";
	}		
	
	$ulos .= "<br><br>";


	$query = "	SELECT tuoteno, nimitys, tilausrivi.hinta, round(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)),2) rivihinta, (varattu+jt) varattu, tilausrivi.tunnus
				FROM tilausrivi
				JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]' and
				otunnus = '$kukarow[kesken]' and
				tyyppi = 'L'
				and var != 'P'
				GROUP BY tilausrivi.tunnus";
	$riviresult = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($riviresult) > 0) {
		$ulos .=  "<table style = 'width: 600px;'>
					<tr>
						<th>Tuoteno</th>
						<th>Nimitys</th>
						<th>Määrä</th>
						<th>Yksikköhinta</th>
						<th>Rivihinta</th>
						<td class='back'></td>
					</tr>";

		

		while ($koririvi = mysql_fetch_array($riviresult)) {

			$ulos .= "<tr>
						<td>$koririvi[tuoteno]</td>
						<td>$koririvi[nimitys]</td>
						<td>$koririvi[varattu]</td>
						<td>$koririvi[hinta]</td>
						<td>$koririvi[rivihinta]</td>
						<td class='back'><a href='#' onclick = \"javascript:sndReq('selain', 'verkkokauppa.php?tee=poistarivi&rivitunnus={$koririvi["tunnus"]}&osasto=$osasto&merkki=$merkki')\">Poista</a></td>
					</tr>";

		}
	}
	else {
		$ulos .= "<font class='message'>Tilauksessa ei ole tavaraa.</font><br>";
	}
	
	die($ulos);
}

if($tee == "selaa") {
	
		
	ob_start();
	$poistetut 	= "";
	$poistuvat 	= "";
	$lisatiedot	= "";
	
	$haku[0]	= "";	
	$haku[1]	= "";	
	$haku[2]	= "";
	$haku[3]	= $osasto;	
	$haku[4]	= "";
	$haku[5]	= $merkki;	
	
	if($kukarow["kuka"] != "www" and $kukarow["kesken"] == 0) {
		require_once("luo_myyntitilausotsikko.inc");
		$tilausnumero = luo_myyntitilausotsikko($asiakasid, $tilausnumero, $myyjanro);
		$kukarow["kesken"] = $tilausnumero;
	}
	
	$kukarow["extranet"] 	= "X";
	$rajattunakyma 			= "On";
	$kori_polku				= "ostoskori.php";
	$PHP_SELF				= "tuote_selaus_haku.php";
	$toim_kutsu 			= "RIVISYOTTO";
	$target 				= "input";
	$request 				= "true"; 

	require("tuote_selaus_haku.php");

	$dada = "<a href='#' onclick=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=tilatut&osasto=$osasto&merkki=$merkki')\">Tilauksen tuotteet</a><br><br>".ob_get_contents();
	ob_end_clean();
	
	die($dada);
}

//	Tehdään vain pohja..
if($tee == "") {
	
	enable_ajax();
	
	if($kukarow["kuka"] == "www") {
		$login_screen = "
			<form name='login' id= 'loginform' action='login_extranet.php' method='post'>
				<input type='hidden' id = 'location' name='location' value='".$palvelin."verkkokauppa.php'>
				<font class='info'>".t("Käyttäjätunnus",$browkieli).":</font>
				<input type='text' value='' name='user' size='15' maxlength='30'>
				<font class='info'>".t("Salasana",$browkieli).":</font>
				<input type='password' name='salasana' size='15' maxlength='30'>
				<input type='submit' value='".t("Kirjaudu sisään",$browkieli)."'>
				<br>
				$errormsg
			</form>";
			
	}
	else {
		$login_screen = "
			<form action = '".$palvelin2."logout.php' method='post'>Tervetuloa {$kukarow["nimi"]}, <input type = 'hidden' name='location' value='".$palvelin."verkkokauppa.php'><input type='submit' value='".t("Kirjaudu ulos")."'></form><br>";
	}
	
	echo "	<div id='menu' style='position:absolute; top: 50px; left: 0px; width: 250px'>".menu()."</div>
			<div id='login' style='position:absolute; top: 0px; left: 200px; height: 20px;'>$login_screen</div>
			<div id='selain' style='position:absolute; top: 50px; left: 200px; width: 650px'></div>
			</body></html>";
}


?>