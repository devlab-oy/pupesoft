<?php

include '../inc/parametrit.inc';

if ($toim == "TRATTA") {
	echo "<font class='head'>".t("Selaa trattoja")."</font><hr />";
	$tyyppi = "T";
}
else {
	echo "<font class='head'>".t("Selaa karhuja")."</font><hr />";
	$tyyppi = "";
}

?>

<form name="karhu_selaa" action="" method="post">
<table>
	<tr>
		<th><?php echo t('Ytunnus') ?>:</th><td><input type="text" name="ytunnus"></td>
	</tr>
	<tr>
		<th><?php echo t('Laskunro') ?>:</th><td><input type="text" name="laskunro"></td>
		<td class="back"><input type="submit" name="tee" value="Hae"></td>
	</tr>
</table>
</form>

<?php

if (isset($_POST['tee']) and $_POST['tee'] == 'Hae') {

	if (!empty($_POST['laskunro'])) {
		$where = sprintf("and lasku.laskunro = %d", (int) $_POST['laskunro']);
	}
	elseif (!empty($_POST['laskunro'])) {
		$where = sprintf("and lasku.ytunnus = '%s'", (int) $_POST['ytunnus']);
	}

	// haetaan uusin karhukierros/karhukerta
	$query = "	SELECT karhu_lasku.ktunnus as tunnus, liitostunnus, ytunnus, concat_ws(' / ',lasku.nimi,lasku.toim_nimi) nimi
				FROM karhu_lasku
				JOIN lasku ON (lasku.tunnus = karhu_lasku.ltunnus and lasku.yhtio = '$kukarow[yhtio]' $where)
				JOIN karhukierros ON (karhukierros.tunnus = karhu_lasku.ktunnus and karhukierros.yhtio = lasku.yhtio and karhukierros.tyyppi = '$tyyppi')
				ORDER BY tunnus desc
				LIMIT 1";
	$res = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($res) > 0) {

		$ktunnus = mysql_fetch_array($res);

		if ($toim == "TRATTA") {
			echo "<br><font class='message'>Viimeinen trattakierros asiakkaalle ytunnus $ktunnus[ytunnus].</font>";
		}
		else {
			echo "<br><font class='message'>Viimeinen karhukierros asiakkaalle ytunnus $ktunnus[ytunnus].</font>";
		}

		$query = "	SELECT group_concat(karhu_lasku.ltunnus) laskutunnukset
					FROM karhu_lasku
					JOIN lasku ON (lasku.tunnus = karhu_lasku.ltunnus and lasku.liitostunnus = '$ktunnus[liitostunnus]')
					JOIN karhukierros ON (karhukierros.tunnus = karhu_lasku.ktunnus and karhukierros.yhtio = '$kukarow[yhtio]' and karhukierros.tyyppi = '$tyyppi')
					WHERE karhu_lasku.ktunnus = '$ktunnus[tunnus]'";
		$res = mysql_query($query) or pupe_error($query);
		$tunnukset = mysql_fetch_array($res);

		if ($toim == "TRATTA") {
			echo " <a href='".$palvelin2."muutosite.php?karhutunnus=$ktunnus[tunnus]&lasku_tunnus[]=$tunnukset[laskutunnukset]&tee=tulosta_tratta&nayta_pdf=1'>Näytä tratta</a><br>";
		}
		else {
			echo " <a href='".$palvelin2."muutosite.php?karhutunnus=$ktunnus[tunnus]&lasku_tunnus[]=$tunnukset[laskutunnukset]&tee=tulosta_karhu&nayta_pdf=1'>Näytä karhu</a><br>";
		}

		echo "<br>
			<table>
				<tr>
					<th>".t('Ytunnus')."</th>
					<th>".t('Asiakas')."</th>
					<th>".t('Laskunro')."</th>
					<th>".t('Summa')."</th>
					<th>".t('Maksettu')."</th>";

		if ($toim == "TRATTA") {
			echo "<th>".t('Tratta pvm')."</th>";
			echo "<th>".t('Eräpäivä')."</th>";
			echo "<th>".t('Trattakertoja')."</th>";
		}
		else {
			echo "<th>".t('Karhuamis pvm')."</th>";
			echo "<th>".t('Eräpäivä')."</th>";
			echo "<th>".t('Karhukertoja')."</th>";
		}

		echo "</tr>";

		$query = "	SELECT lasku.laskunro, lasku.summa, lasku.saldo_maksettu,
					if(lasku.nimi != lasku.toim_nimi and lasku.toim_nimi != '', concat_ws('<br>', lasku.nimi, lasku.toim_nimi), lasku.nimi) nimi,
					karhukierros.pvm, lasku.erpcm, lasku.ytunnus, karhu_lasku.ltunnus
					FROM karhu_lasku
					JOIN lasku ON (lasku.tunnus = karhu_lasku.ltunnus and lasku.liitostunnus = {$ktunnus['liitostunnus']})
					JOIN karhukierros ON (karhukierros.tunnus = karhu_lasku.ktunnus and karhukierros.yhtio = '{$kukarow['yhtio']}' and karhukierros.tyyppi = '$tyyppi')
					WHERE karhu_lasku.ktunnus = '{$ktunnus['tunnus']}'";
		$res = mysql_query($query) or pupe_error($query);

		while ($row = mysql_fetch_array($res)) {

			$query = "SELECT count(distinct ktunnus) as summa from karhu_lasku where ltunnus={$row['ltunnus']}";
			$ka_res = mysql_query($query);
			$karhuttu = mysql_fetch_array($ka_res);

			echo "<tr>
					<td>$row[ytunnus]</td>
					<td>$row[nimi]</td>
					<td>$row[laskunro]</td>
					<td>$row[summa]</td>
					<td>$row[saldo_maksettu]</td>
					<td>".tv1dateconv($row['pvm'])."</td>
					<td>".tv1dateconv($row['erpcm'])."</td>
					<td style='text-align: right;'>$karhuttu[summa]</td>
				</tr>";
		}

		echo "</table>";

	}
	else {
		echo "<br><font class='message'>Yhtään laskua ei löytynyt!</font>";
	}
}

include '../inc/footer.inc';

?>