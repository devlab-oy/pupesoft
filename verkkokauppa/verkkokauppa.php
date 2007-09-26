<?php	

$_GET["ohje"] = "off";

require ("parametrit.inc");

if(!function_exists("menu")) {
	function menu($osasto="") {
		global $yhtiorow, $kukarow;
		
		$val = "";
		

		if($osasto == "") {
			$query = "	SELECT selite osasto, selitetark nimi
			 			FROM avainsana
						WHERE yhtio='{$kukarow["yhtio"]}' and laji = 'OSASTO'
						ORDER BY selite+0";
			$ores = mysql_query($query) or pupe_error($query);
			while($orow = mysql_fetch_array($ores)) {
				$val .=  "<a id='{$orow["osasto"]}_P' href='javascript:sndReq(\"{$orow["osasto"]}_T\", \"verkkokauppa.php?tee=menu&osasto={$orow["osasto"]}\", \"{$orow["osasto"]}_P\", true)'>{$orow["nimi"]}</a><br><div id='{$orow["osasto"]}_T'></div>";
			}
		}
		else {
			$query = "	SELECT distinct try, selitetark trynimi
			 			FROM tuote
						JOIN avainsana ON tuote.yhtio = avainsana.yhtio and tuote.try = avainsana.selite and avainsana.laji = 'TRY'
						WHERE tuote.yhtio='{$kukarow["yhtio"]}' and osasto = '$osasto' and try != '' and status != 'P'";
			$tryres = mysql_query($query) or pupe_error($query);
			while($tryrow = mysql_fetch_array($tryres)) {
				$val .=  "&nbsp;&nbsp;&nbsp;&nbsp;<a id='{$tryrow["try"]}_T' href='javascript:sndReq(\"selain\", \"verkkokauppa.php?tee=selaa&osasto={$osasto}&try={$tryrow["try"]}\", \"\", true);'>{$tryrow["trynimi"]}</a><br>";
			}
		}
		
		return $val;
	}
}

if($tee == "menu") {
	die(menu($osasto, $try));
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
		
		$ulos = "<font class='head'>Kiitos tilauksesta</font><br><br><font class='message'>Tilauksesi numero on {$kukarow["kesken"]}</font><br>";
		
		
		// tilaus ei enää kesken...
		$query	= "update kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
		$result = mysql_query($query) or pupe_error($query);
	}
	

	die($ulos);
}


if($tee == "tilatut") {
	
	if($kukarow["kesken"] == 0) {
		$tee = "selaa";
	}
	else {
		$ulos = "<font class='head'>".t("Tilauksen tuotteet")." {$kukarow["kesken"]}</font><br>
				<a href=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=selaa&osasto=$osasto&try=$try')\">Takaisin selaimelle</a>&nbsp;&nbsp;";

		$query = "	SELECT count(*) rivei
					FROM tilausrivi 
					WHERE yhtio = '{$kukarow["yhtio"]}' and otunnus = '{$kukarow["kesken"]}' and tyyppi = 'L'";
		$result = mysql_query($query) or pupe_error($query);
		$row = mysql_fetch_array($result);

		if($row["rivei"] > 0) {
			$ulos .= "<a href=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=tilaa')\";>Tilaa tuotteet</a>&nbsp;&nbsp;";
			$ulos .= "<a href=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=poistakori&osasto=$osasto&try=$try')\" disabled>Mitätöi tilaus</a>";
		}		

		$ulos .= "<br><br>";


		$query = "	SELECT tuoteno, nimitys, tilausrivi.hinta, round(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)),2) rivihinta, (varattu+jt) varattu, tilausrivi.tunnus
					FROM tilausrivi
					JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]' and
					otunnus = '$kukarow[kesken]' and
					tyyppi = 'L'
					and var != 'D'
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
							<td class='back'><a href='#' onclick = \"javascript:sndReq('selain', 'verkkokauppa.php?tee=poistarivi&rivitunnus={$koririvi["tunnus"]}&osasto=$osasto&try=$try')\">Poista</a></td>
						</tr>";

			}
		}
		else {
			$ulos .= "<font class='message'>Tilauksessa ei ole tavaraa.</font><br>";
		}

		die($ulos);		
	}
}

if($tee == "selaa") {
	
		
	ob_start();
	$poistetut 	= "";
	$poistuvat 	= "";
	$lisatiedot	= "";
	$ojarj		= "tuote_wrapper.tuotemerkki";
	
	$haku[0]	= "";	
	$haku[1]	= "";	
	$haku[2]	= "";
	$haku[3]	= $osasto;	
	$haku[4]	= $try;
	$haku[5]	= "";	
	
	if($kukarow["kuka"] != "www" and $kukarow["kesken"] == 0) {
		require_once("luo_myyntitilausotsikko.inc");
		$tilausnumero = luo_myyntitilausotsikko($asiakasid, $tilausnumero, $myyjanro);
		$kukarow["kesken"] = $tilausnumero;
	}
	
	require("tuote_selaus_haku.php");
	
	$dada = ob_get_contents();	
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
	
	//$border = "border: 1px #88adeb solid;'";
	
	echo "	<div id='login' style='text-align: center; z-index: 0; height: 25px; $border'>$login_screen</div>
			<div id='menu' style='float:left; width:  250px; z-index: 0; $border'>".menu()."</div>
			<div id='selain' style='float:left; z-index: 0; $border'></div>
			</body></html>";
}


?>
