<?php	

if(!function_exists("tuotteen_toimittajattarkista")) {
	function tuotteen_toimittajattarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $alasveto, $liitostunnus_static, $chtuoteno, $lue_datasta, $toimi_liitostunnus, $toimi_ytunnus;
		
		if (mysql_field_name($result, $i) == "tuoteno") {
			$chtuoteno = $t[$i];
			
			if ($chtuoteno != '') {
				$query = "	SELECT tuoteno
							FROM tuote
							WHERE yhtio='$kukarow[yhtio]' and tuoteno = '$t[$i]'";
				$sresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($sresult) != 1) {

					if (substr($t[$i], -1) != '*') $tuoteno = $t[$i]."*";
					else $tuoteno = $t[$i];
			
					$kutsuja 		= "yllapito.php";
					$ulos			= "";

					require ("inc/tuotehaku.inc");

					if ($ulos != "") {	
						$alasveto[$i] = "<select name='t[$i]'>".$ulos."</select>";
					}

					$virhe[$i] = t("Tuotenumeroa ei löydy!");
				}
			}
			else {
				$virhe[$i] = t("Tuotenumero puuttuu!");
			}
		}

		if (mysql_field_name($result, $i) == "toimittaja") {
			if ($t[$i] != '') {
				
				$t[$i] = trim($t[$i]);
				
				if (isset($toimi_ytunnus) and $toimi_ytunnus != $t[$i]) {
					$toimi_liitostunnus = 0;
				}
				
				$query = "	SELECT * 
							from toimi 
							where yhtio = '$kukarow[yhtio]'
							and tyyppi != 'P' 
							and tunnus = ".(int) $toimi_liitostunnus."
							ORDER by nimi";
				$sresult = mysql_query($query) or pupe_error($query);
												
				if (mysql_num_rows($sresult) == 1) {
					$srow = mysql_fetch_assoc($sresult);
					
					$t[$i] = $srow["ytunnus"];
					$liitostunnus_static = $srow["tunnus"];
				
					if ((int) $tunnus == 0) {
						$query = "	SELECT * 
									from tuotteen_toimittajat 
									where yhtio			= '$kukarow[yhtio]' 
									and liitostunnus	= '$liitostunnus_static' 
									and tuoteno			= '$chtuoteno'";
						$sresult = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($sresult) > 0) {
							$virhe[$i] = t("Tämä toimittaja on jo tällä tuotteella")."!";
							$errori = 1;
						}
					}
				}
				else {
				
					$query = "	SELECT * 
								from toimi 
								where yhtio = '$kukarow[yhtio]'
								and tyyppi != 'P' 
								and (ytunnus like '%$t[$i]%' or nimi like '%$t[$i]%')
								order by nimi";
					$sresult = mysql_query($query) or pupe_error($query);
						
					if (mysql_num_rows($sresult) == 0) {
						$virhe[$i] = t("Toimittaja puuttuu!");
						$errori = 1;
					}
					elseif (mysql_num_rows($sresult) == 1) {
						$srow = mysql_fetch_assoc($sresult);
						
						$t[$i] = $srow["ytunnus"];
						$liitostunnus_static = $srow["tunnus"];
						
						if ((int) $tunnus == 0) {
							$query = "	SELECT * 
										from tuotteen_toimittajat 
										where yhtio			= '$kukarow[yhtio]' 
										and liitostunnus	= '$liitostunnus_static' 
										and tuoteno			= '$chtuoteno'";
							$sresult = mysql_query($query) or pupe_error($query);

							if (mysql_num_rows($sresult) > 0 and $lue_datasta != 'MUUTA') {
								$virhe[$i] = t("Tämä toimittaja on jo tällä tuotteella")."!";
								$errori = 1;
							}
						}											
					}
					else {
				
						$alasveto[$i] = "<input type='hidden' name='t[$i]' value='XXX'>
										<select name='toimi_liitostunnus'>";
										
						while ($srow = mysql_fetch_assoc($sresult)) {
							$alasveto[$i] .= "<option value='$srow[tunnus]'>$srow[ytunnus] $srow[nimi] $srow[nimitark]</option>";
						}
					
						$alasveto[$i] .= "</select>";
				
						$virhe[$i] = t("Valitse toimittaja")."!";
						$errori = 1;
					}					
				}				
			}
			else {
				$virhe[$i] = t("Toimittaja puuttuu!");
				$errori = 1;
			}
		}
		
		if (mysql_field_name($result, $i) == "liitostunnus") {
			$t[$i] = $liitostunnus_static;
		}
					
		if (mysql_field_name($result, $i) == "alkuperamaa") {
			if ($t[$i] == "") {
				$virhe[$i] =  t("Tieto puuttuu")."!";
				$errori = 1;
			}
		}
		
		if (mysql_field_name($result, $i) == "toim_tuoteno") {
			
			$query = "SELECT tyyppi from toimi where yhtio='$kukarow[yhtio]' and tunnus='$liitostunnus_static'";
			$sresult = mysql_query($query) or pupe_error($query);
			$tuotoimirow = mysql_fetch_assoc($sresult);
			
			if ($t[$i] == "" and $tuotoimirow["tyyppi"] != "") {
				$virhe[$i] = t("Online/sisäinen toimittaja vaatii aina toimittajan tuotenumeron")."!";
				$errori = 1;
			}
		}
	}
}

?>