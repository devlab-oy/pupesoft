<?php
	
	/*
		Melko geneerisiä kalenterifunktioita
	*/

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
			popUp(event, 'ajax_menu_yp', '10', '-10', url);	
		}
	}

	</SCRIPT>
	<?php
	

	if (!function_exists("tee_menu")) {
				
		// scripti javascript menujen tekemiseen
		function tee_menu ($valinnat, $nappi) {
		
			if(is_array($valinnat) and count($valinnat)>0) {
				$data = "";
				foreach($valinnat as $key => $valinta) {
					if(isset($valinta["VALI"])) {
						$data .= "&menu[$key][vali]=".urlencode($valinta["VALI"]);
					}
					else {
						$data .= "&menu[$key][menu]=".urlencode($valinta["TEKSTI"]); 
						$data .= "&menu[$key][href]=".urlencode($valinta["HREF"]);
						$data .= "&menu[$key][div]=".urlencode($valinta["DIV"]);
						$data .= "&menu[$key][offsetx]=".urlencode($valinta["OFFSETX"]);
						$data .= "&menu[$key][offsety]=".urlencode($valinta["OFFSETY"]);
						$data .= "&menu[$key][target]=".urlencode($valinta["TARGET"]);
						$data .= "&menu[$key][tapa]=".urlencode($valinta["TAPA"]);										
					}
				}

				$data = "<a href='#' onclick=\"popUp(event, 'ajax_menu', '50', '-10', '{$_SERVER["SCRIPT_NAME"]}?kaletee=GENEROIMENU$data'); return false;\">$nappi</a>";			
				return $data;
			}
			return true;
		}
	}

	if(!function_exists("color_code")) {
		// scripti javascript menujen tekemiseen
		function color_code ($arvo, $maxvalue) {
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
			return "rgba($r,$g, 0, 0.4);";
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
		function url_params ($kalenteri, $method) {
			
			$ret = "";
			//	Tehdään urliin parametrit
			if(is_array($kalenteri["url_params"])) {
				foreach($kalenteri["url_params"] as $var => $varval) {
					if($method != "POST") {
						$ret .= "&$var=$varval";	
					}
					else {
						$ret .= "<input type='hidden' name='$var' value='$varval'>";
					}
				}				
			}
			
			return $ret;
		}
	}

	if(!function_exists("kaletyyppi")) {
		function kaletyyppi ($tyyppi) {
			
			static $kaletyypit;
			
			if(!is_array($kaletyyppi)) {
				$kaletyyppi = array(	"kalenteri"	=> "Kalenteri",
										"Memo" 		=> "Asiakasmuistio",
										"Muistutus" => "Muistutus",
									);
				
			}

			return $kaletyyppi[$tyyppi];
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
		function hae_lisatiedot($laji, $lajitark, $tunnus, $viestipituus="") {
			global $kukarow, $kalenteri;
			
			if($laji == "kalenteri") {
				$query = "	SELECT kalenteri.*, if(kentta01='', '***', kentta01) viesti, kalenteri.tyyppi,
									UNIX_TIMESTAMP(pvmalku) pvmalku, 
									UNIX_TIMESTAMP(pvmloppu) pvmloppu,
									avainsana.selitetark tyyppi_nimi,
									DATEDIFF(pvmalku, now()) paivia_jaljella, 
									asiakas.nimi asiakas,
									yhteyshenkilo.nimi yhteyshenkilo
							FROM kalenteri
							LEFT JOIN avainsana ON avainsana.yhtio=kalenteri.yhtio and avainsana.laji='KALETAPA' and avainsana.selite=kalenteri.tapa
							LEFT JOIN asiakas ON asiakas.yhtio=kalenteri.yhtio and asiakas.tunnus=kalenteri.liitostunnus
							LEFT JOIN yhteyshenkilo ON yhteyshenkilo.yhtio=kalenteri.yhtio and yhteyshenkilo.liitostunnus=kalenteri.liitostunnus and yhteyshenkilo.tunnus = kalenteri.henkilo and yhteyshenkilo.tyyppi = 'A'
							WHERE kalenteri.yhtio='$kukarow[yhtio]' and kalenteri.tunnus='$tunnus'";
				$res = mysql_query($query) or pupe_error($query);
				$row = mysql_fetch_array($res);
				//var_dump($kalenteri);
				$otsikko = kaletyyppi($row["tyyppi"]);
				

				$popup_otsikko = "<table>";
				if($row["asiakas"] != "") {
					$popup_otsikko .= "<tr><th class=''>".t("Asiakas").":</th><td class=''>$row[asiakas]</td></tr>";
				}
				if($row["yhteyshenkilo"] != "") {
					$popup_otsikko .= "<tr><th class=''>".t("Yhteyshenkilö").":</th><td class=''>$row[yhteyshenkilo]</td></tr>";
				}
				
				if($row["tyyppi"] == "kalenteri") {
					$popup_otsikko .= "<tr><th class=''>".t("Tapa").":</th><td class=''>$row[tapa]</td></tr>";
					$popup_otsikko .= "<tr><th class=''>".t("Alkaa").":</th><td class=''>".date("d.m.Y h:i", $row["pvmalku"])."</td></tr>";
					$popup_otsikko .= "<tr><th class=''>".t("Päättyy").":</th><td class=''>".date("d.m.Y h:i", $row["pvmloppu"])."</td></tr>";
				}
				elseif($row["tyyppi"] == "Muistutus") {
					$popup_otsikko .= "<tr><th class=''>".t("Päivälle").":</th><td class=''>".date("d.m.Y", $row["pvmalku"])."</td></tr>";
				}
				
				$popup_otsikko .= "</table>";
				
				$popup_otsikko = "	<div style='text-align: right;'>
										<font class='info'>$row[laatija]@$row[luontiaika]</font>
									</div>
									$popup_otsikko";
				
				$otsikko = "<div style='font-weight: bold;'>$otsikko</div>";
				
				if(in_array($row["tyyppi"], array("Muistutus"))) {
					$row["viesti"] = "<div ><font class='info' style='background-color: ".color_code((1*$row["paivia_jaljella"]), 7)."'>{$row["viesti"]}</font></div";
				}
				else {
					$row["viesti"] = "<font class='info'>{$row["viesti"]}</font>";
				}
				
				if(is_int($viestipituus) and strlen($viestipituus)>0) {
					lyhytviesti($row["viesti"], $viestipituus, $otsikko, $popup_otsikko);
				}
				
				if($row["laatija"] == $kukarow["kuka"]) {
					$row["viesti"] = "<a onclick=\"popUp(event, 'ajax_menu', '350', '-10', '$kalenteri[URL]?kaletee=KALENTERITAPAHTUMA&tyyppi=$lajitark&ktunnus=$tunnus".url_params($kalenteri)."'); return false;\" class='kale'>$row[viesti]</a>";
				}
				elseif(in_array($row["tyyppi"], array("Muistutus"))) {
					$row["viesti"] = "<a onclick=\"popUp(event, 'toimitus', '300', '-20', '$kalenteri[URL]?ohje=off&kaletee=ANNAKUITTAUS&ktunnus={$row["tunnus"]}".url_params($kalenteri)."'); return false;\">{$row["viesti"]}</a>";
				}
				else {
					$row["viesti"] = "<a onclick=\"alert('Voit muokata vain omaa kalenteriasi'); return false;\" class='kale'>$row[viesti]</a>";
				}
			}
			elseif($laji == "lasku") {
				
				$query = "	SELECT *
							FROM lasku
							WHERE lasku.yhtio='$kukarow[yhtio]' and lasku.tunnus='$tunnus'";
				$res = mysql_query($query) or pupe_error($query);
				$row = mysql_fetch_array($res);
				
				$viesti = "";
				if($row["tila"] == "T") {
					$atoim = "TARJOUS";
					if($lajitark == "alku") {
						if($versio == 1) {
							$viesti = "$row[laatija] avasi tarjouksen";
						}
						else {
							$viesti = "$row[laatija] teki version $versio";
						}
					}
					elseif($lajitark == "loppu") {
						$viesti = "Tarjous happanee..";
					}
					
				}
				elseif(in_array($row["tila"], array("L", "N", "R"))) {
					$atoim = "MYYNTI";
					if($lajitark == "keräys") {
						$viesti = "Toimituksen lähetys";
					}
					elseif($lajitark == "toimitus") {
						$viesti = "Toimitus perillä";
					}
					elseif($lajitark == "laskutus") {
						$viesti = "Toimitus laskutettu";
					}
					elseif($lajitark == "perustettu") {
						$viesti = "Toimitus perustettu";
					}
					else {
						$viesti = $lajitark;
					}
				}
				
				
				//list($viesti, $poppari) = lyhytviesti($row["tunnus"]." ".$viesti."\nviite: {$row["viesti"]}\n\n{$row["comments"]}", strlen($row["tunnus"]));
				$row["viesti"] = " <a href='#' onclick=\"popUp(event, 'toimitus', '300', '-20', '../raportit/asiakkaantilaukset.php?tee=NAYTA&toim=$atoim&h&tunnus=$row[tunnus]'); return false;\" class='kale' $poppari>$viesti</a>";
				
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
			else {
				echo "Tuntematon laji '$laji'";
				return false;
			}
			
			return purge($row);
		}	 
	}
	
	if(!function_exists("alusta_kalenteri")) {
		function alusta_kalenteri($kalenteri) {
			
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
					if($kj == "kaikki") {
						//Haetaan kaikki käyttäjät joilla on kalenteri käytössä
						$query = "	SELECT DISTINCT kuka.kuka
									FROM kuka
									JOIN oikeu ON oikeu.yhtio = kuka.yhtio and oikeu.kuka = kuka.kuka and oikeu.nimi = 'crm/kalenteri.php'";
					}
					
					if($query != "") {
						$result = mysql_query($query) or pupe_error($query);

						while($row = mysql_fetch_array($result)) {
							$kalenteri["kalenteri_ketka"][] = $row["kuka"];
						}
					}
					else {
						$kalenteri["kalenteri_ketka"] = "";
						echo "Tällästä kalenteri_ketka:a ei tunneta '$kj'<br>";
					}
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
									WHERE yhtio='$kukarow[yhtio]' and tunnusnippu = '{$kalenteri["otunnus"]}' and tila in ({$kalenteri["laskutilat"]})";
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
			
			//	Aloitetaan kalenteri, tallennetaan sen parametrit muistiin..
			if(!muistiin("kalenteri", "kalenteri", $kalenteri)) {
				die("OHO! Kalenterin käyttönotto ei nyt vaan onnistu!");
			}
		} 
	}
	
	if(!function_exists("kalequery")) {
		function kalequery($query) {
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
						
			
			//	Painetaan nämä taas muistin syövereihin jotta ei dementia iske
			if(!muistiin("kalenteri", "kalenteri", $kalenteri)) {
				die("OHO! Kalenterin käyttönotto ei nyt vaan onnistu!");
			}
			
			if(!is_array($kalenteri["kalenteri_nayta_kuka"]) and is_array($kalenteri["kalenteri_ketka"])) {
				$kalenteri["kalenteri_nayta_kuka"] = array();
			}
			if(!is_array($kalenteri["kalenteri_nayta_tyyppi"]) and is_array($kalenteri["kalenteri_tyypit"])) {
				$kalenteri["kalenteri_nayta_tyyppi"] = array();
			}
			
			//	Kalenteri_kuka on meidän masteri, näytetäänkö kalenterit vai ei
			if(is_array($kalenteri["kalenteri_nayta_kuka"]) or is_array($kalenteri["kalenteri_nayta_tyyppi"])) {
				
				//	Tämä on meidän basic kalenterikysely omista kalentereista
				$q = "	
					SELECT 'kalenteri' laji, kalenteri.tyyppi laji_tark, UNIX_TIMESTAMP(date(pvmalku)) aika,  kalenteri.luontiaika, DATEDIFF(pvmloppu, pvmalku) vali, kalenteri.laatija, kalenteri.tunnus
					FROM kalenteri
					JOIN kuka ON kuka.yhtio=kalenteri.yhtio and kuka.kuka=kalenteri.kuka and kuka.kuka IN ('".implode("','", $kalenteri["kalenteri_ketka"])."')
					WHERE kalenteri.yhtio = '$kukarow[yhtio]' 
						and kalenteri.kuka IN ('".implode("','", $kalenteri["kalenteri_nayta_kuka"])."')
						and kalenteri.tyyppi IN ('".implode("','", $kalenteri["kalenteri_nayta_tyyppi"])."')
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
			}
			else {
				echo "KIVI";
			}
			
			
			/*	Haetaan kaikki kalenterimerkinnät */
			$query .= "	ORDER BY 3, 4";
			$result = mysql_query($query) or pupe_error($query);
			//echo $query."<br>";
			$data=array();
			while($row=mysql_fetch_array($result)) {
				if(!isset($data["tiedot"]["alkustr"])) {
					$data["tiedot"]["alkustr"] = $row["aika"];
				}

				$data["tiedot"]["loppustr"] = $row["aika"];	
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
	
	//	Luodaan kalenterinäkymiä annetusta datasta
	if(!function_exists("kalenteri")) {
		function kalenteri ($data) {
			global $kukarow, $yhtiorow, $nakyma, $kaleaika;
			
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
						<caption>".t("Käyttäjät")."</caption>";
						
				$lisa = "";
				
				$query = " 	SELECT *
							FROM kuka
							WHERE yhtio='$kukarow[yhtio]' and kuka IN ('".implode("','", $kalenteri["kalenteri_ketka"])."')
							ORDER BY kuka IN('{$kukarow["kuka"]}') DESC, nimi ";
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
						<caption>".t("Näytä merkinnät")."</caption>";

					foreach($kalenteri["kalenteri_tyypit"] as $tyyppi) {
						
						$kalejako .= "<tr>";
						
						if(in_array($tyyppi, $kalenteri["kalenteri_nayta_tyyppi"])) {
							$kalejako .= "
								<td><div style='float: left;'><font class='info'>".kaletyyppi($tyyppi)."</font></div><div style='float: right;'><input type='checkbox' name='' onclick=\"sndReq('$kalenteri[div]', '$kalenteri[URL]?ohje=off&kalepoisto[kalenteri_nayta_tyyppi]=$tyyppi".url_params($kalenteri)."', '$kalenteri[div]', false);\" checked></div></td>";
						}
						else {
							$kalejako .= "
								<td><div style='float: left;'><font class='info'>".kaletyyppi($tyyppi)."</font></div><div style='float: right;'><input type='checkbox' name='' onclick=\"sndReq('$kalenteri[div]', '$kalenteri[URL]?ohje=off&kalelisaa[kalenteri_nayta_tyyppi]=$tyyppi".url_params($kalenteri)."', '$kalenteri[div]', false);\"></div></td>";
						}
						
						$kalejako .= "</tr>";
					}

					$kalejako .= "
					</table>";
			}
					
			
			//	Piirretään header
			
			$menu = array();
			$menu[]	= array("VALI" => "Toiminnot");	
			if(is_array($data["tiedot"]["menut"]["toiminnot"])) {

				foreach($data["tiedot"]["menut"]["toiminnot"] as $a) {
					$menu[] = $a;
				}
			}
			
			$menu[] = array("TEKSTI" => "Lisää Kalenteritapahtuma", "HREF" => "$kalenteri[URL]?kaletee=KALENTERITAPAHTUMA&tyyppi=kalenteri&tapvm=$kaleaika".url_params($kalenteri), "OFFSETX" => 350, "OFFSETY" => 30);
			$menu[] = array("TEKSTI" => "Lisää Asiakasmuistio",		"HREF" => "$kalenteri[URL]?kaletee=KALENTERITAPAHTUMA&tyyppi=Memo&tapvm=$kaleaika".url_params($kalenteri), "OFFSETX" => 350, "OFFSETY" => 40);
			$menu[] = array("TEKSTI" => "Lisää Muistutus",			"HREF" => "$kalenteri[URL]?kaletee=KALENTERITAPAHTUMA&tyyppi=Muistutus&tapvm=$kaleaika".url_params($kalenteri), "OFFSETX" => 350, "OFFSETY" => 50);
			
			$palkki[] = tee_menu($menu, "<font class='message'>".t("Toiminnot")."</font>");

			//	Tallennetaan tämä oletukseksi
			$toiminnot = tee_menu($menu, "<font class='message'>".t("Toiminnot")."</font>");
			$palkki = array($toiminnot);

			$menu = array();
			foreach(array("TAPAHTUMALISTAUS", "KALENTERINAKYMA", "KUUKAUSINAKYMA", "VIIKKONAKYMA", "PAIVANAKYMA") as $n) {
				if($kalenteri["sallittu_nakyma"] == "" or in_array($n, $kalenteri["sallittu_nakyma"])) {
					$menu[] = array("TEKSTI" => ucfirst(strtolower($n)),		"HREF" => "$kalenteri[URL]?nakyma=$n".url_params($kalenteri), "DIV" => $kalenteri["div"], "TAPA" => "request");
				} 
			}

			$palkki[] = tee_menu($menu, "<font class='message'>".t("Vaihda näkymää")."</font>");
			
			if(is_array($data["tiedot"]["menut"])) {
				foreach($data["tiedot"]["menut"] as $key => $val) {
					//	Tämä on oma valikko vain jos sen indexi on numero
					if(is_numeric($key)) {
						$palkki[] = $val;
					}
				}
			}
			
			/*
			if($laji == "TILAUS") {
				if($laskurow["tila"] == "R") {
					//	Tarkastetaan onko nipussa toimittamattomia tilauksia
					$query = "	SELECT tunnus
								FROM lasku
								WHERE yhtio = '$kukarow[yhtio]' and tunnusnippu = '$tarjous' and (tila='N' or (tila='L' and alatila != 'X'))";
					$tarkres = mysql_query($query) or pupe_error($query);
					if(mysql_num_rows($tarkres)==0) {

						//	Tarkastetaan onko nipussa maksamattomia laskuja?
						$query = "	SELECT tunnus
									FROM lasku
									WHERE yhtio = '$kukarow[yhtio]' and tunnusnippu = '$tarjous' and tila='U' and mapvm='0000-00-00'";
						$tarkres = mysql_query($query) or pupe_error($query);
						if(mysql_num_rows($tarkres)==0) {
							$menu[] = array("TEKSTI" => "Sulje projekti", "HREF" => "tilaushakukone.php?tarjous=$tarjous&toim=$toim&setti=$setti&tee=SULJEPROJEKTI", "DIV" => "main");
						}
					}
				}
			}
			elseif($laji == "TARJOUS") {
				if($laskurow["alatila"] == "A" and $laskurow["tila"] == "T") {
					$menu[] = array("TEKSTI" => "Hylkää tarjous", "HREF" => "tilaushakukone.php?tarjous=$tarjous&toim=$toim&setti=$setti&tee=HYLKAATARJOUS&otunnus=$laskurow[versio]&liitostunnus=$laskurow[liitostunnus]", "DIV" => "main");
				}			
			}

			$versioita = $laskurow["versioita"];
			unset($teksti);

			if($laji == "TARJOUS") {
				$menu[]	= array("VALI" => "Näytä versio");
			}
			else {
				$menu[]	= array("VALI" => "Näytä toimitus");
			}

			if($laji == "TARJOUS") {
				$versiot = explode(",", $laskurow["versiot"]);				
				rsort($versiot);

				foreach($versiot as $v) {
					if(!isset($teksti)) {
						$teksti = "Viimeisin versio";
					}
					else {
						$teksti = "Versio $versioita";
					}

					$menu[] = array("TEKSTI" => $teksti, "HREF" => "../raportit/asiakkaantilaukset.php?tee=NAYTA&toim=TARJOUS&h&tunnus=$v", "OFFSETX" => 650, "OFFSETY" => 20);

					$versioita--;
				}		
			}
			elseif($laji == "TILAUS") {
				$versiot = explode(",", $laskurow["versiot"]);

				$query = "	SELECT tunnus, tila, alatila
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]' and tunnus IN ($laskurow[versiot])
							ORDER BY tunnus";
				$res = mysql_query($query) or pupe_error($query);
				if(mysql_num_rows($res) > 0) {
					while($r = mysql_fetch_array($res)) {
						$laskutyyppi = $r["tila"];
						$alatila	 = $r["alatila"];
						require "inc/laskutyyppi.inc";

						$menu[] = array("TEKSTI" => "$r[tunnus] - $laskutyyppi $alatila", "HREF" => "../raportit/asiakkaantilaukset.php?tee=NAYTA&toim=MYYNTI&h&tunnus=$r[tunnus]", "OFFSETX" => 650, "OFFSETY" => 20);
					}
				}
			}




			$liitetiedosto = "<a href=\"javascript:sndReq('tarjouskalenteri_$tarjous', '', 'kasittele_$tarjous', true);\"><font class='message'>$liitetiedosto</font></a>";	
			$palkki[] = $liitetiedosto;
			*/
			//	Piirretään koko palkki
			$kale = "<center>".implode("&nbsp;&nbsp;-&nbsp;&nbsp;", $palkki)."</center><br><br>";
			
			if($nakyma == "KALENTERINAKYMA" or $nakyma == "KUUKAUSINAKYMA") {
				$kale .= "
				<div style='float: left;'>
				<table>
				<tr>
					<th>".t("Vko")."</th></td><th>".t("Ma")."</th><th>".t("Ti")."</th><th>".t("Ke")."</th><th>".t("To")."</th><th>".t("Pe")."</th><th>".t("La")."</th><th>".t("Su")."</th>
				</tr>";

				$leveys = 7;
				if($nakyma == "KUUKAUSINAKYMA") {
					$kaleaika = mktime(0, 0, 0, date("m", $kaleaika), 1, date("Y", $kaleaika));
					$paivia = cal_days_in_month(CAL_GREGORIAN, date("m", $kaleaika), date("Y", $kaleaika))-2;
					
					$seuraava 	= strtotime("+1 months", $kaleaika);
					$edellinen 	= strtotime("-1 months", $kaleaika);
					$otsikko	= "Kausi ".date("m", $kaleaika)." - ".date("Y", $kaleaika);
					
					$kale .= "<caption><a href=\"javascript:sndReq('$kalenteri[div]', '$kalenteri[URL]?ohje=off&kaleaika=$edellinen".url_params($kalenteri)."', '$kalenteri[div]', false);\">&lt;&lt;&nbsp;</a>&nbsp;&nbsp;$otsikko&nbsp;&nbsp;&nbsp;<a href=\"javascript:sndReq('$kalenteri[div]', '$kalenteri[URL]?ohje=off&kaleaika=$seuraava".url_params($kalenteri)."', '$kalenteri[div]', false);\">&gt;&gt;</a></caption>";
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
				if(date("N", $kaleaika)>0) {
					$maanantai = strtotime("-2 monday", $kaleaika);
				}
				elseif(date("N", $kaleaika)==1) {
					$maanantai = $kaleaika;
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
									$lisatiedot = hae_lisatiedot($row["laji"], $row["laji_tark"], $row["tunnus"], 0);	
									
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

								$lisatiedot = hae_lisatiedot($row["laji"], $row["laji_tark"], $row["tunnus"], 10);	

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
					$menu[] = array("TEKSTI" => "Lisää kalenteriisi", 		"HREF" => "$kalenteri[URL]?kaletee=KALENTERITAPAHTUMA&tyyppi=kalenteri&tapvm=$aikastr".url_params($kalenteri), "OFFSETX" => 350, "OFFSETY" => 30);
					$menu[] = array("TEKSTI" => "Lisää muistutus", 			"HREF" => "$kalenteri[URL]?kaletee=KALENTERITAPAHTUMA&tyyppi=Muistutus&tapvm=$aikastr".url_params($kalenteri), "OFFSETX" => 350, "OFFSETY" => 40);
					$menu[] = array("TEKSTI" => "Lisää asiakasmuistio", 	"HREF" => "$kalenteri[URL]?kaletee=KALENTERITAPAHTUMA&tyyppi=Memo&&tapvm=$aikastr".url_params($kalenteri), "OFFSETX" => 350, "OFFSETY" => 40);
															
					$kale .= "	<td width='100' height = '$height' NOWRAP style='border-style: solid; border-width: 1px; background-color: $color;'>
									<div style='text-align: right;'>".tee_menu($menu, "<font class='kaleinfo'>".date("d.m.", $aikastr)."</font>")."</div>".implode("", $ulos)."
								</td>";

				}			

				$kale .= "</table>
						</div>
						<div style='float: left; position: relative; left: 20px;'>$kalejako</div>";
			}
			elseif($nakyma == "TAPAHTUMALISTAUS") {
				
				$kale .= "	<div style='width: 750px;'>
							<div style='float: right; position: relative; left: 20px;'>$kalejako</div>
							<table width = '600' align='left'>";
				
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
								
								$lisatiedot = hae_lisatiedot($tapahtuma["laji"], $tapahtuma["laji_tark"], $tapahtuma["tunnus"]);
								$viesti = $lisatiedot["viesti"];
								
								if($tapahtuma["laji"] == "kalenteri" and $tapahtuma["vali"] > 1) {
									$viesti .= "<br><br>".t("%s päättyy: %s", $kieli, $lisatiedot["tyyppi_nimi"], date("d.m.Y h:i"));
								}
								
								if($lisatiedot["yhteyshenkilo"] != "") {
									$extra = "&nbsp;&ndash;&nbsp;".$lisatiedot["yhteyshenkilo"];
								}
								else {
									$extra = "";
								}
								
								$kale .= "<th>$tapahtuma[laji_tark]$extra<div style='float: right;'><font class='info'>$tapahtuma[laatija]@$tapahtuma[luontiaika]</font></div></th></tr>";
								
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
					if(date("N", $kaleaika)>0) {
						$maanantai = strtotime("-1 monday", $kaleaika);
					}
					elseif(date("N", $kaleaika)==1) {
						$maanantai = $kaleaika;
					}
					else {
						$maanantai = strtotime("+1 monday", $kaleaika);
					}
					
					$seuraava 	= strtotime("+2 weeks", $maanantai);
					$edellinen 	= strtotime("-0 weeks", $maanantai);
					
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
				<div style='float: left;'>
					<table width='".(($days*$w)-40)."' cellpadding='1' cellspacing='10'>
					<caption><br><a href=\"javascript:sndReq('$kalenteri[div]', '$kalenteri[URL]?ohje=off&kaleaika=$edellinen".url_params($kalenteri)."', '$kalenteri[div]', false);\">&lt;&lt;&nbsp;</a>&nbsp;&nbsp;$otsikko&nbsp;&nbsp;&nbsp;<a href=\"javascript:sndReq('$kalenteri[div]', '$kalenteri[URL]?ohje=off&kaleaika=$seuraava".url_params($kalenteri)."', '$kalenteri[div]', false);\">&gt;&gt;</a></caption>
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
				for($i=0;$i<$days;$i++) {
					$ulos = "";
					
					foreach($data["lista"][$paivat[$i]] as $row) {
						if(in_array($row["laji"], array("kalenteri"))) {
							if(in_array($row["laji_tark"], array("Muistutus", "Memo"))) {

								if($ulos != "") {
									$ulos .= "<hr>";
								}
								$lisatiedot = hae_lisatiedot($row["laji"], $row["laji_tark"], $row["tunnus"]);
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

					foreach($data["lista"][$paivat[$i]] as $row) {
						if(in_array($row["laji"], array("lasku"))) {
							if($ulos .= "") {
								$ulos .= "<hr>";
							}
							$lisatiedot = hae_lisatiedot($row["laji"], $row["laji_tark"], $row["tunnus"]);

							$ulos .= $lisatiedot["viesti"];
						}
					}
					$kale .= "
						<td width='$w' height = '25' NOWRAP style='border-style: solid; border-width: 0px;'>$ulos</td>";
				}
				$kale .= "<tr>";
			
				$h = 07;
				$m = 00;
				
				//	loopataan klo 21 asti
				while($h<18) {
					
					$h = sprintf("%02d", $h);
					$m = sprintf("%02d", $m);
					
					$kale .= "
							<tr>
								<td class='back' align = 'left'><font class='kaleinfo'>$h:$m</font></td>";
					
					
					for($i=0;$i<$days;$i++) {
						
						//	Tämän hetken kellonaika
						$aikastr = strtotime("+$h hours +$m minutes", $paivat[$i]);
						
						$kale .= "<td width='$w' height = '10' NOWRAP style='border-style: solid; border-width: 0px; background-color: $color;'>";
						
						if(is_array($data["lista"][$paivat[$i]])) {
							
							$ulos = array();
							foreach($data["lista"][$paivat[$i]] as $row) {
								if(!in_array($row["laji_tark"], array("Memo", "Muistutus"))) {
									$lisatiedot = hae_lisatiedot($row["laji"], $row["laji_tark"], $row["tunnus"]);
									if($lisatiedot["pvmalku"] <= $aikastr and $lisatiedot["pvmloppu"] > $aikastr) {

										if(!isset($tcolor[$row["tunnus"]])) {
											$tcolor[$row["tunnus"]] = color_code(count($tcolor)*13+55, 100);
											
											$viesti = $poppari = "";
											if($nakyma == "VIIKKONAKYMA") {
												list($viesti, $poppari) = lyhytviesti($lisatiedot["viesti"], 40);
											}
											else {
												$viesti = $lisatiedot["viesti"];
											}

											$ulos[$tpos[$i][$row["tunnus"]]] = "<div style='width: ".($w/count($tpos[$i]))."; float: left; text-align: left; background-color: ".$tcolor[$row["tunnus"]].";' $poppari>$viesti</div>";
										}
										else {
											$ulos[$tpos[$i][$row["tunnus"]]] = "<div style='width: ".($w/count($tpos[$i]))."; float: left; text-align: left; background-color: ".$tcolor[$row["tunnus"]].";'>&nbsp;</div>";
										}
									}
									else {
										$ulos[$tpos[$i][$row["tunnus"]]] = "<div style='width: ".($w/count($tpos[$i]))."; height: 1px; float: left; background-color: inherit;'></div>";
									}
								}
							}
						}
						else {
							$ulos = array("");
						}
												
						$menu = array();
						$menu[] = array("TEKSTI" => "Lisää kalenteriin", 		"HREF" => "$kalenteri[URL]?kaletee=KALENTERITAPAHTUMA&tyyppi=kalenteri&tapvm=$aikastr".url_params($kalenteri), "OFFSETX" => 350, "OFFSETY" => 30);
						$menu[] = array("TEKSTI" => "Lisää muistutus", 			"HREF" => "$kalenteri[URL]?kaletee=KALENTERITAPAHTUMA&tyyppi=Muistutus&tapvm=$aikastr".url_params($kalenteri), "OFFSETX" => 350, "OFFSETY" => 40);
						$menu[] = array("TEKSTI" => "Lisää asiakasmuistio", 	"HREF" => "$kalenteri[URL]?kaletee=KALENTERITAPAHTUMA&tyyppi=Memo&tapvm=$aikastr".url_params($kalenteri), "OFFSETX" => 350, "OFFSETY" => 50);

						$kale .= "<div style='width: {$w}px; text-align: right; z-index: 1;'>".tee_menu($menu, "<font class='kaleinfo'>Lisää</font>")."</div>".implode("",$ulos);
						$kale .= "</td>";
					}			
					
					$m += 30;
					if($m == 60) {
						$m = 00;
						$h++;
					}
				}

				$kale .= "</table><br><br>
						</div>
						<div style='float: left; position: relative; left: 20px;'>$kalejako</div>";
			}
			else {
				echo "Näkymää '$nakyma' ei vielä osata!";
			}
			return $kale;
		}
	}	
	
	if($kaletee == "GENEROIMENU") {
		if(is_array($menu) and count($menu)>0) {
		
			echo "<table>";
			foreach($menu as $item) {
				if(isset($item["vali"])) {
					echo "<tr><td class=\"back\" height='20' style='vertical-align: bottom;'><font class='info'>{$item["vali"]}</font></td></tr>";
				}
				else {
					if($item["target"] == "page") {
						echo "<tr><td class=\"back\"><a class=\"menu\" href=\"{$item["href"]}\">$item[menu]</a></td></tr>";
					}
					elseif($item["target"] == "_blank") {
						echo "<tr><td class=\"back\"><a class=\"menu\" href=\"{$item["href"]}\" target='_blank'>$item[menu]</a></td></tr>";
					}
					else {
						if($item["div"] != "") {
							$div = $item["div"];
						}
						else {
							$div = "ajax_menu";
						}

						if(isset($item["offsetx"])) {
							$x = $item["offsetx"];
						}
						else {
							$x = 50;					
						}

						if(isset($item["offsety"])) {
							$y = $item["offsety"];
						}
						else {
							$y = -10;
						}
					
						if($item["tapa"] == "request") {
							echo "<tr><td class=\"back\"><a class=\"menu\" onclick=\"divi=document.getElementById('ajax_menu'); divi.innerHTML=''; divi.style.display='none'; sndReq('$div', '$item[href]', false, false);\">$item[menu]</a></td></tr>";	
						}
						else {
							echo "<tr><td class=\"back\"><a class=\"menu\" href=\"#\" onclick=\"divi=document.getElementById('ajax_menu'); divi.innerHTML=''; divi.style.display='none'; popUp(event, '$div', '$x', '$y', '{$item["href"]}'); return false;\">$item[menu]</a></td></tr>";	
						}
					}
				}
			}
			echo "</table>";

		}

		die("</body></html>");
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
		
		$ok = 1;
		
		//	Näille vaaditaan asiakasid
		if($liitostunnus== 0 and in_array($tyyppi, array("Memo"))) {
			echo "<font class='error'>$tyyppi vaatii aina asiakkaan!<br></font>";
			$ok = 0;
		}

		//	Viestin on aina oltava
		if($viesti== "") {
			echo "<font class='error'>viesti puuttuu!</font><br>";
			$ok = 0;			
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
			
			$viesti = $viesti;

			$query = "select * from asiakas where yhtio='$kukarow[yhtio]' and tunnus='$liitostunnus'";
			$result = mysql_query($query) or pupe_error($query);
			$asrow = mysql_fetch_array($result);

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
			$kysely = "	tapa 			= '$tapa',
						asiakas  	 	= '$asrow[ytunnus]',
						liitostunnus	= '$liitostunnus',
						henkilo  		= '$yhteyshenkilo',
						kuka     		= '$kuka',
						tyyppi   		= '$tyyppi',
						pvmalku  		= '$tapvm',
						pvmloppu  		= '$lopvm',
						otunnus  		= '$otunnus',
						kuittaus  		= '$kuittaus',
						kentta01 		= '$viesti'";
			$query .= $kysely.$where;
			$result = mysql_query($query) or pupe_error($query);

			if(count($jakelu)) {

				//	Haetaan kaikki listata
				$query = "	SELECT nimi, eposti
							FROM kuka
							WHERE yhtio = '$kukarow[yhtio]' and kuka IN ('".str_replace(",","','", implode(",", $jakelu))."') and eposti != ''";
				$jres = mysql_query($query) or pupe_error($query);
				if(mysql_num_rows($jres)>0) {
					$to = "";
					while($jrow = mysql_fetch_array($jres)) {
						if($to != "") $to .= ", ";
						$to .= "=?iso-8859-1?Q?".imap_8bit($jrow["nimi"])."?= <{$jrow["eposti"]}>";
					}
					
					//	melkosta..
					if(preg_match_all("//"))
					$header  = "From: <$yhtiorow[postittaja_email]>\n";
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
						$msg = "Tyyppi: ".t("Kalenterimerkintä")."\n";
						$msg .= "Tapa: $tapa\n";
						
					}
					else {
						$msg = "Tyyppi: ".t($tyyppi)."\n";
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
					
					$msg .= "\n$viesti\n";
					if(mail($to, "=?iso-8859-1?Q?".imap_8bit($subject)."?=", $msg, $header, " -f $yhtiorow[postittaja_email]")) {
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

			die("<script LANGUAGE='JavaScript'>sndReq('$kalenteri[div]', '$kalenteri[URL]?ohje=off&".url_params($kalenteri)."', false, false); pop=document.getElementById('ajax_menu'); pop.innerHTML=''; pop.style.display='none';</script></body></html>");
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
				
				$viesti	 		= $krow["kentta01"];
				$liitostunnus 	= $krow["liitostunnus"];
				$otunnus 		= $krow["otunnus"];
				$tapvm 			= $krow["pvmalku"];
				$lopvm 			= $krow["pvmloppu"];			
				$kuka 			= $krow["kuka"];
				$otunnus 		= $krow["otunnus"];
				$yhteyshenkilo 	= $krow["henkilo"];
				$tapa 			= $krow["tapa"];							
				$poistanappi 	= "<a href='#' onclick=\"sndReq('$kalenteri[div]', '$kalenteri[URL]?ohje=off&kaletee=POISTA_KALENTERITAPAHTUMA&ktunnus=$ktunnus".url_params($kalenteri)."', false, false); pop=document.getElementById('ajax_menu'); pop.innerHTML=''; pop.style.display='none'; return false;\">Poista</a>";
				$liitanappi 	= "<a href='#' onclick=\"sndReq('ajax_menu', '$kalenteri[URL]?ohje=off&kaletee=LISAA_LIITE&ktunnus=$ktunnus".url_params($kalenteri)."', false, false); return false;\">Liitteet</a>";
			}
		}
		else {
			$muokkaus	= 0;
			$poistanappi = "";
		}
		
		if($tarjous > 0) {
			$query = "	SELECT liitostunnus
						FROM lasku
						WHERE yhtio='$kukarow[yhtio]' and tunnus='$tarjous'";
			$result = mysql_query($query) or pupe_error($query);
			$aburow = mysql_fetch_array($result);
			$liitostunnus = $aburow["liitostunnus"];
		}
		
		$order = "DESC";		
		if($kalenteri["laskutilat"] != "") {

			$query = "";
			//	Jos tullaan tilaushakukoneesta..
			if($tarjous > 0) {
				//	Valitaan joku toimitus
				$query = "	SELECT tunnus, tila, alatila, liitostunnus
							FROM lasku
							WHERE yhtio  = '$kukarow[yhtio]' and tunnusnippu = $tarjous and tila IN ($kalenteri[laskutilat])
							ORDER BY tunnus $order";
			}
			elseif($otunnus > 0) {
				$query = "	SELECT tunnus, tila, alatila, liitostunnus
							FROM lasku
							WHERE yhtio  = '$kukarow[yhtio]' and tunnus = $otunnus
							ORDER BY tunnus $order";			
			}
			elseif($liitostunnus > 0) {
				//	Valitaan joku asiakkaan tilaus
				$query = "	SELECT tunnus, tila, alatila
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

					$oval .= "<input type='hidden' name='otunnus' value='$row[tunnus]'>$row[tunnus] - $laskutyyppi $alatila</option>";
					
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

						$oval .= "<option value='$row[tunnus]'>$row[tunnus] - $laskutyyppi $alatila</option>";

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
		
		$query = "	SELECT selitetark
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji = 'KALETAPA'
					ORDER BY selite";
		$vresult = mysql_query($query) or pupe_error($query);
		$tavat = "<select name='tapa'>";
		while($taparow = mysql_fetch_array($vresult)) {
			if($tapa == $taparow["selitetark"]) {
				$sel = "SELECTED";
			}
			else {
				$sel = "";
			}
			
			$tavat .= "<option value='$taparow[selitetark]' $sel>$taparow[selitetark]</option>";
		}
		$tavat .= "</select>";

		/*
			Yhteyshenkilölistaus
		*/
		$query = "	SELECT *
					FROM yhteyshenkilo
					WHERE yhtio='$kukarow[yhtio]' and liitostunnus = '{$asiakasrow["tunnus"]}' and tyyppi = 'A'";
		$result = mysql_query($query) or pupe_error($query);
		if(mysql_num_rows($result) > 0) {
			$yhdata = "<select name='yhteyshenkilo' id='yhteyshenkilo' onchange=\"selectPopper(event, '../yllapito.php?toim=yhteyshenkilo&liitostunnus={$asiakasrow["tunnus"]}&yhtyyppi=asiakas', this.id);\">
								<option value=''>".t("Ei yhteyshenkilöä")."</option>";
			
			while ($yhteyshenkilorow = mysql_fetch_array($result)) {
				$sel = "";
				if($yhteyshenkilo == $yhteyshenkilorow["tunnus"]) {
					$sel = "SELECTED";
				}
				
				$yhdata .= "<option value='{$yhteyshenkilorow["tunnus"]}' $sel>{$yhteyshenkilorow["nimi"]}</option>";
			}
			
			$yhdata .= "	<optgroup label='".t("Toiminnot")."'>
						<option value='uusi'>Luo uusi yhteyshenkilö</option>
					</optgroup>";
			
			$yhdata .= "</select>";
		}
		else {
			$yhdata = "&nbsp;";
		}
		
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
		
		if($otunnus > 0 or $tarjous > 0 or $ktunnus>0) {
			$asiakas = "<input type='hidden' name='liitostunnus' value='$liitostunnus'>$asiakasrow[nimi] $asiakasrow[nimitark]";
		} 
		else {
			$asiakas = nayta_asiakashaku($liitostunnus, "kaleForm");
		}
		
		if($tyyppi == "Muistutus") {

			if($muokkaus) {
				$otsikko = "Muokkaa muistutusta";
			}
			else {
				$otsikko = "Lisää muistutus";
			}

			$header = "<select name='kuka'><option value=''>".t("Valitse henkilö")."</option>";
			$query = "	SELECT *
						FROM kuka
						WHERE yhtio = '$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);
			while($row = mysql_fetch_array($result)) {
				if($row["kuka"] == $kuka) $sel = "selected";
				else $sel = "";
	 			$header .= "<option value='$row[kuka]' $sel>$row[nimi]</option>";
			}
			$header .= "</select> $oval $paiva_alku";
			
			if($ktunnus>0) {
				$kuittaanappi = "<a href='#' onclick=\"sndReq('ajax_menu', '$kalenteri[URL]?ohje=off&kaletee=ANNAKUITTAUS&ktunnus=$ktunnus".url_params($kalenteri)."', false, false); return false;\">".t("Kuittaa")."</a>";
			}
		}
		elseif(substr($tyyppi, 0, 8) == "kontakti") {
			if($otunnus == 0) echo "OHO, tunnus puuttuu!!";
			if($muokkaus) {
				$otsikko = "Muokkaa kontaktointimuistiota";
			}
			else {
				$otsikko = "Kuittaa kontaktointi suoritetuksi";
			}
			$header = "$ostat $paiva_alku";			
		}
		elseif($tyyppi == "Memo") {

			if($muokkaus) {
				$otsikko = "Muokkaa asiakasmuistiota";
			}
			else {
				$otsikko = "Lisää asiakasmuistio";
			}
			
			$header = "<table>
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
							<th>".t("Päiväys")."</th>
							<th>".t("Toimitus")."</th>
						</tr>			
						<tr>
							<td>$paiva_alku</td>
							<td>$oval</td>
						</tr>						
						</table>";
			
		}
		elseif($tyyppi == "kalenteri") {

			if($muokkaus) {
				$otsikko = "Muokkaa kalenteritapahtumaa";
			}
			else {
				$otsikko = "Lisää kalenteritapahtuma";
			}
						
			$header = "<table>
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
							<th>".t("Tapa")."</th>
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
							<td>$paiva_loppu $klo_loppu</td>
						</tr>
						</table>";
		}
		else {
			echo "Tapahtuman tyyppi puuttuu tai se on väärä '$tyyppi'!!<br>";
		}

		$header .= " $klo";

		$mailit = "<select name='jakelu[]' multiple='TRUE' size='18'>
						<option value=''>".t("Valitse jakelu")."</option>";			

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
		$mailit .= "</select>";
				
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
				".url_params($kalenteri, "POST")."			
				<input type = 'hidden' name='kaletee' value = 'TALLENNA_KALENTERITAPAHTUMA'>
				<table>
					<caption>$otsikko</caption>
					<tr>
						<td class='back'>
							<table>
								<tr>
									<th>".t("Lähetä sähköpostilla")."</th>
								</tr>
								<tr>
									<td>$mailit</td>
								</tr>
							</table>
						</td>
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
						<td colspan = '2'>$kuittaanappi&nbsp;&nbsp;$liitanappi&nbsp;&nbsp;$poistanappi&nbsp;&nbsp;<a href='#' onclick=\"ajaxPost('kaleForm', '$kalenteri[URL]?' , 'ajax_menu'); return false;\">Tallenna</a></td>
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
	
	if($kaletee == "LISAA_LIITE") {
		$kalenteri = muistista("kalenteri", "kalenteri");
		if(!is_array($kalenteri)) {
			die("OHO! Kalenterin meni rikki!");
		}
		
		//	Näille voidaan laittaa myös minkä lajin liite on kyseessä
		$query = "	SELECT selite, if(selitetark_2='PAKOLLINEN', concat('* ', selitetark), concat('&nbsp;&nbsp;', selitetark)) selitetark
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji = 'TIL-LITETY'
					ORDER BY selitetark_2 DESC, jarjestys, selite";
		$ares = mysql_query($query) or pupe_error($query);
		if(mysql_num_rows($ares) > 0) {
			$litety = "<td><select name ='kayttotarkoitus'>";
			while($a = mysql_fetch_array($ares)) {
				$litety .= "<option value = '$a[selite]'>$a[selitetark]</option>";
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
