<?php
					
	if (!isset($variaatio_tuoteno) and $tuoteno != "")  $variaatio_tuoteno = $tuoteno;
	if (!isset($variaatio_tuoteno) and $tuoteno_array[0] != "")  $variaatio_tuoteno = $tuoteno_array[0];
	
	if ($variaatio_tuoteno != "") {
		
		$variaatiolisaqu = "";
		
		if (count($variaatio_valinta) > 0) {
			foreach($variaatio_valinta as $variaatioindex => $variaatiovalinta) {
				$variaatiolisaqu .= " and tuotteen_parametrit like '%$variaatioindex=$variaatiovalinta%' ";
			}
		}
		
		$query = "	SELECT *
					FROM tuote
					WHERE yhtio = '$kukarow[yhtio]' 
					and tuotteen_parametrit like '%Variaatio=$variaatio_tuoteno;%'
					$variaatiolisaqu ";
		$variaatiores = mysql_query($query) or pupe_error($query);
	}
	
	if (is_resource($variaatiores) and mysql_num_rows($variaatiores) > 0) {
		
		$variaatiorow = mysql_fetch_assoc($variaatiores);
	
		parse_str(str_replace(";", "&", strtolower($variaatiorow["tuotteen_parametrit"])), $variaatio_check);
					
		//Onko meillä moniulotteinen variaatio esim. väri ja koko...
		$variaatiot = explode(",", $variaatio_check["variaatio_jako"]);

		// Syöttörivi
		echo "<table>";		
		echo "<tr><td class='back' colspan='6'><table><tr><th align='left'>".t("Tuote")."</th>";
	
		for ($vi = 0; $vi < count($variaatiot)-1; $vi++) {										
			echo "<th>".ucfirst($variaatiot[$vi])."</th>";
		}
		
		echo "</tr>";
		echo "<tr>";
		echo "<td valign='top'><input type='text' name='variaatio_tuoteno' size='15' maxlength='20' value='$variaatio_tuoteno'></td>";
							
		// Tutkitaan kuinka moni variaatioistamme on jo setattu
		for ($vi = 0; $vi < count($variaatiot)-1; $vi++) {										
							
			$thisvariaatio = array();
			mysql_data_seek($variaatiores, 0);
		
			while($variaatiorow = mysql_fetch_assoc($variaatiores)) {
				parse_str(str_replace(";", "&", strtolower($variaatiorow["tuotteen_parametrit"])), $this_parametrit);

				if (!in_array($this_parametrit[$variaatiot[$vi]], $thisvariaatio)) $thisvariaatio[] = $this_parametrit[$variaatiot[$vi]];
			}

			echo "<td><select name='variaatio_valinta[$variaatiot[$vi]]' onchange='submit();'>";
			echo "<option value=''>Näytä kaikki</option>";
								
			foreach ($thisvariaatio as $ekavar) {
				
				if ($ekavar == $variaatio_valinta[$variaatiot[$vi]]) $sel = "SELECTED";
				else $sel = "";
				
				echo "<option value='$ekavar' $sel>$ekavar</option>";
			}

			echo "</select></td>";					
		}
	
		echo "</tr></table></td></tr>";
		
	
		echo "<tr><td class='back'><br></td></tr><tr>";
		echo "<th align='left'>".t("Tuotenumero")."</th>
				<th align='left'>".t("Määrä")."</th>
				<th align='left'>".t("Var")."</th>
				<th align='left'>".t("Hinta")."</th>
				<th align='left'>".t("Ale")."</th>
				<th align='left'>".t("Netto")."</th>";
		
		if ($toim != "PIKATILAUS") {
			echo "<th align='left'>".t("Alv")."</th>";

			if ($yhtiorow['rivinumero_syotto'] != '') {
				echo "<th align='left'></th>";
			}
		}
		
		echo "<td valign='top' class='back'></td></tr>";
					
		$varilask = 1;

		mysql_data_seek($variaatiores, 0);

		while($varirow = mysql_fetch_assoc($variaatiores)) {
			echo "<tr>";
			echo "<td valign='top'>$varirow[tuoteno] $varirow[nimitys] <input type='hidden' name='tuoteno_array[$varirow[tuoteno]]' value='$varirow[tuoteno]'></td>
					<td valign='top'><input type='text' name='kpl_array[$varirow[tuoteno]]' size='5' maxlength='5' value='".$kpl_array[$varirow["tuoteno"]]."'></td>";
					
			
			if ($toim != "PIKATILAUS") {
				$selh = $selj = $selp = "";

				if ($var_array[$varirow["tuoteno"]] == "H") {
					$selh = "SELECTED";
				}
				elseif ($var_array[$varirow["tuoteno"]] == "J") {
					$selj = "SELECTED";
				}
				elseif ($var_array[$varirow["tuoteno"]] == "P") {
					$selp = "SELECTED";
				}
				elseif ($var_array[$varirow["tuoteno"]] == "W") {
					$selw = "SELECTED";
				}

				echo "<td valign='top'><select name='var_array[$varirow[tuoteno]]'>";
				echo "<option value=''>".t("Oletus")."</option>";
				echo "<option value='J' $selj>".t("JT")."</option>";
				echo "<option value='H' $selh>".t("Hyväksy")."</option>";
				echo "<option value='P' $selp>".t("Puute")."</option>";

				if($laskurow["tila"] == 'V' and $laskurow["tilaustyyppi"] == 'V') {
					echo "<option value='W' $selw>".t("Varastosta")."</option>";
				}

				echo "</select></td>";
			}
			else {
				echo "<td valign='top'><input type='text' name='var_array[$varirow[tuoteno]]' size='2' maxlength='1' value='".$var_array[$varirow["tuoteno"]]."'></td>";
			}
								
			echo "<td valign='top'><input type='text' name='hinta_array[$varirow[tuoteno]]' size='".(5+$yhtiorow['hintapyoristys']-2)."' maxlength='".(12+$yhtiorow['hintapyoristys']-2)."' value='".$hinta_array[$varirow["tuoteno"]]."'></td>
					<td valign='top'><input type='text' name='ale_array[$varirow[tuoteno]]' size='5' maxlength='6' value='".$ale_array[$varirow["tuoteno"]]."'></td>";			
			
			if ($toim != "PIKATILAUS") {
				$chke = $chkn = "";

				if ($netto_array[$varirow["tuoteno"]] == 'N') {
					$chkn = 'SELECTED';
				}
				elseif($netto_array[$varirow["tuoteno"]] == "E") {
					$chke = 'SELECTED';
				}

				echo "<td valign='top'><select name='netto_array[$varirow[tuoteno]]'>";
				echo "<option value=''>".t("Oletus")."</option>";
				echo "<option value='N' $chkn>".t("N-Netto")."</option>";
				echo "<option value='E' $chke>".t("E-Netto")."</option>";
				echo "</select></td>";
			}
			else {
				echo "<td valign='top'><input type='text' name='netto_array[$varirow[tuoteno]]' size='2' maxlength='1' value='".$netto_array[$varirow["tuoteno"]]."'></td>";
			}
			
			if ($toim != "PIKATILAUS") {
				if (isset($alv) and $alv >= 500) {
					echo "<td valign='top'>".t("M.V.")."</td>";
					echo "<input type='hidden' name='alv' value='".$alv_array[$varirow["tuoteno"]]."'>";
				}
				else {
					echo "<td valign='top'>".alv_popup_oletus("alv_array[$varirow[tuoteno]]", $alv_array[$varirow["tuoteno"]], $laskurow["maa"])."</td>";
				}

				if ($yhtiorow['rivinumero_syotto'] != '') {
					echo "<th align='left'></th>";
				}
			}			
			
			if ($varilask == mysql_num_rows($variaatiores)) {
				echo "	<td valign='top' class='back'><input type='submit' value='".t("Lisää")."'></td>
						<td valign='top' class='back'>";
								
				if ($toim == "PIKATILAUS") {
					echo "	<input type='hidden' name='perheid' value = '$perheid'>
							<input type='hidden' name='perheid2' value = '$perheid2'>
							<input type='hidden' name='takaisin' value = '$takaisin'>
							<input type='submit' name='tyhjenna' value='".t("Tyhjennä")."'> <font class='error'>$varaosavirhe</font></td>";
				}
			}
	
			echo "</tr>";
	
			$varilask++;
		}
		
		if ($toim != "PIKATILAUS") {
			echo "<tr>
					<th align='left'>".t("Keräysaika")."</th>
					<th align='left' colspan='2'>".t("Toimitusaika")."</th>
					<th colspan='4' align='left'>".t("Kommentti")."</th>";
		
			if ($yhtiorow['rivinumero_syotto'] != '') {
				echo "<th align='left'>".t("Rivinumero")."</th>";
			}
		
			echo "<td valign='top' class='back'></td></tr>";
			echo "<tr>";
		
			if ($toimaika == "" or $toimaika == "0000-00-00") {
				$toimaika = $laskurow["toimaika"];
			}

			if ($kerayspvm == "" or $kerayspvm == "0000-00-00") {
				$kerayspvm = $laskurow["kerayspvm"];
			}
		
			if ($toimaika == "" or $toimaika == "0000-00-00") {
				$toimkka = date("m",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
				$toimvva = date("Y",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
				$toimppa = date("d",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
			}
			else {
				$toimkka = substr($toimaika, 5, 2);
				$toimvva = substr($toimaika, 0, 4);
				$toimppa = substr($toimaika, 8, 2);
			}
		
			if ($kerayspvm == "" or $kerayspvm == "0000-00-00") {
				$kerayskka = date("m",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
				$keraysvva = date("Y",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
				$keraysppa = date("d",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
			}
			else {
				$kerayskka = substr($kerayspvm, 5, 2);
				$keraysvva = substr($kerayspvm, 0, 4);
				$keraysppa = substr($kerayspvm, 8, 2);
			}
		
			echo "<td valign='top'><input type='text' size='3' name='keraysppa' value='$keraysppa'><input type='text' size='3' name='kerayskka' value='$kerayskka'><input type='text' size='5' name='keraysvva' value='$keraysvva'></td>";		
			echo "<td valign='top' colspan='2'><input type='text' size='3' name='toimppa' value='$toimppa'><input type='text' size='3' name='toimkka' value='$toimkka'><input type='text' size='5' name='toimvva' value='$toimvva'></td>";		
			echo "<td valign='top' colspan='4'><textarea name='kommentti' cols='32' rows='2' wrap='soft'>$kommentti</textarea></td>";
		
			if ($yhtiorow['rivinumero_syotto'] != '') {
				echo "<td valign='top'><input type='text' size='2' name='tilrivinumero' value='$rivinumero'></td>";
			}
		
			if ($yhtiorow["nimityksen_muutos_tilauksella"] == "Y" and ($toim == "RIVISYOTTO" or $toim == "TYOMAARAYS" or ($toim == "TARJOUS" or $laskurow["tilaustyyppi"] == "T"))) {
				echo "</tr>
					<tr>$jarjlisa
						<th colspan='7' align='left'>".t("Tuotteen nimitys")."</th>
						</tr>
					<tr>$jarjlisa
						<td valign='top' colspan='7'><input type='text' maxlength='100' size='75' name='tuotenimitys' value = '$tuotenimitys'></td>";
			}

			echo "<td valign='top' class='back'><input type='submit' name='tyhjenna' value='".t("Tyhjennä")."'>";
		}
		
		echo "</table></form>";
		
		$variaaritorivi = TRUE;
	}
?>