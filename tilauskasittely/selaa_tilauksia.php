<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

require "../inc/parametrit.inc";

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
$ekv = date("Y", mktime(0, 0, 0, $kk-1, 1, $vv));
$ekk = date("m", mktime(0, 0, 0, $kk-1, 1, $vv));
$ekp = date("d", mktime(0, 0, 0, $kk-1, 1, $vv));
$nkv = date("Y", mktime(0, 0, 0, $kk+1, 1, $vv));
$nkk = date("m", mktime(0, 0, 0, $kk+1, 1, $vv));
$nkp = date("d", mktime(0, 0, 0, $kk+1, 1, $vv));

// edellinen ja seuraava päivä
$epv = date("Y", mktime(0, 0, 0, $kk, $pp-1, $vv));
$epk = date("m", mktime(0, 0, 0, $kk, $pp-1, $vv));
$epp = date("d", mktime(0, 0, 0, $kk, $pp-1, $vv));
$npv = date("Y", mktime(0, 0, 0, $kk, $pp+1, $vv));
$npk = date("m", mktime(0, 0, 0, $kk, $pp+1, $vv));
$npp = date("d", mktime(0, 0, 0, $kk, $pp+1, $vv));

$etsi = '';

if (is_string($haku) and trim($haku) != "") {
  $etsi = "and (lasku.nimi LIKE '%$haku%' or lasku.toim_nimi LIKE '%$haku%') ";
}

if (is_numeric($haku) and trim($haku) != "") {
  $etsi = "and lasku.ytunnus LIKE '%$haku%' ";
}

if ($keikkanrohaku != '' and $toim == 'KEIKKA' and is_numeric($keikkanrohaku)) {
  $etsi = "and lasku.laskunro = $keikkanrohaku ";
}

// tässä myyntitilausten queryt
if ($toim == "MYYNTI" or $toim == "MYYNTI_KATE") {
  $query_ale_lisa = generoi_alekentta('M');
  $ale_query_select_lisa = generoi_alekentta_select('erikseen', 'M');

  if ($toim == "MYYNTI_KATE") {

    $katesql = "if(tilausrivi.laskutettu!='', tilausrivi.kate, (tilausrivi.hinta/if('{$yhtiorow['alv_kasittely']}'='',(1+tilausrivi.alv/100),1)*(tilausrivi.varattu+tilausrivi.jt))*{$query_ale_lisa} - (if(tuote.epakurantti100pvm = '0000-00-00', if(tuote.epakurantti75pvm = '0000-00-00', if(tuote.epakurantti50pvm = '0000-00-00', if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0) * (tilausrivi.varattu+tilausrivi.jt)))";

    $kk_pv_kate_lisa = ",round(sum($katesql), 2) AS 'kate' ";
    $tilaus_kate_lisa = ",round($katesql, 2) AS 'kate' ";

    $tuote_join = " JOIN tuote ON (tuote.tuoteno = tilausrivi.tuoteno
                     AND tuote.yhtio = tilausrivi.yhtio) ";
  }

  // kuukausinäkymä
  $query1 = "SELECT DATE_FORMAT(lasku.luontiaika,'%d.%m.%Y') pvm, DATE_FORMAT(lasku.luontiaika,'%a') vkpvm,
             count(distinct lasku.tunnus) tilauksia,
             count(distinct tilausrivi.tunnus) riveja,
             round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa}),2) summa,
             round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa}),2) arvo
             {$kk_pv_kate_lisa}
             FROM lasku use index (yhtio_tila_luontiaika)
             JOIN tilausrivi use index (yhtio_otunnus) ON (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
             {$tuote_join}
             WHERE lasku.yhtio    = '$kukarow[yhtio]'
             and lasku.tila       = 'L'
             and lasku.luontiaika >= '$vv-$kk-01 00:00:00'
             and lasku.luontiaika < '$nkv-$nkk-01 00:00:00'
             $etsi
             GROUP BY pvm
             ORDER BY pvm";

  // päivänäkymä
  $query2 = "SELECT lasku.tunnus, if(lasku.nimi!=lasku.toim_nimi, concat_ws(' / ', lasku.nimi, lasku.toim_nimi),concat_ws(' / ', lasku.nimi, lasku.nimitark)) nimi, DATE_FORMAT(lasku.luontiaika,'%d.%m.%Y') pvm, DATE_FORMAT(lasku.luontiaika,'%a') vkpvm,
             round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa}),2) summa,
             round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa}),2) arvo
             {$kk_pv_kate_lisa}
             FROM lasku use index (yhtio_tila_luontiaika)
             JOIN tilausrivi use index (yhtio_otunnus) ON (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
             {$tuote_join}
             WHERE lasku.yhtio    = '$kukarow[yhtio]'
             and lasku.tila       = 'L'
             and lasku.luontiaika >= '$vv-$kk-$pp 00:00:00'
             and lasku.luontiaika <= '$vv-$kk-$pp 23:59:59'
             $etsi
             GROUP BY lasku.tunnus
             ORDER BY lasku.tunnus";

  // tilausnäkymä
  $query3 = "SELECT otunnus tunnus, DATE_FORMAT(lasku.luontiaika,'%d.%m.%Y') pvm, tilausrivi.tuoteno, concat(tilausrivi.nimitys, if(kommentti!='', concat('<br>* ',kommentti),'')) nimitys, kpl+varattu kpl, tilausrivi.hinta, {$ale_query_select_lisa} lasku.erikoisale, tilausrivi.alv,
             round(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa},'$yhtiorow[hintapyoristys]') summa,
             round(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa},'$yhtiorow[hintapyoristys]') arvo
             {$tilaus_kate_lisa}
             FROM tilausrivi use index (yhtio_otunnus)
             JOIN lasku use index (PRIMARY) on (lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus)
             {$tuote_join}
             WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
             and tilausrivi.tyyppi  != 'D'
             and tilausrivi.otunnus  = '$tunnus'
             ORDER BY tilausrivi.tunnus";
}

// tässä ostotilausten queryt
if ($toim == "OSTO") {

  $query_ale_lisa = generoi_alekentta('O');

  // kuukausinäkymä
  $query1 = "SELECT DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, DATE_FORMAT(luontiaika,'%a') vkpvm,
             count(distinct lasku.tunnus) tilauksia, count(distinct tilausrivi.tunnus) riveja
             FROM lasku use index (yhtio_tila_luontiaika)
             JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tyyppi!='D')
             WHERE lasku.yhtio = '$kukarow[yhtio]' and
             tila              in ('O') and
             luontiaika        >= '$vv-$kk-01 00:00:00' and
             luontiaika        < '$nkv-$nkk-01 00:00:00'
             $etsi
             GROUP BY pvm
             ORDER BY luontiaika";

  // päivänäkymä
  $query2 = "SELECT lasku.tunnus, lasku.nimi, DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, DATE_FORMAT(luontiaika,'%a') vkpvm,
             round(tilausrivi.hinta*{$query_ale_lisa}*(tilausrivi.varattu+tilausrivi.kpl),2) summa, lasku.valkoodi
             FROM lasku use index (yhtio_tila_luontiaika)
             JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tyyppi!='D')
             WHERE lasku.yhtio = '$kukarow[yhtio]' and
             tila              in ('O') and
             luontiaika        >= '$vv-$kk-$pp 00:00:00' and
             luontiaika        <= '$vv-$kk-$pp 23:59:59'
             $etsi
             GROUP BY lasku.tunnus
             ORDER BY luontiaika";

  // tilausnäkymä
  $query3 = "SELECT otunnus, DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, tuoteno, nimitys, kpl+varattu kpl, tilausrivi.hinta,
             round(tilausrivi.hinta*{$query_ale_lisa}*(tilausrivi.varattu+tilausrivi.kpl),'$yhtiorow[hintapyoristys]') arvo, lasku.valkoodi
             FROM tilausrivi use index (yhtio_otunnus)
             JOIN lasku use index (PRIMARY) on (lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus)
             WHERE tilausrivi.yhtio = '$kukarow[yhtio]' and
             otunnus                = '$tunnus' and
             tyyppi!='D'
             ORDER BY tilausrivi.tunnus";
}

// tässä keikkojen queryt
if ($toim == "KEIKKA") {

  $query_ale_lisa = generoi_alekentta('O');

  // kuukausinäkymä
  $query1 = "SELECT DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, DATE_FORMAT(luontiaika,'%a') vkpvm,
             count(distinct lasku.tunnus) keikkoja, count(distinct tilausrivi.tunnus) riveja
             FROM lasku use index (yhtio_tila_luontiaika)
             JOIN tilausrivi use index (uusiotunnus_index) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.uusiotunnus=lasku.tunnus and tyyppi!='D')
             WHERE lasku.yhtio = '$kukarow[yhtio]' and
             tila              in ('K') and
             vanhatunnus       = 0 and
             luontiaika        >= '$vv-$kk-01 00:00:00' and
             luontiaika        < '$nkv-$nkk-01 00:00:00'
             $etsi
             GROUP BY pvm
             ORDER BY luontiaika";

  // päivänäkymä
  $query2 = "SELECT lasku.laskunro saapuminen, lasku.tunnus, lasku.nimi, DATE_FORMAT(lasku.luontiaika,'%d.%m.%Y') pvm, if(lasku.mapvm='0000-00-00','',DATE_FORMAT(lasku.mapvm,'%d.%m.%Y')) jlaskenta,
             round(sum(tilausrivi.hinta*{$query_ale_lisa}*(tilausrivi.varattu+tilausrivi.kpl)),2) summa, lasku.valkoodi
             FROM lasku use index (yhtio_tila_luontiaika)
             JOIN tilausrivi use index (uusiotunnus_index) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.uusiotunnus=lasku.tunnus and tyyppi!='D')
             WHERE lasku.yhtio = '$kukarow[yhtio]' and
             tila              in ('K') and
             vanhatunnus       = 0 ";

  if (!isset($keikkanrohaku) or $keikkanrohaku == "") {
    $query2 .= " and lasku.luontiaika >= '$vv-$kk-$pp 00:00:00' and lasku.luontiaika <= '$vv-$kk-$pp 23:59:59' ";
  }

  $query2 .= "$etsi
        GROUP BY lasku.tunnus
        ORDER BY lasku.laskunro";

  // tilausnäkymä
  $query3 = "SELECT lasku.laskunro saapuminen, DATE_FORMAT(lasku.luontiaika,'%d.%m.%Y') pvm, DATE_FORMAT(tilausrivi.laskutettuaika,'%d.%m.%Y') 'viety varastoon', tuoteno, nimitys, kpl+varattu kpl, round(tilausrivi.hinta*vienti_kurssi, '$yhtiorow[hintapyoristys]') hinta,
             round(tilausrivi.hinta*{$query_ale_lisa}*(tilausrivi.varattu+tilausrivi.kpl)*vienti_kurssi,'$yhtiorow[hintapyoristys]') arvo, '$yhtiorow[valkoodi]' valkoodi, round(tilausrivi.rivihinta, '$yhtiorow[hintapyoristys]') ostohinta, vienti_kurssi kurssi, tilausrivin_lisatiedot.hankintakulut
             FROM tilausrivi use index (uusiotunnus_index)
             JOIN lasku use index (PRIMARY) on (lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.uusiotunnus)
             LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)
             WHERE tilausrivi.yhtio = '$kukarow[yhtio]' and
             uusiotunnus            = '$tunnus' and
             tyyppi!='D'
             ORDER BY tilausrivi.tunnus";

  // tilausnumerohaku
  $query4 = "SELECT lasku.laskunro saapuminen, lasku.tunnus, lasku.nimi, DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, if(mapvm='0000-00-00','',DATE_FORMAT(mapvm,'%d.%m.%Y')) jlaskenta,
             round(sum(tilausrivi.hinta*{$query_ale_lisa}*(tilausrivi.varattu+tilausrivi.kpl)),2) summa, lasku.valkoodi
             FROM tilausrivi use index (yhtio_otunnus)
             JOIN lasku ON tilausrivi.yhtio=lasku.yhtio and tilausrivi.uusiotunnus=lasku.tunnus and tila = 'K' and vanhatunnus = 0
             WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
             and otunnus            = '$tilhaku'
             and tyyppi!='D'
             GROUP BY lasku.tunnus
             ORDER BY luontiaika";
}

// tässä valmistusten queryt
if ($toim == "VALMISTUS") {

  $query_ale_lisa = generoi_alekentta('M');

  // kuukausinäkymä
  $query1 = "SELECT DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, DATE_FORMAT(luontiaika,'%a') vkpvm,
             count(distinct lasku.tunnus) valmistuksia,
             count(distinct tilausrivi.tunnus) riveja,
             round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa}),2) summa,
             round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa}),2) arvo
             FROM lasku use index (yhtio_tila_luontiaika)
             JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tyyppi!='D')
             WHERE lasku.yhtio = '$kukarow[yhtio]' and
             tila              in ('V') and
             luontiaika        >= '$vv-$kk-01 00:00:00' and
             luontiaika        < '$nkv-$nkk-01 00:00:00'
             $etsi
             GROUP BY pvm
             ORDER BY luontiaika";

  // päivänäkymä
  $query2 = "SELECT lasku.tunnus valmistus, lasku.tunnus, lasku.nimi, DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, DATE_FORMAT(luontiaika,'%a') vkpvm,
             round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa}),2) summa,
             round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa}),2) arvo
             FROM lasku use index (yhtio_tila_luontiaika)
             JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tyyppi!='D')
             WHERE lasku.yhtio = '$kukarow[yhtio]' and
             tila              in ('V') and
             luontiaika        >= '$vv-$kk-$pp 00:00:00' and
             luontiaika        <= '$vv-$kk-$pp 23:59:59'
             $etsi
             GROUP BY lasku.tunnus
             ORDER BY luontiaika";

  // tilausnäkymä
  $query3 = "SELECT lasku.tunnus valmistus, DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, tuoteno, nimitys, kpl+varattu kpl, tilausrivi.hinta,
             round(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa},'$yhtiorow[hintapyoristys]') summa,
             round(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa},'$yhtiorow[hintapyoristys]') arvo
             FROM tilausrivi use index (yhtio_otunnus)
             JOIN lasku use index (PRIMARY) on (lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus)
             WHERE tilausrivi.yhtio = '$kukarow[yhtio]' and
             otunnus                = '$tunnus'
             ORDER BY perheid desc, tyyppi in ('W','L','D','V'), tilausrivi.tunnus";
}

// tässä tarjousten queryt
if ($toim == "TARJOUS") {

  $query_ale_lisa = generoi_alekentta('M');
  $ale_query_select_lisa = generoi_alekentta_select('erikseen', 'M');

  // kuukausinäkymä
  $query1 = "SELECT DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, DATE_FORMAT(luontiaika,'%a') vkpvm,
             count(distinct lasku.tunnus) tilauksia,
             count(distinct tilausrivi.tunnus) riveja,
             round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa}),2) summa,
             round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa}),2) arvo
             FROM lasku use index (yhtio_tila_luontiaika)
             JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tyyppi!='D')
             WHERE lasku.yhtio = '$kukarow[yhtio]' and
             tila              in ('T') and
             luontiaika        >= '$vv-$kk-01 00:00:00' and
             luontiaika        < '$nkv-$nkk-01 00:00:00'
             $etsi
             GROUP BY pvm
             ORDER BY luontiaika";

  // päivänäkymä
  $query2 = "SELECT lasku.tunnus, if(lasku.nimi!=lasku.toim_nimi, concat_ws(' / ', lasku.nimi, lasku.toim_nimi),lasku.nimi) nimi, DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, DATE_FORMAT(luontiaika,'%a') vkpvm,
             round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa}),2) summa,
             round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa}),2) arvo
             FROM lasku use index (yhtio_tila_luontiaika)
             JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tyyppi!='D')
             WHERE lasku.yhtio = '$kukarow[yhtio]' and
             tila              in ('T') and
             luontiaika        >= '$vv-$kk-$pp 00:00:00' and
             luontiaika        <= '$vv-$kk-$pp 23:59:59'
             $etsi
             GROUP BY lasku.tunnus
             ORDER BY luontiaika";

  // tilausnäkymä
  $query3 = "SELECT otunnus, DATE_FORMAT(luontiaika,'%d.%m.%Y') pvm, tuoteno, nimitys, kpl+varattu kpl, tilausrivi.hinta, {$ale_query_select_lisa} lasku.erikoisale, tilausrivi.alv,
             round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa}),'$yhtiorow[hintapyoristys]') summa,
             round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa}),'$yhtiorow[hintapyoristys]') arvo
             FROM tilausrivi use index (yhtio_otunnus)
             JOIN lasku use index (PRIMARY) on (lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus)
             WHERE tilausrivi.yhtio  = '$kukarow[yhtio]' and
             tyyppi                 != 'D' and
             otunnus                 = '$tunnus'
             ORDER BY tilausrivi.tunnus";
}

if ($tee == "paiva") {
  $result = pupe_query($query2);
  echo "<a href='$PHP_SELF?toim=$toim&tee=paiva&vv=$epv&kk=$epk&pp=$epp&haku=$haku&keikkanrohaku=$keikkanrohaku'>".t("Edellinen päivä")."</a> - <a href='$PHP_SELF?toim=$toim&tee=paiva&vv=$npv&kk=$npk&pp=$npp&haku=$haku&keikkanrohaku=$keikkanrohaku'>".t("Seuraava päivä")."</a>";
  echo " - <a href='$PHP_SELF?toim=$toim&tee=kk&vv=$vv&kk=$kk&haku=$haku&keikkanrohaku=$keikkanrohaku'>".t("Kuukausinäkymä")."</a>";
  echo "<br><br>";
  //echo "$query2<br><br>";
}
elseif ($tee == "kk") {
  $result = pupe_query($query1);
  echo "<a href='$PHP_SELF?toim=$toim&tee=kk&vv=$ekv&kk=$ekk&pp=$ekp&haku=$haku&keikkanrohaku=$keikkanrohaku'>".t("Edellinen kuukausi")."</a> - <a href='$PHP_SELF?toim=$toim&tee=kk&vv=$nkv&kk=$nkk&pp=$nkp&haku=$haku&keikkanrohaku=$keikkanrohaku'>".t("Seuraava kuukausi")."</a>";
  echo "<br><br>";
  //echo "$query1<br><br>";
}
elseif ($tee == "tilaus") {
  $result = pupe_query($query3);
  echo "<a href='$PHP_SELF?toim=$toim&tee=paiva&vv=$vv&kk=$kk&pp=$pp'>".t("Päivänäkymä")."</a> - <a href='$PHP_SELF?toim=$toim&tee=kk&vv=$vv&kk=$kk'>".t("Kuukausinäkymä")."</a>";
  echo "<br><br>";
  //echo "$query3<br><br>";
}
elseif ($tee == 'tilhaku' and $toim == 'KEIKKA') {
  $result = pupe_query($query4);
  echo "<a href='$PHP_SELF?toim=$toim&tee=paiva&vv=$vv&kk=$kk&pp=$pp'>".t("Päivänäkymä")."</a> - <a href='$PHP_SELF?toim=$toim&tee=kk&vv=$vv&kk=$kk'>".t("Kuukausinäkymä")."</a>";
  echo "<br><br>";
  //echo "$query4<br><br>";
}
else {
  echo "Kaboom!";
  unset($result);
}

if ($tee == "paiva" or $tee == "kk") {
  echo "<form method='post'>";
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
    echo "<tr><th>".t("Hae saapumisnumerolla").":</th>";
    echo "<td><input type='text' name='keikkanrohaku' value='$keikkanrohaku'></td>";
    echo "</tr><tr>";
    echo "<th>".t("Hae tilausnumerolla").":</th>";
    echo "<td><input type='text' name='tilhaku' value='$tilhaku'></td>";
  }
  echo "<td class='back'><input type='submit' class='hae_btn' value='".t("Hae")."'></td>";
  echo "</tr>";
  echo "</table>";

  echo "</form><br>";
}

if (mysql_num_rows($result) > 0) {

  echo "<table>";

  echo "<tr>";

  for ($i = 0; $i < mysql_num_fields($result); $i++) {
    if (mysql_field_name($result, $i) == "hankintakulut") {
      echo "<th>".t("Kululaskut")."</th><th>".t("Eturahti")."</th><th>".t("Pyöristyserot")."</th><th>".t("Tulli%")."</th><th>".t("Tulli")."</th><th>".t("Lisäkulu")."</th><th>".t("Saapumisen kulut")."<br>(".t("Summan tai prosentin osuus").")</th>";      }
    else {
      echo "<th>".t(mysql_field_name($result, $i))."</th>";
    }
  }

  echo "</tr>";

  $arvo    = 0;
  $summa   = 0;
  $teemita = "";
  $osuus_kululaskuista_yhteensa = "";
  $osuus_eturahdista_yhteensa = "";
  $osuus_pyoristyseroista_yhteensa = "";
  $ostohinta_yhteesa = "";
  $aputullimaara_yhteensa = "";
  $rivinlisakulu_yhteensa = "";
  $saapumisenkulut_yhteensa = "";

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
  $oikeuquery = "SELECT * from oikeu where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]' and nimi like '%vaihda_tila.php'";
  $apuoikeures = pupe_query($oikeuquery);

  while ($row = mysql_fetch_array($result)) {

    list ($pp, $kk, $vv) = explode(".", $row["pvm"], 3);

    echo "<tr>";

    for ($i = 0; $i < mysql_num_fields($result); $i++) {

      if (mysql_field_name($result, $i) == "kate") {
        $katepros = round($row["kate"] / $row["arvo"] * 100, 2);

        if ($row["kate"] < 0) {
          $katepros = abs($katepros) * -1;
        }

        echo "<td align='right'>$katepros%</td>";
      }
      elseif (is_numeric($row[$i]) and (mysql_field_type($result, $i) == 'real' or mysql_field_type($result, $i) == 'int')) {
        echo "<td align='right'>$row[$i]</td>";
      }
      elseif (mysql_field_name($result, $i) == "hankintakulut") {
        $osuus_kululaskuista = $osuus_eturahdista = $osuus_pyoristyseroista = $tulliprossa = $aputullimaara = $rivinlisakulu = $saapumisenkulut ="";

        if (strpos($row[$i], "#") !== FALSE) {
          list($osuus_kululaskuista, $osuus_eturahdista, $osuus_pyoristyseroista, $tulliprossa, $aputullimaara, $rivinlisakulu, $saapumisenkulut) = explode("#", $row[$i]);
          $osuus_kululaskuista_yhteensa += $osuus_kululaskuista;
          $osuus_eturahdista_yhteensa += $osuus_eturahdista;
          $osuus_pyoristyseroista_yhteensa += $osuus_pyoristyseroista;
          $aputullimaara_yhteensa += $aputullimaara;
          $rivinlisakulu_yhteensa += $rivinlisakulu;
          $saapumisenkulut_yhteensa += $saapumisenkulut;
        }

        echo "<td align='right'>$osuus_kululaskuista $yhtiorow[valkoodi]</td>";
        echo "<td align='right'>$osuus_eturahdista $yhtiorow[valkoodi]</td>";
        echo "<td align='right'>$osuus_pyoristyseroista $yhtiorow[valkoodi]</td>";
        echo "<td align='right'>$tulliprossa %</td>";
        echo "<td align='right'>$aputullimaara $yhtiorow[valkoodi]</td>";
        echo "<td align='right'>$rivinlisakulu $yhtiorow[valkoodi]</td>";
        echo "<td align='right'>$saapumisenkulut $yhtiorow[valkoodi]</td>";
      }
      elseif (mysql_field_name($result, $i) == "tuoteno") {
        echo "<td><a href='{$palvelin2}tuote.php?tee=Z&tuoteno=".urlencode($row[$i])."'>$row[$i]</a></td>";
      }
      else {
        echo "<td>$row[$i]</td>";
      }
    }

    $arvo  += $row["arvo"];
    $kate  += $row["kate"];
    $summa += $row["summa"];
    $ostohinta_yhteesa += $row["ostohinta"];

    // jos ollaan muussa tilassa ku tilausnäkymässä tehdää näytä nappi
    if ($tee != "tilaus") {
      echo "<form method='post'>";
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
    if (($toim == "MYYNTI" or $toim == "MYYNTI_KATE") and $tee == "paiva" and mysql_num_rows($apuoikeures) > 0) {

      // haetaan tässä keisissä vielä tila ja alatila
      $aputilaquery = "SELECT tila, alatila from lasku where yhtio='$kukarow[yhtio]' and tunnus='$row[tunnus]'";
      $aputilares = pupe_query($aputilaquery);
      $tila_row = mysql_fetch_array($aputilares);

      // vain laskuttamattomille myyntitilaukille voi tehdä jotain
      if ($tila_row["tila"] == "L" and $tila_row["alatila"] != "X" or ($tila_row["tila"] == "N" and in_array($tila_row["alatila"], array('A', '')))) {
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
    if ($toim == "MYYNTI_KATE") {
      $i = 3;
    }
    else {
      $i = 2;
    }

    $summa = sprintf('%.02f', $summa);

    if ($osuus_kululaskuista_yhteensa != "" or $osuus_eturahdista_yhteensa != "" or $osuus_pyoristyseroista_yhteensa != "" or $aputullimaara_yhteensa != "" or $rivinlisakulu_yhteensa != "" or $saapumisenkulut_yhteensa != "") {
      $i = 6;
      $summa = "";
    }
    echo "<tr>";
    echo "<th colspan='".(mysql_num_fields($result)-$i)."'>".t("Yhteensä").": </th>";
    echo "<th align='right'>$summa</td>";
    echo "<th align='right'>".sprintf('%.02f', $arvo)."</td>";

    $kateprosyht = round($kate / $arvo * 100, 2);

    if ($kate < 0) {
      $kateprosyht = abs($kateprosyht) * -1;
    }
    if ($toim == "MYYNTI_KATE") {
      echo "<th align='right'>".sprintf('%.02f', $kateprosyht)."</td>";
    }
    if ($osuus_kululaskuista_yhteensa != "" or $osuus_eturahdista_yhteensa != "" or $osuus_pyoristyseroista_yhteensa != "" or $aputullimaara_yhteensa != "" or $rivinlisakulu_yhteensa != "" or $saapumisenkulut_yhteensa != "") {
      echo "<th align='right'>&nbsp;</td>";
      echo "<th align='right'>&nbsp;$ostohinta_yhteesa</td>";
      echo "<th align='right'>&nbsp;</td>";
      echo "<th align='right'>".sprintf('%.02f', $osuus_kululaskuista_yhteensa)." $yhtiorow[valkoodi]</td>";
      echo "<th align='right'>".sprintf('%.02f', $osuus_eturahdista_yhteensa)." $yhtiorow[valkoodi]</td>";
      echo "<th align='right'>".sprintf('%.02f', $osuus_pyoristyseroista_yhteensa)." $yhtiorow[valkoodi]</td>";
      echo "<th align='right'>&nbsp;</td>";
      echo "<th align='right'>".sprintf('%.02f', $aputullimaara_yhteensa)." $yhtiorow[valkoodi]</td>";
      echo "<th align='right'>".sprintf('%.02f', $rivinlisakulu_yhteensa)." $yhtiorow[valkoodi]</td>";
      echo "<th align='right'>".sprintf('%.02f', $saapumisenkulut_yhteensa)." $yhtiorow[valkoodi]</td>";
    }
    echo "</tr>";
  }

  echo "</table>";
}
else {
  echo t("Ei tilauksia")."...";
}

require "inc/footer.inc";
