<?php

require ("../inc/parametrit.inc");

// tehd‰‰n tiedostolle uniikki nimi
$filename = "../datain/$tyyppi-order-".md5(uniqid(rand(),true))."-".$_FILES['userfile']['name'];

if (move_uploaded_file($_FILES['userfile']['tmp_name'], $filename)) {

	$path_parts = pathinfo($filename);

	if (strtoupper($path_parts["extension"])!="TXT") {
		echo "<font class='error'>".t("Tiedosto")." $filename ".t("ei tunnu aineistolta")."!<br>".t("Pit‰‰ olla .txt (nyt")." $path_parts[extension]).<br>".t("Tarkista tiedosto")."!<br></font>";
		exit;
	}

	echo "<font class='message'>".t("K‰sittelen")." $tyyppi ".t("tiedoston")."</font><br>";

	if ($tyyppi=='pos') {
		// tarvitaan $filename
		require ("../inc/tilaus_in.inc");
	}

	if ($tyyppi=='edi') {
		// tarvitaan $filename
		echo "<pre>";
		require ("editilaus_in.inc");
		echo "$edi_ulos";
		echo "</pre>";
	}

	if ($tyyppi=='futursoft') {
		// tarvitaan $filename
		echo "<pre>";
		$edi_tyyppi = "futursoft";
		require ("editilaus_in.inc");
		echo "$edi_ulos";
		echo "</pre>";
	}

	if ($tyyppi=='yct') {
		// tarvitaan $filename
		require ("../inc/tilaus_in.inc");
	}
}

else {

	echo "<font class='head'>".t("Tilausten sis‰‰nluku")."</font><hr>";

	echo "<form enctype='multipart/form-data' name='sendfile' action='$PHP_SELF' method='post'>

		<table>
		<tr>
			<th>".t("Valitse tiedosto")."</th>
			<td><input type='file' name='userfile'></td>
		</tr>
		<tr>
			<th>".t("Tiedoston tyyppi")."</th>
			<td><select name='tyyppi'>
		 		<option value='edi'>".t("Editilaus")."
		 		<option value='futursoft'>".t("Futursoft")."
		 		<option value='pos'>".t("Kassap‰‰te")."
		 		<option value='yct'>Yamaha Center
				</select>
		 	</td>
		 </tr>
		</table>

		<br><input type='submit' value='".t("K‰sittele tiedosto")."'>

		</form>";
}

require ("../inc/footer.inc");

?>
