<?php
require("inc/parametrit.inc");

$query = "	SELECT nimitys
			FROM tuote
			WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$yhtiorow[ennakkomaksu_tuotenumero]'";
$tresult = mysql_query($query) or pupe_error($query);

if(mysql_num_rows($tresult) == 0) die(t("VIRHE: Yhtiˆll‰ EI OLE ennakkolaskutustuotetta, sopimuslaskutusta ei voida toteuttaa!"));


echo "<font class='head'>".t("Sopimuslaskutus").":</font><hr><br><br>";

$debug = 0;

// laskutuksen tarkastukset
if($tee == "ennakkolaskuta" or $tee == "loppulaskuta") {

	//	tarkistetaan ett‰ meill‰ on jotain j‰rkev‰‰ laskutettavaa
	$query = "	SELECT *
				FROM maksupositio
				WHERE yhtio = '$kukarow[yhtio]'
				and otunnus = '$tunnus'
				and uusiotunnus = ''
				ORDER BY tunnus
				LIMIT 1";
	$posres = mysql_query($query) or pupe_error($query);
	$posrow = mysql_fetch_array($posres);


	if ($debug==1) echo t("Lˆydettiin maksupositio")." $posrow[tunnus], $posrow[osuus] %, $posrow[maksuehto]<br>";

	if ($posrow["summa"] <= 0 or $posrow["maksuehto"] == 0 or (int) $posrow["tunnus"] == 0) {
		echo $query." ".t("VIRHE: laskutusposition summa on nolla tai sen alle. Korjaa t‰m‰!<br>");
		$tee = "";
	}

	$query = "	SELECT
				sum(if(uusiotunnus != '', 1, 0)) laskutettu,
				count(*) yhteensa
				FROM maksupositio
				WHERE yhtio = '$kukarow[yhtio]'
				and otunnus = '$tunnus'";
	$abures = mysql_query($query) or pupe_error($query);
	$aburow = mysql_fetch_array($abures);

	$lahteva_lasku = ($abu["laskutettu"] + 1)."/".$aburow["yhteensa"];

	// varmistetaan ett‰ laskutus n‰ytt‰isi olevan OK!!
	if($tee == "loppulaskuta") {
		if($aburow["yhteensa"] - $aburow["laskutettu"] != 1) {
			echo t("SHIT: Koitetaan loppulaskuttaa mutta positioita on j‰ljell‰ enemm‰in kuin yksi! Ei runkita systeemi‰!<br>");
			$tee = "";
		}
	}
}

if($tee == "ennakkolaskuta") {

	///* Etsit‰‰n alkuper‰isen-rivin laskun kaikki tiedot *///
	$query = "	SELECT *
				FROM lasku
				WHERE yhtio='$kukarow[yhtio]'
				and tunnus='$tunnus'";
	$stresult = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($stresult) == 0) {
		echo "Otsikkoa '$tunnus' ei lˆytynyt";
		exit;
	}
	$laskurow=mysql_fetch_array ($stresult);

	if ($debug==1) echo t("Perusotsikko lˆytyi")." $laskurow[nimi]<br>";


	// tehd‰‰n vanhasta laskusta 1:1 kopio...
	$query = "insert into lasku set ";
	for ($i=0; $i<mysql_num_fields($stresult); $i++) {

		// paitsi tilaan laitetaan N
		if (mysql_field_name($stresult,$i)=='tila') {
			$query .= "tila='N',";

		}
		elseif (mysql_field_name($stresult,$i)=='alatila') {
			$query .= "alatila='',";
		}
		elseif (mysql_field_name($stresult,$i)=='ketjutus') {
			$query .= "ketjutus='o',";
		}
		elseif (mysql_field_name($stresult,$i)=='tilaustyyppi') {
			$query .= "tilaustyyppi='L',";
		}
		// laatijaksi klikkaaja
		elseif (mysql_field_name($stresult,$i)=='laatija') {
			$query .= "laatija='$kukarow[kuka]',";
		}
		elseif (mysql_field_name($stresult,$i)=='eilahetetta') {
			$query .= "eilahetetta='',";
		}
		// ker‰ysaika, luontiaika ja toimitusaikaan now
		elseif (mysql_field_name($stresult,$i)=='kerayspvm' or
				mysql_field_name($stresult,$i)=='luontiaika' or
				mysql_field_name($stresult,$i)=='toimaika') {
			$query .= mysql_field_name($stresult,$i)."=now(),";
		}
		// n‰m‰ kent‰t tyhjennet‰‰n
		elseif (mysql_field_name($stresult,$i)=='kapvm' or
				mysql_field_name($stresult,$i)=='tapvm' or
				mysql_field_name($stresult,$i)=='olmapvm' or
				mysql_field_name($stresult,$i)=='summa' or
				mysql_field_name($stresult,$i)=='kasumma' or
				mysql_field_name($stresult,$i)=='hinta' or
				mysql_field_name($stresult,$i)=='kate' or
				mysql_field_name($stresult,$i)=='arvo' or
				mysql_field_name($stresult,$i)=='maksuaika' or
				mysql_field_name($stresult,$i)=='lahetepvm' or
				mysql_field_name($stresult,$i)=='viite' or
				mysql_field_name($stresult,$i)=='laskunro' or
				mysql_field_name($stresult,$i)=='mapvm' or
				mysql_field_name($stresult,$i)=='tilausvahvistus' or
				mysql_field_name($stresult,$i)=='viikorkoeur' or
				mysql_field_name($stresult,$i)=='tullausnumero' or
				mysql_field_name($stresult,$i)=='laskutuspvm' or
				mysql_field_name($stresult,$i)=='erpcm' or
				mysql_field_name($stresult,$i)=='laskuttaja' or
				mysql_field_name($stresult,$i)=='laskutettu' or
				mysql_field_name($stresult,$i)=='lahetepvm' or
				mysql_field_name($stresult,$i)=='maksaja' or
				mysql_field_name($stresult,$i)=='maksettu' or
				mysql_field_name($stresult,$i)=='maa_maara' or
				mysql_field_name($stresult,$i)=='kuljetusmuoto' or
				mysql_field_name($stresult,$i)=='kauppatapahtuman_luonne' or
				mysql_field_name($stresult,$i)=='sisamaan_kuljetus' or
				mysql_field_name($stresult,$i)=='sisamaan_kuljetusmuoto' or
				mysql_field_name($stresult,$i)=='poistumistoimipaikka' or
				mysql_field_name($stresult,$i)=='vanhatunnus' or
				mysql_field_name($stresult,$i)=='poistumistoimipaikka_koodi') {
			$query .= mysql_field_name($stresult,$i)."='',";
		}
		// maksuehto tulee t‰lt‰ positiolta
		elseif (mysql_field_name($stresult,$i)=='maksuehto') {
			$query .= "maksuehto ='$posrow[maksuehto]',";
		}
		// maksuehto tulee t‰lt‰ positiolta						/*	T‰t‰ ei kai tullut k‰ytetty‰, mutta siit‰ ei ollut haittaakaan voisi kai raportoida -tuomas 	*/
		elseif (mysql_field_name($stresult,$i)=='clearing') {
			$query .= "clearing ='ennakkolasku',";
		}
		// ja kaikki muut paitsi tunnus sellaisenaan
		elseif (mysql_field_name($stresult,$i)!='tunnus') {
			$query .= mysql_field_name($stresult,$i)."='".$laskurow[$i]."',";
		}
	}

	$query = substr($query,0,-1);
	$stresult = mysql_query($query) or pupe_error($query);
	$id = mysql_insert_id();

	if ($debug==1) echo t("Perustin laskun")." $laskurow[nimi] $id<br>";

	$query = "	SELECT nimitys
				FROM tuote
				WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$yhtiorow[ennakkomaksu_tuotenumero]'";
	$tresult = mysql_query($query) or pupe_error($query);
	$trow = mysql_fetch_array($tresult);
	$nimitys = $trow["nimitys"];

	//Lasketaan tilauksen arvo verokannoittain jotta voidaan laskuttaa ennakot oikeissa alveissa
	// ja lis‰t‰‰n ennakkolaskutusrivi laskulle, vain jaksotetut rivit!
	$query = "	SELECT
				round(sum(if(tilausrivi.jaksotettu='J', tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * tilausrivi.varattu * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)), 0)),2) jaksotettavaa
	 			FROM lasku
				JOIN tilausrivi ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi = 'L' and tilausrivi.jaksotettu='J'
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				and lasku.tunnus  = '$tunnus'
				GROUP by lasku.tunnus";
	$result = mysql_query($query) or pupe_error($query);
	$sumrow = mysql_fetch_array($result);

	$query = "	SELECT
				round(sum(if(tilausrivi.jaksotettu='J', tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * tilausrivi.varattu * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)), 0)),2) summa,
	 			if(tilausrivi.alv>=500, tilausrivi.alv-500, tilausrivi.alv) alv
				FROM lasku
				JOIN tilausrivi ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi = 'L' and tilausrivi.jaksotettu='J'
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				and lasku.tunnus  = '$tunnus'
				GROUP BY lasku.tunnus, alv";
	$sresult = mysql_query($query) or pupe_error($query);
	$tot = 0;

	if(mysql_num_rows($sresult) == 0) {
		echo "<font class = 'error'>".t("VIRHE: Ennakkolaskulla ei ole yht‰‰n jaksotettua tilausrivi‰!")." $tunnus</font><br>";
		echo "<font class = 'message'>".t("K‰y tekem‰ss‰ ennakkolasku manuaalisesti. Ennakkolaskulle perustetun laskun tunnus on")." $id</font><br>";
		echo "<font class = 'message'>".t("Ennakkolaskutuksen tuotenumero on")." $yhtiorow[ennakkomaksu_tuotenumero]</font><br><br>";

		$query  = "insert into tilausrivi (hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti) values  ('0', 'N', '1', '1', '$id', '$yhtiorow[ennakkomaksu_tuotenumero]', '$nimitys', '$kukarow[yhtio]', 'L', '$row[alv]', '".t("Ennakkolasku")." $lahteva_lasku ".t("tilaukselle")." $tunnus ".t("Osuus")." $posrow[osuus]%')";
		$addtil = mysql_query($query) or pupe_error($query);

	}
	else {
		while($row = mysql_fetch_array($sresult)) {

			$summa = round($row["summa"]/$sumrow["jaksotettavaa"] * $posrow["summa"],2);


			$query  = "insert into tilausrivi (hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti) values ('$summa', 'N', '1', '1', '$id', '$yhtiorow[ennakkomaksu_tuotenumero]', '$nimitys', '$kukarow[yhtio]', 'L', '$row[alv]', '".t("Ennakkolasku")." $lahteva_lasku ".t("tilaukselle")." $tunnus ".t("Osuus")." $posrow[osuus]%')";
			$addtil = mysql_query($query) or pupe_error($query);

			if ($debug==1) echo t("Lis‰ttiin ennakkolaskuun rivi")." $summa $row[alv] otunnus $id<br>";

			$tot += $summa;
		}

		echo "<font class = 'message'>".t("Tehtiin ennakkolasku tilaukselle")." $tunnus ".t("tunnus").": $id ".t("osuus").": ".($posrow["osuus"] * 100)."% ".t("summa").": $tot</font><br>";
	}

	// P‰ivitet‰‰n positiolle t‰m‰n laskun tunnus
	$query = "update maksupositio set uusiotunnus='$id' where tunnus='$posrow[tunnus]'";
	$result = mysql_query($query) or pupe_error($query);

	// haetaan laskun tiedot ja laitetaan tilaus valmis putkeen
	$query = "select * from lasku where yhtio = '$kukarow[yhtio]' and tunnus = $id";
	$result = mysql_query($query) or pupe_error($query);
	$laskurow = mysql_fetch_array($result);
	$kukarow["kesken"] = $laskurow["tunnus"];

	require("tilauskasittely/tilaus-valmis.inc");

	$tee = "";
}

if($tee == "loppulaskuta") {

	echo "<font class = 'message'>".t("Loppulaskutetaan tilaus")." $tunnus<br></font><br>";

	$query = "	SELECT nimitys
				FROM tuote
				WHERE yhtio = '$kukarow[yhtio]'
				and tuoteno = '$yhtiorow[ennakkomaksu_tuotenumero]'";
	$tresult = mysql_query($query) or pupe_error($query);
	$trow = mysql_fetch_array($tresult);
	$nimitys = $trow["nimitys"];

	//	Lasketaan paljonko ollaan jo laskutettu ja mill‰ verokannoilla
	$query = "	SELECT round(sum(rivihinta),2) laskutettu, tilausrivi.alv
				FROM lasku
				JOIN tilausrivi ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and kpl <> 0 and uusiotunnus > 0
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				and lasku.jaksotettu = '$tunnus'
				GROUP BY alv";
	$sresult = mysql_query($query) or pupe_error($query);

	while($row = mysql_fetch_array($sresult)) {
		$query  = "	insert into tilausrivi (hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti, keratty, kerattyaika, toimitettu, toimitettuaika)
					values  ('$row[laskutettu]', 'N', '-1', '-1', '$tunnus', '$yhtiorow[ennakkomaksu_tuotenumero]', '$nimitys', '$kukarow[yhtio]', 'L', '$row[alv]', '".t("Ennakkolaskutuksen hyvitys")."', '$kukarow[kuka]', now(), '$kukarow[kuka]', now())";
		$addtil = mysql_query($query) or pupe_error($query);

		if ($debug==1) echo t("Loppulaskuun lis‰ttiin ennakkolaskun hyvitys")." -$row[laskutettu] alv $row[alv]% otunnus $vimppa<br>";
	}

	// P‰ivitet‰‰n positiolle laskutustunnus
	$query = "update maksupositio set uusiotunnus='$tunnus' where tunnus = '$posrow[tunnus]'";
	$result = mysql_query($query) or pupe_error($query);

	// Alkuper‰inen tilaus menee laskutukseen
	$query = "	update lasku
				set maksuehto = '$posrow[maksuehto]', clearing = 'loppulasku', ketjutus = 'o', alatila = 'D'
				where yhtio = '$kukarow[yhtio]'
				and tunnus = '$tunnus'";
	$result = mysql_query($query) or pupe_error($query);

	$tee = "";
}

if ($tee == "sulje") {
	echo "ei osata viel, sorry";
	$tee = "";
}


if($tee=="") {

	echo "	<SCRIPT LANGUAGE=JAVASCRIPT>
				function verify(msg){
					return confirm(msg);
				}
			</SCRIPT>";

	$query = "	SELECT
				lasku.jaksotettu,
				min(lasku.tunnus) tilaus,
				group_concat(distinct concat_ws(' ',lasku.nimi, lasku.nimitark)) nimi,
				sum(if(maksupositio.uusiotunnus != '0', 1,0)) laskutettu_kpl,
				count(*) yhteensa_kpl,
				sum(if(maksupositio.uusiotunnus  = '0', maksupositio.summa,0)) laskuttamatta,
				sum(if(maksupositio.uusiotunnus != '0', maksupositio.summa,0)) laskutettu,
				sum(maksupositio.summa) yhteensa
				FROM lasku
				JOIN maksupositio ON maksupositio.yhtio = lasku.yhtio and maksupositio.otunnus = lasku.tunnus
				JOIN maksuehto ON maksuehto.yhtio = lasku.yhtio and maksuehto.tunnus = lasku.maksuehto and maksuehto.jaksotettu != ''
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				and lasku.jaksotettu > 0
				GROUP BY lasku.jaksotettu
				ORDER BY lasku.jaksotettu desc";
	$result = mysql_query($query) or pupe_error($query);

	echo "<table><tr>";

	echo "	<th>".t("Tilaus")."</th>
			<th>".t("Asiakas")."</th>
			<th>".t("Er‰")."</th>
			<th>".t("Laskuttamatta")."</th>
			<th>".t("Laskutettu")."</th>
			<th>".t("Yhteens‰")."</th>
			<th>".t("Seuraava positio")."</th>";
	echo "</tr>";

	while($row = mysql_fetch_array($result)) {

		// seuraava positio on t‰m‰ siis
		$query = "	SELECT maksupositio.*, maksuehto.teksti
					FROM maksupositio
					JOIN maksuehto on maksupositio.yhtio = maksupositio.yhtio and maksupositio.maksuehto = maksuehto.tunnus
					WHERE maksupositio.yhtio ='$kukarow[yhtio]'
					and otunnus = '$row[tilaus]'
					and uusiotunnus = 0
					ORDER BY maksupositio.tunnus
					LIMIT 1";
		$rahres = mysql_query($query) or pupe_error($query);
		$posrow = mysql_fetch_array($rahres);

		$query = "	SELECT *
					FROM lasku
					WHERE yhtio ='$kukarow[yhtio]'
					and tunnus = '$row[tilaus]'";
		$rahres = mysql_query($query) or pupe_error($query);
		$laskurow = mysql_fetch_array($rahres);

		echo "<tr>";
		echo "<td valign='top'>$row[tilaus]</td>";
		echo "<td valign='top'>$row[nimi]</td>";
		echo "<td valign='top'>$row[laskutettu_kpl] / $row[yhteensa_kpl]</td>";
		echo "	<td valign='top' align='right'>$row[laskuttamatta]</td>
				<td valign='top' align='right'>$row[laskutettu]</td>
				<td valign='top' align='right'>$row[yhteensa]</td>
				<td>
				<table>
				<tr><td>Osuus:</td><td>$posrow[osuus]%</td></tr>
				<tr><td>Summa:</td><td>$posrow[summa] $laskurow[valkoodi]</td></tr>
				<tr><td>Lis‰tiedot:</td><td>$posrow[lisatiedot]</td></tr>
				<tr><td>Ohje:</td><td>$posrow[ohje]</td></tr>
				</table>";

		// ennakkolaskutetaanko vaiko loppulaskutetaanko?
		// loppulaskutetaan
		if($row["yhteensa_kpl"] - $row["laskutettu_kpl"] == 1) {
			// tarkastetaan onko kaikki jo toimitettu ja t‰m‰ on good to go
			$query = "	SELECT lasku.tunnus,
						sum(if(tila='L' and alatila IN ('J','X'),1,0)) tilaok,
						sum(if(toimitettu='',1,0)) toimittamatta,
						count(*) toimituksia
						FROM lasku
						JOIN tilausrivi ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi != 'D' and tilausrivi.jaksotettu='$row[tilaus]'
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.jaksotettu = '$row[tilaus]'
						GROUP BY lasku.jaksotettu";
			$tarkres = mysql_query($query) or pupe_error($query);
			$tarkrow = mysql_fetch_array($tarkres);

			if($tarkrow["tilaok"] <> $tarkrow["toimituksia"] or $tarkrow["toimittamatta"] > 0) {
				echo "<td class='back'>Ei valmis</td>";
			}
			else {
				$msg = t("Oletko varma, ett‰ haluat LOPPULASKUTTAA tilauksen")." $row[tilaus]\\n\\nOsuus: $posrow[osuus]%\\nSumma: $posrow[summa] $laskurow[valkoodi]\\nMaksuehto: $posrow[teksti]";

				echo "	<form method='post' action='$PHP_SELF' onSubmit='return verify(\"$msg\");'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='tunnus' value='$row[tilaus]'>
						<input type='hidden' name='tee' value='loppulaskuta'>
						<td class='back'><input type='submit' name = 'submit' value='".t("Laskuta")."'></td>
						</form>";
			}
			echo "</tr>";

		}
		// suljetaan projektia
		elseif($row["yhteensa_kpl"] - $row["laskutettu_kpl"] == 0) {
			// tarkastetaan onko kaikki jo toimitettu
			$msg = t("Oletko varma, ett‰ haluat sulkea projektin")." $row[tunnus]\\n";

			echo "<td class='back'>
					<form method='post' action='$PHP_SELF' onSubmit='return verify(\"$msg\");'>
					<input type='hidden' name = 'toim' value='$toim'>
					<input type='hidden' name = 'tunnus' value='$row[tilaus]'>
					<input type='hidden' name = 'tee' value='sulje'>
					<input type='submit' name = 'submit' value='".t("Sulje projekti")."'>
					</form></td>";

			echo "</tr>";

		}
		// muuten t‰m‰ taitaa olla vain ennakkolaskutusta
		else {
			$msg = t("Oletko varma, ett‰ haluat tehd‰ ennakkolaskun tilaukselle").": $row[tilaus]\\n\\nOsuus: $posrow[osuus]%\\nSumma: $posrow[summa] $laskurow[valkoodi]\\nMaksuehto: $posrow[teksti]";

			echo "<td class='back'><form method='post' name='case' action='$PHP_SELF' enctype='multipart/form-data'  autocomplete='off' onSubmit = 'return verify(\"$msg\");'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='tunnus' value='$row[tilaus]'>
					<input type='hidden' name='tee' value='ennakkolaskuta'>
					<input type='submit' name = 'submit' value='".t("Laskuta")."'>
					</form></td>";

			echo "</tr>";
		}
	}

	echo "</table>";

}

?>