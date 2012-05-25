<?php

	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Tulosseuranta")."</font><hr>";

	# alter table tili add column tulosseuranta_taso int not null after sisainen_taso;

	// Haetaan kaikki tulosseurannan tasot sekä katsotaan löytyykö niille kirjauksia
	$query = "	SELECT taso.taso, taso.nimi, taso.summattava_taso, round(ifnull(sum(tiliointi.summa), taso.oletusarvo)) summa
				FROM taso
				LEFT JOIN tili ON (tili.yhtio = taso.yhtio
					AND tili.tulosseuranta_taso = taso.taso)
				LEFT JOIN tiliointi ON (tiliointi.yhtio = tili.yhtio
					AND tiliointi.tilino = tili.tilino
					AND tiliointi.tapvm >= '2012-05-01'
					AND tiliointi.tapvm <= '2012-05-31'
					AND tiliointi.korjattu = '')
				WHERE taso.yhtio = '{$kukarow['yhtio']}'
				AND taso.tyyppi = 'B'
				GROUP BY taso.taso, taso.nimi, taso.summattava_taso
				ORDER BY taso.taso";
	$result = pupe_query($query);

	$tulosseuranta = array();

	while ($row = mysql_fetch_assoc($result)) {
		$tulosseuranta[$row["taso"]]["summa"] = $row["summa"];
		$tulosseuranta[$row["taso"]]["nimi"] = $row["nimi"];
		$tulosseuranta[$row["taso"]]["summattava_taso"] = $row["summattava_taso"];
	}

	if (count($tulosseuranta) > 0) {

		echo "<table>";

		echo "<tr>";
		echo "<th>Taso</th>";
		echo "<th>Nimi</th>";
		echo "<th>Summa</th>";
		echo "</tr>";

		foreach ($tulosseuranta as $taso => $row) {
			echo "<tr>";
			echo "<td>{$taso}</td>";
			echo "<td>{$row["nimi"]}</td>";
			echo "<td>{$row["summa"]}</td>";
			echo "</tr>";
		}

		echo "</table>";

	}
