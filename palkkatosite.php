<?
require "inc/parametrit.inc";
if (is_uploaded_file($_FILES['userfile']['tmp_name'])==TRUE) {

	// T‰m‰ on Pretaxin palkkaohjelmiston normaali siirtomuoto ver 2
	// Tuetaan myˆs M2 matkalaskuohjelmista

	if ($_FILES['userfile']['size']==0){
		die ("<font class='error'><br>".t("Tiedosto on tyhj‰")."!</font>");
	}

	$file	 = fopen($_FILES['userfile']['tmp_name'],"r") or die (t("Tiedoston avaus ep‰onnistui")."!");
	$rivi    = fgets($file);

	$maara=1;
	$flip = 0;

	while (!feof($file)) {

/*		$isumma[$maara] = (float) substr($rivi,53,14) / 100;
		$itili[$maara]  = (int) substr($rivi,10,6);
		$ikustp[$maara] = substr($rivi,16,3);
		$iselite[$maara] = "Palkkatosite ". $tpp . "." . $tpk . "." . $tpv;  
*/

		if (strlen($rivi) == 852) {
			if (!isset($tpv)) {
				$tpv=substr($rivi,639,4);
				$tpk=substr($rivi,643,2);
				$tpp=substr($rivi,645,2);
			}
			if ($flip == 1) { // Seuraavalla rivill‰ tulee veronm‰‰r‰. Lis‰t‰‰n se!
					$maara--;
					$alv = (float) substr($rivi,24,12);
					if (substr($rivi,23,1) == 'K') $alv *= -1;
					$isumma[$maara] += $alv;
					$flip = 0;
			}
			else {
				$isumma[$maara] = (float) substr($rivi,24,12);
				if (substr($rivi,23,1) == 'K') $isumma[$maara] *= -1;
				$itili[$maara]  = (int) substr($rivi,13,4);
				$ikustp[$maara] = (int) substr($rivi,228,5);
				// Etsit‰‰‰n vastaava kustannuspaikka
				$query = "SELECT tunnus
						FROM kustannuspaikka
						WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'P' and kaytossa <> 'E' and nimi = '$ikustp[$maara]'";
				$result = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($result) == 1) {
					$row = mysql_fetch_array($result);
					$ikustp[$maara] = $row[1];
				}
				$iselite[$maara] = "Matkalasku ". $tpp . "." . $tpk . "." . $tpv . " " . trim(substr($rivi,240,50)) . " " . trim(substr($rivi,431,60));
				$ivero[$maara] = (float) substr($rivi,332,5);
				if ($ivero[$maara] != 0.0) $flip = 1;
			}
		}
		else {
			if (!isset($tpv)) {
				$tpv=substr($rivi,39,4);
				$tpk=substr($rivi,43,2);
				$tpp=substr($rivi,45,2);
			}
			$isumma[$maara] = (float) substr($rivi,117,16) / 100;
			$itili[$maara]  = (int) substr($rivi,190,7);
			$ikustp[$maara] = (int) substr($rivi,198,3);
			$iselite[$maara] = "Palkkatosite ". $tpp . "." . $tpk . "." . $tpv;
		}
		
		$maara++;

		// luetaan seuraava rivi failista
		$rivi = fgets($file);

	}

	fclose($file);
	
	unset($_FILES['userfile']['tmp_name']);
	unset($_FILES['userfile']['error']);
	
	$gok = 1; // Pakotetaan virhw
	$tee = 'I';
	
	require ('tosite.php');
	
	exit;
}

require ("inc/parametrit.inc");
echo "<font class='head'>".t("Palkka- ja matkalaskuaineiston sis‰‰nluku")."</font><hr>";
echo "<form method='post' name='sendfile' enctype='multipart/form-data' action='$PHP_SELF'>
		<table>
		<tr><td>".t("Valitse tiedosto").":</td>
			<td><input name='userfile' type='file'></td>
			<td class='back'><input type='submit' value='".t("L‰het‰")."'></td>
		</tr>
		</table>
		</form>";	

require ("inc/footer.inc");
