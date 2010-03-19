<?php

	if (isset($formi) and isset($kentta)) {
		echo "<script LANGUAGE='JavaScript'>window.document.$formi.$kentta.focus();</script>";
	}

	$timeparts = explode(" ",microtime());
	$endtime   = $timeparts[1].substr($timeparts[0],1);
	$aika      = round($endtime-$starttime,4);
	$useslave  = 0;

	if ((!isset($verkkokauppa) or $verkkokauppa == "") and require("connect.inc")) {
		$suoque = "	INSERT INTO suorituskykyloki
					SET yhtio	 	= '$kukarow[yhtio]',
					skripti      	= '".$_SERVER['SCRIPT_NAME']."',
					request			= '".mysql_real_escape_string(serialize($_REQUEST))."',
					suoritusalku	= '".date("Y-m-d H:i:s", $starttime)."',
					suoritusloppu	= '".date("Y-m-d H:i:s", $endtime)."',
					suoritusaika 	= '$aika',
					laatija      	= '$kukarow[kuka]',
					luontiaika   	= now()";
		$suores = mysql_query($suoque) or pupe_error($suoque);
	}

	if ((!isset($nayta_pdf)) and (!isset($ei_kelloa) or $ei_kelloa == "") and (!isset($ohje) or $ohje != "off")) {

		$loppumem = memory_get_usage();

		echo "<br><br>";
		echo "<font class='info'>$aika ".round((($loppumem-$alkumem)/1000000), 1)."</font>";

		if (isset($kaannetyt_sanat) and count($kaannetyt_sanat) > 0) {
			echo " <a href='".$palvelin."sanakirja.php?hakusana=".urlencode(implode("\n", $kaannetyt_sanat))."&tarkkahaku=ON&etsi_kieli=fi&kieli[0]=en&show=all'><font class='info'>".t("Sanakirja")."</font></a>";
		}

		echo "<br><br>";

		if ($kukarow["extranet"] != "") {
			echo $yhtiorow["web_seuranta"];
		}

		echo "</body>";
		echo "</html>";
	}

	mysql_close($link);

?>