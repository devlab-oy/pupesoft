<?php

	if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
	if($_POST["filenimi"] != '') $kaunisnimi=$_POST["filenimi"];

	require("inc/parametrit.inc");

	if ($tee == "lataa_tiedosto") {
		readfile("dataout/".$filenimi);
		exit;
	}
	else {
	
		if (strtoupper($yhtiorow['maa']) == 'FI') {
			$kotimaa = "FI";
		}
		elseif (strtoupper($yhtiorow['maa']) == 'SE') {
			$kotimaa = "SE";
		}
		if (strtoupper($yhtiorow['maa']) != 'FI' and strtoupper($yhtiorow['maa']) != 'SE') {
			echo "<font class='error'>".t("Yrityksen maa ei ole sallittu")." (FI, SE) '$yhtiorow[maa]'</font><br>";
			exit;
		}

		echo "<font class='head'>".t("Nouda tallennettu maksuaineisto")."</font><hr><br><br>";

		if ($kotimaa == "FI") {
			echo "<font class='message'>Kotimaan LM03-maksuaineisto:</font><br>";
		}
		else {
			echo "<font class='message'>Betalningsuppdrang via Bankgirot - Inrikesbetalningar:</font><hr>";
		}
		
		$handle = opendir("dataout");

		echo "<form method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
		echo "<select name='filenimi' multiple='FALSE' size='10'>";

		echo "<option value=''></option>";

		while ($file = readdir($handle)) {
			
			if (($kotimaa == "FI" and substr($file,0, 4) == "lm03") or ($kotimaa == "SE" and substr($file,0, 4+strlen($kukarow["yhtio"])) == "bg-$kukarow[yhtio]-")) {
				echo "<option value='$file' $sel>$file</option>";
			}
		}
		closedir($handle);

		echo "</select>";
		echo "<input type='submit' value='Tallenna'></form>";
		echo "<br><br>";
	
	
	
		if ($kotimaa == "FI") {
			echo "<font class='message'>Ulkomaan LUM3-maksuaineisto:</font><br>";
		}
		else {
			echo "<font class='message'>Betalningsuppdrang via Bankgirot - Utlandsbetalningar:</font><hr>";
		}
		
		$handle = opendir("dataout");

		echo "<form method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
		echo "<select name='filenimi' multiple='FALSE' size='10'>";

		echo "<option value=''></option>";

		while ($file = readdir($handle)) {
			
			if (($kotimaa == "FI" and substr($file,0, 4) == "lum3") or ($kotimaa == "SE" and substr($file,0, 4+strlen($kukarow["yhtio"])) == "bg-$kukarow[yhtio]-")) {
				echo "<option value='$file' $sel>$file</option>";
			}
		}
		closedir($handle);

		echo "</select>";
		echo "<input type='submit' value='Tallenna'></form>";
		echo "<br><br>";
	
		require ("inc/footer.inc");
	}

?>