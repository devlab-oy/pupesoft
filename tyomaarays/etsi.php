<?php
	include('../inc/parametrit.inc');

	echo "<font class='head'>Etsi työmääräys:</font><hr><br>";

	if($tee == 'etsi') {
		echo "<table>";	
		
		if($paivam1 != ''){
			$muu1 = "lasku.luontiaika >= '$paivam1' and lasku.luontiaika <= ";
			$muu2 = $paivam2;
		}
		if($nimi != ''){
			$muu1 = "lasku.nimi LIKE ";  
			$muu2 = "%".$nimi."%";
		}
		if($rekno != ''){
			$muu1 = "tyomaarays.rekno LIKE ";  
			$muu2 = "%".$rekno."%";
		}
		if($eid != ''){
			$muu1 = "lasku.tunnus = ";  
			$muu2 = $eid;
		}
		if($asno != ''){
			$muu1 = "lasku.ytunnus = ";
			$muu2 = $asno;
		}
		if($valmno != ''){
			$muu1 = "tyomaarays.valmnro = ";
			$muu2 = $valmno;
		}
		
		$squery = "	SELECT *, lasku.tunnus laskutunnus
					FROM lasku, tyomaarays
					WHERE lasku.yhtio='$kukarow[yhtio]'
					and tyomaarays.yhtio=lasku.yhtio 
					and tyomaarays.otunnus=lasku.tunnus
					and lasku.tila in ('A','L','N')
					and lasku.tilaustyyppi='A'
					and $muu1 '$muu2'";
		$sresult = mysql_query($squery) or pupe_error($query);
		
		if(mysql_num_rows($sresult) > 0){
			echo "<tr>
					<th>Työmääräysno:</th>
					<th>Nimi:</th>
					<th>Rekno:</th>
					<th>Päivämäärä:</th>
					<th>Kommentti:</th>
					<th>Muokkaa:</th>
					<th>Tulosta:</th>
				 </tr>";
		
			while($row = mysql_fetch_array($sresult)) {
				
				echo "<tr>
						<td valign='top'>$row[laskutunnus]</td>
						<td valign='top'>$row[nimi]</td>
						<td valign='top'>$row[rekno]</td>
						<td valign='top'>".substr($row["luontiaika"],0,10)."</td>
						<td><pre>$row[komm2]</pre></td>";
				
				if ($row["alatila"] == '' or $row["alatila"] == 'A' or $row["alatila"] == 'B' or$row["alatila"] == 'C' or $row["alatila"] == 'J') {
					echo "<td valign='top'>
							<form method='post' action='../tilauskasittely/tilaus_myynti.php'>
							<input type='hidden' name='aktivoinnista' value='true'>
							<input type='hidden' name='tee' value='aktivoi'>
							<input type='hidden' name='toim' value='TYOMAARAYS'>
							<input type='hidden' name='tilausnumero' value='$row[laskutunnus]'>
							<input type='submit' value = '".t("Muokkaa")."'></form></td>";
				}
				else {
					echo "<td></td>";
				}
				
				echo "<td valign='top'><form action = '../tilauskasittely/tulostakopio.php' method='post'>
						<input type='hidden' name='tee' value = 'ETSILASKU'>
						<input type='hidden' name='otunnus' value='$row[laskutunnus]'>
						<input type='hidden' name='toim' value='TYOMAARAYS'>
						<input type='submit' value = '".t("Tulosta")."'></form></td>";
			
				echo " </tr>";
			}   
			echo "</table><br>";
		}
		else{
			echo "Yhtään työmääräystä ei löytynyt annetuilla ehdoilla!<br>";
		}
	}
	

	echo "<table><tr><form method='post' action='$PHP_SELF'><input type='hidden' name='tee' value='etsi'>";
	echo "<th>Hae työmääräykset väliltä: </th>";
		
	$d1 = date(j);
	$m1 = date(n);
	$m11 = date(n)-1;		
	$y1 = date(Y);

	$paiva1 = $y1."-".$m1."-".$d1;
	$paiva2 = $y1."-".$m11."-".$d1;

	echo "<td><input type='text' name='paivam1' size='10' value='$paiva2'> - <input type='text' name='paivam2' size='10' value='$paiva1'></td>";
	echo "<td class='back'><input type='submit' value='Hae'></td></form></tr>";
	echo "<tr><form method='post' action='$PHP_SELF'><input type='hidden' name='tee' value='etsi'>
		<th>Asiakkaan nimi:</th><td><input type='text' name='nimi' size='35'></td>
		<td class='back'><input type='submit' value='Hae'></td></form></tr>";
	echo "<tr><form method='post' action='$PHP_SELF'><input type='hidden' name='tee' value='etsi'>
		<th>Rekno:</th><td><input type='text' name='rekno' size='35'></td>
		<td class='back'><input type='submit' value='Hae'></td></form></tr>";
	echo "<tr><form method='post' action='$PHP_SELF'><input type='hidden' name='tee' value='etsi'>
		<th>Työmääräysno:</th><td><input type='text' name='eid' size='35'></td>
		<td class='back'><input type='submit' value='Hae'></td></form></tr>";
	echo "<tr><form method='post' action='$PHP_SELF'><input type='hidden' name='tee' value='etsi'>
		<th>Asiakasnumero:</th><td><input type='text' name='asno' size='35'></td>
		<td class='back'><input type='submit' value='Hae'></td></form></tr>";
	echo "<tr><form method='post' action='$PHP_SELF'><input type='hidden' name='tee' value='etsi'>
		<th>Valm.numero:</th><td><input type='text' name='valmno' size='35'></td>
		<td class='back'><input type='submit' value='Hae'></td></form></tr>";
	echo "</table>";
	
?>