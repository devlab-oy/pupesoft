<?php

	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;

	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Tavarakatelista")."</font><hr>";

	if ($tee != '') {

		$rivi = "Tullinimike\tTullinimitys\tMaa\tTuotenumero\tNimitys\r\n";
		$virtu = "";

		// tehdään tullin aineisto
		$query = "	SELECT tuote.tullinimike1, ifnull(tuotteen_toimittajat.alkuperamaa, '') alkuperamaa, tuote.tuoteno, tuote.nimitys
					FROM tuote
					LEFT JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tuote.yhtio and tuotteen_toimittajat.tuoteno = tuote.tuoteno)
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					AND tuote.tullinimike1 > 0
					AND tuote.status NOT IN ('P','X')
					GROUP BY tullinimike1, alkuperamaa
					ORDER BY tullinimike1, alkuperamaa";
		$result = mysql_query($query) or pupe_error($query);

		while ($row  = mysql_fetch_array($result)) {
			// katotaanonko oikea tullinimike
			$query = "SELECT cn, dm FROM tullinimike WHERE cn = '$row[tullinimike1]' AND kieli = '$yhtiorow[kieli]'";
			$tulre = mysql_query($query) or pupe_error($query);

			$tullinimikeres = mysql_fetch_array($tulre);

			if (mysql_num_rows($tulre)==0) {
				$virtu .= "'$row[tullinimike1]',";
			}

			// kirjoitetaan rivi
			$rivi .= "$row[tullinimike1]\t$tullinimikeres[dm]\t$row[alkuperamaa]\t$row[tuoteno]\t$row[nimitys]\r\n";
		}

		if ($virtu == "") {
			// meilin infoja
			$otsikko   = t("Tavarakatelista");
			$failinimi = t("Tavarakatelista")."-$yhtiorow[yhtio].txt";

			$bound     = uniqid(time()."_") ;

			$headeri   = "From: <$yhtiorow[postittaja_email]>\n";
			$headeri  .= "MIME-Version: 1.0\n" ;
			$headeri  .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

			$content   = "--$bound\n";
			$content  .= "Content-Type: text/plain; name=\"$failinimi\"\n" ;
			$content  .= "Content-Transfer-Encoding: base64\n" ;
			$content  .= "Content-Disposition: attachment; filename=\"$failinimi\"\n\n";

			$content .= chunk_split(base64_encode($rivi));
			$content .= "\n" ;
			$content .= "--$bound\n";

			$boob     = mail($kukarow["eposti"], $otsikko, $content, $headeri, "-f $yhtiorow[postittaja_email]");

			echo t("Tavarakatelista lähetettiin osoitteeseen"). " $kukarow[eposti].<br><br>";
		}
		else {

			$virtu = substr($virtu,0,-1); // vika pilkku pois
			$query = "SELECT tuoteno, osasto, try, nimitys, tullinimike1 FROM tuote WHERE yhtio = '$kukarow[yhtio]' AND tullinimike1 IN ($virtu) ORDER BY osasto, try, tuoteno";
			$virre = mysql_query($query) or pupe_error($query);

			echo "<font class='message'>".t("Virheellisiä tullinimikkeitä seuraavilla tuotteilla. Nämä on korjattava ennen lähetystä.")."</font><hr>";

			echo "<table>";
			echo "<tr>";
			echo "<th>osasto</th>";
			echo "<th>try</th>";
			echo "<th>tuoteno</th>";
			echo "<th>nimitys</th>";
			echo "<th>tullinimike</th>";
			echo "</tr>";

			while ($row  = mysql_fetch_array($virre)) {
				echo "<tr>";
				echo "<td>$row[osasto]</td>";
				echo "<td>$row[try]</td>";
				echo "<td>$row[tuoteno]</td>";
				echo "<td>$row[nimitys]</td>";
				echo "<td>$row[tullinimike1]</td>";
				echo "</tr>";
			}

			echo "</table><br>";
		}

	}

	echo "<form name='epaku' action='$PHP_SELF' method='post' autocomplete='off'>";
	echo "<input type='submit' name='tee' value='".t("Aja tavarakatelista")."'>";
	echo "</form>";

	require ("../inc/footer.inc");

?>