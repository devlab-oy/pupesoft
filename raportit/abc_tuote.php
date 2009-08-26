<?php

	if (isset($_POST["exceltee"])) {
		if($_POST["exceltee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	require ("../inc/parametrit.inc");

	if (isset($exceltee) and $exceltee == "lataa_tiedosto") {
		readfile("/tmp/".$tmpfilenimi);
		exit;
	}
	else {
		// tämä skripti käyttää slave-tietokantapalvelinta
		$useslave = 1;

		if ($toim == "") {
			$toim = "myynti";
		}

		if ($toim == "kate") {
			$abcwhat = "kate";
			$abcchar = "TK";
		}
		elseif ($toim == "kpl") {
			$abcwhat = "kpl";
			$abcchar = "TP";
		}
		elseif ($toim == "kulutus") {
			$abcwhat = "kpl";
			$abcchar = "TV";
		}
		elseif ($toim == "rivia") {
			$abcwhat = "rivia";
			$abcchar = "TR";
		}
		else {
			$abcwhat = "summa";
			$abcchar = "TM";
		}

		if ($tee == '') {
			echo "<font class='head'>".t("ABC-Analysointia tuotteille")."<hr></font>";
			echo "<font class='message'>";

			if ($toim == "myynti") {
				echo "ABC-luokat myynnin mukaan.";
			}
			elseif ($toim == "kpl") {
				echo "ABC-luokat kappaleiden mukaan.";
			}
			elseif ($toim == "rivia") {
				echo "ABC-luokat myyntirivien mukaan.";
			}
			else {
				echo "ABC-luokat katteen mukaan.";
			}

			echo "<br><br>".t("Valitse toiminto").":<br><br>";
			echo "</font>";
			echo "<li><a class='menu' href='$PHP_SELF?tee=YHTEENVETO&toim=$toim'         >".t("ABC-luokkayhteenveto")."</a><br>";
			echo "<li><a class='menu' href='$PHP_SELF?tee=OSASTOTRY&toim=$toim'          >".t("Tuoteosaston tai tuoteryhmän luokat")."</a><br>";
			echo "<li><a class='menu' href='$PHP_SELF?tee=LUOKKA&toim=$toim'             >".t("Luokan tuotteet")."</a><br>";
			echo "<li><a class='menu' href='$PHP_SELF?tee=PITKALISTA&toim=$toim'         >".t("Kaikki luokat tekstinä")." / ".t("Tallenna Excel tiedosto")."</a><br>";
		}

		// jos kaikki tarvittavat tiedot löytyy mennään queryyn
		if ($tee == 'YHTEENVETO') {
			require ("abc_tuote_yhteenveto.php");
		}

		if ($tee == 'OSASTOTRY') {
			require ("abc_tuote_osastotry.php");
		}

		if ($tee == 'LUOKKA') {
			require ("abc_tuote_luokka.php");
		}

		if ($tee == 'PITKALISTA') {
			require ("abc_tuote_kaikki_taullask.php");
		}

		require ("../inc/footer.inc");
	}
?>
