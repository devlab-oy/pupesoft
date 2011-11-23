<?php

	// verkkolasku.php
	//
	// tarvitaan $kukarow ja $yhtiorow
	//
	// laskutetaan kaikki/valitut toimitetut tilaukset
	// l�hetet��n kaikki laskutetut tilaukset operaattorille tai tulostetaan ne paperille
	//
	// $laskutettavat   --> jos halutaan laskuttaa vain tietyt tilaukset niin silloin ne tulee muuttujassa
	// $laskutakaikki   --> muutujan avulla voidaan ohittaa laskutusviikonp�iv�t
	// $eiketjut        --> muuttujan avulla katotaan halutaanko laskuttaa vaan laskut joita *EI SAA* ketjuttaa
	//
	// jos ollaan saatu komentorivilt� parametrej�
	// $yhtio ja $kieli --> komentorivilt� pit�� tulla parametrein�
	// $eilinen         --> optional parametri on jollon ajetaan laskutus eiliselle p�iv�lle
	//
	// $silent muuttujalla voidaan hiljent�� kaikki outputti

	//jos chn = 999 se tarkoittaa ett� lasku on laskutuskiellossa eli ei saa k�sitell� t��ll�!!!!!

	//$silent = '';

	// Kutsutaanko CLI:st�
	$php_cli = FALSE;

	if (php_sapi_name() == 'cli' or isset($editil_cli)) {
		$php_cli = TRUE;
	}

	if ($php_cli) {

		if (!isset($argv[1]) or $argv[1] == '') {
			echo "Anna yhti�!!!\n";
			die;
		}

		// otetaan includepath aina rootista
		ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(__FILE__)).PATH_SEPARATOR."/usr/share/pear");
		error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
		ini_set("display_errors", 0);

		// otetaan tietokanta connect
		require("inc/connect.inc");
		require("inc/functions.inc");

		// hmm.. j�nn��
		$kukarow['yhtio'] = $argv[1];

		if (isset($argv[2])) {
			$kieli = $argv[2];
		}

		$kukarow['kuka']  = "crond";

		//Pupeasennuksen root
		$pupe_root_polku = dirname(dirname(__FILE__));

		$query    = "SELECT * from yhtio where yhtio='$kukarow[yhtio]'";
		$yhtiores = pupe_query($query);

		if (mysql_num_rows($yhtiores) == 1) {
			$yhtiorow = mysql_fetch_assoc($yhtiores);

			// haetaan yhti�n parametrit
			$query = "  SELECT *
						FROM yhtion_parametrit
						WHERE yhtio='$yhtiorow[yhtio]'";
			$result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

			if (mysql_num_rows($result) == 1) {
				$yhtion_parametritrow = mysql_fetch_assoc($result);
				// lis�t��n kaikki yhtiorow arrayseen, niin ollaan taaksep�inyhteensopivia
				foreach ($yhtion_parametritrow as $parametrit_nimi => $parametrit_arvo) {
					$yhtiorow[$parametrit_nimi] = $parametrit_arvo;
				}
			}

			$laskkk = "";
			$laskpp = "";
			$laskvv = "";
			$eiketjut = "";

			// jos komentorivin kolmas arg on "eilinen" niin edelliselle laskutus p�iv�lle
			if ($argv[3] == "eilinen") {
				$laskkk = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
				$laskpp = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
				$laskvv = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
			}

			// jos komentorivin kolmas arg on "eiketjut"
			if ($argv[3] == "eiketjut") {
				$eiketjut = "KYLLA";
			}

			$tee = "TARKISTA";
		}
		else {
			die ("Yhti� $kukarow[yhtio] ei l�ydy!");
		}
	}
	elseif (strpos($_SERVER['SCRIPT_NAME'], "verkkolasku.php") !== FALSE) {

		if (isset($_POST["tee"])) {
			if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto = 1;
			if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
		}

		require("../inc/parametrit.inc");
	}

	if (isset($tee) and $tee == "lataa_tiedosto") {
		readfile("$pupe_root_polku/dataout/".basename($filenimi));
		exit;
	}
	else {

		//Nollataan muuttujat
		$tulostettavat       = array();
		$tulostettavat_apix  = array();
		$tulostettavat_email = array();
		$tulos_ulos          = "";

		$verkkolaskuputkeen_pupevoice  	= array();
		$verkkolaskuputkeen_finvoice  	= array();
		$verkkolaskuputkeen_suora 		= array();
		$verkkolaskuputkeen_elmaedi 	= array();
		$verkkolaskuputkeen_apix		= array();

		if (!isset($silent)) {
			$silent = "";
		}
		if (!isset($tee)) {
			$tee = "";
		}
		if (!isset($kieli)) {
			$kieli = "";
		}

		if ($silent == "") {
			$tulos_ulos .= "<font class='head'>".t("Laskutusajo")."</font><hr>\n";
		}

		if ($tee == 'TARKISTA') {

			$poikkeava_pvm = "";

			//sy�tetty p�iv�m��r�
			if ($laskkk != '' or $laskpp != '' or $laskvv != '') {

				// Korjataan vuosilukua
				if ($laskvv < 1000) $laskvv += 2000;

				// Katotaan ensin, ett� se on ollenkaan validi
				if (checkdate($laskkk, $laskpp, $laskvv)) {

					//vertaillaan tilikauteen
					list($vv1,$kk1,$pp1) = explode("-",$yhtiorow["myyntireskontrakausi_alku"]);
					list($vv2,$kk2,$pp2) = explode("-",$yhtiorow["myyntireskontrakausi_loppu"]);

					$tilialku  = (int) date('Ymd',mktime(0,0,0,$kk1,$pp1,$vv1));
					$tililoppu = (int) date('Ymd',mktime(0,0,0,$kk2,$pp2,$vv2));
					$syotetty  = (int) date('Ymd',mktime(0,0,0,$laskkk,$laskpp,$laskvv));
					$tanaan    = (int) date('Ymd');

					if ($syotetty < $tilialku or $syotetty > $tililoppu) {
						$tulos_ulos .= "<br>\n".t("VIRHE: Sy�tetty p�iv�m��r� ei sis�lly kuluvaan tilikauteen!")."<br>\n<br>\n";
						$tee = "";
					}
					else {

						if ($syotetty > $tanaan) {
							//tulevaisuudessa ei voida laskuttaa
							$tulos_ulos .= "<br>\n".t("VIRHE: Sy�tetty p�iv�m��r� on tulevaisuudessa, ei voida laskuttaa!")."<br>\n<br>\n";
							$tee = "";
						}
						else {
							//homma on ok
							$poikkeava_pvm = $syotetty;

							//ohitetaan my�s laskutusviikonp�iv�t jos poikkeava p�iv�m��r� on sy�tetty
							$laskutakaikki = "ON";

							$tee = "LASKUTA";
						}
					}
				}
				else {
					$tulos_ulos .= "<br>\n".t("VIRHE: Sy�tetty p�iv�m��r� on virheellinen, tarkista se!")."<br>\n<br>\n";
					$tee = "";
				}
			}
			else {
				//poikkeavaa p�iv�m��r�� ei ole, eli laskutetaan
				$tee = "LASKUTA";
			}
		}

		if ($tee == "LASKUTA") {

			if (!function_exists("vlas_dateconv")) {
				function vlas_dateconv ($date) {
					//k��nt�� mysqln vvvv-kk-mm muodon muotoon vvvvkkmm
					return substr($date,0,4).substr($date,5,2).substr($date,8,2);
				}
			}

			//tehd��n viitteest� SPY standardia eli 20 merkki� etunollilla
			if (!function_exists("spyconv")) {
				function spyconv ($spy) {
					return $spy = sprintf("%020.020s",$spy);
				}
			}

			//pilkut pisteiksi
			if (!function_exists("pp")) {
				function pp ($muuttuja, $round="", $rmax="", $rmin="") {

					if (strlen($round)>0) {
						if (strlen($rmax)>0 and $rmax<$round) {
							$round = $rmax;
						}
						if (strlen($rmin)>0 and $rmin>$round) {
							$round = $rmin;
						}

						return $muuttuja = number_format($muuttuja, $round, ",", "");
					}
					else {
						return $muuttuja = str_replace(".",",", $muuttuja);
					}
				}
			}

			//Tiedostojen polut ja nimet
			//keksit��n uudelle failille joku varmasti uniikki nimi:
			$nimixml = "$pupe_root_polku/dataout/laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true)).".xml";

			//  Itellan iPost vaatii siirtoon v�h�n oman nimen..
			if ($yhtiorow["verkkolasku_lah"] == "iPost") {
				$nimiipost = "-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true))."_finvoice.xml";
				$nimifinvoice = "$pupe_root_polku/dataout/TRANSFER_IPOST".$nimiipost;
				$nimifinvoice_delivered = "DELIVERED_IPOST".$nimiipost;
			}
			elseif ($yhtiorow["verkkolasku_lah"] == "apix") {
				$nimifinvoice = "/tmp/laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true))."_finvoice.xml";
			}
			else {
				$nimifinvoice = "$pupe_root_polku/dataout/laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true))."_finvoice.xml";
			}

			$nimisisainenfinvoice = "$pupe_root_polku/dataout/laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true))."_sisainenfinvoice.xml";

			$nimiedi = "$pupe_root_polku/dataout/laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true)).".edi";

			//Pupevoice xml-dataa
			if (!$tootxml = fopen($nimixml, "w")) die("Filen $nimixml luonti ep�onnistui!");

			//Finvoice xml-dataa
			if (!$tootfinvoice = fopen($nimifinvoice, "w")) die("Filen $nimifinvoice luonti ep�onnistui!");

			//Elma-EDI-inhouse dataa (EIH-1.4.0)
			if (!$tootedi = fopen($nimiedi, "w")) die("Filen $nimiedi luonti ep�onnistui!");

			//Sis�inenfinvoice xml-dataa
			if (!$tootsisainenfinvoice = fopen($nimisisainenfinvoice, "w")) die("Filen $nimisisainenfinvoice luonti ep�onnistui!");

			// lock tables
			$query = "LOCK TABLES tili READ, lasku WRITE, tilausrivi WRITE, tilausrivi as t2 WRITE, yhtio READ, tilausrivi as t3 READ, tilausrivin_lisatiedot WRITE, tilausrivin_lisatiedot as tl2 WRITE, tilausrivin_lisatiedot as tlt2 WRITE, tilausrivin_lisatiedot as tlt3 WRITE, sanakirja WRITE, tapahtuma WRITE, tuotepaikat WRITE, tiliointi WRITE, toimitustapa READ, maksuehto READ, sarjanumeroseuranta WRITE, tullinimike READ, kuka WRITE, varastopaikat READ, tuote READ, rahtikirjat READ, kirjoittimet READ, tuotteen_avainsanat READ, tuotteen_toimittajat READ, asiakas READ, rahtimaksut READ, avainsana READ, avainsana as a READ, avainsana as b READ, avainsana as avainsana_kieli READ, factoring READ, pankkiyhteystiedot READ, yhtion_toimipaikat READ, yhtion_parametrit READ, tuotteen_alv READ, maat READ, laskun_lisatiedot WRITE, kassalipas READ, kalenteri WRITE, etaisyydet READ, tilausrivi as t READ, asiakkaan_positio READ, yhteyshenkilo as kk READ, yhteyshenkilo as kt READ, asiakasalennus READ, tyomaarays READ, dynaaminen_puu AS node READ, dynaaminen_puu AS parent READ, puun_alkio READ, asiakaskommentti READ, pakkaus READ, panttitili WRITE, lasku AS ux_otsikko WRITE, lasku AS lx_otsikko WRITE";
			$locre = pupe_query($query);

			//Haetaan tarvittavat funktiot aineistojen tekoa varten
			require("verkkolasku_elmaedi.inc");
			require("verkkolasku_finvoice.inc");
			require("verkkolasku_pupevoice.inc");

			// haetaan kaikki tilaukset jotka on toimitettu ja kuuluu laskuttaa t�n��n (t�t� resulttia k�ytet��n alhaalla lis��)
			$lasklisa = "";

			// tarkistetaan t�ss� tuleeko laskutusviikonp�iv�t ohittaa
			// ohitetaan jos ruksi on ruksattu tai poikkeava laskutusp�iv�m��r� on sy�tetty
			if (!isset($laskutakaikki) or $laskutakaikki == "") {

				// Mik� viikonp�iv� t�n��n on 1-7.. 1=sunnuntai, 2=maanantai, jne...
				$today = date("w") + 1;

				// Kuukauden eka p�iv�
				$eka_pv = laskutuspaiva("eka");

				// Kuukauden keskimm�inen p�iv�
				$keski_pv = laskutuspaiva("keski");

				// Kuukauden viimeinen p�iv�
				$vika_pv = laskutuspaiva("vika");

				$lasklisa .= " and (lasku.laskutusvkopv = 0 or
								   (lasku.laskutusvkopv = $today) or
								   (lasku.laskutusvkopv = -1 and curdate() = '$vika_pv') or
								   (lasku.laskutusvkopv = -2 and curdate() = '$eka_pv') or
								   (lasku.laskutusvkopv = -3 and curdate() = '$keski_pv') or
								   (lasku.laskutusvkopv = -4 and curdate() in ('$keski_pv','$vika_pv')) or
								   (lasku.laskutusvkopv = -5 and curdate() in ('$eka_pv','$keski_pv'))) ";
			}

			// katotaan halutaanko laskuttaa vaan laskut joita *EI SAA* ketjuttaa
			if (isset($eiketjut) and $eiketjut == "KYLLA") {
				$lasklisa .= " and lasku.ketjutus != '' ";
			}

			// laskutetaan vain tietyt tilausket
			if (isset($laskutettavat) and $laskutettavat != "") {
				$lasklisa .= " and lasku.tunnus in ($laskutettavat) ";
			}

			$tulos_ulos_maksusoppari = "";
			$tulos_ulos_sarjanumerot = "";

			// alustetaan muuttujia
			$laskutus_esto_saldot = array();

			// parametri, jolla voidaan est�� tilauksen laskutus, jos tilauksen yhdelt�kin tuotteelta saldo menee miinukselle
			if ($yhtiorow['saldovirhe_esto_laskutus'] == 'H') {

				$query = "  SELECT tilausrivi.tuoteno, sum(tilausrivi.varattu) varattu, group_concat(distinct lasku.tunnus) tunnukset
							FROM lasku
							JOIN tilausrivi on (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi = 'L' and tilausrivi.var not in ('P','J'))
							JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.ei_saldoa = '')
							WHERE lasku.yhtio = '$kukarow[yhtio]'
							and lasku.tila    = 'L'
							and lasku.alatila = 'D'
							and lasku.viite   = ''
							and lasku.chn    != '999'
							$lasklisa
							GROUP BY 1";
				$lasku_chk_res = pupe_query($query);

				while ($lasku_chk_row = mysql_fetch_assoc($lasku_chk_res)) {

					$query = "  SELECT sum(saldo) saldo
								FROM tuotepaikat
								WHERE yhtio = '$kukarow[yhtio]'
								AND tuoteno = '$lasku_chk_row[tuoteno]'";
					$saldo_chk_res = pupe_query($query);
					$saldo_chk_row = mysql_fetch_assoc($saldo_chk_res);

					if ($saldo_chk_row["saldo"] - $lasku_chk_row["varattu"] < 0) {
						$lasklisa .= " and lasku.tunnus not in ($lasku_chk_row[tunnukset]) ";
						$tulos_ulos .= "<br>\n".t("Saldovirheet").":<br>\n".t("Tilausta")." $lasku_chk_row[tunnukset] ".t("ei voida laskuttaa, koska tuotteen")." $lasku_chk_row[tuoteno] ".t("saldo ei riit�")."!<br>\n";
					}
				}
			}

			//haetaan kaikki laskutettavat tilaukset ja tehd��n maksuehtosplittaukset ja muita tarkistuksia jos niit� on
			$query = "  SELECT *
						FROM lasku
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila    = 'L'
						and lasku.alatila = 'D'
						and lasku.viite   = ''
						and lasku.chn    != '999'
						$lasklisa
						ORDER BY lasku.tunnus";
			$res = pupe_query($query);

			while ($laskurow = mysql_fetch_assoc($res)) {

				// Tsekataan maskuehto
				$query = "  SELECT tunnus
							FROM maksuehto
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus = '$laskurow[maksuehto]'
							and kaytossa = ''";
				$matsek = pupe_query($query);

				if (mysql_num_rows($matsek) == 0) {
					// Oho ei l�ytnyt, katotaan onko asiakkaalla oletus kunnossa?
					$query = "  SELECT asiakas.maksuehto
								FROM asiakas
								JOIN maksuehto ON asiakas.yhtio=maksuehto.yhtio and asiakas.maksuehto=maksuehto.tunnus and maksuehto.kaytossa=''
								WHERE asiakas.yhtio = '$kukarow[yhtio]'
								AND asiakas.tunnus = '$laskurow[liitostunnus]'";
					$matsek = pupe_query($query);

					if (mysql_num_rows($matsek) == 1) {
						$marow = mysql_fetch_assoc($matsek);

						$query = "  UPDATE lasku
									SET maksuehto = {$marow["maksuehto"]}
									WHERE tunnus = '$laskurow[tunnus]'";
						$updres = pupe_query($query);
					}
					else {
						// Jos tilauksella oli huono maksuehto, niin ei laskuteta
						$lasklisa .= " and lasku.tunnus != '$laskurow[tunnus]' ";

						$tulos_ulos .= "<br>\n".t("Maksuehtovirhe").":<br>\n".t("Tilausta")." $laskurow[tunnus] ".t("ei voida laskuttaa, koska maksuehto on virheellinen")."!<br>\n";
					}
				}

				// SALLITTAAN FIFO PERIAATTELLA SALDOJA
				if ($yhtiorow['saldovirhe_esto_laskutus'] == 'K') {

					// haetaan tilausriveilt� tuotenumero ja summataan varatut kappaleet
					$query = "  SELECT tilausrivi.tuoteno, sum(tilausrivi.varattu) varattu
								FROM tilausrivi
								JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.ei_saldoa = '')
								WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
								and tilausrivi.otunnus = '$laskurow[tunnus]'
								and tilausrivi.tyyppi  = 'L'
								and tilausrivi.var not in ('P','J')
								GROUP BY 1";
					$tuoteno_varattu_chk_res = pupe_query($query);

					while ($tuoteno_varattu_chk_row = mysql_fetch_assoc($tuoteno_varattu_chk_res)) {

						if (!isset($laskutus_esto_saldot[$tuoteno_varattu_chk_row['tuoteno']])) {

							// haetaan saldo tuotepaikalta
							$query = "  SELECT sum(tuotepaikat.saldo) saldo
										FROM tuotepaikat
										WHERE tuotepaikat.yhtio = '$kukarow[yhtio]'
										AND tuotepaikat.tuoteno = '$tuoteno_varattu_chk_row[tuoteno]'";
							$saldo_chk_res = pupe_query($query);
							$saldo_chk_row = mysql_fetch_assoc($saldo_chk_res);

							$laskutus_esto_saldot[$tuoteno_varattu_chk_row['tuoteno']] = $saldo_chk_row['saldo'];
						}

						if ($laskutus_esto_saldot[$tuoteno_varattu_chk_row['tuoteno']] - $tuoteno_varattu_chk_row['varattu'] < 0) {

							$lasklisa .= " and lasku.tunnus != '$laskurow[tunnus]' ";
							$tulos_ulos .= "<br>\n".t("Saldovirheet").":<br>\n".t("Tilausta")." $laskurow[tunnus] ".t("ei voida laskuttaa, koska tuotteen")." $tuoteno_varattu_chk_row[tuoteno] ".t("saldo ei riit�")."!<br>\n";

							// skipataan seuraavaan laskuun
							continue 2;
						}

						$laskutus_esto_saldot[$tuoteno_varattu_chk_row['tuoteno']] -= $tuoteno_varattu_chk_row['varattu'];

					}
				}

				// Tsekataan ettei lipsahda JT-rivej� laskutukseen jos osaotoimitus on kielletty
				if ($yhtiorow["varaako_jt_saldoa"] != "") {
					$lisavarattu = " + tilausrivi.varattu";
				}
				else {
					$lisavarattu = "";
				}

				$query = "  SELECT sum(if (tilausrivi.var in ('J','S') and tilausrivi.jt $lisavarattu > 0, 1, 0)) jteet
							FROM tilausrivi
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.otunnus = '$laskurow[tunnus]'
							and tilausrivi.tyyppi  = 'L'
							and tilausrivi.var in ('J','S')";
				$sarjares1 = pupe_query($query);
				$srow1 = mysql_fetch_assoc($sarjares1);

				if ($srow1["jteet"] > 0 and $laskurow["osatoimitus"] != '') {
					// Jos tilauksella oli yksikin jt-rivi ja osatoimitus on kielletty
					$lasklisa .= " and lasku.tunnus != '$laskurow[tunnus]' ";

					if ($silent == "" or $silent == "VIENTI") {
						$tulos_ulos_sarjanumerot .= sprintf(t("Tilauksella %s oli JT-rivej� ja osatoimitusta ei tehd�, eli se j�tettiin odottamaan JT-tuotteita."), $laskurow["tunnus"])."<br>\n";
					}
				}

				// Onko asiakkalla panttitili
				$query = "	SELECT panttitili
							FROM asiakas
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$laskurow['liitostunnus']}'";
				$asiakas_panttitili_chk_res = pupe_query($query);
				$asiakas_panttitili_chk_row = mysql_fetch_assoc($asiakas_panttitili_chk_res);

				// Tsekataan v�h�n alveja ja sarjanumerojuttuja
				$query = "  SELECT tuote.sarjanumeroseuranta, tilausrivi.tunnus, tilausrivi.varattu, tilausrivi.tuoteno, tilausrivin_lisatiedot.osto_vai_hyvitys, tilausrivi.alv, tuote.kehahin, tuote.ei_saldoa, tuote.panttitili, tilausrivi.var2
							FROM tilausrivi use index (yhtio_otunnus)
							JOIN tuote ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno
							LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.otunnus = '$laskurow[tunnus]'
							and tilausrivi.tyyppi  = 'L'
							and tilausrivi.var not in ('P','J','S')";
				$sarjares1 = pupe_query($query);

				while ($srow1 = mysql_fetch_assoc($sarjares1)) {

					// Tsekataan onko tuotetta ikin� ostettu jos kehahinarvio_ennen_ensituloa-parametri on p��ll�
					if ($yhtiorow["kehahinarvio_ennen_ensituloa"] != "" and $srow1["kehahin"] != 0 and $srow1["ei_saldoa"] == "") {

						if ($poikkeava_pvm != "") {
							$tapapvm = $laskvv."-".$laskkk."-".$laskpp." 23:59:59";
						}
						else {
							$tapapvm = date("Y-m-d H:i:s");
						}

						$query = "  SELECT tunnus
									FROM tapahtuma
									WHERE yhtio = '$kukarow[yhtio]'
									and laji in ('tulo', 'valmistus')
									and laadittu < '$tapapvm'
									and tuoteno = '$srow1[tuoteno]'";
						$sarjares2 = pupe_query($query);

						if (mysql_num_rows($sarjares2) == 0) {
							$lasklisa .= " and lasku.tunnus != '$laskurow[tunnus]' ";

							if ($silent == "" or $silent == "VIENTI") {
								$tulos_ulos_sarjanumerot .= "<font class='error'>".t("Tilausta ei voida laskuttaa arvioidulla keskihankintahinnalla").": $laskurow[tunnus] $srow1[tuoteno]!!!</font><br>\n";
							}
						}
					}

					// Tsekataan alvit
					$query = "  SELECT group_concat(distinct concat_ws(',', selite, selite+500, selite+600)) alvit
								FROM avainsana
								WHERE yhtio = '$kukarow[yhtio]'
								and laji in ('ALV','ALVULK')";
					$sarjares2 = pupe_query($query);
					$srow2 = mysql_fetch_assoc($sarjares2);

					if (!in_array($srow1["alv"], explode(",", $srow2["alvit"]))) {
						$lasklisa .= " and lasku.tunnus != '$laskurow[tunnus]' ";

						if ($silent == "" or $silent == "VIENTI") {
							$tulos_ulos_sarjanumerot .= t("Tilauksella virheellisi� verokantoja").": $laskurow[tunnus] $srow1[tuoteno] $srow1[alv]!!!<br>\n";
						}
					}

					if ($srow1["sarjanumeroseuranta"] != "") {

						if ($srow1["varattu"] < 0) {
							$tunken = "ostorivitunnus";
						}
						else {
							$tunken = "myyntirivitunnus";
						}

						if ($srow1["sarjanumeroseuranta"] == "S" or $srow1["sarjanumeroseuranta"] == "T" or $srow1["sarjanumeroseuranta"] == "U" or $srow1["sarjanumeroseuranta"] == "V") {
							$query = "  SELECT count(distinct sarjanumero) kpl
										FROM sarjanumeroseuranta
										WHERE yhtio = '$kukarow[yhtio]'
										and tuoteno = '$srow1[tuoteno]'
										and $tunken = '$srow1[tunnus]'";
							$sarjares2 = pupe_query($query);
							$srow2 = mysql_fetch_assoc($sarjares2);

							if ($srow2["kpl"] != abs($srow1["varattu"])) {
								$lasklisa .= " and lasku.tunnus != '$laskurow[tunnus]' ";

								if ($silent == "" or $silent == "VIENTI") {
									$tulos_ulos_sarjanumerot .= t("Tilaukselta puuttuu sarjanumeroita, ei voida laskuttaa").": $laskurow[tunnus] $srow1[tuoteno] $laskurow[nimi]!!!<br>\n";
								}
							}
						}
						else {
							$query = "  SELECT count(*) kpl
										FROM sarjanumeroseuranta
										WHERE yhtio = '$kukarow[yhtio]'
										and tuoteno = '$srow1[tuoteno]'
										and $tunken = '$srow1[tunnus]'";
							$sarjares2 = pupe_query($query);
							$srow2 = mysql_fetch_assoc($sarjares2);

							if ($srow2["kpl"] != 1) {
								$lasklisa .= " and lasku.tunnus != '$laskurow[tunnus]' ";

								if ($silent == "" or $silent == "VIENTI") {
									$tulos_ulos_sarjanumerot .= t("Tilaukselta puuttuu er�numeroita, ei voida laskuttaa").": $laskurow[tunnus] $srow1[tuoteno] $laskurow[nimi]!!!<br>\n";
								}
							}
						}

						if (($srow1["sarjanumeroseuranta"] == "S" or $srow1["sarjanumeroseuranta"] == "U") and $srow1["varattu"] < 0 and $srow1["osto_vai_hyvitys"] == "") {
							//Jos tuotteella on sarjanumero ja kyseess� on HYVITYST�

							//T�h�n hyvitysriviin liitetyt sarjanumerot
							$query = "  SELECT sarjanumero, kaytetty, tunnus
										FROM sarjanumeroseuranta
										WHERE yhtio         = '$kukarow[yhtio]'
										and ostorivitunnus  = '$srow1[tunnus]'";
							$sarjares = pupe_query($query);

							while ($sarjarowx = mysql_fetch_assoc($sarjares)) {

								// Haetaan hyvitett�vien myyntirivien kautta alkuper�iset ostorivit
								$query  = " SELECT sarjanumeroseuranta.tunnus
											FROM sarjanumeroseuranta
											JOIN tilausrivi use index (PRIMARY) ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.ostorivitunnus
											WHERE sarjanumeroseuranta.yhtio     = '$kukarow[yhtio]'
											and sarjanumeroseuranta.tuoteno     = '$srow1[tuoteno]'
											and sarjanumeroseuranta.sarjanumero = '$sarjarowx[sarjanumero]'
											and sarjanumeroseuranta.kaytetty    = '$sarjarowx[kaytetty]'
											and sarjanumeroseuranta.myyntirivitunnus > 0
											and sarjanumeroseuranta.ostorivitunnus   > 0
											and sarjanumeroseuranta.tunnus != '$sarjarowx[tunnus]'
											ORDER BY sarjanumeroseuranta.tunnus DESC
											LIMIT 1";
								$sarjares12 = pupe_query($query);

								if (mysql_num_rows($sarjares12) == 0) {
									// Jos sarjanumeroa ei ikin� olla ostettu, mutta se on myyty ja nyt halutaan perua kaupat
									$query  = " SELECT sarjanumeroseuranta.tunnus
												FROM sarjanumeroseuranta
												JOIN tilausrivi t2 use index (PRIMARY) ON t2.yhtio=sarjanumeroseuranta.yhtio and t2.tunnus=sarjanumeroseuranta.ostorivitunnus
												JOIN tilausrivi t3 use index (PRIMARY) ON t3.yhtio=sarjanumeroseuranta.yhtio and t3.tunnus=sarjanumeroseuranta.myyntirivitunnus and t3.uusiotunnus>0
												WHERE sarjanumeroseuranta.yhtio     = '$kukarow[yhtio]'
												and sarjanumeroseuranta.tuoteno     = '$srow1[tuoteno]'
												and sarjanumeroseuranta.sarjanumero = '$sarjarowx[sarjanumero]'
												and sarjanumeroseuranta.kaytetty    = '$sarjarowx[kaytetty]'
												and sarjanumeroseuranta.myyntirivitunnus > 0
												and sarjanumeroseuranta.ostorivitunnus   > 0";
									$sarjares12 = pupe_query($query);

									if (mysql_num_rows($sarjares12) != 1) {
										$lasklisa .= " and lasku.tunnus != '$laskurow[tunnus]' ";

										if ($silent == "" or $silent == "VIENTI") {
											$tulos_ulos_sarjanumerot .= t("Hyvitett�v�� rivi� ei l�ydy, ei voida laskuttaa").": $laskurow[tunnus] $srow1[tuoteno] $sarjarowx[sarjanumero] $laskurow[nimi]!!!<br>\n";
										}
									}
								}
							}
						}

						$query = "  SELECT distinct kaytetty
									FROM sarjanumeroseuranta
									WHERE yhtio = '$kukarow[yhtio]'
									and tuoteno = '$srow1[tuoteno]'
									and $tunken = '$srow1[tunnus]'";
						$sarres = pupe_query($query);

						if (mysql_num_rows($sarres) > 1) {
							$lasklisa .= " and lasku.tunnus != '$laskurow[tunnus]' ";

							if ($silent == "" or $silent == "VIENTI") {
								$tulos_ulos_sarjanumerot .= t("Riviin ei voi liitt�� sek� k�ytettyj� ett� uusia sarjanumeroita").": $laskurow[tunnus] $srow1[tuoteno] $laskurow[nimi]!!!<br>\n";
							}
						}

						// ollaan tekem�ss� myynti�
						if ($tunken == "myyntirivitunnus") {
							$query = "  SELECT sum(if (ifnull(tilausrivi.rivihinta, 0) = 0, 1, 0)) ei_ostohintaa
										FROM sarjanumeroseuranta
										LEFT JOIN tilausrivi use index (PRIMARY) ON (tilausrivi.yhtio = sarjanumeroseuranta.yhtio and tilausrivi.tunnus = sarjanumeroseuranta.ostorivitunnus)
										LEFT JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio=tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus=tilausrivin_lisatiedot.tilausrivitunnus)
										WHERE sarjanumeroseuranta.yhtio  = '$kukarow[yhtio]'
										and sarjanumeroseuranta.tuoteno  = '$srow1[tuoteno]'
										and sarjanumeroseuranta.$tunken  = '$srow1[tunnus]'
										and sarjanumeroseuranta.kaytetty = 'K'";
							$sarres = pupe_query($query);
							$srow2 = mysql_fetch_assoc($sarres);

							if (mysql_num_rows($sarres) > 0 and $srow2["ei_ostohintaa"] > 0) {
								$lasklisa .= " and lasku.tunnus != '$laskurow[tunnus]' ";

								if ($silent == "" or $silent == "VIENTI") {
									$tulos_ulos_sarjanumerot .= t("Olet myym�ss� k�ytetty� venett�, jota ei ole viel� ostettu! Ei voida laskuttaa!").": $laskurow[tunnus] $srow1[tuoteno] $laskurow[nimi]!!!<br>\n";
								}
							}
						}
					}

					// jos tilausrivi ei ole cronin generoima (silloin var2-kentt��n tallennetaan PANT-teksti)
					// cron-ohjelma on panttitili_cron.php
					// jos asiakkaalla on panttitili k�yt�ss�, katsotaan tilausrivien tuotteet l�pi onko niiss� panttitilillisi� tuotteita
					if ($asiakas_panttitili_chk_row['panttitili'] == "K" and $srow1['panttitili'] == 'K' and $srow1['var2'] != 'PANT' and $srow1['varattu'] < 0) {

						if ($laskurow['clearing'] == 'HYVITYS') {

							// jos tilauksella on panttituotteita ja ollaan tekem�ss� hyvityst�, pit�� katsoa, ett� alkuper�isen veloituslaskun panttitili rivej� ei ole viel� k�ytetty
							$query = "	SELECT otunnus, tuoteno, sum(kpl) kpl
										FROM tilausrivi
										WHERE yhtio 	= '{$kukarow['yhtio']}'
										AND tyyppi  	= 'L'
										AND tuoteno 	= '{$srow1['tuoteno']}'
										AND uusiotunnus = '{$laskurow['vanhatunnus']}'
										AND kpl > 0
										GROUP BY 1, 2";
							$vanhatunnus_chk_res = pupe_query($query);

							while ($vanhatunnus_chk_row = mysql_fetch_assoc($vanhatunnus_chk_res)) {

								$query = "  SELECT sum(kpl) kpl
									        FROM panttitili
									        WHERE yhtio 			= '{$kukarow['yhtio']}'
									        AND asiakas 			= '{$laskurow['liitostunnus']}'
									        AND tuoteno 			= '{$srow1['tuoteno']}'
									        AND myyntitilausnro 	= '{$vanhatunnus_chk_row['otunnus']}'
									        AND status 				= ''
									        AND kaytettypvm 		= '0000-00-00'
									        AND kaytettytilausnro 	= 0";
								$pantti_chk_res = pupe_query($query);
                            	$pantti_chk_row = mysql_fetch_assoc($pantti_chk_res);

								if ($vanhatunnus_chk_row['kpl'] != $pantti_chk_row['kpl']) {
									$lasklisa .= " and lasku.tunnus != '{$laskurow['tunnus']}' ";

									if ($silent == "" or $silent == "VIENTI") {
										$tulos_ulos_sarjanumerot .= t("Hyvitett�v�n laskun pantit on jo k�ytetty")."!<br>\n";
									}
								}
							}
						}
					}
				}

				$query = "  SELECT *
							FROM maksuehto
							WHERE yhtio	= '$kukarow[yhtio]'
							and tunnus	= '$laskurow[maksuehto]'";
				$maresult = pupe_query($query);
				$maksuehtorow = mysql_fetch_assoc($maresult);

				if ($maksuehtorow['jaksotettu'] != '') {
					$query = "  UPDATE lasku SET alatila='J'
								WHERE tunnus = '$laskurow[tunnus]'";
					$updres = pupe_query($query);

					if ($silent == "" or $silent == "VIENTI") {
						$tulos_ulos_maksusoppari .= t("Maksusopimustilaus siirretty odottamaan loppulaskutusta").": $laskurow[tunnus] $laskurow[nimi]<br>\n";
					}
				}
				else {
					require("maksuehtosplittaus.inc");
				}
			}

			if (isset($tulos_ulos_sarjanumerot) and $tulos_ulos_sarjanumerot != '' and ($silent == "" or $silent == "VIENTI")) {
				$tulos_ulos .= "<br>\n".t("Sarjanumerovirheet").":<br>\n";
				$tulos_ulos .= $tulos_ulos_sarjanumerot;
			}

			if (isset($tulos_ulos_maksusoppari) and $tulos_ulos_maksusoppari != '' and ($silent == "" or $silent == "VIENTI")) {
				$tulos_ulos .= "<br>\n".t("Maksusopimustilaukset").":<br>\n";
				$tulos_ulos .= $tulos_ulos_maksusoppari;
			}

			if (isset($tulos_ulos_ehtosplit) and $tulos_ulos_ehtosplit != '' and $silent == "") {
				$tulos_ulos .= "<br>\n".t("Tilauksia joilla on moniehto-maksuehto").":<br>\n";
				$tulos_ulos .= $tulos_ulos_ehtosplit;
			}

			//haetaan kaikki laskutettavat tilaukset uudestaan, nyt meill� on maksuehtosplittaukset tehty
			$query = "  SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						and tila    = 'L'
						and alatila = 'D'
						and viite   = ''
						and chn != '999'
						$lasklisa";
			$res = pupe_query($query);

			if (mysql_num_rows($res) > 0) {

				$tunnukset = "";

				// otetaan tunnukset talteen
				while ($row = mysql_fetch_assoc($res)) {
					$tunnukset .= "'$row[tunnus]',";
				}

				//vika pilkku pois
				$tunnukset = substr($tunnukset,0,-1);

				if ($yhtiorow["koontilaskut_yhdistetaan"] == 'T') {
					$ketjutus_group = ", lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp, lasku.toim_maa ";
				}
				else {
					$ketjutus_group = "";
				}

				// Lasketaan rahtikulut ja j�lkivaatimuskulut vain jos niit� ei olla laskettu jo tilausvaiheessa.
				if ($yhtiorow["rahti_hinnoittelu"] == "") {

					//rahtien ja j�lkivaatimuskulujen muuttujia
					$rah     = 0;
					$jvhinta = 0;
					$rahinta = 0;

					// haetaan laskutettavista tilauksista kaikki distinct toimitustavat per asiakas per p�iv�
					// j�lkivaatimukset omalle riville ja tutkitaan tarvimmeko lis�ill� JV-kuluja
					if ($silent == "") {
						$tulos_ulos .= "<br>\n".t("J�lkivaatimuskulut").":<br>\n";
					}

					$query = "  SELECT group_concat(distinct lasku.tunnus) tunnukset
								FROM lasku, rahtikirjat, maksuehto
								WHERE lasku.yhtio = '$kukarow[yhtio]'
								AND lasku.tunnus in ($tunnukset)
								AND lasku.yhtio = rahtikirjat.yhtio
								AND lasku.tunnus = rahtikirjat.otsikkonro
								AND lasku.yhtio = maksuehto.yhtio
								AND lasku.maksuehto = maksuehto.tunnus
								AND maksuehto.jv != ''
								GROUP BY date_format(rahtikirjat.tulostettu, '%Y-%m-%d'), lasku.ytunnus, lasku.toimitustapa";
					$result = pupe_query($query);

					$yhdista = array();

					while ($row = mysql_fetch_assoc($result)) {
						$yhdista[] = $row["tunnukset"];
					}

					if (count($yhdista) == 0 and $silent == "") {
						$tulos_ulos .= t("Ei j�lkivaatimuksia")."!<br>\n";
					}

					if ($silent == "") $tulos_ulos .= "<table>";

					foreach ($yhdista as $otsikot) {

						// lis�t��n n�ille tilauksille jvkulut
						$virhe = 0;

						//haetaan ekan otsikon tiedot
						$query = "  SELECT lasku.*, maksuehto.jv
									FROM lasku, maksuehto
									WHERE lasku.yhtio = '$kukarow[yhtio]'
									AND lasku.tunnus in ($otsikot)
									AND lasku.yhtio = maksuehto.yhtio
									AND lasku.maksuehto = maksuehto.tunnus
									ORDER BY lasku.tunnus
									LIMIT 1";
						$otsre = pupe_query($query);
						$laskurow = mysql_fetch_assoc($otsre);

						if (mysql_num_rows($otsre) != 1) $virhe++;

						if (mysql_num_rows($otsre) == 1 and $virhe == 0) {

							// kirjoitetaan jv kulurivi ekalle otsikolle
							$query = "  SELECT jvkulu
										FROM toimitustapa
										WHERE yhtio = '$kukarow[yhtio]'
										AND selite = '$laskurow[toimitustapa]'";
							$tjvres = pupe_query($query);
							$tjvrow = mysql_fetch_assoc($tjvres);

							if ($yhtiorow["jalkivaatimus_tuotenumero"] == "") {
								$yhtiorow["jalkivaatimus_tuotenumero"] = $yhtiorow["rahti_tuotenumero"];
							}

							$query = "  SELECT *
										FROM tuote
										WHERE yhtio = '$kukarow[yhtio]'
										AND tuoteno = '$yhtiorow[jalkivaatimus_tuotenumero]'";
							$rhire = pupe_query($query);

							// jos tuotenumero l�ytyy
							if (mysql_num_rows($rhire) == 1) {
								$trow  = mysql_fetch_assoc($rhire);

								$hinta = $tjvrow['jvkulu']; // jv kulu
								$nimitys = "J�lkivaatimuskulu";
								$kommentti = "";

								list($jvhinta, $alv) = alv($laskurow, $trow, $hinta, '', '');

								$query  = " INSERT INTO tilausrivi (hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti)
											values ('$jvhinta', 'N', '1', '1', '$laskurow[tunnus]', '$trow[tuoteno]', '$nimitys', '$kukarow[yhtio]', 'L', '$alv', '$kommentti')";
								$addtil = pupe_query($query);

								if ($silent == "") {
									$tulos_ulos .= "<tr><td>".t("Lis�ttiin jv-kulut")."</td><td>$laskurow[tunnus]</td><td>$laskurow[toimitustapa]</td><td>$jvhinta</td><td>$yhtiorow[valkoodi]</td></tr>\n";
								}
							}
						}
						elseif (mysql_num_rows($otsre) != 1 and $silent == "") {
							$tulos_ulos .= "<tr><td>".t("J�lkivaatimuskulua ei l�ydy!")."</td><td>$laskurow[tunnus]</td><td>$laskurow[toimitustapa]</td></tr>\n";
						}
						elseif ($silent == "") {
							$tulos_ulos .= "<tr><td>".t("J�lkivaatimuskulua ei osattu lis�t�!")." $virhe</td><td>$otsikot</td><td>$laskurow[toimitustapa]</td></tr>\n";
						}
					}

					if ($silent == "") {
						$tulos_ulos .= "<br>\n".t("Rahtikulut").":<br>\n<table>";
					}

					// haetaan laskutettavista tilauksista kaikki distinct toimitustavat per asiakas per p�iv� miss� merahti (eli kohdistettu) = K (K�ytet��n l�hett�j�n rahtisopimusnumeroa)
					// j�lkivaatimukset omalle riville
					$query   = "SELECT group_concat(distinct lasku.tunnus) tunnukset
								FROM lasku, rahtikirjat, maksuehto
								WHERE lasku.yhtio = '$kukarow[yhtio]'
								and lasku.tunnus in ($tunnukset)
								and lasku.rahtivapaa = ''
								and lasku.kohdistettu = 'K'
								and lasku.yhtio = rahtikirjat.yhtio
								and lasku.tunnus = rahtikirjat.otsikkonro
								and lasku.yhtio = maksuehto.yhtio
								and lasku.maksuehto = maksuehto.tunnus
								GROUP BY date_format(rahtikirjat.tulostettu, '%Y-%m-%d'), lasku.ytunnus, lasku.toimitustapa, maksuehto.jv";
					$result  = pupe_query($query);
					$yhdista = array();

					while ($row = mysql_fetch_assoc($result)) {
						$yhdista[] = $row["tunnukset"];
					}

					foreach ($yhdista as $otsikot) {

						// lis�t��n n�ille tilauksille rahtikulut
						$virhe=0;

						//haetaan ekan otsikon tiedot
						$query = "  SELECT lasku.*, maksuehto.jv
									FROM lasku, maksuehto
									WHERE lasku.yhtio='$kukarow[yhtio]'
									and lasku.tunnus in ($otsikot)
									and lasku.yhtio = maksuehto.yhtio
									and lasku.maksuehto = maksuehto.tunnus
									order by lasku.tunnus
									limit 1";
						$otsre = pupe_query($query);
						$laskurow = mysql_fetch_assoc($otsre);

						if (mysql_num_rows($otsre)!=1) $virhe++;

						//summataan kaikki painot yhteen
						$query = "SELECT sum(kilot) kilot FROM rahtikirjat WHERE yhtio='$kukarow[yhtio]' and otsikkonro in ($otsikot)";
						$pakre = pupe_query($query);
						$pakka = mysql_fetch_assoc($pakre);
						if (mysql_num_rows($pakre)!=1) $virhe++;

						//haetaan v�h�n infoa rahtikirjoista
						$query = "SELECT distinct date_format(tulostettu, '%d.%m.%Y') pvm, rahtikirjanro from rahtikirjat where yhtio='$kukarow[yhtio]' and otsikkonro in ($otsikot)";
						$rahre = pupe_query($query);
						if (mysql_num_rows($rahre)==0) $virhe++;

						$rahtikirjanrot = "";
						while ($rahrow = mysql_fetch_assoc($rahre)) {
							if ($rahrow["pvm"]!='') $pvm = $rahrow["pvm"]; // pit�s olla kyll� aina sama
							$rahtikirjanrot .= "$rahrow[rahtikirjanro] ";
						}
						//vika pilkku pois
						$rahtikirjanrot = substr($rahtikirjanrot,0,-1);

						// haetaan rahdin hinta
						$rahtihinta_array = hae_rahtimaksu($otsikot);

						$rahtihinta_ale = array();

						// rahtihinta tulee rahtimatriisista yhti�n kotivaluutassa ja on verollinen, jos myyntihinnat ovat verollisia, tai veroton, jos myyntihinnat ovat verottomia (huom. yhti�n parametri alv_kasittely)
						if (is_array($rahtihinta_array)) {
							$rahtihinta = $rahtihinta_array['rahtihinta'];

							foreach ($rahtihinta_array['alennus'] as $ale_k => $ale_v) {
								$rahtihinta_ale[$ale_k] = $ale_v;
							}
						}
						else {
							$rahtihinta = 0;
						}

						$query = "  SELECT *
									FROM tuote
									WHERE yhtio = '$kukarow[yhtio]'
									AND tuoteno = '$yhtiorow[rahti_tuotenumero]'";
						$rhire = pupe_query($query);

						if ($rahtihinta != 0 and $virhe == 0 and mysql_num_rows($rhire) == 1) {

							$trow       = mysql_fetch_assoc($rhire);
							$otunnus    = $laskurow['tunnus'];
							$hinta      = $rahtihinta;
							$nimitys    = "$pvm $laskurow[toimitustapa]";
							$kommentti  = t("Rahtikirja").": $rahtikirjanrot";
							$netto      = count($rahtihinta_ale) > 0 ? '' : 'N';

							list($lis_hinta, $lis_netto, $lis_ale_kaikki, $alehinta_alv, $alehinta_val) = alehinta($laskurow, $trow, '1', $netto, $hinta, $rahtihinta_ale);
							list($rahinta, $alv) = alv($laskurow, $trow, $lis_hinta, '', $alehinta_alv);

							$ale_lisa_insert_query_1 = $ale_lisa_insert_query_2 = '';

							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale_lisa_insert_query_1 .= " ale{$alepostfix},";
								$ale_lisa_insert_query_2 .= " '".$lis_ale_kaikki["ale{$alepostfix}"]."',";
							}

							$query  = " INSERT INTO tilausrivi (laatija, laadittu, hinta, {$ale_lisa_insert_query_1} netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti)
										values ('automaatti', now(), '$rahinta', {$ale_lisa_insert_query_2} '$netto', '1', '1', '$otunnus', '$trow[tuoteno]', '$nimitys', '$kukarow[yhtio]', 'L', '$alv', '$kommentti')";
							$addtil = pupe_query($query);

							if ($silent == "") {
								$tulos_ulos .= "<tr><td>".t("Lis�ttiin rahtikulut")."</td><td>$laskurow[tunnus]</td><td>$laskurow[toimitustapa]</td><td>$rahtihinta</td><td>$yhtiorow[valkoodi]</td><td>$pakka[kilot] kg</td></tr>\n";
							}

							$rah++;
						}
						elseif ($silent == "") {
							$tulos_ulos .= "<tr><td>".t("Rahtimaksua ei osattu lis�t�!")." $virhe</td><td>$otsikot</td><td>$laskurow[toimitustapa]</td><td></td><td></td><td>$pakka[kilot] kg</td></tr>\n";
						}
					}

					if ($silent == "") {
						$tulos_ulos .= "</table>\n".sprintf(t("Lis�ttiin rahtikulu %s kpl rahtikirjaan"),$rah).".";
					}
				}
				elseif ($silent == "") {
					$tulos_ulos .= "<br>\n".t("Laskujen rahtikulut muodostuivat jo tilausvaiheessa").".<br>\n";
				}

				// katsotaan halutaanko laskuille lis�t� lis�kulu prosentti
				if ($yhtiorow["laskutuslisa_tuotenumero"] != "" and $yhtiorow["laskutuslisa"] > 0 and $yhtiorow["laskutuslisa_tyyppi"] != "") {

					$yhdista = array();
					$laskutuslisa_tyyppi_ehto = "";

					//ei k�teislaskuihin
					if ($yhtiorow["laskutuslisa_tyyppi"] == 'B' or $yhtiorow["laskutuslisa_tyyppi"] == 'K') {
						$query = "  SELECT tunnus
									FROM maksuehto
									WHERE yhtio = '$kukarow[yhtio]'
									and kateinen != ''";
						$limaresult = pupe_query($query);

						$lisakulu_maksuehto = array();

						while ($limaksuehtorow = mysql_fetch_assoc($limaresult)) {
							$lisakulu_maksuehto[] = $limaksuehtorow["tunnus"];
						}

						if (count($lisakulu_maksuehto) > 0) {
							$laskutuslisa_tyyppi_ehto = " and lasku.maksuehto not in (".implode(',',$lisakulu_maksuehto).") ";
						}
					}
					elseif ($yhtiorow["laskutuslisa_tyyppi"] == 'C' or $yhtiorow["laskutuslisa_tyyppi"] == 'K') {
						//ei noudolle
						$query = "  SELECT selite
									FROM toimitustapa
									WHERE yhtio = '$kukarow[yhtio]'
									and nouto != ''";
						$toimitusresult = pupe_query($query);

						$lisakulu_toimitustapa = array();

						while ($litoimitustaparow = mysql_fetch_assoc($toimitusresult)) {
							$lisakulu_toimitustapa[] = "'".$litoimitustaparow["selite"]."'";
						}

						if (count($lisakulu_toimitustapa) > 0) {
							$laskutuslisa_tyyppi_ehto = " and lasku.toimitustapa not in (".implode(',',$lisakulu_toimitustapa).") ";
						}
					}

					// Tehd��n ketjutus (group by PIT�� OLLA sama kuin alhaalla) rivi ~1243
					$query = "  SELECT
								if(lasku.ketjutus = '', '', if (lasku.vanhatunnus > 0, lasku.vanhatunnus, lasku.tunnus)) ketjutuskentta,
								group_concat(lasku.tunnus) tunnukset
								FROM lasku
								LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio and laskun_lisatiedot.otunnus = lasku.tunnus)
								where lasku.yhtio = '$kukarow[yhtio]'
								and lasku.tunnus in ($tunnukset)
								$laskutuslisa_tyyppi_ehto
								GROUP BY ketjutuskentta, lasku.ytunnus, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, lasku.maksuehto, lasku.erpcm, lasku.vienti,
								lasku.lisattava_era, lasku.vahennettava_era, lasku.maa_maara, lasku.kuljetusmuoto, lasku.kauppatapahtuman_luonne,
								lasku.sisamaan_kuljetus, lasku.aktiivinen_kuljetus, lasku.kontti, lasku.aktiivinen_kuljetus_kansallisuus,
								lasku.sisamaan_kuljetusmuoto, lasku.poistumistoimipaikka, lasku.poistumistoimipaikka_koodi, lasku.chn, lasku.maa, lasku.valkoodi,
								laskun_lisatiedot.laskutus_nimi, laskun_lisatiedot.laskutus_nimitark, laskun_lisatiedot.laskutus_osoite, laskun_lisatiedot.laskutus_postino, laskun_lisatiedot.laskutus_postitp, laskun_lisatiedot.laskutus_maa
								$ketjutus_group";
					$result = pupe_query($query);

					while ($row = mysql_fetch_assoc($result)) {
						$yhdista[] = $row["tunnukset"];
					}

					// haetaan laskutuslisa_tuotenumero-tuotteen tiedot
					$query = "  SELECT *
								FROM tuote
								WHERE yhtio = '$kukarow[yhtio]'
								AND tuoteno = '$yhtiorow[laskutuslisa_tuotenumero]'";
					$rhire = pupe_query($query);
					$trow  = mysql_fetch_assoc($rhire);

					foreach ($yhdista as $otsikot) {
						// Tsekataan, ett� laskutuslis�� ei ole jo lis�tty k�sin
						$query = "  SELECT tunnus, hinta
									FROM tilausrivi
									WHERE yhtio = '$kukarow[yhtio]'
									and otunnus in ($otsikot)
									and tuoteno = '$trow[tuoteno]'
									and tyyppi = 'L'";
						$listilre = pupe_query($query);

						if (mysql_num_rows($listilre) == 0) {
							//haetaan ekan otsikon tiedot
							$query = "  SELECT lasku.*
										FROM lasku
										WHERE lasku.yhtio = '$kukarow[yhtio]'
										AND lasku.tunnus in ($otsikot)
										ORDER BY lasku.tunnus
										LIMIT 1";
							$otsre = pupe_query($query);
							$laskurow = mysql_fetch_assoc($otsre);

							$query = "  SELECT *
										FROM asiakas
										WHERE yhtio = '$kukarow[yhtio]'
										AND tunnus = '$laskurow[liitostunnus]'";
							$aslisakulres = pupe_query($query);
							$aslisakulrow = mysql_fetch_assoc($aslisakulres);

							if (mysql_num_rows($otsre) == 1 and mysql_num_rows($rhire) == 1 and $aslisakulrow['laskutuslisa'] == '') {
								if ($yhtiorow["laskutuslisa_tyyppi"] == 'L' or $yhtiorow["laskutuslisa_tyyppi"] == 'K' or $yhtiorow["laskutuslisa_tyyppi"] == 'N') {

									$query_ale_lisa = generoi_alekentta('M');

									// Prosentuaalinen laskutuslis�
									// lasketaan laskun loppusumma (HUOM ei tarvitse huomioida veroa!)
									$query = "  SELECT sum(tilausrivi.hinta * (tilausrivi.varattu + tilausrivi.jt) * {$query_ale_lisa}) laskun_loppusumma
												FROM tilausrivi
												JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
												WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
												and tilausrivi.tyyppi = 'L'
												and tilausrivi.otunnus in ($otsikot)";
									$listilre = pupe_query($query);
									$listilro = mysql_fetch_assoc($listilre);

									$hinta = $listilro["laskun_loppusumma"] * $yhtiorow["laskutuslisa"] / 100;
								}
								else {
									// Raham��r�inen laskutulis�
									$hinta = $yhtiorow["laskutuslisa"];
								}

								$hinta = laskuval($hinta, $laskurow["vienti_kurssi"]);
								list($lis_hinta, $lis_netto, $lis_ale, $alehinta_alv, $alehinta_val) = alehinta($laskurow, $trow, '1', 'N', $hinta, 0);
								list($lkhinta, $alv) = alv($laskurow, $trow, $lis_hinta, '', $alehinta_alv);

								// lis�t��n puuterivi
								$query = "  INSERT into tilausrivi set
											hyllyalue       = '',
											hyllynro        = '',
											hyllyvali       = '',
											hyllytaso       = '',
											tilaajanrivinro = '',
											laatija         = '$kukarow[kuka]',
											laadittu        = now(),
											yhtio           = '$kukarow[yhtio]',
											tuoteno         = '$trow[tuoteno]',
											varattu         = '1',
											yksikko         = '$trow[yksikko]',
											kpl             = '0',
											kpl2            = '0',
											tilkpl          = '1',
											jt              = '0',
											ale1            = '0',
											alv             = '$alv',
											netto           = 'N',
											hinta           = '$lkhinta',
											kerayspvm       = now(),
											otunnus         = '$laskurow[tunnus]',
											tyyppi          = 'L',
											toimaika        = now(),
											kommentti       = '',
											var             = '',
											try             = '$trow[try]',
											osasto          = '$trow[osasto]',
											perheid         = '',
											perheid2        = '',
											nimitys         = '$trow[nimitys]',
											jaksotettu      = '',
											kerattyaika     = now()";
								$addtil = pupe_query($query);
								$lisatty_tun = mysql_insert_id();

								$query = "  INSERT INTO tilausrivin_lisatiedot
											SET yhtio           = '$kukarow[yhtio]',
											positio             = '',
											tilausrivilinkki    = '',
											toimittajan_tunnus  = '',
											tilausrivitunnus    = '$lisatty_tun',
											jarjestys           = '',
											vanha_otunnus       = '$laskurow[tunnus]',
											ei_nayteta          = '',
											luontiaika          = now(),
											laatija             = '$kukarow[kuka]'";
								$addtil = pupe_query($query);

								if ($silent == "") {
									$tulos_ulos .= t("Lis�ttiin lis�kuluja")." $laskurow[tunnus]: $lkhinta $laskurow[valkoodi]<br>\n";
								}
							}
							else {
								$tulos_ulos .= t("Lis�kulua ei voitu lis�t�")." $laskurow[tunnus]!<br>\n";
							}
						}
					}
				}

				// laskutetaan kaikki tilaukset (siis teh��n kaikki tarvittava matikka)
				// rullataan eka query alkuun
				if (mysql_num_rows($res) != 0) {
					mysql_data_seek($res,0);
				}

				$laskutetttu = 0;

				if ($silent == "") {
					$tulos_ulos .= "<br><br>\n".t("Tilausten laskutus:")."<br>\n";
				}

				while ($row = mysql_fetch_assoc($res)) {
					// laskutus tarttee kukarow[kesken]
					$kukarow['kesken']=$row['tunnus'];

					require("laskutus.inc");
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

				//haetaan kaikki laskutusvalmiit tilaukset jotka saa ketjuttaa, viite pit�� olla tyhj�� muuten ei laskuteta
				$query  = " SELECT
							if(lasku.ketjutus = '', '', if (lasku.vanhatunnus > 0, lasku.vanhatunnus, lasku.tunnus)) ketjutuskentta,
							lasku.ytunnus, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, lasku.maksuehto, lasku.erpcm, lasku.vienti,
							lasku.lisattava_era, lasku.vahennettava_era, lasku.maa_maara, lasku.kuljetusmuoto, lasku.kauppatapahtuman_luonne,
							lasku.sisamaan_kuljetus, lasku.aktiivinen_kuljetus, lasku.kontti, lasku.aktiivinen_kuljetus_kansallisuus,
							lasku.sisamaan_kuljetusmuoto, lasku.poistumistoimipaikka, lasku.poistumistoimipaikka_koodi, lasku.chn, lasku.maa, lasku.valkoodi,
							count(lasku.tunnus) yht,
							group_concat(lasku.tunnus) tunnukset
							FROM lasku
							LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio and laskun_lisatiedot.otunnus = lasku.tunnus)
							WHERE lasku.yhtio   = '$kukarow[yhtio]'
							and lasku.alatila   = 'V'
							and lasku.tila      = 'L'
							and lasku.viite     = ''
							$lasklisa
							GROUP BY ketjutuskentta, lasku.ytunnus, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, lasku.maksuehto, lasku.erpcm, lasku.vienti,
							lasku.lisattava_era, lasku.vahennettava_era, lasku.maa_maara, lasku.kuljetusmuoto, lasku.kauppatapahtuman_luonne,
							lasku.sisamaan_kuljetus, lasku.aktiivinen_kuljetus, lasku.kontti, lasku.aktiivinen_kuljetus_kansallisuus,
							lasku.sisamaan_kuljetusmuoto, lasku.poistumistoimipaikka, lasku.poistumistoimipaikka_koodi, lasku.chn, lasku.maa, lasku.valkoodi,
							laskun_lisatiedot.laskutus_nimi, laskun_lisatiedot.laskutus_nimitark, laskun_lisatiedot.laskutus_osoite, laskun_lisatiedot.laskutus_postino, laskun_lisatiedot.laskutus_postitp, laskun_lisatiedot.laskutus_maa
							$ketjutus_group
							ORDER BY lasku.ytunnus, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, lasku.maksuehto, lasku.erpcm, lasku.vienti,
							lasku.lisattava_era, lasku.vahennettava_era, lasku.maa_maara, lasku.kuljetusmuoto, lasku.kauppatapahtuman_luonne,
							lasku.sisamaan_kuljetus, lasku.aktiivinen_kuljetus, lasku.kontti, lasku.aktiivinen_kuljetus_kansallisuus,
							lasku.sisamaan_kuljetusmuoto, lasku.poistumistoimipaikka, lasku.poistumistoimipaikka_koodi, lasku.chn, lasku.maa, lasku.valkoodi,
							laskun_lisatiedot.laskutus_nimi, laskun_lisatiedot.laskutus_nimitark, laskun_lisatiedot.laskutus_osoite, laskun_lisatiedot.laskutus_postino, laskun_lisatiedot.laskutus_postitp, laskun_lisatiedot.laskutus_maa";
				$result = pupe_query($query);

				if ($silent == "") {
					$tulos_ulos .= "<br><br>\n".t("Laskujen ketjutus:")."<br>\n<table>";
				}

				while ($row = mysql_fetch_assoc($result)) {

					if ($silent == "") {
						$tulos_ulos .= "<tr><td>$row[ytunnus]</td><td>$row[nimi]<br>$row[nimitark]</td><td>$row[osoite]</td><td>$row[postino]</td>\n
										<td>$row[postitp]</td><td>$row[maksuehto]</td><td>$row[erpcm]</td><td>Ketjutettu $row[yht] kpl</td></tr>\n";
					}

					$ketjut[]  = $row["tunnukset"];
				}

				if ($silent == "") {
					$tulos_ulos .= "</table><br>\n";
				}

				//laskuri
				$lask       = 0;
				$edilask    = 0;

				// jos on jotain laskutettavaa ...
				if (count($ketjut) != 0) {

					//Timestamppi EDI-failiin alkuu ja loppuun
					$timestamppi = gmdate("YmdHis");

					//nyt meill� on $ketjut arrayssa kaikki yhteenkuuluvat tunnukset suoraan mysql:n IN-syntaksin muodossa!! jee!!
					foreach ($ketjut as $tunnukset) {

						// generoidaan laskulle viite ja lasno
						$query = "SELECT max(laskunro) laskunro FROM lasku WHERE yhtio = '$kukarow[yhtio]' and tila = 'U'";
						$result= pupe_query($query);
						$lrow  = mysql_fetch_assoc($result);

						$lasno = $lrow["laskunro"] + 1;

						if ($lasno < 100) {
							$lasno = 100;
						}

						// Tutkitaan onko ketju factorinkia
						$query  = " SELECT factoring.sopimusnumero, maksuehto.factoring, factoring.viitetyyppi
									FROM lasku
									JOIN maksuehto ON lasku.yhtio=maksuehto.yhtio and lasku.maksuehto=maksuehto.tunnus and maksuehto.factoring!=''
									JOIN factoring ON maksuehto.yhtio=factoring.yhtio and maksuehto.factoring=factoring.factoringyhtio and lasku.valkoodi=factoring.valkoodi
									WHERE lasku.yhtio = '$kukarow[yhtio]'
									and lasku.tunnus in ($tunnukset)
									GROUP BY factoring.sopimusnumero, maksuehto.factoring";
						$fres = pupe_query($query);
						$frow = mysql_fetch_assoc($fres);

						//Nordean viitenumero rakentuu hieman eri lailla ku normaalisti
						if ($frow["sopimusnumero"] > 0 and $frow["factoring"] == 'NORDEA' and $frow["viitetyyppi"] == '') {
							$viite = $frow["sopimusnumero"]."0".sprintf('%08d', $lasno);
						}
						elseif ($frow["sopimusnumero"] > 0 and $frow["factoring"] == 'OKO' and $frow["viitetyyppi"] == '') {
							$viite = $frow["sopimusnumero"]."001".sprintf('%09d', $lasno);
						}
						elseif ($frow["sopimusnumero"] > 0 and $frow["factoring"] == 'SAMPO' and $frow["viitetyyppi"] == '') {
							$viite = $frow["sopimusnumero"]."1".sprintf('%09d', $lasno);
						}
						else {
							$viite = $lasno;
						}

						// Tutkitaan k�ytet��nk� maksuehdon pankkiyhteystietoja
						$query  = " SELECT pankkiyhteystiedot.viite
									FROM lasku
									JOIN maksuehto ON lasku.yhtio=maksuehto.yhtio and lasku.maksuehto=maksuehto.tunnus
									JOIN pankkiyhteystiedot ON maksuehto.yhtio=pankkiyhteystiedot.yhtio and maksuehto.pankkiyhteystiedot = pankkiyhteystiedot.tunnus and pankkiyhteystiedot.viite = 'SE'
									WHERE lasku.yhtio = '$kukarow[yhtio]'
									and lasku.tunnus in ($tunnukset)";
						$pankres = pupe_query($query);

						$seviite = "";

						if (mysql_num_rows($pankres) > 0) {
							$seviite = "SE";
						}

						//  Onko k�sinsy�tetty viite?
						$query = "  SELECT kasinsyotetty_viite
									FROM laskun_lisatiedot
									WHERE yhtio = '$kukarow[yhtio]'
									AND otunnus IN ($tunnukset)
									AND kasinsyotetty_viite != ''";
						$tarkres = pupe_query($query);

						if (mysql_num_rows($tarkres) == 1) {
							$tarkrow = mysql_fetch_assoc($tarkres) or pupe_error($tarkres);
							$viite = $tarkrow["kasinsyotetty_viite"];

							if ($seviite != 'SE') {
								//  Jos viitenumero on v��rin menn��n oletuksilla!
								if (tarkista_viite($viite) === FALSE) {
									$viite = $lasno;
									$tulos_ulos .= "<font class='message'><br>\n".t("HUOM!!! laskun '%s' k�sinsyotetty viitenumero '%s' on v��rin! Laskulle annettii uusi viite '%s'", $kieli, $lasno, $tarkrow["kasinsyotetty_viite"], $viite)."!</font><br>\n<br>\n";
									require('inc/generoiviite.inc');
								}
							}
							unset($oviite);
						}
						else {
							if ($seviite == 'SE') {
								require('inc/generoiviite_se.inc');
							}
							else {
								require('inc/generoiviite.inc');
							}

						}

						// p�ivitet��n ketjuun kuuluville laskuille sama laskunumero ja viite..
						$query  = " UPDATE lasku SET
									laskunro = '$lasno',
									viite = '$viite'
									WHERE yhtio = '$kukarow[yhtio]'
									AND tunnus IN ($tunnukset)";
						$result = pupe_query($query);

						// tehd��n U lasku ja tili�innit
						// tarvitaan $tunnukset mysql muodossa

						require("teeulasku.inc");

						// saadaan takaisin $laskurow
						$lasrow = $laskurow;

						// Luodaan tullausnumero jos sellainen tarvitaan
						// Jos on esim puhtaasti hyvityst� niin ei generoida tullausnumeroa
						if ($lasrow["vienti"] == 'K' and $lasrow["sisainen"] == "") {
							$query = "  SELECT tilausrivi.yhtio
										FROM tilausrivi
										JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.ei_saldoa = '')
										WHERE tilausrivi.uusiotunnus = '$lasrow[tunnus]'
										and tilausrivi.kpl > 0
										and tilausrivi.yhtio = '$kukarow[yhtio]'
										and tilausrivi.tyyppi = 'L'";
							$cresult = pupe_query($query);

							$hyvitys = "";
							if (mysql_num_rows($cresult) == 0) {
								//laskulla on vain hyvitysrivej�, tai ei yht��n rivi� --> ei tullata!
								$hyvitys = "ON";
							}
							else {
								$hyvitys = "EI";
							}

							$tullausnumero = '';

							if ($hyvitys == 'EI') {
								//generoidaan tullausnumero.
								$p = date('d');
								$k = date('m');
								$v = date('Y');
								$pvm = $v."-".$k."-".$p;

								$query = "	SELECT count(*)+1 tullausnumero
											FROM lasku use index (yhtio_tila_tapvm)
											WHERE vienti = 'K'
											and tila = 'U'
											and alatila = 'X'
											and tullausnumero != ''
											and tapvm = '$pvm'
											and yhtio = '$kukarow[yhtio]'";
								$result= pupe_query($query);
								$lrow  = mysql_fetch_assoc($result);

								$pvanumero = date('z')+1;

								//tullausnumero muodossa Vuosi-Tullikamari-P�iv�nnumero-Tullip��te-Juoksevanumeroperp�iv�
								$tullausnumero = date('y') . "-". $yhtiorow["tullikamari"] ."-" . sprintf('%03d', $pvanumero) . "-" . $yhtiorow["tullipaate"] . "-" . sprintf('%03d', $lrow["tullausnumero"]);

								// p�ivitet��n ketjuun kuuluville laskuille sama laskunumero ja viite..
								$query  = "UPDATE lasku set tullausnumero='$tullausnumero' WHERE vienti='K' and tila='U' and yhtio='$kukarow[yhtio]' and tunnus='$lasrow[tunnus]'";
								$result = pupe_query($query);

								$lasrow["tullausnumero"] = $tullausnumero;
							}
						}

						if ($silent == "") {
							$tulos_ulos .= $tulos_ulos_ulasku;
							$tulos_ulos .= $tulos_ulos_tiliointi;
						}

						// Haetaan maksuehdon tiedot
						$query  = " SELECT pankkiyhteystiedot.*, maksuehto.*
									FROM maksuehto
									LEFT JOIN pankkiyhteystiedot on (pankkiyhteystiedot.yhtio=maksuehto.yhtio and pankkiyhteystiedot.tunnus=maksuehto.pankkiyhteystiedot)
									WHERE maksuehto.yhtio='$kukarow[yhtio]' and maksuehto.tunnus='$lasrow[maksuehto]'";
						$result = pupe_query($query);

						if (mysql_num_rows($result) == 0) {
							$masrow = array();

							if ($lasrow["erpcm"] == "0000-00-00") {
								$tulos_ulos .= "<font class='message'><br>\n".t("Maksuehtoa")." $lasrow[maksuehto] ".t("ei l�ydy!")." Tunnus $lasrow[tunnus] ".t("Laskunumero")." $lasrow[laskunro] ".t("ep�onnistui pahasti")."!</font><br>\n<br>\n";
							}
						}
						else {
							$masrow = mysql_fetch_assoc($result);
						}

						//Haetaan factoringsopimuksen tiedot
						if ($masrow["factoring"] != '') {
							$query = "  SELECT *
										FROM factoring
										WHERE yhtio         = '$kukarow[yhtio]'
										and factoringyhtio  = '$masrow[factoring]'
										and valkoodi        = '$lasrow[valkoodi]'";
							$fres = pupe_query($query);
							$frow = mysql_fetch_assoc($fres);
						}
						else {
							unset($frow);
						}

						$pankkitiedot = array();

						//Laitetaan pankkiyhteystiedot kuntoon
						if ($masrow["factoring"] != "") {
							$pankkitiedot["pankkinimi1"]  = $frow["pankkinimi1"];
							$pankkitiedot["pankkitili1"]  = $frow["pankkitili1"];
							$pankkitiedot["pankkiiban1"]  = $frow["pankkiiban1"];
							$pankkitiedot["pankkiswift1"] = $frow["pankkiswift1"];
							$pankkitiedot["pankkinimi2"]  = $frow["pankkinimi2"];
							$pankkitiedot["pankkitili2"]  = $frow["pankkitili2"];
							$pankkitiedot["pankkiiban2"]  = $frow["pankkiiban2"];
							$pankkitiedot["pankkiswift2"] = $frow["pankkiswift2"];
							$pankkitiedot["pankkinimi3"]  = "";
							$pankkitiedot["pankkitili3"]  = "";
							$pankkitiedot["pankkiiban3"]  = "";
							$pankkitiedot["pankkiswift3"] = "";

						}
						elseif ($masrow["pankkinimi1"] != "") {
							$pankkitiedot["pankkinimi1"]  = $masrow["pankkinimi1"];
							$pankkitiedot["pankkitili1"]  = $masrow["pankkitili1"];
							$pankkitiedot["pankkiiban1"]  = $masrow["pankkiiban1"];
							$pankkitiedot["pankkiswift1"] = $masrow["pankkiswift1"];
							$pankkitiedot["pankkinimi2"]  = $masrow["pankkinimi2"];
							$pankkitiedot["pankkitili2"]  = $masrow["pankkitili2"];
							$pankkitiedot["pankkiiban2"]  = $masrow["pankkiiban2"];
							$pankkitiedot["pankkiswift2"] = $masrow["pankkiswift2"];
							$pankkitiedot["pankkinimi3"]  = $masrow["pankkinimi3"];
							$pankkitiedot["pankkitili3"]  = $masrow["pankkitili3"];
							$pankkitiedot["pankkiiban3"]  = $masrow["pankkiiban3"];
							$pankkitiedot["pankkiswift3"] = $masrow["pankkiswift3"];
						}
						else {
							$pankkitiedot["pankkinimi1"]  = $yhtiorow["pankkinimi1"];
							$pankkitiedot["pankkitili1"]  = $yhtiorow["pankkitili1"];
							$pankkitiedot["pankkiiban1"]  = $yhtiorow["pankkiiban1"];
							$pankkitiedot["pankkiswift1"] = $yhtiorow["pankkiswift1"];
							$pankkitiedot["pankkinimi2"]  = $yhtiorow["pankkinimi2"];
							$pankkitiedot["pankkitili2"]  = $yhtiorow["pankkitili2"];
							$pankkitiedot["pankkiiban2"]  = $yhtiorow["pankkiiban2"];
							$pankkitiedot["pankkiswift2"] = $yhtiorow["pankkiswift2"];
							$pankkitiedot["pankkinimi3"]  = $yhtiorow["pankkinimi3"];
							$pankkitiedot["pankkitili3"]  = $yhtiorow["pankkitili3"];
							$pankkitiedot["pankkiiban3"]  = $yhtiorow["pankkiiban3"];
							$pankkitiedot["pankkiswift3"] = $yhtiorow["pankkiswift3"];
						}

						$asiakas_apu_query = "  SELECT *
												FROM asiakas
												WHERE yhtio = '$kukarow[yhtio]'
												AND tunnus = '$lasrow[liitostunnus]'";
						$asiakas_apu_res = pupe_query($asiakas_apu_query);

						if (mysql_num_rows($asiakas_apu_res) == 1) {
							$asiakas_apu_row = mysql_fetch_assoc($asiakas_apu_res);
						}
						else {
							$asiakas_apu_row = array();
						}

						if (strtoupper(trim($asiakas_apu_row["kieli"])) == "SE") {
							$laskun_kieli = "SE";
						}
						elseif (strtoupper(trim($asiakas_apu_row["kieli"])) == "EE") {
							$laskun_kieli = "EE";
						}
						elseif (strtoupper(trim($asiakas_apu_row["kieli"])) == "FI") {
							$laskun_kieli = "FI";
						}
						else {
							$laskun_kieli = trim(strtoupper($yhtiorow["kieli"]));
						}

						if ($kieli != "") {
							$laskun_kieli = trim(strtoupper($kieli));
						}

						// t�ss� pohditaan laitetaanko verkkolaskuputkeen
						if (($lasrow["vienti"] == "" or ($lasrow["vienti"] == "E" and $lasrow["chn"] == "020")) and $masrow["itsetulostus"] == "" and $lasrow["sisainen"] == "" and $masrow["kateinen"] == ""  and $lasrow["chn"] != '666' and $lasrow["chn"] != '667' and abs($lasrow["summa"]) != 0) {

							// Nyt meill� on:
							// $lasrow array on U-laskun tiedot
							// $yhtiorow array on yhtion tiedot
							// $masrow array maksuehdon tiedot

							// Etsit��n myyj�n nimi
							$mquery  = "SELECT nimi, puhno, eposti
										FROM kuka
										WHERE tunnus='$lasrow[myyja]' and yhtio='$kukarow[yhtio]'";
							$myyresult = pupe_query($mquery);
							$myyrow = mysql_fetch_assoc($myyresult);

							//HUOM: T�ss� kaikki sallitut verkkopuolen chn:�t
							if (!in_array($lasrow['chn'], array("100", "010", "001", "020", "111", "112"))) {
								//Paperi by default
								$lasrow['chn'] = "100";
							}

							if ($lasrow['chn'] == "020") {
								$lasrow['chn'] = "010";
							}

							 if ($lasrow['arvo'] >= 0) {
								//Veloituslasku
								$tyyppi='380';
							}
							else {
								//Hyvityslasku
								$tyyppi='381';
							}

							// Laskukohtaiset kommentit kuntoon
							// T�m� merkki | eli pystyviiva on rivinvaihdon merkki laskun kommentissa elmalla
							$komm = "";

							// Onko k��nteist� verotusta
							$alvquery = "   SELECT tunnus
											FROM tilausrivi
											WHERE yhtio = '$kukarow[yhtio]'
											and otunnus in ($tunnukset)
											and tyyppi  = 'L'
											and alv >= 600";
							$alvresult = pupe_query($alvquery);

							if (mysql_num_rows($alvresult) > 0) {
								$komm .= t_avainsana("KAANTALVVIESTI", $laskun_kieli, "", "", "", "selitetark");
							}

							if (trim($lasrow['tilausyhteyshenkilo']) != '') {
								$komm .= "\n".t("Tilaaja", $laskun_kieli).": ".$lasrow['tilausyhteyshenkilo'];
							}

							if (trim($lasrow['asiakkaan_tilausnumero']) != '') {
								$komm .= "\n".t("Tilauksenne", $laskun_kieli).": ".$lasrow['asiakkaan_tilausnumero'];
							}

							if (trim($lasrow['kohde']) != '') {
								$komm .= "\n".t("Kohde", $laskun_kieli).": ".$lasrow['kohde'];
							}

							if (trim($lasrow['sisviesti1']) != '') {
								$komm .= "\n".t("Kommentti", $laskun_kieli).": ".$lasrow['sisviesti1'];
							}

							if (trim($komm) != '') {
								$lasrow['sisviesti1'] = str_replace(array("\r\n","\r","\n"),"|", trim($komm));
							}

							// Hoidetaan py�ristys sek� valuuttak�sittely
							if ($lasrow["valkoodi"] != '' and trim(strtoupper($lasrow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
								$lasrow["kasumma"]   = $lasrow["kasumma_valuutassa"];
								$lasrow["summa"]     = sprintf("%.2f", $lasrow["summa_valuutassa"] - $lasrow["pyoristys_valuutassa"]);
								$lasrow["arvo"]      = $lasrow["arvo_valuutassa"];
								$lasrow["pyoristys"] = $lasrow["pyoristys_valuutassa"];
							}
							else {
								$lasrow["summa"]    = sprintf("%.2f", $lasrow["summa"] - $lasrow["pyoristys"]);
							}

							// Ulkomaisen ytunnuksen korjaus
							if (substr(trim(strtoupper($lasrow["ytunnus"])),0,2) != strtoupper($lasrow["maa"]) and trim(strtoupper($lasrow["maa"])) != trim(strtoupper($yhtiorow["maa"]))) {
								$lasrow["ytunnus"] = strtoupper($lasrow["maa"])."-".$lasrow["ytunnus"];
							}

							if (strtoupper($laskun_kieli) != strtoupper($yhtiorow['kieli'])) {
								//K��nnet��n maksuehto
								$masrow["teksti"] = t_tunnus_avainsanat($masrow, "teksti", "MAKSUEHTOKV", $laskun_kieli);
							}

							$query = "  SELECT
										ifnull(min(date_format(if('$yhtiorow[tilausrivien_toimitettuaika]' = 'X', toimaika, if('$yhtiorow[tilausrivien_toimitettuaika]' = 'K' and keratty = 'saldoton', toimaika, toimitettuaika)), '%Y-%m-%d')), '0000-00-00') mint,
										ifnull(max(date_format(if('$yhtiorow[tilausrivien_toimitettuaika]' = 'X', toimaika, if('$yhtiorow[tilausrivien_toimitettuaika]' = 'K' and keratty = 'saldoton', toimaika, toimitettuaika)), '%Y-%m-%d')), '0000-00-00') maxt
										FROM tilausrivi
										WHERE yhtio = '$kukarow[yhtio]'
										and otunnus in ($tunnukset)
										and toimitettuaika != '0000-00-00 00:00:00'";
							$toimaikares = pupe_query($query);
							$toimaikarow = mysql_fetch_assoc($toimaikares);

							if ($toimaikarow["mint"] == "0000-00-00") {
								$toimaikarow["mint"] = date("Y-m-d");
							}
							if ($toimaikarow["maxt"] == "0000-00-00") {
								$toimaikarow["maxt"] = date("Y-m-d");
							}

							//Kirjoitetaan failiin laskun otsikkotiedot
							if ($lasrow["chn"] == "111") {
								elmaedi_otsik($tootedi, $lasrow, $masrow, $tyyppi, $timestamppi, $toimaikarow);
							}
							elseif ($lasrow["chn"] == "112") {
								finvoice_otsik($tootsisainenfinvoice, $lasrow, $kieli, $pankkitiedot, $masrow, $myyrow, $tyyppi, $toimaikarow, $tulos_ulos, $silent);
							}
							elseif ($yhtiorow["verkkolasku_lah"] == "iPost" or $yhtiorow["verkkolasku_lah"] == "finvoice") {
								finvoice_otsik($tootfinvoice, $lasrow, $kieli, $pankkitiedot, $masrow, $myyrow, $tyyppi, $toimaikarow, $tulos_ulos, $silent);
							}
							elseif ($yhtiorow["verkkolasku_lah"] == "apix") {
								finvoice_otsik($tootfinvoice, $lasrow, $kieli, $pankkitiedot, $masrow, $myyrow, $tyyppi, $toimaikarow, $tulos_ulos, $silent, "NOSOAPAPIX");
							}
							else {
								pupevoice_otsik($tootxml, $lasrow, $laskun_kieli, $pankkitiedot, $masrow, $myyrow, $tyyppi, $toimaikarow);
							}

							// Tarvitaan rivien eri verokannat
							$alvquery = "   SELECT distinct alv
											FROM tilausrivi
											WHERE yhtio = '$kukarow[yhtio]'
											and otunnus in ($tunnukset)
											and tyyppi  = 'L'
											ORDER BY alv";
							$alvresult = pupe_query($alvquery);

							while ($alvrow1 = mysql_fetch_assoc($alvresult)) {

								if ($alvrow1["alv"] >= 500) {
									$aquery = " SELECT '0' alv,
												round(sum(tilausrivi.rivihinta/if (lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1)),2) rivihinta,
												round(sum(0),2) alvrivihinta
												FROM tilausrivi
												JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
												WHERE tilausrivi.uusiotunnus = '$lasrow[tunnus]' and tilausrivi.yhtio = '$kukarow[yhtio]' and tilausrivi.alv = '$alvrow1[alv]' and tilausrivi.tyyppi = 'L'
												GROUP BY alv";
								}
								else {
									$aquery = " SELECT tilausrivi.alv,
												round(sum(tilausrivi.rivihinta/if (lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1)),2) rivihinta,
												round(sum((tilausrivi.rivihinta/if (lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1))*(tilausrivi.alv/100)),2) alvrivihinta
												FROM tilausrivi
												JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
												WHERE tilausrivi.uusiotunnus = '$lasrow[tunnus]' and tilausrivi.yhtio = '$kukarow[yhtio]' and tilausrivi.alv = '$alvrow1[alv]' and tilausrivi.tyyppi = 'L'
												GROUP BY alv";
								}
								$aresult = pupe_query($aquery);
								$alvrow = mysql_fetch_assoc($aresult);

								// Kirjotetaan failiin arvierittelyt
								if ($lasrow["chn"] == "111") {
									elmaedi_alvierittely($tootedi, $alvrow);
								}
								elseif ($lasrow["chn"] == "112") {
									finvoice_alvierittely($tootsisainenfinvoice, $lasrow, $alvrow);
								}
								elseif ($yhtiorow["verkkolasku_lah"] == "iPost" or $yhtiorow["verkkolasku_lah"] == "finvoice" or $yhtiorow["verkkolasku_lah"] == "apix") {
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
							elseif ($lasrow["chn"] == "112") {
								finvoice_otsikko_loput($tootsisainenfinvoice, $lasrow, $masrow);
							}
							elseif ($yhtiorow["verkkolasku_lah"] == "iPost" or $yhtiorow["verkkolasku_lah"] == "finvoice" or $yhtiorow["verkkolasku_lah"] == "apix") {
								finvoice_otsikko_loput($tootfinvoice, $lasrow, $masrow);
							}

							// katotaan miten halutaan sortattavan
							// haetaan asiakkaan tietojen takaa sorttaustiedot
							$order_sorttaus = '';

							$asiakas_apu_query = "  SELECT laskun_jarjestys, laskun_jarjestys_suunta, laskutyyppi
													FROM asiakas
													WHERE yhtio = '$kukarow[yhtio]'
													and tunnus = '$lasrow[liitostunnus]'";
							$asiakas_apu_res = pupe_query($asiakas_apu_query);

							if (mysql_num_rows($asiakas_apu_res) == 1) {
								$asiakas_apu_row = mysql_fetch_assoc($asiakas_apu_res);
								$sorttauskentta = generoi_sorttauskentta($asiakas_apu_row["laskun_jarjestys"] != "" ? $asiakas_apu_row["laskun_jarjestys"] : $yhtiorow["laskun_jarjestys"]);
								$order_sorttaus = $asiakas_apu_row["laskun_jarjestys_suunta"] != "" ? $asiakas_apu_row["laskun_jarjestys_suunta"] : $yhtiorow["laskun_jarjestys_suunta"];
							}
							else {
								$sorttauskentta = generoi_sorttauskentta($yhtiorow["laskun_jarjestys"]);
								$order_sorttaus = $yhtiorow["laskun_jarjestys_suunta"];
							}

							// Asiakkaan / yhti�n laskutyyppi
							if (isset($asiakas_apu_row['laskutyyppi']) and $asiakas_apu_row['laskutyyppi'] != -9) {
								$laskutyyppi = $asiakas_apu_row['laskutyyppi'];
							}
							else {
								$laskutyyppi = $yhtiorow['laskutyyppi'];
							}

							if ($yhtiorow["laskun_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";
							else $pjat_sortlisa = "";

							// Kirjoitetaan rivitietoja tilausriveilt�
							$query = "  SELECT tilausrivi.*, tuote.eankoodi, lasku.vienti_kurssi, lasku.viesti laskuviesti,
										if (date_format(tilausrivi.toimitettuaika, '%Y-%m-%d') = '0000-00-00', date_format(now(), '%Y-%m-%d'), date_format(tilausrivi.toimitettuaika, '%Y-%m-%d')) toimitettuaika,
										if (tilausrivi.toimaika = '0000-00-00', date_format(now(), '%Y-%m-%d'), tilausrivi.toimaika) toimaika,
										$sorttauskentta,
										if (tuote.tuotetyyppi='K','2 Ty�t','1 Muut') tuotetyyppi
										FROM tilausrivi
										JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
										JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno)
										LEFT JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus)
										WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
										and tilausrivi.otunnus in ($tunnukset)
										and tilausrivi.kpl <> 0
										and tilausrivi.tyyppi = 'L'
										and (tilausrivi.perheid = 0 or tilausrivi.perheid=tilausrivi.tunnus or tilausrivin_lisatiedot.ei_nayteta !='E' or tilausrivin_lisatiedot.ei_nayteta is null)
										ORDER BY tilausrivi.otunnus, $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
							$tilres = pupe_query($query);

							$rivinumerot = array(0 => 0);
							$rivilaskuri = 1;
							$rivimaara   = mysql_num_rows($tilres);

							while ($tilrow = mysql_fetch_assoc($tilres)) {

								if (strtolower($laskun_kieli) != strtolower($yhtiorow['kieli'])) {
									//K��nnet��n nimitys
									$tilrow['nimitys'] = t_tuotteen_avainsanat($tilrow, 'nimitys', $laskun_kieli);
								}

								// Rivin toimitusaika
								if ($yhtiorow["tilausrivien_toimitettuaika"] == 'K' and $tilrow["keratty"] == "saldoton") {
									$tilrow["toimitettuaika"] = $tilrow["toimaika"];
								}
								elseif ($yhtiorow["tilausrivien_toimitettuaika"] == 'X') {
									$tilrow["toimitettuaika"] = $tilrow["toimaika"];
								}
								else {
									$tilrow["toimitettuaika"] = $tilrow["toimitettuaika"];
								}

								// Laitetaan alennukset kommenttiin, koska laksulla on vain yksi alekentt�
								if ($yhtiorow['myynnin_alekentat'] > 1) {

									$alekomm = "";

									for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
										if (trim($tilrow["ale{$alepostfix}"]) > 0) {
											$alekomm .= t("Ale")."{$alepostfix} ".($tilrow["ale{$alepostfix}"]*1)." %|";
										}
									}

									if ($tilrow['erikoisale'] > 0) {
										$alekomm .= t("Erikoisale")." ".($tilrow["erikoisale"]*1)." %|";
									}

									$tilrow["kommentti"] = $alekomm.$tilrow["kommentti"];
								}

								// K��nnetty arvonlis�verovelvollisuus ja k�ytetyn tavaran myynti
								if ($tilrow["alv"] >= 600) {
									$tilrow["alv"] = 0;
									$tilrow["kommentti"] .= " Ei lis�tty� arvonlis�veroa, ostajan k��nnetty verovelvollisuus.";
								}
								elseif ($tilrow["alv"] >= 500) {
									$tilrow["alv"] = 0;
									$tilrow["kommentti"] .= " Ei sis�ll� v�hennett�v�� veroa.";
								}

								//Hetaan sarjanumeron tiedot
								if ($tilrow["kpl"] > 0) {
									$sarjanutunnus = "myyntirivitunnus";
								}
								else {
									$sarjanutunnus = "ostorivitunnus";
								}

								$query = "  SELECT *
											FROM sarjanumeroseuranta
											WHERE yhtio = '$kukarow[yhtio]'
											and tuoteno = '$tilrow[tuoteno]'
											and $sarjanutunnus='$tilrow[tunnus]'
											and sarjanumero != ''";
								$sarjares = pupe_query($query);

								if ($tilrow["kommentti"] != '' and mysql_num_rows($sarjares) > 0) {
									$tilrow["kommentti"] .= " ";
								}
								while ($sarjarow = mysql_fetch_assoc($sarjares)) {
									$tilrow["kommentti"] .= "S:nro: $sarjarow[sarjanumero] ";
								}

								if ($laskutyyppi == "7") {

									if ($tilrow["eankoodi"] != "") {
										$tilrow["kommentti"] = "EAN: $tilrow[eankoodi]|$tilrow[kommentti]";
									}

									$query = "  SELECT kommentti
												FROM asiakaskommentti
												WHERE yhtio = '{$kukarow['yhtio']}'
												AND tuoteno = '{$tilrow['tuoteno']}'
												AND ytunnus = '{$lasrow['ytunnus']}'
												ORDER BY tunnus";
									$asiakaskommentti_res = pupe_query($query);

									if (mysql_num_rows($asiakaskommentti_res) > 0) {
										while ($asiakaskommentti_row = mysql_fetch_assoc($asiakaskommentti_res)) {
											$tilrow["kommentti"] .= "|".$asiakaskommentti_row['kommentti'];
										}
									}
								}

								if ($lasrow["valkoodi"] != '' and trim(strtoupper($lasrow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
									// Veroton rivihinta valuutassa
									$tilrow["rivihinta"] = $tilrow["rivihinta_valuutassa"];

									// Yksikk�hinta valuutassa
									$tilrow["hinta"] = laskuval($tilrow["hinta"], $tilrow["vienti_kurssi"]);
								}

								// Verollinen Rivihinta. Lasketaan saman kaavan mukaan kuin laskutus.inc:ss�, eli py�ristet��n kaikki kerralla lopuksi!
								$totalvat = $tilrow["hinta"] * generoi_alekentta_php($tilrow, 'M', 'kerto') * $tilrow["kpl"];

								if ($yhtiorow["alv_kasittely"] != '') {
									$totalvat = $totalvat * (1 + ($tilrow["alv"] / 100));
								}

								// Yksikk�hinta on laskulla aina veroton
								if ($yhtiorow["alv_kasittely"] == '') {
									$tilrow["hinta"] = $tilrow["hinta"] / (1 + $tilrow["alv"] / 100);
								}

								// Veron m��r�
								$vatamount = $tilrow['rivihinta'] * $tilrow['alv'] / 100;

								// Py�ristet��n ja formatoidaan lopuksi
								$tilrow["hinta"]     = hintapyoristys($tilrow["hinta"]);
								$tilrow["rivihinta"] = hintapyoristys($tilrow["rivihinta"]);
								$totalvat            = hintapyoristys($totalvat);
								$vatamount           = hintapyoristys($vatamount);

								$tilrow['kommentti'] = preg_replace("/[^A-Za-z0-9������ ".preg_quote(".,-/!+()%#|:", "/")."]/", " ", $tilrow['kommentti']);
								$tilrow['nimitys']   = preg_replace("/[^A-Za-z0-9������ ".preg_quote(".,-/!+()%#|:", "/")."]/", " ", $tilrow['nimitys']);

								// Otetaan seuraavan rivin otunnus
								if ($rivilaskuri < $rivimaara) {
									$tilrow_seuraava = mysql_fetch_assoc($tilres);
									mysql_data_seek($tilres, $rivilaskuri);
									$tilrow['seuraava_otunnus'] = $tilrow_seuraava["otunnus"];
								}
								else {
									$tilrow['seuraava_otunnus'] = 0;
								}

								if ($lasrow["chn"] == "111") {

									if ((int) substr(sprintf("%06s", $tilrow["tilaajanrivinro"]), -6) > 0 and !in_array((int) substr(sprintf("%06s", $tilrow["tilaajanrivinro"]), -6), $rivinumerot)) {
										$rivinumero = (int) substr(sprintf("%06s", $tilrow["tilaajanrivinro"]), -6);
									}
									else {
										$rivinumero = (int) substr(sprintf("%06s", $tilrow["tunnus"]), -6);
									}

									elmaedi_rivi($tootedi, $tilrow, $rivinumero);
								}
								elseif ($lasrow["chn"] == "112") {
									finvoice_rivi($tootsisainenfinvoice, $tilrow, $lasrow, $vatamount, $totalvat);
								}
								elseif ($yhtiorow["verkkolasku_lah"] == "iPost" or $yhtiorow["verkkolasku_lah"] == "finvoice" or $yhtiorow["verkkolasku_lah"] == "apix") {
									finvoice_rivi($tootfinvoice, $tilrow, $lasrow, $vatamount, $totalvat);
								}
								else {
									pupevoice_rivi($tootxml, $tilrow, $vatamount, $totalvat);
								}

								$rivilaskuri++;
							}

							//Lopetetaan lasku
							if ($lasrow["chn"] == "111") {
								elmaedi_lasku_loppu($tootedi, $lasrow);

								//N�m� menee verkkolaskuputkeen
								$verkkolaskuputkeen_elmaedi[$lasrow["laskunro"]] = $lasrow["nimi"];

								$edilask++;
							}
							elseif ($lasrow["chn"] == "112") {
								finvoice_lasku_loppu($tootsisainenfinvoice, $lasrow, $pankkitiedot, $masrow);

								//N�m� menee verkkolaskuputkeen
								$verkkolaskuputkeen_suora[$lasrow["laskunro"]] = $lasrow["nimi"];
							}
							elseif ($yhtiorow["verkkolasku_lah"] == "iPost" or $yhtiorow["verkkolasku_lah"] == "finvoice" or $yhtiorow["verkkolasku_lah"] == "apix") {
								finvoice_lasku_loppu($tootfinvoice, $lasrow, $pankkitiedot, $masrow);

								if ($yhtiorow["verkkolasku_lah"] == "apix") {
									$tulostettavat_apix[] = $lasrow["tunnus"];

									//N�m� menee verkkolaskuputkeen
									$verkkolaskuputkeen_apix[$lasrow["laskunro"]] = $lasrow["nimi"];
								}
								else {
									//N�m� menee verkkolaskuputkeen
									$verkkolaskuputkeen_finvoice[$lasrow["laskunro"]] = $lasrow["nimi"];
								}
							}
							else {
								pupevoice_lasku_loppu($tootxml);

								//N�m� menee verkkolaskuputkeen
								$verkkolaskuputkeen_pupevoice[$lasrow["laskunro"]] = $lasrow["nimi"];
							}

							// Otetaan talteen jokainen laskunumero joka l�hetet��n jotta voidaan tulostaa paperilaskut
							$tulostettavat[] = $lasrow["tunnus"];
							$lask++;
						}
						elseif ($lasrow["sisainen"] != '') {
							if ($silent == "") $tulos_ulos .= "<br>\n".t("Tehtiin sis�inen lasku")."! $lasrow[laskunro] $lasrow[nimi]<br>\n";

							// Sis�isi� laskuja ei normaaalisti tuloseta paitsi jos meill� on valittu_tulostin
							if ($valittu_tulostin != '') {
								$tulostettavat[] = $lasrow["tunnus"];
								$lask++;
							}
						}
						elseif ($masrow["kateinen"] != '') {
							if ($silent == "") {
								$tulos_ulos .= "<br>\n".t("K�teislaskua ei l�hetetty")."! $lasrow[laskunro] $lasrow[nimi]<br>\n";
							}

							// K�teislaskuja ei l�hetet� ulos mutta ne halutaan kuitenkin tulostaa itse
							$tulostettavat[] = $lasrow["tunnus"];
							$lask++;
						}
						elseif ($lasrow["vienti"] != '' or $masrow["itsetulostus"] != '' or $lasrow["chn"] == "666" or $lasrow["chn"] == '667') {
							if ($silent == "" or $silent == "VIENTI") {

								if ($lasrow["chn"] == "666") {
									$tulos_ulos .= "<br>\n".t("T�m� lasku l�hetet��n suoraan asiakkaan s�hk�postiin")."! $lasrow[laskunro] $lasrow[nimi]<br>\n";
								}
								elseif ($lasrow["chn"] == "667") {
									$tulos_ulos .= "<br>\n".t("Tehtiin sis�inen lasku")."! $lasrow[laskunro] $lasrow[nimi]<br>\n";
								}
								else {
									$tulos_ulos .= "<br>\n".t("T�m� lasku tulostetaan omalle tulostimelle")."! $lasrow[laskunro] $lasrow[nimi]<br>\n";
								}

							}

							// halutaan l�hett�� lasku suoraan asiakkaalle s�hk�postilla..
							if ($lasrow["chn"] == "666") {
								$tulostettavat_email[] = $lasrow["tunnus"];
							}

							// Halutaan tulostaa itse
							$tulostettavat[] = $lasrow["tunnus"];
							$lask++;
						}
						elseif ($silent == "") {
							$tulos_ulos .= "\n".t("Nollasummaista laskua ei l�hetetty")."! $lasrow[laskunro] $lasrow[nimi]<br>\n";
						}

						// p�ivitet��n kaikki laskut l�hetetyiksi...
						$tquery = "UPDATE lasku SET alatila='X' WHERE (tunnus in ($tunnukset) or tunnus='$lasrow[tunnus]') and yhtio='$kukarow[yhtio]'";
						$tresult = pupe_query($tquery);

					} // end foreach ketjut...

					if ($silent == "") {
						$tulos_ulos .= "<br><br>\n\n";
					}

					//Aineistojen lopput�git
					elmaedi_aineisto_loppu($tootedi, $timestamppi);
					pupevoice_aineisto_loppu($tootxml);
				}
			}
			else {
				$tulos_ulos .= "<br>\n".t("Yht��n laskutettavaa tilausta ei l�ytynyt")."!<br><br>\n";
			}

			// suljetaan faili
			fclose($tootxml);
			fclose($tootedi);
			fclose($tootfinvoice);
			fclose($tootsisainenfinvoice);

			//dellataan failit jos ne on tyhji�
			if (filesize($nimixml) == 0) {
				unlink($nimixml);
			}
			if (filesize($nimifinvoice) == 0) {
				unlink($nimifinvoice);
			}
			if (filesize($nimiedi) == 0) {
				unlink($nimiedi);
			}
			if (filesize($nimisisainenfinvoice) == 0) {
				unlink($nimisisainenfinvoice);
			}

			// poistetaan lukot
			$query = "UNLOCK TABLES";
			$locre = pupe_query($query);

			// jos laskutettiin jotain
			if ($lask > 0) {

				if (count($tulostettavat_apix) > 0 or count($tulostettavat) > 0) {
					require_once("tilauskasittely/tulosta_lasku.inc");
				}

				if ($silent == "" or $silent == "VIENTI") {
					$tulos_ulos .= t("Luotiin")." $lask ".t("laskua").".<br>\n";
				}

				//jos verkkotunnus l�ytyy niin
				if ($yhtiorow['verkkotunnus_lah'] != '' and file_exists(realpath($nimixml))) {

					if ($silent == "") {
						$tulos_ulos .= "<br><br>\n".t("FTP-siirto pupevoice:")."<br>\n";
					}

					//siirretaan laskutiedosto operaattorille
					$ftphost = (isset($verkkohost_lah) and trim($verkkohost_lah) != '') ? $verkkohost_lah : "ftp.verkkolasku.net";
					$ftpuser = $yhtiorow['verkkotunnus_lah'];
					$ftppass = $yhtiorow['verkkosala_lah'];
					$ftppath = (isset($verkkopath_lah) and trim($verkkopath_lah) != '') ? $verkkopath_lah : "out/einvoice/data/";
					$ftpfile = realpath($nimixml);

					// Tehd��n maili, ett� siirret��n laskut operaattorille
					$bound = uniqid(time()."_") ;

					$verkkolasheader  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n";
					$verkkolasheader .= "MIME-Version: 1.0\n" ;
					$verkkolasheader .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

					$verkkolasmail = "--$bound\n";
					$verkkolasmail .= "Content-type: text/plain; charset=iso-8859-1\n";
					$verkkolasmail .= "Content-Transfer-Encoding: quoted-printable\n\n";
					$verkkolasmail .= t("Pvm").": ".date("Y-m-d H:i:s")."\n\n";
					$verkkolasmail .= t("Aineiston laskut").":\n";

					foreach ($verkkolaskuputkeen_pupevoice as $lasnoputk => $nimiputk) {
						$verkkolasmail .= "$lasnoputk - $nimiputk\n";
					}

					$verkkolasmail .= "\n\n".t("FTP-komento").":\n";
					$verkkolasmail .= "ncftpput -u $ftpuser -p $ftppass $ftphost $ftppath $ftpfile\n\n";
					$verkkolasmail .= t("Aineisto liitteen�")."!\n\n\n\n";
					$verkkolasmail .= "--$bound\n";
					$verkkolasmail .= "Content-Type: text/plain; name=\"".basename($ftpfile)."\"\n" ;
					$verkkolasmail .= "Content-Transfer-Encoding: base64\n" ;
					$verkkolasmail .= "Content-Disposition: attachment; filename=\"".basename($ftpfile)."\"\n\n";
					$verkkolasmail .= chunk_split(base64_encode(file_get_contents($ftpfile)));
					$verkkolasmail .= "\n" ;
					$verkkolasmail .= "--$bound--\n";

					$silari = mail($yhtiorow["alert_email"], mb_encode_mimeheader(t("Pupevoice-aineiston siirto Itellaan"), "ISO-8859-1", "Q"), $verkkolasmail, $verkkolasheader, "-f $yhtiorow[postittaja_email]");

					require("inc/ftp-send.inc");

					if ($silent == "") {
						$tulos_ulos .= $tulos_ulos_ftp;
					}
				}
				elseif ($yhtiorow["verkkolasku_lah"] == "apix" and file_exists(realpath($nimifinvoice))) {
					// siirret��n laskutiedosto operaattorille
					#$url           = "https://test-api.apix.fi/invoices";
					$url            = "https://api.apix.fi/invoices";
					$transferkey    = $yhtiorow['apix_avain'];
					$transferid     = $yhtiorow['apix_tunnus'];
					$software       = "Pupesoft";
					$version        = "1.0";
					$timestamp      = gmdate("YmdHis");
					$apixfinvoice   = basename($nimifinvoice);
					$apixzipfile    = "Apix_".$yhtiorow['yhtio']."_invoices_$timestamp.zip";

					// Luodaan temppidirikka jonne ty�nnet��n t�n kiekan kaikki apixfilet
					list($usec, $sec) = explode(' ', microtime());
					mt_srand((float) $sec + ((float) $usec * 100000));
					$apix_tmpdirnimi = "/tmp/apix-".md5(uniqid(mt_rand(), true));

					if (mkdir($apix_tmpdirnimi)) {

						// Siirret��n finvoiceaineisto dirikkaan
						if (!rename("/tmp/".$apixfinvoice, $apix_tmpdirnimi."/".$apixfinvoice)) {
							$tulos_ulos .= "APIX finvoicemove $apixfinvoice feilas!";
						}

						$kaikki_apix_laskunumerot_talteen = "";

						// Luodaan laskupdf:�t
						foreach ($tulostettavat_apix as $apixlasku) {
							list($apixnumero, $apixtmpfile) = tulosta_lasku($apixlasku, $kieli, "VERKKOLASKU_APIX", "", $valittu_tulostin, $valittu_kopio_tulostin);

							// Siirret��n faili apixtemppiin
							if (!rename($apixtmpfile, $apix_tmpdirnimi."/Apix_invoice_$apixnumero.pdf")) {
								$tulos_ulos .= "APIX tmpmove Apix_invoice_$apixnumero.pdf feilas!";
							}

							$kaikki_apix_laskunumerot_talteen .= "$apixlasku ";
						}

						// Tehd��n apixzippi
						exec("cd $apix_tmpdirnimi; zip $apixzipfile *;");

						// Aineisto dataouttiin
						exec("cp $apix_tmpdirnimi/$apixzipfile $pupe_root_polku/dataout/");

						// Poistetaan apix-tmpdir
						exec("rm -rf $apix_tmpdirnimi");

						// Siirret��n aineisto APIXiin
						$digest_src = $software."+".$version."+".$transferid."+".$timestamp."+".$transferkey;

						$dt = substr(hash('sha256', $digest_src), 0, 64);

						$real_url = "$url?soft=$software&ver=$version&TraID=$transferid&t=$timestamp&d=SHA-256:$dt";

						$apixfilesize = filesize("$pupe_root_polku/dataout/$apixzipfile");
						$apix_fh = fopen("$pupe_root_polku/dataout/$apixzipfile", 'r');

						$ch = curl_init($real_url);
						curl_setopt($ch, CURLOPT_PUT, true);
						curl_setopt($ch, CURLOPT_INFILE, $apix_fh);
						curl_setopt($ch, CURLOPT_INFILESIZE, $apixfilesize);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

						$tulos_ulos .= "L�hetet��n aineisto APIX:lle...<br>";
						$response = curl_exec($ch);

						curl_close($ch);
						fclose($apix_fh);

						$xml = simplexml_load_string($response);

						if ($xml->Status == "OK") {
							$tulos_ulos .= "L�hetys onnistui!";
						}
						else {
							$tulos_ulos .= "L�hetys ep�onnistui:<br>";

							$tulos_ulos .= "Tila: ".$xml->Status."<br>";
							$tulos_ulos .= "Tilakoodi: ".$xml->StatusCode."<br>";

							foreach ($xml->FreeText as $teksti) {
								$tulos_ulos .= "Tilaviesti: ".$teksti."<br>";
							}

							$tulos_ulos .= "Laskut: $kaikki_apix_laskunumerot_talteen<br>";
						}
					}
					else {
						$tulos_ulos .= "APIX tmpdirrin teko feilas!<br>";
					}

					if ($silent == "" or $silent == "VIENTI") {
						$tulos_ulos .= $tulos_ulos_ftp;
					}
				}
				elseif ($yhtiorow["verkkolasku_lah"] == "iPost" and file_exists(realpath($nimifinvoice))) {
					if ($silent == "" or $silent == "VIENTI") {
						$tulos_ulos .= "<br><br>\n".t("FTP-siirto iPost Finvoice:")."<br>\n";
					}

					//siirretaan laskutiedosto operaattorille
					$ftphost = "ftp.itella.net";
					$ftpuser = $yhtiorow['verkkotunnus_lah'];
					$ftppass = $yhtiorow['verkkosala_lah'];
					$ftppath = "out/finvoice/data/";
					$ftpfile = realpath($nimifinvoice);
					$renameftpfile = $nimifinvoice_delivered;

					// Tehd��n maili, ett� siirret��n laskut operaattorille
					$bound = uniqid(time()."_") ;

					$verkkolasheader  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n";
					$verkkolasheader .= "MIME-Version: 1.0\n" ;
					$verkkolasheader .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

					$verkkolasmail = "--$bound\n";
					$verkkolasmail .= "Content-type: text/plain; charset=iso-8859-1\n";
					$verkkolasmail .= "Content-Transfer-Encoding: quoted-printable\n\n";
					$verkkolasmail .= t("Pvm").": ".date("Y-m-d H:i:s")."\n\n";
					$verkkolasmail .= t("Aineiston laskut").":\n";

					foreach ($verkkolaskuputkeen_finvoice as $lasnoputk => $nimiputk) {
						$verkkolasmail .= "$lasnoputk - $nimiputk\n";
					}

					$verkkolasmail .= "\n\n".t("FTP-komento").":\n";
					$verkkolasmail .= "mv $ftpfile ".str_replace("TRANSFER_", "DELIVERED_", $ftpfile)."\nncftpput -u $ftpuser -p $ftppass -T T $ftphost $ftppath ".str_replace("TRANSFER_", "DELIVERED_", $ftpfile)."\n\n";
					$verkkolasmail .= t("Aineisto liitteen�")."!\n\n\n\n";
					$verkkolasmail .= "--$bound\n";
					$verkkolasmail .= "Content-Type: text/plain; name=\"".basename($ftpfile)."\"\n" ;
					$verkkolasmail .= "Content-Transfer-Encoding: base64\n" ;
					$verkkolasmail .= "Content-Disposition: attachment; filename=\"".basename($ftpfile)."\"\n\n";
					$verkkolasmail .= chunk_split(base64_encode(file_get_contents($ftpfile)));
					$verkkolasmail .= "\n" ;
					$verkkolasmail .= "--$bound--\n";

					$silari = mail($yhtiorow["alert_email"], mb_encode_mimeheader(t("iPost Finvoice-aineiston siirto Itellaan"), "ISO-8859-1", "Q"), $verkkolasmail, $verkkolasheader, "-f $yhtiorow[postittaja_email]");

					require("inc/ftp-send.inc");

					if ($silent == "" or $silent == "VIENTI") {
						$tulos_ulos .= $tulos_ulos_ftp;
					}
				}
				elseif ($yhtiorow["verkkolasku_lah"] == "finvoice" and file_exists(realpath($nimifinvoice))) {
					if (isset($verkkolaskut_out)) {
						if (is_writable($verkkolaskut_out)) {
							copy(realpath($nimifinvoice), $verkkolaskut_out."/".basename($nimifinvoice));
						}
						else {
							$tulos_ulos .= "<br><br>\n".t("Tiedoston kopiointi ep�onnistui")."!<br>\n";
						}
					}
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

					// Tehd��n maili, ett� siirret��n laskut operaattorille
					$bound = uniqid(time()."_") ;

					$verkkolasheader  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n";
					$verkkolasheader .= "MIME-Version: 1.0\n" ;
					$verkkolasheader .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

					$verkkolasmail = "--$bound\n";
					$verkkolasmail .= "Content-type: text/plain; charset=iso-8859-1\n";
					$verkkolasmail .= "Content-Transfer-Encoding: quoted-printable\n\n";
					$verkkolasmail .= t("Pvm").": ".date("Y-m-d H:i:s")."\n\n";
					$verkkolasmail .= t("Aineiston laskut").":\n";

					foreach ($verkkolaskuputkeen_elmaedi as $lasnoputk => $nimiputk) {
						$verkkolasmail .= "$lasnoputk - $nimiputk\n";
					}

					$verkkolasmail .= "\n\n".t("FTP-komento").":\n";
					$verkkolasmail .= "ncftpput -u $ftpuser -p $ftppass $ftphost $ftppath $ftpfile\n\n";
					$verkkolasmail .= t("Aineisto liitteen�")."!\n\n\n\n";
					$verkkolasmail .= "--$bound\n";
					$verkkolasmail .= "Content-Type: text/plain; name=\"".basename($ftpfile)."\"\n" ;
					$verkkolasmail .= "Content-Transfer-Encoding: base64\n" ;
					$verkkolasmail .= "Content-Disposition: attachment; filename=\"".basename($ftpfile)."\"\n\n";
					$verkkolasmail .= chunk_split(base64_encode(file_get_contents($ftpfile)));
					$verkkolasmail .= "\n" ;
					$verkkolasmail .= "--$bound--\n";

					$silari = mail($yhtiorow["alert_email"], mb_encode_mimeheader(t("EDI-inhouse-aineiston siirto Itellaan"), "ISO-8859-1", "Q"), $verkkolasmail, $verkkolasheader, "-f $yhtiorow[postittaja_email]");

					require("inc/ftp-send.inc");

					if ($silent == "") {
						$tulos_ulos .= $tulos_ulos_ftp;
					}
				}

				if (isset($sisainenfoinvoice_ftphost) and $sisainenfoinvoice_ftphost != '' and file_exists(realpath($nimisisainenfinvoice))) {
					if ($silent == "") {
						$tulos_ulos .= "<br><br>\n".t("FTP-siirto Pupesoft-Finvoice:")."<br>\n";
					}

					//siirretaan laskutiedosto operaattorille, Sis�inenFinvoice muoto
					$ftphost = $sisainenfoinvoice_ftphost;
					$ftpuser = $sisainenfoinvoice_ftpuser;
					$ftppass = $sisainenfoinvoice_ftppass;
					$ftppath = $sisainenfoinvoice_ftppath;
					$ftpfile = realpath($nimisisainenfinvoice);

					// Tehd��n maili, ett� siirret��n laskut operaattorille
					$bound = uniqid(time()."_") ;

					$verkkolasheader  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n";
					$verkkolasheader .= "MIME-Version: 1.0\n" ;
					$verkkolasheader .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

					$verkkolasmail = "--$bound\n";
					$verkkolasmail .= "Content-type: text/plain; charset=iso-8859-1\n";
					$verkkolasmail .= "Content-Transfer-Encoding: quoted-printable\n\n";
					$verkkolasmail .= t("Pvm").": ".date("Y-m-d H:i:s")."\n\n";
					$verkkolasmail .= t("Aineiston laskut").":\n";

					foreach ($verkkolaskuputkeen_suora as $lasnoputk => $nimiputk) {
						$verkkolasmail .= "$lasnoputk - $nimiputk\n";
					}

					$verkkolasmail .= "\n\n".t("FTP-komento").":\n";
					$verkkolasmail .= "ncftpput -u $ftpuser -p $ftppass $ftphost $ftppath $ftpfile\n\n";
					$verkkolasmail .= t("Aineisto liitteen�")."!\n\n\n\n";
					$verkkolasmail .= "--$bound\n";
					$verkkolasmail .= "Content-Type: text/plain; name=\"".basename($ftpfile)."\"\n" ;
					$verkkolasmail .= "Content-Transfer-Encoding: base64\n" ;
					$verkkolasmail .= "Content-Disposition: attachment; filename=\"".basename($ftpfile)."\"\n\n";
					$verkkolasmail .= chunk_split(base64_encode(file_get_contents($ftpfile)));
					$verkkolasmail .= "\n" ;
					$verkkolasmail .= "--$bound--\n";

					$silari = mail($yhtiorow["alert_email"], mb_encode_mimeheader(t("Pupesoft-Finvoice-aineiston siirto eteenp�in"), "ISO-8859-1", "Q"), $verkkolasmail, $verkkolasheader, "-f $yhtiorow[postittaja_email]");

					require("inc/ftp-send.inc");

					if ($silent == "") {
						$tulos_ulos .= $tulos_ulos_ftp;
					}
				}

				// jos yhti�ll� on laskuprintteri on m��ritelty tai halutaan jostain muusta syyst� tulostella laskuja paperille
				if (($yhtiorow['lasku_tulostin'] > 0 or $yhtiorow['lasku_tulostin'] == -99) or (isset($valittu_tulostin) and $valittu_tulostin != "") or count($tulostettavat_email) > 0) {

					if ((!isset($valittu_tulostin) or $valittu_tulostin == "") and ($yhtiorow['lasku_tulostin'] > 0 or $yhtiorow['lasku_tulostin'] == -99)) {
						$valittu_tulostin = $yhtiorow['lasku_tulostin'];
					}

					if ($silent == "") $tulos_ulos .= "<br>\n".t("Tulostetaan paperilaskuja").":<br>\n";

					foreach ($tulostettavat as $lasku) {

						if ($silent == "") $tulos_ulos .= t("Tulostetaan lasku").": $lasku<br>\n";

						$vientierittelymail    = "";
						$vientierittelykomento = "";

						tulosta_lasku($lasku, $kieli, "VERKKOLASKU", "", $valittu_tulostin, $valittu_kopio_tulostin, $tulostettavat_email, $saatekirje);

						$query = "  SELECT *
									FROM lasku
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus = '$lasku'";
						$laresult = pupe_query($query);
						$laskurow = mysql_fetch_assoc($laresult);

						if ($laskurow["vienti"] == "E" and $yhtiorow["vienti_erittelyn_tulostus"] != "E") {
							$uusiotunnus = $laskurow["tunnus"];

							require('tulosta_vientierittely.inc');

							//keksit��n uudelle failille joku varmasti uniikki nimi:
							list($usec, $sec) = explode(' ', microtime());
							mt_srand((float) $sec + ((float) $usec * 100000));
							$pdffilenimi = "/tmp/Vientierittely-".md5(uniqid(mt_rand(), true)).".pdf";

							//kirjoitetaan pdf faili levylle..
							$fh = fopen($pdffilenimi, "w");
							if (fwrite($fh, $Xpdf->generate()) === FALSE) die("PDF kirjoitus ep�onnistui $pdffilenimi");
							fclose($fh);

							if ($vientierittelykomento == "email" or $vientierittelymail != "") {
								// l�hetet��n meili
								if ($vientierittelymail != "") {
									$komento = $vientierittelymail;
								}
								else {
									$komento = "";
								}

								$kutsu = t("Lasku", $kieli)." $lasku ".t("Vientierittely", $kieli);

								if ($yhtiorow["liitetiedostojen_nimeaminen"] == "N") {
									$kutsu .= ", ".trim($laskurow["nimi"]);
								}

								$liite              = $pdffilenimi;
								$sahkoposti_cc      = "";
								$content_subject    = "";
								$content_body       = "";
								include("inc/sahkoposti.inc"); // sanotaan include eik� require niin ei kuolla
							}
							elseif ($vientierittelykomento != '' and $vientierittelykomento != 'edi') {
								// itse print komento...
								$line = exec("$vientierittelykomento $pdffilenimi");
							}

							//poistetaan tmp file samantien kuleksimasta...
							system("rm -f $pdffilenimi");

							if ($silent == "") $tulos_ulos .= t("Vientierittely tulostuu")."...<br>\n";

							unset($Xpdf);
						}
					}
				}
			}
			elseif ($silent == "") {
				$tulos_ulos .= t("Yht��n laskua ei siirretty/tulostettu!")."<br>\n";
			}

			// l�hetet��n meili vaan jos on jotain laskutettavaa ja ollaan tultu komentorivilt�
			if ($lask > 0 and $php_cli) {

				//echotaan ruudulle ja l�hetet��n meili yhtiorow[admin]:lle
				$bound = uniqid(time()."_") ;

				$header  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n";
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

				mail($yhtiorow["alert_email"],  mb_encode_mimeheader("$yhtiorow[nimi] - Laskutusajo", "ISO-8859-1", "Q"), $content, $header, "-f $yhtiorow[postittaja_email]");
			}
		}

		if (!$php_cli) {
			echo "$tulos_ulos";

			// Annetaan mahdollisuus tallentaa finvoicetiedosto jos se on luotu..
			if (file_exists($nimifinvoice) and (strpos($_SERVER['SCRIPT_NAME'], "verkkolasku.php") !== FALSE or strpos($_SERVER['SCRIPT_NAME'], "valitse_laskutettavat_tilaukset.php") !== FALSE) and $yhtiorow["verkkolasku_lah"] == "finvoice") {
				echo "<br><table><tr><th>".t("Tallenna finvoice-aineisto").":</th>";
				echo "<form method='post' action='$PHP_SELF'>";
				echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
				echo "<input type='hidden' name='kaunisnimi' value='".basename($nimifinvoice)."'>";
				echo "<input type='hidden' name='filenimi' value='".basename($nimifinvoice)."'>";
				echo "<td><input type='submit' value='".t("Tallenna")."'></td></tr></form></table>";
			}
		}

		if ($tee == '' and strpos($_SERVER['SCRIPT_NAME'], "verkkolasku.php") !== FALSE) {

			//p�iv�m��r�n tarkistus
			$tilalk = explode("-", $yhtiorow["myyntireskontrakausi_alku"]);
			$tillop = explode("-", $yhtiorow["myyntireskontrakausi_loppu"]);

			$tilalkpp = $tilalk[2];
			$tilalkkk = $tilalk[1]-1;
			$tilalkvv = $tilalk[0];

			$tilloppp = $tillop[2];
			$tillopkk = $tillop[1]-1;
			$tillopvv = $tillop[0];

			$tanaanpp = date("d");
			$tanaankk = date("m")-1;
			$tanaanvv = date("Y");

			echo "  <SCRIPT LANGUAGE=JAVASCRIPT>

						function verify(){
							var pp = document.lasku.laskpp;
							var kk = document.lasku.laskkk;
							var vv = document.lasku.laskvv;

							pp = Number(pp.value);
							kk = Number(kk.value)-1;
							vv = Number(vv.value);

							if (vv == 0 && pp == 0 && kk == -1) {
								var tanaanpp = $tanaanpp;
								var tanaankk = $tanaankk;
								var tanaanvv = $tanaanvv;

								var dateSyotetty = new Date(tanaanvv, tanaankk, tanaanpp);
							}
							else {
								if (vv > 0 && vv < 1000) {
									vv = vv+2000;
								}

								var dateSyotetty = new Date(vv,kk,pp);
							}

							var dateTallaHet = new Date();
							var ero = (dateTallaHet.getTime() - dateSyotetty.getTime()) / 86400000;

							var tilalkpp = $tilalkpp;
							var tilalkkk = $tilalkkk;
							var tilalkvv = $tilalkvv;
							var dateTiliAlku = new Date(tilalkvv,tilalkkk,tilalkpp);
							dateTiliAlku = dateTiliAlku.getTime();


							var tilloppp = $tilloppp;
							var tillopkk = $tillopkk;
							var tillopvv = $tillopvv;
							var dateTiliLoppu = new Date(tillopvv,tillopkk,tilloppp);
							dateTiliLoppu = dateTiliLoppu.getTime();

							dateSyotetty = dateSyotetty.getTime();

							if (dateSyotetty < dateTiliAlku || dateSyotetty > dateTiliLoppu) {
								var msg = '".t("VIRHE: Sy�tetty p�iv�m��r� ei sis�lly kuluvaan tilikauteen!")."';

								if (alert(msg)) {
									return false;
								}
								else {
									return false;
								}
							}
							if (ero >= 2) {
								var msg = '".t("Oletko varma, ett� haluat p�iv�t� laskun yli 2pv menneisyyteen?")."';
								return confirm(msg);
							}
							if (ero < 0) {
								var msg = '".t("VIRHE: Laskua ei voi p�iv�t� tulevaisuuteen!")."';

								if (alert(msg)) {
									return false;
								}
								else {
									return false;
								}
							}
						}
					</SCRIPT>";


			echo "<br>\n<table>";

			// Mik� viikonp�iv� t�n��n on 1-7.. 1=sunnuntai, 2=maanantai, jne...
			$today = date("w") + 1;

			// Kuukauden eka p�iv�
			$eka_pv = laskutuspaiva("eka");

			// Kuukauden keskimm�inen p�iv�
			$keski_pv = laskutuspaiva("keski");

			// Kuukauden viimeinen p�iv�
			$vika_pv = laskutuspaiva("vika");

			$lasklisa .= "
							    ";

			$query = "  SELECT
						sum(if (lasku.laskutusvkopv = '0', 1, 0)) normaali,
						sum(if (((lasku.laskutusvkopv = $today) or
						   		 (lasku.laskutusvkopv = -1 and curdate() = '$vika_pv') or
						   		 (lasku.laskutusvkopv = -2 and curdate() = '$eka_pv') or
						   		 (lasku.laskutusvkopv = -3 and curdate() = '$keski_pv') or
						   		 (lasku.laskutusvkopv = -4 and curdate() in ('$keski_pv','$vika_pv')) or
						   		 (lasku.laskutusvkopv = -5 and curdate() in ('$eka_pv','$keski_pv'))), 1, 0)) paiva,
						sum(if (maksuehto.factoring != '', 1, 0)) factoroitavat,
						count(lasku.tunnus) kaikki
						from lasku
						LEFT JOIN maksuehto ON lasku.yhtio=maksuehto.yhtio and lasku.maksuehto=maksuehto.tunnus
						where lasku.yhtio   = '$kukarow[yhtio]'
						and lasku.tila  = 'L'
						and lasku.alatila   = 'D'
						and lasku.viite = ''
						and lasku.chn   != '999'";
			$res = pupe_query($query);
			$row = mysql_fetch_assoc($res);

			echo "<form action = '$PHP_SELF' method = 'post' name='lasku' onSubmit = 'return verify()'>
				<input type='hidden' name='tee' value='TARKISTA'>";

			echo "<tr><th>".t("Laskutettavia tilauksia joilla on laskutusviikonp�iv� t�n��n").":</th><td colspan='3'>$row[paiva]</td></tr>\n";
			echo "<tr><th>".t("Laskutettavia tilauksia joiden laskutusviikonp�iv� ei ole t�n��n").":</th><td colspan='3'>".($row["kaikki"]-$row["normaali"]-$row["paiva"])."</td></tr>\n";
			echo "<tr><th>".t("Laskutettavia tilauksia joilla EI ole laskutusviikonp�iv��").":</th><td colspan='3'>$row[normaali]</td></tr>\n";
			echo "<tr><th>".t("Laskutettavia tilauksia jotka siirret��n rahoitukseen").":</th><td colspan='3'>$row[factoroitavat]</td></tr>\n";
			echo "<tr><th>".t("Laskutettavia tilauksia kaikkiaan").":</th><td colspan='3'>$row[kaikki]</td></tr>\n";

			echo "<tr><th>".t("Sy�t� poikkeava laskutusp�iv�m��r� (pp-kk-vvvv)")."</th>
					<td><input type='text' name='laskpp' value='' size='3'></td>
					<td><input type='text' name='laskkk' value='' size='3'></td>
					<td><input type='text' name='laskvv' value='' size='5'></td></tr>\n";

			if ($yhtiorow["myyntilaskun_erapvmlaskenta"] == "K") {
				echo "<tr><th>".t("Laske er�p�iv�").":</th>
						<td colspan='3'><select name='erpcmlaskenta'>";
				echo "<option value=''>".t("Er�p�iv� lasketaan laskutusp�iv�st�")."</option>";
				echo "<option value='NOW'>".t("Er�p�iv� lasketaan t�st� hetkest�")."</option>";
				echo "</select></td></tr>\n";
			}

			echo "<tr><th>".t("Ohita laskujen laskutusviikonp�iv�t").":</th><td colspan='3'><input type='checkbox' name='laskutakaikki'></td></tr>\n";

			echo "<tr><th>".t("Laskuta vain tilaukset, lista pilkulla eroteltuna").":</th><td colspan='3'><textarea name='laskutettavat' rows='10' cols='60'></textarea></td></tr>";

			echo "</table>";

			echo "<br>\n<input type='submit' value='".t("Jatka")."'>";
			echo "</form>";
		}
	}

	if (!$php_cli and strpos($_SERVER['SCRIPT_NAME'], "verkkolasku.php") !== FALSE) {
		require("inc/footer.inc");
	}

?>