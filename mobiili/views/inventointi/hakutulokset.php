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

		<?php foreach($tuotteet as $tuote) { ?>
			<?php if ($tuote['inventointilista'] != 0 and $tuote['inventointilista_aika'] != '0000-00-00 00:00:00') { ?>
				<tr>
					<td><?php echo $tuote['tuoteno'] ?></td>
					<td><?php echo $tuote['tuotepaikka'] ?></td>
					<td><?php echo "(".$tuote['inventointilista'].")" ?></td>
				</tr>
			<?php } else { ?>
				<?php $url = http_build_query(array(
									'tee' => 'laske',
									'tuotepaikka' => $tuote['tuotepaikka'],
									'tuoteno' => $tuote['tuoteno'],
									'tuotepaikalla' => $haku_tuotepaikalla)) ?>
				<tr>
					<td><a href='inventointi.php?<?php echo $url ?>'><?php echo $tuote['tuoteno'] ?></a></td>
					<td><?php echo $tuote['tuotepaikka'] ?></td>
					<td><?php if($tuote['inventointilista'] != 0 and $tuote['inventointilista_aika'] != '0000-00-00 00:00:00') echo "(listalla {$tuote['inventointilista']})" ?></td>
				</tr>
			<?php } ?>
		<?php } ?>
	</table>
</div>