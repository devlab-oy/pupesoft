<?php

if (strpos($_SERVER['SCRIPT_NAME'], "keikka.php")  !== FALSE) {
	require ("../inc/parametrit.inc");
}

if ($livesearch_tee == "TUOTEHAKU") {
	livesearch_tuotehaku();
	exit;
}

if (!function_exists("tsekit")) {
	function tsekit($row, $kaikkivarastossayhteensa) {

		global $kukarow, $yhtiorow;

		$tsekit = array();

		// tutkitaan onko kaikilla tuotteilla on joku varastopaikka
		$query  = "	SELECT tilausrivi.*
					FROM tilausrivi USE INDEX (uusiotunnus_index)
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]' and
					tilausrivi.uusiotunnus = '$row[tunnus]' and
					tilausrivi.tyyppi = 'O'";
		$tilres = mysql_query($query) or pupe_error($query);

		$kplyhteensa = 0;  // apumuuttuja
		$kplvarasto  = 0;  // apumuuttuja
		$eipaikkoja  = 0;  // apumuuttuja
		$eituotteet  = ""; // apumuuttuja
		$varastossaarvo = 0; // apumuuttuja
		$uusiot = array();

		while ($rivirow = mysql_fetch_array($tilres)) {
			$query = "SELECT * from tuote where tuoteno='$rivirow[tuoteno]' and yhtio='$kukarow[yhtio]'";
			$tuore = mysql_query($query) or pupe_error($query);
			$tuote = mysql_fetch_array($tuore);

			if (!in_array($rivirow["otunnus"], $uusiot)) {
				$uusiot[] = $rivirow["otunnus"];
			}

			$kplyhteensa++; // lasketaan montako tilausrivi� on kohdistettu

			$varastossaarvo += $rivirow["rivihinta"];
			$kaikkivarastossayhteensa += $rivirow["rivihinta"];

			if (($rivirow["kpl"] != 0 and $rivirow["varattu"] == 0) or ($rivirow["kpl"] == 0 and $rivirow["varattu"] == 0)) {
				$kplvarasto++; // lasketaan montako tilausrivi� on viety varastoon
			}

			// jos kyseess� on saldollinen tuote
			if ($tuote['ei_saldoa'] == "") {
				//jos rivi on jo viety varastoon niin ei en�� katota sen paikkaa
				if ($rivirow["kpl"] == 0 and $rivirow["varattu"] != 0) {
					// katotaan l�ytyyk� tuotteelta varastopaikka joka on tilausriville tallennettu
					$query = "	SELECT *
								from tuotepaikat use index (tuote_index)
								where tuoteno='$rivirow[tuoteno]' and yhtio='$kukarow[yhtio]' and hyllyalue='$rivirow[hyllyalue]' and hyllynro='$rivirow[hyllynro]' and hyllytaso='$rivirow[hyllytaso]' and hyllyvali='$rivirow[hyllyvali]'";
					$tpres = mysql_query($query) or pupe_error($query);

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
			$varok=1;
			$varastopaikat = "<font class='ok'>".t("ok")."</font>";
		}
		else {
			$varok=0;
			$varastopaikat = t("kesken");
		}

		// tutkitaan onko kaikki lis�tiedot sy�tetty vai ei...
		$query = "	SELECT *
					from lasku use index (PRIMARY)
					where lasku.yhtio='$kukarow[yhtio]'
					and tunnus='$row[tunnus]'
					and maa != '$yhtiorow[maa]'
					and (maa_maara = '' or maa_lahetys = '' or bruttopaino = '' or kauppatapahtuman_luonne <= 0 or kuljetusmuoto = '' or toimaika = '0000-00-00')
					and kauppatapahtuman_luonne != '999'";
		$okres = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($okres)==0) {
			$lisok=1;
			$lisatiedot = "<font class='ok'>".t("ok")."</font>";
		}
		else {
			$lisok=0;
			$lisatiedot = t("kesken");
		}

		// katotaan onko kohdistus tehty pennilleen
		if ($row["kohdistettu"] == 'K') {
			$kohok=1;
			$kohdistus = "<font class='ok'>".t("ok")."</font>";
		}
		else {
			$kohok=0;
			$kohdistus = t("kesken");
		}

		// katotaan onko kaikki sarjanumerot ok
		$query = "	SELECT tilausrivi.tunnus, tilausrivi.tuoteno, tilausrivi.varattu+tilausrivi.kpl kpl, tuote.sarjanumeroseuranta, tilausrivi.uusiotunnus
					FROM tilausrivi use index (uusiotunnus_index)
					JOIN tuote on tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta!=''
					WHERE tilausrivi.yhtio='$kukarow[yhtio]' and
					tilausrivi.uusiotunnus='$row[tunnus]' and
					tilausrivi.tyyppi='O'";
		$toimresult = mysql_query($query) or pupe_error($query);

		$sarjanrook = 1;

		while ($toimrow = mysql_fetch_array($toimresult)) {

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
			$sarjares = mysql_query($query) or pupe_error($query);
			$sarjarow = mysql_fetch_array($sarjares);

			// pit�� olla yht�monta sarjanumeroa liitettyn� kun kamaa viety varastoon
			if ($sarjarow["kpl"] != abs($toimrow["kpl"])) {
				$sarjanrook++;
			}
		}

		// pit�� olla yht�monta sarjanumeroa liitettyn� kun kamaa viety varastoon
		if ($sarjanrook == 1) {
			$sarjanrook	= 1;
			$sarjanrot	= "<font class='ok'>".t("ok")."</font>";
		}
		else {
			$sarjanrook	= 0; // ei ole kaikilla tuotteilla sarjanumeroa
			$sarjanrot	= "kesken";
		}

		// katotaan onko liitettyj� laskuja
		// ('C','F','I','J','K','L') // vaihto-omaisuus ja raaka-aine
		// ('B','C','J','E','F','K','H','I','L') // kaikki

		$query = "	SELECT count(*) num,
					sum(if(lasku.vienti='C' or lasku.vienti='F' or lasku.vienti='I' or lasku.vienti='J' or lasku.vienti='K' or lasku.vienti='L', 1, 0)) volasku,
					sum(if(ostores_lasku.tila != 'H' and (lasku.vienti='C' or lasku.vienti='F' or lasku.vienti='I' or lasku.vienti='J' or lasku.vienti='K' or lasku.vienti='L'), 1, 0)) volasku_ok,
					sum(if(lasku.vienti!='C' and lasku.vienti!='F' and lasku.vienti!='I' and lasku.vienti!='J' and lasku.vienti!='K' and lasku.vienti!='L', 1, 0)) kulasku,
					sum(if(ostores_lasku.tila != 'H' and lasku.vienti!='C' and lasku.vienti!='F' and lasku.vienti!='I' and lasku.vienti!='J' and lasku.vienti!='K' and lasku.vienti!='L',1,0)) kulasku_ok,
					round(sum(if(lasku.vienti='C' or lasku.vienti='F' or lasku.vienti='I' or lasku.vienti='J' or lasku.vienti='K' or lasku.vienti='L', lasku.summa * lasku.vienti_kurssi, 0)),2) vosumma,
					round(sum(if(lasku.vienti!='C' and lasku.vienti!='F' and lasku.vienti!='I' and lasku.vienti!='J' and lasku.vienti!='K' and lasku.vienti!='L', lasku.arvo * lasku.vienti_kurssi, 0)),2) kusumma
					from lasku use index (yhtio_tila_laskunro)
					JOIN lasku ostores_lasku on (ostores_lasku.yhtio = lasku.yhtio and ostores_lasku.tunnus = lasku.vanhatunnus)
					where lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila = 'K'
					and lasku.vanhatunnus <> 0
					and lasku.laskunro = '$row[laskunro]'";
		$llres = mysql_query($query) or pupe_error($query);
		$llrow = mysql_fetch_array($llres);

		// $kaikkivarastossayhteensa,$kohdistus,$kohok,$kplvarasto,$kplyhteensa,$lisatiedot,$lisok,$llrow,$sarjanrook,$sarjanrot,$uusiot,$varastopaikat,$varastossaarvo,$varok
		return array($kaikkivarastossayhteensa,$kohdistus,$kohok,$kplvarasto,$kplyhteensa,$lisatiedot,$lisok,$llrow,$sarjanrook,$sarjanrot,$uusiot,$varastopaikat,$varastossaarvo,$varok);

	}
}

echo "<font class='head'>".t("Saapuva keikka")."</font><hr>";

if ($yhtiorow["livetuotehaku_tilauksella"] == "K") {
	enable_ajax();
}

// scripti balloonien tekemiseen
js_popup();

echo "<div id='toimnapit'></div>";

if ($nappikeikalle == 'menossa') {
	$query = "UPDATE kuka SET kesken = 0 where yhtio = '$kukarow[yhtio]' and kuka = '$kukarow[kuka]'";
	$nappiresult = mysql_query($query) or pupe_error($query);
}

// yhdistetaan keikkaan $otunnus muita keikkoja
if ($toiminto == "yhdista") {
	require('ostotilausten_rivien_yhdistys.inc');
}

// poistetaan vanha keikka numerolla $keikkaid
if ($toiminto == "poista") {
	$eisaapoistaa = 0;

	$query  = "SELECT tunnus from tilausrivi where yhtio='$kukarow[yhtio]' and uusiotunnus='$tunnus' and tyyppi='O'";
	$delres = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($delres) != 0) {
		$eisaapoistaa++;
	}

	$query = "	SELECT tunnus
				from lasku
				where yhtio='$kukarow[yhtio]' and tila='K' and vanhatunnus<>0 and laskunro='$laskunro'";
	$delres2 = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($delres2) != 0) {
		$eisaapoistaa++;
	}

	if ($eisaapoistaa == 0) {

		$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mit�t�itiin ohjelmassa keikka.php")."<br>";

		$query  = "UPDATE lasku SET alatila = tila, tila = 'D', comments = '$komm' where yhtio='$kukarow[yhtio]' and tila='K' and laskunro='$keikkaid'";
		$result = mysql_query($query) or pupe_error($query);

		// formissa on tullut my�s $ytunnus, joten n�in p��st��n takaisin selaukseen
		$toiminto = "";
	}
	else {
		echo "<font class='error'>Keikkaan on jo liitetty laskuja tai kohdistettu rivej�, sit� ei voi poistaa!!!<br>";
		// formissa on tullut my�s $ytunnus, joten n�in p��st��n takaisin selaukseen
		$toiminto = "";
	}

}

// tulostetaan tarvittavia papruja $otunnuksen mukaan
if ($toiminto == "tulosta") {
	// Haetaan itse keikka
	$query    = "	SELECT *
					from lasku
					where tunnus = '$otunnus'
					and yhtio = '$kukarow[yhtio]'";
	$result   = mysql_query($query) or pupe_error($query);
	$laskurow = mysql_fetch_array($result);

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
	$llres = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($llres) > 0) {
		$llrow = mysql_fetch_array($llres);

		$query = "	SELECT
					GROUP_CONCAT(viesti SEPARATOR ' ') viesti,
					GROUP_CONCAT(tapvm SEPARATOR ' ') tapvm
					from lasku
					where yhtio = '$kukarow[yhtio]'
					and tunnus in ($llrow[volaskutunn])";
		$llres = mysql_query($query) or pupe_error($query);
		$llrow = mysql_fetch_array($llres);


		$laskurow["tapvm "] = $llrow["tapvm"];
		$laskurow["viesti"] = $llrow["viesti"];

	}

	if (count($komento) == 0) {
		$tulostimet = array('Purkulista','Tuotetarrat','Tariffilista');

		echo "<font class='message'>".t("Keikka")." $laskurow[laskunro] $laskurow[nimi]</font><br><br>";

		require('../inc/valitse_tulostin.inc');
	}

	if ($komento["Purkulista"] != '')
		require('tulosta_purkulista.inc');

	if ($komento["Tuotetarrat"] != '')
		require('tulosta_tuotetarrat.inc');

	if ($komento["Tariffilista"] != '')
		require('tulosta_tariffilista.inc');

	// takaisin selaukseen
	$toiminto = "";
	$ytunnus = $laskurow["ytunnus"];
}

// sy�tet��n keikan lis�tietoja
if ($toiminto == "lisatiedot") {
	require ("ostotilauksen_lisatiedot.inc");
}

// chekataan tilauksen varastopaikat
if ($toiminto == "varastopaikat") {
	require('ostorivienvarastopaikat.inc');
}

// lis�ill��n keikkaan kululaskuja
if ($toiminto == "kululaskut") {
	$keikanalatila 	= "";

	require('kululaskut.inc');
}

// tehd��n errorichekkej� jos on varastoonvienti kyseess�
if ($toiminto == "kaikkiok" or $toiminto == "kalkyyli") {
	$query = "	SELECT nimi
				FROM kuka
				WHERE yhtio = '$kukarow[yhtio]'
				and kesken  = '$otunnus'";
	$result = mysql_query($query) or pupe_error($query);

	$varastoerror = 0;

	if (file_exists("/tmp/$kukarow[yhtio]-keikka.lock")) {
		echo "<font class='error'>".t("VIRHE: Keikkaa ei voi vied� varastoon.")." ".t("Varastoonvienti on kesken!")."</font><br>";
		$varastoerror = 1;
	}
	elseif (mysql_num_rows($result) != 0){
		while ($rivi = mysql_fetch_array($result)) {
			echo "<font class='error'>".t("VIRHE: Keikkaa ei voi vied� varastoon.")." ".sprintf(t("K�ytt�j�ll� %s on kohdistus kesken!"), $rivi["nimi"])."</font><br>";
		}
		$varastoerror = 1;
	}

	if ($varastoerror != 0) {
		echo "<br><form action='$PHP_SELF' method='post'>";
		echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
		echo "<input type='hidden' name='toiminto' value=''>";
		echo "<input type='hidden' name='ytunnus' value='$laskurow[ytunnus]'>";
		echo "<input type='submit' value='".t("Takaisin")."'>";
		echo "</form>";

		$ytunnus = "";
		$toiminto = "dummieimit��n";
	}
}

// lasketaan lopullinen varastonarvo
if ($toiminto == "kaikkiok") {
	require ("varastonarvo_historia.inc");
}

// vied��n keikka varastoon
if ($toiminto == "kalkyyli") {
	require ("kalkyyli.inc");
}

if ($toiminto != 'kohdista' and $nappikeikalla == 'ollaan') {
	$toiminto = "kohdista";
}

if ($toiminto == "kohdista") {
	require('ostotilausten_rivien_kohdistus.inc');
}

if ($ytunnus == "" and $keikka != "") {
	$keikka = (int) $keikka;
	$query = "	SELECT ytunnus, max(laskunro)
				FROM lasku USE INDEX (tila_index)
				LEFT JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus)
				where lasku.yhtio = '$kukarow[yhtio]'
				and lasku.tila = 'K'
				and lasku.alatila = ''
				and lasku.vanhatunnus = 0
				and (lasku.laskunro = $keikka or tilausrivi.otunnus = $keikka)
				group by lasku.ytunnus
				order by lasku.laskunro desc";
	$keikkahaku_res = mysql_query($query) or pupe_error($query);
	$keikkahaku_row = mysql_fetch_assoc($keikkahaku_res);

	if ($keikkahaku_row['laskunro'] == '' and $keikkahaku_row['ytunnus'] == '') {
		$query = "	SELECT ytunnus
					FROM lasku
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tila = 'O'
					AND tunnus = '$keikka'";
		$ytunnus_res = mysql_query($query) or pupe_error($query);
		$ytunnus_row = mysql_fetch_assoc($ytunnus_res);
	}

	$keikka = $keikkahaku_row["laskunro"];
	$ytunnus = ($keikkahaku_row["ytunnus"] == '' and $ytunnus_row['ytunnus'] != '') ? $ytunnus_row['ytunnus'] : $keikkahaku_row['ytunnus'];
}

// jos ollaan annettu $ytunnus haetaan toimittajan tiedot arrayseen $toimittajarow
if ($ytunnus != "" or $toimittajaid != "") {
	$keikkamonta = 0;
	$hakutunnus = $ytunnus;
	$hakuid		= $toimittajaid;

	require ("../inc/kevyt_toimittajahaku.inc");

	$keikkamonta += $monta;
/*
	if ($ytunnus == "") {
		$ytunnus   = $hakutunnus;
		$asiakasid = $hakuid;

		require ("inc/asiakashaku.inc");

		$toimittajaid  = $asiakasid;
		$toimittajarow = $asiakasrow;
		$keikkamonta  += $monta;
	}
*/
	if ($keikkamonta > 1) {
		$toimittajaid  = "";
		$toimittajarow = "";
		$ytunnus 	   = "";
	}
}

if ($toiminto == "" and $ytunnus == "" and $keikka == "") {
	echo "<table>";
	echo "<form name='toimi' action='$PHP_SELF' method='post' autocomplete='off'>";
	echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
	echo "<tr>";
	echo "<th>".t("Etsi toimittaja")."</th>";
	echo "<td><input type='text' name='ytunnus' value='$ytunnus'></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<th>".t("Etsi keikka-/tilausnumero")."</th>";
	echo "<td><input type='text' name='keikka' value='$keikka'></td>";
	echo "<td class='back'><input type='submit' value='".t("Hae")."'></td>";
	echo "</tr>";
	echo "</form>";
	echo "</table>";

	// kursorinohjausta
	$formi    = "toimi";
	$kentta   = "ytunnus";
	$toiminto = "";

	$kaikkivarastossayhteensa = 0;
	$vaihtoomaisuuslaskujayhteensa = 0;
	$kululaskujayhteensa = 0;

	// n�ytet��n mill� toimittajilla on keskener�isi� keikkoja
	$query = "	SELECT ytunnus, nimi, nimitark, osoite, postitp, swift, group_concat(distinct if(comments!='',comments,NULL) SEPARATOR '<br><br>') comments, liitostunnus, count(distinct lasku.tunnus) kpl, group_concat(distinct laskunro SEPARATOR ', ') keikat,
				round(sum(tilausrivi.rivihinta),2) varastossaarvo, lasku.laskunro
				FROM lasku
				LEFT JOIN tilausrivi USE INDEX (uusiotunnus_index) on (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus and tilausrivi.tyyppi = 'O')
				WHERE lasku.yhtio = '$kukarow[yhtio]' and
				lasku.tila = 'K' and
				lasku.alatila = '' and
				lasku.vanhatunnus = 0
				GROUP BY liitostunnus, ytunnus, nimi, osoite, postitp, swift
				ORDER BY nimi, nimitark, ytunnus";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) != 0) {

		echo "<br><font class='head'>".t("Keskener�iset keikat")."</font><hr>";

		echo "<table>";
		echo "<tr><th>".t("ytunnus")."</th><th>&nbsp;</th><th>".t("nimi")."</th><th>".t("osoite")."</th><th>".t("swift")."</th><th>".t("keikkanumerot")."</th><th>".t("kpl")."</th><th>".t("varastonarvo")."</th><th></th></tr>";

		while ($row = mysql_fetch_array($result)) {

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
			$laskuja_result = mysql_query($query) or pupe_error($query);
			$laskuja_row = mysql_fetch_array($laskuja_result);

			$kaikkivarastossayhteensa += $row["varastossaarvo"];
			$vaihtoomaisuuslaskujayhteensa += $laskuja_row["vosumma"];
			$kululaskujayhteensa += $laskuja_row["kusumma"];

			echo "<tr class='aktiivi'>";

			echo "<td valign='top'>$row[ytunnus]</td>";

			// tehd��n pop-up divi jos keikalla on kommentti...
			if ($row["comments"] != "") {
				echo "<div id='div_$row[liitostunnus]' class='popup' style='width: 500px;'>";
				echo $row["comments"];
				echo "</div>";
				echo "<td valign='top' class='tooltip' id='$row[liitostunnus]'><img src='$palvelin2/pics/lullacons/info.png'></td>";
			}
			else {
				echo "<td>&nbsp;</td>";
			}

			if ($row["varastossaarvo"] == 0) $row["varastossaarvo"] = "";

			echo "<td>$row[nimi] $row[nimitark]</td><td>$row[osoite] $row[postitp]</td><td>$row[swift]</td><td>$row[keikat]</td><td align='right'>$row[kpl]</td><td align='right'>$row[varastossaarvo]</td>";
			echo "<form action='$PHP_SELF' method='post'>";
			echo "<input type='hidden' name='toimittajaid' value='$row[liitostunnus]'>";
			echo "<td><input type='submit' value='".t("Valitse")."'></td>";
			echo "</form>";
			echo "</tr>";
		}

		echo "</table>";

		if ($kaikkivarastossayhteensa != 0 or $vaihtoomaisuuslaskujayhteensa != 0 or $kululaskujayhteensa != 0) {
			echo "<br><table>";
			echo "<tr><th>".t("Varastoon viety yhteens�")."</th><td align='right'> ".sprintf("%01.2f", $kaikkivarastossayhteensa)." $yhtiorow[valkoodi]</td></tr>";
			echo "<tr><th>".t("Vaihto-omaisuuslaskuja liitetty")."</th><td align='right'>".sprintf("%01.2f", $vaihtoomaisuuslaskujayhteensa)." $yhtiorow[valkoodi]</td></tr>";
			echo "<tr><th>".t("Kululaskuja liitetty")."</th><td align='right'>".sprintf("%01.2f", $kululaskujayhteensa)." $yhtiorow[valkoodi]</td></tr>";
			echo "</table>";
		}

	}
}

// perustetaan uusi keikka toimittajalle $ytunnus
if ($toiminto == "uusi" and $toimittajaid > 0) {
	// haetaan seuraava vapaa keikkaid
	$query  = "SELECT max(laskunro) from lasku where yhtio='$kukarow[yhtio]' and tila='K'";
	$result = mysql_query($query) or pupe_error($query);
	$row    = mysql_fetch_array($result);
	$id		= $row[0]+1;

	$query  = "SELECT kurssi from valuu where nimi='$toimittajarow[oletus_valkoodi]' and yhtio='$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);
	$row    = mysql_fetch_array($result);
	$kurssi = $row["kurssi"];

	$maa_lahetys = $toimittajarow['maa_lahetys'] != '' ? $toimittajarow['maa_lahetys'] : $toimittajarow['maa'];

	// meill� on $toimittajarow haettuna ylh��ll�
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
	$result = mysql_query($query) or pupe_error($query);

	// selaukseen
	$toiminto = "";
}

// selataan toimittajan keikkoja
if ($toiminto == "" and (($ytunnus != "" or $keikka != '') and $toimittajarow["ytunnus"] != '')) {

	// n�ytet��n v�h� toimittajan tietoja
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
	echo "<form action='$PHP_SELF' method='post'>";
	echo "<input type='hidden' name='toiminto' value='uusi'>";
	echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
	echo "<input type='hidden' name='ytunnus' value='$ytunnus'>";
	echo "<input type='submit' value='".t("Perusta uusi keikka")."'>";
	echo "</form>";
	echo "</td>";
	echo "</tr>";

	if (trim($toimittajarow["fakta"]) != "") {
		echo "<tr><td colspan='5'>".wordwrap($toimittajarow["fakta"], 100, "<br>")."</td></tr>";
	}

	echo "</table><br>";

	if ($naytakaikki == "YES") {
		$limitti = " ";
	}
	else {
		$limitti = " LIMIT 50";
	}

	if (!isset($keikkalisa)) {
		$keikkalisa = '';
	}

	if ($keikka != '') {
		$keikka = (int) $keikka;
		$keikkalisa = " and lasku.laskunro = $keikka ";
	}

	// etsit��n vanhoja keikkoja, vanhatunnus pit�� olla tyhj�� niin ei listata liitettyj� laskuja
	$query = "	SELECT *
				FROM lasku USE INDEX (tila_index)
				where lasku.yhtio = '$kukarow[yhtio]'
				and lasku.liitostunnus = '$toimittajaid'
				and lasku.tila = 'K'
				and lasku.alatila = ''
				and lasku.vanhatunnus = 0
				$keikkalisa
				order by lasku.laskunro desc
				$limitti";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {

		echo "<font class='head'>".t("Toimittajan keskener�iset keikat")."</font><hr>";

		if (mysql_num_rows($result) == 50 and $naytakaikki == "") {
			echo "<table><tr><td class='back'><font class='error'>".t("HUOM: Toimittajalla on yli 50 avointa keikkaa! Vain 50 viimeisint� n�ytet��n.")."</font></td>";

			echo "<td class='back'>";
			echo "<form action='$PHP_SELF' method='post'>";
			echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
			echo "<input type='hidden' name='ytunnus' value='$ytunnus'>";
			echo "<input type='hidden' name='naytakaikki' value='YES'>";
			echo "<input type='submit' value='".t("N�yt� kaikki")."'>";
			echo "</form>";
			echo "</td>";
			echo "</tr></table><br>";
		}

		echo "<table>";
		echo "<tr>";
		echo "<th valign='top'>".t("keikka")."</th>";
		echo "<th valign='top'>&nbsp;</th>";
		echo "<th valign='top'>".t("ytunnus")." /<br>".t("nimi")."</th>";
		echo "<th valign='top'>".t("kohdistus")." /<br>".t("lis�tiedot")."</th>";
		echo "<th valign='top'>".t("paikat")." /<br>".t("sarjanrot")."</th>";
		echo "<th valign='top'>".t("kohdistettu")." /<br>".t("varastossa")."</th>";
		echo "<th valign='top'>&nbsp;</th>";
		echo "<th valign='top'>".t("ostolaskuja")." /<br>".t("kululaskuja")."</th>";
		echo "<th valign='top'>".t("toiminto")."</th>";
		echo "</tr>";

		$keikkakesken = 0;
		if (file_exists("/tmp/$kukarow[yhtio]-keikka.lock")) {
			$keikkakesken = file_get_contents("/tmp/$kukarow[yhtio]-keikka.lock");
		}

		$kaikkivarastossayhteensa = 0;
		$vaihtoomaisuuslaskujayhteensa = 0;
		$kululaskujayhteensa = 0;

		while ($row = mysql_fetch_array($result)) {

			list ($kaikkivarastossayhteensa,$kohdistus,$kohok,$kplvarasto,$kplyhteensa,$lisatiedot,$lisok,$llrow,$sarjanrook,$sarjanrot,$uusiot,$varastopaikat,$varastossaarvo,$varok) = tsekit($row,$kaikkivarastossayhteensa);
			$vaihtoomaisuuslaskujayhteensa += $llrow["vosumma"];
			$kululaskujayhteensa += $llrow["kusumma"];

			echo "<tr class='aktiivi'>";

			echo "<td valign='top'>$row[laskunro]</td>";

			// tehd��n pop-up divi jos keikalla on kommentti...
			if ($row["comments"] != "") {
				echo "<div id='div_$row[laskunro]' class='popup' style='width:500px;'>";
				echo t("Keikka").": $row[laskunro] / $row[nimi]<br><br>";
				echo $row["comments"];
				echo "</div>";
				echo "<td valign='top' class='tooltip' id='$row[laskunro]'><img src='$palvelin2/pics/lullacons/info.png'></td>";
			}
			else {
				echo "<td>&nbsp;</td>";
			}

			echo "<td valign='top'>$row[ytunnus]<br>$row[nimi]</td>";
			echo "<td valign='top'>$kohdistus<br>$lisatiedot</td>";
			echo "<td valign='top'>$varastopaikat<br>$sarjanrot</td>";
			echo "<td valign='top'>$kplyhteensa<br>$kplvarasto $varastossaarvo</td>";

			if (count($uusiot) > 0) {
				echo "<div id='div_keikka_$row[laskunro]' class='popup' style='width:100px;'>";
				echo t("Tilaukset").":<br><br>";
				echo implode("<br>", $uusiot);
				echo "</div>";
				echo "<td valign='top' class='tooltip' id='keikka_$row[laskunro]'><img src='$palvelin2/pics/lullacons/info.png'></td>";
			}
			else {
				echo "<td>&nbsp;</td>";
			}

			$laskujen_tiedot = "";

			if ($llrow["volasku"] != $llrow["volasku_ok"] or $llrow["kulasku"] != $llrow["kulasku_ok"]) {
				$query = "	SELECT ostores_lasku.*, kuka.nimi kukanimi
							from lasku use index (yhtio_tila_laskunro)
							JOIN lasku ostores_lasku on (ostores_lasku.yhtio = lasku.yhtio and ostores_lasku.tunnus = lasku.vanhatunnus and ostores_lasku.hyvaksyja_nyt != '')
							LEFT JOIN kuka ON (kuka.yhtio = ostores_lasku.yhtio and kuka.kuka = ostores_lasku.hyvaksyja_nyt)
							where lasku.yhtio = '$kukarow[yhtio]'
							and lasku.tila = 'K'
							and lasku.vanhatunnus <> 0
							and lasku.laskunro = '$row[laskunro]'";
				$volasresult = mysql_query($query) or pupe_error($query);

				echo "<div id='div_lasku_$row[laskunro]' class='popup'>";
				while ($volasrow = mysql_fetch_array($volasresult)) {
					echo t("Lasku")." $volasrow[nimi] ($volasrow[summa] $volasrow[valkoodi]) ".t("hyv�ksytt�v�n� k�ytt�j�ll�")." $volasrow[kukanimi]<br>";
				}
				echo "</div>";
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

			// jos t�t� keikkaa ollaan just viem�ss� varastoon ei tehd� dropdownia
			if ($keikkakesken == $row["tunnus"]) {
				echo "<td>".t("Varastoonvienti kesken")."</td>";
			}
			else {
				echo "<form action='$PHP_SELF' method='post'>";
				echo "<td align='right'>";
				echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
				echo "<input type='hidden' name='otunnus' value='$row[tunnus]'>";
				echo "<input type='hidden' name='ytunnus' value='$ytunnus'>";
				echo "<input type='hidden' name='keikkaid' value='$row[laskunro]'>";
				echo "<input type='hidden' name='tunnus' value='$row[tunnus]'>";
				echo "<input type='hidden' name='laskunro' value='$row[laskunro]'>";
				echo "<select name='toiminto'>";

				// n�it� saa tehd� aina keikalle
				echo "<option value='kohdista'>"         .t("Kohdista rivej�")."</option>";
				echo "<option value='kululaskut'>"       .t("Keikan laskut")."</option>";
				echo "<option value='lisatiedot'>"       .t("Lis�tiedot")."</option>";
				echo "<option value='yhdista'>"          .t("Yhdist� keikkoja")."</option>";

				// poista keikka vaan jos ei ole yht��n rivi� kohdistettu ja ei ole yht��n kululaskua liitetty
				if ($kplyhteensa == 0 and $llrow["num"] == 0) {
					echo "<option value='poista'>"       .t("Poista keikka")."</option>";
				}

				// jos on kohdistettuja rivej� niin saa tehd� n�it�
				if ($kplyhteensa > 0) {
					echo "<option value='varastopaikat'>".t("Varastopaikat")."</option>";
					echo "<option value='tulosta'>"      .t("Tulosta paperit")."</option>";
				}

				// jos on kohdistettuja rivej� ja lis�tiedot on sy�tetty ja varastopaikat on ok ja on viel� jotain viet�v�� varastoon
				if ($kplyhteensa > 0 and $varok == 1 and $kplyhteensa != $kplvarasto and $sarjanrook == 1) {
					echo "<option value='kalkyyli'>"     .t("Vie varastoon")."</option>";
				}

				// jos lis�tiedot, kohdistus ja paikat on ok sek� kaikki rivit on viety varastoon, ja kaikki liitetyt laskut on hyv�ksytty (kukarow.taso 3 voi ohittaa t�m�n), niin saadaan laskea virallinen varastonarvo
				if ($lisok == 1 and $kohok == 1 and $varok == 1 and $kplyhteensa == $kplvarasto and $sarjanrook == 1 and (($llrow["volasku"] == $llrow["volasku_ok"] and $llrow["kulasku"] == $llrow["kulasku_ok"]) or $kukarow["taso"] == "3")) {
					echo "<option value='kaikkiok'>"     .t("Laske virallinen varastonarvo")."</option>";
				}

				echo "</select>";
				echo " <input type='submit' value='".t("Tee")."'>";
				echo "</td>";
				echo "</form>";
			}
			echo "</tr>";
		}

		echo "</table>";

		if ($kaikkivarastossayhteensa != 0 or $vaihtoomaisuuslaskujayhteensa != 0 or $kululaskujayhteensa != 0) {
			echo "<br><table>";
			echo "<tr><th>".t("Varastoon viety yhteens�")."</th><td align='right'> ".sprintf("%01.2f", $kaikkivarastossayhteensa)." $yhtiorow[valkoodi]</td></tr>";
			echo "<tr><th>".t("Vaihto-omaisuuslaskuja liitetty")."</th><td align='right'>".sprintf("%01.2f", $vaihtoomaisuuslaskujayhteensa)." $yhtiorow[valkoodi]</td></tr>";
			echo "<tr><th>".t("Kululaskuja liitetty")."</th><td align='right'>".sprintf("%01.2f", $kululaskujayhteensa)." $yhtiorow[valkoodi]</td></tr>";
			echo "</table>";
		}

	}
}

$nappikeikka = "";

// kohdisteaan keikkaa laskun tunnuksella $otunnus
if ($toiminto == "kohdista" or $toiminto == "yhdista" or $toiminto == "poista" or $toiminto == "tulosta" or $toiminto == "lisatiedot" or $toiminto == "varastopaikat" or $toiminto == "kululaskut" or $toiminto == "kalkyyli" or $toiminto == "kaikkiok") {

	$query = "	SELECT *
				FROM lasku
				where lasku.yhtio 		= '$kukarow[yhtio]'
				and lasku.liitostunnus 	= '$toimittajaid'
				and lasku.tunnus 		= '$otunnus'";
	$tsekkiresult = mysql_query($query) or pupe_error($query);
	$tsekkirow = mysql_fetch_array($tsekkiresult);

	list ($kaikkivarastossayhteensa,$kohdistus,$kohok,$kplvarasto,$kplyhteensa,$lisatiedot,$lisok,$llrow,$sarjanrook,$sarjanrot,$uusiot,$varastopaikat,$varastossaarvo,$varok) = tsekit($tsekkirow,$kaikkivarastossayhteensa);

	$formalku =  "<td class='back'>";
	$formalku .= "<form action='$PHP_SELF' method='post'>";
	$formalku .= "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
	$formalku .= "<input type='hidden' name='otunnus' value='$tsekkirow[tunnus]'>";
	$formalku .= "<input type='hidden' name='ytunnus' value='$ytunnus'>";
	$formalku .= "<input type='hidden' name='keikkaid' value='$tsekkirow[laskunro]'>";
	$formalku .= "<input type='hidden' name='tunnus' value='$tsekkirow[tunnus]'>";
	$formalku .= "<input type='hidden' name='laskunro' value='$tsekkirow[laskunro]'>";
	$formalku .= "<input type='hidden' name='nappikeikalle' value='menossa'>";

	$formloppu = "</form></td>";

	// n�it� saa tehd� aina keikalle
	$nappikeikka = "<table><tr>";

	$nappikeikka .= "$formalku";
	$nappikeikka .= "<input type='hidden' name='toiminto' value='kohdista'>";
	$nappikeikka .= "<input type='submit' value='".t("Kohdista rivej�")."'>";
	$nappikeikka .= "$formloppu";

	$nappikeikka .= "$formalku";
	$nappikeikka .= "<input type='hidden' name='toiminto' value='kululaskut'>";
	$nappikeikka .= "<input type='submit' value='".t("Keikan laskut")."'>";
	$nappikeikka .= "$formloppu";

	$nappikeikka .= "$formalku";
	$nappikeikka .= "<input type='hidden' name='toiminto' value='lisatiedot'>";
	$nappikeikka .= "<input type='submit' value='".t("Lis�tiedot")."'>";
	$nappikeikka .= "$formloppu";

	// poista keikka vaan jos ei ole yht��n rivi� kohdistettu ja ei ole yht��n kululaskua liitetty
	if ($kplyhteensa == 0 and $llrow["num"] == 0) {
		$nappikeikka .= "$formalku";
		$nappikeikka .= "<input type='hidden' name='toiminto' value='poista'>";
		$nappikeikka .= "<input type='submit' value='".t("Poista keikka")."'>";
		$nappikeikka .= "$formloppu";
	}

	// jos on kohdistettuja rivej� niin saa tehd� n�it�
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

	// jos on kohdistettuja rivej� ja lis�tiedot on sy�tetty ja varastopaikat on ok ja on viel� jotain viet�v�� varastoon
	if ($kplyhteensa > 0 and $varok == 1 and $kplyhteensa != $kplvarasto and $sarjanrook == 1) {
		$nappikeikka .= "$formalku";
		$nappikeikka .= "<input type='hidden' name='toiminto' value='kalkyyli'>";
		$nappikeikka .= "<input type='submit' value='".t("Vie varastoon")."'>";
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