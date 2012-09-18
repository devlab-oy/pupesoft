<div class='header'>
	<h1><?php echo $title ?></h1>
</div>

<div class='main'>
	<form action'inventointi.php' method='post'>
	<table>
		<tr>
			<th nowrap>Pakkaus 1</th>
			<td><input type='text' id='p1' onkeyup='update_maara(this);' value='7'></td>
			<td id='p1_kerroin'>1</td>
			<td>KPL</td>
			<td id='p1_ulkkpl'>7</td>
		</tr>
		<tr>
			<th>Pakkaus 2</th>
			<td><input type='text' id='p2' onkeyup='update_maara(this);' value='30' /></td>
			<td id='p2_kerroin'>10</td>
			<td>PAK</td>
			<td id='p2_ulkkpl'>300</td>
		</tr>
		<tr>
			<th>Pakkaus 3</th>
			<td><input type='text' id='p3' onkeyup='update_maara(this);' value='2'></td>
			<td id='p3_kerroin'>100</td>
			<td>LAVA</td>
			<td id='p3_ulkkpl'>200</td>
		</tr>
	</table>
	<table style='text-align: center;'>
		<tr>
			<th>M‰‰r‰ yhteens‰</th>
			<td>
				<input type='text' id='maara' name='maara' value='570' readonly>
			</td>
		</tr>
	</table>

	<input type='hidden' name='tee' value='laske_maara'>
	<!--<input type='submit' value='OK'>-->
	<!--<a href='inventointi.php?tee=laske_maara'><input type='button' value='Lopeta'></a>-->

	<input class="color button green" type='submit' value='OK'/>
	<a href='inventointi.php?tee=laske_maara' class='color red button'>LOPETA</a>
	</form>
</div>

<script type='text/javascript'>
	function update_maara(el) {
		var maara = parseInt(el.value);
		var kerroin = parseInt(document.getElementById(el.id + '_kerroin').innerHTML);
		document.getElementById(el.id + '_ulkkpl').innerHTML = maara * kerroin;

		update_summa();
	}
	function update_summa() {
		var p1 = parseInt(document.getElementById('p1_ulkkpl').innerHTML);
		var p2 = parseInt(document.getElementById('p2_ulkkpl').innerHTML);
		var p3 = parseInt(document.getElementById('p3_ulkkpl').innerHTML);
		document.getElementById('maara').value = p1 + p2 + p3;
	}

</script>