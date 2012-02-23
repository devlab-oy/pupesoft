<?php

/*

TƒSTƒ SKRIPTISTƒ PUUTTUU KORVAAVIIN LIITTYVƒ KƒSITTELY. NYKYINEN TUOTTEEN_AVAINSANOISTA KATOTTU KORVAAVUUS PITƒƒ OLLA TUTKITTU TODELLISISTA KORVAUKSISTA, EI AVAINSANOISTA.
LISƒKSI XSWP EI OLE KUNNOSSA KORVAAVIEN TAKIA. NYT EI TIEDETƒ KOSKA KORVAUS ON TEHTY.



XLTO: pit‰‰ rajata viel‰ laatija = 'E3' tjsp et tuloutukset jtoka tehty E3:lla tehdyill‰ ostotilauksilla rekisterˆid‰‰n!
XF01 avoimet pit‰‰ katsoa et ne on E3:n avoimia ostotilausrivej‰!
XF01 myynnit pit‰isi katsoa niin et ne on ns ykkˆsvaraston myyntej‰, eik‰ esim palautuksia tjsp rekkulavarastosta!!!! -jouni ojala
^
Varmista et asn ja ostolasku rivisplittaukset ym s‰ilytt‰‰ alk.per laatijan riveilt‰, et t‰‰ tieto j‰‰ taltee.

*/


	if (empty($argv)) {
	    die('<p>T‰m‰n scriptin voi ajaa ainoastaan komentorivilt‰.</p>');
	}

	if ($argv[1] == '') {
		die("Yhtiˆ on annettava!!");
	}

	$yhtio = $argv[1];

	// otetaan includepath aina rootista
	ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(__FILE__)).PATH_SEPARATOR."/usr/share/pear");
	error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
	ini_set("display_errors", 0);

	if (include("/var/www/html/demo/orum/inc/connect.inc"));
	elseif (include("/Users/tony/Sites/pupesoft/inc/connect.inc"));
	else require("/var/www/html/pupesoft/inc/connect.inc");

	if (include("/var/www/html/demo/orum/inc/functions.inc"));
	elseif (include("/Users/tony/Sites/pupesoft/inc/functions.inc"));
	else require("/var/www/html/pupesoft/inc/functions.inc");

	$tanaan = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	$today = $tanaan;

	//rajaukset
	$tuoterajaukset = " AND tuote.status != 'P' AND tuote.ei_saldoa = '' AND tuote.tuotetyyppi = ''";
	$toimirajaus = " AND toimi.oletus_vienti in ('C','F','I')";
	
	//$path = "/Users/tony/E3konversio/E3_$yhtio/filet/terve/";
	//$path = "/home/tony/E3konversio/artr/artr/";
	$path = "/home/satu/e3/aineisto/";
	
	// Sivotaan eka vanha pois
  	system("rm -rf $path");

	// Teh‰‰n uysi dirikka
	system("mkdir $path");

	$path_xauxi      = $path . 'xauxi.txt';
	$path_xlto    	 = $path . 'xlt0.txt';
	$path_wswp 		 = $path . 'xswp.txt';
	$path_xvni    	 = $path . 'xvni.txt';
	$path_xf04 		 = $path . 'xf04.txt';
	$path_xf01     	 = $path . 'xf01'.$today.'.txt';
	$path_xf02       = $path . 'xf02.txt';

	$query = "SELECT * from yhtio where yhtio='$yhtio'";
	$res = mysql_query($query) or pupe_error($query);
	$yhtiorow = mysql_fetch_assoc($res);

	$query = "SELECT * from yhtion_parametrit where yhtio='$yhtio'";
	$res = mysql_query($query) or pupe_error($query);
	$params = mysql_fetch_assoc($res);

	$yhtiorow = array_merge($yhtiorow, $params);
	
	echo "E3rajapinta siirto: $yhtio\n";

	//testausta varten limit
	$limit = "";

	// Ajetaan kaikki operaatiot
	xauxi($limit);
	xlto($limit); 
//	xswp($limit); 
	xvni($limit); 
	xf04($limit);
	xf01($limit); 
	xf02($limit); 
                  
	//Siirret‰‰n failit logisticar palvelimelle
	#siirto($path);

	function siirto ($path) {
		GLOBAL $logisticar, $yhtio;
		
		//n‰‰ varmaan asetetaan salasanat.php:hen
		$path_localdir 	 = "/mnt/logisticar_siirto/";
		$user_logisticar = $logisticar[$yhtio]["user"];
		$pass_logisticar = $logisticar[$yhtio]["pass"];
		$host_logisticar = $logisticar[$yhtio]["host"];
		$path_logisticar = $logisticar[$yhtio]["path"];

		unset($retval);
		system("mount -t cifs -o username=$user_logisticar,password=$pass_logisticar //$host_logisticar/$path_logisticar $path_localdir", $retval);

		if ($retval != 0) {
			echo "Mount failed! $retval\n";
		}
		else {

			unset($retval);
			system("cp -f $path/* $path_localdir", $retval);

			if ($retval != 0) {
				echo "Copy failed! $retval\n";
			}

			unset($retval);
			system("umount $path_localdir", $retval);

			if ($retval != 0) {
				echo "Unmount failed! $retval\n";
			}
		}
	}

	function xauxi($limit = '') {
		global $path_xauxi, $yhtio, $tanaan, $today, $tuoterajaukset, $toimirajaus;

		echo "TULOSTETAAN xauxi...";
		//vied‰‰n nimityksen sijaan lyhytkuvaus -Satu 8.2.12
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
					WHERE tuote.yhtio = '$yhtio' $tuoterajaukset AND tuote.ostoehdotus = ''
					HAVING (korvaavatuoteno = tuote.tuoteno OR korvaavatuoteno is null)";
					//ta1.selite,
					//LEFT JOIN tuotteen_avainsanat ta1 use index (yhtio_kieli_laji_tuoteno) ON (ta1.yhtio = tuote.yhtio AND ta1.kieli='fi' AND ta1.laji = 'parametri_korvattu' AND ta1.tuoteno = tuote.tuoteno)
					//(ta1.selite = '$tanaan' OR ta1.selite is null)
					//tuoteperhe.isatuoteno,
					//LEFT JOIN tuoteperhe ON (tuoteperhe.yhtio = tuote.yhtio AND tuoteperhe.isatuoteno = tuote.tuoteno AND tuoteperhe.tyyppi = 'P')
					//tuoteperhe.isatuoteno is null AND
		$rested = mysql_query($query) or pupe_error($query);
		$rows = mysql_num_rows($rested);

		if ($rows == 0) {
			echo "Yht‰‰n tuotetta ei lˆytynyt\n $query\n";
			//die();
		}

		$fp = fopen($path_xauxi, 'w+');

		$row = 0;

		while ($tuote = mysql_fetch_assoc($rested)) {
			
			$toimittaja = 'AS ytunnus, toimi.herminator as toimittaja';
			
			$query = "	SELECT toimi.ytunnus $toimittaja, toimi.tyyppi
						FROM tuotteen_toimittajat
						JOIN toimi on (toimi.yhtio=tuotteen_toimittajat.yhtio AND tuotteen_toimittajat.liitostunnus = toimi.tunnus $toimirajaus)
						WHERE tuotteen_toimittajat.yhtio = '$yhtio'
						AND tuotteen_toimittajat.tuoteno = '$tuote[tuoteno]'
						ORDER BY if(tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys), tuotteen_toimittajat.tunnus 
						LIMIT 1";
			$tutoq = mysql_query($query) or pupe_error($query);
			$tuto = mysql_fetch_assoc($tutoq);
			
			if ($tuto['toimittaja'] == '' or $tuto['tyyppi'] == 'P') continue;

			// mones t‰m‰ on
			$row++;

			$nimitys = $tuote['tuotenimi'];
			$order   = array("\r\n", "\n", "\r", "\t");
			$nimitys = str_replace($order, ' ', $nimitys);
			$nimitys = preg_replace(" {2,}", " ", $nimitys);

			$out  = sprintf("%-3.3s",		"E3T");
			$out .= sprintf("%-18.18s",		$tuote['tuoteno']);
			$out .= sprintf("%-3.3s",		"001");
			$out .= sprintf("%-8.8s",		$tuto['toimittaja']);
			$out .= sprintf("%-45.45s",		$nimitys); 
			
			//$tuote = implode("\t", $tuote);

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

//			echo sprintf("%s  |%-40s|\r", $str, $hash);
		}

		fclose($fp);
		echo "Done. Have An Ice Day\n";
	}

	function xlto($limit = '') {
		global $path_xlto, $yhtio, $tanaan, $today, $tuoterajaukset, $toimirajaus;

		//Item Lead Time File XLT0 (toimitusaikaseuranta)
		//Tiedot p‰iv‰n aikaan tehdyist‰ tuloutuksista, jotka tehty E3:ssa syntynytt‰ tilausta vastaan
		//tony: tein muutokset luonti ja lahete tietojen sis‰ltˆˆn. Sek‰ myˆs laskun tila semmoiseksi ett‰ on kyse tuloutetuista ostokeikasta
		//j‰tet‰‰ t‰st aineistost ostoehdotus EI:t pois -satu 17-2-12
		
		echo "Ulostetaan xlt0...\n";

	//	$tanaan = date("Y-m-d"); // muodossa Y-m-d == 2005-10-29

		$query = "	SELECT
						tilausrivi.tuoteno tuoteno,
						sum(round(tilausrivi.kpl)) kpl,
						DATE_FORMAT(tilausrivi.laskutettuaika,'%Y%m%d') luonti,
						DATE_FORMAT(tilausrivi.laadittu,'%Y%m%d') lahete,
						tilausrivin_lisatiedot.tilausrivitunnus,
						lasku.ytunnus,
						(
							SELECT korv.tuoteno
							FROM korvaavat AS korv
							WHERE korv.yhtio = tuote.yhtio
							AND korv.id = korvaavat.id
							ORDER BY if(korv.jarjestys = 0, 9999, korv.jarjestys), korv.tuoteno
							LIMIT 1
						) korvaavatuoteno
					FROM tilausrivi
					LEFT JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio AND tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivilinkki)
					JOIN tuote use index (tuoteno_index) ON (tilausrivi.yhtio = tuote.yhtio AND tilausrivi.tuoteno=tuote.tuoteno $tuoterajaukset AND tuote.ostoehdotus = '')
					JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.uusiotunnus AND lasku.tila = 'K' AND lasku.alatila != 'I')
					#t‰‰ ei ole aukoton ratkaisu ratkaista sit‰, ett‰ vain e3:lla tehtyj‰ tilauksia vastaan tehdyt tuloutukset.. jotkut tulot ei tuu osumaan jatkossa jos t‰lleen tehd‰‰n (jos asn/ostolasku kohdistuksien vuoksi uupuu ja tehd‰‰n k‰sin rivej‰)
					#JOIN lasku AS lasku2 ON (lasku2.yhtio = tilausrivi.yhtio and lasku2.tunnus = tilausrivi.otunnus AND lasku2.tila = 'O' AND lasku2.alatila = 'A' and lasku2.laatija = 'E3')
					LEFT JOIN korvaavat ON (korvaavat.yhtio = tuote.yhtio AND korvaavat.tuoteno = tuote.tuoteno)
					WHERE tilausrivi.yhtio	= '$yhtio' 
					AND tilausrivi.tyyppi = 'O'
					AND tilausrivi.laskutettuaika = '$tanaan'
					GROUP BY 1
					HAVING tilausrivin_lisatiedot.tilausrivitunnus is null AND (korvaavatuoteno = tilausrivi.tuoteno OR korvaavatuoteno is null)";
		$rest = mysql_query($query) or pupe_error($query);

		$rows = mysql_num_rows($rest);
		$row = 0;
		$laskuri = 0;

		if ($rows == 0) {
			echo "Yht‰‰n laskutustietoa ei lˆytynyt\n";
			//miks ihmeess‰ die? koko skripti kuolee t‰h‰n, eik‰ muita aineistoja tehd‰ laisinkaan... ˆˆ-ˆˆ
			//die();
		}

		$fp = fopen($path_xlto, 'w+');

		while ($xlto = mysql_fetch_assoc($rest)) {
			
			if ($xlto['kpl'] == 0 or $xlto['kpl'] < 0) continue;
			
			$toimittaja = 'AS ytunnus, toimi.herminator AS toimittaja';
			
			$query = "	SELECT toimi.ytunnus $toimittaja, toimi.tyyppi
						FROM tuotteen_toimittajat
						JOIN toimi on (toimi.yhtio=tuotteen_toimittajat.yhtio AND tuotteen_toimittajat.liitostunnus = toimi.tunnus $toimirajaus)
						WHERE tuotteen_toimittajat.yhtio = '$yhtio'
						AND tuotteen_toimittajat.tuoteno = '$xlto[tuoteno]'
						ORDER BY if(tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys), tuotteen_toimittajat.tunnus 
						LIMIT 1";
			$tutoq = mysql_query($query) or pupe_error($query);
			$tuto = mysql_fetch_assoc($tutoq);
			
			//tyhj‰t pois ja jos ostorivin toimittaja ei oo p‰‰toimittaja, ni skipataan (vain p‰‰toimittajan ostoja e3)
			if ($tuto['toimittaja'] == '' or $tuto['tyyppi'] == 'P') continue;
			//if ($tuto['ytunnus'] != $xlto['ytunnus']) continue;	//t‰m‰ pois Sadun pyynnˆst‰, kaikki tuloutukset e3:seen.
			
			// mones t‰m‰ on
			$row++;

			$out   = sprintf("%-8.8s", $tuto['toimittaja']);	//LTVNDR
			$out  .= sprintf("%-18.18s", $xlto['tuoteno']);		//LTITEM
			$out  .= sprintf("%-3.3s", "001");					//LTWHSE
			$out  .= sprintf("%07.7s", $xlto['kpl']);			//LTRQTY
			$out  .= sprintf("%-8.8s", $xlto['luonti']);		//LTRCDT		oikeasti saapunut.m‰‰r‰
			$out  .= sprintf("%-8.8s", $xlto['lahete']);		//LTORDT		oikeasti tilauspvm

			if (! fwrite($fp, $out . "\n")) {
				echo "Failed writing row.\n";
				die();
			}

			$laskuri++;
			echo "Kasitelty $laskuri / $rows\r";

		}

		fclose($fp);
		echo "\nHave An Ice Day.\n";
	}

	function xswp($limit = '') {
		global $path_wswp, $yhtio, $tanaan, $today;

		echo "Tulostetaan xswp...";

		$query = "	SELECT korvaavat.id, RPAD(tuotteen_toimittajat.tunnus, 7, ' ') as tunnus,
		 			group_concat(korvaavat.tuoteno order by korvaavat.jarjestys, korvaavat.tunnus SEPARATOR '####') as tuotteet
					FROM korvaavat
					JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = korvaavat.yhtio and tuotteen_toimittajat.tuoteno = korvaavat.tuoteno)
					WHERE korvaavat.yhtio = '$yhtio'
					GROUP BY id, toimittaja
					$limit";
		$rest = mysql_query($query) or pupe_error($query);
		$rows = mysql_num_rows($rest);
		$row = 0;

		if ($rows == 0) {
			echo "nilkoille kusi\n";
			//die();
		}

		$fp = fopen($path_wswp, 'w+');

		while ($korvaavat = mysql_fetch_assoc($rest)) {

			// mones t‰m‰ on
			$row++;

			//// explode, tunniste , alikysely ///
			$testi = explode("####", $korvaavat['tuotteet']);
			$alitunniste = $testi[0];

			// korjattu, herminator toimii

			$apuquery2 = "	SELECT DISTINCT RPAD(korvaavat.tuoteno,17,' ') as tuoteno, RPAD(toimi.ytunnus,7,' ') as tunnus
							FROM korvaavat
							JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = korvaavat.yhtio and tuotteen_toimittajat.tuoteno = korvaavat.tuoteno)
							JOIN toimi on (toimi.ytunnus = tuotteen_toimittajat.toimittaja and toimi.yhtio='$yhtio')
							WHERE korvaavat.yhtio = '$yhtio'
							AND korvaavat.tuoteno != '$alitunniste'
							AND korvaavat.id = '$korvaavat[id]'";
			$rested2 = mysql_query($apuquery2) or pupe_error($apuquery2);

			$i=1;

			while ($ali2 = mysql_fetch_assoc($rested2)) {
				//echo "alkuptuote: $alitunniste # alkuptoimittaja: $korvaavat[tunnus] # korvaavatuote: $ali2[tuoteno] # korvaavatoimittaja $ali2[tunnus]\n";
				$lause = "E3T001$korvaavat[tunnus] ".str_pad($alitunniste,17)." 001$ali2[tunnus] $ali2[tuoteno] U1    000000000000000000000000000AYNY";
			}

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

		fclose($fp);
		echo "Do Did Done.\n";
	}

	function xvni($limit = '') {
		global $path_xvni, $yhtio, $tanaan, $today, $tuoterajaukset, $toimirajaus;

		echo "Ulostetaan XVNI...";
		
		$toimittaja = 'toimi.herminator';
		$ytunnus = 'AS ytunnus, toimi.herminator AS tunnus';
		
		$qxvni = "	SELECT ytunnus $ytunnus, toimi.nimi nimi, SUBSTRING(toimi.nimi, 1, 18) lyhytnimi
					FROM toimi
					WHERE toimi.yhtio = '$yhtio'
					AND $toimittaja not in ('0','')
					AND tyyppi = '' $toimirajaus
					ORDER BY 1 $limit";
		$resto = mysql_query($qxvni) or pupe_error($qxvni);
		$rows = mysql_num_rows($resto);

		if ($rows == 0) {
			echo "ˆˆ.. error, jotain ?\n";
			//die();
		}
		$fp = fopen($path_xvni, 'w+');

		while ($xvni = mysql_fetch_assoc($resto)) {

			$out = sprintf("%-3.3s", "E3T");					//XVCOMP
			$out .= sprintf("%-3.3s", "001");					//XVWHSE
			$out .= sprintf("%-8.8s", $xvni['tunnus']);			//XVVNDR
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

	//	echo $out."\n";

			if (!fwrite($fp, $out . "\n")) {
				echo "Failed writing row.\n";
				die();
			}

		}

		fclose($fp);
		echo "sniff.\n";
	}

	function xf04($limit = '') {
		global $path_xf04, $yhtio, $tanaan, $today, $tuoterajaukset, $toimirajaus;

		echo "Tulostetaan xf04...\n";
		// jos kirjaimet on A-I niin homma toimii, jos on enemm‰n niin homma kusee.

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
					WHERE tuote.yhtio = '$yhtio' $tuoterajaukset AND tuote.ostoehdotus = ''
					HAVING (korvaavatuoteno = tuote.tuoteno OR korvaavatuoteno is null) 
					ORDER BY tuote.tuoteno";
		$resto = mysql_query($qxf04) or pupe_error($qxf04);
		$rows = mysql_num_rows($resto);
		echo "÷ˆˆˆˆˆˆ\n";
		$laskuri = 0;

		if ($rows == 0) {
			echo "ˆˆ.. error, jotain jossain...\n";
			//die();
		}
		$fp = fopen($path_xf04, 'w+');

		while ($xf04 = mysql_fetch_assoc($resto)) {
			
			$toimittaja = 'AS ytunnus, toimi.herminator AS toimittaja';

			$query = "	SELECT tuotteen_toimittajat.toim_tuoteno as ttuoteno, toimi.ytunnus $toimittaja, toimi.tyyppi
						FROM tuotteen_toimittajat
						JOIN toimi on (toimi.yhtio=tuotteen_toimittajat.yhtio AND tuotteen_toimittajat.liitostunnus = toimi.tunnus $toimirajaus)
						WHERE tuotteen_toimittajat.yhtio = '$yhtio'
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

			$laskuri++;
			echo "Kasitelty $laskuri / $rows\r";
		}
		echo "÷ˆˆˆˆˆˆ2\n";
		fclose($fp);
		echo "\nsniffer daa.\n";
	}

	function xf01($limit = '') {
		global $path_xf01, $yhtiorow, $yhtio, $tanaan, $today, $tuoterajaukset, $toimirajaus;

		echo "Ajetaan xf01...\n";

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
				#ROUND(sum(tuotepaikat.saldo),0) saldo
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
				#LEFT JOIN tuotepaikat on (tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno)
				LEFT JOIN korvaavat ON (korvaavat.yhtio = tuote.yhtio AND korvaavat.tuoteno = tuote.tuoteno)
				WHERE tuote.yhtio = '$yhtio' $tuoterajaukset AND tuote.ostoehdotus = ''
				GROUP BY tuote.tuoteno, tuote.status, korvaavatuoteno
				HAVING (korvaavatuoteno = tuote.tuoteno OR korvaavatuoteno is null)";
		$rests = mysql_query($Q1) or pupe_error($Q1);
		$rows = mysql_num_rows($rests);
		$laskuri = 0;

		if ($rows == 0) {
			echo "ˆˆ.. error, jotain jossain...\n";
			//die();
		}
		$fp = fopen($path_xf01, 'w+');

		while ($tuoterow = mysql_fetch_assoc($rests)) {

			$toimittaja = 'AS ytunnus, toimi.herminator AS toimittaja';

			$toimittajaquery = "	SELECT toimi.ytunnus $toimittaja, toimi.tyyppi
									FROM tuotteen_toimittajat
									JOIN toimi on (toimi.yhtio=tuotteen_toimittajat.yhtio AND tuotteen_toimittajat.liitostunnus = toimi.tunnus $toimirajaus)
									WHERE tuotteen_toimittajat.yhtio = '$yhtio' 
									AND tuotteen_toimittajat.tuoteno = '$tuoterow[tuoteno]'
									ORDER BY if(tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys), tuotteen_toimittajat.tunnus
									LIMIT 1";
			$toimires = mysql_query($toimittajaquery) or pupe_error($toimittajaquery);
			$toimirow = mysql_fetch_assoc($toimires);
			
			if ($toimirow['toimittaja'] == '' or $toimirow['tyyppi'] == 'P') continue;
			
			//Jos ostoehdotus on kyll‰, siirret‰‰n myyntilukuja. Muuten ei.
			//Myynnit vaan "normaaleist" varastoist, tsekataa vaa varastopaikat-taulusta tyyppi '':st‰ myydyt
			//Jos asiakkuuksilla palautetaan tavaraa (toimittajapalautus), ei oteta niit‰ palautuksia myyntilukuihin mukaan. Katotaan t‰‰ kauppatapahtuman luonteella
			if ($tuoterow['ostoehdotus'] == '') {
			
				$Q2 = " 	SELECT round(SUM(tilausrivi.kpl), 0) myyty
				        	FROM tilausrivi
							JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus AND lasku.kauppatapahtuman_luonne != '21')
				        	JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio AND tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus AND tilausrivin_lisatiedot.tilausrivilinkki = '')
							JOIN varastopaikat ON (varastopaikat.yhtio = tilausrivi.yhtio 
							AND concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0')) <= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0')) 
							AND concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'))
							AND varastopaikat.tyyppi = '')
				        	WHERE tilausrivi.yhtio = '$yhtio'
				        	AND tilausrivi.tyyppi = 'L'
				  			AND tilausrivi.tuoteno = '$tuoterow[tuoteno]'
				  			AND laskutettuaika > '0000-00-00'
				        	AND tilausrivi.toimitettuaika = '$tanaan'";
				$q2r =  mysql_query($Q2) or pupe_error($Q2);
				$myyntirow = mysql_fetch_assoc($q2r);
				
				$Q2 = " 	SELECT round(SUM(tilausrivi.varattu), 0) myyty2
				        	FROM tilausrivi
							JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus AND lasku.kauppatapahtuman_luonne != '21')
				        	JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio AND tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus AND tilausrivin_lisatiedot.tilausrivilinkki = '')
							JOIN varastopaikat ON (varastopaikat.yhtio = tilausrivi.yhtio 
							AND concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0')) <= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0')) 
							AND concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'))
							AND varastopaikat.tyyppi = '')
				        	WHERE tilausrivi.yhtio = '$yhtio'
				        	AND tilausrivi.tyyppi = 'L'
				  			AND tilausrivi.tuoteno = '$tuoterow[tuoteno]'
				  			AND varattu != 0
				        	AND tilausrivi.toimitettuaika = '$tanaan'";
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
			$Q3 = 	"	SELECT round(SUM(tilausrivi.varattu),0) as tilauksessa, tilausrivin_lisatiedot.tilausrivitunnus
						FROM tilausrivi
						#JOIN lasku AS lasku2 ON (lasku2.yhtio = tilausrivi.yhtio and lasku2.tunnus = tilausrivi.otunnus AND lasku2.tila = 'O' AND lasku2.alatila = 'A' and lasku2.laatija = 'E3')
						LEFT JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio AND tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivilinkki)
						WHERE tilausrivi.yhtio = '$yhtio'
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
			$out 	 .= sprintf("%07.7s",	$myyntipvm);						//P‰iv‰nmyynti	//XSHIP
			$out 	 .= sprintf("%07.7s",	"0");								//XLOST

			//echo $out."\n";

			if (!fwrite($fp, $out . "\n")) {
				echo "Failed writing row.\n";
				die();
			}

			$laskuri++;
			echo "Kasitelty $laskuri / $rows\r";
		}

		fclose($fp);
		echo "\nmelkein valmis.\n";
	}

	function xf02($limit = '') {
		global $path_xf02, $yhtiorow, $yhtio, $tanaan, $today, $tuoterajaukset, $toimirajaus;

		echo "Ajetaan xf02...\n";

		$kyselyxfo2 = " SELECT tuote.tuoteno org_tuoteno,
						round((abc_aputaulu.summa / abc_aputaulu.kpl),4) as KAhinta,
						round(tuote.tuotemassa,3) tuotemassa,
						tuote.tuotekorkeus,
						tuote.tuoteleveys,
						tuote.tuotesyvyys,
						round(((tuote.tuotekorkeus * tuote.tuoteleveys * tuote.tuotesyvyys)/1000000000),4) as tilavuus,
						tuote.nimitys,
						tuote.status status,
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
						) korvaavatuoteno
						FROM tuote use index (tuoteno_index)
						LEFT JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (abc_aputaulu.yhtio=tuote.yhtio AND abc_aputaulu.tyyppi='TM' AND tuote.tuoteno=abc_aputaulu.tuoteno)
						LEFT JOIN korvaavat ON (korvaavat.yhtio = tuote.yhtio AND korvaavat.tuoteno = tuote.tuoteno)
						WHERE tuote.yhtio='$yhtio' $tuoterajaukset
						HAVING (korvaavatuoteno = tuote.tuoteno OR korvaavatuoteno is null)
						ORDER BY 1";
						//AND (ta2.selite != 'no' OR ta2.selite is null) 
						//ta2.selite as ostoehdotus
						//LEFT JOIN tuotteen_avainsanat ta2 use index (yhtio_kieli_laji_tuoteno) ON (ta2.yhtio = tuote.yhtio AND ta2.kieli='fi' AND ta2.laji = 'parametri_ostoehdotus' AND ta2.tuoteno = tuote.tuoteno)
		$rests = mysql_query($kyselyxfo2) or pupe_error($kyselyxfo2);
		$rows = mysql_num_rows($rests);
		$laskuri = 0;

		if ($rows == 0) {
			echo "ˆˆ.. error, jotain jossain...\n";
			//die();
		}
		$fp = fopen($path_xf02, 'w+');
		
		while ($xf02 = mysql_fetch_assoc($rests)) {
			
			$toimittaja = 'AS ytunnus, toimi.herminator AS toimittaja';
			
			$query = "	SELECT toimi.ytunnus $toimittaja, round(tuotteen_toimittajat.ostohinta * (1 - (tuotteen_toimittajat.alennus / 100)),4) ostohinta, round(tuotteen_toimittajat.pakkauskoko,0) ostokpl, tuotteen_toimittajat.valuutta, toimi.tyyppi
						FROM tuotteen_toimittajat use index (yhtio_tuoteno)
						JOIN toimi on (toimi.yhtio=tuotteen_toimittajat.yhtio AND tuotteen_toimittajat.liitostunnus = toimi.tunnus $toimirajaus)
						WHERE tuotteen_toimittajat.yhtio = '$yhtio'
						AND tuotteen_toimittajat.tuoteno = '$xf02[org_tuoteno]'
						ORDER BY if(tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys), tuotteen_toimittajat.tunnus 
						LIMIT 1";
			$rest_toimittajista = mysql_query($query) or pupe_error($query);
			$toim_row = mysql_fetch_assoc($rest_toimittajista);
			if ($toim_row['toimittaja'] == '' or $toim_row['tyyppi'] == 'P') continue;

			$valuuttaQ = "SELECT kurssi FROM valuu WHERE nimi = '$toim_row[valuutta]' AND yhtio='$yhtio' limit 1";
			$resaluutta = mysql_query($valuuttaQ) or pupe_error($valuuttaQ);
			$rowstest = mysql_num_rows($resaluutta);
			if ($rowstest == 0) {
				$valurow['kurssi'] = '1';
			}
			else {
				$valurow = mysql_fetch_assoc($resaluutta);
			}

			$tilavuus = $xf02['tilavuus'];

			if ($toim_row['ostohinta'] > '0') $ostonetto = sprintf("%01.4f", round($toim_row['ostohinta'] / $valurow['kurssi'], 4));
			elseif ($ostonetto < 0) $ostonetto = 999999999;
			else $ostonetto = '0';
			
			$KA_myynti_hinta = $xf02['KAhinta'];

			if ($tilavuus > 99) {$tilavuus = '9999999';} else {$tilavuus = $tilavuus*1000; $tilavuus = str_replace('.','',$tilavuus);}

			if ($KA_myynti_hinta == '') {
				if ($ostonetto == 999999999) { $KA_myynti_hinta = 999999999; }
				else {$KA_myynti_hinta = sprintf("%.4f",$ostonetto * 1.30);  }
			}

			if ($xf02['status'] == 'T') {
				$tuotestatus = 'M';										//tehdastoimitustuotteet		
			}	
			elseif ($xf02['ostoehdotus'] == 'E') {
				$tuotestatus = 'D';										//ostoehdotus=no tuotteet t‰nne
			}
			else {
				$tuotestatus = 'R';
			}
			
			if ($toim_row['ostokpl'] == '0') $toim_row['ostokpl'] = '1';

			$out  = sprintf("%-8.8s",	$toim_row['toimittaja']);	   				//XVNDR
			$out .= sprintf("%-18.18s",	$xf02['org_tuoteno']);   	   				//XITEM
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

			$laskuri++;
			echo "Kasitelty $laskuri / $rows\r";
		}

		fclose($fp);
		echo "melkein valmis.\n";
	}

	function create_headers($fp, array $cols) {
		$data = implode("\t", $cols) . "\n";
		fwrite($fp, $data);
	}

?>
