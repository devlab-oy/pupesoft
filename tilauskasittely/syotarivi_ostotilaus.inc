<?php

	require ('../inc/alvpopup.inc');

	// Annetaan mahdollisuus tehdä uusi tilausrivi tai näytetään virhe
	echo "<table border='0' cellspacing='1' cellpadding='2'>
			<tr><th align='left'>".t("tuoteno")."</th>
			<th align='left'>".t("kpl_rivillä")."</th>
			<th align='left'>".t("kpl")."</th>
			<th align='left'>".t("hinta")."</th>
			<th align='left'>".t("ale")."</th>
			<th align='left'>".t("alv")."</th>
			<th align='left'>".t("viestit")."</th>
			<td class='back'></td>
			</tr>";

	// Kursorinohjausta
	$formi='rivi';
	if ($kentta == '') {
		$kentta='tuoteno';
	}

	// Syötetään rivi
	echo "	<tr><form name='rivi' action = '$PHP_SELF#$rivitunnus' method='post' autocomplete='off'>
			<input type='hidden' name='toiminto' value='$toiminto'>
			<input type='hidden' name='tee' value = 'TI'>
			<input type='hidden' name='tilausnumero' value='$tilausnumero'>
			<input type='hidden' name='rivitunnus' value='$rivitunnus'>
			<input type='hidden' name='perheid2' value='$perheid2'>
			<input type='hidden' name='muuta' value = '$muuta'>
			<input type='hidden' name='otunnus' value = '$otunnus'>
			<input type='hidden' name='muut_siirrettavat' value = '$muut_siirrettavat'>
			<input type='hidden' name='rivinotunnus' value = '$rivinotunnus'>
			<input type='hidden' name='rivinvar' value = '$var'>";

	if($ulos != '') { // Nyt me selataan
		// Meilla on kaikki valmiina $ulos:ssa
		echo "<td>$ulos</td>";
	}
	else {
		echo "<td><input type='text' size='18' name='tuoteno' value = '$tuoteno'></td>";
	}

	echo "<td>$kpl</td>
		<td><input type='text' size='5' name='kpl' value='$kpl'></td>
		<td><input type='text' size='5' name='hinta' value = '$hinta'></td>
		<td><input type='text' size='5' name='ale' value = '$ale'></td>";

	if ($alv=='') $alv=$laskurow['alv'];

	echo "<td>".alv_popup_oletus('alv',$alv)."</td><td>";

	if ($automatiikka == "ON" and $onjodropdowni != 'ON' and $laskurow['tila'] != 'O') {
		echo "	".t("Valtse toiminto").": <select name='var'>";
		echo "<option value=''>".t("Oletus")."</option>";
		echo "<option value='LOJ#$kpl'>".t("Lisää syötetyt ja merkkaa erotus JT:ksi")."</option>
			<option value='LOP#$kpl'>".t("Lisää syötetyt ja merkkaa erotus puutteeksi")."</option>";
		echo "</select>";
	}

	// Ostohinnan päivityskysely
	if ($paiv == 'X') {
		echo "	<input type='radio' name='paiv' value='K'>".t("Päivitä")."<br>";
		echo "	<input type='radio' name='paiv' value='E' CHECKED>".t("Älä Päivitä")."<br>";
	}

	echo " $varaosavirhe</td>";
	echo "<td class='back'><input type='Submit' name= 'kumpi' value = '".t("Lisää")."'></td></tr>";

	echo "<tr>
			<th align='left'>".t("Toimitusaika")."</th>
			<th colspan='6' align='left'>".t("Kommentti")."</th>
			<td class='back'></td>
			</tr>";

	echo "<tr>
			<td><input type='text' size='12' name='toimaika' value='$toimaika'></td>
			<td colspan='6'><input type='text' size='50' name='kommentti' value = '$kommentti'></td>
			</form>";


	//Tyhjennetään kentät
	echo "	<form name='rivi' action = '$PHP_SELF' method='post' autocomplete='off'>
			<input type='hidden' name='toiminto' value='$toiminto'>
			<input type='hidden' name='tee' value = 'TY'>
			<input type='hidden' name='tilausnumero' value='$tilausnumero'>
			<input type='hidden' name='muuta' value = '$muuta'>
			<input type='hidden' name='otunnus' value = '$otunnus'>
			<input type='hidden' name='muut_siirrettavat' value = '$muut_siirrettavat'>
			<td class='back'><input type='Submit' name= 'kumpi' value = '".t("Tyhjennä")."'></td></form></tr>";

	echo "</table><br>";
?>