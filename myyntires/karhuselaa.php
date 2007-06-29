<?php include '../inc/parametrit.inc'; ?>

<font class='head'><?php echo t("Selaa karhuja") ?></font><hr />

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
		$where = sprintf("lasku.laskunro = %d", (int) $_POST['laskunro']);
	}
	else {
		$where = sprintf("lasku.ytunnus = '%s'", (int) $_POST['ytunnus']);
	}

	// haetaan uusin karhukierros/karhukerta
	$query = "SELECT karhu_lasku.ktunnus as tunnus, liitostunnus, ytunnus, concat_ws(' / ',lasku.nimi,lasku.toim_nimi) nimi
				FROM karhu_lasku
				JOIN lasku ON lasku.tunnus=karhu_lasku.ltunnus
				WHERE {$where} AND lasku.yhtio = '{$kukarow['yhtio']}'
				ORDER BY tunnus desc
				LIMIT 1";
	$res = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($res) > 0) {

		$ktunnus = mysql_fetch_array($res);

		echo "<br><font class='message'>Viimeinen karhukierros asiakkaalle ytunnus $ktunnus[ytunnus]:</font><br>";

		$query = "SELECT lasku.laskunro, lasku.summa, lasku.saldo_maksettu, concat_ws('<br>',lasku.nimi,lasku.toim_nimi) nimi, karhukierros.pvm, lasku.erpcm, lasku.ytunnus, karhu_lasku.ltunnus
					FROM karhu_lasku
					JOIN lasku ON (lasku.tunnus = karhu_lasku.ltunnus and lasku.liitostunnus = {$ktunnus['liitostunnus']})
					JOIN karhukierros ON (karhukierros.tunnus = karhu_lasku.ktunnus and karhukierros.yhtio = '{$kukarow['yhtio']}')
					WHERE karhu_lasku.ktunnus = '{$ktunnus['tunnus']}'";

	$res = mysql_query($query) or pupe_error($query);

	?>

	<br>
	<table>
		<tr>
			<th><?php echo t('Asiakas') ?></th>
			<th><?php echo t('Laskunro') ?></th>
			<th><?php echo t('Ytunnus') ?></th>
			<th><?php echo t('Summa') ?></th>
			<th><?php echo t('Maksettu') ?></th>
			<th><?php echo t('Karhuamis pvm') ?></th>
			<th><?php echo t('Eräpäivä') ?></th>
			<th><?php echo t('Karhukertoja') ?></th>
		</tr>
		<?php while ($row = mysql_fetch_array($res)): ?>
		<?php
		$query = "select count(distinct ktunnus) as summa from karhu_lasku where ltunnus={$row['ltunnus']}";
		$ka_res = mysql_query($query);
		$karhuttu = mysql_fetch_array($ka_res);
		?>
			<tr>
				<td><?php echo $row['nimi'] ?></td>
				<td><?php echo $row['laskunro'] ?></td>
				<td><?php echo $row['ytunnus'] ?></td>
				<td><?php echo $row['summa'] ?></td>
				<td><?php echo $row['saldo_maksettu'] ?></td>
				<td><?php echo tv1dateconv($row['pvm']) ?></td>
				<td><?php echo tv1dateconv($row['erpcm']) ?></td>
				<td style="text-align: right;"><?php echo $karhuttu['summa'] ?></td>
			</tr>
		<?php endwhile; ?>
	</table>

<?php
	}
	else {
		echo "<br><font class='message'>Yhtään karhua ei löytynyt!</font>";
	}
}
?>

<?php include '../inc/footer.inc' ?>
