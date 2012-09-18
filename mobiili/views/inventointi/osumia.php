<div class='header'>
	<button onclick='window.location.href="inventointi.php"' class='button left'><img src='back2.png'></button>
	<h1><?php echo $title ?></h1>
</div>

<div class='main'>

	<form action='inventointi.php' method='post'>
		<table>
			<tr>
				<th>Tuoteno</th>
				<th>Tuotepaikka</th>
			</tr>

			<?php foreach($osumat as $osuma): ?>
				<tr>
					<td><a href='inventointi.php?tee=varmistuskoodi&tuotepaikka=<?= $osuma['tuotepaikka'] ?>'><?= $osuma['tuoteno'] ?></a></td>
					<td><?= $osuma['tuotepaikka'] ?></td>
				</tr>
			<?php endforeach ?>
			<tr>
				<td colspan='3'>
					<div class='controls'>
						<input type='submit' value='OK'>
						<input type='submit' value='LOPETA'>
					</div>
				</td>
			</tr>
		</table>
	</form>

</div>

