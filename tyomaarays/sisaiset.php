<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	include "inc/parametrit.inc";

	echo "$headerfont<b>".t("Sisäiset työt").":<hr>";

	$MonthNames = array(1=> t('Tammikuu'),t('Helmikuu'),t('Maaliskuu'),t('Huhtikuu'),t('Toukokuu'),t('Kesäkuu'),t('Heinäkuu'),t('Elokuu'),t('Syyskuu'),t('Lokakuu'),t('Marraskuu'),t('Joulukuu'));
	$CurDate = getdate();

	if ($month == NULL) {
		$YearToShow = $year = $CurDate['year'];
		$MonthToShow = $CurDate['mon'];
	}
	else {
		$YearToShow = $year;
		$MonthToShow = $month;
	}
	if (($YearToShow == $CurDate['year']) && ($MonthToShow == $CurDate['mon'])) {
		$DayToShow = $CurDate['mday'];
	}

	print "	<head>
			<SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
			<!--
			function kuu_Click() {
				month = document.vaihdaKuukausi.month.options[document.vaihdaKuukausi.month.selectedIndex].value
				month = month.toString()
				setTimeout(\"parent.kesk.location.href='sisaiset.php?session=$session&year=$year&month=\"+month+\"'\",0);
			}
			//-->
			</script></head>";


	echo "<br>$thfont <form action = '?session=$session' name='vaihdaKuukausi' method='post'>Valitse kuukausi:
		<select name='month' Onchange='kuu_Click()'>";
	$i=1;
	foreach($MonthNames as $val) {
		if($i == $MonthToShow) {
			$sel = "selected";
		}
		else {
			$sel = "";
		}
		print "<option value='$i' $sel>$val</option>";
		$i++;
	}
	echo "</select>&nbsp;<input type='text' size='5' name='year' value='$YearToShow'></form><br><br>";

	echo"<table bgcolor='$tablecolor' border='0' cellspacing='1' cellpadding='2'>";

	$query = "	SELECT id, maksuaika, maksutapa, maksutapa
				FROM asiakastiedot
				WHERE month(maksuaika)='$MonthToShow' and year(maksuaika)='$YearToShow' and (asnum='303' or asnum='660494')
				ORDER by id";
	$result = mysql_query ($query)
		or die ("Kysely ei onnistu $query");

	echo "	<tr><td>$thfont Työmääräys: </td>
				<td>$thfont Tunnit (EUR): </td><td>$thfont Maksuaika:</td></tr>";
	$yhteensa = 0;

	while ($row = mysql_fetch_array($result)){
		$query = "	SELECT sum(rivihinta)
					FROM huoltotiedot
					WHERE kpl > 0 and id='$row[0]'";
		$presult = mysql_query ($query)
			or die ("Kysely ei onnistu $query");

		$query = "	SELECT sum(if (osanro='HT295',rivihinta,0))
					FROM varaosat
					WHERE kpl > 0 and id='$row[0]'";
		$rresult = mysql_query ($query)
			or die ("Kysely ei onnistu $query");

		$prow = mysql_fetch_array($presult);
		$rrow = mysql_fetch_array($rresult);

		if ($prow[0] + $rrow[0] > 0) {
			$tunnit = $prow[0] + $rrow[0];
			echo "	<tr><td nowrap align='center'><a href='tulosta.php?session=$session&xid=$row[0]'>$tdfont $row[0]</a> &nbsp;&nbsp;</td>
					<td align='center'>$tdfont $tunnit &nbsp;&nbsp;</td>
					<td align='center'>$tdfont $row[1] &nbsp;&nbsp;</td></tr>";
			$yhteensa += $tunnit;
		}
	}
	echo "<tr><td colspan='5'><hr size='1'></td></tr>";
	echo "<tr><td>$thfont Yhteensä: </td>
				<td align='center'>$thfont $yhteensa</td><td>$thfont  EUR</td><td>$thfont </td></tr>";
	echo "</table>";

?>