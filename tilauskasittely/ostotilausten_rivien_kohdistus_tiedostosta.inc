<?php

if ($faarao == 'TUTKHM') {
	$vikaa	= 0;
	$tarkea	= 0;
	$lask	= 0;
	$rivino	= 0;

	if (is_uploaded_file($_FILES['userfile']['tmp_name'])==TRUE) {

		list($name,$ext) = split("\.", $_FILES['userfile']['name']);

		if (strtoupper($ext) !="TXT" and strtoupper($ext)!="CSV") {
			die ("<font class='error'><br>".t("Ainoastaa .txt ja .csv tiedostot sallittuja")."!</font>");
		}

		if ($_FILES['userfile']['size']==0) {
			die ("<font class='error'><br>".t("Tiedosto oli tyhjä")."!</font>");
		}

		$file = fopen($_FILES['userfile']['tmp_name'],"r") or die (t("Tiedoston avaus epäonnistui")."!");
		$rivi = fgets($file);

		while (!feof($file)) {

			$rivino++;
			$rivi = explode("\t", $rivi);
			// Eka layout on 0 tilausnro, 1 toim_tuoteno, 2 tuoteno, 3 kpl, 4 hinta, 5 kuluhinta
			$tilnro 		= trim($rivi[0]);
			$toim_tuoteno	= trim($rivi[1]);
			$tuoteno		= trim($rivi[2]);
			$kpl			= sprintf('%.2f', str_replace(',', '.', $rivi[3]));
			$hinta			= sprintf("%.".$yhtiorow['hintapyoristys']."f", str_replace(',', '.', $rivi[4]));
			$kuluhinta		= sprintf('%.2f', str_replace(',', '.', $rivi[5]));

			$tuote_etsi = "";

			// toimittajan tuotenumero ylikirjottaa tuotteen?
			if ($toim_tuoteno != "") {
				$query = "	SELECT GROUP_CONCAT(distinct concat(\"'\", tuoteno, \"'\") SEPARATOR ',') tuoteno
							FROM tuotteen_toimittajat use index (yhtio_toimtuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and toim_tuoteno = '$toim_tuoteno'
							and liitostunnus = '$toimittajaid'";
				$result = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result) > 0) {
					$rivi = mysql_fetch_array($result);
					$tuote_etsi = $rivi["tuoteno"];
				}
				else {
					$tuote_etsi = "";
				}
			}

			if ($tuoteno != "" and $tuote_etsi == "") {
				$tuote_etsi = "'$tuoteno'";
			}

			if ($tuote_etsi != "") {

				//Etsitään hyvää osumaa!
				$query = "	SELECT tilausrivi.*
							FROM tilausrivi, lasku, tuote
							WHERE lasku.ytunnus = '$laskurow[ytunnus]'
							and lasku.yhtio = '$kukarow[yhtio]'
							and tilausrivi.otunnus = lasku.tunnus
							and tilausrivi.yhtio = lasku.yhtio
							and tilausrivi.yhtio = tuote.yhtio
							and tilausrivi.tuoteno = tuote.tuoteno
							and lasku.tila = 'O'
							and lasku.alatila = 'A'
							and tilausrivi.tyyppi = 'O'
							and tilausrivi.uusiotunnus = 0
							and tilausrivi.tuoteno in ($tuote_etsi)
							ORDER BY tilausrivi.otunnus, tilausrivi.varattu";
				$result = mysql_query($query) or pupe_error($query);

				$ok=0;

				if (mysql_num_rows($result) > 0) {

					while ($rivirow = mysql_fetch_array ($result)) {

						$query = "	SELECT tuotekerroin
									FROM tuotteen_toimittajat use index (yhtio_toimtuoteno)
									WHERE yhtio = '$kukarow[yhtio]'
									and toim_tuoteno = '$toim_tuoteno'
									and liitostunnus = '$toimittajaid'
									and tuoteno = '$rivirow[tuoteno]'";
						$kerroinres = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($kerroinres) > 1) {
							echo "<font class='error'>Samalle toimittajan tuotteelle löytyi useita tuotteita! Käytetään ensimmäistä!</font><br>";
						}

						if (mysql_num_rows($kerroinres) > 0) {
							$kerroinrow = mysql_fetch_array($kerroinres);

							if ($kerroinrow["tuotekerroin"] == 0) $kerroinrow["tuotekerroin"] = 1;

							$apukpl = $kpl / $kerroinrow["tuotekerroin"]; // huomioidaan tuotekerroin
						}
						else {
							$apukpl = $kpl;
						}

						///* Testataan eka, löytyykö tilausnumero, tuotenumero, kpl mätchiä *///
						if ($rivirow['varattu'] == $apukpl and $rivirow['otunnus'] == $tilnro) {
							$ok = 1;
							break;
						}
					}

					if ($ok != 1) {

						mysql_data_seek($result,0);

						while ($rivirow = mysql_fetch_array ($result)) {

							$query = "	SELECT tuotekerroin
										FROM tuotteen_toimittajat use index (yhtio_toimtuoteno)
										WHERE yhtio = '$kukarow[yhtio]'
										and toim_tuoteno = '$toim_tuoteno'
										and liitostunnus = '$toimittajaid'
										and tuoteno = '$rivirow[tuoteno]'";
							$kerroinres = mysql_query($query) or pupe_error($query);

							if (mysql_num_rows($kerroinres) > 1) {
								echo "<font class='error'>Samalle toimittajan tuotteelle löytyi useita tuotteita! Käytetään ensimmäistä!</font><br>";
							}

							if (mysql_num_rows($kerroinres) > 0) {
								$kerroinrow = mysql_fetch_array($kerroinres);

								if ($kerroinrow["tuotekerroin"] == 0) $kerroinrow["tuotekerroin"] = 1;

								$apukpl = $kpl / $kerroinrow["tuotekerroin"]; // huomioidaan tuotekerroin
							}
							else {
								$apukpl = $kpl;
							}

							///* Testataan toka, löytyykö tuotenumero, kpl mätchiä *///
							if ($rivirow['varattu'] == $apukpl) {
								$ok = 1;
								break;
							}
						}
					}

					if ($ok != 1 and $hyvaksy != '') {

						mysql_data_seek($result,0);

						while ($rivirow = mysql_fetch_array ($result)) {

							$query = "	SELECT tuotekerroin
										FROM tuotteen_toimittajat use index (yhtio_toimtuoteno)
										WHERE yhtio = '$kukarow[yhtio]'
										and toim_tuoteno = '$toim_tuoteno'
										and liitostunnus = '$toimittajaid'
										and tuoteno = '$rivirow[tuoteno]'";
							$kerroinres = mysql_query($query) or pupe_error($query);

							if (mysql_num_rows($kerroinres) > 1) {
								echo "<font class='error'>Samalle toimittajan tuotteelle löytyi useita tuotteita! Käytetään ensimmäistä!</font><br>";
							}

							if (mysql_num_rows($kerroinres) > 0) {
								$kerroinrow = mysql_fetch_array($kerroinres);

								if ($kerroinrow["tuotekerroin"] == 0) $kerroinrow["tuotekerroin"] = 1;

								$apukpl = $kpl / $kerroinrow["tuotekerroin"]; // huomioidaan tuotekerroin
							}
							else {
								$apukpl = $kpl;
							}

							///* Testataan kolmanneks, löytyykö edes tuotenumero ja kappale mätchiä jossa kpl:ää on riittävästi *///
							if ($rivirow['varattu'] > $apukpl) {

								$filehinta = $hinta;

								// Poistetaan vanha rivi
								$query = "	DELETE from tilausrivi
											WHERE tunnus = '$rivirow[tunnus]'
											and yhtio = '$kukarow[yhtio]'
											and tyyppi = 'O'
											and otunnus = '$rivirow[otunnus]'";
								$poistoresult = mysql_query($query) or pupe_error($query);

								// Lisätään rivi ja splitataan se käyttäjän toiveita mukaillen
								$kpl 				= $apukpl;
								$tuoteno			= $rivirow["tuoteno"];
								$toimaika			= $rivirow["toimaika"];
								$kerayspvm			= $rivirow["kerayspvm"];

								if ($paivi != 'JOO') {
									$hinta			= $rivirow["hinta"];
								}
								elseif ($hinta == 0) {
									$hinta			= $rivirow["hinta"];
								}
								else {
									$hinta			= $hinta;
								}

								$netto				= $rivirow["netto"];
								$ale 				= $rivirow["ale"];
								$var 				= $hyvaksy."#".$rivirow['varattu'];
								$kukarow["kesken"]	= $rivirow["otunnus"];
								$laskurow["tila"] 	= "O";
								$alv				= $rivirow["alv"];
								$kommentti			= $rivirow["kommentti"];
								$rivitunnus			= $rivirow["tunnus"];

								$query = "	SELECT *
											FROM tuote
											WHERE yhtio = '$kukarow[yhtio]'
											and tuoteno = '$rivirow[tuoteno]'";
								$tuoteresult = mysql_query($query) or pupe_error($query);
								$trow = mysql_fetch_array($tuoteresult);

								require('lisaarivi.inc');

								$hinta = $filehinta;
								$ok = 1;
								break;
							}
						}
					}

					//Nyt tämä merkataan valituksi
					if ($ok == 1) {

						if ($kuluhinta != 0) {
							$kululisa = ", kate='$kuluhinta' ";
						}
						else {
							$kululisa = "";
						}

						if ($paivi == 'JOO' and $hinta != 0) {
							$hintalisa = ", hinta='$hinta' ";
						}
						else {
							$hintalisa = '';
						}

						$query = "UPDATE tilausrivi SET uusiotunnus='$otunnus' $kululisa $hintalisa WHERE tunnus = '$rivirow[tunnus]' AND yhtio='$kukarow[yhtio]'";
						$result = mysql_query($query) or pupe_error($query);

						echo "<font class='message'>".t("Rivi")." $rivino ".t("Tuotenumero").": $rivirow[tuoteno] ".t("Kpl").": $apukpl ".t("kohdistettu laskuun")."!</font><br>";

						if ($hinta != 0 and $hinta != $rivirow["hinta"] and $paivi != 'JOO') {
							echo "<font class='message'>".t("Rivi")." $rivino ".t("Tuotenumero").": $rivirow[tuoteno] ".t("Hinta on väärä")."!</font><br>";
						}

						if ($paivi == 'JOO') {
							if ($hinta != 0) {
								echo "<font class='message'>".t("Rivi")." $rivino ".t("Tuotenumero").": $paivirow[tuoteno] ".t("Päivitettiin tiedostossa ollut hinta").": $hinta ".t("riville")."!</font><br>";
							}
							else {
								echo "<font class='message'>".t("Rivi")." $rivino ".t("Tuotenumero").": $paivirow[tuoteno] ".t("EI päivitetty tiedostossa ollutta hintaa riville, koska se oli tyhjä tai nolla")."!</font><br>";
							}
						}
					}
				}
			}
			if ($ok==0) echo "<font class='error'>".t("Rivi")." $rivino ".t("Tuotenumero").": $tuote_etsi ".t("Kpl").": $apukpl ".t("EI voitu kohdistaa laskuun")."!<br></font>";

			//haetaan seuraava rivi
			$rivi = fgets($file);
		}
	}

	$tila = '';
}
else {
	echo "<form method='post' name='sendfile' enctype='multipart/form-data' action='$PHP_SELF'>";
	echo "<input type='hidden' name='otunnus' value='$otunnus'>";
	echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
	echo "<input type='hidden' name='tee' value='$tee'>";
	echo "<input type='hidden' name='toiminto' value='kohdista'>";
	echo "<input type='hidden' name='tila' value='FAILISTA'>";
	echo "<input type='hidden' name='faarao' value='TUTKHM'>";



	echo "<font class='message'>".t("Tiedostomuoto").":</font><br><br>

			<table>
			<tr><th colspan='6'>".t("Sarkaineroteltu tekstitiedosto").".</th></tr>
			<tr>
			<td>".t("Tilausnumero")."</td>
			<td>".t("Toim_tuoteno")."</td>
			<td>".t("Tuoteno")."</td>
			<td>".t("Kpl")."</td>
			<td>".t("Hinta")."</td>
			<td>".t("Kuluhinta")."</td>
			</tr>
			</table>

			<br>
			<table>";
	echo "<tr><th>".t("Valitse kohdistustapa").": </th><td><select name='hyvaksy'>";
	echo "<option value=''>".t("Tiedostossa oleva kpl on oltava sama kuin rivillä oleva kpl")."</option>";
	echo "<option value='LOJ'>".t("Kohdista tiedostossa oleva kpl ja merkkaa erotus JT:ksi")."</option>";
	echo "<option value='LOP'>".t("Kohdista tiedostossa oleva kpl ja poista ylijäävät kpl:ät")."</option>";
	echo "	</select></td></tr>";

	echo "<tr><th>".t("Päivitetäänkö hinnat?").": </th><td><select name='paivi'>";
	echo "<option value=''>".t("Tiedostossa oleva hinta EI PÄIVITETÄ riville")."</option>";
	echo "<option value='JOO'>".t("Tiedostossa oleva hinta PÄIVITETÄÄN riville")."</option>";
	echo "	</select></td></tr>";

	echo "<tr><th>".t("Valitse tiedosto").":</th>
				<td><input name='userfile' type='file'></td>
				<td class='back'><input type='submit' value='".t("Aja")."'></td>
			</tr>
			</table>

			</form>";
	//pysähdytään tähän
	exit;
}

?>