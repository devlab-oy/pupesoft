<?php
// Tässä on esimerkki, miten voi korjata tietokannan eheysongelmia

	require "inc/parametrit.inc";
	$link = mysql_pconnect ($dbhost, $dbuser, $dbpass)
			or die ("Ongelma tietokantapalvelimessa $dbhost");
	mysql_select_db ("ostores")
			or die ("Tietokanta ei löydy palvelimelta $dbhost");


	$query = "SELECT tiliointi.tunnus, lasku.tapvm, tilino, tiliointi.summa
			  FROM tiliointi, lasku
			  WHERE ltunnus = lasku.tunnus";
	$result = mysql_query($query) or pupe_error($query);

	echo "<pre>";

	while ($tiliointirow=mysql_fetch_array ($result)) {

		echo "$tiliointirow[0] $tiliointirow[1] $tiliointirow[2] $tiliointirow[3]<br>";

		$query = "UPDATE  tiliointi set
                                        tapvm = '$tiliointirow[1]'
                          WHERE tunnus = '$tiliointirow[0]'";
		$uresult = mysql_query($query) or pupe_error($query);
	}

	echo "</pre>";
?>
