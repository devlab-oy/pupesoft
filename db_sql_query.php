<?php

	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	require("inc/parametrit.inc");

	//Ja t‰ss‰ laitetaan ne takas
	$sqlhaku = $sqlapu;

	if (isset($tee)) {
		if ($tee == "lataa_tiedosto") {
			readfile("/tmp/".$tmpfilenimi);
			exit;
		}
	}
	else {

		echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
			<!--
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
			//-->
			</script>";

		echo "<font class='head'>".t("SQL-raportti").":</font><hr>";

		if ($rtee == "AJA" and isset($ruks_pakolliset)) {
			require("inc/pakolliset_sarakkeet.inc");
			
			list($pakolliset, $kielletyt, $wherelliset, , ) = pakolliset_sarakkeet($table);

			if (!is_array($wherelliset)) {
				$ruksaa = $pakolliset;
			}
			else {
				$ruksaa = array_merge($pakolliset,$wherelliset);
			}
		}

		// jos ollaan annettu uusirappari nimi, niin unohdetaan dropdowni!
		if ($uusirappari != "") $kysely = "";

		// T‰ss‰ luodaan uusi raporttiprofiili
		if ($rtee == "AJA" and $uusirappari != '' and $kysely == "") {

			$rappari = $table."##".$kukarow["kuka"]."##".$uusirappari;

			foreach($sarakkeet as $val) {
				if ($kentat[$val] != '' or $operaattori[$val] != '' or $rajaus[$val] != '') {

					$valsarake = $val;

					if ($kentat[$val] != '') {
						$valsarake .= "**";
					}

					$query = "	INSERT INTO avainsana
								set yhtio		= '$kukarow[yhtio]',
								laji			= 'SQLDBQUERY',
								selite			= '$rappari',
								selitetark		= '$valsarake',
								selitetark_2	= '$operaattori[$val]',
								selitetark_3	= '$rajaus[$val]',
								jarjestys		= '$jarjestys[$val]'";
					$res = mysql_query($query) or pupe_error($query);
				}
			}
		}

		if ($kysely != $edkysely) {
			$rtee = "";
		}

		if ($rtee == "AJA" and is_array($kentat)) {

			if ($kysely != '') {
				$query = "DELETE FROM avainsana WHERE yhtio='$kukarow[yhtio]' and laji='SQLDBQUERY' and selite='$kysely'";
				$res = mysql_query($query) or pupe_error($query);

				foreach($sarakkeet as $val) {
					if ($kentat[$val] != '' or $operaattori[$val] != '' or $rajaus[$val] != '') {

						$valsarake = $val;

						if ($kentat[$val] != '') {
							$valsarake .= "**";
						}

						$query = "	INSERT INTO avainsana
									set yhtio		= '$kukarow[yhtio]',
									laji			= 'SQLDBQUERY',
									selite			= '$kysely',
									selitetark		= '$valsarake',
									selitetark_2	= '$operaattori[$val]',
									selitetark_3	= '$rajaus[$val]',
									jarjestys		= '$jarjestys[$val]'";
						$res = mysql_query($query) or pupe_error($query);
					}
				}
			}

			$where = "";
			$order = "ORDER BY $table.yhtio";

			$oper_array = array('on' => '=', 'not' => '!=', 'in' => 'in','like' => 'like','gt' => '>','lt' => '<','gte' => '>=','lte' => '<=');

			foreach($operaattori as $kentta => $oper) {
				if ($oper != "") {
					if ($oper == "in") {
						$raj = "('".str_replace(",", "','", $rajaus[$kentta])."')";
					}
					elseif ($oper == "like") {
						$raj = "'".$rajaus[$kentta]."%'";
					}
					else {
						$raj = "'".$rajaus[$kentta]."'";
					}

					$where .= " and $kentta ".$oper_array[$oper]." ".$raj;
				}
			}


			asort($jarjestys);
			$jlask = 1;
			foreach($jarjestys as $kentta => $jarj) {
				if ($jarj != "") {
					$order .= ", $jlask";				
				}
				
				if (in_array($kentta, $kentat)) $jlask++;
			}
			
			$selecti  = "";
			$selecti2 = "";
			
			foreach ($kentat as $kentta) {				
				if (substr($kentta, 0, strlen($table)) == $table) {
					$selecti .= $kentta.",";
				}
				elseif (substr($kentta, 0, 19) == "tuotteen_avainsanat"){
					
					$kentta = str_replace("tuotteen_avainsanat.", "", $kentta);
															
					$selecti .= "(SELECT concat_ws('##', laji, selite, kieli) FROM tuotteen_avainsanat WHERE tuote.yhtio=tuotteen_avainsanat.yhtio and tuote.tuoteno=tuotteen_avainsanat.tuoteno and tuotteen_avainsanat.laji='$kentta') 'tuotteen_avainsanat.$kentta',";					
				}				
			} 
			
			$selecti = substr($selecti, 0, -1);
			$selecti2 = substr($selecti2, 0, -1);

			$sqlhaku = "SELECT $selecti
						FROM $table
						WHERE $table.yhtio='$kukarow[yhtio]'
						$where
						$order";
			$result = mysql_query($sqlhaku) or pupe_error($sqlhaku);

			echo "<font class='message'>$sqlhaku<br>".t("Haun tulos")." ".mysql_num_rows($result)." ".t("rivi‰").".</font><br><br>";

			if (mysql_num_rows($result) > 0) {

				if (include('Spreadsheet/Excel/Writer.php')) {

					function tee_excel ($result) {
						global $excelrivi, $excelnimi;

						// keksit‰‰n failille joku varmasti uniikki nimi:
						list($usec, $sec) = explode(' ', microtime());
						mt_srand((float) $sec + ((float) $usec * 100000));
						$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

						$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
						$workbook->setVersion(8);
						$worksheet = $workbook->addWorksheet('Sheet 1');

						$format_bold = $workbook->addFormat();
						$format_bold->setBold();

						$excelrivi = 0;
						$talis = 0;
						
						for ($i=0; $i < mysql_num_fields($result); $i++) {
							
							if (strpos(mysql_field_name($result,$i), "tuotteen_avainsanat") !== FALSE) {
								
								$worksheet->writeString($excelrivi, $i+$talis, "tuotteen_avainsanat.laji", $format_bold);
								$talis++;
								$worksheet->writeString($excelrivi, $i+$talis, "tuotteen_avainsanat.selite", $format_bold);
								$talis++;
								$worksheet->writeString($excelrivi, $i+$talis, "tuotteen_avainsanat.kieli", $format_bold);
							}
							else {							
								$worksheet->write($excelrivi, $i+$talis, ucfirst(t(mysql_field_name($result,$i))), $format_bold);
							}							
						}
						$worksheet->write($excelrivi, $i+$talis, "TOIMINTO", $format_bold);
						$excelrivi++;

						return(array($workbook, $worksheet, $excelrivi));
					}

					function sulje_excel ($filelask) {
						global $workbook, $excelnimi, $table;

						// We need to explicitly close the workbook
						$workbook->close();

						$loprivi = $filelask*65000;
						$alkrivi = ($loprivi-65000)+1;

						echo "<table>";
						echo "<tr><th>".t("Tallenna tulos")." (".t("Rivit")." $alkrivi-$loprivi):</th>";
						echo "<form method='post' action='$PHP_SELF'>";
						echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
						echo "<input type='hidden' name='kaunisnimi' value='SQLhaku_".$table."_".$filelask.".xls'>";
						echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
						echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
						echo "</table><br>";
					}

					$lask = 0;
					$filelask = 1;

					list($workbook, $worksheet, $excelrivi) = tee_excel($result);

					while ($row = mysql_fetch_array($result)) {
						$lask++;

						if ($lask % 65000 == 0) {
							sulje_excel($filelask);
							$filelask++;

							list($workbook, $worksheet, $excelrivi) = tee_excel($result);
						}
						
						$talis = 0;

						for ($i=0; $i<mysql_num_fields($result); $i++) {																			
							if (strpos(mysql_field_name($result,$i), "tuotteen_avainsanat") !== FALSE) {
								list ($laji,$selite, $kieli) = explode("##", $row[$i]);
								
								$worksheet->writeString($excelrivi, $i+$talis, $laji);
								$talis++;
								$worksheet->writeString($excelrivi, $i+$talis, $selite);
								$talis++;
								$worksheet->writeString($excelrivi, $i+$talis, $kieli);
							} 							
							elseif (mysql_field_type($result,$i) == 'real') {
								$worksheet->writeNumber($excelrivi, $i+$talis, $row[$i]);
							}
							else {
								$worksheet->writeString($excelrivi, $i+$talis, $row[$i]);
							}
						}
						
						$worksheet->writeString($excelrivi, $i+$talis, "MUUTA");
						$excelrivi++;
					}

					sulje_excel($filelask);
				}
			}
		}

		echo "<table cellpadding='5'><tr><td valign='top' class='back'>";

		$query  = "show tables from $dbkanta";
	    $result =  mysql_query($query);

		while ($row=mysql_fetch_array($result)) {
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

				$al_res = t_avainsana("PARAMETRI", "", "ORDER BY selite");
				
				while ($al_row = mysql_fetch_array($al_res)) {
										
					$row = array();
					$row[0] = "tuotteen_avainsanat.parametri_".$al_row["selite"];
					
					$fields[] = $row;
				}
			}


			echo "<form name='sql' action='$PHP_SELF' method='post' autocomplete='off'>";
			echo "<input type='hidden' name='table' value='$table'>";
			echo "<input type='hidden' name='rtee' value='AJA'>";
			echo "<input type='hidden' name='edkysely' value='$kysely'>";

			echo "<table>";
			echo "<tr><th colspan='2'>$table</th></tr>";

			echo "<tr><td>".t("Tallenna kysely").":</td><td><input type='text' size='20' name='uusirappari' value=''></td></tr>";
			echo "<tr><td>".t("Valitse kysely").":</td><td>";

			//Haetaan tallennetut h‰lyrapit
			$query = "	SELECT distinct selite, concat('(',replace(selite, '##',') ')) nimi
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji 	= 'SQLDBQUERY'
						and selite	like '".$table."##%'
						ORDER BY selite";
			$sresult = mysql_query($query) or pupe_error($query);

			echo "<select name='kysely' onchange='submit()'>";
			echo "<option value=''>".t("Valitse")."</option>";

			while ($srow = mysql_fetch_array($sresult)) {

				$sel = '';
				if ($kysely == $srow["selite"]) {
					$sel = "selected";
				}

				echo "<option value='$srow[selite]' $sel>$srow[nimi]</option>";
			}
			echo "</select>";

			echo "</td></tr>";
			echo "</table><br><br>";

			echo "<table>";
			echo "<tr><td>".t("Ruksaa sis‰‰nluvussa pakolliset kent‰t").":</td><td><input type='submit' name='ruks_pakolliset' value='".t("Ruksaa")."'></td></tr>";
			echo "</table><br><br>";

			echo "<table>";
			echo "<tr><th>Kentt‰</th><th>Valitse</th><th>Operaattori</th><th>Rajaus</th><th>J‰rjestys</th></tr>";

			$kala = array();

			foreach ($fields as $row) {

				list($taulu, $sarake) = explode(".", $row[0]);

				if ($kysely != "") {
					$query = "	SELECT *
								FROM avainsana
								WHERE yhtio 	= '$kukarow[yhtio]'
								and laji		= 'SQLDBQUERY'
								and selite		= '$kysely'
								and selitetark 	IN ('$sarake','$sarake**')";
					$sresult = mysql_query($query) or pupe_error($query);
					$srow = mysql_fetch_array($sresult);

					if ($srow["selitetark"] != '' and substr($srow["selitetark"],-2) == "**") {
						$kentat[$sarake] = substr($srow["selitetark"], 0, -2);
					}

					if ($srow["selitetark_2"] != '') {
						$operaattori[$sarake] = $srow["selitetark_2"];
					}

					if ($srow["selitetark_3"] != '') {
						$rajaus[$sarake] = $srow["selitetark_3"];
					}

					if ((int) $srow["jarjestys"] > 0) {
						$jarjestys[$sarake] = $srow["jarjestys"];
					}
				}

				//tehd‰‰n array, ett‰ saadaan sortattua nimen mukaan..
				if ($kentat[$sarake] == $sarake) {
					$chk = "CHECKED";
				}
				elseif (is_array($ruksaa) and count($ruksaa) > 0 and in_array(strtoupper($sarake), $ruksaa)) {
					$chk = "CHECKED";
				}
				else {
					$chk = "";
				}

				$sel = array();
				$sel[$operaattori[$sarake]] = "SELECTED";

				array_push($kala,"<tr>
									<td>$row[0]</td>
									<td><input type='hidden' name='sarakkeet[$sarake]' value='$sarake'><input type='checkbox' name='kentat[$sarake]' value='$sarake' $chk></td>
									<td><select name='operaattori[$sarake]'>
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
									<td><input type='text' size='15' name='rajaus[$sarake]' value='".$rajaus[$sarake]."'></td>
									<td><input type='text' size='5'  name='jarjestys[$sarake]' value='".$jarjestys[$sarake]."'></td>
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
