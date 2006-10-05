<?php

echo "Luetaan maaryhmiä\n\n";

require ("/var/www/html/pupesoft/inc/connect.inc");
require ("/var/www/html/pupesoft/inc/functions.inc");

		
$file=fopen($argv[1],"r") or die ("Ei aukea!\n");

// luetaan tiedosto alusta loppuun...
$rivi = fgets($file, 4096);
$lask = 0;

while (!feof($file)) {

	$rivi = explode("\t", trim($rivi));

	$ryhmakoodi = trim($rivi[0]);
	$ryhmanimi  = trim($rivi[1]);	

	$ryhmienmaara = count($rivi);

	for ($i = 2; $i<$ryhmienmaara; $i++) {
		$lask++;

		$rivi[$i] = trim($rivi[$i]);		
		
		if ($rivi[$i] != '') {
		
			//hateaan maa
			$query  = "	SELECT *
						FROM maat
						WHERE koodi = '$rivi[$i]'";
			$result = mysql_query($query) or pupe_error($query);
			$maarow = mysql_fetch_array($result);
			
			
			$query = "	INSERT into maat
						SET
						koodi 			= '$rivi[$i]',
						nimi			= '$maarow[nimi]',
						ryhma_tunnus 	= '$ryhmakoodi'";
			$result = mysql_query($query) or pupe_error($query);
			
			//echo "$lask $query\n\n";		
			//if ($lask > 10) exit;
		}
	}
		
	$rivi = fgets($file, 4096);
} // end while eof

echo "$lask maata lisätty!\n";

fclose($file);

?>