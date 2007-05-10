<?php

	if (isset($formi) and isset($kentta)) {
		echo "<script LANGUAGE='JavaScript'>window.document.$formi.$kentta.focus();</script>";
	}

	$timeparts = explode(" ",microtime());
	$endtime   = $timeparts[1].substr($timeparts[0],1);
	$aika      = round($endtime-$starttime,4);


	
	//	Tarkastetaan käsitelläänkö suorituskykyongelmia
	$query 	= "describe suorituskykyloki";
	$suores = mysql_query($query);

	if (mysql_error() == "") {
		$suoque = "	INSERT INTO suorituskykyloki
					SET yhtio	 = '$kukarow[yhtio]',        
					skripti      = '".$_SERVER['SCRIPT_NAME']."',
					suoritusaika = '$aika',
					laatija      = '$kukarow[kuka]',
					luontiaika   = now()";
		$suores = mysql_query($suoque) or pupe_error($suoque);
	}
	
	echo "<br><font class='info'><br>$aika</font>";
	echo "</body></html>";
?>