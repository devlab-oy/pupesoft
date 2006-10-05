
<?php

$ebid=trim($ebid);

if (substr($ebid,0,1) != "/") {

	$verkkolaskutunnus = $yhtiorow['verkkotunnus_vas'];
	$salasana          = $yhtiorow['verkkosala_vas'];

	$timestamppi=gmdate("YmdHis")."Z";

	$urlhead = "http://www.verkkolasku.net";
	$urlmain = "/view/ebs-2.0/$verkkolaskutunnus/visual?DIGEST-ALG=MD5&DIGEST-KEY-VERSION=1&EBID=$ebid&TIMESTAMP=$timestamppi&VERSION=ebs-2.0";

	$digest  = md5($urlmain . "&" . $salasana);
	$url     = $urlhead.$urlmain."&DIGEST=$digest";
}
else {

	// otetaan palvelin muuttujasta mahdolliset turhat alut pois
	$alut    = array("http://","https://");
	$apunimi = str_replace($alut, "", $yhtiorow['lasku_polku_http']);

	if ($_SERVER["HTTPS"] == "on") {
		$url = "https://$apunimi".$ebid;
	}
	else {
		$url = "http://$apunimi".$ebid;
	}

}
?>
