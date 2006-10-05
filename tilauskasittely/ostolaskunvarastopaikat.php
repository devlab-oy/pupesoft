<?php
	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Keikan varastopaikat")."</font><hr>";

	if ($otunnus != "") {	//toimittaja ja lasku on valittu. Nyt kohdistetaan, muutetaan ja lisätään rivejä.
		require('ostorivienvarastopaikat.inc');
		if ($tee == "valmis") $tee = "";
		else $tee = "dummy";
	}

	$selectn="";
	$selecty="";

	if ($tee == '') {

		if ($tila == 'N') $selectn=" selected ";
		if ($tila == 'Y') $selecty=" selected ";

		if ($tila=='' or $tila=='N' or $tila=='Y') {
			echo "<br><form name='valinta' action='$PHP_SELF' method='post'>
					<td>".t("Etsi toimittaja").": </td>
					<td><input type = 'text' name = 'nimi' value='$nimi'></td>
					<td><select name='tila'>
					<option value='N' $selectn>".t("Toimittajan nimi")."
					<option value='Y' $selecty>".t("Y-tunnus")."
					</select></td>
					<td><input type='submit' value='".t("Hae")."'></td>
					</tr></table></form>";
			$formi = 'valinta';
			$kentta = 'nimi';
		}

	}

	if ($tee == '') {	//valitaan keikka

		if ($tila == 'N') { // N = nimihaku
			$lisat = " and nimi like '%$nimi%' ";
		}
		elseif ($tila == 'Y') { // Y = yritystunnushaku
			$lisat = " and ytunnus like '$nimi%' ";
		}
		else {
			$lisat="";
		}

		$query = "select lasku.tunnus, laskunro, ytunnus, nimi, sum(varattu) kpl
				from lasku
				left join tilausrivi on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.uusiotunnus=lasku.tunnus)
				where lasku.yhtio='$kukarow[yhtio]' and tila='K' and alatila='' and vanhatunnus=0 $lisat
				group by 1,2,3,4
				having kpl > 0
				order by ytunnus";

		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='message'>".t("Yhtään sopivaa keikkaa ei löytynyt").".</font><br><br>";
		}
		elseif (mysql_num_rows($result) > 100) {
			echo "<font class='message'>".t("Haulla löytyi liikaa keikkoja")." (>100). ".t("Tarkenna hakua").".</font><br><br>";
		}
		else {
			echo "<table>";

			echo "<tr>
					<th>".t("Keikka")."</th>
					<th>".t("Ytunnus")."</th>
					<th>".t("Nimi")."</th>
					<th></th>
				</tr>";

			while ($trow = mysql_fetch_array ($result)) {
				echo "<form action='$PHP_SELF' method='post'>";
				echo "<tr>";
				echo "<input type='hidden' name='otunnus' value='$trow[tunnus]'>";
				echo "<input type='hidden' name='tee' value=''>";
				echo "	<td>$trow[laskunro]</td>
						<td>$trow[ytunnus]</td>
						<td>$trow[nimi]</td>";
				echo "<td><input type='submit' value='".t("Valitse")."'></td>";
				echo "</tr>";
				echo "</form>";
			}

			echo "</table>";
		}
	}
	require ("../inc/footer.inc");
?>
