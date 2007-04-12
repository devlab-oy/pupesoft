<?php

	require ("inc/parametrit.inc");

	echo "<font class='head'>Tekstiviestin lähetys</font><hr>";

	if ($tee == 'laheta') {

		if (strlen($teksti) > 160) {
			echo "<font class='error'>VIRHE: Tekstiviestin maksimipituus on 160 merkkiä!</font><br>";
		}

		$kotinum = str_replace ("-", "", $kotinum);
		$ok = 1;

		if (is_numeric($kotinum) and strlen($teksti) > 0 and strlen($teksti) <= 160 and $sms_palvelin != "" and $sms_user != "" and $sms_pass != "") {
			$teksti = urlencode($teksti);
			$retval = file_get_contents("$sms_palvelin?user=$sms_user&pass=$sms_pass&numero=$kotinum&viesti=$teksti");
			if (trim($retval) == "0") $ok = 0;
			$teksti = urldecode($teksti);
		}

		if ($ok == 1) {
			echo "<font class='error'>VIRHE: Tekstiviestin lähetys epäonnistui! $retval</font><br><br>";
		}

		if ($ok == 0) {
			echo "<font class='message'>Tekstiviesti lähetetty!</font><br><br>";
		}

		$tee = "";
	}

	if ($tee == "") {

		echo "<form name='form' action = '$PHP_SELF' method='post' name='tekstari'>
			<input type='hidden' name='tee' value = 'laheta'>

			<table>
				<tr>
					<th>Puh.</th>
					<td><input type='text' size='20' name='kotinum' value='$kotinum'></td>
				</tr>
				<tr>
					<th>Viesti</th>
					<td><textarea name='teksti' cols='45' rows='6' wrap='soft'>$teksti</textarea></td>
				</tr>
			</table>

			<br><input type='submit' value = 'Lähetä'>

			</form>";

	}

	require ("inc/footer.inc");

?>