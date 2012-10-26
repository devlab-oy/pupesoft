<div class='header'>
	<button onclick='window.location.href="inventointi.php?tee=haku"' class='button left'><img src='back2.png'></button>
	<h1><?php echo $title ?></h1>
</div>

<div class='main'>
	<table>
		<tr>
			<th>Tuoteno</th>
			<th>Tuotepaikka</th>
		</tr>

		<?php foreach($tuotteet as $tuote): ?>
			<? if ($tuote['inventointilista'] != 0 and $tuote['inventointilista_aika'] != '0000-00-00 00:00:00'): ?>
				<tr>
					<td><?= $tuote['tuoteno'] ?></td>
					<td><?= $tuote['tuotepaikka'] ?></td>
					<td><?= "(".$tuote['inventointilista'].")" ?></td>
				</tr>
			<? else: ?>
				<?php $url = http_build_query(array(
									'tee' => 'laske',
									'tuotepaikka' => $tuote['tuotepaikka'],
									'tuoteno' => $tuote['tuoteno'],
									'tuotepaikalla' => $haku_tuotepaikalla)) ?>
				<tr>
					<td><a href='inventointi.php?<?= $url ?>'><?= $tuote['tuoteno'] ?></a></td>
					<td><?= $tuote['tuotepaikka'] ?></td>
					<td><? if($tuote['inventointilista'] != 0 and $tuote['inventointilista_aika'] != '0000-00-00 00:00:00') echo "(listalla {$tuote['inventointilista']})" ?></td>
				</tr>
			<? endif ?>
		<?php endforeach ?>
	</table>
</div>