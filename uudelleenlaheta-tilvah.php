<?php

//* T�m� skripti k�ytt�� slave-tietokantapalvelinta *//
$useslave = 1;

require ("inc/parametrit.inc");

echo "<font class='head'>".t("Uudelleenl�het� tilausvahvistus")."</font><hr>";

if ($tee == "laheta" and $tunnukset != "") {

	$query = "SELECT * FROM lasku WHERE yhtio = '$kukarow[yhtio]' AND tila in ('N','L') AND tunnus in ($tunnukset)";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {

		while ($laskurow = mysql_fetch_array($result)) {

			echo t("Uudelleenl�hetet��n tilausvahvistus")." ($laskurow[tilausvahvistus]): $laskurow[nimi]<br>";

			// L�HETET��N TILAUSVAHVISTUS
			$params_tilausvahvistus = array(
			'tee'						=> $tee,
			'toim'						=> $toim,
			'kieli'						=> $kieli,
			'komento'					=> $komento,
			'laskurow'					=> $laskurow,
			'naytetaanko_rivihinta'		=> $naytetaanko_rivihinta,
			'extranet_tilausvahvistus'	=> $extranet_tilausvahvistus,
			);

			laheta_tilausvahvistus($params_tilausvahvistus);
		}

	}
	else {
		print "<font class='error'>".t("Tilauksia ei l�ytynyt").": $tunnukset!</font><br>";
	}
}
else {
	echo "<font class='message'>".t("Anna tilausnumerot pilkulla eroteltuna")."</font><br>";
	echo "<form method='post'>";
	echo "<input type='hidden' name='tee' value='laheta'>";
	echo "<input name='tunnukset' type='text' size='60'>";
	echo "<input type='submit' value='".t("L�het� tilausvahvistukset")."'>";
	echo "</form>";
}

?>