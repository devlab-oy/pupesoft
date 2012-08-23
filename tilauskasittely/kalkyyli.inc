<?php

// tehd‰‰n lock file, ettei voi kerralla ajaa kuin yks per firma
file_put_contents("/tmp/$kukarow[yhtio]-keikka.lock", "$otunnus");

$query  = "SELECT * from lasku where tunnus='$otunnus' and yhtio='$kukarow[yhtio]' and tila='K' and alatila=''";
$result = mysql_query($query) or pupe_error($query);

if (mysql_num_rows($result) == 1) {
	$laskurow = mysql_fetch_array($result);
}
else {
	echo "<font style='error'>".t("Tilaus katosi!")."</font>";
	exit;
}

list(,,$kohok,,,,$lisok,,$sarjanrook,,,,,$varok) = tsekit($laskurow,0);

if (($vietiinko_koko_suuntalava == 'joo' or $varok == 1) and $sarjanrook == 1) {

	if ($tee == "varastoon") {
		require ("varastoon.inc");

		// takaisin ihan alkuun
		$toiminto 		= "dummy";
		$ytunnus  		= "";
		$toimittajaid 	= "";
	}
	else {

		if ($tee == "eitullia") {
			//merkataan ne rivit joille ei pid‰ laskea tullia
			$query = "	UPDATE tilausrivi
						SET var = '', netto = 'X'
						WHERE uusiotunnus = '$laskurow[tunnus]' and yhtio = '$kukarow[yhtio]' and kpl = 0";
			$varresult = mysql_query($query) or pupe_error($query);

			if (count($eitullia) > 0) {
				foreach ($eitullia as $eitul) {
					$query = "	UPDATE tilausrivi
								SET var = 'T'
								WHERE tunnus = '$eitul' and uusiotunnus = '$laskurow[tunnus]' and yhtio = '$kukarow[yhtio]' and kpl = 0";
					$varresult = mysql_query($query) or pupe_error($query);
				}
			}

			if (count($varastoonko) > 0) {
				foreach ($varastoonko as $varas) {
					$query = "	UPDATE tilausrivi
								SET netto = ''
								WHERE tunnus = '$varas' and uusiotunnus = '$laskurow[tunnus]' and yhtio = '$kukarow[yhtio]' and kpl = 0";
					$varresult = mysql_query($query) or pupe_error($query);
				}
			}

			if ($yhtiorow["varastoonvientipaiva"] == "K" and count($vva) > 0) {
				foreach ($vva as $varas => $vuosi) {

					$kuukausi	= $kka[$varas];
					$paiva		= $ppa[$varas];

					if (!checkdate($kuukausi, $paiva, $vuosi)) {
						echo "<font class='error'>".t("VIRHE: Virheellinen p‰iv‰m‰‰r‰")." $vuosi-$kuukausi-$paiva</font><br>";
					}
					else {
						$query = "	UPDATE tilausrivi
									SET toimitettuaika = '$vuosi-$kuukausi-$paiva 00:00:10',
									toimitettu = '$kukarow[kuka]'
									WHERE tunnus = '$varas' and uusiotunnus = '$laskurow[tunnus]' and yhtio = '$kukarow[yhtio]'";
						$varresult = mysql_query($query) or pupe_error($query);
					}
				}
			}

			$tee = "";
		}

		if ($yhtiorow['suuntalavat'] == 'S' and $tee == '' and trim($suuntalavan_tunnus) != '' and trim($koko_suuntalava) == 'X') {

			if ($komento["Tavaraetiketti"] != '') {

				$suuntalavat = array($suuntalavan_tunnus);

				require('tulosta_tavaraetiketti.inc');
			}

			echo "<br />";
			echo "<form method='post'>";
			echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
			echo "<input type='hidden' name='toiminto' value='kalkyyli'>";
			echo "<input type='hidden' name='tee' value=''>";
			echo "<input type='hidden' name='otunnus' value='$otunnus'>";
			echo "<input type='hidden' name='suuntalavan_tunnus' value='{$suuntalavan_tunnus}' />";

			if (trim($suuntalavan_hyllypaikka) != '') {
				echo "<input type='hidden' name='suuntalavan_hyllypaikka' value='{$suuntalavan_hyllypaikka}' />";
			}
			else {
				echo "<input type='hidden' name='suuntalavan_hyllyalue' value='{$suuntalavan_hyllyalue}' />";
				echo "<input type='hidden' name='suuntalavan_hyllynro' value='{$suuntalavan_hyllynro}' />";
				echo "<input type='hidden' name='suuntalavan_hyllyvali' value='{$suuntalavan_hyllyvali}' />";
				echo "<input type='hidden' name='suuntalavan_hyllytaso' value='{$suuntalavan_hyllytaso}' />";
			}

			echo "<input type='hidden' name='koko_suuntalava' value='X' />";
			echo "<input type='hidden' name='vietiinko_koko_suuntalava' value='{$vietiinko_koko_suuntalava}' />";

			echo "<table><tr>";
			echo "<th>",t("Tulosta tavaraetiketti"),"</th>";

			echo "<td><select name='komento[Tavaraetiketti]'>";
			$query = "	SELECT *
						FROM kirjoittimet
						WHERE yhtio = '{$kukarow['yhtio']}'
						ORDER BY kirjoitin";
			$kires = pupe_query($query);

			while ($kirow = mysql_fetch_assoc($kires)) {
				$sel = $kirow['tunnus'] == $kukarow['kirjoitin'] ? " selected" : "";
				echo "<option value='{$kirow['komento']}'{$sel}>{$kirow['kirjoitin']}</option>";
			}

			echo "</select></td>";
			echo "<td class='back'><input type='submit' value='",t("Tulosta"),"' /></td>";

			echo "</tr></table>";
			echo "</form>";
		}

		require("varastoon.inc");

		echo "<form method='post'>";
		echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
		echo "<input type='hidden' name='toiminto' value='kalkyyli'>";
		echo "<input type='hidden' name='tee' value='varastoon'>";
		echo "<input type='hidden' name='otunnus' value='$otunnus'>";
		echo "<input type='hidden' name='suuntalavan_tunnus' value='{$suuntalavan_tunnus}' />";

		if ($yhtiorow['suuntalavat'] == 'S' and $tee == '' and trim($suuntalavan_tunnus) != '' and trim($koko_suuntalava) == 'X') {

			if (trim($suuntalavan_hyllypaikka) != '') {
				echo "<input type='hidden' name='suuntalavan_hyllypaikka' value='{$suuntalavan_hyllypaikka}' />";
			}
			else {
				echo "<input type='hidden' name='suuntalavan_hyllyalue' value='{$suuntalavan_hyllyalue}' />";
				echo "<input type='hidden' name='suuntalavan_hyllynro' value='{$suuntalavan_hyllynro}' />";
				echo "<input type='hidden' name='suuntalavan_hyllyvali' value='{$suuntalavan_hyllyvali}' />";
				echo "<input type='hidden' name='suuntalavan_hyllytaso' value='{$suuntalavan_hyllytaso}' />";
			}
			echo "<input type='hidden' name='koko_suuntalava' value='X' />";
			echo "<input type='hidden' name='vietiinko_koko_suuntalava' value='{$vietiinko_koko_suuntalava}' />";
		}

		echo "<input type='submit' value='".t("Hyv‰lt‰ n‰ytt‰‰, vie varastoon!")."'>";
		echo "</form>";

		echo "<form method='post'>";
		echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
		echo "<input type='hidden' name='toiminto' value=''>";
		echo "<input type='hidden' name='ytunnus' value='$laskurow[ytunnus]'>";

		if ($nappikeikalle == 'menossa') {
			echo "<input type='hidden' name='nappikeikalla' value='ollaan'>";
			echo "<input type='hidden' name='otunnus' value='$otunnus'>";
		}

		echo "<input type='submit' value='".t("Peruuta")."'>";
		echo "</form>";
	}
}
else {
	echo "<br><br><font class='error'>".t("Saapumisen tiedot eiv‰t t‰sm‰‰! Korjaa asia")."!</font><br>";
}

// poistetaan lock file
unlink("/tmp/$kukarow[yhtio]-keikka.lock");
