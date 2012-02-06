<?php

	require ("inc/parametrit.inc");

	if (isset($tee) and $tee == 'I') {

		$query = "	SELECT tunnus, tilino
					FROM tili
					WHERE yhtio = '{$kukarow['yhtio']}' and tunnus = '{$yhtiorow["tilikauden_tulos"]}'";
		$tilires = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($tilires) != 1) {
			echo  "<br><font class='error'>\n".t("VIRHE: Tili� ei l�ydy").": $yhtiorow[tilikauden_tulos]</font><br>\n<br>\n";
			$tee = "go";
		}
		else {
			$tilirow = mysql_fetch_assoc($tilires);
		}

		$query = "	SELECT tunnus
					FROM tili
					WHERE yhtio = '{$kukarow['yhtio']}' and tilino = '{$edellisten_tilikausien_voitto_tappio}'";
		$tilires = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($tilires) != 1) {
			echo  "<br><font class='error'>\n".t("VIRHE: Tili� ei l�ydy").": $edellisten_tilikausien_voitto_tappio</font><br>\n<br>\n";
			$tee = "go";
		}

		$query = "	SELECT tunnus
					FROM tili
					WHERE yhtio = '{$kukarow['yhtio']}' and tilino = '{$tilikauden_tulos_siirto}'";
		$tilires = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($tilires) != 1) {
			echo  "<br><font class='error'>\n".t("VIRHE: Tili� ei l�ydy").": $tilikauden_tulos_siirto</font><br>\n<br>\n";
			$tee = "go";
		}

		$query = "	SELECT tilikausi_alku, tilikausi_loppu, avaava_tase
					FROM tilikaudet
					WHERE yhtio = '{$kukarow['yhtio']}' and tunnus = '{$tilikausi}'";
		$tilikausi_alku_loppu_res = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($tilikausi_alku_loppu_res) == 1) {
			$tilikausi_alku_loppu_row = mysql_fetch_assoc($tilikausi_alku_loppu_res);

			if ($tilikausi_alku_loppu_row["avaava_tase"] != 0) {
				echo  "<br><font class='error'>\n".t("VIRHE: Avaava tase on jo sy�tetty!")."</font><br>\n<br>\n";
				$tee = "go";
			}

			// Vertaillaan tilikauteen
			list($vv1,$kk1,$pp1) = explode("-",$yhtiorow["tilikausi_alku"]);
			list($vv2,$kk2,$pp2) = explode("-",$yhtiorow["tilikausi_loppu"]);
			list($vv3,$kk3,$pp3) = explode("-",$tilikausi_alku_loppu_row["tilikausi_loppu"]);

			$tilialku  = (int) date('Ymd', mktime(0,0,0,$kk1,$pp1,$vv1));
			$tililoppu = (int) date('Ymd', mktime(0,0,0,$kk2,$pp2,$vv2));
			$syotetty  = (int) date('Ymd', mktime(0,0,0,$kk3,$pp3,$vv3));

			list($vv4,$kk4,$pp4) = explode("-", date('Y-m-d', mktime(0,0,0,$kk3,$pp3+1,$vv3)));

			if ($syotetty < $tilialku or $syotetty > $tililoppu) {
				echo  "<br><font class='error'>\n".t("VIRHE: Valitun tilikauden viimeinen p�iv� ei sis�lly avoimeen tilikauteen!")."</font><br>\n<br>\n";
				$tee = "go";
			}
		}
	}

	if (isset($tee) and $tee == 'I') {
		$isumma 		= unserialize(urldecode($isumma));
		$itili 			= unserialize(urldecode($itili));
		$iselite 		= unserialize(urldecode($iselite));
		$ivero 			= unserialize(urldecode($ivero));

		$userfile		= "";
		$kuitti			= "";
		$kuva			= "";
		$tpp			= $pp4;
		$tpk			= $kk4;
		$tpv			= $vv4;
		$nimi			= "";
		$comments		= t("Avaavat saldot");
		$MAX_FILE_SIZE	= "";
		$ikustp			= array();
		$ikohde			= array();
		$iprojekti		= array();
		$toimittajaid	= 0;
		$asiakasid		= 0;

		$gokfrom 	= 'avaavatase';
		$avaavatase = 'joo';

		// Onko tulos jo kirjattu
		$query = "	SELECT tunnus
					FROM lasku
					WHERE yhtio 	= '{$kukarow['yhtio']}'
					and tapvm 		= '{$vv3}-{$kk3}-{$pp3}'
					and tila 		= 'X'
					and nimi		= '{$yhtiorow['nimi']}'
					and alv_tili 	= ''
					and comments	= '{$iselite[$maara]}'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			// Kirjataan tilikauden tulos
			$voittosumma 		= $isumma[$maara];
			$summa				= $voittosumma;
			$summa_valuutassa	= 0;
			$tili 				= $itili[$maara];
			$kustp 				= "";
			$selite 			= $iselite[$maara];
			$vero 				= $ivero[$maara];
			$projekti 			= "";
			$kohde 				= "";
			$valkoodi 			= $yhtiorow['valkoodi'];

			$query = "	INSERT into lasku set
						yhtio 		= '{$kukarow['yhtio']}',
						tapvm 		= '{$vv3}-{$kk3}-{$pp3}',
						tila 		= 'X',
						nimi		= '{$yhtiorow['nimi']}',
						alv_tili 	= '',
						comments	= '{$selite}',
						laatija 	= '{$kukarow['kuka']}',
						luontiaika 	= now()";
			$result = mysql_query($query) or pupe_error($query);
			$tunnus = mysql_insert_id($link);

			require("inc/teetiliointi.inc");

			$summa				= $voittosumma*-1;
			$summa_valuutassa	= 0;
			$tili 				= $tilikauden_tulos_siirto;
			$kustp 				= "";
			$selite 			= $iselite[count($iselite)];
			$vero 				= 0;
			$projekti 			= "";
			$kohde 				= "";
			$valkoodi 			= $yhtiorow['valkoodi'];

			require("inc/teetiliointi.inc");

			echo t("Tilikauden tulos kirjattu")."!<br><br>";
		}

		// Kirjataan avaava tase
		unset($tunnus);
		$summa				= '';
		$summa_valuutassa	= '';
		$tili				= '';
		$kustp				= '';
		$selite 			= '';
		$vero				= '';
		$projekti			= '';
		$kohde 				= '';
		$valkoodi 			= '';

		// Korjataan tulossiirron kommentti
		$iselite[$maara]	= $iselite[1]." / ".t("Siirret��n")." ".t("Tilikauden tulos");

		// Siirret��n tulos
		$itili[$maara+1]	= $itili[$maara];
		$iselite[$maara+1]	= $iselite[$maara];
		$isumma[$maara+1] 	= $isumma[$maara]*-1;
		$ivero[$maara+1] 	= $ivero[$maara];

		// Ja lis�t��n summa my�s edellisten tilikausien voitto tilille
		$itili[$maara+2]	= $edellisten_tilikausien_voitto_tappio;
		$iselite[$maara+2] 	= $iselite[$maara];
		$isumma[$maara+2] 	= $isumma[$maara];
		$ivero[$maara+2] 	= $ivero[$maara];

		$maara += 3;

		require("tosite.php");
		exit;
	}

	enable_ajax();

	if (isset($livesearch_tee) and $livesearch_tee == "TILIHAKU") {
		livesearch_tilihaku();
		exit;
	}

	if (isset($tee) and $tee == 'gotili') {
		$tee = 'go';
	}

	echo "<font class='head'>",t("Avaava tase"),"</font><hr>";

	if (isset($tilikausi) and trim($tilikausi) == '') {
		echo "<font class='error'>",t("Valitse tilikausi"),"!</font><br />";
		$tee = '';
	}

	if (!isset($tilikausi)) $tilikausi = '';
	if (!isset($tee)) $tee = '';

	echo "<form method='post'>";
	echo "<input type='hidden' name='tee' value='go'>";
	echo "<table>";

	echo "<tr><th>",t("Valitse suljettava tilikausi"),"</th>";

	$query = "	SELECT tilikausi_alku, tilikausi_loppu, tunnus
				FROM tilikaudet
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND avaava_tase = 0
				ORDER BY tilikausi_alku DESC";
	$tilikausi_result = mysql_query($query) or pupe_error($query);

	echo "<td><select name='tilikausi'><option value=''>",t("Valitse"),"</option>";

	while ($tilikausi_row = mysql_fetch_assoc($tilikausi_result)) {
		$sel = $tilikausi_row['tunnus'] == $tilikausi ? ' selected' : '';
		echo "<option value='{$tilikausi_row['tunnus']}'{$sel}>",tv1dateconv($tilikausi_row['tilikausi_alku'])," - ",tv1dateconv($tilikausi_row['tilikausi_loppu']),"</option>";
	}

	echo "</select>";
	echo "</td><td class='back'><input type='submit' name='submit' value='",t("Hae"),"' /></td></tr>";
	echo "</table>";
	echo "</form><br />";

	if (trim($tee) == 'go') {

		$query = "	SELECT tilino
					FROM tili
					WHERE yhtio = '{$kukarow['yhtio']}' and tunnus = '{$yhtiorow["tilikauden_tulos"]}'";
		$tilires = mysql_query($query) or pupe_error($query);
		$tilirow = mysql_fetch_assoc($tilires);

		if ((!isset($edellisten_tilikausien_voitto_tappio) or !isset($tilikauden_tulos_siirto)) or trim($edellisten_tilikausien_voitto_tappio) == '' or trim($tilikauden_tulos_siirto) == '') {
			echo "<form method='post' name='tilisyotto'>";
			echo "<table>";
			echo "<tr><th colspan='2'>",t("Sy�t� seuraavat pakolliset tilit"),"</th><td class='back'>&nbsp;</td></tr>";
			echo "<tr><th>",t("Tilikauden voitto/tappio"),"</th><td>$tilirow[tilino]</td><td class='back'>&nbsp;</td></tr>";
			echo "<tr><th>",t("Edellisten tilikausien voitto/tappio"),"</th><td>";

			if (isset($edellisten_tilikausien_voitto_tappio) and trim($edellisten_tilikausien_voitto_tappio) != '') {
				echo livesearch_kentta("tilisyotto", "TILIHAKU", "edellisten_tilikausien_voitto_tappio", 200, $edellisten_tilikausien_voitto_tappio);
			}
			else {
				echo livesearch_kentta("tilisyotto", "TILIHAKU", "edellisten_tilikausien_voitto_tappio", 200);
			}

			echo "</td><td class='back'>&nbsp;</td></tr>";
			echo "<tr><th>",t("Tili jolla tuloslaskelma nollataan"),"</th><td>";

			if (isset($tilikauden_tulos_siirto) and trim($tilikauden_tulos_siirto) != '') {
				echo livesearch_kentta("tilisyotto", "TILIHAKU", "tilikauden_tulos_siirto", 200, $tilikauden_tulos_siirto);
			}
			else {
				echo livesearch_kentta("tilisyotto", "TILIHAKU", "tilikauden_tulos_siirto", 200);
			}

			echo "</td><td class='back'><input type='submit' value='",t("Jatka"),"' /></td></tr>";
			echo "</table>";
			echo "<input type='hidden' name='tee' value='gotili' />";
			echo "<input type='hidden' name='tilikausi' value='{$tilikausi}' />";
			echo "</form>";
			$tee = '';
		}
		else {
			echo "<table>";
			echo "<tr><th>",t("Tilikauden voitto/tappio"),"</th><td>{$tilirow["tilino"]}</td></tr>";
			echo "<tr><th>",t("Edellisten tilikausien voitto/tappio"),"</th><td>{$edellisten_tilikausien_voitto_tappio}</td></tr>";
			echo "<tr><th>",t("Tili jolla tuloslaskelma nollataan"),"</th><td>{$tilikauden_tulos_siirto}</td></tr>";
			echo "</table>";
		}

		echo "<br />";
	}

	if (trim($tee) == 'go') {

		$query = "	SELECT tilikausi_alku, tilikausi_loppu, avaava_tase
					FROM tilikaudet
					WHERE yhtio = '{$kukarow['yhtio']}' and tunnus = '{$tilikausi}'";
		$tilikausi_alku_loppu_res = mysql_query($query) or pupe_error($query);
		$tilikausi_alku_loppu_row = mysql_fetch_assoc($tilikausi_alku_loppu_res);

		// Haetaan firman tulos
		$query = "	SELECT sum(tiliointi.summa) summa
	 	            FROM tiliointi USE INDEX (yhtio_tilino_tapvm)
					JOIN tili ON (tiliointi.yhtio=tili.yhtio and tiliointi.tilino=tili.tilino and LEFT(tili.ulkoinen_taso, 1) = BINARY '3')
		            WHERE tiliointi.yhtio = '{$kukarow['yhtio']}'
		            and tiliointi.korjattu = ''
		            and tiliointi.tapvm >= '{$tilikausi_alku_loppu_row['tilikausi_alku']}'
		            and tiliointi.tapvm <= '{$tilikausi_alku_loppu_row['tilikausi_loppu']}'";
		$tulosres = mysql_query($query) or pupe_error($query);
		$tulosrow = mysql_fetch_assoc($tulosres);

		$query = "	SELECT tili.tilino, tili.nimi, tiliointi.kustp, tiliointi.kohde, tiliointi.projekti, count(*) vientej�, sum(summa) saldo
					FROM tiliointi
					JOIN tili ON (tili.yhtio = '{$kukarow['yhtio']}' and tiliointi.tilino = tili.tilino and LEFT(ulkoinen_taso, 1) < BINARY '3')
					WHERE tiliointi.yhtio = '{$kukarow['yhtio']}'
					and tiliointi.korjattu = ''
					and tapvm >= '{$tilikausi_alku_loppu_row['tilikausi_alku']}'
					and tapvm <= '{$tilikausi_alku_loppu_row['tilikausi_loppu']}'
					GROUP BY 1,2
					ORDER BY 1,2";
		$result = mysql_query($query) or pupe_error($query);

		list($tpv, $tpk, $tpp) = explode("-", $tilikausi_alku_loppu_row['tilikausi_loppu']);

		$tpv = date("Y-m-d", mktime(0, 0, 0, $tpk, $tpp+1, $tpv));

		echo "<form method='post'>";
		echo "<input type='hidden' name='tee' value='I' />";
		echo "<input type='hidden' name='tilikausi' value='{$tilikausi}' />";
		echo "<input type='hidden' name='edellisten_tilikausien_voitto_tappio' value='{$edellisten_tilikausien_voitto_tappio}' />";
		echo "<input type='hidden' name='tilikauden_tulos_siirto' value='{$tilikauden_tulos_siirto}' />";

		echo "<table><tr>";
		echo "<th>",t("Tili"),"</font></th>";
		echo "<th>",t("Nimi"),"</font></th>";
		echo "<th>",t("Vientej�"),"</font></th>";
		echo "<th>",t("Saldo"),"</font></th>";
		echo "</tr>";

		$summa		 = 0;
		$summa2 	 = 0;
		$isumma 	 = array();
		$itili		 = array();
		$iselite	 = array();
		$ivero		 = array();
		$maara		 = 1;
		$valkoodi	 = '';

		$lopelink = "&lopetus=$PHP_SELF////tee=$tee//tilikausi=$tilikausi";
		$linkkilisa = "&tkausi=$tilikausi";

		while ($trow = mysql_fetch_assoc($result)) {

			if ($trow['saldo'] == 0 or $trow['vientej�'] == 0) continue;

			$summa2 += $trow['saldo'];

			$isumma[$maara] 	= $trow['saldo'];
			$itili[$maara] 		= $trow['tilino'];
			$iselite[$maara] 	= t("Avaavat saldot")." ".tv1dateconv($tpv);
			$ivero[$maara] 		= 0;

			echo "<tr class='aktiivi'>";
			echo "<td><a name='tili2_{$trow['tilino']}' href='raportit.php?toim=paakirja&tee=K&tili={$trow['tilino']}{$linkkilisa}{$lopelink}///tili2_{$trow['tilino']}'>{$trow['tilino']}</a></td>";
			echo "<td>{$trow['nimi']}</td>";
			echo "<td>{$trow['vientej�']}</td>";
			echo "<td align='right'>{$trow['saldo']}</td>";
			echo "</tr>";

			$maara++;
		}

		echo "<tr class='aktiivi'>";
		echo "<td></td>";
		echo "<td>".t("Tilikauden tulos")."</td><td></td><td align='right'>{$tulosrow['summa']}</td>";
		echo "</tr>";

		$summa2 += $tulosrow['summa'];

		$isumma[$maara]  = $tulosrow['summa'];
		$itili[$maara] 	 = $tilirow["tilino"];
		$iselite[$maara] = t("Tilikauden tulos")." ".tv1dateconv($tilikausi_alku_loppu_row['tilikausi_loppu']);
		$ivero[$maara] 	 = 0;

		echo "<tr>";
		echo "<td class='tumma' colspan='3'>",t("Summa"),"</td>";
		echo "<td align='right' class='tumma'>";
		echo "<input type='hidden' name='isumma' value='",urlencode(serialize($isumma)),"' />";
		echo "<input type='hidden' name='itili' value='",urlencode(serialize($itili)),"' />";
		echo "<input type='hidden' name='iselite' value='",urlencode(serialize($iselite)),"' />";
		echo "<input type='hidden' name='ivero' value='",urlencode(serialize($ivero)),"' />";
		echo "<input type='hidden' name='maara' value='{$maara}' />";
		echo sprintf('%.2f', $summa2),"</td>";
		echo "</tr>";

		if ($tilikausi_alku_loppu_row['avaava_tase'] == 0) {
			if (round($summa2, 2) != 0) {
				echo "<tr><td class='back' colspan='5'><font class='message'>",t("Summat eiv�t t�sm��"),"!</font>$summa2</td></tr>";
			}
			else {
				echo "<tr><th colspan='5'><input type='submit' value='",t("Jatka"),"' /></th></tr>";
			}
		}
		else {
			echo "<tr><td class='back' colspan='5'><font class='message'>",t("Tosite on jo luotu"),"!</font></td></tr>";
		}
		echo "</table></form><br /><br />";
	}

	require ("inc/footer.inc");

?>