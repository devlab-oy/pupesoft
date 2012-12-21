<?php

require ("../inc/parametrit.inc");

// tehd‰‰n tiedostolle uniikki nimi
$filename = "$pupe_root_polku/datain/$tyyppi-order-".md5(uniqid(rand(),true)).".txt";

if (move_uploaded_file($_FILES['userfile']['tmp_name'], $filename)) {

	$path_parts = pathinfo($filename);

	if ($path_parts["extension"] != "" and strtoupper($path_parts["extension"]) != "TXT") {
		echo "<font class='error'>".t("Tiedosto")." $filename ".t("ei tunnu aineistolta")."!<br>".t("Pit‰‰ olla .txt (nyt")." $path_parts[extension]).<br>".t("Tarkista tiedosto")."!<br></font>";
		exit;
	}

	echo "<font class='message'>".t("K‰sittelen")." $tyyppi ".t("tiedoston")."</font><br><br>";

	if ($tyyppi == 'multi') {
		// tarvitaan $filename
		require ("inc/tilaus_in_multi.inc");
	}

	if ($tyyppi == 'pos') {
		// tarvitaan $filename
		require ("inc/tilaus_in.inc");
	}

	if ($tyyppi == 'edi') {
		// tarvitaan $filename
		echo "<pre>";
		require ("editilaus_in.inc");
		echo "</pre>";
	}

	if ($tyyppi == 'futursoft') {
		// tarvitaan $filename
		echo "<pre>";
		$edi_tyyppi = "futursoft";
		require ("editilaus_in.inc");
		echo "</pre>";
	}

	if ($tyyppi == 'magento') {
		// tarvitaan $filename
		echo "<pre>";
		$edi_tyyppi = "magento";
		require ("editilaus_in.inc");
		echo "</pre>";
	}

	if ($tyyppi == 'edifact911') {
		// tarvitaan $filename
		echo "<pre>";
		$edi_tyyppi = "edifact911";
		require ("editilaus_in.inc");
		echo "</pre>";
	}

	if ($tyyppi == 'yct') {
		// tarvitaan $filename
		require ("inc/tilaus_in.inc");
	}
	
	if ($tyyppi == 'asnui') {

		if (copy($filename, $teccomkansio.'/'.$path_parts["basename"])) {
			require ("sisaanlue_teccom_asn.php");		
		}
		else {
			echo "Kopiointi ep‰onnistui!";
		}
	}
}

else {

	echo "<font class='head'>".t("Tilausten sis‰‰nluku")."</font><hr>";

	echo "<form enctype='multipart/form-data' name='sendfile' method='post'>

		<table>
		<tr>
			<th>".t("Valitse tiedosto")."</th>
			<td><input type='file' name='userfile'></td>
		</tr>
		<tr>
			<th>".t("Tiedoston tyyppi")."</th>
			<td><select name='tyyppi'>
		 		<option value='edi'>".t("Editilaus")."</option>
		 		<option value='futursoft'>Futursoft</option>
		 		<option value='magento'>Magento</option>
		 		<option value='pos'>".t("Kassap‰‰te")."</option>
		 		<option value='yct'>Yamaha Center</option>
				<option value='edifact911'>Orders 91.1</option>
		 		<option value='multi'>".t("Useita asiakkaita")."</option>
				<option value='asnui'>".t("ASN-sanoma")."</option>
				</select>
		 	</td>
		 </tr>
		</table>

		<br><input type='submit' value='".t("K‰sittele tiedosto")."'>

		</form>";
}

require ("inc/footer.inc");
