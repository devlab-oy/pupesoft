<?php
	if ($muutparametrit != '') list($laji, $vv, $kk, $pp ,$tee) = split("/",$muutparametrit);

	if ($tee != '') {	// Tarkistetaan pvm
		// muutetaan numeroiksi
		$kk += 0;
		$pp += 0;
		$vv += 0;
		$pvm = $vv . "-" . $kk . "-" . $pp;

		if (!checkdate($kk, $pp, $vv)) {
			echo "<font class='error'>".t("Virheellinen pvm")."</font><br>";
			$tee = '';
		}
	}

	if ($tee != '' and $ytunnus != '') {	// Valitaan asiakas, jos sitä ei tiedetä

		$muutparametrit = $laji . "/" . $vv . "/" . $kk . "/" . $pp . "/" . $tee . "/" . $maksuehto;

		if (substr($laji,0,1) == 'M') {
			include "asiakashaku.inc";
			if ($asiakasid == '') $tee = '';
		}

		if (substr($laji,0,1) == 'O') {
			include "kevyt_toimittajahaku.inc";
			if ($toimittajaid == '') $tee = '';
		}
	}

	if ($tee == '') {
		// mikä kuu/vuosi nyt on
		$year = date("Y");
		$kuu  = date("n");

		// poimitaan erikseen edellisen kuun viimeisen päivän vv,kk,pp raportin oletuspäivämääräksi
		if (!isset($vv)) $vv = date("Y",mktime(0,0,0,$kuu-1,1,$year));
		if (!isset($kk)) $kk = date("n",mktime(0,0,0,$kuu-1,1,$year));
		if (!isset($pp)) $pp = date("j",mktime(0,0,0,$kuu,0,$year));

		$sel[$laji] = " selected ";
		
		echo "<form name = 'valinta' action = 'raportit.php?tee=X' method='post'>
				<input type = 'hidden' name = 'toim' value = 'avoimet'>
				<table>
				<tr><th>".t("Anna päivämäärä, muodossa pp-kk-vvvv:")."</th>
				<td><input type = 'text' name = 'pp' value='$pp' size=2></td>
				<td><input type = 'text' name = 'kk' value='$kk' size=2></td>
				<td><input type = 'text' name = 'vv' value='$vv' size=4></td></tr>
				<th>".t("Mitkä laskut listataan:")."</th>
				<td colspan = '3'><select name='laji'>
				<option value='M'  $sel[M]>".t("myyntilaskut")."
				<option value='MF' $sel[MF]>".t("myyntilaskut factoring")."
				<option value='MK' $sel[MK]>".t("myyntilaskut konserni")."
				<option value='O'  $sel[O]>".t("ostolaskut")."
				<option value='OK' $sel[OK]>".t("ostolaskut konserni")."
				</select></td>";
		
		echo "<tr><th>".t("Maksuehto").":</th>";
		echo "<td colspan='3'><select name='maksuehto'>";
		echo "<option value=''>".t("Ei valintaa")."";

		$query = "	SELECT maksuehto.tunnus, concat_ws(' ', ".avain('selectcon','MEHTOKATXT_').",  ".avain('selectcon2','MEHTOTXT_').") mehto
					FROM maksuehto
					".avain('join','MEHTOKATXT_')."
					".avain('join2','MEHTOTXT_')."
					WHERE maksuehto.yhtio = '$kukarow[yhtio]' 
					and (maksuehto.sallitut_maat = '' or maksuehto.sallitut_maat like '%$srow[maa]%')
					order by maksuehto.jarjestys, mehto, maksuehto.tunnus";
		$mresult = mysql_query($query) or pupe_error($query);

		while($row=mysql_fetch_array($mresult)) {
			$sel = "";
			if ($row[0] == $maksuehto) {
				$sel = 'selected';
			}
			echo "<option value='$row[0]' $sel>$row[1]";
		}
		echo "</select></td>";
		
		echo "<tr><th>".t("Näytä laskut, joilta puuttuu kaikki tiliöinnit")."	</th>
				<td colspan='3'><input type = 'checkbox' name = 'rikki'></td></tr>
				<tr><th>".t("Vain toimittaja/asiakas")."	</th>
				<td colspan='3'><input type = 'text' name = 'ytunnus' value='$ytunnus'></td></tr>
				</tr><td class='back'></td><td colspan='3' class='back'><input type = 'submit' value = '".t("Valitse")."'></td>
				</tr>
				</table>
				</form>";
		$formi = 'valinta';
		$kentta = 'pp';
	}
	else {

		switch ($laji) {
			case 'M' :
				$lista = 'myyntisaamiset';
				break;
			case 'MF' :
				$lista = 'factoringmyyntisaamisett';
				break;
			case 'MK' :
				$lista = 'konsernimyyntisaamiset';
				break;
			case 'O' :
				$lista = 'ostovelat';
				break;
			case 'OK' :
				$lista = 'konserniostovelat';
				break;
		}

		if (substr($laji,0,1) == 'O') {

			$lisa = '';

			if ($toimittajaid != '')  {
				$lisa = " and liitostunnus='$toimittajaid'";
				$indeksi = " use index (yhtio_tila_liitostunnus_tapvm)";
			}
			else {
				$indeksi = " use index (yhtio_tila_tapvm)";
			}

			if ($laji=='OK') $tili=$yhtiorow['konserniostovelat'];
			else $tili=$yhtiorow['ostovelat'];

			$query = "	SELECT ytunnus, nimi, concat_ws(' ', viite, viesti) maksutieto, lasku.tapvm tapvm, mapvm, olmapvm, erpcm, tiliointi.summa * -1 avoinsaldo
						FROM lasku use index (yhtio_tila_tapvm)
						LEFT JOIN tiliointi use index (tositerivit_index) ON lasku.yhtio=tiliointi.yhtio and lasku.tunnus=tiliointi.ltunnus and lasku.tapvm=tiliointi.tapvm and tilino='$tili' and korjattu=''
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and (mapvm > '$pvm' or mapvm='0000-00-00')
						and lasku.tapvm <= '$pvm'
						and tila in ('H', 'Y', 'M', 'P', 'Q')
						$lisa
						ORDER BY ytunnus";
			$result = mysql_query($query) or pupe_error($query);

			if ($ytunnus != '') echo "<font class='message'>".t("Avoimet laskut")."</font><br>";
			// poimitaan kuluva päivä, raportin timestampille

			$today = date("Y-m-d");

			if ($ytunnus == '') echo "<br><font class=head>".t("Avoimet")." $lista</font><font class='message'>".t("Raportti otettu")." $today.</font><br>

			<font class='message'>".t("Yrityksellä")." $yhtiorow[nimi] ".t("oli")." $pvm ".t("seuraavat avoimet")." $lista:</font><br>";

			echo "<table>";
			echo "<tr>";
			for ($i = 0; $i < mysql_num_fields($result); $i++) {
				echo "<th>" . t(mysql_field_name($result,$i))."</th>";
			}
			echo "</tr>";

			$summa = 0;

			while ($trow=mysql_fetch_array ($result)) {
				if ((($rikki == 'on') and ($trow['avoinsaldo']==0)) or ($trow['avoinsaldo']!=0)) {
					echo "<tr>";
					$summa += $trow['avoinsaldo'];
					for ($i=0; $i<mysql_num_fields($result); $i++) {
						if (($i == 4) and ($trow[$i] == '0000-00-00')) $trow[$i]='';
						if ($i == 7) {
		                  $trow[$i] = str_replace('.',',',$trow[$i]);
		                  echo "<td align='right'>$trow[$i]</td>";
		                }
						else 
						  echo "<td>$trow[$i]</td>";
					}
					echo "</tr>";
				}
			}
	        $query = "	SELECT sum(summa) saldo FROM tiliointi
						WHERE yhtio = '$kukarow[yhtio]' and korjattu = '' and tapvm >= '$yhtiorow[tilikausi_alku]' and tapvm <= '$pvm'
						and tilino='$tili' and korjattu = ''";
			$result = mysql_query($query) or pupe_error($query);
			$trow = mysql_fetch_array ($result);

			$erotus=$trow['saldo']+$summa;
			$summa = sprintf("%01.2f", $summa);
			$summa = str_replace('.',',',$summa);
			echo "<tr><th>".t("Avoimet yhteensä").":</th><th colspan='6'></th><td align='right'>$summa</td></tr>";
			$summa = sprintf("%01.2f", $trow['saldo']);
			$summa = str_replace('.',',',$summa);
			echo "<tr><th>".t("Kirjanpito yhteensä").":</th><th colspan='6'></th><td align='right'>$summa</td></tr>";
			$summa = sprintf("%01.2f", $erotus);
			$summa = str_replace('.',',',$summa);
			echo "<tr><th>".t("Ero").":</th><th colspan='6'></th><td align='right'>$summa</td></tr>";
		}
		else {
			$lisa =  "";
			$lisa2 = "";
			$lisa3 = "";

			if ($asiakasid != '')  {
				$lisa = "and liitostunnus='$asiakasid'";
				$lisa2 = "and asiakas_tunnus='$asiakasid'";
				$indeksi = " use index (yhtio_tila_liitostunnus_tapvm)";
			}
			else {
				$indeksi = " use index (yhtio_tila_mapvm)";
			}
			
			if ($maksuehto != '') {
				$lisa3 = " and lasku.maksuehto = '$maksuehto' ";
			}

			$tili=0;

			if ($laji == 'MK') $tili = $yhtiorow['konsernimyyntisaamiset'];
			elseif ($laji == 'MF') $tili = $yhtiorow['factoringsaamiset'];
			else $tili = $yhtiorow['myyntisaamiset'];

			$query = "	SELECT ytunnus, nimi, laskunro maksutieto, lasku.tapvm tapvm,
						mapvm, erpcm, sum(tiliointi.summa) avoinsaldo, sum(if(tiliointi.tapvm=lasku.tapvm, tiliointi.summa, 0)) alkusaldo, lasku.summa laskunsumma
						FROM lasku $indeksi
						LEFT JOIN tiliointi use index (tositerivit_index) on lasku.yhtio=tiliointi.yhtio
						and lasku.tunnus=tiliointi.ltunnus
						and tilino='$tili'
						and korjattu=''
						and tiliointi.tapvm <= '$pvm'
				  		WHERE lasku.yhtio = '$kukarow[yhtio]'
						and (mapvm > '$pvm' or mapvm='0000-00-00')
						and lasku.tapvm <= '$pvm'
						and tila 	= 'U'
						and alatila	= 'X'
						$lisa
						$lisa3
						GROUP BY 1,3
						ORDER BY 1,3";

			$result = mysql_query($query) or pupe_error($query);

			if ($ytunnus != '') echo "<font class='message'>".t("Avoimet laskut")."</font>";
			// poimitaan kuluva päivä, raportin timestampille
			$today = date("Y-m-d");
			echo "<font class='message'>".t("Raportti otettu")." $today.</font><br>";

			if ($ytunnus == '')
				echo "<font class=head>".t("Avoimet")." $lista</font><br><font class='message'>".t("Yrityksellä")." $yhtiorow[nimi] ".t("oli")." $pvm ".t("seuraavat avoimet")." $lista:</font><br>";

			echo "<table><tr>";

			for ($i = 0; $i < mysql_num_fields($result)-2; $i++) {
				echo "<th>" . t(mysql_field_name($result,$i))."</th>";
			}
			echo "</tr>";

			$summa = 0;
			$kirjanpito = 0;

			while ($trow = mysql_fetch_array ($result)) {
				$erotinal = '<td>';
				$erotinlo = '</td>';
				$erotinaloikea = "<td align='right'>";
				if ($trow['alkusaldo'] == 0 and $rikki == 'on') {
					$erotinal = '<th>';
					$erotinlo = '</th>';
					$erotinaloikea = "<th align='right'>";
				}

				if (($rikki == 'on' and $trow['alkusaldo'] == 0) or ($trow['alkusaldo'] != 0)) {

					if ($trow['alkusaldo'] == 0) { // Tämä on tullut konversiosta ja siltä puuttuu laskun myyntisaatavat vienti
							$trow[6] = $trow['laskunsumma'] + $trow['avoinsaldo'];
							$trow['avoinsaldo'] = $trow['laskunsumma'] + $trow['avoinsaldo'];
					}

					$summa += $trow['avoinsaldo'];

					echo "<tr>";
					for ($i=0; $i<mysql_num_fields($result)-2; $i++) {
						if (($i == 4) and ($trow[$i] == '0000-00-00')) $trow[$i]='';
						if (($i == 6) or ($i == 7)) {
		                  $trow[$i] = str_replace('.',',',$trow[$i]);
		                  echo $erotinaloikea.$trow[$i].$erotinlo;
		                }
						else
						  echo $erotinal.$trow[$i].$erotinlo;
					}
					echo "</tr>";
				}
			}

			//Listataan vielä kohdistamattomat
			$query = "	SELECT asiakas.ytunnus, nimi_maksaja, viite, kirjpvm,
						kohdpvm, '',  tiliointi.summa avoinsaldo, suoritus.summa
				  		FROM suoritus
				  		LEFT JOIN asiakas on suoritus.yhtio=asiakas.yhtio and suoritus.asiakas_tunnus=asiakas.tunnus
				  		LEFT JOIN tiliointi on tiliointi.tunnus = suoritus.ltunnus and tiliointi.tilino='$tili'
				  		WHERE suoritus.yhtio = '$kukarow[yhtio]' and kirjpvm <= '$pvm' and kohdpvm='0000-00-00'
						$lisa2
				  		ORDER BY ytunnus";

			$result = mysql_query($query) or pupe_error($query);

			while ($trow = mysql_fetch_array ($result)) {

				if ($trow['avoinsaldo'] != 0) {
					$summa += $trow["avoinsaldo"];

					echo "<tr>";
					for ($i=0; $i<mysql_num_fields($result)-1; $i++) {
						if (($i == 4) and ($trow[$i] == '0000-00-00')) $trow[$i]='';
						if ($i == 6) {
							$trow[$i] = str_replace('.',',',$trow[$i]."*");
							echo "<td align='right'>$trow[$i]</td>";
		                }
		                else
							echo "<td>$trow[$i]</td>";
					}
					echo "</tr>";
				}
			}

	        $query = "	SELECT sum(summa) saldo FROM tiliointi
						WHERE yhtio = '$kukarow[yhtio]' and korjattu = '' and tapvm >= '$yhtiorow[tilikausi_alku]' and tapvm <= '$pvm'
						and tilino='$tili' and korjattu = ''";
			$result = mysql_query($query) or pupe_error($query);
			$trow = mysql_fetch_array ($result);

			$erotus=$trow['saldo']-$summa;
			$summa = sprintf("%01.2f", $summa);
	        $summa = str_replace('.',',',$summa);
	        echo "<tr><th>".t("Avoimet yhteensä").":</th><th colspan='5'></th><td align='right'>$summa</td></tr>";
			$summa = sprintf("%01.2f", $trow['saldo']);
        	$summa = str_replace('.',',',$summa);
			echo "<tr><th>".t("Kirjanpito yhteensä").":</th><th colspan='5'></th><td align='right'>$summa</td></tr>";
			$summa = sprintf("%01.2f", $erotus);
        	$summa = str_replace('.',',',$summa);
			echo "<tr><th>".t("Ero").":</th><th colspan='5'></th><td align='right'>$summa</td></tr>";
		}
		$toim="";
		echo "</table>";
	}
?>
