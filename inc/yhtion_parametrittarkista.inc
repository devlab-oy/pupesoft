<?php

if(!function_exists("yhtion_parametrittarkista")) {
	function yhtion_parametrittarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $tiedostopaate;

		static $verkkolasku_lah_talteen, $verkkolasku_lah_talteen_i;

		if (mysql_field_name($result, $i) == "admin_email" or
			mysql_field_name($result, $i) == "alert_email" or
			mysql_field_name($result, $i) == "talhal_email" or
			mysql_field_name($result, $i) == "postittaja_email") {

			if (trim($t[$i]) == '') {
				$virhe[$i] = t("Tieto puuttuu!");
			}
		}

		if (mysql_field_name($result, $i) == "kuvapankki_polku") {
			if ($t[$i] != "" and !is_writable($t[$i]."/".$kukarow['yhtio'])) {
				$virhe[$i] = "<font class='error'>Kansiossa ei ole kirjoitusoikeutta!<br></font>";
			}
		}

		if (mysql_field_name($result, $i) == "maksuehto_tuotenumero" OR
		    mysql_field_name($result, $i) == "rahti_tuotenumero" OR
		    mysql_field_name($result, $i) == "jalkivaatimus_tuotenumero" OR
		    mysql_field_name($result, $i) == "laskutuslisa_tuotenumero" OR
		    mysql_field_name($result, $i) == "ennakkomaksu_tuotenumero" OR
		    mysql_field_name($result, $i) == "alennus_tuotenumero" OR
			mysql_field_name($result, $i) == "kuljetusvakuutus_tuotenumero" OR
			mysql_field_name($result, $i) == "ennakkomaksu_tuotenumero") {

			$tuote = mysql_real_escape_string(trim($t[$i]));

			if ($tuote != "") {
				// katotaan löytyykö tällänen saldoton tuote
				$query = "	SELECT tunnus
							FROM tuote
							WHERE yhtio	= '$kukarow[yhtio]'
							AND tuoteno = '$tuote'
							AND tuoteno != ''
							AND ei_saldoa != ''";
				$xyresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($xyresult) == 0) {
					$virhe[$i] = t("Tuotetta ei löydy!")." / ".t("Tuote pitää olla saldoton!");
				}
			}
		}

		if (mysql_field_name($result, $i) == "maksuehto_tuotenumero") {

			$tuote = trim($t[$i]);

			$query = "	SELECT *
						from maksuehto
						where yhtio	= '$kukarow[yhtio]'
						and summanjakoprososa2 != 0";
			$xyresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($xyresult) != 0 and $tuote=='') {
				$virhe[$i] = t("Yhtiölla on moniehto-maksuehtoja, maksuehto_tuotenumero on syötettävä!");
			}
		}

		if (mysql_field_name($result, $i) == "myynnin_alekentat_muokkaus") {
			if (count($t[$i]) > 0) {
				if (count($t[$i]) == 1 and $t[$i][0] == 'default') {
					unset($t[$i]);
					$t[$i] = '';
				}
				else {
					unset($t[$i][0]);
					$t[$i] = implode(",", $t[$i]);
				}
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

		if (mysql_field_name($result, $i) == "maventa_api_avain") {
			if ($verkkolasku_lah_talteen == "maventa" and trim($t[$i]) == "") {
				$virhe[$i] = t("Pakollinen: maventa_api_avain ja maventa_ohjelmisto_api_avain!!");
				$virhe[$verkkolasku_lah_talteen_i] = t("Pakollisia: maventa_api_avain ja maventa_ohjelmisto_api_avain!!");
			}
		}

		if (mysql_field_name($result, $i) == "maventa_ohjelmisto_api_avain") {

			if ($verkkolasku_lah_talteen == "maventa" and trim($t[$i]) == "") {
				$virhe[$i] = t("Pakollinen:  maventa_ohjelmisto_api_avain!!");
				$virhe[$verkkolasku_lah_talteen_i] = t("Pakollisia: maventa_api_avain ja maventa_ohjelmisto_api_avain!!");
			}
		}

		if (mysql_field_name($result, $i) == "verkkolasku_lah") {
			$verkkolasku_lah_talteen = trim($t[$i]);
			$verkkolasku_lah_talteen_i = $i;
		}
        if (mysql_field_name($result, $i) == "erapaivan_ylityksen_raja") {

            $t[$i] = int(trim($t[$i]));
            
			if ($t[$i] < 1) {
				$virhe[$i] = t("Arvon oltava kokonaisluku ja vähintään 1") . "!";
			}
        }
	}
}

?>
