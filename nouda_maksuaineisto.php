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

		echo "<font class='head'>".t("Nouda tallennettu maksuaineisto")."</font><hr><br>";

		if ($kotimaa == "FI") {
			echo "<font class='message'>Kotimaan LM03-maksuaineisto:</font><br>";
		}
		else {
			echo "<font class='message'>Betalningsuppdrang via Bankgirot - Inrikesbetalningar:</font><hr>";
		}

		echo "<form method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
		echo "<select name='filenimi' multiple='FALSE' size='10'>";
		
		$handle = opendir("dataout");
		
		$i=0;
		
		while ($file = readdir($handle)) {
		  	$lista[$i] = $file;
		 	$i++;
		}

		sort($lista);

		for ($i=0; $i < count($lista); $i++) {
			if (($kotimaa == "FI" and substr($lista[$i],0, 5+strlen($kukarow["yhtio"])) == "lm03-$kukarow[yhtio]") or ($kotimaa == "SE" and substr($lista[$i],0, 4+strlen($kukarow["yhtio"])) == "bg-$kukarow[yhtio]-")) {
				echo "<option value='$lista[$i]' $sel>$lista[$i]</option>";
			}
		}
		
		closedir($handle);

		echo "</select>";
		echo "<input type='submit' value='Tallenna'></form>";
		echo "<br><br>";

		if ($kotimaa == "FI") {
			echo "<font class='message'>Ulkomaan LUM2-maksuaineisto:</font><br>";
		}
		else {
			echo "<font class='message'>Betalningsuppdrang via Bankgirot - Utlandsbetalningar:</font><hr>";
		}

		$handle = opendir("dataout");

		echo "<form method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
		echo "<select name='filenimi' multiple='FALSE' size='10'>";

		$i=0;
		unset($lista);
		
		while ($file = readdir($handle)) {
		  	$lista[$i] = $file;
		 	$i++;
		}

		sort($lista);

		for ($i=0; $i < count($lista); $i++) {
			if (($kotimaa == "FI" and substr($lista[$i], 0, 5+strlen($kukarow["yhtio"])) == "lum3-$kukarow[yhtio]") or ($kotimaa == "SE" and substr($lista[$i],0, 4+strlen($kukarow["yhtio"])) == "bg-$kukarow[yhtio]-")) {
				echo "<option value='$lista[$i]' $sel>$lista[$i]</option>";
			}
		}
		closedir($handle);

		echo "</select>";
		echo "<input type='submit' value='Tallenna'></form>";
		echo "<br><br>";

		require ("inc/footer.inc");
	}

?>
