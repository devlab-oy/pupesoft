<?php

	require ("inc/parametrit.inc");

	$status_message1 = "";
	$status_message2 = "";

	if ($tee != "") {
		$yhtiorow["ytunnus"] = tulosta_ytunnus($yhtiorow["ytunnus"]);
		$yhtiorow["www"] = str_replace(array("http://", "https://"), "", $yhtiorow["www"]);
	}

	if ($tee == "SendRegistrationInfo") {

		$xmlfile = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>
					<Request version=\"1.0\">
					<Content>
					<Group>
					<Value type=\"Ytunnus\">{$yhtiorow["ytunnus"]}</Value>
					<Value type=\"Name\">{$yhtiorow["nimi"]}</Value>
					<Value type=\"FieldOfBusiness\"></Value>
					<Value type=\"CompanyForm\"></Value>
					<Value type=\"Email\">{$yhtiorow["email"]}</Value>
					<Value type=\"Www\">{$yhtiorow["www"]}</Value>
					<Value type=\"Phonenumber\">{$yhtiorow["puhelin"]}</Value>
					<Value type=\"ContactPerson\"></Value>
					<Value type=\"LanguageCoded\">fi</Value>
					</Group>
					<Group>
					<Value type=\"AddressType\">Official</Value>
					<Value type=\"Street1\">{$yhtiorow["osoite"]}</Value>
					<Value type=\"PostalOffice\">{$yhtiorow["postitp"]}</Value>
					<Value type=\"PostalCode\">{$yhtiorow["postino"]}</Value>
					<Value type=\"Country\">{$yhtiorow["maa"]}</Value>
					</Group>
					<Group>
					<Value type=\"AddressType\">Visiting</Value>
					<Value type=\"Street1\">{$yhtiorow["osoite"]}</Value>
					<Value type=\"PostalOffice\">{$yhtiorow["postitp"]}</Value>
					<Value type=\"PostalCode\">{$yhtiorow["postino"]}</Value>
					<Value type=\"Country\">{$yhtiorow["maa"]}</Value>
					</Group>
					<Group>
					<Value type=\"AddressType\">Billing</Value>
					<Value type=\"Street1\">{$yhtiorow["osoite"]}</Value>
					<Value type=\"PostalOffice\">{$yhtiorow["postitp"]}</Value>
					<Value type=\"PostalCode\">{$yhtiorow["postino"]}</Value>
					<Value type=\"Country\">{$yhtiorow["maa"]}</Value>
					</Group>
					</Content>
					</Request>";

		$xmlfile = utf8_encode($xmlfile);

		#$url = "https://test-api.apix.fi/registration";
		$url = "https://api.apix.fi/registration";

		$real_url = "$url?id=$yhtiorow[ytunnus]&idq=y-tunnus";

		// Kirjoitetaan XML tempfailiin
		$tempfile = tmpfile();
		fwrite($tempfile, $xmlfile);
		rewind($tempfile);

		$ch = curl_init($real_url);
		curl_setopt($ch, CURLOPT_PUT, true);
		curl_setopt($ch, CURLOPT_INFILE, $tempfile);
		curl_setopt($ch, CURLOPT_INFILESIZE, strlen($xmlfile));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$response = curl_exec($ch);

		curl_close($ch);
		fclose($tempfile);

		$xml = simplexml_load_string($response);

		$status_message1 = "<br><br>";

		if ($xml->Status == "OK") {
			$status_message1 .= "<font class='ok'>Lähetys onnistui!</font>";
		}
		else {
			$status_message1 .= "<font class='error'>Lähetys epäonnistui!<br><br>";
			foreach ($xml->FreeText as $teksti) {
				$status_message1 .= $teksti."<br>";
			}
			$status_message1 .= "</font>";
		}
	}

	if ($tee == "RetrieveTransferID") {

		#$url		= "https://test-api.apix.fi/app-transferID";
		$url		= "https://api.apix.fi/app-transferID";
		$timestamp	= gmdate("YmdHis");
		$pw_digest	= substr(hash('sha256', trim($password)), 0, 64);
		$digest_src = $yhtiorow["ytunnus"]."+y-tunnus+".trim($username)."+".$timestamp."+".$pw_digest;
		$dt 		= substr(hash("sha256", $digest_src), 0, 64);
		$real_url 	= "$url?id=$yhtiorow[ytunnus]&idq=y-tunnus&uid=$username&ts=$timestamp&d=SHA-256:$dt";

		$ch = curl_init($real_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$response = curl_exec($ch);
		curl_close($ch);

		$xml = simplexml_load_string($response);

		$status_message2 = "<br><br>";

		if ($xml->Status == "OK") {

			$transfer_id = "";
			$transfer_key = "";

			foreach ($xml->Content->Group->Value as $value) {
				if ($value->attributes()->type == "TransferKey") {
					$transfer_key = $value;
				}
				if ($value->attributes()->type == "TransferID") {
					$transfer_id = $value;
				}
			}

			if ($transfer_id != "" and $transfer_key != "") {
				$status_message2 .= "<font class='ok'>Päivitys onnistui, APIX laskutus käyttöönotettu!</font>";

				$query = "	UPDATE yhtion_parametrit SET
							apix_tunnus = '$transfer_id',
							apix_avain = '$transfer_key',
							verkkolasku_lah = 'apix',
							finvoice_senderpartyid = '$yhtiorow[ovttunnus]',
							finvoice_senderintermediator = '003723327487'
							WHERE yhtio = '$kukarow[yhtio]'";
				$query = mysql_query($query) or pupe_error($query);
			}
			else {
				$status_message2 .= "<font class='error'>Päivitys epäonnistui!<br><br>";
				$status_message2 .= "<font class='error'>Asiakastiedot olivat tyhjää!<br>";
			}
		}
		else {
			$status_message2 .= "<font class='error'>Päivitys epäonnistui!<br><br>";
			foreach ($xml->FreeText as $teksti) {
				$status_message2 .= $teksti."<br>";
			}
			$status_message2 .= "</font>";
		}
	}

	echo "<font class='head'>".t("APIX Laskutuksen käyttöönotto")."</font><hr>";

	echo "<br>";
	echo "<img src='{$palvelin2}pics/apix_logo.png'>";
	echo "<br><br>";

	echo "<font class='message'>Vaihe 1: Lähetä yhtiötiedot APIX:lle</font><hr>";

	echo "<form method = 'post' class='multisubmit'>";
	echo "<input type='hidden' name='tee' value='SendRegistrationInfo'>";
	echo "<input type='submit' value='Lähetä yhtiötiedot klikkaamalla tästä'>";
	echo "</form>";
	echo $status_message1;
	echo "<br><br>";

	echo "<font class='message'>Vaihe 2: Rekisteröidy APIX asiakkaaksi ja hanki verkkopostimerkkejä heidän verkkokaupasta</font><hr>";
#	echo "<form target='top' action='https://test-registration.apix.fi' method='get'>";
	echo "<form target='top' action='https://registration.apix.fi' method='get'>";
	echo "<input type='submit' value='Siirry APIX rekisteröintiin klikkaamalla tästä'>";
	echo "</form>";

	echo "<br><br>";

	echo "<font class='message'>Vaihe 3: Ota APIX laskutus käyttöön Pupesoft:issa antamalla APIX käyttäjätietosi</font><hr>";

	echo "<form method = 'post' class='multisubmit'>";
	echo "<input type='hidden' name='tee' value='RetrieveTransferID'>";
	echo "<table>";
	echo "<tr><th>Käyttäjätunnus</th><td><input type='text' size='20' name='username' value='$username'></td></tr>";
	echo "<tr><th>Salasana</th><td><input type='password' size='20' name='password' value='$password'></td></tr>";
	echo "</table>";
	echo "<br>";
	echo "<input type='submit' value='Ota APIX laskutus käyttöön klikkaamalla tästä'>";
	echo $status_message2;

	echo "</form>";

	require ("inc/footer.inc");

?>