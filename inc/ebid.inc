<?php

if (! function_exists('ebid')) {
	function ebid($lasku_tunnus) {
		global $kukarow, $yhtiorow;
		
		$query = "SELECT * from lasku where tunnus=" . (int) $lasku_tunnus . " and yhtio='{$kukarow['yhtio']}'";
		$res = mysql_query($query) or pupe_error($query);
		$laskurow = mysql_fetch_array($res);
		
		if ($laskurow['ebid'] != "") {
			$ebid = $laskurow['ebid'];
			
			$verkkolaskutunnus = $yhtiorow['verkkotunnus_vas'];
			$salasana		   = $yhtiorow['verkkosala_vas'];
			
			$timestamppi=gmdate("YmdHis")."Z";
			
			$urlhead = "http://www.verkkolasku.net";
			$urlmain = "/view/ebs-2.0/$verkkolaskutunnus/visual?DIGEST-ALG=MD5&DIGEST-KEY-VERSION=1&EBID=$ebid&TIMESTAMP=$timestamppi&VERSION=ebs-2.0";
			
			$digest	 = md5($urlmain . "&" . $salasana);
			$url	 = $urlhead.$urlmain."&DIGEST=$digest";
			return "<a href='$url'>". t('Näytä lasku')."</a>";
		}
		else {
			
			$query = "SELECT liitostunnus from liitetiedostot where liitostunnus='{$laskurow['tunnus']}' and liitos='lasku' and yhtio='{$kukarow['yhtio']}'";
			$res = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($res) > 0) {
				return "<a href='view.php?id={$laskurow['tunnus']}'>". t('Näytä lasku') ."</a>";
			}
			
			return t('Paperilasku');
		}
	}
}
?>
