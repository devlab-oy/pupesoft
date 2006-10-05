<?php

	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Korjaa keskihankintahinta")."</font><hr>";

	if ($tee == "KORJAA") {

		// takasin tuotteen valintaan
		$tee = "";

		$uusihinta = (float) str_replace(',','.',$uusi_uusihinta);

		if ($uusihinta != 0 and $rivitunnus != 0) {

			$query   = "select * from tapahtuma where yhtio='$kukarow[yhtio]' and tunnus='$rivitunnus'";
			$res     = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($res) == 1) {

				$taparow = mysql_fetch_array($res);
				// $uusihinta tulee ylhäältä
				$tuoteno	= $taparow["tuoteno"];
				$pvm		= $taparow["laadittu"];
				$rivitunnus = $taparow["rivitunnus"];

				echo "Korjataan tuotteen $tuoteno tapahtumat $pvm ($rivitunnus) lähtien. Uusi ostohinta $uusihinta.<br><br>";

				require("jalkilaskenta.inc");

				// näytetään tapahtumat uudestaan
				$tee = "VALITSE";
			}
			else {
				echo "<font class='error'>Ruksattu rivi katosi!</font><br><br>";
			}
		}
		elseif ($uusihinta == 0) {
			echo "<font class='error'>Virheellinen ostohinta: $uusi_uusihinta</font><br><br>";
		}
		else {
			echo "<font class='error'>Et valinnut kääntöpistettä!</font><br><br>";
		}

	}


	if ($tee == "VALITSE") {

		$query = "select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
		$res = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($res) == 0) {
			echo "<font class='error'>Tuotetta ei löytynyt</font><br><br>";
			$tee = "";
		}
		else {
			$tuoterow = mysql_fetch_array($res);

			// näytetään tuotteen tapahtumat
			$query = "	select * from tapahtuma use index (yhtio_tuote_laadittu)
						where yhtio='$kukarow[yhtio]' and tuoteno='$tuoterow[tuoteno]' and laadittu >= '$yhtiorow[tilikausi_alku]'
						ORDER BY laadittu desc";
			$res = mysql_query($query) or pupe_error($query);

			echo "<form action='$PHP_SELF' method='post'>";
			echo "<input type='hidden' name='tee' value='KORJAA'>";

			echo "Anna tuotteen kääntöpisteen uusi kappaleen ostohinta: ";
			echo "<input type='text' name='uusi_uusihinta' size='5'><br><br>";

			echo "<table>";

			echo "<tr>";
			echo "<th>tuoteno</th>";
			echo "<th>laji</th>";
			echo "<th>kpl</th>";
			echo "<th>kplhinta</th>";
			echo "<th>kehahin</th>";
			echo "<th>selite</th>";
			echo "<th>laatija</th>";
			echo "<th>laadittu</th>";
			echo "<th>kääntöpiste</th>";
			echo "</tr>";

			while ($rivi = mysql_fetch_array($res)) {

				echo "<tr>";
				echo "<td>$rivi[tuoteno]</td>";
				echo "<td>$rivi[laji]</td>";
				echo "<td>$rivi[kpl]</td>";
				echo "<td>$rivi[kplhinta]</td>";
				echo "<td>$rivi[hinta]</td>";
				echo "<td width='300'>$rivi[selite]</td>";
				echo "<td>$rivi[laatija]</td>";
				echo "<td>$rivi[laadittu]</td>";
				echo "<td>";

				if ($rivi["laji"] == "tulo" or $rivi['laji'] == 'valmistus') {
					if ($rivi["rivitunnus"] != 0) {
						echo "<input type='radio' name='rivitunnus' value='$rivi[tunnus]'>";
					}
					else {
						echo "invalid";
					}
				}

				echo "</td>";
				echo "</tr>";
			}

			echo "</table>";
			echo "<br><input type='Submit' value='".t("Korjaa hinnat")."'>";
			echo "</form>";

		}

	}

	// meillä ei ole valittua tilausta
	if ($tee == "") {

		$formi  = "find";
		$kentta = "etsi";

		// tehdään etsi valinta
		echo "<form action='$PHP_SELF' name='find' method='post'>";
		echo t("Etsi tuotenumero").": ";
		echo "<input type='hidden' name='tee' value='VALITSE'>";
		echo "<input type='text' name='tuoteno'>";
		echo "<input type='Submit' value='".t("Etsi")."'>";
		echo "</form>";

	}

	require ("../inc/footer.inc");

?>