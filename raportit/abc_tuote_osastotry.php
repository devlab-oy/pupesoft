<?php

	echo "<font class='head'>".t("ABC-Analyysi: Tuoteosasto tai tuoteryhmä")."<hr></font>";

	// tutkaillaan saadut muuttujat
	$osasto 		= trim($osasto);
	$try    		= trim($try);
	$tuotemerkki    = trim($tuotemerkki);

	if ($osasto 	 == "")	$osasto 	 = trim($osasto2);
	if ($try    	 == "")	$try 		 = trim($try2);
	if ($tuotemerkki == "")	$tuotemerkki = trim($tuotemerkki2);

	// piirrellään formi
	echo "<form action='$PHP_SELF' method='post' autocomplete='OFF'>";
	echo "<input type='hidden' name='tee' value='OSASTOTRY'>";
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
	
	if ($osasto != '' or $try != '' or $tuotemerkki != '') {

		$valinta = 'luokka';
		$valintalisa = "";

		if ($osasto != '') {
			$valintalisa .= " and osasto='$osasto' ";
			$valinta = "luokka_osasto";
		}
		if ($try != '') {
			$valintalisa .= " and try='$try' ";
			$valinta = "luokka_try";
		}
		if ($tuotemerkki != '') {
			$valintalisa .= " and tuotemerkki='$tuotemerkki' ";
			$valinta = "luokka_try";
		}

		if (count($haku) > 0) {
			foreach ($haku as $kentta => $arvo) {
				if (strlen($arvo) > 0 and $kentta != 'kateosuus') {
					$lisa  .= " and abc_aputaulu.$kentta like '%$arvo%'";
					$ulisa .= "&haku[$kentta]=$arvo";
				}
				if (strlen($arvo) > 0 and $kentta == 'kateosuus') {
					$hav = "HAVING abc_aputaulu.kateosuus like '%$arvo%' ";
					$ulisa .= "&haku[$kentta]=$arvo";
				}
			}
		}

		if (strlen($order) > 0) {
			$jarjestys = $order." ".$sort;
		}
		else {
			$jarjestys = "$valinta, $abcwhat desc";
		}

		//kauden yhteismyynnit ja katteet
		$query = "	SELECT
					sum(summa) yhtmyynti,
					sum(kate)  yhtkate
					FROM abc_aputaulu
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi='$abcchar'
					$valintalisa";
		$sumres = mysql_query($query) or pupe_error($query);
		$sumrow = mysql_fetch_array($sumres);

		if ($sumrow["yhtkate"] == 0) {
			$sumrow["yhtkate"] = 0.01;
		}

		//haetaan rivien arvot
		$query = "	SELECT
					luokka,
					osasto,
					try,
					tuotemerkki,
					$valinta,
					tuoteno,
					osasto,
					nimitys,
					tulopvm,
					try,
					summa,
					kate,
					katepros,
					kate/$sumrow[yhtkate] * 100	kateosuus,
					vararvo,
					varaston_kiertonop,
					kate * varaston_kiertonop kate_kertaa_kierto,	
					myyntierankpl,
					myyntieranarvo,
					rivia,
					kpl,
					puuterivia,
					palvelutaso,
					ostoerankpl,
					ostoeranarvo,
					osto_rivia,
					osto_kpl,
					osto_summa,
					kustannus,
					kustannus_osto,
					kustannus_yht,
					kate-kustannus_yht total
					FROM abc_aputaulu
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi='$abcchar'
					$valintalisa
					$lisa
					$hav
					ORDER BY $jarjestys";
		$res = mysql_query($query) or pupe_error($query);

		echo "<table>";
		echo "<tr>";

		if ($valinta == 'luokka_osasto')	$otsikko = "Osaston";
		if ($valinta == 'luokka_try') 		$otsikko = "Tryn";

		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&valinta=$valinta&order=$valinta&sort=asc$ulisa'>$otsikko<br>".t("Luokka")."</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&valinta=$valinta&order=luokka&sort=asc$ulisa'>".t("ABC")."<br>".t("Luokka")."</th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&valinta=$valinta&order=tuoteno&sort=asc$ulisa'>".t("Tuoteno")."</a><br>&nbsp;</th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&valinta=$valinta&order=nimitys&sort=asc$ulisa'>".t("Nimitys")."</a><br>&nbsp;</th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&valinta=$valinta&order=osasto&sort=asc$ulisa'>".t("Osasto")."</a><br>&nbsp;</th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&valinta=$valinta&order=try&sort=asc$ulisa'>".t("Try")."</a><br>&nbsp;</th>";
		if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&valinta=$valinta&order=tulopvm&sort=desc$ulisa'>".t("Tulopvm")."</a><br>&nbsp;</th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&valinta=$valinta&order=summa&sort=desc$ulisa'>".t("Myynti")."<br>".t("tot")."</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&valinta=$valinta&order=kate&sort=desc$ulisa'>".t("Kate")."<br>".t("tot")."</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&valinta=$valinta&order=katepros&sort=desc$ulisa'>".t("Kate")."<br>%</a></th>";
		if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&valinta=$valinta&order=kateosuus&sort=desc$ulisa'>".t("Osuus")." %<br>".t("kat").".</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&valinta=$valinta&order=vararvo&sort=desc$ulisa'>".t("Varast").".<br>".t("arvo")."</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&valinta=$valinta&order=varaston_kiertonop&sort=desc$ulisa'>".t("Varast").".<br>".t("kiert").".</a></th>";		
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&valinta=$valinta&order=kate_kertaa_kierto&sort=desc$ulisa'>".t("Kate")." x<br>".t("kiert").".</a></th>";			
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&valinta=$valinta&order=kpl&sort=desc$ulisa'>".t("Myydyt")."<br>".t("KPL")."</a></th>";
		if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&valinta=$valinta&order=myyntierankpl&sort=desc$ulisa'>".t("Myyerä")."<br>".t("KPL")."</a></th>";
		if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&valinta=$valinta&order=myyntieranarvo&sort=desc$ulisa'>".t("Myyerä")."<br>$yhtiorow[valkoodi]</a></th>";
		if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&valinta=$valinta&order=rivia&sort=desc$ulisa'>".t("Myyty")."<br>".t("rivejä")."</a></th>";
		if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&valinta=$valinta&order=puuterivia&sort=desc$ulisa'>".t("Puute")."<br>".t("rivejä")."</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&valinta=$valinta&order=palvelutaso&sort=desc$ulisa'>".t("Palvelu")."-<br>".t("taso")." %</a></th>";
		if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&valinta=$valinta&order=ostoerankpl&sort=desc$ulisa'>".t("Ostoerä")."<br>".t("KPL")."</a></th>";
		if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&valinta=$valinta&order=ostoeranarvo&sort=desc$ulisa'>".t("Ostoerä")."<br>$yhtiorow[valkoodi]</a></th>";
		if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&valinta=$valinta&order=osto_rivia&sort=desc$ulisa'>".t("Ostettu")."<br>".t("rivejä")."</a></th>";
		if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&valinta=$valinta&order=kustannus&sort=desc$ulisa'>".t("Myynn").".<br>".t("kustan").".</a></th>";
		if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&valinta=$valinta&order=kustannus_osto&sort=desc$ulisa'>".t("Oston")."<br>".t("kustan").".</a></th>";
		if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&valinta=$valinta&order=kustannus_yht&sort=desc$ulisa'>".t("Kustan").".<br>".t("yht")."</a></th>";
		if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&valinta=$valinta&order=total&sort=desc$ulisa'>".t("Kate -")."<br>".t("Kustannus")."</a></th>";
		echo "</tr>";

		echo "<form action='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&valinta=$valinta' method='post'>";
		echo "<tr>";
		echo "<th><input type='text' name='haku[$valinta]' value='$haku[$valinta]' size='5'></th>";
		echo "<th><input type='text' name='haku[luokka]' value='$haku[luokka]' size='5'></th>";
		echo "<th><input type='text' name='haku[tuoteno]' value='$haku[tuoteno]' size='5'></th>";
		echo "<th><input type='text' name='haku[nimitys]' value='$haku[nimitys]' size='5'></th>";
		echo "<th><input type='text' name='haku[osasto]' value='$haku[osasto]' size='5'></th>";
		echo "<th><input type='text' name='haku[try]' value='$haku[try]' size='5'></th>";
		if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[tulopvm]' value='$haku[tulopvm]' size='5'></th>";
		echo "<th><input type='text' name='haku[summa]' value='$haku[summa]' size='5'></th>";
		echo "<th><input type='text' name='haku[kate]' value='$haku[kate]' size='5'></th>";
		echo "<th><input type='text' name='haku[katepros]' value='$haku[katepros]' size='5'></th>";
		if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[kateosuus]' value='$haku[kateosuus]' size='5'></th>";
		echo "<th><input type='text' name='haku[vararvo]' value='$haku[vararvo]' size='5'></th>";
		echo "<th><input type='text' name='haku[varaston_kiertonop]' value='$haku[varaston_kiertonop]' size='5'></th>";
		echo "<th><input type='text' name='haku[kate_kertaa_kierto]' value='$haku[kate_kertaa_kierto]' size='5'></th>";
		echo "<th><input type='text' name='haku[kpl]' value='$haku[kpl]' size='5'></th>";
		if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[myyntierankpl]' value='$haku[myyntierankpl]' size='5'></th>";
		if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[myyntieranarvo]' value='$haku[myyntieranarvo]' size='5'></th>";
		if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[rivia]' value='$haku[rivia]' size='5'></th>";
		if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[puuterivia]' value='$haku[puuterivia]' size='5'></th>";
		echo "<th><input type='text' name='haku[palvelutaso]' value='$haku[palvelutaso]' size='5'></th>";
		if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[ostoerankpl]' value='$haku[ostoerankpl]' size='5'></th>";
		if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[ostoeranarvo]' value='$haku[ostoeranarvo]' size='5'></th>";
		if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[osto_rivia]'	value='$haku[osto_rivia]' size='5'></th>";
		if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[kustannus]' value='$haku[kustannus]' size='5'></th>";
		if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[kustannus_osto]'value='$haku[kustannus_osto]' size='5'></th>";
		if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[kustannus_yht]' value='$haku[kustannus_yht]' size='5'></th>";
		if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[total]' value='$haku[total]' size='5'></th>";
		echo "<td class='back'><input type='Submit' value='".t("Etsi")."'></td></form></tr>";

		if (mysql_num_rows($res) == 0) {
			echo "</table>";
		}
		else {

			$ryhmanimet   = array('A-30','B-20','C-15','D-15','E-10','F-05','G-03','H-02','I-00');
			$ryhmaprossat = array(30.00,20.00,15.00,15.00,10.00,5.00,3.00,2.00,0.00);

			while ($row = mysql_fetch_array($res)) {
				
				if (strtoupper($row['ei_varastoida']) == 'O') {
					$row['ei_varastoida'] = "<font style='color:FF0000'>".t("Ei varastoitava")."</font>";
				}
				else {
					$row['ei_varastoida'] = "";
				}
				
				if ($lisatiedot == "TARK") {
					$query = "	SELECT distinct avainsana.selite, ".avain('select')."
								FROM avainsana
								".avain('join','TRY_')."
								WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='TRY' and avainsana.selite='$row[try]'";
					$keyres = mysql_query($query) or pupe_error($query);
					$keytry = mysql_fetch_array($keyres);

					$query = "	SELECT distinct avainsana.selite, ".avain('select')."
								FROM avainsana
								".avain('join','OSASTO_')."
								WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='OSASTO' and avainsana.selite='$row[osasto]'";
					$keyres = mysql_query($query) or pupe_error($query);
					$keyosa = mysql_fetch_array($keyres);
				}
				
				echo "<tr>";
				echo "<td>".$ryhmanimet[$row[$valinta]]."</td>";
				echo "<td valign='top'><a href='$PHP_SELF?toim=$toim&tee=YHTEENVETO&luokka=$l&osasto=$osasto&try=$try&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot'>".$ryhmanimet[$row["luokka"]]."</a></td>";
				echo "<td valign='top'><a href='../tuote.php?tee=Z&tuoteno=$row[tuoteno]'>$row[tuoteno]</a></td>";
				echo "<td valign='top'>$row[nimitys]  $row[ei_varastoida]</td>";
				echo "<td valign='top' nowrap><a href='$PHP_SELF?toim=$toim&tee=YHTEENVETO&osasto=$row[osasto]&tuotemerkki=$row[tuotemerkki]'>$row[osasto] $keyosa[selitetark]</a></td>";
				echo "<td valign='top' nowrap><a href='$PHP_SELF?toim=$toim&tee=YHTEENVETO&osasto=$row[osasto]&try=$row[try]&tuotemerkki=$row[tuotemerkki]'>$row[try] $keytry[selitetark]</a></td>";
				if ($lisatiedot == "TARK") echo "<td>".tv1dateconv($row["tulopvm"])."</td>";
				echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["summa"]))."</td>";
				echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kate"]))."</td>";
				echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["katepros"]))."</td>";
				if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kateosuus"]))."</td>";
				echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["vararvo"]))."</td>";
				echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["varaston_kiertonop"]))."</td>";
				echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kate_kertaa_kierto"]))."</td>";
				echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.0f',$row["kpl"]))."</td>";
				if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["myyntierankpl"]))."</td>";
				if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["myyntieranarvo"]))."</td>";
				if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.0f',$row["rivia"]))."</td>";
				if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.0f',$row["puuterivia"]))."</td>";
				echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["palvelutaso"]))."</td>";
				if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["ostoerankpl"]))."</td>";
				if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["ostoeranarvo"]))."</td>";
				if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.0f',$row["osto_rivia"]))."</td>";
				if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kustannus"]))."</td>";
				if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kustannus_osto"]))."</td>";
				if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kustannus_yht"]))."</td>";
				if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["total"]))."</td>";
				echo "</tr>\n";

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

			if ($lisatiedot == "TARK") {
				echo "<td colspan='7' class='spec'>".t("Yhteensä").":</td>";
			}
			else {
				echo "<td colspan='6' class='spec'>".t("Yhteensä").":</td>";
			}
			
			echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ryhmamyyntiyht))."</td>";
			echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ryhmakateyht))."</td>";
			echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$kateprosenttiyht))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$kateosuusyht))."</td>";
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
	}

?>