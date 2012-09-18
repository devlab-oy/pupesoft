<div class='header'>
	<h1><?php echo $title ?>
</div>

<div class='main'>
	<form method='post'>
		<table>
			<tr>
				<th>Koodi</th>
				<td><input type='text' name='varmistuskoodi'></td>
			</tr>
			<tr>
				<th>Osoite</th>
				<td><input type='text' name='tuotepaikka' value='<?= $tuotepaikka ?>'></td>
			</tr>
			<tr>
				<td><input type='submit' value='OK'></td>
				<td><a href='inventointi.php'><input type='button' name='cancel' value='Lopeta'></a></td>
			</tr>
		</table>
	</form>
</div>
