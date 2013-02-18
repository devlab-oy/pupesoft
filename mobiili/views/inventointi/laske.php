<div class='header'>
	<button onclick='window.location.href="inventointi.php"' class='button left'><img src='back2.png'></button>
	<h1><?php echo $title ?></h1>
</div>

<form method='post' action='inventointi.php'>
<div class='main'>
	<input type='hidden' name='tuoteno' value='<?php echo $tuote['tuoteno'] ?>'>
	<input type='hidden' name='tuotepaikka' value='<?php echo $tuote['tuotepaikka'] ?>'>
	<input type='hidden' name='lista' value='<?php echo $tuote['inventointilista'] ?>'>
	<input type='hidden' name='tuotepaikalla' value='<?php echo $tuotepaikalla ?>'>
	<input type='hidden' name='reservipaikka' value='<?php echo $reservipaikka ?>'>
	<table>
		<tr>
			<th>M‰‰r‰</th>
			<td>
				<input type='text' name='maara' value='<?php echo $maara ?>' size='6' autofocus>
				<b><?php echo $tuote['yksikko'] ?><b>
			</td>
		</tr>
		<?php if (!empty($tuote['tyyppi'])): ?>
		<tr>
			<th>SSCC</th>
			<td><?php echo $sscc ?></td>
		</tr>
		<?php endif ?>
		<tr>
			<th>Tuote</th>
			<td><?php echo $tuote['tuoteno'] ?></td>
		</tr>
		<tr>
			<th>Nimitys</th>
			<td><?php echo $tuote['nimitys'] ?></td>
		</tr>
		<tr>
			<th>Tuotepaikka</th>
			<td><?php echo $tuote['tuotepaikka'] ?></td>
		</tr>
		<tr>
			<th>Inventointi selite</th>
			<td>
				<?php $sel = "";?>
				<select name="inventointi_seliteen_tunnus">
					<?php foreach($inventointi_selitteet as $inventointi_selite) {
						if(!empty($inventointi_selite['selitetark_2'])) {
							$sel = "SELECTED";
						}
					?>
					<option value="<?php echo $inventointi_selite['tunnus'] ?>" <?php echo $sel ?>><?php echo $inventointi_selite['selite'] ?></option>
					<?php
						$sel = "";
					}
					?>
				</select>
			</td>
		</tr>
	</table>
</div>
<div class='controls'>
	<input type='hidden' name='tee' value='inventoi'>
	<input type='submit' name='inventoidaan' value='OK'>
	<?php if($apulaskuri_url != ''): ?>
		<a class='button right' href='inventointi.php?<?php echo $apulaskuri_url ?>'>Apulaskuri</a>
	<?php endif ?>
</div>
</form>