<?php

require("functions.inc");

// tarvitaan $ftphost $ftpuser $ftppass $ftppath $ftpfile
// palautetaan $palautus ja $syy

$dummy			= array();
$syy			= "";
$palautus		= "";
$tulos_ulos_ftp	= "";

$filenimi = basename($ftpfile);

if ($ftphost=='' or $ftpuser=='' or $ftppass=='' or $ftppath=='' or $ftpfile=='') {
	$tulos_ulos_ftp .= "<font class='error'>".t("Lähetykseen tarvittavia tietoja puuttuu")."! (host, user, pass, path, file)</font><br>";
}
else {

	//lähetetään tiedosto

	$conn_id = ftp_connect($ftphost);

	// jos connectio ok, kokeillaan loginata
	if ($conn_id) {
		$login_result = ftp_login($conn_id, $ftpuser, $ftppass);
	}

	// jos viimeinen merkki pathissä ei ole kauttaviiva lisätään kauttaviiva...
	if (substr($ftppath, -1) != "/") {
		$ftppath .= "/";
	}

	// jos login ok kokeillaan uploadata
	if ($login_result) {
		$upload = ftp_put($conn_id, $ftppath.$filenimi, realpath($ftpfile), FTP_ASCII);
	}

	if ($conn_id) {
		ftp_close($conn_id);
	}

	// mikä feilas?
	if ($conn_id === FALSE) {
		$palautus = 1;
	}
	if ($login_result === FALSE) {
		$palautus = 2;
	}
	if ($upload === FALSE) {
		$palautus = 3;
	}

	// jos siirto epäonnistuu
	if ($palautus<>0) {

		// ncftpput:in exit valuet
		switch ($palautus) {
			case  1:
				$syy = "Could not connect to remote host. ($ftphost)";
				break;
			case  2:
				$syy = "Could not login to remote host ($conn_id, $ftpuser, $ftppass)";
				break;
			case  3:
				$syy = "Transfer failed ($conn_id, $ftppath, ".realpath($ftpfile).")";
				break;
			default:
				$syy = t("Tuntematon errorkoodi")." ($palautus)!!";
		}
		
		$cmd = "ncftpput -u $ftpuser -p $ftppass $ftphost $ftppath $ftpfile";
		
		$rivi  = "$PHP_SELF\n";
		$rivi .= "\n";
		$rivi .= "".t("Tiedoston")." '$ftpfile' ".t("lähetys epäonnistui")."!\n";
		$rivi .= "\n";
		$rivi .= "$cmd\n";
		$rivi .= "\n";
		$rivi .= "$syy\n";

		$boob = mail($yhtiorow['admin_email'], "".t("Tiedostonsiirto epäonnistui")."!", $rivi, "From: $yhtiorow[postittaja_email]\n");

		if ($boob===FALSE) {
			$tulos_ulos_ftp .= "<font class='error'>\n";
			$tulos_ulos_ftp .= "".t("Tiedostonsiirto epäonnistui! Ja meilin lähettäminen")." $yhtiorow[admin_email] ".t("epäonnistui myös! Perkele!")."\n";
			$tulos_ulos_ftp .= "</font><br>\n";

			$tulos_ulos_ftp .= "<br><pre>$rivi</pre><br>\n";
		}
		else {
			$tulos_ulos_ftp .= "<font class='error'>FAILED! (".t("lähetettiin sähköposti")." $yhtiorow[admin_email])</font><br>\n";
		}
	}
	else {
//		$tulos_ulos_ftp .= "<font class='message'>".t("Aineiston FTP-siirto onnistui!")."</font><br>\n";
	}
}

//echotaan rudulle jos kyseessä ei ole batch-ajo
if ($tulos_ulos == "") {
	echo $tulos_ulos_ftp;
}
elseif ($tulos_ulos == "edi") {
	$edi_ulos .= strip_tags($tulos_ulos_ftp);
}

?>
