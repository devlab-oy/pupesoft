<?php

	// Kutsutaanko CLI:stä
	$php_cli = FALSE;

	if (php_sapi_name() == 'cli') {
		$php_cli = TRUE;
	}

	if ($php_cli) {
		// otetaan includepath aina rootista
		ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear");
		error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
		ini_set("display_errors", 0);

		// otetaan tietokanta connect
		require("inc/connect.inc");
		require("inc/functions.inc");
	}
	else {
		if (isset($_POST["tee"])) {
			if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
			if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
		}

		require("inc/parametrit.inc");
	}

	//Hardcoodataan failin nimi /tmp diririkkaan
	$tmpfilenimi = $kukarow["yhtio"]."_mysqlkuvays.sql";

	if (isset($tee)) {
		if ($tee == "lataa_tiedosto") {
			readfile("/tmp/".$tmpfilenimi);
			exit;
		}
	}
	else {
		if (!$php_cli) echo "<font class='head'>".t("SQL-tietokantarakenne").":</font><hr>";

		$ulos = array();

		# /usr/bin/mysqldump --> toimii ainakin fedorassa ja ubuntussa by default
		if (file_exists("/usr/bin/mysqldump")) {
			$mysql_dump_path = "/usr/bin/mysqldump";
		}
		elseif (file_exists("/usr/local/bin/mysqldump")) {
			$mysql_dump_path = "/usr/local/bin/mysqldump";
		}
		else {
			$mysql_dump_path = "mysqldump";
		}

		$kala = exec("$mysql_dump_path -u $dbuser --host=$dbhost --password=$dbpass $dbkanta --no-data", $ulos);

		if (!$toot = fopen("/tmp/".$tmpfilenimi, "w")) die("Filen /tmp/$tmpfilenimi luonti epäonnistui!");

		foreach ($ulos as $print) {
			// poistetaan mysql-sarakkeen kommentti koska se kaataa sqlupdate-ohjelman
			$print = preg_replace("/ COMMENT '[^']*',/", ",", $print);

			fputs($toot, $print."\n");
		}

		if (!$php_cli) {
			echo "<table>";
			echo "<tr><th>".t("Tallenna tulos").":</th>";
			echo "<form method='post' class='multisubmit'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='Tietokantakuvaus.sql'>";
			echo "<input type='hidden' name='tmpfilenimi' value='$tmpfilenimi'>";
			echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
			echo "</table><br>";
		}

		$curlfile = "/tmp/".$tmpfilenimi;

		$ch  = curl_init();
		curl_setopt ($ch, CURLOPT_URL, "http://api.devlab.fi/sqlupdate.php");
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt ($ch, CURLOPT_HEADER, 1);
		curl_setopt ($ch, CURLOPT_POST, 1);
		curl_setopt ($ch, CURLOPT_POSTFIELDS, array('tee' => "remotefile", 'userfile' => "@$curlfile"));
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_HEADER, FALSE);
		$result = curl_exec ($ch);

		if ($result === FALSE) {
			echo "<font class='error'>VIRHE:</font><br>\n";
		   	echo curl_errno($ch) . " - " . curl_error($ch) . "</font><br>";
		}
		curl_close ($ch);

		if (!$php_cli) {
			echo "<pre>$result</pre>";
		}
		elseif (trim($result) != "") {
			$rivit = explode(";", trim($result));

			foreach ($rivit as $rivi) {
				$rivi = trim(str_replace("\n", " ", $rivi));
				if ($rivi != "") {
					echo "echo \"$rivi;\" | mysql -h $dbhost -u $dbuser --password=$dbpass $dbkanta;\\n";
				}
			}
		}

		// Löytyykö custom updateja?
		$ch  = curl_init();
		curl_setopt ($ch, CURLOPT_URL, "http://api.devlab.fi/sqlupdate.sql");
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_HEADER, FALSE);
		$updatet = curl_exec ($ch);

		if (!$php_cli) {
			echo "<pre>$updatet</pre>";
		}
		elseif (trim($updatet) != "") {
			echo "\\n\\n";
			$rivit = explode("\n", trim($updatet));

			foreach ($rivit as $rivi) {
				if (trim($rivi) != "") echo "echo \"$rivi\" | mysql -h $dbhost -u $dbuser --password=$dbpass $dbkanta;\\n";
				else echo "\\n";
			}
		}

		if (!$php_cli) require("inc/footer.inc");
	}
?>