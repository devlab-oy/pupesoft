<?php
include "inc/parametrit.inc";

echo "<br><font class='head'>Myyntireskontran tapahtumaraportointi</font><hr>";

$oikeus = true; // includeissa tarkistetaan t‰m‰n avulla, onko k‰ytt‰j‰ll‰ oikeutta tehd‰ ko. toimintoa

if ($tila == 'tee_raportti') {
  include('myyntireskontra_tapahtumaraportointi_tee_raportti.php');
} else {
  $formi='valinta';
  $kentta='submitti';
  echo "<b>P‰‰- ja p‰iv‰kirja</b><hr>
				<form name=\"$formi\" action = '$PHP_SELF' method='post'>
				<input type = 'hidden' name = 'tila' value = 'tee_raportti'>
				<table><tr>
				<td>Tyyppi</td>
				<td><select name='tee'>
				<option value = 'K'>P‰‰kirja
				<option value = 'P'>P‰iv‰kirja
				</select></td></tr>
				<td>Ajalta</td>
				<td><select name='alvv'>";

				for ($i = date("Y"); $i >= date("Y")-4; $i--) {
					if ($i == date("Y")) $sel = "selected";
					else $sel = "";
					echo "<option value='$i' $sel>$i</option>";
				}

echo "			</select>
				<select name='alvk'>
				<option value = '0'>koko vuosi
				<option value = '1'>01
				<option value = '2'>02
				<option value = '3'>03
				<option value = '4'>04
				<option value = '5'>05
				<option value = '6'>06
				<option value = '7'>07
				<option value = '8'>08
				<option value = '9'>09
				<option value = '10'>10
				<option value = '11'>11
				<option value = '12'>12
				</select>
				<select name='alvp'>
				<option value = '0'>koko kuukausi
				<option value = '1'>01
				<option value = '2'>02
				<option value = '3'>03
				<option value = '4'>04
				<option value = '5'>05
				<option value = '6'>06
				<option value = '7'>07
				<option value = '8'>08
				<option value = '9'>09
				<option value = '10'>10
				<option value = '11'>11
				<option value = '12'>12
				<option value = '13'>13
				<option value = '14'>14
				<option value = '15'>15
				<option value = '16'>16
				<option value = '17'>17
				<option value = '18'>18
				<option value = '19'>19
				<option value = '20'>20
				<option value = '21'>21
				<option value = '22'>22
				<option value = '23'>23
				<option value = '24'>24
				<option value = '25'>25
				<option value = '26'>26
				<option value = '27'>27
				<option value = '28'>28
				<option value = '29'>29
				<option value = '30'>30
				<option value = '31'>31
				</select></td>
				</tr>
				<tr>
				<td>Vain tili</td>
				<td><input type = 'text' name = 'tili' value = ''></td>
				</tr>";
	echo "<tr><td>Asiakas</td><td><input type='text' name= 'asiakas' value=''></td></tr>";
  echo "<tr><td>Excel muoto</td><td><input type='checkbox' name='excel'></td></tr>";
  echo "<tr><td></td>
		      <td><input type='submit' value='N‰yt‰' name=\"$kentta\" ></td></tr></table></form>";

}

include "inc/footer.inc";

?>
