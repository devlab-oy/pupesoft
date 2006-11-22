<?php

	echo "<font class='head'>".t("ABC-Analyysiä: Osasto-/Ryhmäyhteenveto")."<hr></font>";

	if ($toim == "kate") {
		$abcwhat = "kate";
		$abcchar = "AK";
	}
	else {
		$abcwhat = "summa";
		$abcchar = "AM";
	}

	//ryhmäjako
	$ryhmanimet   = array('A-50','B-30','C-20');
	$ryhmaprossat = array(50.00,30.00,20.00);

	// tutkaillaan saadut muuttujat
	$osasto = trim($osasto);
	$try    = trim($try);

	if ($osasto == "")	$osasto = trim($osasto2);
	if ($try    == "")	$try = trim($try2);

	if ($ed == 'on')	$chk = "CHECKED";
	else				$chk = "";

	// piirrellään formi
	echo "<form action='$PHP_SELF' method='post' autocomplete='OFF'>";
	echo "<input type='hidden' name='tee' value='OSASTOTRYYHTEENVETO'>";
	echo "<input type='hidden' name='toim' value='$toim'>";
	echo "<table>";

	echo "<tr>";
	echo "<th>".t("Syötä tai valitse osasto").":</th>";
	echo "<td><input type='text' name='osasto' size='10'></td>";

	$query = "	SELECT distinct selite, selitetark
				FROM avainsana
				WHERE yhtio='$kukarow[yhtio]' and laji='ASIAKASOSASTO'
				ORDER BY selite+0";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='osasto2' onChange='submit()'>";
	echo "<option value=''>".t("Osasto")."</option>";

	while ($srow = mysql_fetch_array($sresult)) {
		if ($osasto == $srow[0]) $sel = "selected";
		else $sel = "";
		echo "<option value='$srow[0]' $sel>$srow[0] $srow[1]</option>";
	}

	echo "</select></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Syötä tai valitse ryhmä").":</th>";
	echo "<td><input type='text' name='try' size='10'></td>";

	$query = "	SELECT distinct selite, selitetark
				FROM avainsana
				WHERE yhtio='$kukarow[yhtio]' and laji='ASIAKASRYHMA'
				ORDER BY selite+0";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='try2' onChange='submit()'>";
	echo "<option value=''>".t("Ryhmä")."</option>";

	while ($srow = mysql_fetch_array($sresult)) {
		if ($try == $srow[0]) $sel = "selected";
		else $sel = "";
		echo "<option value='$srow[0]' $sel>$srow[0] $srow[1]</option>";
	}

	echo "</select></td><td><input type='submit' value='".t("Aja raportti")."'></td>";
	echo "</tr>";
	echo "</table>";
	echo "</form>";


	if ($osasto != '') {
		$osastolisa = " and osasto='$osasto' ";
	}
	if ($try != '') {
		$trylisa = " and try='$try' ";
	}


	$kentat = array('luokka_trygroup','osasto','try','summa','max','min','kate','kateprosentti','kateosuus','tuotelkm','palvelutaso');

	for ($i=0; $i<=count($kentat); $i++) {
		if (strlen($haku[$i]) > 0 and $i <= 2) {
			$lisa  .= " and " . $kentat[$i] . " like '%" . $haku[$i] . "%'";
			$ulisa .= "&haku[" . $i . "]=" . $haku[$i];
		}
		if (strlen($haku[$i]) > 0 and $i > 2) {
			$hav   .= " and " . $kentat[$i] . " like '%" . $haku[$i] . "%'";
			$ulisa .= "&haku[" . $i . "]=" . $haku[$i];
		}
	}

	if ($hav != '') {
		$hav = "HAVING ".substr($hav,4);
	}


	if (strlen($order) > 0) {
		$jarjestys = $order." ".$sort;
	}
	else {
		$jarjestys = "kate desc";
	}


	//kauden yhteismyynnit ja katteet
	$query = "	SELECT
				sum(summa) yhtmyynti,
				sum(kate)  yhtkate
				FROM abc_aputaulu
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi='$abcchar'
				$osastolisa
				$trylisa";
	$sumres = mysql_query($query) or pupe_error($query);
	$sumrow = mysql_fetch_array($sumres);

	if ($sumrow["yhtkate"] == 0) {
		$sumrow["yhtkate"] = 0.01;
	}

	//haetaan luokkien arvot
	$query = "	SELECT
				luokka_trygroup,
				osasto,
				try,
				count(tuoteno)						tuotelkm,
				max(summa) 							max,
				min(summa)	 						min,
				sum(rivia) 							rivia,
				sum(kpl) 							kpl,
				sum(summa) 							summa,
				sum(kate) 							kate,
				sum(puutekpl) 						puutekpl,
				sum(puuterivia) 					puuterivia,
				sum(kustannus_yht) 					kustannus_yht,
				sum(summa)/sum(rivia) 				myyntieranarvo,
				sum(kpl)/sum(rivia) 				myyntieranakpl,
				sum(kate)/sum(summa)*100 			kateprosentti,
				sum(kate)/$sumrow[yhtkate] * 100	kateosuus,
				100 - ((sum(puuterivia)/(sum(puuterivia)+sum(rivia))) * 100) palvelutaso
				FROM abc_aputaulu
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi='$abcchar'
				$osastolisa
				$trylisa
				$lisa
				GROUP BY osasto, try
				$hav
				ORDER BY $jarjestys";
	$res = mysql_query($query) or pupe_error($query);

	echo "<table>";
	echo "<tr>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=luokka_trygroup&sort=asc$ulisa'>".t("ABC")."<br>".t("Luokka")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=osasto&sort=asc$ulisa'>".t("Osasto")."</a><br>&nbsp;</th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=try&sort=asc$ulisa'>".t("Ryhmä")."</a><br>&nbsp;</th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=summa&sort=desc$ulisa'>".t("Myynti")."<br>".t("tot")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=max&sort=desc$ulisa'>".t("Myynti")."<br>".t("max")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=min&sort=desc$ulisa'>".t("Myynti")."<br>".t("min")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=kate&sort=desc$ulisa'>".t("Kate")."<br>".t("tot")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=kateprosentti&sort=desc$ulisa'>".t("Kate")."<br>%</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=kateosuus&sort=desc$ulisa'>".t("Osuus")." %<br>".t("kat").".</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=tuotelkm&sort=desc$ulisa'>".t("Asiakkaita")."<br>".t("KPL")."</a></th>";
//	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=myyntieranakpl&sort=desc$ulisa'>".t("Myyerä")."<br>".t("KPL")."</a></th>";
//	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=myyntieranarvo&sort=desc$ulisa'>".t("Myyerä")."<br>$yhtiorow[valkoodi]</a></th>";
//	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=rivia&sort=desc$ulisa'>".t("Myyty")."<br>".t("rivejä")."</a></th>";
//	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=puuterivia&sort=desc$ulisa'>".t("Puute")."<br>".t("rivejä")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=palvelutaso&sort=desc$ulisa'>".t("Palvelu")."-<br>".t("taso")." %</a></th>";
//	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=kustannus_yht&sort=desc$ulisa'>".t("Kustan").".<br>".t("yht")."</a></th>";
	echo "</tr>\n";

	echo "<form action='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto' method='post'>";
	echo "<tr>";

	for ($i = 0; $i < count($kentat); $i++) {
		echo "<th><input type='text' name='haku[$i]' value='$haku[$i]' size='5'></th>";
	}

	echo "<td class='back'><input type='Submit' value='".t("Etsi")."'></td></form></tr>";

	while($row = mysql_fetch_array($res)) {

		echo "<tr>";

		$l = $row["luokka_trygroup"];

		echo "<td>$ryhmanimet[$l]</td>";
		echo "<td><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&osasto=$row[osasto]'>$row[osasto]</a></td>";
		echo "<td><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&osasto=$row[osasto]&try=$row[try]'>$row[try]</a></td>";
		echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["summa"]))."</td>";
		echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["max"]))."</td>";
		echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["min"]))."</td>";
		echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["kate"]))."</td>";
		echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["kateprosentti"]))."</td>";
		echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["kateosuus"]))."</td>";
		echo "<td align='right'>".str_replace(".",",",sprintf('%.0f',$row["tuotelkm"]))."</td>";
		//echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["myyntieranakpl"]))."</td>";
		//echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["myyntieranarvo"]))."</td>";
		//echo "<td align='right'>".str_replace(".",",",sprintf('%.0f',$row["rivia"]))."</td>";
		//echo "<td align='right'>".str_replace(".",",",sprintf('%.0f',$row["puuterivia"]))."</td>";
		echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["palvelutaso"]))."</td>";
		//echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["kustannus_yht"]))."</td>";
		echo "</tr>\n";

		$tuotelkmyht			+= $row["tuotelkm"];
		$ryhmamyyntiyht 		+= $row["summa"];
		$ryhmakateyht   		+= $row["kate"];
		$rivilkmyht				+= $row["rivia"];
		$ryhmakplyht			+= $row["kpl"];
		$ryhmapuuteyht			+= $row["puutekpl"];
		$ryhmapuuterivityht		+= $row["puuterivia"];
		$ryhmakustayhtyht		+= $row["kustannus_yht"];

	}


	//yhteensärivi
	$kateprosenttiyht 	= round ($ryhmakateyht / $ryhmamyyntiyht * 100,2);
	$kateosuusyht     	= round ($ryhmakateyht / $sumrow["yhtkate"] * 100,2);
	$myyntieranarvoyht  = round ($ryhmamyyntiyht / $rivilkmyht,2);
	$myyntieranakplyht  = round ($ryhmakplyht / $rivilkmyht,2);
	$palvelutasoyht 	= round (100 - ($ryhmapuuterivityht / ($ryhmapuuterivityht + $rivilkmyht) * 100),2);


	echo "<tr>";
	echo "<td colspan='3'>".t("Yhteensä").":</td>";
	echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$ryhmamyyntiyht))."</td>";
	echo "<td align='right' class='spec'></td>";
	echo "<td align='right' class='spec'></td>";
	echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$ryhmakateyht))."</td>";
	echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$kateprosenttiyht))."</td>";
	echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$kateosuusyht))."</td>";
	echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.0f',$tuotelkmyht))."</td>";
	//echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$myyntieranakplyht))."</td>";
	//echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$myyntieranarvoyht))."</td>";
	//echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.0f',$rivilkmyht))."</td>";
	//echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.0f',$ryhmapuuterivityht))."</td>";
	echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$palvelutasoyht))."</td>";
	//echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$ryhmakustayhtyht))."</td>";
	echo "</tr>\n";

	echo "</table>";

?>