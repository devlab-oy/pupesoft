<?php
/**
 * Päivittää myyntireskontran laskuja maksetuksi Excel-tiedostosta.
 *
 */

include "inc/parametrit.inc";

$errors = array();

// Formilta on saatu tiedosto
if (isset($_FILES['userfile']['tmp_name']) and is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {

	// Pilkotaan tiedostonimi osiin
	$path_parts = pathinfo($_FILES['userfile']['name']);
	$ext = strtoupper($path_parts['extension']);

	// Tarkistetaan tiedostopääte
	if ($ext != 'XLS') {
		$errors[] = "<font class='error'><br>".t("Ainoastaan .xls tiedostot sallittuja")."!</font>";
	}

	// Tarkistetaan että tiedosto ei ole tyhjä
	if ($_FILES['userfile']['size'] == 0) {
		$errors[] = "<font class='error'<br>" . t("Tiedosto on tyhjä") . "!</font>";
	}

	if (empty($errors)) {
		// Luetaan excel tiedosto
		$tiedosto = pupeFileReader($_FILES['userfile']['tmp_name'], 'xls');

		// Loopataan excelistä tunnukset läpi (maksamattomat laskut)
		foreach ($tiedosto as $rivi) {
			$maksamattomat_laskut[] = $rivi[0];
		}

		// Jos tunnus löytyy avoimista laskuista niin skipataan.
		$query = "SELECT laskunro, tunnus
					FROM lasku
					WHERE yhtio='{$kukarow['yhtio']}'
					AND mapvm = '0000-00-00 00:00'
					AND tila = 'U'
					AND alatila = 'X'";
		$result = pupe_query($query);

		echo "<font class='message'>";
		echo t("Avoimia laskuja yhteensä") . ": " . mysql_num_rows($result) . "<br>";
		echo "</font>";

		/**
		 * Toimii 'käänteisesti' ja päivittä ne lasku maksetuksi joita EI löydy
		 * sisäänluettavasta tiedostosta.
		 */

		// Päivitetään ne laskut maksetuksi joita ei löydy maksamattomat_laskut-listasta
		$query = "UPDATE lasku
					SET mapvm = now()
					WHERE yhtio='{$kukarow['yhtio']}'
					AND mapvm = '0000-00-00 00:00'
					AND tila = 'U'
					AND alatila = 'X'
					AND laskunro NOT IN (" . implode(', ', $maksamattomat_laskut) . ")";
		$result = pupe_query($query);

		echo "<font class='message'>";
		echo mysql_affected_rows() . " " . t("laskua päivitetty maksetuksi") . "<br>";
		echo "</font>";

		// Päivitetään kaikki kaikki laskut jotka löytyvät maksamattomat listasta.
		// Tämä on vain varokeino jos joku lasku on merkattu maksetuksi.
		$query = "UPDATE lasku
					SET mapvm = '0000-00-00 00:00'
					WHERE yhtio='{$kukarow['yhtio']}'
					AND tila = 'U'
					AND alatila = 'X'
					AND mapvm != '0000-00-00 00:00'
					AND laskunro IN (" . implode(', ', $maksamattomat_laskut) . ")";
		$result = pupe_query($query);

		echo "<font class='message'>";
		echo mysql_affected_rows() . " " . t("laskua korjattu maksamattomaksi") . "<br>";
		echo "</font>";
	}

}
// Form
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

// Virheet
if ($errors) {
	foreach($errors as $error) {
		echo $error;
	}
}