<?php

if($ytunnus != '') {
	if (is_numeric($ytunnus)) {
		$query = "	SELECT nimi, osoite, ytunnus, tunnus
					FROM asiakas
					WHERE yhtio='$kukarow[yhtio]'
					and ytunnus like '$ytunnus%' 
					LIMIT 50";
		$result = mysql_query($query) or pupe_error($query);
	}
	else {
		$query = "	SELECT nimi, osoite, rekno, concat('£',max(tunnus)) ytunnus, concat('£',max(tunnus)) tunnus
					FROM lasku use index (asiakasnimi)
					LEFT JOIN tyomaarays ON tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila in ('A','L','N')
					and match (nimi) against ('$ytunnus' IN BOOLEAN MODE)
					and ytunnus != ''
					GROUP BY nimi, osoite, rekno
					LIMIT 50";
		$result = mysql_query($query) or pupe_error($query);
	
		if (mysql_num_rows($result) == 0) {
			$query = "	SELECT nimi, osoite, rekno, concat('£',max(tunnus)) ytunnus, concat('£',max(tunnus)) tunnus
						FROM lasku 
						LEFT JOIN tyomaarays ON tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus
						WHERE lasku.yhtio='$kukarow[yhtio]'						
						and lasku.tila in ('A','L','N')
						and lasku.ytunnus like '$ytunnus%'
						and ytunnus != ''
						GROUP BY nimi, osoite, rekno
						LIMIT 50";
			$result = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($result) == 0) {
				$query = "	SELECT nimi, osoite, ytunnus, tunnus
							FROM asiakas
							WHERE yhtio='$kukarow[yhtio]'
							and ytunnus like '$ytunnus%'
							LIMIT 50";
				$result = mysql_query($query) or pupe_error($query);
			}
		}
	}
	$jatko = 1;
}
if($rekno != '' and $jatko != 1){
	$query = "	SELECT nimi, osoite, rekno, concat('£',max(tunnus)) ytunnus, concat('£',max(tunnus)) tunnus
				FROM tyomaarays
				LEFT JOIN  lasku ON lasku.yhtio=tyomaarays.yhtio and lasku.tunnus=tyomaarays.otunnus
				WHERE lasku.yhtio='$kukarow[yhtio]'						
				and lasku.tila in ('A','L','N')
				and tyomaarays.rekno = '$rekno'
				and ytunnus != ''
				GROUP BY nimi, osoite, rekno
				LIMIT 50";
	$result = mysql_query($query) or pupe_error($query);
	$jatko = 1;
}
if($valmnro != '' and $jatko != 1){
	$query = "	SELECT distinct nimi, osoite, rekno, concat('£',max(tunnus)) ytunnus, concat('£',max(tunnus)) tunnus
				FROM tyomaarays
				LEFT JOIN  lasku ON lasku.yhtio=tyomaarays.yhtio and lasku.tunnus=tyomaarays.otunnus
				WHERE lasku.yhtio='$kukarow[yhtio]'						
				and lasku.tila in ('A','L','N')
				and tyomaarays.valmnro = '$valmnro'
				and ytunnus != ''
				GROUP BY nimi, osoite, rekno
				LIMIT 50";
	$result = mysql_query($query) or pupe_error($query);
	$jatko = 1;
}

echo $lause;
echo "<table>";
echo "<tr><th colspan='3'>Valitse kopioitava työmääräys tai valitse tyhjä</th></tr>";

if(mysql_num_rows($result) == 0){
	echo "<tr><td>Mitään ei löytynyt!</td></tr>";
}
else {
	
	echo "	<tr>
			<th>".t("Nimi")."</th>
			<th>".t("Osoite")."</th>
			<th>".t("Rekisterinumero")."</th>
			</tr>";

	while($row = mysql_fetch_array($result)){
		echo "<tr>
				<td>$row[nimi]</td>
				<td>$row[osoite]</td>
				<td>$row[rekno]</td>";

		echo "<form action='$PHP_SELF' method='post'>";
		echo "<input type='hidden' name='asiakasid' 	  value='$row[tunnus]'>";
		echo "<input type='hidden' name='tee' 			  value='$tee'>";
		echo "<input type='hidden' name='konserni'		  value='$konserni'>";
		echo "<input type='hidden' name='toim' 			  value='$toim'>";
		echo "<input type='hidden' name='tilausnumero' 	  value='$tilausnumero'>";		
		echo "<input type='hidden' name='from' 	  		  value='$from'>";		
		echo "<input type='hidden' name='tila' 			  value='$tila'>";
		echo "<input type='hidden' name='muutparametrit'  value='$muutparametrit'>";
		echo "<input type='hidden' name='ytunnus'  		  value='$row[ytunnus]'>";
		echo "<input type='hidden' name='asiakasyhtio'    value='$row[yhtio]'>";
		echo "<input type='hidden' name='alatila'		  value='$alatila'>";
		echo "<input type='hidden' name='ylatila'  		  value='$ylatila'>";						
		echo "<td class='back'><input type='submit' value='".t("Valitse")."'>";
		echo "</form>";
		echo "</td></tr>";
		
	}					
}

$ytunnus = "";

echo "<tr><td colspan='3'>Ei löytynyt oikeaa kaveria. Jatketaan tyhjällä formilla.</td>";

echo "<form action='$PHP_SELF' method='post'>";
echo "<input type='hidden' name='asiakasid' 	  value=''>";
echo "<input type='hidden' name='tee' 			  value='$tee'>";
echo "<input type='hidden' name='konserni'		  value='$konserni'>";
echo "<input type='hidden' name='toim' 			  value='$toim'>";
echo "<input type='hidden' name='tilausnumero' 	  value='$tilausnumero'>";		
echo "<input type='hidden' name='from' 	  		  value='$from'>";		
echo "<input type='hidden' name='tila' 			  value='$tila'>";
echo "<input type='hidden' name='muutparametrit'  value='$muutparametrit'>";
echo "<input type='hidden' name='ytunnus'  		  value=''>";
echo "<input type='hidden' name='asiakasyhtio'    value='$row[yhtio]'>";
echo "<input type='hidden' name='alatila'		  value='$alatila'>";
echo "<input type='hidden' name='ylatila'  		  value='$ylatila'>";						
echo "<td class='back'><input type='submit' value='".t("Tyhjä")."'>";
echo "</td></tr>";
echo "</form>";
echo "</table><br>";

?>