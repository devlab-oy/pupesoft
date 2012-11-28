<?php

require('inc/parametrit.inc');

if (!isset($tee)) $tee = '';
if (!isset($kk)) $kk = '';
if (!isset($vv)) $vv = '';

if ($tee == "lataa_tiedosto") {
	echo file_get_contents("$pupe_root_polku/dataout/".$filenimi);
	exit;
}

echo "<font class='head'>".t("Viranomaisilmoitukset")."</font><hr><br><br>";

if ($tee == "VSRALVKK_VANHA" or $tee == 'VSRALVKK_VANHA_erittele') {
	include ('raportit/alv_laskelma.php');
	alvlaskelma($kk,$vv);
}
elseif ($tee == "VSRALVKK_UUSI" or $tee == 'VSRALVKK_UUSI_erittele') {
	include ('raportit/alv_laskelma_uusi.php');
}

if ($tee == "VSRALVYV") {

	if (!isset($kohdekausi)) $kohdekausi = '';
	if (!isset($kohdekuukausi)) $kohdekuukausi = '';
	if (!isset($ytunnus)) $ytunnus = '';
	if (!isset($kausi)) $kausi = '';

	echo "<table>";
	echo "<tr><th>".t("Arvonlisäveron yhteenvetoilmoitus kaudelta").":</th>";

	//	Haetaan alkupiste
	$query = "	SELECT ((year(now())-year(min(tilikausi_alku)))*4), quarter(now())
				from tilikaudet
				where tilikausi_alku != '0000-00-00'
				and yhtio='$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);
	$row = mysql_fetch_array($result);

	$kausia = $row[0] + $row[1] + 1;
	$kvarttaali = $row[1];
	$vuosi = date("Y");

	//	Ei näytetä ihan kaikeka
	if ($kausia > 10) $kausia = 10;

	echo "<td>";
	echo "<form enctype='multipart/form-data' method='post'>
			<input type='hidden' name='tee' value='$tee'>
			<input type='hidden' name='tyyppi' value='kausi'>
			<select name='kohdekausi' onchange='submit();'>
				<option value = ''>".t('Valitse kohdekausi')."</option>";

	for ($i=1; $i<$kausia; $i++) {

		if ($kohdekausi == $kvarttaali."/".$vuosi) {
			$sel = "SELECTED";
		}
		else {
			$sel = "";
		}

		if ($kohdekuukausi != '') $sel = '';

		echo "<option value='$kvarttaali/$vuosi' $sel>$kvarttaali/$vuosi</option>";

		if ($kvarttaali == 1) {
			$kvarttaali = 4;
			$vuosi--;
		}
		else {
			$kvarttaali--;
		}
	}
	echo "</select></form></td></tr>";

	echo "<tr><th>".t("Arvonlisäveron yhteenvetoilmoitus kuukaudelta").":</th>";

	//	Haetaan alkupiste
	$kausia = 24;
	$kuukausi = date("m");
	$vuosi = date("Y");

	echo "<td>";
	echo "	<form enctype='multipart/form-data' method='post'>
				<input type='hidden' name='tee' value='$tee'>
				<input type='hidden' name='tyyppi' value='kuukausi'>
				<select name='kohdekuukausi' onchange='submit();'>
				<option value = ''>".t('Valitse kohdekuukausi')."</option>";

	for ($i=1; $i<$kausia; $i++) {

		$kuukausi = str_pad((int) $kuukausi, 2, 0, STR_PAD_LEFT);

		if ($kohdekuukausi == $kuukausi."/".$vuosi) {
			$sel = "SELECTED";
		}
		else {
			$sel = "";
		}

		if ($kohdekausi != '') {
			$sel = '';
		}

		echo "<option value='$kuukausi/$vuosi' $sel>$kuukausi/$vuosi</option>";

		if ($kuukausi == 01) {
			$kuukausi = 12;
			$vuosi--;
		}
		else {
			$kuukausi--;
		}

	}
	echo "</select></form></td></tr></table>";

	if (strtoupper($yhtiorow["maa"])== 'FI') {
		//muutetaan ytunnus takas oikean näköseks
		$ytunpit = 8-strlen($yhtiorow["ytunnus"]);

		if ($ytunpit > 0) {
			$uytunnus = $yhtiorow["ytunnus"];
			while ($ytunpit > 0) {
			    $uytunnus = "0".$uytunnus; $ytunpit--;
			}
		}
		else {
			$uytunnus = $yhtiorow["ytunnus"];
		}

		$uytunnus = substr($uytunnus,0,7)."-".substr($uytunnus,7,1);
	}
	else {
		$uytunnus = $yhtiorow["ytunnus"];
	}

	if ($kohdekausi != "" or $kohdekuukausi != "") {

		if ($kohdekausi != '' and $tyyppi == 'kausi') {
			list($kvarttaali, $vuosi) = explode("/", $kohdekausi);
		}
		else {
			list($kuukausi, $vuosi) = explode("/", $kohdekuukausi);
		}

		if ($kvarttaali != '' and $tyyppi == 'kausi') {
			switch ($kvarttaali) {
				case 1:
					$alkupvm = "$vuosi-01-01";
					$loppupvm = "$vuosi-03-31";
					break;
				case 2:
					$alkupvm = "$vuosi-04-01";
					$loppupvm = "$vuosi-06-30";
					break;
				case 3:
					$alkupvm = "$vuosi-07-01";
					$loppupvm = "$vuosi-09-30";
					break;
				case 4:
					$alkupvm = "$vuosi-10-01";
					$loppupvm = "$vuosi-12-31";
					break;
				default:
					die("Kohdekausi on väärä!!!");
			}
		}
		elseif ($kuukausi != '') {
			$alkupvm = "$vuosi-$kuukausi-01";
			$loppupvm = date("Y-m-d", mktime(0, 0, 0, $kuukausi+1, 0, $vuosi));
		}

		echo "<br><hr>";

		if ($ytunnus != "") {
			//	Onko syötetty maa oikea
			$query = "SELECT distinct(koodi) from maat where koodi = '$maa'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 1) {
				$query = "	UPDATE asiakas SET maa = '$maa'
							WHERE yhtio = '$kukarow[yhtio]'and ytunnus='$ytunnus'";
				$result = mysql_query($query) or pupe_error($query);
				echo "<font class='message'>".t("Korjattiin asiakkaan")." '$ytunnus' ".t("maaksi")." '$maa'</font><br>";
			}
			else {
				echo "<font class='error'>".t("Syötetty maa on väärin")."</font><br>";
			}
		}

		$query = "SELECT group_concat(distinct(koodi) SEPARATOR '\',\'') from maat where eu != '' and koodi != 'FI'";
		$result = mysql_query($query) or pupe_error($query);
		$row = mysql_fetch_array($result);

		$eumaat = $row[0];

		$query = "	SELECT
					if(tuote.tuotetyyppi != 'K', 'JOO', 'EI') tav_pal,
					lasku.ytunnus,
					if(lasku.maa='', asiakas.maa, lasku.maa) as maa,
					if(lasku.maa='','X','') asiakkaan_maa,
					max(asiakas.nimi) nimi,
					round(sum(rivihinta),2) summa,
					round(sum(rivihinta)*100,0) arvo,
					count(distinct(lasku.tunnus)) laskuja,
					group_concat(DISTINCT lasku.tunnus) ltunnus
					FROM lasku USE INDEX (yhtio_tila_tapvm)
					JOIN tilausrivi USE INDEX (uusiotunnus_index) ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus)
					JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.tuoteno != '$yhtiorow[ennakkomaksu_tuotenumero]')
					LEFT JOIN asiakas ON (asiakas.yhtio = lasku.yhtio and lasku.liitostunnus = asiakas.tunnus)
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila = 'U'
					and lasku.tapvm >= '$alkupvm'
					and lasku.tapvm <= '$loppupvm'
					and lasku.vienti = 'E'
					GROUP BY 1,2,3,4
					ORDER BY tav_pal DESC, tuote.tuotetyyppi, lasku.ytunnus, asiakas.nimi ";
		$result = mysql_query($query) or pupe_error($query);

		$ok = 0;

		if (mysql_num_rows($result) > 0) {

			$arvo 		= 0;
			$summa_tav	= 0;
			$summa_pal	= 0;

			$osatiedot 	= "";
			$i			= 0;
			$edtav_pal	= "XXX";

			echo "<table>";
			echo "<tr><td class='back' colspan='5'><br>".t("Normaalit, Valmisteet ja Raaka-Aineet").":</td></tr>";

			$kassaalearray_tav = array();
			$kassaalearray_pal = array();

			// Tavaramyynnin käteisalennus
			list($kakerroinlisa, $ttres) = alvilmo_kassa_ale_erittely($alkupvm, $loppupvm, "", "", "fi311", 0, TRUE);

			if (is_resource($ttres)) {
				while ($trow = mysql_fetch_assoc($ttres)) {
					if (round($kakerroinlisa*$trow['bruttosumma'], 2) != 0) {
						$kassaalearray_tav[$trow["ytunnus"]][$trow["maa"]][$trow["laskunimi"]] = round($kakerroinlisa*$trow['bruttosumma'], 2);
					}
				}
			}

			// Palvelumyynnin käteisalennus
			list($kakerroinlisa, $ttres) = alvilmo_kassa_ale_erittely($alkupvm, $loppupvm, "", "", "fi312", 0, TRUE);

			if (is_resource($ttres)) {
				while ($trow = mysql_fetch_assoc($ttres)) {
					if (round($kakerroinlisa*$trow['bruttosumma'], 2) != 0) {
						$kassaalearray_pal[$trow["ytunnus"]][$trow["maa"]][$trow["laskunimi"]] = round($kakerroinlisa*$trow['bruttosumma'], 2);
					}
				}
			}

			$palotsikot = FALSE;

			while ($row = mysql_fetch_array($result)) {

				if ($row["tav_pal"] != $edtav_pal or $edtav_pal == "XXX") {
					if ($edtav_pal != "XXX") {

						// Piirretään tavaramyynnin kassa-alet jotka ei osunut tän kuun asiakkaisiin
						if (count($kassaalearray_tav) > 0) {
							foreach ($kassaalearray_tav as $ytunnus => $a) {
								foreach ($a as $maa => $b) {
									foreach ($b as $nimi => $c) {

										$arvo+=round($c*100, 0);
										$summa_tav+=$c;

										echo "<tr class='aktiivi'><td>1</td><td>$maa</td><td>$ytunnus</td><td>$nimi</td><td align='right'>$c</td><td align='right'>1</td></tr>";

										$i++;
										$osatiedot .= "102:$maa\n";
										$osatiedot .= "103:".sprintf("%012.12s",str_ireplace(array($maa,"-","_"), "", $ytunnus))."\n";
										$osatiedot .= "210:".round($c*100, 0)."\n";
										$osatiedot .= "104:\n";
										$osatiedot .= "009:$i\n";
									}
								}
							}
						}

						unset($kassaalearray_tav);

						$palotsikot = TRUE;

						echo "<tr><th colspan='3'></th><td class='tumma' align='right'>".sprintf("%.2f", $summa_tav)."</th><th></th></tr>";
						echo "<tr><td class='back' colspan='5'><br><br><br>".t("Palvelutuotteet").":</td></tr>";
					}

					echo "<tr><th>".t("Maatunnus")."</th><th>".t("Ytunnus")."</th><th>".t("Asiakas")."</th><th>".t("Arvo")."</th><th>".t("Laskuja")."</th></tr>";
				}

				$edtav_pal = $row["tav_pal"];

				if ($row["tav_pal"] == "JOO") {
					// Käsitellään tavaramyynnin kassa-alet jotka osuu tän kuun asiakkaisiin
					if (isset($kassaalearray_tav[$row["ytunnus"]][$row["maa"]][$row["nimi"]])) {
						$row["summa"] = $row["summa"] + $kassaalearray_tav[$row["ytunnus"]][$row["maa"]][$row["nimi"]];
						$row["arvo"] = $row["arvo"] + round(($kassaalearray_tav[$row["ytunnus"]][$row["maa"]][$row["nimi"]]*100), 0);

						unset($kassaalearray_tav[$row["ytunnus"]][$row["maa"]][$row["nimi"]]);
					}
				}
				else {
					// Käsitellään palvelumyynnin kassa-alet jotka osuu tän kuun asiakkaisiin
					if (isset($kassaalearray_pal[$row["ytunnus"]][$row["maa"]][$row["nimi"]])) {
						$row["summa"] = $row["summa"] + $kassaalearray_pal[$row["ytunnus"]][$row["maa"]][$row["nimi"]];
						$row["arvo"] = $row["arvo"] + round(($kassaalearray_pal[$row["ytunnus"]][$row["maa"]][$row["nimi"]]*100), 0);

						unset($kassaalearray_pal[$row["ytunnus"]][$row["maa"]][$row["nimi"]]);
					}
				}

				if ($row["maa"] == "") {
					$query = "	SELECT distinct koodi, nimi
								FROM maat
								WHERE nimi != ''
								ORDER BY koodi";
					$vresult = mysql_query($query) or pupe_error($query);
					$ulos = "<select name='maa'>";

					$ulos .= "<option value=''>".t("Valitse maa")."</option>";

					while ($vrow = mysql_fetch_array($vresult)) {

						$ulos .= "<option value = '".strtoupper($vrow[0])."'>".t($vrow[1])."</option>";
					}

					$ulos .= "</select>";

					echo "<tr><form enctype='multipart/form-data' method='post'>
								<input type='hidden' name='tee' value='$tee'>
								<input type='hidden' name='ytunnus' value='$row[ytunnus]'>
								<input type='hidden' name='kohdekausi' value='$kohdekausi'>
								<td>$ulos</td><td>$row[ytunnus]</td><td>$row[nimi]</td><td>$row[summa]</td><td>$row[laskuja]</td>
								<td class='back'>
								<font class='error'>".t("VIRHE: Asiakkaan maa puuttuu")."!</font><br>
								<input type='submit' name='tallenna' value='".t("Korjaa asiakkaan maa")."'>
								</td></form></tr>";
					$ok = 1;
				}
				elseif($row["maa"] != "" and $row["asiakkaan_maa"] == "X") {
					echo "<tr class='aktiivi'><td>$row[maa]</td><td>$row[ytunnus]</td><td>$row[nimi]</td><td align='right'>$row[summa]</td><td align='right'>$row[laskuja]</td><td class='back'><font class='info'>".t("HUOM: Maa haettu asiakkaan tiedoista")."</font></td></tr>";
				}
				else {
					echo "<tr class='aktiivi'><td>$row[maa]</td><td>$row[ytunnus]</td><td>$row[nimi]</td><td align='right'>$row[summa]</td><td align='right'>$row[laskuja]</td></tr>";
				}

				if ($row["maa"] != "") {
					if ($row["tav_pal"] == "JOO") {
						$arvo+=$row["arvo"];
						$summa_tav+=$row["summa"];
						$koodi = "";
					}
					else {
						$arvo+=$row["arvo"];
						$summa_pal+=$row["summa"];
						$koodi = "4";
					}

					$i++;
					$osatiedot .= "102:$row[maa]\n";
					$osatiedot .= "103:".sprintf("%012.12s",str_ireplace(array($row["maa"],"-","_"), "", $row["ytunnus"]))."\n";
					$osatiedot .= "210:$row[arvo]\n";
					$osatiedot .= "104:$koodi\n";
					$osatiedot .= "009:$i\n";
				}
			}

			// Piirretään tavaramyynnin kassa-alet jotka ei osunut tän kuun asiakkaisiin
			if (count($kassaalearray_tav) > 0) {
				foreach ($kassaalearray_tav as $ytunnus => $a) {
					foreach ($a as $maa => $b) {
						foreach ($b as $nimi => $c) {
							echo "<tr class='aktiivi'><td>$maa</td><td>$ytunnus</td><td>$nimi</td><td align='right'>$c</td><td align='right'>1</td></tr>";

							$arvo+=round($c*100, 0);
							$summa_tav+=$c;

							$i++;
							$osatiedot .= "102:$maa\n";
							$osatiedot .= "103:".sprintf("%012.12s",str_ireplace(array($maa,"-","_"), "", $ytunnus))."\n";
							$osatiedot .= "210:".round($c*100, 0)."\n";
							$osatiedot .= "104:\n";
							$osatiedot .= "009:$i\n";
						}
					}
				}
			}

			// Piirretään palvelumyynnin kassa-alet jotka ei osunut tän kuun asiakkaisiin
			if (count($kassaalearray_pal) > 0) {
				if (!$palotsikot) {
					echo "<tr><th colspan='3'></th><td class='tumma' align='right'>".sprintf("%.2f", $summa_tav)."</th><th></th></tr>";
					echo "<tr><td class='back' colspan='5'><br><br><br>".t("Palvelutuotteet").":</td></tr>";
					echo "<tr><th>".t("Maatunnus")."</th><th>".t("Ytunnus")."</th><th>".t("Asiakas")."</th><th>".t("Arvo")."</th><th>".t("Laskuja")."</th></tr>";
				}

				foreach ($kassaalearray_pal as $ytunnus => $a) {
					foreach ($a as $maa => $b) {
						foreach ($b as $nimi => $c) {
							echo "<tr class='aktiivi'><td>$maa</td><td>$ytunnus</td><td>$nimi</td><td align='right'>$c</td><td align='right'>1</td></tr>";

							$arvo+=round($c*100, 0);
							$summa_pal+=$c;

							$i++;
							$osatiedot .= "102:$maa\n";
							$osatiedot .= "103:".sprintf("%012.12s",str_ireplace(array($maa,"-","_"), "", $ytunnus))."\n";
							$osatiedot .= "210:".round($c*100, 0)."\n";
							$osatiedot .= "104:4\n";
							$osatiedot .= "009:$i\n";
						}
					}
				}
			}

			echo "<tr><th colspan='3'></th><td class='tumma' align='right'>".sprintf("%.2f", $summa_pal)."</th><th></th></tr>";
			echo "<tr><th colspan='3'></th><td class='tumma' align='right'>".sprintf("%.2f", ($summa_tav+$summa_pal))."</th><th></th></tr>";
			echo "</table>";

			if ($ok == 0) {

				$kohdekausi = $kohdekuukausi != '' ? $kohdekuukausi : $kohdekausi;
				$kvarttaali = $kuukausi != '' ? $kuukausi : $kvarttaali;

				if (substr($kohdekausi,0,1) == 0) {
					$kohdekausi = substr($kohdekausi,1);
				}

				// Korjataan kerikan maakoodi. wtf?
				$osatiedot = str_replace("102:GR", "102:EL", $osatiedot);

				$file  = "000:$tee\n";
				$file .= "100:".date("dmY")."\n";
				$file .= "051:".date("H:i:s").":00\n";
				$file .= "105:E03\n";
				$file .= "010:$uytunnus\n";
				$file .= "053:$kohdekausi\n";
				$file .= "098:1\n";
				$file .= "101:$arvo\n";
				$file .= "001:$i\n";
				$file .= $osatiedot;
				$file .= "999:1\n";

				$filenimi = "VSRALVYV-$kvarttaali$vuosi	".date("dmy-His").".txt";
				$fh = fopen("$pupe_root_polku/dataout/".$filenimi, "w");

				if (fwrite($fh, $file) === FALSE) die("Kirjoitus epäonnistui $filenimi");
				fclose($fh);

				echo "<br><form enctype='multipart/form-data' method='post' class='multisubmit'>
						<input type='hidden' name='tee' value='lataa_tiedosto'>
						<input type='hidden' name='kausi' value='$kausi'>
						<input type='hidden' name='lataa_tiedosto' value='1'>
						<input type='hidden' name='kaunisnimi' value='".t("Arvonlisäveron_yhteenvetoilmoitus")."-$kvarttaali$vuosi.txt'>
						<input type='hidden' name='filenimi' value='$filenimi'>
						<input type='submit' name='tallenna' value='".t("Tallenna tiedosto")."'></form>";
			}
			else {
				echo "<br><font class='error'>".t("Korjaa virheet maat ennen ilmoituksen lähettämistä")."</font>";
			}
		}
		else {
			echo "<br><font class='message'>".t("Ei aineistoa valitulla kaudella")."</font>";
		}
	}
}

if ($tee == "") {
	echo "<table>";
	echo "<tr><th>",t("Vanha arvonlisäveroilmoitus"),"</th>";
	echo "<td>";
	echo "<form action='viranomaisilmoitukset.php' method='post'>
			<input type='hidden' name='tee' value='VSRALVKK_VANHA'>
			<input type='submit' value='",t("Valitse"),"'></form>";
	echo "</td></tr>";
	echo "<tr><th>",t("Uusi arvonlisäveroilmoitus"),"</th>";
	echo "<td>";
	echo "<form action='viranomaisilmoitukset.php' method='post'>
			<input type='hidden' name='tee' value='VSRALVKK_UUSI'>
			<input type='submit' value='",t("Valitse"),"'></form>";
	echo "</td></tr>";
	echo "<tr><th>",t("Arvonlisäveron yhteenvetoilmoitus"),"</th>";
	echo "<td>";
	echo "<form action='viranomaisilmoitukset.php' method='post'>
			<input type='hidden' name='tee' value='VSRALVYV'>
			<input type='submit' value='",t("Valitse"),"'></form>";
	echo "</td></tr></table>";
}

require ("inc/footer.inc");

?>