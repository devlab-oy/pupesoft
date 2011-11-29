<?php

	$pupe_DataTables = array("eankoodi");

	// Tämä skripti käyttää slave-tietokantapalvelinta
	$useslave = 1;

	require("inc/parametrit.inc");

	echo "<font class='head'>".t("Listataan kaikki tuotteet joiden EAN-koodi on viallinen")."</font><hr>\n";

	echo "<p>".t("HUOM: Mikäli EAN13-koodiksi on lyöty joku muu viivakoodi, niin se näyttää tässä tulosteessa vialliselta").".</p>";

	echo "<form method='post' action=''>";
	echo "<br><input type='submit' name='tee' value='".t("Tee listaus")."'>";
	echo "</form><br>";

	if (isset($tee) and $tee != "") {

		$query = " 	SELECT yhtio, eankoodi, tuoteno, nimitys, tunnus
					FROM tuote
					WHERE yhtio = '{$kukarow["yhtio"]}'
					AND status in ('A','E','T')
					AND eankoodi != ''";
		$result = pupe_query($query);
		$total = mysql_num_rows($result);

		$count = 0;

		// piirretään taulua
		echo "<br>";

		pupe_DataTables(array(array($pupe_DataTables[0], 3, 4, true, true)));

		echo "<br>";
		echo "<table class='display dataTable' id='$pupe_DataTables[0]'>";
		echo "<thead>";
		echo "<tr>";
		echo "<th>".t("tuoteno")."</th>";
		echo "<th>".t("Nimitys")."</th>";
		echo "<th>".t("Eankoodi")."</th>";
		echo "<th>".t("Virhe")."</th>";
		echo "</tr>";
		echo "<tr>";
		echo "<td><input type='text' 	size='10' class='search_field' name='search_tuoteno_haku'></td>";
		echo "<td><input type='text' 	size='10' class='search_field' name='search_nimitys_haku'></td>";
		echo "<td><input type='text' 	size='10' class='search_field' name='search_eankoodi_haku'></td>";
		echo "<td><input type='hidden' 	size='10' class='search_field' name='search_virhe_haku'></td>";
		echo "</tr>";
		echo "</thead>";
		echo "<tbody>";

		while ($rivi = mysql_fetch_assoc($result)) {
			$virhe = t("Viallinen ean13-koodi");

			if (tarkista_ean13($rivi["eankoodi"]) === FALSE) {

				if (strlen($rivi["eankoodi"]) != 13) {
					$virhe .= ",<br>".t("EAN-koodi vääränpituinen")."";
				}

				if (preg_match('/[^0-9]/', $rivi["eankoodi"])) {
					$virhe .= ",<br>".t("EAN-koodissa vääriä merkkejä tai välilyönti")."";
				}

				echo "<tr>";
				echo "<td>{$rivi["tuoteno"]}</td>";
				echo "<td>{$rivi["nimitys"]}</td>";
				echo "<td>{$rivi["eankoodi"]}</td>";
				echo "<td>{$virhe}</td>";
				echo "</tr>";
				$count++;
			}
		}

		echo "</tbody>";
		echo "</table>";
	}

	require("inc/footer.inc");
?>