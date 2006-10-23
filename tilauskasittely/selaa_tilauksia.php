<?php

	// k‰ytet‰‰n slavea
	$useslave = 1;

	require ("../inc/parametrit.inc");

	if ($toim == "") $toim = "MYYNTI";

	echo "<font class='head'>".ucfirst(strtolower($toim)).t("selaus").":<hr></font>";

	// menn‰‰ defaulttina aina p‰iv‰n‰kym‰‰n
	if ($tee == "") {
		$tee = "paiva";
	}

	if ($tee == "paiva" and !isset($vv) and !isset($kk) and !isset($pp)) {
		$vv = date("Y");
		$kk = date("m");
		$pp = date("d");
	}

	if ($tee == "kk" and !isset($vv) and !isset($kk)) {
		$vv = date("Y");
		$kk = date("m");
		$pp = 1;
	}

	// edellinen ja seuraava kuukausi
	$ekv = date("Y",mktime(0, 0, 0, $kk-1, 1, $vv));
	$ekk = date("m",mktime(0, 0, 0, $kk-1, 1, $vv));
	$ekp = date("d",mktime(0, 0, 0, $kk-1, 1, $vv));
	$nkv = date("Y",mktime(0, 0, 0, $kk+1, 1, $vv));
	$nkk = date("m",mktime(0, 0, 0, $kk+1, 1, $vv));
	$nkp = date("d",mktime(0, 0, 0, $kk+1, 1, $vv));

	// edellinen ja seuraava p‰iv‰
	$epv = date("Y",mktime(0, 0, 0, $kk, $pp-1, $vv));
	$epk = date("m",mktime(0, 0, 0, $kk, $pp-1, $vv));
	$epp = date("d",mktime(0, 0, 0, $kk, $pp-1, $vv));
	$npv = date("Y",mktime(0, 0, 0, $kk, $pp+1, $vv));
	$npk = date("m",mktime(0, 0, 0, $kk, $pp+1, $vv));
	$npp = date("d",mktime(0, 0, 0, $kk, $pp+1, $vv));

	$etsi='';
	if (is_string($haku))  $etsi = "and lasku.nimi LIKE '%$haku%'";
	if (is_numeric($haku)) $etsi = "and lasku.ytunnus LIKE '%$haku%'";

	// t‰ss‰ myyntitilausten queryt
	if ($toim == "MYYNTI") {
		// kuukausin‰kym‰
		$query1 = "	SELECT DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, DATE_FORMAT(luontiaika,'%a') vkpvm,
					count(distinct lasku.tunnus) tilauksia,
					count(distinct tilausrivi.tunnus) riveja,
					round(sum(tilausrivi.hinta*(1-(tilausrivi.ale/100))*(1-(lasku.erikoisale/100))*(tilausrivi.varattu+tilausrivi.kpl)),2) summa,
					round(sum(tilausrivi.hinta*(1-(tilausrivi.ale/100))*(1-(lasku.erikoisale/100))*(tilausrivi.varattu+tilausrivi.kpl)/if('$yhtiorow[alv_kasittely]'='',1+(tilausrivi.alv/100),1)),2) arvo
					FROM lasku use index (yhtio_tila_luontiaika)
					JOIN tilausrivi on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tyyppi!='D')
					WHERE lasku.yhtio = '$kukarow[yhtio]' and
					tila in ('L') and
					luontiaika >= '$vv-$kk-01' and
					luontiaika < '$nkv-$nkk-01'
					GROUP BY pvm
					ORDER BY luontiaika";

		// p‰iv‰n‰kym‰
		$query2 = "	SELECT lasku.tunnus, if(lasku.nimi!=lasku.toim_nimi, concat_ws(' / ', lasku.nimi, lasku.toim_nimi),lasku.nimi) nimi, DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, DATE_FORMAT(luontiaika,'%a') vkpvm,
					round(sum(tilausrivi.hinta*(1-(tilausrivi.ale/100))*(1-(lasku.erikoisale/100))*(tilausrivi.varattu+tilausrivi.kpl)),2) summa,
					round(sum(tilausrivi.hinta*(1-(tilausrivi.ale/100))*(1-(lasku.erikoisale/100))*(tilausrivi.varattu+tilausrivi.kpl)/if('$yhtiorow[alv_kasittely]'='',1+(tilausrivi.alv/100),1)),2) arvo
					FROM lasku use index (yhtio_tila_luontiaika)
					JOIN tilausrivi on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tyyppi!='D')
					WHERE lasku.yhtio = '$kukarow[yhtio]' and
					tila in ('L') and
					luontiaika >= '$vv-$kk-$pp 00:00:00' and
					luontiaika <= '$vv-$kk-$pp 23:59:59'
					$etsi
					GROUP BY lasku.tunnus
					ORDER BY luontiaika";

		// tilausn‰kym‰
		$query3 = "	SELECT otunnus tunnus, DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, tuoteno, nimitys, kpl+varattu kpl, tilausrivi.hinta, ale, erikoisale, tilausrivi.alv,
					round(tilausrivi.hinta*(1-(tilausrivi.ale/100))*(1-(lasku.erikoisale/100))*(tilausrivi.varattu+tilausrivi.kpl),2) summa,
					round(tilausrivi.hinta*(1-(tilausrivi.ale/100))*(1-(lasku.erikoisale/100))*(tilausrivi.varattu+tilausrivi.kpl)/if('$yhtiorow[alv_kasittely]'='',1+(tilausrivi.alv/100),1),2) arvo
					FROM tilausrivi use index (yhtio_otunnus)
					JOIN lasku on (lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus)
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]' and
					tyyppi != 'D' and
					otunnus = '$tunnus'
					ORDER BY tilausrivi.tunnus";
	}

	// t‰ss‰ ostotilausten queryt
	if ($toim == "OSTO") {
		// kuukausin‰kym‰
		$query1 = "	SELECT DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, DATE_FORMAT(luontiaika,'%a') vkpvm,
					count(distinct lasku.tunnus) tilauksia, count(distinct tilausrivi.tunnus) riveja
					FROM lasku use index (yhtio_tila_luontiaika)
					JOIN tilausrivi on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tyyppi!='D')
					WHERE lasku.yhtio = '$kukarow[yhtio]' and
					tila in ('O') and
					luontiaika >= '$vv-$kk-01' and
					luontiaika < '$nkv-$nkk-01'
					GROUP BY pvm
					ORDER BY luontiaika";

		// p‰iv‰n‰kym‰
		$query2 = "	SELECT lasku.tunnus, lasku.nimi, DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, DATE_FORMAT(luontiaika,'%a') vkpvm,
					round(tilausrivi.hinta*(1-(tilausrivi.ale/100))*(1-(lasku.erikoisale/100))*(tilausrivi.varattu+tilausrivi.kpl),2) summa, lasku.valkoodi
					FROM lasku use index (yhtio_tila_luontiaika)
					JOIN tilausrivi on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tyyppi!='D')
					WHERE lasku.yhtio = '$kukarow[yhtio]' and
					tila in ('O') and
					luontiaika >= '$vv-$kk-$pp 00:00:00' and
					luontiaika <= '$vv-$kk-$pp 23:59:59'
					$etsi
					GROUP BY lasku.tunnus
					ORDER BY luontiaika";

		// tilausn‰kym‰
		$query3 = "	SELECT otunnus, DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, tuoteno, nimitys, kpl+varattu kpl, tilausrivi.hinta,
					round(tilausrivi.hinta*(1-(tilausrivi.ale/100))*(1-(lasku.erikoisale/100))*(tilausrivi.varattu+tilausrivi.kpl),2) arvo, lasku.valkoodi
					FROM tilausrivi use index (yhtio_otunnus)
					JOIN lasku on (lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus)
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]' and
					otunnus = '$tunnus' and
					tyyppi!='D'
					ORDER BY tilausrivi.tunnus";
	}

	// t‰ss‰ keikkojen queryt
	if ($toim == "KEIKKA") {
		// kuukausin‰kym‰
		$query1 = "	SELECT DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, DATE_FORMAT(luontiaika,'%a') vkpvm,
					count(distinct lasku.tunnus) keikkoja, count(distinct tilausrivi.tunnus) riveja
					FROM lasku use index (yhtio_tila_luontiaika)
					JOIN tilausrivi on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.uusiotunnus=lasku.tunnus and tyyppi!='D')
					WHERE lasku.yhtio = '$kukarow[yhtio]' and
					tila in ('K') and
					vanhatunnus = 0 and
					luontiaika >= '$vv-$kk-01' and
					luontiaika < '$nkv-$nkk-01'
					GROUP BY pvm
					ORDER BY luontiaika";

		// p‰iv‰n‰kym‰
		$query2 = "	SELECT lasku.laskunro keikka, lasku.tunnus, lasku.nimi, DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, if(mapvm='0000-00-00','',DATE_FORMAT(mapvm,'%d.%m.%Y')) jlaskenta,
					round(sum(tilausrivi.hinta*(1-(tilausrivi.ale/100))*(1-(lasku.erikoisale/100))*(tilausrivi.varattu+tilausrivi.kpl)),2) summa, lasku.valkoodi
					FROM lasku use index (yhtio_tila_luontiaika)
					JOIN tilausrivi on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.uusiotunnus=lasku.tunnus and tyyppi!='D')
					WHERE lasku.yhtio = '$kukarow[yhtio]' and
					tila in ('K') and
					vanhatunnus = 0 and
					luontiaika >= '$vv-$kk-$pp 00:00:00' and
					luontiaika <= '$vv-$kk-$pp 23:59:59'
					$etsi
					GROUP BY lasku.tunnus
					ORDER BY luontiaika";

		// tilausn‰kym‰
		$query3 = "	SELECT lasku.laskunro keikka, DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, tuoteno, nimitys, kpl+varattu kpl, tilausrivi.hinta,
					round(tilausrivi.hinta*(1-(tilausrivi.ale/100))*(1-(lasku.erikoisale/100))*(tilausrivi.varattu+tilausrivi.kpl),2) arvo, lasku.valkoodi
					FROM tilausrivi use index (uusiotunnus_index)
					JOIN lasku on (lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.uusiotunnus)
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]' and
					uusiotunnus = '$tunnus' and
					tyyppi!='D'
					ORDER BY tilausrivi.tunnus";
	}

	// t‰ss‰ valmistusten queryt
	if ($toim == "VALMISTUS") {
		// kuukausin‰kym‰
		$query1 = "	SELECT DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, DATE_FORMAT(luontiaika,'%a') vkpvm,
					count(distinct lasku.tunnus) valmistuksia,
					count(distinct tilausrivi.tunnus) riveja,
					round(sum(tilausrivi.hinta*(1-(tilausrivi.ale/100))*(1-(lasku.erikoisale/100))*(tilausrivi.varattu+tilausrivi.kpl)),2) arvo
					FROM lasku use index (yhtio_tila_luontiaika)
					JOIN tilausrivi on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tyyppi!='D')
					WHERE lasku.yhtio = '$kukarow[yhtio]' and
					tila in ('V') and
					luontiaika >= '$vv-$kk-01' and
					luontiaika < '$nkv-$nkk-01'
					GROUP BY pvm
					ORDER BY luontiaika";

		// p‰iv‰n‰kym‰
		$query2 = "	SELECT lasku.tunnus valmistus, lasku.tunnus, lasku.nimi, DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, DATE_FORMAT(luontiaika,'%a') vkpvm,
					round(sum(tilausrivi.hinta*(1-(tilausrivi.ale/100))*(1-(lasku.erikoisale/100))*(tilausrivi.varattu+tilausrivi.kpl)),2) arvo
					FROM lasku use index (yhtio_tila_luontiaika)
					JOIN tilausrivi on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tyyppi!='D')
					WHERE lasku.yhtio = '$kukarow[yhtio]' and
					tila in ('V') and
					luontiaika >= '$vv-$kk-$pp 00:00:00' and
					luontiaika <= '$vv-$kk-$pp 23:59:59'
					$etsi
					GROUP BY lasku.tunnus
					ORDER BY luontiaika";

		// tilausn‰kym‰
		$query3 = "	SELECT lasku.tunnus valmistus, DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, tuoteno, nimitys, kpl+varattu kpl, tilausrivi.hinta,
					round(tilausrivi.hinta*(1-(tilausrivi.ale/100))*(1-(lasku.erikoisale/100))*(tilausrivi.varattu+tilausrivi.kpl),2) arvo
					FROM tilausrivi use index (yhtio_otunnus)
					JOIN lasku on (lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus)
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]' and
					otunnus = '$tunnus'
					ORDER BY perheid desc, tyyppi in ('W','L','D','V'), tilausrivi.tunnus";
	}

	// t‰ss‰ tarjousten queryt
	if ($toim == "TARJOUS") {
		// kuukausin‰kym‰
		$query1 = "	SELECT DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, DATE_FORMAT(luontiaika,'%a') vkpvm,
					count(distinct lasku.tunnus) tilauksia,
					count(distinct tilausrivi.tunnus) riveja,
					round(sum(tilausrivi.hinta*(1-(tilausrivi.ale/100))*(1-(lasku.erikoisale/100))*(tilausrivi.varattu+tilausrivi.kpl)),2) summa,
					round(sum(tilausrivi.hinta*(1-(tilausrivi.ale/100))*(1-(lasku.erikoisale/100))*(tilausrivi.varattu+tilausrivi.kpl)/if('$yhtiorow[alv_kasittely]'='',1+(tilausrivi.alv/100),1)),2) arvo
					FROM lasku use index (yhtio_tila_luontiaika)
					JOIN tilausrivi on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tyyppi!='D')
					WHERE lasku.yhtio = '$kukarow[yhtio]' and
					tila in ('T') and
					luontiaika >= '$vv-$kk-01' and
					luontiaika < '$nkv-$nkk-01'
					GROUP BY pvm
					ORDER BY luontiaika";

		// p‰iv‰n‰kym‰
		$query2 = "	SELECT lasku.tunnus, if(lasku.nimi!=lasku.toim_nimi, concat_ws(' / ', lasku.nimi, lasku.toim_nimi),lasku.nimi) nimi, DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, DATE_FORMAT(luontiaika,'%a') vkpvm,
					round(sum(tilausrivi.hinta*(1-(tilausrivi.ale/100))*(1-(lasku.erikoisale/100))*(tilausrivi.varattu+tilausrivi.kpl)),2) summa,
					round(sum(tilausrivi.hinta*(1-(tilausrivi.ale/100))*(1-(lasku.erikoisale/100))*(tilausrivi.varattu+tilausrivi.kpl)/if('$yhtiorow[alv_kasittely]'='',1+(tilausrivi.alv/100),1)),2) arvo
					FROM lasku use index (yhtio_tila_luontiaika)
					JOIN tilausrivi on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tyyppi!='D')
					WHERE lasku.yhtio = '$kukarow[yhtio]' and
					tila in ('T') and
					luontiaika >= '$vv-$kk-$pp 00:00:00' and
					luontiaika <= '$vv-$kk-$pp 23:59:59'
					$etsi
					GROUP BY lasku.tunnus
					ORDER BY luontiaika";

		// tilausn‰kym‰
		$query3 = "	SELECT otunnus, DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, tuoteno, nimitys, kpl+varattu kpl, tilausrivi.hinta, ale, erikoisale, tilausrivi.alv,
					round(tilausrivi.hinta*(1-(tilausrivi.ale/100))*(1-(lasku.erikoisale/100))*(tilausrivi.varattu+tilausrivi.kpl),2) summa,
					round(tilausrivi.hinta*(1-(tilausrivi.ale/100))*(1-(lasku.erikoisale/100))*(tilausrivi.varattu+tilausrivi.kpl)/if('$yhtiorow[alv_kasittely]'='',1+(tilausrivi.alv/100),1),2) arvo
					FROM tilausrivi use index (yhtio_otunnus)
					JOIN lasku on (lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus)
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]' and
					tyyppi != 'D' and
					otunnus = '$tunnus'
					ORDER BY tilausrivi.tunnus";
	}

	if ($tee == "paiva") {
		$result = mysql_query($query2) or pupe_error($query2);
		echo "<a href='$PHP_SELF?toim=$toim&tee=paiva&vv=$epv&kk=$epk&pp=$epp&haku=$haku'>".t("Edellinen p‰iv‰")."</a> - <a href='$PHP_SELF?toim=$toim&tee=paiva&vv=$npv&kk=$npk&pp=$npp&haku=$haku'>".t("Seuraava p‰iv‰")."</a>";
		echo " - <a href=$PHP_SELF?toim=$toim&tee=kk&vv=$vv&kk=$kk>".t("Kuukausin‰kym‰")."</a>";
		echo "<br><br>";

		echo "<form method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' name='tee' value='paiva'>";
		echo "<input type='hidden' name='pp' value='$pp'>";
		echo "<input type='hidden' name='kk' value='$kk'>";
		echo "<input type='hidden' name='vv' value='$vv'>";

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Hae nimell‰ tai numerolla").":</th>";
		echo "<td><input type='text' name='haku' value='$haku'></td>";
		echo "<td class='back'><input type='submit' value='".t("Hae")."'></td>";
		echo "</tr>";
		echo "</table>";

		echo "</form>";

		//echo "$query2<br><br>";
	}
	elseif ($tee == "kk") {
		$result = mysql_query($query1) or pupe_error($query1);
		echo "<a href='$PHP_SELF?toim=$toim&tee=kk&vv=$ekv&kk=$ekk&pp=$ekp'>".t("Edellinen kuukausi")."</a> - <a href='$PHP_SELF?toim=$toim&tee=kk&vv=$nkv&kk=$nkk&pp=$nkp'>".t("Seuraava kuukausi")."</a>";
		echo "<br><br>";
		//echo "$query1<br><br>";
	}
	elseif ($tee == "tilaus") {
		$result = mysql_query($query3) or pupe_error($query3);
		echo "<a href=$PHP_SELF?toim=$toim&tee=paiva&vv=$vv&kk=$kk&pp=$pp>".t("P‰iv‰n‰kym‰")."</a> - <a href=$PHP_SELF?toim=$toim&tee=kk&vv=$vv&kk=$kk>".t("Kuukausin‰kym‰")."</a>";
		echo "<br><br>";
		//echo "$query3<br><br>";
	}
	else {
		echo "Kaboom!";
		unset($result);
	}

	if (mysql_num_rows($result) > 0) {

		echo "<table>";

		echo "<tr>";

		for ($i = 0; $i < mysql_num_fields($result); $i++) {
			echo "<th>".mysql_field_name($result, $i)."</th>";
		}

		echo "</tr>";

		$arvo    = 0;
		$summa   = 0;
		$teemita = "";

		if ($tee == "kk") {
			$teemita = "paiva";
		}

		if ($tee == "paiva") {
			$teemita = "tilaus";
		}

		while ($row = mysql_fetch_array($result)) {

			list ($pp,$kk,$vv) = explode(".", $row["pvm"],3);

			echo "<tr>";

			for ($i = 0; $i < mysql_num_fields($result); $i++) {
				echo "<td>$row[$i]</td>";
			}

			$arvo  += $row["arvo"];
			$summa += $row["summa"];

			// jos ollaan muussa tilassa ku tilausn‰kym‰ss‰ tehd‰‰ n‰yt‰ nappi
			if ($tee != "tilaus") {
				echo "<form method='post' action='$PHP_SELF'>";
				echo "<input type='hidden' name='tee' value='$teemita'>";
				echo "<input type='hidden' name='pp' value='$pp'>";
				echo "<input type='hidden' name='kk' value='$kk'>";
				echo "<input type='hidden' name='vv' value='$vv'>";
				echo "<input type='hidden' name='toim' value='$toim'>";
				echo "<input type='hidden' name='tunnus' value='$row[tunnus]'>";
				echo "<td class='back'><input type='submit' value='".t("N‰yt‰")."'></td>";
				echo "</form>";
			}

			// katotaan lˆytyykˆ oikeuksia vaihda_tilaan...
			$oikeuquery = "select * from oikeu where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]' and nimi like '%vaihda_tila.php'";
			$apuoikeures = mysql_query($oikeuquery) or pupe_error($oikeuquery);

			// jos kyseess‰ on myyntitilaus, ollaan p‰iv‰n‰kym‰ss‰ ja meill‰ on oikeudet, niin tehd‰‰n t‰ll‰nenki nappula
			if ($toim == "MYYNTI" and $tee == "paiva" and mysql_num_rows($apuoikeures) > 0) {
				echo "<form method='post' action='vaihda_tila.php'>";
				echo "<input type='hidden' name='parametrit' value='$teemita#$pp#$kk#$vv#$toim'>";
				echo "<input type='hidden' name='tee' value='valitse'>";
				echo "<input type='hidden' name='tunnus' value='$row[tunnus]'>";
				echo "<td class='back'><input type='submit' value='".t("Vaihda tila")."'></td>";
				echo "</form>";
			}

			echo "</tr>";


		}

		echo "</table>";

		if ($arvo != 0 or $summa != 0) {
			echo "<br><table cellpadding='5'>";
			if ($arvo != 0) {
				echo "<tr>";
				echo "<th>".t("Arvo yhteens‰").": </th>";
				echo "<td>".sprintf('%.02f',$arvo)."</td>";
				echo "</tr>";
			}
			if ($summa != 0 and $arvo != 0 and $arvo != $summa) {
				echo "<tr>";
				echo "<th>".t("Summa yhteens‰").": </th>";
				echo "<td>".sprintf('%.02f',$summa)."</td>";
				echo "</tr>";
			}
			echo "</table>";
		}

	}
	else {
		echo t("Ei tilauksia")."...";
	}

	require ("inc/footer.inc");
?>
