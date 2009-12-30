<?php
if(!function_exists("luoiban")) {
	function luoiban ($tilino) {
		include "pankkitiedot.inc";

		$iban = $tilino . "151800";
		for ($ind = 0; $ind < 20; $ind += 7) {
			$vali .= substr($iban, $ind, 7);
			$vali = $vali % 97;
			//echo "iban: $iban, (".substr($iban, $ind, 7).") $vali\n";
		}
		$tarkiste = 98 - $vali;
		$vastaus['iban'] = "FI" . sprintf('%02d', $tarkiste) . $tilino;

		$pankki = pankkitiedot($tilino,'');
		$vastaus['swift'] = $pankki['swift'];

		return $vastaus;
	}
}
?>

