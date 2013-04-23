<?php

	require("../inc/parametrit.inc");

	/*
	1.		EE100	Sales 20% VAT
	1.1		EE110	Sales 20% VAT, omaan käyttöön
	2.		EE200	Sales  9% VAT
	2.1		EE210	Sales  9% VAT, omaan käyttöön
	3.		EE300	Sales  0% VAT
	3.1		EE310	Intra-Community supply of GOODS AND SERVICES provided to a taxable person of another Member State / taxable person with limited liability
	3.1.1	EE311	Intra-Community supply of GOODS provided to a taxable person of another Member State / taxable person with limited liability
	3.2		EE320	Exportation of goods outside EU
	3.2.1	EE321	Exportation of goods outside EU, sale to passengers with return of value added tax
	4.		EE400	VAT from sales: EE400=(EE100*20%)+(EE200*9%)
	4.1		EE410	VAT payable upon the import of the goods
	5.		EE500	Total amount of input VAT subject to deduction
	5.1		EE510	Total amount of input VAT subject to deduction from PRODUCT IMPORT (outside EU)
	5.2		EE520	Total amount of input VAT subject to deduction from FIXED ASSET PURCHASES
	6.		EE600	Intra-Community acquisitions of GOODS AND SERVICES received from a taxable person of another Member State
	6.1		EE610	Intra-Community acquisitions of GOODS received from a taxable person of another Member State
	7.		EE700	Other extraordinary purchases taxed with VAT
	7.1		EE710	Acquisition of immovables and metal waste taxable by special arrangements for imposition of value added tax on immovables and metal waste
	8.		EE800	Supply exempt from tax / Non-taxable sales
	9.		EE900	Supply of goods taxable by special arrangements for imposition of value added tax 9 on immovables (VAT Act § 411) and metal waste and taxable value of goods to be installed or assembled in another Member State
	10.		EE1000	Corrections +
	11.		EE1100	Corrections -
	12.		EE1200	VAT payable + (EE1200=EE400+EE410-EE500+EE1000-EE1100)
	13.		EE1300	VAT refundable - (EE1300=EE400+EE410-EE500+EE1000-EE1100)
	*/

	$oletus_verokanta = 20;

	// Sallittu erotus on luku kuinka paljon sallitaan ALV-ilmoitus erotus poikkeavan
	if (!isset($alv_laskelman_sallittu_erotus)) {
		$alv_laskelman_sallittu_erotus = 1;
	}

	enable_ajax();

	if (isset($livesearch_tee) and $livesearch_tee == "TILIHAKU") {
		livesearch_tilihaku();
		exit;
	}

	if (isset($tee) and $tee == 'kuittaa_alv_ilmoitus') {

		$query = "	SELECT lasku.tunnus
					FROM lasku
					JOIN tiliointi ON (tiliointi.yhtio = lasku.yhtio AND tiliointi.ltunnus = lasku.tunnus)
					WHERE lasku.yhtio = '{$kukarow['yhtio']}'
					AND lasku.tapvm = '{$loppukk}'
					AND lasku.tila = 'X'
					AND lasku.nimi = 'ALVTOSITEMAKSUUN$loppukk'";
		$tositelinkki_result = pupe_query($query);

		if (mysql_num_rows($tositelinkki_result) == 0) {

			$query = "	SELECT *
						FROM tili
						WHERE yhtio = '$kukarow[yhtio]'
						AND tilino = '$maksettava_alv_tili'";
			$tilires = pupe_query($query);

			if (mysql_num_rows($tilires) != 1) {
				echo "<font class='error'>",t("VIRHE: Maksettava ALV-Tili virheellinen")."! ($maksettava_alv_tili)</font><br><br>";
			}

			$query = "	SELECT *
						FROM tili
						WHERE yhtio = '$kukarow[yhtio]'
						AND tilino = '$erotus_tili'";
			$erotilires = pupe_query($query);

			if (mysql_num_rows($erotilires) != 1) {
				echo "<font class='error'>",t("VIRHE: Erotuksen Tili virheellinen")."! ($erotus_tili)</font><br><br>";
			}

			if (mysql_num_rows($tilires) == 1 and mysql_num_rows($erotilires) == 1) {

				$alvtili_yht = (float) $alvtili_yht;
	            $alvmaks_yht = (float) $alvmaks_yht;
				$alvpyor_yht = (float) round($alvmaks_yht-$alvtili_yht, 2);

				$summa 				= $alvtili_yht;
				$tili 				= $yhtiorow['alv'];
				$kustp 				= '';
				$selite 			= t("ALV")." $kk $vv ".t("maksuun");
				$vero 				= 0;
				$projekti 			= '';
				$kohde 				= '';
				$summa_valuutassa 	= 0;
				$valkoodi 			= $yhtiorow['valuutta'];

				list($tpv2, $tpk2, $tpp2) = explode("-", $tilikausi_loppu);

				$query = "	INSERT into lasku set
							yhtio 		= '{$kukarow['yhtio']}',
							tapvm 		= '{$loppukk}',
							tila 		= 'X',
							nimi		= 'ALVTOSITEMAKSUUN$loppukk',
							alv_tili 	= '',
							comments	= '',
							laatija 	= '{$kukarow['kuka']}',
							luontiaika 	= now()";
				$result = pupe_query($query);
				$tunnus = mysql_insert_id ($link);

				require("inc/teetiliointi.inc");

				$summa 				= $alvmaks_yht * -1;
				$tili 				= $maksettava_alv_tili;
				$kustp 				= '';
				$selite 			= t("ALV")." $kk $vv ".t("maksuun");
				$vero 				= 0;
				$projekti 			= '';
				$kohde 				= '';
				$summa_valuutassa 	= 0;
				$valkoodi 			= $yhtiorow['valuutta'];

				require("inc/teetiliointi.inc");

				if ($alvpyor_yht != 0) {

					$summa 				= $alvpyor_yht;
					$tili 				= $erotus_tili;
					$kustp 				= '';
					$selite 			= t("ALV")." $kk $vv ".t("maksuun");
					$vero 				= 0;
					$projekti 			= '';
					$kohde 				= '';
					$summa_valuutassa 	= 0;
					$valkoodi 			= $yhtiorow['valuutta'];

					require("inc/teetiliointi.inc");
				}
			}

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
		else {
			echo "<font class='error'>",t("VIRHE: ALV-laskelma on jo täsmätty"),"!</font><br><br>";
		}
	}

	if (isset($tee) and $tee == 'erittele') {

		$alvv			 = $vv;
		$alvk			 = $kk;
		$alvp			 = 0;
		$tiliointilisa 	 = '';
		$alkupvm  		 = date("Y-m-d", mktime(0, 0, 0, $alvk,   1, $alvv));
		$loppupvm 		 = date("Y-m-d", mktime(0, 0, 0, $alvk+1, 0, $alvv));
		$kerroin 		 = " * -1";

		$maalisa	 	 = '';
		$eetasolisa	 	 = '';
		$vainveroton 	 = '';
		$tuotetyyppilisa = '';
		$cleantaso 		 = $taso;

		if ($ryhma == '1') {
			$taso = "ee100";
			$eetasolisa = " or alv_taso like '%ee110%'";
			$tiliointilisa = " and tiliointi.vero = 20 ";
		}
		elseif ($ryhma == '1.1') {
			$taso = "ee110";
			$tiliointilisa = " and tiliointi.vero = 20 ";
		}
		elseif ($ryhma == '2') {
			$taso = "ee100";
			$eetasolisa = " or alv_taso like '%ee110%'";
			$tiliointilisa = " and tiliointi.vero = 9 ";
		}
		elseif ($ryhma == '2.1') {
			$taso = "ee110";
			$tiliointilisa = " and tiliointi.vero = 9 ";
		}
		elseif ($ryhma == '3') {
			$taso = "ee300";
			$eetasolisa  = " or alv_taso like '%ee310%'
							 or alv_taso like '%ee320%'";
			$vainveroton = " and tiliointi.vero = 0 ";
		}
		elseif ($ryhma == '3.1') {
			$taso = "ee310";
		}
		elseif ($ryhma == '3.1.1') {
			$tuotetyyppilisa = " AND tuote.tuotetyyppi != 'K' ";
			$taso = 'ee310';
		}
		elseif ($ryhma == '3.2') {
			$taso = 'ee320';
		}
		elseif ($ryhma == '5') {
			$taso = 'ee500';
			$eetasolisa = " or alv_taso like '%ee510%'
							or alv_taso like '%ee520%'";
			$kerroin = "";
		}
		elseif ($ryhma == '5.1') {
			$taso = 'ee510';
			$kerroin = "";
		}
		elseif ($ryhma == '5.2') {
			$taso = 'ee520';
			$kerroin = "";
		}

		$query = "	SELECT
					ifnull(group_concat(if(alv_taso like '%ee100%' or alv_taso like '%ee110%', concat(\"'\",tilino,\"'\"), NULL)), '') tilit100,
					ifnull(group_concat(if(alv_taso not like '%ee100%' and alv_taso not like '%ee110%', concat(\"'\",tilino,\"'\"), NULL)), '') tilitMUU
					FROM tili
					WHERE yhtio = '$kukarow[yhtio]'
					and (alv_taso like '%$taso%' $eetasolisa)";
		$tilires = pupe_query($query);
		$tilirow = mysql_fetch_assoc($tilires);

		if ($ryhma == '3.1.1') {

			$query = "	SELECT if(lasku.toim_maa = '', '$yhtiorow[maa]', lasku.toim_maa) maa,
						if(lasku.valkoodi = '', '$yhtiorow[valkoodi]', lasku.valkoodi) valuutta,
						tilausrivi.alv vero,
						group_concat(DISTINCT lasku.tunnus) ltunnus,
						group_concat(DISTINCT lasku.laskunro) laskunro,
						sum(round(rivihinta * (1 + tilausrivi.alv / 100), 2)) bruttosumma,
						sum(round(rivihinta * (tilausrivi.alv / 100), 2)) verot,
						sum(round(rivihinta / if(lasku.vienti_kurssi = 0, 1, lasku.vienti_kurssi) * (1 + tilausrivi.alv / 100), 2)) bruttosumma_valuutassa,
						sum(round(rivihinta / if(lasku.vienti_kurssi = 0, 1, lasku.vienti_kurssi) * tilausrivi.alv / 100, 2)) verot_valuutassa
						FROM lasku USE INDEX (yhtio_tila_tapvm)
						JOIN tilausrivi USE INDEX (uusiotunnus_index) ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus)
						JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.tuoteno != '$yhtiorow[ennakkomaksu_tuotenumero]' $tuotetyyppilisa)
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila = 'U'
						and lasku.tapvm >= '$alkupvm'
						and lasku.tapvm <= '$loppupvm'
						and lasku.vienti = 'E'
						and lasku.tilaustyyppi != '9'
						GROUP BY 1, 2, 3
						ORDER BY maa, valuutta, vero";
			$result = pupe_query($query);

			echo "<font class='head'>Kauba ühendusesisene käive:</font><hr>";

			echo "<table><tr>";
			echo "<th valign='top'>" . t("Maa") . "</th>";
			echo "<th valign='top'>" . t("Val") . "</th>";
			echo "<th valign='top'>" . t("Vero") . "</th>";
			echo "<th valign='top'>" . t("Tili") . "</th>";
			echo "<th valign='top'>" . t("Nimi") . "</th>";
			echo "<th valign='top'>" . t("Verollinen summa") . "</th>";
			echo "<th valign='top'>" . t("Verot") . "</th>";
			echo "<th valign='top'>" . t("Verollinen summa valuutassa") . "</th>";
			echo "<th valign='top'>" . t("Verot valuutassa") . "</th>";
			echo "<th valign='top'>" . t("Laskut") . "</th>";
			echo "</tr>";

			$verosum  = 0.0;
			$kplsum   = 0;
			$verotot  = 0.0;
			$kpltot   = 0;
			$kantasum = 0.0;
			$kantatot = 0.0;
			unset($edvero);
			unset($edmaa);

			while ($trow = mysql_fetch_assoc($result)) {

				if ($trow['bruttosumma'] == 0) continue;

				if (isset($edvero) and ($edvero != $trow["vero"] or (isset($edmaa) and $edmaa != $trow["maa"]))) { // Vaihtuiko verokanta?
					echo "<tr>
							<td colspan = '5' align = 'right' class='spec'>".t("Yhteensä").":</td>
							<td align = 'right' class='spec'>".sprintf('%.2f', $kantasum)."</td>
							<td align = 'right' class='spec'>".sprintf('%.2f', $verosum)."</td>
							<td colspan='3' class='spec'></td>
						  </tr>";

					$verosum 	= 0.0;
					$kplsum 	= 0;
					$kantasum	= 0.0;
				}

				$query = "	SELECT group_concat(distinct tili.tilino) tilino, group_concat(distinct tili.nimi) nimi
							FROM tiliointi
							JOIN tili ON (tili.yhtio = tiliointi.yhtio AND tiliointi.tilino = tili.tilino)
							WHERE tiliointi.yhtio = '$kukarow[yhtio]'
							AND tiliointi.ltunnus in ($trow[ltunnus])
							AND tiliointi.tilino in ($tilirow[tilitMUU])";
				$tili_res = pupe_query($query);
				$tili_row = mysql_fetch_assoc($tili_res);

				$trow['tilino'] = $tili_row['tilino'];
				$trow['nimi'] = $tili_row['nimi'];

				echo "<tr>";
				echo "<td valign='top'>$trow[maa]</td>";
				echo "<td valign='top'>$trow[valuutta]</td>";
				echo "<td valign='top' align='right'>". (float) $trow["vero"]."%</td>";
				echo "<td valign='top'><a href='".$palvelin2."raportit.php?toim=paakirja&tee=P&alvv=$vv&alvk=$kk&tili=$trow[tilino]&alv=$trow[vero]&maarajaus=$trow[maa]&lopetus=$PHP_SELF////tee=erittele//ryhma=$ryhma//vv=$vv//kk=$kk//maarajaus=$trow[maa]'>$trow[tilino]</a></td>";
				echo "<td valign='top'>$trow[nimi]</td>";
				echo "<td valign='top' align='right' nowrap>$trow[bruttosumma]</td>";
				echo "<td valign='top' align='right' nowrap>$trow[verot]</td>";

				if (strtoupper($trow["maa"]) != strtoupper($yhtiorow["maa"])) {
					echo "<td valign='top' align='right' nowrap>$trow[bruttosumma_valuutassa]</td>";
					echo "<td valign='top' align='right' nowrap></td>";
				}
				else {
					echo "<td valign='top' align='right'></td>";
					echo "<td valign='top' align='right'></td>";
				}
				echo "<td><a href = '".$palvelin2."tilauskasittely/tulostakopio.php?toim=LASKU&tee=ETSILASKU&laskunro=$trow[laskunro]&lopetus=$PHP_SELF////tee=erittele//ryhma=$ryhma//vv=$vv//kk=$kk//maarajaus=$trow[maa]'>".t("Näytä laskut")."</a></td>";

				echo "</tr>";

				$verosum  += $trow['verot'];
				$kplsum   += $trow['kpl'];
				$verotot  += $trow['verot'];
				$kpltot   += $trow['kpl'];
				$kantasum += $trow['bruttosumma'];
				$kantatot += $trow['bruttosumma'];
				$edvero    = $trow["vero"];
				$edmaa 	   = $trow["maa"];
			}

			if (is_resource($ttres)) {

				mysql_data_seek($ttres, 0);

				while ($trow = mysql_fetch_assoc($ttres)) {

					$trow['bruttosumma']	 		= round($kakerroinlisa * $trow['bruttosumma'], 2);
					$trow['verot'] 					= round($kakerroinlisa * $trow['verot'], 2);
					$trow['bruttosumma_valuutassa'] = round($kakerroinlisa * $trow['bruttosumma_valuutassa'], 2);
					$trow['verot_valuutassa'] 		= round($kakerroinlisa * $trow['verot_valuutassa'], 2);

					echo "<tr>
							<td colspan='5' align='right' class='spec'>".t("Yhteensä").":</td>
							<td align = 'right' class='spec'>".sprintf('%.2f', $kantasum)."</td>
							<td align = 'right' class='spec'>".sprintf('%.2f', $verosum)."</td>
							<td colspan='2' class='spec'></td></tr>";

					$verosum 	= 0.0;
					$kplsum 	= 0;
					$kantasum	= 0.0;

					echo "<tr>";
					echo "<td valign='top'>$trow[maa]</td>";
					echo "<td valign='top'>$trow[valuutta]</td>";
					echo "<td valign='top' align='right'>". (float) $trow["vero"]."%</td>";
					echo "<td valign='top'><a href='".$palvelin2."raportit.php?toim=paakirja&tee=P&alvv=$vv&alvk=$kk&tili=$trow[tilino]&alv=$trow[vero]&maarajaus=$trow[maa]&lopetus=$PHP_SELF////tee=erittele//ryhma=$ryhma//vv=$vv//kk=$kk//maarajaus=$trow[maa]'>$trow[tilino]</a></td>";
					echo "<td valign='top'>$trow[nimi]</td>";
					echo "<td valign='top' align='right' nowrap>",sprintf('%.2f', $trow['bruttosumma']),"</td>";
					echo "<td valign='top' align='right' nowrap>",sprintf('%.2f', $trow['verot']),"</td>";

					if (strtoupper($trow["maa"]) != strtoupper($yhtiorow["maa"])) {
						echo "<td valign='top' align='right' nowrap>",sprintf('%.2f', $trow['bruttosumma_valuutassa']),"</td>";
						echo "<td valign='top' align='right' nowrap>",sprintf('%.2f', $trow['verot_valuutassa']),"</td>";
					}
					else {
						echo "<td valign='top' align='right'></td>";
						echo "<td valign='top' align='right'></td>";
					}

					echo "</tr>";

					$verosum  += $trow['verot'];
					$verotot  += $trow['verot'];
					$kantasum += $trow['bruttosumma'];
					$kantatot += $trow['bruttosumma'];
				}
			}

			echo "<tr><td colspan='5' align='right' class='spec'>".t("Yhteensä").":</td>
					<td align = 'right' class='spec'>".sprintf('%.2f', $kantasum)."</td>
					<td align = 'right' class='spec'>".sprintf('%.2f', $verosum)."</td>
					<td colspan='3' class='spec'></td></tr>";

			echo "<tr><td colspan='5' align='right' class='spec'>".t("Verokannat yhteensä").":</td>
					<td align = 'right' class='spec'>".sprintf('%.2f', $kantatot)."</td>
					<td align = 'right' class='spec'>".sprintf('%.2f', $verotot)."</td>
					<td colspan='3' class='spec'></td></tr>";
			echo "</table><br/>";

		}
		elseif ($tilirow['tilit100'] != '' or $tilirow['tilitMUU'] != '') {

			echo "<table>";
			echo "<tr>";
			echo "<br><font class='head'>".t("Arvonlisäveroerittely kaudelta")." $alvv-$alvk " . t("taso") . " $ryhma</font><hr>";

			$query = "	SELECT if(lasku.toim_maa!='', lasku.toim_maa, if(lasku.maa != '', lasku.maa, '$yhtiorow[maa]')) maa,
						if(lasku.valkoodi = '', '$yhtiorow[valkoodi]', lasku.valkoodi) valuutta,
						tiliointi.vero,
						tiliointi.tilino,
						tili.nimi,
						group_concat(lasku.tunnus) ltunnus,
						sum(round(tiliointi.summa * (1 + tiliointi.vero / 100), 2)) $kerroin bruttosumma,
						sum(round(tiliointi.summa, 2)) $kerroin nettosumma,
						sum(round(tiliointi.summa * tiliointi.vero / 100, 2)) $kerroin verot,
						sum(round(tiliointi.summa / if(lasku.vienti_kurssi = 0, 1, lasku.vienti_kurssi) * (1 + vero / 100), 2)) $kerroin bruttosumma_valuutassa,
						sum(round(tiliointi.summa / if(lasku.vienti_kurssi = 0, 1, lasku.vienti_kurssi), 2)) $kerroin nettosumma_valuutassa,
						sum(round(tiliointi.summa / if(lasku.vienti_kurssi = 0, 1, lasku.vienti_kurssi) * vero / 100, 2)) $kerroin verot_valuutassa,
						count(*) kpl
						FROM tiliointi
						JOIN lasku ON (lasku.yhtio = tiliointi.yhtio AND lasku.tunnus = tiliointi.ltunnus)
						LEFT JOIN tili ON (tili.yhtio = tiliointi.yhtio AND tiliointi.tilino = tili.tilino)
						WHERE tiliointi.yhtio = '$kukarow[yhtio]'
						AND tiliointi.korjattu = ''
						AND tiliointi.tapvm >= '$alkupvm'
						AND tiliointi.tapvm <= '$loppupvm'
						$maalisa
						$tiliointilisa
						AND (";

			if ($tilirow["tilit100"] != "") $query .= "	(tiliointi.tilino in ($tilirow[tilit100]) $vainveroton)";
			if ($tilirow["tilit100"] != "" and $tilirow["tilitMUU"] != "") $query .= " or ";
			if ($tilirow["tilitMUU"] != "") $query .= " tiliointi.tilino in ($tilirow[tilitMUU])";

			$query .= "	)
						GROUP BY 1, 2, 3, 4, 5
						ORDER BY maa, valuutta, vero, tilino, nimi";
			$result = pupe_query($query);

			echo "<table><tr>";
			echo "<th valign='top'>" . t("Maa") . "</th>";
			echo "<th valign='top'>" . t("Val") . "</th>";
			echo "<th valign='top'>" . t("Vero") . "</th>";
			echo "<th valign='top'>" . t("Tili") . "</th>";
			echo "<th valign='top'>" . t("Nimi") . "</th>";
			echo "<th valign='top'>" . t("Veroton summa") . "</th>";
			echo "<th valign='top'>" . t("Verot") . "</th>";
			echo "<th valign='top'>" . t("Veroton summa valuutassa") . "</th>";
			echo "<th valign='top'>" . t("Verot valuutassa") . "</th>";
			echo "<th valign='top'>" . t("Kpl") . "</th>";
			echo "</tr>";

			$verosum  = 0.0;
			$kplsum   = 0;
			$verotot  = 0.0;
			$kpltot   = 0;
			$kantasum = 0.0;
			$kantatot = 0.0;

			while ($trow = mysql_fetch_assoc($result)) {

				// Vaihtuiko verokanta?
				if (isset($edvero) and ($edvero != $trow["vero"] or $edmaa != $trow["maa"])) {
					echo "<tr>
							<td colspan='5' align='right' class='spec'>".t("Yhteensä").":</td>
							<td align = 'right' class='spec'>".sprintf('%.2f', $kantasum)."</td>
							<td align = 'right' class='spec'>".sprintf('%.2f', $verosum)."</td>
							<td colspan='2' class='spec'></td>
							<td align = 'right' class='spec'>$kplsum</td></tr>";

					$verosum 	= 0.0;
					$kplsum 	= 0;
					$kantasum	= 0.0;
				}

				echo "<tr>";
				echo "<td valign='top'>$trow[maa]</td>";
				echo "<td valign='top'>$trow[valuutta]</td>";
				echo "<td valign='top' align='right'>". (float) $trow["vero"]."%</td>";
				echo "<td valign='top'><a href='".$palvelin2."raportit.php?toim=paakirja&tee=P&alvv=$vv&alvk=$kk&tili=$trow[tilino]&alv=$trow[vero]&maarajaus=$trow[maa]&lopetus=$PHP_SELF////tee=erittele//ryhma=$ryhma//vv=$vv//kk=$kk//maarajaus=$trow[maa]'>$trow[tilino]</a></td>";
				echo "<td valign='top'>$trow[nimi]</td>";
				echo "<td valign='top' align='right' nowrap>",sprintf('%.2f', $trow['nettosumma']),"</td>";
				echo "<td valign='top' align='right' nowrap>",sprintf('%.2f', $trow['verot']),"</td>";

				if (strtoupper($trow["maa"]) != strtoupper($yhtiorow["maa"])) {
					echo "<td valign='top' align='right' nowrap>",sprintf('%.2f', $trow['nettosumma_valuutassa']),"</td>";
					echo "<td valign='top' align='right' nowrap>",sprintf('%.2f', $trow['verot_valuutassa']),"</td>";
				}
				else {
					echo "<td valign='top' align='right'></td>";
					echo "<td valign='top' align='right'></td>";
				}

				echo "<td valign='top' align='right' nowrap>$trow[kpl]</td>";
				echo "</tr>";

				$verosum  += $trow['verot'];
				$kplsum   += $trow['kpl'];
				$verotot  += $trow['verot'];
				$kpltot   += $trow['kpl'];
				$kantasum += $trow['nettosumma'];
				$kantatot += $trow['nettosumma'];
				$edvero    = $trow["vero"];
				$edmaa 	   = $trow["maa"];
			}

			echo "<tr><td colspan='5' align='right' class='spec'>".t("Yhteensä").":</td>
					<td align = 'right' class='spec'>".sprintf('%.2f', $kantasum)."</td>
					<td align = 'right' class='spec'>".sprintf('%.2f', $verosum)."</td>
					<td colspan = '2' class='spec'></td>
					<td align = 'right' class='spec'>$kplsum</td></tr>";

			echo "<tr><td colspan='5' align='right' class='spec'>".t("Verokannat yhteensä").":</td>
					<td align = 'right' class='spec'>".sprintf('%.2f', $kantatot)."</td>
					<td align = 'right' class='spec'>".sprintf('%.2f', $verotot)."</td>
					<td colspan = '2' class='spec'></td>
					<td align = 'right' class='spec'>$kpltot</td></tr>";
			echo "</table><br>";
		}
	}

	function laskeveroja ($taso, $tulos) {
		global $yhtiorow, $kukarow, $startmonth, $endmonth, $oletus_verokanta;

		if ($tulos == $oletus_verokanta or $tulos == 'veronmaara' or $tulos == 'summa') {

			$maalisa	 	 = '';
			$eetasolisa	 	 = '';
			$vainveroton 	 = '';
			$tuotetyyppilisa = '';
			$cleantaso 		 = $taso;

			// Kaikki veroton myynti
			if ($taso == 'ee300') {
				$eetasolisa  = " or alv_taso like '%ee310%'
								 or alv_taso like '%ee320%'";
				$vainveroton = " and tiliointi.vero = 0 ";
			}

			if ($taso == 'ee311') {
				$tuotetyyppilisa = " AND tuote.tuotetyyppi != 'K' ";
				$taso 			 = 'ee310';
				$cleantaso 		 = 'ee311';
			}

			if ($taso == 'ee500') {
				$eetasolisa = " or alv_taso like '%ee510%'
								or alv_taso like '%ee520%'";
			}

			if ($taso == 'ee600') {
				$eetasolisa = " or alv_taso like '%ee610%'";
			}

			$vero = 0.0;

			$query = "	SELECT
						ifnull(group_concat(if(alv_taso like '%ee100%' or alv_taso like '%ee110%', concat(\"'\",tilino,\"'\"), NULL)), '') tilit100,
						ifnull(group_concat(if(alv_taso not like '%ee100%' and alv_taso not like '%ee110%', concat(\"'\",tilino,\"'\"), NULL)), '') tilitMUU
						FROM tili
						WHERE yhtio = '$kukarow[yhtio]'
						and (alv_taso like '%$taso%' $eetasolisa)";
			$tilires = pupe_query($query);
			$tilirow = mysql_fetch_assoc($tilires);

			if ($tilirow['tilit100'] != '' or $tilirow['tilitMUU'] != '') {
				if ($tuotetyyppilisa != '') {
					$query = "	SELECT lasku.tunnus, lasku.arvo laskuarvo, round(sum(tilausrivi.rivihinta),2) summa
								FROM lasku USE INDEX (yhtio_tila_tapvm)
								JOIN tilausrivi USE INDEX (uusiotunnus_index) ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus)
								JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.tuoteno != '$yhtiorow[ennakkomaksu_tuotenumero]' $tuotetyyppilisa)
								WHERE lasku.yhtio = '$kukarow[yhtio]'
								and lasku.tila = 'U'
								and lasku.tapvm >= '$startmonth'
								and lasku.tapvm <= '$endmonth'
								and lasku.vienti = 'E'
								and lasku.tilaustyyppi != '9'
								GROUP BY 1,2";
				}
				else {
					$query = "	SELECT sum(round(tiliointi.summa * if('$tulos'='$oletus_verokanta', $oletus_verokanta, vero) / 100, 2)) veronmaara,
								sum(tiliointi.summa) summa,
					 			count(*) kpl
								FROM tiliointi
								$maalisa
								WHERE tiliointi.yhtio = '$kukarow[yhtio]'
								AND korjattu = ''
								AND (";

					if ($tilirow["tilit100"] != "") $query .= "	(tilino in ($tilirow[tilit100]) $vainveroton)";
					if ($tilirow["tilit100"] != "" and $tilirow["tilitMUU"] != "") $query .= " or ";
					if ($tilirow["tilitMUU"] != "") $query .= "	 tilino in ($tilirow[tilitMUU])";

					$query .= "	)
								AND tiliointi.tapvm >= '$startmonth'
								AND tiliointi.tapvm <= '$endmonth'";
				}
				$verores = pupe_query($query);

				while ($verorow = mysql_fetch_assoc($verores)) {
					if ($tulos == $oletus_verokanta) $tulos = 'veronmaara';
					$vero += $verorow[$tulos];
				}
			}
		}
		else {
			$vero = 0;
		}

		return sprintf('%.2f',$vero);
	}

	function laskeverojaverokannoittain ($taso) {
		global $yhtiorow, $kukarow, $startmonth, $endmonth, $oletus_verokanta;

		$ee100lisa = "";

		if ($taso == 'ee100') {
			$ee100lisa 	 = " or alv_taso like '%ee110%'";
		}

		$query = "	SELECT group_concat(concat(\"'\",tilino,\"'\")) tilit
					FROM tili
					WHERE yhtio = '$kukarow[yhtio]'
					AND (alv_taso like '%$taso%' $ee100lisa)";
		$tilires = pupe_query($query);
		$tilirow = mysql_fetch_assoc($tilires);

		$verot = array();

		if ($tilirow['tilit'] != '') {
			$query = "	SELECT vero, sum(round(-1 * tiliointi.summa, 2)) summa, count(*) kpl
						FROM tiliointi
						JOIN lasku on (lasku.yhtio=tiliointi.yhtio and lasku.tunnus=tiliointi.ltunnus and lasku.tilaustyyppi != '9')
						WHERE tiliointi.yhtio = '$kukarow[yhtio]'
						AND tiliointi.korjattu = ''
						AND tiliointi.tilino in ($tilirow[tilit])
						AND tiliointi.tapvm >= '$startmonth'
						AND tiliointi.tapvm <= '$endmonth'
						AND tiliointi.vero > 0
						GROUP BY vero
						ORDER BY vero DESC";
			$verores = pupe_query($query);

			while ($verorow = mysql_fetch_assoc($verores)) {
				$verot[$verorow['vero']] += $verorow['summa'];
			}
		}

		return $verot;
	}

	function alvlaskelma($kk, $vv) {
		global $yhtiorow, $kukarow, $startmonth, $endmonth, $oletus_verokanta, $maksettava_alv_tili, $palvelin2, $erotus_tili, $alv_laskelman_sallittu_erotus;

		echo "<font class='head'>".t("ALV-laskelma")."</font><hr>";

		if (isset($kk) and $kk != '') {

			$startmonth	= date("Y-m-d", mktime(0, 0, 0, $kk,   1, $vv));
			$endmonth 	= date("Y-m-d", mktime(0, 0, 0, $kk+1, 0, $vv));

			//1. Sales 20% VAT
			//2. Sales 9% VAT
			$ee100 = laskeverojaverokannoittain('ee100');

			//1.1 Sales 20% VAT, vain omaan käyttöön
			//2.1 Sales 9% VAT, vain omaan käyttöön
			$ee110 = laskeverojaverokannoittain('ee110');

			//3. Sales 0% VAT
			$ee300 = laskeveroja('ee300','summa') * -1;

			//3.1. Intra-Community supply of GOODS AND SERVICES
			$ee310 = laskeveroja('ee310','summa') * -1;

			//3.1.1. Intra-Community supply of GOODS
			$ee311 = laskeveroja('ee311','summa');

			//3.2. Exportation of goods outside EU
			$ee320 = laskeveroja('ee320','summa') * -1;

			//3.2.1. Exportation of goods outside EU, sale to passengers with return of value added tax
			$ee321 = 0;

			//4. VAT from sales: ($ee100*20%)+($ee200*9%)
			$ee400 = round(($ee100["20.00"] * 0.20) + ($ee100["9.00"] * 0.09), 2);

			//4.1. VAT payable upon the import of the goods (Ei implementoitu)
			$ee410 = 0;

			//5. Total amount of input VAT subject to deduction
			$ee500 = laskeveroja('ee500','veronmaara');

			//5.1. Total amount of input VAT subject to deduction from PRODUCT IMPORT (outside EU)
			$ee510 = laskeveroja('ee510','veronmaara');

			//5.2. Total amount of input VAT subject to deduction from FIXED ASSET PURCHASES
			$ee520 = laskeveroja('ee520','veronmaara');

			//6. Intra-Community acquisitions of GOODS AND SERVICES received from a taxable person of another Member State
			$ee600 = laskeveroja('ee600','summa');

			//6.1. Intra-Community acquisitions of GOODS received from a taxable person of another Member State
			$ee610 = laskeveroja('ee610','summa');

			//7. Other extraordinary purchases taxed with VAT
			$ee700 = 0;

			//7.1. Acquisition of immovables and metal waste taxable by special arrangements for imposition of value added tax on immovables and metal waste
			$ee710 = 0;

			//8. Supply exempt from tax / Non-taxable sales
			$ee800 = 0;

			//9. Supply of goods taxable by special arrangements for imposition of value added tax 9 on immovables (VAT Act § 411) and metal waste and taxable value of goods to be installed or assembled in another Member State
			$ee900 = 0;

			//10. Corrections +
			$ee1000 = 0;

			//11. Corrections -
			$ee1100 = 0;

			// Maksettava ALV
			$vat_loppusumma = $ee400+$ee410-$ee500+$ee1000-$ee1100;

			//12. VAT payable + (EE1200=EE400+EE410-EE500+EE1000-EE1100)
			$ee1200 = $vat_loppusumma > 0 ? $vat_loppusumma : 0;

			//13. VAT refundable - (EE1300=EE400+EE410-EE500+EE1000-EE1100)
			$ee1300 = $vat_loppusumma < 0 ? $vat_loppusumma : 0;

			echo "<br><table>";
			echo "<tr><th>",t("Ilmoittava yritys"),"</th><th>EE{$yhtiorow["ytunnus"]}</th></tr>";
			echo "<tr><th>",t("Ilmoitettava kausi"),"</th><th>".substr($startmonth,0,4)."/".substr($startmonth,5,2)."</th></tr>";

			// Verollinen myynti
			echo "<tr class='aktiivi'><td><a href = '?tee=erittele&ryhma=1&vv=$vv&kk=$kk'>1)</a> 20% määraga maksustatavad toimingud ja tehingud, sh</td><td align='right'>".sprintf('%.2f', $ee100["20.00"])."</td></tr>";
			echo "<tr class='aktiivi'><td>&raquo; <a href = '?tee=erittele&ryhma=1.1&vv=$vv&kk=$kk'>1.1)</a> 20% määraga maksustatav kauba või teenuse omatarve</td><td align='right'>".sprintf('%.2f', $ee110["20.00"])."</td></tr>";
			echo "<tr class='aktiivi'><td><a href = '?tee=erittele&ryhma=2&vv=$vv&kk=$kk'>2)</a> 9% määraga maksustatavad toimingud ja tehingud, sh</td><td align='right'>".sprintf('%.2f', $ee100["9.00"])."</td></tr>";
			echo "<tr class='aktiivi'><td>&raquo; <a href = '?tee=erittele&ryhma=2.1&vv=$vv&kk=$kk'>2.1)</a> 9% määraga maksustatav kauba voi teenuse omatarve</td><td align='right'>".sprintf('%.2f', $ee110["9.00"])."</td></tr>";

			// Väärät alvikannat
			foreach ($ee100 as $eekey => $eeval) {
				if ($eekey != "20.00" and $eekey != "9.00") echo "<tr><td>XXX ".($eekey * 1)."% määraga maksustatavad toimingud ja tehingud</td><td align='right'>".sprintf('%.2f', $eeval)."</td></tr>";
			}
			foreach ($ee110 as $eekey => $eeval) {
				if ($eekey != "20.00" and $eekey != "9.00") echo "<tr><td>XXX ".($eekey * 1)."% määraga maksustatav kauba voi teenuse omatarve</td><td align='right'>".sprintf('%.2f', $eeval)."</td></tr>";
			}

			// Veroton myynti
			echo "<tr class='aktiivi'><td><a href = '?tee=erittele&ryhma=3&vv=$vv&kk=$kk'>3)</a> 0% määraga maksustatavad toimingud ja tehingud, sh</td><td align='right'>".sprintf('%.2f', $ee300)."</td></tr>";
			echo "<tr class='aktiivi'><td>&raquo; <a href = '?tee=erittele&ryhma=3.1&vv=$vv&kk=$kk'>3.1)</a> Kauba ühendusesisene käive ja teise liikmesriigi maksukohustuslase / piiratud maksukohustuslase osutatud teenuste käive kokku, sh</td><td align='right'>".sprintf('%.2f', $ee310)."</td></tr>";
			echo "<tr class='aktiivi'><td>&raquo; &raquo; <a href = '?tee=erittele&ryhma=3.1.1&vv=$vv&kk=$kk'>3.1.1)</a> Kauba ühendusesisene käive</td><td align='right'>".sprintf('%.2f', $ee311)."</td></tr>";
			echo "<tr class='aktiivi'><td>&raquo; <a href = '?tee=erittele&ryhma=3.2&vv=$vv&kk=$kk'>3.2)</a> Kauba eksport, sh</td><td align='right'>".sprintf('%.2f', $ee320)."</td></tr>";
			echo "<tr class='aktiivi'><td>&raquo; &raquo; 3.2.1) Käibemaksutagastusega müük reisijale</td><td align='right'>".sprintf('%.2f', $ee321)."</td></tr>";

			echo "<tr class='aktiivi'><td>4) Käibemaks kokku (20% lahtrist 1 + 9% lahtrist 2) +</td><td align='right'>".sprintf('%.2f', $ee400)."</td></tr>";
			echo "<tr class='aktiivi'><td>&raquo; 4.1) Impordilt tasumisele kuuluv käibemaks +</td><td align='right'>".sprintf('%.2f', $ee410)."</td></tr>";

			echo "<tr class='aktiivi'><td><a href = '?tee=erittele&ryhma=5&vv=$vv&kk=$kk'>5)</a> Kokku sisendkäibemaksusumma, mis on seadusega lubatud maha arvata, sh -</td><td align='right'>".sprintf('%.2f', $ee500)."</td></tr>";
			echo "<tr class='aktiivi'><td>&raquo; <a href = '?tee=erittele&ryhma=5.1&vv=$vv&kk=$kk'>5.1)</a> Impordilt tasutud või tasumisele kuuluv käibemaks</td><td align='right'>".sprintf('%.2f', $ee510)."</td></tr>";
			echo "<tr class='aktiivi'><td>&raquo; <a href = '?tee=erittele&ryhma=5.2&vv=$vv&kk=$kk'>5.2)</a> Põhivara soetamiselt tasutud või tasumisele kuuluv käibemaks</td><td align='right'>".sprintf('%.2f', $ee520)."</td></tr>";

			echo "<tr class='aktiivi'><td>6) Kauba ühendusesisene soetamine ja teise liikmesriigi maksukohustuslaselt saadud teenused kokku, sh</td><td align='right'>".sprintf('%.2f', $ee600)."</td></tr>";
			echo "<tr class='aktiivi'><td>&raquo; 6.1) Kauba ühendusesisene soetamine</td><td align='right'>".sprintf('%.2f', $ee610)."</td></tr>";

			echo "<tr class='aktiivi'><td>7) Muu kauba soetamine ja teenuse saamine, mida maksustatakse käibemaksuga, sh</td><td align='right'>".sprintf('%.2f', $ee700)."</td></tr>";
			echo "<tr class='aktiivi'><td>&raquo; 7.1) Erikorra alusel maksustatava kinnisasja, metallijäätmete, kullamaterjali ja investeeringukulla soetamine (KMS § 41)</td><td align='right'>".sprintf('%.2f', $ee710)."</td></tr>";

			echo "<tr class='aktiivi'><td>8) Maksuvaba käive</td><td align='right'>".sprintf('%.2f', $ee800)."</td></tr>";

			echo "<tr class='aktiivi'><td>9) Erikorra alusel maksustatava kinnisasja, metallijäätmete, kullamaterjali ja investeeringukulla käive</td><td align='right'>".sprintf('%.2f', $ee900)."</td></tr>";

			echo "<tr class='aktiivi'><td>10) Täpsustused +</td><td align='right'>".sprintf('%.2f', $ee1000)."</td></tr>";

			echo "<tr class='aktiivi'><td>11) Täpsustused -</td><td align='right'>".sprintf('%.2f', $ee1100)."</td></tr>";

			echo "<tr class='aktiivi'><td>12) Tasumisele kuuluv käibemaks (lahter 4 + lahter 4.1 - lahter 5 + lahter 10 - lahter 11) +</td><td align='right'>".sprintf('%.2f', $ee1200)."</td></tr>";

			echo "<tr class='aktiivi'><td>13) Enammakstud käibemaks (lahter 4 + lahter 4.1 - lahter 5 + lahter 10 - lahter 11) -</td><td align='right'>".sprintf('%.2f', $ee1300)."</td></tr>";
			echo "</table><br>";

			$query = "	SELECT sum(tiliointi.summa) vero
						FROM tiliointi
						WHERE tiliointi.yhtio = '$kukarow[yhtio]'
						AND tiliointi.korjattu = ''
						AND tiliointi.selite not like 'Avaavat saldot%'
						AND tiliointi.tilino = '$yhtiorow[alv]'
						AND tiliointi.tapvm >= '$startmonth'
						AND tiliointi.tapvm <= '$endmonth'";
			$verores = pupe_query($query);
			$verorow = mysql_fetch_assoc ($verores);

			// ei näytetä yhteensä-laatikkoa turhaan
			if ($verorow["vero"] != 0 or (($verorow['vero'] - $ee1200) * -1) != $ee1200 or $ee1200 == 0) {
				echo "<table>";
				echo "<tr class='aktiivi'><th>",t("Tili")," $yhtiorow[alv] ",t("yhteensä"),"</th><td align='right'>".sprintf('%.2f', $verorow['vero'] * -1)."</td></tr>";
				echo "<tr class='aktiivi'><th>",t("Maksettava alv"),"</th><td align='right'>".sprintf('%.2f', $ee1200)."</td></tr>";
				echo "<tr class='aktiivi'><th>",t("Erotus"),"</th><td align='right'>".sprintf('%.2f', (-1 * $verorow['vero']) - $ee1200)."</td></tr>";
				echo "</table><br>";
			}

			if (tarkista_oikeus("muutosite.php")) {

				$query = "	SELECT lasku.tunnus
							FROM lasku
							JOIN tiliointi ON (tiliointi.yhtio = lasku.yhtio AND tiliointi.ltunnus = lasku.tunnus)
							WHERE lasku.yhtio = '{$kukarow['yhtio']}'
							AND lasku.tapvm = '{$endmonth}'
							AND lasku.tila = 'X'
							AND lasku.nimi = 'ALVTOSITEMAKSUUN$endmonth'";
				$tositelinkki_result = pupe_query($query);

				if (mysql_num_rows($tositelinkki_result) > 0) {
					$tositelinkki_row = mysql_fetch_assoc($tositelinkki_result);
					echo "<a href='../muutosite.php?tee=E&tunnus={$tositelinkki_row['tunnus']}&lopetus={$palvelin2}raportit/alv_laskelma_viro.php////kk=$kk//vv=$vv'>",t("Katso tositetta"),"</a><br /><br />";
				}
				elseif (abs($verorow['vero']) != 0 and abs(round((-1 * $verorow['vero']) - $ee1200, 2)) <= $alv_laskelman_sallittu_erotus and (int) date("Ym") > (int) $vv.$kk) {
					echo "<form method='post' name='alv_ilmoituksen_kuittaus'>";
					echo "<table>";
					echo "<input type='hidden' name='alkukk' value='{$startmonth}' />";
					echo "<input type='hidden' name='loppukk' value='{$endmonth}' />";
					echo "<input type='hidden' name='vv' value='$vv' />";
					echo "<input type='hidden' name='kk' value='$kk' />";
					echo "<input type='hidden' name='tee' value='kuittaa_alv_ilmoitus' />";

					echo "<input type='hidden' name='alvmaks_yht' value='".round($ee1200, 2)."' />";
					echo "<input type='hidden' name='alvtili_yht' value='".round($verorow['vero']*-1, 2)."' />";

					echo "<tr><th>",t("Anna maksettava ALV-tili"),"</th><td>";
					echo livesearch_kentta("alv_ilmoituksen_kuittaus", "TILIHAKU", "maksettava_alv_tili", 200, $maksettava_alv_tili, 'EISUBMIT');
					echo "</td></tr>";

					if (!isset($erotus_tili) or $erotus_tili == "") $erotus_tili = $yhtiorow["pyoristys"];

					echo "<tr><th>",t("Anna erotuksen tili"),"</th><td>";
					echo livesearch_kentta("erotuksen_kuittaus", "TILIHAKU", "erotus_tili", 200, $erotus_tili, 'EISUBMIT');

					echo "</td><td class='back'><input type='submit' value='",t("Kuittaa ALV-ilmoitus"),"' /></td></tr>";
					echo "</table></form><br />";
				}
				elseif (abs($verorow['vero']) != 0 and abs(round((-1 * $verorow['vero']) - $ee1200, 2)) != 0 and (int) date("Ym") > (int) $vv.$kk) {
					echo "<font class='error'>",t("Tilin")," {$yhtiorow['alv']} ",t("ja maksettavan arvonlisäveron luvut eivät täsmää"),"!</font><br /><br />";
				}
			}
		}

		// tehdään käyttöliittymä, näytetään aina
		echo "<form method='post'><input type='hidden' name='tee' value ='VSRALVKK_UUSI'>";
		echo "<table>";

		if (!isset($vv)) $vv = date("Y");
		if (!isset($kk)) $kk = date("m");

		echo "<tr>";
		echo "<th>".t("Valitse kausi")."</th>";
		echo "<td>";

		$sel = array();
		$sel[$vv] = "SELECTED";

		$vv_select = date("Y") < 2010 ? 2010 : date("Y");

		echo "<select name='vv'>";
		for ($i = $vv_select; $i >= $vv_select-4; $i--) {
			if ($i < 2010) continue;
			echo "<option value='$i' $sel[$i]>$i</option>";
		}
		echo "</select>";

		$sel = array(1 => '', 2 => '', 3 => '', 4 => '', 5 => '', 6 => '', 7 => '', 8 => '', 9 => '', 10 => '', 11 => '', 12 => '');
		$sel[$kk] = "SELECTED";

		echo "<select name='kk'>
				<option $sel[01] value = '01'>01</option>
				<option $sel[02] value = '02'>02</option>
				<option $sel[03] value = '03'>03</option>
				<option $sel[04] value = '04'>04</option>
				<option $sel[05] value = '05'>05</option>
				<option $sel[06] value = '06'>06</option>
				<option $sel[07] value = '07'>07</option>
				<option $sel[08] value = '08'>08</option>
				<option $sel[09] value = '09'>09</option>
				<option $sel[10] value = '10'>10</option>
				<option $sel[11] value = '11'>11</option>
				<option $sel[12] value = '12'>12</option>
				</select>";
		echo "</td>";

		echo "<td class='back' style='text-align:bottom;'><input type = 'submit' value = '".t("Näytä")."'></td>";
		echo "</tr>";

		echo "</table>";

		echo "</form><br>";
	}

	if (!isset($kk)) $kk = "";
	if (!isset($vv)) $vv = "";

	alvlaskelma($kk, $vv);

	require("inc/footer.inc");
