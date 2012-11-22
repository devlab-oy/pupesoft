<?php

$query  = "	SELECT *
			from lasku
			where tunnus	= '$otunnus'
			and yhtio		= '$kukarow[yhtio]'
			and tila		= 'K'
			and alatila 	= '$keikanalatila'";
$result = pupe_query($query);

if (mysql_num_rows($result) == 1) {
	$laskurow = mysql_fetch_array($result);
}
else {
	if (substr($luouusikeikka,0,2) == "OK") {
		// haetaan seuraava vapaa keikkaid
		$query  = "SELECT max(laskunro)+1 from lasku where yhtio='$kukarow[yhtio]' and tila='K'";
		$result = pupe_query($query);
		$row    = mysql_fetch_array($result);
		$id		= $row[0];

		if ($luouusikeikka == "OK") {
			$keiknimi = "Sarjanumeroseuranta";
			$keikanalatila = "S";
		}
		else {
			$keiknimi = "Myyntitilaus";
			$keikanalatila = "T";
		}

		$query = "	INSERT into lasku set
					yhtio        = '$kukarow[yhtio]',
					laskunro     = '$id',
					ytunnus	     = '$liitostunnus',
					nimi         = '$keiknimi',
					liitostunnus = '$liitostunnus',
					tila         = 'K',
					alatila      = '$keikanalatila',
					luontiaika	 = now(),
					laatija		 = '$kukarow[kuka]'";
		$result = pupe_query($query);

		$otunnus = mysql_insert_id();

		$query  = "	SELECT *
					from lasku
					where tunnus	= '$otunnus'
					and yhtio		= '$kukarow[yhtio]'
					and tila		= 'K'
					and alatila     = '$keikanalatila'";
		$result = pupe_query($query);
		$laskurow = mysql_fetch_array($result);
	}
	else {
		echo "<font class='error'>".t("Tilaus katosi!")."</font>";
		exit;
	}
}

if ($keikanalatila == "T") {
	$query = "SELECT * from oikeu where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]' and nimi LIKE '%ulask.php'";
	$res = pupe_query($query);

	if (mysql_num_rows($res) > 0) {
		echo "<font class='message'>".t("Syötä uusi lasku")." <form method='POST' action='".$palvelin2."ulask.php'>
				<input type='hidden' name='lopetus' value='$lopetus/SPLIT/$PHP_SELF////toim=$toim//tee=kululaskut//toiminto=kululaskut//projektilla=$projektilla//from=VALITSETOIMITUS//tilausnumero=$tilausnumero//keikanalatila=T//otunnus=$otunnus/NIMILISA=Liitä kululasku/'>
				<input type='submit' value='" . t('Syötä lasku')."'>
				</form><br><br>";
	}

	$query = "SELECT * from oikeu where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]' and nimi LIKE '%matkalasku.php'";
	$res = pupe_query($query);

	if (mysql_num_rows($res) > 0) {
		echo "<font class='message'>".t("Syötä uusi matkalasku")." <form method='POST' action='".$palvelin2."matkalasku.php'>
				<input type='hidden' name='lopetus' value='$lopetus/SPLIT/$PHP_SELF////toim=$toim//tee=kululaskut//toiminto=kululaskut//projektilla=$projektilla//from=VALITSETOIMITUS//tilausnumero=$tilausnumero//keikanalatila=T//otunnus=$otunnus/NIMILISA=Liitä kululasku/'>
				<input type='submit' value='" . t('Syötä lasku')."'>
				</form><br><br>";
	}
}

if ($tee_kululaskut == "liita") {

	// Haetaan ostolaskun tiedot
	$query = "	SELECT *
				from lasku
				where yhtio	= '$kukarow[yhtio]'
				and tunnus	= '$laskutunnus'";
	$result = pupe_query($query);
	$oslasrow = mysql_fetch_array($result);

	// jos meillä on hyvityslasku niin haetaan vaan negatiivisiä alvikirjauksia (en tiedä ollenkaan onko tämä futureprooof) :(
	if ($oslasrow["summa"] < 0) {
		$alvilisa = "and summa < 0";
	}
	else {
		$alvilisa = "and summa > 0";
	}

	// Haetaan kululaskun kaikki verotiliöinnit jotta voidaan tallentaa myös veroton summa
	$query = "	SELECT sum(summa) summa
				from tiliointi
				where yhtio	= '$kukarow[yhtio]'
				and ltunnus	= '$oslasrow[tunnus]'
				and tilino  = '$yhtiorow[alv]'
				$alvilisa
				and korjattu = ''";
	$alvires = pupe_query($query);
	$alvirow = mysql_fetch_array($alvires);

	// Ostoreskontralaskun veroton arvo
	$alvisumma = $alvirow["summa"];

	if (strtoupper($oslasrow["valkoodi"]) != strtoupper($yhtiorow["valkoodi"])) {
		$alvisumma = $alvirow["summa"] / $oslasrow["vienti_kurssi"];
	}

	$oslasrow["arvo"] = round((float) $oslasrow["summa"] - (float) $alvisumma, 2);

	// Pilkut pisteiksi
	$liitasumma = (float) str_replace(",", ".", $liitasumma);

	$query = "	SELECT sum(arvo) arvo
				from lasku
				where yhtio		= '$kukarow[yhtio]'
				and tila		= 'K'
				and vanhatunnus	= '$laskutunnus'";
	$apure = pupe_query($query);
	$apurow = mysql_fetch_array($apure);

	// Tämän kululaskun käytettävissä oleva summa
	$summa_kaytettavissa = round((float) $oslasrow["arvo"] - (float) $apurow["arvo"], 2);

	// Jos käyttäjä on syöttänyt osasumman kululaskulta
	if ($liitasumma != 0) {
		//jos tämä on hyvityslasku käännetään käyttäjän syöttämä summa aina negatiiviseksi
		if ($oslasrow["arvo"] < 0) {
			$liitasumma = -1 * abs($liitasumma);
		}
		else {
			$liitasumma = abs($liitasumma);
		}
		
		$verosumma		= (float) round($liitasumma * ($oslasrow["summa"]/($oslasrow["summa"]-$alvisumma)), 2);
		$verotonsumma	= (float) $liitasumma;
	}
	elseif (abs($summa_kaytettavissa-$oslasrow["arvo"]) >= 0.01) {
		$verosumma		= (float) round($liitasumma * ($oslasrow["summa"]/($oslasrow["summa"]-$alvisumma)), 2);
		$verotonsumma	= (float) $liitasumma;
	}
	else {
		//Tässä on ostolaskun verollinen summa (Tarvitaan alvillisissa vaihto-omaisuuslaskuissa)
		$verosumma 		= (float) $oslasrow["summa"];
		//Ja veroton summa
		$verotonsumma 	= (float) $oslasrow["arvo"];
	}

	if ($summa_kaytettavissa > 0 and $verotonsumma > 0 and $summa_kaytettavissa < $verotonsumma) {
		echo "<font class='error'>".t("VIRHE: Yritit liittää liian suuren summan. Laskun saldo ei riittänyt")."!</font><br>";
		$tee_kululaskut	= "haku";
		$verosumma 		= 0;
		$verotonsumma 	= 0;
	}
	elseif ($summa_kaytettavissa < 0 and $verotonsumma < 0 and $summa_kaytettavissa > $verotonsumma) {
		echo "<font class='error'>".t("VIRHE: Yritit liittää liian suuren summan. Laskun saldo ei riittänyt")."!</font><br>";
		$tee_kululaskut	= "haku";
		$verosumma 		= 0;
		$verotonsumma 	= 0;
	}

	//lisätään lasku
	if ($verotonsumma != 0 and $verosumma != 0) {

		$osto_kulu = (!isset($osto_kulu) or trim($osto_kulu) == '') ? 0 : (float) $osto_kulu;
		$osto_rahti = (!isset($osto_rahti) or trim($osto_rahti) == '') ? 0 : (float) $osto_rahti;
		$osto_rivi_kulu = (!isset($osto_rivi_kulu) or trim($osto_rivi_kulu) == '') ? 0 : (float) $osto_rivi_kulu;

		$osto_kulu_alv = (!isset($osto_kulu_alv) or trim($osto_kulu_alv) == '') ? 0 : (float) $osto_kulu_alv;
		$osto_rahti_alv = (!isset($osto_rahti_alv) or trim($osto_rahti_alv) == '') ? 0 : (float) $osto_rahti_alv;
		$osto_rivi_kulu_alv = (!isset($osto_rivi_kulu_alv) or trim($osto_rivi_kulu_alv) == '') ? 0 : (float) $osto_rivi_kulu_alv;

		// meillä on laskurow haettuna ylhäällä, tehdään liitosotsikko
		$query = "	INSERT into lasku set
					yhtio        		= '$kukarow[yhtio]',
					laskunro     		= '$laskurow[laskunro]',
					ytunnus	     		= '$oslasrow[ytunnus]',
					nimi         		= '$oslasrow[nimi]',
					tapvm        		= '$oslasrow[tapvm]',
					valkoodi     		= '$oslasrow[valkoodi]',
					maksu_kurssi 		= '$oslasrow[maksu_kurssi]',
					vienti_kurssi		= '$oslasrow[vienti_kurssi]',
					toimitusehto 		= '$oslasrow[toimitusehto]',
					vanhatunnus  		= '$oslasrow[tunnus]',
					arvo 		 		= '$verotonsumma',
					viite		 		= '$oslasrow[viite]',
					summa        		= '$verosumma',
					vienti       		= '$oslasrow[vienti]',
					liitostunnus 		= '$oslasrow[liitostunnus]',
					osto_kulu	 		= '{$osto_kulu}',
					osto_rahti   		= '{$osto_rahti}',
					osto_rivi_kulu 		= '{$osto_rivi_kulu}',
					osto_kulu_alv 		= '{$osto_kulu_alv}',
					osto_rahti_alv 		= '{$osto_rahti_alv}',
					osto_rivi_kulu_alv	= '{$osto_rivi_kulu_alv}',
					laatija				= '$kukarow[kuka]',
					luontiaika	 		= now(),
					tila         		= 'K'";
		$result = pupe_query($query);

		if ($yhtiorow['ostolaskun_kulutilit'] != '' and ($yhtiorow['osto_rahti'] != '' or $yhtiorow['osto_kulu'] != '' or $yhtiorow['osto_rivi_kulu'] != '') and ($oslasrow['vienti'] == 'C' or $oslasrow['vienti'] == 'F' or $oslasrow['vienti'] == 'I') and ($osto_kulu != 0 or $osto_rahti != 0 or $osto_rivi_kulu != 0)) {

			$tmp_tunnus = $tunnus;
			$tmp_laskurow = $laskurow;
			$tmp_tee = $tee;

			$tunnus = $laskutunnus;
			$tila = 'ostolasku_kulut';

			require('inc/muutosite.inc');

			$tunnus = $tmp_tunnus;
			$laskurow = $tmp_laskurow;
			$tee = $tmp_tee;
		}

		if ($silent == "") echo "<font class='message'>".t("Lasku")." ($oslasrow[nimi] $oslasrow[summa] $oslasrow[valkoodi]) ".t("liitettiin saapumiseen")." $laskurow[laskunro].</font><br><br>";

		// jos kyseessä on vaihto-omaisuuslasku, niin päivitetään laskun tapvm keikalla ja vientikentän info keikan otsikolle (jos jostain syystä toimittajan oletus onkin väärin)
		if ($oslasrow["vienti"] == "C" or $oslasrow["vienti"] == "F" or $oslasrow["vienti"] == "I" or $oslasrow["vienti"] == "J" or $oslasrow["vienti"] == "K" or $oslasrow["vienti"] == "L") {

			// Jos keikka on jo loppulaskettu (eli tässä vain jaetaan lasku osiin, niin ei nollata kohdistettu-kenttää)
			if ($keikanalatila == "X") {
				$upd_kohdistettu = "X";
			}
			else {
				$upd_kohdistettu = "";
			}

			$query = "	UPDATE lasku set
						vienti			= '$oslasrow[vienti]',
						tapvm			= '$oslasrow[tapvm]',
						valkoodi		= '$oslasrow[valkoodi]',
						maksu_kurssi 	= '$oslasrow[maksu_kurssi]',
						vienti_kurssi	= '$oslasrow[vienti_kurssi]',
						kohdistettu		= '$upd_kohdistettu'
						where yhtio		= '$kukarow[yhtio]'
						and tunnus		= '$laskurow[tunnus]'";
			$result = pupe_query($query);
		}

		// takasin selaukseen
		$toiminto = "kululaskut";
		$ytunnus  = $laskurow["ytunnus"];
	}
}

if ($tee_kululaskut == "poista") {

	if ($tunnus != "") {
		// poistetaan liitetty ostolasku
		$query  = "DELETE from lasku where yhtio='$kukarow[yhtio]' and tila='K' and laskunro='$laskurow[laskunro]' and summa='$poistasumma' and vanhatunnus='$tunnus'";
		$result = pupe_query($query);

		// jos poistettiin vaihto-omaisuuslasku niin laitetaan oletukset takasin
		if ($poistavienti == "C" or $poistavienti == "F" or $poistavienti == "I" or $poistavienti == "J" or $poistavienti == "K" or $poistavienti == "L") {

			if ($yhtiorow['ostolaskun_kulutilit'] != '' and ($yhtiorow['osto_rahti'] != '' or $yhtiorow['osto_kulu'] != '' or $yhtiorow['osto_rivi_kulu'] != '') and ($poistavienti == 'C' or $poistavienti == 'F' or $poistavienti == 'I')) {

				$tmp_tunnus = $tunnus;
				$tmp_laskurow = $laskurow;
				$tmp_tee = $tee;

				$tila = 'ostolasku_kulut';

				require('inc/muutosite.inc');

				$tunnus = $tmp_tunnus;
				$laskurow = $tmp_laskurow;
				$tee = $tmp_tee;
			}

			//jos keikalta poistettiin vaihto-omaisuuslasku niin merkataan se ei kohdistetuksi
			$query = "	UPDATE lasku set
						kohdistettu		= ''
						where yhtio		= '$kukarow[yhtio]'
						and tunnus		= '$laskurow[tunnus]'";
			$result = pupe_query($query);

			//katotaan jäikö keikalle enää yhtään vaihto-omaisuuslaskua
			$query  = "	SELECT tunnus
						from lasku
						where yhtio='$kukarow[yhtio]' and tila='K' and alatila = '$keikanalatila' and vanhatunnus<>0 and laskunro='$laskurow[laskunro]' and vienti in ('C','F','I','J','K','L')";
			$result = pupe_query($query);

			//vaihto-omaisuuslasku/laskuja ei löytynyt
			if (mysql_num_rows($result) == 0) {
				// haetaan toimittajan tiedot uusiks
				$query = "SELECT * from toimi where yhtio='$kukarow[yhtio]' and tunnus='$toimittajaid'";
				$kurres = pupe_query($query);
				$asiakasrow = mysql_fetch_array($kurres);

				// haetaan oletuskurssi uusiksi
				$query  = "SELECT kurssi from valuu where nimi='$asiakasrow[oletus_valkoodi]' and yhtio='$kukarow[yhtio]'";
				$kurres = pupe_query($query);
				$kurrow = mysql_fetch_array($kurres);
				$kurssi = $kurrow["kurssi"];

				// palautetaan keikalle oletusarvot
				$query = "UPDATE lasku set vienti='$asiakasrow[oletus_vienti]', tapvm='', valkoodi='$asiakasrow[oletus_valkoodi]', vienti_kurssi='$kurssi' where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[tunnus]'";
				$result = pupe_query($query);
			}
		}
	}
	else {
		echo "<font class='error'>".t("Laskua ei voida poistaa!")."<br><font>";
	}

	// takasin laskuhakuun
	$tee_kululaskut = "";
}

if ($keikanalatila == "T" and $tee_kululaskut == "lisaa_rivit" and $kukarow["kesken"] > 0) {
	$query 	= "	SELECT *
				FROM tuote
				WHERE tuoteno = '$yhtiorow[laskutuslisa_tuotenumero]' and yhtio = '$kukarow[yhtio]'";
	$tuoteres = pupe_query($query);

	if (mysql_num_rows($tuoteres) != 1) {
		echo t("VIRHE: Yhtiön laskutuslisa_tuotenumero ei ole määritelty")."!!<br>";
		$tee_kululaskut = "";
	}
}

if ($keikanalatila == "T" and $tee_kululaskut == "lisaa_rivit" and $kukarow["kesken"] > 0) {

	$laskurow_save["alv"] = $laskurow["alv"];

	//yhtiön oletusalvi!
	$wquery = "SELECT selite from avainsana where yhtio='$kukarow[yhtio]' and laji='alv' and selitetark!=''";
	$wtres  = pupe_query($wquery);
	$wtrow  = mysql_fetch_array($wtres);

	$query 	= "	SELECT *
				FROM lasku
				WHERE tunnus = '$kukarow[kesken]' and yhtio = '$kukarow[yhtio]'";
	$result = pupe_query($query);
	$myy_laskrow = mysql_fetch_array($result);

	$query  = "	SELECT distinct tilausrivi.tunnus, tilausrivi.tuoteno, tilausrivi.hinta, tilausrivi.kpl, tilausrivi.kommentti, tilausrivi.nimitys
				FROM lasku keikka
				JOIN lasku liitosotsikko ON keikka.yhtio=liitosotsikko.yhtio and keikka.laskunro=liitosotsikko.laskunro and keikka.tila=liitosotsikko.tila and liitosotsikko.alatila='' and liitosotsikko.vanhatunnus!=0
				JOIN lasku matkalasku ON matkalasku.yhtio=liitosotsikko.yhtio and matkalasku.tunnus=liitosotsikko.vanhatunnus
				JOIN tilausrivi ON tilausrivi.yhtio=matkalasku.yhtio and tilausrivi.otunnus=matkalasku.tunnus
				WHERE keikka.yhtio		= '$kukarow[yhtio]'
				and keikka.tila			= 'K'
				and keikka.alatila		= 'T'
				and keikka.liitostunnus	= '$kukarow[kesken]'
				and keikka.ytunnus		= '$kukarow[kesken]'
				and matkalasku.tunnus	= $matkalasktunnus
				and tilausrivi.kpl 		> 0
				and tilausrivi.tyyppi  != 'D'";
	$keikkares = pupe_query($query);

	while ($keikkarow = mysql_fetch_array($keikkares)) {

		// Tutkitaan löytääkö tämä rivi jo työmääräyksellä
		$query = "	SELECT tilausrivi.tunnus
					FROM tilausrivi
					JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus
		 			WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.tyyppi != 'D'
					and tilausrivi.otunnus = '$kukarow[kesken]'
					and tilausrivin_lisatiedot.tilausrivilinkki = '$keikkarow[tunnus]'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			// tuote löytyi ok, lisätään rivi
			$trow = mysql_fetch_array($tuoteres);

			$tilausrivilinkki	= $keikkarow["tunnus"];
			$kpl             	= $keikkarow["kpl"];
			$tuoteno         	= $trow["tuoteno"];
			$tuotenimitys_force	= $keikkarow["nimitys"];
			$toimaika 	     	= $myy_laskrow["toimaika"];
			$kerayspvm	     	= $myy_laskrow["kerayspvm"];
			$hinta 		     	= $keikkarow["hinta"];
			$kommentti 		    = $keikkarow["kommentti"];
			$alv 				= (float) $wtrow["selite"];
			$laskurow["alv"] 	= $myy_laskrow["alv"];

			require ("lisaarivi.inc");
		}
	}

	echo t("Matkalaskun rivit lisättiin tilaukselle")."!<br>";

	$tyhjenna		 = "OK";
	$laskurow["alv"] = $laskurow_save["alv"];
}

if ($silent == "") {
	if ($toiminto == "kululaskut") {

		// katotaan onko liitettyjä laskuja
		$query  = "	SELECT lasku.*, l2.comments
					FROM lasku
					LEFT JOIN lasku l2 ON l2.yhtio=lasku.yhtio and l2.tunnus = lasku.vanhatunnus
					WHERE lasku.yhtio='$kukarow[yhtio]'
					and lasku.tila='K'
					and lasku.alatila = ''
					and lasku.vanhatunnus<>0
					and lasku.laskunro='$laskurow[laskunro]'
					ORDER BY lasku.tapvm desc";
		$result = pupe_query($query);

		if (mysql_num_rows($result) != 0) {

			echo "<br><font class='message'>".t("Liitetyt laskut")." (".t("saapuminen").": $laskurow[laskunro] $laskurow[nimi])</font><br><br>";
			echo "<table>";
			echo "<tr>
					<th>".t("Nimi")."</th>
					<th>".t("Pvm")."</th>
					<th>".t("Summa")."</th>
					<th>".t("Veroton")."</th>
					<th>".t("Valuutta")."</th>
					<th>".t("Viite")."</th>
					<th>".t("Kuva")."</th>
					<th>".t("Vienti")."</th>";

			if ($yhtiorow['ostolaskun_kulutilit'] != '' and ($yhtiorow['osto_rahti'] != '' or $yhtiorow['osto_kulu'] != '' or $yhtiorow['osto_rivi_kulu'] != '')) {
				echo "<th>",t("Rahdit"),"</th>";
				echo "<th>",t("Kulut"),"</th>";
				echo "<th>",t("Rivikohtaiset kulut"),"</th>";
			}

			echo "</tr>";

			mysql_data_seek($result, 0);

			while($llrow = mysql_fetch_array($result)) {

				$comments = "";
				if($llrow["comments"] != "") {
					$comments = "<br><i>$llrow[comments]</i>asd";
				}

				echo "<tr>
						<td valign='top'>$llrow[nimi]$comments</td>
						<td valign='top' NOWRAP>".tv1dateconv($llrow["tapvm"])."</td>
						<td valign='top' align='right' NOWRAP>$llrow[summa]</td>
						<td valign='top' align='right' NOWRAP>$llrow[arvo]</td>
						<td valign='top'>$llrow[valkoodi]</td>
						<td valign='top'>$llrow[viite]</td>";

				$query  = "	SELECT tunnus, tilaustyyppi
							from lasku
							where yhtio	= '$kukarow[yhtio]'
							and tunnus	= '$llrow[vanhatunnus]'";
				$osresres = pupe_query($query);
				$osresrow = mysql_fetch_array($osresres);

				// tehdään lasku linkki
				echo "<td valign='top'>" . ebid($osresrow['tunnus']) . "</td>";

				$vienti_teksti = "";

				if ($llrow["vienti"] == 'A') $vienti_teksti = t("Kotimaa");
				if ($llrow["vienti"] == 'B') $vienti_teksti = t("Kotimaa huolinta/rahti");
				if ($llrow["vienti"] == 'C') $vienti_teksti = t("Kotimaa vaihto-omaisuus");
				if ($llrow["vienti"] == 'J') $vienti_teksti = t("Kotimaa raaka-aine");
				if ($llrow["vienti"] == 'D') $vienti_teksti = t("EU");
				if ($llrow["vienti"] == 'E') $vienti_teksti = t("EU huolinta/rahti");
				if ($llrow["vienti"] == 'F') $vienti_teksti = t("EU vaihto-omaisuus");
				if ($llrow["vienti"] == 'K') $vienti_teksti = t("EU raaka-aine");
				if ($llrow["vienti"] == 'G') $vienti_teksti = t("ei-EU");
				if ($llrow["vienti"] == 'H') $vienti_teksti = t("ei-EU huolinta/rahti");
				if ($llrow["vienti"] == 'I') $vienti_teksti = t("ei-EU vaihto-omaisuus");
				if ($llrow["vienti"] == 'L') $vienti_teksti = t("ei-EU raaka-aine");

				echo "<td valign='top'>$vienti_teksti</td>";

				if ($yhtiorow['ostolaskun_kulutilit'] != '' and ($yhtiorow['osto_rahti'] != '' or $yhtiorow['osto_kulu'] != '' or $yhtiorow['osto_rivi_kulu'] != '')) {
					echo "<td valign='top'>",sprintf("%.2f", $llrow['osto_rahti']),"</td>";
					echo "<td valign='top'>",sprintf("%.2f", $llrow['osto_kulu']),"</td>";
					echo "<td valign='top'>",sprintf("%.2f", $llrow['osto_rivi_kulu']),"</td>";
				}

				echo "<td valign='top' class='back'>
						<form name='haku' method='post' autocomplete='off'>
						<input type='hidden' name='keikanalatila' value='$keikanalatila'>
						<input type='hidden' name='lopetus' value='$lopetus'>
						<input type='hidden' name='toimittajaid' value='$toimittajaid'>
						<input type='hidden' name='toiminto' value='kululaskut'>
						<input type='hidden' name='poistavienti' value='$llrow[vienti]'>
						<input type='hidden' name='tee_kululaskut' value='poista'>
						<input type='hidden' name='tee' value='$tee'>
						<input type='hidden' name='otunnus' value='$otunnus'>
						<input type='hidden' name='tilausnumero' value='$tilausnumero'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='projektilla' value='$projektilla'>
						<input type='hidden' name='tunnus' value='$llrow[vanhatunnus]'>
						<input type='hidden' name='poistasumma' value='$llrow[summa]'>
						<input type='hidden' name='osto_kulu' value='{$llrow['osto_kulu']}'>
						<input type='hidden' name='osto_rahti' value='{$llrow['osto_rahti']}'>
						<input type='hidden' name='osto_rivi_kulu' value='{$llrow['osto_rivi_kulu']}'>
						<input type='hidden' name='osto_kulu_alv' value='{$llrow['osto_kulu_alv']}'>
						<input type='hidden' name='osto_rahti_alv' value='{$llrow['osto_rahti_alv']}'>
						<input type='hidden' name='osto_rivi_kulu_alv' value='{$llrow['osto_rivi_kulu_alv']}'>
						<input type='submit' value='".t("Poista")."'></form>
						</td>";

				if ($osresrow["tilaustyyppi"] == 'M' and $keikanalatila == "T") {

					$query  = "	SELECT count(distinct matkarivi.tunnus) mrivi, count(distinct tilausrivin_lisatiedot.tunnus) trivi
								FROM lasku matkalasku
								JOIN tilausrivi matkarivi ON matkarivi.yhtio=matkalasku.yhtio and matkarivi.otunnus=matkalasku.tunnus and matkarivi.kpl > 0 and matkarivi.tyyppi  != 'D'
								LEFT JOIN tilausrivi tilrivi ON tilrivi.yhtio=matkalasku.yhtio and tilrivi.otunnus = '$kukarow[kesken]' and tilrivi.tyyppi  != 'D'
								LEFT JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio=tilrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilrivi.tunnus and tilausrivin_lisatiedot.tilausrivilinkki = matkarivi.tunnus
								WHERE matkalasku.yhtio	= '$kukarow[yhtio]'
								and matkalasku.tunnus	= $osresrow[tunnus]";
					$mlrivires = pupe_query($query);
					$mlrivirow = mysql_fetch_assoc($mlrivires);

					if ($mlrivirow["mrivi"] > 0 and $mlrivirow["mrivi"] != $mlrivirow["trivi"]) {
						echo "<td valign='top' class='back'>
								<form method='POST'>
								<input type='hidden' name='keikanalatila' value='$keikanalatila'>
								<input type='hidden' name='lopetus' value='$lopetus'>
								<input type='hidden' name='toimittajaid' value='$toimittajaid'>
								<input type='hidden' name='toiminto' value='kululaskut'>
								<input type='hidden' name='tee_kululaskut' value='lisaa_rivit'>
								<input type='hidden' name='matkalasktunnus' value='$osresrow[tunnus]'>
								<input type='hidden' name='tee' value='$tee'>
								<input type='hidden' name='otunnus' value='$otunnus'>
								<input type='hidden' name='tilausnumero' value='$tilausnumero'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='projektilla' value='$projektilla'>
								<input type='submit' value='" . t('Lisää matkalaskun rivit tilaukselle')."'></form>
								</form></td>";
					}
				}

				echo "</tr>";
			}
			echo "</table>";

			echo "<hr>";
		}

		echo "<form name='haku' method='post' autocomplete='off'>
				<input type='hidden' name='keikanalatila' value='$keikanalatila'>
				<input type='hidden' name='lopetus' value='$lopetus'>
				<input type='hidden' name='toimittajaid' value='$toimittajaid'>
				<input type='hidden' name='toiminto' value='kululaskut'>
				<input type='hidden' name='tee_kululaskut' value='haku'>
				<input type='hidden' name='tee' value='$tee'>
				<input type='hidden' name='otunnus' value='$otunnus'>
				<input type='hidden' name='tilausnumero' value='$tilausnumero'>
				<input type='hidden' name='toim' value='$toim'>
				<input type='hidden' name='projektilla' value='$projektilla'>";

		$sel1 = "";
		$sel2 = "";
		$sel3 = "";

		if ($hakutapa == '' or $hakutapa == 'S') {
			$sel1 = "SELECTED";
		}
		elseif ($hakutapa == "N") {
			$sel2 = "SELECTED";
		}
		elseif ($hakutapa == "V") {
			$sel3 = "SELECTED";
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

		echo "<table><tr>";
		echo "<th>".t("Etsi lasku")."</th>";
		echo "<th><SELECT name='hakutapa'>";
		echo "<option value='S' $sel1>".t("verollisella summalla")."</option>";
		echo "<option value='N' $sel2>".t("nimellä")."</option>";
		echo "<option value='V' $sel3>".t("viitteellä")."</option>";
		echo "</SELECT></th>";
		echo "<td><input type='text' name='haku' value='$haku'></td>";
		echo "<td class='back'><input type='submit' value='".t("Hae")."'></td>";
		echo "</tr>";

		if ($listaustapa == "liittamattomat") {
			$sel1 = "SELECTED";
			$sel2 = "";
		}
		elseif ($listaustapa == "liitetyt") {
			$sel2 = "SELECTED";
			$sel1 = "";
		}
		elseif (!isset($listaustapa)) {
			$sel1 = "SELECTED";
			$sel2 = "";
		}
		else {
			$sel1 = "";
			$sel2 = "";
		}

		echo "<tr>";
		echo "<th align='left'>".t("Näytä laskut")."</th>";
		echo "<th align='left'><select name='listaustapa'>";
		echo "<option value=''>".t("Näytä kaikki")."</option>";
		echo "<option value='liittamattomat' $sel1>".t("Näytä vain liittämättömät")."</option>";
		echo "<option value='liitetyt' $sel2>".t("Näytä vain liitetyt")."</option>";
		echo "</select></th>";
		echo "<td>&nbsp;</td>";
		echo "<td class='back'><input type='submit' value='".t("Hae")."'></td>";
		echo "</tr></table>";

		echo "</form>";

		// kursorinohjausta
		$formi    = "haku";
		$kentta   = "haku";
	}

	if(!isset($hakutapa) and !isset($tee_kululaskut) and $keikanalatila == "") {
		$hakutapa 		= "N";
		$haku 			= substr($laskurow["nimi"], 0, 25);
		$tee_kululaskut = "haku";
	}

	if ($tee_kululaskut == "haku") {

		$aikalisa = "";
		if (!isset($listaustapa)) {
			$listaustapa = "liittamattomat";
			$aikalisa = " AND lasku.tapvm > date_sub(now(), INTERVAL 1 YEAR)";
		}

		$lisa = ""; // no hacking
		$haku = mysql_real_escape_string(trim($haku));

		if ($haku == "") {
			$hakutapa = "";
		}

		//	Haetaan oletuksena nimen alulla!!
		if ($hakutapa == "S") {
			$haku = (float) str_replace(",",".",$haku); // pilkut pisteiksi
			$lisa = " and lasku.summa = '$haku'";
		}

		if ($hakutapa == "N") $lisa = " and lasku.nimi like '%$haku%'";
		if ($hakutapa == "V") $lisa = " and lasku.viite = '$haku'";

//		if ($hakutapa == "" or $hakutapa != "N") {
//			$lisa .= " and lasku.ytunnus = '$laskurow[ytunnus]'";
//		}

		// katsotaan aluksi, ollaanko tähän keikkaan liitetty jo vaihto-omaisuuslasku
		$query  = "	SELECT *
					from lasku
					where yhtio='$kukarow[yhtio]' and tila='K' and alatila = '$keikanalatila' and vanhatunnus<>0 and laskunro='$laskurow[laskunro]' and vienti in ('C','F','I','J','K','L')";
		$result = pupe_query($query);

		$vienti 	= "";
		$valuutta 	= "";

		//vaihto-omaisuuslasku/laskuja löytyi
		if (mysql_num_rows($result) != 0) {
			$vaihtrow = mysql_fetch_array($result);

			//näitä tarvitaan jos haluamme liittää lisää vaihto-omaisuuslaskuja keikalle
			$valuutta 	= $vaihtrow["valkoodi"];
			$vienti		= $vaihtrow["vienti"];
		}

		if ($keikanalatila == "T") {
			$vientitilat = " and lasku.vienti in ('A','D','G') ";
		}
		elseif ($yhtiorow["jalkilaskenta_kuluperuste"] == 'KP') {
			$vientitilat = " and lasku.vienti in ('C','J','F','K','I','L') ";
		}
		elseif ($yhtiorow["jalkilaskenta_kuluperuste"] == 'PX') {
			$vientitilat = " and lasku.vienti in ('B','C','J','E','F','K','H','I','L') ";
		}
		else {
			$vientitilat = " and lasku.vienti in ('B','C','J','E','F','K','H','I','L') ";
		}

		// haetaan vaihto-omaisuus, rahti ja raaka-aine laskuja (kotimaa, ei-eu, eu) HUOMAA LIMIT!
		$query = "	SELECT lasku.*, (select min(tunnus) from tiliointi where lasku.yhtio=tiliointi.yhtio and lasku.tunnus=tiliointi.ltunnus and tiliointi.korjattu='') tiliointi
					FROM lasku use index (kohdistus_index)
					WHERE lasku.yhtio='$kukarow[yhtio]'
					and lasku.tila in ('H','Y','M','P','Q')
					$vientitilat
					$lisa
					$aikalisa
					HAVING tiliointi is not null
					order by lasku.tapvm desc";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Yhtään sopivaa laskua ei löytynyt!")."</font><br><br>";
			$tee_kululaskut = "";
		}
		else {

			echo "<br><table>";
			echo "<tr>
					<th>".t("Nimi")."</th>
					<th>".t("Pvm")."</th>
					<th>".t("Summa")."</th>
					<th>".t("Veroton")."</th>
					<th>".t("Valuutta")."</th>
					<th>".t("Viite")."</th>
					<th>".t("Kuva")."</th>
					<th>".t("Vienti")."</th>
					<th>".t("Liitettävissä")."</th>
					<th>".t("Summa")."</th>";

			if ($yhtiorow['ostolaskun_kulutilit'] != '' and ($yhtiorow['osto_rahti'] != '' or $yhtiorow['osto_kulu'] != '' or $yhtiorow['osto_rivi_kulu'] != '')) {
				echo "<th>",t("Rahdit"),"</th>";
				echo "<th>",t("Kulut"),"</th>";
				echo "<th>",t("Rivikohtaiset kulut"),"</th>";
			}

			echo "</tr>";

			while ($llrow = mysql_fetch_array($result)) {

				// jos meillä on hyvityslasku niin haetaan vaan negatiivisiä alvikirjauksia (en tiedä ollenkaan onko tämä futureprooof) :(
				if ($llrow["summa"] < 0) {
					$alvilisa = "and summa < 0";
				}
				else {
					$alvilisa = "and summa > 0";
				}

				// Haetaan kululaskun kaikki verotiliöinnit jotta voidaan tallentaa myös veroton summa
				$query = "	SELECT sum(summa) summa
							from tiliointi
							where yhtio	= '$kukarow[yhtio]'
							and ltunnus	= '$llrow[tunnus]'
							and tilino  = '$yhtiorow[alv]'
							$alvilisa
							and korjattu = ''";
				$alvires = pupe_query($query);
				$alvirow = mysql_fetch_array($alvires);

				//Tässä on ostolasku verollinen summa
				$verosumma = $llrow["summa"];

				// Ostoreskontralaskun veroton arvo
				$alvisumma = $alvirow["summa"];

				if (strtoupper($oslasrow["valkoodi"]) != strtoupper($yhtiorow["valkoodi"])) {
					$alvisumma = $alvirow["summa"] / $llrow["vienti_kurssi"];
				}

				//Ja lopuksi vielä veroton summa
				$verotonsumma = $verosumma - $alvisumma;

				$query = "	SELECT sum(arvo) arvo, GROUP_CONCAT(DISTINCT laskunro ORDER BY laskunro SEPARATOR ', ') laskunrot
							from lasku
							where yhtio		= '$kukarow[yhtio]'
							and tila		= 'K'
							and vanhatunnus	= '$llrow[tunnus]'";
				$apure = pupe_query($query);
				$apurow = mysql_fetch_array($apure);

				$summa_kaytettavissa = round(abs($verotonsumma) - abs($apurow["arvo"]),2);

				if ($summa_kaytettavissa > 0) {
					if ($listaustapa == "liittamattomat" or $listaustapa == "") {
						// checkkaus tässä ettei piiretä turhaan
						$voikolisata = "";

						//kululaskut voidaan aina lisätä
						if ($llrow["vienti"] != "C" and $llrow["vienti"] != "F" and $llrow["vienti"] != "I" and $llrow["vienti"] != "J" and $llrow["vienti"] != "K" and $llrow["vienti"] != "L") {
							$voikolisata = "OK";
						}
						//jos tämä on vahto-omaisuuslasku pitää vielä tarkistaa vienti ja valuutta
						elseif ($laskurow["alatila"] != "T" and $laskurow["alatila"] != "S" and $llrow["valkoodi"] == $valuutta and $llrow["vienti"] == $vienti and ($llrow["vienti"] == "C" or $llrow["vienti"] == "F" or $llrow["vienti"] == "I" or $llrow["vienti"] == "J" or $llrow["vienti"] == "K" or $llrow["vienti"] == "L")) {
							$voikolisata = "OK";
						}
						elseif ($laskurow["alatila"] != "T" and $laskurow["alatila"] != "S" and $vienti == '' and $valuutta == '' and ($llrow["vienti"] == "C" or $llrow["vienti"] == "F" or $llrow["vienti"] == "I" or $llrow["vienti"] == "J" or $llrow["vienti"] == "K" or $llrow["vienti"] == "L")) {
							$voikolisata = "OK";
						}

						if ($voikolisata != "OK" and $listaustapa == "liittamattomat") {
							continue;
						}

					}
					else {
						continue;
					}
				}
				elseif ($listaustapa == "liitetyt" or $listaustapa =="") {
					// annetaan mennä läpi
				}
				else {
					continue;
				}

				echo "<tr><td valign='top'><strong>$llrow[nimi]</strong>";

				if ($llrow["comments"] != '') {
					echo "<br><i>$llrow[comments]</i>";
				}

				echo "</td>";

				echo "	<td valign='top' NOWRAP>".tv1dateconv($llrow["tapvm"])."</td>
						<td align='right' valign='top' NOWRAP>".sprintf('%.2f',$verosumma)."</td>
						<td align='right' valign='top' NOWRAP>".sprintf('%.2f',$verotonsumma)."</td>
						<td valign='top'>$llrow[valkoodi]</td>
						<td valign='top'>$llrow[viite]</td>";

				// tehdään lasku linkki
				echo "<td valign='top'>".ebid($llrow['tunnus']) ."</td>";

				$vienti_teksti = "";

				if ($llrow["vienti"] == 'A') $vienti_teksti = t("Kotimaa");
				if ($llrow["vienti"] == 'B') $vienti_teksti = t("Kotimaa huolinta/rahti");
				if ($llrow["vienti"] == 'C') $vienti_teksti = t("Kotimaa vaihto-omaisuus");
				if ($llrow["vienti"] == 'J') $vienti_teksti = t("Kotimaa raaka-aine");
				if ($llrow["vienti"] == 'D') $vienti_teksti = t("EU");
				if ($llrow["vienti"] == 'E') $vienti_teksti = t("EU huolinta/rahti");
				if ($llrow["vienti"] == 'F') $vienti_teksti = t("EU vaihto-omaisuus");
				if ($llrow["vienti"] == 'K') $vienti_teksti = t("EU raaka-aine");
				if ($llrow["vienti"] == 'G') $vienti_teksti = t("ei-EU");
				if ($llrow["vienti"] == 'H') $vienti_teksti = t("ei-EU huolinta/rahti");
				if ($llrow["vienti"] == 'I') $vienti_teksti = t("ei-EU vaihto-omaisuus");
				if ($llrow["vienti"] == 'L') $vienti_teksti = t("ei-EU raaka-aine");

				echo "<td valign='top'>$vienti_teksti</td>";

				if ($summa_kaytettavissa > 0) {

					if ($verotonsumma < 0) {
						$lw = -1;
					}
					else {
						$lw = 1;
					}

					$summa_kaytettavissa = $summa_kaytettavissa * $lw;

					echo "<td class='green' align='right' valign='top'>".sprintf('%.2f',$summa_kaytettavissa)."</td>";

					if ($voikolisata == "OK") {

						echo "<form name='haku' method='post'>";

						if ($llrow["vienti"] != "C" and $llrow["vienti"] != "F" and $llrow["vienti"] != "I" and $llrow["vienti"] != "J" and $llrow["vienti"] != "K" and $llrow["vienti"] != "L") {
							echo "<td valign='top'><input type='text' name='liitasumma' size='8'></td>";
						}
						else {
							echo "<td valign='top'></td>";
						}

						if ($yhtiorow['ostolaskun_kulutilit'] != '' and ($yhtiorow['osto_rahti'] != '' or $yhtiorow['osto_kulu'] != '' or $yhtiorow['osto_rivi_kulu'] != '')) {

							if ($llrow['vienti'] == 'C' or $llrow['vienti'] == 'F' or $llrow['vienti'] == 'I') {

								echo "<td valign='top'><input type='text' name='osto_rahti' size='10' value='",sprintf('%.2f',$llrow['osto_rahti']),"' /> {$llrow['valkoodi']}";
								echo "<br />",alv_popup('osto_rahti_alv', $llrow['osto_rahti_alv'])," ",t("alv"),"</td>";
								echo "<td valign='top'><input type='text' name='osto_kulu' size='10' value='",sprintf('%.2f',$llrow['osto_kulu']),"' /> {$llrow['valkoodi']}";
								echo "<br />",alv_popup('osto_kulu_alv', $llrow['osto_kulu_alv'])," ",t("alv"),"</td>";
								echo "<td valign='top'><input type='text' name='osto_rivi_kulu' size='10' value='",sprintf('%.2f',$llrow['osto_rivi_kulu']),"' /> {$llrow['valkoodi']}";
								echo "<br />",alv_popup('osto_rivi_kulu_alv', $llrow['osto_rivi_kulu_alv'])," ",t("alv"),"</td>";
							}
							else {
								echo "	<td valign='top'></td>
										<td valign='top'></td>
										<td valign='top'></td>";
							}
						}

						echo "<td class='back' valign='top'>
								<input type='hidden' name='keikanalatila' value='$keikanalatila'>
								<input type='hidden' name='lopetus' value='$lopetus'>
								<input type='hidden' name='toimittajaid' value='$toimittajaid'>
								<input type='hidden' name='toiminto' value='kululaskut'>
								<input type='hidden' name='hakutapa' value='$hakutapa'>
								<input type='hidden' name='haku' value='$haku'>
								<input type='hidden' name='tee_kululaskut' value='liita'>
								<input type='hidden' name='tee' value='$tee'>
								<input type='hidden' name='otunnus' value='$otunnus'>
								<input type='hidden' name='tilausnumero' value='$tilausnumero'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='projektilla' value='$projektilla'>
								<input type='hidden' name='laskutunnus' value='$llrow[tunnus]'>
								<input type='submit' value='".t("Valitse")."'>";

						echo "</td></tr>";
						echo "</form>";
					}
					else {
						echo "<td class='spec' valign='top'>";
						echo t("Tätä vaihto-omaisuuslaskua ei voida lisätä tälle saapumiselle")."!";
						echo "</td></tr>";
					}
				}
				elseif ($listaustapa == "liitetyt" or $listaustapa == "") {
					echo "<td valign='top' colspan='2'>";
					echo t("Laskun summa on jo käytetty saapumisella/keikoilla")." $apurow[laskunrot]!";
					echo "</td></tr>";
				}
				else {
					echo "<td valign='top' colspan='2'>&nbsp;</td></tr>";
				}
			}

			echo "</table>";
		}
	}

	if ($toiminto != "" and $lopetus == '') {

		echo "<hr>";
		echo "<form method='post'>";
		echo "<input type='hidden' name='toiminto' value=''>";
		echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
		echo "<input type='hidden' name='ytunnus' value='$laskurow[ytunnus]'>";

		if ($nappikeikalle == 'menossa') {
			echo "<input type='hidden' name='otunnus' value='$otunnus'>";
			echo "<input type='hidden' name='nappikeikalla' value='ollaan'>";
		}

		echo "<input type='submit' value='".t("Takaisin saapumiselle")."'>";
		echo "</form>";
	}
}

?>