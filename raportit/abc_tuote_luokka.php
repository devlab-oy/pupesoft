<?php

	echo "<font class='head'>".t("ABC-Analyysiä: ABC-luokka")." $ryhmanimet[$luokka]<hr></font>";

	//ryhmäjako
	$ryhmanimet   = array('A-30','B-20','C-15','D-15','E-10','F-05','G-03','H-02','I-00');
	$ryhmaprossat = array(30.00,20.00,15.00,15.00,10.00,5.00,3.00,2.00,0.00);

	if (trim($saapumispp) != '' and trim($saapumiskk) != '' and trim($saapumisvv) != '') {
		$saapumispp = $saapumispp; 
		$saapumiskk = $saapumiskk;  
		$saapumisvv	= $saapumisvv;
	}
	elseif (trim($saapumispvm) != '') {
		list($saapumisvv, $saapumiskk, $saapumispp) = split('-', $saapumispvm);
	}

	$lisa_haku_osasto 		 = "";
	$lisa_haku_try 			 = "";
	$lisa_haku_tme 			 = "";
	$lisa_haku_malli 		 = "";
	$lisa_haku_myyja		 = "";
	$lisa_haku_ostaja		 = "";

	if (!isset($mul_osasto)) {
		$mul_osasto = array();
	}

	if (!isset($mul_try)) {
		$mul_try = array();
	}

	if (!isset($mul_tme)) {
		$mul_tme = array();
	}

	$ulisa = '';
	$ulisa_ilman_os = '';
	$ulisa_ilman_try = '';

	// jos on valittu jotakin dropdowneista (muu kuin osasto) niin tehdään niillä rajaukset muihin dropdowneihin
	if (count($mul_osasto) > 0 or count($mul_try) > 0 or count($mul_tme) > 0) {
		if (count($mul_osasto) > 0) {
			$osastot = '';

			foreach ($mul_osasto as $osx) {
				if (trim($osx) != '') {
					$osx = trim(mysql_real_escape_string($osx));
					$osastot .= "'$osx',";
				}
			}

			$osastot = substr($osastot, 0, -1);
		
			if (trim($osastot) != '') {
				$lisa_haku_osasto = " and tuote.osasto in ($osastot) ";
				$lisa .= " and abc_aputaulu.osasto in ($osastot) ";
				$ulisa .= "&mul_osasto[]=".urlencode($osastot);
			}
		}

		if (count($mul_try) > 0) {
			$tryt = '';

			foreach ($mul_try as $tryx) {
				if (trim($tryx) != '') {
					$tryx = trim(mysql_real_escape_string($tryx));
					$tryt .= "'$tryx',";
				}
			}

			$tryt = substr($tryt, 0, -1);
		
			if (trim($tryt) != '') {
				$lisa_haku_try = " and tuote.try in ($tryt) ";
				$lisa .= " and abc_aputaulu.try in ($tryt) ";
				$ulisa .= "&mul_try[]=".urlencode($tryt);
				$ulisa_ilman_os .= "&mul_try[]=".urlencode($tryt);
			}
		}

		if (count($mul_tme) > 0) {
			$tmet = '';

			foreach ($mul_tme as $tmex) {
				if (trim($tmex) != '') {
					$tmex = trim(mysql_real_escape_string(urldecode($tmex)));
					$tmet .= "'$tmex',";
				}
			}

			$tmet = substr($tmet, 0, -1);
		
			if (trim($tmet) != '') {
				$lisa_haku_tme = " and tuote.tuotemerkki in ($tmet) ";
				$lisa .= " and abc_aputaulu.tuotemerkki in ($tmet) ";
				$ulisa .= "&mul_tme[]=".urlencode($tmet);
				$ulisa_ilman_os .= "&mul_tme[]=".urlencode($tmet);
				$ulisa_ilman_try .= "&mul_tme[]=".urlencode($tmet);	
			}
		}

		if (count($mul_malli) > 0) {
			$mallit = '';

			foreach ($mul_malli as $mallix) {
				if (trim($mallix) != '') {
					if (count($_GET['mul_malli']) > 0) {
						$mallix = rawurldecode($mallix);
					}
					$mallit .= "'".mysql_real_escape_string($mallix)."',";
					$ulisa .= "&mul_malli[]=".rawurlencode($mallix);
					$ulisa_ilman_os .= "&mul_malli[]=".rawurlencode($mallix);
					$ulisa_ilman_try .= "&mul_malli[]=".rawurlencode($mallix);
				}
			}

			$mallit = substr($mallit, 0, -1);
			
			if (trim($mallit) != '') {
				$lisa_haku_malli = " and tuote.malli in ($mallit) ";
				$lisa .= " and abc_aputaulu.malli in ($mallit) ";
			}
		}

		if (count($mul_tuotemyyja) > 0) {
			$tuotemyyjat = '';

			foreach ($mul_tuotemyyja as $tuotemyyjax) {
				if (trim($tuotemyyjax) != '') {
					if (count($_GET['mul_tuotemyyja']) > 0) {
						$tuotemyyjax = rawurldecode($tuotemyyjax);
					}
					$tuotemyyjat .= "'".mysql_real_escape_string($tuotemyyjax)."',";
					$ulisa .= "&mul_tuotemyyja[]=".rawurlencode($tuotemyyjax);
					$ulisa_ilman_os .= "&mul_tuotemyyja[]=".rawurlencode($tuotemyyjax);
					$ulisa_ilman_try .= "&mul_tuotemyyja[]=".rawurlencode($tuotemyyjax);
				}
			}

			$tuotemyyjat = substr($tuotemyyjat, 0, -1);
			
			if (trim($tuotemyyjat) != '') {
				$lisa_haku_myyja = " and kuka.myyja in ($tuotemyyjat) ";
				$lisa .= " and abc_aputaulu.myyjanro in ($tuotemyyjat) ";
			}
		}

		if (count($mul_tuoteostaja) > 0) {
			$tuoteostajat = '';

			foreach ($mul_tuoteostaja as $tuoteostajax) {
				if (trim($tuoteostajax) != '') {
					if (count($_GET['mul_tuoteostaja']) > 0) {
						$tuoteostajax = rawurldecode($tuoteostajax);
					}
					$tuoteostajat .= "'".mysql_real_escape_string($tuoteostajax)."',";
					$ulisa .= "&mul_tuoteostaja[]=".rawurlencode($tuoteostajax);
					$ulisa_ilman_os .= "&mul_tuoteostaja[]=".rawurlencode($tuoteostajax);
					$ulisa_ilman_try .= "&mul_tuoteostaja[]=".rawurlencode($tuoteostajax);
				}
			}

			$tuoteostajat = substr($tuoteostajat, 0, -1);
			
			if (trim($tuoteostajat) != '') {
				$lisa_haku_ostaja = " and kuka.myyja in ($tuoteostajat) ";
				$lisa .= " and abc_aputaulu.ostajanro in ($tuoteostajat) ";
			}
		}
	}

	$orderlisa = "ORDER BY avainsana.jarjestys, avainsana.selite+0";
	
	// piirrellään formi
	echo "<form action='$PHP_SELF' method='post' autocomplete='OFF'>";
	echo "<input type='hidden' name='tee' value='LUOKKA'>";
	echo "<input type='hidden' name='toim' value='$toim'>";
	
	echo "<table style='display:inline;'>";
	echo "<tr><th>",t("Osasto"),"</th></tr>";
	echo "<tr>";

	// tehdään avainsana query
	$query = "	SELECT DISTINCT avainsana.selite,
	            IFNULL((SELECT avainsana_kieli.selitetark
	            FROM avainsana as avainsana_kieli
	            WHERE avainsana_kieli.yhtio = avainsana.yhtio
	            and avainsana_kieli.laji = avainsana.laji
	            and avainsana_kieli.selite = avainsana.selite
	            and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selitetark) selitetark
	            FROM avainsana
	            WHERE avainsana.yhtio = '$kukarow[yhtio]'
	            and avainsana.laji = 'OSASTO'
	            and avainsana.kieli in ('$yhtiorow[kieli]', '')
	            $orderlisa";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<td nowrap valign='top' class='back'><select name='mul_osasto[]' multiple size='7' onchange='submit();'>";
	echo "<option value=''>".t("Ei valintaa")."</option>";

	while($sxrow = mysql_fetch_array ($sresult)){
		$sel = '';

		if (count($mul_osasto) > 0) {
			if (in_array(trim($sxrow['selite']), $mul_osasto)) {
				$sel = 'SELECTED';
			}
		}
	
		echo "<option value='$sxrow[selite]' $sel>";
		if ($yhtiorow['naytetaan_kaunis_os_try'] == '') {
			echo $sxrow['selite']." ";
		}
		echo "$sxrow[selitetark]</option>";
	}
	echo "</select></td>";
	echo "</tr></table>";

	if ($lisa_haku_osasto == "") {
		$query = "	SELECT DISTINCT avainsana.selite,
		            IFNULL((SELECT avainsana_kieli.selitetark
		            FROM avainsana as avainsana_kieli
		            WHERE avainsana_kieli.yhtio = avainsana.yhtio
		            and avainsana_kieli.laji = avainsana.laji
		            and avainsana_kieli.selite = avainsana.selite
		            and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selitetark) selitetark
		            FROM avainsana
		            WHERE avainsana.yhtio = '$kukarow[yhtio]'
		            and avainsana.laji = 'TRY'
		            and avainsana.kieli in ('$yhtiorow[kieli]', '')
					$orderlisa";
	}
	else {
		$query = "	SELECT distinct avainsana.selite,
					IFNULL((SELECT avainsana_kieli.selitetark
			        FROM avainsana as avainsana_kieli
			        WHERE avainsana_kieli.yhtio = avainsana.yhtio
			        and avainsana_kieli.laji = avainsana.laji
			        and avainsana_kieli.selite = avainsana.selite
			        and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selitetark) selitetark
					FROM tuote
					JOIN avainsana ON (avainsana.yhtio = tuote.yhtio and tuote.try = avainsana.selite and avainsana.laji = 'TRY' and avainsana.kieli in ('$yhtiorow[kieli]', ''))
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					$lisa_haku_osasto
					$orderlisa";
	}

	$sresult = mysql_query($query) or pupe_error($query);

	echo "<table style='display:inline;'><tr><th>",t("Tuoteryhmä"),"</th></tr>";
	echo "<tr><td nowrap valign='top' class='back'><select name='mul_try[]' onchange='submit();' multiple='TRUE' size='7'>";
	echo "<option value=''>".t("Ei valintaa")."</option>";

	while($srow = mysql_fetch_array ($sresult)){
		$sel = '';

		if (count($mul_try) > 0 and in_array(trim($srow['selite']), $mul_try)) {
			$sel = 'SELECTED';
		}

		echo "<option value='$srow[selite]' $sel>";
		if ($yhtiorow['naytetaan_kaunis_os_try'] == '') {
			echo $srow['selite']." ";
		}
		echo "$srow[selitetark]</option>";
	}
	echo "</select></td>";
	echo "</tr></table>";

	if ($lisa_haku_osasto == "" and $lisa_haku_try == "") {
		$query = "	SELECT avainsana.selite, avainsana.selitetark		         
		            FROM avainsana
		            WHERE avainsana.yhtio 	= '$kukarow[yhtio]'
		            and avainsana.laji 		= 'TUOTEMERKKI'
					$orderlisa";
	}
	else {
		$query = "	SELECT distinct avainsana.selite, avainsana.selitetark
					FROM tuote
					JOIN avainsana ON (avainsana.yhtio = tuote.yhtio and tuote.tuotemerkki = avainsana.selite and avainsana.laji = 'TUOTEMERKKI')
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					$lisa_haku_osasto
					$lisa_haku_try
					$orderlisa";
	}
	$sresult = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($sresult) > 0) {
		echo "<table style='display:inline;'><tr><th>",t("Tuotemerkki"),"</th></tr>";
		echo "<tr><td nowrap valign='top' class='back'>";
		echo "<select name='mul_tme[]' multiple='TRUE' size='7' onchange='submit();'>";
		echo "<option value=''>",t("Ei valintaa"),"</option>";

		while($srow = mysql_fetch_array ($sresult)){
			$sel = '';

			if (count($mul_tme) > 0 and in_array(trim($srow['selite']), $mul_tme)) {
				$sel = 'SELECTED';
			}

			echo "<option value='$srow[selite]' $sel>$srow[selite]</option>";
		}

		echo "</select></td>";
		echo "</tr></table>";
	}

	if ($lisa_haku_tme != '' or  $lisa_haku_try != '') {
		$query = "	SELECT DISTINCT tuote.malli
					FROM tuote
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					and tuote.malli != ''
					$lisa_haku_osasto
					$lisa_haku_try
					$lisa_haku_tme
					ORDER BY malli";
		$sxresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($sxresult) > 0) {
			echo "<table style='display:inline;'><tr><th>",t("Malli"),"</th></tr>";
			echo "<tr><td nowrap valign='top' class='back'>";
			echo "<select name='mul_malli[]' multiple='TRUE' size='7' onchange='submit();'>";
			echo "<option value=''>",t("Ei valintaa"),"</option>";

			while($mallirow = mysql_fetch_array ($sxresult)){
				$sel = '';

				if (count($mul_malli) > 0 and in_array(trim($mallirow['malli']), $mul_malli)) {
					$sel = 'SELECTED';
				}

				echo "<option value='$mallirow[malli]' $sel>$mallirow[malli]</option>";
			}

			echo "</select>";
			echo "</td>";
			echo "</tr></table>";
		}
	}

	echo "<table style='display:inline;'>";
	echo "<tr><th>",t("Tuotemyyjä"),"</th></tr>";
	echo "<tr>";

	// tehdään query
	$query = "	SELECT DISTINCT myyja, nimi 
				FROM kuka 
				WHERE yhtio = '$kukarow[yhtio]' 
				AND myyja>0
				ORDER BY myyja";
	$sresult = mysql_query($query) or pupe_error($query);

	/*
	if ($tuotemyyja == "KAIKKI") $sel = "selected";
	echo "<option value='KAIKKI' $sel>".t("Tuotemyyjittäin")."</option>";
	*/

	echo "<td nowrap valign='top' class='back'><select name='mul_tuotemyyja[]' multiple size='7' onchange='submit();'>";
	echo "<option value=''>".t("Ei valintaa")."</option>";

	while($sxrow = mysql_fetch_array ($sresult)){
		$sel = '';

		if (count($mul_tuotemyyja) > 0) {
			if (in_array(trim($sxrow['myyja']), $mul_tuotemyyja)) {
				$sel = 'SELECTED';
			}
		}
	
		echo "<option value='$sxrow[myyja]' $sel>$sxrow[myyja] $sxrow[nimi]</option>";
	}
	echo "</select></td>";
	echo "</tr></table>";

	echo "<table style='display:inline;'>";
	echo "<tr><th>",t("Tuoteostaja"),"</th></tr>";
	echo "<tr>";

	$query = "	SELECT distinct myyja, nimi 
				FROM kuka 
				WHERE yhtio='$kukarow[yhtio]' 
				AND myyja>0
				ORDER BY myyja";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<td nowrap valign='top' class='back'><select name='mul_tuoteostaja[]' multiple size='7' onchange='submit();'>";
	echo "<option value=''>".t("Ei valintaa")."</option>";

	while($sxrow = mysql_fetch_array ($sresult)){
		$sel = '';

		if (count($mul_tuoteostaja) > 0) {
			if (in_array(trim($sxrow['myyja']), $mul_tuoteostaja)) {
				$sel = 'SELECTED';
			}
		}
	
		echo "<option value='$sxrow[myyja]' $sel>$sxrow[myyja] $sxrow[nimi]</option>";
	}
	echo "</select></td>";
	echo "</tr></table>";

	echo "<br>";

	echo "<table style='display:inline;'>";
	echo "<tr>";
	echo "<th>".t("Valitse luokka").":</th>";
	echo "<td></td><td><select name='luokka'>";
	echo "<option value=''>Valitse luokka</option>";

	$sel = array();
	$sel[$luokka] = "selected";

	$i=0;
	foreach ($ryhmanimet as $nimi) {
		echo "<option value='$i' $sel[$i]>$nimi</option>";
		$i++;
	}

	echo "</select></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<th>".t("Syötä viimeinen saapumispäivä").":</th>";
	echo "	<td><input type='text' name='saapumispp' value='$saapumispp' size='2'>
			<input type='text' name='saapumiskk' value='$saapumiskk' size='2'>
			<input type='text' name='saapumisvv' value='$saapumisvv'size='4'></td><td></td></tr>";
	
	echo "<tr>";
	echo "<th>".t("Taso").":</th>";
	echo "<td></td>";
	
	if ($lisatiedot != '') $sel = "selected";
	else $sel = "";
	
	echo "<td><select name='lisatiedot'>";
	echo "<option value=''>".t("Normaalitiedot")."</option>";
	echo "<option value='TARK' $sel>".t("Näytetään kaikki sarakkeet")."</option>";
	echo "</select></td>";
	echo "<td class='back'><input type='submit' value='".t("Aja raportti")."'></td>";
	echo "</tr>";
	
	echo "</form>";
	echo "</table><br>";

	if (count($haku) > 0) {
		foreach ($haku as $kentta => $arvo) {
			if (strlen($arvo) > 0 and $kentta != 'kateosuus') {
				$lisa  .= " and abc_aputaulu.$kentta like '%$arvo%'";
				$ulisa2 .= "&haku[$kentta]=$arvo";
			}
			if (strlen($arvo) > 0 and $kentta == 'kateosuus') {
				$hav = "HAVING abc_aputaulu.kateosuus like '%$arvo%' ";
				$ulisa2 .= "&haku[$kentta]=$arvo";
			}
		}
	}

	if (strlen($order) > 0) {
		$jarjestys = $order." ".$sort;
	}
	else {
		$jarjestys = "abc_aputaulu.luokka, $abcwhat desc";
	}
	
	$saapumispvmlisa = "";

	if (trim($saapumispp) != '' and trim($saapumiskk) != '' and trim($saapumisvv) != '') {
		$saapumispvm = "$saapumisvv-$saapumiskk-$saapumispp";
		$saapumispvmlisa = " and abc_aputaulu.saapumispvm <= '$saapumispvm' ";
	}

	//kauden yhteismyynnit ja katteet
	$query = "	SELECT
				sum(summa) yhtmyynti,
				sum(kate)  yhtkate
				FROM abc_aputaulu
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi='$abcchar'
				and luokka = '$luokka'
				$lisa
				$saapumispvmlisa";
	$sumres = mysql_query($query) or pupe_error($query);
	$sumrow = mysql_fetch_array($sumres);

	if ($sumrow["yhtkate"] == 0) {
		$sumrow["yhtkate"] = 0.01;
	}
	
	//haetaan rivien arvot	
	$query = "	SELECT
				abc_aputaulu.luokka,
				abc_aputaulu.tuoteno,
				abc_aputaulu.nimitys,
				abc_aputaulu.osasto,
				abc_aputaulu.tulopvm,
				abc_aputaulu.try,
				abc_aputaulu.tuotemerkki,
				abc_aputaulu.myyjanro,
				abc_aputaulu.ostajanro,
				abc_aputaulu.malli,
				abc_aputaulu.mallitarkenne,
				abc_aputaulu.saapumispvm,
				abc_aputaulu.saldo,
				abc_aputaulu.summa,
				abc_aputaulu.kate,
				abc_aputaulu.katepros,
				abc_aputaulu.kate/$sumrow[yhtkate] * 100	kateosuus,
				abc_aputaulu.vararvo,
				abc_aputaulu.varaston_kiertonop,				
				abc_aputaulu.katepros * abc_aputaulu.varaston_kiertonop kate_kertaa_kierto,				
				abc_aputaulu.myyntierankpl,
				abc_aputaulu.myyntieranarvo,
				abc_aputaulu.rivia,
				abc_aputaulu.kpl,
				abc_aputaulu.puuterivia,
				abc_aputaulu.palvelutaso,
				abc_aputaulu.ostoerankpl,
				abc_aputaulu.ostoeranarvo,
				abc_aputaulu.osto_rivia,
				abc_aputaulu.osto_kpl,
				abc_aputaulu.osto_summa,
				abc_aputaulu.kustannus,
				abc_aputaulu.kustannus_osto,
				abc_aputaulu.kustannus_yht,
				abc_aputaulu.kate-abc_aputaulu.kustannus_yht total,
				tuote.ei_varastoida
				FROM abc_aputaulu
				JOIN tuote ON abc_aputaulu.tuoteno = tuote.tuoteno and tuote.yhtio = '$kukarow[yhtio]'
				WHERE abc_aputaulu.yhtio = '$kukarow[yhtio]'
				and abc_aputaulu.tyyppi= '$abcchar'
				and abc_aputaulu.luokka = '$luokka'
				$saapumispvmlisa
				$lisa
				$hav
				ORDER BY $jarjestys";							
	$res = mysql_query($query) or pupe_error($query);

	echo "<table>";

	echo "<tr>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=luokka&sort=asc$ulisa2'>".t("ABC")."<br>".t("Luokka")."</th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=tuoteno&sort=asc$ulisa2'>".t("Tuoteno")."</a><br>&nbsp;</th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=nimitys&sort=asc$ulisa2'>".t("Nimitys")."</a><br>&nbsp;</th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=osasto&sort=asc$ulisa2'>".t("Osasto")."</a><br>&nbsp;</th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=try&sort=asc$ulisa2'>".t("Try")."</a><br>&nbsp;</th>";
	
	if ($lisatiedot == "TARK") {
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=myyjanro&sort=asc$ulisa2'>".t("Myyjä")."</a><br>&nbsp;</th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=ostajanro&sort=asc$ulisa2'>".t("Ostaja")."</a><br>&nbsp;</th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=malli&sort=asc$ulisa2'>".t("Malli")."</a><br>&nbsp;</th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=mallitarkenne&sort=asc$ulisa2'>".t("Mallitarkenne")."</a><br>&nbsp;</th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=saapumispvm&sort=asc$ulisa2'>".t("Viimeinen")."<br>".t("Saapumispvm")."</a><br>&nbsp;</th>";
		echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=saldo&sort=asc$ulisa2'>".t("Saldo")."</a><br>&nbsp;</th>";
	}		
	
	if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=tulopvm&sort=desc$ulisa2'>".t("Tulopvm")."</a><br>&nbsp;</th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=summa&sort=desc$ulisa2'>".t("Myynti")."<br>".t("tot")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=kate&sort=desc$ulisa2'>".t("Kate")."<br>".t("tot")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=katepros&sort=desc$ulisa2'>".t("Kate")."<br>%</a></th>";
	if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=kateosuus&sort=desc$ulisa2'>".t("Osuus")." %<br>".t("kat").".</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=vararvo&sort=desc$ulisa2'>".t("Varast").".<br>".t("arvo")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=varaston_kiertonop&sort=desc$ulisa2'>".t("Varast").".<br>".t("kiert").".</a></th>";	
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=kate_kertaa_kierto&sort=desc$ulisa2'>".t("Kate")."% x<br>".t("kiert").".</a></th>";	
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=kpl&sort=desc$ulisa2'>".t("Myydyt")."<br>".t("KPL")."</a></th>";
	if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=myyntierankpl&sort=desc$ulisa2'>".t("Myyerä")."<br>".t("KPL")."</a></th>";
	if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=myyntieranarvo&sort=desc$ulisa2'>".t("Myyerä")."<br>$yhtiorow[valkoodi]</a></th>";
	if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=rivia&sort=desc$ulisa2'>Myyty<br>".t("rivejä")."</a></th>";
	if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=puuterivia&sort=desc$ulisa2'>".t("Puute")."<br>".t("rivejä")."</a></th>";
	echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=palvelutaso&sort=desc$ulisa2'>".t("Palvelu")."-<br>".t("taso")." %</a></th>";
	if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=ostoerankpl&sort=desc$ulisa2'>".t("Ostoerä")."<br>".t("KPL")."</a></th>";
	if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=ostoeranarvo&sort=desc$ulisa2'>".t("Ostoerä")."<br>$yhtiorow[valkoodi]</a></th>";
	if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=osto_rivia&sort=desc$ulisa2'>".t("Ostettu")."<br>".t("rivejä")."</a></th>";
	if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=kustannus&sort=desc$ulisa2'>".t("Myynn").".<br>".t("kustan").".</a></th>";
	if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=kustannus_osto&sort=desc$ulisa2'>".t("Oston")."<br>".t("kustan").".</a></th>";
	if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=kustannus_yht&sort=desc$ulisa2'>".t("Kustan").".<br>".t("yht")."</a></th>";
	if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=total&sort=desc$ulisa2'>".t("Kate -")."<br>".t("Kustannus")."</a></th>";
	

	echo "<form action='$PHP_SELF?tee=LUOKKA&luokka=$luokka' method='post'>";
	echo "<input type='hidden' name='toim' value='$toim'>";
	echo "<tr>";
	echo "<th><input type='text' name='haku[luokka]' value='$haku[luokka]' size='5'></th>";
	echo "<th><input type='text' name='haku[tuoteno]' value='$haku[tuoteno]' size='5'></th>";
	echo "<th><input type='text' name='haku[nimitys]' value='$haku[nimitys]' size='5'></th>";
	echo "<th><input type='text' name='haku[osasto]' value='$haku[osasto]' size='5'></th>";
	echo "<th><input type='text' name='haku[try]' value='$haku[try]' size='5'></th>";
	
	if ($lisatiedot == "TARK") {
		echo "<th><input type='text' name='haku[myyjanro]' value='$haku[myyjanro]' size='5'></th>";
		echo "<th><input type='text' name='haku[ostajanro]' value='$haku[ostajanro]' size='5'></th>";
		echo "<th><input type='text' name='haku[malli]' value='$haku[malli]' size='5'></th>";
		echo "<th><input type='text' name='haku[mallitarkenne]' value='$haku[mallitarkenne]' size='5'></th>";
		echo "<th><input type='text' name='haku[saapumispvm]' value='$haku[saapumispvm]' size='5'></th>";
		echo "<th><input type='text' name='haku[saldo]' value='$haku[saldo]' size='5'></th>";
	}
	
	if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[tulopvm]' value='$haku[tulopvm]' size='5'></th>";
	echo "<th><input type='text' name='haku[summa]' value='$haku[summa]' size='5'></th>";
	echo "<th><input type='text' name='haku[kate]' value='$haku[kate]' size='5'></th>";
	echo "<th><input type='text' name='haku[katepros]' value='$haku[katepros]' size='5'></th>";
	if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[kateosuus]' value='$haku[kateosuus]' size='5'></th>";
	echo "<th><input type='text' name='haku[vararvo]' value='$haku[vararvo]' size='5'></th>";
	echo "<th><input type='text' name='haku[varaston_kiertonop]' value='$haku[varaston_kiertonop]' size='5'></th>";
	echo "<th><input type='text' name='haku[kate_kertaa_kierto]' value='$haku[kate_kertaa_kierto]' size='5'></th>";
	echo "<th><input type='text' name='haku[kpl]' value='$haku[kpl]' size='5'></th>";
	if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[myyntierankpl]' value='$haku[myyntierankpl]' size='5'></th>";
	if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[myyntieranarvo]' value='$haku[myyntieranarvo]' size='5'></th>";
	if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[rivia]' value='$haku[rivia]' size='5'></th>";
	if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[puuterivia]' value='$haku[puuterivia]' size='5'></th>";
	echo "<th><input type='text' name='haku[palvelutaso]' value='$haku[palvelutaso]' size='5'></th>";
	if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[ostoerankpl]' value='$haku[ostoerankpl]' size='5'></th>";
	if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[ostoeranarvo]' value='$haku[ostoeranarvo]' size='5'></th>";
	if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[osto_rivia]'	value='$haku[osto_rivia]' size='5'></th>";
	if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[kustannus]' value='$haku[kustannus]' size='5'></th>";
	if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[kustannus_osto]'value='$haku[kustannus_osto]' size='5'></th>";
	if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[kustannus_yht]' value='$haku[kustannus_yht]' size='5'></th>";
	if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[total]' value='$haku[total]' size='5'></th>";
	echo "<td class='back'><input type='Submit' value='".t("Etsi")."'></td></form></tr>";

	
		//jos rivejä ei löydy
	if (mysql_num_rows($res) == 0) {
		echo "</table>";
	}
	else {
		while ($row = mysql_fetch_array($res)) {
									
			if (strtoupper($row['ei_varastoida']) == 'O') {
				$row['ei_varastoida'] = "<font style='color:FF0000'>".t("Ei varastoitava")."</font>";
			}
			else {
				$row['ei_varastoida'] = "";
			}		
			
			echo "<tr>";
			echo "<td valign='top'><a href='$PHP_SELF?toim=$toim&tee=YHTEENVETO$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot'>".$ryhmanimet[$row["luokka"]]."</a></td>";
			echo "<td valign='top'><a href='../tuote.php?tee=Z&tuoteno=$row[tuoteno]'>$row[tuoteno]</a></td>";
			echo "<td valign='top'>$row[nimitys] $row[ei_varastoida]</td>";		
			echo "<td valign='top'><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY$ulisa_ilman_os&mul_osasto[]=$row[osasto]&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot'>$row[osasto]</a></td>";
			echo "<td valign='top'><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY$ulisa_ilman_try&mul_osasto[]=$row[osasto]&mul_try[]=$row[try]&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot'>$row[try]</a></td>";
			
			if ($lisatiedot == "TARK") {
				$query = "	SELECT distinct myyja, nimi 
							FROM kuka 
							WHERE yhtio='$kukarow[yhtio]' 
							AND myyja = '$row[myyjanro]'
							AND myyja != ''
							ORDER BY myyja";
				$sresult = mysql_query($query) or pupe_error($query);
				$srow = mysql_fetch_array($sresult);
				
				echo "<td valign='top'>$srow[nimi]</td>";
				
				$query = "	SELECT distinct myyja, nimi 
							FROM kuka 
							WHERE yhtio='$kukarow[yhtio]' 
							AND myyja = '$row[ostajanro]'
							AND myyja != ''
							ORDER BY myyja";
				$sresult = mysql_query($query) or pupe_error($query);
				$srow = mysql_fetch_array($sresult);
				
				echo "<td valign='top'>$srow[nimi]</td>";
				echo "<td valign='top'>$row[malli]</td>";
				echo "<td valign='top'>$row[mallitarkenne]</td>";
				echo "<td valign='top'>".tv1dateconv($row["saapumispvm"])."</td>";
				echo "<td align='right' valign='top'>$row[saldo]</td>";
			}
			
			if ($lisatiedot == "TARK") echo "<td valign='top'>".tv1dateconv($row["tulopvm"])."</td>";
			echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["summa"]))."</td>";
			echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kate"]))."</td>";
			echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["katepros"]))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kateosuus"]))."</td>";
			echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["vararvo"]))."</td>";
			echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["varaston_kiertonop"]))."</td>";
			echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kate_kertaa_kierto"]))."</td>";
			echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.0f',$row["kpl"]))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["myyntierankpl"]))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["myyntieranarvo"]))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.0f',$row["rivia"]))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.0f',$row["puuterivia"]))."</td>";
			echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["palvelutaso"]))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["ostoerankpl"]))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["ostoeranarvo"]))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.0f',$row["osto_rivia"]))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kustannus"]))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kustannus_osto"]))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["kustannus_yht"]))."</td>";
			if ($lisatiedot == "TARK") echo "<td align='right' valign='top' nowrap>".str_replace(".",",",sprintf('%.1f',$row["total"]))."</td>";
			echo "</tr>\n";
			
			$saldoyht				+= $row["saldo"];
			$ryhmamyyntiyht 		+= $row["summa"];
			$ryhmakateyht   		+= $row["kate"];
			$ryhmanvarastonarvoyht 	+= $row["vararvo"];
			$rivilkmyht				+= $row["rivia"];
			$ryhmakplyht			+= $row["kpl"];
			$ryhmapuuteyht			+= $row["puutekpl"];
			$ryhmapuuterivityht		+= $row["puuterivia"];
			$ryhmaostotyht 	 		+= $row["osto_summa"];
			$ryhmaostotkplyht		+= $row["osto_kpl"];
			$ryhmaostotrivityht 	+= $row["osto_rivia"];
			$ryhmakustamyyyht		+= $row["kustannus"];
			$ryhmakustaostyht		+= $row["kustannus_osto"];
			$ryhmakustayhtyht		+= $row["kustannus_yht"];
			$totalyht				+= $row["total"];

		}

		//yhteensärivi
		if ($ryhmamyyntiyht != 0) $kateprosenttiyht = round($ryhmakateyht / $ryhmamyyntiyht * 100,2);	
		else $kateprosenttiyht = 0;

		if ($sumrow["yhtkate"] != 0) $kateosuusyht = round($ryhmakateyht / $sumrow["yhtkate"] * 100,2);
		else $kateosuusyht = 0;

		if ($ryhmanvarastonarvoyht != 0) $kiertonopeusyht = round(($ryhmamyyntiyht - $ryhmakateyht) / $ryhmanvarastonarvoyht,2);
		else $kiertonopeusyht = 0;

		if ($rivilkmyht != 0) $myyntieranarvoyht = round($ryhmamyyntiyht / $rivilkmyht,2);
		else $myyntieranarvoyht = 0;

		if ($rivilkmyht != 0) $myyntieranakplyht = round($ryhmakplyht / $rivilkmyht,2);
		else $myyntieranakplyht = 0;

		if ($ryhmapuuterivityht + $rivilkmyht != 0)	$palvelutasoyht = round(100 - ($ryhmapuuterivityht / ($ryhmapuuterivityht + $rivilkmyht) * 100),2);
		else $palvelutasoyht = 0;

		if ($ryhmaostotrivityht != 0) $ostoeranarvoyht = round($ryhmaostotyht / $ryhmaostotrivityht,2);
		else $ostoeranarvoyht = 0;

		if ($ryhmaostotrivityht != 0) $ostoeranakplyht = round($ryhmaostotkplyht / $ryhmaostotrivityht,2);
		else $ostoeranakplyht = 0;

		if ($ryhmamyyntiyht != 0 and $ryhmanvarastonarvoyht != 0) { 
			$kate_kertaa_kierto = round(($ryhmakateyht / $ryhmamyyntiyht * 100) * (($ryhmamyyntiyht - $ryhmakateyht) / $ryhmanvarastonarvoyht), 2);
		}
		else { 
			$kate_kertaa_kierto = 0;
		}
		
		echo "<tr>";
		
		if ($lisatiedot == "TARK") {
			echo "<td colspan='10' class='spec'>".t("Yhteensä").":</td>";
		}
		else {
			echo "<td colspan='5' class='spec'>".t("Yhteensä").":</td>";
		}
		
		if ($lisatiedot == "TARK") {
			echo "<td align='right' class='spec' nowrap>$saldoyht</td><td></td>";			
		}	
		
		if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>$saldoyht</td>";
		echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ryhmamyyntiyht))."</td>";
		echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ryhmakateyht))."</td>";
		echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$kateprosenttiyht))."</td>";
		if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$kateosuusyht))."</td>";
		echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ryhmanvarastonarvoyht))."</td>";
		echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$kiertonopeusyht))."</td>";
		echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$kate_kertaa_kierto))."</td>";
		echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.0f',$ryhmakplyht))."</td>";
		if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$myyntieranakplyht))."</td>";
		if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$myyntieranarvoyht))."</td>";
		if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.0f',$rivilkmyht))."</td>";
		if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.0f',$ryhmapuuterivityht))."</td>";
		echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$palvelutasoyht))."</td>";
		if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ostoeranakplyht))."</td>";
		if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ostoeranarvoyht))."</td>";
		if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.0f',$ryhmaostotrivityht))."</td>";
		if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ryhmakustamyyyht))."</td>";
		if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ryhmakustaostyht))."</td>";
		if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$ryhmakustayhtyht))."</td>";
		if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".",",",sprintf('%.1f',$totalyht))."</td>";

		echo "</tr>\n";

		echo "</table>";
	}

?>