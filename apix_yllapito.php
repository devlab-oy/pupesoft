<?php

	require ("inc/parametrit.inc");

	echo "<font class='head'>".t("APIX Ylläpitoa")."</font><hr>";
	echo "<br><br>";

	echo "<form action = '$PHP_SELF' method = 'post'>
			<input type='hidden' name='tee' value='SendRegistrationInfo'>";

	echo "<table>";
	echo "<tr><th>Läheta yhtiön info APIX:lle:</th></tr>";
	echo "<tr><td><input type='submit' value='1. SendRegistrationInfo'></td></tr>";
	echo "</table>";
	echo "</form><br><br>";

	echo "<form action = '$PHP_SELF' method = 'post'>
			<input type='hidden' name='tee' value='RetrieveTransferID'>";
	echo "<table>";
	echo "<tr><th colspan='2'>Nouda verkkotunnus ja salasana APIX:sta:</th></tr>";
	echo "<tr><th>USERID</th><td><input type='text' size='30' name='username' value=''></td></tr>";
	echo "<tr><th>PASSWORD</th><td><input type='text' size='30' name='password' value=''></td></tr>";
	echo "<tr><td colspan='2'><input type='submit' value='2. RetrieveTransferID'></td></tr>";
	echo "</table>";
	echo "</form><br><br>";

	if ($tee != "") {
		$yhtiorow["ytunnus"] = tulosta_ytunnus($yhtiorow["ytunnus"]);
		$yhtiorow["www"] = str_replace(array("http://", "https://"), "", $yhtiorow["www"]);
	}

	if ($tee == "SendRegistrationInfo") {

		echo "Apix SendRegistrationInfo:<br>";

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

		$url = "https://test-api.apix.fi/registration";

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

		$response = preg_replace("/<([^\/])/", "\n<$1", utf8_decode($response));

		echo "<pre>",htmlentities($response),"</pre>";
	}

	if ($tee == "RetrieveTransferID") {

		echo "Apix RetrieveTransferID:<br>";

		$url		= "https://test-api.apix.fi/app-transferID";
		$timestamp	= gmdate("YmdHis");
		$pw_digest	= substr(hash('sha256', $password), 0, 64);
		$digest_src = $yhtiorow["ytunnus"]."+y-tunnus+".$username."+".$timestamp."+".$pw_digest;
		$dt 		= substr(hash("sha256", $digest_src), 0, 64);

		$real_url 	= "$url?id=$yhtiorow[ytunnus]&idq=y-tunnus&uid=$username&ts=$timestamp&d=SHA-256:$dt";

		$ch = curl_init($real_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$response = curl_exec($ch);

		curl_close($ch);

		$response = preg_replace("/<([^\/])/", "\n<$1", utf8_decode($response));

		echo "<pre>",htmlentities($response),"</pre>";
	}

	require ("inc/footer.inc");
?>