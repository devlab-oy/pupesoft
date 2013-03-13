<?php

	//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
	$useslave = 1;

	//$toim='YKS' tarkottaa yksinkertainen ja silloin ei v‰litet‰ onko tuotteella eankoodia vaan tulostetaan suoraan tuoteno viivakoodiin
	require("inc/parametrit.inc");

	echo "<font class='head'>".t("Tulosta hyllytarroja")."</font><hr>";

	$lets='';

	if ($tee == 'Z') {
		if ($malli== '') {
			$tee = 'Y';
			$varaosavirhe = t("Sinun on valittava tulostusmalli");
		}

		if ($lisa == '') {
			if ($ahyllyalue == '' or $ahyllynro == '' or $ahyllyvali == '' or $ahyllytaso == '' or $lhyllyalue == '' or $lhyllynro == '' or $lhyllyvali == '' or $lhyllytaso == '') {
				$tee = 'Y';
				$varaosavirhe = t("Sinun on annettava t‰ydellinen osoitev‰li")."<br>";
			}
		}
		else {
			if ($yhyllyalue == '' or $yhyllynro == '' or $yhyllyvali == '' or $yhyllytaso == '') {
				$tee = 'Y';
				$varaosavirhe = t("Sinun on annettava t‰ydellinen osoite")."<br>";
			}
		}

		if ($lisa == '') {
			$query = "	SELECT distinct concat_ws('-',hyllyalue,if(hyllynro!='',hyllynro,0),if(hyllyvali!='',hyllyvali,0),if(hyllytaso!='',hyllytaso,0)) as paikka
						FROM tuotepaikat
						WHERE yhtio = '$kukarow[yhtio]' and
						concat(rpad(upper(hyllyalue),  5, '0'),lpad(upper(hyllynro),  5, '0'),lpad(upper(hyllyvali),  5, '0'),lpad(upper(hyllytaso),  5, '0')) >= concat(rpad(upper('$ahyllyalue'), 5, '0'),lpad(upper('$ahyllynro'), 5, '0'),lpad(upper('$ahyllyvali'), 5, '0'),lpad(upper('$ahyllytaso'), 5, '0')) and
						concat(rpad(upper(hyllyalue),  5, '0'),lpad(upper(hyllynro),  5, '0'),lpad(upper(hyllyvali),  5, '0'),lpad(upper(hyllytaso),  5, '0')) <= concat(rpad(upper('$lhyllyalue'), 5, '0'),lpad(upper('$lhyllynro'), 5, '0'),lpad(upper('$lhyllyvali'), 5, '0'),lpad(upper('$lhyllytaso'), 5, '0'))
						ORDER BY hyllyalue,hyllynro+0,hyllyvali+0,hyllytaso+0";
			$paikatres = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($paikatres) > 0) {
				while ($paikatrow=mysql_fetch_array($paikatres)) {
					$paikat[] = $paikatrow['paikka'];
				}
			}
			else {
				$tee = 'Y';
				$varaosavirhe = t("Aluev‰lilt‰ ei lˆytynyt yht‰‰n paikkaa!")."<br>";
			}
		}
		else {
			$paikat = array();
			$paikat[] = $yhyllyalue."-".$yhyllynro."-".$yhyllyvali."-".$yhyllytaso;
		}
	}


	if ($tee=='Y') echo "<font class='error'>$varaosavirhe</font>";

	if ($tee== 'Z' and $ulos == '') {
		$query = "SELECT komento from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus = '$kirjoitin'";
		$komres = mysql_query($query) or pupe_error($query);
		$komrow = mysql_fetch_array($komres);
		$komento = $komrow['komento'];

		foreach ($paikat as $paikka) {
			require("inc/tulosta_hyllytarrat_tec.inc");
		}

		echo t("Hyllytarrat tulostuu")."...<br><br>";
		$tee = '';
	}

	$formi  = 'formi';
	$kentta = 'ahyllyalue';

	echo t("Osoitev‰li");

	echo "<form method='post' name='$formi' autocomplete='off'>";
	echo "<input type='hidden' name='tee' value='Z'>";
	echo "<input type='hidden' name='toim' value='$toim'>";

	echo "<table>";
	echo "<tr><th>".t("Alkuosoite")."</th></tr><tr>";
	echo "<td>",hyllyalue("ahyllyalue", $ahyllyalue);
	echo "-";
	echo "<input type='text' name='ahyllynro' size='5' maxlength='5' value='$ahyllynro'>";
	echo "-";
	echo "<input type='text' name='ahyllyvali' size='5' maxlength='5' value='$ahyllyvali'>";
	echo "-";
	echo "<input type='text' name='ahyllytaso' size='5' maxlength='5' value='$ahyllytaso'></td></tr>";

	echo "<tr><th>".t("Loppuosoite")."</th></tr><tr>";
	echo "<td>",hyllyalue("lhyllyalue", $lhyllyalue);
	echo "-";
	echo "<input type='text' name='lhyllynro' size='5' maxlength='5' value='$lhyllynro'>";
	echo "-";
	echo "<input type='text' name='lhyllyvali' size='5' maxlength='5' value='$lhyllyvali'>";
	echo "-";
	echo "<input type='text' name='lhyllytaso' size='5' maxlength='5' value='$lhyllytaso'></td></tr>";

	echo "<tr><th>".t("Kirjoitin")."</th><th>".t("Malli")."</th></tr>";

	$query = "SELECT * from kirjoittimet where yhtio='$kukarow[yhtio]' order by kirjoitin";
	$kires = mysql_query($query) or pupe_error($query);

	echo "<td><select name='kirjoitin'>";
	echo "<option value=''>".t("Ei kirjoitinta")."</option>";

	while ($kirow=mysql_fetch_array($kires)) {
		if ($kirow['tunnus']==$kirjoitin) $select='SELECTED';
		else $select = '';

		echo "<option value='$kirow[tunnus]' $select>$kirow[kirjoitin]</option>";
	}

	mysql_data_seek($kires,0);

	echo "</select></td>";

	//t‰h‰n arrayhin pit‰‰ lis‰t‰ uusia malleja jos tehd‰‰n uusia inccej‰ ja ylemp‰n‰ tehd‰ iffej‰.
	$pohjat=array();
	$pohjat[]='Tec 80x20x4';

	echo "<td><select name='malli'>";
	//echo "<option value=''>".t("Ei mallia")."</option>";
	foreach ($pohjat as $pohja) {
		if ($pohja==$malli) $select='SELECTED';
		else $select = '';

		echo "<option value='$pohja' $select>$pohja</option>";
	}

	echo "</select></td>";

	echo "<td class='back'><input type='Submit' value='".t("Tulosta")."'></td>";
	echo "</tr></table></form><br><br><br>";

	// Annetaan mahollisuus tulostaa yksitt‰inen tarra
	echo "Yksitt‰inen osoite";

	echo "<table><tr>";
	echo "<th>".t("Hyllyosoite")."</th><th>".t("Kirjoitin")."</th><th>".t("Malli")."</th>";

	echo "<tr><form method='post' name='kala' autocomplete='off'>";
	echo "<input type='hidden' name='tee' value='Z'>";
	echo "<input type='hidden' name='lisa' value='yks'>";
	echo "<input type='hidden' name='toim' value='$toim'>";
	echo "<td>",hyllyalue("yhyllyalue", $yhyllyalue);
	echo "-";
	echo "<input type='text' name='yhyllynro' size='5' maxlength='5' value='$yhyllynro'>";
	echo "-";
	echo "<input type='text' name='yhyllyvali' size='5' maxlength='5' value='$yhyllyvali'>";
	echo "-";
	echo "<input type='text' name='yhyllytaso' size='5' maxlength='5' value='$yhyllytaso'></td>";

	echo "<td><select name='kirjoitin'>";
	echo "<option value=''>".t("Ei kirjoitinta")."</option>";

	while ($kirow=mysql_fetch_array($kires)) {
		if ($kirow['tunnus']==$kirjoitin) $select='SELECTED';
		else $select = '';

		echo "<option value='$kirow[tunnus]' $select>$kirow[kirjoitin]</option>";
	}

	echo "</select></td>";

	echo "<td><select name='malli'>";
	//echo "<option value=''>".t("Ei mallia")."</option>";
	foreach ($pohjat as $pohja) {
		if ($pohja==$malli) $select='SELECTED';
		else $select = '';

		echo "<option value='$pohja' $select>$pohja</option>";
	}

	echo "</select></td>";

	echo "<td class='back'><input type='Submit' value='".t("Tulosta")."'></td>";
	echo "</form></tr></table>";

	require("inc/footer.inc");
