<?php
	require "inc/parametrit.inc";
	echo "<font class='head'>".t("Kopioi asiakkaan alennuksia").":</font><hr>";
	
	if ($tee == "write") {
	
		if ($uusiytunnus != '' and $uusiytunnus != $vanhaytunnus) {
			$query = "	SELECT tunnus
						FROM asiakas
						WHERE ytunnus='$uusiytunnus' and yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($result) > 0) {		
				$query = "	SELECT ytunnus, ryhma, alennus
							FROM asiakasalennus
							WHERE ytunnus='$vanhaytunnus' and yhtio='$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);
								
				while ($trow = mysql_fetch_array($result)) {
						$query = "	INSERT INTO asiakasalennus
									SET 
									ytunnus		= '$uusiytunnus',
									yhtio		= '$kukarow[yhtio]',
									ryhma		= '$trow[ryhma]',
									alennus 	= '$trow[alennus]',
									muutospvm	= now()";
						$insresult = mysql_query($query) or pupe_error($query);	
				}
				echo "".t("Asiakasalennukset kopioitu asiakkaalta")." $vanhaytunnus --> $uusiytunnus<br><br>";
				$tee = "";
			}
			else {
				echo "".t("Syöttämälläsi ytunnuksella ei löytynyt yhtään asiakasta!")."!<br>";
			}
		}
		else {
			echo "".t("Et syöttänyt mitään/tai syötit surkean ytunnuksen!")."<br>";
		}
	}
	
	if ($tee == "edit") {
		
		// Kokeillaan geneeristä
		$query = "	SELECT ytunnus, ryhma, alennus
					FROM asiakasalennus
					WHERE ytunnus='$ytunnus' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or die ("Kysely ei onnistu $query");
		
		if (mysql_num_rows($result) > 0) {
			echo "<form action = '$PHP_SELF' method = 'post'>";
			echo "<input type = 'hidden' name = 'tee' value ='write'>";
			echo "<input type = 'hidden' name = 'vanhaytunnus' value ='$ytunnus'>";			
			echo "<table>";
			
			echo "<tr><th>".t("Syötä ytunnus").":</th><td colspan='2'><input type = 'text' size='15' name = 'uusiytunnus'></td></tr>";
			echo "<tr><td colspan='3' class='back'><br></td></tr>";
			echo "<tr><th>".t("Ytunnus")."</th><th>".t("Alennusryhma")."</th><th>".t("Alennusprosentti")."</th></tr>";
			
			while ($trow = mysql_fetch_array($result)) {
				echo "<tr><td>$trow[ytunnus]</td><td>$trow[ryhma]</td><td>$trow[alennus]</td></tr>";		
			}
			
			echo "</table><br>";
			
			echo "<input type = 'submit' value = '".t("Kopioi")."'>";
			echo "</form>";
		
		}
		else {
			echo "<br><br>".t("Tällä asiakaalla ei ole yhtään asiakasalennusta")."!<br><br>";
			$tee = '';
		}
	}
	
	if($tee == ''){
		
		$kentat = 'tunnus, nimi, nimitark, postitp, ytunnus, ovttunnus';
		$jarjestys = 'selaus, nimi';
		
		$array = split(",", $kentat);
        $count = count($array);
		
        for ($i=0; $i<=$count; $i++) {
			if (strlen($haku[$i]) > 0) {
					$lisa .= " and " . $array[$i] . " like '%" . $haku[$i] . "%'";
					$ulisa .= "&haku[" . $i . "]=" . $haku[$i]; 
			}
        }
        if (strlen($ojarj) > 0) {  
			$jarjestys = $ojarj;
        }       
                
        $query = "SELECT $kentat FROM asiakas WHERE yhtio = '$kukarow[yhtio]' $lisa ";
        $query .= "$ryhma ORDER BY $jarjestys LIMIT 100";

		$result = mysql_query ($query)
				or die ("Kysely ei onnistu $query");

		echo "	<table><tr>
				<form action = '$PHP_SELF' method = 'post'>";
	
		for ($i = 1; $i < mysql_num_fields($result); $i++) {
			echo "<th valign='top' align='left'><a href = '$PHP_SELF?ojarj=".$array[$i].$ulisa ."'> 
					" . t(mysql_field_name($result,$i)) . "</a>";
			echo "<br><input type='text' name = 'haku[" . $i . "]' value = '$haku[$i]'>";
			echo "</th>";
		}
		echo "<td valign='bottom' class='back'><input type='Submit' value = '".t("Etsi")."'></td></form></tr>";
	
		while ($trow=mysql_fetch_array($result)) {
			echo "<tr>";
			for ($i=1; $i<mysql_num_fields($result); $i++) {
				if ($i == 1) {
					echo "<td><a href='$PHP_SELF?ytunnus=$trow[ytunnus]&tee=edit'>$trow[$i]</a></td>";
				}
				else {
					echo "<td>$trow[$i]</td>";
				}
			}
			echo "</tr>";
		}
		echo "</table>";
	}

?>