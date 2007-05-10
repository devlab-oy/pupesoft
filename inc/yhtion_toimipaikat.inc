<?php

	echo "<font class='head'>".t("Yhtiön toimipaikat")."</font><hr>";

	if ($oikeurow['paivitys'] != '1') { // Saako päivittää
		echo "<br><font class='error'>".t("Sun natsat ei riitä tähä hommaan poika")."!</font><br>";
		exit;
	}

	// tehdään tarkastuksia ja muuta kivaa
	if ($tee != "uusi" and $tee != "poista" and $tee != "") {

		$toimerrorit = 0;

		if ($toim_maa != "") {
			$query = "select * from maat where koodi='$toim_maa'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0) {
				$toimerrorit++;
				echo "<font class='error'>Virheellinen maa!</font><br>";
			}
		}
		else {
			$toimerrorit++;
			echo "<font class='error'>Virheellinen maa!</font><br>";
		}

		if ($vat_numero != "" and substr($vat_numero, 0, 2) != $toim_maa) {
			$toimerrorit++;
			echo "<font class='error'>Virheellinen vat-numero!</font><br>";
		}

		if ($vat_numero != "") {
			$query = "	SELECT selite
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]' and laji = 'alvulk' and selitetark_2 = '$toim_maa'";
			$vresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($vresult) == 0) {
				echo "<font class='error'>Yrityksen ALV-prosentit puuttuvat tälle maalle ($toim_maa)! Käy perustamassa ensin ne avainsanoihin.</font>";
				$toimerrorit++;
			}

			$query = "	SELECT *
						FROM yhtion_toimipaikat
						WHERE yhtio = '$kukarow[yhtio]' and maa = '$toim_maa' and tunnus != '$toim_tunnus' and vat_numero != ''";
			$vresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($vresult) != 0) {
				echo "<font class='error'>Yksi vat tunnus per maa sallittu!</font>";
				$toimerrorit++;
			}

			if ($toim_ovtlisa == "" or
				$toim_kotipaikka == "" or
				$vat_numero == "" or
				$toim_nimi == "" or
				$toim_osoite == "" or
				$toim_postino == "" or
				$toim_postitp == "" or
				$toim_maa == "" or
				$toim_fax == "" or
				$toim_puhelin == "" or
				$toim_email == "") {
				echo "<font class='error'>Kaikki tiedot on syötettävä alv-velvolliselle toimipaikalle!</font>";
				$toimerrorit++;
			}

		}

		if ($toimerrorit > 0) {
			if ($tee == "insert") $tee = "uusi";
			else $tee = "";
		}
	}

	// päivitetään toimipaikka
	if ($tee == "update") {
		$query = "	update yhtion_toimipaikat set
					yhtio			= '$kukarow[yhtio]',
					ovtlisa 		= '$toim_ovtlisa',
					kotipaikka		= '$toim_kotipaikka',
					vat_numero		= '$vat_numero',
					nimi 			= '$toim_nimi',
					osoite 			= '$toim_osoite',
					postino 		= '$toim_postino',
					postitp 		= '$toim_postitp',
					maa		 		= '".strtoupper($toim_maa)."',
					fax 			= '$toim_fax',
					puhelin 		= '$toim_puhelin',
					email 			= '$toim_email',
					lasku_logo		= '$lasku_logo',
					muuttaja		= '$kukarow[kuka]',
					muutospvm		= now()
					where yhtio = '$kukarow[yhtio]' and tunnus = '$toim_tunnus'";
		$result = mysql_query($query) or pupe_error($query);
		$tee = "";
	}

	// poistetaan toimittaja
	if ($tee == "poista") {
		$query = "delete from yhtion_toimipaikat where yhtio='$kukarow[yhtio]' and tunnus='$toim_tunnus'";
		$result = mysql_query($query) or pupe_error($query);
		$tee = "";
	}

	// lisätään toimittaja
	if ($tee == "insert") {
		$query = "	insert into yhtion_toimipaikat set
					yhtio			= '$kukarow[yhtio]',
					ovtlisa 		= '$toim_ovtlisa',
					vat_numero		= '$vat_numero',
					kotipaikka		= '$toim_kotipaikka',
					nimi 			= '$toim_nimi',
					osoite 			= '$toim_osoite',
					postino 		= '$toim_postino',
					postitp 		= '$toim_postitp',
					maa		 		= '".strtoupper($toim_maa)."',
					fax 			= '$toim_fax',
					puhelin 		= '$toim_puhelin',
					email 			= '$toim_email',
					lasku_logo		= '$lasku_logo',
					laatija			= '$kukarow[kuka]',
					luontiaika		= now()";
		$result = mysql_query($query) or pupe_error($query);
		$tee = "";
	}

	// uuden syöttö
	if ($tee == "uusi") {

		echo "<form action='yllapito.php?ojarj=$ojarj$ulisa' method='post'>";

		echo "<table>";
		echo "<tr><th>".t("Ovtlisa")."</th>		<td><input type='text' name='toim_ovtlisa'  value='$toim_ovtlisa'></td></tr>";
		echo "<tr><th>".t("VAT-numero")."</th>	<td><input type='text' name='vat_numero'    value='$vat_numero'><td class='back'><a target='uusiytj' href='http://ec.europa.eu/taxation_customs/vies/$kukarow[kieli]/vieshome.htm'>Tarkista numero täältä!</a></td></td></tr>";
		echo "<tr><th>".t("Kotipaikka")."</th>	<td><input type='text' name='toim_kotipaikka'value='$toim_kotipaikka'></td></tr>";
		echo "<tr><th>".t("Nimi")."</th>		<td><input type='text' name='toim_nimi'     value='$toim_nimi'></td></tr>";
		echo "<tr><th>".t("Osoite")."</th>		<td><input type='text' name='toim_osoite'   value='$toim_osoite'></td></tr>";
		echo "<tr><th>".t("Postino")."</th>		<td><input type='text' name='toim_postino'  value='$toim_postino'></td></tr>";
		echo "<tr><th>".t("Postitp")."</th>		<td><input type='text' name='toim_postitp'  value='$toim_postitp'></td></tr>";
		echo "<tr><th>".t("Maa")."</th>			<td><input type='text' name='toim_maa'      value='$toim_maa'></td></tr>";
		echo "<tr><th>".t("Fax")."</th>			<td><input type='text' name='toim_fax'      value='$toim_fax'></td></tr>";
		echo "<tr><th>".t("Puhelin")."</th>		<td><input type='text' name='toim_puhelin'  value='$toim_puhelin'></td></tr>";
		echo "<tr><th>".t("Email")."</th>		<td><input type='text' name='toim_email'    value='$toim_email'></td></tr>";
		echo "<tr><th>".t("Lasku logo")."</th>	<td><input type='text' name='lasku_logo'    value='$lasku_logo'></td></tr>";
		echo "</table><hr>";

		echo "<input type='hidden' name='tee' value='insert'>";
		echo "<input type='hidden' name='toim' value='yhtio'>";
		echo "<input type='hidden' name='tunnus' value='$tunnus'>";
		echo "<input type='hidden' name='toim_tunnus' value='$toim_tunnus'>";
		echo "<input type='hidden' name='yhtio' value='$kukarow[yhtio]'>";
		echo "<input type='submit' value='".t("Perusta toimipaikka")."'></form>";
	}

	// selataan vanhoja
	if ($tee == "") {

		$query  = "select * from yhtion_toimipaikat where yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		while ($trow = mysql_fetch_array($result)) {

			echo "<form action='yllapito.php?ojarj=$ojarj$ulisa' method='post'>";

			echo "<table>";
			echo "<tr><th>".t("Ovtlisa")."</th>	<td><input type='text' name='toim_ovtlisa'  value='$trow[ovtlisa]'></td></tr>";
			echo "<tr><th>".t("VAT-numero")."</th><td><input type='text' name='vat_numero'  value='$trow[vat_numero]'></td><td class='back'><a target='uusiytj' href='http://ec.europa.eu/taxation_customs/vies/$kukarow[kieli]/vieshome.htm'>Tarkista numero täältä!</a></td></tr>";
			echo "<tr><th>".t("Kotipaikka")."</th>	<td><input type='text' name='toim_kotipaikka'value='$toim_kotipaikka'></td></tr>";
			echo "<tr><th>".t("Nimi")."</th>	<td><input type='text' name='toim_nimi'     value='$trow[nimi]'></td></tr>";
			echo "<tr><th>".t("Osoite")."</th>	<td><input type='text' name='toim_osoite'   value='$trow[osoite]'></td></tr>";
			echo "<tr><th>".t("Postino")."</th>	<td><input type='text' name='toim_postino'  value='$trow[postino]'></td></tr>";
			echo "<tr><th>".t("Postitp")."</th>	<td><input type='text' name='toim_postitp'  value='$trow[postitp]'></td></tr>";
			echo "<tr><th>".t("Maa")."</th>		<td><input type='text' name='toim_maa'      value='$trow[maa]'></td></tr>";
			echo "<tr><th>".t("Fax")."</th>		<td><input type='text' name='toim_fax'      value='$trow[fax]'></td></tr>";
			echo "<tr><th>".t("Puhelin")."</th>	<td><input type='text' name='toim_puhelin'  value='$trow[puhelin]'></td></tr>";
			echo "<tr><th>".t("Email")."</th>	<td><input type='text' name='toim_email'    value='$trow[email]'></td></tr>";
			echo "<tr><th>".t("Lasku logo")."</th><td><input type='text' name='lasku_logo'  value='$trow[lasku_logo]'></td></tr>";

			echo "<tr><td class='back'>";
			echo "<input type='hidden' name='tee' value='update'>";
			echo "<input type='hidden' name='toim' value='yhtio'>";
			echo "<input type='hidden' name='tunnus' value='$tunnus'>";
			echo "<input type='hidden' name='yhtio' value='$kukarow[yhtio]'>";
			echo "<input type='hidden' name='toim_tunnus'  value='$trow[tunnus]'>";
			echo "<input type='submit' value='".t("Päivitä toimipaikka")."'></td></form>";

			echo "<form action='yllapito.php?ojarj=$ojarj$ulisa' method='post'>";

			echo "<td class='back'>";
			echo "<input type='hidden' name='tee' value='poista'>";
			echo "<input type='hidden' name='toim' value='yhtio'>";
			echo "<input type='hidden' name='tunnus' value='$tunnus'>";
			echo "<input type='hidden' name='yhtio' value='$kukarow[yhtio]'>";
			echo "<input type='hidden' name='toim_tunnus'  value='$trow[tunnus]'>";
			echo "<input type='submit' value='".t("Poista toimipaikka")."'></td></form>";
			echo "</tr>";

			echo "</table>";
			echo "<hr>";
		}

		echo "<br><form action='yllapito.php?ojarj=$ojarj$ulisa' method='post'>
		<input type='hidden' name='tee' value='uusi'>
		<input type='hidden' name='toim' value='yhtio'>
		<input type='hidden' name='tunnus' value='$tunnus'>
		<input type='hidden' name='yhtio' value='$kukarow[yhtio]'>
		<input type='submit' value='".t("Lisää uusi toimipaikka")."'></form>";

	}

?>