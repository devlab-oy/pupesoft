<?php

	require ("../inc/parametrit.inc");
	
	echo "<font class='head'>M��r�aikaishuollot:</font><hr><br>";
	
	echo "Muokkaa huoltoa:";
	echo "<table>";
	echo "<form method='post'>";
	
	echo "<tr><th>Valitse malli:</th>";
	echo "<td><select name='malli'><option value='menu'>Valitse malli </option>";
	
	$query = "	SELECT distinct malli
				FROM huollot
				WHERE yhtio='$kukarow[yhtio]'
				ORDER by malli";
	$result = mysql_query ($query) or pupe_error($query);
	
	while($trow = mysql_fetch_array($result)) {
		
		$sel = "";
		
		if ($malli == $trow["malli"]) {
			$sel = "SELECTED";
		}
	
		echo "<option value='$trow[malli]' $sel>$trow[malli]</option>";
	}
	echo "</select></td></tr>";
	
	echo "<tr>
			<th><input type='hidden' name='tee' value = 'etsi'> tai sy�t� hakusana</th>
			<td><input type='text' size ='25' name='malli2'></td>
			<td class='back'><input type='submit' value='Hae'></form></td>
			</tr>";	
	echo "</table>";
	
	
				
	if($tee == ''){
		echo "	<br><br><br>Tai perusta uusi m��r�aikaishuolto:
				
				<form method='post'>
				<input type='hidden' name='tee' value = 'U'>
				<table>				
				<tr>				
				<tr><th>Malli:</th><td><input type='text' name='malli' size='35' value='Py�r�n malli'></td></tr>
				<tr><th>Huolto:</th><td><input type='text' name='huolto' size='35' value='Huolto xxx km'></td><td><input type='Submit' value = 'Lis��'></td></tr>
				</form></table>";
	}
	
	if($tee == 'poista') {
		$query = "	DELETE
					FROM huollot
					WHERE yhtio='$kukarow[yhtio]' and malli='$malli' and huolto='$huolto'";
		$result = mysql_query ($query) or pupe_error($query);
		
		echo "<br><br>M��r�aikaishuolto poistettu!<br>";
		
		$tee = "";
	}
	
	//tehd��n tarkistukset ja lis�t��n rivi
	if($tee == 'UV'){
		if ($tuoteno != '' and $maara != '') {
		
			$query = "	SELECT tuoteno
						FROM tuote
						WHERE yhtio='$kukarow[yhtio]' and tuoteno = '$tuoteno'";
			$result = mysql_query ($query) or pupe_error($query);		
			
			if (mysql_num_rows($result) == 1) {
					
				$query = "	INSERT into huollot
							SET 
							yhtio	= '$kukarow[yhtio]',
							malli	= '$malli',
							maara	= '$maara',
							tuoteno	= '$tuoteno',
							huolto	= '$huolto'";
				$result = mysql_query ($query) or pupe_error($query);
				$tunnus = mysql_insert_id();
				
				echo "<br><br><font class='message'>Rivi lis�tty!</font><br>";
				
				$tuoteno = "";
				$maara = "";
				$tee = "U";
			}
			else {
				echo "<br><br><font class='error'>VIRHE: Tuotetta ei l�ydy!</font><br>";	
				$tee = "U";
			}
		}
		else {
			echo "<br><br><font class='error'>VIRHE: Tuoteno ja m��r� on sy�tett�v�!</font><br>";
			$tee = "U";
		}
	}

	//listataan t�m�n mallin huollot
	if ($malli != '' or $malli2 != '') {
		
		$lis = "";
		
		if($malli == 'menu'){
			$malli = $malli2;
			$lis = "like '%$malli%'";
		}
		else{
			$lis = "='$malli'";
		}
		
		$query = "	SELECT distinct malli, huolto
					FROM huollot
					WHERE yhtio='$kukarow[yhtio]' and malli $lis
					ORDER by malli, huolto";
		$result = mysql_query ($query) or pupe_error($query);
		
		if(mysql_num_rows($result) == 0){
			//echo "<br>Hakuehdoila ei l�ytynyt yht��n huoltoa!<br>";
		}
		else{			
			echo "<br><table>";
			echo "<tr><th>Malli</th><th>Huolto</th><th>Muokkaa</th></tr>";
			
			while($rrow = mysql_fetch_array($result)) {
				echo "	<tr>
						<td>$rrow[malli]</td>
						<td>$rrow[huolto]</td>
						<td><a href='$PHP_SELF?&tee=U&malli=$rrow[malli]&huolto=$rrow[huolto]'>Muokkaa</a></td></tr>";
			}
			
			echo "</table>";
		}
	}
	
	//muokataan valittua mallia
	if($tee == 'U'){
		
		echo "<br><br>";
		echo "<table>";
		echo "<tr><th colspan='3'>K�sitelt�v� huolto: $malli $huolto</th></tr>";
		
		//muokataan olemassa olevaa rivi�
		if($rtunnus != ''){
			$query = "	DELETE 
						FROM huollot
						WHERE yhtio='$kukarow[yhtio]' and tunnus='$rtunnus'"; 
			$result = mysql_query ($query) or pupe_error($query);
		}
		
		echo "	<tr><form method='post'>     
				<input type='hidden' name='tee' value = 'UV'>
				<input type='hidden' name='malli' value = '$malli'>
				<input type='hidden' name='huolto' value = '$huolto'>
				<td></td>
				<td>Tuoteno:</td>
				<td>M��r�:</td></tr> 
				<tr valign='top'><td>Lis�� ty�/osa:</td>  
				<td><input type='text' size='20' name='tuoteno' value = '$tuoteno'></td>
				<td><input type='text' size='6' name='maara' value = '$maara'></td> 
				<td class='back'><input type='Submit' value = 'Lis��'>
				</form></td></tr>";			
		echo "	<tr><th colspan='3'>Huoltoon sis�ltyv�t tuotteet: </th></tr>";		
		
		$query = "	SELECT tuoteno, malli, huolto, tunnus, maara
					FROM huollot
					WHERE yhtio='$kukarow[yhtio]' and malli='$malli' and huolto='$huolto'";
		$result = mysql_query ($query) or pupe_error($query);		
		
		while ($row = mysql_fetch_array($result)){
			
			$query = "	SELECT tuoteno, nimitys
						FROM tuote
						WHERE yhtio='$kukarow[yhtio]' and tuoteno = '$row[tuoteno]'";
			$res2 = mysql_query ($query) or pupe_error($query);
			$row2 = mysql_fetch_array($res2);
			
			echo "	<tr>
					<td>".t_tuotteen_avainsanat($row2, 'nimitys')."</td>
					<td>$row[tuoteno]</td>
					<td>$row[maara]</td>
					<td>
					<form method='post'>     
					<input type='hidden' name='malli' value = '$malli'>
					<input type='hidden' name='huolto' value = '$huolto'>
					<input type='hidden' name='rtunnus' value = '$row[tunnus]'>
					<input type='hidden' name='tuoteno' value = '$row[tuoteno]'>
					<input type='hidden' name='maara' value = '$row[maara]'>
					<input type='hidden' name='tee' value = 'U'>   
					<input type='Submit' value = 'Muuta'>
					</form></td></tr>";
		} 		
		echo "	</table>";
		echo "	<br><br><table><form method='post'>
				<input type='hidden' name='tee' value = 'poista'>
				<input type='hidden' name='malli' value = '$malli'>
				<input type='hidden' name='huolto' value = '$huolto'>
				<tr>
				<th>Poista t�m� m��r�aikaishuolto:</th>
				<td><input type='Submit' value = 'Poista'></td>
				</tr>
				</form></table>";
	}
	
	require ("../inc/footer.inc");
	
?>