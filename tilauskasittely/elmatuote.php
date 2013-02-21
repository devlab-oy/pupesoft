<?php

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
$useslave = 1;

// Kutsutaanko CLI:st‰
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
	$php_cli = TRUE;
}

// jos ollaan saatu komentorivilt‰ parametri
if ($php_cli) {

	// otetaan tietokanta connect
	require ("../inc/connect.inc");
	require ("../inc/functions.inc");

	// hmm.. j‰nn‰‰
	$kukarow['yhtio']=$argv[1];

	$query    = "SELECT * from yhtio where yhtio='$kukarow[yhtio]'";
	$yhtiores = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($yhtiores)==1) {
		$yhtiorow = mysql_fetch_array($yhtiores);
		$aja='run';
	}
	else {
		die ("Yhtiˆ $kukarow[yhtio] ei lˆydy!");
	}
}
else {
	require ("../inc/parametrit.inc");
}

$echoulos = "<font class='head'>Elmatuote</font><hr>";

if ($aja=='run') {

	flush();

	// tehd‰‰n temppitiedosto
	$elma  = "../dataout/elmatuot-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true)).".txt";
	if (!$handle = fopen($elma, "w")) die("Filen $elma luonti ep‰onnistui!");

	// itte query
	$query = "	SELECT *
				FROM tuote
				WHERE yhtio = '$kukarow[yhtio]'
				AND hinnastoon != 'E'
				AND tuotetyyppi NOT IN ('A', 'B')
				AND status NOT IN ('P','X')";
	$res   = mysql_query($query) or pupe_error($query);

	$echoulos .= "<font class='message'>K‰sitell‰‰n tuotteita (".mysql_num_rows($res)." kpl)...<br>";

	// arvioidaan kestoa
	$arvio     = array();
	$timeparts = explode(" ",microtime());
	$alkuaika  = $timeparts[1].substr($timeparts[0],1);
	$joukko    = 100; //kuinka monta rivi‰ otetaan keskiarvoon

	while ($row = mysql_fetch_array($res))
	{
		// muutetaan yksikˆt ISO-standardin mukaisiksi
		$yksikko="";
		if ($row['yksikko']=='KPL' or $row['yksikko']=='PCE')
			$yksikko = "PCE";
		if ($row['yksikko']=='SRJ')
			$yksikko = "SET";
		if ($row['yksikko']=='PAR')
			$yksikko = "PAR";

		// haetaan oletus varastopaikka
		$query  = "select * from tuotepaikat where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]' and oletus='X'";
		$kores  = mysql_query($query) or pupe_error($query);
		$korow  = mysql_fetch_array($kores);

		$hyllyalue = $korow['hyllyalue'];
		$hyllynro  = $korow['hyllynro'];
		$hyllyvali = $korow['hyllyvali'];
		$hyllytaso = $korow['hyllytaso'];

		// asiakkaiden j‰rjestelm‰t on paskoja
		if ($row['eankoodi'] == 0) {
			$row['eankoodi'] = "";
		}

		// katotaan paljon myyt‰viss‰
		list( , , $saldo) = saldo_myytavissa($row["tuoteno"]);

		if ($saldo > 1) $saldo = 1;
		if ($saldo < 1) $saldo = 0;

		// tehd‰‰n tietuetta
		$ulos   = sprintf("%-20.20s",$row['tuoteno']);
		$ulos  .= sprintf("%-2.2s" ,$row['osasto']);
		$ulos  .= sprintf("%-5.5s" ,$row['try']);
		$ulos  .= sprintf("%-15.15s",$row['aleryhma']);
		$ulos  .= sprintf("%-50.50s",$row['nimitys']);
		$ulos  .= sprintf("%-10.10s",$row['myyntihinta']);
		$ulos  .= sprintf("%-10.10s",$saldo);
		$ulos  .= sprintf("%-1.1s" ,$hyllyalue);
		$ulos  .= sprintf("%-2.2s" ,$hyllynro);
		$ulos  .= sprintf("%-2.2s" ,$hyllyvali);
		$ulos  .= sprintf("%-2.2s" ,$hyllytaso);
		$ulos  .= sprintf("%-3.3s" ,$yksikko);
		$ulos  .= sprintf("%-7.7s" ,$row['myynti_era']);
		$ulos  .= sprintf("%-13.13s",$row['eankoodi']);

/* vanha kuvaus
		$ulos   = sprintf("%-12.12s",$row['tuoteno']);
		$ulos  .= sprintf("%01.1s"  ,$row['osasto']);
		$ulos  .= sprintf("%03.3s"  ,$row['try']);
		$ulos  .= sprintf("%02.2s"  ,$row['aleryhma']);
		$ulos  .= sprintf("%-30.30s",$row['nimitys']);
		$ulos  .= sprintf("%010.10s",$row['myyntihinta']*100);
		$ulos  .= sprintf("%010.10s",$saldo*100);
		$ulos  .= sprintf("%1.1s"   ,$hyllyalue);
		$ulos  .= sprintf("%02.2s"  ,$hyllynro);
		$ulos  .= sprintf("%02.2s"  ,$hyllyvali);
		$ulos  .= sprintf("%02.2s"  ,$hyllytaso);
		$ulos  .= sprintf("%-3.3s"  ,$yksikko);
		$ulos  .= sprintf("%05.5s"  ,$row['osto_era']*100);
		$ulos  .= sprintf("%013.13s",$row['eankoodi']);
*/
		// haetaan korvaavia tuotteita
		$query  = "select * from korvaavat where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]'";
		$kores  = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($kores)>0)
		{
			$korow  = mysql_fetch_array($kores);
			$query  = "select * from korvaavat where yhtio='$kukarow[yhtio]' and id='$korow[id]' order by jarjestys, tuoteno";
			$kores  = mysql_query($query) or pupe_error($query);
			$nexti  = 0;

			while ($korow = mysql_fetch_array($kores))
			{
				if ($nexti == 1) {
					// vanha
					// $ulos .= sprintf("%-12.12s",$korow['tuoteno']);
					// uusi
					$ulos .= sprintf("%-20.20s",$korow['tuoteno']);
					break;
				}
				if ($korow['tuoteno'] == $row['tuoteno']) {
					$nexti = 1; // meid‰n tulee ottaa seuraava tuote, koska se on t‰m‰n tuotteen j‰lkeen seuraava korvaava
				}
			}
		}
		$ulos  .= "\n";

		// kirjotetaan tietue failiin
		if (fwrite($handle, $ulos) === FALSE) die("failin kirjoitus ep‰onnistui");

	}

	// faili kiinni
	fclose($handle);

	$timeparts = explode(" ",microtime());
	$endtime   = $timeparts[1].substr($timeparts[0], 1);
	$aika      = round($endtime-$starttime, 4);

	$echoulos .= "<font class='message'>Kesto $aika sec.</font><br>";
	$echoulos .= "<font class='message'>L‰hetet‰‰n tiedosto Elmaan...</font>";

	//pakataan faili
	#$cmd = "/usr/bin/bzip2 $elma";
	#$palautus = exec($cmd);

	// tarvitaan  $ftphost $ftpuser $ftppass $ftppath $ftpfile
	// palautetaan $palautus ja $syy

	$ftphost = $elmatuotehost;
	$ftpuser = $elmatuoteuser;
	$ftppass = $elmatuotepass;
	$ftppath = $elmatuotepath;
	#$ftpfile = realpath($elma.".bz2");
	$ftpfile = realpath($elma);

	require ("../inc/ftp-send.inc");

	//L‰hetet‰‰n tiedosto asiakkaille suoraan jotka haluavat sen ilman Elmaa
	$echoulos .= "<font class='message'>L‰hetet‰‰n tiedosto Asiakkaille...</font><br>";

	$query  = "select * from asiakas where yhtio='$kukarow[yhtio]' and fakta like '%ELMATUOTE-SƒHK÷POSTILLA%' and email!=''";
	$kores  = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($kores) > 0) {

		$elmazip = substr(substr($elma,0,-4).".zip",11);
		$cmd = "/usr/bin/zip -j /tmp/$elmazip $elma";
		$palautus = exec($cmd);

		$handle  = fopen("/tmp/$elmazip", "r");
		$sisalto = fread($handle, filesize("/tmp/$elmazip"));
		fclose($handle);

		system("rm -f /tmp/$elmazip");

		while ($korow = mysql_fetch_array($kores)) {
			// l‰hetet‰‰n yhteenvetomeili
			$bound = uniqid(time()."_") ;
			$header  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n";
			$header .= "MIME-Version: 1.0\n" ;
			$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

			$mail  = "Varastotilanne\n";
			$mail .= "--$bound\n";
			$mail .= "Content-Type: application/zip; name=\"varastotilanne.zip\"\n" ;
			$mail .= "Content-Transfer-Encoding: base64\n" ;
			$mail .= "Content-Disposition: attachment; filename=\"varastotilanne.zip\"\n\n";
			$mail .= chunk_split(base64_encode($sisalto));
			$mail .= "\n" ;

			$boob = mail($korow["email"], mb_encode_mimeheader("Varastotilanne - $yhtiorow[nimi]", "ISO-8859-1", "Q"), $mail, $header, "-f $yhtiorow[postittaja_email]");
			if ($boob === FALSE) $echoulos .= "S‰hkˆpostin l‰hetys ep‰onnistui<br>";
		}
	}

	if ($palautus == 0)
		$echoulos .= "<font class='message'>Valmis.</font><br><br>";

	// komentorivilt‰
	if ($php_cli) {
		$echoulos = strip_tags($echoulos, "<br>");
		$echoulos = str_replace("<br>", "\n", $echoulos);
		echo "$echoulos\n\n";
	}
	else {
		echo "$echoulos";
		require ("../inc/footer.inc");
	}

}
else {
	echo "<br><form method='post'>";
	echo "<input type='hidden' name='aja' value='run'>";
	echo "<input type='submit' value='Aja Elmatuote!'>";
	echo "</form>";

	require ("../inc/footer.inc");
}

?>