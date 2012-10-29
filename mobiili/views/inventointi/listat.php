<div class='header'>
	<button onclick='window.location.href="inventointi.php"' class='button left'><img src='back2.png'></button>
	<h1><?php echo $title ?></h1>
</div>

<form action='inventointi.php' method='post'>
<div class='main'>
	<table>
		<tr>
			<th>Listan nro</th>
			<th>Tuotteita</th>
			<th>Hyllyalue väli</th>
		</tr>

		<? foreach($listat as $lista): ?>
			<tr>
				<td><a href='<?= $lista['url'] ?>'><?= $lista['lista'] ?></a></td>
				<td><?= $lista['tuotteita'] ?></td>
				<td><?= $lista['hyllyvali'] ?></td>
			</tr>
		<? endforeach ?>

	</table>
</div>
<div class='controls'>
	<input type='submit' value='OK'>
</div>
</form>

