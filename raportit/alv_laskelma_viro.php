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

	function laskeveroja($taso, $tulos) {
		global $yhtiorow, $kukarow, $startmonth, $endmonth, $oletus_verokanta;

		if ($tulos == $oletus_verokanta or $tulos == 'veronmaara' or $tulos == 'summa') {

			$maalisa	 	 = '';
			$ee300lisa	 	 = '';
			$vainveroton 	 = '';
			$tuotetyyppilisa = '';
			$cleantaso 		 = $taso;

			// Kaikki veroton myynti
			if ($taso == 'ee300') {
				$ee300lisa 	 = " or alv_taso like '%ee100%'
								 or alv_taso like '%ee110%'
								 or alv_taso like '%ee310%'
								 or alv_taso like '%ee320%'";
				$vainveroton = " and tiliointi.vero = 0 ";
			}			

			if ($taso == 'ee311') {
				$tuotetyyppilisa = " AND tuote.tuotetyyppi != 'K' ";
				$taso 			 = 'ee310';
				$cleantaso 		 = 'ee311';
			}

			$query = "	SELECT ifnull(group_concat(if(alv_taso like '%fi300%', concat(\"'\",tilino,\"'\"), NULL)), '') tilit300,
						ifnull(group_concat(if(alv_taso not like '%fi300%', concat(\"'\",tilino,\"'\"), NULL)), '') tilitMUU
						FROM tili
						WHERE yhtio = '$kukarow[yhtio]'
						and (alv_taso like '%$taso%' $ee300lisa)";
			$tilires = pupe_query($query);
			$tilirow = mysql_fetch_assoc($tilires);

			$vero = 0.0;

			if ($tilirow['tilit300'] != '' or $tilirow['tilitMUU'] != '') {
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

					if ($tilirow["tilit300"] != "") $query .= "	(tilino in ($tilirow[tilit300]) $vainveroton)";
					if ($tilirow["tilit300"] != "" and $tilirow["tilitMUU"] != "") $query .= " or ";
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

	function alvlaskelma($kk, $vv) {
		global $yhtiorow, $kukarow, $startmonth, $endmonth, $oletus_verokanta, $maksettava_alv_tili, $palvelin2, $erotus_tili, $alv_laskelman_sallittu_erotus;

		echo "<font class='head'>".t("ALV-laskelma")."</font><hr>";

		if (isset($kk) and $kk != '') {

			$startmonth	= date("Y-m-d", mktime(0, 0, 0, $kk,   1, $vv));
			$endmonth 	= date("Y-m-d", mktime(0, 0, 0, $kk+1, 0, $vv));

			//1.	EE100	Sales 20% VAT
			//1.1	EE110	Sales 20% VAT, omaan käyttöön
			//2.	EE200	Sales  9% VAT
			//2.1	EE210	Sales  9% VAT, omaan käyttöön
			$query = "	SELECT group_concat(concat(\"'\",tilino,\"'\")) tilit
						FROM tili
						WHERE yhtio = '$kukarow[yhtio]'
						AND (alv_taso like '%EE100%' OR alv_taso like '%EE110%')";
			$tilires = pupe_query($query);

			$eeXXX = array();
			$ee100 = 0.0;
			$ee110 = 0.0;
			$ee200 = 0.0;
			$ee210 = 0.0;

			$tilirow = mysql_fetch_assoc($tilires);

			if ($tilirow['tilit'] != '') {
				$query = "	SELECT vero, sum(round(summa * (1 + vero / 100), 2)) summa, count(*) kpl
							FROM tiliointi
							WHERE yhtio = '$kukarow[yhtio]'
							AND korjattu = ''
							AND tilino in ($tilirow[tilit])
							AND tapvm >= '$startmonth'
							AND tapvm <= '$endmonth'
							AND vero > 0
							GROUP BY vero
							ORDER BY vero DESC";
				$verores = pupe_query($query);

				while ($verorow = mysql_fetch_assoc ($verores)) {

					switch ($verorow['vero']) {
						case 20 :
							$ee100 += $verorow['summa'];
							break;
						case 9 :
							$ee200 += $verorow['summa'];
							break;
						default:
							$eeXXX[$verorow['vero']] += $verorow['summa'];
							break;
					}
				}
			}

			//3. EE300 Sales 0% VAT
			$ee300 = laskeveroja('ee300','summa');
			
			//3.1. EE310 Intra-Community supply of GOODS AND SERVICES
			$ee310 = laskeveroja('ee310','summa');
			
			//3.1.1. EE320 Intra-Community supply of GOODS
			$ee311 = laskeveroja('ee311','summa');
			
			//3.2. Exportation of goods outside EU
			$ee320 = laskeveroja('ee320','summa');
			
			//3.2.1. Exportation of goods outside EU, sale to passengers with return of value added tax
			$ee321 = 0;

			echo "<br><table>";
			echo "<tr><th>",t("Ilmoittava yritys"),"</th><th>EE{$yhtiorow["ytunnus"]}</th></tr>";
			echo "<tr><th>",t("Ilmoitettava kausi"),"</th><th>".substr($startmonth,0,4)."/".substr($startmonth,5,2)."</th></tr>";

			echo "<tr class='aktiivi'><td>1 20% määraga maksustatavad toimingud ja tehingud, sh</td><td align='right'>".sprintf('%.2f', $ee100)."</td></tr>";
			echo "<tr class='aktiivi'><td>&raquo; 1.1 20% määraga maksustatav kauba või teenuse omatarve</td><td align='right'>".sprintf('%.2f', $ee110)."</td></tr>";
			echo "<tr class='aktiivi'><td>2 9% määraga maksustatavad toimingud ja tehingud, sh</td><td align='right'>".sprintf('%.2f', $ee200)."</td></tr>";
			echo "<tr class='aktiivi'><td>&raquo; 2.1 9% määraga maksustatav kauba voi teenuse omatarve</td><td align='right'>".sprintf('%.2f', $ee210)."</td></tr>";

			// Väärät alvikannat
			foreach ($eeXXX as $eekey => $eeval) {
				echo "<tr><td>XXX ".($eekey * 1)."% VAT</td><td align='right'>".sprintf('%.2f', $eeval)."</td></tr>";
			}

			echo "<tr class='aktiivi'><td>3 0% määraga maksustatavad toimingud ja tehingud, sh</td><td align='right'>".sprintf('%.2f', 0)."</td></tr>";
			echo "<tr class='aktiivi'><td>&raquo; 3.1 Kauba ühendusesisene käive ja teise liikmesriigi maksukohustuslase / piiratud maksukohustuslase osutatud teenuste käive kokku, sh</td><td align='right'>".sprintf('%.2f', 0)."</td></tr>";
			echo "<tr class='aktiivi'><td>&raquo; &raquo; 3.1.1 Kauba ühendusesisene käive</td><td align='right'>".sprintf('%.2f', 0)."</td></tr>";
			echo "<tr class='aktiivi'><td>&raquo; 3.2 Kauba eksport, sh</td><td align='right'>".sprintf('%.2f', 0)."</td></tr>";
			echo "<tr class='aktiivi'><td>&raquo; &raquo; 3.2.1 Käibemaksutagastusega müük reisijale</td><td align='right'>".sprintf('%.2f', 0)."</td></tr>";
			echo "<tr class='aktiivi'><td>4 Käibemaks kokku (20% lahtrist 1 + 9% lahtrist 2) +</td><td align='right'>".sprintf('%.2f', 0)."</td></tr>";
			echo "<tr class='aktiivi'><td>&raquo; 4.1 Impordilt tasumisele kuuluv käibemaks +</td><td align='right'>".sprintf('%.2f', 0)."</td></tr>";
			echo "<tr class='aktiivi'><td>5 Kokku sisendkäibemaksusumma, mis on seadusega lubatud maha arvata, sh -</td><td align='right'>".sprintf('%.2f', 0)."</td></tr>";
			echo "<tr class='aktiivi'><td>&raquo; 5.1 Impordilt tasutud või tasumisele kuuluv käibemaks</td><td align='right'>".sprintf('%.2f', 0)."</td></tr>";
			echo "<tr class='aktiivi'><td>&raquo; 5.2 Põhivara soetamiselt tasutud või tasumisele kuuluv käibemaks</td><td align='right'>".sprintf('%.2f', 0)."</td></tr>";
			echo "<tr class='aktiivi'><td>6 Kauba ühendusesisene soetamine ja teise liikmesriigi maksukohustuslaselt saadud teenused kokku, sh</td><td align='right'>".sprintf('%.2f', 0)."</td></tr>";
			echo "<tr class='aktiivi'><td>&raquo; 6.1 Kauba ühendusesisene soetamine</td><td align='right'>".sprintf('%.2f', 0)."</td></tr>";
			echo "<tr class='aktiivi'><td>7 Muu kauba soetamine ja teenuse saamine, mida maksustatakse käibemaksuga, sh</td><td align='right'>".sprintf('%.2f', 0)."</td></tr>";
			echo "<tr class='aktiivi'><td>&raquo; 7.1 Erikorra alusel maksustatava kinnisasja, metallijäätmete, kullamaterjali ja investeeringukulla soetamine (KMS § 41)</td><td align='right'>".sprintf('%.2f', 0)."</td></tr>";
			echo "<tr class='aktiivi'><td>8 Maksuvaba käive</td><td align='right'>".sprintf('%.2f', 0)."</td></tr>";
			echo "<tr class='aktiivi'><td>9 Erikorra alusel maksustatava kinnisasja, metallijäätmete, kullamaterjali ja investeeringukulla käive</td><td align='right'>".sprintf('%.2f', 0)."</td></tr>";
			echo "<tr class='aktiivi'><td>10 Täpsustused +</td><td align='right'>".sprintf('%.2f', 0)."</td></tr>";
			echo "<tr class='aktiivi'><td>11 Täpsustused -</td><td align='right'>".sprintf('%.2f', 0)."</td></tr>";
			echo "<tr class='aktiivi'><td>12 Tasumisele kuuluv käibemaks (lahter 4 + lahter 4.1 - lahter 5 + lahter 10 - lahter 11) +</td><td align='right'>".sprintf('%.2f', 0)."</td></tr>";
			echo "<tr class='aktiivi'><td>13 Enammakstud käibemaks (lahter 4 + lahter 4.1 - lahter 5 + lahter 10 - lahter 11) -</td><td align='right'>".sprintf('%.2f', 0)."</td></tr>";


			echo "</table><br>";
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
