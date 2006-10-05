<?php
	require("inc/parametrit.inc");

	echo "<font class='head'>".t("Tuotekysely")."</font><hr>";
		 //syotetaan tuotenumero
		$formi='formi';
		$kentta = 'tuoteno';

		echo "<form action='$PHP_SELF' method='post' name='$formi' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='Z'>";
		echo "<table><tr>";
		echo "<th>".t("Anna tuotenumero").":</th>";
		echo "<td><input type='text' name='tuoteno' value=''></td>";
		echo "<td class='back'><input type='Submit' value='".t("Valitse")."'></td>";
		echo "</tr></table>";
		echo "</form>";

	if ($tee == 'Z') {

		if(substr($tuoteno,0,1) == '*') { // Nyt me selataan
			$tee = 'Y';
			$query = "	SELECT tuoteno, nimitys
						FROM tuote
						WHERE yhtio = '$kukarow[yhtio]' 
						and status != 'P'
						and nimitys like '%" . substr($tuoteno,1) . "%'
						ORDER BY tuoteno";
		}
		if (substr($tuoteno,-1) == '*') {
			$tee = 'Y';
			$query = "	SELECT tuoteno, nimitys
						FROM tuote
						WHERE yhtio = '$kukarow[yhtio]' 
						and status != 'P'
						and tuoteno like '" . substr($tuoteno,0,-1) . "%'
						ORDER BY tuoteno";
		}
		if ($tee == 'Y') {
			$tresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($tresult) >  100) {
				$kentta='tuoteno';
				$varaosavirhe = "".t("Haulla loytyy liikaa tuotteita")."!";
				$tuoteno='';
				$tee = '';
			}
			else {
				if (mysql_num_rows($tresult) == 0) {
					$kentta='tuoteno';
					$varaosavirhe = "".t("Haulla ei loydy tuotteita")."!";
					$tuoteno='';
					$tee = '';
				}
				else {
					//Tehdaan pop-up valmiiksi myohempaa kayttoa varten
					$kentta = 'atil';
					$ulos = "<select name='tuoteno'>";
					while ($trow = mysql_fetch_array ($tresult)) {
						$ulos .= "<option value='$trow[0]'>$trow[0] $trow[1]";
					}
					$ulos .= "</select>";
				}
			}
		}
	}
	//tuotteen varastostatus
	if ($tee == 'Z') {
		$query = "	SELECT tuote.*, date_format(muutospvm, '%Y-%m-%d') muutos, date_format(luontiaika, '%Y-%m-%d') luonti,
					group_concat(distinct tuotteen_toimittajat.toimittaja order by tuotteen_toimittajat.tunnus separator '<br>') toimittaja,
					group_concat(distinct tuotteen_toimittajat.osto_era order by tuotteen_toimittajat.tunnus separator '<br>') osto_era,
					group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') toim_tuoteno,
					group_concat(distinct tuotteen_toimittajat.tuotekerroin order by tuotteen_toimittajat.tunnus separator '<br>') tuotekerroin 
					FROM tuote 
					LEFT JOIN tuotteen_toimittajat USING (yhtio, tuoteno)
					WHERE tuote.yhtio = '$kukarow[yhtio]' 
					and tuote.status != 'P'
					and tuote.tuoteno = '$tuoteno'
					GROUP BY tuote.tuoteno";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {
			$tuoterow = mysql_fetch_array($result);

			//saldot per varastopaikka
			$query = "select * from tuotepaikat where tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
			$sresult = mysql_query($query) or pupe_error($query);

			//saldolaskentaa tulevaisuuteen
			$query = "	SELECT  if(tyyppi = 'O', toimaika, kerayspvm) paivamaara,
						sum(if(tyyppi='O', varattu, 0)) tilattu,
						sum(if((tyyppi='L' or tyyppi='G' or tyyppi='V'), varattu, 0)) varattu
						FROM tilausrivi
						WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno' and varattu>0 and tyyppi in ('O','L','G','V')
						GROUP BY toimaika";
			$presult = mysql_query($query) or pupe_error($query);

			//tilauksessa olevat
			$query = "	SELECT toimaika paivamaara,
						sum(varattu) tilattu, otunnus
						FROM tilausrivi
						WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno' and varattu>0 and tyyppi='O'
						GROUP BY toimaika, otunnus
						ORDER BY toimaika";
			$tulresult = mysql_query($query) or pupe_error($query);

			//korvaavat tuotteet
			$query  = "select * from korvaavat where tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
			$korvaresult = mysql_query($query) or pupe_error($query);

			//eka laitetaan tuotteen yleiset (aika staattiset) tiedot
			echo "<table>";
			echo "<tr><th>".t("Tuoteno")."</th><th colspan='5'>".t("Nimitys")."</th>";
			echo "<tr><td>$tuoterow[tuoteno]</td><td colspan='5'>".substr($tuoterow["nimitys"],0,100)."</td></tr>";

			echo "<tr><th>".t("Os/Try")."</th><th>".t("Toimittaja")."</th><th>".t("Aleryhmä")."</th><th>".t("Tähti")."</th><th colspan='2'>".t("VAK")."</th></tr>";
			echo "<td>$tuoterow[osasto]/$tuoterow[try]</td><td>$tuoterow[toimittaja]</td>
					<td>$tuoterow[aleryhma]</td><td>$tuoterow[tahtituote]</td><td colspan='2'>$tuoterow[vakkoodi]</td></tr>";
			echo "<tr><th>".t("Toimtuoteno")."</th><th>".t("Myyntihinta")."</th><th>".t("Nettohinta")."</th><th colspan='3'>".t("Viimeksi tullut")."</th>";
			echo "<tr><td>$tuoterow[toim_tuoteno]</td>
					<td>$tuoterow[myyntihinta]</td><td>$tuoterow[nettohinta]</td><td colspan='3'>$tuoterow[vihapvm]</td></tr>";
			echo "<tr><th>".t("Hälyraja")."</th><th>".t("Tilerä")."</th><th>".t("Toierä")."</th><th>".t("Kerroin")."</th><th>".t("Tarrakerroin")."</th><th>".t("Tarrakpl")."</th>";
			echo "<tr><td>$tuoterow[halytysraja]</td>
					<td>$tuoterow[osto_era]</td><td>$tuoterow[myynti_era]</td><td>$tuoterow[tuotekerroin]</td>
					<td>$tuoterow[tarrakerroin]</td><td>$tuoterow[tarrakpl]</td></tr>";
			echo "</table><br>";


			// Varastosaldot ja paikat
			echo "<table><tr><td class='back' valign='top'>";

			if ($tuoterow["ei_saldoa"] == '') {
				//saldot
				echo "<table>";
				echo "<tr><th>".t("Varastopaikka")."</th><th>".t("Saldo")."</th></tr>";

				$kokonaissaldo = 0;
				if (mysql_num_rows($sresult) > 0) {
					while ($saldorow = mysql_fetch_array ($sresult)) {
						echo "<tr><td>$saldorow[hyllyalue] $saldorow[hyllynro] $saldorow[hyllyvali] $saldorow[hyllytaso]</td>";
						echo "<td>$saldorow[saldo]</td></tr>";
						//summataan kokonaissaldoa
						$kokonaissaldo += $saldorow["saldo"];
					}
				}
				echo "<tr><th>".t("Yhteensä")."</th><td>$kokonaissaldo</td></tr>";
				$asaldo = $kokonaissaldo;
				$ennpois = 0;
				$tilauksessa = 0;
				while ($prow = mysql_fetch_array ($presult)) {
					if($prow["varattu"] > 0) {
						$ennpois += $prow["varattu"];
					}
				}
				$myytavissa = $kokonaissaldo-$ennpois;
				echo "<tr><th>".t("Myytävissä")."</th><td>$myytavissa</td></tr>";

				echo "</table>";
				echo "</td><td class='back' valign='top'>";

				// tilatut
				echo "<table>";
				echo "<tr><th>".t("Päivämäärä")."</th><th>".t("Tilattu")."</th><th>".t("Tilaus")."</th></tr>";

				$tilauksessa = 0;
				if (mysql_num_rows($tulresult)>0) {
					while ($prow = mysql_fetch_array ($tulresult)) {
							$asaldo = $asaldo + $prow["tilattu"];
							echo "<tr><td>$prow[paivamaara]</td><td>$prow[tilattu]</td><td>$prow[otunnus]</td></tr>";
							$tilauksessa += $prow["tilattu"];
					}
				}
				echo "<tr><th>".t("Yhteensä")."</th><td>$tilauksessa</td></tr>";
				echo "</table>";
				echo "</td><td class='back' valign='top'>";
			}

			echo "<table>";
			echo "<th>".t("Korvaavat")."</th><th>".t("Kpl").".</th>";

			if (mysql_num_rows($korvaresult)==0)
			{
				echo "<tr><td>".t("Ei korvaavia")."!</td><td></td></tr>";
			}
			else
			{
				// tuote löytyi, joten haetaan sen id...
				$row    = mysql_fetch_array($korvaresult);
				$id		= $row['id'];

				$query = "select * from korvaavat where id='$id' and tuoteno<>'$tuoteno' and yhtio='$kukarow[yhtio]' order by jarjestys, tuoteno";
				$korva2result = mysql_query($query) or pupe_error($query);

				while ($row = mysql_fetch_array($korva2result))
				{
					//hateaan vielä korvaaville niiden saldot.
					//saldot per varastopaikka
					$query = "select sum(saldo) alkusaldo from tuotepaikat where tuoteno='$row[tuoteno]' and yhtio='$kukarow[yhtio]'";
					$alkuresult = mysql_query($query) or pupe_error($query);
					$alkurow = mysql_fetch_array($alkuresult);

					//ennakkopoistot
					$query = "	SELECT sum(varattu) varattu
								FROM tilausrivi
								WHERE tyyppi in ('L','G','V') and yhtio = '$kukarow[yhtio]' and tuoteno = '$row[tuoteno]' and varattu>0";
					$varatutresult = mysql_query($query) or pupe_error($query);
					$varatutrow = mysql_fetch_array($varatutresult);

					$vapaana = $alkurow["alkusaldo"] - $varatutrow["varattu"];

					echo "<tr><td><a href='$PHP_SELF?tee=Z&tuoteno=$row[tuoteno]'>$row[tuoteno]</a></td><td>$vapaana</td></tr>";
				}

			}
			echo "</table></td></tr></table><br>";
		}
		else {
			echo "<font class='message'>".t("Yhtään tuotetta ei löytynyt")."!<br></font>";
		}
		$tee = '';
	}
	if ($tee == "Y") {
			echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='Z'>";
			echo "<table><tr>";
			echo "<th>".t("Valitse tuotenumero").":</th>";
			echo "<td>$ulos</td>";
			echo "<td class='back'><input type='Submit' value='".t("Valitse")."'></td>";
			echo "</tr></table>";
			echo "</form>";
	}

	require ("inc/footer.inc");
?>
