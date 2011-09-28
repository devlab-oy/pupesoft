<?php

	// UPDATE-scripti ruotsinkielisten avainsanojen konvertoimiseen uuteen tapaan käsitellä eri kielisiä avainsanoja

	require("inc/connect.inc");

	$query = "	SELECT *
				FROM avainsana
				WHERE yhtio = 'allr'
				AND kieli = 'fi'
				AND laji = 'OSASTO'
				ORDER BY jarjestys";

	$result = mysql_query($query) or die("Select failed: ".$query."\n\n");

	$i = 0;
	
	while ($row = mysql_fetch_array($result)) {
		$query = "	SELECT *
					FROM avainsana
					WHERE yhtio = 'allr'
					AND kieli in ('', 'fi', 'se')
					AND laji like '%_SE'
					AND selite = '$row[selitetark]'";
		$result2 = mysql_query($query) or die("Select failed: ".$query."\n\n");
		
		if (mysql_num_rows($result2) == 1) {
			$i++;
			$row2 = mysql_fetch_array($result2);
		
			$query = "INSERT INTO avainsana SET yhtio = 'allr', kieli = 'se', laji = 'OSASTO', selite = '$row[selite]',  selitetark = '$row2[selitetark]', selitetark_3 = '$row2[selitetark_3]', jarjestys = '$row[jarjestys]'\n\n";
			$res_ins = mysql_query($query) or die("Insert failed: ".$query."\n\n");
		}
	}
	
	echo "NUMBER OF ROWS: $i \n\n\n";
	
	$query = "	SELECT *
				FROM avainsana
				WHERE yhtio = 'allr'
				AND kieli = 'fi'
				AND laji = 'TRY'
				ORDER BY jarjestys";

	$result = mysql_query($query) or die("Select failed: ".$query."\n\n");
	
	$i = 0;
	
	while ($row = mysql_fetch_array($result)) {
		$query = "	SELECT *
					FROM avainsana
					WHERE yhtio = 'allr'
					AND kieli in ('', 'fi', 'se')
					AND laji like '%_SE'
					AND selite = '$row[selitetark]'";
		$result2 = mysql_query($query) or die("Select failed: ".$query."\n\n");
		
		if (mysql_num_rows($result2) == 1) {
			$i++;
			$row2 = mysql_fetch_array($result2);
		
			$query = "INSERT INTO avainsana SET yhtio = 'allr', kieli = 'se', laji = 'TRY', selite = '$row[selite]',  selitetark = '$row2[selitetark]', selitetark_3 = '$row2[selitetark_3]', jarjestys = '$row[jarjestys]'\n\n";
			$res_ins = mysql_query($query) or die("Insert failed: ".$query."\n\n");
		}
	}

	echo "NUMBER OF ROWS: $i \n\n\n";

	//echo "DELETE FROM avainsana WHERE yhtio = 'allr' AND kieli in ('','fi','se','en') AND laji like '%_SE'\n\n"
?>