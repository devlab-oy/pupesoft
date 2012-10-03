<div class='header'>
	<button onclick='window.location.href="inventointi.php"' class='button left'><img src='back2.png'></button>
	<h1><?php echo $title ?></h1>
</div>

<form method='post' action='inventointi.php'>
<div class='main'>
	<input type='hidden' name='tuoteno' value='<?= $tuote['tuoteno'] ?>'>
	<input type='hidden' name='tuotepaikka' value='<?= $tuote['tuotepaikka'] ?>'>
	<input type='hidden' name='lista' value='<?= $tuote['inventointilista'] ?>'>
	<input type='hidden' name='tuotepaikalla' value='<?= $tuotepaikalla ?>'>
	<input type='hidden' name='reservipaikka' value='<?= $reservipaikka ?>'>
	<table>
		<tr>
			<th>M‰‰r‰</th>
			<td>
				<input type='text' name='maara' value='<?= $maara ?>' size='6' autofocus>
				<b><?= $tuote['yksikko'] ?><b>
			</td>
		</tr>
		<? if (!empty($tuote['tyyppi'])): ?>
		<tr>
			<th>SSCC</th>
			<td><?= $sscc ?></td>
		</tr>
		<? endif ?>
		<tr>
			<th>Tuote</th>
			<td><?= $tuote['tuoteno'] ?></td>
		</tr>
		<tr>
			<th>Nimitys</th>
			<td><?= $tuote['nimitys'] ?></td>
		</tr>
		<tr>
			<th>Tuotepaikka</th>
			<td>
				<?= $tuote['tuotepaikka'] ?>
			</td>
		</tr>
	</table>
</div>
<div class='controls'>
	<input type='hidden' name='tee' value='inventoi'>
	<input type='submit' name='inventoidaan' value='OK'>
	<? if(!$disabled): ?>
		<a class='button right' href='inventointi.php?<?= http_build_query(array('tee' => 'apulaskuri', 'tuotepaikka' => $tuotepaikka, 'tuoteno' => $tuote['tuoteno'])) ?>'>Apulaskuri</a>
	<? endif ?>
</div>
</form>