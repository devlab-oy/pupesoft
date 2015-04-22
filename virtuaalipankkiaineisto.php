<?php
	require ("inc/parametrit.inc");
	require ("inc/lm03sisaan.inc");
	
	// katotaan onko faili uploadttu
	if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
		$userfile = $_FILES['userfile']['name'];
		$filenimi = $_FILES['userfile']['tmp_name'];
		$ok = 1;
	}

	if ($ok == 1) {
		$fd = fopen ($filenimi, "r");
		if (!($fd)) {
			echo "<font class='error'>Tiedosto '$filenimi' ei auennut!</font>";
			exit;
		}

		kasittelelm03($fd);
		echo t("Aineisto käsitelty.")."<br/><br/>";
	}
		
	echo "<font class='head'>Virtuaalipankkitestaus<hr></font>";
	echo "<form enctype='multipart/form-data' action = '' method = 'post'><table>";
	echo "<tr><th>".t("Aineiston käsittely")."</th>";
	echo "<td><input type='file' name='userfile'></td></tr>";
	echo "<tr><th></th><td><input type='submit' value = 'Käsitele tiedosto'></td></tr>";
	echo "</table></form>";
?>
