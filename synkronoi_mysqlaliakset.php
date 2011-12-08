<?php

require ("inc/parametrit.inc");

echo "<font class='head'>".t("Mysqlaliaksien synkronointi")."</font><hr>";

if ($oikeurow['paivitys'] != '1') { // Saako p‰ivitt‰‰
	if ($uusi == 1) {
		echo "<b>".t("Sinulla ei ole oikeutta lis‰t‰")."</b><br>";
		$uusi = '';
	}
	if ($del == 1) {
		echo "<b>".t("Sinulla ei ole oikeuttaa poistaa")."</b><br>";
		$del = '';
		$tunnus = 0;
	}
	if ($upd == 1) {
		echo "<b>".t("Sinulla ei ole oikeuttaa muuttaa")."</b><br>";
		$upd = '';
		$uusi = 0;
		$tunnus = 0;
	}
}

if ($tee == "TEE") {

	$file = fopen("http://api.devlab.fi/referenssialiakset.sql","r") or die (t("Tiedoston avaus ep‰onnistui")."!");

	$taulut = array();
	$rivit = array();

	// Eka rivi roskikseen
	$rivi = fgets($file);
	
	while ($rivi = fgets($file)) {
		list($perhe, $kieli, $selite, $selitetark, $selitetark_2, $selitetark_3, $jarjestys) = explode("\t", trim($rivi));
		
		list($taulu, $sarake) = explode(".", $selite);
		
		$taulut[] = $taulu."###".$selitetark_2;
		$rivit[] = $rivi;
	}
	
	fclose($file);
	
	$taulut = array_unique($taulut);
	
	// Poistetaan vanhat
	foreach ($taulut as $taulujaalias) {
		list($taul, $alia) = explode("###", $taulujaalias);
	
		$sanakirjaquery = "	DELETE FROM avainsana 
							WHERE yhtio = '$kukarow[yhtio]' 
							and laji = 'MYSQLALIAS' 
							and selite like '$taul%' 
							and selitetark_2 = '$alia'";
		$sanakirjaresult = mysql_query($sanakirjaquery) or pupe_error($sanakirjaquery);
	}
	
	
	echo "<br><table>";
	
	foreach ($rivit as $rivi) {
		list($perhe, $kieli, $selite, $selitetark, $selitetark_2, $selitetark_3, $jarjestys) = explode("\t", trim($rivi));

		$sanakirjaquery = "	SELECT * 
							FROM avainsana 
							WHERE yhtio = '$kukarow[yhtio]' 
							and laji = 'MYSQLALIAS' 
							and selite = '$selite' 
							and selitetark_2 = '$selitetark_2'";
		$sanakirjaresult = mysql_query($sanakirjaquery) or pupe_error($sanakirjaquery);

		if (mysql_num_rows($sanakirjaresult) > 0) {
			echo "<tr><th>".t("Ei lis‰t‰ mysqlaliasta")."</th><td>$selite</td><td>".htmlentities($selitetark)."</td><td>$selitetark_2</td></tr>";
		}
		else {
			$sanakirjaquery  = "INSERT INTO avainsana SET
								yhtio			= '$kukarow[yhtio]',
								laji 			= 'MYSQLALIAS',
								perhe			= '$perhe', 
								kieli			= '$kieli', 
								selite			= '$selite', 
								selitetark		= '$selitetark', 
								selitetark_2	= '$selitetark_2', 
								selitetark_3	= '$selitetark_3', 
								jarjestys		= '$jarjestys', 
								laatija			= '$kukarow[kuka]', 
								luontiaika		= now()";
			$sanakirjaresult = mysql_query($sanakirjaquery, $link) or pupe_error($sanakirjaquery);
			
			echo "<tr><th>".t("Lis‰t‰‰n mysqlalias")."</th><td>$selite</td><td>".htmlentities($selitetark)."</td><td>$selitetark_2</td></tr>";
		}
	}
	
	echo "</table><br>";
	
	echo t("Mysqlaliakset synkronoitu onnistuneesti")."!<br>";
	
}

echo "	<br><br>
		<form method='post' action='$PHP_SELF'>
		<input type='hidden' name='tee' value='TEE'>
		<input type='submit' value='".t("Hae uusimmat mysqlaliakset")."'>
		</form>";


require ("inc/footer.inc");

?>