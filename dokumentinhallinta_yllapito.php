<?php

require ("inc/parametrit.inc");

if($svnStatus === false) {
	die("Dokumentinhallinta EI OLE käytössä");
}

echo "<font class='head'>".t("Dokumentinhallinnan korjaukset")."</font><hr><br>";

if($tee == "perusta_uusi") {
	
	if((int) $tunnus > 0) {
		$retval = svnOpenNew($tunnus, $tyyppi);
		if($retval === true) {
			echo "<font class='message'>".t("Kansio perustettu")."</font>";
		}
		else {
			echo "<font class='error'>".t("Virhe!")." ".t($retval)."</font>";
		}
	}
	else {
		echo "<font class='error'>".t("Anna tunnus!")."</font>";
	}
	
	echo "<br><br>";
}

if($tee == "sulje") {
	
	if((int) $tunnus > 0) {
		$retval = svnClose($tunnus, $tyyppi);
		if($retval === true) {
			echo "<font class='message'>".t("Kansio siirretty")."</font>";
		}
		else {
			echo "<font class='error'>".t("Virhe!")." ".t($retval)."</font>";
		}
	}
	else {
		echo "<font class='error'>".t("Anna tunnus!")."</font>";
	}
	
	echo "<br><br>";
}

if($tee == "luo_asiakaskansiot") {
	$query = "	SELECT *
				FROM asiakas
				WHERE yhtio = '$kukarow[yhtio]' and nimi != ''";
	$result = mysql_query($query) or pupe_error($query);
	while($row = mysql_fetch_array($result)) {
		echo "Tarkastetaan asiakas {$row["nimi"]} {$row["nimitark"]}<br>";
		svnSyncMaintenanceFolders("asiakas", $row["tunnus"]);

		//	Ja kohteet
		$query = "	SELECT *
					FROM asiakkaan_kohde
					WHERE yhtio = '$kukarow[yhtio]' and liitostunnus = '$row[tunnus]'";
		$kohderes = mysql_query($query) or pupe_error($query);
		while($kohderow = mysql_fetch_array($kohderes)) {
			var_dump(svnSyncMaintenanceFolders("asiakkaan_kohde", $kohderow["tunnus"]));
		}

		flush();
	}
}

echo "
	<table>
		<tr>
			<th>".t("Perusta uusi kansio projektille/tarjoukselle")."</th>
		</tr>
		<tr>
			<td>
				<form action = '$PHP_SELF' method='POST'>
				<input type='hidden' name='tee' value='perusta_uusi'>		
					<select name='tyyppi'>
						<option value = ''>".t("Valitse laji")."</option>
						<option value = 'TARJOUS'>".t("Tarjous")."</option>
						<option value = 'PROJEKTI'>".t("Projekti")."</option>
					</select>
					<input type = 'text' name='tunnus' value = '$tunnus' size = '10'>
					<input type = 'submit' value='Avaa'>
				</form>
			</td>
		</tr>
		<tr>
			<th>".t("Sulje projekti/tarjous")."</th>
		</tr>
		<tr>
			<td>
				<form action = '$PHP_SELF' method='POST'>
				<input type='hidden' name='tee' value='sulje'>
					<select name='tyyppi'>
						<option value = ''>".t("Valitse laji")."</option>
						<option value = 'TARJOUS'>".t("Tarjous")."</option>
						<option value = 'PROJEKTI'>".t("Projekti")."</option>
					</select>
					<input type = 'text' name='tunnus' value = '$tunnus' size = '10'>
					<input type = 'submit' value='Sulje'>					
				</form>
			</td>
	</table>
	<br>";

echo "
	<form action = '$PHP_SELF' method='POST'>
	<input type='hidden' name='tee' value='luo_asiakaskansiot'>
	<input type = 'submit' value = 'Luo asiakaskansiot'>
	</form>";

?>
