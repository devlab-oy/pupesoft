<?php

require("inc/parametrit.inc");

echo "<font class='head'>".t("Tullinimikkeet")."</font><hr>";

/*
// nämä pitää ajaa jos päivittää uudet tullinimikkeet:
update tullinimike set su=trim(su);
update tullinimike set su='' where su='-';
update tullinimike set su_vientiilmo='NAR' where su='p/st';
update tullinimike set su_vientiilmo='MIL' where su='1 000 p/st';
update tullinimike set su_vientiilmo='MIL' where su='1000 p/st';
update tullinimike set su_vientiilmo='LPA' where su='l alc. 100';
update tullinimike set su_vientiilmo='LTR' where su='l';
update tullinimike set su_vientiilmo='KLT' where su='1 000 l';
update tullinimike set su_vientiilmo='KLT' where su='1000 l';
update tullinimike set su_vientiilmo='TJO' where su='TJ';
update tullinimike set su_vientiilmo='MWH' where su='1 000 kWh';
update tullinimike set su_vientiilmo='MWH' where su='1000 kWh';
update tullinimike set su_vientiilmo='MTQ' where su='m³';
update tullinimike set su_vientiilmo='MTQ' where su='m3';
update tullinimike set su_vientiilmo='GRM' where su='g';
update tullinimike set su_vientiilmo='MTK' where su='m²';
update tullinimike set su_vientiilmo='MTK' where su='m2';
update tullinimike set su_vientiilmo='MTR' where su='m';
update tullinimike set su_vientiilmo='NPR' where su='pa';
update tullinimike set su_vientiilmo='CEN' where su='100 p/st';
*/

if ($tee == "muuta") {

	$ok = 0;
	$uusitullinimike1 = trim($uusitullinimike1);
	$uusitullinimike2 = trim($uusitullinimike2);

	// katotaan, että tullinimike1 löytyy
	$query = "SELECT cn FROM tullinimike WHERE cn = '$uusitullinimike1' and kieli = '$yhtiorow[kieli]'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) != 1 or $uusitullinimike1 == "") {
		$ok = 1;
		echo "<font class='error'>Tullinimike 1 on virheellinen!</font><br>";
	}

	// kaks pitkä tai ei mitään
	if (strlen($uusitullinimike2) != 2) {
		$ok = 1;
		echo "<font class='error'>Tullinimike 2 tulee olla 2 merkkiä pitkä!</font><br>";
	}

	// tää on aika fiinisliippausta
	if ($ok == 1) echo "<br>";

	// jos kaikki meni ok, nii päivitetään
	if ($ok == 0) {

		if ($tullinimike2 != "") $lisa = " and tullinimike2='$tullinimike2'";
		else $lisa = "";

		$query = "update tuote set tullinimike1='$uusitullinimike1', tullinimike2='$uusitullinimike2' where yhtio='$kukarow[yhtio]' and tullinimike1='$tullinimike1' $lisa";
		$result = mysql_query($query) or pupe_error($query);

		echo sprintf("<font class='message'>Päivitettiin %s tuotetta.</font><br><br>", mysql_affected_rows());

		$tullinimike1 = $uusitullinimike1;
		$tullinimike2 = $uusitullinimike2;
		$uusitullinimike1 = "";
		$uusitullinimike2 = "";
	}
}

if ($tee == "synkronoi") {

	$ok = FALSE;

	if ($file = fopen("http://api.devlab.fi/referenssitullinimikkeet.sql","r")) {
		$ok = TRUE;
	}
	elseif ($file = fopen("http://10.0.1.2/referenssitullinimikkeet.sql","r")) {
		$ok = TRUE;
	}

	if (!$ok) {
		echo t("Tiedoston avaus epäonnistui")."!";
		require ("inc/footer.inc");
		exit;
	}

	echo "<br><br>";
	echo t("Poistetaan vanhat tullinimikkeet")."...<br>";

	// Poistetaan nykyiset nimikkeet....
	$query  = "	DELETE FROM tullinimike";
	$result = mysql_query($query) or pupe_error($query);

	// Päivitetään tuotteet 2010 - 2011
	$muunnosavaimet = array(
		"31052010"=>"31052000",
		"31052090"=>"31052000",
		"31056010"=>"31056000",
		"31056090"=>"31056000",
		"39046990"=>"39046920",
		"41063110"=>"41063100",
		"41063190"=>"41063100",
		"41063210"=>"41063200",
		"41063290"=>"41063200",
		"42032991"=>"42032990",
		"42032999"=>"42032990",
		"46021991"=>"46021990",
		"46021999"=>"46021990",
		"48092010"=>"48092000",
		"48092090"=>"48092000",
		"57050010"=>"57050080",
		"57050090"=>"57050080",
		"66019911"=>"66019920",
		"66019919"=>"66019920",
		"68029110"=>"68029100",
		"68029190"=>"68029100",
		"68029210"=>"68029200",
		"68029290"=>"68029200",
		"68030010"=>"68030000",
		"68030090"=>"68030000",
		"68053010"=>"68053000",
		"68053020"=>"68053000",
		"68053080"=>"68053000",
		"68071010"=>"68071000",
		"68071090"=>"68071000",
		"68101910"=>"68101900",
		"68101931"=>"68101900",
		"68101939"=>"68101900",
		"68101990"=>"68101900",
		"68109110"=>"68109100",
		"68109190"=>"68109100",
		"68118210"=>"68118200",
		"68118290"=>"68118200",
		"68159910"=>"68159900",
		"68159990"=>"68159900",
		"69079010"=>"69079020",
		"69079091"=>"69079020",
		"69079093"=>"69079080",
		"69079099"=>"69079080",
		"69081010"=>"69081000",
		"69081090"=>"69081000",
		"69089021"=>"69089020",
		"69089029"=>"69089020",
		"69139091"=>"69139098",
		"69139099"=>"69139098",
		"69149010"=>"69149000",
		"69149090"=>"69149000",
		"71069110"=>"71069100",
		"71069190"=>"71069100",
		"71069220"=>"71069200",
		"71069280"=>"71069200",
		"71159010"=>"71159000",
		"71159090"=>"71159000",
		"71162019"=>"71162080",
		"71162090"=>"71162080",
		"71171910"=>"71171900",
		"71171991"=>"71171900",
		"71171999"=>"71171900",
		"71181010"=>"71181000",
		"71181090"=>"71181000",
		"74072910"=>"74072900",
		"74072990"=>"74072900",
		"74094010"=>"74094000",
		"74094090"=>"74094000",
		"74111011"=>"74111010",
		"74111019"=>"74111010",
		"74130020"=>"74130000",
		"74130080"=>"74130000",
		"76061210"=>"76061220",
		"76061250"=>"76061220",
		"76061291"=>"76061292",
		"76071991"=>"76071990",
		"76071999"=>"76071990",
		"76072091"=>"76072090",
		"76072099"=>"76072090",
		"76129010"=>"76129090",
		"76129091"=>"76129090",
		"76129098"=>"76129090",
		"78019991"=>"78019990",
		"78019999"=>"78019990",
		"78060030"=>"78060080",
		"78060050"=>"78060080",
		"78060090"=>"78060080",
		"79070010"=>"79070000",
		"79070090"=>"79070000",
		"80070030"=>"80070080",
		"80070050"=>"80070080",
		"80070090"=>"80070080",
		"82029911"=>"82029920",
		"82029919"=>"82029980",
		"82029990"=>"82029920",
		"82032010"=>"82032000",
		"82032090"=>"82032000",
		"82055930"=>"82055980",
		"82055990"=>"82055980",
		"82083010"=>"82083000",
		"82083090"=>"82083000",
		"82119130"=>"82119100",
		"82119180"=>"82119100",
		"83030010"=>"83030040",
		"83030030"=>"83030040",
		"83062910"=>"83062900",
		"83062990"=>"83062900",
		"83111010"=>"83111000",
		"83111090"=>"83111000",
		"84313910"=>"84313900",
		"84313970"=>"84313900",
		"85013220"=>"85013200",
		"85013280"=>"85013200",
		"85013450"=>"85013400",
		"85013492"=>"85013400",
		"85013498"=>"85013400",
		"85043220"=>"85043200",
		"85043280"=>"85043200",
		"85044040"=>"85044082",
		"85044081"=>"85044082",
		"85059010"=>"85059020",
		"85059030"=>"85059020",
		"85061015"=>"85061018",
		"85061019"=>"85061018",
		"85061095"=>"85061098",
		"85061099"=>"85061098",
		"85063010"=>"85063000",
		"85063030"=>"85063000",
		"85063090"=>"85063000",
		"85064010"=>"85064000",
		"85064030"=>"85064000",
		"85064090"=>"85064000",
		"85066010"=>"85066000",
		"85066030"=>"85066000",
		"85066090"=>"85066000",
		"85068011"=>"85068080",
		"85068015"=>"85068080",
		"85068090"=>"85068080",
		"85071041"=>"85071020",
		"85071049"=>"85071080",
		"85071092"=>"85071020",
		"85071098"=>"85071080",
		"85072041"=>"85072020",
		"85072049"=>"85072080",
		"85072092"=>"85072020",
		"85072098"=>"85072080",
		"85073081"=>"85073080",
		"85073089"=>"85073080",
		"85079020"=>"85079080",
		"85079090"=>"85079080",
		"85143019"=>"85143000",
		"85143099"=>"85143000",
		"85152910"=>"85152900",
		"85152990"=>"85152900",
		"85158011"=>"85158010",
		"85158019"=>"85158010",
		"85161019"=>"85161080",
		"85161090"=>"85161080",
		"85163110"=>"85163100",
		"85163190"=>"85163100",
		"85164010"=>"85164000",
		"85164090"=>"85164000",
		"85166051"=>"85166050",
		"85166059"=>"85166050",
		"85184081"=>"85184080",
		"85184089"=>"85184080",
		"85284935"=>"85284980",
		"85284991"=>"85284980",
		"85284999"=>"85284980",
		"85285990"=>"85285940",
		"85287231"=>"85287230",
		"85287233"=>"85287230",
		"85287235"=>"85287230",
		"85287239"=>"85287230",
		"85287251"=>"85287230",
		"85287259"=>"85287230",
		"85287275"=>"85287230",
		"85287291"=>"85287240",
		"85287299"=>"85287240",
		"85393210"=>"85393220",
		"85393250"=>"85393220",
		"85394910"=>"85394900",
		"85394930"=>"85394900",
		"85401111"=>"85401100",
		"85401113"=>"85401100",
		"85401115"=>"85401100",
		"85401119"=>"85401100",
		"85401191"=>"85401100",
		"85401199"=>"85401100",
		"85437051"=>"85437050",
		"85437055"=>"85437050",
		"85437059"=>"85437050",
		"85441910"=>"85441900",
		"85441990"=>"85441900",
		"85451910"=>"85451900",
		"85451990"=>"85451900",
		"85462010"=>"85462000",
		"85462091"=>"85462000",
		"85462099"=>"85462000",
		"85471010"=>"85471000",
		"85471090"=>"85471000",
		"86071901"=>"86071910",
		"86071911"=>"86071910",
		"86071918"=>"86071910",
		"86071991"=>"86071990",
		"86071999"=>"86071990",
		"86072910"=>"86072900",
		"86072990"=>"86072900",
		"86073001"=>"86073000",
		"86073099"=>"86073000",
		"86079191"=>"86079190",
		"86079199"=>"86079190",
		"86079930"=>"86079980",
		"86079950"=>"86079980",
		"86079990"=>"86079980",
		"86080010"=>"86080000",
		"86080030"=>"86080000",
		"86080090"=>"86080000",
		"89019091"=>"89019090",
		"89019099"=>"89019090",
		"89020012"=>"89020010",
		"89020018"=>"89020010",
		"89039192"=>"89039190",
		"89039199"=>"89039190",
		"90031910"=>"90031900",
		"90031930"=>"90031900",
		"90031990"=>"90031900",
		"90172011"=>"90172010",
		"90172019"=>"90172010",
		"90173010"=>"90173000",
		"90173090"=>"90173000",
		"90189041"=>"90189040",
		"90189049"=>"90189040",
		"90189070"=>"90189084",
		"90189085"=>"90189084",
		"90229010"=>"90229000",
		"90229090"=>"90229000",
		"90278093"=>"90278099",
		"90278097"=>"90278099",
		"91059910"=>"91059900",
		"91059990"=>"91059900",
		"91069010"=>"91069000",
		"91069080"=>"91069000",
		"91139010"=>"91139000",
		"91139080"=>"91139000",
		"93062940"=>"93062900",
		"93062970"=>"93062900",
		"93063091"=>"93063090",
		"93063093"=>"93063090",
		"93063097"=>"93063090",
		"94013010"=>"94013000",
		"94013090"=>"94013000",
		"94031010"=>"94031058",
		"94031059"=>"94031058",
		"94031099"=>"94031098",
		"94051028"=>"94051040",
		"94051030"=>"94051040",
		"94052019"=>"94052040",
		"94052030"=>"94052040",
		"94059111"=>"94059110",
		"94059119"=>"94059110",
		"95042010"=>"95042000",
		"95042090"=>"95042000",
		"95043030"=>"95043020",
		"95043050"=>"95043020",
		"95064010"=>"95064000",
		"95064090"=>"95064000",
		"95066210"=>"95066200",
		"95066290"=>"95066200",
		"96019010"=>"96019000",
		"96019090"=>"96019000",
		"96081030"=>"96081092",
		"96081030"=>"96081099",
		"96081091"=>"96081092",
		"96081099"=>"96081099",
		"96089920"=>"96089900",
		"96089980"=>"96089900",
		"96132010"=>"96132000",
		"96132090"=>"96132000",
		"96170011"=>"96170000",
		"96170019"=>"96170000",
		"96170090"=>"96170000");

	echo t("Päivitetään muuttuneet tullinimikkeet tuotteille")."...<br>";

	foreach ($muunnosavaimet as $vanha => $uusi) {
		$query  = "	UPDATE tuote set
					tullinimike1 = '$uusi'
					where yhtio			= '$kukarow[yhtio]'
					and tullinimike1	= '$vanha'";
		$result = mysql_query($query) or pupe_error($query);
	}

	// Eka rivi roskikseen
	$rivi = fgets($file);

	echo t("Lisätään uudet tullinimikkeet tietokantaan")."...<br>";

	while ($rivi = fgets($file)) {
		list($cnkey, $cn, $dashes, $dm, $su, $su_vientiilmo, $kieli) = explode("\t", trim($rivi));

		$dm = preg_replace("/([^A-Z0-9öäåÅÄÖ \.,\-_\:\/\!\|\?\+\(\)%#]|é)/i", "", $dm);

		$query  = "	INSERT INTO tullinimike SET
					yhtio			= '$kukarow[yhtio]',
					cnkey			= '$cnkey',
					cn				= '$cn',
					dashes			= '$dashes',
					dm				= '$dm',
					su				= '$su',
					su_vientiilmo	= '$su_vientiilmo',
					kieli			= '$kieli',
					laatija			= '$kukarow[kuka]',
					luontiaika		= now()";
		$result = mysql_query($query) or pupe_error($query);
	}

	fclose($file);

	echo t("Päivitys valmis")."...<br><br><hr>";
}


echo "<br><form action = '$PHP_SELF' method='post' autocomplete='off'>";
echo t("Listaa ja muokkaa tuotteiden tullinimikkeitä").":<br><br>";
echo "<table>";
echo "<tr>";
echo "<th>".t("Syötä tullinimike").":</th>";
echo "<td><input type='text' name='tullinimike1' value='$tullinimike1'></td>";
echo "</tr><tr>";
echo "<th>".t("Syötä tullinimikkeen lisäosa").":</th>";
echo "<td><input type='text' name='tullinimike2' value='$tullinimike2'> (ei pakollinen) </td>";
echo "<td class='back'><input type='submit' value='".t("Hae")."'></td>";
echo "</tr></table>";
echo "</form><br><br>";

echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
echo "<input type='hidden' name='tee' value='synkronoi'>";
echo t("Päivitä järjestelmän tullinimiketietokanta").":<br><br>";
echo "<table>";
echo "<th>".t("Nouda uusimmat tullinimikkeet").":</th>";
echo "<td><input type='submit' value='".t("Nouda")."'></td>";
echo "</tr></table>";
echo "</form>";


if ($tullinimike1 != "") {

	if ($tullinimike2 != "") $lisa = " and tullinimike2='$tullinimike2'";
	else $lisa = "";

	$query = "	SELECT *
				from tuote use index (yhtio_tullinimike)
				where yhtio = '$kukarow[yhtio]'
				and tullinimike1 = '$tullinimike1' $lisa
				order by tuoteno";
	$resul = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($resul) == 0) {
		echo "<font class='error'>Yhtään tuotetta ei löytynyt!</font><br>";
	}
	else {

		echo sprintf("<font class='message'>Haulla löytyi %s tuotetta.</font><br><br>", mysql_num_rows($resul));

		echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
		echo "<input type='hidden' name='tullinimike1' value='$tullinimike1'>";
		echo "<input type='hidden' name='tullinimike2' value='$tullinimike2'>";
		echo "<input type='hidden' name='tee' value='muuta'>";

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Syötä uusi tullinimike").":</th>";
		echo "<td><input type='text' name='uusitullinimike1' value='$uusitullinimike1'></td>";
		echo "</tr><tr>";
		echo "<th>".t("Syötä tullinimikkeen lisäosa").":</th>";
		echo "<td><input type='text' name='uusitullinimike2' value='$uusitullinimike2'></td>";
		echo "<td class='back'><input type='submit' value='".t("Päivitä")."'></td>";
		echo "</tr></table>";
		echo "</form><br>";

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Tuoteno")."</th>";
		echo "<th>".t("Osasto")."</th>";
		echo "<th>".t("Try")."</th>";
		echo "<th>".t("Merkki")."</th>";
		echo "<th>".t("Nimitys")."</th>";
		echo "<th>".t("Tullinimike")."</th>";
		echo "<th>".t("Tullinimikkeen lisäosa")."</th>";
		echo "</tr>";

		while ($rivi = mysql_fetch_array($resul)) {

			// tehdään avainsana query
			$oresult = t_avainsana("OSASTO", "", "and avainsana.selite ='$rivi[osasto]'");
			$os = mysql_fetch_array($oresult);

			// tehdään avainsana query
			$tresult = t_avainsana("TRY", "", "and avainsana.selite ='$rivi[try]'");
			$try = mysql_fetch_array($tresult);

			echo "<tr>";
			echo "<td><a href='yllapito.php?toim=tuote&tunnus=$rivi[tunnus]&lopetus=tullinimikkeet.php'>$rivi[tuoteno]</a></td>";
			echo "<td>$rivi[osasto] $os[selitetark]</td>";
			echo "<td>$rivi[try] $try[selitetark]</td>";
			echo "<td>$rivi[tuotemerkki]</td>";
			echo "<td>".t_tuotteen_avainsanat($rivi, 'nimitys')."</td>";
			echo "<td>$rivi[tullinimike1]</td>";
			echo "<td>$rivi[tullinimike2]</td>";
			echo "</tr>";
		}
		echo "</table>";
	}
}

require ("inc/footer.inc");

?>