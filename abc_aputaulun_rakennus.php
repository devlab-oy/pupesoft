<?php

require ("inc/parametrit.inc");


echo "<font class='head'>".t("ABC-Aputaulun rakennus")."<hr></font>";

// piirrell‰‰n formi
echo "<form action='$PHP_SELF' method='post' autocomplete='OFF'>";
echo "<input type='hidden' name='tee' value='YHTEENVETO'>";
echo "<table>";




if (!isset($kka))
	$kka = date("m",mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
if (!isset($vva))
	$vva = date("Y",mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
if (!isset($ppa))
	$ppa = date("d",mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));

if (!isset($kkl))
	$kkl = date("m");
if (!isset($vvl))
	$vvl = date("Y");
if (!isset($ppl))
	$ppl = date("d");

echo "<th colspan='4'>".t("Valitse kausi").":</th>";

echo "<tr><th>".t("Alkup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>
		<td><input type='text' name='ppa' value='$ppa' size='3'></td>
		<td><input type='text' name='kka' value='$kka' size='3'></td>
		<td><input type='text' name='vva' value='$vva' size='5'></td>
		</tr><tr><th>".t("Loppup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>
		<td><input type='text' name='ppl' value='$ppl' size='3'></td>
		<td><input type='text' name='kkl' value='$kkl' size='3'></td>
		<td><input type='text' name='vvl' value='$vvl' size='5'></td></tr>";

echo "<tr><td colspan='4' class='back'><br></td></tr>";

echo "<th colspan='4'>".t("Valitse osastot").":</th>";


$query = "	SELECT distinct selite, selitetark
			FROM avainsana
			WHERE yhtio='$kukarow[yhtio]' and laji='OSASTO'";
$sresult = mysql_query($query) or pupe_error($query);

$i = 0;

while ($srow = mysql_fetch_array($sresult)) {
	echo "<tr>";

	if ($osasto[$i] == $srow[0]) $sel = "CHECKED";
	else $sel = "";

	echo "<td><input type='checkbox' name='osasto[$i]' value='$srow[0]' $sel></td><td  colspan='3'>$srow[0] $srow[1]</td>";

	echo "</tr>";

	$i++;
}

echo "<tr><td colspan='4' class='back'><br></td></tr>";
echo "<th colspan='4'>".t("Kustannukset per vuosi").":</th>";

echo "<tr><th colspan='3'>".t("Myynnin kustannukset").":</th>
		<td><input type='text' name='myynninkustayht' value='$myynninkustavuosi' size='10'></td></tr>";

echo "<tr><th colspan='3'>".t("Oston kustannukset").":</th>
		<td><input type='text' name='ostojenkustayht' value='$ostojenkustavuosi' size='10'></td></tr>";

echo "</table>";
echo "<br><input type='submit' value='".t("Rakenna")."'>";
echo "</form><br><br><br>";



// rakennetaan tiedot
if ($tee == 'YHTEENVETO') {

	echo "".t("ABC-aputaulua rakennetaan")."...<br>";

	if (!is_array($osasto)) {
		echo "<br><br><font class='error'>".t("VIRHE: Valitse osastot")."!</font>";
		exit;
	}


	$osastot = '';
	foreach ($osasto as $osa) {
		$osastot .= "'".$osa."',";
	}
	$osastot = substr($osastot, 0,-1);


	//siivotaan ensin aputaulu tyhj‰ksi
	$query = "	DELETE from abc_aputaulu
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi='T'";
	$res = mysql_query($query) or pupe_error($query);



	///* sitten l‰het‰‰n rakentamaan uutta aputaulua *///

	//haetaan ensin koko kauden yhteisnmyynti ja ostot
	$query = "	SELECT
				sum(if(tyyppi='O', 1, 0))			yhtriviaosto,
				sum(if(tyyppi='L', 1, 0))			yhtrivia,
				sum(if(tyyppi='L', rivihinta, 0))	yhtmyynti,
				sum(if(tyyppi='L', kate, 0)) 		yhtkate
				FROM tilausrivi use index (yhtio_tyyppi_laskutettuaika)
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				and tilausrivi.tyyppi in ('L','O')
				and tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'
				and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl'
				and tilausrivi.osasto in ($osastot)";
	$res = mysql_query($query) or pupe_error($query);
	$row = mysql_fetch_array($res);

	//kokokauden kokonaismyynti
	$kaudenmyynyht 		= $row["yhtmyynti"];
	$kaudenkateyht 		= $row["yhtkate"];
	$kaudenmyyriviyht 	= $row["yhtrivia"];
	$kaudenostriviyht 	= $row["yhtriviaosto"];


	// t‰ss‰ on kayttajan syottamat kustannukset per vuosi, jyvitet‰‰n ne per p‰iv‰ ja sit katotaan p‰iv‰m‰‰r‰v‰liin kuuluvien p‰ivien lukum‰‰r‰‰

	$query  = "SELECT TO_DAYS('$vvl-$kkl-$ppl')-TO_DAYS('$vva-$kka-$ppa') ero";
	$result = mysql_query($query) or pupe_error($query);
	$row    = mysql_fetch_array($result);
	$paivat = abs($row["ero"]);


	$myynninkustapaiva = $myynninkustavuosi / 365;
	$ostojenkustapaiva = $ostojenkustavuosi / 365;


	$myynninkustayht = $myynninkustapaiva * $ero;
	$ostojenkustayht = $ostojenkustapaiva * $ero;

	$kustapermyyrivi = $myynninkustayht / $kaudenmyyriviyht;
	$kustaperostrivi = $ostojenkustayht / $kaudenostriviyht;


	// rakennetaan perus ABC-luokat myynnin peusteella
	$query = "	SELECT
				tuoteno,
				try,
				osasto,
				sum(if(tyyppi='L' and (var='H' or var=''), 1, 0))			rivia,
				sum(if(tyyppi='L' and (var='H' or var=''), kpl, 0))			kpl,
				sum(if(tyyppi='L' and (var='H' or var=''), rivihinta, 0))	summa,
				sum(if(tyyppi='L' and (var='H' or var=''), kate, 0))		kate,
				sum(if(tyyppi='L' and (var='P'), tilkpl, 0))				puutekpl,
				sum(if(tyyppi='L' and (var='P'), 1, 0))						puuterivia,
				sum(if(tyyppi='O', 1, 0))									osto_rivia,
				sum(if(tyyppi='O', kpl, 0))									osto_kpl,
				sum(if(tyyppi='O', rivihinta, 0))							osto_summa
				FROM tilausrivi use index (yhtio_tyyppi_laskutettuaika)
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				and tilausrivi.tyyppi in ('L','O')
				and tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'
				and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl'
				and tilausrivi.osasto in ($osastot)
				GROUP BY tuoteno, try, osasto
				HAVING summa > 0
	   			ORDER BY summa desc";
	$res = mysql_query($query) or pupe_error($query);

	$i					= 0;
	$ryhmaprossa		= 0;

	$ryhmanimet   = array('A-30','B-20','C-15','D-15','E-10','F-05','G-03','H-02');
	$ryhmaprossat = array(30.00,20.00,15.00,15.00,10.00,5.00,3.00,2.00);

	while($row = mysql_fetch_array($res)) {
		//varastonarvot
		$query  = "	select round(sum(saldo)*if(epakurantti1pvm='0000-00-00', kehahin, kehahin/2),2) vararvo
					from tuotepaikat, tuote
					where tuotepaikat.yhtio		= '$kukarow[yhtio]'
					and tuotepaikat.tuoteno		= '$row[tuoteno]'
					and tuote.yhtio				= tuotepaikat.yhtio
					and tuote.tuoteno			= tuotepaikat.tuoteno
					and tuote.ei_saldoa			= ''
					and tuote.epakurantti2pvm	= '0000-00-00'";
		$tuores = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($tuores) > 0) {
			//tuotteen ja tuotepaikan kaikki tiedot
			$tuorow = mysql_fetch_array($tuores);


			//tuotteen suhteellinen myynti totaalimyynnist‰
			$tuoteprossa = ($row["summa"] / $kaudenmyynyht) * 100;

			//muodostetaan ABC-luokka rym‰prossan mukaan
			$ryhmaprossa += $tuoteprossa;


			if ($row["summa"] != 0)			$kateprosentti = round($row["kate"] / $row["summa"] * 100,2);
			else $kateprosentti = 0;

			if ($tuorow["vararvo"] != 0)	$kiertonopeus  = round(($row["summa"] - $row["kate"]) / $tuorow["vararvo"],2);
			else $kiertonopeus = 0;

			if ($row["rivia"] != 0)			$myyntieranarvo = round($row["summa"] / $row["rivia"],2);
			else $myyntieranarvo = 0;

			if ($row["rivia"] != 0)			$myyntieranakpl = round($row["kpl"] / $row["rivia"],2);
			else $myyntieranakpl = 0;

			if ($row["puuterivia"] + $row["rivia"] != 0)	$palvelutaso = round(100 - ($row["puuterivia"] / ($row["puuterivia"] + $row["rivia"]) * 100),2);
			else $palvelutaso = 0;

			if ($row["osto_rivia"] != 0)	$ostoeranarvo 	= round ($row["osto_summa"] / $row["osto_rivia"],2);
			else $ostoeranarvo = 0;

			if ($row["osto_rivia"] != 0)	$ostoeranakpl 	= round ($row["osto_kpl"] / $row["osto_rivia"],2);
			else $ostoeranakpl = 0;


			//rivin kustannus
			$kustamyy = round($kustapermyyrivi * $row["rivia"],2);
			$kustaost = round($kustaperostrivi * $row["osto_rivia"],2);
			$kustayht = $kustamyy + $kustaost;

			$query = "	INSERT INTO abc_aputaulu
						SET yhtio			= '$kukarow[yhtio]',
						tyyppi				= 'T',
						luokka				= '$i',
						tuoteno				= '$row[tuoteno]',
						osasto				= '$row[osasto]',
						try					= '$row[try]',
						summa				= '$row[summa]',
						kate				= '$row[kate]',
						katepros			= '$kateprosentti',
						vararvo				= '$tuorow[vararvo]',
						varaston_kiertonop 	= '$kiertonopeus',
						myyntierankpl 		= '$myyntieranakpl',
						myyntieranarvo 		= '$myyntieranarvo',
						rivia				= '$row[rivia]',
						kpl					= '$row[kpl]',
						puutekpl			= '$row[puutekpl]',
						puuterivia			= '$row[puuterivia]',
						palvelutaso 		= '$palvelutaso',
						osto_rivia			= '$row[osto_rivia]',
						osto_kpl			= '$row[osto_kpl]',
						ostoerankpl 		= '$ostoeranakpl',
						ostoeranarvo 		= '$ostoeranarvo',
						osto_summa			= '$row[osto_summa]',
						kustannus			= '$kustamyy',
						kustannus_osto		= '$kustaost',
						kustannus_yht		= '$kustayht'";
			$insres = mysql_query($query) or pupe_error($query);

			//luokka vaihtuu
			if ($ryhmaprossa >= $ryhmaprossat[$i]) {
				$ryhmaprossa = 0;
				$i++;

				if ($i == 8) {
					$i = 7;
				}
			}
		}
	}


	/// luodaan I-ryhm‰, eli tuotteet joilla ei ole ollut myynti‰ kaudella, mutta joilla on varastonarvoa ///

	// etsit‰‰n kaikki tuotteet joilla on varastonarvoa
	$query  = "	SELECT tuote.tuoteno, tuote.osasto, tuote.try, ifnull(round(sum(saldo)*if(epakurantti1pvm='0000-00-00', kehahin, kehahin/2),2),0) vararvo
				FROM tuotepaikat, tuote
				WHERE tuote.yhtio			= '$kukarow[yhtio]'
				and tuote.ei_saldoa			= ''
				and tuote.epakurantti2pvm	= '0000-00-00'
				and tuote.osasto 			in ($osastot)
				and tuotepaikat.tuoteno		= tuote.tuoteno
				and tuotepaikat.yhtio		= tuote.yhtio
				group by 1,2,3
				HAVING vararvo > 0";
	$result = mysql_query($query) or pupe_error($query);

	while ($tuoterow = mysql_fetch_array($result)) {

		// tutkitaan onko tuotetta myyty
		$query = "	SELECT
					sum(if(tyyppi='L' and (var='H' or var=''), 1, 0))			rivia,
					sum(if(tyyppi='L' and (var='H' or var=''), kpl, 0))			kpl,
					sum(if(tyyppi='L' and (var='H' or var=''), rivihinta, 0))	summa,
					sum(if(tyyppi='L' and (var='H' or var=''), kate, 0))		kate,
					sum(if(tyyppi='L' and (var='P'), tilkpl, 0))				puutekpl,
					sum(if(tyyppi='L' and (var='P'), 1, 0))						puuterivia,
					sum(if(tyyppi='O', 1, 0))									osto_rivia,
					sum(if(tyyppi='O', kpl, 0))									osto_kpl,
					sum(if(tyyppi='O', rivihinta, 0))							osto_summa
					FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi in ('L','O')
					and tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'
					and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl'
					and tuoteno	= '$tuoterow[tuoteno]'
					and try		= '$tuoterow[try]'
					and osasto	= '$tuoterow[osasto]'
					HAVING summa is null or summa <= 0";
		$res = mysql_query($query) or pupe_error($query);

		while($row = mysql_fetch_array($res)) {

			if ($row["summa"] != 0)			$kateprosentti = round($row["kate"] / $row["summa"] * 100,2);
			else $kateprosentti = 0;

			if ($tuoterow["vararvo"] != 0)	$kiertonopeus  = round(($row["summa"] - $row["kate"]) / $tuoterow["vararvo"],2);
			else $kiertonopeus = 0;

			if ($row["rivia"] != 0)			$myyntieranarvo = round($row["summa"] / $row["rivia"],2);
			else $myyntieranarvo = 0;

			if ($row["rivia"] != 0)			$myyntieranakpl = round($row["kpl"] / $row["rivia"],2);
			else $myyntieranakpl = 0;

			if ($row["puuterivia"] + $row["rivia"] != 0)	$palvelutaso = round(100 - ($row["puuterivia"] / ($row["puuterivia"] + $row["rivia"]) * 100),2);
			else $palvelutaso = 0;

			if ($row["osto_rivia"] != 0)	$ostoeranarvo 	= round ($row["osto_summa"] / $row["osto_rivia"],2);
			else $ostoeranarvo = 0;

			if ($row["osto_rivia"] != 0)	$ostoeranakpl 	= round ($row["osto_kpl"] / $row["osto_rivia"],2);
			else $ostoeranakpl = 0;


			//rivin kustannus
			$kustamyy = round($kustapermyyrivi * $row["rivia"],2);
			$kustaost = round($kustaperostrivi * $row["osto_rivia"],2);
			$kustayht = $kustamyy + $kustaost;

			$query = "	INSERT INTO abc_aputaulu
						SET yhtio			= '$kukarow[yhtio]',
						tyyppi				= 'T',
						luokka				= '8',
						tuoteno				= '$tuoterow[tuoteno]',
						osasto				= '$tuoterow[osasto]',
						try					= '$tuoterow[try]',
						summa				= '$row[summa]',
						kate				= '$row[kate]',
						katepros			= '$kateprosentti',
						vararvo				= '$tuoterow[vararvo]',
						varaston_kiertonop 	= '$kiertonopeus',
						myyntierankpl 		= '$myyntieranakpl',
						myyntieranarvo 		= '$myyntieranarvo',
						rivia				= '$row[rivia]',
						kpl					= '$row[kpl]',
						puutekpl			= '$row[puutekpl]',
						puuterivia			= '$row[puuterivia]',
						palvelutaso 		= '$palvelutaso',
						osto_rivia			= '$row[osto_rivia]',
						osto_kpl			= '$row[osto_kpl]',
						ostoerankpl 		= '$ostoeranakpl',
						ostoeranarvo 		= '$ostoeranarvo',
						osto_summa			= '$row[osto_summa]',
						kustannus			= '$kustamyy',
						kustannus_osto		= '$kustaost',
						kustannus_yht		= '$kustayht'";
			$insres = mysql_query($query) or pupe_error($query);
		}
	}



	//rakennetaan osastokohtainen luokka
	for ($a = 0; $a <= 99; $a++) {
		//rakennetaan aliluokat
		$query = "	SELECT
					summa,
					kate,
					tunnus
					FROM abc_aputaulu
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi = 'T'
					and osasto = '$a'
					ORDER BY summa desc";
		$res = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($res) > 0) {

			//haetaan luokan myynti yhteens‰
			$query = "	SELECT
						sum(summa) yhtmyynti,
						sum(kate) yhtkate
						FROM abc_aputaulu
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi = 'T'
						and osasto = '$a'";
			$resi 	= mysql_query($query) or pupe_error($query);
			$yhtrow = mysql_fetch_array($resi);

			$i			 = 0;
			$ryhmaprossa = 0;

			$ryhmanimet   = array('A-30','B-20','C-15','D-15','E-10','F-05','G-03','H-02','I-00');
			$ryhmaprossat = array(30.00,20.00,15.00,15.00,10.00,5.00,3.00,2.00,0.00);

			while($row = mysql_fetch_array($res)) {

				//tuotteen suhteellinen myynti totaalimyynnist‰
				$tuoteprossa = ($row["summa"] / $yhtrow["yhtmyynti"]) * 100;

				//muodostetaan ABC-luokka rym‰prossan mukaan
				$ryhmaprossa += $tuoteprossa;

				$query = "	UPDATE abc_aputaulu
							SET luokka_osasto = '$i'
							WHERE yhtio = '$kukarow[yhtio]'
							and tyyppi = 'T'
							and tunnus  = '$row[tunnus]'";
				$insres = mysql_query($query) or pupe_error($query);

				//luokka vaihtuu
				if (round($ryhmaprossa,2) >= $ryhmaprossat[$i]) {
					$ryhmaprossa = 0;
					$i++;

					if ($i == 8) {
						$i = 7;
					}
				}
			}
		}
	}





	//rakennetaan tuoteryhm‰kohtainen luokka
	for ($a = 0; $a <= 99; $a++) {
		for ($b = 0; $b <= 999; $b++) {

			$query = "	SELECT tunnus
						FROM abc_aputaulu
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi = 'T'
						and osasto	= '$a'
						and try		= '$b'";
			$res = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($res) > 0) {
				//rakennetaan aliluokat
				$query = "	SELECT
							summa,
							kate,
							tunnus
							FROM abc_aputaulu
							WHERE yhtio = '$kukarow[yhtio]'
							and tyyppi = 'T'
							and osasto	= '$a'
							and try		= '$b'
							ORDER BY summa desc";
				$res = mysql_query($query) or pupe_error($query);


				//haetaan luokan myynti yhteens‰
				$query = "	SELECT
							sum(summa) yhtmyynti,
							sum(kate) yhtkate
							FROM abc_aputaulu
							WHERE yhtio = '$kukarow[yhtio]'
							and tyyppi = 'T'
							and osasto	= '$a'
							and try		= '$b'";
				$resi 	= mysql_query($query) or pupe_error($query);
				$yhtrow = mysql_fetch_array($resi);

				$i			 = 0;
				$ryhmaprossa = 0;

				$ryhmanimet   = array('A-30','B-20','C-15','D-15','E-10','F-05','G-03','H-02','I-00');
				$ryhmaprossat = array(30.00,20.00,15.00,15.00,10.00,5.00,3.00,2.00,0.00);

				while($row = mysql_fetch_array($res)) {

					//tuotteen suhteellinen myynti totaalimyynnist‰
					if ($yhtrow["yhtmyynti"] != 0) $tuoteprossa = ($row["summa"] / $yhtrow["yhtmyynti"]) * 100;
					else $tuoteprossa = 0;

					//muodostetaan ABC-luokka rym‰prossan mukaan
					$ryhmaprossa += $tuoteprossa;

					$query = "	UPDATE abc_aputaulu
								SET luokka_try = '$i'
								WHERE yhtio = '$kukarow[yhtio]'
								and tyyppi = 'T'
								and tunnus  = '$row[tunnus]'";
					$insres = mysql_query($query) or pupe_error($query);

					//luokka vaihtuu
					if (round($ryhmaprossa,2) >= $ryhmaprossat[$i]) {
						$ryhmaprossa = 0;
						$i++;

						if ($i == 8) {
							$i = 7;
						}
					}
				}
			}
		}
	}


	//rakennetaan tuoteryhm‰grouppikohtainen abc-ryhm‰
	$query = "	SELECT
				osasto,
				try,
				sum(summa) summa,
				sum(kate) kate
				FROM abc_aputaulu
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi = 'T'
				GROUP BY osasto, try
				ORDER BY summa desc";
	$res = mysql_query($query) or pupe_error($query);

	//haetaan myynti yhteens‰
	$query = "	SELECT
				sum(summa) yhtmyynti,
				sum(kate) yhtkate
				FROM abc_aputaulu
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi = 'T'";
	$resi 	= mysql_query($query) or pupe_error($query);
	$yhtrow = mysql_fetch_array($resi);

	$i			 = 0;
	$ryhmaprossa = 0;

	$ryhmanimet   = array('A-30','B-20','C-15','D-15','E-10','F-05','G-03','H-02','I-00');
	$ryhmaprossat = array(30.00,20.00,15.00,15.00,10.00,5.00,3.00,2.00,0.00);

	while($row = mysql_fetch_array($res)) {

		//tuotteen suhteellinen myynti totaalimyynnist‰
		$tuoteprossa = ($row["summa"] / $yhtrow["yhtmyynti"]) * 100;

		//muodostetaan ABC-luokka rym‰prossan mukaan
		$ryhmaprossa += $tuoteprossa;

		$query = "	UPDATE abc_aputaulu
					SET luokka_trygroup = '$i'
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi = 'T'
					and osasto  = '$row[osasto]'
					and try 	= '$row[try]'";
		$insres = mysql_query($query) or pupe_error($query);

		//luokka vaihtuu
		if ($ryhmaprossa >= $ryhmaprossat[$i]) {
			$ryhmaprossa = 0;
			$i++;

			if ($i == 8) {
				$i = 7;
			}
		}
	}
}

require ("inc/footer.inc");

?>