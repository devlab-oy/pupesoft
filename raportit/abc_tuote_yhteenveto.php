<?php

	echo "<font class='head'>".t("ABC-Analyysi‰: ABC-Luokkayhteenveto")." $yhtiorow[nimi]<hr></font>";

	// tutkaillaan saadut muuttujat
	$osasto 		= trim($osasto);
	$try    		= trim($try);
	$tuotemerkki    = trim($tuotemerkki);

	if ($osasto 	 == "")	$osasto 	 = trim($osasto2);
	if ($try    	 == "")	$try 		 = trim($try2);
	if ($tuotemerkki == "")	$tuotemerkki = trim($tuotemerkki2);

	if ($ed == 'on')	$chk = "CHECKED";
	else				$chk = "";

	// piirrell‰‰n formi
	echo "<form action='$PHP_SELF' method='post' autocomplete='OFF' id='abc_tuote_yhteenveto_form'>";
	echo "<input type='hidden' name='tee' value='YHTEENVETO'>";
	echo "<input type='hidden' name='toim' value='$toim'>";

	echo "<table>";

	echo "<tr>";
	echo "<th>".t("Syˆt‰ tai valitse osasto").":</th>";
	echo "<td><input type='text' name='osasto' size='10'></td>";

	// tehd‰‰n avainsana query
	$sresult = avainsana("OSASTO", $kukarow['kieli']);

	echo "<td><select name='osasto2'>";
	echo "<option value=''>".t("Osasto")."</option>";

	if ($osasto == "KAIKKI") $sel = "selected";
	echo "<option value='KAIKKI' $sel>".t("Osastoittain")."</option>";

	while ($srow = mysql_fetch_array($sresult)) {
		if ($osasto == $srow["selite"]) $sel = "selected";
		else $sel = "";
		echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
	}

	echo "</select></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Syˆt‰ tai valitse tuoteryhm‰").":</th>";
	echo "<td><input type='text' name='try' size='10'></td>";

	// tehd‰‰n avainsana query
	$sresult = avainsana("TRY", $kukarow['kieli']);

	echo "<td><select name='try2'>";
	echo "<option value=''>".t("Tuoteryhm‰")."</option>";

	if ($try == "KAIKKI") $sel = "selected";
	echo "<option value='KAIKKI' $sel>".t("Tuoteryhmitt‰in")."</option>";

	while ($srow = mysql_fetch_array($sresult)) {
		if ($try == $srow["selite"]) $sel = "selected";
		else $sel = "";
		echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
	}

	echo "</select></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Syˆt‰ tai valitse tuotemerkki").":</th>";
	echo "<td><input type='text' name='tuotemerkki' size='10'></td>";

	$query = "	SELECT distinct tuotemerkki
				FROM abc_aputaulu
				WHERE yhtio='$kukarow[yhtio]' and tuotemerkki != ''
				ORDER BY tuotemerkki";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='tuotemerkki2'>";
	echo "<option value=''>".t("Tuotemerkki")."</option>";

	if ($tuotemerkki == "KAIKKI") $sel = "selected";
	echo "<option value='KAIKKI' $sel>".t("Tuotemerkeitt‰in")."</option>";

	while ($srow = mysql_fetch_array($sresult)) {
		if ($tuotemerkki == $srow[0]) $sel = "selected";
		else $sel = "";
		echo "<option value='$srow[0]' $sel>$srow[0]</option>";
	}

	echo "</select></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Taso").":</th>";
	echo "<td></td>";

	if ($lisatiedot == 'TARK') {
		$sel1 = "selected";
		$sel2 = "";
	}
	elseif ($lisatiedot == 'OSTONOHJ') {
		$sel1 = "";
		$sel2 = "selected";
	}
	else {
		$sel1 = "";
		$sel2 = "";
	}

	echo "<td><select name='lisatiedot'>";
	echo "<option value=''>".t("Normaalitiedot")."</option>";
	echo "<option value='TARK' $sel1>".t("N‰ytet‰‰n kaikki sarakkeet")."</option>";
	echo "<option value='OSTONOHJ' $sel2>".t("N‰yt‰ simulointin‰kym‰")."</option>";
	echo "</select></td>";
	echo "<td class='back'><input type='submit' value='".t("Aja raportti")."'></td>";
	echo "</tr>";

	echo "</form>";
	echo "</table><br>";

	if ($tee == "YHTEENVETO") {

		echo "<table>";

		echo "<tr>";

		if (strlen($order) > 0) {
			$orderurl = $order.",";
		}

		if ($osasto == 'KAIKKI') {
			echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=YHTEENVETO&osasto=$osasto&try=$try&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&order=".$orderurl."osasto'>".t("Osasto")."</a><br>&nbsp;</th>";
		}

		if ($try == 'KAIKKI') {
			echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=YHTEENVETO&osasto=$osasto&try=$try&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&order=".$orderurl."try'>".t("Tuoteryhm‰")."</a><br>&nbsp;</th>";
		}

		if ($tuotemerkki == 'KAIKKI') {
			echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=YHTEENVETO&osasto=$osasto&try=$try&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&order=".$orderurl."tuotemerkki'>".t("Tuotemerkki")."</a><br>&nbsp;</th>";
		}

		if ($osasto != 'KAIKKI' and $try != 'KAIKKI' and $tuotemerkki != 'KAIKKI')  {
			echo "<th nowrap>".t("ABC")."<br>".t("Luokka")."</th>";
		}

		echo "<th nowrap>".t("Myynti")."<br>".t("tot")."</th>";

		if ($lisatiedot == "TARK") {
			echo "<th nowrap>".t("Myynti")."<br>".t("max")."</th>";
			echo "<th nowrap>".t("Myynti")."<br>".t("min")."</th>";
		}

		if ($lisatiedot != "OSTONOHJ") {
			echo "<th nowrap>".t("Kate")."<br>".t("tot")."</th>";
			echo "<th nowrap>".t("Kate")."<br>%</th>";
		}

		if ($lisatiedot == "TARK" or $lisatiedot == "OSTONOHJ") {
			if ($lisatiedot == "TARK") {
				echo "<th nowrap>".t("Osuus")." %<br>".t("kat").".</th>";
			}
			echo "<th nowrap>".t("Tuotteita")."<br>".t("KPL")."</th>";
		}

		echo "<th nowrap>".t("Varast").".<br>".t("arvo")."<br>".t("nyt")."</th>";

		if ($lisatiedot == "OSTONOHJ") {
			echo "<th nowrap>".t("Varast")."<br>".t("arvo")."<br>".t("tavoite")."</th>";
		}

		echo "<th nowrap>".t("Varast").".<br>".t("kiert").".<br>".t("nyt")."</th>";

		if ($lisatiedot == "OSTONOHJ") {
			echo "<th nowrap>".t("Varast").".<br>".t("kiert").".<br/>".t("tavoite")."</th>";
		}

		if ($lisatiedot != "OSTONOHJ") {
			echo "<th nowrap>".t("Kate")."% x<br>".t("kiert").".</th>";
			echo "<th nowrap>".t("Myydyt")."<br>".t("KPL")."</th>";
		}

		if ($lisatiedot == "TARK" or $lisatiedot == "OSTONOHJ") {
			if ($lisatiedot == "TARK") {
				echo "<th nowrap>".t("Myyer‰")."<br>".t("KPL")."</th>";
				echo "<th nowrap>".t("Myyer‰")."<br>$yhtiorow[valkoodi]</th>";
				echo "<th nowrap>".t("Myyty")."<br>".t("rivej‰")."</th>";
			}
			echo "<th nowrap>".t("Puute")."<br>".t("rivej‰")."<br>".t("nyt")."</th>";
			if ($lisatiedot == "OSTONOHJ") {
				echo "<th nowrap>".t("Puute")."<br>".t("rivej‰")."<br>".t("tavoite")."</th>";
			}
		}

		echo "<th nowrap>".t("Palvelu")."-<br>".t("taso")." %<br>".t("nyt")."</th>";

		if ($lisatiedot == "OSTONOHJ") {
			echo "<th nowrap>".t("Palvelu")."-<br>".t("taso")." %<br>".t("tavoite")."</th>";
			echo "<th nowrap>".t("Varm").".<br>".t("var")."</th>";
			echo "<th nowrap>".t("Hankinta")."-<br>".t("aika")."</th>";
			echo "<th nowrap>".t("Tilaus")."-<br>".t("piste")."</th>";
			echo "<th nowrap>".t("Keskiv").".</th>";
			echo "<th nowrap>".t("Ostoer‰")."<br>".t("pv")."</th>";
			echo "<th nowrap>".t("Saap").".<br>".t("lkm")."<br>".t("nyt")."</th>";
			echo "<th nowrap>".t("Saap").".<br>".t("lkm")."<br>".t("tavoite")."</th>";
			echo "<th nowrap>".t("Otot").".<br>".t("lkm")."</th>";
		}

		if ($lisatiedot == "TARK") {
			echo "<th nowrap>".t("Ostoer‰")."<br>".t("KPL")."</th>";
			echo "<th nowrap>".t("Ostoer‰")."<br>$yhtiorow[valkoodi]</th>";
			echo "<th nowrap>".t("Ostettu")."<br>".t("rivej‰")."</th>";
			echo "<th nowrap>".t("Myynn").".<br>".t("kustan").".</th>";
			echo "<th nowrap>".t("Oston")."<br>".t("kustan").".</th>";
			echo "<th nowrap>".t("Kustan").".<br>".t("yht")."</th>";
			echo "<th nowrap>".t("Kate -")."<br>".t("Kustanus")."</th>";
		}
		echo "</tr>\n";

		$osastolisa = $trylisa = $tuotemerkkilisa = "";

		if ($osasto != '' and $osasto != 'KAIKKI') {
			$osastolisa = " and osasto='$osasto' ";
		}
		if ($try != '' and $try != 'KAIKKI') {
			$trylisa = " and try='$try' ";
		}
		if ($tuotemerkki != '' and $tuotemerkki != 'KAIKKI') {
			$tuotemerkkilisa = " and tuotemerkki='$tuotemerkki' ";
		}

		//kauden yhteismyynnit ja katteet
		$query = "	SELECT
					sum(summa) yhtmyynti,
					sum(kate)  yhtkate
					FROM abc_aputaulu
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi='$abcchar'
					$osastolisa
					$trylisa
					$tuotemerkkilisa";
		$sumres = mysql_query($query) or pupe_error($query);
		$sumrow = mysql_fetch_array($sumres);

		if ($sumrow["yhtkate"] == 0) {
			$sumrow["yhtkate"] = 0.01;
		}

		$prequery = " SELECT ";
		$groupby  = " GROUP BY ";
		$orderby  = " ORDER BY ";

		if ($osasto == 'KAIKKI') {
			$prequery .= " osasto,";
			$groupby  .= " osasto,";
		}

		if ($try == 'KAIKKI') {
			$prequery .= " try,";
			$groupby  .= " try,";
		}

		if ($tuotemerkki == 'KAIKKI') {
			$prequery .= " tuotemerkki,";
			$groupby  .= " tuotemerkki,";
		}

		if ($osasto != 'KAIKKI' and $try != 'KAIKKI' and $tuotemerkki != 'KAIKKI')  {
			$prequery .= " luokka,";
			$groupby  .= " luokka,";
			$orderby  .= " luokka,";
		}
		$groupby  = substr($groupby, 0, -1);

		if (strlen($order) > 0) {
			$orderby  = " ORDER BY $order ";
		}
		else {
			$orderby .= " $abcwhat desc";
		}

		//haetaan luokkien arvot
		$query = "	$prequery
					count(tuoteno)						tuotelkm,
					group_concat(tuoteno SEPARATOR '<br>')				tuotenumerot,
					max(summa) 							max,
					min(summa)	 						min,
					sum(rivia) 							rivia,
					sum(kpl) 							kpl,
					sum(summa) 							summa,
					sum(kate) 							kate,
					sum(puutekpl) 						puutekpl,
					sum(puuterivia) 					puuterivia,
					sum(osto_rivia) 					osto_rivia,
					sum(osto_kpl) 						osto_kpl,
					sum(osto_summa) 					osto_summa,
					sum(vararvo) 						vararvo,
					sum(kustannus) 						kustannus,
					sum(kustannus_osto) 				kustannus_osto,
					sum(kustannus_yht) 					kustannus_yht,
					sum(osto_kerrat)					osto_kerrat,
					osto_kerrat							osto_kerrat2,
														kerrat,
					sum(osto_summa)/sum(osto_rivia) 	ostoeranarvo,
					sum(osto_kpl)/sum(osto_rivia) 		ostoeranakpl,
					sum(summa)/sum(rivia) 				myyntieranarvo,
					sum(kpl)/sum(rivia) 				myyntieranakpl,
					sum(kate)/sum(summa)*100 			kateprosentti,
					(sum(summa)-sum(kate))/sum(vararvo) kiertonopeus,
					(sum(kate)/sum(summa)*100) * ((sum(summa)-sum(kate))/sum(vararvo)) kate_kertaa_kierto,
					sum(kate)/$sumrow[yhtkate] * 100	kateosuus,
					100 - ((sum(puuterivia)/(sum(puuterivia)+sum(rivia))) * 100) palvelutaso,
					sum(kate)-sum(kustannus_yht)		total
					FROM abc_aputaulu
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi='$abcchar'
					$osastolisa
					$trylisa
					$tuotemerkkilisa
					$groupby
					$orderby";
		$res = mysql_query($query) or pupe_error($query);

		$ryhmanimet   = array('A-30','B-20','C-15','D-15','E-10','F-05','G-03','H-02','I-00');
		$ryhmaprossat = array(30.00,20.00,15.00,15.00,10.00,5.00,3.00,2.00,0.00);

		$i = 0;

		while ($row = mysql_fetch_array($res)) {
			
			if (strtolower($toim) == 'myynti') {
				$paramtyppi = "TM";
			}
			elseif (strtolower($toim) == 'kate') {
				$paramtyppi = "TK";
			}
			elseif (strtolower($toim) == 'kpl') {
				$paramtyppi = "TP";
			}
			elseif (strtolower($toim) == 'rivit') {
				$paramtyppi = "TR";
			}
			else {
				$paramtyppi = "";
			}
			
			$query = "SELECT * FROM abc_parametrit WHERE yhtio = '$kukarow[yhtio]' and tyyppi = '$paramtyppi' and luokka = '$ryhmanimet[$i]'";
			$paramres = mysql_query($query) or pupe_error($query);
			$paramrow = mysql_fetch_array($paramres);
			
			$i++;
			
			echo "<tr>";
			
			if ($osasto == 'KAIKKI') {
				// tehd‰‰n avainsana query
				$keyres = avainsana("OSASTO", $kukarow['kieli'], $row["osasto"]);
				$keyosa = mysql_fetch_array($keyres);

				echo "<td valign='top'><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&osasto=$row[osasto]&lisatiedot=$lisatiedot'>$row[osasto] $keyosa[selitetark]</a></td>";
			}

			if ($try == 'KAIKKI') {
				// tehd‰‰n avainsana query
				$keyres = avainsana("TRY", $kukarow['kieli'], $row["try"]);
				$keytry = mysql_fetch_array($keyres);

				echo "<td valign='top'><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&try=$row[try]&lisatiedot=$lisatiedot'>$row[try] $keytry[selitetark]</a></td>";
			}

			if ($tuotemerkki == 'KAIKKI') {
				echo "<td valign='top'><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&tuotemerkki=$row[tuotemerkki]&lisatiedot=$lisatiedot'>$row[tuotemerkki]</a></td>";
			}

			if ($osasto != 'KAIKKI' and $try != 'KAIKKI' and $tuotemerkki != 'KAIKKI')  {
				echo "<td valign='top'><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$row[luokka]&osasto=$osasto&try=$try&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot'>".$ryhmanimet[$row["luokka"]]."</a></td>";
			}

			echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["summa"]));
			echo "<input type='hidden' name='summa_$i' id='summa_$i' value='$row[summa]'></td>";

			if ($lisatiedot == "TARK") {
				echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["max"]))."</td>";
				echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["min"]))."</td>";
			}

			if ($lisatiedot != "OSTONOHJ") {
				echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kate"]))."</td>";
				echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kateprosentti"]))."</td>";
			}

			if ($lisatiedot == "TARK" or $lisatiedot == "OSTONOHJ") {
				if ($lisatiedot == "TARK") {
					echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kateosuus"]))."</td>";
				}
				echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.0f',$row["tuotelkm"]))."</td>";
			}

			echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["vararvo"]))."</td>";

			if ($lisatiedot == "OSTONOHJ") {
				echo "<td align='right' valign='top' nowrap>";
				echo "<input type='text' size='10' name='vararvo_$i' id='vararvo_$i' value='' disabled>";
				echo "<input type='hidden' name='kate_$i' id='kate_$i' value='$row[kate]'>";
				echo "</td>";
			}

			echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kiertonopeus"]))."</td>";

			if ($lisatiedot == "OSTONOHJ") {								
				if (${'kiertonopeus_input'.$i} == '') {
					$kntavoitevalue = str_replace(".",",",$paramrow["kiertonopeus_tavoite"]);
				}
				else {
					$kntavoitevalue = ${'kiertonopeus_input'.$i};
				}
				
				echo "<td align='center' valign='top' nowrap><input type='text' size='5' name='kiertonopeus_input$i' id='kiertonopeus_input$i' value='$kntavoitevalue' onkeyup='kutsu_update(\"abc_tuote_yhteenveto_form\");'></td>";
			}

			if ($lisatiedot != "OSTONOHJ") {
				echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kate_kertaa_kierto"]))."</td>";
				echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.0f',$row["kpl"]))."</td>";
			}

			if ($lisatiedot == "TARK" or $lisatiedot == "OSTONOHJ") {
				if ($lisatiedot == "TARK") {
					echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["myyntieranakpl"]))."</td>";
					echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["myyntieranarvo"]))."</td>";
					echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.0f',$row["rivia"]))."</td>";
				}
				echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.0f',$row["puuterivia"]))."</td>";
				if ($lisatiedot == "OSTONOHJ") {
					echo "<td align='right' valign='top' nowrap></td>";
				}
			}

			echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["palvelutaso"]))."</td>";

			if ($lisatiedot == "OSTONOHJ") {
				$ostoera_paivissa = 0;
				
//				365 p‰iv‰‰ /(( vuosimyynti yht kpl/(ostot yht kp/ ostokerrat ))

//				Jos vuosimyyti on 500 kpl  
//				ostettu tuotetta 20 kertaa eli yhteens‰ 600 kpl
				
//				kertaosto keskim‰‰rin 30 kpl
//				eli noin 22 p‰iv‰‰ suosimyyntiin n‰hden.

/*
				Vuosimyynti m‰‰r‰ = 500
				Vuosiostot m‰‰r‰ = 600
				Vuosiostot 20 kertaa

				T‰st‰ alla olevalla kaavalla tulisi noin 22 (21,9) p‰iv‰‰ riit‰isi ostoer‰.

				Keskim‰‰r‰inen ostoer‰ on 30 kpl.
				Keskim‰‰rin myyd‰‰n 1,37 ( 500(365) kappaletta p‰iv‰ss‰ eli 30 kpl antaa
				n‰in tuon  noin 22 (21,9) p‰iv‰‰ (30/1,37).
				T‰ss‰ t‰m‰ viimeinen vai tarkistuslaskenta.
*/

				//$ostoera_paivissa = round(($row["osto_kpl"] / $row["osto_kerrat"]) / ($row["kpl"] / 365), 2);
				
				if ($ryhmanimet[$row["luokka"]] == 'A-30' and 1==2) {
					echo "<pre>";
					echo "365 / ($row[kpl] / ($row[osto_kpl] / $row[osto_kerrat]))<br>";
					echo $ryhmanimet[$row["luokka"]]."<br>";
					echo "Vuosimyynti m‰‰r‰ = $row[kpl]<br>";
					echo "Vuosiostot m‰‰r‰ = $row[osto_kpl]<br>";
					echo "Vuosiostot $row[osto_kerrat] kertaa<br><br>";

					echo "Keskim‰‰r‰inen ostoer‰ on ".round(($row["osto_kpl"] / $row["osto_kerrat"]), 0)." kpl. (".round($row["osto_kpl"],0)." / $row[osto_kerrat])<br>";
					echo "Keskim‰‰rin myyd‰‰n ".round(($row["kpl"] / 365), 2)." ($row[kpl] / 365) kappaletta p‰iv‰ss‰<br>";
					echo "Vuodessa myyd‰‰n rivi‰: $row[rivia]<br>";
					echo "eli ".round(($row["osto_kpl"] / $row["osto_kerrat"]), 0)." antaa n‰in tuon noin ".round(($row["osto_kpl"] / $row["osto_kerrat"]) / ($row["kpl"] / 365), 2)." p‰iv‰‰ (".round(($row["osto_kpl"] / $row["osto_kerrat"]),2)." / ".round(($row["kpl"] / 365),2).")<br><br>";

					echo "$row[tuotenumerot]<br>";
					echo "KPL: $row[kpl] &#09; OSTO_KPL: $row[osto_kpl] &#09; OSTO_KERRAT: $row[osto_kerrat] &#09; OSTO_RIVIT: $row[osto_rivia]<br>";
					
					echo "Saapumisten tavoite: ".round(($row["osto_kerrat"]/str_replace(",",".",$row["kiertonopeus"]))*str_replace(",",".",$kntavoite),0)." eli ($row[osto_kerrat]/$row[kiertonopeus])*$kntavoite";
					
					
					echo "</pre>";
				}
				

				
								
				if (${'palvelutaso_input'.$i} == '') {
					$pttvalue = str_replace(".",",",$paramrow["palvelutaso_tavoite"]);
				}
				else {
					$pttvalue = ${'palvelutaso_input'.$i};
				}
				
				echo "<td align='center' valign='top' nowrap><input type='text' size='6' name='palvelutaso_input$i' id='palvelutaso_input$i' value='$pttvalue' onkeyup='kutsu_update(\"abc_tuote_yhteenveto_form\");'></td>";
				
				if (${'varmuusvarasto_input'.$i} == '') {
					$varmvarvalue = str_replace(".",",",$paramrow["varmuusvarasto_pv"]);
				}
				else {
					$varmvarvalue = ${'varmuusvarasto_input'.$i};
				}
				
				echo "<td align='center' valign='top' nowrap><input type='text' size='5' name='varmuusvarasto_input$i' id='varmuusvarasto_input$i' value='$varmvarvalue' onkeyup='kutsu_update(\"abc_tuote_yhteenveto_form\");'></td>";
				
				if (${'hankintaaika_input'.$i} == '') {
					$haikavalue = str_replace(".",",",$paramrow["toimittajan_toimitusaika_pv"]);
				}
				else {
					$haikavalue = ${'hankintaaika_input'.$i};
				}
				
				echo "<td align='center' valign='top' nowrap><input type='text' size='5' name='hankintaaika_input$i' id='hankintaaika_input$i' value='$haikavalue' onkeyup='kutsu_update(\"abc_tuote_yhteenveto_form\");'></td>";
				echo "<td align='center' valign='top' nowrap><input type='text' size='5' name='tilauspiste$i' id='tilauspiste$i' value='' disabled></td>";
				
				echo "<td align='center' valign='top' nowrap><input type='text' size='5' name='keskivarasto$i' id='keskivarasto$i' value='' disabled></td>";
				
				$ostoera_paivissa = round(((365/$kntavoitevalue)-$varmvarvalue)*2, 2);
				
				echo "<td align='center' valign='top' nowrap><input type='text' size='6' name='ostoera_pv_$i' id='ostoera_pv_$i' value='$ostoera_paivissa' disabled></td>";
				echo "<td align='center' valign='top' nowrap><input type='text' size='5' name='saap_lkm_nyt_$i' id='saap_lkm_nyt_$i' value='$row[osto_kerrat]' disabled></td>";
				
				$saaplkmtavoite = round((365/$ostoera_paivissa)*$row["tuotelkm"],0);
				echo "<input type='hidden' name='tuote_lkm_$i' id='tuote_lkm_$i' value='$row[tuotelkm]'>";
				echo "<td align='center' valign='top' nowrap><input type='text' size='5' name='saap_lkm_tavoite_$i' id='saap_lkm_tavoite_$i' value='$saaplkmtavoite' disabled></td>";
				echo "<td align='center' valign='top' nowrap><input type='text' size='5' name='otot_lkm_$i' id='otot_lkm_$i' value='$row[rivia]' disabled></td>";
			}

			if ($lisatiedot == "TARK") {
				echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["ostoeranakpl"]))."</td>";
				echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["ostoeranarvo"]))."</td>";
				echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.0f',$row["osto_rivia"]))."</td>";
				echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kustannus"]))."</td>";
				echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kustannus_osto"]))."</td>";
				echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kustannus_yht"]))."</td>";
				echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["total"]))."</td>";
			}
			echo "</tr>\n";

			$tuotelkmyht			+= $row["tuotelkm"];
			$ryhmamyyntiyht 		+= $row["summa"];
			$ryhmakateyht   		+= $row["kate"];
			$ryhmanvarastonarvoyht 	+= $row["vararvo"];
			$rivilkmyht				+= $row["rivia"];
			$ryhmakplyht			+= $row["kpl"];
			$ryhmapuuteyht			+= $row["puutekpl"];
			$ryhmapuuterivityht		+= $row["puuterivia"];
			$ryhmaostotyht 	 		+= $row["osto_summa"];
			$ryhmaostotkplyht		+= $row["osto_kpl"];
			$ryhmaostotrivityht 	+= $row["osto_rivia"];
			$ryhmakustamyyyht		+= $row["kustannus"];
			$ryhmakustaostyht		+= $row["kustannus_osto"];
			$ryhmakustayhtyht		+= $row["kustannus_yht"];
			$totalyht				+= $row["total"];
		}

		//yhteens‰rivi
		if ($ryhmamyyntiyht != 0) $kateprosenttiyht = round($ryhmakateyht / $ryhmamyyntiyht * 100,2);
		else $kateprosenttiyht = 0;

		if ($sumrow["yhtkate"] != 0) $kateosuusyht = round($ryhmakateyht / $sumrow["yhtkate"] * 100,2);
		else $kateosuusyht = 0;

		if ($ryhmanvarastonarvoyht != 0) $kiertonopeusyht = round(($ryhmamyyntiyht - $ryhmakateyht) / $ryhmanvarastonarvoyht,2);
		else $kiertonopeusyht = 0;

		if ($rivilkmyht != 0) $myyntieranarvoyht = round($ryhmamyyntiyht / $rivilkmyht,2);
		else $myyntieranarvoyht = 0;

		if ($rivilkmyht != 0) $myyntieranakplyht = round($ryhmakplyht / $rivilkmyht,2);
		else $myyntieranakplyht = 0;

		if ($ryhmapuuterivityht + $rivilkmyht != 0)	$palvelutasoyht = round(100 - ($ryhmapuuterivityht / ($ryhmapuuterivityht + $rivilkmyht) * 100),2);
		else $palvelutasoyht = 0;

		if ($ryhmaostotrivityht != 0) $ostoeranarvoyht = round($ryhmaostotyht / $ryhmaostotrivityht,2);
		else $ostoeranarvoyht = 0;

		if ($ryhmaostotrivityht != 0) $ostoeranakplyht = round($ryhmaostotkplyht / $ryhmaostotrivityht,2);
		else $ostoeranakplyht = 0;

		if ($ryhmamyyntiyht != 0 and $ryhmanvarastonarvoyht != 0) {
			$kate_kertaa_kierto = round(($ryhmakateyht / $ryhmamyyntiyht * 100) * (($ryhmamyyntiyht - $ryhmakateyht) / $ryhmanvarastonarvoyht), 2);
		}
		else {
			$kate_kertaa_kierto = 0;
		}

		echo "<tr>";

		$colspan = 0;

		if ($osasto == 'KAIKKI') {
			$colspan++;
		}

		if ($try == 'KAIKKI') {
			$colspan++;
		}

		if ($tuotemerkki == 'KAIKKI') {
			$colspan++;
		}

		if ($osasto != 'KAIKKI' and $try != 'KAIKKI' and $tuotemerkki != 'KAIKKI')  {
			$colspan++;
		}

		echo "<td colspan='$colspan'>".t("Yhteens‰").":</td>";
		echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ryhmamyyntiyht));
		echo "<input type='hidden' name='summa_yht' id='summa_yht' value='$ryhmamyyntiyht'>";
		echo "<input type='hidden' name='kate_yht' id='kate_yht' value='$ryhmakateyht'>";
		echo "</td>";

		if ($lisatiedot == "TARK") {
			echo "<td align='right' class='spec' nowrap></td>";
			echo "<td align='right' class='spec' nowrap></td>";
		}

		if ($lisatiedot != "OSTONOHJ") {
			echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ryhmakateyht))."</td>";
			echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$kateprosenttiyht))."</td>";
		}

		if ($lisatiedot == "TARK" or $lisatiedot == "OSTONOHJ") {
			if ($lisatiedot == "TARK") {
				echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$kateosuusyht))."</td>";
			}
			echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.0f',$tuotelkmyht))."</td>";
		}

		echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ryhmanvarastonarvoyht))."</td>";

		if ($lisatiedot == "OSTONOHJ") {
			echo "<td align='right' valign='top' nowrap><input type='text' size='10' name='vararvo_yht' id='vararvo_yht' value='' disabled></td>";
		}

		echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$kiertonopeusyht))."</td>";

		if ($lisatiedot == "OSTONOHJ") {
			echo "<td align='center' class='spec' nowrap><input type='text' size='5' name='kiertonopeus_input_yht' id='kiertonopeus_input_yht' value='' disabled></td>";
		}

		if ($lisatiedot != "OSTONOHJ") {
			echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$kate_kertaa_kierto))."</td>";
			echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.0f',$ryhmakplyht))."</td>";
		}

		if ($lisatiedot == "TARK" or $lisatiedot == "OSTONOHJ") {
			if ($lisatiedot == "TARK") {
				echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$myyntieranakplyht))."</td>";
				echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$myyntieranarvoyht))."</td>";
				echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.0f',$rivilkmyht))."</td>";
			}
			echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.0f',$ryhmapuuterivityht))."</td>";
			if ($lisatiedot == "OSTONOHJ") {
				echo "<td align='center' valign='top' nowrap></td>";
			}
		}

		echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$palvelutasoyht))."</td>";

		if ($lisatiedot == "OSTONOHJ") {
			echo "<td align='center' class='spec' nowrap><input type='text' size='5' name='palvelutaso_input_yht' id='palvelutaso_input_yht' value='100' disabled></td>";
			echo "<td align='center' class='spec' nowrap></td>";
			echo "<td align='center' class='spec' nowrap></td>";
			echo "<td align='center' class='spec' nowrap></td>";
			echo "<td align='center' class='spec' nowrap></td>";
			echo "<td align='center' class='spec' nowrap></td>";
			echo "<td align='center' class='spec' nowrap></td>";
			echo "<td align='center' class='spec' nowrap></td>";
			echo "<td align='center' class='spec' nowrap name='otot_lkm_yht' id='otot_lkm_yht'></td>";
		}

		if ($lisatiedot == "TARK") {
			echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ostoeranakplyht))."</td>";
			echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ostoeranarvoyht))."</td>";
			echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.0f',$ryhmaostotrivityht))."</td>";
			echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ryhmakustamyyyht))."</td>";
			echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ryhmakustaostyht))."</td>";
			echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ryhmakustayhtyht))."</td>";
			echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$totalyht))."</td>";
		}

		echo "</tr>\n";

		echo "</table>";

		echo "	<script type='text/javascript' language='JavaScript'>
				<!--
					
					function kutsu_update(ID) {
						update_kiertonopeus(ID);
						update_palvelutaso(ID);
						update_varmuusvarasto(ID);
						update_hankintaaika(ID);
					}
					
					function update_kiertonopeus(ID) {
						obj = document.getElementById(ID);
						var summa = 0;
						var pointer = '';
						var counter = 0;
						var myynti = 0;
						var kate = 0;
						var keskivar_tavoite = 0;
						var keskivar_summa = 0;
						var myynti_summa = 0;
						var kate_summa = 0;
						var kiertonopeus_summa = 0;
						var ostoera_paivissa = 0;
						var saaplkmtavoite = 0;
						var palvelutasokerr = 0;

				 		for (i=0; i<obj.length; i++) {

							if (obj.elements[i].value != null) {
								if (obj.elements[i].id.substring(0,18) == ('kiertonopeus_input') && obj.elements[i].id.substring(0,22) != ('kiertonopeus_input_yht')) {
									if (obj.elements[i].value != '' && obj.elements[i].value != null && obj.elements[i].value != 0) {
										if (!isNaN(obj.elements[i].id.substring(18,20))) {
											pointer = obj.elements[i].id.substring(18,20);
											summa += Number(document.getElementById('kiertonopeus_input'+pointer).value.replace(\",\",\".\"));
											
											myynti = Number(document.getElementById('summa_'+pointer).value.replace(\",\",\".\"));
											kate = Number(document.getElementById('kate_'+pointer).value.replace(\",\",\".\"));
											keskivar_tavoite = (myynti - kate) / Number(document.getElementById('kiertonopeus_input'+pointer).value.replace(\",\",\".\"));
											keskivar_summa += keskivar_tavoite;
											//$ostoera_paivissa = round(((365/$kntavoitevalue)-$varmvarvalue)*2, 2);
											ostoera_paivissa = ((365/Number(document.getElementById('kiertonopeus_input'+pointer).value.replace(\",\",\".\")))-Number(document.getElementById('varmuusvarasto_input'+pointer).value.replace(\",\",\".\")))*2;
											//$saaplkmtavoite = round((365/$ostoera_paivissa)*$row[tuotelkm],0);
											saaplkmtavoite = (365/ostoera_paivissa)*Number(document.getElementById('tuote_lkm_'+pointer).value.replace(\",\",\".\"));
											ostoera_paivissa = ostoera_paivissa*Number(document.getElementById('palvelutaso_input'+pointer).value.replace(\",\",\".\")) / 100;
											
											document.getElementById('vararvo_'+pointer).value = keskivar_tavoite.toFixed(1);
											document.getElementById('ostoera_pv_'+pointer).value = ostoera_paivissa.toFixed(2);
											document.getElementById('saap_lkm_tavoite_'+pointer).value = saaplkmtavoite.toFixed(0);
										}
									}
								}

								counter++;

								if (obj.elements[i].id == ('kiertonopeus_input_yht')) {
									myynti_summa = Number(document.getElementById('summa_yht').value.replace(\",\",\".\"));
									kate_summa = Number(document.getElementById('kate_yht').value.replace(\",\",\".\"));

									kiertonopeus_summa = (myynti_summa - kate_summa) / keskivar_summa;

									if (isNaN(kiertonopeus_summa)) {
										kiertonopeus_summa = 0;
									}
									
									document.getElementById('kiertonopeus_input_yht').value = kiertonopeus_summa.toFixed(1);
									document.getElementById('kiertonopeus_input_yht').style.color = 'darkgreen';
								}

								if (obj.elements[i].id == ('vararvo_yht')) {
									document.getElementById('vararvo_yht').value = keskivar_summa.toFixed(1);
									document.getElementById('vararvo_yht').style.color = 'darkgreen';
								}
							}
						}
					}

					function update_palvelutaso(ID) {
						obj = document.getElementById(ID);
						var summa = 0;
						var loppusumma = 0;
						var pointer = '';
						var counter = 0;

				 		for (i=0; i<obj.length; i++) {

							if (obj.elements[i].value != null) {
								if (obj.elements[i].id.substring(0,17) == ('palvelutaso_input') && obj.elements[i].id.substring(0,21) != ('palvelutaso_input_yht')) {
									counter++;

									if (obj.elements[i].value != '' && obj.elements[i].value != null && obj.elements[i].value != 0) {
										if (!isNaN(obj.elements[i].id.substring(17,19))) {
											pointer = obj.elements[i].id.substring(17,19);
											summa += Number(document.getElementById('palvelutaso_input'+pointer).value.replace(\",\",\".\"));
										}
									}
								}


								if (obj.elements[i].id == ('palvelutaso_input_yht')) {
									loppusumma = summa / counter;
									if (isNaN(loppusumma)) {
										loppusumma = 0;
									}
									document.getElementById('palvelutaso_input_yht').value = loppusumma.toFixed(1);
									document.getElementById('palvelutaso_input_yht').style.color = 'darkgreen';
								}
							}
						}
					}

					function update_varmuusvarasto(ID) {
						obj = document.getElementById(ID);
						var summa = 0;
						var loppusumma = 0;
						var pointer = '';
						var counter = 0;
						var keskivar = 0;
						var ostoera = 0;

				 		for (i=0; i<obj.length; i++) {

							if (obj.elements[i].value != null) {
								if (obj.elements[i].id.substring(0,20) == ('varmuusvarasto_input') && obj.elements[i].id.substring(0,24) != ('varmuusvarasto_input_yht')) {
									// && obj.elements[i].value != 0
									if (obj.elements[i].value != '' && obj.elements[i].value != null) {
										if (!isNaN(obj.elements[i].id.substring(20,22))) {
											pointer = obj.elements[i].id.substring(20,22);
											summa += Number(document.getElementById('varmuusvarasto_input'+pointer).value.replace(\",\",\".\"));
											
											//ostoera = Number(document.getElementById('ostoera_pv_'+pointer).value.replace(\",\",\".\"));
											//keskivar = Number(document.getElementById('varmuusvarasto_input'+pointer).value.replace(\",\",\".\"));
											//keskivar = keskivar + (ostoera / 2);
											keskivar = 365 / Number(document.getElementById('kiertonopeus_input'+pointer).value.replace(\",\",\".\"));
											document.getElementById('keskivarasto'+pointer).value = keskivar.toFixed(1);
											
											document.getElementById('tilauspiste'+pointer).value = Number(document.getElementById('hankintaaika_input'+pointer).value.replace(\",\",\".\")) + Number(document.getElementById('varmuusvarasto_input'+pointer).value.replace(\",\",\".\"));
										}
									}
								}

								counter++;
							}
						}
					}

					function update_hankintaaika(ID) {
						obj = document.getElementById(ID);
						var summa = 0;
						var loppusumma = 0;
						var pointer = '';
						var counter = 0;
						var varmuusvar = 0;

				 		for (i=0; i<obj.length; i++) {

							if (obj.elements[i].value != null) {
								if (obj.elements[i].id.substring(0,18) == ('hankintaaika_input') && obj.elements[i].id.substring(0,22) != ('hankintaaika_input_yht')) {
									if (obj.elements[i].value != '' && obj.elements[i].value != null && obj.elements[i].value != 0) {
										if (!isNaN(obj.elements[i].id.substring(18,20))) {
											pointer = obj.elements[i].id.substring(18,20);
											summa += Number(document.getElementById('hankintaaika_input'+pointer).value.replace(\",\",\".\"));
											
											varmuusvar = Number(document.getElementById('varmuusvarasto_input'+pointer).value.replace(\",\",\".\"));

											// lasketaan tilauspiste
											document.getElementById('tilauspiste'+pointer).value = Number(document.getElementById('hankintaaika_input'+pointer).value.replace(\",\",\".\")) + varmuusvar;

											
										}
									}
								}

								counter++;
							}
						}
					}
					
					kutsu_update(\"abc_tuote_yhteenveto_form\");
					
				-->
				</script>";

	}

?>