<?php

// tehd‰‰n lock file, ettei voi kerralla ajaa kuin yks per firma
file_put_contents("/tmp/$kukarow[yhtio]-keikka.lock", "$otunnus");

// katsotaan, ett‰ kaikilla tuotteilla on joku varastopaikka
$query  = "select * from tilausrivi where uusiotunnus='$otunnus' and yhtio='$kukarow[yhtio]'";
$result = mysql_query($query) or pupe_error($query);

$eipaikkoja = 0;  // apumuuttuja
$eituotteet = ""; // apumuuttuja

while ($rivirow = mysql_fetch_array($result)) {
	$query = "select * from tuote where tuoteno='$rivirow[tuoteno]' and yhtio='$kukarow[yhtio]'";
	$tuore = mysql_query($query) or pupe_error($query);
	$tuote = mysql_fetch_array($tuore);

	// jos kyseess‰ on saldollinen tuote
	if ($tuote['ei_saldoa'] == "") {
		$query = "select * from tuotepaikat where tuoteno='$rivirow[tuoteno]' and yhtio='$kukarow[yhtio]' and oletus!=''";
		$tpres = mysql_query($query) or pupe_error($query);

		// jos tuotteella ei ole varastopaikkaa, lis‰t‰‰n dummy-paikka
		if (mysql_num_rows($tpres)==0) {
			$eipaikkoja++;
			$eituotteet .= $rivirow["tuoteno"]." ";
		}
	}
}

// takaisin selaukseen
$toiminto = "";
$ytunnus  = $laskurow["ytunnus"];

if ($eipaikkoja == 0) {
	$query = "select * from lasku where tunnus='$otunnus' and yhtio='$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 1) {
		$laskurow = mysql_fetch_array($result);

		// vied‰‰n oikeasti varastoon tai sitten n‰ytet‰‰n vaan ruudulla..
		if ($tee == "varastoon") {
			require ("varastoon.inc");
		}
		else {

			if ($tee == "eitullia") {
				//merkataan ne rivit joille ei pid‰ laskea tullia
				$query = "	UPDATE tilausrivi
							SET var='', netto='X'
							WHERE uusiotunnus='$laskurow[tunnus]' and yhtio='$kukarow[yhtio]'";
				$varresult = mysql_query($query) or pupe_error($query);

				if (count($eitullia) > 0) {
					foreach($eitullia as $eitul) {
						$query = "	UPDATE tilausrivi
									SET var='T'
									WHERE tunnus='$eitul' and uusiotunnus='$laskurow[tunnus]' and yhtio='$kukarow[yhtio]'";
						$varresult = mysql_query($query) or pupe_error($query);
					}
				}

				if (count($varastoonko) > 0) {
					foreach($varastoonko as $varas) {
						$query = "	UPDATE tilausrivi
									SET netto=''
									WHERE tunnus='$varas' and uusiotunnus='$laskurow[tunnus]' and yhtio='$kukarow[yhtio]'";
						$varresult = mysql_query($query) or pupe_error($query);
					}
				}

				$tee = "";
			}

			require("varastoon_ruudulle.inc");

			echo "<form action='$PHP_SELF' method='post'>";
			echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
			echo "<input type='hidden' name='toiminto' value='kalkyyli'>";
			echo "<input type='hidden' name='tee' value='varastoon'>";
			echo "<input type='hidden' name='otunnus' value='$otunnus'>";
			echo "<input type='submit' value='".t("Hyv‰lt‰ n‰ytt‰‰, vie varastoon!")."'>";
			echo "</form>";

			echo "<form action='$PHP_SELF' method='post'>";
			echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
			echo "<input type='hidden' name='toiminto' value=''>";
			echo "<input type='hidden' name='ytunnus' value='$laskurow[ytunnus]'>";
			echo "<input type='submit' value='".t("Peruuta")."'>";
			echo "</form>";
		}

		// ei n‰ytet‰ en‰‰ muuta
		$toiminto = "dummy";
	}
	else {
		echo "<font class='error'>".t("Lasku")." $otunnus ".t("katosi")."!</font><br>";
	}
}
else {
	echo "<font class='error'>".t("Laskulla olevista tuotteista")." $eipaikkoja".t(":lta puuttuu varastopaikka tai oletuspaikka! Korjaa asia").".<br>".t("Tuotteet").": $eituotteet</font><br>";
}

// poistetaan lock file
unlink("/tmp/$kukarow[yhtio]-keikka.lock");

?>