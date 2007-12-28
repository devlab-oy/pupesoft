<?php

	echo "<font class='head'>".t("ABC-Analyysiä: ABC-Luokkayhteenveto")." $yhtiorow[nimi]<hr></font>";

	// tutkaillaan saadut muuttujat
	$osasto 		= trim($osasto);
	$try    		= trim($try);
	$tuotemerkki    = trim($tuotemerkki);

	if ($osasto 	 == "")	$osasto 	 = trim($osasto2);
	if ($try    	 == "")	$try 		 = trim($try2);
	if ($tuotemerkki == "")	$tuotemerkki = trim($tuotemerkki2);

	if ($ed == 'on')	$chk = "CHECKED";
	else				$chk = "";

	// piirrellään formi
	echo "<form action='$PHP_SELF' method='post' autocomplete='OFF'>";
	echo "<input type='hidden' name='tee' value='YHTEENVETO'>";
	echo "<input type='hidden' name='toim' value='$toim'>";
	
	echo "<table>";

	echo "<tr>";
	echo "<th>".t("Syötä tai valitse osasto").":</th>";
	echo "<td><input type='text' name='osasto' size='10'></td>";

	$query = "	SELECT distinct avainsana.selite, ".avain('select')."
				FROM avainsana
				".avain('join','OSASTO_')."
				WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='OSASTO'
				ORDER BY selite";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='osasto2'>";
	echo "<option value=''>".t("Osasto")."</option>";

	while ($srow = mysql_fetch_array($sresult)) {
		if ($osasto == $srow[0]) $sel = "selected";
		else $sel = "";
		echo "<option value='$srow[0]' $sel>$srow[0] $srow[1]</option>";
	}

	echo "</select></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Syötä tai valitse tuoteryhmä").":</th>";
	echo "<td><input type='text' name='try' size='10'></td>";

	$query = "	SELECT distinct avainsana.selite, ".avain('select')."
				FROM avainsana
				".avain('join','TRY_')."
				WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='TRY'
				ORDER BY selite";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='try2'>";
	echo "<option value=''>".t("Tuoteryhmä")."</option>";

	while ($srow = mysql_fetch_array($sresult)) {
		if ($try == $srow[0]) $sel = "selected";
		else $sel = "";
		echo "<option value='$srow[0]' $sel>$srow[0] $srow[1]</option>";
	}

	echo "</select></td>";
	echo "</tr>";
	
	echo "<tr>";
	echo "<th>".t("Syötä tai valitse tuotemerkki").":</th>";
	echo "<td><input type='text' name='tuotemerkki' size='10'></td>";

	$query = "	SELECT distinct tuotemerkki
				FROM abc_aputaulu
				WHERE yhtio='$kukarow[yhtio]' and tuotemerkki != ''
				ORDER BY tuotemerkki";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='tuotemerkki2'>";
	echo "<option value=''>".t("Tuotemerkki")."</option>";

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
	
	if ($lisatiedot != '') $sel = "selected";
	else $sel = "";
	
	echo "<td><select name='lisatiedot'>";
	echo "<option value=''>".t("Normaalitiedot")."</option>";
	echo "<option value='TARK' $sel>".t("Näytetään kaikki sarakkeet")."</option>";
	echo "</select></td>";
	echo "<td class='back'><input type='submit' value='".t("Aja raportti")."'></td>";
	echo "</tr>";
	
	echo "</form>";
	echo "</table><br>";
	
	if ($tee == "YHTEENVETO") {

		echo "<table>";

		echo "<tr>";
		echo "<th nowrap>".t("ABC")."<br>".t("Luokka")."</th>";
		echo "<th nowrap>".t("Myynti")."<br>".t("tot")."</th>";
		if ($lisatiedot == "TARK") echo "<th nowrap>".t("Myynti")."<br>".t("max")."</th>";
		if ($lisatiedot == "TARK") echo "<th nowrap>".t("Myynti")."<br>".t("min")."</th>";
		echo "<th nowrap>".t("Kate")."<br>".t("tot")."</th>";
		echo "<th nowrap>".t("Kate")."<br>%</th>";
		if ($lisatiedot == "TARK") echo "<th nowrap>".t("Osuus")." %<br>".t("kat").".</th>";
		if ($lisatiedot == "TARK") echo "<th nowrap>".t("Tuotteita")."<br>".t("KPL")."</th>";
		echo "<th nowrap>".t("Varast").".<br>".t("arvo")."</th>";
		echo "<th nowrap>".t("Varast").".<br>".t("kiert").".</th>";
		echo "<th nowrap>".t("Kate")." x<br>".t("kiert").".</th>";
		echo "<th nowrap>".t("Myydyt")."<br>".t("KPL")."</th>";
		if ($lisatiedot == "TARK") echo "<th nowrap>".t("Myyerä")."<br>".t("KPL")."</th>";
		if ($lisatiedot == "TARK") echo "<th nowrap>".t("Myyerä")."<br>$yhtiorow[valkoodi]</th>";
		if ($lisatiedot == "TARK") echo "<th nowrap>".t("Myyty")."<br>".t("rivejä")."</th>";
		if ($lisatiedot == "TARK") echo "<th nowrap>".t("Puute")."<br>".t("rivejä")."</th>";
		echo "<th nowrap>".t("Palvelu")."-<br>".t("taso")." %</th>";
		if ($lisatiedot == "TARK") echo "<th nowrap>".t("Ostoerä")."<br>".t("KPL")."</th>";
		if ($lisatiedot == "TARK") echo "<th nowrap>".t("Ostoerä")."<br>$yhtiorow[valkoodi]</th>";
		if ($lisatiedot == "TARK") echo "<th nowrap>".t("Ostettu")."<br>".t("rivejä")."</th>";
		if ($lisatiedot == "TARK") echo "<th nowrap>".t("Myynn").".<br>".t("kustan").".</th>";
		if ($lisatiedot == "TARK") echo "<th nowrap>".t("Oston")."<br>".t("kustan").".</th>";
		if ($lisatiedot == "TARK") echo "<th nowrap>".t("Kustan").".<br>".t("yht")."</th>";
		if ($lisatiedot == "TARK") echo "<th nowrap>".t("Kate -")."<br>".t("Kustanus")."</th>";
		echo "</tr>\n";

		$osastolisa = $trylisa = $tuotemerkkilisa = "";

		if ($osasto != '') {
			$osastolisa = " and osasto='$osasto' ";
		}
		if ($try != '') {
			$trylisa = " and try='$try' ";
		}
		if ($tuotemerkki != '') {
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

		//haetaan luokkien arvot
		$query = "	SELECT
					luokka,
					count(tuoteno)						tuotelkm,
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
					sum(osto_summa)/sum(osto_rivia) 	ostoeranarvo,
					sum(osto_kpl)/sum(osto_rivia) 		ostoeranakpl,
					sum(summa)/sum(rivia) 				myyntieranarvo,
					sum(kpl)/sum(rivia) 				myyntieranakpl,
					sum(kate)/sum(summa)*100 			kateprosentti,
					(sum(summa)-sum(kate))/sum(vararvo) kiertonopeus,
					sum(kate) * ((sum(summa)-sum(kate))/sum(vararvo)) kate_kertaa_kierto,
					sum(kate)/$sumrow[yhtkate] * 100	kateosuus,
					100 - ((sum(puuterivia)/(sum(puuterivia)+sum(rivia))) * 100) palvelutaso,
					sum(kate)-sum(kustannus_yht)		total
					FROM abc_aputaulu
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi='$abcchar'
					$osastolisa
					$trylisa
					$tuotemerkkilisa
					GROUP BY luokka
					ORDER BY luokka, $abcwhat desc";
		$res = mysql_query($query) or pupe_error($query);

		$ryhmanimet   = array('A-30','B-20','C-15','D-15','E-10','F-05','G-03','H-02','I-00');
		$ryhmaprossat = array(30.00,20.00,15.00,15.00,10.00,5.00,3.00,2.00,0.00);

		while ($row = mysql_fetch_array($res)) {
			
			echo "<tr>";
			echo "<td valign='top'><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$row[luokka]&osasto=$osasto&try=$try&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot'>".$ryhmanimet[$row["luokka"]]."</a></td>";			
			echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["summa"]))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["max"]))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["min"]))."</td>";
			echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kate"]))."</td>";
			echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kateprosentti"]))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kateosuus"]))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.0f',$row["tuotelkm"]))."</td>";
			echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["vararvo"]))."</td>";
			echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kiertonopeus"]))."</td>";			
			echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kate_kertaa_kierto"]))."</td>";			
			echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.0f',$row["kpl"]))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["myyntieranakpl"]))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["myyntieranarvo"]))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.0f',$row["rivia"]))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.0f',$row["puuterivia"]))."</td>";
			echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["palvelutaso"]))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["ostoeranakpl"]))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["ostoeranarvo"]))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.0f',$row["osto_rivia"]))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kustannus"]))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kustannus_osto"]))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kustannus_yht"]))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["total"]))."</td>";
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

		//yhteensärivi
		if ($ryhmamyyntiyht != 0) $kateprosenttiyht = round ($ryhmakateyht / $ryhmamyyntiyht * 100,2);
		else $kateprosenttiyht = 0;

		if ($sumrow["yhtkate"] != 0) $kateosuusyht = round ($ryhmakateyht / $sumrow["yhtkate"] * 100,2);
		else $kateosuusyht = 0;

		if ($ryhmanvarastonarvoyht != 0) {
			$kiertonopeusyht 	= round (($ryhmamyyntiyht - $ryhmakateyht) / $ryhmanvarastonarvoyht,2);
			$kate_kertaa_kierto = round($ryhmakateyht*(($ryhmamyyntiyht - $ryhmakateyht) / $ryhmanvarastonarvoyht),2);
		}
		else { 
			$kiertonopeusyht 	= 0;
			$kate_kertaa_kierto = 0;
		}

		if ($rivilkmyht != 0)	$myyntieranarvoyht = round ($ryhmamyyntiyht / $rivilkmyht,2);
		else $myyntieranarvoyht = 0;

		if ($rivilkmyht != 0)	$myyntieranakplyht = round ($ryhmakplyht / $rivilkmyht,2);
		else $myyntieranakplyht = 0;

		if ($ryhmapuuterivityht + $rivilkmyht != 0)	$palvelutasoyht = round (100 - ($ryhmapuuterivityht / ($ryhmapuuterivityht + $rivilkmyht) * 100),2);
		else $palvelutasoyht = 0;

		if ($ryhmaostotrivityht != 0)	$ostoeranarvoyht = round ($ryhmaostotyht / $ryhmaostotrivityht,2);
		else $ostoeranarvoyht = 0;

		if ($ryhmaostotrivityht != 0)	$ostoeranakplyht = round ($ryhmaostotkplyht / $ryhmaostotrivityht,2);
		else $ostoeranakplyht = 0;

		echo "<tr>";
		echo "<td>".t("Yhteensä").":</td>";
		echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ryhmamyyntiyht))."</td>";
		if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap></td>";
		if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap></td>";
		echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ryhmakateyht))."</td>";
		echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$kateprosenttiyht))."</td>";
		if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$kateosuusyht))."</td>";
		if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.0f',$tuotelkmyht))."</td>";
		echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ryhmanvarastonarvoyht))."</td>";
		echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$kiertonopeusyht))."</td>";
		echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$kate_kertaa_kierto))."</td>";
		echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.0f',$ryhmakplyht))."</td>";		
		if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$myyntieranakplyht))."</td>";
		if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$myyntieranarvoyht))."</td>";
		if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.0f',$rivilkmyht))."</td>";
		if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.0f',$ryhmapuuterivityht))."</td>";
		echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$palvelutasoyht))."</td>";
		if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ostoeranakplyht))."</td>";
		if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ostoeranarvoyht))."</td>";
		if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.0f',$ryhmaostotrivityht))."</td>";
		if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ryhmakustamyyyht))."</td>";
		if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ryhmakustaostyht))."</td>";
		if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ryhmakustayhtyht))."</td>";
		if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$totalyht))."</td>";
		echo "</tr>\n";

		echo "</table>";
	}

?>