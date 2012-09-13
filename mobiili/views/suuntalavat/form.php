<!-- form.php, uuden tekemiseen ja-->
<div class='header'>
	<button onclick='window.location.href="suuntalavat.php"' class='button left'><img src='back2.png'></button>
	<h1><?php echo $title ?></h1>
</div>

<div class='main'>
<form action='' method='post' onsubmit='check_disabled();'>
<!-- _form.php -->
	<table>
		<tr>
			<?php if(!isset($muokkaa)): ?>
			<th>Valitse tulostin</th>
			<td>
				<select name='tulostin'>
					<?php
						foreach($kirjoittimet as $kirjoitin) {
							echo "<option value='{$kirjoitin['tunnus']}' size='5'>{$kirjoitin['kirjoitin']}</option>";
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
							echo "<option class='keraysvyohyke' value='{$vyohyke['tunnus']}' $sel $disabled>";
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
				<input type='text' class='hylly' name='alkuhyllyalue' size='3' maxlength='5' value='<?= $suuntalava['alkuhyllyalue'] ?>' <?= $disabled ?> >
				<input type='text' class='hylly' name='alkuhyllynro' size='3' maxlength='5' value='<?= $suuntalava['alkuhyllynro'] ?>' <?= $disabled ?> >
				<input type='text' class='hylly' name='alkuhyllyvali' size='3' maxlength='5' value='<?= $suuntalava['alkuhyllyvali'] ?>' <?= $disabled ?> >
				<input type='text' class='hylly' name='alkuhyllytaso' size='3' maxlength='5' value='<?= $suuntalava['alkuhyllytaso'] ?>' <?= $disabled ?> >
			</td>
		</tr>
		<tr>
			<th>Loppuhylly</th>

			<td>
				<input type='text' class='hylly' name='loppuhyllyalue' size='3' maxlength='5' value='<?= $suuntalava['loppuhyllyalue'] ?>' <?= $disabled ?> >
				<input type='text' class='hylly' name='loppuhyllynro' size='3' maxlength='5' value='<?= $suuntalava['loppuhyllynro'] ?>' <?= $disabled ?> >
				<input type='text' class='hylly' name='loppuhyllyvali' size='3' maxlength='5' value='<?= $suuntalava['loppuhyllyvali'] ?>' <?= $disabled ?> >
				<input type='text' class='hylly' name='loppuhyllytaso' size='3' maxlength='5' value='<?= $suuntalava['loppuhyllytaso'] ?>' <?= $disabled ?> ></td>
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
				echo "<td><input type='radio' name='kaytettavyys' id='yksityinen' value='Y' {$checked[0]} /><label for='yksityinen'>Yksityinen</label>";
				echo "<input type='radio' name='kaytettavyys' id='yleinen' value='L' {$checked[1]} /><label for='yleinen'>Yleinen</label></td>";
			?>
		</tr>
		<tr><th>Terminaalialue</th>
			<?php
				if (isset($suuntalava)) {
					$checked = array(($suuntalava['terminaalialue'] == 'Lähettämö') ? 'checked' : '',
									 ($suuntalava['terminaalialue'] == 'Pakkaamo') ? 'checked' : '');
				}
				echo "<td><input type='radio' name='terminaalialue' id='lahettamo' value='Lähettämö' {$checked[0]} /><label for='lahettamo'>Lähettämö</label>";
				echo "<input type='radio' name='terminaalialue' id='pakkaamo' value='Pakkaamo' {$checked[1]} /><label for='pakkaamo'>Pakkaamo</label></td>";
			?>
		</tr>
		<tr><th>Sallitaanko</th>
			<?php
				if (isset($suuntalava)) {
					$checked = array(($suuntalava['usea_keraysvyohyke'] == 'K') ? 'checked' : '',
									 ($suuntalava['usea_keraysvyohyke'] == '') 	? 'checked' : '');
				}
				echo "<td><input type='radio' name='sallitaanko' id='kylla' value='K' {$checked[0]} /><label for='kylla'>Kyllä</label>";
				echo "<input type='radio' name='sallitaanko' id='ei' value='' {$checked[1]} /><label for='ei'>Ei</label></td>";
			?>
		</tr>
	</table>
<!-- end_form -->

</div>

<div class='controls'>
	<input type='submit' name='post' value='OK' class='button left' />
	</form>
		<? if(isset($muokkaa)): ?>
		<!--
		<a href='suuntalavat.php?tee=siirtovalmis&suuntalava=<?php echo $suuntalava['tunnus'] ?>' class='right'>Siirtovalmis (normaali)</a>
		<a href='suuntalavat.php?tee=suoraan_hyllyyn&suuntalava=<?php echo $suuntalava['tunnus'] ?>' class='right'>Siirtovalmis (suoraan hyllyyn)</a>
		-->
		<button onclick='window.location.href="suuntalavat.php?tee=siirtovalmis&suuntalava=<?php echo $suuntalava['tunnus'] ?>"' class='button right' <?= $disable_siirtovalmis ?>>Siirtovalmis (normaali)</button>
		<button onclick='window.location.href="suuntalavat.php?tee=suoraan_hyllyyn&suuntalava=<?php echo $suuntalava['tunnus'] ?>"' class='button right' <?= $disable_siirtovalmis ?>>Siirtovalmis (suoraan hyllyyn)</button>

		<? endif ?>
</div>

<script type='text/javascript'>
	function check_disabled() {
		hyllyt = document.getElementsByClassName('hylly');

		for(i=0; i < hyllyt.length; i++) {
			hyllyt[i].disabled = false;
		}

		keraysvyohykkeet = document.getElementsByClassName('keraysvyohyke');

		for(i=0; i < keraysvyohykkeet.length; i++) {
			keraysvyohykkeet[i].disabled = false;
		}
	}
</script>