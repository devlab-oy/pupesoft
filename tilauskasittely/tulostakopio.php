<?php

	//Pari muutujakorjausta ettei tilausmyynnissä tarttis tehdä niin pirun rumia nappuloita
	if(isset($_POST["NAYTATILAUS"])) {
		$_POST["tee"] = "NAYTATILAUS";
	}
	if(isset($_POST["TULOSTA"])) {
		$_POST["tee"] = "TULOSTA";
	}

	if(isset($_POST["komento"]) and in_array("PDF_RUUDULLE", $_POST["komento"]) !== false) {
		$_POST["tee"] = "NAYTATILAUS";
	}

	if($_POST["tee"] == 'NAYTATILAUS') $nayta_pdf=1; //Generoidaan .pdf-file
	
	if (file_exists("../inc/parametrit.inc")) {
		require('../inc/parametrit.inc');
	}
	else {
		require('inc/parametrit.inc');
	}

	if ($toim == "") $toim = "LASKU";

	if ($tee == 'NAYTAHTML') {
		echo "<font class='head'>".t("Tilaus")." $tunnus:</font><hr>";
		require ("../raportit/naytatilaus.inc");
		echo "<br><br>";
		$tee = "ETSILASKU";
	}

	if ($toim == "OSTO") {
		$fuse = t("Ostotilaus");
	}
	if ($toim == "PURKU") {
		$fuse = t("Purkulista");
	}
	if ($toim == "TARIFFI") {
		$fuse = t("Tariffilista");
	}
	if ($toim == "SIIRTOLISTA") {
		$fuse = t("Siirtolista");
	}
	if ($toim == "VALMISTUS") {
		$fuse = t("Valmistus");
	}
	if ($toim == "LASKU") {
		$fuse = t("Lasku");
	}
	if ($toim == "TUOTETARRA") {
		$fuse = t("Tuotetarra");
	}
	if ($toim == "TYOMAARAYS") {
		$fuse = t("Työmääräys");
	}
	if ($toim == "SAD") {
		$fuse = t("Sad-lomake");
	}
	if ($toim == "LAHETE") {
		$fuse = t("Lähete");
	}
	if ($toim == "PAKKALISTA") {		//	Tämä on about yhdistetty vienti-erittely ja lähete
		$fuse = t("Pakkalista");
	}
	if ($toim == "KERAYSLISTA") {
		$fuse = t("Keräyslista");
	}
	if ($toim == "OSOITELAPPU") {
		$fuse = t("Osoitelappu");
	}
	if ($toim == "VIENTIERITTELY") {
		$fuse = t("Vientierittely");
	}
	if ($toim == "PROFORMA") {
		$fuse = t("Proforma");
	}
	if ($toim == "TILAUSVAHVISTUS") {
		$fuse = t("Tilausvahvistus");
	}
	if ($toim == "TARJOUS" or $toim == "TARJOUS!!!VL") {
		$fuse = t("Tarjous");
	}
	if ($toim == "MYYNTISOPIMUS" or $toim == "MYYNTISOPIMUS!!!VL") {
		$fuse = t("Myyntisopimus");
	}
	if ($toim == "OSAMAKSUSOPIMUS") {
		$fuse = t("Osamaksusopimus");
	}
	if ($toim == "LUOVUTUSTODISTUS") {
		$fuse = t("Luovutustodistus");
	}
	if ($toim == "VAKUUTUSHAKEMUS") {
		$fuse = t("Vakuutushakemus");
	}
	if ($toim == "REKISTERIILMOITUS") {
		$fuse = t("Rekisteröinti-ilmoitus");
	}

	if (isset($muutparametrit) and $muutparametrit != '') {
		$muut = explode('/',$muutparametrit);

		$vva 		= $muut[0];
		$kka 		= $muut[1];
		$ppa 		= $muut[2];
		$vvl 		= $muut[3];
		$kkl		= $muut[4];
		$ppl 		= $muut[5];
	}

	if($tee != 'NAYTATILAUS') {
		echo "<font class='head'>".sprintf(t("Tulosta %s kopioita"), $fuse).":</font><hr><br>";
	}
	
	if ($laskunro > 0 and $laskunroloppu > 0 and $laskunro < $laskunroloppu) {
		$tee = "TULOSTA";

		$tulostukseen = array();

		for($las = $laskunro; $las<=$laskunroloppu; $las++) {
			//hateaan laskun kaikki tiedot
			$query = "  SELECT tunnus
						FROM lasku
						WHERE tila		= 'U'
						and alatila		= 'X'
						and laskunro	= '$las'
						and yhtio 		= '$kukarow[yhtio]'";
			$rrrresult = mysql_query($query) or pupe_error($query);
			$laskurow = mysql_fetch_array($rrrresult);

			$tulostukseen[] = $laskurow["tunnus"];
		}
		$laskunro		= "";
		$laskunroloppu	= "";
	}

	if (($tee == "" or $tee == 'ETSILASKU') and $toim != 'SIIRTOLISTA'){
		
		$muutparametrit = $vva."/".$kka."/".$ppa."/".$vvl."/".$kkl."/".$ppl;
		
		if ($ytunnus != '' or (int) $asiakasid > 0 or (int) $toimittajaid > 0) {
			if ($toim == 'OSTO' or $toim == 'PURKU' or $toim == 'TARIFFI' or $toim == 'TUOTETARRA') {
				require ("../inc/kevyt_toimittajahaku.inc");
			}
			else {
				require ("../inc/asiakashaku.inc");
			}
		}

		if ($ytunnus != '') {
			$tee = "ETSILASKU";
		}
		else {
			$tee = "";
		}

		if ($laskunro > 0) {
			$tee = "ETSILASKU";
		}

		if ($otunnus > 0) {
			$tee = 'ETSILASKU';
		}
	}
	
	if (($tee == "" or $tee == 'ETSILASKU') and $toim == 'SIIRTOLISTA'){
		if ($lahettava_varasto != '' or $vastaanottava_varasto != '') {
			$tee = "ETSILASKU";
		}
		else {
			$tee = "";
		}

		if ($otunnus > 0) {
			$tee = 'ETSILASKU';
		}
	}

	if($tee != 'NAYTATILAUS') {
		//syötetään tilausnumero
		echo "<form method='post' action='$PHP_SELF' autocomplete='off' name='hakuformi'>
				<input type='hidden' name='lopetus' value='$lopetus'>
				<input type='hidden' name='toim' value='$toim'>
				<input type='hidden' name='tee' value='ETSILASKU'>";
				
		echo "<table>";
    
		if (trim($toim) == "SIIRTOLISTA") {
    
			echo "<tr><th>".t("Lähettävä varasto")."</th><td colspan='3'>";
    
			$query = "	SELECT *
						FROM varastopaikat
						WHERE yhtio = '$kukarow[yhtio]' order by nimitys";
			$vtresult = mysql_query($query) or pupe_error($query);
    
			echo "<select name='lahettava_varasto'>";
			echo "<option value=''>".t("Valitse varasto")."</option>";
    
			while ($vrow = mysql_fetch_array($vtresult)) {
				if ($lahettava_varasto == $vrow["tunnus"]) {
					$sel = "SELECTED";
				}
				else {
					$sel = "";
				}
			
				echo "<option value='$vrow[tunnus]' $sel>$vrow[maa] $vrow[nimitys]</option>";
			}
			echo "</select>";
    
			echo "</td></tr>";
    
			echo "<tr><th>".t("Vastaanottava varasto")."</th><td colspan='3'>";
    
			$query = "	SELECT *
						FROM varastopaikat
						WHERE yhtio = '$kukarow[yhtio]' order by nimitys";
			$vtresult = mysql_query($query) or pupe_error($query);
    
			echo "<select name='vastaanottava_varasto'>";
			echo "<option value=''>".t("Valitse varasto")."</option>";
    
			while ($vrow = mysql_fetch_array($vtresult)) {
				if ($vastaanottava_varasto == $vrow["tunnus"]) {
					$sel = "SELECTED";
				}
				else {
					$sel = "";
				}
				echo "<option value='$vrow[tunnus]' $sel>$vrow[maa] $vrow[nimitys]</option>";
			}
			echo "</select>";
    
			echo "</td></tr>";
    
			echo "<tr><th>".t("Tilausnumero")."</th><td colspan='3'><input type='text' size='15' name='otunnus'></td></tr>";
		}
		else {
			if (((int) $asiakasid > 0 or (int) $toimittajaid > 0)) {
				if ($toim == "OSTO" or $toim == "PURKU" or $toim == "TARIFFI" or $toim == "TUOTETARRA") {
					echo "<th>".t("Toimittajan nimi")."</th><td colspan='3'>$toimittajarow[nimi]<input type='hidden' name='toimittajaid' value='$toimittajaid'></td><td><a href='$PHP_SELF?lopetus=$lopetus&toim=$toim&ppl=$ppl&vvl=$vvl&kkl=$kkl&ppa=$ppa&vva=$vva&kka=$kka'>Vaihda toimittaja</a></td></tr>";
				}
				else {
					echo "<th>".t("Asiakkaan nimi")."</th><td colspan='3'>$asiakasrow[nimi]<input type='hidden' name='asiakasid' value='$asiakasid'></td><td><a href='$PHP_SELF?lopetus=$lopetus&toim=$toim&ppl=$ppl&vvl=$vvl&kkl=$kkl&ppa=$ppa&vva=$vva&kka=$kka'>Vaihda asiakas</a></td></tr>";
				}
			}
			else {
				if ($toim == "OSTO" or $toim == "PURKU" or $toim == "TARIFFI" or $toim == "TUOTETARRA") {
					echo "<th>".t("Toimittajan nimi")."</th><td colspan='3'><input type='text' name='ytunnus' value='$ytunnus' size='15'></td></tr>";
				}
				else {
					echo "<th>".t("Asiakkaan nimi")."</th><td colspan='3'><input type='text' name='ytunnus' value='$ytunnus' size='15'></td></tr>";
				}
			}
			
			echo "<tr><th>".t("Tilausnumero")."</th><td colspan='3'><input type='text' size='15' name='otunnus'></td></tr>";
			echo "<tr>";
    
			if ($toim == "PURKU" or $toim == "TARIFFI") {
				echo "<th>".t("Keikkanumero")."</th>";
			}
			else {
				echo "<th>".t("Laskunumero")."</th>";
			}
    
			echo "	<td colspan='3'><input type='text' size='15' name='laskunro'></td>
					<td colspan='3'><input type='text' size='15' name='laskunroloppu'></td></tr>";
		}
	
		if ($toim == "OSOITELAPPU") {
			//osoitelapuille on vähän eri päivämäärävaatimukset kuin muilla
			if (!isset($kka))
				$kka = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
			if (!isset($vva))
				$vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
			if (!isset($ppa))
				$ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
		}
		elseif ($toim == "TARJOUS" or $toim == "TARJOUS!!!VL" or $toim == "MYYNTISOPIMUS" or $toim == "MYYNTISOPIMUS!!!VL" or $toim == "OSAMAKSUSOPIMUS" or $toim == "LUOVUTUSTODISTUS" or $toim == "VAKUUTUSHAKEMUS" or $toim == "REKISTERIILMOITUS") {
			//Näissä kaupoissa voi kestää vähän kauemmin
			if (!isset($kka))
				$kka = date("m",mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
			if (!isset($vva))
				$vva = date("Y",mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
			if (!isset($ppa))
				$ppa = date("d",mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
		}
		else {
			if (!isset($kka))
				$kka = date("m",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
			if (!isset($vva))
				$vva = date("Y",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
			if (!isset($ppa))
				$ppa = date("d",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		}
    
		if (!isset($kkl))
			$kkl = date("m");
		if (!isset($vvl))
			$vvl = date("Y");
		if (!isset($ppl))
			$ppl = date("d");
    
		echo "<tr><th>".t("Alkupäivämäärä (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppa' value='$ppa' size='3'></td>
				<td><input type='text' name='kka' value='$kka' size='3'></td>
				<td><input type='text' name='vva' value='$vva' size='5'></td>
				</tr><tr><th>".t("loppupäivämäärä (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppl' value='$ppl' size='3'></td>
				<td><input type='text' name='kkl' value='$kkl' size='3'></td>
				<td><input type='text' name='vvl' value='$vvl' size='5'></td>
				<td class='back'><input type='submit' value='".t("Etsi")."'></td>";		
		echo "</table>";
	    echo "</form><br>";
    
		$formi  = 'hakuformi';
		$kentta = 'ytunnus';
	}
	
	if ($tee == "ETSILASKU") {
		$where1 = "";
		$where2 = "";

		if ($toim == "OSTO") {
			//ostotilaus kyseessä, ainoa paperi joka voidaan tulostaa on itse tilaus
			$where1 = " lasku.tila = 'O' ";

			$where2 = " and lasku.liitostunnus='$toimittajaid'
						and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
						and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59' ";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_luontiaika) ";
		}
		if ($toim == "PURKU") {
			//ostolasku jolle on kohdistettu rivejä. Tälle oliolle voidaan tulostaa purkulista
			$where1 = " lasku.tila = 'K' ";

			$where2 = " and lasku.liitostunnus='$toimittajaid'
						and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
						and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59' ";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_luontiaika) ";
		}
		if ($toim == "TARIFFI") {
			//ostolasku jolle on kohdistettu rivejä. Tälle oliolle voidaan tulostaa tariffilista
			$where1 = " lasku.tila in ('H','Y','M','P','Q') and lasku.kohdistettu in ('K','X') ";

			$where2 = " and lasku.liitostunnus='$toimittajaid'
						and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
						and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_luontiaika) ";
		}
		if ($toim == "TUOTETARRA") {
			//Ostolasku, tuotetarrat. Tälle oliolle voidaan tulostaa tuotetarroja
			$where1 = " lasku.tila in ('H','Y','M','P','Q') and lasku.kohdistettu in ('K','X') ";

			$where2 = " and lasku.liitostunnus='$toimittajaid'
						and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
						and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_luontiaika) ";
		}
		if ($toim == "SIIRTOLISTA") {
			//ostolasku jolle on kohdistettu rivejä. Tälle oliolle voidaan tulostaa tariffilista
			$where1 = " lasku.tila = 'G' ";

			$where2 = " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
						 and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

			if ($lahettava_varasto != '') {
				$where2 .= " and lasku.varasto = '$lahettava_varasto'";
			}
			if ($vastaanottava_varasto != '') {
				$where2 .= " and lasku.clearing = '$vastaanottava_varasto'";
			}

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_luontiaika) ";
		}
		if ($toim == "VALMISTUS") {
			//valmistuslista
			$where1 = " lasku.tila = 'V' ";

			if ($ytunnus{0} == '£') {
				$where2 = " and lasku.nimi      = '$asiakasrow[nimi]'
							and lasku.nimitark  = '$asiakasrow[nimitark]'
							and lasku.osoite    = '$asiakasrow[osoite]'
							and lasku.postino   = '$asiakasrow[postino]'
							and lasku.postitp   = '$asiakasrow[postitp]' ";
			}
			else {
				$where2 = " and lasku.liitostunnus  = '$asiakasid'";
			}

			$where2 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
						 and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_luontiaika) ";
		}
		if ($toim == "LASKU") {
			//myyntilasku. Tälle oliolle voidaan tulostaa laskun kopio
			$where1 = " lasku.tila = 'U' ";

			if ($ytunnus{0} == '£') {
				$where2 = " and lasku.nimi      = '$asiakasrow[nimi]'
							and lasku.nimitark  = '$asiakasrow[nimitark]'
							and lasku.osoite    = '$asiakasrow[osoite]'
							and lasku.postino   = '$asiakasrow[postino]'
							and lasku.postitp   = '$asiakasrow[postitp]' ";
			}
			else {
				$where2 = " and lasku.liitostunnus  = '$asiakasid'";
			}

			$where2 .= " and lasku.tapvm >='$vva-$kka-$ppa 00:00:00'
						 and lasku.tapvm <='$vvl-$kkl-$ppl 23:59:59' ";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_tapvm) ";
		}
		if ($toim == "SAD") {
			//myyntilasku. Tälle oliolle voidaan tulostaa laskun kopio
			$where1 = " lasku.tila = 'U' and lasku.vienti = 'K' ";

			if ($ytunnus{0} == '£') {
				$where2 = " and lasku.nimi      = '$asiakasrow[nimi]'
							and lasku.nimitark  = '$asiakasrow[nimitark]'
							and lasku.osoite    = '$asiakasrow[osoite]'
							and lasku.postino   = '$asiakasrow[postino]'
							and lasku.postitp   = '$asiakasrow[postitp]' ";
			}
			else {
				$where2 = " and lasku.liitostunnus  = '$asiakasid'";
			}

			$where2 .= " and lasku.tapvm >='$vva-$kka-$ppa 00:00:00'
						 and lasku.tapvm <='$vvl-$kkl-$ppl 23:59:59'";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_tapvm) ";
		}
		if ($toim == "LAHETE" or $toim == "PAKKALISTA") {
			//myyntitilaus. Tulostetaan lähete.
			$where1 = " lasku.tila in ('L','N','V') ";

			if ($ytunnus{0} == '£') {
				$where2 = " and lasku.nimi      = '$asiakasrow[nimi]'
							and lasku.nimitark  = '$asiakasrow[nimitark]'
							and lasku.osoite    = '$asiakasrow[osoite]'
							and lasku.postino   = '$asiakasrow[postino]'
							and lasku.postitp   = '$asiakasrow[postitp]' ";
			}
			else {
				$where2 = " and lasku.liitostunnus  = '$asiakasid'";
			}

			$where2 .= " and lasku.lahetepvm >='$vva-$kka-$ppa 00:00:00'
						 and lasku.lahetepvm <='$vvl-$kkl-$ppl 23:59:59'";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = "";
		}
		if ($toim == "KERAYSLISTA") {
			//myyntitilaus. Tulostetaan lähete.
			$where1 = " lasku.tila in ('L','N') ";

			if ($ytunnus{0} == '£') {
				$where2 = " and lasku.nimi      = '$asiakasrow[nimi]'
							and lasku.nimitark  = '$asiakasrow[nimitark]'
							and lasku.osoite    = '$asiakasrow[osoite]'
							and lasku.postino   = '$asiakasrow[postino]'
							and lasku.postitp   = '$asiakasrow[postitp]' ";
			}
			else {
				$where2 = " and lasku.liitostunnus  = '$asiakasid'";
			}

			$where2 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
						 and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_luontiaika) ";
		}
		if ($toim == "OSOITELAPPU") {
			//myyntitilaus. Tulostetaan osoitelappuja.
			$where1 = " lasku.tila in ('L','G') ";

			if ($ytunnus{0} == '£') {
				$where2 = " and lasku.nimi      = '$asiakasrow[nimi]'
							and lasku.nimitark  = '$asiakasrow[nimitark]'
							and lasku.osoite    = '$asiakasrow[osoite]'
							and lasku.postino   = '$asiakasrow[postino]'
							and lasku.postitp   = '$asiakasrow[postitp]' ";
			}
			else {
				$where2 = " and lasku.liitostunnus  = '$asiakasid'";
			}

			$where2 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
						 and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_luontiaika) ";
		}
		if ($toim == "VIENTIERITTELY") {
			//myyntitilaus. Tulostetaan vientieruttely.
			$where1 = " lasku.tila = 'U' and lasku.vienti != '' ";

			if ($ytunnus{0} == '£') {
				$where2 = " and lasku.nimi      = '$asiakasrow[nimi]'
							and lasku.nimitark  = '$asiakasrow[nimitark]'
							and lasku.osoite    = '$asiakasrow[osoite]'
							and lasku.postino   = '$asiakasrow[postino]'
							and lasku.postitp   = '$asiakasrow[postitp]' ";
			}
			else {
				$where2 = " and lasku.liitostunnus  = '$asiakasid'";
			}

			$where2 .= " and lasku.tapvm >='$vva-$kka-$ppa 00:00:00'
						 and lasku.tapvm <='$vvl-$kkl-$ppl 23:59:59'";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_tapvm) ";
		}
		if ($toim == "PROFORMA") {
			//myyntitilaus. Tulostetaan proforma.
			$where1 = " (lasku.tila in ('L','U','N') or (tila = 'N' and alatila IN ('A','T','U')))";

			if ($ytunnus{0} == '£') {
				$where2 = " and lasku.nimi      = '$asiakasrow[nimi]'
							and lasku.nimitark  = '$asiakasrow[nimitark]'
							and lasku.osoite    = '$asiakasrow[osoite]'
							and lasku.postino   = '$asiakasrow[postino]'
							and lasku.postitp   = '$asiakasrow[postitp]' ";
			}
			else {
				$where2 = " and lasku.liitostunnus  = '$asiakasid'";
			}

			$where2 .= " and lasku.lahetepvm >='$vva-$kka-$ppa 00:00:00'
						 and lasku.lahetepvm <='$vvl-$kkl-$ppl 23:59:59'";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = "";
		}
		if ($toim == "TILAUSVAHVISTUS") {
			//myyntitilaus. Tulostetaan proforma.
			$where1 = " lasku.tila in ('N','L','U','R')";

			if ($ytunnus{0} == '£') {
				$where2 = " and lasku.nimi      = '$asiakasrow[nimi]'
							and lasku.nimitark  = '$asiakasrow[nimitark]'
							and lasku.osoite    = '$asiakasrow[osoite]'
							and lasku.postino   = '$asiakasrow[postino]'
							and lasku.postitp   = '$asiakasrow[postitp]' ";
			}
			else {
				$where2 = " and lasku.liitostunnus  = '$asiakasid'";
			}

			$where2 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
						 and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = "";
		}
		if ($toim == "TARJOUS" or $toim == "TARJOUS!!!VL" or $toim == "MYYNTISOPIMUS" or $toim == "MYYNTISOPIMUS!!!VL" or $toim == "OSAMAKSUSOPIMUS" or $toim == "LUOVUTUSTODISTUS" or $toim == "VAKUUTUSHAKEMUS" or $toim == "REKISTERIILMOITUS") {
			// Tulostellaan venemyyntiin liittyviä osia
			$where1 = " lasku.tila in ('L','T','N') ";

			if ($ytunnus{0} == '£') {
				$where2 = " and lasku.nimi      = '$asiakasrow[nimi]'
							and lasku.nimitark  = '$asiakasrow[nimitark]'
							and lasku.osoite    = '$asiakasrow[osoite]'
							and lasku.postino   = '$asiakasrow[postino]'
							and lasku.postitp   = '$asiakasrow[postitp]' ";
			}
			else {
				$where2 = " and lasku.liitostunnus  = '$asiakasid'";
			}

			$where2 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
						 and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_luontiaika) ";
		}
		if ($toim == "TYOMAARAYS") {
			/// Työmääräys
			$where1 = " lasku.tila in ('L','A','N','S','T')";

			if ($ytunnus{0} == '£') {
				$where2 = " and lasku.nimi      = '$asiakasrow[nimi]'
							and lasku.nimitark  = '$asiakasrow[nimitark]'
							and lasku.osoite    = '$asiakasrow[osoite]'
							and lasku.postino   = '$asiakasrow[postino]'
							and lasku.postitp   = '$asiakasrow[postitp]' ";
			}
			else {
				$where2 = " and lasku.liitostunnus  = '$asiakasid'";
			}

			$where2 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
						 and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_luontiaika) ";
		}

		if ($laskunro > 0) {
			$where2 = " and lasku.laskunro = '$laskunro' ";
			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (lasno_index) ";
		}

		if ($otunnus > 0) {
			//katotaan löytyykö lasku ja sen kaikki tilaukset
			$query = "  SELECT laskunro
						FROM lasku
						WHERE tunnus = '$otunnus' and lasku.yhtio = '$kukarow[yhtio]'";
			$laresult = mysql_query($query) or pupe_error($query);
			$larow = mysql_fetch_array($laresult);

			if ($larow["laskunro"] > 0) {
				$where2 = " and lasku.laskunro = '$larow[laskunro]' ";
				if (!isset($jarj)) $jarj = " lasku.tunnus desc";
				$use = " use index (lasno_index) ";
			}
			else {
				$where2 = " and lasku.tunnus = '$otunnus' ";
				if (!isset($jarj)) $jarj = " lasku.tunnus desc";
				$use = " use index (PRIMARY) ";
			}
		}

		// Mihin järjestykseen laitetaan
		if ($jarj != ''){
			$jarj = "ORDER BY $jarj";
		}

		// Etsitään muutettavaa tilausta
		$query = "  SELECT lasku.tunnus Tilaus, if(lasku.laskunro=0, '', laskunro) Laskunro,
					concat_ws(' ', lasku.nimi, lasku.nimitark) Asiakas, lasku.ytunnus Ytunnus,
					if(lasku.tapvm = '0000-00-00', lasku.luontiaika, lasku.tapvm) Pvm,
					if(kuka.nimi!=''and kuka.nimi is not null, kuka.nimi, lasku.laatija) Laatija,
					if(lasku.summa=0, (SELECT round(sum(hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))), 2) FROM tilausrivi WHERE tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus), lasku.summa) Summa,
					toimaika Toimitusaika,
					lasku.tila, lasku.alatila
					FROM lasku $use
					LEFT JOIN kuka ON kuka.yhtio=lasku.yhtio and kuka.kuka=lasku.laatija
					WHERE $where1 $where2
					and lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila != 'D'
					$jarj";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {

			echo "<td class='back'>
						<form method='post' action='$PHP_SELF'>
						<input type='hidden' name='lopetus' value='$lopetus'>
						<input type='hidden' name='tee' value='$tee'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='tila' value='monta'>
						<input type='hidden' name='ytunnus' value='$ytunnus'>
						<input type='hidden' name='asiakasid' value='$asiakasid'>
						<input type='hidden' name='toimittajaid' value='$toimittajaid'>
						<input type='hidden' name='ppa' value='$ppa'>
						<input type='hidden' name='kka' value='$kka'>
						<input type='hidden' name='vva' value='$vva'>
						<input type='hidden' name='ppl' value='$ppl'>
						<input type='hidden' name='kkl' value='$kkl'>
						<input type='hidden' name='vvl' value='$vvl'>
						<input type='submit' value='".t("Tulosta useita kopioita")."'></form></td>";


			echo "<table border='0' cellpadding='2' cellspacing='1'>";
			echo "<tr>";

			for ($i=0; $i < mysql_num_fields($result)-2; $i++) {
				$jarj = $i+1;

				echo "<th><a href='$PHP_SELF?tee=$tee&ppl=$ppl&vvl=$vvl&kkl=$kkl&ppa=$ppa&vva=$vva&kka=$kka&toim=$toim&ytunnus=$ytunnus&asiakasid=$asiakasid&toimittajaid=$toimittajaid&jarj=$jarj'>".t(mysql_field_name($result,$i))."</a></th>";
			}
			echo "<th>".t("Tyyppi")."</th>";

			if ($tila == 'monta') {
				echo "<th>".t("Tulosta")."</th>";

				echo "  <form method='post' action='$PHP_SELF' autocomplete='off'>
						<input type='hidden' name='lopetus' value='$lopetus'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='tee' value='TULOSTA'>";
			}

			echo "</tr>";

			while ($row = mysql_fetch_array($result)) {
				echo "<tr>";
				$ero="td";

				if ($tunnus==$row['Tilaus']) $ero="th";

				echo "<tr>";

				for ($i=0; $i<mysql_num_fields($result)-2; $i++) {

					if ($i==4 or $i==6 or $i==7) {
						$ali = " align='right' ";
					}
					else {
						$ali = " align='left' ";
					}

					if ($i==4 or $i==7) {
						echo "<$ero $ali>".tv1dateconv($row[$i])."</$ero>";
					}
					else {
						echo "<$ero $ali>$row[$i]</$ero>";
					}
				}

				$laskutyyppi = $row["tila"];
				$alatila     = $row["alatila"];

				//tehdään selväkielinen tila/alatila
				require "../inc/laskutyyppi.inc";

				echo "<$ero>".t("$laskutyyppi")." ".t("$alatila")."</$ero>";

				if ($tila != 'monta') {

					echo "<td class='back'>
							<form method='post' action='$PHP_SELF'>
							<input type='hidden' name='lopetus' value='$lopetus'>
							<input type='hidden' name='tee' value='NAYTAHTML'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='tunnus' value='$row[Tilaus]'>
							<input type='hidden' name='ytunnus' value='$ytunnus'>
							<input type='hidden' name='asiakasid' value='$asiakasid'>
							<input type='hidden' name='toimittajaid' value='$toimittajaid'>
							<input type='hidden' name='otunnus' value='$otunnus'>
							<input type='hidden' name='laskunro' value='$laskunro'>
							<input type='hidden' name='ppa' value='$ppa'>
							<input type='hidden' name='kka' value='$kka'>
							<input type='hidden' name='vva' value='$vva'>
							<input type='hidden' name='ppl' value='$ppl'>
							<input type='hidden' name='kkl' value='$kkl'>
							<input type='hidden' name='vvl' value='$vvl'>
							<input type='submit' value='".t("Näytä ruudulla")."'></form></td>";

					echo "<td class='back'>
							<form method='post' action='$PHP_SELF' autocomplete='off'>
							<input type='hidden' name='lopetus' value='$lopetus'>
							<input type='hidden' name='otunnus' value='$row[Tilaus]'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='tee' value='NAYTATILAUS'>
							<input type='submit' value='".t("Näytä pdf")."'></form></td>";

					echo "<td class='back'>
							<form method='post' action='$PHP_SELF' autocomplete='off'>
							<input type='hidden' name='otunnus' value='$row[Tilaus]'>
							<input type='hidden' name='lopetus' value='$lopetus'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='tee' value='TULOSTA'>
							<input type='submit' value='".t("Tulosta")."'></form></td>";
				}

				if ($tila == 'monta') {
					echo "<td><input type='checkbox' name='tulostukseen[]' value='$row[Tilaus]'></td>";
				}

				echo "</tr>";
			}

			if ($tila == 'monta') {
				echo "<tr><td colspan='8' class='back'></td><td class='back'><input type='submit' value='".t("Tulosta")."'></form></td></tr>";
			}

			echo "</table>";
		}
		else {
			echo "<font class='error'>".t("Ei tilauksia")."...</font><br><br>";
		}
	}

	if ($tee == "TULOSTA" or $tee == 'NAYTATILAUS') {

		//valitaan tulostin heti alkuun, jos se ei ole vielä valittu
		if ($toim == "OSTO") {
			$tulostimet[0] = 'Ostotilaus';
			if ($kappaleet > 0 and $komento["Ostotilaus"] != 'email') {
				$komento["Ostotilaus"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "PURKU") {
			$tulostimet[0] = 'Purkulista';
			if ($kappaleet > 0 and $komento["Purkulista"] != 'email') {
				$komento["Purkulista"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "TARIFFI") {
			$tulostimet[0] = 'Tariffilista';
			if ($kappaleet > 0 and $komento["Tariffilista"] != 'email') {
				$komento["Tariffilista"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "LASKU") {
			$tulostimet[0] = 'Lasku';
			if ($kappaleet > 0 and $komento["Lasku"] != 'email') {
				$komento["Lasku"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "TUOTETARRA") {
			$tulostimet[0] = 'Tuotetarrat';
			if ($kappaleet > 0 and $komento["Tuotetarrat"] != 'email') {
				$komento["Tuotetarrat"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "SIIRTOLISTA") {
			$tulostimet[0] = 'Siirtolista';
			if ($kappaleet > 0 and $komento["Siirtolista"] != 'email') {
				$komento["Siirtolista"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "VALMISTUS") {
			$tulostimet[0] = 'Valmistus';
			if ($kappaleet > 0 and $komento["Valmistus"] != 'email') {
				$komento["Valmistus"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "SAD") {
			$tulostimet[0] = 'SAD-lomake';

			if ($yhtiorow["sad_lomake_tyyppi"] == "T") {
				$tulostimet[1] = 'SAD-lomake lisäsivu';
			}

			if ($kappaleet > 0 and $komento["SAD-lomake"] != 'email') {
				$komento["SAD-lomake"] .= " -# $kappaleet ";
			}

			if ($yhtiorow["sad_lomake_tyyppi"] == "T") {
				if ($kappaleet > 0 and $komento["SAD-lomake lisäsivu"] != 'email') {
					$komento["SAD-lomake lisäsivu"] .= " -# $kappaleet ";
				}
			}
		}
		if ($toim == "LAHETE") {
			$tulostimet[0] = 'Lähete';
			if ($kappaleet > 0 and $komento["Lähete"] != 'email') {
				$komento["Lähete"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "PAKKALISTA") {
			$tulostimet[0] = 'Pakkalista';
			if ($kappaleet > 0 and $komento["pakkalista"] != 'email') {
				$komento["Pakkalista"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "KERAYSLISTA") {
			$tulostimet[0] = 'Keräyslista';
			if ($kappaleet > 0 and $komento["Keräyslista"] != 'email') {
				$komento["Keräyslista"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "OSOITELAPPU") {
			$tulostimet[0] = 'Osoitelappu';
			if ($kappaleet > 0 and $komento["Osoitelappu"] != 'email') {
				$komento["Osoitelappu"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "VIENTIERITTELY") {
			$tulostimet[0] = 'Vientierittely';
			if ($kappaleet > 0 and $komento["Vientierittely"] != 'email') {
				$komento["Vientierittely"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "PROFORMA" and $komento["Lasku"] != 'email') {
			$tulostimet[0] = 'Lasku';
			if ($kappaleet > 0) {
				$komento["Lasku"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "TILAUSVAHVISTUS" and $komento["Lasku"] != 'email') {
			$tulostimet[0] = 'Tilausvahvistus';
			if ($kappaleet > 0) {
				$komento["Tilausvahvistus"] .= " -# $kappaleet ";
			}
		}
		if (($toim == "TARJOUS"  or $toim == "TARJOUS!!!VL") and $komento["Tarjous"] != 'email' and substr($komento["Tarjous"],0,12) != 'asiakasemail') {
			$tulostimet[0] = 'Tarjous';
			if ($kappaleet > 0) {
				$komento["Tarjous"] .= " -# $kappaleet ";
			}
		}
		if (($toim == "MYYNTISOPIMUS" or $toim == "MYYNTISOPIMUS!!!VL") and $komento["Myyntisopimus"] != 'email') {
			$tulostimet[0] = 'Myyntisopimus';
			if ($kappaleet > 0) {
				$komento["Myyntisopimus"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "OSAMAKSUSOPIMUS" and $komento["Osamaksusopimus"] != 'email') {
			$tulostimet[0] = 'Osamaksusopimus';
			if ($kappaleet > 0) {
				$komento["Osamaksusopimus"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "LUOVUTUSTODISTUS" and $komento["Luovutustodistus"] != 'email') {
			$tulostimet[0] = 'Luovutustodistus';
			if ($kappaleet > 0) {
				$komento["Luovutustodistus"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "VAKUUTUSHAKEMUS" and $komento["Vakuutushakemus"] != 'email') {
			$tulostimet[0] = 'Vakuutushakemus';
			if ($kappaleet > 0) {
				$komento["Vakuutushakemus"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "REKISTERIILMOITUS" and $komento["Rekisteröinti_ilmoitus"] != 'email') {
			$tulostimet[0] = 'Rekisteröinti_ilmoitus';
			if ($kappaleet > 0) {
				$komento["Rekisteröinti_ilmoitus"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "TYOMAARAYS" and $komento["Työmääräys"] != 'email') {
			$tulostimet[0] = 'Työmääräys';
			if ($kappaleet > 0) {
				$komento["Työmääräys"] .= " -# $kappaleet ";
			}
		}

		if (isset($tulostukseen)) {
			$laskut="";
			foreach ($tulostukseen as $tun) {
				$laskut .= "$tun,";
			}
			$tilausnumero = substr($laskut,0,-1); // vika pilkku pois
		}

		if ($otunnus == '' and $laskunro == '' and $tilausnumero == '') {
			echo "<font class='error'>".t("VIRHE: Et valinnut mitään tulostettavaa")."!</font>";
			exit;
		}

		if ((count($komento) == 0) and ($tee == 'TULOSTA')) {
			require("../inc/valitse_tulostin.inc");
		}

		//hateaan laskun kaikki tiedot
		$query = "  SELECT *
					FROM lasku
					WHERE";

		if ($tilausnumero != '') {
			$query .= " tunnus in ($tilausnumero) ";
		}
		else {
			if ($otunnus > 0) {
				$query .= " tunnus='$otunnus' ";
			}
			if ($laskunro > 0) {
				if ($otunnus > 0) {
					$query .= " and laskunro='$laskunro' ";
				}
				else {
					$query .= " tila='U' and laskunro='$laskunro' ";
				}
			}
		}

		$query .= " AND yhtio ='$kukarow[yhtio]'";
		$rrrresult = mysql_query($query) or pupe_error($query);

		while($laskurow = mysql_fetch_array($rrrresult)) {

			if ($toim == "OSTO") {
				$otunnus = $laskurow["tunnus"];
				require('tulosta_ostotilaus.inc');
				$tee = '';
			}

			if ($toim == "PURKU") {
				$otunnus = $laskurow["tunnus"];
				$mista = 'tulostakopio';
				require('tulosta_purkulista.inc');
				$tee = '';
			}

			if ($toim == "TUOTETARRA") {
				$otunnus = $laskurow["tunnus"];
				require('tulosta_tuotetarrat.inc');
				$tee = '';
			}

			if($toim == "TARIFFI") {
				$otunnus = $laskurow["tunnus"];
				require('tulosta_tariffilista.inc');
				$tee = '';
			}

			if ($toim == "SAD") {
				$uusiotunnus = $laskurow["tunnus"];

				if ($yhtiorow["sad_lomake_tyyppi"] == "T" and $tee != 'NAYTATILAUS') {

					require('tulosta_sadvientiilmo_teksti.inc');

					if ($paalomake != '') {
						lpr($paalomake,0, $komento["SAD-lomake"]);

						echo t("SAD-lomake tulostuu")."...<br>";

						if ($lisalomake != "") {
							lpr($lisalomake,0, $komento["SAD-lomake lisäsivu"]);

							echo t("SAD-lomakkeen lisäsivu tulostuu")."...<br>";
						}
					}
				}
				else {
					require_once('pdflib/phppdflib.class.php');

					require('tulosta_sadvientiilmo.inc');

					//keksitään uudelle failille joku varmasti uniikki nimi:
					list($usec, $sec) = explode(' ', microtime());
					mt_srand((float) $sec + ((float) $usec * 100000));
					$pdffilenimi = "/tmp/SAD_Lomake_Kopio-".md5(uniqid(mt_rand(), true)).".pdf";

					//kirjoitetaan pdf faili levylle..
					$fh = fopen($pdffilenimi, "w");
					if (fwrite($fh, $pdf2->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
					fclose($fh);

					// itse print komento...
					if ($komento["SAD-lomake"] == 'email') {
						$liite = $pdffilenimi;
						$kutsu = "SAD-lomake";

						require("../inc/sahkoposti.inc");
					}
					elseif ($tee == 'NAYTATILAUS') {
						//Työnnetään tuo pdf vaan putkeen!
						echo file_get_contents($pdffilenimi);
					}
					elseif ($komento["SAD-lomake"] != '' and $komento["SAD-lomake"] != 'edi') {
						$line = exec($komento["SAD-lomake"]." ".$pdffilenimi);
					}

					//poistetaan tmp file samantien kuleksimasta...
					system("rm -f $pdffilenimi");

					if ($tee != 'NAYTATILAUS') {
						echo t("SAD-lomake tulostuu")."...<br>";
					}
				}

				$tee = '';
			}

			if ($toim == "VIENTIERITTELY") {
				$uusiotunnus = $laskurow["tunnus"];

				require_once('pdflib/phppdflib.class.php');
				require('tulosta_vientierittely.inc');

				//keksitään uudelle failille joku varmasti uniikki nimi:
				list($usec, $sec) = explode(' ', microtime());
				mt_srand((float) $sec + ((float) $usec * 100000));
				$pdffilenimi = "/tmp/Vientierittely_Kopio-".md5(uniqid(mt_rand(), true)).".pdf";

				//kirjoitetaan pdf faili levylle..
				$fh = fopen($pdffilenimi, "w");
				if (fwrite($fh, $Xpdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
				fclose($fh);

				// itse print komento...
				if ($komento["Vientierittely"] == 'email') {
					$liite = $pdffilenimi;
					$kutsu = "Vientierittely";

					require("../inc/sahkoposti.inc");
				}
				elseif ($tee == 'NAYTATILAUS') {
					//Työnnetään tuo pdf vaan putkeen!
					echo file_get_contents($pdffilenimi);
				}
				elseif ($komento["Vientierittely"] != '' and $komento["Vientierittely"] != 'edi') {
					$line = exec($komento["Vientierittely"]." ".$pdffilenimi);
				}
				//poistetaan tmp file samantien kuleksimasta...
				system("rm -f $pdffilenimi");

				if ($tee != 'NAYTATILAUS') {
					echo t("Vientierittely tulostuu")."...<br>";
				}
				$tee = '';
			}

			if ($toim == "LASKU" or $toim == 'PROFORMA') {
				$otunnus = $laskurow["tunnus"];

				// haetaan maksuehdon tiedot
				$query  = "	select *
							from maksuehto
							left join pankkiyhteystiedot on (pankkiyhteystiedot.yhtio=maksuehto.yhtio and pankkiyhteystiedot.tunnus=maksuehto.pankkiyhteystiedot)
							where maksuehto.yhtio='$kukarow[yhtio]' and maksuehto.tunnus='$laskurow[maksuehto]'";
				$result = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result) == 0) {
					$masrow = array();
					if ($laskurow["erpcm"] == "0000-00-00") {
						echo "<font class='error'>".t("VIRHE: Maksuehtoa ei löydy")."! $laskurow[maksuehto]!</font>";
					}
				}
				else {
					$masrow = mysql_fetch_array($result);
				}

				//maksuehto tekstinä
				$maksuehto      = $masrow["teksti"]." ".$masrow["kassa_teksti"];
				$kateistyyppi   = $masrow["kateinen"];
				
				//tilausyhteyshenkilo asiakkaan_tilausnumero kohde
				if (trim($laskurow['tilausyhteyshenkilo']) != '') {
					$laskurow['sisviesti1'] .= "\n"."Tilaaja: ".$laskurow['tilausyhteyshenkilo'];
				}
				
				if (trim($laskurow['asiakkaan_tilausnumero']) != '') {
					$laskurow['sisviesti1'] .= "\n"."Tilaajan tilausnumero: ".$laskurow['asiakkaan_tilausnumero'];								
				}
				
				if (trim($laskurow['kohde']) != '') {
					$laskurow['sisviesti1'] .= "\n"."Kohde: ".$laskurow['kohde'];								
				}

				if ($yhtiorow['laskutyyppi'] == 3) {
					require_once ("tulosta_lasku_simppeli.inc");
					tulosta_lasku($otunnus, $komento["Lasku"], $kieli, $toim, $tee);
					$tee = '';
				}
				else {
					require_once("tulosta_lasku.inc");

					if ($laskurow["tila"] == 'U') {
						$where = " uusiotunnus='$otunnus' ";
					}
					else {
						$where = " otunnus='$otunnus' ";
					}

					// katotaan miten halutaan sortattavan
					$sorttauskentta = generoi_sorttauskentta();

					if ($toim == 'PROFORMA') {
						if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $laskurow["vienti_kurssi"] != 0) {
							$hinta_riv = "(tilausrivi.hinta/$laskurow[vienti_kurssi])";
						}
						else {
							$hinta_riv = "tilausrivi.hinta";
						}

						$lisa = " 	$hinta_riv / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)) rivihinta,
									(varattu+kpl) kpl, ";
					}
					else {
						$lisa = "";
					}

					// haetaan tilauksen kaikki rivit
					$query = "  SELECT *, $lisa $sorttauskentta
								FROM tilausrivi
								WHERE $where
								and yhtio  = '$kukarow[yhtio]'
								and tyyppi = 'L'
								ORDER BY otunnus, sorttauskentta, tunnus";
					$result = mysql_query($query) or pupe_error($query);

					//kuollaan jos yhtään riviä ei löydy
					if (mysql_num_rows($result) == 0) {
						echo t("Laskurivejä ei löytynyt");
						exit;
					}

					unset($pdf);
					unset($page);

					$sivu 	= 1;
					$summa 	= 0;
					$arvo 	= 0;

					// aloitellaan laskun teko
					$page[$sivu] = alku();

					while ($row = mysql_fetch_array($result)) {
						rivi($page[$sivu]);
					}

					alvierittely($page[$sivu]);

					//keksitään uudelle failille joku varmasti uniikki nimi:
					list($usec, $sec) = explode(' ', microtime());
					mt_srand((float) $sec + ((float) $usec * 100000));
					$pdffilenimi = "/tmp/Lasku_Kopio-".md5(uniqid(mt_rand(), true)).".pdf";

					//kirjoitetaan pdf faili levylle..
					$fh = fopen($pdffilenimi, "w");
					if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
					fclose($fh);

					// itse print komento...
					if ($komento["Lasku"] == 'email') {
						$liite = $pdffilenimi;
						if($laskurow["laskunro"] > 0) {
							$kutsu = "Lasku {$laskurow["laskunro"]}";
						}
						else {
							$kutsu = "Lasku";
						}
						

						require("../inc/sahkoposti.inc");
					}
					elseif ($tee == 'NAYTATILAUS') {
						//Työnnetään tuo pdf vaan putkeen!
						echo file_get_contents($pdffilenimi);
					}
					elseif ($komento["Lasku"] != '' and $komento["Lasku"] != 'edi') {
						$line = exec("$komento[Lasku] $pdffilenimi");
					}

					//poistetaan tmp file samantien kuleksimasta...
					system("rm -f $pdffilenimi");

					unset($pdf);
					unset($page);

					if ($tee != 'NAYTATILAUS') {
						echo t("Lasku tulostuu")."...<br>";
						$tee = '';
					}
				}
			}

			if ($toim == "TILAUSVAHVISTUS") {
				$otunnus = $laskurow["tunnus"];

				require_once ("tulosta_tilausvahvistus_pdf.inc");

				tulosta_tilausvahvistus($otunnus, $komento["Tilausvahvistus"], $kieli, $tee);

				$tee = '';
			}

			if ($toim == "TARJOUS" or $toim == "TARJOUS!!!VL") {
				$otunnus = $laskurow["tunnus"];
				list ($toimalku, $hinnat) = explode("!!!", $toim);

				require_once ("tulosta_tarjous.inc");

				tulosta_tarjous($otunnus, $komento["Tarjous"], $kieli, $tee, $hinnat);

				$tee = '';
			}

			if ($toim == "MYYNTISOPIMUS" or $toim == "MYYNTISOPIMUS!!!VL") {

				$otunnus = $laskurow["tunnus"];
				list ($toimalku, $hinnat) = explode("!!!", $toim);

				require_once ("tulosta_myyntisopimus.inc");

				tulosta_myyntisopimus($otunnus, $komento["Myyntisopimus"], $kieli, $tee, $hinnat);

				$tee = '';
			}

			if ($toim == "OSAMAKSUSOPIMUS") {

				$otunnus = $laskurow["tunnus"];

				require_once ("tulosta_osamaksusoppari.inc");

				tulosta_osamaksusoppari($otunnus, $komento["Osamaksusopimus"], $kieli, $tee);

				$tee = '';
			}

			if ($toim == "LUOVUTUSTODISTUS") {

				$otunnus = $laskurow["tunnus"];

				require_once ("tulosta_luovutustodistus.inc");

				tulosta_luovutustodistus($otunnus, $komento["Luovutustodistus"], $kieli, $tee);

				$tee = '';
			}

			if ($toim == "VAKUUTUSHAKEMUS") {

				$otunnus = $laskurow["tunnus"];

				require_once ("tulosta_vakuutushakemus.inc");

				tulosta_vakuutushakemus($otunnus, $komento["Vakuutushakemus"], $kieli, $tee);

				$tee = '';
			}

			if ($toim == "REKISTERIILMOITUS") {

				$otunnus = $laskurow["tunnus"];

				require_once ("tulosta_rekisteriilmoitus.inc");

				tulosta_rekisteriilmoitus($otunnus, $komento["Rekisteröinti_ilmoitus"], $kieli, $tee);

				$tee = '';
			}

			if ($toim == "TYOMAARAYS") {
				//Tehdään joini
				$query = "  SELECT tyomaarays.*, lasku.*
							FROM lasku
							LEFT JOIN tyomaarays ON tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus
							WHERE lasku.yhtio='$kukarow[yhtio]'
							and lasku.tunnus='$laskurow[tunnus]'";
				$result = mysql_query($query) or pupe_error($query);
				$laskurow = mysql_fetch_array($result);

				require_once('../tyomaarays/tulosta_tyomaarays.inc');

				$query  = "	SELECT *
							FROM asiakas
							WHERE yhtio='$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]'";
				$result = mysql_query($query) or pupe_error($query);
				$asiakasrow = mysql_fetch_array($result);


				if ($laskurow["tila"] == 'U') {
					$where = " uusiotunnus='$laskurow[tunnus]' ";
				}
				else {
					$where = " otunnus='$laskurow[tunnus]' ";
				}

				// aloitellaan laskun teko
				$firstpage = alku();

				tyokommentit($firstpage);

				// haetaan tilauksen kaikki rivit
				$query = "  SELECT tilausrivi.*, round(tilausrivi.varattu*tilausrivi.hinta*(1-(tilausrivi.ale/100)),2) rivihinta,
							if (tuotetyyppi='K','TT','VV') tuotetyyppi,
							if(tilausrivi.perheid=0 and tilausrivi.perheid2=0, tilausrivi.tunnus, if(tilausrivi.perheid>0,tilausrivi.perheid,if(tilausrivi.perheid2>0,tilausrivi.perheid2,tilausrivi.tunnus))) as sorttauskentta
							FROM tilausrivi
							LEFT JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tilausrivi.tuoteno=tuote.tuoteno
							WHERE $where
							and tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.tyyppi in ('L','G','T')
							ORDER by tuotetyyppi, sorttauskentta desc, tunnus";
				$presult = mysql_query($query) or pupe_error($query);

				$rivino = 1;
				while ($row = mysql_fetch_array($presult)) {
					rivi($firstpage);
					$rivino++;
				}

				loppu($firstpage);

				//keksitään uudelle failille joku varmasti uniikki nimi:
				list($usec, $sec) = explode(' ', microtime());
				mt_srand((float) $sec + ((float) $usec * 100000));
				$pdffilenimi = "/tmp/Lasku_Kopio-".md5(uniqid(mt_rand(), true)).".pdf";

				//kirjoitetaan pdf faili levylle..
				$fh = fopen($pdffilenimi, "w");
				if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
				fclose($fh);

				// itse print komento...
				if ($komento["Työmääräys"] == 'email') {
					$liite = $pdffilenimi;
					$kutsu = "Työmääräys";

					require("../inc/sahkoposti.inc");
				}
				elseif ($tee == 'NAYTATILAUS') {
					//Työnnetään tuo pdf vaan putkeen!
					echo file_get_contents($pdffilenimi);
				}
				elseif ($komento["Työmääräys"] != '' and $komento["Työmääräys"] != 'edi') {
					$line = exec("$komento[Työmääräys] $pdffilenimi");
				}

				//poistetaan tmp file samantien kuleksimasta...
				system("rm -f $pdffilenimi");

				if ($tee != 'NAYTATILAUS') {
					echo t("Työmääräys tulostuu")."...<br>";
					$tee = '';
				}
			}

			if ($toim == "SIIRTOLISTA") {
				if ($tilausnumeroita == '') {
					$tilausnumeroita = $laskurow['tunnus'];
				}

				#todo pitää lisätä tulosta_siirtolista.inc:iin toimitustapa, tilausviite ja katsoa että varastot menee oikein.
				//require_once ("tulosta_siirtolista.inc");

				require_once ("tulosta_lahete_kerayslista.inc");

				//tehdään uusi PDF failin olio
				$pdf= new pdffile;
				$pdf->set_default('margin', 0);

				//generoidaan lähetteelle ja keräyslistalle rivinumerot
				$query = "  SELECT *, concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta
							FROM tilausrivi
							WHERE otunnus = '$laskurow[tunnus]' and yhtio='$kukarow[yhtio]'
							ORDER BY sorttauskentta, tuoteno";
				$result = mysql_query($query) or pupe_error($query);

				//generoidaan rivinumerot
				$rivinumerot = array();

				$kal = 1;

				while ($row = mysql_fetch_array($result)) {
					$rivinumerot[$row["tunnus"]] = $row["tunnus"];
				}

				sort($rivinumerot);

				$kal = 1;

				foreach($rivinumerot as $rivino) {
					$rivinumerot[$rivino] = $kal;
					$kal++;
				}

				mysql_data_seek($result,0);

				//pdf:n header..
				$firstpage = alku();

				while ($row = mysql_fetch_array($result)) {
					//piirrä rivi
					$firstpage = rivi($firstpage);
				}

				loppu($firstpage, 1);

				print_pdf($komento["Siirtolista"]);

				echo t("Siirtolista tulostuu")."...<br>";
				$tee = '';
			}

			if ($toim == "VALMISTUS") {

				require_once ("tulosta_valmistus.inc");

				//tehdään uusi PDF failin olio
				$pdf_valm= new pdffile;
				$pdf_valm->set_default('margin', 0);

				$tilausnumeroita = $laskurow["tunnus"];

				//generoidaan lähetteelle ja keräyslistalle rivinumerot
				$query = " 	SELECT *, if(tilausrivi.perheid=0 and tilausrivi.perheid2=0, tilausrivi.tunnus, if(tilausrivi.perheid>0,tilausrivi.perheid,if(tilausrivi.perheid2>0,tilausrivi.perheid2,tilausrivi.tunnus))) as sorttauskentta
							FROM tilausrivi use index (yhtio_otunnus)
							WHERE otunnus = '$laskurow[tunnus]'
							and yhtio = '$kukarow[yhtio]'
							and var in ('','H')
							ORDER by sorttauskentta desc, tunnus";
				$result = mysql_query($query) or pupe_error($query);

				//generoidaan rivinumerot
				$rivinumerot = array();

				$kal = 1;

				while ($row = mysql_fetch_array($result)) {
					$rivinumerot[$row["tunnus"]] = $row["tunnus"];
				}

				sort($rivinumerot);

				$kal = 1;

				foreach($rivinumerot as $rivino) {
					$rivinumerot[$rivino] = $kal;
					$kal++;
				}

				mysql_data_seek($result,0);

				//pdf:n header..
				$firstpage_valm = alku_valm();

				$perhe = "KALA";

				while ($row = mysql_fetch_array($result)) {
					//piirrä rivi
					$firstpage_valm = rivi_valm($firstpage_valm);
				}

				$x[0] = 20;
				$x[1] = 580;
				$y[0] = $y[1] = $kala_valm+$rivinkorkeus-4;
				$pdf_valm->draw_line($x, $y, $firstpage_valm, $rectparam);


				loppu_valm($firstpage_valm, 1);

				print_pdf_valm($komento["Valmistus"]);

				echo t("Valmistus tulostuu")."...<br>";
				$tee = '';
			}

			if ($toim == "LAHETE" or $toim == "PAKKALISTA") {

				$otunnus = $laskurow["tunnus"];

				//hatetaan asiakkaan lähetetyyppi
				$query = "  SELECT lahetetyyppi, luokka, puhelin
							FROM asiakas
							WHERE tunnus='$laskurow[liitostunnus]' and yhtio='$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);
				$asrow = mysql_fetch_array($result);

				$lahetetyyppi = "";

				if ($asrow["lahetetyyppi"] != '') {
					$lahetetyyppi = $asrow["lahetetyyppi"];
				}
				else {
					//Haetaan yhtiön oletuslähetetyyppi
					$query = "  SELECT selite
								FROM avainsana
								WHERE yhtio = '$kukarow[yhtio]' and laji = 'LAHETETYYPPI'
								ORDER BY jarjestys, selite
								LIMIT 1";
					$vres = mysql_query($query) or pupe_error($query);
					$vrow = mysql_fetch_array($vres);

					if ($vrow["selite"] != '' and file_exists($vrow["selite"])) {
						$lahetetyyppi = $vrow["selite"];
					}
				}

				if ($lahetetyyppi == "tulosta_lahete_alalasku.inc") {
					require_once ("tulosta_lahete_alalasku.inc");
				}
				elseif (strpos($lahetetyyppi,'simppeli') !== FALSE) {
					require_once ("$lahetetyyppi");
				}
				else {
					require_once ("tulosta_lahete.inc");
				}

				//	Jos meillä on funktio tulosta_lahete meillä on suora funktio joka hoitaa koko tulostuksen
				if(function_exists("tulosta_lahete")) {
					if($vrow["selite"] != '') {
						$tulostusversio = $vrow["selite"];
					}
					else {
						$tulostusversio = $asrow["lahetetyyppi"];
					}
					if($toim == "PAKKALISTA") {
						tulosta_lahete($otunnus, $komento["Pakkalista"], $kieli = "", $toim, $tee, $tulostusversio);
					}
					else {
						tulosta_lahete($otunnus, $komento["Lähete"], $kieli = "", $toim, $tee, $tulostusversio);
					}
				}
				else {
					// katotaan miten halutaan sortattavan
					$sorttauskentta = generoi_sorttauskentta();

					if($laskurow["tila"] == "L" or $laskurow["tila"] == "N") {
						$tyyppilisa = " and tilausrivi.tyyppi in ('L') ";
					}
					else {
						$tyyppilisa = " and tilausrivi.tyyppi in ('L','G','W') ";
					}

					//generoidaan lähetteelle ja keräyslistalle rivinumerot
					$query = "  SELECT tilausrivi.*,
								round(if(tuote.myymalahinta != 0, tuote.myymalahinta, tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1)),2) ovhhinta,
								round(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)),2) rivihinta,
								$sorttauskentta,
								if(tilausrivi.var='J', 1, 0) jtsort
								FROM tilausrivi
								JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
								JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
								WHERE tilausrivi.otunnus = '$otunnus'
								and tilausrivi.yhtio = '$kukarow[yhtio]'
								$tyyppilisa
								ORDER BY jtsort, sorttauskentta";
					$riresult = mysql_query($query) or pupe_error($query);

					//generoidaan rivinumerot
					$rivinumerot = array();

					while ($row = mysql_fetch_array($riresult)) {
						$rivinumerot[$row["tunnus"]] = $row["tunnus"];
					}

					sort($rivinumerot);

					$kal = 1;

					foreach($rivinumerot as $rivino) {
						$rivinumerot[$rivino] = $kal;
						$kal++;
					}

					mysql_data_seek($riresult,0);


					unset($pdf);
					unset($page);

					$sivu  = 1;
					$total = 0;

					// Aloitellaan lähetteen teko
					$page[$sivu] = alku();

					while ($row = mysql_fetch_array($riresult)) {
						rivi($page[$sivu]);
						$total+= $row["rivihinta"];
					}

					//Vikan rivin loppuviiva
					$x[0] = 20;
					$x[1] = 580;
					$y[0] = $y[1] = $kala + $rivinkorkeus - 4;
					$pdf->draw_line($x, $y, $page[$sivu], $rectparam);
					
					loppu($page[$sivu], 1);
					
					//katotaan onko laskutus nouto
					$query = "  SELECT toimitustapa.nouto, maksuehto.kateinen
								FROM lasku 
								JOIN toimitustapa ON lasku.yhtio = toimitustapa.yhtio and lasku.toimitustapa = toimitustapa.selite
								JOIN maksuehto ON lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus
								WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tunnus = '$laskurow[tunnus]'
								and toimitustapa.nouto != '' and maksuehto.kateinen = ''";
					$kures = mysql_query($query) or pupe_error($query);
					
					if (mysql_num_rows($kures) > 0) {
						kuittaus();
					}

					if ($lahetetyyppi == "tulosta_lahete_alalasku.inc") {
						alvierittely($page[$sivu]);
					}

					//tulostetaan sivu
					print_pdf($komento["Lähete"]);
				}

				$tee = '';
			}

			if ($toim == "KERAYSLISTA") {

				//keräyslistan tulostusta varten
				if ($yhtiorow["kerailylistatyyppi"] != "" and file_exists($yhtiorow["kerailylistatyyppi"])) {
					require_once ($yhtiorow["kerailylistatyyppi"]);
				}
				else {
					require_once ("tulosta_lahete_kerayslista.inc");
				}

				$otunnus = $laskurow["tunnus"];

				//tehdään uusi PDF failin olio
				$pdf= new pdffile;
				$pdf->set_default('margin', 0);

				//ovhhintaa tarvitaan jos lähetetyyppi on sellainen, että sinne tulostetaan bruttohinnat
				if ($yhtiorow["alv_kasittely"] != "") {
					$lisa2 = " round(if(tuote.myymalahinta != 0, tuote.myymalahinta, tilausrivi.hinta*(1+(tilausrivi.alv/100))),2) ovhhinta ";
				}
				else {
					$lisa2 = " round(if(tuote.myymalahinta != 0, tuote.myymalahinta, tilausrivi.hinta),2) ovhhinta ";
				}

				//generoidaan lähetteelle ja keräyslistalle rivinumerot
				$query = "  SELECT tilausrivi.*,
							round((tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * tilausrivi.hinta * (1-(tilausrivi.ale/100)),2) rivihinta,
							tuote.sarjanumeroseuranta,
							if(perheid = 0,
								(select concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0'), tuoteno, tunnus)  from tilausrivi as t2 where t2.yhtio = tilausrivi.yhtio and t2.tunnus = tilausrivi.tunnus),
								(select concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0'), tuoteno, perheid) from tilausrivi as t3 where t3.yhtio = tilausrivi.yhtio and t3.tunnus = tilausrivi.perheid)
							) as sorttauskentta,
							$lisa2
							FROM tilausrivi, tuote
							WHERE tilausrivi.otunnus = '$otunnus'
							and tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.yhtio = tuote.yhtio
							and tilausrivi.tuoteno = tuote.tuoteno
							and tuote.ei_saldoa = ''
							ORDER BY sorttauskentta";
				$result = mysql_query($query) or pupe_error($query);

				$tilausnumeroita = $otunnus;
				//generoidaan rivinumerot
				$rivinumerot = array();

				$kal = 1;

				while ($row = mysql_fetch_array($result)) {
					$rivinumerot[$row["tunnus"]] = $kal;
					$kal++;
				}

				mysql_data_seek($result,0);

				//pdf:n header..
				$firstpage = alku();
				$paino = 0;

				while ($row = mysql_fetch_array($result)) {
					//piirrä rivi
					$firstpage = rivi($firstpage);

					if ($row["netto"] != 'N' and $row["laskutettu"] == "") {
						$total += $row["rivihinta"]; // lasketaan tilauksen loppusummaa MUUT RIVIT.. (ja laskuttamattomat)
					}
					else {
						$total_netto += $row["rivihinta"]; // lasketaan tilauksen loppusummaa NETTORIVIT..
					}
				}

				//Vikan rivin loppuviiva
				$x[0] = 20;
				$x[1] = 580;
				$y[0] = $y[1] = $kala + $rivinkorkeus - 4;
				$pdf->draw_line($x, $y, $firstpage, $rectparam);

				loppu($firstpage, 1);

				//tulostetaan sivu
				print_pdf($komento["Keräyslista"]);
				$tee = '';
			}

			if($toim == "OSOITELAPPU") {
				$tunnus = $laskurow["tunnus"];
				$oslapp = $komento["Osoitelappu"];

				$query = "  SELECT GROUP_CONCAT(DISTINCT if(tunnusnippu>0, concat(tunnusnippu,'/',tunnus),tunnus) ORDER BY tunnus SEPARATOR ', ') tunnukset
							FROM lasku
							WHERE yhtio='$kukarow[yhtio]' and tila='L' and kerayslista='$laskurow[kerayslista]' and kerayslista != 0";
				$toimresult = mysql_query($query) or pupe_error($query);
				$toimrow = mysql_fetch_array($toimresult);

				$tilausnumeroita = $toimrow["tunnukset"];

				if ($oslapp != '' or $tee == 'NAYTATILAUS') {
					require('osoitelappu_pdf.inc');
				}
				$tee = '';
			}

			// Siirrytään takaisin sieltä mistä tultiin
			if ($lopetus != '') {
				// Jotta urlin parametrissa voisi päässätä toisen urlin parametreineen
				$lopetus = str_replace('////','?', $lopetus);
				$lopetus = str_replace('//','&',  $lopetus);

				echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=$lopetus'>";
				exit;
			}
		}
	}

	

	require ('../inc/footer.inc');
?>
