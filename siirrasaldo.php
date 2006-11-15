<?php
	require ("inc/parametrit.inc");

	echo "<font class='head'>".t("Siirr‰ saldoja")."</font><hr>";

	if ($tee == 'M') {

		$virhe = 0;
		$lask = 0;
		$siirrettavat = array();

		if ($kohdevarasto == $lahdevarasto) {
			echo "".t("Kohdevarasto ei voi olla sama kuin l‰hdevarasto")."!<br>";
			$virhe++;
		}
		else {
			if ($lahdevarasto != '') {
				$query = "	SELECT *
							FROM varastopaikat
							WHERE tunnus='$lahdevarasto' and yhtio = '$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);
				$lahderow = mysql_fetch_array($result);
			}
			else {
				echo "".t("Et valinnut l‰hdevarastoa")."!<br>";
				$virhe++;
			}
			if ($kohdevarasto != '') {
				$query = "	SELECT *
							FROM varastopaikat
							WHERE tunnus='$kohdevarasto' and yhtio = '$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);
				$kohderow = mysql_fetch_array($result);
			}
			else {
				echo "".t("Et valinnut kohdevarastoa")."!<br>";
				$virhe++;
			}
		}

		if ($hyllyalue != '') {

			if ($hyllynro  == '')
				$hyllynro = 0;
			if ($hyllyvali == '')
				$hyllyvali = 0;
			if ($hyllytaso == '')
				$hyllytaso = 0;

			$query = "	SELECT tunnus
						FROM varastopaikat
						WHERE alkuhyllyalue <= '$hyllyalue'
						and loppuhyllyalue >= '$hyllyalue'
						and yhtio = '$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0) {
				echo "".t("Turvapaikkaa")." $hyllyalue $hyllynro $hyllyvali $hyllytaso ".t("ei ole miss‰‰n varastossa")."!<br>";
				$virhe++;
			}
			else {
				$varastorow = mysql_fetch_array($result);

				if ($varastorow["tunnus"] != $kohdevarasto) {
					echo "".t("Turvapaikka ei sijaitse kohdevarastossa")."!<br>";
					$virhe++;
				}
			}
		}

		if (is_uploaded_file($_FILES['userfile']['tmp_name'])==TRUE) {

			list($name,$ext) = split("\.", $_FILES['userfile']['name']);

			if (strtoupper($ext) !="TXT" and strtoupper($ext)!="CSV") {
				die ("<font class='error'><br>".t("Ainoastaa .txt ja .csv tiedostot sallittuja")."!</font>");
			}

			if ($_FILES['userfile']['size']==0) {
				die ("<font class='error'><br>".t("Tiedosto oli tyhj‰")."!</font>");
			}

			$file=fopen($_FILES['userfile']['tmp_name'],"r") or die ("".t("Tiedoston avaus ep‰onnistui")."!");

			$rivi = fgets($file, 4096);
			while (!feof($file)) {

				// luetaan rivi tiedostosta..
				$poista	 = array("'", "\\");
				$rivi	 = str_replace($poista,"",$rivi);
				$rivi	 = explode("\t", trim($rivi));

				$tuoteno = $rivi[0];
				$maara	 = $rivi[1];

				if ($tuoteno == '') {
					echo "".t("Tuotenumero puuttui")."!<br>";
					$virhe++;
				}
				else {
					$query = "	SELECT *
								FROM tuote
								WHERE tuoteno = '$tuoteno' and yhtio = '$kukarow[yhtio]'";
					$result = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($result) == 1) {
						//Yritet‰‰n p‰‰tt‰‰ l‰hdepaikka
						$query = "	SELECT *
									FROM tuotepaikat
									WHERE tuoteno = '$tuoteno'
									and concat(rpad(upper('$lahderow[alkuhyllyalue]')  ,5,'0'),lpad(upper('$lahderow[alkuhyllynro]')  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
									and concat(rpad(upper('$lahderow[loppuhyllyalue]') ,5,'0'),lpad(upper('$lahderow[loppuhyllynro]') ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
									and yhtio = '$kukarow[yhtio]'
									ORDER BY oletus desc
									LIMIT 1";
						$result = mysql_query($query) or pupe_error($query);

						$lahde = 0;

						if (mysql_num_rows($result) == 0) {
							echo "".t("Tuotteella")." $tuoteno ".t("ei ollut yht‰‰n sopivaa varastopaikkaa l‰hdevarastossa")."!<br>";
							$virhe++;
						}
						else {
							$lahdepaikat = mysql_fetch_array($result);
							//echo "Sopiva l‰hdepaikka lˆytyi tuotteelle $tuoteno: $lahdepaikat[hyllyalue] $lahdepaikat[hyllynro] $lahdepaikat[hyllyvali] $lahdepaikat[hyllytaso] ($lahdepaikat[oletus])<br>";
							$lahde = 1;
						}

						if ($lahde == 1) {
							//Yritet‰‰n p‰‰tt‰‰ kohdepaikka
							$query = "	SELECT *
										FROM tuotepaikat
										WHERE tuoteno = '$tuoteno'
										and concat(rpad(upper('$kohderow[alkuhyllyalue]')  ,5,'0'),lpad(upper('$kohderow[alkuhyllynro]')  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
										and concat(rpad(upper('$kohderow[loppuhyllyalue]') ,5,'0'),lpad(upper('$kohderow[loppuhyllynro]') ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
										and yhtio 	   = '$kukarow[yhtio]'
										ORDER BY oletus desc
										LIMIT 1";
							$result = mysql_query($query) or pupe_error($query);

							if (mysql_num_rows($result) == 0) {
								if ($hyllyalue != '') {
									if ($maara > 0) {
										//echo "Tuote $tuoteno ($maara kpl.) siirret‰‰n syˆtt‰m‰llesi turvapaikalle $hyllyalue $hyllynro $hyllyvali $hyllytaso!<br>";
										$siirrettavat[] = "$tuoteno##$maara##$lahdepaikat[hyllyalue]##$lahdepaikat[hyllynro]##$lahdepaikat[hyllyvali]##$lahdepaikat[hyllytaso]##$hyllyalue##$hyllynro##$hyllyvali##$hyllytaso##TURVAPAIKKA";
									}
									else {
										echo "".t("Siirrett‰v‰ m‰‰r‰ tuotteella")." $tuoteno ".t("puuttui tai se oli nolla")."!<br>";
										$virhe++;
									}
								}
								else {
									echo "".t("Tuotteella")." $tuoteno ".t("ei ollut yht‰‰n sopivaa varastopaikkaa kohdevarastossa")."!<br>";
									$virhe++;
								}
							}
							else {
								$kohdepaikat = mysql_fetch_array($result);
								if ($maara > 0) {
									//echo "Tuote $tuoteno ($maara kpl.) siirret‰‰n kohdepaikalle: $kohdepaikat[hyllyalue] $kohdepaikat[hyllynro] $kohdepaikat[hyllyvali] $kohdepaikat[hyllytaso]<br>";
									$siirrettavat[] = "$tuoteno##$maara##$lahdepaikat[hyllyalue]##$lahdepaikat[hyllynro]##$lahdepaikat[hyllyvali]##$lahdepaikat[hyllytaso]##$kohdepaikat[hyllyalue]##$kohdepaikat[hyllynro]##$kohdepaikat[hyllyvali]##$kohdepaikat[hyllytaso]##KOHDEPAIKKA";
								}
								else {
									echo "".t("Siirrett‰v‰ m‰‰r‰ tuotteella")." $tuoteno ".t("puuttui tai se oli nolla")."!<br>";
									$virhe++;
								}
							}
						}
					}
					else {
						echo "".t("Tuotetta")." $tuoteno ".t("ei lˆytynyt kannasta, tai sit‰ lˆytyi enemm‰n kuin yksi")."!<br>";
						$virhe++;
					}
				}

				$rivi = fgets($file, 4096);
				$lask++;
			}

			fclose($file);

			if ($virhe == 0) {
				$query  = "LOCK TABLE tuotepaikat WRITE, tapahtuma WRITE, sanakirja WRITE, tuote READ, tilausrivi READ";
				$result = mysql_query($query) or pupe_error($query);

				echo "<pre>";

				foreach($siirrettavat as $siirrettava) {
					$siirrettava = explode("##",$siirrettava);

					$tuoteno	= $siirrettava[0];
					$maara		= $siirrettava[1];
					$lhyllyalue	= $siirrettava[2];
					$lhyllynro	= $siirrettava[3];
					$lhyllyvali	= $siirrettava[4];
					$lhyllytaso	= $siirrettava[5];
					$khyllyalue	= $siirrettava[6];
					$khyllynro	= $siirrettava[7];
					$khyllyvali	= $siirrettava[8];
					$khyllytaso	= $siirrettava[9];
					$toiminto	= $siirrettava[10];


					$query = "	UPDATE tuotepaikat set saldo = saldo - $maara, saldoaika=now()
								WHERE yhtio = '$kukarow[yhtio]' and tuoteno='$tuoteno' and
								hyllyalue = '$lhyllyalue' and hyllynro = '$lhyllynro' and hyllyvali = '$lhyllyvali' and hyllytaso = '$lhyllytaso'";
					$result = mysql_query($query) or pupe_error($query);


					if ($toiminto == "TURVAPAIKKA") {
						$query = "	INSERT into tuotepaikat set
									tuoteno		= '$tuoteno',
									saldo 		= '$maara',
									saldoaika	= now(),
									yhtio 		= '$kukarow[yhtio]',
									hyllyalue 	= '$khyllyalue',
									hyllynro 	= '$khyllynro',
									hyllyvali 	= '$khyllyvali',
									hyllytaso 	= '$khyllytaso'";
						$result = mysql_query($query) or pupe_error($query);
					}
					else {
						$query = "	UPDATE tuotepaikat set saldo = saldo + $maara, saldoaika=now()
									WHERE yhtio = '$kukarow[yhtio]' and tuoteno='$tuoteno' and
									hyllyalue = '$khyllyalue' and hyllynro = '$khyllynro' and hyllyvali = '$khyllyvali' and hyllytaso = '$khyllytaso'";
						$result = mysql_query($query) or pupe_error($query);
					}

					$query = "	INSERT into tapahtuma set
									yhtio ='$kukarow[yhtio]',
									tuoteno = '$tuoteno',
									kpl = $maara * -1,
									hinta = '0',
									laji = 'siirto',
									selite = '".t("Paikasta")." $lhyllyalue $lhyllynro $lhyllyvali $lhyllytaso ".t("v‰hennettiin")." $maara',
									laatija = '$kukarow[kuka]',
									laadittu = now()";
					$result = mysql_query($query) or pupe_error($query);

					$query = "	INSERT into tapahtuma set
									yhtio ='$kukarow[yhtio]',
									tuoteno = '$tuoteno',
									kpl = '$maara',
									hinta = '0',
									laji = 'siirto',
									selite = '".t("Paikalle")." $khyllyalue $khyllynro $khyllyvali $khyllytaso ".t("lis‰ttiin")." $maara',
									laatija = '$kukarow[kuka]',
									laadittu = now()";
					$result = mysql_query($query) or pupe_error($query);

					echo "".t("Tuote")." $tuoteno ".t("siirrettiin")." $maara ".t("kpl")." ".t("paikasta")." $lhyllyalue $lhyllynro $lhyllyvali $lhyllytaso --> ".t("paikkaan").": $khyllyalue $khyllynro $khyllyvali $khyllytaso<br>";
				}

				echo "</pre>";

				$query  = "UNLOCK TABLES";
				$result = mysql_query($query) or pupe_error($query);
			}
			else {
				echo "<br>".t("Materiaalissasi oli virheit‰! Korjaa ensin kaikki virheet, vasta sitten saldojen siirto onnistuu")."!<br>";
			}
			echo "<br>".t("Tiedostossa oli")." $lask ".t("rivi‰")."!<br>";
		}
	}

	if ($tee == '') {
		// T‰ll‰ ollaan, jos olemme syˆtt‰m‰ss‰ tiedostoa ja muuta
		echo "<form name = 'valinta' action = '$PHP_SELF' enctype='multipart/form-data' method='post'>
				<input type='hidden' name='tee' value='M'>
				<table>";

				echo "<tr><td class='back'></td><th>".t("Hyllyalue")."</th><th>".t("Hyllynro")."</th><th>".t("Hyllyv‰li")."</th><th>".t("Hyllytaso")."</th>";


				echo "<tr><td>".t("Tuotteet joilla ei ole paikkaa kohdevarastossa laitetaan t‰lle paikalle").":</td>";
				echo "<td><input type='text' size='6' name='hyllyalue' value='$hyllyalue'></td>";
				echo "<td><input type='text' size='5' name='hyllynro'  value='$hyllynro'></td>";
				echo "<td><input type='text' size='5' name='hyllyvali' value='$hyllyvali'></td>";
				echo "<td><input type='text' size='5' name='hyllytaso' value='$hyllytaso'></td></tr>";

				echo "<tr><td>".t("L‰hdevarasto").":</td>";
				echo "<td colspan='4'><select name='lahdevarasto'><option value=''>".t("Valitse")."</option>";

				$query  = "SELECT tunnus, nimitys FROM varastopaikat WHERE yhtio='$kukarow[yhtio]'";
				$vares = mysql_query($query) or pupe_error($query);

				while ($varow = mysql_fetch_array($vares))
				{
					$sel='';
					if ($varow['tunnus']==$lahdevarasto) $sel = 'selected';

					echo "<option value='$varow[tunnus]' $sel>$varow[nimitys]</option>";
				}

				echo "</select></td></tr>";

				echo "<tr><td>".t("Kohdevarasto").":</td>";
				echo "<td colspan='4'><select name='kohdevarasto'><option value=''>".t("Valitse")."</option>";

				$query  = "SELECT tunnus, nimitys FROM varastopaikat WHERE yhtio='$kukarow[yhtio]'";
				$vares = mysql_query($query) or pupe_error($query);

				while ($varow = mysql_fetch_array($vares))
				{
					$sel='';
					if ($varow['tunnus']==$kohdevarasto) $sel = 'selected';

					echo "<option value='$varow[tunnus]' $sel>$varow[nimitys]</option>";
				}

				echo "</select></td></tr>";

				echo "<tr><td>".t("Valitse tiedosto").":</td>
				<td colspan='4'><input name='userfile' type='file'></td></tr>";

				echo "</table><br>
				<input type = 'submit' value = '".t("L‰het‰")."'>
				</form>";
	}
	require "inc/footer.inc";
?>
