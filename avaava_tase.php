<?php

	require ("inc/parametrit.inc");

	if (isset($tee) and $tee == 'I') {
		
		// yhtiön tilikauden loppupäivämäärä
		list($y_vv, $y_kk, $y_pp) = $yhtiorow['tilikausi_loppu'];
		
		if (strtotime($tpv.'-'.$tpk.'-'.$tpp) > strtotime($yhtiorow['tilikausi_loppu'])) {
			echo "<font class='error'>{$tpp}.{$tpk}.{$tpv} ",t("ei kuulu kuluvaan tilikauteen"),"!</font><br /><br />";
			$tee = 'go';
		}

	}

	if (isset($tee) and $tee == 'I') {
		$isumma = unserialize(urldecode($isumma));
		$itili = unserialize(urldecode($itili));
		$ikustp = unserialize(urldecode($ikustp));
		$iselite = unserialize(urldecode($iselite));
		$ivero = unserialize(urldecode($ivero));
		$iprojekti = unserialize(urldecode($iprojekti));
		$ikohde = unserialize(urldecode($ikohde));

		$gokfrom = 'avaavatase';

		// Jos automaattinen, lasketaan tuloslaskelman loppusumma ja kirjataan se tilikauden viimeiselle päivälle (eli tulos nollataan).
		if (trim($yhtiorow['tilikauden_tulos']) != '' and $tilikauden_voiton_kasittely == 'automaattisesti') {
			$summa 				= array_pop($isumma);
			$tili 				= array_pop($itili);
			$kustp 				= array_pop($ikustp);
			$comments 			= array_pop($iselite);
			$selite 			= $comments;
			$vero 				= array_pop($ivero);
			$projekti 			= array_pop($iprojekti);
			$kohde 				= array_pop($ikohde);
			$summa_valuutassa 	= 0;
			$valkoodi 			= $yhtiorow['valuutta'];

			list($tpv2, $tpk2, $tpp2) = explode("-", $tilikausi_loppu);

			$query = "	INSERT into lasku set
						yhtio 		= '{$kukarow['yhtio']}',
						tapvm 		= '{$tpv2}-{$tpk2}-{$tpp2}',
						tila 		= 'X',
						nimi		= '{$yhtiorow['nimi']}',
						alv_tili 	= '',
						comments	= '{$comments}',
						laatija 	= '{$kukarow['kuka']}',
						luontiaika 	= now()";
			$result = mysql_query($query) or pupe_error($query);
			$tunnus = mysql_insert_id ($link);

			require("inc/teetiliointi.inc");

			$summa 				= $edellisen_tilikauden_summa;
			$tili 				= $edellisten_tilikausien_voitto_tappio;
			$kustp 				= '';
			$selite 			= t("Siirretään tilikauden tulos");
			$vero 				= 0;
			$projekti 			= '';
			$kohde 				= '';
			$summa_valuutassa 	= 0;
			$valkoodi 			= $yhtiorow['valuutta'];

			require("inc/teetiliointi.inc");

			$tili				= '';
			$kustp				= '';
			$kohde				= '';
			$projekti			= '';
			$summa				= '';
			$vero				= '';
			$selite 			= '';
			$summa_valuutassa	= '';
			$valkoodi 			= '';
		}

		require("tosite.php");
		exit;
	}

	enable_ajax();

	if ($livesearch_tee == "TILIHAKU") {
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
	if (!isset($tilikauden_voiton_kasittely)) $tilikauden_voiton_kasittely = '';
	if (!isset($tee)) $tee = '';

	echo "<form method='post'>";
	echo "<table>";
	echo "<tr><th>",t("Valitse tilikausi"),"</th>";

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

	echo "</td><td class='back'>&nbsp;</td></tr>";

	$sel = array('manuaalisesti' => '', 'automaattisesti' => '');

	// yhtiön parametreistä tilikauden_tulos
	if (trim($tilikauden_voiton_kasittely) == '' and trim($yhtiorow['tilikauden_tulos']) != '') {
		$sel['automaattisesti'] = ' selected';
	}
	elseif (trim($tilikauden_voiton_kasittely) == '') {
		$sel['manuaalisesti'] = ' selected';
	}
	else {
		$sel[$tilikauden_voiton_kasittely] = ' selected';
	}

	echo "<tr><th>",t("Tilikauden voiton käsittely"),"</th>";
	echo "<td><select name='tilikauden_voiton_kasittely'>";
	echo "<option value=''>",t("Valitse"),"</option>";
	echo "<option value='manuaalisesti'{$sel['manuaalisesti']}>",t("Manuaalisesti"),"</option>";
	echo "<option value='automaattisesti'{$sel['automaattisesti']}>",("Automaattisesti"),"</option>";
	echo "</select></td><td class='back'><input type='hidden' name='tee' value='go' /><input type='submit' name='submit' value='",t("Hae"),"' /></td>";
	echo "</tr>";

	echo "</table>";
	echo "</form><br />";

	if (trim($tee) == 'go' and $tilikauden_voiton_kasittely == 'automaattisesti') {

		if ((!isset($edellisten_tilikausien_voitto_tappio) or !isset($tilikauden_tulos)) or trim($edellisten_tilikausien_voitto_tappio) == '' or trim($tilikauden_tulos) == '') {
			echo "<form method='post' name='tilisyotto'>";
			echo "<table>";
			echo "<tr><th colspan='2'>",t("Syötä seuraavat pakolliset tilit"),"</th><td class='back'>&nbsp;</td></tr>";
			echo "<tr><th>",t("Edellisten tilikausien voitto/tappio"),"</th><td>";

			if (isset($edellisten_tilikausien_voitto_tappio) and trim($edellisten_tilikausien_voitto_tappio) != '') {
				echo livesearch_kentta("tilisyotto", "TILIHAKU", "edellisten_tilikausien_voitto_tappio", 200, $edellisten_tilikausien_voitto_tappio);
			}
			else {
				echo livesearch_kentta("tilisyotto", "TILIHAKU", "edellisten_tilikausien_voitto_tappio", 200);
			}

			echo "</td><td class='back'>&nbsp;</td></tr>";
			echo "<tr><th>",t("Tilikauden tulos"),"</th><td>";

			if (isset($tilikauden_tulos) and trim($tilikauden_tulos) != '') {
				echo livesearch_kentta("tilisyotto", "TILIHAKU", "tilikauden_tulos", 200, $tilikauden_tulos);
			}
			else {
				echo livesearch_kentta("tilisyotto", "TILIHAKU", "tilikauden_tulos", 200);
			}

			echo "</td><td class='back'><input type='submit' value='",t("Jatka"),"' /></td></tr>";
			echo "</table>";
			echo "<input type='hidden' name='tee' value='gotili' />";
			echo "<input type='hidden' name='tilikauden_voiton_kasittely' value='{$tilikauden_voiton_kasittely}' />";
			echo "<input type='hidden' name='tilikausi' value='{$tilikausi}' />";
			echo "</form>";
			$tee = '';
		}
		else {
			echo "<table>";
			echo "<tr><th>",t("Edellisten tilikausien voitto/tappio"),"</th><td>{$edellisten_tilikausien_voitto_tappio}</td></tr>";
			echo "<tr><th>",t("Tilikauden tulos"),"</th><td>{$tilikauden_tulos}</td></tr>";
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

		$query = "	SELECT tiliointi.tilino, tiliointi.kustp, tiliointi.kohde, tiliointi.projekti, count(*) vientejä, sum(summa) saldo
					FROM tiliointi
					JOIN tili ON (tili.yhtio = '{$kukarow['yhtio']}' and tiliointi.tilino = tili.tilino and LEFT(ulkoinen_taso, 1) < BINARY '3')
					WHERE tiliointi.yhtio = '{$kukarow['yhtio']}'
					and tiliointi.korjattu = ''
					and tapvm >= '{$tilikausi_alku_loppu_row['tilikausi_alku']}'
					and tapvm <= '{$tilikausi_alku_loppu_row['tilikausi_loppu']}'
					GROUP BY tiliointi.tilino, tiliointi.kustp, tiliointi.kohde, tiliointi.projekti
					ORDER BY tiliointi.tilino, tiliointi.kustp, tiliointi.kohde, tiliointi.projekti";
		$result = mysql_query($query) or pupe_error($query);

		echo "<form method='post'><table><tr>";

		echo "<th>",t("Tili"),"</font></th>";
		echo "<th>",t("Vientejä"),"</font></th>";
		echo "<th>",t("Saldo"),"</font></th>";
		echo "<th>",t("Summa valuutassa"),"</font></th>";

		echo "</tr>";

		$summa	= $summa2 = $summa3 = 0;
		$summa_valuutassa = array();

		list($tpv, $tpk, $tpp) = explode("-", $tilikausi_alku_loppu_row['tilikausi_loppu']);

		$tpv = date("Y", mktime(0, 0, 0, $tpk, $tpp+1, $tpv));
		$tpk = date("m", mktime(0, 0, 0, $tpk, $tpp+1, $tpv));
		$tpp = date("d", mktime(0, 0, 0, $tpk, $tpp+1, $tpv));

		echo "<input type='hidden' name='tee' value='I' />";
		echo "<input type='hidden' name='tpv' value='{$tpv}' />";
		echo "<input type='hidden' name='tpk' value='{$tpk}' />";
		echo "<input type='hidden' name='tpp' value='{$tpp}' />";
		echo "<input type='hidden' name='tilikauden_voiton_kasittely' value='{$tilikauden_voiton_kasittely}' />";
		echo "<input type='hidden' name='tilikausi_loppu' value='{$tilikausi_alku_loppu_row['tilikausi_loppu']}' />";
		echo "<input type='hidden' name='tilikausi' value='{$tilikausi}' />";
		echo "<input type='hidden' name='edellisten_tilikausien_voitto_tappio' value='{$edellisten_tilikausien_voitto_tappio}' />";

		$isumma = $itili = $ikustp = $iselite = $ivero = $iprojekti = array();
		$maara = 1;
		$valkoodi = '';

		$tulokset = array();

		// Haetaan firman tulos
		$query = "	SELECT tiliointi.yhtio groupsarake, sum(if(tiliointi.tapvm >= '{$tilikausi_alku_loppu_row['tilikausi_alku']}' and tiliointi.tapvm <= '{$tilikausi_alku_loppu_row['tilikausi_loppu']}', tiliointi.summa, 0)) AS summa
	 	            FROM tiliointi USE INDEX (yhtio_tilino_tapvm)
					JOIN tili ON (tiliointi.yhtio=tili.yhtio and tiliointi.tilino=tili.tilino and LEFT(tili.ulkoinen_taso, 1) = BINARY '3')
		            WHERE tiliointi.yhtio = '{$kukarow['yhtio']}'
		            and tiliointi.korjattu = ''
		            and tiliointi.tapvm >= '{$tilikausi_alku_loppu_row['tilikausi_alku']}'
		            and tiliointi.tapvm <= '{$tilikausi_alku_loppu_row['tilikausi_loppu']}'
		            GROUP BY groupsarake
					ORDER BY groupsarake";
		$tulosres = mysql_query($query) or pupe_error($query);

		while ($tulosrow = mysql_fetch_assoc($tulosres)) {
			$tulokset[(string) $tulosrow["groupsarake"]] = $tulosrow;
		}

		while ($trow = mysql_fetch_assoc($result)) {

			if ($trow['saldo'] == 0) continue;

			$summa2 += $trow['saldo'];
			
			if ($trow['vientejä'] > 0) {

				$isumma[$maara] 	= $trow['saldo'];
				$itili[$maara] 		= $trow['tilino'];
				$ikustp[$maara] 	= $trow['kustp'];
				$iselite[$maara] 	= t("Avaavat saldot")." {$tpp}.{$tpk}.{$tpv}";
				$ivero[$maara] 		= 0;
				$iprojekti[$maara] 	= $trow['projekti'];
				$ikohde[$maara] 	= $trow['kohde'];

				echo "<tr class='aktiivi'>";

				$lopelink = "&lopetus=$PHP_SELF////tee=$tee//tilikausi=$tilikausi//tilikauden_voiton_kasittely=$tilikauden_voiton_kasittely";

				$linkkilisa = "&tkausi=$tilikausi";

				echo "<td><a name='tili2_{$trow['tilino']}' href='raportit.php?toim=paakirja&tee=K&tili={$trow['tilino']}{$linkkilisa}{$lopelink}///tili2_{$trow['tilino']}'>{$trow['tilino']}</a></td>";

				echo "<td>{$trow['vientejä']}</td>";
				echo "<td align='right'>{$trow['saldo']}</td>";

				$summa3 = $summa;

				if ($trow['tapvm'] == '') {
					$lisa = " AND tiliointi.tapvm >= '{$tilikausi_alku_loppu_row['tilikausi_alku']}' AND tiliointi.tapvm <= '{$tilikausi_alku_loppu_row['tilikausi_loppu']}' ";
				}
				else {
					$lisa = " AND tiliointi.tapvm = '{$trow['tapvm']}' ";
				}

				$query = "	SELECT sum(summa_valuutassa) summa_val, valkoodi
							FROM tiliointi
							WHERE tiliointi.yhtio = '{$kukarow['yhtio']}'
							AND tiliointi.korjattu = ''
							AND tiliointi.tilino = '{$trow['tilino']}'
							AND tiliointi.valkoodi != '{$yhtiorow['valkoodi']}'
							AND tiliointi.valkoodi != ''
							$lisa
							GROUP BY tiliointi.valkoodi
							HAVING summa_val != 0";
				$summa_val_result = mysql_query($query) or pupe_error($query);

				echo "<td align='right'>";

				while ($summa_val_row = mysql_fetch_assoc($summa_val_result)) {
					echo "{$summa_val_row['summa_val']} {$summa_val_row['valkoodi']}<br />";
					$summa_valuutassa[$summa_val_row['valkoodi']] += $summa_val_row['summa_val'];
				}

				echo "</td></tr>";

				$maara++;
			}
		}

		if (trim($yhtiorow['tilikauden_tulos']) != '' and $tilikauden_voiton_kasittely == 'automaattisesti') {
			$query = "	SELECT tilino, nimi
						FROM tili
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$yhtiorow['tilikauden_tulos']}'";
			$tili_result = mysql_query($query) or pupe_error($query);
			$tili_row = mysql_fetch_assoc($tili_result);

			echo "<tr class='aktiivi'>";
			echo "<td><a name='tili2_{$tili_row['tilino']}' href='raportit.php?toim=paakirja&tee=K&tili={$tili_row['tilino']}{$linkkilisa}{$lopelink}///tili2_{$trow['tilino']}'>{$tili_row['tilino']}</a></td>";
			echo "<td>{$tili_row['nimi']}</td><td>{$tulokset[$kukarow['yhtio']]['summa']}</td><td>&nbsp;</td>";
			echo "</tr>";

			echo "<input type='hidden' name='edellisen_tilikauden_summa' value='{$summa2}' />";

			$isumma[$maara] 	= $tulokset[$kukarow['yhtio']]['summa'];
			$itili[$maara] 		= $tilikauden_tulos;
			$ikustp[$maara] 	= '';
			$iselite[$maara] 	= t("Siirretään tilikauden tulos"); 
			$ivero[$maara] 		= 0;
			$iprojekti[$maara] 	= '';
			$ikohde[$maara] 	= '';
		}

		echo "<tr>";

		$cspan = 2;

		echo "<td class='tumma' colspan='{$cspan}'>",t("Summa"),"</td><td align='right' class='tumma'>",sprintf('%.2f', $summa2),"</td>";
		echo "<td align='right' class='tumma'>";

		foreach ($summa_valuutassa as $valuutta => $sum) {
			echo sprintf('%.2f', $sum)," $valuutta<br/>";
		}

		echo "<input type='hidden' name='isumma' value='",urlencode(serialize($isumma)),"' />";
		echo "<input type='hidden' name='itili' value='",urlencode(serialize($itili)),"' />";
		echo "<input type='hidden' name='ikustp' value='",urlencode(serialize($ikustp)),"' />";
		echo "<input type='hidden' name='iselite' value='",urlencode(serialize($iselite)),"' />";
		echo "<input type='hidden' name='ivero' value='",urlencode(serialize($ivero)),"' />";
		echo "<input type='hidden' name='iprojekti' value='",urlencode(serialize($iprojekti)),"' />";
		echo "<input type='hidden' name='ikohde' value='",urlencode(serialize($ikohde)),"' />";
		echo "<input type='hidden' name='maara' value='{$maara}' />";

		echo "</td></tr>";

		if ($tilikausi_alku_loppu_row['avaava_tase'] == 0) {
			if ($tilikauden_voiton_kasittely == 'automaattisesti' and $summa2 - $tulokset[$kukarow['yhtio']]['summa'] != 0) {
				echo "<tr><td class='back' colspan='4'><font class='message'>",t("Summat eivät täsmää"),"!</font></td></tr>";
			}
			else {
				echo "<tr><th colspan='4'><input type='submit' value='",t("Tee tosite"),"' /></th></tr>";
			}
		}
		else {
			echo "<tr><td class='back' colspan='4'><font class='message'>",t("Tosite on jo luotu"),"!</font></td></tr>";
		}
		echo "</table></form><br /><br />";
	}

	require ("inc/footer.inc");
