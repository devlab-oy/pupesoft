<?php

// tulostetaan kalkkyyli ruudulle
// tarvitaan $laskurow array, jossa on meid‰n lasku ostoreskontrasta...

if ($toiminto == "kaikkiok") {
	// etsit‰‰n laskun tilausrivit
	$query = "	SELECT tilausrivi.*, tilausrivi.varattu+tilausrivi.kpl varattu
				FROM tilausrivi
				JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.uusiotunnus
				JOIN tuotteen_toimittajat ON tilausrivi.yhtio = tuotteen_toimittajat.yhtio and tilausrivi.tuoteno = tuotteen_toimittajat.tuoteno and tuotteen_toimittajat.liitostunnus = lasku.liitostunnus
				WHERE tilausrivi.uusiotunnus='$laskurow[tunnus]' and tilausrivi.yhtio='$kukarow[yhtio]'
				ORDER BY tuotteen_toimittajat.toim_tuoteno";
}
else {
	// etsit‰‰n laskun tilausrivit
	$query = "	SELECT tilausrivi.*
				FROM tilausrivi
				JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.uusiotunnus
				JOIN tuotteen_toimittajat ON tilausrivi.yhtio = tuotteen_toimittajat.yhtio and tilausrivi.tuoteno = tuotteen_toimittajat.tuoteno and tuotteen_toimittajat.liitostunnus = lasku.liitostunnus
				WHERE tilausrivi.uusiotunnus='$laskurow[tunnus]' and tilausrivi.varattu<>0 and tilausrivi.yhtio='$kukarow[yhtio]'
				ORDER BY tuotteen_toimittajat.toim_tuoteno";
}
$presult = mysql_query($query) or pupe_error($query);

// lasketaan tilauksen rahtikulut
//arvotaan illan 7 oikein, eli kurssi
if ($laskurow["maksu_kurssi"] == 0) {	//t‰m‰ olisi huonompi juttu, mut ei mik‰‰n stopperi
	$laskurow["maksu_kurssi"] = $laskurow["vienti_kurssi"];
}

//tsekataan viel‰
if ($laskurow["maksu_kurssi"] == 0) {	//t‰m‰ olisi huonompi juttu, mut ei mik‰‰n stopperi
	echo t("Kurssi uupuu! Jossain on iso ongelma ,8,1");
	exit;
}

// jos erikoislale niin otetaan sit‰ kanssa huomioon
if ($laskurow['erikoisale']<>0) {
	$erikoisale = 1-($laskurow['erikoisale']/100);
}
else {
	$erikoisale = 1;
}


if ($toiminto == "kaikkiok" and $tee == "") {
	// jos lasketaan virallista varastonarvoa, otetaan keikan summa huomioon (kululaskujen yhteenlaskettu on laskurow.saldo_maksettu)
	$rahtikulut = $laskurow['saldo_maksettu'] + round($laskurow['rahti_etu'] * $laskurow['maksu_kurssi'], 2);
	$rahtitapa  = t("Virallinen");
}
else {
	// muuten rahtikulut valuutassa laskettuna kuluprosentin mukaan (laskurow.rahti on kuluprosentti, laskurow.summa on kaikkien kohdistettujen rivien summa)

	// jos erikoislale niin otetaan sit‰ kanssa huomioon
	if ($laskurow['erikoisale']<>0) {
		$erikoisale = 1-($laskurow['erikoisale']/100);
	}
	else {
		$erikoisale=1;
	}


	// lasketaan nyt varastoon viet‰vien rivien summa yhteens‰
	$query    = "	SELECT sum((tilausrivi.varattu+tilausrivi.kpl)*if(tuotekerroin<=0,1,tuotekerroin) * tilausrivi.hinta * $erikoisale) summa
					FROM tilausrivi use index (uusiotunnus_index), tuotteen_toimittajat
					WHERE uusiotunnus='$otunnus'
					and tilausrivi.yhtio ='$kukarow[yhtio]'
					and varattu<>0
					and tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]'
					and tuotteen_toimittajat.yhtio=tilausrivi.yhtio
					and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno";
	$result   = mysql_query($query) or pupe_error($query);
	$hintarow = mysql_fetch_array($result);
	$laskurow['summa'] = $hintarow['summa'];

	// lasketaan itse rahtikulut
	$rahtikulut = round($laskurow['summa'] * $laskurow['maksu_kurssi'] * ($laskurow['rahti'] / 100), 2);
	$rahtitapa  = t("Kuluprosentti") ." ". $laskurow['rahti'] ."%";

	// jos ollaan annettu t‰lle batchille kulusumma, k‰ytet‰‰nkin vaan sit‰!
	if ($laskurow["rahti_huolinta"] != 0) {
		$rahtikulut = $laskurow["rahti_huolinta"];
		$rahtitapa  = t("Kulusumma");
	}
}

echo "<font class='message'>".t("Keikka")." $laskurow[laskunro] $laskurow[nimi]</font><br>";
echo "<font class='message'>".t("Rahdin m‰‰r‰ yhteens‰").": $rahtikulut $yhtiorow[valkoodi] (".t("Peruste").": $rahtitapa)<br><hr></font>";

$summa = 0;

if (mysql_num_rows($presult) > 0) {
	echo "<table><tr>";

	if ($toiminto != "kaikkiok") {
		echo "<th valign='top'>".t("Varastoon")."</th>";
	}

	echo "<th valign='top'>".t("Tuoteno")."</th>
			<th valign='top'>".t("M‰‰r‰")."<br>".t("sis").".</th>
			<th valign='top'>".t("M‰‰r‰")."<br>".t("ulk").".</th>
			<th valign='top'>".t("Ostohinta")."<br>".t("ulk").".</th>
			<th valign='top'>".t("Ostohinta")."<br>".t("sis").".</th>
			<th valign='top'>".t("Osuus")."<br>".t("rahdista")."</th>
			<th valign='top'>".t("Tulli")."</th>
			<th valign='top'>".t("Ostohinta")."+<br>".t("Rahti")."+".t("Tulli")."</th>
			<th valign='top'>".t("Rivihinta")."</th>
			<th valign='top'>".t("Vanha")."<br>".t("kehahin")."</th>
			<th valign='top'>".t("Uusi")."<br>".t("kehahin")."</th>
			<th valign='top'>".t("Ei tullia")."</th>
			</tr>";

	if ($toiminto == "kaikkiok") {
		$tm = "kaikkiok";
	}
	else {
		$tm = "kalkyyli";
	}

	echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
			<!--

			function toggleAll(toggleBox) {

				var currForm = toggleBox.form;
				var isChecked = toggleBox.checked;
				var nimi = toggleBox.name;

				for (var elementIdx=0; elementIdx<currForm.elements.length; elementIdx++) {
					if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].name.substring(0,3) == nimi) {
						currForm.elements[elementIdx].checked = isChecked;
					}
				}
			}

			//-->
			</script>";

	echo "<form action='$PHP_SELF' method='post'>";
	echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
	echo "<input type='hidden' name='toiminto' value='$tm'>";
	echo "<input type='hidden' name='tee' value='eitullia'>";
	echo "<input type='hidden' name='otunnus' value='$otunnus'>";

}
else {
	echo "<font class='error'>".t("Yht‰‰n rivi‰ ei lˆytynyt!")."</font>";
}

while ($tilrivirow = mysql_fetch_array($presult)) {

	// lasketaan keskihankintahinta, tarvitaan tuotteen saldo ja tuotteen tiedot
	$query  = "select sum(saldo) saldo from tuotepaikat where yhtio='$kukarow[yhtio]' and tuoteno='$tilrivirow[tuoteno]'";
	$salres = mysql_query($query) or pupe_error($query);
	$salrow = mysql_fetch_array($salres);

	$query  =	"select *
				from tuote, tuotteen_toimittajat
				where tuote.yhtio='$kukarow[yhtio]' and tuote.tuoteno='$tilrivirow[tuoteno]' and tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]'
				and tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno";
	$tuores = mysql_query($query) or pupe_error($query);
	$tuorow = mysql_fetch_array($tuores);

	if ($tuorow['tuotekerroin'] <= 0) $tuorow['tuotekerroin']=1;

	// jos kysess‰ on kotimainen vaihto-omaisuuslasku, pit‰‰ lis‰t‰ tuotteen hintaan alvi
	if ($laskurow['vienti'] == 'C' or $laskurow['vienti'] == 'J') {
		$alvit = 1 + $tuorow["alv"] / 100;
	}
	else {
		$alvit = 1;
	}

	$osuus   = $erikoisale*$tilrivirow['hinta']*$alvit*abs($tilrivirow['varattu'])*$tuorow['tuotekerroin']/$laskurow['summa'];	// kuinka paljon t‰m‰ rivi on koko tilauksesta
	$rahtios = $osuus*$rahtikulut;	// lasketaan sama osuus rahtikuluista t‰lle riville

	if ($tilrivirow['varattu'] < 0) {
		$rahtios = $rahtios * -1;
	}

	$ohinta  = round($erikoisale*$tilrivirow['hinta']*(1-($tilrivirow['ale']/100))*$tuorow['tuotekerroin']*$laskurow['maksu_kurssi']*$tilrivirow['varattu']+$rahtios,4); // tuotteen hinta rahteineen

	$tulliprossa = "";
	$tv_nimrow	 = "";

	if ($tilrivirow["var"] == "" and $laskurow["maa"] != $yhtiorow["maa"]) {
		//lis‰t‰‰n tulli
		require ("taric_veroperusteet.inc");
		$ohinta = $ohinta * (1+($tulliprossa/100));
		$tv_nimrow["maara"] = $tv_nimrow["maara"] * 1; // teh‰‰n numero, ett‰ lista n‰ytt‰‰ kivemmalta
	}

	// lis‰t‰‰n riville extra kulu, jos sellanen oli annettu
	if ($tilrivirow["kate"] != 0) {
		$kuluteksti = "(".round($ohinta,4)." + $tilrivirow[kate])";
		$ohinta = $ohinta + $tilrivirow['kate'];
	}
	else $kuluteksti = "";

	$rivihin = round($ohinta,2);	// tilausrivin rivihinta talteen
	$ohinta  = round($ohinta / $tilrivirow['varattu'],4); // yhden hinta


	//Keskihankintahintaa yll‰pidet‰‰n vain jos tuotteella ei ole sarjanumeroseurantaa tai marginaaliverostusta
	if ($tuorow["sarjanumeroseuranta"] == "S") {
		//Laitetaan kehahintamuuttujaan t‰m‰n keikan ostohinta jotta tapahtumat n‰ytt‰isiv‰t fiksuja lukuja
		$kehahin = $ohinta;	
	}
	else {
		if ($salrow['saldo'] + $tilrivirow['varattu'] > 0 and $salrow['saldo'] > 0) {
			// kehahin matikka, tuotteella pit‰‰ olla saldoa ennen ja j‰lkeen ett‰ edes tehd‰‰n matikkaa
			$kehahin = round(($salrow['saldo'] * $tuorow['kehahin'] + $ohinta * $tilrivirow['varattu']) / ($salrow['saldo'] + $tilrivirow['varattu']),4);
		}
		else {
			// Jos j‰‰d‰‰n saldossa t‰m‰nkin tulon j‰lkeen miinukselle tai nollille, laitetaan kehahin suoraan t‰m‰n keikan hinnasta
			$kehahin = round($ohinta,4);
		}
	}

	echo "<tr>";


	if ($toiminto != "kaikkiok") {
		$chk = "";
		if ($tilrivirow["netto"] == '') {
			$chk = "CHECKED";
		}

		echo "<td align='center'><input type='checkbox' name='varastoonko[]' value='$tilrivirow[tunnus]' $chk></td>";
	}

	echo "<td>$tilrivirow[tuoteno]</td>
		<td align='right'>$tilrivirow[varattu]</td>
		<td align='right'>".sprintf('%.2f',round($tilrivirow['varattu']*$tuorow['tuotekerroin'],2))."</td>
		<td align='right'>$tilrivirow[hinta]</td>
		<td align='right'>".sprintf('%.2f',round($tilrivirow['hinta']*$tuorow['tuotekerroin'],2))."</td>
		<td align='right'>".sprintf('%.6f',round($osuus,6))."</td>
		<td align='right'>$tv_nimrow[maara] $tv_nimrow[rahayksikko]</td>
		<td align='right'>".sprintf('%.4f',$ohinta)."</td>
		<td align='right'>".sprintf('%.4f',round($ohinta*$tilrivirow['varattu'],4))." $kuluteksti</td>";

	if ($tuorow["sarjanumeroseuranta"] == "S") {
		echo "	<td align='right'></td>
				<td align='right'></td>";
	}
	else {
		echo "	<td align='right'>$tuorow[kehahin]</td>
				<td align='right'>".sprintf('%.4f',$kehahin)."</td>";	
	}

	$chk = "";
	if ($tilrivirow["var"] != '') {
		$chk = "CHECKED";
	}

	echo "<td align='center'><input type='checkbox' name='eitullia[]' value='$tilrivirow[tunnus]' $chk></td>";

	echo "</tr>";
	$summa += round($ohinta*$tilrivirow['varattu'],2);

}

if ($toiminto != "kaikkiok") {
	echo "<td align='center'><input type='checkbox' name='var' onclick='toggleAll(this);'></td><th><--".t("Ruksaa kaikki")."</th>";
}

if ($summa != 0) {
	echo "<tr>";

	if ($toiminto != "kaikkiok") {
		echo "<th><input type='submit' value='".t("P‰ivit‰")."'></th>";
	}

	echo "<th colspan='8' align='right'>".t("Yhteens‰").":</th>
			<th colspan='3'>$summa</th>
			<th><input type='submit' value='".t("P‰ivit‰")."'></form></th>
			</tr>";
	echo "</table><br>";
}
else {
	echo "</table><br>";
}

?>
