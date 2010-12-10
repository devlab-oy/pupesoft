<?php

	/*
	alter table tiliotedata add column perheid int not null after tieto;
	create index perheid_index on tiliotedata (yhtio, perheid);
	create index aineisto_index on tiliotedata (aineisto);
	*/

	require("inc/connect.inc");

	$query = "	SELECT yhtio, tunnus, left(tieto,3) tietotyyppi
				FROM tiliotedata
				WHERE tyyppi != '3'
				ORDER BY yhtio, tunnus";
	$tiliotedataresult = mysql_query($query) or pupe_error($query);

	$perheid = 0;

	while ($tiliotedatarow = mysql_fetch_array($tiliotedataresult)) {

		if ($tiliotedatarow["tietotyyppi"] != "T11" and $tiliotedatarow["tietotyyppi"] != "T81") {
			$perheid = $tiliotedatarow["tunnus"];
		}

		$query = "	UPDATE tiliotedata SET perheid = $perheid
					WHERE tunnus = $tiliotedatarow[tunnus]";
		$kuitetaan_result = mysql_query($query) or pupe_error($query);
	}

	$query = "	SELECT yhtio, tunnus, kuitattu, kuitattuaika
				FROM tiliotedata
				WHERE kuitattu != ''
				and tunnus = perheid";
	$tiliotedataresult = mysql_query($query) or pupe_error($query);

	while ($tiliotedatarow = mysql_fetch_array($tiliotedataresult)) {

		$query = "	UPDATE tiliotedata
					SET kuitattu = '$tiliotedatarow[kuitattu]',
					kuitattuaika = '$tiliotedatarow[kuitattuaika]'
					WHERE yhtio = '$tiliotedatarow[yhtio]'
					and perheid = $tiliotedatarow[tunnus]";
		$kuitetaan_result = mysql_query($query) or pupe_error($query);
	}

?>