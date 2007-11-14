<?php

require ("inc/parametrit.inc");

echo "<font class='head'>".t("Tuotenumeroiden vaihto")."</font><hr>";
flush();

$vikaa			= 0;
$tarkea			= 0;
$kielletty		= 0;
$lask			= 0;
$postoiminto 	= 'X';
$tyhjatok  		= "";
$kentta 		= 'tuoteno';
$chekatut 		= 0;
$taulut 		= '';
$error 			= 0;
$failista		= "";
$uusi_on_jo		= "";

if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE and $tee == "file") {
	//Tuotenumerot tulevat tiedostosta
	list($name,$ext) = split("\.", $_FILES['userfile']['name']);

	if (strtoupper($ext) !="TXT" and strtoupper($ext)!="CSV") {
		die ("<font class='error'><br>".t("Ainoastaan .txt ja .cvs tiedostot sallittuja")."!</font>");
	}

	if ($_FILES['userfile']['size']==0) {
		die ("<font class='error'><br>".t("Tiedosto on tyhjä")."!</font>");
	}
	
	$file=fopen($_FILES['userfile']['tmp_name'],"r") or die (t("Tiedoston avaus epäonnistui")."!");

	echo "<font class='message'>".t("Tutkaillaan mitä olet lähettänyt").".<br></font>";
	flush();
	
	while (!feof($file)) {
		// luetaan rivi tiedostosta..
		$poista	  = array("'", "\\","\"");
		$rivi	  = str_replace($poista,"",$rivi);
		$rivi	  = explode("\t", $rivi);

		if(trim($rivi[0]) != '' and trim($rivi[1]) != '') {
			
			$vantuoteno = strtoupper(trim($rivi[0]));
			$uustuoteno = strtoupper(trim($rivi[1]));
			
			$query  = "select tunnus from tuote where yhtio = '$kukarow[yhtio]' and tuoteno = '$vantuoteno'";
			$tuoteresult = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($tuoteresult) != '0') {
				$query  = "select tunnus from tuote where yhtio = '$kukarow[yhtio]' and tuoteno = '$uustuoteno'";
				$tuoteuresult = mysql_query($query) or pupe_error($query);
				
				if (mysql_num_rows($tuoteuresult) != '0') {
					$error++;
					echo "<font class='message'>".t("UUSI TUOTENUMERO LÖYTYY JO").": $uustuoteno</font><br>";
				}
			}
			else {
				$error++;
				echo "<font class='message'>".t("VANHAA TUOTENUMEROA EI LÖYDY").": $vantuoteno</font><br>";
			}
		}
		else {
			if (trim($rivi[0]) == '' and trim($rivi[1]) != '') {
				$error++;
				echo "<font class='message'>".t("Vanha tuotenumero puuttuu tiedostosta").": (tyhjä) --> $rivi[1]</font><br>";
			}
			elseif (trim($rivi[1]) == '' and trim($rivi[0]) != '') {
				$error++;
				echo "<font class='message'>".t("Uusi tuotenumero puuttuu tiedostosta").": $rivi[0] --> (tyhjä)</font><br>";
			}
		}
		$rivi = fgets($file, 4096);
	}
	
	$failista = "JOO";
	fclose($file);
}
elseif (is_uploaded_file($_FILES['userfile']['tmp_name']) !== TRUE and $tee == "file") {
	//Tuotenumerot tulevat käyttöliittymästä
	$query  = "select tunnus, kehahin from tuote where yhtio = '$kukarow[yhtio]' and tuoteno = '$vantuoteno'";
	$tuoteresult = mysql_query($query) or pupe_error($query);
	
	if (mysql_num_rows($tuoteresult) != 0) {
		$vantuoterow = mysql_fetch_array($tuoteresult);
		
		$query  = "select tunnus, kehahin from tuote where yhtio = '$kukarow[yhtio]' and tuoteno = '$uustuoteno'";
		$tuoteuresult = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($tuoteuresult) != 0) {
			$uustuoterow = mysql_fetch_array($tuoteresult);		
			
			echo "<font class='message'>".t("UUSI TUOTENUMERO LÖYTYY JO").": $uustuoteno</font><br>";
			
			//Jos molempien tuotteiden varastonarvo on nolla niin ei haittaa vaikka uusi tuote löytyy jo
			$query = "	SELECT sum(saldo) saldo
						FROM tuotepaikat
						WHERE yhtio = '$kukarow[yhtio]' 
						and tuoteno = '$vantuoteno'";
			$result = mysql_query($query) or pupe_error($query);
			$vansaldorow = mysql_fetch_array($result);
			
			$query = "	SELECT sum(saldo) saldo
						FROM tuotepaikat
						WHERE yhtio = '$kukarow[yhtio]' 
						and tuoteno = '$uustuoteno'";
			$result = mysql_query($query) or pupe_error($query);
			$uussaldorow = mysql_fetch_array($result);
			
			if (($uustuoterow["kehahin"] == 0 and $vantuoterow["kehahin"] == 0) or ($vansaldorow["saldo"] == 0 and $uussaldorow["saldo"] == 0)) {
				$uusi_on_jo = "OK";
			}
			else {
				$error++;
			}
		}
	}
	else {
		$error++;
		echo "<font class='message'>".t("VANHAA TUOTENUMEROA EI LÖYDY").": $vantuoteno</font><br>";
	}
	
	$failista 	= "EI";
}

if ($error == 0 and $tee == "file") {
	
	echo "<font class='message'>".t("Syötetyt tiedot ovat ok")."</font><br><br>";
	flush();
	
	echo "<font class='message'>".t("Aloitellaan päivitys, tämä voi kestää hetken").".<br></font>";
	flush();
	
	$tulos = array();

	//$dbkanta --> tulee salasanat.php:stä

	$query  = "SHOW TABLES FROM $dbkanta";
	$tabresult = mysql_query($query) or pupe_error($query);
	
	while ($tables = mysql_fetch_array($tabresult)) {
		$query  = "describe $tables[0]";
		$fieldresult = mysql_query($query) or pupe_error($query);
		
		while ($fields = mysql_fetch_array($fieldresult)) {						
			if (strpos($fields[0], $kentta) !== false and $tables[0] != 'tuote_muutokset' and $fields[0] != 'toim_tuoteno' and !in_array($tables[0], $tulos)) {
				$taulut .= $tables[0].' WRITE,';
				$tulos[] = $tables[0];
			}
		}
	}
	$taulut .= 'tuote_muutokset WRITE, sanakirja WRITE, avainsana WRITE';
		
	$montako =count($tulos);

	if ($montako > 0) {
		echo "<font class='message'>".t("Löydettiin paikat joita pitää muuttaa").": $montako kappaletta.</font><br>";
		flush();
	}
	else {
		die ("<font class='error'><br>".t("Ei löydetty muutettavia paikkoja, ei uskalleta tehdä mitään")."!</font>");
	}
	
	$tyyppi = "(";

	$query  = "select distinct tyyppi from tilausrivi where yhtio = '$kukarow[yhtio]' and tuoteno != ''";
	$tyypitresult = mysql_query($query) or pupe_error($query);
	
	while ($tyypitrow = mysql_fetch_array($tyypitresult)) {
		$tyyppi .= "'"."$tyypitrow[tyyppi]"."'".",";
	}
	$tyyppi = substr($tyyppi, 0, -1).")";

	echo "<font class='message'>".t("Nyt ollan kerätty tietokannasta kaikki tarpeellinen")."<br>".t("Aloitellaan muutos")."...</font><br>";
	flush();

	if ($failista == "JOO") {
		$file = fopen($_FILES['userfile']['tmp_name'],"r") or die (t("Tiedoston avaus epäonnistui")."!");
		$rivi = fgets($file, 4096);
		$lask_kaks = 0;
	}
	else {
		$rivi = "$vantuoteno	$uustuoteno";
		$lask_kaks = 0;
	}

	
	while (!feof($file) and $lask_kaks == 0) {
		// luetaan rivi tiedostosta..
		$poista	  = array("'", "\\","\"");
		$rivi	  = str_replace($poista,"",$rivi);
		$rivi	  = explode("\t", trim($rivi));

		if(trim($rivi[0]) != '' and trim($rivi[1]) != '') {
		
			$lokki = "LOCK TABLES $taulut";
			$res   = mysql_query($lokki) or pupe_error($lokki);
			
			
			$vantuoteno = strtoupper(trim($rivi[0]));
			$uustuoteno = strtoupper(trim($rivi[1]));
			
			$query  = "select tunnus from tuote where yhtio = '$kukarow[yhtio]' and tuoteno = '$vantuoteno'";
			$tuoteresult = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($tuoteresult) == 1) {
				
				$query  = "select tunnus from tuote where yhtio = '$kukarow[yhtio]' and tuoteno = '$uustuoteno'";
				$tuoteuresult = mysql_query($query) or pupe_error($query);
				
				if (mysql_num_rows($tuoteuresult) == 0 or $uusi_on_jo == "OK") {
				
					$query = "	INSERT INTO tuote_muutokset
								SET 
								yhtio = '$kukarow[yhtio]',
								tuoteno = '$uustuoteno',
								alkup_tuoteno = '$vantuoteno',
								muutospvm = now(),
								kuka = '$kukarow[kuka]'";
					$result2 = mysql_query($query) or pupe_error($query);
					
					echo "<font class='message'>".t("Vaihdetaan tuotenumero ja siirretään historiatiedot").": $vantuoteno --> $uustuoteno.</font><br>";
					flush();
				
					foreach ($tulos as $tulos2) {
						if ($tulos2 == 'tilausrivi') {
							$query = "	UPDATE $tulos2
										SET
										tuoteno	= '$uustuoteno'
										WHERE
										yhtio = '$kukarow[yhtio]'
										and tyyppi in $tyyppi
										and tuoteno	= '$vantuoteno'
										ORDER BY yhtio";
							$result2 = mysql_query($query) or pupe_error($query);
						}
						elseif ($tulos2 == 'tuoteperhe') {
							$query = "	UPDATE $tulos2
										SET
										tuoteno	= '$uustuoteno'
										WHERE
										yhtio = '$kukarow[yhtio]'
										and tuoteno	= '$vantuoteno'
										ORDER BY yhtio";
							$result2 = mysql_query($query) or pupe_error($query);
														
							$query = "	UPDATE $tulos2
										SET
										isatuoteno = '$uustuoteno'
										WHERE
										yhtio = '$kukarow[yhtio]'
										and isatuoteno = '$vantuoteno'
										ORDER BY yhtio";
							$result2 = mysql_query($query) or pupe_error($query);
						}
						elseif ($tulos2 == 'tuotepaikat') {
							// Tuotepaikat käsitellään hieman eri lailla
							$query = "	SELECT * 
										FROM tuotepaikat 
										WHERE yhtio = '$kukarow[yhtio]'
										and tuoteno	= '$vantuoteno'";
							$paires = mysql_query($query) or pupe_error($query);
							
							while($pairow = mysql_fetch_array($paires)) {
								
								// Tutkitaan löytyykö vanhan tuotteen paikka uudella tuoteella
								$query = "	SELECT * 
											FROM tuotepaikat 
											WHERE yhtio 	= '$kukarow[yhtio]'
											and tuoteno		= '$uustuoteno'
											and hyllyalue	= '$pairow[hyllyalue]'
											and hyllynro	= '$pairow[hyllynro]'
											and hyllyvali	= '$pairow[hyllyvali]'
											and hyllytaso	= '$pairow[hyllytaso]'";
								$paires2 = mysql_query($query) or pupe_error($query);
								
								if (mysql_num_rows($paires2) == 0) {
									$query = "	UPDATE tuotepaikat
												SET tuoteno = '$uustuoteno'
												WHERE yhtio 	= '$kukarow[yhtio]'
												and tuoteno		= '$vantuoteno'
												and hyllyalue	= '$pairow[hyllyalue]'
												and hyllynro	= '$pairow[hyllynro]'
												and hyllyvali	= '$pairow[hyllyvali]'
												and hyllytaso	= '$pairow[hyllytaso]'";
									$result2 = mysql_query($query) or pupe_error($query);
								}
								else {
									$query = "	UPDATE tuotepaikat
												SET saldo = saldo+$pairow[saldo]
												WHERE yhtio 	= '$kukarow[yhtio]'
												and tuoteno		= '$uustuoteno'
												and hyllyalue	= '$pairow[hyllyalue]'
												and hyllynro	= '$pairow[hyllynro]'
												and hyllyvali	= '$pairow[hyllyvali]'
												and hyllytaso	= '$pairow[hyllytaso]'";
									$result2 = mysql_query($query) or pupe_error($query);
									
									$query = "	DELETE from tuotepaikat
												WHERE yhtio 	= '$kukarow[yhtio]'
												and tuoteno		= '$vantuoteno'
												and hyllyalue	= '$pairow[hyllyalue]'
												and hyllynro	= '$pairow[hyllynro]'
												and hyllyvali	= '$pairow[hyllyvali]'
												and hyllytaso	= '$pairow[hyllytaso]'
												and tunnus 		= '$pairow[tunnus]'";
									$result2 = mysql_query($query) or pupe_error($query);
								}								
							}
							
							// Fixaataan jottei meille jäisi useita oletuspaikkoja
							$query = "	SELECT *
										FROM tuotepaikat 
										WHERE yhtio 	= '$kukarow[yhtio]'
										and tuoteno		= '$uustuoteno'
										and oletus 	   != ''";
							$paires = mysql_query($query) or pupe_error($query);

							if (mysql_num_rows($paires) == 0) {
								$query = "	UPDATE tuotepaikat
											SET oletus = 'X'
											WHERE yhtio = '$kukarow[yhtio]'
											and tuoteno	= '$uustuoteno'
											ORDER BY tunnus
											LIMIT 1";
								$result2 = mysql_query($query) or pupe_error($query);
							}
							elseif (mysql_num_rows($paires) > 1) {
								$query = "	UPDATE tuotepaikat
											SET oletus = ''
											WHERE yhtio = '$kukarow[yhtio]'
											and tuoteno	= '$uustuoteno'
											and oletus != ''
											ORDER BY tunnus
											LIMIT ".(mysql_num_rows($paires)-1);
								$result2 = mysql_query($query) or pupe_error($query);
							}
						}
						elseif($uusi_on_jo == "" or ($uusi_on_jo == "OK" and $tulos2 != 'tuote')) {
							$query = "	UPDATE $tulos2
										SET tuoteno	= '$uustuoteno'
										WHERE yhtio = '$kukarow[yhtio]'
										and tuoteno	= '$vantuoteno'
										ORDER BY yhtio";
							$result2 = mysql_query($query) or pupe_error($query);							
						}						
					}
					
					if ($jatavanha != '') {
						if($uusi_on_jo == "") {
							$query = "select * from avainsana where yhtio = '$kukarow[yhtio]' and laji = 'alv' and selitetark = 'o' LIMIT 1";
							$alvresult = mysql_query($query) or pupe_error($query);
							$alv = '0.00';
						
							if (mysql_num_rows($alvresult) != '0') {
								$alvrow = mysql_fetch_array($alvresult);
								$alv = $alvrow['selite'];
							}
						
							$query = "	INSERT INTO tuote
										SET
										tuoteno 		= '$vantuoteno',
										nimitys			= '-->--> $uustuoteno',
										osasto			= '9',
										try				= '999',
										alv				= '$alv',
										status			= 'P',
										hinnastoon		= 'E',
										yhtio			= '$kukarow[yhtio]'";
							$result3 = mysql_query($query) or pupe_error($query);
						}
						
						$querykorv = "select max(id)+1 maxi from korvaavat where yhtio = '$kukarow[yhtio]'";
						$korvresult = mysql_query($querykorv) or pupe_error($querykorv);
						$korvid = mysql_fetch_array($korvresult);

						$loytyikorv = '';

						$querykorvv  = "select id maxi from korvaavat where yhtio = '$kukarow[yhtio]' and tuoteno = '$uustuoteno'";
						$korvvresult = mysql_query($querykorvv) or pupe_error($querykorvv);
					
						if (mysql_num_rows($korvvresult) != '0') {
							$korvid = mysql_fetch_array($korvvresult);
							//echo "löytyi korvid"."$korvid[maxi]"." $vantuoteno --> $uustuoteno<br>";
							$loytyikorv = '1';
						}
						
						$query = "	INSERT INTO korvaavat
									SET
									tuoteno 			= '$vantuoteno',
									id					= '$korvid[maxi]',
									yhtio				= '$kukarow[yhtio]'";
						$result4 = mysql_query($query) or pupe_error($query);
						
						if ($loytyikorv != '1') {
							$query = "	INSERT INTO korvaavat
										SET
										tuoteno 			= '$uustuoteno',
										id					= '$korvid[maxi]',
										yhtio				= '$kukarow[yhtio]'";
							$result4 = mysql_query($query) or pupe_error($query);
						}
					}
					$lask++;
				}
				else {
					echo t("UUSI TUOTENUMERO LÖYTYY JO")." $uustuoteno<br>";
				}
			}
			else {
				echo t("VANHAA TUOTENUMEROA EI LÖYDY")." $vantuoteno<br>";
			}
		}
		
		$unlokki = "UNLOCK TABLES";
		$res     = mysql_query($unlokki) or pupe_error($unlokki);
		
		if ($failista == "JOO") {
			$rivi = fgets($file, 4096);
		}
		else {
			$lask_kaks++;
		}
	}
	
	if ($failista == "JOO") {
		fclose($file);
	}
	
	echo "<br><font class='message'>".t("Valmis, muutettiin")." $lask ".t("tuotetta")."!<br><br><br></font>";
	$tee = "";
}
elseif ($tee == "file") {
	echo "<font class='error'>".t("Edellämainitut viat pitää korjata ennenkuin voidaan jatkaa")."!!!<br>".t("Mitään ei päivitetty")."!!!<br><br>";
	$tee = "";
}


if ($tee == "") {
	echo	"<font class='message'>".t("Tiedostomuoto").":</font><br>
			<table>
			<tr><th colspan='2'>".t("Tabulaattorilla eroteltu tekstitiedosto").".</th></tr>
			<tr><td>".t("VANHA tuotenumero")."</td><td>".t("UUSI tuotenumero")."</td></tr>";
			
	echo "<form method='post' name='sendfile' enctype='multipart/form-data' action='$PHP_SELF'>
			<tr>
				<th>".t("Valitse tiedosto").":</th>
				<td><input name='userfile' type='file'></td>
			</tr>
			<tr>
				<td class='back'><br></td>
			</tr>
				<th colspan='2'>".t("Tai syöta tuotenumerot").":</th>
			</tr>
			
			<tr>
				<th>".t("Vanha tuotenumero").":</th>
				<td><input type='text' name='vantuoteno' size='25'></td>
			</tr>
			<tr>
				<th>".t("Uusi tuotenumero").":</th>
				<td><input type='text' name='uustuoteno' size='25'></td>
			</tr>
			<tr>
				<td class='back'><br></td>
			</tr>
			<tr>
				<th>".t("Jätetäänkö vanha ja lisätään korvaavuus")."?</th>
				<td><input type='checkbox' name='jatavanha' value='jatavanha'</td>
			</tr>
			</table>
			<br>
			<input type='hidden' name='tee' value='file'>
			<input type='submit' value='".t("Siirrä tuotteen historia")."'>
			</form>";
}

require ("inc/footer.inc");

?>