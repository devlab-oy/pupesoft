<?php

function teeValinta($tunken, $lisaaTilausriviTyypit) {
	global $yhtiorow, $kukarow, $era_new_paikka, $era_old_paikka;
	
	$paivitetty = 0;
	
	foreach($era_new_paikka as $rivitunnus => $uusipaikka) {

		//	Jos meill‰ on uusipaikka ja se on eri kuin vanhapaikka kohdistetaan er‰
		if($uusipaikka!=$era_old_paikka[$rivitunnus]) {
			
			$query = "	SELECT *
						FROM tilausrivi 
						WHERE yhtio = '$kukarow[yhtio]' and tunnus='$rivitunnus' and tyyppi IN ('".implode("','", $lisaaTilausriviTyypit)."')";
			$toimres = mysql_query($query) or pupe_error($query);

			if(mysql_num_rows($toimres) > 0) {
				$toimrow = mysql_fetch_array($toimres);

				list($myy_hyllyalue, $myy_hyllynro, $myy_hyllyvali, $myy_hyllytaso, $myy_era) = explode("#", $era_new_paikka[$toimrow["tunnus"]]);
				
				$query = "	DELETE FROM sarjanumeroseuranta
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$toimrow[tuoteno]'
							and myyntirivitunnus = '$toimrow[tunnus]'";
				$sarjares2 = mysql_query($query) or pupe_error($query);
				
				if ($toimrow["varattu"] > 0) {
					$query = "	SELECT *
								FROM sarjanumeroseuranta
								WHERE yhtio		= '$kukarow[yhtio]'
								and tuoteno		= '$toimrow[tuoteno]'
								and hyllyalue   = '$myy_hyllyalue'
								and hyllynro    = '$myy_hyllynro'
								and hyllytaso   = '$myy_hyllytaso'
								and hyllyvali   = '$myy_hyllyvali'
								and sarjanumero = '$myy_era'
								and myyntirivitunnus = 0
								and ostorivitunnus > 0
								LIMIT 1";
					$lisa_res = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($lisa_res) > 0) {
						$lisa_row = mysql_fetch_array($lisa_res);
						$oslisa = " ostorivitunnus ='$lisa_row[ostorivitunnus]', ";
					}
					else {
						$oslisa = " ostorivitunnus ='', ";
					}

				}
				else {
					$tunken = "ostorivitunnus";
					$oslisa = "";
				}

				$query = "	INSERT into sarjanumeroseuranta
							SET yhtio 		= '$kukarow[yhtio]',
							tuoteno			= '$toimrow[tuoteno]',
							lisatieto 		= '$lisa_row[lisatieto]',
							$tunken 		= '$toimrow[tunnus]',
							$oslisa
							kaytetty		= '$lisa_row[kaytetty]',
							era_kpl			= '',
							laatija			= '$kukarow[kuka]',
							luontiaika		= now(),
							takuu_alku 		= '$lisa_row[takuu_alku]',
							takuu_loppu		= '$lisa_row[takuu_loppu]',
							parasta_ennen	= '$lisa_row[parasta_ennen]',
							hyllyalue   	= '$myy_hyllyalue',
							hyllynro    	= '$myy_hyllynro',
							hyllytaso   	= '$myy_hyllytaso',
							hyllyvali   	= '$myy_hyllyvali',
							sarjanumero 	= '$myy_era'";
				$lisa_res = mysql_query($query) or pupe_error($query);

				$query = "	UPDATE tilausrivi
							SET hyllyalue   = '$myy_hyllyalue',
							hyllynro    	= '$myy_hyllynro',
							hyllytaso   	= '$myy_hyllytaso',
							hyllyvali   	= '$myy_hyllyvali'
							WHERE yhtio 	= '$kukarow[yhtio]'
							and tunnus		= '$toimrow[tunnus]'";
				$lisa_res = mysql_query($query) or pupe_error($query);
				
				$paivitetty++;
			}
			else {
				echo "Rivi‰ ei voi liitt‰‰ $query<br>";
			}
		}
	}

	return $paivitetty;
}

function naytaValinta($laskurow, $row, $tunken1, $tunken2, $lisaaTilausriviTyypit, $from, $loppu) {
	global $yhtiorow, $kukarow;
	
	/*
		$row tulee olla lis‰tt‰v‰ rivi
	*/
		
	$ulos = "";
	
	if ($row["sarjanumeroseuranta"] == "E" or $row["sarjanumeroseuranta"] == "F" or $row["sarjanumeroseuranta"] == "G") {

		if ($row["sarjanumeroseuranta"] == "F") {
			$pepvmlisa1 = " sarjanumeroseuranta.parasta_ennen, ";
			$pepvmlisa2 = ", 17";
		}
		else {
			$pepvmlisa1 = "";
			$pepvmlisa2 = "";
		}

		$query = "	SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
					tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
					sarjanumeroseuranta.sarjanumero era,
					sarjanumeroseuranta.ostorivitunnus,
					$pepvmlisa1
					concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0')) sorttauskentta,
					varastopaikat.nimitys, if(varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
		 			FROM tuote
					JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
					JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
					and concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
					and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
					JOIN sarjanumeroseuranta ON sarjanumeroseuranta.yhtio = tuote.yhtio
					and sarjanumeroseuranta.tuoteno = tuote.tuoteno
					and sarjanumeroseuranta.hyllyalue = tuotepaikat.hyllyalue
					and sarjanumeroseuranta.hyllynro  = tuotepaikat.hyllynro
					and sarjanumeroseuranta.hyllyvali = tuotepaikat.hyllyvali
					and sarjanumeroseuranta.hyllytaso = tuotepaikat.hyllytaso
					and sarjanumeroseuranta.myyntirivitunnus = 0
					and sarjanumeroseuranta.era_kpl != 0
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					and tuote.tuoteno = '$row[tuoteno]'
					GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16 $pepvmlisa2
					ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
		$omavarastores = mysql_query($query) or pupe_error($query);
		
		$paikat 	= "<option value=''>".t("Valitse er‰")."</option>";
		$selpaikka 	= "";

		$query	= "	SELECT sarjanumeroseuranta.sarjanumero era, sarjanumeroseuranta.parasta_ennen
					FROM sarjanumeroseuranta
					WHERE yhtio = '$kukarow[yhtio]'
					and tuoteno = '$row[tuoteno]'
					and myyntirivitunnus = '$row[tunnus]'
					LIMIT 1";
		$sarjares = mysql_query($query) or pupe_error($query);
		$sarjarow = mysql_fetch_array($sarjares);

		$ulos = t("Er‰").": ";
		
		while($alkurow = mysql_fetch_array($omavarastores)) {

			if ($alkurow["hyllyalue"] != "!!M" and
				($alkurow["varastotyyppi"] != "E" or
				$laskurow["varasto"] == $alkurow["varasto"] or
				($alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"]))) {

				if ($yhtiorow["saldo_kasittely"] == "T") {
					$saldoaikalisa = date("Y-m-d");
				}
				else {
					$saldoaikalisa = "";
				}

				list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"], '', '', '', $alkurow["hyllyalue"], $alkurow["hyllynro"], $alkurow["hyllyvali"], $alkurow["hyllytaso"], $laskurow["toim_maa"], $saldoaikalisa, $alkurow["era"]);

				$myytavissa = (float) $myytavissa;

				//Jos er‰ on keksitty k‰sin t‰‰lt‰ ker‰yksest‰
				$query = "	SELECT tyyppi, (varattu+kpl+jt) kpl, tunnus
							FROM tilausrivi
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$row[tuoteno]'
							and tunnus	= '$alkurow[ostorivitunnus]'";
				$lisa_res = mysql_query($query) or pupe_error($query);
				$lisa_row = mysql_fetch_array($lisa_res);
				
				if ((in_array($lisa_row["tyyppi"], $lisaaTilausriviTyypit) or $lisa_row["kpl"] < 0 or $lisa_row["tunnus"] == $row["tunnus"]) and
					(in_array($yhtiorow["puute_jt_oletus"], array('H','O')) or
					$myytavissa >= $row["varattu"] or
					($row["var"] != "P"
					and $alkurow["hyllyalue"] == $row["hyllyalue"]
					and $alkurow["hyllynro"] == $row["hyllynro"]
					and $alkurow["hyllyvali"] == $row["hyllyvali"]
					and $alkurow["hyllytaso"] == $row["hyllytaso"]
					and $sarjarow["era"] == $alkurow["era"]))) {

					$sel = "";

					if ($sarjarow["era"] == $alkurow["era"] and !in_array($row["var"], array("P","S")) and $alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"]) {
						$sel = "SELECTED";

						$selpaikka = "$alkurow[hyllyalue]#$alkurow[hyllynro]#$alkurow[hyllyvali]#$alkurow[hyllytaso]#$alkurow[era]";
					}
					elseif (isset($_POST) and $_POST["era_new_paikka"][$row["tunnus"]] == "$alkurow[hyllyalue]#$alkurow[hyllynro]#$alkurow[hyllyvali]#$alkurow[hyllytaso]#$alkurow[era]") {
						$sel = "SELECTED";

						$selpaikka = "$alkurow[hyllyalue]#$alkurow[hyllynro]#$alkurow[hyllyvali]#$alkurow[hyllytaso]#$alkurow[era]";
					}

					$paikat .= "<option value='$alkurow[hyllyalue]#$alkurow[hyllynro]#$alkurow[hyllyvali]#$alkurow[hyllytaso]#$alkurow[era]' $sel>";

					if (strtoupper($alkurow['varastomaa']) != strtoupper($yhtiorow['maa'])) {
						$paikat .= strtoupper($alkurow['varastomaa'])." ";
					}

					$paikat .= "$alkurow[hyllyalue] $alkurow[hyllynro] $alkurow[hyllyvali] $alkurow[hyllytaso], $alkurow[era]";
					$paikat .= " ($myytavissa)";

					if ($row["sarjanumeroseuranta"] == "F") {
						$paikat .= " ".tv1dateconv($alkurow["parasta_ennen"]);
					}

					$paikat .= "</option>";
				}
			}
		}

		$subbari = " onchange='submit();'";

		if (($row["sarjanumeroseuranta"] == "E" or $row["sarjanumeroseuranta"] == "F" or $row["sarjanumeroseuranta"] == "G") and $yhtiorow["kerayspoikkeama_kasittely"] != '') {
			$subbari = "";
		}

		$ulos = "	<select name='era_new_paikka[$row[tunnus]]' $subbari style='width: 230px;'>
						$paikat
					</select>
					<input type='hidden' name='era_old_paikka[$row[tunnus]]' value='$selpaikka'>
					(<a href='sarjanumeroseuranta.php?tuoteno=".urlencode($row["tuoteno"])."&$tunken2=$row[tunnus]&from=$from&aputoim=$toim$loppu#".urlencode($sarjarow["sarjanumero"])."'>".t("E:nro")."</a>)";

		
	}	
	
	return $ulos;
}

function tarkistaSarjanumerot ($otsikot, $tunnukset, $tunken) {
	global $yhtiorow, $kukarow;
	
	if ($tunnukset > 0) {
		$lisa = "tilausrivi.tunnus in ($tunnukset) and ";
		$indexlisa = " use index (PRIMARY) ";
	}
	else {
		$lisa = "tilausrivi.otunnus in ($otsikot) and ";
		$indexlisa = " use index (yhtio_otunnus) ";
	}
	// katotaan aluks onko yht‰‰n tuotetta sarjanumeroseurannassa t‰ll‰ ker‰yslistalla
	$query = "	SELECT tilausrivi.tunnus, tilausrivi.tuoteno, tilausrivi.varattu, tuote.sarjanumeroseuranta
				FROM tilausrivi $indexlisa
				JOIN tuote on tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta!=''
				WHERE tilausrivi.yhtio='$kukarow[yhtio]' and
				$lisa
				tilausrivi.tyyppi in ('L','G','D','V','W')
				and tilausrivi.var not in ('P','T','U','J')";
	$toimresult = mysql_query($query) or pupe_error($query);
	$virheet = 0;
	
	if (mysql_num_rows($toimresult) > 0) {

		while ($toimrow = mysql_fetch_array($toimresult)) {

			if ($toim == 'SIIRTOTYOMAARAYS' or $toim == 'SIIRTOLISTA') {
				$tunken = "siirtorivitunnus";
			}
			elseif ($toimrow["varattu"] < 0) {
				$tunken = "ostorivitunnus";
			}
			else {
				$tunken = "myyntirivitunnus";
			}

			if ($toimrow["sarjanumeroseuranta"] == "S" or $toimrow["sarjanumeroseuranta"] == "T" or $toimrow["sarjanumeroseuranta"] == "U" or $toimrow["sarjanumeroseuranta"] == "V") {
				$query = "	SELECT count(distinct sarjanumero) kpl, min(sarjanumero) sarjanumero
							FROM sarjanumeroseuranta
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$toimrow[tuoteno]'
							and $tunken = '$toimrow[tunnus]'";
				$sarjares2 = mysql_query($query) or pupe_error($query);
				$sarjarow = mysql_fetch_array($sarjares2);

				if ($sarjarow["kpl"] != abs($toimrow["varattu"])) {
					echo "<font class='error'>".t("Sarjanumeroseurannassa oleville tuotteille on liitett‰v‰ sarjanumero ennen ker‰yst‰")."! ".t("Tuote").": $toimrow[tuoteno].</font><br>";
					$virheet++;
				}
			}
			else {

				$query = "	SELECT count(*) kpl
							FROM sarjanumeroseuranta
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$toimrow[tuoteno]'
							and myyntirivitunnus = '$toimrow[tunnus]'";
				$sarjares2 = mysql_query($query) or pupe_error($query);
				$sarjarow = mysql_fetch_array($sarjares2);

				if ($sarjarow["kpl"] != 1) {
					echo "<font class='error'>".t("Er‰numeroseurannassa oleville tuotteille on liitett‰v‰ er‰numero ennen ker‰yst‰")."! ".t("Tuote").": $toimrow[tuoteno].</font><br>";
					$virheet++;
				}
			}
		}
	}
	
	return $virheet;
}

?>
