<!-- form.php, uuden tekemiseen ja-->
<div class='header'>
	<h1><?php echo $title ?></h1>
</div>

<div class='main'>
<form method='POST' action=''>

<!-- _form.php -->
	<table>
		<tr>
			<th>Tyyppi</th>
			<td>
				<select name='tyyppi'>
					<?php
						$sel = '';
						foreach($pakkaukset as $pakkaus) {
							if (isset($suuntalava)) {
								$sel = ($pakkaus['tunnus'] == $suuntalava['ptunnus']) ? ' selected' : '';
							}
							echo "<option value='{$pakkaus['tunnus']}' $sel>";
							echo t_tunnus_avainsanat($pakkaus, "pakkaus", "PAKKAUSKV")." ".t_tunnus_avainsanat($pakkaus, "pakkauskuvaus", "PAKKAUSKV");
							echo "</option>";
						}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<th>Keräysvyöhyke</th>
			<td>
				<select name='keraysvyohyke'>
					<?php
						$sel = '';
						foreach($keraysvyohykkeet as $vyohyke) {
							if (isset($suuntalava)) {
								$sel = ($vyohyke['tunnus'] == $suuntalava['keraysvyohyke']) ? ' selected' : '';
							}
							echo "<option value='{$vyohyke['tunnus']}' $sel $disabled>";
							echo $vyohyke['nimitys'];
							echo "</option>";
						}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<th>Hyllyalue</th>
			<td><input type='text' name='hyllyalue' value='<?php echo $hyllyalue ?>' <?php echo $disabled ?>/></td>
		</tr>
		<tr>
			<th>Käytettävyys</th>
			<?php
				$checked = array('','');
				if (isset($suuntalava)) {
					$checked = array(($suuntalava['kaytettavyys'] == 'Y') ? 'checked' : '',
									 ($suuntalava['kaytettavyys'] == 'L') ? 'checked' : '');
				}
				echo "<td>Yksityinen<input type='radio' name='kaytettavyys' value='Y' {$checked[0]} /></td>";
				echo "<td>Yleinen<input type='radio' name='kaytettavyys' value='L' {$checked[1]} /></td>";
			?>
		</tr>
		<tr><th>Terminaalialue</th>
			<?php
				if (isset($suuntalava)) {
					$checked = array(($suuntalava['terminaalialue'] == 'Lähettämö') ? 'checked' : '',
									 ($suuntalava['terminaalialue'] == 'Pakkaamo') ? 'checked' : '');
				}
				echo "<td>Lähettämö<input type='radio' name='terminaalialue' value='Lähettämö' {$checked[0]} /></td>";
				echo "<td>Pakkaamo<input type='radio' name='terminaalialue' value='Pakkaamo' {$checked[1]} /></td>";
			?>
		</tr>
		<tr><th>Sallitaanko</th>
			<?php
				if (isset($suuntalava)) {
					$checked = array(($suuntalava['usea_keraysvyohyke'] == 'K') 	? 'checked' : '',
									 ($suuntalava['usea_keraysvyohyke'] == '') 	? 'checked' : '');
				}
				echo "<td>Kyllä<input type='radio' name='sallitaanko' value='K' {$checked[0]} /></td>";
				echo "<td>Ei<input type='radio' name='sallitaanko' value='' {$checked[1]} /></td>";
			?>
		</tr>
	</table>
<!-- end_form -->

</div>

<div class='controls'>
	<input type='submit' name='submit' value='OK' />
	<a href='suuntalavat.php?tee=siirtovalmis&suuntalava=<?php echo $suuntalava['tunnus'] ?>'>Siirtovalmis</a>
	<a href='suuntalavat.php'>Takaisin </a>
</div>
</form>