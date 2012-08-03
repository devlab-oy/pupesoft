<?php

$pupe_DataTables = 'keikka';

if (strpos($_SERVER['SCRIPT_NAME'], "keikka.php")  !== FALSE) {
	require ("../inc/parametrit.inc");
}

if (isset($_POST['ajax_toiminto']) and trim($_POST['ajax_toiminto']) != '') {
	require ("../inc/keikan_toiminnot.inc");
}

if (isset($livesearch_tee) and $livesearch_tee == "TUOTEHAKU") {
	livesearch_tuotehaku();
	exit;
}

if (!function_exists("tsekit")) {
	function tsekit($row, $kaikkivarastossayhteensa, $toimittajaid) {

		global $kukarow, $yhtiorow;

		$tsekit = array();

		$query_ale_lisa = generoi_alekentta('O');

		// haetaan keikan tiedot
		$query    = "SELECT * FROM lasku WHERE tunnus = '{$row['tunnus']}' AND yhtio = '{$kukarow['yhtio']}'";
		$result   = pupe_query($query);
		$laskurow = mysql_fetch_assoc($result);

		// katsotaan onko tälle keikalle jo liitetty vaihto-omaisuuslaskuja (kotimaa, eu tai ei-eu)
		$query = "	SELECT sum(summa) summa, sum(arvo) arvo, sum(abs(summa)) abssumma, valkoodi, vienti
					FROM lasku
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tila = 'K'
					AND laskunro = '{$laskurow['laskunro']}'
					AND vanhatunnus <> 0
					AND vienti IN ('C','F','I','J','K','L')
					GROUP BY valkoodi, vienti";
		$result = pupe_query($query);

		// jos on, haetaan liitettyjen laskujen
		if (mysql_num_rows($result) == 1) {
			$kulurow = mysql_fetch_assoc($result);
		}
		else {
			$kulurow = array("vienti" => "", "summa" => 0, "arvo" => 0, "valkoodi" => ""); // muuten tyhjää
		}

		// jos kysessä on kotimainen vaihto-omaisuuslasku, pitää lisätä tuotteen hintaan alvi, jos ostolaskuilla on alvia!
		if ($laskurow['vienti'] == 'C' or $laskurow['vienti'] == 'J') {

			if ($kulurow["arvo"] != 0) $simualv = round(100 * (($kulurow["summa"]/$kulurow["arvo"])-1),2);
			else $simualv = 0;

			if ($kulurow["arvo"] > 0 and $simualv == 0) {
				$alvit = 0;
			}
			elseif (in_array($simualv, array(8,12,17,22))) {
				$alvit = $simualv;
			}
			else {
				$alvit = "if(tuotteen_toimittajat.osto_alv >= 0, tuotteen_toimittajat.osto_alv, tuote.alv)";
			}

			if ($laskurow["maa"] != "" and $laskurow["maa"] != $yhtiorow["maa"]) {
				// tutkitaan ollaanko siellä alv-rekisteröity
				$alhqur = "SELECT * FROM yhtion_toimipaikat WHERE yhtio = '{$kukarow['yhtio']}' AND maa = '{$laskurow['maa']}' AND vat_numero != ''";
				$alhire = pupe_query($alhqur);

				// ollaan alv-rekisteröity
				if (mysql_num_rows($alhire) == 1) {
					$alvit = "tuotteen_alv.alv";
				}
			}
		}
		else {
			$alvit = 0;
		}

		// tutkitaan onko kaikilla tuotteilla on joku varastopaikka
		$query  = "	SELECT tilausrivi.*,
					(tilausrivi.varattu + tilausrivi.kpl) * IF(tuotteen_toimittajat.tuotekerroin <= 0 OR tuotteen_toimittajat.tuotekerroin IS NULL, 1, tuotteen_toimittajat.tuotekerroin) * tilausrivi.hinta * {$query_ale_lisa} *
					(1 + (IF((SELECT MAX(kaytetty) kaytetty
						FROM sarjanumeroseuranta
						WHERE sarjanumeroseuranta.yhtio = tilausrivi.yhtio
						AND sarjanumeroseuranta.tuoteno = tilausrivi.tuoteno
						AND ((tilausrivi.varattu + tilausrivi.kpl < 0 AND sarjanumeroseuranta.myyntirivitunnus = tilausrivi.tunnus) OR (tilausrivi.varattu + tilausrivi.kpl > 0 AND sarjanumeroseuranta.ostorivitunnus = tilausrivi.tunnus))) = 'K', 0, {$alvit}) / 100)) rivihinta
					FROM tilausrivi USE INDEX (uusiotunnus_index)
					JOIN tuote use index (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno and tuote.ei_saldoa = '')
					LEFT JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno AND tuotteen_toimittajat.liitostunnus = '{$toimittajaid}')
					WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
					AND tilausrivi.uusiotunnus = '{$row['tunnus']}'
					AND tilausrivi.tyyppi = 'O'";
		$tilres = pupe_query($query);

		$kplyhteensa = 0;  	// apumuuttuja
		$kplvarasto  = 0;  	// apumuuttuja
		$eipaikkoja  = 0;  	// apumuuttuja
		$eituotteet  = ""; 	// apumuuttuja
		$varastossaarvo = 0; // apumuuttuja
		$uusiot = array();

		while ($rivirow = mysql_fetch_assoc($tilres)) {
			$query = "SELECT * from tuote where tuoteno='$rivirow[tuoteno]' and yhtio='$kukarow[yhtio]'";
			$tuore = pupe_query($query);
			$tuote = mysql_fetch_assoc($tuore);

			if (!in_array($rivirow["otunnus"], $uusiot)) {
				$uusiot[] = $rivirow["otunnus"];
			}

			$kplyhteensa++; // lasketaan montako tilausriviä on kohdistettu

			$varastossaarvo += $rivirow["rivihinta"];
			$kaikkivarastossayhteensa += $rivirow["rivihinta"];

			if (($rivirow["kpl"] != 0 and $rivirow["varattu"] == 0) or ($rivirow["kpl"] == 0 and $rivirow["varattu"] == 0)) {
				$kplvarasto++; // lasketaan montako tilausriviä on viety varastoon
			}

			// jos kyseessä on saldollinen tuote
			if ($tuote['ei_saldoa'] == "") {
				//jos rivi on jo viety varastoon niin ei enää katota sen paikkaa
				if ($rivirow["kpl"] == 0 and $rivirow["varattu"] != 0) {
					// katotaan löytyykö tuotteelta varastopaikka joka on tilausriville tallennettu
					$query = "	SELECT *
								from tuotepaikat use index (tuote_index)
								where tuoteno='$rivirow[tuoteno]' and yhtio='$kukarow[yhtio]' and hyllyalue='$rivirow[hyllyalue]' and hyllynro='$rivirow[hyllynro]' and hyllytaso='$rivirow[hyllytaso]' and hyllyvali='$rivirow[hyllyvali]'";
					$tpres = pupe_query($query);

					if (mysql_num_rows($tpres)==0) {
						$eipaikkoja++;
					}
				}
			}
		}

		if ($varastossaarvo != 0) {
			$varastossaarvo = "(".round($varastossaarvo,2).")";
		}
		else {
			$varastossaarvo = "";
		}

		if ($eipaikkoja == 0 and $kplyhteensa > 0) {
			$varok = 1;
			$varastopaikat = "<font class='ok'>".t("ok")."</font>";
		}
		else {
			$varok = 0;
			$varastopaikat = t("kesken");
		}

		// tutkitaan onko kaikki lisätiedot syötetty vai ei...
		$query = "	SELECT *
					from lasku use index (PRIMARY)
					where yhtio	= '$kukarow[yhtio]'
					and tunnus	= '$row[tunnus]'
					and maa	   != '$yhtiorow[maa]'
					and (maa_maara = '' or maa_lahetys = '' or bruttopaino = '' or kauppatapahtuman_luonne <= 0 or kuljetusmuoto = '' or toimaika = '0000-00-00')
					and kauppatapahtuman_luonne != '999'";
		$okres = pupe_query($query);

		if (mysql_num_rows($okres) == 0) {
			$lisok = 1;
			$lisatiedot = "<font class='ok'>".t("ok")."</font>";
		}
		else {
			$lisok = 0;
			$lisatiedot = t("kesken");
		}

		// katotaan onko kohdistus tehty pennilleen
		if ($row["kohdistettu"] == 'K') {
			$kohok = 1;
			$kohdistus = "<font class='ok'>".t("ok")."</font>";
		}
		else {
			$kohok = 0;
			$kohdistus = t("kesken");
		}

		// katotaan onko kaikki sarjanumerot ok
		$query = "	SELECT tilausrivi.tunnus, tilausrivi.tuoteno, tilausrivi.varattu+tilausrivi.kpl kpl, tuote.sarjanumeroseuranta, tilausrivi.uusiotunnus
					FROM tilausrivi use index (uusiotunnus_index)
					JOIN tuote on tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta!=''
					WHERE tilausrivi.yhtio='$kukarow[yhtio]' and
					tilausrivi.uusiotunnus='$row[tunnus]' and
					tilausrivi.tyyppi='O'";
		$toimresult = pupe_query($query);

		$sarjanrook = 1;

		while ($toimrow = mysql_fetch_assoc($toimresult)) {

			if ($toimrow["kpl"] < 0) {
				$tunken = "myyntirivitunnus";
			}
			else {
				$tunken = "ostorivitunnus";
			}

			if ($toimrow["sarjanumeroseuranta"] == "S" or $toimrow["sarjanumeroseuranta"] == "U" or $toimrow["sarjanumeroseuranta"] == "V") {
				$query = "	SELECT count(distinct sarjanumero) kpl, min(sarjanumero) sarjanumero
							FROM sarjanumeroseuranta
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$toimrow[tuoteno]'
							and $tunken = '$toimrow[tunnus]'";
			}
			else {
				$query = "	SELECT sum(abs(era_kpl)) kpl, min(sarjanumero) sarjanumero
							FROM sarjanumeroseuranta
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$toimrow[tuoteno]'
							and $tunken = '$toimrow[tunnus]'";
			}
			$sarjares = pupe_query($query);
			$sarjarow = mysql_fetch_assoc($sarjares);

			// pitää olla yhtämonta sarjanumeroa liitettynä kun kamaa viety varastoon
			if ($sarjarow["kpl"] != abs($toimrow["kpl"])) {
				$sarjanrook++;
			}
		}

		// pitää olla yhtämonta sarjanumeroa liitettynä kun kamaa viety varastoon
		if ($sarjanrook == 1) {
			$sarjanrook	= 1;
			$sarjanrot	= "<font class='ok'>".t("ok")."</font>";
		}
		else {
			$sarjanrook	= 0; // ei ole kaikilla tuotteilla sarjanumeroa
			$sarjanrot	= "kesken";
		}

		// katotaan onko liitettyjä laskuja
		// ('C','F','I','J','K','L') // vaihto-omaisuus ja raaka-aine
		// ('B','C','J','E','F','K','H','I','L') // kaikki

		$query = "	SELECT count(*) num,
					sum(if(lasku.vienti='C' or lasku.vienti='F' or lasku.vienti='I' or lasku.vienti='J' or lasku.vienti='K' or lasku.vienti='L', 1, 0)) volasku,
					sum(if(ostores_lasku.tila != 'H' and (lasku.vienti='C' or lasku.vienti='F' or lasku.vienti='I' or lasku.vienti='J' or lasku.vienti='K' or lasku.vienti='L'), 1, 0)) volasku_ok,
					sum(if(lasku.vienti!='C' and lasku.vienti!='F' and lasku.vienti!='I' and lasku.vienti!='J' and lasku.vienti!='K' and lasku.vienti!='L', 1, 0)) kulasku,
					sum(if(ostores_lasku.tila != 'H' and lasku.vienti!='C' and lasku.vienti!='F' and lasku.vienti!='I' and lasku.vienti!='J' and lasku.vienti!='K' and lasku.vienti!='L',1,0)) kulasku_ok,
					round(sum(if(lasku.vienti='C' or lasku.vienti='F' or lasku.vienti='I' or lasku.vienti='J' or lasku.vienti='K' or lasku.vienti='L', lasku.summa * lasku.vienti_kurssi, 0)),2) vosumma,
					round(sum(if(lasku.vienti='C' or lasku.vienti='F' or lasku.vienti='I' or lasku.vienti='J' or lasku.vienti='K' or lasku.vienti='L', lasku.summa, 0)),2) vosumma_valuutassa,
					round(sum(if(lasku.vienti!='C' and lasku.vienti!='F' and lasku.vienti!='I' and lasku.vienti!='J' and lasku.vienti!='K' and lasku.vienti!='L', lasku.arvo * lasku.vienti_kurssi, 0)),2) kusumma,
					round(sum(if(lasku.vienti!='C' and lasku.vienti!='F' and lasku.vienti!='I' and lasku.vienti!='J' and lasku.vienti!='K' and lasku.vienti!='L', lasku.arvo, 0)),2) kusumma_valuutassa
					from lasku use index (yhtio_tila_laskunro)
					JOIN lasku ostores_lasku on (ostores_lasku.yhtio = lasku.yhtio and ostores_lasku.tunnus = lasku.vanhatunnus)
					where lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila = 'K'
					and lasku.vanhatunnus <> 0
					and lasku.laskunro = '$row[laskunro]'";
		$llres = pupe_query($query);
		$llrow = mysql_fetch_assoc($llres);

		if ((abs($row['rahti_etu']) > abs($llrow['vosumma_valuutassa'])) and $llrow['volasku'] > 0) {
			$lisok = 0;
			$lisatiedot = t("kesken");
		}

		// $kaikkivarastossayhteensa,$kohdistus,$kohok,$kplvarasto,$kplyhteensa,$lisatiedot,$lisok,$llrow,$sarjanrook,$sarjanrot,$uusiot,$varastopaikat,$varastossaarvo,$varok
		return array($kaikkivarastossayhteensa,$kohdistus,$kohok,$kplvarasto,$kplyhteensa,$lisatiedot,$lisok,$llrow,$sarjanrook,$sarjanrot,$uusiot,$varastopaikat,$varastossaarvo,$varok);
	}
}

if (!isset($toiminto)) $toiminto = "";
if (!isset($keikkarajaus)) $keikkarajaus = "";

echo "<font class='head'>".t("Saapumiset")."</font><hr>";

if ($yhtiorow["livetuotehaku_tilauksella"] == "K") {
	enable_ajax();
}

// scripti balloonien tekemiseen
js_popup();

echo "<div id='toimnapit'></div>";

if (isset($nappikeikalle) and $nappikeikalle == 'menossa') {
	$query = "UPDATE kuka SET kesken = 0 where yhtio = '$kukarow[yhtio]' and kuka = '$kukarow[kuka]'";
	$nappiresult = pupe_query($query);
}

// yhdistetaan saapumiseen $otunnus muita keikkoja
if ($toiminto == "yhdista") {
	require('ostotilausten_rivien_yhdistys.inc');
}

// poistetaan vanha saapuminen numerolla $keikkaid
if ($toiminto == "poista") {
	$eisaapoistaa = 0;

	$query  = "	SELECT tunnus
				from tilausrivi
				where yhtio = '$kukarow[yhtio]'
				and uusiotunnus = '$tunnus'
				and tyyppi = 'O'";
	$delres = pupe_query($query);

	if (mysql_num_rows($delres) != 0) {
		$eisaapoistaa++;
	}

	$query = "	SELECT tunnus
				from lasku
				where yhtio  = '$kukarow[yhtio]'
				and tila	 = 'K'
				and vanhatunnus <> 0
				and laskunro = '$laskunro'";
	$delres2 = pupe_query($query);

	if (mysql_num_rows($delres2) != 0) {
		$eisaapoistaa++;
	}

	if ($eisaapoistaa == 0) {

		$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mitätöitiin ohjelmassa keikka.php")."<br>";

		// Mitätöidään saapuminen
		$query  = "UPDATE lasku SET alatila = tila, tila = 'D', comments = '$komm' where yhtio='$kukarow[yhtio]' and tila='K' and laskunro='$keikkaid'";
		$result = pupe_query($query);

		// Mitätöidään keikalle suoraan "lisätyt" rivit eli otunnus=keikan tunnus ja uusiotunnus=0
		$query  = "UPDATE tilausrivi SET tyyppi = 'D' where yhtio='$kukarow[yhtio]' and tyyppi='O' and otunnus='$otunnus' and uusiotunnus=0";
		$result = pupe_query($query);

		// Siirretään tälle kiekalle lisätyt sille keikalle jolle ne on kohdistettu
		$query  = "UPDATE tilausrivi SET otunnus=uusiotunnus where yhtio='$kukarow[yhtio]' and tyyppi='O' and otunnus='$otunnus' and uusiotunnus>0";
		$result = pupe_query($query);

		// formissa on tullut myös $ytunnus, joten näin päästään takaisin selaukseen
		$toiminto = "";
	}
	else {
		echo "<font class='error'>".t("VIRHE: Saapumiseen on jo liitetty laskuja tai kohdistettu rivejä, sitä ei voi poistaa")."!<br>";
		// formissa on tullut myös $ytunnus, joten näin päästään takaisin selaukseen
		$toiminto = "";
	}
}

// tulostetaan tarvittavia papruja $otunnuksen mukaan
if ($toiminto == "tulosta") {
	// Haetaan itse saapuminen
	$query    = "	SELECT *
					from lasku
					where tunnus = '$otunnus'
					and yhtio = '$kukarow[yhtio]'";
	$result   = pupe_query($query);
	$laskurow = mysql_fetch_assoc($result);

	// katotaan liitetyt laskut
	$query = "	SELECT
				GROUP_CONCAT(distinct vanhatunnus SEPARATOR ',') volaskutunn
				from lasku
				where yhtio = '$kukarow[yhtio]'
				and tila = 'K'
				and vienti in ('C','F','I','J','K','L')
				and vanhatunnus <> 0
				and laskunro = '$laskurow[laskunro]'
				HAVING volaskutunn is not null";
	$llres = pupe_query($query);

	if (mysql_num_rows($llres) > 0) {
		$llrow = mysql_fetch_assoc($llres);

		$query = "	SELECT
					GROUP_CONCAT(viesti SEPARATOR ' ') viesti,
					GROUP_CONCAT(tapvm SEPARATOR ' ') tapvm
					from lasku
					where yhtio = '$kukarow[yhtio]'
					and tunnus in ($llrow[volaskutunn])";
		$llres = pupe_query($query);
		$llrow = mysql_fetch_assoc($llres);

		$laskurow["tapvm "] = $llrow["tapvm"];
		$laskurow["viesti"] = $llrow["viesti"];
	}

	if (count($komento) == 0) {
		$tulostimet = array('Purkulista','Tuotetarrat','Tariffilista');

		if ($yhtiorow['suuntalavat'] == 'S' and $otunnus != '') {

			$valitut_lavat = "";

			$query = "	SELECT GROUP_CONCAT(tunnus) tunnukset, group_concat(suuntalava) suuntalavat
						FROM tilausrivi
						WHERE yhtio 	= '{$kukarow['yhtio']}'
						AND uusiotunnus = '{$otunnus}'
						AND tyyppi 		= 'O'
						AND suuntalava  > 0
						AND kpl 	   != 0";
			$check_result = pupe_query($query);
			$check_row = mysql_fetch_assoc($check_result);

			if (trim($check_row['tunnukset']) != '') {
				$tulostimet[] = "Vastaanottoraportti";

				$valitut_lavat .= $check_row["suuntalavat"];
			}

			$query = "	SELECT GROUP_CONCAT(tunnus) tunnukset, group_concat(suuntalava) suuntalavat
						FROM tilausrivi
						WHERE yhtio 	= '{$kukarow['yhtio']}'
						AND uusiotunnus = '{$otunnus}'
						AND tyyppi 		= 'O'
						AND suuntalava  > 0
						AND kpl 		= 0";
			$check_result = pupe_query($query);
			$check_row = mysql_fetch_assoc($check_result);

			if (trim($check_row['tunnukset']) != '') {
				$tulostimet[] = "Tavaraetiketti";

				$valitut_lavat .= $check_row["suuntalavat"];
			}
		}

		echo "<br><table>";
		echo "<tr>";
		echo "<th>".t("saapuminen")."</th>";
		echo "<th>".t("ytunnus")."</th>";
		echo "<th>".t("nimi")."</th>";
		echo "</tr>";
		echo "<tr>
				<td>$laskurow[laskunro]</td>
				<td>$laskurow[ytunnus]</td>
				<td>$laskurow[nimi]</td>
				</tr>";
		echo "</table><br>";

		require('../inc/valitse_tulostin.inc');
	}
	else {
		// takaisin selaukseen
		$toiminto 	  = "";
		$ytunnus  	  = $laskurow["ytunnus"];
		$toimittajaid = $laskurow["liitostunnus"];
	}

	if ($komento["Purkulista"] != '') {
		require('tulosta_purkulista.inc');
	}

	if ($komento["Tuotetarrat"] != '') {
		require('tulosta_tuotetarrat.inc');
	}

	if ($komento["Tariffilista"] != '') {
		require('tulosta_tariffilista.inc');
	}

	if ($komento["Vastaanottoraportti"] != '') {
		require('tulosta_vastaanottoraportti.inc');
	}

	if ($komento["Tavaraetiketti"] != '') {
		require('tulosta_tavaraetiketti.inc');
	}
}

if ($toiminto == 'erolista') {

	// Haetaan itse saapuminen
	$query    = "	SELECT *
					FROM lasku
					WHERE tunnus = '{$otunnus}'
					AND yhtio = '{$kukarow['yhtio']}'";
	$result   = pupe_query($query);
	$laskurow = mysql_fetch_assoc($result);

	// katotaan liitetyt laskut
	$query = "	SELECT GROUP_CONCAT(DISTINCT ostores_lasku.laskunro SEPARATOR ',') volaskutunn
				FROM lasku
				JOIN lasku ostores_lasku on (ostores_lasku.yhtio = lasku.yhtio and ostores_lasku.tunnus = lasku.vanhatunnus)
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				AND lasku.tila = 'K'
				AND lasku.vienti IN ('C','F','I','J','K','L')
				AND lasku.vanhatunnus <> 0
				AND lasku.laskunro = '{$laskurow['laskunro']}'
				HAVING volaskutunn IS NOT NULL";
	$llres = pupe_query($query);
	$llrow = mysql_fetch_assoc($llres);

	if ($llrow['volaskutunn'] != '') {

		$komento = isset($komento) ? $komento : '';

		$return_bool = erolista($llrow['volaskutunn'], 'ostolasku', $komento, $otunnus);

		$toiminto = $return_bool === TRUE ? "" : $toiminto;
		$toimittajaid = $laskurow['liitostunnus'];
	}
}

// syötetään keikan lisätietoja
if ($toiminto == "lisatiedot") {
	require ("ostotilauksen_lisatiedot.inc");
}

// chekataan tilauksen varastopaikat
if ($toiminto == "varastopaikat") {
	require('ostorivienvarastopaikat.inc');
}

// lisäillään keikkaan kululaskuja
if ($toiminto == "kululaskut") {
	$keikanalatila 	= "";

	require('kululaskut.inc');
}

if ($toiminto == 'kalkyyli' and $yhtiorow['suuntalavat'] == 'S' and $tee == '' and trim($suuntalavan_tunnus) != '' and trim($koko_suuntalava) == 'X') {
	if ((isset($suuntalavan_hyllyalue) and trim($suuntalavan_hyllyalue) == '') or (isset($suuntalavan_hyllypaikka) and trim($suuntalavan_hyllypaikka) == '')) {
		echo "<font class='error'>",t("Hyllyalue oli tyhjä"),"!</font><br />";
		$toiminto = 'suuntalavat';
		$tee = 'vie_koko_suuntalava';
	}
	else {
		$vietiinko_koko_suuntalava = '';

		if (trim($suuntalavan_hyllypaikka) != '') {
			list($suuntalavan_hyllyalue, $suuntalavan_hyllynro, $suuntalavan_hyllyvali, $suuntalavan_hyllytaso) = explode("#", $suuntalavan_hyllypaikka);
		}

		$suuntalavan_hyllyalue = mysql_real_escape_string($suuntalavan_hyllyalue);
		$suuntalavan_hyllynro  = mysql_real_escape_string($suuntalavan_hyllynro);
		$suuntalavan_hyllyvali = mysql_real_escape_string($suuntalavan_hyllyvali);
		$suuntalavan_hyllytaso = mysql_real_escape_string($suuntalavan_hyllytaso);

		$paivitetyt_rivit = paivita_hyllypaikat($suuntalavan_tunnus,
												$suuntalavan_hyllyalue,
												$suuntalavan_hyllynro,
												$suuntalavan_hyllyvali,
												$suuntalavan_hyllytaso);

		if ($paivitetyt_rivit > 0) {
			echo "<br />",t("Päivitettiin suuntalavan tuotteet paikalle")," {$suuntalavan_hyllyalue} {$suuntalavan_hyllynro} {$suuntalavan_hyllyvali} {$suuntalavan_hyllytaso}<br />";
			$vietiinko_koko_suuntalava = 'joo';
		}
	}
}

if ($toiminto == 'suuntalavat') {
	require('suuntalavat.inc');
}

if ($toiminto == 'tulosta_sscc') {
	require('tulosta_sscc.inc');
}

// tehdään errorichekkejä jos on varastoonvienti kyseessä
if ($toiminto == "kaikkiok" or $toiminto == "kalkyyli") {

	$varastoerror = saako_vieda_varastoon($otunnus, $toiminto, 'echota_virheet');

	if ($varastoerror != 0) {
		echo "<br><form method='post'>";
		echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
		echo "<input type='hidden' name='toiminto' value=''>";
		echo "<input type='hidden' name='ytunnus' value='$laskurow[ytunnus]'>";
		echo "<input type='submit' value='".t("Takaisin")."'>";
		echo "</form>";

		$ytunnus = "";
		$toiminto = "dummieimitään";
	}
}

// lasketaan lopullinen varastonarvo
if ($toiminto == "kaikkiok") {
	require ("varastonarvo_historia.inc");
}

// viedään keikka varastoon
if ($toiminto == "kalkyyli") {
	require ("kalkyyli.inc");
}

if (isset($nappikeikalla) and $nappikeikalla == 'ollaan' and $toiminto != 'kohdista') {
	$toiminto = "kohdista";
}

if ($toiminto == "kohdista") {
	require('ostotilausten_rivien_kohdistus.inc');
}

// Haku
if ($ytunnus == "" and $keikka != "") {
	$keikka = (int) $keikka;

	$query = "	SELECT ytunnus, liitostunnus, laskunro
				FROM lasku USE INDEX (tila_index)
				WHERE lasku.yhtio 		= '$kukarow[yhtio]'
				and lasku.tila 			= 'K'
				and lasku.alatila 		= ''
				and lasku.vanhatunnus 	= 0
				and lasku.laskunro 		= $keikka";
	$keikkahaku_res = pupe_query($query);

	if (mysql_num_rows($keikkahaku_res) > 0) {
		$keikkahaku_row = mysql_fetch_assoc($keikkahaku_res);

		$keikkarajaus = $keikkahaku_row["laskunro"];
		$ytunnus 	  = $keikkahaku_row["ytunnus"];
		$toimittajaid = $keikkahaku_row["liitostunnus"];
	}
	else {
		$ostotil = "";
		$keikka  = "";
	}
}

// Haku
if ($ytunnus == "" and $ostotil != "") {
	$ostotil = (int) $ostotil;

	$query = "	SELECT lasku.ytunnus, lasku.liitostunnus, group_concat(lasku.laskunro) laskunro
				FROM tilausrivi
				JOIN lasku USE INDEX (tila_index) ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus and lasku.tila = 'K' and lasku.alatila = '' and lasku.vanhatunnus = 0)
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
			 	and tilausrivi.otunnus = $ostotil
				and tilausrivi.tyyppi = 'O'";
	$keikkahaku_res = pupe_query($query);

	if (mysql_num_rows($keikkahaku_res) > 0) {
		$keikkahaku_row = mysql_fetch_assoc($keikkahaku_res);

		if ($keikkahaku_row['laskunro'] != '') {
			$keikkarajaus = $keikkahaku_row["laskunro"];
			$ytunnus 	  = $keikkahaku_row["ytunnus"];
			$toimittajaid = $keikkahaku_row["liitostunnus"];
		}
		else {
			$ostotil = "";
			$keikka  = "";
		}
	}
	else {
		$ostotil = "";
		$keikka  = "";
	}
}

// jos ollaan annettu $ytunnus haetaan toimittajan tiedot arrayseen $toimittajarow
if ($ytunnus != "" or $toimittajaid != "") {
	$keikkamonta = 0;
	$hakutunnus  = $ytunnus;
	$hakuid		 = $toimittajaid;

	require ("../inc/kevyt_toimittajahaku.inc");

	$keikkamonta += $monta;

	if ($keikkamonta > 1) {
		$toimittajaid  = "";
		$toimittajarow = "";
		$ytunnus 	   = "";
	}
}

if ($toiminto == "" and $ytunnus == "" and $keikka == "") {

	echo "<form name='toimi' method='post' autocomplete='off'>";
	echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";

	echo "<table>";
	echo "<tr>";
	echo "<th>".t("Etsi toimittaja")."</th>";
	echo "<td><input type='text' name='ytunnus' value='$ytunnus'></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<th>".t("Etsi saapumisnumerolla")."</th>";
	echo "<td><input type='text' name='keikka' value='$keikka'></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Etsi ostotilausnumerolla")."</th>";
	echo "<td><input type='text' name='ostotil' value='$ostotil'></td>";

	if ($yhtiorow['varastopaikkojen_maarittely'] == 'M') {

		$query = "SELECT tunnus, nimitys FROM keraysvyohyke WHERE yhtio = '{$kukarow['yhtio']}' AND nimitys != ''";
		$keraysvyohyke_result = pupe_query($query);

		if (mysql_num_rows($keraysvyohyke_result) > 0) {
			echo "</tr><tr>";
			echo "<th>",t("Rajaa laatijaa keräysvyöhykkeellä"),"</th>";
			echo "<td><select name='keraysvyohyke' onchange='submit();'>";
			echo "<option value=''>",t("Valitse"),"</option>";

			while ($keraysvyohyke_row = mysql_fetch_assoc($keraysvyohyke_result)) {

				$sel = $keraysvyohyke_row['tunnus'] == $keraysvyohyke ? ' selected' : '';

				echo "<option value='{$keraysvyohyke_row['tunnus']}'{$sel}>{$keraysvyohyke_row['nimitys']}</option>";
			}

			echo "</select></td>";
		}

		echo "</tr><tr>";
		echo "<th>",t("Etsi saapumisen laatijalla"),"</th>";

		$kukalisa = trim($keraysvyohyke) != '' ? " and keraysvyohyke = '{$keraysvyohyke}' " : '';

		$query = "	SELECT kuka, nimi
					FROM kuka
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND extranet = ''
					$kukalisa
					ORDER BY nimi";
		$keikan_laatija_res = pupe_query($query);

		echo "<td><select name='keikan_laatija' onchange='submit();'>";
		echo "<option value=''>",t("Valitse"),"</option>";

		while ($keikan_laatija_row = mysql_fetch_assoc($keikan_laatija_res)) {

			$sel = $keikan_laatija_row['kuka'] == $keikan_laatija ? ' selected' : '';

			echo "<option value='{$keikan_laatija_row['kuka']}'{$sel}>{$keikan_laatija_row['nimi']} ({$keikan_laatija_row['kuka']})</option>";
		}

		echo "</select></td>";
	}

	if ($yhtiorow['suuntalavat'] == 'S') {
		echo "</tr><tr>";
		echo "<th>",t("Näytä vain saapumiset, joilla on siirtovalmiita suuntalavoja"),"</th>";

		$chk = trim($nayta_siirtovalmiit_suuntalavat) != '' ? ' checked' : '';

		echo "<td><input type='checkbox' name='nayta_siirtovalmiit_suuntalavat' {$chk} onchange='submit();'></td>";
	}

	echo "<td class='back'><input type='submit' value='".t("Hae")."'></td>";
	echo "</tr>";

	echo "</table>";
	echo "</form>";

	// kursorinohjausta
	$formi    = "toimi";
	$kentta   = "ytunnus";
	$toiminto = "";

	$kaikkivarastossayhteensa 		= 0;
	$vaihtoomaisuuslaskujayhteensa 	= 0;
	$kululaskujayhteensa 			= 0;

	$laatijalisa = '';

	if (isset($keikan_laatija) and trim($keikan_laatija) != '') {
		$laatijalisa = " and lasku.laatija = '$keikan_laatija' ";
	}

	$suuntalavajoin = '';

	if ($yhtiorow['suuntalavat'] == 'S' and trim($nayta_siirtovalmiit_suuntalavat) != '') {
		$suuntalavajoin = " JOIN suuntalavat ON (suuntalavat.yhtio = tilausrivi.yhtio AND suuntalavat.tunnus = tilausrivi.suuntalava AND suuntalavat.tila = 'S')
							JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus AND suuntalavat_saapuminen.saapuminen = lasku.tunnus) ";
	}

	// näytetään millä toimittajilla on keskeneräisiä keikkoja
	$query = "	SELECT ytunnus, nimi, nimitark, osoite, postitp, swift, group_concat(distinct if(comments!='',comments,NULL) SEPARATOR '<br><br>') comments, liitostunnus, count(distinct lasku.tunnus) kpl, group_concat(distinct laskunro SEPARATOR ', ') keikat,
				round(sum(tilausrivi.rivihinta),2) varastossaarvo, lasku.laskunro
				FROM lasku USE INDEX (yhtio_tila_mapvm)
				LEFT JOIN tilausrivi USE INDEX (uusiotunnus_index) on (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus and tilausrivi.tyyppi = 'O')
				$suuntalavajoin
				WHERE lasku.yhtio 	  = '$kukarow[yhtio]'
				and lasku.tila 		  = 'K'
				and lasku.alatila 	  = ''
				and lasku.vanhatunnus = 0
				and lasku.mapvm 	  = '0000-00-00'
				$laatijalisa
				GROUP BY liitostunnus, ytunnus, nimi, osoite, postitp, swift
				ORDER BY nimi, nimitark, ytunnus";
	$result = pupe_query($query);

	if (mysql_num_rows($result) != 0) {

		echo "<br><font class='head'>".t("Keskeneräiset saapumiset")."</font><hr>";

		echo "<table>";
		echo "<tr><th>".t("ytunnus")."</th><th>&nbsp;</th><th>".t("nimi")."</th><th>".t("osoite")."</th><th>".t("swift")."</th><th>".t("saapumisnumerot")."</th><th>".t("kpl")."</th><th>".t("varastonarvo")."</th><th></th></tr>";

		while ($row = mysql_fetch_assoc($result)) {

			$query = "	SELECT count(*) num,
						sum(if(vienti='C' or vienti='F' or vienti='I' or vienti='J' or vienti='K' or vienti='L', 1, 0)) volasku,
						sum(if(vienti!='C' and vienti!='F' and vienti!='I' and vienti!='J' and vienti!='K' and vienti!='L', 1, 0)) kulasku,
						sum(if(vienti='C' or vienti='F' or vienti='I' or vienti='J' or vienti='K' or vienti='L', summa * vienti_kurssi, 0)) vosumma,
						sum(if(vienti!='C' and vienti!='F' and vienti!='I' and vienti!='J' and vienti!='K' and vienti!='L', arvo * vienti_kurssi, 0)) kusumma
						from lasku use index (yhtio_tila_laskunro)
						where yhtio='$kukarow[yhtio]'
						and tila='K'
						and vanhatunnus<>0
						and laskunro='$row[laskunro]'";
			$laskuja_result = pupe_query($query);
			$laskuja_row = mysql_fetch_assoc($laskuja_result);

			$kaikkivarastossayhteensa += $row["varastossaarvo"];
			$vaihtoomaisuuslaskujayhteensa += $laskuja_row["vosumma"];
			$kululaskujayhteensa += $laskuja_row["kusumma"];

			echo "<tr class='aktiivi'>";

			echo "<td valign='top'>$row[ytunnus]</td>";

			// tehdään pop-up divi jos keikalla on kommentti...
			if ($row["comments"] != "") {
				echo "<td valign='top' class='tooltip' id='$row[liitostunnus]'><img src='$palvelin2/pics/lullacons/info.png'>";
				echo "<div id='div_$row[liitostunnus]' class='popup' style='width: 500px;'>";
				echo $row["comments"];
				echo "</div>";
				echo "</td>";
			}
			else {
				echo "<td>&nbsp;</td>";
			}

			if ($row["varastossaarvo"] == 0) $row["varastossaarvo"] = "";

			echo "<td>$row[nimi] $row[nimitark]</td><td>$row[osoite] $row[postitp]</td><td>$row[swift]</td><td>$row[keikat]</td><td align='right'>$row[kpl]</td><td align='right'>$row[varastossaarvo]</td>";
			echo "<td><form method='post'>";
			echo "<input type='hidden' name='toimittajaid' value='$row[liitostunnus]'>";
			echo "<input type='submit' value='".t("Valitse")."'>";
			echo "</form></td>";
			echo "</tr>";
		}

		echo "</table>";

		if ($kaikkivarastossayhteensa != 0 or $vaihtoomaisuuslaskujayhteensa != 0 or $kululaskujayhteensa != 0) {
			echo "<br><table>";
			echo "<tr><th>".t("Varastoon viety yhteensä")."</th><td align='right'> ".sprintf("%01.2f", $kaikkivarastossayhteensa)." $yhtiorow[valkoodi]</td></tr>";
			echo "<tr><th>".t("Vaihto-omaisuuslaskuja liitetty")."</th><td align='right'>".sprintf("%01.2f", $vaihtoomaisuuslaskujayhteensa)." $yhtiorow[valkoodi]</td></tr>";
			echo "<tr><th>".t("Kululaskuja liitetty")."</th><td align='right'>".sprintf("%01.2f", $kululaskujayhteensa)." $yhtiorow[valkoodi]</td></tr>";
			echo "</table>";
		}
	}
}

// perustetaan uusi keikka toimittajalle $ytunnus
if ($toiminto == "uusi" and $toimittajaid > 0) {
	// haetaan seuraava vapaa keikkaid
	$query  = "SELECT max(laskunro) laskunro from lasku where yhtio='$kukarow[yhtio]' and tila='K'";
	$result = pupe_query($query);
	$row    = mysql_fetch_assoc($result);

	$id		= $row['laskunro']+1;

	$query  = "SELECT kurssi from valuu where nimi='$toimittajarow[oletus_valkoodi]' and yhtio='$kukarow[yhtio]'";
	$result = pupe_query($query);
	$row    = mysql_fetch_assoc($result);
	$kurssi = $row["kurssi"];

	$maa_lahetys = $toimittajarow['maa_lahetys'] != '' ? $toimittajarow['maa_lahetys'] : $toimittajarow['maa'];

	// meillä on $toimittajarow haettuna ylhäällä
	$query = "	INSERT into lasku set
				yhtio        	= '$kukarow[yhtio]',
				laskunro     	= '$id',
				ytunnus	     	= '$toimittajarow[ytunnus]',
				nimi         	= '$toimittajarow[nimi]',
				valkoodi     	= '$toimittajarow[oletus_valkoodi]',
				vienti       	= '$toimittajarow[oletus_vienti]',
				vienti_kurssi	= '$kurssi',
				toimitusehto 	= '$toimittajarow[toimitusehto]',
				osoite       	= '$toimittajarow[osoite]',
				postitp      	= '$toimittajarow[postitp]',
				maa			 	= '$toimittajarow[maa]',
				maa_lahetys 	= '$maa_lahetys',
				kauppatapahtuman_luonne = '$toimittajarow[kauppatapahtuman_luonne]',
				kuljetusmuoto 	= '$toimittajarow[kuljetusmuoto]',
				rahti 			= '$toimittajarow[oletus_kulupros]',
				swift        	= '$toimittajarow[swift]',
				liitostunnus 	= '$toimittajarow[tunnus]',
				tila         	= 'K',
				luontiaika	 	= now(),
				laatija		 	= '$kukarow[kuka]'";
	$result = pupe_query($query);

	// selaukseen
	$toiminto = "";
}

// selataan toimittajan keikkoja
if ($toiminto == "" and (($ytunnus != "" or $keikkarajaus != '') and $toimittajarow["ytunnus"] != '')) {

	// näytetään vähä toimittajan tietoja
	echo "<table>";
	echo "<tr>";
	echo "<th colspan='5'>".t("Toimittaja")."</th>";
	echo "</tr><tr>";
	echo "<td>$toimittajarow[ytunnus]</td>";
	echo "<td>$toimittajarow[nimi]</td>";
	echo "<td>$toimittajarow[osoite]</td>";
	echo "<td>$toimittajarow[postino]</td>";
	echo "<td>$toimittajarow[postitp]</td>";

	echo "<td class='back' rowspan='4' style='vertical-align:bottom;'>";
	echo "<form method='post'>";
	echo "<input type='hidden' name='toiminto' value='uusi'>";
	echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
	echo "<input type='hidden' name='ytunnus' value='$ytunnus'>";
	echo "<input type='submit' value='".t("Perusta uusi saapuminen")."'>";
	echo "</form>";
	echo "</td>";
	echo "</tr>";

	if (trim($toimittajarow["fakta"]) != "") {
		echo "<tr><td colspan='5'>".wordwrap($toimittajarow["fakta"], 100, "<br>")."</td></tr>";
	}

	echo "</table><br>";

	// etsitään vanhoja keikkoja, vanhatunnus pitää olla tyhjää niin ei listata liitettyjä laskuja
	$query = "	SELECT *
				FROM lasku USE INDEX (tila_index)
				where lasku.yhtio = '$kukarow[yhtio]'
				and lasku.liitostunnus = '$toimittajaid'
				and lasku.tila = 'K'
				and lasku.alatila = ''
				and lasku.vanhatunnus = 0
				ORDER BY lasku.laskunro DESC";
	$result = pupe_query($query);

	if (mysql_num_rows($result) > 0) {

		echo "<font class='head'>".t("Toimittajan keskeneräiset saapumiset")."</font><hr>";

		pupe_DataTables(array(array($pupe_DataTables, 9, 9, false)));

		echo "<table class='display dataTable' id='{$pupe_DataTables}'>";
		echo "<thead>";
		echo "<tr>";
		echo "<th valign='top'>".t("saapuminen")."</th>";
		echo "<th valign='top'>&nbsp;</th>";
		echo "<th valign='top'>".t("ytunnus")." /<br>".t("nimi")."</th>";
		echo "<th valign='top'>".t("kohdistus")." /<br>".t("lisätiedot")."</th>";
		echo "<th valign='top'>".t("paikat")." /<br>".t("sarjanrot")."</th>";
		echo "<th valign='top'>".t("kohdistettu")." /<br>".t("varastossa")."</th>";
		echo "<th valign='top'>".t("tilaukset")."</th>";
		echo "<th valign='top'>".t("ostolaskuja")." /<br>".t("kululaskuja")."</th>";
		echo "<th valign='top'>".t("toiminto")."</th>";
		echo "</tr>";

		echo "<tr>";
		echo "<td><input type='text' class='search_field' name='search_saapuminen'></td>";
		echo "<td><input type='hidden' class='search_field' name='search_eimitaan'></td>";
		echo "<td><input type='text' class='search_field' name='search_ytunnus'></td>";
		echo "<td><input type='text' class='search_field' name='search_kohdistus'></td>";
		echo "<td><input type='text' class='search_field' name='search_paikat'></td>";
		echo "<td><input type='text' class='search_field' name='search_kohdistettu'></td>";
		echo "<td><input type='text' class='search_field' name='search_tilaukset'></td>";
		echo "<td><input type='text' class='search_field' name='search_laskuja'></td>";
		echo "<td><input type='hidden' class='search_field' name='search_eimitaan'></td>";
		echo "</tr>";

		echo "</thead>";
		echo "<tbody>";

		$keikkakesken = 0;
		if (file_exists("/tmp/$kukarow[yhtio]-keikka.lock")) {
			$keikkakesken = file_get_contents("/tmp/$kukarow[yhtio]-keikka.lock");
		}

		$kaikkivarastossayhteensa 		= 0;
		$vaihtoomaisuuslaskujayhteensa 	= 0;
		$kululaskujayhteensa 			= 0;

		while ($row = mysql_fetch_assoc($result)) {

			list ($kaikkivarastossayhteensa,$kohdistus,$kohok,$kplvarasto,$kplyhteensa,$lisatiedot,$lisok,$llrow,$sarjanrook,$sarjanrot,$uusiot,$varastopaikat,$varastossaarvo,$varok) = tsekit($row,$kaikkivarastossayhteensa,$toimittajaid);
			$vaihtoomaisuuslaskujayhteensa += $llrow["vosumma"];
			$kululaskujayhteensa += $llrow["kusumma"];

			echo "<tr class='aktiivi'>";

			echo "<td valign='top'>$row[laskunro]</td>";

			// tehdään pop-up divi jos keikalla on kommentti...
			if ($row["comments"] != "") {
				echo "<td valign='top' class='tooltip' id='$row[laskunro]'>";
				echo "<div id='div_$row[laskunro]' class='popup' style='width:500px;'>";
				echo t("Saapuminen").": $row[laskunro] / $row[nimi]<br><br>";
				echo $row["comments"];
				echo "</div>";
				echo "<img src='$palvelin2/pics/lullacons/info.png'></td>";
			}
			else {
				echo "<td>&nbsp;</td>";
			}

			echo "<td valign='top'>$row[ytunnus]<br>$row[nimi]</td>";
			echo "<td valign='top'>$kohdistus<br>$lisatiedot</td>";
			echo "<td valign='top'>$varastopaikat<br>$sarjanrot</td>";
			echo "<td valign='top'>$kplyhteensa<br>$kplvarasto $varastossaarvo</td>";

			if (count($uusiot) > 0 and count($uusiot) < 4) {
				echo "<td valign='top'>";
				echo implode("<br>", $uusiot);
				echo "</td>";
			}
			elseif (count($uusiot) > 0) {
				echo "<td valign='top' class='tooltip' id='keikka_$row[laskunro]'>";
				echo "<div id='div_keikka_$row[laskunro]' class='popup' style='width:100px;'>";
				echo t("Tilaukset").":<br><br>";
				echo implode("<br>", $uusiot);
				echo "</div>";
				echo "<img src='$palvelin2/pics/lullacons/info.png'></td>";
			}
			else {
				echo "<td>&nbsp;</td>";
			}

			$laskujen_tiedot = "";

			if ($llrow["volasku"] != $llrow["volasku_ok"] or $llrow["kulasku"] != $llrow["kulasku_ok"]) {
				$query = "	SELECT ostores_lasku.*, kuka.nimi kukanimi
							FROM lasku use index (yhtio_tila_laskunro)
							JOIN lasku ostores_lasku use index (PRIMARY) ON (ostores_lasku.yhtio = lasku.yhtio and ostores_lasku.tunnus = lasku.vanhatunnus and ostores_lasku.hyvaksyja_nyt != '')
							LEFT JOIN kuka ON (kuka.yhtio = ostores_lasku.yhtio and kuka.kuka = ostores_lasku.hyvaksyja_nyt)
							WHERE lasku.yhtio 	  = '$kukarow[yhtio]'
							AND lasku.tila 		  = 'K'
							AND lasku.vanhatunnus > 0
							AND lasku.laskunro    = '$row[laskunro]'";
				$volasresult = pupe_query($query);

				$laskujen_tiedot .= "<div id='div_lasku_$row[laskunro]' class='popup'>";
				while ($volasrow = mysql_fetch_assoc($volasresult)) {
					$laskujen_tiedot .= t("Lasku")." $volasrow[nimi] ($volasrow[summa] $volasrow[valkoodi]) ".t("hyväksyttävänä käyttäjällä")." $volasrow[kukanimi]<br>";
				}
				$laskujen_tiedot .= "</div>";
			}

			if ($llrow["volasku"] > 0) {
				if ($llrow["volasku"] != $llrow["volasku_ok"]) {
					$laskujen_tiedot .= "$llrow[volasku] ($llrow[vosumma]) <font class='error'>*</font> <img class='tooltip' id='lasku_$row[laskunro]' src='$palvelin2/pics/lullacons/alert.png'>";
				}
				else {
					$laskujen_tiedot .= "$llrow[volasku] ($llrow[vosumma]) <font class='ok'>*</font>";
				}
			}

			$laskujen_tiedot .= "<br>";

			if ($llrow["kulasku"] > 0) {
				if ($llrow["kulasku"] != $llrow["kulasku_ok"]) {
					$laskujen_tiedot .= "$llrow[kulasku] ($llrow[kusumma]) <font class='error'>*</font> <img class='tooltip' id='lasku_$row[laskunro]' src='$palvelin2/pics/lullacons/alert.png'>";
				}
				else {
					$laskujen_tiedot .= "$llrow[kulasku] ($llrow[kusumma]) <font class='ok'>*</font>";
				}
			}

			echo "<td valign='top'>$laskujen_tiedot</td>";

			// jos tätä keikkaa ollaan just viemässä varastoon ei tehdä dropdownia
			if ($keikkakesken == $row["tunnus"]) {
				echo "<td>".t("Varastoonvienti kesken")."</td>";
			}
			else {

				echo "<td align='right'>";
				echo "<form method='post'>";
				echo "<input type='hidden' name='toimittajaid' 	value='$toimittajaid'>";
				echo "<input type='hidden' name='otunnus' 		value='$row[tunnus]'>";
				echo "<input type='hidden' name='ytunnus' 		value='$ytunnus'>";
				echo "<input type='hidden' name='keikkaid' 		value='$row[laskunro]'>";
				echo "<input type='hidden' name='tunnus' 		value='$row[tunnus]'>";
				echo "<input type='hidden' name='laskunro' 		value='$row[laskunro]'>";
				echo "<input type='hidden' name='indexvas' 		value='1'>";
				echo "<select name='toiminto'>";

				// näitä saa tehdä aina keikalle
				echo "<option value='kohdista'>"         .t("Kohdista rivejä")."</option>";
				echo "<option value='kululaskut'>"       .t("Saapumisen laskut")."</option>";
				echo "<option value='lisatiedot'>"       .t("Lisätiedot")."</option>";
				echo "<option value='erolista'>"       	 .t("Erolista")."</option>";
				echo "<option value='yhdista'>"          .t("Yhdistä saapumisia")."</option>";

				// poista keikka vaan jos ei ole yhtään riviä kohdistettu ja ei ole yhtään kululaskua liitetty
				if ($kplyhteensa == 0 and $llrow["num"] == 0) {
					echo "<option value='poista'>"       .t("Poista saapuminen")."</option>";
				}

				if ($yhtiorow['suuntalavat'] == 'S') {
					echo "<option value='suuntalavat'>",t("Suuntalavat"),"</option>";
				}

				// jos on kohdistettuja rivejä niin saa tehdä näitä
				if ($kplyhteensa > 0) {
					echo "<option value='varastopaikat'>".t("Varastopaikat")."</option>";
					echo "<option value='tulosta'>"      .t("Tulosta paperit")."</option>";
				}

				// jos on kohdistettuja rivejä ja lisätiedot on syötetty ja varastopaikat on ok ja on vielä jotain vietävää varastoon
				if ($kplyhteensa > 0 and $varok == 1 and $kplyhteensa != $kplvarasto and $sarjanrook == 1) {
					if ($yhtiorow['suuntalavat'] != 'S') {
						echo "<option value='kalkyyli'>"     .t("Vie varastoon")."</option>";
					}
				}

				// jos lisätiedot, kohdistus ja paikat on ok sekä kaikki rivit on viety varastoon, ja kaikki liitetyt laskut on hyväksytty (kukarow.taso 3 voi ohittaa tämän), niin saadaan laskea virallinen varastonarvo
				if ($lisok == 1 and $kohok == 1 and $varok == 1 and $kplyhteensa == $kplvarasto and $sarjanrook == 1 and (($llrow["volasku"] == $llrow["volasku_ok"] and $llrow["kulasku"] == $llrow["kulasku_ok"]) or $kukarow["taso"] == "3")) {
					echo "<option value='kaikkiok'>"     .t("Laske virallinen varastonarvo")."</option>";
				}

				echo "</select>";
				echo "<input type='submit' value='".t("Tee")."'>";
				echo "</form>";
				echo "</td>";

			}
			echo "</tr>";
		}

		echo "</tbody>";
		echo "</table>";

		if ($kaikkivarastossayhteensa != 0 or $vaihtoomaisuuslaskujayhteensa != 0 or $kululaskujayhteensa != 0) {
			echo "<br><table>";
			echo "<tr><th>".t("Varastoon viety yhteensä")."</th><td align='right'> ".sprintf("%01.2f", $kaikkivarastossayhteensa)." $yhtiorow[valkoodi]</td></tr>";
			echo "<tr><th>".t("Vaihto-omaisuuslaskuja liitetty")."</th><td align='right'>".sprintf("%01.2f", $vaihtoomaisuuslaskujayhteensa)." $yhtiorow[valkoodi]</td></tr>";
			echo "<tr><th>".t("Kululaskuja liitetty")."</th><td align='right'>".sprintf("%01.2f", $kululaskujayhteensa)." $yhtiorow[valkoodi]</td></tr>";
			echo "</table>";
		}

		// Rajaukset
		if ($keikkarajaus != "") {
			$datatables_rajaus = str_replace(",", "|", $keikkarajaus);

			echo "<script language='javascript' type='text/javascript'>
					$(document).ready(function() {
						$('input[name=\"search_saapuminen\"]').val(\"$datatables_rajaus\");
						$('input[name=\"search_saapuminen\"]').keyup();
					});
					</script>";
		}
	}
}

$nappikeikka = "";

// kohdisteaan keikkaa laskun tunnuksella $otunnus
if ($toiminto == "kohdista" or $toiminto == "yhdista" or $toiminto == "poista" or $toiminto == "tulosta" or $toiminto == "lisatiedot" or
	$toiminto == "varastopaikat" or $toiminto == "kululaskut" or $toiminto == "kalkyyli" or $toiminto == "kaikkiok" or $toiminto == "suuntalavat") {

	$query = "	SELECT *
				FROM lasku
				where lasku.yhtio 		= '$kukarow[yhtio]'
				and lasku.liitostunnus 	= '$toimittajaid'
				and lasku.tunnus 		= '$otunnus'";
	$tsekkiresult = pupe_query($query);
	$tsekkirow = mysql_fetch_assoc($tsekkiresult);

	if (!isset($kaikkivarastossayhteensa)) $kaikkivarastossayhteensa = 0;

	list ($kaikkivarastossayhteensa,$kohdistus,$kohok,$kplvarasto,$kplyhteensa,$lisatiedot,$lisok,$llrow,$sarjanrook,$sarjanrot,$uusiot,$varastopaikat,$varastossaarvo,$varok) = tsekit($tsekkirow, $kaikkivarastossayhteensa, $toimittajaid);

	$formalku =  "<td class='back'>";
	$formalku .= "<form action = '?indexvas=1' method='post'>";
	$formalku .= "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
	$formalku .= "<input type='hidden' name='otunnus' value='$tsekkirow[tunnus]'>";
	$formalku .= "<input type='hidden' name='ytunnus' value='$ytunnus'>";
	$formalku .= "<input type='hidden' name='keikkaid' value='$tsekkirow[laskunro]'>";
	$formalku .= "<input type='hidden' name='tunnus' value='$tsekkirow[tunnus]'>";
	$formalku .= "<input type='hidden' name='laskunro' value='$tsekkirow[laskunro]'>";
	$formalku .= "<input type='hidden' name='nappikeikalle' value='menossa'>";

	$formloppu = "</form></td>";

	// näitä saa tehdä aina keikalle
	$nappikeikka = "<table><tr>";

	$nappikeikka .= "$formalku";
	$nappikeikka .= "<input type='hidden' name='toiminto' value='kohdista'>";
	$nappikeikka .= "<input type='submit' value='".t("Kohdista rivejä")."'>";
	$nappikeikka .= "$formloppu";

	$nappikeikka .= "$formalku";
	$nappikeikka .= "<input type='hidden' name='toiminto' value='kululaskut'>";
	$nappikeikka .= "<input type='submit' value='".t("Saapumisen laskut")."'>";
	$nappikeikka .= "$formloppu";

	$nappikeikka .= "$formalku";
	$nappikeikka .= "<input type='hidden' name='toiminto' value='lisatiedot'>";
	$nappikeikka .= "<input type='submit' value='".t("Lisätiedot")."'>";
	$nappikeikka .= "$formloppu";

	$nappikeikka .= "$formalku";
	$nappikeikka .= "<input type='hidden' name='toiminto' value='erolista'>";
	$nappikeikka .= "<input type='submit' value='".t("Erolista")."'>";
	$nappikeikka .= "$formloppu";


	// poista keikka vaan jos ei ole yhtään riviä kohdistettu ja ei ole yhtään kululaskua liitetty
	if ($kplyhteensa == 0 and $llrow["num"] == 0) {
		$nappikeikka .= "$formalku";
		$nappikeikka .= "<input type='hidden' name='toiminto' value='poista'>";
		$nappikeikka .= "<input type='submit' value='".t("Poista saapuminen")."'>";
		$nappikeikka .= "$formloppu";
	}

	// jos on kohdistettuja rivejä niin saa tehdä näitä
	if ($kplyhteensa > 0) {
		$nappikeikka .= "$formalku";
		$nappikeikka .= "<input type='hidden' name='toiminto' value='varastopaikat'>";
		$nappikeikka .= "<input type='submit' value='".t("Varastopaikat")."'>";
		$nappikeikka .= "$formloppu";

		$nappikeikka .= "$formalku";
		$nappikeikka .= "<input type='hidden' name='toiminto' value='tulosta'>";
		$nappikeikka .= "<input type='submit' value='".t("Tulosta paperit")."'>";
		$nappikeikka .= "$formloppu";
	}

	// jos on kohdistettuja rivejä ja lisätiedot on syötetty ja varastopaikat on ok ja on vielä jotain vietävää varastoon
	if ($yhtiorow['suuntalavat'] != 'S' and $kplyhteensa > 0 and $varok == 1 and $kplyhteensa != $kplvarasto and $sarjanrook == 1) {
		$nappikeikka .= "$formalku";
		$nappikeikka .= "<input type='hidden' name='toiminto' value='kalkyyli'>";
		$nappikeikka .= "<input type='submit' value='".t("Vie varastoon")."'>";
		$nappikeikka .= "$formloppu";
	}

	if ($yhtiorow['suuntalavat'] == 'S') {
		$nappikeikka .= "$formalku";
		$nappikeikka .= "<input type='hidden' name='toiminto' value='suuntalavat'>";
		$nappikeikka .= "<input type='submit' value='".t("Suuntalavat")."'>";
		$nappikeikka .= "$formloppu";
	}

	$nappikeikka .=	"</tr></table>";
	$nappikeikka = str_replace('\n','',$nappikeikka);
}

echo "<SCRIPT LANGUAGE=JAVASCRIPT>
nappikeikka = \"$nappikeikka\";
document.getElementById('toimnapit').innerHTML = nappikeikka;
</SCRIPT>";

if (strpos($_SERVER['SCRIPT_NAME'], "keikka.php")  !== FALSE) {
	require ("inc/footer.inc");
}

?>