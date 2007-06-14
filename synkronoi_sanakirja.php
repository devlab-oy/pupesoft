<?php

require ("inc/parametrit.inc");

echo "<font class='head'>".t("Sanakirjan synkronointi")."</font><hr>";

if ($oikeurow['paivitys'] != '1') { // Saako päivittää
	if ($uusi == 1) {
		echo "<b>".t("Sinulla ei ole oikeutta lisätä")."</b><br>";
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

if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {

	list($name,$ext) = split("\.", $_FILES['userfile']['name']);

	if (strtoupper($ext) !="TXT" and strtoupper($ext)!="XLS" and strtoupper($ext)!="CSV") {
		die ("<font class='error'><br>".t("Ainoastaan .txt ja .cvs tiedostot sallittuja")."!</font>");
	}

	if ($_FILES['userfile']['size']==0){
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

	echo "<font class='message'>".t("Tutkaillaan mitä olet lähettänyt").".<br></font>";

	// luetaan eka rivi tiedostosta..
	if (strtoupper($ext) == "XLS") {
		$otsikot = array();

		for ($excej = 0; $excej < $data->sheets[0]['numCols']; $excej++) {
			$otsikot[] = strtoupper(trim($data->sheets[0]['cells'][0][$excej]));
		}
	}
	else {
		$file	 = fopen($_FILES['userfile']['tmp_name'],"r") or die (t("Tiedoston avaus epäonnistui")."!");

		$rivi    = fgets($file);
		$otsikot = explode("\t", strtoupper(trim($rivi)));
	}

	if (count($otsikot) > 1) {
		$sync_otsikot = array();
		
		for ($i = 0; $i < count($otsikot); $i++) {
			
			$a = strtolower($otsikot[$i]);
			
			$sync_otsikot[$a] = $i;
		}
				
		if (isset($sync_otsikot["fi"])) {
			
			echo "<table>";
			
			if (strtoupper($ext) == "XLS") {
				for ($excei = 1; $excei < $data->sheets[0]['numRows']; $excei++) {
					
					if($data->sheets[0]['cells'][$excei][$sync_otsikot["fi"]] != "") {
						
						$sanakirjaquery  = "SELECT fi,se,en,de,dk FROM sanakirja WHERE fi = BINARY '".$data->sheets[0]['cells'][$excei][$sync_otsikot["fi"]]."'";
						$sanakirjaresult = mysql_query($sanakirjaquery, $link) or pupe_error($sanakirjaquery);
						
						if (mysql_num_rows($sanakirjaresult) > 0) {
							$sanakirjarow = mysql_fetch_array($sanakirjaresult);
							
							if($sanakirjarow["fi"] != '' or $data->sheets[0]['cells'][$excei][$sync_otsikot["fi"]] != "") echo "<tr><td>".$sanakirjarow["fi"]."</td><td>".$data->sheets[0]['cells'][$excei][$sync_otsikot["fi"]]."</td></tr>";
							if($sanakirjarow["se"] != '' or $data->sheets[0]['cells'][$excei][$sync_otsikot["se"]] != "") echo "<tr><td>".$sanakirjarow["se"]."</td><td>".$data->sheets[0]['cells'][$excei][$sync_otsikot["se"]]."</td></tr>";
							if($sanakirjarow["en"] != '' or $data->sheets[0]['cells'][$excei][$sync_otsikot["en"]] != "") echo "<tr><td>".$sanakirjarow["en"]."</td><td>".$data->sheets[0]['cells'][$excei][$sync_otsikot["en"]]."</td></tr>";
							if($sanakirjarow["de"] != '' or $data->sheets[0]['cells'][$excei][$sync_otsikot["de"]] != "") echo "<tr><td>".$sanakirjarow["de"]."</td><td>".$data->sheets[0]['cells'][$excei][$sync_otsikot["de"]]."</td></tr>";
							if($sanakirjarow["dk"] != '' or $data->sheets[0]['cells'][$excei][$sync_otsikot["dk"]] != "") echo "<tr><td>".$sanakirjarow["dk"]."</td><td>".$data->sheets[0]['cells'][$excei][$sync_otsikot["dk"]]."</td></tr>";
							
						}
						else {
							echo "<tr><td><font class='error'>Sanaa ei löydy sanakirjasta!</font></td><td>".$data->sheets[0]['cells'][$excei][$sync_otsikot["fi"]]."</td></tr>";
						}
					}
				}
			}
			else {
				$rivi = fgets($file);

				while (!feof($file)) {
					// luetaan rivi tiedostosta..
					$poista	 = array("'", "\\");
					$rivi	 = str_replace($poista,"",$rivi);
					$rivi	 = explode("\t", trim($rivi));

					// luetaan seuraava rivi failista
					$rivi = fgets($file);
				}
				fclose($file);
			}
			
			echo "</table>";
		}
	}
}
else {
	echo "	<form method='post' name='sendfile' enctype='multipart/form-data' action='$PHP_SELF'>
			<table>
			<tr><th>".t("Valitse tiedosto").":</th>
				<td><input name='userfile' type='file'></td>
				<td class='back'><input type='submit' value='".t("Lähetä")."'></td>
			</tr>
			</table>
			</form>";
}
	
require ("inc/footer.inc");
	
?>