<?php

	if (isset($formi) and isset($kentta)) {
		echo "<script LANGUAGE='JavaScript'>window.document.$formi.$kentta.focus();</script>";
	}

	$timeparts = explode(" ",microtime());
	$endtime   = $timeparts[1].substr($timeparts[0],1);
	$aika      = round($endtime-$starttime,4);

	$useslave  = 0;

	if ($verkkokauppa == "") {
		if (require("connect.inc")) {

			//	Tarkastetaan käsitelläänkö suorituskykyongelmia
			$query 	= "describe suorituskykyloki";
			$suores = mysql_query($query);

			if (mysql_error() == "") {
				$starttime = date("Y-m-d H:i:s", $starttime);
				$endtime = date("Y-m-d H:i:s", $endtime);

				$suoque = "	INSERT INTO suorituskykyloki
							SET yhtio	 	= '$kukarow[yhtio]',
							skripti      	= '".$_SERVER['SCRIPT_NAME']."',
							suoritusalku	= '$starttime',
							suoritusloppu	= '$endtime',
							suoritusaika 	= '$aika',
							laatija      	= '$kukarow[kuka]',
							luontiaika   	= now()";
				$suores = mysql_query($suoque) or pupe_error($suoque);
			}
		}				
	}

	if (!isset($ei_kelloa)) {
		$ei_kelloa = '';
	}

	if ($ei_kelloa == "") {
		echo "<br><br>";
		echo "<font class='info'>$aika</font> ";
			
		if (count($kaannetyt_sanat) > 0) {			
			echo " <a href='".$palvelin."sanakirja.php?hakusana=".urlencode(implode("\n", $kaannetyt_sanat))."&tarkkahaku=ON&etsi_kieli=fi&kieli[0]=en&show=all'><font class='info'>".t("Sanakirja")."</font></a>";			
		}
				
		echo "</body></html>";
	}

	mysql_close();

?>