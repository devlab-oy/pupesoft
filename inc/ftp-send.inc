<?php

if (@include_once("inc/functions.inc"));
elseif (@include_once("functions.inc"));
else exit;

// Parametrit:
# $ftphost --> FTP-palvelin
# $ftpuser --> Käyttäjätunnus
# $ftppass --> Salasana
# $ftppath --> Kansio FTP-palvelimella jonne failit työnnetään
# $ftpfile --> Siirrettävä tiedosto
# $ftpport --> Custom portti, ei pakollinen
# $ftpfail --> Kansio jonne laitetaan failit jos siirto feilaa, ei pakollinen

// Palautetaan:
# $palautus
# $syy

// Debug:
# echo "ftphost --> $ftphost<br>";
# echo "ftpuser --> $ftpuser<br>";
# echo "ftppass --> $ftppass<br>";
# echo "ftppath --> $ftppath<br>";
# echo "ftpfile --> $ftpfile<br>";
# echo "ftpport --> $ftpport<br>";
# echo "ftpfail --> $ftpfail<br>";

$syy				= "";
$palautus			= 0;
$tulos_ulos_ftp		= "";
$ncftplisa			= "";
$renameftpfile		= isset($renameftpfile) ? basename(trim($renameftpfile)) : "";
$palautus_pyynto	= isset($palautus_pyynto) ? trim($palautus_pyynto) : "";
$filenimi			= basename($ftpfile);

if ($ftphost == '' or $ftpuser == '' or $ftppass == '' or $ftppath == '' or $ftpfile == '') {
	$tulos_ulos_ftp .= "<font class='error'>".t("Lähetykseen tarvittavia tietoja puuttuu")."! (host, user, pass, path, file)</font><br>\n";
	$palautus = 9;
	$syy = t("Lähetykseen tarvittavia tietoja puuttuu");
}
else {
	//lähetetään tiedosto

	// Onko käytössä custom portti
	if (isset($ftpport) and (int) $ftpport > 0) {
		$ftpport = (int) $ftpport;

		$ncftplisa = "-P $ftpport";

		$conn_id = ftp_connect($ftphost, $ftpport);
	}
	else {
		$conn_id = ftp_connect($ftphost);
	}

	// jos connectio ok, kokeillaan loginata
	if ($conn_id) {
		$login_result = ftp_login($conn_id, $ftpuser, $ftppass);
	}

	// jos viimeinen merkki pathissä ei ole kauttaviiva lisätään kauttaviiva...
	if (substr($ftppath, -1) != "/") {
		$ftppath .= "/";
	}

	if ($renameftpfile != "") {
		// ncftp:llä ei hanskata uudelleennimeämistä, joten laitetaan suoraan lopullisella nimellä...
		$cmd = "/usr/bin/ncftpput -u ".escapeshellarg($ftpuser)." -p ".escapeshellarg($ftppass)." $ncftplisa ".escapeshellarg($ftphost)." ".escapeshellarg($ftppath)." ".escapeshellarg($renameftpfile);
	}
	else {
		$cmd = "/usr/bin/ncftpput -u ".escapeshellarg($ftpuser)." -p ".escapeshellarg($ftppass)." $ncftplisa ".escapeshellarg($ftphost)." ".escapeshellarg($ftppath)." ".escapeshellarg($ftpfile);
	}

	// jos login ok kokeillaan uploadata
	if ($login_result) {

		// Käytetään aina .TMP päätettä kun siirto on kesken
		$ftpfile_tmp = $filenimi.".TMP";

		// Kokeillaan passiivista siirtoa
		ftp_pasv($conn_id, TRUE);
		$upload = @ftp_put($conn_id, $ftppath.$ftpfile_tmp, realpath($ftpfile), FTP_ASCII);

		// Kokeillaan aktiivista siirtoa jos passiivi feilaa
		if ($upload === FALSE) {
			ftp_pasv($conn_id, FALSE);
			$upload = ftp_put($conn_id, $ftppath.$ftpfile_tmp, realpath($ftpfile), FTP_ASCII);
		}

		// Otetaan .TMP pääte veks ku siirto on valmis
		if ($upload === TRUE) {
			$rename = ftp_raw($conn_id, "RNFR {$ftppath}{$ftpfile_tmp}");
            $rename = ftp_raw($conn_id, "RNTO {$ftppath}{$filenimi}");

			if (stripos($rename[0], "command successful") === FALSE) {
				$rename1 = FALSE;
			}
		}

		// Pitääkö faili vielä nimetä kokonana uudestaan (Finvoice iPost)
		if ($upload === TRUE and $renameftpfile != "") {
			$rename = ftp_raw($conn_id, "RNFR {$ftppath}{$filenimi}");
            $rename = ftp_raw($conn_id, "RNTO {$ftppath}{$renameftpfile}");

			if (stripos($rename[0], "command successful") === FALSE) {
				$rename2 = FALSE;
			}
		}

		// Kokeillaan ncftp-ohjelmalla, uudelleenimeäminen ei onnaa joten laitetaan suoraan lopullisella nimellä
		if ($upload === FALSE) {
			exec($cmd, $skit, $upload_ncftp);

			if ($upload_ncftp == 0) {
				$upload = TRUE;
			}
		}
	}

	if ($conn_id) {
		ftp_close($conn_id);
	}

	// mikä feilas?
	if (isset($conn_id) and $conn_id === FALSE) {
		$palautus = 1;
	}
	if (isset($login_result) and $login_result === FALSE) {
		$palautus = 2;
	}
	if (isset($upload) and $upload === FALSE) {
		$palautus = 3;
	}
	if (isset($rename1) and $rename1 === FALSE) {
		$palautus = 4;
	}
	if (isset($rename2) and $rename2 === FALSE) {
		$palautus = 5;
	}

	// jos siirto epäonnistuu
	if ($palautus != 0) {

		// Siirretään faili outboxiin odottamaan uutta yritystä jos siirto feilasi
		if (isset($ftpfail)) {
			rename($ftpfile, $ftpfail."/".$filenimi);
		}

		// ncftpput:in exit valuet
		switch ($palautus) {
			case  1:
				$syy = "Could not connect to remote host. ($ftphost)";
				break;
			case  2:
				$syy = "Could not login to remote host ($ftpuser, $ftppass)";
				break;
			case  3:
				$syy = "Transfer failed ($ftppath, ".realpath($ftpfile).")";
				break;
			case  4:
				$syy = "Rename failed ($ftppath, {$ftppath}{$ftpfile_tmp} --> {$ftppath}{$filenimi})";
				break;
			case  5:
				$syy = "Rename failed ($ftppath, {$ftppath}{$filenimi} --> {$ftppath}{$renameftpfile})";
				break;
			default:
				$syy = t("Tuntematon errorkoodi")." ($palautus)!!";
		}

		$rivi  = "$PHP_SELF\n";
		$rivi .= "\n";
		$rivi .= t("Tiedoston")." '$ftpfile' ".t("lähetys epäonnistui")."!\n";
		$rivi .= "\n";
		$rivi .= "$cmd\n";
		$rivi .= "\n";
		$rivi .= "$syy\n";

		$boob = mail($yhtiorow['alert_email'], mb_encode_mimeheader(t("Tiedostonsiirto epäonnistui")."!", "ISO-8859-1", "Q"), $rivi, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");

		if ($boob === FALSE) {
			$tulos_ulos_ftp .= "<font class='error'>\n";
			$tulos_ulos_ftp .= t("Tiedostonsiirto epäonnistui! Ja meilin lähettäminen")." $yhtiorow[alert_email] ".t("epäonnistui myös!")."\n";
			$tulos_ulos_ftp .= "</font><br>\n";

			$tulos_ulos_ftp .= "<br><pre>$rivi</pre><br>\n";
		}
		else {
			$tulos_ulos_ftp .= "<font class='error'>FAILED! (".t("lähetettiin sähköposti")." $yhtiorow[alert_email])</font><br>\n";
		}
	}
	elseif ($palautus_pyynto != "") {
		$rivi  = "$PHP_SELF\n";
		$rivi .= "\n";
		$rivi .= "".t("Tiedoston")." '$ftpfile' ".t("lähetys onnistui")."!\n";
		$rivi .= "\n";
		$rivi .= "$ftpuser @ $ftphost : $ftppath\n";
		$rivi .= "\n";
		$rivi .= "\n";

		$silari = mail($yhtiorow['alert_email'], mb_encode_mimeheader(t("Tiedostonsiirto onnistui")."!", "ISO-8859-1", "Q"), $rivi, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
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