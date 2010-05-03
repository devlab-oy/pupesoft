<?php

	if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
	if($_POST["filenimi"] != '') $kaunisnimi=$_POST["filenimi"];

	require("inc/parametrit.inc");

	if ($tee == "poista_tiedosto") {
		$filenimi = basename($filenimi);
		unlink("dataout/".$filenimi);
	}

	if ($tee == "lataa_tiedosto") {
		$filenimi = basename($filenimi);
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

		$handle = opendir("dataout");
		$i=0;
		$lista = array();

		while ($file = readdir($handle)) {
		  	$lista[$i] = $file;
		 	$i++;
		}

		sort($lista);
		closedir($handle);

		echo "<font class='head'>".t("Nouda tallennettu maksuaineisto")."</font><hr><br>";

		if ($kotimaa == "FI") {
			echo "<font class='message'>Kotimaan LM03-maksuaineisto:</font><br>";
		}
		else {
			echo "<font class='message'>Betalningsuppdrang via Bankgirot - Inrikesbetalningar:</font><hr>";
		}

		$valinta = "";
		for ($i=0; $i < count($lista); $i++) {
			if (($kotimaa == "FI" and substr($lista[$i],0, 5+strlen($kukarow["yhtio"])) == "lm03-$kukarow[yhtio]") or ($kotimaa == "SE" and substr($lista[$i],0, 4+strlen($kukarow["yhtio"])) == "bg-$kukarow[yhtio]-")) {
				$valinta .= "<option value='$lista[$i]' $sel>$lista[$i]</option>";
			}
		}

		if ($valinta != "") {
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<select name='filenimi' multiple='FALSE' size='10'>";
			echo $valinta;
			echo "</select>";
			echo "<input type='submit' value='".t("Tallenna")."'></form><br>";
		}
		else {
			echo "<font class='message'>Ei aineistoja.</font><br>";
		}

		if ($kotimaa == "FI") {
			echo "<font class='message'>Ulkomaan LUM2-maksuaineisto:</font><br>";
		}
		else {
			echo "<font class='message'>Betalningsuppdrang via Bankgirot - Utlandsbetalningar:</font><hr>";
		}

		$valinta = "";
		for ($i=0; $i < count($lista); $i++) {
			if (($kotimaa == "FI" and substr($lista[$i], 0, 5+strlen($kukarow["yhtio"])) == "lum2-$kukarow[yhtio]") or ($kotimaa == "SE" and substr($lista[$i],0, 4+strlen($kukarow["yhtio"])) == "bgut-$kukarow[yhtio]-")) {
				$valinta .= "<option value='$lista[$i]' $sel>$lista[$i]</option>";
			}
		}

		if ($valinta != "") {
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<select name='filenimi' multiple='FALSE' size='10'>";
			echo $valinta;
			echo "</select>";
			echo "<input type='submit' value='".t("Tallenna")."'></form>";
		}
		else {
			echo "<font class='message'>Ei aineistoja.</font><br>";
		}

		echo "<br><br>";
		echo "<font class='head'>".t("Poista tallennettu maksuaineisto")."</font><hr><br>";

		if ($kotimaa == "FI") {
			echo "<font class='message'>Kotimaan LM03-maksuaineisto:</font><br>";
		}
		else {
			echo "<font class='message'>Betalningsuppdrang via Bankgirot - Inrikesbetalningar:</font><hr>";
		}

		$valinta = "";
		for ($i=0; $i < count($lista); $i++) {
			if (($kotimaa == "FI" and substr($lista[$i],0, 5+strlen($kukarow["yhtio"])) == "lm03-$kukarow[yhtio]") or ($kotimaa == "SE" and substr($lista[$i],0, 4+strlen($kukarow["yhtio"])) == "bg-$kukarow[yhtio]-")) {
				$valinta .= "<option value='$lista[$i]' $sel>$lista[$i]</option>";
			}
		}

		if ($valinta != "") {
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tee' value='poista_tiedosto'>";
			echo "<select name='filenimi' multiple='FALSE' size='10'>";
			echo $valinta;
			echo "</select>";
			echo "<input type='submit' value='".t("Poista")."'></form><br>";
		}
		else {
			echo "<font class='message'>Ei aineistoja.</font><br>";
		}

		if ($kotimaa == "FI") {
			echo "<font class='message'>Ulkomaan LUM2-maksuaineisto:</font><br>";
		}
		else {
			echo "<font class='message'>Betalningsuppdrang via Bankgirot - Utlandsbetalningar:</font><hr>";
		}

		$valinta = "";
		for ($i=0; $i < count($lista); $i++) {
			if (($kotimaa == "FI" and substr($lista[$i], 0, 5+strlen($kukarow["yhtio"])) == "lum2-$kukarow[yhtio]") or ($kotimaa == "SE" and substr($lista[$i],0, 4+strlen($kukarow["yhtio"])) == "bgut-$kukarow[yhtio]-")) {
				$valinta .= "<option value='$lista[$i]' $sel>$lista[$i]</option>";
			}
		}

		if ($valinta != "") {
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tee' value='poista_tiedosto'>";
			echo "<select name='filenimi' multiple='FALSE' size='10'>";
			echo $valinta;
			echo "</select>";
			echo "<input type='submit' value='".t("Poista")."'></form>";
		}
		else {
			echo "<font class='message'>Ei aineistoja.</font><br>";
		}

		require ("inc/footer.inc");
	}

?>
