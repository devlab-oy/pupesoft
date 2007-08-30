<?php

	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	require("inc/parametrit.inc");

	//Hardcoodataan failin nimi /tmp diririkkaan
	$tmpfilenimi = $kukarow["yhtio"]."_mysqlkuvays.sql";

	if (isset($tee)) {
		if ($tee == "lataa_tiedosto") {
			readfile("/tmp/".$tmpfilenimi);
			exit;
		}
	}
	else {
		echo "<font class='head'>".t("SQL-tietokantarakenne").":</font><hr>";


		$ulos = array();

		///usr/bin/mysqldump --> toimii ainakin fedorassa ja ubuntussa by default
		$kala = exec("/usr/bin/mysqldump -u $dbuser --host=$dbhost --password=$dbpass $dbkanta --no-data", $ulos);

		if (!$toot = fopen("/tmp/".$tmpfilenimi, "w")) die("Filen /tmp/$tmpfilenimi luonti epäonnistui!");

		foreach($ulos as $print) {
			fputs($toot, $print."\r\n");
		}

		echo "<table>";
		echo "<tr><th>".t("Tallenna tulos").":</th>";
		echo "<form method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
		echo "<input type='hidden' name='kaunisnimi' value='Tietokantakuvaus.sql'>";
		echo "<input type='hidden' name='tmpfilenimi' value='$tmpfilenimi'>";
		echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
		echo "</table><br>";

		/*
		foreach($ulos as $print) {
			echo "$print<br>";
		}
		*/
		
		$curlfile = "/tmp/".$tmpfilenimi;
		
		$ch  = curl_init();
		curl_setopt ($ch, CURLOPT_URL, "http://www.pupesoft.com/sqlupdate/index.php");
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt ($ch, CURLOPT_HEADER, 1);
		curl_setopt ($ch, CURLOPT_POST, 1);
		curl_setopt ($ch, CURLOPT_POSTFIELDS, array('tee' => "remotefile", 'userfile' => "@$curlfile"));
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_HEADER, FALSE);
		$result = curl_exec ($ch);

		if ($result === FALSE) { 
			echo "<font class='error'>VIRHE:</font><br>";
		   	echo curl_errno($ch) . " - " . curl_error($ch) . "</font><br>";
		}
		curl_close ($ch);
		
		echo "<pre>$result</pre>";
		
				
		require("inc/footer.inc");
	}

?>
