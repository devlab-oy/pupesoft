<?php

	// nyt on kaikki tiedot rahtikirjaa varten haettu..
	//
	// arrayt:
	// toitarow, lotsikot, pakkaus, kilot, kollit, kuutiot, lavametri, vakit
	// $rakir_row:sta löytyy asiakkaan tiedot
	//
	// muuttujat:
	// otunnukset, rahdinmaksaja, pvm, toimitustapa, kolliyht, kilotyht, kuutiotyht, kirjoitin
	// mehto sisältää maksuehdon tiedot
	// jv tapauksissa on myös yhteensa, summa, jvhinta, lasno ja viite muuttujat
	//
	// tulostetaan rahtikirja

	$query = "	SELECT lasku.*, asiakas.toim_ovttunnus, asiakas.toim_nimi, asiakas.toim_postitp
				FROM lasku
				JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus)
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				AND lasku.tunnus in ($otunnukset)
				ORDER BY lasku.ytunnus, lasku.tunnus";
	$tempr = mysql_query($query) or pupe_error($query);

	//PDF:n luonti ja defaultit
	define (__TRACE_ENABLED__, false);
	define (__DEBUG_ENABLED__, false);

	require_once("pdflib/phppdflib.class.php");

	//PDF parametrit
	$pdf = new pdffile;
	$pdf->set_default('margin-top', 	0);
	$pdf->set_default('margin-bottom', 	0);
	$pdf->set_default('margin-left', 	0);
	$pdf->set_default('margin-right', 	0);
	$rectparam["width"] = 0.3;

	//fontit
	$norm["height"] = 6;
	$norm["font"] = "Times-Roman";

	$kirj["height"] = 8;
	//$kirj["font"] = "Times-Roman";
	$kirj["font"] = "Times-Bold";
	
	$iso_non_bold['height'] = 10; 
	$iso_non_bold['font'] = 'Times-Roman';

	$iso["height"] = 12;
	$iso["font"] = "Helvetica-Bold";

	$huge["height"] = 22;
	$huge["font"] = "Helvetica-Bold";

	if (!function_exists('alku_erittely')) {
		function alku_erittely ($pdf, $asiakkaan_tiedot_row, $kieli = '') {
			global $yhtiorow, $kukarow, $firstpage, $rectparam, $norm, $kirj, $iso, $kala, $vanhakala, $asiakas;

			$lahete = $asiakkaan_tiedot_row['tunnus']; //Laitetaan laheteen numero rahtikirjan alku_erittelyun
			$asiakas = $asiakkaan_tiedot_row['ytunnus'];

		   	$pdf->draw_rectangle($kala, 60, $kala-50, 540, $firstpage, $rectparam);
		   	$pdf->draw_text(62, $kala-8, "Asiakas",	$firstpage, $norm);
		   	$pdf->draw_text(62, $kala-18, "Ytunnus ".$asiakkaan_tiedot_row['ytunnus'],	$firstpage, $kirj);
		   	$pdf->draw_text(62, $kala-28, $asiakkaan_tiedot_row['nimi']." ".$asiakkaan_tiedot_row['nimitark'],	$firstpage, $kirj);
		   	$pdf->draw_text(62, $kala-38, $asiakkaan_tiedot_row['osoite'],	$firstpage, $kirj);
		   	$pdf->draw_text(62, $kala-48, $asiakkaan_tiedot_row['postino']." ".$asiakkaan_tiedot_row['postitp'],	$firstpage, $kirj);
			
			$pdf->draw_text(244, $kala-10, 'Allekirjoitus ja nimenselvennys', $firstpage, $iso_non_bold);

			$kala -= 57; // kala = 713
			$vanhakala = $kala;

			//kollitietot
			//ylärivi
			$pdf->draw_rectangle($kala, 60, $kala-25, 110, $firstpage, $rectparam);
			$pdf->draw_text(62, $kala-8, "Myymälänro",	$firstpage, $norm);
			$pdf->draw_text(62, $kala-14, "Astilnro",	$firstpage, $norm);
			$pdf->draw_rectangle($kala, 110, $kala-25, 242,	$firstpage, $rectparam);
			$pdf->draw_text(113, $kala-8, "Myymälän nimi",	$firstpage, $norm);
			$pdf->draw_rectangle($kala, 242, $kala-25, 415,	$firstpage, $rectparam);
			$pdf->draw_text(244, $kala-8, "Kolliluku ja -laji", $firstpage, $norm);
			$pdf->draw_text(244, $kala-14, "Kolliantal och -slag", $firstpage, $norm);
			$pdf->draw_rectangle($kala, 415, $kala-25, 450,	$firstpage, $rectparam);
			$pdf->draw_text(422, $kala-8, "Brutto kg", $firstpage, $norm);
			$pdf->draw_rectangle($kala, 450, $kala-25, 540, $firstpage, $rectparam);
			$pdf->draw_text(453, $kala-8, "Tilavuus, m3", $firstpage, $norm);
			$pdf->draw_text(453, $kala-14, "Tilavuus, m3", $firstpage, $norm);

			$kala -= 36;

			return $pdf;
		}
	}

	// tehdään pdf:n uusi sivu
	$firstpage = $pdf->new_page("a4");

	$lahete = '';
	$asiakas = '';
	$ed_tun = '';
	$kollityht = 0;
	$kilotyht = 0;

	$kala = 810;
	$vanhakala = 810;

	$rivi = 1;
	$maxrivit = mysql_num_rows($tempr);

	$asiakkaan_tilausnumerot = array();
	
	while ($asiakkaan_tiedot_row = mysql_fetch_assoc($tempr)) {

		$asiakkaan_tilausnumerot[] = $asiakkaan_tiedot_row['tunnus'];
		
		if ($asiakas == '' or $asiakas != $asiakkaan_tiedot_row['ytunnus'] or ($asiakas == $asiakkaan_tiedot_row['ytunnus'] and $asiakkaan_tiedot_row['tunnus'] != $ed_tun)) {

			if ($kala < 80) {
				$pdf->draw_rectangle($vanhakala, 60, $kala-22, 540,	$firstpage, $rectparam);

				// tehdään pdf:n uusi sivu
				$firstpage = $pdf->new_page("a4");
				// sitten aletaan piirtämään itse PDF sisältöä
				$pdf->draw_text(302, 812, "RAHTIKIRJAERITTELY", $firstpage, $iso);
				$kala = 810;
				$vanhakala = 810;
				$pdf = alku_erittely($pdf, $asiakkaan_tiedot_row);
			}
			elseif ($asiakas == '') {
				$pdf->draw_text(302, 812, "RAHTIKIRJAERITTELY", $firstpage, $iso);
				$pdf = alku_erittely($pdf, $asiakkaan_tiedot_row);

				$ed_tun = $asiakkaan_tiedot_row['tunnus'];
			}
			elseif ($asiakas != $asiakkaan_tiedot_row['ytunnus']) {
				$asiakkaan_tilausnumero_temp = array_pop($asiakkaan_tilausnumerot);
				$pdf->draw_text(62, $kala_temp, 'Tilausnumerot:', $firstpage, $iso_non_bold);
				$kala_temp = $kala_temp - 10;
				foreach ($asiakkaan_tilausnumerot as $a) {
					$pdf->draw_text(62, $kala_temp, $a, $firstpage, $iso_non_bold);
					$kala_temp = $kala_temp - 10;
				}
				
				$pdf->draw_rectangle($vanhakala, 60, $kala-22, 540,	$firstpage, $rectparam);

				//alarivi
				$pdf->draw_text(246, $kala-6 , "Kollit yht. Kolliantal tot.", $firstpage, $norm);
				$pdf->draw_text(246, $kala-20, $kollityht, $firstpage, $kirj);
				$pdf->draw_rectangle($kala, 111, $kala-22, 242,	$firstpage, $rectparam);
				$pdf->draw_text(365, $kala-6, "Lavametrit Flakmeter", $firstpage, $norm);
				$pdf->draw_text(365, $kala-20, $lavametri[0], $firstpage, $norm);
				$pdf->draw_rectangle($kala, 363, $kala-22, 420,	$firstpage, $rectparam);
				$pdf->draw_text(422, $kala-6, "Brutto yht. total., kg Rahditysp.  Fraktvikt", $firstpage, $norm);
				$pdf->draw_text(422, $kala-20, $kilotyht, $firstpage, $kirj);
				//vähä vedetää viivaa
				$pdf->draw_rectangle($kala, 60, $kala, 540,	$firstpage, $rectparam);
				$pdf->draw_rectangle($kala-22, 60, $kala-22, 540, $firstpage, $rectparam);

				$kala -= 40;

				$pdf = alku_erittely($pdf, $asiakkaan_tiedot_row);

				$kollityht = 0;
				$kilotyht = 0;
				
				unset($asiakkaan_tilausnumerot);
				$asiakkaan_tilausnumerot = array();
				$asiakkaan_tilausnumerot[] = $asiakkaan_tilausnumero_temp;
				unset($kala_temp);
			}

			//summataan kaikki painot yhteen
			$query = "	SELECT rahtikirjat.pakkaus,
						rahtikirjat.pakkauskuvaus,
						rahtikirjat.kilot,
						rahtikirjat.kollit,
						rahtikirjat.kuutiot,
						rahtikirjat.lavametri,
						rahtikirjat.otsikkonro
						FROM rahtikirjat
						LEFT JOIN tilausrivi ON (tilausrivi.yhtio = rahtikirjat.yhtio AND
												tilausrivi.tyyppi in ('L', 'G') AND
												tilausrivi.otunnus = '$asiakkaan_tiedot_row[tunnus]' AND
												tilausrivi.var in ('', 'H'))
						WHERE rahtikirjat.otsikkonro = '$asiakkaan_tiedot_row[tunnus]'
						AND rahtikirjat.yhtio='$kukarow[yhtio]'
						GROUP BY 1, 2, 3, 4, 5, 6, 7
						ORDER BY otsikkonro";
			$pakka = mysql_query($query) or pupe_error($query);
		}

		$i = 0;
		while ($pakkarow = mysql_fetch_assoc($pakka)) {

			//kollit
			if ($i == 0 && !isset($kala_temp)) {
				$kala_temp = $kala;
			}

			$pdf->draw_text(246, $kala, $pakkarow['kollit']."  ".$pakkarow['pakkaus'], $firstpage, $kirj);
			$pdf->draw_text(422, $kala, round($pakkarow['kilot'], 2),	$firstpage, $kirj);
			$pdf->draw_text(486, $kala, $pakkarow['kuutiot'], $firstpage, $kirj);

			$i++;
			$kollityht += $pakkarow['kollit'];
			$kilotyht += round($pakkarow['kilot'], 2);

			$kala -= 20;

			if ($kala < 60) {
				$pdf->draw_rectangle($vanhakala, 60, $kala-22, 540,	$firstpage, $rectparam);

				// tehdään pdf:n uusi sivu
				$firstpage = $pdf->new_page("a4");
				// sitten aletaan piirtämään itse PDF sisältöä
				$pdf->draw_text(302,  812,  "RAHTIKIRJAERITTELY", $firstpage, $iso);
				$kala = 810;
				$vanhakala = 810;
				$pdf = alku_erittely($pdf, $asiakkaan_tiedot_row);
			}
		}

		$kala -= 10;

		if ($asiakas != $asiakkaan_tiedot_row['ytunnus'] or $rivi == $maxrivit) {
			$pdf->draw_text(62, $kala_temp, 'Tilausnumerot:', $firstpage, $iso_non_bold);
			$kala_temp = $kala_temp - 10;
			foreach ($asiakkaan_tilausnumerot as $a) {
				$pdf->draw_text(62, $kala_temp, $a, $firstpage, $iso_non_bold);
				$kala_temp = $kala_temp - 10;
			}
			
			$pdf->draw_rectangle($vanhakala, 60, $kala-22, 540,	$firstpage, $rectparam);

			//alarivi
			$pdf->draw_text(246, $kala-6 , "Kollit yht. Kolliantal tot.", $firstpage, $norm);
			$pdf->draw_text(246, $kala-20, $kollityht, $firstpage, $kirj);
			$pdf->draw_rectangle($kala, 111, $kala-22, 242,	$firstpage, $rectparam);
			$pdf->draw_text(365, $kala-6, "Lavametrit Flakmeter", $firstpage, $norm);
			$pdf->draw_text(365, $kala-20, $lavametri[0], $firstpage, $norm);
			$pdf->draw_rectangle($kala, 363, $kala-22, 420,	$firstpage, $rectparam);
			$pdf->draw_text(422, $kala-6, "Brutto yht. total., kg Rahditysp.  Fraktvikt", $firstpage, $norm);
			$pdf->draw_text(422, $kala-20, $kilotyht, $firstpage, $kirj);
			//vähä vedetää viivaa
			$pdf->draw_rectangle($kala, 60, $kala, 540,	$firstpage, $rectparam);
			$pdf->draw_rectangle($kala-22, 60, $kala-22, 540, $firstpage, $rectparam);

			$kala -= 40;
		}

		$asiakas = $asiakkaan_tiedot_row['ytunnus'];
		$ed_tun = $asiakkaan_tiedot_row['tunnus'];
		$rivi++;
	}

	//keksitään uudelle failille joku varmasti uniikki nimi:
	$pdffilenimi = "/tmp/rahtikirjaerittely-".md5(uniqid(rand(),true)).".pdf";

	//kirjoitetaan pdf faili levylle..
	$fh = fopen($pdffilenimi, "w");
	if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
	fclose($fh);

	if ($kirjoitin == "email") {
		$liite = $pdffilenimi;
		$kutsu = "PDF-erittely";
		require("inc/sahkoposti.inc");

	}
	else {
		//itse print komento...
		$line = exec("$kirjoitin $pdffilenimi");
	}

	system("rm -f $pdffilenimi");
?>
