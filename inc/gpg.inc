<?php

    // PGP enkoodaa viesti
    //
    // otetaan sisään $recipient ja $message
    // palautetaan $encrypted_message

	$gpg       = "/usr/bin/gpg";
	$recipient = escapeshellarg($recipient);

	$msgfile1   = "/tmp/$kukarow[yhtio]".md5(uniqid(rand(),true)).".txt";
	$msgfile2   = "/tmp/$kukarow[yhtio]".md5(uniqid(rand(),true)).".gpg";

	if (!$handle = fopen($msgfile1, "w"))
		die("filen luonti epäonnistui!");

	if (fwrite($handle, $message) === FALSE)
		die("failin kirjoitus epäonnistui");

	fclose($handle);

	#echo "Recipient: ".htmlentities($recipient)."<br>";
	#echo "Message: $message<br>";

	$test = shell_exec("$gpg --homedir /root/.gnupg --check-sigs $recipient 2>&1 | grep error");

	if (strpos($test,"error")) {
		echo "<font class='error'>";
		echo "Vastaanottajan tiedot vialliset!<br><br>";
		echo "Recipient: ".htmlentities($recipient)."<br><br>";
		echo "$test<br><br>";
		echo "</font>";
	}
	else {
		$test = shell_exec("$gpg --homedir /root/.gnupg -e -r $recipient -o $msgfile2 $msgfile1");

		if (strpos($test,"error")) {
			echo "<font class='error'>";
			echo "GPG salaus epäonnistui!<br><br>";
			echo "$gpg --homedir /root/.gnupg -e -r ".htmlentities($recipient)." -o $msgfile2 $msgfile1<br><br>";
			echo "$test<br><br>";
			echo "</font>";
		}

		if (!$handle = fopen($msgfile2, "rb"))
			die("failin $msgfile2 avaus epäonnistui!");

		$encrypted_message = fread($handle, filesize($msgfile2));

		fclose($handle);

		shell_exec("rm -f $msgfile1 $msgfile2");
	}

?>