<!-- valikko.php -->
<div class='header'>
	<h1><?php echo $title ?></h1>
</div>

<div class='main'>

	<table>
		<tr>
			<td><a href='suuntalavat.php?tee=uusi';><?php echo t("Uusi suuntalava") ?></a></td>
		</tr>
		<tr>
			<td><a href='suuntalavat.php?tee=muokkaa';><?php echo t("Muokkaa suuntalavaa") ?></a></td>
		</tr>
		<tr>
			<td><a href='suuntalavat.php?tee=siirtovalmis';><?php echo t("Suuntalava siirtovalmiiksi") ?></a></td>
		</tr>
	</table>

</div>

<div class='controls'>
	<a href='suuntalavat.php'><?php echo t("Takaisin") ?> </a>
</div>
