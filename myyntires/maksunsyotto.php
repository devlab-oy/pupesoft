<?php

require ("../inc/parametrit.inc");
require_once ("inc/tilinumero.inc");

if ($tee != "CHECK" or $tiliote != 'Z') {
	echo "<font class='head'>".t("Suorituksen käsinsyöttö")."</font><hr>";
}

//Tultiinko tiliotteelta ja olisiko tämä jo viety?
if ($tiliote == 'Z' and $ytunnus != '' and $asiakasid != '') {
	$query = "select tunnus from suoritus where yhtio='$kukarow[yhtio]' and asiakas_tunnus='$asiakasid' and summa='$summa' and kirjpvm = '$vva-$kka-$ppa'";
	$result = mysql_query($query) or pupe_error($query);
	if (mysql_num_rows($result) != 0)
		echo "<br><br><font class='error'>".t("Tällainen samanlainen suoritus on jo olemassa").".</font><br><br>";
}

if ($tee == "CHECK") {

	$errors = 0;

	if ($tilino == 0) {
	 	$error[] = "".t("Valitse saajan tilinumero").".";
	}

	if (strlen($summa) == 0) {
	 	$error[] = "".t("Anna suorituksen summa").".";
	}
	else {
		preg_match("/^[-+]?[0-9]+([,.][0-9]+)?/", $summa, $tsumma);
		if ($summa != $tsumma[0]) {
			$error[] = "".t("Summa on virheellinen").".";
		}
		else {
			$pistesumma = str_replace(",", ".", $summa);
		}
	}

	$kka = sprintf('%02d', $kka);
	$ppa = sprintf('%02d', $ppa);
	if ($vva < 1000) $vva += 2000;

	if (!checkdate($kka, $ppa, $vva)) {
		$error[] = "".t("Tarkista maksupäivä! Anna maksupäivä muodossa PP.KK.VVVV")."";
	}

	$errors = count($error);

	if ($errors > 0) {
		echo "<ul>";
		foreach ($error as $err) {
			echo "<li>$err</li>\n";
		}
		echo "</ul>";
		// mennään takasin selaukseen
		$tee = "";
	}
	else {
		// kaikki ok, laitetaan rivi kantaan
		$tee = "SYOTTO";
	}
}

if ($tee == "SYOTTO") {

	$myyntisaamiset = 0;

	switch ($vastatili) {
		case 'myynti' :
			$myyntisaamiset=$yhtiorow['myyntisaamiset'];
			break;
		case 'factoring' :
			$myyntisaamiset=$yhtiorow['factoringsaamiset'];
			break;
		case 'konserni' :
			$myyntisaamiset=$yhtiorow['konsernimyyntisaamiset'];
			break;
		default :
			echo "".t("Virheellinen vastatilitieto")."!";
			exit;
	}

	if ($myyntisaamiset == 0) {
		echo "".t("Myyntisaamiset-tilin selvittely epäonnistui")."";
		exit;
	}

	$query = "	SELECT yriti.*, valuu.kurssi
				FROM yriti
				JOIN valuu ON (valuu.yhtio = yriti.yhtio and yriti.valkoodi = valuu.nimi)				
				WHERE yriti.yhtio = '$kukarow[yhtio]'
				AND yriti.tunnus = '$tilino'";
	$result = mysql_query($query) or pupe_error($query);

	if ($row = mysql_fetch_array($result)) {

		$tilistr        = $row["tilino"];
		$kassatili      = $row["oletus_rahatili"];
		$tilivaluutta	= $row["valkoodi"];
		$tilikurssi		= $row["kurssi"];
		
		if ($row["valkoodi"] != $yhtiorow['valkoodi']) {
			// koitetaan hakea maksupäivän kurssi
			$query = "	SELECT * 
						FROM valuu_historia 
						WHERE kotivaluutta = '$yhtiorow[valkoodi]'
						AND valuutta = '$row[valkoodi]'
						AND kurssipvm <= '$vva-$kka-$ppa'
						LIMIT 1";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 1) {
				$row = mysql_fetch_array($result);
				$tilikurssi = $row["kurssi"];			
				echo "<font class='message'>".t("Käytettiin kurssia")." $row[kurssipvm] = $tilikurssi</font><br>";
			}
			else {
				echo "<font class='message'>".t("Ei löydetty maksupäivän kurssia, käyttään tämänhetkistä kurssia")." $tilikurssi</font><br>";
			}
		}
		
		//suorituksen summa kotivaluutassa
		$omasumma = round($pistesumma * $tilikurssi, 2);
	}
	else {
		echo "<font class='error'>".t("Valitun pankkitilin tiedot ovat puutteelliset, tarkista pankkitilin tiedot!")."</font>";
		exit;
	}

	if ($ytunnus{0} == "£") {
		$query = "SELECT nimi FROM lasku WHERE ytunnus='".substr($ytunnus, 1)."' and yhtio = '$kukarow[yhtio]'";
		$asiakasid = 0;
	}
	else {
		$query = "SELECT nimi FROM asiakas WHERE tunnus = '$asiakasid' and yhtio = '$kukarow[yhtio]'";
	}
	$result = mysql_query($query) or pupe_error($query);

	if ($row = mysql_fetch_array($result)) {
		$asiakas_nimi = $row[0];
		$asiakasstr = substr($row[0], 0, 12);
	}

	// tehdään dummy-lasku johon liitetään kirjaukset
	$tapvm = $vva."-".$kka."-".$ppa;

	$query = "INSERT into lasku set yhtio = '$kukarow[yhtio]', tapvm = '$tapvm', tila = 'X', laatija = '$kukarow[kuka]', luontiaika = now()";

	if (!($result = mysql_query($query))) {
		$result = mysql_query($unlockquery);
		die ("Kysely ei onnistu $query");
	}
	$ltunnus = mysql_insert_id($link);

	// Myyntisaamiset
	$query = "	INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, ltunnus, tilino, summa, summa_valuutassa, valkoodi, selite, lukko)
				VALUES ('$kukarow[yhtio]','$kukarow[kuka]',now(),'$tapvm','$ltunnus','$myyntisaamiset', $omasumma * -1, $pistesumma * -1, '$tilivaluutta', 'Käsin syötetty suoritus $asiakas_nimi $selite','1')";

	if (!($result = mysql_query($query))) {
		$result = mysql_query($unlockquery);
		die ("Kysely ei onnistu $query");
	}
	$ttunnus = mysql_insert_id($link);

	// Kassatili
	$query = "	INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, ltunnus, tilino, summa, summa_valuutassa, valkoodi, selite, aputunnus, lukko, kustp)
				VALUES ('$kukarow[yhtio]', '$kukarow[kuka]', now(), '$tapvm', '$ltunnus', '$kassatili', '$omasumma', '$pistesumma', '$tilivaluutta', 'Käsin syötetty suoritus $asiakas_nimi $selite', '$ttunnus', '1','$kustannuspaikka')";
	mysql_query($query) or pupe_error($query);

	// Näin kaikki tiliöinnit ovat kauniisti linkitetty toisiinsa. (Kuten alv-vienti)
	$query = "	INSERT INTO suoritus (yhtio, tilino, nimi_maksaja, summa, maksupvm, kirjpvm, asiakas_tunnus, ltunnus, viesti, valkoodi, kurssi)
				VALUES ('$kukarow[yhtio]', '$tilistr', '$asiakasstr', '$pistesumma', '$tapvm', '$tapvm', '$asiakasid', '$ttunnus', '$selite', '$tilivaluutta', '$tilikurssi')";

	if (!($result = mysql_query($query))) {
		$result = mysql_query($unlockquery);
		die ("Kysely ei onnistu $query");
	}
	$suoritus_tunnus = mysql_insert_id();

	echo "<font class='message'>".t("Suoritus tallennettu").".</font><br>";

	// tulostetaan suorituksesta kuitti
	if ($tulostakuitti != "") {

		$query = "SELECT * FROM asiakas WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$asiakasid'";
		$result = mysql_query($query) or pupe_error($query);
		$asiakasrow = mysql_fetch_array($result);

		$summa = $pistesumma;

		require ("tilauskasittely/tulosta_kuitti.inc");

		// pdffän piirto
		$firstpage = alku();
		rivi($firstpage);
		loppu($firstpage);

		$pdffilenimi = "/tmp/kuitti-".md5(uniqid(mt_rand(), true)).".pdf";

		//kirjoitetaan pdf faili levylle..
		$fh = fopen($pdffilenimi, "w");
		if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
		fclose($fh);

		// itse print komento...
		$line = exec("$tulostakuitti $pdffilenimi");
		$line = exec("$tulostakuitti $pdffilenimi");

		//poistetaan tmp file samantien kuleksimasta...
		$line = exec("rm -f $pdffilenimi");

		echo "<font class='message'>".t("Kuitti (2 kpl) tulostettu").".</font><br>";
	}

	// takasin jonnekin
	if ($jatko != t("Tallenna suoritus")) {
		echo "<br>";
		$oikeus = 1;
		$tila = "kohdistaminen";
		$PHP_SELF = 'manuaalinen_suoritusten_kohdistus.php';
		require("manuaalinen_suoritusten_kohdistus.php");
		exit;
	}

	if ($tiliote == 'Z') {
		$tee = 'Z';
		require('tilioteselailu.php');
		exit;
	}
	else {
		$ytunnus = "";
		$tee = "";
	}

}

if ($ytunnus != '' and $tee == "ETSI") {

	$laskunro = $ytunnus; // haku talteen

	require ("inc/asiakashaku.inc");
	$tee = "";

	// jos ei löytynyt ytunnuksella kokeillaan laskunumerolla
	if ($ytunnus == "" and is_numeric($laskunro)) {
		$query = "	SELECT liitostunnus
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' and
					tila = 'U' and
					alatila = 'X' and
					laskunro = '$laskunro'";
		$result  = mysql_query($query) or pupe_error($query);

		if ($asiakas = mysql_fetch_array($result)) {
			echo "<font class='message'>".t("Maksaja löytyi laskunumerolla")." $laskunro:</font><br>";
			$asiakasid = $asiakas["liitostunnus"];
			require ("inc/asiakashaku.inc");
		}
	}

	// otetaan muutparametrit takas
	list ($tiliote,$summa,$ppa,$kka,$vva,$mtili) = explode("#", $muutparametrit);
}


// meillä on ytunnus, tehdään syöttöruutu
if ($ytunnus != '' and $tee == "") {

	// Meitä on kutsuttu tiliotteelta!
	if ($tiliote == 'Z') {
		echo "<form action = '../tilioteselailu.php' method='post'>
			<input type='hidden' name='mtili' value='$mtili'>
			<input type='hidden' name='tee' value='Z'>
			<input type='hidden' name='pvm' value='$vva-$kka-$ppa'>
			<input type='Submit' value='".t("Palaa tiliotteelle")."'></form>";
	}

	//päivämäärän tarkistus
	$tilalk = split("-", $yhtiorow["tilikausi_alku"]);
	$tillop = split("-", $yhtiorow["tilikausi_loppu"]);

	$tilalkpp = $tilalk[2];
	$tilalkkk = $tilalk[1]-1;
	$tilalkvv = $tilalk[0];

	$tilloppp = $tillop[2];
	$tillopkk = $tillop[1]-1;
	$tillopvv = $tillop[0];

	echo "	<SCRIPT LANGUAGE=JAVASCRIPT>

			function verify(){
				var pp = document.formi.ppa;
				var kk = document.formi.kka;
				var vv = document.formi.vva;

				pp = Number(pp.value);
				kk = Number(kk.value)-1;
				vv = Number(vv.value);

				if (vv < 1000) {
					vv = vv+2000;
				}

				var dateSyotetty = new Date(vv,kk,pp);
				var dateTallaHet = new Date();
				var ero = (dateTallaHet.getTime() - dateSyotetty.getTime()) / 86400000;

				var tilalkpp = $tilalkpp;
				var tilalkkk = $tilalkkk;
				var tilalkvv = $tilalkvv;
				var dateTiliAlku = new Date(tilalkvv,tilalkkk,tilalkpp);
				dateTiliAlku = dateTiliAlku.getTime();


				var tilloppp = $tilloppp;
				var tillopkk = $tillopkk;
				var tillopvv = $tillopvv;
				var dateTiliLoppu = new Date(tillopvv,tillopkk,tilloppp);
				dateTiliLoppu = dateTiliLoppu.getTime();

				dateSyotetty = dateSyotetty.getTime();

				if(dateSyotetty < dateTiliAlku || dateSyotetty > dateTiliLoppu) {
					var msg = '".t("VIRHE: Syötetty päivämäärä ei sisälly kuluvaan tilikauteen")."!';

					if(alert(msg)) {
						return false;
					}
					else {
						return false;
					}
				}

				if(ero >= 30) {
					var msg = '".t("Oletko varma, että haluat päivätä laskun yli 30pv menneisyyteen")."?';
					return confirm(msg);
				}
				if(ero <= -14) {
					var msg = '".t("Oletko varma, että haluat päivätä laskun yli 14pv tulevaisuuteen")."?';
					return confirm(msg);
				}
				
				if (vv < dateTallaHet.getFullYear()) {
					if (5 < dateTallaHet.getDate()) {
						var msg = '".t("Oletko varma, että haluat päivätä laskun menneisyyteen")."?';
						return confirm(msg);
					}												
				}
				else if (vv == dateTallaHet.getFullYear()) {
					if (kk < dateTallaHet.getMonth() && 5 < dateTallaHet.getDate()) {
						var msg = '".t("Oletko varma, että haluat päivätä laskun menneisyyteen")."?';
						return confirm(msg);
					}
				}

			}
		</SCRIPT>";

	echo "<form action='$PHP_SELF' method='post' onSubmit = 'return verify()' name='formi'>";
	echo "<input type='hidden' name='tee' value='CHECK'/>\n";

	if ($ytunnus{0} == "£") {
		$query = "SELECT concat('£',tunnus) tunnus, nimi, ytunnus FROM lasku WHERE ytunnus='".substr($ytunnus, 1)."' and yhtio = '$kukarow[yhtio]'";
	}
	else {
		$query = "	SELECT asiakas.tunnus, asiakas.nimi, ytunnus, konserniyhtio, factoring FROM asiakas
					LEFT JOIN maksuehto ON maksuehto.tunnus = asiakas.maksuehto
					WHERE asiakas.tunnus = '$asiakasid' and asiakas.yhtio = '$kukarow[yhtio]'";
	}
	$result  = mysql_query($query) or pupe_error($query);
	$asiakas = mysql_fetch_array($result);

	echo "<input type='hidden' name='asiakasid' value='$asiakas[tunnus]'/>\n
			<input type='hidden' name='ytunnus' value='$asiakas[ytunnus]'/>\n
			<input type='hidden' name='mtili' value='$mtili'>\n
			<input type='hidden' name='tiliote' value='$tiliote'>\n
			<input type='hidden' name='pvm' value='$vva-$kka-$ppa'>\n";
	echo "<table>
	<tr>
		<th>".t("Maksaja")."</th>
		<td>
		$asiakas[nimi]
		</td>
	</tr>
	<tr>
		<th>".t("Saajan tilinumero")."</th>
		<td>";

	$query  = "SELECT * FROM yriti WHERE yhtio = '$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);

	$sel='';
	echo "<select name='tilino'>";
	echo "<option value='0'>".t("Valitse")."</option>\n";

	while ($row = mysql_fetch_array($result)) {
		if ($tilino == 0 and $row['oletus_rahatili'] == $yhtiorow['selvittelytili']) $sel='selected';
		if ($tilino == $row['tilino']) $sel='selected';
		echo "<option value='$row[tunnus]' $sel>$row[nimi] ".tilinumero_print($row['tilino'])." $row[valkoodi]</option>\n";
		$sel='';
	}
	echo "</select>";

	// Tehdään kustannuspaikkapopup
	$query = "	SELECT tunnus, nimi
				FROM kustannuspaikka
				WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'K' and kaytossa <> 'E'
				ORDER BY nimi";
	$kustpvr = mysql_query($query) or pupe_error($query);

	echo "<select name='kustannuspaikka'>";
	echo "<option value=''>".t("Ei kustannuspaikkaa")."</option>";

	while ($apurow=mysql_fetch_array($kustpvr)) {
		echo "<option value ='$apurow[tunnus]'>$apurow[nimi]</option>";
	}

	echo "</select></td>
	</tr>";

	$sel1='';
	$sel2='';

	if ($asiakas['factoring'] != '') $sel2='checked';
	if ($sel2=='') $sel1='checked';

	echo "<tr><th>".t("Kohdistus")."</th><td>";
	if ($asiakas['konserniyhtio'] != '') {
		echo "<input type='hidden' name='vastatili' value='konserni'>".t("Konsernimyyntisaamiset")."";
	}
	else {
		echo "<input type='radio' name='vastatili' value='myynti' $sel1> ".t("Myyntisaamiset")."
				<input type='radio' name='vastatili' value='factoring' $sel2> ".t("Factoringsaamiset")."</td></tr>";
	}
	echo "
	<tr>
		<th>".t("Maksupäivä (pp kk vvvv)")."</th>";

		if ($kka == "")
			$kka = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
		if ($vva == "")
			$vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
		if ($ppa == "")
			$ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));


		echo "<td><input name='ppa' size='3' value='$ppa'> <input name='kka' size='3' value='$kka'> <input name='vva' size=5' value='$vva'></td>";

	echo "	</tr>
	<tr>
		<th>".t("Summa")."</th>
		<td>
		<input type='text' name='summa' value='$summa'>
		</td>
	</tr>
	<tr>
		<th>".t("Selite")."</th>
		<td>
		<input type='text' name='selite' size='40' value='$selite'>
		</td>
	</tr>
	<tr>
		<th>".t("Tulosta suorituksesta kuitti")."</th>
		<td>";

		echo "<select name='tulostakuitti'>";
		echo "<option value=''>".t("Ei tulosteta")."</option>";

		$querykieli = "	SELECT *
						FROM kirjoittimet
						WHERE yhtio = '$kukarow[yhtio]'
						ORDER BY kirjoitin";
		$kires = mysql_query($querykieli) or pupe_error($querykieli);

		while ($kirow=mysql_fetch_array($kires)) {
			echo "<option value='$kirow[komento]'>$kirow[kirjoitin]</option>";
		}

		echo "</select>";

	echo "</td>
	</tr>
	</table>
	<br>
	<input type='submit' name='jatko' value='".t("Tallenna suoritus ja siirry kohdistukseen")."'>
	<input type='submit' name='jatko' value='".t("Tallenna suoritus")."'>
	</form>";

	$formi = "formi";
	$kentta = "summa";
}


if ($tee == "" and $ytunnus == "") {
	echo "<font class='message'>Voit etsiä maksajaa nimen, ytunnuksen tai laskunumeron perusteella.</font><br>";
	echo "<br><form action = '$PHP_SELF' method='post' name='maksaja'>
			".t("Maksaja").":
			<input type='text' name='ytunnus'>
			<input type='hidden' name='muutparametrit' value='$tiliote#$summa#$ppa#$kka#$vva#$mtili'>
			<input type='hidden' name='tee' value='ETSI'>
			<input type='submit' value='".t("Etsi")."'></form>";

	$formi = "maksaja";
	$kentta = "ytunnus";
}

require ("inc/footer.inc");

?>
