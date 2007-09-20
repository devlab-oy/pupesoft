<?

if (is_uploaded_file($_FILES['userfile']['tmp_name'])==TRUE) {

	// T‰m‰ on Pretaxin palkkaohjelmiston normaali siirtomuoto ver 2
	
	if ($_FILES['userfile']['size']==0){
		die ("<font class='error'><br>".t("Tiedosto on tyhj‰")."!</font>");
	}

	$file	 = fopen($_FILES['userfile']['tmp_name'],"r") or die (t("Tiedoston avaus ep‰onnistui")."!");
	$rivi    = fgets($file);
	
	$maara=1;
	
	$tpv=substr($rivi,39,4);
	$tpk=substr($rivi,43,2);
	$tpp=substr($rivi,45,2);
		
	while (!feof($file)) {

/*		$isumma[$maara] = (float) substr($rivi,53,14) / 100;
		$itili[$maara]  = (int) substr($rivi,10,6);
		$ikustp[$maara] = substr($rivi,16,3);
		$iselite[$maara] = "Palkkatosite ". $tpp . "." . $tpk . "." . $tpv;  
*/

		$isumma[$maara] = (float) substr($rivi,117,16) / 100;
		$itili[$maara]  = (int) substr($rivi,190,7);
		$ikustp[$maara] = (int) substr($rivi,198,3);
		$iselite[$maara] = "Palkkatosite ". $tpp . "." . $tpk . "." . $tpv;  

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
echo "<font class='head'>".t("Palkka-aineiston sis‰‰nluku")."</font><hr>";
echo "<form method='post' name='sendfile' enctype='multipart/form-data' action='$PHP_SELF'>
		<table>
		<tr><td>".t("Valitse tiedosto").":</td>
			<td><input name='userfile' type='file'></td>
			<td class='back'><input type='submit' value='".t("L‰het‰")."'></td>
		</tr>
		</table>
		</form>";	

require ("inc/footer.inc");
