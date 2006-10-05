<?php
	//$toim='YKS' tarkottaa yksinkertainen ja silloin ei v‰litet‰ onko tuotteella eankoodia vaan tulostetaan suoraan tuoteno viivakoodiin
	require("inc/parametrit.inc");
	echo "<font class='head'>".t("Tulosta tuotetarroja")."</font><hr>";
	

	
	if ($tulostakappale== '') {
		$tulostakappale = 1;
	}
	
	$lets='';
	$uusean='';
	
	if ($updateean!= '' and $uuseankoodi!= '' and $tee!= '' and $toim!= 'YKS') {
		$query = "UPDATE tuote SET eankoodi = '$uuseankoodi' WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno'";
		$resulteankoodi = mysql_query($query) or pupe_error($query);
	}
	
	if ($tee== 'Z') {
		require "inc/tuotehaku.inc";
	}
	
	$koodi = 'eankoodi';
	if ($toim== 'YKS') {
		$koodi = 'tuoteno';
	}
	
	$query = "select $koodi from tuote where yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno'";
	$eankoodires = mysql_query($query) or pupe_error($query);
	$eankoodirow=mysql_fetch_array($eankoodires);
	
	//echo "$eankoodirow[0]<br>$toim<br>$tee<br>";
	
	if ($eankoodirow[0]!= 0) {
		$lets='go';
	}
	elseif ($tee!= '' and $tee!='Y') {
		if ($toim== 'YKS' and $eankoodirow[0]!= '') {
			$lets='go';
		}
		else {
			$tee = 'Y';
			$varaosavirhe = t("Tuotteella ei ole eankoodia. Anna se nyt niin se p‰ivitet‰‰n tuotteen tietoihin");
			$uusean='jeppis';
		}
	}
	
	if ($malli== '' and $tee== 'Z') {
		$tee = 'Y';
		$varaosavirhe = t("Sinun on valittava tulostusmalli");
	}
	
	if ($ulos != "") {
			$formi  = 'hakua';
			echo "<form action = '$PHP_SELF' method='post' name='$formi' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='Z'>";
			echo "<input type='hidden' name='tulostakappale' value='$tulostakappale'>";
			echo "<input type='hidden' name='kirjoitin' value='$kirjoitin'>";
			echo "<input type='hidden' name='toim' value='$toim'>";
			echo "<input type='hidden' name='malli' value='$malli'>";
			echo "<table><tr>";
			echo "<td>".t("Valitse listasta").":</td>";
			echo "<td>$ulos</td>";
			echo "<td class='back'><input type='Submit' value='".t("Valitse")."'></td>";
			echo "</tr></table>";
			echo "</form>";
	}
	if ($tee=='Y') echo "<font class='error'>$varaosavirhe</font>";
	
	$tkpl = $tulostakappale;
	if ($tee== 'Z' and $ulos == '') {
		if ($lets== 'go') {
			$query = "select komento from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus = '$kirjoitin'";
			$komres = mysql_query($query) or pupe_error($query);
			$komrow = mysql_fetch_array($komres);
			$komento = $komrow['komento'];
			for($a = 0; $a < $tkpl; $a++) {
				if ($malli== 'Tec') {
					require("inc/tulosta_tuotetarrat_tec.inc");
				}
				elseif ($malli== 'Intermec') {
					require("inc/tulosta_tuotetarrat_intermec.inc");
				}
			}
			$tuoteno = '';
			$tee = '';
		}
		else {
			echo t("nyt on jotain m‰t‰‰!!!!");
		}
	}

	$formi  = 'formi';
	$kentta = 'tuoteno';	

	echo "<table><tr>";
	echo "<th>".t("Tuotenumero")."</th><th>".t("KPL")."</th><th>".t("Kirjoitin")."</th><th>".t("Malli")."</th>";
	if ($uusean!= '') {
		echo "<th>".t("Eankoodi")."</th>";
	}
	echo "<tr><form action='$PHP_SELF' method='post' name='$formi' autocomplete='off'>";
	echo "<input type='hidden' name='tee' value='Z'>";
	echo "<input type='hidden' name='toim' value='$toim'>";
	echo "<td><input type='text' name='tuoteno' size='20' maxlength='20' value='$tuoteno'></td>";
	echo "<td><input type='text' name='tulostakappale' size='3' value='$tulostakappale'></td>";
	$query = "select * from kirjoittimet where yhtio='$kukarow[yhtio]'";
	$kires = mysql_query($query) or pupe_error($query);
	echo "<td><select name='kirjoitin'>";
	echo "<option value='$kirow[tunnus]'>".t("Ei kirjoitinta")."</option>";
	while ($kirow=mysql_fetch_array($kires))
	{
		if ($kirow['tunnus']==$kirjoitin) $select='SELECTED';
		else $select = '';

		echo "<option value='$kirow[tunnus]' $select>$kirow[kirjoitin]</option>";
	}
	
	echo "</select></td>";
	
	//t‰h‰n arrayhin pit‰‰ lis‰t‰ uusia malleja jos tehd‰‰n uusia inccej‰ ja ylemp‰n‰ tehd‰ iffej‰.
	$pohjat=array();
	$pohjat[]='Tec';
	$pohjat[]='Intermec';

	echo "<td><select name='malli'>";
	echo "<option value='$kirow[tunnus]'>".t("Ei mallia")."</option>";
	foreach ($pohjat as $pohja) {
		if ($pohja==$malli) $select='SELECTED';
		else $select = '';

		echo "<option value='$pohja' $select>$pohja</option>";
	}

	echo "</select></td>";
	
	if ($uusean!= '') {
		echo "<input type='hidden' name='updateean' value='joo'>";
		echo "<td><input type='text' name='uuseankoodi' size='13' maxlength='13' value='$uuseankoodi'></td>";
	}
	echo "<td class='back'><input type='Submit' value='".t("Tulosta")."'></td>";
	echo "</form></tr></table>";
	
	
	require("inc/footer.inc");
?>