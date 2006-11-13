<?php
	if($_POST[tee] == 'NAYTATILAUS') $nayta_pdf=1; //Generoidaan .pdf-file

	require('../inc/parametrit.inc');

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
	if ($toim == "TARJOUS") {
		$fuse = t("Tarjous");
	}
	if ($toim == "MYYNTISOPIMUS") {
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

	if($tee != 'NAYTATILAUS') echo "<font class='head'>".sprintf(t("Tulosta %s kopioita"), $fuse).":</font><hr><br>";

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

	if ($tee == "" or $tee == 'ETSILASKU'){
		if ($ytunnus != '') {

			if ($toim == 'OSTO' or $toim == 'PURKU' or $toim == 'TARIFFI' or $toim == 'TUOTETARRA') {
				require ("../inc/kevyt_toimittajahaku.inc");
			}
			elseif ($toim != 'SIIRTOLISTA') {
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

	if ($tee == "ETSILASKU") {
		echo "<form method='post' action='$PHP_SELF' autocomplete='off'>
			<input type='hidden' name='toim' value='$toim'>
			<input type='hidden' name='ytunnus' value='$ytunnus'>
			<input type='hidden' name='asiakasid' value='$asiakasid'>
			<input type='hidden' name='toimittajaid' value='$toimittajaid'>
			<input type='hidden' name='tee' value='ETSILASKU'>";

		echo "<table>";

		if ($toim == "OSOITELAPPU") {
			//osoitelapuille on vähän eri päivämäärävaatimukset kuin muilla
			if (!isset($kka))
				$kka = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
			if (!isset($vva))
				$vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
			if (!isset($ppa))
				$ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
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

		echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppa' value='$ppa' size='3'></td>
				<td><input type='text' name='kka' value='$kka' size='3'></td>
				<td><input type='text' name='vva' value='$vva' size='5'></td>
				</tr><tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppl' value='$ppl' size='3'></td>
				<td><input type='text' name='kkl' value='$kkl' size='3'></td>
				<td><input type='text' name='vvl' value='$vvl' size='5'></td>";
		echo "<td class='back'><input type='submit' value='".t("Hae")."'></td></tr></form></table><br>";

		if ($laskunro == '' and $otunnus == '') {
			if ($toim == "OSTO") {
				//ostotilaus kyseessä, ainoa paperi joka voidaan tulostaa on itse tilaus
				$where = "tila = 'O' ";

				$where .= " and lasku.liitostunnus='$toimittajaid'";

				$where .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
							and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59' ";

				$use = " use index (yhtio_tila_luontiaika) ";
			}
			if ($toim == "PURKU") {
				//ostolasku jolle on kohdistettu rivejä. Tälle oliolle voidaan tulostaa purkulista
				$where = " tila = 'K' ";

				$where .= " and lasku.liitostunnus='$toimittajaid'";

				$where .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
							and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59' ";

				$use = " use index (yhtio_tila_luontiaika) ";
			}
			if ($toim == "TARIFFI") {
				//ostolasku jolle on kohdistettu rivejä. Tälle oliolle voidaan tulostaa tariffilista
				$where = " tila in ('H','Y','M','P','Q') and kohdistettu in ('K','X') ";

				$where .= " and lasku.liitostunnus='$toimittajaid'";

				$where .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
							and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

				$use = " use index (yhtio_tila_luontiaika) ";
			}
			if ($toim == "TUOTETARRA") {
				//Ostolasku, tuotetarrat. Tälle oliolle voidaan tulostaa tuotetarroja
				$where = " tila in ('H','Y','M','P','Q') and kohdistettu in ('K','X') ";

				$where .= " and lasku.liitostunnus='$toimittajaid'";

				$where .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
							and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

				$use = " use index (yhtio_tila_luontiaika) ";
			}


			if ($toim == "SIIRTOLISTA") {
				//ostolasku jolle on kohdistettu rivejä. Tälle oliolle voidaan tulostaa tariffilista
				$where = " tila = 'G' ";

				if ($ytunnus{0} == '£') {
					$where .= " and lasku.nimi      = '$asiakasrow[nimi]'
								and lasku.nimitark  = '$asiakasrow[nimitark]'
								and lasku.osoite    = '$asiakasrow[osoite]'
								and lasku.postino   = '$asiakasrow[postino]'
								and lasku.postitp   = '$asiakasrow[postitp]' ";
				}
				else {
					$where .= " and lasku.liitostunnus  = '$asiakasid'";
				}

				$where .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
							and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

				$use = " use index (yhtio_tila_luontiaika) ";
			}
			if ($toim == "VALMISTUS") {
				//valmistuslista
				$where = " tila = 'V' ";

				if ($ytunnus{0} == '£') {
					$where .= " and lasku.nimi      = '$asiakasrow[nimi]'
								and lasku.nimitark  = '$asiakasrow[nimitark]'
								and lasku.osoite    = '$asiakasrow[osoite]'
								and lasku.postino   = '$asiakasrow[postino]'
								and lasku.postitp   = '$asiakasrow[postitp]' ";
				}
				else {
					$where .= " and lasku.liitostunnus  = '$asiakasid'";
				}

				$where .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
							and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

				$use = " use index (yhtio_tila_luontiaika) ";
			}
			if ($toim == "LASKU") {
				//myyntilasku. Tälle oliolle voidaan tulostaa laskun kopio
				$where = " tila = 'U' ";

				if ($ytunnus{0} == '£') {
					$where .= " and lasku.nimi      = '$asiakasrow[nimi]'
								and lasku.nimitark  = '$asiakasrow[nimitark]'
								and lasku.osoite    = '$asiakasrow[osoite]'
								and lasku.postino   = '$asiakasrow[postino]'
								and lasku.postitp   = '$asiakasrow[postitp]' ";
				}
				else {
					$where .= " and lasku.liitostunnus  = '$asiakasid'";
				}

				$where .= " and lasku.tapvm >='$vva-$kka-$ppa 00:00:00'
							and lasku.tapvm <='$vvl-$kkl-$ppl 23:59:59' ";

				$use = " use index (yhtio_tila_tapvm) ";
			}
			if ($toim == "SAD") {
				//myyntilasku. Tälle oliolle voidaan tulostaa laskun kopio
				$where = " tila = 'U' and vienti = 'K' ";

				if ($ytunnus{0} == '£') {
					$where .= " and lasku.nimi      = '$asiakasrow[nimi]'
								and lasku.nimitark  = '$asiakasrow[nimitark]'
								and lasku.osoite    = '$asiakasrow[osoite]'
								and lasku.postino   = '$asiakasrow[postino]'
								and lasku.postitp   = '$asiakasrow[postitp]' ";
				}
				else {
					$where .= " and lasku.liitostunnus  = '$asiakasid'";
				}

				$where .= " and lasku.tapvm >='$vva-$kka-$ppa 00:00:00'
							and lasku.tapvm <='$vvl-$kkl-$ppl 23:59:59'";

				$use = " use index (yhtio_tila_tapvm) ";
			}
			if ($toim == "LAHETE") {
				//myyntitilaus. Tulostetaan lähete.
				$where = " tila in ('L','N') ";

				if ($ytunnus{0} == '£') {
					$where .= " and lasku.nimi      = '$asiakasrow[nimi]'
								and lasku.nimitark  = '$asiakasrow[nimitark]'
								and lasku.osoite    = '$asiakasrow[osoite]'
								and lasku.postino   = '$asiakasrow[postino]'
								and lasku.postitp   = '$asiakasrow[postitp]' ";
				}
				else {
					$where .= " and lasku.liitostunnus  = '$asiakasid'";
				}

				$where .= " and lasku.lahetepvm >='$vva-$kka-$ppa 00:00:00'
							and lasku.lahetepvm <='$vvl-$kkl-$ppl 23:59:59'";

				$use = "";
			}
			if ($toim == "KERAYSLISTA") {
				//myyntitilaus. Tulostetaan lähete.
				$where = " tila in ('L','N') ";

				if ($ytunnus{0} == '£') {
					$where .= " and lasku.nimi      = '$asiakasrow[nimi]'
								and lasku.nimitark  = '$asiakasrow[nimitark]'
								and lasku.osoite    = '$asiakasrow[osoite]'
								and lasku.postino   = '$asiakasrow[postino]'
								and lasku.postitp   = '$asiakasrow[postitp]' ";
				}
				else {
					$where .= " and lasku.liitostunnus  = '$asiakasid'";
				}

				$where .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
							and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

				$use = " use index (yhtio_tila_luontiaika) ";
			}
			if ($toim == "OSOITELAPPU") {
				//myyntitilaus. Tulostetaan osoitelappuja.
				$where = " tila = 'L' ";

				if ($ytunnus{0} == '£') {
					$where .= " and lasku.nimi      = '$asiakasrow[nimi]'
								and lasku.nimitark  = '$asiakasrow[nimitark]'
								and lasku.osoite    = '$asiakasrow[osoite]'
								and lasku.postino   = '$asiakasrow[postino]'
								and lasku.postitp   = '$asiakasrow[postitp]' ";
				}
				else {
					$where .= " and lasku.liitostunnus  = '$asiakasid'";
				}

				$where .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
							and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

				$use = " use index (yhtio_tila_luontiaika) ";

			}
			if ($toim == "VIENTIERITTELY") {
				//myyntitilaus. Tulostetaan vientieruttely.
				$where = " tila = 'U' and vienti != '' ";

				if ($ytunnus{0} == '£') {
					$where .= " and lasku.nimi      = '$asiakasrow[nimi]'
								and lasku.nimitark  = '$asiakasrow[nimitark]'
								and lasku.osoite    = '$asiakasrow[osoite]'
								and lasku.postino   = '$asiakasrow[postino]'
								and lasku.postitp   = '$asiakasrow[postitp]' ";
				}
				else {
					$where .= " and lasku.liitostunnus  = '$asiakasid'";
				}

				$where .= " and lasku.tapvm >='$vva-$kka-$ppa 00:00:00'
							and lasku.tapvm <='$vvl-$kkl-$ppl 23:59:59'";

				$use = " use index (yhtio_tila_tapvm) ";

			}
			if ($toim == "PROFORMA") {
				//myyntitilaus. Tulostetaan proforma.
				$where = " tila in ('L','U') and vienti != '' ";

				if ($ytunnus{0} == '£') {
					$where .= " and lasku.nimi      = '$asiakasrow[nimi]'
								and lasku.nimitark  = '$asiakasrow[nimitark]'
								and lasku.osoite    = '$asiakasrow[osoite]'
								and lasku.postino   = '$asiakasrow[postino]'
								and lasku.postitp   = '$asiakasrow[postitp]' ";
				}
				else {
					$where .= " and lasku.liitostunnus  = '$asiakasid'";
				}

				$where .= " and lasku.lahetepvm >='$vva-$kka-$ppa 00:00:00'
							and lasku.lahetepvm <='$vvl-$kkl-$ppl 23:59:59'";

				$use = "";
			}
			if ($toim == "TARJOUS" or $toim == "MYYNTISOPIMUS" or $toim == "OSAMAKSUSOPIMUS" or $toim == "LUOVUTUSTODISTUS" or $toim == "VAKUUTUSHAKEMUS" or $toim == "REKISTERIILMOITUS") {
				// Tulostellaan venemyyntiin liittyviä osia
				$where = " tila = 'T' and tilaustyyppi='T' ";

				if ($ytunnus{0} == '£') {
					$where .= " and lasku.nimi      = '$asiakasrow[nimi]'
								and lasku.nimitark  = '$asiakasrow[nimitark]'
								and lasku.osoite    = '$asiakasrow[osoite]'
								and lasku.postino   = '$asiakasrow[postino]'
								and lasku.postitp   = '$asiakasrow[postitp]' ";
				}
				else {
					$where .= " and lasku.liitostunnus  = '$asiakasid'";
				}

				$where .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
							and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

				$use = "";
			}
			if ($toim == "TYOMAARAYS") {
				//myyntitilaus. Tulostetaan proforma.
				$where = " tila in ('L','A','N') and tilaustyyppi='A'";

				if ($ytunnus{0} == '£') {
					$where .= " and lasku.nimi      = '$asiakasrow[nimi]'
								and lasku.nimitark  = '$asiakasrow[nimitark]'
								and lasku.osoite    = '$asiakasrow[osoite]'
								and lasku.postino   = '$asiakasrow[postino]'
								and lasku.postitp   = '$asiakasrow[postitp]' ";
				}
				else {
					$where .= " and lasku.liitostunnus  = '$asiakasid'";
				}

				$where .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
							and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

				$use = " use index (yhtio_tila_luontiaika) ";
			}
		}

		if ($laskunro > 0) {
			$where = "laskunro = '$laskunro'";
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
				$where = "laskunro = '$larow[laskunro]'";
				$use = " use index (lasno_index) ";
			}
			else {
				$where = "tunnus = '$otunnus'";
				$use = " ";
			}
		}


		if ($jarj != ''){
			$jarj = "ORDER BY $jarj";
		}
		else {
			$jarj = "ORDER BY toimaika, lasku.tunnus desc";
		}

		// Etsitään muutettavaa tilausta
		$query = "  SELECT lasku.tunnus 'tilaus', laskunro, concat_ws(' ', nimi, nimitark) asiakas, ytunnus, tapvm, laatija, summa, tila, alatila
					FROM lasku $use
					WHERE $where and lasku.yhtio = '$kukarow[yhtio]' and tila != 'D'
					$jarj";


		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {

			echo "<td class='back'>
						<form method='post' action='$PHP_SELF'>
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
				echo "<th align='left'><a href='$PHP_SELF?tee=$tee&ppl=$ppl&vvl=$vvl&kkl=$kkl&ppa=$ppa&vva=$vva&kka=$kka&toim=$toim&ytunnus=$ytunnus&asiakasid=$asiakasid&toimittajaid=$toimittajaid&jarj=$jarj'>".t(mysql_field_name($result,$i))."</a></th>";
			}
			echo "<th>".t("Tyyppi")."</th>";

			if ($tila == 'monta') {
				echo "<th>".t("Tulosta")."</th>";

				echo "  <form method='post' action='$PHP_SELF' autocomplete='off'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='tee' value='TULOSTA'>";
			}

			echo "</tr>";

			while ($row = mysql_fetch_array($result)) {
				echo "<tr>";
				$ero="td";

				if ($tunnus==$row['tilaus']) $ero="th";

				echo "<tr>";

				for ($i=0; $i<mysql_num_fields($result)-2; $i++)
				{
					echo "<$ero>$row[$i]</$ero>";
				}

				$laskutyyppi = $row["tila"];
				$alatila     = $row["alatila"];

				//tehdään selväkielinen tila/alatila
				require "../inc/laskutyyppi.inc";

				echo "<$ero>".t("$laskutyyppi")." ".t("$alatila")."</$ero>";

				if ($tila != 'monta') {

					echo "<td class='back'>
							<form method='post' action='$PHP_SELF'>
							<input type='hidden' name='tee' value='NAYTAHTML'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='tunnus' value='$row[tilaus]'>
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
							<input type='hidden' name='otunnus' value='$row[tilaus]'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='tee' value='NAYTATILAUS'>
							<input type='submit' value='".t("Näytä pdf")."'></form></td>";

					echo "<td class='back'>
							<form method='post' action='$PHP_SELF' autocomplete='off'>
							<input type='hidden' name='otunnus' value='$row[tilaus]'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='tee' value='TULOSTA'>
							<input type='submit' value='".t("Tulosta")."'></form></td>";
				}

				if ($tila == 'monta') {
					echo "<td><input type='checkbox' name='tulostukseen[]' value='$row[tilaus]'></td>";
				}

				echo "</tr>";
			}

			if ($tila == 'monta') {
				echo "<tr><td colspan='8' class='back'></td><td class='back'><input type='submit' value='".t("Tulosta")."'></form></td></tr>";
			}

			echo "</table>";
		}
		else {
			echo "".t("Ei tilauksia")."...<br><br>";
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
			if ($kappaleet > 0 and $komento["SAD-lomake"] != 'email') {
				$komento["SAD-lomake"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "LAHETE") {
			$tulostimet[0] = 'Lähete';
			if ($kappaleet > 0 and $komento["Lähete"] != 'email') {
				$komento["Lähete"] .= " -# $kappaleet ";
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
		if ($toim == "TARJOUS" and $komento["Tarjous"] != 'email' and substr($komento["Tarjous"],0,12) != 'asiakasemail') {
			$tulostimet[0] = 'Tarjous';
			if ($kappaleet > 0) {
				$komento["Tarjous"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "MYYNTISOPIMUS" and $komento["Myyntisopimus"] != 'email') {
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

				require_once('../pdflib/phppdflib.class.php');
				
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
				$tee = '';
			}

			if ($toim == "VIENTIERITTELY") {
				$uusiotunnus = $laskurow["tunnus"];

				require_once('../pdflib/phppdflib.class.php');
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

				if ($toim == 'PROFORMA') {
					//kerrotaan laskuntulostukselle, että on proforma
					$kutsuja = 'proforma';
				}

				// haetaan maksuehdon tiedot
				$query  = "select * from maksuehto where tunnus='$laskurow[maksuehto]'";
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

				if ($yhtiorow['laskutyyppi'] == 0) {
					require_once("tulosta_lasku.inc");
					$laskujarj = 'otunnus, hyllyalue, hyllynro, hyllyvali, hyllytaso, tuoteno, tunnus';
				}
				else {
					require_once("tulosta_lasku_plain.inc");
					$laskujarj = 'otunnus, tilaajanrivinro, tunnus';
				}

				if ($laskurow["tila"] == 'U') {
					$where = " uusiotunnus='$otunnus' ";
				}
				else {
					$where = " otunnus='$otunnus' ";
				}

				// haetaan tilauksen kaikki rivit
				$query = "  SELECT *
							FROM tilausrivi
							WHERE $where and yhtio='$kukarow[yhtio]'
							ORDER BY $laskujarj";
				$result = mysql_query($query) or pupe_error($query);

				//kuollaan jos yhtään riviä ei löydy
				if (mysql_num_rows($result) == 0) {
					echo t("Laskurivejä ei löytynyt");
					exit;
				}
				
				$sivu = 1;
				
				// aloitellaan laskun teko
				$firstpage = alku();

				while ($row = mysql_fetch_array($result)) {
					rivi($firstpage);
				}

				loppu($firstpage);
				alvierittely ($firstpage);

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
					$kutsu = "Lasku";

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
				unset($firstpage);

				if ($tee != 'NAYTATILAUS') {
					echo t("Lasku tulostuu")."...<br>";
					$tee = '';
				}
			}

			if ($toim == "TARJOUS") {
				$otunnus = $laskurow["tunnus"];

				require_once ("tulosta_tarjous.inc");

				tulosta_tarjous($otunnus, $komento["Tarjous"], $kieli, $tee);

				$tee = '';
			}


			if ($toim == "MYYNTISOPIMUS") {

				$otunnus = $laskurow["tunnus"];

				require_once ("tulosta_myyntisopimus.inc");

				tulosta_myyntisopimus($otunnus, $komento["Myyntisopimus"], $kieli, $tee);

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
				$query = "  SELECT *
							FROM lasku
							LEFT JOIN tyomaarays ON tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus
							WHERE lasku.yhtio='$kukarow[yhtio]'
							and lasku.tunnus='$laskurow[tunnus]'";
				$result = mysql_query($query) or pupe_error($query);
				$laskurow = mysql_fetch_array($result);

				// haetaan maksuehdon tiedot
				$query  = "select * from maksuehto where tunnus='$laskurow[maksuehto]'";
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

				require_once('../tyomaarays/tulosta_tyomaarays.inc');

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
							if (tuotetyyppi='K','TT','VV') tuotetyyppi
							FROM tilausrivi
							LEFT JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tilausrivi.tuoteno=tuote.tuoteno
							WHERE $where
							and tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.tyyppi= 'L'
							ORDER BY tuotetyyppi, tunnus";
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

				require_once ("tulosta_siirtolista.inc");

				//tehdään uusi PDF failin olio
				$pdf= new pdffile;
				$pdf->set_default('margin', 0);

				//generoidaan lähetteelle ja keräyslistalle rivinumerot
				$query = "  SELECT *
							FROM tilausrivi
							WHERE otunnus = '$laskurow[tunnus]' and yhtio='$kukarow[yhtio]'
							ORDER BY hyllyalue, hyllynro, hyllyvali, hyllytaso, tuoteno";
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

				echo "".t("Siirtolista tulostuu")."...<br>";
				$tee = '';
			}

			if ($toim == "VALMISTUS") {

				require_once ("tulosta_valmistus.inc");

				//tehdään uusi PDF failin olio
				$pdf_valm= new pdffile;
				$pdf_valm->set_default('margin', 0);

				//generoidaan lähetteelle ja keräyslistalle rivinumerot
				$query = "  SELECT *
							FROM tilausrivi
							WHERE otunnus = '$laskurow[tunnus]' and yhtio='$kukarow[yhtio]'
							ORDER BY perheid desc, tunnus";
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

			if ($toim == "LAHETE") {
				//hatetaan asiakkaan lähetetyyppi
				$query = "  SELECT lahetetyyppi, luokka, puhelin
							FROM asiakas
							WHERE tunnus='$laskurow[liitostunnus]' and yhtio='$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);
				$asrow = mysql_fetch_array($result);

				if ($asrow["lahetetyyppi"] != '' and file_exists($asrow["lahetetyyppi"])) {
					require_once ($asrow["lahetetyyppi"]);
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
						require_once ($vrow["selite"]);
					}
					else {
						echo "<font class='error'>".t("Emme löytäneet yhtään lähetetyyppiä. Lähetettä ei voida tulostaa.")."</font><br>";
					}
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
							if(perheid = 0,
							(select concat(rpad(upper(hyllyalue), 5, '0'),lpad(hyllynro, 5, '0'),lpad(hyllyvali, 5, '0'),lpad(hyllytaso, 5, '0'), tuoteno, tunnus)  from tilausrivi as t2 where t2.yhtio = tilausrivi.yhtio and t2.tunnus = tilausrivi.tunnus),
							(select concat(rpad(upper(hyllyalue), 5, '0'),lpad(hyllynro, 5, '0'),lpad(hyllyvali, 5, '0'),lpad(hyllytaso, 5, '0'), tuoteno, perheid) from tilausrivi as t3 where t3.yhtio = tilausrivi.yhtio and t3.tunnus = tilausrivi.perheid)
							) as sorttauskentta,
							$lisa2
							FROM tilausrivi, tuote
							WHERE tilausrivi.otunnus = '$otunnus'
							and tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.yhtio = tuote.yhtio
							and tilausrivi.tuoteno = tuote.tuoteno
							and tilausrivi.tyyppi = 'L'
							ORDER BY sorttauskentta";
				$riresult = mysql_query($query) or pupe_error($query);

				//generoidaan rivinumerot
				$rivinumerot = array();

				$kal = 1;

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

				//pdf:n header..
				$firstpage = alku();


				while ($row = mysql_fetch_array($riresult)) {
					//piirrä rivi
					$firstpage = rivi($firstpage);

					if ($row["netto"] != 'N' and $row["laskutettu"] == "") {
						$total += $row["rivihinta"]; // lasketaan tilauksen loppusummaa MUUT RIVIT.. (pitää olla laskuttamaton, muuten erikoisale on jo jyvitetty)
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
				print_pdf($komento["Lähete"]);
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
							if(perheid = 0,
							(select concat(rpad(upper(hyllyalue), 5, '0'),lpad(hyllynro, 5, '0'),lpad(hyllyvali, 5, '0'),lpad(hyllytaso, 5, '0'), tuoteno, tunnus)  from tilausrivi as t2 where t2.yhtio = tilausrivi.yhtio and t2.tunnus = tilausrivi.tunnus),
							(select concat(rpad(upper(hyllyalue), 5, '0'),lpad(hyllynro, 5, '0'),lpad(hyllyvali, 5, '0'),lpad(hyllytaso, 5, '0'), tuoteno, perheid) from tilausrivi as t3 where t3.yhtio = tilausrivi.yhtio and t3.tunnus = tilausrivi.perheid)
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

				$query = "  SELECT GROUP_CONCAT(DISTINCT tunnus ORDER BY tunnus SEPARATOR ', ') tunnukset
							FROM lasku
							WHERE yhtio='$kukarow[yhtio]' and tila='L' and kerayslista='$laskurow[kerayslista]' and kerayslista != 0";
				$toimresult = mysql_query($query) or pupe_error($query);
				$toimrow = mysql_fetch_array($toimresult);

				$tilausnumeroita = $toimrow["tunnukset"];

				if ($oslapp != '') {
					require('osoitelappu_pdf.inc');
				}
				$tee = '';
			}
		}
	}


	if ($tee == '') {
		//syötetään tilausnumero
		echo "<br><table>";
		echo "<form action = '$PHP_SELF' method = 'post'>
			<input type='hidden' name='toim' value='$toim'>";


		if (trim($toim) == "SIIRTOLISTA") {
			echo "<tr><th>".t("Varaston tunnus")."</th><td class='back'></td><td><input type='text' size='10' name='ytunnus'></td></tr>";
			echo "<tr><th>".t("Tilausnumero")."</th><td class='back'></td><td><input type='text' size='10' name='otunnus'></td></tr>";
		}
		else {
			if ($toim == "OSTO" or $toim == "PURKU" or $toim == "TARIFFI" or $toim == "TUOTETARRA") {
				echo "<tr><th>".t("Toimittajan nimi")."</th><td class='back'></td><td><input type='text' size='10' name='ytunnus'></td></tr>";
			}
			else {
				echo "<tr><th>".t("Asiakkaan nimi")."</th><td class='back'></td><td><input type='text' size='10' name='ytunnus'></td></tr>";
			}
			echo "<tr><th>".t("Tilausnumero")."</th><td class='back'></td><td><input type='text' size='10' name='otunnus'></td></tr>";
			echo "<tr>
					<th>".t("Laskunumero")."</th>
					<td class='back'></td><td><input type='text' size='10' name='laskunro'></td>
					<td><input type='text' size='10' name='laskunroloppu'></td></tr>";
		}

		echo "</table>";

		echo "<br><input type='submit' value='".t("Jatka")."'>";
		echo "</form>";
	}

	require ('../inc/footer.inc');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta name="generator" content="HTML Tidy for Mac OS X (vers 1st December 2004), see www.w3.org" />

	<title></title>
</head>

<body>
</body>
</html>
