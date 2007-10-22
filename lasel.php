<?php
	require "inc/parametrit.inc";

	echo "<font class='head'>".t("Virtuaaliraportointi")."</font><hr>";
	if ($toim == 'S') {
		$query = "SELECT ytunnus, nimi, postitp
		FROM toimi
		WHERE tunnus = '$tunnus' and yhtio = '$kukarow[yhtio]'";

		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
		 	echo "<b>".t("Haulla ei löytynyt yhtään toimittajaa")."</b>";
		}

	 	$trow=mysql_fetch_array ($result);
		echo "<b>$trow[ytunnus]</b> $trow[nimi] $trow[postitp]<br><br>";
		if ($laji != 'K') {
			$lisa = " and tila = '" . $laji ."'";
			if ($laji=='M'){
				$lisa = " and tila < 'S'";
			}
		}

		$alku += 0;

		$query = "SELECT tapvm, erpcm,
			  summa, valkoodi, ebid, tila, tunnus
			  FROM lasku
			  WHERE liitostunnus = '$tunnus' $lisa
			  ORDER BY tapvm desc LIMIT $alku, 20";

		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
		 	echo "<b>".t("Haulla ei löytynyt yhtään laskua")."</b>";
		}
		echo "<table><tr>";
		for ($i = 0; $i < mysql_num_fields($result)-1; $i++) {
			echo "<th>" . t(mysql_field_name($result,$i))."</th>";
		}
		echo "</tr>";


		while ($trow=mysql_fetch_array ($result)) {
		        echo "<tr>";
		        for ($i=0; $i<mysql_num_fields($result)-1; $i++) {
				if ($i == 4) {
					// tehdään lasku linkki
					echo "<td valign='top'>".ebid($trow['tunnus']) ."</td>";
				}
				else {
					if ($i == 0) { // Linkki tositteelle
						echo "<td>";
						echo "<a href = 'muutosite.php?toim=E&tunnus=$trow[tunnus]'>";
						echo "$trow[$i]</td>";
					}
					else {
						if ($i == 5) { // Laskun tila
							$laskutyyppi = $trow[$i];
							require "inc/laskutyyppi.inc";
			         			echo "<td>".t($laskutyyppi)."</td>";
						}
						else {
							echo "<td>$trow[$i]</td>";
						}
					}
				}
		        }
			echo "</tr>";
		}
		echo "</table><br>";
		if ($alku > 0) {
			$siirry = $alku - 20;
			echo "<a href = '$PHP_SELF?toim=S&tunnus=$tunnus&alku=$siirry&laji=$laji'>".t("Edelliset")."</a> ";
		}
		else {
			echo "".t("Edelliset")." ";
		}
		$siirry = $alku + 20;
		echo "<a href = '$PHP_SELF?toim=S&tunnus=$tunnus&alku=$siirry&laji=$laji'>".t("Seuraavat")."</a> ";
		echo "<br><br>";
		$toim="";
	}
	if (($toim == 'N') || ($toim == 'Y')) {
		if ($toim == 'N') {
			if ($tyyppi == 'T') {
				$lisat = "and nimi like '%" . $nimi . "%'";
			}
                        if ($tyyppi == 'S') {
                                $lisat = "and selaus like '%" . $nimi . "%'";
                        }
		}
		if ($toim == 'Y') {
		        $lisat = "and ytunnus = $ytunnus";
		}
		if (($toim == 'Y') || ($tyyppi == 'T')  || ($tyyppi == 'S')) {
			$query = "SELECT tunnus, ytunnus, nimi, postitp
				  FROM toimi
				  WHERE yhtio = '$kukarow[yhtio]' $lisat
				  ORDER BY selaus";

			$result = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($result) == 0) {
			 	echo "<b>".t("Haulla ei löytynyt yhtään toimittajaa")."</b>";
			}

			echo "<table><tr>";
			for ($i = 0; $i < mysql_num_fields($result); $i++) {
				echo "<th>" . t(mysql_field_name($result,$i))."</th>";
			}
			echo "<th></th><th></th></tr>";

			while ($trow=mysql_fetch_array ($result)) {
		 		echo "<form action = 'lasel.php?toim=S' method='post'>
				      <tr>
				      <input type='hidden' name='tunnus' value='$trow[tunnus]'>";
		 		for ($i=0; $i<mysql_num_fields($result); $i++) {
		 	 		echo "<td>$trow[$i]</td>";
			 	}
				echo "<td><select name = 'laji'>
				      <option value = 'K'>".t("Kaikki")."
				      <option value = 'H'>".t("Hyväksymättä")."
				      <option value = 'M'>".t("Maksamatta")."
				      <option value = 'Y'>".t("Maksettu")."
				      </select></td><td>
				      <input type='Submit' value='".t("Näytä")."'></td>
		 		      </tr></form>";
			}
			echo "</table>";
			$toim='';
		}
		else {
                        $query = "SELECT tunnus, ytunnus, nimi, postitp, summa
                                  FROM lasku
                                  WHERE yhtio = '$kukarow[yhtio]' and nimi like '%" . $nimi . "%'
                                  ORDER BY nimi, summa";

                        $result = mysql_query($query) or pupe_error($query);

                        if (mysql_num_rows($result) == 0) {
                                echo "<b>".t("Haulla ei löytynyt yhtään laskua")."</b>";
                        }
			if (mysql_num_rows($result) > 150) {
                                echo "<b>".t("Haulla löytyi liikaa laskuja")."</b><br>$query";
				exit;
                        }

                        echo "<table><tr>";
                        for ($i = 1; $i < mysql_num_fields($result); $i++) {
                                echo "<th>" . t(mysql_field_name($result,$i))."</th>";
                        }
                        echo "</tr>";

                        while ($trow=mysql_fetch_array ($result)) {
                                echo "<tr>";
                                for ($i=1; $i<mysql_num_fields($result); $i++) {
					if ($i == 2) {
						 echo "<td><a href = 'muutosite.php?toim=E&tunnus=$trow[tunnus]'>
						       $trow[$i]</a></td>";
					}
					else {
                                        	echo "<td>$trow[$i]</td>";
					}
                                }
                                echo "</tr>";
                        }
                        echo "</table>";
                        $toim='';
		}
	}
        if ($toim == 'T') { // Summaukset ja niihin porautuminen
                if ($selaus == 'K') { // Yhden käyttäjän pöydällä olevat laskut tai sen muutos
			if ((strlen($mika) > 0) && (strlen($nimi) > 0)) { // Muutetaan hyväksyntää
				$query = "SELECT hyvak1, hyvak2, hyvak3, hyvak4, hyvak5, hyvaksyja_nyt,
						 h1time, h2time, h3time, h4time
					  FROM lasku
					  WHERE tunnus = '$mika' and yhtio = '$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($result) == 0) {
			        	echo "<b>".t("Muutettavaa laskua ei löytynyt")."!</b>";
					exit;
				}
				$trow=mysql_fetch_array ($result);

				$mita="";

				// Aikaikkunan poisto, joku muu ehti ennen meitä
				if ($trow['hyvaksyja_nyt'] = $kuka)
				{
					if (($trow['hyvak1'] == $kuka) and ($trow['h1time'] == '0000-00-00 00:00:00'))
						$mita = "hyvak1";
					elseif (($trow['hyvak2'] == $kuka) and ($trow['h2time'] == '0000-00-00 00:00:00'))
						$mita = "hyvak2";
					elseif (($trow['hyvak3'] == $kuka) and ($trow['h3time'] == '0000-00-00 00:00:00'))
						$mita = "hyvak3";
					elseif (($trow['hyvak4'] == $kuka) and ($trow['h4time'] == '0000-00-00 00:00:00'))
						$mita = "hyvak4";
					elseif (($trow['hyvak5'] == $kuka) and ($trow['h5time'] == '0000-00-00 00:00:00'))
						$mita = "hyvak5";
					else {
						echo "<b>".t("Laskun siirto ei onnitunut")."</b> '$kuka' --> '$nimi'<br>";
						exit;
					}

					$query = "UPDATE lasku set
							$mita = '$nimi',
							hyvaksyja_nyt = '$nimi'
						  WHERE tunnus = '$mika'";
					$result = mysql_query($query) or pupe_error($query);
					echo "".t("Lasku siirrettiin").".. '$kuka' --> '$nimi'<br>";
				}
				else {
					echo "".t("Lasku ei enää ollut siirrettävissä")."!<br>";
				}
 			}

                	$query = "SELECT nimi, kuka, tuuraaja
                          	  FROM kuka
                          	  WHERE yhtio = '$kukarow[yhtio]' and kuka = '$kuka'";

			$result = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($result) == 0) {
			 	echo "<b>".t("Haulla ei löytynyt yhtään käyttäjää")."</b>";
				exit;
			}
			$trow=mysql_fetch_array ($result);

			echo "<b>$trow[nimi]:".t("n pöydällä olevat laskut")."</b><hr>";

// Tehdään popup, jolla voidaan hyväksyjä myöhemmin vaihtaa
			$query = "SELECT kuka, nimi
	                          FROM kuka
	                          WHERE yhtio = '$kukarow[yhtio]'
	                          ORDER BY nimi";
			$result = mysql_query($query) or pupe_error($query);
			$ulos = "<form action = 'lasel.php?toim=T&selaus=K&kuka=$trow[kuka]'
				 method = 'post'>";
			$ulos .= "<select name='nimi'>";
			while ($vrow=mysql_fetch_array($result)) {
				$sel = "";
				if ($vrow['kuka'] == $trow['tuuraaja']) {
					$sel = "selected";
				}
	                        $ulos .= "<option value = '$vrow[kuka]' $sel>$vrow[nimi]";
			}
	                $ulos .= "</select>";

			$query = "SELECT tapvm, kapvm, erpcm,
						nimi, postitp,
						round(summa * vienti_kurssi, 2) 'kotisumma',
						summa, valkoodi, ebid, tunnus
						FROM lasku
						WHERE hyvaksyja_nyt='$kuka' and
						yhtio = '$kukarow[yhtio]'
						ORDER BY erpcm";
			$result = mysql_query($query) or pupe_error($query);

			echo "<table><tr>";
			for ($i = 0; $i < mysql_num_fields($result)-1; $i++) { // Ei näytetä tunnusta
				echo "<th>" . t(mysql_field_name($result,$i)) . "</th>";
			}
			echo "<th>".t("Siirrä")."</th>";
			echo "</tr>";

			while ($trow=mysql_fetch_array ($result)) {
				echo "<tr>";
				for ($i=0; $i<mysql_num_fields($result)-1; $i++) { // Ei näytetä tunnusta
				if ($i == 8) {
					// tehdään lasku linkki
					echo "<td valign='top'>".ebid($trow['tunnus']) ."</td>";
				}
				else {
					echo "<td>$trow[$i]</td>";
				}
			}
				echo "<td>$ulos<br>
							<input type = 'hidden' name = 'mika' value = '$trow[tunnus]'><input type = 'submit' value = '".t("Siirrä")."'>
				      </td></tr></form>";
			}
                        echo "</table><br>";
 		}
		if ($selaus == 'H') { // Summaus hyväksynnässä olevista laskuista
			echo "<b>".t("Laskuja hyväksymättä")."</b><hr>";
	                $query = "SELECT hyvaksyja_nyt, count(*), min(erpcm)
	                          FROM lasku
	                          WHERE lasku.yhtio = '$kukarow[yhtio]' and hyvaksyja_nyt <> ' '
				  GROUP BY hyvaksyja_nyt
	                          ORDER BY hyvaksyja_nyt";

			$result = mysql_query($query) or pupe_error($query);

	                echo "<table>";
			echo "<tr><th>".t("Kuka")."</th><th>".t("Kpl")."</th><th>".t("Min eräpvm")."</th></tr>";
			while ($trow=mysql_fetch_array ($result)) {
				echo "<tr>";
				for ($i=0; $i<mysql_num_fields($result); $i++) {
					if ($i == 0) {
						echo "<td><a href = 'lasel.php?toim=T&selaus=K&kuka=$trow[hyvaksyja_nyt]'>
						$trow[$i]</a></td>";
					}
					else {
						echo "<td>$trow[$i]</td>";
					}
				}
	                        echo "</tr>";
			}
	                echo "</table><br>";
		}
		if ($selaus == 'T') {
			echo "<b>".t("Laskut per tila")."</b><hr>";
			
			$query = "	SELECT tila, sum(summa * valuu.kurssi), count(*)
						FROM lasku, valuu
						WHERE lasku.yhtio = '$kukarow[yhtio]' and valuu.yhtio = '$kukarow[yhtio]' and
						lasku.valkoodi = valuu.nimi
						GROUP BY tila";
			$result = mysql_query($query) or pupe_error($query);

			echo "<table>";
			echo "<tr><th>".t("Tila")."</th><th>".t("Summa")."</th><th>".t("Kpl")."</th></tr>";
			
			while ($trow=mysql_fetch_array ($result)) {
				echo "<tr>";
			
				for ($i=0; $i<mysql_num_fields($result); $i++) {
					if (($i > 0) && ($i < 3)) {
						echo "<td>";
						printf("%.2f", $trow[$i]);
						echo "</td>";
					}
					else {
						if ($i == 0) { // Laskun tila
							$laskutyyppi = $trow[$i];
							require "inc/laskutyyppi.inc";
							echo "<td>".t($laskutyyppi)."</td>";
						}
						else {
							echo "<td>$trow[$i]</td>";
						}
					}
				}
				echo "</tr>";
			}
			echo "</table><br>";
		}
		if ($selaus == 'E') {
			echo "<b>".t("Laskut per eräpvm")."</b><hr>";
			if ($aika == 'pv') {
				$tapa = 'erpcm';
			}
			if ($aika == 'vi') {
				$tapa = "YEARWEEK(erpcm)";
			}
			if ($aika == 'kk') {
				$tapa = "LEFT(erpcm,7)";
			}
			$query = "SELECT $tapa aika, sum(summa * valuu.kurssi), count(*)
	                          FROM lasku, valuu
	                          WHERE lasku.yhtio = '$kukarow[yhtio]' and valuu.yhtio = '$kukarow[yhtio]' and
					lasku.valkoodi = valuu.nimi and lasku.tila < 'Q'
	                          GROUP BY Aika
	                          ORDER BY Aika";

			$result = mysql_query($query) or pupe_error($query);

	                echo "<table>";
			echo "<tr><th>".t("Eräpvm")."</th><th>".t("Summa")."</th><th>".t("Kpl")."</th></tr>";
	                while ($trow=mysql_fetch_array ($result)) {
				echo "<tr>";
				for ($i=0; $i<mysql_num_fields($result); $i++) {
					if ($i < 2) {
						if (($i == 0) and ($aika == 'pv')) {
							echo "<td align='right'>
							<a href = laskuselailu.php?haku=erpcm&pvm=$trow[aika]>$trow[$i]</td>";
						}
						else {
							echo "<td align='right'>";
							printf("%.2f", $trow[$i]);
							echo "</td>";
						}
					}
					else {
	                                	echo "<td>$trow[$i]</td>";
					}
	                        }
	                        echo "</tr>";
	                }
			echo "</table><br>";
		}
		if ($selaus == 'V') {
			echo "<b>".t("Laskut per valuutta")."</b><hr>";
			$query = "SELECT valkoodi, sum(summa),sum(summa * valuu.kurssi), count(*)
				  FROM lasku, valuu
				  WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.yhtio = '$kukarow[yhtio]' and
				      lasku.valkoodi = valuu.nimi and lasku.tila < 'Q'
				  GROUP BY valkoodi
				  ORDER BY valkoodi";

			$result = mysql_query($query) or pupe_error($query);

	                echo "<table>";
	                echo "<tr><th>".t("Valkoodi")."</th><th>".t("Summa valuutassa")."</th><th>".t("Summa")."</th><th>".t("Kpl")."</th></tr>";
	                while ($trow=mysql_fetch_array ($result)) {
	                        echo "<tr>";
	                        for ($i=0; $i<mysql_num_fields($result); $i++) {
					if (($i > 0) && ($i < 3)) {
						echo "<td align = 'right'>";
						printf("%.2f", $trow[$i]);
						echo "</td>";
					}
					else {
	                                	echo "<td>$trow[$i]</td>";
					}
	                        }
	                        echo "</tr>";
	                }
	                echo "</table><br>";
		}

                if ($selaus == 'I') {
			if ($alvk == 0) {
				echo "<b>".t("Päiväkirja vuodelta")." $alvv</b><hr>";
				$lisa = "YEAR(tiliointi.tapvm) = '$alvv'";
			}
			else {
				echo "<b>".t("Päiväkirja kaudelta")." $alvv-$alvk</b><hr>";
				$lisa = "CONCAT_WS(' ', YEAR(tiliointi.tapvm),MONTH(tiliointi.tapvm)) = '$alvv $alvk'";
			}
			$query = "SELECT tiliointi.tapvm, tiliointi.tilino, selite,
				  tiliointi.summa, vero
		                  FROM tiliointi, lasku
		                  WHERE tiliointi.yhtio = '$kukarow[yhtio]' and lasku.yhtio = '$kukarow[yhtio]' and
					tiliointi.ltunnus = lasku.tunnus and
					tiliointi.korjattu='' and
					$lisa
		                  ORDER BY tiliointi.tapvm, ltunnus";

			$result = mysql_query($query) or pupe_error($query);

		        echo "<table><tr>";
		 	for ($i = 0; $i < mysql_num_fields($result); $i++) { // Ei näytetä tunnusta
		 	 	echo "<th>" . t(mysql_field_name($result,$i)) . "</th>";
		 	}
			while ($trow=mysql_fetch_array ($result)) {
			        echo "<tr>";
			        for ($i=0; $i<mysql_num_fields($result); $i++) {
					if ($i==0) { // Rivin alussa tehtävät jutut
						if ($edvero ==  $trow[$i]) { // Vaihtuiko verokanta?
							$trow[$i] = " ";
						}
						else {
							if ($edvero > 0) { // Ei summaa listan alkuun!
								echo "<td></td><td></td><td></td><td></td>
								      <td></td>
								      <td>*</td></tr><tr>";
							}
                                                        $edvero = $trow[$i];
						}
					}
			 		if ($i > 2) {
			                    	echo "<td align = 'right'>";
						if ($i > 4) {
							echo $trow[$i];
						}
						else {
		                                	printf("%.2f", $trow[$i]);
						}
                                     		echo "</td>";
			                }
			                else {
		                                echo "<td>$trow[$i]</td>";
		                        }
			 	}
		                echo "</tr>";
		        }
			$verotot += $verosum;
//	                echo "<td></td><td></td><td></td><td></td>
//                              <td align = 'right'>$verosum</td>
//                               <td>*</td></tr><tr>";
			echo "<td></td><td></td><td></td><td></td>
                              <td></td>
                    	      <td>**</td></tr>";
                        echo "</table><br>";
		}

		if ($selaus == 'J') {
			require "inc/paakirja.inc";
		}
                if ($selaus == 'R') {
			echo "<b>".t("Arvonlisäverolaskelma kaudelta")." $alvv-$alvk</b><hr>";
			$query = "SELECT vero, tiliointi.tilino, nimi, round(sum(summa * (1+vero/100)),2) Bruttosumma,
                                  round(sum(summa * vero / 100),2) Verot, count(*) Kpl
                                  FROM tiliointi, tili
                                  WHERE tiliointi.yhtio = '$kukarow[yhtio]' and tili.yhtio = tiliointi.yhtio and
                                        tiliointi.tilino = tili.tilino and
					tiliointi.korjattu='' and
                                        CONCAT_WS(' ', YEAR(tapvm),MONTH(tapvm)) = '$alvv $alvk' and
                                        vero > 0
                                  GROUP BY vero, tiliointi.tilino
                                  ORDER BY vero, tiliointi.tilino";

			$result = mysql_query($query) or pupe_error($query);

			echo "".t("rivejä")." ".mysql_num_rows($result);
			echo "<table><tr>";
			for ($i = 0; $i < mysql_num_fields($result); $i++) { // Ei näytetä tunnusta
                                echo "<th>" . t(mysql_field_name($result,$i)) . "</th>";
			}
                        while ($trow=mysql_fetch_array ($result)) {
                                echo "<tr>";
                                for ($i=0; $i<mysql_num_fields($result); $i++) {
                                        if ($i==0) { // Rivin alussa tehtävät jutut
                                                if ($edvero ==  $trow[$i]) { // Vaihtuiko verokanta?
                                                        $trow[$i] = " ";
                                                }
                                                else {
                                                      	if ($edvero > 0) { // Ei summaa listan alkuun!
                                                                echo "<td></td><td></td><td></td><td></td>
                                                                      <td align = 'right'>$verosum</td>
                                                                      <td>*</td></tr><tr>";
                                                                $verotot += $verosum;
                                                                $verosum = 0;
                                                        }
                                                        $edvero = $trow[$i];
                                                }
                                        }
                                        if ($i > 2) {
                                                echo "<td align = 'right'>";
                                                if ($i > 4) {
                                                        echo $trow[$i];
                                                }
                                                else {
                                                      	printf("%.2f", $trow[$i]);
                                                }
                                                echo "</td>";
                                        }
                                        else {
                                              	echo "<td>$trow[$i]</td>";
                                        }
                                }
                                $verosum += $trow['Verot'];
                                echo "</tr>";
			}
                        $verotot += $verosum;
			echo "<td></td><td></td><td></td><td></td>
                              <td align = 'right'>$verosum</td>
                               <td>*</td></tr><tr>";
			echo "<td></td><td></td><td></td><td></td>
                              <td align = 'right'>$verotot</td>
                                 <td>**</td></tr>";
                        echo "</table><br>";
                }

			if ($selaus == 'Q') {
				require 'inc/maksuvalmius.inc';
			}
			if ($selaus == 'Z') {
				require 'inc/tase.inc';
			}
			if ($selaus == 'C') {
				require 'inc/tilisaldot.inc';
			}
			if ($selaus == 'B') {
				require 'inc/ostovelat.inc';
			}
			if ($selaus == 'n') {
				require 'inc/laskusumma.inc';
			}

	        $toim='';
	}
	if (strlen($toim) == 0) {
		echo "<br><br><table border='0'><tr>";
 	        echo "<td><form action = 'lasel.php?toim=Y' method='post'>
                      ".t("Valitse laskut Y-tunnuksen perusteella")."</td>
 	              <td><input type = 'text' name = 'ytunnus' value = '0'></td>
 	              <td><input type = 'submit' value = '".t("Valitse")."'></td></form>";
		echo "</tr><tr>";
 	        echo "<td><form action = 'lasel.php?toim=N' method='post'>
                      ".t("Valitse toimittajaa nimellä")."</td>
 	              <td><input type = 'text' name = 'nimi'>
			<select name='tyyppi'><option value = 'T'>".t("Toimittajan nimi")."<option value = 'S'>".t("Toimittajan selaussana")."<option value = 'L'>".t("Nimi laskulla")."</select></td>
 	              <td><input type = 'submit' value = '".t("Valitse")."'></td></form>";
                echo "</tr><tr>";
                echo "<td><form action = 'lasel.php?toim=T&selaus=n' method='post'>
                      ".t("Valitse lasku summalla")."</td>
                      <td><input type = 'text' name = 'summa1' size=8> - <input type = 'text' name = 'summa2' size=8></td>
                      <td><input type = 'submit' value = '".t("Valitse")."'></td></form>";
		echo "</tr><tr>";
		echo "<td><form action = 'lasel.php?toim=T' method='post'>
		      <input type = 'hidden' name = 'selaus' value = 'H'>
                      ".t("Laskuja hyväksymättä")."</td>
                      <td></td><td><input type = 'submit' value = '".t("Näytä")."'></td></form>";
		echo "</tr><tr>";
                echo "<td><form action = 'lasel.php?toim=T' method='post'>
		      <input type = 'hidden' name = 'selaus' value = 'T'>
                      ".t("Laskut tilansa mukaan")."</td>
                      <td></td><td><input type = 'submit' value = '".t("Näytä")."'></td></form>";
		echo "</tr><tr>";
		echo "<td><form action = 'lasel.php?toim=T' method='post'>
                      <input type = 'hidden' name = 'selaus' value = 'B'>
                      ".t("Ostovelat kustannuspaikoittain")."</td>
                      <td></td><td><input type = 'submit' value = '".t("Näytä")."'></td></form>";
		echo "</tr><tr>";
                echo "<td><form action = 'lasel.php?toim=T' method='post'>
		      <input type = 'hidden' name = 'selaus' value = 'E'>
                      ".t("Laskut per eräpäivä")."</td><td>
		      <select name='aika'>
		      <option value = 'pv'>".t("Päivä")."
		      <option value = 'vi'>".t("Viikko")."
		      <option value = 'kk'>".t("Kuukausi")."</select>
                      </td><td><input type = 'submit' value = '".t("Näytä")."'></td></form>";
		echo "</tr><tr>";
		echo "<td><form action = 'lasel.php?toim=T' method='post'>
                      <input type = 'hidden' name = 'selaus' value = 'Q'>
                      ".t("Maksuvalmius")."</td><td>
                      <select name='aika'>
                      <option value = 'pv'>".t("Päivä")."
                      <option value = 'vi'>".t("Viikko")."
                      <option value = 'kk'>".t("Kuukausi")."</select>
		      Konserni <input type = 'checkbox' name = 'konserni'>
                      </td><td><input type = 'submit' value = '".t("Näytä")."'></td></form>";
		echo "</tr><tr>";
                echo "<td><form action = 'lasel.php?toim=T' method='post'>
		      <input type = 'hidden' name = 'selaus' value = 'V'>
                      ".t("Laskut per valuutta")."</td>
                      <td></td><td><input type = 'submit' value = '".t("Näytä")."'></td></form>";
	        echo "</tr><tr>";
                echo "<td><form action = 'lasel.php?toim=T' method='post'>
                      <input type = 'hidden' name = 'selaus' value = 'I'>
                      ".t("Päiväkirja")."</td>
                      <td>
                      <select name='alvv'>
                      <option value = '".(date("Y")-4)."'>".(date("Y")-4)."
                      <option value = '".(date("Y")-3)."'>".(date("Y")-3)."
                      <option value = '".(date("Y")-2)."'>".(date("Y")-2)."
                      <option value = '".(date("Y")-1)."'>".(date("Y")-1)."
                      <option value = '".date("Y")  ."' selected>".date("Y")."
                      <select name='alvk'>
                      <option value = '0'>".t("koko vuosi")."
                      <option value = '1'>01
                      <option value = '2'>02
                      <option value = '3'>03
                      <option value = '4'>04
                      <option value = '5'>05
                      <option value = '6'>06
                      <option value = '7'>07
                      <option value = '8'>08
                      <option value = '9'>09
                      <option value = '10'>10
                      <option value = '11'>11
                      <option value = '12'>12</select>
                      </td><td><input type = 'submit' value = '".t("Näytä")."'></td></form>";
                echo "</tr><tr>";
		echo "<td><form action = 'lasel.php?toim=T' method='post'>
                      <input type = 'hidden' name = 'selaus' value = 'J'>
                      ".t("Pääkirja")."</td>
                      <td>
                      <select name='alvv'>";

				for ($i = date("Y"); $i >= date("Y")-4; $i--) {
					if ($i == date("Y")) $sel = "selected";
					else $sel = "";
					echo "<option value='$i' $sel>$i</option>";
				}

echo "</select>
                      <select name='alvk'>
                      <option value = '0'>".t("koko vuosi")."
                      <option value = '1'>01
                      <option value = '2'>02
                      <option value = '3'>03
                      <option value = '4'>04
                      <option value = '5'>05
                      <option value = '6'>06
                      <option value = '7'>07
                      <option value = '8'>08
                      <option value = '9'>09
                      <option value = '10'>10
                      <option value = '11'>11
                      <option value = '12'>12</select>
		      ".t("Lisää valintoja")." <input type = 'checkbox' name = 'lisaa'>
                      </td><td><input type = 'submit' value = '".t("Näytä")."'></td></form>";
		echo "</tr><tr>";
		echo "<td><form action = 'lasel.php?toim=T' method='post'>
                      <input type = 'hidden' name = 'selaus' value = 'Z'>
                      ".t("Tase/tuloslaskelma")."</td>
                      <td><select name = 'tyyppi'>
                      <option value='4'>".t("Sisäinen tuloslaskelma")."
		      <option value='3'>".t("Ulkoinen tuloslaskelma")."
		      <option value='2'>".t("Vastattavaa")."
		      <option value='1'>".t("Vastaavaa")."
		      </select><br>
                      <select name='alvv'>
                      <option value = '2000'>2000
                      <option value = '2001'>2001
                      <option value = '2002'>2002
                      <option value = '2003'>2003
                      <option value = '2004' selected>2004
                      <option value = '2005'>2005
                      <select name='alvk'>
                      <option value = '01'>01
                      <option value = '02'>02
                      <option value = '03'>03
                      <option value = '04'>04
                      <option value = '05'>05
                      <option value = '06'>06
                      <option value = '07'>07
                      <option value = '08'>08
                      <option value = '09'>09
                      <option value = '10'>10
                      <option value = '11'>11
                      <option value = '12'>12</select>
		      Lisää valintoja <input type = 'checkbox' name = 'lisaa'>
                      </td><td><input type = 'submit' value = '".t("Näytä")."'></td></form>";
		echo "</tr><tr>";
		echo "<td><form action = 'lasel.php?toim=T' method='post'>
                      <input type = 'hidden' name = 'selaus' value = 'C'>
                      ".t("Tilien saldot")."</td>
                      <td>
                      <select name='alvv'>
                      <option value = '2000'>2000
                      <option value = '2001'>2001
                      <option value = '2002'>2002
                      <option value = '2003'>2003
                      <option value = '2004' selected>2004
                      <option value = '2005'>2005
                      <select name='alvk'>
		      <option value = '0'>".t("Tämä hetki")."
                      <option value = '01'>01
                      <option value = '02'>02
                      <option value = '03'>03
                      <option value = '04'>04
                      <option value = '05'>05
                      <option value = '06'>06
                      <option value = '07'>07
                      <option value = '08'>08
                      <option value = '09'>09
                      <option value = '10'>10
                      <option value = '11'>11
                      <option value = '12'>12</select>
                      kumulatiivinen <input type = 'checkbox' name = 'kumulat'>
                      </td><td><input type = 'submit' value = '".t("Näytä")."'></td></form>";
		echo "</tr><tr>";
	        echo "<td><form action = 'lasel.php?toim=T' method='post'>
	              <input type = 'hidden' name = 'selaus' value = 'R'>
	              ".t("Arvonlisälaskelma")."</td>
	              <td>
		      <select name='alvv'>
	              <option value = '2000'>2000
	              <option value = '2001'>2001
	              <option value = '2002'>2002
                  <option value = '2003'>2003
                  <option value = '2004' selected>2004
                  <option value = '2005'>2005

                  <select name='alvk'>
	              <option value = '1'>01
	              <option value = '2'>02
	              <option value = '3'>03
	              <option value = '4'>04
	              <option value = '5'>05
	              <option value = '6'>06
	              <option value = '7'>07
                      <option value = '8'>08
                      <option value = '9'>09
                      <option value = '10'>10
                      <option value = '11'>11
                      <option value = '12'>12</select>
                      </td><td><input type = 'submit' value = '".t("Näytä")."'></td></form>";
		echo "</tr></table>";
	}

	echo "</body></html>";
?>
