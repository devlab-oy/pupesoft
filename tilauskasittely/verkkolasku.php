<?php

	// verkkolasku.php
	//
	// tarvitaan $kukarow ja $yhtiorow
	//
	// laskutetaan kaikki/valitut toimitetut tilaukset
	// l‰hetet‰‰n kaikki laskutetut tilaukset operaattorille tai tulostetaan ne paperille
	//
	// $laskutettavat 	--> jos halutaan laskuttaa vain tietyt tilaukset niin silloin ne tulee muuttujassa
	// $laskutakaikki 	--> muutujan avulla voidaan ohittaa laskutusviikonp‰iv‰t
	// $eiketjut 		--> muuttujan avulla katotaan halutaanko laskuttaa vaan laskut joita *EI SAA* ketjuttaa
	//
	// jos ollaan saatu komentorivilt‰ parametrej‰
	// $yhtio ja $kieli	--> komentorivilt‰ pit‰‰ tulla parametrein‰
	// $eilinen			--> optional parametri on jollon ajetaan laskutus eiliselle p‰iv‰lle
	//
	// $silent muuttujalla voidaan hiljent‰‰ kaikki outputti

	//jos chn = 999 se tarkoittaa ett‰ lasku on laskutuskiellossa eli ei saa k‰sitell‰ t‰‰ll‰!!!!!

	//$silent = '';

	if (isset($argv[1]) and trim($argv[1]) != '') {

		if ($argc == 0) die ("T‰t‰ scripti‰ voi ajaa vain komentorivilt‰!");

		// otetaan tietokanta connect
		require ("../inc/connect.inc");
		require ("../inc/functions.inc");

		// hmm.. j‰nn‰‰
		$kukarow['yhtio'] = $argv[1];
		$kieli = $argv[2];
		$kukarow['kuka']  = "crond";

		$query    = "select * from yhtio where yhtio='$kukarow[yhtio]'";
		$yhtiores = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($yhtiores) == 1) {
			$yhtiorow = mysql_fetch_array($yhtiores);

			// haetaan yhtiˆn parametrit
			$query = "	SELECT *
						FROM yhtion_parametrit
						WHERE yhtio='$yhtiorow[yhtio]'";
			$result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

			if (mysql_num_rows($result) == 1) {
				$yhtion_parametritrow = mysql_fetch_array($result);
				// lis‰t‰‰n kaikki yhtiorow arrayseen, niin ollaan taaksep‰inyhteensopivia
				foreach ($yhtion_parametritrow as $parametrit_nimi => $parametrit_arvo) {
					$yhtiorow[$parametrit_nimi] = $parametrit_arvo;
				}
			}

			$laskkk = "";
			$laskpp = "";
			$laskvv = "";
			$eiketjut = "";

			// jos komentorivin kolmas arg on "eilinen" niin edelliselle laskutus p‰iv‰lle
			if ($argv[3] == "eilinen") {
				$laskkk = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
				$laskpp = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
				$laskvv	= date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
			}

			// jos komentorivin kolmas arg on "eiketjut"
			if ($argv[3] == "eiketjut") {
				$eiketjut = "KYLLA";
			}

			$komentorivilta = "ON";

			$tee = "TARKISTA";
		}
		else {
			die ("Yhtiˆ $kukarow[yhtio] ei lˆydy!");
		}
	}
	elseif (strpos($_SERVER['SCRIPT_NAME'], "verkkolasku.php") !== FALSE) {
		require ("../inc/parametrit.inc");
	}

	//HUOMHUOM!!
	$query = "SET SESSION group_concat_max_len = 100000, max_allowed_packet = 2095104";
	$result = mysql_query($query) or pupe_error($query);

	//Nollataan muuttujat
	$tulostettavat = array();
	$tulos_ulos = "";

	if (!isset($silent)) {
		$silent = "";
	}
	if (!isset($tee)) {
		$tee = "";
	}
	if (!isset($kieli)) {
		$kieli = "";
	}
	if (!isset($komentorivilta)) {
		$komentorivilta = "";
	}

	if ($silent == "") {
		$tulos_ulos .= "<font class='head'>".t("Laskutusajo")."</font><hr>\n";
	}

	if ($tee == 'TARKISTA') {

		$poikkeava_pvm = "";

		//syˆtetty p‰iv‰m‰‰r‰
		if ($laskkk != '' or $laskpp != '' or $laskvv != '') {

			//katotaan ensin, ett‰ se on ollenkaan validi
			if (checkdate($laskkk, $laskpp, $laskvv)) {

				//vertaillaan tilikauteen
				list($vv1,$kk1,$pp1) = split("-",$yhtiorow["tilikausi_alku"]);
				list($vv2,$kk2,$pp2) = split("-",$yhtiorow["tilikausi_loppu"]);

				$tilialku  = (int) date('Ymd',mktime(0,0,0,$kk1,$pp1,$vv1));
				$tililoppu = (int) date('Ymd',mktime(0,0,0,$kk2,$pp2,$vv2));
				$syotetty  = (int) date('Ymd',mktime(0,0,0,$laskkk,$laskpp,$laskvv));
				$tanaan    = (int) date('Ymd');

				if ($syotetty < $tilialku or $syotetty > $tililoppu) {
					$tulos_ulos .= "<br>\n".t("VIRHE: Syˆtetty p‰iv‰m‰‰r‰ ei sis‰lly kuluvaan tilikauteen!")."<br>\n<br>\n";
					$tee = "";
				}
				else {

					if ($syotetty > $tanaan) {
						//tulevaisuudessa ei voida laskuttaa
						$tulos_ulos .= "<br>\n".t("VIRHE: Syˆtetty p‰iv‰m‰‰r‰ on tulevaisuudessa, ei voida laskuttaa!")."<br>\n<br>\n";
						$tee = "";
					}
					else {
						//homma on ok
						$poikkeava_pvm = $syotetty;

						//ohitetaan myˆs laskutusviikonp‰iv‰t jos poikkeava p‰iv‰m‰‰r‰ on syˆtetty
						$laskutakaikki = "ON";

						$tee = "LASKUTA";
					}
				}
			}
			else {
				$tulos_ulos .= "<br>\n".t("VIRHE: Syˆtetty p‰iv‰m‰‰r‰ on virheellinen, tarkista se!")."<br>\n<br>\n";
				$tee = "";
			}
		}
		else {
			//poikkeavaa p‰iv‰m‰‰r‰‰ ei ole, eli laskutetaan
			$tee = "LASKUTA";
		}
	}

	if ($tee == "LASKUTA") {

		if (!function_exists("xml_add")) {
			function xml_add ($joukko, $tieto, $handle) {
				global $yhtiorow, $lasrow;
				
				$ulos = "<$joukko>";

				if (strlen($tieto) > 0) {
					//K‰sitell‰‰n xml-entiteetit
					$serc = array("&", ">", "<", "'", "\"", "¥", "`");
					$repl = array("&amp;", "&gt;", "&lt;", "&apos;", "&quot;", " ", " ");
					$tieto = str_replace($serc, $repl, $tieto);

					$ulos .= $tieto;

				}

				$pos = strpos($joukko, " ");
	            if ($pos === false) {
					//	Jos tehd‰‰n finvoicea rivilopu on \r\n
					if($yhtiorow["verkkolasku_lah"] != "" and $lasrow["chn"] != "111") {
						$ulos .= "</$joukko>\r\n";
					}
					else {
						$ulos .= "</$joukko>\n";
					}
	                
	            }
	            else {
					if($yhtiorow["verkkolasku_lah"] != "" and $lasrow["chn"] != "111") {
						$ulos .= "</".substr($joukko,0,$pos).">\r\n";
					}
					else {
						$ulos .= "</".substr($joukko,0,$pos).">\n";
					}
	            }

				fputs($handle, $ulos);
			}
		}

		if (!function_exists("vlas_dateconv")) {
			function vlas_dateconv ($date) {
				//k‰‰nt‰‰ mysqln vvvv-kk-mm muodon muotoon vvvvkkmm
				return substr($date,0,4).substr($date,5,2).substr($date,8,2);
			}
		}

		//tehd‰‰n viitteest‰ SPY standardia eli 20 merkki‰ etunollilla
		if (!function_exists("spyconv")) {
			function spyconv ($spy) {
				return $spy = sprintf("%020.020s",$spy);
			}
		}

		//pilkut pisteiksi
		if (!function_exists("pp")) {
			function pp ($muuttuja) {
				return $muuttuja = str_replace(".",",",$muuttuja);
			}
		}

		if (!function_exists("ymuuta")) {
			function ymuuta ($ytunnus) {
				$ytunnus = sprintf("%08.8s",$ytunnus);
				return substr($ytunnus,0,7)."-".substr($ytunnus,-1);
			}
		}

		$today = date("w") + 1; // mik‰ viikonp‰iv‰ t‰n‰‰n on 1-7.. 1=sunnuntai, 2=maanantai, jne...

		//Tiedostojen polut ja nimet
		//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
		$nimixml = "../dataout/laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true)).".xml";
		$nimi_filexml = "laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true)).".xml";

		$nimifinvoice = "../dataout/laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true))."_finvoice.xml";
		$nimi_filefinvoice = "laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true))."_finvoice.xml";

		$nimiedi = "../dataout/laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true)).".edi";
		$nimi_fileedi = "laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true)).".edi";

		//Pupevoice xml-dataa
		if (!$tootxml = fopen($nimixml, "w")) die("Filen $nimixml luonti ep‰onnistui!");

		//Finvoice xml-dataa
		if (!$tootfinvoice = fopen($nimifinvoice, "w")) die("Filen $nimifinvoice luonti ep‰onnistui!");

		//Elma-EDI-inhouse dataa (EIH-1.4.0)
		if (!$tootedi = fopen($nimiedi, "w")) die("Filen $nimiedi luonti ep‰onnistui!");


		// lock tables
		$query = "LOCK TABLES lasku WRITE, tilausrivi WRITE, tilausrivi as t2 WRITE, sanakirja WRITE, tapahtuma WRITE, tuotepaikat WRITE, tiliointi WRITE, toimitustapa READ, maksuehto READ, sarjanumeroseuranta READ, tullinimike READ, kuka READ, varastopaikat READ, tuote READ, rahtikirjat READ, kirjoittimet READ, tuotteen_avainsanat READ, tuotteen_toimittajat READ, asiakas READ, rahtimaksut READ, avainsana READ, factoring READ";
		$locre = mysql_query($query) or pupe_error($query);

		//Haetaan tarvittavat funktiot aineistojen tekoa varten
		require("verkkolasku_elmaedi.inc");
		require("verkkolasku_finvoice.inc");
		require("verkkolasku_pupevoice.inc");

		// haetaan kaikki tilaukset jotka on toimitettu ja kuuluu laskuttaa t‰n‰‰n (t‰t‰ resulttia k‰ytet‰‰n alhaalla lis‰‰)
		$lasklisa = "";

		// tarkistetaan t‰ss‰ tuleeko laskutusviikonp‰iv‰t ohittaa
		// ohitetaan jos ruksi on ruksattu tai poikkeava laskutusp‰iv‰m‰‰r‰ on syˆtetty
		if (!isset($laskutakaikki) or $laskutakaikki == "") {
			$lasklisa .= " and (laskutusvkopv='0' or laskutusvkopv='$today')";
		}

		// katotaan halutaanko laskuttaa vaan laskut joita *EI SAA* ketjuttaa
		if (isset($eiketjut) and $eiketjut == "KYLLA") {
			$lasklisa .= " and ketjutus != '' ";
		}

		// laskutetaan vain tietyt tilausket
		if (isset($laskutettavat) and $laskutettavat != "") {
			$lasklisa .= " and tunnus in ($laskutettavat) ";
		}

		$tulos_ulos_maksusoppari = "";

		//haetaan kaikki laskutettavat tilaukset ja tehd‰‰n maksuehtosplittaukset jos niit‰ on
		$query = "	select *
					from lasku
					where yhtio	= '$kukarow[yhtio]'
					and tila	= 'L'
					and alatila	= 'D'
					and viite	= ''
					and chn	!= '999'
					$lasklisa";
		$res   = mysql_query($query) or pupe_error($query);

		//ja katotaan vaatiiko joku maksuehto uusia tilauksia
		while ($laskurow = mysql_fetch_array($res)) {
			$query = " 	select *
						from maksuehto
						where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
			$maresult = mysql_query($query) or pupe_error($query);
			$maksuehtorow = mysql_fetch_array($maresult);

			if ($maksuehtorow['jaksotettu'] != '') {
				$query = "	UPDATE lasku SET alatila='J'
							WHERE tunnus = '$laskurow[tunnus]'";
				$updres = mysql_query($query) or pupe_error($query);

				if ($silent == "") {
					$tulos_ulos_maksusoppari .= t("Maksusopimustilaus siirretty odottamaan loppulaskutusta").": $laskurow[tunnus] $laskurow[nimi]<br>\n<table>";
				}
			}
			else {
				require("maksuehtosplittaus.inc");
			}
		}

		if (isset($tulos_ulos_maksusoppari) and $tulos_ulos_maksusoppari != '' and $silent == "") {
			$tulos_ulos .= "<br>\n".t("Maksusopimustilausket").":<br>\n";
			$tulos_ulos .= $tulos_ulos_maksusoppari;
		}

		if (isset($tulos_ulos_ehtosplit) and $tulos_ulos_ehtosplit != '' and $silent == "") {
			$tulos_ulos .= "<br>\n".t("Tilauksia joilla on moniehto-maksuehto").":<br>\n";
			$tulos_ulos .= $tulos_ulos_ehtosplit;
		}

		//haetaan kaikki laskutettavat tilaukset uudestaan, nyt meill‰ on maksuehtosplittaukset tehty
		$query = "	select *
					from lasku
					where yhtio	= '$kukarow[yhtio]'
					and tila	= 'L'
					and alatila	= 'D'
					and viite	= ''
					and chn	!= '999'
					$lasklisa";
		$res   = mysql_query($query) or pupe_error($query);

		$tunnukset = "'',";

		// otetaan tunnukset talteen
		while ($row = mysql_fetch_array($res)) {
			$tunnukset .= "'$row[tunnus]',";
		}

		//vika pilkku pois
		$tunnukset = substr($tunnukset,0,-1);

		//rahtien ja j‰lkivaatimuskulujen muuttujia
		$rah 	 = 0;
		$jvhinta = 0;
		$rahinta = 0;

		// haetaan laskutettavista tilauksista kaikki distinct toimitustavat per asiakas per p‰iv‰
		// j‰lkivaatimukset omalle riville ja tutkitaan tarvimmeko lis‰ill‰ JV-kuluja
		if ($silent == "") {
			$tulos_ulos .= "<br>\n".t("J‰lkivaatimuskulut").":<br>\n";
		}

		$query   = "select group_concat(distinct lasku.tunnus) tunnukset
					from lasku, rahtikirjat, maksuehto
					where lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tunnus in ($tunnukset)
					and lasku.yhtio = rahtikirjat.yhtio
					and lasku.tunnus = rahtikirjat.otsikkonro
					and lasku.yhtio = maksuehto.yhtio
					and lasku.maksuehto	= maksuehto.tunnus
					and maksuehto.jv != ''
					GROUP BY date_format(rahtikirjat.tulostettu, '%Y-%m-%d'), lasku.ytunnus, lasku.toimitustapa";
		$result  = mysql_query($query) or pupe_error($query);

		$yhdista = array();

		while ($row = mysql_fetch_array($result)) {
			$yhdista[] = $row["tunnukset"];
		}

		if (count($yhdista) == 0) {
			$tulos_ulos .= t("Ei j‰lkivaatimuksia")."!<br>\n";
		}

		$tulos_ulos .= "<table>";

		foreach ($yhdista as $otsikot) {

			// lis‰t‰‰n n‰ille tilauksille jvkulut
			$virhe=0;

			//haetaan ekan otsikon tiedot
			$query = "	select lasku.*, maksuehto.jv
						from lasku, maksuehto
						where lasku.yhtio='$kukarow[yhtio]'
						and lasku.tunnus in ($otsikot)
						and lasku.yhtio = maksuehto.yhtio
						and lasku.maksuehto	= maksuehto.tunnus
						order by lasku.tunnus
						limit 1";
			$otsre = mysql_query($query) or pupe_error($query);
			$laskurow = mysql_fetch_array($otsre);

			if (mysql_num_rows($otsre)!=1) $virhe++;

			if (mysql_num_rows($otsre)==1 and $virhe==0) {

				// kirjoitetaan jv kulurivi ekalle otsikolle
				$query = "	select jvkulu
							from toimitustapa
							where yhtio='$kukarow[yhtio]'
							and selite='$laskurow[toimitustapa]'";
				$tjvres = mysql_query($query) or pupe_error($query);
				$tjvrow = mysql_fetch_array($tjvres);

				$query = "	select *
							from tuote
							where yhtio = '$kukarow[yhtio]'
							and tuoteno = '$yhtiorow[rahti_tuotenumero]'";
				$rhire = mysql_query($query) or pupe_error($query);
				$trow  = mysql_fetch_array($rhire);


				$hinta		        = $tjvrow['jvkulu']; // jv kulu
				$alv               	= '';
				$nimitys            = "J‰lkivaatimuskulu";
				$kommentti          = "";

				require("tilauskasittely/alv.inc");

				// jv kulu tarvitaan tulostuksessa
				$jvhinta            = $hinta;

				$query  = "insert into tilausrivi (hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti) values  ('$hinta', 'N', '1', '1', '$laskurow[tunnus]', '$trow[tuoteno]', '$nimitys', '$kukarow[yhtio]', 'L', '$alv', '$kommentti')";
				$addtil = mysql_query($query) or pupe_error($query);

				if ($silent == "") {
					$tulos_ulos .= "<tr><td>".t("Lis‰ttiin jv-kulut")."</td><td>$laskurow[tunnus]</td><td>$laskurow[toimitustapa]</td><td>$jvhinta</td><td>$yhtiorow[valkoodi]</td></tr>\n";
				}
			}
			elseif (mysql_num_rows($otsre) != 1 and $silent == "") {
				$tulos_ulos .= "<tr><td>".t("J‰lkivaatimuskulua ei lˆydy!")."</td><td>$laskurow[tunnus]</td><td>$laskurow[toimitustapa]</td></tr>\n";
			}
			elseif ($silent == "") {
				$tulos_ulos .= "<tr><td>".t("J‰lkivaatimuskulua ei osattu lis‰t‰!")." $virhe</td><td>$otsikot</td><td>$laskurow[toimitustapa]</td></tr>\n";
			}
		}


		// Lasketaan rahtikulut vain jos ne eiv‰t ole laskettu jo tilausvaiheessa.
		if ($yhtiorow["rahti_hinnoittelu"] == "") {

			if ($silent == "") {
				$tulos_ulos .= "<br>\n".t("Rahtikulut").":<br>\n<table>";
			}

			// haetaan laskutettavista tilauksista kaikki distinct toimitustavat per asiakas per p‰iv‰ miss‰ merahti (eli kohdistettu) = K
			// j‰lkivaatimukset omalle riville
			$query   = "select group_concat(distinct lasku.tunnus) tunnukset
						from lasku, rahtikirjat, maksuehto
						where lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tunnus in ($tunnukset)
						and lasku.kohdistettu = 'K'
						and lasku.yhtio = rahtikirjat.yhtio
						and lasku.tunnus = rahtikirjat.otsikkonro
						and lasku.yhtio = maksuehto.yhtio
						and lasku.maksuehto	= maksuehto.tunnus
						GROUP BY date_format(rahtikirjat.tulostettu, '%Y-%m-%d'), lasku.ytunnus, lasku.toimitustapa, maksuehto.jv";
			$result  = mysql_query($query) or pupe_error($query);
			$yhdista = array();

			while ($row = mysql_fetch_array($result)) {
				$yhdista[] = $row["tunnukset"];
			}

			foreach ($yhdista as $otsikot) {

				// lis‰t‰‰n n‰ille tilauksille rahtikulut
				$virhe=0;

				//haetaan ekan otsikon tiedot
				$query = "	select lasku.*, maksuehto.jv
							from lasku, maksuehto
							where lasku.yhtio='$kukarow[yhtio]'
							and lasku.tunnus in ($otsikot)
							and lasku.yhtio = maksuehto.yhtio
							and lasku.maksuehto	= maksuehto.tunnus
							order by lasku.tunnus
							limit 1";
				$otsre = mysql_query($query) or pupe_error($query);
				$laskurow = mysql_fetch_array($otsre);

				if (mysql_num_rows($otsre)!=1) $virhe++;

				//summataan kaikki painot yhteen
				$query = "SELECT sum(kilot) kilot FROM rahtikirjat WHERE yhtio='$kukarow[yhtio]' and otsikkonro in ($otsikot)";
				$pakre = mysql_query($query) or pupe_error($query);
				$pakka = mysql_fetch_array($pakre);
				if (mysql_num_rows($pakre)!=1) $virhe++;

				//haetaan v‰h‰n infoa rahtikirjoista
				$query = "select distinct date_format(tulostettu, '%d.%m.%Y') pvm, rahtikirjanro from rahtikirjat where yhtio='$kukarow[yhtio]' and otsikkonro in ($otsikot)";
				$rahre = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($rahre)==0) $virhe++;

				$rahtikirjanrot = "";
				while ($rahrow = mysql_fetch_array($rahre)) {
					if ($rahrow["pvm"]!='') $pvm = $rahrow["pvm"]; // pit‰s olla kyll‰ aina sama
					$rahtikirjanrot .= "$rahrow[rahtikirjanro] ";
				}
				//vika pilkku pois
				$rahtikirjanrot = substr($rahtikirjanrot,0,-1);

				//haetaan t‰ll‰ rahtikirjalle rahtimaksu
				$query = "	select *
							from rahtimaksut
							where toimitustapa = '$laskurow[toimitustapa]'
							and kilotalku <= '$pakka[kilot]'
							and kilotloppu >= '$pakka[kilot]'
							and yhtio = '$kukarow[yhtio]'";
				$rares = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($rares)==1 and $virhe==0) {
					$rahti = mysql_fetch_array($rares);

					// kirjoitetaan hintarivi ekalle otsikolle
					if (trim($yhtiorow["rahti_tuotenumero"]) == "") {
						$tuotska = t("Rahtikulu");
					}
					else {
						$tuotska = $yhtiorow["rahti_tuotenumero"];
					}

					$query = "	select *
								from tuote
								where yhtio='$kukarow[yhtio]'
								and tuoteno='$tuotska'";
					$rhire = mysql_query($query) or pupe_error($query);
					$trow  = mysql_fetch_array($rhire);

					$otunnus	= $laskurow['tunnus'];
					$hinta		= $rahti["rahtihinta"]; // rahtihinta
					$alv 		= '';
					$nimitys	= "$pvm $laskurow[toimitustapa]";
					$kommentti  = "Rahtikirja: $rahtikirjanrot";

					require("alv.inc");

					//rahdin hinta tarvitaan tulostuksessa
					$rahinta = $hinta;

					$query  = "insert into tilausrivi (hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti) values  ('$hinta', 'N', '1', '1', '$otunnus', '$trow[tuoteno]', '$nimitys', '$kukarow[yhtio]', 'L', '$alv', '$kommentti')";
					$addtil = mysql_query($query) or pupe_error($query);

					if ($silent == "") {
						$tulos_ulos .= "<tr><td>".t("Lis‰ttiin rahtikulut")."</td><td>$laskurow[tunnus]</td><td>$laskurow[toimitustapa]</td><td>$rahti[rahtihinta]</td><td>$yhtiorow[valkoodi]</td><td>$pakka[kilot] kg</td></tr>\n";
					}

					$rah++;
				}
				elseif (mysql_num_rows($rares) != 1 and $silent == "") {
					$tulos_ulos .= "<tr><td>".t("Rahtimaksua ei lˆydy!")."</td><td>$laskurow[tunnus]</td><td>$laskurow[toimitustapa]</td><td></td><td></td><td>$pakka[kilot] kg</td></tr>\n";
				}
				elseif ($silent == "") {
					$tulos_ulos .= "<tr><td>".t("Rahtimaksua ei osattu lis‰t‰!")." $virhe</td><td>$otsikot</td><td>$laskurow[toimitustapa]</td><td></td><td></td><td>$pakka[kilot] kg</td></tr>\n";
				}
			}

			if ($silent == "") {
				$tulos_ulos .= "</table>\n".sprintf(t("Lis‰ttiin rahtikulu %s kpl rahtikirjaan"),$rah).".";
			}
		}
		elseif ($silent == "") {
			$tulos_ulos .= "<br>\n".t("Laskujen rahtikulut muodostuivat jo tilausvaiheessa").".<br>\n";
		}

		// laskutetaan kaikki tilaukset (siis teh‰‰n kaikki tarvittava matikka)
		// rullataan eka query alkuun
		if (mysql_num_rows($res) != 0) {
			mysql_data_seek($res,0);
		}

		$laskutetttu = 0;

		if ($silent == "") {
			$tulos_ulos .= "<br><br>\n".t("Tilausten laskutus:")."<br>\n";
		}

		while ($row=mysql_fetch_array($res)) {
			// laskutus tarttee kukarow[kesken]
			$kukarow['kesken']=$row['tunnus'];

			require ("laskutus.inc");
			$laskutetttu++;

			//otetaan laskutuksen viestit talteen
			if ($silent == "") {
				$tulos_ulos .= $tulos_ulos_laskutus;
			}
		}

		if ($silent == "") {
			$tulos_ulos .= t("Laskutettiin")." $laskutetttu ".t("tilausta").".";
		}

		//ketjutetaan laskut...
		$ketjut = array();

		//haetaan kaikki laskutusvalmiit tilaukset jotka saa ketjuttaa, viite pit‰‰ olla tyhj‰‰ muuten ei laskuteta
		$query  = "	SELECT ytunnus, nimi, nimitark, osoite, postino, postitp, maksuehto, erpcm, vienti,
					lisattava_era, vahennettava_era, maa_maara, kuljetusmuoto, kauppatapahtuman_luonne,
					sisamaan_kuljetus, aktiivinen_kuljetus, kontti, aktiivinen_kuljetus_kansallisuus,
					sisamaan_kuljetusmuoto, poistumistoimipaikka, poistumistoimipaikka_koodi, chn,
					count(*) yht, group_concat(tunnus) tunnukset
					FROM lasku
					WHERE yhtio		= '$kukarow[yhtio]'
					and alatila		= 'V'
					and tila		= 'L'
					and ketjutus	= ''
					and viite		= ''
					$lasklisa
					GROUP BY ytunnus, nimi, nimitark, osoite, postino, postitp, maksuehto, erpcm, vienti,
					lisattava_era, vahennettava_era, maa_maara, kuljetusmuoto, kauppatapahtuman_luonne,
					sisamaan_kuljetus, aktiivinen_kuljetus, kontti, aktiivinen_kuljetus_kansallisuus,
					sisamaan_kuljetusmuoto, poistumistoimipaikka, poistumistoimipaikka_koodi, chn
					ORDER BY ytunnus, nimi, nimitark, osoite, postino, postitp, maksuehto, erpcm, vienti,
					lisattava_era, vahennettava_era, maa_maara, kuljetusmuoto, kauppatapahtuman_luonne,
					sisamaan_kuljetus, aktiivinen_kuljetus, kontti, aktiivinen_kuljetus_kansallisuus,
					sisamaan_kuljetusmuoto, poistumistoimipaikka, poistumistoimipaikka_koodi, chn";
		$result = mysql_query($query) or pupe_error($query);

		if ($silent == "") {
			$tulos_ulos .= "<br><br>\n".t("Laskujen ketjutus:")."<br>\n<table>";
		}

		while ($row = mysql_fetch_array($result)) {

			if ($silent == "") {
				$tulos_ulos .= "<tr><td>$row[ytunnus]</td><td>$row[nimi]<br>$row[nimitark]</td><td>$row[osoite]</td><td>$row[postino]</td>\n
								<td>$row[postitp]</td><td>$row[maksuehto]</td><td>$row[erpcm]</td><td>Ketjutettu $row[yht] kpl</td></tr>\n";
			}

			$ketjut[]  = $row["tunnukset"];
		}

		//haetaan kaikki laskutusvalmiit tilaukset joita *EI SAA* ketjuttaa, viite pit‰‰ olla tyhj‰‰ muuten ei laskuteta..
		$query  = "	select *
					from lasku use index (tila_index)
					where yhtio		= '$kukarow[yhtio]'
					and alatila		= 'V'
					and tila		= 'L'
					and ketjutus   != ''
					and viite		= ''
					$lasklisa";
		$result = mysql_query($query) or pupe_error($query);

		while ($row = mysql_fetch_array($result)) {

			$ketjukpl  = 1;

			// lis‰t‰‰n mukaan ketjuun
			$ketjut[]  = $row["tunnus"];

			if ($silent == "") {
				$tulos_ulos .= "<tr><td>$row[ytunnus]</td><td>$row[nimi]</td><td>$row[nimitark] </td><td>$row[postino]</td>\n
								<td>$row[postitp]</td><td>$row[maksuehto]</td><td>$row[erpcm]</td><td>".t("Ketjutettu")." $ketjukpl ".t("kpl")."</td></tr>\n";
			}
		}

		if ($silent == "") {
			$tulos_ulos .= "</table><br>\n";
		}

		//laskuri
		$lask 		= 0;
		$edilask 	= 0;

		// jos on jotain laskutettavaa ...
		if (count($ketjut) != 0) {

			//Timestamppi EDI-failiin alkuu ja loppuun
			$timestamppi=gmdate("YmdHis");

			//nyt meill‰ on $ketjut arrayssa kaikki yhteenkuuluvat tunnukset suoraan mysql:n IN-syntaksin muodossa!! jee!!
			foreach ($ketjut as $tunnukset) {

				// generoidaan laskulle viite ja lasno
				$query = "SELECT max(laskunro) FROM lasku WHERE yhtio='$kukarow[yhtio]' and tila='U'";
				$result= mysql_query($query) or pupe_error($query);
				$lrow  = mysql_fetch_array($result);

				$lasno = $lrow[0] + 1;

				// Tutkitaan onko ketju Nordean factorinkia
				$query  = "	SELECT factoring.sopimusnumero
							FROM lasku
							JOIN maksuehto ON lasku.yhtio=maksuehto.yhtio and lasku.maksuehto=maksuehto.tunnus and maksuehto.factoring='NORDEA'
							JOIN factoring ON maksuehto.yhtio=factoring.yhtio and maksuehto.factoring=factoring.factoringyhtio and lasku.valkoodi=factoring.valkoodi
							WHERE lasku.yhtio = '$kukarow[yhtio]'
							and lasku.tunnus in ($tunnukset)
							GROUP BY factoring.sopimusnumero";
				$fres = mysql_query($query) or pupe_error($query);
				$frow = mysql_fetch_array($fres);

				//Nordean viitenumero rakentuu hieman eri lailla ku normaalisti
				if ($frow["sopimusnumero"] > 0) {
					$viite = $frow["sopimusnumero"]."0".sprintf('%08d', $lasno);
				}
				else {
					$viite = $lasno;
				}

				require('../inc/generoiviite.inc');

				// p‰ivitet‰‰n ketjuun kuuluville laskuille sama laskunumero ja viite..
				$query  = "update lasku set laskunro='$lasno', viite='$viite' where yhtio='$kukarow[yhtio]' and tunnus in ($tunnukset)";
				$result = mysql_query($query) or pupe_error($query);

				// tehd‰‰n U lasku ja tiliˆinnit
				// tarvitaan $tunnukset mysql muodossa

				require("teeulasku.inc");

				// saadaan takaisin $laskurow
				$lasrow = $laskurow;

				// Luodaan tullausnumero jos sellainen tarvitaan
				//Jos on esim puhtaasti hyvityst‰ niin ei generoida tullausnumeroa
				if ($lasrow["vienti"] == 'K' and $lasrow["sisainen"] == "") {
					$query = "	SELECT yhtio
								FROM tilausrivi
								WHERE uusiotunnus = '$lasrow[tunnus]' and kpl > 0 and yhtio='$kukarow[yhtio]'";
					$cresult = mysql_query($query) or pupe_error($query);

					$hyvitys = "";
					if (mysql_num_rows($cresult) == 0) {
						//laskulla on vain hyvitysrivej‰, tai ei yht‰‰n rivi‰ --> ei tullata!
						$hyvitys = "ON";
					}
					else {
						$hyvitys = "EI";
					}

					$tullausnumero = '';

					if($hyvitys == 'EI') {
						//generoidaan tullausnumero.
						$p = date('d');
						$k = date('m');
						$v = date('Y');
						$pvm = $v."-".$k."-".$p;

						$query = "SELECT count(*)+1 FROM lasku use index (yhtio_tila_tapvm) WHERE vienti='K' and tila='U' and alatila='X' and tullausnumero!='' and tapvm='$pvm' and yhtio='$kukarow[yhtio]'";
						$result= mysql_query($query) or pupe_error($query);
						$lrow  = mysql_fetch_array($result);

						$pvanumero = date('z')+1;

						//tullausnumero muodossa Vuosi-Tullikamari-P‰iv‰nnumero-Tullip‰‰te-Juoksevanumeroperp‰iv‰
						$tullausnumero = date('y') . "-". $yhtiorow["tullikamari"] ."-" . sprintf('%03d', $pvanumero) . "-" . $yhtiorow["tullipaate"] . "-" . sprintf('%03d', $lrow[0]);

						// p‰ivitet‰‰n ketjuun kuuluville laskuille sama laskunumero ja viite..
						$query  = "update lasku set tullausnumero='$tullausnumero' WHERE vienti='K' and tila='U' and yhtio='$kukarow[yhtio]' and tunnus='$lasrow[tunnus]'";
						$result = mysql_query($query) or pupe_error($query);

						$lasrow["tullausnumero"] = $tullausnumero;
					}
				}

				if ($silent == "") {
					$tulos_ulos .= $tulos_ulos_ulasku;
					$tulos_ulos .= $tulos_ulos_tiliointi;
				}

				// haetaan maksuehdon tiedot
				$query  = "select * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$lasrow[maksuehto]'";
				$result = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result) == 0) {
					$masrow = array();

					if ($lasrow["erpcm"] == "0000-00-00") {
						$tulos_ulos .= "<font class='message'><br>\n".t("Maksuehtoa")." $lasrow[maksuehto] ".t("ei lˆydy!")." Tunnus $lasrow[tunnus] ".t("Laskunumero")." $lasrow[laskunro] ".t("meni nyt pahasti vituiks").".</font><br>\n<br>\n";
					}
				}
				else {
					$masrow = mysql_fetch_array($result);
				}

				//Haetaan factoringsopimuksen tiedot
				if ($masrow["factoring"] != '') {
					$query = "	SELECT *
								FROM factoring
								WHERE yhtio 		= '$kukarow[yhtio]'
								and factoringyhtio 	= '$masrow[factoring]'
								and valkoodi 		= '$lasrow[valkoodi]'";
					$fres = mysql_query($query) or pupe_error($query);
					$frow = mysql_fetch_array($fres);
				}

				// t‰ss‰ pohditaan laitetaanko verkkolaskuputkeen
				if (($lasrow["vienti"] == "" or ($lasrow["vienti"] == "E" and $lasrow["chn"] == "020")) and $masrow["itsetulostus"] == "" and $lasrow["sisainen"] == "" and $masrow["kateinen"] == "" and abs($lasrow["summa"]) != 0) {

					// Nyt meill‰ on:
					// $lasrow array on U-laskun tiedot
					// $yhtiorow array on yhtion tiedot
					// $masrow array maksuehdon tiedot

					// Etsit‰‰n myyj‰n nimi
					$mquery  = "select nimi
								from kuka
								where tunnus='$lasrow[myyja]' and yhtio='$kukarow[yhtio]'";
					$myyresult = mysql_query($mquery) or pupe_error($mquery);
					$myyrow = mysql_fetch_array($myyresult);

					if ($lasrow['chn'] == '') {
						//Paperi by default
						$lasrow['chn'] = 100;
					}
					if ($lasrow['chn'] == "020") {
						$lasrow['chn'] = "010";
					}

					if ($lasrow['arvo']>=0) {
						//Veloituslasku
						$tyyppi='380';
					}
					else {
						//Hyvityslasku
						$tyyppi='381';
					}

					// Laskukohtaiset kommentit kuntoon
					// T‰m‰ merkki | eli pystyviiva on rivinvaihdon merkki elmalla
					// Laskun kommentti on stripattu erikoismerkeist‰ jo aikaisemmin joten se on nyt puhdas t‰ss‰
					if (trim($lasrow['sisviesti1']) != '') {
						$lasrow['sisviesti1'] = str_replace(array("\r\n","\r","\n"),"|", $lasrow['sisviesti1']);
					}

					//Kirjoitetaan failiin laskun otsikkotiedot
					if ($lasrow["chn"] == "111") {
						elmaedi_otsik($tootedi, $lasrow, $masrow, $tyyppi, $timestamppi);
					}
					elseif($yhtiorow["verkkolasku_lah"] != "") {
						finvoice_otsik($tootfinvoice, $lasrow, $silent, $tulos_ulos);
					}
					else {
						pupevoice_otsik($tootxml, $lasrow, $kieli, $frow, $masrow, $myyrow, $tyyppi);
					}


					// Tarvitaan rivien eri verokannat
					$query = "	select alv, round(sum(rivihinta),2), round(sum(alv/100*rivihinta),2)
								from tilausrivi
								where yhtio='$kukarow[yhtio]' and otunnus in ($tunnukset) group by alv";
					$alvres = mysql_query($query) or pupe_error($query);

					while ($alvrow = mysql_fetch_array($alvres)) {
						// Kirjotetaan failiin arvierittelyt
						if ($lasrow["chn"] == "111") {
							elmaedi_alvierittely($tootedi, $alvrow);
						}
						elseif($yhtiorow["verkkolasku_lah"] != "") {
							finvoice_alvierittely($tootfinvoice, $lasrow, $alvrow);
						}
						else {
							pupevoice_alvierittely($tootxml, $alvrow);
						}
					}

					//Kirjoitetaan otsikkojen lopputiedot
					if ($lasrow["chn"] == "111") {
						elmaedi_otsikko_loput($tootedi, $lasrow);
					}
					elseif($yhtiorow["verkkolasku_lah"] != "") {
						finvoice_otsikko_loput($tootfinvoice, $lasrow, $masrow);
					}

					// katotaan miten halutaan sortattavan
					$sorttauskentta = generoi_sorttauskentta();

					// Kirjoitetaan rivitietoja tilausriveilt‰
					$query = "	SELECT *, $sorttauskentta
								FROM tilausrivi
								WHERE yhtio='$kukarow[yhtio]' and otunnus in ($tunnukset) and kpl<>0
								ORDER BY otunnus, sorttauskentta, tuoteno, tunnus";
					$tilres = mysql_query($query) or pupe_error($query);

					$rivinumero = 1;

					while ($tilrow = mysql_fetch_array($tilres)) {
						$vatamount = round($tilrow['rivihinta']*$tilrow['alv']/100, 2);
						$totalvat  = round($tilrow['rivihinta']+$vatamount, 2);

						$tilrow['kommentti'] 	= ereg_replace("[^A-Za-z0-9÷ˆƒ‰≈Â .,-/!|+()%]", " ", $tilrow['kommentti']);
						$tilrow['nimitys'] 		= ereg_replace("[^A-Za-z0-9÷ˆƒ‰≈Â .,-/!|+()%]", " ", $tilrow['nimitys']);

						if ($lasrow["chn"] == "111") {
							elmaedi_rivi($tootedi, $tilrow, $rivinumero);
						}
						elseif($yhtiorow["verkkolasku_lah"] != "") {
							finvoice_rivi($tootfinvoice, $tilrow, $lasrow, $vatamount, $totalvat);
						}
						else {
							pupevoice_rivi($tootxml, $tilrow, $vatamount, $totalvat);
						}
						$rivinumero++;
					}

					//Lopetetaan lasku
					if ($lasrow["chn"] == "111") {
						elmaedi_lasku_loppu($tootedi, $lasrow);

						$edilask++;
					}
					elseif($yhtiorow["verkkolasku_lah"] != "") {
						finvoice_lasku_loppu($tootfinvoice, $lasrow);
					}
					else {
						pupevoice_lasku_loppu($tootxml);
					}

					// Otetaan talteen jokainen laskunumero joka l‰hetet‰‰n jotta voidaan tulostaa paperilaskut
					$tulostettavat[] = $lasrow["laskunro"];
					$lask++;
				}
				elseif ($lasrow["sisainen"] != '') {
					$tulos_ulos .= "<br>\n".t("Tehtiin sis‰inen lasku")."! $lasrow[laskunro] $lasrow[nimi]<br>\n";

					// Sis‰isi‰ laskuja ei normaaalisti tuloseta paitski jos meill‰ on valittu_tulostin
					if ($valittu_tulostin != '') {
						$tulostettavat[] = $lasrow["laskunro"];
						$lask++;
					}
				}
				elseif ($masrow["kateinen"] != '') {
					if ($silent == "") {
						$tulos_ulos .= "<br>\n".t("K‰teislaskua ei l‰hetetty")."! $lasrow[laskunro] $lasrow[nimi]<br>\n";
					}

					// K‰teislaskuja ei l‰hetet‰ ulos mutta ne halutaan kuitenkin tulostaa itse
					$tulostettavat[] = $lasrow["laskunro"];
					$lask++;
				}
				elseif ($lasrow["vienti"] != '' or $masrow["itsetulostus"] != '') {
					if ($silent == "" or $silent == "VIENTI") {
						$tulos_ulos .= "<br>\n".t("T‰m‰ lasku tulostetaan omalle tulostimelle")."! $lasrow[laskunro] $lasrow[nimi]<br>\n";
					}

					// Halutaan tulostaa itse
					$tulostettavat[] = $lasrow["laskunro"];
					$lask++;
				}
				elseif($silent == "") {
					$tulos_ulos .= "\n".t("Nollasummaista laskua ei l‰hetetty")."! $lasrow[laskunro] $lasrow[nimi]<br>\n";
				}

				// p‰ivitet‰‰n kaikki laskut l‰hetetyiksi...
				$tquery = "UPDATE lasku SET alatila='X' WHERE (tunnus in ($tunnukset) or tunnus='$lasrow[tunnus]') and yhtio='$kukarow[yhtio]'";
				$tresult = mysql_query($tquery) or pupe_error($tquery);

			} // end foreach ketjut...

			if ($silent == "") {
				$tulos_ulos .= "<br><br>\n\n";
			}

			//Aineistojen lopput‰git
			elmaedi_aineisto_loppu($tootedi, $timestamppi);
			pupevoice_aineisto_loppu($tootxml);
		}

		// suljetaan faili
		fclose($tootxml);
		fclose($tootedi);
		fclose($tootfinvoice);

		//dellataan failit jos ne on tyhji‰
		if(filesize($nimixml) == 0) {
			unlink($nimixml);
		}
		if(filesize($nimifinvoice) == 0) {
			unlink($nimifinvoice);
		}
		if(filesize($nimiedi) == 0) {
			unlink($nimiedi);
		}

		// poistetaan lukot
		$query = "UNLOCK TABLES";
		$locre = mysql_query($query) or pupe_error($query);

		// jos laskutettiin jotain
		if ($lask > 0) {

			if ($silent == "" or $silent == "VIENTI") {
				$tulos_ulos .= t("Luotiin")." $lask ".t("laskua").".<br>\n";
			}

			//jos verkkotunnus lˆytyy niin
			if ($yhtiorow['verkkotunnus_lah'] != '' and file_exists(realpath($nimixml))) {

				if ($silent == "") {
					$tulos_ulos .= "<br><br>\n".t("FTP-siirto pupevoice:")."<br>\n";
				}

				//siirretaan laskutiedosto operaattorille
				$ftphost = "ftp.verkkolasku.net";
				$ftpuser = $yhtiorow['verkkotunnus_lah'];
				$ftppass = $yhtiorow['verkkosala_lah'];
				$ftppath = "out/einvoice/data/";
				$ftpfile = realpath($nimixml);

				// t‰t‰ ei ajata eik‰ k‰ytet‰, mutta jos tulee ftp errori niin echotaan t‰‰ meiliin, niin ei tartte k‰sin kirjotella resendi‰
				$cmd = "ncftpput -u $ftpuser -p $ftppass $ftphost $ftppath $ftpfile";

				require ("../inc/ftp-send.inc");

				if ($silent == "") {
					$tulos_ulos .= $tulos_ulos_ftp;
				}
			}
			elseif($silent == "") {
				$tulos_ulos .= t("Verkkolaskutus ei ole k‰ytˆss‰")."!<br>\n";
			}



			if ($edilask > 0 and $edi_ftphost != '' and file_exists(realpath($nimiedi))) {
				if ($silent == "") {
					$tulos_ulos .= "<br><br>\n".t("FTP-siirto Elma EDI-inhouse:")."<br>\n";
				}

				//siirretaan laskutiedosto operaattorille, EDI-inhouse muoto
				$ftphost = $edi_ftphost;
				$ftpuser = $edi_ftpuser;
				$ftppass = $edi_ftppass;
				$ftppath = $edi_ftppath;
				$ftpfile = realpath($nimiedi);

				require ("../inc/ftp-send.inc");

				if ($silent == "") {
					$tulos_ulos .= $tulos_ulos_ftp;
				}
			}

			// jos yhtiˆll‰ on laskuprintteri on m‰‰ritelty tai halutaan jostain muusta syyst‰ tulostella laskuja paperille
			if ($yhtiorow['lasku_tulostin'] != 0 or (isset($valittu_tulostin) and $valittu_tulostin != "")) {

				//K‰sin valittu tulostin
				if ($valittu_tulostin != "") {
					$yhtiorow['lasku_tulostin'] = $valittu_tulostin;
				}

				$tulos_ulos .= "<br>\n".t("Tulostetaan paperilaskuja").":<br>\n";

				foreach($tulostettavat as $lasku) {

					$tulos_ulos .= t("Tulostetaan lasku").": $lasku<br>\n";

					$query = "	SELECT *
								FROM lasku
								WHERE tila='U' and alatila='X' and laskunro='$lasku' and yhtio='$kukarow[yhtio]'";
					$laresult = mysql_query($query) or pupe_error($query);
					$laskurow = mysql_fetch_array($laresult);

					$otunnus = $laskurow["tunnus"];

					// haetaan maksuehdon tiedot
					$query  = "select * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
					$result = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($result) == 0) {
						$masrow = array();
					}
					else {
						$masrow = mysql_fetch_array($result);
					}

					//maksuehto tekstin‰
					$maksuehto 		= $masrow["teksti"]." ".$masrow["kassa_teksti"];
					$kateistyyppi	= $masrow["kateinen"];

					if ($yhtiorow['laskutyyppi'] == 0) {
						require_once("tulosta_lasku.inc");
					}
					elseif ($yhtiorow['laskutyyppi'] == 2) {
						require_once("tulosta_lasku_perhe.inc");
					}
					else {
						require_once("tulosta_lasku_plain.inc");
					}

					// katotaan miten halutaan sortattavan
					$sorttauskentta = generoi_sorttauskentta();

					// haetaan tilauksen kaikki rivit
					$query = "	SELECT *, $sorttauskentta
								FROM tilausrivi
								WHERE uusiotunnus='$laskurow[tunnus]' and yhtio='$kukarow[yhtio]'
								ORDER BY otunnus, sorttauskentta, tuoteno, tunnus";
					$result = mysql_query($query) or pupe_error($query);

					$sivu 	= 1;
					$summa 	= 0;
					$arvo 	= 0;

					// aloitellaan laskun teko
					$firstpage = alku();

					$varasto = 0;

					while ($row = mysql_fetch_array($result)) {
						rivi($firstpage);

						//Tutkitaan mihin printteriin t‰m‰n laskun voisi tulostaa
						if ($yhtiorow["lasku_tulostin"] == "AUTOMAAGINEN_VALINTA" and $varasto == 0) {
							//otetaan varastopaikat talteen
							$query = "	SELECT *
										FROM tuote
										WHERE tuoteno='$row[tuoteno]' and yhtio='$kukarow[yhtio]'";
							$tresult = mysql_query($query) or pupe_error($query);
							$tuoterow = mysql_fetch_array($tresult);

							if ($tuoterow["ei_saldoa"] == '') {
								$varasto = kuuluukovarastoon($row["hyllyalue"], $row["hyllynro"], "");
							}
						}
					}

					loppu($firstpage);
					alvierittely ($firstpage, $kala);

					//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
					list($usec, $sec) = explode(' ', microtime());
					mt_srand((float) $sec + ((float) $usec * 100000));
					$pdffilenimi = "/tmp/Lasku_Ajo-".md5(uniqid(mt_rand(), true)).".pdf";

					//kirjoitetaan pdf faili levylle..
					$fh = fopen($pdffilenimi, "w");
					if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus ep‰onnistui $pdffilenimi");
					fclose($fh);

					//haetaan varaston tiedot
					if ($yhtiorow["lasku_tulostin"] == "AUTOMAAGINEN_VALINTA") {
						if ($varasto != 0) {
							$query = "select * from varastopaikat where yhtio='$kukarow[yhtio]' and tunnus='$varasto' order by alkuhyllyalue,alkuhyllynro";
						}
						else {
							$query = "select * from varastopaikat where yhtio='$kukarow[yhtio]' order by alkuhyllyalue,alkuhyllynro limit 1";
						}
						$prires= mysql_query($query) or pupe_error($query);
						$prirow= mysql_fetch_array($prires);
						$yhtiorow['lasku_tulostin'] = $prirow["printteri5"];

						$tulos_ulos .= t("Lasku tulostuu varastoon").": $prirow[nimitys]<br>\n";
					}

					$querykieli = "	select *
									from kirjoittimet
									where yhtio='$kukarow[yhtio]' and tunnus='$yhtiorow[lasku_tulostin]'";
					$kires = mysql_query($querykieli) or pupe_error($querykieli);
					$kirow = mysql_fetch_array($kires);

					$tulos_ulos .= t("Lasku tulostuu kirjoittimelle").": $kirow[kirjoitin]<br>\n";

					if ($kirow["komento"] != "email") {
						// itse print komento...
						$line = exec("$kirow[komento] $pdffilenimi");
					}
					elseif ($kukarow["eposti"] != '') {
						// l‰hetet‰‰n meili
						$kutsu = "lasku $lasku";
						$liite = $pdffilenimi;
						include ("inc/sahkoposti.inc"); // sanotaan include eik‰ require niin ei kuolla
					}

					if ($valittu_kopio_tulostin != '') {
						$querykieli = "	select *
										from kirjoittimet
										where yhtio='$kukarow[yhtio]' and tunnus='$valittu_kopio_tulostin'";
						$kires = mysql_query($querykieli) or pupe_error($querykieli);
						$kirow = mysql_fetch_array($kires);

						$tulos_ulos .= t("Laskukopio tulostuu kirjoittimelle").": $kirow[kirjoitin]<br>\n";

						if ($kirow["komento"] != "email") {
							// itse print komento...
							$line = exec("$kirow[komento] $pdffilenimi");
						}
						elseif ($kukarow["eposti"] != '') {
							// l‰hetet‰‰n meili
							$kutsu = "lasku $lasku";
							$liite = $pdffilenimi;
							include ("inc/sahkoposti.inc"); // sanotaan include eik‰ require niin ei kuolla
						}
					}


					//poistetaan tmp file samantien kuleksimasta...
					system("rm -f $pdffilenimi");
					unset($pdf);

					if ($laskurow["vienti"] == "K" and $hyvitys == "EI") {

						$uusiotunnus = $laskurow["tunnus"];

						if ($yhtiorow["sad_lomake_tyyppi"] == "T") {
							// Tulostetaan Teksti-versio SAD-lomakkeeesta
							require('tulosta_sadvientiilmo_teksti.inc');

							if ($paalomake != '') {
								lpr($paalomake, $valittu_sadtulostin);

								$tulos_ulos .=  t("SAD-lomake tulostuu")."...<br>\n";

								if ($lisalomake != "") {
									lpr($lisalomake, $valittu_sadlitulostin);

									$tulos_ulos .=  t("SAD-lomakkeen lis‰sivu tulostuu")."...<br>\n";
								}
							}
						}
						else {
							// Tulostetaan PDF-versio SAD-lomakkeeesta
							require('tulosta_sadvientiilmo.inc');

							//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
							list($usec, $sec) = explode(' ', microtime());
							mt_srand((float) $sec + ((float) $usec * 100000));
							$pdffilenimi = "/tmp/SAD_Lomake_Kopio-".md5(uniqid(mt_rand(), true)).".pdf";

							//kirjoitetaan pdf faili levylle..
							$fh = fopen($pdffilenimi, "w");
							if (fwrite($fh, $pdf2->generate()) === FALSE) die("PDF kirjoitus ep‰onnistui $pdffilenimi");
							fclose($fh);

							if ($kirow["komento"] != "email") {
								// itse print komento...
								$line = exec("$kirow[komento] $pdffilenimi");
							}
							elseif ($kukarow["eposti"] != '') {
								// l‰hetet‰‰n meili
								$kutsu = "lasku $lasku SAD-lomake";
								$liite = $pdffilenimi;
								include ("inc/sahkoposti.inc"); // sanotaan include eik‰ require niin ei kuolla
							}

							//poistetaan tmp file samantien kuleksimasta...
							system("rm -f $pdffilenimi");

							unset($pdf2);
							unset($sadilmo);

							$tulos_ulos .= t("SAD-lomake tulostuu")."...<br>\n";
						}
					}

					if ($laskurow["vienti"] == "E") {
						$uusiotunnus = $laskurow["tunnus"];

						require('tulosta_vientierittely.inc');

						//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
						list($usec, $sec) = explode(' ', microtime());
						mt_srand((float) $sec + ((float) $usec * 100000));
						$pdffilenimi = "/tmp/Vientierittely_Kopio-".md5(uniqid(mt_rand(), true)).".pdf";

						//kirjoitetaan pdf faili levylle..
						$fh = fopen($pdffilenimi, "w");
						if (fwrite($fh, $Xpdf->generate()) === FALSE) die("PDF kirjoitus ep‰onnistui $pdffilenimi");
						fclose($fh);

						if ($kirow["komento"] != "email") {
							// itse print komento...
							$line = exec("$kirow[komento] $pdffilenimi");
						}
						elseif ($kukarow["eposti"] != '') {
							// l‰hetet‰‰n meili
							$kutsu = "lasku $lasku Vientierittely";
							$liite = $pdffilenimi;
							include ("inc/sahkoposti.inc"); // sanotaan include eik‰ require niin ei kuolla
						}

						//poistetaan tmp file samantien kuleksimasta...
						system("rm -f $pdffilenimi");

						$tulos_ulos .= t("Vientierittely tulostuu")."...<br>\n";

						unset($Xpdf);
					}

					// ei exhaustata muistia
					unset($pdf);

					//PDF parametrit
					$pdf = new pdffile;
					$pdf->set_default('margin-top', 	0);
					$pdf->set_default('margin-bottom', 	0);
					$pdf->set_default('margin-left', 	0);
					$pdf->set_default('margin-right', 	0);
					$rectparam["width"] = 0.3;

					$norm["height"] = 10;
					$norm["font"] = "Times-Roman";

					$pieni["height"] = 8;
					$pieni["font"] = "Times-Roman";

					// defaultteja
					$kala = 540;
					$lask = 1;
					$sivu = 1;
				}
			}
		}
		elseif($silent == "") {
			$tulos_ulos .= t("Yht‰‰n laskua ei siirretty/tulostettu!")."<br>\n";
		}

		// l‰hetet‰‰n meili vaan jos on jotain laskutettavaa
		if ($lask > 0) {

			//echotaan ruudulle ja l‰hetet‰‰n meili yhtiorow[admin]:lle
			$bound = uniqid(time()."_") ;

			$header  = "From: <$yhtiorow[postittaja_email]>\n";
			$header .= "MIME-Version: 1.0\n" ;
			$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

			$content = "--$bound\n";

			$content .= "Content-Type: text/html;charset=iso-8859-1\n" ;
			$content .= "Content-Transfer-Encoding: quoted-printable\n";

			$content .= "<html><body>\n";
			$content .= $tulos_ulos;
			$content .= "</body></html>\n";
			$content .= "\n";
			$content .= "--$bound--\n";

			mail($yhtiorow["admin_email"],  "$yhtiorow[nimi] - Laskutusajo", $content, $header);
		}
	}

	if ($komentorivilta == "") {
		echo "$tulos_ulos";
	}

	if ($tee == '' and strpos($_SERVER['SCRIPT_NAME'], "verkkolasku.php") !== FALSE) {

		echo "	<SCRIPT LANGUAGE=JAVASCRIPT>

				function verify(){
					var pp = document.lasku.laskpp;
					var kk = document.lasku.laskkk;
					var vv = document.lasku.laskvv;

					if (vv > 0) {

						pp = Number(pp.value);
						kk = Number(kk.value)-1;
						vv = Number(vv.value);

						if (vv < 1000) {
							vv = vv+2000;
						}

						var dateSyotetty = new Date(vv,kk,pp);
						var dateTallaHet = new Date();
						var ero = (dateTallaHet.getTime() - dateSyotetty.getTime()) / 86400000;

						dateSyotetty = dateSyotetty.getTime();

						if(ero > 2) {
							var msg = '".t("Oletko varma, ett‰ haluat p‰iv‰t‰ laskun yli 2pv menneisyyteen?")."';
							return confirm(msg);
						}
					}
				}
			</SCRIPT>";


		echo "<br>\n<table>";
		$today = date("w") + 1;


		$query = "	select
					sum(if(lasku.laskutusvkopv='0',1,0)) normaali,
					sum(if(lasku.laskutusvkopv='$today',1,0)) paiva,
					sum(if(lasku.laskutusvkopv!='$today' and lasku.laskutusvkopv!='0',1,0)) muut,
					sum(if(maksuehto.factoring!='',1,0)) factoroitavat,
					count(lasku.tunnus) kaikki
					from lasku
					LEFT JOIN maksuehto ON lasku.yhtio=maksuehto.yhtio and lasku.maksuehto=maksuehto.tunnus
					where lasku.yhtio	= '$kukarow[yhtio]'
					and lasku.tila	= 'L'
					and lasku.alatila	= 'D'
					and lasku.viite	= ''
					and lasku.chn	!= '999'";
		$res = mysql_query($query) or pupe_error($query);
		$row = mysql_fetch_array($res);


		echo "<form action = '$PHP_SELF' method = 'post' name='lasku' onSubmit = 'return verify()'>
			<input type='hidden' name='tee' value='TARKISTA'>";


		echo "<tr><th>".t("Laskutettavia tilauksia joilla on laskutusviikonp‰iv‰ t‰n‰‰n").":</th><td colspan='3'>$row[paiva]</td></tr>\n";
		echo "<tr><th>".t("Laskutettavia tilauksia joiden laskutusviikonp‰iv‰ ei ole t‰n‰‰n").":</th><td colspan='3'>$row[muut]</td></tr>\n";
		echo "<tr><th>".t("Laskutettavia tilauksia joilla EI ole laskutusviikonp‰iv‰‰").":</th><td colspan='3'>$row[normaali]</td></tr>\n";
		echo "<tr><th>".t("Laskutettavia tilauksia jotka siirret‰‰n rahoitukseen").":</th><td colspan='3'>$row[factoroitavat]</td></tr>\n";
		echo "<tr><th>".t("Laskutettavia tilauksia kaikkiaan").":</th><td colspan='3'>$row[kaikki]</td></tr>\n";

		echo "<tr><th>".t("Syˆt‰ poikkeava laskutusp‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>
				<td><input type='text' name='laskpp' value='' size='3'></td>
				<td><input type='text' name='laskkk' value='' size='3'></td>
				<td><input type='text' name='laskvv' value='' size='5'></td></tr>\n";

		echo "<tr><th>".t("Ohita laskujen laskutusviikonp‰iv‰t").":</th><td colspan='3'><input type='checkbox' name='laskutakaikki'></td></tr>\n";


		echo "</table>";

		echo "<br>\n<input type='submit' value='".t("Jatka")."'>";
		echo "</form>";
	}

	if ($komentorivilta != "ON" and strpos($_SERVER['SCRIPT_NAME'], "verkkolasku.php") !== FALSE) {
		require ("../inc/footer.inc");
	}

?>
