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

	//$silent = '';

	if (trim($argv[1]) != '') {

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

		if (mysql_num_rows($yhtiores)==1) {
			$yhtiorow = mysql_fetch_array($yhtiores);

			// haetaan yhtiˆn parametrit
			$query = "	SELECT *
						FROM yhtion_parametrit
						WHERE yhtio='$yhtiorow[yhtio]'";
			$result = mysql_query($query)
					or die ("Kysely ei onnistu yhtio $query");

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

		function xml_add ($joukko, $tieto, $handle) {
			$ulos = "<$joukko>";

			if (strlen($tieto) > 0) {
				//K‰sitell‰‰n xml-entiteetit
				$serc = array("&", ">", "<", "'", "\"", "¥", "`");
				$repl = array("&amp;", "&gt;", "&lt;", "&apos;", "&quot;", " ", " ");
				$tieto = str_replace($serc, $repl, $tieto);

				$ulos .= $tieto;

			}

			$ulos .= "</$joukko>\n";

			fputs($handle, $ulos);
		}

		function dateconv ($date) {
			//k‰‰nt‰‰ mysqln vvvv-kk-mm muodon muotoon vvvvkkmm
			return substr($date,0,4).substr($date,5,2).substr($date,8,2);
		}

		//tehd‰‰n viitteest‰ SPY standardia eli 20 merkki‰ etunollilla
		function spyconv ($spy) {
			return $spy = sprintf("%020.020s",$spy);
		}

		//pilkut pisteiksi
		function pp ($muuttuja) {
			return $muuttuja = str_replace(".",",",$muuttuja);
		}

		function ymuuta ($ytunnus) {
			$ytunnus = sprintf("%08.8s",$ytunnus);
			return substr($ytunnus,0,7)."-".substr($ytunnus,-1);
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
		$query = "LOCK TABLES lasku WRITE, tilausrivi WRITE, sanakirja WRITE, tapahtuma WRITE, tuotepaikat WRITE, tiliointi WRITE, toimitustapa READ, maksuehto READ, sarjanumeroseuranta READ, tullinimike READ, kuka READ, varastopaikat READ, tuote READ, rahtikirjat READ, kirjoittimet READ, tuotteen_avainsanat READ, tuotteen_toimittajat READ, asiakas READ, rahtimaksut READ, avainsana READ";
		$locre = mysql_query($query) or pupe_error($query);

		// haetaan kaikki tilaukset jotka on toimitettu ja kuuluu laskuttaa t‰n‰‰n (t‰t‰ resulttia k‰ytet‰‰n alhaalla lis‰‰)
		$lasklisa = "";

		// tarkistetaan t‰ss‰ tuleeko laskutusviikonp‰iv‰t ohittaa
		// ohitetaan jos ruksi on ruksattu tai poikkeava laskutusp‰iv‰m‰‰r‰ on syˆtetty
		if ($laskutakaikki == "") {
			$lasklisa .= " and (laskutusvkopv='0' or laskutusvkopv='$today')";
		}

		// katotaan halutaanko laskuttaa vaan laskut joita *EI SAA* ketjuttaa
		if ($eiketjut == "KYLLA") {
			$lasklisa .= " and ketjutus != '' ";
		}

		// laskutetaan vain tietyt tilausket
		if ($laskutettavat != "") {
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

		if ($tulos_ulos_maksusoppari != '' and $silent == "") {
			$tulos_ulos .= "<br>\n".t("Maksusopimustilausket").":<br>\n";
			$tulos_ulos .= $tulos_ulos_maksusoppari;
		}

		if ($tulos_ulos_ehtosplit != '' and $silent == "") {
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
			$tulos_ulos .= "<br>\n".t("J‰lkivaatimuskulut").":<br>\n<table>";
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
		$query  = "	select ytunnus, nimi, nimitark, osoite, postino, postitp, maksuehto, erpcm, vienti,
					lisattava_era, vahennettava_era, maa_maara, kuljetusmuoto, kauppatapahtuman_luonne,
					sisamaan_kuljetus, aktiivinen_kuljetus, kontti, aktiivinen_kuljetus_kansallisuus,
					sisamaan_kuljetusmuoto, poistumistoimipaikka, poistumistoimipaikka_koodi, chn, count(*) yht
					from lasku
					where yhtio		= '$kukarow[yhtio]'
					and alatila		= 'V'
					and tila		= 'L'
					and ketjutus	= ''
					and viite		= ''
					$lasklisa
					group by 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22
					order by 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22";
		$result = mysql_query($query) or pupe_error($query);

		if ($silent == "") {
			$tulos_ulos .= "<br><br>\n".t("Laskujen ketjutus:")."<br>\n<table>";
		}

		while ($row = mysql_fetch_array($result)) {

			$query    = "	select tunnus
							from lasku
							where yhtio				= '$kukarow[yhtio]'
							and alatila				= 'V'
							and tila				= 'L'
							and ketjutus			= ''
							and viite				= ''
							and ytunnus				= '$row[ytunnus]'
							and nimi				= '$row[nimi]'
							and nimitark			= '$row[nimitark]'
							and osoite				= '$row[osoite]'
							and postino				= '$row[postino]'
							and postitp				= '$row[postitp]'
							and maksuehto			= '$row[maksuehto]'
							and erpcm				= '$row[erpcm]'
							and vienti				= '$row[vienti]'
							and lisattava_era		= '$row[lisattava_era]'
							and vahennettava_era	= '$row[vahennettava_era]'
							and maa_maara 			= '$row[maa_maara]'
							and kuljetusmuoto 		= '$row[kuljetusmuoto]'
							and kauppatapahtuman_luonne = '$row[kauppatapahtuman_luonne]'
							and sisamaan_kuljetus 	= '$row[sisamaan_kuljetus]'
							and aktiivinen_kuljetus = '$row[aktiivinen_kuljetus]'
							and kontti 				= '$row[kontti]'
							and aktiivinen_kuljetus_kansallisuus = '$row[aktiivinen_kuljetus_kansallisuus]'
							and sisamaan_kuljetusmuoto 	= '$row[sisamaan_kuljetusmuoto]'
							and poistumistoimipaikka 	= '$row[poistumistoimipaikka]'
							and poistumistoimipaikka_koodi = '$row[poistumistoimipaikka_koodi]'
							and chn					= '$row[chn]'";
			$lares    = mysql_query($query) or pupe_error($query);

			$tunnukset= "";

			while ($larow = mysql_fetch_array($lares)) {
				$tunnukset .= "'$larow[tunnus]',";
			}

			if ($silent == "") {
				$tulos_ulos .= "<tr><td>$row[ytunnus]</td><td>$row[nimi]<br>$row[nimitark]</td><td>$row[osoite]</td><td>$row[postino]</td>\n
								<td>$row[postitp]</td><td>$row[maksuehto]</td><td>$row[erpcm]</td><td>Ketjutettu $row[yht] kpl</td></tr>\n";
			}

			$tunnukset = substr($tunnukset,0,-1);
			$ketjut[]  = $tunnukset;
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
			$ketjut[]  = "'$row[tunnus]'";

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

		if ($silent == "") {
			$tulos_ulos .= "<br><br>\n".t("Tehd‰‰n u-laskut:")."<br>\n";
		}

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
				$query  = "	SELECT maksuehto.factoring 
							FROM lasku, maksuehto
							WHERE lasku.yhtio = '$kukarow[yhtio]'
							and lasku.tunnus in ($tunnukset)
							and lasku.yhtio = maksuehto.yhtio
							and lasku.maksuehto = maksuehto.tunnus
							and maksuehto.factoring = 'N'
							group by (maksuehto.factoring)";
				$mkjres = mysql_query($query) or pupe_error($query);
				
				//Nordean viitenumero rakentuu hieman eri lailla ku normmalisti
				if (mysql_num_rows($mkjres) == 1) {
					$viite = $yhtiorow["factoringsopimus"]."0".sprintf('%08d', $lasno);
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

				// Luodaan tullusnumero jos sellainen tarvitaan
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

						$query = "SELECT count(*) FROM lasku use index (yhtio_tila_tapvm) WHERE vienti='K' and tila='U' and tapvm='$pvm' and yhtio='$kukarow[yhtio]'";
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

				// t‰ss‰ pohditaan laitetaanko verkkolaskuputkeen
				if (($lasrow["vienti"] == "" or ($lasrow["vienti"] == "E" and $lasrow["chn"] == "020")) and
					$masrow["itsetulostus"] == "" and
					$lasrow["sisainen"] == "" and
					$masrow["kateinen"] == "" and
					abs($lasrow["summa"]) != 0) {

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
						// Estet‰‰n KUMMALLISUUDET (paperi by default)
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

					// Laskukohtaisen kommentit kuntoon
					// T‰m‰ merkki | eli pystyviiva on rivinvaihdon merkki elmalla
					// Laskun kommentti on stripattu erikoismerkeist‰ jo aikaisemmin joten se on nyt puhdas t‰ss‰
					if (trim($lasrow['sisviesti1']) != '') {
						$laskunkommentit = str_replace(array("\r\n","\r","\n"),"|", $lasrow['sisviesti1']);
					}
					else {
						$laskunkommentit = "";
					}

					if ($lasrow["chn"] == "111") {

						$fstat = fstat($tootedi);

						if ($fstat["size"] == 0) {
							fputs($tootedi, "ICHGSTART:$timestamppi\n");
							fputs($tootedi, "ICHG_TYPE:POS\n");
							fputs($tootedi, "ICHG_SNDR:".sprintf("%-35.35s",str_replace('-','', $yhtiorow["ovttunnus"]))."@30\n");
							fputs($tootedi, "ICHG_RCPT:003705655815                       @30\n");
							fputs($tootedi, "ICHG_DATA:EIH-1.4.0\n");
							fputs($tootedi, "ICHG_TEST:1\n");
							fputs($tootedi, "ICHG_INFO:TESTITULOKSET PUPESOFTILLE\n");
						}

						// Kirjotetaan laskun tietoja Elman inhouse EDI muotoon
						fputs($tootedi, "IMSGSTART:".substr($lasrow["tunnus"], -6)."\n");
						fputs($tootedi, "IHDRSTART:".substr($lasrow["tunnus"], -6)."\n");
						fputs($tootedi, "IBGMITYPE:$tyyppi\n");
						fputs($tootedi, "IBGMINUMB:$lasrow[laskunro]\n");
						fputs($tootedi, "IDTM3__DT:".sprintf("%-35.35s",dateconv($lasrow["tapvm"]))."@102\n");
						fputs($tootedi, "IDTM171DT:".sprintf("%-35.35s",dateconv($lasrow["tapvm"]))."@102\n");
						fputs($tootedi, "IRFFPK_NU:$lasrow[tunnus]\n");
						fputs($tootedi, "IRFFVN_NU:$lasrow[viesti]\n");
						fputs($tootedi, "INADSE_CC:$lasrow[viesti]\n");
						fputs($tootedi, "IRFFCO_NU:$lasrow[viitetxt]\n");
						fputs($tootedi, "IFTXAAITX:\n");
						fputs($tootedi, "IFTXAAITX:\n");
						fputs($tootedi, "IFTXAAITX:\n");
						fputs($tootedi, "IFTXAAITX:\n");
						fputs($tootedi, "INADSE_PC:".sprintf("%-17.17s",$yhtiorow["ovttunnus"])."@100\n");
						fputs($tootedi, "INADSE_PC:".sprintf("%-17.17s","7002479")."@92\n");
						fputs($tootedi, "INADSE_NA:$yhtiorow[nimi]\n");
						fputs($tootedi, "INADSE_SA:$yhtiorow[osoite]\n");
						fputs($tootedi, "INADSE_CI:$yhtiorow[postitp]\n");
						fputs($tootedi, "INADSE_PO:$yhtiorow[postino]\n");
						fputs($tootedi, "INADPL_RF:IT @$lasrow[toim_ovttunnus]\n");
						fputs($tootedi, "INADPL_RF:ZZ @$lasrow[ytunnus]\n");
						fputs($tootedi, "INADPL_NA:$lasrow[toim_nimi]\n");
						fputs($tootedi, "INADPL_NX:$lasrow[toim_nimitark]\n");
						fputs($tootedi, "INADPL_SA:$lasrow[toim_osoite]\n");
						fputs($tootedi, "INADPL_CI:$lasrow[toim_postitp]\n");
						fputs($tootedi, "INADPL_PO:$lasrow[toim_postino]\n");
						fputs($tootedi, "INADIV_NA:$lasrow[nimi]\n");
						fputs($tootedi, "INADIV_NX:$lasrow[nimitark]\n");
						fputs($tootedi, "INADIV_SA:$lasrow[osoite]\n");
						fputs($tootedi, "INADIV_CI:$lasrow[postitp]\n");
						fputs($tootedi, "INADIV_PO:$lasrow[postino]\n");
						fputs($tootedi, "INADDP_NA:$lasrow[toim_nimi]\n");
						fputs($tootedi, "INADDP_NX:$lasrow[toim_nimitark]\n");
						fputs($tootedi, "INADDP_SA:$lasrow[toim_osoite]\n");
						fputs($tootedi, "INADDP_CI:$lasrow[toim_postitp]\n");
						fputs($tootedi, "INADDP_PO:$lasrow[toim_postino]\n");
						fputs($tootedi, "ICUX1__CR:$yhtiorow[valkoodi]\n");
						fputs($tootedi, "IPAT1__DT:13 @".sprintf("%-35.35s",dateconv($lasrow["erpcm"]))."@102\n");
						fputs($tootedi, "IPAT1__PC:15 @$lasrow[viikorkopros]\n");
						fputs($tootedi, "IPAT1__TP:$masrow[teksti] $masrow[kassa_teksti]\n");

						if ($lasrow["kasumma"] != 0) {
							fputs($tootedi, "IPAT8__DT:12 @".sprintf("%-35.35s",dateconv($lasrow["kapvm"]))."@102\n");
							fputs($tootedi, "IPAT8__PC:12 @$masrow[kassa_alepros]\n");
							fputs($tootedi, "IPAT8__MA:12 @".sprintf("%018.2f", $lasrow["kasumma"])."@$yhtiorow[valkoodi]\n");
						}

						fputs($tootedi, "IRFFPQ_NU:$lasrow[viite]\n");
						fputs($tootedi, "IMOA39_MA:$lasrow[summa]\n");
					}
					// Finvoice
					elseif($yhtiorow["verkkolasku_lah"] != "") {

						//varmuudeksi alku aina puhtaalta pˆyd‰lt‰
						$senderpartyid 			= '';
						$senderintermediator 	= '';
						$receiverpartyid 		= '';
						$receiverintermediator 	= '';
						$val 					= $lasrow['valkoodi'];

						if($yhtiorow["verkkolasku_lah"] == "iban+soap") {
							//tehd‰‰n Finvoicen SOAP-Envelope ibantunnuksella

							$senderpartyid 			= str_replace(" ", "", $yhtiorow['pankkiiban1']);
							$senderintermediator	= $yhtiorow['pankkiswift1'];
						}
						elseif($yhtiorow["verkkolasku_lah"] == "ovt+soap") {
							//tehd‰‰n Finvoicen SOAP-Envelope ibantunnuksella

							$senderpartyid 			= str_replace(" ", "", $yhtiorow['ovttunnus']);
							$senderintermediator	= $yhtiorow['pankkiswift1'];
						}

						//	Aloitellaan aineiston luontia
						if($senderpartyid != "" and $senderintermediator != "") {

							list($receiverpartyid, $receiverintermediator) = explode("@",$lasrow["verkkotunnus"]);

							// jos t‰nne ei saada mit‰‰n arvoja laitetaan se edelleen pankkiin, mutta annetaan lipuksi tulostukseen
							if($receiverpartyid == "" or $receiverintermediator == "" or $lasrow["chn"] == 100) {
								$receiverpartyid 		= "tulostukseen";
								$receiverintermediator 	= $yhtiorow['pankkiswift1'];

								if($lasrow["chn"] != 100 and $silent == "") {
									$tulos_ulos .= "<font class='error'>".t("Asiakkaalta puuttuu verkkotunnus, lasku menee tulostukseen.")." $lasrow[nimi] $lasrow[laskunro]</font><br>\n<br>\n";
								}
							}

							// 	Tehd‰‰n SOAP
							fputs ($tootfinvoice, "<SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" xmlns:eb=\"http://www.oasis-open.org/committees/ebxml-msg/schema/msg-header-2_0.xsd\">\n");
							fputs ($tootfinvoice, "<SOAP-ENV:Header>\n");
							fputs ($tootfinvoice, "<eb:MessageHeader xmlns:eb=\"http://www.oasis-open.org/committees/ebxml-msg/schema/msg-header-2_0.xsd\" SOAP-ENV:mustUnderstand=\"1\" eb:version=\"2.0\">\n");

							fputs ($tootfinvoice, "<eb:From>\n");
							xml_add("eb:PartyId", $senderpartyid ,								$tootfinvoice);
							xml_add("eb:Role", "Sender", 										$tootfinvoice);
							fputs ($tootfinvoice, "</eb:From>\n");

							fputs ($tootfinvoice, "<eb:From>\n");
							xml_add("eb:PartyId", $senderintermediator ,						$tootfinvoice);
							xml_add("eb:Role", "Intermediator",									$tootfinvoice);
							fputs ($tootfinvoice, "</eb:From>\n");

							fputs ($tootfinvoice, "<eb:To>\n");
							xml_add("eb:PartyId", $receiverpartyid ,							$tootfinvoice);
							xml_add("eb:Role", "Receiver",										$tootfinvoice);
							fputs ($tootfinvoice, "</eb:To>\n");

							fputs ($tootfinvoice, "<eb:To>\n");
							xml_add("eb:PartyId", $receiverintermediator ,						$tootfinvoice);
							xml_add("eb:Role", "Intermediator",									$tootfinvoice);
							fputs ($tootfinvoice, "</eb:To>\n");

							xml_add("eb:CPAId", "yoursand-mycpa", 								$tootfinvoice); //	Ei ilmeisesti juuri merkitt‰v‰
							xml_add("eb:ConversationId", "nnnnn", 								$tootfinvoice);	// 	Ei ilmeisesti juuri merkitt‰v‰
							xml_add("eb:Service", "Routing",									$tootfinvoice); //	Ei ilmeisesti juuri merkitt‰v‰
							xml_add("eb:Action", "ProcessInvoice",								$tootfinvoice);
							fputs ($tootfinvoice, "<eb:MessageData>\n");
							xml_add("eb:MessageId", date("YmdHis")."-".$lasrow['laskunro'], 	$tootfinvoice);
							xml_add("eb:Timestamp", date("Y-m-d")."T".date("H:i:s")."+02", 		$tootfinvoice);
							fputs ($tootfinvoice, "<eb:RefToMessageId/>\n");
							fputs ($tootfinvoice, "</eb:MessageData>\n");
							fputs ($tootfinvoice, "</eb:MessageHeader>\n");
							fputs ($tootfinvoice, "</SOAP-ENV:Header>\n");
							fputs ($tootfinvoice, "<SOAP-ENV:Body>\n");
							fputs ($tootfinvoice, "<eb:Manifest eb:id=\"Manifest\" eb:version=\"2.0\">\n");
							fputs ($tootfinvoice, "<eb:Reference eb:id=\"Finvoice\" xlink:href=\"$mid\">\n");
							fputs ($tootfinvoice, "<eb:schema eb:location=\"http://www.pankkiyhdistys.fi/verkkolasku/finvoice/finvoice.xsd\" eb:version=\"2.0\"/>\n");
							fputs ($tootfinvoice, "</eb:Reference>\n");
							fputs ($tootfinvoice, "</eb:Manifest>\n");
							fputs ($tootfinvoice, "</SOAP-ENV:Body>\n");
							fputs ($tootfinvoice, "</SOAP-ENV:Envelope>\n");

							//laitetaan setit kuntoon..
							fputs($tootfinvoice, "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n");
							fputs($tootfinvoice, "<!DOCTYPE Finvoice SYSTEM \"Finvoice.dtd\">\n");
							fputs($tootfinvoice, "<?xml-stylesheet type=\"text/xsl\" href=\"Finvoice.xsl\"?>\n");

							//t‰st‰ l‰htee Finvoice koodi
							fputs($tootfinvoice, "<Finvoice Version=\"1.2\">\n");
							fputs($tootfinvoice, "<SellerPartyDetails>\n");
							xml_add("SellerPartyIdentifier",											ymuuta($yhtiorow['ytunnus']), 							$tootfinvoice);
							xml_add("SellerPartyIdentifierUrlText",										"http://www.ytj.fi/yrit_sel2.asp?kielikoodi=1",			$tootfinvoice);
							xml_add("SellerOrganisationName",											$yhtiorow['nimi'],	$tootfinvoice);
							xml_add("SellerOrganisationTaxCode", 										ymuuta($yhtiorow['ytunnus']),	$tootfinvoice);
							xml_add("SellerOrganisationTaxCodeUrlText",									"http://europa.eu.int/comm/taxation_customs/vies/fi/vieshome.htm", 	$tootfinvoice);

							fputs($tootfinvoice, "<SellerPostalAddressDetails>\n");
							xml_add("SellerStreetName", 												$yhtiorow['osoite'], 									$tootfinvoice);
							xml_add("SellerTownName", 													$yhtiorow['postitp'], 									$tootfinvoice);
							xml_add("SellerPostCodeIdentifier", 										$yhtiorow['postino'], 									$tootfinvoice);
							xml_add("CountryCode",														$yhtiorow['maakoodi'],									$tootfinvoice);
							xml_add("CountryName",														$yhtiorow['maa'], 										$tootfinvoice);
							fputs($tootfinvoice, "</SellerPostalAddressDetails>\n");

							fputs($tootfinvoice, "</SellerPartyDetails>\n");

							//xml_add("SellerOrganisationUnitNumber",									$yhtiorow["ovttunnus"], 								$tootfinvoice);

							xml_add("SellerContactPersonName",											$lasrow["laatija"], 									$tootfinvoice);

							fputs($tootfinvoice, "<SellerCommunicationDetails>\n");
							xml_add("SellerEmailaddressIdentifier",										$yhtiorow['email'], 									$tootfinvoice);
							fputs($tootfinvoice, "</SellerCommunicationDetails>\n");

							fputs($tootfinvoice, "<SellerInformationDetails>\n");
							xml_add("SellerHomeTownName",												$yhtiorow['kotipaikka'],								$tootfinvoice);
							xml_add("SellerPhoneNumber", 												$yhtiorow['puhelin'], 									$tootfinvoice);
							xml_add("SellerFaxNumber", 													$yhtiorow['fax'], 										$tootfinvoice);
							xml_add("SellerCommonEmailaddressIdentifier",								$yhtiorow['email'], 									$tootfinvoice);
							xml_add("SellerWebaddressIdentifier",										$yhtiorow['www'],   									$tootfinvoice);
							xml_add("SellerFreeText",   		                     					$yhtiorow['laskun_vapaakentta'],      					$tootfinvoice);

							fputs($tootfinvoice, "<SellerAccountDetails>\n");
							xml_add('SellerAccountID IdentificationSchemeName="IBAN"',					$yhtiorow['pankkiiban1'],								$tootfinvoice);
							xml_add('SellerBic IdentificationSchemeName="BIC"',							$yhtiorow['pankkiswift1'],								$tootfinvoice);
							fputs($tootfinvoice, "</SellerAccountDetails>\n");

							if ($yhtiorow['pankkiiban2']!='') {
								fputs($tootfinvoice, "<SellerAccountDetails>\n");
								xml_add('SellerAccountID IdentificationSchemeName="IBAN"',				$yhtiorow['pankkiiban2'],       						$tootfinvoice);
								xml_add('SellerBic IdentificationSchemeName="BIC"',						$yhtiorow['pankkiswift2'],      						$tootfinvoice);
								fputs($tootfinvoice, "</SellerAccountDetails>\n");
							}
							if ($yhtiorow['pankkiiban3']!='') {
								fputs($tootfinvoice, "<SellerAccountDetails>\n");
								xml_add('SellerAccountID IdentificationSchemeName="IBAN"',				$yhtiorow['pankkiiban3'],       						$tootfinvoice);
								xml_add('SellerBic IdentificationSchemeName="BIC"',						$yhtiorow['pankkiswift3'],     							$tootfinvoice);
								fputs($tootfinvoice, "</SellerAccountDetails>\n");
							}


							fputs($tootfinvoice, "<InvoiceRecipientDetails>\n");
							xml_add("InvoiceRecipientAddress",											$yhtiorow['pankkiiban1'],		  						$tootfinvoice);
							xml_add("InvoiceRecipientIntermediatorAddress",								$yhtiorow['pankkiswift1'],                 				$tootfinvoice);
							fputs($tootfinvoice, "</InvoiceRecipientDetails>\n");

							fputs($tootfinvoice, "</SellerInformationDetails>\n");

							fputs($tootfinvoice, "<BuyerPartyDetails>\n");
							xml_add("BuyerPartyIdentifier",				  								ymuuta($lasrow['ytunnus']), 							$tootfinvoice);
							xml_add("BuyerOrganisationName",                     						$lasrow['nimi'],    									$tootfinvoice);
							xml_add("BuyerOrganisationTaxCode",                     					$asiakarow['maa']."".ymuuta($lasrow['ytunnus']),    	$tootfinvoice);
							//xml_add("BuyerOrganisationUnitNumber",                    				$asiakasrow['ovttunnus'],    							$tootfinvoice);

							fputs($tootfinvoice, "<BuyerPostalAddressDetails>\n");
							xml_add("BuyerStreetName",													$lasrow['osoite'],     									$tootfinvoice);
							xml_add("BuyerTownName",                                  					$lasrow['postitp'],      								$tootfinvoice);
							xml_add("BuyerPostCodeIdentifier",                         					$lasrow['postino'],     								$tootfinvoice);
							fputs($tootfinvoice, "</BuyerPostalAddressDetails>\n");

							fputs($tootfinvoice, "</BuyerPartyDetails>\n");

							fputs($tootfinvoice, "<DeliveryPartyDetails>\n");

							fputs($tootfinvoice, "<DeliveryPartyIdentifier/>\n");
							xml_add("DeliveryOrganisationName",                            				$lasrow['toim_nimi'],     								$tootfinvoice);

							fputs($tootfinvoice, "<DeliveryPostalAddressDetails>\n");
							xml_add("DeliveryStreetName",                             					$lasrow['toim_osoite'],     							$tootfinvoice);
							xml_add("DeliveryTownName",                                 				$lasrow['toim_postitp'],     							$tootfinvoice);
							xml_add("DeliveryPostCodeIdentifier",                     					$lasrow['toim_postino'],								$tootfinvoice);
							fputs($tootfinvoice, "</DeliveryPostalAddressDetails>\n");

							fputs($tootfinvoice, "</DeliveryPartyDetails>\n");

							fputs($tootfinvoice, "<DeliveryDetails>\n");
							xml_add('DeliveryDate Format="CCYYMMDD"',                   				dateconv($lasrow['toimaika']),							$tootfinvoice);
							xml_add("DeliveryMethodText",                                     			$lasrow['toimitustapa'],          						$tootfinvoice);
							fputs($tootfinvoice, "</DeliveryDetails>\n");

							fputs($tootfinvoice, "<InvoiceDetails>\n");
							//tsekataan laskun tyyppikoodi INV
							if ($lasrow['arvo']>=0)	{
								xml_add("InvoiceTypeCode",												"INV01",      		      								$tootfinvoice);
								xml_add("InvoiceTypeText",												"LASKU",      		      								$tootfinvoice);
							}
							else {
								xml_add("InvoiceTypeCode",												"INV02",      		      								$tootfinvoice);
								xml_add("InvoiceTypeText",												"HYVITYSLASKU",      		      						$tootfinvoice);
							}

							xml_add("OriginCode",              	                   						"Original",                  					 		$tootfinvoice);
							xml_add("InvoiceNumber",       	 	                     					$lasrow['laskunro'],  									$tootfinvoice);
							xml_add('InvoiceDate Format="CCYYMMDD"',									dateconv($lasrow['tapvm']),								$tootfinvoice);
							xml_add("OrderIdentifier",													$lasrow['tunnus'],										$tootfinvoice);
							xml_add("InvoiceTotalVatExcludedAmount AmountCurrencyIdentifier=\"$val\"", 	pp($lasrow['arvo']),									$tootfinvoice);
							xml_add("InvoiceTotalVatAmount AmountCurrencyIdentifier=\"$val\"",			pp(round($lasrow['summa']-$lasrow['arvo'], 2)),			$tootfinvoice);
							xml_add("InvoiceTotalVatIncludedAmount AmountCurrencyIdentifier=\"$val\"",	pp($lasrow['summa']),									$tootfinvoice);

						}
						elseif($silent == "") {
							$tulos_ulos .= t("Finvoiceaineistoa ei voitu luoda. Yhtiolta puuttuu ovttunnus, SWIFT tai IBAN tunnus. Tulosta lasku manuaalisesti!")." ".$lasrow["laskunro"]."<br>\n<br>\n";
						}
					}
					else {

						$fstat = fstat($tootxml);

						if ($fstat["size"] == 0) {
							//tehd‰‰n verkkolasku oliot
							fputs($tootxml, "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n");
							fputs($tootxml, "<Pupevoice Version=\"0.99\">\n");
						}

						// Kirjotetaan laskun tietoja pupevoice XML-muotoon
						fputs($tootxml, "<Invoice>\n");
						fputs($tootxml, "<CHN>\n");

						xml_add("Paper", 								substr($lasrow['chn'], 0, 1), 	$tootxml);
						xml_add("Einvoice", 							substr($lasrow['chn'], 1, 1), 	$tootxml);
						xml_add("Edi", 									substr($lasrow['chn'], 2, 1), 	$tootxml);
						xml_add("Email", 								$yhtiorow['admin_email'],		$tootxml);

						if (strtoupper($yhtiorow['kieli']) == 'SE' or $kieli == "SE") {
							xml_add("Language",							"SV",							$tootxml);
						}

						fputs($tootxml, "</CHN>\n");
						fputs($tootxml, "<SellerPartyInformation>\n");

						xml_add("SellerPartyIdentifier", 				$yhtiorow['ovttunnus'],		$tootxml);
						xml_add("SellerPartyDomicile", 					$yhtiorow['kotipaikka'], 	$tootxml);
						xml_add("SellerOrganisationName", 				$yhtiorow['nimi'], 			$tootxml);
						xml_add("SellerStreetName", 					$yhtiorow['osoite'], 		$tootxml);
						xml_add("SellerPostCode", 						$yhtiorow['postino'], 		$tootxml);
						xml_add("SellerTownName", 						$yhtiorow['postitp'], 		$tootxml);
						xml_add("SellerCountryName", 					$yhtiorow['maakoodi'],		$tootxml);
						xml_add("SellerPhoneNumber", 					$yhtiorow['puhelin'], 		$tootxml);
						xml_add("SellerFaxNumber", 						$yhtiorow['fax'], 			$tootxml);

						if ($masrow["factoring"] == "") {
							xml_add("SellerAccountName1", 				$yhtiorow['pankkinimi1'],	$tootxml);
							xml_add("SellerAccountID1", 				$yhtiorow['pankkitili1'],	$tootxml);
							xml_add("SellerAccountName2", 				$yhtiorow['pankkinimi2'],	$tootxml);
							xml_add("SellerAccountID2", 				$yhtiorow['pankkitili2'],	$tootxml);
							xml_add("SellerAccountName3", 				$yhtiorow['pankkinimi3'],	$tootxml);
							xml_add("SellerAccountID3", 				$yhtiorow['pankkitili3'],	$tootxml);
						}
						else {
							xml_add("SellerAccountName1", 				$yhtiorow['factoring_pankkinimi1'],	$tootxml);
							xml_add("SellerAccountID1", 				$yhtiorow['factoring_pankkitili1'],	$tootxml);
							xml_add("SellerAccountName2", 				$yhtiorow['factoring_pankkinimi2'],	$tootxml);
							xml_add("SellerAccountID2", 				$yhtiorow['factoring_pankkitili2'],	$tootxml);
							xml_add("SellerAccountName3", 				$yhtiorow['factoring_pankkinimi3'],	$tootxml);
							xml_add("SellerAccountID3", 				$yhtiorow['factoring_pankkitili3'],	$tootxml);
						}

						xml_add("SellerVatRegistrationText", 			t("Alv.Rek"), 		$tootxml);
						xml_add("SellerContactPerson", 					$myyrow['nimi'],	$tootxml);

						fputs($tootxml, "</SellerPartyInformation>\n");
						fputs($tootxml, "<InvoiceDetails>\n");

						xml_add("InvoiceType",							$tyyppi, 										$tootxml);
						xml_add("InvoicedPartyIdentifier",				$lasrow['ytunnus'], 							$tootxml);
						xml_add("InvoicedPartyOVT",						$lasrow['ovttunnus'],							$tootxml);
						xml_add("InvoicedPartyEBA",						$lasrow['verkkotunnus'], 						$tootxml);
						xml_add("InvoicedOrganisationName",				"$lasrow[nimi] $lasrow[nimitark]", 				$tootxml);
						xml_add("InvoicedStreetName",					$lasrow['osoite'], 								$tootxml);
						xml_add("InvoicedPostCode",						$lasrow['postino'],								$tootxml);
						xml_add("InvoicedTownName",						$lasrow['postitp'],								$tootxml);
						xml_add("InvoicedCountryName",					$lasrow['maa'], 								$tootxml);
						xml_add("InvoiceNumber",						$lasrow['laskunro'],							$tootxml);
						xml_add("InvoiceCurrency",						$yhtiorow['valkoodi'],							$tootxml);
						xml_add("InvoicePaymentReference",				$lasrow['viite'], 								$tootxml);
						xml_add("InvoiceDate",							dateconv($lasrow['tapvm']), 					$tootxml);
						xml_add("OrderIdentifier",						'', 											$tootxml); //Ostajan tilausnumero, eii oo viel‰ olemassa
						xml_add("DeliveredPartyName",					"$lasrow[toim_nimi] $lasrow[toim_nimitark]",	$tootxml);
						xml_add("DeliveredPartyStreetName",				$lasrow['toim_osoite'], 						$tootxml);
						xml_add("DeliveredPartyPostCode",				$lasrow['toim_postino'], 						$tootxml);
						xml_add("DeliveredPartyTownName",				$lasrow['toim_postitp'], 						$tootxml);
						xml_add("DeliveredPartyCountryName",			$lasrow['toim_maa'], 							$tootxml);
						xml_add("DeliveredPartyOVT",					$lasrow['toim_ovttunnus'], 						$tootxml);
						xml_add("DueDate",								dateconv($lasrow['erpcm']),						$tootxml);
						xml_add("InvoiceTotalVatExcludedAmount",		$lasrow['arvo'],								$tootxml);
						xml_add("InvoiceTotalVatAmount",				round($lasrow['summa']-$lasrow['arvo'], 2),		$tootxml);
						xml_add("InvoiceTotalVatIncludedAmount",		$lasrow['summa'],								$tootxml);
						xml_add("PaymentTerms",							$masrow['teksti']." ".$masrow['kassa_teksti'],	$tootxml);
						xml_add("PaymentOverDueFinePercent",			$lasrow['viikorkopros'], 						$tootxml);
						xml_add("InvoiceDeliveryMethod",				$lasrow['toimitustapa'], 						$tootxml);

						//Laitetaan kassa-alennustietoja
						xml_add("CashDiscountDate",						dateconv($lasrow['kapvm']), 					$tootxml);
						xml_add("CashDiscountBaseAmount",				$lasrow['summa'],			 					$tootxml);
						xml_add("CashDiscountPercent",					$masrow['kassa_alepros'],	 					$tootxml);
						xml_add("CashDiscountAmount",					$lasrow['kasumma'],			 					$tootxml);
						xml_add("InvoiceReferenceFreeText",				$lasrow['viesti'],								$tootxml);
						xml_add("InvoiceFreeText",						$laskunkommentit,								$tootxml);
					}


					// Tarvitaan rivien eri verokannat
					$query = "	select alv, round(sum(rivihinta),2), round(sum(alv/100*rivihinta),2)
								from tilausrivi
								where yhtio='$kukarow[yhtio]' and otunnus in ($tunnukset) group by alv";
					$alvres = mysql_query($query) or pupe_error($query);

					while ($alvrow = mysql_fetch_array($alvres)) {
						// Kirjotetaan failiin arvierittelyt
						if ($lasrow["chn"] == "111") {
							fputs($tootedi, "ITAXVATTX:$alvrow[0]\n");
							fputs($tootedi, "ITAXVATMA:125@$alvrow[1]\n");
							fputs($tootedi, "ITAXVATMA:150@$alvrow[2]\n");
						}
						elseif($yhtiorow["verkkolasku_lah"] != "") {
							fputs($tootfinvoice, "<VatSpecificationDetails>\n");
							xml_add("VatBaseAmount AmountCurrencyIdentifier=\"$val\"",	pp($alvrow[1]), 				$tootfinvoice);
							xml_add("VatRatePercent",									pp($alvrow[0]), 				$tootfinvoice);
							xml_add("VatRateAmount AmountCurrencyIdentifier=\"$val\"",	pp($alvrow[2]), 				$tootfinvoice);
							fputs($tootfinvoice, "</VatSpecificationDetails>\n");
						}
						else {
							fputs($tootxml, "<VatSpecificationDetails>\n");
							xml_add("VatRatePercent",					$alvrow[0], $tootxml);
							xml_add("VatBaseAmount",					$alvrow[1], $tootxml);
							xml_add("VatRateAmount",					$alvrow[2], $tootxml);
							fputs($tootxml, "</VatSpecificationDetails>\n");
						}
					}

					if ($lasrow["chn"] == "111") {
						//Otsikko on saatu valmiiksi
						fputs($tootedi, "IHDR__END:".substr($lasrow["tunnus"], -6)."\n");
					}
					elseif($yhtiorow["verkkolasku_lah"] != "") {
						// finvoicelle pit‰‰ viel‰ kirjoittaa jaddajadda
						fputs($tootfinvoice, "<PaymentTermsDetails>\n");
						xml_add("PaymentTermsFreeText",       											pp($masrow['teksti']." ".$masrow['kassa_teksti']),		$tootfinvoice);
						xml_add('InvoiceDueDate Format="CCYYMMDD"',               						pp(dateconv($lasrow['tapvm'])),   	       				$tootfinvoice);

						fputs($tootfinvoice, "<PaymentOverDueFineDetails>\n");
						xml_add("PaymentOverDueFineFreeText",											"viiv‰styskorko ".pp($lasrow['viikorkopros']),				$tootfinvoice);
						xml_add("PaymentOverDueFinePercent",                 						 	pp($lasrow['viikorkopros']),   							$tootfinvoice);
						fputs($tootfinvoice, "</PaymentOverDueFineDetails>\n");

						fputs($tootfinvoice, "</PaymentTermsDetails>\n");

						fputs($tootfinvoice, "</InvoiceDetails>\n");

						fputs($tootfinvoice, "<PaymentStatusDetails>\n");
						xml_add("PaymentStatusCode", 													"NOTPAID", 												$tootfinvoice);
						fputs($tootfinvoice, "</PaymentStatusDetails>\n");
					}

					// Kirjoitetaan rivitietoja tilausriveilt‰
					$query = "	select *
								from tilausrivi
								where yhtio='$kukarow[yhtio]' and otunnus in ($tunnukset) and kpl<>0
								order by otunnus, hyllyalue, hyllynro, hyllyvali, hyllytaso, tuoteno, tunnus";
					$tilres = mysql_query($query) or pupe_error($query);

					$rivinumero = 1;

					while ($tilrow = mysql_fetch_array($tilres)) {
						$vatamount = round($tilrow['rivihinta']*$tilrow['alv']/100, 2);
						$totalvat  = round($tilrow['rivihinta']+$vatamount, 2);

						if ($lasrow["chn"] == "111") {
							$query = "	select eankoodi
										from tuote
										where yhtio='$kukarow[yhtio]' and tuoteno='$tilrow[tuoteno]'";
							$eanres = mysql_query($query) or pupe_error($query);
							$eanrow = mysql_fetch_array($eanres);

							fputs($tootedi, "ILINSTART:$rivinumero\n");
							fputs($tootedi, "ILINEN_NU:$eanrow[eankoodi]\n");
							fputs($tootedi, "ILINMF_PI:".sprintf("%-35.35s", $tilrow['tuoteno'])."@5\n");
							fputs($tootedi, "ILIN8__IF:       @   @   @   @".sprintf("%-35.35s", $tilrow['nimitys'])."\n");
							fputs($tootedi, "ILIN47_QT:".sprintf("%017.2f", $tilrow["kpl"])."@$tilrow[yksikko]\n");
							fputs($tootedi, "ILININVIV:".sprintf("%017.2f", $tilrow["hinta"])."@   @   @   @          1@PCE\n");
							fputs($tootedi, "ILIN203MA:$tilrow[rivihinta]\n");
							fputs($tootedi, "ILIN___PA:$tilrow[ale]\n");
							fputs($tootedi, "ILINVATTX:$tilrow[alv]\n");
							fputs($tootedi, "ILIN__END:$rivinumero\n");
						}
						elseif($yhtiorow["verkkolasku_lah"] != "") {
							fputs($tootfinvoice, "<InvoiceRow>\n");
							xml_add("ArticleIdentifier",                                   			$tilrow['tuoteno'],         	$tootfinvoice);
							xml_add("ArticleName",                                    				$tilrow['nimitys'],  			$tootfinvoice);
							xml_add("DeliveredQuantity QuantityUnitCode=\"$tilrow[yksikko]\"",		pp($tilrow['kpl']),      		$tootfinvoice);
							xml_add("UnitPriceAmount AmountCurrencyIdentifier=\"$val\"",  			pp($tilrow['hinta']),        	$tootfinvoice);

							if($tilrow["tilaajanrivinro"] != 0) {
								xml_add("RowIdentifier",                                    		$tilrow['tilaajanrivinro'], 	$tootfinvoice); // t‰nne laitetaan asiakkaan rivinumero, niin saavat parseroida senkin laskuista
							}
							if ($tilrow['kommentti']!='') {
								xml_add("RowFreeText",    											$tilrow['kommentti'],			$tootfinvoice);
							}

							xml_add("RowVatRatePercent",                                    		pp($tilrow['alv']),           	$tootfinvoice);
							xml_add("RowVatAmount AmountCurrencyIdentifier=\"$val\"",          		pp($vatamount),      			$tootfinvoice);	// veron m‰‰r‰
							xml_add("RowVatExcludedAmount AmountCurrencyIdentifier=\"$val\"",  		pp($tilrow['rivihinta']),      	$tootfinvoice);	// veroton rivihinta
							xml_add("RowAmount AmountCurrencyIdentifier=\"$val\"",             		pp($totalvat),     				$tootfinvoice);	// verollinen rivihinta
							fputs($tootfinvoice, "</InvoiceRow>\n");

						}
						else {
							fputs($tootxml, "<Row>\n");
							xml_add("OrderIdentifier",					$tilrow['otunnus'], 			$tootxml); // t‰m‰ on tilauksen numero meill‰, ei kuulu standardiin mutta se on pakko lis‰t‰
							xml_add("ArticleIdentifier",				$tilrow['tuoteno'], 			$tootxml);
							xml_add("ArticleName",						$tilrow['nimitys'], 			$tootxml);
							xml_add("DeliveredQuantity",				$tilrow['kpl'], 				$tootxml);
							xml_add("DeliveredQuantityUnitCode",		$tilrow['yksikko'],				$tootxml);
							xml_add("DeliveryDate",						dateconv($tilrow['toimaika']),	$tootxml);
							xml_add("UnitPrice",						$tilrow['hinta'], 				$tootxml);
							xml_add("RowIdentifier",					$tilrow['tilaajanrivinro'], 	$tootxml); // t‰nne laitetaan asiakkaan rivinumero, niin saavat parseroida senkin laskuista
							xml_add("RowDiscountPercent",				$tilrow['ale'], 				$tootxml);
							xml_add("RowVatRatePercent",				$tilrow['alv'], 				$tootxml);
							xml_add("RowVatAmount",						$vatamount, 					$tootxml); // veron m‰‰r‰
							xml_add("RowTotalVatExcludedAmount",		$tilrow['rivihinta'],		 	$tootxml); // veroton rivihinta
							xml_add("RowTotalVatIncludedAmount",		$totalvat, 						$tootxml); // verollinen rivihinta
							xml_add("RowFreeText",						$tilrow['kommentti'],		 	$tootxml);
							fputs($tootxml, "</Row>\n");
						}
						$rivinumero++;
					}


					if ($lasrow["chn"] == "111") {
						fputs($tootedi, "IMSG__END:".substr($lasrow["tunnus"], -6)."\n");
						$edilask++;
					}
					elseif($yhtiorow["verkkolasku_lah"] != "") {
						fputs($tootfinvoice, "<EpiDetails>\n");

						fputs($tootfinvoice, "<EpiIdentificationDetails>\n");
						xml_add('EpiDate Format="CCYYMMDD"',                   							dateconv($lasrow['tapvm']),		$tootfinvoice);
						xml_add("EpiReference",                               							$lasrow['viite'],				$tootfinvoice);
						fputs($tootfinvoice, "</EpiIdentificationDetails>\n");

						fputs($tootfinvoice, "<EpiPartyDetails>\n");

						fputs($tootfinvoice, "<EpiBfiPartyDetails>\n");
						xml_add('EpiBfiIdentifier IdentificationSchemeName="BIC"',						$yhtiorow['pankkiswift1'],		$tootfinvoice);
						fputs($tootfinvoice, "</EpiBfiPartyDetails>\n");

						fputs($tootfinvoice, "<EpiBeneficiaryPartyDetails>\n");
						xml_add("EpiNameAddressDetails",    											$yhtiorow['nimi'],				$tootfinvoice);
						xml_add("EpiBei",                         										ymuuta($yhtiorow['ytunnus']),  	$tootfinvoice);
						xml_add("EpiAccountID IdentificationSchemeName=\"BBAN\"",						$yhtiorow['pankkitili1'],		$tootfinvoice);
						fputs($tootfinvoice, "</EpiBeneficiaryPartyDetails>\n");

						fputs($tootfinvoice, "</EpiPartyDetails>\n");

						fputs($tootfinvoice, "<EpiPaymentInstructionDetails>\n");
						xml_add("EpiRemittanceInfoIdentifier IdentificationSchemeName=\"SPY\"",			spyconv($lasrow['viite']),    	$tootfinvoice);
						xml_add("EpiInstructedAmount AmountCurrencyIdentifier=\"$valuutta\"", 			pp($lasrow['summa']),         	$tootfinvoice);
						xml_add("EpiCharge ChargeOption=\"SHA\"", 										"SHA",							$tootfinvoice);
						xml_add('EpiDateOptionDate Format="CCYYMMDD"',                					dateconv($lasrow['erpcm']),		$tootfinvoice);
						fputs($tootfinvoice, "</EpiPaymentInstructionDetails>\n");

						fputs($tootfinvoice, "</EpiDetails>\n");

						fputs($tootfinvoice, "</Finvoice>\n");
					}
					else {
						fputs($tootxml, "</InvoiceDetails>\n");
						fputs($tootxml, "</Invoice>\n");
					}

					// Otetaan talteen jokainen laskunumero joka l‰hetet‰‰n jotta voidaan tulostaa paperilaskut
					$tulostettavat[] = $lasrow["laskunro"];
					$lask++;
				} // end if lasrow.summa != 0
				elseif ($lasrow["sisainen"] != '') {
					$tulos_ulos .= "<br>\n".t("Tehtiin sis‰inen lasku")."! $lasrow[laskunro] $lasrow[nimi]<br>\n";
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
					$tulos_ulos .= "<br>\n".t("Nollasummaista laskua ei l‰hetetty")."! $lasrow[laskunro] $lasrow[nimi]<br>\n";
				}

				// p‰ivitet‰‰n kaikki laskut l‰hetetyiksi...
				$tquery = "UPDATE lasku SET alatila='X' WHERE (tunnus in ($tunnukset) or tunnus='$lasrow[tunnus]') and yhtio='$kukarow[yhtio]'";
				$tresult = mysql_query($tquery) or pupe_error($tquery);

			} // end foreach ketjut...

			if ($silent == "") {
				$tulos_ulos .= "<br><br>\n\n";
			}

			//Lopput‰git mukaan, paitsi jos failit on tyhji‰
			$fstat = fstat($tootedi);

			if ($fstat["size"] > 0) {
				fputs($tootedi, "ICHG__END:$timestamppi\n");
			}

			$fstat = fstat($tootxml);

			if ($fstat["size"] > 0) {
				fputs($tootxml, "</Pupevoice>\n");
			}
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

		// jos laskutettiin jotain
		if ($lask > 0) {

			if ($silent == "" or $silent == "VIENTI") {
				$tulos_ulos .= t("L‰hetettiin")." $lask ".t("laskua").".<br>\n";
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
			elseif($silent == "") {
				$tulos_ulos .= t("Verkkolaskuja ei l‰hetetty Elma EDI-inhouse muodossa")."!<br>\n";
			}

			// jos yhtiˆll‰ on laskuprintteri on m‰‰ritelty tai halutaan jostain muusta syyst‰ tulostella laskuja paperille
			if ($yhtiorow['lasku_tulostin'] != 0 or $valittu_tulostin != "") {

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
						$laskujarj = 'otunnus, hyllyalue, hyllynro, hyllyvali, hyllytaso, tuoteno';
					}
					else {
						require_once("tulosta_lasku_plain.inc");
						$laskujarj = 'otunnus, tilaajanrivinro, tunnus';
					}

					// haetaan tilauksen kaikki rivit
					$query = "	SELECT *
								FROM tilausrivi
								WHERE uusiotunnus='$laskurow[tunnus]' and yhtio='$kukarow[yhtio]'
								ORDER BY $laskujarj";
					$result = mysql_query($query) or pupe_error($query);

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
					if($yhtiorow["lasku_tulostin"] == "AUTOMAAGINEN_VALINTA") {
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

					// itse print komento...
					$line = exec("$kirow[komento] $pdffilenimi");

					if($valittu_kopio_tulostin != '') {
						$querykieli = "	select *
										from kirjoittimet
										where yhtio='$kukarow[yhtio]' and tunnus='$valittu_kopio_tulostin'";
						$kires = mysql_query($querykieli) or pupe_error($querykieli);
						$kirow = mysql_fetch_array($kires);

						$tulos_ulos .= t("Laskukopio tulostuu kirjoittimelle").": $kirow[kirjoitin]<br>\n";

						// itse print komento...
						$line = exec("$kirow[komento] $pdffilenimi");
					}


					//poistetaan tmp file samantien kuleksimasta...
					system("rm -f $pdffilenimi");


					if ($laskurow["vienti"] == "K" and $hyvitys == "EI") {

						$uusiotunnus = $laskurow["tunnus"];

						require('tulosta_sadvientiilmo.inc');

						//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
						list($usec, $sec) = explode(' ', microtime());
						mt_srand((float) $sec + ((float) $usec * 100000));
						$pdffilenimi = "/tmp/SAD_Lomake_Kopio-".md5(uniqid(mt_rand(), true)).".pdf";

						//kirjoitetaan pdf faili levylle..
						$fh = fopen($pdffilenimi, "w");
						if (fwrite($fh, $pdf2->generate()) === FALSE) die("PDF kirjoitus ep‰onnistui $pdffilenimi");
						fclose($fh);

						//itse print komento
						$line = exec("$kirow[komento] $pdffilenimi");

						//poistetaan tmp file samantien kuleksimasta...
						system("rm -f $pdffilenimi");

						$tulos_ulos .= t("SAD-lomake tulostuu")."...<br>\n";
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

						//itse print komento
						$line = exec("$kirow[komento] $pdffilenimi");

						//poistetaan tmp file samantien kuleksimasta...
						system("rm -f $pdffilenimi");

						$tulos_ulos .= t("Vientierittely tulostuu")."...<br>\n";
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
			$tulos_ulos .= t("Sinulla ei ollut yht‰‰n laskua siirrett‰v‰n‰!")."<br>\n";
		}

		$query = "UNLOCK TABLES";
		$locre = mysql_query($query) or pupe_error($query);

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
					sum(if(laskutusvkopv='0',1,0)) normaali,
					sum(if(laskutusvkopv='$today',1,0)) paiva,
					sum(if(laskutusvkopv!='$today' and laskutusvkopv!='0',1,0)) muut,
					count(*) kaikki
					from lasku
					where yhtio	= '$kukarow[yhtio]'
					and tila	= 'L'
					and alatila	= 'D'
					and viite	= ''";
		$res = mysql_query($query) or pupe_error($query);
		$row = mysql_fetch_array($res);


		echo "<form action = '$PHP_SELF' method = 'post' name='lasku' onSubmit = 'return verify()'>
			<input type='hidden' name='tee' value='TARKISTA'>";


		echo "<tr><th>".t("Laskutettavia tilauksia joilla on laskutusviikonp‰iv‰ t‰n‰‰n").":</th><td colspan='3'>$row[paiva]</td></tr>\n";
		echo "<tr><th>".t("Laskutettavia tilauksia joiden laskutusviikonp‰iv‰ ei ole t‰n‰‰n").":</th><td colspan='3'>$row[muut]</td></tr>\n";
		echo "<tr><th>".t("Laskutettavia tilauksia joilla EI ole laskutusviikonp‰iv‰‰").":</th><td colspan='3'>$row[normaali]</td></tr>\n";
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
