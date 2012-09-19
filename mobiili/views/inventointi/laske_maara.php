<div class='header'>
	<button onclick='window.location.href="inventointi.php?tee=vapaa_inventointi"' class='button left'><img src='back2.png'></button>
	<h1><?php echo $title ?></h1>
</div>

<div class='main'>
	<table>
		<tr>
			<th>M‰‰r‰</th>
			<td><input type='text' value='<?= $maara ?>'></td>
			<td><?= $tuote['yksikko'] ?></td>
		</tr>
		<tr>
			<th>SSCC</th>
			<td>-</td>
		</tr>
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
			<td><?= $tuote['tuotepaikka'] ?></td>
		</tr>
	</table>

	<a href='inventointi.php?tee=inventoi' class='color green button'>OK</a>
	<a href='inventointi.php' class='color red button'>Lopeta</a>
	<? if($disabled): ?>
		<a href='inventointi.php?<?= http_build_query(array('tee' => 'apulaskuri', 'tuoteno' => $tuote['tuoteno'])) ?>' class='color blue button'>Apulaskuri</a>
	<? endif ?>
</div>