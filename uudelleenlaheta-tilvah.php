<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

require ("inc/parametrit.inc");

echo "<font class='head'>".t("Uudelleenlähetä tilausvahvistus")."</font><hr>";

if ($tee == "laheta" and $tunnukset != "") {

	$query = "SELECT * FROM lasku WHERE yhtio = '$kukarow[yhtio]' AND tila in ('N','L') AND tunnus in ($tunnukset)";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {

		while ($laskurow = mysql_fetch_array($result)) {

			echo t("Uudelleenlähetetään tilausvahvistus")." ($laskurow[tilausvahvistus]): $laskurow[nimi]<br>";

			if (trim($laskurow['tilausvahvistus']) != "") {
				//
				// LÄHETETÄÄN TILAUSVAHVISTUS
				//
				// tulostetaan tässä, niin saadaan vahvistukseen koko tilaus, ennenkun sen splitaatan eri varastoihin
				$params_tilausvahvistus = array(
				'tee'						=> "",
				'toim'						=> "",
				'kieli'						=> "",
				'komento'					=> "",
				'laskurow'					=> $laskurow,
				'naytetaanko_rivihinta'		=> "",
				'extranet_tilausvahvistus'	=> "",
				);

				laheta_tilausvahvistus($params_tilausvahvistus);
			}
		}
	}
	else {
		print "<font class='error'>".t("Tilauksia ei löytynyt").": $tunnukset!</font><br>";
	}
}
else {
	echo "<font class='message'>".t("Anna tilausnumerot pilkulla eroteltuna")."</font><br>";
	echo "<form method='post'>";
	echo "<input type='hidden' name='tee' value='laheta'>";
	echo "<input name='tunnukset' type='text' size='60'>";
	echo "<input type='submit' value='".t("Lähetä tilausvahvistukset")."'>";
	echo "</form>";
}

?>