<?php	

$_GET["ohje"] = "off";

require ("parametrit.inc");

if(!function_exists("menu")) {
	function menu($osasto="", $try="") {
		global $yhtiorow, $kukarow;
		
		$val = "";
		
		if($osasto == "") {
			$val 		=  "<table class='menu'><tr class='aktiivi'><td><a href='verkkokauppa.php'>".t("Alkuun")."</a></td></tr>";
			
			$query = "	SELECT selite osasto, selitetark nimi
			 			FROM avainsana
						WHERE yhtio='{$kukarow["yhtio"]}' and laji = 'OSASTO' and selitetark_2 = 'verkkokauppa'
						ORDER BY selitetark";
			$ores = mysql_query($query) or pupe_error($query);
			while($orow = mysql_fetch_array($ores)) {
				$target		= "{$orow["osasto"]}_T";
				$parent		= "{$orow["osasto"]}_P";
				$onclick	= "document.getElementById(\"$target\").style.display==\"none\"? sndReq(\"selain\", \"verkkokauppa.php?tee=kuvaus&osasto={$orow["osasto"]}\", \"\", false) : \"\";";
				$href 		= "javascript:sndReq(\"$target\", \"verkkokauppa.php?tee=menu&osasto={$orow["osasto"]}\", \"$parent\", false, true);";
				$val .=  "<tr class='aktiivi'><td><a class = 'menu' id='$parent' onclick='$onclick' href='$href'>{$orow["nimi"]}</a><div id='$target' style='display: none'></div></td></tr>";
			}
			$val .= "</table>";
			
			$val .= "<br><table>
						<tr><td class='back'><font class='info'>Hae:</font></td></tr>
						<tr>
							<td><form id = 'tuotehaku' name='tuotehaku'  action = \"javascript:ajaxPost('tuotehaku', 'verkkokauppa.php?tee=selaa&hakutapa=nimi', 'selain', false, true);\" method = 'post'>
							<input type = 'text' size='15' name = 'tuotehaku'></form><br>
							<a href=\"javascript:ajaxPost('tuotehaku', 'verkkokauppa.php?tee=selaa&hakutapa=nimi', 'selain', false, true);\">".t("Nimityksellä")."</a><br>
							<a href=\"javascript:ajaxPost('tuotehaku', 'verkkokauppa.php?tee=selaa&hakutapa=koodilla', 'selain', false, true);\">".t("Tuotekoodilla")."</a>
							</td>
						</tr>
					</table>";
		}
		elseif($try == "") {
			$val = "<table class='menu'>";
			$query = "	SELECT distinct try, selitetark trynimi
			 			FROM tuote
						JOIN avainsana ON tuote.yhtio = avainsana.yhtio and tuote.try = avainsana.selite and avainsana.laji = 'TRY'
						WHERE tuote.yhtio='{$kukarow["yhtio"]}' and osasto = '$osasto' and try != '' and status != 'P' and hinnastoon = 'W' and tuotemerkki != ''
						ORDER BY selitetark";
			$tryres = mysql_query($query) or pupe_error($query);
			while($tryrow = mysql_fetch_array($tryres)) {

				$target		= "{$osasto}_{$tryrow["try"]}_P";
				$parent		= "{$osasto}_{$tryrow["try"]}_T";
				$href 		= "javascript:sndReq(\"$target\", \"verkkokauppa.php?tee=menu&osasto=$osasto&try={$tryrow["try"]}\", \"\", true); sndReq(\"selain\", \"verkkokauppa.php?tee=selaa&osasto=$osasto&try={$tryrow["try"]}&tuotemerkki=\", \"\", true);";
				
				$val .=  "<tr class='aktiivi'><td class='sisennys1'></td><td><a class = 'menu' id='$parent' href='$href'>{$tryrow["trynimi"]}</a><div id=\"$target\" style='display: none'></div></td></tr>";
			}
			$val .= "</table>";
		}
		else {
			$val = "<table class='menu'>";
			$query = "	SELECT distinct tuotemerkki
			 			FROM tuote
						WHERE tuote.yhtio='{$kukarow["yhtio"]}' and osasto = '$osasto' and try = '$try' and status != 'P' and hinnastoon = 'W' and tuotemerkki != ''
						ORDER BY tuotemerkki";
			$meres = mysql_query($query) or pupe_error($query);
			while($merow = mysql_fetch_array($meres)) {
				$val .=  "<tr class='aktiivi'><td class='sisennys1'></td><td class='sisennys2'></td><td><a class = 'menu' id='{$osasto}_{$try}_{$merow["tuotemerkki"]}_P' href='javascript:sndReq(\"selain\", \"verkkokauppa.php?tee=selaa&osasto=$osasto&try=$try&tuotemerkki=$tuotemerkki&tuotemerkki={$merow["tuotemerkki"]}\", \"\", true);'>{$merow["tuotemerkki"]}</a></td></tr>";
			}
			$val .= "</table>";
		}
		
		return $val;
	}
}

if(!function_exists("kuvaus")) {
	function kuvaus($osasto="", $try="") {
		global $yhtiorow, $kukarow;

		$val = "<table class='kuvaus'>";
		
		if($osasto == "") {
			$query = "	SELECT *
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]' and laji = 'OSASTO' and selitetark_2 = 'verkkokauppa'";
			$result = mysql_query($query) or pupe_error($query);
			if(mysql_num_rows($result) > 0) {
				while($row = mysql_fetch_array($result)) {
					$val .= "<tr><td class = 'back'><font class='head'>{$row["selitetark"]}</font><br>{$row["selitetark_3"]}<br><br></td></tr>";
				}
			}
			else {
				
			}
		}
		elseif($try == "") {
			$query = "	SELECT distinct selitetark, selitetark_3
			 			FROM tuote
						JOIN avainsana ON tuote.yhtio = avainsana.yhtio and tuote.try = avainsana.selite and avainsana.laji = 'TRY'
						WHERE tuote.yhtio='{$kukarow["yhtio"]}' and osasto = '$osasto' and try != '' and status != 'P' and hinnastoon = 'W' and tuotemerkki != ''
						ORDER BY selitetark";
			$result = mysql_query($query) or pupe_error($query);
			if(mysql_num_rows($result) > 0) {
				while($row = mysql_fetch_array($result)) {
					$val .= "<tr><td class = 'back'><font class='head'>{$row["selitetark"]}</font><br>{$row["selitetark_3"]}<br><br></td></tr>";
				}
			}
			else {

			}	
		}
		
		$val .= "</table>";
		
		return $val;
	}
}

if(!function_exists("uutiset")) {
	function uutiset() {
		global $yhtiorow, $kukarow;

		$val = "<table class='uutinen'>";
		
		$query = "	SELECT *
					FROM kalenteri
					WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'VERKKOKAUPPAUUTINEN'
					ORDER BY luontiaika DESC
					LIMIT 8";
		$result = mysql_query($query) or pupe_error($query);
		while($row = mysql_fetch_array($result)) {
			$val .= "<tr><td class='back'>";
			if($row["kentta03"] > 0) {
				$val .= "<img class='uutinen' src='view.php?id=$row[kentta03]'><br>";
			}
			
			$val .= "<font class='head'>{$row["kentta01"]}</font><br>";
			$val .= "<font class='message'>{$row["kentta02"]}</font><hr>";
			$val .= "</td></tr>";
		}
		
		$val .= "</table>";
		
		return $val;
	}
}

if($tee == "menu") {
	die(menu($osasto, $try));
}

if($tee == "kuvaus") {
	die(kuvaus($osasto, $try));	
}

if($tee == "uutiset") {
	die(uutiset($osasto, $try));	
}

if($tee == "tuotteen_lisatiedot") {
	
	$query = "	SELECT kuvaus, lyhytkuvaus
				FROM tuote
				WHERE yhtio = '{$kukarow["yhtio"]}' and tuoteno = '$tuoteno' and hinnastoon = 'W'";
	$result = mysql_query($query) or pupe_error($query);
	$row = mysql_fetch_array($result);

	//	Eka rivi aina head
	if(trim($row["kuvaus"]) == "" and trim($row["lyhytkuvaus"]) == "") {
		$row["tekstit"] = t("Tuotteestamme ei ole tällähetkellä lisätietoja..");
	}
	else {
		$row["tekstit"] = $row["lyhytkuvaus"]."<br><br>".$row["kuvaus"];
		$row["tekstit"] = preg_replace("/(.*)/", "<font class='head'>\$1</font>", $row["tekstit"], 1);
	}
	
	// Shit! joudutaan tutkimaan mille välille tehdään li
	$txt = explode("\n", $row["tekstit"]);
	$onli = "";
	$tekstit = "";
	foreach($txt as $line) {

		unset($matches);
		unset($avaus);		
		preg_match("/(\#{2}|\*{2})/", $line, $matches, PREG_OFFSET_CAPTURE);

		if(count($matches)>0) {			
			$line = substr($line, 2);

			//	Avataan lista
			if($matches[0][1] == 0 and $onli == "") {
				$lista = $matches[0][0];
				if($lista == "**") $onli = "ul";
				if($lista == "##") $onli = "dl";

				$avaus = "<$onli>";
			}
		}
		elseif($onli != "") {
			$line .= "</$onli>";
			$onli = "";
		}

		//	Meillä on lista!
		if($onli != "") {
			$line = "$avaus<li>$line</li>";		
		}
		$tekstit .= $line."<br>";	
	}
	
	
	$from[]		= "/\?\?(.*)\?\?/m";
	$to[]		= "<font class='italic'>\$1</font>";
	$from[]		= "/\!\!(.*)\!\!/m";
	$to[]		= "<font class='bold'>\$1</font>";
	
	$tekstit = preg_replace($from, $to, $tekstit);
	
	if($kukarow["kuka"] == "www") {
		$liitetyypit = array("public");
	}
	else {
		$liitetyypit = array("extranet","public");
	}

	//	Haetaan kaikki liitetiedostot
	$query = "	SELECT liitetiedostot.tunnus, liitetiedostot.selite, liitetiedostot.kayttotarkoitus, avainsana.selitetark, avainsana.selitetark_2, (select tunnus from liitetiedostot l where l.yhtio=liitetiedostot.yhtio and l.liitos=liitetiedostot.liitos and l.selite=liitetiedostot.selite and l.kayttotarkoitus='TH') peukalokuva
				FROM tuote
				JOIN liitetiedostot ON liitetiedostot.yhtio = tuote.yhtio and liitos = 'TUOTE' and liitetiedostot.liitostunnus=tuote.tunnus 
				JOIN avainsana ON avainsana.yhtio = liitetiedostot.yhtio and avainsana.laji = 'LITETY' and avainsana.selite!='TH' and avainsana.selite=liitetiedostot.kayttotarkoitus
				WHERE tuote.yhtio = '{$kukarow["yhtio"]}' and tuoteno = '$tuoteno' and hinnastoon = 'W'
				ORDER BY liitetiedostot.kayttotarkoitus IN ('TK') DESC, liitetiedostot.selite";
	$result = mysql_query($query) or pupe_error($query);
	if(mysql_num_rows($result) > 0) {
		
		$liitetiedostot = $edkaytto = "";
		
		while($row = mysql_fetch_array($result)) {
			if($row["kayttotarkoitus"] == "TK") {
				if($row["peukalokuva"] > 0) {
					$liitetiedostot .= "{$row["selite"]}<br><a href='view.php?id={$row["tunnus"]}' target='_blank'><img src='view.php?id={$row["peukalokuva"]}'></a><br><font class='info'>".t("Klikkaa kuvaa")."</info><br>";
				}
				else {
					$liitetiedostot .= "<a href='view.php?id={$row["tunnus"]}'  class='liite'>{$row["selite"]}</a><br>";
				}
			}
			else {				
				if(in_array($row["selitetark_2"], $liitetyypit)) {
					
					if($edkaytto != $row["kayttotarkoitus"]) {
						$liitetiedostot .= "<br><br><font class='bold'>{$row["selitetark"]}</font><br>";
						$edkaytto = $row["kayttotarkoitus"];
					}
					
					$liitetiedostot .= "<a href='view.php?id={$row["tunnus"]}' class='liite'>{$row["selite"]}</a><br>";
				}
			}
		}
	}	

	if($liitetiedostot == "") {
		$liitetiedostot = "Tuotteesta ei ole kuvia";
	}

	//	Vasemmalla meillä on kaikki tekstit
	echo "<table width='100%'><tr><td valign='top'>$tekstit</td><td valign='top'>$liitetiedostot</td></tr><tr><td class='back'><br></td></tr></table>";
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
		
		echo "<center><font class ='message'>".t("Tilaus %s mitätöity", $kieli, $kukarow["kesken"])."</font></center>";
		$kukarow["kesken"] = 0;
	}
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

if($tee == "tallenna_osoite") {
	$query = "	SELECT tunnus
				FROM lasku
				WHERE yhtio = '{$kukarow["yhtio"]}' and
				tila = 'N' and
				tunnus = '{$kukarow["kesken"]}' and
				alatila=''";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 1) {
		$kalakori = mysql_fetch_array($result);

		$query = "	UPDATE lasku SET
						toim_nimi 		= '$toim_nimi',
						toim_nimitark 	= '$toim_nimitark',
						toim_osoite 	= '$toim_osoite',
						toim_postino 	= '$toim_postino',
						toim_postitp 	= '$toim_postitp',
						viesti			= '$viesti',
						comments		= '$comments'
					WHERE yhtio = '{$kukarow["yhtio"]}' and tila = 'N' and tunnus = '$kukarow[kesken]' LIMIT 1";
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
	
	$query = "	SELECT *
				FROM lasku
				WHERE yhtio = '{$kukarow["yhtio"]}' and
				tila = 'N' and
				tunnus = '{$kukarow["kesken"]}' and
				alatila=''";
	$result = mysql_query($query) or pupe_error($query);
	if(mysql_num_rows($result) == 0) {
		$tee = "selaa";
	}
	else {
		$laskurow = mysql_fetch_array($result);
		
		$ulos = "<font class='head'>".t("Tilauksen %s tuotteet", $kieli, $kukarow["kesken"])." </font><br>
				<a href=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=selaa&osasto=$osasto&try=$try&tuotemerkki=$tuotemerkki')\">Takaisin selaimelle</a>&nbsp;&nbsp;";

		$query = "	SELECT count(*) rivei
					FROM tilausrivi 
					WHERE yhtio = '{$kukarow["yhtio"]}' and otunnus = '{$kukarow["kesken"]}' and tyyppi = 'L'";
		$result = mysql_query($query) or pupe_error($query);
		$row = mysql_fetch_array($result);

		if($row["rivei"] > 0) {
			$ulos .= "<a href=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=tilaa')\";>Tilaa tuotteet</a>&nbsp;&nbsp;";
			$ulos .= "<a href=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=poistakori&osasto=$osasto&try=$try&tuotemerkki=$tuotemerkki')\" disabled>Mitätöi tilaus</a>";
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
			$ulos .=  "	<form id = 'laskutiedot' name = 'laskutiedot' method='POST' action=\"javascript:ajaxPost('laskutiedot', 'verkkokauppa.php?', 'selain', false, true);\">
						<input type='hidden' name='tee' value = 'tallenna_osoite'>
						<input type='hidden' name='osasto' value = '$osasto'>
						<input type='hidden' name='tuotemerkki' value = '$tuotemerkki'>
						<input type='hidden' name='try' value = '$try'>
						<table style = 'width: 500px;'>
						<tr>
							<th>
								".t("Laskutusosoite")."
							</th>
							<th>
								".t("Toimitusosoite")."
							</th>
							<td class='back'></td>
						</tr>			
						<tr>
							<td valign = 'top' width='250px'>
								$laskurow[nimi]<br>
								$laskurow[nimitark]<br>
								$laskurow[osoite]<br>
								$laskurow[postino] $laskurow[postitp]
							</td>
							<td valign = 'top' width='250px'>
								<input type = 'text' name='toim_nimi' value = '$laskurow[toim_nimi]' size = '30'><br>
								<input type = 'text' name='toim_nimitark' value = '$laskurow[toim_nimitark]' size = '30'><br>
								<input type = 'text' name='toim_osoite' value = '$laskurow[toim_osoite]' size = '30'><br>
								<input type = 'text' name='toim_postino' value = '$laskurow[toim_postino]' size = '6'> <input type = 'text' name='toim_postitp' value = '$laskurow[toim_postitp]' size = '20'><br>
							</td>
							<td class='back' valign = 'bottom'>
								<input type = 'submit' name='tallenna' value='".t("Tallenna")."'>
							</td>
						</tr>			
						<tr>
							<td colspan='3' class='back'>
								<br>
							</td>
						</tr>			
						
						<tr>
							<th colspan='2'>
								".t("Tilausviite")."
							</th>
							<td class='back'></td>
						</tr>			
						<tr>
							<td colspan='2'>
								<input type = 'text' name='viesti' value='$laskurow[viesti]' size = '62'>
							</td>
							<td class='back'></td>
						</tr>			
						<tr>
							<th colspan='2'>
								".t("Lisätiedot")."
							</th>
							<td class='back'></td>
						</tr>			
						<tr>
							<td colspan='2'>
							<textarea name='comments' cols = '60' rows = '3'>$laskurow[comments]</textarea>
							</td>
							<td class='back'></td>
						</tr>			
						</table>
						</form><br>
						
						<font class='message'>".t("Tilausrivit").":</font><hr>
						
						<table style = 'width: 600px;'>
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
							<td class='back'><a href='#' onclick = \"javascript:sndReq('selain', 'verkkokauppa.php?tee=poistarivi&rivitunnus={$koririvi["tunnus"]}&osasto=$osasto&try=$try&tuotemerkki=$tuotemerkki')\">Poista</a></td>
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
	
	$tuoteno = $nimitys = "";
	if($hakutapa != "" and $tuotehaku == "") {
		die ("<font class='error'>".t("Anna jokin hakukriteeri")."</font>");
	}
	elseif($hakutapa == "koodilla") {
		$tuoteno = $tuotehaku;
	}
	elseif($hakutapa == "nimi") {
		$nimitys = $tuotehaku;
	}
		
	ob_start();
	$poistetut 	= "";
	$poistuvat 	= "";
	$lisatiedot	= "";
	if($tuotemerkki != "") {
		$ojarj		= "sorttauskentta, tuote_wrapper.tuotemerkki IN ('$tuotemerkki') DESC";
	}
	else {
		$ojarj		= "tuote_wrapper.tuotemerkki";
	}
	
	$haku[0]	= $tuoteno;	
	$haku[1]	= "";	
	$haku[2]	= $nimitys;
	$haku[3]	= $osasto;	
	$haku[4]	= $try;
	$haku[5]	= $tuotemerkki;	
	
	if($kukarow["kuka"] != "www" and (int) $kukarow["kesken"] == 0) {
		require_once("luo_myyntitilausotsikko.inc");
		$tilausnumero = luo_myyntitilausotsikko($kukarow["oletus_asiakas"], $tilausnumero, "");
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
	
	echo "	<div class='login' id='login'>$login_screen</div>
			<div class='menu' id='menu'>".menu()."</div>
			<div class='selain' id='selain'>".kuvaus()."</div>
			<div class='uutiset' id='uutiset'>".uutiset()."</div>
			</body></html>";
}


?>
