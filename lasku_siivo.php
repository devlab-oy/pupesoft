<?php

require('inc/parametrit.inc');

echo "<br/>";
echo "<br/>";
echo "<br/>";
if (empty($tee)) {
	echo "<form action='' method='POST'>";
	echo "<input type='hidden' name='tee' value='poista' />";
	echo "<input type='submit' value='Poista' />";
	echo "</form>";
}
else {
	echo "Poistettu";
	$query = array(
		"DELETE FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus != ''",
		"DELETE FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus != ''",
		"DELETE FROM tyomaarays WHERE yhtio = '{$kukarow['yhtio']}' AND otunnus != ''",
		"DELETE FROM laskun_lisatiedot WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus != ''",
		"DELETE FROM tilausrivin_lisatiedot WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus != ''",
		"UPDATE laite SET viimeinen_tapahtuma = NULL WHERE yhtio = '{$kukarow['yhtio']}'",
	);
		
	foreach ($query as $q) {
		pupe_query($q);
	}
}

require('inc/footer.inc');
?>
