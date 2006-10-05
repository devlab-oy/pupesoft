<?php

// estetään sivun lataus suoraan
if (!empty($HTTP_GET_VARS["oikeus"]) ||
    !empty($HTTP_POST_VARS["oikeus"]) ||
    !empty($HTTP_COOKIE_VARS["oikeus"]) ||
    !isset($oikeus)) {

  echo "<p>".t("Kielletty toiminto")."!</p>";
  exit;
}


echo "<font class='head'>".t("Manuaalinen suoritusten kohdistaminen")."</font><hr>";

$query = "SELECT a.nimi nimi, a.ytunnus ytunnus, s.asiakas_tunnus tunnus, COUNT(s.asiakas_tunnus) maara, sum(if(s.viite>0, 1,0)) viitteita FROM suoritus s, asiakas a where s.asiakas_tunnus<>0 AND s.asiakas_tunnus=a.tunnus AND s.yhtio ='$kukarow[yhtio]' AND a.yhtio ='$kukarow[yhtio]' AND kohdpvm='0000-00-00' AND ltunnus!=0 GROUP BY s.asiakas_tunnus ORDER BY a.nimi";

//echo "$query<br>";

$result = mysql_query($query) or pupe_error($query);

echo "<table><tr><th>Asiakas</th><th>".t("Kohdistamattomia suorituksia")."</th><th>".t("Avoimia laskuja")."</th><th></th><th></th></tr>";

while ($asiakas=mysql_fetch_object ($result)) {

	// Onko asiakkaalla avoimia laskuja???
	$query = "SELECT COUNT(*) maara FROM lasku
				WHERE yhtio ='$kukarow[yhtio]' and mapvm='0000-00-00' and tila = 'U' and ytunnus = '" . $asiakas->ytunnus . "'";
	
	//echo "$query<br>";
	
	$lresult = mysql_query($query) or pupe_error($query);
	$lasku=mysql_fetch_object ($lresult);
	
	echo "<tr><td>";
	echo $asiakas->nimi;
	echo "</td><td>";
	echo $asiakas->maara;
	echo "/";
	echo $asiakas->viitteita;	
	echo "</td><td>";
	echo $lasku->maara;
	echo "</td><td class='back'><form action='$PHP_SELF' method='POST'>";
	echo "<input type='hidden' name='tila' value='suorituksenvalinta'>";
	echo "<input type='hidden' name='asiakas_tunnus' value='$asiakas->tunnus'>";
	echo "<input type='submit' value='".t("Valitse")."'></form></td>";
	echo "<td><form action='$PHP_SELF' method='POST'>";
	echo "<input type='hidden' name='tila' value='suorituksenvalinta'>";
	echo "<input type='hidden' name='asiakas_tunnus' value='$asiakas->tunnus'>";
	echo "<input type='hidden' name='asiakas_nimi' value='$asiakas->nimi'>";
	echo "<input type='submit' value='".t("Valitse nimellä")."'>";
	echo "</form></td></tr>";
}

echo "</table>";
echo "<script LANGUAGE='JavaScript'>document.forms[0][0].focus()</script>";

?>
