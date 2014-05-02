<?php

	// Kutsutaanko CLI:st�
	$php_cli = FALSE;

	if (php_sapi_name() == 'cli') {
		$php_cli = TRUE;
	}

	if ($php_cli) {

		date_default_timezone_set('Europe/Helsinki');

		// otetaan includepath aina rootista
		ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(__FILE__)).PATH_SEPARATOR."/usr/share/pear");
		error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
		ini_set("display_errors", 0);

		// otetaan tietokanta connect
		require("inc/connect.inc");
		require("inc/functions.inc");

		if (trim($argv[1]) == '') {
			die ("Et antanut l�hett�v�� yhti�t�!\n");
		}

		if (trim($argv[1]) == '-help') {
			echo utf8_encode("\n
				\rCron-ajon parametrit\n
				\r1. yhti�\n
				\r2. l�hdevarastot pilkulla eroteltuna\n
				\r3. kohdevarasto\n
				\r4. toimitustavan selite-teksti\n
				\r5. l�hdevarastojen ker�ysvy�hykkeet pilkulla eroteltuna\n
				\r6. kohdevaraston ker�ysvy�hykkeet pilkulla eroteltuna\n
				\r7. monivalintalaatikon osaston tunnukset pilkulla eroteltuna\n
				\r8. monivalintalaatikon tuoteryhm�n tunnukset pilkulla eroteltuna\n
				\r9. monivalintalaatikon tuotemerkin tunnukset pilkulla eroteltuna\n
				\r10. toimittajan ytunnus\n
				\r11. ABC-luokkarajaus ja rajausperuste eroteltuna ##-merkill�\n
				\r12. J�t� siirtolista kesken, arvona X\n
				\r13. Siirr� my�s tuoteperheen lapsituotteet, arvona X\n
				\r14. Huomioi siirrett�v�n tuotteen myyntier�, arvona X\n
				\r15. Rivej� per siirtolista (tyhj� = 20)\n
				\r16. Ei siirret� jos tarve on suurempi tai yht� suuri kuin saatavilla oleva m��r�, arvona X\n
			");
			exit;
		}

		$yhtio = mysql_real_escape_string($argv[1]);

		$yhtiorow = hae_yhtion_parametrit($yhtio);

		if (trim($argv[2]) == '') {
			die ("Et antanut l�hdevarastoa!\n");
		}

		if (trim($argv[3]) == '') {
			die ("Et antanut kohdevarastoa!\n");
		}

		if (trim($argv[4]) == '') {
			die ("Et antanut toimitustapaa!\n");
		}

		pupesoft_flock();

		// Haetaan kukarow
		$query = "	SELECT *
					FROM kuka
					WHERE yhtio = '{$yhtio}'
					AND kuka = 'admin'";
		$kukares = pupe_query($query);

		if (mysql_num_rows($kukares) != 1) {
			exit("VIRHE: Admin k�ytt�j� ei l�ydy!\n");
		}

		$kukarow = mysql_fetch_assoc($kukares);
		$kukarow["kieli"] = $yhtiorow["kieli"];

		$lahdevarastot = explode(",", $argv[2]);
		$kohdevarasto = (int) $argv[3];
		$valittu_toimitustapa = mysql_real_escape_string(trim($argv[4]));
		$lahdekeraysvyohyke = (isset($argv[5]) and trim($argv[5]) != "") ? explode(",", $argv[5]) : array();
		$keraysvyohyke = (isset($argv[6]) and trim($argv[6]) != "") ? explode(",", $argv[6]) : array();
		$mul_osasto = (isset($argv[7]) and trim($argv[7]) != "") ? explode(",", $argv[7]) : array();
		$mul_try = (isset($argv[8]) and trim($argv[8]) != "") ? explode(",", $argv[8]) : array();
		$mul_tme = (isset($argv[9]) and trim($argv[9]) != "") ? explode(",", $argv[9]) : array();
		$toimittaja = (isset($argv[10]) and trim($argv[10]) != "") ? trim($argv[10]) : "";
		$abcrajaus = (isset($argv[11]) and trim($argv[11]) != "") ? trim($argv[11]) : "";
		$kesken = (isset($argv[12]) and trim($argv[12]) != "") ? "X" : "";
		$lapsituotteet = (isset($argv[13]) and trim($argv[13]) != "") ? "X" : "";
		$myyntiera = (isset($argv[14]) and trim($argv[14]) != "") ? "X" : "";
		$olliriveja = (isset($argv[15]) and trim($argv[15]) != "") ? (int) trim($argv[15]) : 20;
		$ei_siirreta_jos_tarve_ylittyy = (isset($argv[16]) and trim($argv[16]) != "") ? "X" : "";

		require ("tilauskasittely/monivalintalaatikot.inc");

		array_unshift($keraysvyohyke, 'default');
		array_unshift($lahdekeraysvyohyke, 'default');

		$generoi = "Tee siirtolista";
		$tee = 'M';
	}
	else {
		require ("../inc/parametrit.inc");

		echo "<font class='head'>",t("Luo siirtolista tuotepaikkojen h�lytysrajojen perusteella"),"</font><hr /><br />";
	}

	if (!isset($abcrajaus) or $abcrajaus == "") {
		$abcrajaus = "";
		$org_rajaus = "";
	}
	else {
		// org_rajausta tarvitaan yhdess� selectiss� joka trigger�i taas toisen asian.
		$org_rajaus = $abcrajaus;
		list($abcrajaus,$abcrajaustapa) = explode("##",$abcrajaus);
	}

	if (!isset($abcrajaustapa)) $abcrajaustapa = "TK";
	if (!isset($keraysvyohyke)) $keraysvyohyke = array();
	if (!isset($lahdekeraysvyohyke)) $lahdekeraysvyohyke = array();
	if (!isset($lapsituotteet)) $lapsituotteet = isset($_COOKIE["lapsituotteet"]) ? $_COOKIE["lapsituotteet"] : "";
	if (!isset($myyntiera)) $myyntiera = isset($_COOKIE["myyntiera"]) ? $_COOKIE["myyntiera"] : "";
	if (!isset($kesken)) $kesken = isset($_COOKIE["kesken"]) ? $_COOKIE["kesken"] : "";
	if (!isset($tee)) $tee = "";
	if (!isset($kohdevarasto)) $kohdevarasto = "";
	if (!isset($toimittaja)) $toimittaja = "";
	if (!isset($olliriveja)) $olliriveja = "";
	if (!isset($ei_siirreta_jos_tarve_ylittyy)) $ei_siirreta_jos_tarve_ylittyy = "";

	list($ryhmanimet, $ryhmaprossat, , , , ) = hae_ryhmanimet($abcrajaustapa);

	if (!$php_cli) {

		// T�ll� ollaan, jos olemme sy�tt�m�ss� tiedostoa ja muuta
		echo "<form name = 'valinta' method='post'>
				<input type='hidden' name='tee' value='M'>
				<table>";

		echo "<tr><th>",t("L�hdevarasto, eli varasto josta ker�t��n"),":</th>";
		echo "<td><table>";

		$query  = "	SELECT tunnus, nimitys, maa
					FROM varastopaikat
					WHERE yhtio = '{$kukarow['yhtio']}'
					ORDER BY tyyppi, nimitys";
		$vares = pupe_query($query);

		$kala = 0;

		while ($varow = mysql_fetch_assoc($vares)) {
			$sel = '';
			if (isset($lahdevarastot) and is_array($lahdevarastot) and in_array($varow['tunnus'], $lahdevarastot)) $sel = 'checked';

			$varastomaa = '';
			if ($varow['maa'] != "" and strtoupper($varow['maa']) != strtoupper($yhtiorow['maa'])) {
				$varastomaa = '(' . maa(strtoupper($varow['maa'])) . ')';
			}

			if ($kala == 0) echo "<tr>";

			echo "<td><input type='checkbox' name='lahdevarastot[]' value='{$varow['tunnus']}' {$sel} />{$varow['nimitys']} {$varastomaa}</td>";

			if ($kala == 3) {
				echo "</tr>";
				$kala = -1;
			}

			$kala++;
		}

		if ($kala != 0) {
			echo "</tr>";
		}

		echo "</table></td></tr>";

		if ($yhtiorow['kerayserat'] == 'K') {

			$query = "	SELECT nimitys, tunnus
						FROM keraysvyohyke
						WHERE yhtio = '{$kukarow['yhtio']}'";
			$keraysvyohyke_res = pupe_query($query);

			if (mysql_num_rows($keraysvyohyke_res) > 0) {

				echo "<tr><th>",t("Ker�ysvy�hyke josta ker�t��n"),"</th>";
				echo "<td>";
				echo "<input type='hidden' name='lahdekeraysvyohyke[]' value='default' />";
				echo "<table>";

				$kala = 0;

				while ($keraysvyohyke_row = mysql_fetch_assoc($keraysvyohyke_res)) {

					$chk = in_array($keraysvyohyke_row['tunnus'], $lahdekeraysvyohyke) ? " checked" : "";

					if ($kala == 0) echo "<tr>";

					echo "<td><input type='checkbox' name='lahdekeraysvyohyke[]' value='{$keraysvyohyke_row['tunnus']}'{$chk} /> {$keraysvyohyke_row['nimitys']}</td>";

					if ($kala == 3) {
						echo "</tr>";
						$kala = -1;
					}

					$kala++;
				}

				if ($kala != 0) {
					echo "</tr>";
				}

				echo "</table></td></tr>";
			}
		}

		echo "<tr><td class='back' colspan='2'><br></td></tr>";

		echo "<tr><th>",t("Kohdevarasto, eli varasto jonne l�hetet��n"),":</th>";
		echo "<td><select name='kohdevarasto'><option value=''>",t("Valitse"),"</option>";

		mysql_data_seek($vares, 0);

		while ($varow = mysql_fetch_assoc($vares)) {
			$sel = '';
			if ($varow['tunnus'] == $kohdevarasto) $sel = 'selected';

			$varastomaa = '';
			if ($varow['maa'] != "" and strtoupper($varow['maa']) != strtoupper($yhtiorow['maa'])) {
				$varastomaa = '(' . maa(strtoupper($varow['maa'])) . ')';
			}

			echo "<option value='{$varow['tunnus']}' {$sel}>{$varastomaa} {$varow['nimitys']}</option>";
		}

		echo "</select></td></tr>";

		if ($yhtiorow['kerayserat'] == 'K') {
			if (mysql_num_rows($keraysvyohyke_res) > 0) {
				mysql_data_seek($keraysvyohyke_res, 0);

				echo "<tr><th>",t("Ker�ysvy�hyke"),"</th>";
				echo "<td>";
				echo "<input type='hidden' name='keraysvyohyke[]' value='default' />";
				echo "<table>";

				$kala = 0;

				while ($keraysvyohyke_row = mysql_fetch_assoc($keraysvyohyke_res)) {

					$chk = in_array($keraysvyohyke_row['tunnus'], $keraysvyohyke) ? " checked" : "";

					if ($kala == 0) echo "<tr>";

					echo "<td><input type='checkbox' name='keraysvyohyke[]' value='{$keraysvyohyke_row['tunnus']}'{$chk} /> {$keraysvyohyke_row['nimitys']}</td>";

					if ($kala == 3) {
						echo "</tr>";
						$kala = -1;
					}

					$kala++;
				}

				if ($kala != 0) {
					echo "</tr>";
				}

				echo "</table></td></tr>";
			}
		}

		echo "<tr><td class='back' colspan='2'><br></td></tr>";

		echo "<tr><th>",t("Lis�rajaukset"),"</th><td>";

		$monivalintalaatikot = array("OSASTO", "TRY", "TUOTEMERKKI");
		$monivalintalaatikot_normaali = array();

		require ("tilauskasittely/monivalintalaatikot.inc");

		echo "</td></tr>";
		echo "<tr><th>",t("Toimittaja"),"</th><td><input type='text' size='20' name='toimittaja' value='{$toimittaja}'></td></tr>";

		echo "<tr><th>",t("ABC-luokkarajaus ja rajausperuste"),"</th><td>";

		echo "<select name='abcrajaus' onchange='submit()'>";
		echo "<option  value=''>",t("Valitse"),"</option>";

		$teksti = "";
		for ($i = 0; $i < count($ryhmaprossat); $i++) {
			$selabc = "";

			if ($i > 0) $teksti = t("ja paremmat");
			if ($org_rajaus == "{$i}##TM") $selabc = "SELECTED";

			echo "<option  value='{$i}##TM' {$selabc}>",t("Myynti"),": {$ryhmanimet[$i]} {$teksti}</option>";
		}

		$teksti = "";
		for ($i = 0; $i < count($ryhmaprossat); $i++) {
			$selabc = "";

			if ($i > 0) $teksti = t("ja paremmat");
			if ($org_rajaus == "{$i}##TK") $selabc = "SELECTED";

			echo "<option  value='{$i}##TK' {$selabc}>",t("Myyntikate"),": {$ryhmanimet[$i]} {$teksti}</option>";
		}

		$teksti = "";
		for ($i = 0; $i < count($ryhmaprossat); $i++) {
			$selabc = "";

			if ($i > 0) $teksti = t("ja paremmat");
			if ($org_rajaus == "{$i}##TR") $selabc = "SELECTED";

			echo "<option  value='{$i}##TR' {$selabc}>",t("Myyntirivit"),": {$ryhmanimet[$i]} {$teksti}</option>";
		}

		$teksti = "";
		for ($i = 0; $i < count($ryhmaprossat); $i++) {
			$selabc = "";

			if ($i > 0) $teksti = t("ja paremmat");
			if ($org_rajaus == "{$i}##TP") $selabc = "SELECTED";

			echo "<option  value='{$i}##TP' {$selabc}>",t("Myyntikappaleet"),": {$ryhmanimet[$i]} {$teksti}</option>";
		}

		echo "</select>";

		echo "<tr>";
		echo "<th>",t("Toimitustapa"),"</th><td>";

		$query = "	SELECT tunnus, selite
					FROM toimitustapa
					WHERE yhtio = '{$kukarow['yhtio']}'
					ORDER BY jarjestys, selite";
		$tresult = pupe_query($query);
		echo "<select name='valittu_toimitustapa'>";

		while ($row = mysql_fetch_assoc($tresult)) {
			echo "<option value='{$row['selite']}' {$sel}>",t_tunnus_avainsanat($row, "selite", "TOIMTAPAKV"),"</option>";
		}
		echo "</select>";


		echo "</td></tr>";

		if ($kesken == "X") {
			$c = "checked";
		}
		else {
			$c = "";
		}

		$lapsituote_chk = $lapsituotteet != "" ? "checked" : "";
		$myyntiera_chk = $myyntiera != "" ? "checked" : "";
		$ei_siirreta_jos_tarve_ylittyy_chk = $ei_siirreta_jos_tarve_ylittyy != "" ? "checked" : "";

		echo "<tr><th>",t("J�t� siirtolista kesken"),":</th><td><input type='checkbox' name = 'kesken' value='X' {$c}></td></tr>";
		echo "<tr><th>",t("Siirr� my�s tuoteperheen lapsituotteet"),":</th><td><input type='checkbox' name = 'lapsituotteet' value='X' {$lapsituote_chk}></td></tr>";
		echo "<tr><th>",t("Huomioi siirrett�v�n tuotteen myyntier�"),":</th><td><input type='checkbox' name = 'myyntiera' value='X' {$myyntiera_chk}></td></tr>";
		echo "<tr><th>",t("Rivej� per siirtolista (tyhj� = 20)"),":</th><td><input type='text' size='8' value='{$olliriveja}' name='olliriveja'></td></tr>";
		echo "<tr><th>",t("Ei siirret� jos tarve on suurempi tai yht� suuri kuin saatavilla oleva m��r�"),"</th>";
		echo "<td><input type='checkbox' name='ei_siirreta_jos_tarve_ylittyy' value='X' {$ei_siirreta_jos_tarve_ylittyy_chk} /></td></tr>";
		echo "</table><br><input type = 'submit' name = 'generoi' value = '",t("Generoi siirtolista"),"'></form>";
	}

	if ($tee == 'M' and isset($generoi)) {

		if (!$php_cli) echo "<br /><br />";

		$kohdevarasto = (int) $kohdevarasto;

		if ($kohdevarasto > 0 and count($lahdevarastot) > 0) {

			$abcjoin = "";
			$lahdevyohykkeet = FALSE;

			if ($abcrajaus != "") {
				// joinataan ABC-aputaulu katteen mukaan lasketun luokan perusteella
				$abcjoin = " JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (abc_aputaulu.yhtio = tuote.yhtio AND
							abc_aputaulu.tuoteno = tuote.tuoteno AND
							abc_aputaulu.tyyppi = '{$abcrajaustapa}' AND
							(abc_aputaulu.luokka <= '{$abcrajaus}' OR abc_aputaulu.luokka_osasto <= '{$abcrajaus}' OR abc_aputaulu.luokka_try <= '{$abcrajaus}'))";
			}

			if (count($lahdekeraysvyohyke) > 1) {

				// ensimm�inen alkio on 'default' ja se otetaan pois
				array_shift($lahdekeraysvyohyke);

				$query = "	SELECT distinct concat(varastopaikat.tunnus, '##', keraysvyohyke.tunnus) tunnari
							FROM varastopaikat
							JOIN keraysvyohyke ON (keraysvyohyke.yhtio = varastopaikat.yhtio AND keraysvyohyke.varasto = varastopaikat.tunnus AND keraysvyohyke.tunnus IN (".implode(",", $lahdekeraysvyohyke)."))
							WHERE varastopaikat.yhtio = '{$kukarow['yhtio']}'
							AND varastopaikat.tunnus IN (".implode(",", $lahdevarastot).")";
				$result = pupe_query($query);

				// Ylikirjataan $lahdevarastot-array
				$lahdevarastot = array();

				if (mysql_num_rows($result) > 0) {
					$lahdevyohykkeet = TRUE;

					while ($kvrow = mysql_fetch_assoc($result)) {
						$lahdevarastot[] = $kvrow['tunnari'];
					}
				}
			}

			if (count($keraysvyohyke) > 1) {

				// ensimm�inen alkio on 'default' ja se otetaan pois
				array_shift($keraysvyohyke);

				$keraysvyohykelisa = "	JOIN varaston_hyllypaikat AS vh ON (
										vh.yhtio = tuotepaikat.yhtio AND
										vh.hyllyalue = tuotepaikat.hyllyalue AND
										vh.hyllynro = tuotepaikat.hyllynro AND
										vh.hyllytaso = tuotepaikat.hyllytaso AND
										vh.hyllyvali = tuotepaikat.hyllyvali AND
										vh.keraysvyohyke IN (".implode(",", $keraysvyohyke)."))
										JOIN keraysvyohyke ON (keraysvyohyke.yhtio = vh.yhtio AND keraysvyohyke.tunnus = vh.keraysvyohyke)";
			}
			else {
				$keraysvyohykelisa = "";
			}

			if ($toimittaja != "") {
				$query = "	SELECT GROUP_CONCAT(DISTINCT CONCAT('\'',tuotteen_toimittajat.tuoteno,'\'')) tuotteet
							FROM toimi
							JOIN tuotteen_toimittajat ON toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.tunnus = tuotteen_toimittajat.liitostunnus
							WHERE toimi.yhtio = '{$kukarow['yhtio']}'
							AND toimi.ytunnus = '{$toimittaja}'";
				$result = pupe_query($query);
				$toimirow = mysql_fetch_assoc($result);

				if ($toimirow["tuotteet"] != "") {
					$lisa .= " AND tuote.tuoteno IN ({$toimirow["tuotteet"]}) ";
				}
				else {
					if (!$php_cli) echo "<font class='error'>",t("Toimittajaa ei l�ytynyt"),"! ",t("Ajetaan ajo ilman rajausta"),"!</font><br><br>";
				}
			}

			$query = "SELECT * FROM varastopaikat WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$kohdevarasto}'";
			$result = pupe_query($query);
			$varow = mysql_fetch_assoc($result);

			$kohdepaikkalisa = "";
			$varastonsisainensiirto = FALSE;

			// Siirret��nk� varaston sis�ll� tai osittain varaston sis�ll�?
			// T�ss� tapauksessa VAIN tuotteen oletuspaikka kelpaa kohdepaikaksi
			if (in_array($kohdevarasto, $lahdevarastot)) {
				$kohdepaikkalisa = " AND tuotepaikat.oletus != '' ";
				$varastonsisainensiirto = TRUE;
			}

			// Katotaan kohdepaikkojen tarvetta
			$query = "	SELECT tuotepaikat.*,
						tuotepaikat.halytysraja,
						if (tuotepaikat.tilausmaara = 0, 1, tuotepaikat.tilausmaara) tilausmaara,
						CONCAT_WS('-',tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) hyllypaikka,
						tuote.nimitys,
						if (tuote.myynti_era = 0, 1, tuote.myynti_era) myynti_era
						FROM tuotepaikat
						JOIN tuote ON (tuote.yhtio = tuotepaikat.yhtio AND tuote.tuoteno = tuotepaikat.tuoteno {$lisa})
						{$abcjoin}
						{$keraysvyohykelisa}
						WHERE tuotepaikat.yhtio = '{$kukarow['yhtio']}'
						AND CONCAT(RPAD(UPPER('{$varow['alkuhyllyalue']}'),  5, '0'),LPAD(UPPER('{$varow['alkuhyllynro']}'),  5, '0')) <= CONCAT(RPAD(UPPER(tuotepaikat.hyllyalue), 5, '0'),LPAD(UPPER(tuotepaikat.hyllynro), 5, '0'))
						AND CONCAT(RPAD(UPPER('{$varow['loppuhyllyalue']}'), 5, '0'),LPAD(UPPER('{$varow['loppuhyllynro']}'), 5, '0')) >= CONCAT(RPAD(UPPER(tuotepaikat.hyllyalue), 5, '0'),LPAD(UPPER(tuotepaikat.hyllynro), 5, '0'))
						AND tuotepaikat.halytysraja > 0
						{$kohdepaikkalisa}
						ORDER BY tuotepaikat.tuoteno";
			$resultti = pupe_query($query);

			if ((int) $olliriveja == 0 or $olliriveja == '') {
				$olliriveja = 20;
			}

			//	Otetaan luodut otsikot talteen
			$otsikot = array();

			// tehd��n jokaiselle valitulle lahdevarastolle erikseen
			foreach ($lahdevarastot as $lahdevarasto) {

				$lahdevyohyke = 0;

				if ($lahdevyohykkeet) {
					list($lahdevarasto, $lahdevyohyke) = explode("##", $lahdevarasto);
				}

				$lahdevarasto = (int) $lahdevarasto;
				$lahdevyohyke = (int) $lahdevyohyke;

				//	Varmistetaan ett� aloitetaan aina uusi otsikko uudelle varastolle
				$tehtyriveja = 0;

				// menn��n aina varmasti alkuun
				if (mysql_num_rows($resultti) > 0) mysql_data_seek($resultti, 0);

				while ($pairow = mysql_fetch_assoc($resultti)) {

					// katotaan paljonko t�lle PAIKALLE on menossa
					$query = "	SELECT SUM(tilausrivi.varattu) varattu
								FROM tilausrivi USE INDEX (yhtio_tyyppi_tuoteno_varattu)
								JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio AND tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus)
								JOIN lasku ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND lasku.clearing = '{$kohdevarasto}')
								WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
								AND tilausrivi.tuoteno = '{$pairow['tuoteno']}'
								AND tilausrivi.varattu > 0
								AND tilausrivi.tyyppi  = 'G'
								AND tilausrivin_lisatiedot.kohde_hyllyalue = '{$pairow['hyllyalue']}'
								AND tilausrivin_lisatiedot.kohde_hyllynro  = '{$pairow['hyllynro']}'
								AND tilausrivin_lisatiedot.kohde_hyllyvali = '{$pairow['hyllyvali']}'
								AND tilausrivin_lisatiedot.kohde_hyllytaso = '{$pairow['hyllytaso']}'";
					$vanres = pupe_query($query);
					$vanrow_paikalle = mysql_fetch_assoc($vanres);

					$menossa_paikalle = (float) $vanrow_paikalle["varattu"];

					// katotaan paljonko VARASTOON on menossa
					$query = "	SELECT SUM(tilausrivi.varattu) varattu
								FROM tilausrivi USE INDEX (yhtio_tyyppi_tuoteno_varattu)
								JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio AND tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus)
								JOIN lasku ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND lasku.clearing = '$kohdevarasto')
								WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
								AND tilausrivi.tuoteno = '{$pairow['tuoteno']}'
								AND tilausrivi.varattu > 0
								AND tilausrivi.tyyppi  = 'G'
								AND tilausrivin_lisatiedot.kohde_hyllyalue = ''
								AND tilausrivin_lisatiedot.kohde_hyllynro  = ''
								AND tilausrivin_lisatiedot.kohde_hyllyvali = ''
								AND tilausrivin_lisatiedot.kohde_hyllytaso = ''";
					$vanres = pupe_query($query);
					$vanrow_varastoon = mysql_fetch_assoc($vanres);

					$menossa_varastoon = (float) $vanrow_varastoon["varattu"];

					// Kohdepaikan myyt�viss�m��r�
					list( , , $saldo_myytavissa_kohde) = saldo_myytavissa($pairow["tuoteno"], "KAIKKI", $kohdevarasto, '', $pairow["hyllyalue"], $pairow["hyllynro"], $pairow["hyllyvali"], $pairow["hyllytaso"]);

					$tarve_kohdevarasto = $pairow['halytysraja']-$saldo_myytavissa_kohde-$menossa_paikalle-$menossa_varastoon;

					if ($tarve_kohdevarasto > 0) {

						if ($tarve_kohdevarasto <= $pairow['tilausmaara']) {
							$tarve_kohdevarasto = $pairow['tilausmaara'];
						}
						else {
							$kokonaisluku = round($tarve_kohdevarasto / $pairow['tilausmaara']);

							$test = $kokonaisluku * $pairow['tilausmaara'];

							if ($tarve_kohdevarasto > $test) {
								$test = ($kokonaisluku + 1) * $pairow['tilausmaara'];
							}

							$tarve_kohdevarasto = (float) $test;
						}

						if ($myyntiera == 'X') {
							$kokonaisluku = ceil($tarve_kohdevarasto / $pairow['myynti_era']);
							$test = $kokonaisluku * $pairow['myynti_era'];
							$tarve_kohdevarasto = (float) $test;
						}

					}

					if ($tarve_kohdevarasto <= 0) {
						continue;
					}

					// L�hdevaraston myyt�viss�m��r�
					if ($lahdevyohykkeet) {
						list( , , $saldo_myytavissa_lahde) = saldo_myytavissa($pairow["tuoteno"], "KAIKKI", $lahdevarasto."##".$lahdevyohyke);
					}
					else {
						list( , , $saldo_myytavissa_lahde) = saldo_myytavissa($pairow["tuoteno"], "KAIKKI", $lahdevarasto);
					}

					// jos l�hdevarasto on sama kuin kohdevarasto, niin silloin kohdepaikka on aina oletuspaikka, joten poistetaan sen myyt�viss�m��r� l�hdepuolelta
					if ($kohdevarasto == $lahdevarasto) {
						$saldo_myytavissa_lahde = (float) $saldo_myytavissa_lahde - $saldo_myytavissa_kohde;
					}
					else {
						$saldo_myytavissa_lahde = (float) $saldo_myytavissa_lahde;
					}

					#echo "TUOTENO: $kala $pairow[tuoteno]<br>";
					#echo "MENOSSA_PAIKALLE: $menossa_paikalle<br>";
					#echo "MENOSSA_VARASTOON: $menossa_varastoon<br>";
					#echo "MYYTAVISS�_KOHDE: $saldo_myytavissa_kohde<br>";
					#echo "H�LYRAJA_KOHDE: $pairow[halytysraja]<br>";
					#echo "TILAUSM��R�_KOHDE: $pairow[tilausmaara]<br>";
					#echo "TARVE: $tarve_kohdevarasto<br>";
					#echo "MYYTAVISS�_L�HDE: $saldo_myytavissa_lahde<br><br>";

					if ($saldo_myytavissa_lahde > 0 and $tarve_kohdevarasto > 0) {

						// Jos tarve on suurempi tai yht� suuri kuin saatavilla oleva m��r�
						if ($tarve_kohdevarasto >= $saldo_myytavissa_lahde) {

							if ($ei_siirreta_jos_tarve_ylittyy == "X") {
								$siirretaan = 0;
							}
							elseif ($saldo_myytavissa_lahde == 1) {
								$siirretaan = $saldo_myytavissa_lahde;
							}
							else {
								$siirretaan = floor($saldo_myytavissa_lahde / 2);
							}
						}
						else {
							$siirretaan = $tarve_kohdevarasto;
						}

						if ($siirretaan > 0) {

							//	Onko meill� jo otsikko vai pit��k� tehd� uusi?
							if ($tehtyriveja == 0 or $tehtyriveja == (int) $olliriveja+1) {

								// Nollataan kun tehd��n uusi otsikko
								$tehtyriveja = 0;

								$jatka = "kala";

								$query = "UPDATE kuka SET kesken = 0 WHERE yhtio = '{$kukarow['yhtio']}' and kuka = '{$kukarow['kuka']}'";
								$delresult = pupe_query($query);

								$kukarow["kesken"] = 0;

								$tilausnumero	= 0;
								$clearing 		= $kohdevarasto;
								$chn 			= 'GEN'; // t�ll� erotellaan "tulosta siirtolista"-kohdassa generoidut ja k�sin tehdyt siirtolistat
								$toimpp 		= $kerpp = date("j");
								$toimkk 		= $kerkk = date("n");
								$toimvv 		= $kervv = date("Y");
								$comments 		= $kukarow["nimi"]." ".t("Generoi h�lytysrajojen perusteella");
								$viesti 		= $kukarow["nimi"]." ".t("Generoi h�lytysrajojen perusteella");
								$varasto 		= $lahdevarasto;
								$toimitustapa 	= $valittu_toimitustapa;
								$toim			= "SIIRTOLISTA";

								require ("otsik_siirtolista.inc");

								$query = "	SELECT *
											FROM lasku
											WHERE tunnus = '{$kukarow['kesken']}'";
								$aresult = pupe_query($query);

								if (mysql_num_rows($aresult) == 0) {
									if (!$php_cli) echo "<font class='message'>",t("VIRHE: Tilausta ei l�ydy"),"!<br /><br /></font>";
									exit;
								}

								$query = "	SELECT nimitys
											FROM varastopaikat
											WHERE yhtio = '{$kukarow['yhtio']}'
											AND tunnus  = '{$lahdevarasto}'";
								$varres = pupe_query($query);
								$varrow = mysql_fetch_assoc($varres);

								if (!$php_cli) echo "<br /><font class='message'>",t("Tehtiin siirtolistalle otsikko %s l�hdevarasto on %s", $kieli, $kukarow["kesken"], $varrow["nimitys"]),"</font><br />";

								//	Otetaan luotu otsikko talteen
								$otsikot[] = $kukarow["kesken"];

								$laskurow = mysql_fetch_assoc($aresult);
							}

							$query = "	SELECT *
										FROM tuote
										WHERE tuoteno = '{$pairow['tuoteno']}'
										AND yhtio = '{$kukarow['yhtio']}'";
							$rarresult = pupe_query($query);

							if (mysql_num_rows($rarresult) == 1) {

								$trow = mysql_fetch_assoc($rarresult);

								$tuoteno_echo 		= $trow['tuoteno'];
								$yksikko_echo		= $trow['yksikko'];
								$toimaika 			= $laskurow["toimaika"];
								$kerayspvm			= $laskurow["kerayspvm"];
								$tuoteno			= $pairow["tuoteno"];
								$kpl				= $siirretaan;
								$jtkielto 			= $laskurow['jtkielto'];
								$varasto			= $lahdevarasto;
								$hinta 				= "";
								$netto 				= "";
								$var				= "";
								$korvaavakielto		= 1;
								$perhekielto		= $lapsituotteet == "" ? 1 : 0;
								$orvoteikiinnosta	= "EITOD";

								// Tallennetaan riville minne se on menossa
								$kohde_alue 	= $pairow["hyllyalue"];
								$kohde_nro 		= $pairow["hyllynro"];
								$kohde_vali 	= $pairow["hyllyvali"];
								$kohde_taso 	= $pairow["hyllytaso"];

								for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
									${'ale'.$alepostfix} = "";
								}

								require ('lisaarivi.inc');

								$tuoteno	= '';
								$kpl		= '';
								$hinta		= '';
								$alv		= 'X';
								$var		= '';
								$toimaika	= '';
								$kerayspvm	= '';
								$kommentti	= '';

								for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
									${'ale'.$alepostfix} = '';
								}

								$tehtyriveja++;

								if (!$php_cli) echo "<font class='info'>",t("Siirtolistalle lis�ttiin %s tuotetta %s", "", $siirretaan." ".$yksikko_echo, $tuoteno_echo),"</font><br />";

							}
							else {
								if (!$php_cli) echo t("VIRHE: Tuotetta ei l�ydy"),"!<br />";
							}
						}
					}
				}
			}

			if (!$php_cli) echo "</table><br />";

			if (count($otsikot) == 0) {
				if (!$php_cli) echo "<font class='error'>",t("Yht��n siirtolistaa ei luotu"),"!</font><br />";
			}
			else {
				if (!$php_cli) echo "<font class='message'>",t("Luotiin %s siirtolistaa", $kieli, count($otsikot)),"</font><br /><br /><br />";

				if ($kesken != "X") {
					foreach ($otsikot as $ots) {
						$query = "	SELECT *
									FROM lasku
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND tunnus = '{$ots}'";
						$aresult = pupe_query($query);
						$laskurow = mysql_fetch_assoc($aresult);

						$kukarow["kesken"]	= $laskurow["tunnus"];
						$toim 				= "SIIRTOLISTA";

						require ("tilaus-valmis-siirtolista.inc");
					}
				}
				else {
					if (!$php_cli) echo "<font class='message'>",t("Siirtolistat j�tettiin kesken"),"</font><br /><br /><br />";
				}
			}

			$query = "UPDATE kuka SET kesken = 0 WHERE yhtio = '{$kukarow['yhtio']}' and kuka = '{$kukarow['kuka']}'";
			$delresult = pupe_query($query);
		}
		else {
			if (!$php_cli) echo "<font class='error'>",t("Varastonvalinnassa on virhe"),"</font><br />";
		}
	}

	if (!$php_cli) require ("inc/footer.inc");
