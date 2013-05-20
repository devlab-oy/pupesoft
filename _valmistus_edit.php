<div class='head'><?php echo t("Valmista tarkastukseen") ?></div>

<form method='POST'>
<input type='hidden' name='tee' value='update'>
<input type='hidden' name='tunnus' value='<?php echo $valmistus->tunnus() ?>'>
<input type='hidden' name='tila' value='<?php echo $tila ?>'>

<table>
	<tr>
		<th><?php echo t("Valmistus") ?></th>
		<td colspan='3'><?php echo $valmistus->tunnus() ?></td>
	</tr>
	<tr>
		<th><?php echo t("Kommentti") ?></th>
		<td colspan='3'><input type='text' name='kommentti' size='40'></td>
	</tr>
	<tr>
		<th><?php echo t("Ylityötunnit") ?></th>
		<td colspan='3'><input type='text' name='ylityotunnit'></td>
	</tr>
	<tr>
		<th><?php echo t("Aloitusaika") ?></th>
		<td colspan='3'><input type='text' name='pvmalku' value='<?php echo $valmistus->pvmalku ?>'></td>
	</tr>
	<tr>
		<th><?php echo t("Lopetusaika") ?></th>
		<td colspan='3'><input type='text' name='pvmloppu' value='<?php echo date('Y-m-d H:i:s', round_time(strtotime('now'))) ?>'></td>
	</tr>

	<tr>
		<th><?php echo t("Valmiste") ?></th>
		<th><?php echo t("Määrä") ?></th>
		<th><?php echo t("Valmistettava määrä") ?></th>
		<th><?php echo t("Ylityötunnit") ?></th>
	</tr>

	<?php foreach($valmistus->tuotteet() as $valmiste) { ?>
		<tr>
			<td><?php echo $valmiste['nimitys'] ?></td>
			<td><?php echo $valmiste['varattu'] ?></td>
			<td><input type='text' name='valmisteet[<?php echo $valmiste['tuoteno'] ?>][maara]' value='<?php echo $valmiste['varattu'] ?>'></td>
			<td><input type='text' name='valmisteet[<?php echo $valmiste['tuoteno'] ?>][tunnit]'></td>
		</tr>
	<?php } ?>

</table>

<a href='valmistuslinjojen_tyojonot.php'><?php echo t("Takaisin") ?></a>
<input type='submit' value='<?php echo t("Valmis") ?>'>

</form>

