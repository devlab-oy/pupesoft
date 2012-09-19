<div class='header'>
	<button onclick='window.location.href="inventointi.php?tee=vapaa_inventointi"' class='button left'><img src='back2.png'></button>
	<h1><?php echo $title ?>
</div>

<div class='main'>
	<form method='post'>
		<table>
			<input type='hidden' name='tee' value='varmistuskoodi'>
			<input type='text' name='tuoteno' value='<?= $tuoteno ?>'>
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
				<td><a href='inventointi.php?tee=vapaa_inventointi'><input type='button' name='cancel' value='Lopeta'></a></td>
			</tr>
		</table>
	</form>
</div>
