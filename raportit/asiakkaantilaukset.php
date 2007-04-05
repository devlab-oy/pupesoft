<?php
	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;

	require ("../inc/parametrit.inc");

	if ($toim == 'MYYNTI') {
		echo "<font class='head'>".t("Asiakkaan tilaukset").":</font><hr>";
		
		$til = " tila in ('L','U','N') ";
	}
	if ($toim == 'OSTO') {
		echo "<font class='head'>".t("Toimittajan tilaukset").":</font><hr>";
		
		$til = " tila ='O' ";
	}
	if ($toim == 'TARJOUS') {
		echo "<font class='head'>".t("Asiakkaan tarjoukset").":</font><hr>";
		
		$til = " tila = 'T' ";
	}
	
	if ($ytunnus == '' and $otunnus == '' and $laskunro == '' and $kukarow['kesken'] != 0 and $til != '') {
	
		$query = "SELECT ytunnus, liitostunnus FROM lasku WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$kukarow[kesken]' and $til";
		$keskenresult = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($keskenresult) == 1) {
			$keskenrow = mysql_fetch_array($keskenresult);	
		
			$ytunnus = $keskenrow['ytunnus'];
			$asiakasid = $keskenrow['liitostunnus'];
		
			if (!isset($kka))
				$kka = date("m",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
			if (!isset($vva))
				$vva = date("Y",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
			if (!isset($ppa))
				$ppa = date("d",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
			
			if (!isset($kkl))
				$kkl = date("m");
			if (!isset($vvl))
				$vvl = date("Y");
			if (!isset($ppl))
				$ppl = date("d");
		}
	}

	if ($tee == 'NAYTATILAUS') {
		echo "<font class='head'>".t("Tilaus")." $tunnus:</font><hr>";

		require ("naytatilaus.inc");

		if ($toim == "MYYNTI" or $toim == "TARJOUS") {
			$query = "	SELECT *
						FROM rahtikirjat
						WHERE otsikkonro='$tunnus'
						and yhtio = '$kukarow[yhtio]' ";
			$rahtiresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($rahtiresult)> 0) {
				echo "<b>".t("Rahtikirjatiedot").":</b><hr>";
				echo "<table><tr><th>".t("Kilot")."</th>
								<th>".t("Kollit")."</th>
								<th>".t("Kuutiot")."</th>
								<th>".t("Lavametri")."</th>
								<th>".t("Rahdinmaksaja")."</th>
								<th>".t("Pakkaus")."</th>
								<th>".t("Pakkauskuvaus")."</th>
								<th>".t("Rahtisopimus")."</th>
								<th>".t("Tulostettu")."</th>
								<th>".t("Tulostuspaikka")."</th>
								<th>".t("Tulostustapa")."</th></tr>";

				while ($rahtirow = mysql_fetch_array($rahtiresult)){
					$query = "	SELECT nimitys
								FROM varastopaikat
								WHERE tunnus='$rahtirow[tulostuspaikka]'
								and yhtio = '$kukarow[yhtio]' limit 1";
					$varnimresult = mysql_query($query) or pupe_error($query);
					$varnimrow = mysql_fetch_array($varnimresult);

					echo "<tr><td>$rahtirow[kilot]</td>
								<td>$rahtirow[kollit]</td>
								<td>$rahtirow[kuutiot]</td>
								<td>$rahtirow[lavametri]</td>";

					if ($rahtirow['merahti']== 'K') {
						echo "	<td>".t("Lähettäjä")."</td>";
					}
					else {
						echo "	<td>".t("Vastaanottaja")."</td>";
					}

					echo "		<td>$rahtirow[pakkaus]</td>
								<td>$rahtirow[pakkauskuvaus]</td>
								<td>$rahtirow[rahtisopimus]</td>
								<td>$rahtirow[tulostettu]</td>
								<td>$varnimrow[nimitys]</td>
								<td>$rahtirow[tulostustapa]</td></tr>";
				}
				echo "</table>";
			}
		}
		echo "<hr>";
		$tee = "TULOSTA";
	}
	
	if ($ytunnus != '' and ($otunnus == '' and $laskunro == '')) {
		if ($toim == 'MYYNTI' or $toim == "TARJOUS") {
			require ("../inc/asiakashaku.inc");
		}
		
		if ($toim == 'OSTO') {
			require ("../inc/kevyt_toimittajahaku.inc");
		}
	}
	elseif($otunnus > 0) {
		$query = "	(SELECT laskunro, ytunnus, liitostunnus
					FROM lasku use index (PRIMARY)
					WHERE tunnus='$otunnus'
					and yhtio = '$kukarow[yhtio]')
					UNION
					(SELECT laskunro, ytunnus, liitostunnus
					FROM lasku use index (yhtio_tunnusnippu)
					WHERE tunnusnippu = '$otunnus'
					and yhtio = '$kukarow[yhtio]')";
		$result = mysql_query($query) or pupe_error($query);
		$row = mysql_fetch_array($result);

		if ($row["laskunro"] > 0) {
			$laskunro = $row["laskunro"];
		}
		$ytunnus   = $row["ytunnus"];
		$asiakasid = $row["liitostunnus"];
	}
	elseif($laskunro > 0) {
		$query = "	SELECT laskunro, ytunnus, liitostunnus
					FROM lasku
					WHERE laskunro='$laskunro'
					and yhtio = '$kukarow[yhtio]' ";
		$result = mysql_query($query) or pupe_error($query);
		$row = mysql_fetch_array($result);

		$laskunro = $row["laskunro"];
		$ytunnus  = $row["ytunnus"];
		$asiakasid = $row["liitostunnus"];
	}

	if ($ytunnus != '') {
		echo "<form method='post' action='$PHP_SELF' autocomplete='off'>
			<input type='hidden' name='ytunnus' value='$ytunnus'>
			<input type='hidden' name='asiakasid' value='$asiakasid'>
			<input type='hidden' name='toimittajaid' value='$toimittajaid'>
			<input type='hidden' name='toim' value='$toim'>
			<input type='hidden' name='nimi' value='$nimi'>
			<input type='hidden' name='tee' value='TULOSTA'>";

		echo "<table>";

		if (!isset($kka))
			$kka = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
		if (!isset($vva))
			$vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
		if (!isset($ppa))
			$ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

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
		echo "<td class='back'><input type='submit' value='".t("Hae")."'></td></tr></form></table>";

		if ($jarj != '') {
			$jarj = "ORDER BY $jarj";
		}
		else {
			$jarj = "ORDER BY lasku.laskunro desc";
		}
		
		if ($toim == 'OSTO') {
			$asiakasid = $toimittajaid;
		}

		if ($otunnus > 0 or $laskunro > 0) {
			if ($laskunro > 0) {
				$query = "	SELECT lasku.tunnus tilaus, laskunro, concat_ws(' ', nimi, nimitark) asiakas, ytunnus, toimaika, laatija, summa, tila, alatila
							FROM lasku
							WHERE lasku.yhtio = '$kukarow[yhtio]'
							and lasku.liitostunnus = '$asiakasid'
							and $til
							and lasku.laskunro='$laskunro'";
			}
			else {
				$query = "	(SELECT lasku.tunnus tilaus, laskunro, concat_ws(' ', nimi, nimitark) asiakas, ytunnus, toimaika, laatija, summa, tila, alatila
							FROM lasku
							WHERE lasku.yhtio = '$kukarow[yhtio]'
							and lasku.liitostunnus = '$asiakasid'
							and $til
							and lasku.tunnus='$otunnus')
							UNION
							(SELECT lasku.tunnus tilaus, laskunro, concat_ws(' ', nimi, nimitark) asiakas, ytunnus, toimaika, laatija, summa, tila, alatila
							FROM lasku
							WHERE lasku.yhtio = '$kukarow[yhtio]'
							and lasku.liitostunnus = '$asiakasid'
							and $til
							and lasku.tunnusnippu='$otunnus')";
			}

			$query .=	"$jarj";
		}
		else {
			// jos on iiiiso näyttö niin näytetään myös viite
			if ($kukarow['resoluutio'] == 'I') {
				$query = "	SELECT lasku.tunnus tilaus, laskunro, concat_ws(' ', nimi, nimitark) asiakas, ytunnus, toimaika, laatija, summa, viesti, tila, alatila
							FROM lasku use index (yhtio_tila_luontiaika)
							WHERE lasku.yhtio = '$kukarow[yhtio]' ";
			}
			else {
				$query = "	SELECT lasku.tunnus tilaus, laskunro, concat_ws(' ', nimi, nimitark) asiakas, ytunnus, toimaika, laatija, summa, tila, alatila
							FROM lasku use index (yhtio_tila_luontiaika)
							WHERE lasku.yhtio = '$kukarow[yhtio]' ";
			}

			if ($ytunnus{0} == '£') {
				$query .= "	and lasku.nimi		= '$asiakasrow[nimi]'
							and lasku.nimitark	= '$asiakasrow[nimitark]'
							and lasku.osoite	= '$asiakasrow[osoite]'
							and lasku.postino	= '$asiakasrow[postino]'
							and lasku.postitp	= '$asiakasrow[postitp]' ";
			}
			else {
				$query .= "	and lasku.liitostunnus	= '$asiakasid'";
			}
			
			$query .= "	and $til
						and lasku.luontiaika >= '$vva-$kka-$ppa 00:00:00'
						and lasku.luontiaika <= '$vvl-$kkl-$ppl 23:59:59'
						$jarj";
		}

		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result)!=0) {

			echo "<br><table>";
			echo "<tr>";
			for ($i=0; $i < mysql_num_fields($result)-2; $i++) {
				echo "<th align='left'><a href='$PHP_SELF?tee=$tee&toim=$toim&ppl=$ppl&vvl=$vvl&kkl=$kkl&ppa=$ppa&vva=$vva&kka=$kka&ytunnus=$ytunnus&asiakasid=$asiakasid&toimittajaid=$toimittajaid&jarj=".mysql_field_name($result,$i)."'>".t(mysql_field_name($result,$i))."</a></th>";
			}
			echo "<th align='left'>".t("Tyyppi")."</th>";
			echo "</tr>";

			while ($row = mysql_fetch_array($result)) {

				$ero="td";
				if ($tunnus==$row['tilaus']) $ero="th";

				echo "<tr>";
				for ($i=0; $i<mysql_num_fields($result)-2; $i++)
				{
					echo "<$ero>$row[$i]</$ero>";
				}

				$laskutyyppi	= $row["tila"];
				$alatila		= $row["alatila"];

				//tehdään selväkielinen tila/alatila
				require "../inc/laskutyyppi.inc";

				echo "<$ero>".t($laskutyyppi)." ".t($alatila)."</$ero>";

				echo "<form method='post' action='$PHP_SELF'><td class='back'>
						<input type='hidden' name='tee' value='NAYTATILAUS'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='asiakasid' value='$asiakasid'>
						<input type='hidden' name='toimittajaid' value='$toimittajaid'>
						<input type='hidden' name='tunnus' value='$row[tilaus]'>
						<input type='hidden' name='ytunnus' value='$ytunnus'>
						<input type='hidden' name='nimi' value='$nimi'>
						<input type='hidden' name='ppa' value='$ppa'>
						<input type='hidden' name='kka' value='$kka'>
						<input type='hidden' name='vva' value='$vva'>
						<input type='hidden' name='ppl' value='$ppl'>
						<input type='hidden' name='kkl' value='$kkl'>
						<input type='hidden' name='vvl' value='$vvl'>
						<input type='submit' value='".t("Näytä tilaus")."'></td></form>";

				echo "</tr>";
			}
			echo "</table>";
		}
		else {
			echo t("Ei tilauksia")."...<br><br>";
		}
	}

	// Näytetään muuten vaan sopivia tilauksia
	echo "<br><table>";
	echo "<form action = '$PHP_SELF' method = 'post'>
		<input type='hidden' name='toim' value='$toim'>";

	if ($toim == "OSTO") {
		echo "<tr><th>".t("Toimittajan nimi")."</th><td class='back'></td><td><input type='text' size='10' name='ytunnus'></td></tr>";
	}
	else {
		echo "<tr><th>".t("Asiakkaan nimi")."</th><td class='back'></td><td><input type='text' size='10' name='ytunnus'></td></tr>";
	}
	echo "<tr><th>".t("Tilausnumero")."</th><td class='back'></td><td><input type='text' size='10' name='otunnus'></td></tr>";
	echo "<tr><th>".t("Laskunumero")."</th><td class='back'></td><td><input type='text' size='10' name='laskunro'></td></tr>";
	echo "</table>";

	echo "<br><input type='submit' value='".t("Etsi")."'>";
	echo "</form>";

	require ("../inc/footer.inc");
?>
