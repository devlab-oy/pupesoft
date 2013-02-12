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
	function tsekit($row, $kaikkivarastossayhteensa, $kaikkiliitettyyhteensa) {

		global $kukarow, $yhtiorow;

		$tsekit = array();

		$query_ale_lisa = generoi_alekentta("O");

		// tutkitaan onko kaikilla tuotteilla on joku varastopaikka
		$query  = "	SELECT tilausrivi.*,
					if(kpl!=0, rivihinta, 0) rivihinta,
					((varattu+kpl) * hinta * {$query_ale_lisa}) rivihinta_liitetty
					FROM tilausrivi USE INDEX (uusiotunnus_index)
					WHERE yhtio 	= '$kukarow[yhtio]'
					and uusiotunnus = '$row[tunnus]'
					and tyyppi 		= 'O'";
		$tilres = pupe_query($query);

		$kplyhteensa 	= 0;
		$kplvarasto  	= 0;
		$eipaikkoja  	= 0;
		$eituotteet  	= "";
		$varastossaarvo = 0;
		$liitettyarvo	= 0;
		$uusiot 		= array();

		while ($rivirow = mysql_fetch_assoc($tilres)) {
			$query = "	SELECT *
						FROM tuote
						WHERE tuoteno = '$rivirow[tuoteno]'
						and yhtio = '$kukarow[yhtio]'";
			$tuore = pupe_query($query);
			$tuote = mysql_fetch_assoc($tuore);

			if (!in_array($rivirow["otunnus"], $uusiot)) {
				$uusiot[] = $rivirow["otunnus"];
			}

			$kplyhteensa++; // lasketaan montako tilausrivi‰ on kohdistettu

			$varastossaarvo 			+= $rivirow["rivihinta"];
			$liitettyarvo				+= $rivirow["rivihinta_liitetty"];
			$kaikkivarastossayhteensa 	+= $rivirow["rivihinta"];
			$kaikkiliitettyyhteensa 	+= $rivirow["rivihinta_liitetty"];

			if (($rivirow["kpl"] != 0 and $rivirow["varattu"] == 0) or ($rivirow["kpl"] == 0 and $rivirow["varattu"] == 0)) {
				$kplvarasto++; // lasketaan montako tilausrivi‰ on viety varastoon
			}

			// jos kyseess‰ on saldollinen tuote
			if ($tuote['ei_saldoa'] == "") {
				//jos rivi on jo viety varastoon niin ei en‰‰ katota sen paikkaa
				if ($rivirow["kpl"] == 0 and $rivirow["varattu"] != 0) {
					// katotaan lˆytyykˆ tuotteelta varastopaikka joka on tilausriville tallennettu
					$query = "	SELECT *
								from tuotepaikat use index (tuote_index)
								where tuoteno = '$rivirow[tuoteno]'
								and yhtio	  = '$kukarow[yhtio]'
								and hyllyalue = '$rivirow[hyllyalue]'
								and hyllynro  = '$rivirow[hyllynro]'
								and hyllytaso = '$rivirow[hyllytaso]'
								and hyllyvali = '$rivirow[hyllyvali]'";
					$tpres = pupe_query($query);

					if (mysql_num_rows($tpres) == 0) {
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

		// tutkitaan onko kaikki lis‰tiedot syˆtetty vai ei...
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
				$tunken = "sarjanumeroseuranta.myyntirivitunnus";
			}
			else {
				$tunken = "sarjanumeroseuranta.ostorivitunnus";
			}

			if ($toimrow["sarjanumeroseuranta"] == "S" or $toimrow["sarjanumeroseuranta"] == "U" or $toimrow["sarjanumeroseuranta"] == "V") {
				$query = "	SELECT count(distinct sarjanumero) kpl, min(sarjanumero) sarjanumero
							FROM sarjanumeroseuranta
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$toimrow[tuoteno]'
							and $tunken = '$toimrow[tunnus]'";
			}
			else {
				$query = "	SELECT sum(sarjanumeroseuranta.era_kpl*if(tilausrivi.tunnus is not null, if(tilausrivi.kpl+tilausrivi.varattu+tilausrivi.jt > 0 or tilausrivi.tyyppi = 'O', 1, -1), 1)) kpl, min(sarjanumeroseuranta.sarjanumero) sarjanumero
							FROM sarjanumeroseuranta
							LEFT JOIN tilausrivi ON (tilausrivi.yhtio = sarjanumeroseuranta.yhtio and tilausrivi.tunnus = sarjanumeroseuranta.myyntirivitunnus)
							WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
							and sarjanumeroseuranta.tuoteno = '$toimrow[tuoteno]'
							and $tunken = '$toimrow[tunnus]'";
			}
			$sarjares = pupe_query($query);
			$sarjarow = mysql_fetch_assoc($sarjares);

			// pit‰‰ olla yht‰monta sarjanumeroa liitettyn‰ kun kamaa viety varastoon
			if ($sarjarow["kpl"] != abs($toimrow["kpl"])) {
				$sarjanrook++;
			}
		}

		// pit‰‰ olla yht‰monta sarjanumeroa liitettyn‰ kun kamaa viety varastoon
		if ($sarjanrook == 1) {
			$sarjanrook	= 1;
			$sarjanrot	= "<font class='ok'>".t("ok")."</font>";
		}
		else {
			$sarjanrook	= 0; // ei ole kaikilla tuotteilla sarjanumeroa
			$sarjanrot	= "kesken";
		}

		// katotaan onko liitettyj‰ laskuja
		// ('C','F','I','J','K','L') // vaihto-omaisuus ja raaka-aine
		// ('B','C','J','E','F','K','H','I','L') // kaikki
		$query = "	SELECT count(*) num,
					sum(if(lasku.vienti in ('C','F','I','J','K','L'), 1, 0)) volasku,
					sum(if(ostores_lasku.tila != 'H' and lasku.vienti in ('C','F','I','J','K','L'), 1, 0)) volasku_ok,
					sum(if(lasku.vienti not in ('C','F','I','J','K','L'), 1, 0)) kulasku,
					sum(if(ostores_lasku.tila != 'H' and lasku.vienti not in ('C','F','I','J','K','L'), 1, 0)) kulasku_ok,
					round(sum(if(ostores_lasku.vienti in ('C','F','I','J','K','L'), lasku.arvo * lasku.vienti_kurssi, 0)), 2) vosumma,
					round(sum(if(ostores_lasku.vienti in ('C','F','I','J','K','L'), lasku.arvo, 0)), 2) vosumma_valuutassa,
					round(sum(if(ostores_lasku.vienti in ('C','F','I','J','K','L'), lasku.summa * lasku.vienti_kurssi, 0)), 2) voverosumma,
					round(sum(if(ostores_lasku.vienti in ('C','F','I','J','K','L'), lasku.summa, 0)), 2) voverosumma_valuutassa,
					round(sum(if(lasku.vienti not in ('C','F','I','J','K','L'), lasku.arvo * lasku.vienti_kurssi, 0)),2) kusumma,
					round(sum(if(lasku.vienti not in ('C','F','I','J','K','L'), lasku.arvo, 0)),2) kusumma_valuutassa
					FROM lasku use index (yhtio_tila_laskunro)
					JOIN lasku ostores_lasku ON (ostores_lasku.yhtio = lasku.yhtio AND ostores_lasku.tunnus = lasku.vanhatunnus)
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					AND lasku.tila = 'K'
					AND lasku.vanhatunnus <> 0
					AND lasku.laskunro = '$row[laskunro]'";
		$llres = pupe_query($query);
		$llrow = mysql_fetch_assoc($llres);

		if ((abs($row['rahti_etu']) > abs($llrow['vosumma_valuutassa'])) and $llrow['volasku'] > 0) {
			$lisok = 0;
			$lisatiedot = t("kesken");
		}

		return array($kaikkivarastossayhteensa,$kaikkiliitettyyhteensa,$kohdistus,$kohok,$kplvarasto,$kplyhteensa,$lisatiedot,$lisok,$llrow,$sarjanrook,$sarjanrot,$uusiot,$varastopaikat,$varastossaarvo,$liitettyarvo,$varok);
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

		$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mit‰tˆitiin ohjelmassa keikka.php")."<br>";

		// Mit‰tˆid‰‰n saapuminen
		$query  = "UPDATE lasku SET alatila = tila, tila = 'D', comments = '$komm' where yhtio='$kukarow[yhtio]' and tila='K' and laskunro='$keikkaid'";
		$result = pupe_query($query);

		// Mit‰tˆid‰‰n keikalle suoraan "lis‰tyt" rivit eli otunnus=keikan tunnus ja uusiotunnus=0
		$query  = "UPDATE tilausrivi SET tyyppi = 'D' where yhtio='$kukarow[yhtio]' and tyyppi='O' and otunnus='$otunnus' and uusiotunnus=0";
		$result = pupe_query($query);

		// Siirret‰‰n t‰lle kiekalle lis‰tyt sille keikalle jolle ne on kohdistettu
		$query  = "UPDATE tilausrivi SET otunnus=uusiotunnus where yhtio='$kukarow[yhtio]' and tyyppi='O' and otunnus='$otunnus' and uusiotunnus>0";
		$result = pupe_query($query);

		// formissa on tullut myˆs $ytunnus, joten n‰in p‰‰st‰‰n takaisin selaukseen
		$toiminto = "";
	}
	else {
		echo "<font class='error'>".t("VIRHE: Saapumiseen on jo liitetty laskuja tai kohdistettu rivej‰, sit‰ ei voi poistaa")."!<br>";
		// formissa on tullut myˆs $ytunnus, joten n‰in p‰‰st‰‰n takaisin selaukseen
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

			$on_jo_lava = trim($valitut_lavat) != "" ? true : false;
			$valitut_lavat = $on_jo_lava ? $valitut_lavat : "";

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

				$valitut_lavat = $on_jo_lava ? $valitut_lavat : $check_row["suuntalavat"];
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

				if (!$on_jo_lava) {
					$valitut_lavat = trim($valitut_lavat) != "" ? $valitut_lavat.",".$check_row["suuntalavat"] : $check_row["suuntalavat"];
				}
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

// syˆtet‰‰n keikan lis‰tietoja
if ($toiminto == "lisatiedot") {
	require ("ostotilauksen_lisatiedot.inc");
}

// chekataan tilauksen varastopaikat
if ($toiminto == "varastopaikat") {
	require('ostorivienvarastopaikat.inc');
}

// lis‰ill‰‰n saapumiseen kululaskuja
if ($toiminto == "kululaskut") {
	$keikanalatila 	= "";

	require('kululaskut.inc');
}

if ($toiminto == 'kalkyyli' and $yhtiorow['suuntalavat'] == 'S' and $tee == '' and trim($suuntalavan_tunnus) != '' and trim($koko_suuntalava) == 'X') {
	if ((isset($suuntalavan_hyllyalue) and trim($suuntalavan_hyllyalue) == '') or (isset($suuntalavan_hyllypaikka) and trim($suuntalavan_hyllypaikka) == '')) {
		echo "<font class='error'>",t("Hyllyalue oli tyhj‰"),"!</font><br />";
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

		# Koko suuntalava voidaan vied‰ vain reservipaikalle, jossa ei ole tuotteita.
		$options = array('reservipaikka' => 'K');
		$hyllypaikka_ok = tarkista_varaston_hyllypaikka($suuntalavan_hyllyalue, $suuntalavan_hyllynro, $suuntalavan_hyllyvali, $suuntalavan_hyllytaso, $options);

		# Hyllypaikkaa ei lˆydy tai se ei ole reservipaikka
		if (!$hyllypaikka_ok) {
			echo "<font class='error'>".t("Hyllypaikkaa ei lˆydy tai se ei ole reservipaikka")."</font></br>";

			# Takaisin samaan n‰kym‰‰n
			$toiminto = 'suuntalavat';
			$tee = 'vie_koko_suuntalava';
		}
		else {
			# OK, p‰ivitet‰‰n tilausrivien hyllypaikat
			$paivitetyt_rivit = paivita_hyllypaikat($suuntalavan_tunnus,
													$suuntalavan_hyllyalue,
													$suuntalavan_hyllynro,
													$suuntalavan_hyllyvali,
													$suuntalavan_hyllytaso);

			if ($paivitetyt_rivit > 0) {
				echo "<br />",t("P‰ivitettiin suuntalavan tuotteet paikalle")," {$suuntalavan_hyllyalue} {$suuntalavan_hyllynro} {$suuntalavan_hyllyvali} {$suuntalavan_hyllytaso}<br />";
				$vietiinko_koko_suuntalava = 'joo';
			}
		}
	}
}

if ($toiminto == 'suuntalavat') {
	require('suuntalavat.inc');
}

if ($toiminto == 'tulosta_sscc') {
	require('tulosta_sscc.inc');
}

// tehd‰‰n errorichekkej‰ jos on varastoonvienti kyseess‰
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
		$toiminto = "dummieimit‰‰n";
	}
}

// lasketaan lopullinen varastonarvo
if ($toiminto == "kaikkiok") {
	require ("varastonarvo_historia.inc");
}

// vied‰‰n keikka varastoon
if ($toiminto == "kalkyyli") {
	require ("kalkyyli.inc");
}

if (isset($nappikeikalla) and $nappikeikalla == 'ollaan' and $toiminto != 'kohdista') {
	$toiminto = "kohdista";
}

if (isset($messenger) and $messenger == 'X' and isset($message) and trim($message) != "" and isset($vastaanottaja) and trim($vastaanottaja) != "" and isset($status) and $status == 'X') {

	$message = trim($message);

	$message .= " {$message_postfix}";

	$query = "	INSERT INTO messenger SET
				yhtio = '{$kukarow['yhtio']}',
				kuka = '{$kukarow['kuka']}',
				vastaanottaja = '{$vastaanottaja}',
				viesti = '{$message}',
				status = '{$status}',
				luontiaika = now()";
	$messenger_result = pupe_query($query);

	echo "<font class='message'>",sprintf(t('Viesti l‰hetetty onnistuneesti k‰ytt‰j‰lle %s.'), $vastaanottaja)."</font><br />";

	$messenger = $message = $vastaanottaja = $status = "";
}

if ($toiminto == "kohdista") {
	require('ostotilausten_rivien_kohdistus.inc');
}

// Haku
if ($ytunnus == "" and $keikka != "") {
	$keikka = (int) $keikka;

	$query = "	SELECT ytunnus, liitostunnus, laskunro
				FROM lasku USE INDEX (yhtio_tila_laskunro)
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
				JOIN lasku ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus and lasku.tila = 'K' and lasku.alatila = '' and lasku.vanhatunnus = 0)
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
			 	and tilausrivi.otunnus = $ostotil
				and tilausrivi.tyyppi = 'O'";
	$keikkahaku_res = pupe_query($query);
	$keikkahaku_row = mysql_fetch_assoc($keikkahaku_res);

	if ($keikkahaku_row['laskunro'] != '') {
		$keikkarajaus = $keikkahaku_row["laskunro"];
		$ytunnus 	  = $keikkahaku_row["ytunnus"];
		$toimittajaid = $keikkahaku_row["liitostunnus"];
	}
	else {
		// Napataan kuitenkin toimittajan keikat auki
		$query = "	SELECT lasku.ytunnus, lasku.liitostunnus
					FROM tilausrivi
					JOIN lasku ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and lasku.tila = 'O')
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				 	and tilausrivi.otunnus = $ostotil
					and tilausrivi.tyyppi = 'O'
					LIMIT 1";
		$keikkahaku_res = pupe_query($query);

		if (mysql_num_rows($keikkahaku_res) > 0) {

			echo "<font class='error'>".t("HUOM: Haettua ostotilausta ei lˆytynyt saapumisilta. N‰ytet‰‰n toimittajan kaikki avoimet saapumiset")."!</font><br><br>";

			$keikkahaku_row = mysql_fetch_assoc($keikkahaku_res);

			$ytunnus 	  = $keikkahaku_row["ytunnus"];
			$toimittajaid = $keikkahaku_row["liitostunnus"];
		}

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

// N‰ytet‰‰n kaikkien toimittajien keskener‰iset saapumiset
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
			echo "<th>",t("Rajaa laatijaa ker‰ysvyˆhykkeell‰"),"</th>";
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
		echo "<th>",t("N‰yt‰ vain saapumiset, joilla on siirtovalmiita suuntalavoja"),"</th>";

		$chk = trim($nayta_siirtovalmiit_suuntalavat) != '' ? ' checked' : '';

		echo "<td><input type='checkbox' name='nayta_siirtovalmiit_suuntalavat' {$chk} onchange='submit();'></td>";
	}

	echo "</tr>";
	echo "<tr>";
	echo "<th>",t("Lis‰rajaus"),"</th>";

	if (!isset($lisarajaus)) $lisarajaus = "";

	$sel = array_fill_keys(array($lisarajaus), ' selected') + array_fill_keys(array('riveja_viematta_varastoon', 'liitetty_lasku', 'liitetty_lasku_rivitok_kohdistus_eiok', 'liitetty_lasku_rivitok_kohdistus_ok'), '');

	echo "<td><select name='lisarajaus' ",js_alasvetoMaxWidth('lisarajaus', 250),">";
	echo "<option value=''>",t("N‰yt‰ kaikki"),"</option>";
	echo "<option value='riveja_viematta_varastoon'{$sel['riveja_viematta_varastoon']}>",t("Saapumiset joissa on rivej‰ viem‰tt‰ varastoon"),"</option>";
	echo "<option value='liitetty_lasku'{$sel['liitetty_lasku']}>",t("Saapumiset joihin on liitetty lasku"),"</option>";
	echo "<option value='liitetty_lasku_rivitok_kohdistus_eiok'{$sel['liitetty_lasku_rivitok_kohdistus_eiok']}>",t("Saapumiset joihin on liitetty lasku ja kaikki rivit on viety varastoon ja kohdistus ei ole ok"),"</option>";
	echo "<option value='liitetty_lasku_rivitok_kohdistus_ok'{$sel['liitetty_lasku_rivitok_kohdistus_ok']}>",t("Saapumiset joihin on liitetty lasku ja kaikki rivit on viety varastoon ja kohdistus on ok"),"</option>";
	echo "</select></td>";

	echo "<td class='back'><input type='submit' value='".t("Hae")."'></td>";
	echo "</tr>";

	echo "</table>";
	echo "</form>";

	// kursorinohjausta
	$formi    = "toimi";
	$kentta   = "ytunnus";
	$toiminto = "";

	$kaikkivarastossayhteensa 		= 0;
	$kaikkiliitettyyhteensa			= 0;
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

	$query_ale_lisa = generoi_alekentta("O");

	$joinlisa = "";

	if ($lisarajaus == 'liitetty_lasku') {
		$joinlisa = "JOIN lasku AS liitetty_lasku ON (liitetty_lasku.yhtio = lasku.yhtio AND liitetty_lasku.tila = 'K' AND liitetty_lasku.laskunro = lasku.laskunro AND liitetty_lasku.vanhatunnus <> 0 AND liitetty_lasku.vienti IN ('C','F','I','J','K','L'))";
	}

	// n‰ytet‰‰n mill‰ toimittajilla on keskener‰isi‰ keikkoja
	$query = "	SELECT lasku.liitostunnus,
				max(lasku.ytunnus)  ytunnus,
				max(lasku.nimi)     nimi,
				max(lasku.nimitark) nimitark,
				max(lasku.osoite)   osoite,
				max(lasku.postitp)  postitp,
				group_concat(distinct if(lasku.comments!='',lasku.comments,NULL) SEPARATOR '<br><br>') comments,
				count(distinct lasku.tunnus) kpl,
				group_concat(distinct lasku.laskunro SEPARATOR ', ') keikat,
				round(sum(if(tilausrivi.kpl!=0, tilausrivi.rivihinta, 0)),2) varastossaarvo,
				round(sum((tilausrivi.varattu+tilausrivi.kpl) * tilausrivi.hinta * {$query_ale_lisa}),2) kohdistettuarvo,
				GROUP_CONCAT(DISTINCT lasku.tunnus) tilauksien_tunnukset
				FROM lasku USE INDEX (yhtio_tila_mapvm)
				LEFT JOIN tilausrivi USE INDEX (uusiotunnus_index) on (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus and tilausrivi.tyyppi = 'O')
				{$joinlisa}
				{$suuntalavajoin}
				WHERE lasku.yhtio 	  = '$kukarow[yhtio]'
				and lasku.tila 		  = 'K'
				and lasku.alatila 	  = ''
				and lasku.vanhatunnus = 0
				and lasku.mapvm 	  = '0000-00-00'
				$laatijalisa
				GROUP BY lasku.liitostunnus
				ORDER BY lasku.nimi, lasku.nimitark, lasku.ytunnus";
	$result = pupe_query($query);

	if (mysql_num_rows($result) > 0) {

		echo "<br><font class='head'>".t("Keskener‰iset saapumiset")."</font><hr>";

		echo "<table>";
		echo "<tr><th>".t("ytunnus")."</th><th>&nbsp;</th><th>".t("nimi")."</th><th>".t("osoite")."</th><th>".t("saapumisnumerot")."</th><th>".t("kpl")."</th><th>".t("varastonarvo")."</th><td class='back'></td></tr>";

		while ($row = mysql_fetch_assoc($result)) {

			$row_keikat = array();

			if ($lisarajaus == 'riveja_viematta_varastoon' or $lisarajaus == 'liitetty_lasku_rivitok_kohdistus_eiok' or $lisarajaus == 'liitetty_lasku_rivitok_kohdistus_ok') {

				if ($lisarajaus == 'riveja_viematta_varastoon') {
					$havinglisa = "HAVING kpl IS NOT NULL AND (kpl > 0 OR kpl = 0) AND varattu > 0";
				}
				else {
					$havinglisa = "HAVING kpl IS NOT NULL AND kpl > 0 AND varattu = 0";
				}

				$query = "	SELECT lasku.laskunro, SUM(tilausrivi.kpl) kpl, SUM(tilausrivi.varattu) varattu
							FROM tilausrivi
							JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.uusiotunnus)
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.uusiotunnus IN ({$row['tilauksien_tunnukset']})
							AND tilausrivi.tyyppi = 'O'
							GROUP BY 1
							{$havinglisa}";
				$tilriv_chk_res = pupe_query($query);

				if (mysql_num_rows($tilriv_chk_res) == 0) continue;

				while ($tilriv_chk_row = mysql_fetch_assoc($tilriv_chk_res)) {
					$row_keikat[$tilriv_chk_row['laskunro']] = $tilriv_chk_row['laskunro'];
				}

				$row['keikat'] = implode(",", $row_keikat);
			}
			else {
				$row_keikat = explode(',', $row['keikat']);
			}

			$query = "	SELECT tunnus, laskunro,
						count(*) num,
						sum(if(vienti in ('C','F','I','J','K','L'), 1, 0)) volasku,
						sum(if(vienti not in ('C','F','I','J','K','L'), 1, 0)) kulasku,
						sum(if(vienti in ('C','F','I','J','K','L'), arvo * vienti_kurssi, 0)) vosumma,
						sum(if(vienti not in ('C','F','I','J','K','L'), arvo * vienti_kurssi, 0)) kusumma
						FROM lasku use index (yhtio_tila_laskunro)
						WHERE yhtio 	= '$kukarow[yhtio]'
						AND tila 		= 'K'
						AND vanhatunnus > 0
						AND laskunro 	IN ({$row['keikat']})
						GROUP BY 1,2";
			$laskuja_result = pupe_query($query);

			$summat_row = array(
				'volasku' => 0,
				'kulasku' => 0,
				'vosumma' => 0,
				'kusumma' => 0
			);

			$oliko_ok = ($lisarajaus == 'liitetty_lasku_rivitok_kohdistus_eiok' or $lisarajaus == 'liitetty_lasku_rivitok_kohdistus_ok') ? false : true;

			if ($lisarajaus == 'liitetty_lasku_rivitok_kohdistus_eiok' or $lisarajaus == 'liitetty_lasku_rivitok_kohdistus_ok') {
				$oliko_ok = false;
				$pitaisi_kayda_nama_tunnukset_lapi = explode(",", $row['keikat']);
			}
			else {
				$oliko_ok = true;
				$pitaisi_kayda_nama_tunnukset_lapi = array();
			}

			while ($laskuja_row = mysql_fetch_assoc($laskuja_result)) {

				if ($lisarajaus == 'liitetty_lasku_rivitok_kohdistus_eiok' or $lisarajaus == 'liitetty_lasku_rivitok_kohdistus_ok') {

					if (in_array($laskuja_row['laskunro'], $pitaisi_kayda_nama_tunnukset_lapi)) unset($pitaisi_kayda_nama_tunnukset_lapi[array_search($laskuja_row['laskunro'], $pitaisi_kayda_nama_tunnukset_lapi)]);

					$query = "	SELECT ROUND(SUM(summa), 2) summa
								FROM lasku
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tunnus = '{$laskuja_row['tunnus']}'";
					$sum_chk_res = pupe_query($query);
					$sum_chk_row = mysql_fetch_assoc($sum_chk_res);

					$erotus_chk = abs(round($sum_chk_row['summa'] - $laskuja_row['vosumma'], 2));

					$query = "	SELECT kohdistettu, tunnus
								FROM lasku
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND laskunro = '{$laskuja_row['laskunro']}'
								AND tunnus IN ({$row['tilauksien_tunnukset']})
								and tila 		= 'K'
								and alatila 	= ''
								and vanhatunnus	= 0
								and mapvm 		= '0000-00-00'";
					$kohdistettu_chk_res = pupe_query($query);
					$kohdistettu_chk_row = mysql_fetch_assoc($kohdistettu_chk_res);

					if ($lisarajaus == 'liitetty_lasku_rivitok_kohdistus_eiok' and ($kohdistettu_chk_row['kohdistettu'] == 'K' or ($sum_chk_row['summa'] != $laskuja_row['vosumma'] and $erotus_chk > 0.01))) {
						unset($row_keikat[$laskuja_row['laskunro']]);
						continue;
					}

					if ($lisarajaus == 'liitetty_lasku_rivitok_kohdistus_ok' and ($kohdistettu_chk_row['kohdistettu'] == '' or ($sum_chk_row['summa'] != $laskuja_row['vosumma'] and $erotus_chk > 0.01))) {
						unset($row_keikat[$laskuja_row['laskunro']]);
						continue;
					}

					$oliko_ok = true;
				}

				$summat_row['volasku'] += $laskuja_row['volasku'];
				$summat_row['kulasku'] += $laskuja_row['kulasku'];
				$summat_row['vosumma'] += $laskuja_row['vosumma'];
				$summat_row['kusumma'] += $laskuja_row['kusumma'];
			}

			if (count($pitaisi_kayda_nama_tunnukset_lapi) > 0) {
				foreach ($pitaisi_kayda_nama_tunnukset_lapi as $pitais_kayda_tun) {
					unset($row_keikat[$pitais_kayda_tun]);
				}
			}

			if (!$oliko_ok or count($row_keikat) == 0) continue;

			$row['keikat'] = implode(", ", $row_keikat);

			$kaikkivarastossayhteensa += $row["varastossaarvo"];
			$vaihtoomaisuuslaskujayhteensa += $summat_row["vosumma"];
			$kululaskujayhteensa += $summat_row["kusumma"];
			$kaikkiliitettyyhteensa 		+= $row["kohdistettuarvo"];

			echo "<tr class='aktiivi'>";
			echo "<td valign='top'>$row[ytunnus]</td>";

			// tehd‰‰n pop-up divi jos keikalla on kommentti...
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

			echo "<td>$row[nimi] $row[nimitark]</td><td>$row[osoite] $row[postitp]</td><td>$row[keikat]</td><td align='right'>$row[kpl]</td><td align='right'>$row[varastossaarvo]</td>";
			echo "<td class='back'><form method='post'>";
			echo "<input type='hidden' name='toimittajaid' value='$row[liitostunnus]'>";
			echo "<input type='hidden' name='lisarajaus' value='{$lisarajaus}' />";
			echo "<input type='submit' value='".t("Valitse")."'>";
			echo "</form></td>";
			echo "</tr>";
		}

		echo "</table>";

		if (isset($naytalaskelma) and $naytalaskelma != "") {
			list (	$liitetty_lasku_viety_summa,
					$ei_liitetty_lasku_viety_summa,
					$liitetty_lasku_ei_viety_summa,
					$ei_liitetty_lasku_ei_viety_summa,
					$liitetty_lasku_osittain_viety_summa,
					$ei_liitetty_lasku_osittain_viety_summa,
					$laskut_ei_viety,
					$laskut_viety,
					$laskut_osittain_viety,
					$row_vaihto
					) = hae_yhteenveto_tiedot($toimittajaid);

			$params = array(
				'kaikkivarastossayhteensa'				 => $kaikkivarastossayhteensa,
				'kaikkiliitettyyhteensa'				 => $kaikkiliitettyyhteensa,
				'vaihtoomaisuuslaskujayhteensa'			 => $vaihtoomaisuuslaskujayhteensa,
				'row_vaihto'							 => $row_vaihto,
				'kululaskujayhteensa'					 => $kululaskujayhteensa,
				'liitetty_lasku_ei_viety_summa'			 => $liitetty_lasku_ei_viety_summa,
				'ei_liitetty_lasku_ei_viety_summa'		 => $ei_liitetty_lasku_ei_viety_summa,
				'laskut_ei_viety'						 => $laskut_ei_viety,
				'liitetty_lasku_viety_summa'			 => $liitetty_lasku_viety_summa,
				'ei_liitetty_lasku_viety_summa'			 => $ei_liitetty_lasku_viety_summa,
				'laskut_viety'							 => $laskut_viety,
				'liitetty_lasku_osittain_viety_summa'	 => $liitetty_lasku_osittain_viety_summa,
				'ei_liitetty_lasku_osittain_viety_summa' => $ei_liitetty_lasku_osittain_viety_summa,
				'laskut_osittain_viety'					 => $laskut_osittain_viety,
			);

			echo_yhteenveto_table($params);
		}
		else {
			echo "<br><form name='toimi' method='post' autocomplete='off'>";
			echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
			echo "<input type='hidden' name='naytalaskelma' value='JOO'>";
			echo "<input type='submit' value='".t("N‰yt‰ varastonarvolaskelma")."'>";
			echo "</form>";
		}
	}
}

// perustetaan uusi keikka toimittajalle $ytunnus
if ($toiminto == "uusi" and $toimittajaid > 0) {

	# Toiminta funktioitu
	$result = uusi_saapuminen($toimittajarow);

	// selaukseen
	$toiminto = "";
}

// selataan toimittajan keikkoja
if ($toiminto == "" and (($ytunnus != "" or $keikkarajaus != '') and $toimittajarow["ytunnus"] != '')) {

	// n‰ytet‰‰n v‰h‰ toimittajan tietoja
	echo "<table>";
	echo "<tr>";
	echo "<th colspan='5'>".t("Toimittaja")."</th>";
	echo "</tr><tr>";
	echo "<td>$toimittajarow[ytunnus]</td>";
	echo "<td>$toimittajarow[nimi]</td>";
	echo "<td>$toimittajarow[osoite]</td>";
	echo "<td>$toimittajarow[postino]</td>";
	echo "<td>$toimittajarow[postitp]</td>";

	echo "<td class='back' style='vertical-align:bottom;'>";
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

	if (!isset($lisarajaus)) $lisarajaus = "";

	$sel = array_fill_keys(array($lisarajaus), ' selected') + array_fill_keys(array('riveja_viematta_varastoon', 'liitetty_lasku', 'liitetty_lasku_rivitok_kohdistus_eiok', 'liitetty_lasku_rivitok_kohdistus_ok'), '');

	echo "<tr>";
	echo "<th>",t("Lis‰rajaus"),"</th>";
	echo "<td colspan='4'>";
	echo "<form method='post'>";
	echo "<input type='hidden' name='toiminto' value=''>";
	echo "<input type='hidden' name='toimittajaid' value='{$toimittajaid}'>";
	echo "<select name='lisarajaus' ",js_alasvetoMaxWidth('lisarajaus', 250)," onchange='submit();'>";
	echo "<option value=''>",t("N‰yt‰ kaikki"),"</option>";
	echo "<option value='riveja_viematta_varastoon'{$sel['riveja_viematta_varastoon']}>",t("Saapumiset joissa on rivej‰ viem‰tt‰ varastoon"),"</option>";
	echo "<option value='liitetty_lasku'{$sel['liitetty_lasku']}>",t("Saapumiset joihin on liitetty lasku"),"</option>";
	echo "<option value='liitetty_lasku_rivitok_kohdistus_eiok'{$sel['liitetty_lasku_rivitok_kohdistus_eiok']}>",t("Saapumiset joihin on liitetty lasku ja kaikki rivit on viety varastoon ja kohdistus ei ole ok"),"</option>";
	echo "<option value='liitetty_lasku_rivitok_kohdistus_ok'{$sel['liitetty_lasku_rivitok_kohdistus_ok']}>",t("Saapumiset joihin on liitetty lasku ja kaikki rivit on viety varastoon ja kohdistus on ok"),"</option>";
	echo "</select></form></td></tr>";

	echo "</table><br />";

	// etsit‰‰n vanhoja keikkoja, vanhatunnus pit‰‰ olla tyhj‰‰ niin ei listata liitettyj‰ laskuja
	$query = "	SELECT *
				FROM lasku USE INDEX (tila_index)
				where lasku.yhtio = '$kukarow[yhtio]'
				and lasku.liitostunnus = '$toimittajaid'
				and lasku.tila 		   = 'K'
				and lasku.alatila 	   = ''
				and lasku.vanhatunnus  = 0
				#and lasku.mapvm 	   = '0000-00-00'
				ORDER BY lasku.laskunro DESC";
	$result = pupe_query($query);

	echo "<font class='head'>".t("Toimittajan keskener‰iset saapumiset")."</font><hr>";

	if (mysql_num_rows($result) > 0) {

		pupe_DataTables(array(array($pupe_DataTables, 9, 9, false)));

		echo "<table class='display dataTable' id='{$pupe_DataTables}'>";
		echo "<thead>";
		echo "<tr>";
		echo "<th valign='top'>".t("saapuminen")."</th>";
		echo "<th valign='top'>&nbsp;</th>";
		echo "<th valign='top'>".t("ytunnus")." /<br>".t("nimi")."</th>";
		echo "<th valign='top'>".t("kohdistus")." /<br>".t("lis‰tiedot")."</th>";
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
		$kaikkiliitettyyhteensa			= 0;
		$vaihtoomaisuuslaskujayhteensa 	= 0;
		$kululaskujayhteensa 			= 0;

		while ($row = mysql_fetch_assoc($result)) {

			if ($lisarajaus == 'liitetty_lasku') {
				$query = "	SELECT tunnus
							FROM lasku
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tila = 'K'
							AND laskunro = '{$row['laskunro']}'
							AND vanhatunnus <> 0
							AND vienti IN ('C','F','I','J','K','L')";
				$lasku_chk_res = pupe_query($query);
				if (mysql_num_rows($lasku_chk_res) == 0) continue;
			}

			if ($lisarajaus == 'riveja_viematta_varastoon' or $lisarajaus == 'liitetty_lasku_rivitok_kohdistus_eiok' or $lisarajaus == 'liitetty_lasku_rivitok_kohdistus_ok') {

				if ($lisarajaus == 'riveja_viematta_varastoon') {
					$havinglisa = "HAVING kpl IS NOT NULL AND (kpl > 0 OR kpl = 0) AND varattu > 0";
				}
				else {
					$havinglisa = "HAVING kpl IS NOT NULL AND kpl > 0 AND varattu = 0";
				}

				$query = "	SELECT SUM(kpl) kpl, SUM(varattu) varattu
							FROM tilausrivi
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND uusiotunnus = '{$row['tunnus']}'
							AND tyyppi = 'O'
							{$havinglisa}";
				$tilriv_chk_res = pupe_query($query);

				if (mysql_num_rows($tilriv_chk_res) == 0) continue;
			}

			list($kaikkivarastossayhteensa,$kaikkiliitettyyhteensa,$kohdistus,$kohok,$kplvarasto,$kplyhteensa,$lisatiedot,$lisok,$llrow,$sarjanrook,$sarjanrot,$uusiot,$varastopaikat,$varastossaarvo,$liitettyarvo,$varok) = tsekit($row,$kaikkivarastossayhteensa,$kaikkiliitettyyhteensa);
			$vaihtoomaisuuslaskujayhteensa += $llrow["vosumma"];
			$kululaskujayhteensa += $llrow["kusumma"];

			if ($lisarajaus == 'liitetty_lasku_rivitok_kohdistus_eiok' or $lisarajaus == 'liitetty_lasku_rivitok_kohdistus_ok') {
				if ($llrow['num'] == 0 or ($lisarajaus == 'liitetty_lasku_rivitok_kohdistus_eiok' and $kohok == 1) or ($lisarajaus == 'liitetty_lasku_rivitok_kohdistus_ok' and $kohok == 0)) continue;
			}

			echo "<tr class='aktiivi'>";

			echo "<td valign='top'>$row[laskunro]</td>";

			// tehd‰‰n pop-up divi jos keikalla on kommentti...
			if ($row["comments"] != "") {

				$query = "	SELECT nimi
							FROM kuka
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND kuka = '{$row['laatija']}'";
				$kuka_chk_res = pupe_query($query);
				$kuka_chk_row = mysql_fetch_assoc($kuka_chk_res);

				echo "<td valign='top' class='tooltip' id='$row[laskunro]'>";
				echo "<div id='div_$row[laskunro]' class='popup' style='width:500px;'>";
				echo t("Saapuminen").": $row[laskunro] / $row[nimi]<br><br>";
				echo t("Laatija"),": {$kuka_chk_row['nimi']}<br />";
				echo t("Luontiaika"),": ",tv1dateconv($row['luontiaika'], "pitk‰"),"<br /><br />";
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
					$laskujen_tiedot .= t("Lasku")." $volasrow[nimi] ($volasrow[summa] $volasrow[valkoodi]) ".t("hyv‰ksytt‰v‰n‰ k‰ytt‰j‰ll‰")." $volasrow[kukanimi]<br>";
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

			// jos t‰t‰ keikkaa ollaan just viem‰ss‰ varastoon ei tehd‰ dropdownia
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
				echo "<input type='hidden' name='lisarajaus'	value='{$lisarajaus}' />";
				echo "<select name='toiminto'>";

				// n‰it‰ saa tehd‰ aina keikalle
				echo "<option value='kohdista'>"         .t("Kohdista rivej‰")."</option>";
				echo "<option value='kululaskut'>"       .t("Saapumisen laskut")."</option>";
				echo "<option value='lisatiedot'>"       .t("Lis‰tiedot")."</option>";
				echo "<option value='yhdista'>"          .t("Yhdist‰ saapumisia")."</option>";

				// poista keikka vaan jos ei ole yht‰‰n rivi‰ kohdistettu ja ei ole yht‰‰n kululaskua liitetty
				if ($kplyhteensa == 0 and $llrow["num"] == 0) {
					echo "<option value='poista'>"       .t("Poista saapuminen")."</option>";
				}

				if ($yhtiorow['suuntalavat'] == 'S') {
					echo "<option value='suuntalavat'>",t("Suuntalavat"),"</option>";
				}

				// jos on kohdistettuja rivej‰ niin saa tehd‰ n‰it‰
				if ($kplyhteensa > 0) {
					echo "<option value='varastopaikat'>".t("Varastopaikat")."</option>";
					echo "<option value='tulosta'>"      .t("Tulosta paperit")."</option>";
				}

				// jos on kohdistettuja rivej‰ ja lis‰tiedot on syˆtetty ja varastopaikat on ok ja on viel‰ jotain viet‰v‰‰ varastoon
				if ($kplyhteensa > 0 and $varok == 1 and $kplyhteensa != $kplvarasto and $sarjanrook == 1) {
					if ($yhtiorow['suuntalavat'] != 'S') {
						echo "<option value='kalkyyli'>"     .t("Vie varastoon")."</option>";
					}
				}

				// jos lis‰tiedot, kohdistus ja paikat on ok sek‰ kaikki rivit on viety varastoon, ja kaikki liitetyt laskut on hyv‰ksytty (kukarow.taso 3 voi ohittaa t‰m‰n), niin saadaan laskea virallinen varastonarvo
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

		if (isset($naytalaskelma) and $naytalaskelma != "") {
			list (	$liitetty_lasku_viety_summa,
					$ei_liitetty_lasku_viety_summa,
					$liitetty_lasku_ei_viety_summa,
					$ei_liitetty_lasku_ei_viety_summa,
					$liitetty_lasku_osittain_viety_summa,
					$ei_liitetty_lasku_osittain_viety_summa,
					$laskut_ei_viety,
					$laskut_viety,
					$laskut_osittain_viety,
					$row_vaihto
					) = hae_yhteenveto_tiedot($toimittajaid);

			$params = array(
				'kaikkivarastossayhteensa'				 => $kaikkivarastossayhteensa,
				'kaikkiliitettyyhteensa'				 => $kaikkiliitettyyhteensa,
				'vaihtoomaisuuslaskujayhteensa'			 => $vaihtoomaisuuslaskujayhteensa,
				'row_vaihto'							 => $row_vaihto,
				'kululaskujayhteensa'					 => $kululaskujayhteensa,
				'liitetty_lasku_ei_viety_summa'			 => $liitetty_lasku_ei_viety_summa,
				'ei_liitetty_lasku_ei_viety_summa'		 => $ei_liitetty_lasku_ei_viety_summa,
				'laskut_ei_viety'						 => $laskut_ei_viety,
				'liitetty_lasku_viety_summa'			 => $liitetty_lasku_viety_summa,
				'ei_liitetty_lasku_viety_summa'			 => $ei_liitetty_lasku_viety_summa,
				'laskut_viety'							 => $laskut_viety,
				'liitetty_lasku_osittain_viety_summa'	 => $liitetty_lasku_osittain_viety_summa,
				'ei_liitetty_lasku_osittain_viety_summa' => $ei_liitetty_lasku_osittain_viety_summa,
				'laskut_osittain_viety'					 => $laskut_osittain_viety,
			);
			echo_yhteenveto_table($params);
		}
		else {
			echo "<br><br><form method='post'>";
			echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
			echo "<input type='hidden' name='ytunnus' value='$ytunnus'>";
			echo "<input type='hidden' name='naytalaskelma' value='JOO'>";
			echo "<input type='submit' value='".t("N‰yt‰ varastonarvolaskelma")."'>";
			echo "</form>";
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
	else {
		echo "<br>".t("Toimittajalla ei ole keskener‰isi‰ saapumisia")."!";
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
	if (!isset($kaikkiliitettyyhteensa))   $kaikkiliitettyyhteensa   = 0;

	list ($kaikkivarastossayhteensa,$kaikkiliitettyyhteensa,$kohdistus,$kohok,$kplvarasto,$kplyhteensa,$lisatiedot,$lisok,$llrow,$sarjanrook,$sarjanrot,$uusiot,$varastopaikat,$varastossaarvo,$liitettyarvo,$varok) = tsekit($tsekkirow,$kaikkivarastossayhteensa,$kaikkiliitettyyhteensa);

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

	// n‰it‰ saa tehd‰ aina keikalle
	$nappikeikka = "<table><tr>";

	$nappikeikka .= "$formalku";
	$nappikeikka .= "<input type='hidden' name='toiminto' value='kohdista'>";
	$nappikeikka .= "<input type='submit' value='".t("Kohdista rivej‰")."'>";
	$nappikeikka .= "$formloppu";

	$nappikeikka .= "$formalku";
	$nappikeikka .= "<input type='hidden' name='toiminto' value='kululaskut'>";
	$nappikeikka .= "<input type='submit' value='".t("Saapumisen laskut")."'>";
	$nappikeikka .= "$formloppu";

	$nappikeikka .= "$formalku";
	$nappikeikka .= "<input type='hidden' name='toiminto' value='lisatiedot'>";
	$nappikeikka .= "<input type='submit' value='".t("Lis‰tiedot")."'>";
	$nappikeikka .= "$formloppu";

	// poista keikka vaan jos ei ole yht‰‰n rivi‰ kohdistettu ja ei ole yht‰‰n kululaskua liitetty
	if ($kplyhteensa == 0 and $llrow["num"] == 0) {
		$nappikeikka .= "$formalku";
		$nappikeikka .= "<input type='hidden' name='toiminto' value='poista'>";
		$nappikeikka .= "<input type='submit' value='".t("Poista saapuminen")."'>";
		$nappikeikka .= "$formloppu";
	}

	// jos on kohdistettuja rivej‰ niin saa tehd‰ n‰it‰
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

	// jos on kohdistettuja rivej‰ ja lis‰tiedot on syˆtetty ja varastopaikat on ok ja on viel‰ jotain viet‰v‰‰ varastoon
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

function echo_yhteenveto_table($params) {
	global $yhtiorow;

	echo "<br><br><table>";
	echo "<tr><th>".t("Tuotteita liitetty saapumisille yhteens‰")."</th><td align='right'> ".number_format($params['kaikkiliitettyyhteensa'], 2, '.', ' ')." $yhtiorow[valkoodi]</td></tr>";
	echo "<tr><th>".t("Tuotteita viety varastoon yhteens‰")."</th><td align='right'> ".number_format($params['kaikkivarastossayhteensa'], 2, '.', ' ')." $yhtiorow[valkoodi]</td></tr>";

	echo "<tr><th>".t("Vaihto-omaisuuslaskuja liitetty saapumisille")."</th><td align='right'>".number_format($params['vaihtoomaisuuslaskujayhteensa'], 2, '.', ' ')." $yhtiorow[valkoodi]</td></tr>";
	echo "<tr><th>".t("Vaihto-omaisuuslaskuja liitt‰m‰tt‰ saapumisille")."</th><td align='right'>".number_format($params['row_vaihto']['vosumma'], 2, '.', ' ')." $yhtiorow[valkoodi]</td></tr>";

	echo "<tr><th>".t("Huolinta-/rahtilaskuja liitetty saapumisille")."</th><td align='right'>".number_format($params['kululaskujayhteensa'], 2, '.', ' ')." $yhtiorow[valkoodi]</td></tr>";
	echo "<tr><th>".t("Huolinta-/rahtilaskuja osittain liitt‰m‰tt‰ saapumisille")."</th><td align='right'>".number_format($params['row_vaihto']['kuosasumma'], 2, '.', ' ')." $yhtiorow[valkoodi]</td></tr>";
	echo "<tr><th>".t("Huolinta-/rahtilaskuja liitt‰m‰tt‰ saapumisille")."</th><td align='right'>".number_format($params['row_vaihto']['kusumma'], 2, '.', ' ')." $yhtiorow[valkoodi]</td></tr>";

	echo '<tr><td class="back"></td></tr>';
	echo '<tr>';
	echo "<th>".t('Saapumiset')."</th>";
	echo '<th>'.t('johon liitetty lasku (rivien arvo)').'</th>';
	echo '<th>'.t('johon ei liitetty lasku (rivien arvo)').'</th>';
	echo '<th>'.t('Laskut').'</th>';
	echo '</tr>';

	echo '<tr>';
	echo '<th>'.t('Viem‰tt‰ varastoon').'</th>';
	echo "<td style='text-align:right;'>".number_format($params['liitetty_lasku_ei_viety_summa'], 2, '.', ' ')."</td>";
	echo "<td style='text-align:right;'>".number_format($params['ei_liitetty_lasku_ei_viety_summa'], 2, '.', ' ')."</td>";
	echo "<td style='text-align:right;'>".number_format($params['laskut_ei_viety'], 2, '.', ' ')."</td>";
	echo '</tr>';

	echo '<tr>';
	echo '<th>'.t('Viety varastoon kokonaan').'</th>';
	echo "<td style='text-align:right;'>".number_format($params['liitetty_lasku_viety_summa'], 2, '.', ' ')."</td>";
	echo "<td style='text-align:right;'>".number_format($params['ei_liitetty_lasku_viety_summa'], 2, '.', ' ')."</td>";
	echo "<td style='text-align:right;'>".number_format($params['laskut_viety'], 2, '.', ' ')."</td>";
	echo '</tr>';

	echo '<tr>';
	echo '<th>'.t('Viety varastoon osittain').'</th>';
	echo "<td style='text-align:right;'>".number_format($params['liitetty_lasku_osittain_viety_summa'], 2, '.', ' ')."</td>";
	echo "<td style='text-align:right;'>".number_format($params['ei_liitetty_lasku_osittain_viety_summa'], 2, '.', ' ')."</td>";
	echo "<td style='text-align:right;'>".number_format($params['laskut_osittain_viety'], 2, '.', ' ')."</td>";
	echo '</tr>';

	echo '<tr>';
	echo '<th>'.t('Yhteens‰').'</th>';
	$yhteensa	 = $params['liitetty_lasku_ei_viety_summa'] + $params['liitetty_lasku_viety_summa'] + $params['liitetty_lasku_osittain_viety_summa'];
	echo "<th style='text-align:right;'>".number_format($yhteensa, 2, '.', ' ')."</th>";
	$yhteensa	 = $params['ei_liitetty_lasku_ei_viety_summa'] + $params['ei_liitetty_lasku_viety_summa'] + $params['ei_liitetty_lasku_osittain_viety_summa'];
	echo "<th style='text-align:right;'>".number_format($yhteensa, 2, '.', ' ')."</th>";
	$yhteensa	 = $params['laskut_viety'] + $params['laskut_ei_viety'] + $params['laskut_osittain_viety'];
	echo "<th style='text-align:right;'>".number_format($yhteensa, 2, '.', ' ')."</th>";
	echo '</tr>';
	echo "</table>";
}

function hae_yhteenveto_tiedot($toimittajaid = null) {
	global $kukarow, $yhtiorow;

	if ($toimittajaid == null) {
		$toimittaja_where = '';
	}
	else {
		$toimittaja_where = "AND lasku.liitostunnus = '{$toimittajaid}'";
	}

	// haetaan vaihto-omaisuus- ja huolinta/rahti- laskut joita ei oo liitetty saapumisiin
	$query = "	SELECT
				lasku.tunnus,
				if(lasku.vienti in ('C','F','I','J','K','L'), lasku.summa * lasku.vienti_kurssi, 0) vosumma,
				if(lasku.vienti in ('B','E','H'),	 		  lasku.summa * lasku.vienti_kurssi, 0) kusumma,
				sum(if(lasku.vienti in ('C','F','I','J','K','L'), tiliointi.summa, 0)) voalvit,
				sum(if(lasku.vienti in ('B','E','H'),	 		  tiliointi.summa, 0)) kualvit
				FROM lasku
				LEFT JOIN tiliointi USE INDEX (tositerivit_index) ON (tiliointi.yhtio = lasku.yhtio and tiliointi.ltunnus = lasku.tunnus and tiliointi.tilino = '$yhtiorow[alv]' and tiliointi.korjattu = '' AND if(lasku.summa > 0, tiliointi.summa, tiliointi.summa*-1) > 0)
				LEFT JOIN lasku AS liitos ON liitos.yhtio = lasku.yhtio AND liitos.vanhatunnus = lasku.tunnus AND liitos.tila = 'K'
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				AND lasku.tila IN ('H','Y','M','P','Q')
				AND lasku.vienti in ('B','C','J','E','F','K','H','I','L')
				AND liitos.tunnus IS NULL
				AND lasku.tapvm >= date_sub(current_date, interval 12 month)
				{$toimittaja_where}
				GROUP BY lasku.tunnus";
	$result_vaihto_omaisuus = pupe_query($query);

	$rv_vosumma = 0;
	$rv_voalvit = 0;
	$rv_kusumma = 0;
	$rv_kualvit = 0;

	while ($row_vaihto = mysql_fetch_assoc($result_vaihto_omaisuus)) {
		$rv_vosumma += $row_vaihto["vosumma"];
		$rv_voalvit += $row_vaihto["voalvit"];
		$rv_kusumma += $row_vaihto["kusumma"];
		$rv_kualvit += $row_vaihto["kualvit"];
	}

	$row_vaihto["vosumma"] = $rv_vosumma - $rv_voalvit;
	$row_vaihto["kusumma"] = $rv_kusumma - $rv_kualvit;

	// haetaan rahti/huolinta laskut jotka on liitetty vain osittain saapumisiin
	$query = "	SELECT
				(SELECT sum(summa) summa
					 FROM tiliointi
					 WHERE tiliointi.yhtio = lasku.yhtio
					 AND tiliointi.ltunnus = lasku.tunnus
					 AND tiliointi.tilino in ('$yhtiorow[varasto]','$yhtiorow[raaka_ainevarasto]')
					 AND tiliointi.korjattu = '') varastossa,
				sum(liitos.arvo*liitos.vienti_kurssi) kohdistettu
				FROM lasku
				JOIN lasku AS liitos on (liitos.yhtio = lasku.yhtio and liitos.vanhatunnus = lasku.tunnus and liitos.tila = 'K')
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				AND lasku.tila in ('H','Y','M','P','Q')
				AND lasku.vienti in ('B','E','H')
				AND lasku.tapvm >= date_sub(current_date, interval 12 month)
				{$toimittaja_where}
				GROUP BY lasku.tunnus
				HAVING varastossa != kohdistettu";
	$result_huolintarahdit = pupe_query($query);

	$row_vaihto["kuosasumma"] = 0;

	while($row_huorah = mysql_fetch_assoc($result_huolintarahdit)) {
		$row_vaihto["kuosasumma"] += ($row_huorah["varastossa"]-$row_huorah["kohdistettu"]);
	}

	$liitetty_lasku_viety_summa	   = 0;
	$ei_liitetty_lasku_viety_summa = 0;

	$liitetty_lasku_ei_viety_summa    = 0;
	$ei_liitetty_lasku_ei_viety_summa = 0;

	$liitetty_lasku_osittain_viety_summa	= 0;
	$ei_liitetty_lasku_osittain_viety_summa = 0;

	$laskut_ei_viety	   = 0;
	$laskut_viety		   = 0;
	$laskut_osittain_viety = 0;

	$query = "	SELECT lasku.laskunro,
				lasku.tila,
				lasku.vanhatunnus,
				lasku.tunnus,
				count(DISTINCT liitos.tunnus) liitetty,
				group_concat(liitos.vanhatunnus) tunnukset
				FROM lasku
				LEFT JOIN lasku AS liitos ON liitos.yhtio = lasku.yhtio AND liitos.laskunro = lasku.laskunro AND liitos.vanhatunnus > 0 AND liitos.vienti IN ('C','F','I','J','K','L') AND liitos.tila = 'K'
				WHERE  lasku.yhtio 	  = '{$kukarow['yhtio']}'
				AND lasku.tila 		  = 'K'
				AND lasku.alatila 	  = ''
				AND lasku.mapvm 	  = '0000-00-00'
				AND lasku.vanhatunnus = 0
				{$toimittaja_where}
				GROUP BY 1,2,3,4";
	$result = pupe_query($query);

	$query_ale_lisa = generoi_alekentta("O");

	//haetaan saapuvia ostotilauksia, joihin liitetty tai ei liitetty lasku (kts. liitetty)
	while ($row = mysql_fetch_assoc($result)) {

		$query = "	SELECT
					sum(kpl * hinta * {$query_ale_lisa}) viety,
					sum(varattu * hinta * {$query_ale_lisa}) ei_viety
					FROM tilausrivi
					WHERE yhtio 	= '{$kukarow['yhtio']}'
					AND uusiotunnus = {$row['tunnus']}
					AND tyyppi 		= 'O'";
		$result2 = pupe_query($query);
		$tilausrivirow = mysql_fetch_assoc($result2);

		if ($row['liitetty'] == 0) {
			$ei_liitetty_lasku_ei_viety_summa += $tilausrivirow['ei_viety'];

			if ($tilausrivirow['viety'] != 0 and $tilausrivirow['ei_viety'] == 0) {
				//viety kokonaan
				$ei_liitetty_lasku_viety_summa += $tilausrivirow['viety'];
			}
			else {
				//saapuminen viety osittain varastoon ja ei liitetty lasku
				$ei_liitetty_lasku_osittain_viety_summa += $tilausrivirow['viety'];
			}
		}
		else {
			$query = "	SELECT round(lasku.vienti_kurssi * lasku.summa, 2) summa,
						sum(round(tiliointi.summa, 2)) alvit
						FROM lasku
						LEFT JOIN tiliointi USE INDEX (tositerivit_index) ON (tiliointi.yhtio = lasku.yhtio and tiliointi.ltunnus = lasku.tunnus and tiliointi.tilino = '$yhtiorow[alv]' and tiliointi.korjattu = '' AND if(lasku.summa > 0, tiliointi.summa, tiliointi.summa*-1) > 0)
						WHERE lasku.yhtio = '{$kukarow['yhtio']}'
						AND lasku.tunnus IN ({$row['tunnukset']})
						GROUP BY 1";
			$result_laskut = pupe_query($query);

			$laskujensummat = 0;

			while ($laskut = mysql_fetch_assoc($result_laskut)) {
				$laskujensummat += ($laskut['summa']-$laskut['alvit']);
			}

			//on liitetty lasku
			if ($tilausrivirow['viety'] == 0 and $tilausrivirow['ei_viety'] != 0) {
				// ei viety ollenkaan
				$liitetty_lasku_ei_viety_summa += $tilausrivirow['ei_viety'];
				$laskut_ei_viety += $laskujensummat;
			}
			elseif ($tilausrivirow['viety'] != 0 and $tilausrivirow['ei_viety'] == 0) {
				//viety kokonaan
				$liitetty_lasku_viety_summa += $tilausrivirow['viety'];
				$laskut_viety += $laskujensummat;
			}
			else {
				//saapuminen viety osittain varastoon ja liitetty lasku
				$liitetty_lasku_osittain_viety_summa += $tilausrivirow['viety'];
				$laskut_osittain_viety += $laskujensummat;
			}
		}
	}

	return array(
		$liitetty_lasku_viety_summa,
		$ei_liitetty_lasku_viety_summa,
		$liitetty_lasku_ei_viety_summa,
		$ei_liitetty_lasku_ei_viety_summa,
		$liitetty_lasku_osittain_viety_summa,
		$ei_liitetty_lasku_osittain_viety_summa,
		$laskut_ei_viety,
		$laskut_viety,
		$laskut_osittain_viety,
		$row_vaihto,
	);
}

echo "<SCRIPT LANGUAGE=JAVASCRIPT>
nappikeikka = \"$nappikeikka\";
document.getElementById('toimnapit').innerHTML = nappikeikka;
</SCRIPT>";

if (strpos($_SERVER['SCRIPT_NAME'], "keikka.php")  !== FALSE) {
	require ("inc/footer.inc");
}
