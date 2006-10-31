<?php

if ($tee == "file") {
	if (is_uploaded_file($_FILES['userfile']['tmp_name']) == TRUE) {
		$timeparts = explode(" ",microtime());
		$starttime = $timeparts[1].substr($timeparts[0],1);

		list($name,$ext) = split("\.", $_FILES['userfile']['name']);

		if (!(strtoupper($ext) == "TXT" || strtoupper($ext) == "CSV")) {
			die ("<font class='error'><br>".t("Ainoastaa .txt tai .csv tiedostot sallittuja")."!</font>");
		}

		if ($_FILES['userfile']['size']==0) {
			die ("<font class='error'><br>".t("Tiedosto oli tyhjä")."!</font>");
		}

		$file=fopen($_FILES['userfile']['tmp_name'],"r") or die (t("Tiedoston avaus epäonnistui")."!");

		// luetaan tiedosto alusta loppuun...
		$rivi = fgets($file, 4096);

		while (!feof($file)) {

			$tila			= '';
			$valinta		= '';
			$varaosavirhe 	= '';
			$tuoteerror 	= 0;

			// luetaan rivi tiedostosta..
			$poista	  	= array("\"", "'", "\\", " ");
			$rivi	  	= str_replace($poista,"",$rivi);
			$rivi	  	= explode("\t", trim($rivi));

			$tuoteno	= trim($rivi[0]);
			$kpl  		= (float) str_replace(",", ".", $rivi[1]);

			$toimaika 	= $rivi[2];

			if (trim($toimaika) == "") {
				$toimaika = $laskurow["toimaika"];
			}

			$hinta 	  = (float) str_replace(",", ".", $rivi[3]);
			$ale	  = (float) str_replace(",", ".", $rivi[4]);
			$netto 	  = $rivi[5];

			if ($tuoteno != '' and $kpl != 0) {

				///* Toimittajan tuotenumerospecial*///
				if (substr($tuoteno,0,1) == '?') {
					$query = "	SELECT *
								FROM tuotteen_toimittajat
								JOIN tuote USING (yhtio, tuoteno)
								WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
								and tuotteen_toimittajat.toim_tuoteno='".substr($tuoteno,1)."'";
				}
				else {
					$query = "	SELECT *, tuote.tuoteno as tuoteno
								FROM tuote
								LEFT JOIN tuotepaikat ON tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.oletus!=''
								WHERE tuote.yhtio='$kukarow[yhtio]'
								and tuote.tuoteno='$tuoteno'";
				}
				$result = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result) == 1) {

					$trow = mysql_fetch_array($result);
					$tuoteno = $trow["tuoteno"];

					require('lisaarivi.inc');
				}
				else {
					echo "<font class='message'>".t("Tuotenumeroa")." $tuoteno ".t("ei löydy")."!</font><br>";
				}
			}

			$tuoteno	= '';
			$kpl		= '';
			$hinta		= '';
			$ale		= '';
			$alv		= '';
			$var		= '';
			$toimaika	= '';
			$kommentti	= '';
			$rivitunnus = '';

			$rivi = fgets($file, 4096);
		} // end while eof

		fclose($file);

		echo "<br><br>";
		$tee = "Y";
	}
	else {
		$tee = "Y";
	}
}

if ($tee == 'mikrotila') {
	echo "<font class='head'>".t("Ostotilaus").":</font><hr><br>";

	echo "<form method='post' name='sendfile' enctype='multipart/form-data' action='$PHP_SELF'>
			<input type='hidden' name='tilausnumero' value='$tilausnumero'>
			<input type='hidden' name='tee' value='file'>

			<font class='message'>".t("Tiedostomuoto").":</font><br><br>

			<table border='0' cellpadding='3' cellspacing='2'>
			<tr><th colspan='7'>".t("Tabulaattorilla eroteltu tekstitiedosto").".</th></tr>
			<tr><td>".t("Tuoteno")."</td><td>".t("Kpl")."</td><td>".t("Arvioitu toimituspäivä vvvv-kk-pp")."</td><td>".t("Hinta")."</td><td>".t("Ale")."</td></tr>
			</table>

			<br>
			<table>
			<tr>";
	echo "
			<th>".t("Valitse tiedosto").":</th>
			<td><input name='userfile' type='file'></td>
			<td class='back'><input type='submit' value='".t("Läheta")."'></td>
			</tr>
			</table>
			</form>";
}
?>