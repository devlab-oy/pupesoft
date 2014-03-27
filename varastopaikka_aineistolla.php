<?php

	if (strpos($_SERVER['SCRIPT_NAME'], "varastopaikka_aineistolla.php")  !== FALSE) {
		require("inc/parametrit.inc");
		echo "<font class='head'>".t("Tuotteen varastopaikkojen muutos aineistolla")."</font><hr>";
	}

	if (!isset($tee) or (isset($varasto_valinta) and $varasto_valinta == '')) $tee = "";
	if (!isset($virheviesti)) $virheviesti = "";
	var_dump($_POST);

	if ($tee == "AJA") {
		var_dump($_FILES);
		if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {
			echo "faili löytyy<br>";
			$kasiteltava_tiedosto_path = $_FILES['userfile']['tmp_name'];
			echo "NORJA $kasiteltava_tiedosto_path";
		}
		else {
			$tee = "";
		}
	}

	if ($tee == "") {
		$query = "	SELECT *
					FROM varastopaikat
					WHERE yhtio = '{$yhtiorow['yhtio']}'
					AND nimitys != ''
					AND tyyppi != 'P'
					ORDER BY tyyppi,nimitys";
		$vresult = pupe_query($query);

		
		echo "	<form name='varasto' method='post'>
				<input type='hidden' name='tee' value='VALITSE_TIEDOSTO'>
				<table>
				<tr><th>".t("Valitse kohdevarasto")."</th>
				<td><select name='varasto_valinta'><option value = ''>".t("Ei varastoa")."</option>";
		while($varasto = mysql_fetch_assoc($vresult)) {
			$sel = "";
			if ($varasto_valinta != '' and $varasto_valinta == $varasto['tunnus']) $sel = "SELECTED";
			echo "<option value='{$varasto['tunnus']}' $sel>{$varasto['nimitys']}</option>";
		}
		echo "	</select></td>$virheviesti</tr>
				</table>
				<br><input type = 'submit' value = '".t("Hae")."'>
				</form>";
	}

	if ($tee == 'VALITSE_TIEDOSTO' and $varasto_valinta != '') {
		echo "nyt kutsuttiin valitse_tiedostoa<br>";
		echo "	<form name='tiedosto' enctype='multipart/form-data'>
				<input type='hidden' name='varasto_valinta' value='$varasto_valinta'>
				<input type='hidden' name='tee' value='AJA'>
				<table>
				<tr><th>".t("Valitse tiedosto").":</th>
				<td><input name='userfile' type='file'></td>
				<td class='back'><input type='submit' value='".t("Läheta")."'></td></tr>
				</table></form>";
	}
	
	if ($tee == 'AJA' and $userfile != '') {
		$kutsuja = "varastopaikka_aineistolla.php";
		echo "nyt kutsuttiin ajoa";
	}

	if ($kutsuja == '') {
		require "inc/footer.inc";
	}