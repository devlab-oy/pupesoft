<?php

// tehd‰‰n lock file, ettei voi kerralla ajaa kuin yks per firma
file_put_contents("/tmp/$kukarow[yhtio]-keikka.lock", "$otunnus");

$query  = "select * from lasku where tunnus='$otunnus' and yhtio='$kukarow[yhtio]' and tila='K' and alatila=''";
$result = mysql_query($query) or pupe_error($query);

if (mysql_num_rows($result) == 1) {
	$laskurow = mysql_fetch_array($result);
}
else {
	echo "<font style='error'>".t("Tilaus katosi!")."</font>";
	exit;
}

list(,,$kohok,,,,$lisok,,$sarjanrook,,,,,$varok) = tsekit($laskurow,0);

if ($sarjanrook == 1 and $varok == 1) {
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
				foreach($eitullia as $eitul) {
					$query = "	UPDATE tilausrivi
								SET var = 'T'
								WHERE tunnus = '$eitul' and uusiotunnus = '$laskurow[tunnus]' and yhtio = '$kukarow[yhtio]' and kpl = 0";
					$varresult = mysql_query($query) or pupe_error($query);
				}
			}

			if (count($varastoonko) > 0) {
				foreach($varastoonko as $varas) {
					$query = "	UPDATE tilausrivi
								SET netto = ''
								WHERE tunnus = '$varas' and uusiotunnus = '$laskurow[tunnus]' and yhtio = '$kukarow[yhtio]' and kpl = 0";
					$varresult = mysql_query($query) or pupe_error($query);
				}
			}

			$tee = "";
		}

		require("varastoon.inc");

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
		
		if ($nappikeikalle == 'menossa') {
			echo "<input type='hidden' name='nappikeikalla' value='ollaan'>";
			echo "<input type='hidden' name='otunnus' value='$otunnus'>";
		}
		
		echo "<input type='submit' value='".t("Peruuta")."'>";
		echo "</form>";
	}
}
else {
	echo "<br><br><font class='error'>".t("Keikan tiedot eiv‰t t‰sm‰‰! Korjaa asia")."!</font><br>";
}

// poistetaan lock file
unlink("/tmp/$kukarow[yhtio]-keikka.lock");

?>