<?php

	include('../inc/parametrit.inc');

	if (!isset($tee)) $tee = '';

	if (!function_exists("piirra_tuntiraportti")) {
		function piirra_tuntiraportti($asentaja = "", $kukarow, $yhtiorow, $vva, $kka, $ppa, $vvl, $kkl, $ppl, $tyom_nro = '', $asiakasid = '', $asiakasosasto = '', $asiakasryhma = '', $tyojono = '', $tyostatus = '', $ytunnus = '') {
			
			if (trim($asentaja) != "") {
				$asentaja = mysql_real_escape_string($asentaja);

				$asenlisa = " and kuka.kuka = '$asentaja' ";
				$keiklisa = " and matkalasku.toim_ovttunnus = '$asentaja' ";
			}
			else {
				$asenlisa = "";
				$keiklisa = "";
			}
			
			if (trim($tyom_nro) != '') {
				$lisa .= " and lasku.tunnus = '".(int) $tyom_nro."' ";
			}

			if (trim($asiakasid) != '') {
				$lisa .= " and lasku.liitostunnus = '".(int) $asiakasid."' ";
			}

			$asiakaslisa = "";

			if (trim($asiakasosasto) != '') {
				$asiakaslisa .= " and asiakas.osasto = '".mysql_real_escape_string($asiakasosasto)."' ";
			}

			if (trim($asiakasryhma) != '') {
				$asiakaslisa .= " and asiakas.ryhma = '".mysql_real_escape_string($asiakasryhma)."' ";
			}

			if (trim($ytunnus) != '') {
				$asiakaslisa .= " and asiakas.ytunnus = '".mysql_real_escape_string($ytunnus)."' ";
			}

			$tyomaarayslisa = '';

			if (trim($tyojono) != '') {
				$tyomaarayslisa .= " and tyomaarays.tyojono = '".mysql_real_escape_string($tyojono)."' ";
			}

			if (trim($tyostatus) != '') {
				$tyomaarayslisa .= " and tyomaarays.tyostatus = '".mysql_real_escape_string($tyostatus)."' ";
			}
			
			if (trim($tyom_nro) == '' and trim($vva) != '' and trim($kka) != '' and trim($ppa) != '' and trim($vvl) != '' and trim($kkl) != '' and trim($ppl) != ''){
				$vva = (int) $vva;
				$kka = (int) $kka;
				$ppa = (int) $ppa;
				$vvl = (int) $vvl;
				$kkl = (int) $kkl;
				$ppl = (int) $ppl;

				$lisa = " and lasku.luontiaika >= '$vva-$kka-$ppa 00:00:00' and lasku.luontiaika <= '$vvl-$kkl-$ppl 23:59:59' ";
			}

			$query = "	SELECT 
						lasku.tunnus, lasku.nimi, lasku.nimitark, lasku.ytunnus, lasku.luontiaika, 
						(SELECT selitetark FROM avainsana WHERE avainsana.yhtio = lasku.yhtio AND avainsana.selite = tyomaarays.tyostatus AND avainsana.laji = 'TYOM_TYOSTATUS') tyostatus,
						lasku.erikoisale, lasku.valkoodi, 
						group_concat(DISTINCT concat(left(kalenteri.pvmalku,16), '##', left(kalenteri.pvmloppu,16), '##', kuka.nimi, '##', kuka.kuka) ORDER BY kalenteri.pvmalku) asennuskalenteri
						FROM lasku
						JOIN yhtio ON (lasku.yhtio = yhtio.yhtio)
						JOIN tyomaarays ON (tyomaarays.yhtio = lasku.yhtio and tyomaarays.otunnus = lasku.tunnus $tyomaarayslisa)
						JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus $asiakaslisa)
						LEFT JOIN kalenteri ON (kalenteri.yhtio = lasku.yhtio and kalenteri.tyyppi = 'asennuskalenteri' and kalenteri.liitostunnus = lasku.tunnus)
						LEFT JOIN kuka ON (kuka.yhtio = kalenteri.yhtio and kuka.kuka = kalenteri.kuka $asenlisa)
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila IN ('A','L','N','S','C')
						and lasku.tilaustyyppi = 'A'
						$lisa
						GROUP BY 1,2,3,4,5,6,7,8
						ORDER BY lasku.tunnus";
			$sresult = mysql_query($query) or pupe_error($query);
				
			if (mysql_num_rows($sresult) > 0) {
				
				$echootsikot =  "<tr><th>".t("Työmääräys").":<br>".t("Nimi").":<br>".t("Ytunnus").":</th><th>".t("Työnjohdon työtunnit").":</th><th>".t("Asentajien työtunnit")."</th><th>".t("Työstatus").":</th><th>".t("Matkalaskut").":</th></tr>";
					
				$kaletunnit = array();
				$asekaletunnit = array();
				$rivihinnat = array();
				$kplyht = '';
				$i = 0;
	
				$query_ale_lisa = generoi_alekentta('M');
	
				while ($row = mysql_fetch_array($sresult)) {

					$query  = "	SELECT DISTINCT matkalasku.nimi, tilausrivi.tunnus, tilausrivi.tuoteno, tilausrivi.yksikko, tilausrivi.nimitys, tilausrivi.hinta, tilausrivi.kpl, tilausrivi.kommentti, tilausrivi.rivihinta
								FROM lasku keikka
								JOIN lasku liitosotsikko ON (keikka.yhtio = liitosotsikko.yhtio and keikka.laskunro = liitosotsikko.laskunro and keikka.tila = liitosotsikko.tila and liitosotsikko.alatila = '' and liitosotsikko.vanhatunnus != 0)
								JOIN lasku matkalasku ON (matkalasku.yhtio = liitosotsikko.yhtio and matkalasku.tunnus = liitosotsikko.vanhatunnus and matkalasku.tilaustyyppi = 'M')
								JOIN tilausrivi ON (tilausrivi.yhtio = matkalasku.yhtio and tilausrivi.otunnus = matkalasku.tunnus)
								WHERE keikka.yhtio		= '$kukarow[yhtio]'
								and keikka.tila			= 'K'
								and keikka.alatila		= 'T'
								and keikka.liitostunnus	= '$row[tunnus]'
								and keikka.ytunnus		= '$row[tunnus]'
								and tilausrivi.kpl 		> 0
								and tilausrivi.tyyppi  != 'D'
								$keiklisa";
					$keikkares = mysql_query($query) or pupe_error($query);

					if ($asentaja == "" or mysql_num_rows($keikkares) > 0 or $row["asennuskalenteri"] != "") {
						
						echo "$echootsikot";
						
						if ($asentaja == "") {
							$echootsikot = "";
						}
						
						echo "<tr>
								<td valign='top'>$row[tunnus]<br/>$row[nimi]";

								if (trim($row['nimitark']) != '') echo "<br/>$row[nimitark]";
								if (trim($row['ytunnus']) != '') echo "<br/>$row[ytunnus]";

								echo "</td>
								<td valign='top' style='padding: 0px;' align='right'>";

						if ($row["asennuskalenteri"] != "") {
							echo "<table width='100%'>";

							foreach (explode(",", $row["asennuskalenteri"]) as $asekale) {

								list($alku, $loppu, $nimi, $kuka) = explode("##", $asekale);

								$atstamp = mktime(substr($alku,11,2), substr($alku,14,2), 0, substr($alku,5,2), substr($alku,8,2), substr($alku,0,4));
								$ltstamp = mktime(substr($loppu,11,2), substr($loppu,14,2), 0, substr($loppu,5,2), substr($loppu,8,2), substr($loppu,0,4));

								if (!isset($kaletunnit[$nimi])) $kaletunnit[$nimi] = 0;

								$kaletunnit[$nimi] += ($ltstamp - $atstamp)/60;

								echo "<tr><td>$nimi:</td><td align='right'>".tv1dateconv($alku, "P")." - ".tv1dateconv($loppu, "P")."</td></tr>";																
							}

							echo "</table>";
						}

						$query = "	SELECT GROUP_CONCAT(tilausrivi.yksikko,'#',if(tuote.tuotetyyppi = 'K', tilausrivi.varattu, 0)) yksikko, 
									sum(if(tuote.tuotetyyppi = '', round(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa},2), 0)) rivihinta_tuote,
									sum(if(tuote.tuotetyyppi = 'K', round(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa},2), 0)) rivihinta_tyo
									FROM tilausrivi 
									JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
									WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
									AND tilausrivi.otunnus = '$row[tunnus]'";
						$rivihinta_res = mysql_query($query) or pupe_error($query);
						$rivihinta_row = mysql_fetch_assoc($rivihinta_res);

						if (!isset($rivihinnat['Tuotteet'][$row['valkoodi']])) $rivihinnat['Tuotteet'][$row['valkoodi']] = 0;
						if (!isset($rivihinnat['Työt'][$row['valkoodi']])) $rivihinnat['Työt'][$row['valkoodi']] = 0;

						$rivihinnat['Tuotteet'][$row['valkoodi']] += $rivihinta_row['rivihinta_tuote'];
						$rivihinnat['Työt'][$row['valkoodi']] += $rivihinta_row['rivihinta_tyo'];
						
						if ($rivihinta_row['yksikko'] != '') {
							$kplyht = $rivihinta_row['yksikko'];
						}

						echo "</td>";

						echo "<td valign='top' style='padding: 0px'>";

						if ($row["tunnus"] != "") {

							$query = "	SELECT concat(left(kalenteri2.pvmalku,16), '##', left(kalenteri2.pvmloppu,16), '##', kuka.nimi, '##', kuka.kuka) kalenteri
										FROM kalenteri kalenteri2
										LEFT JOIN kuka ON (kuka.yhtio = kalenteri2.yhtio and kuka.kuka = kalenteri2.kuka $asenlisa)
										WHERE kalenteri2.yhtio = '$kukarow[yhtio]'
										AND kalenteri2.tyyppi = 'kalenteri'
										AND kalenteri2.kentta02 = '$row[tunnus]'";
							$kalenteri_res = mysql_query($query) or pupe_error($query);

							while ($kalenteri_row = mysql_fetch_assoc($kalenteri_res)) {
								echo "<table width='100%'>";

								foreach (explode(",", $kalenteri_row["kalenteri"]) as $asekale) {

									list($alku, $loppu, $nimi, $kuka) = explode("##", $asekale);

									$atstamp = mktime(substr($alku,11,2), substr($alku,14,2), 0, substr($alku,5,2), substr($alku,8,2), substr($alku,0,4));
									$ltstamp = mktime(substr($loppu,11,2), substr($loppu,14,2), 0, substr($loppu,5,2), substr($loppu,8,2), substr($loppu,0,4));

									if (!isset($asekaletunnit[$nimi])) $asekaletunnit[$nimi] = 0;

									$asekaletunnit[$nimi] += ($ltstamp - $atstamp)/60;

									echo "<tr><td>$nimi:</td><td align='right'>".tv1dateconv($alku, "P")." - ".tv1dateconv($loppu, "P")."</td></tr>";
								}

								echo "</table>";
							}
						}

						echo "</td>";

						echo "<td valign='top' style='padding: 0px'>$row[tyostatus]</td>";
	
						echo "<td valign='top' style='padding: 0px;'>";

						if (mysql_num_rows($keikkares) > 0) {
							echo "<table width='100%'>";

							while ($keikkarow = mysql_fetch_array($keikkares)) {
								echo "<tr><td>$keikkarow[nimi]:</td><td>$keikkarow[nimitys]</td><td align='right'>".((float) $keikkarow["kpl"])."</td><td align='right'>".sprintf("%.2f", $keikkarow["hinta"])." $yhtiorow[valkoodi]</td></tr>";


								$matkakulut[$keikkarow["nimi"]][$keikkarow["nimitys"]] += $keikkarow["rivihinta"];				
							}	
							echo "</table>";
						}
						echo "</td>";				
						echo "</tr>";
					}	

					$i++;
				}   

				if (isset($rivihinnat) and count($rivihinnat) > 0) {
					echo "<tr><td class='spec' valign='top'>".t("Tuotteet ja työt yhteensä").":</td>";

					echo "<td class='spec' style='padding: 0px;' valign='top'><table width='100%'>";

					$hinnatyht = array();

					foreach ($rivihinnat as $tuotetyyppi => $hinta) {
						foreach ($hinta as $valuutta => $rivihinta) {
							if ($rivihinta == 0) continue;

							echo "<tr><td class='spec' align='left'>",t("$tuotetyyppi").":";

							if ($tuotetyyppi == 'Työt') {
								echo "<br/>";
								$yksgroup = array();

								foreach (explode(',', $kplyht) as $yksikko_kpl) {
									
									list($yksikko, $kpl) = explode('#', $yksikko_kpl);
									if ($yksikko != '' and $kpl != 0) {
										$yksgroup[$yksikko] += $kpl;
									}
								}

								$i = 0;
								foreach ($yksgroup as $yksikko => $kpl) {
									if ($i != 0) echo "<br/>";
									echo "$kpl ".t_avainsana("Y", "", " and avainsana.selite='$yksikko'", "", "", "selite");
									$i++;
								}
								
							}

							echo "</td><td class='spec' align='right'>$rivihinta $valuutta</td></tr>";

							if (!isset($hinnatyht[$valuutta])) $hinnatyht[$valuutta] = 0;

							$hinnatyht[$valuutta] += $rivihinta;
						}
					}

					echo "<tr><td class='spec'>",t("Yhteensä"),":</td>";

					foreach ($hinnatyht as $val => $hinta) {
						echo "<td class='spec' align='right'>$hinta $valuutta</td>";
					}

					echo "</tr></table></td>";
					echo "<td class='spec'>&nbsp;</td><td class='spec'>&nbsp;</td><td class='spec'>&nbsp;</td></tr>";			
				}

				if ((isset($kaletunnit) and count($kaletunnit) > 0) or (isset($matkakulut) and count($matkakulut) > 0) or (isset($asekaletunnit) and count($asekaletunnit) > 0)) {
					echo "<tr><td class='spec' valign='top'>".t("Tunnit yhteensä").":</td>";
		
					echo "<td class='spec' style='padding: 0px;' valign='top'><table width='100%'>";
				
					if (count($kaletunnit) > 0) {
						foreach ($kaletunnit as $kuka => $minuutit) {
			
							$tunti		= floor($minuutit/60);				
							$minuutti	= sprintf('%02d', $minuutit - ($tunti*60));
			
							echo "<tr><td class='spec'>$kuka:</td><td class='spec' align='right'>$tunti:$minuutti ".t("tuntia")."</td></tr>";
						}
					}
		
					echo "</table></td>";
		

					echo "<td class='spec' style='padding: 0px' valign='top'><table width='100%'>";

					if (count($asekaletunnit) > 0) {
						foreach ($asekaletunnit as $kuka => $minuutit) {
			
							$tunti		= floor($minuutit/60);				
							$minuutti	= sprintf('%02d', $minuutit - ($tunti*60));
			
							echo "<tr><td class='spec'>$kuka:</td><td class='spec' align='right'>$tunti:$minuutti ".t("tuntia")."</td></tr>";
						}
					}

					echo "</table></td>";

					echo "<td class='spec'>&nbsp;</td><td class='spec' style='padding: 0px;' valign='top'><table width='100%'>";
		
					if (isset($matkakulut) and count($matkakulut) > 0) {
						foreach ($matkakulut as $kuka => $matkat) {
							foreach ($matkat as $tuoteno => $hinta) {
								echo "<tr><td class='spec'>$kuka:</td><td class='spec'>$tuoteno</td><td class='spec'>&nbsp;</td><td class='spec' align='right'>".sprintf("%.2f", $hinta)." $yhtiorow[valkoodi]</td></tr>";
							}
						}
					}
				
					echo "</table></td>";
					echo "</tr>";			
					echo "<tr><td class='back'><br></td></tr>";
				}
			}
		}
	}

	//Voidaan tarvita jotain muuttujaa täältä
	if (isset($muutparametrit)) {
		list($tyom_nro,$asentaja,$tyojono,$tyostatus,$asiakasid,$asiakasosasto,$asiakasryhma,$kka,$vva,$ppa,$kkl,$vvl,$ppl,$tultiin) = explode('#', $muutparametrit);
	}

	if (!isset($tyom_nro)) $tyom_nro = '';
	if (!isset($ytunnus)) $ytunnus = '';
	if (!isset($asentaja)) $asentaja = '';
	if (!isset($tyojono)) $tyojono = '';
	if (!isset($tyostatus)) $tyostatus = '';
	if (!isset($asiakasid)) $asiakasid = '';
	if (!isset($asiakasosasto)) $asiakasosasto = '';
	if (!isset($asiakasryhma)) $asiakasryhma = '';
	if (!isset($kka)) $kka = date("m");
	if (!isset($vva)) $vva = date("Y");
	if (!isset($ppa)) $ppa = "01";
	if (!isset($kkl)) $kkl = date("m");
	if (!isset($vvl)) $vvl = date("Y");
	if (!isset($ppl)) $ppl = date("d", mktime(0, 0, 0, date("m")+1, 0, date("Y")));

	echo "<font class='head'>",t("Tunti- ja kuluraportointi"),":</font><hr>";

	echo "	<form method='post' action='$PHP_SELF'>
			<input type='hidden' name='tee' value='raportoi'>
			<table>
			<tr>
			<th>",t("Työmääräys numero"),"</th>
			<td colspan='3'><input type='text' name='tyom_nro' value='$tyom_nro'></td>
			</tr>
			<tr>
			<th>",t("Asiakkaan nimi")," / ",t("Asiakasnumero")," / ",t("Ytunnus"),"</th>
			<td colspan='3'><input type='text' name='ytunnus' value='$ytunnus'></td>
			</tr>";

	$vresult = t_avainsana("ASIAKASOSASTO");

	echo "<tr><th>",t("Asiakasosasto"),"</th><td colspan='3'><select name='asiakasosasto'>";
	echo "<option value = ''>".t("Kaikki")."</option>";

	while ($vrow = mysql_fetch_assoc($vresult)) {
		$sel = "";
		if ($asiakasosasto == $vrow['selite']) {
			$sel = "selected";
		}

		echo "<option value = '$vrow[selite]' $sel>$vrow[selitetark]</option>";
	}

	echo "</select></td></tr>";

	$vresult = t_avainsana("ASIAKASRYHMA");

	echo "<tr><th>",t("Asiakasryhmä"),"</th><td colspan='3'><select name='asiakasryhma'>";
	echo "<option value = ''>".t("Kaikki")."</option>";

	while ($vrow = mysql_fetch_assoc($vresult)) {
		$sel = "";
		if ($asiakasryhma == $vrow['selite']) {
			$sel = "selected";
		}

		echo "<option value = '$vrow[selite]' $sel>$vrow[selitetark]</option>";
	}

	echo "</select></td></tr>";

	$vresult = t_avainsana("TYOM_TYOLINJA");

	echo "<tr><th>",t("Työlinja"),"</th><td colspan='3'><select name='asentaja'>";
	echo "<option value = ''>".t("Kaikki")."</option>";
	
	$sel = "";
	if ($asentaja == "ASENTAJITTAIN") {
		$sel = "selected";
	}
	
	echo "<option value = 'ASENTAJITTAIN' $sel>".t("Asentajittain")."</option>";
	
	while ($vrow = mysql_fetch_assoc($vresult)) {
		$sel = "";
		if ($asentaja == $vrow['selitetark']) {
			$sel = "selected";
		}
		
		echo "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
	}
	
	echo "</select></td></tr>";

	$vresult = t_avainsana("TYOM_TYOJONO");

	echo "<tr><th>",t("Työjono"),"</th><td colspan='3'><select name='tyojono'>";
	echo "<option value = ''>".t("Kaikki")."</option>";
	
	while ($vrow = mysql_fetch_assoc($vresult)) {
		$sel = "";
		if ($tyojono == $vrow['selite']) {
			$sel = "selected";
		}
		
		echo "<option value = '$vrow[selite]' $sel>$vrow[selitetark]</option>";
	}
	
	echo "</select></td></tr>";

	$vresult = t_avainsana("TYOM_TYOSTATUS");

	echo "<tr><th>",t("Työstatus"),"</th><td colspan='3'><select name='tyostatus'>";
	echo "<option value = ''>".t("Kaikki")."</option>";
	
	while ($vrow = mysql_fetch_assoc($vresult)) {
		$sel = "";
		if ($tyostatus == $vrow['selite']) {
			$sel = "selected";
		}
		
		echo "<option value = '$vrow[selite]' $sel>$vrow[selitetark]</option>";
	}
	
	echo "</select></td></tr>";

	echo "	<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'></td>
			<td><input type='text' name='kka' value='$kka' size='3'></td>
			<td><input type='text' name='vva' value='$vva' size='5'></td>
			</tr>
			<tr>
			<th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppl' value='$ppl' size='3'></td>
			<td><input type='text' name='kkl' value='$kkl' size='3'></td>
			<td><input type='text' name='vvl' value='$vvl' size='5'></td>
			</tr>";

	echo "</table>";

	echo "<input type='submit' value='",t("Hae"),"'></form><br/><br/>";

	if ($tee == 'raportoi' and trim($ytunnus) != '' and $tultiin != 'asiakashaku') {

		$ahlopetus 	= "raportit/tyom_tuntiraportti.php////tee=$tee//ytunnus=$ytunnus//tyom_nro=$tyom_nro//vva=$vva//kka=$kka//ppa=$ppa//vvl=$vvl//kkl=$kkl//ppl=$ppl";

		$tultiin = 'asiakashaku';

		//Voidaan tarvita jotain muuttujaa täältä
		$muutparametrit = $tyom_nro.'#'.$asentaja.'#'.$tyojono.'#'.$tyostatus.'#'.$asiakasid.'#'.$asiakasosasto.'#'.$asiakasryhma.'#'.$kka.'#'.$vva.'#'.$ppa.'#'.$kkl.'#'.$vvl.'#'.$ppl.'#'.$tultiin;

		if (@include("inc/asiakashaku.inc"));
		elseif (@include("asiakashaku.inc"));
		else exit;
	}

	if ($tee == 'raportoi') {
		
		echo "<table>";
		
		if ($asentaja == "ASENTAJITTAIN") {
			$query = "SELECT kuka, nimi from kuka where yhtio = '$kukarow[yhtio]'";
			$vresult = mysql_query($query) or pupe_error($query);
			
			while ($vrow=mysql_fetch_array($vresult)) {
				piirra_tuntiraportti($vrow["kuka"], $kukarow, $yhtiorow, $vva, $kka, $ppa, $vvl, $kkl, $ppl, $tyom_nro, $asiakasid, $asiakasosasto, $asiakasryhma, $tyojono, $tyostatus, $ytunnus);
			}
		}
		else {
			piirra_tuntiraportti($asentaja, $kukarow, $yhtiorow, $vva, $kka, $ppa, $vvl, $kkl, $ppl, $tyom_nro, $asiakasid, $asiakasosasto, $asiakasryhma, $tyojono, $tyostatus, $ytunnus);
		}
		
		echo "</table><br>";
	}

	require ("../inc/footer.inc");
	
?>