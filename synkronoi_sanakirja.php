<?php

require ("inc/parametrit.inc");

echo "<font class='head'>".t("Sanakirjan synkronointi")."</font><hr>";

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

	$file	 = fopen("http://api.devlab.fi/referenssisanakirja.sql","r") or die (t("Tiedoston avaus epäonnistui")."!");
	$rivi    = fgets($file);
	$otsikot = explode("\t", strtoupper(trim($rivi)));

	if (count($otsikot) > 1) {
		$sync_otsikot = array();

		for ($i = 0; $i < count($otsikot); $i++) {

			$a = strtolower($otsikot[$i]);

			$sync_otsikot[$a] = $i;
		}

		if (isset($sync_otsikot["fi"])) {

			echo "<table>";
			echo "<tr><th>".t("Kysytty")."</td>";
			echo "<th>".t("Me")." FI</td><th>".t("Ref")." FI</td>";

			foreach ($kieliarray as $kieli) {
				echo "<th>".t("Me")." $kieli</td><th>".t("Ref")." $kieli</td>";
			}

			echo "</tr>";

			$sanakirjaquery  = "UPDATE sanakirja SET synkronoi = ''";
			$sanakirjaresult = pupe_query($sanakirjaquery);

			while ($rivi = fgets($file)) {
				// luetaan rivi tiedostosta..
				$poista	 = array("'", "\\");
				$rivi	 = str_replace($poista,"",$rivi);
				$rivi	 = explode("\t", trim($rivi));

				if ($rivi[$sync_otsikot["fi"]] != "") {

					$sanakirjaquery  = "SELECT kysytty,fi,se,no,en,de,dk,ee,muutospvm
										FROM sanakirja
										WHERE fi = BINARY '".$rivi[$sync_otsikot["fi"]]."'";
					$sanakirjaresult = pupe_query($sanakirjaquery);

					if (mysql_num_rows($sanakirjaresult) > 0) {
						$sanakirjarow = mysql_fetch_assoc($sanakirjaresult);

						$sanakirjaquery  = "UPDATE sanakirja SET synkronoi = 'X' where fi = BINARY '$sanakirjarow[fi]'";
						$sanakirjaresult = pupe_query($sanakirjaquery);

						echo "<tr><td>".$rivi[$sync_otsikot["kysytty"]]."</td>";
						echo "<td>".$sanakirjarow["fi"]."</td><td>".$rivi[$sync_otsikot["fi"]]."</td>";

						foreach ($kieliarray as $kieli) {

							$e = "";
							$t = "";

							if ($sanakirjarow[$kieli] != $rivi[$sync_otsikot[$kieli]]) {

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

								if ($tee == "UPDATE") {
									$sanakirjaquery  = "UPDATE sanakirja SET $kieli = '".$rivi[$sync_otsikot[$kieli]]."' where fi = BINARY '$sanakirjarow[fi]'";
									$sanakirjaresult = pupe_query($sanakirjaquery);

									$sanakirjarow[$kieli] = $rivi[$sync_otsikot[$kieli]];
								}
								else {
									$e = "<font class='error'>";
									$t = "</font>";
								}
							}

							echo "<td>$e".$sanakirjarow[$kieli]."$t</td><td>".$rivi[$sync_otsikot[$kieli]]."</td>";
						}

						echo "</tr>";
					}
					else {

						echo "<tr><td>".$rivi[$sync_otsikot["kysytty"]]."</td>";

						if ($tee == "UPDATE") {
							$sanakirjaquery  = "INSERT INTO sanakirja SET
												fi = '".$rivi[$sync_otsikot["fi"]]."',
												se = '".$rivi[$sync_otsikot["se"]]."',
												no = '".$rivi[$sync_otsikot["no"]]."',
												en = '".$rivi[$sync_otsikot["en"]]."',
												de = '".$rivi[$sync_otsikot["de"]]."',
												dk = '".$rivi[$sync_otsikot["dk"]]."',
												ee = '".$rivi[$sync_otsikot["ee"]]."',
												aikaleima	= now(),
												kysytty		= 1,
												laatija		= '$kukarow[kuka]',
												luontiaika	= now()";
							$sanakirjaresult = pupe_query($sanakirjaquery);

							echo "<td>".$rivi[$sync_otsikot["fi"]]."</td><td>".$rivi[$sync_otsikot["fi"]]."</td>";

							foreach ($kieliarray as $kieli) {
								echo "<td>".$rivi[$sync_otsikot[$kieli]]."</td><td>".$rivi[$sync_otsikot[$kieli]]."</td>";
							}
						}
						else {
							echo "<td><font class='error'>".t("Sana puuttuu")."!</font></td><td>".$rivi[$sync_otsikot["fi"]]."</td>";

							foreach ($kieliarray as $kieli) {
								echo "<td><font class='error'>".t("Sana puuttuu")."!</font></td><td>".$rivi[$sync_otsikot[$kieli]]."</td>";
							}
						}

						echo "</tr>";
					}
				}
			}

			fclose($file);

			$sanakirjaquery  = "SELECT kysytty,fi,se,no,en,de,dk,ee,muutospvm
								FROM sanakirja
								WHERE synkronoi = ''
								and (se !='' or no !='' or en !='' or de !='' or dk !='' or ee !='')
								and kysytty > 1
								ORDER BY kysytty desc";
			$sanakirjaresult = pupe_query($sanakirjaquery);

			while ($sanakirjarow = mysql_fetch_assoc($sanakirjaresult)) {
				echo "<tr><td>".$sanakirjarow["kysytty"]."</td>";
				echo "<td>".$sanakirjarow["fi"]."</td><td><font class='error'>".t("Puuttuu referenssistä")."</font></td>";

				foreach ($kieliarray as $kieli) {
					echo "<td>".$sanakirjarow[$kieli]."</td><td><font class='error'>".t("Puuttuu referenssistä")."</font></td>";
				}

				echo "</tr>";
			}

			echo "</table><br><br>";


			echo "	<form method='post' action='$PHP_SELF'>
					<input type='hidden' name='tee' value='UPDATE'>
					<input type='submit' value='".t("Synkronoi")."'>

					</form>";
		}
	}
}
else {
	echo "	<br><br>
			<form method='post' action='$PHP_SELF'>
			<input type='hidden' name='tee' value='TEE'>
			<input type='submit' value='".t("Vertaa sanakirjoja")."'>
			</form>";
}

require ("inc/footer.inc");

?>