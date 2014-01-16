<?php

	// Kutsutaanko CLI:stä
	if (php_sapi_name() != 'cli') {
		die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
	}

	require ("inc/connect.inc");
	require ("inc/functions.inc");

	$xml = @simplexml_load_file("http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml");

	if ($xml !== FALSE) {

		$pvm = tv1dateconv($xml->Cube->Cube->attributes()->time);
		$pvm_mysql = $xml->Cube->Cube->attributes()->time;

		foreach ($xml->Cube->Cube->Cube as $valuutta) {

			$valkoodi = (string) $valuutta->attributes()->currency;
			$kurssi   = (float)  $valuutta->attributes()->rate;

	    	$query = "	UPDATE valuu, yhtio SET
						valuu.kurssi = round(1 / $kurssi, 9),
						valuu.updated_at = now(),
						valuu.updated_by = 'crond'
						WHERE valuu.nimi = '$valkoodi'
						AND yhtio.yhtio = valuu.yhtio
						AND yhtio.valkoodi = 'EUR'";
			$result = mysql_query($query) or pupe_error($query);

			$query = "	INSERT INTO valuu_historia (kotivaluutta, valuutta, kurssi, kurssipvm)
						VALUES ('EUR', '$valkoodi', round(1 / $kurssi, 9), '$pvm_mysql')
			  			ON DUPLICATE KEY UPDATE kurssi = round(1 / $kurssi, 9)";
			$result = mysql_query($query) or pupe_error($query);
		}

		echo date("d.m.Y @ G:i:s").": Eurokurssit päivitetty $pvm\n\n";
	}
	else {
		echo date("d.m.Y @ G:i:s").": Valuuttakurssien haku epäonnistui!\n\n";
	}

?>