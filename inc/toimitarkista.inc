<?php

if(!function_exists("toimitarkista")) {
	function toimitarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $ulosarray;
		
		static $tmp_maakoodi, $ytunnus, $hetu, $tmp_ytunnus, $tmp_valkoodi, $toimtyyppi;
		
		if ((mysql_field_name($result, $i) == "nimi") or (mysql_field_name($result, $i) == "maa") or (mysql_field_name($result, $i) == "ytunnus") or (mysql_field_name($result, $i) == "oletus_hyvak1")) {
			if (trim($t[$i]) == '') {
				$virhe[$i] = t("Tieto puuttuu");
			}
			if (mysql_field_name($result, $i) == "maa") {
				$t[$i] = strtoupper($t[$i]);
				$tmp_maakoodi = $t[$i];
			}
		}

		if (mysql_field_name($result, $i) == "tilino") {
			if ($t[$i] != '') {
				$query = "	SELECT nimi
							FROM tili
							WHERE yhtio='$kukarow[yhtio]' and tilino = '$t[$i]'";
				$sresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($sresult) != 1) {
					//Olisiko siell‰ nimi
					if ($t[$i] != '') {
						$query = "	SELECT nimi, tilino
									FROM tili
									WHERE yhtio = '$kukarow[yhtio]' and nimi like '%$t[$i]%'
									ORDER BY tilino";
						$sresult = mysql_query($query) or pupe_error($query);
						if (mysql_num_rows($sresult) > 0) {
							$ulosarray[$i] = "<td><select name='t[$i]'><option value = '' $sel>".t("Ei valintaa")."</option>";
							while ($vrow=mysql_fetch_array($sresult)) {
								$sel="";
								if ($trow[$i] == $vrow[0]) {
									$sel = "selected";
								}
								$ulosarray[$i] .= "<option value = '$vrow[tilino]' $sel>$vrow[tilino] $vrow[nimi]</option>";
							}
							$ulosarray[$i] .= "</select></td>";
							$virhe[$i] = t("Valitse tili");
						}

					}
					if ($virhe[$i] == '') $virhe[$i] = t("Tili‰ ei lˆydy");
				}
			}
		}

		if (mysql_field_name($result, $i) == "tilinumero") {
			if ($tmp_maakoodi == strtoupper($yhtiorow['maa'])) {
				if ((int)trim($t[$i]) != 0) {
					$pankkitili = $t[$i];
					if ($tmp_maakoodi == 'FI') {
						require 'pankkitilinoikeellisuus.php';
						if ($pankkitili == '') {
							$virhe[$i] = t("Pankkitili '$pankkitili' on virheellinen");
						}
						else {
							$t[$i]=$pankkitili;
						}
					}
				}
				else {
					$virhe[$i] = t("Tieto puuttuu");
					$errori = 1;
				}
			}
		}

		if (mysql_field_name($result, $i) == "ultilno") {
			if ($tmp_maakoodi != strtoupper($yhtiorow['maa'])) {
				if ($t[$i] == '') {
					$virhe[$i] = "".t("Tieto puuttuu")."";
				}
			}
			//Vaaditaan isot kirjaimet
			$t[$i] = strtoupper($t[$i]);
		}

		if (mysql_field_name($result, $i) == "swift") {
			//Vaaditaan isot kirjaimet
			$t[$i] = strtoupper($t[$i]);
		}

		if ((mysql_field_name($result, $i) == "ytunnus") and (trim($t[$i])!='')) {
			$ytunnus = trim($t[$i]);

			if ($tmp_maakoodi == 'FI' and ($yhtiorow["ytunnus_tarkistukset"] == "" or $yhtiorow["ytunnus_tarkistukset"] == "T")) {

				$hetu = $ytunnus; // hetu talteen
				require ("tarkistaytunnus.inc");

				if ($ok==0) {

					// katotaan oliko kyseess‰ hetu
					require ("tarkistahetu.inc");

					// ei ollu oikee hetukaan
					if ($hetuok == 1) {
						$virhe[$i] = t("Virheellinen ytunnus/hetu!")." $t[$i] -> $ytunnus. ".t("Voit tarkistaa ytunnuksen")." <a target='uusiytj' href='http://www.ytj.fi/yrit_sel2.asp?kielikoodi=1'>".t("t‰‰lt‰")."</a>.";
						$errori = 1;
					}
				}
				else {
					$t[$i] = $ytunnus;
				}
			}
			else { // ulkomaalainen saa olla mit‰ se haluaa
				$t[$i] = $ytunnus;
			}
			$tmp_ytunnus=$t[$i];
		}

		if (mysql_field_name($result, $i) == "ovttunnus") {
			if ($tmp_maakoodi=='FI') {
				if (trim($t[$i])=='') {
					// t‰ytet‰‰n ovttunnus jos sit‰ ei ole
					$t[$i] = "0037". sprintf("%08.0f",$tmp_ytunnus);
				}

				$query   = "select ytunnus, tunnus from toimi where yhtio='$kukarow[yhtio]' and ovttunnus='$t[$i]'";
				$sresult = mysql_query($query) or pupe_error($query);

				if ($tunnus == "") { // ollaan lis‰‰m‰ss‰ uutta
					$raja=0;
				}
				else {
					$raja=1;
				}

				if (mysql_num_rows($sresult)>0) {
					$srowapu = mysql_fetch_array($sresult);
					// ei lˆydetty itte‰mme
					if ($srowapu['tunnus'] != $tunnus) {
						$virhe[$i] = t("Samalla ytunnuksella '$tmp_ytunnus' on useita toimittajia! Lis‰‰ tarkenne ovt-tunnkseen.");
					}
				}
			}
		}

		if (mysql_field_name($result, $i) == "email" and trim($t[$i]) != '') {
			// katotaan lˆytyykˆ email osoitteen domain...
			list($nimi, $domain) = split("@", trim($t[$i]));

	 		$domain = trim($domain);
			if ($domain != "") {
				exec("host -t MX $domain | grep \"not found\"", $output);
			}

			if (count($output) > 0 or $domain == "") {
				$virhe[$i] = t("S‰hkˆpostin domain ei lˆydy")."! ($domain)";
			}
		}

		if (mysql_field_name($result, $i) == "oletus_valkoodi") {
			$tmp_valkoodi = $t[$i];
		}

		if (mysql_field_name($result, $i) == "tyyppi") {
			$toimtyyppi = $t[$i]; // otetaan t‰‰ talteen

			// Jos toimittaja merkataan poistetuksi niin unohdetaan kaikki errortsekit...
			if ($toimtyyppi == "P" or $toimtyyppi == "PP") {						
				unset($virhe);
			}
		}

		if (mysql_field_name($result, $i) == "tyyppi_tieto") {

			if ($toimtyyppi == "S") {
				$query = "SELECT * from yhtio where konserni='$yhtiorow[konserni]' and yhtio='$t[$i]' and yhtio!='$kukarow[yhtio]'";
				$tyychkre = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($tyychkre) == 0) {
					$virhe[$i] = "V‰‰r‰ yhtiˆtunnus: $t[$i]!";
				}
			}

			if ($toimtyyppi == "O" and trim($t[$i]) == "") {
				$virhe[$i] = "Tieto puuttuu!";
			}

			if ($toimtyyppi == "L" and $tunnus > 0 and $errori != 1) {
				// injisoidaan tuotteen_toimittajat kaikille tuotteille
				$query = "	INSERT into tuotteen_toimittajat (yhtio, tuoteno, toim_tuoteno, toimittaja, liitostunnus, valuutta, alkuperamaa, tuotekerroin, laatija, luontiaika) 
							SELECT yhtio, tuoteno, tuoteno, '$tmp_ytunnus', '$tunnus', '$tmp_valkoodi', '$tmp_maakoodi', 1, '$kukarow[kuka]', now() 
							FROM tuote 
							WHERE tuote.yhtio='$kukarow[yhtio]' and tuote.ei_saldoa='' and tuote.status != 'P'  
							ON DUPLICATE KEY UPDATE 
							tuotteen_toimittajat.liitostunnus 	= $tunnus,
							tuotteen_toimittajat.toimittaja		= '$tmp_ytunnus', 
							tuotteen_toimittajat.valuutta		= '$tmp_valkoodi', 
							tuotteen_toimittajat.alkuperamaa	= '$tmp_maakoodi'"; 
				$vres = mysql_query($query) or pupe_error($query);
			}
			
			if ($toimtyyppi == "PP" and $tunnus > 0 and $errori != 1) {
				// poistetaan tuotteen_toimittajat kaikille tuotteille
				$query = "	SELECT lasku.yhtio, group_concat(DISTINCT tilausrivi.otunnus) tilaukset 
							from lasku 
							join tilausrivi on tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi='O' and tilausrivi.varattu != 0
							where lasku.yhtio		= '$kukarow[yhtio]' 
							and lasku.liitostunnus	= '$tunnus'
							and lasku.tila = 'O'
							group by 1";																				
				$tyychkre1 = mysql_query($query) or pupe_error($query);
								
				$query = "	SELECT lasku.yhtio, group_concat(DISTINCT lasku.laskunro) tilaukset 
							from lasku 
							where lasku.yhtio		= '$kukarow[yhtio]'
							and lasku.liitostunnus	= '$tunnus'
							and lasku.tila 	= 'K'
							and lasku.alatila != 'X'
							and lasku.vanhatunnus = 0
							group by 1";																				
				$tyychkre2 = mysql_query($query) or pupe_error($query);
								
				if (mysql_num_rows($tyychkre1) == 0 and mysql_num_rows($tyychkre2) == 0) {
			
					$query = "	DELETE FROM tuotteen_toimittajat
								WHERE tuotteen_toimittajat.yhtio		= '$kukarow[yhtio]'
								and tuotteen_toimittajat.liitostunnus 	= $tunnus"; 
					$vres = mysql_query($query) or pupe_error($query);
				}
				else {
					$tyychkro1 = mysql_fetch_array($tyychkre1);
					$tyychkro2 = mysql_fetch_array($tyychkre2);
					
					$virhe[$i] = "Tuotteen toimittajia ei voitu poistaa!<br>Tilaukset: ($tyychkro1[tilaukset]) ovat auki.<br>Keikat: ($tyychkro2[tilaukset]) ovat auki.";	
				}
			}			
		}
	}
}


?>
