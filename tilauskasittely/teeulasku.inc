<?php

// tarvitaan $tunnukset muuttuja, jossa on kaikki laskuun kuuluvat tilaukset mysql muodossa: 'nnn','nnn','nnn'
// palautetaan $laskurow array jossa on uuden U-laskun tiedot

$tulos_ulos_ulasku = "";

// lasketaan laskulle loppusumma.. ja otetaan kaikki mahdolliset eriävät tiedot talteen
$arvo			= 0;
$summa			= 0;
$kate			= 0;
$bruttopaino	= 0;
$viestit		= "";
$comments		= "";
$sisviestit1	= "";
$viitetxt		= "";

$query  = "	select sum(arvo) arvo, sum(summa) summa, sum(kate) kate, sum(bruttopaino) bruttopaino,
			group_concat(distinct trim(comments) SEPARATOR ' / ') comments,
			group_concat(distinct trim(sisviesti1) SEPARATOR ' / ') sisviestit1,
			group_concat(distinct trim(viesti) SEPARATOR ' / ') viestit,
			group_concat(distinct trim(viitetxt) SEPARATOR ' / ') viitetxt
			from lasku
			where yhtio='$kukarow[yhtio]'
			and tunnus in ($tunnukset)";
$uusiuresult = mysql_query($query) or pupe_error($query);

if (mysql_num_rows($uusiuresult) > 0) {
	$sumrow = mysql_fetch_array($uusiuresult);

	$arvo			= round($sumrow["arvo"], 2);
	$summa			= round($sumrow["summa"], 2);
	$kate			= round($sumrow["kate"], 2);
	$bruttopaino	= round($sumrow["bruttopaino"], 2);

	$viestit		= $sumrow["viestit"];
	$comments		= $sumrow["comments"];
	$sisviestit1	= $sumrow["sisviestit1"];
	$viitetxt		= $sumrow["viitetxt"];

	//Onko tämä mysql-bugi vai group_concatin ominaisuus?
	if ($viestit == " / ") 		$viestit = "";
	if ($comments == " / ") 	$comments = "";
	if ($sisviestit1 == " / ") 	$sisviestit1 = "";
	if ($viitetxt == " / ") 	$viitetxt = "";
}

// haetaan laskulle otsikkotiedot ketjun ekalta laskulta
$query  = "	select *
			from lasku
			where yhtio = '$kukarow[yhtio]'
			and tunnus in ($tunnukset)
			order by tunnus
			limit 1";
$uusiuresult = mysql_query($query) or pupe_error($query);
$lasrow = mysql_fetch_array($uusiuresult);

$query  = "select distinct toim_nimi, toim_nimitark, toim_osoite, toim_postino, toim_postitp, toim_maa from lasku where yhtio='$kukarow[yhtio]' and tunnus in ($tunnukset)";
$uusiuresult = mysql_query($query) or pupe_error($query);

// jos meillä on useita toimitusosoitteita, niin ei kirjoiteta vain ekaa siihen otsikolle. se on sekavaa.. kijotetaan vain "useita"
if (mysql_num_rows($uusiuresult) != 1) {
	$lasrow["toim_nimi"]     = t("Useita");
	$lasrow["toim_nimitark"] = "";
	$lasrow["toim_osoite"]   = "";
	$lasrow["toim_postino"]  = "";
	$lasrow["toim_postitp"]  = "";
	$lasrow["toim_maa"]      = "";
}

// haetaan maksuehdon tiedot, jotta osataan tiliöidä käteismyynti kassaan
$query = "	select *
			from maksuehto
			where yhtio = '$kukarow[yhtio]'
			and tunnus  = '$lasrow[maksuehto]'";
$xresult = mysql_query($query) or pupe_error($query);

if (mysql_num_rows($xresult) == 0) {
	$mehtorow = array();

	if ($lasrow["erpcm"] == "0000-00-00") {
		$tulos_ulos_ulasku .= "<font class='message'><br>".t("Maksuehtoa")." $lasrow[maksuehto] ".t("ei löydy")."! Tunnus $lasrow[tunnus] ".t("Laskunro")." $lasrow[laskunro] ".t("meni nyt pahasti vituiks").".</font><br><br>\r\n";
	}
}
else {
	$mehtorow = mysql_fetch_array($xresult);
}

// jos kyseessä on käteislasku, laitetaan maksaja ja maksupäivä mukaan
$maksupaiva = "''";
$maksaja    = "''";

if ($mehtorow['kateinen']!='') {
	$maksaja    = "'$kukarow[kuka]'";
	$maksupaiva = "now()";
}

///* Lasketaan Kassa-alennus *///
$kassaale = round($summa*$mehtorow['kassa_alepros']/100,2);


$lisa_comments = "";

//Laitetaan laskun kommenttiin talteen tiettyjä lisätietoja
if ($mehtorow["factoring"] != '') {
	$lisa_comments .= "Vår fordran enligt denna faktura har överlåtits till S E B Finans AB (publ). \n";
	$lisa_comments .= "Fakturan skall därför betalas direkt till S E B Finans AB(publ), 167 81  BROMMA. \n";
}

if ($lasrow["vienti"] == 'E') {
	$lisa_comments .= "VAT 0% Intra community supply. \n";
}
if ($lasrow["vienti"] == 'K') {
	$lisa_comments .= "VAT 0% Export of goods outside the European Union. \n";
}

if ($lasrow["vienti"] != '') {
	//Laitetaan kollimäärät ja kilot mukaan kommenttiin vientilaskuille
	$query = "	SELECT pakkaus, pakkauskuvaus, sum(kilot) kilot, sum(kollit) kollit
				FROM rahtikirjat
				WHERE otsikkonro in ($tunnukset)
				and yhtio='$kukarow[yhtio]'
				group by pakkaus, pakkauskuvaus
				having kollit > 0";
	$rahtiresult = mysql_query($query) or pupe_error($query);

	while($rahtirow = mysql_fetch_array($rahtiresult)) {

		if($rahtirow["pakkaus"] != $rahtirow["pakkauskuvaus"]) {
			$rahtirow["pakkaus"] .= " ".$rahtirow["pakkauskuvaus"];
		}

		$lisa_comments .= "$rahtirow[kollit] ".t("kpl").". $rahtirow[pakkaus] $rahtirow[kilot] kg. \n";
	}
}
// Lisätään järjestelmän generoimat kommentit laskun kommenttikenttään
if ($lisa_comments != "") {
	$sisviestit1 = $lisa_comments." ".$sisviestit1;
}

//Siivotaan kommenteista kaikki kummalliset merkit pois
$comments 		= ereg_replace("[^A-Za-z0-9ÖöÄäÅå .,-/|%\r\n]", "", $comments);
$viestit		= ereg_replace("[^A-Za-z0-9ÖöÄäÅå .,-/|%\r\n]", "", $viestit);
$sisviestit1	= ereg_replace("[^A-Za-z0-9ÖöÄäÅå .,-/|%\r\n]", "", $sisviestit1);
$viitetxt		= ereg_replace("[^A-Za-z0-9ÖöÄäÅå .,-/|%\r\n]", "", $viitetxt);


// kirjotetaan kokonaan uusi lasku-olio laskusta.. helpottaa myyntireskontraa! tilaksi: U alatila: tyhjä
$query = "INSERT into lasku SET
		aktiivinen_kuljetus 				= '$lasrow[aktiivinen_kuljetus]',
		aktiivinen_kuljetus_kansallisuus	= '$lasrow[aktiivinen_kuljetus_kansallisuus]',
		alv									= '$lasrow[alv]',
		arvo								= '$arvo',
		bruttopaino							= '$bruttopaino',
		chn									= '$lasrow[chn]',
		comments							= '$comments',
		erikoisale							= '$lasrow[erikoisale]',
		erpcm								= '$lasrow[erpcm]',
		kapvm								= '$lasrow[kapvm]',
		kasumma								= '$kassaale',
		kate								= '$kate',
		kauppatapahtuman_luonne			 	= '$lasrow[kauppatapahtuman_luonne]',
		kerayspvm							= '$lasrow[kerayspvm]',
		kontti								= '$lasrow[kontti]',
		kuljetusmuoto						= '$lasrow[kuljetusmuoto]',
		laatija								= '$lasrow[laatija]',
		laskunro							= '$lasrow[laskunro]',
		laskutettu							=  now(),
		laskuttaja							= '$kukarow[kuka]',
		laskutusvkopv						= '$lasrow[laskutusvkopv]',
		lisattava_era 						= '$lasrow[lisattava_era]',
		luontiaika							= '$lasrow[luontiaika]',
		maa 								= '$lasrow[maa]',
		maa_lahetys							= '$lasrow[maa_lahetys]',
		maa_maara							= '$lasrow[maa_maara]',
		maksaja								=  $maksaja,
		maksuehto							= '$lasrow[maksuehto]',
		mapvm								=  $maksupaiva,
		myyja								= '$lasrow[myyja]',
		nimi								= '$lasrow[nimi]',
		nimitark							= '$lasrow[nimitark]',
		osoite								= '$lasrow[osoite]',
		ovttunnus							= '$lasrow[ovttunnus]',
		poistumistoimipaikka 				= '$lasrow[poistumistoimipaikka]',
		poistumistoimipaikka_koodi 			= '$lasrow[poistumistoimipaikka_koodi]',
		postino								= '$lasrow[postino]',
		postitp								= '$lasrow[postitp]',
		sisainen							= '$lasrow[sisainen]',
		sisamaan_kuljetus 					= '$lasrow[sisamaan_kuljetus]',
		sisamaan_kuljetus_kansallisuus		= '$lasrow[sisamaan_kuljetus_kansallisuus]',
		sisamaan_kuljetusmuoto 				= '$lasrow[sisamaan_kuljetusmuoto]',
		summa								= '$summa',
		tapvm								= '$lasrow[tapvm]',
		tilausvahvistus						= '$lasrow[tilausvahvistus]',
		toim_maa							= '$lasrow[toim_maa]',
		toim_nimi							= '$lasrow[toim_nimi]',
		toim_nimitark						= '$lasrow[toim_nimitark]',
		toim_osoite							= '$lasrow[toim_osoite]',
		toim_ovttunnus						= '$lasrow[toim_ovttunnus]',
		toim_postino						= '$lasrow[toim_postino]',
		toim_postitp						= '$lasrow[toim_postitp]',
		toimaika							= '$lasrow[toimaika]',
		toimitusehto						= '$lasrow[toimitusehto]',
		kohdistettu							= '$lasrow[kohdistettu]',
		toimitustapa						= '$lasrow[toimitustapa]',
		tullausnumero						= '$lasrow[tullausnumero]',
		vahennettava_era					= '$lasrow[vahennettava_era]',
		valkoodi							= '$lasrow[valkoodi]',
		verkkotunnus						= '$lasrow[verkkotunnus]',
		vienti 								= '$lasrow[vienti]',
		viesti								= '$viestit',
		viikorkopros						= '$lasrow[viikorkopros]',
		viite								= '$lasrow[viite]',
		yhtio								= '$lasrow[yhtio]',
		ytunnus								= '$lasrow[ytunnus]',
		liitostunnus						= '$lasrow[liitostunnus]',
		viitetxt							= '$viitetxt',
		sisviesti1							= '$sisviestit1',
		alatila								= '',
		tila								= 'U'";

$uusiotsikko = mysql_query($query) or pupe_error($query);

// otetaan uuden otsikon tunnus talteen
$uusiotunnus = (string) mysql_insert_id();

// haetaan nyt varmuuden vuoksi vielä insertoidut tiedot takaisin..
$query = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$uusiotunnus'";
$uusiuresult = mysql_query($query) or pupe_error($query);
if (mysql_num_rows($uusiuresult)==0) die ("".t("Lasku")." $uusiotunnus ".t("katosi")."!");
$laskurow = mysql_fetch_array($uusiuresult);

// päivitetään tilausriveille uuden otsikon tiedot (ei päivitetä Puute ja Jt rivejä laskutetuiksi)
$query = "UPDATE tilausrivi SET uusiotunnus='$laskurow[tunnus]' WHERE otunnus in ($tunnukset) and yhtio='$kukarow[yhtio]' and var not in ('P','J')";
$ulasr = mysql_query($query) or pupe_error($query);



//laitetaan välisummat rivin kommenttiin aina kun otunnus vaihtuu
$query = "	select otunnus, tunnus from tilausrivi where yhtio='$kukarow[yhtio]' and otunnus in ($tunnukset)
			order by otunnus, hyllyalue, hyllynro, hyllyvali, hyllytaso, tuoteno, tunnus";
$valsures = mysql_query($query) or pupe_error($query);

$edotunnus = '';
$edtunnus  = '';

while ($valsurow = mysql_fetch_array($valsures)) {

	if ($valsurow["otunnus"] != $edotunnus and $edotunnus != '') {

		$query 		= "SELECT summa, valkoodi, nimi, toim_nimi, toim_osoite FROM lasku WHERE yhtio='$kukarow[yhtio]' and tunnus='$edotunnus'";
		$sumsresult = mysql_query($query) or pupe_error($query);
		$sumsrow	= mysql_fetch_array($sumsresult);

		// jos meillä on eri toimitusosoite kun laskutusosoite, kirjotetaan toimitusosoitteen asiakasnimi yhteensäriville kommenttiin...
		if (strtoupper($sumsrow["nimi"]) != strtoupper($sumsrow["toim_nimi"]) and $sumsrow["toim_nimi"] != "") {
			$sumskommentti = "$sumsrow[toim_nimi] $sumsrow[toim_osoite] ".t("yhteensä");
		}
		else {
			$sumskommentti = t("Tilaus")." $edotunnus ".t("yhteensä");
		}

		//jos käytetään pelkistettyä lasku niin ei haluta näitä kommentteja
		if ($yhtiorow['laskutyyppi']!= 1) {
			$query   = "UPDATE tilausrivi SET kommentti = concat(kommentti, ' ', '$sumskommentti: $sumsrow[summa] $sumsrow[valkoodi].') WHERE tunnus='$edtunnus' and yhtio='$kukarow[yhtio]'";
			$paivres = mysql_query($query) or pupe_error($query);
		}
	}

	$edotunnus = $valsurow["otunnus"];
	$edtunnus  = $valsurow["tunnus"];
}

if ($edtunnus != "") {
	//laitetaan välisumma vikallekin tilaukselle
	$query 		= "SELECT summa, valkoodi, nimi, toim_nimi, toim_osoite FROM lasku WHERE yhtio='$kukarow[yhtio]' and tunnus='$edotunnus'";
	$sumsresult = mysql_query($query) or pupe_error($query);
	$sumsrow	= mysql_fetch_array($sumsresult);


	// jos meillä on eri toimitusosoite kun laskutusosoite, kirjotetaan toimitusosoitteen asiakasnimi yhteensäriville kommenttiin...
	if (strtoupper($sumsrow["nimi"]) != strtoupper($sumsrow["toim_nimi"]) and $sumsrow["toim_nimi"] != "") {
		$sumskommentti = "$sumsrow[toim_nimi] $sumsrow[toim_osoite] ".t("yhteensä");
	}
	else {
		$sumskommentti = t("Tilaus")." $edotunnus ".t("yhteensä");
	}

	//jos käytetään pelkistettyä lasku niin ei haluta näitä kommentteja
	if ($yhtiorow['laskutyyppi']!= 1) {
		$query   = "UPDATE tilausrivi SET kommentti = concat(kommentti, ' ', '$sumskommentti: $sumsrow[summa] $sumsrow[valkoodi].') WHERE tunnus='$edtunnus' and yhtio='$kukarow[yhtio]'";
		$paivres = mysql_query($query) or pupe_error($query);
	}
}




// tehdään laskun tiliöinnit.. tarvitaan $lasku array
$lasku = $laskurow;

//haetaan asiakkaan tiedot (esim konserniyhtiö)
$query = "	SELECT konserniyhtio
			FROM asiakas
			WHERE yhtio='$kukarow[yhtio]' and ytunnus='$laskurow[ytunnus]'";
$konsres = mysql_query($query) or pupe_error($query);
$konsrow = mysql_fetch_array($konsres);

require('teelaskuntiliointi.inc');

//echotaan rudulle jos kyseessä ei ole batch-ajo
if ($tulos_ulos == "" and $silent == "") {
	echo $tulos_ulos_ulasku;
}

?>