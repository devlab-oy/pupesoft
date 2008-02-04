<?php
	require "inc/parametrit.inc";
	echo "<font class='head'>".t("Kopioi yhteensopivuus").":</font><hr>";
	
	if ($tee == "write") {
	

		// Tarkistetaan
		$errori = '';
		for ($i=1; $i < mysql_num_fields($result)-1; $i++) {
			require "inc/yhteensopivuus_{$vehicle_type}tarkista.inc";
		}

		if ($errori != '') {
			echo "<font class='error'>".t("Jossain oli jokin virhe! Ei voitu paivittaa!")."</font>";
		}

		// Luodaan tietue
		if ($errori == '') {
			
			for ($z = 0; $z < 2; $z++) {
				// Taulun ensimmäinen kenttä on aina yhtiö
				if ($z == 0) {
					$query = "INSERT INTO yhteensopivuus_$vehicle_type SET yhtio='$kukarow[yhtio]',";
				}
				else {
					$query = "UPDATE yhteensopivuus_$vehicle_type SET ";
				}

					// Luodaan puskuri, jotta saadaan taulukot kuntoon
					$tquery = "	SELECT *
								FROM yhteensopivuus_$vehicle_type
								WHERE tunnus = '$id'";
					$tresult = mysql_query($tquery) or pupe_error($tquery);
					$trow = mysql_fetch_array($tresult);

					for ($i=1; $i < mysql_num_fields($tresult) -1; $i++) {
							if (mysql_field_name($tresult,$i) == "muutospvm") {
								$query .= mysql_field_name($tresult,$i)."=now(),";														
							}
							else if (mysql_field_name($tresult,$i) == "muuttaja") {
								$query .= mysql_field_name($tresult,$i)."='{$kukarow['kuka']}',";
							}
							else if (mysql_field_name($tresult,$i) == "luontiaika") {
								$query .= mysql_field_name($tresult,$i)."=now(),";														
							}
							else if (mysql_field_name($tresult,$i) == "laatija") {
								$query .= mysql_field_name($tresult,$i)."='{$kukarow['kuka']}',";
							}
							else if (mysql_field_name($tresult,$i) == "tunnus" and $z == 0) {
								//jätetään pois
							}
							else {
								if ($z == 0) {
									$query .= mysql_field_name($tresult,$i)."='$t[$i]',";									
								}
								else {
									$query .= mysql_field_name($tresult,$i)."='$y[$i]',";
								}
							}
					}
					
					$query = substr($query, 0, -1);
					if ($z == 1) {
						$query .= " WHERE yhtio='$kukarow[yhtio]' and tunnus=$id";
					}
				
				$result = mysql_query($query) or pupe_error($query);
				
				if ($z == 0) {
					$uusid = mysql_insert_id();
				}
			}

			$query = "SELECT * FROM yhteensopivuus_tuote WHERE yhtio='{$kukarow['yhtio']}' AND atunnus=$id";
			$result = mysql_query($query) or pupe_error($query);
			
			while ($row = mysql_fetch_array($result)) {
				
				$yhteensopivuus_query = "INSERT INTO yhteensopivuus_tuote SET ";

				for($i = 0; $i < mysql_num_fields($result) - 1; $i++) {

					if (mysql_field_name($result,$i) == "muutospvm") {
						$yhteensopivuus_query .= mysql_field_name($result,$i)."='now()',";														
					}
					else if (mysql_field_name($result,$i) == "muuttaja") {
						$yhteensopivuus_query .= mysql_field_name($result,$i)."='{$kukarow['kuka']}',";
					}
					else if (mysql_field_name($result,$i) == "luontiaika") {
						$yhteensopivuus_query .= mysql_field_name($result,$i)."='now()',";														
					}
					else if (mysql_field_name($result,$i) == "laatija") {
						$yhteensopivuus_query .= mysql_field_name($result,$i)."='{$kukarow['kuka']}',";
					}
					else if (mysql_field_name($result,$i) == "atunnus") {
						$yhteensopivuus_query .= mysql_field_name($result,$i)."='$uusid',";														
					}
					else if (mysql_field_name($result,$i) == "tunnus") {
						//jätetään pois
					}
					else {
						$yhteensopivuus_query .= mysql_field_name($result,$i)."='".$row[$i]."',";							
					}

				}

				$yhteensopivuus_query = substr($yhteensopivuus_query, 0, -1);
				$yhteensopivuus_result = mysql_query($yhteensopivuus_query) or pupe_error($yhteensopivuus_query);
			}
			$tee = '';
		}
		else {
			$tee = 'edit';
		}
		
	}
	
	if ($tee == "edit") {
		
		echo "<table><tr><td class='back'>";
		echo "<form action = '$PHP_SELF' method = 'post'>";
		echo "<input type = 'hidden' name = 'tee' value ='write'>";
		echo "<input type = 'hidden' name = 'id' value ='$id'>";
		echo "<input type = 'hidden' name = 'vehicle_type' value='$vehicle_type'>";
		echo "<input type = 'hidden' name = 'toim' value='$toim'>";

		// Uusi auto
		// Kokeillaan geneeristä
		$query = "	SELECT *
					FROM yhteensopivuus_$vehicle_type
					WHERE tunnus='$id' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or die ("Kysely ei onnistu $query");
		$trow = mysql_fetch_array($result);
		
		for ($y = 0; $y < 2; $y++) {

			echo "<table>";
			$title = $y == 0 ? t("Uusi") : t("Vanha");
			echo "<tr><td class='back'><font class='head'>$title</font></td></tr>";

			for ($i=0; $i < mysql_num_fields($result) - 1; $i++) {
				$nimi = $y == 0 ? "t[$i]" : "y[$i]";

				require "inc/yhteensopivuus_{$vehicle_type}rivi.inc";
			
				if (mysql_field_name($result, $i) == 'laatija') {	//speciaali tapaukset
					$tyyppi = 2;
					$trow[$i] = $kukarow["kuka"];
				}
				if (mysql_field_name($result, $i) == 'luontiaika') {	//speciaali tapaukset
					$tyyppi = 2;
					$trow[$i] = date('Y-m-d H:i:s');
				}
				if (mysql_field_name($result, $i) == 'muuttaja') {
					$tyyppi = 2;
					$trow[$i] = $kukarow["kuka"];
				}
				if (mysql_field_name($result, $i) == 'muutospvm') {
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
						if ($tyyppi == 2 and (mysql_field_name($result, $i) == 'muutospvm' or mysql_field_name($result, $i) == 'muuttaja' or mysql_field_name($result, $i) == 'luontiaika' or mysql_field_name($result, $i) == 'laatija')) {
							echo "<td>";
						}
						else {
							echo "<td class='back'>";
						}
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
						if ($y == 1) {
							echo " value = '$y[$i]' size='$size' maxlength='" . mysql_field_len($result,$i) ."'>";
						}
						else {
							echo " value = '$t[$i]' size='$size' maxlength='" . mysql_field_len($result,$i) ."'>";
						}
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
			echo "</table></td><td class='back'>";
		}

		echo "</td></tr>";
		echo "<tr><td colspan='2' align='left' class='back'>";
		echo "<input type = 'submit' value = '".t("Perusta")."'>";
		echo "</form>";
		echo "</td></tr></table>";
	}
	
	if ($tee == '') {
		
		if ($toim == "MP") {
			$vehicle_type = "mp";
			$kentat = 'tunnus, tunnus, merkki, malli, cc, vm';
			$jarjestys = 'merkki, malli';
		}
		else if ($toim == "") {
			$vehicle_type = "auto";
			$kentat = 'tunnus, tunnus, merkki, malli, mallitarkenne, korityyppi, cc, moottorityyppi, polttoaine, sylinterimaara, sylinterinhalkaisija, teho_kw, teho_hv, alkukk, alkuvuosi, loppukk, loppuvuosi, lisatiedot';
			$jarjestys = 'merkki, malli';
		}
		
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

        $query = "SELECT $kentat FROM yhteensopivuus_$vehicle_type WHERE yhtio = '$kukarow[yhtio]' $lisa ";
        $query .= "$ryhma ORDER BY $jarjestys LIMIT 100";

		$result = mysql_query ($query)
				or die ("Kysely ei onnistu $query");

		echo "<table><form action = '$PHP_SELF' method = 'post'><tr>";
		
		echo "<input type='hidden' name='toim' value='$toim'>";

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
					echo "<td><a href='$PHP_SELF?id=$trow[tunnus]&tee=edit&vehicle_type=$vehicle_type&toim=$toim'>$trow[$i]</a></td>";
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