<?php

if ($tee == "file") {
	if (is_uploaded_file($_FILES['userfile']['tmp_name']) == TRUE or substr($_FILES['userfile']['tmp_name'],5,11) == "MiKrOtIlAuS") {
		$timeparts = explode(" ",microtime());
		$starttime = $timeparts[1].substr($timeparts[0],1);

		list($name,$ext) = split("\.", $_FILES['userfile']['name']);

		if (!(strtoupper($ext) == "TXT" || strtoupper($ext) == "CSV")) {
			die ("<font class='error'><br>".t("Ainoastaa .txt tai .csv tiedostot sallittuja")."!</font>");
		}

		if ($_FILES['userfile']['size']==0) {
			die ("<font class='error'><br>".t("Tiedosto oli tyhj‰")."!</font>");
		}

		$file=fopen($_FILES['userfile']['tmp_name'],"r") or die (t("Tiedoston avaus ep‰onnistui")."!");

		//Myyd‰‰n vain tietyst‰ varastosta
		if ($laskurow["varasto"] != 0) {
			$varasto = (int) $laskurow["varasto"];
		}

		// luetaan tiedosto alusta loppuun...
		$rivi = fgets($file, 4096);

		while (!feof($file)) {

			$tila='';
			$valinta='';

			// luetaan rivi tiedostosta..
			$poista	  	= array("\"", "'", "\\", " ");
			$rivi	  	= str_replace($poista,"",$rivi);
			$rivi	  	= explode("\t", trim($rivi));

			$tuoteno	= trim($rivi[0]);
			$kpl	  	= str_replace(",", ".", $rivi[1]);
			$alv 		= "";

			if ($kukarow["extranet"] == "") {
				$toimaika 	= $rivi[2];
				$hinta		= str_replace(",", ".", $rivi[3]);
				$ale	  	= str_replace(",", ".", $rivi[4]);
				$netto 	  	= strtoupper(trim($rivi[5]));
				$var  		= strtoupper(trim($rivi[6]));

				if (($var!='') and ($var!='P') and ($var!='H') and ($var!='J')) {
					echo t("V‰‰r‰ arvo kent‰ss‰ 'var'! Sallitut arvot ovat: 'P', 'J' ja 'H'").".<br>";
					$var = '';
				}

				if ($netto != '' and $netto != 'N') {
					echo t("V‰‰r‰ arvo kent‰ss‰ netto! Sallitut arvot ovat: tyhj‰ tai N")."<br>";
					$netto = '';
				}
			}

			if($tuoteno != '' and $kpl != 0) {

				// Tuotehaku ei toimi extranetin puolella
				if (file_exists("../inc/tuotehaku.inc")) {
					require ("../inc/tuotehaku.inc");
				}

				$query = "	SELECT *
							FROM tuote
							WHERE tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
				$rarresult = mysql_query($query) or pupe_error($query);

				if(mysql_num_rows($rarresult) == 1) {

					$trow = mysql_fetch_array($rarresult);

					if ($toimaika == '') {
						$toimaika = $laskurow["toimaika"];
					}

					if ($hinta == 0) {
						$hinta = "";
					}

					if ($laskurow["tilaustyyppi"] == "E") {
						$varataan_saldoa = "EI";
					}

					//lis‰t‰‰n rivi
					require ("lisaarivi.inc");
				}
				else {
					echo "<font class='message'>".t("Tuotenumeroa")." $tuoteno ".t("ei lˆydy")."!</font><br>";
				}
			}
			$rivi = fgets($file, 4096);
		} // end while eof

		fclose($file);

		$tuoteno	= '';
		$kpl		= '';
		$var		= '';
		$hinta		= '';
		$netto		= '';
		$ale		= '';
		$rivitunnus	= '';
		$kommentti	= '';
		$kerayspvm	= '';
		$toimaika	= '';
		$paikka		= '';
		$alv		= '';
		$varasto 	= '';
		$tee 		= 'Y';
	}
	else {
		$tuoteno	= '';
		$kpl		= '';
		$var		= '';
		$hinta		= '';
		$netto		= '';
		$ale		= '';
		$rivitunnus	= '';
		$kommentti	= '';
		$kerayspvm	= '';
		$toimaika	= '';
		$paikka		= '';
		$alv		= '';
		$varasto 	= '';
		$tee 		= 'Y';
	}
}

if ($tee == 'mikrotila') {
	echo "<font class='head'>$otsikko</font><hr>";

	echo "<form method='post' name='sendfile' enctype='multipart/form-data' action='$PHP_SELF?toim=$toim'>
			<input type='hidden' name='tilausnumero' value='$tilausnumero'>
			<input type='hidden' name='tee' value='file'>

			<font class='message'>".t("Tiedostomuoto").":</font><br><br>

			<table border='0' cellpadding='3' cellspacing='2'>
			<tr><th colspan='7'>".t("Sarkaineroteltu tekstitiedosto").".</th></tr>
			<tr>";

	echo "	<td>".t("Tuoteno")."</td><td>".t("Kpl")."</td>";

	if ($kukarow["extranet"] == "") {
		echo "<td>".t("Arvioitu toimitusp‰iv‰ vvvv-kk-pp")."</td><td>".t("Hinta")."</td><td>".t("Ale")."</td><td>".t("Netto")."</td><td>".t("Var")."</td>";
	}

	echo "	</tr>
			</table>

			<br>
			<table>
			<tr>";

	echo "
			<th>".t("Valitse tiedosto").":</th>
			<td><input name='userfile' type='file'></td>
			<td class='back'><input type='submit' value='".t("L‰heta")."'></td>
			</tr>
			</table>
			</form>";
}
