<?php

	echo "<font class='head'>".t("ABC-Analyysi: Osasto/tuoteryhmäyhteenveto")."<hr></font>";

	if ($toim == "kate") {
		$abcwhat = "kate";
		$abcchar = "TK";
	}
	else {
		$abcwhat = "summa";
		$abcchar = "TM";
	}

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
				WHERE yhtio='$kukarow[yhtio]' and laji='OSASTO'";
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
	echo "<th>".t("Syötä tai valitse tuoteryhmä").":</th>";
	echo "<td><input type='text' name='try' size='10'></td>";

	$query = "	SELECT distinct selite, selitetark
				FROM avainsana
				WHERE yhtio='$kukarow[yhtio]' and laji='TRY'";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='try2' onChange='submit()'>";
	echo "<option value=''>".t("Tuoteryhmä")."</option>";

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

	$kentat = array('luokka_trygroup','osasto','try','summa','max','min','kate','kateprosentti','kateosuus','tuotelkm','vararvo','varaston_kiertonop','myyntierankpl','myyntieranarvo','rivia','puuterivia','palvelutaso','ostoerankpl','ostoeranarvo','osto_rivia','kustannus','kustannus_osto','kustannus_yht','total');

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
		$jarjestys = "luokka_trygroup, osasto, try, summa desc";
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
				sum(kate)/$sumrow[yhtkate] * 100	kateosuus,
				100 - ((sum(puuterivia)/(sum(puuterivia)+sum(rivia))) * 100) palvelutaso,
				sum(kate)-sum(kustannus_yht)		total
				FROM abc_aputaulu
				WHERE abc_aputaulu.yhtio = '$kukarow[yhtio]'
				and tyyppi='$abcchar'
				$osastolisa
				$trylisa
				$lisa
				GROUP BY luokka_trygroup, osasto, try
				$hav
				ORDER BY $jarjestys";
	$res = mysql_query($query) or pupe_error($query);

	echo "<table>";
	echo "<tr>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=luokka_trygroup&sort=asc$ulisa'>".t("ABC")."<br>".t("Luokka")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=osasto&sort=asc$ulisa'>".t("Osasto")."</a><br>&nbsp;</th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=try&sort=asc$ulisa'>".t("Try")."</a><br>&nbsp;</th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=summa&sort=desc$ulisa'>".t("Myynti")."<br>".t("tot")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=max&sort=desc$ulisa'>".t("Myynti")."<br>".t("max")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=min&sort=desc$ulisa'>".t("Myynti")."<br>".t("min")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=kate&sort=desc$ulisa'>".t("Kate")."<br>".t("tot")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=kateprosentti&sort=desc$ulisa'>".t("Kate")."<br>%</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=kateosuus&sort=desc$ulisa'>".t("Osuus")." %<br>".t("kat").".</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=tuotelkm&sort=desc$ulisa'>".t("Tuotteita")."<br>".t("KPL")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=vararvo&sort=desc$ulisa'>".t("Varast").".<br>".t("arvo")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=kiertonopeus&sort=desc$ulisa'>".t("Varast").".<br>".t("kiert").".</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=myyntieranakpl&sort=desc$ulisa'>".t("Myyerä")."<br>".t("KPL")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=myyntieranarvo&sort=desc$ulisa'>".t("Myyerä")."<br>$yhtiorow[valkoodi]</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=rivia&sort=desc$ulisa'>".t("Myyty")."<br>".t("rivejä")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=puuterivia&sort=desc$ulisa'>".t("Puute")."<br>".t("rivejä")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=palvelutaso&sort=desc$ulisa'>".t("Palvelu")."-<br>".t("taso")." %</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=ostoeranakpl&sort=desc$ulisa'>".t("Ostoerä")."<br>".t("KPL")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=myyntieranarvo&sort=desc$ulisa'>".t("Ostoerä")."<br>$yhtiorow[valkoodi]</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=osto_rivia&sort=desc$ulisa'>".t("Ostettu")."<br>".t("rivejä")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=kustannus&sort=desc$ulisa'>".t("Myynn").".<br>".t("kustan").".</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=kustannus_osto&sort=desc$ulisa'>".t("Oston")."<br>".t("kustan").".</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=kustannus_yht&sort=desc$ulisa'>".t("Kustan").".<br>".t("yht")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto&order=total&sort=desc$ulisa'>".t("Kate -")."<br>".t("Kustannus")."</a></th>";
	echo "</tr>\n";

	echo "<form action='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&luokka=$luokka&try=$try&osasto=$osasto' method='post'>";
	echo "<tr>";

	for ($i = 0; $i < count($kentat); $i++) {
		echo "<th><input type='text' name='haku[$i]' value='$haku[$i]' size='5'></th>";
	}

	echo "<td class='back'><input type='Submit' value='".t("Etsi")."'></td></form></tr>";

	$ryhmanimet   = array('A-30','B-20','C-15','D-15','E-10','F-05','G-03','H-02','I-00');
	$ryhmaprossat = array(30.00,20.00,15.00,15.00,10.00,5.00,3.00,2.00,0.00);

	while ($row = mysql_fetch_array($res)) {

		echo "<tr>";

		$l = $row["luokka_trygroup"];

		$query = "	SELECT selite, selitetark
					FROM avainsana
					WHERE yhtio='$kukarow[yhtio]' and laji='TRY' and selite='$row[try]'";
		$keyres = mysql_query($query) or pupe_error($query);
		$keytry = mysql_fetch_array($keyres);	

		$query = "	SELECT selite, selitetark
					FROM avainsana
					WHERE yhtio='$kukarow[yhtio]' and laji='OSASTO' and selite='$row[osasto]'";
		$keyres = mysql_query($query) or pupe_error($query);
		$keyosa = mysql_fetch_array($keyres);	
					
		echo "<td>$ryhmanimet[$l]</td>";
		echo "<td><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&osasto=$row[osasto]'>$row[osasto] $keyosa[selitetark]</a></td>";
		echo "<td><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&osasto=$row[osasto]&try=$row[try]'>$row[try] $keytry[selitetark]</a></td>";
		echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["summa"]))."</td>";
		echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["max"]))."</td>";
		echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["min"]))."</td>";
		echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["kate"]))."</td>";
		echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["kateprosentti"]))."</td>";
		echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["kateosuus"]))."</td>";
		echo "<td align='right'>".str_replace(".",",",sprintf('%.0f',$row["tuotelkm"]))."</td>";
		echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["vararvo"]))."</td>";
		echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["kiertonopeus"]))."</td>";
		echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["myyntieranakpl"]))."</td>";
		echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["myyntieranarvo"]))."</td>";
		echo "<td align='right'>".str_replace(".",",",sprintf('%.0f',$row["rivia"]))."</td>";
		echo "<td align='right'>".str_replace(".",",",sprintf('%.0f',$row["puuterivia"]))."</td>";
		echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["palvelutaso"]))."</td>";
		echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["ostoeranakpl"]))."</td>";
		echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["ostoeranarvo"]))."</td>";
		echo "<td align='right'>".str_replace(".",",",sprintf('%.0f',$row["osto_rivia"]))."</td>";
		echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["kustannus"]))."</td>";
		echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["kustannus_osto"]))."</td>";
		echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["kustannus_yht"]))."</td>";
		echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["total"]))."</td>";
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
	$kateprosenttiyht 	= round ($ryhmakateyht / $ryhmamyyntiyht * 100,2);
	$kateosuusyht     	= round ($ryhmakateyht / $sumrow["yhtkate"] * 100,2);
	$kiertonopeusyht  	= round (($ryhmamyyntiyht - $ryhmakateyht) / $ryhmanvarastonarvoyht,2);
	$myyntieranarvoyht  = round ($ryhmamyyntiyht / $rivilkmyht,2);
	$myyntieranakplyht  = round ($ryhmakplyht / $rivilkmyht,2);
	$palvelutasoyht 	= round (100 - ($ryhmapuuterivityht / ($ryhmapuuterivityht + $rivilkmyht) * 100),2);
	$ostoeranarvoyht	= round ($ryhmaostotyht / $ryhmaostotrivityht,2);
	$ostoeranakplyht 	= round ($ryhmaostotkplyht / $ryhmaostotrivityht,2);


	echo "<tr>";
	echo "<td colspan='3'>".t("Yhteensä").":</td>";
	echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$ryhmamyyntiyht))."</td>";
	echo "<td align='right' class='spec'></td>";
	echo "<td align='right' class='spec'></td>";
	echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$ryhmakateyht))."</td>";
	echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$kateprosenttiyht))."</td>";
	echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$kateosuusyht))."</td>";
	echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.0f',$tuotelkmyht))."</td>";
	echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$ryhmanvarastonarvoyht))."</td>";
	echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$kiertonopeusyht))."</td>";
	echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$myyntieranakplyht))."</td>";
	echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$myyntieranarvoyht))."</td>";
	echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.0f',$rivilkmyht))."</td>";
	echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.0f',$ryhmapuuterivityht))."</td>";
	echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$palvelutasoyht))."</td>";
	echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$ostoeranakplyht))."</td>";
	echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$ostoeranarvoyht))."</td>";
	echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.0f',$ryhmaostotrivityht))."</td>";
	echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$ryhmakustamyyyht))."</td>";
	echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$ryhmakustaostyht))."</td>";
	echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$ryhmakustayhtyht))."</td>";
	echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$totalyht))."</td>";
	echo "</tr>\n";

	echo "</table>";

?>