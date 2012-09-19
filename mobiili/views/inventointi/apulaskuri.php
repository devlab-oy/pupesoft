<div class='header'>
	<h1><?php echo $title ?></h1>
</div>

<div class='main'>
	<form action'inventointi.php' method='post'>
	<table border='1	'>
		<tr>
			<th nowrap>Pakkaus 1</th>
			<td><input type='text' id='p1' onkeyup='update_maara(this);' value='0'/></td>
			<td id='p1_kerroin'><?= $p1['myynti_era'] ?></td>
			<td><?= $p1['yksikko'] ?></td>
			<td id='p1_ulkkpl'>0</td>
		</tr>

		<? if($p2): ?>
		<tr>
			<th>Pakkaus 2</th>
			<td><input type='text' id='p2' onkeyup='update_maara(this);' value='0'/></td>
			<td id='p2_kerroin'><?= $p2['myynti_era'] ?></td>
			<td><?= $p2['yksikko'] ?></td>
			<td id='p2_ulkkpl'>0</td>
		</tr>
		<? endif ?>

		<? if($p3): ?>
		<tr>
			<th>Pakkaus 3</th>
			<td><input type='text' id='p3' onkeyup='update_maara(this);' value='0'/></td>
			<td id='p3_kerroin'><?= $p3['myynti_era'] ?></td>
			<td><?= $p3['yksikko'] ?></td>
			<td id='p3_ulkkpl'>0</td>
		</tr>
		<? endif ?>

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

	<input class="color button green" type='submit' value='OK'/>
	<a href='inventointi.php?<?= http_build_query(array('tee' => 'laske_maara', 'tuoteno' => $tuoteno)) ?>' class='color red button'>LOPETA</a>
	</form>
</div>

<script type='text/javascript'>
	function update_maara(el) {
		var maara = new Number(el.value);
		var kerroin = new Number(document.getElementById(el.id + '_kerroin').innerHTML);
		document.getElementById(el.id + '_ulkkpl').innerHTML = maara * kerroin;
		update_summa();
	}
	function update_summa() {
		// Lasketaan summa
		var summa = new Number();
		['p1_ulkkpl', 'p2_ulkkpl', 'p3_ulkkpl'].forEach(function(id) {
			elem = document.getElementById(id);
			if (elem != null) {
				summa += new Number(elem.innerHTML);
			}
		});

		// P‰ivitet‰‰n summa kenttt‰
		document.getElementById('maara').value = summa;
	}

</script>