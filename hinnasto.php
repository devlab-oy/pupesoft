<?php
	require ("inc/parametrit.inc");

	echo "<font class='head'>".t("Hinnastojen sisaanluku")."</font><hr>";

	if ($oikeurow[2] != '1') { // Saako p‰ivitt‰‰
		if ($uusi == 1) {
			echo "<b>".t("Sinulla ei ole oikeutta lis‰t‰ t‰t‰ tietoa")."</b><br>";
			$uusi = '';
		}
		if ($del == 1) {
			echo "<b>".t("Sinulla ei ole oikeutta poistaa t‰t‰ tietoa")."</b><br>";
			$del = '';
			$tunnus = 0;
		}
		if ($upd == 1) {
			echo "<b>".t("Sinulla ei ole oikeutta muuttaa t‰t‰ tietoa")."</b><br>";
			$upd = '';
			$uusi = 0;
			$tunnus = 0;
		}
	}
	if($tee == '') {
		echo "<br><br>";
		echo "	<table><form method='post' enctype='multipart/form-data' action='$PHP_SELF'>";
		echo "	<input type='hidden' name='tee' value='file'>";
		echo "<tr><td>&nbsp;".t("Valitse tiedosto").": </td><td> <input name='userfile' type='file' size='30'>&nbsp;</td>
				<td>&nbsp;&nbsp;<input type='submit' value='".t("L‰het‰")."'></td></tr></table>";
	}

	if($tee == 'file') {
		if (is_uploaded_file($userfile)==TRUE) {
			$filenimi=$userfile_name;
			$ext   = explode(".", $filenimi);
			$ext   = $ext[count($ext)-1];

			if (strtoupper($ext)!="") {
				if($userfile_size > 0) {
					$faili = fopen($userfile, 'r+');
					$rivinum = 0;
					echo "<table border='1' cellpadding='3' bordercolor='#ffffff'>";
					while($rivi = fgets($faili, 4096)) {
						echo "<tr>";
						$rivit[$rivinum] = explode("\t", $rivi);
						$maara  = count($rivit[$rivinum]);

						for($i = 0; $i < $maara; $i++) {
							echo "<td> ".$rivit[$rivinum][$i]." </td>";
						}
						echo "</tr>";
						$rivinum++;
					}
					fclose($faili);
					echo "</table><br><br><br>";

					$rivimaara = $rivinum;
					$tee = "lisaa";
				}
				else {
					echo "<font class='message'>".t("VIRHE! Tiedosto on tyhja").".</font><br>";
				}
			}
			else {
				echo "".t("Vaara paate")."!<br>";
			}
		}
		else {
			echo "".t("Mitaan ei uploadattu")."!<br>";
		}
	}
	if($tee == 'lisaa') {
		for($i = 0; $i < $rivimaara; $i++) {
			$maara = count($rivit[$i]);
			for($a = 0; $a < $maara; $a++) {
				if($rivit[$i][$a-1] == "Tuoteno:") {
					$tuoterivi = $i;
					$tuotesarake = $a;
					$tuoteno = $rivit[$i][$a];
				}
				if($i > $tuoterivi) {
					if($a == $tuotesarake-1) {
						$minkpl = $rivit[$i][$a];
					}
					if($a == $tuotesarake) {
						$maxkpl = $rivit[$i][$a];
					}
					if($a > $tuotesarake) {
						$hinta = $rivit[$i][$a];
						$vari = $rivit[$tuoterivi][$a];
						if(trim($hinta) != '') {
							if(trim($minkpl) != '' && trim($maxkpl) == '') {
								$maxkpl = 9999999999;
							}
							echo "".t("Tuoteno").": $tuoteno ";
							echo "".t("Vari").": $vari ";
							echo "".t("Min").": ".$minkpl." ";
							echo "".t("Max").": ".$maxkpl." ";
							echo "".t("Hinta").": ".$hinta."<br>";
							$vari  = trim($vari);
							$hinta = trim($hinta);

							$query = "	INSERT into hinnasto
										SET yhtio = '$kukarow[kuka]',
										tuoteno='$tuoteno',
										vari = '$vari',
										koko = 'one',
										minkpl = '$minkpl',
										maxkpl = '$maxkpl',
										hinta = '$hinta',
										laji='M'";
							$result = mysql_query($query) or pupe_error($query);

							$query = "SELECT distinct tuoteno, vari
									  FROM tuote
									  WHERE tuoteno='$tuoteno' and vari='$vari'";
							$result = mysql_query($query) or pupe_error($query);
							if(mysql_num_rows($result) == 0) {
								$query = "	INSERT into tuote
											SET yhtio = '$kukarow[kuka]',
											tuoteno='$tuoteno',
											vari = '$vari',
											koko='one',
											myyntihinta = '99999999.99'";
								$result = mysql_query($query) or pupe_error($query);
							}
						}
					}
				}
			}
		}
	}
?>