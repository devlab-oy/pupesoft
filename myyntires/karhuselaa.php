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
if (isset($_POST['tee']) && $_POST['tee'] == 'Hae') {
	if (! empty($_POST['laskunro'])) {
		$where = sprintf("lasku.laskunro = %d", (int) $_POST['laskunro']);
	} else {
		$where = sprintf("lasku.ytunnus = '%s'", (int) $_POST['ytunnus']);
	}
	
	// haetaan uusin karhukierros/karhukerta
	$query = "SELECT MAX(karhu_lasku.ktunnus) as tunnus, lasku.ytunnus
		FROM karhu_lasku
		JOIN lasku ON lasku.tunnus=karhu_lasku.ltunnus
		where {$where} AND lasku.yhtio = '{$kukarow['yhtio']}' GROUP BY tunnus";
	$res = mysql_query($query) or pupe_error($query);
	$ktunnus = mysql_fetch_array($res);

	$query = "SELECT lasku.laskunro, lasku.summa, lasku.saldo_maksettu, lasku.nimi, karhukierros.pvm, lasku.erpcm,
				lasku.ytunnus, karhu_lasku.ltunnus
				FROM lasku
				JOIN karhu_lasku ON karhu_lasku.ltunnus=lasku.tunnus
				JOIN karhukierros ON karhukierros.tunnus=karhu_lasku.ktunnus
				WHERE karhu_lasku.ktunnus = '{$ktunnus['tunnus']}'
				AND karhukierros.yhtio = '{$kukarow['yhtio']}'
				AND karhu_lasku.ltunnus
				AND lasku.ytunnus = '{$ktunnus['ytunnus']}'
				GROUP BY karhu_lasku.ltunnus";

$res = mysql_query($query) or pupe_error($query);

?>
<p><?php echo t('Viimeisin karhukerta ytunnukselle ') . $ktunnus['ytunnus'] ?>:</p>
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
?>

<?php include '../inc/footer.inc' ?>
