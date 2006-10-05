<?php
function dateconv ($date)
{
	//kääntää vvkkmm muodon muotoon vv-kk-mm
	return substr($date,0,2). "-" . substr($date,2,2) . "-". substr($date,4,2);
}
$ok=0;

// tehdään tällänen häkkyrä niin voidaan scriptiä kutsua vaikka perlistä..

if ((trim($argv[1])=='perl') and (trim($argv[2])!='')) {

	if ($argc == 0) die ("Tätä scriptiä voi ajaa vain komentoriviltä!");

	require ("inc/connect.inc");
	require ("inc/functions.inc");
	$userfile=trim($argv[2]);
	$filenimi=$userfile;
	$ok=1;
}
else
	require "inc/parametrit.inc";

// katotaan onko faili uploadttu


if (is_uploaded_file($userfile)==TRUE) {
	$filenimi=$userfile_name;
	$ok=1;
}

if ($ok=='1') {
	$fd = fopen ($userfile, "r");
	if (!($fd)) {
		echo "<font class='message'>Tiedosto '$filenimi' ei auennut!</font>";
		exit;
	}
	$tietue = fgets($fd, 4096);
	
// Jos tämä on viiteaineisto, se käsitellään erikseen (Historialliset syyt)

	if($tietue{0} == '0') {		//meillä on kai viitemaksuaineistoa, vähän niukka tunnistus mutta..
// Onko tämä aineisto jo käsitelty
		$oliotsikko = '';
		while (!feof($fd)) {
			if ($tietue{0} == '0') { // Otsikolta aineiston luontipvm
				$tarkistapvm = "20" . dateconv(substr($tietue,1,6));
				$oliotsikko='X';
			}
			if ($tietue{0} == '3') { // Tapahtuma
				if ($oliotsikko=='X') {// Tarkistus
					$tilino = substr($tietue,1,14);
					//echo "<font class='message'>Maksupvm = $tarkistapvm Tilino=$tilino</font><br>";
					// Onko tämä aineisto jo ajettu?
					$query= "SELECT * FROM tiliotedata 
									WHERE tilino='$tilino' and alku = '$tarkistapvm' and tyyppi = 3";
					$tiliotedataresult = mysql_query($query) or pupe_error($query);
					if (mysql_num_rows($tiliotedataresult) > 0) {
						die ("<font class='error'>Tämä aineisto on jo aiemmin käsitelty! Tili = $tilino Ajalta $tarkistapvm</font><br>");
						
					}
				}
				$oliotsikko='';
			}
			$tietue = fgets($fd, 4096);
		}
		fclose ($fd);			
		$dirri = "datain/viitteet/";
		$filenimi = $dirri . "viitedata-" . $filenimi; 
		exec("mv $userfile $filenimi", $output, $returnval);
		if($returnval != 0){
			die("Tapahtui virhe tallentaessa tiedostoa $filenimi");
		}
		echo "<font class='message'>".t("Käsitellään saapuva viitemaksuaineisto")."</font><br>";
		flush();
		echo "<pre>";
		passthru("/usr/bin/perl myyntires/viitemaksut.pl -user $dbuser -pass $dbpass -host $dbhost -db $dbkanta -file $filenimi", $returnval);
		echo "</pre>";
		if ($returnval=='0')
			echo "<font class='message'>".t("Ajo onnistui")."</font><br>";
		else
			echo "<font class='error'>".t("Ajo epäonnistui")." $returnval</font><br>";
	}
	else {
		$query= "LOCK TABLE tiliotedata WRITE, yriti READ";
		$tiliotedataresult = mysql_query($query) or pupe_error($query);
		
		$query= "SELECT max(aineisto)+1 FROM tiliotedata";
		$tiliotedataresult = mysql_query($query) or pupe_error($query); // Etsitään aineistonumero
		$aineistorow=mysql_fetch_array ($tiliotedataresult);
		
		while (!feof ($fd)) {
			if ((substr($tietue,0,3) == 'T00') or (substr($tietue,0,3) == 'T03')) {
				if (substr($tietue,0,3) == 'T03') {
					$xtyyppi = 2; // Tämä on LMP
					$alkupvm=substr($tietue,38,6);
					$loppupvm = $alkupvm;
				}
				if (substr($tietue,0,3) == 'T00') {
					$xtyyppi = 1; // Tämä on tiliote
					$alkupvm = dateconv(substr($tietue,26,6));
					$loppupvm = dateconv(substr($tietue,32,6));
				}
				$tilino = substr($tietue, 9, 14);

				$query = "SELECT *
							FROM yriti
							WHERE tilino = '$tilino'";
				$yritiresult = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($yritiresult) != 1) {
					echo "<font class='error'> Tiliä '$tilino' ei löytynyt!</font><br>";
					$xtyyppi=0;
				}
				else {
					$yritirow=mysql_fetch_array ($yritiresult);
				}
				
				// Onko tämä aineisto jo ajettu?
				$query= "SELECT * FROM tiliotedata 
								WHERE tilino='$tilino' and alku = '$alkupvm' and loppu = '$loppupvm' and tyyppi = $xtyyppi";
				$tiliotedataresult = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($tiliotedataresult) > 0) {
					echo "<font class='error'>Tämä aineisto on jo aiemmin käsitelty! Tili = $tilino Ajalta $alkupvm - $loppupvm Yritystunnus $yritirow[yhtio]</font><br>";
					$xtyyppi=0;
				}
			}	
			$tietue = str_replace ( "{", "ä", $tietue);
			$tietue = str_replace ( "|", "Ö", $tietue);
			$tietue = str_replace ( "}", "å", $tietue);
			$tietue = str_replace ( "[", "Ä", $tietue);
			$tietue = str_replace ( "\\", "Ö", $tietue);
			$tietue = str_replace ( "]", "Å", $tietue);
			if (($xtyyppi > 0) and ($xtyyppi < 3)) {
				$tietue = str_replace ("'", " ", $tietue); // Poistaa SQL-virheen mahdollisuuden
				$query= "INSERT into tiliotedata (yhtio, aineisto, tilino, alku, loppu, tyyppi, tieto)
							values ('$yritirow[yhtio]', '$aineistorow[0]', '$tilino', '$alkupvm', '$loppupvm', '$xtyyppi', '$tietue')";
				$tiliotedataresult = mysql_query($query) or pupe_error($query); // Kirjoitetaan tiedosto kantaan
			}
			$tietue = fgets($fd, 4096);
		}
		$query= "UNLOCK TABLES";
		$tiliotedataresult = mysql_query($query) or pupe_error($query);
		
		$query= "SELECT * FROM tiliotedata 
						WHERE aineisto='$aineistorow[0]'
						ORDER BY tunnus";

		$tiliotedataresult = mysql_query($query) or pupe_error($query); // Käsitellään uudet tietueet
		
		while ($tiliotedatarow=mysql_fetch_array ($tiliotedataresult)) {
			$tietue = $tiliotedatarow['tieto'];

			if ($tiliotedatarow['tyyppi'] == 1) {
				require "inc/tiliote.inc";
			}
			if ($tiliotedatarow['tyyppi'] == 2) {
				require "inc/LMP.inc";
			}
		}
		//echo "tyyppi = $xtyyppi<br>";
		if ($xtyyppi == 1) {
			//echo "nyt PITÄISI syntiä vastavienti<br>";
			$tkesken = 0;
			echo "<tr><td colspan = '6'>";
			$maara = $vastavienti;
			require "inc/teeselvittely.inc";
			echo "</td></tr>";
			echo "</table>";
		}
		if ($xtyyppi == 2) {
			$tkesken = 0;
			$maara = $vastavienti;
			require "inc/teeselvittely.inc";
			echo "</table>";
		}
	}
}
else {
	echo "<font class='head'>Tiliotteen, LMP:n ja viitemaksujen käsittely</font><hr><br><br>";
	echo "<form enctype='multipart/form-data' name='sendfile' action='$PHP_SELF' method='post'>
		pankin aineisto <input type='file' name='userfile'><br><br>
		<input type='submit' value='Käsittele tiedosto'>
		</form>";
}
require "inc/footer.inc";
?>
