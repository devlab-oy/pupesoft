<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
pupeslave_start();

if (!isset($monivalintalaatikot)) {
  $monivalintalaatikot = array();
}

if (!isset($monivalintalaatikot_normaali)) {
  $monivalintalaatikot_normaali = array();
}

if (!isset($orderlisa)) {
  $orderlisa = '';
}

if (!isset($noautosubmit)) {
  $autosubmit = " onchange='submit();' ";
}
else {
  $autosubmit = "";
}

if (!isset($piirra_otsikot)) {
  $piirra_otsikot = TRUE;
}

// Näytetäänkö osaston ja tuoteryhmän selitteet
if ($yhtiorow['naytetaanko_osaston_ja_tryn_selite'] == "K") {
  $orderlisa = "ORDER BY avainsana.selitetark";
}
else {
  $orderlisa = "ORDER BY avainsana.jarjestys, avainsana.selite+0";
}

if (!isset($mulselprefix) or $mulselprefix == "") {
  $mulselprefix = "tuote";
}

// trimmataan ja strtoloweroidaan array
$monivalintalaatikot = array_map('trim', $monivalintalaatikot);
$monivalintalaatikot = array_map('strtolower', $monivalintalaatikot);
$monivalintalaatikot_normaali = array_map('trim', $monivalintalaatikot_normaali);
$monivalintalaatikot_normaali = array_map('strtolower', $monivalintalaatikot_normaali);

if (strtoupper($kukarow["kieli"]) != strtoupper($yhtiorow["kieli"])) {
  $kieli_selitetark = "  IFNULL((SELECT avainsana_kieli.selitetark
                  FROM avainsana as avainsana_kieli
                    WHERE avainsana_kieli.yhtio = avainsana.yhtio
                    and avainsana_kieli.laji = avainsana.laji
              and avainsana_kieli.perhe > 0
                    and avainsana_kieli.perhe = avainsana.perhe
                    and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selitetark) selitetark ";

  $kieli_lisa = ".kieli = '$kukarow[kieli]'";
}
else {
  $kieli_selitetark = " avainsana.selitetark ";
  $kieli_lisa = ".kieli in ('$yhtiorow[kieli]', '')";
}

if (strtoupper($kukarow["kieli"]) != strtoupper($yhtiorow["kieli"])) {
  $kieli_selite = "  IFNULL((SELECT avainsana_kieli.selite
            FROM avainsana as avainsana_kieli
              WHERE avainsana_kieli.yhtio = avainsana.yhtio
              and avainsana_kieli.laji = avainsana.laji
            and avainsana_kieli.perhe > 0
              and avainsana_kieli.perhe = avainsana.perhe
              and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selite) selite ";
}
else {
  $kieli_selite = " avainsana.selite ";
}

// Setataan muuttujat
$lisa_haku_toimi         = "";
$lisa_haku_osasto        = "";
$lisa_haku_try           = "";
$lisa_haku_tme           = "";
$lisa_haku_malli         = "";
$lisa_haku_mallitarkenne = "";
$lisa_haku_asiakastila   = "";
$lisa_haku_myyja         = "";
$lisa_haku_asiakasmyyja  = "";
$lisa_haku_ostaja        = "";
$lisa_haku_asiakasosasto = "";
$lisa_haku_asiakasryhma  = "";
$lisa_haku_asiakaspiiri  = "";
$lisa_haku_toimipaikka   = "";
$lisa_haku_maa   = "";
$lisa_haku_kustp         = "";
$lisa_haku_kohde         = "";
$lisa_haku_projekti      = "";
$lisa_parametri          = "";
$lisa_dynaaminen         = array("tuote" => "", "asiakas" => "", "kuka" => "");

$lisa           = isset($lisa) ? trim($lisa) : "";
$ulisa          = isset($ulisa) ? trim($ulisa) : "";
$kieltolisa     = isset($kieltolisa) ? trim($kieltolisa) : "";
$extra_poislisa = isset($extra_poislisa) ? trim($extra_poislisa) : "";
$avainlisa      = isset($avainlisa) ? trim($avainlisa) : "";

if (!isset($piirivalinta) or $piirivalinta == 'lasku') {
  $laskuvalintachk    = "CHECKED";
}
else {
  $asiakasvalintachk  = "CHECKED";
}

if (isset($kustapvalinta) and $kustapvalinta == 'tuote') {
  $kustapvalinta_tuote    = "CHECKED";
}
else {
  $kustapvalinta_asiakas  = "CHECKED";
}

if (!isset($kohdevalinta) or $kohdevalinta == 'tuote') {
  $kohdevalinta_tuote    = "CHECKED";
}
else {
  $kohdevalinta_asiakas  = "CHECKED";
}

if (!isset($projektivalinta) or $projektivalinta == 'tuote') {
  $projektivalinta_tuote    = "CHECKED";
}
else {
  $projektivalinta_asiakas  = "CHECKED";
}

// Setataan muuttujat
if (!isset($mul_toimi))         $mul_toimi = array();
if (!isset($mul_osasto))        $mul_osasto = array();
if (!isset($mul_try))           $mul_try = array();
if (!isset($mul_tme))           $mul_tme = array();
if (!isset($mul_malli))         $mul_malli = array();
if (!isset($mul_mallitarkenne)) $mul_mallitarkenne = array();
if (!isset($mul_laskumyyja))    $mul_laskumyyja = array();
if (!isset($mul_asiakastila))   $mul_asiakastila = array();
if (!isset($mul_tuotemyyja))    $mul_tuotemyyja = array();
if (!isset($mul_asiakasmyyja))  $mul_asiakasmyyja = array();
if (!isset($mul_tuoteostaja))   $mul_tuoteostaja = array();
if (!isset($mul_asiakasosasto)) $mul_asiakasosasto = array();
if (!isset($mul_asiakasryhma))  $mul_asiakasryhma = array();
if (!isset($mul_asiakaspiiri))  $mul_asiakaspiiri = array();
if (!isset($mul_toimipaikka))   $mul_toimipaikka = array();
if (!isset($mul_kustp))         $mul_kustp = array();
if (!isset($mul_kohde))         $mul_kohde = array();
if (!isset($mul_projekti))      $mul_projekti = array();
if (!isset($mul_maa))           $mul_maa = array();

if (!isset($poislisa_mulsel))   $poislisa_mulsel = "";

$dynaaminen_syvintaso = array("tuote" => 0, "asiakas" => 0);
$dynaaminen_check = "";

// Setataan muuttujat
foreach ($monivalintalaatikot as $monivalintalaatikko) {
  $monivalintalaatikko = strip_tags($monivalintalaatikko);

  if (substr($monivalintalaatikko, 0, 9) == "parametri") {
    $param = substr($monivalintalaatikko, 10);

    ${"lisa_haku_".$param} = '';

    if (!isset(${"mul_".$param})) {
      ${"mul_".$param} = array();
    }
  }

  if (substr($monivalintalaatikko, 0, 10) == "dynaaminen") {
    $param = substr($monivalintalaatikko, 11);

    $query = "SELECT DISTINCT (COUNT(node.tunnus) - 1) AS syvyys
              FROM dynaaminen_puu AS node
              JOIN dynaaminen_puu AS parent ON (parent.yhtio = node.yhtio AND parent.laji = node.laji AND parent.lft <= node.lft AND parent.rgt >= node.lft)
              WHERE node.yhtio = '{$kukarow['yhtio']}'
              AND node.laji    = '{$param}'
              AND node.lft     > 1
              GROUP BY node.lft
              ORDER BY syvyys";
    $dynpuu_count_result = pupe_query($query, $link);

    while ($count_row = mysql_fetch_assoc($dynpuu_count_result)) {

      ${"lisa_haku_".$param.$count_row['syvyys']} = '';

      if (!isset(${"mul_".$param.$count_row['syvyys']})) {
        ${"mul_".$param.$count_row['syvyys']} = array();
      }
      else {
        $dynaaminen_check = 'joo';
      }
    }
  }
}

$mul_toimi = array_filter($mul_toimi);

// jos on valittu jotakin dropdowneista (muu kuin osasto) niin tehdään niillä rajaukset muihin dropdowneihin
if (count($mul_toimi) > 0 or count($mul_osasto) > 0 or count($mul_try) > 0 or count($mul_tme) > 0 or trim($dynaaminen_check) != '') {

  if (count($mul_toimi) > 0) {
    $toimittajat = '';

    foreach ($mul_toimi as $toimittaja) {
      $toimittajat .= "$toimittaja,";
      $ulisa .= "&mul_toimi[]=".rawurlencode($toimittaja);
    }

    $toimittajat = trim(substr($toimittajat, 0, -1), ',');

    $query = "SELECT group_concat(quote(tuoteno))
              FROM tuotteen_toimittajat
              WHERE yhtio      = '{$yhtiorow['yhtio']}'
              AND liitostunnus IN ({$toimittajat})";
    $result = pupe_query($query);

    if (mysql_num_rows($result) > 0) {
      $tuotenumerot = mysql_result($result, 0);
      if (trim($tuotenumerot) != '') {
        $tuotenumerot = mysql_result($result, 0);
        $lisa_haku_toimi = " and tuote.tuoteno in ($tuotenumerot) ";
        $lisa .= " and tuote.tuoteno in ($tuotenumerot) ";
      }
    }
  }

  if (count($mul_osasto) > 0) {
    $osastot = '';

    foreach ($mul_osasto as $osx) {
      if (trim($osx) != '') {
        $osx = trim(mysql_real_escape_string($osx));
        $osastot .= "'$osx',";
        $ulisa .= "&mul_osasto[]=".rawurlencode($osx);
      }
    }

    $osastot = substr($osastot, 0, -1);

    if (trim($osastot) != '') {
      $lisa_haku_osasto = " and tuote.osasto in ($osastot) ";
      $lisa .= " and tuote.osasto in ($osastot) ";
    }
  }

  if (count($mul_try) > 0) {
    $tryt = '';

    foreach ($mul_try as $tryx) {
      if (trim($tryx) != '') {
        $tryx = trim(mysql_real_escape_string($tryx));
        $tryt .= "'$tryx',";
        $ulisa .= "&mul_try[]=".rawurlencode($tryx);
      }
    }

    $tryt = substr($tryt, 0, -1);

    if (trim($tryt) != '') {
      $lisa_haku_try = " and tuote.try in ($tryt) ";
      $lisa .= " and tuote.try in ($tryt) ";
    }
  }

  if (count($mul_tme) > 0) {
    $tmet = '';

    foreach ($mul_tme as $tmex) {
      if (trim($tmex) != '') {
        $tmex = trim(mysql_real_escape_string($tmex));
        $tmet .= "'$tmex',";
        $ulisa .= "&mul_tme[]=".rawurlencode($tmex);
      }
    }

    $tmet = substr($tmet, 0, -1);

    if (trim($tmet) != '') {
      $lisa_haku_tme = " and tuote.tuotemerkki in ($tmet) ";
      $lisa .= " and tuote.tuotemerkki in ($tmet) ";
    }
  }

  if (count($mul_malli) > 0) {
    $mallit = '';

    foreach ($mul_malli as $mallix) {
      if (trim($mallix) != '') {
        $mallix = trim(mysql_real_escape_string($mallix));
        $mallit .= "'$mallix',";
        $ulisa .= "&mul_malli[]=".rawurlencode($mallix);
      }
    }

    $mallit = substr($mallit, 0, -1);

    if (trim($mallit) != '') {
      $lisa_haku_malli = " and tuote.malli in ($mallit) ";
      $lisa .= " and tuote.malli in ($mallit) ";
    }
  }

  if (count($mul_mallitarkenne) > 0) {
    $mallitarkenteet = '';

    foreach ($mul_mallitarkenne as $mallitarkennex) {
      if (trim($mallitarkennex) != '') {
        $mallitarkennex = trim(mysql_real_escape_string($mallitarkennex));
        $mallitarkenteet .= "'$mallitarkennex',";
        $ulisa .= "&mul_mallitarkenne[]=".rawurlencode($mallitarkennex);
      }
    }

    $mallitarkenteet = substr($mallitarkenteet, 0, -1);

    if (trim($mallitarkenteet) != '') {
      $lisa_haku_mallitarkenne = " and tuote.mallitarkenne in ($mallitarkenteet) ";
      $lisa .= " and tuote.mallitarkenne in ($mallitarkenteet) ";
    }
  }

  foreach ($monivalintalaatikot as $monivalintalaatikko) {
    $monivalintalaatikko = strip_tags($monivalintalaatikko);

    if (substr($monivalintalaatikko, 0, 9) == "parametri") {
      $param = substr($monivalintalaatikko, 10);

      if (count(${"mul_".$param}) > 0) {
        $paramit = '';

        foreach (${"mul_".$param} as $paramx) {
          if (trim($paramx) != '') {
            $paramx = trim(mysql_real_escape_string($paramx));
            $paramit .= "'$paramx',";
            $ulisa .= "&mul_{$param}[]=".rawurlencode($paramx);
          }
        }

        $paramit = substr($paramit, 0, -1);

        if (trim($paramit) != '') {
          ${"lisa_haku_".$param} = " and t_av_$param.selite in ($paramit) ";

          $lisa_parametri .= " JOIN tuotteen_avainsanat t_av_$param ON (t_av_$param.yhtio = tuote.yhtio and t_av_$param.tuoteno = tuote.tuoteno and t_av_$param$kieli_lisa and t_av_$param.laji = 'parametri_$param' and t_av_$param.selite in ($paramit)) ";
        }
      }
    }
  }
}

// Dynaamisen puun rajaukset1
foreach ($monivalintalaatikot as $monivalintalaatikko) {
  $monivalintalaatikko = strip_tags($monivalintalaatikko);

  if (isset($dynpuu_count_result) and mysql_num_rows($dynpuu_count_result) > 0 and substr($monivalintalaatikko, 0, 10) == "dynaaminen") {
    $param = substr($monivalintalaatikko, 11);

    mysql_data_seek($dynpuu_count_result, 0);

    while ($count_row = mysql_fetch_assoc($dynpuu_count_result)) {

      if (count(${"mul_".$param.$count_row['syvyys']}) > 0) {
        $dynaamiset = '';

        foreach (${"mul_".$param.$count_row['syvyys']} as $dynaaminenx) {
          if (trim($dynaaminenx) != '') {
            $dynaaminenx = trim(mysql_real_escape_string($dynaaminenx));
            $dynaamiset .= "'$dynaaminenx',";
            $ulisa .= "&mul_".$param.$count_row['syvyys']."[]=".rawurlencode($dynaaminenx);
          }
        }

        $dynaamiset = substr($dynaamiset, 0, -1);

        if (trim($dynaamiset) != '') {

          $liitoksetlisa = "";
          $liitoksetlisawhere = "";

          if ($param == "tuote" or $param == "kuka") {
            $liitoksetlisa = ", GROUP_CONCAT(DISTINCT concat('\'',puun_alkio.liitos,'\'')) liitokset ";
            $liitoksetlisawhere = " and puun_alkio.liitos != '' ";
          }
          elseif ($param == "asiakas") {
            $liitoksetlisa = ", GROUP_CONCAT(DISTINCT puun_alkio.liitos) liitokset ";
            $liitoksetlisawhere = " and puun_alkio.liitos != '' ";
          }

          $query = "SELECT GROUP_CONCAT(DISTINCT node.tunnus) tunnukset
                    {$liitoksetlisa}
                    FROM dynaaminen_puu AS node
                    JOIN puun_alkio ON (puun_alkio.yhtio = node.yhtio AND puun_alkio.puun_tunnus = node.tunnus AND puun_alkio.laji = node.laji)
                    JOIN dynaaminen_puu AS parent ON (parent.yhtio = node.yhtio AND parent.laji = node.laji AND parent.lft <= node.lft AND parent.rgt >= node.lft AND parent.tunnus IN ($dynaamiset))
                    WHERE node.yhtio = '{$kukarow['yhtio']}'
                    AND node.laji    = '{$param}'
                    AND node.lft     > 1
                    {$liitoksetlisawhere}
                    ORDER BY node.lft";
          $kaikki_puun_tunnukset_res = pupe_query($query, $link);
          $kaikki_puun_tunnukset_row = mysql_fetch_assoc($kaikki_puun_tunnukset_res);

          if ($kaikki_puun_tunnukset_row["tunnukset"] != "") {
            // Nollataan joinit tässä vaiheessa näin, koska syvempi puun taso rajaa enemmän kuin edelliset tasot, joten turha joinata monta kertaa
            $lisa_dynaaminen[$param] = "";
            $lisa_dynaaminen_liitokset[$param] = "";

            $lisa_dynaaminen[$param] .= "JOIN puun_alkio {$param}_alkio{$count_row['syvyys']} use index (yhtio_laji_puun_tunnus) ON ({$param}_alkio{$count_row['syvyys']}.yhtio = $param.yhtio AND {$param}_alkio{$count_row['syvyys']}.laji = '{$param}' AND {$param}_alkio{$count_row['syvyys']}.puun_tunnus IN ($kaikki_puun_tunnukset_row[tunnukset]) AND {$param}_alkio{$count_row['syvyys']}.liitos = ";

            if ($param == "tuote") {
              $lisa_dynaaminen[$param] .= "tuote.tuoteno)\n";
            }
            elseif ($param == "asiakas") {
              $lisa_dynaaminen[$param] .= "asiakas.tunnus)\n";
            }
            elseif ($param == "kuka") {
              $lisa_dynaaminen[$param] .= "kuka.kuka)\n";
            }

            $lisa_dynaaminen[$param] .= "JOIN dynaaminen_puu dynaaminen_{$param}_{$count_row['syvyys']} ON (dynaaminen_{$param}_{$count_row['syvyys']}.yhtio = {$param}_alkio{$count_row['syvyys']}.yhtio AND dynaaminen_{$param}_{$count_row['syvyys']}.tunnus = {$param}_alkio{$count_row['syvyys']}.puun_tunnus AND dynaaminen_{$param}_{$count_row['syvyys']}.laji = {$param}_alkio{$count_row['syvyys']}.laji)\n\n";
            $dynaaminen_syvintaso[$param] = $count_row['syvyys'];
            $lisa_dynaaminen_liitokset[$param] = $kaikki_puun_tunnukset_row["liitokset"];
          }
        }
      }
    }
  }
}

// Dynaamisen puun rajaukset2
if (isset($lisa_dynaaminen_liitokset) and count($lisa_dynaaminen_liitokset) > 0) {
  foreach ($lisa_dynaaminen_liitokset as $d_param => $d_liitokset) {
    if ($d_liitokset != "") {
      if ($d_param == "tuote") {
        $lisa .= " and tuote.tuoteno in ($d_liitokset) ";
      }
      elseif ($d_param == "asiakas") {
        $lisa .= " and asiakas.tunnus in ($d_liitokset) ";
      }
      elseif ($d_param == "kuka") {
        $lisa .= " and kuka.kuka in ($d_liitokset) ";
      }
    }
  }
}

if (count($mul_laskumyyja) > 0) {
  $laskumyyjat = '';

  foreach ($mul_laskumyyja as $laskumyyjax) {
    if (trim($laskumyyjax) != '') {
      $laskumyyjax = trim(mysql_real_escape_string($laskumyyjax));
      $laskumyyjat .= "'$laskumyyjax',";
      $ulisa .= "&mul_laskumyyja[]=".rawurlencode($laskumyyjax);
    }
  }

  $laskumyyjat = substr($laskumyyjat, 0, -1);

  if (trim($laskumyyjat) != '') {
    if (strpos($_SERVER['SCRIPT_NAME'], "budjetinyllapito_tat.php") !== FALSE) {
      $lisa .= " and kuka.tunnus in ({$laskumyyjat}) ";
    }
    else {
      $lisa .= " and lasku.myyja in ($laskumyyjat) ";
    }
  }
}

if (count($mul_asiakastila) > 0) {
  $asiakastilat = '';

  foreach ($mul_asiakastila as $asiakastilax) {
    if (trim($asiakastilax) != '') {
      $asiakastilax = trim(mysql_real_escape_string($asiakastilax));
      $asiakastilat .= "'$asiakastilax',";
      $ulisa .= "&mul_asiakastila[]=".rawurlencode($asiakastilax);
    }
  }

  $asiakastilat = substr($asiakastilat, 0, -1);

  if (trim($asiakastilat) != '') {
    $lisa_haku_asiakastila = " and asiakas.tila in ($asiakastilat) ";
    $lisa .= " and asiakas.tila in ($asiakastilat) ";
  }
}

if (count($mul_tuotemyyja) > 0) {
  $tuotemyyjat = '';

  foreach ($mul_tuotemyyja as $tuotemyyjax) {
    if (trim($tuotemyyjax) != '') {
      $tuotemyyjax = trim(mysql_real_escape_string($tuotemyyjax));
      $tuotemyyjat .= "'$tuotemyyjax',";
      $ulisa .= "&mul_tuotemyyja[]=".rawurlencode($tuotemyyjax);
    }
  }

  $tuotemyyjat = substr($tuotemyyjat, 0, -1);

  if (trim($tuotemyyjat) != '') {
    $lisa_haku_myyja = " and kuka.myyja in ($tuotemyyjat) ";
    $lisa .= " and tuote.myyjanro in ($tuotemyyjat) ";
  }
}

if (count($mul_asiakasmyyja) > 0) {
  $asiakasmyyjat = '';

  foreach ($mul_asiakasmyyja as $asiakasmyyjax) {
    if (trim($asiakasmyyjax) != '') {
      $asiakasmyyjax = trim(mysql_real_escape_string($asiakasmyyjax));
      $asiakasmyyjat .= "'$asiakasmyyjax',";
      $ulisa .= "&mul_asiakasmyyja[]=".rawurlencode($asiakasmyyjax);
    }
  }

  $asiakasmyyjat = substr($asiakasmyyjat, 0, -1);

  if (trim($asiakasmyyjat) != '') {
    $lisa_haku_asiakasmyyja = " and kuka.myyja in ($asiakasmyyjat) ";
    $lisa .= " and asiakas.myyjanro in ($asiakasmyyjat) ";
  }
}

if (count($mul_tuoteostaja) > 0) {
  $tuoteostajat = '';

  foreach ($mul_tuoteostaja as $tuoteostajax) {
    if (trim($tuoteostajax) != '') {
      $tuoteostajax = trim(mysql_real_escape_string($tuoteostajax));
      $tuoteostajat .= "'$tuoteostajax',";
      $ulisa .= "&mul_tuoteostaja[]=".rawurlencode($tuoteostajax);
    }
  }

  $tuoteostajat = substr($tuoteostajat, 0, -1);

  if (trim($tuoteostajat) != '') {
    $lisa_haku_ostaja = " and kuka.myyja in ($tuoteostajat) ";
    $lisa .= " and tuote.ostajanro in ($tuoteostajat) ";
  }
}

if (count($mul_asiakasosasto) > 0) {
  $asiakasosastot = '';

  foreach ($mul_asiakasosasto as $asiakasosastox) {
    if (trim($asiakasosastox) != '') {
      $asiakasosastox = trim(mysql_real_escape_string($asiakasosastox));
      $asiakasosastot .= "'$asiakasosastox',";
      $ulisa .= "&mul_asiakasosasto[]=".rawurlencode($asiakasosastox);
    }
  }

  $asiakasosastot = substr($asiakasosastot, 0, -1);

  if (trim($asiakasosastot) != '') {
    $lisa_haku_asiakasosasto = " and asiakas.osasto in ($asiakasosastot) ";

    if ($mulselprefix == "abc_aputaulu") $lisa .= " and abc_aputaulu.osasto in ($asiakasosastot) ";
    else $lisa .= " and asiakas.osasto in ($asiakasosastot) ";
  }
}

if (count($mul_asiakasryhma) > 0) {
  $asiakasryhmat = '';

  foreach ($mul_asiakasryhma as $asiakasryhmax) {
    if (trim($asiakasryhmax) != '') {
      $asiakasryhmax = trim(mysql_real_escape_string($asiakasryhmax));
      $asiakasryhmat .= "'$asiakasryhmax',";
      $ulisa .= "&mul_asiakasryhma[]=".rawurlencode($asiakasryhmax);
    }
  }

  $asiakasryhmat = substr($asiakasryhmat, 0, -1);

  if (trim($asiakasryhmat) != '') {
    $lisa_haku_asiakasryhma = " and asiakas.ryhma in ($asiakasryhmat) ";

    if ($mulselprefix == "abc_aputaulu") $lisa .= " and abc_aputaulu.try in ($asiakasryhmat) ";
    else $lisa .= " and asiakas.ryhma in ($asiakasryhmat) ";
  }
}

if (count($mul_asiakaspiiri) > 0) {
  $asiakaspiirit = '';

  foreach ($mul_asiakaspiiri as $asiakaspiirix) {
    if (trim($asiakaspiirix) != '') {
      $asiakaspiirix = trim(mysql_real_escape_string($asiakaspiirix));
      $asiakaspiirit .= "'$asiakaspiirix',";
      $ulisa .= "&mul_asiakaspiiri[]=".rawurlencode($asiakaspiirix);
    }
  }

  $asiakaspiirit = substr($asiakaspiirit, 0, -1);

  if (trim($asiakaspiirit) != '') {

    if (isset($piirivalinta) and $piirivalinta == "lasku") {
      $lisa_haku_asiakaspiiri = " and lasku.piiri in ($asiakaspiirit) ";
      $lisa .= " and lasku.piiri in ($asiakaspiirit) ";
    }
    else {
      $lisa_haku_asiakaspiiri = " and asiakas.piiri in ($asiakaspiirit) ";
      $lisa .= " and asiakas.piiri in ($asiakaspiirit) ";
    }
  }
}

if (count($mul_toimipaikka) > 0) {
  $toimipaikat = '';
  foreach ($mul_toimipaikka as $toimipaikkax) {
    if (trim($toimipaikkax) != '') {
      $toimipaikkax = trim(mysql_real_escape_string($toimipaikkax));
      $toimipaikat .= "'$toimipaikkax',";
      $ulisa .= "&mul_toimipaikka[]=".rawurlencode($toimipaikkax);
    }
  }

  $toimipaikat = substr($toimipaikat, 0, -1);

  if (trim($toimipaikat) != '') {
    $lisa_haku_toimipaikka = "  and lasku.yhtio_toimipaikka in ($toimipaikat)";
    $lisa .= " and lasku.yhtio_toimipaikka in ($toimipaikat) ";
  }
}

if (count($mul_maa) > 0) {
  $maat = '';
  foreach ($mul_maa as $maax) {
    if (trim($maax) != '') {
      $maax = trim(mysql_real_escape_string($maax));
      $maat .= "'$maax',";
      $ulisa .= "&mul_maa[]=".rawurlencode($maax);
    }
  }

  $maat = substr($maat, 0, -1);

  if (trim($maat) != '') {
    $lisa_haku_maa = " maat.tunnus in ($maat)";
    $lisa .= " maat.tunnus in ($maat) ";
  }
}

if (count($mul_kustp) > 0) {
  $kustpt = '';

  foreach ($mul_kustp as $kustpx) {
    if (trim($kustpx) != '') {
      $kustpx = trim(mysql_real_escape_string($kustpx));
      $kustpt .= "'$kustpx',";
      $ulisa .= "&mul_kustp[]=".rawurlencode($kustpx);
    }
  }

  $kustpt = substr($kustpt, 0, -1);

  if (trim($kustpt) != '') {
    if ($mulselprefix == 'asiakas') {
      $lisa_haku_kustp = " and asiakas.kustannuspaikka in ($kustpt) ";
      $lisa .= " and asiakas.kustannuspaikka in ($kustpt) ";
    }
    elseif (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta") !== false) {
      if (isset($kustapvalinta) and $kustapvalinta == "tuote") {
        $lisa_haku_kustp = " and tuote.kustp in ($kustpt) ";
        $lisa .= " and tuote.kustp in ($kustpt) ";
      }
      else {
        $lisa_haku_kustp = " and asiakas.kustannuspaikka in ($kustpt) ";
        $lisa .= " and asiakas.kustannuspaikka in ($kustpt) ";
      }
    }
    elseif (strpos($_SERVER['SCRIPT_NAME'], "tulosseuranta") !== false) {
      $lisa_haku_kustp = " and tuote.kustp in ($kustpt) ";
      $lisa .= " and tiliointi.kustp in ($kustpt) ";
    }
    else {
      $lisa_haku_kustp = " and tiliointi.kustp in ($kustpt) ";
      $lisa .= " and tiliointi.kustp in ($kustpt) ";
    }
  }
}

if (count($mul_kohde) > 0) {
  $kohdet = '';

  foreach ($mul_kohde as $kohdex) {
    if (trim($kohdex) != '') {
      $kohdex = trim(mysql_real_escape_string($kohdex));
      $kohdet .= "'$kohdex',";
      $ulisa .= "&mul_kohde[]=".rawurlencode($kohdex);
    }
  }

  $kohdet = substr($kohdet, 0, -1);

  if (trim($kohdet) != '') {
    if ($mulselprefix == 'asiakas') {
      $lisa_haku_kustp = " and asiakas.kohde in ($kohdet) ";
      $lisa .= " and asiakas.kohde in ($kohdet) ";
    }
    elseif (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta") !== FALSE) {
      if (isset($kohdevalinta) and $kohdevalinta == "tuote") {
        $lisa_haku_kustp = " and tuote.kohde in ($kohdet) ";
        $lisa .= " and tuote.kohde in ($kohdet) ";
      }
      else {
        $lisa_haku_kustp = " and asiakas.kohde in ($kohdet) ";
        $lisa .= " and asiakas.kohde in ($kohdet) ";
      }
    }
    else {
      $lisa_haku_kohde = " and tiliointi.kohde in ($kohdet) ";
      $lisa .= " and tiliointi.kohde in ($kohdet) ";
    }
  }
}

if (count($mul_projekti) > 0) {
  $projektit = '';

  foreach ($mul_projekti as $projektix) {
    if (trim($projektix) != '') {
      $projektix = trim(mysql_real_escape_string($projektix));
      $projektit .= "'$projektix',";
      $ulisa .= "&mul_projekti[]=".rawurlencode($projektix);
    }
  }

  $projektit = substr($projektit, 0, -1);

  if (trim($projektit) != '') {
    if ($mulselprefix == 'asiakas') {
      $lisa_haku_kustp = " and asiakas.projekti in ($projektit) ";
      $lisa .= " and asiakas.projekti in ($projektit) ";
    }
    elseif (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta") !== FALSE) {
      if (isset($projektivalinta) and $projektivalinta == "tuote") {
        $lisa_haku_kustp = " and tuote.projekti in ($projektit) ";
        $lisa .= " and tuote.projekti in ($projektit) ";
      }
      else {
        $lisa_haku_kustp = " and asiakas.projekti in ($projektit) ";
        $lisa .= " and asiakas.projekti in ($projektit) ";
      }
    }
    else {
      $lisa_haku_projekti = " and tiliointi.projekti in ($projektit) ";
      $lisa .= " and tiliointi.projekti in ($projektit) ";
    }
  }
}

foreach ($monivalintalaatikot as $monivalintalaatikko) {

  if (substr($monivalintalaatikko, 0, 4) == "<br>") {
    $monivalintalaatikko = substr($monivalintalaatikko, 4);
    echo "<div style='clear: both;'><br></div>";
  }

  if ($monivalintalaatikko == "toimi") {

    if (isset($monivalinta_tuotteet)) {
      $query = "SELECT DISTINCT avainsana.selite,
                $kieli_selitetark,
                sum(if(tuote.status not in ('P','X'), 1, (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0))) naytetaanko
                FROM tuote
                JOIN avainsana ON (avainsana.yhtio = tuote.yhtio AND avainsana.selite = tuote.osasto AND avainsana.laji = 'OSASTO' AND avainsana.kieli in ('$yhtiorow[kieli]', '') $avainlisa)
                WHERE tuote.yhtio = '$kukarow[yhtio]'
                $kieltolisa
                $extra_poislisa
                $poislisa_mulsel
                AND tuote.tuoteno in ($monivalinta_tuotteet)
                GROUP BY 1,2
                HAVING naytetaanko > 0
                ORDER BY avainsana.selitetark";
    }
    else {
      $query = "SELECT toimi.nimi, toimi.tunnus
                FROM toimi
                RIGHT JOIN tuotteen_toimittajat AS tt
                ON tt.yhtio = toimi.yhtio
                AND tt.liitostunnus = toimi.tunnus
                WHERE toimi.yhtio   = '$kukarow[yhtio]'
                GROUP BY toimi.nimi";
    }
    $sresult = pupe_query($query);

    if (mysql_num_rows($sresult) == 0) {
      $query = "SELECT DISTINCT tuote.osasto selite, tuote.osasto selitetark
                FROM tuote
                WHERE tuote.yhtio  = '$kukarow[yhtio]'
                and tuote.osasto  != ''
                $kieltolisa
                $extra_poislisa
                $poislisa_mulsel
                ORDER BY 1, 2";
      $sresult = pupe_query($query);
    }

    if (mysql_num_rows($sresult) >= 1) {

      $multiple_lisa = " multiple='multiple' class='multipleselect' size='7' ";

      if (in_array("toimi", $monivalintalaatikot_normaali)) {
        $multiple_lisa = "";
      }

      echo "<div class='monivalintadiv'><table valign='top'>";

      if ($piirra_otsikot) {
        echo "<tr><th>";

        echo t("Toimittaja");

        echo "</th></tr>";
      }

      echo "<tr><td nowrap style='padding:0px'><select name='mul_toimi[]' $multiple_lisa $autosubmit>";
      echo "<option value=''>".t("Ei valintaa")."</option>";

      while ($sxrow = mysql_fetch_assoc($sresult)) {
        $sel = '';

        if (count($mul_toimi) > 0) {
          if (in_array(strtolower(trim($sxrow['tunnus'])), $mul_toimi)) {
            $sel = 'SELECTED';
          }
        }

        echo "<option value='".strtolower($sxrow['tunnus'])."' $sel>";

        echo "$sxrow[nimi]</option>";
      }

      echo "</select></td>";
      echo "</tr>";

      echo "</table></div>";
    }
  }

  if ($monivalintalaatikko == "osasto") {
    if (isset($monivalinta_tuotteet)) {
      $query = "SELECT DISTINCT avainsana.selite,
                $kieli_selitetark,
                sum(if(tuote.status not in ('P','X'), 1, (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0))) naytetaanko
                FROM tuote
                JOIN avainsana ON (avainsana.yhtio = tuote.yhtio AND avainsana.selite = tuote.osasto AND avainsana.laji = 'OSASTO' AND avainsana.kieli in ('$yhtiorow[kieli]', '') $avainlisa)
                WHERE tuote.yhtio = '$kukarow[yhtio]'
                $lisa_haku_toimi
                $kieltolisa
                $extra_poislisa
                $poislisa_mulsel
                AND tuote.tuoteno in ($monivalinta_tuotteet)
                GROUP BY 1,2
                HAVING naytetaanko > 0
                ORDER BY avainsana.selitetark";
    }
    elseif ($lisa_haku_toimi == "") {
      $query = "SELECT DISTINCT avainsana.selite,
                $kieli_selitetark
                FROM avainsana
                WHERE avainsana.yhtio = '$kukarow[yhtio]'
                and avainsana.laji    = 'OSASTO'
                and avainsana.kieli   in ('$yhtiorow[kieli]', '')
                $avainlisa
                $orderlisa";
    }
    else {
      $query = "SELECT distinct avainsana.selite,
                $kieli_selitetark
                FROM tuote
                JOIN avainsana ON (avainsana.yhtio = tuote.yhtio and tuote.osasto = avainsana.selite and avainsana.laji = 'OSASTO' and avainsana.kieli in ('$yhtiorow[kieli]', '') $avainlisa)
                WHERE tuote.yhtio = '$kukarow[yhtio]'
                $lisa_haku_toimi
                $kieltolisa
                $extra_poislisa
                $poislisa_mulsel
                $orderlisa";
    }
    $sresult = pupe_query($query);

    if (mysql_num_rows($sresult) == 0) {
      $query = "SELECT DISTINCT tuote.osasto selite, tuote.osasto selitetark
                FROM tuote
                WHERE tuote.yhtio  = '$kukarow[yhtio]'
                and tuote.osasto  != ''
                $lisa_haku_toimi
                $kieltolisa
                $extra_poislisa
                $poislisa_mulsel
                ORDER BY 1, 2";
      $sresult = pupe_query($query);
    }

    if (mysql_num_rows($sresult) >= 1) {

      $multiple_lisa = " multiple='multiple' class='multipleselect' size='7' ";

      if (in_array("osasto", $monivalintalaatikot_normaali)) {
        $multiple_lisa = "";
      }

      echo "<div class='monivalintadiv'><table valign='top'>";

      if ($piirra_otsikot) {
        echo "<tr><th>";

        echo t("Osasto");

        echo "</th></tr>";
      }

      echo "<tr><td nowrap style='padding:0px'><select name='mul_osasto[]' $multiple_lisa $autosubmit>";
      echo "<option value=''>".t("Ei valintaa")."</option>";

      while ($sxrow = mysql_fetch_assoc($sresult)) {
        $sel = '';

        if (count($mul_osasto) > 0) {
          if (in_array(strtolower(trim($sxrow['selite'])), $mul_osasto)) {
            $sel = 'SELECTED';
          }
        }

        echo "<option value='".strtolower($sxrow['selite'])."' $sel>";

        if ($yhtiorow['naytetaanko_osaston_ja_tryn_selite'] == '') {
          echo $sxrow['selite']." ";
        }

        echo "$sxrow[selitetark]</option>";
      }

      echo "</select></td>";
      echo "</tr>";

      if (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta") !== FALSE) {
        $ruksitchk = "";

        if ($ruksit["osasto"] != "") {
          $ruksitchk = "CHECKED";
        }

        echo "<tr><th>".t("Prio").": <input type='text' name='jarjestys[osasto]' size='2' value='$jarjestys[osasto]'> ".t("Osastoittain")." <input type='checkbox' name='ruksit[osasto]' value='osasto' $ruksitchk></th></tr>";
      }

      echo "</table></div>";
    }
  }

  if ($monivalintalaatikko == "try") {
    if (isset($monivalinta_tuotteet)) {
      $query = "SELECT distinct avainsana.selite,
                $kieli_selitetark,
                sum(if(tuote.status not in ('P','X'), 1, (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0))) naytetaanko
                FROM tuote
                JOIN avainsana ON (avainsana.yhtio = tuote.yhtio and tuote.try = avainsana.selite and avainsana.laji = 'TRY' and avainsana.kieli in ('$yhtiorow[kieli]', '') $avainlisa)
                WHERE tuote.yhtio = '$kukarow[yhtio]'
                $lisa_haku_toimi
                $lisa_haku_osasto
                $kieltolisa
                $extra_poislisa
                $poislisa_mulsel
                AND tuote.tuoteno in ($monivalinta_tuotteet)
                GROUP BY 1,2
                HAVING naytetaanko > 0
                ORDER BY avainsana.selitetark";
    }
    elseif ($lisa_haku_toimi == "" and $lisa_haku_osasto == "") {
      $query = "SELECT DISTINCT avainsana.selite,
                $kieli_selitetark
                FROM avainsana
                WHERE avainsana.yhtio = '$kukarow[yhtio]'
                and avainsana.laji    = 'TRY'
                and avainsana.kieli   in ('$yhtiorow[kieli]', '')
                $avainlisa
                $orderlisa";
    }
    elseif ($lisa_haku_toimi != "" or $lisa_haku_osasto != "") {
      $query = "SELECT distinct avainsana.selite,
                $kieli_selitetark
                FROM tuote
                JOIN avainsana ON (avainsana.yhtio = tuote.yhtio and tuote.try = avainsana.selite and avainsana.laji = 'TRY' and avainsana.kieli in ('$yhtiorow[kieli]', '') $avainlisa)
                WHERE tuote.yhtio = '$kukarow[yhtio]'
                $lisa_haku_toimi
                $lisa_haku_osasto
                $kieltolisa
                $extra_poislisa
                $poislisa_mulsel
                $orderlisa";
    }
    $sresult = pupe_query($query);

    if (mysql_num_rows($sresult) == 0) {
      $query = "SELECT DISTINCT tuote.try selite, tuote.try selitetark
                FROM tuote
                WHERE tuote.yhtio  = '$kukarow[yhtio]'
                and tuote.try     != ''
                $lisa_haku_toimi
                $lisa_haku_osasto
                $kieltolisa
                $extra_poislisa
                $poislisa_mulsel
                ORDER BY 1, 2";
      $sresult = pupe_query($query);
    }

    if (mysql_num_rows($sresult) >= 1) {

      $multiple_lisa = " multiple='multiple' class='multipleselect' size='7' ";

      if (in_array("try", $monivalintalaatikot_normaali)) {
        $multiple_lisa = "";
      }

      echo "<div class='monivalintadiv'><table valign='top'>";

      if ($piirra_otsikot) {
        echo "<tr><th>";

        echo t("Tuoteryhmä");

        echo "</th></tr>";
      }

      echo "<tr><td nowrap style='padding:0px'><select name='mul_try[]' $multiple_lisa $autosubmit>";
      echo "<option value=''>".t("Ei valintaa")."</option>";

      while ($srow = mysql_fetch_assoc($sresult)) {
        $sel = '';

        if (count($mul_try) > 0 and in_array(strtolower(trim($srow['selite'])), $mul_try)) {
          $sel = 'SELECTED';
        }

        echo "<option value='".strtolower($srow['selite'])."' $sel>";

        if ($yhtiorow['naytetaanko_osaston_ja_tryn_selite'] == '') {
          echo $srow['selite']." ";
        }

        echo "$srow[selitetark]</option>";
      }

      echo "</select></td>";
      echo "</tr>";

      if (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta") !== FALSE) {
        $ruksitchk = "";

        if ($ruksit["try"] != "") {
          $ruksitchk = "CHECKED";
        }

        echo "<tr><th>".t("Prio").": <input type='text' name='jarjestys[try]' size='2' value='$jarjestys[try]'> ".t("Tuoteryhmittäin")." <input type='checkbox' name='ruksit[try]' value='try' $ruksitchk></th></tr>";
      }

      echo "</table></div>";
    }
  }

  if ($monivalintalaatikko == "tuotemerkki") {
    unset($tmsresult);

    if (isset($monivalinta_tuotteet)) {
      $query = "SELECT avainsana.selite as tme,
                $kieli_selite,
                sum(if(tuote.status not in ('P','X'), 1, (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0))) naytetaanko
                FROM tuote
                JOIN avainsana ON (avainsana.yhtio = tuote.yhtio and tuote.tuotemerkki = avainsana.selite and avainsana.laji = 'TUOTEMERKKI' and avainsana.kieli in ('$yhtiorow[kieli]', '') $avainlisa)
                WHERE tuote.yhtio = '$kukarow[yhtio]'
                $lisa_haku_toimi
                $lisa_haku_osasto
                $lisa_haku_try
                $kieltolisa
                $extra_poislisa
                $poislisa_mulsel
                AND tuote.tuoteno in ($monivalinta_tuotteet)
                GROUP BY 1,2
                HAVING naytetaanko > 0
                ORDER BY avainsana.selite";
      $tmsresult = pupe_query($query);
    }
    elseif ($lisa_haku_toimi == "" and $lisa_haku_osasto == "" and $lisa_haku_try == "" and $lisa_dynaaminen["tuote"] == "") {
      $query = "SELECT avainsana.selite as tme,
                $kieli_selite
                      FROM avainsana
                      WHERE avainsana.yhtio = '$kukarow[yhtio]'
                      and avainsana.laji    = 'TUOTEMERKKI'
                and avainsana.kieli         in ('$yhtiorow[kieli]', '')
                      $avainlisa
                      ORDER BY avainsana.jarjestys, avainsana.selite";
      $tmsresult = pupe_query($query);
    }
    elseif ($lisa_haku_toimi != "" or $lisa_haku_osasto != "" or $lisa_haku_try != "" or $lisa_dynaaminen["tuote"] != "") {
      $query = "SELECT distinct avainsana.selite as tme,
                $kieli_selite
                FROM tuote
                {$lisa_dynaaminen["tuote"]}
                JOIN avainsana ON (avainsana.yhtio = tuote.yhtio and tuote.tuotemerkki = avainsana.selite and avainsana.laji = 'TUOTEMERKKI' and avainsana.kieli in ('$yhtiorow[kieli]', '') $avainlisa)
                WHERE tuote.yhtio = '$kukarow[yhtio]'
                $lisa_haku_toimi
                $lisa_haku_osasto
                $lisa_haku_try
                $lisa_haku_malli
                $kieltolisa
                $extra_poislisa
                $poislisa_mulsel
                ORDER BY avainsana.jarjestys, avainsana.selite";
      $tmsresult = pupe_query($query);
    }

    if (is_resource($tmsresult) and mysql_num_rows($tmsresult) == 0) {
      $query = "SELECT DISTINCT tuote.tuotemerkki tme, tuote.tuotemerkki selite
                FROM tuote
                {$lisa_dynaaminen["tuote"]}
                $lisa_parametri
                WHERE tuote.yhtio      = '$kukarow[yhtio]'
                and tuote.tuotemerkki != ''
                $lisa_haku_toimi
                $lisa_haku_osasto
                $lisa_haku_try
                $kieltolisa
                $extra_poislisa
                $poislisa_mulsel
                ORDER BY 1";
      $tmsresult = pupe_query($query);
    }

    if (is_resource($tmsresult) and mysql_num_rows($tmsresult) > 0) {

      echo "<div class='monivalintadiv'><table valign='top'>";

      if ($piirra_otsikot) {
        echo "<tr><th>";

        echo t("Tuotemerkki");

        echo "</th></tr>";
      }

      echo "<tr><td nowrap style='padding:0px'>";

      $multiple_lisa = " multiple='multiple' class='multipleselect' size='7' ";

      if (in_array("tuotemerkki", $monivalintalaatikot_normaali)) {
        $multiple_lisa = "";
      }

      echo "<select name='mul_tme[]' $multiple_lisa $autosubmit>";
      echo "<option value=''>", t("Ei valintaa"), "</option>";

      while ($srow = mysql_fetch_assoc($tmsresult)) {
        $sel = '';

        if (count($mul_tme) > 0 and in_array(strtolower(trim($srow['tme'])), $mul_tme)) {
          $sel = 'SELECTED';
        }

        echo "<option value='".strtolower($srow['tme'])."' $sel>$srow[selite]</option>";
      }

      echo "</select></td>";
      echo "</tr>";

      if (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta") !== FALSE) {
        $ruksitchk = "";

        if ($ruksit["tuotemerkki"] != "") {
          $ruksitchk = "CHECKED";
        }

        echo "<tr><th>".t("Prio").": <input type='text' name='jarjestys[tuotemerkki]' size='2' value='$jarjestys[tuotemerkki]'> ".t("Tuotemerkeittäin")." <input type='checkbox' name='ruksit[tuotemerkki]' value='tuotemerkki' $ruksitchk></th></tr>";
      }

      echo "</table></div>";
    }
  }

  if ($monivalintalaatikko == "malli/mallitark" or $monivalintalaatikko == "malli") {
    unset($sxresult);

    if (($lisa_haku_toimi != "" or $lisa_haku_osasto != "" or $lisa_haku_try != "")) {

      if (($lisa_haku_tme != '' or $lisa_haku_try != '') and ($monivalintalaatikko == "malli" or (!in_array("malli", $monivalintalaatikot) and $monivalintalaatikko == 'malli/mallitark'))) {
        // malli dropdown
        if (isset($monivalinta_tuotteet)) {
          $query = "SELECT avainsana.selite as malli,
                    $kieli_selite,
                    $kieli_selitetark,
                    sum(if(tuote.status not in ('P','X'), 1, (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0))) naytetaanko
                    FROM tuote
                    JOIN avainsana ON (avainsana.yhtio = tuote.yhtio and tuote.malli = avainsana.selite and avainsana.laji = 'MALLI' and avainsana.kieli in ('$yhtiorow[kieli]', '') $avainlisa)
                    WHERE tuote.yhtio = '$kukarow[yhtio]'
                    $lisa_haku_toimi
                    $lisa_haku_osasto
                    $lisa_haku_try
                    $lisa_haku_tme
                    $kieltolisa
                    $extra_poislisa
                    $poislisa_mulsel
                    AND tuote.tuoteno in ($monivalinta_tuotteet)
                    GROUP BY 1,2
                    HAVING naytetaanko > 0
                    ORDER BY avainsana.selite";
          $sxresult = pupe_query($query);
        }
        elseif ($lisa_haku_try == "" and $lisa_haku_tme == "") {
          $query = "SELECT avainsana.selite as malli,
                    $kieli_selite,
                    $kieli_selitetark
                    FROM avainsana
                    WHERE avainsana.yhtio = '$kukarow[yhtio]'
                    and avainsana.laji    = 'MALLI'
                    and avainsana.kieli         in ('$yhtiorow[kieli]', '')
                    $avainlisa
                    ORDER BY avainsana.jarjestys, avainsana.selite";
          $sxresult = pupe_query($query);
        }
        elseif ($lisa_haku_try != "" or $lisa_haku_tme != "") {
          $query = "SELECT distinct avainsana.selite as malli,
                    $kieli_selite,
                    $kieli_selitetark
                    FROM tuote
                    $lisa_parametri
                    JOIN avainsana ON (avainsana.yhtio = tuote.yhtio and tuote.malli = avainsana.selite and avainsana.laji = 'MALLI' and avainsana.kieli in ('$yhtiorow[kieli]', '') $avainlisa)
                    WHERE tuote.yhtio = '$kukarow[yhtio]'
                    $lisa_haku_toimi
                    $lisa_haku_osasto
                    $lisa_haku_try
                    $lisa_haku_tme
                    $kieltolisa
                    $extra_poislisa
                    $poislisa_mulsel
                    ORDER BY avainsana.jarjestys, avainsana.selite";
          $sxresult = pupe_query($query);
        }

        if (is_resource($sxresult) and mysql_num_rows($sxresult) == 0) {
          $query = "SELECT DISTINCT tuote.malli malli, tuote.malli selite
                    FROM tuote
                    WHERE tuote.yhtio  = '$kukarow[yhtio]'
                    and tuote.malli   != ''
                    $lisa_haku_toimi
                    $lisa_haku_osasto
                    $lisa_haku_try
                    $lisa_haku_tme
                    $kieltolisa
                    $extra_poislisa
                    $poislisa_mulsel
                    ORDER BY malli";
          $sxresult = pupe_query($query);
        }

        if (is_resource($sxresult) and mysql_num_rows($sxresult) > 0) {

          echo "<div class='monivalintadiv'><table valign='top'>";

          if ($piirra_otsikot) {
            echo "<tr><th>";
            echo t("Malli");
            echo "</th></tr>";
          }

          echo "<tr><td nowrap style='padding:0px'>";

          $multiple_lisa = " multiple='multiple' class='multipleselect' size='7' ";

          if (in_array("malli", $monivalintalaatikot_normaali)) {
            $multiple_lisa = "";
          }

          echo "<select name='mul_malli[]' $multiple_lisa $autosubmit>";
          echo "<option value=''>", t("Ei valintaa"), "</option>";

          while ($mallirow = mysql_fetch_assoc($sxresult)) {
            $sel = '';

            if (count($mul_malli) > 0 and in_array(strtolower(trim($mallirow['malli'])), $mul_malli)) {
              $sel = 'SELECTED';
            }


            $_mallie = "";

            if ($yhtiorow['naytetaanko_osaston_ja_tryn_selite'] == '' or empty($mallirow["selitetark"])) {
              $_mallie = $mallirow["selite"];
            }

            if (!empty($mallirow["selitetark"]) and $_mallie != $mallirow["selitetark"]) {
              $_mallie .= " {$mallirow["selitetark"]}";
            }

            echo "<option value='".strtolower($mallirow['malli'])."' $sel>$_mallie</option>";
          }

          echo "</select></td>";
          echo "</tr>";

          if (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta") !== FALSE) {
            $ruksitchk = "";

            if ($ruksit["malli"] != "") {
              $ruksitchk = "CHECKED";
            }

            echo "<tr><th>".t("Prio").": <input type='text' name='jarjestys[malli]' size='2' value='$jarjestys[malli]'> ".t("Malleittain")." <input type='checkbox' name='ruksit[malli]' value='malli' $ruksitchk></th></tr>";
          }

          echo "</table></div>";
        }
      }

      if ($lisa_haku_malli != '' and $monivalintalaatikko == "malli/mallitark") {
        unset($sxresult);

        // mallitarkenne dropdown
        if (isset($monivalinta_tuotteet)) {
          $query = "SELECT avainsana.selite as mallitarkenne,
                    $kieli_selite,
                    sum(if(tuote.status not in ('P','X'), 1, (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0))) naytetaanko
                    FROM tuote
                    JOIN avainsana ON (avainsana.yhtio = tuote.yhtio and tuote.mallitarkenne = avainsana.selite and avainsana.laji = 'MALLITARKENNE' and avainsana.kieli in ('$yhtiorow[kieli]', '') $avainlisa)
                    WHERE tuote.yhtio = '$kukarow[yhtio]'
                    $lisa_haku_toimi
                    $lisa_haku_osasto
                    $lisa_haku_try
                    $lisa_haku_tme
                    $lisa_haku_malli
                    $kieltolisa
                    $extra_poislisa
                    $poislisa_mulsel
                    AND tuote.tuoteno in ($monivalinta_tuotteet)
                    GROUP BY 1,2
                    HAVING naytetaanko > 0
                    ORDER BY avainsana.selite";
          $sxresult = pupe_query($query);
        }
        elseif ($lisa_haku_malli == "") {
          $query = "SELECT avainsana.selite as mallitarkenne,
                    $kieli_selite
                          FROM avainsana
                          WHERE avainsana.yhtio = '$kukarow[yhtio]'
                          and avainsana.laji    = 'MALLITARKENNE'
                    and avainsana.kieli         in ('$yhtiorow[kieli]', '')
                          $avainlisa
                          ORDER BY avainsana.jarjestys, avainsana.selite";
          $sxresult = pupe_query($query);
        }
        elseif ($lisa_haku_malli != "") {
          $query = "SELECT distinct avainsana.selite as mallitarkenne,
                    $kieli_selite
                    FROM tuote
                    $lisa_parametri
                    JOIN avainsana ON (avainsana.yhtio = tuote.yhtio and tuote.mallitarkenne = avainsana.selite and avainsana.laji = 'MALLITARKENNE' and avainsana.kieli in ('$yhtiorow[kieli]', '') $avainlisa)
                    WHERE tuote.yhtio = '$kukarow[yhtio]'
                    $lisa_haku_toimi
                    $lisa_haku_osasto
                    $lisa_haku_try
                    $lisa_haku_tme
                    $lisa_haku_malli
                    $kieltolisa
                    $extra_poislisa
                    $poislisa_mulsel
                    ORDER BY avainsana.jarjestys, avainsana.selite";
          $sxresult = pupe_query($query);
        }

        if (is_resource($sxresult) and mysql_num_rows($sxresult) == 0) {
          $query = "SELECT DISTINCT tuote.mallitarkenne mallitarkenne, tuote.mallitarkenne selite
                    FROM tuote
                    WHERE tuote.yhtio        = '$kukarow[yhtio]'
                    and tuote.mallitarkenne != ''
                    $lisa_haku_toimi
                    $lisa_haku_osasto
                    $lisa_haku_try
                    $lisa_haku_tme
                    $lisa_haku_malli
                    $kieltolisa
                    $extra_poislisa
                    $poislisa_mulsel
                    ORDER BY mallitarkenne";
          $sxresult = pupe_query($query);
        }

        if (is_resource($sxresult) and mysql_num_rows($sxresult) > 0) {
          echo "<div class='monivalintadiv'><table valign='top'>";

          if ($piirra_otsikot) {
            echo "<tr><th>";
            echo t("Mallitarkenne");
            echo "</th></tr>";
          }

          echo "<tr><td nowrap style='padding:0px'>";

          $multiple_lisa = " multiple='multiple' class='multipleselect' size='7' ";

          if (in_array("mallitarkenne", $monivalintalaatikot_normaali)) {
            $multiple_lisa = "";
          }

          echo "<select name='mul_mallitarkenne[]' $multiple_lisa $autosubmit>";
          echo "<option value=''>", t("Ei valintaa"), "</option>";

          while ($mallitarkennerow = mysql_fetch_assoc($sxresult)) {
            $sel = '';

            if (count($mul_mallitarkenne) > 0 and in_array(strtolower(trim($mallitarkennerow['mallitarkenne'])), $mul_mallitarkenne)) {
              $sel = 'SELECTED';
            }

            echo "<option value='".strtolower($mallitarkennerow['mallitarkenne'])."' $sel>$mallitarkennerow[selite]</option>";
          }

          echo "</select></td>";
          echo "</tr>";

          if (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta") !== FALSE) {
            $ruksitchk = "";

            if ($ruksit["mallitarkenne"] != "") {
              $ruksitchk = "CHECKED";
            }

            echo "<tr><th>".t("Prio").": <input type='text' name='jarjestys[mallitarkenne]' size='2' value='$jarjestys[mallitarkenne]'> ".t("Mallitarkenteittain")." <input type='checkbox' name='ruksit[mallitarkenne]' value='mallitarkenne' $ruksitchk></th></tr>";
          }

          echo "</table></div>";
        }
      }
    }
  }

  if (substr($monivalintalaatikko, 0, 10) == "dynaaminen") {
    unset($sxresult);

    $tree = substr($monivalintalaatikko, 11);

    if ($tree == "asiakas") {
      $mul_otsik = t("Asiakaskategoria");
    }
    elseif ($tree == "tuote") {
      $mul_otsik = t("Tuotekategoria");
    }
    elseif ($tree == "kuka") {
      $mul_otsik = t("Organisaatiotaso");
    }
    else {
      $mul_otsik = t(ucfirst($tree)).t("segmentti");
    }

    $joinlisa = "";
    $nodelisa = "";
    $nodeinde = "";

    // haetaan vain ne puun tunnukset, joille on tehty linkkauksia
    if ($tree != "kuka") {

      if (isset($autoid) and $autoid != '') {
        $joinlisa .= " JOIN yhteensopivuus_tuote ON (yhteensopivuus_tuote.yhtio = puun_alkio.yhtio AND yhteensopivuus_tuote.tuoteno = puun_alkio.liitos and yhteensopivuus_tuote.atunnus IN ($autoid)) ";
      }

      $query = "SELECT GROUP_CONCAT(DISTINCT puun_tunnus) tunnukset
                FROM puun_alkio
                $joinlisa
                WHERE puun_alkio.yhtio = '{$kukarow['yhtio']}'
                AND puun_alkio.laji    = '{$tree}'";
      $kaikki_puun_tunnukset_res = pupe_query($query, $link);
      $kaikki_puun_tunnukset_row = mysql_fetch_assoc($kaikki_puun_tunnukset_res);

      if (strlen($kaikki_puun_tunnukset_row["tunnukset"]) > 0) {
        $nodelisa = " AND node.tunnus IN ($kaikki_puun_tunnukset_row[tunnukset]) ";
        $nodeinde = " use index (PRIMARY) ";
      }
      else {
        continue;
      }
    }

    // Haetaan koko hierarkia
    $query = "SELECT DISTINCT parent.tunnus node_tunnus, parent.nimi node_nimi,
              parent.syvyys,
              parent.lft,
              parent.rgt
              FROM dynaaminen_puu AS node $nodeinde
              JOIN dynaaminen_puu AS parent ON (parent.yhtio = node.yhtio AND parent.laji = node.laji AND parent.lft <= node.lft AND parent.rgt >= node.lft AND parent.lft > 1)
              WHERE node.yhtio = '{$kukarow['yhtio']}'
              AND node.laji    = '{$tree}'
              AND node.lft     > 1
              $nodelisa
              ORDER BY syvyys, node_nimi";
    $sxresult2 = pupe_query($query, $link);

    if (mysql_num_rows($sxresult2) > 0) {
      $edsyvyys = 0;
      $sallitut_valit = array();

      while ($looppirow = mysql_fetch_assoc($sxresult2)) {

        // Suljetaan selectboxi ja tehdään seuraava kun taso vaihtuu
        if ($looppirow["syvyys"] != $edsyvyys) {

          if ($edsyvyys > 0) {
            echo "</select></td>";
            echo "</tr>";

            if (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta") !== FALSE) {
              $ruksitchk = "";

              if ($ruksit["dynaaminen_".$tree."_".$edsyvyys] != "") {
                $ruksitchk = "CHECKED";
              }

              //echo "<tr><th>".t("Prio").": <input type='text' name='jarjestys[dynaaminen_".$tree."_".$edsyvyys."]' size='2' value='".$jarjestys["dynaaminen_".$tree."_".$edsyvyys]."'> ";
              //echo t(ucfirst($tree)).t("kategorioittain")." {$edsyvyys} <input type='checkbox' name='ruksit[dynaaminen_".$tree."_".$edsyvyys."]' value='dynaaminen_".$tree."_".$edsyvyys."' {$ruksitchk} {$autosubmit}></th></tr>";
            }
            echo "</table></div>";
          }

          echo "<div class='monivalintadiv'><table valign='top'>";

          if ($piirra_otsikot) {
            echo "<tr><th>$mul_otsik {$looppirow['syvyys']}</th></tr>";
          }

          echo "<tr><td nowrap style='padding:0px'>";
          echo "<select name='mul_{$tree}{$looppirow['syvyys']}[]' ";

          if (is_array($monivalintalaatikot_normaali) and !in_array("dynaaminen_".$tree, $monivalintalaatikot_normaali)) {
            echo " multiple='multiple' class='multipleselect' size='7' ";
          }

          echo "{$autosubmit}>";
          echo "<option value=''>", t("Ei valintaa"), "</option>";
        }

        $show_node = FALSE;

        // Skipataanko vai näytetäänkö
        if (count($sallitut_valit) > 0) {

          $raj_syvyys = 0;

          // Otetaan tiukin rajaus, eli mahd oikealta (as in oikea tai vasen)
          for ($syv = $looppirow['syvyys']-1; $syv >= 1; $syv--) {
            if (isset($sallitut_valit[$syv]) and count($sallitut_valit[$syv]) > 0) {
              $raj_syvyys = $syv;
              break;
            }
          }

          // Lähin vasemmanpuolinen rajaava taso
          if ($raj_syvyys > 0) {
            foreach ($sallitut_valit[$raj_syvyys] as $lft_rgt) {
              if ($looppirow['lft'] > $lft_rgt[0] and $looppirow['rgt'] < $lft_rgt[1]) {
                $show_node = TRUE;
                break;
              }
            }
          }
          else {
            $show_node = TRUE;
          }
        }
        else {
          $show_node = TRUE;
        }

        if ($show_node) {

          $sel = '';

          if ((count(${"mul_".$tree.$looppirow['syvyys']}) > 0 and in_array($looppirow['node_tunnus'], ${"mul_".$tree.$looppirow['syvyys']})) or
            (isset($monivalintarajaus_dynaaminen) and in_array($looppirow['node_tunnus'], explode(",", $monivalintarajaus_dynaaminen))) or
            (isset($ruksit["dynaaminen_".$tree."_".$looppirow['syvyys']]) and $ruksit["dynaaminen_".$tree."_".$looppirow['syvyys']] != "")) {

            $sel = ' SELECTED';

            // Tämä rajaa tulevia tasoja/nodeja
            $sallitut_valit[$looppirow['syvyys']][] = array($looppirow['lft'], $looppirow['rgt']);
          }

          echo "<option value='{$looppirow['node_tunnus']}'{$sel}>{$looppirow['node_nimi']}</option>";
        }

        $edsyvyys = $looppirow["syvyys"];
      }

      // Suljetaan vielä vika selectboxi
      echo "</select><input type='hidden' name='dynaaminen{$tree}maxsyvyys' value='$edsyvyys'></td>";
      echo "</tr>";

      if (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta") !== FALSE) {
        $ruksitchk = "";

        if ($ruksit["dynaaminen_".$tree."_".$edsyvyys] != "") {
          $ruksitchk = "CHECKED";
        }

        //echo "<tr><th>".t("Prio").": <input type='text' name='jarjestys[dynaaminen_".$tree."_".$edsyvyys."]' size='2' value='".$jarjestys["dynaaminen_".$tree."_".$edsyvyys]."'>";
        //echo t(ucfirst($tree)).t("kategorioittain")." {$edsyvyys} <input type='checkbox' name='ruksit[dynaaminen_".$tree."_".$edsyvyys."]' value='dynaaminen_".$tree."_".$edsyvyys."' {$ruksitchk} {$autosubmit}></th></tr>";
      }
      echo "</table></div>";
    }
  }

  if (substr($monivalintalaatikko, 0, 9) == "parametri") {

    unset($sxresult);

    $parametri = substr($monivalintalaatikko, 10);

    // Näytetään parametrit jos ollaan valittu osasto tai  tuoteryhmä tai rajattu segmenteillä
    if ($lisa_haku_osasto != "" or $lisa_haku_try != "" or $lisa_dynaaminen["tuote"] != "") {

      if ($kukarow["extranet"] == "" and $verkkokauppa == "") {
        if (!isset($poistetut) or $poistetut == '') {
          $poislisa_mulsel = " and tuote.status != 'P' ";
        }
      }

      // dynaaminen dropdowni
      if (isset($monivalinta_tuotteet)) {
        $query = "SELECT 1_t_av_$parametri.selite as $parametri,
                  sum(if(tuote.status != 'P', 1, (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0))) naytetaanko
                  FROM tuote
                  {$lisa_dynaaminen["tuote"]}
                  $lisa_parametri
                  JOIN tuotteen_avainsanat 1_t_av_$parametri ON (1_t_av_$parametri.yhtio = tuote.yhtio and 1_t_av_$parametri.laji = 'parametri_$parametri' and 1_t_av_$parametri.tuoteno = tuote.tuoteno and 1_t_av_$parametri$kieli_lisa and 1_t_av_$parametri.selite != '')
                  JOIN avainsana ON (avainsana.yhtio = tuote.yhtio and avainsana.selite = '$parametri' and avainsana.laji = 'PARAMETRI' and avainsana.kieli in ('$yhtiorow[kieli]', '') $avainlisa)
                  WHERE tuote.yhtio = '$kukarow[yhtio]'
                  $lisa_haku_osasto
                  $lisa_haku_try
                  $lisa_haku_tme
                  $kieltolisa
                  $extra_poislisa
                  $poislisa_mulsel
                  AND tuote.tuoteno in ($monivalinta_tuotteet)
                  GROUP BY 1
                  HAVING naytetaanko > 0
                  ORDER BY 1_t_av_$parametri.jarjestys, 1_t_av_$parametri.selite";
        $sxresult = pupe_query($query);
      }
      else {
        $query = "SELECT distinct 1_t_av_$parametri.selite as $parametri
                  FROM tuote
                  {$lisa_dynaaminen["tuote"]}
                  $lisa_parametri
                  JOIN tuotteen_avainsanat 1_t_av_$parametri ON (1_t_av_$parametri.yhtio = tuote.yhtio and 1_t_av_$parametri.laji = 'parametri_$parametri' and 1_t_av_$parametri.tuoteno = tuote.tuoteno and 1_t_av_$parametri$kieli_lisa and 1_t_av_$parametri.selite != '')
                  JOIN avainsana ON (avainsana.yhtio = tuote.yhtio and avainsana.selite = '$parametri' and avainsana.laji = 'PARAMETRI' and avainsana.kieli in ('$yhtiorow[kieli]', '') $avainlisa)
                  WHERE tuote.yhtio = '$kukarow[yhtio]'
                  $lisa_haku_osasto
                  $lisa_haku_try
                  $lisa_haku_tme
                  $lisa_haku_malli
                  $kieltolisa
                  $extra_poislisa
                  $poislisa_mulsel
                  GROUP BY 1
                  ORDER BY 1_t_av_$parametri.jarjestys, 1_t_av_$parametri.selite";
        $sxresult = pupe_query($query);
      }

      if (is_resource($sxresult) and mysql_num_rows($sxresult) > 0) {

        echo "<div class='monivalintadiv'><table valign='top'>";

        if ($piirra_otsikot) {
          echo "<tr><th>";
          echo t_avainsana("PARAMETRI", "", " and avainsana.selite='$parametri'", "", "", "selitetark");
          echo "</th></tr>";
        }

        echo "<tr><td nowrap style='padding:0px'>";
        echo "<select name='mul_{$parametri}[]' ";

        if (!in_array("parametri_$parametri", $monivalintalaatikot_normaali)) {
          echo " multiple='multiple' class='multipleselect' size='7' ";
        }

        if (isset($hae_ja_selaa_row['selite']) and $hae_ja_selaa_row['selite'] == 'B') {
          echo " style='width: 170px;' ";
        }

        echo "$autosubmit>";
        echo "<option value=''>", t("Ei valintaa"), "</option>";

        while (${$parametri."row"} = mysql_fetch_assoc($sxresult)) {
          $sel = '';

          if (count(${"mul_".$parametri}) > 0 and in_array(strtolower(trim(${$parametri."row"}["$parametri"])), ${"mul_".$parametri})) {
            $sel = 'SELECTED';
          }

          echo "<option value='".strtolower(${$parametri."row"}[$parametri])."' $sel>".${$parametri."row"}[$parametri]."</option>";
        }

        echo "</select></td>";
        echo "</tr>";

        if (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta") !== FALSE) {
          $ruksitchk = "";

          if ($ruksit[$parametri] != "") {
            $ruksitchk = "CHECKED";
          }

          echo "<tr><th>".t("Prio").": <input type='text' name='jarjestys[$parametri]' size='2' value='$jarjestys[$parametri]'> {$parametri}ttain <input type='checkbox' name='ruksit[$parametri]' value='$parametri' $ruksitchk></th></tr>";
        }

        echo "</table></div>";
      }
    }
  }

  if ($monivalintalaatikko == "laskumyyja") {
    echo "<div class='monivalintadiv'><table valign='top'>";

    if ($piirra_otsikot) {
      echo "<tr><th>", t("Myyjä"), "</th></tr>";
    }

    echo "<tr>";

    // tehdään query
    $query = "SELECT DISTINCT myyja, nimi, tunnus
              FROM kuka
              WHERE yhtio = '$kukarow[yhtio]'
              AND myyja   > 0
              ORDER BY myyja";
    $sresult = pupe_query($query);

    echo "<td nowrap style='padding:0px'><select name='mul_laskumyyja[]' multiple='multiple' class='multipleselect' size='7' $autosubmit>";
    echo "<option value=''>".t("Ei valintaa")."</option>";

    while ($sxrow = mysql_fetch_array($sresult)) {
      $sel = '';

      if (count($mul_laskumyyja) > 0) {
        if (in_array(trim($sxrow['tunnus']), $mul_laskumyyja)) {
          $sel = 'SELECTED';
        }
      }

      echo "<option value='$sxrow[tunnus]' $sel>$sxrow[myyja] $sxrow[nimi]</option>";
    }

    echo "</select></td>";
    echo "</tr>";

    if (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta") !== FALSE) {
      $ruksitchk = "";

      if ($ruksit["laskumyyja"] != "") {
        $ruksitchk = "CHECKED";
      }

      echo "<tr><th>".t("Prio").": <input type='text' name='jarjestys[laskumyyja]' size='2' value='$jarjestys[laskumyyja]'> ".t("Myyjittäin")." <input type='checkbox' name='ruksit[laskumyyja]' value='laskumyyja' $ruksitchk></th></tr>";
    }

    echo "</table></div>";
  }

  if ($monivalintalaatikko == "asiakastila") {
    echo "<div class='monivalintadiv'><table valign='top'>";

    if ($piirra_otsikot) {
      echo "<tr><th>", t("Asiakastila"), "</th></tr>";
    }

    echo "<tr>";

    $sresult = t_avainsana("ASIAKASTILA");

    echo "<td nowrap style='padding:0px'><select name='mul_asiakastila[]' multiple='multiple' class='multipleselect' size='7' $autosubmit>";
    echo "<option value=''>".t("Ei valintaa")."</option>";

    while ($sxrow = mysql_fetch_array($sresult)) {
      $sel = '';

      if (count($mul_asiakastila) > 0) {
        if (in_array(trim($sxrow['selite']), $mul_asiakastila)) {
          $sel = 'SELECTED';
        }
      }

      echo "<option value='$sxrow[selite]' $sel>$sxrow[selite]";
      if ($sxrow["selite"] != $sxrow["selitetark"]) echo " $sxrow[selitetark]";
      echo "</option>";
    }

    echo "</select></td>";
    echo "</tr>";

    if (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta") !== FALSE) {
      $ruksitchk = "";

      if ($ruksit["asiakastila"] != "") {
        $ruksitchk = "CHECKED";
      }

      echo "<tr><th>".t("Prio").": <input type='text' name='jarjestys[asiakastila]' size='2' value='$jarjestys[asiakastila]'> ".t("Asiakastiloittain")." <input type='checkbox' name='ruksit[asiakastila]' value='asiakastila' $ruksitchk></th></tr>";
    }

    echo "</table></div>";
  }

  if ($monivalintalaatikko == "tuotemyyja") {
    echo "<div class='monivalintadiv'><table valign='top'>";

    if ($piirra_otsikot) {
      echo "<tr><th>", t("Tuotemyyjä"), "</th></tr>";
    }

    echo "<tr>";

    // tehdään query
    $query = "SELECT DISTINCT myyja, nimi
              FROM kuka
              WHERE yhtio = '$kukarow[yhtio]'
              AND myyja   > 0
              ORDER BY myyja";
    $sresult = pupe_query($query);

    echo "<td nowrap style='padding:0px'><select name='mul_tuotemyyja[]' multiple='multiple' class='multipleselect' size='7' $autosubmit>";
    echo "<option value=''>".t("Ei valintaa")."</option>";

    while ($sxrow = mysql_fetch_array($sresult)) {
      $sel = '';

      if (count($mul_tuotemyyja) > 0) {
        if (in_array(trim($sxrow['myyja']), $mul_tuotemyyja)) {
          $sel = 'SELECTED';
        }
      }

      echo "<option value='$sxrow[myyja]' $sel>$sxrow[myyja] $sxrow[nimi]</option>";
    }

    echo "</select></td>";
    echo "</tr>";

    if (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta") !== FALSE) {
      $ruksitchk = "";

      if ($ruksit["tuotemyyja"] != "") {
        $ruksitchk = "CHECKED";
      }

      echo "<tr><th>".t("Prio").": <input type='text' name='jarjestys[tuotemyyja]' size='2' value='$jarjestys[tuotemyyja]'> ".t("Tuotemyyjittäin")." <input type='checkbox' name='ruksit[tuotemyyja]' value='tuotemyyja' $ruksitchk></th></tr>";
    }

    echo "</table></div>";
  }

  if ($monivalintalaatikko == "asiakasmyyja") {
    echo "<div class='monivalintadiv'><table valign='top'>";

    if ($piirra_otsikot) {
      echo "<tr><th>", t("Asiakasmyyjä"), "</th></tr>";
    }

    echo "<tr>";

    // tehdään query
    $query = "SELECT DISTINCT myyja, nimi
              FROM kuka
              WHERE yhtio = '$kukarow[yhtio]'
              AND myyja   > 0
              ORDER BY myyja";
    $sresult = pupe_query($query);

    echo "<td nowrap style='padding:0px'><select name='mul_asiakasmyyja[]' multiple='multiple' class='multipleselect' size='7' $autosubmit>";
    echo "<option value=''>".t("Ei valintaa")."</option>";

    while ($sxrow = mysql_fetch_array($sresult)) {
      $sel = '';

      if (count($mul_asiakasmyyja) > 0) {
        if (in_array(trim($sxrow['myyja']), $mul_asiakasmyyja)) {
          $sel = 'SELECTED';
        }
      }

      echo "<option value='$sxrow[myyja]' $sel>$sxrow[myyja] $sxrow[nimi]</option>";
    }

    echo "</select></td>";
    echo "</tr>";

    if (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta") !== FALSE) {
      $ruksitchk = "";

      if ($ruksit["asiakasmyyja"] != "") {
        $ruksitchk = "CHECKED";
      }

      echo "<tr><th>".t("Prio").": <input type='text' name='jarjestys[asiakasmyyja]' size='2' value='$jarjestys[asiakasmyyja]'> ".t("Asiakasmyyjittäin")." <input type='checkbox' name='ruksit[asiakasmyyja]' value='asiakasmyyja' $ruksitchk></th></tr>";
    }

    echo "</table></div>";
  }

  if ($monivalintalaatikko == "tuoteostaja") {
    echo "<div class='monivalintadiv'><table valign='top'>";

    if ($piirra_otsikot) {
      echo "<tr><th>", t("Tuoteostaja"), "</th></tr>";
    }

    echo "<tr>";

    $query = "SELECT DISTINCT myyja, nimi
              FROM kuka
              WHERE yhtio = '$kukarow[yhtio]'
              AND myyja   > 0
              ORDER BY myyja";
    $sresult = pupe_query($query);

    echo "<td nowrap style='padding:0px'><select name='mul_tuoteostaja[]' multiple='multiple' class='multipleselect' size='7' $autosubmit>";
    echo "<option value=''>".t("Ei valintaa")."</option>";

    while ($sxrow = mysql_fetch_array($sresult)) {
      $sel = '';

      if (count($mul_tuoteostaja) > 0) {
        if (in_array(trim($sxrow['myyja']), $mul_tuoteostaja)) {
          $sel = 'SELECTED';
        }
      }

      echo "<option value='$sxrow[myyja]' $sel>$sxrow[myyja] $sxrow[nimi]</option>";
    }

    echo "</select></td>";
    echo "</tr>";

    if (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta") !== FALSE) {
      $ruksitchk = "";

      if ($ruksit["tuoteostaja"] != "") {
        $ruksitchk = "CHECKED";
      }

      echo "<tr><th>".t("Prio").": <input type='text' name='jarjestys[tuoteostaja]' size='2' value='$jarjestys[tuoteostaja]'> ".t("Tuoteostajaittain")." <input type='checkbox' name='ruksit[tuoteostaja]' value='tuoteostaja' $ruksitchk></th></tr>";
    }

    echo "</table></div>";
  }

  if ($monivalintalaatikko == "asiakasosasto") {
    echo "<div class='monivalintadiv'><table valign='top'>";

    if ($piirra_otsikot) {
      echo "<tr><th>", t("Asiakasosasto"), "</th></tr>";
    }

    echo "<tr>";

    $sresult = t_avainsana("ASIAKASOSASTO");

    echo "<td nowrap style='padding:0px'><select name='mul_asiakasosasto[]' multiple='multiple' class='multipleselect' size='7' $autosubmit>";
    echo "<option value=''>".t("Ei valintaa")."</option>";

    while ($sxrow = mysql_fetch_array($sresult)) {
      $sel = '';

      if (count($mul_asiakasosasto) > 0) {
        if (in_array(trim($sxrow['selite']), $mul_asiakasosasto)) {
          $sel = 'SELECTED';
        }
      }

      echo "<option value='$sxrow[selite]' $sel>$sxrow[selite]";
      if ($sxrow["selite"] != $sxrow["selitetark"]) echo " $sxrow[selitetark]";
      echo "</option>";
    }

    echo "</select></td>";
    echo "</tr>";

    if (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta") !== FALSE) {
      $ruksitchk = "";

      if ($ruksit["asiakasosasto"] != "") {
        $ruksitchk = "CHECKED";
      }

      echo "<tr><th>".t("Prio").": <input type='text' name='jarjestys[asiakasosasto]' size='2' value='$jarjestys[asiakasosasto]'> ".t("Asiakasosastoittain")." <input type='checkbox' name='ruksit[asiakasosasto]' value='asiakasosasto' $ruksitchk></th></tr>";
    }

    echo "</table></div>";
  }

  if ($monivalintalaatikko == "asiakasryhma") {
    echo "<div class='monivalintadiv'><table valign='top'>";

    if ($piirra_otsikot) {
      echo "<tr><th>", t("Asiakasryhmä"), "</th></tr>";
    }

    echo "<tr>";

    $sresult = t_avainsana("ASIAKASRYHMA");

    echo "<td nowrap style='padding:0px'><select name='mul_asiakasryhma[]' multiple='multiple' class='multipleselect' size='7' $autosubmit>";
    echo "<option value=''>".t("Ei valintaa")."</option>";

    while ($sxrow = mysql_fetch_array($sresult)) {
      $sel = '';

      if (count($mul_asiakasryhma) > 0) {
        if (in_array(trim($sxrow['selite']), $mul_asiakasryhma)) {
          $sel = 'SELECTED';
        }
      }

      echo "<option value='$sxrow[selite]' $sel>$sxrow[selite]";
      if ($sxrow["selite"] != $sxrow["selitetark"]) echo " $sxrow[selitetark]";
      echo "</option>";
    }

    echo "</select></td>";
    echo "</tr>";

    if (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta") !== FALSE) {
      $ruksitchk = "";

      if ($ruksit["asiakasryhma"] != "") {
        $ruksitchk = "CHECKED";
      }

      echo "<tr><th>".t("Prio").": <input type='text' name='jarjestys[asiakasryhma]' size='2' value='$jarjestys[asiakasryhma]'> ".t("Asiakasryhmittäin")." <input type='checkbox' name='ruksit[asiakasryhma]' value='asiakasryhma' $ruksitchk></th></tr>";
    }

    echo "</table></div>";
  }

  if ($monivalintalaatikko == "asiakaspiiri") {
    echo "<div class='monivalintadiv'><table valign='top'>";

    if ($piirra_otsikot) {
      echo "<tr><th>", t("Asiakaspiiri"), "</th></tr>";
    }

    echo "<tr>";

    $sresult = t_avainsana("PIIRI");

    echo "<td nowrap style='padding:0px'><select name='mul_asiakaspiiri[]' multiple='multiple' class='multipleselect' size='7' $autosubmit>";
    echo "<option value=''>".t("Ei valintaa")."</option>";

    while ($sxrow = mysql_fetch_array($sresult)) {
      $sel = '';

      if (count($mul_asiakaspiiri) > 0) {
        if (in_array(trim($sxrow['selite']), $mul_asiakaspiiri)) {
          $sel = 'SELECTED';
        }
      }

      echo "<option value='$sxrow[selite]' $sel>$sxrow[selite]";
      if ($sxrow["selite"] != $sxrow["selitetark"]) echo " $sxrow[selitetark]";
      echo "</option>";
    }

    echo "</select></td>";
    echo "</tr>";

    if (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta") !== FALSE) {
      $ruksitchk = "";

      if ($ruksit["asiakaspiiri"] != "") {
        $ruksitchk = "CHECKED";
      }

      echo "<tr><th>".t("Prio").": <input type='text' name='jarjestys[asiakaspiiri]' size='2' value='$jarjestys[asiakaspiiri]'> ".t("Asiakaspiireittäin")." <input type='checkbox' name='ruksit[asiakaspiiri]' value='asiakaspiiri' $ruksitchk><br>";

      echo "<input type='radio' name='piirivalinta' value='lasku' {$laskuvalintachk}>", t("Laskuilta");
      echo "<input type='radio' name='piirivalinta' value='asiakas' {$asiakasvalintachk}>", t("Asiakkailta");

      echo "</th></tr>";
    }

    echo "</table></div>";
  }

  if ($monivalintalaatikko == "toimipaikka") {
    $toimipaikka_result = hae_yhtion_toimipaikat($kukarow['yhtio']);

    if (mysql_num_rows($toimipaikka_result) > 0) {

      echo "<div class='monivalintadiv'>";
      echo "<table valign='top'>";
      if ($piirra_otsikot) {
        echo "<tr><th>", t("Toimipaikka"), "</th></tr>";
      }
      echo "<tr>";
      echo "<td nowrap style='padding:0px'><select name='mul_toimipaikka[]' multiple='multiple' class='multipleselect' size='7' $autosubmit>";
      echo "<option value=''>".t("Ei valintaa")."</option>";

      while ($toimipaikka = mysql_fetch_assoc($toimipaikka_result)) {
        $sel = '';
        if (count($mul_toimipaikka) > 0) {
          if (in_array(trim($toimipaikka['tunnus']), $mul_toimipaikka)) {
            $sel = 'SELECTED';
          }
        }

        echo "<option value='{$toimipaikka['tunnus']}' $sel>{$toimipaikka['nimi']}" . "</option>";
      }

      echo "</tr>";

      if (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta") !== FALSE) {
        $ruksitchk = "";

        if ($ruksit["toimipaikka"] != "") {
          $ruksitchk = "CHECKED";
        }

        echo "<tr><th>".t("Prio").": <input type='text' name='jarjestys[toimipaikka]' size='2' value='$jarjestys[toimipaikka]'> ".t("Toimipaikoittain")." <input type='checkbox' name='ruksit[toimipaikka]' value='toimipaikka' $ruksitchk><br>";

        echo "</th></tr>";
      }

      echo "</table>";
      echo "</div>";
    }
  }

  if ($monivalintalaatikko == "maa") {
    $query = "SELECT distinct koodi, nimi, MIN(tunnus) id
              FROM maat
              WHERE koodi != ''
              AND nimi    != ''
              GROUP BY 1,2
              ORDER BY 1,2";
    $maa_res = pupe_query($query);

    if (mysql_num_rows($maa_res) > 0) {

      echo "<div class='monivalintadiv'>";
      echo "<table valign='top'>";
      if ($piirra_otsikot) {
        echo "<tr><th>", t("Maa"), "</th></tr>";
      }
      echo "<tr>";
      echo "<td nowrap style='padding:0px'><select name='mul_maa[]' multiple='multiple' class='multipleselect' size='7' $autosubmit>";
      echo "<option value=''>".t("Ei valintaa")."</option>";

      while ($maa_row = mysql_fetch_assoc($maa_res)) {
        $sel = '';
        if (count($mul_maa) > 0) {
          if (in_array(trim($maa_row['id']), $mul_maa)) {
            $sel = 'SELECTED';
          }
        }

        echo "<option value='{$maa_row['id']}' $sel>{$maa_row['nimi']}" . "</option>";
      }

      echo "</select></td></tr>";

      if (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta") !== FALSE) {
        $ruksitchk = "";

        if ($ruksit["maa"] != "") {
          $ruksitchk = "CHECKED";
        }

        echo "<tr><th>".t("Prio").": <input type='text' name='jarjestys[maa]' size='2' value='$jarjestys[maa]'> ".t("Maittain")." <input type='checkbox' name='ruksit[maa]' value='maa' $ruksitchk><br>";

        echo "</th></tr>";
      }

      echo "</table>";
      echo "</div>";
    }
  }

  if ($monivalintalaatikko == "kustp") {

    echo "<script language='javascript'>
        function kustannuspaikkaselect(klikattu) {

          var sel = document.getElementById(klikattu);
          var mul = document.getElementById('mul_kustp[]');
          var len = klikattu.length;

          var cli = 0;
          if (sel.selected) {
            for (var i = 0; i < mul.options.length; i++) {
                if (mul.options[i].id.substring(0,len) == klikattu && mul.options[i].id != klikattu) {
                mul.options[i].selected = 1;
              }
            }
          }
        }
      </script> ";

    $omakustprajaus = "";

    if (!empty($vainomakustp)) {
      $omakustprajaus = " and kustannuspaikka.tunnus = {$kukarow["osasto"]} ";
    }

    $query = "SELECT tunnus, nimi, koodi, ifnull(isa_tarkenne, 0) as isa_tarkenne
              FROM kustannuspaikka
              WHERE yhtio   = '$kukarow[yhtio]'
              {$omakustprajaus}
              and kaytossa != 'E'
              and tyyppi    = 'K'
              ORDER BY isa_tarkenne, koodi+0, koodi, nimi";
    $sresult = pupe_query($query);

    $kustparray = array();

    while ($sxrow = mysql_fetch_array($sresult)) {
      $kustparray[$sxrow["isa_tarkenne"]][$sxrow["tunnus"]] = $sxrow;
    }

    function lapsitasot($isataso, $sisennys, $tunnukset, $kustparray) {
      global $mul_kustp;

      $sisennys++;

      $tunnukset_save = $tunnukset;

      if (isset($kustparray[$isataso])) {
        foreach ($kustparray[$isataso] as $lapsitaso => $sxrow) {

          $sel = '';
          $tunnukset .= "$sxrow[tunnus],";

          if (count($mul_kustp) > 0) {
            if (in_array(trim($sxrow['tunnus']), $mul_kustp)) {
              $sel = 'SELECTED';
            }
          }

          echo "<option value='$sxrow[tunnus]' id='$tunnukset' name='$tunnukset' $sel onclick='javascript:kustannuspaikkaselect(\"$tunnukset\");'>";

          for ($i = 0; $i < $sisennys; $i++) echo "&raquo;";

          echo "$sxrow[koodi] $sxrow[nimi]</option>";

          lapsitasot($sxrow["tunnus"], $sisennys, $tunnukset, $kustparray);

          $tunnukset = $tunnukset_save;
        }
      }
    }

    if (mysql_num_rows($sresult) > 0) {
      echo "<div class='monivalintadiv'><table valign='top'>";

      if ($piirra_otsikot) {
        echo "<tr><th>", t("Kustannuspaikka"), "</th></tr>";
      }

      echo "<tr>";

      echo "<td nowrap style='padding:0px'>";

      $multiple_lisa = " multiple='multiple' class='multipleselect' size='7' ";

      if (in_array("kustp", $monivalintalaatikot_normaali)) {
        $multiple_lisa = "";
      }

      echo "<select id='mul_kustp[]' name='mul_kustp[]' $multiple_lisa $autosubmit>";


      echo "<option value=''>".t("Ei valintaa")."</option>";
      echo "<option value='0'>" . t("Ei kustannuspaikkaa" . "</option>");

      lapsitasot(0, -1, "0,", $kustparray);

      echo "</select></td>";
      echo "</tr>";

      if (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta") !== FALSE) {
        $ruksitchk = "";

        if ($ruksit["kustp"] != "") {
          $ruksitchk = "CHECKED";
        }

        echo "<tr><th>".t("Prio").": <input type='text' name='jarjestys[kustp]' size='2' value='$jarjestys[kustp]'> ".t("Kustannuspaikoittain")." <input type='checkbox' name='ruksit[kustp]' value='kustp' $ruksitchk><br>";

        echo "<input type='radio' name='kustapvalinta' value='tuote' {$kustapvalinta_tuote}>", t("Tuotteilta");
        echo "<input type='radio' name='kustapvalinta' value='asiakas' {$kustapvalinta_asiakas}>", t("Asiakkailta");
        echo "</th></tr>";
      }
      elseif (strpos($_SERVER['SCRIPT_NAME'], "matkalaskuraportti") !== FALSE) {
        $ruksitchk_kustp = "";
        $ruksitchk_toimittajalta = "";
        $ruksitchk_tuotteilta = "";

        if ($ruksit["kustp"] != "") {
          $ruksitchk_kustp = "CHECKED";
        }
        if ($kenelta_kustp == "toimittajilta") {
          $ruksitchk_toimittajalta = "CHECKED";
        }
        if ($kenelta_kustp == "tuotteilta") {
          $ruksitchk_tuotteilta = "CHECKED";
        }

        echo "<tr><th>";
        echo t("Prio").": <input type='text' name='jarjestys[kustp]' size='2' value='$jarjestys[kustp]'>";
        echo t("Kustannuspaikoittain")." <input type='checkbox' name='ruksit[kustp]' value='kustp' $ruksitchk_kustp>";
        echo "</th></tr>";
        echo "<tr><th>";
        echo t("Toimittajilta")." <input type='radio' name='kenelta_kustp' value='toimittajilta' $ruksitchk_toimittajalta> ";
        echo t("Tuotteilta")." <input type='radio' name='kenelta_kustp' value='tuotteilta' $ruksitchk_tuotteilta> ";
        echo "</th></tr>";
      }

      echo "</table></div>";
    }
  }

  if ($monivalintalaatikko == "kohde") {

    $query = "SELECT tunnus, nimi, koodi
              FROM kustannuspaikka
              WHERE yhtio   = '$kukarow[yhtio]'
              and kaytossa != 'E'
              and tyyppi    = 'O'
              ORDER BY koodi+0, koodi, nimi";
    $sresult = pupe_query($query);

    if (mysql_num_rows($sresult) > 0) {
      echo "<div class='monivalintadiv'><table valign='top'>";

      if ($piirra_otsikot) {
        echo "<tr><th>", t("Kohde"), "</th></tr>";
      }

      echo "<tr>";

      echo "<td nowrap style='padding:0px'><select name='mul_kohde[]' multiple='multiple' class='multipleselect' " .
        "size='7' style='width: 100%;' $tunnukset_save $autosubmit>";
      echo "<option value=''>" . t("Ei valintaa") . "</option>";
      echo "<option value='0'>" . t("Ei kohdetta") . "</option>";

      while ($sxrow = mysql_fetch_array($sresult)) {
        $sel = '';

        if (count($mul_kohde) > 0) {
          if (in_array(trim($sxrow['tunnus']), $mul_kohde)) {
            $sel = 'SELECTED';
          }
        }

        echo "<option value='$sxrow[tunnus]' $sel>$sxrow[koodi] $sxrow[nimi]</option>";
      }

      echo "</select></td>";
      echo "</tr>";

      if (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta") !== FALSE) {
        $ruksitchk = "";

        if ($ruksit["kohde"] != "") {
          $ruksitchk = "CHECKED";
        }

        echo "<tr><th>".t("Prio").": <input type='text' name='jarjestys[kohde]' size='2' value='$jarjestys[kohde]'> ".t("Kohteittain")." <input type='checkbox' name='ruksit[kohde]' value='kohde' $ruksitchk><br>";

        echo "<input type='radio' name='kohdevalinta' value='tuote' {$kohdevalinta_tuote}>", t("Tuotteilta");
        echo "<input type='radio' name='kohdevalinta' value='asiakas' {$kohdevalinta_asiakas}>", t("Asiakkailta");
        echo "</th></tr>";
      }

      echo "</table></div>";
    }
  }

  if ($monivalintalaatikko == "projekti") {

    $query = "SELECT tunnus, nimi, koodi
              FROM kustannuspaikka
              WHERE yhtio   = '$kukarow[yhtio]'
              and kaytossa != 'E'
              and tyyppi    = 'P'
              ORDER BY koodi+0, koodi, nimi";
    $sresult = pupe_query($query);

    if (mysql_num_rows($sresult) > 0) {
      echo "<div class='monivalintadiv'><table valign='top'>";

      if ($piirra_otsikot) {
        echo "<tr><th>", t("Projekti"), "</th></tr>";
      }

      echo "<tr>";

      echo "<td nowrap style='padding:0px'><select name='mul_projekti[]' multiple='multiple' class='multipleselect' size='7' $autosubmit>";
      echo "<option value=''>".t("Ei valintaa")."</option>";
      echo "<option value='0'>" . t("Ei projektia") . "</option>";

      while ($sxrow = mysql_fetch_array($sresult)) {
        $sel = '';

        if (count($mul_projekti) > 0) {
          if (in_array(trim($sxrow['tunnus']), $mul_projekti)) {
            $sel = 'SELECTED';
          }
        }

        echo "<option value='$sxrow[tunnus]' $sel>$sxrow[koodi] $sxrow[nimi]</option>";
      }

      echo "</select></td>";
      echo "</tr>";

      if (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta") !== FALSE) {
        $ruksitchk = "";

        if ($ruksit["projekti"] != "") {
          $ruksitchk = "CHECKED";
        }

        echo "<tr><th>".t("Prio").": <input type='text' name='jarjestys[projekti]' size='2' value='$jarjestys[projekti]'> ".t("Projekteittain")." <input type='checkbox' name='ruksit[projekti]' value='projekti' $ruksitchk><br>";

        echo "<input type='radio' name='projektivalinta' value='tuote' {$projektivalinta_tuote}>", t("Tuotteilta");
        echo "<input type='radio' name='projektivalinta' value='asiakas' {$projektivalinta_asiakas}>", t("Asiakkailta");
        echo "</th></tr>";
      }

      echo "</table></div>";
    }
  }
}

if (!isset($php_cli) or !$php_cli) echo "<div style='clear: both;'></div>";

pupeslave_stop();
