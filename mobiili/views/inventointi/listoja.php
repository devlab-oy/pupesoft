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
			<tr>
				<td>6234</td>
				<td>10</td>
				<td>7 - 8</td>
			</tr>
			<tr>
				<td>62345</td>
				<td>12</td>
				<td>1 - 1</td>
			</tr>
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

