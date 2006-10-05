<?php
	
	//ryhmäjako
	$ryhmanimet   = array('A-50','B-30','C-20');
	$ryhmaprossat = array(50.00,30.00,20.00);

	echo "<font class='head'>".t("ABC-Analyysiä: ABC-luokka")." $ryhmanimet[$luokka]<hr></font>";

	$kentat = array('luokka','tuoteno','osasto','try','osto_rivia','summa','kate','katepros','kateosuus','palvelutaso');

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
				and tyyppi='A'
				and luokka = '$luokka'";
	$sumres = mysql_query($query) or pupe_error($query);
	$sumrow = mysql_fetch_array($sumres);

	if ($sumrow["yhtkate"] == 0) {
		$sumrow["yhtkate"] = 0.01;
	}
	
	//haetaan rivien arvot
	$query = "	SELECT
				luokka,
				tuoteno,
				osasto,
				try,
				osto_rivia,
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
				and tyyppi='A'
				and luokka = '$luokka'
				$lisa
				$hav
				ORDER BY $jarjestys";
	$res = mysql_query($query) or pupe_error($query);

	echo "<table>";

	echo "<tr>";
	echo "<th nowrap><a href='$PHP_SELF?tee=LUOKKA&luokka=$luokka&order=luokka&sort=asc$ulisa'>".t("ABC")."<br>".t("Luokka")."</th>";
	echo "<th nowrap><a href='$PHP_SELF?tee=LUOKKA&luokka=$luokka&order=tuoteno&sort=asc$ulisa'>".t("Asiakas")."</a><br>&nbsp;</th>";
	echo "<th nowrap><a href='$PHP_SELF?tee=LUOKKA&luokka=$luokka&order=osasto&sort=asc$ulisa'>".t("Osasto")."</a><br>&nbsp;</th>";
	echo "<th nowrap><a href='$PHP_SELF?tee=LUOKKA&luokka=$luokka&order=try&sort=asc$ulisa'>".t("Piiri")."</a><br>&nbsp;</th>";
	echo "<th nowrap><a href='$PHP_SELF?tee=LUOKKA&luokka=$luokka&order=osto_rivia&sort=asc$ulisa'>".t("Myyjä")."</a><br>&nbsp;</th>";
	echo "<th nowrap><a href='$PHP_SELF?tee=LUOKKA&luokka=$luokka&order=summa&sort=desc$ulisa'>".t("Myynti")."<br>".t("tot")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?tee=LUOKKA&luokka=$luokka&order=kate&sort=desc$ulisa'>".t("Kate")."<br>".t("tot")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?tee=LUOKKA&luokka=$luokka&order=katepros&sort=desc$ulisa'>".t("Kate")."<br>%</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?tee=LUOKKA&luokka=$luokka&order=kateosuus&sort=desc$ulisa'>".t("Osuus")." %<br>".t("kat").".</a></th>";
	//echo "<th nowrap><a href='$PHP_SELF?tee=LUOKKA&luokka=$luokka&order=myyntierankpl&sort=desc$ulisa'>".t("Myyerä")."<br>".t("KPL")."</a></th>";
	//echo "<th nowrap><a href='$PHP_SELF?tee=LUOKKA&luokka=$luokka&order=myyntieranarvo&sort=desc$ulisa'>".t("Myyerä")."<br>$yhtiorow[valkoodi]</a></th>";
	//echo "<th nowrap><a href='$PHP_SELF?tee=LUOKKA&luokka=$luokka&order=rivia&sort=desc$ulisa'>Myyty<br>".t("rivejä")."</a></th>";
	//echo "<th nowrap><a href='$PHP_SELF?tee=LUOKKA&luokka=$luokka&order=puuterivia&sort=desc$ulisa'>".t("Puute")."<br>".t("rivejä")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?tee=LUOKKA&luokka=$luokka&order=palvelutaso&sort=desc$ulisa'>".t("Palvelu")."-<br>".t("taso")." %</a></th>";
	//echo "<th nowrap><a href='$PHP_SELF?tee=LUOKKA&luokka=$luokka&order=kustannus_yht&sort=desc$ulisa'>".t("Kustan").".<br>".t("yht")."</a></th>";

	echo "<form action='$PHP_SELF?tee=LUOKKA&luokka=$luokka' method='post'>";
	echo "<tr>";

	for ($i = 0; $i < count($kentat); $i++) {
		echo "<th><input type='text' name='haku[$i]' value='$haku[$i]' size='5'></th>";
	}

	echo "<td class='back'><input type='Submit' value='".t("Etsi")."'></td></form></tr>";

		//jos rivejä ei löydy
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
			echo "<td><a href='$PHP_SELF?tee=YHTEENVETO'>$ryhmanimet[$l]</a></td>";

			echo "<td><a href='../crm/asiakasmemo.php?ytunnus=$row[tuoteno]'>$row[tuoteno] $asrow[nimi]</a></td>";
			echo "<td>$row[osasto]</td>";
			echo "<td>$row[try]</td>";
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
		if ($ryhmamyyntiyht != 0)	$kateprosenttiyht = round ($ryhmakateyht / $ryhmamyyntiyht * 100,2);
		else $kateprosenttiyht = 0;

		if ($sumrow["yhtkate"] != 0)	$kateosuusyht = round ($ryhmakateyht / $sumrow["yhtkate"] * 100,2);
		else $kateosuusyht = 0;

		if ($rivilkmyht != 0)	$myyntieranarvoyht = round ($ryhmamyyntiyht / $rivilkmyht,2);
		else $myyntieranarvoyht = 0;

		if ($rivilkmyht != 0)	$myyntieranakplyht = round ($ryhmakplyht / $rivilkmyht,2);
		else $myyntieranakplyht = 0;

		if ($ryhmapuuterivityht + $rivilkmyht != 0)	$palvelutasoyht = round (100 - ($ryhmapuuterivityht / ($ryhmapuuterivityht + $rivilkmyht) * 100),2);
		else $palvelutasoyht = 0;


		echo "<tr>";
		echo "<td colspan='5' class='spec'>Yhteensä:</td>";
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

?>