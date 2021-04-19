<?php

require("../inc/parametrit.inc");

echo "<br><font class='head'>".t("Tiliöintien tilit")."</font><hr><br>";

$query = "	SELECT distinct tili.tilino t, tiliointi.tilino
			FROM tiliointi
			LEFT JOIN tili ON tili.yhtio=tiliointi.yhtio and tili.tilino=tiliointi.tilino
			WHERE tiliointi.yhtio = '$kukarow[yhtio]'
			and tiliointi.korjattu = ''
			and tiliointi.tilino > 0
			HAVING t IS NULL";
$result = mysql_query($query) or pupe_error($query);

while ($tili = mysql_fetch_array($result)) {
	$query = "	SELECT tapvm viimeisin, selite
				FROM tiliointi
				WHERE yhtio		= '$kukarow[yhtio]'
				and tilino 		= $tili[tilino]
				and korjattu	= ''
				ORDER BY tapvm asc
				LIMIT 1";
	$res = mysql_query($query) or pupe_error($query);
	$viimrow = mysql_fetch_array($res);

	echo "<br><font class='error'>Tiliä $tili[tilino] ei ole enää olemassa! Viimeisin tiliöinti $viimrow[viimeisin], $viimrow[selite]</font><br>";
}

$tables	= array("asiakas", "tuote", "toimi");
$columnit = array("tilino", "tilino_eu", "tilino_ei_eu");

$query = "SHOW TABLES";
$result = mysql_query($query) or pupe_error($query);

while($table = mysql_fetch_array($result)) {

	if (in_array($table[0], $tables)) {

		echo "<br><font class='message'>Tarkastetaan taulu $table[0]</font><br>";

		$query = "	SHOW columns FROM $table[0]";
		$res = mysql_query($query) or pupe_error($query);

		while($col = mysql_fetch_array($res)) {
			if (in_array($col[0], $columnit)) {
				foreach ($columnit as $c) {
					$query = "	SELECT $c
								FROM $table[0]
								WHERE yhtio = '$kukarow[yhtio]'
								and $c != ''";
					$haku = mysql_query($query);

					while ($row = mysql_fetch_array($haku)) {
						$query = "	SELECT tunnus
									FROM tili
									WHERE yhtio = '$kukarow[yhtio]' and tilino = '".$row[$c]."'";
						$tarkresr = mysql_query($query) or pupe_error($query);

						if(mysql_num_rows($tarkresr) == 0) {
							echo "<font class='error'>[$c] Tiliä ei löydy '".$row[$c]."'</font><br>";
						}
					}
				}
			}
		}
	}
}

require("../inc/footer.inc");

?>