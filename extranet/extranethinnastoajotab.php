<?php

	//* T�m� skripti k�ytt�� slave-tietokantapalvelinta *//
	$useslave = 1;

	require('parametrit.inc');

	echo "<font class='head'>".t("Hinnastoajo").":</font><hr>";

	//Haetaan asiakkaan tunnuksella
	$query  = "	SELECT *
				FROM asiakas
				WHERE yhtio='$kukarow[yhtio]' and tunnus='$kukarow[oletus_asiakas]'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 1) {
		$asiakas = mysql_fetch_array($result);
		$ytunnus = $asiakas["ytunnus"];
	}
	else {
		echo t("VIRHE: K�ytt�j�tiedoissasi on virhe! Ota yhteys j�rjestelm�n yll�pit�j��n.")."<br><br>";
		exit;
	}

	if ($tee != '') {

		flush();
		//kirjoitetaan pdf faili levylle..

		$filenimi = "$kukarow[yhtio]-hinnasto-$ytunnus-".date('Ymd_His').".txt";

		if (!$fh = fopen("/tmp/".$filenimi, "w+")) {
			die("filen luonti ep�onnistui!");
		}
		$lisa = '';

		if ($tuoteosasto != 'kaikki') {
			$lisa = "and a.osasto = '$tuoteosasto'";
		}

		if ($tuoteryhma != 'kaikki') {
			$lisa = "and a.try = '$tuoteryhma'";
		}

		$rivi = '';
		$query = "	select a.tuoteno, a.nimitys, a.lyhytkuvaus, a.tuotemerkki, a.myyntihinta hinta_veroll, a.alv,
					if(b.alennus is null,'0,00', alennus) 'alepros', a.aleryhma
					from tuote a
					join asiakas c on a.yhtio = c.yhtio and c.ytunnus = '$ytunnus'
					left join asiakasalennus b on a.yhtio = b.yhtio and a.aleryhma = b.ryhma and b.ytunnus = c.ytunnus
					where a.yhtio = '$kukarow[yhtio]' and a.status in ('','a') and hinnastoon != 'E'
					$lisa
					order by 1";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			$rivi .= "".t("Valitettavasti ei l�ytynyt yht��n tuotetta.")."\t";
		}

		$rivi .= "".t("Tuotenumero")."\t";
		$rivi .= "".t("Nimitys")."\t";
		$rivi .= "".t("Kuvaus")."\t";
		$rivi .= "".t("tuotemerkki")."\t";
		$rivi .= "".t("Hinta_veroll")."\t";
		$rivi .= "".t("alv")."\t";
		$rivi .= "".t("Aleprosentti")."\t";
		$rivi .= "".t("Aleryhm�")."\t\n";

		while ($tuoterow = mysql_fetch_array($result)) {

			$rivi .= "$tuoterow[tuoteno]\t";
			$rivi .= t_tuotteen_avainsanat($tuoterow, 'nimitys')."\t";
			$rivi .= "$tuoterow[lyhytkuvaus]\t";
			$rivi .= "$tuoterow[tuotemerkki]\t";
			$rivi .= str_replace(".",",",$tuoterow['hinta_veroll'])."\t";
			$rivi .= str_replace(".",",",$tuoterow['alv'])."\t";
			$rivi .= str_replace(".",",",$tuoterow['alepros'])."\t";
			$rivi .= "$tuoterow[aleryhma]\t\n";

			fwrite($fh, $rivi);
			$rivi = '';
		}
		fclose($fh);//pakataan faili
		$zipname = t("hinnasto")."-$kukarow[yhtio]-$ytunnus-".date('Ymd_His').".zip";
		$cmd = "cd /tmp/;/usr/bin/zip "."$zipname $filenimi";
		$palautus = exec($cmd);

		system("cd /tmp/;rm -f ".$filenimi);

		$filename = "/tmp/"."$zipname";
		$handle = fopen($filename, "r");
		$contents = fread($handle, filesize($filename));
		fclose($handle);

		$filenimi = "/tmp/".t("Hinnasto")."-$kukarow[yhtio]-$ytunnus-".date('Ymd_His').".zip";

		echo t("L�hetet��n s�hk�posti osoitteeseen").": ";
		flush();

		$timeparts = explode(" ",microtime());
		$endtime   = $timeparts[1].substr($timeparts[0],1);
		$total     = round($endtime-$starttime,0);


		$bound = uniqid(time()."_") ;

		$header  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n";
		$header .= "MIME-Version: 1.0\n" ;
		$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

		$content = "--$bound\n" ;

		$content .= "Content-Type: application/pgp-encrypted;\n" ;
		$content .= "Content-Transfer-Encoding: base64\n" ;
		$content .= "Content-Disposition: attachment; filename=\"".t("Hinnasto")."-$kukarow[yhtio]-$ytunnus-".date('Ymd_His').".zip\"\n\n";
		$content .= chunk_split(base64_encode($contents));
		$content .= "\n" ;

		$content .= "--$bound\n" ;

		$boob = mail($kukarow["eposti"], mb_encode_mimeheader(t("Hinnasto")."-$kukarow[yhtio]-$ytunnus-".date('Ymd_His'), "ISO-8859-1", "Q"), $content, $header, "-f $yhtiorow[postittaja_email]");

		if ($boob===FALSE) echo " - ".t("S�hk�postin l�hetys ep�onnistui")."!<br>";
		else echo " $kukarow[eposti].<br>".t("S�hk�posti l�hetetty").".<br>";
		echo "<br>".t("Sinulle tulee liitteen� pakattu ZIP tiedosto josta saat purettua sarkaimilla erotellun tekstitiedoston").". <br>";
		echo "".t("Huomaa ett� jos avaat tiedoston Exceliin, sinun pit�� m��ritell� ensimm�inen sarake tekstiksi jotta Excel ei poista etunollia").". <br>";

		//lopetetaan t�h�n
		require ("footer.inc");
		exit;
	}

	//K�ytt�liittym�
	echo "<br>".t("Voit rajata hinnastoa joko valitsemalla yhden tuoteosaston, tai yhden tuoteryhm�n. <br>Tai jos et tee mit��n valintaa ajetaan hinnasto kaikista tuotteista").".<br>";
	echo "<br>".t("Aja hinnasto painamalla L�het� nappia. Hinnasto l�hetet��n s�hk�postilla liitetiedostona").".";
	echo "<br><br>";
	echo "<table><form method='post' action='$PHP_SELF'>";

	echo "<input type='hidden' name='tee' value='kaikki'>";

	echo "<tr>";
	echo "<th>".t("Valitse tuoteosasto")."</th>";

	// tehd��n avainsana query
	$result = t_avainsana("OSASTO");

	echo "<td><select name='tuoteosasto'>";
	echo "<option value='kaikki' $tuoteosastosel>".t("Kaikki")."</option>";

	while ($row = mysql_fetch_array($result)) {
		if ($tuoteosasto == $row["selite"]) $sel = "selected";
		else $sel = "";
		echo "<option value='$row[selite]' $sel>$row[selite] $row[selitetark]</option>";
	}

	echo "</select></td></tr>";
	echo "<tr><th>".t("tai tuoteryhm�")."</th>";

	// tehd��n avainsana query
	$result = t_avainsana("TRY");

	echo "<td><select name='tuoteryhma'>";
	echo "<option value='kaikki' $tuoteryhmasel>".t("Kaikki")."</option>";

	while ($row = mysql_fetch_array($result)) {
		if ($tuoteryhma == $row["selite"]) $sel = "selected";
		else $sel = "";
		echo "<option value='$row[selite]' $sel>$row[selite] $row[selitetark]</option>";
	}

	echo "</select></td>";
	echo "</tr>";

	echo "<tr><td class='back'><br><input type='submit' value='".t("L�het�")."'></td></tr></form>";

	echo "</table>	";

	require ("footer.inc");
?>