<div class='header'>
	<button onclick='window.location.href="inventointi.php"' class='button left'><img src='back2.png'></button>
	<h1><?php echo $title ?>
</div>

<div class='main'>
	<form method='post'>
		<table>
			<tr>
				<th>Koodi</th>
				<td><input type='text' name='varmistuskoodi' size='10'></td>
			</tr>
			<tr>
				<th>Osoite</th>
				<td><input type='text' name='tuotepaikka' value='<?php echo $tuote['tuotepaikka'] ?>' size='10'></td>
			</tr>
			<tr>
				<td><input type='submit' value='OK'></td>
			</tr>
		</table>
	</form>
</div>
