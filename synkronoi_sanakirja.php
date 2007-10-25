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

if ($tee == "TEE" or $tee == "UPDATE") {

	$file	 = fopen("http://www.pupesoft.com/softa/referenssisanakirja.sql","r") or die (t("Tiedoston avaus epäonnistui")."!");
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
			echo "<tr><th>".t("Kysytty")."</td>
			<th>".t("Me")." FI</td><th>".t("Ref")." FI</td>
			<th>".t("Me")." SE</td><th>".t("Ref")." SE</td>
			<th>".t("Me")." EN</td><th>".t("Ref")." EN</td>
			</tr>";
			
			
			$sanakirjaquery  = "UPDATE sanakirja SET synkronoi = ''";
			$sanakirjaresult = mysql_query($sanakirjaquery) or pupe_error($sanakirjaquery);
			
			$rivi = fgets($file);

			while (!feof($file)) {
				// luetaan rivi tiedostosta..
				$poista	 = array("'", "\\");
				$rivi	 = str_replace($poista,"",$rivi);
				$rivi	 = explode("\t", trim($rivi));

				if($rivi[$sync_otsikot["fi"]] != "") {
					
					$sanakirjaquery  = "SELECT kysytty,fi,se,en,de,dk FROM sanakirja WHERE fi = BINARY '".$rivi[$sync_otsikot["fi"]]."'";
					$sanakirjaresult = mysql_query($sanakirjaquery) or pupe_error($sanakirjaquery);
					
					if (mysql_num_rows($sanakirjaresult) > 0) {
						$sanakirjarow = mysql_fetch_array($sanakirjaresult);
						
						$sanakirjaquery  = "UPDATE sanakirja SET synkronoi = 'X' where fi = BINARY '$sanakirjarow[fi]'";
						$sanakirjaresult = mysql_query($sanakirjaquery) or pupe_error($sanakirjaquery);
						
						echo "<tr><td>".$rivi[$sync_otsikot["kysytty"]]."</td>";
							
						echo "<td>".$sanakirjarow["fi"]."</td><td>".$rivi[$sync_otsikot["fi"]]."</td>";
						
						if ($sanakirjarow["se"] != $rivi[$sync_otsikot["se"]]) {
							
							if ($tee == "UPDATE") {
								$sanakirjaquery  = "UPDATE sanakirja SET se = '".$rivi[$sync_otsikot["se"]]."' where fi = BINARY '$sanakirjarow[fi]'";
								$sanakirjaresult = mysql_query($sanakirjaquery) or pupe_error($sanakirjaquery);
							}
							
							$e = "<font class='error'>";
							$t = "</font>";
						}
						else {
							$e = "";
							$t = "";
						}
						
						echo "<td>$e".$sanakirjarow["se"]."$t</td><td>".$rivi[$sync_otsikot["se"]]."</td>";
						
						if ($sanakirjarow["en"] != $rivi[$sync_otsikot["en"]]) {
							
							if ($tee == "UPDATE") {
								$sanakirjaquery  = "UPDATE sanakirja SET en = '".$rivi[$sync_otsikot["en"]]."' where fi = BINARY '$sanakirjarow[fi]'";
								$sanakirjaresult = mysql_query($sanakirjaquery) or pupe_error($sanakirjaquery);
							}
							
							
							$e = "<font class='error'>";
							$t = "</font>";
						}
						else {
							$e = "";
							$t = "";
						}
						
						echo "<td>$e".$sanakirjarow["en"]."$t</td><td>".$rivi[$sync_otsikot["en"]]."</td>";
						echo "</tr>";
					}
					else {
						
						if ($tee == "UPDATE") {
							$sanakirjaquery  = "INSERT INTO sanakirja SET fi = '".$rivi[$sync_otsikot["fi"]]."', aikaleima=now(), kysytty=1, laatija='$kukarow[kuka]', luontiaika=now()";
							$sanakirjaresult = mysql_query($sanakirjaquery, $link) or pupe_error($sanakirjaquery);
						}
						
						echo "<tr><td></td><td><font class='error'>Sanaa ei löydy sanakirjasta!</font></td><td>".$rivi[$sync_otsikot["fi"]]."</td></tr>";
					}
				}

				// luetaan seuraava rivi failista
				$rivi = fgets($file);
			}
			
			fclose($file);
			
			$sanakirjaquery  = "SELECT kysytty,fi,se,en,de,dk FROM sanakirja WHERE synkronoi='' and (se!='' or en!='') and kysytty>1 ORDER BY kysytty desc";
			$sanakirjaresult = mysql_query($sanakirjaquery) or pupe_error($sanakirjaquery);

			while ($sanakirjarow = mysql_fetch_array($sanakirjaresult)) {
				echo "<tr><td>".$sanakirjarow["kysytty"]."</td>";
				echo "<td>".$sanakirjarow["fi"]."</td><td><font class='error'>".t("Puuttuu referenssistä")."</font></td>";
				echo "<td>".$sanakirjarow["se"]."</td><td><font class='error'>".t("Puuttuu referenssistä")."</font></td>";
				echo "<td>".$sanakirjarow["en"]."</td><td><font class='error'>".t("Puuttuu referenssistä")."</font></td>";
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
	echo "	<form method='post' action='$PHP_SELF'>
			<table>
			<tr><th>".t("Valitse tiedosto").":</th>
				<td><input hidden name='tee' value='TEE'></td>
				<td class='back'><input type='submit' value='".t("Lähetä")."'></td>
			</tr>
			</table>
			</form>";
}
	
require ("inc/footer.inc");
	
?>