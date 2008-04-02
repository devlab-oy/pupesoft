<?php
	
	require("inc/parametrit.inc");

	if (function_exists("js_popup")) {
		echo js_popup(-100);
	}

	if ($tee == 'NAYTATILAUS') {
		echo "<font class='head'>".t("Tilaus")." $tunnus:</font><hr>";
		require ("raportit/naytatilaus.inc");
		echo "<br><br><br>";
		$tee = "Z";
	}

	if (($tee == 'N') or ($tee == 'E')) {
		if ($tee == 'N') {
			$oper='>';
			$suun='';
		}
		else {
			$oper='<';
			$suun='desc';
		}

		$query = "	SELECT tuote.tuoteno, tuote.status, sum(tuotepaikat.saldo) saldo
					FROM tuote use index (tuoteno_index)
					LEFT JOIN tuotepaikat ON tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.yhtio=tuote.yhtio
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					and tuote.tuoteno " . $oper . " '$tuoteno'
					GROUP BY 1,2
					HAVING tuote.status not in ('P','X') or saldo > 0
					ORDER BY tuote.tuoteno " . $suun . "
					LIMIT 1";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {
			$trow = mysql_fetch_array ($result);
			$tuoteno = $trow['tuoteno'];
			$tee='Z';
		}
		else {
			$varaosavirhe = t("Yhtään tuotetta ei löytynyt")."!";
			$tuoteno = '';
			$tee='Y';
		}
	}

	echo "<font class='head'>".t("Tuotekysely")."</font><hr>";

	if (($tee == 'Z') and ($tyyppi == '')) {
		require "inc/tuotehaku.inc";
	}
	if (($tee == 'Z') and ($tyyppi != '')) {

		if ($tyyppi == 'TOIMTUOTENO') {

			$query = "	SELECT tuotteen_toimittajat.tuoteno, tuote.status, sum(tuotepaikat.saldo) saldo
						FROM tuotteen_toimittajat
						JOIN tuote ON tuote.yhtio=tuotteen_toimittajat.yhtio and tuote.tuoteno=tuotteen_toimittajat.tuoteno
						LEFT JOIN tuotepaikat ON tuotepaikat.yhtio=tuotteen_toimittajat.yhtio and tuotepaikat.tuoteno=tuotteen_toimittajat.tuoteno
						WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
						and tuotteen_toimittajat.toim_tuoteno = '$tuoteno'
						GROUP BY 1,2
						HAVING tuote.status not in ('P','X') or saldo > 0
						ORDER BY tuote.tuoteno";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0) {
				$varaosavirhe = t("VIRHE: Tiedolla ei löytynyt tuotetta")."!";
				$tee = 'Y';
			}
			elseif (mysql_num_rows($result) > 1) {
				$varaosavirhe = t("VIRHE: Tiedolla löytyi useita tuotteita")."!";
				$tee = 'Y';
			}
			else {
				$tr = mysql_fetch_array($result);
				$tuoteno = $tr["tuoteno"];
			}
		}
		elseif ($tyyppi != '') {
			$query = "	SELECT tuoteno
						FROM tuotteen_avainsanat
						WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno' and laji='$tyyppi'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) != 1) {
				$varaosavirhe = t("VIRHE: Tiedolla ei löytynyt tuotetta")."!";
				$tee = 'Y';
			}
			else {
				$tr = mysql_fetch_array($result);
				$tuoteno = $tr["tuoteno"];
			}
		}
	}

	if ($tee=='Y') echo "<font class='error'>$varaosavirhe</font>";

	 //syotetaan tuotenumero
	$formi  = 'formi';
	$kentta = 'tuoteno';

	echo "<table><tr>";;
	echo "<form action='$PHP_SELF' method='post' name='$formi' autocomplete='off'>";
	echo "<input type='hidden' name='tee' value='Z'>";

	echo "<td class='back'><select name='tyyppi'>";
	echo "<option value=''>".t("Tuotenumero").":</option>";
	echo "<option value='TOIMTUOTENO'>".t("Toimittajan tuotenumero").":</option>";

	$query = "	SELECT selite, selitetark
				FROM avainsana
				WHERE laji = 'TUOTEULK' and yhtio = '$kukarow[yhtio]'
				ORDER BY jarjestys";
	$vresult = mysql_query($query) or pupe_error($query);

	while ($vrow = mysql_fetch_array($vresult)) {
		echo "<option value='$vrow[selite]'>$vrow[selitetark]:</option>";
	}

	echo "</select></th>";
	echo "<td class='back'><input type='text' name='tuoteno' value=''></td>";
	echo "<td class='back'><input type='Submit' value='".t("Valitse")."'></td>";
	echo "</form>";

	//Jos ei haettu, annetaan 'edellinen' & 'seuraava'-nappi
	if (($ulos=='') and ($tee=='Z')) {
		echo "<form action='$PHP_SELF' method='post'>";
		echo "<input type='hidden' name='tee' value='E'>";
		echo "<input type='hidden' name='tyyppi' value='$tyyppi'>";
		echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";
		echo "<td class='back'>";
		echo "<input type='Submit' value='".t("Edellinen")."'>";
		echo "</td>";
		echo "</form>";

		echo "<form action='$PHP_SELF' method='post'>";
		echo "<input type='hidden' name='tyyppi' value='$tyyppi'>";
		echo "<input type='hidden' name='tee' value='N'>";
		echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";
		echo "<td class='back'>";
		echo "<input type='Submit' value='".t("Seuraava")."'>";
		echo "</td>";
		echo "</form>";
	}
	echo "</tr></table><br>";



	//tuotteen varastostatus
	if ($tee == 'Z') {
		
		echo "<font class='message'>".t("Tuotetiedot")."</font><hr>";
		
		$query = "	SELECT tuote.*, date_format(tuote.muutospvm, '%Y-%m-%d') muutos, date_format(tuote.luontiaika, '%Y-%m-%d') luonti,
					group_concat(distinct concat(tuotteen_toimittajat.toimittaja, ' ', toimi.nimi) order by tuotteen_toimittajat.tunnus separator '<br>') toimittaja,
					group_concat(distinct tuotteen_toimittajat.osto_era order by tuotteen_toimittajat.tunnus separator '<br>') osto_era,
					group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') toim_tuoteno,
					group_concat(distinct tuotteen_toimittajat.tuotekerroin order by tuotteen_toimittajat.tunnus separator '<br>') tuotekerroin,
					group_concat(distinct tuotteen_toimittajat.alkuperamaa order by tuotteen_toimittajat.tunnus) alkuperamaa,
					group_concat(distinct concat_ws(' ',tuotteen_toimittajat.ostohinta,upper(tuotteen_toimittajat.valuutta), '/',tuotteen_toimittajat.alennus, '%') order by tuotteen_toimittajat.tunnus separator '<br>') ostohinta
					FROM tuote
					LEFT JOIN tuotteen_toimittajat ON (tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno)
					LEFT JOIN toimi on (toimi.yhtio = tuote.yhtio and toimi.tunnus = tuotteen_toimittajat.liitostunnus)
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					and tuote.tuoteno = '$tuoteno'
					GROUP BY tuote.tuoteno";
		$result = mysql_query($query) or pupe_error($query);

		$query = "	SELECT sum(saldo) saldo 
					from tuotepaikat  
					where tuoteno	= '$tuoteno' 
					and yhtio		= '$kukarow[yhtio]'";
		$salre = mysql_query($query) or pupe_error($query);
		$salro = mysql_fetch_array($salre);

		if (mysql_num_rows($result) == 1) {
			$tuoterow = mysql_fetch_array($result);
		}
		else {
			$tuoterow = array();
		}

		if ($tuoterow["tuoteno"] != "" and (!in_array($tuoterow["status"], array('P', 'X')) or $salro["saldo"] != 0)) {
			
			if ($yhtiorow["saldo_kasittely"] == "T") {
				$saldoaikalisa = date("Y-m-d");
			}
			else {
				$saldoaikalisa = "";
			}
					
			// Jos tuote on sarjanumeroseurannassa niin kehahinta lasketaan yksilöiden ostohinnoista (ostetut yksilöt jotka eivät vielä ole myyty(=laskutettu))
			if ($tuoterow["sarjanumeroseuranta"] == "S" or $tuoterow["sarjanumeroseuranta"] == "U") {
				$query	= "	SELECT sarjanumeroseuranta.tunnus
							FROM sarjanumeroseuranta
							LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
							LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
							WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
							and sarjanumeroseuranta.tuoteno = '$tuoterow[tuoteno]'
							and sarjanumeroseuranta.myyntirivitunnus != -1
							and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00')
							and tilausrivi_osto.laskutettuaika != '0000-00-00'";
				$sarjares = mysql_query($query) or pupe_error($query);
				
				$kehahin = 0;
				
				while($sarjarow = mysql_fetch_array($sarjares)) {																	
					$kehahin += sarjanumeron_ostohinta("tunnus", $sarjarow["tunnus"]);
				}
									
				$tuoterow['kehahin'] = sprintf('%.6f', ($kehahin / mysql_num_rows($sarjares)));
			}
			
			if 		($tuoterow['epakurantti100pvm'] != '0000-00-00') $tuoterow['kehahin'] = 0;
			elseif 	($tuoterow['epakurantti75pvm'] != '0000-00-00') $tuoterow['kehahin'] = round($tuoterow['kehahin'] * 0.25, 6);
			elseif 	($tuoterow['epakurantti50pvm'] != '0000-00-00') $tuoterow['kehahin'] = round($tuoterow['kehahin'] * 0.5,  6);
			elseif 	($tuoterow['epakurantti25pvm'] != '0000-00-00') $tuoterow['kehahin'] = round($tuoterow['kehahin'] * 0.75, 6);
			

			// Hinnastoon
			if (strtoupper($tuoterow['hinnastoon']) == 'E') {
			 	$tuoterow['hinnastoon'] = "<font style='color:FF0000'>".t("Ei")."</font>";
			}
			else {
				$tuoterow['hinnastoon'] = "<font style='color:00FF00'>".t("Kyllä")."</font>";
			}
			
			// Varastoon
			if (strtoupper($tuoterow['ei_varastoida']) == 'O') {
			 	$tuoterow['ei_varastoida'] = "<font style='color:FF0000'>".t("Ei")."</font>";
			}
			else {
				$tuoterow['ei_varastoida'] = "<font style='color:00FF00'>".t("Kyllä")."</font>";
			}

			//tullinimike
			$cn1 = $tuoterow["tullinimike1"];
			$cn2 = substr($tuoterow["tullinimike1"],0,6);
			$cn3 = substr($tuoterow["tullinimike1"],0,4);

			$query = "select cn, dm, su from tullinimike where cn='$cn1' and kieli = '$yhtiorow[kieli]'";
			$tulliresult1 = mysql_query($query) or pupe_error($query);

			$query = "select cn, dm, su from tullinimike where cn='$cn2' and kieli = '$yhtiorow[kieli]'";
			$tulliresult2 = mysql_query($query) or pupe_error($query);

			$query = "select cn, dm, su from tullinimike where cn='$cn3' and kieli = '$yhtiorow[kieli]'";
			$tulliresult3 = mysql_query($query) or pupe_error($query);

			$tullirow1 = mysql_fetch_array($tulliresult1);
			$tullirow2 = mysql_fetch_array($tulliresult2);
			$tullirow3 = mysql_fetch_array($tulliresult3);

			//perusalennus
			$query  = "select alennus from perusalennus where ryhma='$tuoterow[aleryhma]' and yhtio='$kukarow[yhtio]'";
			$peralresult = mysql_query($query) or pupe_error($query);
			$peralrow = mysql_fetch_array($peralresult);

			$query = "	select distinct valkoodi, maa from hinnasto
						where yhtio = '$kukarow[yhtio]'
						and tuoteno = '$tuoterow[tuoteno]'
						and laji = ''
						order by maa, valkoodi";
			$hintavalresult = mysql_query($query) or pupe_error($query);

			$valuuttalisa = "";

			while ($hintavalrow = mysql_fetch_array($hintavalresult)) {

				// katotaan onko tuotteelle valuuttahintoja
				$query = "	SELECT *
							from hinnasto
							where yhtio = '$kukarow[yhtio]'
							and tuoteno = '$tuoterow[tuoteno]'
							and valkoodi = '$hintavalrow[valkoodi]'
							and maa = '$hintavalrow[maa]'
							and laji = ''
							and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							order by ifnull(to_days(current_date)-to_days(alkupvm),9999999999999)
							limit 1";
				$hintaresult = mysql_query($query) or pupe_error($query);

				while ($hintarow = mysql_fetch_array($hintaresult)) {
					$valuuttalisa .= "<br>$hintarow[maa]: ".sprintf("%.".$yhtiorow['hintapyoristys']."f", $hintarow["hinta"])." $hintarow[valkoodi]";
				}

			}
			
			
			if ($tullirow1['cn'] != '') {
				$alkuperamaat = array();
				$alkuperamaat[] = split(',',$tuoterow['alkuperamaa']);
				$tuorow = $tuoterow;
				$prossat = '';
				$prossa_str = '';
				
				foreach ($alkuperamaat as $alkuperamaa) {
					foreach ($alkuperamaa as $alkupmaa) {					
					
						$laskurow['maa_lahetys'] = $alkupmaa;				    
						
						$mista = 'tuote.php';
						
						include('tilauskasittely/taric_veroperusteet.inc');
												
						$prossa_str = trim($tulliprossa,"0");
						if (strlen($prossa_str) > 1) {
							$prossat .= "<br>".trim($tulliprossa,"0")." ".$alkupmaa;
						}
					}
				}
			}
			
						
			//eka laitetaan tuotteen yleiset (aika staattiset) tiedot
			echo "<table>";

			//1
			echo "<tr><th>".t("Tuotenumero")."<br>".t("Tuotemerkki")."</th><th>".t("Yksikkö")."</th><th>".t("Eankoodi")."</th><th colspan='2'>".t("Nimitys")."</th><th>".t("Hinnastoon")."</th>";
			
			echo "<tr><td>$tuoterow[tuoteno]";
			//haetaan orginaalit	
			if (table_exists("tuotteen_orginaalit")) {		
				$query = "	SELECT * 
							from tuotteen_orginaalit 
							where yhtio = '$kukarow[yhtio]' 
							and tuoteno = '$tuoterow[tuoteno]'";
					$origresult = mysql_query($query) or pupe_error($query);
			
				if (mysql_num_rows($origresult) > 0) {					
				
					$i = 0;
				
					$divit = "<div id='$tuoterow[tuoteno]' class='popup'>";
					$divit .= "<table><tr><td valign='top'><table>";
					$divit .= "<tr><td class='back' valign='top' align='center'>".t("Alkuperäisnumero")."</td><td class='back' valign='top' align='center'>".t("Hinta")."</td><td class='back' valign='top' align='center'>".t("Merkki")."</td></tr>";
				
					while ($origrow = mysql_fetch_array($origresult)) {
						++$i;
						if ($i == 20) {
							$divit .= "</table></td><td valign='top'><table>";
							$divit .= "<tr><td class='back' valign='top' align='center'>".t("Alkuperäisnumero")."</td><td class='back' valign='top' align='center'>".t("Hinta")."</td><td class='back' valign='top' align='center'>".t("Merkki")."</td></tr>";
							$i = 1;
						}
						$divit .= "<tr><td class='back' valign='top'>$origrow[orig_tuoteno]</td><td class='back' valign='top' align='right'>$origrow[orig_hinta]</td><td class='back' valign='top'>$origrow[merkki]</td></tr>";
					}
				
					$divit .= "</table></td></tr>";

					$divit .= "</table>";
					$divit .= "</div>";

					echo "&nbsp;&nbsp;<a src='#' onmouseover=\"popUp(event, '$tuoterow[tuoteno]');\" onmouseout=\"popUp(event, '$tuoterow[tuoteno]');\"><img src='pics/lullacons/info.png' height='13'></a>";
				
				}
			}		
			echo "<br>$tuoterow[tuotemerkki]</td><td>$tuoterow[yksikko]</td><td>$tuoterow[eankoodi]</td><td colspan='2'>".substr(asana('nimitys_',$tuoterow['tuoteno'],$tuoterow['nimitys']),0,100)."</td><td>$tuoterow[hinnastoon]</td></tr>";
			
			//2
			echo "<tr><th>".t("Osasto/try")."</th><th>".t("Toimittaja")."</th><th>".t("Aleryhmä")."</th><th>".t("Tähti")."</th><th>".t("Perusalennus")."</th><th>".t("VAK")."</th></tr>";
			echo "<td>$tuoterow[osasto]/$tuoterow[try]</td><td>$tuoterow[toimittaja]</td>
					<td>$tuoterow[aleryhma]</td><td>$tuoterow[tahtituote]</td><td>$peralrow[alennus]%</td><td>$tuoterow[vakkoodi]</td></tr>";

			//3
			echo "<tr><th>".t("Toimtuoteno")."</th><th>".t("Myyntihinta")."</th><th>".t("Netto/Ovh")."</th><th>".t("Ostohinta")." / ".t("Alennus")."</th><th>".t("Kehahinta")."</th><th>".t("Vihahinta")."</th>";
			echo "<tr><td valign='top' >$tuoterow[toim_tuoteno]</td>
						<td valign='top' align='right'>".sprintf("%.".$yhtiorow['hintapyoristys']."f", $tuoterow["myyntihinta"])." $yhtiorow[valkoodi]$valuuttalisa</td>
						<td valign='top' align='right'>".sprintf("%.".$yhtiorow['hintapyoristys']."f", $tuoterow["nettohinta"])."/".sprintf("%.".$yhtiorow['hintapyoristys']."f", $tuoterow["myymalahinta"])."</td>
						<td valign='top' align='right'>";
						if ($tuoterow[ostohinta][0] != '/') {
							echo sprintf("%.".$yhtiorow['hintapyoristys']."f", $tuoterow["ostohinta"]);
						}
						
						echo"</td><td valign='top' align='right'>$tuoterow[kehahin]</td>
						<td valign='top' align='right'>$tuoterow[vihahin] ".tv1dateconv($tuoterow["vihapvm"])."</td>
				</tr>";

			//4
			echo "<tr><th>".t("Hälyraja")." / ".t("Varastoitava")."</th><th>".t("Tilerä")."</th><th>".t("Toierä")."</th><th>".t("Kerroin")."</th><th>".t("Tarrakerroin")."</th><th>".t("Tarrakpl")."</th>";
			echo "<tr><td valign='top' align='right'>$tuoterow[halytysraja] / $tuoterow[ei_varastoida]</td>
						<td valign='top' align='right'>$tuoterow[osto_era]</td>
						<td valign='top' align='right'>$tuoterow[myynti_era]</td>
						<td valign='top' align='right'>$tuoterow[tuotekerroin]</td>
						<td valign='top' align='right'>$tuoterow[tarrakerroin]</td>
						<td valign='top' align='right'>$tuoterow[tarrakpl]</td>
					</tr>";

			//5
			echo "<tr><th>".t("Tullinimike")." / %</th><th colspan='4'>".t("Tullinimikkeen kuvaus")."</th><th>".t("Toinen paljous")."</th></tr>";
			echo "<tr><td>$tullirow1[cn] $prossat</td><td colspan='4'>".substr($tullirow3['dm'],0,20)." - ".substr($tullirow2['dm'],0,20)." - ".substr($tullirow1['dm'],0,20)."</td><td>$tullirow1[su]</td></tr>";
			// $prossa

			//6
			echo "<tr><th>".t("Luontipvm")."</th><th>".t("Muutospvm")."</th><th>".t("Epäkurantti25pvm")."</th><th>".t("Epäkurantti50pvm")."</th><th>".t("Epäkurantti75pvm")."</th><th>".t("Epäkurantti100pvm")."</th></tr>";
			echo "<tr><td>".tv1dateconv($tuoterow["luonti"])."</td><td>".tv1dateconv($tuoterow["muutos"])."</td><td>".tv1dateconv($tuoterow["epakurantti25pvm"])."</td><td>".tv1dateconv($tuoterow["epakurantti50pvm"])."</td><td>".tv1dateconv($tuoterow["epakurantti75pvm"])."</td><td>".tv1dateconv($tuoterow["epakurantti100pvm"])."</td></tr>";
			
			//7
			echo "<tr><th colspan='6'>".t("Tuotteen kuvaus")."</th></tr>";
			echo "<td colspan='6'>$tuoterow[kuvaus]&nbsp;</td></tr>";
			
			//8
			echo "<tr><th>".t("Info")."</th><th colspan='5'>".t("Avainsanat")."</th></tr>";
			echo "<tr><td>$tuoterow[muuta]&nbsp;</td><td colspan='5'>$tuoterow[lyhytkuvaus]</td></tr>";


			echo "</table>";

			echo "<table><tr>";
			echo "<td class='back' valign='top' align='left'>";
			echo "<table>";
			echo "<tr><th>".t("Korvaavat")."</th><th>".t("Kpl")."</th></tr>";

			//korvaavat tuotteet
			$query  = "select * from korvaavat where tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
			$korvaresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($korvaresult)==0) {
				echo "<tr><td>".t("Ei korvaavia")."!</td><td></td></tr>";
			}
			else {
				// tuote löytyi, joten haetaan sen id...
				$row    = mysql_fetch_array($korvaresult);
				$id		= $row['id'];

				$query = "select * from korvaavat where id='$id' and tuoteno<>'$tuoteno' and yhtio='$kukarow[yhtio]' order by jarjestys, tuoteno";
				$korva2result = mysql_query($query) or pupe_error($query);

				while ($row = mysql_fetch_array($korva2result)) {
					list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"], '', '', '', '', '', '', '', '', $saldoaikalisa);

					echo "<tr><td><a href='$PHP_SELF?tee=Z&tuoteno=$row[tuoteno]'>$row[tuoteno]</a></td><td>$myytavissa</td></tr>";
				}
			}

			echo "</table>";
			echo "</td>";

			// aika karseeta, mutta katotaan voidaanko tällästä optiota näyttää yks tosi firma specific juttu
			$query = "describe yhteensopivuus_tuote";
			$res = mysql_query($query);

			if (mysql_error() == "" and file_exists("yhteensopivuus_tuote.php")) {

				$query = "select count(*) countti from yhteensopivuus_tuote where tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]' and tyyppi='HA'";
				$yhtresult = mysql_query($query) or pupe_error($query);
				$yhtrow = mysql_fetch_array($yhtresult);

				if ($yhtrow['countti'] > 0) {
					echo "<td class='back' valign='top' align='left'>";
					echo "<table>";
					echo "<tr><th>".t("Yhteensopivuudet")."</th></tr>";

					echo "<tr><td><a href='yhteensopivuus_tuote.php?tee=etsi&tuoteno=$tuoteno'>Siirry tuotteen yhteensopivuuksiin</a></td></tr>";

					echo "</table>";
					echo "</td>";
				}
			}

			echo "<tr></table><br>";
			
			// Varastosaldot ja paikat
			echo "<font class='message'>".t("Varastopaikat")."</font><hr>";			
			echo "<table>";
			echo "<tr>";
			echo "<td class='back' valign='top' align='left'>";

			
			if ($tuoterow["ei_saldoa"] == '') {
				// Saldot
				echo "<table>";
				echo "<tr><th>".t("Varasto")."</th><th>".t("Varastopaikka")."</th><th>".t("Saldo")."</th><th>".t("Hyllyssä")."</th><th>".t("Myytävissä")."</th></tr>";

				$kokonaissaldo = 0;
				$kokonaishyllyssa = 0;
				$kokonaismyytavissa = 0;

				//saldot per varastopaikka			
				if ($tuoterow["sarjanumeroseuranta"] == "E" or $tuoterow["sarjanumeroseuranta"] == "F") {
					$query = "	SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa, 
								tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
								sarjanumeroseuranta.sarjanumero era,
								concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0')) sorttauskentta,
								varastopaikat.nimitys, if(varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
					 			FROM tuote
								JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
								JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
								and concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
								and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
								JOIN sarjanumeroseuranta ON sarjanumeroseuranta.yhtio = tuote.yhtio 
								and sarjanumeroseuranta.tuoteno = tuote.tuoteno
								and sarjanumeroseuranta.hyllyalue = tuotepaikat.hyllyalue
								and sarjanumeroseuranta.hyllynro  = tuotepaikat.hyllynro
								and sarjanumeroseuranta.hyllyvali = tuotepaikat.hyllyvali
								and sarjanumeroseuranta.hyllytaso = tuotepaikat.hyllytaso
								and sarjanumeroseuranta.myyntirivitunnus = 0
								and sarjanumeroseuranta.era_kpl != 0
								WHERE tuote.yhtio = '$kukarow[yhtio]'
								and tuote.tuoteno = '$tuoteno'
								GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15
								ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";		
				}
				else {
					$query = "	SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa, 
								tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
								concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta,
								varastopaikat.nimitys, if(varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
					 			FROM tuote
								JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
								JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
								and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
								and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
								WHERE tuote.yhtio = '$kukarow[yhtio]'
								and tuote.tuoteno = '$tuoteno'
								ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";	
				}
				$sresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($sresult) > 0) {
					while ($saldorow = mysql_fetch_array ($sresult)) {

						list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($saldorow["tuoteno"], '', '', '', $saldorow["hyllyalue"], $saldorow["hyllynro"], $saldorow["hyllyvali"], $saldorow["hyllytaso"], '', $saldoaikalisa, $saldorow["era"]);

						//summataan kokonaissaldoa
						$kokonaissaldo += $saldo;
						$kokonaishyllyssa += $hyllyssa;
						$kokonaismyytavissa += $myytavissa;

						echo "<tr>
								<td>$saldorow[nimitys] $saldorow[tyyppi] $saldorow[era]</td>
								<td>$saldorow[hyllyalue] $saldorow[hyllynro] $saldorow[hyllyvali] $saldorow[hyllytaso]</td>
								<td align='right'>".sprintf("%.2f", $saldo)."</td>
								<td align='right'>".sprintf("%.2f", $hyllyssa)."</td>
								<td align='right'>".sprintf("%.2f", $myytavissa)."</td>
								</tr>";
					}
				}

				list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($tuoteno, 'ORVOT', '', '', '', '', '', '', '', $saldoaikalisa);

				if ($myytavissa != 0) {
					echo "<tr><td>".t("Tuntematon")."</td><td>?</td>";
					echo "<td align='right'>".sprintf("%.2f", $saldo)."</td>
							<td align='right'>".sprintf("%.2f", $hyllyssa)."</td>
							<td align='right'>".sprintf("%.2f", $myytavissa)."</td>
							</tr>";

					//summataan kokonaissaldoa
					$kokonaissaldo += $saldo;
					$kokonaishyllyssa += $hyllyssa;
					$kokonaismyytavissa += $myytavissa;
				}

				echo "<tr>
						<th colspan='2'>".t("Yhteensä")."</th>
						<td align='right'>".sprintf("%.2f", $kokonaissaldo)."</td>
						<td align='right'>".sprintf("%.2f", $kokonaishyllyssa)."</td>
						<td align='right'>".sprintf("%.2f", $kokonaismyytavissa)."</td>
						</tr></td>";

				echo "</table>";

				$kokonaissaldo_tapahtumalle = $kokonaissaldo;

				// katsotaan onko tälle tuotteelle yhtään sisäistä toimittajaa ja että toimittajan tiedoissa on varmasti kaikki EDI mokkulat päällä ja oletusvienti on jotain vaihto-omaisuutta
				$query = "	SELECT tyyppi_tieto, liitostunnus
							from tuotteen_toimittajat, toimi
							where tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
							and tuotteen_toimittajat.tuoteno = '$tuoteno'
							and toimi.yhtio         = tuotteen_toimittajat.yhtio
							and toimi.tunnus        = tuotteen_toimittajat.liitostunnus
							and toimi.tyyppi        = 'S'
							and toimi.tyyppi_tieto != ''
							and toimi.edi_palvelin != ''
							and toimi.edi_kayttaja != ''
							and toimi.edi_salasana != ''
							and toimi.edi_polku    != ''
							and toimi.oletus_vienti in ('C','F','I')";
				$kres  = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($kres) > 0) {
					echo "<td class='back' valign='top' align='left'><table>";
					echo "<tr><th>".t("Suoratoimitus Yhtiö/Varasto")."</th><th>".t("Saldo")."</th></tr>";

					$kokonaissaldo = 0;
					$firmanimi = '';

					while ($superrow = mysql_fetch_array($kres)) {
						$query = "	SELECT yhtio.nimi, yhtio.yhtio, yhtio.tunnus, varastopaikat.tunnus, varastopaikat.nimitys, hyllyalue, hyllynro, hyllyvali, hyllytaso, alkuhyllyalue, loppuhyllyalue, alkuhyllynro, loppuhyllynro, sum(saldo) saldo
									from tuotepaikat
									join yhtio on yhtio.yhtio=tuotepaikat.yhtio
									join varastopaikat on tuotepaikat.yhtio = varastopaikat.yhtio
									and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
									and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
									where tuotepaikat.yhtio = '$superrow[tyyppi_tieto]'
									and tuoteno = '$tuoteno'
									and varastopaikat.tyyppi = ''
									group by 1,2,3,4
									order by 5";
						$kres2  = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($kres2) > 0) {

							while ($krow  = mysql_fetch_array($kres2)) {
								// katotaan ennakkopoistot toimittavalta yritykseltä
								$query = "	SELECT sum(varattu) varattu
											from tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
											where yhtio	= '$krow[yhtio]' and
											tyyppi		= 'L' and
											varattu		> 0 and
											tuoteno		= '$tuoteno'
											and concat(rpad(upper('$krow[alkuhyllyalue]')  ,5,'0'),lpad(upper('$krow[alkuhyllynro]')  ,5,'0')) <= concat(rpad(upper(tilausrivi.hyllyalue) ,5,'0'),lpad(upper(tilausrivi.hyllynro) ,5,'0'))
											and concat(rpad(upper('$krow[loppuhyllyalue]') ,5,'0'),lpad(upper('$krow[loppuhyllynro]') ,5,'0')) >= concat(rpad(upper(tilausrivi.hyllyalue) ,5,'0'),lpad(upper(tilausrivi.hyllynro) ,5,'0'))";
								$krtre = mysql_query($query) or pupe_error($query);
								$krtur = mysql_fetch_array($krtre);

								// sitten katotaan ollaanko me jo varattu niitä JT rivejä toimittajalta
								$query = "	SELECT sum(jt) varattu
											from tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
											where yhtio			= '$kukarow[yhtio]' 
											and tyyppi			= 'L' 
											and laskutettuaika	= '0000-00-00' 
											and var				= 'S'
											and tuoteno			= '$tuoteno' 
											and tilaajanrivinro	= '$superrow[liitostunnus]'
											and hyllyalue 		= '$krow[hyllyalue]' 
											and hyllynro 		= '$krow[hyllynro]' 
											and hyllyvali 		= '$krow[hyllyvali]' 
											and hyllytaso 		= '$krow[hyllytaso]'";
								$krtre = mysql_query($query) or pupe_error($query);
								$krtu2 = mysql_fetch_array($krtre);

								// katotaan tuotteen saldo
								$saldo = sprintf("%.02f",$krow["saldo"] - $krtur["varattu"] - $krtu2["varattu"]);

								echo "<tr><td>$krow[nimi]/$krow[nimitys]</td>";
								echo "<td align='right'>$saldo</td></tr>";

								$firmanimi = $krow['nimi'];

								$kokonaissaldo += $saldo;
							}
						}
					}
					echo "<tr><th>".t("Suoratoimitettavissa Yhteensä")."</th><td align='right'>".sprintf("%.02f",$kokonaissaldo)."</td></tr>";

					echo "</table></td></tr>";
				}
			}

			echo "</td>";
			echo "</tr><tr><td class='back' valign='top' align='left' colspan='2'>";


			// Tilausrivit tälle tuotteelle
			$query = "	SELECT if(asiakas.ryhma != '', concat(lasku.nimi,' (',asiakas.ryhma,')'), lasku.nimi) nimi, lasku.tunnus, (tilausrivi.varattu+tilausrivi.jt) kpl,
						if(tilausrivi.tyyppi!='O' and tilausrivi.tyyppi!='W', tilausrivi.kerayspvm, tilausrivi.toimaika) pvm,
						varastopaikat.nimitys varasto, tilausrivi.tyyppi, lasku.laskunro, lasku.tilaustyyppi, tilausrivi.var, lasku2.laskunro as keikkanro, tilausrivi.jaksotettu
						FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
						JOIN lasku use index (PRIMARY) ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
						LEFT JOIN varastopaikat ON varastopaikat.yhtio = lasku.yhtio and varastopaikat.tunnus = lasku.varasto
						LEFT JOIN lasku as lasku2 ON lasku2.yhtio = tilausrivi.yhtio and lasku2.tunnus = tilausrivi.uusiotunnus
						LEFT JOIN asiakas ON asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.tyyppi in ('L','E','O','G','V','W','M')
						and tilausrivi.tuoteno = '$tuoteno'
						and tilausrivi.laskutettuaika = '0000-00-00'
						and tilausrivi.varattu + tilausrivi.jt != 0
						and tilausrivi.var not in ('P')
						ORDER BY pvm";
			$jtresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($jtresult) != 0) {

				// Avoimet rivit
				echo "<br><table>";

				echo "<tr>
						<th>".t("Asiakas/Toimittaja")."</th>
						<th>".t("Tilaus/Keikka")."</th>
						<th>".t("Tyyppi")."</th>
						<th>".t("Toimaika")."</th>
						<th>".t("Kpl")."</th>
						<th>".t("Myytävissä")."</th>
						</tr>";

				$yhteensa 	= array();

				while ($jtrow = mysql_fetch_array($jtresult)) {

					$tyyppi 	= "";
					$vahvistettu= "";
					$merkki 	= "";
					$keikka 	= "";

					if ($jtrow["tyyppi"] == "O") {
						$tyyppi = t("Ostotilaus");
						$merkki = "+";

						if ($jtrow["keikkanro"] > 0) {
							$keikka = " / ".$jtrow["keikkanro"];
						}
					}
					elseif($jtrow["tyyppi"] == "E") {
						$tyyppi = t("Ennakkotilaus");
						$merkki = "-";
					}
					elseif($jtrow["tyyppi"] == "G") {
						$tyyppi = t("Varastosiirto");
						$merkki = "-";
					}
					elseif($jtrow["tyyppi"] == "V") {
						$tyyppi = t("Kulutus");
						$merkki = "-";
					}
					elseif($jtrow["tyyppi"] == "L" and $jtrow["var"] == "J") {
						$tyyppi = t("Jälkitoimitus");
						$merkki = "-";
					}
					elseif($jtrow["tyyppi"] == "L" and $jtrow["kpl"] > 0) {
						$tyyppi = t("Myynti");
						$merkki = "-";
					}
					elseif($jtrow["tyyppi"] == "L" and $jtrow["kpl"] < 0) {
						$tyyppi = t("Hyvitys");
						$merkki = "+";
					}
					elseif(($jtrow["tyyppi"] == "W" or $jtrow["tyyppi"] == "M") and $jtrow["tilaustyyppi"] == "W") {
						$tyyppi = t("Valmistus");
						$merkki = "+";
					}
					elseif(($jtrow["tyyppi"] == "W" or $jtrow["tyyppi"] == "M") and $jtrow["tilaustyyppi"] == "V") {
						$tyyppi = t("Asiakkaallevalmistus");
						$merkki = "+";
					}
					
					if ($jtrow["jaksotettu"] == 1) {
						$vahvistettu = " (".t("Vahvistettu").")";
					}
					
					$yhteensa[$tyyppi] += $jtrow["kpl"];

					if($jtrow["varasto"] != "") {
						$tyyppi = $tyyppi." - ".$jtrow["varasto"];
					}

					list(, , $myyta) = saldo_myytavissa($tuoteno, "KAIKKI", '', '', '', '', '', '', '', $jtrow["pvm"]);

					echo "<tr>
							<td>$jtrow[nimi]</td>
							<td><a href='$PHP_SELF?tuoteno=$tuoteno&tee=NAYTATILAUS&tunnus=$jtrow[tunnus]'>$jtrow[tunnus]</a>$keikka</td>
							<td>$tyyppi</td>
							<td>".tv1dateconv($jtrow["pvm"])."$vahvistettu</td>
							<td align='right'>$merkki".abs($jtrow["kpl"])."</td>
							<td align='right'>".sprintf('%.2f', $myyta)."</td>
							</tr>";
				}

				echo "<tr><td class='back'>&nbsp;</td></tr>";
				foreach($yhteensa as $type => $kappale) {
					echo "<tr><th colspan='1'>".t("$type yhteensä")."</th><td>$kappale</td></tr>";
				}

				echo "</table>";
			}

			echo "</td>";
			echo "</tr>";
			echo "</table><br>";

			if(!isset($raportti)) {
				if($tuoterow["tuotetyyppi"] == "R") $raportti="KULUTUS";
				else $raportti="MYYNTI";
			}
			
			if($raportti == "KULUTUS") $sele["K"] = "checked";
			else $sele["M"] = "checked";
			
			echo "<form action='$PHP_SELF#Raportit' method='post'>
					<input type='hidden' name='tuoteno' value='$tuoteno'>
					<input type='hidden' name='tee' value='Z'>
					<font class='message'>".t("Raportointi")."</font><a href='#' name='Raportit'></a>
					<input type='radio' onclick='submit()' name='raportti' value='MYYNTI' $sele[M]> ".t("Myynnistä")." 
					<input type='radio' onclick='submit()' name='raportti' value='KULUTUS' $sele[K]> ".t("Kulutuksesta")."
				</form><hr>";
			
			echo "<table>";

			if($raportti=="MYYNTI") {
				//myynnit
				$edvuosi  = date('Y')-1;
				$taavuosi = date('Y');

				$query = "	SELECT
							round(sum(if(laskutettuaika >= date_sub(now(),interval 30 day), rivihinta,0)), $yhtiorow[hintapyoristys])	summa30,
							round(sum(if(laskutettuaika >= date_sub(now(),interval 30 day), kate,0)), $yhtiorow[hintapyoristys])  		kate30,
							sum(if(laskutettuaika >= date_sub(now(),interval 30 day), kpl,0))  											kpl30,
							round(sum(if(laskutettuaika >= date_sub(now(),interval 90 day), rivihinta,0)), $yhtiorow[hintapyoristys])	summa90,
							round(sum(if(laskutettuaika >= date_sub(now(),interval 90 day), kate,0)), $yhtiorow[hintapyoristys])		kate90,
							sum(if(laskutettuaika >= date_sub(now(),interval 90 day), kpl,0))											kpl90,
							round(sum(if(YEAR(laskutettuaika) = '$taavuosi', rivihinta,0)), $yhtiorow[hintapyoristys])	summaVA,
							round(sum(if(YEAR(laskutettuaika) = '$taavuosi', kate,0)), $yhtiorow[hintapyoristys])		kateVA,
							sum(if(YEAR(laskutettuaika) = '$taavuosi', kpl,0))											kplVA,
							round(sum(if(YEAR(laskutettuaika) = '$edvuosi', rivihinta,0)), $yhtiorow[hintapyoristys])	summaEDV,
							round(sum(if(YEAR(laskutettuaika) = '$edvuosi', kate,0)), $yhtiorow[hintapyoristys])		kateEDV,
							sum(if(YEAR(laskutettuaika) = '$edvuosi', kpl,0))											kplEDV
							FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
							WHERE yhtio='$kukarow[yhtio]'
							and tyyppi='L'
							and tuoteno='$tuoteno'
							and laskutettuaika >= '$edvuosi-01-01'";
				$result3 = mysql_query($query) or pupe_error($query);
				$lrow = mysql_fetch_array($result3);
				
				echo "<tr>
						<th>".t("Myynti").":</th>
						<th>".t("Edelliset 30pv")."</th>
						<th>".t("Edelliset 90pv")."</th>
						<th>".t("Kauden alusta")."</th>
						<th>".t("Vuosi")." $edvuosi</th>
						</tr>";

				echo "<tr><th align='left'>".t("Liikevaihto").":</th>
						<td align='right' nowrap>$lrow[summa30] $yhtiorow[valkoodi]</td>
						<td align='right' nowrap>$lrow[summa90] $yhtiorow[valkoodi]</td>
						<td align='right' nowrap>$lrow[summaVA] $yhtiorow[valkoodi]</td>
						<td align='right' nowrap>$lrow[summaEDV] $yhtiorow[valkoodi]</td></tr>";

				echo "<tr><th align='left'>".t("Myykpl").":</th>
						<td align='right' nowrap>$lrow[kpl30] ".t("KPL")."</td>
						<td align='right' nowrap>$lrow[kpl90] ".t("KPL")."</td>
						<td align='right' nowrap>$lrow[kplVA] ".t("KPL")."</td>
						<td align='right' nowrap>$lrow[kplEDV] ".t("KPL")."</td></tr>";

				echo "<tr><th align='left'>".t("Kate").":</th>
						<td align='right' nowrap>$lrow[kate30] $yhtiorow[valkoodi]</td>
						<td align='right' nowrap>$lrow[kate90] $yhtiorow[valkoodi]</td>
						<td align='right' nowrap>$lrow[kateVA] $yhtiorow[valkoodi]</td>
						<td align='right' nowrap>$lrow[kateEDV] $yhtiorow[valkoodi]</td></tr>";

				echo "<tr><th align='left'>".t("Katepros").":</th>";				
				
				if ($lrow["summa30"] > 0)
					$kate30pros = round($lrow["kate30"]/$lrow["summa30"]*100,2);

				if ($lrow["summa90"] > 0)
					$kate90pros = round($lrow["kate90"]/$lrow["summa90"]*100,2);

				if ($lrow["summaVA"] > 0)
					$kateVApros = round($lrow["kateVA"]/$lrow["summaVA"]*100,2);

				if ($lrow["summaEDV"] > 0)
					$kateEDVpros = round($lrow["kateEDV"]/$lrow["summaEDV"]*100,2);

				echo "<td align='right' nowrap>$kate30pros %</td>";
				echo "<td align='right' nowrap>$kate90pros %</td>";
				echo "<td align='right' nowrap>$kateVApros %</td>";
				echo "<td align='right' nowrap>$kateEDVpros %</td></tr>";

				echo "</table><br>";
			}
			elseif($raportti == "KULUTUS") {

				$kk=date("m");
				$vv=date("Y");
				$select_summa = $otsikkorivi = "";
				for($y=1;$y<=12;$y++) {

					$kk--;

					if($kk == 0) {
						$kk = 12;
						$vv--;
					}
					
					switch ($kk) {
						case "1":
							$month = "Tammi";
							break;
						case "2":
							$month = "Helmi";
							break;
						case "3":
							$month = "Maalis";
							break;
						case "4":
							$month = "Huhti";
							break;
						case "5":
							$month = "Touko";
							break;
						case "6":
							$month = "Kesä";
							break;
						case "7":
							$month = "Heinä";
							break;
						case "8":
							$month = "Elo";
							break;
						case "9":
							$month = "Syys";
							break;
						case "10":
							$month = "Loka";
							break;
						case "11":
							$month = "Marras";
							break;
						case "12":
							$month = "Joulu";
							break;
					}
					
					$otsikkorivi .= "<th>".t("$month")."</th>";
					
					$ppk = date("t");
					$alku="$vv-".sprintf("%02s",$kk)."-01 00:00:00";
					$ed=($vv-1)."-".sprintf("%02s",$kk)."-01 00:00:00";
					
					if($select_summa=="") {
						$select_summa .= "	SUM(IF(toimitettuaika>='$alku' and toimitettuaika<=DATE_ADD('$alku', interval 1 month) and tyyppi='L', kpl, NULL)) kpl_myynti_$kk
											, SUM(IF(toimitettuaika>='$alku' and toimitettuaika<=DATE_ADD('$alku', interval 1 month) and tyyppi='V', kpl, NULL)) kpl_valmistus_asiakkaalle_$kk
											, SUM(IF(toimitettuaika>='$alku' and toimitettuaika<=DATE_ADD('$alku', interval 1 month) and tyyppi='W', kpl, NULL)) kpl_valmistus_varastoon_$kk
											, SUM(IF(toimitettuaika>='$ed' and toimitettuaika<=DATE_ADD('$ed', interval 1 month) and tyyppi='L', kpl, NULL)) ed_kpl_myynti_$kk
											, SUM(IF(toimitettuaika>='$ed' and toimitettuaika<=DATE_ADD('$ed', interval 1 month) and tyyppi='V', kpl, NULL)) ed_kpl_valmistus_asiakkaalle_$kk
											, SUM(IF(toimitettuaika>='$ed' and toimitettuaika<=DATE_ADD('$ed', interval 1 month) and tyyppi='W', kpl, NULL)) ed_kpl_valmistus_varastoon_$kk
											
											";
					}
					else {
						$select_summa .= "	, SUM(IF(toimitettuaika>='$alku' and toimitettuaika<=DATE_ADD('$alku', interval 1 month) and tyyppi='L', kpl, NULL)) kpl_myynti_$kk
											, SUM(IF(toimitettuaika>='$alku' and toimitettuaika<=DATE_ADD('$alku', interval 1 month) and tyyppi='V', kpl, NULL)) kpl_valmistus_asiakkaalle_$kk
											, SUM(IF(toimitettuaika>='$alku' and toimitettuaika<=DATE_ADD('$alku', interval 1 month) and tyyppi='W', kpl, NULL)) kpl_valmistus_varastoon_$kk
											, SUM(IF(toimitettuaika>='$ed' and toimitettuaika<=DATE_ADD('$ed', interval 1 month) and tyyppi='L', kpl, NULL)) ed_kpl_myynti_$kk
											, SUM(IF(toimitettuaika>='$ed' and toimitettuaika<=DATE_ADD('$ed', interval 1 month) and tyyppi='V', kpl, NULL)) ed_kpl_valmistus_asiakkaalle_$kk
											, SUM(IF(toimitettuaika>='$ed' and toimitettuaika<=DATE_ADD('$ed', interval 1 month) and tyyppi='W', kpl, NULL)) ed_kpl_valmistus_varastoon_$kk
											
											";
					}
					
				}
				
				//	Tutkitaan onko tää liian hias, indexi on ainakin vähän paska, toimitettuaikaa ei ole indexoitu
				$query = "	SELECT
							$select_summa
							FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
							WHERE yhtio='$kukarow[yhtio]'
							and tyyppi IN ('L','W','V')
							and tuoteno='$tuoteno'
							and toimitettuaika >= '$ed'";
				$result3 = mysql_query($query) or pupe_error($query);
				$lrow = mysql_fetch_array($result3);
				
				echo "<table><tr><th>".t("Tyyppi")."</th>$otsikkorivi<th>".t("Yhteensä")."</th></tr>";
				$erittely=array();
				$ed_erittely=array();
				
				foreach(array("myynti", "valmistus_asiakkaalle", "valmistus_varastoon") as $tyyppi) {
					echo "<tr class='aktiivi'><td class='tumma'>".t(str_replace("_"," ",$tyyppi))."</td>";

					$kk=date("m");					
					$summa=0;
					$ed_summa=0;
					
					for($y=1;$y<=12;$y++) {
						
						$kk--;
						if($kk == 0) {
							$kk = 12;
						}
						
						$key="kpl_".$tyyppi."_".$kk;
						
						$muutos="";
						$muutos_abs = $lrow[$key] - $lrow["ed_".$key];
						
						if($lrow["ed_".$key]>0) {
							$muutos_suht = round((($lrow[$key] / $lrow["ed_".$key])-1)*100,2);
						}
						else {
							$muutos_suht=0;
						}
						
						if($muutos_abs<>0) {
							$muutos = "edellinen: ".(int)$lrow["ed_".$key]."{$tuoterow["yksikko"]} muutos: $muutos_abs{$tuoterow["yksikko"]}";
						}
						
						if($muutos_suht<>0 and $lrow[$key]<>0 and $lrow["ed_".$key] <> 0) {
							$muutos .= " ($muutos_suht%)";
						}
						
						if($lrow[$key]>0) {
							echo "<td title='$muutos'>".$lrow[$key]."</td>";
						}
						else {
							echo "<td title='$muutos'></td>";
						}
						
						$summa+=$lrow[$key];
						$ed_summa+=$lrow["ed_".$key];
												
						$erittely[$kk]+=$lrow[$key];
						$ed_erittely[$kk]+=$lrow["ed_".$key];
					}
					
					$muutos="";
					$muutos_abs = $summa - $ed_summa;
					
					if($ed_summa>0) {
						$muutos_suht = round((($summa / $ed_summa)-1)*100,2);
					}
					else {
						$muutos_suht=0;
					}

					if($muutos_abs<>0) {
						$muutos = "edellinen: ".(int)$ed_summa."{$tuoterow["yksikko"]} muutos: $muutos_abs{$tuoterow["yksikko"]}";
					}
					
					if($muutos_suht<>0 and $summa<>0 and $ed_summa<>0) {
						$muutos .= " ($muutos_suht%)";
					}

					if($summa>0) {
						echo "<td class='tumma' title='$muutos'>".number_format($summa, 2, ',', ' ')."</td></tr>";
					}
					else {
						echo "<td class='tumma' title='$muutos'></td></tr>";
					}
				}
				
				echo "<tr><th>".t("Yhteensä")."</th>";
				
				$kk=date("m");
				$gt=$ed_gt=0;
				for($y=1;$y<=12;$y++) {
					
					$kk--;
					if($kk == 0) {
						$kk = 12;
					}
					
					$muutos="";
					$muutos_abs = $erittely[$kk] - $ed_erittely[$kk];

					if($erittely[$kk]>0) {
						$muutos_suht = round((($erittely[$kk] / $erittely[$kk])-1)*100,2);
					}
					else {
						$muutos_suht=0;
					}

					if($muutos_abs<>0) {
						$muutos = "edellinen: ".(int)$ed_erittely[$kk]."{$tuoterow["yksikko"]} muutos: $muutos_abs{$tuoterow["yksikko"]}";
					}
					
					if($muutos_suht<>0 and $erittely[$kk]<>0 and $ed_erittely[$kk]<>0) {
						$muutos .= " ($muutos_suht%)";
					}
					
					if($erittely[$kk]>0) {
						echo "<td class='tumma' title='$muutos'>".number_format($erittely[$kk], 2, ',', ' ')."</td>";
						$gt+=$erittely[$kk];						
					}
					else {
						echo "<td class='tumma' title='$muutos'></td>";
					}
					$ed_gt+=$ed_erittely[$kk];
				}
				
				$muutos="";
				$muutos_abs = $gt - $ed_gt;
				
				if($ed_gt>0) {
					$muutos_suht = round((($gt / $ed_gt)-1)*100,2);
				}
				else {
					$muutos_suht=0;
				}
				
				if($muutos_abs<>0) {
					$muutos = "edellinen: ".(int)$ed_gt."{$tuoterow["yksikko"]} muutos: $muutos_abs{$tuoterow["yksikko"]}";
				}
				
				if($muutos_suht<>0 and $gt<>0 and $ed_gt <> 0) {
					$muutos .= " ($muutos_suht%)";
				}
				
				echo "<td class='tumma' title='$muutos'>".number_format($gt, 2, ',', ' ')."</td><tr></table><br><br>";
			}

			if ($tuoterow["sarjanumeroseuranta"] == "S" or $tuoterow["sarjanumeroseuranta"] == "U" or $tuoterow["sarjanumeroseuranta"] == "V") {
				
				echo "<font class='message'>".t("Sarjanumerot")."</font><hr>";
				
				$query	= "	SELECT sarjanumeroseuranta.*, tilausrivi_osto.tunnus, if(tilausrivi_osto.rivihinta=0 and tilausrivi_osto.tyyppi='L', tilausrivi_osto.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi_osto.alv<500, (1+tilausrivi_osto.alv/100), 1) * if(tilausrivi_osto.netto='N', (1-tilausrivi_osto.ale/100), (1-(tilausrivi_osto.ale+lasku_osto.erikoisale-(tilausrivi_osto.ale*lasku_osto.erikoisale/100))/100)), if(tilausrivi_osto.rivihinta!=0 and tilausrivi_osto.kpl!=0, tilausrivi_osto.rivihinta/tilausrivi_osto.kpl, 0)) ostosumma,
							tilausrivi_osto.nimitys nimitys
							FROM sarjanumeroseuranta
							LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
							LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
							LEFT JOIN lasku lasku_osto   use index (PRIMARY) ON lasku_osto.yhtio=sarjanumeroseuranta.yhtio and lasku_osto.tunnus=tilausrivi_osto.uusiotunnus
							WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
							and sarjanumeroseuranta.tuoteno = '$tuoterow[tuoteno]'
							and sarjanumeroseuranta.myyntirivitunnus != -1
							and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00')
							and tilausrivi_osto.laskutettuaika != '0000-00-00'";
				$sarjares = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($sarjares) > 0) {
					echo "<table>";
					echo "<tr><th colspan='3'>".t("Varasto").":</th></tr>";
					echo "<tr><th>".t("Nimitys")."</th>";
					echo "<th>".t("Sarjanumero")."</th>";
					echo "<th>".t("Ostohinta")."</th></tr>";

					while($sarjarow = mysql_fetch_array($sarjares)) {
						echo "<tr><td>$sarjarow[nimitys]</td><td><a href='tilauskasittely/sarjanumeroseuranta.php?tuoteno_haku=$tuoterow[tuoteno]&sarjanumero_haku=".urlencode($sarjarow["sarjanumero"])."'>$sarjarow[sarjanumero]</a></td><td align='right'>".sprintf('%.2f', $sarjarow["ostosumma"])."</td></tr>";
					}

					echo "</table><br>";
				}
			}
			elseif ($tuoterow["sarjanumeroseuranta"] == "E" or $tuoterow["sarjanumeroseuranta"] == "F") {
				echo "<font class='message'>".t("Eränumerot")."</font><hr>";
				
				$query	= "	SELECT sarjanumeroseuranta.sarjanumero, sarjanumeroseuranta.parasta_ennen, sarjanumeroseuranta.lisatieto, 
							sarjanumeroseuranta.hyllyalue, sarjanumeroseuranta.hyllynro, sarjanumeroseuranta.hyllyvali, sarjanumeroseuranta.hyllytaso, 
							sarjanumeroseuranta.era_kpl kpl
							FROM sarjanumeroseuranta
							WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
							and sarjanumeroseuranta.tuoteno = '$tuoterow[tuoteno]'
							and sarjanumeroseuranta.myyntirivitunnus = 0
							and sarjanumeroseuranta.era_kpl != 0";
				$sarjares = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($sarjares) > 0) {
					echo "<table>";
					echo "<tr><th colspan='4'>".t("Varasto").":</th></tr>";
					echo "<th>".t("Eränumero")."</th>";
					
					if ($tuoterow["sarjanumeroseuranta"] == "F") {
						echo "<th>".t("Parasta ennen")."</th>";	
					}
					
					echo "<th>".t("Kpl")."</th>";
					echo "<th>".t("Lisätieto")."</th></tr>";

					while($sarjarow = mysql_fetch_array($sarjares)) {
						echo "<tr>
								<td><a href='tilauskasittely/sarjanumeroseuranta.php?tuoteno_haku=$tuoterow[tuoteno]&sarjanumero_haku=$sarjarow[sarjanumero]'>$sarjarow[sarjanumero]</a></td>";
						
						if ($tuoterow["sarjanumeroseuranta"] == "F") {
							echo "<td>".tv1dateconv($sarjarow["parasta_ennen"])."</td>";	
						}
						
						echo "<td align='right'>$sarjarow[kpl]</td>";
						
						
								
						echo "<td>$sarjarow[lisatieto]</td></tr>";
					}

					echo "</table><br>";
				}
			}

			// Varastotapahtumat
			echo "<font class='message'>".t("Tapahtumat")."</font><hr>";
			echo "<table>";
			echo "<form action='$PHP_SELF#Tapahtumat' method='post'>";

			if ($historia == "") $historia=1;
			$chk[$historia] = "SELECTED";

			echo "<input type='hidden' name='tee' value='Z'>";
			echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";

			echo "<a href='#' name='Tapahtumat'>";

			echo "<tr><th>".t("Tuotehistoria").":</th>";
			echo "<th colspan='2'>".t("Näytä tapahtumat").":</th>";
			echo "<th colspan='2'>";
			echo "<select name='historia' onchange='submit();'>'";
			echo "<option value='1' $chk[1]> ".t("20 viimeisintä")."</option>";
			echo "<option value='2' $chk[2]> ".t("Tilivuoden alusta")."</option>";
			echo "<option value='3' $chk[3]> ".t("Edellinen tilivuosi")."</option>";
			echo "<option value='4' $chk[4]> ".t("Kaikki tapahtumat")."</option>";			
			echo "</select>";
			echo "</th>";


			if ($tapahtumalaji == "laskutus") 			$sel1="SELECTED";
			if ($tapahtumalaji == "tulo") 				$sel2="SELECTED";
			if ($tapahtumalaji == "valmistus") 			$sel3="SELECTED";
			if ($tapahtumalaji == "siirto") 			$sel4="SELECTED";
			if ($tapahtumalaji == "kulutus") 			$sel5="SELECTED";
			if ($tapahtumalaji == "Inventointi") 		$sel6="SELECTED";
			if ($tapahtumalaji == "Epäkurantti") 		$sel7="SELECTED";
			if ($tapahtumalaji == "poistettupaikka") 	$sel8="SELECTED";
			if ($tapahtumalaji == "uusipaikka") 		$sel9="SELECTED";

			echo "<th colspan='2'>".t("Tapahtumalaji").":</th>";
			echo "<th>";
			echo "<select name='tapahtumalaji' onchange='submit();'>'";
			echo "<option value=''>".t("Näytä kaikki")."</option>";
			echo "<option value='laskutus' $sel1>".t("Laskutukset")."</option>";
			echo "<option value='tulo' $sel2>".t("Tulot")."</option>";
			echo "<option value='valmistus' $sel3>".t("Valmistukset")."</option>";
			echo "<option value='siirto' $sel4>".t("Siirrot")."</option>";
			echo "<option value='kulutus' $sel5>".t("Kulutukset")."</option>";
			echo "<option value='Inventointi' $sel6>".t("Inventoinnit")."</option>";
			echo "<option value='Epäkurantti' $sel7>".t("Epäkuranttiusmerkinnät")."</option>";
			echo "<option value='poistettupaikka' $sel8>".t("Poistetut tuotepaikat")."</option>";
			echo "<option value='uusipaikka' $sel9>".t("Perustetut tuotepaikat")."</option>";
			echo "</select>";
			echo "</th>";

			echo "<tr>";
			echo "<th>".t("Käyttäjä@Pvm")."</th>";
			echo "<th>".t("Tyyppi")."</th>";
			echo "<th>".t("Kpl")."</th>";
			echo "<th>".t("Kplhinta")."</th>";
			echo "<th>".t("Kehahinta")."</th>";
			echo "<th>".t("Arvo")."</th>";
			echo "<th>".t("Var.Arvo")."</th>";
			echo "<th>".t("Selite")."";

			echo "</th></form>";
			echo "</tr>";


			//tapahtumat
			if ($historia == '2') {
				$maara = "";
				$ehto  = " and tapahtuma.laadittu > '$yhtiorow[tilikausi_alku]' ";
			}
			elseif ($historia == '3') {
				$maara = "";
				$ehto  = " and tapahtuma.laadittu >= date_sub('$yhtiorow[tilikausi_alku]', interval 12 month) and tapahtuma.laadittu <= '$yhtiorow[tilikausi_alku]' ";
			}
			elseif ($historia == '4') {
				$maara = "";
				$ehto  = "";
			}
			else {
				$maara = "LIMIT 20";
				$ehto  = " and tapahtuma.laadittu >= date_sub(now(), interval 6 month) ";
			}

			$query = "	SELECT tapahtuma.laatija, tapahtuma.laadittu, tapahtuma.laji, tapahtuma.kpl, tapahtuma.kplhinta, tapahtuma.hinta,
						if(tapahtuma.laji in ('tulo','valmistus'), tapahtuma.kplhinta, tapahtuma.hinta)*tapahtuma.kpl arvo, 
						tapahtuma.selite, lasku.tunnus laskutunnus,
						concat_ws(' ', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso) paikka,
						tapahtuma.tuoteno,
						tilausrivi.tunnus trivitunn,
						tilausrivin_lisatiedot.osto_vai_hyvitys,
						lasku2.tunnus lasku2tunnus																
						FROM tapahtuma use index (yhtio_tuote_laadittu)
						LEFT JOIN tilausrivi use index (primary) ON tilausrivi.yhtio=tapahtuma.yhtio and tilausrivi.tunnus=tapahtuma.rivitunnus
						LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)						
						LEFT JOIN lasku use index (primary) ON lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
						LEFT JOIN lasku as lasku2 use index (primary) ON lasku2.yhtio=tilausrivi.yhtio and lasku2.tunnus=tilausrivi.uusiotunnus
						WHERE tapahtuma.yhtio = '$kukarow[yhtio]'
						and tapahtuma.tuoteno = '$tuoteno'
						$ehto
						ORDER BY tapahtuma.laadittu desc $maara";
			$qresult = mysql_query($query) or pupe_error($query);

			$vararvo_nyt = sprintf('%.2f',$kokonaissaldo_tapahtumalle*$tuoterow["kehahin"]);

			echo "<tr class='aktiivi'>
					<td colspan='4'>".t("Varastonarvo nyt").":</td>
					<td align='right'>$tuoterow[kehahin]</td>
					<td align='right'>$vararvo_nyt</td>
					<td align='right'>".sprintf('%.2f',$kokonaissaldo_tapahtumalle*$tuoterow["kehahin"])."</td>
					<td></td>
					</tr>";

			while ($prow = mysql_fetch_array ($qresult)) {

				$vararvo_nyt -= $prow["arvo"];

				if ($tapahtumalaji == "" or strtoupper($tapahtumalaji)==strtoupper($prow["laji"])) {
					echo "<tr class='aktiivi'>";
					echo "<td nowrap valign='top'>$prow[laatija]@".tv1dateconv($prow["laadittu"], "pitka")."</td>";
					echo "<td nowrap valign='top'>";

					if ($prow["laji"] == "laskutus" and $prow["laskutunnus"] != "") {
						echo "<a href='raportit/asiakkaantilaukset.php?toim=MYYNTI&tee=NAYTATILAUS&tunnus=$prow[laskutunnus]'>".t("$prow[laji]")."</a>";
					}
					elseif ($prow["laji"] == "tulo" and $prow["laskutunnus"] != "") {
						echo "<a href='raportit/asiakkaantilaukset.php?toim=OSTO&tee=NAYTATILAUS&tunnus=$prow[laskutunnus]'>".t("$prow[laji]")."</a>";
					}
					else {
						echo t("$prow[laji]");
					}

					echo "</td>";

					echo "<td nowrap align='right' valign='top'>$prow[kpl]</td>";
					echo "<td nowrap align='right' valign='top'>$prow[kplhinta]</td>";
					echo "<td nowrap align='right' valign='top'>$prow[hinta]</td>";
					echo "<td nowrap align='right' valign='top'>".sprintf('%.2f', $prow["arvo"])."</td>";
					echo "<td nowrap align='right' valign='top'>".sprintf('%.2f', $vararvo_nyt)."</td>";
					echo "<td valign='top'>$prow[selite]";
					
					if ($prow["laji"] == "tulo" and $prow["lasku2tunnus"] != "") {
						echo "<br><a href='raportit/asiakkaantilaukset.php?toim=OSTO&tee=NAYTATILAUS&tunnus=$prow[lasku2tunnus]'>".t("Näytä keikka")."</a>";
					}
					
					
					if (trim($prow["paikka"]) != "") echo "<br>".t("Varastopaikka").": $prow[paikka]";
					
					
					if ($tuoterow["sarjanumeroseuranta"] != "" and ($prow["laji"] == "tulo" or $prow["laji"] == "laskutus")) {
						
						if ($prow["laji"] == "tulo") {
							//Haetan sarjanumeron tiedot
							if ($prow["kpl"] < 0) {
								$sarjanutunnus = "myyntirivitunnus";
							}
							else {
								$sarjanutunnus = "ostorivitunnus";
							}
						}
						if ($prow["laji"] == "laskutus") {
							//Haetan sarjanumeron tiedot
							if ($prow["osto_vai_hyvitys"] == '' and $prow["kpl"] < 0) {
								$sarjanutunnus = "myyntirivitunnus";
							}
							elseif ($prow["kpl"] < 0){
								$sarjanutunnus = "ostorivitunnus";
							}
							else {
								$sarjanutunnus = "myyntirivitunnus";
							}
						}

						$query = "	SELECT distinct sarjanumero
									from sarjanumeroseuranta
									where yhtio = '$kukarow[yhtio]'
									and tuoteno = '$prow[tuoteno]'
									and $sarjanutunnus='$prow[trivitunn]'
									and sarjanumero != ''
									group by sarjanumero
									order by sarjanumero";
						$sarjares = mysql_query($query) or pupe_error($query);

						while($sarjarow = mysql_fetch_array($sarjares)) {							
							if ($tuoterow["sarjanumeroseuranta"] == "E" or $tuoterow["sarjanumeroseuranta"] == "F") {
								echo "<br>".t("E:nro").": $sarjarow[sarjanumero]";
							}
							else {
								echo "<br>".t("S:nro").": $sarjarow[sarjanumero]";
							}
						}
					}
														
					echo "</td>";
					echo "</tr>";
				}
			}
			echo "</table>";
			echo $divit;
		}
		else {
			echo "<font class='message'>".t("Yhtään tuotetta ei löytynyt")."!<br></font>";
		}
		$tee = '';
	}
	if ($ulos != "") {
			echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='Z'>";
			echo "<table><tr>";
			echo "<td>".t("Valitse listasta").":</td>";
			echo "<td>$ulos</td>";
			echo "<td class='back'><input type='Submit' value='".t("Valitse")."'></td>";
			echo "</tr></table>";
			echo "</form>";
	}

	require ("inc/footer.inc");
?>
