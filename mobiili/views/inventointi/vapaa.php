<div class='header'>
	<button onclick='window.location.href="inventointi.php"' class='button left'><img src='back2.png'></button>
	<h1><?php echo $title ?></h1>
</div>

<div class='main'>

	<form action='inventointi.php' method='get'>
		<input type='hidden' name='tee' value='haku'>
		<table>
			<tr>
				<th>Viivakoodi</th>
				<td><input type='text' name='viivakoodi'></td>
			</tr>
			<tr>
				<th>Tuoteno</th>
				<td><input type='text' name='tuoteno'></td>
			</tr>
			<tr>
				<th>Tuotepaikka</th>
				<td><input type='text' name='tuotepaikka'></td>
			</tr>
			<tr>
				<td colspan='2'>
					<div class='controls'>
						<input type='submit' value='OK'>
						<a href='inventointi.php'>Lopeta</a>
					</div>
				</td>
			</tr>
		</table>
	</form>

</div>

