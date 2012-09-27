<div class='header'>
	<button onclick='window.location.href="inventointi.php"' class='button left'><img src='back2.png'></button>
	<h1><?php echo $title ?></h1>
</div>

<div class='main'>

	<form action='inventointi.php' method='post'>
		<table>
			<tr>
				<th>Listan nro</th>
				<th>Tuotteita</th>
				<th>Hyllyalue väli</th>
			</tr>

			<? foreach($listat as $lista): ?>
				<tr>
					<td><a href='?tee=laske&lista=<?= $lista['lista'] ?>'><?= $lista['lista'] ?></a></td>
					<td><?= $lista['tuotteita'] ?></td>
					<td><?= $lista['hyllyvali'] ?></td>
					<td><?= $ensimmainen['tuotepaikka'] ?></td>
				</tr>
			<? endforeach ?>

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

