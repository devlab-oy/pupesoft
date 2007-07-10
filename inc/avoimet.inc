<?php
	if ($muutparametrit != '') list($laji, $vv, $kk, $pp ,$tee) = split("/",$muutparametrit);

	echo "<font class=head>".t("Avoimet laskut")."</font><hr>";

	if ($tee != '') {	// Tarkistetaan pvm
		// muutetaan numeroiksi
		$kk += 0;
		$pp += 0;
		$vv += 0;
		$pvm = sprintf("%04d", $vv) . "-" . sprintf("%02d", $kk) . "-" . sprintf("%02d", $pp);

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
        $query = "SELECT distinct valkoodi from lasku";
        
        
		echo "<tr><th>".t("Näytä laskut, joilta puuttuu kaikki tiliöinnit")."	</th>
				<td colspan='3'><input type = 'checkbox' name = 'rikki'></td></tr>";
				
		$query = "	SELECT nimi, tunnus
		 	                FROM valuu
		 	             	WHERE yhtio = '$kukarow[yhtio]'
		 	               	ORDER BY jarjestys";
		 		$vresult = mysql_query($query) or pupe_error($query);

		 		echo "<tr><th>" . t('Näytä vain valuutassa') .":</th><td colspan=3><select name='savalkoodi'>";
		 		echo "<option value = ''>".t("Kaikki")."</option>";


		 		while ($vrow = mysql_fetch_array($vresult)) {
		 			$sel="";
		 			if (strtoupper($vrow['nimi']) == strtoupper($savalkoodi)) {
		 				$sel = "selected";
		 			}

		 			echo "<option value = '$vrow[nimi]' $sel>$vrow[nimi]</option>";
		 		}

		 		echo "</select></td></tr>";
    	
		$sel1 = '';

		if ($valuutassako == 'V') {
			$sel1 = "SELECTED";
		}
		
		echo "<tr><th>".t("Summat valuutassa").":</th>";
		echo "<td colspan=3><select name='valuutassako'>";
		echo "<option value = ''>".t("Yrityksen valuutassa")."</option>";
		echo "<option value = 'V' $sel1>".t("Laskun valuutassa")."</option>";
		echo "</select></td></tr>";

		echo "<tr><th>" . t('Maa'). ":</th><td colspan=3><select name='maa'>";
		
		$query = "select distinct maa from asiakas where yhtio='{$kukarow['yhtio']}'";
		$res = mysql_query($query) or pupe_error($query);
		
		echo "<option value=''>" . t('Kaikki'). "</option>";
		
		while ($rowmaa = mysql_fetch_array($res)) {
			echo "<option value='{$rowmaa['maa']}'>".maa($rowmaa['maa'])."</option>";
		}
		
		echo "</td></tr>";
		
		echo "</td></tr>
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
				$lista = 'factoringmyyntisaamiset';
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

			if ($laji=='OK') 	$tili=$yhtiorow['konserniostovelat'];
			else 				$tili=$yhtiorow['ostovelat'];

			$query = "	SELECT lasku.liitostunnus, ytunnus, nimi, concat_ws(' ', viite, viesti) maksutieto, lasku.tapvm tapvm, mapvm, olmapvm, erpcm, tiliointi.summa * -1 avoinsaldo
						FROM lasku use index (yhtio_tila_tapvm)
						JOIN tiliointi use index (tositerivit_index) ON lasku.yhtio=tiliointi.yhtio
						and lasku.tunnus	= tiliointi.ltunnus
						and lasku.tapvm		= tiliointi.tapvm
						and tilino			= '$tili'
						and korjattu		= ''
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and (mapvm > '$pvm' or mapvm='0000-00-00')
						and lasku.tapvm <= '$pvm'
						and tila in ('H','Y','M','P','Q')
						$lisa
						ORDER BY ytunnus";
			$result = mysql_query($query) or pupe_error($query);

			// poimitaan kuluva päivä, raportin timestampille
			$today = date("Y-m-d");

			echo "<font class='message'>".t("Avoimet")." $lista</font><br><font class='message'>".t("Raportti otettu")." ".tv1dateconv($today)."</font><br>";

			if ($toimittajaid == '') {
				echo "<font class='message'>".t("Yrityksellä")." $yhtiorow[nimi] ".t("oli")." ".tv1dateconv($pvm)." ".t("seuraavat avoimet")." $lista:</font><br>";
			}

			echo "<table>";
			echo "<tr>";
			echo "<th>".t("Ytunnus")."</th>";
			echo "<th>".t("Nimi")."</th>";
			echo "<th>".t("Maksutieto")."</th>";
			echo "<th>".t("Pvm")."</th>";
			echo "<th>".t("Maksupäivä")."</th>";
			echo "<th>".t("Oletusmaksupäivä")."</th>";
			echo "<th>".t("Eräpäivä")."</th>";
			echo "<th>".t("Avoin saldo")."</th>";
			echo "</tr>";

			$summa = 0;

			while ($trow=mysql_fetch_array ($result)) {
				if (($rikki == 'on' and $trow['avoinsaldo']==0) or $trow['avoinsaldo']!=0) {

					$summa += $trow['avoinsaldo'];

					echo "<tr>";
					echo "<td><a href='../myyntires/myyntilaskut_asiakasraportti.php?tunnus=$row[liitostunnus]&tila=tee_raportti'>$trow[ytunnus]</a></td>";
					echo "<td>$trow[nimi]</td>";
					echo "<td>$trow[maksutieto]</td>";
					echo "<td>".tv1dateconv($trow["tapvm"])."</td>";

					if ($trow["mapvm"] != '0000-00-00') echo "<td>".tv1dateconv($trow["mapvm"])."</td>";
					else echo "<td></td>";

					echo "<td>".tv1dateconv($trow["olmapvm"])."</td>";
					echo "<td>".tv1dateconv($trow["erpcm"])."</td>";
					echo "<td align='right'>$trow[avoinsaldo]</td>";
					echo "</tr>";
				}
			}
	        $query = "	SELECT sum(summa) saldo FROM tiliointi
						WHERE yhtio = '$kukarow[yhtio]'
						and korjattu = ''
						and tapvm >= '$yhtiorow[tilikausi_alku]'
						and tapvm <= '$pvm'
						and tilino = '$tili'";
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
			$lisa1 =  "";
			$lisa2 = "";
			$lisa3 = "";

			if ($asiakasid != '')  {
				$lisa1 = " and liitostunnus='$asiakasid' ";
				$lisa2 = " and asiakas_tunnus='$asiakasid' ";
				$indeksi = " use index (yhtio_tila_liitostunnus_tapvm)";
			}
			else {
				$indeksi = " use index (yhtio_tila_mapvm) ";
			}

			if ($maksuehto != '') {
				$lisa3 = " and lasku.maksuehto = '$maksuehto' ";
			}
			
			$valkoodi = '';
			$valkoodi2 = '';
			
			if (isset($_POST['savalkoodi']) && $_POST['savalkoodi'] != '') {
				$valkoodi = " and lasku.valkoodi = '{$_POST['savalkoodi']}'";
				$valkoodi2 = " and suoritus.valkoodi = '{$_POST['savalkoodi']}'";
			}
			
			$maa = '';
			if (isset($_POST['maa']) && $_POST['maa'] != '') {
				$maa = " and lasku.maa = '{$_POST['maa']}'";
			}
			
			$tili=0;

			if ($laji == 'MK') 		$tili = $yhtiorow['konsernimyyntisaamiset'];
			elseif ($laji == 'MF') 	$tili = $yhtiorow['factoringsaamiset'];
			else 					$tili = $yhtiorow['myyntisaamiset'];

			$query = "	SELECT lasku.*, lasku.laskunro maksutieto, lasku.tapvm tapvm,
						lasku.mapvm, lasku.erpcm,
						lasku.summa-lasku.saldo_maksettu avoinsaldo2, lasku.summa_valuutassa-lasku.saldo_maksettu_valuutassa avoinsaldo3,
						lasku.tunnus laskutunnus,
						sum(tiliointi.summa) avoinsaldo, 
						sum(if(tiliointi.tapvm=lasku.tapvm, tiliointi.summa, 0)) alkusaldo
						FROM lasku $indeksi
						JOIN tiliointi use index (tositerivit_index) ON lasku.yhtio=tiliointi.yhtio
						and lasku.tunnus		 = tiliointi.ltunnus
						and tiliointi.tilino	 = '$tili'
						and tiliointi.korjattu	 = ''
						and tiliointi.tapvm	 	<= '$pvm'
				  		WHERE lasku.yhtio 	= '$kukarow[yhtio]'
						and (lasku.mapvm > '$pvm' or lasku.mapvm='0000-00-00')
						and lasku.tapvm    <= '$pvm'
						and lasku.tila 		= 'U'
						and lasku.alatila	= 'X'
						$lisa1
						$lisa3
						$valkoodi
						$maa
						GROUP BY lasku.liitostunnus, lasku.ytunnus, lasku.nimi, lasku.laskunro, lasku.tapvm, lasku.mapvm, lasku.erpcm, lasku.summa, avoinsaldo2
						ORDER BY lasku.ytunnus, lasku.laskunro";
			$result = mysql_query($query) or pupe_error($query);

			// poimitaan kuluva päivä, raportin timestampille
			$today = date("Y-m-d");

			echo "<font class='message'>".t("Avoimet")." $lista</font><br><font class='message'>".t("Raportti otettu")." ".tv1dateconv($today)."</font><br>";

			if ($toimittajaid == '') {
				echo "<font class='message'>".t("Yrityksellä")." $yhtiorow[nimi] ".t("oli")." ".tv1dateconv($pvm)." ".t("seuraavat avoimet")." $lista:</font><br>";
			}

			echo "<table>";
			echo "<tr>";
			echo "<th>".t("Ytunnus")."</th>";
			echo "<th>".t("Nimi")."</th>";
			echo "<th>".t("Laskunro")."</th>";
			echo "<th>".t("Pvm")."</th>";
			echo "<th>".t("Maksupäivä")."</th>";
			echo "<th>".t("Eräpäivä")."</th>";
			echo "<th>".t("Avoin saldo")."</th>";
			echo "</tr>";

			$summa 		= 0;
			$kirjanpito = 0;

			while ($trow = mysql_fetch_array ($result)) {
				
				if ($trow['alkusaldo'] == 0 and $rikki == 'on') {
					$class = " class='spec' ";
				}
				else {
					$class = "";
				}

				if (($rikki == 'on' and $trow['alkusaldo'] == 0) or $trow['alkusaldo'] != 0) {

					if ($trow['alkusaldo'] == 0) {
						// Tämä on tullut konversiosta ja siltä puuttuu laskun myyntisaatavat vienti
						$trow['avoinsaldo'] = $trow['summa'] + $trow['avoinsaldo'];
					} 

					$summa += $trow['avoinsaldo'];

					echo "<tr>";
					echo "<td $class><a href='myyntires/myyntilaskut_asiakasraportti.php?tunnus=$trow[liitostunnus]&tila=tee_raportti'>$trow[ytunnus]</a></td>";
					echo "<td $class>$trow[nimi]</td>";
					echo "<td $class>$trow[maksutieto]</td>";
					echo "<td $class>".tv1dateconv($trow["tapvm"])."</td>";

					if ($trow["mapvm"] != '0000-00-00') echo "<td $class>".tv1dateconv($trow["mapvm"])."</td>";
					else echo "<td $class></td>";

					echo "<td $class>".tv1dateconv($trow["erpcm"])."</td>";
					
					if ($valuutassako != 'V' or $trow['avoinsaldo3'] == 0) {
						echo "<td align='right' $class>{$trow['avoinsaldo2']} {$yhtiorow['valkoodi']}</td>";
						
					} else {
						echo "<td align='right' $class>{$trow['avoinsaldo3']} {$trow['valkoodi']}</td>";
					}

					if ($trow["avoinsaldo"] != $trow["avoinsaldo2"]) echo "<td class='back'><font class='error'>VIRHE: Lasku / Kirjanpito summat heittävät!</font></td><td class='back' nowrap> ($trow[avoinsaldo2] / <a href='muutosite.php?tee=E&tunnus=$trow[laskutunnus]'>$trow[avoinsaldo]</a>)</td>";

					echo "</tr>";
				}
			}

			//Listataan vielä kohdistamattomat
			$query = "	SELECT asiakas.tunnus, asiakas.ytunnus, nimi_maksaja, viite, kirjpvm, '', '',  tiliointi.summa avoinsaldo, round(suoritus.summa*if(suoritus.kurssi=0, 1, kurssi),2)*-1 avoinsaldo2, tiliointi.ltunnus, suoritus.summa, suoritus.valkoodi
				  		FROM suoritus
						JOIN tiliointi on tiliointi.tunnus = suoritus.ltunnus and tiliointi.tilino='$tili' and tiliointi.korjattu = ''
				  		LEFT JOIN asiakas on suoritus.yhtio=asiakas.yhtio and suoritus.asiakas_tunnus=asiakas.tunnus
				  		WHERE suoritus.yhtio = '$kukarow[yhtio]'
						and kirjpvm <= '$pvm'
						and kohdpvm  = '0000-00-00'
						$lisa2
						$valkoodi2
				  		ORDER BY ytunnus";
			$result = mysql_query($query) or pupe_error($query);

			while ($trow = mysql_fetch_array ($result)) {

					$summa += $trow["avoinsaldo"];

					echo "<tr>";
					echo "<td><a href='myyntires/myyntilaskut_asiakasraportti.php?tunnus=$trow[tunnus]&tila=tee_raportti'>$trow[ytunnus]</a></td>";
					echo "<td>$trow[nimi_maksaja]</td>";
					echo "<td>$trow[viite]</td>";
					echo "<td>".tv1dateconv($trow["kirjpvm"])."</td>";
					echo "<td></td>";
					echo "<td></td>";
					
					if ($valuutassako != 'V') {
						echo "<td align='right'><font class='error'>{$trow['summa']} {$trow['valkoodi']}</font></td>";
					} else {
						echo "<td align='right'><font class='error'>{$trow['avoinsaldo']} {$yhtiorow['valkoodi']}</font></td>";
					}


					if ($trow["avoinsaldo"] != $trow["avoinsaldo2"]) echo "<td class='back'><font class='error'>VIRHE: Kirjanpito / Suoritus summat heittävät!</font></td><td class='back' nowrap> (<a href='muutosite.php?tee=E&tunnus=$trow[ltunnus]'>$trow[avoinsaldo]</a> / $trow[avoinsaldo2])</td>";

					echo "</tr>";

			}

	        $query = "	SELECT sum(summa) saldo
						FROM tiliointi
						WHERE yhtio = '$kukarow[yhtio]'
						and korjattu = ''
						and tapvm >= '$yhtiorow[tilikausi_alku]'
						and tapvm <= '$pvm'
						and tilino ='$tili'";
			$result = mysql_query($query) or pupe_error($query);
			$trow = mysql_fetch_array ($result);

			$erotus=$trow['saldo']-$summa;

			$summa = sprintf("%01.2f", $summa);
			$summa = str_replace('.',',',$summa);
	        echo "<tr><th>".t("Avoimet yhteensä").":</th><th colspan='5'></th><td align='right'>$summa {$yhtiorow['valkoodi']}</td></tr>";

			$summa = sprintf("%01.2f", $trow['saldo']);
        	$summa = str_replace('.',',',$summa);
			echo "<tr><th>".t("Kirjanpito yhteensä").":</th><th colspan='5'></th><td align='right'>$summa {$yhtiorow['valkoodi']}</td></tr>";

			$summa = sprintf("%01.2f", $erotus);
        	$summa = str_replace('.',',',$summa);
			echo "<tr><th>".t("Ero").":</th><th colspan='5'></th><td align='right'>$summa {$yhtiorow['valkoodi']}</td></tr>";
		}
		$toim="";
		echo "</table>";
	}
?>
