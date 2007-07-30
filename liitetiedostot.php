<?php

require ('inc/parametrit.inc');

echo "<font class='head'>".t("Liitetiedostot")."</font><hr>";

if (! isset($_GET['liitos']) and ! isset($_GET['id'])) {
	echo "<form action='' method='get'>
		<table>
		<tr>
			<th>" . t("Tyyppi") ."</th>
			<td>
				<select name='liitos'>
				<option value='lasku'>" . t('Lasku'). "</option>
				</select>
			</td>
		</tr>
		<tr>
			<th>" . t('Tunnus') . "</th>
			<td><input type='text' name='id'/></td>
			<td class='back'><input type='submit' name='submit' value='" .t('Etsi') . "'</td>
		</tr>
	</table>
	</form>";
}

// uusi upload?
if (isset($_POST['tee'])
and $_POST['tee'] == 'file'
and isset($_GET['liitos'])
and isset($_GET['id'])
and is_uploaded_file($_FILES['userfile']['tmp_name'])) {

    $errormsg = '';

	$filetype = $_FILES['userfile']['type'];
	$filesize = $_FILES['userfile']['size'];
	$filename = $_FILES['userfile']['name'];

	$query = "SHOW variables like 'max_allowed_packet'";
	$result = mysql_query($query) or pupe_error($query);
	$varirow = mysql_fetch_array($result);

	$path_parts = pathinfo($_FILES['userfile']['name']);
	$ext = $path_parts['extension'];
	if (strtoupper($ext) == "JPEG") $ext = "jpg";

	// extensio pit‰‰ olla oikein
	if (strtoupper($ext) != "JPG" and strtoupper($ext) != "PNG" and strtoupper($ext) != "GIF" and strtoupper($ext) != "PDF") {
		$errormsg .= "<font class='error'>".t("Ainoastaan .jpg .gif .png .pdf tiedostot sallittuja")."!</font>";
	}
	// ja file jonkun kokonen
	elseif ($_FILES['userfile']['size'] == 0) {
		$errormsg .= "<font class='error'>".t("Tiedosto on tyhj‰")."!</font>";
	}
	elseif ($filesize > $varirow[1]) {
		$errormsg .= "<font class='error'>".t("Liitetiedosto on liian suuri")."! ($varirow[1]) </font>";
	}

	if (empty($errormsg)) {
		$data = mysql_real_escape_string(file_get_contents($_FILES['userfile']['tmp_name']));

		// lis‰t‰‰n kuva
		$query = "	insert into liitetiedostot set
					yhtio    = '{$kukarow['yhtio']}',
					liitos   = '". mysql_real_escape_string($_GET['liitos']) . "',
					liitostunnus = '" . (int) $_GET['id']. "',
					laatija    = '{$kukarow['kuka']}',
					luontiaika = now(),
					data     = '$data',
					selite   = '$filename',
					filename = '$filename',
					filesize = '$filesize',
					filetype = '$filetype'";

		$result = mysql_query($query) or pupe_error($query);
		$liitostunnus = mysql_insert_id();
		$kuva = $liitostunnus;

		$query = "SELECT * from lasku where tunnus=" . (int) $_GET['id'] . " and yhtio='{$kukarow['yhtio']}'";
		$res = mysql_query($query) or pupe_error($query);
		$laskurow = mysql_fetch_array($res);

		// nollataan hyv‰ksyj‰t jos jokin n‰ist‰ tiloista
		if (in_array($laskurow['tila'], array('H', 'M', 'Q'))) {
			nollaa_hyvak((int) $_GET['id']);
		}
	} else {
		echo $errormsg;
	}
}

// poistetaanko liite?
if (isset($_POST['poista']) and isset($_POST['tunnus']) and isset($_GET['liitos']) and isset($_GET['id'])) {

	settype($_GET['id'], 'int');
	$query = "DELETE from liitetiedostot where tunnus='{$_POST['tunnus']}' and yhtio='{$kukarow['yhtio']}'";
	mysql_query($query) or pupe_error($query);

	$query = "SELECT * from lasku where tunnus=" . (int) $_GET['id'] . " and yhtio='{$kukarow['yhtio']}'";
	$res = mysql_query($query) or pupe_error($query);
	$laskurow = mysql_fetch_array($res);

	if (in_array($laskurow['tila'], array('H', 'M', 'Q'))) {
		nollaa_hyvak($_GET['id']);
	}
}

if (isset($_GET['liitos']) and $_GET['liitos'] == 'lasku' and isset($_GET['id'])) {

	echo "<h2>Lasku</h2>";

	$query = "SELECT * from lasku where tunnus=" . (int) $_GET['id'] . " and yhtio='{$kukarow['yhtio']}'";
	$res = mysql_query($query) or pupe_error($query);
	$laskurow = mysql_fetch_array($res);

	$query = "SELECT * from oikeu where yhtio='{$kukarow['yhtio']}' and kuka='{$kukarow['kuka']}' and nimi LIKE '%ulask.php'";
	$res = mysql_query($query) or pupe_error($query);

	// sallittu katselu
	if (! in_array($laskurow['tila'], array('H', 'Y', 'M', 'P', 'Q', 'X')) or mysql_num_rows($res) == 0) {
		die ("<font class='error'>".t("V‰‰r‰ laskutyyppi!")."</font>");
	}

	echo "<table>
		<tr><th>". t('Nimi')."</th><td>{$laskurow['nimi']}</td></tr>
		<tr><th>". t('Nimitark')."</th><td>{$laskurow['nimitark']}</td></tr>
		<tr><th>". t('Osoite')."</th><td>{$laskurow['osoite']}</td></tr>
		<tr><th>". t('Postitp')."</th><td>{$laskurow['postitp']}</td></tr>";

	echo "</table>";

	if (! in_array($laskurow['tila'], array('P', 'Y'))) {
		echo "<p>Lis‰‰ uusi tiedosto: <form method='post' name='kuva' enctype='multipart/form-data' action='$PHP_SELF'>
				<input type='file' name='userfile'/>
				<input type='hidden' name='tee' value='file'/>
				<input type='hidden' name='liitos' value='lasku'/>
				<input type='submit' name='submit' value='" . t('Liit‰ tiedosto') . "'/>
			</form></p>";
	}

	$query = "	SELECT liitostunnus,tunnus,filename,filesize,selite,filetype,laatija,luontiaika
				FROM liitetiedostot
				WHERE liitostunnus=" . (int) $_GET['id'] . " AND liitos='lasku' and yhtio='{$kukarow['yhtio']}'";
	$res = mysql_query($query) or pupe_error($query);

	echo "<table>
		<tr>
		    <th>". t('Selite'). "</th>
			<th>". t('Tiedosto'). "</th>
			<th>". t('Koko'). "</th>
			<th>". t('Tyyppi'). "</th>
			<th>". t('Lis‰ttyaika'). "</th>
			<th>". t('Lis‰‰j‰'). "</th>
			<th colspan=2></th>
		</tr>";

	while ($liite = mysql_fetch_array($res)) {

		$filesize = $liite['filesize'];
		$type = array('b', 'kb', 'mb', 'gb');

		for ($ii=0; $filesize>1024; $ii++) {
			$filesize /= 1024;
		}

		$filesize = sprintf("%.2f",$filesize) . " $type[$ii]";

		echo "<tr>
			<td>". $liite['selite']  . "</td>
			<td>". $liite['filename']  . "</td>
			<td>". $filesize . "</td>
			<td>". $liite['filetype']  . "</td>
			<td>". $liite['luontiaika'] . "</td>
			<td>". $liite['laatija']  . "</td>
			";

			echo "<td><form method='post' action='view.php?id={$liite['tunnus']}'><input type='submit' value='" . t('N‰yt‰ lasku')."'/></form></td>";

			if (! in_array($laskurow['tila'], array('P', 'Y'))) {
				echo "<td><form action='' method='post'>
					<input type='hidden' name='tunnus' value='{$liite['tunnus']}'/>
					<input type='submit' name='poista' value='Poista' onclick='return confirm(\"" . t('Haluatko varmasti poistaa t‰m‰n liitteen') . "\");'/>
					</form></td>";
			}

		echo "</tr>";
	}

	echo "</table>";

	echo "<br /><br /><a href='muutosite.php?tee=E&tunnus={$laskurow['tunnus']}'>&laquo; " . t('Takaisin laskulle') . "</a>";
}

// nollaa hyvak ajat sek‰ asettaa viimeisimm‰n hyv‰ksyj‰n = hyvak1
function nollaa_hyvak($id) {
	global $kukarow;

	// nollataan hyv‰ksyj‰t jos jokin n‰ist‰ tiloista
	$query = "	UPDATE lasku set h1time = '', h2time = '', h3time = '', h4time = '', h5time = '', hyvaksyja_nyt = hyvak1
				WHERE tunnus =" . $id . " and yhtio='{$kukarow['yhtio']}'";
	mysql_query($query) or pupe_error($query);
}

require ('inc/footer.inc');

?>