<?php

	// nyt on kaikki tiedot rahtikirjaa varten haettu..
	//
	// arrayt:
	// toitarow, otsikot, pakkaus, kilot, kollit, kuutiot, lavametri, vakit
	// $rakir_row:sta löytyy asiakkaan tiedot ja $rivi:stä ytunnus
	//
	// muuttujat:
	// otunnukset, rahdinmaksaja, rahtihinta, pvm, toimitustapa, kollityht, kilotyht, kuutiotyht, kirjoitin
	// jv tapauksissa on myös yhteensa, summa, jvhinta, lasno ja viite muuttujat
	//
	// tulostetaan rahtikirja

	// Setataan tarvittavat muuttujat
	require_once("rahtikirja_postitarra_otsikkotiedot.inc");

	if ($tulostuskpl == 0) {
		$tulostakolli = 1;
	}
	else {
		$tulostakolli = $tulostuskpl;
	}

	// tulostetaan niin monta lappua kun on kollejakin
	for ($tulostuskpl=1; $tulostuskpl<=$tulostakolli; $tulostuskpl++) {

		$kuka    = sprintf("%02d",$tulostuskpl); 		// laitetaan tulostuskpl alkuun niin jokalapulla on uniikki viiva

		if (substr($rivi["rahtikirjanro"], 0, 4) == "JJFI") {
			$oma = sprintf("%09d", substr($rivi["rahtikirjanro"], -9));
		}
		else {
			$oma = sprintf("%09d",$rtunnus); 			// 9, pitkä numero tulee rahtikirja taulun tunnuksesta, pitää olaa uniikki
		}

		// tehdään viivakoodit
		$viiva1  = ">6JJFI";
		$viiva1 .= substr($toitarow['sopimusnro'], 0, 1);
		$viiva1 .= ">5";
		$viiva1 .= substr($toitarow['sopimusnro'], 1);
		$viiva1 .= $kuka.$oma;

		$viivaedi = "JJFI$toitarow[sopimusnro]$kuka$oma";

		$viiva2   = "JJFI $toitarow[sopimusnro] $kuka$oma";
		$v2_alku  = substr($viiva2, 0, -6);
		$v2_loppu = substr($viiva2, -6);

		$keltainen_parts = explode(" ", $keltainen);
		$keltainen1 = array_shift($keltainen_parts);
		$keltainen2 = implode(" ", $keltainen_parts);

		// erotellaan spacella jokainen rahtikirjanumero
		$rahtikirjanro .= "$viiva2 ";

		$sivu = "*** ETIKETIN KOKO ***
                 {D2280,1040,2250|}
                 {AX;+000,+000,+00|}
                 {AY;+10,0|}
                 {C|}

                 *** LOGO ***
                 {PC085;0025,0068,1,1,H,00,B|}
			     {RC085;Itella|}

                 *** KIINTEÄÄ TEKSTIÄ JA VIIVOJA ***
                 {PC004;0060,0168,05,05,H,00,B|}
                 {RC004;Lähettäjä Avsändare From|}
                 {PC005;0060,0369,05,05,H,00,B|}
                 {RC005;Vastaanottaja Adressat To   Puh.|}
                 {PC008;0810,0409,05,05,A,00,B|}
                 {RC008;Päivämäärä|}
                 {PC009;0810,0429,05,05,A,00,B|}
                 {RC009;Datum|}
                 {PC010;0975,0529,05,05,H,00,B|}
                 {RC010;kg|}
                 {PC011;0975,0589,05,05,H,00,B|}
                 {RC011;m3|}
                 {PC012;0057,0631,05,10,G,00,B|}
                 {RC012;Lisäpalvelut  Tilläggstjänster|}
                 {PC013;0340,0626,05,10,G,00,B|}
                 {RC013;Maksaja muu kuin lähettäjä  Betalaren annan än avsändaren|}
                 {PC014;0887,0633,05,05,H,00,B|}
                 {RC014;Kpl St.|}
                 {PC015;0057,0709,05,10,G,00,B|}
                 {RC015;PE-summa PF-belopp|}
                 {PC016;0380,0738,05,05,H,00,B|}
                 {RC016;|}
                 {PC017;0480,0709,05,10,G,00,B|}
                 {RC017;Tilinumero Kontonummer|}
 				 {PC018;0057,0775,05,10,G,00,B|}
                 {RC018;Pankkiviite Bank Referens|}
				 {PC018;0740,0775,05,10,G,00,B|}
                 {RC018;BIC|}
				 {PC019;0062,1205,05,05,I,00,B|}
                 {RC019;SAAPUMISILMOITUS  ANKOMSTAVI|}";

		if (count($vakit) > 0 or $toitarow['kuljyksikko'] != '') {
			$sivu .= "{PC020;0060,1235,05,10,G,00,B|}
	                 {RC020;Lisätietoja 0200 77000, www.itella.com * Mera information 0200 75000, www.itella.com.|}
	                 {PC021;0060,1261,05,10,G,00,B|}
	                 {RC021;Säilytysaika on yksi (1) täysi kalenteriviikko * Kvarligger en (1) hel kalendervecka.|}
	                 {PC022;0060,1287,05,10,G,00,B|}
	                 {RC022;|}";
		}
		else {
			$sivu .= "{PC020;0060,1235,05,10,G,00,B|}
	                 {RC020;Lähetys on noudettavissa Postistanne. Lisätietoja 0200 77000, www.itella.com.|}
	                 {PC021;0060,1261,05,10,G,00,B|}
	                 {RC021;Ni kan hämta försändelsen på Ert postkontor. Mera information 0200 75000, www.itella.com.|}
	                 {PC022;0060,1287,05,10,G,00,B|}
	                 {RC022;Säilytysaika on 2 täyttä kalenteriviikkoa. Kvarligger 2 hela kalenderveckor.|}";
		}

		$sivu .= "

				 {PC023;0060,1319,05,05,H,00,B|}
                 {RC023;Lähettäjä Avsändare From|}
                 {PC123;0690,1319,05,05,H,00,B|}
                 {RC123;Lisätiedot Tilläggsuppgifter|}
                 {PC024;0060,1389,05,05,H,00,B|}
                 {RC024;Vastaanottaja Adressat To   Puh.|}
                 {PC025;0975,1620,05,05,H,00,B|}
                 {RC025;kg|}
                 {PC026;0815,1524,05,05,H,00,B|}
                 {RC026;Pvm Dat.|}
                 {PC027;0805,1588,05,05,H,00,B|}
                 {RC027;Kpl St.|}
                 {PC028;0055,1655,05,10,G,00,B|}
                 {RC028;Sisältö Innehåll|}
                 {PC029;0055,1709,05,10,G,00,B|}
                 {RC029;PE-summa PF-belopp|}
                 {PC030;0375,1739,05,05,H,00,B|}
                 {RC030;|}
                 {PC031;0465,1709,05,10,G,00,B|}
                 {RC031;Tilinumero Kontonummer|}
                 {PC032;0055,1775,05,10,G,00,B|}
                 {RC032;Pankkiviite Bank Referens|}
				 {PC032;0740,1775,05,10,G,00,B|}
                 {RC032;BIC|}
                 {PC033;0055,1830,05,10,G,00,B|}
                 {RC033;Kuittaus ja nimen selvennys Kvittering och namnförtydligande|}
                 {PC035;0340,0651,05,10,G,00,B|}
                 {RC035;Maksajan sopimustunnus|}
                 {PC036;0341,0677,05,10,G,00,B|}
                 {RC036;Betalarens avtalskod|}
                 {PC086;0285,1890,05,05,H,00,B|}
                 {RC086;klo kl.|}
                 {LC;0050,0748,1000,0748,0,2|}
                 {LC;0050,1293,1000,1293,0,2|}
                 {LC;0050,1628,1000,1628,0,2|}
                 {LC;0050,1683,1000,1683,0,2|}
                 {LC;0050,1748,1000,1748,0,2|}
                 {LC;0050,1803,1000,1803,0,2|}
                 {LC;0050,0683,1000,0683,0,2|}
                 {LC;0050,0603,1000,0603,0,2|}
                 {LC;0050,0808,1000,0808,0,2|}
                 {LC;0335,0606,0335,0681,0,2|}
                 {LC;0880,0606,0880,0681,0,2|}
                 {LC;0880,1565,0880,1628,0,2|}
                 {LC;0470,0684,0470,0749,0,2|}
                 {LC;0810,0543,1000,0543,0,2|}
                 {LC;0810,0478,1000,0478,0,2|}
                 {LC;0050,1177,1000,1177,0,2|}
                 {LC;0810,1563,1000,1563,0,2|}
                 {LC;0810,1498,1000,1498,0,2|}
                 {LC;0460,1685,0460,1750,0,2|}
				 {LC;0730,0750,0730,0805,0,2|}
   			     {LC;0730,0750,0730,0805,0,2|}
				 {LC;0730,1750,0730,1803,0,2|}
                 {XR;0050,0637,0101,0683,B|}
                 {XR;0053,0639,0099,0681,B|}
                 {XR;0115,0637,0166,0683,B|}
                 {XR;0118,0639,0164,0681,B|}
                 {XR;0180,0637,0231,0683,B|}
                 {XR;0183,0639,0229,0681,B|}
                 {XR;0245,0637,0297,0683,B|}
                 {XR;0248,0639,0294,0681,B|}

                 *** SEURANTA VIIVAKOODI ***
                 {XB00;0058,0845,A,3,04,0,0300,+0000000000,000,0,00|}
                 {RB00;$viiva1|}
				 {PC037;0250,0837,07,07,Q,00,B|}
                 {RC037;$v2_alku|}
				 {PC037;0660,0837,06,06,R,00,B|}
                 {RC037;$v2_loppu|}
                 {PC038;0250,1172,07,07,Q,00,B|}
                 {RC038;$v2_alku|}
				 {PC038;0660,1172,06,06,R,00,B|}
                 {RC038;$v2_loppu|}

				 *** PALVELUN NIMI JA NOPEUS ***
                 {PC000;0180,0060,05,05,K,00,B|}
                 {RC000;$keltainen1|}

				 {PC001;0180,0090,05,05,K,00,B|}
                 {RC001;$keltainen2|}

                 {PC001;0180,0120,05,05,K,00,B|}
                 {RC001;$kuljetus|}";

		if (strlen($keku) > 2) $kpos = "0355";
		else $kpos = "0430";

		// Laitetaan kuljetusohje
		if ($kuljetusohjeet != "") $vakit[6] = $kuljetusohjeet;

		$sivu .= "{PC003;$kpos,0090,15,15,H,00,B|}
                 {RC003;$keku|}
				 {PC115;0600,1205,05,05,I,00,B|}
                 {RC115;$keltainen $kuljetus $keku|}

                 {PC078;0055,1890,05,05,K,00,B|}
                 {RC078;   .   .|}

                 *** TUOTEVIIVAKOODI (2W) ***
                 {XB01;0548,0028,A,3,04,0,0150,+0000000000,000,0,00|}
                 {RB01;$tuoteviiva2|}
                 {PC090;0705,0208,07,07,Q,00,B|}
                 {RC090;$tuoteviiva1|}

                 *** LISÄTIETO-OSA EDI/2W (Otsikko) ***
                 {PC034;0060,1930,05,05,H,00,B|}
                 {RC034;Lisätiedot Tilläggsuppgifter|}
				 {PC033;0060,2250,08,08,H,00,B|}
                 {RC033;Itella Green - ilmastoystävällinen kuljetus|}

                 *** MUUTTUVAA TIETOA ***
                 {PC039;0060,0204,05,05,I,00,B|}
                 {RC039;$yhtiorow[nimi]|}
                 {PC040;0060,0236,05,05,I,00,B|}
                 {RC040;$yhtiorow[osoite]|}
                 {PC041;0060,0266,05,05,I,00,B|}
                 {RC041;$yhtiorow[postino] $yhtiorow[postitp]|}
                 {PC042;0060,0296,05,05,I,00,B|}
                 {RC042;Puh. $yhtiorow[puhelin]|}
                 {PC043;0060,0326,05,05,I,00,B|}
                 {RC043;                      |}
                 {PC045;0060,0403,05,05,I,00,B|}
                 {RC045;$rakir_row[toim_nimi]|}

  				 {PC045;0440,0370,05,05,I,00,B|}
                 {RC045;$rakir_row[puhelin]|}

                 {PC046;0060,0440,05,05,I,00,B|}
                 {RC046;$rakir_row[toim_nimitark]|}
                 {PC047;0060,0478,05,05,I,00,B|}
                 {RC047;$rakir_row[toim_osoite]|}
                 {PC048;0060,0517,05,05,I,00,B|}
                 {RC048;                                |}
                 {PC049;0060,0554,05,05,I,00,B|}
                 {RC049;                                |}
                 {PC050;0060,0597,05,05,K,00,B|}
                 {RC050;$rakir_row[toim_maa]-$rakir_row[toim_postino] $rakir_row[toim_postitp]|}
                 {PC051;0815,0468,05,05,I,00,B|}
                 {RC051;".date("d.m.Y")."|}
                 {PC052;0815,0528,05,05,I,00,B|}
                 {RC052;$kilotyht|}
                 {PC053;0815,0588,05,05,I,00,B|}
                 {RC053;$kuutiotyht|}
                 {PC054;0065,0672,05,05,K,00,B|}
                 {RC054;$x[0]|}
                 {PC055;0130,0672,05,05,K,00,B|}
                 {RC055;$x[1]|}
                 {PC056;0195,0672,05,05,K,00,B|}
                 {RC056;$x[2]|}
                 {PC057;0260,0672,05,05,K,00,B|}
                 {RC057;$x[3]|}
                 {PC084;0606,0667,05,05,I,00,B|}
                 {RC084;$rahdinmaksaja|}
                 {PC058;0890,0673,05,05,I,00,B|}
                 {RC058;$kollityht|}
                 {PC059;0060,0737,05,05,I,00,B|}
                 {RC059;".$postiennakkomaara."|}
                 {PC060;0425,0738,05,05,H,00,B|}
                 {RC060;eur|}
                 {PC061;0480,0737,05,05,I,00,B|}
                 {RC061;".$postiennakkotilino."|}
                 {PC062;0350,0792,05,05,I,00,B|}
                 {RC062;".$postiennakkoviite."|}
                 {PC063;0060,1353,05,05,I,00,B|}
                 {RC063;$yhtiorow[nimi]|}
                 {PC064;0060,1428,05,05,I,00,B|}
                 {RC064;$rakir_row[toim_nimi]|}

				 {PC045;0440,1390,05,05,I,00,B|}
                 {RC045;$rakir_row[puhelin]|}

                 {PC065;0060,1465,05,05,I,00,B|}
                 {RC065;$rakir_row[toim_nimitark]|}
                 {PC066;0060,1504,05,05,I,00,B|}
                 {RC066;$rakir_row[toim_osoite]|}
                 {PC067;0060,1542,05,05,I,00,B|}
                 {RC067;                                  |}
                 {PC068;0060,1579,05,05,I,00,B|}
                 {RC068;                                  |}
                 {PC069;0060,1617,05,05,K,00,B|}
                 {RC069;$rakir_row[toim_maa]-$rakir_row[toim_postino] $rakir_row[toim_postitp]|}
                 {PC080;0650,1540,05,05,K,00,B|}
                 {RC080;|}
                 {PC070;0893,1615,05,05,I,00,B|}
                 {RC070;$kilotyht|}
                 {PC071;0810,1553,05,05,I,00,B|}
                 {RC071;|}
                 {PC072;0815,1618,05,05,I,00,B|}
                 {RC072;$kollityht|}
                 {PC073;0220,1674,05,05,H,00,B|}
                 {RC073;".$tahan_tulisi_paketin_sisalto."|}
                 {PC074;0060,1738,05,05,I,00,B|}
                 {RC074;".$postiennakkomaara."|}
                 {PC075;0415,1739,05,05,H,00,B|}
                 {RC075;eur|}
                 {PC076;0470,1738,05,05,I,00,B|}
                 {RC076;".$postiennakkotilino."|}
                 {PC077;0350,1793,05,05,I,00,B|}
                 {RC077;".$postiennakkoviite."|}

                 *** LISÄTIETO-OSA EDI/2W (Ei Viivakoodia) ***
                 {PC079;0060,1980,05,05,H,00,B|}
                 {RC079;$vakit[0]|}
                 {PC080;0060,2010,05,05,H,00,B|}
                 {RC080;$vakit[1]|}
                 {PC081;0060,2040,05,05,H,00,B|}
                 {RC081;$vakit[2]|}
                 {PC082;0060,2070,05,05,H,00,B|}
                 {RC082;$vakit[3]|}
                 {PC083;0060,2100,05,05,H,00,B|}
                 {RC083;$vakit[4]|}
                 {PC084;0060,2130,05,05,H,00,B|}
                 {RC084;$vakit[5]|}
                 {PC085;0060,2160,05,05,H,00,B|}
                 {RC085;$vakit[6]|}";

		if ($postiedihost != "" and $postiediuser != "" and $postiedipass != "" and $postiedipath != "") {
        	$sivu .= "***EDI ***
                 	 {PC121;0800,0350,15,15,K,00,B|}
                 	 {RC121;EDI|}";
		}

        $sivu .= "*** TULOSTUS  ***
                 {XS;I,0001,0002C3210|}
                 ".chr(12);

		$sivu = escapeshellarg($sivu);

		if ($kirjoitin == "email") {
			echo t("Rahtikirjaa ei voida tulostaa sähköpostiin")."<br>";
		}
		else {
			$line = exec("echo \"$sivu\" | $kirjoitin");
		}

		//lähetetään postin edisanoma
		if (strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE) {
			require("postiedi.inc");
		}
	}

?>