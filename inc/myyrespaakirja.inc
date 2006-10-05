<?php
	if ($tee == '') {
		echo "<b>".t("Suorituspäiväkirja")."</b><hr>
				<form name = 'valinta' action = 'raportit.php' method='post'>
				<input type = 'hidden' name = 'toim' value = 'myyrespaakirja'>
				<input type = 'hidden' name = 'tee' value = '1'>
				<table>
				<td>".t("Ajalta")."</td>
				<td><select name='alvv'>
				";

								for ($i = date("Y")+2; $i >= date("Y")-4; $i--) {
									if ($i == date("Y")) $sel = "selected";
									else $sel = "";
									echo "<option value='$i' $sel>$i</option>";
								}

				echo "
				</select>
				<select name='alvk'>
				<option value = '00'>".t("koko vuosi")."
				<option value = '01'>01
				<option value = '02'>02
				<option value = '03'>03
				<option value = '04'>04
				<option value = '05'>05
				<option value = '06'>06
				<option value = '07'>07
				<option value = '08'>08
				<option value = '09'>09
				<option value = '10'>10
				<option value = '11'>11
				<option value = '12'>12
				</select>
				<select name='alvp'>
				<option value = '00'>".t("koko kuukausi")."
				<option value = '01'>01
				<option value = '02'>02
				<option value = '03'>03
				<option value = '04'>04
				<option value = '05'>05
				<option value = '06'>06
				<option value = '07'>07
				<option value = '08'>08
				<option value = '09'>09
				<option value = '10'>10
				<option value = '11'>11
				<option value = '12'>12
				<option value = '13'>13
				<option value = '14'>14
				<option value = '15'>15
				<option value = '16'>16
				<option value = '17'>17
				<option value = '18'>18
				<option value = '19'>19
				<option value = '20'>20
				<option value = '21'>21
				<option value = '22'>22
				<option value = '23'>23
				<option value = '24'>24
				<option value = '25'>25
				<option value = '26'>26
				<option value = '27'>27
				<option value = '28'>28
				<option value = '29'>29
				<option value = '30'>30
				<option value = '31'>31
				</select></td>
		      		<td><input type = 'submit' value = '".t("Näytä")."'></td></tr></table></form>";
		require "inc/footer.inc";
		exit;
	}


	$jarj = "tiliointi.tapvm, tiliointi.summa";

	if ($alvk == 0) {
        	echo "".t("Suorituspäiväkirja vuodelta")." $alvv<hr>";
			$lisa = "LEFT(tiliointi.tapvm,4) = '$alvv'";
	}
	else {
		if ($alvp == 0) {
			echo "".t("Suorituspäiväkirja kaudelta")." $alvv-$alvk<hr>";
			$lisa = "LEFT(tiliointi.tapvm,7) = '$alvv-$alvk'";
		}
		else {
				echo "<b>".t("Suorituspäiväkirja päivältä")." $alvv-$alvk-$alvp</b><hr>";
				$lisa = "tiliointi.tapvm = '$alvv-$alvk-$alvp'";
		}
	}

	$lisa .= " and tiliointi.tilino = '" . $yhtiorow['myyntisaamiset'] . "'";

	$query = "SELECT lasku.nimi, lasku.laskunro, tiliointi.tapvm, -1 * tiliointi.summa summa, tiliointi.ltunnus
				FROM tiliointi, lasku
				WHERE tiliointi.yhtio = '$kukarow[yhtio]' and
					tiliointi.yhtio = lasku.yhtio and
					tiliointi.ltunnus = lasku.tunnus and
					tiliointi.tapvm = lasku.mapvm and
					tiliointi.korjattu = '' and
					$lisa
				ORDER BY $jarj";

	$result = mysql_query($query) or pupe_error($query);

	echo "<table><tr>";
	for ($i = 0; $i < mysql_num_fields($result)-1; $i++) {
		echo "<th>" . t(mysql_field_name($result,$i)) . "</th>";
	}
	while ($trow=mysql_fetch_array ($result)) {
		echo "<tr>";
		for ($i=0; $i<mysql_num_fields($result)-1; $i++) {
			if ($i==0) echo "<td><a href = 'muutosite.php?tee=E&tunnus=$trow[ltunnus]'>$trow[$i]</td>";
			else echo "<td>$trow[$i]</td>";
		}
		echo "</tr>";
		$summa+=$trow['summa'];
	}

	echo "<tr><td></td><td></td><td align = 'right'>".t("Yhteensä")."</td>";
	echo "<td align = 'right'>";
	printf("%.2f", $summa);
	echo "</td></tr>";
	echo "</table><br>";
?>
