<?php
	require("inc/connect.inc");

	$query = "	SELECT * FROM avainsana JOIN yhtio ON (yhtio.yhtio = avainsana.yhtio) WHERE avainsana.laji='KASSA'";
	$result = mysql_query($query) or die("$query\n");

	$i = 1;

	while ($row = mysql_fetch_array($result)) {
		echo "$i.\n";

		$kassa = "";

		if ($row["selitetark_2"] == "") {
			$kassa = $row["kassa"];
		}
		else {
			$kassa = $row["selitetark_2"];
		}

		$update = "INSERT INTO kassalipas SET yhtio='$row[yhtio]', nimi='$row[selitetark]', kassa='$kassa', pankkikortti='$row[pankkikortti]', luottokortti='$row[luottokortti]', kateistilitys='$row[kateistilitys]', kassaerotus='$row[kassaerotus]', laatija='sami', luontiaika=now()";
		echo "$update\n";
		$updateres = mysql_query($update) or die("$update\n");
		
		$kassalipas_id = mysql_insert_id();
		
		$update_kuka = "UPDATE kuka SET kassamyyja='$kassalipas_id' WHERE yhtio='$row[yhtio]' AND kassamyyja != '' AND kassamyyja = '$row[selite]'";
		echo "$update_kuka\n";
		$kukares = mysql_query($update_kuka) or die("$update_kuka\n");
		
		$update_lasku = "UPDATE lasku SET kassalipas='$kassalipas_id' WHERE yhtio='$row[yhtio]' AND kassalipas != '' AND kassalipas = '$row[selite]' AND lasku.tila = 'U' AND lasku.alatila = 'X'";
		echo "$update_lasku\n\n";
		$laskures = mysql_query($update_lasku) or die("$update_lasku\n");

		$i++;
	}
?>