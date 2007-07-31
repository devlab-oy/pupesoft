<?php

if ($argc==2 and strlen($argv[1]) < 5) {

	require("inc/connect.inc");
	
	//	Poistetaan rivi
	$query = "	DELETE FROM tilausrivi WHERE yhtio='{$argv[1]}' and tyyppi = 'B'";
	$delres = mysql_query($query) or pupe_error($query);
	
}
else {
	echo "Tätä ohjelmaa voi käyttäää vain komentoriviltä. Anna parametriksi yhtiön tunnus\n";
}


?>