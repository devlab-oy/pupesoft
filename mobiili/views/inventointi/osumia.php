<div class='header'>
	<button onclick='window.location.href="inventointi.php?tee=vapaa_inventointi"' class='button left'><img src='back2.png'></button>
	<h1><?php echo $title ?></h1>
</div>

<div class='main'>

	<form action='inventointi.php' method='post'>
		<input type='hidden' name='tee' value='varmistuskoodi'>
		<table>
			<tr>
				<th></th>
				<th></th>
				<th>Tuoteno</th>
				<th>Tuotepaikka</th>
			</tr>

			<?php foreach($osumat as $osuma): ?>
				<?php $url = http_build_query(array('tee' => 'varmistuskoodi', 'tuotepaikka' => $osuma['tuotepaikka'], 'tuoteno' => $osuma['tuoteno'])) ?>
				<tr>
					<td><input type='radio' name='tuotepaikka' value='<?= $osuma['tuotepaikka'] ?>'></td>
					<td><input type='text' name='tuoteno' value='<?= $osuma['tuoteno'] ?>'></td>
					<td><a href='inventointi.php?<?= $url ?>'><?= $osuma['tuoteno'] ?></a></td>
					<td><?= $osuma['tuotepaikka'] ?></td>
					<td><? if($osuma['inventointilista_aika'] != '0000-00-00 00:00:00') echo "({$osuma['inventointilista']})" ?></td>
				</tr>
			<?php endforeach ?>

			<tr>
				<td colspan='4'>
					<div class='controls'>
						<input type='submit' value='OK'>
						<a href='?tee=vapaa_inventointi' class='button'>Lopeta</a>
					</div>
				</td>
			</tr>
		</table>
	</form>

</div>

