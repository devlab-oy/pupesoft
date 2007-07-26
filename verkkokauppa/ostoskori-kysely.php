<?php

// online kysely.. näillä infoilla pitäs onnistua
if ($_GET["user"] != "" and $_GET["pass"] != "" and $_GET["yhtio"] != "" and $_GET["ostoskori"] != "") {
	
	require("connect.inc");

	// katotaan löytyykö asiakas
	$query = "select oletus_asiakas from kuka where yhtio='$_GET[yhtio]' and kuka='$_GET[user]' and salasana=md5('$_GET[pass]') and extranet != '' and oletus_asiakas != ''";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 1) {

		$kukarivi = mysql_fetch_array($result);

		// asiakas löytyi, katotaan löytyykö sille ostoskoria $ostoskori
		$query = "	SELECT tilausrivi.*
					FROM lasku use index (yhtio_tila_liitostunnus_tapvm)
					JOIN tilausrivi on (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi = 'B')
					WHERE lasku.yhtio = '$_GET[yhtio]' and
					lasku.tila = 'B' and
					lasku.liitostunnus = '$kukarivi[oletus_asiakas]' and
					lasku.alatila = '$_GET[ostoskori]'";
		$result = mysql_query($query) or pupe_error($query);

		while ($rivit = mysql_fetch_array($result)) {			
			echo sprintf("%-20.20s", $rivit['tuoteno']);
			echo sprintf("%-10.10s", $rivit['varattu']);
			echo sprintf("%-15.15s", $rivit['hinta']);
			echo sprintf("%-35.35s", $rivit['nimitys']);
			echo "\n";
			
			//	Poistetaan rivi
			$query = "	DELETE FROM tilausrivi WHERE yhtio='{$_GET["yhtio"]}' and tyyppi = 'B' and tunnus = '{$rivit["tunnus"]}'";
			$delres = mysql_query($query) or pupe_error($query);
		}
	}
}

?>
