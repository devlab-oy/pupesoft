<?php
	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;
	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Tavarakatelista")."</font><hr>";

	if ($tee != '') {

		$rivi = "Tullinimike\tMaakoodi\tTuotenumero\tNimitys\r\n";
		$virtu= "";

		// tehdään tullin aineisto
		$query  = "select tullinimike1, (SELECT alkuperamaa FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tuote.yhtio and tuotteen_toimittajat.tuoteno=tuote.tuoteno LIMIT 1) alkuperamaa, tuoteno, nimitys
					from tuote 
					where yhtio='$kukarow[yhtio]' 
					and tullinimike1 > 0
					and status!='P'
					group by tullinimike1, alkuperamaa
					order by tullinimike1, alkuperamaa";
		$result = mysql_query($query) or pupe_error($query);

		while ($row  = mysql_fetch_array($result)) {

			// katotaanonko oikea tullinimike
			$query = "select cn from tullinimike where cn='$row[tullinimike1]' and kieli = '$yhtiorow[kieli]'";
			$tulre = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($tulre)==0) {
				$virtu .= "'$row[tullinimike1]',";
			}

			// kirjoitetaan rivi
			$rivi .= "$row[tullinimike1]\t$row[alkuperamaa]\t$row[tuoteno]\t".asana('nimitys_',$row['tuoteno'],$row['nimitys'])."\r\n";
		}

		if ($virtu == "") {

			// meilin infoja
			$otsikko   = t("Tavarakatelista");
			$failinimi = "$yhtiorow[yhtio].txt";

			$bound     = uniqid(time()."_") ;

			$headeri   = "From: <$yhtiorow[postittaja_email]>\r\n";
			$headeri  .= "MIME-Version: 1.0\r\n" ;
			$headeri  .= "Content-Type: multipart/mixed; boundary=\"$bound\"\r\n" ;

			$content   = "--$bound\r\n";
			$content  .= "Content-Type: application/pdf; name=\"$failinimi\"\r\n" ;
			$content  .= "Content-Transfer-Encoding: base64\r\n" ;
			$content  .= "Content-Disposition: inline; filename=\"$failinimi\"\r\n\r\n";

			$content .= chunk_split(base64_encode($rivi));
			$content .= "\r\n" ;
			$content .= "--$bound\r\n";

			$boob     = mail($kukarow[eposti], $otsikko, $content, $headeri);

			echo t("Lähetettiin meili"). " $kukarow[eposti].";
		}
		else {

			$virtu = substr($virtu,0,-1); // vika pilkku pois
			$query = "select tuoteno, osasto, try, nimitys, tullinimike1 from tuote where yhtio='$kukarow[yhtio]' and tullinimike1 in ($virtu) order by osasto, try, tuoteno";
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
				echo "<td>".asana('nimitys_',$row['tuoteno'],$row['nimitys'])."</td>";
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