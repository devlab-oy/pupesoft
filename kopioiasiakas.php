<?php
	require "inc/parametrit.inc";
	require "inc/asiakas.inc";
	
	echo "<font class='head'>".t("Kopioi asiakas").":</font><hr>";
	
	if ($tee == "write") {
	
		// Luodaan puskuri, jotta saadaan taulukot kuntoon
		$query = "	SELECT *
					FROM asiakas
					WHERE tunnus = '$id'";
		$result = mysql_query($query) or pupe_error($query);
		$trow = mysql_fetch_array($result);

		// Tarkistetaan
		$errori = '';
		require "inc/asiakastarkista.inc";
		for ($i=1; $i < mysql_num_fields($result)-1; $i++) {
			asiakastarkista (&$t, $i, $result, $tunnus, &$virhe, $trow);
			if($virhe[$i] != "") {
				$errori = 1;
			}
		}		

		if ($errori != '') {
			echo "<font class='error'>".t("Jossain oli jokin virhe! Ei voitu paivittaa!")."</font>";
		}

		// Luodaan tietue
		if ($errori == '') {
			// Taulun ensimmäinen kenttä on aina yhtiö
			$query = "INSERT into asiakas values ('$kukarow[yhtio]'";
				for ($i=1; $i < mysql_num_fields($result); $i++) {
				$query .= ",'" . $t[$i] . "'";
			}
			$query .= ")";
			
			$result = mysql_query($query) or pupe_error($query);
			
			$tee = '';
		}
		else {
			$tee = 'edit';
		}
	}
	
	if ($tee == "edit") {
		
		echo "<form action = '$PHP_SELF' method = 'post' id='mainform'>";
		echo "<input type = 'hidden' name = 'tee' value ='write'>";
		echo "<input type = 'hidden' name = 'id' value ='$id'>";
		
		// Kokeillaan geneeristä
		$query = "	SELECT *
					FROM asiakas
					WHERE tunnus='$id' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or die ("Kysely ei onnistu $query");
		$trow = mysql_fetch_array($result);
		echo "<table>";
		
		for ($i=0; $i < mysql_num_fields($result) - 1; $i++) {
			$nimi = "t[$i]";

			require "inc/asiakasrivi.inc";
			
			if (mysql_field_name($result, $i) == "muutospvm") {
				$tyyppi = 0;
				$jatko = 0;
				$ulos = '';
			}
			
			if(mysql_field_name($result, $i) == 'laatija') {	//speciaali tapaukset
				$tyyppi = 2;
				$trow[$i] = $kukarow["kuka"];
			}
			if(mysql_field_name($result, $i) == 'luontiaika') {	//speciaali tapaukset
				$tyyppi = 2;
				$trow[$i] = date('Y-m-d H:i:s');
			}
			
			if 	(mysql_field_len($result,$i)>10) $size='35';
			elseif	(mysql_field_len($result,$i)<5)  $size='5';
			else	$size='10';
		 	
			if ($tyyppi > 0) {
				echo "<tr>";
			}
			
			if ($tyyppi > 0) {
		 		echo "<th align='left'>".t(mysql_field_name($result, $i))."</th>";
			}

			if ($jatko == 0) {
				echo $ulos;
			}
			else {
				$mita = 'text';
				if ($tyyppi != 1)
				{
					$mita='hidden';
					echo "<td class='back'>";
				}
				else
				{
					echo "<td>";
				}

				echo "<input type = '$mita' name = '$nimi'";

				if ($errori == '') {
					echo " value = '$trow[$i]' size='$size' maxlength='" . mysql_field_len($result,$i) ."'>";
				}
				else {
					echo " value = '$t[$i]' size='$size' maxlength='" . mysql_field_len($result,$i) ."'>";
				}

				if($tyyppi == 2) {
					echo "$trow[$i]";
				}

				echo "</td>";
			}
			
			if ($tyyppi > 0) {
				echo "<td class='back'><font class='error'>$virhe[$i]</font></td></tr>\n";
			}
		}
		echo "</table>";
		
		echo "<input type = 'submit' value = '".t("Perusta")."'>";
		echo "</form>";
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
					echo "<td><a href='$PHP_SELF?id=$trow[tunnus]&tee=edit'>$trow[$i]</a></td>";
				}
				else {
					echo "<td>$trow[$i]</td>";
				}
			}
			echo "</tr>";
		}
		echo "</table>";
	}
	
	require "inc/footer.inc";

?>