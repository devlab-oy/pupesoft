<?php

	if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto = 1;
	if($_POST["filenimi"] != '') $kaunisnimi = "SOLOMYSA.DAT";

	require("inc/parametrit.inc");

	if ($tee == "lataa_tiedosto") {
		readfile("dataout/".$filenimi);
		exit;
	}
	else {
		echo "<font class='head'>".t("Nouda tallennettu siirtoluettelo")."</font><hr><br><br>";

		$handle = opendir("dataout");

		echo "<form method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
		echo "<select name='filenimi' multiple='FALSE' size='10'>";

		while ($file = readdir($handle)) {
			if (substr($file,0, 12) == "Nordeasiirto") {
				echo "<option value='$file' $sel>".t($file)."</option>";
			}
		}
		closedir($handle);

		echo "</select>";
		echo "<input type='submit' value='Tallenna'></form>";
		echo "<br><br>";
	
		require ("inc/footer.inc");
	}

?>