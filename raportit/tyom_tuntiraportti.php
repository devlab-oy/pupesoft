<?php
	
	include('../inc/parametrit.inc');
		
	if (!function_exists("piirra_tuntiraportti")) {
		function piirra_tuntiraportti($asentaja = "") {
			global $kukarow, $yhtiorow, $vva, $kka, $ppa, $vvl, $kkl, $ppl;
			
			if ($vva != '' and $kka != '' and $ppa != '' and $vvl != '' and $kkl != '' and $ppl != ''){
				$lisa = " and lasku.luontiaika >= '$vva-$kka-$ppa 00:00:00' and lasku.luontiaika <= '$vvl-$kkl-$ppl 23:59:59' ";
			}

			if ($asentaja != "") {
				$asenlisa = " and kuka.kuka = '$asentaja' ";
			}
			else {
				$asenlisa = "";
			}
			
			if ($asentaja != "") {
				$keiklisa = " and matkalasku.toim_ovttunnus = '$asentaja' ";
			}
			else {
				$keiklisa = "";
			}
	
			$query = "	SELECT 
						lasku.tunnus,
						lasku.nimi,
						lasku.luontiaika,						
						group_concat(concat(left(kalenteri.pvmalku,16), '##', left(kalenteri.pvmloppu,16), '##', kuka.nimi, '##', kuka.kuka) ORDER BY kalenteri.pvmalku) asennuskalenteri
						FROM lasku
						JOIN yhtio ON lasku.yhtio=yhtio.yhtio
						JOIN tyomaarays ON tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus
						LEFT JOIN kalenteri ON kalenteri.yhtio = lasku.yhtio and kalenteri.tyyppi = 'asennuskalenteri' and kalenteri.liitostunnus = lasku.tunnus
						LEFT JOIN kuka ON kuka.yhtio=kalenteri.yhtio and kuka.kuka=kalenteri.kuka $asenlisa
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila in ('A','L','N','S')
						and lasku.alatila != 'X'
						$lisa
						GROUP BY 1,2,3
						ORDER BY lasku.tunnus";
			$sresult = mysql_query($query) or pupe_error($query);
		
			if (mysql_num_rows($sresult) > 0) {
				
				$echootsikot =  "<tr><th>".t("Työmääräys").":<br>".t("Nimi").":</th><th>".t("Työtunnit").":</th><th>".t("Matkalaskut").":</th></tr>";
					
				$kaletunnit = array();
	
				while ($row = mysql_fetch_array($sresult)) {
							
					$query  = "	SELECT distinct matkalasku.nimi, tilausrivi.tunnus, tilausrivi.tuoteno, tilausrivi.yksikko, tilausrivi.nimitys, tilausrivi.hinta, tilausrivi.kpl, tilausrivi.kommentti, tilausrivi.rivihinta
								FROM lasku keikka
								JOIN lasku liitosotsikko ON keikka.yhtio=liitosotsikko.yhtio and keikka.laskunro=liitosotsikko.laskunro and keikka.tila=liitosotsikko.tila and liitosotsikko.alatila='' and liitosotsikko.vanhatunnus!=0
								JOIN lasku matkalasku ON matkalasku.yhtio=liitosotsikko.yhtio and matkalasku.tunnus=liitosotsikko.vanhatunnus and matkalasku.tilaustyyppi='M'
								JOIN tilausrivi ON tilausrivi.yhtio=matkalasku.yhtio and tilausrivi.otunnus=matkalasku.tunnus
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
								<td valign='top'>$row[tunnus]<br>$row[nimi]</td>
								<td valign='top' style='padding: 0px;' align='right'>";

						if ($row["asennuskalenteri"] != "") {
							echo "<table width='100%'>";

							foreach(explode(",", $row["asennuskalenteri"]) as $asekale) {

								list($alku, $loppu, $nimi, $kuka) = explode("##", $asekale);

								$atstamp = mktime(substr($alku,11,2), substr($alku,14,2), 0, substr($alku,5,2), substr($alku,8,2), substr($alku,0,4));
								$ltstamp = mktime(substr($loppu,11,2), substr($loppu,14,2), 0, substr($loppu,5,2), substr($loppu,8,2), substr($loppu,0,4));

								$kaletunnit[$nimi] += ($ltstamp - $atstamp)/60;

								echo "<tr><td>$nimi:</td><td align='right'>".tv1dateconv($alku, "P")." - ".tv1dateconv($loppu, "P")."</td></tr>";																
							}

							echo "</table>";
						}

						echo "</td>";		
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
				}   
	
				if (count($kaletunnit) > 0 or count($matkakulut) > 0) {
					echo "<tr><td class='spec' valign='top'>".t("Yhteensä").":</td>";
		
					echo "<td class='spec' style='padding: 0px;' valign='top'><table width='100%'>";
				
					if (count($kaletunnit) > 0) {
						foreach ($kaletunnit as $kuka => $minuutit) {
			
							$tunti		= floor($minuutit/60);				
							$minuutti	= sprintf('%02d', $minuutit - ($tunti*60));
			
							echo "<tr><td class='spec'>$kuka:</td><td class='spec' align='right'>$tunti:$minuutti ".t("tuntia")."</td></tr>";
						}
					}
		
					echo "</table></td>";
		
		
					echo "<td class='spec' style='padding: 0px;' valign='top'><table width='100%'>";
		
					if (count($matkakulut) > 0) {
						foreach ($matkakulut as $kuka => $matkat) {
							foreach ($matkat as $tuoteno => $hinta) {
								echo "<tr><td class='spec'>$kuka:</td><td class='spec'>$tuoteno</td><td class='spec'></td><td class='spec' align='right'>".sprintf("%.2f", $hinta)." $yhtiorow[valkoodi]</td></tr>";
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
	
	echo "<font class='head'>".t("Tunti- ja kuluraportointi").":</font><hr><br>";

	if ($tee == 'raportoi') {
		
		echo "<table>";
		
		if ($asentaja == "ASENTAJITTAIN") {
			$query = "SELECT kuka, nimi from kuka where yhtio='$kukarow[yhtio]'";
			$vresult = mysql_query($query) or pupe_error($query);
			
			while ($vrow=mysql_fetch_array($vresult)) {
				piirra_tuntiraportti($vrow["kuka"]);
			}
		}
		else {
			piirra_tuntiraportti($asentaja);
		}
		
		echo "</table><br>";
	}

	if (!isset($kka)) $kka = date("m");
	if (!isset($vva)) $vva = date("Y");
	if (!isset($ppa)) $ppa = "01";
	if (!isset($kkl)) $kkl = date("m");
	if (!isset($vvl)) $vvl = date("Y");
	if (!isset($ppl)) $ppl = date("d", mktime(0, 0, 0, date("m")+1, 0, date("Y")));;
	
	echo "<table><tr>
			<form method='post' action='$PHP_SELF'>
			<input type='hidden' name='tee' value='raportoi'>
			<th colspan='4'>".t("Hae työmääräykset väliltä").":</th>
			<tr>
			<th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'></td>
			<td><input type='text' name='kka' value='$kka' size='3'></td>
			<td><input type='text' name='vva' value='$vva' size='5'></td>
			</tr>\n
			<tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppl' value='$ppl' size='3'></td>
			<td><input type='text' name='kkl' value='$kkl' size='3'></td>
			<td><input type='text' name='vvl' value='$vvl' size='5'></td></tr>";

	$query = "SELECT kuka, nimi from kuka where yhtio='$kukarow[yhtio]'";
	$vresult = mysql_query($query) or pupe_error($query);

	echo "<th></th><td colspan='3'><select name='asentaja'>";
	echo "<option value = ''>".t("Kaikki")."</option>";
	
	$sel="";
	if ($asentaja == "ASENTAJITTAIN") {
		$sel = "selected";
	}
	
	echo "<option value = 'ASENTAJITTAIN' $sel>".t("Asentajittain")."</option>";
	
	while ($vrow=mysql_fetch_array($vresult)) {
		$sel="";
		if ($asentaja == $vrow['kuka']) {
			$sel = "selected";
		}
		
		echo "<option value = '$vrow[kuka]' $sel>$vrow[nimi]</option>";
	}
	
	echo "</td></tr>";
	echo "</table>";
	
	echo "<br><br><input type='submit' value='Hae'></form>";
	
	require ("../inc/footer.inc");
	
?>