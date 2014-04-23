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
echo "<tr><th>".t("Sy�t� p�iv�m��r� (pp-kk-vvvv)")."</th>
		<td><input type='text' name='pp' value='$pp' size='3'></td>
		<td><input type='text' name='kk' value='$kk' size='3'></td>
		<td><input type='text' name='vv' value='$vv' size='5'></td>";

echo "<td class='back'><input type='submit' value='".t("Arkistoi")."'></td></tr></table>";
echo "</form><br><br>";

if (isset($teearkistointi) and $teearkistointi != "") {

	$dellataan = TRUE;

	##################################################################################################################################
	#OSTORESKONTRA
	##################################################################################################################################
	if ($dellataan) {
		# Maksetut ostolaskut
		$query = "	DELETE lasku
					FROM lasku
					LEFT JOIN tiliointi ON (lasku.yhtio = tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus and tiliointi.tapvm > '$vv-$kk-$pp')
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					AND lasku.tila    in ('H','Y','M','P','Q')
					AND lasku.mapvm   > 0
					AND lasku.mapvm  <= '$vv-$kk-$pp'
					AND tiliointi.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del ostolaskua.<br>";
	}

	##################################################################################################################################
	#OSTOHISTORIA
	##################################################################################################################################
	if ($dellataan) {

		# Saapumiset
		$query = "	DELETE lasku
					FROM lasku
					WHERE yhtio     = '$kukarow[yhtio]'
					AND tila        = 'K'
					AND alatila     = 'X'
					AND mapvm       > 0
					AND mapvm      <= '$vv-$kk-$pp'
					AND vanhatunnus = 0";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del saapumista.<br>";

		# Saapumisen liitosotsikot
		$query = "	DELETE lasku
					FROM lasku
					LEFT JOIN lasku AS saapuminen ON (lasku.yhtio = saapuminen.yhtio and saapuminen.laskunro = lasku.laskunro and saapuminen.tila = 'K' and saapuminen.vanhatunnus = 0 )
					WHERE lasku.yhtio     = '$kukarow[yhtio]'
					AND lasku.tila        = 'K'
					AND lasku.vanhatunnus > 0
					AND saapuminen.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del saapumisen liitosotsikkoa.<br>";

		# Ostotilaukset
		$query = "	DELETE lasku
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]'
					AND tila = 'O'
					AND alatila = 'X'
					AND luontiaika <= '$vv-$kk-$pp 23:59:59'";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del ostotilausta.<br>";

		# ASN-sanomat
		$query = "	DELETE asn_sanomat
					FROM asn_sanomat
					WHERE yhtio = '$kukarow[yhtio]'
					AND luontiaika > 0
					AND luontiaika <= '$vv-$kk-$pp 23:59:59'";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del ASN-sanomaa.<br>";
	}

	##################################################################################################################################
	#MYYNTIHISTORIA
	##################################################################################################################################
	if ($dellataan) {
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

		echo "Poistettiin $del myyntilaskua.<br>";

		# Myyntitilaukset
		$query = "	DELETE lasku FROM lasku
					WHERE yhtio = '$kukarow[yhtio]'
					AND tila    = 'L'
					AND alatila = 'X'
					AND tapvm  <= '$vv-$kk-$pp'";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del laskutettua myntitilausta.<br>";

		# Laskun lis�tiedot
		$query = "	DELETE laskun_lisatiedot
					FROM laskun_lisatiedot
					LEFT JOIN lasku ON (lasku.yhtio = laskun_lisatiedot.yhtio and lasku.tunnus = laskun_lisatiedot.otunnus)
					WHERE laskun_lisatiedot.yhtio = '$kukarow[yhtio]'
					AND lasku.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del laskun lis�tietorivi�.<br>";

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

			echo "Poistettiin $del tapahtumaa.<br>";

			// Orvot tapahtumat
			$query = "	DELETE tapahtuma
						FROM tapahtuma
						LEFT JOIN tilausrivi ON (tilausrivi.yhtio = tapahtuma.yhtio and tilausrivi.tunnus = tapahtuma.rivitunnus)
						WHERE tapahtuma.yhtio = '{$kukarow["yhtio"]}'
					 	AND tapahtuma.rivitunnus > 0
						AND tilausrivi.tunnus is null";
			pupe_query($query);
			$del = mysql_affected_rows();

			echo "Poistettiin $del orpoa-tapahtumarivi�.<br>";
		}

		# Tilausrivit
		$query = "	DELETE tilausrivi
					FROM tilausrivi
					LEFT JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					AND lasku.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del tilausrivi�.<br>";

		# Tilausrivin_lisatiedot
		$query = "	DELETE tilausrivin_lisatiedot
					FROM tilausrivin_lisatiedot
					LEFT JOIN tilausrivi ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus)
					WHERE tilausrivin_lisatiedot.yhtio = '$kukarow[yhtio]'
					AND tilausrivi.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del tilausrivin lis�tietorivi�.<br>";

		# Sahkoisen_lahetteen_rivit
		$query = "	DELETE sahkoisen_lahetteen_rivit
					FROM sahkoisen_lahetteen_rivit
					LEFT JOIN lasku ON (lasku.yhtio = sahkoisen_lahetteen_rivit.yhtio and lasku.tunnus = sahkoisen_lahetteen_rivit.otunnus)
					WHERE sahkoisen_lahetteen_rivit.yhtio = '$kukarow[yhtio]'
					AND lasku.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del s�hk�isen l�hetteen rivi�.<br>";

		# Laskujen/tilausten liitetiedostot
		$query = "	DELETE liitetiedostot
					FROM liitetiedostot
					LEFT JOIN lasku ON (lasku.yhtio = liitetiedostot.yhtio and lasku.tunnus = liitetiedostot.liitostunnus)
					WHERE liitetiedostot.yhtio = '$kukarow[yhtio]'
					AND liitetiedostot.liitos  = 'lasku'
					AND lasku.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del liitetiedostoa.<br>";

		# Maksupositio
		$query = "	DELETE maksupositio
					FROM maksupositio
					LEFT JOIN lasku ON (lasku.yhtio = maksupositio.yhtio and lasku.tunnus = maksupositio.otunnus)
					WHERE maksupositio.yhtio = '$kukarow[yhtio]'
					AND lasku.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del laskun maksusopimusta.<br>";

		# Rahtikirjat
		$query = "	DELETE rahtikirjat
					FROM rahtikirjat
					LEFT JOIN lasku ON (lasku.yhtio = rahtikirjat.yhtio and lasku.tunnus = rahtikirjat.otsikkonro)
					WHERE rahtikirjat.yhtio = '$kukarow[yhtio]'
					AND rahtikirjat.otsikkonro > 0
					AND lasku.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del rahtikirjaa.<br>";

		# Ty�m��r�ykset
		$query = "	DELETE tyomaarays
					FROM tyomaarays
					LEFT JOIN lasku ON (lasku.yhtio = tyomaarays.yhtio and lasku.tunnus = tyomaarays.otunnus)
					WHERE tyomaarays.yhtio = '$kukarow[yhtio]'
					AND lasku.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del ty�m��r�yst�.<br>";
	}

	##################################################################################################################################
	#KIRJANPITO
	##################################################################################################################################
	if ($dellataan) {
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

		echo "Poistettiin $del tositetta.<br>";

		# Tili�innit
		$query = "	DELETE tiliointi
					FROM tiliointi
					WHERE yhtio = '$kukarow[yhtio]'
					AND tapvm  <= '$vv-$kk-$pp'";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del tili�inti�.<br>";

		# Tiliotteet
		$query = "	DELETE tiliotedata
					FROM tiliotedata
					WHERE yhtio = '$kukarow[yhtio]'
					AND alku   <= '$vv-$kk-$pp'
					AND loppu  <= '$vv-$kk-$pp'";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del tiliotetta.<br>";
	}

	##################################################################################################################################
	#MYYNTIRESKONTRA
	##################################################################################################################################
	if ($dellataan) {
		# Suoritukset
		$query = "	DELETE suoritus
					FROM suoritus
					WHERE yhtio = '$kukarow[yhtio]'
					AND kohdpvm > 0
					AND kohdpvm <= '$vv-$kk-$pp'";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del suoritusta.<br>";

		# Karhukirjeet
		$query = "	DELETE karhu_lasku
					FROM karhu_lasku
					LEFT JOIN lasku ON (lasku.tunnus = karhu_lasku.ltunnus)
					WHERE lasku.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del maksukehotusta.<br>";

		# Karhukierrokset
		$query = "	DELETE karhukierros
					FROM karhukierros
					LEFT JOIN karhu_lasku ON (karhukierros.tunnus = karhu_lasku.ktunnus)
					WHERE karhu_lasku.ktunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del maksukehotuskierrosta.<br>";
	}

	##################################################################################################################################
	#ASIAKASTIEDOT
	##################################################################################################################################
	if ($dellataan) {
		// Poistetaan "P" asiakkaita joilla ei ole laskutusta
		$query = "	DELETE asiakas
					FROM asiakas
					LEFT JOIN lasku ON (asiakas.yhtio=lasku.yhtio and asiakas.tunnus=lasku.liitostunnus)
					WHERE asiakas.yhtio = '{$kukarow["yhtio"]}'
					AND asiakas.laji = 'P'
					AND lasku.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del 'P'-asiakasta joilla ei ollut yht��n myynti�!<br>";

		// Poistetaan asiakasalet joiden asiakkaat dellattu
		$query = "	DELETE asiakasalennus
					FROM asiakasalennus
					LEFT JOIN asiakas ON (asiakasalennus.yhtio=asiakas.yhtio and asiakasalennus.asiakas=asiakas.tunnus)
					WHERE asiakasalennus.yhtio = '{$kukarow["yhtio"]}'
					AND asiakasalennus.asiakas > 0
					AND asiakas.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del asiakasalennusta joiden asiakas oli poistettu (asiakas)!<br>";

		// Poistetaan asiakasalet joiden asiakkaat dellattu
		$query = "	DELETE asiakasalennus
					FROM asiakasalennus
					LEFT JOIN asiakas ON (asiakasalennus.yhtio=asiakas.yhtio and asiakasalennus.ytunnus=asiakas.ytunnus)
					WHERE asiakasalennus.yhtio = '{$kukarow["yhtio"]}'
					AND asiakasalennus.ytunnus not in ('','0')
					AND asiakas.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del asiakasalennusta joiden asiakas oli poistettu (ytunnus)!<br>";

		// Poistetaan asiakashinnat joiden asiakkaat dellattu
		$query = "	DELETE asiakashinta
					FROM asiakashinta
					LEFT JOIN asiakas ON (asiakashinta.yhtio=asiakas.yhtio and asiakashinta.asiakas=asiakas.tunnus)
					WHERE asiakashinta.yhtio = '{$kukarow["yhtio"]}'
					AND asiakashinta.asiakas > 0
					AND asiakas.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del asiakashintaa joiden asiakas oli poistettu (asiakas)!<br>";

		// Poistetaan asiakashinnat joiden asiakkaat dellattu
		$query = "	DELETE asiakashinta
					FROM asiakashinta
					LEFT JOIN asiakas ON (asiakashinta.yhtio=asiakas.yhtio and asiakashinta.ytunnus=asiakas.ytunnus)
					WHERE asiakashinta.yhtio = '{$kukarow["yhtio"]}'
					AND asiakashinta.ytunnus not in ('','0')
					AND asiakas.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del asiakashintaa joiden asiakas oli poistettu (ytunnus)!<br>";

		// Poistetaan asiakaskommentit joiden asiakkaat dellattu
		$query = "	DELETE asiakaskommentti
					FROM asiakaskommentti
					LEFT JOIN asiakas ON (asiakaskommentti.yhtio=asiakas.yhtio and asiakaskommentti.ytunnus=asiakas.ytunnus)
					WHERE asiakaskommentti.yhtio = '{$kukarow["yhtio"]}'
					AND asiakas.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del asiakaskommenttia joiden asiakas oli poistettu!<br>";

		// Poistetaan asiakkaan_avainsanat joiden asiakkaat dellattu
		$query = "	DELETE asiakkaan_avainsanat
					FROM asiakkaan_avainsanat
					LEFT JOIN asiakas ON (asiakkaan_avainsanat.yhtio=asiakas.yhtio and asiakkaan_avainsanat.liitostunnus=asiakas.tunnus)
					WHERE asiakkaan_avainsanat.yhtio = '{$kukarow["yhtio"]}'
					AND asiakas.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del asiakkaan_avainsanaa joiden asiakas oli poistettu!<br>";

		// Poistetaan budjetti_asiakaat joiden asiakkaat dellattu
		$query = "	DELETE budjetti_asiakas
					FROM budjetti_asiakas
					LEFT JOIN asiakas ON (budjetti_asiakas.yhtio=asiakas.yhtio and budjetti_asiakas.asiakkaan_tunnus=asiakas.tunnus)
					WHERE budjetti_asiakas.yhtio = '{$kukarow["yhtio"]}'
					AND asiakas.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del asiakasbudjettia joiden asiakas oli poistettu!<br>";

		// Poistetaan yhteyshenkilo joiden asiakkaat dellattu
		$query = "	DELETE yhteyshenkilo
					FROM yhteyshenkilo
					LEFT JOIN asiakas ON (yhteyshenkilo.yhtio=asiakas.yhtio and yhteyshenkilo.liitostunnus=asiakas.tunnus)
					WHERE yhteyshenkilo.yhtio = '{$kukarow["yhtio"]}'
					AND yhteyshenkilo.tyyppi = 'A'
					AND asiakas.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del yhteyshenkil�� joiden asiakas oli poistettu!<br>";

		// Poistetaan rahtisopimukset joiden asiakkaat dellattu
		$query = "	DELETE rahtisopimukset
					FROM rahtisopimukset
					LEFT JOIN asiakas ON (rahtisopimukset.yhtio=asiakas.yhtio and rahtisopimukset.asiakas=asiakas.tunnus)
					WHERE rahtisopimukset.yhtio = '{$kukarow["yhtio"]}'
					AND rahtisopimukset.asiakas > 0
					AND asiakas.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del rahtisopimusta joiden asiakas oli poistettu (asiakas)!<br>";

		// Poistetaan rahtisopimukset joiden asiakkaat dellattu
		$query = "	DELETE rahtisopimukset
					FROM rahtisopimukset
					LEFT JOIN asiakas ON (rahtisopimukset.yhtio=asiakas.yhtio and rahtisopimukset.ytunnus=asiakas.ytunnus)
					WHERE rahtisopimukset.yhtio = '{$kukarow["yhtio"]}'
					AND rahtisopimukset.ytunnus not in ('','0')
					AND asiakas.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del rahtisopimusta joiden asiakas oli poistettu (ytunnus)!<br>";

		// Poistetaan liitetiedostot joiden asiakkaat dellattu
		$query = "	DELETE liitetiedostot
					FROM liitetiedostot
					LEFT JOIN asiakas ON (liitetiedostot.yhtio=asiakas.yhtio and liitetiedostot.liitostunnus=asiakas.tunnus)
					WHERE liitetiedostot.yhtio = '{$kukarow["yhtio"]}'
					AND liitetiedostot.liitos = 'asiakas'
					AND asiakas.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del liitetiedostoa joiden asiakas oli poistettu!<br>";

		// Poistetaan korvaavat_kiellot joiden asiakkaat dellattu
		$query = "	DELETE korvaavat_kiellot
					FROM korvaavat_kiellot
					LEFT JOIN asiakas ON (korvaavat_kiellot.yhtio=asiakas.yhtio and korvaavat_kiellot.ytunnus=asiakas.ytunnus)
					WHERE korvaavat_kiellot.yhtio = '{$kukarow["yhtio"]}'
					AND korvaavat_kiellot.ytunnus not in ('','0')
					AND asiakas.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del korvaavuuskieltoa joiden asiakas oli poistettu!<br>";

		// Poistetaan kohteet joiden asiakkaat dellattu
		$query = "	DELETE kohde
					FROM kohde
					LEFT JOIN asiakas ON (kohde.yhtio=asiakas.yhtio and kohde.asiakas=asiakas.tunnus)
					WHERE kohde.yhtio = '{$kukarow["yhtio"]}'
					AND kohde.asiakas > 0
					AND kohde.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del kohdetta joiden asiakas oli poistettu!<br>";

		// Poistetaan synclogit joiden asiakkaat dellattu
		$query = "	DELETE synclog
					FROM synclog
					LEFT JOIN asiakas ON (synclog.yhtio=asiakas.yhtio and synclog.tauluntunnus=asiakas.tunnus)
					WHERE synclog.yhtio = '{$kukarow["yhtio"]}'
					AND synclog.taulu = 'asiakas'
					AND asiakas.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del synclogia joiden asiakas oli poistettu!<br>";
	}

	##################################################################################################################################
	#TUOTETIEDOT
	##################################################################################################################################
	if ($dellataan) {

		// Poistetaan "P" tuotteita joilla ei ole laskutusta eik� saldoa
		$query = "	SELECT tuoteno
					FROM tuote
					WHERE yhtio = '{$kukarow["yhtio"]}'
					AND status  = 'P'";
		$result = pupe_query($query);

		$del = 0;

		while ($row = mysql_fetch_assoc($result)) {

			$poistolukko = "ON";

			onkotapahtumajariveja($row["tuoteno"]);

			if ($poistolukko == "") {
				$query = "	DELETE tuote
							FROM tuote
							WHERE yhtio = '{$kukarow["yhtio"]}'
							and tuoteno = '{$row["tuoteno"]}'";
				$sdtres = pupe_query($query);
				$del++;
			}
		}

		echo "Poistettiin $del 'P'-tuotetta joilla ei ollut yht��n tapahtumaa!<br>";

		// Poistetaan tuotepaikat joiden tuotteet dellattu
		$query = "	DELETE tuotepaikat
					FROM tuotepaikat
					LEFT JOIN tuote ON (tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno)
					WHERE tuotepaikat.yhtio = '{$kukarow["yhtio"]}'
					AND tuotepaikat.saldo   = 0
					AND tuote.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del tuotepaikkaa joiden tuote oli poistettu!<br>";

		// Poistetaan tuoteperheiden lapset joiden tuotteet dellattu
		$query = "	DELETE tuoteperhe
					FROM tuoteperhe
					LEFT JOIN tuote ON (tuoteperhe.yhtio=tuote.yhtio and tuoteperhe.tuoteno=tuote.tuoteno)
					WHERE tuoteperhe.yhtio = '{$kukarow["yhtio"]}'
					AND tuote.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del tuoteperheiden lasta joiden tuote oli poistettu!<br>";

		// Poistetaan tuoteperheet joiden is�tuotteet dellattu
		$query = "	DELETE tuoteperhe
					FROM tuoteperhe
					LEFT JOIN tuote ON (tuoteperhe.yhtio=tuote.yhtio and tuoteperhe.isatuoteno=tuote.tuoteno)
					WHERE tuoteperhe.yhtio = '{$kukarow["yhtio"]}'
					AND tuote.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del tuoteperhett� joiden is�tuote oli poistettu!<br>";

		// Poistetaan tuotteen_alvit joiden tuotteet dellattu
		$query = "	DELETE tuotteen_alv
					FROM tuotteen_alv
					LEFT JOIN tuote ON (tuotteen_alv.yhtio=tuote.yhtio and tuotteen_alv.tuoteno=tuote.tuoteno)
					WHERE tuotteen_alv.yhtio = '{$kukarow["yhtio"]}'
					AND tuote.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del tuotteen alvia joiden tuote oli poistettu!<br>";

		// Poistetaan tuotteen_avainsanat joiden tuotteet dellattu
		$query = "	DELETE tuotteen_avainsanat
					FROM tuotteen_avainsanat
					LEFT JOIN tuote ON (tuotteen_avainsanat.yhtio=tuote.yhtio and tuotteen_avainsanat.tuoteno=tuote.tuoteno)
					WHERE tuotteen_avainsanat.yhtio = '{$kukarow["yhtio"]}'
					AND tuote.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del tuotteen avainsanaa joiden tuote oli poistettu!<br>";

		// Poistetaan tuotteen_orginaalit joiden tuotteet dellattu
		$query = "	DELETE tuotteen_orginaalit
					FROM tuotteen_orginaalit
					LEFT JOIN tuote ON (tuotteen_orginaalit.yhtio=tuote.yhtio and tuotteen_orginaalit.tuoteno=tuote.tuoteno)
					WHERE tuotteen_orginaalit.yhtio = '{$kukarow["yhtio"]}'
					AND tuote.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del tuotteen_orginaalit joiden tuote oli poistettu!<br>";

		// Poistetaan tuotteen_toimittajat joiden tuotteet dellattu
		$query = "	DELETE tuotteen_toimittajat
					FROM tuotteen_toimittajat
					LEFT JOIN tuote ON (tuotteen_toimittajat.yhtio=tuote.yhtio and tuotteen_toimittajat.tuoteno=tuote.tuoteno)
					WHERE tuotteen_toimittajat.yhtio = '{$kukarow["yhtio"]}'
					AND tuote.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del tuotteen toimittajaa joiden tuote oli poistettu!<br>";

		// Poistetaan vastaavat joiden tuotteet dellattu
		$query = "	DELETE vastaavat
					FROM vastaavat
					LEFT JOIN tuote ON (vastaavat.yhtio=tuote.yhtio and vastaavat.tuoteno=tuote.tuoteno)
					WHERE vastaavat.yhtio = '{$kukarow["yhtio"]}'
					AND tuote.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del vastaavat joiden tuote oli poistettu!<br>";

		// Poistetaan vastaavat joiden ketjussa on vain yksi tuote
		$query = "	SELECT id, count(*) maara, group_concat(tunnus) tunnari
					FROM vastaavat
					WHERE yhtio = '{$kukarow["yhtio"]}'
					GROUP BY id
					HAVING maara = 1";
		$result = pupe_query($query);

		$del = 0;

		while ($row = mysql_fetch_assoc($result)) {
			$query = "	DELETE vastaavat
						FROM vastaavat
						WHERE yhtio = '{$kukarow["yhtio"]}'
						AND tunnus  = '{$row["tunnari"]}'";
			$sdtres = pupe_query($query);
			$del++;
		}

		echo "Poistettiin $del vastaavaketjua joissa oli vain yksi tuote!<br>";

		// Poistetaan korvaavat joiden tuotteet dellattu
		$query = "	DELETE korvaavat
					FROM korvaavat
					LEFT JOIN tuote ON (korvaavat.yhtio=tuote.yhtio and korvaavat.tuoteno=tuote.tuoteno)
					WHERE korvaavat.yhtio = '{$kukarow["yhtio"]}'
					AND tuote.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del korvaavat joiden tuote oli poistettu!<br>";

		// Korvaavat joiden ketjussa on vain yksi tuote
		$query = "	SELECT id, count(*) maara, group_concat(tunnus) tunnari
					FROM korvaavat
					WHERE yhtio = '{$kukarow["yhtio"]}'
					GROUP BY id
					HAVING maara = 1";
		$result = pupe_query($query);

		$del = 0;

		while ($row = mysql_fetch_assoc($result)) {
			$query = "	DELETE korvaavat
						FROM korvaavat
						WHERE yhtio = '{$kukarow["yhtio"]}'
						AND tunnus  = '{$row["tunnari"]}'";
			$sdtres = pupe_query($query);
			$del++;
		}

		echo "Poistettiin $del korvaavuusketjua joissa oli vain yksi tuote!<br>";

		// Poistetaan yhteensopivuus_tuotteet joiden tuotteet dellattu
		$query = "	DELETE yhteensopivuus_tuote
					FROM yhteensopivuus_tuote
					LEFT JOIN tuote ON (yhteensopivuus_tuote.yhtio=tuote.yhtio and yhteensopivuus_tuote.tuoteno=tuote.tuoteno)
					WHERE yhteensopivuus_tuote.yhtio = '{$kukarow["yhtio"]}'
					AND tuote.tunnus is null";
		$result = pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del yhteensopivuus_tuoteetta joiden tuote oli poistettu!<br>";

		// Poistetaan yhteensopivuus_tuote_lisatiedot joiden tuotteet dellattu
		$query = "	DELETE yhteensopivuus_tuote_lisatiedot
					FROM yhteensopivuus_tuote_lisatiedot
					LEFT JOIN yhteensopivuus_tuote ON (yhteensopivuus_tuote_lisatiedot.yhtio=yhteensopivuus_tuote.yhtio and yhteensopivuus_tuote_lisatiedot.yhteensopivuus_tuote_tunnus=yhteensopivuus_tuote.tunnus)
					WHERE yhteensopivuus_tuote_lisatiedot.yhtio = '{$kukarow["yhtio"]}'
					AND yhteensopivuus_tuote.tunnus is null";
		$result = pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del yhteensopivuus_tuote_lisatietoa joiden tuote oli poistettu!<br>";

		// Poistetaan synclogit joiden tuotteet dellattu
		$query = "	DELETE synclog
					FROM synclog
					LEFT JOIN tuote ON (synclog.yhtio=tuote.yhtio and synclog.tauluntunnus=tuote.tunnus)
					WHERE synclog.yhtio = '{$kukarow["yhtio"]}'
					AND synclog.taulu = 'tuote'
					AND tuote.tunnus is null";
		$result = pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del synclogia joiden tuote oli poistettu!<br>";

		// Poistetaan liitetiedostot joiden tuotteet dellattu
		$query = "	DELETE liitetiedostot
					FROM liitetiedostot
					LEFT JOIN tuote ON (liitetiedostot.yhtio=tuote.yhtio and liitetiedostot.liitostunnus=tuote.tunnus)
					WHERE liitetiedostot.yhtio = '{$kukarow["yhtio"]}'
					AND liitetiedostot.liitos = 'tuote'
					AND tuote.tunnus is null";
		$result = pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del liitetiedostoa joiden tuote oli poistettu!<br>";

		// Poistetaan hinnastot joiden tuotteet dellattu
		$query = "	DELETE hinnasto
					FROM hinnasto
					LEFT JOIN tuote ON (hinnasto.yhtio=tuote.yhtio and hinnasto.tuoteno=tuote.tuoteno)
					WHERE hinnasto.yhtio = '{$kukarow["yhtio"]}'
					AND tuote.tunnus is null";
		$result = pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del hinnastoa joiden tuote oli poistettu!<br>";

		// Poistetaan budjetti_tuotteet joiden tuotteet dellattu
		$query = "	DELETE budjetti_tuote
					FROM budjetti_tuote
					LEFT JOIN tuote ON (budjetti_tuote.yhtio=tuote.yhtio and budjetti_tuote.tuoteno=tuote.tuoteno)
					WHERE budjetti_tuote.yhtio = '{$kukarow["yhtio"]}'
					AND tuote.tunnus is null";
		$result = pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del budjetti_tuotetta joiden tuote oli poistettu!<br>";

		// Poistetaan asiakashinnat joiden tuotteet dellattu
		$query = "	DELETE asiakashinta
					FROM asiakashinta
					LEFT JOIN tuote ON (asiakashinta.yhtio=tuote.yhtio and asiakashinta.tuoteno=tuote.tuoteno)
					WHERE asiakashinta.yhtio = '{$kukarow["yhtio"]}'
					AND asiakashinta.tuoteno != ''
					AND tuote.tuoteno is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del asiakashintaa joiden tuote oli poistettu!<br>";

		// Poistetaan asiakasalennukset joiden tuotteet dellattu
		$query = "	DELETE asiakasalennus
					FROM asiakasalennus
					LEFT JOIN tuote ON (asiakasalennus.yhtio=tuote.yhtio and asiakasalennus.tuoteno=tuote.tuoteno)
					WHERE asiakasalennus.yhtio = '{$kukarow["yhtio"]}'
					AND asiakasalennus.tuoteno != ''
					AND tuote.tuoteno is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del asiakasalennusta joiden tuote oli poistettu!<br>";

		// Poistetaan toimittajahinnat joiden tuotteet dellattu
		$query = "	DELETE toimittajahinta
					FROM toimittajahinta
					LEFT JOIN tuote ON (toimittajahinta.yhtio=tuote.yhtio and toimittajahinta.tuoteno=tuote.tuoteno)
					WHERE toimittajahinta.yhtio = '{$kukarow["yhtio"]}'
					AND toimittajahinta.tuoteno != ''
					AND tuote.tuoteno is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del toimittajahintaa joiden tuote oli poistettu!<br>";

		// Poistetaan toimittaja-alennukset joiden tuotteet dellattu
		$query = "	DELETE toimittajaalennus
					FROM toimittajaalennus
					LEFT JOIN tuote ON (toimittajaalennus.yhtio=tuote.yhtio and toimittajaalennus.tuoteno=tuote.tuoteno)
					WHERE toimittajaalennus.yhtio = '{$kukarow["yhtio"]}'
					AND toimittajaalennus.tuoteno != ''
					AND tuote.tuoteno is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del toimittaja-alennusta joiden tuote oli poistettu!<br>";
	}

	##################################################################################################################################
	#TOIMITTAJATIEDOT
	##################################################################################################################################
	if ($dellataan) {

		// Poistetaan "P" toimittajat joilla ei ole laskua
		$query = "	DELETE toimi
					FROM toimi
					LEFT JOIN lasku ON (toimi.yhtio=lasku.yhtio and toimi.tunnus=lasku.liitostunnus)
					WHERE toimi.yhtio = '{$kukarow["yhtio"]}'
					AND toimi.tyyppi = 'P'
					AND lasku.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del 'P'-toimittajaa joilla ei ollut yht��n laskua!<br>";

		// Poistetaan yhteyshenkil�t joiden toimittajat dellattu
		$query = "	DELETE yhteyshenkilo
					FROM yhteyshenkilo
					LEFT JOIN toimi ON (yhteyshenkilo.yhtio=toimi.yhtio and yhteyshenkilo.liitostunnus=toimi.tunnus)
					WHERE yhteyshenkilo.yhtio = '{$kukarow["yhtio"]}'
					AND yhteyshenkilo.tyyppi = 'T'
					AND toimi.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del yhteyshenkil�� joiden toimittaja oli poistettu!<br>";

		// Poistetaan budjetti_toimittaja joiden toimittajat dellattu
		$query = "	DELETE budjetti_toimittaja
					FROM budjetti_toimittaja
					LEFT JOIN toimi ON (budjetti_toimittaja.yhtio=toimi.yhtio and budjetti_toimittaja.toimittajan_tunnus=toimi.tunnus)
					WHERE budjetti_toimittaja.yhtio = '{$kukarow["yhtio"]}'
					AND toimi.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del toimittajabudjettia joiden toimittaja oli poistettu!<br>";

		// Poistetaan liitetiedostot joiden toimittajat dellattu
		$query = "	DELETE liitetiedostot
					FROM liitetiedostot
					LEFT JOIN toimi ON (liitetiedostot.yhtio=toimi.yhtio and liitetiedostot.liitostunnus=toimi.tunnus)
					WHERE liitetiedostot.yhtio = '{$kukarow["yhtio"]}'
					AND liitetiedostot.liitos = 'toimi'
					AND toimi.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del liitetiedostoa joiden toimittaja oli poistettu!<br>";

		// Poistetaan tuotteen_toimittajat joiden tuotteet dellattu
		$query = "	DELETE tuotteen_toimittajat
					FROM tuotteen_toimittajat
					LEFT JOIN toimi ON (tuotteen_toimittajat.yhtio=toimi.yhtio and tuotteen_toimittajat.liitostunnus=toimi.tunnus)
					WHERE tuotteen_toimittajat.yhtio = '{$kukarow["yhtio"]}'
					AND toimi.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del tuotteen toimittajaa joiden toimittaja oli poistettu!<br>";

		// Poistetaan toimittajaalet joiden asiakkaat dellattu
		$query = "	DELETE toimittajaalennus
					FROM toimittajaalennus
					LEFT JOIN toimi ON (toimittajaalennus.yhtio=toimi.yhtio and toimittajaalennus.toimittaja=toimi.tunnus)
					WHERE toimittajaalennus.yhtio = '{$kukarow["yhtio"]}'
					AND toimittajaalennus.toimittaja > 0
					AND toimi.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del toimittajaalennusta joiden toimittaja oli poistettu (toimittaja)!<br>";

		// Poistetaan toimittajaalet joiden asiakkaat dellattu
		$query = "	DELETE toimittajaalennus
					FROM toimittajaalennus
					LEFT JOIN toimi ON (toimittajaalennus.yhtio=toimi.yhtio and toimittajaalennus.ytunnus=toimi.ytunnus)
					WHERE toimittajaalennus.yhtio = '{$kukarow["yhtio"]}'
					AND toimittajaalennus.ytunnus not in ('','0')
					AND toimi.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del toimittajaalennusta joiden toimittaja oli poistettu (ytunnus)!<br>";

		// Poistetaan toimittajahinnat joiden asiakkaat dellattu
		$query = "	DELETE toimittajahinta
					FROM toimittajahinta
					LEFT JOIN toimi ON (toimittajahinta.yhtio=toimi.yhtio and toimittajahinta.toimittaja=toimi.tunnus)
					WHERE toimittajahinta.yhtio = '{$kukarow["yhtio"]}'
					AND toimittajahinta.toimittaja > 0
					AND toimi.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del toimittajahintaa joiden toimittaja oli poistettu (toimittaja)!<br>";

		// Poistetaan toimittajahinnat joiden asiakkaat dellattu
		$query = "	DELETE toimittajahinta
					FROM toimittajahinta
					LEFT JOIN toimi ON (toimittajahinta.yhtio=toimi.yhtio and toimittajahinta.ytunnus=toimi.ytunnus)
					WHERE toimittajahinta.yhtio = '{$kukarow["yhtio"]}'
					AND toimittajahinta.ytunnus not in ('','0')
					AND toimi.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del toimittajahintaa joiden toimittaja oli poistettu (ytunnus)!<br>";
	}

	##################################################################################################################################
	#SEKALAISET
	##################################################################################################################################
	if ($dellataan) {
		# Automanual-hakuhistoria
		$query = "	DELETE automanual_hakuhistoria
					FROM automanual_hakuhistoria
					WHERE yhtio = '$kukarow[yhtio]'
					AND luontiaika > 0
					AND luontiaika <= '$vv-$kk-$pp 23:59:59'";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del Automanual-hakuhistoriarivi�.<br>";

		# L�hd�t
		$query = "	DELETE lahdot
					FROM lahdot
					WHERE yhtio = '$kukarow[yhtio]'
					AND pvm > 0
					AND pvm <= '$vv-$kk-$pp'";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del l�ht��.<br>";

		# Kalenteritapahtumat
		$query = "	DELETE kalenteri
					FROM kalenteri
					WHERE yhtio = '$kukarow[yhtio]'
					AND pvmalku > 0
					AND pvmalku   <= '$vv-$kk-$pp 23:59:59'
					AND (pvmloppu <= '$vv-$kk-$pp 23:59:59' OR pvmloppu = 0)";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del kalenteritapahtumaa.<br>";

		# Ker�yser�t
		$query = "	DELETE kerayserat
					FROM kerayserat
					LEFT JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio and tilausrivi.tunnus = kerayserat.tilausrivi)
					WHERE kerayserat.yhtio = '$kukarow[yhtio]'
					AND tilausrivi.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del ker�yser�rivi�.<br>";

		# Budjetit
		$budjettiarray = array("budjetti", "budjetti_asiakas", "budjetti_myyja", "budjetti_toimittaja", "budjetti_tuote");

		foreach ($budjettiarray as $budjettitaulu) {
			$query = "	DELETE $budjettitaulu
						FROM $budjettitaulu
						WHERE yhtio = '$kukarow[yhtio]'
						AND kausi <= '$vv-$kk'";
			pupe_query($query);
			$del = mysql_affected_rows();

			echo "Poistettiin $del {$budjettitaulu}-rivi�.<br>";
		}

		# Kampanjat
		$query = "	DELETE kampanjat
					FROM kampanjat
					WHERE yhtio = '$kukarow[yhtio]'
					AND luontiaika > 0
					AND luontiaika <= '$vv-$kk-$pp 23:59:59'";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del kampanjaa.<br>";

		# Kampanjaehdot
		$query = "	DELETE kampanja_ehdot
					FROM kampanja_ehdot
					LEFT JOIN kampanjat ON (kampanjat.yhtio = kampanja_ehdot.yhtio and kampanjat.tunnus = kampanja_ehdot.kampanja)
					WHERE kampanja_ehdot.yhtio = '$kukarow[yhtio]'
					AND kampanjat.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del kampanjan ehtorivi�.<br>";

		# Kampanjapalkinnot
		$query = "	DELETE kampanja_palkinnot
					FROM kampanja_palkinnot
					LEFT JOIN kampanjat ON (kampanjat.yhtio = kampanja_palkinnot.yhtio and kampanjat.tunnus = kampanja_palkinnot.kampanja)
					WHERE kampanja_palkinnot.yhtio = '$kukarow[yhtio]'
					AND kampanjat.tunnus is null";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del kampanjan palkinotiriv�.<br>";

		# Messenger-viestit
		$query = "	DELETE messenger
					FROM messenger
					WHERE yhtio = '$kukarow[yhtio]'
					AND luontiaika > 0
					AND luontiaika <= '$vv-$kk-$pp 23:59:59'";
		pupe_query($query);
		$del = mysql_affected_rows();

		echo "Poistettiin $del Messenger-viesti�.<br>";
	}
}

require ("inc/footer.inc");
