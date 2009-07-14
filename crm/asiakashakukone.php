<?php

if (isset($_POST["tee"])) {
	if($_POST["tee"] == 'lataa_tiedosto' or $_POST["tee"] == "tallenna_ja_laheta_mailiin") $lataa_tiedosto=1;
	if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
}
	
//parametrit
if (strpos($_SERVER['SCRIPT_NAME'], "asiakashakukone.php")  !== FALSE) {
	require('../inc/parametrit.inc');
}


//valittujen tallennus excelmuotoon ja l‰hetys s‰hkˆpostiin
if ($tee == 'tallenna_ja_laheta_mailiin') {

	if(require_once('Spreadsheet/Excel/Writer.php')) {
		//keksit‰‰n failille joku varmasti uniikki nimi:
		$filenimi = "/tmp/".$yhtiorow["yhtio"]."-Yhteyshenkilot_".md5(uniqid(mt_rand(), true)).".xls";
		$workbook = new Spreadsheet_Excel_Writer($filenimi);
		$workbook->setVersion(8);
		$worksheet =& $workbook->addWorksheet('Yhteyshenkilˆt');
		$format_bold =& $workbook->addFormat();
		$excelrivi = 0;		
	}
	else {
		die(t("<br>VIRHE: Uuden exceltaulukon luonti ep‰onnistui!<br>"));
	}
	
	foreach ($lahetettavat_asiakastunnukset as $asiakastunnus) { 
		
		$query = "	SELECT email
					FROM yhteyshenkilo
					WHERE liitostunnus='$asiakastunnus' and tyyppi='A' and email != ''";
		$result = mysql_query($query) or pupe_error($query);
		if(mysql_num_rows($result)>0) {
			while ($yhteyshenkilo = mysql_fetch_array($result)) {
				$worksheet->write($excelrivi, 0, strtolower($yhteyshenkilo["email"]));
				$excelrivi++;
			}
		}
	}

	//Rivit lis‰tty, suljetaan exceltiedosto
	$workbook->close();
	//	Lyˆd‰‰n filekoko tietoon uusiksi
	header("Content-Length: ".filesize($filenimi));
	print(file_get_contents($filenimi));
	exec("rm $filenimi");
	
	//	Over and out!
	die();
}


if (strpos($_SERVER['SCRIPT_NAME'], "asiakashakukone.php")  !== FALSE) {
	js_popup();
	js_showhide();
	js_selectAllCheckboxesByName();
	enable_ajax();
}

//echo "<pre>".print_r($_REQUEST, true)."</pre>";

if($tee == "ASIAKASHAKU") {
	tee_asiakashaku($haku, $formi);
}

require('inc/kalenteri.inc');		

$kaupungit = array(	"helsinki", "kotka", "kouvola", "lappeenranta", "hameenlinna", "turku", "forssa", "lahti", "mikkeli", "savonlinna", "tampere", "pori", "jyv‰skyl‰", "pieksam‰ki", "joensuu", "kuopio", "sein‰joki", "kuopio", "vaasa", "kokkola", "ylivieska", "kajaani", "oulu", "kemi", "rovaniemi");

function hae_postialue ($kaupunki) {
	switch ($kaupunki) {
		case "helsinki":
			$min = "00000";
			$max = "10000";
			break;
		case "kotka":
			$min = "48000";
			$max = "49000";
			break;
		case "kouvola":
			$min = "45000";
			$max = "47000";
			break;
		case "lappeenranta":
			$min = "53000";
			$max = "56000";
			$jokeri = " and asiakas.postino LIKE ('59%')";
			break;
		case "hameenlinna":
			$min = "11000";
			$max = "14000";
			break;
		case "turku":
			$min = "20000";
			$max = "27000";
			break;
		case "forssa":
			$min = "30000";
			$max = "32000";
			break;
		case "lahti":
			$min = "15000";
			$max = "19000";
			break;
		case "mikkeli":
			$min = "50000";
			$max = "52000";
			break;
		case "savonlinna":
			$min = "57000";
			$max = "58000";
			break;
		case "tampere":
			$min = "33000";
			$max = "39000";
			break;
		case "pori":
			$min = "28000";
			$max = "29000";
			break;
		case "jyv‰skyl‰":
			$min = "40000";
			$max = "44000";
			break;
		case "pieksam‰ki":
			$min = "76000";
			$max = "79000";
			break;
		case "joensuu":
			$min = "80000";
			$max = "83000";
			break;
		case "kuopio":
			$min = "70000";
			$max = "75000";
			break;
		case "sein‰joki":
			$min = "60000";
			$max = "64000";
			break;
		case "vaasa":
			$min = "65000";
			$max = "66000";
			break;
		case "kokkola":
			$min = "67000";
			$max = "69000";
			break;
		case "ylivieska":
			$min = "84000";
			$max = "86000";
			break;
		case "kajaani":
			$min = "87000";
			$max = "89000";
			break;
		case "oulu":
			$min = "90000";
			$max = "93000";
			break;
		case "kemi":
			$min = "94000";
			$max = "95000";
			break;
		case "rovaniemi":
			$min = "96000";
			$max = "99000";
			break;
		default:
			echo "tuntematon kaupunki '$kaupunki'<br>";
			break;
	}
	
	return array($min, $max, $jokeri);
}

if($tee == "") {

	//	Setit t‰nne
	if($setti == "laajenna") $aja_kysely = "tmpquery";
	
	aja_kysely();

	if($setti == "laajenna") {
		unset($group);
		$hakupalkki = "OHI";
		
		foreach($laajennus as $l => $a) {
			unset(${$l});
			${$l} = $a;
		}
	}
	
	if($hakupalkki != "OHI") {

		if($hakupalkki == "mini") {
			echo "<br><a href=\"javascript:showhide('hakupalkki');\">".t("N‰yt‰ hakukriteerit")."</a><br><br><div id = 'hakupalkki' style='display:none'>";
		}

		echo "	
			<font class='message'>".t("Anna hakuparametrit").":</font>
			<form method='post' id='tiedot_form'>
			<input type='hidden' name='toim' value='$toim'>
			<input type='hidden' name='hakupalkki' value='mini'>
			<table width='750px'>";

		echo "
				<tr>
					<th>".t("Piiri")."</th><th>".t("Asiakasluokka")."</th><th>".t("Asiakasryhm‰")."</th><th>".t("Asiakasosasto")."</th>
				</tr>
				<tr>";			

		//	Piirit
		$query = "	SELECT selite, concat_ws(' - ',selite, selitetark)
					FROM avainsana
					WHERE yhtio='$kukarow[yhtio]' and laji='PIIRI'
					ORDER BY jarjestys, selite";
		$mryresult = mysql_query($query) or pupe_error($query);		
		echo "		<td NOWRAP>
						<select name='piiri[]' multiple='TRUE' size='6' style='width: 100%;' style='width: 100%;'>";
		while($row = mysql_fetch_array($mryresult)) {
			$sel = "";
			if (array_search($row[0], $piiri) !== false) {
				$sel = 'selected';
			}
			echo "		<option value='$row[0]' $sel>$row[1]</option>";
		}

		echo "			</select>
						<br>
						".t("Piireitt‰in").": <input type='checkbox' name='group[piiri]' value='checked' {$group["piiri"]}> prio: <input type='text' name='prio[piiri]' value='{$prio["piiri"]}' size='2'>
					</td>";

		//	Luokat
		$query = "	SELECT selite, concat_ws(' - ',selite, selitetark)
					FROM avainsana
					WHERE yhtio='$kukarow[yhtio]' and laji='ASIAKASLUOKKA'
					ORDER BY jarjestys, selite";
		$abures = mysql_query($query) or pupe_error($query);
		echo "		<td NOWRAP>
						<select name='asiakasluokka[]' multiple='TRUE' size='6' style='width: 100%;'>";
		while($row = mysql_fetch_array($abures)) {
			$sel = "";
			if (array_search($row[0], $asiakasluokka) !== false) {
				$sel = 'selected';
			}
			echo "		<option value='$row[0]' $sel>$row[1]</option>";
		}

		echo "			</select>
						<br>
						".t("Luokittain").": <input type='checkbox' name='group[asiakasluokka]' value='checked' {$group["asiakasluokka"]}> prio: <input type='text' name='prio[asiakasluokka]' value='{$prio["asiakasluokka"]}' size='2'>
					</td>";

		//	Asiakasryhm‰
		$query = "	SELECT selite, concat_ws(' - ',selite, selitetark)
					FROM avainsana
					WHERE yhtio='$kukarow[yhtio]' and laji='ASIAKASRYHMA'
					ORDER BY jarjestys, selite";
		$abures = mysql_query($query) or pupe_error($query);
		echo "		<td NOWRAP>
						<select name='asiakasryhma[]' multiple='TRUE' size='6' style='width: 100%;'>";
		while($row = mysql_fetch_array($abures)) {
			$sel = "";
			if (array_search($row[0], $asiakasryhma) !== false) {
				$sel = 'selected';
			}
			echo "		<option value='$row[0]' $sel>$row[1]</option>";
		}
		echo "			</select>
						<br>
						".t("Ryhmitt‰in").": <input type='checkbox' name='group[asiakasryhma]' value='checked' {$group["asiakasryhma"]}> prio: <input type='text' name='prio[asiakasryhma]' value='{$prio["asiakasryhma"]}' size='2'>
					</td>";

		//	Osastot
		$query = "	SELECT selite, concat_ws(' - ',selite, selitetark)
					FROM avainsana
					WHERE yhtio='$kukarow[yhtio]' and laji='ASIAKASOSASTO'
					ORDER BY jarjestys, selite";
		$abures = mysql_query($query) or pupe_error($query);
		echo "		<td NOWRAP>
						<select name='asiakasosasto[]' multiple='TRUE' size='6' style='width: 100%;'>";
		while($row = mysql_fetch_array($abures)) {
			$sel = "";
			if (array_search($row[0], $asiakasosasto) !== false) {
				$sel = 'selected';
			}
			echo "		<option value='$row[0]' $sel>$row[1]</option>";
		}
		echo "			</select>
						<br>
						".t("Osastoittain").": <input type='checkbox' name='group[asiakasosasto]' value='checked' {$group["asiakasosasto"]}> prio: <input type='text' name='prio[asiakasosasto]' value='{$prio["asiakasosasto"]}' size='2'>

					</td>";
		
		echo "
				</tr>
			</table>
			<br>";

		echo "	
				<table width='750px'>
				<tr>
					<th>".t("As. Maa")."</th><th>".t("As. Postipaikka")."</th><th>".t("Tm. Maa")."</th><th>".t("Tm. Postipaikka")."</th>
				</tr>
				<tr>";

			
		//	As. Maa
		$selt = "";
		if(array_search("", $maa)!==false) $selt = "SELECTED";
		echo "		<td NOWRAP>
						<select name='maa[]' multiple='TRUE' size='8' style='width: 100%;'>
						<option value='' $selt>".t("Ei maata")."</option>";

		$query = "	SELECT distinct(maa)
					FROM asiakas
					WHERE yhtio = '$kukarow[yhtio]' and maa != ''
					ORDER BY maa";
		$result = mysql_query($query) or pupe_error($query);
		while($maarow = mysql_fetch_array($result)) {
			$sel = "";
			if (array_search($maarow[0], $maa) !== false) {
				$sel = 'selected';
			}
 			echo "			<option value='$maarow[maa]' $sel>".maa($maarow[maa])."</option>";
		}
						
		echo "
						</select>
						<br>
						".t("As. Maittain").": <input type='checkbox' name='group[maa]' value='checked' {$group["maa"]}> prio: <input type='text' name='prio[maa]' value='{$prio["maa"]}' size='2'>
					</td>";

		//	As. Postipaikat
		$selt = "";
		if(array_search("", $postitp)!==false) $selt = "SELECTED";
		echo "		<td NOWRAP>
						<select name='postitp[]' multiple='TRUE' size='8' style='width: 100%;'>
						<option value='' $selt>".t("Ei postipaikkaa")."</option>
						<optgroup label=".t("Postialue").">";
						
		foreach($kaupungit as $k) {
			$sel = "";
			if (array_search("kaupunki:$k", $postitp) !== false) {
				$sel = 'selected';
			}
			echo "			<option value='kaupunki:$k' $sel>".ucfirst(t($k))."</option>";
		}
						
		echo "			</optgroup>";
		
		$query = "	SELECT DISTINCT maa
					FROM asiakas
					WHERE yhtio='$kukarow[yhtio]' and maa != ''
					ORDER BY maa IN ('$yhtiorow[maa]') desc, maa";
		$mabures = mysql_query($query) or pupe_error($query);

		while($maarow = mysql_fetch_array($mabures)) {
			echo "<optgroup label='".maa($maarow["maa"])."'>";
			
			$query = "	SELECT DISTINCT postitp
						FROM asiakas
						WHERE yhtio='$kukarow[yhtio]' and maa = '$maarow[maa]' and postitp != ''
						ORDER BY postitp";
			$abures = mysql_query($query) or pupe_error($query);
			
			while($row = mysql_fetch_array($abures)) {
				$sel = "";
				if (array_search($row[0], $postitp) !== false) {
					$sel = 'selected';
				}
				echo "		<option value='$row[0]' $sel>$row[0]</option>";
			}
			echo "</optgroup>";
		}
		
		echo "			</select>
						<br>
						".t("As. Postipaikoittain").": <input type='checkbox' name='group[postitp]' value='checked' {$group["postitp"]}> prio: <input type='text' name='prio[postitp]' value='{$prio["postitp"]}' size='2'>
					</td>";

		//	Tm. Maa
		$selt = "";
		if(array_search("", $toim_maa)!==false) $selt = "SELECTED";
		echo "		<td NOWRAP>
						<select name='toim_maa[]' multiple='TRUE' size='8' style='width: 100%;'>
						<option value='' $selt>".t("Ei toimmitusmaata")."</option>";

		$query = "	SELECT distinct(toim_maa)
					FROM asiakas
					WHERE yhtio = '$kukarow[yhtio]' and toim_maa != ''";
		$result = mysql_query($query) or pupe_error($query);
		while($maarow = mysql_fetch_array($result)) {
			$sel = "";
			if (array_search($maarow[0], $toim_maa) !== false) {
				$sel = 'selected';
			}
 			echo "			<option value='$maarow[0]' $sel>".maa($maarow["toim_maa"])."</option>";
		}

		echo "
						</select>
						<br>
						".t("Tm. Maittain").": <input type='checkbox' name='group[toim_maa]' value='checked' {$group["toim_maa"]}> prio: <input type='text' name='prio[toim_maa]' value='{$prio["toim_maa"]}' size='2'>
					</td>";

		//	Tm. Postipaikat
		$selt = "";
		if(array_search("", $toim_postitp)!==false) $selt = "SELECTED";
		echo "		<td NOWRAP>
						<select name='toim_postitp[]' multiple='TRUE' size='8' style='width: 100%;'>
						<option value='' $selt>".t("Ei toimituspostipaikkaa")."</option>
						<optgroup label=".t("Postialue").">";
						
		foreach($kaupungit as $k) {
			$sel = "";
			if (array_search("kaupunki:$k", $toim_postitp) !== false) {
				$sel = 'selected';
			}
			echo "			<option value='kaupunki:$k' $sel>".ucfirst(t($k))."</option>";
		}
						
		echo "			</optgroup>";
					
		$query = "	SELECT DISTINCT toim_maa
					FROM asiakas
					WHERE yhtio='$kukarow[yhtio]' and toim_maa != ''
					ORDER BY toim_maa IN ('$yhtiorow[maa]') desc, toim_maa";
		$mabures = mysql_query($query) or pupe_error($query);

		while($maarow = mysql_fetch_array($mabures)) {
			echo "<optgroup label='".maa($maarow["toim_maa"])."'>";

			$query = "	SELECT DISTINCT toim_postitp
						FROM asiakas
						WHERE yhtio='$kukarow[yhtio]' and maa = '$maarow[toim_maa]' and toim_postitp != ''
						ORDER BY postitp";
			$abures = mysql_query($query) or pupe_error($query);

			while($row = mysql_fetch_array($abures)) {
				$sel = "";
				if (array_search($row[0], $toim_postitp) !== false) {
					$sel = 'selected';
				}
				echo "		<option value='$row[0]' $sel>$row[0]</option>";
			}
			echo "</optgroup>";
		}

		echo "			</select>
						<br>
						".t("Ts. Postipaikoittain").": <input type='checkbox' name='group[toim_postitp]' value='checked' {$group["toim_postitp"]}> prio: <input type='text' name='prio[toim_postitp]' value='{$prio["toim_postitp"]}' size='2'>
					</td>";	
		echo "	</tr>
				</table>
				<br>";
				
		$query = "	SELECT asiakkaan_avainsanat.laji, avainsana.selitetark, group_concat(distinct(asiakkaan_avainsanat.avainsana) order by asiakkaan_avainsanat.avainsana SEPARATOR '|' ) avainsana
					FROM asiakkaan_avainsanat
					JOIN avainsana ON avainsana.yhtio=asiakkaan_avainsanat.yhtio and avainsana.selite=asiakkaan_avainsanat.laji
					WHERE asiakkaan_avainsanat.yhtio = '{$kukarow["yhtio"]}'
					GROUP BY asiakkaan_avainsanat.laji
					ORDER BY asiakkaan_avainsanat.laji DESC, asiakkaan_avainsanat.avainsana";
		$result = mysql_query($query) or pupe_error($query);
		if(mysql_num_rows($result)>0) {
			$shetti  = "";
			echo "
				<table width='750px'>
				<tr>";
				while($row = mysql_fetch_array($result)) {
					echo "<th>".t($row["selitetark"])."</th>";
					
					if(strpos($row["avainsana"], "|") === false) {
						$sanat = array($row["avainsana"]);
					}
					else {
						$sanat = explode("|", $row["avainsana"]);
					}
					
					$shetti .= "<td NOWRAP>
								<select name='{$row["laji"]}[]' multiple='TRUE' size='8' style='width: 100%;'>";
					foreach($sanat as $avainsana) {
						$sel = "";
						if(array_search($avainsana, ${$row["laji"]}) !== false) {
							$sel = "selected";
						}
						$shetti .= "<option value='$avainsana' $sel>".$avainsana."</option>";
					}
					$shetti .= "</select>
								<br>
								".t("Ryhmit‰‰ avainsanoittain").": <input type='checkbox' name='group[{$row["laji"]}.avainsana]' value='checked' ".$group[$row["laji"].".avainsana"]."> prio: <input type='text' name='prio[{$row["laji"]}.avainsana]' value='".$prio[$row["laji"]."avainsana"]."' size='2'>					
								</td>";
					
				}
			echo "</tr>";
			
			echo "
				<tr>
					$shetti
				</tr>
				</table>
				<br>";	
		}
		
		echo "	
				<table width='750px'>
				<tr>
					<th>".t("Tapahtuma")."</th><th>".t("Ei tapahtumaa")."</th>
				</tr>
				<tr>";

			
		$tapahtumat = array("Memo" => "Asiakasmuistio", "kalenteri" => "Kalenteritapahtuma", "Muistutus" => "Muistutus", "kontakti_1" => "1 Tarjouskontakti", "kontakti_2" => "2 Tarjouskontakti", "kontakti_3" => "Viimeinen kontakti");

		//	Tapahtuma
		echo "		<td NOWRAP>
						<select name='tapahtuma[]' multiple='TRUE' size='4' style='width: 100%;'>";
		foreach($tapahtumat as $t => $n) {
			$sel = "";
			if (array_search($t, $tapahtuma) !== false) {
				$sel = 'selected';
			}
			echo "<option value='$t' $sel>".t($n)."</option>";
			
		}
		echo "
						</select>
						<br>
						".t("Tapahtumittain").": <input type='checkbox' name='group[tapahtuma.tyyppi]' value='checked' {$group["tapahtuma.tyyppi"]}> prio: <input type='text' name='prio[tapahtuma.tyyppi]' value='{$prio["tapahtuma.tyyppi"]}' size='2'>
					</td>";

		//	Ei tapahtumaa
		echo "		<td NOWRAP>
						<select name='tapahtuma_puuttuu[]' multiple='TRUE' size='4' style='width: 100%;'>";
		foreach($tapahtumat as $t => $n) {
			$sel = "";
			if (array_search($t, $tapahtuma_puuttuu) !== false) {
				$sel = 'selected';
			}
			echo "<option value='$t' $sel>".t($n)."</option>";

		}
		echo "
						</select>
						<br>
						".t("Ei Tapahtumittain").": <input type='checkbox' name='group[tapahtuma_puuttuu.tyyppi]' value='checked' {$group["tapahtuma_puuttuu.tyyppi"]}> prio: <input type='text' name='prio[tapahtuma_puuttuu.tyyppi]' value='{$prio["tapahtuma_puuttuu.tyyppi"]}' size='2'>
					</td>";

		echo "	</tr>
				</table>
				<br>";
								
		echo "
				<br>
				<table>
					<tr>
						<td class='back'>
							<table>
							<caption style='text-align: center;'>Rajaa aika</caption>
							<tr>
								<th>".t("Alkup‰iv‰")."</th><th>".t("Loppup‰iv‰")."</th<td></td>
							</tr>
							<tr>
								<td>
									<input type='text' name='ppa' value='$ppa' size='3'>
									<input type='text' name='kka' value='$kka' size='3'>
									<input type='text' name='vva' value='$vva' size='5'>
								</td>
								<td>
									<input type='text' name='ppl' value='$ppl' size='3'>
									<input type='text' name='kkl' value='$kkl' size='3'>
									<input type='text' name='vvl' value='$vvl' size='5'>
								</td>
							</tr>

							<tr>
								<th colspan = '2'>".t("Ajalta")."</th>
							</tr>";

						$sel =  array();
						$sel[$aika_maare] = "SELECTED";

						$sel2 =  array();
						$sel2[$aikarajaus] = "SELECTED";
												
						echo "
							<tr>
								<td colspan = '2'>
									<input type='text' name='aika_arvo' value='$aika_arvo' size = '4'>
									<select name='aika_maare' style='width: 80%; align: right;'>
									<option value=''>".t("Valitse aikam‰‰re")."</option>
									<option value='DAY' $sel[DAY]>".t("P‰iv‰‰")."</option>
									<option value='WEEK' $sel[WEEK]>".t("Viikkoa")."</option>
									<option value='MONTH' $sel[MONTH]>".t("Kuukautta")."</option>
									</select>
								</td>
							</tr>
							<tr>
								<td colspan = '2'>
									<select name='aikarajaus' style='width: 100%'>
									<option value=''>".t("Valitse aikarajaus")."</option>
									<option value='AP' $sel2[AP]>".t("Asiakkaan luontiaika")."</option>
									<option value='TT' $sel2[TT]>".t("Tapahtuman luontiaika")."</option>
									<option value='TP' $sel2[TP]>".t("Tapahtuman puuttumisaika")."</option>
									</select>
								</td>
							</tr>
							</table>
						</td>";
		
		$sel_YH = "";
		if($tuo_yhteyshenkilot != "") {
			$sel_YH = "CHECKED";
		}
		echo "			<td class='back' valign = 'top' width='10'>&nbsp;</td>

						<td class='back'>
							<table>
							<caption style='text-align: center;'>Muut rajaukset</caption>

							<tr>
								<th>".t("Lukum‰‰r‰")."</th>
							</tr>
							<tr>
								<td>
									<input type='text' name='lkm' value='$lkm' size='10'>
								</td>
							</tr>
							</table>
						</td>

						<td class='back' valign = 'top' width='10'>&nbsp;</td>

						<td class='back'>
							<table>
							<caption>".t("Muut valinnat")."</caption>
								<th>".t("Tuo yhteyshenkilˆt")."</th>
								<td><input type='checkbox' name='tuo_yhteyshenkilot' $sel_YH></td>
							</table>
						</td>
					</tr>
					<tr>
						<td class='back' colspan = '6'><br>
							<table>
							<caption style='text-align: center;'>J‰rjestely</caption>
							<tr>
							<tr>
								<th>".t("1. Sarake")."</th><th>".t("2. Sarake")."</th><th>".t("3. Sarake")."</th>
							</tr>
							";
												
							for($i=0;$i<3;$i++) {
								
								$sel =  array();
								$sel[$jarj[$i]] = "SELECTED";
								
								echo "
									<td>
										<select name='jarj[$i]'>
											<option value=''>".t("Valitse sarake")."</option>
											<option value='seuranta' {$sel["piiri"]}>".t("Piiri")."</option>
											<option value='asiakasluokka' {$sel["asiakasluokka"]}>".t("Asiakasluokka")."</option>
											<option value='asiakasryhma' {$sel["asiakasryhma"]}>".t("Asiakasryhm‰")."</option>
											<option value='asiakasosasto' {$sel["asiakasosasto"]}>".t("Asiakasosasto")."</option>																																			
											<option value='maa' {$sel["maa"]}>".t("Maa")."</option>
											<option value='postitp' {$sel["postitp"]}>".t("As. Postipaikka")."</option>
											<option value='toim_postitp' {$sel["toim_postitp"]}>".t("Ts. Postipaikka")."</option>
											<option value='asiakkaan_avainsanat.laji' {$sel["asiakkaan_avainsanat.laji"]}>".t("Avainsanalaji")."</option>
											<option value='avainsana' {$sel["avainsana"]}>".t("Avainsana")."</option>
											<option value='kpl' {$sel["kpl"]}>".t("Kpl")."</option>
										</select>
										<select name='suunta[$i]'>
											<option value='DESC'  {$sel2["DESC"]}>".t("Laskeva")."</option>
											<option value='ASC' {$sel2["ASC"]}>".t("Nouseva")."</option>												
										</select>
									</td>";
							}
							
							
							echo "
							</tr>
							</table>							
						</td>
					</tr>
					<tr><td class='back' colspan = '6'><br>".nayta_kyselyt("asiakashakukone")."<br></td></tr>
					<tr><td class='back'><input type = 'submit' value='".t("Aja kysely")."'></td></tr>
				</table>
				<br>
				</form>";
	}

	if($hakupalkki == "mini") {
		echo "<br><a href=\"javascript:showhide('hakupalkki');\">".t("Piilota hakukriteerit")."</a>
		</div><br>";
	}

	$asiakas_rajaus = $avainsana_rajaus = $having_rajaus = $lkm_rajaus = "";
	$gruupattu = false;
	
	//	key = asiakas. value = avainsana.
	$asarakkeet = array("piiri" => "piiri", "luokka" => "asiakasluokka", "ryhma" => "asiakasryhma", "osasto" => "asiakasosasto");
	
	foreach(array("piiri", "asiakasluokka", "asiakasryhma", "asiakasosasto", "maa", "postitp", "toim_postitp", "tapahtuma", "tapahtuma_puuttuu") as $r) {
		if(count(${$r}) > 0) {
			$spe = "";
			if($r == "postitp" or $r == "toim_postitp") {
				if($r == "postitp") {
					$kentta = "postino";
				}
				else {
					$kentta = "toim_postino";
				}
				
				$pprajaus = "";
				
				foreach(${$r} as $k => $p) {
					if(substr($p,0,9) == "kaupunki:") {

						unset(${$r}[$k]);
						
						list($min, $max, $jokeri) = hae_postialue(substr($p, 9));
						
						if($pprajaus == "") {
							$pprajaus .= " (asiakas.maa='FI' and asiakas.$kentta > $min and asiakas.$kentta < $max $jokeri)";
						}
						else {
							$pprajaus .= " or (asiakas.maa='FI' and asiakas.$kentta > $min and asiakas.$kentta < $max $jokeri)";
						}
					}
					
					if($pprajaus != "") {
						$asiakas_rajaus = " and ($pprajaus)";
					}
					else {
						$asiakas_rajaus .= " and asiakas.$r IN ('".implode("','", ${$r})."')";
					}
				}
			}
			elseif($r=="tapahtuma") {
				$tapahtuma_rajaus .= " and tapahtuma.tyyppi IN ('".implode("','", ${$r})."')";
			}
			elseif($r=="tapahtuma_puuttuu") {
				$tapahtuma_puuttuu_rajaus .= " and tapahtuma_puuttuu.tyyppi IN ('".implode("','", ${$r})."')";
			}
			else {
				if(in_array($r, $asarakkeet)) {
					$asiakas_rajaus .= " and asiakas.".array_search($r, $asarakkeet)." IN ('".implode("','", ${$r})."')";
				}
				else {
					$asiakas_rajaus .= " and asiakas.$r IN ('".implode("','", ${$r})."')";
				}
			}
		}
		
		if(count($group[$r])>0) {
			
			if(in_array($r, $asarakkeet)) {
				$avainsana_rajaus .= " JOIN avainsana AS $r ON $r.yhtio=asiakas.yhtio and $r.laji='$r' and $r.selite=asiakas.".array_search($r, $asarakkeet);
			}
			$gruupattu = true;
		}
	}
	
	
	$ppa = sprintf("%02d", $ppa);
	$kka = sprintf("%02d", $kka);
	if($aika_maare != "") {
		if($aikarajaus == "AP") {
			$asiakas_rajaus .= " and asiakas.luontiaika >= DATE_SUB(now(), INTERVAL $aika_arvo $aika_maare)";
		}
		elseif($aikarajaus == "TT") {
			$tapahtuma_rajaus .= " and tapahtuma.luontiaika >= DATE_SUB(now(), INTERVAL $aika_arvo $aika_maare)";
		}
		elseif($aikarajaus == "TP") {
			$tapahtuma_puuttuu_rajaus .= " and tapahtuma_puuttuu.luontiaika >= DATE_SUB(now(), INTERVAL $aika_arvo $aika_maare)";
		}
	}
	else {
		if($aikarajaus == "AP") {
			if($ppa > 0 and $kka > 0 and $vva > 0) {
				$asiakas_rajaus .= " and asiakas.luontiaika >= '$vva-$kka-$ppa'";
			}			
			if($ppl > 0 and $kkl > 0 and $vvl > 0) {
				$asiakas_rajaus .= " and asiakas.luontiaika <= '$vva-$kka-$ppa'";
			}
		}
		elseif($aikarajaus == "TT") {
			if($ppa > 0 and $kka > 0 and $vva > 0) {
				$tapahtuma_rajaus .= " and tapahtuma.luontiaika >= '$vva-$kka-$ppa'";
			}			
			if($ppl > 0 and $kkl > 0 and $vvl > 0) {
				$tapahtuma_rajaus .= " and tapahtuma.luontiaika <= '$vva-$kka-$ppa'";
			}
		}		
		elseif($aikarajaus == "TP") {
			if($ppa > 0 and $kka > 0 and $vva > 0) {
				$tapahtuma_puuttuu_rajaus .= " and tapahtuma_puuttuu.luontiaika >= '$vva-$kka-$ppa'";
			}			
			if($ppl > 0 and $kkl > 0 and $vvl > 0) {
				$tapahtuma_puuttuu_rajaus .= " and tapahtuma_puuttuu.luontiaika <= '$vva-$kka-$ppa'";
			}
		}		
	}

	$query = "	SELECT distinct(laji) laji
				FROM asiakkaan_avainsanat
				WHERE yhtio = '{$kukarow["yhtio"]}'";
	$result = mysql_query($query) or pupe_error($query);
	if(mysql_num_rows($result) > 0) {
		while($row = mysql_fetch_array($result)) {
			if(count(${$row["laji"]}) > 0 or $group["$row[laji].avainsana"] == "checked") {
//				$avainsana_rajaus = " ON asiakkaan_avainsanat.yhtio=asiakas.yhtio and asiakkaan_avainsanat.liitostunnus=asiakas.tunnus $avainsana_rajaus";
				if(count(${$row["laji"]}) > 0) {
					$araja = " and {$row["laji"]}.avainsana IN ('".implode("','", ${$row["laji"]})."')";
				}
				else {
					$araja = "";
				}
				
				$avainsana_rajaus .= "JOIN asiakkaan_avainsanat AS {$row["laji"]} ON {$row["laji"]}.yhtio='{$kukarow["yhtio"]}' and {$row["laji"]}.liitostunnus = asiakas.tunnus and {$row["laji"]}.laji = '{$row["laji"]}' $araja";
			}
		}
	}

	if($tapahtuma_puuttuu_rajaus != "" or $group["tapahtuma_puuttuu.tyyppi"] == "checked") {
		$tapahtuma_puuttuu_rajaus = " LEFT JOIN kalenteri tapahtuma_puuttuu ON tapahtuma_puuttuu.yhtio=asiakas.yhtio and tapahtuma_puuttuu.liitostunnus=asiakas.tunnus $tapahtuma_puuttuu_rajaus";
		$qlisa = ", count(tapahtuma_puuttuu.tunnus) tapahtumia";
		$having_rajaus = " HAVING tapahtumia = 0";
	}

	if($tapahtuma_rajaus != "" or $group["tapahtuma.tyyppi"] == "checked") {
		$tapahtuma_rajaus = " JOIN kalenteri tapahtuma ON tapahtuma.yhtio=asiakas.yhtio and tapahtuma.liitostunnus=asiakas.tunnus $tapahtuma_rajaus";
	}

	if($asiakas_rajaus == "" and $avainsana_rajaus == "" and $tapahtuma_rajaus == "" and $tapahtuma_puuttuu_rajaus == "" and $gruupattu === false) {
		die("Tee edes jotain valintoja!");
	}
	
	if($lkm > 0) {
		$lkm_rajaus = "LIMIT $lkm";
	}

	if(count($group)>0) {
		//	sortataan prioriteettien mukaan..
		for($i=0;$i<count($prio);$i++) {
			$v = current($prio);
			if($v == 0) $prio[key($prio)]=max($prio)+1;
			next($prio);
		}

		$g = array();

		foreach($group as $gk => $gv) {
			if(in_array($gk, $asarakkeet)) {
				$g[$prio[$gk]] = array_search($gk, $asarakkeet);
			}
			else {
				$g[$prio[$gk]] = $gk;
			}
		}

		//	sortataan n‰‰ arrayt
		ksort($g);

		$group_by = "GROUP BY ".implode(", ", $g)." WITH ROLLUP";
	}
	else {
		$order = "ORDER BY ";
		if(is_array($jarj)) {
			foreach($jarj as $key => $jarj) {
				if($jarj != "" and $jarj != "kpl") {
					if(in_array($jarj, array("pva", "summa_"))) {
						$order .= "$jarj+0 ".$suunta[$key].", ";
					}
					else {
						$order .= "$jarj ".$suunta[$key].", ";
					}
				}
			}		
		}
		
		$order .= "asiakas.nimi ASC";
		$group_by = "GROUP BY asiakas.tunnus";
	}
	
	$c = $ce = 0;
	//	Grouppaus toimii aina samalla kaavalla
	if(count($group)>0) {
		$q = $qe = "";

		foreach($g as $k) {
			if(isset($asarakkeet[$k])) {
				$q .= $k." as ".$asarakkeet[$k].", ";
				$qe .= $asarakkeet[$k].".selitetark as ".str_replace(".avainsana", "", $asarakkeet[$k])."_nimi, ";
				$ce++;
			}
			else {
				$q .= "$k as ".str_replace(".avainsana", "", $k).", ";
			}
		}

		$q .= "count(*) kpl $qlisa,";
		$c = count($group)+1;
	}
	else {
		$q = "asiakas.ytunnus, concat_ws(' ',asiakas.nimi, asiakas.nimitark) asiakas, asiakas.postitp postipaikka, asiakas.postino postinumero $qlisa, ";
		$c = 4;		
	}

	//	T‰ss‰ kasataan kysely kasaan
	$query = "	SELECT 	$q $qe
						asiakas.tunnus
				FROM asiakas
				$avainsana_rajaus
				$tapahtuma_rajaus
				$tapahtuma_puuttuu_rajaus
				WHERE asiakas.yhtio = '$kukarow[yhtio]'
				$asiakas_rajaus
				$group_by
				$having_rajaus
				$order
				$lkm_rajaus";
	//echo $query."<br>";

	$asres = mysql_query($query) or pupe_error($query);
	if(mysql_num_rows($asres)>0) {
		if(!is_array($laajennus)) {
			echo "<font class='message'>".t("Hakutuloksia").": ".mysql_num_rows($asres)."</font>";
		}
		
		echo "<br>";
		
		echo "	<div id='main'>
				<table border='0' cellpadding='2' cellspacing='1' width = '800'>
					<tr>";

		if($tuo_yhteyshenkilot != "") {
			echo "<th>" . t("Valitse") . "</th>";
		}

		for($i=0;$i<($c+$ce); $i++) {
			if(strpos(mysql_field_name($asres, $i), "_nimi") === false) {
				$o = ucfirst(strtolower(trim(str_replace("_", " ", mysql_field_name($asres, $i)))));
				echo "<th>$o</th>";

				if(isset($workbook)) {
					$worksheet->write($excelrivi, $i, ucfirst(t($o)), $format_otsikko);
				}
			}
			else {
				$korvaava[str_replace("_nimi", "", mysql_field_name($asres, $i))] = $i;
			}
		}

		if(isset($workbook)) {
			$excelrivi++;
		}

		echo "
			</tr>";

		$gt = 0;
		$ed=array();
		if($tuo_yhteyshenkilot != "") {
			echo "	<form action='$PHP_SELF' method='post' name='vie_tiedostoon'>
						<input type='submit' value='".t("Vie valittujen s‰hkˆpostiosoitteet")."'>
						<input type='hidden' name='tee' value='tallenna_ja_laheta_mailiin'>
						<input type='hidden' name='kaunisnimi' value='Asiakkaiden_yhteyshenkilot_".date('d_m_Y_H_i').".xls'>";
		}
					
		$rivejatiedostoon = 0;
		while($asiakasrow = mysql_fetch_array($asres)) {

			echo "<tr  class='aktiivi'>";
			if($tuo_yhteyshenkilot != "") {
				echo "<td><input type='checkbox' name='lahetettavat_asiakastunnukset[$rivejatiedostoon]' value='$asiakasrow[tunnus]'></td>";
			}
			
			$rivejatiedostoon++;
			$summarivi = 0;
			$url_lisa = "";
			for($i=0;$i<$c; $i++) {
				if(mysql_field_name($asres, $i) !="kpl") {
					$url_lisa .= "&laajennus[".mysql_field_name($asres, $i)."][]=".$asiakasrow[$i];	
				}
				
				if($korvaava[mysql_field_name($asres, $i)]>0) { 
					$arvo = $asiakasrow[$korvaava[mysql_field_name($asres, $i)]];
				}
				else {
					$arvo = $asiakasrow[$i];
				}
				
				if(is_null($asiakasrow[$i]) and isset($ed[$i]) and $group_by != ""){
					if($i == 0) {
						echo "<td align='right' class='tumma'>".t("Kaikki yhteens‰").":</td>";

						if(isset($workbook)) {
							$worksheet->write($excelrivi, $i, t("Kaikki yhteens‰"), $format_loppusumma);
						}
					}
					else {
						echo "<td align='right' class='tumma'>$edarvo ".t("yhteens‰").":</td>";

						if(isset($workbook)) {
							$worksheet->write($excelrivi, $i, $edarvo." ".t("yhteens‰"), $format_valisumma);
						}						
					}

					$extraTR = 1;
					$summarivi = 1;
				}
				elseif($ed[$i] != $asiakasrow[$i].$asiakasrow[($i-1)] or mysql_field_name($asres, $i) == "Yhteens‰") {


					if($summarivi == 1) {
						$class = "tumma";
						$format = $format_valisumma;
					}
					else {
						$class = "";
						$format = $format_rivi;						
					}

					//	T‰m‰ gruuppi pit‰‰ nollata my√∂s seruaavilla sarakkeilla..
					for($z=$i;$z<$c; $z++) {
						$ed[$z] = "alibabadubadaba";
					}

					if(mysql_field_name($asres, $i) == "pva") {
						if($asiakasrow["pva"] > 5) {
							$color = "green"; 
						}
						elseif($asiakasrow["pva"] > 0) {
							$color = "orange"; 
						}
						else {
							$color = "red"; 				
						}

						echo "<td><font style='color:$color;' class='$class'>$asiakasrow[pva]</font></td>";

						if(isset($workbook)) {
							$worksheet->write($excelrivi, $i, $asiakasrow["pva"], $format);
						}
					}
					elseif(mysql_field_type($asres, $i) == "real") {
						echo "<td align = 'right' class='$class'>".number_format($asiakasrow[$i], 2, ',', ' ')."</td>";

						if(isset($workbook)) {
							$worksheet->write($excelrivi, $i, number_format($asiakasrow[$i], 2, ',', ' '), $format);
						}
					}
					elseif(mysql_field_name($asres, $i) == "maa") {

						echo "<td class='$class'>".maa($asiakasrow[$i])."</td>";

						if(isset($workbook)) {
							$worksheet->write($excelrivi, $i, maa($asiakasrow[$i]), $format);
						}
					}
					else {
						echo "<td class='$class'>$arvo</td>";

						if(isset($workbook)) {
							$worksheet->write($excelrivi, $i, $asiakasrow[$i], $format);
						}
					}
				}
				else {
					if($summarivi == 1) {
						$class = "tumma";
						if(isset($workbook)) {
							$worksheet->write($excelrivi, $i, "", $format_valisumma);
						}
					}
					else {
						$class = "";
					}

					echo "<td class='$class'>&nbsp;</td>";
				}

				if(mysql_field_name($asres, $i) == "Yhteens‰" and $summarivi == 0) {
					$colspan = $i;
					$gt += $asiakasrow[$i];
				}

				$ed[$i] = $asiakasrow[$i].$asiakasrow[($i-1)];
				$edarvo = $arvo;
				
			}

			$excelrivi++;
			if(count($group)==0) {
				echo "					
						<td class='back'><a id = 'kasittele_$asiakasrow[tunnus]' href=\"javascript: sndReq('asiakasmemo_$asiakasrow[tunnus]', 'asiakasmemo.php?ohje=off&liitostunnus=$asiakasrow[tunnus]&tee=NAYTA&kaleDIV=asiakasmemo_$asiakasrow[tunnus]', 'kasittele_$asiakasrow[tunnus]', true);\">K‰sittele</a></td>
						</tr>
						<tr>
							<td class='back' colspan = '8'>
								<div id='asiakasmemo_$asiakasrow[tunnus]'></div>
							</td>
						</tr>";
			}
			elseif($summarivi == 0) {
				$divID = "asiakkaat_".md5(uniqid());
				echo "					
						<td class='back'><a id = 'kasittele_$divID' href=\"javascript: sndReq('$divID', 'asiakashakukone.php?ohje=off&setti=laajenna$url_lisa', 'kasittele_$divID', true);\">Laajenna</a></td>
						</tr>
						<tr>
							<td class='back' colspan = '8'>
								<div id='$divID'></div>
							</td>
						</tr>";
			}
			
			if($extraTR == 1) {
				echo "<tr><td class='back' colspan = '8'>&nbsp;</td></tr>";

				if(isset($workbook)) {
					$excelrivi++;
				}

				$extraTR = 0;
			}
		}

		if($group_by == "") {
			echo "<tr><td colspan='$colspan' class='back'></td><td class='back' align = 'right'><font class='message'>".number_format($gt, 2, ',', ' ')."</font></td></tr>";
		}
		
		if($tuo_yhteyshenkilot != "") {
			echo "
				<tr><td class='back'><input type='checkbox' onclick=\"selectAllCheckboxesByName(this.checked, 'lahetettavat_asiakastunnukset');\"></td></tr>
				</table></div>
				<input type='submit' value='".t("Vie valittujen s‰hkˆpostiosoitteet")."'><br></form>
				<div id='tilanvieja' style='height: 800px;'>&nbsp;</div>";
		}
		else {
			echo "
				</table></div>
				<div id='tilanvieja' style='height: 800px;'>&nbsp;</div>";
		}

		if(isset($workbook) and $excelrivi>0) {
			// We need to explicitly close the workbook
			if(!isset($worksheetName)) {
				$workbook->close();
			}

			echo "<table>";
			echo "<tr><th>".t("Tallenna tulos").":</th>";
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='".ucfirst($excelnimi)."'>";
			echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
			echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
			echo "</table><br>";
		}

		//	Jos meill‰ on tarjous halutaan se varmaan aktivoida?
		if($tarjous > 0) {
			echo "
				<script type='text/javascript' language='JavaScript'>
					javascript: sndReq('asiakasmemo_$tarjous', 'asiakashakukone.php?ohje=off&toim=$toim&setti=$setti&tee=NAYTA&nakyma=TAPAHTUMALISTAUS&tarjous=$tarjous', 'kasittele_$tarjous', true);
				</script>";
		}

	}
}

require ("inc/footer.inc");

?>
