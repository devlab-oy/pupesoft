<?php
	require "inc/parametrit.inc";

	
	echo "<font class='head'>".t("Puuttuvat maksutilit")."</font><hr>";



	if ($tee == 'X') {
		$query = "UPDATE lasku set
					  maksu_tili = '$tili'
					  WHERE yhtio = '$kukarow[yhtio]' and maksu_tili='' and tunnus='$tunnus' and tila='Q'";
		$result = mysql_query($query) or pupe_error($query);
		if (mysql_affected_rows() == 0) {
			echo "<font class='error'>".t("Laskun päivitys ei onnistunut")."</font>";
		}
		$tee='';
	}



	
	if ($tee == '') {


		$query = "SELECT tunnus, nimi
					FROM yriti
					WHERE yhtio='$kukarow[yhtio]'
					ORDER BY nimi";
		$sresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='tili'>";
		while ($srow = mysql_fetch_array($sresult)) {
			$ulos .= "<option value='$srow[0]' $sel>$srow[1]</option>";
		}
		$ulos .= "</select></td>";


		$query = "SELECT lasku.tunnus, lasku.nimi, tapvm, erpcm, round(summa * valuu.kurssi,2) summa
					FROM lasku, valuu
					WHERE lasku.valkoodi = valuu.nimi and
						valuu.yhtio = '$kukarow[yhtio]' and
						lasku.yhtio = '$kukarow[yhtio]' and
						lasku.maksu_tili = '' and
						lasku.tila = 'Q'
					ORDER BY lasku.tapvm";

		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='message'>".t("Ei sopivia laskuja")."</font>";
			exit;
		}

		echo "<table><tr>";

		for ($i = 0; $i < mysql_num_fields($result); $i++) {
			echo "<th>" . t(mysql_field_name($result,$i))."</th>";
		}
		echo "<th>".t("Maksutili")."</th><th></th></tr>";

		while ($trow=mysql_fetch_array ($result)) {
		        echo "<tr>
			<form name = 'viivat' action = '$PHP_SELF?tee=VIIVA' method='post'>
			<input type = 'hidden' name = 'tee' value = 'X'>
			<input type = 'hidden' name = 'tunnus' value = '$trow[tunnus]'>";
		        for ($i=0; $i<mysql_num_fields($result); $i++) { // ei näytetä tunnusta
				echo "<td>$trow[$i]</td>";
			}
			echo "$ulos<td><input type = 'submit' value = '".t("Valitse")."'></td></tr></form>";
		}
	}
?>
