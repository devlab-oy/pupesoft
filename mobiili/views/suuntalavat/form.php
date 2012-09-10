<!-- form.php, uuden tekemiseen ja-->
<div class='header'>
	<a href='suuntalavat.php' class='button left'>Takaisin </a>
	<h1><?php echo $title ?></h1>
</div>

<div class='main'>
<form action='' method='post'>

<!-- _form.php -->
	<table>
		<tr>
			<?php if(!isset($muokkaa)): ?>
			<th>Valitse tulostin:</th>
			<td>
				<select name='tulostin'>
					<?php
						foreach($kirjoittimet as $kirjoitin) {
							echo "<option value='{$kirjoitin['tunnus']}'>{$kirjoitin['kirjoitin']}</option>";
						}
					?>
				</select>
			</td>
			<?php endif ?>
		</tr>
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
			<th>Alkuhylly</th>
			<td>
				<input type='text' name='alkuhyllyalue' size='5' maxlength='5' value='<?= $suuntalava['alkuhyllyalue'] ?>' <?= $disabled ?> >
				<input type='text' name='alkuhyllynro' size='5' maxlength='5' value='<?= $suuntalava['alkuhyllynro'] ?>' <?= $disabled ?> >
				<input type='text' name='alkuhyllyvali' size='5' maxlength='5' value='<?= $suuntalava['alkuhyllyvali'] ?>' <?= $disabled ?> >
				<input type='text' name='alkuhyllytaso' size='5' maxlength='5' value='<?= $suuntalava['alkuhyllytaso'] ?>' <?= $disabled ?> >
			</td>
		</tr>
		<tr>
			<th>Loppuhylly</th>
			<td>
				<input type='text' name='loppuhyllyalue' size='5' maxlength='5' value='<?= $suuntalava['loppuhyllyalue'] ?>' <?= $disabled ?> >
				<input type='text' name='loppuhyllynro' size='5' maxlength='5' value='<?= $suuntalava['loppuhyllynro'] ?>' <?= $disabled ?> >
				<input type='text' name='loppuhyllyvali' size='5' maxlength='5' value='<?= $suuntalava['loppuhyllyvali'] ?>' <?= $disabled ?> >
				<input type='text' name='loppuhyllytaso' size='5' maxlength='5' value='<?= $suuntalava['loppuhyllytaso'] ?>' <?= $disabled ?> ></td>
			</tr>
		</tr>
		<tr>
			<th>Käytettävyys</th>
			<?php
				$checked = array('','');
				if (isset($suuntalava)) {
					$checked = array(($suuntalava['kaytettavyys'] == 'Y') ? 'checked' : '',
									 ($suuntalava['kaytettavyys'] == 'L') ? 'checked' : '');
				}
				echo "<td><input type='radio' name='kaytettavyys' id='yksityinen' value='Y' {$checked[0]} /><label for='yksityinen'>Yksityinen</label></td>";
				echo "<td><input type='radio' name='kaytettavyys' id='yleinen' value='L' {$checked[1]} /><label for='yleinen'>Yleinen</label></td>";
			?>
		</tr>
		<tr><th>Terminaalialue</th>
			<?php
				if (isset($suuntalava)) {
					$checked = array(($suuntalava['terminaalialue'] == 'Lähettämö') ? 'checked' : '',
									 ($suuntalava['terminaalialue'] == 'Pakkaamo') ? 'checked' : '');
				}
				echo "<td><input type='radio' name='terminaalialue' id='lahettamo' value='Lähettämö' {$checked[0]} /><label for='lahettamo'>Lähettämö</label></td>";
				echo "<td><input type='radio' name='terminaalialue' id='pakkaamo' value='Pakkaamo' {$checked[1]} /><label for='pakkaamo'>Pakkaamo</label></td>";
			?>
		</tr>
		<tr><th>Sallitaanko</th>
			<?php
				if (isset($suuntalava)) {
					$checked = array(($suuntalava['usea_keraysvyohyke'] == 'K') ? 'checked' : '',
									 ($suuntalava['usea_keraysvyohyke'] == '') 	? 'checked' : '');
				}
				echo "<td><input type='radio' name='sallitaanko' id='kylla' value='K' {$checked[0]} /><label for='kylla'>Kyllä</label></td>";
				echo "<td><input type='radio' name='sallitaanko' id='ei' value='' {$checked[1]} /><label for='ei'>Ei</label></td>";
			?>
		</tr>
	</table>
<!-- end_form -->

</div>

<div class='controls'>
	<input type='submit' name='post' value='OK' >
		<? if(isset($muokkaa)): ?>
		<a href='suuntalavat.php?tee=siirtovalmis&suuntalava=<?php echo $suuntalava['tunnus'] ?>' class='right'>Siirtovalmis (normaali)</a>
		<a href='suuntalavat.php?tee=suoraan_hyllyyn&suuntalava=<?php echo $suuntalava['tunnus'] ?>' class='right'>Siirtovalmis (suoraan hyllyyn)</a>
		<? endif ?>
</div>
</form>