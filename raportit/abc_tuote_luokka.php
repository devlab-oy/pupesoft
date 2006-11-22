<?php

	echo "<font class='head'>".t("ABC-Analyysiä: ABC-luokka")." $ryhmanimet[$luokka]<hr></font>";

	if ($toim == "kate") {
		$abcwhat = "kate";
		$abcchar = "TK";
	}
	else {
		$abcwhat = "summa";
		$abcchar = "TM";
	}

	$ryhmanimet   = array('A-30','B-20','C-15','D-15','E-10','F-05','G-03','H-02','I-00');
	$ryhmaprossat = array(30.00,20.00,15.00,15.00,10.00,5.00,3.00,2.00,0.00);

	$kentat = array('luokka','tuoteno','osasto','try','summa','kate','katepros','kateosuus','vararvo','varaston_kiertonop','myyntierankpl','myyntieranarvo','rivia','puuterivia','palvelutaso','ostoerankpl','ostoeranarvo','osto_rivia','kustannus','kustannus_osto','kustannus_yht');

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
		$jarjestys = "luokka, $abcwhat desc";
	}


	//kauden yhteismyynnit ja katteet
	$query = "	SELECT
				sum(summa) yhtmyynti,
				sum(kate)  yhtkate
				FROM abc_aputaulu
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi='$abcchar'
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
				kustannus_yht
				FROM abc_aputaulu
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi='$abcchar'
				and luokka = '$luokka'
				$lisa
				$hav
				ORDER BY $jarjestys";
	$res = mysql_query($query) or pupe_error($query);

	echo "<table>";

	echo "<tr>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&order=luokka&sort=asc$ulisa'>".t("ABC")."<br>".t("Luokka")."</th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&order=tuoteno&sort=asc$ulisa'>".t("Tuoteno")."</a><br>&nbsp;</th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&order=osasto&sort=asc$ulisa'>".t("Osasto")."</a><br>&nbsp;</th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&order=try&sort=asc$ulisa'>".t("Try")."</a><br>&nbsp;</th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&order=summa&sort=desc$ulisa'>".t("Myynti")."<br>".t("tot")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&order=kate&sort=desc$ulisa'>".t("Kate")."<br>".t("tot")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&order=katepros&sort=desc$ulisa'>".t("Kate")."<br>%</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&order=kateosuus&sort=desc$ulisa'>".t("Osuus")." %<br>".t("kat").".</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&order=vararvo&sort=desc$ulisa'>".t("Varast").".<br>".t("arvo")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&order=varaston_kiertonop&sort=desc$ulisa'>".t("Varast").".<br>".t("kiert").".</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&order=myyntierankpl&sort=desc$ulisa'>".t("Myyerä")."<br>".t("KPL")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&order=myyntieranarvo&sort=desc$ulisa'>".t("Myyerä")."<br>$yhtiorow[valkoodi]</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&order=rivia&sort=desc$ulisa'>Myyty<br>".t("rivejä")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&order=puuterivia&sort=desc$ulisa'>".t("Puute")."<br>".t("rivejä")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&order=palvelutaso&sort=desc$ulisa'>".t("Palvelu")."-<br>".t("taso")." %</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&order=ostoerankpl&sort=desc$ulisa'>".t("Ostoerä")."<br>".t("KPL")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&order=ostoeranarvo&sort=desc$ulisa'>".t("Ostoerä")."<br>$yhtiorow[valkoodi]</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&order=osto_rivia&sort=desc$ulisa'>".t("Ostettu")."<br>".t("rivejä")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&order=kustannus&sort=desc$ulisa'>".t("Myynn").".<br>".t("kustan").".</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&order=kustannus_osto&sort=desc$ulisa'>".t("Oston")."<br>".t("kustan").".</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka&order=kustannus_yht&sort=desc$ulisa'>".t("Kustan").".<br>".t("yht")."</a></th>";

	echo "<form action='$PHP_SELF?tee=LUOKKA&luokka=$luokka' method='post'>";
	echo "<input type='hidden' name='toim' value='$toim'>";
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

		while ($row = mysql_fetch_array($res)) {

			echo "<tr>";

			$l = $row["luokka"];
			echo "<td><a href='$PHP_SELF?toim=$toim&tee=YHTEENVETO'>$ryhmanimet[$l]</a></td>";

			echo "<td><a href='$PHP_SELF?toim=$toim&tee=TUOTE&teekutsu=LUOKKA&luokka=$luokka&tuoteno=$row[tuoteno]'>$row[tuoteno]</a></td>";
			echo "<td>$row[osasto]</td>";
			echo "<td>$row[try]</td>";
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

		}


		//yhteensärivi
		if ($ryhmamyyntiyht != 0)	$kateprosenttiyht = round ($ryhmakateyht / $ryhmamyyntiyht * 100,2);
		else $kateprosenttiyht = 0;

		if ($sumrow["yhtkate"] != 0)	$kateosuusyht = round ($ryhmakateyht / $sumrow["yhtkate"] * 100,2);
		else $kateosuusyht = 0;

		if ($ryhmanvarastonarvoyht != 0)	$kiertonopeusyht = round (($ryhmamyyntiyht - $ryhmakateyht) / $ryhmanvarastonarvoyht,2);
		else $kiertonopeusyht = 0;

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
		echo "<td colspan='4' class='spec'>Yhteensä:</td>";
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

		echo "</tr>\n";

		echo "</table>";
	}

?>