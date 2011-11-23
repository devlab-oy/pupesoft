<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	// Kutsutaanko CLI:stä
	if (php_sapi_name() != 'cli') {
		die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
	}

	error_reporting(E_ALL);
	ini_set("display_errors", 1);

	require ("inc/connect.inc");
	require ("inc/functions.inc");

	if ($argv[1] == '') {
		echo "Yhtiötä ei ole annettu, ei voida toimia\n";
		die;
	}
	else {
		$kukarow["yhtio"] = $argv[1];
		$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);
	}

	$haku = date("d.m");

	// Ajopäivämääräluokat
	$arvot = array(	1 => array('pvm1'=>'01.01','pvm2'=>''     ,'pvm3'=>''     ,'pvm4'=>''),
					2 => array('pvm1'=>'01.01','pvm2'=>'01.07','pvm3'=>''     ,'pvm4'=>''),
					3 => array('pvm1'=>'01.01','pvm2'=>'01.05','pvm3'=>'01.09','pvm4'=>''),
					4 => array('pvm1'=>'01.01','pvm2'=>'01.04','pvm3'=>'01.07','pvm4'=>'01.10'));
	$syklit = "";

	foreach ($arvot as $nro => $arr) {
		foreach ($arr as $pvm_tag => $paivamaara) {
			if ($paivamaara == $haku) {
				$syklit .= $nro.",";
			}
		}
	}

	$syklit = substr($syklit, 0, -1);

	if (trim($syklit) != "") {

		// 1. nämä toimittajat ovat toimittaneet meille tavaraa ja jotka ovat AKTIIVISIA, ei poistettuja tai poistuvia
		$sql = "	SELECT tuotteen_toimittajat.liitostunnus, toimi.hintojenpaivityssykli, toimi.nimi
					FROM tuotteen_toimittajat
					JOIN toimi ON (toimi.yhtio = tuotteen_toimittajat.yhtio
						AND toimi.tunnus = tuotteen_toimittajat.liitostunnus
						AND toimi.tyyppi not in ('P', 'PP')
						AND toimi.hintojenpaivityssykli in ($syklit))
					WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
					GROUP BY tuotteen_toimittajat.liitostunnus";
		$result = mysql_query($sql) or pupe_error($sql);

		$laheta_meilit = array();

		if (mysql_num_rows($result) > 0) {

			while ($ttrow = mysql_fetch_assoc($result)) {

				// 2. tämä hakee toimittajan tuotteet, jotka ovat aktiivisia tuotteita
				$tuotteet="	SELECT group_concat(distinct tuote.tunnus) lista_tunnuksista
							FROM tuotteen_toimittajat
							JOIN tuote ON (tuote.yhtio = tuotteen_toimittajat.yhtio
									AND tuote.tuoteno = tuotteen_toimittajat.tuoteno
									AND tuote.status not in ('P','X'))
							WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
							AND tuotteen_toimittajat.liitostunnus = '$ttrow[liitostunnus]'";
				$result2 = mysql_query($tuotteet) or pupe_error($tuotteet);
				$tuoterow = mysql_fetch_assoc($result2);

				// 3. haetaan kyseisien tuotteiden tuotepäällikkönro
				$tuotesql="	SELECT DISTINCT tuotepaallikko
							FROM tuote
							WHERE yhtio = '$kukarow[yhtio]'
							AND tunnus in ($tuoterow[lista_tunnuksista])
							AND tuotepaallikko != 0";
				$result3 = mysql_query($tuotesql) or pupe_error($tuotesql);

				while ($henkilot = mysql_fetch_assoc($result3)) {
					$laheta_meilit[$henkilot["tuotepaallikko"]][$ttrow["liitostunnus"]] = $ttrow["nimi"];
				}

			}

			if (count($laheta_meilit) > 0) {

				foreach ($laheta_meilit as $key => $values) {

					$lista = "";
					foreach ($values as $firma => $value) {
						$lista .= "\n- $value";
					}

					// 4. Haetaan ostajan ja tuotepyällikön sähköpostiosoitteet esille.
					$postisql = "	SELECT nimi, eposti
									FROM kuka
									WHERE yhtio = '$kukarow[yhtio]'
									AND myyja = '$key'";
					$resuposti = mysql_query($postisql) or pupe_error($postisql);

					while ($posti = mysql_fetch_assoc($resuposti)) {

						$meili = t("Toimittajien hinnantarkistuspyyntö")."\n";
						$meili .= "\nTervehdys {$posti["nimi"]} \n\n";
						$meili .= t("Pyyntö").":\n".str_replace("\r\n","\n","Tarkista seuraavilta toimittajilta hinnat Pupesoftiin\n");
						$meili .= $lista;

						if ($posti['eposti'] == "") {
							$email_osoite = $yhtiorow['alert_email'];
						}
						else {
							$email_osoite = $posti['eposti'];
						}

						$tulos = mail($email_osoite, mb_encode_mimeheader(t("Hinnan tarkistuspyyntö")." $yhtiorow[nimi]", "ISO-8859-1", "Q"), $meili, "From: ".mb_encode_mimeheader($yhtiorow["postittaja_email"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
					}
				}
			}
		}
	}

?>