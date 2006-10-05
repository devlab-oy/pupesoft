<?php
	
	if (strpos($_SERVER['SCRIPT_NAME'], "lahetamuistutus.php")  !== FALSE) {	
		require "inc/parametrit.inc";
	}

	echo "<font class='head'>".t("L‰hetet‰‰n k‰ytt‰jille muistutukset hyv‰ksynn‰st‰")."</font><hr>";

	$query = "	SELECT concat_ws(' ',lasku.nimi, nimitark) nimi, tapvm, erpcm, round(summa * valuu.kurssi,2) summa, kuka.eposti
				FROM lasku, valuu, kuka
				WHERE lasku.yhtio='$kukarow[yhtio]' and valuu.yhtio=lasku.yhtio and
				kuka.yhtio=lasku.yhtio and lasku.valkoodi=valuu.nimi and
				lasku.hyvaksyja_nyt=kuka.kuka and kuka.eposti <> '' and
				lasku.tila = 'H'
				ORDER BY kuka.eposti, tapvm";
	$result = mysql_query($query) or pupe_error($query);

	while ($trow=mysql_fetch_array($result)) {
		$laskuja++;
		if ($trow['eposti'] != $veposti) {
			if ($veposti != '') {
				$meili = t("Sinulla on hyv‰ksytt‰v‰n‰ seuraavat laskut").":\n\n" . $meili;
				$tulos = mail($veposti, t("Muistutus laskujen hyv‰ksynn‰st‰")."", $meili, "From: " . $yhtiorow["nimi"] . "<" . $yhtiorow["admin_email"] . ">\nReply-To: " . $yhtiorow["nimi"] . "<" . $yhtiorow["admin_email"] . ">\n");
				$maara++;
			}
			$meili = '';
			$veposti = $trow['eposti'];
		}

		$meili .= "Laskuttaja: " . $trow['nimi'] . "\n";
		$meili .= "Laskutusp‰iv‰: " . $trow['tapvm'] . "\n";
		$meili .= "Er‰p‰iv‰: " . $trow['erpcm'] . "\n";
		$meili .= "Summa: " .$yhtiorow["valkoodi"]." ".$trow['summa'] . "\n\n";
	}
	if ($meili != '') {
		$meili = t("Sinulla on hyv‰ksytt‰v‰n‰ seuraavat laskut").":\n\n" . $meili;
		$tulos = mail($veposti, t("Muistutus laskujen hyv‰ksynn‰st‰")."", $meili, "From: " . $yhtiorow["nimi"] . "<" . $yhtiorow["admin_email"]. ">\nReply-To: " . $yhtiorow["nimi"] . "<" . $yhtiorow["admin_email"] . ">\n");
		$maara++;
	}
	echo "<font class='message'>".t("L‰hetettiin")." $maara ".t("muistutusta. Muistutettuja laskuja")." $laskuja</font><hr>";
	
?>
