<?php

if ($tee == "FILE") {
	if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {

		/** Käsiteltävän filen nimi **/
		$kasiteltava_tiedoto_path = $_FILES['userfile']['tmp_name'];

		$excelrivit = pupeFileReader($kasiteltava_tiedoto_path, $ext);

		// Otsikkorivi veks
		$null = array_shift($excelrivit);

		foreach ($excelrivit as $rivinumero => $rivi) {

			$rivinumero+=2;

			$tila	 = '';
			$valinta = '';

			$asiakasnro = $rivi[1];
			$ytunnus 	= $rivi[2];

			$yhtio   	= $rivi[5];

			$suunpvm 	= "";
			$suun 		= "";

			if (is_numeric($rivi[9])) {
				// Excelissä päivämäärät lasketaan päivissä päivästä 1.1.1900
				$suun = explode("-", date("d-m-Y", (($rivi[9]-25569) * 86400)));
			}
			elseif (strpos(trim($rivi[9]), "/") !== FALSE) {
				$suun = explode("/", trim($rivi[9]));
			}
			elseif (strpos(trim($rivi[9]), ".") !== FALSE) {
				$suun = explode(".", trim($rivi[9]));
			}

			if ((int) $suun[2] < 1000) {
				$suun[2] += 2000;
			}

			if (checkdate(sprintf('%02d',$suun[1]),sprintf('%02d',$suun[0]),sprintf('%04d',$suun[2]))) {
				$suunpvm = sprintf('%04d',$suun[2])."-".sprintf('%02d',$suun[1])."-".sprintf('%02d',$suun[0]);
			}

			$kampanjat 	= $rivi[10];

			$kaytypvm 	= "";
			$kayty		= "";

			if (is_numeric($rivi[11])) {
				// Excelissä päivämäärät lasketaan päivissä päivästä 1.1.1900
				$kayty = explode("-", date("d-m-Y", (($rivi[11]-25569) * 86400)));
			}
			elseif (strpos(trim($rivi[11]), "/") !== FALSE) {
				$kayty = explode("/", trim($rivi[11]));
			}
			elseif (strpos(trim($rivi[11]), ".") !== FALSE) {
				$kayty = explode(".", trim($rivi[11]));
			}

			if ((int) $kayty[2] < 1000) {
				$kayty[2] += 2000;
			}

			if (checkdate(sprintf('%02d',$kayty[1]),sprintf('%02d',$kayty[0]),sprintf('%04d',$kayty[2]))) {
				$kaytypvm = sprintf('%04d',$kayty[2])."-".sprintf('%02d',$kayty[1])."-".sprintf('%02d',$kayty[0]);
			}

			$kilsat = $rivi[12];
			$lahto  = $rivi[13];

			if (strlen($lahto) == 1 or strlen($lahto) == 2) {
				$lahto = sprintf('%02d',$lahto).":00:00";
			}
			if (strlen($lahto) == 5) {
				$lahto = sprintf('%02d',substr($lahto,0,2)).":".sprintf('%02d',substr($lahto,3,2)).":00";
			}
			if (strlen($lahto) == 8) {
				$lahto = sprintf('%02d',substr($lahto,0,2)).":".sprintf('%02d',substr($lahto,3,2)).":".sprintf('%02d',substr($lahto,6,2));
			}
			if (strlen($lahto) > 8) {
				$lahto = sprintf('%02d',substr($lahto,0,2)).":".sprintf('%02d',substr($lahto,3,2)).":".sprintf('%02d',substr($lahto,6,2));
			}

			$paluu = $rivi[14];

			if (strlen($paluu) == 1 or strlen($paluu) == 2) {
				$paluu = sprintf('%02d',$paluu).":00:00";
			}
			if (strlen($paluu) == 5) {
				$paluu = sprintf('%02d',substr($paluu,0,2)).":".sprintf('%02d',substr($paluu,3,2)).":00";
			}
			if (strlen($paluu) == 8) {
				$paluu = sprintf('%02d',substr($paluu,0,2)).":".sprintf('%02d',substr($paluu,3,2)).":".sprintf('%02d',substr($paluu,6,2));
			}
			if (strlen($paluu) > 8) {
				$paluu = sprintf('%02d',substr($paluu,0,2)).":".sprintf('%02d',substr($paluu,3,2)).":".sprintf('%02d',substr($paluu,6,2));
			}

			$pvrahat	= $rivi[15];
			$kommentit	= $rivi[16];

			$virhe = 0;

			if (trim($kaytypvm) == '' and trim($suunpvm) != '') {
				$kumpi = "Viikkosuunnitelma";
				$kpvm  = $suunpvm;
				if ($kommentit == '') {
					$kommentit = "$kukarow[nimi] ".t("suunnittelee käyvänsä asiakkaan luona")." $pvm.";
				}
			}
			elseif (trim($kaytypvm) == '') {
				echo "VIRHE: Rivillä $rivinumero. ".t("Päivämäärä puuttui tai se oli virheellinen")."!<br>";
				$virhe = 1;
			}

			if (trim($kaytypvm) != '') {
				$kumpi = "Asiakaskäynti";
				$kpvm  = $kaytypvm;
			}
			elseif (trim($suunpvm) == '') {
				echo "VIRHE: Rivillä $rivinumero. ".t("Päivämäärä puuttui tai se oli virheellinen")."!<br>";
				$virhe = 1;
			}

			if ($lahto == '') {
				$lahto = "00:00:00";
			}
			if ($paluu == '') {
				$paluu = "00:00:00";
			}

			$query = "	SELECT yhtio, tunnus, ytunnus
						FROM asiakas
						WHERE yhtio 	= '$yhtio'
						and asiakasnro  = '$asiakasnro'
						and ytunnus 	= '$ytunnus'";
			$result = pupe_query($query);

			if (mysql_num_rows($result) == 0) {
				echo t("VIRHE: Rivillä")." $rivinumero. ".t("Asiakasta ei löydy")."! $query<br>";
				$virhe = 1;
			}
			else {
				$asiakasrow = mysql_fetch_assoc($result);
			}

			if ($virhe == 0) {
				$query = "	SELECT yhtio
							FROM kalenteri
							WHERE
							yhtio 			 = '$yhtio'
							and kuka 		 = '$kukarow[kuka]'
							and pvmalku 	 = '$kpvm $lahto'
							and pvmloppu 	 = '$kpvm $paluu'
							and asiakas 	 = '$asiakasrow[ytunnus]'
							and liitostunnus = '$asiakasrow[tunnus]'
							and tapa 		 = '$kumpi'
							and tyyppi		 = 'kalenteri'";
				$result = pupe_query($query);

				if (mysql_num_rows($result) == 0) {

					if ($kumpi == "Asiakaskäynti") {

						$query = "	SELECT tunnus
									FROM kalenteri
									WHERE
									yhtio 					= '$yhtio'
									and kuka 				= '$kukarow[kuka]'
									and left(pvmalku,10) 	= '$suunpvm'
									and left(pvmloppu,10) 	= '$suunpvm'
									and asiakas 	 		= '$asiakasrow[ytunnus]'
									and liitostunnus 		= '$asiakasrow[tunnus]'
									and tapa 				= 'Viikkosuunnitelma'
									and tyyppi				= 'kalenteri'";
						$result = pupe_query($query);

						if (mysql_num_rows($result) != 0) {

							$prow = mysql_fetch_array($result);

							$query = "	UPDATE kalenteri
										SET
										tyyppi 		= 'memo'
										WHERE yhtio = '$yhtio'
										and tunnus 	= '$prow[tunnus]'";
							$result = pupe_query($query);
						}
					}

					$query = "	INSERT INTO kalenteri
								SET
								yhtio 		 = '$yhtio',
								kuka 		 = '$kukarow[kuka]',
								pvmalku 	 = '$kpvm $lahto',
								pvmloppu 	 = '$kpvm $paluu',
								asiakas 	 = '$asiakasrow[ytunnus]',
								liitostunnus = '$asiakasrow[tunnus]',
								kentta01 	 = '$kommentit',
								tapa 		 = '$kumpi',
								kentta02	 = '$kampanjat',
								kentta03	 = '$kilsat',
								kentta04	 = '$pvrahat',
								kokopaiva	 = 'x',
								tyyppi 		 = 'kalenteri'";
					$result = pupe_query($query);
				}
				elseif ($kumpi == "Asiakaskäynti") {
					$query = "	UPDATE kalenteri
								SET
								yhtio 		 = '$yhtio',
								kuka 		 = '$kukarow[kuka]',
								pvmalku 	 = '$kpvm $lahto',
								pvmloppu 	 = '$kpvm $paluu',
								asiakas 	 = '$asiakasrow[ytunnus]',
								liitostunnus = '$asiakasrow[tunnus]',
								kentta01 	 = '$kommentit',
								tapa 		 = '$kumpi',
								kentta02	 = '$kampanjat',
								kentta03	 = '$kilsat',
								kentta04	 = '$pvrahat',
								tyyppi 		 = 'kalenteri'
								WHERE yhtio  	 = '$yhtio'
								and kuka 	 	 = '$kukarow[kuka]'
								and pvmalku  	 = '$kpvm $lahto'
								and pvmloppu 	 = '$kpvm $paluu'
								and asiakas 	 = '$asiakasrow[ytunnus]'
								and liitostunnus = '$asiakasrow[tunnus]'
								and tapa 	 	 = '$kumpi'
								and tyyppi	 	 = 'kalenteri'";
					$result = pupe_query($query);
				}
				else {
					echo t("VIRHE: Rivillä")." $rivinumero. ".t("Tieto on jo viikkosuunnitelmassasi")."!<br>";
				}
			}
		}

		if ($kumpi != '') {

			$kaladate = explode('-', $suunpvm);
			$kausi 	  = $kaladate[0].sprintf('%02d',date("W", mktime(0, 0, 0, $kaladate[1], $kaladate[2], $kaladate[0])));

			$vstk = $kumpi;

			if ($vstk == 'Viikkosuunnitelma') {
				$sel1 = "CHECKED";
			}
			if ($vstk == 'Asiakaskäynti') {
				$sel2 = "CHECKED";
			}
		}

		$tee = "";
	}
	else {
		$tee = "";
	}
}

if ($tee == 'VALITSE_TIEDOSTO') {

	echo "<form method='post' name='sendfile' enctype='multipart/form-data'>
			<input type='hidden' name='tee' value='FILE'>
			<input type='hidden' name='vaiht' value='$vaiht'>
			<input type='hidden' name='kausi' value='$kausi'>
			<input type='hidden' name='vstk' value='$vstk'>
			<font class='message'>".t("Tiedostomuoto").":</font><br><br>";

	echo "	<table>
			<tr><th colspan='10'>".t("Suunnitelma")."</th><th colspan='7'>".t("Toteutuneet")."</th></tr>
			<tr>
				<td>".t("Asiakas")."</td>
				<td>".t("Asiakasnumero")."</td>
				<td>".t("Ytunnus")."</td>
				<td>".t("Postitp")."</td>
				<td>".t("Postino")."</td>
				<td>".t("Yhtiöt")."</td>
				<td>".t("Myyjä")."</td>
				<td>".t("Email")."</td>
				<td>".t("Puhelin")."</td>
				<td>".t("Pvm")."</td>
				<td>".t("Kampanjat")."</td>
				<td>".t("PvmKäyty")."</td>
				<td>".t("Km")."</td>
				<td>".t("Lähtö")."</td>
				<td>".t("Paluu")."</td>
				<td>".t("PvRaha")."</td>
				<td>".t("Kommentit")."</td>
			</tr>
			</table>";

	echo "	<br>
			<table>
			<tr>
			<th>".t("Valitse tiedosto").":</th>
			<td><input name='userfile' type='file'></td>
			<td class='back'><input type='submit' value='".t("Läheta")."'></td>
			</tr>
			</table>
			</form>";
}
