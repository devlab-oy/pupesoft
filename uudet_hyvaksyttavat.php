<?php

if (isset($argv[1]) and trim($argv[1]) != '') {

	if ($argc == 0) die ("Tätä scriptiä voi ajaa vain komentoriviltä!");

	// otetaan tietokanta connect
	require ("inc/connect.inc");
	require ("inc/functions.inc");
	
	if($argv[2] != "") {
		$ajalta = $argv[2];
	}
	
	//	Oletus
	if((int) $ajalta == 0) $ajalta = 1;
	
	$query    = "select * from kuka where kuka='$argv[1]' limit 1";
	$kukares = mysql_query($query) or pupe_error($query);
	if(mysql_num_rows($kukares) == 0) die("Karhuajaa ei löyry!\n$query\n");
	$kukarow = mysql_fetch_array($kukares);
	
	$query    = "select * from yhtio where yhtio='$kukarow[yhtio]'";
	$yhtiores = mysql_query($query) or pupe_error($query);
	if(mysql_num_rows($yhtiores) == 0) die("Firmaa ei löyry!\n");
	$yhtiorow = mysql_fetch_array($yhtiores);

	$query = "	SELECT *
				FROM yhtion_parametrit
				WHERE yhtio='$kukarow[yhtio]'";
	$result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

	if (mysql_num_rows($result) == 1) {
		$yhtion_parametritrow = mysql_fetch_array($result);
				
		// lisätään kaikki yhtiorow arrayseen
		foreach ($yhtion_parametritrow as $parametrit_nimi => $parametrit_arvo) {
			$yhtiorow[$parametrit_nimi] = $parametrit_arvo;
		}
	}


	$query = "	SELECT concat_ws(' ',lasku.nimi, nimitark) nimi, tapvm, erpcm, round(summa * valuu.kurssi,2) summa, kuka.eposti
				FROM lasku
				LEFT JOIN valuu ON valuu.yhtio=lasku.yhtio and lasku.valkoodi=valuu.nimi
				JOIN kuka ON kuka.yhtio=lasku.yhtio and lasku.hyvaksyja_nyt=kuka.kuka and kuka.eposti <> ''
				WHERE lasku.yhtio='$kukarow[yhtio]' and lasku.tila = 'H' and lasku.luontiaika > DATE_SUB(now(), INTERVAL $ajalta DAY)
				ORDER BY kuka.eposti, tapvm";
	$result = mysql_query($query) or pupe_error($query);

	while ($trow=mysql_fetch_array($result)) {
		$laskuja++;
		
		if ($trow['eposti'] != $veposti) {
			if ($veposti != '') {
				$meili = t("Sinulla on seuraavat uudet laskut hyväksyttävänä").":\n\n" . $meili;
				$tulos = mail($veposti, t("Uudet hyväksyttävät laskusi")."", $meili, "From: " . $yhtiorow["nimi"] . "<" . $yhtiorow["alert_email"] . ">\nReply-To: " . $yhtiorow["nimi"] . "<" . $yhtiorow["alert_email"] . ">\n", "-f $yhtiorow[postittaja_email]");
				$maara++;
			}
			$meili = '';
			$veposti = $trow['eposti'];
		}

		$meili .= "Laskuttaja: " . $trow['nimi'] . "\n";
		$meili .= "Laskutuspäivä: " . $trow['tapvm'] . "\n";
		$meili .= "Eräpäivä: " . $trow['erpcm'] . "\n";
		$meili .= "Summa: " .$yhtiorow["valkoodi"]." ".$trow['summa'] . "\n\n";
	}
	if ($meili != '') {
		$meili = t("Sinulla on seuraavat uudet laskut hyväksyttävänä").":\n\n" . $meili;
		$tulos = mail($veposti, t("Uudet hyväksyttävät laskusi")."", $meili, "From: " . $yhtiorow["nimi"] . "<" . $yhtiorow["alert_email"] . ">\nReply-To: " . $yhtiorow["nimi"] . "<" . $yhtiorow["alert_email"] . ">\n", "-f $yhtiorow[postittaja_email]");
		$maara++;
	}
}
else {
	echo "Tätä ei saa ajaa kuin komentoriviltä!<br>\n";
}

?>
