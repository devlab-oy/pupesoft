<?php

	if (!function_exists('print_ytunnus')) {
		function print_ytunnus ($ytunnus) {
			//k‰‰nt‰‰ ytunnuksen kauniseen muotoon
			$ytunnus = sprintf('%08d', $ytunnus);
			return substr($ytunnus,0,7)."-".substr($ytunnus,-1);
		}
	}

	function sadteksti_alku ($tulosulos, $laskurow, $sivut, $tavaraerittelyt, $kollit) {
		global $yhtiorow;
	
		$tulosulos .= "\r\n";
		$tulosulos .= "\r\n";
		$tulosulos .= "\r\n";
		$tulosulos .= "\r\n";
		
		//Kentt‰ 1 Imoitustyyppi, Ilmoitus
		//Kentt‰ 2 Viej‰
		//Kentt‰ 3 Lomakenro
		$tulosulos .= sprintf('%-27.27s', "");
		$tulosulos .= sprintf('%-15.15s', $yhtiorow["maakoodi"].print_ytunnus($yhtiorow["ytunnus"]))."\r\n";
		
		$tulosulos .= sprintf('%-8.8s', "");
		$tulosulos .= sprintf('%-36.36s', $yhtiorow["nimi"]);
		$tulosulos .= sprintf('%-4.4s', "EX");
		$tulosulos .= sprintf('%-1.1s', "A")."\r\n";

		$tulosulos .= sprintf('%-8.8s', "");
		$tulosulos .= sprintf('%-36.36s', $yhtiorow["osoite"])."\r\n";
		
		$tulosulos .= sprintf('%-8.8s', "");		
		$tulosulos .= sprintf('%-36.36s', $yhtiorow["postino"]." ".$yhtiorow["postino"]);
		$tulosulos .= sprintf('%-3.3s', "1");
		$tulosulos .= sprintf('%-3.3s', $sivut)."\r\n";
		
		//Kentt‰ 5 tavaraerien kokonaism‰‰r‰
		//Kentt‰ 6 Kollim‰‰r‰
		//Kentt‰ 7 Ilmoittajan viitenumero
		
		$tulosulos .= "\r\n";
		
		$tulosulos .= sprintf('%-47.47s', "");
		$tulosulos .= sprintf('%-6.6s', $tavaraerittelyt);
		$tulosulos .= sprintf('%-9.9s', $kollit);
		$tulosulos .= sprintf('%-18.18s', $laskurow["laskunro"])."\r\n";
		$tulosulos .= "\r\n";

		//Kentt‰ 8
		$tulosulos .= sprintf('%-8.8s', "");
		$tulosulos .= sprintf('%-36.36s', $laskurow["nimi"])."\r\n";

		$tulosulos .= sprintf('%-8.8s', "");
		$tulosulos .= sprintf('%-36.36s', $laskurow["osoite"])."\r\n";

		$tulosulos .= sprintf('%-8.8s', "");
		$tulosulos .= sprintf('%-36.36s', $laskurow["postino"]."".$laskurow["postitp"])."\r\n";

		$tulosulos .= sprintf('%-8.8s', "");
		$tulosulos .= sprintf('%-36.36s', $laskurow["maa"])."\r\n";

		$tulosulos .= "\r\n";
		
		//Kentt‰ 14
		//Kentt‰ 15 L‰hetysmaan koodi
		//Kentt‰ 17 M‰‰r‰maan koodi
		$tulosulos .= sprintf('%-27.27s', "");
		$tulosulos .= sprintf('%-23.23s', "")."\r\n";
		
		
		$tulosulos .= sprintf('%-8.8s', "");
		$tulosulos .= sprintf('%-39.39s', "");
		$tulosulos .= sprintf('%-16.16s', "FINLAND");
		$tulosulos .= sprintf('%-9.9s', "FI");
		$tulosulos .= sprintf('%-5.5s', $laskurow["maa_maara"])."\r\n";
		
		$tulosulos .= sprintf('%-8.8s', "");		
		$tulosulos .= sprintf('%-36.36s', "")."\r\n";
		
		$tulosulos .= sprintf('%-8.8s', "");
		$tulosulos .= sprintf('%-36.36s', "")."\r\n";

		$tulosulos .= "\r\n";
		
		//hetaan toimitusehto
		$query = "	SELECT selite, selitetark
					FROM avainsana
					WHERE yhtio	= '$yhtiorow[yhtio]'
					and laji	= 'TOIMEHTO'
					and selite	= '$laskurow[toimitusehto]'";
		$toimres = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($toimres) > 0) {
			$toimrow = mysql_fetch_array($toimres);
		}
		else {
			$toimrow["selite"] = "EXW";
		}
		
		//Kentt‰ 18 Sis‰maan kuljetus
		//Kentt‰ 19 Kontti
		//Kentt‰ 20 Toimitusehto
		$tulosulos .= sprintf('%-8.8s', "");
		$tulosulos .= sprintf('%-28.28s', $laskurow["sisamaan_kuljetus"]);
		$tulosulos .= sprintf('%-5.5s', "");
		$tulosulos .= sprintf('%-3.3s', $laskurow["kontti"]);
		$tulosulos .= sprintf('%-6.6s', $toimrow["selite"]);
		$tulosulos .= sprintf('%-20.20s', $toimrow["selitetark"])."\r\n";
		
		$tulosulos .= "\r\n";
		
		//Kentt‰ 21 Akt kuljetusv‰line
		//Kentt‰ 22 Valuutta ja kokonaissumma
		//Kentt‰ 24 Kauppatapahtuman luonne
		$tulosulos .= sprintf('%-8.8s', "");
		$tulosulos .= sprintf('%-28.28s', $laskurow["aktiivinen_kuljetus"]);
		$tulosulos .= sprintf('%-5.5s', $laskurow["aktiivinen_kuljetus_kansallisuus"]);
		$tulosulos .= sprintf('%-8.8s', $laskurow["valkoodi"]);
		$tulosulos .= sprintf('%-22.22s', $laskurow["summa"]);
		$tulosulos .= sprintf('%-2.2s', substr($laskurow["kauppatapahtuman_luonne"],0,1));
		$tulosulos .= sprintf('%-2.2s', substr($laskurow["kauppatapahtuman_luonne"],1,1))."\r\n";
		
		$tulosulos .= "\r\n";

		//Kentt‰ 25 Kuljetusmuoto
		//Kentt‰ 26 Sis‰maan kuljetusmuoto
		//Kentt‰ 28 Maksu ja pankkitietoja
		$tulosulos .= sprintf('%-8.8s', "");
		$tulosulos .= sprintf('%-10.10s', $laskurow["kuljetusmuoto"]);
		$tulosulos .= sprintf('%-32.32s', $laskurow["sisamaan_kuljetusmuoto"]);
		$tulosulos .= sprintf('%-15.15s', $laskurow["summa"]);
		
		if ($laskurow["vahennettava_era"] != 0) {
			$tulosulos .= sprintf('%-10.10s', $laskurow["vahennettava_era"])."\r\n";
		}
		else {
			$tulosulos .= sprintf('%-10.10s', "")."\r\n";
		}
				
		$tulosulos .= "\r\n";
		
		//lasketaan tilastoarvo, varmistetaan ett‰ summat menee oikein ottamalla abs-arvo erist‰, jos k‰ytt‰j‰ siis on antanu jotain negatiivisia summia tai jotain
		$tilastoarvo = $laskurow["summa"]-abs($laskurow["vahennettava_era"])+abs($laskurow["lisattava_era"]);
		
		$query = "	SELECT osoite
					FROM varastopaikat
					WHERE tunnus = '$laskurow[varasto]'
					and yhtio = '$yhtiorow[yhtio]'
					and osoite != ''";
		$varresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($varresult) > 0) {
			$varrow = mysql_fetch_array($varresult);
			$sijaintipaikka = $varrow["osoite"];
		}
		else {
			$sijaintipaikka = $yhtiorow["osoite"];
		}

		
		//Kentt‰ 29 Postumispaikka
		//Kentt‰ 30 Tavaran sijaintipaikka
		$tulosulos .= sprintf('%-8.8s', "");
		$tulosulos .= sprintf('%-18.18s', $laskurow["poistumistoimipaikka_koodi"]);
		$tulosulos .= sprintf('%-24.24s', "O ".$sijaintipaikka);
		
		if ($laskurow["lisattava_era"] != 0) {
			$tulosulos .= sprintf('%-15.15s', $laskurow["lisattava_era"]);
		}
		else {
			$tulosulos .= sprintf('%-15.15s', "");
		}
		
		$tulosulos .= sprintf('%-10.10s', $tilastoarvo)."\r\n";
		
		$tulosulos .= "\r\n";	

		return $tulosulos;
	}
	
	
	function sadteksti_rivi ($tulosulos, $laskurow, $rivirow, $tjarj, $extrat) {
		
		//tullausarvo lis‰erineen
		if ($extrat != 0 and $laskurow["summa"] != 0) {
			$tullarvo = round(($rivirow["rivihinta"]/$laskurow["summa"]*$extrat)+$rivirow["rivihinta"],2);
		}
		else {
			$tullarvo = $rivirow["rivihinta"];
		}
		
		//Tehd‰‰n nimityksist‰ array
		$nimitykset = explode("##", $rivirow["nimitys_hieno"]);
		
		// Kentt‰ 31 Nimitys
		//Kentt‰ 32 Tavaraer‰n j‰rjestysnumero
		//Kentt‰ 33 Taric koodi
		$tulosulos .= sprintf('%-8.8s', "");
		$tulosulos .= sprintf('%-40.40s', substr($nimitykset[0],0,35));
		$tulosulos .= sprintf('%-5.5s', $tjarj);
		$tulosulos .= sprintf('%-10.10s', $rivirow["tullinimike1"])."\r\n";

		$tulosulos .= sprintf('%-8.8s', "");
		$tulosulos .= sprintf('%-40.40s', substr($nimitykset[1],0,35))."\r\n";

		//Kentt‰ 34 Alkuper‰maan koodi
		//Kentt‰ 35 Bruttopaino
		$tulosulos .= sprintf('%-8.8s', "");
		$tulosulos .= sprintf('%-46.46s', substr($nimitykset[2],0,35));
		$tulosulos .= sprintf('%-2.2s', $rivirow["alkuperamaa"]);
		$tulosulos .= sprintf('%-9.9s', $kilot)."\r\n";

		$tulosulos .= sprintf('%-8.8s', "");
		$tulosulos .= sprintf('%-40.40s', substr($nimitykset[3],0,35))."\r\n";

		//Kentt‰ 37 Kohtelukoodi
		//Kentt‰ 38 Nettopaino
		$tulosulos .= sprintf('%-8.8s', "");
		$tulosulos .= sprintf('%-45.45s', substr($nimitykset[4],0,35));
		$tulosulos .= sprintf('%-6.6s', $rivirow["tullikohtelu"]);
		$tulosulos .= sprintf('%-5.5s', "");
		$tulosulos .= sprintf('%-2.2s', $rivirow["nettop"])."\r\n";
		
		$tulosulos .= sprintf('%-8.8s', "");
		$tulosulos .= sprintf('%-40.40s', substr($nimitykset[5],0,35))."\r\n";
		
		$tulosulos .= sprintf('%-8.8s', "");
		$tulosulos .= sprintf('%-40.40s', substr($nimitykset[5],0,35))."\r\n";
		
		$tulosulos .= "\r\n";
		
		//Kentt‰ 41 Muu paljous
		if ($rivirow["su"] != '') {
			$tulosulos .= sprintf('%-54.54s', "");
			$tulosulos .= sprintf('%-10.10s', $rivirow["kpl"]." ".$rivirow["su_vientiilmo"])."\r\n";
		}
		else {
			$tulosulos .= "\r\n";
		}

		//Kentt‰ 44 Lis‰tietoja
		$tulosulos .= sprintf('%-8.8s', "");
		$tulosulos .= sprintf('%-46.46s', "Kauppalasku: $laskurow[laskunro] - ".tv1dateconv($laskurow["tapvm"]))."\r\n";

		$tulosulos .= sprintf('%-8.8s', "");
		$tulosulos .= sprintf('%-46.46s', "RET.EXP.")."\r\n";

		if ($yhtiorow["kotitullauslupa"] != "") {
			$tulosulos .= sprintf('%-8.8s', "");
			$tulosulos .= sprintf('%-46.46s', "Kotitullauslupa ".$yhtiorow["kotitullauslupa"].", Etel‰inen tullipiiri")."\r\n";
			
			$tulosulos .= sprintf('%-8.8s', "");
			$tulosulos .= sprintf('%-46.46s', "Yksinkertaistettu vienti");
			$tulosulos .= sprintf('%-6.6s', "");
		}
		else {
			$tulosulos .= "\r\n";
			$tulosulos .= sprintf('%-60.60s', "");
		}

		//Kentt‰ 45
		//Kentt‰ 46 Vientiarvo
		
		$tulosulos .= sprintf('%-9.9s', $laskurow["valkoodi"]);
		$tulosulos .= sprintf('%-11.11s', sprintf("%01.2f", $tullarvo))."\r\n";
		
		$tulosulos .= "\r\n";
		
		return $tulosulos;
	}
	
	
	function sadteksti_lisasivu ($tulosulos, $sivu, $sivut) {
		global $yhtiorow, $kukarow;
		
		$tulosulos .= "\r\n";
		$tulosulos .= "\r\n";
		$tulosulos .= "\r\n";
			
		//Kentt‰ 1 Imoitustyyppi, Ilmoitus
		//Kentt‰ 2 Viej‰
		//Kentt‰ 3 Lomakenro
		$tulosulos .= "\r\n";
		
		$tulosulos .= sprintf('%-8.8s', "");
		$tulosulos .= sprintf('%-21.21s', $yhtiorow["nimi"]);
		
		$tulosulos .= sprintf('%-15.15s', $yhtiorow["maakoodi"].print_ytunnus($yhtiorow["ytunnus"]));
		
		$tulosulos .= sprintf('%-4.4s', "EX");
		$tulosulos .= sprintf('%-1.1s', "A")."\r\n";

		$tulosulos .= sprintf('%-8.8s', "");
		$tulosulos .= sprintf('%-36.36s', $yhtiorow["osoite"])."\r\n";
		
		$tulosulos .= sprintf('%-8.8s', "");		
		$tulosulos .= sprintf('%-36.36s', $yhtiorow["postino"]." ".$yhtiorow["postino"]);
		$tulosulos .= sprintf('%-3.3s', $sivu);
		$tulosulos .= sprintf('%-3.3s', $sivut)."\r\n";
		$tulosulos .= "\r\n";
		
		return $tulosulos;
	}
	
	function sadteksti_loppu ($tulosulos, $sivu, $lask) {
		global $yhtiorow, $kukarow;
		
		if ($sivu == 1) {
			//Kentt‰ 48 Maksun lykk‰ys
			$tulosulos .= sprintf('%-48.48s', "");
			$tulosulos .= sprintf('%-12.12s', $yhtiorow["tullin_asiaknro"])."\r\n";

			for($x = 0; $x<19; $x++) {
				$tulosulos .= "\r\n";
			}
		
			$pp = date('d');
			$kk = date('m');
			$vv = date('Y');
			$pvm = $pp.".".$kk.".".$vv;

			//Kentt‰ 54 Tullausilmoituksen allekirjoitus
			$tulosulos .= sprintf('%-57.57s', "");
			$tulosulos .= sprintf('%-20.20s', $yhtiorow["kotipaikka"]." ".$pvm)."\r\n";
		
			$tulosulos .= "\r\n";
			$tulosulos .= sprintf('%-57.57s', "");
			$tulosulos .= sprintf('%-20.20s', $yhtiorow["nimi"])."\r\n";

			$tulosulos .= sprintf('%-57.57s', "");
			$tulosulos .= sprintf('%-20.20s', $kukarow["nimi"])."\r\n";

			$tulosulos .= sprintf('%-57.57s', "");
			$tulosulos .= sprintf('%-20.20s', $yhtiorow["puhelin"])."\r\n";

			//Kentt‰ B Merkinn‰t kirjanpitoa varten
			if ($yhtiorow["kotitullauslupa"] != "") {
				$tulosulos .= sprintf('%-20.20s', "ATK TULLAUS");
				$tulosulos .= sprintf('%-20.20s', $laskurow["tullausnumero"]);
			}

			$tulosulos .= "\r\n";
			$tulosulos .= "\r\n";
			$tulosulos .= "\r\n";
			$tulosulos .= "\r\n";
		}
		else {
			
			if($lask == 1) $y = 45;
			if($lask == 2) $y = 31;
			if($lask == 3) $y = 17;
			
			for($x = 0; $x<$y; $x++) {
				$tulosulos .= "\r\n";
			}
			
			$tulosulos .= sprintf('%-61.61s', "");
			$tulosulos .= sprintf('%-18.18s', $yhtiorow["nimi"])."\r\n";

			$tulosulos .= sprintf('%-61.61s', "");
			$tulosulos .= sprintf('%-18.18s', $kukarow["nimi"])."\r\n";

			$tulosulos .= sprintf('%-61.61s', "");
			$tulosulos .= sprintf('%-18.18s', $yhtiorow["puhelin"])."\r\n";
			$tulosulos .= "\r\n";
			$tulosulos .= "\r\n";
		}

		return $tulosulos;
	}
	
	//hetaan kaikki otunnukset jotka lˆytyv‰t t‰n uusiotunnuksen alta
	$query = "	SELECT group_concat(distinct otunnus) tilaukset
				FROM tilausrivi
				WHERE uusiotunnus = '$laskurow[tunnus]'
				and yhtio = '$kukarow[yhtio]'";
	$uresult = mysql_query($query) or pupe_error($query);

	// varmistetaan, ett‰ lˆydettiin otsikoita
	if (mysql_num_rows($uresult) > 0) {
	
		$urow = mysql_fetch_array($uresult);
			
		//haetaan kollim‰‰r‰ ja bruttopaino
		$query = "	SELECT sum(kilot) kilot, sum(kollit) kollit
					FROM rahtikirjat
					WHERE otsikkonro in ($urow[tilaukset])
					and yhtio='$kukarow[yhtio]'";
		$rahtiresult = mysql_query($query) or pupe_error($query);
		$rahtirow = mysql_fetch_array($rahtiresult);
	
		//Rahtikirjan mukaiset kilot ja kollim‰‰r‰t
		if ($rahtirow["kilot"] > 0) {
			$kilot  = $rahtirow["kilot"];
		}
		else {
			$kilot  = 0;
		}
		
		if ($rahtirow["kollit"] > 0) {
			$kollit = $rahtirow["kollit"];
		}
		else {
			$kollit = 0;
		}
		
		//ylim‰‰r‰isten erien vaikutus
		$extrat = abs($laskurow["lisattava_era"])-abs($laskurow["vahennettava_era"]);
	
		$query = "	SELECT
					tuote.tullinimike1,
					tuote.tullinimike2,
					tuote.tullikohtelu,
					(SELECT alkuperamaa FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.alkuperamaa!='' LIMIT 1) alkuperamaa,
					tullinimike.su,
					tullinimike.su_vientiilmo,
					tullinimike.dm,
					min(tilausrivi.nimitys) nimitys,
					GROUP_CONCAT(distinct trim(tuote.nimitys) ORDER BY tuote.nimitys SEPARATOR '##') nimitys_hieno,
					sum(tilausrivi.rivihinta) rivihinta,
					sum(tilausrivi.kpl) kpl,
					round(sum((tilausrivi.rivihinta/$laskurow[summa])*$kilot),0) nettop
					FROM tilausrivi
					JOIN tuote ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.ei_saldoa = ''
					JOIN tullinimike ON tuote.tullinimike1 = tullinimike.cn and tullinimike.kieli = '$yhtiorow[kieli]' and tuote.tullinimike1!=''
					WHERE tilausrivi.uusiotunnus	= '$uusiotunnus'
					and tilausrivi.yhtio			= '$kukarow[yhtio]'
					and tilausrivi.kpl				> 0
					GROUP BY tuote.tullinimike1, tuote.tullinimike2, tuote.tullikohtelu, alkuperamaa, tullinimike.su, tullinimike.su_vientiilmo, tullinimike.dm";
		$riviresult = mysql_query($query) or pupe_error($query);
				
		if (mysql_num_rows($riviresult) > 0) {
			
			$lask  = 2;
			$sivu  = 1;
			$tjarj = 1;
			
			$paalomake  = "";
			$lisalomake = "";
			$sivut = 1;
			
			$sivut += ceil((mysql_num_rows($riviresult)-1)/3);
			
			$tavaraerittelyt = mysql_num_rows($riviresult);
			
			$paalomake = sadteksti_alku ($paalomake, $laskurow, $sivut, $tavaraerittelyt, $kollit);
			
			while ($rivirow = mysql_fetch_array($riviresult)) {
				if ($lask == 3) {
					
					if ($sivu == 1) {
						$paalomake  = sadteksti_loppu ($paalomake, $sivu, $lask);
					}
					else {
						$lisalomake = sadteksti_loppu ($lisalomake, $sivu, $lask);	
					}
					
					$lask = 0;
					$sivu++;
					
					$lisalomake = sadteksti_lisasivu ($lisalomake, $sivu, $sivut);
				}
				
				if ($sivu == 1) {
					$paalomake  = sadteksti_rivi ($paalomake, $laskurow, $rivirow, $tjarj, $extrat, $lask);
				}
				else {
					$lisalomake = sadteksti_rivi ($lisalomake, $laskurow, $rivirow, $tjarj, $extrat, $lask);	
				}
				
				$lask++;
				$tjarj++;
			}
			
			if ($sivu == 1) {
				$paalomake  = sadteksti_loppu ($paalomake, $sivu, $lask);
			}
			else {
				$lisalomake = sadteksti_loppu ($lisalomake, $sivu, $lask);
			}
		}
	}
?>
