<?php

	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;

	//Tehdään tällanen replace jotta parametric.inc ei poista merkkejä
	$sqlapu = $_POST["sqlhaku"];

	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	require("../inc/parametrit.inc");

	//Ja tässä laitetaan ne takas
	$sqlhaku = $sqlapu;

	if (isset($tee)) {
		if ($tee == "lataa_tiedosto") {
			readfile("/tmp/".$tmpfilenimi);	
			exit;
		}
	}
	else {
		echo "<font class='head'>".t("SQL-raportti").":</font><hr>";

		// käsitellään syötetty arvo nätiksi...
		$sqlhaku = stripslashes(strtolower(trim($sqlhaku)));

		// laitetaan aina kuudes merkki spaceks.. safetymeasure ni ei voi olla ku select
		if ($sqlhaku{6} != " ") {
			$sqlhaku = substr($sqlhaku,0,6)." ".substr($sqlhaku,6);
		}

		echo "<form name='sql' action='$PHP_SELF' method='post' autocomplete='off'>";
		echo "<table>";
		echo "<tr><th>".t("Syötä SQL kysely")."</th></tr>";
		echo "<tr><td><textarea cols='70' rows='10' name='sqlhaku'>$sqlhaku</textarea></td></tr>";
		echo "<tr><td class='back'><input type='submit' value='".t("Suorita")."'></td></tr>";
		echo "</table>";
		echo "</form>";

		// eka sana pitää olla select... safe enough kai.
		if (substr($sqlhaku, 0, strpos($sqlhaku, " ")) != 'select') {
			echo "<font class='error'>".t("Ainoastaan SELECT lauseet sallittu")."!</font><br>";
			$sqlhaku = "";
		}

		if ($sqlhaku != '') {

			$result = mysql_query($sqlhaku) or die ("<font class='error'>".mysql_error()."</font>");

			if(include('Spreadsheet/Excel/Writer.php')) {
				
				//keksitään failille joku varmasti uniikki nimi:
				list($usec, $sec) = explode(' ', microtime());
				mt_srand((float) $sec + ((float) $usec * 100000));
				$excelnimi = md5(uniqid(mt_rand(), true)).".xls";
				
				$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
				$worksheet =& $workbook->addWorksheet('Sheet 1');
			
				$format_bold =& $workbook->addFormat();
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
			
			
			echo "<font class='message'>".t("Haun tulos")." ".mysql_num_rows($result)." ".t("riviä").".</font><br>";

			mysql_data_seek($result,0);

			echo "<pre>";

			for ($i=0; $i<mysql_num_fields($result); $i++) {
				echo mysql_field_name($result,$i)."\t";
			}
			echo "\n";

			while ($row = mysql_fetch_array($result)) {

				for ($i=0; $i<mysql_num_fields($result); $i++) {

					// desimaaliluvuissa muutetaan pisteet pilkuiks...
					if (mysql_field_type($result, $i) == 'real') {
						echo str_replace(".",",", $row[$i])."\t";
					}
					else {
						echo "$row[$i]\t";
					}
				}
				echo "\n";
			}
			echo "</pre>";
		}

		// kursorinohjausta
		$formi  = "sql";
		$kentta = "sqlhaku";

		require("../inc/footer.inc");
	}

?>
