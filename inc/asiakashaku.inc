<?php

// tarvitaan $ytunnus (jolla haetaan) tai $asiakasid (joka on asiakas.tunnus) ja $kukarow
// palautetaan $asiakasrow

if (isset($konserni) and trim($konserni) != '') {
  if ($konserni == "KONSERNIVARASTO") {
    if ($yhtiorow['konsernivarasto'] != '' and $konsernivarasto_yhtiot != '') {
      $konsyhtiot = "yhtio in ($konsernivarasto_yhtiot) ";
    }
    else {
      $konsyhtiot = "yhtio = '$kukarow[yhtio]' ";
    }
  }
  else {
    $query = "SELECT distinct yhtio FROM yhtio WHERE (konserni = '$yhtiorow[konserni]' and konserni != '') or (yhtio = '$yhtiorow[yhtio]')";
    $result = pupe_query($query);
    $konsyhtiot = "";

    while ($row = mysql_fetch_array($result)) {
      $konsyhtiot .= " '".$row["yhtio"]."' ,";
    }
    $konsyhtiot = "yhtio in (".substr($konsyhtiot, 0, -1).") ";
  }
}
else {
  $konserni   = "";
  $konsyhtiot = "yhtio = '$kukarow[yhtio]' ";
}

$tp = $kukarow['toimipaikka'];

if (trim($toimipaikka_rajaus) != '') {
  $tp = $toimipaikka_rajaus;
}

if ($toimipaikka_rajaus == 'X' or $yhtiorow['toimipaikkakasittely'] != 'L') {
  $toimipaikkalisa = '';
}
else {
  $toimipaikkalisa = " and asiakas.toimipaikka = {$tp} ";
}

$ytunnus = addslashes(trim($ytunnus));
$ytunnus_talteen = $ytunnus;

$limit = 1000; // montako riviä on maksimi
$monta = 0;
$force_selaus = FALSE;
$asiakasrajaus = "";

if (in_array($kutsuja, array("asiakasemo.php", "kalenteri.php"))) {
  // Tässä haarassa myös prospektit näytetään
  if ($myos_poistetut == "") {
    $asiakasrajaus = " and asiakas.laji != 'P' ";
  }
  else {
    $asiakasrajaus = "";
  }
}
else {
  // Normaalisti prospekteja ei näytetä, mutta jos ollaan tarjouksella voidaa niitä katsoa
  if ($myos_prospektit == "TRUE" and $kutsuja == "otsik.inc" and $toim == "TARJOUS") {
    $asiakasrajaus = " and asiakas.laji not in ('P') ";
  }
  elseif ($myos_poistetut == "") {
    $asiakasrajaus = " and asiakas.laji not in ('P','R') ";
  }
  else {
    $asiakasrajaus = " and asiakas.laji != 'R' ";
  }
}

//  Jos käyttäjällä on valittu piirejä niin sallitaan vain ko. piirin/piirien hakeminen
if ($kukarow["piirit"] != "") {
  $asiakasrajaus .= " and asiakas.piiri IN ($kukarow[piirit]) ";
}

if (is_numeric($asiakasid) and $asiakasid > 0) {
  // Jos asiakaan tunnus on löydetty niin ei rajata "$asiakasrajaus":lla ollenkaan
  $query  = "SELECT asiakas.*, yhtio.nimi yhtionimi,
             if(asiakas.gsm != '', asiakas.gsm,
               if(asiakas.tyopuhelin != '', asiakas.tyopuhelin,
                 if(asiakas.puhelin != '', asiakas.puhelin, ''))) AS toim_puh
             FROM asiakas
             JOIN yhtio on asiakas.yhtio=yhtio.yhtio
             WHERE asiakas.$konsyhtiot
             and asiakas.tunnus = '$asiakasid'";
  $result = pupe_query($query);
  $monta  = mysql_num_rows($result);
}
elseif ($ytunnus != '') {
  if (substr($ytunnus, 0, 1) == "£") {
    // jos eka merkki £ etsitään tilausnumerolla
    // eka merkki pois alusta
    $ytunnus = substr($ytunnus, 1);

    $query  = "SELECT lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, lasku.toim_nimi,
               lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp,
               concat('£',lasku.tunnus) ytunnus, concat('£',lasku.tunnus) tunnus, yhtio.nimi yhtionimi,
               if(asiakas.gsm != '', asiakas.gsm,
                if(asiakas.tyopuhelin != '', asiakas.tyopuhelin,
                  if(asiakas.puhelin != '', asiakas.puhelin, ''))) AS toim_puh
               FROM lasku
               JOIN yhtio on lasku.yhtio=yhtio.yhtio
               JOIN asiakas ON (asiakas.yhtio = lasku.yhtio
                 AND asiakas.tunnus = lasku.liitostunnus)
               WHERE lasku.$konsyhtiot
               and lasku.tunnus     = '$ytunnus'";
    $result = pupe_query($query);
    $monta  = mysql_num_rows($result);
  }

  if (substr($ytunnus, 0, 1) == "*") {
    // jos eka merkki on * etsitään laskuilta asiakastietoja nimellä

    // eka merkki pois alusta
    $ytunnus = substr($ytunnus, 1);

    // kokeillaan ihan asiakkaan nimellä laskulta
    $query  = "SELECT lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, lasku.toim_nimi,
               lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp,
               concat('£',max(lasku.tunnus)) ytunnus, concat('£',max(lasku.tunnus)) tunnus, yhtio.nimi yhtionimi,
                if(asiakas.gsm != '', asiakas.gsm,
                if(asiakas.tyopuhelin != '', asiakas.tyopuhelin,
                  if(asiakas.puhelin != '', asiakas.puhelin, ''))) AS toim_puh
               FROM lasku use index (asiakasnimi)
               JOIN yhtio on lasku.yhtio = yhtio.yhtio
               JOIN asiakas ON (asiakas.yhtio = lasku.yhtio
                AND asiakas.tunnus  = lasku.liitostunnus)
               WHERE lasku.$konsyhtiot
               and lasku.tila      != 'D'
               and match (lasku.nimi) against ('$ytunnus*' IN BOOLEAN MODE)
               GROUP BY 1,2,3,4,5,6,7,8,9,10
               ORDER BY 1";
    $result = pupe_query($query);
    $monta  = mysql_num_rows($result);

    // aina selailuun niin menee liitostunnukset ja ytunnukset oikein
    if ($monta > 0) {
      $force_selaus = TRUE;
    }
  }

  if ($monta == 0  and substr($ytunnus, 0, 1) == "#") {
    // jos eka merkki on # etsitään toimitusnimen perusteella

    // eka merkki pois alusta
    $ytunnus = substr($ytunnus, 1);

    // kokeillaan ihan asiakkaan nimellä laskulta
    $query  = "SELECT lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, lasku.toim_nimi,
               lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp,
               concat('£',max(lasku.tunnus)) ytunnus, concat('£',max(lasku.tunnus)) tunnus, yhtio.nimi yhtionimi
               FROM lasku use index (asiakastoim_nimi)
               JOIN yhtio on lasku.yhtio=yhtio.yhtio
               WHERE lasku.$konsyhtiot
               and lasku.tila != 'D'
               and match (lasku.toim_nimi) against ('$ytunnus*' IN BOOLEAN MODE)
               GROUP BY 1,2,3,4,5,6,7,8,9,10
               ORDER BY 1";
    $result = pupe_query($query);
    $monta  = mysql_num_rows($result);

    // aina selailuun niin menee liitostunnukset ja ytunnukset oikein
    if ($monta > 0) {
      $force_selaus = TRUE;
    }
  }

  if ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA") {
    $query = "SELECT lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, lasku.toim_nimi,
              lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp,
              concat('£',max(lasku.tunnus)) ytunnus, concat('£',max(lasku.tunnus)) tunnus, yhtio.nimi yhtionimi
              FROM lasku
              JOIN yhtio on lasku.yhtio=yhtio.yhtio
              JOIN tyomaarays ON lasku.yhtio=tyomaarays.yhtio and lasku.tunnus=tyomaarays.otunnus
              WHERE lasku.$konsyhtiot
              and lasku.tila       in ('A','L','N')
              and tyomaarays.rekno = '$ytunnus'
              GROUP BY 1,2,3,4,5,6,7,8,9,10
              ORDER BY 1
              LIMIT 50";
    $result = pupe_query($query);
    $monta  = mysql_num_rows($result);

    if ($monta == 0) {
      $query = "SELECT lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, lasku.toim_nimi,
                lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp,
                concat('£',max(lasku.tunnus)) ytunnus, concat('£',max(lasku.tunnus)) tunnus, yhtio.nimi yhtionimi
                FROM lasku
                JOIN yhtio on lasku.yhtio=yhtio.yhtio
                JOIN tyomaarays ON lasku.yhtio=tyomaarays.yhtio and lasku.tunnus=tyomaarays.otunnus
                WHERE lasku.$konsyhtiot
                and lasku.tila         in ('A','L','N')
                and tyomaarays.valmnro = '$ytunnus'
                GROUP BY 1,2,3,4,5,6,7,8,9,10
                ORDER BY 1
                LIMIT 50";
      $result = pupe_query($query);
      $monta  = mysql_num_rows($result);
    }
  }

  if ($monta == 0) {
    // exactihaku
    $query  = "(SELECT asiakas.*, yhtio.nimi yhtionimi, '1' osuma
                FROM asiakas
                JOIN yhtio on asiakas.yhtio=yhtio.yhtio
                WHERE asiakas.$konsyhtiot
                and REPLACE(REPLACE(asiakas.ytunnus, '-', ''), '+', '') = '".str_replace(array('-', '+'), '', $ytunnus)."'
                and asiakas.ytunnus!=''
                $toimipaikkalisa
                $lajilisa $asiakasrajaus)
                UNION
                (SELECT asiakas.*, yhtio.nimi yhtionimi, '1' osuma
                FROM asiakas
                JOIN yhtio on asiakas.yhtio=yhtio.yhtio
                WHERE asiakas.$konsyhtiot
                and asiakas.asiakasnro             = '$ytunnus'
                and asiakas.asiakasnro             not in ('0','')
                $toimipaikkalisa
                $lajilisa $asiakasrajaus)
                UNION
                (SELECT asiakas.*, yhtio.nimi yhtionimi, '1' osuma
                FROM asiakas
                JOIN yhtio on asiakas.yhtio=yhtio.yhtio
                WHERE asiakas.$konsyhtiot
                and REPLACE(REPLACE(asiakas.ovttunnus, '-', ''), '+', '') = '".str_replace(array('-', '+'), '', $ytunnus)."'
                and asiakas.ovttunnus!=''
                $toimipaikkalisa
                $lajilisa $asiakasrajaus)
                UNION
                (SELECT asiakas.*, yhtio.nimi yhtionimi, '1' osuma
                FROM asiakas
                JOIN yhtio on asiakas.yhtio=yhtio.yhtio
                WHERE asiakas.$konsyhtiot
                and asiakas.toim_ovttunnus         = '$ytunnus'
                and asiakas.toim_ovttunnus!=''
                $toimipaikkalisa
                $lajilisa $asiakasrajaus)
                UNION
                (SELECT asiakas.*, yhtio.nimi yhtionimi, '5' osuma
                FROM asiakas
                JOIN asiakkaan_avainsanat ON (asiakas.yhtio=asiakkaan_avainsanat.yhtio and asiakas.tunnus=asiakkaan_avainsanat.liitostunnus and asiakkaan_avainsanat.laji='kantaasiakastunnus')
                JOIN yhtio on asiakas.yhtio=yhtio.yhtio
                WHERE asiakas.$konsyhtiot
                and asiakkaan_avainsanat.avainsana = '$ytunnus'
                and asiakkaan_avainsanat.avainsana!=''
                $toimipaikkalisa
                $lajilisa $asiakasrajaus)
                ORDER BY nimi";
    $result = pupe_query($query);
    $monta  = mysql_num_rows($result);
  }

  if ($monta == 0) {
    // kokeillaan ytunnuksen alulla
    $query  = "SELECT asiakas.*, yhtio.nimi yhtionimi
               FROM asiakas
               JOIN yhtio on asiakas.yhtio=yhtio.yhtio
               WHERE asiakas.$konsyhtiot
               and asiakas.ytunnus like '$ytunnus%'
               and asiakas.ytunnus!=''
               $lajilisa
               $asiakasrajaus
               $toimipaikkalisa
               ORDER BY asiakas.nimi";
    $result = pupe_query($query);
    $monta  = mysql_num_rows($result);
  }

  if ($monta == 0 and is_string($ytunnus)) {

    // ei rikota alkuperäistä ytunnus-muuttuja
    $ytunnus2 = $ytunnus;

    // mahdollistetaan asiakashaku monella eri sanalla
    if (strpos($ytunnus, " ") !== FALSE) {
      $ytunnus2 = str_replace(" ", "%", $ytunnus);
    }

    $osoitelisa = '';

    if (trim($osoite) != '') {
      $osoitelisa = " and asiakas.osoite like '".mysql_real_escape_string($osoite)."%' ";
      $osoite_toim_lisa = " and asiakas.toim_osoite like '".mysql_real_escape_string($osoite)."%' ";
    }

    $postitplisa = '';

    // jos on annettu postitoimipaikka, niin lisätää se jokaiseen asiakashakuqueryyn ja ei tehdä erillistä postitoimipaikka unionia
    if (trim($postitp) != '') {
      $postitp2 = mysql_real_escape_string($postitp);
      $postitplisa = " and asiakas.postitp like '%$postitp2%' ";
      $postitp_toim_lisa = " and asiakas.toim_postitp like '%$postitp2%' ";
    }
    else {
      $postitp2 = $ytunnus;
    }

    $postinolisa = '';

    if (trim($postino) != '') {
      $postinolisa = " and asiakas.postino like '%".(int) $postino."%' ";
      $postino_toim_lisa = " and asiakas.toim_postino like '%".(int) $postino."%' ";
    }

    // arvailuhaku
    $query = "";

    if (strpos($_SERVER['SCRIPT_NAME'], "asiakasmemo.php") !== FALSE) {
      $query .= "(SELECT asiakas.*, yhtio.nimi yhtionimi,
                 if (yht.nimi = '{$ytunnus2}', 1, if(left(yht.nimi, length('{$ytunnus2}')) = '{$ytunnus2}', 2, 3)) apujarjestys,
                 '0' apujarjestys2,
                 yht.nimi yhteyshenkilo
                 FROM asiakas
                 JOIN yhtio on (asiakas.yhtio=yhtio.yhtio)
                 JOIN yhteyshenkilo yht ON (yht.yhtio = asiakas.yhtio and yht.liitostunnus = asiakas.tunnus and yht.tyyppi = 'A')
                 WHERE asiakas.$konsyhtiot
                 and yht.nimi like '%$ytunnus2%'
                 and yht.nimi != ''
                 $toimipaikkalisa
                 $osoitelisa
                 $postitplisa
                 $postinolisa
                 $lajilisa $asiakasrajaus)
                 UNION ";
    }

    $query .= "(SELECT asiakas.*, yhtio.nimi yhtionimi,
                if (asiakas.nimi = '{$ytunnus2}', 1, if(left(asiakas.nimi, length('{$ytunnus2}')) = '{$ytunnus2}', 2, 3)) apujarjestys,
                '1' apujarjestys2,
                '' yhteyshenkilo
                FROM asiakas
                JOIN yhtio on asiakas.yhtio=yhtio.yhtio
                WHERE asiakas.$konsyhtiot
                and asiakas.nimi          like '%$ytunnus2%'
                and asiakas.nimi!=''
                $toimipaikkalisa
                $osoitelisa
                $postitplisa
                $postinolisa
                $lajilisa $asiakasrajaus)
                UNION
                (SELECT asiakas.*, yhtio.nimi yhtionimi,
                if (asiakas.nimitark = '{$ytunnus2}', 1, if(left(asiakas.nimitark, length('{$ytunnus2}')) = '{$ytunnus2}', 2, 3)) apujarjestys,
                '2' apujarjestys2,
                '' yhteyshenkilo
                FROM asiakas
                JOIN yhtio on asiakas.yhtio=yhtio.yhtio
                WHERE asiakas.$konsyhtiot
                and asiakas.nimitark      like '%$ytunnus2%'
                and asiakas.nimitark!=''
                $toimipaikkalisa
                $osoitelisa
                $postitplisa
                $postinolisa
                $lajilisa $asiakasrajaus)
                UNION
                (SELECT asiakas.*, yhtio.nimi yhtionimi,
                if (asiakas.toim_nimi = '{$ytunnus2}', 1, if(left(asiakas.toim_nimi, length('{$ytunnus2}')) = '{$ytunnus2}', 2, 3)) apujarjestys,
                '3' apujarjestys2,
                '' yhteyshenkilo
                FROM asiakas
                JOIN yhtio on asiakas.yhtio=yhtio.yhtio
                WHERE asiakas.$konsyhtiot
                and asiakas.toim_nimi     like '%$ytunnus2%'
                and asiakas.toim_nimi!=''
                $toimipaikkalisa
                $osoite_toim_lisa
                $postitp_toim_lisa
                $postino_toim_lisa
                $lajilisa $asiakasrajaus)
                UNION
                (SELECT asiakas.*, yhtio.nimi yhtionimi,
                if (asiakas.toim_nimitark = '{$ytunnus2}', 1, if(left(asiakas.toim_nimitark, length('{$ytunnus2}')) = '{$ytunnus2}', 2, 3)) apujarjestys,
                '4' apujarjestys2,
                '' yhteyshenkilo
                FROM asiakas
                JOIN yhtio on asiakas.yhtio=yhtio.yhtio
                WHERE asiakas.$konsyhtiot
                and asiakas.toim_nimitark like '%$ytunnus2%'
                and asiakas.toim_nimitark!=''
                $toimipaikkalisa
                $osoite_toim_lisa
                $postitp_toim_lisa
                $postino_toim_lisa
                $lajilisa $asiakasrajaus)
                UNION
                (SELECT asiakas.*, yhtio.nimi yhtionimi,
                if (asiakas.asiakasnro = '{$ytunnus2}', 1, if(left(asiakas.asiakasnro, length('{$ytunnus2}')) = '{$ytunnus2}', 2, 3)) apujarjestys,
                '5' apujarjestys2,
                '' yhteyshenkilo
                FROM asiakas
                JOIN yhtio on asiakas.yhtio=yhtio.yhtio
                WHERE asiakas.$konsyhtiot
                and asiakas.asiakasnro    like '%$ytunnus2%'
                and asiakas.asiakasnro    not in ('0','')
                $toimipaikkalisa
                $osoitelisa
                $postitplisa
                $postinolisa
                $lajilisa $asiakasrajaus)
                UNION
                (SELECT asiakas.*, yhtio.nimi yhtionimi,
                if (asiakas.ovttunnus = '{$ytunnus2}', 1, if(left(asiakas.ovttunnus, length('{$ytunnus2}')) = '{$ytunnus2}', 2, 3)) apujarjestys,
                '6' apujarjestys2,
                '' yhteyshenkilo
                FROM asiakas
                JOIN yhtio on asiakas.yhtio=yhtio.yhtio
                WHERE asiakas.$konsyhtiot
                and asiakas.ovttunnus     like '%$ytunnus2%'
                and asiakas.ovttunnus!=''
                $toimipaikkalisa
                $osoitelisa
                $postitplisa
                $postinolisa
                $lajilisa $asiakasrajaus)";

    if (trim($postitplisa) == '') {
      $query .= "  UNION
            (SELECT asiakas.*, yhtio.nimi yhtionimi,
              if (asiakas.postitp = '{$ytunnus2}', 1, if(left(asiakas.postitp, length('{$ytunnus2}')) = '{$ytunnus2}', 2, 3)) apujarjestys,
              '7' apujarjestys2,
              '' yhteyshenkilo
              FROM asiakas
              JOIN yhtio on asiakas.yhtio=yhtio.yhtio
              WHERE asiakas.$konsyhtiot
              and asiakas.postitp like '%$postitp2%'
              and asiakas.postitp!=''
              $toimipaikkalisa
              $osoitelisa
              $postinolisa
              $lajilisa $asiakasrajaus)";
    }

    $query .= "ORDER BY apujarjestys2, apujarjestys, nimi";
    $result = pupe_query($query);
    $monta  = mysql_num_rows($result);
  }
}
elseif ($asiakasnro != '') {
  $query = "SELECT asiakas.*, yhtio.nimi yhtionimi
            FROM asiakas
            JOIN yhtio on asiakas.yhtio = yhtio.yhtio
            WHERE asiakas.$konsyhtiot
             AND asiakas.asiakasnro = '$asiakasnro'
            $asiakasrajaus";
  $result = pupe_query($query);
  $monta  = mysql_num_rows($result);
}

// Putsattu resultti
$_asiakas_tunnukset = array();

if ($monta == 0) {
  if ($ytunnus and strpos($_SERVER["PHP_SELF"], "lue_data.php") === FALSE) {
    echo "<font class='error'>".t("Asiakas")." ". tarkistahetu($ytunnus)." ".t("ei löydy")."!</font> ";
  }
  elseif ($asiakasnro and strpos($_SERVER["PHP_SELF"], "lue_data.php") === FALSE) {
    echo "<font class='error'>".t("Asiakas")." '$asiakasnro' ".t("ei löydy")."!</font> ";
  }

  if (strpos($_SERVER["PHP_SELF"], "lue_data.php") === FALSE) {
    echo "<br><br>";
  }

  $ytunnus = "";
  $asiakasnro = "";
}
else {
  // talteen tunnukset, näytetään asiakas vain kerran
  // UNIONista saataa tulla sama asiakas monta kertaa resultiin...

  // Osuiko yhteyshenkilön nimellä?
  $yh_match = FALSE;

  while ($asiakas_loop = mysql_fetch_array($result)) {
    if (!isset($_asiakas_tunnukset[$asiakas_loop['tunnus']])) {

      if (!empty($asiakas_loop['yhteyshenkilo'])) {
        $yh_match = TRUE;
      }

      $_asiakas_tunnukset[$asiakas_loop['tunnus']] = $asiakas_loop;
    }
  }

  $monta = count($_asiakas_tunnukset);
}

if ($monta == 1 and !$force_selaus) {
  $asiakasrow = array_shift($_asiakas_tunnukset);
  $ytunnus = $asiakasrow['ytunnus'];

  if ($asiakasnro != "") {
    $asiakasnro  = $asiakasrow['asiakasnro'];
  }

  $asiakasid = $asiakasrow['tunnus'];

  if ($kutsuja == 'otsik.inc' and $asiakasrow['osuma'] == '5') {
    $query = "SELECT avainsana
              FROM asiakkaan_avainsanat
              WHERE yhtio      = '$kukarow[yhtio]'
              AND laji         = 'kantaasiakastunnus'
              AND liitostunnus = '$asiakasid'";
    $kantaasiakastunnus_result = pupe_query($query);
    $kantaasiakastunnus_row = mysql_fetch_assoc($kantaasiakastunnus_result);

    $kantaasiakastunnus = $kantaasiakastunnus_row['avainsana'];
  }
}
elseif ($monta > 1 and $monta <= $limit) {

  if ($kutsuja != "yllapito.php") {
    $piiri_result = t_avainsana('PIIRI');
    $on_piireja   = mysql_num_rows($piiri_result) !== 0;

    if ($on_piireja) {
      $piirit = array();

      while ($piiri = mysql_fetch_assoc($piiri_result)) {
        $piirit[$piiri["selite"]] = $piiri["selitetark"];
      }
    }

    //tämä vain otsik.php:tä varten
    echo $lause;

    echo "<table>";
    echo "<tr class='aktiivi'>";

    if ($konserni != '') {
      echo "<th>".t("yhtiö")."</th>";
    }

    echo "<th>", t("ytunnus"), "</th>";
    echo "<th>", t("asnro"), "</th>";
    echo "<th>", t("lasknimi/toimnimi"), "</th>";
    echo "<th colspan='3'>", t("laskosoite/toimosoite"), "</th>";

    if ($on_piireja) {
      echo "<th>" . t("piiri") . "</th>";
    }

    if ($yh_match) {
      echo "<th>" . t("yhteyshenkilö") . "</th>";
    }

    if ($yhtiorow['toimipaikkakasittely'] == 'L') {

      echo "<th>";

      echo "<form method='post'>";
      echo "<input type='hidden' name='ale1' value='$ale1'>";
      echo "<input type='hidden' name='ale2' value='$ale2'>";
      echo "<input type='hidden' name='ale3' value='$ale3'>";
      echo "<input type='hidden' name='from' value='$from'>";
      echo "<input type='hidden' name='hinta' value='$hinta'>";
      echo "<input type='hidden' name='kpl' value='$kpl'>";
      echo "<input type='hidden' name='lopetus' value='$lopetus'>";
      echo "<input type='hidden' name='mista' value='$mista'>";
      echo "<input type='hidden' name='myyjanumero' value='$myyjanumero'>";
      echo "<input type='hidden' name='netto' value='$netto'>";
      echo "<input type='hidden' name='nimi' value='$nimi'>";
      echo "<input type='hidden' name='nimitark' value='$nimitark'>";
      echo "<input type='hidden' name='osoite' value='$osoite'>";
      echo "<input type='hidden' name='perheid' value='$perheid'>";
      echo "<input type='hidden' name='perheid2' value='$perheid2'>";
      echo "<input type='hidden' name='postino' value='$postino'>";
      echo "<input type='hidden' name='postitp' value='$postitp'>";
      echo "<input type='hidden' name='projektilla' value='$projektilla'>";
      echo "<input type='hidden' name='ruutulimit' value='$ruutulimit'>";
      echo "<input type='hidden' name='syotetty_ytunnus' value='$syotetty_ytunnus'>";
      echo "<input type='hidden' name='takaisin' value='$takaisin'>";
      echo "<input type='hidden' name='tee' value='$tee'>";
      echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
      echo "<input type='hidden' name='tilausrivi_alvillisuus' value='$tilausrivi_alvillisuus'>";
      echo "<input type='hidden' name='tilaustyyppi' value='$tilaustyyppi'>";
      echo "<input type='hidden' name='toim' value='$toim'>";
      echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";
      echo "<input type='hidden' name='var' value='$var'>";
      echo "<input type='hidden' name='muutparametrit' value='$muutparametrit'>";
      echo "<input type='hidden' name='vain_toimittamattomat' value='{$vain_toimittamattomat}' />";

      $query = "SELECT *
                FROM yhtion_toimipaikat
                WHERE yhtio    = '{$kukarow['yhtio']}'
                AND vat_numero = ''
                ORDER BY nimi";
      $vresult = pupe_query($query);

      if (mysql_num_rows($vresult) > 0) {

        echo "<input type='hidden' name='ytunnus' value='{$ytunnus_talteen}'>";
        echo "<select name='toimipaikka_rajaus' onchange='this.form.submit()'>";
        echo "<option value='X' "; if ($toimipaikka_rajaus == 'X') {echo 'selected';} echo ">" . t("Kaikki") . "</option>";
        echo "<option value='0' "; if (empty($toimipaikka_rajaus)) {echo 'selected';} echo ">" . t("Ei toimipaikkaa") . "</option>";

        while ($vrow = mysql_fetch_array($vresult)) {
          $sel="";
          if ($tp == $vrow['tunnus']) {
            $sel = "selected";
          }
          echo "<option value='{$vrow['tunnus']}' {$sel}>{$vrow['ovtlisa']} {$vrow['nimi']}</option>";
        }
        echo "</select>";
      }

      echo "</form>";
      echo "</th>";
    }

    echo "<td class='back'></td></tr>";
  }

  foreach ($_asiakas_tunnukset as $asiakas) {
    if     ($asiakas['ytunnus']    != '') $numero = trim($asiakas['ytunnus']);
    elseif ($asiakas['asiakasnro'] != '') $numero = trim($asiakas['asiakasnro']);

    $brnim     = '';
    $bros      = '';
    $brpostino = '';
    $brpostitp = '';

    $asiakas['toim_nimi']    = trim($asiakas['toim_nimi']);
    $asiakas['toim_osoite']  = trim($asiakas['toim_osoite']);
    $asiakas['toim_postino'] = trim($asiakas['toim_postino']);
    $asiakas['toim_postitp'] = trim($asiakas['toim_postitp']);

    $asiakas['nimi']         = trim($asiakas['nimi']);
    $asiakas['osoite']       = trim($asiakas['osoite']);
    $asiakas['postino']      = trim($asiakas['postino']);
    $asiakas['postitp']      = trim($asiakas['postitp']);

    if ($kutsuja != "yllapito.php") {
      // jos mikätahansa on eri ja mikätahansa on erisuurta ku tyhjää.. tää on varmaan huomenna daily WTF:ssä.. :)
      if (($asiakas['toim_nimi'] != $asiakas["nimi"] or $asiakas['toim_osoite'] != $asiakas["osoite"] or $asiakas['toim_postino'] != $asiakas["postino"] or $asiakas['toim_postitp'] != $asiakas["postitp"] or $asiakas["toim_nimitark"] != $asiakas["nimitark"]) and
        ($asiakas['toim_nimi'] != "" or $asiakas['toim_osoite'] != "" or $asiakas['toim_postino'] != "" or $asiakas['toim_postitp'] != "" or $asiakas["toim_nimitark"] != "")) {
        $brnim     = '<br>'.$asiakas['toim_nimi']." ".$asiakas['toim_nimitark'];
        $bros      = '<br>'.$asiakas['toim_osoite'];
        $brpostino = '<br>'.$asiakas['toim_postino'];
        $brpostitp = '<br>'.$asiakas['toim_postitp'];
      }

      echo "<tr class='aktiivi'>";

      if ($konserni != '') {
        echo "<td>$asiakas[yhtionimi]</td>";
      }

      if ($asiakas["laji"] == "K" and $yhtiorow["yhtio"] == "artr") {
        $brnim .= "<br><font class='error'>".t("HUOM: Tämä on korjaamo-asiakas")."</font>";
      }

      echo "<td>", tarkistahetu($asiakas['ytunnus']), "</td>";
      echo "<td>{$asiakas['asiakasnro']}</td>";
      echo "<td>{$asiakas['nimi']} {$asiakas['nimitark']} {$brnim}</td>";
      echo "<td>{$asiakas['osoite']} {$bros}</td>";
      echo "<td>{$asiakas['postino']} {$brpostino}</td>";
      echo "<td>{$asiakas['postitp']} {$brpostitp}</td>";

      if ($yhtiorow['toimipaikkakasittely'] == 'L') {

        echo "<td>";

        if ($asiakas['toimipaikka'] != 0) {

          $toimipaikkares = hae_yhtion_toimipaikat($kukarow['yhtio'], $asiakas['toimipaikka']);

          if (mysql_num_rows($toimipaikkares) > 0) {

            $toimipaikkarow = mysql_fetch_assoc($toimipaikkares);
            echo $toimipaikkarow['nimi'];
          }
        }
        else {
          echo t("Ei toimipaikkaa.");
        }


        echo "</td>";
      }

      if ($yh_match) {
        echo "<td>{$asiakas['yhteyshenkilo']}</td>";
      }

      if ($on_piireja) {
        if ($asiakas['piiri'] != 0) {
          echo "<td>{$asiakas['piiri']} - {$piirit[$asiakas['piiri']]}</td>";
        }
        else {
          echo "<td></td>";
        }
      }

      $hintojen_vaihto = isset($hintojen_vaihto) ? $hintojen_vaihto : 'JOO';

      echo "<td class='back'>";
      echo "<form method='post'>";
      echo "<input type='hidden' name='asiakasid'     value='$asiakas[tunnus]'>";
      echo "<input type='hidden' name='tee'         value='$tee'>";
      echo "<input type='hidden' name='konserni'      value='$konserni'>";
      echo "<input type='hidden' name='toim'         value='$toim'>";
      echo "<input type='hidden' name='tilausnumero'     value='$tilausnumero'>";
      echo "<input type='hidden' name='from'           value='$from'>";
      echo "<input type='hidden' name='tila'         value='$tila'>";
      echo "<input type='hidden' name='muutparametrit'  value='$muutparametrit'>";
      echo "<input type='hidden' name='tilaustyyppi'    value='{$tilaustyyppi}'>";
      echo "<input type='hidden' name='ytunnus'        value='$numero'>";
      echo "<input type='hidden' name='asiakasnro'      value='$asiakasnro'>";
      echo "<input type='hidden' name='toimittajaid'    value='$toimittajaid'>";
      echo "<input type='hidden' name='asiakasyhtio'    value='$asiakas[yhtio]'>";
      echo "<input type='hidden' name='alatila'      value='$alatila'>";
      echo "<input type='hidden' name='ylatila'        value='$ylatila'>";
      echo "<input type='hidden' name='lopetus'       value='$lopetus'>";
      echo "<input type='hidden' name='myyja'       value='$myyja'>";
      echo "<input type='hidden' name='myyjanro'       value='$myyjanro'>";
      echo "<input type='hidden' name='tuoteno'       value='$tuoteno'>";
      echo "<input type='hidden' name='lasku_yhtio'    value='$asiakas[yhtio]'>";
      echo "<input type='hidden' name='tarra_aineisto'  value='$tarra_aineisto'>";
      echo "<input type='hidden' name='hintojen_vaihto' value='$hintojen_vaihto'>";
      echo "<input type='hidden' name='vain_toimittamattomat' value='{$vain_toimittamattomat}' />";

      if (isset($yhtiotoimipaikka)) echo "<input type='hidden' name='yhtiotoimipaikka'  value='{$yhtiotoimipaikka}' />";
      if (isset($tilaustyyppi)) echo "<input type='hidden' name='tilaustyyppi'  value='{$tilaustyyppi}' />";

      if (is_array($varastosta)) {
        foreach ($varastosta as $varatunnu) {
          echo "<input type='hidden' name='varastosta[$varatunnu]' value='$varatunnu'>";
        }
      }

      if (is_array($oletus)) {
        foreach ($oletus as $o => $a) {
          echo "<input type='hidden' name='oletus[$o]' value='$a'>";
        }
      }

      echo "<input type='submit' name='valitse_asiakas' value='".t("Valitse")."'>";
      echo "</form>";
      echo "</td>";
      echo "</tr>";
    }
    else {
      // jos mikätahansa on eri ja mikätahansa on erisuurta ku tyhjää.. tää on varmaan huomenna daily WTF:ssä.. :)
      if (($asiakas['toim_nimi'] != $asiakas["nimi"] or $asiakas['toim_osoite'] != $asiakas["osoite"] or $asiakas['toim_postino'] != $asiakas["postino"] or $asiakas['toim_postitp'] != $asiakas["postitp"]) and
        ($asiakas['toim_nimi'] != "" or $asiakas['toim_osoite'] != "" or $asiakas['toim_postino'] != "" or $asiakas['toim_postitp'] != "")) {
        $brnim     = ', '.$asiakas['toim_nimi']." ".$asiakas['toim_nimitark'];
        $bros      = ' '.$asiakas['toim_osoite'];
        $brpostino = ' '.$asiakas['toim_postino'];
        $brpostitp = ' '.$asiakas['toim_postitp'];
      }

      $ulos  .= "<option value='$asiakas[ytunnus]'>$asiakas[ytunnus] $asiakas[nimi] $asiakas[osoite] $asiakas[postino] $asiakas[postitp] $brnim $bros $brpostitp $brpostitp</option>";
      $ulos2 .= "<option value='$asiakas[tunnus]'>$asiakas[ytunnus] $asiakas[nimi] $asiakas[nimitark] $asiakas[osoite] $asiakas[postino] $asiakas[postitp] $brnim $bros $brpostitp $brpostitp</option>";
    }
  }

  if ($kutsuja != "yllapito.php") {
    echo "</table><br>";
  }

  $ytunnus = "";
}

if ($monta > $limit) {
  echo "<font class='error'>".t("Asiakashaulla löytyi")." $monta ".t("sopivaa asiakasta. Tarkenna hakuasi")."!</font>";
  $ytunnus = "";
}

if ($monta != 1) {

  if ($kutsuja != "yllapito.php") {

    if (($ytunnus or $asiakasnro) and strpos($PHP_SELF, "tilaus_myynti") === FALSE) {
      echo "<form method='post'>";
      echo "<input type='hidden' name='myos_poistetut'   value='TRUE'>";
      echo "<input type='hidden' name='tee'          value='$tee'>";
      echo "<input type='hidden' name='konserni'       value='$konserni'>";
      echo "<input type='hidden' name='toim'          value='$toim'>";
      echo "<input type='hidden' name='tilausnumero'      value='$tilausnumero'>";
      echo "<input type='hidden' name='from'            value='$from'>";
      echo "<input type='hidden' name='tila'          value='$tila'>";
      echo "<input type='hidden' name='muutparametrit'   value='$muutparametrit'>";
      echo "<input type='hidden' name='ytunnus'         value='$ytunnus'>";
      echo "<input type='hidden' name='asiakasnro'       value='$asiakasnro'>";
      echo "<input type='hidden' name='toimittajaid'     value='$toimittajaid'>";
      echo "<input type='hidden' name='asiakasyhtio'     value='$asiakas[yhtio]'>";
      echo "<input type='hidden' name='alatila'       value='$alatila'>";
      echo "<input type='hidden' name='ylatila'         value='$ylatila'>";
      echo "<input type='hidden' name='lopetus'        value='$lopetus'>";
      echo "<input type='hidden' name='myyja'        value='$myyja'>";
      echo "<input type='hidden' name='myyjanro'        value='$myyjanro'>";
      echo "<input type='hidden' name='lasku_yhtio'     value='$asiakas[yhtio]'>";
      echo "<input type='hidden' name='tuoteno'        value='$tuoteno'>";
      echo "<input type='hidden' name='tarra_aineisto'   value='$tarra_aineisto'>";
      echo "<input type='hidden' name='yhtiotoimipaikka' value='{$yhtiotoimipaikka}' />";
      echo "<input type='hidden' name='tilaustyyppi'     value='{$tilaustyyppi}' />";
      echo "<input type='hidden' name='vain_toimittamattomat' value='{$vain_toimittamattomat}' />";

      if (is_array($varastosta)) {
        foreach ($varastosta as $varatunnu) {
          echo "<input type='hidden' name='varastosta[$varatunnu]' value='$varatunnu'>";
        }
      }

      echo "<input type='submit' value='".t("Etsi myös poistettuja asiakkaita")."'>";
      echo "</form><br>";
    }
  }

  if ($kutsuja == "otsik.inc" and $toim == "TARJOUS") {
    echo "<form method='post'>";
    echo "<input type='hidden' name='myos_prospektit' value='TRUE'>";
    echo "<input type='hidden' name='toim' value='$toim'>";
    echo "<input type='hidden' name='indexvas' value='$indexvas'>";
    echo "<input type='hidden' name='tee' value='$tee'>";
    echo "<input type='hidden' name='lopetus' value='$lopetus'>";
    echo "<input type='hidden' name='ruutulimit' value='$ruutulimit'>";
    echo "<input type='hidden' name='projektilla' value='$projektilla'>";
    echo "<input type='hidden' name='from' value='$from'>";
    echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
    echo "<input type='hidden' name='mista' value='$mista'>";
    echo "<input type='hidden' name='nimi' value='$nimi'>";
    echo "<input type='hidden' name='nimitark' value='$nimitark'>";
    echo "<input type='hidden' name='osoite' value='$osoite'>";
    echo "<input type='hidden' name='postino' value='$postino'>";
    echo "<input type='hidden' name='postitp' value='$postitp'>";
    echo "<input type='hidden' name='toimipaikka_rajaus' value='$toimipaikka_rajaus'>";
    echo "<input type='submit' value='".t("Etsi myös prospekti asiakkaita")."'>";
    echo "</form>";
  }

  if ($yhtiorow['toimipaikkakasittely'] == 'L' and $toimipaikka_rajaus != 'X') {

    if ($toim == "PIKATILAUS") {
      $nimi = $syotetty_ytunnus;
    }

    echo "<form method='post'>";
    echo "<input type='hidden' name='ytunnus' value='{$ytunnus_talteen}'>";
    echo "<input type='hidden' name='toimipaikka_rajaus' value='X'>";
    echo "<input type='hidden' name='ale1' value='$ale1'>";
    echo "<input type='hidden' name='ale2' value='$ale2'>";
    echo "<input type='hidden' name='ale3' value='$ale3'>";
    echo "<input type='hidden' name='from' value='$from'>";
    echo "<input type='hidden' name='hinta' value='$hinta'>";
    echo "<input type='hidden' name='kpl' value='$kpl'>";
    echo "<input type='hidden' name='lopetus' value='$lopetus'>";
    echo "<input type='hidden' name='mista' value='$mista'>";
    echo "<input type='hidden' name='myyjanumero' value='$myyjanumero'>";
    echo "<input type='hidden' name='netto' value='$netto'>";
    echo "<input type='hidden' name='nimi' value='$nimi'>";
    echo "<input type='hidden' name='nimitark' value='$nimitark'>";
    echo "<input type='hidden' name='osoite' value='$osoite'>";
    echo "<input type='hidden' name='perheid' value='$perheid'>";
    echo "<input type='hidden' name='perheid2' value='$perheid2'>";
    echo "<input type='hidden' name='postino' value='$postino'>";
    echo "<input type='hidden' name='postitp' value='$postitp'>";
    echo "<input type='hidden' name='projektilla' value='$projektilla'>";
    echo "<input type='hidden' name='ruutulimit' value='$ruutulimit'>";
    echo "<input type='hidden' name='syotetty_ytunnus' value='$syotetty_ytunnus'>";
    echo "<input type='hidden' name='takaisin' value='$takaisin'>";
    echo "<input type='hidden' name='tee' value='$tee'>";
    echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
    echo "<input type='hidden' name='tilausrivi_alvillisuus' value='$tilausrivi_alvillisuus'>";
    echo "<input type='hidden' name='tilaustyyppi' value='$tilaustyyppi'>";
    echo "<input type='hidden' name='toim' value='$toim'>";
    echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";
    echo "<input type='hidden' name='var' value='$var'>";
    echo "<input type='hidden' name='muutparametrit' value='{$muutparametrit}'>";
    echo "<input type='hidden' name='vain_toimittamattomat' value='{$vain_toimittamattomat}' />";
    echo "<input type='submit' value='".t("Hae kaikista toimipaikoista")."'>";
    echo "</form>";
  }

  if (($ytunnus or $asiakasnro) and strpos($PHP_SELF, "tilaus_myynti") === FALSE and $kutsuja != "yllapito.php") {
    echo "<form method='post'>";
    echo "<input type='hidden' name='myos_poistetut'  value='TRUE'>";
    echo "<input type='hidden' name='tee'         value='$tee'>";
    echo "<input type='hidden' name='konserni'      value='$konserni'>";
    echo "<input type='hidden' name='toim'         value='$toim'>";
    echo "<input type='hidden' name='tilausnumero'     value='$tilausnumero'>";
    echo "<input type='hidden' name='from'             value='$from'>";
    echo "<input type='hidden' name='tila'             value='$tila'>";
    echo "<input type='hidden' name='muutparametrit'   value='$muutparametrit'>";
    echo "<input type='hidden' name='ytunnus'          value='$ytunnus'>";
    echo "<input type='hidden' name='asiakasnro'       value='$asiakasnro'>";
    echo "<input type='hidden' name='toimittajaid'     value='$toimittajaid'>";
    echo "<input type='hidden' name='asiakasyhtio'     value='$asiakas[yhtio]'>";
    echo "<input type='hidden' name='alatila'          value='$alatila'>";
    echo "<input type='hidden' name='ylatila'          value='$ylatila'>";
    echo "<input type='hidden' name='lopetus'          value='$lopetus'>";
    echo "<input type='hidden' name='myyja'            value='$myyja'>";
    echo "<input type='hidden' name='myyjanro'         value='$myyjanro'>";
    echo "<input type='hidden' name='tuoteno'          value='$tuoteno'>";
    echo "<input type='hidden' name='tarra_aineisto'   value='$tarra_aineisto'>";
    echo "<input type='hidden' name='vain_toimittamattomat' value='{$vain_toimittamattomat}' />";

    if (is_array($varastosta)) {
      foreach ($varastosta as $varatunnu) {
        echo "<input type='hidden' name='varastosta[$varatunnu]' value='$varatunnu'>";
      }
    }

    echo "<input type='submit' value='".t("Etsi myös poistettuja asiakkaita")."'>";
    echo "</form>";
  }

  echo "<form method='post'>";
  echo "<input type='hidden' name='toim' value='$toim'>";
  echo "<input type='submit' value='".t("Tee uusi haku")."'>";
  echo "</form>";

  if ($kutsuja == "otsik.inc" or $kutsuja == "asiakasemo.php" or $kutsuja == "kalenteri.php") {

    $asylloik1 = tarkista_oikeus("yllapito.php", "asiakas!!!VAHITTAISMYYNTI!!!true&laji=H", "X", TRUE);
    $asylloik2 = tarkista_oikeus("yllapito.php", "asiakas%", "X", TRUE);

    $uusiasiakas = "uusiasiakas";

    $ahlopetus .= "//uusiasiakas=$uusiasiakas";

    if ($monta != 1 and ($asylloik1 or $asylloik2)) {

      //Annetaan mahdollisuus perustaa uusi asiakas
      if ($asylloik1) {
        echo "<form action='{$palvelin2}yllapito.php' method='post'>";
        echo "<input type='hidden' name='lopetus'   value='$ahlopetus'>";
        echo "<input type='hidden' name='toim'    value='{$asylloik2["alanimi"]}'>";
        echo "<input type='hidden' name='laji'    value='H'>";
        echo "<input type='hidden' name='uusi'    value='1'>";
        echo "<input type='submit' value='".t("Uusi henkilöasiakas")."'>";
        echo "</form>";
      }

      if ($asylloik2) {
        echo "<form action='{$palvelin2}yllapito.php' method='post'>";
        echo "<input type='hidden' name='lopetus'   value='$ahlopetus'>";
        echo "<input type='hidden' name='toim'    value='{$asylloik2["alanimi"]}'>";
        echo "<input type='hidden' name='laji'    value='Y'>";
        echo "<input type='hidden' name='uusi'    value='1'>";
        echo "<input type='submit' value='".t("Uusi yritysasiakas")."'>";
        echo "</form>";
      }
    }
  }

  echo "<br><br>";
}
