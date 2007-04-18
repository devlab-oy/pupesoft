<?php

// tarvitaan $tapa jossa on vienti / tuonti
// ja $row jossa on kenttä kaikkitunnukset ja kaikkituotteet
// kasvatetaan $virhe muuttujaa jos löytyy virheitä
// itse virheet tulee $virhetxt muuttujaan

$virhetxt = "";
$specutxt = "";
$lopetus  = "intrastat.php////tee=tulosta//kk=$kk//vv=$vv//tapa=$tapa//outputti=$outputti//lahetys=nope";

// käydään läpi kaikki tuotteet
$query = "	select *, (SELECT alkuperamaa FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tuote.yhtio and tuotteen_toimittajat.tuoteno=tuote.tuoteno and tuotteen_toimittajat.alkuperamaa!='' LIMIT 1) alkuperamaa
			from tuote where tuote.yhtio='$kukarow[yhtio]' and tuote.tuoteno in ($row[kaikkituotteet])";
$intrtuores = mysql_query($query) or pupe_error($query);

while ($intrtuorow = mysql_fetch_array($intrtuores)) {

	if ($intrtuorow["tullinimike1"] == '' or $intrtuorow["tullinimike1"] == 0) {
	  	$virhetxt .= t("Tuote").": <a href='yllapito.php?toim=tuote&tunnus=$intrtuorow[tunnus]&lopetus=$lopetus'>$intrtuorow[tuoteno]</a>. ".t("Tullinimike tuotteelta puuttuu")."!<br>";
		$virhe++;
	}
	elseif ($intrtuorow["tullinimike1"] != "") {
		$query = "	SELECT cn
					FROM tullinimike
					WHERE cn='$intrtuorow[tullinimike1]' and kieli = '$yhtiorow[kieli]' and cn != ''";
		$tulserul = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($tulserul) == 0) {
		  	$virhetxt .= t("Tuote").": <a href='yllapito.php?toim=tuote&tunnus=$intrtuorow[tunnus]&lopetus=$lopetus'>$intrtuorow[tuoteno]</a> ".t("Tullinimike on virheellinen")." $intrtuorow[tullinimike1]<br>";
			$virhe++;
		}
	}
	else {
	  	$virhetxt .= t("Tuote").": <a href='yllapito.php?toim=tuote&tunnus=$intrtuorow[tunnus]&lopetus=$lopetus'>$intrtuorow[tuoteno]</a> ".t("Tullinimike on virheellinen")." $intrtuorow[tullinimike1]<br>";
		$virhe++;
	}

	// #TODO tällänen häkki, että toimii :) vois korjata joskus
	if ($intrtuorow["alkuperamaa"] == $yhtiorow["maakoodi"] and $intrtuorow["toim_maa"] == $yhtiorow["maakoodi"]) {
		$intrtuorow["alkuperamaa"] = "SE";
	}

	if ($intrtuorow["alkuperamaa"] == '') {
		$virhetxt .= t("Alkuperämaa puuttuu tuotteelta")." <a href='yllapito.php?toim=tuote&tunnus=$intrtuorow[tunnus]&lopetus=$lopetus'>$intrtuorow[tuoteno]</a>!<br>";
		$virhe++;
	}
	elseif ($intrtuorow["alkuperamaa"] == $yhtiorow["maakoodi"] and $tapa != "vienti") {
		$virhetxt .= t("Tuote").": <a href='yllapito.php?toim=tuote&tunnus=$intrtuorow[tunnus]&lopetus=$lopetus'>$intrtuorow[tuoteno]</a>. ".t("Alkuperämaa ei voi olla sama kuin yhtiön kotimaa")."!<br>";
		$virhe++;
	}
	else {
		$query = "	SELECT distinct koodi
					FROM maat
					WHERE koodi='$intrtuorow[alkuperamaa]'";
		$maaresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($maaresult) == 0) {
			$virhetxt .= t("Tuote").": <a href='yllapito.php?toim=tuote&tunnus=$intrtuorow[tunnus]&lopetus=$lopetus'>$intrtuorow[tuoteno]</a>. ".t("Alkuperämaa on virheellinen")."!<br>";
			$virhe++;
		}
	}
}

// käydään läpi kaikki laskut
$query = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus in ($row[kaikkitunnukset])";
$intrlasres = mysql_query($query) or pupe_error($query);

while ($intrlasrow = mysql_fetch_array($intrlasres)) {

	$specutxt = "";

	// erikoiskeissi jos kyseessä on keikka joka on vientiä
	if ($tapa == "vienti" and $row["tapa"] == "Keikka") {
		$specutxt = "Tämä on keikka joka on viety kotimaasta ulkomaanvarastoon.<br>";
		$tapa = "vientituonti";
	}

	// erikoiskeissi jos kyseessä on lasku joka on tuontia
	if ($tapa == "tuonti" and $row["tapa"] == "Lasku") {
		$specutxt  = "Tämä on myyntilasku joka on myyty ulkomaanvarastosta kotimaahan.<br>";
		$tapa = "tuontivienti";
	}

	// echoillaan tällästä infoa
	if ($tapa == "vienti" and $row["tapa"] == "Siirtolista") {
		$specutxt = "Tämä on siirtolista joka on viety kotimaasta ulkomaanvarastoon.<br>";
	}

	// echoillaan tällästä infoa
	if ($tapa == "tuonti" and $row["tapa"] == "Siirtolista") {
		$specutxt  = "Tämä on siirtolista joka on tuotu ulkomaanvarastosta kotimaahan.<br>";
	}

	// kauniiiimpaa
	if ($row["tapa"] == "Siirtolista") {
		$intrlasrow["laskunro"] = $intrlasrow["tunnus"];
	}

	// tuontispecific tarkistuksia
	if ($tapa == 'tuonti' or $tapa == 'tuontivienti') {

		if ($intrlasrow["maa_lahetys"] == '') {
			$virhetxt .= t($row["tapa"]).": <a href='tilauskasittely/muokkaa_vienti_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$intrlasrow[tunnus]&lopetus=$lopetus'>$intrlasrow[laskunro]</a>. ".t("Lähetysmaa puuttuu")."!<br>";
			$virhe++;
		}
		elseif ($intrlasrow["maa_lahetys"] == $yhtiorow["maakoodi"]) {
			$virhetxt .= t($row["tapa"]).": <a href='tilauskasittely/muokkaa_vienti_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$intrlasrow[tunnus]&lopetus=$lopetus'>$intrlasrow[laskunro]</a>. ".t("Lähetysmaa ei voi olla sama kuin yhtiön kotimaa")."!<br>";
			$virhe++;
		}
		else {
			$query = "	SELECT distinct koodi, eu
						FROM maat
						WHERE koodi='$intrlasrow[maa_lahetys]'";
			$maaresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($maaresult) == 0) {
				$virhetxt .= t($row["tapa"]).": <a href='tilauskasittely/muokkaa_vienti_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$intrlasrow[tunnus]&lopetus=$lopetus'>$intrlasrow[laskunro]</a>. ".t("Lähetysmaa on virheellinen")."!<br>";
				$virhe++;
			}
			else {
				$eurow = mysql_fetch_array($maaresult);
				if ($eurow["eu"] == "") {
					$virhetxt .= t($row["tapa"]).": <a href='tilauskasittely/muokkaa_vienti_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$intrlasrow[tunnus]&lopetus=$lopetus'>$intrlasrow[laskunro]</a>. ".t("Lähetysmaa on virheellinen").": NON-EU!<br>";
					$virhe++;
				}
			}
		}

	}

	// vientispecifig tarkistuksia
	if ($tapa == 'vienti' or $tapa == 'vientituonti') {

		if ($intrlasrow["maa_maara"] == '') {
			$virhetxt .= t($row["tapa"]).": <a href='tilauskasittely/muokkaa_vienti_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$intrlasrow[tunnus]&lopetus=$lopetus'>$intrlasrow[laskunro]</a>. ".t("Määrämaa puuttuu")."!<br>";
			$virhe++;
		}
		elseif ($intrlasrow["maa_maara"] == $yhtiorow["maakoodi"]) {
			$virhetxt .= t($row["tapa"]).": <a href='tilauskasittely/muokkaa_vienti_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$intrlasrow[tunnus]&lopetus=$lopetus'>$intrlasrow[laskunro]</a>. ".t("Määrämaa ei voi olla sama kuin yhtiön kotimaa")."!<br>";
			$virhe++;
		}
		else {
			$query = "	SELECT distinct koodi, eu
						FROM maat
						WHERE koodi='$intrlasrow[maa_maara]'";
			$maaresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($maaresult) == 0) {
				$virhetxt .= t($row["tapa"]).": <a href='tilauskasittely/muokkaa_vienti_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$intrlasrow[tunnus]&lopetus=$lopetus'>$intrlasrow[laskunro]</a>. ".t("Määrämaa on virheellinen")."!<br>";
				$virhe++;
			}
			else {
				$eurow = mysql_fetch_array($maaresult);
				if ($eurow["eu"] == "") {
					$virhetxt .= t($row["tapa"]).": <a href='tilauskasittely/muokkaa_vienti_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$intrlasrow[tunnus]&lopetus=$lopetus'>$intrlasrow[laskunro]</a>. ".t("Määrämaa on virheellinen").": NON-EU!<br>";
					$virhe++;
				}
			}
		}

	}

	if ((int) $intrlasrow["kauppatapahtuman_luonne"] <= 0) {
	   	$virhetxt .= t($row["tapa"]).": <a href='tilauskasittely/muokkaa_vienti_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$intrlasrow[tunnus]&lopetus=$lopetus'>$intrlasrow[laskunro]</a>. ".t("Kauppatapahtuman luonne puuttuu")."!<br>";
		$virhe++;
	}

	if ((int) $intrlasrow["kuljetusmuoto"] == 0) {
	    $virhetxt .= t($row["tapa"]).": <a href='tilauskasittely/muokkaa_vienti_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$intrlasrow[tunnus]&lopetus=$lopetus'>$intrlasrow[laskunro]</a>. ".t("Kuljetusmuoto puuttuu")."!<br>";
		$virhe++;
	}

	if ($tapa == "tuontivienti") {
		$tapa = "tuonti";
	}

	if ($tapa == "vientituonti") {
		$tapa = "vienti";
	}

}

if ($virhetxt != "") {
	$virhetxt = $specutxt . $virhetxt;
}

?>