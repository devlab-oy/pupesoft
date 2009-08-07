<?php
	
	/*
		Melko geneerisiä kalenterifunktioita
	*/
	
	//funktio palauttaa solun taustavärin, jos $type='PDF' niin palauttaa hexana
	function defineColor($row, $type="") {
		if($row["laji_tark"] == "tyomaarays") {
			if($type == "PDF") {
				$color = "#FAFA00";
			}
			else {
				$color = "rgba(250, 250, 0, 0.8);";
			}
		}
		elseif($row["laji_tark"] == "valmistus") {
			if($type == "PDF") {
				$color = "#1E5AFF";	
			}
			else {
				$color = "rgba(30, 90, 200, 0.8);";
			}	
		}
		elseif($row["laji"] == "kulunvalvonta") {
			if($row["laji_tark"] == "kuka") {
				if($type == "PDF") {
					$color = "#FF6464";
				}
				else {
					$color = "rgba(255, 100, 100, 0.9);";
				}	
			}
			else {
				if($type == "PDF") {
					$color = "#32C8C8";	
				}
				else {
					$color = "rgba(50, 200, 200, 0.7);";
				}
			}
		}
		elseif($row["laji_tark"] == "Freezing point") {
			if($type == "PDF") {
				$color = "#FF0000";	
			}
			else {
				$color = "rgba(255, 0, 0, 0.8);";
			}	
		}
		elseif ($row["laji_tark"] == "projektitapahtuma") {
			if($type == "PDF") {
				$color = "#CD7F32";	
			}
			else {
				$color = "rgba(205, 127, 50, 0.8);";
			}
		}
		
		
		else { //väri on vihreä
			if($type == "PDF") {
				$color = "#32C800";	
			}
			else {
				$color = "rgba(50, 200, 0, 0.8);";
			}
		}
		return $color;
	}
	
		
	if($nayta_pdf != 1) {
		?>
		<SCRIPT TYPE="text/javascript" LANGUAGE="JavaScript">

		function selectPopper(event, url, id) {

			select=document.getElementById(id);

			url=url+'&ajax_menu_yp='+id;

			proceed=false;
			if(select.options[select.selectedIndex].value == "uusi") {
				url=url+'&uusi=1';
				proceed=true;
			}

			if(proceed!==false) {
				popUp(event, 'ajax_menu_yp', '100', '-10', url);	
			}
		}

		</SCRIPT>

		<SCRIPT TYPE="text/javascript" LANGUAGE="JavaScript">

		function doSelectAction (sel, event, asiakasID, kaleDIV) {

			s=document.getElementById(sel);
			if(isNaN(s.options[s.selectedIndex].value)) {
				selectPopper(event, '../yllapito.php?toim=yhteyshenkilo&liitostunnus='+asiakasID+'&yhtyyppi=asiakas', sel);
			}
			else {
				ajaxPost('yhteyshenkiloLista_'+asiakasID, 'asiakasmemo.php?kaleDIV='+kaleDIV , kaleDIV);
			}
		}

		</SCRIPT>

		<?php
	}	

	if (!function_exists("tee_menu")) {
				
		// scripti javascript menujen tekemiseen
		function tee_menu ($valinnat, $nappi) {
		
			if(is_array($valinnat) and count($valinnat)>0) {
				$data = "";
				foreach($valinnat as $key => $valinta) {
					if(isset($valinta["VALI"])) {
						$data .= "&menu[$key][VALI]=".urlencode($valinta["VALI"]);
					}
					else {
						foreach(array("TEKSTI", "HREF", "EVENT_ACTION", "ONMOUSEOUT", "DIV", "OFFSETX", "OFFSETY", "TARGET", "TAPA", "EVENT") as $p) {
							if($valinta[$p] != "") {
								$data .= "&menu[$key][$p]=".urlencode($valinta[$p]); 
							}
						}
					}
				}

				$data = "<a href='#' onclick=\"popUp(event, 'ajax_menu', '50', '-10', '{$_SERVER["SCRIPT_NAME"]}?kaletee=GENEROIMENU$data', false, true); return false;\">$nappi</a>";
				return $data;
			}
			return false;
		}
	}

	if(!function_exists("generoiMenu")) {
		function generoiMenu($menu) {
			if(is_array($menu) and count($menu)>0) {

				$ulos =  "<table>";
				foreach($menu as $item) {
					if(isset($item["VALI"])) {
						$ulos .=  "<tr><td class=\"back\" height='20' style='vertical-align: bottom;'><font class='info'>{$item["VALI"]}</font></td></tr>";
					}
					else {
						if($item["TARGET"] == "page") {
							$ulos .=  "<tr><td class=\"back\"><a class=\"menu\" href=\"{$item["HREF"]}\">$item[TEKSTI]</a></td></tr>";
						}
						elseif($item["TARGET"] == "_blank") {
							$ulos .=  "<tr><td class=\"back\"><a class=\"menu\" href=\"{$item["HREF"]}\" target='_blank'>$item[TEKSTI]</a></td></tr>";
						}
						else {
							if($item["DIV"] != "") {
								$div = $item["DIV"];
							}
							else {
								$div = "ajax_menu";
							}

							if($item["EVENT"] != "") {
								$event = $item["EVENT"];
							}
							else {
								$event = "onclick";
							}
							
							if(isset($item["OFFSETX"])) {
								$x = $item["OFFSETX"];
							}
							else {
								$x = 50;					
							}

							if(isset($item["OFFSETY"])) {
								$y = $item["OFFSETY"];
							}
							else {
								$y = -10;
							}
							if($item["TAPA"] == "request") {
								$ulos .=  "<tr><td class=\"back\"><a class=\"menu\" $event=\"divi=document.getElementById('ajax_menu'); divi.innerHTML=''; divi.style.display='none'; sndReq('$div', '$item[HREF]', false, false);\">$item[TEKSTI]</a></td></tr>";	
							}
							elseif($item["TAPA"] == "CUSTOM") {
								$ulos .=  "<tr><td class=\"back\"><a class=\"menu\" $event=\"".stripslashes($item["EVENT_ACTION"])."\">$item[TEKSTI]</a></td></tr>";
							}
							else {
								$ulos .=  "<tr><td class=\"back\"><a class=\"menu\" href=\"#\" $event=\"divi=document.getElementById('ajax_menu'); divi.innerHTML=''; divi.style.display='none'; popUp(event, '$div', '$x', '$y', '{$item["HREF"]}', false, false); return false;\">$item[TEKSTI]</a></td></tr>";
							}
						}
					}
				}
				$ulos .=  "</table>";
				
				return $ulos;
			}
		}
	}

	if(!function_exists("color_code")) {
		// scripti javascript menujen tekemiseen
		function color_code ($arvo, $maxvalue, $values="", $scheme="green") {
			$porras = 200/$maxvalue;
			if($arvo < 0) $arvo = 0;
			if($arvo > $maxvalue) $arvo = $maxvalue;

			$k = 200/255;

		
			$r = (200-($porras*$arvo));
			if($r>100) {
				$r+=55;
			}
			$r = floor($r);
		
			$g = floor(($porras*$arvo));
			if($g>100) {
				$g+=55;
			}
			$g = floor($g);

			// filter: alpha(opacity=60);-moz-opacity:.60;opacity:.60;
			if($values == 0) {
				return "rgba($r,$g, 0, 0.4);";
			}
			else {
				return array("red" => (int) ($r/255), "green" => (int) ($g/255), "blue" => (int) 0, "alpha" => 0.4);
			}			
		}
	}

	if(!function_exists("purge")) {
		function purge ($row) {
			$l = array();		
			foreach($row as $k => $r) {
				if(!is_numeric($k)) {
					$l[$k] = $r;		
				}
			}
			return $l;
		}
	}	

	if(!function_exists("url_params")) {
		function url_params ($kalenteri, $method = "") {
			global $_REQUEST;
			
			$ret = "";
			//	Tehdään urliin parametrit
			if(is_array($kalenteri["url_params"])) {
				$kalenteri["url_params"]["kaleDIV"] = $kalenteri["div"];
				foreach($kalenteri["url_params"] as $var => $varval) {
					if($method != "POST") {
						$ret .= "&$var=$varval";
					}
					else {
						$ret .= "<input type='hidden' name='$var' value='$varval'>";
					}
				}				
			}
			
			if($method != "POST") {
				$ret .= "&ohje=$_REQUEST[ohje]&sulje=$_REQUEST[sulje]";
			}
			else {
				$ret .= "<input type='hidden' name='ohje' value='$_REQUEST[ohje]'><input type='hidden' name='sulje' value='$_REQUEST[sulje]'>";
			}
			
			
			return $ret;
		}
	}

	if(!function_exists("kaleotsikko")) {
		function kaleotsikko ($tyyppi) {
			
			static $kaleotsikko;
			
			if(!is_array($kaleotsikko)) {
				$kaleotsikko = array(	"kalenteri"		=> t("Kalenteri"),
										"Memo" 			=> t("Asiakasmuistio"),
										"Muistutus" 	=> t("Muistutus"),
										"Merkinta" 		=> t("Merkintä"),
										"tilaus"		=> t("Tilaus/Projekti"),
										"tarjous" 		=> t("Tarjous"),
										"toimitus" 		=> t("Toimitus"),
										"alku"			=> t("Avattu"),
										"laskutus"		=> t("Laskutus"),
										"kontakti_1"	=> t("1. kontaktointi"),
										"kontakti_2"	=> t("2. kontaktointi"),
										"kontakti_3"	=> t("Viimeinen voitelu"),
										"tarjouskontaktointi"	=> t("Tarjouskontaktointi"),
										"projektitapahtuma"		=> t("Projektitapahtuma"),
										"tyomaarays" 	=> t("Työmääräys"),
										"valmistus" 	=> t("Valmistus"),
										"projektitunnit"		=> t("Projektitunnit"),
									);
				
			}

			$otsikko = $kaleotsikko[$tyyppi];
			if($otsikko == "") $otsikko = $tyyppi;
			
			return $otsikko;
		}
	}

	if(!function_exists("nakymaotsikko")) {
		function nakymaotsikko ($nakyma) {
			
			static $nakymaotsikko;
			
			if(!is_array($nakymaotsikko)) {
				$nakymaotsikko = array(	"KALENTERINAKYMA"	=> t("Kalenterinakyma"),
										"KUUKAUSINAKYMA"	=> t("Kuukausinäkymä"),
										"VIIKKONAKYMA" 		=> t("Viikkonäkymä"),
										"PAIVANAKYMA" 		=> t("Päivänäkymä"),
										"TAPAHTUMALISTAUS" 	=> t("Tapahtumalistaus"),
										"RIVINAKYMA_PAIVA"	=> t("Rivinäkymä päivittäin"),
										"RIVINAKYMA_VIIKKO"	=> t("Rivinäkymä viikoittain"),
									);
				
			}

			$otsikko = $nakymaotsikko[$nakyma];
			if($otsikko == "") $otsikko = $nakyma;
			
			return $otsikko;
		}
	}

	if(!function_exists("kaletapaKaannos")) {
		function kaletapaKaannos ($tapa, $from, $to) {
			global $kukarow;
			
			//	Palautetaan automaattiseti takaisin
			if($from == $to) return $tapa;
			
			//	Haetaan selite
			$query = "	SELECT selite
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]' and avainsana.laji = 'KALETAPA' and selitetark = '$tapa' and kieli = '$from'
						LIMIT 1";
			$fromres = mysql_query($query) or pupe_error($query);
			if(mysql_num_rows($fromres) > 0) {
				$fromrow = mysql_fetch_array($fromres);

				$query = "	SELECT selitetark
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]' and avainsana.laji = 'KALETAPA' and selite = '$fromrow[selite]' and kieli = '$to'
							LIMIT 1";
				$tores = mysql_query($query) or pupe_error($query);
				if(mysql_num_rows($tores) > 0) {
					$torow = mysql_fetch_array($tores);
					
					//	Palautetaan käännös
					return $torow["selitetark"];
				}
			}
			
			//	Palautetaan oletus jos muuta ei saatu..
			return $tapa;
		}
	}	
	
	if(!function_exists("lyhytviesti")) {
		function lyhytviesti (&$viesti, $viestipituus, $otsikko="", $popup_otsikko="") {
			
			if($popup_otsikko!="") {
				$popup_otsikko.="<hr>";
			}
			
			//$viesti = nl2br($viesti); 
			
			$id=uniqid();
			$div = "
			<div id='$id' class='popup'>
				<table width='500'>
					<tr>
						<td>
						$popup_otsikko
						".nl2br($viesti)."
						</td>
					</tr>
				</table>
			</div>";	
			
			$poppari = "onmouseover=\"popUp(event, '$id');\" onmouseout=\"popUp(event, '$id');\"";
			
			if($viestipituus == 0) {
				$viesti = "$div<div $poppari>$otsikko</div>";
			}
			elseif(strlen(strip_tags($viesti))>$viestipituus ) {
				$viesti = "$div<div $poppari>$otsikko".str_replace(strip_tags($viesti), substr(strip_tags($viesti), 0, $viestipituus), $viesti)."..</div>";
			}
			else {
				$viesti = "$div<div $poppari>$otsikko$viesti</div>";
			}
		}
	}
	
	if(!function_exists("hae_lisatiedot")) {
		function hae_lisatiedot(&$krow, $viestipituus="") {
			global $kukarow, $kalenteri;

			$laji		= $krow["laji"];
			$lajitark	= $krow["laji_tark"];
			$tunnus		= $krow["tunnus"];
			if($laji == "kalenteri" or substr($laji, 0, 8) == "kontakti") {
				$query = "	SELECT  lasku.*, kalenteri.*, concat_ws(', ', tapa, if(kentta09='', null, kentta09)) tapa, if(kentta01='', '***', kentta01) viesti, kalenteri.tyyppi,
									UNIX_TIMESTAMP(pvmalku) pvmalku, 
									UNIX_TIMESTAMP(pvmloppu) pvmloppu,
									avainsana.selitetark tyyppi_nimi,
									DATEDIFF(pvmalku, now()) paivia_jaljella, 
									asiakas.nimi asiakas,
									yhteyshenkilo.nimi yhteyshenkilo,
									kuka.nimi kuka_nimi,
									kalenteri.luontiaika,
									kalenteri.laatija,
									kalenteri.tunnus
							FROM kalenteri
							LEFT JOIN avainsana ON avainsana.yhtio=kalenteri.yhtio and avainsana.laji='KALETAPA' and avainsana.selite=kalenteri.tapa
							LEFT JOIN asiakas ON asiakas.yhtio=kalenteri.yhtio and asiakas.tunnus=kalenteri.liitostunnus
							LEFT JOIN lasku ON lasku.yhtio=kalenteri.yhtio and lasku.tunnus=kalenteri.otunnus
							LEFT JOIN yhteyshenkilo ON yhteyshenkilo.yhtio=kalenteri.yhtio and yhteyshenkilo.liitostunnus=kalenteri.liitostunnus and yhteyshenkilo.tunnus = kalenteri.henkilo and yhteyshenkilo.tyyppi = 'A'
							LEFT JOIN kuka ON kuka.yhtio = kalenteri.yhtio and kalenteri.kuka=kuka.kuka
							WHERE kalenteri.yhtio='$kukarow[yhtio]' and kalenteri.tunnus='$tunnus'";
				$res = mysql_query($query) or pupe_error($query);
				$row = mysql_fetch_array($res);
				//var_dump($kalenteri);
				$otsikko = kaletapaKaannos($row["tapa"], "fi", $kukarow["kieli"]);
				$row["otsikko"] = $otsikko;
				$row["viesti"] = stripslashes($row["viesti"]);

				$popup_otsikko = "<table>
									<tr><th class=''>".t("Tyyppi").":</th><td class=''>".kaleotsikko($row["tyyppi"])."</td></tr>";
				if($row["asiakas"] != "") {
					$popup_otsikko .= "<tr><th class=''>".t("Asiakas").":</th><td class=''>$row[asiakas]</td></tr>";
				}
				if($row["yhteyshenkilo"] != "") {
					$popup_otsikko .= "<tr><th class=''>".t("Yhteyshenkilö").":</th><td class=''>$row[yhteyshenkilo]</td></tr>";
				}
				
				if($row["tila"] == "T" and $row["tunnusnippu"] > 0) {
					$popup_otsikko .= "<tr><th class=''>".t("Tarjous").":</th><td class=''>{$row["tunnusnippu"]}</td></tr>";
				}
				elseif(in_array($row["tila"], array("R")) and $row["tunnusnippu"] > 0) {
					$popup_otsikko .= "<tr><th class=''>".t("Projekti").":</th><td class=''>{$row["tunnusnippu"]}</td></tr>";
				}
				elseif(in_array($row["tila"], array("L", "U")) and $row["tunnusnippu"] > 0) {
					$popup_otsikko .= "<tr><th class=''>".t("Tilaus").":</th><td class=''>{$row["tunnusnippu"]}</td></tr>";
				}
				
				if(in_array($lajitark, array("kalenteri", "projektitapahtuma"))) {
					$popup_otsikko .= "<tr><th class=''>".t("Tapa").":</th><td class=''>".kaletapaKaannos($row["tapa"], "fi", $kukarow["kieli"])."</td></tr>";
					$popup_otsikko .= "<tr><th class=''>".t("Alkaa").":</th><td class=''>".date("d.m.Y H:i", $row["pvmalku"])."</td></tr>";
					$popup_otsikko .= "<tr><th class=''>".t("Päättyy").":</th><td class=''>".date("d.m.Y H:i", $row["pvmloppu"])."</td></tr>";
				}
				elseif($lajitark == "Muistutus") {
					$popup_otsikko .= "<tr><th class=''>".t("Päivälle").":</th><td class=''>".date("d.m.Y", $row["pvmalku"])."</td></tr>";
				}
				elseif($laji == "kontakti_oletettu") {
					
					$days = 0;
					if($lajitark == "kontakti_1") {
						$days = 14;
					}
					elseif($lajitark == "kontakti_2") {
						$days = 45;
					}
					else {
						$days = 76;
					}
					
					//	Täällä vähän poikkeava käsittely koska meillä ei ole vielä edes oikeaa kalenteritapahtumaa
					$abuq = "	SELECT *, 
								DATEDIFF(date_add(luontiaika, interval $days day), now()) paivia_jaljella
								FROM lasku
								WHERE yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
					$res = mysql_query($abuq) or pupe_error($abuq);
					$row = mysql_fetch_array($res);

					$popup_otsikko .= "<tr><th class=''>".t("Päivälle").":</th><td class=''>".date("d.m.Y", $row["pvmalku"])."</td></tr>";
					
					if($lajitark == "kontakti_1") {
						$row["viesti"] = t("Tarjouksen %s 1. kontaktointi", $kieli, $row["tunnusnippu"]);
					}
					if($lajitark == "kontakti_2") {
						$row["viesti"] = t("Tarjouksen %s 2. kontaktointi", $kieli, $row["tunnusnippu"]);
					}
					if($lajitark == "kontakti_3") {
						$row["viesti"] = t("Tarjouksen %s viimeinen voitelu", $kieli, $row["tunnusnippu"]);
					}					
				}
				
				$popup_otsikko .= "</table>";
				
				$popup_otsikko = "	<div style='text-align: right;'>
										<font class='info'>$row[laatija]@$row[luontiaika]</font>
									</div>
									$popup_otsikko";
				
				$otsikko = "<div style='font-weight: bold;'>$otsikko</div>";
				
				if(in_array($lajitark, array("Muistutus", "kontakti_1", "kontakti_2", "kontakti_3")) and $laji != "kontakti") {
					$row["viesti"] = "<div ><font class='info' style='background-color: ".color_code((1*$row["paivia_jaljella"]), 7)."'>{$row["viesti"]}</font></div";
				}
				else {
					$row["viesti"] = "<font class='info'>{$row["viesti"]}</font>";
				}
				
				if(is_int($viestipituus) and strlen($viestipituus)>0) {
					lyhytviesti($row["viesti"], $viestipituus, $otsikko, $popup_otsikko);
				}
				
				if($kalenteri["vain_luku"] != true) {
					if($laji == "kontakti_oletettu") {
						$row["viesti"] = "<a onclick=\"popUp(event, 'ajax_menu', '350', '-10', '$kalenteri[URL]?kaletee=KALENTERITAPAHTUMA&tyyppi=$lajitark&otunnus={$row["tunnusnippu"]}&liitostunnus={$row["liitostunnus"]}".url_params($kalenteri)."'); return false;\" class='kale'>$row[viesti]</a>";
					}
					elseif($row["laatija"] == $kukarow["kuka"]) {
						$row["viesti"] = "<a onclick=\"popUp(event, 'ajax_menu', '350', '-10', '$kalenteri[URL]?kaletee=KALENTERITAPAHTUMA&tyyppi=$lajitark&ktunnus=$tunnus".url_params($kalenteri)."'); return false;\" class='kale'>$row[viesti]</a>";
					}
					elseif(in_array($lajitark, array("Muistutus"))) {
						$row["viesti"] = "<a onclick=\"popUp(event, 'toimitus', '300', '-20', '$kalenteri[URL]?ohje=off&kaletee=ANNAKUITTAUS&ktunnus={$row["tunnus"]}".url_params($kalenteri)."'); return false;\">{$row["viesti"]}</a>";
					}
					else {
						$row["viesti"] = "<a onclick=\"alert('".t("Voit muokata vain omaa kalenteriasi")."'); return false;\" class='kale'>$row[viesti]</a>";
					}
				}
			}
			elseif($laji == "lasku") {
				
				$query = "	SELECT *
							FROM lasku
							WHERE lasku.yhtio='$kukarow[yhtio]' and lasku.tunnus='$tunnus'";
				$res = mysql_query($query) or pupe_error($query);
				$row = mysql_fetch_array($res);
				
				$viesti = "";
				$menu = array();
				if($row["tila"] == "T") {
					
					$abuq = "	SELECT tunnus
								FROM lasku
								WHERE lasku.yhtio='$kukarow[yhtio]' and lasku.tunnusnippu='{$row["tunnusnippu"]}' and tunnus < $tunnus";
					$res = mysql_query($abuq) or pupe_error($abuq);

					$atoim = "TARJOUS";
					if($lajitark == "alku") {
						if(mysql_num_rows($res) == 0) {
							$viesti = t("Tarjous %s avattu", $kieli, $row["tunnusnippu"]);
						}
						else {
							$viesti = t("Versio tarjoukseen %s", $kieli, $row["tunnusnippu"]);
						}
					}
					elseif($lajitark == "loppu") {
						$viesti = t("Tarjous %s happanee..", $kieli, $row["tunnusnippu"]);
					}
					
				}
				elseif(in_array($row["tila"], array("L", "N", "R", "U"))) {
					$atoim = "MYYNTI";
					if($lajitark == "tilaus") {
						$viesti = t("Tilaus %s kirjattu", $kieli, $row["tunnusnippu"]);
					}
					elseif($lajitark == "laskutus") {
						$viesti = t("Tilausta %s laskutettu, laskunro %s", $kieli, $row["tunnusnippu"], $row["laskunro"]);
					}
					elseif($lajitark == "toimitus") {
						$viesti = t("Toimitus %s toimitus", $kieli, $row["tunnus"]);
						
						//	Voimmeko vielä muokata toimitusaikoja?
						if(!in_array($row["alatila"], array("D","J","E","V","X"))) {
							$menu[] = array("TEKSTI" => t("Muokkaa aikaa"), 		"HREF" => $kalenteri["URL"]."?kaletee=MUOKKAAKERAYSPAIVAA&otunnus=$row[tunnus]");
						}						
					}
					else {
						$viesti = $lajitark;
					}
				}				
				elseif(in_array($row["tila"], array("V"))) {
					$atoim = "MYYNTI";
					if($lajitark == "valmistus") {
						$viesti = t("Valmistus %s", $kieli, $row["tunnus"]);
					}
					$menu[] = array("TEKSTI" => t("Muokkaa aikaa"), 		"HREF" => $kalenteri["URL"]."?kaletee=MUOKKAAKERAYSPAIVAA&otunnus=$row[tunnus]");
				}				
				elseif(in_array($row["tila"], array("A"))) {
					$atoim = "MYYNTI";
					if($lajitark == "tyomaarays") {
						$viesti = t("Tyomaarays %s", $kieli, $row["tunnus"]);
					}

					$menu[] = array("TEKSTI" => t("Muokkaa aikaa"), 		"HREF" => $kalenteri["URL"]."?kaletee=MUOKKAAKERAYSPAIVAA&otunnus=$row[tunnus]");
				}				
				
				if(!in_array($row["alatila"], array("X"))) {
					$menu[] = array("TEKSTI" => t("Muokkaa tilausta")." (ei viel toiminnassa..)", 		"HREF" => "");
				}
				
				$row["otsikko"] = $viesti;
				
				//list($viesti, $poppari) = lyhytviesti($row["tunnus"]." ".$viesti."\nviite: {$row["viesti"]}\n\n{$row["comments"]}", strlen($row["tunnus"]));
				if(file_exists("../raportit/asiakkaantilaukset.php")) {
					$row["viesti"] = " <a href='#' onclick=\"popUp(event, 'toimitus', '300', '-20', '../raportit/asiakkaantilaukset.php?tee=NAYTA&toim=$atoim&h&tunnus=$row[tunnus]'); return false;\" class='kale' $poppari>$viesti</a>";
				}
				else {
					$row["viesti"] = " <a href='#' onclick=\"popUp(event, 'toimitus', '300', '-20', 'raportit/asiakkaantilaukset.php?tee=NAYTA&toim=$atoim&h&tunnus=$row[tunnus]'); return false;\" class='kale' $poppari>$viesti</a>";
				}
				
				//	Duunataan mmuokkausmenu jos tarvetta
				if(is_array($menu) and count($menu) > 0) {
					$row["viesti"] .= "<div align='right' style='float: right; width: 25px;'>".tee_menu($menu, "<img src='$kalenteri[picsDirPath]/lullacons/folder-open-green.png' class='edit'>")."</div>";
				}
			}
			elseif($laji == "liite") {
				
				$query = "	SELECT liitos, liitostunnus, liitetiedostot.selite, filename, filesize, filetype, kayttotarkoitus, selitetark kayttotarkoitus_nimi
							FROM liitetiedostot
							LEFT JOIN avainsana ON avainsana.yhtio=liitetiedostot.yhtio and avainsana.laji = 'TIL-LITETY' and avainsana.selite=liitetiedostot.kayttotarkoitus
							WHERE liitetiedostot.yhtio='$kukarow[yhtio]' and liitetiedostot.tunnus='$tunnus'";
				$abures = mysql_query($query) or pupe_error($query);
				$row = mysql_fetch_array($abures);

				$row["viesti"] .= $row["kayttotarkoitus_nimi"].":&nbsp;<a href='../view.php?id=$tunnus' target='_blank'>{$row["selite"]}</a>";	

			}
			elseif($laji == "kulunvalvonta") {
				
				if($lajitark != "kuka") {
					$query = "	SELECT avainsana.selitetark erittely, kuka.nimi, minuuttimaara, kulunvalvonta.otunnus
								FROM kulunvalvonta
								LEFT JOIN avainsana ON avainsana.yhtio=kulunvalvonta.yhtio and avainsana.laji='KVERITTELY' and avainsana.selite=kulunvalvonta.tyyppi
								LEFT JOIN kuka ON kuka.yhtio=kulunvalvonta.yhtio and kuka.kuka=kulunvalvonta.kuka
								WHERE kulunvalvonta.yhtio = '$kukarow[yhtio]' and kulunvalvonta.tunnus = '$krow[tunnus]'";
					$abures = mysql_query($query) or pupe_error($query);
					$row = mysql_fetch_array($abures);
					//	Nimestä vaan etukirjaimet
					foreach(explode(" ", $row["nimi"]) as $npart) {
						$row["viesti"] .= strtoupper($npart{0});
					}
					$row["viesti"] .= " - $row[erittely]";
					$id=uniqid();
					$div = "
					<div id='$id' class='popup'>
						<table width='150'>
							<tr>
								<td>
									$row[nimi]<br>
									Työaika: ".sprintf("%02d", floor($row["minuuttimaara"]/60)).":".sprintf("%02d", floor($row["minuuttimaara"]%60))."<br>
									projekti: $row[otunnus]
								</td>
							</tr>
						</table>
					</div>";	

					$row["viesti"] = "$div<div onmouseover=\"popUp(event, '$id');\" onmouseout=\"popUp(event, '$id');\">".$row["viesti"]."</div>";
				}
				else {
					$query = "	SELECT nimi
								FROM kuka
								WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$krow[tunnus]'";
					$abures = mysql_query($query) or pupe_error($query);
					$row = mysql_fetch_array($abures);
					//	Nimestä vaan etukirjaimet
					foreach(explode(" ", $row["nimi"]) as $npart) {
						$row["viesti"] .= strtoupper($npart{0});
					}
					$row["viesti"] .= " - ei tunteja";
					
					$id=uniqid();
					$div = "
					<div id='$id' class='popup'>
						<table width='150'>
							<tr>
								<td>
									$row[nimi]<br>
								</td>
							</tr>
						</table>
					</div>";	

					$row["viesti"] = "$div<div onmouseover=\"popUp(event, '$id');\" onmouseout=\"popUp(event, '$id');\">".$row["viesti"]."</div>";
					
				}
			}
			else {
				echo "<pre>Tuntematon laji $laji ".print_r($krow, true)."</pre><br>";
				return false;
			}
			
			return purge($row);
		}	 
	}
	
	if(!function_exists("alusta_kalenteri")) {
		function alusta_kalenteri($kalenteri) {
			global $kukarow;
			//echo "data:<pre>".print_r($data, true)."</pre>";
			
			//	Tallennetaan oikeat muuttujat
			$kalenteri["url_params"][] = "kaleID";
			foreach($kalenteri["url_params"] as $varval) {
				$kalenteri["url_params"][$varval] = $GLOBALS[$varval];
			}
			$kalenteri["url_params"] = purge($kalenteri["url_params"]);
						
			//	Rajataan listaa keiden kalenteria katsotaan
			if(is_array($kalenteri["kalenteri_ketka"])) {
				$k=$kalenteri["kalenteri_ketka"]; 
				$kalenteri["kalenteri_ketka"] = array();
				$kukalisa = "";
				
				if($k[0] != "kaikki" and $k[0] != "aivan_kaikki") {
					foreach($k as $kj) {
						if($kukalisa == "") {
							$kukalisa = " and (";
						}
						else {
							$kukalisa .= " or ";
						}

						if($kj == "myyjat") {
							$kukalisa .= "kuka.myyja > 0";
						}
						elseif($kj == "keraajat") {
							$kukalisa .= "kuka.keraajanro > 0";
						}
						elseif($kj == "projektipaallikot") {
							$kukalisa .= "kuka.asema = 'PP'";
						}
						else {
							die("Väärä kj '$kj'");
						}
					}
				}
				
				//	Joissain caseissa halutaan myös ne jotka eivät ole kalenterikäyttäjiä
				if($k[0] == "aivan_kaikki") {
					$query = "	SELECT DISTINCT kuka.kuka
								FROM kuka
								WHERE yhtio = '$kukarow[yhtio]'";
					$result = mysql_query($query) or pupe_error($query);
				}
				else {
					$query = "	SELECT DISTINCT kuka.kuka
								FROM kuka
								JOIN oikeu ON oikeu.yhtio = kuka.yhtio and oikeu.kuka = kuka.kuka and oikeu.nimi = 'crm/kalenteri.php'
								WHERE kuka.yhtio = '$kukarow[yhtio]' $kukalisa";
					$result = mysql_query($query) or pupe_error($query);
				}

				while($row = mysql_fetch_array($result)) {
					$kalenteri["kalenteri_ketka"][] = $row["kuka"];
				}
			}
			
			//	Millaiset kalenterit jaetaan?
			if(is_array($kalenteri["kalenteri_jako"])) {
				foreach($kalenteri["kalenteri_jako"] as $kj) {
					if($kalenteri_jako == "") {
						$kalenteri_jako = " and (";
					}
					else {
						$kalenteri_jako .= " or ";
					}
					
					if($kj == "tilaus") {
						//	Meidän pitää tarkistaa onko tämä nippu!
						$abuq = "	SELECT GROUP_CONCAT(tunnus) nippu
									FROM lasku
									WHERE yhtio='$kukarow[yhtio]' and tunnusnippu = '{$kalenteri["tunnusnippu"]}' and tila in ('R','L','N','T','A')";
						$res = mysql_query($abuq) or pupe_error($abuq);
						$row = mysql_fetch_array($res);
						
						if($row["nippu"] == "") $row["nippu"] = $kalenteri["otunnus"];

						$kalenteri_jako .= "kalenteri.otunnus IN ({$row["nippu"]})";
					}
					elseif($kj == "asiakas"){
						$kalenteri_jako .= "kalenteri.liitostunnus = {$kalenteri["liitostunnus"]}";
					}
					else {
						echo "Tällästä kalenteri_jako:a ei tunneta '$kj'<br>";
					}
				}

				$kalenteri_jako .= ")";
				$kalenteri["kalenteri_jako"] = $kalenteri_jako;
			}
			
			if($kalenteri["div"] == "") die("Kalenterin DIV puuttuu");
			
			//	Arvotaan missä meidän pics kansio on?
			if(file_exists("pics/")) {
				$kalenteri["picsDirPath"] = "pics/";
			}
			else {
				$kalenteri["picsDirPath"] = "../pics/";
			}
			
			//	Aloitetaan kalenteri, tallennetaan sen parametrit muistiin..
			if(!muistiin("kalenteri", "kalenteri", $kalenteri)) {
				die("OHO! Kalenterin käyttönotto ei nyt vaan onnistu!");
			}			
		} 
	}
	
	if(!function_exists("kalequery")) {
		function kalequery($query = "") {
			global $kukarow, $kalelisaa, $kalepoisto;
			
			$kalenteri = muistista("kalenteri", "kalenteri");
			
			if(!is_array($kalenteri)) {
				die("OHO! Kalenterin meni rikki!");
			}
			
			//	Lisätään arvoja kalenteriin
			if(count($kalelisaa)>0) {
				foreach($kalelisaa as $key => $val) {
					if(!in_array($val, $kalenteri[$key])) {
						$kalenteri[$key][] = $val;
					}
				}
			}

			//	Poistetaan arvoja kalenterista
			if(count($kalepoisto)>0) {
				foreach($kalepoisto as $key => $val) {

					$i = array_search($val, $kalenteri[$key]);
					
					if($i !== false) {
						unset($kalenteri[$key][$i]);
					}
				}
			}
			
			//	Tarkistetaan, että koitetaan näyttää oikeaa käyttäkää
			if(count($kalenteri["kalenteri_ketka"]) > 0) {
				foreach($kalenteri["kalenteri_nayta_kuka"] as $key => $val) {
					
					if(array_search($val, $kalenteri["kalenteri_ketka"]) === FALSE) {
						unset($kalenteri["kalenteri_nayta_kuka"][$key]);
					}
				}
			}			
			
			//	Painetaan nämä taas muistin syövereihin jotta ei dementia iske
			if(!muistiin("kalenteri", "kalenteri", $kalenteri)) {
				die("OHO! Kalenterin käyttönotto ei nyt vaan onnistu!");
			}
			
			/*	
				Perus kalenteritapahtumat
			*/
						
			if(count($kalenteri["kalenteri_nayta_kuka"]) > 0) {
				$kukat = implode("','", $kalenteri["kalenteri_nayta_kuka"]);
			}
			else {
				$kukat = "";
			}
			
			if(count($kalenteri["kalenteri_nayta_tyyppi"]) > 0) {
				$tyypit = implode("','", $kalenteri["kalenteri_nayta_tyyppi"]);
			}
			else {
				$tyypit = "";
			}
			
			/*
				Tilaus ja tarjousdata
			*/
			if(count($kalenteri["kalenteri_nayta_tilausdata"])>0 and ($kalenteri["liitostunnus"] > 0 or $kukat != "" or $kalenteri["tunnusnippu"] > 0 or $kalenteri["toimitus"])) {
				
				//	Rajaukset
				$wherelisa = $asiakas_join = "";
				if($kalenteri["liitostunnus"] > 0) {
					$asiakas_join = " and asiakas.tunnus = '{$kalenteri["liitostunnus"]}'";
				}
				
				if($kalenteri["tunnusnippu"] > 0) {
					$wherelisa .= " and lasku.tunnusnippu = '{$kalenteri["tunnusnippu"]}'";
				}
				
				//	Rajataan käyttäjittäin jos käyttäjä saa raksitella tässä jotain
				if($kalenteri["kalenteri_tilausdata_rajaus"] == "kayttaja") {
					$kukaq = "	SELECT group_concat(myyja)
								FROM kuka
								WHERE yhtio='$kukarow[yhtio]' and kuka IN ('$kukat') and myyja > 0
								GROUP BY yhtio";
					$result = mysql_query($kukaq) or pupe_error($kukaq);
					$row = mysql_fetch_array($result);

					$wherelisa .= " and (lasku.laatija IN ('$kukat') or lasku.myyja IN ('{$row[0]}'))";
				}		
				
				//	Koostetaan rajaukset
				if($asiakas_join != "") {
					$asiakas_join = "JOIN asiakas ON asiakas.yhtio = lasku.yhtio and asiakas.tunnus=lasku.liitostunnus $asiakas_join";
				}

				foreach($kalenteri["kalenteri_nayta_tilausdata"] as $tdata) {
										
					if($tdata == "tilaus") {
						$query .= "
						UNION
						/*	Tilaus kirjattu	*/	
						(	
							SELECT distinct 'lasku' laji, 'tilaus' laji_tark, UNIX_TIMESTAMP(date(lasku.luontiaika)) aika, lasku.luontiaika, '1' vali, lasku.laatija laatija, lasku.tunnus tunnus
							FROM lasku
							$asiakas_join
							WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tunnus = lasku.tunnusnippu and lasku.tila IN ('R','L','N','A') $wherelisa
						)
						";						
					}
					elseif($tdata == "toimitus") {
						$query .= "
						UNION
						/*	Toimituksen lähetys	*/	
						(	
							SELECT distinct 'lasku' laji, 'toimitus' laji_tark, UNIX_TIMESTAMP(date(lasku.kerayspvm)) aika, lasku.luontiaika, DATEDIFF(toimaika, kerayspvm) vali, lasku.laatija laatija, lasku.tunnus tunnus
							FROM lasku
							$asiakas_join
							WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila IN ('L','N') $wherelisa
						)
						";
					}
					elseif($tdata == "valmistus") {
						$query .= "
						UNION
						/*	Tilauksen valmistua	*/	
						(	
							SELECT distinct 'lasku' laji, 'valmistus' laji_tark, UNIX_TIMESTAMP(date(lasku.kerayspvm)) aika, lasku.luontiaika, DATEDIFF(toimaika, kerayspvm) vali, lasku.laatija laatija, lasku.tunnus tunnus
							FROM lasku
							$asiakas_join
							WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila IN ('V') $wherelisa
						)
						";
					}
					elseif($tdata == "tyomaarays") {
						$query .= "
						UNION
						/*	Työmääräys	*/	
						(	
							SELECT distinct 'lasku' laji, 'tyomaarays' laji_tark, UNIX_TIMESTAMP(date(lasku.kerayspvm)) aika, lasku.luontiaika, DATEDIFF(toimaika, kerayspvm) vali, lasku.laatija laatija, lasku.tunnus tunnus
							FROM lasku
							$asiakas_join
							WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila IN ('A') $wherelisa
						)
						";
					}
					elseif($tdata == "tarjous") {
						$query .= "
						UNION
						/*	Tarjous avattu	*/	
						(	
							SELECT 'lasku' laji, 'alku' laji_tark, UNIX_TIMESTAMP(date(lasku.luontiaika)) aika, lasku.luontiaika, '1' vali, lasku.laatija laatija, lasku.tunnus tunnus
							FROM lasku
							$asiakas_join
							WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila = 'T' $wherelisa
						)
						";
					}
					elseif($tdata == "tarjouskontaktointi") {
						
						$abuq = "	SELECT max(l2.tunnus)
									FROM lasku AS l2
									WHERE l2.yhtio=lasku.yhtio and l2.tunnusnippu=lasku.tunnusnippu and l2.tunnusnippu>0";

						$abuq2 = "	SELECT group_concat(l2.tunnus)
									FROM lasku AS l2
									WHERE l2.yhtio=lasku.yhtio and l2.tunnusnippu=lasku.tunnusnippu and l2.tunnusnippu>0
									GROUP BY tunnusnippu";
									
						$query .= "
						UNION					
						/*	Eka kontakti	*/
						(
							SELECT laji, 'kontakti_1' laji_tark,  UNIX_TIMESTAMP(lasku.aika) aika, luontiaika, '1' vali, laatija, tunnus
							FROM 
							(
							 (
							  SELECT 'kontakti_oletettu' laji, date_add(lasku.luontiaika, INTERVAL 14 DAY) aika, lasku.luontiaika, lasku.laatija, lasku.tunnus, lasku.tunnusnippu
							  FROM lasku l
							  JOIN lasku ON lasku.yhtio = l.yhtio and lasku.tunnusnippu = l.tunnusnippu and lasku.tunnus = ($abuq)
							  $asiakas_join
							  WHERE lasku.yhtio = '{$kukarow["yhtio"]}' and lasku.tila IN ('T') $wherelisa
							 )
							 UNION
							 (
							  SELECT 'kontakti' laji, k.pvmalku aika, k.luontiaika, k.laatija, k.tunnus, lasku.tunnusnippu tunnusnippu
							  FROM lasku
							  JOIN kalenteri k ON k.yhtio=lasku.yhtio and k.otunnus IN ($abuq2) and k.tyyppi = 'kontakti_1'
							  $asiakas_join
							  WHERE lasku.yhtio = '{$kukarow["yhtio"]}' and lasku.tila IN ('T') $wherelisa
							 )
							 ORDER BY laji IN('kontakti') desc
							)
							AS lasku
							GROUP BY tunnusnippu
						)
						UNION					
						/*	toka kontakti	*/
						(
							SELECT laji, 'kontakti_2' laji_tark,  UNIX_TIMESTAMP(lasku.aika) aika, luontiaika, '1' vali, laatija, tunnus
							FROM 
							(
							 (
							  SELECT 'kontakti_oletettu' laji, date_add(lasku.luontiaika, INTERVAL 45 DAY) aika, lasku.luontiaika, lasku.laatija, lasku.tunnus, lasku.tunnusnippu
							  FROM lasku l
							  JOIN lasku ON lasku.yhtio = l.yhtio and lasku.tunnusnippu = l.tunnusnippu and lasku.tunnus = ($abuq)
							  $asiakas_join
							  WHERE lasku.yhtio = '{$kukarow["yhtio"]}' and lasku.tila IN ('T') $wherelisa
							 )
							 UNION
							 (
							  SELECT 'kontakti' laji, k.pvmalku aika, k.luontiaika, k.laatija, k.tunnus, lasku.tunnusnippu
							  FROM lasku
							  JOIN kalenteri k ON k.yhtio=lasku.yhtio and k.otunnus=lasku.tunnus and k.tyyppi = 'kontakti_2'
							  $asiakas_join
							  WHERE lasku.yhtio = '{$kukarow["yhtio"]}' and lasku.tila IN ('T') $wherelisa
							 )
							 ORDER BY laji IN('kontakti') desc
							)
							AS lasku
							GROUP BY tunnusnippu
						)
						UNION					
						/*	Viimeinen voitelu	*/
						(
							SELECT laji, 'kontakti_3' laji_tark, UNIX_TIMESTAMP(lasku.aika) aika, luontiaika, '1' vali, laatija, tunnus
							FROM 
							(
							 (
							  SELECT 'kontakti_oletettu' laji, date_add(lasku.luontiaika, INTERVAL 76 DAY) aika, lasku.luontiaika, lasku.laatija, lasku.tunnus, lasku.tunnusnippu
							  FROM lasku l
							  JOIN lasku ON lasku.yhtio = l.yhtio and lasku.tunnusnippu = l.tunnusnippu and lasku.tunnus = ($abuq)
							  $asiakas_join
							  WHERE lasku.yhtio = '{$kukarow["yhtio"]}' and lasku.tila IN ('T') $wherelisa
							 )
							 UNION
							 (
							  SELECT 'kontakti' laji, k.pvmalku aika, k.luontiaika, k.laatija, k.tunnus, lasku.tunnusnippu
							  FROM lasku
							  JOIN kalenteri k ON k.yhtio=lasku.yhtio and k.otunnus=lasku.tunnus and k.tyyppi = 'kontakti_3'
							  $asiakas_join
							  WHERE lasku.yhtio = '{$kukarow["yhtio"]}' and lasku.tila IN ('T') $wherelisa
							 )
							 ORDER BY laji IN('kontakti') desc
							)
							AS lasku
							GROUP BY tunnusnippu
						)";
					}					
					elseif($tdata == "laskutus") {
						$query .= "
						UNION
						/*	Toimitus laskutettu	*/	
						(	
							SELECT 'lasku' laji, 'laskutus' laji_tark, UNIX_TIMESTAMP(date(lasku.laskutettu)) aika, lasku.luontiaika, '1' vali, lasku.laatija laatija, lasku.tunnus tunnus
							FROM lasku
							$asiakas_join
							WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila = 'U' and lasku.laskunro > 0 $wherelisa
						)
						";
					}
				}
			}
			
			if(count($kalenteri["kalenteri_nayta_tuntidata"])>0) {
				
				foreach($kalenteri["kalenteri_nayta_tuntidata"] as $tdata) {
					if($tdata == "projektitunnit") {
						if($kukat != "") {
							$wherelisa = " and kulunvalvonta.kuka IN ('$kukat')";
						}
						else {
							$wherelisa = "";
						}
						
						$query .= "
						UNION
						/*	Tehty tunteja projektille	*/	
						(	
							SELECT distinct 'kulunvalvonta' laji, 'projektitunnit' laji_tark, UNIX_TIMESTAMP(date(kulunvalvonta.aika)) aika, kulunvalvonta.aika luontiaika, '0' vali, kulunvalvonta.kuka laatija, kulunvalvonta.tunnus tunnus
							FROM kulunvalvonta
							WHERE kulunvalvonta.yhtio = '$kukarow[yhtio]' and kulunvalvonta.otunnus='{$kalenteri["tunnusnippu"]}' and suunta ='' $wherelisa
						)
						";
					}
					elseif($tdata == "tyotunnit") {
						if($kukat != "") {
							$wherelisa = " and kuka.kuka IN ('$kukat')";
						}
						else {
							$wherelisa = "";
						}
						
						$query .= "
						UNION
						/*	Tehty työtunteja	*/	
						(	
							SELECT distinct 'kulunvalvonta' laji, if(kulunvalvonta.tunnus IS NULL, 'kuka', 'tunnit') laji_tark, UNIX_TIMESTAMP(date(if(kulunvalvonta.aika IS NULL, now(), kulunvalvonta.aika))) aika, kulunvalvonta.aika luontiaika, '0' vali, kulunvalvonta.kuka laatija, if(kulunvalvonta.tunnus IS NULL, kuka.tunnus, kulunvalvonta.tunnus) tunnus
							FROM kuka
							LEFT JOIN kulunvalvonta ON kulunvalvonta.yhtio=kuka.yhtio and kulunvalvonta.kuka=kuka.kuka and suunta =''
							WHERE kuka.yhtio = '$kukarow[yhtio]' and osasto != '' $wherelisa
						)
						";
					}
					
				}				
			}
			
			//	Laitetaan tämä vaikka ei ole tyyppejä jotta ei tule SQL-erroria
			if($kukat != "") {
				$kukalisa = " and kuka.kuka IN ('$kukat')";
			}
			elseif($kalenteri["kalenteri_rajaus"] == "kayttaja") {
				$kukalisa = " and kuka.kuka IN ('')";
			}
			
			$q = "	
				SELECT 'kalenteri' laji, kalenteri.tyyppi laji_tark, UNIX_TIMESTAMP(date(pvmalku)) aika,  kalenteri.luontiaika, DATEDIFF(pvmloppu, pvmalku) vali, kalenteri.laatija, kalenteri.tunnus
				FROM kalenteri
				JOIN kuka ON kuka.yhtio=kalenteri.yhtio and kuka.kuka=kalenteri.kuka $kukalisa
				WHERE kalenteri.yhtio = '$kukarow[yhtio]' 
					and kalenteri.tyyppi IN ('$tyypit')
					and kalenteri.kuittaus = ''
					$kalenteri[kalenteri_jako]";
			
			if($query != "") {
				$query = "	(
								$q
							)
								$query";
			}
			else {
				$query = $q;
			}
			
			/*	Haetaan kaikki kalenterimerkinnät */
			$query .= "	ORDER BY 3, 4";
			$result = mysql_query($query) or pupe_error($query);
			//echo "<div align = 'left'><pre>$query</pre><br></div>";
			$data=array();
			while($row=mysql_fetch_array($result)) {
				if(!isset($data["tiedot"]["alkustr"])) {
					$data["tiedot"]["alkustr"] = $row["aika"];
				}
				$laika = strtotime("+$row[vali] days", $row["aika"]);

				if($laika > $data["tiedot"]["loppustr"]) {
					$data["tiedot"]["loppustr"] = $laika;
				}
				
				$data["lista"][$row["aika"]][] = purge($row);
			}
			
			foreach($data["lista"] as $aika => &$t) {	
				foreach($t as $k => &$kalerow) {

					//	Jos tämä on kopioitu aika..
					if($kalerow["aika"] != $aika) {
						continue;
					}

					//	Liitetään lisää tapahtumia..
					if($kalerow["vali"] >= 1) {
						for($i=1;$i<=$kalerow["vali"]; $i++) {
							$data["lista"][strtotime("+$i days", $aika)][] = &$kalerow;
						}
					}
				}
			}
			
			return $data;
		}
	}
	
	if($kaletee == "printPDF") {
		require_once('pdflib/pupepdf.class.php');

		$fonts["snadi"]['height']	= 9;
		$fonts["snadi"]['font']		= "Helvetica";

		$fonts["norm"]['height']	= 10;
		$fonts["norm"]['font']		= "Helvetica";

		$fonts["BIG"]['height']		= 14;
		$fonts["BIG"]['font']		= "Helvetica";	
	}
	
	//	Luodaan kalenterinäkymiä annetusta datasta
	if(!function_exists("kalenteri")) {
		function kalenteri ($data, $kalePost="") {
			global $kukarow, $yhtiorow, $nakyma, $kaleaika, $kaletee, $kalenteri;
			
			//echo "<hr>data:<pre>".print_r($data, true)."</pre>";
			$kalenteri = muistista("kalenteri", "kalenteri");
			
			if(!is_array($kalenteri)) {
				die("OHO! Kalenterin meni rikki!");
			}
			
			if($nakyma != $kalenteri["nakyma"] and $nakyma != "") {
				$kalenteri["nakyma"] = $nakyma;
			}
			
			if($kaleaika != "") {
				$kalenteri["kaleaika"] = $kaleaika;
			}
						
			$nakyma = $kalenteri["nakyma"];
			$kaleaika = $kalenteri["kaleaika"];			
			
			if($kaleaika == "") {
				$kaleaika = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
			}
						
			//	tallennetaan muuttunut tilanne
			if(!muistiin("kalenteri", "kalenteri", $kalenteri)) {
				die("OHO! Kalenterin käyttönotto ei nyt vaan onnistu!");
			}
			else {
				//echo "tallennetaan: <pre>".print_r($kalenteri, true)."</pre>";
			}
			
			
			$kalejako = "";
			if(is_array($kalenteri["kalenteri_nayta_kuka"])) {
				$kalejako = "
						<table border='1' width='140'>
						<caption>".t("Valitse henkilöt")."</caption>";
						
				$lisa = "";
				
				$query = " 	SELECT *
							FROM kuka
							WHERE yhtio='$kukarow[yhtio]' and kuka IN ('".implode("','", $kalenteri["kalenteri_ketka"])."')
							ORDER BY kuka IN('{$kukarow["kuka"]}') DESC, kuka.nimi ASC ";
				$result = mysql_query($query) or pupe_error($query);
				if(mysql_num_rows($result)>0) {
					while($row = mysql_fetch_array($result)) {
						
						$kalejako .= "<tr>";
						
						if(in_array($row["kuka"], $kalenteri["kalenteri_nayta_kuka"])) {
							$kalejako .= "
								<td><div style='float: left;'><font class='info'>{$row["nimi"]}</font></div><div style='float: right;'><input type='checkbox' name='' onclick=\"sndReq('$kalenteri[div]', '$kalenteri[URL]?ohje=off&kalepoisto[kalenteri_nayta_kuka]=$row[kuka]".url_params($kalenteri)."', '$kalenteri[div]', false);\" checked></div></td>";
						}
						else {
							$kalejako .= "
								<td><div style='float: left;'><font class='info'>{$row["nimi"]}</font></div><div style='float: right;'><input type='checkbox' name='' onclick=\"sndReq('$kalenteri[div]', '$kalenteri[URL]?ohje=off&kalelisaa[kalenteri_nayta_kuka]=$row[kuka]".url_params($kalenteri)."', '$kalenteri[div]', false);\"></div></td>";
						}
						
						$kalejako .= "</tr>";
					}
				}
				
				$kalejako .= "</table>";
							
			}
			
			if(count($kalenteri["kalenteri_tyypit"])>0) {
				$kalejako .= "
					<br>
					<table border='1' width='140'>
						<caption>".t("Näytä tapahtumat")."</caption>";

					foreach($kalenteri["kalenteri_tyypit"] as $tyyppi) {
						
						$kalejako .= "<tr>";
						
						if(in_array($tyyppi, $kalenteri["kalenteri_nayta_tyyppi"])) {
							$kalejako .= "
								<td><div style='float: left;'><font class='info'>".kaleotsikko($tyyppi)."</font></div><div style='float: right;'><input type='checkbox' name='' onclick=\"sndReq('$kalenteri[div]', '$kalenteri[URL]?ohje=off&kalepoisto[kalenteri_nayta_tyyppi]=$tyyppi".url_params($kalenteri)."', '$kalenteri[div]', false);\" checked></div></td>";
						}
						else {
							$kalejako .= "
								<td><div style='float: left;'><font class='info'>".kaleotsikko($tyyppi)."</font></div><div style='float: right;'><input type='checkbox' name='' onclick=\"sndReq('$kalenteri[div]', '$kalenteri[URL]?ohje=off&kalelisaa[kalenteri_nayta_tyyppi]=$tyyppi".url_params($kalenteri)."', '$kalenteri[div]', false);\"></div></td>";
						}
						
						$kalejako .= "</tr>";
					}

					$kalejako .= "
					</table>";
			}

			if(count($kalenteri["kalenteri_tilausdata"])>0) {
				$kalejako .= "
					<br>
					<table border='1' width='140'>
						<caption>".t("Näytä tilausdata")."</caption>";

					foreach($kalenteri["kalenteri_tilausdata"] as $tyyppi) {
						
						$kalejako .= "<tr>";
						
						if(in_array($tyyppi, $kalenteri["kalenteri_nayta_tilausdata"])) {
							$kalejako .= "
								<td><div style='float: left;'><font class='info'>".kaleotsikko($tyyppi)."</font></div><div style='float: right;'><input type='checkbox' name='' onclick=\"sndReq('$kalenteri[div]', '$kalenteri[URL]?ohje=off&kalepoisto[kalenteri_nayta_tilausdata]=$tyyppi".url_params($kalenteri)."', '$kalenteri[div]', false);\" checked></div></td>";
						}
						else {
							$kalejako .= "
								<td><div style='float: left;'><font class='info'>".kaleotsikko($tyyppi)."</font></div><div style='float: right;'><input type='checkbox' name='' onclick=\"sndReq('$kalenteri[div]', '$kalenteri[URL]?ohje=off&kalelisaa[kalenteri_nayta_tilausdata]=$tyyppi".url_params($kalenteri)."', '$kalenteri[div]', false);\"></div></td>";
						}
						
						$kalejako .= "</tr>";
					}

					$kalejako .= "
					</table>";
			}
			
			if(count($kalenteri["kalenteri_tuntidata"])>0) {
				$kalejako .= "
					<br>
					<table border='1' width='140'>
						<caption>".t("Näytä työtunnit")."</caption>";

					foreach($kalenteri["kalenteri_tuntidata"] as $tyyppi) {
						
						$kalejako .= "<tr>";
						
						if(in_array($tyyppi, $kalenteri["kalenteri_nayta_tuntidata"])) {
							$kalejako .= "
								<td><div style='float: left;'><font class='info'>".kaleotsikko($tyyppi)."</font></div><div style='float: right;'><input type='checkbox' name='' onclick=\"sndReq('$kalenteri[div]', '$kalenteri[URL]?ohje=off&kalepoisto[kalenteri_nayta_tuntidata]=$tyyppi".url_params($kalenteri)."', '$kalenteri[div]', false);\" checked></div></td>";
						}
						else {
							$kalejako .= "
								<td><div style='float: left;'><font class='info'>".kaleotsikko($tyyppi)."</font></div><div style='float: right;'><input type='checkbox' name='' onclick=\"sndReq('$kalenteri[div]', '$kalenteri[URL]?ohje=off&kalelisaa[kalenteri_nayta_tuntidata]=$tyyppi".url_params($kalenteri)."', '$kalenteri[div]', false);\"></div></td>";
						}
						
						$kalejako .= "</tr>";
					}

					$kalejako .= "
					</table>";
			}					
			
			//	Piirretään header
			if($kalenteri["vain_luku"] != true) {
				$menu = array();
				$menu[]	= array("VALI" => t("Toiminnot"));	
				if(is_array($data["tiedot"]["menut"]["toiminnot"])) {

					foreach($data["tiedot"]["menut"]["toiminnot"] as $a) {
						$menu[] = $a;
					}
				}

				$menu[] = array("TEKSTI" => t("Lisää kalenteritapahtuma"),	"HREF" => "$kalenteri[URL]?kaletee=KALENTERITAPAHTUMA&tyyppi=kalenteri&tapvm=$kaleaika".url_params($kalenteri), "OFFSETX" => 350, "OFFSETY" => 30);
				$menu[] = array("TEKSTI" => t("Lisää asiakasmuistio"),		"HREF" => "$kalenteri[URL]?kaletee=KALENTERITAPAHTUMA&tyyppi=Memo&tapvm=$kaleaika".url_params($kalenteri), "OFFSETX" => 350, "OFFSETY" => 40);
				$menu[] = array("TEKSTI" => t("Lisää muistutus"),			"HREF" => "$kalenteri[URL]?kaletee=KALENTERITAPAHTUMA&tyyppi=Muistutus&tapvm=$kaleaika".url_params($kalenteri), "OFFSETX" => 350, "OFFSETY" => 50);
			
				if(in_array("projektitapahtuma", $kalenteri["kalenteri_tyypit"])) {
					$menu[] = array("TEKSTI" => t("Lisää projektitapahtuma"),			"HREF" => "$kalenteri[URL]?kaletee=KALENTERITAPAHTUMA&tyyppi=projektitapahtuma&tapvm=$kaleaika".url_params($kalenteri), "OFFSETX" => 350, "OFFSETY" => 70);
					
					//katsotaan onko projektin numero 9, eli omia projekteja, jos on niin annetaan vaihtoehto vaihtaan projektin tilaa
					$query = "	SELECT tilaustyyppi
								FROM lasku
								WHERE yhtio='$kukarow[yhtio]' and tunnus='$kalenteri[tunnusnippu]'";
					$result = mysql_query($query) or pupe_error($query);
					if (mysql_num_rows($result) == 1) {
						$tilaustyyppi = mysql_result($result,0,"tilaustyyppi");
						if ($tilaustyyppi == 9) {
							$menu[] = array("TEKSTI" => t("Vaihda oman projektin tila"),			"HREF" => "$PHP_SELF?tee=muutaomanprojektintila&tunnus=$kalenteri[tunnusnippu]", "OFFSETX" => 350, "OFFSETY" => 60);
						}
					}
				
					
					$menu[] = array("TEKSTI" => t("Tulosta kalenteri"),					"HREF" => "$kalenteri[URL]?kaletee=printPDF&tyyppi=projektitapahtuma&nayta_pdf=1".url_params($kalenteri), "TARGET" => "_blank");
					$menu[] = array("TEKSTI" => t("Tulosta kalenteri asiakkaalle"),					"HREF" => "$kalenteri[URL]?kaletee=printPDF&tyyppi=projektitapahtuma&nayta_pdf=1&asiakkaalle=yes".url_params($kalenteri), "TARGET" => "_blank");
				}

				//$menu[] = array("TEKSTI" => "Lisää merkintä",			"HREF" => "$kalenteri[URL]?kaletee=KALENTERITAPAHTUMA&tyyppi=Merkinta&tapvm=$kaleaika".url_params($kalenteri), "OFFSETX" => 350, "OFFSETY" => 50);
				
			}
			
			$palkki = array();
			//	Tallennetaan tämä oletukseksi
			$menuItem = tee_menu($menu, "<font class='message'>".t("Toiminnot")."</font>");
			if($menuItem) {
				$palkki = array($menuItem);
			}

			$menu = array();
			foreach(array("TAPAHTUMALISTAUS", "KALENTERINAKYMA", "KUUKAUSINAKYMA", "VIIKKONAKYMA", "PAIVANAKYMA", "RIVINAKYMA_PAIVA", "RIVINAKYMA_VIIKKO") as $n) {
				if(in_array($n, $kalenteri["sallittu_nakyma"])) {
					$menu[] = array("TEKSTI" => nakymaotsikko($n),		"HREF" => "$kalenteri[URL]?nakyma=$n".url_params($kalenteri), "DIV" => $kalenteri["div"], "TAPA" => "request");
				} 
			}
			
			$menuItem = tee_menu($menu, "<font class='message'>".t("Vaihda näkymää")."</font>");
			if($menuItem) {
				$palkki[] = $menuItem;
			}
			
			if(is_array($data["tiedot"]["menut"])) {
				foreach($data["tiedot"]["menut"] as $key => $val) {
					//	Tämä on oma valikko vain jos sen indexi on numero
					if(is_numeric($key)) {
						$palkki[] = $val;
					}
				}
			}
			
			//	valmistellaan palakki
			$tmenut = implode("&nbsp;&nbsp;-&nbsp;&nbsp;", $palkki);
			$kale = "<table align='center' border='0'><tr><td class='back'>";
			if($nakyma == "KALENTERINAKYMA" or $nakyma == "KUUKAUSINAKYMA") {
				
				$kale .= "
				<table align='center'>";
				
				if($nakyma == "KUUKAUSINAKYMA") {
										
					$kaleaika = mktime(0, 0, 0, date("m", $kaleaika), 1, date("Y", $kaleaika));
					$paivia = cal_days_in_month(CAL_GREGORIAN, date("m", $kaleaika), date("Y", $kaleaika))-2;
					
					$seuraava 	= strtotime("+1 months", $kaleaika);
					$edellinen 	= strtotime("-1 months", $kaleaika);
					$otsikko	= t("Kausi")." ".date("m", $kaleaika)." - ".date("Y", $kaleaika);
					
$kale .= "<caption style='margin-bottom: 5px;'><div style='text-align: left; float: left; margin-left: 20px'><a href=\"javascript:sndReq('$kalenteri[div]', '$kalenteri[URL]?ohje=off&kaleaika=$edellinen".url_params($kalenteri)."', '$kalenteri[div]', false);\">&lt;&lt;&nbsp;</a>&nbsp;&nbsp;$otsikko&nbsp;&nbsp;&nbsp;<a href=\"javascript:sndReq('$kalenteri[div]', '$kalenteri[URL]?ohje=off&kaleaika=$seuraava".url_params($kalenteri)."', '$kalenteri[div]', false);\">&gt;&gt;</div></a>$tmenut</caption>";
					
				}
				else {
										
					//	Käsitellään maksimiajankohta
					$query = " SELECT DATEDIFF('".date("Y-m-d", $data["tiedot"]["loppustr"])."', '".date("Y-m-d", $data["tiedot"]["alkustr"])."') paivia";
					$abures = mysql_query($query) or pupe_error($query);
					$aburow = mysql_fetch_array($abures);
					$paivia = $aburow["paivia"];
					$kaleaika = $data["tiedot"]["alkustr"];
										
				}
				
				//	Tän viikon eka päivä
				if(date("N", $kaleaika) == 1) {
					$maanantai = $kaleaika;
				}
				elseif(date("N", $kaleaika)<1) {
					$maanantai = strtotime("-1 monday", $kaleaika);
				}
				else {
					$maanantai = strtotime("+1 monday", $kaleaika);
				}
				
				//	varmistetaan että päätytään sunnuntaihin
				$vikapaiva = strtotime("+$paivia days", $kaleaika);
				if(date("N", $vikapaiva)!=0) {
					$vikapaiva = strtotime("sunday", $vikapaiva);
				}
	
				$query = " SELECT DATEDIFF('".date("Y-m-d", $vikapaiva)."', '".date("Y-m-d", $maanantai)."') paivia";
				$abures = mysql_query($query) or pupe_error($query);
				$aburow = mysql_fetch_array($abures);
				$paivia = $aburow["paivia"]+1;
				
				//	Tehdään otsikot
				$kale .= "
				<tr>
					<th>".t("Vko")."</th><th>".t("Ma")."</th><th>".t("Ti")."</th><th>".t("Ke")."</th><th>".t("To")."</th><th>".t("Pe")."</th><th>".t("La")."</th><th>".t("Su")."</th>
				</tr>";
				
				$leveys = 7;
				$height = floor(500/($paivia/7));
								
				
				$tpos=array();
				$tcolor = array();
				for($i=0;$i<$paivia;$i++) {
					$aikastr = strtotime("+$i days", $maanantai);
					if($i/$leveys == (int) ($i/$leveys)) {
						$kale .= "	</tr>
								<tr style='height: 5px;'>
									<td class='back'></td>
								</tr>
								<tr>
									<td class='back' align = 'left'><font class='info'>".date("W", $aikastr)."</font></td>";
									
						//	Rullataas kerran läpi niin tiedetään maksimit ja minimit tapahtumamäärille joka auttaa positioinnissa
						for($y=0;$y<$leveys;$y++) {
							if(is_array($data["lista"][strtotime("+$y days", $aikastr)])) {
								foreach($data["lista"][strtotime("+$y days", $aikastr)] as $row) {
									if($row["vali"] >= 1 and !isset($tpos[$row["tunnus"]])) {
										$tpos[$row["tunnus"]] = (int) count($tpos)+1;
									}
								}
							}
						}
					}

					if(date("Ymd", $aikastr) == date("Ymd")) {
						$color = "rgba(252,245,22,0.5)";
					}
					else {
						$color = "inherit";				
					}

					if(is_array($data["lista"][$aikastr])) {

						$ulos = array();
						$viesti = "";
						
						//	Haetaan ensin useampipäiväiset tapahtumat
						foreach($data["lista"][$aikastr] as $row) {
							if($row["vali"] >= 1) {
								if($row["aika"] == $aikastr) {
									
									$tcolor[$row["tunnus"]] = color_code(count($tcolor)*13+55, 100);

									$poppari="";
									$lisatiedot = hae_lisatiedot($row, 0);	
									
									$ulos[$tpos[$row["tunnus"]]] .= "<div style='height: 15; width; 100%; text-align: left; background-color: ".$tcolor[$row["tunnus"]].";'>{$lisatiedot["viesti"]}</div>";
								}
								else {
									$ulos[$tpos[$row["tunnus"]]] .= " <div style='height: 15; width; 100%; text-align: left; background-color: ".$tcolor[$row["tunnus"]].";'></div>";
								}
							}
						}
						
						$u = "";
						//	Haetaan sitten tämänpäivän tapahtumat
						foreach($data["lista"][$aikastr] as $row) {
							if($row["aika"] == $aikastr and $row["vali"] < 1) {
								if($u != "") $u .= "<hr>";

								$lisatiedot = hae_lisatiedot($row, 0);

								$u .= $lisatiedot["viesti"];
							}
						}
						$ulos[]=$u;
					}
					else {
						$ulos = array("&nbsp;");
					}
					

					
					//	Tarkastetaan vielä että kaikki slotit on täynnä
					foreach($tpos as $t) {
						if($ulos[$t] == "") $ulos[$t] = "<div style='height: 15; width; 100%; text-align: left; background-color: inherit;'>&nbsp;</div>";
					}
					ksort($ulos);					
					$menu = array();
					$menu[] = array("TEKSTI" => t("Lisää kalenteriisi"), 		"HREF" => "$kalenteri[URL]?kaletee=KALENTERITAPAHTUMA&tyyppi=kalenteri&tapvm=$aikastr".url_params($kalenteri), "OFFSETX" => 350, "OFFSETY" => 30);
					$menu[] = array("TEKSTI" => t("Lisää muistutus"), 			"HREF" => "$kalenteri[URL]?kaletee=KALENTERITAPAHTUMA&tyyppi=Muistutus&tapvm=$aikastr".url_params($kalenteri), "OFFSETX" => 350, "OFFSETY" => 40);
					$menu[] = array("TEKSTI" => t("Lisää asiakasmuistio"), 		"HREF" => "$kalenteri[URL]?kaletee=KALENTERITAPAHTUMA&tyyppi=Memo&&tapvm=$aikastr".url_params($kalenteri), "OFFSETX" => 350, "OFFSETY" => 40);
															
					$kale .= "	<td width='100' height = '$height' NOWRAP style='border-style: solid; border-width: 1px; background-color: $color;'>
									<div style='text-align: right;'>".tee_menu($menu, "<font class='kaleinfo'>".date("d.m.", $aikastr)."</font>")."</div>".implode("", $ulos)."
								</td>";

				}			

				$kale .= "</table>
						<td class='back'>$kalejako";
			}
			elseif(in_array($nakyma, array("RIVINAKYMA_PAIVA", "RIVINAKYMA_VIIKKO"))) {
				
				$kale .= "
				<table>";
				
				$kale .= "<caption style='margin-bottom: 5px;'>$tmenut</caption>";
				
				if(count($data["lista"]) == 0) {
					$kale .= "	<tr>
									<td width='600' align='center'><br><br><br><font class='error'>".t("Ei kalenterimerkintöjä")."</font></td>
									<td>".$kalejako;
				}
				else {
					//	Käsitellään maksimiajankohta
					$query = " SELECT DATEDIFF('".date("Y-m-d", $data["tiedot"]["loppustr"])."', '".date("Y-m-d", $data["tiedot"]["alkustr"])."') paivia";
					$abures = mysql_query($query) or pupe_error($query);
					$aburow = mysql_fetch_array($abures);
					$paivia = $aburow["paivia"];
					$kaleaika = $data["tiedot"]["alkustr"];
					
					//	Tän viikon eka päivä
					if(date("N", $kaleaika) == 1) {
						$maanantai = $kaleaika;
					}
					else {
						$maanantai = strtotime("-1 monday", $kaleaika);
					}
					
					//	varmistetaan että päätytään sunnuntaihin
					$vikapaiva = strtotime("+$paivia days", $kaleaika);
					if(date("N", $vikapaiva)!=7) {
						$vikapaiva = strtotime("sunday", $vikapaiva);
					}

					$query = " SELECT DATEDIFF('".date("Y-m-d", $vikapaiva)."', '".date("Y-m-d", $maanantai)."') paivia";
					$abures = mysql_query($query) or pupe_error($query);

					$aburow = mysql_fetch_array($abures);

					if($nakyma == "RIVINAKYMA_PAIVA") {
						$paivia = $aburow["paivia"]+1;
					}
					else {
						$paivia = ($aburow["paivia"]/7)+1;
					}
					
					//echo "<pre>data".print_r($data["lista"], true)."</pre><br>";
					
					$dataByEvent = array();
					foreach($data["lista"] as $date => $events) {
						foreach($events as $key => $event) {

							if($nakyma == "RIVINAKYMA_PAIVA") {
								$dataByEvent[$event["laji_tark"].$event["tunnus"]][$date] = $event;
							}
							else {
								
								if(date("N", $date) == 1) {
									$dataByEvent[$event["laji_tark"].$event["tunnus"]][strtotime("monday", $date)] = $event;
								}
								else {
									$dataByEvent[$event["laji_tark"].$event["tunnus"]][strtotime("-1 monday", $date)] = $event;
								}
							}
						}
					}

					//echo "<pre>dataByEvent".print_r($dataByEvent, true)."</pre><br>";
					
					//	Tehdään otsikot
					$kale .= "
					<tr><td class='back'></td>";

					if($kaletee == "printPDF") {
						//	Meidän pitää tehdä taulukko tätä varten, samalla selviää minkä kokoinen sivu pitää olla
						$pdfCellWidth = 20;
						$pageWidth = (($paivia+2) * mm_pt($pdfCellWidth)) + mm_pt(50)+100;
						$pageHeight = count($dataByEvent) * mm_pt(10) + 200;

						$pdf = new pdf;
						$pdf->addPage($pageWidth."x".$pageHeight);

						$pdf->set_default('margin-top', 	mm_pt(10));
						$pdf->set_default('margin-bottom', 	mm_pt(10));
						$pdf->set_default('margin-left', 	0);
						$pdf->set_default('margin-right',	0);

						//tehdään sarakkeet jotta piirtäminen on snadisti helpompaa
						$sarray = array_fill(0,$paivia+1, $pdfCellWidth);
						array_unshift($sarray, "50");
						$sarakkeet = $pdf->teeSarakkeet($sarray);

						//echo "leveys: $pageWidth korkeus: $pageHeight<br>";

						$h = $pdf->pt_mm($pageHeight) - 30;

					
						//tässä luodaan PDF-tiedoston "kiinteä" kentät
						
						$query = "	SELECT (
										SELECT nimi 
										FROM kuka 
										WHERE yhtio='$kukarow[yhtio]' and kuka=laskun_lisatiedot.projektipaallikko) 
									as projektipaallikko, 
									concat(lasku.nimi,' ',lasku.nimitark) as asiakasnimi, 
									concat(toim_nimi,' ',toim_nimitark) as toimitusnimi,
									concat_ws(' - ', tunnusnippu, asiakkaan_kohde.kohde) otsikko,
									kuka.nimi as myyjanimi, 
									lasku.tilausyhteyshenkilo, 
									asiakkaan_kohde.kohde kohdenimi, 
									yhteyshenkilo.nimi as yhteyshenkilo,
									lasku.sisviesti2 as kommenttivalmistukseen
									FROM lasku 
									JOIN laskun_lisatiedot ON laskun_lisatiedot.yhtio=lasku.yhtio and laskun_lisatiedot.otunnus=lasku.tunnus
								 	LEFT JOIN kuka ON kuka.yhtio=lasku.yhtio and kuka.tunnus=lasku.myyja
									LEFT JOIN asiakkaan_kohde ON asiakkaan_kohde.yhtio=lasku.yhtio and asiakkaan_kohde.tunnus=laskun_lisatiedot.asiakkaan_kohde 
									LEFT JOIN yhteyshenkilo ON yhteyshenkilo.yhtio=lasku.yhtio and laskun_lisatiedot.yhteyshenkilo_tekninen=yhteyshenkilo.tunnus
									WHERE lasku.yhtio='$kukarow[yhtio]' and lasku.tunnusnippu = '$kalenteri[tunnusnippu]'";
						$lisatiedotres = mysql_query($query) or pupe_error($query);
						$laskun_lisatiedot = mysql_fetch_array($lisatiedotres); 
						
						//korjataan liian pitkän rivin ongelma jos sivu lyhyt
						if ($pageWidth < 500) {
							$pdf->write($h, 1, 0, 0, t("PROJEKTIKALENTERI")." - ".$laskun_lisatiedot["otsikko"], "BIG", "C", "B");
							$h-=5;
							$pdf->write($h, 1, 0, 0, $laskun_lisatiedot["kohdenimi"], "BIG", "C", "B");
						}
						else {
							$pdf->write($h, 1, 0, 0, t("PROJEKTIKALENTERI")." - ".$laskun_lisatiedot["otsikko"], "BIG", "C", "B");
						}
					
						$h-=6;
						
						$pdf->write($h, 1, 0, 26, t("Projektin kesto").":", "snadi", "L", "");
						$pdf->write($h, 1, 26, 0, date('d.m.Y',$data["tiedot"]["alkustr"])." - ".date('d.m.Y',$data["tiedot"]["loppustr"]), "snadi", "L", "");
						$h-=4;
						$pdf->write($h, 1, 0, 26, t("Projektipäällikkö").":", "snadi", "L", "");
						$pdf->write($h, 1, 26, 0, $laskun_lisatiedot["projektipaallikko"], "snadi", "L", "");
						$h-=4;
						$pdf->write($h, 1, 0, 26, t("Myyjä").":", "snadi", "L", "");
						$pdf->write($h, 1, 26, 0, $laskun_lisatiedot["myyjanimi"], "snadi", "L", "");
						$h-=4;
						$pdf->write($h, 1, 0, 26, t("Asiakas").":", "snadi", "L", "");
						$pdf->write($h, 1, 26, 0, $laskun_lisatiedot["asiakasnimi"], "snadi", "L", "");
						$h-=4;
						if($laskun_lisatiedot["asiakasnimi"] != $laskun_lisatiedot["toimitusnimi"]) {
							$pdf->write($h, 1, 26, 0, $laskun_lisatiedot["toimitusnimi"], "snadi", "L", "");
							$h-=4;							
						}
						$pdf->write($h, 1, 0, 26, t("Yhteyshenkilö").":", "snadi", "L", "");
						$pdf->write($h, 1, 26, 0, $laskun_lisatiedot["yhteyshenkilo"], "snadi", "L", "");
						$h-=15;
					
					
						//ja vielä tuotontoon tarkoitetut kommentit
						if ($_GET["asiakkaalle"] != 'yes') {
							$pdf->write(-10,1,1,0,t($laskun_lisatiedot["kommenttivalmistukseen"]), "snadi", "L", "");
						}
						

					
					
					}
					
					$d = 0;
					for($i=0;$i<$paivia;$i++) {

						if($nakyma == "RIVINAKYMA_PAIVA") {
							if($d == 7) $d = 0;

							switch ($d) {
								case 0:
									$day = "Ma";
									break;
								case 1:
									$day = "Ti";
									break;
								case 2:
									$day = "Ke";
									break;
								case 3:
									$day = "To";
									break;
								case 4:
									$day = "Pe";
									break;
								case 5:
									$day = "La";
									break;
								case 6:
									$day = "Su";
									break;
							}

							$kale .= "<th style = 'text-align: center; width: 60px;'>".t($day)." ".date("d.m.", strtotime("+$i days", $maanantai))."</th>";

							if($kaletee == "printPDF") {
								$pdf->write($h, 1, $sarakkeet[$i], $sarakkeet[($i+1)], t($day)." ".date("d.m.", strtotime("+$i days", $maanantai)), "norm", "C", "");
							}

							$d++;
						}
						else {
							$kale .= "<th style = 'text-align: center; width: 25px;'>".date("W", strtotime("+$i weeks", $maanantai))."</th>";

							if($kaletee == "printPDF") {
								$pdf->write($h, 1, $sarakkeet[$i], $sarakkeet[$i+1], date("W", strtotime("+$i weeks", $maanantai)), "norm", "C", "");
							}

						}
					}

					if($kaletee == "printPDF") {
						$h-=$pdf->ln(0.5);
					}

					$kale .= "	</tr>";	

					$leveys = $paivia;
					$height = 15;

					foreach($dataByEvent as $event) {

						$e = &current($event);

						$lisatiedot = hae_lisatiedot(current($event), 0);
						
						if($e["laji_tark"] == "kalenteri") {
							$kale .= "<tr class='aktiivi'><td NOWRAP>$lisatiedot[viesti], $lisatiedot[kuka_nimi]</td>";

							if($kaletee == "printPDF") {
								$h-=$pdf->ln();
								$pdf->write($h, 1, 0, $sarakkeet[0], "$lisatiedot[otsikko], $lisatiedot[kuka_nimi]", "norm", "L", "");
							}
						}
						else {
							$kale .= "<tr class='aktiivi'><th NOWRAP>$lisatiedot[viesti]</th>";

							if($kaletee == "printPDF") {
								$h-=$pdf->ln();
								$pdf->write($h, 1, 0, $sarakkeet[0], $lisatiedot["otsikko"], "norm", "L", "");
							}
						}

						$tcolor = array();

						for($i=0;$i<$paivia;$i++) {

							if($nakyma == "RIVINAKYMA_PAIVA") {
								$aikastr = strtotime("+$i days", $maanantai);
							}
							else {
								$aikastr = strtotime("+$i weeks", $maanantai);
							}

							if(date("Ymd", $aikastr) == date("Ymd")) {
								$color = "rgba(252,245,22,0.5)";
							}
							else {
								$color = "inherit";
							}

							$stylelisa = "";
							$ulos = "";	
							if(is_array($event[$aikastr])) {
								
								$row = $event[$aikastr];
								//jos väriä ei ole vielä asetettu tuolle tapahtumalle, katsotaan se
								if(!isset($tcolor[$row["tunnus"]])) {
									if ($lisatiedot["tapa"] == "Freezing point") {
										$tcolor[$row["tunnus"]] = defineColor(array("laji_tark" => "Freezing point"), "PDF");
									}
									else {
										$tcolor[$row["tunnus"]] = defineColor($row);
									}
								}
								
								
								$stylelisa = "background-color: ".$tcolor[$row["tunnus"]];

								if(($nakyma == "RIVINAKYMA_PAIVA" and $row["aika"] == $aikastr) or
									($nakyma == "RIVINAKYMA_VIIKKO" and (
											(date("N", $row["aika"]) != 1 and strtotime("-1 monday", $row["aika"]) == $aikastr) or
											(date("N", $row["aika"]) == 1 and strtotime("monday", $row["aika"]) == $aikastr)
										)
									)
								) {
									$ulos = $lisatiedot["viesti"];
								}

								if($kaletee == "printPDF") {
									$param["width"] = 1; // PDF units
									$param["strokecolor"] = $pdf->get_color('black');
									if ($lisatiedot["tapa"] == "Freezing point") {
										$param["fillcolor"] = $pdf->get_color(defineColor(array("laji_tark" => "Freezing point"), "PDF"));
									}
									else {
										$param["fillcolor"] = $pdf->get_color(defineColor($row, "PDF"));
									}
									$param["mode"] = "fill+stroke";
									$pdf->drawRectangle($h, $sarakkeet[$i], ($h-$pdf->ln()), $sarakkeet[($i+1)], $param);
								}
							}
							else {
								if($kaletee == "printPDF") {
									$param["width"] = 1; // PDF units
									$param["strokecolor"] = $pdf->get_color('black');
									$param["fillcolor"] = $pdf->get_color('white');
									$param["mode"] = "fill+stroke";
									$pdf->drawRectangle($h, $sarakkeet[$i], ($h-$pdf->ln()), $sarakkeet[($i+1)], $param);
								}
								$ulos = "&nbsp;";
							}

							$kale .= "	<td height = '$height' style='background-color: $color; $stylelisa'>
											$ulos 
										</td>";
							
							//jos freezing point on määritetty, ja tulostetaan kalenteria asiakkaalle, tulostetaan myös freezing pointin selitys sivun alalaitaan
							if ($kaletee == "printPDF" and $lisatiedot["tapa"] == "Freezing point" and $_GET["asiakkaalle"] == 'yes') {
								$pdf->write(-5,1,0,0,t($lisatiedot["kentta01"]), "snadi", "L", "B");	
							}
						}

						$kale .= "</tr>";
					}
					
					//taulukon alle väriselitykset
					$h-=5;
					if ($kaletee == "printPDF") {
						$param["width"] = 1; // PDF units
						$param["mode"] = "fill";
						
						//sarake josta näitä lähdetään tulostamaan...
						$sarake = 50;
					
						//vihree
						$param["fillcolor"] = $pdf->get_color(defineColor(array("laji_tark" => "tilausprojekti"), "PDF"));
						$pdf->drawRectangle($h, $sarake, $h-3, $sarake+10,$param);
						$pdf->write($h+1, 1, $sarake+11, 0, t("Tilaus/Projekti"), "snadi", "L", "");
						//keltainen
						$param["fillcolor"] = $pdf->get_color(defineColor(array("laji_tark" => "tyomaarays"), "PDF"));
						$pdf->drawRectangle($h, $sarake+35, $h-3, $sarake+45, $param);
						$pdf->write($h+1, 1, $sarake+46, 0, t("Työmääräys"), "snadi", "L", "");

						//sininen,punanen,beige(jos vähän päiviä, listaus lyhyt -> menee sivun yli.. korjataan iffillä) 
						if ($pageWidth < 700) {
							$param["fillcolor"] = $pdf->get_color(defineColor(array("laji_tark" => "valmistus"), "PDF"));	
							$pdf->drawRectangle($h-5, $sarake, $h-8, $sarake+10, $param);
							$pdf->write($h-4, 1, $sarake+11, 0, t("Valmistus"), "snadi", "L", "");
							$param["fillcolor"] = $pdf->get_color(defineColor(array("laji_tark" => "Freezing point"), "PDF"));
							$pdf->drawRectangle($h-5, $sarake+35, $h-8, $sarake+45, $param);
							$pdf->write($h-4, 1, 96, 0, t("Freezing point"), "snadi", "L", "");
							if ($pageWidth < 500) {
								$param["fillcolor"] = $pdf->get_color(defineColor(array("laji_tark" => "projektitapahtuma"), "PDF"));
								$pdf->drawRectangle($h-11, $sarake, $h-14, $sarake+10, $param);
								$pdf->write($h-10, 1, $sarake+11, 0, t("Projektitapahtuma"), "snadi", "L", "");
							}
							else {
								$param["fillcolor"] = $pdf->get_color(defineColor(array("laji_tark" => "projektitapahtuma"), "PDF"));
								$pdf->drawRectangle($h-5, $sarake+70, $h-8, $sarake+80, $param);
								$pdf->write($h-4, 1, $sarake+81, 0, t("Projektitapahtuma"), "snadi", "L", "");
							}
							
						}
						//tilaa on vaikka muille jakaa, pistetään samaan pötköön vaan kaikki..
						else {
							$param["fillcolor"] = $pdf->get_color(defineColor(array("laji_tark" => "valmistus"), "PDF"));
							$pdf->drawRectangle($h, $sarake+70, $h-3, $sarake+80, $param);
							$pdf->write($h+1, 1, $sarake+81, 0, t("Valmistus"), "snadi", "L", "");
							$param["fillcolor"] = $pdf->get_color(defineColor(array("laji_tark" => "Freezing point"), "PDF"));
							$pdf->drawRectangle($h, $sarake+105, $h-3, $sarake+115, $param);
							$pdf->write($h+1, 1, $sarake+116, 0, t("Freezing point"), "snadi", "L", "");
							$param["fillcolor"] = $pdf->get_color(defineColor(array("laji_tark" => "projektitapahtuma"), "PDF"));
							$pdf->drawRectangle($h, $sarake+140, $h-3, $sarake+150, $param);
							$pdf->write($h+1, 1, 201, 0, t("Projektitapahtuma"), "snadi", "L", "");
							
						}
					}
					
					$menu = array();
					$menu[] = array("TEKSTI" => t("Lisää kalenteriisi"), 		"HREF" => "$kalenteri[URL]?kaletee=KALENTERITAPAHTUMA&tyyppi=kalenteri&tapvm=$aikastr".url_params($kalenteri), "OFFSETX" => 350, "OFFSETY" => 30);
					$menu[] = array("TEKSTI" => t("Lisää muistutus"), 			"HREF" => "$kalenteri[URL]?kaletee=KALENTERITAPAHTUMA&tyyppi=Muistutus&tapvm=$aikastr".url_params($kalenteri), "OFFSETX" => 350, "OFFSETY" => 40);
					$menu[] = array("TEKSTI" => t("Lisää asiakasmuistio"), 		"HREF" => "$kalenteri[URL]?kaletee=KALENTERITAPAHTUMA&tyyppi=Memo&&tapvm=$aikastr".url_params($kalenteri), "OFFSETX" => 350, "OFFSETY" => 40);

					if(in_array("projektitapahtuma", $kalenteri["kalenteri_tyypit"])) {
						$menu[] = array("TEKSTI" => t("Lisää projektitapahtuma"),			"HREF" => "$kalenteri[URL]?kaletee=KALENTERITAPAHTUMA&tyyppi=projektitapahtuma&tapvm=$kaleaika".url_params($kalenteri), "OFFSETX" => 350, "OFFSETY" => 50);
					}

					$kale .= "
							<tr>
								<td>
									".tee_menu($menu, "<font class='kaleinfo'>".t("Toiminnot")."</font>")."
								</td>
							</tr>
							</table>$kalePost
							</td>
							<td>$kalejako";

					if($kaletee == "printPDF") {
						$pdffilenimi = "/tmp/projektikalenteri.pdf";

						die($pdf->generate());
					}
				}				
			}
			elseif($nakyma == "TAPAHTUMALISTAUS") {
				
				$kale .= "	<div style='width: 750px;'>
							<div style='float: right; position: relative; left: 20px;'>$kalejako</div>
							<table width = '600' align='left'>
							<caption style='margin-bottom: 5px;'>$tmenut</caption>";
							
				
				krsort($data["lista"]);
				
				foreach($data["lista"] as $aika => $tapahtumat) {
					$rivei = 0;
					foreach($tapahtumat as $tapahtuma) {
						if($tapahtuma["aika"] == $aika) {
							$rivei++;
						}
					}
					
					if($rivei > 0) {
						
						$kale .= "
							<tr class='aktiivi'>
								<td class='back' style='width: 80px; text-align: right; vertical-align: top;' rowspan = '".($rivei*3)."'>".date("d.m.Y", $aika)."&nbsp;&nbsp;</td>
							";

						$i = 0;
						foreach($tapahtumat as $tapahtuma) {
							if($i > 0) {
								$kale .= "<tr>";
							}
							if($tapahtuma["aika"] == $aika) {
								
								$lisatiedot = hae_lisatiedot($tapahtuma);
								$viesti = $lisatiedot["viesti"];
								
								if($tapahtuma["laji"] == "kalenteri" and $tapahtuma["vali"] > 1) {
									$viesti .= "<br><br>".t("%s päättyy: %s", $kieli, $lisatiedot["tyyppi_nimi"], date("d.m.Y H:i"));
								}
								
								$extra = "";
								
								if($lisatiedot["tunnusnippu"]>0) {
									$extra = $lisatiedot["tunnusnippu"]."&nbsp;&ndash;&nbsp;";
								}
								
								$extra .= kaleotsikko($tapahtuma["laji_tark"]);
								
								if($lisatiedot["tapa"] != "") {
									$extra .= "&nbsp;&ndash;&nbsp;".kaletapaKaannos($lisatiedot["tapa"], "fi", $kukarow["kieli"]);
								}
								
								if($lisatiedot["yhteyshenkilo"] != "") {
									$extra .= "&nbsp;&ndash;&nbsp;".$lisatiedot["yhteyshenkilo"];
								}
								
								$kale .= "<th>$extra<div style='float: right;'><font class='info'>$tapahtuma[laatija]@$tapahtuma[luontiaika]</font></div></th></tr>";
								
								$kale .= "<tr><td>".nl2br($viesti)."</td></tr>";
								$kale .= "<tr><td class='back' height='10'></td></tr>";
							}
						}						
					}
				}

				$kale .= "</table>
						</div>";
			}
			elseif(in_array($nakyma, array("VIIKKONAKYMA", "PAIVANAKYMA"))) {
								
				if($nakyma == "VIIKKONAKYMA") {
					$days 	= 7;
					$w		= 100;					
					
					//	Tän viikon eka päivä
					if(date("N", $kaleaika) == 1) {
						$maanantai = $kaleaika;
					}
					else {
						$maanantai = strtotime("-1 monday", $kaleaika);
					}
					
					$seuraava 	= strtotime("+1 weeks", $maanantai);
					$edellinen 	= strtotime("-1 weeks", $maanantai);
					
					$paivat 	= array();
					for($i=0;$i<7;$i++) {
						$paivat[$i] = strtotime("+$i day", $maanantai);
					}
					
					$otsikko = t("Viikko").": ".(int) date("W", $maanantai)." - ".date("Y", $maanantai);
				}
				else {
					$days 	= 1;
					$w		= 600;					
				
					$seuraava 	= strtotime("+1 days", $kaleaika);
					$edellinen 	= strtotime("-1 days", $kaleaika);	
					$paivat		= array($kaleaika);
					
					$otsikko 	= date("d.m.Y", $kaleaika);
				}
				
				//echo "<pre>kalenteri".print_r($kalenteri, true)."</pre><br>".url_params($kalenteri);
				
				$kale .= "
					<table width='".(($days*$w)-40)."' cellpadding='1' cellspacing='10'>
					<caption style='margin-bottom: 5px;'><div style='text-align: left; float: left; margin-left: 20px'><a href=\"javascript:sndReq('$kalenteri[div]', '$kalenteri[URL]?ohje=off&kaleaika=$edellinen".url_params($kalenteri)."', '$kalenteri[div]', false);\">&lt;&lt;&nbsp;</a>&nbsp;&nbsp;$otsikko&nbsp;&nbsp;&nbsp;<a href=\"javascript:sndReq('$kalenteri[div]', '$kalenteri[URL]?ohje=off&kaleaika=$seuraava".url_params($kalenteri)."', '$kalenteri[div]', false);\">&gt;&gt;</div></a>$tmenut</caption>
					<tr>
						<th width='40'>".t("Klo")."</th>";
					
				$paivanimi=array("ma", "ti", "ke", "to", "pe", "la", "su");
				for($i=0;$i<$days;$i++) {
					$kale .= "
					<th width='$w'>".t($paivanimi[$i])." <div style='float: right;'><font class='kaleinfo'>".date("d.m.", $paivat[$i])."</font></div></th>";					
				}
				$kale .= "
				</tr>";
			
				// 	Puretaan ensin kaikki muistutukset ja memot, samalla voidaankin tarkastaa montako tapahtumaa on yhdellä päiväll
				$kale .= "
				<tr>
					<td class='back' align = 'left'><font class='info'>".t("Merkinnät")."</font></td>";
				
				$tpos=array();
				$tcolor = array();
				
				//	Haetaan tän päivän kaikki muistutukset ja memot!
				for($i=0;$i<$days;$i++) {
					$ulos = "";
					if (is_array($data["lista"][$paivat[$i]])) {

						foreach($data["lista"][$paivat[$i]] as $row) {
							if(in_array($row["laji"], array("kalenteri"))) {
								
								if(in_array($row["laji_tark"], array("Muistutus", "Memo"))) {
									if($ulos != "") {
										$ulos .= "<hr>";
									}
									$lisatiedot = hae_lisatiedot($row);
									list($viesti, $poppari) = lyhytviesti($lisatiedot["viesti"], 20);
								
									$viesti = $poppari = "";
									if($nakyma == "VIIKKONAKYMA") {
										list($viesti, $poppari) = lyhytviesti($lisatiedot["viesti"], 40);
									
										if($poppari != "") {
											$ulos .= "<div $poppari>$viesti</div>";
										}
										else {
											$ulos .= $viesti;
										}
									}
									else {
										$ulos .= $lisatiedot["viesti"];
									}
								}
								else {
									$tpos[$i][$row["tunnus"]] = (int) count($tpos[$i])+1;
								}
							}
						}
					}
					
					$kale .= "
						<td width='$w' height = '25' NOWRAP style='border-style: solid; border-width: 0px;'>$ulos</td>";
				}
				$kale .= "<tr>";


				// 	Puretaan kaikki toimitukset yms
				$kale .= "
				<tr>
					<td class='back' align = 'left'><font class='info'>".t("Toimitukset")."</font></td>";
				
				for($i=0;$i<$days;$i++) {
					$ulos = "";
					
					if (is_array($data["lista"][$paivat[$i]])) {
						foreach($data["lista"][$paivat[$i]] as $row) {
							if(in_array($row["laji"], array("lasku"))) {
								if($ulos .= "") {
									$ulos .= "<hr>";
								}
								$lisatiedot = hae_lisatiedot($row);

								$ulos .= $lisatiedot["viesti"];
							}
						}
					}
					$kale .= "
						<td width='$w' height = '25' NOWRAP style='border-style: solid; border-width: 0px;'>$ulos</td>";
				}
				$kale .= "<tr>";
			
				$h = 07;
				$m = 00;
				
				//	loopataan klo 21 meidän kalenteri!
				while($h<18) {
					
					$h = sprintf("%02d", $h);
					$m = sprintf("%02d", $m);
					
					$kale .= "
							<tr>
								<td class='back' align = 'left'><font class='kaleinfo'>$h:$m</font></td>";
					
					
					for($i=0;$i<$days;$i++) {
						
						//	Tämän hetken kellonaika
						$aikastr = strtotime("+$h hours +$m minutes", $paivat[$i]);
						$edaikastr = strtotime("+30 minutes", $aikastr);
						
						$kale .= "<td width='$w' height = '10' NOWRAP style='border-style: solid; border-width: 0px; background-color: $color;'>";
						
						if(is_array($data["lista"][$paivat[$i]])) {
							$ulos = "";
							foreach($data["lista"][$paivat[$i]] as $row) {
								if(!in_array($row["laji_tark"], array("Memo", "Muistutus"))) {
									$lisatiedot = hae_lisatiedot($row);
									if($lisatiedot["pvmalku"] < $edaikastr and $lisatiedot["pvmloppu"] >= $edaikastr) {
										
										if(!isset($tcolor[$row["tunnus"]])) {
											$tcolor[$row["tunnus"]] = color_code(count($tcolor)*13+55, 100);
											
											$viesti = $poppari = "";
											if($nakyma == "VIIKKONAKYMA") {
												$viesti = $lisatiedot["viesti"];
												lyhytviesti($viesti, 40);
											}
											else {
												$viesti = $lisatiedot["viesti"];
												lyhytviesti($viesti, 100);
											}

											$ulos .= "<div style='width: ".@($w/count($tpos[$i]))."; float: left; text-align: left; background-color: ".$tcolor[$row["tunnus"]].";' $poppari>$viesti</div>";
										}
										else {
											$ulos .= "<div style='width: ".@($w/count($tpos[$i]))."; float: left; text-align: left; background-color: ".$tcolor[$row["tunnus"]].";'>&nbsp;</div>";
										}
									}
									else {
										$ulos .= "<div style='width: ".@($w/count($tpos[$i]))."; height: 1px; float: left; background-color: inherit;'></div>";
									}
								}
							}
						}
						else {
							$ulos = "";
						}
												
						$menu = array();
						$menu[] = array("TEKSTI" => t("Lisää kalenteriin"), 		"HREF" => "$kalenteri[URL]?kaletee=KALENTERITAPAHTUMA&tyyppi=kalenteri&tapvm=$aikastr".url_params($kalenteri), 	"OFFSETX" => 350, "OFFSETY" => 30);
						$menu[] = array("TEKSTI" => t("Lisää muistutus"), 			"HREF" => "$kalenteri[URL]?kaletee=KALENTERITAPAHTUMA&tyyppi=Muistutus&tapvm=$aikastr".url_params($kalenteri), 	"OFFSETX" => 350, "OFFSETY" => 40);
						$menu[] = array("TEKSTI" => t("Lisää asiakasmuistio"), 		"HREF" => "$kalenteri[URL]?kaletee=KALENTERITAPAHTUMA&tyyppi=Memo&tapvm=$aikastr".url_params($kalenteri), 		"OFFSETX" => 350, "OFFSETY" => 50);

						$kale .= "<div style='width: {$w}px; text-align: right; z-index: 1;'>".tee_menu($menu, "<font class='kaleinfo'>".t("Lisää")."</font>")."</div>$ulos";
						
						$kale .= "</td>";
					}			
					
					$m += 30;
					if($m == 60) {
						$m = 00;
						$h++;
					}
				}

				$kale .= "	</table>
							</td>
							<td>
							$kalejako";
			}
			else {
				echo "Näkymää '$nakyma' ei vielä osata!";
			}
						
			$kale .="</td></tr></table>";
			
			return $kale;
		}
	}	
	
	if($kaletee == "GENEROIMENU") {
		die(generoiMenu($menu)."</body></html>");
	}
	
	if($kaletee == "ANNAKUITTAUS") {
		$kalenteri = muistista("kalenteri", "kalenteri");
		if(!is_array($kalenteri)) {
			die("OHO! Kalenterin meni rikki!");
		}
		
		$query = "	SELECT kentta01
					FROM kalenteri
					WHERE yhtio='$kukarow[yhtio]' and tunnus='$ktunnus'";
		$result = mysql_query($query) or pupe_error($query);
		$row = mysql_fetch_array($result);
		
		echo "
			<table width='400'>
				<tr>
					<caption>".t("Muistutus")."</caption>
				</tr>
				<tr>
					<td><font class='message'>{$row["kentta01"]}</font></td>
				</tr>
			</table>
			<br>
			<br>
			<form id = 'kuittausForm' name = 'kuittaus' onSubmit=\"ajaxPost('kuittausForm', '$kalenteri[URL]?' , 'ajax_menu'); return false;\">
			<input type = 'hidden' name='kaletee' value='KUITTAA'>
			<input type = 'hidden' name='ktunnus' value='$ktunnus'>
			<table width='400'>
				<tr>
					<th>".t("Anna kuittaus")."</th><td class='back'></td>
				</tr>
				<tr>
					<td><input type = 'text' name='kuittaus' size='50'></td>
					<td class='back'><input type = 'submit' value='".t("Kuittaa")."' onclick = \"ajaxPost('kuittausForm', '$kalenteri[URL]?' , 'ajax_menu'); return false;\"></td>
				</tr>
			</table>
			</form>";
		
		die("</body></html>");
	}

	if($kaletee == "MUOKKAAKERAYSPAIVAA") {
		$kalenteri = muistista("kalenteri", "kalenteri");
		if(!is_array($kalenteri)) {
			die("OHO! Kalenterin meni rikki!");
		}
		
		$query = "	SELECT date(toimaika) toimaika, date(kerayspvm) kerayspvm
					FROM lasku
					WHERE yhtio='$kukarow[yhtio]' and tunnus='$otunnus'";
		$result = mysql_query($query) or pupe_error($query);
		$row = mysql_fetch_array($result);
		list($toimvv, $toimkk, $toimpp) = explode("-", $row["toimaika"]);
		list($kervv, $kerkk, $kerpp) = explode("-", $row["kerayspvm"]);
		echo "
			<form id = 'aikaForm' name = 'aikaForm' onSubmit=\"ajaxPost('aikaForm', '$kalenteri[URL]?ohje=off&".url_params($kalenteri)."' , 'ajax_menu'); return false;\">
			<input type = 'hidden' name='kaletee' value='PAIVITAOTSIKKO'>
			<input type = 'hidden' name='otunnus' value='$otunnus'>
			<table width='400'>
				<tr>
					<th>".t("Keräyspäivä")."</th><th>".t("Toimitusaika")."</th><td class='back'></td>
				</tr>
				<tr>
					<td><input type = 'text' name='kerpp' value='$kerpp' size='3'> <input type = 'text' name='kerkk' value='$kerkk' size='3'> <input type = 'text' name='kervv' value='$kervv' size='5'></td>
					<td><input type = 'text' name='toimpp' value='$toimpp' size='3'> <input type = 'text' name='toimkk' value='$toimkk' size='3'> <input type = 'text' name='toimvv' value='$toimvv' size='5'></td>
					<td class='back'><input type = 'submit' value='".t("Päivitä")."' onclick = \"ajaxPost('kuittausForm', '$kalenteri[URL]?' , 'ajax_menu'); return false;\"></td>
				</tr>
			</table>
			</form>";
		
		die("</body></html>");
	}

	if($kaletee == "PAIVITAOTSIKKO") {
		$kalenteri = muistista("kalenteri", "kalenteri");
		if(!is_array($kalenteri)) {
			die("OHO! Kalenterin meni rikki!");
		}
		
		//	Päivitetään keräysaika
		if($kerpp != "" and $kerkk != "" and $kervv != "") {
			$kerpp = sprintf("%02s", $kerpp);
			$kerkk = sprintf("%02s", $kerkk);
			$kervv = sprintf("%04s", $kervv);
			
			$query = "	UPDATE lasku
						SET kerayspvm = '".$kervv."-".$kerkk."-".$kerpp."'
						WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$otunnus'";
			$result = mysql_query($query) or pupe_error($query);

			$query = "	UPDATE tilausrivi
						SET kerayspvm = '".$kervv."-".$kerkk."-".$kerpp."'
						WHERE yhtio = '$kukarow[yhtio]' and otunnus = '$otunnus'";
			$result = mysql_query($query) or pupe_error($query);
		}
		//	Päivitetään toimitusaika
		if($toimpp != "" and $toimkk != "" and $toimvv != "") {
			$toimpp = sprintf("%02s", $toimpp);
			$toimkk = sprintf("%02s", $toimkk);
			$toimvv = sprintf("%04s", $toimvv);
			
			$query = "	UPDATE lasku
						SET toimaika = '".$toimvv."-".$toimkk."-".$toimpp."'
						WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$otunnus'";
			$result = mysql_query($query) or pupe_error($query);
			
			$query = "	UPDATE tilausrivi
						SET toimaika = '".$toimvv."-".$toimkk."-".$toimpp."'
						WHERE yhtio = '$kukarow[yhtio]' and otunnus = '$otunnus'";
			$result = mysql_query($query) or pupe_error($query);
		}
				
		die("<script LANGUAGE='JavaScript'>sndReq('$kalenteri[div]', '$kalenteri[URL]?ohje=off&".url_params($kalenteri)."', false, false); pop=document.getElementById('ajax_menu'); pop.innerHTML=''; pop.style.display='none';</script></body></html>");
		
	}
	
	
	if($kaletee == "KUITTAA") {
		$kalenteri = muistista("kalenteri", "kalenteri");
		if(!is_array($kalenteri)) {
			die("OHO! Kalenterin meni rikki!");
		}
		
		$query = "	UPDATE kalenteri SET 
						kuittaus = '$kuittaus'
					WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$ktunnus'";
		$updres = mysql_query($query) or pupe_error($query);

		die("<script LANGUAGE='JavaScript'>sndReq('$kalenteri[div]', '$kalenteri[URL]?ohje=off&".url_params($kalenteri)."', false, false); pop=document.getElementById('ajax_menu'); pop.innerHTML=''; pop.style.display='none';</script></body></html>");
	}
	
	//	Skipataan aina takaisin kalenteritapahtumaan
	if($kaletee == "TALLENNA_KALENTERITAPAHTUMA" and $from == "asiakasvalinta") {
		$kaletee = "KALENTERITAPAHTUMA";
	}
	
	if($kaletee == "TALLENNA_KALENTERITAPAHTUMA") {
		$kalenteri = muistista("kalenteri", "kalenteri");
		if(!is_array($kalenteri)) {
			die("OHO! Kalenterin meni rikki!");
		}

		/*	
			Testataaan voidaanko tallentaa
		*/
		
		$query = "select * from asiakas where yhtio='$kukarow[yhtio]' and tunnus='$liitostunnus'";
		$result = mysql_query($query) or pupe_error($query);

		$asrow = mysql_fetch_array($result);

		$kieli = $asrow["kieli"];
		
		$ok = 1;
		
		//	Näille vaaditaan asiakasid
		if($liitostunnus== 0 and in_array($tyyppi, array("Memo"))) {
			echo "<font class='error'>".t("$tyyppi vaatii aina asiakkaan!")."<br></font>";
			$ok = 0;
		}
		
		if($tyyppi == "Muistutus" and $kuka == "") {
			echo "<font class='error'>".t("Muistutuksella pitää olla 'Kuka'")."!<br></font>";
			$ok = 0;			
		}

		//	Viestin on aina oltava
		if($viesti== "") {
			echo "<font class='error'>viesti puuttuu!</font><br>";
			$ok = 0;			
		}	
		
		//	Tavasta tallennetaankin ihan koko rimpsu..
		$query = "	SELECT selitetark
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and avainsana.laji = 'KALETAPA' and selite = '$tapa' and kieli IN ('fi','')";
		$tapares = mysql_query($query) or pupe_error($query);
		if(mysql_num_rows($tapares) == 1) {
			$taparow = mysql_fetch_array($tapares);
			$tapa_string = $taparow["selitetark"];
		}
		else {
			echo "<font class='error'>".t("Yhteydenottotapa katosi!")."</font><br>";
			$ok = 0;
		}
		
		if($tapa == "yhteyshenkilon_email") {
			if($mail_subject == "") {
				echo "<font class='error'>".t("Anna sähköpostille otsikko.")."</font><br>";
				$ok = 0;
			}
			else {
				$query = "	SELECT email
							FROM yhteyshenkilo
							WHERE yhtio = '$kukarow[yhtio]' and liitostunnus = '$liitostunnus' and tyyppi = 'A' and tunnus = '$yhteyshenkilo' and email != ''";
				$yhres = mysql_query($query) or pupe_error($query);
				if(mysql_num_rows($yhres) == 1) {
					$yhrow = mysql_fetch_array($yhres);
					$to = $yhrow["email"];
					
					$allekirjoitus = "\n\n".t("Parhain terveisin", $kieli)."\n$kukarow[nimi]\nmobile: $kukarow[puhno]\nemail: $kukarow[eposti]";
					
					if(mail($to, "=?iso-8859-1?Q?".imap_8bit($mail_subject)."?=", $viesti.$allekirjoitus, $header, " -f {$kukarow["eposti"]}")) {
						echo "<font class='message'>".t("Sähköpostin lähetettiin yhteyshenkilölle osoitteeseen %s", $kieli, $to)."</font><br>";
					}
					else {
						echo "<font class='error'>".t("Sähköpostin lähetys yhteyshenkilölle epäonnistui")."</font><br>";
					}
					
					if(mail($kukarow["eposti"], "=?iso-8859-1?Q?".imap_8bit($mail_subject)."?=", "Viesti lähetetty osoitteeseen $to\n\n".$viesti.$allekirjoitus, $header, " -f {$kukarow["eposti"]}")) {
						echo "<font class='message'>".t("Sähköpostin kopio lähetettiin sähköpostiisi", $kieli)."</font><br>";
					}
					else {
						echo "<font class='error'>".t("Sähköpostin lähetys epäonnistui")."</font><br>";
					}
				}
				else {
					echo "<font class='error'>".t("Yhteyshenkilöä ei ollut valittu tai sillä ei ollut sähköpostiosoitetta!")."</font><br>";
					$ok = 0;
				}
			}
		}
		
		//	Siirretään kalenteriin
		if($kalenteriin != "") {
			$kentta10 = "M";	
		}		
				
		if($ok == 1) {
			
			$tapvm = $lopvm = "";
			if($tapp != "" and $takk != "" and $tavv != "") {
				$tapvm = "$tavv-$takk-$tapp";
				if($tahh != "" and $tamm != "") {
					$tapvm .= " $tahh:$tamm:00";
				}
			}

			if($lopp != "" and $lokk != "" and $lovv != "") {
				$lopvm = "$lovv-$lokk-$lopp";
				if($lohh != "" and $lomm != "") {
					$lopvm .= " $lohh:$lomm:00";
				}
			}
			else {
				$lopvm = "";
			}
			
			if($ktunnus > 0) {
				$query = "	UPDATE kalenteri SET ";
				$where = " WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$ktunnus'";
			}
			else {
				$query = "	INSERT INTO kalenteri SET
								yhtio 		= '{$kukarow["yhtio"]}',
								luontiaika	= now(),
								laatija 	= '{$kukarow["kuka"]}',";
				$where = "";
			}

			if($pvm != "") $pvm = "'$pvm'";
			else $pvm = "now()";

			if($kuka == "") $kuka = $kukarow["kuka"];

			//Tehdään asiakasmemotapahtuma jos se on tarpeellinen
			$kysely = "	tapa 			= '$tapa_string',
						asiakas  	 	= '$asrow[ytunnus]',
						liitostunnus	= '$liitostunnus',
						henkilo  		= '$yhteyshenkilo',
						kuka     		= '$kuka',
						tyyppi   		= '$tyyppi',
						pvmalku  		= '$tapvm',
						pvmloppu  		= '$lopvm',
						otunnus  		= '$otunnus',
						kuittaus  		= '$kuittaus',
						kentta01 		= '$viesti',
						kentta09 		= '$tapatarkenne',
						kentta10 		= '$kentta10'";
			$query .= $kysely.$where;
			$result = mysql_query($query) or pupe_error($query);

			//die($query);
			if(count($jakelu)) {
				
				//	Otetaan listasta emailosoitteet pois..
				unset($osoitteet);
				unset($kayttajat);
				foreach($jakelu as $j) {
					if(strpos($j, "@")) {
						$osoitteet[] = array(	"email" => $j);
					}
					else {
						$kayttajat[] = $j;
					}
				}
				
				if(isset($kayttajat)) {
					//	Haetaan kaikki listata
					$query = "	SELECT nimi, eposti
								FROM kuka
								WHERE yhtio = '$kukarow[yhtio]' and kuka IN ('".str_replace(",","','", implode(",", $kayttajat))."') and eposti != ''";
					$jres = mysql_query($query) or pupe_error($query);
					if(mysql_num_rows($jres)>0) {
						while($jrow = mysql_fetch_array($jres)) {
							$osoitteet[] = array(	"email" => $jrow["eposti"],
													"nimi" => $jrow["nimi"]);
						}
					}
				}
				
				if(count($osoitteet) > 0) {
					
					//	Muodostetaan TO
					$to = "";
					
					foreach($osoitteet as $o) {
						if($to != "") $to .= ", ";
						if($o["nimi"] == "") {
							$to .= "{$o["email"]}";
						}
						else {
							$to .= "=?iso-8859-1?Q?".imap_8bit($o["nimi"])."?= <{$o["email"]}>";
						}
					}
					
					//	melkosta..
					$header  = "From: <{$kukarow["eposti"]}>\n";
					$header .= "MIME-Version: 1.0\n" ;
					$content .= "Content-Type: text/plain; charset=\"iso-8859-1\"\n";
					
					if($tyyppi == "kalenteri") {
						$subject = t("Kalenteritapahtuma");
					}
					else {
						$subject = t($tyyppi);
					}

					if($otunnus > 0) {
						$query = "	SELECT nimi, nimitark, lasku.tunnusnippu, asiakkaan_kohde.kohde, laskun_lisatiedot.seuranta
									FROM lasku
									LEFT JOIN laskun_lisatiedot ON laskun_lisatiedot.yhtio=lasku.yhtio and laskun_lisatiedot.otunnus=lasku.tunnus
									LEFT JOIN asiakkaan_kohde ON asiakkaan_kohde.yhtio=laskun_lisatiedot.yhtio and asiakkaan_kohde.tunnus=laskun_lisatiedot.asiakkaan_kohde
									WHERE lasku.yhtio='$kukarow[yhtio]' and lasku.tunnus='$otunnus'";
						$kres = mysql_query($query) or pupe_error($query);
						if(mysql_num_rows($kres) > 0) {
							$krow = mysql_fetch_array($kres);

							$subject .= " / {$krow["tunnusnippu"]} {$krow["seuranta"]} - {$krow["nimi"]} {$krow["nimitark"]}";
							if($krow["kohde"] != "") {
								$subject .= " - {$krow["kohde"]}";
							}
						}
					}
					elseif($liitostunnus > 0){
						$query = "	SELECT nimi, nimitark
									FROM asiakas
									WHERE yhtio='$kukarow[yhtio]' and tunnus='$liitostunnus'";
						$kres = mysql_query($query) or pupe_error($query);
						if(mysql_num_rows($kres) > 0) {
							$krow = mysql_fetch_array($kres);

							$subject .= " / {$krow["nimi"]}";
						}
					}
					
					$subject .= " / ".t("Käyttäjältä")." {$kukarow["nimi"]} {$kukarow["nimitark"]}";
					
					if($tyyppi == "kalenteri") {
						$msg = "Tyyppi: ".t("Kalenteritapahtuma")."\n";
						$msg .= "Tapa: $tapa_string\n";
						
					}
					else {
						$msg = "Tyyppi: ".t($tyyppi)."\n";
					}
					
					
					if($henkilo > 0) {
						$query = "	SELECT *
									FROM yhteyshenkilo
									WHERE yhtio='$kukarow[yhtio]' and tyyppi = 'A' and tunnus = '$henkilo'";
						$kres = mysql_query($query) or pupe_error($query);
						if(mysql_num_rows($kres) > 0) {
							$krow = mysql_fetch_array($kres);
							$msg .= "Yhteyshenkilö: {$krow["nimi"]}\n";
						}
					}
					
					$msg .= "Tapahtuma-aika: ";
					$msg .= "$tapp.$takk.$tavv";
					if($tahh != "" and $tamm != "") {
						$msg .= " klo. $tahh:$tamm";
					}
					if($lopp != "" and $lokk != "" and $lovv != "") {
						$msg .= " - $lopp.$lokk.$lovv";
						if($lohh != "" and $lomm != "") {
							$msg .= " klo. $lohh:$lomm";
						}
					}
					$msg .=  "\n";
					
					$query = "	SELECT *
								FROM lasku
								WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$otunnus'";
					$abures = mysql_query($query) or pupe_error($query);
					$aburow = mysql_fetch_array($abures);

					$msg .= "\n".str_replace("\\n", "\n", $viesti)."\n";
					if(mail($to, "=?iso-8859-1?Q?".imap_8bit($subject)."?=", $msg, $header, " -f {$kukarow["eposti"]}")) {
						if(mysql_num_rows($jres) > 1) {
							echo "<font class='message'>".t("Sähköpostin lähetettiin käyttäjille %s", $kieli, $jrow["nimi"])."</font>";
						}
						else {
							echo "<font class='message'>".t("Sähköpostin lähetettiin käyttäjälle %s", $kieli, $jrow["nimi"])."</font>";
						}
					}
					else {
						echo "<font class='error'>".t("Sähköpostin lähetys epäonnistui")."</font>";
					}
				}				
			}

			die("<script LANGUAGE='JavaScript'>sndReq('$kalenteri[div]', '$kalenteri[URL]?ohje=off&".url_params($kalenteri)."', false, false); document.body.removeChild(document.getElementById('ajax_menu'))</script></body></html>");
		}
		else {
			$kaletee = "KALENTERITAPAHTUMA";
		}
	}

	if($kaletee == "POISTA_KALENTERITAPAHTUMA") {
		$kalenteri = muistista("kalenteri", "kalenteri");
		if(!is_array($kalenteri)) {
			die("OHO! Kalenterin meni rikki!");
		}
		
		$query = " DELETE FROM kalenteri WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$ktunnus'";
		$updres = mysql_query($query) or pupe_error($query);	
		
		die("<script LANGUAGE='JavaScript'>sndReq('$kalenteri[div]', '$kalenteri[URL]?ohje=off&".url_params($kalenteri)."', false, false); pop=document.getElementById('ajax_menu'); pop.innerHTML=''; pop.style.display='none';</script></body></html>");
	}

	if($kaletee == "KALENTERITAPAHTUMA") {
		
		$kalenteri = muistista("kalenteri", "kalenteri");
		if(!is_array($kalenteri)) {
			die("OHO! Kalenterin meni rikki!");
		}

		//echo "data:<pre>".print_r($_REQUEST, true)."</pre>";
		
		if($ktunnus > 0) {
			$muokkaus = 1;
			$query = "	SELECT *, UNIX_TIMESTAMP(pvmalku) pvmalku, UNIX_TIMESTAMP(pvmloppu) pvmloppu
						FROM kalenteri
						WHERE yhtio  = '$kukarow[yhtio]' and tunnus = '$ktunnus'";
			$result = mysql_query($query) or pupe_error($query);
			if(mysql_num_rows($result) > 0) {
				$krow 			= mysql_fetch_array($result);
				
				$viesti	 		= stripslashes($krow["kentta01"]);
				$liitostunnus 	= $krow["liitostunnus"];
				$otunnus 		= $krow["otunnus"];
				$tapvm 			= $krow["pvmalku"];
				$lopvm 			= $krow["pvmloppu"];			
				$kuka 			= $krow["kuka"];
				$otunnus 		= $krow["otunnus"];
				$yhteyshenkilo 	= $krow["henkilo"];
				$tapa 			= $krow["tapa"];
				$tapatarkenne	= $krow["kentta09"];
				$kentta10		= $krow["kentta10"];
				
				$query = "	SELECT count(*)
							FROM liitetiedostot
							WHERE liitostunnus='$ktunnus' AND liitos='kalenteri'";
				$abures = mysql_query($query) or pupe_error($query);
				$abur = mysql_fetch_array($abures);
				if($abur[0] > 0) {
					$llisa = "({$abur[0]})";
				}
				$poistanappi 	= "<a href='#' onclick=\"sndReq('$kalenteri[div]', '$kalenteri[URL]?ohje=off&kaletee=POISTA_KALENTERITAPAHTUMA&ktunnus=$ktunnus".url_params($kalenteri)."', false, false); pop=document.getElementById('ajax_menu'); pop.innerHTML=''; pop.style.display='none'; return false;\">".("Poista")."</a>";
				$liitanappi 	= "<a href='#' onclick=\"sndReq('ajax_menu', '$kalenteri[URL]?ohje=off&kaletee=LISAA_LIITE&ktunnus=$ktunnus".url_params($kalenteri)."', false, false); return false;\">".t("Liitteet")." $llisa</a>";
			}
		}
		else {
			$muokkaus	= 0;
			$poistanappi = "";
		}
		
		if($tarjous > 0 or $otunnus>0) {
			if($otunnus > 0) {
				$ttunnus = $otunnus;
			}
			else {
				$ttunnus = $tarjous;
			}
			
			$query = "	SELECT liitostunnus
						FROM lasku
						WHERE yhtio='$kukarow[yhtio]' and tunnus='$ttunnus'";
			$result = mysql_query($query) or pupe_error($query);
			$aburow = mysql_fetch_array($result);
			$liitostunnus = $aburow["liitostunnus"];
		}
		
		$order = "DESC";		
		if($kalenteri["laskutilat"] != "") {
			
			$vquery="select count(*) from lasku l where l.yhtio=lasku.yhtio and l.tunnusnippu=lasku.tunnusnippu and l.tunnus<=lasku.tunnus and l.tila='T'";
			$query = "";
			//	Jos tullaan tilaushakukoneesta..
			if($tarjous > 0) {
				//	Valitaan joku toimitus
				
				$query = "	SELECT tunnus, tila, alatila, liitostunnus, if(lasku.tila='T' and lasku.tunnusnippu>0, concat(lasku.tunnusnippu,'/',($vquery)), tunnus) nakyvatunnus
							FROM lasku
							WHERE yhtio  = '$kukarow[yhtio]' and tunnusnippu = $tarjous and tila IN ($kalenteri[laskutilat])
							ORDER BY tunnus $order";
			}
			elseif($otunnus > 0) {
				$query = "	SELECT tunnus, tila, alatila, liitostunnus, if(lasku.tila='T' and lasku.tunnusnippu>0, concat(lasku.tunnusnippu,'/',($vquery)), tunnus) nakyvatunnus
							FROM lasku
							WHERE yhtio  = '$kukarow[yhtio]' and tunnus = $otunnus
							ORDER BY tunnus $order";			
			}
			elseif($liitostunnus > 0) {
				//	Valitaan joku asiakkaan tilaus
				$query = "	SELECT tunnus, tila, alatila, if(lasku.tila='T' and lasku.tunnusnippu>0, concat(lasku.tunnusnippu,'/',($vquery)), tunnus) nakyvatunnus
							FROM lasku
							WHERE yhtio  = '$kukarow[yhtio]' and liitostunnus = $liitostunnus and tunnusnippu=tunnus and tila IN ($kalenteri[laskutilat]) and alatila != 'X'
							ORDER BY tunnus $order";										
			}

			if($query!="") {
				$result = mysql_query($query) or pupe_error($query);

				if($otunnus > 0) {
					$row = mysql_fetch_array($result);
					$laskutyyppi = $row["tila"];
					$alatila	 = $row["alatila"];
					require "inc/laskutyyppi.inc";

					$oval .= "<input type='hidden' name='otunnus' value='$row[tunnus]'>$row[nakyvatunnus] - $laskutyyppi $alatila</option>";
					
				}
				elseif(mysql_num_rows($result) > 0) {
					$i = mysql_num_rows($result);
					$oval = "<select name='otunnus' style='width:200px;'>";
					
					//	Meillä on ollut 
					if($tarjous == 0) {
						$oval .= "<option value=''>".t("Valitse tilaus/tarjous jota muistio koskee")."</option>";
					}
					
					while($row = mysql_fetch_array($result)) {
						$laskutyyppi = $row["tila"];
						$alatila	 = $row["alatila"];
						require "inc/laskutyyppi.inc";

						$oval .= "<option value='$row[tunnus]'>$row[nakyvatunnus] - ".t($laskutyyppi)." ".t($alatila)."</option>";

						if($tarjous == $row["tunnus"]) {
							$liitostunnus = $row["liitostunnus"];
						}
					}
					$oval .= "</select>";
				}
				else {
					$oval .= t("Sopivia toimituksia ei ole");
				}				
			}
			else {
				$oval .= t("Valitse ensin asiakas");
			}				
		}

		if($liitostunnus > 0) {
			$query = "	SELECT *
						FROM asiakas
						WHERE yhtio='$kukarow[yhtio]' and tunnus='$liitostunnus'";
			$result = mysql_query($query) or pupe_error($query);
			$asiakasrow = mysql_fetch_array($result);			
		}
		
		//	Tavat ja Merkinnät
		if($tyyppi == "Merkinta") {
			$qlisa = "and avainsana.selitetark_2 like '%merkinta%'";
			$tapaotsikko = t("Merkintä");
		}
		elseif($tyyppi == "Memo" or $tyyppi == "Muistutus") {
			$qlisa = "and avainsana.selitetark_2 like '%kalenteritapahtuma%'";
			$tapaotsikko = t("Tapahtuma");
		}
		elseif($tyyppi == "projektitapahtuma") {
			$qlisa = "and avainsana.selitetark_2 like '%projektitapahtuma%'";
			$tapaotsikko = t("Projektitapahtuma");
		}
		elseif(substr($tyyppi, 0, 8) == "kontakti") {
			$qlisa = "and avainsana.selitetark_2 like '%kontakti%'";
			$tapaotsikko = t("Kontaktitapa");			
		}
		else {
			$qlisa = "";
			$tapaotsikko = t("Kalenteritapahtuma");			
		}

		$vresult = t_avainsana("KALETAPA", "", $qlisa);
		
		$tavat = "<select name='tapa'>";
		while($taparow = mysql_fetch_array($vresult)) {
			if($tapa == $taparow["selitetark"]) {
				$sel = "SELECTED";
			}
			else {
				$sel = "";
			}
			
			$tavat .= "<option value='$taparow[selite]' $sel>$taparow[selitetark]</option>";
		}
		$tavat .= "</select>";

		/*
			Yhteyshenkilölistaus
		*/
		$query = "	SELECT *
					FROM yhteyshenkilo
					WHERE yhtio='$kukarow[yhtio]' and liitostunnus = '{$asiakasrow["tunnus"]}' and tyyppi = 'A'";
		$result = mysql_query($query) or pupe_error($query);
		$yhdata = "<select name='yhteyshenkilo' id='yhteyshenkilo_kaletapa' onchange=\"selectPopper(event, '../yllapito.php?toim=yhteyshenkilo&liitostunnus={$asiakasrow["tunnus"]}&yhtyyppi=asiakas', 'yhteyshenkilo_kaletapa');\">
							<option value=''>".t("Ei yhteyshenkilöä")."</option>";
		if(mysql_num_rows($result) > 0) {
			
			while ($yhteyshenkilorow = mysql_fetch_array($result)) {
				$sel = "";
				if($yhteyshenkilo == $yhteyshenkilorow["tunnus"]) {
					$sel = "SELECTED";
				}
				
				$yhdata .= "<option value='{$yhteyshenkilorow["tunnus"]}' $sel>{$yhteyshenkilorow["nimi"]}</option>";
			}			
		}
		$yhdata .= "	<optgroup label='".t("Toiminnot")."'>
					<option value='uusi'>".t("Luo uusi yhteyshenkilö")."</option>
				</optgroup>";
		
		$yhdata .= "</select>";
		
		//	Jos meillä on käyttöliittymästä syötetty aika
		if($tapp != "" and $takk != "" and $tavv != "") {
			$tapvm = mktime($tahh, $tamm, 0, $takk, $tapp, $tavv);
		}

		if($lopp != "" and $lokk != "" and $lovv != "") {
			$lopvm = mktime($lohh, $lomm, 0, $lokk, $lopp, $lovv);
		}
		
		if($tapvm == "") {
			$tapvm = time();
		}
		if($lopvm == "") {
			$lopvm = strtotime("+2 hours", $tapvm);
		}

		$klo_alku 		= "<input type = 'text' name='tahh' value = '".date("H", $tapvm)."' size = '3'>:<input type = 'text' name='tamm' value = '".date("i", $tapvm)."' size = '3'>";
		$paiva_alku		= "<input type = 'text' name='tapp' value = '".date("d", $tapvm)."' size = '3'><input type = 'text' name='takk' value = '".date("m", $tapvm)."' size = '3'><input type = 'text' name='tavv' value = '".date("Y", $tapvm)."' size = '5'>";

		$klo_loppu 		= "<input type = 'text' name='lohh' value = '".date("H", $lopvm)."' size = '3'>:<input type = 'text' name='lomm' value = '".date("i", $lopvm)."' size = '3'>";
		$paiva_loppu	= "<input type = 'text' name='lopp' value = '".date("d", $lopvm)."' size = '3'><input type = 'text' name='lokk' value = '".date("m", $lopvm)."' size = '3'><input type = 'text' name='lovv' value = '".date("Y", $lopvm)."' size = '5'>";		
		
		if($otunnus > 0 or $tarjous > 0) {
			$asiakas = "<input type='hidden' name='liitostunnus' value='$liitostunnus'>$asiakasrow[nimi] $asiakasrow[nimitark]";
		} 
		else {
			$asiakas = nayta_asiakashaku($liitostunnus, "kaleForm");
		}
		
		$jakele = false;
		if($tyyppi == "Muistutus") {

			if($muokkaus) {
				$otsikko = "Muokkaa muistutusta";
			}
			else {
				$otsikko = "Lisää muistutus";
			}

			$valitse_kuka = "<select name='kuka'><option value=''>".t("Valitse henkilö")."</option>";
			$query = "	SELECT *
						FROM kuka
						WHERE yhtio = '$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);
			while($row = mysql_fetch_array($result)) {
				if($row["kuka"] == $kuka or (!isset($kuka) and $row["kuka"] == $kukarow["kuka"])) $sel = "selected";
				else $sel = "";
	 			$valitse_kuka .= "<option value='$row[kuka]' $sel>$row[nimi]</option>";
			}
			$valitse_kuka .= "</select>";
			
			$header = "
					<table width='100%'>
						<tr>
							<th colspan='2'>".t("Asiakas")."</th>
						</tr>						
						<tr>
							<td colspan='2'>$asiakas</td>
						</tr>											
						<tr>
							<th>".t("Kuka")."</th>
							<th>".t("Kenelle")."</th>
						</tr>
						<tr>
							<td>$valitse_kuka</td>
							<td>$valitse_kuka</td>
						</tr>
						<tr>
							<th>".t("Tapa")."</th>
							<th>".t("Päiväys")."</th>
						</tr>
						<tr>
							<td>$tavat</td>
							<td>$paiva_alku</td>
						</tr>
						</table>";
			if($ktunnus>0) {
				$kuittaanappi = "<a href='#' onclick=\"sndReq('ajax_menu', '$kalenteri[URL]?ohje=off&kaletee=ANNAKUITTAUS&ktunnus=$ktunnus".url_params($kalenteri)."', false, false); return false;\">".t("Kuittaa")."</a>";
			}

			$jakele = true;
		}
		elseif(substr($tyyppi, 0, 8) == "kontakti") {
			if($otunnus == 0) echo "OHO, tunnus puuttuu!!";

			if($muokkaus) {
				$otsikko = "Muokkaa kontaktointimuistiota";
			}
			else {
				$otsikko = "Kuittaa kontaktointi suoritetuksi";
			}
			$header = "
					<input type='hidden' name='otunnus' value='$otunnus'>
					<table width='100%'>
						<tr>
							<th colspan='2'>".t("Yhteyshenkilö")."</th>
						</tr>						
						<tr>
							<td colspan='2'>$yhdata ".t("Kieli").": {$asiakasrow["kieli"]}</td>
						</tr>						
						<tr>
							<th>".t("Tapa")."</th>
							<th>".t("Päiväys")."</th>
						</tr>			
						<tr>
							<td>$tavat</td>
							<td>$paiva_alku</td>
						</tr>
						<tr>
							<th colspan='2'>".t("Sähköpostin otsikko")."</th>
						</tr>						
						<tr>
							<td colspan='2'><input type = 'text' name = 'mail_subject' value = '$mail_subject' size = '57'></td>
						</tr>						
						</table>";
		}
		elseif($tyyppi == "Memo") {

			if($muokkaus) {
				$otsikko = "Muokkaa asiakasmuistiota";
			}
			else {
				$otsikko = "Lisää asiakasmuistio";
			}
			
			$header = "<table width='100%'>
						<tr>
							<th colspan='2'>".t("Asiakas")."</th>
						</tr>						
						<tr>
							<td colspan='2'>$asiakas</td>
						</tr>						
						<tr>
							<th>".t("Yhteyshenkilö")."</th><th>$tapaotsikko</th>
						</tr>						
						<tr>
							<td>$yhdata</td><td>$tavat</td>
						</tr>						
						<tr>
							<th>".t("Päiväys")."</th>
							<th>".t("Toimitus")."</th>
						</tr>			
						<tr>
							<td>$paiva_alku</td>
							<td>$oval</td>
						</tr>						
						</table>";
			$jakele = true;
		}
		elseif($tyyppi == "projektitapahtuma") {

			if($muokkaus) {
				$otsikko = "Muokkaa projektitapahtumaa";
			}
			else {
				$otsikko = "Lisää projektitapahtuma";
			}
			
			$header = "	<input type='hidden' name='otunnus' value='$otunnus'>
						<table width='100%'>
							<tr>
								<th colspan='2'>".t("Projektitapahtuma")."</th>
							</tr>
							<tr>
								<td colspan='2'>$tavat <input type='text' name='tapatarkenne' value='$tapatarkenne' size='15'></td>
							</tr>
							<tr>
								<th>".t("Alku")."</th>
								<th>".t("Loppu")."</th>
							</tr>
							<tr>
								<td>$paiva_alku</td>
								<td>$paiva_loppu</td>
							</tr>
						</table>";
			$jakele = true;
		}
		elseif($tyyppi == "kalenteri" or $tyyppi == "Merkinta") {
			
			if($tyyppi == "Merkinta") {
				if($muokkaus) {
					$otsikko = "Muokkaa meerkintää";
				}
				else {
					$otsikko = "Lisää merkintä";
				}
			}
			else {
				if($muokkaus) {
					$otsikko = "Muokkaa kalenteritapahtumaa";
				}
				else {
					$otsikko = "Lisää kalenteritapahtuma";
				}
			}
			
			if($kentta10 == "M") {
				$kalenteriin = "<input type='checkbox' name='kalenteriin' value='M' CHECKED>";
			}
			elseif($kentta10 > 0) {
				$kalenteriin = "<input type='checkbox' name='kalenteriin' value='$kentta10' CHECKED DISABLED>";
			}
			else {
				$kalenteriin = "<input type='checkbox' name='kalenteriin' value='M'>";
			}
			
			$header = "<table width='100%'>
						<tr>
							<th colspan='2'>".t("Asiakas")."</th>
						</tr>						
						<tr>
							<td colspan='2'>$asiakas</td>
						</tr>						
						<tr>
							<th colspan='2'>".t("Yhteyshenkilö")."</th>
						</tr>						
						<tr>
							<td colspan='2'>$yhdata</td>
						</tr>						
						<tr>
							<th>$tapaotsikko</th>
							<th>".t("Valitse toimitus")."</th>
						</tr>			
						<tr>
							<td>$tavat</td>
							<td>$oval</td>
						</tr>						
						<tr>
							<th>".t("Alku")."</th>
							<th>".t("Loppu")."</th>
						</tr>
						<tr>
							<td>$paiva_alku $klo_alku</td>
							<td>$paiva_loppu $klo_loppu $kalenteriin</td>
						</tr>
						</table>";
			$jakele = true;
		}
		else {
			echo t("Tapahtuman tyyppi puuttuu tai se on väärä!!")." '$tyyppi'<br>";
		}

		$header .= " $klo";
		
		if($jakele) {
			$mailit = "
				<td class='back'>
				<table>
					<tr>
						<th>".t("Lähetä sähköpostilla")."</th>
					</tr>
					<tr>
						<td>
							<select name='jakelu[]' multiple='TRUE' size='18'>
								<optgroup label='".t("Speciaaliryhmät")."'>
									<option value='yhteyshenkilo'>".t("Valittu yhteyshenkilö")."</option>
								</optgroup>";

			$query = "	SELECT *, concat_ws(',', selitetark) selitetark
			 			FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]' and laji='JAKELULISTA'";
			$ares = mysql_query($query) or pupe_error($query);
			if(mysql_num_rows($ares)>0) {
				$mailit .= "<optgroup label='".t("Jakelulista")."'>";
				while($arow = mysql_fetch_array($ares)) {
					$mailit .= "<option value='{$arow["selitetark"]}'>{$arow["selite"]}</option>";
				}
				$mailit .= "</optgroup>";
			}

			$query = "	SELECT *
			 			FROM kuka
						WHERE yhtio = '$kukarow[yhtio]' and eposti!=''";
			$ares = mysql_query($query) or pupe_error($query);
			if(mysql_num_rows($ares)>0) {
				$mailit .= "<optgroup label='".t("Käyttäjät")."'>";
				while($arow = mysql_fetch_array($ares)) {
					$mailit .= "<option value='{$arow["kuka"]}'>{$arow["nimi"]}</option>";
				}
				$mailit .= "</optgroup>";
			}
			$mailit .= "</select>
					</td>
							</tr>
						</table>
					</td>";
		}
						
		$otsikko = t($otsikko);
		echo "
			<form id = 'kaleForm' name = 'kalenteritapahtuma' onSubmit=\"ajaxPost('kaleForm', '$kalenteri[URL]?' , 'ajax_menu'); return false;\">
				<input type = 'hidden' name='tyyppi' value = '$tyyppi'>
				<input type = 'hidden' name='nakyma' value = '$nakyma'>			
				<input type = 'hidden' name='liitostunnus' value = '$liitostunnus'>
				<input type = 'hidden' name='tarjous' value = '$tarjous'>			
				<input type = 'hidden' name='ktunnus' value = '$ktunnus'>
				<input type = 'hidden' name='toim' value = '$toim'>
				<input type = 'hidden' name='ohje' value = 'off'>
				<input type = 'hidden' name='tapvm' value = '$tapvm'>
				<input type = 'hidden' name='lopvm' value = '$lopvm'>
				<input type = 'hidden' name='dont_str_replace' value = 'true'>
				".url_params($kalenteri, "POST")."			
				<input type = 'hidden' name='kaletee' value = 'TALLENNA_KALENTERITAPAHTUMA'>
				<table>
					<caption>$otsikko</caption>
					<tr>
						$mailit
						<td class='back'>
							<table>
								<tr>
									<td>
										$header
									</td>
								</tr>
								<tr>
									<th>".t("Viesti")."</th>
								</tr>
								<tr>
									<td><textarea rows='10' cols='55' id='viesti' name = 'viesti'>$viesti</textarea></td>
								</tr>
							</table>
						</td>
					</tr>
					<tr align = 'right'>
						<td colspan = '2'>$kuittaanappi&nbsp;&nbsp;$liitanappi&nbsp;&nbsp;$poistanappi&nbsp;&nbsp;<a href='#' onclick=\"ajaxPost('kaleForm', '$kalenteri[URL]?' , 'ajax_menu'); return false;\">".t("Tallenna")."</a></td>
					</tr>
				</table>
				</form>";
		die("</body></html>");
	}

	if($kaletee == "TALLENNA_LIITE") {
		$kalenteri = muistista("kalenteri", "kalenteri");
		if(!is_array($kalenteri)) {
			die("OHO! Kalenterin meni rikki!");
		}

		$id = tallenna_liite("userfile", "kalenteri", $ktunnus, $selite, $kayttotarkoitus);

		$kaletee = "LISAA_LIITE";
		
		//die("<script LANGUAGE='JavaScript'>sndReq('$kalenteri[div]', '$kalenteri[URL]?ohje=off&".url_params($kalenteri)."', false, false); pop=document.getElementById('ajax_menu'); pop.innerHTML=''; pop.style.display='none';</script></body></html>");		
	}

	if($kaletee == "POISTA_LIITE") {
		$kalenteri = muistista("kalenteri", "kalenteri");
		if(!is_array($kalenteri)) {
			die("OHO! Kalenterin meni rikki!");
		}
		
		$query = " DELETE FROM liitetiedostot WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$ltunnus' and liitos = 'kalenteri'";
		$updres = mysql_query($query) or pupe_error($query);	
		$kaletee = "LISAA_LIITE";
		
		//die("<script LANGUAGE='JavaScript'>sndReq('$kalenteri[div]', '$kalenteri[URL]?ohje=off&".url_params($kalenteri)."', false, false); pop=document.getElementById('ajax_menu'); pop.innerHTML=''; pop.style.display='none';</script></body></html>");		
	}
	
	if ($kaletee == "LISAA_LIITE") {
		$kalenteri = muistista("kalenteri", "kalenteri");
		
		if(!is_array($kalenteri)) {
			die("OHO! Kalenterin meni rikki!");
		}
		
		//	Näille voidaan laittaa myös minkä lajin liite on kyseessä
		$ares = t_avainsana("TIL-LITETY", "", "ORDER BY selitetark_2 DESC, jarjestys, selite");
		
		if(mysql_num_rows($ares) > 0) {
			$litety = "<td><select name ='kayttotarkoitus'>";

			while($a = mysql_fetch_array($ares)) {
				
				if ($a["selitetark_2"] == "PAKOLLINEN") {
					$paklisa = "*";
				}
				else {
					$paklisa = "";
				}					
				
				$litety .= "<option value = '$a[selite]'>$paklisa $a[selitetark]</option>";
			}

			$litety .= "</select></td>";
		}
		
		echo "<font class='message'>".t("Lisää uusi tiedosto").":</font><br>";
		
		echo "
			<form method='post' id='liiteForm' name='liiteForm' enctype='multipart/form-data'>
			<input type='hidden' name='ktunnus' value='$ktunnus'>
			<input type='hidden' name='kaletee' value='TALLENNA_LIITE'>
			
			<table>
				<tr>
					<th>".t("Käyttötarkoitus"),"</th>
					<th>".t("Selite"),"</th>
					<th>".t("Tiedosto"),"</th>
				</tr>
				<tr>
					$litety
					<td><input type='text' name='selite' value='' size='20'></td>
					<td><input type='file' name='userfile'></td>
					<td class='back'><input type='submit' name='".t("Liitä")."'></td>
				</tr>
			</table>
			</form><br>";
			
		$query = "	SELECT *, (select selitetark from avainsana a where a.yhtio = liitetiedostot.yhtio and laji = 'TIL-LITETY' and a.selite = liitetiedostot.kayttotarkoitus) kayttotarkoitus
					FROM liitetiedostot
					WHERE liitostunnus='$ktunnus' AND liitos='kalenteri' and yhtio='{$kukarow['yhtio']}'";
		$res = mysql_query($query) or pupe_error($query);

		echo "<table>
			<tr>
				<th>".t('Käyttötarkoitus')."</th>
			    <th>".t('Selite')."</th>
				<th>".t('Tiedosto')."</th>
				<th>".t('Koko')."</th>
				<th>".t('Tyyppi')."</th>
				<th>".t('Lisättyaika')."</th>
				<th>".t('Lisääjä')."</th>
				<th colspan=2></th>
			</tr>";

		while ($liite = mysql_fetch_array($res)) {

			$filesize = $liite['filesize'];
			$type = array('b', 'kb', 'mb', 'gb');

			for ($ii=0; $filesize>1024; $ii++) {
				$filesize /= 1024;
			}

			$filesize = sprintf("%.2f",$filesize)." $type[$ii]";

			echo "<tr>
				<td>".$liite['kayttotarkoitus'] ."</td>
				<td>".$liite['selite'] ."</td>
				<td>".$liite['filename'] ."</td>
				<td>".$filesize."</td>
				<td>".$liite['filetype'] ."</td>
				<td>".tv1dateconv($liite['luontiaika'],"P")."</td>
				<td>".$liite['laatija'] ."</td>
				";

			echo "<td><a href='../view.php?id={$liite['tunnus']}' target='_blank'>".t('Näytä liite')."</a></td>";

			echo "<td>
					<a href='$kalenteri[URL]?ohje=off&kaletee=POISTA_LIITE&ktunnus=$ktunnus&ltunnus=$liite[tunnus]".url_params($kalenteri)."'>".t("Poista")."</a>
				</td>";
			echo "</tr>";
		}
		
		echo "<tr>
				<td><br></td>
			</tr>
			<tr>
				<td colspan='9' align='right' class='back'>
					<a href='$kalenteri[URL]?kaletee=".url_params($kalenteri)."'>".t("Palaa taikaisin")."</a>
				</td>
			</tr>";

		echo "</table><br>";
		
		die("</body></html>");
	}


?>
