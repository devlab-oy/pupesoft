<?php

require('../inc/parametrit.inc');

if (empty($tee)) {
	echo "<form action='' method='POST'>";
	echo "<input type='hidden' name='tee' value='poista' />";
	echo "</form>";
}
else {
	echo "Poistettu";
	$query = "	DELETE FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus != '';
			DELETE FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus != '';
			DELETE FROM tyomaarays WHERE yhtio = '{$kukarow['yhtio']}' AND otunnus != '';
			DELETE FROM laskun_lisatiedot WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus != '';
			DELETE FROM tilausrivin_lisatiedot WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus != '';
			UPDATE laite SET viimeinen_tapahtuma = NULL WHERE yhtio = '{$kukarow['yhtio']}';";
	pupe_query($query);
}
?>
