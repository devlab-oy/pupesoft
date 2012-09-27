<div class='header'>
	<button onclick='window.location.href="inventointi.php?tee=haku"' class='button left'><img src='back2.png'></button>
	<h1><?php echo $title ?></h1>
</div>

<div class='main'>

	<form action='inventointi.php' method='post'>
		<input type='text' name='tee' value='laske'>
		<table>
			<tr>
				<th></th>
				<th></th>
				<th>Tuoteno</th>
				<th>Tuotepaikka</th>
			</tr>

			<?php foreach($tuotteet as $tuote): ?>
				<?php $url = http_build_query(array('tee' => 'laske', 'tuotepaikka' => $tuote['tuotepaikka'], 'tuoteno' => $tuote['tuoteno'])) ?>
				<tr>
					<td><input type='radio' name='tuotepaikka' value='<?= $tuote['tuotepaikka'] ?>'></td>
					<td><input type='text' name='tuoteno' value='<?= $tuote['tuoteno'] ?>'></td>
					<td><a href='inventointi.php?<?= $url ?>'><?= $tuote['tuoteno'] ?></a></td>
					<td><?= $tuote['tuotepaikka'] ?></td>
					<td><? if($tuote['inventointilista'] != 0 and $tuote['inventointilista_aika'] != '0000-00-00 00:00:00') echo "({$tuote['inventointilista']})" ?></td>
				</tr>
			<?php endforeach ?>

			<tr>
				<td colspan='4'>
					<div class='controls'>
						<input type='submit' value='OK'>
						<a href='?tee=haku' class='button'>Lopeta</a>
					</div>
				</td>
			</tr>
		</table>
	</form>

</div>
<pre>
Haettu:
<?
	if (!empty($viivakoodi)) echo "viivakoodilla";
	if (!empty($tuoteno)) echo "tuotenumerolla";
	if (!empty($tuotepaikka)) echo "tuotepaikalla";

?>
</pre>