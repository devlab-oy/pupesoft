<?php

// tarvitaan $tapa jossa on vienti / tuonti
// ja $row jossa on queryn tulos lasku , tilausrivi, tuote ja tullinimike tauluista
// kasvatetaan $virhe muuttujaa jos löytyy virheitä
// itse virheet tulee $virhetxt muuttujaan

$virhetxt = "";
$vienti_special = "";

// tarvitaan queryä, että saadaan linkit tehtyä
$query = "select tunnus from tuote where tuoteno='$row[tuoteno]' and yhtio='$kukarow[yhtio]'";
$maaresult = mysql_query($query) or pupe_error($query);
$intrtuote = mysql_fetch_array($maaresult);

///* Laskun tietoja tarkistetaan*///
if ($tapa == 'vienti') {

	$query = "select tunnus from lasku where yhtio='$kukarow[yhtio]' and laskunro='$row[laskunro]' and tila='U' and alatila='X' and vienti='E'";
	$maaresult = mysql_query($query) or pupe_error($query);
	$intrkeikk = mysql_fetch_array($maaresult);

	if (mysql_num_rows($maaresult) == 0) {
		$query = "select tunnus from lasku where yhtio='$kukarow[yhtio]' and laskunro='$row[laskunro]' and lasku.kohdistettu='X' and lasku.tila = 'K' and kauppatapahtuman_luonne = '-1'";
		$maaresult = mysql_query($query) or pupe_error($query);
		$intrkeikk = mysql_fetch_array($maaresult);
		$virhetxt .= "Tämä on keikka joka on viety ulkomaanvarastoon suomesta.<br>";
		$vienti_special = "JESS";
		$tapa = "tuonti";
	}

	if (mysql_num_rows($maaresult) != 0) {

		if ($row["maa_maara"] == '') {
			$virhetxt .= t("Laskunumero").": <a href='tilauskasittely/muokkaa_vienti_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$intrkeikk[tunnus]'>$row[laskunro]</a>. ".t("Määrämaa puuttuu")."!<br>";
			$virhe++;
		}
		elseif ($row["maa_maara"] == $yhtiorow["maakoodi"]) {
			$virhetxt .= t("Laskunumero").": <a href='tilauskasittely/muokkaa_vienti_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$intrkeikk[tunnus]'>$row[laskunro]</a>. ".t("Määrämaa ei voi olla sama kuin yhtiön kotimaa")."!<br>";
			$virhe++;
		}
		else {
			$query = "	SELECT distinct koodi, eu
						FROM maat
						WHERE koodi='$row[maa_maara]'";
			$maaresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($maaresult) == 0) {
				$virhetxt .= t("Laskunumero").": <a href='tilauskasittely/muokkaa_vienti_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$intrkeikk[tunnus]'>$row[laskunro]</a>. ".t("Määrämaa on virheellinen")."!<br>";
				$virhe++;
			}
			else {
				$eurow = mysql_fetch_array($maaresult);
				if ($eurow["eu"] == "") {
					$virhetxt .= t("Laskunumero").": <a href='tilauskasittely/muokkaa_vienti_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$intrkeikk[tunnus]'>$row[laskunro]</a>. ".t("Määrämaa on virheellinen").": NON-EU!<br>";
					$virhe++;
				}
			}
		}

		if ((int) $row["kauppatapahtuman_luonne"] <= 0) {
		   	$virhetxt .= t("Laskunumero").": <a href='tilauskasittely/muokkaa_vienti_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$intrkeikk[tunnus]'>$row[laskunro]</a>. ".t("Kauppatapahtuman luonne puuttuu")."!<br>";
			$virhe++;
		}

		if ((int) $row["kuljetusmuoto"] == 0) {
		    $virhetxt .= t("Laskunumero").": <a href='tilauskasittely/muokkaa_vienti_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$intrkeikk[tunnus]'>$row[laskunro]</a>. ".t("Kuljetusmuoto puuttuu")."!<br>";
			$virhe++;
		}

	}
	else {
		$virhetxt = t("Lasku/keikka").": $row[laskunro] ".t("ei löydy")."!<br>";
		$virhe++;
	}

}

if ($vienti_special != "") $tapa = "vienti";
$vienti_special = "";

if ($tapa == 'tuonti') {

	$query = "select tunnus from lasku where yhtio='$kukarow[yhtio]' and laskunro='$row[laskunro]' and tila='K' and vanhatunnus=0";
	$maaresult = mysql_query($query) or pupe_error($query);
	$intrkeikk = mysql_fetch_array($maaresult);

	if (mysql_num_rows($maaresult) == 0) {
		$query = "select * from lasku where yhtio='$kukarow[yhtio]' and laskunro='$row[laskunro]' and tila='U' and alatila='X' and kauppatapahtuman_luonne = '-2'";
		$maaresult = mysql_query($query) or pupe_error($query);
		$intrkeikk = mysql_fetch_array($maaresult);
		$virhetxt .= "Tämä on lasku joka on myyty ulkomaanvarastosta suomeen.<br>";
		$vienti_special = "JESS";
		$tapa = "vienti";
	}

	if (mysql_num_rows($maaresult) != 0) {

		//häkki että toimii
		if ($row["alkuperamaa"] == $yhtiorow["maakoodi"] and $intrkeikk["toim_maa"] == $yhtiorow["maakoodi"]) {
			$row["alkuperamaa"] = "SE";
		}

		if ($row["alkuperamaa"] == '') {
			$virhetxt .= t("Alkuperämaa puuttuu tuotteelta")." <a href='yllapito.php?toim=tuote&tunnus=$intrtuote[tunnus]&lopetus=$PHP_SELF'>$row[tuoteno]</a>!<br>";
			$virhe++;
		}
		elseif ($row["alkuperamaa"] == $yhtiorow["maakoodi"]) {
			$virhetxt .= t("Tuote").": <a href='yllapito.php?toim=tuote&tunnus=$intrtuote[tunnus]&lopetus=$PHP_SELF'>$row[tuoteno]</a>. ".t("Alkuperämaa ei voi olla sama kuin yhtiön kotimaa")."!<br>";
			$virhe++;
		}
		else {
			$query = "	SELECT distinct koodi
						FROM maat
						WHERE koodi='$row[alkuperamaa]'";
			$maaresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($maaresult) == 0) {
				$virhetxt .= t("Tuote").": <a href='yllapito.php?toim=tuote&tunnus=$intrtuote[tunnus]&lopetus=$PHP_SELF'>$row[tuoteno]</a>. ".t("Alkuperämaa on virheellinen")."!<br>";
				$virhe++;
			}
		}

		if ($row["maa_lahetys"] == '') {
			$virhetxt .= t("Keikka").": <a href='tilauskasittely/muokkaa_vienti_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$intrkeikk[tunnus]'>$row[laskunro]</a>. ".t("Lähetysmaa puuttuu")."!<br>";
			$virhe++;
		}
		elseif ($row["maa_lahetys"] == $yhtiorow["maakoodi"]) {
			$virhetxt .= t("Keikka").": <a href='tilauskasittely/muokkaa_vienti_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$intrkeikk[tunnus]'>$row[laskunro]</a>. ".t("Lähetysmaa ei voi olla sama kuin yhtiön kotimaa")."!<br>";
			$virhe++;
		}
		else {
			$query = "	SELECT distinct koodi, eu
						FROM maat
						WHERE koodi='$row[maa_lahetys]'";
			$maaresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($maaresult) == 0) {
				$virhetxt .= t("Keikka").": <a href='tilauskasittely/muokkaa_vienti_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$intrkeikk[tunnus]'>$row[laskunro]</a>. ".t("Lähetysmaa on virheellinen")."!<br>";
				$virhe++;
			}
			else {
				$eurow = mysql_fetch_array($maaresult);
				if ($eurow["eu"] == "") {
					$virhetxt .= t("Keikka").": <a href='tilauskasittely/muokkaa_vienti_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$intrkeikk[tunnus]'>$row[laskunro]</a>. ".t("Lähetysmaa on virheellinen").": NON-EU!<br>";
					$virhe++;
				}
			}
		}

		if ((int) $row["kauppatapahtuman_luonne"] <= 0) {
		   	$virhetxt .= t("Keikka").": <a href='tilauskasittely/muokkaa_vienti_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$intrkeikk[tunnus]'>$row[laskunro]</a>. ".t("Kauppatapahtuman luonne puuttuu")."!<br>";
			$virhe++;
		}

		if ((int) $row["kuljetusmuoto"] == 0) {
		    $virhetxt .= t("Keikka").": <a href='tilauskasittely/muokkaa_vienti_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$intrkeikk[tunnus]'>$row[laskunro]</a>. ".t("Kuljetusmuoto puuttuu")."!<br>";
			$virhe++;
		}

	}
	else {
		$virhetxt = t("Lasku/keikka")." $row[laskunro] ".t("ei löydy")."!<br>";
		$virhe++;
	}
}

if ($vienti_special != "") $tapa = "tuonti";

if ((float) $row["paino"] == 0) {
  	//$virhetxt .= "Laskunumero: $row[laskunro], Paino puuttuu $row[tullinimike1]!<br>";
	//$virhe++;
}

if ($row["tullinimike1"] == '' or $row["tullinimike1"] == 0) {
  	$virhetxt .= t("Tuote").": <a href='yllapito.php?toim=tuote&tunnus=$intrtuote[tunnus]&lopetus=$PHP_SELF'>$row[tuoteno]</a>. ".t("Tullinimike tuotteelta puuttuu")."!<br>";
	$virhe++;
}
elseif ($row["tullinimike1"] != "") {
	$query = "	SELECT cn
				FROM tullinimike
				WHERE cn='$row[tullinimike1]' and kieli = '$yhtiorow[kieli]' and cn != ''";
	$tulserul = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($tulserul) == 0) {
	  	$virhetxt .= t("Tuote").": <a href='yllapito.php?toim=tuote&tunnus=$intrtuote[tunnus]&lopetus=$PHP_SELF'>$row[tuoteno]</a> ".t("Tullinimike on virheellinen")." $row[tullinimike1]<br>";
		$virhe++;
	}
}
else {
  	$virhetxt .= t("Tuote").": <a href='yllapito.php?toim=tuote&tunnus=$intrtuote[tunnus]&lopetus=$PHP_SELF'>$row[tuoteno]</a> ".t("Tullinimike on virheellinen")." $row[tullinimike1]<br>";
	$virhe++;
}

if ($row["rivihinta"] == 0.01) {
  	//$virhetxt .= "WARNING: Laskunumero: $row[laskunro], Nimikeen $row[tullinimike1] rivihinta oli nolla!<br>";
	//$virhe++;
}

///* Laskun tietojen tarkistus loppuu*///

?>