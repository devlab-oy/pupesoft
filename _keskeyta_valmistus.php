<div class='head'><?php echo t("Keskeytä työ") ?></div>

<form method='POST'>
<input type='hidden' name='tee' value='update'>
<input type='hidden' name='tunnus' value='<?php echo $valmistus->tunnus() ?>'>
<input type='hidden' name='tila' value='<?php echo $tila ?>'>

<table>
	<tr>
		<th><?php echo t("Valmistus") ?></th>
		<td><?php echo $valmistus->tunnus() ?></td>
	</tr>
	<tr>
		<th><?php echo t("Ylityötunnit") ?></th>
		<td><input type='text' name='ylityotunnit'></td>
	</tr>
	<tr>
		<th><?php echo t("Käytetyt tunnit") ?></th>
		<td><input type='text' name='kaytetyttunnit'></td>
	</tr>
	<tr>
		<th><?php echo t("Kommentti") ?></th>
		<td><input type='text' name='kommentti'></td>
	</tr>

	<tr>
		<th><?php echo t("Valmiste") ?></th>
		<th><?php echo t("Määrä") ?></th>
	</tr>

	<?php foreach($valmistus->tuotteet() as $valmiste) { ?>
		<tr>
			<td><?php echo $valmiste['nimitys'] ?></td>
			<td><?php echo $valmiste['varattu'] ?></td>
		</tr>
	<?php } ?>

</table>

<a href='valmistuslinjojen_tyojonot.php'><?php echo t("Takaisin") ?></a>
<input type='submit' value='Valmis'>

</form>

