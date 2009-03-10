<?php

	if ($toim == 'asiakas') {
		echo "<table><tr>";
		
		echo "<td class='back'>";
		echo "<form id='asalet' action='yllapito.php' method='post' autocomplete='off'>";
		echo "<input type='hidden' name='toim' value='asiakasalennus'>";
		echo "<input type='hidden' name='haku[1]' value='$tunnus'>";
		echo "<input type='submit' value='".t("Siirry asiakasalennuksiin")."'>";
		echo "</form></td>";
		
		echo "<td class='back'>";
		echo "<form id='asalet' action='yllapito.php' method='post' autocomplete='off'>";
		echo "<input type='hidden' name='toim' value='asiakashinta'>";
		echo "<input type='hidden' name='haku[1]' value='$tunnus'>";
		echo "<input type='submit' value='".t("Siirry asiakashintoihin")."'>";
		echo "</form></td>";
		
		echo "</tr></table><br>";
	}


?>