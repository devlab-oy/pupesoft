<?php

	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	require "inc/parametrit.inc";

	if (!isset($tee))		$tee = "";
	if (!isset($tyyppi))	$tyyppi = "";
	if (!isset($tiliote))	$tiliote = "";
	if (!isset($tilino))	$tilino = "";

	$query = "	SELECT tunnus
				FROM tiliotedata
				WHERE perheid > 0";
	$tiliotedataresult = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($tiliotedataresult) == 0) {

		$query = "	SELECT yhtio, tunnus, left(tieto,3) tietotyyppi
					FROM tiliotedata
					WHERE tyyppi != '3'
					ORDER BY yhtio, tunnus";
		$tiliotedataresult = mysql_query($query) or pupe_error($query);

		$perheid = 0;

		while ($tiliotedatarow = mysql_fetch_array($tiliotedataresult)) {

			if ($tiliotedatarow["tietotyyppi"] != "T11" and $tiliotedatarow["tietotyyppi"] != "T81") {
				$perheid = $tiliotedatarow["tunnus"];
			}

			$query = "	UPDATE tiliotedata SET perheid = $perheid
						WHERE tunnus = $tiliotedatarow[tunnus]";
			$kuitetaan_result = mysql_query($query) or pupe_error($query);
		}

		$query = "	SELECT yhtio, tunnus, kuitattu, kuitattuaika
					FROM tiliotedata
					WHERE kuitattu != ''
					and tunnus = perheid";
		$tiliotedataresult = mysql_query($query) or pupe_error($query);

		while ($tiliotedatarow = mysql_fetch_array($tiliotedataresult)) {

			$query = "	UPDATE tiliotedata
						SET kuitattu = '$tiliotedatarow[kuitattu]',
						kuitattuaika = '$tiliotedatarow[kuitattuaika]'
						WHERE yhtio = '$tiliotedatarow[yhtio]'
						and perheid = $tiliotedatarow[tunnus]";
			$kuitetaan_result = mysql_query($query) or pupe_error($query);
		}
	}

	if (isset($tee) and $tee == "lataa_tiedosto") {
		readfile("/tmp/".$tmpfilenimi);
		exit;
	}

	echo "<font class='head'>".t("Pankkiaineistojen selailu")."</font><hr>";

	if ($tee == 'T' and trim($kuitattava_tiliotedata_tunnus) != '') {

		$kuitataan_lisa = $kuitattu == 'on' ? " kuitattu = '$kukarow[kuka]', kuitattuaika = now() " : " kuitattu = '', kuitattuaika = '0000-00-00 00:00:00' ";

		$query = "	UPDATE tiliotedata SET
					$kuitataan_lisa
					WHERE yhtio = '$kukarow[yhtio]'
					AND perheid = '$kuitattava_tiliotedata_tunnus'";
		$kuitetaan_result = mysql_query($query) or pupe_error($query);
	}

	//Olemme tulossa takasin suorituksista
	if ($tee == 'Z' or $tiliote == 'Z') {
		$query = "	SELECT tilino
					FROM yriti
					WHERE tunnus = $mtili
					and yhtio = '$kukarow[yhtio]'
					and kaytossa = ''";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			echo "<font class='error'>".t("Tili katosi")."</font><br>";

			require ("inc/footer.inc");
			exit;
		}
		else {
			$yritirow = mysql_fetch_array ($result);
			$tee = 'T';
			$tilino = $yritirow['tilino'];
			$tyyppi = 1;
		}
	}

	if ($tee == 'X' or $tee == 'XX' or $tee == "XS" or $tee == "XXS") {

		if ($tee == 'X') {
			// Pyynt� seuraavasta tiliotteesta
			$query = "	SELECT *
						FROM tiliotedata
						WHERE alku > '$pvm'
						AND tilino = '$tilino'
						AND tyyppi = '1'
						ORDER BY tunnus
						LIMIT 1";
			$tyyppi = 1;
		}
		elseif ($tee == 'XX') {
			// Pyynt� edellisest� tiliotteesta
			$query = "	SELECT *
						FROM tiliotedata
						WHERE alku < '$pvm'
						AND tilino = '$tilino'
						AND tyyppi = '1'
						ORDER BY tunnus desc
						LIMIT 1";
			$tyyppi = 1;
		}
		elseif ($tee == 'XS') {
			// Pyynt� seuraavasta viiteaineistosta
			$query = "	SELECT *
						FROM tiliotedata
						WHERE alku > '$pvm'
						AND tilino = '$tilino'
						AND tyyppi = '3'
						ORDER BY tunnus
						LIMIT 1";
			$tyyppi = 3;
		}
		elseif ($tee == 'XXS') {
			// Pyynt� seuraavasta viiteaineistosta
			$query = "	SELECT *
						FROM tiliotedata
						WHERE alku < '$pvm'
						AND tilino = '$tilino'
						AND tyyppi = '3'
						ORDER BY tunnus desc
						LIMIT 1";
			$tyyppi = 3;
		}

		$tiliotedataresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($tiliotedataresult) == 0) {
			$tee = '';
		}
		else {
			$tee = 'T';
			$tiliotedatarow = mysql_fetch_array ($tiliotedataresult);
			$pvm = $tiliotedatarow['alku'];
		}

	}

	if ($tee == 'S') {
		// Tarkistetaan oliko pvm ok
		$val = checkdate($kk, $pp, $vv);
		if (!$val) {
			echo "<b>".t("Virheellinen pvm")."</b><br>";
		}
		else {
			$pvm = $vv . "-" . $kk . "-" . $pp;
		}
		$tee = '';
	}

	if ($tee == 'T') {
		$tee = 'S'; // Pvm on jo kunnossa
	}

	if ($tee == 'S') {

		if ($tyyppi == '3') {
			$query = "	SELECT tiliotedata.*, ifnull(kuka.nimi, tiliotedata.kuitattu) kukanimi
						FROM tiliotedata
						LEFT JOIN kuka ON (kuka.yhtio = tiliotedata.yhtio AND kuka.kuka = tiliotedata.kuitattu)
						WHERE alku = '$pvm' and tilino = '$tilino' and tyyppi ='$tyyppi'
						ORDER BY tieto";
		}
		else {

			$tjarjlista = "";

			if ($tiliotejarjestys != "") {
				$tjarjlista = "sorttauskentta,";
			}

			$query = "	SELECT tiliotedata.*, ifnull(kuka.nimi, tiliotedata.kuitattu) kukanimi, if(left(tieto,3) in ('T40','T50','T60','T70') or kuitattu!='', 2, 1) sorttauskentta
						FROM tiliotedata
						LEFT JOIN kuka ON (kuka.yhtio = tiliotedata.yhtio AND kuka.kuka = tiliotedata.kuitattu)
						WHERE alku = '$pvm' and tilino = '$tilino' and tyyppi ='$tyyppi'
						ORDER BY $tjarjlista perheid, tunnus";
		}

		$tiliotedataresult = mysql_query($query) or pupe_error($query);

		// Lopetusmuuttujaa varten, muuten ylikirjoittuu
		$lopp_pvm    = $pvm;
		$lopp_tilino = $tilino;
		$lopp_tyyppi = $tyyppi;

		$txttieto = "";
		$txtfile  = "$tilino-$pvm.txt";

		$tilioterivilaskuri = 1;
		$tilioterivimaara	= mysql_num_rows($tiliotedataresult);

		if ($tilioterivimaara == 0) {
			echo "<font class='message'>".t("Tuollaista aineistoa ei l�ytynyt")."! $query</font><br>";
			$tee = '';
		}
		else {
			while ($tiliotedatarow = mysql_fetch_array($tiliotedataresult)) {
				$tietue = $tiliotedatarow['tieto'];

				if ($tiliotedatarow['tyyppi'] == 1) {
					require "inc/tiliote.inc";
				}
				if ($tiliotedatarow['tyyppi'] == 2) {
					require "inc/LMP.inc";
				}
				if ($tiliotedatarow['tyyppi'] == 3) {
					require "inc/naytaviitteet.inc";
				}

				$txttieto .= $tiliotedatarow["tieto"];
				$tilioterivilaskuri++;
			}

			echo "</table>";

			$filename = md5(uniqid()).".txt";
			file_put_contents("/tmp/".$filename, $txttieto);

			echo "<br>";
			echo "<form method='post' class='multisubmit'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='$txtfile'>";
			echo "<input type='hidden' name='tmpfilenimi' value='$filename'>";
			echo "<input type='submit' value='".t("Tallenna tiedosto")."'></form>";
		}
	}

	if ($tee == '') {

		$query = "	SELECT *
					FROM yriti
					WHERE yhtio  = '$kukarow[yhtio]'
					and kaytossa = '' ";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Sinulla ei ole yht��n pankkitili�")."</font><hr>";
			require ("inc/footer.inc");
			exit;
		}

		$querylisa = "";
		if (!isset($kk)) $kk = date("m",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		if (!isset($vv)) $vv = date("Y",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		if (!isset($pp)) $pp = date("d",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));

		if ($tilino != "") $querylisa .= " and tiliotedata.tilino = '$tilino' ";
		if ($tyyppi != "") $querylisa .= " and tyyppi = '$tyyppi' ";

		echo "<form name = 'valikko' method='post'><table>
			  <tr>
			  <th>".t("Tapahtumapvm")."</th>
			  <td>
			  	<input type='hidden' name='tee' value='S'>
				<input type='text' name='pp' maxlength='2' size=2 value='$pp'>
				<input type='text' name='kk' maxlength='2' size=2 value='$kk'>
				<input type='text' name='vv' maxlength='4' size=4 value='$vv'></td>
			  </tr>
			  <tr>
			  <th>".t("Pankkitili")."</th>
			  <td><select name='tilino'>";

		echo "<option value=''>".t("N�yt� kaikki")."</option>";

		while ($yritirow = mysql_fetch_array ($result)) {
			$chk = "";
			if ($yritirow["tilino"] == $tilino) $chk = "selected";
			echo "<option value='$yritirow[tilino]' $chk>$yritirow[nimi] ($yritirow[tilino])";
		}

		$chk = array_fill_keys(array($tyyppi), " selected") + array_fill_keys(array('1', '2', '3'), '');

		echo "</select></td></tr>
				<tr>
				<th>Laji</th>
				<td><select name='tyyppi'>
					<option value=''>".t("N�yt� kaikki")."
					<option value='1' $chk[1]>".t("Tiliote")."
					<option value='2' $chk[2]>".t("Lmp")."
					<option value='3' $chk[3]>".t("Viitesiirrot")."
				</select>
				</td>
				<td class='back'><input type='submit' value='".t("Hae")."'></td>
				</tr>
				</table><br>
				</form>";

		$query = "	SELECT alku, loppu, concat_ws(' ', yriti.nimi, yriti.tilino) tili, if(tyyppi='1', 'tiliote', if(tyyppi='2','lmp','viitesiirrot')) laji, tyyppi, yriti.tilino
					FROM tiliotedata
					JOIN yriti ON (yriti.yhtio = tiliotedata.yhtio and yriti.tilino = tiliotedata.tilino)
	                WHERE tiliotedata.yhtio = '$kukarow[yhtio]' and
					tiliotedata.alku >= '$vv-$kk-$pp'
					$querylisa
					GROUP BY alku, tili, laji
					ORDER BY alku DESC, tiliotedata.tilino, laji";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Sopivia pankkiainestoja ei l�ytynyt")."</font><hr>";
			require ("inc/footer.inc");
			exit;
		}

		echo "<table>";
		echo "<tr>";
		for ($i = 0; $i < mysql_num_fields($result)-2; $i++) {
			echo "<th>" . t(mysql_field_name($result,$i)) ."</th>";
		}
		echo "</tr>";

		while ($row = mysql_fetch_array ($result)) {

			echo "<tr class='aktiivi'>";

			for ($i=0; $i<mysql_num_fields($result)-2 ; $i++) {
				if ($i < 2) {
					echo "<td>".tv1dateconv($row[$i])."</td>";
				}
				else {
					echo "<td>$row[$i]</td>";
				}
			}

			$edalku = $row["alku"];

			echo "	<form name = 'valikko' method='post'>
					<input type='hidden' name='tee' value='T'>
					<input type='hidden' name='lopetus' value='${palvelin2}tilioteselailu.php////tee=//pp=$pp//kk=$kk//vv=$vv//tilino=$tilino//tyyppi=$tyyppi'>
					<input type='hidden' name='pvm' value='$row[alku]'>
					<input type='hidden' name='tyyppi' value='$row[tyyppi]'>
					<input type='hidden' name='tilino' value='$row[tilino]'>
					<td class='back'><input type = 'submit' value = '".t("Valitse")."'></td>
			  		</form>
			  		</tr>";
		}
		echo "</table></form>";

		$tee = "";
		$formi = 'valikko';
		$kentta = 'pp';
	}

	require ("inc/footer.inc");

?>