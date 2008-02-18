<?php
//Kun tullaan tänne tarvitaan $komento joka on se komento jolla tulostetaan esim. lpr -P tarrakirjoitin
//ja tarvitaan myös $tuoteno
$query = "select * from tuote where yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno'";
$tarrares = mysql_query($query) or pupe_error($query);
//tuote pitää löytyä
if (mysql_num_rows($tarrares) == 1) {
	$tarrarow = mysql_fetch_array($tarrares);
	//jos tullaan yksinkertaisesta näkymästä tulostetaan tuoteno eankoodin sijaan
	if ($toim== 'YKS') {
		$tarrarow['eankoodi'] = $tarrarow['tuoteno'];
	}
	//jos eankoodi kenttä on tyhjä ei viittitä edes lähteä tulostelemaan
	if ($tarrarow['eankoodi']!= '0') {
		$eankoodi = trim($tarrarow['eankoodi']);
		$nimitys = $tarrarow['nimitys'];
		$nimitys2 = '';
		
		//jos nimitys on pidempi kuin 25 merkkiä niin se ei mahdu yhdelle riville vaan pitää jakaa kahtia
		if (strlen($nimitys) > 25) {
			if (strpos($nimitys, " ")) {
				$nimipalat = split(' ',$nimitys);
				
				$merkkimaara = 0;
				$nimitys = "";
				$nimitys2 = "";
				
				foreach ($nimipalat as $nimipala) {
					if (strlen($nimitys)+strlen($nimipala) <= 25 or $merkkimaara == 0) {
						$nimitys .= $nimipala." ";
					}
					else {
						$nimitys2 .= $nimipala." ";
					}
					$merkkimaara += strlen($nimipala);
				}
			}
			
			$nimitys = substr($nimitys,0,25);
			$nimitys2 = substr($nimitys2,0,25);
		}
		$tuoteno = $tarrarow['tuoteno'];
		
		//tässä tehdään aineisto jota Zebra tulostin tajuaa
		/* Esimerkki
		D15
		N
		B125,0,0,3,2,5,45,B,"HC3310CPT40H     "
		A560,0,0,2,1,2,N,
		A120,75,0,3,1,2,N,"PALL PROFILE STAR        ""  ""         0"
		A120,115,0,3,1,2,N,"SUODATINPANOS            "
		P1
		*/
		
		$sivu =  "D15\n"; //DENSITY eli kirjainten vahvuus
		$sivu .= "N\n";
		$sivu .= "B125,0,0,3,2,5,45,B,"; //alku
		$sivu .= "\"";
		$sivu .= sprintf("%-17s",$tuoteno);
		$sivu .= "\"";
		$sivu .= "\n";
		$sivu .= "A560,0,0,2,1,2,N,";
		$sivu .= "\n";
		$sivu .= "A120,75,0,3,1,2,N,";
		$sivu .= "\"";
		$sivu .= sprintf("%-25s",$nimitys);
		$sivu .= "\"\"";
		$sivu .= "  ";
		$sivu .= "\"\"";
		$sivu .= sprintf("%10s",$tarrarow["tarrakpl"]);
		$sivu .= "\"";
		$sivu .= "\n";
		$sivu .= "A120,115,0,3,1,2,N,";
		$sivu .= "\"";
		$sivu .= sprintf("%-25s",$nimitys2);
		$sivu .= "\"";
		$sivu .= "\n";
		$sivu .= "P".$tkpl;
		$sivu .= "\n";

		//konvertoidaan ääkköset printterin ymmärtämään muotoon
		$from = array ('ä','å','ö','Ä','Å','Ö');
		$to   = array (chr(132),chr(134),chr(148),chr(142),chr(143),chr(153)); // DOS charset
		$sivu = str_replace($from, $to, $sivu);											// Tehdään käännös

		$sivu = escapeshellarg($sivu);
		
		//keksitään uudelle failille joku varmasti uniikki nimi:
		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000));
		$filenimi = "/tmp/CRM-Osoitetarrat-".md5(uniqid(mt_rand(), true)).".txt";
		$fh = fopen($filenimi, "w+");
		
		fwrite($fh,$sivu);
		
		fclose($fh);
		
		$line = exec("$komento $filenimi");
		
		//$line = exec(" echo \"$sivu\" | $komento");
		//echo "<pre>$sivu</pre><br>";
		
		system("rm -f $filenimi");
		
	}
	else {
		echo "<font class='error'>".t("Tuotteella")." $tuoteno ".t("ei ole viivakoodia järjestelmässä joten tarrojen tulostus taitaa olla turhaa")."!!!<br></font>";
	}
}
else {
	echo "<font class='error'>".t("Tuotetta")." $tuoteno ".t("ei löydy järjestelmästä")."!!!<br></font>";
}

?>