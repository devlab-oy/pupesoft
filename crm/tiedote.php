<?php
	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Tiedotteet")."</font><hr>";

	echo "<table width='350'>";

	//Lis‰t‰‰n rivi
	if ($tee == 'LISAA') {
		if (strlen($teksti) > 0) {
			if (is_uploaded_file($userfile) == TRUE) {
				$filenimi=$userfile_name;
				if($userfile_size > 0) {
					move_uploaded_file($userfile, 'liitteet/'.$filenimi);
				}
				else {
					echo "".t("Tiedosto on tyhja")."!<br>";
				}
			}
			else {
				//echo "Tiedostoa ei liitetty!<br>";
			}
			$teksti = nl2br(strip_tags($teksti));
			$query = "	INSERT INTO kalenteri
						SET kuka = '$kukarow[kuka]',
						tapa = 'tiedote',
						tyyppi = 'tiedote',
						pvmalku = now(),
						kentta01 = '$teksti',
						kentta02 = '$filenimi',
						yhtio = '$kukarow[yhtio]'";
			mysql_query($query) or pupe_error($query);
		}
		$tee = '';	
	}

	if ($tee == "PAIVITA") { //Nyt editoidaan
		$teksti = nl2br(strip_tags($teksti));
		$query = "	UPDATE kalenteri
					SET kentta01='$teksti'
					WHERE tunnus='$tunnus' and yhtio='$kukarow[yhtio]'";
		mysql_query($query) or pupe_error($query);
		$tee = '';
	}

	if ($tee == "POISTA") { //Poistetaan
		$query = "	DELETE
					FROM kalenteri
					WHERE tunnus='$tunnus' and yhtio='$kukarow[yhtio]'";
		mysql_query($query) or pupe_error($query);
		echo "<font class='message'>".t("Tiedote poistettu")."</font>";
		$tee = '';
	}

	if ($tee == "EDITOI") { //etsitaan sisalto editoitavaksi
		$queryt = "	SELECT kentta01
					FROM kalenteri
					WHERE tunnus='$tunnus' and yhtio='$kukarow[yhtio]'";
		$resultt = mysql_query($queryt) or pupe_error($queryt);
		$text = mysql_fetch_array($resultt);
        $text = $text[0];
		$tee = 'MUOKKAA';
	}


	if($tee == '') {
		echo "	<tr>
				<form action='$PHP_SELF' method='POST'>
				<td align='right' class='back'>
				<input type='hidden' name='tee' value='MUOKKAA'>
				<input type='submit' value='".t("Lis‰‰ tiedote")."'></form></td></tr>";
	}
	
	if ($tee == 'MUOKKAA' or $tee == 'LISAA') {
		$text = strip_tags($text);
		echo "	<tr>
				<th colspan='2'>".t("Lis‰‰ tiedote").":</th></tr>
				<tr><form action='$PHP_SELF' enctype='multipart/form-data' method='POST'>
				<td colspan='2' align='center'><textarea wrap='hard' name='teksti' cols='83' rows='15'>$text</textarea></td></tr>
				<tr><td>".t("Liitetiedosto").": <input type='file' name='userfile'></td><td align='left'>
				<input type='hidden' name='tee' value='LISAA'>
				<input type='hidden' name='filenimi' value='$filenimi'>
				<input type='hidden' name='tunnus' value='$tunnus'>
				<input type='submit' value='".t("Lis‰‰")."'></form></td></tr>";
		echo "<tr><td class='back'><br></td></tr>";
	}

	$query = " 	SELECT *
				FROM kalenteri 
				where tyyppi='tiedote' and yhtio='$kukarow[yhtio]'
				ORDER BY tunnus DESC";
	$result = mysql_query($query) or pupe_error($query);

	while($tiedoterow = mysql_fetch_array($result)) {
		$urllink ='';

		$urllink = "<a href='$PHP_SELF?tee=EDITOI&tunnus=$tiedoterow[tunnus]'>".t("Editoi")."</font></a>";
		$urllink2 = "<a href='$PHP_SELF?tee=POISTA&tunnus=$tiedoterow[tunnus]'>".t("Poista")."</font></a>";
		
		echo "<tr>
				<th colspan='2' align='left'>&nbsp; Tiedottaja: $tiedoterow[kuka] Aika: $tiedoterow[pvmalku] &nbsp;
				<a href='liitteet/$tiedoterow[kentta02]' target='_blank'>$tiedoterow[file]</a> &nbsp; $urllink &nbsp; $urllink2</th>
				</tr>
				<td colspan='2'>$tiedoterow[kentta01]</font></td>
				</tr>";
	}
	echo "</table>";
	
	require ("../inc/footer.inc");
?>