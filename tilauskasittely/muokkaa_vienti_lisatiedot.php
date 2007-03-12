<?php
	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Lisätietojen korjaukset")."</font><hr>";

	if ($tapa == "vientituonti") {
		$tapa = "vienti";
	}

	if ($tapa == "tuontivienti") {
		$tapa = "tuonti";
	}

	if ($tapa == "vienti") {

		$query = "SELECT *
				  FROM lasku
				  WHERE tunnus ='$otunnus' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "laskua ei löydy";
			$tee = "";
		}
		else {
			$laskurow = mysql_fetch_array ($result);
		}

		if ($tee == 'L') {

			list($poistumistoimipaikka, $poistumistoimipaikka_koodi) = split("##", $poistumistoimipaikka, 2);

			if ($aktiivinen_kuljetus_kansallisuus == '') {
				$aktiivinen_kuljetus_kansallisuus = $sisamaan_kuljetus_kansallisuus;
			}

			$aktiivinen_kuljetus_kansallisuus = strtoupper($aktiivinen_kuljetus_kansallisuus);
			$sisamaan_kuljetus_kansallisuus = strtoupper($sisamaan_kuljetus_kansallisuus);
			$maa_maara = strtoupper($maa_maara);

			$query = " UPDATE lasku
						SET maa_maara = '$maa_maara',
						kauppatapahtuman_luonne = '$kauppatapahtuman_luonne',
						kuljetusmuoto = '$kuljetusmuoto',
						sisamaan_kuljetus = '$sisamaan_kuljetus',
						sisamaan_kuljetusmuoto  = '$sisamaan_kuljetusmuoto',
						sisamaan_kuljetus_kansallisuus = '$sisamaan_kuljetus_kansallisuus',
						kontti  = '$kontti',
						aktiivinen_kuljetus = '$aktiivinen_kuljetus',
						aktiivinen_kuljetus_kansallisuus = '$aktiivinen_kuljetus_kansallisuus',
						poistumistoimipaikka = '$poistumistoimipaikka',
						poistumistoimipaikka_koodi = '$poistumistoimipaikka_koodi',
						bruttopaino = '$bruttopaino',
						lisattava_era = '$lisattava_era',
						vahennettava_era = '$vahennettava_era'
						WHERE tunnus ='$otunnus' and yhtio='$kukarow[yhtio]'";

			$result = mysql_query($query) or pupe_error($query);

			$tee = '';
		}

		if ($tee == 'K') {
			//toimittaja ja lasku on valittu. Nyt jumpataan.
			echo "<table>";
			echo "<form action = '$PHP_SELF' method='post'>
					<input type='hidden' name='otunnus' value='$otunnus'>
					<input type='hidden' name='tee' value='L'>";

			$query = "SELECT sum(kollit) kollit, sum(kilot) kilot
					  FROM rahtikirjat
					  WHERE otsikkonro ='$otunnus' and yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);
			$rahtirow = mysql_fetch_array ($result);

			echo "	<tr><td>6.  ".t("Kollimäärä").":</td>
					<td colspan='2'>$rahtirow[kollit]</td></tr>";

			echo "	<tr><td>17. ".t("Määrämaan koodi").":</td>
					<td colspan='2'><input type='text' name='maa_maara' size='2' value='$laskurow[maa_maara]'></td><td class='back'>".t("Pakollinen kenttä")."</td></tr>";

			if ($laskurow["vienti"] == "K") {
				echo "	<tr><td>18. ".t("Sisämaan kuljetusväline").":</td>
						<td><input type='text' name='sisamaan_kuljetus' size='30' value='$laskurow[sisamaan_kuljetus]'></td>
						<td><input type='text' name='sisamaan_kuljetus_kansallisuus' size='2' value='$laskurow[sisamaan_kuljetus_kansallisuus]'></td>
						<td class='back'>Pakollinen kenttä</td></tr>";

				echo "	<tr><td>26. ".t("Sisämaan kuljetusmuoto").":</td>
						<td colspan='2'><select NAME='sisamaan_kuljetusmuoto'>";

				$query = "	SELECT selite, selitetark
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]' and laji='KM'
							ORDER BY jarjestys, selite";
				$result = mysql_query($query) or pupe_error($query);

				echo "<option value=''>".t("Valitse")."</option>";

				while($row = mysql_fetch_array($result)){
					$sel = '';
					if($row[0] == $laskurow["sisamaan_kuljetusmuoto"]) {
						$sel = 'selected';
					}
					echo "<option value='$row[0]' $sel>$row[1]</option>";
				}
				echo "</select></td><td class='back'>".t("Pakollinen kenttä")."</td>";
				echo "</tr>";

				$chk1 = '';
				$chk2 = '';
				if($laskurow["kontti"] == 1) {
					$chk1 = 'checked';
				}
				if($laskurow["kontti"] == 0) {
					$chk2 = 'checked';
				}

				echo "	<tr><td>19. ".t("Kulkeeko tavara kontissa").":</td><td>".t("Kyllä").": <input type='radio' name='kontti' value='1' $chk1></td>
						<td>Ei: <input type='radio' name='kontti' value='0' $chk2></td><td class='back'>".t("Pakollinen kenttä")."</td></tr>";

				echo "	<tr><td>21. ".t("Aktiivisen kuljetusvälineen tunnus ja kansalaisuus").":</td>
						<td><input type='text' name='aktiivinen_kuljetus' size='25' value='$laskurow[aktiivinen_kuljetus]'></td>
						<td><input type='text' name='aktiivinen_kuljetus_kansallisuus' size='2' value='$laskurow[aktiivinen_kuljetus_kansallisuus]'></td><td class='back'>".t("Voidaan jättää tyhjäksi jos asiakas täyttää")."</td></tr>";
			}

			echo "	<tr><td>24. ".t("Kauppatapahtuman luonne").":</td>
					<td colspan='2'><select NAME='kauppatapahtuman_luonne'>";

			$query = "	SELECT selite, selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]' and laji='KT'
						ORDER BY jarjestys, selite";
			$result = mysql_query($query) or pupe_error($query);

			echo "<option value=''>".t("Valitse")."</option>";

			while($row = mysql_fetch_array($result)){
				$sel = '';
				if($row[0] == $laskurow["kauppatapahtuman_luonne"]) {
					$sel = 'selected';
				}
				echo "<option value='$row[0]' $sel>$row[1]</option>";
			}
			echo "</select></td><td class='back'>".t("Pakollinen kenttä")."</td>";
			echo "</tr>";


			echo "	<tr><td>25 ".t("Kuljetusmuoto rajalla").":</td>
					<td colspan='2'><select NAME='kuljetusmuoto'>";

			$query = "	SELECT selite, selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]' and laji='KM'
						ORDER BY jarjestys, selite";
			$result = mysql_query($query) or pupe_error($query);

			echo "<option value=''>".t("Valitse")."</option>";

			while($row = mysql_fetch_array($result)){
				$sel = '';
				if($row[0] == $laskurow["kuljetusmuoto"]) {
					$sel = 'selected';
				}
				echo "<option value='$row[0]' $sel>$row[1]</option>";
			}
			echo "</select></td><td class='back'>".t("Pakollinen kenttä")."</td>";
			echo "</tr>";

			if ($laskurow["vienti"] == "K") {
				$query = "	SELECT selite, selitetark
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]' and laji = 'TULLI'
							ORDER BY selitetark";
				$vresult = mysql_query($query) or pupe_error($query);


				echo "<tr><td>29. ".t("Poistumistoimipaikka").":</td>";
				echo "<td colspan='2'><select name='poistumistoimipaikka'>";
				echo "<option value = '##'>".t("Ole hyvä ja valitse")."";

				while ($vrow = mysql_fetch_array($vresult)) {
					$sel = "";
					if ($laskurow["poistumistoimipaikka"] == $vrow[1] and $laskurow["poistumistoimipaikka_koodi"] == $vrow[0]) {
						$sel = "selected";
					}
					echo "<option value = '$vrow[1]##$vrow[0]' $sel>$vrow[1] $vrow[0]";
				}
				echo "</select></td><td class='back'>".t("Pakollinen kenttä")."</td></tr>";

				echo "	<tr><td>28. ".t("Vähennettävä erä, ulkomaiset kustannukset")."</td><td colspan='2'><input type='text' name='vahennettava_era' size='25' value='$laskurow[vahennettava_era]'></td></tr>";
				echo "	<tr><td>28. ".t("Toimitusehdon mukainen lisättävä erä")."</td><td colspan='2'><input type='text' name='lisattava_era' size='25' value='$laskurow[lisattava_era]'></td></tr>";
			}

			echo "	<tr><td>35. ".t("Bruttopaino").":</td>
					<td colspan='2'>$rahtirow[kilot]</td>
					<input type='hidden' name='bruttopaino' value='$rahtirow[kilot]'></tr>";

			echo "</table>";

			echo "<br><input type='submit' value='".t("Päivitä tiedot")."'>";
			echo "<input type='hidden' name='tapa' value='$tapa'>";
			echo "</form>";
		}

	}

	if ($tapa == "tuonti") {

		$query = "SELECT *
				  FROM lasku
				  WHERE tunnus ='$otunnus' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "laskua ei löydy";
			$tee = "";
		}
		else {
			$laskurow = mysql_fetch_array ($result);
		}

		if ($tee == "update") {

			$query = "	UPDATE lasku
						SET maa_lahetys = '$maa_lahetys',
						kauppatapahtuman_luonne = '$ktapahtuman_luonne',
						kuljetusmuoto = '$kuljetusmuoto'
						WHERE tunnus='$otunnus' and yhtio='$kukarow[yhtio]'";

			$result = mysql_query($query) or pupe_error($query);

			$tee = "";
		}

		if ($tee == "K") {

			// näytetään vielä laskun tiedot, ettei kohdisteta päin berberiä
			echo "<table>";
			echo "<tr>";
			echo "<th>".t("Ytunnus")."</th>";
			echo "<th>".t("Nimi")."</th>";
			echo "<th>".t("Tapvm")."</th>";
			echo "<th>".t("Summa")."</th>";
			echo "<th>".t("Toimitusehto")."</th>";
			echo "</tr>";
			echo "<tr><td>$laskurow[ytunnus]</td><td>$laskurow[nimi]</td><td>$laskurow[tapvm]</td><td>$laskurow[summa] $laskurow[valkoodi]</td><td>$laskurow[toimitusehto]</td></tr>";
			echo "</table><br>";

			echo "<table>";
			echo "<form action = '$PHP_SELF' method='post'>";
			echo "<input type='hidden' name='asiakasid' value='$asiakasid'>";
			echo "	<input type='hidden' name='toiminto' value='lisatiedot'>
					<input type='hidden' name='otunnus' value='$otunnus'>
					<input type='hidden' name='ytunnus' value='$laskurow[ytunnus]'>
					<input type='hidden' name='tee' value='update'>";
			echo "<tr><td>".t("Lähetysmaa").":</td><td colspan='3'>
					<select name='maa_lahetys'>";

			$query = "	SELECT distinct koodi, nimi
						FROM maat
						where nimi != ''
						ORDER BY koodi";
			$result = mysql_query($query) or pupe_error($query);
			echo "<option value=''>".t("Valitse")."</option>";

			while($row = mysql_fetch_array($result)){
				$sel = '';
				if($row[0] == $laskurow["maa_lahetys"]) {
					$sel = 'selected';
				}
				echo "<option value='$row[0]' $sel>$row[1]</option>";
			}
			echo "</select></td>";
			echo "</tr>";

			if ($laskurow["tuontipvm"] == '0000-00-00') {
				$pp = date('d');
				$kk = date('m');
				$vv = date('Y');
				$laskurow["tuontipvm"] = $vv."-".$kk."-".$pp;
			}

			echo "<tr><td>".t("Kauppatapahtuman luonne").":</td><td colspan='3'>
				<select NAME='ktapahtuman_luonne'>";

			$query = "	SELECT selite, selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]' and laji='KT'
						ORDER BY jarjestys, selite";
			$result = mysql_query($query) or pupe_error($query);

			while($row = mysql_fetch_array($result)){
				$sel = '';
				if($row[0] == $laskurow["kauppatapahtuman_luonne"]) {
					$sel = 'selected';
				}
				echo "<option value='$row[0]' $sel>$row[1]</option>";
			}
			echo "</select></td>";
			echo "</tr>";

			echo "<tr><td>".t("Kuljetusmuoto").":</td><td colspan='3'>
						<select NAME='kuljetusmuoto'>";

			$query = "	SELECT selite, selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]' and laji='KM'
						ORDER BY jarjestys, selite";
			$result = mysql_query($query) or pupe_error($query);

			while($row = mysql_fetch_array($result)){
				$sel = '';
				if($row[0] == $laskurow["kuljetusmuoto"]) {
					$sel = 'selected';
				}
				echo "<option value='$row[0]' $sel>$row[1]</option>";
			}
			echo "</select></td>";
			echo "</tr>";

			echo "</table>";

			echo "<input type='hidden' name='tapa' value='$tapa'";
			echo "<br><input type='submit' value='".t("Päivitä tiedot")."'>";
			echo "</form>";
		}

	}

	if ($tee == "lista") {

		$haku='';
		if (is_string($etsi))  $haku="and nimi LIKE '%$etsi%'";
		if (is_numeric($etsi)) $haku="and laskunro='$etsi'";

		if ($tapa == "tuonti") $tila = " and tila='K' and vanhatunnus=0 ";
		else $tila = " and tila='U' and alatila='X' ";

		if (trim($etsi) == "") $tee = "";

		//listataan tuoreet tilausket
		if (trim($etsi) != "") {
			$query = "	select laskunro, nimi asiakas, luontiaika laadittu, laatija, vienti, tapvm, tunnus
						from lasku where yhtio='$kukarow[yhtio]' and vienti!='' $tila
						$haku
						ORDER by tapvm
						LIMIT 50";
			$tilre = mysql_query($query) or pupe_error($query);

			echo "<table>";
			if (mysql_num_rows($tilre) > 0) {
				echo "<tr>";
				for ($i=0; $i<mysql_num_fields($tilre)-1; $i++)
					echo "<th align='left'>".t(mysql_field_name($tilre,$i))."</th>";
				echo "</tr>";

				while ($tilrow = mysql_fetch_array($tilre))
				{
					echo "<tr>";

					for ($i=0; $i<mysql_num_fields($tilre)-1; $i++)
						echo "<td>$tilrow[$i]</td>";

					echo "<form method='post' action='$PHP_SELF'><td class='back'>
							<input type='hidden' name='otunnus' value='$tilrow[tunnus]'>
							<input type='hidden' name='tee' value='K'>
							<input type='hidden' name='tapa' value='$tapa'>
							<input type='submit' value='".t("Valitse")."'></td></form>";

				}
				echo "</tr>";
			}
			else {
				echo "<tr>";
				echo "<th colspan='5'>".t("Yhtään laskua ei löytynyt")."!</th>";
				echo "</tr>";
			}
			echo "</table>";
		}
	}

	// meillä ei ole valittua tilausta
	if ($tee == '') {
		$formi="find";
		$kentta="etsi";

		// tehdään etsi valinta
		echo "<form action='$PHP_SELF' name='find' method='post'>";

		echo "<table>";
		echo "<tr>";
		echo "<th>";
		echo t("Valitse tapa");
		echo "</th>";
		echo "<td>";
		echo "<select name='tapa'>";
		echo "<option value='vienti'>".t("Vienti")."</option>";
		echo "<option value='tuonti'>".t("Tuonti")."</option>";
		echo "</select>";
		echo "</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<th>";
		echo t("Syötä nimi")." / ".t("Laskunumero")." / ".t("Keikkanumero");
		echo "</th>";
		echo "<td>";
		echo "<input type='text' name='etsi'>";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		echo "<input type='hidden' name='tee' value='lista'>";
		echo "<br><input type='Submit' value='".t("Etsi")."'>";
		echo "</form>";
	}

	require "../inc/footer.inc";
?>
