<!-- lista.php | index.php? , listaa suuntalavat (muokkaa suuntalava ja suuntalava siirtovalmiiksi) -->
<div class='header'>
	<a href='suuntalavat.php' class='left'>Takaisin</a>
	<h1><?php echo $title ?></h1>
</div>

<div class='main'>

<form method='get' action='suuntalavat.php'>
	<input type='hidden' name='tee' value='muokkaa'>
	<table>
		<tr>
			<th><?= t("Hae suuntalava") ?>:</th>
			<td><input type='text' name='hae'></td>
			<td><input type='submit' value='Hae' /></td>
		</tr>
	</table>
</form>

	<table>
		<tr>
			<th></th>
			<th><?= t("Suuntalavan nro") ?></th>
			<th><?= t("Ker.vyöhyk.") ?></th>
			<th><?= t("Rivejä") ?></th>
			<th><?= t("Tyyppi") ?></th>
		</tr>

		<?php foreach($suuntalavat as $lava): ?>
		<tr>
			<td><input type='radio' class='radio' /></td>
			<td><a href='suuntalavat.php?tee=muokkaa&suuntalava=<?php echo $lava['tunnus'] ?>'><?php echo $lava['sscc'] ?></a></td>
			<td><?= $lava['keraysvyohyke'] ?></td>
			<td><?= $lava['rivit'] ?></td>
			<td><?= $lava['tyyppi'] ?></td>
		<tr>
		<?php endforeach ?>
	</table>

</div>

<div class='controls'>
	<input type='submit' name='submit' value='OK' />
	<a href='suuntalavat.php'>Takaisin </a>
</div>

