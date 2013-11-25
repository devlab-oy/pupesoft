<?php

	/*
	CREATE TABLE pakkaus (
	       yhtio VARCHAR(5) NOT NULL,
	       pakkaus VARCHAR(50) NOT NULL,
	       pakkauskuvaus VARCHAR(50) NOT NULL,
	       pakkausveloitus_tuotenumero VARCHAR(30) NOT NULL,
	       erikoispakkaus CHAR(1) NOT NULL,
	       korkeus DECIMAL(10,4) NOT NULL,
	       leveys DECIMAL(10,4) NOT NULL,
	       syvyys DECIMAL(10,4) NOT NULL,
	       paino DECIMAL(12,4) NOT NULL,
	       jarjestys INT(11) NOT NULL,
	       laatija VARCHAR(10) NOT NULL,
	       luontiaika DATETIME NOT NULL,
	       muutospvm DATETIME NOT NULL,
	       muuttaja VARCHAR(10) NOT NULL,
	       tunnus INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY
	);

	CREATE INDEX yhtio_pakkaus_pakkauskuvaus ON pakkaus (yhtio, pakkaus,pakkauskuvaus);

	*/

	require("inc/connect.inc");

	$query = "	SELECT *
				FROM avainsana
				WHERE laji = 'PAKKAUS'
				ORDER BY yhtio, tunnus";
	$tiliotedataresult = pupe_query($query);

	while ($asrow = mysql_fetch_array($tiliotedataresult)) {

		$erikois = "";

		if ($asrow["Selitetark_3"] != "") {
			$erikois = "E";
		}

		$query = "	INSERT INTO pakkaus SET
					yhtio = '$asrow[yhtio]',
				    pakkaus = '$asrow[selite]',
				    pakkauskuvaus = '$asrow[selitetark]',
				    pakkausveloitus_tuotenumero = '$asrow[selitetark_2]',
				    erikoispakkaus = '$erikois',
				    korkeus = '',
				    leveys = '',
				    syvyys = '',
				    paino = '',
				    jarjestys = '$asrow[jarjestys]',
				    laatija = '$asrow[laatija]',
				    luontiaika = '$asrow[luontiaika]',
				    muutospvm = '$asrow[muutospvm]',
				    muuttaja = '$asrow[muuttaja]'";
		$pakkausres = pupe_query($query);

		$query = "	DELETE
					FROM avainsana
					WHERE tunnus = '$asrow[tunnus]'";
		$pakkausres = pupe_query($query);

	}
	
	$query = "	DELETE
				FROM oikeu
				WHERE nimi = 'yllapito.php'
				and alanimi = 'pakkaus'";
	$pakkausres = pupe_query($query);

	$query = "	INSERT into oikeu (kuka,sovellus,nimi,alanimi,paivitys,lukittu,nimitys,jarjestys,jarjestys2,profiili,yhtio,hidden)
				select kuka,'Varasto',nimi,'pakkaus',paivitys,lukittu,'Pakkaustiedot','295','0',profiili,yhtio,''
				from oikeu o2 where o2.nimi='yllapito.php' and o2.alanimi='avainsana' on duplicate key update oikeu.yhtio=o2.yhtio;";
	$pakkausres = pupe_query($query);

?>