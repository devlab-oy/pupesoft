<?php

	echo "<font class='head'>".t("ABC-Analyysiä: Osasto/Piiri")."<hr></font>";

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
	echo "<input type='hidden' name='tee' value='OSASTOTRY'>";
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
	echo "<th>".t("Syötä tai valitse tuoteryhmä").":</th>";
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


	if ($osasto != '' or $try != '') {

		$valinta = '';
		if ($osasto != '') {
			$osastolisa = " and osasto='$osasto' ";
			$valinta = "luokka_osasto";
		}
		if ($try != '') {
			$trylisa = " and try='$try' ";
			$valinta = "luokka_try";
		}


		$kentat = array('luokka',$valinta,'tuoteno','osasto','try','osto_rivia','summa','kate','katepros','kateosuus','palvelutaso');

		for ($i=0; $i<=count($kentat); $i++) {
			if (strlen($haku[$i]) > 0 and $kentat[$i] != 'kateosuus') {
				$lisa  .= " and " . $kentat[$i] . " like '%" . $haku[$i] . "%'";
				$ulisa .= "&haku[" . $i . "]=" . $haku[$i];
			}
			if (strlen($haku[$i]) > 0 and $kentat[$i] == 'kateosuus') {
				$hav = "HAVING kateosuus like '%" . $haku[$i] . "%' ";
				$ulisa .= "&haku[" . $i . "]=" . $haku[$i];
			}
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


		//haetaan rivien arvot
		$query = "	SELECT
					luokka,
					osasto,
					try,
					osto_rivia,
					$valinta,
					tuoteno,
					summa,
					kate,
					katepros,
					kate/$sumrow[yhtkate] * 100	kateosuus,
					myyntierankpl,
					myyntieranarvo,
					rivia,
					kpl,
					puuterivia,
					palvelutaso,
					kustannus_yht
					FROM abc_aputaulu
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi='$abcchar'
					$osastolisa
					$trylisa
					$lisa
					$hav
					ORDER BY $jarjestys";
		$res = mysql_query($query) or pupe_error($query);

		echo "<table>";
		echo "<tr>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&order=luokka&sort=asc$ulisa'>".t("ABC")."<br>".t("Luokka")."</th>";

		if ($valinta == 'luokka_osasto')	$otsikko = "Osaston";
		if ($valinta == 'luokka_try') 		$otsikko = "Piirin";

		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=$valinta&sort=asc$ulisa'>$otsikko<br>".t("Luokka")."</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=tuoteno&sort=asc$ulisa'>".t("Asiakas")."</a><br>&nbsp;</th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=osasto&sort=asc$ulisa'>".t("Osasto")."</a><br>&nbsp;</th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=try&sort=asc$ulisa'>".t("Piiri")."</a><br>&nbsp;</th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=osto_rivia&sort=asc$ulisa'>".t("Myyjä")."</a><br>&nbsp;</th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=summa&sort=desc$ulisa'>".t("Myynti")."<br>".t("tot")."</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=kate&sort=desc$ulisa'>".t("Kate")."<br>".t("tot")."</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=katepros&sort=desc$ulisa'>".t("Kate")."<br>%</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=kateosuus&sort=desc$ulisa'>".t("Osuus")." %<br>".t("kat").".</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=myyntierankpl&sort=desc$ulisa'>".t("Myyerä")."<br>".t("KPL")."</a></th>";
//		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=myyntieranarvo&sort=desc$ulisa'>".t("Myyerä")."<br>$yhtiorow[valkoodi]</a></th>";
//		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=rivia&sort=desc$ulisa'>".t("Myyty")."<br>".t("rivejä")."</a></th>";
//		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=puuterivia&sort=desc$ulisa'>".t("Puute")."<br>".t("rivejä")."</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=palvelutaso&sort=desc$ulisa'>".t("Palvelu")."-<br>".t("taso")." %</a></th>";
//		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=kustannus_yht&sort=desc$ulisa'>".t("Kustan").".<br>".t("yht")."</a></th>";
		echo "</tr>";

		echo "<form action='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta' method='post'>";
		echo "<tr>";

		for ($i = 0; $i < count($kentat); $i++) {
			echo "<th><input type='text' name='haku[$i]' value='$haku[$i]' size='5'></th>";
		}

		echo "<td class='back'><input type='Submit' value='".t("Etsi")."'></td></form></tr>";

		if (mysql_num_rows($res) == 0) {
			echo "</table>";
		}
		else {

			while($row = mysql_fetch_array($res)) {

				//haetaan asiakkaan tiedot
				$query = "	SELECT *
							FROM asiakas
							WHERE yhtio = '$kukarow[yhtio]'
							and ytunnus = '$row[tuoteno]'";
				$asres = mysql_query($query) or pupe_error($query);
				$asrow = mysql_fetch_array($asres);

				echo "<tr>";

				$l = $row["luokka"];
				echo "<td>$ryhmanimet[$l]</td>";

				$l = $row[$valinta];
				echo "<td>$ryhmanimet[$l]</td>";

				echo "<td><a href='../crm/asiakasmemo.php?ytunnus=$row[tuoteno]'>$row[tuoteno] $asrow[nimi]</a></td>";
				echo "<td><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&osasto=$row[osasto]'>$row[osasto]</a></td>";
				echo "<td><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&osasto=$row[osasto]&try=$row[try]'>$row[try]</a></td>";
				echo "<td align='right'>".str_replace(".",",",sprintf('%.0f',$row["osto_rivia"]))."</td>";
				echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["summa"]))."</td>";
				echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["kate"]))."</td>";
				echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["katepros"]))."</td>";
				echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["kateosuus"]))."</td>";
				//echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["myyntierankpl"]))."</td>";
				//echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["myyntieranarvo"]))."</td>";
				//echo "<td align='right'>".str_replace(".",",",sprintf('%.0f',$row["rivia"]))."</td>";
				//echo "<td align='right'>".str_replace(".",",",sprintf('%.0f',$row["puuterivia"]))."</td>";
				echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["palvelutaso"]))."</td>";
				//echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["kustannus_yht"]))."</td>";
				echo "</tr>\n";

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
			$myyntieranarvoyht 	= round ($ryhmamyyntiyht / $rivilkmyht,2);
			$myyntieranakplyht 	= round ($ryhmakplyht / $rivilkmyht,2);
			$palvelutasoyht 	= round (100 - ($ryhmapuuterivityht / ($ryhmapuuterivityht + $rivilkmyht) * 100),2);



			echo "<tr>";
			echo "<td colspan='6' class='spec'>".t("Yhteensä").":</td>";
			echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$ryhmamyyntiyht))."</td>";
			echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$ryhmakateyht))."</td>";
			echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$kateprosenttiyht))."</td>";
			echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$kateosuusyht))."</td>";
			//echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$myyntieranakplyht))."</td>";
			//echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$myyntieranarvoyht))."</td>";
			//echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.0f',$rivilkmyht))."</td>";
			//echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.0f',$ryhmapuuterivityht))."</td>";
			echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$palvelutasoyht))."</td>";
			//echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$ryhmakustayhtyht))."</td>";
			echo "</tr>\n";

			echo "</table>";
		}
	}
?>