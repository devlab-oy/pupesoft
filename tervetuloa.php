<?php

require ("inc/parametrit.inc");

if (!isset($tee) or $tee == '') {

	if (file_exists("tervetuloa_".$kukarow["yhtio"].".inc")) {
		require("tervetuloa_".$kukarow["yhtio"].".inc");
	}

	echo "<table>";
	echo "<tr>";

	///* Uutiset *///
	echo "<tr><td class='back' valign='top'>";
	$toim = "";
	require("uutiset.php");
	echo "</td>";

	///* Hyväksyttävät laskut*///
	echo "<td class='back' width='10'></td>";

	echo "<td class='back' valign='top' width='350'>";

	// haetaan kaikki yritykset, jonne tämä käyttäjä pääsee
	$query  = "	SELECT distinct yhtio.yhtio, yhtio.nimi from kuka
				join yhtio using (yhtio)
				where kuka='$kukarow[kuka]'";
	$kukres = mysql_query($query) or pupe_error($query);

	while ($kukrow = mysql_fetch_array($kukres)) {

		$query = "	SELECT count(*) FROM lasku
					WHERE hyvaksyja_nyt = '$kukarow[kuka]' and yhtio = '$kukrow[yhtio]' and alatila = 'H' and tila!='D'
					ORDER BY erpcm";
		$result = mysql_query($query) or pupe_error($query);
		$piilorow=mysql_fetch_array ($result);

		$query = "	SELECT tapvm, erpcm 'eräpvm', ytunnus, nimi, round(summa * vienti_kurssi, 2) 'kotisumma'
					FROM lasku
					WHERE hyvaksyja_nyt = '$kukarow[kuka]' and yhtio = '$kukrow[yhtio]' and alatila!='H' and tila!='D'
					ORDER BY erpcm";
		$result = mysql_query($query) or pupe_error($query);

		if ((mysql_num_rows($result) > 0) or ($piilorow[0] > 0)) {

			echo "<table width='100%'>";

			// ei näytetä suotta firman nimeä, jos käyttäjä kuuluu vaan yhteen firmaan
			if (mysql_num_rows($kukres) == 1) $kukrow["nimi"] = "";

			echo "<tr><td colspan='".mysql_num_fields($result)."' class='back'><font class='head'>".t("Hyväksyttävät laskusi")." $kukrow[nimi]</font><hr></td></tr>";

			if ($piilorow[0] > 0)
				echo "<tr><td colspan='".mysql_num_fields($result)."' class='back'>". sprintf(t('Sinulla on %d pysäytettyä laskua'), $piilorow[0]) . "</tr>";

			if (mysql_num_rows($result) > 0) {
				for ($i = 1; $i < mysql_num_fields($result); $i++) {
					echo "<th>" . t(mysql_field_name($result,$i))."</th>";
				}
				while ($trow=mysql_fetch_array ($result)) {
					echo "<tr>";
					for ($i=1; $i<mysql_num_fields($result); $i++) {
						if (mysql_field_name($result,$i) == "nimi" and $kukrow["yhtio"] == $kukarow["yhtio"]) {
							echo "<td><a href='hyvak.php'>$trow[$i]</a></td>";
						}
						else {
							echo "<td>$trow[$i]</td>";
						}
					}
					echo "</tr>";
				}
			}
			echo "</table><br><br>";
		}

	}

	///* RUOKALISTA *///
	$query = "	SELECT *, kalenteri.tunnus tun, year(pvmalku) vva, month(pvmalku) kka, dayofmonth(pvmalku) ppa, year(pvmloppu) vvl, month(pvmloppu) kkl, dayofmonth(pvmloppu) ppl
				from kalenteri
				left join kuka on kuka.yhtio=kalenteri.yhtio and kuka.kuka=kalenteri.kuka
				where tyyppi='ruokalista'
				and kalenteri.yhtio='$kukarow[yhtio]'
				and pvmalku<=now()
				and pvmloppu>=now()
				LIMIT 1";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {
		while($uutinen = mysql_fetch_array($result)) {
			echo "
				<table width='100%'>
				<tr><td colspan='5' class='back'><font class='head'>".t("Ruokalista")." $uutinen[ppa].$uutinen[kka].-$uutinen[ppl].$uutinen[kkl].$uutinen[vvl]</font><hr></td></tr>
				<tr><th>".t("Maanantai")."</th></tr>
				<tr><td valign='top'>$uutinen[kentta01]</td></tr>
				<tr><th>".t("Tiistai")."</th></tr>
				<tr><td valign='top'>$uutinen[kentta02]</td></tr>
				<tr><th>".t("Keskiviikko")."</th></tr>
				<tr><td valign='top'>$uutinen[kentta03]</td></tr>
				<tr><th>".t("Torstai")."</th></tr>
				<tr><td valign='top'>$uutinen[kentta04]</td></tr>
				<tr><th>".t("Perjantai")."</th></tr>
				<tr><td valign='top'>$uutinen[kentta05]</td></tr>
				</table>";
		}
	}

	echo "</td>";
	echo "</tr>";
	echo "</table>";
}

require("inc/footer.inc");

?>
