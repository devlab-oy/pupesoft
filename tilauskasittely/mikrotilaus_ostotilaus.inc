<?php
	if ($tee == "file") {
		if (is_uploaded_file($_FILES['userfile']['tmp_name']) == TRUE) {
			$timeparts = explode(" ",microtime());
			$starttime = $timeparts[1].substr($timeparts[0],1);

			list($name,$ext) = split("\.", $_FILES['userfile']['name']);

			if (strtoupper($ext) !="TXT" and strtoupper($ext)!="XLS" and strtoupper($ext)!="CSV") {
				die ("<font class='error'><br>".t("Ainoastaan .xls, .txt ja .cvs tiedostot sallittuja")."!</font>");
			}

			if ($_FILES['userfile']['size']==0) {
				die ("<font class='error'><br>".t("Tiedosto oli tyhjä")."!</font>");
			}

			if (strtoupper($ext)=="XLS") {
				require_once ('excel_reader/reader.php');

				// ExcelFile
				$data = new Spreadsheet_Excel_Reader();

				// Set output Encoding.
				$data->setOutputEncoding('CP1251');
				$data->setRowColOffset(0);
				$data->read($_FILES['userfile']['tmp_name']);
			}
			else {
				$file = fopen($_FILES['userfile']['tmp_name'],"r") or die (t("Tiedoston avaus epäonnistui")."!");
			}

			// luetaan tiedosto loppuun ja tehdään array koko datasta
			$excelrivi[][] = array();

			if (strtoupper($ext) == "XLS") {
				for ($excei = 0; $excei < $data->sheets[0]['numRows']; $excei++) {
					for ($excej = 0; $excej < $data->sheets[0]['numCols']; $excej++) {
						$excelrivi[$excei-1][$excej] = $data->sheets[0]['cells'][$excei][$excej];
					}
				}
			}
			else {
				$excei = 0;

				while ($rivi = fgets($file)) {
					// luetaan rivi tiedostosta..
					$poista	 = array("'", "\\");
					$rivi	 = str_replace($poista,"",$rivi);
					$rivi	 = explode("\t", trim($rivi));

					$excej = 0;
					foreach ($rivi as $riv) {
						$excelrivi[$excei][$excej] = $riv;
						$excej++;
					}
					$excei++;
				}
				fclose($file);
			}

			foreach ($excelrivi as $rivi) {

				$tila			= '';
				$valinta		= '';
				$varaosavirhe 	= '';
				$tuoteerror 	= 0;

				$tuoteno	= trim($rivi[0]);
				$kpl  		= (float) str_replace(",", ".", $rivi[1]);

				$toimaika 	= $rivi[2];

				if (trim($toimaika) == "") {
					$toimaika = $laskurow["toimaika"];
				}

				if (trim($rivi[3]) != '') {
					$hinta = (float) str_replace(",", ".", $rivi[3]);
				}
				else {
					$hinta = "";
				}

				if (trim($rivi[4]) != '') {
					$ale = (float) str_replace(",", ".", $rivi[4]);
				}
				else {
					$ale = "";
				}

				$paikka	  = $rivi[5];

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

			}

			echo "<br><br>";
			$tee = "Y";
			$ei_aikatarkistusta = "JOO";
		}
		else {
			$tee = "Y";
		}
	}

	if ($tee == 'mikrotila') {
		echo "<font class='head'>".t("Ostotilaus").":</font><hr><br>";

		echo "<form method='post' name='sendfile' enctype='multipart/form-data' action='$PHP_SELF'>
				<input type='hidden' name='tilausnumero' value='$tilausnumero'>
				<input type='hidden' name='lopetus' value='$lopetus'>
				<input type='hidden' name='tee' value='file'>
				<input type='hidden' name='ei_aikatarkistusta' value='$ei_aikatarkistusta'>

				<font class='message'>".t("Tiedostomuoto").":</font><br><br>

				<table>
				<tr><th colspan='7'>".t("Sarkaineroteltu tekstitiedosto tai Excel-tiedosto").".</th></tr>
				<tr><td>".t("Tuoteno")."</td><td>".t("Määrä")."</td><td>".t("Arvioitu toimituspäivä vvvv-kk-pp")."</td><td>".t("Hinta")."</td><td>".t("Ale")."</td><td>".t("Paikka (#-eroteltu)")."</td></tr>
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