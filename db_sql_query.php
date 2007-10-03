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
		
		echo "<font class='head'>".t("SQL-raportti").":</font><hr>";
		
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
								selitetark_3	= '$rajaus[$val]'";								
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
									selitetark_3	= '$rajaus[$val]'";								
						$res = mysql_query($query) or pupe_error($query);
					}
				}
			}
		
			$where = "";
		
			$oper_array = array('on' => '=', 'not' => '!=', 'in' => 'in','like' => 'like','gt' => '>','lt' => '<','gte' => '=>','lte' => '<=');
		
			foreach($operaattori as $kentta => $oper) {
				if ($oper != "") {
					if ($oper == "in") {
						$raj = "('".str_replace(",", "','", $rajaus[$kentta])."')";
					}
					else {
						$raj = "'".$rajaus[$kentta]."'";
					}
					
					$where .= " and $kentta ".$oper_array[$oper]." ".$raj;				
				}
			}
				
			$sqlhaku = "SELECT ".implode(",", $kentat)." 
						FROM $table 
						WHERE yhtio='$kukarow[yhtio]' $where";
			$result = mysql_query($sqlhaku) or pupe_error($query);
		
			echo "<font class='message'>$sqlhaku<br>".t("Haun tulos")." ".mysql_num_rows($result)." ".t("rivi‰").".</font><br>";
		
			if (mysql_num_rows($result) > 0) {
			
				if(include('Spreadsheet/Excel/Writer.php')) {
			
					//keksit‰‰n failille joku varmasti uniikki nimi:
					list($usec, $sec) = explode(' ', microtime());
					mt_srand((float) $sec + ((float) $usec * 100000));
					$excelnimi = md5(uniqid(mt_rand(), true)).".xls";
			
					$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
					$worksheet = $workbook->addWorksheet('Sheet 1');
		
					$format_bold = $workbook->addFormat();
					$format_bold->setBold();
		
					$excelrivi = 0;
				}
        
				if(isset($workbook)) {
					for ($i=0; $i < mysql_num_fields($result); $i++) $worksheet->write($excelrivi, $i, ucfirst(t(mysql_field_name($result,$i))), $format_bold);
					$excelrivi++;
				}
        
				while ($row = mysql_fetch_array($result)) {
					for ($i=0; $i<mysql_num_fields($result); $i++) {
						if (mysql_field_type($result,$i) == 'real') {
							if(isset($workbook)) {
								$worksheet->writeNumber($excelrivi, $i, sprintf("%.02f",$row[$i]));
							}
						}
						else {						
							if(isset($workbook)) {
								$worksheet->writeString($excelrivi, $i, $row[$i]);
							}
						}
					}
					$excelrivi++;
				}
        
				if(isset($workbook)) {
			
					// We need to explicitly close the workbook
					$workbook->close();
			
					echo "<table>";
					echo "<tr><th>".t("Tallenna tulos").":</th>";
					echo "<form method='post' action='$PHP_SELF'>";
					echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
					echo "<input type='hidden' name='kaunisnimi' value='SQLhaku.xls'>";
					echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
					echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
					echo "</table><br>";
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
			$query  = "show columns from $table";
			$fields =  mysql_query($query);

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
			echo "<tr><th>Kentt‰</th><th>Valitse</th><th>Operaattori</th><th>Rajaus</th></tr>";

			$kala = array();

			while ($row = mysql_fetch_array($fields)) {
			
				if ($kysely != "") {
					$query = "	SELECT *
								FROM avainsana
								WHERE yhtio 	= '$kukarow[yhtio]'
								and laji		= 'SQLDBQUERY'
								and selite		= '$kysely'
								and selitetark 	IN ('$row[0]','$row[0]**')";
					$sresult = mysql_query($query) or pupe_error($query);
					$srow = mysql_fetch_array($sresult);

					if($srow["selitetark"] != '' and substr($srow["selitetark"],-2) == "**") {
						$kentat[$row[0]] = substr($srow["selitetark"], 0, -2);				
					}
			
					if ($srow["selitetark_2"] != '') {
						$operaattori[$row[0]] = $srow["selitetark_2"];
					}
			
					if ($srow["selitetark_3"] != '') {
						$rajaus[$row[0]] = $srow["selitetark_3"];
					}
				}
			
				//tehd‰‰n array, ett‰ saadaan sortattua nimen mukaan..
				if ($kentat[$row[0]] == $row[0]) {
					$chk = "CHECKED";
				}
				else {
					$chk = "";
				}
			
				$sel = array();
				$sel[$operaattori[$row[0]]] = "SELECTED";
			
				array_push($kala,"<tr>
									<td>$row[0]</td>
									<td><input type='hidden' name='sarakkeet[$row[0]]' value='$row[0]'><input type='checkbox' name='kentat[$row[0]]' value='$row[0]' $chk></td>
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
									<td><input type='text' name='rajaus[$row[0]]' value='".$rajaus[$row[0]]."'></td>
									</tr>");
			}

			sort($kala);
		
			foreach ($kala as $rivi) {
				echo "$rivi";
			}
		
			echo "</table>";
			echo "<input type='submit' value='".t("Suorita")."'>";
			echo "</form>";
		}

		echo "</td></tr></table>";

		require "inc/footer.inc";
	}
?>