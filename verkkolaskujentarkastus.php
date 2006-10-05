<?php

require "inc/parametrit.inc";
$ftp_server="ftp.verkkolasku.net";

$path[1]="/home/verkkolaskut/ok/"; //Missä ovat käsitellyt, lokaalit filet Huomaa viimeinen kauttaviiva
$path[2]="/home/verkkolaskut/ok/2004/";
$path[3]="/home/verkkolaskut/ok/2005/";

$muoto = "xml"; // Mitä muotoa tarkastetaan

$conn_id = ftp_connect($ftp_server);
if($conn_id) {
	$login_result = ftp_login($conn_id, $yhtiorow['verkkotunnus_vas'], $yhtiorow['verkkosala_vas']);
	if($login_result) {
		ftp_pasv($conn_id, true);
		$contents = ftp_nlist($conn_id, "/bills/by-ebid");
		ftp_close($conn_id);
		if (is_array($contents)) {
			echo "<font class='message'>Tarkistettavia laskuja on ".sizeof($contents)."</font><br>";
			foreach ($contents as $tiedosto) {
				$ok=0;
				foreach ($path as $polku) {
					$hae = $polku.substr($tiedosto,15,35);
					//echo "$hae<br>";
					if ((is_file($hae))) {
						$ok=1;
						break;
					}
				}
				if ($ok == 0) {
					echo "</font><font class='error'>".substr($tiedosto,15,35)." --> puuttuu</font><br>";
				}
				else {
					echo "<font class='message'>Tiedosto $hae --> on ok</font><br>";
				}
			}
		}
		else {
			echo "<font class='error'>Laskujen haku ei onnistu</font><br>";
		}
	}
	else {
		echo "<font class='error'>Ftp-kirjautuminen ei onnistu</font><br>";
	}
}
else {
	echo "<font class='error'>Ftp-yhteyden avaaminen ei onnistu</font><br>";
}
require("inc/footer.inc");
?> 
