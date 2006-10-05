<?php

	echo "<font class='head'>".t("ABC-Analyysiä: ABC-Luokkayhteenveto")." $yhtiorow[nimi]<hr></font>";

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
	echo "<input type='hidden' name='tee' value='YHTEENVETO'>";
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
	echo "<th>".t("Syötä tai valitse piiri").":</th>";
	echo "<td><input type='text' name='try' size='10'></td>";

	$query = "	SELECT distinct piiri
				FROM asiakas
				WHERE yhtio='$kukarow[yhtio]' and piiri!=0
				ORDER BY piiri";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='try2' onChange='submit()'>";
	echo "<option value=''>".t("Piiri")."</option>";

	while ($srow = mysql_fetch_array($sresult)) {
		if ($try == $srow[0]) $sel = "selected";
		else $sel = "";
		echo "<option value='$srow[0]' $sel>$srow[0] $srow[1]</option>";
	}

	echo "</select></td><td><input type='submit' value='".t("Aja raportti")."'></td>";
	echo "</tr>";
	echo "</table>";
	echo "</form>";


	if ($os = 1) {

		echo "<table>";

		echo "<tr>";
		echo "<th nowrap>".t("ABC")."<br>".t("Luokka")."</th>";
		echo "<th nowrap>".t("Myynti")."<br>".t("tot")."</th>";
		echo "<th nowrap>".t("Myynti")."<br>".t("max")."</th>";
		echo "<th nowrap>".t("Myynti")."<br>".t("min")."</th>";
		echo "<th nowrap>".t("Kate")."<br>".t("tot")."</th>";
		echo "<th nowrap>".t("Kate")."<br>%</th>";
		echo "<th nowrap>".t("Osuus")." %<br>".t("kat").".</th>";
		echo "<th nowrap>".t("Asiakkaita")."<br>".t("KPL")."</th>";
		//echo "<th nowrap>".t("Myyerä")."<br>".t("KPL")."</th>";
		//echo "<th nowrap>".t("Myyerä")."<br>$yhtiorow[valkoodi]</th>";
		//echo "<th nowrap>".t("Myyty")."<br>".t("rivejä")."</th>";
		//echo "<th nowrap>".t("Puute")."<br>".t("rivejä")."</th>";
		echo "<th nowrap>".t("Palvelu")."-<br>".t("taso")." %</th>";
		//echo "<th nowrap>".t("Kustan").".<br>".t("yht")."</th>";

		echo "</tr>\n";


		if ($osasto != '') {
			$osastolisa = " and osasto='$osasto' ";
		}
		if ($try != '') {
			$trylisa = " and try='$try' ";
		}

		//kauden yhteismyynnit ja katteet
		$query = "	SELECT
					sum(summa) yhtmyynti,
					sum(kate)  yhtkate
					FROM abc_aputaulu
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi='A'
					$osastolisa
					$trylisa";
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
					sum(kustannus_yht) 					kustannus_yht,
					sum(summa)/sum(rivia) 				myyntieranarvo,
					sum(kpl)/sum(rivia) 				myyntieranakpl,
					sum(kate)/sum(summa)*100 			kateprosentti,
					sum(kate)/$sumrow[yhtkate] * 100	kateosuus,
					100 - ((sum(puuterivia)/(sum(puuterivia)+sum(rivia))) * 100) palvelutaso
					FROM abc_aputaulu
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi='A'
					$osastolisa
					$trylisa
					GROUP BY luokka
					ORDER BY luokka, kate desc";
		$res = mysql_query($query) or pupe_error($query);

		while($row = mysql_fetch_array($res)) {

			echo "<tr>";

			$l = $row["luokka"];

			echo "<td><a href='$PHP_SELF?tee=LUOKKA&luokka=$row[luokka]'>$ryhmanimet[$l]</a></td>";
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
			$ryhmakustamyyyht		+= $row["kustannus"];
			$ryhmakustayhtyht		+= $row["kustannus_yht"];

		}


		//yhteensärivi
		$kateprosenttiyht 	= round ($ryhmakateyht / $ryhmamyyntiyht * 100,2);
		$kateosuusyht     	= round ($ryhmakateyht / $sumrow["yhtkate"] * 100,2);
		$myyntieranarvoyht  = round ($ryhmamyyntiyht / $rivilkmyht,2);
		$myyntieranakplyht  = round ($ryhmakplyht / $rivilkmyht,2);
		$palvelutasoyht 	= round (100 - ($ryhmapuuterivityht / ($ryhmapuuterivityht + $rivilkmyht) * 100),2);


		echo "<tr>";
		echo "<td>".t("Yhteensä").":</td>";
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
	}
?>