<?php
	if (!function_exists("kirjoitaote")) {
		function kirjoitaote($tiliointi) { // Voisimme kirjoittaa otteen levylle
			global $kukarow, $otsikko, $sisalto, $ote;

			if (($ote != '') and ($tiliointi != 0)) {
				$query = "SELECT tosite
					  		FROM tiliointi
					  		WHERE yhtio='$kukarow[yhtio]' and tunnus='$tiliointi'";
				$xresult = mysql_query ($query) or die ("Kysely ei onnistu $query<br>".mysql_error());
				if (mysql_num_rows($xresult) != 1) {
					echo "Tiliöinti ei löydy<br>";
				}
				else {
					$tiliointirow=mysql_fetch_array($xresult);
					// Muutetaan tiliote --> tilioteote
					$uotsikko = str_replace (t("Tiliote"), t("Ote tiliotteesta"), $otsikko);
					if ($tiliointirow[0] == 0) {
						echo "Tositetta ei ole siirretty tikoniin ja siltä puuttuu tositenro $tiliointi<br>";
						//echo "$uotsikko $ote </table>";
						$tyofile="/tmp/too-" . $tiliointi . ".html";
						file_put_contents($tyofile, $uotsikko . $ote . "</table>");
					}
					else {
						//echo "$uotsikko $ote </table>";
						$tyofile="/tmp/too-" . $tiliointirow[0] . ".html";
						file_put_contents($tyofile, $uotsikko . $ote . "</table>");
					}
				}
				$ote = '';
			}
		}
	}

	require ("inc/functions.inc");
	require ("inc/parametrit.inc");
	
	echo "<font class='head'>Tiliotteiden siirto levylle</font><hr><br>";

	if ($tee == 'all') { // haetaan sopivat tiliotteet
		$lisa='';
		if ($kausi != '') $lisa = "and DATE_FORMAT(alku, '%Y-%m') = '$kausi'";
		
		$query = "SELECT distinct alku, tilino
					  FROM tiliotedata
					  WHERE yhtio='$kukarow[yhtio]' and tyyppi = '1' $lisa";
		$result = mysql_query ($query)
				or die ("Kysely ei onnistu $query<br>".mysql_error());
		echo "Siirrettäviä tiliotteita on ". mysql_num_rows($result) . " kpl<br>";
		//while ($tilioterow=mysql_fetch_array($result)) {
		//	echo "$tilioterow[alku] $tilioterow[tilino]<br>";
		//}

		$query = "SELECT * FROM tiliotedata
				WHERE yhtio='$kukarow[yhtio]' and tyyppi = '1' $lisa
				ORDER BY alku, tilino, tunnus";
		$result = mysql_query ($query)
				or die ("Kysely ei onnistu $query<br>".mysql_error());
		
		$aineisto='';
		
		while ($tilioterow=mysql_fetch_array($result)) {
			if (!isset($tilino)) echo "<font class='message'>Aloitan tiliotteen $tilioterow[tilino] alkaen $tilioterow[alku]</font><br>";
			
			if (($alku!=$tilioterow['alku'] or $tilino!=$tilioterow['tilino']) and isset($tilino)) {
				$sisalto = $otsikko . $sisalto;
				echo "<font class='message'>Tiliote $tilino alkaen $alku on nyt valmis</font><br>";
				if ($sisalto != '') {
					$sisalto .= "</table>";
					$tyofile="/tmp/to" . $tiliotenro ."-". $alkupvm ."-". $loppupvm ."-". $tilino . ".html";
					file_put_contents($tyofile, $sisalto);
					$sisalto='';
				}
				echo "<font class='message'>Aloitan tiliotteen $tilioterow[tilino] alkaen $tilioterow[alku]</font><br>";
			}
			
			$alku=$tilioterow['alku'];
			$tilino =$tilioterow['tilino'];
			$tietue = $tilioterow['tieto'];
			$tunnus = substr($tietue, 0,3);
			switch ($tunnus) {
				case 'T00' :
					$otsikko = "<table>";
					$pankkitilino = substr($tietue, 9, 14);
					$otsikko .= "<tr>";
					$otsikko .= "<td>" . substr($tietue,182,40) . "</td>";
					$tiliotenro = substr($tietue,23,3);
					$otsikko .= "<td>".t("Tiliote")." " . substr($tietue,23,3) . "</td><td></td>";
					$otsikko .= "</tr>";

					$otsikko .= "<tr>";
					$otsikko .= "<td rowspan='2'>". substr($tietue,222,40) . "<br>". substr($tietue,262,30) ."<br>". substr($tietue,292,30) ."</td>";
					$otsikko .= "<td></td>";
					$otsikko .= "<td>".t("Muodostettu")."<br>". substr($tietue,38,10) . "</td>";
					$otsikko .= "</tr>";

					$otsikko .= "<tr>";
					$otsikko .= "<td>".t("Tulkittu asiakkaalla")."</td>";
					$alkupvm = substr($tietue,26,6);
					$loppupvm = substr($tietue,32,6);
					$otsikko .= "<td>".t("Kausi")."<br> $alkupvm - $loppupvm</td>";
					$otsikko .= "</tr>";

					$otsikko .= "<tr>";
					$otsikko .= "<td>" . substr($tietue,147,18) . "</td>";
					$otsikko .= "<td>" . substr($tietue,99,30) . "<br>" . substr($tietue,96,3) . " $pankkitilino</td>";
					$otsikko .= "<td>".t("Myönnetty määrä")."<br>". substr($tietue, 117, 30) * 1 . "</td>";
					$otsikko .= "</tr>";

					$otsikko .= "</td></tr></table>"; // Tämä päättää otsikon

					$otsikko .= "<table>"; // Aloitetaan tapahtumat
					$otsikko .= "<tr>";
					$otsikko .= "<td>".t("Arkistointitunnus")."<br>".t("Saajan tilinumero")."</td>";
					$otsikko .= "<td>".t("Maksup.")."<br>".t("Arvop.")."</td>";
					$otsikko .= "<td>".t("Saaja/Maksaja")."<br>".t("Viesti")."</td>";
					$otsikko .= "<td>".t("Tap.")."<br>".t("n:o")."</td>";
					$otsikko .= "<td>".t("Tiliöinti")."</td>";
					$otsikko .= "<td>".t("Määrä")."</td>";
					$otsikko .= "</tr>";

					$otsikko .= "<tr><td></td><td></td><td></td><td></td>";
					$otsikko .= "<td>".t("Saldo")." " . substr($tietue,65,6) . "</td>";
					$otsikko .= "<td>" . substr($tietue,71,19) / 100 . "</td>";
					$otsikko .= "</tr>";
								
					break;

				case 'T10' :
					kirjoitaote($tiliointi);		
					$ote='';
					$tiliointi = $tilioterow['tiliointitunnus'];
					
					$koodi = substr($tietue, 49, 3);
					$kirjpvm = substr($tietue, 30, 6);
					$pvm = substr($tietue, 36, 6);
					//Tilioidaan kuitenkin loppupeleissa kaikki kirjauspvm:lle.
					
					$pvm=$kirjpvm;

					if ($pvm == "000000") { // Haa, pankki ei antanutkaan arvopvmää, joten otetaan käyttään tapahtumapvm
						$pvm = $kirjpvm;
					}
					
					//$sisalto .= "Tunnus: '" .  substr($tietue, 48, 1) . "'<br>";
					$etumerkki = substr($tietue, 87, 1);
					$maara = substr($tietue, 88, 18) / 100;
					if ($etumerkki == '-') {
						$maara *= -1;
					}
					$kuittikoodi = substr($tietue,106,1);
					$maksaa = trim(substr($tietue, 108, 35));
					$omatilino = substr($tietue, 144, 14);
					$omataso = substr($tietue, 187, 1);
					$koodiselite = trim(substr($tietue, 52, 35));
					$ote .= "<tr>";
					$ote .= "<td>" . substr($tietue,12,18) . "</td>";
					$ote .= "<td>$kirjpvm</td>";
					$ote .= "<td>$maksaa</td>";
					$ote .= "<td>" . substr($tietue, 6, 6) * 1 . "$kuittikoodi $omataso</td>";
					$ote .= "<td></td>";
					$ote .= "<td>$maara</td>";
					$ote .= "</tr><tr>";
					$ote .= "<td>$omatilino</td>";
					$ote .= "<td>$pvm</td>";
					$ote .= "<td>$koodi $koodiselite</td>";
					$ote .= "<td></td><td></td><td></td>";
					$ote .= "</tr>";
					$vientiselite = "$maksaa<br>$koodi $koodiselite<br>";
					$sisalto .= $ote;
					break;

				case 'T11' :
					$selite = '';
					$ote1 = '';
					$tyyppi = substr($tietue, 6, 2);
					$arvo =  substr($tietue,8,substr($tietue, 3, 3));
					$ote1 .= "<tr><td></td><td></td>";
					switch ($tyyppi) {
					case '00' :
							$selite = "";
							$luottokuntatieto = "";
							for ($i = 0; $i < 13; $i++) {
								$osa = trim(substr($arvo, $i*35, 35));
								if (strlen($osa) > 0) {
									$selite .= $osa . "<br>";
								}
								// Luottokunnan käsittelyä varten
								if ($i == 1) $luottokuntatieto = $osa;
							}
							break;
					case '01' :
							$selite = "".t("tapahtumia").": " . substr($arvo, 0, 8) * 1;
							//Tällä taklataan Aktian virhettä, jossa tuo E puuttuu aineistosta
							if ((substr($pankkitilino,0,1) == '4') and ($kuittikoodi == ' ')) $kuittikoodi='Z';
							break;
					case'05' :
							$selite = "".t("vasta-arvo").": " .  substr($arvo, 0, 19) * 1 .  "<br>";
							$selite .= "".t("valuutta").": " .  substr($arvo, 20, 3) .  "<br>";
							$selite .= "".t("kurssi").": " .  substr($arvo, 24, 11) * 1;
							break;
					case '06' :
							$selite ="".t("Oma sisäinen viite").":";
							$selite .= trim(substr($arvo, 0, 35)) . "<br>";
							$selite .= trim(substr($arvo, 35, 35));
							$sisviite=trim(substr($arvo, 0, 35))*1;
							//Tämä on (kai) ongelma Analysten pankkisoftassa. Lisäävät joskus oman tunnuksen
							//ekaan sisäinenviite kenttään, silloin meidän onkin toisessa.
							if ((trim(substr($arvo, 35, 35))*1 != 0) and (trim(substr($arvo, 0, 35))*1 != 0))
									$sisviite = trim(substr($arvo, 35, 35))*1;
							break;
					case '07' :
							for ($i = 0; $i < 13; $i++) {
								$osa = trim(substr($arvo, $i*35, 35));
								if (strlen($osa) > 0) {
									$selite .= $osa . "<br>";
								}
							}
					case '08' :
							$selite = "".t("maksuaihekoodi").": " . substr($arvo, 0, 3) . "<br>";
							$selite .= "selite: " . trim(substr($arvo, 4, 31));
							break;
					case '09' :
							$selite = "".t("nimen tarkenne").": " .  trim(substr($arvo, 0, 35));
							break;
					default :
						$selite = "<font color='red'><b>*** ".t("OHITETTU LISÄTIETO")." ***</b></font>";
						$eiselitetta = 1;
					}
					$ote1 .= "<td>$selite</td><td></td><td></td><td></td></tr>";
					$vientiselite .= $selite;
					$sisalto .= $ote1;
					$ote .= $ote1;
					break;

				case 'T40':
					kirjoitaote($tiliointi);
					
					$sisalto .= "<tr><td></td><td></td><td></td><td></td><td>".t("Saldo")." " . substr($tietue,6,6) . "</td>";
					$sisalto .= "<td>" . substr($tietue,31,19) / 100 . "</td></tr>";
					break;

				case 'T50':
					kirjoitaote($tiliointi);
					
					if (substr($tietue,6,1) == 1) $jakso = "päivän";
					if (substr($tietue,6,1) == 2) $jakso = "tiliotteen";
					if (substr($tietue,6,1) == 3) $jakso = "kuukauden";
					if (substr($tietue,6,1) == 4) $jakso = "vuoden";
					$sisalto .= "<tr><td colspan='3'>";
					$sisalto .= "".t("Panot")." $jakso ".t("alusta")." ".t("kpl")." " . substr($tietue,13,8) * 1 . "</td>";
					$sisalto .= "<td></td><td></td><td>" . substr($tietue,21,19) / 100 . "</td></tr>";
					$sisalto .= "<tr><td colspan='3'>";
					$sisalto .= "".t("Otot")." $jakso ".t("alusta")." kpl " . substr($tietue,40,8) * 1 . "</td>";
					$sisalto .= "<td></td><td></td><td>" . substr($tietue,48,19) / 100 . "</td></tr>";
					break;


				default:
					if (trim($tunnus) != '') {
						$sisalto .= "<tr><td colspan='6'>";
						$sisalto .= "".t("Tunnistamaton tietue").": $tunnus</td></tr>";
					}
			}
		}
		$sisalto = $otsikko . $sisalto . "</table>";
		if ($sisalto != '') {
			$tyofile="/tmp/to" . $tiliotenro ."-". $alkupvm ."-". $loppupvm ."-". $tilino . ".html";
			file_put_contents($tyofile, $sisalto);
		}
		echo "Done!";
	}

	echo "<br><br><form action = '$PHP_SELF' method='post' name='formi'>
			Siirrä tiliotteet kaudelta
			<input type='hidden' name='tunnus' value='$trow[ytunnus]'>
			<input type='hidden' name='tee' value='all'>
			<input type='text' name='kausi' value=''> esim 2008-01
			<input type='Submit' value='Siirrä'>
			</form>";
	$formi='formi';
	$kentta='kausi';
	require "inc/footer.inc";
?>
