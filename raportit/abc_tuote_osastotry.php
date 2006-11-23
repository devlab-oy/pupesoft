<?php

	echo "<font class='head'>".t("ABC-Analyysi: Tuoteosasto tai tuoteryhmä")."<hr></font>";

	// tutkaillaan saadut muuttujat
	$osasto = trim($osasto);
	$try    = trim($try);

	if ($osasto == "") $osasto = trim($osasto2);
	if ($try    == "") $try = trim($try2);
	if ($try    != "") $osasto = "";

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

	echo "</select></td><td class='back'><input type='submit' value='".t("Aja raportti")."'></td>";
	echo "</tr>";
	echo "</table>";
	echo "</form>";


	if ($osasto != '' or $try != '') {

		$valinta = 'luokka';
		$valintalisa = "";

		if ($osasto != '') {
			$valintalisa = " and osasto='$osasto' ";
			$valinta = "luokka_osasto";
		}
		if ($try != '') {
			$valintalisa = " and try='$try' ";
			$valinta = "luokka_try";
		}

		$kentat = array($valinta,'luokka','tuoteno','osasto','try','summa','kate','katepros','kateosuus','vararvo','varaston_kiertonop','myyntierankpl','myyntieranarvo','rivia','puuterivia','palvelutaso','ostoerankpl','ostoeranarvo','osto_rivia','kustannus','kustannus_osto','kustannus_yht','total');

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
					$valinta,
					tuoteno,
					osasto,
					try,
					summa,
					kate,
					katepros,
					kate/$sumrow[yhtkate] * 100	kateosuus,
					vararvo,
					varaston_kiertonop,
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
		if ($valinta == 'luokka_try') 		$otsikko = "Tuoryn";

		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=$valinta&sort=asc$ulisa'>$otsikko<br>".t("Luokka")."</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=luokka&sort=asc$ulisa'>".t("ABC")."<br>".t("Luokka")."</th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=tuoteno&sort=asc$ulisa'>".t("Tuoteno")."</a><br>&nbsp;</th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=osasto&sort=asc$ulisa'>".t("Osasto")."</a><br>&nbsp;</th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=try&sort=asc$ulisa'>".t("Try")."</a><br>&nbsp;</th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=summa&sort=desc$ulisa'>".t("Myynti")."<br>".t("tot")."</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=kate&sort=desc$ulisa'>".t("Kate")."<br>".t("tot")."</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=katepros&sort=desc$ulisa'>".t("Kate")."<br>%</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=kateosuus&sort=desc$ulisa'>".t("Osuus")." %<br>".t("kat").".</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=vararvo&sort=desc$ulisa'>".t("Varast").".<br>".t("arvo")."</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=varaston_kiertonop&sort=desc$ulisa'>".t("Varast").".<br>".t("kiert").".</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=myyntierankpl&sort=desc$ulisa'>".t("Myyerä")."<br>".t("KPL")."</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=myyntieranarvo&sort=desc$ulisa'>".t("Myyerä")."<br>$yhtiorow[valkoodi]</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=rivia&sort=desc$ulisa'>".t("Myyty")."<br>".t("rivejä")."</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=puuterivia&sort=desc$ulisa'>".t("Puute")."<br>".t("rivejä")."</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=palvelutaso&sort=desc$ulisa'>".t("Palvelu")."-<br>".t("taso")." %</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=ostoerankpl&sort=desc$ulisa'>".t("Ostoerä")."<br>".t("KPL")."</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=ostoeranarvo&sort=desc$ulisa'>".t("Ostoerä")."<br>$yhtiorow[valkoodi]</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=osto_rivia&sort=desc$ulisa'>".t("Ostettu")."<br>".t("rivejä")."</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=kustannus&sort=desc$ulisa'>".t("Myynn").".<br>".t("kustan").".</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=kustannus_osto&sort=desc$ulisa'>".t("Oston")."<br>".t("kustan").".</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=kustannus_yht&sort=desc$ulisa'>".t("Kustan").".<br>".t("yht")."</a></th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&order=total&sort=desc$ulisa'>".t("Kate -")."<br>".t("Kustannus")."</a></th>";
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

			$ryhmanimet   = array('A-30','B-20','C-15','D-15','E-10','F-05','G-03','H-02','I-00');
			$ryhmaprossat = array(30.00,20.00,15.00,15.00,10.00,5.00,3.00,2.00,0.00);

			while ($row = mysql_fetch_array($res)) {

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

				echo "<tr>";

				$l = $row[$valinta];
				echo "<td>$ryhmanimet[$l]</td>";

				$l = $row["luokka"];
				echo "<td>$ryhmanimet[$l]</td>";

				echo "<td><a href='../tuote.php?tee=Z&tuoteno=$row[tuoteno]'>$row[tuoteno]</a></td>";
				echo "<td nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&osasto=$row[osasto]'>$row[osasto] $keyosa[selitetark]</a></td>";
				echo "<td nowrap><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRYYHTEENVETO&osasto=$row[osasto]&try=$row[try]'>$row[try] $keytry[selitetark]</a></td>";
				echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["summa"]))."</td>";
				echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["kate"]))."</td>";
				echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["katepros"]))."</td>";
				echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["kateosuus"]))."</td>";
				echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["vararvo"]))."</td>";
				echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["varaston_kiertonop"]))."</td>";
				echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["myyntierankpl"]))."</td>";
				echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["myyntieranarvo"]))."</td>";
				echo "<td align='right'>".str_replace(".",",",sprintf('%.0f',$row["rivia"]))."</td>";
				echo "<td align='right'>".str_replace(".",",",sprintf('%.0f',$row["puuterivia"]))."</td>";
				echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["palvelutaso"]))."</td>";
				echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["ostoerankpl"]))."</td>";
				echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["ostoeranarvo"]))."</td>";
				echo "<td align='right'>".str_replace(".",",",sprintf('%.0f',$row["osto_rivia"]))."</td>";
				echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["kustannus"]))."</td>";
				echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["kustannus_osto"]))."</td>";
				echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["kustannus_yht"]))."</td>";
				echo "<td align='right'>".str_replace(".",",",sprintf('%.1f',$row["total"]))."</td>";
				echo "</tr>\n";

				/*
				$l = $row["luokka"];
				echo sprintf('%-4s',$ryhmanimet[$l])."\t";

				$l = $row["luokan_luokka"];
				echo sprintf('%-4s',$ryhmanimet[$l])."\t";

				echo sprintf('%-20s',$row["tuoteno"])."\t";
				echo sprintf('%-2s',$row["osasto"])."\t";
				echo sprintf('%-5s',$row["try"])."\t";
				echo sprintf('%10s',sprintf('%.1f',$row["summa"]))."\t";;
				echo sprintf('%10s',sprintf('%.1f',$row["kate"]))."\t";;
				echo sprintf('%6s',sprintf('%.1f',$row["katepros"]))."\t";;
				echo sprintf('%6s',sprintf('%.1f',$row["kateosuus"]))."\t";;
				echo sprintf('%10s',sprintf('%.1f',$row["vararvo"]))."\t";;
				echo sprintf('%10s',sprintf('%.1f',$row["varaston_kiertonop"]))."\t";;
				echo sprintf('%10s',sprintf('%.1f',$row["myyntierankpl"]))."\t";;
				echo sprintf('%10s',sprintf('%.1f',$row["myyntieranarvo"]))."\t";;
				echo sprintf('%10s',sprintf('%.0f',$row["rivia"]))."\t";;
				echo sprintf('%10s',sprintf('%.0f',$row["puuterivia"]))."\t";;
				echo sprintf('%6s',sprintf('%.1f',$row["palvelutaso"]))."\t";;
				echo sprintf('%10s',sprintf('%.1f',$row["ostoerankpl"]))."\t";;
				echo sprintf('%10s',sprintf('%.1f',$row["ostoeranarvo"]))."\t";;
				echo sprintf('%10s',sprintf('%.1f',$row["osto_rivia"]))."\t";;
				echo "\n";
				*/

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
			$myyntieranarvoyht 	= round ($ryhmamyyntiyht / $rivilkmyht,2);
			$myyntieranakplyht 	= round ($ryhmakplyht / $rivilkmyht,2);
			$palvelutasoyht 	= round (100 - ($ryhmapuuterivityht / ($ryhmapuuterivityht + $rivilkmyht) * 100),2);
			$ostoeranarvoyht	= round ($ryhmaostotyht / $ryhmaostotrivityht,2);
			$ostoeranakplyht 	= round ($ryhmaostotkplyht / $ryhmaostotrivityht,2);



			echo "<tr>";
			echo "<td colspan='5' class='spec'>".t("Yhteensä").":</td>";
			echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$ryhmamyyntiyht))."</td>";
			echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$ryhmakateyht))."</td>";
			echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$kateprosenttiyht))."</td>";
			echo "<td align='right' class='spec'>".str_replace(".",",",sprintf('%.1f',$kateosuusyht))."</td>";
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
		}
	}

?>