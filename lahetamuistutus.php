<?php

	//k‰yttˆliittym‰
	if (strpos($_SERVER['SCRIPT_NAME'], "lahetamuistutus.php")  !== FALSE) {

		//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
		$useslave = 1;

		require ("inc/parametrit.inc");

		echo "<font class='head'>".t("Muistuta k‰ytt‰ji‰ hyv‰ksynn‰ss‰ olevista ostolaskuista")."</font><hr>";
	}

	if (strpos($_SERVER['SCRIPT_NAME'], "lahetamuistutus.php")  === FALSE or $tee == "LAHETA") {

		$maara = 0;
		$laskuja = 0;

		echo "<br>".t("L‰hetet‰‰n k‰ytt‰jille muistutukset hyv‰ksynn‰st‰")."...<br>";

		$query = "	SELECT concat_ws(' ',lasku.nimi, nimitark) nimi, tapvm, erpcm, round(summa * valuu.kurssi,2) summa, kuka.eposti
					FROM lasku, valuu, kuka
					WHERE lasku.yhtio='$kukarow[yhtio]' and valuu.yhtio=lasku.yhtio and
					kuka.yhtio=lasku.yhtio and lasku.valkoodi=valuu.nimi and
					lasku.hyvaksyja_nyt=kuka.kuka and kuka.eposti <> '' and
					lasku.tila = 'H'
					ORDER BY kuka.eposti, tapvm";
		$result = mysql_query($query) or pupe_error($query);

		while ($trow = mysql_fetch_array($result)) {
			$laskuja++;
			if ($trow['eposti'] != $veposti) {
				if ($veposti != '') {
					$meili = t("Sinulla on hyv‰ksytt‰v‰n‰ seuraavat laskut").":\n\n" . $meili;
					/* Muokattu 14.2.2014, kommentoitu vanha mail() -funktio pois ja lis‰tty paranneltu sendMail */
					//$tulos = mail($veposti, mb_encode_mimeheader(t("Muistutus laskujen hyv‰ksynn‰st‰"), "ISO-8859-1", "Q"), $meili, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
					include_once '/var/www/html/lib/functions/sendMail.php';  // Lis‰t‰‰n sendMail funktio
					$tulos = sendMail($yhtiorow['postittaja_email'], $veposti, t("Muistutus laskujen hyv‰ksynn‰st‰"), $meili);
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
			/* Muokattu 14.2.2014, kommentoitu vanha mail() -funktio pois ja lis‰tty paranneltu sendMail */
			//$tulos = mail($veposti, mb_encode_mimeheader(t("Muistutus laskujen hyv‰ksynn‰st‰"), "ISO-8859-1", "Q"), $meili, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
			include_once '/var/www/html/lib/functions/sendMail.php';  // Lis‰t‰‰n sendMail funktio
			$tulos = sendMail($yhtiorow['postittaja_email'], $veposti, t("Muistutus laskujen hyv‰ksynn‰st‰"), $meili);
			$maara++;
		}

		echo "<br><br><font class='message'>".t("L‰hetettiin")." $maara ".t("muistutusta. Muistutettuja laskuja")." $laskuja ".t("kappaletta").".</font><hr>";
	}

	//k‰yttˆliittym‰
	if (strpos($_SERVER['SCRIPT_NAME'], "lahetamuistutus.php")  !== FALSE) {

		echo "	<br><br>
				<form method='post' action='$PHP_SELF'>
				<input type='hidden' name='tee' value='LAHETA'>
				<input type='submit' value='".t("L‰het‰ muistutukset hyv‰ksynn‰ss‰ olevista ostolaskuista")."'>
				</form>";


		require ("inc/footer.inc");
	}
?>