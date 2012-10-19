<?php
	if ($tee == "file") {
		if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {
			$timeparts = explode(" ",microtime());
			$starttime = $timeparts[1].substr($timeparts[0],1);

			$path_parts = pathinfo($_FILES['userfile']['name']);
			$name	= strtoupper($path_parts['filename']);
			$ext	= strtoupper($path_parts['extension']);

			if ($ext != "TXT" and $ext != "CSV" and $ext != "XLS") {
				die ("<font class='error'><br>".t("Ainoastaan .txt, .csv tai .xls tiedostot sallittuja")."!</font>");
			}

			if ($_FILES['userfile']['size']==0) {
				die ("<font class='error'><br>".t("Tiedosto on tyhjä")."!</font>");
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
			$excelrivi = array();

			if (strtoupper($ext) == "XLS") {
				for ($excei = 0; $excei < $data->sheets[0]['numRows']; $excei++) {
					for ($excej = 0; $excej < $data->sheets[0]['numCols']; $excej++) {
						$excelrivi[$excei][$excej] = $data->sheets[0]['cells'][$excei][$excej];
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

			$mtrivilask = 0;

			foreach ($excelrivi as $rivi) {

				$tuoteno  	= trim($rivi[0]);
				$kpl  		= str_replace(",", ".", $rivi[1]);
				$kommentti	= $rivi[2];
				$paikka		= $rivi[3];

				if (strtoupper($kpl) == "X") {
					//tilataan kaikki paikalla olevat, ei kuitenkaan varata niitä vielä
					$kpl  = 9999.99;
					$varataan_saldoa = "EI";
				}

				if ($tuoteno != '' and $kpl > 0) {
					$query = "	SELECT *
								FROM tuote
								WHERE tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
					$result = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($result) == 1) {

						$mtrivilask++;

						$trow = mysql_fetch_array($result);

						$tuoteno 	= $trow["tuoteno"];
						$toimaika 	= $laskurow["toimaika"];
						$kerayspvm 	= $laskurow["kerayspvm"];
						$varasto 	= $laskurow["varasto"];
						$jtkielto 	= $laskurow['jtkielto'];

						require('lisaarivi.inc');

						$tuoteno	= '';
						$kpl		= '';
						$var		= '';
						$hinta		= '';
						$netto		= '';
						$rivitunnus	= '';
						$kommentti	= '';
						$kerayspvm	= '';
						$toimaika	= '';
						$paikka		= '';
						$alv		= 'X';

						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							${'ale'.$alepostfix} = '';
						}
					}
					else {
						echo "<font class='message'>".t("Tuotenumeroa")." $tuoteno ".t("ei löydy")."!</font><br>";
					}
				}

				if ((int) $olliriveja > 0 and $mtrivilask >= (int) $olliriveja) {

					$query = "	UPDATE lasku
								SET alatila = 'J'
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus = '$laskurow[tunnus]'
								and tila='G'";
					$updresult = mysql_query($query) or pupe_error($query);

					$mtrivilask = 0;

					$jatka		= "kala";

					$query = "	UPDATE kuka SET kesken = 0 WHERE yhtio = '$kukarow[yhtio]' and kuka = '$kukarow[kuka]'";
					$delresult = mysql_query($query) or pupe_error($query);

					$kukarow["kesken"] = 0;

					$tilausnumero 	= 0;
					$clearing 		= $laskurow["clearing"];
					$toimpp 		= substr($laskurow["toimaika"],  8, 2);
					$kerpp 			= substr($laskurow["kerayspvm"], 8, 2);
					$toimkk 		= substr($laskurow["toimaika"],  5, 2);
					$kerkk 			= substr($laskurow["kerayspvm"], 5, 2);
					$toimvv 		= substr($laskurow["toimaika"],  0, 4);
					$kervv 			= substr($laskurow["kerayspvm"], 0 ,4);
					$comments 		= $laskurow["comments"];
					$viesti 		= $laskurow["viesti"];
					$varasto 		= $laskurow["varasto"];
					$toim 			= "SIIRTOLISTA";

					require ("otsik_siirtolista.inc");

					$query = "	SELECT *
								FROM lasku
								WHERE tunnus = '$kukarow[kesken]'";
					$aresult = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($aresult) == 0) {
						echo "<font class='message'>".t("VIRHE: Tilausta ei löydy")."!<br><br></font>";
						exit;
					}

					$query = "SELECT nimitys from varastopaikat where yhtio='$kukarow[yhtio]' and tunnus = '$varasto' AND varasto_status != 'P'";
					$varres = mysql_query($query) or pupe_error($query);
					$varrow = mysql_fetch_array($varres);

					echo "<br><font class='message'>".t("Tehtiin uusi siirtolista! Siirtolistalle otsikko %s lähdevarasto on %s", $kieli, $kukarow["kesken"], $varrow["nimitys"])."</font><br>";

					$laskurow = mysql_fetch_array($aresult);
				}
			}

			$tuoteno	= '';
			$kpl		= '';
			$var		= '';
			$hinta		= '';
			$netto		= '';
			$rivitunnus	= '';
			$kommentti	= '';
			$kerayspvm	= '';
			$toimaika	= '';
			$paikka		= '';
			$alv		= 'X';
			$tee = "Y";

			for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
				${'ale'.$alepostfix} = '';
			}
		}
		else {
			$tee = "Y";
		}
	}

	if ($tee == 'mikrotila') {

		echo "<font class='head'>".t("Siirtolista").":</font><hr><br>";

		echo "<form method='post' name='sendfile' enctype='multipart/form-data'>
				<input type='hidden' name='tilausnumero' value='$tilausnumero'>
				<input type='hidden' name='tee' value='file'>
				<input type='hidden' name='toim' value='$toim'>
				<input type='hidden' name='lopetus' value='$lopetus'>
				<input type='hidden' name='taalta' value='$laskurow[varasto]'>
				<font class='message'>".t("Tiedostomuoto").":</font><br><br>

				<table>
				<tr><th colspan='4'>".t("Sarkaineroteltu tekstitiedosto tai Excel-tiedosto").".</th></tr>
				<tr><td>".t("Tuoteno")."</td><td>".t("Määrä")."*</td><td>".t("Kommentti")."</td><td>".t("Lähettävä varastopaikka (#!¡!#-eroteltu)")."</td></tr>
				</table>
				<br>
				* ".t("Siirrä kaikki vapaana olevat laittamalla määrä-kenttään X")."
				<br><br>
				<table>";

		echo "<tr><th>".t("Rivejä per siirtolista").":</th>
				<td>
				<select name='olliriveja'>
				<option value=''>".t("Kaikki rivit nykyiselle siirtolistalle")."</option>
				<option value='25'>25 ".t("riviä per siirtolista")."</option>
				<option value='50'>50 ".t("riviä per siirtolista")."</option>
				<option value='75'>75 ".t("riviä per siirtolista")."</option>
				<option value='100'>100 ".t("riviä per siirtolista")."</option>
				</select>
				</td></tr>";

		echo "<tr>
				<th>".t("Valitse tiedosto").":</th>
				<td><input name='userfile' type='file'></td>
				<td class='back'><input type='submit' value='".t("Läheta")."'></td>
				</tr>
				</table>
				</form>";
	}
?>