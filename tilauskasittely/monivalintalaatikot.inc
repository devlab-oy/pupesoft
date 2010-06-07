<?php

	if (!isset($orderlisa)) {
		$orderlisa = '';
	}

	// Näytetäänkö osaston ja tuoteryhmän selitteet
	if ($yhtiorow['naytetaanko_osaston_ja_tryn_selite'] == "K") {
		$orderlisa = "ORDER BY avainsana.selitetark";
	}
	else {
		$orderlisa = "ORDER BY avainsana.jarjestys, avainsana.selite+0";
	}

	if (!isset($mulselprefix) or $mulselprefix == "") {
		$mulselprefix = "tuote";
	}

	$lisa_haku_osasto 		 = "";
	$lisa_haku_try 			 = "";
	$lisa_haku_tme 			 = "";
	$lisa_haku_malli 		 = "";
	$lisa_haku_mallitarkenne = "";
	$lisa_haku_myyja		 = "";
	$lisa_haku_ostaja		 = "";
	$lisa_haku_asiakasosasto = "";
	$lisa_haku_asiakasryhma	 = "";

	foreach ($monivalintalaatikot as $monivalintalaatikko) {
		if (strtolower(substr($monivalintalaatikko, 0, 9)) == "parametri") {
			$param = substr($monivalintalaatikko, 10);

			${"lisa_haku_".$param} = '';
			if (!isset(${"mul_".$param})) ${"mul_".$param} = array();
		}
	}

	if (!isset($mul_osasto)) $mul_osasto = array();
	if (!isset($mul_try)) $mul_try = array();
	if (!isset($mul_tme)) $mul_tme = array();
	if (!isset($mul_malli)) $mul_malli = array();
	if (!isset($mul_mallitarkenne)) $mul_mallitarkenne = array();
	if (!isset($mul_tuotemyyja)) $mul_tuotemyyja = array();
	if (!isset($mul_tuoteostaja)) $mul_tuoteostaja = array();
	if (!isset($mul_asiakasosasto)) $mul_asiakasosasto = array();
	if (!isset($mul_asiakasryhma)) $mul_asiakasryhma = array();

	// jos on valittu jotakin dropdowneista (muu kuin osasto) niin tehdään niillä rajaukset muihin dropdowneihin
	if (count($mul_osasto) > 0 or count($mul_try) > 0 or count($mul_tme) > 0) {
		if (count($mul_osasto) > 0) {
			$osastot = '';

			foreach ($mul_osasto as $osx) {
				if (trim($osx) != '') {
					if (trim($osx) != "PUPEKAIKKIMUUT") {
						$osx = trim(mysql_real_escape_string($osx));
						$osastot .= "'$osx',";
						$ulisa .= "&mul_osasto[]=".rawurlencode($osx);
					}
				}
			}

			$osastot = substr($osastot, 0, -1);

			if (trim($osastot) != '') {
				$lisa_haku_osasto = " and tuote.osasto in ($osastot) ";
				$lisa .= " and $mulselprefix.osasto in ($osastot) ";
			}
		}

		if (count($mul_try) > 0) {
			$tryt = '';

			foreach ($mul_try as $tryx) {
				if (trim($tryx) != '') {
					if (trim($tryx) != "PUPEKAIKKIMUUT") {
						$tryx = trim(mysql_real_escape_string($tryx));
						$tryt .= "'$tryx',";
						$ulisa .= "&mul_try[]=".rawurlencode($tryx);
					}
				}
			}

			$tryt = substr($tryt, 0, -1);

			if (trim($tryt) != '') {
				$lisa_haku_try = " and tuote.try in ($tryt) ";
				$lisa .= " and $mulselprefix.try in ($tryt) ";
			}
		}

		if (count($mul_tme) > 0) {
			$tmet = '';

			foreach ($mul_tme as $tmex) {
				if (trim($tmex) != '') {
					if (trim($tmex) != "PUPEKAIKKIMUUT") {
						$tmex = trim(mysql_real_escape_string($tmex));
						$tmet .= "'$tmex',";
						$ulisa .= "&mul_tme[]=".rawurlencode($tmex);
					}
				}
			}

			$tmet = substr($tmet, 0, -1);

			if (trim($tmet) != '') {
				$lisa_haku_tme = " and tuote.tuotemerkki in ($tmet) ";
				$lisa .= " and $mulselprefix.tuotemerkki in ($tmet) ";
			}
		}

		if (count($mul_malli) > 0) {
			$mallit = '';

			foreach ($mul_malli as $mallix) {
				if (trim($mallix) != '') {
					if (trim($mallix) != "PUPEKAIKKIMUUT") {
						$mallix = trim(mysql_real_escape_string($mallix));
						$mallit .= "'$mallix',";
						$ulisa .= "&mul_malli[]=".rawurlencode($mallix);
					}
				}
			}

			$mallit = substr($mallit, 0, -1);

			if (trim($mallit) != '') {
				$lisa_haku_malli = " and tuote.malli in ($mallit) ";
				$lisa .= " and $mulselprefix.malli in ($mallit) ";
			}
		}

		if (count($mul_mallitarkenne) > 0) {
			$mallitarkenteet = '';

			foreach ($mul_mallitarkenne as $mallitarkennex) {
				if (trim($mallitarkennex) != '') {
					if (trim($mallitarkennex) != "PUPEKAIKKIMUUT") {
						$mallitarkennex = trim(mysql_real_escape_string($mallitarkennex));
						$mallitarkenteet .= "'$mallitarkennex',";
						$ulisa .= "&mul_mallitarkenne[]=".rawurlencode($mallitarkennex);
					}
				}
			}

			$mallitarkenteet = substr($mallitarkenteet, 0, -1);

			if (trim($mallitarkenteet) != '') {
				$lisa_haku_mallitarkenne = " and tuote.mallitarkenne in ($mallitarkenteet) ";
				$lisa .= " and $mulselprefix.mallitarkenne in ($mallitarkenteet) ";
			}
		}

		foreach ($monivalintalaatikot as $monivalintalaatikko) {
			if (strtolower(substr($monivalintalaatikko, 0, 9)) == "parametri") {
				$param = substr($monivalintalaatikko, 10);

				$i = 1;

				if (count(${"mul_".$param}) > 0) {
					${$param."t"} = '';

					foreach (${"mul_".$param} as $paramx) {
						if (trim($paramx) != '') {
							if (trim($paramx) != "PUPEKAIKKIMUUT") {
								$paramx = trim(mysql_real_escape_string($paramx));
								${$param."t"} .= "'$paramx',";
								$ulisa .= "&mul_{$param}[]=".rawurlencode($paramx);
							}
						}
					}

					${$param."t"} = substr(${$param."t"}, 0, -1);

					if (trim(${$param."t"}) != '') {
						${"lisa_haku_".$param} = " and t_av_$param.selite in (".${$param."t"}.") ";
						$lisa_parametri .= " JOIN tuotteen_avainsanat t_av_$param ON (t_av_$param.yhtio = tuote.yhtio and t_av_$param.tuoteno = tuote.tuoteno and t_av_$param.laji = 'parametri_$param' and t_av_$param.selite in (".${$param."t"}.")) ";
					}
					$i++;
				}
			}
		}
	}

	if (count($mul_tuotemyyja) > 0) {
		$tuotemyyjat = '';

		foreach ($mul_tuotemyyja as $tuotemyyjax) {
			if (trim($tuotemyyjax) != '') {
				$tuotemyyjax = trim(mysql_real_escape_string($tuotemyyjax));
				$tuotemyyjat .= "'$tuotemyyjax',";
				$ulisa .= "&mul_tuotemyyja[]=".rawurlencode($tuotemyyjax);
			}
		}

		$tuotemyyjat = substr($tuotemyyjat, 0, -1);

		if (trim($tuotemyyjat) != '') {
			$lisa_haku_myyja = " and kuka.myyja in ($tuotemyyjat) ";
			$lisa .= " and $mulselprefix.myyjanro in ($tuotemyyjat) ";
		}
	}

	if (count($mul_tuoteostaja) > 0) {
		$tuoteostajat = '';

		foreach ($mul_tuoteostaja as $tuoteostajax) {
			if (trim($tuoteostajax) != '') {
				$tuoteostajax = trim(mysql_real_escape_string($tuoteostajax));
				$tuoteostajat .= "'$tuoteostajax',";
				$ulisa .= "&mul_tuoteostaja[]=".rawurlencode($tuoteostajax);
			}
		}

		$tuoteostajat = substr($tuoteostajat, 0, -1);

		if (trim($tuoteostajat) != '') {
			$lisa_haku_ostaja = " and kuka.myyja in ($tuoteostajat) ";
			$lisa .= " and $mulselprefix.ostajanro in ($tuoteostajat) ";
		}
	}

	if (count($mul_asiakasosasto) > 0) {
		$asiakasosastot = '';

		foreach ($mul_asiakasosasto as $asiakasosastox) {
			if (trim($asiakasosastox) != '') {
				$asiakasosastox = trim(mysql_real_escape_string($asiakasosastox));
				$asiakasosastot .= "'$asiakasosastox',";
				$ulisa .= "&mul_asiakasosasto[]=".rawurlencode($asiakasosastox);
			}
		}

		$asiakasosastot = substr($asiakasosastot, 0, -1);

		if (trim($asiakasosastot) != '') {
			$lisa_haku_asiakasosasto = " and asiakas.osasto in ($asiakasosastot) ";

			if ($mulselprefix == "abc_aputaulu") $lisa .= " and $mulselprefix.osasto in ($asiakasosastot) ";
			else $lisa .= " and asiakas.osasto in ($asiakasosastot) ";
		}
	}

	if (count($mul_asiakasryhma) > 0) {
		$asiakasryhmat = '';

		foreach ($mul_asiakasryhma as $asiakasryhmax) {
			if (trim($asiakasryhmax) != '') {
				$asiakasryhmax = trim(mysql_real_escape_string($asiakasryhmax));
				$asiakasryhmat .= "'$asiakasryhmax',";
				$ulisa .= "&mul_asiakasryhma[]=".rawurlencode($asiakasryhmax);
			}
		}

		$asiakasryhmat = substr($asiakasryhmat, 0, -1);

		if (trim($asiakasryhmat) != '') {
			$lisa_haku_asiakasryhma = " and asiakas.ryhma in ($asiakasryhmat) ";

			if ($mulselprefix == "abc_aputaulu") $lisa .= " and $mulselprefix.try in ($asiakasryhmat) ";
			else $lisa .= " and asiakas.ryhma in ($asiakasryhmat) ";
		}
	}

	// Rajataanko mitä osastoja näytetään
	if ($monivalintarajaus != "") {
		$monivalintarajaus_osasto = " and avainsana.selite in ($monivalintarajaus)";

		if (trim($lisa_haku_osasto) == '') {
			$lisa_haku_osasto = " and tuote.osasto in ($monivalintarajaus)";
			$lisa .= " and $mulselprefix.osasto in ($monivalintarajaus)";

			foreach (explode(",", $monivalintarajaus) as $monival) {
				$ulisa .= "&mul_osasto[]=".urlencode(str_replace("'", "", $osastot));
			}
		}
	}
	else {
		$monivalintarajaus_osasto = "";
	}

	foreach ($monivalintalaatikot as $monivalintalaatikko) {
		$monivalintalaatikko = trim($monivalintalaatikko);

		if (strtolower($monivalintalaatikko) == "osasto") {
			if (isset($monivalinta_tuotteet)) {
				$query = "	SELECT DISTINCT avainsana.selite,
				            IFNULL((SELECT avainsana_kieli.selitetark
				            FROM avainsana as avainsana_kieli
				            WHERE avainsana_kieli.yhtio = avainsana.yhtio
				            and avainsana_kieli.laji = avainsana.laji
							and avainsana_kieli.perhe > 0
				            and avainsana_kieli.perhe = avainsana.perhe
				            and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selitetark) selitetark,
							sum(if(tuote.status not in ('P','X'), 1, (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0))) naytetaanko
							FROM tuote
							JOIN avainsana ON (avainsana.yhtio = tuote.yhtio AND avainsana.selite = tuote.osasto AND avainsana.laji = 'OSASTO' AND avainsana.kieli in ('$yhtiorow[kieli]', '') $avainlisa)
							WHERE tuote.yhtio = '$kukarow[yhtio]'
							$kieltolisa
							$extra_poislisa
							$poislisa_mulsel
							AND tuote.tuoteno in ($monivalinta_tuotteet)
							GROUP BY 1,2
							HAVING naytetaanko > 0
							ORDER BY avainsana.selitetark";
			}
			else {
				$query = "	SELECT DISTINCT avainsana.selite,
				            IFNULL((SELECT avainsana_kieli.selitetark
				            FROM avainsana as avainsana_kieli
				            WHERE avainsana_kieli.yhtio = avainsana.yhtio
				            and avainsana_kieli.laji = avainsana.laji
							and avainsana_kieli.perhe > 0
				            and avainsana_kieli.perhe = avainsana.perhe
				            and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selitetark) selitetark
				            FROM avainsana
				            WHERE avainsana.yhtio = '$kukarow[yhtio]'
				            and avainsana.laji = 'OSASTO'
				            and avainsana.kieli in ('$yhtiorow[kieli]', '')
							$monivalintarajaus_osasto
				            $avainlisa
				            $orderlisa";
			}
			$sresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($sresult) == 0) {
				$query = "	SELECT DISTINCT tuote.osasto selite, tuote.osasto selitetark
							FROM tuote
							WHERE tuote.yhtio = '$kukarow[yhtio]'
							and tuote.osasto != ''
							$kieltolisa
							$extra_poislisa
							$poislisa_mulsel
							ORDER BY 1, 2";
				$sresult = mysql_query($query) or pupe_error($query);
			}

			if (mysql_num_rows($sresult) > 1) {

				$multiple_lisa = " multiple='multiple' size='7' ";

				if (in_array("osasto", array_map('strtolower', $monivalintalaatikot_normaali))) {
					$multiple_lisa = "";
				}

				echo "<div style='float: left; padding-right:4px; padding-top:4px;'><table valign='top'>";
				echo "<tr><th>";

				if ($hae_ja_selaa_row['selite'] == 'B') {
					echo "<label>";
				}

				echo t("Osasto");

				if ($hae_ja_selaa_row['selite'] == 'B') {
					echo "</label>";
				}

				echo "</th></tr>";
				echo "<tr><td nowrap style='padding:0px'><select name='mul_osasto[]' $multiple_lisa onchange='submit();'>";

				$mul_check = "";
				if ($mul_try != "") {
					if (in_array("PUPEKAIKKIMUUT", $mul_osasto)) {
						$mul_check = 'SELECTED';
					}
				}

				echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Näytä kaikki")."</option>";
				echo "<option value=''>".t("Ei valintaa")."</option>";

				while ($sxrow = mysql_fetch_assoc($sresult)) {
					$sel = '';

					if (count($mul_osasto) > 0) {
						if (in_array(strtolower(trim($sxrow['selite'])), $mul_osasto)) {
							$sel = 'SELECTED';
						}
					}

					echo "<option value='".strtolower($sxrow['selite'])."' $sel>";

					if ($yhtiorow['naytetaanko_osaston_ja_tryn_selite'] == '') {
						echo $sxrow['selite']." ";
					}

					echo "$sxrow[selitetark]</option>";
				}

				echo "</select></td>";
				echo "</tr></table></div>";
			}
		}

		if (strtolower($monivalintalaatikko) == "try") {

			if (isset($monivalinta_tuotteet)) {
				$query = "	SELECT distinct avainsana.selite,
							IFNULL((SELECT avainsana_kieli.selitetark
					        FROM avainsana as avainsana_kieli
					        WHERE avainsana_kieli.yhtio = avainsana.yhtio
					        and avainsana_kieli.laji = avainsana.laji
							and avainsana_kieli.perhe > 0
					        and avainsana_kieli.perhe = avainsana.perhe
					        and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selitetark) selitetark,
							sum(if(tuote.status not in ('P','X'), 1, (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0))) naytetaanko
							FROM tuote
							JOIN avainsana ON (avainsana.yhtio = tuote.yhtio and tuote.try = avainsana.selite and avainsana.laji = 'TRY' and avainsana.kieli in ('$yhtiorow[kieli]', '') $avainlisa)
							WHERE tuote.yhtio = '$kukarow[yhtio]'
							$lisa_haku_osasto
							$kieltolisa
							$extra_poislisa
							$poislisa_mulsel
							AND tuote.tuoteno in ($monivalinta_tuotteet)
							GROUP BY 1,2
							HAVING naytetaanko > 0
							ORDER BY avainsana.selitetark";
			}
			elseif ($lisa_haku_osasto == "") {
				$query = "	SELECT DISTINCT avainsana.selite,
				            IFNULL((SELECT avainsana_kieli.selitetark
				            FROM avainsana as avainsana_kieli
				            WHERE avainsana_kieli.yhtio = avainsana.yhtio
				            and avainsana_kieli.laji = avainsana.laji
							and avainsana_kieli.perhe > 0
				            and avainsana_kieli.perhe = avainsana.perhe
				            and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selitetark) selitetark
				            FROM avainsana
				            WHERE avainsana.yhtio = '$kukarow[yhtio]'
				            and avainsana.laji = 'TRY'
				            and avainsana.kieli in ('$yhtiorow[kieli]', '')
				            $avainlisa
				            $orderlisa";
			}
			else {
				$query = "	SELECT distinct avainsana.selite,
							IFNULL((SELECT avainsana_kieli.selitetark
					        FROM avainsana as avainsana_kieli
					        WHERE avainsana_kieli.yhtio = avainsana.yhtio
					        and avainsana_kieli.laji = avainsana.laji
							and avainsana_kieli.perhe > 0
					        and avainsana_kieli.perhe = avainsana.perhe
					        and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selitetark) selitetark
							FROM tuote
							JOIN avainsana ON (avainsana.yhtio = tuote.yhtio and tuote.try = avainsana.selite and avainsana.laji = 'TRY' and avainsana.kieli in ('$yhtiorow[kieli]', '') $avainlisa)
							WHERE tuote.yhtio = '$kukarow[yhtio]'
							$lisa_haku_osasto
							$kieltolisa
							$extra_poislisa
							$poislisa_mulsel
							$orderlisa";
			}
			$sresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($sresult) == 0) {
				$query = "	SELECT DISTINCT tuote.try selite, tuote.try selitetark
							FROM tuote
							WHERE tuote.yhtio = '$kukarow[yhtio]'
							and tuote.try != ''
							$lisa_haku_osasto
							$kieltolisa
							$extra_poislisa
							$poislisa_mulsel
							ORDER BY 1, 2";
				$sresult = mysql_query($query) or pupe_error($query);
			}

			if (mysql_num_rows($sresult) > 1) {

				$multiple_lisa = " multiple='multiple' size='7' ";

				if (in_array("try", array_map('strtolower', $monivalintalaatikot_normaali))) {
					$multiple_lisa = "";
				}

				echo "<div style='float: left; padding-right:4px; padding-top:4px;'><table valign='top'><tr><th>";

				if ($hae_ja_selaa_row['selite'] == 'B') {
					echo "<label>";
				}

				echo t("Tuoteryhmä");

				if ($hae_ja_selaa_row['selite'] == 'B') {
					echo "</label>";
				}

				echo "</th></tr>";
				echo "<tr><td nowrap style='padding:0px'><select name='mul_try[]' onchange='submit();' $multiple_lisa>";

				$mul_check = '';
				if ($mul_try != "") {
					if (in_array("PUPEKAIKKIMUUT", $mul_try)) {
						$mul_check = 'SELECTED';
					}
				}
				echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Näytä kaikki")."</option>";
				echo "<option value=''>".t("Ei valintaa")."</option>";

				while ($srow = mysql_fetch_assoc($sresult)) {
					$sel = '';

					if (count($mul_try) > 0 and in_array(strtolower(trim($srow['selite'])), $mul_try)) {
						$sel = 'SELECTED';
					}

					echo "<option value='".strtolower($srow['selite'])."' $sel>";

					if ($yhtiorow['naytetaanko_osaston_ja_tryn_selite'] == '') {
						echo $srow['selite']." ";
					}

					echo "$srow[selitetark]</option>";
				}

				echo "</select></td>";
				echo "</tr></table></div>";
			}
		}

		if (strtolower($monivalintalaatikko) == "tuotemerkki") {
			unset($tmsresult);

			if (isset($monivalinta_tuotteet)) {
				$query = "	SELECT avainsana.selite as tme, IFNULL((SELECT avainsana_kieli.selite
		        			FROM avainsana as avainsana_kieli
		        			WHERE avainsana_kieli.yhtio = avainsana.yhtio
		        			and avainsana_kieli.laji = avainsana.laji
							and avainsana_kieli.perhe > 0
		        			and avainsana_kieli.perhe = avainsana.perhe
		        			and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selite) selite,
							sum(if(tuote.status not in ('P','X'), 1, (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0))) naytetaanko
							FROM tuote
							JOIN avainsana ON (avainsana.yhtio = tuote.yhtio and tuote.tuotemerkki = avainsana.selite and avainsana.laji = 'TUOTEMERKKI' and avainsana.kieli in ('$yhtiorow[kieli]', '') $avainlisa)
							WHERE tuote.yhtio = '$kukarow[yhtio]'
							$lisa_haku_osasto
							$lisa_haku_try
							$kieltolisa
							$extra_poislisa
							$poislisa_mulsel
							AND tuote.tuoteno in ($monivalinta_tuotteet)
							GROUP BY 1,2
							HAVING naytetaanko > 0
							ORDER BY avainsana.selite";
				$tmsresult = mysql_query($query) or pupe_error($query);
			}
			elseif ($lisa_haku_osasto == "" and $lisa_haku_try == "" and ($kukarow['yhtio'] != 'artr' or count($mul_tme) > 0)) {
				$query = "	SELECT avainsana.selite as tme, IFNULL((SELECT avainsana_kieli.selite
		        			FROM avainsana as avainsana_kieli
		        			WHERE avainsana_kieli.yhtio = avainsana.yhtio
		        			and avainsana_kieli.laji = avainsana.laji
							and avainsana_kieli.perhe > 0
		        			and avainsana_kieli.perhe = avainsana.perhe
		        			and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selite) selite
				            FROM avainsana
				            WHERE avainsana.yhtio 	= '$kukarow[yhtio]'
				            and avainsana.laji 		= 'TUOTEMERKKI'
				            $avainlisa
				            ORDER BY avainsana.jarjestys, avainsana.selite";
				$tmsresult = mysql_query($query) or pupe_error($query);
			}
			elseif ($lisa_haku_osasto != "" or $lisa_haku_try != "") {
				$query = "	SELECT distinct avainsana.selite as tme, IFNULL((SELECT avainsana_kieli.selite
		        			FROM avainsana as avainsana_kieli
		        			WHERE avainsana_kieli.yhtio = avainsana.yhtio
		        			and avainsana_kieli.laji = avainsana.laji
							and avainsana_kieli.perhe > 0
		        			and avainsana_kieli.perhe = avainsana.perhe
		        			and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selite) selite
							FROM tuote";

				$lisaquery = '';

				foreach ($monivalintalaatikot as $monivalintalaatikko2) {
					if (strtolower(substr($monivalintalaatikko2, 0, 9)) == "parametri") {						
						$param = substr($monivalintalaatikko2, 10);

						if (${"lisa_haku_".$param} != "") {
							$lisaquery .= " JOIN tuotteen_avainsanat t_av_$param ON (t_av_$param.yhtio = tuote.yhtio and t_av_$param.tuoteno = tuote.tuoteno and t_av_$param.laji = 'parametri_$param' ".${"lisa_haku_".$param}.") ";
						}
					}
				}

				$query .= $lisaquery;

				$query .= " JOIN avainsana ON (avainsana.yhtio = tuote.yhtio and tuote.tuotemerkki = avainsana.selite and avainsana.laji = 'TUOTEMERKKI' $avainlisa)
							WHERE tuote.yhtio = '$kukarow[yhtio]'
							$lisa_haku_osasto
							$lisa_haku_try
							$lisa_haku_malli
							$kieltolisa
							$extra_poislisa
							$poislisa_mulsel
							ORDER BY avainsana.jarjestys, avainsana.selite";
				$tmsresult = mysql_query($query) or pupe_error($query);
			}

			if (is_resource($tmsresult) and mysql_num_rows($tmsresult) == 0) {
				$query = "	SELECT DISTINCT tuote.tuotemerkki tme, tuote.tuotemerkki selite
							FROM tuote
							WHERE tuote.yhtio = '$kukarow[yhtio]'
							and tuote.tuotemerkki != ''
							$lisa_haku_osasto
							$lisa_haku_try
							$kieltolisa
							$extra_poislisa
							$poislisa_mulsel
							ORDER BY 1";
				$tmsresult = mysql_query($query) or pupe_error($query);
			}

			if (is_resource($tmsresult) and mysql_num_rows($tmsresult) > 0) {
				echo "<div style='float: left; padding-right:4px; padding-top:4px;'><table valign='top'><tr><th>";

				if ($hae_ja_selaa_row['selite'] == 'B') {
					echo "<label>";
				}

				echo t("Tuotemerkki");

				if ($hae_ja_selaa_row['selite'] == 'B') {
					echo "</label>";
				}

				echo "</th></tr>";
				echo "<tr><td nowrap style='padding:0px'>";

				$multiple_lisa = " multiple='multiple' size='7' ";

				if (in_array("tuotemerkki", array_map('strtolower', $monivalintalaatikot_normaali))) {
					$multiple_lisa = "";
				}

				echo "<select name='mul_tme[]' $multiple_lisa onchange='submit();'>";


				$mul_check = '';
				if ($mul_tme != "") {
					if (in_array("PUPEKAIKKIMUUT", $mul_tme)) {
						$mul_check = 'SELECTED';
					}
				}
				echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Näytä kaikki")."</option>";
				echo "<option value=''>",t("Ei valintaa"),"</option>";

				while ($srow = mysql_fetch_assoc($tmsresult)) {
					$sel = '';

					if (count($mul_tme) > 0 and in_array(strtolower(trim($srow['tme'])), $mul_tme)) {
						$sel = 'SELECTED';
					}

					echo "<option value='".strtolower($srow['tme'])."' $sel>$srow[selite]</option>";
				}

				echo "</select></td>";
				echo "</tr></table></div>";
			}
		}

		if (strtolower($monivalintalaatikko) == "malli/mallitark" or strtolower($monivalintalaatikko) == "malli") {
			unset($sxresult);

			if (($lisa_haku_osasto != "" or $lisa_haku_try != "") or ($lisa_haku_osasto != "" and in_array("PUPEKAIKKIMUUT", $mul_try))) {

				if (($lisa_haku_tme != '' or $lisa_haku_try != '') and (strtolower($monivalintalaatikko) == "malli" or (!in_array("malli", array_map('strtolower', $monivalintalaatikot)) and strtolower($monivalintalaatikko) == 'malli/mallitark'))) {
					// malli dropdown
					if (isset($monivalinta_tuotteet)) {
						$query = "	SELECT avainsana.selite as malli, IFNULL((SELECT avainsana_kieli.selite
				        			FROM avainsana as avainsana_kieli
				        			WHERE avainsana_kieli.yhtio = avainsana.yhtio
				        			and avainsana_kieli.laji = avainsana.laji
									and avainsana_kieli.perhe > 0
				        			and avainsana_kieli.perhe = avainsana.perhe
				        			and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selite) selite,
									sum(if(tuote.status not in ('P','X'), 1, (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0))) naytetaanko
									FROM tuote
									JOIN avainsana ON (avainsana.yhtio = tuote.yhtio and tuote.malli = avainsana.selite and avainsana.laji = 'MALLI' and avainsana.kieli in ('$yhtiorow[kieli]', '') $avainlisa)
									WHERE tuote.yhtio = '$kukarow[yhtio]'
									$lisa_haku_osasto
									$lisa_haku_try
									$lisa_haku_tme
									$kieltolisa
									$extra_poislisa
									$poislisa_mulsel
									AND tuote.tuoteno in ($monivalinta_tuotteet)
									GROUP BY 1,2
									HAVING naytetaanko > 0
									ORDER BY avainsana.selite";
						$sxresult = mysql_query($query) or pupe_error($query);
					}
					elseif ($lisa_haku_try == "" and $lisa_haku_tme == "" and ($kukarow['yhtio'] != 'artr' or count($mul_malli) > 0)) {
						$query = "	SELECT avainsana.selite as malli, IFNULL((SELECT avainsana_kieli.selite
				        			FROM avainsana as avainsana_kieli
				        			WHERE avainsana_kieli.yhtio = avainsana.yhtio
				        			and avainsana_kieli.laji = avainsana.laji
									and avainsana_kieli.perhe > 0
				        			and avainsana_kieli.perhe = avainsana.perhe
				        			and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selite) selite
						            FROM avainsana
						            WHERE avainsana.yhtio 	= '$kukarow[yhtio]'
						            and avainsana.laji 		= 'MALLI'
						            $avainlisa
						            ORDER BY avainsana.jarjestys, avainsana.selite";
						$sxresult = mysql_query($query) or pupe_error($query);
					}
					elseif ($lisa_haku_try != "" or $lisa_haku_tme != "") {
						$query = "	SELECT distinct avainsana.selite as malli, IFNULL((SELECT avainsana_kieli.selite
				        			FROM avainsana as avainsana_kieli
				        			WHERE avainsana_kieli.yhtio = avainsana.yhtio
				        			and avainsana_kieli.laji = avainsana.laji
				        			and avainsana_kieli.perhe > 0
									and avainsana_kieli.perhe = avainsana.perhe
				        			and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selite) selite
									FROM tuote";

						$lisaquery = '';

						foreach ($monivalintalaatikot as $monivalintalaatikko3) {
							if (strtolower(substr($monivalintalaatikko3, 0, 9)) == "parametri") {						
								$param = substr($monivalintalaatikko3, 10);

								if (${"lisa_haku_".$param} != "") {
									$lisaquery .= " JOIN tuotteen_avainsanat t_av_$param ON (t_av_$param.yhtio = tuote.yhtio and t_av_$param.tuoteno = tuote.tuoteno and t_av_$param.laji = 'parametri_$param' ".${"lisa_haku_".$param}.") ";
								}
							}
						}

						$query .= $lisaquery;

						$query .= " JOIN avainsana ON (avainsana.yhtio = tuote.yhtio and tuote.malli = avainsana.selite and avainsana.laji = 'MALLI' $avainlisa)
									WHERE tuote.yhtio = '$kukarow[yhtio]'
									$lisa_haku_osasto
									$lisa_haku_try
									$lisa_haku_tme
									$kieltolisa
									$extra_poislisa
									$poislisa_mulsel
									ORDER BY avainsana.jarjestys, avainsana.selite";
						$sxresult = mysql_query($query) or pupe_error($query);
					}

					if (is_resource($sxresult) and mysql_num_rows($sxresult) == 0) {
						$query = "	SELECT DISTINCT tuote.malli malli, tuote.malli selite
									FROM tuote
									WHERE tuote.yhtio = '$kukarow[yhtio]'
									and tuote.malli != ''
									$lisa_haku_osasto
									$lisa_haku_try
									$lisa_haku_tme
									$kieltolisa
									$extra_poislisa
									$poislisa_mulsel
									ORDER BY malli";
						$sxresult = mysql_query($query) or pupe_error($query);
					}

					if (is_resource($sxresult) and mysql_num_rows($sxresult) > 0) {

						echo "<div style='float: left; padding-right:4px; padding-top:4px;'><table valign='top'><tr><th>";

						if ($hae_ja_selaa_row['selite'] == 'B') {
							echo "<label>";
						}

						echo t("Malli");

						if ($hae_ja_selaa_row['selite'] == 'B') {
							echo "</label>";
						}

						echo "</th></tr>";
						echo "<tr><td nowrap style='padding:0px'>";

						$multiple_lisa = " multiple='multiple' size='7' ";

						if (in_array("malli", array_map('strtolower', $monivalintalaatikot_normaali))) {
							$multiple_lisa = "";
						}

						echo "<select name='mul_malli[]' $multiple_lisa onchange='submit();'>";

						$mul_check = '';
						if ($mul_malli != "") {
							if (in_array("PUPEKAIKKIMUUT", $mul_malli)) {
								$mul_check = 'SELECTED';
							}
						}
						echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Näytä kaikki")."</option>";
						echo "<option value=''>",t("Ei valintaa"),"</option>";

						while ($mallirow = mysql_fetch_assoc($sxresult)) {
							$sel = '';

							if (count($mul_malli) > 0 and in_array(strtolower(trim($mallirow['malli'])), $mul_malli)) {
								$sel = 'SELECTED';
							}

							echo "<option value='".strtolower($mallirow['malli'])."' $sel>$mallirow[selite]</option>";
						}

						echo "</select>";
						echo "</td>";
						echo "</tr></table></div>";
					}
				}

				if ($lisa_haku_malli != '' and strtolower($monivalintalaatikko) == "malli/mallitark") {
					unset($sxresult);

					// mallitarkenne dropdown
					if (isset($monivalinta_tuotteet)) {
						$query = "	SELECT avainsana.selite as mallitarkenne, IFNULL((SELECT avainsana_kieli.selite
				        			FROM avainsana as avainsana_kieli
				        			WHERE avainsana_kieli.yhtio = avainsana.yhtio
				        			and avainsana_kieli.laji = avainsana.laji
									and avainsana_kieli.perhe > 0
				        			and avainsana_kieli.perhe = avainsana.perhe
				        			and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selite) selite,
									sum(if(tuote.status not in ('P','X'), 1, (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0))) naytetaanko
									FROM tuote
									JOIN avainsana ON (avainsana.yhtio = tuote.yhtio and tuote.mallitarkenne = avainsana.selite and avainsana.laji = 'MALLITARKENNE' and avainsana.kieli in ('$yhtiorow[kieli]', '') $avainlisa)
									WHERE tuote.yhtio = '$kukarow[yhtio]'
									$lisa_haku_osasto
									$lisa_haku_try
									$lisa_haku_tme
									$lisa_haku_malli
									$kieltolisa
									$extra_poislisa
									$poislisa_mulsel
									AND tuote.tuoteno in ($monivalinta_tuotteet)
									GROUP BY 1,2
									HAVING naytetaanko > 0
									ORDER BY avainsana.selite";
						$sxresult = mysql_query($query) or pupe_error($query);
					}
					elseif ($lisa_haku_malli == "" and ($kukarow['yhtio'] != 'artr' or count($mul_mallitarkenne) > 0)) {
						$query = "	SELECT avainsana.selite as mallitarkenne, IFNULL((SELECT avainsana_kieli.selite
				        			FROM avainsana as avainsana_kieli
				        			WHERE avainsana_kieli.yhtio = avainsana.yhtio
				        			and avainsana_kieli.laji = avainsana.laji
									and avainsana_kieli.perhe > 0
				        			and avainsana_kieli.perhe = avainsana.perhe
				        			and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selite) selite
						            FROM avainsana
						            WHERE avainsana.yhtio 	= '$kukarow[yhtio]'
						            and avainsana.laji 		= 'MALLITARKENNE'
						            $avainlisa
						            ORDER BY avainsana.jarjestys, avainsana.selite";
						$sxresult = mysql_query($query) or pupe_error($query);
					}
					elseif ($lisa_haku_malli != "") {
						$query = "	SELECT distinct avainsana.selite as mallitarkenne, IFNULL((SELECT avainsana_kieli.selite
				        			FROM avainsana as avainsana_kieli
				        			WHERE avainsana_kieli.yhtio = avainsana.yhtio
				        			and avainsana_kieli.laji = avainsana.laji
									and avainsana_kieli.perhe > 0
				        			and avainsana_kieli.perhe = avainsana.perhe
				        			and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selite) selite
									FROM tuote";

						$lisaquery = '';

						foreach ($monivalintalaatikot as $monivalintalaatikko4) {
							if (strtolower(substr($monivalintalaatikko4, 0, 9)) == "parametri") {						
								$param = substr($monivalintalaatikko4, 10);

								if (${"lisa_haku_".$param} != "") {
									$lisaquery .= " JOIN tuotteen_avainsanat t_av_$param ON (t_av_$param.yhtio = tuote.yhtio and t_av_$param.tuoteno = tuote.tuoteno and t_av_$param.laji = 'parametri_$param' ".${"lisa_haku_".$param}.") ";
								}
							}
						}

						$query .= $lisaquery;

						$query .= " JOIN avainsana ON (avainsana.yhtio = tuote.yhtio and tuote.mallitarkenne = avainsana.selite and avainsana.laji = 'MALLITARKENNE' $avainlisa)
									WHERE tuote.yhtio = '$kukarow[yhtio]'
									$lisa_haku_osasto
									$lisa_haku_try
									$lisa_haku_tme
									$lisa_haku_malli
									$kieltolisa
									$extra_poislisa
									$poislisa_mulsel
									ORDER BY avainsana.jarjestys, avainsana.selite";
						$sxresult = mysql_query($query) or pupe_error($query);
					}

					if (is_resource($sxresult) and mysql_num_rows($sxresult) == 0) {
						$query = "	SELECT DISTINCT tuote.mallitarkenne mallitarkenne, tuote.mallitarkenne selite
									FROM tuote
									WHERE tuote.yhtio = '$kukarow[yhtio]'
									and tuote.mallitarkenne != ''
									$lisa_haku_osasto
									$lisa_haku_try
									$lisa_haku_tme
									$lisa_haku_malli
									$kieltolisa
									$extra_poislisa
									$poislisa_mulsel
									ORDER BY mallitarkenne";
						$sxresult = mysql_query($query) or pupe_error($query);
					}

					if (is_resource($sxresult) and mysql_num_rows($sxresult) > 0) {
						echo "<div style='float: left; padding-right:4px; padding-top:4px;'><table valign='top'>";
						echo "<tr><th>";

						if ($hae_ja_selaa_row['selite'] == 'B') {
							echo "<label>";
						}

						echo t("Mallitarkenne");

						if ($hae_ja_selaa_row['selite'] == 'B') {
							echo "</label>";
						}

						echo "</th></tr>";

						echo "<tr><td nowrap style='padding:0px'>";

						$multiple_lisa = " multiple='multiple' size='7' ";

						if (in_array("mallitarkenne", array_map('strtolower', $monivalintalaatikot_normaali))) {
							$multiple_lisa = "";
						}

						echo "<select name='mul_mallitarkenne[]' $multiple_lisa onchange='submit();'>";


						$mul_check = '';
						if ($mul_mallitarkenne != "") {
							if (in_array("PUPEKAIKKIMUUT", $mul_mallitarkenne)) {
								$mul_check = 'SELECTED';
							}
						}
						echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Näytä kaikki")."</option>";
						echo "<option value=''>",t("Ei valintaa"),"</option>";

						while ($mallitarkennerow = mysql_fetch_assoc($sxresult)) {
							$sel = '';

							if (count($mul_mallitarkenne) > 0 and in_array(strtolower(trim($mallitarkennerow['mallitarkenne'])), $mul_mallitarkenne)) {
								$sel = 'SELECTED';
							}

							echo "<option value='".strtolower($mallitarkennerow['mallitarkenne'])."' $sel>$mallitarkennerow[selite]</option>";
						}

						echo "</select>";
						echo "</td>";
						echo "</tr></table></div>";
					}
				}
			}
		}

		if (strtolower(substr($monivalintalaatikko, 0, 9)) == "parametri") {

			unset($sxresult);

			$parametri = substr($monivalintalaatikko, 10);

			if (($lisa_haku_osasto != "" or $lisa_haku_try != "")) {
				if ($kukarow["extranet"] == "" and $verkkokauppa == "") {
					if ($poistetut == '') {
						$poislisa_mulsel = " and tuote.status != 'P' ";
					}
				}

				$lisaquery = '';

				foreach ($monivalintalaatikot as $monivalintalaatikko5) {
					if (strtolower(substr($monivalintalaatikko5, 0, 9)) == "parametri" and strtolower(substr($monivalintalaatikko5, 10)) != strtolower($parametri)) {
						$param = substr($monivalintalaatikko5, 10);

						if (${"lisa_haku_".$param} != "") {
							$lisaquery .= " JOIN tuotteen_avainsanat t_av_$param ON (t_av_$param.yhtio = tuote.yhtio and t_av_$param.tuoteno = tuote.tuoteno and t_av_$param.laji = 'parametri_$param' ".${"lisa_haku_".$param}.") ";
						}
					}
				}

				// dynaaminen dropdowni
				if (isset($monivalinta_tuotteet)) {
					$query = "	SELECT t_av_$parametri.selite as $parametri,
								sum(if(tuote.status != 'P', 1, (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0))) naytetaanko
								FROM tuote
								JOIN tuotteen_avainsanat t_av_$parametri ON (t_av_$parametri.yhtio = tuote.yhtio and t_av_$parametri.laji = 'parametri_$parametri' and t_av_$parametri.tuoteno = tuote.tuoteno ".${"lisa_haku_".$parametri}.")";

					$query .= $lisaquery;

					$query .= " JOIN avainsana ON (avainsana.yhtio = tuote.yhtio and avainsana.selite = '$parametri' and avainsana.laji = 'PARAMETRI' and avainsana.kieli in ('$yhtiorow[kieli]', '') $avainlisa)
								WHERE tuote.yhtio = '$kukarow[yhtio]'
								$lisa_haku_osasto
								$lisa_haku_try
								$lisa_haku_tme
								$kieltolisa
								$extra_poislisa
								$poislisa_mulsel
								AND tuote.tuoteno in ($monivalinta_tuotteet)
								GROUP BY 1
								HAVING naytetaanko > 0
								ORDER BY t_av_$parametri.jarjestys, t_av_$parametri.selite";
					$sxresult = mysql_query($query) or pupe_error($query);
				}
				elseif ($lisa_haku_osasto == "" and $lisa_haku_try == "" and $lisa_haku_tme == "") {
					$query = "	SELECT distinct tuotteen_avainsanat.selite as $parametri
					            FROM tuotteen_avainsanat
								JOIN avainsana ON (avainsana.yhtio = tuotteen_avainsanat.yhtio and avainsana.laji = 'PARAMETRI' and avainsana.selite = '$parametri' $avainlisa)
					            WHERE tuotteen_avainsanat.yhtio 	= '$kukarow[yhtio]'
					            and tuotteen_avainsanat.laji 		= 'parametri_$parametri'
								and tuotteen_avainsanat.kieli		= '$kukarow[kieli]'
					            ORDER BY tuotteen_avainsanat.jarjestys, tuotteen_avainsanat.selite";
					$sxresult = mysql_query($query) or pupe_error($query);
				}
				elseif ($lisa_haku_osasto != "" or $lisa_haku_try != "" or $lisa_haku_tme != "" or $lisa_haku_malli != "") {
					$query = "	SELECT distinct t_av_$parametri.selite as $parametri
								FROM tuote
								JOIN tuotteen_avainsanat t_av_$parametri ON (t_av_$parametri.yhtio = tuote.yhtio and t_av_$parametri.laji = 'parametri_$parametri' and t_av_$parametri.tuoteno = tuote.tuoteno and t_av_$parametri.kieli = '$kukarow[kieli]' and t_av_$parametri.selite != '' ".${"lisa_haku_".$parametri}.")";

					$query .= $lisaquery;

					$query .= "	JOIN avainsana ON (avainsana.yhtio = t_av_$parametri.yhtio and avainsana.selite = '$parametri' and avainsana.laji = 'PARAMETRI' $avainlisa)
								WHERE tuote.yhtio = '$kukarow[yhtio]'
								$lisa_haku_osasto
								$lisa_haku_try
								$lisa_haku_tme
								$lisa_haku_malli
								$kieltolisa
								$extra_poislisa
								$poislisa_mulsel
								GROUP BY 1
								ORDER BY t_av_$parametri.jarjestys, t_av_$parametri.selite";
					$sxresult = mysql_query($query) or pupe_error($query);
				}

				if (is_resource($sxresult) and mysql_num_rows($sxresult) > 0) {

					echo "<div style='float: left; padding-right:4px; padding-top:4px;'><table valign='top'><tr><th>";

					if ($hae_ja_selaa_row['selite'] == 'B') {
						echo "<label>";
					}

					echo t_avainsana("PARAMETRI", "", " and avainsana.selite='$parametri'", "", "", "selitetark");

					if ($hae_ja_selaa_row['selite'] == 'B') {
						echo "</label>";
					}

					echo "</th></tr>";
					echo "<tr><td nowrap style='padding:0px'>";
					echo "<select name='mul_{$parametri}[]' ";

					if (!in_array("parametri_$parametri", array_map('strtolower', $monivalintalaatikot_normaali))) {
						echo " multiple='multiple' size='7' ";
					}

					if ($hae_ja_selaa_row['selite'] == 'B') {
						echo " style='width: 170px;' ";
					}

					echo "onchange='submit();'>";

					$mul_check = '';

					if (${"mul_".$parametri} != "") {
						if (in_array("PUPEKAIKKIMUUT", ${"mul_".$parametri})) {
							$mul_check = 'SELECTED';
						}
					}

					echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Näytä kaikki")."</option>";
					echo "<option value=''>",t("Ei valintaa"),"</option>";

					while (${$parametri."row"} = mysql_fetch_assoc($sxresult)) {
						$sel = '';

						if (count(${"mul_".$parametri}) > 0 and in_array(strtolower(trim(${$parametri."row"}["$parametri"])), ${"mul_".$parametri})) {
							$sel = 'SELECTED';
						}

						echo "<option value='".strtolower(${$parametri."row"}[$parametri])."' $sel>".${$parametri."row"}[$parametri]."</option>";
					}

					echo "</select>";
					echo "</td>";
					echo "</tr></table></div>";
				}
			}
		}

		if ($monivalintalaatikko == "TUOTEMYYJA") {
			echo "<div style='float: left; padding-right:4px; padding-top:4px;'><table valign='top'>";
			echo "<tr><th>",t("Tuotemyyjä"),"</th></tr>";
			echo "<tr>";

			// tehdään query
			$query = "	SELECT DISTINCT myyja, nimi
						FROM kuka
						WHERE yhtio = '$kukarow[yhtio]'
						AND myyja>0
						ORDER BY myyja";
			$sresult = mysql_query($query) or pupe_error($query);

			echo "<td nowrap style='padding:0px'><select name='mul_tuotemyyja[]' multiple='multiple' size='7' onchange='submit();'>";
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
			echo "</tr></table></div>";
		}

		if ($monivalintalaatikko == "TUOTEOSTAJA") {
			echo "<div style='float: left; padding-right:4px; padding-top:4px;'><table valign='top'>";
			echo "<tr><th>",t("Tuoteostaja"),"</th></tr>";
			echo "<tr>";

			$query = "	SELECT distinct myyja, nimi
						FROM kuka
						WHERE yhtio='$kukarow[yhtio]'
						AND myyja>0
						ORDER BY myyja";
			$sresult = mysql_query($query) or pupe_error($query);

			echo "<td nowrap style='padding:0px'><select name='mul_tuoteostaja[]' multiple='multiple' size='7' onchange='submit();'>";
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
			echo "</tr></table></div>";
		}

		if ($monivalintalaatikko == "ASIAKASOSASTO") {
			echo "<div style='float: left; padding-right:4px; padding-top:4px;'><table valign='top'>";
			echo "<tr><th>",t("Asiakasosasto"),"</th></tr>";
			echo "<tr>";

			$sresult = t_avainsana("ASIAKASOSASTO");

			echo "<td nowrap style='padding:0px'><select name='mul_asiakasosasto[]' multiple='multiple' size='7' onchange='submit();'>";
			echo "<option value=''>".t("Ei valintaa")."</option>";

			while($sxrow = mysql_fetch_array ($sresult)){
				$sel = '';

				if (count($mul_asiakasosasto) > 0) {
					if (in_array(trim($sxrow['selite']), $mul_asiakasosasto)) {
						$sel = 'SELECTED';
					}
				}

				echo "<option value='$sxrow[selite]' $sel>$sxrow[selite] $sxrow[selitetark]</option>";
			}
			echo "</select></td>";
			echo "</tr></table></div>";
		}

		if ($monivalintalaatikko == "ASIAKASRYHMA") {
			echo "<div style='float: left; padding-right:4px; padding-top:4px;'><table valign='top'>";
			echo "<tr><th>",t("Asiakasryhmä"),"</th></tr>";
			echo "<tr>";

			$sresult = t_avainsana("ASIAKASRYHMA");

			echo "<td nowrap style='padding:0px'><select name='mul_asiakasryhma[]' multiple='multiple' size='7' onchange='submit();'>";
			echo "<option value=''>".t("Ei valintaa")."</option>";

			while($sxrow = mysql_fetch_array ($sresult)){
				$sel = '';

				if (count($mul_asiakasryhma) > 0) {
					if (in_array(trim($sxrow['selite']), $mul_asiakasryhma)) {
						$sel = 'SELECTED';
					}
				}

				echo "<option value='$sxrow[selite]' $sel>$sxrow[selite] $sxrow[selitetark]</option>";
			}
			echo "</select></td>";
			echo "</tr></table></div>";
		}		
	}

	echo "<div style='clear: both;'></div>";
?>