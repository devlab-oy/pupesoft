<?php

	//* T�m� skripti k�ytt�� slave-tietokantapalvelinta *//
	$useslave = 1;

	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Tuotepaikkalistaus")."</font><hr>";

	if ($ahyllyalue == '' and $ahyllynro == '' and $ahyllyvali == '' and $ahyllytaso == '' and isset($tee)) {
		unset($tee);
		echo "<font class='error'>Alkuhylly on m��ritelt�v�!</font><br>";
	}

	if ($lhyllyalue == '' and $lhyllynro == '' and $lhyllyvali == '' and $lhyllytaso == '' and isset($tee)) {
		unset($tee);
		echo "<font class='error'>Loppuhylly on m��ritelt�v�!</font><br>";
	}

	if ($tee == "TULOSTA") {

		$tulostimet[0] = "Tuotepaikkalistaus";
		if (count($komento) == 0) {
			require("inc/valitse_tulostin.inc");
		}

		$wherelisa = "";

		if ($ahyllyalue == '') {
			$ahyllyalue = 0;
		}
		if ($ahyllynro == '') {
			$ahyllynro = 0;
		}
		if ($ahyllyvali == '') {
			$ahyllyvali = 0;
		}
		if ($ahyllytaso == '') {
			$ahyllytaso = 0;
		}

		if ($ahyllyalue == $lhyllyalue and $ahyllynro == $lhyllynro and $ahyllyvali == $lhyllyvali and $ahyllytaso == $lhyllytaso) {
			$wherelisa = " 	AND hyllyalue='$ahyllyalue' AND hyllynro = '$ahyllynro' AND hyllyvali = '$ahyllyvali' AND hyllytaso = '$ahyllytaso'";
		}
		else {
			$wherelisa = " 	AND hyllyalue >= '$ahyllyalue' AND hyllynro >= '$ahyllynro' AND hyllyvali >= '$ahyllyvali' AND hyllytaso >= '$ahyllytaso'
							AND hyllyalue <= '$lhyllyalue' AND hyllynro <= '$lhyllynro' AND hyllyvali <= '$lhyllyvali' AND hyllytaso <= '$lhyllytaso'";
		}

		$jarj = "ORDER BY 1";

		if ($jarjestys == "hylly") {
			$jarj = "ORDER BY 3";
		}

		$tuotepaikka_query = "	SELECT tuote.tuoteno, tuote.nimitys, CONCAT_WS('-', hyllyalue, hyllynro, hyllyvali, hyllytaso) hyllyosoite
								FROM tuotepaikat
								JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio=tuotepaikat.yhtio AND tuote.tuoteno=tuotepaikat.tuoteno)
								WHERE tuotepaikat.yhtio='{$kukarow['yhtio']}'
								$wherelisa
								$jarj";
		$tuotepaikka_result = mysql_query($tuotepaikka_query) or pupe_error($tuotepaikka_query);

		if (mysql_num_rows($tuotepaikka_result) == 0) {
			echo "<font class='error'>".t("Ei l�ytynyt tuotteita")."</font><br><br>";
			$tee='';
		}

		if ($tee == "TULOSTA") {
			if (mysql_num_rows($tuotepaikka_result) > 0 ) {
				//kirjoitetaan  faili levylle..
				//keksit��n uudelle failille joku varmasti uniikki nimi:
				list($usec, $sec) = explode(' ', microtime());
				mt_srand((float) $sec + ((float) $usec * 100000));
				$filenimi = "/tmp/tpl-".md5(uniqid(mt_rand(), true)).".txt";
				$fh = fopen($filenimi, "w+");

				$pp = date('d');
				$kk = date('m');
				$vv = date('Y');
				$kello = date('H:i:s');

				//rivinleveys default
				$rivinleveys = 75;

				$ots  = "\n{$yhtiorow['nimi']}\t\t".t("Tuotepaikkalistaus").": $pp.$kk.$vv - $kello\n";
				$ots .= "-------------------------------------------------------------------------------------\n";
				$ots .= sprintf ('%-20.20s', 	t("Tuoteno"));
				$ots .= "\t";
				$ots .= sprintf ('%-30.30s', 	t("Nimitys"));
				$ots .= "\t";
				$ots .= sprintf ('%-21.21s', 	t("Hyllyosoite"));
				$ots .= "\n";
				$ots .= "-------------------------------------------------------------------------------------\n";
				fwrite($fh, $ots);
				$ots = chr(12).$ots;

				$rivit = 1;

				while($tuotepaikka_row = mysql_fetch_array($tuotepaikka_result)) {
					// Joskus halutaan vain tulostaa lista, mutta ei oikeasti invata tuotteita

					if ($rivit >= 50) {
						fwrite($fh, $ots);
						$rivit = 1;
					}

					$prn  = sprintf ('%-20.20s', 	$tuotepaikka_row["tuoteno"]);
					$prn .= "\t";
					$prn .= sprintf ('%-30.30s', 	$tuotepaikka_row["nimitys"]);
					$prn .= "\t";
					$prn .= sprintf ('%-21.21s', 	strtoupper($tuotepaikka_row["hyllyosoite"]));
					$prn .= "\n";
					fwrite($fh, $prn);
					$rivit++;
				}

				fclose($fh);

				if ($komento["Tuotepaikkalistaus"] != '') {
					exec("a2ps -o ".$filenimi.".ps --no-header --columns=1 -R --medium=A4 --chars-per-line=80 --margin=0 --borders=0 $filenimi");
					// itse print komento...
					$line = exec("$komento[Tuotepaikkalistaus] ".$filenimi.".ps", $output);
				}

				echo "<font class='message'>".t("Tuotepaikkalistaus tulostuu")."!</font><br><br>";

				//poistetaan tmp file samantien kuleksimasta...
				system("rm -f $filenimi");
				system("rm -f ".$filenimi.".ps");
				$tee = "";
			}
		}
	}

	if (!isset($tee) or $tee == "") {
		echo "<table>";
		echo "<form action='$PHP_SELF' method='post'>";
		echo "<input type='hidden' name='tee' value='TULOSTA'>";

		echo "<tr><th>".t("Alkuhylly")." (".t("alue-nro-v�li-taso")."):</th>";
		echo "<td align='right'><input type='text' name='ahyllyalue' value='$ahyllyalue' size='5'></td>";
		echo "<td><input type='text' name='ahyllynro' value='$ahyllynro' size='4'></td>";
		echo "<td><input type='text' name='ahyllyvali' value='$ahyllyvali' size='4'></td>";
		echo "<td><input type='text' name='ahyllytaso' value='$ahyllytaso' size='4'></td></tr>";

		echo "<tr><th>".t("Loppuhylly")." (".t("alue-nro-v�li-taso")."):</th>";
		echo "<td align='right'><input type='text' name='lhyllyalue' value='$lhyllyalue' size='5'></td>";
		echo "<td><input type='text' name='lhyllynro' value='$lhyllynro' size='4'></td>";
		echo "<td><input type='text' name='lhyllyvali' value='$lhyllyvali' size='4'></td>";
		echo "<td><input type='text' name='lhyllytaso' value='$lhyllytaso' size='4'></td></tr>";

		$sel = $jarjestys == "hylly" ? "hylly" : "tuoteno";

		echo "<tr><th>".t("Valitse listausj�rjestys")."</th><td align='right' colspan='4'><select name='jarjestys'>";
		echo "<option value='tuoteno' ";
			if ($sel == "tuoteno") { echo "selected"; }
		echo ">".t("Tuotenumero")."</option>";
		echo "<option value='hylly' ";
			if ($sel == "hylly") { echo "selected"; }
		echo ">".t("Paikka")."</option>";
		echo "</select></td></tr>";

		echo "<tr><td class='back'><input type='submit' value='".t("Tulosta")."'></td></tr>";
		echo "</form></table>";
	}

	require("../inc/footer.inc");

?>