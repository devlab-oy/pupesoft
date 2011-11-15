<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Laskuttamattomat vientitilausket")."</font><hr>";

	if ($tee == 'NAYTATILAUS') {

			echo "<font class='head'>Tilausnro: $tunnus</font><hr>";

			require ("naytatilaus.inc");

			echo "<br><br><br>";

			$tee = "";

	}

	if ($tee == '') {

		$query_ale_lisa = generoi_alekentta('M');

		//listataan tuoreet tilausket
		$query = "	SELECT lasku.tunnus tilaus, lasku.vienti, lasku.luontiaika laadittu, lasku.laatija, lasku.ytunnus, lasku.nimi, lasku.nimitark, round(sum(varattu * tilausrivi.hinta * {$query_ale_lisa}), 2) summa
					FROM lasku, tilausrivi
					WHERE lasku.yhtio='$kukarow[yhtio]'
					AND lasku.yhtio=tilausrivi.yhtio
					AND lasku.tunnus=tilausrivi.otunnus
					AND lasku.tila='L'
					AND lasku.alatila in ('B','D','E','A','C')
					AND lasku.vienti in ('K','E')
					GROUP by 1,2,3,4,5,6,7
					ORDER by 1,2,3,4,5,6,7";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table><tr>";
		echo "<th>".t("Tilaus")."</th>";
		echo "<th>".t("Vienti")."</th>";
		echo "<th>".t("Ytunnus")."</th>";
		echo "<th>".t("Nimi")."</th>";
		echo "<th>".t("Nimitark")."</th>";
		echo "<th>".t("Laadittu")."</th>";
		echo "<th>".t("Laatija")."</th>";
		echo "<th>".t("Summa")."</th>";

		echo "</tr>";

		$yht = 0;

		while ($tulrow = mysql_fetch_assoc($result)) {

			echo "<tr>";
			echo "<td><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$tulrow[tilaus]'>$tulrow[tilaus]</a></td>";
			echo "<td>$tulrow[vienti]</td>";
			echo "<td>$tulrow[ytunnus]</td>";
			echo "<td>$tulrow[nimi]</td>";
			echo "<td>$tulrow[nimitark]</td>";
			echo "<td>$tulrow[laadittu]</td>";
			echo "<td>$tulrow[laatija]</td>";
			echo "<td>$tulrow[summa]</td>";
			echo "</tr>";

			$yht += $tulrow["summa"];
		}

		echo "<tr>";
		echo "<td class='back' colspan='6'></td>";
		echo "<th>".t("Yhteensä")."</th>";
		echo "<th>$yht</th>";
		echo "</tr>";
		echo "</table>";
	}

	require ("../inc/footer.inc");
