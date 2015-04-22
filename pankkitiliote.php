<?php
	require ("inc/parametrit.inc");
	require ("inc/tilinumero.inc");

	echo "<font class='head'>".t("Pankin tiliote")."</font><hr>";

	if ($tee != '') {
		// Etsitään tili
		$query = "	SELECT *
					FROM TAMK_pankkitili
					WHERE yhtio = '$kukarow[yhtio]' and tilinro = '$tilinro'";
		$result = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Pankkitiliä ei löydy")."!</font><br><br>";
		}
		else {
			$tilrow = mysql_fetch_array($result);
			echo "<font class='message'>".t("Tiliotteesi ")."$tilrow[omistaja] $summa!</font><br><br>";
			$query = "	SELECT	tapvm
						, saajanNimi
						, maksajanNimi
						, summa
						, selite
					FROM	TAMK_pankkitapahtuma
					WHERE	yhtio = '$yhtiorow[yhtio]'
						AND
						(saaja = '$tilinro' OR maksaja = '$tilinro')
					";
			$result = mysql_query($query) or pupe_error($query);

			echo "<table><tr>";
			for ($i = 1; $i < mysql_num_fields($result); $i++) {
				echo "<th>" . t(mysql_field_name($result,$i))."</th>";
			}
			echo "<th></th></tr>";

			while($row = mysql_fetch_array($result)) {
				echo "<tr>";
				for ($i=1; $i<mysql_num_fields($result); $i++) {
					echo "<td>$row[$i]</td>";
				}
				echo "</tr>";
			}
			echo "</table>";
		}
	}

	echo "<table><form action = '' method='post'>";

	echo "	<tr><th>".t("Anna tilinumero")."</th>
			<td><input type='text' name = 'tilinro'></td></tr>";
	echo "	<tr><td></td><td>
			<input type='hidden' name = 'tee' value='D'>
			<input type='Submit' value='".t('Näytä tiliote')."'></td></tr>
			</form></table>";

	echo "<table>";

	require ("inc/footer.inc");

?>
