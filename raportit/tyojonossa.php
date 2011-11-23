<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Työjono-raportti")."</font><hr>";

	// Hoidetaan parametri kuntoon
	$peruste = isset($peruste) ? $peruste : "tyojono";
	$sel = ($peruste == "suorittaja") ? "SELECTED" : "";

	echo "<form action='$PHP_SELF' name='vaihdaPeruste' method='POST'>";
	echo "<table>";
	echo "<tr>";
	echo "<th>".t("Näytä työmääräykset")."</th>";
	echo "<td><select name='peruste' onchange='submit()'>";
	echo "<option value='tyojono'>".t("Työjonottain")."</option>";
	echo "<option value='suorittaja' $sel>".t("Suorittajittain")."</option>";
	echo "</select></td>";
	echo "</tr>";
	echo "</table>";
	echo "</form>";

	echo "<br>";
	echo "<table>";

	if ($peruste == "suorittaja") {
		$query = "	SELECT
					tyomaarays.suorittaja,
					tyomaarays.tyostatus,
					ifnull(a3.nimi, 'tuntematon') tyojono1,
					ifnull(a2.selitetark, 'tuntematon') tyostatus1,
					count(*) maara
					FROM lasku
					JOIN tyomaarays ON (tyomaarays.yhtio = lasku.yhtio and tyomaarays.otunnus=lasku.tunnus and tyomaarays.tyojono != '')
					LEFT JOIN avainsana a1 ON (a1.yhtio = tyomaarays.yhtio and a1.laji = 'TYOM_TYOJONO' and a1.selite = tyomaarays.tyojono )
					LEFT JOIN avainsana a2 ON (a2.yhtio = tyomaarays.yhtio and a2.laji = 'TYOM_TYOSTATUS' and a2.selite = tyomaarays.tyostatus)
					LEFT JOIN kuka a3 ON (a3.yhtio = tyomaarays.yhtio and a3.kuka = tyomaarays.suorittaja)
					WHERE lasku.yhtio = '{$kukarow["yhtio"]}'
					AND lasku.tila in ('A','L','N','S','C')
					AND lasku.alatila != 'X'
					GROUP BY 1,2,3,4
					ORDER BY suorittaja, tyostatus ASC";
	}
	else {
		$query = "	SELECT
					tyomaarays.tyojono,
					tyomaarays.tyostatus,
					a1.selitetark tyojono1,
					ifnull(a2.selitetark, 'tuntematon') tyostatus1,
					count(*) maara
					FROM lasku
					JOIN tyomaarays ON (tyomaarays.yhtio = lasku.yhtio and tyomaarays.otunnus = lasku.tunnus and tyomaarays.tyojono != '')
					LEFT JOIN avainsana a1 ON (a1.yhtio = tyomaarays.yhtio and a1.laji = 'TYOM_TYOJONO' and a1.selite = tyomaarays.tyojono)
					LEFT JOIN avainsana a2 ON (a2.yhtio = tyomaarays.yhtio and a2.laji = 'TYOM_TYOSTATUS' and a2.selite = tyomaarays.tyostatus)
					WHERE lasku.yhtio = '{$kukarow["yhtio"]}'
					AND lasku.tila in ('A','L','N','S','C')
					AND lasku.alatila != 'X'
					GROUP BY 1,2,3,4
					ORDER BY tyojono, tyostatus ASC";
	}
	$ekares = pupe_query($query);

	$vaihdajono = "";
	$jonosumma = 0;

	echo "<tr>";
	echo "<th>".t($peruste)."</th>";
	echo "<th>".t("Työstatus")."</th>";
	echo "<th>".t("Määrä")."</th>";
	echo "</tr>";

	while ($rivit = mysql_fetch_assoc($ekares)) {

		if ($vaihdajono != $rivit["tyojono1"] and $vaihdajono != "") {
			echo "<tr><th colspan='2'>".t("Yhteensä").":</th><th>$jonosumma</th></tr>";
			echo "<tr><td class='back' colspan='3'><br></td></tr>";
			$jonosumma = 0;
		}

		echo "<tr class='aktiivi'>";

		if ($vaihdajono != $rivit["tyojono1"]) {

			if ($peruste == "tyojono") {
				echo "<td><a href='{$palvelin2}tyomaarays/tyojono.php?tyojono_haku={$rivit["tyojono1"]}&lopetus={$palvelin2}raportit/tyojonossa.php////peruste=$peruste//tee=K'>{$rivit["tyojono1"]}</a></td>";
			}
			else {
				$linkkihaku = urlencode($rivit["tyojono1"]);
				echo "<td><a href='{$palvelin2}tyomaarays/tyojono.php?linkkihaku=$linkkihaku&lopetus={$palvelin2}raportit/tyojonossa.php////peruste=$peruste//tee=K''>{$rivit["tyojono1"]}</a></td>";
			}
		}
		else {
			echo "<td></td>";
		}

		echo "<td>{$rivit["tyostatus1"]} </td>";
		echo "<td>{$rivit["maara"]} </td>";

		echo "</tr>";

		$jonosumma += $rivit["maara"];
		$vaihdajono = $rivit["tyojono1"];
	}

	echo "<tr><th colspan='2'>".t("Yhteensä").":</th><th>$jonosumma</th></tr>";
	echo "</table>";

	require ("inc/footer.inc");
