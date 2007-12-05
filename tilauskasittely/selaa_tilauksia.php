<?php

	// käytetään slavea
	$useslave = 1;

	require ("../inc/parametrit.inc");

	if ($toim == "") $toim = "MYYNTI";

	echo "<font class='head'>".ucfirst(strtolower($toim)).t("selaus").":<hr></font>";

	// mennää defaulttina aina päivänäkymään
	if ($tee == "") {
		$tee = "paiva";
	}
	
	if ($tilhaku != '' and $toim == "KEIKKA" and $tee != 'tilaus') {
		$tee = "tilhaku";
	}
	
	if ($tee == "paiva" and !isset($vv) and !isset($kk) and !isset($pp)) {
		$vv = date("Y");
		$kk = date("m");
		$pp = date("d");
	}

	if ($tee == "kk" and !isset($vv) and !isset($kk)) {
		$vv = date("Y");
		$kk = date("m");
		$pp = 1;
	}

	// edellinen ja seuraava kuukausi
	$ekv = date("Y",mktime(0, 0, 0, $kk-1, 1, $vv));
	$ekk = date("m",mktime(0, 0, 0, $kk-1, 1, $vv));
	$ekp = date("d",mktime(0, 0, 0, $kk-1, 1, $vv));
	$nkv = date("Y",mktime(0, 0, 0, $kk+1, 1, $vv));
	$nkk = date("m",mktime(0, 0, 0, $kk+1, 1, $vv));
	$nkp = date("d",mktime(0, 0, 0, $kk+1, 1, $vv));

	// edellinen ja seuraava päivä
	$epv = date("Y",mktime(0, 0, 0, $kk, $pp-1, $vv));
	$epk = date("m",mktime(0, 0, 0, $kk, $pp-1, $vv));
	$epp = date("d",mktime(0, 0, 0, $kk, $pp-1, $vv));
	$npv = date("Y",mktime(0, 0, 0, $kk, $pp+1, $vv));
	$npk = date("m",mktime(0, 0, 0, $kk, $pp+1, $vv));
	$npp = date("d",mktime(0, 0, 0, $kk, $pp+1, $vv));

	$etsi = '';

	if (is_string($haku)) {		
		$etsi = "and (lasku.nimi LIKE '%$haku%' or lasku.toim_nimi LIKE '%$haku%') ";
	}
	
	if (is_numeric($haku)) {
		$etsi = "and lasku.ytunnus LIKE '%$haku%' ";
	}
	
	if ($keikkanrohaku != '' and $toim == 'KEIKKA' and is_numeric($keikkanrohaku)) {
		$etsi = "and lasku.laskunro = $keikkanrohaku ";
	}

	// tässä myyntitilausten queryt
	if ($toim == "MYYNTI") {
		// kuukausinäkymä
		$query1 = "	SELECT DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, DATE_FORMAT(luontiaika,'%a') vkpvm,
					count(distinct lasku.tunnus) tilauksia,
					count(distinct tilausrivi.tunnus) riveja,					
					round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
					round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo
					FROM lasku use index (yhtio_tila_luontiaika)
					JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tyyppi!='D')
					WHERE lasku.yhtio = '$kukarow[yhtio]' and
					tila in ('L') and
					luontiaika >= '$vv-$kk-01 00:00:00' and
					luontiaika < '$nkv-$nkk-01 00:00:00'
					$etsi
					GROUP BY pvm
					ORDER BY luontiaika";

		// päivänäkymä
		$query2 = "	SELECT lasku.tunnus, if(lasku.nimi!=lasku.toim_nimi, concat_ws(' / ', lasku.nimi, lasku.toim_nimi),lasku.nimi) nimi, DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, DATE_FORMAT(luontiaika,'%a') vkpvm,
					round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
					round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo
					FROM lasku use index (yhtio_tila_luontiaika)
					JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tyyppi!='D')
					WHERE lasku.yhtio = '$kukarow[yhtio]' and
					tila in ('L') and 
					luontiaika >= '$vv-$kk-$pp 00:00:00' and 
					luontiaika <= '$vv-$kk-$pp 23:59:59'
					$etsi
					GROUP BY lasku.tunnus
					ORDER BY luontiaika";

		// tilausnäkymä
		$query3 = "	SELECT otunnus tunnus, DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, tuoteno, concat(nimitys, if(kommentti!='', concat('<br>* ',kommentti),'')) nimitys, kpl+varattu kpl, tilausrivi.hinta, ale, erikoisale, tilausrivi.alv,
					round(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)),'$yhtiorow[hintapyoristys]') summa,
					round(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)),'$yhtiorow[hintapyoristys]') arvo
					FROM tilausrivi use index (yhtio_otunnus)
					JOIN lasku use index (PRIMARY) on (lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus)
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]' and
					tyyppi != 'D' and
					otunnus = '$tunnus'
					ORDER BY tilausrivi.tunnus";
	}

	// tässä ostotilausten queryt
	if ($toim == "OSTO") {
		// kuukausinäkymä
		$query1 = "	SELECT DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, DATE_FORMAT(luontiaika,'%a') vkpvm,
					count(distinct lasku.tunnus) tilauksia, count(distinct tilausrivi.tunnus) riveja
					FROM lasku use index (yhtio_tila_luontiaika)
					JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tyyppi!='D')
					WHERE lasku.yhtio = '$kukarow[yhtio]' and
					tila in ('O') and
					luontiaika >= '$vv-$kk-01 00:00:00' and
					luontiaika < '$nkv-$nkk-01 00:00:00'
					$etsi
					GROUP BY pvm
					ORDER BY luontiaika";

		// päivänäkymä
		$query2 = "	SELECT lasku.tunnus, lasku.nimi, DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, DATE_FORMAT(luontiaika,'%a') vkpvm,
					round(tilausrivi.hinta*(1-(tilausrivi.ale/100))*(1-(lasku.erikoisale/100))*(tilausrivi.varattu+tilausrivi.kpl),2) summa, lasku.valkoodi
					FROM lasku use index (yhtio_tila_luontiaika)
					JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tyyppi!='D')
					WHERE lasku.yhtio = '$kukarow[yhtio]' and
					tila in ('O') and
					luontiaika >= '$vv-$kk-$pp 00:00:00' and
					luontiaika <= '$vv-$kk-$pp 23:59:59'
					$etsi
					GROUP BY lasku.tunnus
					ORDER BY luontiaika";

		// tilausnäkymä
		$query3 = "	SELECT otunnus, DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, tuoteno, nimitys, kpl+varattu kpl, tilausrivi.hinta,
					round(tilausrivi.hinta*(1-(tilausrivi.ale/100))*(1-(lasku.erikoisale/100))*(tilausrivi.varattu+tilausrivi.kpl),'$yhtiorow[hintapyoristys]') arvo, lasku.valkoodi
					FROM tilausrivi use index (yhtio_otunnus)
					JOIN lasku use index (PRIMARY) on (lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus)
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]' and
					otunnus = '$tunnus' and
					tyyppi!='D'
					ORDER BY tilausrivi.tunnus";
	}

	// tässä keikkojen queryt
	if ($toim == "KEIKKA") {
		// kuukausinäkymä
		$query1 = "	SELECT DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, DATE_FORMAT(luontiaika,'%a') vkpvm,
					count(distinct lasku.tunnus) keikkoja, count(distinct tilausrivi.tunnus) riveja
					FROM lasku use index (yhtio_tila_luontiaika)
					JOIN tilausrivi use index (uusiotunnus_index) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.uusiotunnus=lasku.tunnus and tyyppi!='D')
					WHERE lasku.yhtio = '$kukarow[yhtio]' and
					tila in ('K') and
					vanhatunnus = 0 and
					luontiaika >= '$vv-$kk-01 00:00:00' and
					luontiaika < '$nkv-$nkk-01 00:00:00'
					$etsi
					GROUP BY pvm
					ORDER BY luontiaika";

		// päivänäkymä
		$query2 = "	SELECT lasku.laskunro keikka, lasku.tunnus, lasku.nimi, DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, if(mapvm='0000-00-00','',DATE_FORMAT(mapvm,'%d.%m.%Y')) jlaskenta,
					round(sum(tilausrivi.hinta*(1-(tilausrivi.ale/100))*(1-(lasku.erikoisale/100))*(tilausrivi.varattu+tilausrivi.kpl)),2) summa, lasku.valkoodi
					FROM lasku use index (yhtio_tila_luontiaika)
					JOIN tilausrivi use index (uusiotunnus_index) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.uusiotunnus=lasku.tunnus and tyyppi!='D')
					WHERE lasku.yhtio = '$kukarow[yhtio]' and
					tila in ('K') and
					vanhatunnus = 0 ";
					if (!isset($keikkanrohaku)) {
						$query2 .= " and luontiaika >= '$vv-$kk-$pp 00:00:00' and luontiaika <= '$vv-$kk-$pp 23:59:59' ";
					}
		$query2 .= "$etsi
					GROUP BY lasku.tunnus
					ORDER BY lasku.laskunro";

		// tilausnäkymä
		$query3 = "	SELECT lasku.laskunro keikka, DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, tuoteno, nimitys, kpl+varattu kpl, tilausrivi.hinta,
					round(tilausrivi.hinta*(1-(tilausrivi.ale/100))*(1-(lasku.erikoisale/100))*(tilausrivi.varattu+tilausrivi.kpl),'$yhtiorow[hintapyoristys]') arvo, lasku.valkoodi
					FROM tilausrivi use index (uusiotunnus_index)
					JOIN lasku use index (PRIMARY) on (lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.uusiotunnus)
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]' and
					uusiotunnus = '$tunnus' and
					tyyppi!='D'
					ORDER BY tilausrivi.tunnus";
		
		// tilausnumerohaku
		$query4 = "	SELECT lasku.laskunro keikka, lasku.tunnus, lasku.nimi, DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, if(mapvm='0000-00-00','',DATE_FORMAT(mapvm,'%d.%m.%Y')) jlaskenta,
					round(sum(tilausrivi.hinta*(1-(tilausrivi.ale/100))*(1-(lasku.erikoisale/100))*(tilausrivi.varattu+tilausrivi.kpl)),2) summa, lasku.valkoodi
					FROM tilausrivi use index (yhtio_otunnus)
					JOIN lasku ON tilausrivi.yhtio=lasku.yhtio and tilausrivi.uusiotunnus=lasku.tunnus and tila = 'K' and vanhatunnus = 0
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and otunnus = '$tilhaku'
					and tyyppi!='D'
					GROUP BY lasku.tunnus
					ORDER BY luontiaika";
	}

	// tässä valmistusten queryt
	if ($toim == "VALMISTUS") {
		// kuukausinäkymä
		$query1 = "	SELECT DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, DATE_FORMAT(luontiaika,'%a') vkpvm,
					count(distinct lasku.tunnus) valmistuksia,
					count(distinct tilausrivi.tunnus) riveja,
					round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
					round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo
					FROM lasku use index (yhtio_tila_luontiaika)
					JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tyyppi!='D')
					WHERE lasku.yhtio = '$kukarow[yhtio]' and
					tila in ('V') and
					luontiaika >= '$vv-$kk-01 00:00:00' and
					luontiaika < '$nkv-$nkk-01 00:00:00'
					$etsi
					GROUP BY pvm
					ORDER BY luontiaika";

		// päivänäkymä
		$query2 = "	SELECT lasku.tunnus valmistus, lasku.tunnus, lasku.nimi, DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, DATE_FORMAT(luontiaika,'%a') vkpvm,
					round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
					round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo
					FROM lasku use index (yhtio_tila_luontiaika)
					JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tyyppi!='D')
					WHERE lasku.yhtio = '$kukarow[yhtio]' and
					tila in ('V') and
					luontiaika >= '$vv-$kk-$pp 00:00:00' and
					luontiaika <= '$vv-$kk-$pp 23:59:59'
					$etsi
					GROUP BY lasku.tunnus
					ORDER BY luontiaika";

		// tilausnäkymä
		$query3 = "	SELECT lasku.tunnus valmistus, DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, tuoteno, nimitys, kpl+varattu kpl, tilausrivi.hinta,
					round(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)),'$yhtiorow[hintapyoristys]') summa,
					round(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)),'$yhtiorow[hintapyoristys]') arvo
					FROM tilausrivi use index (yhtio_otunnus)
					JOIN lasku use index (PRIMARY) on (lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus)
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]' and
					otunnus = '$tunnus'
					ORDER BY perheid desc, tyyppi in ('W','L','D','V'), tilausrivi.tunnus";
	}

	// tässä tarjousten queryt
	if ($toim == "TARJOUS") {
		// kuukausinäkymä
		$query1 = "	SELECT DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, DATE_FORMAT(luontiaika,'%a') vkpvm,
					count(distinct lasku.tunnus) tilauksia,
					count(distinct tilausrivi.tunnus) riveja,
					round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
					round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo
					FROM lasku use index (yhtio_tila_luontiaika)
					JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tyyppi!='D')
					WHERE lasku.yhtio = '$kukarow[yhtio]' and
					tila in ('T') and
					luontiaika >= '$vv-$kk-01 00:00:00' and
					luontiaika < '$nkv-$nkk-01 00:00:00'
					$etsi
					GROUP BY pvm
					ORDER BY luontiaika";

		// päivänäkymä
		$query2 = "	SELECT lasku.tunnus, if(lasku.nimi!=lasku.toim_nimi, concat_ws(' / ', lasku.nimi, lasku.toim_nimi),lasku.nimi) nimi, DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, DATE_FORMAT(luontiaika,'%a') vkpvm,
					round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
					round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo
					FROM lasku use index (yhtio_tila_luontiaika)
					JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tyyppi!='D')
					WHERE lasku.yhtio = '$kukarow[yhtio]' and
					tila in ('T') and
					luontiaika >= '$vv-$kk-$pp 00:00:00' and
					luontiaika <= '$vv-$kk-$pp 23:59:59'
					$etsi
					GROUP BY lasku.tunnus
					ORDER BY luontiaika";

		// tilausnäkymä
		$query3 = "	SELECT otunnus, DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, tuoteno, nimitys, kpl+varattu kpl, tilausrivi.hinta, ale, erikoisale, tilausrivi.alv,
					round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),'$yhtiorow[hintapyoristys]') summa,
					round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),'$yhtiorow[hintapyoristys]') arvo
					FROM tilausrivi use index (yhtio_otunnus)
					JOIN lasku use index (PRIMARY) on (lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus)
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]' and
					tyyppi != 'D' and
					otunnus = '$tunnus'
					ORDER BY tilausrivi.tunnus";
	}

	if ($tee == "paiva") {
		$result = mysql_query($query2) or pupe_error($query2);
		echo "<a href='$PHP_SELF?toim=$toim&tee=paiva&vv=$epv&kk=$epk&pp=$epp&haku=$haku&keikkanrohaku=$keikkanrohaku'>".t("Edellinen päivä")."</a> - <a href='$PHP_SELF?toim=$toim&tee=paiva&vv=$npv&kk=$npk&pp=$npp&haku=$haku&keikkanrohaku=$keikkanrohaku'>".t("Seuraava päivä")."</a>";
		echo " - <a href='$PHP_SELF?toim=$toim&tee=kk&vv=$vv&kk=$kk&haku=$haku&keikkanrohaku=$keikkanrohaku'>".t("Kuukausinäkymä")."</a>";
		echo "<br><br>";
		//echo "$query2<br><br>";
	}
	elseif ($tee == "kk") {
		$result = mysql_query($query1) or pupe_error($query1);
		echo "<a href='$PHP_SELF?toim=$toim&tee=kk&vv=$ekv&kk=$ekk&pp=$ekp&haku=$haku&keikkanrohaku=$keikkanrohaku'>".t("Edellinen kuukausi")."</a> - <a href='$PHP_SELF?toim=$toim&tee=kk&vv=$nkv&kk=$nkk&pp=$nkp&haku=$haku&keikkanrohaku=$keikkanrohaku'>".t("Seuraava kuukausi")."</a>";
		echo "<br><br>";
		//echo "$query1<br><br>";
	}
	elseif ($tee == "tilaus") {
		$result = mysql_query($query3) or pupe_error($query3);
		echo "<a href='$PHP_SELF?toim=$toim&tee=paiva&vv=$vv&kk=$kk&pp=$pp'>".t("Päivänäkymä")."</a> - <a href='$PHP_SELF?toim=$toim&tee=kk&vv=$vv&kk=$kk'>".t("Kuukausinäkymä")."</a>";
		echo "<br><br>";
		//echo "$query3<br><br>";
	}
	elseif ($tee == 'tilhaku' and $toim == 'KEIKKA') {
		$result = mysql_query($query4) or pupe_error($query4);
		echo "<a href='$PHP_SELF?toim=$toim&tee=paiva&vv=$vv&kk=$kk&pp=$pp'>".t("Päivänäkymä")."</a> - <a href='$PHP_SELF?toim=$toim&tee=kk&vv=$vv&kk=$kk'>".t("Kuukausinäkymä")."</a>";
		echo "<br><br>";
		//echo "$query4<br><br>";
	}
	else {
		echo "Kaboom!";
		unset($result);
	}

	if ($tee == "paiva" or $tee == "kk") {
		echo "<form method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' name='tee' value='$tee'>";
		echo "<input type='hidden' name='pp' value='$pp'>";
		echo "<input type='hidden' name='kk' value='$kk'>";
		echo "<input type='hidden' name='vv' value='$vv'>";

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Hae asiakkaan nimellä tai numerolla").":</th>";
		echo "<td><input type='text' name='haku' value='$haku'></td>";
		if ($toim == "KEIKKA") {
			echo "</tr>";
			echo "<tr><th>".t("Hae keikkanumerolla").":</th>";
			echo "<td><input type='text' name='keikkanrohaku' value='$keikkanrohaku'></td>";
			echo "</tr><tr>";
			echo "<th>".t("Hae tilausnumerolla").":</th>";
			echo "<td><input type='text' name='tilhaku' value='$tilhaku'></td>";
		}
		echo "<td class='back'><input type='submit' value='".t("Hae")."'></td>";
		echo "</tr>";
		echo "</table>";

		echo "</form>";
	}

	if (mysql_num_rows($result) > 0) {

		echo "<table>";

		echo "<tr>";

		for ($i = 0; $i < mysql_num_fields($result); $i++) {
			echo "<th>".mysql_field_name($result, $i)."</th>";
		}

		echo "</tr>";

		$arvo    = 0;
		$summa   = 0;
		$teemita = "";

		if ($tee == "kk") {
			$teemita = "paiva";
		}

		if ($tee == "paiva") {
			$teemita = "tilaus";
		}
		
		if ($tee == 'tilhaku') {
			$teemita = "tilaus";
		}

		// katotaan löytyykö oikeuksia vaihda_tilaan... tätä käytetään tuolla whilen sisällä
		$oikeuquery = "select * from oikeu where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]' and nimi like '%vaihda_tila.php'";
		$apuoikeures = mysql_query($oikeuquery) or pupe_error($oikeuquery);

		while ($row = mysql_fetch_array($result)) {

			list ($pp,$kk,$vv) = explode(".", $row["pvm"],3);

			echo "<tr>";

			for ($i = 0; $i < mysql_num_fields($result); $i++) {
				
				if (is_numeric($row[$i]) and (mysql_field_type($result,$i) == 'real' or mysql_field_type($result,$i) == 'int')) {
					echo "<td align='right'>$row[$i]</td>";
				}
				else {
					echo "<td>$row[$i]</td>";
				}
			}

			$arvo  += $row["arvo"];
			$summa += $row["summa"];

			// jos ollaan muussa tilassa ku tilausnäkymässä tehdää näytä nappi
			if ($tee != "tilaus") {
				echo "<form method='post' action='$PHP_SELF'>";
				echo "<input type='hidden' name='tee' value='$teemita'>";
				echo "<input type='hidden' name='pp' value='$pp'>";
				echo "<input type='hidden' name='kk' value='$kk'>";
				echo "<input type='hidden' name='vv' value='$vv'>";
				echo "<input type='hidden' name='toim' value='$toim'>";
				echo "<input type='hidden' name='tunnus' value='$row[tunnus]'>";
				echo "<input type='hidden' name='haku' value='$haku'>";
				echo "<input type='hidden' name='tilhaku' value='$tilhaku'>";
				echo "<input type='hidden' name='keikkanrohaku' value='$keikkanrohaku'>";
				echo "<td class='back'><input type='submit' value='".t("Näytä")."'></td>";
				echo "</form>";
			}

			// jos kyseessä on myyntitilaus, ollaan päivänäkymässä ja meillä on oikeudet, niin tehdään tällänen nappula
			if ($toim == "MYYNTI" and $tee == "paiva" and mysql_num_rows($apuoikeures) > 0) {

				// haetaan tässä keisissä vielä tila ja alatila
				$aputilaquery = "select tila, alatila from lasku where yhtio='$kukarow[yhtio]' and tunnus='$row[tunnus]'";
				$aputilares = mysql_query($aputilaquery) or pupe_error($aputilaquery);
				$tila_row = mysql_fetch_array($aputilares);

				// vain laskuttamattomille myyntitilaukille voi tehdä jotain
				if ($tila_row["tila"] == "L" and $tila_row["alatila"] != "X" or ($tila_row["tila"] == "N" and in_array($tila_row["alatila"], array('A','')))) {
					echo "<form method='post' action='vaihda_tila.php'>";
					echo "<input type='hidden' name='parametrit' value='$teemita#$pp#$kk#$vv#$toim'>";
					echo "<input type='hidden' name='tee' value='valitse'>";
					echo "<input type='hidden' name='tunnus' value='$row[tunnus]'>";
					echo "<td class='back'><input type='submit' value='".t("Vaihda tila")."'></td>";
					echo "</form>";
				}
			}

			echo "</tr>";


		}
		
		if ($arvo != 0 or $summa != 0) {
			echo "<tr>";
			echo "<th colspan='".(mysql_num_fields($result)-2)."'>".t("Yhteensä").": </th>";
			echo "<td align='right'>".sprintf('%.02f',$summa)."</td>";
			echo "<td align='right'>".sprintf('%.02f',$arvo)."</td>";
			echo "</tr>";
		}

		echo "</table>";
	}
	else {
		echo t("Ei tilauksia")."...";
	}

	require ("inc/footer.inc");
?>
