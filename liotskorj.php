<?php

	// Kutsutaanko CLI:stä
	if (php_sapi_name() != 'cli') {
		die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
	}

	// otetaan tietokanta connect
	require ("inc/connect.inc");
	require ("inc/functions.inc");

	$query  = "	SELECT lasku.tunnus,
				liitos.tunnus litunnus,
				lasku.summa laskusumma,
				liitos.summa lisumma,
				liitos.arvo liarvo,
				sum(tiliointi.summa) alvit
				FROM lasku
				LEFT JOIN tiliointi USE INDEX (tositerivit_index) ON (tiliointi.yhtio = lasku.yhtio and tiliointi.ltunnus = lasku.tunnus and tiliointi.tilino = (select alv from yhtio where yhtio.yhtio=tiliointi.yhtio LIMIT 1) and tiliointi.korjattu = '' AND if(lasku.summa > 0, tiliointi.summa, tiliointi.summa*-1) > 0)
				JOIN lasku AS liitos ON liitos.yhtio = lasku.yhtio AND liitos.vanhatunnus = lasku.tunnus AND liitos.tila = 'K'
				WHERE lasku.yhtio IN (select yhtio from yhtio)
				AND lasku.tila IN ('H','Y','M','P','Q')
				AND lasku.vienti = 'B'
				GROUP BY 1,2,3,4,5
				ORDER BY 1;";
	$laskures = mysql_query($query) or pupe_error($query);

	while ($laskurow = mysql_fetch_assoc($laskures)) {

		$arvo 	  = $laskurow["laskusumma"]-$laskurow["alvit"];
		$liipros  = $laskurow["lisumma"] / $laskurow["laskusumma"];
		$sumarvio = round($laskurow["laskusumma"] * $liipros, 2);
		$arvarvio = round($arvo * $liipros, 2);

		if ($laskurow["lisumma"] != $sumarvio or $laskurow["liarvo"] != $arvarvio) {

			$query = " UPDATE lasku SET summa=$sumarvio, arvo=$arvarvio, muuttaja='liitosko' where tunnus={$laskurow["litunnus"]} and muuttaja!='liitosko'";
			$result = mysql_query($query) or pupe_error($query);

			echo "Paivitetaan lasku: {$laskurow["litunnus"]}, ",mysql_affected_rows(),"\n";
		}
	}