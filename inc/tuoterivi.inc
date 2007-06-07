<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali k‰sittely
	$tyyppi = 1; // oletetaan rivin n‰kyvyys
	
	if (mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;

		//	Glunge..
		$query = " select tuotetyyppi from tuote where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
		$sresult = mysql_query($query) or pupe_error($query);
		$srow = mysql_fetch_array($sresult);
		$tuotetyyppi=$srow["tuotetyyppi"];
		
	}
	
	//	N‰m‰ tuotteet ovat Kielletty viranomaistuotteilla
	if(!in_array($tuotetyyppi, array("A","B"))
				or mysql_field_name($result, $i) == "nimitys"
	 			or mysql_field_name($result, $i) == "tilino"
	 			or mysql_field_name($result, $i) == "kommentoitava"	
				or mysql_field_name($result, $i) == "kohde"
				or mysql_field_name($result, $i) == "status"				
				or mysql_field_name($result, $i) == "projekti"
				or mysql_field_name($result, $i) == "kustp"
				or mysql_field_name($result, $i) == "vihapvm"
				or mysql_field_name($result, $i) == "kehahin") {
		
		//	V‰h‰n speciaali featureita viranomaistuotteille
		if (mysql_field_name($result, $i) == "nimitys" and in_array($tuotetyyppi, array("A","B"))) {
			$tyyppi = 3;
		}
		
		if (mysql_field_name($result, $i) == "tuotetyyppi") {
			// Tyyppi‰ ei saa vaihtaa, jos tuotteella on saldoa
			$query = "	SELECT sum(saldo) saldo
						FROM tuotepaikat
						WHERE yhtio='$kukarow[yhtio]' and tuoteno='$trow[tuoteno]'";
			$sresult = mysql_query($query) or pupe_error($query);
			$srow = mysql_fetch_array($sresult);

			if ($srow['saldo'] == 0) {
				$ulos = "<td><select name='$nimi'>";

				if($trow[$i]=='') $selv="SELECTED";
				if($trow[$i]=='P') $selp="SELECTED";
				if($trow[$i]=='R') $selr="SELECTED";
				if($trow[$i]=='K') $selk="SELECTED";
				if($trow[$i]=='A') $sela="SELECTED";
				if($trow[$i]=='B') $selb="SELECTED";

				$ulos .= "<option value='' $selv>".t("Valmiste")."</option>";
				//$ulos .= "<option value='P' $selp>".t("Puolivalmiste")."</option>";
				$ulos .= "<option value='R' $selr>".t("Raaka-aine")."</option>";
				$ulos .= "<option value='K' $selk>".t("Palvelu")."</option>";
				$ulos .= "<option value='A' $sela>".t("Viranomaistuote - P‰iv‰raha")."</option>";
				$ulos .= "<option value='B' $selb>".t("Viranomaistuote - Muu kulu")."</option>";

				$ulos .= "</select></td>";
			}
			else {
				$ulos="<td>";
				if($trow[$i]=='') $ulos.=t("Valmiste");
				//if($trow[$i]=='P') $ulos.=t("Puolivalmiste");
				if($trow[$i]=='R') $ulos.=t("Raaka-aine");
				if($trow[$i]=='K') $ulos.=t("Palvelu");
				$ulos.=" (Ei voida muuttaa, koska tuotteella on saldoa $srow[0])<input type = 'hidden' name = '$nimi' value = '$trow[$i]' size='$size'>";
				$ulos.="</td>";
			}
			$jatko=0;
		}

		if (mysql_field_name($result, $i) == "kehahin" or
			mysql_field_name($result, $i) == "vihahin" or
			mysql_field_name($result, $i) == "vihapvm" or
			mysql_field_name($result, $i) == "saldo" or
			mysql_field_name($result, $i) == "saldoaika" or
			mysql_field_name($result, $i) == "epakurantti1pvm" or
			mysql_field_name($result, $i) == "epakurantti2pvm") {
			$tyyppi = 2;
		}

		if (mysql_field_name($result, $i) == "tuoteno" and $uusi=='') {
			$tyyppi = 3;
			$tuoteno = $trow[$i]; // otetaan t‰‰ talteen
		}

		if (mysql_field_name($result, $i) == "try") {
			$query = "	SELECT distinct avainsana.selite, ".avain('select')."
						FROM avainsana
						".avain('join','TRY_')."
						WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='TRY'
						ORDER BY avainsana.selite+0";
			$sresult = mysql_query($query) or pupe_error($query);

			$ulos = "<td><select name='$nimi'>";
			while ($srow = mysql_fetch_array($sresult)) {
				$sel = '';
				if ($trow[$i] == $srow[0]) {
					$sel = "selected";
				}
				$ulos .= "<option value='$srow[0]' $sel>$srow[0] $srow[1]</option>";
			}
			$ulos .= "</select></td>";
			$jatko = 0;
		}

		if (mysql_field_name($result, $i) == "osasto") {
			$query = "	SELECT distinct avainsana.selite, ".avain('select')."
						FROM avainsana
						".avain('join','OSASTO_')."
						WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='OSASTO'
						ORDER BY avainsana.selite+0";
			$sresult = mysql_query($query) or pupe_error($query);

			$ulos = "<td><select name='$nimi'>";
			while ($srow = mysql_fetch_array($sresult)) {
				$sel = '';
				if ($trow[$i] == $srow[0]) {
					$sel = "selected";
				}
				$ulos .= "<option value='$srow[0]' $sel>$srow[0] $srow[1]</option>";
			}
			$ulos .= "</select></td>";
			$jatko = 0;
		}

		if (mysql_field_name($result, $i) == "valuutta") {
			$query = "	SELECT nimi, tunnus
						FROM valuu
						WHERE yhtio = '$kukarow[yhtio]'
						ORDER BY jarjestys";
		        $vresult = mysql_query($query) or pupe_error($query);
		        $ulos = "<td><select name='$nimi'>";
		        while ($vrow=mysql_fetch_array($vresult)) {
			        $sel="";
			        if ($trow[$i] == $vrow[0]) {
			                $sel = "selected";
			        }
		        	$ulos .= "<option value = '$vrow[0]' $sel>$vrow[0]</option>";
		        }
			$ulos .= "</select></td>";
			$jatko = 0;
		}

		if (mysql_field_name($result, $i) == "ei_saldoa") {

			$sel = '';
			if ($trow[$i] != '') {
				$sel = "SELECTED";
			}

			$ulos  = "<td><select name='$nimi'>";
			$ulos .= "<option value = ''>".t("Saldollinen tuote")."</option>";
			$ulos .= "<option value = 'o' $sel>".t("Saldoton tuote")."</option>";
			$ulos .= "</select></td>";
			$jatko = 0;
		}

		if (mysql_field_name($result, $i) == "kommentoitava") {

			$sel = '';
			if ($trow[$i] != '') {
				$sel = "SELECTED";
			}

			$ulos  = "<td><select name='$nimi'>";
			$ulos .= "<option value = ''>".t("Normaalik‰sittely")."</option>";
			$ulos .= "<option value = 'o' $sel>".t("Tuotteelle on annettava rivikommentti")."</option>";
			$ulos .= "</select></td>";
			$jatko = 0;
		}

		if (mysql_field_name($result, $i) == "vaaditaan_kpl2") {

			$sel = '';
			if ($trow[$i] != '') {
				$sel = "SELECTED";
			}

			$ulos  = "<td><select name='$nimi'>";
			$ulos .= "<option value = ''>".t("Normaalik‰sittely")."</option>";
			$ulos .= "<option value = 'P' $sel>".t("M‰‰r‰ on perheen is‰n position pituus")."</option>";
			$ulos .= "</select></td>";
			$jatko = 0;
		}

		if (mysql_field_name($result, $i) == "sarjanumeroseuranta") {

			$ulos = "<td><select name='$nimi'>";

			$sel1=$sel2="";

			if ($trow[$i] == '') {
				$sel1 = "selected";
			}
			if ($trow[$i] == 'S') {
				$sel2 = "selected";
			}

			$ulos .= "<option value = ''  $sel1>".t("Ei seurantaa")."</option>";
			$ulos .= "<option value = 'S' $sel2>".t("Sarjanumeroseuranta")."</option>";
			$ulos .= "</select></td>";
			$jatko = 0;
		}


		if (mysql_field_name($result, $i) == "aleryhma") {
			$query = "	SELECT tunnus, ryhma, alennus
						FROM perusalennus
						WHERE yhtio = '$kukarow[yhtio]'
						ORDER BY ryhma";
			$vresult = mysql_query($query) or pupe_error($query);
			$ulos = "<td><select name='$nimi'>";

			while ($vrow=mysql_fetch_array($vresult)) {
				$sel="";
				if ($trow[$i] == $vrow[1]) {
					$sel = "selected";
				}
				$ulos .= "<option value = '$vrow[1]' $sel>$vrow[1]</option>";
			}
			$ulos .= "</select></td>";
			$jatko = 0;
		}

		if (mysql_field_name($result, $i) == "kuvaus" or mysql_field_name($result, $i) == "lyhytkuvaus") {
			$ulos = "<td><textarea cols='35' rows='7' name='$nimi'>$trow[$i]</textarea></td>";
			$jatko = 0;
		}

		if (mysql_field_name($result, $i) == "yksikko") {
			$query = "	SELECT avainsana.tunnus, avainsana.selite, ".avain('select')."
						FROM avainsana
						".avain('join','Y_')."
						WHERE avainsana.yhtio = '$kukarow[yhtio]' and avainsana.laji = 'Y'
						ORDER BY avainsana.selite";
			$vresult = mysql_query($query) or pupe_error($query);
			$ulos = "<td><select name='$nimi'>";

			while ($vrow=mysql_fetch_array($vresult)) {
				$sel="";
				if (strtoupper($trow[$i]) == strtoupper($vrow[1])) {
					$sel = "selected";
				}
				$ulos .= "<option value = '$vrow[1]' $sel>$vrow[1] ($vrow[2])</option>";
			}
			$ulos .= "</select></td>";
			$jatko = 0;
		}

		if (mysql_field_name($result, $i) == "alkuperamaa") {
			$query = "	SELECT distinct koodi, nimi
						FROM maat
						WHERE nimi != ''
						ORDER BY koodi";
			$vresult = mysql_query($query) or pupe_error($query);
			$ulos = "<td><select name='$nimi'>";

			$ulos .= "<option value = '' ></option>";

			while ($vrow=mysql_fetch_array($vresult)) {
				$sel="";
				if ($trow[$i] == $vrow[0]) {
					$sel = "selected";
				}
				$ulos .= "<option value = '$vrow[0]' $sel>$vrow[1]</option>";
			}

			$ulos .= "</select></td>";
			$jatko = 0;
		}

		if (mysql_field_name($result, $i) == "status") {
			$query = "	SELECT avainsana.tunnus, avainsana.selite, ".avain('select')."
						FROM avainsana
						".avain('join','S_')."
						WHERE avainsana.yhtio = '$kukarow[yhtio]' and avainsana.laji = 'S'
						ORDER BY avainsana.selite";
			$vresult = mysql_query($query) or pupe_error($query);
			$ulos = "<td><select name='$nimi'>";

			while ($vrow=mysql_fetch_array($vresult)) {
				$sel="";
				if (strtoupper($trow[$i]) == strtoupper($vrow[1])) {
					$sel = "selected";
				}
				$ulos .= "<option value = '$vrow[1]' $sel>$vrow[1] ($vrow[2])</option>";
			}
			$ulos .= "</select></td>";
			$jatko = 0;
		}
		
		if (mysql_field_name($result, $i) == "alv") {
				require ('alvpopup.inc');
				if ($trow[$i]=='') $trow[$i]=alv_oletus();
				$ulos = "<td>".alv_popup($nimi,$trow[$i])."</td>";
				$jatko = 0;
		}

		if (mysql_field_name($result, $i) == "hinnastoon") {

				$sel="";
				if ($trow[$i] == 'E') {
					$sel = "selected";
				}

				$ulos = "<td><select name = '$nimi'>
					<option value=''>".t("Tuote n‰ytet‰‰n hinnastossa")."</option>
					<option value='E' $sel>".t("Tuotetta ei n‰ytet‰ hinnastossa")."</option>
					</select></td>";
				$jatko = 0;
		}

		if (mysql_field_name($result, $i) == "myyjanro") {

			$query = "select distinct myyja, nimi from kuka where yhtio='$kukarow[yhtio]' and myyja>0 order by myyja";
			$vresult = mysql_query($query) or pupe_error($query);

			$ulos = "<td><select name='$nimi'>";
			$ulos .= "<option value = '' >".t("Ei myyj‰‰")."</option>";

			while ($vrow=mysql_fetch_array($vresult)) {
				$sel="";
				if ($trow[$i] == $vrow['myyja']) {
					$sel = "selected";
				}
				$ulos .= "<option value = '$vrow[myyja]' $sel>$vrow[myyja] - $vrow[nimi]</option>";
			}

			$ulos .= "</select></td>";
			$jatko = 0;
		}

		if (mysql_field_name($result, $i) == "ostajanro") {

			$query = "select distinct myyja, nimi from kuka where yhtio='$kukarow[yhtio]' and myyja>0 order by myyja";
			$vresult = mysql_query($query) or pupe_error($query);

			$ulos = "<td><select name='$nimi'>";
			$ulos .= "<option value = '' >".t("Ei ostajaa")."</option>";

			while ($vrow=mysql_fetch_array($vresult)) {
				$sel="";
				if ($trow[$i] == $vrow['myyja']) {
					$sel = "selected";
				}
				$ulos .= "<option value = '$vrow[myyja]' $sel>$vrow[myyja] - $vrow[nimi]</option>";
			}

			$ulos .= "</select></td>";
			$jatko = 0;
		}

		if (mysql_field_name($result, $i) == "kustp") {
			$query = "	SELECT tunnus, nimi
						FROM kustannuspaikka
						WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'K'
						ORDER BY nimi";
			$vresult = mysql_query($query) or pupe_error($query);
			$ulos = "<td><select name='$nimi'><option value=' '>".t("Ei kustannuspaikkaa")."</option>";

			while ($vrow=mysql_fetch_array($vresult)) {
				$sel="";
				if ($trow[$i] == $vrow[0]) {
					$sel = "selected";
				}
				$ulos .= "<option value = '$vrow[0]' $sel>$vrow[1]</option>";
			}
			$ulos .= "</select></td>";
			$jatko = 0;
		}

		if (mysql_field_name($result, $i) == "kohde") {
			$query = "	SELECT tunnus, nimi
						FROM kustannuspaikka
						WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'O'
						ORDER BY nimi";
			$vresult = mysql_query($query) or pupe_error($query);
			$ulos = "<td><select name='$nimi'><option value=' '>".t("Ei kohdetta")."</option>";

			while ($vrow=mysql_fetch_array($vresult)) {
				$sel="";
				if ($trow[$i] == $vrow[0]) {
					$sel = "selected";
				}
				$ulos .= "<option value = '$vrow[0]' $sel>$vrow[1]</option>";
			}
			$ulos .= "</select></td>";
			$jatko = 0;
		}

		if (mysql_field_name($result, $i) == "projekti") {
			$query = "	SELECT tunnus, nimi
						FROM kustannuspaikka
						WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'P'
						ORDER BY nimi";
			$vresult = mysql_query($query) or pupe_error($query);
			$ulos = "<td><select name='$nimi'><option value=' '>".t("Ei projektia")."</option>";

			while ($vrow=mysql_fetch_array($vresult)) {
				$sel="";
				if ($trow[$i] == $vrow[0]) {
					$sel = "selected";
				}
				$ulos .= "<option value = '$vrow[0]' $sel>$vrow[1]</option>";
			}
			$ulos .= "</select></td>";
			$jatko = 0;
		}
		
	}
	else {
		$tyyppi = 4;
	}

?>
