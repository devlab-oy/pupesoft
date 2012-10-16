<?php

if ($tee == "file") {
	if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {
		$timeparts = explode(" ",microtime());
		$starttime = $timeparts[1].substr($timeparts[0],1);

		$path_parts = pathinfo($_FILES['userfile']['name']);
		$name	= strtoupper($path_parts['filename']);
		$ext	= strtoupper($path_parts['extension']);

		if ($ext != "TXT" and $ext != "CSV") {
			die ("<font class='error'><br>".t("Ainoastaan .txt ja .cvs tiedostot sallittuja")."!</font>");
		}

		if ($_FILES['userfile']['size']==0) {
			die ("<font class='error'><br>".t("Tiedosto on tyhjä")."!</font>");
		}

		$file = fopen($_FILES['userfile']['tmp_name'],"r") or die (t("Tiedoston avaus epäonnistui")."!");

		$tunnukset 		= "";
		$korjaaalvit 	= array();
		$monistettavat	= array();
		$suoraanlasku 	= array();
		$korjaarahdit 	= array();

		while ($rivi = fgets($file, 4096)) {

			$tila='';
			$valinta='';

			// luetaan rivi tiedostosta..
			$poista	  	= array("'", "\\", " ");
			$rivi	  	= str_replace($poista,"",$rivi);
			$rivi	  	= explode("\t", trim($rivi));

			$lasku = trim($rivi[0]);
			$alvik = trim($rivi[1]);
			$hyvit = trim($rivi[2]);
			$slask = trim($rivi[3]);
			$krahd = trim($rivi[4]);

			$query = "	SELECT tunnus
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						and laskunro= '$lasku'
						and tila	= 'U'
						and alatila	= 'X'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 1) {

				$row = mysql_fetch_array($result);

				$tunnukset .= "$row[tunnus],";

				if ($hyvit != '') {
					$monistettavat[$row["tunnus"]] = "HYVITA";
				}
				else {
					$monistettavat[$row["tunnus"]] = "MONISTA";
				}

				$korjaaalvit[$row["tunnus"]]  = $alvik;
				$suoraanlasku[$row["tunnus"]] = $slask;
				$korjaarahdit[$row["tunnus"]] = $krahd;
			}
		}

		$tunnukset	= substr($tunnukset,0,-1);
		$lasku 		= "";
		$alvik 		= "";
		$hyvit 		= "";
		$slask 		= "";
		$krahd		= "";

		fclose($file);
	}

	$tee = "ETSILASKU";
}

if ($tee == 'mikrotila') {
	echo "<font class='head'>".t("Lue monistettavia laskuja tiedostosta")."</font><hr>";

	echo "	<form method='post' name='sendfile' enctype='multipart/form-data'>
			<input type='hidden' name='tee' value='file'>
			<font class='message'>".t("Tiedostomuoto").":</font><br><br>
			<table>
			<tr><th colspan='7'>".t("Sarkaineroteltu tekstitiedosto").".</th></tr>
			<tr>";

	echo "	<td>".t("Laskunro")."</td><td>".t("Korjaa alvit")."</td><td>".t("Hyvitä")."</td><td>".t("Suoraan laskutukseen")."</td><td>".t("Laske rahtiveloitus uudestaan")."</td>";

	echo "	</tr>
			</table>
			<br>
			<table>
			<tr>";

	echo "	<th>".t("Valitse tiedosto").":</th>
			<td><input name='userfile' type='file'></td>
			<td class='back'><input type='submit' value='".t("Läheta")."'></td>
			</tr>
			</table>
			</form>";
}
