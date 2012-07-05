<?php

	// Laitetaan riittävästi muistia
	ini_set("memory_limit", "5G");

	// Kutsutaanko CLI:stä
	$php_cli = FALSE;

	if (php_sapi_name() == 'cli' or isset($editil_cli)) {
		$php_cli = TRUE;
	}

	if ($php_cli) {

		if (!isset($argv[1]) or $argv[1] == '') {
			echo "Anna yhtiö!!!\n";
			die;
		}

		// otetaan includepath aina rootista
		ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(__FILE__)).PATH_SEPARATOR."/usr/share/pear");
		error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
		ini_set("display_errors", 0);

		// otetaan tietokanta connect
		require("inc/connect.inc");
		require("inc/functions.inc");

		// hmm.. jännää
		$kukarow['yhtio'] = $argv[1];

		//Pupeasennuksen root
		$pupe_root_polku = dirname(dirname(__FILE__));

		$query    = "SELECT * from yhtio where yhtio='$kukarow[yhtio]'";
		$yhtiores = pupe_query($query);

		if (mysql_num_rows($yhtiores) == 1) {
			$yhtiorow = mysql_fetch_assoc($yhtiores);

			// haetaan yhtiön parametrit
			$query = "  SELECT *
						FROM yhtion_parametrit
						WHERE yhtio = '$yhtiorow[yhtio]'";
			$result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

			if (mysql_num_rows($result) == 1) {
				$yhtion_parametritrow = mysql_fetch_assoc($result);

				// lisätään kaikki yhtiorow arrayseen, niin ollaan taaksepäinyhteensopivia
				foreach ($yhtion_parametritrow as $parametrit_nimi => $parametrit_arvo) {
					$yhtiorow[$parametrit_nimi] = $parametrit_arvo;
				}
			}
		}
		else {
			die ("Yhtiö $kukarow[yhtio] ei löydy!");
		}
	}
	else {
		echo "Voidaan ajaa vain komentoriviltä!!!\n";
		die;
	}

	//monenko päivän takaa haetaan mm myynnit ja ostot skriptin ajohetkellä, älä aseta isommaksi kuin 2
	$ajopaiva = 1;

	// 1 = maanantai, 7 = sunnuntai
	$weekday = date("N");
	$weekday = $weekday-$ajopaiva;

	if ($weekday <= 0 OR $weekday == 6 OR $weekday == 7) {
		// tällä hetkellä aineiston saa ainoastaan ma-pe päiviltä
		echo "\n\nTätä skriptiä voi ajaa vain arkipäiviltä!\n\n";
		die;
	}

	$tanaan = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d")-$ajopaiva, date("Y")));

	//xf02:sta varten otetaan korvatut vuorokauden myöhemmin, eli esim maanantaina korvattu esitetään xf02:ssa tiistain aineistossa
	$ajopaiva++;

	if ($weekday == 1) {
		// ma aineistoon korvatut perjantailta. Su->La->Pe eli + 2
		$ajopaiva += 2;
	}

	$edellinen_arki = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d")-$ajopaiva, date("Y")));

	//rajaukset
	$tuoterajaukset = " AND tuote.status != 'P' AND tuote.ei_saldoa = '' AND tuote.tuotetyyppi = '' ";
	$toimirajaus 	= " AND toimi.oletus_vienti in ('C','F','I')";

	$path = "/home/e3_rajapinta/e3siirto_siirto_".date("Ymd")."_$yhtiorow[yhtio]/";

	# siivotaan yli 7 päivää vanhat aineistot
	system("find /home/e3_rajapinta/ -mtime +7 -delete");

	// Tehään uysi dirikka
	system("mkdir $path");

	$path_xauxi = $path.'XAUXI';
	$path_xlto  = $path.'XLT0';
	$path_wswp  = $path.'XSWP';
	$path_xvni  = $path.'XVNI';
	$path_xf04  = $path.'XF04';
	$path_xf01  = $path.'XF01';
	$path_xf02  = $path.'XF02';

	echo "E3rajapinta siirto: $yhtiorow[yhtio]\n";

	// Ajetaan kaikki operaatiot
	xauxi($tanaan);
	xlto($tanaan);
	xswp($tanaan, "");
	xvni($tanaan);
	xf04($tanaan);
	xf01($tanaan);
	xf02($tanaan, xswp($edellinen_arki, "yes"));

	//Siirretään failit e3 palvelimelle
	siirto($path_xf01,  "E3XF01NP");
	siirto($path_xf02,  "E3XF02NP");
	siirto($path_xf04,  "E3XF04NP");
	siirto($path_xvni,  "E3XVNINP");
	siirto($path_xauxi, "E3XAUXINP");
	siirto($path_xlto,  "E3XLT0NP");
	siirto($path_wswp,  "E3XSWPMWNP");
	siirto("", "", "RCMD E3nattsbm");

	function siirto ($ftpfile, $renameftpfile, $komento = "") {
		GLOBAL $e3_params, $yhtiorow;

		$ftphost 	= $e3_params[$yhtiorow["yhtio"]]["ftphost"];
		$ftpuser 	= $e3_params[$yhtiorow["yhtio"]]["ftpuser"];
		$ftppass 	= $e3_params[$yhtiorow["yhtio"]]["ftppass"];
		$ftppath 	= $e3_params[$yhtiorow["yhtio"]]["ftppath"];
		$ftpport 	= "";
	    $ftpfail 	= "";
		$ftpsucc 	= "";
		$syy		= "";
		$palautus	= 0;

		//lähetetään tiedosto
		$conn_id = ftp_connect($ftphost);

		// jos connectio ok, kokeillaan loginata
		if ($conn_id) {
			$login_result = ftp_login($conn_id, $ftpuser, $ftppass);
		}

		// jos login ok kokeillaan uploadata
		if ($login_result) {

			// käytetään active modea
			ftp_pasv($conn_id, FALSE);

			if ($ftpfile != "" and $renameftpfile != "") {

				// jos viimeinen merkki pathissä ei ole kauttaviiva lisätään kauttaviiva...
				if (substr($ftppath, -1) != "/") {
					$ftppath .= "/";
				}

				$renameftpfile	= isset($renameftpfile) ? basename(trim($renameftpfile)) : "";
				$filenimi		= basename($ftpfile);

				// Dellataan olemassaoleva faili eka jos se siellä jostain syystä jo on
				$delete = @ftp_raw($conn_id, "DLTF {$ftppath}{$filenimi}");

				$upload = ftp_put($conn_id, $ftppath.$filenimi, realpath($ftpfile), FTP_ASCII);

				// Pitääkö faili vielä nimetä kokonaan uudestaan
				if ($upload === TRUE) {
					$delete = @ftp_raw($conn_id, "DLTF {$ftppath}{$renameftpfile}");

					$rename = ftp_raw($conn_id, "RNFR {$ftppath}{$filenimi}");
					$rename = ftp_raw($conn_id, "RNTO {$ftppath}{$renameftpfile}");

					if (stripos($rename[0], "renamed as") === FALSE) {
						$rename = FALSE;
					}
				}
			}

			if ($komento != "") {
				$cmd = ftp_raw($conn_id, $komento);
			}
		}

		if ($conn_id) {
			ftp_close($conn_id);
		}

		// mikä feilas?
		if (isset($conn_id) and $conn_id === FALSE) {
			$palautus = 1;
		}
		if (isset($login_result) and $login_result === FALSE) {
			$palautus = 2;
		}
		if (isset($upload) and $upload === FALSE) {
			$palautus = 3;
		}
		if (isset($rename) and $rename === FALSE) {
			$palautus = 4;
		}

		// jos siirto epäonnistuu
		if ($palautus != 0) {
			// ncftpput:in exit valuet
			switch ($palautus) {
				case  1:
					$syy = "Could not connect to remote host. ($ftphost)";
					break;
				case  2:
					$syy = "Could not login to remote host ($ftpuser, $ftppass)";
					break;
				case  3:
					$syy = "Transfer failed ($ftppath, ".realpath($ftpfile).")";
					break;
				case  4:
					$syy = "Rename failed ($ftppath, {$ftppath}{$filenimi} --> {$ftppath}{$renameftpfile})";
					break;
				default:
					$syy = t("Tuntematon errorkoodi")." ($palautus)!!";
			}

			$rivi  = "$PHP_SELF\n";
			$rivi .= "\n";
			$rivi .= t("Tiedoston")." '$ftpfile' ".t("lähetys epäonnistui")."!\n";
			$rivi .= "\n";
			$rivi .= "$cmd\n";
			$rivi .= "\n";
			$rivi .= "$syy\n";

			$boob = mail($yhtiorow['alert_email'], mb_encode_mimeheader(t("Tiedostonsiirto epäonnistui")."!", "ISO-8859-1", "Q"), $rivi, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
		}
	}

	function xauxi($tanaan) {
		global $path_xauxi, $yhtiorow, $tuoterajaukset, $toimirajaus;

		echo "TULOSTETAAN xauxi...\n";

		//viedään nimityksen sijaan lyhytkuvaus -Satu 8.2.12
		$query = "	SELECT 	tuote.tuoteno AS tuoteno,
							tuote.lyhytkuvaus AS tuotenimi,
							(
								SELECT korv.tuoteno
								FROM korvaavat AS korv
								WHERE korv.yhtio = tuote.yhtio
								AND korv.id = korvaavat.id
								ORDER BY if(korv.jarjestys = 0, 9999, korv.jarjestys), korv.tuoteno
								LIMIT 1
							) korvaavatuoteno
					FROM tuote
					LEFT JOIN korvaavat ON (korvaavat.yhtio = tuote.yhtio AND korvaavat.tuoteno = tuote.tuoteno)
					WHERE tuote.yhtio = '$yhtiorow[yhtio]' $tuoterajaukset AND tuote.ostoehdotus = ''
					HAVING (korvaavatuoteno = tuote.tuoteno OR korvaavatuoteno is null)";
		$rested = mysql_query($query) or pupe_error($query);
		$rows = mysql_num_rows($rested);

		$fp = fopen($path_xauxi, 'w+');

		$row = 0;

		while ($tuote = mysql_fetch_assoc($rested)) {

			$query = "	SELECT toimi.toimittajanro as toimittaja, toimi.tyyppi
						FROM tuotteen_toimittajat
						JOIN toimi on (toimi.yhtio=tuotteen_toimittajat.yhtio AND tuotteen_toimittajat.liitostunnus = toimi.tunnus $toimirajaus)
						WHERE tuotteen_toimittajat.yhtio = '$yhtiorow[yhtio]'
						AND tuotteen_toimittajat.tuoteno = '$tuote[tuoteno]'
						ORDER BY if(tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys), tuotteen_toimittajat.tunnus
						LIMIT 1";
			$tutoq = mysql_query($query) or pupe_error($query);
			$tuto = mysql_fetch_assoc($tutoq);

			if ($tuto['toimittaja'] == '' or $tuto['tyyppi'] == 'P') continue;

			// mones tämä on
			$row++;

			$nimitys = $tuote['tuotenimi'];
			$order   = array("\r\n", "\n", "\r", "\t");
			$nimitys = str_replace($order, ' ', $nimitys);
			$nimitys = preg_replace("~\s+~", " ", $nimitys);

			$out  = sprintf("%-3.3s",		"E3T");
			$out .= sprintf("%-18.18s",		$tuote['tuoteno']);
			$out .= sprintf("%-3.3s",		"001");
			$out .= sprintf("%-8.8s",		$tuto['toimittaja']);
			$out .= sprintf("%-45.45s",		$nimitys);

			if (! fwrite($fp, $out . "\n")) {
				echo "Failed writing row.\n";
				die();
			}

			$progress = floor(($row/$rows) * 40);
			$str = sprintf("%10s", "$row/$rows");

			$hash = '';
			for ($i=0; $i < (int) $progress; $i++) {
				$hash .= "#";
			}
		}

		fclose($fp);
	}

	function xlto($tanaan) {
		global $path_xlto, $yhtiorow, $tuoterajaukset, $toimirajaus;

		//Item Lead Time File XLT0 (toimitusaikaseuranta)
		//Tiedot päivän aikaan tehdyistä tuloutuksista, jotka tehty E3:ssa syntynyttä tilausta vastaan
		//tony: tein muutokset luonti ja lahete tietojen sisältöön. Sekä myös laskun tila semmoiseksi että on kyse tuloutetuista ostokeikasta
		//jätetää täst aineistost ostoehdotus EI:t pois -satu 17-2-12

		echo "TULOSTETAAN xlt0...\n";

		$query = "	SELECT
						tilausrivi.tuoteno tuoteno,
						DATE_FORMAT(tilausrivi.laskutettuaika,'%Y%m%d') luonti,
						DATE_FORMAT(tilausrivi.laadittu,'%Y%m%d') lahete,
						tilausrivin_lisatiedot.tilausrivitunnus,
						(
							SELECT korv.tuoteno
							FROM korvaavat AS korv
							WHERE korv.yhtio = tuote.yhtio
							AND korv.id = korvaavat.id
							ORDER BY if(korv.jarjestys = 0, 9999, korv.jarjestys), korv.tuoteno
							LIMIT 1
						) korvaavatuoteno,
					sum(round(tilausrivi.kpl)) kpl
					FROM tilausrivi
					LEFT JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio AND tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivilinkki)
					JOIN tuote use index (tuoteno_index) ON (tilausrivi.yhtio = tuote.yhtio AND tilausrivi.tuoteno=tuote.tuoteno $tuoterajaukset AND tuote.ostoehdotus = '')
					JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.uusiotunnus AND lasku.tila = 'K' AND lasku.alatila != 'I')
					LEFT JOIN korvaavat ON (korvaavat.yhtio = tuote.yhtio AND korvaavat.tuoteno = tuote.tuoteno)
					WHERE tilausrivi.yhtio	= '$yhtiorow[yhtio]'
					AND tilausrivi.tyyppi = 'O'
					AND tilausrivi.laskutettuaika = '$tanaan'
					GROUP BY tuoteno, luonti, lahete, tilausrivitunnus, korvaavatuoteno
					HAVING tilausrivin_lisatiedot.tilausrivitunnus is null AND (korvaavatuoteno = tilausrivi.tuoteno OR korvaavatuoteno is null)";
		$rest = mysql_query($query) or pupe_error($query);

		$rows	= mysql_num_rows($rest);
		$row	= 0;
		$fp		= fopen($path_xlto, 'w+');

		while ($xlto = mysql_fetch_assoc($rest)) {

			if ($xlto['kpl'] == 0 or $xlto['kpl'] < 0) continue;

			$query = "	SELECT toimi.toimittajanro AS toimittaja, toimi.tyyppi
						FROM tuotteen_toimittajat
						JOIN toimi on (toimi.yhtio=tuotteen_toimittajat.yhtio AND tuotteen_toimittajat.liitostunnus = toimi.tunnus $toimirajaus)
						WHERE tuotteen_toimittajat.yhtio = '$yhtiorow[yhtio]'
						AND tuotteen_toimittajat.tuoteno = '$xlto[tuoteno]'
						ORDER BY if(tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys), tuotteen_toimittajat.tunnus
						LIMIT 1";
			$tutoq = mysql_query($query) or pupe_error($query);
			$tuto = mysql_fetch_assoc($tutoq);

			//tyhjät pois ja jos ostorivin toimittaja ei oo päätoimittaja, ni skipataan (vain päätoimittajan ostoja e3)
			if ($tuto['toimittaja'] == '' or $tuto['tyyppi'] == 'P') continue;

			// mones tämä on
			$row++;

			$out   = sprintf("%-8.8s", $tuto['toimittaja']);	//LTVNDR
			$out  .= sprintf("%-18.18s", $xlto['tuoteno']);		//LTITEM
			$out  .= sprintf("%-3.3s", "001");					//LTWHSE
			$out  .= sprintf("%07.7s", $xlto['kpl']);			//LTRQTY
			$out  .= sprintf("%-8.8s", $xlto['luonti']);		//LTRCDT		oikeasti saapunut.määrä
			$out  .= sprintf("%-8.8s", $xlto['lahete']);		//LTORDT		oikeasti tilauspvm

			if (! fwrite($fp, $out . "\n")) {
				echo "Failed writing row.\n";
				die();
			}
		}

		fclose($fp);
	}

	function xswp($tanaan, $korvatut) {
		global $path_wswp, $yhtiorow, $tuoterajaukset, $toimirajaus;

		echo "TULOSTETAAN xswp...\n";

		$query = " SELECT korvaavat.id,
				   tuote.tuoteno,
				   korvaavat.jarjestys,
				   (
				   	SELECT korv.tuoteno
				   	FROM korvaavat AS korv
				   	WHERE korv.yhtio = tuote.yhtio
				   	AND korv.id = korvaavat.id
				   	ORDER BY if(korv.jarjestys = 0, 9999, korv.jarjestys), korv.tuoteno
				   	LIMIT 1
				   ) korvaavatuoteno
				   FROM tuote
				   JOIN korvaavat ON (tuote.yhtio = korvaavat.yhtio AND tuote.tuoteno = korvaavat.tuoteno AND date(korvaavat.luontiaika) = '$tanaan')
				   WHERE tuote.yhtio = '$yhtiorow[yhtio]' $tuoterajaukset AND tuote.ostoehdotus = ''
				   HAVING tuote.tuoteno = korvaavatuoteno";
		$rest = mysql_query($query) or pupe_error($query);
		$rows = mysql_num_rows($rest);
		$row  = 0;

		if ($korvatut == "") $fp = fopen($path_wswp, 'w+');

		$xf02loppulause = '';

		while ($korvaavat = mysql_fetch_assoc($rest)) {

			// mones tämä on
			$row++;

			$query = " SELECT RPAD(toimi.toimittajanro,7,' ') AS toimittaja, toimi.tyyppi, RPAD(tuotteen_toimittajat.tunnus, 7, ' ') AS tutotunnus
					   FROM tuotteen_toimittajat
					   JOIN toimi on (toimi.yhtio=tuotteen_toimittajat.yhtio AND tuotteen_toimittajat.liitostunnus = toimi.tunnus $toimirajaus)
					   WHERE tuotteen_toimittajat.yhtio = '$yhtiorow[yhtio]'
					   AND tuotteen_toimittajat.tuoteno = '$korvaavat[tuoteno]'
					   ORDER BY if(tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys), tuotteen_toimittajat.tunnus
					   LIMIT 1";
			$tutoq = mysql_query($query) or pupe_error($query);
			$tuto = mysql_fetch_assoc($tutoq);

			if ($tuto['toimittaja'] == '' or $tuto['tyyppi'] == 'P') continue;

			$query = "			SELECT korvaavat.tuoteno
								FROM korvaavat
								WHERE korvaavat.yhtio = '$yhtiorow[yhtio]' AND korvaavat.id = '$korvaavat[id]' AND if(korvaavat.jarjestys = 0, 9999, korvaavat.jarjestys) >= '$korvaavat[jarjestys]' AND korvaavat.tuoteno != '$korvaavat[tuoteno]'
								ORDER BY if(korvaavat.jarjestys = 0, 9999, korvaavat.jarjestys), korvaavat.tuoteno
								LIMIT 1";
			$korvaavaresult = mysql_query($query) or pupe_error($query);
			$korvaavarows = mysql_num_rows($korvaavaresult);
			if ($korvaavarows == 0) continue;
			$korvaava = mysql_fetch_assoc($korvaavaresult);

			$query2 = " SELECT RPAD(toimi.toimittajanro,7,' ') AS toimittaja, toimi.tyyppi, RPAD(tuotteen_toimittajat.tunnus, 7, ' ') AS tutotunnus
					   	FROM tuotteen_toimittajat
					   	JOIN toimi on (toimi.yhtio=tuotteen_toimittajat.yhtio AND tuotteen_toimittajat.liitostunnus = toimi.tunnus $toimirajaus)
					   	WHERE tuotteen_toimittajat.yhtio = '$yhtiorow[yhtio]'
					   	AND tuotteen_toimittajat.tuoteno = '$korvaava[tuoteno]'
					   	ORDER BY if(tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys), tuotteen_toimittajat.tunnus
					   	LIMIT 1";
			$tutoq2 = mysql_query($query2) or pupe_error($query2);
			$tuto2 = mysql_fetch_assoc($tutoq2);

			if ($tuto2['toimittaja'] == '' or $tuto2['tyyppi'] == 'P') continue;

			if ($korvatut != "") {
				// laitetaan xf02 loppuun tieto mikä tuote on poistettu. toimittaja ja tuoteno.
				$xf02loppulause .= "$tuto2[toimittaja] ".str_pad($korvaava['tuoteno'],17)." 001000000000000000000000000000000000000000000000000000000                                                        D\n";
			}
			else {
				// eka korvatun toimittaja + tuoteno, sitten korvaavan toimittaja ja tuoteno
				$lause = "E3T001$tuto2[toimittaja] ".str_pad($korvaava['tuoteno'],17)." 001$tuto[toimittaja] ".str_pad($korvaavat['tuoteno'],17)." U1    000000000000000000000000000AYNY";

				if (!fwrite($fp, $lause . "\n")) {
					echo "Failed writing row.\n";
					die();
				}

				$progress = floor(($row/$rows) * 40);
				$str = sprintf("%10s", "$row/$rows");
				$hash = '';

				for ($i=0; $i < (int) $progress; $i++) {
					$hash .= "#";
				}
			}
		}
		if ($korvatut != "") return $xf02loppulause;
		else fclose($fp);
	}

	function xvni($tanaan) {
		global $path_xvni, $yhtiorow, $tuoterajaukset, $toimirajaus;

		echo "TULOSTETAAN XVNI...\n";

		$qxvni = "	SELECT toimi.toimittajanro AS toimittaja, toimi.nimi nimi, SUBSTRING(toimi.nimi, 1, 18) lyhytnimi
					FROM toimi
					WHERE toimi.yhtio = '$yhtiorow[yhtio]'
					AND toimi.toimittajanro not in ('0','')
					AND tyyppi = '' $toimirajaus
					ORDER BY 1";
		$resto = mysql_query($qxvni) or pupe_error($qxvni);
		$rows = mysql_num_rows($resto);

		$fp = fopen($path_xvni, 'w+');

		while ($xvni = mysql_fetch_assoc($resto)) {

			$out = sprintf("%-3.3s", "E3T");					//XVCOMP
			$out .= sprintf("%-3.3s", "001");					//XVWHSE
			$out .= sprintf("%-8.8s", $xvni['toimittaja']);		//XVVNDR
			$out .= sprintf("%-4.4s", "");						//XVSUBV
			$out .= sprintf("%-30.30s", $xvni['nimi']);			//XVNAME
			$out .= sprintf("%-18.18s", $xvni['lyhytnimi']);	//XVSEQ
			$out .= sprintf("%-1.1s",	"");					//XVTYPE
			$out .= sprintf("%0-1.1s",	"");					//XVTYPU
			$out .= sprintf("%-3.3s",	"");					//XVREGN
			$out .= sprintf("%-8.8s",	"");					//XVSUPV
			$out .= sprintf("%0-1.1s",	"");					//XVSUPU
			$out .= sprintf("%-3.3s",	"");					//XVBUYR
			$out .= sprintf("%-3.3s",	"");					//XVGRP1
			$out .= sprintf("%-1.1s",	"1");					//XVGR1U
			$out .= sprintf("%-3.3s",	"");					//XVGRP2
			$out .= sprintf("%-1.1s",	"0");					//XVGR2U
			$out .= sprintf("%-3.3s",	"");					//XVGRP3
			$out .= sprintf("%-1.1s",	"0");					//XVGR3U
			$out .= sprintf("%-3.3s",	"");					//XVGRP4
			$out .= sprintf("%-1.1s",	"0");					//XVGR4U
			$out .= sprintf("%-3.3s",	"");					//XVCURC
			$out .= sprintf("%-7.7s",	"");					//XVORDY
			$out .= sprintf("%-1.1s",	"0");					//XVORDU
			$out .= sprintf("%-1.1s",	"");					//XVORWK
			$out .= sprintf("%-1.1s",	"0");					//XVORWU
			$out .= sprintf("%-2.2s",	"");					//XVORMN
			$out .= sprintf("%-1.1s",	"0");					//XVORMU
			$out .= sprintf("%-1.1s",	"5");					//XVAUTO
			$out .= sprintf("%-1.1s",	"1");					//XVAUTU
			$out .= sprintf("%0-3.3s",	"");					//XVLTQT
			$out .= sprintf("%0-5.5s",	"");					//XVLTFR
			$out .= sprintf("%0-3.3s",	"");					//XVLTVR
			$out .= sprintf("%0-3.3s",	"");					//XVSRVG
			$out .= sprintf("%0-9.9s",	"");					//XVHEDC
			$out .= sprintf("%0-9.9s",	"");					//XVLINC
			$out .= sprintf("%0-3.3s",	"");					//XVCAPR
			$out .= sprintf("%0-3.3s",	"");					//XVPHYR
			$out .= sprintf("%0-3.3s",	"");					//XVOTHR
			$out .= sprintf("%0-3.3s",	"");					//XVDFGM
			$out .= sprintf("%-78.78s",	"");					//XVCMNT
			$out .= sprintf("%-1.1s",	"0");					//XVAMBT
			$out .= sprintf("%-1.1s",	"0");					//XVABTU
			$out .= sprintf("%-1.1s",	"0");					//XVACTB
			$out .= sprintf("%-1.1s",	"0");					//XVACBU
			$out .= sprintf("%-1.1s",	"");					//XVDLTV
			$out .= sprintf("%-30.30s",	"");					//XVUSER
			$out .= sprintf("%-1.1s",	"0");					//XVUSRU

			if (!fwrite($fp, $out . "\n")) {
				echo "Failed writing row.\n";
				die();
			}
		}

		fclose($fp);
	}

	function xf04($tanaan) {
		global $path_xf04, $yhtiorow, $tuoterajaukset, $toimirajaus;

		echo "TULOSTETAAN xf04...\n";

		// jos kirjaimet on A-I niin homma toimii, jos on enemmän niin homma kusee.

		$qxf04 = "	SELECT tuote.tuoteno as tuoteno, tuote.yksikko as yksikko , tuote.try as try,
					tm.luokka,
					if(tm.luokka=1,'A',if(tm.luokka=2,'B',if(tm.luokka=3,'C',if(tm.luokka=4,'D',if(tm.luokka=5,'E',if(tm.luokka=6,'F',if(tm.luokka=7,'G',if(tm.luokka=8,'H','I')))))))) as 'MYYNNINABC',
					tr.luokka,
					if(tr.luokka=1,'A',if(tr.luokka=2,'B',if(tr.luokka=3,'C',if(tr.luokka=4,'D',if(tr.luokka=5,'E',if(tr.luokka=6,'F',if(tr.luokka=7,'G',if(tr.luokka=8,'H','I')))))))) as 'POKEABC',
					(
						SELECT korv.tuoteno
						FROM korvaavat AS korv
						WHERE korv.yhtio = tuote.yhtio
						AND korv.id = korvaavat.id
						ORDER BY if(korv.jarjestys = 0, 9999, korv.jarjestys), korv.tuoteno
						LIMIT 1
					) korvaavatuoteno
					FROM tuote
					LEFT JOIN abc_aputaulu tm ON (tm.yhtio = tuote.yhtio AND tm.tuoteno = tuote.tuoteno AND tm.tyyppi = 'TM')
					LEFT JOIN abc_aputaulu tr ON (tr.yhtio = tuote.yhtio AND tr.tuoteno = tuote.tuoteno AND tr.tyyppi = 'TR')
					LEFT JOIN korvaavat ON (korvaavat.yhtio = tuote.yhtio AND korvaavat.tuoteno = tuote.tuoteno)
					WHERE tuote.yhtio = '$yhtiorow[yhtio]' $tuoterajaukset AND tuote.ostoehdotus = ''
					HAVING (korvaavatuoteno = tuote.tuoteno OR korvaavatuoteno is null)
					ORDER BY tuote.tuoteno";
		$resto = mysql_query($qxf04) or pupe_error($qxf04);
		$rows = mysql_num_rows($resto);

		$fp = fopen($path_xf04, 'w+');

		while ($xf04 = mysql_fetch_assoc($resto)) {

			$query = "	SELECT tuotteen_toimittajat.toim_tuoteno as ttuoteno, toimi.toimittajanro AS toimittaja, toimi.tyyppi
						FROM tuotteen_toimittajat
						JOIN toimi on (toimi.yhtio=tuotteen_toimittajat.yhtio AND tuotteen_toimittajat.liitostunnus = toimi.tunnus $toimirajaus)
						WHERE tuotteen_toimittajat.yhtio = '$yhtiorow[yhtio]'
						AND tuotteen_toimittajat.tuoteno = '$xf04[tuoteno]'
						ORDER BY if(tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys), tuotteen_toimittajat.tunnus
						LIMIT 1";
			$tutoq = mysql_query($query) or pupe_error($query);
			$tuto = mysql_fetch_assoc($tutoq);

			if ($tuto['toimittaja'] == '' or $tuto['tyyppi'] == 'P') continue;

     		$out 	 = sprintf("%-8.8s", $tuto['toimittaja']);		//XVNDR
			$out   	.= sprintf("%-18.18s",$xf04['tuoteno']);		//XITEM
			$out 	.= sprintf("%-3.3s", "001");					//XWHSE
			$out 	.= sprintf("%-4.4s","");						//XSUBV
			$out 	.= sprintf("%-3.3s","");						//XREGON
			$out 	.= sprintf("%-25.25s",$tuto['ttuoteno']);		//XMFGID
			$out 	.= sprintf("%-10.10s",$xf04['yksikko']);		//XPKSIZ
			$out 	.= sprintf("%-15.15s", "");						//XUPC
			$out 	.= sprintf("%-10.10s", "");						//XTIEHI
			$out 	.= sprintf("%07.7s", "0");						//XQRSVR
			$out 	.= sprintf("%01.1s", "1");						//XURSVR
			$out 	.= sprintf("%07.7s", "0");						//XQHELD
			$out 	.= sprintf("%08.8s", "0");						//XOHHDT
			$out 	.= sprintf("%01.1s", "0");						//XUHELD
			$out 	.= sprintf("%07.7s", "0");						//XRVUNT
			$out 	.= sprintf("%07.7s", "0");						//XCNVPK
			$out 	.= sprintf("%03.3s", "0");						//XCNVML
			$out 	.= sprintf("%03.3s", "0");						//XCNVBP
			$out 	.= sprintf("%05.5s", "1");						//XCUBDV
			$out 	.= sprintf("%05.5s", "1");						//XWGTDV
			$out 	.= sprintf("%05.5s", "0");						//XCASUN
			$out 	.= sprintf("%05.5s", "0");						//XLAYUN
			$out 	.= sprintf("%05.5s", "0");						//XPALUN
			$out 	.= sprintf("%05.5s", "0");						//XUNITS
			$out 	.= sprintf("%05.5s", "0");						//XSLSPK
			$out 	.= sprintf("%05.5s", "1");						//XPRDIV
			$out 	.= sprintf("%03.3s", "0");						//XSHELF
			$out 	.= sprintf("%05.5s", "0");						//XLTFOR
			$out 	.= sprintf("%03.3s", "0");						//XSRVGL
			$out 	.= sprintf("%05.5s", "0");						//XPODIV
			$out 	.= sprintf("%0-2.2s", "0");						//XPOUOM
			$out 	.= sprintf("%-5.5s",substr($xf04['try'],0,1));	//XGRP1
			$out 	.= sprintf("%-5.5s",substr($xf04['try'],1,2));	//XGRP2
			$out 	.= sprintf("%-5.5s",substr($xf04['try'],3,6));	//XGRP3
			$out 	.= sprintf("%-5.5s",$xf04['POKEABC']);			//XGRP4 $xf04['MYYNNINABC']);
			$out 	.= sprintf("%-5.5s",$xf04['MYYNNINABC']);		//XGRP5 $xf04['POKEABC']);
			$out 	.= sprintf("%-5.5s","");						//XGRP6
			$out 	.= sprintf("%0-3.3s", "0");						//XOOPNT

			if (!fwrite($fp, $out . "\n")) {
				echo "Failed writing row.\n";
				die();
			}
		}

		fclose($fp);
	}

	function xf01($tanaan) {
		global $path_xf01, $yhtiorow, $tuoterajaukset, $toimirajaus;

		echo "TULOSTETAAN xf01...\n";

		$Q1 = " SELECT tuote.tuoteno,
				tuote.status,
				(
					SELECT korv.tuoteno
					FROM korvaavat AS korv
					WHERE korv.yhtio = tuote.yhtio
					AND korv.id = korvaavat.id
					ORDER BY if(korv.jarjestys = 0, 9999, korv.jarjestys), korv.tuoteno
					LIMIT 1
				) korvaavatuoteno,
				tuote.ostoehdotus,
				(
					SELECT ROUND(sum(tuotepaikat.saldo),0) saldo
					FROM tuotepaikat
					JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
					AND concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
					AND concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
					AND varastopaikat.tyyppi = '')
					WHERE tuotepaikat.yhtio = tuote.yhtio
					AND tuotepaikat.tuoteno = tuote.tuoteno
				) saldo
				FROM tuote
				LEFT JOIN korvaavat ON (korvaavat.yhtio = tuote.yhtio AND korvaavat.tuoteno = tuote.tuoteno)
				WHERE tuote.yhtio = '$yhtiorow[yhtio]' $tuoterajaukset AND tuote.ostoehdotus = ''
				GROUP BY tuote.tuoteno, tuote.status, korvaavatuoteno
				HAVING (korvaavatuoteno = tuote.tuoteno OR korvaavatuoteno is null)";
		$rests = mysql_query($Q1) or pupe_error($Q1);
		$rows = mysql_num_rows($rests);

		$fp = fopen($path_xf01, 'w+');

		while ($tuoterow = mysql_fetch_assoc($rests)) {

			$toimittajaquery = "	SELECT toimi.toimittajanro AS toimittaja, toimi.tyyppi
									FROM tuotteen_toimittajat
									JOIN toimi on (toimi.yhtio=tuotteen_toimittajat.yhtio AND tuotteen_toimittajat.liitostunnus = toimi.tunnus $toimirajaus)
									WHERE tuotteen_toimittajat.yhtio = '$yhtiorow[yhtio]'
									AND tuotteen_toimittajat.tuoteno = '$tuoterow[tuoteno]'
									ORDER BY if(tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys), tuotteen_toimittajat.tunnus
									LIMIT 1";
			$toimires = mysql_query($toimittajaquery) or pupe_error($toimittajaquery);
			$toimirow = mysql_fetch_assoc($toimires);

			if ($toimirow['toimittaja'] == '' or $toimirow['tyyppi'] == 'P') continue;

			//Jos ostoehdotus on kyllä, siirretään myyntilukuja. Muuten ei.
			//Myynnit vaan "normaaleist" varastoist, tsekataa vaa varastopaikat-taulusta tyyppi '':stä myydyt
			//Jos asiakkuuksilla palautetaan tavaraa (toimittajapalautus), ei oteta niitä palautuksia myyntilukuihin mukaan. Katotaan tää kauppatapahtuman luonteella
			if ($tuoterow['ostoehdotus'] == '') {

				$Q2 = "	SELECT round(SUM(tilausrivi.kpl), 0) myyty
				       	FROM tilausrivi
					   	JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus AND lasku.kauppatapahtuman_luonne != '21')
				       	JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio AND tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus AND tilausrivin_lisatiedot.tilausrivilinkki = '')
					   	JOIN varastopaikat ON (varastopaikat.yhtio = tilausrivi.yhtio
					   		AND concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0')) <= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'))
					   		AND concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'))
					   		AND varastopaikat.tyyppi = '')
				       	WHERE tilausrivi.yhtio 		= '$yhtiorow[yhtio]'
				       	AND tilausrivi.tyyppi 		= 'L'
				  	   	AND tilausrivi.tuoteno 		= '$tuoterow[tuoteno]'
				       	AND tilausrivi.toimitettuaika >= '$tanaan 00:00:00'
					   	AND tilausrivi.toimitettuaika <= '$tanaan 23:59:59'";
				$q2r =  mysql_query($Q2) or pupe_error($Q2);
				$myyntirow = mysql_fetch_assoc($q2r);

				$Q2 = "	SELECT round(SUM(tilausrivi.varattu), 0) myyty2
				       	FROM tilausrivi
					   	JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus AND lasku.kauppatapahtuman_luonne != '21')
				       	JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio AND tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus AND tilausrivin_lisatiedot.tilausrivilinkki = '')
					   	JOIN varastopaikat ON (varastopaikat.yhtio = tilausrivi.yhtio
					   		AND concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0')) <= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'))
					   		AND concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'))
					   		AND varastopaikat.tyyppi = '')
				       	WHERE tilausrivi.yhtio 	= '$yhtiorow[yhtio]'
				       	AND tilausrivi.tyyppi 	= 'L'
				  	   	AND tilausrivi.tuoteno 	= '$tuoterow[tuoteno]'
				  	   	AND tilausrivi.varattu != 0
				       	AND tilausrivi.toimitettuaika >= '$tanaan 00:00:00'
					   	AND tilausrivi.toimitettuaika <= '$tanaan 23:59:59'";
				$q2r2 =  mysql_query($Q2) or pupe_error($Q2);
				$myyntirow2 = mysql_fetch_assoc($q2r2);

				// tarkistetaan ettei laitetan negatiivia arvoja
				$myyntipvm = $myyntirow['myyty'] + $myyntirow2['myyty2'];

				if ($myyntipvm < 0) {
					$myyntipvm = '0';
				}
			}
			else {
				$myyntipvm = '0';
			}

			//avoimet ostokappaleet
			$Q3 = "	SELECT round(SUM(tilausrivi.varattu),0) as tilauksessa, tilausrivin_lisatiedot.tilausrivitunnus
				   	FROM tilausrivi
				   	LEFT JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio AND tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivilinkki)
				   	WHERE tilausrivi.yhtio = '$yhtiorow[yhtio]'
				   	AND tilausrivi.tyyppi = 'O'
				   	AND tilausrivi.tuoteno = '$tuoterow[tuoteno]'
				   	AND tilausrivi.laskutettuaika = '0000-00-00'
				   	HAVING tilausrivin_lisatiedot.tilausrivitunnus is null";
			$q3r = 	mysql_query($Q3) or pupe_error($Q3);
			$ostorow = mysql_fetch_assoc($q3r);

			$tilauksessa = $ostorow['tilauksessa'];
			if ($tilauksessa < 0) {
				$tilauksessa = '0';
			}

			$saldo = $tuoterow['saldo'];
			if ($saldo < 0) {
				$saldo = '0';
			}

			$out 	  = sprintf("%-8.8s",	$toimirow['toimittaja']);			//XVNDR
			$out 	 .= sprintf("%-18.18s",	$tuoterow['tuoteno']);				//XITEM
			$out 	 .= sprintf("%-3.3s",	"001");								//XWHSE
			$out 	 .= sprintf("%07.7s",	$saldo);							//XONHD
			$out 	 .= sprintf("%07.7s",	$tilauksessa);						//XOORD (varasto.saapunut)
			$out 	 .= sprintf("%07.7s",	"0");								//XBACK
			$out 	 .= sprintf("%07.7s",	$myyntipvm);						//Päivänmyynti	//XSHIP
			$out 	 .= sprintf("%07.7s",	"0");								//XLOST

			if (!fwrite($fp, $out . "\n")) {
				echo "Failed writing row.\n";
				die();
			}
		}

		fclose($fp);
	}

	function xf02($tanaan, $xf02loppulause) {
		global $path_xf02, $yhtiorow, $tuoterajaukset, $toimirajaus;

		echo "TULOSTETAAN xf02...\n";

		$valuuttaQ = "	SELECT nimi, kurssi
						FROM valuu
						WHERE yhtio  = '$yhtiorow[yhtio]'";
		$resaluutta = mysql_query($valuuttaQ) or pupe_error($valuuttaQ);

		$valuutat = array();

		while ($valurow = mysql_fetch_assoc($resaluutta)) {
			$valuutat[$valurow["nimi"]] = $valurow["kurssi"];
		}

		$kyselyxfo2 = " SELECT tuote.tuoteno,
						tuote.tuotekorkeus,
						tuote.tuoteleveys,
						tuote.tuotesyvyys,
						tuote.nimitys,
						tuote.status,
						tuote.suoratoimitus,
						tuote.epakurantti25pvm epakura,
						tuote.ostoehdotus,
						(
							SELECT korv.tuoteno
							FROM korvaavat AS korv
							WHERE korv.yhtio = tuote.yhtio
							AND korv.id = korvaavat.id
							ORDER BY if(korv.jarjestys = 0, 9999, korv.jarjestys), korv.tuoteno
							LIMIT 1
						) korvaavatuoteno,
						round((abc_aputaulu.summa / abc_aputaulu.kpl),4) as KAhinta,
						round(tuote.tuotemassa,3) tuotemassa,
						round(((tuote.tuotekorkeus * tuote.tuoteleveys * tuote.tuotesyvyys)/1000000000),4) as tilavuus
						FROM tuote use index (tuoteno_index)
						LEFT JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (abc_aputaulu.yhtio=tuote.yhtio AND abc_aputaulu.tyyppi='TM' AND tuote.tuoteno=abc_aputaulu.tuoteno)
						LEFT JOIN korvaavat ON (korvaavat.yhtio = tuote.yhtio AND korvaavat.tuoteno = tuote.tuoteno)
						WHERE tuote.yhtio = '$yhtiorow[yhtio]' $tuoterajaukset
						GROUP BY tuote.tuoteno, tuote.tuotekorkeus, tuote.tuoteleveys, tuote.tuotesyvyys, tuote.nimitys, tuote.status, tuote.suoratoimitus, tuote.epakurantti25pvm, tuote.ostoehdotus, korvaavatuoteno
						HAVING (korvaavatuoteno = tuote.tuoteno OR korvaavatuoteno is null)
						ORDER BY 1";
		$rests = mysql_query($kyselyxfo2) or pupe_error($kyselyxfo2);
		$rows = mysql_num_rows($rests);

		$fp = fopen($path_xf02, 'w+');

		while ($xf02 = mysql_fetch_assoc($rests)) {

			$query = "	SELECT
						toimi.ytunnus,
						toimi.tunnus,
						tuotteen_toimittajat.valuutta,
						toimi.toimittajanro,
						tuotteen_toimittajat.ostohinta,
						ROUND(tuotteen_toimittajat.pakkauskoko, 0) ostokpl,
						toimi.tyyppi
						FROM tuotteen_toimittajat use index (yhtio_tuoteno)
						JOIN toimi on (toimi.yhtio = tuotteen_toimittajat.yhtio AND tuotteen_toimittajat.liitostunnus = toimi.tunnus $toimirajaus)
						WHERE tuotteen_toimittajat.yhtio = '$yhtiorow[yhtio]'
						AND tuotteen_toimittajat.tuoteno = '$xf02[tuoteno]'
						ORDER BY if(tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys), tuotteen_toimittajat.tunnus
						LIMIT 1";
			$rest_toimittajista = mysql_query($query) or pupe_error($query);
			$toim_row = mysql_fetch_assoc($rest_toimittajista);

			if ($toim_row['toimittajanro'] == '' or $toim_row['tyyppi'] == 'P') continue;

			// Hetaan ostohinta
			$laskurow = array();
			$laskurow["liitostunnus"] 	= $toim_row["tunnus"];
			$laskurow["valkoodi"] 		= $toim_row["valuutta"];
			$laskurow["vienti_kurssi"] 	= (isset($valuutat[$toim_row["valuutta"]]) and $valuutat[$toim_row["valuutta"]] > 0) ? $valuutat[$toim_row["valuutta"]] : 1;
			$laskurow["ytunnus"]		= $toim_row["ytunnus"];

			$tuote_row = array();
			$tuote_row["tuoteno"]		= $xf02["tuoteno"];

			list($hinta,$netto,$ale,$valuutta) = alehinta_osto($laskurow, $tuote_row, 1, "", "", "");

			// Muutetaan valuuttahinta euroiksi.
			if (trim(strtoupper($valuutta)) != trim(strtoupper($yhtiorow["valkoodi"]))) {
				$hinta = $hinta * $laskurow["vienti_kurssi"];
			}

			$alennukset = generoi_alekentta_php($ale, 'O', 'kerto');
			$ostonetto = sprintf("%01.4f", round($hinta * $alennukset, 4));

			$tilavuus = $xf02['tilavuus'];

			if ($tilavuus > 99) {
				$tilavuus = '9999999';
			}
			else {
				$tilavuus = $tilavuus*1000;
				$tilavuus = str_replace('.','',$tilavuus);
			}

			$KA_myynti_hinta = $xf02['KAhinta'];

			if ($KA_myynti_hinta == '') {
				if ($ostonetto == 999999999) { $KA_myynti_hinta = 999999999; }
				else {$KA_myynti_hinta = sprintf("%.4f",$ostonetto * 1.30);  }
			}

			if ($xf02['status'] == 'T') {
				$tuotestatus = 'M';										//tehdastoimitustuotteet
			}
			elseif ($xf02['ostoehdotus'] == 'E') {
				$tuotestatus = 'D';										//ostoehdotus=no tuotteet tänne
			}
			else {
				$tuotestatus = 'R';
			}

			if ($toim_row['ostokpl'] == '0') $toim_row['ostokpl'] = '1';

			$out  = sprintf("%-8.8s",	$toim_row['toimittajanro']);				//XVNDR
			$out .= sprintf("%-18.18s",	$xf02['tuoteno']);		   	   				//XITEM
			$out .= sprintf("%-3.3s",	"001");   					   				//XWHSE
			$out .= sprintf("%013.13s",	str_replace('.','',$ostonetto));   			//XPCHP
			$out .= sprintf("%013.13s",	str_replace('.','',$KA_myynti_hinta));   	//XSLSP
			$out .= sprintf("%07.7s",	$toim_row['ostokpl']);   		   			//XPACK
			$out .= sprintf("%07.7s",	"1");   					   				//XMINQ
			$out .= sprintf("%07.7s",	str_replace('.','',$xf02['tuotemassa']));   //XWGHT
			$out .= sprintf("%07.7s",	$tilavuus);				  				  	//XVOLM
			$out .= sprintf("%-35.35s",	trim($xf02['nimitys']));   	  				//XNAME
			$out .= sprintf("%-2.2s",	"");   					  				  	//XUOMS
			$out .= sprintf("%-1.1s",	$tuotestatus);   					  		//XDWO
			$out .= sprintf("%-18.18s",	"");   					  				  	//XSEQ#
			$out .= sprintf("%-1.1s",	"");   					  				  	//XDELET

			if (! fwrite($fp, $out . "\n")) {
				echo "Failed writing row.\n";
				die();
			}
		}

		if (!fwrite($fp, $xf02loppulause . "\n")) {
			echo "Failed writing row.\n";
			die();
		}

		fclose($fp);
	}

	function create_headers($fp, array $cols) {
		$data = implode("\t", $cols) . "\n";
		fwrite($fp, $data);
	}

?>
