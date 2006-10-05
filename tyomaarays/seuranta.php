<?php
	 
	require ("../inc/parametrit.inc");
	
	echo "<font class='head'>Seurantaa:</font><hr><br>";
	
	if($tee == ''){
		
		if ($month == "") {
			$vv = date("Y");
			$kk = date("m");
		}
		else {
			$vv = $year;
			$kk = $month;
		}
		
		$NumberOfDays = date(t,mktime(0,0,0,$kk+1,0,$vv,-1));
		
		$MonthNames = array(1=>'Tammikuu','Helmikuu','Maaliskuu','Huhtikuu','Toukokuu','Kesäkuu','Heinäkuu','Elokuu','Syyskuu','Lokakuu','Marraskuu','Joulukuu');
		
		echo "<table><form action='$PHP_SELF' method='post'><tr>
			<th>Valitse kuukausi:</td>
			<td><select name='month' onchange='submit()'>";
			
		while (list($key,$value) = each($MonthNames)) {
			if ($key != $kk) {
				print '<option value="'.$key.'">'.$value."</option>";
			} 
			else {
				print '<option value="'.$key.'" selected>'.$value."</option>\n";
			}
		}
		echo "</select></td><td><input type='text' size='5' name='year' value='$vv'></td></tr></form></table>";
		
		
		
		echo "<br><table>";
		
		$query = "	SELECT distinct(suorittaja)
					FROM tyomaarays
					WHERE month(maksuaika)='$kk' and year(maksuaika)='$vv' order by suorittaja";
		$result = mysql_query ($query) or pupe_error($query);
		
		echo "	<tr><td>$thfont Työn suorittaja: </td><td align='right'>$thfont Laskutettu:&nbsp;</td></tr>";
		echo "	<tr><td colspan='2'><hr size='1'></td></tr>";
		
		$kaikkiyht = 0;
		$kaikkikpl = 0;
		
		while($row = mysql_fetch_row($result)){
			$query = "	SELECT id
						FROM tyomaarays
						WHERE month(maksuaika)='$kk' and year(maksuaika)='$vv' and suorittaja='$row[0]'";
			$yresult = mysql_query ($query) or pupe_error($query);
			$yhteensa = 0;
			$kplyht = 0;	
			while($yrow = mysql_fetch_row($yresult)){
				$query = "	SELECT sum(rivihinta), sum(kpl)
							FROM huoltotiedot
							WHERE kpl > 0 and id='$yrow[0]'";
				$presult = mysql_query ($query) or pupe_error($query);
				
				$query = "	SELECT sum(rivihinta), sum(kpl)
							FROM varaosat
							WHERE kpl > 0 and osanro='HT295' and id='$yrow[0]'";
				$rresult = mysql_query ($query) or pupe_error($query);
				$prow = mysql_fetch_row($presult);
				$rrow = mysql_fetch_row($rresult);
		
				$tunnit = $prow[0] + $rrow[0];
				$kpl = $prow[1] + $rrow[1];
				$yhteensa += $tunnit;
				$kplyht = $kplyht + $kpl;			
			}
			echo "	<tr><td nowrap align='center'>
					<a href='$PHP_SELF&tee=nayta&month=$kk&year=$vv&mies=$row[0]'>$tdfont&nbsp;$row[0]&nbsp;</a>&nbsp;</td>
					<td align='right'>$thfont $yhteensa &nbsp;EUR</td></tr>";
			$kaikkiyht+=$yhteensa;
			$kaikkikpl+=$kplyht;
		}
		echo "	<tr><td colspan='4'><hr size='1'></td></tr>";
		
		echo "	<tr><td nowrap align='right'>$tdfont Kaikki yht: </td>
				<td align='right'>$thfont $kaikkiyht &nbsp; EUR </td></tr>";
		
		$query = "	SELECT hinta
					FROM tuote 
					WHERE tuoteno='HT295' and yhtio='$yhtio'";
		$result = mysql_db_query("prospekti", $query) or pupe_error($query);
		$drow  = mysql_fetch_row($result);
		$thinta = $drow[0];
		mysql_select_db('tyomaarays')
			or die ("Tietokanta katosi");
		echo "<tr><td align='right'>$tdfont Tyotunnin hinta: </td><td align='right'>$thfont $thinta &nbsp; EUR</td></tr>";
		echo "<tr><td align='right'>$tdfont Tehokas: </td><td align='right'>$thfont ".round($kaikkiyht/$thinta,2)." &nbsp; KPL</td></tr>";
		echo "</table>";
	}
	
	if ($tee == 'nayta') {
		echo"<br><table bgcolor='$tablecolor' border='0' cellspacing='0' cellpadding='3'>";
		echo "	<tr><td>$thfont Työn suorittaja: </td><td>$thfont Työmääräys: </td><td align='right'>$thfont Laskutettu:&nbsp;</td></tr>";
		echo "	<tr><td colspan='3'><hr size='1'></td></tr>";
		
		$query = "	SELECT id
					FROM tyomaarays
					WHERE month(maksuaika)='$month' and year(maksuaika)='$year' and suorittaja='$mies'";
		$yresult = mysql_query ($query)
			or die ("Kysely ei onnistu $query");
		
		$kaikkiyht = 0;
		$kaikkikpl = 0;
		while($yrow = mysql_fetch_row($yresult)){
			$query = "	SELECT sum(rivihinta), sum(kpl)
						FROM huoltotiedot
						WHERE kpl > 0 and id='$yrow[0]'";
			$presult = mysql_query ($query)
				or die ("Kysely ei onnistu $query");
			$query = "	SELECT sum(rivihinta), sum(kpl)
						FROM varaosat
						WHERE kpl > 0 and osanro='HT295' and id='$yrow[0]'";
			$rresult = mysql_query ($query)
				or die ("Kysely ei onnistu $query");
			$prow = mysql_fetch_row($presult);
			$rrow = mysql_fetch_row($rresult);
			
			$tunnit = $prow[0] + $rrow[0];
			$kpl = $prow[1] + $rrow[1];
			$kaikkiyht += $tunnit;
			$kaikkikpl += $kpl;
			
			echo "	<tr><td nowrap align='center'>$thfont $mies &nbsp;&nbsp;</td>
					<td align='center'>$thfont <a href='tulosta.php&xid=$yrow[0]'>$yrow[0]</a> &nbsp;&nbsp;</td>
					<td align='right'>$thfont $tunnit &nbsp;EUR</td></tr>";
					
		}
		$query = "	SELECT hinta
					FROM tuote 
					WHERE tuoteno='HT295' and yhtio='$yhtio'";
		$result = mysql_db_query("prospekti", $query)
			or die("$query feilas");                
		$drow  = mysql_fetch_row($result);
		$thinta = $drow[0];
		mysql_select_db('tyomaarays')
			or die ("Tietokanta katosi");
			
		echo "	<tr><td colspan='3'><hr></td></tr><tr><td nowrap align='right'>$thfont Yhteensä: &nbsp;&nbsp;</td><td></td>
				<td align='center'>$thfont $kaikkiyht &nbsp; EUR</td></tr>
				<tr><td align='right'>$thfont Tyotunnin hinta: </td><td></td><td align='right'>$thfont $thinta &nbsp; EUR</td></tr>
				<tr><td nowrap align='right'>$thfont Tunnit: &nbsp;&nbsp;</td><td></td>
				<td align='right'>$thfont ".round($kaikkiyht/$thinta,2)."&nbsp;KPL</td></tr>";
		echo "	</table>";
	}	
?>