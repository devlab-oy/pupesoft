<?php
	require ("patufunktiot.inc");
	require ("siirto.php");
	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Patutestaus")."<hr></font>";
	
	$ok = 0;
	// katotaan onko faili uploadttu
	if (is_uploaded_file($_FILES['userfile']['tmp_name']) and $tunnus != '') {
		$userfile = $_FILES['userfile']['name'];
		$filenimi = $_FILES['userfile']['tmp_name'];
		$ok = 1;
	}
	if ($ok == 1) {
		$sisalto = file($filenimi);
		$aineisto = mikaaineisto($sisalto[0]);
		$sisalto = teepankkikonversio($sisalto);
	}

	if ($tunnus != '' and $nollapolvi != '') {
		ekakayttoavain($tunnus);
		echo "0-sukupolven k�ytt�avain palautettiin<br>";
	}

	if ($tunnus != '' and $ekapala != '') {
		echo "<font class='error'>".siirtoavain($ekapala,$tokapala,$tarkiste,$suku,$tunnus)."</font><br><br>";
		$query = "	SELECT * 
				FROM yriti 
				WHERE yhtio  = '$kukarow[yhtio]'
				and tunnus = '$tunnus'";
		$vresult = mysql_query($query) or pupe_error($query);
		if ($vrow=mysql_fetch_array($vresult)) {
			echo "<font class='error'>".ekakayttoavain($tunnus)."</font><br><br>";
		}
	}
	if ($tunnus != '' and $asiakas != '') {
		$query = "UPDATE yriti SET asiakas='$asiakas' WHERE yhtio  = '$kukarow[yhtio]' and tunnus = '$tunnus'";
		$vresult = mysql_query($query) or pupe_error($query);
		echo "Asiakastieto p�ivitetty<br>";
	}
	if ($tunnus != '' and $pankki != '') {
		$query = "UPDATE yriti SET pankki='$pankki' WHERE yhtio  = '$kukarow[yhtio]' and tunnus = '$tunnus'";
		$vresult = mysql_query($query) or pupe_error($query);
		echo "Pankkitieto p�ivitetty<br>";
	}
	if ($tunnus != '' and $kertasalasana != '') {
		$testaus = 1;
		$yritirow = salattukertaavain($tunnus);
		tiiviste($jono, $yritirow['kertaavain']);
	}
	if ($tunnus != '' and $aineisto != '') {
		$query = "	SELECT * 
				FROM yriti 
				WHERE yhtio  = '$kukarow[yhtio]'
				and tunnus = '$tunnus' and kayttoavain != ''";
		$vresult = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($vresult) != 1) {
			echo "<font class='error'>Tilill� ei ole k�ytt�avainta</font><br><br>";
			exit;
		}
		else {
			$yritirow=mysql_fetch_array($vresult);
			$yritirow['nro']++;
			$query = "UPDATE yriti SET nro='$yritirow[nro]' WHERE yhtio  = '$kukarow[yhtio]' and tunnus = '$tunnus' and kayttoavain != ''";
			$vresult = mysql_query($query) or pupe_error($query);
			if ($aineisto == 'LMP300') aineistonlahetys($yritirow, $aineisto, $pvm, $sisalto);
			else aineistonnouto($yritirow, $aineisto, $pvm);
		}
	}
	echo "<form action = '' method = 'post'>";
	echo "<table><tr><th>".t("Ensimm�inen siirtoavain")."</th>";
	echo "<td><input type='radio' name = 'toiminto' value = 'ekasiirtoavain'></td>";
	echo "<th>".t("Perustiedot")."</th>";
	echo "<td><input type='radio' name = 'toiminto' value = 'perustiedot'></td>";
	echo "<th>".t("Hae aineisto")."</th>";
	echo "<td><input type='radio' name = 'toiminto' value = 'haeaineisto'></td>";
	echo "<th>".t("L�het� aineisto")."</th>";
	echo "<td><input type='radio' name = 'toiminto' value = 'lahetaaineisto'></td>";
	echo "<td><input type='submit' value = 'Tee'></td></table></form><br><br>";


	if ($toiminto != '') {
		echo "<form enctype='multipart/form-data' action = '' method = 'post'><table><tr><th>".t("Pankkitili");
		$query = "	SELECT * 
				FROM yriti 
				WHERE yhtio  = '$kukarow[yhtio]'
				and kaytossa = ''
				and left(tilino,1) <= '9'";
		$vresult = mysql_query($query) or pupe_error($query);

		echo "</td><td><select name='tunnus'>";

		while ($vrow=mysql_fetch_array($vresult)) {
			echo "<option value = '$vrow[tunnus]'>$vrow[nimi]";
			if ($vrow['kayttoavain'] != '') echo " patu ok";
		}
		echo "</select></td></tr>";
	}

	if ($toiminto=='ekasiirtoavain') {
		echo "<tr><th>".t("Avain1")."</th>";
		echo "<td><input type='text' name = 'ekapala' maxlength = '23'></td></tr>";
		echo "<tr><th>".t("Avain2")."</th>";
		echo "<td><input type='text' name = 'tokapala' maxlength = '23'></td></tr>";
		echo "<tr><th>".t("Tarkiste")."</th>";
		echo "<td><input type='text' name = 'tarkiste' maxlength = '8'></td></tr>";
	}
	if ($toiminto=='perustiedot') {
		echo "<tr><th>".t("Pankki")."</th>";
		echo "<td><input type='text' name = 'pankki'></td></tr>";
		echo "<tr><th>".t("Asiakas")."</th>";
		echo "<td><input type='text' name = 'asiakas'></td></tr>";
	}
	if ($toiminto=='haeaineisto') {
		echo "<tr><th>".t("Tiliote")."</th>";
		echo "<td><input type='radio' name = 'aineisto' value = 'TITO'></td></tr>";
		echo "<tr><th>".t("Valuuttakurssit")."</th>";
		echo "<td><input type='radio' name = 'aineisto' value = 'VKEUR'></td></tr>";
		echo "<tr><th>".t("Aineistojen tilakysely")."</th>";
		echo "<td><input type='radio' name = 'aineisto' value = 'STATUS'></td></tr>";
	}
	if ($toiminto=='lahetaaineisto') {
		echo "<tr><th>".t("Aineiston l�hetys")."</th>";
		echo "<td><input type='file' name='userfile'></td></tr>";
	}
	if ($toiminto=='testi') {
		echo "<tr><th>".t("K�yt� testausaineistoa")."</th>";
		echo "<td><input type='checkbox' name = 'testaus' value = '1'></td></tr>";
		echo "<tr><th>".t("Testaa kertasalasanan suojausta ja tiivisteen laskentaa")."</th>";
		echo "<td><input type='checkbox' name = 'kertasalasana' value = '1'></td></tr>";
		echo "<tr><th>".t("Palauta 0-sukupolven k�ytt�avain")."</th>";
		echo "<td><input type='checkbox' name = 'nollapolvi' value = '1'></td></tr>";
		echo "<tr><th>".t("Suku")."</th>";
		echo "<td><input type='text' name = 'suku' value = '0'></td></tr>";
	}
	if ($toiminto != '') {
		echo "<tr><th></th><td><input type='submit' name = 'P�ivit�'></td></tr>";
		echo "</table></form>";
	}
	require ("../inc/footer.inc");
?>
