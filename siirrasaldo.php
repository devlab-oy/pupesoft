<?php
	require ("inc/parametrit.inc");

	echo "<font class='head'>".t("Siirr‰ saldoja")."</font><hr>";

	if ($tee == 'M') {

		$virhe = 0;
		$lask = 0;
		$siirrettavat = array();

		if ($kohdevarasto == $lahdevarasto) {
			echo "<font class='error'>".t("Kohdevarasto ei voi olla sama kuin l‰hdevarasto")."!</font><br>";
			$virhe++;
		}
		else {
			if ($lahdevarasto != '') {
				$query = "	SELECT *
							FROM varastopaikat
							WHERE tunnus='$lahdevarasto' and yhtio = '$kukarow[yhtio]'";
				$result = pupe_query($query);
				$lahderow = mysql_fetch_array($result);
			}
			else {
				echo "<font class='error'>".t("Et valinnut l‰hdevarastoa")."!</font><br>";
				$virhe++;
			}
			if ($kohdevarasto != '') {
				$query = "	SELECT *
							FROM varastopaikat
							WHERE tunnus='$kohdevarasto' and yhtio = '$kukarow[yhtio]'";
				$result = pupe_query($query);
				$kohderow = mysql_fetch_array($result);
			}
			else {
				echo "<font class='error'>".t("Et valinnut kohdevarastoa")."!</font><br>";
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
			$result = pupe_query($query);

			if (mysql_num_rows($result) == 0) {
				echo "<font class='error'>".t("Turvapaikkaa")." $hyllyalue $hyllynro $hyllyvali $hyllytaso ".t("ei ole miss‰‰n varastossa")."!</font><br>";
				$virhe++;
			}
			else {
				$varastorow = mysql_fetch_array($result);

				if ($varastorow["tunnus"] != $kohdevarasto) {
					echo "<font class='error'>".t("Turvapaikka ei sijaitse kohdevarastossa")."!</font><br>";
					$virhe++;
				}
			}
		}

		if (is_uploaded_file($_FILES['userfile']['tmp_name'])==TRUE) {

			$path_parts = pathinfo($_FILES['userfile']['name']);
			$name	= strtoupper($path_parts['filename']);
			$ext	= strtoupper($path_parts['extension']);

			if ($ext != "TXT" and $ext != "CSV") {
				die ("<font class='error'><br>".t("Ainoastaan .txt ja .cvs tiedostot sallittuja")."!</font>");
			}

			if ($_FILES['userfile']['size']==0) {
				die ("<font class='error'><br>".t("Tiedosto on tyhj‰")."!</font>");
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
					$result = pupe_query($query);

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
						$result = pupe_query($query);

						$lahde = 0;

						if (mysql_num_rows($result) == 0) {
							echo "<font class='error'>".t("Tuotteella")." $tuoteno ".t("ei ollut yht‰‰n sopivaa varastopaikkaa l‰hdevarastossa")."!</font><br>";
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
							$result = pupe_query($query);

							if (mysql_num_rows($result) == 0) {
								if ($hyllyalue != '') {
									if ($maara > 0) {
										//echo "Tuote $tuoteno ($maara kpl.) siirret‰‰n syˆtt‰m‰llesi turvapaikalle $hyllyalue $hyllynro $hyllyvali $hyllytaso!<br>";
										$siirrettavat[] = "$tuoteno##$maara##$lahdepaikat[hyllyalue]##$lahdepaikat[hyllynro]##$lahdepaikat[hyllyvali]##$lahdepaikat[hyllytaso]##$hyllyalue##$hyllynro##$hyllyvali##$hyllytaso##TURVAPAIKKA";
									}
									else {
										echo "<font class='error'>".t("Siirrett‰v‰ m‰‰r‰ tuotteella")." $tuoteno ".t("puuttui tai se oli nolla")."!</font><br>";
										$virhe++;
									}
								}
								else {
									echo "<font class='error'>".t("Tuotteella")." $tuoteno ".t("ei ollut yht‰‰n sopivaa varastopaikkaa kohdevarastossa")."!</font><br>";
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
									echo "<font class='error'>".t("Siirrett‰v‰ m‰‰r‰ tuotteella")." $tuoteno ".t("puuttui tai se oli nolla")."!</font><br>";
									$virhe++;
								}
							}
						}
					}
					else {
						echo "<font class='error'>".t("Tuotetta")." $tuoteno ".t("ei lˆytynyt kannasta, tai sit‰ lˆytyi enemm‰n kuin yksi")."!</font><br>";
						$virhe++;
					}
				}

				$rivi = fgets($file, 4096);
				$lask++;
			}

			fclose($file);

			if ($virhe == 0) {
				$query  = "LOCK TABLE tuotepaikat WRITE, tapahtuma WRITE, sanakirja WRITE, tuote READ, tilausrivi READ, avainsana READ, avainsana as avainsana_kieli READ";
				$result = pupe_query($query);

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
					$result = pupe_query($query);


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
						$result = pupe_query($query);

						$tapahtumaquery = "	INSERT into tapahtuma set
											yhtio 		= '$kukarow[yhtio]',
											tuoteno 	= '$tuoteno',
											kpl 		= 0,
											kplhinta	= 0,
											hinta 		= 0,
											laji 		= 'uusipaikka',
											hyllyalue 	= '$khyllyalue',
											hyllynro 	= '$khyllynro',
											hyllyvali 	= '$khyllyvali',
											hyllytaso 	= '$khyllytaso',
											selite 		= '".t("Siirr‰saldoissa lis‰ttiin tuotepaikka")." $khyllyalue $khyllynro $khyllyvali $khyllytaso',
											laatija 	= '$kukarow[kuka]',
											laadittu 	= now()";
						$tapahtumaresult = pupe_query($tapahtumaquery);

					}
					else {
						$query = "	UPDATE tuotepaikat set saldo = saldo + $maara, saldoaika=now()
									WHERE yhtio = '$kukarow[yhtio]' and tuoteno='$tuoteno' and
									hyllyalue = '$khyllyalue' and hyllynro = '$khyllynro' and hyllyvali = '$khyllyvali' and hyllytaso = '$khyllytaso'";
						$result = pupe_query($query);
					}

					$kehahin_query = "	SELECT yksikko,
										round(if (tuote.epakurantti100pvm = '0000-00-00',
												if (tuote.epakurantti75pvm = '0000-00-00',
													if (tuote.epakurantti50pvm = '0000-00-00',
														if (tuote.epakurantti25pvm = '0000-00-00',
															tuote.kehahin,
														tuote.kehahin * 0.75),
													tuote.kehahin * 0.5),
												tuote.kehahin * 0.25),
											0),
										6) kehahin
										FROM tuote
										WHERE yhtio = '$kukarow[yhtio]'
										and tuoteno = '$tuoteno'";
					$kehahin_result = pupe_query($kehahin_query);
					$kehahin_row = mysql_fetch_array($kehahin_result);

					$query = "	INSERT into tapahtuma set
									yhtio ='$kukarow[yhtio]',
									tuoteno = '$tuoteno',
									kpl = $maara * -1,
									hinta = '$kehahin_row[kehahin]',
									laji = 'siirto',
									hyllyalue =  '$lhyllyalue',
									hyllynro = '$lhyllynro',
									hyllyvali = '$lhyllyvali',
									hyllytaso = '$lhyllytaso',
									selite = '".t("Paikasta")." $lhyllyalue $lhyllynro $lhyllyvali $lhyllytaso ".t("v‰hennettiin")." $maara',
									laatija = '$kukarow[kuka]',
									laadittu = now()";
					$result = pupe_query($query);

					$query = "	INSERT into tapahtuma set
									yhtio ='$kukarow[yhtio]',
									tuoteno = '$tuoteno',
									kpl = '$maara',
									hinta = '$kehahin_row[kehahin]',
									laji = 'siirto',
									hyllyalue =  '$khyllyalue',
									hyllynro = '$khyllynro',
									hyllyvali = '$khyllyvali',
									hyllytaso = '$khyllytaso',
									selite = '".t("Paikalle")." $khyllyalue $khyllynro $khyllyvali $khyllytaso ".t("lis‰ttiin")." $maara',
									laatija = '$kukarow[kuka]',
									laadittu = now()";
					$result = pupe_query($query);

					echo t("Tuote")." $tuoteno ".t("siirrettiin")." $maara ".t_avainsana("Y", "", "and avainsana.selite='$kehahin_row[yksikko]'", "", "", "selite")." ".t("paikasta")." $lhyllyalue $lhyllynro $lhyllyvali $lhyllytaso --> ".t("paikkaan").": $khyllyalue $khyllynro $khyllyvali $khyllytaso<br>";
				}

				echo "</pre>";

				$query  = "UNLOCK TABLES";
				$result = pupe_query($query);
			}
			else {
				echo "<br><font class='error'>".t("Materiaalissasi oli virheit‰! Korjaa ensin kaikki virheet, vasta sitten saldojen siirto onnistuu")."!</font><br>";
				$tee = "";
			}
			echo "<br>".t("Tiedostossa oli")." $lask ".t("rivi‰")."!<br>";
		}
		else {
			echo "<font class='error'>".t("Luettavaa tiedostoa ei lˆytynyt")."!</font><br>";
			$tee = "";
		}
	}

	if ($tee == '') {
		// T‰ll‰ ollaan, jos olemme syˆtt‰m‰ss‰ tiedostoa ja muuta

		echo	"<font class='message'>".t("Tiedostomuoto").":</font><br>

				<table border='0' cellpadding='3' cellspacing='2'>
				<tr><th colspan='2'>".t("Tabulaattorilla eroteltu tekstitiedosto").".</th></tr>
				<tr><td>".t("Tuoteno")."</td><td>".t("M‰‰r‰")."</td></tr>
				</table>
				<br>";

		echo "<form name = 'valinta' enctype='multipart/form-data' method='post'>
				<input type='hidden' name='tee' value='M'>
				<table>";

				echo "<tr><td class='back'></td><th>".t("Hyllyalue")."</th><th>".t("Hyllynro")."</th><th>".t("Hyllyv‰li")."</th><th>".t("Hyllytaso")."</th>";


				echo "<tr><td>".t("Tuotteet joilla ei ole paikkaa kohdevarastossa laitetaan t‰lle paikalle").":</td>";
				echo "<td>",tee_hyllyalue_input("hyllyalue", $hyllyalue),"</td>";
				echo "<td><input type='text' size='5' name='hyllynro'  value='$hyllynro'></td>";
				echo "<td><input type='text' size='5' name='hyllyvali' value='$hyllyvali'></td>";
				echo "<td><input type='text' size='5' name='hyllytaso' value='$hyllytaso'></td></tr>";

				echo "<tr><td>".t("L‰hdevarasto").":</td>";
				echo "<td colspan='4'><select name='lahdevarasto'><option value=''>".t("Valitse")."</option>";

				$query  = "	SELECT tunnus, nimitys
							FROM varastopaikat
							WHERE yhtio='$kukarow[yhtio]'
							ORDER BY tyyppi, nimitys";
				$vares = pupe_query($query);

				while ($varow = mysql_fetch_array($vares))
				{
					$sel='';
					if ($varow['tunnus']==$lahdevarasto) $sel = 'selected';

					echo "<option value='$varow[tunnus]' $sel>$varow[nimitys]</option>";
				}

				echo "</select></td></tr>";

				echo "<tr><td>".t("Kohdevarasto").":</td>";
				echo "<td colspan='4'><select name='kohdevarasto'><option value=''>".t("Valitse")."</option>";

				$query  = "	SELECT tunnus, nimitys
							FROM varastopaikat
							WHERE yhtio='$kukarow[yhtio]' AND tyyppi != 'P'
							ORDER BY tyyppi, nimitys";
				$vares = pupe_query($query);

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
