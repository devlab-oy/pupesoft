<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

require ("inc/parametrit.inc");

ini_set('zlib.output_compression', 0);
ini_set('implicit_flush', 1);
ob_implicit_flush(1);

echo "<font class='head'>".t("Uudelleenlähetä keraysvahvistus")."</font><hr>";

if ($tee == "laheta" and $tunnukset != "") {

	$query = "	SELECT lasku.*, asiakas.email
				FROM lasku
				JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus AND asiakas.email != '')
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				AND lasku.tila in ('N','L')
				AND lasku.tunnus in ($tunnukset)";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {
		while ($laskurow = mysql_fetch_assoc($result)) {

			echo t("Uudelleenlähetetään keräysvahvistus").": $laskurow[nimi] ({$laskurow['email']})<br>";
			flush();

			$komento = "asiakasemail".$laskurow['email'];

			$params = array(
				'laskurow'					=> $laskurow,
				'sellahetetyyppi' 			=> "",
				'extranet_tilausvahvistus' 	=> "",
				'naytetaanko_rivihinta'		=> "",
				'tee'						=> "",
				'toim'						=> "",
				'komento' 					=> $komento,
				'lahetekpl'					=> "",
				'kieli' 					=> "",
				'koontilahete'				=> "",
				);

			pupesoft_tulosta_lahete($params);

			sleep(1);
		}
	}
	else {
		echo "<font class='error'>".t("Tilauksia ei löytynyt").": $tunnukset!</font><br>";
	}
}
else {
	echo "<font class='message'>".t("Anna tilausnumerot pilkulla eroteltuna")."</font><br>";
	echo "<form method='post'>";
	echo "<input type='hidden' name='tee' value='laheta'>";
	echo "<textarea name='tunnukset' rows='10' cols='60'></textarea>";
	echo "<input type='submit' value='".t("Lähetä keräysvahvistukset")."'>";
	echo "</form>";
}

?>