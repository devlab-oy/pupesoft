<?php

require ("inc/parametrit.inc");

echo "<font class='head'>".t("Lis‰‰ ostolasku k‰sittelyjonoon")."</font><hr>";

$dir = $pupe_root_polku."/".$yhtiorow['skannatut_laskut_polku'];

if (!is_dir($dir) or !is_writable($dir)) {
	return false;
}

if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {

	$path_parts = pathinfo($_FILES['userfile']['name']);
	$name		= strtoupper($path_parts['filename']);
	$ext		= strtoupper($path_parts['extension']);

	if ($ext != "JPG" and $ext != "PNG" and $ext != "GIF" and $ext != "PDF") {
		die ("<font class='error'><br>".t("Ainoastaan kuva ja pdf tiedostot sallittuja")."!</font>");
	}

	if ($_FILES['userfile']['size'] == 0) {
		die ("<font class='error'><br>".t("Tiedosto on tyhj‰")."!</font>");
	}

	$query = "SHOW variables like 'max_allowed_packet'";
	$result = pupe_query($query);
	$varirow = mysql_fetch_row($result);

	if ($filesize > $varirow[1]) {
		die ("<font class='error'>".t("Liitetiedosto on liian suuri")."! (mysql: $varirow[1]) </font>");
	}

	// Katotaan, ettei samalla nimell‰ oo jko samaa laskua jonossa
	if (file_exists($dir."/".$_FILES['userfile']['name']) and $_FILES['userfile']['size'] == filesize($dir."/".$_FILES['userfile']['name'])) {
		die ("<font class='error'>".t("Lasku on jo k‰sittelyjonossa")."!</font>");
	}

	// Katotaan, ettei samalla nimell‰ oo jo laskua jonossa
	if (file_exists($dir."/".$_FILES['userfile']['name'])) {

		$kala = 1;
		$filename = $_FILES['userfile']['name'];

		while (file_exists($dir."/".$filename)) {
			$filename = $kala."_".$_FILES['userfile']['name'];

			$kala++;
		}

		$_FILES['userfile']['name'] = $filename;
	}

	if (rename($_FILES['userfile']['tmp_name'], $dir."/".$_FILES['userfile']['name'])) {
		echo "<br>".t("Lasku").": ".$_FILES['userfile']['name']." ".t("siirretty k‰sittelyjonoon")."!<br><br>";
	}
	else {
		echo "<font class='error'>".t("Laskua ei voitu tallentaa k‰sittelyjonoon")."!</font>";
	}
}

echo "<form method='post' name='sendfile' enctype='multipart/form-data'>
		<table>
		<tr><th>".t("Valitse tiedosto").":</th>
			<td><input name='userfile' type='file'></td>
			<td class='back'><input type='submit' value='".t("L‰het‰")."'></td>
		</tr>
		</table>
		</form>";

require ("inc/footer.inc");