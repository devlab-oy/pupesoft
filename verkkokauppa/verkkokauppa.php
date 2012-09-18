<?php

$_GET["ohje"] = "off";

if ($_REQUEST["tee"] == "login") {

	$login 			= "yes";
	$extranet 		= 1;
	$_GET["no_css"] = "yes";

	require("parametrit.inc");

	if (trim($user) != '') {

		$session = "";
		$user = mysql_real_escape_string(trim($user));

		$query = "	SELECT kuka, session, salasana
					FROM kuka
					WHERE kuka 			 = '{$user}'
					AND yhtio 			 = '{$verkkokauppa}'
					AND extranet 		!= ''
					AND oletus_asiakas 	!= ''";
		$result = pupe_query($query);
		$krow = mysql_fetch_array($result);

		if ($salamd5 != '') {
			$vertaa = $salamd5;
		}
		elseif ($salasana == '') {
			$vertaa = $salasana;
		}
		else {
			$vertaa = md5(trim($salasana));
		}

		if (mysql_num_rows ($result) == 1 and $vertaa == $krow['salasana']) {

			srand ((double) microtime() * 1000000);

			for ($i = 0; $i < 25; $i++) {
				$session = $session . chr(rand(65,90)) ;
			}

			$query = "	UPDATE kuka
						SET session = '{$session}',
						lastlogin = now()
						WHERE kuka 			 = '{$user}'
						AND yhtio 			 = '{$verkkokauppa}'
						AND extranet		!= ''
						AND oletus_asiakas	!= ''";
			$result = pupe_query($query);

			$bool = setcookie("pupesoft_session", $session, time()+43200, parse_url($palvelin, PHP_URL_PATH));

			if ($location != "") {
				echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL={$location}'>";
			}
			else {
				echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL={$palvelin2}?go={$go}'>";
			}
			exit;
		}
	}

	$errormsg = "<br /><br /><font class='error'>Käyttäjätunnusta ei löydy ja/tai salasana on virheellinen!</font><br />";
	$tee = "";

	unset($user);
	unset($session);
	unset($login);
	unset($extranet);
	unset($_COOKIE);
	unset($_REQUEST);
	unset($_POST);
	unset($_GET);
}

require ("parametrit.inc");

if ($livesearch_tee == "TUOTEHAKU") {
	livesearch_tuotehaku();
	exit;
}

if (!isset($osasto)) $osasto = '';
if (!isset($try)) $try = '';
if (!isset($tuotemerkki)) $tuotemerkki = '';

if ($verkkokauppa == "") die("Verkkokauppayhtiö määrittelemättä");

if (!function_exists("tilaus")) {
	function tilaus($tunnus, $muokkaa = "") {
		global $yhtiorow, $kukarow, $verkkokauppa, $verkkokauppa_tuotemerkit, $verkkokauppa_saldotsk, $verkkokauppa_anon;

		$query = "	SELECT *
					FROM lasku
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tunnus = '{$tunnus}'
					AND liitostunnus = '{$kukarow['oletus_asiakas']}'";
		$result = pupe_query($query);
		$laskurow = mysql_fetch_array($result);

		if ($muokkaa != "" and ($laskurow["tila"] != "N" or $laskurow["alatila"] != "" or $kukarow["kesken"] != $tunnus)) {
			$ulos .= "<font class='error'>".t("Tilausta ei voi enää muokata")."!</font><br>";
			$muokkaa = "";
		}

		if ($muokkaa != "") {
			$ulos .= "	<form id = 'laskutiedot' name = 'laskutiedot' method='POST' action=\"javascript:ajaxPost('laskutiedot', 'verkkokauppa.php?', 'selain', false, false);\">
						<input type='hidden' name='tee' value = 'tallenna_osoite'>
						<input type='hidden' name='osasto' value = '{$osasto}'>
						<input type='hidden' name='tuotemerkki' value = '{$tuotemerkki}'>
						<input type='hidden' name='try' value = '{$try}'>";
		}

		ob_start();
		require("naytatilaus.inc");
		$ulos .= ob_get_contents();
		ob_end_clean();

		return $ulos;
	}
}

if (!function_exists("menu")) {
	function menu($osasto = "", $try = "") {
		global $yhtiorow, $kukarow, $verkkokauppa, $verkkokauppa_tuotemerkit, $verkkokauppa_saldotsk, $verkkokauppa_anon, $verkkokauppa_hakualkuun, $palvelin2;


		if (isset($osasto) and mb_detect_encoding($osasto, mb_detect_order(), TRUE) == "UTF-8") {
			$osasto = iconv("UTF-8", "latin1//TRANSLIT", $osasto);
		}

		if (isset($try) and mb_detect_encoding($try, mb_detect_order(), TRUE) == "UTF-8") {
			$try = iconv("UTF-8", "latin1//TRANSLIT", $try);
		}

		$val = "";

		if ($kukarow["kuka"] != "www") {
			$toimlisa = "<tr><td class='back'>&raquo; <a onclick=\"self.scrollTo(0,0);\" href=\"javascript:ajaxPost('tuotehaku', 'verkkokauppa.php?tee=selaa&hakutapa=toim_tuoteno', 'selain', false, false);\">".t("Toimittajan koodilla")."</a></td></tr>";
		}
		else {
			$toimlisa = "";
		}

		if ($verkkokauppa_tuotemerkit) {
			$tuotemerkkilis = " and tuote.tuotemerkki != '' ";
		}
		else {
			$tuotemerkkilis = "";
		}

		// vientikieltokäsittely:
		// +maa tarkoittaa että myynti on kielletty tähän maahan ja sallittu kaikkiin muihin
		// -maa tarkoittaa että ainoastaan tähän maahan saa myydä
		// eli näytetään vaan tuotteet jossa vienti kentässä on tyhjää tai -maa.. ja se ei saa olla +maa
		$kieltolisa = "";
		unset($vierow);

		if ($kukarow["kesken"] > 0) {
			$query  = "	SELECT IF(toim_maa != '', toim_maa, maa) maa
						FROM lasku
						WHERE yhtio	= '{$kukarow['yhtio']}'
						AND tunnus  = '{$kukarow['kesken']}'";
			$vieres = pupe_query($query);
			$vierow = mysql_fetch_array($vieres);
		}
		elseif ($verkkokauppa != "") {
			$vierow = array();

			if ($maa != "") {
				$vierow["maa"] = $maa;
			}
			else {
				$vierow["maa"] = $yhtiorow["maa"];
			}
		}

		if (isset($vierow) and $vierow["maa"] != "") {
			$kieltolisa = " and (tuote.vienti = '' or tuote.vienti like '%-{$vierow['maa']}%' or tuote.vienti like '%+%') and tuote.vienti not like '%+{$vierow['maa']}%' ";
		}

		if ($kukarow["kieli"] == "") {
			$kukarow["kieli"] = "FI";
		}

		if ($osasto == "") {
			$val =  "<table id='rootMenu' name='rootMenu' class='menutable' style='visibility: hidden;'>";

			$result = t_avainsana("VERKKOKAULINKKI");

			while ($orow = mysql_fetch_array($result)) {
				if ($orow["selite"] == "ETUSIVU") $val .= "<tr><td class='menucell'><a class='menu' href = '{$palvelin2}'>".t("Etusivu")."</a></td></tr>";
				else $val .= "<tr><td class='menucell'><a class='menu' href = \"javascript:sndReq('selain', 'verkkokauppa.php?tee=uutiset&sivu={$orow['selite']}', false, false);\">{$orow['selitetark']}</a></td></tr>";
			}

			$verkkokauppa_tuotehaku = "<tr><td class='back'><br><font class='info'>".t("Tuotehaku").":</font><br><hr></td></tr>
					 	<form id = 'tuotehaku' name='tuotehaku'  action = \"javascript:ajaxPost('tuotehaku', 'verkkokauppa.php?tee=selaa&hakutapa=nimi', 'selain', false, false);\" method = 'post'>
						<tr><td class='back'><input type = 'text' size='12' name = 'tuotehaku'></td></tr>
						<tr><td class='back'>&raquo; <a onclick=\"self.scrollTo(0,0);\" href=\"javascript:ajaxPost('tuotehaku', 'verkkokauppa.php?tee=selaa&hakutapa=nimi', 'selain', false, false);\">".t("Nimityksellä")."</a></td></tr>
						<tr><td class='back'>&raquo; <a onclick=\"self.scrollTo(0,0);\" href=\"javascript:ajaxPost('tuotehaku', 'verkkokauppa.php?tee=selaa&hakutapa=koodilla', 'selain', false, false);\">".t("Tuotekoodilla")."</a></td></tr>
						{$toimlisa}
						</form>";

			if ($verkkokauppa_anon or $kukarow["kuka"] != "www") {

				if ($verkkokauppa_hakualkuun) {
					$val .= $verkkokauppa_tuotehaku;
				}

				$val .=  "<tr><td class='back'><br><font class='info'>".t("Tuotteet").":</font><br><hr></td></tr>";

				$ores = t_avainsana("OSASTO", "", " and avainsana.nakyvyys = '' ");

				while ($orow = mysql_fetch_array($ores)) {
					$target		= "T_".$orow["selite"];
					$parent		= "P_".$orow["selite"];

					$onclick	= "document.getElementById(\"{$target}\").style.display==\"none\" ? sndReq(\"selain\", \"verkkokauppa.php?tee=uutiset&osasto={$orow['selite']}\", \"\", false) : \"\"";
					$href 		= "javascript:sndReq(\"{$target}\", \"verkkokauppa.php?tee=menu&osasto={$orow['selite']}\", \"{$parent}\", false, false);";
					$val .=  "<tr><td class='menucell td_parent'><a class = 'menu' id='{$parent}' onclick='{$onclick}' href='{$href}'>{$orow['selitetark']}</a></td></tr>
								<tr><td class='menuspacer'><div id='{$target}' style='display: none'></div></td></tr>";
				}

				if (!$verkkokauppa_hakualkuun) {
					$val .= $verkkokauppa_tuotehaku;
				}

			}

			$val .= "</table><script>setTimeout(\"document.getElementById('rootMenu').style.visibility='visible';\", 250)</script>";
		}
		elseif ($try == "" and ($verkkokauppa_anon or $kukarow["kuka"] != "www")) {
			$val = "<table class='menutable'>";

			$query = "	SELECT DISTINCT avainsana.selite try,
						IFNULL((SELECT avainsana_kieli.selitetark
				        FROM avainsana AS avainsana_kieli
				        WHERE avainsana_kieli.yhtio = avainsana.yhtio
				        AND avainsana_kieli.laji = avainsana.laji
				        AND avainsana_kieli.perhe = avainsana.perhe
				        AND avainsana_kieli.kieli = '{$kukarow['kieli']}' LIMIT 1), avainsana.selitetark) trynimi
						FROM tuote
						JOIN avainsana ON (avainsana.yhtio = tuote.yhtio AND tuote.try = avainsana.selite AND avainsana.laji = 'TRY' AND avainsana.kieli IN ('{$yhtiorow['kieli']}', '') AND avainsana.nakyvyys = '')
						WHERE tuote.yhtio = '{$kukarow['yhtio']}'
						AND tuote.osasto = '{$osasto}'
						{$kieltolisa}
						{$tuotemerkkilis}
						AND tuote.status != 'P'
						AND tuote.hinnastoon IN ('W', 'V')
						ORDER BY avainsana.jarjestys, avainsana.selite+0";
			$tryres = pupe_query($query);

			while ($tryrow = mysql_fetch_array($tryres)) {

				//	Oletuksena pimitetään kaikki..
				$ok = 0;

				//	Tarkastetaan onko täällä sopivia tuotteita
				$query = "	SELECT *
				 			FROM tuote
							WHERE yhtio 	= '{$kukarow['yhtio']}'
							AND osasto 		= '{$osasto}'
							AND try 		= '{$tryrow['try']}'
							AND status 	   != 'P'
							{$kieltolisa}
							{$tuotemerkkilis}
							AND hinnastoon IN ('W','V')";
				$res = pupe_query($query);

				while ($trow = mysql_fetch_array($res) and $ok == 0) {

					// Jos tuote näytetään vain jos asiakkaalla on asiakasalennus tai asiakahinta niin skipataan se jos alea tai hintaa ei löydy
					if ($trow["hinnastoon"] == "V" or $kukarow["naytetaan_tuotteet"] == "A") {

						if (!is_array($asiakasrow)) {
							$query = "	SELECT *
										FROM asiakas
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND tunnus = '{$kukarow['oletus_asiakas']}'";
							$asres = pupe_query($query);
							$asiakasrow = mysql_fetch_array($asres);
						}

						$hinnat = alehinta(array(
									"valkoodi" => "EUR",
									"maa" => $yhtiorow["maa"],
									"vienti_kurssi" => 1,
									"liitostunnus" => $asiakasrow["tunnus"],
									"ytunnus" => $asiakasrow["ytunnus"]) , $trow, 1, '', '', '', "hintaperuste,aleperuste");

						if ($hinnat["hintaperuste"] !== FALSE and $hinnat["hintaperuste"] >= 2 and $hinnat["hintaperuste"] <= 13) {
							$ok = 1;
						}

						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							if (isset($hinnat["aleperuste"]["ale".$alepostfix]) and ($hinnat["aleperuste"] !== FALSE and $hinnat["aleperuste"]["ale".$alepostfix] >= 5 and $hinnat["aleperuste"]["ale".$alepostfix] < 13)) {
								$ok = 1;
								break;
							}
						}
					}
					else {
						$ok = 1;
					}
				}

				if ($ok == 1) {
					$target	= "P_".$osasto."_".$tryrow["try"];
					$parent	= "T_".$osasto."_".$tryrow["try"];

					if ($verkkokauppa_tuotemerkit) {
						$onclick	= "sndReq(\"selain\", \"verkkokauppa.php?tee=selaa&osasto={$osasto}&try={$tryrow['try']}\", \"\", false)";
						$href 	= "javascript:sndReq(\"{$target}\", \"verkkokauppa.php?tee=menu&osasto={$osasto}&try={$tryrow['try']}\", \"{$parent}\", false); sndReq(\"selain\", \"verkkokauppa.php?tee=selaa&osasto={$osasto}&try={$tryrow['try']}&tuotemerkki=\", \"\", false);";
						$val .=  "<tr><td class='menuspacer'>&nbsp;</td><td class='menucell td_parent_try'><a class = 'menu' id='{$parent}' onclick='{$onclick}' href='{$href}'>{$tryrow['trynimi']}</a><div id=\"{$target}\" style='display: none'></div></td></tr>";
					}
					else {
						$val .=  "<tr><td class='menuspacer'>&nbsp;</td><td class='menucell td_parent_try'><a class = 'menu' name = 'menulinkki' id='{$parent}' onclick=\"var aEls = document.getElementsByName('menulinkki'); for (var iEl = 0; iEl < aEls.length; iEl++) { document.getElementById(aEls[iEl].id).className='menu';} this.className='menuselected'; self.scrollTo(0,0);\" href=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=selaa&osasto={$osasto}&try={$tryrow['try']}&tuotemerkki=', '', false);\">{$tryrow['trynimi']}</a></td></tr>";
					}
				}
			}
			$val .= "</table>";
		}
		elseif ($verkkokauppa_tuotemerkit and ($verkkokauppa_anon or $kukarow["kuka"] != "www")) {
			$val = "<table class='menutable'>";

			$query = "	SELECT DISTINCT avainsana.selite AS tuotemerkki,
						IFNULL((SELECT avainsana_kieli.selite
	        			FROM avainsana AS avainsana_kieli
	        			WHERE avainsana_kieli.yhtio = avainsana.yhtio
	        			AND avainsana_kieli.laji = avainsana.laji
	        			AND avainsana_kieli.perhe = avainsana.perhe
	        			AND avainsana_kieli.kieli = '{$kukarow['kieli']}' LIMIT 1), avainsana.selite) selite
						FROM tuote
						JOIN avainsana ON (avainsana.yhtio = tuote.yhtio AND tuote.tuotemerkki = avainsana.selite AND avainsana.laji = 'TUOTEMERKKI' AND avainsana.nakyvyys = '')
						WHERE tuote.yhtio = '{$kukarow['yhtio']}'
						AND tuote.osasto = '{$osasto}'
						AND tuote.try = '{$try}'
						{$kieltolisa}
						{$tuotemerkkilis}
						AND tuote.status != 'P'
						AND tuote.hinnastoon IN ('W', 'V')
						ORDER BY avainsana.jarjestys, avainsana.selite";
			$meres = pupe_query($query);

			while ($merow = mysql_fetch_array($meres)) {

				//	Oletuksena pimitetään kaikki..
				$ok = 0;

				//	Tarkastetaan onko täällä sopivia tuotteita
				$query = "	SELECT *
				 			FROM tuote
							WHERE yhtio 	= '{$kukarow['yhtio']}'
							AND osasto 		= '{$osasto}'
							AND try 		= '{$try}'
							AND tuotemerkki = '{$merow['tuotemerkki']}'
							AND status 	   != 'P'
							{$kieltolisa}
							AND hinnastoon IN ('W', 'V')";
				$res = pupe_query($query);

				while ($trow = mysql_fetch_array($res) and $ok == 0) {

					// Jos tuote näytetään vain jos asiakkaalla on asiakasalennus tai asiakahinta niin skipataan se jos alea tai hintaa ei löydy
					if ($trow["hinnastoon"] == "V" or $kukarow["naytetaan_tuotteet"] == "A") {

						if (!is_array($asiakasrow)) {
							$query = "	SELECT *
										FROM asiakas
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND tunnus = '{$kukarow['oletus_asiakas']}'";
							$asres = pupe_query($query);
							$asiakasrow = mysql_fetch_array($asres);
						}

						$hinnat = alehinta(array(
									"valkoodi" => "EUR",
									"maa" => $yhtiorow["maa"],
									"vienti_kurssi" => 1,
									"liitostunnus" => $asiakasrow["tunnus"],
									"ytunnus" => $asiakasrow["ytunnus"]) , $trow, 1, '', '', '', "hintaperuste,aleperuste");

			            if ($hinnat["hintaperuste"] !== FALSE and $hinnat["hintaperuste"] >= 2 and $hinnat["hintaperuste"] <= 13) {
							$ok = 1;
						}

						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							if (isset($hinnat["aleperuste"]["ale".$alepostfix]) and ($hinnat["aleperuste"] !== FALSE and $hinnat["aleperuste"]["ale".$alepostfix] >= 5 and $hinnat["aleperuste"]["ale".$alepostfix] < 13)) {
								$ok = 1;
								break;
							}
						}
					}
					else {
						$ok = 1;
					}
				}

				if ($ok == 1) {
					$val .=  "<tr><td class='menuspacer'>&nbsp;</td><td class='menucell td_parent_tuotemerkki'><a class = 'menu' id='P_{$osasto}_{$try}_{$merow['selite']}' name = 'menulinkki' onclick=\"var aEls = document.getElementsByName('menulinkki'); for (var iEl = 0; iEl < aEls.length; iEl++) { document.getElementById(aEls[iEl].id).className='menu';} this.className='menuselected'; self.scrollTo(0,0);\" href=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=selaa&osasto={$osasto}&try={$try}&tuotemerkki={$merow['tuotemerkki']}', '', false);\">{$merow['tuotemerkki']}</a></td></tr>";
				}
			}
			$val .= "</table>";
		}

		return $val;
	}
}

if (!function_exists("uutiset")) {
	function uutiset($osasto="", $try="", $sivu="") {
		global $yhtiorow, $kukarow, $verkkokauppa, $verkkokauppa_tuotemerkit, $verkkokauppa_saldotsk, $verkkokauppa_anon;

		if ($sivu != "") {
			$lisa = "and tyyppi = 'VERKKOKAUPPA' and kentta09 = '$sivu' ";
		}
		else {

			if ($osasto == "") {
				$lisa = "and tyyppi = 'VERKKOKAUPPA' and kentta09 = '' and kentta10 = ''";
			}
			elseif ($try == "") {
				$lisa = "and tyyppi = 'VERKKOKAUPPA' and kentta09 = '$osasto' and kentta10 = ''";
			}
			else {
				$lisa = "and tyyppi = 'VERKKOKAUPPA' and kentta09 = '$osasto' and kentta10 = '$try'";
			}
		}

		// Ekotetaan avoin kori
		$val = avoin_kori();

		$query = "	SELECT *
					FROM kalenteri
					WHERE yhtio = '$kukarow[yhtio]'  $lisa
					ORDER BY kokopaiva DESC, luontiaika DESC
					LIMIT 8";
		$result = pupe_query($query);

		if (mysql_num_rows($result) > 0) {
			$val .= "<table class='uutinen'>";

			while ($row = mysql_fetch_array($result)) {
				$val .= "<tr>";

				if ($row["kentta03"] > 0) {
					$val .= "<td class='back'><img class='uutinen' width='100px' src='view.php?id=$row[kentta03]'></td>";
				}
				else {
					$val .= "<td class='back'></td>";
				}

				$val .= "<td class='back'>";

				$search = "/#{2}([^#]+)#{2}(([^#]+)#{2}){0,1}/";
				preg_match_all($search, $row["kentta02"], $matches, PREG_SET_ORDER);

				if (count($matches) > 0) {
					$search = array();
					$replace = array();

					foreach($matches as $m) {

						//	Haetaan tuotenumero
						$query = "	SELECT tuoteno, nimitys
						 			FROM tuote
									WHERE yhtio = '$kukarow[yhtio]' and tuoteno like ('$m[1]%') and status != 'P'";
						$tres = pupe_query($query);

						//	Tämä me korvataan aina!
						$search[] = "/$m[0]/";

						if (mysql_num_rows($tres) <> 1) {
							$replace[]	= "";
						}
						else {
							$trow = mysql_fetch_array($tres);
							if (count($m) == 4) {
								$replace[]	= "<a href = \"javascript:sndReq('selain', 'verkkokauppa.php?tee=selaa&hakutapa=koodilla&tuotehaku=$m[1]', false, false);\">$m[3]</a>";
							}
							else {
								$replace[]	= "<a href = \"javascript:sndReq('selain', 'verkkokauppa.php?tee=selaa&hakutapa=koodilla&tuotehaku=$m[1]', false, false);\">$trow[nimitys]</a>";
							}
						}
					}

					$row["kentta02"] = preg_replace($search, $replace, $row["kentta02"]);
				}

				$val .= "<font class='head'>$row[kentta01]</font><br>$row[kentta02]</font><hr>";
				$val .= "</td></tr>";

			}
			$val .= "</table>";
		}

		return $val;
	}
}

if ($tee == "menu") {
	die(menu($osasto, $try));
}

if ($tee == "monistalasku") {
	//	Tehdään tämä funktiossa niin ei saada vääriä muuttujia injisoitua
	function monistalasku($laskunro) {
		global $yhtiorow, $kukarow;

		$query = "	SELECT tunnus
					FROM lasku
					WHERE yhtio='$kukarow[yhtio]' and liitostunnus='$kukarow[oletus_asiakas]' and laskunro = '$laskunro' and tila = 'U'";
		$result = pupe_query($query);
		if (mysql_num_rows($result)==1) {
			$row = mysql_fetch_array($result);

			//	Passataan oikea array
			$monistettavat[$row["tunnus"]] = "";
			$kklkm = 1;
			$tee = "MONISTA";
			$vain_monista = "utilaus";

			require("monistalasku.php");

			return end($tulos_ulos);
		}
		else {
			return false;
		}
	}

	$tilaus = monistalasku($laskunro);
	if ($tilaus===false) {
		echo "<font class='error'>".t("Tilauksen monistaminen epäonnistui")."!!</font><br>";
	}
}

if ($tee == "jatkatilausta") {
	if ((int) $tilaus > 0) {
		$kukarow["kesken"] = $tilaus;
		$query = "	UPDATE kuka SET kesken = '$tilaus' WHERE yhtio ='$kukarow[yhtio]' and kuka = '$kukarow[kuka]'";
		$result = pupe_query($query);

		echo "<font class='message'>".t("Aktivoitiin tilaus %s", $kieli, $tilaus)."</font><br>";

		$tee = "tilatut";
	}
}

if ($tee == "keskeytatilaus") {
	if ((int) $tilaus > 0) {
		$kukarow["kesken"] = 0;
		$query = "	UPDATE kuka SET kesken = 0 WHERE yhtio ='$kukarow[yhtio]' and kuka = '$kukarow[kuka]'";
		$result = pupe_query($query);

		echo "<font class='message'>".t("Jätettiin tilaus %s kesken", $kieli, $tilaus)."</font><br>";
	}
}

if ($tee == "uutiset") {
	die(uutiset($osasto, $try, $sivu));
}

if ($tee == "tuotteen_lisatiedot") {

	$query = "	SELECT kuvaus, lyhytkuvaus
				FROM tuote
				WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno' and hinnastoon in ('W','V') and status != 'P'";
	$result = pupe_query($query);
	$row = mysql_fetch_array($result);

	//	Eka rivi aina head
	if (trim($row["kuvaus"]) == "" and trim($row["lyhytkuvaus"]) == "") {
		$row["tekstit"] = t("Tuotteestamme ei ole tällähetkellä lisätietoja..");
	}
	else {
		$row["tekstit"] = $row["lyhytkuvaus"]."\n<br>".$row["kuvaus"];
		//$row["tekstit"] = preg_replace("/(.*)/", "<font class='head'>\$1</font>", $row["tekstit"], 1);
	}

	if ($row["tekstit"] != "") {
		$search = $replace = array();
		// Shit! joudutaan tutkimaan mille välille tehdään li
		$search[] 	= "/#{2}\+/";
		$replace[] 	= "<ul class='ruutu'>";
		$search[] 	= "/#{2}\-/";
		$replace[]	= "</ul>";

		$search[] 	= "/\*{2}\+/";
		$replace[] 	= "<ul class='pallo'>";
		$search[] 	= "/\*{2}\-/";
		$replace[] 	= "</ul>";

		$search[] 	= "/\*{2}([^\+\-].*)|\#{2}([^\+\-].*)/";
		$replace[] 	= "<li>\$1\$2</li>";

		$search[]	= "/\?\?(.*)\?\?/m";
		$replace[]	= "<font class='italic'>\$1</font>";
		$search[]	= "/\!\!(.*)\!\!/m";
		$replace[]	= "<font class='bold'>\$1</font>";

		$tekstit = preg_replace($search, $replace, nl2br($row["tekstit"]));
	}
	else {
		$tekstit = "";
	}

	if ($kukarow["kuka"] == "www") {
		$liitetyypit = array("public");
	}
	else {
		$liitetyypit = array("extranet","public");
	}

	//	Haetaan kaikki liitetiedostot
	$query = "	SELECT liitetiedostot.tunnus, liitetiedostot.selite, liitetiedostot.kayttotarkoitus, avainsana.selitetark, avainsana.selitetark_2,
				(select tunnus from liitetiedostot l where l.yhtio=liitetiedostot.yhtio and l.liitos=liitetiedostot.liitos and l.liitostunnus=liitetiedostot.liitostunnus and l.kayttotarkoitus='TH' and l.filename=liitetiedostot.filename ORDER BY l.tunnus DESC LIMIT 1) peukalokuva
				FROM tuote
				JOIN liitetiedostot ON liitetiedostot.yhtio = tuote.yhtio and liitos = 'TUOTE' and liitetiedostot.liitostunnus=tuote.tunnus
				JOIN avainsana ON avainsana.yhtio = liitetiedostot.yhtio and avainsana.laji = 'LITETY' and avainsana.selite!='TH' and avainsana.selite=liitetiedostot.kayttotarkoitus
				WHERE tuote.yhtio = '$kukarow[yhtio]'
				and tuote.tuoteno = '$tuoteno'
				and tuote.hinnastoon in ('W','V')
				and tuote.status != 'P'
				ORDER BY liitetiedostot.kayttotarkoitus IN ('TK') DESC, liitetiedostot.selite";
	$result = pupe_query($query);

	if (mysql_num_rows($result) > 0) {

		$liitetiedostot = $edkaytto = "";

		while($row = mysql_fetch_array($result)) {
			if ($row["kayttotarkoitus"] == "TK") {
				if ($row["peukalokuva"] > 0) {
					$liitetiedostot .= "$row[selite]<br><a href='view.php?id=$row[tunnus]' target='_blank'><img src='view.php?id=$row[peukalokuva]'></a><br><font class='info'>".t("Klikkaa kuvaa")."</info><br>";
				}
				else {
					$liitetiedostot .= "<a href='view.php?id=$row[tunnus]'  class='liite'>$row[selite]</a><br>";
				}
			}
			else {
				if (in_array($row["selitetark_2"], $liitetyypit)) {

					if ($edkaytto != $row["kayttotarkoitus"]) {
						$liitetiedostot .= "<br><br><font class='bold'>$row[selitetark]</font><br>";
						$edkaytto = $row["kayttotarkoitus"];
					}

					$liitetiedostot .= "<a href='view.php?id=$row[tunnus]' class='liite'>$row[selite]</a><br>";
				}
			}
		}
	}

	if ($liitetiedostot == "") {
		$liitetiedostot = "Tuotteesta ei ole kuvia";
	}

	//	Vasemmalla meillä on kaikki tekstit
	if (stripos($_SERVER["HTTP_USER_AGENT"], "MSIE") !== FALSE) {
		echo "<table width='100%'><tr><td class='back' style='width:50px;'></td><td class='back'>$tekstit</td><td class='back' style='text-align:right;'>$liitetiedostot</td></tr><tr><td class='back'><br></td></tr></table>";
	}
	else {
		echo "<td class='back'>&nbsp;</td><td><table width='100%'><tr><td class='back' style='padding-left:10px;'>$tekstit</td><td class='back' style='text-align:right;'>$liitetiedostot</td></tr></table></td>";
	}
}

if ($tee == "poistakori") {
	$query = "	SELECT tunnus
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]' and
				tila = 'N' and
				tunnus = '$kukarow[kesken]' and
				alatila=''";
	$result = pupe_query($query);

	if (mysql_num_rows($result) == 1) {
		// löyty vaan yks dellataan se
		//$ostoskori = mysql_fetch_array($result);
		$kalakori = mysql_fetch_array($result);

		$query = "	UPDATE tilausrivi SET
					tyyppi = 'D',
					kommentti = trim(concat(kommentti, ' Käyttäjä $kukarow[kuka] mitätöi tilausrivin laskulta $kalakori[tunnus].'))
					where yhtio = '$kukarow[yhtio]' and
					tyyppi = 'L' and
					otunnus = '$kalakori[tunnus]'";
		$result = pupe_query($query);

		$query = "	UPDATE lasku SET
					alatila = tila,
					tila = 'D',
					comments = trim(concat(comments, ' Käyttäjä $kukarow[kuka] mitätöi laskun $kalakori[tunnus].'))
					where yhtio = '$kukarow[yhtio]' and
					tila = 'N' and
					tunnus = '$kalakori[tunnus]'";
		$result = pupe_query($query);

		$query = "	UPDATE kuka SET kesken = '' WHERE yhtio ='$kukarow[yhtio]' and kuka = '$kukarow[kuka]'";
		$result = pupe_query($query);

		echo "<center><font class ='message'>".t("Tilaus %s mitätöity", $kieli, $kukarow["kesken"])."</font></center>";
		$kukarow["kesken"] = 0;
	}
}

if ($tee == "poistarivi") {
	$query = "	SELECT tunnus
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]' and
				tila = 'N' and
				tunnus = '$kukarow[kesken]' and
				alatila=''";
	$result = pupe_query($query);

	if (mysql_num_rows($result) == 1) {
		// löyty vaan yks dellataan siitä rivi
		//$ostoskori = mysql_fetch_array($result);
		$kalakori = mysql_fetch_array($result);

		$query = "	UPDATE tilausrivi SET
					tyyppi = 'D',
					kommentti = trim(concat(kommentti, ' Käyttäjä $kukarow[kuka] mitätöi tilausrivin laskulta $kukarow[kesken].'))
					WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'L' and tunnus = '$rivitunnus' LIMIT 1";
		$result = pupe_query($query);
	}

	$tee = "tilatut";
}

if ($tee == "tallenna_osoite") {
	$query = "	SELECT tunnus, toimaika
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]' and
				tila = 'N' and
				tunnus = '$kukarow[kesken]' and
				alatila=''";
	$result = pupe_query($query);

	if (mysql_num_rows($result) == 1) {
		$kalakori = mysql_fetch_array($result);

		$query = "	UPDATE lasku SET
						toim_nimi 				= '$toim_nimi',
						toim_nimitark 			= '$toim_nimitark',
						toim_osoite 			= '$toim_osoite',
						toim_postino 			= '$toim_postino',
						toim_postitp 			= '$toim_postitp',
						tilausyhteyshenkilo		= '$tilausyhteyshenkilo',
						asiakkaan_tilausnumero	= '$asiakkaan_tilausnumero',
						kohde					= '$kohde',
						viesti					= '$viesti',
						comments				= '$comments',
						toimaika				= '$toimvv-$toimkk-$toimpp'
					WHERE yhtio = '$kukarow[yhtio]' and tila = 'N' and tunnus = '$kukarow[kesken]' LIMIT 1";
		$result = pupe_query($query);

		$query = "	UPDATE tilausrivi
					SET toimaika = '".$toimvv."-".$toimkk."-".$toimpp."'
					WHERE yhtio = '$kukarow[yhtio]' and otunnus = '$kalakori[tunnus]' and toimaika = '$kalakori[toimaika]'";
		$result = pupe_query($query);
	}

	$tee = "tilatut";
}

if ($tee == "tilaa") {
	$query = "	SELECT *
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]' and
				tila = 'N' and
				tunnus = '$kukarow[kesken]' and
				alatila=''";
	$result = pupe_query($query);
	if (mysql_num_rows($result) == 1) {

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
			$result = pupe_query($query);
		}
		else {
			// tulostetaan lähetteet ja tilausvahvistukset tai sisäinen lasku..
			$silent = "JOO";
			$tilausvalmiskutsuja = "VERKKOKAUPPA";

			require("tilaus-valmis.inc");
		}

		$ulos = "<font class='head'>".t("Kiitos tilauksesta")."</font><br><br><font class='message'>".t("Tilauksesi numero on").": $kukarow[kesken]</font><br>";


		// tilaus ei enää kesken...
		$query	= "UPDATE kuka SET kesken=0 WHERE yhtio='$kukarow[yhtio]' AND kuka='$kukarow[kuka]'";
		$result = pupe_query($query);
	}

	die($ulos);
}

if ($tee == "tilatut") {

	$query = "	SELECT *
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]' and
				tila = 'N' and
				tunnus = '$kukarow[kesken]' and
				alatila = ''";
	$result = pupe_query($query);

	if (mysql_num_rows($result) == 0) {
		$tee = "selaa";
	}
	else {
		$laskurow = mysql_fetch_array($result);

		$ulos = "<font class='head'>".t("Tilauksen %s tuotteet", $kieli, $kukarow["kesken"])." </font><br>";

		if ($osasto != "" and $try != "") {
			$ulos .= "<a href=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=selaa&osasto=$osasto&try=$try&tuotemerkki=$tuotemerkki')\">".t("Takaisin selaimelle")."</a>&nbsp;&nbsp;";
		}

		$query = "	SELECT count(*) rivei
					FROM tilausrivi
					WHERE yhtio = '$kukarow[yhtio]' and otunnus = '$kukarow[kesken]' and tyyppi = 'L'";
		$result = pupe_query($query);
		$row = mysql_fetch_array($result);

		$ulos .= "<br><input type='button' onclick=\"if (confirm('".t("Oletko varma, että haluat jättää tilauksen %s kesken?", $kieli, $laskurow["tunnus"])."')) { sndReq('selain', 'verkkokauppa.php?tee=asiakastiedot&tee=keskeytatilaus&tilaus=$laskurow[tunnus]', false, false); }\" value='".t("Jätä kesken")."'>&nbsp;&nbsp;";

		if ($row["rivei"] > 0) {
			$ulos .= "<input type='button' onclick=\"if (confirm('".t("Oletko varma, että haluat lähettää tilauksen eteenpäin?")."')) { sndReq('selain', 'verkkokauppa.php?tee=tilaa'); }\" value='".t("Tilaa tuotteet")."'>&nbsp;&nbsp;";
		}

		$ulos .= "<input type='button' onclick=\"if (confirm('".t("Oletko varma, että haluat mitätöidä tilauksen?")."')) { sndReq('selain', 'verkkokauppa.php?tee=poistakori&osasto=$osasto&try=$try&tuotemerkki=$tuotemerkki'); }\" value='".t("Mitätöi tilaus")."'>";

		$ulos .= "<br><br>";

		$ulos .= tilaus($kukarow["kesken"], "JOO");

		die($ulos);
	}
}

if ($tee == "asiakastiedot") {

	// Ekotetaan avoin kori
	echo avoin_kori();

	if ($nayta == "") $nayta = "asiakastiedot";

	if ($nayta == "asiakastiedot") {
		$query = "	SELECT *,
							concat_ws('<br>', nimi, nimitark, osoite, postino, postitp) laskutusosoite,
							concat_ws('<br>', toim_nimi, toim_nimitark, toim_osoite, toim_postino, toim_postitp) toimitusosoite
					FROM asiakas
					WHERE yhtio='$kukarow[yhtio]' and tunnus='$kukarow[oletus_asiakas]'";
		$result = pupe_query($query);
		$asiakasrow = mysql_fetch_array($result);

		echo "<table>
				<tr>
					<th>".t("Ostajan osoite")."</th><th>".t("Toimitusosoite")."</th>
				</tr>
				<tr>
					<td>$asiakasrow[laskutusosoite]</td><td>$asiakasrow[toimitusosoite]</td>
				</tr>
			</table>";
	}
	elseif ($nayta == "tilaushistoria") {

		if ($kukarow["naytetaan_tilaukset"] != "O") {
			$selv = array();
			$selv[$vainomat] = "SELECTED";

			$vainoomat = "<div>
				<form id = 'vainoma' name='tilaushaku' action = \"javascript:ajaxPost('vainoma', 'verkkokauppa.php?tee=asiakastiedot&nayta=tilaushistoria&hakutapa=$hakutapa&tilaushaku=$tilaushaku&tila=$tila', 'selain', false, false);\" method = 'post'>
					<select name='vainomat' onchange='submit();'>
						<option value=''>".t("Näytä kaikkien tilaukset")."</option>
						<option value='x' $selv[x]>".t("Näytä vain omat tilaukset")."</option>
					</select>
				</form>
				</div>";
		}
		else {
			$vainoomat = "";
			$vainomat  = "x";
		}

		echo "<table>";

		if (!($hakutapa == "tila" and $tilaustila == "kesken")) {
			echo "	<tr>
					<td class='back' colspan='5'>
					$vainoomat
					<br>
					".t("Tilaushaku").":<br>
					<form id = 'tilaushaku' name='tilaushaku' action = \"javascript:ajaxPost('tilaushaku', 'verkkokauppa.php?tee=asiakastiedot&nayta=tilaushistoria&vainomat=$vainomat&hakutapa=viitteet', 'selain', false, false);\" method = 'post'>
					<input type = 'text' size='50' name = 'tilaushaku' value='$tilaushaku'>
					<br>
					<input type='submit' onclick=\"javascript:ajaxPost('tilaushaku', 'verkkokauppa.php?tee=asiakastiedot&nayta=tilaushistoria&vainomat=$vainomat&tila=haku&hakutapa=viitteet', 'selain', false, false);\" value='".t("Hae laskun viitteistä")."'><br>
					<input type='submit' onclick=\"javascript:ajaxPost('tilaushaku', 'verkkokauppa.php?tee=asiakastiedot&nayta=tilaushistoria&vainomat=$vainomat&tila=haku&hakutapa=toimitusosoite', 'selain', false, false);\" value='".t("Hae laskun toimitusosoitteesta")."'><br>
					</form>
					<br>
					</td>
					</tr>";
		}

		$aika 		= "";
		$tilaukset  = array();
		$where 		= "";

		if ($vainomat != "") $vainlisa = " and laatija = '$kukarow[kuka]'";
		else $vainlisa = "";

		if ($tila == "haku") {
			$aika = "luontiaika";

			if (strlen($tilaushaku) > 2 or $tilaustila != "") {
				$where = " and tila in ('N','L') ";

				if ($hakutapa == "viitteet") {
					$where .= " and concat_ws(' ', viesti, comments, sisviesti2, sisviesti1, asiakkaan_tilausnumero) like ('%".mysql_real_escape_string($tilaushaku)."%') ";
				}
				elseif ($hakutapa == "toimitusosoite") {
					$where .= " and concat_ws(' ', toim_nimi, toim_nimitark, toim_osoite, toim_postino, toim_postitp) like ('%".mysql_real_escape_string($tilaushaku)."%') ";
				}
				elseif ($hakutapa == "tila") {
					if ($tilaustila == "kesken") {
						$where = " and lasku.tila = 'N' and alatila = '' ";
					}
					else {
						$where = "";
					}
				}
			}
			else {
				echo "<tr><td class='back' colspan='5'><font class='error'>".t("Haussa on oltava vähintään 3 merkkiä")."</font></td></tr>";
			}
		}
		elseif ($tila == "kesken") {
			$aika = "luontiaika";
			$where = " and tila = 'N' and alatila = '' ";
		}
		elseif ($tila == "kasittely") {
			$aika = "luontiaika";
			$where = " and tila = 'N' and alatila = 'F' ";
		}
		elseif ($tila == "odottaa") {
			$aika = "luontiaika";
			$where = " and tila = 'N' and alatila NOT IN ('', 'F') ";
		}
		elseif ($tila == "toimituksessa") {
			$aika = "lahetepvm";
			$where = " and tila = 'L' and alatila IN ('A', 'B', 'C', 'E') ";
		}
		elseif ($tila == "toimitettu") {
			$aika = "(select max(toimitettuaika) from tilausrivi where tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus)";
			$where = " and tila = 'L' and alatila IN ('D', 'J', 'V', 'X') ";
		}

		if ($where != "") {

			$query_ale_lisa = generoi_alekentta('M');

			$query = "	SELECT lasku.*, date_format($aika, '%d. %m. %Y') aika,
						(	SELECT sum(tilausrivi.hinta * if ('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.kpl+tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa})
							FROM tilausrivi
							WHERE tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tyyppi != 'D'
						) summa
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						and liitostunnus = '$kukarow[oletus_asiakas]'
						$vainlisa
						$where";
			$result = pupe_query($query);

			if (mysql_num_rows($result) > 0) {

				if ($tila == "kesken") {
					$aika = "Avattu";
				}
				elseif ($tila == "kasittely") {
					$aika = "Avattu";
				}
				elseif ($tila == "odottaa") {
					$aika = "Tilattu";
				}
				elseif ($tila == "toimituksessa") {
					$aika = "Kerätty";
				}
				elseif ($tila == "toimitettu") {
					$aika = "Toimitettu";
				}

				$tilaukset[$tila] .= "	<tr>
										<th>".t("Tilaus")."</th>
										<th>".t($aika)."</th>
										<th>".t("Tilausviite")."</th>
										<th>".t("Summa")."</th>
										<td class='back'></td>
										<tr>";

				while ($laskurow = mysql_fetch_array($result)) {
					$lisa = "";

					if ($tilaus == $laskurow["tunnus"]) {
						$lisa .= "	<tr><td class='back' colspan='5'><br>".tilaus($laskurow["tunnus"])."<br></td></tr>";
					}

					$monista = $jatka ="";

					if ($laskurow["laskunro"] > 0) {
						$monista = " <a href=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=asiakastiedot&tee=monistalasku&laskunro=$laskurow[laskunro]', false, false);\" onclick=\"return confirm('".t("Oletko varma, että haluat monistaa tilauksen?")."');\">".t("Monista")."</a>";
					}

					if ($laskurow["tila"] == "N" and $laskurow["alatila"] == "") {
						if ($laskurow["tunnus"] != $kukarow["kesken"]) {
							$jatka = " <a href=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=asiakastiedot&tee=jatkatilausta&tilaus=$laskurow[tunnus]', false, false);\" onclick=\"return confirm('".t("Oletko varma, että haluat jatkaa tilausta %s?", $kieli, $laskurow["tunnus"])."');\">".t("Aktivoi")."</a>";
						}
						else {
							$jatka = t("Akviivinen");
						}
					}

					$tilaukset[$tila] .= "	<tr>
											<td>$laskurow[tunnus]</td>
											<td>$laskurow[aika]</td>
											<td>$laskurow[viesti]</td>
											<td>".number_format($laskurow["summa"], 2, ',', ' ')."</td>
											<td class='back'><a href=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=asiakastiedot&nayta=tilaushistoria&vainomat=$vainomat&tila=$tila&hakutapa=$hakutapa&tilaustila=$tilaustila&tilaus=$laskurow[tunnus]', false, false);\">".t("Näytä")."</a> $jatka $monista</td>
											</tr>$lisa";
				}
			}
			else {
				$tilaukset[$tila] = "<tr><td class='back' colspan='5'>".t("Ei tilauksia")."</td></tr>";
			}
			$tilaukset[$tila] .= "<tr><td class='back' colspan='5'><br></td></tr>";
		}

		echo $tilaukset["haku"];

		if ($tilaustila != "kesken") {
			echo "<tr>
					<td class='back' colspan='5'>
						<a href=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=asiakastiedot&nayta=tilaushistoria&vainomat=$vainomat&tila=kesken', false, false);\">".t("Keskeneräiset tilaukset")."</a>
					</td>
				</tr>
				<tr>
					<td class='back'><br></td>
				</tr>
				$tilaukset[kesken]

				<tr>
					<td class='back' colspan='5'>
						<a href=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=asiakastiedot&nayta=tilaushistoria&vainomat=$vainomat&tila=kasittely', false, false);\">".t("Tilaukset jotka odottaa käsittelyä")."</a>
					</td>
				</tr>
				<tr>
					<td class='back'><br></td>
				</tr>
				$tilaukset[kasittely]

				<tr>
					<td class='back' colspan='5'>
						<a href=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=asiakastiedot&nayta=tilaushistoria&vainomat=$vainomat&tila=odottaa', false, false);\">".t("Tilaukset jotka odottaa toimitusta")."</a>
					</td>
				</tr>
				<tr>
					<td class='back'><br></td>
				</tr>
				$tilaukset[odottaa]

				<tr>
					<td class='back' colspan='5'>
						<a href=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=asiakastiedot&nayta=tilaushistoria&vainomat=$vainomat&tila=toimituksessa', false, false);\">".t("Toimituksessa olevat tilaukset")."</a>
					</td>
				</tr>
				<tr>
					<td class='back'><br></td>
				</tr>
				$tilaukset[toimituksessa]

				<tr>
					<td class='back' colspan='5'>
						<a href=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=asiakastiedot&nayta=tilaushistoria&vainomat=$vainomat&tila=toimitettu', false, false);\">".t("Toimitetut tilaukset")."</a>
					</td>
				</tr>
				<tr>
					<td class='back'><br></td>
				</tr>
				$tilaukset[toimitettu]";
		}

		echo "</table>";
	}
}

if ($tee == "selaa") {

	$tuoteno = $toim_tuoteno = $nimitys = $alkukoodilla = "";

	if (isset($tuotehaku) and mb_detect_encoding($tuotehaku, mb_detect_order(), TRUE) == "UTF-8") {
		$tuotehaku = iconv("UTF-8", "latin1//TRANSLIT", $tuotehaku);

		$_GET["tuotehaku"]		= $tuotehaku;
		$_POST["tuotehaku"]		= $tuotehaku;
		$_REQUEST["tuotehaku"]	= $tuotehaku;
	}

	if (isset($tuotemerkki) and mb_detect_encoding($tuotemerkki, mb_detect_order(), TRUE) == "UTF-8") {
		$tuotemerkki = iconv("UTF-8", "latin1//TRANSLIT", $tuotemerkki);

		$_GET["tuotemerkki"]		= $tuotemerkki;
		$_POST["tuotemerkki"]		= $tuotemerkki;
		$_REQUEST["tuotemerkki"]	= $tuotemerkki;
	}

	if ($hakutapa != "" and $tuotehaku == "") {
		die ("<font class='error'>".t("Anna jokin hakukriteeri")."</font>");
	}
	elseif ($hakutapa == "alkukoodilla") {
		$tuotenumero = $tuotehaku;
		$alkukoodilla = "JOO";
	}
	elseif ($hakutapa == "koodilla") {
		$tuotenumero = $tuotehaku;
	}
	elseif ($hakutapa == "nimi") {
		$nimitys = $tuotehaku;
	}
	elseif ($hakutapa == "toim_tuoteno") {
		$toim_tuoteno = $tuotehaku;
	}

	if ($tuotemerkki != "") {
		$ojarj	= "sorttauskentta, tuote_wrapper.tuotemerkki IN ('$tuotemerkki') DESC";
	}
	else {
		$ojarj	= "tuote_wrapper.tuotemerkki";
	}

	if ($kukarow["kuka"] != "www" and (int) $kukarow["kesken"] == 0) {
		require_once("luo_myyntitilausotsikko.inc");
		$tilausnumero = luo_myyntitilausotsikko("EXTRANET", $kukarow["oletus_asiakas"], $tilausnumero, "", "", "", "VERKKOKAUPPA");
		$kukarow["kesken"] = $tilausnumero;
	}

	$submit_button = 1;

	if (stripos($_SERVER["HTTP_USER_AGENT"], "MSIE") === FALSE) {
		echo "<div class='livehaku' id='livehaku'>".t("Tuotehaku").": <form action='verkkokauppa.php?tee=selaa&hakutapa=koodilla' name='liveformi' id= 'liveformi'>".livesearch_kentta("liveformi", "TUOTEHAKU", "tuotenumero", 300)."</form></div>";
	}

	require("tuote_selaus_haku.php");
}

if ($tee == "") {

	enable_ajax();

	echo "<input type='hidden' id='osasto_js' value='{$osasto}' />";
	echo "<input type='hidden' id='tuoteryhma_js' value='{$try}' />";
	echo "<input type='hidden' id='tuotemerkki_js' value='{$tuotemerkki}' />";

	echo "	<script type='text/javascript'>

				$(function() {

					$('td.td_parent > a, td.td_parent_try > a, td.td_parent_tuotemerkki > a').live('click', function(e) {

						$('.selected').removeClass('selected');

						var id = $(this).attr('id');
						target_ja_id = id.split(\"_\", 3);

						$(this).addClass('selected');

						$('div[id^=\"P_'+target_ja_id[1]+'_\"]').each(function() {
							if ($(this).attr('id') != 'P_'+target_ja_id[1]+'_'+target_ja_id[2] && $(this).is(':visible')) {
								$(this).hide();
							}
						});

						if ($(this).attr('id') == 'P_'+target_ja_id[1] && $('#P_'+target_ja_id[1]).is(':visible')) {
							sndReq(\"selain\", \"verkkokauppa.php?tee=uutiset&osasto=\"+target_ja_id[1], \"\", false);
						}
					});

					var osasto = '';
					var tuoteryhma = '';
					var tuotemerkki = '';

					if ($('#osasto_js')) {
						osasto = $('#osasto_js').val();
						$('#osasto_js').val('');
					}

					if ($('#tuoteryhma_js')) {
						tuoteryhma = $('#tuoteryhma_js').val();
						$('#tuoteryhma_js').val('');
					}

					if ($('#tuotemerkki_js')) {
						tuotemerkki = $('#tuotemerkki_js').val();
						$('#tuotemerkki_js').val('');
					}

					if (osasto != '') {

						$('.selected').removeClass('selected');
						$('#P_'+osasto).addClass('selected');

						if (tuoteryhma == '' && tuotemerkki == '') {
							sndReq(\"selain\", \"verkkokauppa.php?tee=uutiset&osasto=\"+osasto, \"\", false);
						}

						sndReq(\"T_\"+osasto, \"verkkokauppa.php?tee=menu&osasto=\"+osasto, \"P_\"+osasto, false, false);
					}

					if (tuoteryhma != '') {

						$('.selected').removeClass('selected');

						sndReq('selain', 'verkkokauppa.php?tee=selaa&osasto='+osasto+'&try='+tuoteryhma+'&tuotemerkki='+tuotemerkki, '', false);
						sndReq('P_'+osasto+'_'+tuoteryhma, 'verkkokauppa.php?tee=menu&osasto='+osasto+'&try='+tuoteryhma, 'P_'+osasto+'_'+tuoteryhma, false);

						if (tuotemerkki == '') {
							setTimeout(function() {
								$('#T_'+osasto+'_'+tuoteryhma).addClass('selected');
							}, 200);
						}
					}

					if (tuotemerkki != '') {

						$('.selected').removeClass('selected');

						sndReq('selain', 'verkkokauppa.php?tee=selaa&osasto='+osasto+'&try='+tuoteryhma+'&tuotemerkki='+tuotemerkki, '', false);

						setTimeout(function() {
							$('#P_'+osasto+'_'+tuoteryhma+'_'+tuotemerkki).addClass('selected');
						}, 200);
					}
				});

			</script>";

	if ($kukarow["kuka"] == "www") {
		$login_screen = "<form name='login' id= 'loginform' method='post'>
						<input type='hidden' name='tee' value='login'>
						<input type='hidden' id = 'location' name='location' value='$palvelin2'>
						<font class='login'>".t("Käyttäjätunnus",$browkieli).":</font>
						<input type='text' value='' name='user' size='15' maxlength='30'>
						<font class='login'>".t("Salasana", $browkieli).":</font>
						<input type='password' name='salasana' size='15' maxlength='30'>
						<input type='submit' onclick='submit()' value='".t("Kirjaudu sisään", $browkieli)."'>
						</form>
						$errormsg
						";
	}
	else {
		$login_screen = "<input type='button' onclick=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=asiakastiedot&nayta=tilaushistoria&tila=haku&hakutapa=tila&tilaustila=kesken', false, false);\" value='".t("Avoimet tilaukset")."'>|<input type='button' onclick=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=asiakastiedot&nayta=tilaushistoria', false, false);\" value='".t("Tilaushistoria")."'>|<input type='button' onclick=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=asiakastiedot', false, false);\" value='".t("Asiakastiedot")."'>
			&nbsp;Tervetuloa, ".$kukarow["nimi"]."&nbsp;<input type='button' onclick=\"javascript:document.location='".$palvelin2."logout.php?location=".$palvelin2."';\" value='".t("Kirjaudu ulos")."'>";
	}

	$verkkokauppa_ulos =  "<div class='login' id='login'>$login_screen</div>
					<div class='menu' id='menu'>".menu()."</div>";

	$tuotenumero = mysql_real_escape_string(trim($_GET["tuotenumero"]));
	$tuotenimitys = mysql_real_escape_string(trim($_GET["tuotenimitys"]));

	if (stripos($_SERVER["HTTP_USER_AGENT"], "MSIE") === FALSE and ($verkkokauppa_anon or $kukarow["kuka"] != "www")) {
		$verkko = "<div class='livehaku' id='livehaku'>".t("Tuotehaku").": <form action='verkkokauppa.php?tee=selaa&hakutapa=koodilla' name='liveformi' id= 'liveformi'>".livesearch_kentta("liveformi", "TUOTEHAKU", "tuotenumero", 300)."</form></div>";
	}
	else {
		$verkko = "";
	}

	if ($tuotenumero != "") {
		$verkkokauppa_ulos .= "	<div class='selain' id='selain'>
								$verkko
								<script TYPE=\"text/javascript\" language=\"JavaScript\">
								sndReq('selain', 'verkkokauppa.php?tee=selaa&hakutapa=alkukoodilla&tuotehaku=$tuotenumero');
								</script>
								</div>";
	}
	elseif ($tuotenimitys != "") {
		$verkkokauppa_ulos .= "	<div class='selain' id='selain'>
								$verkko
								<script TYPE=\"text/javascript\" language=\"JavaScript\">
								sndReq('selain', 'verkkokauppa.php?tee=selaa&hakutapa=nimi&tuotehaku=$tuotenimitys');
								</script>
								</div>";
	}
	else {
		$verkkokauppa_ulos .= "<div class='selain' id='selain'>
								$verkko
								".uutiset('','',"ETUSIVU")."
								</div>";
	}

	if (file_exists("verkkokauppa.template")) {
		echo str_replace("<verkkokauppa>", $verkkokauppa_ulos, file_get_contents("verkkokauppa.template"));
	}
	else {
		echo "Verkkokauppapohjan määrittely puuttuu..<br>";
	}

	echo "</body></html>";
}
