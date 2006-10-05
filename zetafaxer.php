<?php

require "inc/parametrit.inc";

echo "<font class='head'>".t('Faxien lähetys')."</font><hr>";

if ($tee == 'file') {

	if (is_uploaded_file($_FILES['thefile']['tmp_name']) == TRUE) {

	    list($name,$ext) = split('\.', $_FILES['thefile']['name']);

	    if (!(strtoupper($ext) == 'PDF')) {
	            die ("<font class='error'><br>".t('Vain PDF tiedostot sallittuja!')."</font>");
	    }

	    if ($_FILES['thefile']['size']==0) {
	            die ("<font class='error'><br>".t('Tiedosto on tyhjä!')."</font>");
	    }

	    system("rm -f /tmp/$kukarow[kuka]spam.tiff");
	
		$kasky    = "gs -q -sDEVICE=tiffg3 -dNOPAUSE -sOutputFile=/tmp/$kukarow[kuka]spam.tiff ".$_FILES['thefile']['tmp_name']." </dev/null";
		$dummy    = array();
		$palautus = "";

		exec($kasky, $dummy, $palautus);
    }

    echo "<font class='message'>Aloitan faxien generoinnin.</font><br>";

    $nrot=explode(',',$lista);
    echo "<font class='message'>" . sprintf ("Tarkoituksena lähettää %d faxia.", sizeof($nrot)) . "</font><br><br>";

    foreach ($nrot as $ytunnus) {

    	echo "<font class='message'>". t('Siirretään faxi jonoon:')." ";

    	$query = "SELECT nimi, fax FROM asiakas WHERE yhtio='$kukarow[yhtio]' and ytunnus='$ytunnus'";
		$aresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($aresult) > 0) {

			$arow = mysql_fetch_array($aresult);

			if ($arow['fax']!='') {

				$ulos  = $arow['fax']."\n";
				$ulos .= file_get_contents("/tmp/$kukarow[kuka]spam.tiff");

				// Kirjoitetaan fax levylle
				$nimi = "dataout/spamfax-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true)).".txt";
				
				if (!$toot = fopen($nimi, "w")) die(t("FAX-SPAMMER: Tiedoston luonti epäonnistui")."!");
				if (!fwrite($toot, $ulos)) die ("Cannot write to file ($nimi)!");
				fclose($toot);
				echo "$arow[nimi] fax: $arow[fax]</font><br>";
				
				// Lähetetään fax-serveriin käsiteltäväksi
				$ftphost = $faxhost;	
				$ftpuser = $faxuser;
				$ftppass = $faxpass;
				$ftppath = $faxpath;
				$ftpfile = realpath($nimi);
				
				require ("inc/ftp-send.inc");
			}
			else {
				echo "<font class='error'>" . sprintf(t('Asiakkaalla %d ei ole faxnumeroa'), $ytunnus)."</font><br>";
			}
		}
		else {
			echo "<font class='error'>" . sprintf(t('Asiakasta %d ei löydy'), $ytunnus)."</font><br>";
		}
	}
	
	echo "<br>";
}

echo "<form method='post' name='sendfile' enctype='multipart/form-data' action='$PHP_SELF'>
			<input type='hidden' name='tee' value='file'>
			<table>
			<tr>
                <th>".t('Lähetettävä PDF tiedosto')."</th>
                <td><input name='thefile' type='file'></td>
            </tr>
			<tr>
                <th>".t('Asiakkaiden ytunnukset pilkulla eroteltuina')."</th>
                <td><input type='text' name='lista' size='50'></td>
            </tr>
            </table>
			<br>
            <input type='submit' value='".t('Lähetä')."'></form>";

require("inc/footer.inc");

?>
