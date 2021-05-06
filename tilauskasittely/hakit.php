<?php

	if (@include("../inc/parametrit.inc"));
elseif (@include("parametrit.inc"));
else exit;

	echo "<font class='head'>Häkki- ja lava-arvio:<hr></font>";

	// sallitaan vain numerot 0-9
	$tunnus = ereg_replace("[^0-9]", "", $tunnus);
	

	

	if ($tunnus != "" and $tee == "valitse") {

		$tila_query  = "	SELECT *
							FROM lasku
							WHERE tunnus = '$tunnus' and tila='L' ";
		$tila_result = mysql_query($tila_query) or pupe_error($tila_query);

		if (mysql_num_rows($tila_result) == 1) {

			$hakquery = "	SELECT SUM( IF( tuotekorkeus =1, tuotesyvyys * tilkpl, '' ) ) AS Hakit , SUM( IF( tuotekorkeus = 2, tuotesyvyys * tilkpl, '' ) ) AS Lavat
								FROM tilausrivi , tuote
								WHERE tilausrivi.tuoteno = tuote.tuoteno
								AND otunnus = '$tunnus'	AND tyyppi = 'L'";
				$hakresult = mysql_query ( $hakquery ) or pupe_error ( $hakquery );
				$apukukarow = mysql_fetch_assoc ( $hakresult );
				$hakkia= $apukukarow["Hakit"];
				$lavaa= $apukukarow["Lavat"];
				echo "<tr>
						<td class='head' colspan='" . ($sarakkeet_alku - 5) . "'>&nbsp;</td>
						<th colspan='5' align='right'>" . $tila_result["nimi"] . ":</th>";
				echo "<tr>$jarjlisa
						<td class='head' colspan='" . ($sarakkeet_alku - 5) . "'>&nbsp;</td>
						<th colspan='5' align='right'>Häkit arvio: </th>";
				echo "<td class='spec' align='right'> " . sprintf ( "%.1f", $hakkia ) . "</td>";
				echo "<tr>$jarjlisa
						<td class='head' colspan='" . ($sarakkeet_alku - 5) . "'>&nbsp;</td>
						<th colspan='5' align='right'>" . t ( "Lavat arvio" ) . ": </th>";
				echo "<td class='spec' align='right'> " . sprintf ( "%.1f", $lavaa ) . "</td>";
		}
		else {
			echo "<font class='error'>".t("Tilausta ei löydy")."!</font>";
			$tee = "";
		}

	}

	if ($tee == "") {
		echo "<form method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='tee' value='valitse'>";
		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Anna tilausnumero").":</th>";
		echo "<td><input type='text' name='tunnus'></td>";
		echo "<td class='back'><input type='submit' value='".t("Hae")."'></td>";
		echo "</tr>";
		echo "</table>";
		echo "</form>";
		#$tee == "valitse"
	}

	require ("../inc/footer.inc");

?>
