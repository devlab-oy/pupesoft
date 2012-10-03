<div class='header'>
	<button onclick='window.location.href="inventointi.php"' class='button left'><img src='back2.png'></button>
	<h1><?php echo $title ?>
</div>

<form method='post'>
	<div class='main'>
		<table>
			<tr>
				<th>Koodi</th>
				<td><input type='text' name='varmistuskoodi' size='10' autofocus></td>
			</tr>
			<tr>
				<th>Osoite</th>
				<td><input type='text' name='tuotepaikka' value='<?php echo $tuote['tuotepaikka'] ?>' size='10' readonly></td>
			</tr>
		</table>
	</div>
	<div class='controls'><input type='submit' value='OK'></div>
	</div>
</form>
