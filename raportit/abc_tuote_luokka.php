<?php

	echo "<font class='head'>".t("ABC-Analyysiä: ABC-luokka")." $ryhmanimet[$luokka]<hr></font>";

	//ryhmäjako
	$ryhmanimet   = array('A-30','B-20','C-15','D-15','E-10','F-05','G-03','H-02','I-00');
	$ryhmaprossat = array(30.00,20.00,15.00,15.00,10.00,5.00,3.00,2.00,0.00);

	// tutkaillaan saadut muuttujat
	$osasto 		= trim($osasto);
	$try    		= trim($try);
	$tuotemerkki    = trim($tuotemerkki);

	if ($osasto 	 == "")	$osasto 	 = trim($osasto2);
	if ($try    	 == "")	$try 		 = trim($try2);
	if ($tuotemerkki == "")	$tuotemerkki = trim($tuotemerkki2);

	// piirrellään formi
	echo "<form action='$PHP_SELF' method='post' autocomplete='OFF'>";
	echo "<input type='hidden' name='tee' value='LUOKKA'>";
	echo "<input type='hidden' name='toim' value='$toim'>";
	
	echo "<table>";

	echo "<tr>";
	echo "<th>".t("Valitse luokka").":</th>";
	echo "<td></td><td><select name='luokka'>";
	echo "<option value=''>Valitse luokka</option>";

	$sel = array();
	$sel[$luokka] = "selected";

	$i=0;
	foreach ($ryhmanimet as $nimi) {
		echo "<option value='$i' $sel[$i]>$nimi</option>";
		$i++;
	}

	echo "</select></td>";
	echo "</tr>";

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
		$jarjestys = "abc_aputaulu.luokka, $abcwhat desc";
	}
	
	$osastolisa = $trylisa = $tuotemerkkilisa = "";

	if ($osasto != '') {
		$osastolisa = " and abc_aputaulu.osasto='$osasto' ";
	}
	if ($try != '') {
		$trylisa = " and abc_aputaulu.try='$try' ";
	}
	if ($tuotemerkki != '') {
		$tuotemerkkilisa = " and abc_aputaulu.tuotemerkki='$tuotemerkki' ";
	}

	//kauden yhteismyynnit ja katteet
	$query = "	SELECT
				sum(summa) yhtmyynti,
				sum(kate)  yhtkate
				FROM abc_aputaulu
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi='$abcchar'
				and luokka = '$luokka'
				$osastolisa
				$trylisa
				$tuotemerkkilisa";
	$sumres = mysql_query($query) or pupe_error($query);
	$sumrow = mysql_fetch_array($sumres);

	if ($sumrow["yhtkate"] == 0) {
		$sumrow["yhtkate"] = 0.01;
	}
	
	//haetaan rivien arvot	
	$query = "	SELECT
				abc_aputaulu.luokka,
				abc_aputaulu.tuoteno,
				abc_aputaulu.nimitys,
				abc_aputaulu.osasto,
				abc_aputaulu.tulopvm,
				abc_aputaulu.try,
				abc_aputaulu.tuotemerkki,
				abc_aputaulu.summa,
				abc_aputaulu.kate,
				abc_aputaulu.katepros,
				abc_aputaulu.kate/$sumrow[yhtkate] * 100	kateosuus,
				abc_aputaulu.vararvo,
				abc_aputaulu.varaston_kiertonop,				
				abc_aputaulu.katepros * abc_aputaulu.varaston_kiertonop kate_kertaa_kierto,				
				abc_aputaulu.myyntierankpl,
				abc_aputaulu.myyntieranarvo,
				abc_aputaulu.rivia,
				abc_aputaulu.kpl,
				abc_aputaulu.puuterivia,
				abc_aputaulu.palvelutaso,
				abc_aputaulu.ostoerankpl,
				abc_aputaulu.ostoeranarvo,
				abc_aputaulu.osto_rivia,
				abc_aputaulu.osto_kpl,
				abc_aputaulu.osto_summa,
				abc_aputaulu.kustannus,
				abc_aputaulu.kustannus_osto,
				abc_aputaulu.kustannus_yht,
				abc_aputaulu.kate-abc_aputaulu.kustannus_yht total,
				tuote.ei_varastoida
				FROM abc_aputaulu
				JOIN tuote ON abc_aputaulu.tuoteno = tuote.tuoteno and tuote.yhtio = '$kukarow[yhtio]'
				WHERE abc_aputaulu.yhtio = '$kukarow[yhtio]'
				and abc_aputaulu.tyyppi= '$abcchar'
				and abc_aputaulu.luokka = '$luokka'
				$osastolisa
				$trylisa
				$tuotemerkkilisa
				$lisa
				$hav
				ORDER BY $jarjestys";							
	$res = mysql_query($query) or pupe_error($query);

	echo "<table>";

	echo "<tr>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&order=luokka&sort=asc$ulisa'>".t("ABC")."<br>".t("Luokka")."</th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&order=tuoteno&sort=asc$ulisa'>".t("Tuoteno")."</a><br>&nbsp;</th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&order=nimitys&sort=asc$ulisa'>".t("Nimitys")."</a><br>&nbsp;</th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&order=osasto&sort=asc$ulisa'>".t("Osasto")."</a><br>&nbsp;</th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&order=try&sort=asc$ulisa'>".t("Try")."</a><br>&nbsp;</th>";
	if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&order=tulopvm&sort=desc$ulisa'>".t("Tulopvm")."</a><br>&nbsp;</th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&order=summa&sort=desc$ulisa'>".t("Myynti")."<br>".t("tot")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&order=kate&sort=desc$ulisa'>".t("Kate")."<br>".t("tot")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&order=katepros&sort=desc$ulisa'>".t("Kate")."<br>%</a></th>";
	if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&order=kateosuus&sort=desc$ulisa'>".t("Osuus")." %<br>".t("kat").".</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&order=vararvo&sort=desc$ulisa'>".t("Varast").".<br>".t("arvo")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&order=varaston_kiertonop&sort=desc$ulisa'>".t("Varast").".<br>".t("kiert").".</a></th>";	
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&order=kate_kertaa_kierto&sort=desc$ulisa'>".t("Kate")."% x<br>".t("kiert").".</a></th>";	
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&order=kpl&sort=desc$ulisa'>".t("Myydyt")."<br>".t("KPL")."</a></th>";
	if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&order=myyntierankpl&sort=desc$ulisa'>".t("Myyerä")."<br>".t("KPL")."</a></th>";
	if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&order=myyntieranarvo&sort=desc$ulisa'>".t("Myyerä")."<br>$yhtiorow[valkoodi]</a></th>";
	if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&order=rivia&sort=desc$ulisa'>Myyty<br>".t("rivejä")."</a></th>";
	if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&order=puuterivia&sort=desc$ulisa'>".t("Puute")."<br>".t("rivejä")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&order=palvelutaso&sort=desc$ulisa'>".t("Palvelu")."-<br>".t("taso")." %</a></th>";
	if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&order=ostoerankpl&sort=desc$ulisa'>".t("Ostoerä")."<br>".t("KPL")."</a></th>";
	if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&order=ostoeranarvo&sort=desc$ulisa'>".t("Ostoerä")."<br>$yhtiorow[valkoodi]</a></th>";
	if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&order=osto_rivia&sort=desc$ulisa'>".t("Ostettu")."<br>".t("rivejä")."</a></th>";
	if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&order=kustannus&sort=desc$ulisa'>".t("Myynn").".<br>".t("kustan").".</a></th>";
	if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&order=kustannus_osto&sort=desc$ulisa'>".t("Oston")."<br>".t("kustan").".</a></th>";
	if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&order=kustannus_yht&sort=desc$ulisa'>".t("Kustan").".<br>".t("yht")."</a></th>";
	if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&try=$try&osasto=$osasto&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot&order=total&sort=desc$ulisa'>".t("Kate -")."<br>".t("Kustannus")."</a></th>";
	

	echo "<form action='$PHP_SELF?tee=LUOKKA&luokka=$luokka' method='post'>";
	echo "<input type='hidden' name='toim' value='$toim'>";
	echo "<tr>";
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

	
		//jos rivejä ei löydy
	if (mysql_num_rows($res) == 0) {
		echo "</table>";
	}
	else {
		while ($row = mysql_fetch_array($res)) {
									
			if (strtoupper($row['ei_varastoida']) == 'O') {
				$row['ei_varastoida'] = "<font style='color:FF0000'>".t("Ei varastoitava")."</font>";
			}
			else {
				$row['ei_varastoida'] = "";
			}		
			
			echo "<tr>";
			echo "<td valign='top'><a href='$PHP_SELF?toim=$toim&tee=YHTEENVETO&osasto=$osasto&try=$try&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot'>".$ryhmanimet[$row["luokka"]]."</a></td>";
			echo "<td valign='top'><a href='../tuote.php?tee=Z&tuoteno=$row[tuoteno]'>$row[tuoteno]</a></td>";
			echo "<td valign='top'>$row[nimitys] $row[ei_varastoida]</td>";		
			echo "<td valign='top'><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&osasto=$row[osasto]&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot'>$row[osasto]</a></td>";
			echo "<td valign='top'><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&osasto=$row[osasto]&try=$row[try]&tuotemerkki=$tuotemerkki&lisatiedot=$lisatiedot'>$row[try]</a></td>";
			if ($lisatiedot == "TARK") echo "<td valign='top'>".tv1dateconv($row["tulopvm"])."</td>";
			echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["summa"]))."</td>";
			echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kate"]))."</td>";
			echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["katepros"]))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kateosuus"]))."</td>";
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
		
		if ($lisatiedot == "TARK") {
			echo "<td colspan='6' class='spec'>".t("Yhteensä").":</td>";
		}
		else {
			echo "<td colspan='5' class='spec'>".t("Yhteensä").":</td>";
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

?>