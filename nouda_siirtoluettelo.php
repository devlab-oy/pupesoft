<?php

	if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto = 1;
	if($_POST["filenimi"] != '' and strpos($_POST["factoringyhtio"], "Nordeasii") !== FALSE) $kaunisnimi = "SOLOMYSA.DAT";
	if($_POST["filenimi"] != '' and strpos($_POST["factoringyhtio"], "OKOsiirto") !== FALSE) $kaunisnimi = "OKOMYSA.DAT";

	require("inc/parametrit.inc");

	if ($tee == "lataa_tiedosto") {
		readfile("dataout/".$filenimi);
		exit;
	}
	else {
		echo "<font class='head'>".t("Nouda tallennettu siirtoluettelo")."</font><hr><br><br>";

		if ($factoringyhtio == "OKOsiirto") {
			$selOKO = "SELECTED";
		}
		else {
			$selNOR = "SELECTED";
		}

		echo "<form method='post' action='$PHP_SELF'>";
		echo "<select name='factoringyhtio' onchange='submit();'>";
		echo "<option value='Nordeasii' $selNOR>Näytä Nordea Factoring siirtotiedostot</option>";
		echo "<option value='OKOsiirto' $selOKO>Näytä OKO Saatavarahoitus siirtotiedosto</option>";
		echo "</select>";
		echo "<input type='submit' value='Näytä'>";
		echo "</form><br><br>";
		
		echo "<form method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
		echo "<input type='hidden' name='factoringyhtio' value='$factoringyhtio'>";
		echo "<select name='filenimi' multiple='FALSE' size='20'>";

		$handle = opendir("dataout");
		
		$i=0;
		
		while ($file = readdir($handle)) {
		  	$lista[$i] = $file;
		 	$i++;
		}

		sort($lista);

		for ($i=0; $i < count($lista); $i++) {
			if (substr($lista[$i],0, 9) == $factoringyhtio) {
				echo "<option value='$lista[$i]'>$lista[$i]</option>";
			}
		}
		
		closedir($handle);

		echo "</select>";
		echo "<input type='submit' value='Tallenna'></form>";
		echo "<br><br>";
	
		require ("inc/footer.inc");
	}

?>