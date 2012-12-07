<?php

$nimi = "../dataout/elma-order-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true)).".txt";

if (!$toot = fopen($nimi, "w")) die("Filen $nimi luonti epäonnistui!");

$query = "	SELECT tilausrivi.tunnus, toim_tuoteno, ostohinta, eankoodi, tilausrivi.tuoteno, round(varattu*if(tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin),4) varattu, toimaika
			FROM tilausrivi
			LEFT JOIN tuote USING (yhtio, tuoteno)
			LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.liitostunnus='$laskurow[liitostunnus]'
			WHERE otunnus = '$laskurow[tunnus]' and varattu > 0 and tilausrivi.yhtio='$kukarow[yhtio]'
			ORDER BY toim_tuoteno";
$result = pupe_query($query);

if (mysql_num_rows($result) == 0) {
	echo "<font class='error'>Lähetettäviä tilausrivejä ei löydy</font>";
}
else {
	$ulos  = "ICHGSTART:" . $yhtiorow['yhtio'] . $laskurow['tunnus'] . "\n";
	$ulos .= "ICHG_TYPE:POS\n";
	$ulos .= "OMSGSTART:" . $yhtiorow['yhtio'] . $laskurow['tunnus'] . "\n";
	$ulos .= "OHDRSTART:" . $yhtiorow['yhtio'] . $laskurow['tunnus'] . "\n";
	$ulos .= "OBGMITYPE:220\n";
	$ulos .= "OBGMINUMB:" . $laskurow['tunnus'] . "\n";
	$ulos .= "OBGMIACKR:NA\n";
	$ulos .= "ODTM4__DT:" . tv2dateconv($laskurow['luontiaika']) . "\n";
	$ulos .= "ODTM137DT:" . tv2dateconv($laskurow['luontiaika']) . "\n";
	$ulos .= "OFTXDELTX:" . $laskurow['huolitsija'] . "\n";
	$ulos .= "ONADSE_NA:" . $laskurow['nimi'] . "\n";
	$ulos .= "ONADSE_NX:" . $laskurow['nimitark'] . "\n";
	$ulos .= "ONADSE_SA:" . $laskurow['osoite'] . "\n";
	$ulos .= "ONADSE_PO:" . $laskurow['postino'] . "\n";
	$ulos .= "ONADSE_CI:" . $laskurow['postitp'] . "\n";
	$ulos .= "ONADSE_CN:" . $laskurow['maa'] . "\n";
	$ulos .= "ONADBY_PC:" . sprintf ('%-18.18s', $yhtiorow['ovttunnus']) . "167 91". "\n";
	$ulos .= "ONADBY_NA:" . $yhtiorow['nimi'] . "\n";
	$ulos .= "ONADBY_SA:" . $yhtiorow['osoite'] . "\n";
	$ulos .= "ONADBY_PO:" . $yhtiorow['postino'] . "\n";
	$ulos .= "ONADBY_CI:" . $yhtiorow['postitp'] . "\n";
	$ulos .= "ONADBY_CN:" . $yhtiorow['maa'] . "\n";
	$ulos .= "ONADBY_CC:" . $laskurow['myyja'] . "\n";
	$ulos .= "ONADBY_RF:" . "VA  " . $yhtiorow['maa'] . "-" . $yhtiorow['ytunnus'] . "\n";
	$ulos .= "ONADBY_DO:" . "320 EI  1" . "\n";
	$ulos .= "OHDR__END:" . $yhtiorow['yhtio'] . $laskurow['tunnus'] . "\n";
	fputs($toot,$ulos);

	$rivino=1; // joudumme tekemään dummy rivinumerot, kun elma ei salli yli kuusimerkkisiä rivinumeroita

	while ($tilausrivirow=mysql_fetch_array ($result)) {
		if ($tilausrivirow['yksikko'] == '') $tilausrivirow['yksikko'] = "PCE";
		if ($tilausrivirow['eankoodi'] == 0) $tilausrivirow['eankoodi'] = '';
		$ulos  = "OLINSTART:" . $rivino . "\n";
		$ulos .= "OLINSA_NU:" . $tilausrivirow['toim_tuoteno']  . "\n";
		$ulos .= "OLINEN_NU:" . $tilausrivirow['eankoodi'] . "\n";
		$ulos .= "OLININ_PI:" . sprintf ('%-36.36s', $tilausrivirow['tuoteno']) . "1" . "\n";
		$ulos .= "OLIN21_QT:" . sprintf("%013.4f", (float) $tilausrivirow['varattu']) . "\n";
		$ulos .= "OLINCONIV:" . sprintf("%015.2f", (float) $tilausrivirow['ostohinta']) . " " . $laskurow['valkoodi'] . " " . $tilausrivirow['yksikko'] . "\n";

		if ($yhtiorow['ostotilaustyyppi'] == 0 or $yhtiorow['ostotilaustyyppi'] == 'B') {
			$ulos .= "OLIN2__DT:" . tv2dateconv($tilausrivirow['toimaika']) . "\n";
		}
		else {
			$ulos .= "OLIN2__DT:\n";
		}

		$ulos .= "OLIN__END:" . $rivino . "\n";
		fputs($toot,$ulos);
		$rivino++;
	}

	$ulos  = "OMSG__END:" . $yhtiorow['yhtio'] . $laskurow['tunnus'] . "\n";
	$ulos .= "ICHG__END:" . $yhtiorow['yhtio'] . $laskurow['tunnus'] . "\n";
	fputs($toot,$ulos);
	fclose ($toot);

	// tarvitaan  $ftphost $ftpuser $ftppass $ftppath $ftpfile
	// palautetaan $palautus ja $syy

	$ftphost = $toimirow['edi_palvelin'];
	$ftpuser = $toimirow['edi_kayttaja'];
	$ftppass = $toimirow['edi_salasana'];
	$ftppath = $toimirow['edi_polku'];
	$ftpfile = realpath($nimi);

	require ("inc/ftp-send.inc");

	if ($palautus == 0) {
		echo "<font class='message'>".t("Tilaus lähetettiin onnistuneesti")."!</font><br>";

		$sqlquery = "	UPDATE lasku
						SET kirjoitin = 'edi'
						WHERE yhtio   = '$kukarow[yhtio]'
						AND tunnus	  = '$laskurow[tunnus]'";
		$result = pupe_query($sqlquery);
	}
	else {
		echo "<font class='error'>".t("Tilauksen lähetys epäonnistui")."!</font><br>";
	}
}

// Nollataan tarkoituksella lopetusmuuttuja
$lopetus = "";

$nimi  = "";
$ulos  = "";
