<?php

require ("inc/parametrit.inc");

echo "<font class='head'>".t("Sanakirjojen yhdistely")."</font><hr>";

if ($oikeurow['paivitys'] != '1') { // Saako päivittää
	if ($uusi == 1) {
		echo "<b>".t("Sinulla ei ole oikeutta lisätä")."</b><br>";
		$uusi = '';
	}
	if ($del == 1) {
		echo "<b>".t("Sinulla ei ole oikeuttaa poistaa")."</b><br>";
		$del = '';
		$tunnus = 0;
	}
	if ($upd == 1) {
		echo "<b>".t("Sinulla ei ole oikeuttaa muuttaa")."</b><br>";
		$upd = '';
		$uusi = 0;
		$tunnus = 0;
	}
}

if (!isset($tee)) $tee = "";

$kieliarray = array("se","en","de","no","dk","ee");

if ($tee == "TEE" or $tee == "UPDATE") {

	$sanakirjaquery  = "UPDATE sanakirja SET synkronoi = ''";
	$referes = pupe_query($sanakirjaquery, $link);

	$query = "SHOW databases";
	$dbresult = pupe_query($query, $link);

	while ($dbrow = mysql_fetch_row($dbresult)) {

		if ($dbrow[0] == "referenssi" or $dbrow[0] == "matchrace" or $dbrow[0] == "signalold") {
			continue;
		}

		// otetaan sanakirja connect
		$sanalink = mysql_connect($dbhost, "pupesoft2", "pupe1") or die ("Ongelma tietokantapalvelimessa $dbhost\n");
		mysql_select_db($dbrow[0], $sanalink) or die ("\nTietokantaa $dbrow[0] ei löydy palvelimelta!\n");

		$sanakirjaquery  = "UPDATE sanakirja SET synkronoi = ''";
		$referes = mysql_query($sanakirjaquery, $sanalink);
	}

	$sanakirjaquery  = "SELECT kysytty,fi,se,no,en,de,dk,ee,muutospvm
						FROM sanakirja
						ORDER BY kysytty desc";
	$referes = pupe_query($sanakirjaquery, $link);

	if (mysql_num_rows($referes) > 1) {

		echo "<table>";
		echo "<tr><th>".t("Kysytty")."</td>";
		echo "<th>".t("Me")." FI</td><th>".t("Ref")." FI</td>";

		foreach ($kieliarray as $kieli) {
			echo "<th>".t("Me")." $kieli</td><th>".t("Ref")." $kieli</td>";
		}

		echo "</tr>";

		while ($rivi = mysql_fetch_assoc($referes)) {

			if (trim($rivi["fi"]) != "") {

				$query = "SHOW databases";
				$dbresult = pupe_query($query, $link);

				while ($dbrow = mysql_fetch_row($dbresult)) {

					if ($dbrow[0] == "referenssi" or $dbrow[0] == "matchrace" or $dbrow[0] == "signalold") {
						continue;
					}

					// otetaan sanakirja connect
					$sanalink = mysql_connect($dbhost, "pupesoft2", "pupe1") or die ("Ongelma tietokantapalvelimessa $dbhost\n");
					mysql_select_db($dbrow[0], $sanalink) or die ("\nTietokantaa $dbrow[0] ei löydy palvelimelta!\n");

					$sanakirjaquery  = "SELECT kysytty,fi,se,no,en,de,dk,ee,muutospvm
										FROM sanakirja
										WHERE fi = BINARY '{$rivi["fi"]}'";
					$sanakirjaresult = mysql_query($sanakirjaquery, $sanalink);

					if ($sanakirjaresult !== FALSE and mysql_num_rows($sanakirjaresult) > 0) {
						$sanakirjarow = mysql_fetch_assoc($sanakirjaresult);

						#echo "<tr><th>{$dbrow[0]}</th><td>".$rivi["kysytty"]."</td>";
						#echo "<td>".$sanakirjarow["fi"]."</td><td>$rivi[fi]</td>";

						$sanakirjaquery  = "UPDATE sanakirja SET synkronoi = 'X' where fi = BINARY '{$sanakirjarow["fi"]}'";
						$sanakirjaresult = mysql_query($sanakirjaquery, $sanalink);

						foreach ($kieliarray as $kieli) {
							$sanakirjarow[$kieli] = pupesoft_cleanstring($sanakirjarow[$kieli]);

							// Korjataan käännöksen eka merkki vastamaan referenssin ekan merkin kokoa
							if (ctype_upper(substr($sanakirjarow["fi"], 0, 1)) === TRUE) {
								// Eka merkki iso kirjain
								$sanakirjarow[$kieli] = ucfirst($sanakirjarow[$kieli]);
							}
							else {
								// Muuten koko stringi pienillä
								$sanakirjarow[$kieli] = strtolower($sanakirjarow[$kieli]);
							}

							if ($sanakirjarow[$kieli] != "" and $sanakirjarow[$kieli] != $rivi[$kieli]) {

								if ($tee == "UPDATE") {
									$sanakirjaquery  = "UPDATE sanakirja SET $kieli = '{$sanakirjarow[$kieli]}' where fi = BINARY '{$sanakirjarow["fi"]}' and muutospvm<='{$sanakirjarow["muutospvm"]}'";
									$sanakirjaresult = pupe_query($sanakirjaquery, $link);
								}

								$e = "<font class='error'>";
								$t = "</font>";
							}
							else {
								$e = "";
								$t = "";
							}

							#echo "<td>$e".$sanakirjarow[$kieli]."$t</td><td>$rivi[$kieli]</td>";
						}
					}
				}

				$sanakirjaquery  = "SELECT kysytty,fi,se,no,en,de,dk,ee,muutospvm FROM sanakirja WHERE fi = BINARY '$rivi[fi]'";
				$sanakirjaresult = pupe_query($sanakirjaquery, $link);
				$sanakirjarow = mysql_fetch_assoc($sanakirjaresult);

				$sanakirjaquery  = "UPDATE sanakirja SET synkronoi = 'X' where fi = BINARY '$rivi[fi]'";
				$sanakirjaresult = pupe_query($sanakirjaquery, $link);

				echo "<tr>
						<td>".$rivi["kysytty"]."</td>
						<td class='green'>{$sanakirjarow["fi"]}</td><td class='green'>$rivi[fi]</td>";

				foreach ($kieliarray as $kieli) {
					echo "<td class='green'>{$sanakirjarow[$kieli]}</td><td class='green'>$rivi[$kieli]</td>";
				}

				echo "</tr>";
			}
		}

		$query = "SHOW databases";
		$dbresult = pupe_query($query, $link);

		while ($dbrow = mysql_fetch_row($dbresult)) {

			if ($dbrow[0] == "referenssi" or $dbrow[0] == "matchrace" or $dbrow[0] == "signalold") {
				continue;
			}

			// otetaan sanakirja connect
			$sanalink = mysql_connect($dbhost, "pupesoft2", "pupe1") or die ("Ongelma tietokantapalvelimessa $dbhost\n");
			mysql_select_db($dbrow[0], $sanalink) or die ("\nTietokantaa $dbrow[0] ei löydy palvelimelta!\n");

			// Hetaan ne jotka ei ollut referenssissä
			$sanakirjaquery  = "SELECT *
								FROM sanakirja
								WHERE synkronoi = ''
								and (se !='' or no !='' or en !='' or de !='' or dk !='' or ee !='')";
			$sanakirjaresult = mysql_query($sanakirjaquery, $sanalink);

			while ($sanakirjarow = mysql_fetch_assoc($sanakirjaresult)) {

				// Varmistetaan, että ei ole referenssissä
				$sanakirjaquery  = "SELECT *
									FROM sanakirja
									WHERE fi = BINARY '$sanakirjarow[fi]'";
				$referesult = mysql_query($sanakirjaquery, $link);
				$referow = mysql_fetch_assoc($referesult);

				if (mysql_num_rows($referesult) == 0) {

					foreach ($kieliarray as $kieli) {
						$sanakirjarow[$kieli] = pupesoft_cleanstring($sanakirjarow[$kieli]);

						// Korjataan käännöksen eka merkki vastamaan referenssin ekan merkin kokoa
						if (ctype_upper(substr($sanakirjarow["fi"], 0, 1)) === TRUE) {
							// Eka merkki iso kirjain
							$sanakirjarow[$kieli] = ucfirst($sanakirjarow[$kieli]);
						}
						else {
							// Muuten koko stringi pienillä
							$sanakirjarow[$kieli] = strtolower($sanakirjarow[$kieli]);
						}
					}

					$sanakirjaquery  = "INSERT INTO sanakirja
										SET
										fi = '$sanakirjarow[fi]',
										se = '$sanakirjarow[se]',
										no = '$sanakirjarow[no]',
										en = '$sanakirjarow[en]',
										de = '$sanakirjarow[de]',
										dk = '$sanakirjarow[dk]',
										ee = '$sanakirjarow[ee]',
										aikaleima 	= '$sanakirjarow[aikaleima]',
										kysytty 	= '$sanakirjarow[kysytty]',
										laatija 	= '$sanakirjarow[laatija]',
										luontiaika 	= '$sanakirjarow[luontiaika]',
										muutospvm 	= '$sanakirjarow[muutospvm]',
										muuttaja 	= '$sanakirjarow[muuttaja]'";
					$result = pupe_query($sanakirjaquery, $link);
				}
				else {

					foreach ($kieliarray as $kieli) {
						$sanakirjarow[$kieli] = pupesoft_cleanstring($sanakirjarow[$kieli]);

						// Korjataan käännöksen eka merkki vastamaan referenssin ekan merkin kokoa
						if (ctype_upper(substr($sanakirjarow["fi"], 0, 1)) === TRUE) {
							// Eka merkki iso kirjain
							$sanakirjarow[$kieli] = ucfirst($sanakirjarow[$kieli]);
						}
						else {
							// Muuten koko stringi pienillä
							$sanakirjarow[$kieli] = strtolower($sanakirjarow[$kieli]);
						}

						if ($sanakirjarow[$kieli] == "") {
							$sanakirjarow[$kieli] = $referow[$kieli];
						}
					}

					$sanakirjaquery  = "UPDATE sanakirja
										SET
										se 			= '$sanakirjarow[se]',
										no 			= '$sanakirjarow[no]',
										en 			= '$sanakirjarow[en]',
										de 			= '$sanakirjarow[de]',
										dk 			= '$sanakirjarow[dk]',
										ee 			= '$sanakirjarow[ee]',
										kysytty     = kysytty+$sanakirjarow[kysytty],
										muutospvm   = if('$sanakirjarow[muutospvm]' > muutospvm, '$sanakirjarow[muutospvm]', muutospvm)
										WHERE fi = BINARY '$sanakirjarow[fi]'";
					$result = pupe_query($sanakirjaquery, $link);
				}
			}
		}

		echo "</table><br><br>";

		echo "	<form method='post' action='$PHP_SELF'>
				<input type='hidden' name='tee' value='UPDATE'>
				<input type='submit' value='".t("Synkronoi")."'>
				</form>";

	}
}
else {
	echo "	<br><br>
			<form method='post' action='$PHP_SELF'>
			<input type='hidden' name='tee' value='UPDATE'>
			<input type='submit' value='".t("Vertaa sanakirjoja")."'>
			</form>";
}

require ("inc/footer.inc");

?>