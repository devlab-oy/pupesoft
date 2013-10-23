<?php

	require ("../inc/parametrit.inc");

	if ($tee != '' and $ytunnus != '') {

		// Vaihdetaan toimittaja
		if (isset($generoi) and $ed_ytunnus != $ytunnus) $toimittajaid = "";

		$ytunnus = mysql_real_escape_string($ytunnus);

		$muutparametrit = $tee."/".serialize($kohdevarastot)."/".$mul_osasto."/".$mul_try."/".$mul_tme."/".$abcrajaus."/".$generoi."/".$ohjausmerkki."/".$tilaustyyppi."/".$viesti;

		require ("inc/kevyt_toimittajahaku.inc");

		if ($toimittajaid == '') {
			$tee = '';
		}
		else {
			if ($muutparametrit != '') {
				list($tee, $kohdevarastot, $mul_osasto, $mul_try, $mul_tme, $abcrajaus, $generoi, $ohjausmerkki, $tilaustyyppi, $viesti) = explode("/", $muutparametrit);
				$kohdevarastot = unserialize($kohdevarastot);
			}
		}
	}

	echo "<font class='head'>",t("Luo ostotilaus tuotepaikkojen hälytysrajojen perusteella"),"</font><hr /><br />";

	// org_rajausta tarvitaan yhdessä selectissä joka triggeröi taas toisen asian.
	$org_rajaus = $abcrajaus;
	list($abcrajaus,$abcrajaustapa) = explode("##",$abcrajaus);

	if (!isset($abcrajaustapa)) $abcrajaustapa = "TK";
	if (!isset($keraysvyohyke)) $keraysvyohyke = array();

	list($ryhmanimet, $ryhmaprossat, , , , ) = hae_ryhmanimet($abcrajaustapa);

	// Tällä ollaan, jos olemme syöttämässä tiedostoa ja muuta
	echo "<form name = 'valinta' method='post'>
			<input type='hidden' name='tee' value='M'>
			<table>";

	echo "<tr><th>",t("Varasto johon tilataan"),"</th>";
	echo "<td><table>";

	$query  = "	SELECT tunnus, nimitys, maa
				FROM varastopaikat
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tyyppi != 'P'
				ORDER BY tyyppi, nimitys";
	$vares = pupe_query($query);

	$kala = 0;

	while ($varow = mysql_fetch_assoc($vares)) {
		$sel = '';
		if (is_array($kohdevarastot) and in_array($varow['tunnus'], $kohdevarastot)) $sel = 'checked';

		$varastomaa = '';
		if ($varow['maa'] != "" and strtoupper($varow['maa']) != strtoupper($yhtiorow['maa'])) {
			$varastomaa = '(' . maa(strtoupper($varow['maa'])) . ')';
		}

		if ($kala == 0) echo "<tr>";

		echo "<td><input type='checkbox' name='kohdevarastot[]' value='{$varow['tunnus']}' {$sel} />{$varow['nimitys']} {$varastomaa}</td>";

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

	echo "<tr><th>",t("Lisärajaukset"),"</th><td>";

	$monivalintalaatikot = array("OSASTO", "TRY", "TUOTEMERKKI");
	$monivalintalaatikot_normaali = array();

	require ("tilauskasittely/monivalintalaatikot.inc");

	echo "</td></tr>";

	if ($yhtiorow['kerayserat'] == 'K') {

		$query = "	SELECT nimitys, tunnus
					FROM keraysvyohyke
					WHERE yhtio = '{$kukarow['yhtio']}'";
		$keraysvyohyke_res = pupe_query($query);

		if (mysql_num_rows($keraysvyohyke_res) > 0) {

			echo "<tr><th>",t("Keräysvyöhyke"),"</th>";
			echo "<td>";

			echo "<input type='hidden' name='keraysvyohyke[]' value='default' />";

			while ($keraysvyohyke_row = mysql_fetch_assoc($keraysvyohyke_res)) {

				$chk = in_array($keraysvyohyke_row['tunnus'], $keraysvyohyke) ? " checked" : "";

				echo "<input type='checkbox' name='keraysvyohyke[]' value='{$keraysvyohyke_row['tunnus']}'{$chk} /> {$keraysvyohyke_row['nimitys']}<br />";
			}

			echo "</td></tr>";
		}
	}

	echo "<tr><th>",t("Toimittaja"),"</th><td><input type='text' size='20' name='ytunnus' value='{$ytunnus}'>";

	if ($toimittajaid > 0) {
		echo "	<input type='hidden' name='ed_ytunnus' value='{$ytunnus}'>
				<input type='hidden' name='toimittajaid' value='{$toimittajaid}'>
				".t("Valittu toimittaja").":  {$toimittajarow['nimi']} {$toimittajarow['nimitark']}";
	}

	echo "</td></tr>";

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
	echo "</td></tr>";

	echo "<tr><td class='back'><br></td></tr>";

	echo "<tr><th>".t("Viite")."</th><td><input type='text' size='61' name='viesti' value='$viesti'></td></tr>";
	echo "<tr><th>".t("Ohjausmerkki")."</th><td><input type='text' size='61' name='ohjausmerkki' value='$ohjausmerkki'></td></tr>";
	echo "<tr><th>".t("Tilaustyyppi")."</th>";

	echo "<td><select name='tilaustyyppi'>";

	$ostotil_tiltyyp_res = t_avainsana("OSTOTIL_TILTYYP");

	if (mysql_num_rows($ostotil_tiltyyp_res) > 0) {

		while ($ostotil_tiltyyp_row = mysql_fetch_assoc($ostotil_tiltyyp_res)) {
			$sel = $tilaustyyppi == $ostotil_tiltyyp_row['selite'] ? " selected" : "";
			echo "<option value='{$ostotil_tiltyyp_row['selite']}'{$sel}>{$ostotil_tiltyyp_row['selitetark']}</option>";
		}
	}
	else {

		$sel = array($tilaustyyppi => "selected") + array(1 => '', 2 => '');

		echo "<option value='2' {$sel[2]}>",t("Normaalitilaus"),"</option>";
		echo "<option value='1' {$sel[1]}>",t("Pikalähetys"),"</option>";
	}

	echo "</select></td>";


	echo "</tr>";

	echo "</table><br><input type = 'submit' name = 'generoi' value = '",t("Generoi ostotilaus"),"'></form>";

	if ($tee == 'M' and isset($generoi) and $toimittajaid > 0 and count($kohdevarastot) > 0) {

		require ("inc/luo_ostotilausotsikko.inc");

		$abcjoin = "";

		if ($abcrajaus != "") {
			// joinataan ABC-aputaulu katteen mukaan lasketun luokan perusteella
			$abcjoin = " JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (abc_aputaulu.yhtio = tuote.yhtio AND
						abc_aputaulu.tuoteno = tuote.tuoteno AND
						abc_aputaulu.tyyppi = '{$abcrajaustapa}' AND
						(abc_aputaulu.luokka <= '{$abcrajaus}' OR abc_aputaulu.luokka_osasto <= '{$abcrajaus}' OR abc_aputaulu.luokka_try <= '{$abcrajaus}'))";
		}

		if (count($keraysvyohyke) > 1) {

			// ensimmäinen alkio on 'default' ja se otetaan pois
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

		// Jos jt-rivit varaavat saldoa niin se vaikuttaa asioihin
		if ($yhtiorow["varaako_jt_saldoa"] != "") {
			$lisavarattu = " + tilausrivi.varattu";
		}
		else {
			$lisavarattu = "";
		}

		// Otetaan luodut otsikot talteen
		$otsikot = array();

		// tehdään jokaiselle valitulle lahdevarastolle erikseen
		foreach ($kohdevarastot as $kohdevarasto) {

			$query = "	SELECT *
						FROM varastopaikat
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus  = '{$kohdevarasto}'";
			$result = pupe_query($query);
			$varow = mysql_fetch_assoc($result);

			// Katotaan kohdepaikkojen tarvetta
			$query = "	SELECT tuotepaikat.*,
						if (tuotepaikat.tilausmaara = 0, 1, tuotepaikat.tilausmaara) tilausmaara,
						if (tuotteen_toimittajat.osto_era = 0, 1, tuotteen_toimittajat.osto_era) osto_era
						FROM tuotepaikat
						JOIN tuote ON (tuote.yhtio = tuotepaikat.yhtio AND tuote.tuoteno = tuotepaikat.tuoteno {$lisa})
						JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tuote.yhtio AND tuotteen_toimittajat.tuoteno = tuote.tuoteno AND tuotteen_toimittajat.liitostunnus = '{$toimittajaid}')
						{$abcjoin}
						{$keraysvyohykelisa}
						WHERE tuotepaikat.yhtio = '{$kukarow['yhtio']}'
						AND CONCAT(RPAD(UPPER('{$varow['alkuhyllyalue']}'),  5, '0'),LPAD(UPPER('{$varow['alkuhyllynro']}'),  5, '0')) <= CONCAT(RPAD(UPPER(tuotepaikat.hyllyalue), 5, '0'),LPAD(UPPER(tuotepaikat.hyllynro), 5, '0'))
						AND CONCAT(RPAD(UPPER('{$varow['loppuhyllyalue']}'), 5, '0'),LPAD(UPPER('{$varow['loppuhyllynro']}'), 5, '0')) >= CONCAT(RPAD(UPPER(tuotepaikat.hyllyalue), 5, '0'),LPAD(UPPER(tuotepaikat.hyllynro), 5, '0'))
						AND tuotepaikat.halytysraja > 0
						ORDER BY tuotepaikat.tuoteno";
			$resultti = pupe_query($query);

			//	Varmistetaan että aloitetaan aina uusi otsikko uudelle varastolle
			$tehtyriveja = 0;

			while ($pairow = mysql_fetch_assoc($resultti)) {

				//tilauksessa, ennakkopoistot ja jt	HUOM: varastolisa määritelty jo aiemmin!
				$query = "	SELECT
							sum(if(tyyppi in ('W','M'), varattu, 0)) valmistuksessa,
							sum(if(tyyppi = 'O', varattu, 0)) tilattu,
							sum(if(tyyppi = 'E', varattu, 0)) ennakot,
							sum(if(tyyppi in ('L','V') and var not in ('P','J','O','S'), varattu, 0)) ennpois,
							sum(if(tyyppi in ('L','G') and var in ('J','S'), jt $lisavarattu, 0)) jt
							FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
							WHERE yhtio 		= '{$kukarow['yhtio']}'
		 					AND tyyppi in ('L','V','O','G','E','W','M')
							AND tuoteno 		= '{$pairow['tuoteno']}'
							AND laskutettuaika 	= '0000-00-00'
							AND hyllyalue 		= '{$pairow['hyllyalue']}'
							AND hyllynro 		= '{$pairow['hyllynro']}'
							AND hyllyvali 		= '{$pairow['hyllyvali']}'
							AND hyllytaso 		= '{$pairow['hyllytaso']}'
							AND (varattu+jt > 0)";
				$result = pupe_query($query);
				$ennp   = mysql_fetch_assoc($result);

				$ostettavahaly = ($pairow['halytysraja'] - ($pairow['saldo'] + $ennp['tilattu'] + $ennp['valmistuksessa'] - $ennp['ennpois'] - $ennp['jt'])) / $pairow['osto_era'];

				if ($ostettavahaly > 0)	$ostettavahaly = ceil($ostettavahaly) * $pairow['osto_era'];
				else $ostettavahaly = 0;

				if ($ostettavahaly > 0 and $ostettavahaly <= $pairow['tilausmaara']) {
					$ostettavahaly = $pairow['tilausmaara'];
				}

				if ($ostettavahaly <= 0) {
					continue;
				}

				/*
				echo "<br>Tuoteno: $pairow[tuoteno]<br>";
				echo "Hälytysraja: {$pairow['halytysraja']}<br>";
				echo "Saldo: {$pairow['saldo']}<br>";
				echo "Tilattu: {$ennp['tilattu']}<br>";
				echo "Valmistuksessa: {$ennp['valmistuksessa']}<br>";
				echo "Varattu: {$ennp['ennpois']}<br>";
				echo "Jt: {$ennp['jt']}<br>";
				echo "Osto_erä: {$pairow['osto_era']}<br>";
				echo "Tilausmäärä: {$pairow['tilausmaara']}<br>";
				echo "Tarve: $ostettavahaly<br>";
				*/

				//	Onko meillä jo otsikko vai pitääkö tehdä uusi?
				if ($tehtyriveja == 0) {

					// Nollataan kun tehdään uusi otsikko
					$tehtyriveja = 0;

					$query = "UPDATE kuka SET kesken = 0 WHERE yhtio = '{$kukarow['yhtio']}' and kuka = '{$kukarow['kuka']}'";
					$delresult = pupe_query($query);

					$kukarow["kesken"] = 0;

					$params = array(
						'liitostunnus' 				=> $toimittajaid,
						'nimi' 						=> $varow['nimi'],
						'nimitark' 					=> $varow['nimitark'],
						'osoite' 					=> $varow['osoite'],
						'postino' 					=> $varow['postino'],
						'postitp' 					=> $varow['postitp'],
						'maa' 						=> $varow['maa'],
						'myytil_toimaika'			=> date("Y-m-d"),
						'toimipaikka' 				=> $varow['toimipaikka'],
						'varasto'	 				=> $kohdevarasto,
						'ohjausmerkki'	 			=> $ohjausmerkki,
						'tilaustyyppi'	 			=> $tilaustyyppi,
						'myytil_viesti'				=> $viesti,
						'ostotilauksen_kasittely'	=> "GEN", # tällä erotellaan generoidut ja käsin tehdyt ostotilaukset
					);

					$laskurow = luo_ostotilausotsikko($params);

					$query = "UPDATE kuka SET kesken = {$laskurow['tunnus']} WHERE yhtio = '{$kukarow['yhtio']}' and kuka = '{$kukarow['kuka']}'";
					$delresult = pupe_query($query);

					$kukarow['kesken'] = $laskurow['tunnus'];

					echo "<br /><font class='message'>",t("Tehtiin ostotilaus otsikko %s kohdevarasto on %s", $kieli, $kukarow["kesken"], $varow["nimitys"]),"</font><br />";

					//	Otetaan luotu otsikko talteen
					$otsikot[] = $kukarow["kesken"];
				}

				$query = "	SELECT *
							FROM tuote
							WHERE tuoteno = '{$pairow['tuoteno']}'
							AND yhtio = '{$kukarow['yhtio']}'";
				$rarresult = pupe_query($query);

				if (mysql_num_rows($rarresult) == 1) {
					$trow = mysql_fetch_assoc($rarresult);
					$toimaika 			= $laskurow["toimaika"];
					$kerayspvm			= $laskurow["kerayspvm"];
					$tuoteno			= $pairow["tuoteno"];
					$kpl				= $ostettavahaly;
					$varasto			= $kohdevarasto;
					$hinta 				= "";
					$netto 				= "";
					$var				= "";
					$paikka 			= "$pairow[hyllyalue]#!¡!#$pairow[hyllynro]#!¡!#$pairow[hyllyvali]#!¡!#$pairow[hyllytaso]";

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

					echo "<font class='info'>",t("Ostotilaukselle lisättiin %s tuotetta %s", "", $ostettavahaly." ".$trow["yksikko"], $trow["tuoteno"]),"</font><br />";
				}
				else {
					echo t("VIRHE: Tuotetta ei löydy"),"!<br />";
				}
			}
		}

		echo "</table><br />";

		if (count($otsikot) == 0) {
			echo "<br><font class='error'>",t("Yhtään ostotilausta ei luotu"),"!</font><br />";
		}
		else {
			echo "<font class='message'>",t("Luotiin %s ostotilausta", $kieli, count($otsikot)),"</font><br /><br /><br />";
		}

		$query = "UPDATE kuka SET kesken = 0 WHERE yhtio = '{$kukarow['yhtio']}' and kuka = '{$kukarow['kuka']}'";
		$delresult = pupe_query($query);
	}
	elseif ($tee == 'M' and isset($generoi)) {
		echo "<br><br><font class='error'>".t("VIRHE: Valitse toimittaja ja varasto johon tilataan")."!</font>";
	}

	require ("inc/footer.inc");
