<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	if (empty($argv)) {
	    die('<p>Tämän scriptin voi ajaa ainoastaan komentoriviltä.</p>');
	}

	if ($argv[1] == '') {
		die("Yhtiö on annettava!!");
	}

	$yhtio = $argv[1];

	// otetaan includepath aina rootista
	ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(__FILE__)).PATH_SEPARATOR."/usr/share/pear");
	error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
	ini_set("display_errors", 0);

	require('inc/connect.inc');
	require('inc/functions.inc');

	$today = date("dmy");

	$path = "/Users/jamppa/tmp/E3/E3_$yhtio/";

	// Sivotaan eka vanha pois
  	system("rm -rf $path");

	// Tehään uysi dirikka
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
	xswp($limit);
	xvni($limit);
	xf04($limit);
	xf01($limit);
	xf02($limit);

	//Siirretään failit logisticar palvelimelle
	#siirto($path);

	function siirto ($path) {
		GLOBAL $logisticar, $yhtio;

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
		global $path_xauxi, $yhtio, $logisticar;

		$where_logisticar = $logisticar[$yhtio]["where"];

		echo "TULOSTETAAN xauxi...";

		$query = "	SELECT 	RPAD(concat('E3T',tuote.tuoteno), 18,' ') as TUOTENO,
						 	RPAD(concat('001',tuotteen_toimittajat.toimittaja), 11, ' ') as YTUNNUS,
							RPAD(tuote.nimitys,45,' ') as Tuotenimi
					FROM tuote
					JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tuote.yhtio AND tuotteen_toimittajat.tuoteno = tuote.tuoteno)
					WHERE tuote.yhtio = '$yhtio' $limit";
		$rested = mysql_query($query) or pupe_error($query);
		$rows = mysql_num_rows($rested);

		if ($rows == 0) {
			echo "Yhtään tuotetta ei löytynyt\n $query\n";
			die();
		}

		$fp = fopen($path_xauxi, 'w+');

		$row = 0;

		while ($tuote = mysql_fetch_assoc($rested)) {

			// mones tämä on
			$row++;

			$tuote = implode("\t", $tuote);

			if (! fwrite($fp, $tuote . "\n")) {
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
		global $path_xlto, $yhtio, $logisticar;

		$where_logisticar = $logisticar[$yhtio]["where"];

		echo "Ulostetaan xlt0...\n";

		$tanaan = date("Y-m-d"); // muodossa Y-m-d == 2005-10-29
	//	$tanaan = '2010-10-13';

		$query = "	SELECT
						tuotteen_toimittajat.toimittaja toimittaja,
						tilausrivi.tuoteno tuoteno,
						tilausrivi.kpl kpl ,
						DATE_FORMAT(lasku.luontiaika,'%Y%m%d') luonti,
						DATE_FORMAT(lasku.lahetepvm,'%Y%m%d') lahete
					FROM tilausrivi
					JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
					JOIN tuotteen_toimittajat on (tilausrivi.tuoteno = tuotteen_toimittajat.tuoteno AND tuotteen_toimittajat.yhtio = tilausrivi.yhtio)
					WHERE tilausrivi.yhtio	= '$yhtio'
					AND laskutettuaika = '$tanaan'";
		$rest = mysql_query($query) or pupe_error($query);

		$rows = mysql_num_rows($rest);
		$row = 0;
		$laskuri = 0;

		if ($rows == 0) {
			echo "Yhtään laskutustietoa ei löytynyt\n";
			die();
		}

		$fp = fopen($path_xlto, 'w+');

		while ($xlto = mysql_fetch_assoc($rest)) {

			// mones tämä on
			$row++;

			$out   = sprintf("%-8.8s", $xlto['toimittaja']);	//LTVNDR
			$out  .= sprintf("%-8.8s", $xlto['tuoteno']);		//LTITEM
			$out  .= sprintf("%-8.8s", "001");					//LTWHSE
			$out  .= sprintf("%-8.8s", $xlto['kpl']);			//LTRQTY
			$out  .= sprintf("%-8.8s", $xlto['luonti']);		//LTRCDT
			$out  .= sprintf("%-8.8s", $xlto['lahete']);		//LTORDT

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
		global $path_wswp, $yhtio, $logisticar;

		$where_logisticar = $logisticar[$yhtio]["where"];

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

		//echo "rivien lukumäärä on: ".$rows."\n";

		if ($rows == 0) {
			echo "nilkoille kusi\n";
			die();
		}

		$fp = fopen($path_wswp, 'w+');

		while ($korvaavat = mysql_fetch_assoc($rest)) {

			// mones tämä on
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
		global $path_xvni, $yhtio, $logisticar;

		$where_logisticar = $logisticar[$yhtio]["where"];

		echo "Ulostetaan XVNI...";

		$qxvni = "	SELECT toimi.ytunnus tunnus, toimi.nimi nimi, SUBSTRING(toimi.nimi, 1, 18) lyhytnimi
					FROM toimi
					WHERE toimi.yhtio = '$yhtio'
					AND toimi.ytunnus != 0
					ORDER BY 1 $limit";
		$resto = mysql_query($qxvni) or pupe_error($qxvni);
		$rows = mysql_num_rows($resto);

		if ($rows == 0) {
			echo "öö.. error, jotain ?\n";
			die();
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
		global $path_xf04, $yhtio, $logisticar;

		$where_logisticar = $logisticar[$yhtio]["where"];

		echo "Tulostetaan xf04...\n";
		// jos kirjaimet on A-I niin homma toimii, jos on enemmän niin homma kusee.

		$qxf04 = "	SELECT toimi.tunnus tunnus, tuote.tuoteno as tuoteno, tuotteen_toimittajat.toim_tuoteno as ttuoteno, tuote.yksikko as yksikko , tuote.try as try,
					tm.luokka,
					if(tm.luokka=1,'A',if(tm.luokka=2,'B',if(tm.luokka=3,'C',if(tm.luokka=4,'D',if(tm.luokka=5,'E',if(tm.luokka=6,'F',if(tm.luokka=7,'G',if(tm.luokka=8,'H','I')))))))) as 'MYYNNINABC',
					tr.luokka,
					if(tr.luokka=1,'A',if(tr.luokka=2,'B',if(tr.luokka=3,'C',if(tr.luokka=4,'D',if(tr.luokka=5,'E',if(tr.luokka=6,'F',if(tr.luokka=7,'G',if(tr.luokka=8,'H','I')))))))) as 'POKEABC'
					FROM toimi
					JOIN tuote ON (tuote.yhtio = toimi.yhtio)
					JOIN tuotteen_toimittajat on (tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuotteen_toimittajat.yhtio = tuote.yhtio AND toimi.ytunnus = tuotteen_toimittajat.toimittaja)
					JOIN abc_aputaulu tm ON (tm.yhtio = toimi.yhtio AND tm.tuoteno = tuote.tuoteno AND tm.tyyppi = 'TM')
					JOIN abc_aputaulu tr ON (tr.yhtio = toimi.yhtio AND tr.tuoteno = tuote.tuoteno AND tr.tyyppi = 'TR')
					WHERE toimi.yhtio = '$yhtio'
					AND tuote.status !='P'
					ORDER BY tuote.tuoteno
					$limit";
		$resto = mysql_query($qxf04) or pupe_error($qxf04);
		$rows = mysql_num_rows($resto);

		$laskuri = 0;

		if ($rows == 0) {
			echo "öö.. error, jotain jossain...\n";
			die();
		}
		$fp = fopen($path_xf04, 'w+');

		while ($xf04 = mysql_fetch_assoc($resto)) {

     		$out 	 = sprintf("%-8.8s", $xf04['tunnus']);			//XVNDR
			$out   	.= sprintf("%-18.18s",$xf04['tuoteno']);		//XITEM
			$out 	.= sprintf("%-3.3s", "001");					//XWHSE
			$out 	.= sprintf("%-4.4s","");						//XSUBV
			$out 	.= sprintf("%-3.3s","");						//XREGON
			$out 	.= sprintf("%-25.25s",$xf04['ttuoteno']);		//XMFGID
			$out 	.= sprintf("%-10.10s", $xf04['yksikko']);		//XPKSIZ
			$out 	.= sprintf("%-15.15s", "");						//XUPC
			$out 	.= sprintf("%-10.10s", "");						//XTIEHI
			$out 	.= sprintf("%07.7s", "0");						//XQRSVR
			$out 	.= sprintf("%01.1s", "0");						//XURSVR
			$out 	.= sprintf("%07.7s", "0");						//XQHELD
			$out 	.= sprintf("%08.8s", "0");						//XOHHDT
			$out 	.= sprintf("%01.1s", "0");						//XUHELD
			$out 	.= sprintf("%07.7s", "0");						//XRVUNT
			$out 	.= sprintf("%07.7s", "0");						//XCNVPK
			$out 	.= sprintf("%03.3s", "1");						//XCNVML
			$out 	.= sprintf("%03.3s", "1");						//XCNVBP
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
			$out 	.= sprintf("%-5.5s", $xf04['MYYNNINABC']);		//XGRP4
			$out 	.= sprintf("%-5.5s",$xf04['POKEABC']);			//XGRP5
			$out 	.= sprintf("%-5.5s","");						//XGRP6
			$out 	.= sprintf("%0-3.3s", "0");						//XOOPNT

			if (!fwrite($fp, $out . "\n")) {
				echo "Failed writing row.\n";
				die();
			}

			$laskuri++;
			echo "Kasitelty $laskuri / $rows\r";

		}

		fclose($fp);
		echo "\nsniffer daa.\n";
	}

	function xf01($limit = '') {
		global $path_xf01, $yhtiorow, $yhtio, $logisticar;

		$where_logisticar = $logisticar[$yhtio]["where"];

		echo "Ajetaan xf01...\n";

		$Q1 = " SELECT tuote.tuoteno,
				ROUND(sum(tuotepaikat.saldo),0) saldo
				FROM tuote
				JOIN tuotepaikat on (tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno)
				WHERE tuote.yhtio = '$yhtio'
				AND tuote.status != 'P'
				group by tuoteno";
		$rests = mysql_query($Q1) or pupe_error($Q1);
		$rows = mysql_num_rows($rests);
		$laskuri = 0;

		if ($rows == 0) {
			echo "öö.. error, jotain jossain...\n";
			die();
		}
		$fp = fopen($path_xf01, 'w+');

		$Lisamaare_Ajalle ='';

		$tanaan = date("Y-m-d"); // muodossa Y-m-d == 2005-10-29

		while ($tuoterow = mysql_fetch_assoc($rests)) {

			$toimittajaquery = "	SELECT toimittaja
									FROM tuotteen_toimittajat
									WHERE tuoteno = '{$tuoterow[tuoteno]}'
									AND yhtio = '$yhtio'
									ORDER BY tunnus
									LIMIT 1";
			$toimires = mysql_query($toimittajaquery) or pupe_error($toimittajaquery);
			$toimirow = mysql_fetch_assoc($toimires);

			$Q2 =	"	SELECT round(SUM(tilausrivi.kpl), 0) myyty
						FROM tilausrivi
						WHERE tyyppi = 'L'
						AND tilausrivi.yhtio = '$yhtio'
						AND tilausrivi.laskutettuaika = '$tanaan'
						AND tilausrivi.tuoteno = '$tuoterow[tuoteno]'";
			$q2r = 	mysql_query($Q2) or pupe_error($Q2);
			$myyntirow = mysql_fetch_assoc($q2r);

			$Q3 = 	"	SELECT round(SUM(tilausrivi.kpl),0) as tilauksessa
						FROM tilausrivi
						WHERE tyyppi = 'O'
						AND tilausrivi.tuoteno = '{$tuoterow[tuoteno]}'
						AND tilausrivi.yhtio = '$yhtio'
						AND tilausrivi.laskutettuaika = '$tanaan'";
			$q3r = 	mysql_query($Q3) or pupe_error($Q3);
			$ostorow = mysql_fetch_row($q3r);

			// tarkistetaan ettei laitetan negatiivia arvoja
			$myyntipvm = $myyntirow['myyty'];

			if ($myyntipvm < 0) {
				$myyntipvm = '0';
			}

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
			$out 	 .= sprintf("%07.7s",	$tuoterow['saldo']);				//XONHD
			$out 	 .= sprintf("%07.7s",	$tilauksessa);						//XOORD
			$out 	 .= sprintf("%07.7s",	"0");								//XBACK
			$out 	 .= sprintf("%07.7s",	$myyntipvm);						//Päivänmyynti	//XSHIP
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
		global $path_xf02, $yhtiorow, $yhtio, $logisticar;

		$where_logisticar = $logisticar[$yhtio]["where"];

		echo "Ajetaan xf02...\n";

		$kyselyxfo2 = " SELECT tuote.tuoteno org_tuoteno,
						round(tuotteen_toimittajat.osto_era,0) ostokpl,
						round((abc_aputaulu.summa / abc_aputaulu.kpl),4) as KAhinta,
						tuote.tuotemassa,
						tuote.tuotekorkeus ,
						tuote.tuoteleveys ,
						tuote.tuotesyvyys ,
						round(((tuote.tuotekorkeus * tuote.tuoteleveys * tuote.tuotesyvyys)/1000000000),4) as tilavuus,
						tuote.nimitys,
						tuote.status status,
						tuote.epakurantti25pvm epakura,
						tuotteen_toimittajat.valuutta
						FROM tuote
						JOIN tuotteen_toimittajat ON (tuote.yhtio=tuotteen_toimittajat.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno)
						JOIN abc_aputaulu on (abc_aputaulu.yhtio=tuote.yhtio AND tuote.tuoteno=abc_aputaulu.tuoteno AND abc_aputaulu.tyyppi='TM')
						WHERE tuote.status !='P' AND tuote.yhtio='$yhtio'
						ORDER BY 1";
		$rests = mysql_query($kyselyxfo2) or pupe_error($kyselyxfo2);
		$rows = mysql_num_rows($rests);
		$laskuri = 0;

		if ($rows == 0) {
			echo "öö.. error, jotain jossain...\n";
			die();
		}
		$fp = fopen($path_xf02, 'w+');

		/*
		$valuuttaQ = "SELECT nimi, kurssi FROM valuu WHERE nimi = '{$xf02['valuutta']}'";
		$resaluutta = mysql_query($valuuttaQ) or pupe_error($valuuttaQ);
		$valurow = mysql_fetch_assoc($resaluutta);
		*/

		while ($xf02 = mysql_fetch_assoc($rests)) {

			$kysely_toimittajista = "	SELECT tuote.tuoteno,
					(SELECT tuotteen_toimittajat.toimittaja
					FROM tuotteen_toimittajat
					WHERE tuotteen_toimittajat.tuoteno=tuote.tuoteno
					AND tuote.yhtio=tuotteen_toimittajat.yhtio ORDER BY tuotteen_toimittajat.tunnus LIMIT 1) AS toimittaja,
					(SELECT round(tuotteen_toimittajat.ostohinta,4)
					FROM tuotteen_toimittajat
					WHERE tuotteen_toimittajat.tuoteno=tuote.tuoteno
					AND tuote.yhtio=tuotteen_toimittajat.yhtio ORDER BY tuotteen_toimittajat.tunnus LIMIT 1) as ostohinta
				FROM tuote
				JOIN tuotteen_toimittajat ON(tuotteen_toimittajat.yhtio=tuote.yhtio AND tuotteen_toimittajat.tuoteno=tuote.tuoteno)
				WHERE tuote.yhtio='$yhtio'
				AND tuote.status !='P'
				AND tuote.tuoteno = '{$xf02['org_tuoteno']}'
				ORDER BY 1";
			$rest_toimittajista = mysql_query($kysely_toimittajista) or pupe_error($kysely_toimittajista);
			$toim_row = mysql_fetch_assoc($rest_toimittajista);

			$tilavuus = $xf02['tilavuus'];
			$ostonetto = $toim_row['ostohinta'];
			$KA_myynti_hinta = $xf02['KAhinta'];
			$tuotestatus = $xf02['status'];

			if ($tilavuus > 99) {$tilavuus = '9999999';} else {$tilavuus = $tilavuus*1000; $tilavuus = str_replace('.','',$tilavuus);}
			if ($ostonetto < 0 ) {$ostonetto = 999999999;}
			/// lisätään valuuttamuunnos jos on tarpeen. toistaiseksi yhtään riviä ei ole tullut vastaan jossa olisi ostettu muulla valuutalla kuin euroilla.

			if ($KA_myynti_hinta == '') {
				if ($ostonetto == 999999999) { $KA_myynti_hinta = 999999999; }
				else {$KA_myynti_hinta = sprintf("%.4f",$ostonetto * 1.30);  }
			}

			if ($tuotestatus == 'T') {
				$tuotestatus = 'M';
			}
			if ($xf02['epakura'] != 0) {
				$tuotestatus = 'D';
			}
			else {
				$tuotestatus = 'R';
			}

			$out  = sprintf("%-8.8s",	$toim_row['toimittaja']);	   				 //XVNDR
			$out .= sprintf("%-18.18s",	$xf02['org_tuoteno']);   	   				 //XITEM
			$out .= sprintf("%-3.3s",	"001");   					   				 //XWHSE
			$out .= sprintf("%013.13s",	str_replace('.','',$ostonetto));   			 //XPCHP
			$out .= sprintf("%013.13s",	str_replace('.','',$KA_myynti_hinta));   	//XSLSP
			$out .= sprintf("%07.7s",	$xf02['ostokpl']);   		   				 //XPACK
			$out .= sprintf("%07.7s",	"1");   					   				 //XMINQ
			$out .= sprintf("%07.7s",	str_replace('.','',$xf02['tuotemassa']));   //XWGHT
			$out .= sprintf("%07.7s",	$tilavuus);				  				  	//XVOLM
			$out .= sprintf("%-35.35s",	$xf02['nimitys']);   	  				  	//XNAME
			$out .= sprintf("%-2.2s",	"");   					  				  	//XUOMS
			$out .= sprintf("%-1.1s",	$tuotestatus);   					  		//XDWO
			$out .= sprintf("%-18.18s",	"");   					  				  	//XSEQ#
			$out .= sprintf("%-1.1s",	"");   					  				  	//XDELET

			if (!fwrite($fp, $out . "\n")) {
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
