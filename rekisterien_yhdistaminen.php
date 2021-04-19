<?php
require ("inc/parametrit.inc");

echo "<font class='head'>".t("Rekisterien yhdist‰minen")."</font><br><br>";
echo "<font class='error'>".t("VAROITUS: siirt‰minen poistaa aina vanhan merkinn‰n, varmista siis ett‰ olet siirt‰m‰ss‰ oikeaa merkint‰‰!")."</font><hr>";

//funkkari joka yhdist‰‰ tietokannassa kaksi merkint‰‰.. eli vaihtaa vanhaan tunnukseen viittaavat asiat uudelle tunnukselle ja poistaa tuon vanhan tunnuksen rivin.
//palauttaa TRUE jos kaikki ok, FALSE jos jotain meni vikaan.
if (!function_exists("yhdista")) {
	function yhdista($vanhatunnus,$uusitunnus,$tyyppi){
		require ("inc/parametrit.inc");

		//mit‰ sarakkeita haetaan kannasta
		if ($tyyppi == 'kohde') {
			$haettavaSarake	= "asiakkaan_kohde";
		}
		elseif ($tyyppi == 'positio') {
			$haettavaSarake = "asiakkaan_positio";
		}

		$taulut	= "";

		//ei voi valita samaa kohdetta ja kohteilla pit‰‰ olla tunnukset (k‰h, miksei niill‰ olis? no kuiteskin...)
		if ($uusitunnus != $vanhatunnus and $uusitunnus != "" and $vanhatunnus != "") {
			echo "<font class='message'>".t("Aloitellaan p‰ivitys, t‰m‰ voi kest‰‰ hetken").".<br></font>";

			$taulutJoissaSarake = array();

			//$dbkanta --> tulee salasanat.php:st‰
			//k‰yd‰‰n kaikki taulut l‰pi ja etsit‰‰n kaikkien kentist‰ ne kent‰t, joissa lukee $kentta, niiden taulujen nimet $taulut muuttujaan lukitsemista varten
			$query  = "SHOW TABLES FROM $dbkanta";
			$tabresult = mysql_query($query) or pupe_error($query);

			while ($tables = mysql_fetch_array($tabresult)) {
				$query  = "describe $tables[0]";
				$fieldresult = mysql_query($query) or pupe_error($query);

				while ($fields = mysql_fetch_array($fieldresult)) {						
					if (strpos($fields[0], $haettavaSarake) !== false and !in_array($tables[0], $taulutJoissaSarake)) {
						$taulut .= $tables[0].' WRITE,';
						$taulutJoissaSarake[] = $tables[0];
					}
				}
			}
			
			//vanhan poistoa varten m‰‰ritet‰‰n myˆs t‰m‰ omataulu k‰yt‰v‰ksi l‰pi 
			//(tuon taulun on siis pakko olla samanniminen kuin tuo haettava sarake, mutta pupessa n‰in onkin)
			$taulutJoissaSarake[] = $haettavaSarake;

			//lis‰t‰‰n parit taulut lukittaviksi
			$taulut .= "$haettavaSarake WRITE, sanakirja WRITE, avainsana WRITE";

			//lˆytykˆ sielt‰ nyt yht‰‰n taulua jota vois k‰pristell‰	
			$montako = count($taulutJoissaSarake);

			if ($montako > 0) {
				echo "<font class='message'>".t("Lˆydettiin taulut joita pit‰‰ muuttaa: "). "$montako kappaletta.</font><br>";
			}
			else {
				die ("<font class='error'><br>".t("Ei lˆydetty muutettavia paikkoja, ei uskalleta tehd‰ mit‰‰n")."!</font>");
			}

			echo "<font class='message'>".t("Aloitetaan muutos")."...</font><br>";

			//lukitaan tietokantataulut kirjoitukselta
			$lokki = "LOCK TABLES $taulut";
			$res   = mysql_query($lokki) or pupe_error($lokki);

			//k‰yd‰‰n lˆytyneet taulut l‰pi ja p‰ivitet‰‰n vanhat tiedot uusiksi ja poistetaan tuo vanha tunnus.
			foreach ($taulutJoissaSarake as $taulu) {
				if ($taulu == "$haettavaSarake") {
					$query = "	DELETE FROM $taulu
								WHERE yhtio = '$kukarow[yhtio]'	and tunnus = '$vanhatunnus'";
					$kohderes = mysql_query($query) or pupe_error($query);
					$poistotapahtui = "kylla";
				}
				else {
					$query = "	UPDATE $taulu
								SET $haettavaSarake = '$uusitunnus'
								WHERE yhtio = '$kukarow[yhtio]'	and $haettavaSarake = '$vanhatunnus'";
					$result = mysql_query($query) or pupe_error($query);
				}
			}

			//puretaan taulujen lukko	
			$unlokki = "UNLOCK TABLES";
			$res     = mysql_query($unlokki) or pupe_error($unlokki);

			//poistettiinko se vanha?
			if ($poistotapahtui != "kylla") {
				echo "<font class='error'>".t("VIRHE: Vanhaa rivi‰ ei poistettu jostain syyst‰!")."<br></font>";
				return FALSE; 
			}
			else {
				echo "<br><font class='message'>".t("Valmis!")."<br>Rivi $vanhatunnus on nyt poistettu ja sen tiedot siirretty riville $uusitunnus<br><br><br></font>";
			}

			//pistet‰‰n selectlistat taas n‰kyviin
			$tee = "";
		}
		else {
			echo "<font class='error'>".t("Et voi yhdist‰‰ samoja!<br>")."</font>";
			return FALSE;
		}
		return TRUE;
	}
	
}

//tietojen yhdistamiseen...
if ($tee == "yhdista") {
	yhdista($vanhatunnus,$uusitunnus,$tyyppi);
	$tee = "";	
}	
	
if ($tee == "") {
	//kohteiden yhdist‰minen
	echo "<font class='message'>".t("Kohteiden yhdist‰minen")."</font>";
	$query = "	SELECT kohde, tunnus 
				FROM asiakkaan_kohde 
				WHERE yhtio='$kukarow[yhtio]' and kohde!=''
				ORDER BY tunnus";
	$result = mysql_query($query) or pupe_error($query);

	echo "<form action='$PHP_SELF?ohje=off' method='post' name='yhdistakohteet'>";
	echo "<table><tr><th>".t("Valitse siirrett‰v‰ kohde")."</th><th>".t("Valitse kohde johon siirret‰‰n")."</th></tr>";
	echo "<tr><td><select name='vanhatunnus'>";
	while ($kohde = mysql_fetch_array($result)) {
		echo "<option value='$kohde[tunnus]'>$kohde[tunnus] - $kohde[kohde]</option>";
	}
	echo "</select></td><td><select name='uusitunnus'>";
	$result = mysql_query($query) or pupe_error($query);
	while ($kohde = mysql_fetch_array($result)) {
		echo "<option value='$kohde[tunnus]'>$kohde[tunnus] - $kohde[kohde]</option>";
	}		
	echo "</select></td>
			<td class='back'><input type='hidden' name='tee' value='yhdista'>
			<input type='hidden' name='tyyppi' value='kohde'>
			<input type='submit' value='".t("Yhdist‰ kohteet")."'</td></tr>
			</table>
			</form><br><br><br>";
	
	//positioiden yhdistaminen		
	echo "<font class='message'>".t("Positioiden yhdist‰minen")."</font>";
	$query = "	SELECT positio, tunnus
				FROM asiakkaan_positio
				WHERE yhtio='$kukarow[yhtio]' and positio != ''
				ORDER BY tunnus";
	$result = mysql_query($query) or pupe_error($query);

	echo "<form action='$PHP_SELF?ohje=off' method='post' name='yhdistapositiot'>";
	echo "<table><tr><th>".t("Valitse siirrett‰v‰ positio")."</th><th>".t("Valitse positio johon siirret‰‰n")."</th></tr>";
	echo "<tr><td><select name='vanhatunnus'>";
	while ($kohde = mysql_fetch_array($result)) {
		echo "<option value='$kohde[tunnus]'>$kohde[tunnus] - $kohde[positio]</option>";
	}
	echo "</select></td><td><select name='uusitunnus'>";
	$result = mysql_query($query) or pupe_error($query);
	while ($kohde = mysql_fetch_array($result)) {
		echo "<option value='$kohde[tunnus]'>$kohde[tunnus] - $kohde[positio]</option>";
	}		
	echo "</select></td>
			<td class='back'><input type='hidden' name='tee' value='yhdista'>
			<input type='hidden' name='tyyppi' value='positio'>
			<input type='submit' value='".t("Yhdist‰ positiot")."'</td></tr>
			</table>
			</form><br><br><br>";
}

require ("inc/footer.inc");
?>