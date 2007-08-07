<?php

	if ($argc == 2 and strlen($argv[1]) < 5) {

		require("inc/connect.inc");

		$yhtio = mysql_real_escape_string($argv[1]);

		//	Poistetaan rivi
		$query = "DELETE FROM tilausrivi WHERE tyyppi = 'B' and yhtio = '$yhtio'";
		$delres = mysql_query($query) or pupe_error($query);

	}
	else {
		echo "Tätä ohjelmaa voi käyttäää vain komentoriviltä. Anna parametriksi yhtiön tunnus\n";
	}

?>