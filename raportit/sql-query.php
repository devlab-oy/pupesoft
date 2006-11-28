<?php

	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;

	//Tehdään tällanen replace jotta parametric.inc ei poista merkkejä
	$sqlapu = $_POST["sqlhaku"];

	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	require("inc/parametrit.inc");

	//Ja tässä laitetaan ne takas
	$sqlhaku = $sqlapu;

	if (isset($tee)) {
		if ($tee == "lataa_tiedosto") {
			echo $file;
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
		echo "</form><br>";

		// eka sana pitää olla select... safe enough kai.
		if (substr($sqlhaku, 0, strpos($sqlhaku, " ")) != 'select') {
			echo "<font class='error'>".t("Ainoastaan SELECT lauseet sallittu")."!</font><br>";
			$sqlhaku = "";
		}

		if ($sqlhaku != '') {

			$result = mysql_query($sqlhaku) or die ("<font class='error'>".mysql_error()."</font>");

			echo "<font class='message'>".t("Haun tulos")." ".mysql_num_rows($result)." ".t("riviä").".</font><br>";

			$file = "";

			for ($i=0; $i<mysql_num_fields($result); $i++) {
				$file .= mysql_field_name($result,$i)."\t";
			}
			$file .= "\r\n";

			while ($row = mysql_fetch_array($result)) {
				for ($i=0; $i<mysql_num_fields($result); $i++) {
					// desimaaliluvuissa muutetaan pisteet pilkuiks...
					if (mysql_field_type($result, $i) == 'real') {
						$file .= str_replace(".",",", $row[$i])."\t";
					}
					else {
						$file .= "$row[$i]\t";
					}
				}
				$file .= "\r\n";
			}

			echo "<table>";
			echo "<tr><th>".t("Tallenna tulos").":</th>";
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='sqlhaku.txt'>";
			echo "<input type='hidden' name='file' value='$file'>";
			echo "<td><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
			echo "</table><br>";

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
