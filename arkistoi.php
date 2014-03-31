<?php

require ("inc/parametrit.inc");

echo "<font class='head'>".t("Arkistoidaan asioita")."</font><hr><br>";

echo "<br>";
echo "<table><form method='post'>";

if (!isset($kk))
	$kk = 12;
if (!isset($vv))
	$vv = date("Y")-2;
if (!isset($pp))
	$pp = 31;

echo "<input type='hidden' name='teearkistointi' value='joo'>";
echo "<tr><th>".t("Syötä päivämäärä (pp-kk-vvvv)")."</th>
		<td><input type='text' name='pp' value='$pp' size='3'></td>
		<td><input type='text' name='kk' value='$kk' size='3'></td>
		<td><input type='text' name='vv' value='$vv' size='5'></td>";

echo "<td class='back'><input type='submit' value='".t("Arkistoi")."'></td></tr></table>";
echo "</form><br><br>";

if (isset($teearkistointi) and $teearkistointi != "") {

	# Maksetut myyntilaskut
	$query = "	DELETE lasku FROM lasku
				LEFT JOIN tiliointi ON (lasku.yhtio = tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus and tiliointi.tapvm > '$vv-$kk-$pp')
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				AND lasku.tila    = 'U'
				AND lasku.alatila = 'X'
				AND lasku.mapvm   > 0
				AND lasku.tapvm  <= '$vv-$kk-$pp'
				AND tiliointi.tunnus is null";
	pupe_query($query);
	$del = mysql_affected_rows();

	echo "Arkistoitiin $del myyntilaskua.<br>";

	# Myyntitilaukset
	$query = "	DELETE lasku FROM lasku
				WHERE yhtio = '$kukarow[yhtio]'
				AND tila    = 'L'
				AND alatila = 'X'
				AND tapvm  <= '$vv-$kk-$pp'";
	pupe_query($query);
	$del = mysql_affected_rows();

	echo "Arkistoitiin $del laskutettua myntitilausta.<br>";

	# Maksetut ostolaskut
	$query = "	DELETE lasku
				FROM lasku
				LEFT JOIN tiliointi ON (lasku.yhtio = tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus and tiliointi.tapvm > '$vv-$kk-$pp')
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				AND lasku.tila    in ('H','Y','M','P','Q')
				AND lasku.mapvm   > 0
				AND lasku.tapvm  <= '$vv-$kk-$pp'
				AND tiliointi.tunnus is null";
	pupe_query($query);
	$del = mysql_affected_rows();

	echo "Arkistoitiin $del ostolaskua.<br>";

	# Tositteet
	$query = "	DELETE lasku
				FROM lasku
				LEFT JOIN tiliointi ON (lasku.yhtio = tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus and tiliointi.tapvm > '$vv-$kk-$pp')
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				AND lasku.tila    = 'X'
				AND lasku.tapvm  <= '$vv-$kk-$pp'
				AND tiliointi.tunnus is null";
	pupe_query($query);
	$del = mysql_affected_rows();

	echo "Arkistoitiin $del tositetta.<br>";

	# Tiliöinnit
	$query = "	DELETE tiliointi
				FROM tiliointi
				WHERE yhtio = '$kukarow[yhtio]'
				AND tapvm  <= '$vv-$kk-$pp'";
	pupe_query($query);
	$del = mysql_affected_rows();

	echo "Arkistoitiin $del tiliöintiä.<br>";

	# Tapahtumat
	$query = "	SELECT group_concat(distinct concat('\'',laji,'\'')) lajit
				FROM tapahtuma
				WHERE yhtio = '$kukarow[yhtio]'";
	$result = pupe_query($query);
	$row = mysql_fetch_assoc($result);

	if ($row['lajit'] != "") {
		$query = "	DELETE tapahtuma
					FROM tapahtuma
					WHERE yhtio = '$kukarow[yhtio]'
					AND laji in ({$row['lajit']})
					AND laadittu <= '$vv-$kk-$pp 23:59:59'";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Arkistoitiin $del tapahtumaa.<br>";
	}


	# Tilausrivit
	$query = "	DELETE tilausrivi
				FROM tilausrivi
				LEFT JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				AND lasku.tunnus is null";
	pupe_query($query);
	$del = mysql_affected_rows();

	echo "Arkistoitiin $del tilausriviä.<br>";

	# Tilausrivin_lisatiedot
	$query = "	DELETE tilausrivin_lisatiedot
				FROM tilausrivin_lisatiedot
				LEFT JOIN tilausrivi ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus)
				WHERE tilausrivin_lisatiedot.yhtio = '$kukarow[yhtio]'
				AND tilausrivi.tunnus is null";
	pupe_query($query);
	$del = mysql_affected_rows();

	echo "Arkistoitiin $del tilausrivin lisätietoriviä.<br>";

	# Laskujen/tilausten liitetiedostot
	$query = "	DELETE liitetiedostot
				FROM liitetiedostot
				LEFT JOIN lasku ON (lasku.yhtio = liitetiedostot.yhtio and lasku.tunnus = liitetiedostot.liitostunnus)
				WHERE liitetiedostot.yhtio = '$kukarow[yhtio]'
				AND liitetiedostot.liitos  = 'lasku'
				AND lasku.tunnus is null";
	pupe_query($query);
	$del = mysql_affected_rows();

	echo "Arkistoitiin $del liitetiedostoa.<br>";

	# Tiliotteet
	$query = "	DELETE tiliotedata
				FROM tiliotedata
				WHERE yhtio = '$kukarow[yhtio]'
				AND alku   <= '$vv-$kk-$pp'
				AND loppu  <= '$vv-$kk-$pp'";
	pupe_query($query);
	$del = mysql_affected_rows();

	echo "Arkistoitiin $del tiliotetta.<br>";

	# Suoritukset
	$query = "	DELETE suoritus
				FROM suoritus
				WHERE yhtio = '$kukarow[yhtio]'
				AND kohdpvm > 0
				AND kohdpvm <= '$vv-$kk-$pp'";
	pupe_query($query);
	$del = mysql_affected_rows();

	echo "Arkistoitiin $del suoritusta.<br>";

	# ASN-sanomat
	$query = "	DELETE asn_sanomat
				FROM asn_sanomat
				WHERE yhtio = '$kukarow[yhtio]'
				AND luontiaika > 0
				AND luontiaika <= '$vv-$kk-$pp 23:59:59'";
	pupe_query($query);
	$del = mysql_affected_rows();

	echo "Arkistoitiin $del ASN-sanomaa.<br>";

	# Automanual-hakuhistoria
	$query = "	DELETE automanual_hakuhistoria
				FROM automanual_hakuhistoria
				WHERE yhtio = '$kukarow[yhtio]'
				AND luontiaika > 0
				AND luontiaika <= '$vv-$kk-$pp 23:59:59'";
	pupe_query($query);
	$del = mysql_affected_rows();

	echo "Arkistoitiin $del Automanual-hakuhistoriariviä.<br>";

	# Lähdöt
	$query = "	DELETE lahdot
				FROM lahdot
				WHERE yhtio = '$kukarow[yhtio]'
				AND pvm > 0
				AND pvm <= '$vv-$kk-$pp'";
	pupe_query($query);
	$del = mysql_affected_rows();

	echo "Arkistoitiin $del lähtöä.<br>";

	# Kalenteritapahtumat
	$query = "	DELETE kalenteri
				FROM kalenteri
				WHERE yhtio = '$kukarow[yhtio]'
				AND pvmalku > 0
				AND pvmalku   <= '$vv-$kk-$pp 23:59:59'
				AND (pvmloppu <= '$vv-$kk-$pp 23:59:59' OR pvmloppu = 0)";
	pupe_query($query);
	$del = mysql_affected_rows();

	echo "Arkistoitiin $del kalenteritapahtumaa.<br>";

	# Karhukirjeet
	$query = "	DELETE karhu_lasku
				FROM karhu_lasku
				LEFT JOIN lasku ON (lasku.tunnus = karhu_lasku.ltunnus)
				WHERE lasku.tunnus is null";
	pupe_query($query);
	$del = mysql_affected_rows();

	echo "Arkistoitiin $del maksukehotusta.<br>";

	# Karhukierrokset
	$query = "	DELETE karhukierros
				FROM karhukierros
				LEFT JOIN karhu_lasku ON (karhukierros.tunnus = karhu_lasku.ktunnus)
				WHERE karhu_lasku.ktunnus is null";
	pupe_query($query);
	$del = mysql_affected_rows();

	echo "Arkistoitiin $del maksukehotuskierrosta.<br>";

	# Keräyserät
	$query = "	DELETE kerayserat
				FROM kerayserat
				LEFT JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio and tilausrivi.tunnus = kerayserat.tilausrivi)
				WHERE kerayserat.yhtio = '$kukarow[yhtio]'
				AND tilausrivi.tunnus is null";
	pupe_query($query);
	$del = mysql_affected_rows();

	echo "Arkistoitiin $del keräyseräriviä.<br>";

	# Laskun lisätiedot
	$query = "	DELETE laskun_lisatiedot
				FROM laskun_lisatiedot
				LEFT JOIN lasku ON (lasku.yhtio = laskun_lisatiedot.yhtio and lasku.tunnus = laskun_lisatiedot.otunnus)
				WHERE laskun_lisatiedot.yhtio = '$kukarow[yhtio]'
				AND lasku.tunnus is null";
	pupe_query($query);
	$del = mysql_affected_rows();

	echo "Arkistoitiin $del laskun lisätietoriviä.<br>";

	# Maksupositio
	$query = "	DELETE maksupositio
				FROM maksupositio
				LEFT JOIN lasku ON (lasku.yhtio = maksupositio.yhtio and lasku.tunnus = maksupositio.otunnus)
				WHERE maksupositio.yhtio = '$kukarow[yhtio]'
				AND lasku.tunnus is null";
	pupe_query($query);
	$del = mysql_affected_rows();

	echo "Arkistoitiin $del laskun maksusopimusta.<br>";

	# Rahtikirjat
	$query = "	DELETE rahtikirjat
				FROM rahtikirjat
				LEFT JOIN lasku ON (lasku.yhtio = rahtikirjat.yhtio and lasku.tunnus = rahtikirjat.otsikkonro)
				WHERE rahtikirjat.yhtio = '$kukarow[yhtio]'
				AND rahtikirjat.otsikkonro > 0
				AND lasku.tunnus is null";
	pupe_query($query);
	$del = mysql_affected_rows();

	echo "Arkistoitiin $del rahtikirjaa.<br>";

	# Työmääräykset
	$query = "	DELETE tyomaarays
				FROM tyomaarays
				LEFT JOIN lasku ON (lasku.yhtio = tyomaarays.yhtio and lasku.tunnus = tyomaarays.otunnus)
				WHERE tyomaarays.yhtio = '$kukarow[yhtio]'
				AND lasku.tunnus is null";
	pupe_query($query);
	$del = mysql_affected_rows();

	echo "Arkistoitiin $del työmääräystä.<br>";


	# Budjetit
	$budjettiarray = array("budjetti", "budjetti_asiakas", "budjetti_myyja", "budjetti_toimittaja", "budjetti_tuote");

	foreach ($budjettiarray as $budjettitaulu) {
		$query = "	DELETE $budjettitaulu
					FROM $budjettitaulu
					WHERE yhtio = '$kukarow[yhtio]'
					AND kausi <= '$vv-$kk'";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Arkistoitiin $del {$budjettitaulu}-riviä.<br>";
	}

	# Kampanjat
	$query = "	DELETE kampanjat
				FROM kampanjat
				WHERE yhtio = '$kukarow[yhtio]'
				AND luontiaika > 0
				AND luontiaika <= '$vv-$kk-$pp 23:59:59'";
	pupe_query($query);
	$del = mysql_affected_rows();

	echo "Arkistoitiin $del kampanjaa.<br>";

	$query = "	DELETE kampanja_ehdot
				FROM kampanja_ehdot
				LEFT JOIN kampanjat ON (kampanjat.yhtio = kampanja_ehdot.yhtio and kampanjat.tunnus = kampanja_ehdot.kampanja)
				WHERE kampanja_ehdot.yhtio = '$kukarow[yhtio]'
				AND kampanjat.tunnus is null";
	pupe_query($query);
	$del = mysql_affected_rows();

	echo "Arkistoitiin $del kampanjan ehtoriviä.<br>";

	$query = "	DELETE kampanja_palkinnot
				FROM kampanja_palkinnot
				LEFT JOIN kampanjat ON (kampanjat.yhtio = kampanja_palkinnot.yhtio and kampanjat.tunnus = kampanja_palkinnot.kampanja)
				WHERE kampanja_palkinnot.yhtio = '$kukarow[yhtio]'
				AND kampanjat.tunnus is null";
	pupe_query($query);
	$del = mysql_affected_rows();

	echo "Arkistoitiin $del kampanjan palkinotirivä.<br>";


}

require ("inc/footer.inc");
