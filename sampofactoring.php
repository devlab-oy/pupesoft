<?php
	require('inc/parametrit.inc');

	echo "<font class='head'>Danskebank Factoring</font><hr><br>";

	if ($tee == "") {

		echo "<font class='message'>Luodaan Danskebank Factoring siirtolista kaikista lähettämättömistä factoring laskuista.</font><br><br>";

		// haetaan kaikki sampo factoroidut laskut jota ei ole vielä liitetty mihinkään siirtolistalle
		$query = "	SELECT count(*) kpl, sum(arvo) arvo, sum(summa) summa
					FROM lasku USE INDEX (factoring)
					JOIN maksuehto ON (maksuehto.yhtio = lasku.yhtio and maksuehto.tunnus = lasku.maksuehto and maksuehto.factoring = 'SAMPO')
					WHERE lasku.yhtio = '$kukarow[yhtio]' and
					lasku.tila = 'U' and
					lasku.alatila = 'X' and
					lasku.summa != 0 and
					lasku.factoringsiirtonumero = '' and
					lasku.valkoodi = '$yhtiorow[valkoodi]'";
		$result = mysql_query ($query) or pupe_error($query);
		$laskurow = mysql_fetch_array($result);

		echo "<table>";

		echo "<tr>";
		echo "<th colspan='2'>Laskuja lähettämättä</th>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>Kpl</th>";
		echo "<td>$laskurow[kpl]</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>Veroton arvo</th>";
		echo "<td>$laskurow[arvo]</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>Verollinen arvo</th>";
		echo "<td>$laskurow[summa]</td>";
		echo "</tr>";

		echo "</table><br>";

		echo "<form method='post'>";
		echo "<input type='hidden' name='tee' value='TULOSTA'>";

		echo "<table>";
		echo "<tr>";
		echo "<th>Tulosta uudestaan siirtolista numero</th>";
		echo "<td><input type='text' name='numero' size='10'></td>";
		echo "<td class='back'> (jättämällä tämä tyhjäksi luodaan aina uusi aineisto lähettämättömistä laskuista)</td>";
		echo "</tr>";
		echo "</table><br>";

		echo "<table><tr><th>Valitse tulostin</th><td>";

		$query = "	SELECT *
					FROM kirjoittimet
					WHERE
					yhtio='$kukarow[yhtio]'
					ORDER by kirjoitin";
		$kirre = mysql_query($query) or pupe_error($query);

		echo "<select name='valittu_tulostin'>";

		while ($kirrow = mysql_fetch_array($kirre)) {

			$sel = "";
			if ($kirrow['tunnus'] == $kukarow['kirjoitin']) {
				$sel = "SELECTED";
			}
			echo "<option value='$kirrow[tunnus]' $sel>$kirrow[kirjoitin]</option>";

		}
		echo "</select></td></tr></table><br>";

		echo "<input type='submit' value='Luo aineisto'>";
		echo "</form>";

	}

	if ($tee == 'TULOSTA') {

		$numero = (int) $numero;

		// haetaan kaikki sampo factoroidut laskut jota ei ole vielä liitetty mihinkään siirtolistalle
		$query = "	SELECT ifnull(group_concat(lasku.tunnus),0) tunnukset
					FROM lasku USE INDEX (factoring)
					JOIN maksuehto ON (maksuehto.yhtio = lasku.yhtio and maksuehto.tunnus = lasku.maksuehto and maksuehto.factoring = 'SAMPO')
					WHERE lasku.yhtio = '$kukarow[yhtio]' and
					lasku.tila = 'U' and
					lasku.alatila = 'X' and
					lasku.summa != 0 and
					lasku.factoringsiirtonumero = '$numero' and
					lasku.valkoodi = '$yhtiorow[valkoodi]'
					order by laskunro";
		$result = mysql_query ($query) or pupe_error($query);
		$laskurow = mysql_fetch_array($result);

		// jos löytyi jotain factoroitavaa tallennetaan ni siirtonumero ekaks laskuille, minimoidaan aikaikkunat ja tablejen lukitusaika
		if ($laskurow["tunnukset"] != 0) {

			// ei olla tulostamassa kopiota
			if ($numero == 0) {
				// lukitaan, ettei muut pääse sörkkimään väliin
				$query  = "	LOCK TABLES lasku WRITE";
				$result = mysql_query ($query) or pupe_error($query);

				// haetaan seuraava vapaa listanumero
				$query = "	SELECT max(factoringsiirtonumero) + 1
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]'";
				$result = mysql_query ($query) or pupe_error($query);
				$facrow = mysql_fetch_array($result);

				// päivitetään se laskuille
				$query = "	UPDATE lasku
							SET factoringsiirtonumero = '$facrow[0]'
							WHERE yhtio = '$kukarow[yhtio]' and tunnus in ($laskurow[tunnukset])";
				$result = mysql_query ($query) or pupe_error($query);

				$numero = $facrow[0];

				// lukko pois
				$query  = "	UNLOCK TABLES";
				$result = mysql_query ($query) or pupe_error($query);
			}

			// sitte käydään vasta laskut läpi..
			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]' and tunnus in ($laskurow[tunnukset])";
			$result = mysql_query ($query) or pupe_error($query);

			// laskurit nollaan
			$hyvitys      = 0;
			$hyvitys_kpl  = 0;
			$veloitus     = 0;
			$veloitus_kpl = 0;
			$kaikki       = 0;
			$kaikki_kpl   = 0;

			$edytunnus    = "";
			$ulos         = "";

			$ulos .= "\n";
			$ulos .= sprintf("%-14.14s", "Y-tunnus");
			$ulos .= sprintf("%-65.65s", "Yhteystiedot");
			$ulos .= "\n";

			$ulos .= sprintf("%-14.14s", "Laskunro");
			$ulos .= sprintf("%-14.14s", "Tapvm");
			$ulos .= sprintf("%-14.14s", "Erpvm");
			$ulos .= sprintf("%-14.14s", "Kapvm");
			$ulos .= sprintf("%20.20s",  "Summa");
			$ulos .= sprintf("%4.4s",    "Val");
			$ulos .= "\n--------------------------------------------------------------------------------";
			$ulos .= "\n";

			while ($laskurow = mysql_fetch_array($result)) {

				if ($edytunnus != $laskurow["ytunnus"]) {
					$ulos .= "\n";
					$ulos .= sprintf("%-14.14s", $laskurow["ytunnus"]);
					$ulos .= sprintf("%-65.65s", $laskurow["nimi"].", ".$laskurow["osoite"].", ".$laskurow["postino"]." ".$laskurow["postitp"]);
					$ulos .= "\n";
				}

				if ($laskurow["kapvm"] == '0000-00-00') $laskurow["kapvm"] = "";

				$ulos .= sprintf("%-14.14s", $laskurow["laskunro"]);
				$ulos .= sprintf("%-14.14s", $laskurow["tapvm"]);
				$ulos .= sprintf("%-14.14s", $laskurow["erpcm"]);
				$ulos .= sprintf("%-14.14s", $laskurow["kapvm"]);
				$ulos .= sprintf("%20.20s",  $laskurow["summa"]);
				$ulos .= sprintf("%4.4s",    $laskurow["valkoodi"]);
				$ulos .= "\n";

				$edytunnus = $laskurow["ytunnus"];

				if ($laskurow["summa"] < 0) {
					$hyvitys += $laskurow["summa"];
					$hyvitys_kpl++;
				}
				else {
					$veloitus += $laskurow["summa"];
					$veloitus_kpl++;
				}

				$kaikki += $laskurow["summa"];
				$kaikki_kpl++;
			}

			// sitte käydään vasta laskut läpi..
			$query  = "	SELECT *
						from factoring
						where yhtio = '$kukarow[yhtio]'
						and factoringyhtio = 'SAMPO'
						and valkoodi = '$yhtiorow[valkoodi]'";
			$result = mysql_query ($query) or pupe_error($query);
			$soprow = mysql_fetch_array($result);

			// vähän siirtolistainfoa ruudulle
			echo "<table>";

			echo "<tr>";
			echo "<th>Sopimusnumero</th>";
			echo "<td>$soprow[sopimusnumero]</td>";
			echo "</tr>";

			echo "<tr>";
			echo "<th>Siirtolistan numero</th>";
			echo "<td>$numero</td>";
			echo "</tr>";

			echo "</table><br>";

			// sitten infoa laskutuksesta ruudulle
			echo "<table>";

			echo "<tr>";
			echo "<th></th>";
			echo "<th>Lukumäärä</th>";
			echo "<th>Summa</th>";
			echo "</tr>";

			echo "<tr>";
			echo "<th>Veloituslaskut</th>";
			echo "<td>$veloitus_kpl</td>";
			echo "<td>$veloitus</td>";
			echo "</tr>";

			echo "<tr>";
			echo "<th>Hyvityslaskut</th>";
			echo "<td>$hyvitys_kpl</td>";
			echo "<td>$hyvitys</td>";
			echo "</tr>";

			echo "<tr>";
			echo "<th>Yhteensä</th>";
			echo "<th>$kaikki_kpl</th>";
			echo "<th>$kaikki</th>";
			echo "</tr>";

			echo "</table>";

			$otsikkoulos  = "\n\n\n";
			$otsikkoulos .= "Sopimusnumero      $soprow[sopimusnumero]\n";
			$otsikkoulos .= "Siirtolistanumero  $numero\n";
			$otsikkoulos .= "\n\n";
			$otsikkoulos .= sprintf("%-15.15s", "Veloituslaskut");
			$otsikkoulos .= sprintf("%10.10s",  "$veloitus_kpl kpl");
			$otsikkoulos .= sprintf("%20.20s",  "$veloitus eur\n");
			$otsikkoulos .= sprintf("%-15.15s", "Hyvityslaskut");
			$otsikkoulos .= sprintf("%10.10s",  "$hyvitys_kpl kpl");
			$otsikkoulos .= sprintf("%20.20s",  "$hyvitys eur\n");
			$otsikkoulos .= sprintf("%-15.15s", "Yhteensä");
			$otsikkoulos .= sprintf("%10.10s",  "$kaikki_kpl kpl");
			$otsikkoulos .= sprintf("%20.20s",  "$kaikki eur\n");
			$otsikkoulos .= "\n\n";

			$query = "	SELECT komento
						FROM kirjoittimet
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus = '$valittu_tulostin'";
			$kirres = mysql_query($query) or pupe_error($query);
			$kirrow = mysql_fetch_assoc($kirres);

			$tempfile1 = tempnam("/tmp", "SAMPOFAC");
			$null = file_put_contents($tempfile1, $otsikkoulos);
			$null = exec("a2ps -o ".$tempfile1.".ps --no-header -R --columns=1 --medium=a4 --chars-per-line=80 --margin=0 --borders=0 $tempfile1");

			if ($kirrow["komento"] == 'email') {

				$subject = "$yhtiorow[nimi] - Danskebankfactoring";
				$liite = array();
				$kutsu = array();
				$ctype = array();

				$liite[0] = $tempfile1.".pdf";
				$kutsu[0] = "Danskebankfactoring-otsikko";
				$ctype[0] = "pdf";

				system("ps2pdf -sPAPERSIZE=a4 $tempfile1.ps $liite[0]");
			}
			else {
				$null = exec("$kirrow[komento] ".$tempfile1.".ps");
			}

			$tempfile2 = tempnam("/tmp", "SAMPOFAC");
			$null = file_put_contents($tempfile2, $ulos);
			$null = exec("a2ps -o ".$tempfile2.".ps --no-header -R --columns=1 --medium=a4 --chars-per-line=80 --margin=0 --borders=0 $tempfile2");

			if ($kirrow["komento"] == 'email') {
				$liite[1] = $tempfile2.".pdf";
				$kutsu[1] = "Danskebankfactoring-laskut";
				$ctype[1] = "pdf";

				system("ps2pdf -sPAPERSIZE=a4 $tempfile2.ps $liite[1]");

				require("inc/sahkoposti.inc");

				unlink($tempfile1.".pdf");
				unlink($tempfile2.".pdf");
			}
			else {
				$null = exec("$kirrow[komento] ".$tempfile2.".ps");
			}

			unlink($tempfile1);
			unlink($tempfile1.".ps");
			unlink($tempfile2);
			unlink($tempfile2.".ps");
		}
		else {
			echo "<font class='message'>Yhtään factoroitavaa laskua ei löytynyt.</font><br><br>";
		}
	}

	require ("inc/footer.inc");
?>