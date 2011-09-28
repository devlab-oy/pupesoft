<?php

	if (isset($_POST["tee"])) {
		if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	require ('inc/parametrit.inc');

	if (isset($tee) and $tee == "lataa_tiedosto") {
		readfile("/tmp/".$tmpfilenimi);
		exit;
	}

	// tuonti vai vienti
	if ($tapa == "tuonti") {
		$laji = "A";
		$tilastoloppu = '001';
	}
	else {
		$tapa = "vienti";
		$laji = "D";
		$tilastoloppu = '002';
	}

	echo "<font class='head'>".t("Intrastat-ilmoitukset")."</font><hr>";

	if ($tee == "tulosta") {

		if ($excel != "") {
			if (include('Spreadsheet/Excel/Writer.php')) {

				//keksit��n failille joku varmasti uniikki nimi:
				list($usec, $sec) = explode(' ', microtime());
				mt_srand((float) $sec + ((float) $usec * 100000));
				$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

				$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
				$workbook->setVersion(8);
				$worksheet =& $workbook->addWorksheet('Sheet 1');

				$format_bold =& $workbook->addFormat();
				$format_bold->setBold();

				$excelrivi = 0;
			}
		}

		// tehd��n kauniiseen muotoon annetun kauden eka ja vika pvm
		$vva = date("Y",mktime(0, 0, 0, $kk, 1, $vv));
		$kka = date("m",mktime(0, 0, 0, $kk, 1, $vv));
		$ppa = date("d",mktime(0, 0, 0, $kk, 1, $vv));
		$vvl = date("Y",mktime(0, 0, 0, $kk+1, 0, $vv));
		$kkl = date("m",mktime(0, 0, 0, $kk+1, 0, $vv));
		$ppl = date("d",mktime(0, 0, 0, $kk+1, 0, $vv));

		$query = "	SELECT distinct koodi, nimi
					FROM maat
					where nimi != '' and eu != ''
					ORDER BY koodi";
		$vresult = mysql_query($query) or pupe_error($query);

		$eumaat = "";
		while ($row = mysql_fetch_array($vresult)) {
			$eumaat .= "'$row[koodi]',";
		}
		$eumaat = substr($eumaat, 0, -1);

		if ($kayttajan_valinta_maa != "") {
			$maa = $kayttajan_valinta_maa;
		}
		else {
			$maa = $yhtiorow["maa"];
		}

		// tuonti vai vienti
		if ($tapa == "tuonti") {
			$maalisa = "maamaara in ('', '$maa') and maalahetys in ('',$eumaat) and maalahetys != '$maa'";
		}
		else {
			$tapa = "vienti";
			$maalisa = "maalahetys in ('', '$maa') and maamaara in ('',$eumaat) and maamaara != '$maa'";
		}

		if ($lisavar == "S") {
			$lisavarlisa = "  and (tilausrivi.perheid2=0 or tilausrivi.perheid2=tilausrivi.tunnus) ";
		}
		else {
			$lisavarlisa = "";
		}

		if ($vaintullinimike != "") {
			$vainnimikelisa  = " and tuote.tullinimike1 = '$vaintullinimike' ";
			$vainnimikelisa2 = " tilausrivi.tunnus, ";
			$vainnimikegroup = " ,9 ";
		}
		else {
			$vainnimikelisa  = "";
			$vainnimikelisa2 = "";
			$vainnimikegroup = "";
		}

		$query = "";
		// t�ss� tulee sitten nimiketietueet unionilla
		if ($tapahtumalaji == "kaikki" or $tapahtumalaji == "keikka") {
			$query = "	(SELECT
						tuote.tullinimike1,
						if (lasku.maa_lahetys='', ifnull(varastopaikat.maa, lasku.yhtio_maa), lasku.maa_lahetys) maalahetys,
						(SELECT alkuperamaa FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.alkuperamaa!='' ORDER BY if (alkuperamaa='$yhtiorow[maa]','2','1') LIMIT 1) alkuperamaa,
						if (lasku.maa_maara='', lasku.toim_maa, lasku.maa_maara) maamaara,
						lasku.kuljetusmuoto,
						lasku.kauppatapahtuman_luonne,
						tullinimike.su_vientiilmo su,
						'Keikka' as tapa,
						$vainnimikelisa2
						max(lasku.laskunro) laskunro,
						max(tuote.tuoteno) tuoteno,
						left(max(tuote.nimitys), 40) nimitys,			
						round(sum(tilausrivi.kpl),0) kpl,
						if (round(sum(if (lasku.summa > tilausrivi.rivihinta, tilausrivi.rivihinta / lasku.summa, 1) * lasku.bruttopaino), 0) > 0.5, round(sum(if (lasku.summa > tilausrivi.rivihinta, tilausrivi.rivihinta / lasku.summa, 1) * lasku.bruttopaino), 0), 1) as paino,
						if (round(sum(tilausrivi.rivihinta),0) > 0.50, round(sum(tilausrivi.rivihinta),0), 1) rivihinta,
						group_concat(lasku.tunnus) as kaikkitunnukset,
						group_concat(distinct tilausrivi.perheid2) as perheid2set,
						group_concat(concat(\"'\",tuote.tuoteno,\"'\") SEPARATOR ',') as kaikkituotteet
						FROM lasku use index (yhtio_tila_mapvm)
						JOIN tilausrivi use index (uusiotunnus_index) ON tilausrivi.uusiotunnus=lasku.tunnus and tilausrivi.yhtio=lasku.yhtio and tilausrivi.kpl > 0 $lisavarlisa
						JOIN tuote use index (tuoteno_index) ON tuote.yhtio=lasku.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = '' $vainnimikelisa
						LEFT JOIN tullinimike ON tuote.tullinimike1=tullinimike.cn and tullinimike.kieli = '$yhtiorow[kieli]' and tullinimike.cn != ''
						LEFT JOIN varastopaikat ON varastopaikat.yhtio=lasku.yhtio and varastopaikat.tunnus=lasku.varasto
						WHERE lasku.kohdistettu = 'X'
						and lasku.tila = 'K'
						and lasku.vanhatunnus = 0
						and lasku.kauppatapahtuman_luonne != '999'
						and lasku.yhtio = '$kukarow[yhtio]'
						and lasku.mapvm >= '$vva-$kka-$ppa'
						and lasku.mapvm <= '$vvl-$kkl-$ppl'
						GROUP BY 1,2,3,4,5,6,7,8 $vainnimikegroup
						HAVING $maalisa)";
		}

		if ($tapahtumalaji == "kaikki") {
			$query .= "	UNION";
		}

		if ($tapahtumalaji == "kaikki" or $tapahtumalaji == "lasku") {
			$query .= "	(SELECT
						tuote.tullinimike1,
						if (lasku.maa_lahetys='', ifnull(varastopaikat.maa, lasku.yhtio_maa), lasku.maa_lahetys) maalahetys,
						(SELECT alkuperamaa FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.alkuperamaa!='' ORDER BY if (alkuperamaa='$yhtiorow[maa]','2','1') LIMIT 1) alkuperamaa,
						if (lasku.maa_maara='', lasku.toim_maa, lasku.maa_maara) maamaara,
						lasku.kuljetusmuoto,
						lasku.kauppatapahtuman_luonne,
						tullinimike.su_vientiilmo su,
						'Lasku' as tapa,
						$vainnimikelisa2
						max(lasku.laskunro) laskunro,
						max(tuote.tuoteno) tuoteno,
						left(max(tuote.nimitys), 40) nimitys,
						round(sum(tilausrivi.kpl),0) kpl,
						if (round(sum((if (lasku.summa > tilausrivi.rivihinta, tilausrivi.rivihinta / lasku.summa, 1))*lasku.bruttopaino),0) > 0.5, round(sum((if (lasku.summa > tilausrivi.rivihinta, tilausrivi.rivihinta / lasku.summa, 1))*lasku.bruttopaino),0), if (round(sum(tilausrivi.kpl*tuote.tuotemassa),0) > 0.5, round(sum(tilausrivi.kpl*tuote.tuotemassa),0),1)) paino,
						if (round(sum(tilausrivi.rivihinta),0) > 0.50,round(sum(tilausrivi.rivihinta),0), 1) rivihinta,
						group_concat(lasku.tunnus) as kaikkitunnukset,
						group_concat(distinct tilausrivi.perheid2) as perheid2set,
						group_concat(concat(\"'\",tuote.tuoteno,\"'\") SEPARATOR ',') as kaikkituotteet
						FROM lasku use index (yhtio_tila_tapvm)
						JOIN tilausrivi use index (yhtio_otunnus) ON tilausrivi.otunnus=lasku.tunnus and tilausrivi.yhtio=lasku.yhtio and tilausrivi.kpl > 0 $lisavarlisa
						JOIN tuote use index (tuoteno_index) ON tuote.yhtio=lasku.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = '' $vainnimikelisa
						LEFT JOIN tullinimike ON tuote.tullinimike1=tullinimike.cn and tullinimike.kieli = '$yhtiorow[kieli]' and tullinimike.cn != ''
						LEFT JOIN varastopaikat ON varastopaikat.yhtio=lasku.yhtio and varastopaikat.tunnus=lasku.varasto
						WHERE lasku.tila = 'L'
						and lasku.alatila = 'X'
						and lasku.kauppatapahtuman_luonne != '999'
						and lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tapvm >= '$vva-$kka-$ppa'
						and lasku.tapvm <= '$vvl-$kkl-$ppl'
						GROUP BY 1,2,3,4,5,6,7,8 $vainnimikegroup
						HAVING $maalisa)";
		}

		if ($tapahtumalaji == "kaikki") {
			$query .= "	UNION";
		}

		if ($tapahtumalaji == "kaikki" or $tapahtumalaji == "siirtolista") {
			$query .= "	(SELECT
						tuote.tullinimike1,
						if (lasku.maa_lahetys='', ifnull(varastopaikat.maa, lasku.yhtio_maa), lasku.maa_lahetys) maalahetys,
						(SELECT alkuperamaa FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.alkuperamaa!='' ORDER BY if (alkuperamaa='$yhtiorow[maa]','2','1') LIMIT 1) alkuperamaa,
						if (lasku.maa_maara='', lasku.toim_maa, lasku.maa_maara) maamaara,
						lasku.kuljetusmuoto,
						lasku.kauppatapahtuman_luonne,
						tullinimike.su_vientiilmo su,
						'Siirtolista' as tapa,
						$vainnimikelisa2
						max(lasku.tunnus) laskunro,
						max(tuote.tuoteno) tuoteno,
						left(max(tuote.nimitys), 40) nimitys,
						round(sum(tilausrivi.kpl),0) kpl,
						if (count(tilausrivi.tunnus) = count(if (tuote.tuotemassa > 0,1,0)),if (round(sum(tilausrivi.kpl*tuote.tuotemassa),0) = 0,1,round(sum(tilausrivi.kpl*tuote.tuotemassa),0)),if (round(sum((tilausrivi.rivihinta/lasku.summa)*lasku.bruttopaino),0) > 0.5, round(sum((tilausrivi.rivihinta/lasku.summa)*lasku.bruttopaino),0), if (round(sum(tilausrivi.kpl*tuote.tuotemassa),0) > 0.5, round(sum(tilausrivi.kpl*tuote.tuotemassa),0),1))) paino,
						if (round(sum(tilausrivi.rivihinta),0) > 0.50, round(sum(tilausrivi.rivihinta),0), 1) rivihinta,
						group_concat(lasku.tunnus) as kaikkitunnukset,
						group_concat(distinct tilausrivi.perheid2) as perheid2set,
						group_concat(concat(\"'\",tuote.tuoteno,\"'\") SEPARATOR ',') as kaikkituotteet
						FROM lasku use index (yhtio_tila_tapvm)
						JOIN tilausrivi use index (yhtio_otunnus) ON tilausrivi.otunnus=lasku.tunnus and tilausrivi.yhtio=lasku.yhtio and tilausrivi.kpl > 0 $lisavarlisa
						JOIN tuote use index (tuoteno_index) ON tuote.yhtio=lasku.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = '' $vainnimikelisa
						LEFT JOIN tullinimike ON tuote.tullinimike1=tullinimike.cn and tullinimike.kieli = '$yhtiorow[kieli]' and tullinimike.cn != ''
						LEFT JOIN varastopaikat ON varastopaikat.yhtio=lasku.yhtio and varastopaikat.tunnus=lasku.varasto
						WHERE lasku.tila = 'G'
						and lasku.alatila = 'V'
						and lasku.kauppatapahtuman_luonne != '999'
						and lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tapvm >= '$vva-$kka-$ppa'
						and lasku.tapvm <= '$vvl-$kkl-$ppl'
						GROUP BY 1,2,3,4,5,6,7,8 $vainnimikegroup
						HAVING $maalisa)";
		}

		$query .= "	ORDER BY tullinimike1, maalahetys, alkuperamaa, maamaara, kuljetusmuoto, kauppatapahtuman_luonne, laskunro, tuoteno";
		$result = mysql_query($query) or pupe_error($query);

		$nim     = "";
		$lask    = 1;
		$arvoyht = 0;
		$virhe   = 0;

		if ($outputti == "tilasto") {
			// tehd��n tilastoarvot listausta
			$tilastoarvot = "<table>
				<tr>
				<th>#</th>
				<th>".t("Tullinimike")."</th>
				<th>".t("Alkuper�maa")."</th>
				<th>".t("L�hetysmaa")."</th>
				<th>".t("M��r�maa")."</th>
				<th>".t("Kuljetusmuoto")."</th>
				<th>".t("Kauppat. luonne")."</th>
				<th>".t("Tilastoarvo")."</th>
				<th>".t("Paino")."</th>
				<th>".t("2-paljous")."</th>
				<th>".t("2-paljous m��r�")."</th>
				<th>".t("Laskutusarvo")."</th>
				</tr>";

				if (isset($workbook)) {
					$worksheet->write($excelrivi, 1, "Tullinimike", $format_bold);
					$worksheet->write($excelrivi, 2, "Alkuper�maa", $format_bold);
					$worksheet->write($excelrivi, 3, "L�hetysmaa", $format_bold);
					$worksheet->write($excelrivi, 4, "M��r�maa", $format_bold);
					$worksheet->write($excelrivi, 5, "Kuljetusmuoto", $format_bold);
					$worksheet->write($excelrivi, 6, "Kauppat. luonne", $format_bold);
					$worksheet->write($excelrivi, 7, "Tilastoarvo", $format_bold);
					$worksheet->write($excelrivi, 8, "Paino", $format_bold);
					$worksheet->write($excelrivi, 9, "KM", $format_bold);
					$worksheet->write($excelrivi, 10, "2-paljous", $format_bold);
					$worksheet->write($excelrivi, 11, "2-paljous m��r�", $format_bold);
					$worksheet->write($excelrivi, 12, "Laskutusarvo", $format_bold);

					$excelrivi++;
				}

		}
		else {
			// tehd��n kaunista ruutukamaa
			$ulos = "<table>";
			$ulos .= "<tr>";
			$ulos .= "<th>".t("Laskunro")."</th>";
			$ulos .= "<th>".t("Tuoteno")."</th>";
			$ulos .= "<th>".t("Nimitys")."</th>";
			$ulos .= "<th>".t("Tullinimike")."</th>";
			$ulos .= "<th>".t("KT")."</th>";
			$ulos .= "<th>".t("AM")."</th>";
			$ulos .= "<th>".t("LM")."</th>";
			$ulos .= "<th>".t("MM")."</th>";
			$ulos .= "<th>".t("KM")."</th>";
			$ulos .= "<th>".t("Rivihinta")."</th>";
			$ulos .= "<th>".t("Paino")."</th>";
			$ulos .= "<th>".t("2. paljous")."</th>";
			$ulos .= "<th>".t("M��r�")."</th>";

			if ($lisavar == "S") {
				$ulos .= "<th>".t("Tehdaslis�varusteet")."</th>";
			}

			$ulos .= "<th>".t("Virhe")."</th>";
			$ulos .= "</tr>";

			if (isset($workbook)) {
				$worksheet->write($excelrivi, 1, "Laskunro", $format_bold);
				$worksheet->write($excelrivi, 2, "Tuoteno", $format_bold);
				$worksheet->write($excelrivi, 3, "Nimitys", $format_bold);
				$worksheet->write($excelrivi, 4, "Tullinimike", $format_bold);
				$worksheet->write($excelrivi, 5, "KT", $format_bold);
				$worksheet->write($excelrivi, 6, "AM", $format_bold);
				$worksheet->write($excelrivi, 7, "LM", $format_bold);
				$worksheet->write($excelrivi, 8, "MM", $format_bold);
				$worksheet->write($excelrivi, 9, "KM", $format_bold);
				$worksheet->write($excelrivi, 10, "Rivihinta", $format_bold);
				$worksheet->write($excelrivi, 11, "Paino", $format_bold);
				$worksheet->write($excelrivi, 12, "2. paljous", $format_bold);
				$worksheet->write($excelrivi, 13, "Kpl", $format_bold);
				if ($lisavar == "S") {
					$worksheet->write($excelrivi, 12, "Tehdaslis�varusteet", $format_bold);
				}
				$excelrivi++;
			}
		}

		// 1. L�hett�j�tietue

		// ytunnus konekielell�
		$ytunnus = sprintf ('%08d', str_replace('-','',$yhtiorow["ytunnus"]));

		// Suomen maatunnus intrastatiksi
		$maatunnus = "0037";

		// ytunnuksen lis�osa
		$ylisatunnus = $yhtiorow["int_koodi"];

		$lah  = sprintf ('%-3.3s', 		"KON");
		$lah .= sprintf ('%-17.17s', $maatunnus.$ytunnus.$ylisatunnus);
		$lah .= "\r\n";

		// 2. Otsikkotietue
		// p�iv�n numero
		$pvanumero = sprintf ('%03d',date('z')+1);
		$vuosi = sprintf ('%02d', substr($vv,-2));
		$kuuka = sprintf ('%02d', $kk);

		$ots  = sprintf ('%-3.3s', 		"OTS");																									//tietuetunnus
		$ots .= sprintf ('%-13.13s', 	date("y").$yhtiorow["tilastotullikamari"].$pvanumero.$yhtiorow["intrastat_sarjanro"].$tilastoloppu);	//Tilastonumero
		$ots .= sprintf ('%-1.1s',		$laji);																									//Onko tuotia vai vienti�, kts alkua...
		$ots .= sprintf ('%-4.4s',		$vuosi.$kuuka);																							//tilastointijakso
		$ots .= sprintf ('%-3.3s',		"T"); 																									//tietok�sittelykoodi
		$ots .= sprintf ('%-13.13s',	""); 																									//virheellisen tilastonro, tyhj�ksi j�tet��n....
		$ots .= sprintf ('%-17.17s', 	"FI".$ytunnus.$ylisatunnus);																			//tiedoantovelvollinen
		$ots .= sprintf ('%-17.17s', 	"");																									//t�h�n vois laittaa asiamiehen tiedot...
		$ots .= sprintf ('%-10.10s', 	"");																									//t�h�n vois laittaa asiamiehen lis�tiedot...
		$ots .= sprintf ('%-17.17s', 	$yhtiorow["tilastotullikamari"]);																		//tilastotullikamari
		$ots .= sprintf ('%-3.3s',	 	$yhtiorow["valkoodi"]);																					//valuutta
		$ots .= "\r\n";

		while ($row = mysql_fetch_array($result)) {

			// tehd��n tarkistukset	vai jos EI OLE k�ytt�j�n valitsemaa maata
			if ($kayttajan_valinta_maa == "") {
				require ("inc/intrastat_tarkistukset.inc");
			}

			if ($row["perheid2set"] != "0" and $lisavar == "S") {
				$query  = "	SELECT ";

				if ($row["tapa"] != "Keikka") {
					$query .= "	if (round(sum((tilausrivi.kpl * tilausrivi.hinta * lasku.vienti_kurssi *
								(SELECT if (tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin) FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno LIMIT 1)
								/ lasku.summa) * lasku.bruttopaino), 0) > 0.5,
								round(sum((tilausrivi.kpl * tilausrivi.hinta * lasku.vienti_kurssi *
								(SELECT if (tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin) FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno LIMIT 1)
								/ lasku.summa) * lasku.bruttopaino), 0), 1) as paino,
								if (round(sum(tilausrivi.rivihinta),0) > 0.50, round(sum(tilausrivi.rivihinta),0), 1) rivihinta";
				}
				elseif ($row["tapa"] != "Lasku") {
					$query .= "	if (round(sum((tilausrivi.rivihinta/lasku.summa)*lasku.bruttopaino),0) > 0.5, round(sum((tilausrivi.rivihinta/lasku.summa)*lasku.bruttopaino),0), if (round(sum(tilausrivi.kpl*tuote.tuotemassa),0) > 0.5, round(sum(tilausrivi.kpl*tuote.tuotemassa),0),1)) paino,
								if (round(sum(tilausrivi.rivihinta),0) > 0.50,round(sum(tilausrivi.rivihinta),0), 1) rivihinta";
				}
				else {
					$query .= "	if (round(sum((tilausrivi.rivihinta/lasku.summa)*lasku.bruttopaino),0) > 0.5, round(sum((tilausrivi.rivihinta/lasku.summa)*lasku.bruttopaino),0), if (round(sum(tilausrivi.kpl*tuote.tuotemassa),0) > 0.5, round(sum(tilausrivi.kpl*tuote.tuotemassa),0),1)) paino,
								round(sum(tilausrivi.rivihinta), 0) rivihinta";
				}

				$query .= "	FROM tilausrivi use index (yhtio_perheid2)
							JOIN lasku ON tilausrivi.otunnus = lasku.tunnus and tilausrivi.yhtio = lasku.yhtio
							JOIN tuote use index (tuoteno_index) ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = ''
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.kpl > 0
							and tilausrivi.perheid2 > 0
							and tilausrivi.perheid2 != tilausrivi.tunnus
							and tilausrivi.perheid2 in ($row[perheid2set])";
				$lisavarres = mysql_query($query) or pupe_error($query);
				$lisavarrow = mysql_fetch_array($lisavarres);

				$row["paino"] 		+= $lisavarrow["paino"];
				$row["rivihinta"] 	+= $lisavarrow["rivihinta"];
			}

			// 3. Nimiketietue
			$nim .= sprintf ('%-3.3s', 		"NIM");																								//tietuetunnus
			$nim .= sprintf ('%05d', 		$lask);																								//j�rjestysnumero
			$nim .= sprintf ('%-8.8s', 		$row["tullinimike1"]);																				//Tullinimike CN
			$nim .= sprintf ('%-2.2s', 		$row["kauppatapahtuman_luonne"]);																	//kauppatapahtuman luonne

			if ($tapa == "tuonti") {
				$nim .= sprintf ('%-2.2s', 	$row["alkuperamaa"]);																				//alkuper�maa
				$nim .= sprintf ('%-2.2s', 	$row["maalahetys"]);																				//l�hetysmaa
				$nim .= sprintf ('%-2.2s', 	"");
			}
			else {
				$nim .= sprintf ('%-2.2s', 	"");
				$nim .= sprintf ('%-2.2s', 	"");
				$nim .= sprintf ('%-2.2s', 	$row["maamaara"]);																					//m��r�maa
			}

			$nim .= sprintf ('%-1.1s', 		$row["kuljetusmuoto"]);																				//kuljetusmuoto
			$nim .= sprintf ('%010d', 		$row["rivihinta"]);																					//tilastoarvo
			$nim .= sprintf ('%-15.15s',	"");																								//ilmoitajan viite...
			$nim .= sprintf ('%-3.3s',		"WT");																								//m��r�ntarkennin 1
			$nim .= sprintf ('%-3.3s',		"KGM");																								//paljouden lajikoodi
			$nim .= sprintf ('%010d', 		$row["paino"]);																						//nettopaino
			$nim .= sprintf ('%-3.3s',		"AAE");																								//m��r�ntarkennin 2, muu paljous

			if ($row["su"] != '') {
				$nim .= sprintf ('%-3.3s',		$row["su"]); 																					//2 paljouden lajikoodi
				$nim .= sprintf ('%010d', 		$row["kpl"]);																					//2 paljouden m��r�
			}
			else {
				$nim .= sprintf ('%-3.3s',		""); 																							//2 paljouden lajikoodi
				$nim .= sprintf ('%010d', 		"");																							//2 paljouden m��r�
			}

			$nim .= sprintf ('%010d', 		$row["rivihinta"]);																					//nimikkeen laskutusarvo
			$nim .= "\r\n";

			if ($outputti == "tilasto") {
				// tehd��n tilastoarvolistausta
				$tilastoarvot .= "<tr>";
				$tilastoarvot .= "<td>$lask</td>";																									//j�rjestysnumero
				$tilastoarvot .= "<td><a href='intrastat.php?tee=tulosta&tapa=$tapa&kk=$kk&vv=$vv&outputti=$outputti&lahetys=nope&lisavar=$lisavar&vaintullinimike=$row[tullinimike1]&tapahtumalaji=$tapahtumalaji'>$row[tullinimike1]</></td>";	//Tullinimike CN

				if ($tapa == "tuonti") {
					$tilastoarvot .= "<td>$row[alkuperamaa]</td>";																					//alkuper�maa
					$tilastoarvot .= "<td>$row[maalahetys]</td>";																					//l�hetysmaa
					$tilastoarvot .= "<td></td>";
				}
				else {
					$tilastoarvot .= "<td></td>";
					$tilastoarvot .= "<td></td>";
					$tilastoarvot .= "<td>$row[maamaara]</td>";																					//m��r�maa
				}

				$tilastoarvot .= "<td>$row[kuljetusmuoto]</td>";																					//kuljetusmuoto
				$tilastoarvot .= "<td>$row[kauppatapahtuman_luonne]</td>";																			//kauppatapahtuman luonne
				$tilastoarvot .= "<td>$row[rivihinta]</td>";																						//tilastoarvo
				$tilastoarvot .= "<td>$row[paino] | $row[paino2]</td>";																							//nettopaino

				if ($row["su"] != '') {
					$tilastoarvot .= "<td>$row[su]</td>"; 																							//2 paljouden lajikoodi
					$tilastoarvot .= "<td>$row[kpl]</td>";																							//2 paljouden m��r�
				}
				else {
					$tilastoarvot .= "<td></td>"; 																									//2 paljouden lajikoodi
					$tilastoarvot .= "<td></td>";																									//2 paljouden m��r�
				}

				$tilastoarvot .= "<td>$row[rivihinta]</td>";																						//nimikkeen laskutusarvo
				$tilastoarvot .= "</tr>";

				if (isset($workbook)) {
					$worksheet->write($excelrivi, 1, $lask);
					$worksheet->write($excelrivi, 2, $row["tullinimike1"]);

					if ($tapa == "tuonti") {
						$worksheet->write($excelrivi, 3, $row["alkuperamaa"]);
						$worksheet->write($excelrivi, 4, $row["maalahetys"]);
					}
					else {
						$worksheet->write($excelrivi, 5, $row["maamaara"]);

					}

					$worksheet->write($excelrivi, 6, $row["kuljetusmuoto"]);
					$worksheet->write($excelrivi, 7, $row["kauppatapahtuman_luonne"]);
					$worksheet->write($excelrivi, 8, $row["rivihinta"]);
					$worksheet->write($excelrivi, 9, $row["paino"]);
					if ($row["su"] != '') {
						$worksheet->write($excelrivi, 10, $row["su"]);
						$worksheet->write($excelrivi, 11, $row["kpl"]);
					}
					$worksheet->write($excelrivi, 12, $row["rivihinta"]);

					$excelrivi++;
				}

			}
			else {
				// tehd��n kaunista ruutukamaa
				$ulos .= "<tr class='aktiivi'>";

				if ($vaintullinimike != "") {
					$ulos .= "<td valign='top'><a href='tilauskasittely/vientitilauksen_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$row[kaikkitunnukset]&vaintullinimike=$vaintullinimike&kayttajan_valinta_maa=$kayttajan_valinta_maa&tapahtumalaji=$tapahtumalaji&lopetus=$lopetus//vaintullinimike=$vaintullinimike//kayttajan_valinta_maa=$kayttajan_valinta_maa//tapahtumalaji=$tapahtumalaji'>$row[laskunro]</a></td>";
				}
				else {
					$ulos .= "<td valign='top'>".$row["laskunro"]."</td>";
				}

				$ulos .= "<td valign='top'>".$row["tuoteno"]."</td>";
				$ulos .= "<td valign='top'>".t_tuotteen_avainsanat($row, 'nimitys')."</td>";
	 			$ulos .= "<td valign='top'><a href='intrastat.php?tee=tulosta&tapa=$tapa&kk=$kk&vv=$vv&outputti=$outputti&lahetys=nope&lisavar=$lisavar&kayttajan_valinta_maa=$kayttajan_valinta_maa&vaintullinimike=$row[tullinimike1]&tapahtumalaji=$tapahtumalaji'>$row[tullinimike1]</></td>";	//Tullinimike CN
				$ulos .= "<td valign='top'>".$row["kauppatapahtuman_luonne"]."</td>";
				$ulos .= "<td valign='top'>".$row["alkuperamaa"]."</td>";
				$ulos .= "<td valign='top'>".$row["maalahetys"]."</td>";
				$ulos .= "<td valign='top'>".$row["maamaara"]."</td>";
				$ulos .= "<td valign='top'>".$row["kuljetusmuoto"]."</td>";
				$ulos .= "<td valign='top' align='right'>".$row["rivihinta"]."</td>";
				$ulos .= "<td valign='top' align='right'>".$row["paino"]."</td>";
				$ulos .= "<td valign='top'>".$row["su"]."</td>";
				$ulos .= "<td valign='top' align='right'>".$row["kpl"]."</td>";

				if ($lisavar == "S") {
					if ($row["perheid2set"] != "0") {
						$ulos .= "<td valign='top'>".t("Tehdaslis�varusteet").":<br>".t("Paino").": $lisavarrow[paino]<br>".t("Arvo").": $lisavarrow[rivihinta]</td>";
					}
					else {
						$ulos .= "<td valign='top'></td>";
					}
				}

				$ulos .= "<td valign='top'><font class='error'>".$virhetxt."</font></td>";
				$ulos .= "</tr>";

				if (isset($workbook)) {
					$worksheet->write($excelrivi, 1, $row["laskunro"]);
					$worksheet->write($excelrivi, 2, $row["tuoteno"]);
					$worksheet->write($excelrivi, 3, t_tuotteen_avainsanat($row, 'nimitys'));
					$worksheet->write($excelrivi, 4, $row["tullinimike1"]);
					$worksheet->write($excelrivi, 5, $row["kauppatapahtuman_luonne"]);
					$worksheet->write($excelrivi, 6, $row["alkuperamaa"]);
					$worksheet->write($excelrivi, 7, $row["maalahetys"]);
					$worksheet->write($excelrivi, 8, $row["maamaara"]);
					$worksheet->write($excelrivi, 9, $row["kuljetusmuoto"]);
					$worksheet->write($excelrivi, 10, $row["rivihinta"]);
					$worksheet->write($excelrivi, 11, $row["paino"]);
					$worksheet->write($excelrivi, 12, $row["su"]);
					$worksheet->write($excelrivi, 13, $row["kpl"]);
					if ($lisavar == "S") {
						$worksheet->write($excelrivi, 12, $lisavarrow["paino"]."kg/".$lisavarrow["rivihinta"]."eur");
					}
					$excelrivi++;
				}
			}

			// summaillaan
			$lask++;
			$arvoyht		+= $row["rivihinta"];
			$bruttopaino	+= $row["paino"];
			$totsumma		+= $row["rivihinta"];
			$totkpl			+= $row["kpl"];
		}

		// 4. Summatietue
		$sum  = sprintf ('%-3.3s', 		"SUM");																									//tietuetunnus
		$sum .= sprintf ('%018d', 		$lask-1);																								//nimikkeiden lukum��r�
		$sum .= sprintf ('%018d', 		$arvoyht);																								//laskutusarvo yhteens�
		$sum .= "\r\n";

		if ($outputti == "tilasto") {
			// tehd��n tilaustoarvolistausta
			$tilastoarvot .= "<tr>";
			$tilastoarvot .= "<th colspan='7'>".t("Yhteens�").":</td>";
			$tilastoarvot .= "<th>$arvoyht</th>";
			$tilastoarvot .= "<th colspan='4'></th>";
			$tilastoarvot .= "</tr>";
			$tilastoarvot .= "</table>";

			if (isset($workbook)) {
				$worksheet->write($excelrivi, 8, $arvoyht, $format_bold);
			}
		}
		else {
			// tehd��n kaunista ruutukamaa
			$ulos .= "<tr>";
			$ulos .= "<th colspan='9'>".t("Yhteens�").":</th>";
			$ulos .= "<th>$totsumma</th>";
			$ulos .= "<th>$bruttopaino</th>";
			$ulos .= "<th></th>";
			$ulos .= "<th>$totkpl</th>";
			$ulos .= "<th></th>";

			if ($lisavar == "S") {
				$ulos .= "<th></th>";
			}

			$ulos .= "</tr>";
			$ulos .= "</table>";

			if (isset($workbook)) {
				$worksheet->write($excelrivi, 10, $totsumma, $format_bold);
				$worksheet->write($excelrivi, 11, $bruttopaino, $format_bold);
				$worksheet->write($excelrivi, 13, $totkpl, $format_bold);
			}
		}

		if (($lahetys == "mina" or $lahetys == "tuli" or $lahetys == "mole") and ($yhtiorow["tilastotullikamari"] == 0 or $yhtiorow["intrastat_sarjanro"] == "")) {
			echo "<font class='error'>".t("Yhti�tiedoista puuttuu pakollisia tietoja (tilastotullikamari/intrastat_sarjanro)").!"</font>";
			$virhe++;
		}

		// ei virheit� .. ja halutaan l�hett�� jotain meilej�
		if ($virhe == 0 and $lahetys != "nope" and $kayttajan_valinta_maa == '' and $tapahtumalaji == "kaikki") {

			//PGP-encryptaus labeli
			$label  = '';
			$label .= "l�hett�j�: $yhtiorow[nimi]\r\n";
			$label .= "sis�lt�: vientitullaus/sis�kaupantilasto\r\n";
			$label .= "kieli: ASCII\r\n";
			$label .= "jakso: $vv$kk\r\n";
			$label .= "koko aineiston tietuem��r�: $lask-1\r\n";
			$label .= "koko aineiston vienti-, verotus- tai laskutusarvo: $arvoyht\r\n";

			$recipient = "pgp-key Customs Finland <ascii.intra@tulli.fi>"; 				// t�m� on tullin virallinen avain

			if ($lahetys == "test") {
				$recipient = "pgp-testkey Customs Finland <test.ascii.intra@tulli.fi>"; 	// t�m� on tullin testiavain
			}

			$message = '';
			$message = $label;
			require("inc/gpg.inc");
			$otsikko_gpg = $encrypted_message;
			$otsikko_plain = $message;

			//PGP-encryptaus atktietue
			$recipient = "pgp-key Customs Finland <ascii.intra@tulli.fi>"; 				// t�m� on tullin virallinen avain

			if ($lahetys == "test") {
				$recipient = "pgp-testkey Customs Finland <test.ascii.intra@tulli.fi>"; 	// t�m� on tullin testiavain
			}

			$message = '';
			$message = $lah.$ots.$nim.$sum;
			require("inc/gpg.inc");
			$tietue_gpg = $encrypted_message;
			$tietue_plain = $message;

			$bound = uniqid(time()."_") ;

			$header  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n";
			$header .= "MIME-Version: 1.0\n";
			$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n";

			$content = "--$bound\n";

			$content .= "Content-Type: application/pgp-encrypted;\n" ;
			$content .= "Content-Transfer-Encoding: base64\n" ;
			$content .= "Content-Disposition: attachment; filename=\"otsikko.pgp\"\n\n";
			$content .= chunk_split(base64_encode($otsikko_gpg));
			$content .= "\n";

			$content .= "--$bound\n";

			$content .= "Content-Type: application/pgp-encrypted;\n";
			$content .= "Content-Transfer-Encoding: base64\n";
			$content .= "Content-Disposition: attachment; filename=\"tietue.pgp\"\n\n";
			$content .= chunk_split(base64_encode($tietue_gpg));
			$content .= "\n";

			$content .= "--$bound\n";

			if ($lahetys == "tuli" or $lahetys == "mole") {
				// l�hetet��n meili tulliin
				$to = 'ascii.intrastat@tulli.fi';			// t�m� on tullin virallinen osoite
				mail($to, "", $content, $header, "-f $yhtiorow[postittaja_email]");
				echo "<font class='message'>".t("Tiedot l�hetettiin tulliin").".</font><br><br>";
			}
			elseif ($lahetys == "test") {
				// l�hetet��n TESTI meili tulliin
				$to = 'test.ascii.intrastat@tulli.fi';		// t�m� on tullin testiosoite
				mail($to, "", $content, $header, "-f $yhtiorow[postittaja_email]");
				echo "<font class='message'>".t("Testitiedosto l�hetettiin tullin testipalvelimelle").".</font><br><br>";
			}
			else {
				echo "<font class='message'>".t("Tietoja EI l�hetetty tulliin").".</font><br><br>";
			}

			// liitet��n mukaan my�s salaamattomat tiedostot
			$content .= "Content-Type: text/plain;\n" ;
			$content .= "Content-Transfer-Encoding: base64\n" ;
			$content .= "Content-Disposition: attachment; filename=\"otsikko.txt\"\n\n";
			$content .= chunk_split(base64_encode($otsikko_plain));
			$content .= "\n";

			$content .= "--$bound\n";

			$content .= "Content-Type: text/plain;\n";
			$content .= "Content-Transfer-Encoding: base64\n";
			$content .= "Content-Disposition: attachment; filename=\"tietue.txt\"\n\n";
			$content .= chunk_split(base64_encode($tietue_plain));
			$content .= "\n";

			$content .= "--$bound\n";

			// katotaan l�hetet��nk� meili k�ytt�j�lle
			if (($lahetys == "mina" or $lahetys == "mole" or $lahetys == "test") and $kukarow["eposti"] != "") {
				// j� l�hetet��n k�ytt�j�lle
				mail($kukarow["eposti"], mb_encode_mimeheader("$yhtiorow[nimi] - ".t("Intrastat")." ".t($tapa)."-".t("ilmoitus")." $vv/$kk ($kukarow[kuka])", "ISO-8859-1", "Q"), $content, $header, "-f $yhtiorow[postittaja_email]");
			}

			// ja aina adminille
			mail($yhtiorow["alert_email"], mb_encode_mimeheader("$yhtiorow[nimi] - ".t("Intrastat")." ".t($tapa)."-".t("ilmoitus")." $vv/$kk ($kukarow[kuka])", "ISO-8859-1", "Q"), $content, $header, "-f $yhtiorow[postittaja_email]");

		}
		else {
			if ($virhe != 0) {
				echo "<font class='error'>".t("Aineistossa on virheit�")."! ".t("Korjaa virheet")."!</font><br>";
			}
			echo "<font class='error'>".t("Aineistoa EI l�hetetty minnek��n").".</font><br><br>";
		}

		if ($kayttajan_valinta_maa != "") {
			echo "<font class='error'>".t("Poikkeava ilmoitusmaa valittu, mit��n oikeellisuustarkistuksia ei tehty")."!</font><br><br>";
		}

		// echotaan oikea taulukko ruudulle
		if ($outputti == "tilasto") {
			echo "$tilastoarvot";
		}
		else {
			echo "$ulos";
		}

		if (isset($workbook) and $virhe == 0) {
			// We need to explicitly close the workbook
			$workbook->close();

			echo "<br><table>";
			echo "<tr><th>".t("Tallenna tulos").":</th>";
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='Intrastat.xls'>";
			echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
			echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
			echo "</table><br>";
		}
	}

	// K�ytt�liittym�
	if (!isset($kk)) $kk = date("m");
	if (!isset($vv)) $vv = date("Y");

	if ($tapa == "vientituonti") $tapa = "vienti";
	if ($tapa == "tuontivienti") $tapa = "tuonti";

	$sel1[$outputti] = "SELECTED";
	$sel2[$tapa]     = "SELECTED";
	$sel3[$lahetys]  = "SELECTED";
	$sel4[$lisavar]  = "SELECTED";
	$sel5[$tapahtumalaji] = "SELECTED";

	if ($excel != "") {
		$echecked = "checked";
	}
	else {
		$echecked = "";
	}

	echo "<br>

	<form method='post' action='$PHP_SELF'>
	<input type='hidden' name='tee' value='tulosta'>

	<table>
		<tr>
			<th>".t("Valitse ilmoitus")."</th>
			<td>
				<select name='tapa'>
				<option value='vienti' $sel2[vienti]>".t("Vienti-ilmoitus")."</option>
				<option value='tuonti' $sel2[tuonti]>".t("Tuonti-ilmoitus")."</option>
				</select>
			</td>
		</tr>";

	$query = "SELECT tunnus from yhtion_toimipaikat where yhtio = '$kukarow[yhtio]' and vat_numero != ''";
	$vresult = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($vresult) > 0) {
		echo "<tr>
				<th>".t("Valitse poikkeava ilmoitusmaa")."</th>
				<td>
					<select name='kayttajan_valinta_maa'>";
					echo "<option value='' $sel>".t("Ei valintaa")."</option>";

					$query = "	SELECT distinct koodi, nimi
								FROM maat
								where nimi != '' and eu != '' and koodi != '$yhtiorow[maa]'
								ORDER BY koodi";
					$vresult = mysql_query($query) or pupe_error($query);

					while ($row = mysql_fetch_array($vresult)) {
						$sel = '';
						if ($row[0] == $kayttajan_valinta_maa) {
							$sel = 'selected';
						}
						echo "<option value='$row[0]' $sel>$row[1]</option>";
					}

		echo "		</select>
				</td>
			</tr>";
	}

	echo "<tr>
			<th>".t("Sy�t� kausi (kk-vvvv)")."</th>
			<td>
				<input type='text' name='kk' value='$kk' size='3'>
				<input type='text' name='vv' value='$vv' size='5'>
			</td>
		</tr>
		<tr>
			<th>".t("N�yt� ruudulla")."</th>
			<td>
				<select name='outputti'>
				<option value='normi'   $sel1[normi]>".t("Normaalilistaus")."</option>
				<option value='tilasto' $sel1[tilasto]>".t("Tilastoarvolistaus")."</option>
				</select>
			</td>
		</tr>
		<tr>
			<th>".t("Tietojen l�hetys s�hk�postilla")."</th>
			<td>
			<select name='lahetys'>
			<option value='nope' $sel3[nope]>".t("�l� l�het� aineistoa minnek��n")."</option>
			<option value='mina' $sel3[mina]>".t("L�het� aineisto vain minulle")."</option>
			<option value='tuli' $sel3[tuli]>".t("L�het� aineisto vain tulliin")."</option>
			<option value='mole' $sel3[mole]>".t("L�het� aineisto tulliin sek� minulle")."</option>
			<option value='test' $sel3[test]>".t("L�het� testiaineisto tullin testipalvelimelle")."</option>
			</select>
		</tr>
		<tr>
			<th>".t("Tehdaslis�varusteet")."</th>
			<td>
			<select name='lisavar'>
			<option value='O' $sel4[O]>".t("Omilla riveill��n")."</option>
			<option value='S' $sel4[S]>".t("Yhdistet��n laitteeseen")."</option>
			</select>
		</tr>
		<tr>
			<th>".t("Tallenna excel")."</th>
			<td>
			<input type='checkbox' name='excel' $echecked>
			</select>
		</tr>
			<th>".t("Valitse tapahtumalaji")."</th>
			<td>
			<select name='tapahtumalaji'>
			<option value='kaikki' 		$sel5[kaikki]>".t("Kaikki")."</option>
			<option value='keikka' 		$sel5[keikka]>".t("Keikka")."</option>
			<option value='lasku'		$sel5[lasku]>".t("Lasku")."</option>
			<option value='siirtolista' $sel5[siirtolista]>".t("Siirtolista")."</option>
			</select>
	</table>

	<br>
	<input type='submit' value='".t("Luo aineisto")."'>
	</form>";

	require ("inc/footer.inc");

?>
