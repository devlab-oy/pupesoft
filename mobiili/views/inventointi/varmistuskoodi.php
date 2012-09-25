<div class='header'>
	<button onclick='window.location.href="inventointi.php?<?= $back ?>"' class='button left'><img src='back2.png'></button>
	<h1><?php echo $title ?>
</div>

<div class='main'>
	<form method='post'>
		<table>
			<input type='text' name='tee' value='varmistuskoodi'>
			<input type='text' name='lista' value='<?= $lista ?>'>
			tuoteno<input type='text' name='tuoteno' value='<?= $tuoteno ?>'>
			<tr>
				<th>Koodi</th>
				<td><input type='text' name='varmistuskoodi'></td>
			</tr>
			<tr>
				<th>Osoite</th>
				<td><input type='text' name='tuotepaikka' value='<?php echo $tuotepaikka ?>'></td>
			</tr>
			<tr>
				<td><input type='submit' value='OK'></td>
			</tr>
		</table>
	</form>
</div>
