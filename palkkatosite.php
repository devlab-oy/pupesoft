<?php

	require ("inc/parametrit.inc");

	if (is_uploaded_file($_FILES['userfile']['tmp_name'])==TRUE) {

		// T‰m‰ on Pretaxin palkkaohjelmiston normaali siirtomuoto ver 2
		// Tuetaan myˆs M2 matkalaskuohjelmista
		// Tuetaan myˆs M2 matkalaskuohjelmista

		if ($_FILES['userfile']['size']==0){
			die ("<font class='error'><br>".t("Tiedosto on tyhj‰")."!</font>");
		}

		$file	 = fopen($_FILES['userfile']['tmp_name'],"r") or die (t("Tiedoston avaus ep‰onnistui")."!");
		$rivi    = fgets($file);

		$maara = 1;
		$flip  = 0;

		while (!feof($file)) {

			//  M2 matkalaskuohjelma
			if ($tiedostomuoto == "M2MATKALASKU") {
				if (!isset($tpv)) {
					$tpv=substr($rivi,639,4);
					$tpk=substr($rivi,643,2);
					$tpp=substr($rivi,645,2);
				}

				if ($flip == 1) { // Seuraavalla rivill‰ tulee veronm‰‰r‰. Lis‰t‰‰n se!
						$maara--;
						$alv = (float) substr($rivi,24,12);
						if (substr($rivi,23,1) == 'K') $alv *= -1;
						$isumma[$maara] += $alv;
						$flip = 0;
				}
				else {
					$isumma[$maara] = (float) substr($rivi,24,12);
					if (substr($rivi,23,1) == 'K') $isumma[$maara] *= -1;
					$itili[$maara]  = (int) substr($rivi,13,4);
					$ikustp[$maara] = (int) substr($rivi,228,5);

					// Etsit‰‰‰n vastaava kustannuspaikka
					$query = "	SELECT tunnus
								FROM kustannuspaikka
								WHERE yhtio = '$kukarow[yhtio]'
								and tyyppi = 'P'
								and kaytossa != 'E'
								and nimi = '$ikustp[$maara]'";
					$result = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($result) == 1) {
						$row = mysql_fetch_assoc($result);
						$ikustp[$maara] = $row["tunnus"];
					}

					$iselite[$maara] = "Matkalasku ". $tpp . "." . $tpk . "." . $tpv . " " . trim(substr($rivi,240,50)) . " " . trim(substr($rivi,431,60));
					$ivero[$maara] = (float) substr($rivi,332,5);
					if ($ivero[$maara] != 0.0) $flip = 1;
				}
			}
			// T‰m‰ on Pretaxin palkkaohjelmiston normaali siirtomuoto ver 2 (Major Blue Palkat)
			elseif ($tiedostomuoto == "PRETAX") {
				if (!isset($tpv)) {
					$tpv=substr($rivi,39,4);
					$tpk=substr($rivi,43,2);
					$tpp=substr($rivi,45,2);
				}
				$isumma[$maara]  = (float) substr($rivi,117,16) / 100;
				$itili[$maara]   = (int) substr($rivi,190,7);

				// Kustannuspaikka
				$ikustp_tsk  	 = trim(substr($rivi,198,3));
				$ikustp[$maara]  = 0;

				if ($ikustp_tsk != "") {
					$query = "	SELECT tunnus
								FROM kustannuspaikka
								WHERE yhtio = '$kukarow[yhtio]'
								and tyyppi = 'K'
								and kaytossa != 'E'
								and nimi = '$ikustp_tsk'";
					$ikustpres = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($ikustpres) == 1) {
						$ikustprow = mysql_fetch_assoc($ikustpres);
						$ikustp[$maara] = $ikustprow["tunnus"];
					}
				}

				if ($ikustp_tsk != "" and $ikustp[$maara] == 0) {
					$query = "	SELECT tunnus
								FROM kustannuspaikka
								WHERE yhtio = '$kukarow[yhtio]'
								and tyyppi = 'K'
								and kaytossa != 'E'
								and koodi = '$ikustp_tsk'";
					$ikustpres = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($ikustpres) == 1) {
						$ikustprow = mysql_fetch_assoc($ikustpres);
						$ikustp[$maara] = $ikustprow["tunnus"];
					}
				}

				if (is_numeric($ikustp_tsk) and (int) $ikustp_tsk > 0 and $ikustp[$maara] == 0) {

					$ikustp_tsk = (int) $ikustp_tsk;

					$query = "	SELECT tunnus
								FROM kustannuspaikka
								WHERE yhtio = '$kukarow[yhtio]'
								and tyyppi = 'K'
								and kaytossa != 'E'
								and tunnus = '$ikustp_tsk'";
					$ikustpres = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($ikustpres) == 1) {
						$ikustprow = mysql_fetch_assoc($ikustpres);
						$ikustp[$maara] = $ikustprow["tunnus"];
					}
				}

				$iselite[$maara] = "Palkkatosite ". $tpp . "." . $tpk . "." . $tpv;
			}
			elseif ($tiedostomuoto == "AMMATTILAINEN") {

				$kentat = explode("\t", $rivi);

				// Tili
				$itili[$maara]   = (int) trim($kentat[0]);

				// Kustannuspaikka
				$ikustp_tsk  	 = trim($kentat[1]);
				$ikustp[$maara]  = 0;

				if ($ikustp_tsk != "") {
					$query = "	SELECT tunnus
								FROM kustannuspaikka
								WHERE yhtio = '$kukarow[yhtio]'
								and tyyppi = 'K'
								and kaytossa != 'E'
								and nimi = '$ikustp_tsk'";
					$ikustpres = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($ikustpres) == 1) {
						$ikustprow = mysql_fetch_assoc($ikustpres);
						$ikustp[$maara] = $ikustprow["tunnus"];
					}
				}

				if ($ikustp_tsk != "" and $ikustp[$maara] == 0) {
					$query = "	SELECT tunnus
								FROM kustannuspaikka
								WHERE yhtio = '$kukarow[yhtio]'
								and tyyppi = 'K'
								and kaytossa != 'E'
								and koodi = '$ikustp_tsk'";
					$ikustpres = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($ikustpres) == 1) {
						$ikustprow = mysql_fetch_assoc($ikustpres);
						$ikustp[$maara] = $ikustprow["tunnus"];
					}
				}

				if (is_numeric($ikustp_tsk) and (int) $ikustp_tsk > 0 and $ikustp[$maara] == 0) {

					$ikustp_tsk = (int) $ikustp_tsk;

					$query = "	SELECT tunnus
								FROM kustannuspaikka
								WHERE yhtio = '$kukarow[yhtio]'
								and tyyppi = 'K'
								and kaytossa != 'E'
								and tunnus = '$ikustp_tsk'";
					$ikustpres = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($ikustpres) == 1) {
						$ikustprow = mysql_fetch_assoc($ikustpres);
						$ikustp[$maara] = $ikustprow["tunnus"];
					}
				}

				// Selite
				$iselite[$maara] = "Palkkatosite $tpp.$tpk.$tpv / ".trim($kentat[2]);

				// Summa
				if (trim($kentat[3]) != "") {
					$isumma[$maara]  = (float) str_replace(",", ".", $kentat[3]);
				}
				else {
					$isumma[$maara]  = ((float) str_replace(",", ".", $kentat[4]));
				}

				//Tositepvm
				if (!isset($tpv)) {
					$tpv=substr($kentat[5],6,4);
					$tpk=substr($kentat[5],3,2);
					$tpp=substr($kentat[5],0,2);
				}
			}

			$maara++;

			// luetaan seuraava rivi failista
			$rivi = fgets($file);
		}

		fclose($file);

		unset($_FILES['userfile']['tmp_name']);
		unset($_FILES['userfile']['error']);

		$gokfrom = "palkkatosite"; // Pakotetaan virhe
		$tee = 'I';

		require ('tosite.php');

		exit;
	}


	echo "<font class='head'>".t("Palkka- ja matkalaskuaineiston sis‰‰nluku")."</font><hr>";
	echo "<form method='post' name='sendfile' enctype='multipart/form-data' action='$PHP_SELF'>
			<table>
			<tr><th>".t("Valitse tiedostomuoto")."</th><td>
			<select name = 'tiedostomuoto'>
			<option value ='PRETAX'>Pretax palkkatosite</option>
			<option value ='AMMATTILAINEN'>Ammattilainen palkkatosite</option>
			<option value ='M2MATKALASKU'>M2 Matkalasku</option>
			</select>
			</td></tr>
			<tr><th>".t("Valitse tiedosto").":</th>
				<td><input name='userfile' type='file'></td>
				<td class='back'><input type='submit' value='".t("L‰het‰")."'></td>
			</tr>
			</table>
			</form>";

	require ("inc/footer.inc");

?>