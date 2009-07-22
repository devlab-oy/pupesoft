<?php

	if ($argc == 0) die ("Tätä scriptiä voi ajaa vain komentoriviltä!");

	require ("inc/connect.inc");
	require ("inc/functions.inc");

	$xml = @simplexml_load_file("http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml");

	if ($xml !== FALSE) {

		$pvm = tv1dateconv($xml->Cube->Cube->attributes()->time);
		$pvm_mysql = $xml->Cube->Cube->attributes()->time;

		foreach ($xml->Cube->Cube->Cube as $valuutta) {

			$valkoodi = (string) $valuutta->attributes()->currency;
			$kurssi   = (float)  $valuutta->attributes()->rate;

			// echo "$valkoodi ".sprintf("%.9f", (1/$kurssi))." ($kurssi)\n";

	    	$query = "	UPDATE valuu, yhtio SET
						valuu.kurssi = round(1 / $kurssi, 9),
						valuu.muutospvm = now(),
						valuu.muuttaja = 'crond'
						WHERE valuu.nimi = '$valkoodi'
						AND yhtio.yhtio = valuu.yhtio
						AND yhtio.valkoodi = 'EUR'";
			$result = mysql_query($query) or pupe_error($query);

			$query = "	INSERT INTO valuu_historia (kotivaluutta, valuutta, kurssi, kurssipvm)
						VALUES ('EUR', '$valkoodi', round(1 / $kurssi, 9), '$pvm_mysql')
			  			ON DUPLICATE KEY UPDATE kurssi = round(1 / $kurssi, 9)";
			$result = mysql_query($query) or pupe_error($query);
		}

		echo date("H:i:s").": Eurokurssit päivitetty $pvm\n\n";
	}
	else {
		echo "Valuuttakurssien haku epäonnistui!\n\n";
	}

?>