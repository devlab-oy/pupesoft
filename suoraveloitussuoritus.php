<?php

require('inc/parametrit.inc');

echo "<font class='head'>".t("Suoraveloitusten kohdistus suorituksiin")."</font><hr>";

if ($tee == 'V') {

	// Lasku on valittu ja sitä tiliöidään (suoritetaan)
	$query = "	SELECT *
				FROM tiliointi
				WHERE tunnus = '$stunnus' and yhtio = '$kukarow[yhtio]' and tilino ='$yhtiorow[selvittelytili]'";
	$result = mysql_query($query) or pupe_error($query);
	$tiliointirow = mysql_fetch_array($result);

	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Suoritus katosi")."!</font><br>";
		exit;
	}

	$query = "	SELECT *
				FROM lasku
				WHERE tunnus = '$tunnus' and yhtio = '$kukarow[yhtio]' and tila = 'Q'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Lasku katosi, tai sen on joku jo suorittanut")."!</font><br>";
		exit;
	}

	$laskurow = mysql_fetch_array($result);

	// Oletustiliöinnit
	// Ostovelat
	$query = "	INSERT INTO tiliointi SET
				yhtio = '$kukarow[yhtio]',
				ltunnus = '$laskurow[tunnus]',
				tilino = '$yhtiorow[ostovelat]',
				tapvm = '$tiliointirow[tapvm]',
				summa = '$laskurow[summa]',
				vero = 0,
				lukko = '',
				laatija = '$kukarow[kuka]',
				laadittu = now()";
	$xresult = mysql_query($query) or pupe_error($query);

	// Rahatili
	$query = "	INSERT INTO tiliointi SET
				yhtio = '$kukarow[yhtio]',
				ltunnus = '$laskurow[tunnus]',
				tilino = '$yhtiorow[selvittelytili]',
				tapvm = '$tiliointirow[tapvm]',
				summa = -1 * $laskurow[summa],
				vero = 0,
				lukko = '',
				laatija = '$kukarow[kuka]',
				laadittu = now()";
	$xresult = mysql_query($query) or pupe_error($query);

	$query = "	UPDATE lasku set
				tila = 'Y',
				mapvm = '$tiliointirow[tapvm]',
				maksu_kurssi = 1
				WHERE tunnus = '$tunnus'";
	$xresult = mysql_query($query) or pupe_error($query);
	$tee = '';

}

//Näytetään kohdistamattomat
if ($tee == '') {

	echo "<table>";

	// katotaan jos meillä on jotain selvittelytilejä pankkitilien takana
	$query = "	SELECT oletus_selvittelytili from yriti where yhtio='$kukarow[yhtio]' and oletus_selvittelytili!=''";
	$result = mysql_query($query) or pupe_error($query);

	$selvittelytilit = "'$yhtiorow[selvittelytili]',";
	while ($trow = mysql_fetch_array($result)) {
		$selvittelytilit .= "'$trow[oletus_selvittelytili]',";
	}
	$selvittelytilit = substr($selvittelytilit, 0, -1); // vika pilkku pois

	$query = "	SELECT nimi nimi, lasku.tapvm tapvm, ifnull(tiliointi.tapvm, 'Ei sopivaa suoritusta') suorituspvm, tiliointi.selite tilioteselite, lasku.summa, lasku.tunnus, tiliointi.tunnus stunnus
				FROM lasku use index (yhtio_tila_mapvm)
				LEFT JOIN tiliointi ON (tiliointi.yhtio = lasku.yhtio and tiliointi.tilino in ($selvittelytilit) and tiliointi.summa = lasku.summa and korjattu = '')
				WHERE lasku.yhtio  = '$kukarow[yhtio]'
				AND lasku.tila = 'Q'
				AND lasku.mapvm = '0000-00-00'
				AND lasku.suoraveloitus != ''";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {

		echo "<font class='message'>".t("Kohdistamattomat suoraveloituslaskut")."</font>";

		echo "<tr>";
		for ($i = 0; $i < mysql_num_fields($result)-2; $i++) {
			echo "<th>" . t(mysql_field_name($result,$i)) . "</th>";
		}
		echo "<th></th></tr>";

		while ($trow = mysql_fetch_array ($result)) {
			echo "<tr>";
			for ($i = 0; $i < mysql_num_fields($result)-2; $i++) {
				echo "<td>" . $trow[$i] . "</td>";
			}
			if ($trow['suorituspvm'] != 'Ei sopivaa suoritusta') {
				echo "<td><form name = 'valinta' action = '$PHP_SELF' method='post'>
					<input type = 'hidden' name = 'tee' value = 'V'>
					<input type = 'hidden' name = 'tunnus' value = '$trow[tunnus]'>
					<input type = 'hidden' name = 'stunnus' value = '$trow[stunnus]'>
					<input type = 'submit' value = '".t("suorita")."'></form></td>";
			}
			else {
				echo "<td><a href='$palvelin2","muutosite.php?tee=E&tunnus=$trow[tunnus]'>Tutki</a></td>";
			}
			echo "</tr>";
		}
		echo "</table>";
	}
	else {
		echo t("Ei kohdistamattomia suoraveloituksia");
	}
}

require ('inc/footer.inc');

?>
