<?php

require ("inc/parametrit.inc");

echo "<font class='head'>".t("Tuotenumeroiden vaihto")."</font><hr>";

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

flush();

$vikaa=0;
$tarkea=0;
$kielletty=0;
$lask=0;
$postoiminto = 'X';
$tyhjatok  = "";
$kentta = 'tuoteno';
$chekatut = 0;
$taulut = '';
$error = 0;

if (is_uploaded_file($_FILES['userfile']['tmp_name'])==TRUE) {

	list($name,$ext) = split("\.", $_FILES['userfile']['name']);

	if (strtoupper($ext) !="TXT" and strtoupper($ext)!="CSV")
	{
		die ("<font class='error'><br>".t("Ainoastaan .txt ja .cvs tiedostot sallittuja")."!</font>");
	}

	if ($_FILES['userfile']['size']==0)
	{
		die ("<font class='error'><br>".t("Tiedosto on tyhj‰")."!</font>");
	}
	
	$file=fopen($_FILES['userfile']['tmp_name'],"r") or die (t("Tiedoston avaus ep‰onnistui")."!");

	echo "<font class='message'>".t("Tutkaillaan mit‰ olet l‰hett‰nyt").".<br></font>";
	flush();
	
	while (!feof($file)) {
		// luetaan rivi tiedostosta..
		$poista	  = array("'", "\\","\"");
		$rivi	  = str_replace($poista,"",$rivi);
		$rivi	  = explode("\t", $rivi);

		if((trim($rivi[0]) != '') and (trim($rivi[1]) != '')) {
			
			$vantuoteno = $rivi[0];
			$uustuoteno = $rivi[1];
			$query  = "select tunnus from tuote where yhtio = '$kukarow[yhtio]' and tuoteno = '$vantuoteno'";
			$tuoteresult = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($tuoteresult) != '0') {
				$query  = "select tunnus from tuote where yhtio = '$kukarow[yhtio]' and tuoteno = '$uustuoteno'";
				$tuoteuresult = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($tuoteuresult) != '0') {
					$error++;
					echo "".t("UUSI TUOTENUMERO L÷YTYY JO")." $uustuoteno<br>";
				}
			}
			else {
				$error++;
				echo "".t("VANHAA TUOTENUMEROA EI L÷YDY")." $vantuoteno<br>";
			}
		}
		else {
			if (trim($rivi[0]) == '' and trim($rivi[1]) != '') {
				$error++;
				echo "".t("Vanha tuotenumero puuttuu tiedostosta").": (tyhj‰) --> $rivi[1]<br>";
			}
			elseif (trim($rivi[1]) == '' and trim($rivi[0]) != '') {
				$error++;
				echo "".t("Uusi tuotenumero puuttuu tiedostosta").": $rivi[0] --> (tyhj‰)<br>";
			}
		}
		$rivi = fgets($file, 4096);
	} // end while eof

	fclose($file);
	
	if ($error== 0) {
		
		echo "".t("Tiedosto oli ok")."<br><br>";
		flush();
		
		$file=fopen($_FILES['userfile']['tmp_name'],"r") or die (t("Tiedoston avaus ep‰onnistui")."!");

		echo "<font class='message'>".t("Aloitellaan p‰ivitys, t‰m‰ voi kest‰‰ hetken").".<br></font>";
		flush();
		
		$tulos = array();
	
		$query  = "SHOW TABLES FROM pupesoft";
		$tabresult = mysql_query($query) or pupe_error($query);
		while ($tables = mysql_fetch_array($tabresult)) {
			$query  = "describe $tables[0]";
			$fieldresult = mysql_query($query) or pupe_error($query);
			while ($fields = mysql_fetch_array($fieldresult)) {
				$pos = strpos($fields[0], $kentta);
				if ($pos !== false and $tables[0] != 'tuote_muutokset' and $fields[0] != 'toim_tuoteno') {
				//if ($fields[0] == $kentta) {
					//echo "$tables[0] $fields[0]<br>";
					$taulut .= $tables[0].' WRITE,';
					$tulos[] = $tables[0];
				}
			}
		}
		$taulut .= 'tuote_muutokset WRITE';
		//echo "$taulut<br>";
		$montako =count($tulos);
		//echo "countti = $montako<br>";
		if ($montako > 0) {
			echo "".t("Lˆydettiin paikat joita pit‰‰ muuttaa")."<br>";
			flush();
		}
		else {
			die ("<font class='error'><br>".t("Ei lˆydetty muutettavia paikkoja, ei uskalleta tehd‰ mit‰‰n")."!</font>");
		}
		$tyyppi = "(";

		$query  = "select distinct tyyppi from tilausrivi where yhtio = '$kukarow[yhtio]' and tuoteno != ''";
		$tyypitresult = mysql_query($query) or pupe_error($query);
		while ($tyypitrow = mysql_fetch_array($tyypitresult)) {
			$tyyppi .= "'"."$tyypitrow[tyyppi]"."'".",";
		}
		$tyyppi = substr($tyyppi, 0, -1).")";
	
		echo "".t("Nyt ollan ker‰tty tietokannasta kaikki tarpeellinen")."<br>".t("Aloitellaan muutos")."<br>";
		flush();

		while (!feof($file)) {
			// luetaan rivi tiedostosta..
			$poista	  = array("'", "\\","\"");
			$rivi	  = str_replace($poista,"",$rivi);
			$rivi	  = explode("\t", trim($rivi));

			if((trim($rivi[0]) != '') and (trim($rivi[1]) != '')) {
			
				$lokki = "LOCK TABLES $taulut";
				$vantuoteno = $rivi[0];
				$uustuoteno = $rivi[1];
				$query  = "select tunnus from tuote where yhtio = '$kukarow[yhtio]' and tuoteno = '$vantuoteno'";
				$tuoteresult = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($tuoteresult) == '1') {
					$query  = "select tunnus from tuote where yhtio = '$kukarow[yhtio]' and tuoteno = '$uustuoteno'";
					$tuoteuresult = mysql_query($query) or pupe_error($query);
					if (mysql_num_rows($tuoteuresult) == '0') {
					
						$query = "INSERT INTO tuote_muutokset
									SET 
									yhtio = '$kukarow[yhtio]',
									tuoteno = '$uustuoteno',
									alkup_tuoteno = '$vantuoteno',
									muutospvm = now(),
									kuka = '$kukarow[kuka]'";
						$result2 = mysql_query($query) or pupe_error($query);
					
						foreach ($tulos as $tulos2) {
							if ($tulos2 == 'tilausrivi') {
								$query = "UPDATE $tulos2
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
								$query = "UPDATE $tulos2
											SET
											tuoteno	= '$uustuoteno'
											WHERE
											yhtio = '$kukarow[yhtio]'
											and tuoteno	= '$vantuoteno'
											ORDER BY yhtio";
								$result2 = mysql_query($query) or pupe_error($query);
								$query = "UPDATE $tulos2
											SET
											isatuoteno = '$uustuoteno'
											WHERE
											yhtio = '$kukarow[yhtio]'
											and isatuoteno = '$vantuoteno'
											ORDER BY yhtio";
								$result2 = mysql_query($query) or pupe_error($query);
							}
							else {
								$query = "UPDATE $tulos2
											SET
											tuoteno	= '$uustuoteno'
											WHERE
											yhtio = '$kukarow[yhtio]'
											and tuoteno	= '$vantuoteno'
											ORDER BY yhtio";
								$result2 = mysql_query($query) or pupe_error($query);
							}	
						}
						if ($jatavanha != '') {
							$query = "select * from avainsana where yhtio = '$kukarow[yhtio]' and laji = 'alv' and selitetark = 'o' LIMIT 1";
							$alvresult = mysql_query($query) or pupe_error($query);
							$alv = '0.00';
							if (mysql_num_rows($alvresult) != '0') {
								$alvrow = mysql_fetch_array($alvresult);
								$alv = $alvrow['selite'];
							}
							$query = "INSERT INTO tuote
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

							$querykorv = "select max(id)+1 maxi from korvaavat where yhtio = '$kukarow[yhtio]'";
							$korvresult = mysql_query($querykorv) or pupe_error($querykorv);
							$korvid = mysql_fetch_array($korvresult);

							$loytyikorv = '';

							$querykorvv  = "select id maxi from korvaavat where yhtio = '$kukarow[yhtio]' and tuoteno = '$uustuoteno'";
							$korvvresult = mysql_query($querykorvv) or pupe_error($querykorvv);
							if (mysql_num_rows($korvvresult) != '0') {
								$korvid = mysql_fetch_array($korvvresult);
								//echo "lˆytyi korvid"."$korvid[maxi]"." $vantuoteno --> $uustuoteno<br>";
								$loytyikorv = '1';
							}
							$query = "INSERT INTO korvaavat
										SET
										tuoteno 			= '$vantuoteno',
										id					= '$korvid[maxi]',
										yhtio				= '$kukarow[yhtio]'";
							$result4 = mysql_query($query) or pupe_error($query);
							if ($loytyikorv != '1') {
								$query = "INSERT INTO korvaavat
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
						echo "".t("UUSI TUOTENUMERO L÷YTYY JO")." $uustuoteno<br>";
					}
				}
				else {
					echo "".t("VANHAA TUOTENUMEROA EI L÷YDY")." $vantuoteno<br>";
				}
			}
			$unlokki = "UNLOCK TABLES";
			$rivi = fgets($file, 4096);
		} // end while eof

		fclose($file);

		echo "<br><font class='message'>".t("Valmis, muutettiin")." $lask ".t("tuotetta")."!<br></font>";
	}
	else {
		echo "<font class='error'>".t("Edell‰mainitut viat pit‰‰ korjata ennenkuin voidaan jatkaa")."!!!<br>".t("Mit‰‰n ei p‰ivitetty")."!!!<br><br>";
	}

}
else
{
	echo	"<font class='message'>".t("Tiedostomuoto").":</font><br>

			<table border='0' cellpadding='3' cellspacing='2'>
			<tr><th colspan='2'>".t("Tabulaattorilla eroteltu tekstitiedosto").".</th></tr>
			<tr><td>".t("VANHA tuotenumero")."</td><td>".t("UUSI tuotenumero")."</td></tr>
			</table>	
			<br><br><br>";
			
	echo "<form method='post' name='sendfile' enctype='multipart/form-data' action='$PHP_SELF'>
			<table>
			<tr>
				<td colspan='2'>".t("J‰tet‰‰nkˆ vanha ja lis‰t‰‰n korvaavuus")."?</td>
				<td align='center'><input type='checkbox' name='jatavanha' value='jatavanha'</td>
			</tr>
			<tr>
				<td colspan='3' class='back'>&nbsp;</td>
			</tr>
			
			<input type='hidden' name='tee' value='file'>

			<tr><td>".t("Valitse tiedosto").":</td>
				<td><input name='userfile' type='file'></td>
				<td class='back'><input type='submit' value='".t("L‰het‰")."'></td>
			</tr>

			</table>
			</form>";
}

require ("inc/footer.inc");

?>