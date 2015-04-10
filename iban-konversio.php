<?php

	require ("inc/connect.inc");
	require ("inc/functions.inc");

	$laskuri = 0;

	$query = "	SELECT ultilno, swift, tilinumero, tunnus
				FROM toimi
				WHERE ultilno = ''
				AND tilinumero not in ('', 0)";
	$result = mysql_query($query) or pupe_error($query);

	while ($toimirow = mysql_fetch_array($result)) {

		$vastaus = luoiban(preg_replace("/[^0-9]/", "", $toimirow["tilinumero"]));
		$iban = trim($vastaus["iban"]);
		$bic = trim($vastaus["swift"]);

		if (tarkista_iban($iban) != "" and $bic != '') {
			$query = "	UPDATE toimi SET
						ultilno = '$iban',
						swift = '$bic'
						WHERE tunnus = '$toimirow[tunnus]'";
			$update = mysql_query($query) or pupe_error($query);
			$laskuri++;
		}
	}

	$query = "	SELECT tilino, iban, bic, tunnus
				FROM yriti
				WHERE iban = ''";
	$result = mysql_query($query) or pupe_error($query);

	while ($toimirow = mysql_fetch_array($result)) {

		$vastaus = luoiban(preg_replace("/[^0-9]/", "", $toimirow["tilino"]));
		$iban = trim($vastaus["iban"]);
		$bic = trim($vastaus["swift"]);

		if (tarkista_iban($iban) != "" and $bic != '') {
			$query = "	UPDATE yriti SET
						iban = '$iban',
						bic = '$bic'
						WHERE tunnus = '$toimirow[tunnus]'";
			$update = mysql_query($query) or pupe_error($query);
			$laskuri++;
		}
	}

	$query = "	SELECT ultilno, swift, tilinumero, tunnus
				FROM lasku
				WHERE ultilno = ''
				AND tilinumero not in ('', 0)
				AND tila in ('H','M','P')";
	$result = mysql_query($query) or pupe_error($query);

	while ($toimirow = mysql_fetch_array($result)) {

		$vastaus = luoiban(preg_replace("/[^0-9]/", "", $toimirow["tilinumero"]));
		$iban = trim($vastaus["iban"]);
		$bic = trim($vastaus["swift"]);

		if (tarkista_iban($iban) != "" and $bic != '') {
			$query = "	UPDATE lasku SET
						ultilno = '$iban',
						swift = '$bic'
						WHERE tunnus = '$toimirow[tunnus]'";
			$update = mysql_query($query) or pupe_error($query);
			$laskuri++;
		}
	}

	echo "\nPaivitettiin $laskuri rivia\n\n";

?>