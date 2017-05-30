<?php

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
$useslave = 1;

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') {
    $lataa_tiedosto = 1;
  }

  if (isset($_POST["kaunisnimi"]) and $_POST["kaunisnimi"] != '') {
    $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
  }
}

require 'inc/parametrit.inc';

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}

if (!isset($tapa))          $tapa = "";
if (!isset($tee))          $tee = "";
if (!isset($outputti))        $outputti = "";
if (!isset($lahetys))        $lahetys = "";
if (!isset($lisavar))        $lisavar = "";
if (!isset($tapahtumalaji))      $tapahtumalaji = "";
if (!isset($excel))          $excel = "";
if (!isset($maalisa))        $maalisa = "";
if (!isset($bruttopaino))      $bruttopaino = "";
if (!isset($kayttajan_valinta_maa))  $kayttajan_valinta_maa = "";
if (!isset($totkpl))        $totkpl = "";
if (!isset($totsumma))        $totsumma = "";
if (!isset($vaintullinimike))    $vaintullinimike = "";

if ($kayttajan_valinta_maa != "") {
  $maa = $kayttajan_valinta_maa;
}
else {
  $maa = $yhtiorow["maa"];
}

// tuonti vai vienti
if ($tapa == "tuonti") {
  $laji = "A";
  $tilastoloppu = '001';
}
elseif ($tapa == "vienti") {
  $laji = "D";
  $tilastoloppu = '002';
}

echo "<font class='head'>".t("Intrastat-ilmoitukset")."</font><hr>";

if ($tee == "tulosta") {

  if ($excel != "") {
    include 'inc/pupeExcel.inc';

    $worksheet    = new pupeExcel();
    $format_bold = array("bold" => TRUE);
    $excelrivi    = 0;
  }

  // tehd‰‰n kauniiseen muotoon annetun kauden eka ja vika pvm
  $vva = date("Y", mktime(0, 0, 0, $kk, 1, $vv));
  $kka = date("m", mktime(0, 0, 0, $kk, 1, $vv));
  $ppa = date("d", mktime(0, 0, 0, $kk, 1, $vv));
  $vvl = date("Y", mktime(0, 0, 0, $kk+1, 0, $vv));
  $kkl = date("m", mktime(0, 0, 0, $kk+1, 0, $vv));
  $ppl = date("d", mktime(0, 0, 0, $kk+1, 0, $vv));

  $query = "SELECT distinct koodi, nimi
            FROM maat
            where nimi != ''
            and eu     != ''
            ORDER BY koodi";
  $vresult = pupe_query($query);

  $eumaat = "";
  while ($row = mysql_fetch_array($vresult)) {
    $eumaat .= "'$row[koodi]',";
  }
  $eumaat = substr($eumaat, 0, -1);

  $ee_yhdistettyorder = "";
  $ee_kentat       = "";
  $ee_group       = "";

  // tuonti vai vienti
  if ($tapa == "tuonti") {
    $maalisa = " maamaara in ('', '$maa') and maalahetys in ('',$eumaat) and maalahetys != '$maa' ";
  }
  elseif ($tapa == "vienti") {
    $maalisa = " maalahetys in ('', '$maa') and maamaara in ('',$eumaat) and maamaara != '$maa' ";
  }
  elseif ($tapa == "yhdistetty") {
    $maalisa = " (maamaara != maalahetys AND
            (
              (maalahetys in ('', '$maa') AND maamaara in ('',$eumaat))
            OR
              (maalahetys in ('', $eumaat) AND maamaara in ('','$maa'))
            )
          ) ";
    $ee_yhdistettyorder = " if(maamaara='$maa', 0, 1), ";

    $ee_kentat = " lasku.valkoodi, tullinimike.dm, substr(lasku.toimitusehto, 1, 3) as toim_ehto, ";
    $ee_group = ", valkoodi, dm, toim_ehto ";
  }

  if ($lisavar == "S") {
    $lisavarlisa = " and (tilausrivi.perheid2=0 or tilausrivi.perheid2=tilausrivi.tunnus) ";
  }
  else {
    $lisavarlisa = "";
  }

  if ($vaintullinimike != "") {
    $vainnimikelisa  = " and tuote.tullinimike1 = '{$vaintullinimike}' and lasku.kuljetusmuoto = '{$vainkuljetusmuoto}' and lasku.kauppatapahtuman_luonne = '{$vainkauppatapahtuman_luonne}' and tullinimike.su_vientiilmo = '{$vainsu}' ";
    $maalisa .= " and maalahetys = '{$vainmaalahetys}' and alkuperamaa = '{$vainalkuperamaa}' and maamaara = '{$vainmaamaara}' ";

    $vainnimikelisa2 = " tilausrivi.tunnus, ";
    $vainnimikelisa2_tyom = "'' as tunnus,";
    $vainnimikegroup = " ,9 ";
  }
  else {
    $vainnimikelisa  = "";
    $vainnimikelisa2 = "";
    $vainnimikelisa2_tyom = "";
    $vainnimikegroup = "";
  }

  $query = "";

  // t‰ss‰ tulee sitten nimiketietueet unionilla
  if ($tapahtumalaji == "kaikki" or $tapahtumalaji == "keikka") {

    $alennukset = generoi_alekentta('O', 'tilausrivi');

    $query = "(SELECT
               tuote.tullinimike1,
               if (lasku.maa_lahetys='', toimi.maa, lasku.maa_lahetys) maalahetys,
               ifnull((SELECT alkuperamaa FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.alkuperamaa not in ('$yhtiorow[maa]','') LIMIT 1), if (lasku.maa_lahetys='', toimi.maa, lasku.maa_lahetys)) alkuperamaa,
               if (lasku.maa_maara='', if (lasku.toim_maa='', if(varastopaikat.maa is null or varastopaikat.maa='', '$yhtiorow[maa]', varastopaikat.maa), lasku.toim_maa), lasku.maa_maara) maamaara,
               lasku.kuljetusmuoto,
               lasku.kauppatapahtuman_luonne,
               tullinimike.su_vientiilmo su,
               'Saapuminen' as tapa,
               $vainnimikelisa2
               $ee_kentat
               max(lasku.laskunro) laskunro,
               max(concat(tuote.tuoteno,'!°!',left(tuote.nimitys, 40))) tuoteno_nimitys,
               round(sum(tilausrivi.kpl * if(tuote.toinenpaljous_muunnoskerroin = 0, 1, tuote.toinenpaljous_muunnoskerroin)),0) kpl,
               round(sum(if(tuote.tuotemassa > 0, tilausrivi.kpl * tuote.tuotemassa, if(lasku.summa > tilausrivi.rivihinta, tilausrivi.rivihinta / lasku.summa, 1) * lasku.bruttopaino)), 0) as paino,
               if (round(sum(tilausrivi.rivihinta),0) > 0.50, round(sum(tilausrivi.rivihinta),0), 1) rivihinta,
               if (round(sum(tilausrivi.rivihinta / (1 + (lasku.rahti / 100))),0) > 0.50,
                 if (valuu.intrastat_kurssi = 0,
                   round(sum(tilausrivi.rivihinta / (1 + (lasku.rahti / 100)) / lasku.vienti_kurssi),0),
                   round(sum(tilausrivi.rivihinta / (1 + (lasku.rahti / 100)) / valuu.intrastat_kurssi),0)), 1) as rivihinta_laskutusarvo,
               group_concat(lasku.tunnus) as kaikkitunnukset,
               group_concat(distinct tilausrivi.perheid2) as perheid2set,
               group_concat(concat(tuote.tunnus,'!°!', tuote.tuoteno)) as kaikkituotteet";

    if ($yhtiorow['intrastat_pvm'] == '') {
      $query .= "  FROM lasku use index (yhtio_tila_mapvm)
            JOIN toimi ON (lasku.yhtio=toimi.yhtio and lasku.liitostunnus=toimi.tunnus)
            JOIN tilausrivi use index (uusiotunnus_index) ON (tilausrivi.yhtio=lasku.yhtio and tilausrivi.uusiotunnus=lasku.tunnus and tilausrivi.tyyppi = 'O' and tilausrivi.kpl > 0)
            JOIN tuote use index (tuoteno_index) ON (tuote.yhtio=lasku.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = '')
            LEFT JOIN tullinimike ON (tuote.tullinimike1=tullinimike.cn and tullinimike.kieli = '$yhtiorow[kieli]' and tullinimike.cn != '')
            LEFT JOIN varastopaikat ON (varastopaikat.yhtio = tilausrivi.yhtio
              AND varastopaikat.tunnus = tilausrivi.varasto)
            LEFT JOIN valuu ON (lasku.yhtio=valuu.yhtio and lasku.valkoodi=valuu.nimi)
            WHERE lasku.kohdistettu = 'X'
            and lasku.tila = 'K'
            and lasku.vanhatunnus = 0
            and lasku.kauppatapahtuman_luonne != '999'
            and lasku.yhtio = '$kukarow[yhtio]'
            and lasku.mapvm >= '$vva-$kka-$ppa'
            and lasku.mapvm <= '$vvl-$kkl-$ppl'";
    }
    else {
      $query .= "  FROM tilausrivi
            JOIN lasku ON (tilausrivi.uusiotunnus=lasku.tunnus and tilausrivi.yhtio=lasku.yhtio and lasku.tila = 'K' and lasku.vanhatunnus = 0 and lasku.kauppatapahtuman_luonne != '999')
            JOIN toimi ON (lasku.yhtio=toimi.yhtio and lasku.liitostunnus=toimi.tunnus)
            JOIN tuote use index (tuoteno_index) ON (tuote.yhtio=lasku.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = '')
            LEFT JOIN tullinimike ON (tuote.tullinimike1=tullinimike.cn and tullinimike.kieli = '$yhtiorow[kieli]' and tullinimike.cn != '')
            LEFT JOIN varastopaikat ON (varastopaikat.yhtio = tilausrivi.yhtio
              AND varastopaikat.tunnus = tilausrivi.varasto)
            LEFT JOIN valuu ON (lasku.yhtio=valuu.yhtio and lasku.valkoodi=valuu.nimi)
            WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
            and tilausrivi.tyyppi = 'O'
            and tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'
            and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl'
            and tilausrivi.kpl > 0";
    }

    $query .= "  $vainnimikelisa
          $lisavarlisa
          GROUP BY 1,2,3,4,5,6,7,8 $vainnimikegroup $ee_group
          HAVING $maalisa)";
  }

  if ($tapahtumalaji == "kaikki") {
    $query .= "  UNION";
  }

  if ($tapahtumalaji == "kaikki" or $tapahtumalaji == "lasku") {
    $query .= "  (SELECT
          tuote.tullinimike1,
          if (lasku.maa_lahetys='', ifnull(varastopaikat.maa, lasku.yhtio_maa), lasku.maa_lahetys) maalahetys,
          ifnull((SELECT alkuperamaa FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.alkuperamaa!='' ORDER BY if (alkuperamaa='$yhtiorow[maa]','2','1') LIMIT 1), '$yhtiorow[maa]') alkuperamaa,
          if (lasku.maa_maara='', lasku.toim_maa, lasku.maa_maara) maamaara,
          lasku.kuljetusmuoto,
          lasku.kauppatapahtuman_luonne,
          tullinimike.su_vientiilmo su,
          'Lasku' as tapa,
          $vainnimikelisa2
          $ee_kentat
          max(lasku.laskunro) laskunro,
          max(concat(tuote.tuoteno,'!°!',left(tuote.nimitys, 40))) tuoteno_nimitys,
          round(sum(tilausrivi.kpl * if (tuote.toinenpaljous_muunnoskerroin = 0, 1, tuote.toinenpaljous_muunnoskerroin)),0) kpl,
          round(sum(if(tuote.tuotemassa > 0, tilausrivi.kpl * tuote.tuotemassa, if(lasku.summa > tilausrivi.rivihinta, tilausrivi.rivihinta / lasku.summa, 1) * lasku.bruttopaino)), 0) as paino,
          if (round(sum(tilausrivi.rivihinta),0) > 0.50,round(sum(tilausrivi.rivihinta),0), 1) rivihinta,
          if (round(sum(tilausrivi.rivihinta),0) > 0.50,round(sum(tilausrivi.rivihinta),0), 1) rivihinta_laskutusarvo,
          group_concat(lasku.tunnus) as kaikkitunnukset,
          group_concat(distinct tilausrivi.perheid2) as perheid2set,
          group_concat(concat(tuote.tunnus,'!°!', tuote.tuoteno)) as kaikkituotteet
          FROM lasku use index (yhtio_tila_tapvm)
          JOIN tilausrivi use index (yhtio_otunnus) ON (tilausrivi.otunnus=lasku.tunnus and tilausrivi.yhtio=lasku.yhtio and tilausrivi.kpl > 0)
          JOIN tuote use index (tuoteno_index) ON (tuote.yhtio=lasku.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = '')
          LEFT JOIN tullinimike ON (tuote.tullinimike1=tullinimike.cn and tullinimike.kieli = '$yhtiorow[kieli]' and tullinimike.cn != '')
          LEFT JOIN varastopaikat ON (varastopaikat.yhtio=lasku.yhtio and varastopaikat.tunnus=lasku.varasto)
          LEFT JOIN asiakas on (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus)
          WHERE lasku.tila = 'L'
          and lasku.alatila = 'X'
          and lasku.tilaustyyppi != 'A'
          and lasku.kauppatapahtuman_luonne != '999'
          and not (lasku.vienti = '' and asiakas.laji = 'H')
          and lasku.yhtio = '$kukarow[yhtio]'
          and lasku.tapvm >= '$vva-$kka-$ppa'
          and lasku.tapvm <= '$vvl-$kkl-$ppl'
          $vainnimikelisa
          $lisavarlisa
          GROUP BY 1,2,3,4,5,6,7,8 $vainnimikegroup $ee_group
          HAVING $maalisa)";
  }

  if ($tapahtumalaji == "kaikki") {
    $query .= "  UNION";
  }

  if ($tapahtumalaji == "kaikki" or $tapahtumalaji == "siirtolista") {
    $query .= "  (SELECT
          tuote.tullinimike1,
          if (lasku.maa_lahetys='', ifnull(varastopaikat.maa, lasku.yhtio_maa), lasku.maa_lahetys) maalahetys,
          ifnull((SELECT alkuperamaa FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.alkuperamaa!='' ORDER BY if (alkuperamaa='$yhtiorow[maa]','2','1') LIMIT 1), '$yhtiorow[maa]') alkuperamaa,
          if (lasku.maa_maara='', lasku.toim_maa, lasku.maa_maara) maamaara,
          lasku.kuljetusmuoto,
          lasku.kauppatapahtuman_luonne,
          tullinimike.su_vientiilmo su,
          'Siirtolista' as tapa,
          $vainnimikelisa2
          $ee_kentat
          max(lasku.tunnus) laskunro,
          max(concat(tuote.tuoteno,'!°!',left(tuote.nimitys, 40))) tuoteno_nimitys,
          round(sum(tilausrivi.kpl * if (tuote.toinenpaljous_muunnoskerroin = 0, 1, tuote.toinenpaljous_muunnoskerroin)),0) kpl,
          round(sum(if(tuote.tuotemassa > 0, tilausrivi.kpl * tuote.tuotemassa, if(lasku.summa > tilausrivi.rivihinta, tilausrivi.rivihinta / lasku.summa, 1) * lasku.bruttopaino)), 0) as paino,
          if (round(sum(tilausrivi.rivihinta),0) > 0.50, round(sum(tilausrivi.rivihinta),0), 1) rivihinta,
          if (round(sum(tilausrivi.rivihinta),0) > 0.50,round(sum(tilausrivi.rivihinta),0), 1) rivihinta_laskutusarvo,
          group_concat(lasku.tunnus) as kaikkitunnukset,
          group_concat(distinct tilausrivi.perheid2) as perheid2set,
          group_concat(concat(tuote.tunnus,'!°!', tuote.tuoteno)) as kaikkituotteet
          FROM lasku use index (yhtio_tila_tapvm)
          JOIN tilausrivi use index (yhtio_otunnus) ON (tilausrivi.otunnus=lasku.tunnus and tilausrivi.yhtio=lasku.yhtio and tilausrivi.kpl > 0)
          JOIN tuote use index (tuoteno_index) ON (tuote.yhtio=lasku.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = '')
          LEFT JOIN tullinimike ON (tuote.tullinimike1=tullinimike.cn and tullinimike.kieli = '$yhtiorow[kieli]' and tullinimike.cn != '')
          LEFT JOIN varastopaikat ON (varastopaikat.yhtio=lasku.yhtio and varastopaikat.tunnus=lasku.varasto)
          WHERE lasku.tila = 'G'
          and lasku.alatila = 'V'
          and lasku.kauppatapahtuman_luonne != '999'
          and lasku.yhtio = '$kukarow[yhtio]'
          and lasku.tapvm >= '$vva-$kka-$ppa'
          and lasku.tapvm <= '$vvl-$kkl-$ppl'
          $vainnimikelisa
          $lisavarlisa
          GROUP BY 1,2,3,4,5,6,7,8 $vainnimikegroup $ee_group
          HAVING $maalisa)";
  }

  if ($tapahtumalaji == "kaikki") {
    $query .= " UNION ";
  }

  if ($tapahtumalaji == "kaikki" or $tapahtumalaji == "tyomaarays") {

    if ($tapa == "tuonti") {
      $query .= "
            (SELECT
            tyomaarays.tullikoodi AS tullinimike1,
            if (tyomaarays.maa_lahetys='', ifnull(varastopaikat.maa, lasku.yhtio_maa), tyomaarays.maa_lahetys) maalahetys,
            tyomaarays.maa_alkupera AS alkuperamaa,
            if (tyomaarays.maa_maara='', lasku.toim_maa, tyomaarays.maa_maara) maamaara,
            tyomaarays.kuljetusmuoto,
            tyomaarays.kauppatapahtuman_luonne,
            tullinimike.su_vientiilmo su,
            'Tyˆm‰‰r‰ys' as tapa,
            {$vainnimikelisa2_tyom}
            {$ee_kentat}
            max(lasku.tunnus) laskunro,
            max(concat('Huolto','!°!', 'Huolto')) tuoteno_nimitys,
            1 AS kpl,
            tyomaarays.bruttopaino AS paino,
            tyomaarays.tulliarvo AS rivihinta,
            tyomaarays.tulliarvo AS rivihinta_laskutusarvo,
            group_concat(lasku.tunnus) as kaikkitunnukset,
            '' AS kaikkituotteet,
            '' AS perheid2set
            FROM lasku use index (yhtio_tila_tapvm)
            JOIN tyomaarays ON (tyomaarays.yhtio = lasku.yhtio AND tyomaarays.otunnus = lasku.tunnus)
            LEFT JOIN tullinimike ON (tyomaarays.tullikoodi=tullinimike.cn and tullinimike.kieli = '{$yhtiorow['kieli']}' and tullinimike.cn != '')
            LEFT JOIN varastopaikat ON (varastopaikat.yhtio=lasku.yhtio and varastopaikat.tunnus=lasku.varasto)
            WHERE lasku.tila = 'L'
            and lasku.alatila = 'X'
            and lasku.kauppatapahtuman_luonne != '999'
            and lasku.yhtio = '{$kukarow['yhtio']}'
            and lasku.tapvm >= '{$vva}-{$kka}-{$ppa}'
            and lasku.tapvm <= '{$vvl}-{$kkl}-{$ppl}'
            GROUP BY 1,2,3,4,5,6,7,8 {$ee_group}
            HAVING {$maalisa})";
    }
    else {
      $query .= "
            (SELECT
            tuote.tullinimike1,
            if (lasku.maa_lahetys='', ifnull(varastopaikat.maa, lasku.yhtio_maa), lasku.maa_lahetys) maalahetys,
            ifnull((SELECT alkuperamaa FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.alkuperamaa!='' ORDER BY if (alkuperamaa='$yhtiorow[maa]','2','1') LIMIT 1), '$yhtiorow[maa]') alkuperamaa,
            if (lasku.maa_maara='', lasku.toim_maa, lasku.maa_maara) maamaara,
            lasku.kuljetusmuoto,
            lasku.kauppatapahtuman_luonne,
            tullinimike.su_vientiilmo su,
            'Tyˆm‰‰r‰ys' as tapa,
            {$vainnimikelisa2}
            {$ee_kentat}
            max(lasku.tunnus) laskunro,
            max(concat(tuote.tuoteno,'!°!',left(tuote.nimitys, 40))) tuoteno_nimitys,
            round(sum(tilausrivi.kpl * if (tuote.toinenpaljous_muunnoskerroin = 0, 1, tuote.toinenpaljous_muunnoskerroin)),0) kpl,
            round(sum(if(tuote.tuotemassa > 0, tilausrivi.kpl * tuote.tuotemassa, if(lasku.summa > tilausrivi.rivihinta, tilausrivi.rivihinta / lasku.summa, 1) * lasku.bruttopaino)), 0) as paino,
            if (round(sum(tilausrivi.rivihinta),0) > 0.50, round(sum(tilausrivi.rivihinta),0), 1) rivihinta,
            if (round(sum(tilausrivi.rivihinta),0) > 0.50,round(sum(tilausrivi.rivihinta),0), 1) rivihinta_laskutusarvo,
            group_concat(lasku.tunnus) as kaikkitunnukset,
            group_concat(distinct tilausrivi.perheid2) as perheid2set,
            group_concat(concat(tuote.tunnus,'!°!', tuote.tuoteno)) as kaikkituotteet
            FROM lasku use index (yhtio_tila_tapvm)
            JOIN tilausrivi use index (yhtio_otunnus) ON (tilausrivi.otunnus=lasku.tunnus and tilausrivi.yhtio=lasku.yhtio and tilausrivi.kpl > 0)
            JOIN tuote use index (tuoteno_index) ON (tuote.yhtio=lasku.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = '')
            LEFT JOIN tullinimike ON (tuote.tullinimike1=tullinimike.cn and tullinimike.kieli = '{$yhtiorow['kieli']}' and tullinimike.cn != '')
            LEFT JOIN varastopaikat ON (varastopaikat.yhtio=lasku.yhtio and varastopaikat.tunnus=lasku.varasto)
            LEFT JOIN asiakas on (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus)
            WHERE lasku.tila = 'L'
            and lasku.alatila = 'X'
            and lasku.tilaustyyppi = 'A'
            and lasku.kauppatapahtuman_luonne != '999'
            and not (lasku.vienti = '' and asiakas.laji = 'H')
            and lasku.yhtio = '{$kukarow['yhtio']}'
            and lasku.tapvm >= '{$vva}-{$kka}-{$ppa}'
            and lasku.tapvm <= '{$vvl}-{$kkl}-{$ppl}'
            {$vainnimikelisa}
            {$lisavarlisa}
            GROUP BY 1,2,3,4,5,6,7,8 {$vainnimikegroup} {$ee_group}
            HAVING {$maalisa})";
    }
  }

  $query .= "  ORDER BY $ee_yhdistettyorder tullinimike1, maalahetys, alkuperamaa, maamaara, kuljetusmuoto, kauppatapahtuman_luonne, laskunro, tuoteno_nimitys";
  $result = pupe_query($query);

  $nim     = "";
  $lask    = 1;
  $arvoyht = 0;
  $virhe   = 0;

  $lopetus_intra1 = "{$palvelin2}intrastat.php////tee=tulosta//kk=$kk//vv=$vv//tapa=$tapa//outputti=$outputti//lahetys=nope//kayttajan_valinta_maa=$kayttajan_valinta_maa//tapahtumalaji=$tapahtumalaji";
  $lopetus_intra2 = "";

  if ($vaintullinimike != "") {
    $lopetus_intra2 = "//vaintullinimike={$vaintullinimike}//vainmaalahetys={$vainmaalahetys}//vainalkuperamaa={$vainalkuperamaa}//vainmaamaara={$vainmaamaara}//vainkuljetusmuoto={$vainkuljetusmuoto}//vainkauppatapahtuman_luonne={$vainkauppatapahtuman_luonne}//vainsu={$vainsu}";
  }

  if ($outputti == "tilasto") {
    // tehd‰‰n tilastoarvot listausta
    $tilastoarvot = "<table><tr>";

    if ($maa == "EE") {
      $tilastoarvot .= "
          <th>".t("Luontipvm")."</th>
          <th>".t("Vuosi")."</th>
          <th>".t("Kuukausi")."</th>
          <th>".t("Tuonti tai vienti")."</th>
          <th>".t("Ytunnus")."</th>
          <th>".t("Rivinro")."</th>
          <th>".t("Toimitusehto")."</th>
          <th>".t("Saapumisen l‰hetysmaa")."</th>
          <th>".t("Kuljetusmuoto")."</th>
          <th>".t("L‰hetysmaa")."</th>
          <th>".t("Kauppatapahtuman luonne")."</th>
          <th>".t("Alkuper‰maa")."</th>
          <th>".t("M‰‰r‰maa")."</th>
          <th>".t("Tullinimike")."</th>
          <th>".t("Paino")."</th>
          <th>".t("Kpl")."</th>
          <th>".t("2. paljous")."</th>
          <th>".t("Laskutusarvo")."</th>
          <th>".t("Ostolaskun valuutta")."</th>
          <th>".t("Tilastoarvo")."</th>
          <th>".t("Yhtiˆn valuutta")."</th>
          <th>".t("Tullinimikkeen nimitys")."</th>
          </tr>";
    }
    else {
      $tilastoarvot .= "
          <th>#</th>
          <th>".t("Tullinimike")."</th>
          <th>".t("Alkuper‰maa")."</th>
          <th>".t("L‰hetysmaa")."</th>
          <th>".t("M‰‰r‰maa")."</th>
          <th>".t("Kuljetusmuoto")."</th>
          <th>".t("Kauppat. luonne")."</th>
          <th>".t("Tilastoarvo")."</th>
          <th>".t("Paino")."</th>
          <th>".t("2-paljous")."</th>
          <th>".t("2-paljous m‰‰r‰")."</th>
          <th>".t("Laskutusarvo")."</th>
          </tr>";
    }

    if (isset($worksheet)) {
      if ($maa == "EE") {
        $worksheet->write($excelrivi, 1, t("Luontipvm"), $format_bold);
        $worksheet->write($excelrivi, 2, t("Vuosi"), $format_bold);
        $worksheet->write($excelrivi, 3, t("Kuukausi"), $format_bold);
        $worksheet->write($excelrivi, 4, t("Tuonti tai vienti"), $format_bold);
        $worksheet->write($excelrivi, 5, t("Ytunnus"), $format_bold);
        $worksheet->write($excelrivi, 6, t("Rivinro"), $format_bold);
        $worksheet->write($excelrivi, 7, t("Toimitusehto"), $format_bold);
        $worksheet->write($excelrivi, 8, t("Saapumisen l‰hetysmaa"), $format_bold);
        $worksheet->write($excelrivi, 9, t("Kuljetusmuoto"), $format_bold);
        $worksheet->write($excelrivi, 10, t("L‰hetysmaa"), $format_bold);
        $worksheet->write($excelrivi, 11, t("Kauppatapahtuman luonne"), $format_bold);
        $worksheet->write($excelrivi, 12, t("Alkuper‰maa"), $format_bold);
        $worksheet->write($excelrivi, 13, t("M‰‰r‰maa"), $format_bold);
        $worksheet->write($excelrivi, 14, t("Tullinimike"), $format_bold);
        $worksheet->write($excelrivi, 15, t("Paino"), $format_bold);
        $worksheet->write($excelrivi, 16, t("Kpl"), $format_bold);
        $worksheet->write($excelrivi, 17, t("2. paljous"), $format_bold);
        $worksheet->write($excelrivi, 18, t("Laskutusarvo"), $format_bold);
        $worksheet->write($excelrivi, 19, t("Ostolaskun valuutta"), $format_bold);
        $worksheet->write($excelrivi, 20, t("Tilastoarvo"), $format_bold);
        $worksheet->write($excelrivi, 21, t("Yhtiˆn valuutta"), $format_bold);
        $worksheet->write($excelrivi, 22, t("Tullinimikkeen nimitys"), $format_bold);
      }
      else {
        $worksheet->write($excelrivi, 1, "Tullinimike", $format_bold);
        $worksheet->write($excelrivi, 2, "Alkuper‰maa", $format_bold);
        $worksheet->write($excelrivi, 3, "L‰hetysmaa", $format_bold);
        $worksheet->write($excelrivi, 4, "M‰‰r‰maa", $format_bold);
        $worksheet->write($excelrivi, 5, "Kuljetusmuoto", $format_bold);
        $worksheet->write($excelrivi, 6, "Kauppat. luonne", $format_bold);
        $worksheet->write($excelrivi, 7, "Tilastoarvo", $format_bold);
        $worksheet->write($excelrivi, 8, "Paino", $format_bold);
        $worksheet->write($excelrivi, 9, "KM", $format_bold);
        $worksheet->write($excelrivi, 10, "2-paljous", $format_bold);
        $worksheet->write($excelrivi, 11, "2-paljous m‰‰r‰", $format_bold);
        $worksheet->write($excelrivi, 12, "Laskutusarvo", $format_bold);
      }
      $excelrivi++;
    }

  }
  else {
    // tehd‰‰n kaunista ruutukamaa
    $ulos = "<table>";
    $ulos .= "<tr>";
    $ulos .= "<th>".t("Laskunro")."</th>";
    $ulos .= "<th>".t("Tuoteno")."</th>";
    $ulos .= "<th>".t("Nimitys")."</th>";
    $ulos .= "<th>".t("Tullinimike")."</th>";
    $ulos .= "<th>".t("KT")."</th>";
    $ulos .= "<th>".t("AM")."</th>";
    $ulos .= "<th>".t("LM")."</th>";
    $ulos .= "<th>".t("MM")."</th>";
    $ulos .= "<th>".t("KM")."</th>";
    $ulos .= "<th>".t("Rivihinta")."</th>";
    $ulos .= "<th>".t("Paino")."</th>";
    $ulos .= "<th>".t("2. paljous")."</th>";
    $ulos .= "<th>".t("M‰‰r‰")."</th>";

    if ($lisavar == "S") {
      $ulos .= "<th>".t("Tehdaslis‰varusteet")."</th>";
    }

    $ulos .= "<th>".t("Virhe")."</th>";
    $ulos .= "</tr>";

    if (isset($worksheet)) {
      $worksheet->write($excelrivi, 1, "Laskunro", $format_bold);
      $worksheet->write($excelrivi, 2, "Tuoteno", $format_bold);
      $worksheet->write($excelrivi, 3, "Nimitys", $format_bold);
      $worksheet->write($excelrivi, 4, "Tullinimike", $format_bold);
      $worksheet->write($excelrivi, 5, "KT", $format_bold);
      $worksheet->write($excelrivi, 6, "AM", $format_bold);
      $worksheet->write($excelrivi, 7, "LM", $format_bold);
      $worksheet->write($excelrivi, 8, "MM", $format_bold);
      $worksheet->write($excelrivi, 9, "KM", $format_bold);
      $worksheet->write($excelrivi, 10, "Rivihinta", $format_bold);
      $worksheet->write($excelrivi, 11, "Paino", $format_bold);
      $worksheet->write($excelrivi, 12, "2. paljous", $format_bold);
      $worksheet->write($excelrivi, 13, "Kpl", $format_bold);
      if ($lisavar == "S") {
        $worksheet->write($excelrivi, 12, "Tehdaslis‰varusteet", $format_bold);
      }
      $excelrivi++;
    }
  }

  // 1. L‰hett‰j‰tietue

  // ytunnus konekielell‰
  $ytunnus = sprintf('%08d', str_replace('-', '', $yhtiorow["ytunnus"]));

  // Suomen maatunnus intrastatiksi
  $maatunnus = "0037";

  // ytunnuksen lis‰osa
  $ylisatunnus = $yhtiorow["int_koodi"];

  $lah  = sprintf('%-3.3s',     "KON");
  $lah .= sprintf('%-17.17s', $maatunnus.$ytunnus.$ylisatunnus);
  $lah .= "\r\n";

  // 2. Otsikkotietue
  // p‰iv‰n numero
  $pvanumero = sprintf('%03d', date('z')+1);
  $vuosi = sprintf('%02d', substr($vv, -2));
  $kuuka = sprintf('%02d', $kk);

  $ots  = sprintf('%-3.3s',     "OTS");                                                  //tietuetunnus
  $ots .= sprintf('%-13.13s',   date("y").$yhtiorow["tilastotullikamari"].$pvanumero.$yhtiorow["intrastat_sarjanro"].$tilastoloppu);  //Tilastonumero
  $ots .= sprintf('%-1.1s',    $laji);                                                  //Onko tuotia vai vienti‰, kts alkua...
  $ots .= sprintf('%-4.4s',    $vuosi.$kuuka);                                              //tilastointijakso
  $ots .= sprintf('%-3.3s',    "T");                                                   //tietok‰sittelykoodi
  $ots .= sprintf('%-13.13s',  "");                                                   //virheellisen tilastonro, tyhj‰ksi j‰tet‰‰n....
  $ots .= sprintf('%-17.17s',   "FI".$ytunnus.$ylisatunnus);                                      //tiedoantovelvollinen
  $ots .= sprintf('%-17.17s',   "");                                                  //t‰h‰n vois laittaa asiamiehen tiedot...
  $ots .= sprintf('%-10.10s',   "");                                                  //t‰h‰n vois laittaa asiamiehen lis‰tiedot...
  $ots .= sprintf('%-17.17s',   $yhtiorow["tilastotullikamari"]);                                    //tilastotullikamari
  $ots .= sprintf('%-3.3s',     $yhtiorow["valkoodi"]);                                          //valuutta
  $ots .= "\r\n";

  while ($row = mysql_fetch_array($result)) {

    list($row["tuoteno"], $row["nimitys"]) = explode("!°!", $row["tuoteno_nimitys"]);

    if ($row["paino"] < 1) $row["paino"] = 1;

    // tehd‰‰n tarkistukset  vai jos EI OLE k‰ytt‰j‰n valitsemaa maata
    if ($kayttajan_valinta_maa == "") {
      require "inc/intrastat_tarkistukset.inc";
    }

    if ($row["perheid2set"] != "0" and $lisavar == "S") {
      $query  = "  SELECT ";

      if ($row["tapa"] != "Saapuminen") {
        $query .= "  if (round(sum((tilausrivi.kpl * tilausrivi.hinta * lasku.vienti_kurssi *
              (SELECT if (tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin) FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno LIMIT 1)
              / lasku.summa) * lasku.bruttopaino), 0) > 0.5,
              round(sum((tilausrivi.kpl * tilausrivi.hinta * lasku.vienti_kurssi *
              (SELECT if (tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin) FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno LIMIT 1)
              / lasku.summa) * lasku.bruttopaino), 0), 1) as paino,
              if (round(sum(tilausrivi.rivihinta),0) > 0.50, round(sum(tilausrivi.rivihinta),0), 1) rivihinta";
      }
      elseif ($row["tapa"] != "Lasku") {
        $query .= "  if (round(sum((tilausrivi.rivihinta/lasku.summa)*lasku.bruttopaino),0) > 0.5, round(sum((tilausrivi.rivihinta/lasku.summa)*lasku.bruttopaino),0), if (round(sum(tilausrivi.kpl*tuote.tuotemassa),0) > 0.5, round(sum(tilausrivi.kpl*tuote.tuotemassa),0),1)) paino,
              if (round(sum(tilausrivi.rivihinta),0) > 0.50,round(sum(tilausrivi.rivihinta),0), 1) rivihinta";
      }
      else {
        $query .= "  if (round(sum((tilausrivi.rivihinta/lasku.summa)*lasku.bruttopaino),0) > 0.5, round(sum((tilausrivi.rivihinta/lasku.summa)*lasku.bruttopaino),0), if (round(sum(tilausrivi.kpl*tuote.tuotemassa),0) > 0.5, round(sum(tilausrivi.kpl*tuote.tuotemassa),0),1)) paino,
              round(sum(tilausrivi.rivihinta), 0) rivihinta";
      }

      $query .= "  FROM tilausrivi use index (yhtio_perheid2)
            JOIN lasku ON tilausrivi.otunnus = lasku.tunnus and tilausrivi.yhtio = lasku.yhtio
            JOIN tuote use index (tuoteno_index) ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = ''
            WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
            and tilausrivi.kpl > 0
            and tilausrivi.perheid2 > 0
            and tilausrivi.perheid2 != tilausrivi.tunnus
            and tilausrivi.perheid2 in ($row[perheid2set])";
      $lisavarres = pupe_query($query);
      $lisavarrow = mysql_fetch_array($lisavarres);

      $row["paino"]     += $lisavarrow["paino"];
      $row["rivihinta"]   += $lisavarrow["rivihinta"];

    }

    // 3. Nimiketietue
    $nim .= sprintf('%-3.3s',     "NIM");                                                //tietuetunnus
    $nim .= sprintf('%05d',     $lask);                                                //j‰rjestysnumero
    $nim .= sprintf('%-8.8s',     $row["tullinimike1"]);                                        //Tullinimike CN
    $nim .= sprintf('%-2.2s',     $row["kauppatapahtuman_luonne"]);                                  //kauppatapahtuman luonne

    if ($tapa == "tuonti") {
      $nim .= sprintf('%-2.2s',   $row["alkuperamaa"]);                                        //alkuper‰maa
      $nim .= sprintf('%-2.2s',   $row["maalahetys"]);                                        //l‰hetysmaa
      $nim .= sprintf('%-2.2s',   "");
    }
    else {
      $nim .= sprintf('%-2.2s',   "");
      $nim .= sprintf('%-2.2s',   "");
      $nim .= sprintf('%-2.2s',   $row["maamaara"]);                                          //m‰‰r‰maa
    }

    $nim .= sprintf('%-1.1s',     $row["kuljetusmuoto"]);                                        //kuljetusmuoto
    $nim .= sprintf('%010d',     $row["rivihinta"]);                                          //tilastoarvo
    $nim .= sprintf('%-15.15s',  "");                                                //ilmoitajan viite...
    $nim .= sprintf('%-3.3s',    "WT");                                                //m‰‰r‰ntarkennin 1
    $nim .= sprintf('%-3.3s',    "KGM");                                                //paljouden lajikoodi
    $nim .= sprintf('%010d',     $row["paino"]);                                            //nettopaino
    $nim .= sprintf('%-3.3s',    "AAE");                                                //m‰‰r‰ntarkennin 2, muu paljous

    if ($row["su"] != '') {
      $nim .= sprintf('%-3.3s',    $row["su"]);                                           //2 paljouden lajikoodi
      $nim .= sprintf('%010d',     $row["kpl"]);                                          //2 paljouden m‰‰r‰
    }
    else {
      $nim .= sprintf('%-3.3s',    "");                                               //2 paljouden lajikoodi
      $nim .= sprintf('%010d',     "");                                              //2 paljouden m‰‰r‰
    }

    if ($tapa == "yhdistetty" and $outputti == 'tilasto') {
      $nim .= sprintf('%010d',     $row["rivihinta_laskutusarvo"]);                                          //nimikkeen laskutusarvo
    }
    else {
      $nim .= sprintf('%010d',     $row["rivihinta"]);                                          //nimikkeen laskutusarvo
    }
    $nim .= "\r\n";

    if ($outputti == "tilasto") {
      // tehd‰‰n tilastoarvolistausta
      $tilastoarvot .= "<tr>";
      if ($maa == "EE") {
        $ee_pvm = date("d.m.Y");
        $ee_ilmoitus = ((($row["maamaara"] == $maa or $row["maamaara"] == '') and $row["maalahetys"] != $maa) ? 'S' : 'L');
        $ee_rivi = sprintf('%05d', $lask);
        $ee_maatxt = t_maanimi($row["maalahetys"], 'ee');

        $tilastoarvot .= "
          <td>$ee_pvm</td>
          <td>$vv</td>
          <td>$kuuka</td>
          <td>$ee_ilmoitus</td>
          <td>{$yhtiorow["ytunnus"]}</td>
          <td>$ee_rivi</td>
          <td>{$row["toim_ehto"]}</td>
          <td>{$ee_maatxt}</td>

          <td>{$row["kuljetusmuoto"]}</td>
          <td>{$row["maalahetys"]}</td>
          <td>{$row["kauppatapahtuman_luonne"]}</td>
          <td>{$row["alkuperamaa"]}</td>
          <td>{$row["maamaara"]}</td>
          <td>{$row["tullinimike1"]}</td>
          <td>{$row["paino"]}</td>
          <td>{$row["kpl"]}</td>
          <td>{$row["su"]}</td>";

        if ($tapa == "yhdistetty" and $outputti == 'tilasto') {
          $tilastoarvot .= "<td>{$row["rivihinta_laskutusarvo"]}</td>";
        }
        else {
          $tilastoarvot .= "<td>{$row["rivihinta"]}</td>";
        }

        $tilastoarvot .= "<td>{$row["valkoodi"]}</td>
          <td>{$row["rivihinta"]}</td>
          <td>{$yhtiorow["valkoodi"]}</td>
          <td>{$row["dm"]}</td>
        </tr>";

      }
      else {
        $tilastoarvot .= "<td>$lask</td>";                                                  //j‰rjestysnumero

        $tilastoarvot .= "<td><a href='intrastat.php?tee=tulosta&tapa=$tapa&kk=$kk&vv=$vv&outputti=$outputti&lahetys=nope&lisavar=$lisavar&tapahtumalaji=$tapahtumalaji&vaintullinimike={$row['tullinimike1']}&vainmaalahetys={$row['maalahetys']}&vainalkuperamaa={$row['alkuperamaa']}&vainmaamaara={$row['maamaara']}&vainkuljetusmuoto={$row['kuljetusmuoto']}&vainkauppatapahtuman_luonne={$row['kauppatapahtuman_luonne']}&vainsu={$row['su']}&lopetus=$lopetus_intra1'>$row[tullinimike1]</></td>";  //Tullinimike CN

        if ($tapa == "tuonti") {
          $tilastoarvot .= "<td>$row[alkuperamaa]</td>";                                          //alkuper‰maa
          $tilastoarvot .= "<td>$row[maalahetys]</td>";                                          //l‰hetysmaa
          $tilastoarvot .= "<td></td>";
        }
        else {
          $tilastoarvot .= "<td></td>";
          $tilastoarvot .= "<td></td>";
          $tilastoarvot .= "<td>$row[maamaara]</td>";                                          //m‰‰r‰maa
        }

        $tilastoarvot .= "<td>$row[kuljetusmuoto]</td>";                                          //kuljetusmuoto
        $tilastoarvot .= "<td>$row[kauppatapahtuman_luonne]</td>";                                      //kauppatapahtuman luonne
        $tilastoarvot .= "<td>$row[rivihinta]</td>";                                            //tilastoarvo
        $tilastoarvot .= "<td>$row[paino] | $row[paino2]</td>";                                              //nettopaino

        if ($row["su"] != '') {
          $tilastoarvot .= "<td>{$row["su"]}</td>";                                               //2 paljouden lajikoodi
          $tilastoarvot .= "<td>{$row["kpl"]}</td>";                                              //2 paljouden m‰‰r‰
        }
        else {
          $tilastoarvot .= "<td></td>";                                                   //2 paljouden lajikoodi
          $tilastoarvot .= "<td></td>";                                                  //2 paljouden m‰‰r‰
        }

        $tilastoarvot .= "<td>$row[rivihinta]</td>";                                            //nimikkeen laskutusarvo
        $tilastoarvot .= "</tr>";
      }

      if (isset($worksheet)) {
        if ($maa == "EE") {
          $worksheet->write($excelrivi, 1, $ee_pvm);
          $worksheet->write($excelrivi, 2, $vv);
          $worksheet->write($excelrivi, 3, $kuuka);
          $worksheet->write($excelrivi, 4, $ee_ilmoitus);
          $worksheet->write($excelrivi, 5, $yhtiorow["ytunnus"]);
          $worksheet->write($excelrivi, 6, "{$ee_rivi}");
          $worksheet->write($excelrivi, 7, $row["toim_ehto"]);
          $worksheet->write($excelrivi, 8, $row["maalahetys"]);
          $worksheet->write($excelrivi, 9, $row["kuljetusmuoto"]);
          $worksheet->write($excelrivi, 10, $ee_maatxt);
          $worksheet->write($excelrivi, 11, $row["kauppatapahtuman_luonne"]);
          $worksheet->write($excelrivi, 12, $row["alkuperamaa"]);
          $worksheet->write($excelrivi, 13, $row["maamaara"]);
          $worksheet->write($excelrivi, 14, $row["tullinimike1"]);
          $worksheet->write($excelrivi, 15, $row["paino"]);
          $worksheet->write($excelrivi, 16, $row["kpl"]);
          $worksheet->write($excelrivi, 17, $row["su"]);

          if ($tapa == "yhdistetty" and $outputti == 'tilasto') {
            $worksheet->write($excelrivi, 18, $row["rivihinta_laskutusarvo"]);
          }
          else {
            $worksheet->write($excelrivi, 18, $row["rivihinta"]);
          }

          $worksheet->write($excelrivi, 19, $row["valkoodi"]);
          $worksheet->write($excelrivi, 20, $row["rivihinta"]);
          $worksheet->write($excelrivi, 21, $yhtiorow["valkoodi"]);
          $worksheet->write($excelrivi, 22, $row["dm"]);
        }
        else {
          $worksheet->write($excelrivi, 1, $lask);
          $worksheet->write($excelrivi, 2, $row["tullinimike1"]);

          if ($tapa == "tuonti") {
            $worksheet->write($excelrivi, 3, $row["alkuperamaa"]);
            $worksheet->write($excelrivi, 4, $row["maalahetys"]);

          }
          else {
            $worksheet->write($excelrivi, 5, $row["maamaara"]);

          }

          $worksheet->write($excelrivi, 6, $row["kuljetusmuoto"]);
          $worksheet->write($excelrivi, 7, $row["kauppatapahtuman_luonne"]);
          $worksheet->write($excelrivi, 8, $row["rivihinta"]);
          $worksheet->write($excelrivi, 9, $row["paino"]);
          if ($row["su"] != '') {
            $worksheet->write($excelrivi, 10, $row["su"]);
            $worksheet->write($excelrivi, 11, $row["kpl"]);
          }
          $worksheet->write($excelrivi, 12, $row["rivihinta"]);
        }
        $excelrivi++;
      }
    }
    else {
      // tehd‰‰n kaunista ruutukamaa
      $ulos .= "<tr class='aktiivi'>";

      if ($vaintullinimike != "") {
        $lisatoim = ($row['tapa'] == "Tyˆm‰‰r‰ys" and $tapa == "tuonti") ? "&toim=TYOMAARAYS" : "";
        $ulos .= "<td valign='top'><a href='tilauskasittely/vientitilauksen_lisatiedot.php?tapa=$tapa&tee=K{$lisatoim}&otunnus=$row[kaikkitunnukset]&lopetus=$lopetus/SPLIT/$lopetus_intra1$lopetus_intra2'>$row[laskunro]</a></td>";
      }
      else {
        $ulos .= "<td valign='top'>".$row["laskunro"]."</td>";
      }

      $ulos .= "<td valign='top'>".$row["tuoteno"]."</td>";
      $ulos .= "<td valign='top'>".t_tuotteen_avainsanat($row, 'nimitys')."</td>";
      $ulos .= "<td valign='top'><a href='intrastat.php?tee=tulosta&tapa=$tapa&kk=$kk&vv=$vv&outputti=$outputti&lahetys=nope&lisavar=$lisavar&kayttajan_valinta_maa=$kayttajan_valinta_maa&tapahtumalaji=$tapahtumalaji&vaintullinimike={$row['tullinimike1']}&vainmaalahetys={$row['maalahetys']}&vainalkuperamaa={$row['alkuperamaa']}&vainmaamaara={$row['maamaara']}&vainkuljetusmuoto={$row['kuljetusmuoto']}&vainkauppatapahtuman_luonne={$row['kauppatapahtuman_luonne']}&vainsu={$row['su']}&lopetus=$lopetus_intra1'>$row[tullinimike1]</></td>";  //Tullinimike CN
      $ulos .= "<td valign='top'>".$row["kauppatapahtuman_luonne"]."</td>";
      $ulos .= "<td valign='top'>".$row["alkuperamaa"]."</td>";
      $ulos .= "<td valign='top'>".$row["maalahetys"]."</td>";
      $ulos .= "<td valign='top'>".$row["maamaara"]."</td>";
      $ulos .= "<td valign='top'>".$row["kuljetusmuoto"]."</td>";
      $ulos .= "<td valign='top' align='right'>".$row["rivihinta"]."</td>";
      $ulos .= "<td valign='top' align='right'>".$row["paino"]."</td>";
      if ($row["su"] != "") {
        $ulos .= "<td valign='top'>".$row["su"]."</td>";
        $ulos .= "<td valign='top' align='right'>".$row["kpl"]."</td>";
      }
      else {
        $ulos .= "<td></td>";
        $ulos .= "<td></td>";
      }

      if ($lisavar == "S") {
        if ($row["perheid2set"] != "0") {
          $ulos .= "<td valign='top'>".t("Tehdaslis‰varusteet").":<br>".t("Paino").": $lisavarrow[paino]<br>".t("Arvo").": $lisavarrow[rivihinta]</td>";
        }
        else {
          $ulos .= "<td valign='top'></td>";
        }
      }

      $ulos .= "<td valign='top'><font class='error'>".$virhetxt."</font></td>";
      $ulos .= "</tr>";

      if (isset($worksheet)) {
        $worksheet->write($excelrivi, 1, $row["laskunro"]);
        $worksheet->write($excelrivi, 2, $row["tuoteno"]);
        $worksheet->write($excelrivi, 3, t_tuotteen_avainsanat($row, 'nimitys'));
        $worksheet->write($excelrivi, 4, $row["tullinimike1"]);
        $worksheet->write($excelrivi, 5, $row["kauppatapahtuman_luonne"]);
        $worksheet->write($excelrivi, 6, $row["alkuperamaa"]);
        $worksheet->write($excelrivi, 7, $row["maalahetys"]);
        $worksheet->write($excelrivi, 8, $row["maamaara"]);
        $worksheet->write($excelrivi, 9, $row["kuljetusmuoto"]);
        $worksheet->write($excelrivi, 10, $row["rivihinta"]);
        $worksheet->write($excelrivi, 11, $row["paino"]);
        if ($row["su"] != '') {
          $worksheet->write($excelrivi, 12, $row["su"]);
          $worksheet->write($excelrivi, 13, $row["kpl"]);
        }
        if ($lisavar == "S") {
          $worksheet->write($excelrivi, 12, $lisavarrow["paino"]."kg/".$lisavarrow["rivihinta"]."eur");
        }
        $excelrivi++;
      }
    }

    // summaillaan
    $lask++;
    $arvoyht    += $row["rivihinta"];
    $bruttopaino  += $row["paino"];
    $totsumma    += $row["rivihinta"];

    if ($row["su"] != '') {
      $totkpl += $row["kpl"];
    }
  }

  // 4. Summatietue
  $sum  = sprintf('%-3.3s',     "SUM");                                                  //tietuetunnus
  $sum .= sprintf('%018d',     $lask-1);                                                //nimikkeiden lukum‰‰r‰
  $sum .= sprintf('%018d',     $arvoyht);                                                //laskutusarvo yhteens‰
  $sum .= "\r\n";

  if ($outputti == "tilasto") {
    // tehd‰‰n tilaustoarvolistausta
    $tilastoarvot .= "<tr>";
    $span = ($maa == "EE" ? 19 : 7);
    $tilastoarvot .= "<th colspan='$span'>".t("Yhteens‰").":</td>";
    $tilastoarvot .= "<th>$arvoyht</th>";
    $tilastoarvot .= "<th colspan='4'></th>";
    $tilastoarvot .= "</tr>";
    $tilastoarvot .= "</table>";

    if (isset($worksheet)) {
      if ($maa == "EE") {
        $worksheet->write($excelrivi, 20, $arvoyht, $format_bold);
      }
      else {
        $worksheet->write($excelrivi, 8, $arvoyht, $format_bold);
      }
    }
  }
  else {
    // tehd‰‰n kaunista ruutukamaa
    $ulos .= "<tr>";
    $ulos .= "<th colspan='9'>".t("Yhteens‰").":</th>";
    $ulos .= "<th>$totsumma</th>";
    $ulos .= "<th>$bruttopaino</th>";
    $ulos .= "<th></th>";
    $ulos .= "<th>$totkpl</th>";
    $ulos .= "<th></th>";

    if ($lisavar == "S") {
      $ulos .= "<th></th>";
    }

    $ulos .= "</tr>";
    $ulos .= "</table>";

    if (isset($worksheet)) {
      $worksheet->write($excelrivi, 10, $totsumma, $format_bold);
      $worksheet->write($excelrivi, 11, $bruttopaino, $format_bold);
      $worksheet->write($excelrivi, 13, $totkpl, $format_bold);
    }
  }

  if (($lahetys == "mina" or $lahetys == "tuli" or $lahetys == "mole") and ($yhtiorow["tilastotullikamari"] == 0 or $yhtiorow["intrastat_sarjanro"] == "")) {
    echo "<font class='error'>".t("Yhtiˆtiedoista puuttuu pakollisia tietoja (tilastotullikamari/intrastat_sarjanro)").!"</font>";
    $virhe++;
  }

  // ei virheit‰ .. ja halutaan l‰hett‰‰ jotain meilej‰
  if ($virhe == 0 and $lahetys != "nope" and $kayttajan_valinta_maa == '' and $tapahtumalaji == "kaikki") {

    //PGP-encryptaus labeli
    $label  = '';
    $label .= "l‰hett‰j‰: $yhtiorow[nimi]\r\n";

    if ($tapa == "tuonti") {
      $label .= "sis‰ltˆ: sis‰kaupantilasto\r\n";
    }
    else {
      $label .= "sis‰ltˆ: vientitullaus\r\n";
    }

    $label .= "kieli: ASCII\r\n";
    $label .= "jakso: $vuosi$kuuka\r\n";
    $label .= "koko aineiston tietuem‰‰r‰: ".($lask-1)."\r\n";
    $label .= "koko aineiston vienti-, verotus- tai laskutusarvo: $arvoyht\r\n";

    $recipient = "pgp-key Customs Finland <ascii.intra@tulli.fi>";         // t‰m‰ on tullin virallinen avain

    if ($lahetys == "test" or $lahetys == "teme") {
      $recipient = "pgp-testkey Customs Finland <test.ascii.intra@tulli.fi>";   // t‰m‰ on tullin testiavain
    }

    $message = '';
    $message = $label;
    require "inc/gpg.inc";
    $otsikko_gpg = $encrypted_message;
    $otsikko_plain = $message;

    //PGP-encryptaus atktietue
    $recipient = "pgp-key Customs Finland <ascii.intra@tulli.fi>";         // t‰m‰ on tullin virallinen avain

    if ($lahetys == "test" or $lahetys == "teme") {
      $recipient = "pgp-testkey Customs Finland <test.ascii.intra@tulli.fi>";   // t‰m‰ on tullin testiavain
    }

    $message = '';
    $message = $lah.$ots.$nim.$sum;
    require "inc/gpg.inc";
    $tietue_gpg = $encrypted_message;
    $tietue_plain = $message;

    $bound = uniqid(time()."_") ;

    $header  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n";
    $header .= "MIME-Version: 1.0\n";
    $header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n";

    $content = "--$bound\n";

    $content .= "Content-Type: application/pgp-encrypted;\n" ;
    $content .= "Content-Transfer-Encoding: base64\n" ;
    $content .= "Content-Disposition: attachment; filename=\"otsikko.pgp\"\n\n";
    $content .= chunk_split(base64_encode($otsikko_gpg));
    $content .= "\n";

    $content .= "--$bound\n";

    $content .= "Content-Type: application/pgp-encrypted;\n";
    $content .= "Content-Transfer-Encoding: base64\n";
    $content .= "Content-Disposition: attachment; filename=\"tietue.pgp\"\n\n";
    $content .= chunk_split(base64_encode($tietue_gpg));
    $content .= "\n";

    $content .= "--$bound\n";

    if ($lahetys == "tuli" or $lahetys == "mole") {
      // l‰hetet‰‰n meili tulliin
      $to = 'ascii.intrastat@tulli.fi';      // t‰m‰ on tullin virallinen osoite
      mail($to, "", $content, $header, "-f $yhtiorow[postittaja_email]");
      echo "<font class='message'>".t("Tiedot l‰hetettiin tulliin").".</font><br><br>";
    }
    elseif ($lahetys == "test") {
      // l‰hetet‰‰n TESTI meili tulliin
      $to = 'test.ascii.intrastat@tulli.fi';    // t‰m‰ on tullin testiosoite
      mail($to, "", $content, $header, "-f $yhtiorow[postittaja_email]");
      echo "<font class='message'>".t("Testitiedosto l‰hetettiin tullin testipalvelimelle").".</font><br><br>";
    }
    else {
      echo "<font class='message'>".t("Tietoja EI l‰hetetty tulliin").".</font><br><br>";
    }

    // katotaan l‰hetet‰‰nkˆ meili k‰ytt‰j‰lle
    if (($lahetys == "mina" or $lahetys == "mole" or $lahetys == "test" or $lahetys == "teme") and $kukarow["eposti"] != "") {
      // j‰ l‰hetet‰‰n k‰ytt‰j‰lle
      mail($kukarow["eposti"], mb_encode_mimeheader("$yhtiorow[nimi] - ".t("Intrastat")." ".t($tapa)."-".t("ilmoitus")." $vv/$kk ($kukarow[kuka])", "ISO-8859-1", "Q"), $content, $header, "-f $yhtiorow[postittaja_email]");
    }

    // liitet‰‰n mukaan myˆs salaamattomat tiedostot
    $content .= "Content-Type: text/plain;\n" ;
    $content .= "Content-Transfer-Encoding: base64\n" ;
    $content .= "Content-Disposition: attachment; filename=\"otsikko.txt\"\n\n";
    $content .= chunk_split(base64_encode($otsikko_plain));
    $content .= "\n";

    $content .= "--$bound\n";

    $content .= "Content-Type: text/plain;\n";
    $content .= "Content-Transfer-Encoding: base64\n";
    $content .= "Content-Disposition: attachment; filename=\"tietue.txt\"\n\n";
    $content .= chunk_split(base64_encode($tietue_plain));
    $content .= "\n";

    $content .= "--$bound\n";

    // ja aina adminille
    mail($yhtiorow["alert_email"], mb_encode_mimeheader("$yhtiorow[nimi] - ".t("Intrastat")." ".t($tapa)."-".t("ilmoitus")." $vv/$kk ($kukarow[kuka])", "ISO-8859-1", "Q"), $content, $header, "-f $yhtiorow[postittaja_email]");
  }
  else {
    if ($virhe != 0) {
      echo "<font class='error'>".t("Aineistossa on virheit‰")."! ".t("Korjaa virheet")."!</font><br>";
    }
    echo "<font class='error'>".t("Aineistoa EI l‰hetetty minnek‰‰n").".</font><br><br>";
  }

  if ($kayttajan_valinta_maa != "") {
    echo "<font class='error'>".t("Poikkeava ilmoitusmaa valittu, mit‰‰n oikeellisuustarkistuksia ei tehty")."!</font><br><br>";
  }

  // echotaan oikea taulukko ruudulle
  if ($outputti == "tilasto") {
    echo "$tilastoarvot";
  }
  else {
    echo "$ulos";
  }

  if (isset($worksheet) and $virhe == 0) {
    // We need to explicitly close the worksheet
    $excelnimi = $worksheet->close();

    echo "<br><table>";
    echo "<tr><th>".t("Tallenna tulos").":</th>";
    echo "<form method='post' class='multisubmit'>";
    echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
    echo "<input type='hidden' name='kaunisnimi' value='Intrastat.xlsx'>";
    echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
    echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
    echo "</table><br>";
  }
}

// K‰yttˆliittym‰
if (!isset($kk)) $kk = date("m");
if (!isset($vv)) $vv = date("Y");

if ($tapa == "vientituonti") $tapa = "vienti";
if ($tapa == "tuontivienti") $tapa = "tuonti";

if ($maa == "EE" and $tapa == "") {
  $tapa = "yhdistetty";
}

$sel1[$outputti]     = "SELECTED";
$sel2[$tapa]         = "SELECTED";
$sel3[$lahetys]      = "SELECTED";
$sel4[$lisavar]      = "SELECTED";

$_lajit = array(
  'kaikki' => '',
  'keikka' => '',
  'lasku' => '',
  'siirtolista' => '',
  'tyomaarays' => ''
);

$sel5 = array($tapahtumalaji => 'selected') + $_lajit;

if ($excel != "") {
  $echecked = "checked";
}
else {
  $echecked = "";
}

echo "<br>
<form method='post' action='intrastat.php'>
<input type='hidden' name='tee' value='tulosta'>

<table>
  <tr>
    <th>".t("Valitse ilmoitus")."</th>
    <td>
      <select name='tapa'>
      <option value='vienti' $sel2[vienti]>".t("Vienti-ilmoitus")."</option>
      <option value='tuonti' $sel2[tuonti]>".t("Tuonti-ilmoitus")."</option>";
if ($maa == "EE") {
  echo "<option value='yhdistetty' $sel2[yhdistetty]>".t("Yhdistetty")."</option>";
}
echo"
      </select>
    </td>
  </tr>";

$query = "SELECT tunnus from yhtion_toimipaikat where yhtio = '$kukarow[yhtio]' and vat_numero != ''";
$vresult = pupe_query($query);

if (mysql_num_rows($vresult) > 0) {
  echo "<tr>
      <th>".t("Valitse poikkeava ilmoitusmaa")."</th>
      <td>
        <select name='kayttajan_valinta_maa'>";
  echo "<option value='' $sel>".t("Ei valintaa")."</option>";

  $query = "SELECT distinct koodi, nimi
            FROM maat
            where nimi != '' and eu != '' and koodi != '$yhtiorow[maa]'
            ORDER BY koodi";
  $vresult = pupe_query($query);

  while ($row = mysql_fetch_array($vresult)) {
    $sel = '';
    if ($row[0] == $kayttajan_valinta_maa) {
      $sel = 'selected';
    }
    echo "<option value='$row[0]' $sel>$row[1]</option>";
  }

  echo "    </select>
      </td>
    </tr>";
}

echo "<tr>
    <th>".t("Syˆt‰ kausi (kk-vvvv)")."</th>
    <td>
      <input type='text' name='kk' value='$kk' size='3'>
      <input type='text' name='vv' value='$vv' size='5'>
    </td>
  </tr>
  <tr>
    <th>".t("N‰yt‰ ruudulla")."</th>
    <td>
      <select name='outputti'>
      <option value='normi'   $sel1[normi]>".t("Normaalilistaus")."</option>
      <option value='tilasto' $sel1[tilasto]>".t("Tilastoarvolistaus")."</option>
      </select>
    </td>
  </tr>
  <tr>
    <th>".t("Tietojen l‰hetys s‰hkˆpostilla")."</th>
    <td>
    <select name='lahetys'>
    <option value='nope' $sel3[nope]>".t("ƒl‰ l‰het‰ aineistoa minnek‰‰n")."</option>
    <option value='mina' $sel3[mina]>".t("L‰het‰ aineisto vain minulle")."</option>
    <option value='tuli' $sel3[tuli]>".t("L‰het‰ aineisto vain tulliin")."</option>
    <option value='mole' $sel3[mole]>".t("L‰het‰ aineisto tulliin sek‰ minulle")."</option>
    <option value='test' $sel3[test]>".t("L‰het‰ testiaineisto tullin testipalvelimelle")."</option>
    <option value='teme' $sel3[teme]>".t("L‰het‰ testiaineisto vain minulle")."</option>
    </select>
  </tr>
  <tr>
    <th>".t("Tehdaslis‰varusteet")."</th>
    <td>
    <select name='lisavar'>
    <option value='O' $sel4[O]>".t("Omilla riveill‰‰n")."</option>
    <option value='S' $sel4[S]>".t("Yhdistet‰‰n laitteeseen")."</option>
    </select>
  </tr>
  <tr>
    <th>".t("Tallenna excel")."</th>
    <td>
    <input type='checkbox' name='excel' $echecked>
    </select>
  </tr>
    <th>".t("Valitse tapahtumalaji")."</th>
    <td>
    <select name='tapahtumalaji'>
    <option value='kaikki' {$sel5['kaikki']}>".t("Kaikki")."</option>
    <option value='keikka' {$sel5['keikka']}>".t("Saapuminen")."</option>
    <option value='lasku' {$sel5['lasku']}>".t("Lasku")."</option>
    <option value='siirtolista' {$sel5['siirtolista']}>".t("Siirtolista")."</option>
    <option value='tyomaarays' {$sel5['tyomaarays']}>".t("Tyˆm‰‰r‰ys")."</option>
    </select>
</table>

<br>
<input type='submit' value='".t("Luo aineisto")."'>
</form>";

require "inc/footer.inc";
