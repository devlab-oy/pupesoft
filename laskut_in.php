<?php
/**
 * P�ivitt�� myyntireskontran laskuja maksetuksi Excel-tiedostosta.
 *
 */

include "inc/parametrit.inc";

// Formilta on saatu tiedosto
if (isset($_FILES['userfile']['tmp_name']) and is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {

	// Pilkotaan tiedostonimi osiin
	$path_parts = pathinfo($_FILES['userfile']['name']);
	$ext = strtoupper($path_parts['extension']);

	// Tarkistetaan tiedostop��te
	if ($ext != 'XLS') {
		exit("<font class='error'><br>".t("Ainoastaan .xls tiedostot sallittuja")."!</font>");
	}

	// Tarkistetaan ett� tiedosto ei ole tyhj�
	if ($_FILES['userfile']['size'] == 0) {
		exit("<font class='error'<br>" . t("Tiedosto on tyhj�") . "!</font>");
	}

	// Luetaan excel tiedosto
	$tiedosto = pupeFileReader($_FILES['userfile']['tmp_name'], 'xls');

	// Loopataan excelist� tunnukset l�pi (maksamattomat laskut)
	foreach ($tiedosto as $rivi) {
		$maksamattomat_laskut[] = $rivi[0];
	}

	// TODO: Haetaan kaikki avoimet laskut.
	// Jos tunnus l�ytyy avoimista laskuista niin skipataan.
	/**
	 * Toimii 'k��nteisesti' ja p�ivitt� ne lasku maksetuksi joita EI l�ydy
	 * sis��nluettavasta tiedostosta.
	 */

	// P�ivitet��n ne laskut maksetuksi joita ei l�ydy maksamattomat_laskut-listasta
	$query = "UPDATE lasku
				SET mapvm = now()
				WHERE yhtio='{$kukarow['yhtio']}'
				AND mapvm = '0000'
				AND laskunro NOT IN (" . implode(', ', $maksamattomat_laskut) . ")";
	echo "P�ivitet��n laskuja maksetuiksi<br>";
	echo $query."<br>";
	# $result = pupe_query();

	// P�ivitet��n kaikki kaikki laskut jotka l�ytyv�t maksamattomat listasta.
	// T�m� on vain varokeino jos joku lasku on merkattu maksetuksi.
	$query = "UPDATE lasku
				SET mapvm = '0000'
				WHERE yhtio='{$kukarow['yhtio']}'
				AND mapvm != '0000'
				AND laksunro IN (" . implode(', ', $maksamattomat_laskut) . ")";
	echo "P�ititet��n laskuja maksamattomiksi<br>";
	echo $query."<br>";
	# $result = pupe_query();

}
// Form
else {

	echo "<font class='head'>".t("Myyntireskontran sis��nluku")."</font><hr>";

	echo "<form method='post' name='sendfile' enctype='multipart/form-data'>
			<table>
				<tr>
					<th>". t("Valitse tiedosto") . ":</th>
					<td><input name='userfile' type='file'></td>
					<td class='back'><input type='submit' value='" . t("L�het�") . "'></td>
				<tr>
			</table>
		</form>";
}