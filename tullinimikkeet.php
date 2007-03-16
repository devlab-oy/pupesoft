<?php
	
require("inc/parametrit.inc");

echo "<font class='head'>".t("Tullinimikkeet")."</font><hr>";

if ($tee == "muuta") {

	$ok = 0;
	$uusitullinimike1 = trim($uusitullinimike1);
	$uusitullinimike2 = trim($uusitullinimike2);
	
	// katotaan, että tullinimike1 löytyy
	$query = "SELECT cn FROM tullinimike WHERE cn = '$uusitullinimike1' and kieli = '$yhtiorow[kieli]'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) != 1 or $uusitullinimike1 == "") {
		$ok = 1;
		echo "<font class='error'>Tullinimike 1 on virheellinen!</font><br>";
	}

	// kaks pitkä tai ei mitään
	if (strlen($uusitullinimike2) != 2) {
		$ok = 1;
		echo "<font class='error'>Tullinimike 2 tulee olla 2 merkkiä pitkä!</font><br>";
	}

	// tää on aika fiinisliippausta 
	if ($ok == 1) echo "<br>";
	
	// jos kaikki meni ok, nii päivitetään
	if ($ok == 0) {

		if ($tullinimike2 != "") $lisa = " and tullinimike2='$tullinimike2'";
		else $lisa = "";
		
		$query = "update tuote set tullinimike1='$uusitullinimike1', tullinimike2='$uusitullinimike2' where yhtio='$kukarow[yhtio]' and tullinimike1='$tullinimike1' $lisa";
		$result = mysql_query($query) or pupe_error($query);
		
		echo sprintf("<font class='message'>Päivitettiin %s tuotetta.</font><br><br>", mysql_affected_rows());

		$tullinimike1 = $uusitullinimike1;
		$tullinimike2 = $uusitullinimike2;
		$uusitullinimike1 = "";
		$uusitullinimike2 = "";
	}
}

echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
echo "<table>";
echo "<tr>";
echo "<th>Syötä tullinimike 1:</th>";
echo "<td><input type='text' name='tullinimike1' value='$tullinimike1'></td>";
echo "</tr><tr>";
echo "<th>Syötä tullinimike 2:</th>";
echo "<td><input type='text' name='tullinimike2' value='$tullinimike2'> (ei pakollinen) </td>";
echo "<td class='back'><input type='submit' value='".t("Hae")."'></td>";
echo "</tr></table>";
echo "</form>";

if ($tullinimike1 != "") {
	
	if ($tullinimike2 != "") $lisa = " and tullinimike2='$tullinimike2'";
	else $lisa = "";
	
	$query = "select * from tuote use index (yhtio_tullinimike) where yhtio='$kukarow[yhtio]' and tullinimike1='$tullinimike1' $lisa order by tuoteno";
	$resul = mysql_query($query) or pupe_error($query);;

	if (mysql_num_rows($resul) == 0) {
		echo "<font class='error'>Yhtään tuotetta ei löytynyt!</font><br>";
	}
	else {

		echo sprintf("<font class='message'>Haulla löytyi %s tuotetta.</font><br><br>", mysql_num_rows($resul));

		echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
		echo "<input type='hidden' name='tullinimike1' value='$tullinimike1'>";
		echo "<input type='hidden' name='tullinimike2' value='$tullinimike2'>";
		echo "<input type='hidden' name='tee' value='muuta'>";

		echo "<table>";
		echo "<tr>";
		echo "<th>Syötä uusi tullinimike 1:</th>";
		echo "<td><input type='text' name='uusitullinimike1' value='$uusitullinimike1'></td>";
		echo "</tr><tr>";
		echo "<th>Syötä uusi tullinimike 2:</th>";
		echo "<td><input type='text' name='uusitullinimike2' value='$uusitullinimike2'></td>";
		echo "<td class='back'><input type='submit' value='".t("Päivitä")."'></td>";
		echo "</tr></table>";
		echo "</form>";		

		echo "<table>";
		echo "<tr>";
		echo "<th>Tuoteno</th>";
		echo "<th>Osasto</th>";
		echo "<th>Try</th>";
		echo "<th>Merkki</th>";
		echo "<th>Nimitys</th>";
		echo "<th>Tullinimike 1</th>";
		echo "<th>Tullinimike 2</th>";
		echo "</tr>";

		while ($rivi = mysql_fetch_array($resul)) {
			
			$query = "	SELECT distinct selite, selitetark
						FROM avainsana
						WHERE yhtio='$kukarow[yhtio]' and laji='OSASTO' and selite='$rivi[osasto]'
						ORDER BY jarjestys";
			$oresult = mysql_query($query) or pupe_error($query);
			$os = mysql_fetch_array($oresult);
			
			$query = "	SELECT distinct selite, selitetark
						FROM avainsana
						WHERE yhtio='$kukarow[yhtio]' and laji='TRY' and selite='$rivi[try]'
						ORDER BY jarjestys";
			$tresult = mysql_query($query) or pupe_error($query);
			$try = mysql_fetch_array($tresult);
			
			echo "<tr>";
			echo "<td><a href='yllapito.php?toim=tuote&tunnus=$rivi[tunnus]&lopetus=tullinimikkeet.php'>$rivi[tuoteno]</a></td>";
			echo "<td>$rivi[osasto] $os[selitetark]</td>";
			echo "<td>$rivi[try] $try[selitetark]</td>";
			echo "<td>$rivi[tuotemerkki]</td>";			
			echo "<td>".asana('nimitys_',$rivi['tuoteno'],$rivi['nimitys'])."</td>";
			echo "<td>$rivi[tullinimike1]</td>";
			echo "<td>$rivi[tullinimike2]</td>";
			echo "</tr>";
		}
		echo "</table>";
	}
}

require ("inc/footer.inc");

?>
