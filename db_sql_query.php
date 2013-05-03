<?php

	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	require("inc/parametrit.inc");

	if (isset($tee)) {
		if ($tee == "lataa_tiedosto") {
			readfile("/tmp/".$tmpfilenimi);
			exit;
		}
	}
	else {

		echo " <script language='javascript' type='text/javascript'>
				function toggleAll(toggleBox) {
					var currForm = toggleBox.form;
					var isChecked = toggleBox.checked;
					var nimi = toggleBox.name;
					for (var elementIdx=0; elementIdx<currForm.elements.length; elementIdx++) {
						if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].name.substring(0,3) == nimi) {
						    currForm.elements[elementIdx].checked = isChecked;
						}
					}
				}
				</script>

				<!-- Enabloidaan shiftill‰ checkboxien chekkaus //-->
				<script src='inc/checkboxrange.js'></script>

				<script language='javascript' type='text/javascript'>
					$(document).ready(function(){
						$(\".shift\").shiftcheckbox();
					});
				</script>";

		echo "<font class='head'>".t("SQL-raportti").":</font><hr>";

		if ($rtee == "AJA" and isset($ruks_pakolliset)) {
			require("inc/pakolliset_sarakkeet.inc");

			list($pakolliset, $kielletyt, $wherelliset, $eiyhtiota, $joinattavat, $saakopoistaa, $oletukset) = pakolliset_sarakkeet($table);

			if (!is_array($wherelliset)) {
				$ruksaa = $pakolliset;
			}
			else {
				$ruksaa = array_merge($pakolliset,$wherelliset);
			}

			// Oletusaliakset ja onko niiss‰ pakollisia
			$query = "	SELECT distinct selite
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji = 'MYSQLALIAS'
						and selite like '{$table}.%'
						and selitetark_2 = ''
						and selitetark_3 = 'PAKOLLINEN'";
			$al_res = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($al_res) > 0) {
				while ($pakollisuuden_tarkistus_rivi = mysql_fetch_assoc($al_res)) {
					$ruksaa[] = strtoupper(str_replace("$table.", "", $pakollisuuden_tarkistus_rivi["selite"]));
				}
			}
		}

		if ($kysely != $edkysely) {
			$rtee = "";
		}

		list($kysely_kuka, $kysely_mika) = explode("#", $kysely);

		// jos ollaan annettu uusirappari nimi, niin unohdetaan dropdowni!
		if ($uusirappari != "") $kysely = "";

		// n‰it‰ muuttujia ei tallenneta
		$ala_tallenna = array("kysely", "uusirappari", "edkysely", "rtee");

		// tallennetaan uusi kysely
		if ($rtee == "AJA" and $uusirappari != '' and $kysely == "") {
			tallenna_muisti($uusirappari, $ala_tallenna);
		}

		// tallennetaan aina myˆs kysely uudestaan jos sit‰ ajetaan (jos on oma rappari)
		if ($rtee == "AJA" and $kysely != '' and $kysely_kuka == $kukarow["kuka"]) {
			tallenna_muisti($kysely_mika, $ala_tallenna);
		}

		// jos kysely on valittuna mutta ei olla viel‰ ajamassa niin haetaan muuttujat
		if ($kysely != "" and $rtee != "AJA") {
			hae_muisti($kysely_mika, $kysely_kuka);
		}

		if ($rtee == "AJA" and is_array($kentat)) {

			//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
			$useslave = 1;

			require ("inc/connect.inc");

			$where   = "";
			$selecti = "";
			$joinit  = "";

			$order = "ORDER BY $table.yhtio";

			$oper_array = array('on' => '=', 'not' => '!=', 'in' => 'in','like' => 'like','gt' => '>','lt' => '<','gte' => '>=','lte' => '<=');

			foreach ($sarakkeet as $kentta) {

				$tuotteen_avainsanat 	= FALSE;
				$clean_kentta 			= $kentta;

				if ($table == "tuote" and substr($kentta, 0, 19) == "tuotteen_avainsanat") {
					$kentta 				= str_replace("tuotteen_avainsanat.", "", $kentta);
					list($kentta, $kieli) 	= explode(":", $kentta);
					$taulunimi 				= "tuotteen_avainsanat_{$kentta}_{$kieli}";
					$tuotteen_avainsanat 	= TRUE;
				}

				if (!$tuotteen_avainsanat and isset($kentat[$clean_kentta]) and $kentat[$clean_kentta] != "") {
					$selecti .= $clean_kentta.",\n";
				}
				elseif ($tuotteen_avainsanat and isset($kentat[$clean_kentta]) and $kentat[$clean_kentta] != ""){
					$selecti .= "$taulunimi.laji as 'tuotteen_avainsanat.laji',\n $taulunimi.selite as 'tuotteen_avainsanat.selite',\n $taulunimi.jarjestys as 'tuotteen_avainsanat.jarjestys',\n $taulunimi.kieli as 'tuotteen_avainsanat.kieli', \n";
					$joinit .= "LEFT JOIN tuotteen_avainsanat AS $taulunimi ON tuote.yhtio=$taulunimi.yhtio and tuote.tuoteno=$taulunimi.tuoteno and $taulunimi.laji='$kentta' and $taulunimi.kieli='$kieli'\n";
				}

				if (isset($operaattori[$clean_kentta]) and $operaattori[$clean_kentta] != "") {
					if ($operaattori[$clean_kentta] == "in") {
						$raj = "('".str_replace(",", "','", $rajaus[$clean_kentta])."')";
					}
					elseif ($operaattori[$clean_kentta] == "like") {
						$raj = "'".$rajaus[$clean_kentta]."%'";
					}
					else {
						$raj = "'".$rajaus[$clean_kentta]."'";
					}

					if ($tuotteen_avainsanat) {
						$where .= "and $taulunimi.selite ".$oper_array[$operaattori[$clean_kentta]]." ".$raj."\n";
					}
					else {
						$where .= "and $kentta ".$oper_array[$operaattori[$clean_kentta]]." ".$raj."\n";
					}
				}
			}

			asort($jarjestys);

			foreach ($jarjestys as $kentta => $jarj) {
				if ($jarj != "") {
					// katotaan monesko t‰‰ oli alkuper‰sess‰ listassa
					$looppi_apu = 1;
					foreach ($kentat as $kala1 => $kala2) {
						if ($kala1 == $kentta) {
							break;
						}
						$looppi_apu++;
					}
					$order .= ", $looppi_apu";
				}
			}

			$selecti = substr(trim($selecti), 0, -1);

			$sqlhaku = "SELECT $selecti
FROM $table
$joinit
WHERE $table.yhtio = '$kukarow[yhtio]'
$where
$order";
			$result = mysql_query($sqlhaku) or pupe_error($sqlhaku);

			echo "<font class='message'><pre>$sqlhaku</pre><br>".t("Haun tulos")." ".mysql_num_rows($result)." ".t("rivi‰").".</font><br><br>";

			if (mysql_num_rows($result) > 0) {
				if (include('inc/pupeExcel.inc')) {

					function tee_excel ($result) {
						global $excelrivi, $excelnimi;

						$worksheet 	 = new pupeExcel();
						$format_bold = array("bold" => TRUE);

						$excelrivi = 0;
						$talis = 0;

						for ($i=0; $i < mysql_num_fields($result); $i++) {
							$worksheet->write($excelrivi, $i+$talis, ucfirst(t(mysql_field_name($result,$i))), $format_bold);
						}
						$worksheet->write($excelrivi, $i+$talis, "TOIMINTO", $format_bold);
						$excelrivi++;

						return(array($worksheet, $excelrivi));
					}

					function sulje_excel ($worksheet, $filelask) {
						global $excelnimi, $table;

						// We need to explicitly close the worksheet
						$excelnimi = $worksheet->close();
						$loprivi = $filelask*65000;
						$alkrivi = ($loprivi-65000)+1;

						echo "<table>";
						echo "<tr><th>".t("Tallenna tulos")." (".t("Rivit")." $alkrivi-$loprivi):</th>";
						echo "<form method='post' class='multisubmit'>";
						echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
						echo "<input type='hidden' name='kaunisnimi' value='SQLhaku_".$table."_".$filelask.".xlsx'>";
						echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
						echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
						echo "</table><br>";
					}

					$lask = 0;
					$filelask = 1;

					list($worksheet, $excelrivi) = tee_excel($result);

					while ($row = mysql_fetch_array($result)) {
						$lask++;

						if ($lask % 65000 == 0) {
							sulje_excel($worksheet, $filelask);
							$filelask++;

							list($worksheet, $excelrivi) = tee_excel($result);
						}

						$talis = 0;

						for ($i=0; $i<mysql_num_fields($result); $i++) {
							if (mysql_field_type($result,$i) == 'real') {
								$worksheet->writeNumber($excelrivi, $i+$talis, $row[$i]);
							}
							else {
								$worksheet->writeString($excelrivi, $i+$talis, $row[$i]);
							}
						}

						$worksheet->writeString($excelrivi, $i+$talis, "MUUTA");
						$excelrivi++;
					}

					sulje_excel($worksheet, $filelask);
				}
			}
		}

		echo "<table cellpadding='5'><tr><td valign='top' class='back'>";

		$query  = "show tables from $dbkanta";
	    $result =  mysql_query($query);

		while ($row = mysql_fetch_array($result)) {
			echo "<a href='$PHP_SELF?table=$row[0]'>$row[0]</a><br>";
		}

		echo "</td><td class='back' valign='top'>";

		if ($table!='') {

			$fields = array();

			$query  = "SHOW columns from $table";
			$fieldres =  mysql_query($query);

			while ($row = mysql_fetch_array($fieldres)) {

				$row[0] = $table.".".$row[0];

				$fields[] = $row;
			}

			if ($table == "tuote") {

				$kielet = array("FI", "SE", "NO", "EN", "DE", "DK", "RU", "EE");

				$query = "	SELECT DISTINCT selite
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]'
							and avainsana.laji = 'PARAMETRI'
							ORDER BY selite";
				$al_res = mysql_query($query) or pupe_error($query);

				foreach ($kielet as $kieli) {

					while ($al_row = mysql_fetch_array($al_res)) {
						$fields[] = array("tuotteen_avainsanat.parametri_".$al_row["selite"].":".$kieli);
					}

					$fields[] = array("tuotteen_avainsanat.nimitys:$kieli");
					$fields[] = array("tuotteen_avainsanat.lyhytkuvaus:$kieli");
					$fields[] = array("tuotteen_avainsanat.kuvaus:$kieli");
					$fields[] = array("tuotteen_avainsanat.mainosteksti:$kieli");
					$fields[] = array("tuotteen_avainsanat.tarratyyppi:$kieli");
					$fields[] = array("tuotteen_avainsanat.sistoimittaja:$kieli");
					$fields[] = array("tuotteen_avainsanat.oletusvalinta:$kieli");
					$fields[] = array("tuotteen_avainsanat.osasto:$kieli");
					$fields[] = array("tuotteen_avainsanat.try:$kieli");

					mysql_data_seek($al_res, 0);
				}
			}

			echo "<form name='sql' class='sql' method='post' autocomplete='off'>";
			echo "<input type='hidden' name='table' value='$table'>";
			echo "<input type='hidden' name='rtee' value='AJA'>";
			echo "<input type='hidden' name='edkysely' value='$kysely'>";

			echo "<table>";
			echo "<tr><th colspan='2'>$table</th></tr>";

			echo "<tr><td>".t("Tallenna kysely").":</td><td><input type='text' size='20' name='uusirappari' value=''></td></tr>";
			echo "<tr><td>".t("Valitse kysely").":</td><td>";

			// tehd‰‰n "serializoitua" dataa ni etsit‰‰n t‰ll‰ vain t‰m‰n tablen tallennettuja kyselyit‰...
			$data = "\"table\";s:".strlen($table).":\"$table\"";

			//Haetaan tallennetut kyselyt
			$query = "	SELECT distinct kuka.nimi, kuka.kuka, tallennetut_parametrit.nimitys
						FROM tallennetut_parametrit
						JOIN kuka on (kuka.yhtio = tallennetut_parametrit.yhtio and kuka.kuka = tallennetut_parametrit.kuka)
						WHERE tallennetut_parametrit.yhtio = '$kukarow[yhtio]'
						and tallennetut_parametrit.sovellus = '$_SERVER[SCRIPT_NAME]'
						and tallennetut_parametrit.data like '%$data%'
						ORDER BY tallennetut_parametrit.nimitys";
			$sresult = mysql_query($query) or pupe_error($query);

			echo "<select name='kysely' onchange='submit()'>";
			echo "<option value=''>".t("Valitse")."</option>";

			while ($srow = mysql_fetch_array($sresult)) {

				$sel = '';
				if ($kysely == $srow["kuka"]."#".$srow["nimitys"]) {
					$sel = "selected";
				}

				echo "<option value='$srow[kuka]#$srow[nimitys]' $sel>$srow[nimitys] ($srow[nimi])</option>";
			}
			echo "</select>";

			echo "</td></tr>";
			echo "</table><br><br>";

			echo "<table>";
			echo "<tr><td>".t("Ruksaa sis‰‰nluvussa pakolliset kent‰t").":</td><td><input type='submit' name='ruks_pakolliset' value='".t("Ruksaa")."'></td></tr>";
			echo "</table><br><br>";

			echo "<table>";
			echo "<tr><th>".t("Kentt‰")."</th><th>".t("Valitse")."</th><th>".t("Operaattori")."</th><th>".t("Rajaus")."</th><th>".t("J‰rjestys")."</th></tr>";

			$kala = array();

			foreach ($fields as $row) {

				list($taulu, $sarake) = explode(".", $row[0]);

				//tehd‰‰n array, ett‰ saadaan sortattua nimen mukaan..
				if ($kentat[$row[0]] == $row[0]) {
					$chk = "CHECKED";
				}
				elseif (is_array($ruksaa) and count($ruksaa) > 0 and in_array(strtoupper($sarake), $ruksaa)) {
					$chk = "CHECKED";
				}
				else {
					$chk = "";
				}

				$sel = array();
				$sel[$operaattori[$row[0]]] = "SELECTED";

				array_push($kala,"<tr>
									<td>$row[0]</td>
									<td><input type='hidden' name='sarakkeet[$row[0]]' value='$row[0]'>
									<input type='checkbox' class='shift' name='kentat[$row[0]]' value='$row[0]' $chk></td>
									<td><select name='operaattori[$row[0]]'>
										<option value=''></option>
										<option value='on'	$sel[on]>=</option>
										<option value='not'	$sel[not]>!=</option>
										<option value='in'	$sel[in]>in</option>
										<option value='like'$sel[like]>like</option>
										<option value='gt'	$sel[gt]>&gt;</option>
										<option value='lt'	$sel[lt]>&lt;</option>
										<option value='gte'	$sel[gte]>&gt;=</option>
										<option value='lte'	$sel[lte]>&lt;=</option>
										</select></td>
									<td><input type='text' size='15' name='rajaus[$row[0]]' value='".$rajaus[$row[0]]."'></td>
									<td><input type='text' size='5'  name='jarjestys[$row[0]]' value='".$jarjestys[$row[0]]."'></td>
									</tr>");
			}

			foreach ($kala as $rivi) {
				echo "$rivi";
			}

			echo "<tr><td class='back'>".t("Ruksaa kaikki")."</td><td class='back'><input type='checkbox' name='ken' onclick='toggleAll(this);'></td></tr>";

			echo "</table>";
			echo "<br><input type='submit' value='".t("Suorita")."'>";
			echo "</form>";
		}

		echo "</td></tr></table>";

		require "inc/footer.inc";
	}
?>