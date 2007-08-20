<?php
		if ($trow['tila'] == 'U') {
			$query = "	SELECT nimitys, tuoteno, tilkpl tilattu, kpl toimitettu, jt jälkitoimitus, hinta, ale, rivihinta, if(alv>=500, '".t("M.V.")."', alv) alv, toimaika, kerayspvm
						FROM tilausrivi
						WHERE uusiotunnus = '$tunnus' and yhtio='$kukarow[yhtio]'
						ORDER BY toimaika, tunnus";
		}
		//nämä ovat ostoreskontran laskuja ja sillon pitää löytää keikan perusteella rivit
		elseif ($trow['tila'] == 'Y' or $trow['tila'] == 'M' or $trow['tila'] == 'P') {
			$query = "	SELECT laskunro
						FROM lasku
						WHERE vanhatunnus = '$tunnus' and yhtio='$kukarow[yhtio]' and tila = 'K' limit 1";
			$laskresult = mysql_query($query) or pupe_error($query);
			$laskrow = mysql_fetch_array($laskresult);
			
			$query = "	SELECT nimitys Nimitys, tuoteno Tuotenumero, kpl, yksikko Yksikkö, tilausrivi.hinta Hinta, rivihinta Rivihinta, tilausrivi.laatija Laatija, otunnus Tilaukselta
						FROM lasku, tilausrivi
						WHERE lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.uusiotunnus and lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila = 'K' and lasku.vanhatunnus = 0 and laskunro = '$laskrow[laskunro]'
						ORDER BY tilausrivi.toimaika, tilausrivi.tunnus";
		}
		else {
			$query = "	SELECT nimitys, tuoteno, tilkpl tilattu, varattu toimitettu, jt jälkitoimitus, hinta, ale, round((varattu+jt)*hinta*(1-(ale/100)),2) rivihinta, if(alv>=500, '".t("M.V.")."', alv) alv, toimaika, kerayspvm
						FROM tilausrivi
						WHERE otunnus = '$tunnus' and yhtio='$kukarow[yhtio]'
						ORDER BY toimaika, tunnus";	
		}
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {

			echo "<table>";

			for ($i = 0; $i < mysql_num_fields($result); $i++) {
				echo "<th>" . t(mysql_field_name($result,$i)) ."</th>";
			}
			echo "</tr>";

			while ($trow=mysql_fetch_array ($result)) {
				echo "<tr>";
				for ($i=0; $i<mysql_num_fields($result); $i++) {
					echo "<td>$trow[$i]</td>";
				}
				echo "</tr>";
			}
			echo "</table><br><br>";
		}
		else {
			echo "<br><br><b>".t("Laskulla ei ole (vielä) tuoterivejä")."</b><br><br>";
		}
?>
