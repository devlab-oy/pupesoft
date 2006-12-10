<?php
//Tositelajit ovat 11, 30, 41
//Tositenrot ovat yyxxxxxx, jossa yy on tositelaji ja xxxxxx on tositenro

require "inc/parametrit.inc";
echo "<font class='head'>".t('TIKON-siirron peruutus')."</font><hr>";

if (isset($kausi)) {
	if(!isset($ok)) {
		echo "<font class='message'>".t("Peruutettavat tositteet")."</font><br><br>";
		echo "<table>";
		$query  = "SELECT max(tosite) pienin, min(tosite) suurin
				FROM tiliointi
				WHERE tiliointi.yhtio='$kukarow[yhtio]' and korjattu=''
				and left(tapvm,7)='$kausi' and tosite >= '11000000' and tosite <= '11999999'";

		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 1) {
			$row=mysql_fetch_array ($result);
			echo "<tr><td>$row[pienin]</td><td>$row[suurin]</td></tr>";
		}

		$query  = "SELECT max(tosite) pienin, min(tosite) suurin
				FROM tiliointi
				WHERE tiliointi.yhtio='$kukarow[yhtio]' and korjattu=''
				and left(tapvm,7)='$kausi' and tosite >= '30000000' and tosite <= '30999999'";

		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 1) {
			$row=mysql_fetch_array ($result);
			echo "<tr><td>$row[pienin]</td><td>$row[suurin]</td></tr>";
		}

		$query  = "SELECT max(tosite) pienin, min(tosite) suurin
				FROM tiliointi
				WHERE tiliointi.yhtio='$kukarow[yhtio]' and korjattu=''
				and left(tapvm,7)='$kausi' and tosite >= '41000000' and tosite <= '41999999'";

		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 1) {
			$row=mysql_fetch_array ($result);
			echo "<tr><td>$row[pienin]</td><td>$row[suurin]</td></tr>";
		}

		echo "</table><br><br><form name = 'valinta' action = '$PHP_SELF' method='post'>
		<input type = 'hidden' name='ok' value = '1'>
		<input type = 'hidden' name='kausi' value = '$kausi'>
		<table>
		<tr><td><input type = 'submit' value = '".t("Peruuta kausi $kausi")."'></td></tr>
		</table></form>";
	}
	else {
		$query  = "UPDATE tiliointi SET tosite = ''
				WHERE tiliointi.yhtio='$kukarow[yhtio]' and korjattu='' and tosite!=''
				and left(tapvm,7) = '$kausi'";
		$result = mysql_query($query) or pupe_error($query);
		echo "<font class='message'>".t("Peruutin kauden ".$kausi. "TIKON-siirron")."</font><br><br>";
	}		
}

if (!isset($kausi)) {
	//Etsitään viimeksi viety kausi
	$query  = "SELECT tunnus,left(tapvm,7) kausi
				FROM tiliointi
				WHERE tiliointi.yhtio='$kukarow[yhtio]' and korjattu=''
				and tosite >= '11000000' and tosite <= '11999999'
				ORDER BY tosite desc LIMIT 1";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 1) {
		$row11=mysql_fetch_array ($result);
	}


	$query  = "SELECT tunnus,left(tapvm,7) kausi
				FROM tiliointi
				WHERE tiliointi.yhtio='$kukarow[yhtio]' and korjattu=''
				and tosite >= '30000000' and tosite <= '30999999'
				ORDER BY tosite desc LIMIT 1";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 1) {
		$row30=mysql_fetch_array ($result);
	}


	$query  = "SELECT tunnus,left(tapvm,7) kausi
				FROM tiliointi
				WHERE tiliointi.yhtio='$kukarow[yhtio]' and korjattu=''
				and tosite >= '41000000' and tosite <= '41999999'
				ORDER BY tosite desc LIMIT 1";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 1) {
		$row41=mysql_fetch_array ($result);
	}

	$suurin = '0000-00';

	if (is_array($row11)) $suurin = $row11['kausi'];
	if (is_array($row30) and $row30['kausi'] > $suurin) $suurin = $row30['kausi'];
	if (is_array($row41) and $row41['kausi'] > $suurin) $suurin = $row41['kausi'];

	echo "<font class='message'>Ehdotan peruutettavaksi kautta $suurin. Se on siirretty viimeksi</font><br><br>";
	echo "<form name = 'valinta' action = '$PHP_SELF' method='post'>
		<table>
		<tr><td>".t("Anna peruutettava kausi")."</td><td><input type = 'text' name = 'kausi' size=8 value ='$suurin'></td></tr>
		<tr><td></td><td><input type = 'submit' value = '".t("Peruuta valittu kausi")."'></td></tr>
		</table></form>";
	$formi = 'valinta';
	$kentta = 'kausi';
}
require "inc/footer.inc";
?>
