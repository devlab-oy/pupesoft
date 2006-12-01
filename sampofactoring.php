<?php
	require('inc/parametrit.inc');

	echo "<font class='head'>Sampo Factoring</font><hr><br>";

	if ($tee == "") {

		echo "<font class='message'>Luodaan Sampo Factoring siirtolista kaikista lähettämättömistä factoring laskuista.</font><br><br>";

		// haetaan kaikki sampo factoroidut laskut jota ei ole vielä liitetty mihinkään siirtolistalle
		$query = "	SELECT count(*) kpl, sum(arvo) arvo, sum(summa) summa
					FROM lasku USE INDEX (yhtio_tila_mapvm)
					JOIN maksuehto ON (maksuehto.yhtio = lasku.yhtio and maksuehto.tunnus = lasku.maksuehto and maksuehto.factoring = 'A')
					WHERE lasku.yhtio = '$kukarow[yhtio]' and
					lasku.tila = 'U' and
					lasku.alatila = 'X' and
					lasku.summa != 0 and
					lasku.mapvm = '0000-00-00' and
					lasku.sisainen = '' and
					lasku.factoringsiirtonumero = ''";
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

		echo "<form method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='tee' value='TULOSTA'>";

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

		// haetaan kaikki sampo factoroidut laskut jota ei ole vielä liitetty mihinkään siirtolistalle
		$query = "	SELECT ifnull(group_concat(lasku.tunnus),0) tunnukset
					FROM lasku USE INDEX (yhtio_tila_mapvm)
					JOIN maksuehto ON (maksuehto.yhtio = lasku.yhtio and maksuehto.tunnus = lasku.maksuehto and maksuehto.factoring = 'A')
					WHERE lasku.yhtio = '$kukarow[yhtio]' and
					lasku.tila = 'U' and
					lasku.alatila = 'X' and
					lasku.summa != 0 and
					lasku.sisainen = '' and
					lasku.mapvm = '0000-00-00' and
					lasku.factoringsiirtonumero = ''
					order by laskunro";
		$result = mysql_query ($query) or pupe_error($query);
		$laskurow = mysql_fetch_array($result);

		// jos löytyi jotain factoroitavaa tallennetaan ni siirtonumero ekaks laskuille, minimoidaan aikaikkunat ja tablejen lukitusaika
		if ($laskurow["tunnukset"] != 0) {

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

			// lukko pois
			$query  = "	UNLOCK TABLES";
			$result = mysql_query ($query) or pupe_error($query);

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
			$ulos .= sprintf("%-15.15s", "Y-tunnus");
			$ulos .= sprintf("%-65.65s", "Yhteystiedot");
			$ulos .= "\n";

			$ulos .= sprintf("%-15.15s", "Laskunro");
			$ulos .= sprintf("%-15.15s", "Tapvm");
			$ulos .= sprintf("%-15.15s", "Erpvm");
			$ulos .= sprintf("%20.20s",  "Summa");
			$ulos .= sprintf("%4.4s",    "Val");
			$ulos .= "\n--------------------------------------------------------------------------------";
			$ulos .= "\n";

			while ($laskurow = mysql_fetch_array($result)) {

				if ($edytunnus != $laskurow["ytunnus"]) {
					$ulos .= "\n";
					$ulos .= sprintf("%-15.15s", $laskurow["ytunnus"]);
					$ulos .= sprintf("%-65.65s", $laskurow["nimi"].", ".$laskurow["osoite"].", ".$laskurow["postino"]." ".$laskurow["postitp"]);
					$ulos .= "\n";
				}

				$ulos .= sprintf("%-15.15s", $laskurow["laskunro"]);
				$ulos .= sprintf("%-15.15s", $laskurow["tapvm"]);
				$ulos .= sprintf("%-15.15s", $laskurow["erpcm"]);
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

			// vähän siirtolistainfoa ruudulle
			echo "<table>";

			echo "<tr>";
			echo "<th>Sopimusnumero</th>";
			echo "<td>$yhtiorow[factoring_sopimus]</td>";
			echo "</tr>";

			echo "<tr>";
			echo "<th>Siirtolistan numero</th>";
			echo "<td>$facrow[0]</td>";
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
			$otsikkoulos .= "Sopimusnumero      $yhtiorow[factoring_sopimus]\n";
			$otsikkoulos .= "Siirtolistanumero  $facrow[0]\n";
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

			lpr($otsikkoulos, $valittu_tulostin);
			lpr($ulos, $valittu_tulostin);

		}
		else {
			echo "<font class='message'>Yhtään factoroitavaa laskua ei löytynyt.</font><br><br>";
		}
	}

	require ("inc/footer.inc");
?>
