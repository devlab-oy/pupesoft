<?php

require ("inc/parametrit.inc");

if($svnStatus === false) {
	die("Dokumentinhallinta EI OLE käytössä");
}

echo "<font class='head'>".t("Dokumentinhallinnan korjaukset")."</font><hr><br>";

if($tee == "perusta_uusi" and (int) $tunnus > 0) {
	
	$retval = svnOpenNew($tunnus, $tyyppi);
	if($retval === true) {
		echo "<font class='message'>".t("Kansio perustettu")."</font>";
	}
	else {
		echo "<font class='error'>".t("Virhe!")." ".t($retval)."</font>";
	}
	
	echo "<br><br>";
}

echo "
	<form action = '$PHP_SELF' method='POST'>
	<input type='hidden' name='tee' value='perusta_uusi'>
	<table>
		<tr>
			<th>".t("Perusta uusi kansio")."</th>
		</tr>
		<tr>
			<td>
				<select name='tyyppi'>
					<option value = ''>".t("Valitse laji")."</option>
					<option value = 'TARJOUS'>".t("Tarjous")."</option>
					<option value = 'PROJEKTI'>".t("Projekti")."</option>
				</select>
				<input type = 'text' name='tunnus' value = '' size = '10'>
			</td>
		</tr>
	</table>
	</form>";

?>