<?php
	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	require("../inc/parametrit.inc");

	if (isset($tee)) {
		if ($tee == "lataa_tiedosto") {
			readfile("/tmp/".$tmpfilenimi);
			exit;
		}
	}
	else {

    	echo "<font class='head'>".t("Tase/tuloslaskelma")."</font><hr>";

		if ($tltee == "aja") {

			// Desimaalit
			$muoto = "%.". (int) $desi . "f";

			// Onko meillä lisärajoitteita??
			$lisa  = "";
			$lisa2 = "";

			if ($kustp != "") {
				$lisa .= " and kustp = '$kustp'";
				$lisa2 .= " and kustannuspaikka = '$kustp'";
			}
			if ($proj != "") {
				$lisa .= " and projekti = '$proj'";
				$lisa2 .= " and projekti = '$proj'";
			}
			if ($kohde != "") {
				$lisa .= " and kohde = '$kohde'";
				$lisa2 .= " and kohde = '$kohde'";
			}
			if ($plvk == '' or $plvv == '') {
				$plvv = substr($yhtiorow['tilikausi_alku'], 0, 4);
				$plvk = substr($yhtiorow['tilikausi_alku'], 5, 2);
			}

			if ($tyyppi == "1") {
				// Vastaavaa Varat
				$otsikko 	= "Vastaavaa Varat";
				$kirjain 	= "U";
				$aputyyppi 	= 1;
				$tilikarttataso = "ulkoinen_taso";
			}
			elseif ($tyyppi == "2") {
				// Vastattavaa Velat
				$otsikko 	= "Vastattavaa Velat";
				$kirjain 	= "U";
				$aputyyppi 	= 2;
				$tilikarttataso = "ulkoinen_taso";
			}
			elseif ($tyyppi == "3") {
				// Ulkoinen tuloslaskelma
				$otsikko 	= "Ulkoinen tuloslaskelma";
				$kirjain 	= "U";
				$aputyyppi 	= 3;
				$tilikarttataso = "ulkoinen_taso";
			}
			else {
				// Sisäinen tuloslaskelma
				$otsikko 	= "Sisäinen tuloslaskelma";
				$kirjain 	= "S";
				$aputyyppi 	= 3;
				$tilikarttataso = "sisainen_taso";
			}

			// edellinen taso
			$taso     = array();
			$tasonimi = array();
			$summa    = array();
			$kaudet   = array();

			$startmonth	= date("Ymd", mktime(0, 0, 0, $plvk, 1, $plvv));
			$endmonth 	= date("Ymd", mktime(0, 0, 0, $alvk, 1, $alvv));
			$annettualk = date("Y-m-d", mktime(0, 0, 0, $plvk, 1, $plvv));
			$annettuabu = date("Ym", mktime(0, 0, 0, $plvk, 1, $plvv));
			$totalloppu = date("Y-m-d", mktime(0, 0, 0, $alvk+1, 1, $alvv));

			if ($vertailued != "") {
				$totalalku  = date("Y-m-d", mktime(0, 0, 0, $plvk, 1, $plvv-1));
			}
			else {
				$totalalku = date("Y-m-d", mktime(0, 0, 0, $plvk, 1, $plvv));
			}

			$alkuquery1 = "";
			$alkuquery2 = "";
			$alkuquery3 = "";
			
			for ($i = $startmonth;  $i <= $endmonth;) {

				$alku    = date("Y-m-d", mktime(0, 0, 0, substr($i,4,2), substr($i,6,2),  substr($i,0,4)));
				$loppu   = date("Y-m-d", mktime(0, 0, 0, substr($i,4,2), date("t", mktime(0, 0, 0, substr($i,4,2), substr($i,6,2),  substr($i,0,4))),  substr($i,0,4)));
				$bukausi = date("Ym", mktime(0, 0, 0, substr($i,4,2), substr($i,6,2),  substr($i,0,4)));

				$headny = date("Y/m",   mktime(0, 0, 0, substr($i,4,2), substr($i,6,2),  substr($i,0,4)));

				$alkuquery1 .= " sum(if(tiliointi.tapvm >= '$alku' and tiliointi.tapvm <= '$loppu', tiliointi.summa, 0)) '$headny', \n";
				$alkuquery2 .= " sum(if(tiliointi.tapvm >= '$alku' and tiliointi.tapvm <= '$loppu', tiliointi.summa, 0)) '$headny', \n";
				$alkuquery3 .= " sum(if(tiliointi.tapvm >= '$alku' and tiliointi.tapvm <= '$loppu', tiliointi.summa, 0)) '$headny', \n";
				
				$kaudet[] = $headny;

				if ($vertailued != "") {
					$alku_ed  = date("Y-m-d", mktime(0, 0, 0, substr($i,4,2), substr($i,6,2),  substr($i,0,4)-1));
					$loppu_ed = date("Y-m-d", mktime(0, 0, 0, substr($i,4,2), date("t", mktime(0, 0, 0, substr($i,4,2), substr($i,6,2),  substr($i,0,4))),  substr($i,0,4)-1));
					$headed   = date("Y/m",   mktime(0, 0, 0, substr($i,4,2), substr($i,6,2),  substr($i,0,4)-1));

					$alkuquery1 .= " sum(if(tiliointi.tapvm >= '$alku_ed' and tiliointi.tapvm <= '$loppu_ed', tiliointi.summa, 0)) '$headed', \n";
					$alkuquery2 .= " sum(if(tiliointi.tapvm >= '$alku_ed' and tiliointi.tapvm <= '$loppu_ed', tiliointi.summa, 0)) '$headed', \n";
					$alkuquery3 .= " sum(if(tiliointi.tapvm >= '$alku_ed' and tiliointi.tapvm <= '$loppu_ed', tiliointi.summa, 0)) '$headed', \n";
					
					$kaudet[] = $headed;
				}

				// sisäisessä tuloslaskelmassa voidaan joinata budjetti
				if ($vertailubu != "" and $kirjain == "S") {
					$alkuquery1 .= " (SELECT sum(budjetti.summa) FROM budjetti USE INDEX (yhtio_taso_kausi) WHERE budjetti.yhtio = tili.yhtio and budjetti.taso = tili.$tilikarttataso and budjetti.kausi = '$bukausi' $lisa2) 'budj $headny', \n";
					$alkuquery2 .= " 0 'budj $headny', \n";
					$alkuquery3 .= " (SELECT sum(budjetti.summa) FROM budjetti USE INDEX (yhtio_taso_kausi) WHERE budjetti.yhtio = tili.yhtio and budjetti.taso = tili.taso and budjetti.kausi = '$bukausi' $lisa2) 'budj $headny', \n";
					
					$kaudet[] = "budj $headny";
				}

				$i = date("Ymd",mktime(0, 0, 0, substr($i,4,2)+1, 1,  substr($i,0,4)));
			}

			// yhteensäotsikkomukaan
			$vka = date("Y/m", mktime(0, 0, 0, $plvk, 1, $plvv));
			$vkl = date("Y/m", mktime(0, 0, 0, $alvk+1, 1, $alvv));
			
			$kaudet[] = $vka." - ".$vkl;
			
			$query = "	SELECT *
						FROM taso
						WHERE yhtio = '$kukarow[yhtio]' AND
						tyyppi = '$kirjain' AND
						LEFT(taso, 1) = '$aputyyppi'
						and taso != ''
						ORDER BY taso";
			$tasores = mysql_query($query) or pupe_error($query);
			
			while ($tasorow = mysql_fetch_array($tasores)) {

				// millä tasolla ollaan (1,2,3,4,5,6)
				$tasoluku = strlen($tasorow["taso"]);												
				
				// tasonimi talteen (rightpäddätään Ö:llä, niin saadaan oikeaan järjestykseen)
				$apusort = str_pad($tasorow["taso"], 20, "Ö");
				$tasonimi[$apusort] = $tasorow["nimi"];

				// pilkotaan taso osiin
				$taso = array();
				for ($i=0; $i < $tasoluku; $i++) {
					$taso[$i] = substr($tasorow["taso"], 0, $i+1);
				}

				$query = "	SELECT $alkuquery1
							sum(if(tiliointi.tapvm >= '$annettualk' and tiliointi.tapvm <= '$totalloppu', tiliointi.summa, 0)) '$vka - $vkl'
						 	FROM tili
							LEFT JOIN tiliointi USE INDEX (yhtio_tilino_tapvm) ON (tiliointi.yhtio = tili.yhtio and tiliointi.tilino = tili.tilino and tiliointi.korjattu = '' and tiliointi.tapvm >= '$totalalku' and tiliointi.tapvm < '$totalloppu' $lisa)
							WHERE tili.yhtio 		 = '$kukarow[yhtio]'
							and tili.$tilikarttataso = '$tasorow[taso]'
							group by tili.$tilikarttataso";
				$tilires = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($tilires) == 0) {
					// Ei tiliöintejä, mutta budjetti voi olla
					$query = "	SELECT $alkuquery3
								sum(if(tiliointi.tapvm >= '$annettualk' and tiliointi.tapvm <= '$totalloppu', tiliointi.summa, 0)) '$vka - $vkl'
							 	FROM budjetti
								JOIN budjetti tili ON (tili.yhtio = budjetti.yhtio and tili.tunnus=budjetti.tunnus)
								LEFT JOIN tiliointi USE INDEX (PRIMARY) ON (tiliointi.tunnus = 0)
								WHERE budjetti.yhtio  = '$kukarow[yhtio]'
								and budjetti.taso 	  = '$tasorow[taso]'
								group by budjetti.taso";
					$tilires = mysql_query($query) or pupe_error($query);					
				}
				
				while ($tilirow = mysql_fetch_array ($tilires)) {
					// summataan kausien saldot
					foreach ($kaudet as $kausi) {
						// summataan kaikkia pienempiä summaustasoja
						if (substr($kausi,0,4) == "budj") {
							$i = $tasoluku - 1;
								
							$summa[$kausi][$taso[$i]] += $tilirow[$kausi];
						}
						else {
							for ($i = $tasoluku - 1; $i >= 0; $i--) {
								$summa[$kausi][$taso[$i]] += $tilirow[$kausi];
							}						
						}
					}
				}
			}

			// Haluaako käyttäjä nähä kaikki kaudet
			if ($kaikkikaudet == "") {
				$alkukausi = count($kaudet)-2;

				if($vertailued != "") $alkukausi-=1;
				if($vertailubu != "") $alkukausi-=1;
			}
			else {
				$alkukausi = 0;
			}

			require_once('pdflib/phppdflib.class.php');

			$pdf = new pdffile;
			$pdf->set_default('margin', 0);
			$pdf->set_default('margin-left', 5);
			$rectparam["width"] = 0.3;

			$p["height"] 	= 10;
			$p["font"]	 	= "Times-Roman";
	        $b["height"]	= 8;
			$b["font"] 		= "Times-Bold";
			$rivikork 		= "15";
			$saraklev 		= "50";

			function alku () {
				global $yhtiorow, $kukarow, $firstpage, $pdf, $bottom, $kaudet, $saraklev, $rivikork, $p, $b, $otsikko, $alkukausi;

				$firstpage = $pdf->new_page("11.5x8in");
				$bottom = "530";

				unset($data);		
				if( (int) $yhtiorow["lasku_logo"] > 0) {
					$liite = hae_liite($yhtiorow["lasku_logo"], "Yllapito", "array");
					$data = $liite["data"];
					$isizelogo[0] = $liite["image_width"];
					$isizelogo[1] = $liite["image_height"];
					unset($liite);
				}
				elseif(file_exists($yhtiorow["lasku_logo"])) {
					$filename = $yhtiorow["lasku_logo"];

					$fh = fopen($filename, "r");
					$data = fread($fh, filesize($filename));
					fclose($fh);

					$isizelogo = getimagesize($yhtiorow["lasku_logo"]);
				}

				if($data) {
					$image = $pdf->jfif_embed($data);

					if(!$image) {
						echo t("Logokuvavirhe");
					}
					else {
						$logoparam = array();
						$logoparam['scale'] = 0.15;

						$iy    = $isizelogo[1]*0.15*0.35714;	// kuvan y-korkeus millimetreissä

						$placement = $pdf->image_place($image, mm_pt(200-$iy), 10, $firstpage, $logoparam);
					}				
				}
				else {
					$pdf->draw_text(10, 560,  $yhtiorow["nimi"], $firstpage);
				}

				$pdf->draw_text(200,  560, $otsikko, $firstpage);

				$left 	= "150";

				for ($i = $alkukausi; $i < count($kaudet); $i++) {
					$oikpos = $pdf->strlen($kaudet[$i], $b);
					$pdf->draw_text($left-$oikpos+$saraklev,  $bottom, $kaudet[$i], $firstpage, $b);

					$left += $saraklev;
				}

				$bottom -= $rivikork;
			}

			alku();

			echo "<table>";

			// printataan headerit
			echo "<tr>";
			
			if ($toim == "TASOMUUTOS") {
				
				echo "	<form action = '".$palvelin2."tasomuutos.php' method='post'>
						<input type = 'hidden' name = 'tee' value = 'tilitaso'>
						<input type = 'hidden' name = 'kirjain' value = '$kirjain'>
						<input type = 'hidden' name = 'taso' value = '$aputyyppi'>";
				
				$lopetus = "raportit/tuloslaskelma.php////";
						
				foreach ($_REQUEST as $key => $value) {
					$lopetus .= $key."=".$value."//";
				}
				echo "<input type = 'hidden' name = 'lopetus' value = '$lopetus'>";
				
				echo "<td class='back' colspan='3'></td>";
			}
			else {
				echo "<td class='back' colspan='1'></td>";	
			}
			
			for ($i = $alkukausi; $i < count($kaudet); $i++) {
				
				if($i==count($kaudet)-1) {
					$w = 115;
				}
				else {
					$w = 78;
				}
				echo "<td class='tumma' align='right' valign='bottom' width = '$w'>$kaudet[$i]</td>";
			}
			echo "</tr>\n";

			// sortataan array indexin (tason) mukaan
			ksort($tasonimi);

			// loopataan tasot läpi
			foreach ($tasonimi as $key => $value) {

				$key = str_replace("Ö", "", $key); // Ö-kirjaimet pois

				// tulostaan rivi vain jos se kuuluu rajaukseen
				if (strlen($key) <= $rtaso or $rtaso == "TILI") {

					if ($bottom < 20) {
						alku();
					}

					$class = "";

					// laitetaan ykkös ja kakkostason rivit tummalla selkeyden vuoksi
					if (strlen($key) < 3 and $rtaso > 2) $class = "tumma";

					$rivi  = "<tr class='aktiivi'>";					
					
					if ($toim == "TASOMUUTOS") {
						$rivi .= "<td class='back' nowrap><a href='".$palvelin2."tasomuutos.php?taso=$key&kirjain=$kirjain&tee=muuta&lopetus=$lopetus'>$key</a></td>";
						$rivi .= "<td class='back' nowrap><a href='".$palvelin2."tasomuutos.php?taso=$key&kirjain=$kirjain&edtaso=$edkey&tee=lisaa&lopetus=$lopetus'>Lisää taso tasoon $key</a></td>";
					}
					
					$tilirivi = "";

					if ($rtaso == "TILI") {

						$class = "tumma";

						$query = "SELECT * FROM tili WHERE yhtio = '$kukarow[yhtio]' and $tilikarttataso = '$key'";
						$tilires = mysql_query($query) or pupe_error($query);

						while ($tilirow = mysql_fetch_array($tilires)) {
							$query = "	SELECT tilino, $alkuquery2
										sum(if(tiliointi.tapvm >= '$annettualk' and tiliointi.tapvm <= '$totalloppu', tiliointi.summa, 0)) 'Total'
										FROM tiliointi
										WHERE yhtio = '$kukarow[yhtio]'
										AND tilino = '$tilirow[tilino]'
										AND korjattu = ''
										AND tapvm >= '$totalalku'
										AND tapvm < '$totalloppu'
										$lisa
										GROUP BY tilino";							
							$summares = mysql_query($query) or pupe_error($query);
							$summarow = mysql_fetch_array($summares);

							$tilirivi2 = "";
							$tulos = 0;

							for ($tilii = $alkukausi + 1; $tilii < mysql_num_fields($summares); $tilii++) {
								$apu = sprintf($muoto, $summarow[$tilii] * -1 / $tarkkuus);
								if ($apu == 0) $apu = "";
																	
								$tilirivi2 .= "<td align='right' nowrap>".number_format($apu, $desi, ',', ' ')."</td>";
								if ($summarow[$tilii] != 0) $tulos++;
							}

							if ($tulos > 0 or $toim == "TASOMUUTOS") {
								
								$tilirivi .= "<tr>";
								
								if ($toim == "TASOMUUTOS") {
									$tilirivi .= "<td class='back' nowrap>$key</td>";
									$tilirivi .= "<td class='back' nowrap><input type='checkbox' name='tiliarray[]' value=\"'$tilirow[tilino]'\"></td>";
								}
								
								$tilirivi .= "<td nowrap>$tilirow[tilino] - $tilirow[nimi]</td>$tilirivi2</tr>";
							}
						}
					}

					$rivi .= "<th nowrap>$value</th>";

					$tulos = 0;

					for ($i = $alkukausi; $i < count($kaudet); $i++) {
						
						$query = "	SELECT summattava_taso 
									FROM taso 
									WHERE yhtio 		 = '$kukarow[yhtio]' 
									and taso 			 = '$key' 
									and summattava_taso != '' 
									and tyyppi 			 = '$kirjain'";
						$summares = mysql_query($query) or pupe_error($query);

						// Budjettia ei summata
						if ($summarow = mysql_fetch_array ($summares) and substr($kaudet[$i],0,4) != "budj") {														
							foreach(explode(",", $summarow["summattava_taso"]) as $staso) {
								$summa[$kaudet[$i]][$key] = $summa[$kaudet[$i]][$key] + $summa[$kaudet[$i]][$staso];
							}
						}

						// formatoidaan luku toivottuun muotoon
						$apu = sprintf($muoto, $summa[$kaudet[$i]][$key] * -1 / $tarkkuus);

						if ($apu == 0) {
							$apu = ""; // nollat spaseiks
						}
						else {
							$tulos++; // summaillaan tätä jos meillä oli rivillä arvo niin osataan tulostaa
						}

						$rivi .= "<td class='$class' align='right' nowrap>".number_format($apu, $desi,  ',', ' ')."</td>";
					}
					
					$rivi .= "</tr>\n";

					// kakkostason jälkeen aina yks tyhjä rivi.. paitsi jos otetaan vain kakkostason raportti
					if (strlen($key) == 2 and ($rtaso > 2 or $rtaso == "TILI")) {
						$rivi .= "<tr><td class='back'>&nbsp;</td></tr>";
					}

					// jos jollain kaudella oli summa != 0 niin tulostetaan rivi
					if ($tulos > 0 or $toim == "TASOMUUTOS") {

						echo $tilirivi, $rivi;

						
						$left = 10+(strlen($key)-1)*3;
						$pdf->draw_text($left,  $bottom, $value, $firstpage, $b);
						$left = 150;

						for ($i = $alkukausi; $i < count($kaudet); $i++) {
							$oikpos = $pdf->strlen(sprintf($muoto, $summa[$kaudet[$i]][$key] * -1 / $tarkkuus), $p);
							$pdf->draw_text($left-$oikpos+$saraklev, $bottom, sprintf($muoto, $summa[$kaudet[$i]][$key] * -1 / $tarkkuus), $firstpage, $p);
							$left += $saraklev;
						}

						$bottom -= $rivikork;

						if (strlen($key) == 2 and ($rtaso > 2 or $rtaso == "TILI")) {
							$bottom -= $rivikork;
						}
					}
				}

				$edkey = $key;
			}
			
			echo "</table>";

			if ($toim == "TASOMUUTOS") {
				echo "<br><input type='submit' value='".t("Anna tileille taso")."'></form><br><br>";
			}

			//keksitään uudelle failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$pdffilenimi = "Tuloslaskelma-".md5(uniqid(mt_rand(), true)).".pdf";

			//kirjoitetaan pdf faili levylle..
			$fh = fopen("/tmp/".$pdffilenimi, "w");
			if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF Error $pdffilenimi");
			fclose($fh);

			echo "<br><table>";
			echo "<tr><th>".t("Tallenna pdf").":</th>";
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='toim' value='$toim'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='".urlencode($otsikko).".pdf'>";
			echo "<input type='hidden' name='tmpfilenimi' value='$pdffilenimi'>";
			echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
			echo "</table><br>";

		}

		// tehdään käyttöliittymä, näytetään aina
		$sel = array();
		if ($tyyppi == "") $tyyppi = "4";
		$sel[$tyyppi] = "SELECTED";

		echo "<br>";
		echo "	<form action = 'tuloslaskelma.php' method='post'>
				<input type = 'hidden' name = 'tltee' value = 'aja'>
				<input type='hidden' name='toim' value='$toim'>
				<table>";

		echo "	<tr>
				<th>".t("Tyyppi")."</th>
				<td>";

		echo "	<select name = 'tyyppi'>
				<option $sel[4] value='4'>".t("Sisäinen tuloslaskelma")."</option>
				<option $sel[3] value='3'>".t("Ulkoinen tuloslaskelma")."</option>
				<option $sel[1] value='1'>".t("Vastaavaa")." (".t("Varat").")</option>
				<option $sel[2] value='2'>".t("Vastattavaa")." (".t("Velat").")</option>
				</select>";

		echo "</td>
				</tr>";

		if (!isset($plvv)) $plvv = substr($yhtiorow['tilikausi_alku'], 0, 4);
		if (!isset($plvk)) $plvk = substr($yhtiorow['tilikausi_alku'], 5, 2);

		echo "	<th>".t("Alkukausi")."</th>
				<td><select name='plvv'>";

		$sel = array();
		$sel[$plvv] = "SELECTED";

		for ($i = date("Y"); $i >= date("Y")-4; $i--) {
			echo "<option value='$i' $sel[$i]>$i</option>";
		}

		echo "</select>";

		$sel = array();
		$sel[$plvk] = "SELECTED";

		echo "<select name='plvk'>
				<option $sel[1] value = '1'>01</option>
				<option $sel[2] value = '2'>02</option>
				<option $sel[3] value = '3'>03</option>
				<option $sel[4] value = '4'>04</option>
				<option $sel[5] value = '5'>05</option>
				<option $sel[6] value = '6'>06</option>
				<option $sel[7] value = '7'>07</option>
				<option $sel[8] value = '8'>08</option>
				<option $sel[9] value = '9'>09</option>
				<option $sel[10] value = '10'>10</option>
				<option $sel[11] value = '11'>11</option>
				<option $sel[12] value = '12'>12</option>
				</select></td></tr>";

		echo "<tr>
			<th>".t("Loppukausi")."</th>
			<td><select name='alvv'>";

		$sel = array();
		if ($alvv == "") $alvv = date("Y");
		$sel[$alvv] = "SELECTED";

		for ($i = date("Y")+1; $i >= date("Y")-4; $i--) {
			echo "<option value='$i' $sel[$i]>$i</option>";
		}

		$sel = array();
		if ($alvk == "") $alvk = date("n");
		$sel[$alvk] = "SELECTED";

		echo "</select>";

		echo "<select name='alvk'>
				<option $sel[1] value = '1'>01</option>
				<option $sel[2] value = '2'>02</option>
				<option $sel[3] value = '3'>03</option>
				<option $sel[4] value = '4'>04</option>
				<option $sel[5] value = '5'>05</option>
				<option $sel[6] value = '6'>06</option>
				<option $sel[7] value = '7'>07</option>
				<option $sel[8] value = '8'>08</option>
				<option $sel[9] value = '9'>09</option>
				<option $sel[10] value = '10'>10</option>
				<option $sel[11] value = '11'>11</option>
				<option $sel[12] value = '12'>12</option>
				</select></td></tr>";

		echo "<tr><th>".t("Vain kustannuspaikka")."</th>";

		$query = "	SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'K'
					ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);

		echo "<td><select name='kustp'><option value=''>".t("Ei valintaa")."</option>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['tunnus'] or $kustp == $vrow["tunnus"]) {
				$sel = "selected";
			}
			echo "<option value = '$vrow[tunnus]' $sel>$vrow[nimi]</option>";
		}

		echo "</select></td>";
		echo "</tr>";
		echo "<tr><th>".t("Vain kohde")."</th>";

		$query = "	SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'O'
					ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);

		echo "<td><select name='kohde'><option value=''>Ei valintaa</option>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['tunnus'] or $kohde == $vrow["tunnus"]) {
				$sel = "selected";
			}
			echo "<option value = '$vrow[tunnus]' $sel>$vrow[nimi]</option>";
		}

		echo "</select></td>";
		echo "</tr>";
		echo "<tr><th>".t("Vain projekti")."</th>";

		$query = "	SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'P'
					ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);

		echo "<td><select name='proj'><option value=''>".t("Ei valintaa")."</option>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['tunnus'] or $proj == $vrow["tunnus"]) {
				$sel = "selected";
			}
			echo "<option value = '$vrow[tunnus]' $sel>$vrow[nimi]</option>";
		}

		echo "</select></td></tr>";

		$sel = array();
		$sel[$rtaso] = "SELECTED";

		echo "<tr><th>".t("Raportointitaso")."</th>
				<td><select name='rtaso'>";

		$query = "select max(length(taso)) taso from taso where yhtio = '$kukarow[yhtio]'";
		$vresult = mysql_query($query) or pupe_error($query);
		$vrow = mysql_fetch_array($vresult);

		echo "<option value='TILI'>".t("Tili taso")."</option>\n";

		for ($i=$vrow["taso"]-1; $i >= 0; $i--) {
			echo "<option ".$sel[$i+2]." value='".($i+2)."'>".t("Taso %s",'',$i+1)."</option>\n";
		}

		echo "</select></td></tr>";

		$sel = array();
		if ($tarkkuus == "") $tarkkuus = 1;
		$sel[$tarkkuus] = "SELECTED";

		echo "<tr><th>".t("Lukujen taarkkuus")."</th>
				<td><select name='tarkkuus'>
					<option $sel[1]    value='1'>".t("Älä jaa lukuja")."</option>
					<option $sel[1000] value='1000'>".t("Jaa 1000:lla")."</option>
					<option $sel[10000] value='10000'>".t("Jaa 10 000:lla")."</option>
					<option $sel[100000] value='100000'>".t("Jaa 100 000:lla")."</option>
					<option $sel[1000000] value='1000000'>".t("Jaa 1 000 000:lla")."</option>
					</select>";

		$sel = array();
		if ($desi == "") $desi = "0";
		$sel[$desi] = "SELECTED";

		echo "<select name='desi'>
				<option $sel[0] value='0'>0 ".t("desimaalia")."</option>
				<option $sel[1] value='1'>1 ".t("desimaalia")."</option>
				<option $sel[2] value='2'>2 ".t("desimaalia")."</option>
				</select></td></tr>";

		$kauchek =  "";
		if ($kaikkikaudet != "") $kauchek = "SELECTED";

		echo "<tr><th>".t("Näkymä")."</th>";

		echo "<td><select name='kaikkikaudet'>
				<option value=''>".t("Näytä vain viimeisin kausi")."</option>
				<option value='o' $kauchek>".t("Näytä kaikki kaudet")."</option>
				</select></td></tr>";

		$vchek = $bchek = "";
		if ($vertailued != "") $vchek = "CHECKED";
		if ($vertailubu != "") $bchek = "CHECKED";

		echo "<tr><th>".t("Vertailu")."</th>";
		echo "<td>";
		echo "&nbsp;<input type='checkbox' name='vertailued' $vchek> ".t("Edellinen vastaava");
		echo "<br>&nbsp;<input type='checkbox' name='vertailubu' $bchek> ".t("Budjetti");
		echo "</td></tr>";

		echo "</table><br>
		      <input type = 'submit' value = '".t("Näytä")."'></form>";

		require("../inc/footer.inc");
	}
?>
