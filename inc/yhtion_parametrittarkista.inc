<?php

if(!function_exists("yhtion_parametrittarkista")) {
	function yhtion_parametrittarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $tiedostopaate;
		
		static $tmp_lasku_polku_http;
		
		if (mysql_field_name($result, $i) == "admin_email" or
			mysql_field_name($result, $i) == "alert_email" or
			mysql_field_name($result, $i) == "postittaja_email") {

			if (trim($t[$i]) == '') {
				$virhe[$i] = t("Tieto puuttuu!");
			}

		}
		
		if (mysql_field_name($result, $i) == "lasku_polku_http") {
			//Tämä turvaan
			$tmp_lasku_polku_http = $t[$i];
		}

		//	TODO ale ja ennakkomaksutuotteet voisi tarkastaa vain jos tarpeellista
		if (mysql_field_name($result, $i) == "maksuehto_tuotenumero" or
		    mysql_field_name($result, $i) == "rahti_tuotenumero" or
		    mysql_field_name($result, $i) == "alennus_tuotenumero" or
			mysql_field_name($result, $i) == "ennakkomaksu_tuotenumero") {

			$tuote = trim($t[$i]);

			// katotaan löytyykö tällänen saldoton tuote
			if ($tuote != "") {

				$query = "	select *
							from tuote
							where yhtio	= '$kukarow[yhtio]'
							and tuoteno = '$tuote'
							and ei_saldoa != ''";
				$xyresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($xyresult) == 0) {
					$virhe[$i] = t("Tuotetta ei löydy!")." / ".t("Tuote pitää olla saldoton!");
				}

			}
		}

		if (mysql_field_name($result, $i) == "maksuehto_tuotenumero") {

			$tuote = trim($t[$i]);

			$query = "	select *
						from maksuehto
						where yhtio	= '$kukarow[yhtio]'
						and summanjakoprososa2 != 0";
			$xyresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($xyresult) != 0 and $tuote=='') {
				$virhe[$i] = t("Yhtiölla on moniehto-maksuehtoja, maksuehto_tuotenumero on syötettävä!");
			}
		}

		if (mysql_field_name($result, $i) == "syncronoi") {

			if($t[$i]!="") {
				$t[$i]=str_replace("'","",$t[$i]);
				if(strpos($t[$i],",")) {
					$taulut=explode(",",$t[$i]);
				}
				else {
					$taulut=array($t[$i]);
				}

				$sallitut=array("asiakas","toimi","avainsana","tuote","tuotteen_toimittajat");
				$sallitut_avainsana=array("TRY","OSASTO");

				if(count($taulut)>0) {			
					foreach($taulut as $taulu) {				

						if(strpos($taulu,"avainsana")!==false) {
							$taululisat = explode("|",substr($taulu,10));
							$taulu = substr($taulu,0,9);
							if(count($taululisat)>0) {
								foreach($taululisat as $taululisa) {
									if(!in_array($taululisa, $sallitut_avainsana)) {
										if($virhe[$i]!="") {
											$virhe[$i] .= "<br>";
										}
										$virhe[$i] .= t("Lisätieto ei kelpaa")." '$lisa' ".t("sallitut lisat ovat")." ".implode("|", $sallitut_avainsana);
									}
								}						
							}
							else {
								if($virhe[$i]!="") {
									$virhe[$i] .= "<br>";
								}
								$virhe[$i] .= t("Lisätiedot vaaditaan")." '$lisa' ".t("sallitut lisat ovat")." ".implode("|", $sallitut_avainsana);
							}
						}

						$query="show tables like '$taulu'";
						$taulures=mysql_query($query) or pupe_error($query);
						if(mysql_num_rows($taulures)==0 or !in_array($taulu, $sallitut)) {
							if($virhe[$i]!="") {
								$virhe[$i] .= "<br>";
							}
							$virhe[$i] .= t("Taulu ei kelpaa")." '$taulu' ".t("sallitut kentät ovat")." ".implode(",", $sallitut);
						}
					}
				}					
			}
		}

		if (mysql_field_name($result, $i) == "lasku_logo") {
			$tiedostopaate = array("JPG","PNG");
		}

		if (mysql_field_name($result, $i) == "logo") {
			$tiedostopaate = array("JPG","PNG", "GIF");
		}
	}
}

?>
