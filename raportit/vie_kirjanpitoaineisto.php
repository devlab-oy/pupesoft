<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

if (isset($_POST["tee"])) {
	if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
	if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
}

require("../inc/parametrit.inc");

if($tee != "lataa_tiedosto") {
	echo "<font class='head'>".t("Vie kirjanpitoaineisto")."</font><hr>";
	flush();
}

//	Tässä voi kestää..
set_time_limit(0);

//Ja tässä laitetaan ne takas
$sqlhaku = $sqlapu;

if ($tee == "lataa_tiedosto") {
	readfile("/tmp/".$tmpfilenimi);
	unlink("/tmp/".$tmpfilenimi);
	exit;
}
elseif($tee == "vie") {

	$tkausi = (int) $tkausi;

	// Tutkitaan ensiksi, mille tilikaudelle pyydettävä lista löytyy, jos lista on sopiva
	$blvk = 0;
	$blvp = 0;

	if ($tkausi > 0) {
		$query = "	SELECT *
					FROM tilikaudet
					WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tkausi'";
		$result = pupe_query($query);
		if (mysql_num_rows($result) != 1) {
			echo "<font class='error'>".t("Sopivaa yrityksen tilikautta ei löytynyt")."</font>";
			exit;
		}
		$tilikaudetrow=mysql_fetch_array($result);
	}


	if ($tkausi != '0') {
        echo "<font class='message'>$nimi ".t("tilikaudelta")." $tilikaudetrow[tilikausi_alku] - $tilikaudetrow[tilikausi_loppu]</font><br><br>";
		$lisa  = "tiliointi.tapvm <= '$tilikaudetrow[tilikausi_loppu]' and tiliointi.tapvm >= '$tilikaudetrow[tilikausi_alku]'";
	}
	else {

		$alkupvm  = "$alkuvv-$alkukk-$alkupp";
		$loppupvm = "$loppuvv-$loppukk-$loppupp";
		$lisa  = "tiliointi.tapvm >= '$alkupvm' AND tiliointi.tapvm <= '$loppupvm'";

		echo "<font class='message'>".t("Tapahtumat ajalta")." $alkupp.$alkukk.$alkuvv - $loppupp.$loppukk.$loppuvv</font><br><br>";
	}

	if (strlen(trim($kohde)) > 0) {
		$lisa .= " and tiliointi.kohde = '$kohde'";
	}

	if (strlen(trim($proj)) > 0) {
		$lisa .= " and tiliointi.projekti = '$proj'";
	}

	if (strlen(trim($kustp)) > 0) {
		if (strlen(trim($kustp2)) > 0) {
			$lisa .= " and tiliointi.kustp in ($vrow[lista])";
		}
		else {
			$lisa .= " and tiliointi.kustp = '$kustp'";
		}
	}

	if($aineisto == "O") {

		$query = "	SELECT 	lasku.*, if(viite='', viesti, viite) laskun_viite, lasku.summa laskun_summa,
							date_format(tiliointi.tapvm, '%d.%m.%Y') maksettu_paiva, tiliointi.summa maksettu_summa, tiliointi.tilino, tiliointi.selite tiliointi_selite,
							tili.nimi tili_nimi
					FROM lasku
					JOIN yriti ON yriti.yhtio = lasku.yhtio and yriti.tunnus = lasku.maksu_tili
					JOIN tiliointi ON tiliointi.yhtio = lasku.yhtio and tiliointi.ltunnus = lasku.tunnus and korjattu = '' and $lisa
					LEFT JOIN tili ON tiliointi.yhtio = tili.yhtio and tili.tilino = tiliointi.tilino
					WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila IN ('Y')
					ORDER BY lasku.mapvm, lasku.tunnus, tiliointi.tilino";
		$result = pupe_query($query);
		if(mysql_num_rows($result)>0) {

			$dirrikka = "/tmp/$kukarow[yhtio]-Kirjanpitoaineisto_".md5(uniqid());
			$dirrikka_kuvat = $dirrikka."/kuvat";
			$excelnimi = $dirrikka."/listaus.xls";
			exec("rm -rf $dirrikka");
			mkdir($dirrikka);
			mkdir($dirrikka_kuvat);

			if(include('Spreadsheet/Excel/Writer.php')) {

				//keksitään failille joku varmasti uniikki nimi:
				$workbook = new Spreadsheet_Excel_Writer($excelnimi);
				$workbook->setVersion(8);
				$worksheet =& $workbook->addWorksheet('Laskulistaus');

				$format_bold =& $workbook->addFormat();
				$format_bold->setBold();

				$excelrivi = 0;
			}
			else {
				die("Asennappa Spreadsheet_Excel_Writer!");
			}

			$sarakkeet = array("tunnus", "ytunnus", "nimi", "laskun_summa", "laskun_viite", "maksettu_paiva", "maksettu_summa", "tilino", "tili_nimi", "tiliointi_selite");
			$tulostetut = array();

			foreach($sarakkeet as $i => $s) {
				$worksheet->write($excelrivi, $i, ucfirst($s), $format_bold);
			}
			$excelrivi++;

			while($row = mysql_fetch_array($result)) {

				foreach($sarakkeet as $i => $s) {
					if($s == "tiliointi_selite") {
						$worksheet->writeString($excelrivi, $i, strip_tags(str_replace("<br>", " ", $row[$s])));
					}
					else {
						$worksheet->writeString($excelrivi, $i, $row[$s]);
					}
				}
				$excelrivi++;

				//	Tämä lasku on uusi, tallennetaan liitteet
				if(!in_array($row["tunnus"], $tulostetut)) {

					echo "<font class='message'>".t("Käsitellään liitteet laskulle")." $row[tunnus] $row[nimi] $row[summa]@$row[mapvm]</font><br>";
					$kuvaok = 0;
					$query = "	SELECT *
								FROM liitetiedostot
								WHERE liitos = 'lasku' and liitostunnus = '$row[tunnus]'";
					$lres = pupe_query($query);

					if(mysql_num_rows($lres)>0) {
						echo "Tallennan liitteet laskulta<br>";
						$y = 0;
						while($lrow = mysql_fetch_array($lres)) {
							$y++;
							if(file_put_contents($dirrikka_kuvat."/".$row["tunnus"]."_".$y."-".$lrow["filename"], $lrow["data"]) == false) {
								echo "<font class='error'>".t("Kuvaa ei voitu tallettaa!")."</font><br>";
							}
							else {
								$kuvaok = 1;
							}
						}
					}

					if($row['ebid'] != "") {

						echo "Haen laskukuvan laskulle<br>";

						$verkkolaskutunnus = $yhtiorow['verkkotunnus_vas'];
						$salasana		   = $yhtiorow['verkkosala_vas'];

						$timestamppi = gmdate("YmdHis")."Z";

						$urlhead = "http://www.verkkolasku.net";
						$urlmain = "/view/ebs-2.0/$verkkolaskutunnus/visual?DIGEST-ALG=MD5&DIGEST-KEY-VERSION=1&EBID=$row[ebid]&TIMESTAMP=$timestamppi&VERSION=ebs-2.0";

						$digest	 = md5($urlmain . "&" . $salasana);
						$url	 = $urlhead.$urlmain."&DIGEST=$digest";

						$sisalto = file_get_contents($url);
						if($sisalto !== false) {
							$tyofile = $dirrikka_kuvat."/".$row["tunnus"]."-".$row["ebid"].".pdf";
							if(file_put_contents($tyofile, $sisalto) == false) {
								echo "<font class='error'>".t("Kuvaa ei voitu tallettaa!")."</font><br>";
							}
							else {
								$kuvaok = 1;
							}
						}
						else {
							echo "<font class='error'>".t("Laskutiedostoa ei löytynyt!")."</font><br>";
						}
					}

					if($kuvaok != 1) {
						echo "<font class='error'>".t("Laskulle ei löytynyt yhtään kuvaa!!")."</font><br>";
					}

					$tulostetut[] = $row["tunnus"];

					echo "<br>";
					flush();
				}
			}

			$workbook->close();

			$zipfile = basename($dirrikka).".zip";

			$odir = getcwd();
			chdir("/tmp/");
			exec("zip -r $zipfile ".basename($dirrikka));
			exec("cd $odir");
			exec("rm -rf $dirrikka");

			echo "<table>";
			echo "<tr><th>".t("Tallenna tiedosto").":</th>";
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='".ucfirst(strtolower($zipfile))."'>";
			echo "<input type='hidden' name='tmpfilenimi' value='".$zipfile."'>";
			echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
			echo "</table><br>";

		}
	}
}
else {

	echo "	<form name = 'valinta' action = '$PHP_SELF' method='post'>
			<input type = 'hidden' name = 'tee' value = 'vie'>
			<table>
			<tr>
			<th>".t("Aineisto")."</th>
			<td>
				<select name='aineisto'>
					<option value='O'>".t("Ostoreskontra")."</option>
				</select>
			</td>
			</tr>
			<tr>
			<th>".t("Ajalta")."</th>
			<td>
				<input type='text' name='alkupp' size = '2'> <input type='text' name='alkukk' size = '2'> <input type='text' name='alkuvv' size = '4'> -
				<input type='text' name='loppupp' size = '2'> <input type='text' name='loppukk' size = '2'> <input type='text' name='loppuvv' size = '4'>
			</tr>";

	echo "<tr><th>".t("tai koko tilikausi")."</th>";

	$query = "SELECT *
				FROM tilikaudet
				WHERE yhtio = '$kukarow[yhtio]'
				ORDER BY tilikausi_alku";
	$vresult = pupe_query($query);

	echo "<td><select name='tkausi'><option value='0'>".t("Ei valintaa")."";

	while ($vrow=mysql_fetch_array($vresult)) {
		$sel="";
		if ($trow[$i] == $vrow["tunnus"]) {
			$sel = "selected";
		}
		echo "<option value = '$vrow[tunnus]' $sel>$vrow[tilikausi_alku] - $vrow[tilikausi_loppu]";
	}

	echo "</select></td>";
	echo "</tr>";

	echo "<tr>
			<th>Vain tili</th>
			<td><input type = 'text' name = 'tili' value = ''> - <input type = 'text' name = 'tili2' value = ''></td>
			</tr>";

	echo "<tr><th>".t("Vain kustannuspaikka")."</th>";

	$query = "	SELECT tunnus, nimi
				FROM kustannuspaikka
				WHERE yhtio = '$kukarow[yhtio]'
				and kaytossa != 'E'
				and tyyppi = 'K'
				ORDER BY koodi+0, koodi, nimi";
	$vresult = pupe_query($query);

	echo "<td><select name='kustp'><option value=' '>".t("Ei valintaa")."";

	while ($vrow=mysql_fetch_array($vresult)) {
		$sel="";
		if ($trow[$i] == $vrow['tunnus'] or $kustp == $vrow["tunnus"]) {
			$sel = "selected";
		}
		echo "<option value = '$vrow[0]' $sel>$vrow[1]";
	}
	echo "</select> - <select name='kustp2'><option value=' '>".t("Ei valintaa");

	mysql_data_seek($vresult,0);

	while ($vrow=mysql_fetch_array($vresult)) {
		$sel="";
		if ($trow[$i] == $vrow['tunnus'] or $kustp2 == $vrow["tunnus"]) {
			$sel = "selected";
		}
		echo "<option value = '$vrow[tunnus]' $sel>$vrow[nimi]";
	}

	echo "</select>";

	echo "</td>";
	echo "</tr>";
	echo "<tr><th>".t("Vain kohde")."</th>";

 	$query = "	SELECT tunnus, nimi
				FROM kustannuspaikka
				WHERE yhtio = '$kukarow[yhtio]'
				and kaytossa != 'E'
				and tyyppi = 'O'
				ORDER BY koodi+0, koodi, nimi";
	$vresult = pupe_query($query);

	echo "<td><select name='kohde'><option value=' '>".t("Ei valintaa")."";

	while ($vrow=mysql_fetch_array($vresult)) {
		$sel="";
		if ($trow[$i] == $vrow['tunnus'] or $kohde == $vrow['tunnus']) {
			$sel = "selected";
		}
		echo "<option value = '$vrow[0]' $sel>$vrow[1]";
	}

	echo "</select></td>";
	echo "</tr>";
	echo "<tr><th>".t("Vain projekti")."</th>";

	$query = "	SELECT tunnus, nimi
				FROM kustannuspaikka
				WHERE yhtio = '$kukarow[yhtio]'
				and kaytossa != 'E'
				and tyyppi = 'P'
				ORDER BY koodi+0, koodi, nimi";
	$vresult = pupe_query($query);

	echo "<td><select name='proj'><option value=' '>".t("Ei valintaa")."";

	while ($vrow=mysql_fetch_array($vresult)) {
		$sel="";
		if ($trow[$i] == $vrow['tunnus'] or $proj == $vrow['tunnus']) {
			$sel = "selected";
		}
		echo "<option value = '$vrow[0]' $sel>$vrow[1]";
	}
	echo "</select></td>";
	echo "</tr>";
	echo "</table><br>
	      <input type = 'submit' value = '".t("Näytä")."'></form>";

	require ("../inc/footer.inc");
}
?>