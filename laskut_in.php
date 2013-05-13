<?php
/**
 * Päivittää myyntireskontran laskuja maksetuksi Excel-tiedostosta.
 *
 */

include "inc/parametrit.inc";

// Formilta on saatu tiedosto
if (isset($_FILES['userfile']['tmp_name']) and is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {

	// Pilkotaan tiedostonimi osiin
	$path_parts = pathinfo($_FILES['userfile']['name']);
	$ext = strtoupper($path_parts['extension']);

	// Tarkistetaan tiedostopääte
	if ($ext != 'XLS') {
		exit("<font class='error'><br>".t("Ainoastaan .xls tiedostot sallittuja")."!</font>");
	}

	// Tarkistetaan että tiedosto ei ole tyhjä
	if ($_FILES['userfile']['size'] == 0) {
		exit("<font class='error'<br>" . t("Tiedosto on tyhjä") . "!</font>");
	}

	// Luetaan excel tiedosto
	$tiedosto = pupeFileReader($_FILES['userfile']['tmp_name'], 'xls');

	// Loopataan excelistä tunnukset läpi (maksamattomat laskut)
	foreach ($tiedosto as $rivi) {
		$maksamattomat_laskut[] = $rivi[0];
	}

	// TODO: Haetaan kaikki avoimet laskut.
	// Jos tunnus löytyy avoimista laskuista niin skipataan.
	/**
	 * Toimii 'käänteisesti' ja päivittä ne lasku maksetuksi joita EI löydy
	 * sisäänluettavasta tiedostosta.
	 */

	// Päivitetään ne laskut maksetuksi joita ei löydy maksamattomat_laskut-listasta
	$query = "UPDATE lasku
				SET mapvm = now()
				WHERE yhtio='{$kukarow['yhtio']}'
				AND mapvm = '0000'
				AND laskunro NOT IN (" . implode(', ', $maksamattomat_laskut) . ")";
	echo "Päivitetään laskuja maksetuiksi<br>";
	echo $query."<br>";
	# $result = pupe_query();

	// Päivitetään kaikki kaikki laskut jotka löytyvät maksamattomat listasta.
	// Tämä on vain varokeino jos joku lasku on merkattu maksetuksi.
	$query = "UPDATE lasku
				SET mapvm = '0000'
				WHERE yhtio='{$kukarow['yhtio']}'
				AND mapvm != '0000'
				AND laksunro IN (" . implode(', ', $maksamattomat_laskut) . ")";
	echo "Päititetään laskuja maksamattomiksi<br>";
	echo $query."<br>";
	# $result = pupe_query();

}
// Form
else {

	echo "<font class='head'>".t("Myyntireskontran sisäänluku")."</font><hr>";

	echo "<form method='post' name='sendfile' enctype='multipart/form-data'>
			<table>
				<tr>
					<th>". t("Valitse tiedosto") . ":</th>
					<td><input name='userfile' type='file'></td>
					<td class='back'><input type='submit' value='" . t("Lähetä") . "'></td>
				<tr>
			</table>
		</form>";
}