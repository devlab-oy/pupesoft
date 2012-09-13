<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta JA master kantaa *//
	$useslave = 1;
	$usemastertoo = 1;

	//Tehdään tällanen replace jotta parametric.inc ei poista merkkejä
	$sqlapu = $_POST["sqlhaku"];

	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	ini_set('zlib.output_compression', 0);

	require("../inc/parametrit.inc");

	ini_set("memory_limit", "2G");

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

		echo "<form name='sql' method='post' autocomplete='off'>";
		echo "<table>";
		echo "<tr><th>".t("Syötä SQL kysely")."</th></tr>";
		echo "<tr><td><textarea cols='100' rows='15' rows='15' name='sqlhaku' style='font-family:\"Courier New\",Courier'>$sqlhaku</textarea></td></tr>";
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

			require('inc/ProgressBar.class.php');

			include('inc/pupeExcel.inc');

			$worksheet 	 = new pupeExcel();
			$format_bold = array("bold" => TRUE);
			$excelrivi 	 = 0;
			$sarakemaara = mysql_num_fields($result);

			for ($i=0; $i < $sarakemaara; $i++) $worksheet->write($excelrivi, $i, ucfirst(t(mysql_field_name($result,$i))), $format_bold);
			$excelrivi++;

			$bar = new ProgressBar();
			$bar->initialize(mysql_num_rows($result));
			
			while ($row = mysql_fetch_row($result)) {

				$bar->increase();

				for ($i=0; $i < $sarakemaara; $i++) {
					if (mysql_field_type($result,$i) == 'real') {
						$worksheet->writeNumber($excelrivi, $i, sprintf("%.02f",$row[$i]));
					}
					else {
						$worksheet->writeString($excelrivi, $i, $row[$i]);
					}
				}
				$excelrivi++;
			}

			$excelnimi = $worksheet->close();

			echo "<br><br><table>";
			echo "<tr><th>".t("Tallenna tulos").":</th>";
			echo "<form method='post' class='multisubmit'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='SQLhaku.xlsx'>";
			echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
			echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
			echo "</table><br>";

			echo "<font class='message'>".t("Haun tulos")." ".mysql_num_rows($result)." ".t("riviä").".</font><br>";

			mysql_data_seek($result,0);

			echo "<pre>";

			for ($i = 0; $i < $sarakemaara; $i++) {
				echo mysql_field_name($result,$i)."\t";
			}
			echo "\n";

			while ($row = mysql_fetch_array($result)) {

				for ($i=0; $i<$sarakemaara; $i++) {

					// desimaaliluvuissa muutetaan pisteet pilkuiks...
					if (mysql_field_type($result, $i) == 'real') {
						echo str_replace(".",",", $row[$i])."\t";
					}
					else {
						echo str_replace(array("\n", "\r", "<br>"), " ", $row[$i])."\t";
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
