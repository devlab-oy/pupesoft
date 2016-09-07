<?php

pupeslave_start();

$enarimatch   = FALSE;
$poistetulisa = " and (tuote.status not in ('P','X') or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0) ";

if (
  // Haetaan myös poistetut, jos kyseessä on tuotehallinta, eikä olla extranetissä
  (strpos($_SERVER['SCRIPT_NAME'], "tuote.php") !== false and $kukarow["extranet"] == "")

  // Haetaan myös poistetut, jos kyseessä on ostotilaus ja yhtiön parametreistä on valittu, että
  // myös poistettuja tuotteita voi lisätä tilaukselle
  or (strpos($_SERVER['SCRIPT_NAME'], "tilaus_osto.php") !== false and $yhtiorow["poistetut_lisays"] == "P")

  // Haetaan myös poistetut, jos kyseessä on extranet-tilaus ja yhtiön parametreistä on valittu,
  // että myös poistettuja tuotteita voi lisätä tilaukselle
  or (strpos($_SERVER['SCRIPT_NAME'], "tilaus_myynti.php") !== false and !empty($extranet) and $yhtiorow["poistetut_lisays"] == "P")
) {
  $poistetulisa = "";
}

// Etsitän enarilla jos kappaleita ei ole syötetty eikä hakuja käytetty
if ((!isset($kpl) or $kpl == "") and !is_array($kpl_array) and substr($tuoteno, 0, 1) != '*' and substr($tuoteno, -1) != '*' and substr($tuoteno, 0, 1) != '?' and substr($tuoteno, 0, 1) != '#' and substr($tuoteno, 0, 1) != '@') {

  $query = "SELECT *
            FROM tuote
            WHERE tuote.yhtio  = '$kukarow[yhtio]'
            and tuote.eankoodi = '$tuoteno'
            and (tuote.status not in ('P','X') or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0)";
  $tresult = pupe_query($query);

  if (mysql_num_rows($tresult) == 1) {
    //Tehdään tuotenumero ja kpl
    $trow = mysql_fetch_assoc($tresult);
    $tuoteno = $trow['tuoteno'];
    $kpl = 1;
    $enarimatch = TRUE;
  }
}

if (!$enarimatch) {
  if (substr($tuoteno, 0, 1) != '*' and substr($tuoteno, -1) != '*' and substr($tuoteno, 0, 1) != '?' and substr($tuoteno, 0, 1) != '#' and substr($tuoteno, 0, 1) != '@' and $yhtiorow["automaattinen_tuotehaku"] != '') {

    $query = "SELECT *
              FROM tuote
              WHERE tuote.yhtio = '$kukarow[yhtio]'
              and tuote.tuoteno = '$tuoteno'
              {$poistetulisa}";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 0) {
      $query = "SELECT *
                FROM tuote
                WHERE tuote.yhtio = '$kukarow[yhtio]'
                and tuote.tuoteno LIKE '$tuoteno%'
                and (tuote.status not in ('P','X') or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0)";
      $result = pupe_query($query);

      if (mysql_num_rows($result) != 1) {
        $tuoteno = $tuoteno . "*";
      }
      else {
        $tuotenorow = mysql_fetch_assoc($result);
        $tuoteno = $tuotenorow["tuoteno"];
      }
    }
  }

  // Nyt me selataan
  if ((substr($tuoteno, 0, 1) == '*' or substr($tuoteno, -1) == '*' or substr($tuoteno, 0, 1) == '?' or substr($tuoteno, 0, 1) == "@") and trim(strlen($tuoteno)) >= 2) {

    $th_lisa1   = "";
    $ulos       = "";
    $kieltolisa = "";

    if (substr($tuoteno, 0, 1) == '?') {
      // Toimittajan tuotenumero

      $tuotteen_toimittajat_liitostunnus = "";

      if (isset($laskurow) and $laskurow['tila'] == 'O') {
        $tuotteen_toimittajat_liitostunnus = "  and liitostunnus = '{$laskurow['liitostunnus']}'";
      }

      $query = "SELECT *
                from tuotteen_toimittajat
                where yhtio      = '$kukarow[yhtio]'
                and toim_tuoteno like '%".substr($tuoteno, 1)."%'
                {$tuotteen_toimittajat_liitostunnus}";
      $tresult = pupe_query($query);

      while ($toimirivi = mysql_fetch_assoc($tresult)) {
        $th_lisa1 .= "'$toimirivi[tuoteno]',";
      }

      if ($th_lisa1 != "") {
        $th_lisa1 = "and tuote.tuoteno in (".substr($th_lisa1, 0, -1).")"; // vika pilkku pois
      }
    }
    elseif (substr($tuoteno, -2) == '**') {
      // Tuoteryhmä
      $th_lisa1 = " and tuote.try = '$tuoteno' ";
    }
    elseif (substr($tuoteno, 0, 1) == '*') {
      // Jos eka merkki on tähti etsitään teksteistä
      $tuoteno = substr($tuoteno, 1);
      $sanat = explode(" ", $tuoteno);

      foreach ($sanat as $sana) {
        $th_lisa1 .= " and concat_ws(' ', tuote.nimitys, tuote.lyhytkuvaus, tuote.tuotemerkki) like '%" . trim($sana) . "%' ";
      }
    }
    elseif (substr($tuoteno, -1) == '*') {
      // Jos vika merkki on tähti niin etsitään osittainen tuotenumero
      $tuoteno = substr($tuoteno, 0, -1);

      $th_lisa1 = " and tuote.tuoteno like '" . $tuoteno . "%' ";

      //Kun kyseessä on ostotilaus ja haetaan tuotelistausta esim. "+1*" haetaan vain ostotilauksen toimittajan tuotteita.
      $tuotteen_toimittajat_join = "";
      if (isset($laskurow) and $laskurow['tila'] == 'O') {
        $tuotteen_toimittajat_join = "  JOIN tuotteen_toimittajat
                        ON ( tuotteen_toimittajat.yhtio = tuote.yhtio
                          AND tuotteen_toimittajat.tuoteno = tuote.tuoteno
                          AND tuotteen_toimittajat.liitostunnus = '{$laskurow['liitostunnus']}' )";
      }
    }
    elseif (substr($tuoteno, 0, 1) == '@') {
      // Jos ensimmäinen merkki on AT-merkki niin etsitään kyseisellä tuotenumero
      $tuoteno = substr($tuoteno, 1);
      $th_lisa1 = " and tuote.tuoteno = '".$tuoteno."' ";
    }

    $yhtiot   = "";
    $konsyhtiot = array();

    //Voidaan hakea myös koko konsernin tuotteista
    if ($konsernihaku != "KYLLA") {
      $yhtiot     = "yhtio = '$kukarow[yhtio]' ";
      $konsyhtiot[] = $kukarow["yhtio"];
    }
    else {
      //Yhtiövalinnat
      $query  = "SELECT distinct yhtio, nimi
                 from yhtio
                 where konserni  = '$yhtiorow[konserni]'
                 and konserni   != ''";
      $presult = pupe_query($query);

      if (mysql_num_rows($presult) > 0) {
        while ($prow = mysql_fetch_assoc($presult)) {
          $yhtiot .= "'".$prow["yhtio"]."',";
          $konsyhtiot[] = $prow["yhtio"];
        }
        $yhtiot = substr($yhtiot, 0, -1);
        $yhtiot = "yhtio in ($yhtiot) ";

      }
      else {
        $yhtiot = "yhtio = '$kukarow[yhtio]' ";
        $konsyhtiot[] = $kukarow["yhtio"];
      }
    }

    if ($kutsuja == "tuoteperhe.php") {
      $perhelisa = ", if ((SELECT tuoteperhe.isatuoteno FROM tuoteperhe WHERE tuoteperhe.yhtio=tuote_wrapper.yhtio and tuoteperhe.isatuoteno=tuote_wrapper.tuoteno and tuoteperhe.tyyppi = '$hakutyyppi' LIMIT 1) IS NULL, 'pois', 'mukaan') rajaus";
      $perhelisa2 = "HAVING rajaus='mukaan'";
    }

    if ($kaikkituhaku != "KYLLA") {

      if ($kukarow["kesken"] > 0) {
        $query  = "SELECT if (toim_maa != '', toim_maa, maa) maa
                   FROM lasku
                   WHERE yhtio = '$kukarow[yhtio]'
                   and tunnus  = '$kukarow[kesken]'";
        $vieres = pupe_query($query);
        $vierow = mysql_fetch_assoc($vieres);
      }
      elseif ($verkkokauppa != "") {
        $vierow = array();

        if ($maa != "") {
          $vierow["maa"] = $maa;
        }
        else {
          $vierow["maa"] = $yhtiorow["maa"];
        }
      }
      elseif ($kukarow["extranet"] != "") {
        $query  = "SELECT if (toim_maa != '', toim_maa, maa) maa
                   FROM asiakas
                   WHERE yhtio = '$kukarow[yhtio]'
                   and tunnus  = '$kukarow[oletus_asiakas]'";
        $vieres = pupe_query($query);
        $vierow = mysql_fetch_assoc($vieres);
      }

      if (isset($vierow) and $vierow["maa"] != "") {
        $kieltolisa = " and (tuote.vienti = '' or tuote.vienti like '%-$vierow[maa]%' or tuote.vienti like '%+%') and tuote.vienti not like '%+$vierow[maa]%' ";
      }
    }

    if (strpos($_SERVER['SCRIPT_NAME'], "tuote.php") !== false and $kukarow["extranet"] == "" and !empty($poistuvat_tuotteet)) {
      $havinglisa = "HAVING tuote.status not in ('P','X') and saldo > 0 and ei_saldoa = ''";
    }
    else {
      $havinglisa = "HAVING (tuote.status not in ('P','X') or saldo > 0)";
    }

    // Poistettuja tuotteita ei näytetä paitsi jos niillä on saldoa
    // Joinataan korvaavat mukaan
    $query = "SELECT
              tuote.yhtio,
              ifnull((SELECT isatuoteno FROM tuoteperhe use index (yhtio_tyyppi_isatuoteno) where tuoteperhe.yhtio=tuote.yhtio and tuoteperhe.tyyppi='P' and tuoteperhe.isatuoteno=tuote.tuoteno LIMIT 1), '') tuoteperhe,
              ifnull((SELECT id FROM korvaavat use index (yhtio_tuoteno) where korvaavat.yhtio=tuote.yhtio and korvaavat.tuoteno=tuote.tuoteno LIMIT 1), tuote.tuoteno) korvaavat,
              tuote.tuoteno,
              tuote.nimitys,
               tuote.osasto,
                tuote.try,
               tuote.myyntihinta,
              tuote.nettohinta,
               tuote.aleryhma,
               tuote.status,
               tuote.ei_saldoa,
               tuote.yksikko,
               tuote.tunnus,
               (SELECT group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') FROM tuotteen_toimittajat use index (yhtio_tuoteno) WHERE tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno) toim_tuoteno,
              tuote.sarjanumeroseuranta,
                tuote.status,
               IF(tuote.epakurantti100pvm = '0000-00-00', IF(tuote.epakurantti75pvm = '0000-00-00', IF(tuote.epakurantti50pvm = '0000-00-00', IF(tuote.epakurantti25pvm = '0000-00-00', '', '25'), '50'), '75'), '100') epakurantti,
               (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) saldo
              FROM tuote
              {$tuotteen_toimittajat_join}
              WHERE tuote.$yhtiot
              $kieltolisa
              $th_lisa1
              {$havinglisa}
              ORDER BY tuote.tuoteno
              LIMIT 201";
    $thresult = pupe_query($query);

    $rows = array();

    if (mysql_num_rows($thresult) > 0) {

      // Rakennetaan array ja laitetaan korvaavat mukaan
      while ($mrow = mysql_fetch_assoc($thresult)) {
        if ($mrow["korvaavat"] != $mrow["tuoteno"]) {

          $query = "SELECT
                    tuote.yhtio,
                    ifnull((SELECT isatuoteno FROM tuoteperhe use index (yhtio_tyyppi_isatuoteno) where tuoteperhe.yhtio=tuote.yhtio and tuoteperhe.tyyppi='P' and tuoteperhe.isatuoteno=tuote.tuoteno LIMIT 1), '') tuoteperhe,
                    korvaavat.id korvaavat,
                    tuote.tuoteno,
                    tuote.nimitys,
                    tuote.osasto,
                    tuote.try,
                    tuote.myyntihinta,
                    tuote.nettohinta,
                    tuote.aleryhma,
                    tuote.status,
                    tuote.ei_saldoa,
                    tuote.yksikko,
                    tuote.tunnus,
                    (SELECT group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') FROM tuotteen_toimittajat use index (yhtio_tuoteno) WHERE tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno) toim_tuoteno,
                    tuote.sarjanumeroseuranta,
                    tuote.status,
                    IF(tuote.epakurantti100pvm = '0000-00-00', IF(tuote.epakurantti75pvm = '0000-00-00', IF(tuote.epakurantti50pvm = '0000-00-00', IF(tuote.epakurantti25pvm = '0000-00-00', '', '25'), '50'), '75'), '100') epakurantti,
                    (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) saldo
                    FROM korvaavat
                    JOIN tuote ON tuote.yhtio=korvaavat.yhtio and tuote.tuoteno=korvaavat.tuoteno
                    WHERE korvaavat.yhtio  = '$mrow[yhtio]'
                    and korvaavat.id       = '$mrow[korvaavat]'
                    and korvaavat.tuoteno != '$mrow[tuoteno]'
                    $kieltolisa
                    HAVING (tuote.status not in ('P','X') or saldo > 0)
                    ORDER BY korvaavat.jarjestys, korvaavat.tuoteno";
          $kores = pupe_query($query);

          if (mysql_num_rows($kores) > 0) {

            $krow = mysql_fetch_assoc($kores);
            $ekakorva = $krow["korvaavat"];

            mysql_data_seek($kores, 0);

            if (!isset($rows[$ekakorva.$mrow["tuoteno"]])) $rows[$ekakorva.$mrow["tuoteno"]] = $mrow;

            while ($krow = mysql_fetch_assoc($kores)) {

              $krow["mikakorva"] = $mrow["tuoteno"];

              if (!isset($rows[$ekakorva.$krow["tuoteno"]])) $rows[$ekakorva.$krow["tuoteno"]] = $krow;
            }
          }
          else {
            $rows[$mrow["tuoteno"]] = $mrow;
          }
        }
        else {
          $rows[$mrow["yhtio"].$mrow["tuoteno"]] = $mrow;
        }
      }
    }

    // Etsitään vielä asiakaskommenteista, jos ei ole mitenkään muuten löytynyt ja kyseessä on
    // myyntitilaus
    if (count($rows) == 0 and strpos($_SERVER['SCRIPT_NAME'], "tilaus_myynti.php") !== false) {
      $query = "SELECT tuote.*
                FROM tuote
                JOIN asiakaskommentti
                  ON (asiakaskommentti.yhtio = tuote.yhtio
                  AND asiakaskommentti.tuoteno = tuote.tuoteno
                  AND asiakaskommentti.ytunnus = '{$asiakasrow["ytunnus"]}')
                WHERE tuote.yhtio              = '{$kukarow["yhtio"]}'
                AND asiakaskommentti.kommentti = '{$tuoteno}'
                {$poistetulisa}
                LIMIT 1";

      $row = pupe_query($query);
      $row = mysql_fetch_assoc($row);

      if (!empty($row)) {
        $rows[$row["tuoteno"]] = $row;
      }
    }

    if (count($rows) >  200) {
      $kentta      = 'tuoteno';
      $varaosavirhe   = t("VIRHE: Hakuehdolla löytyi liikaa tuotteita!");
      $varaosavirhe   .= t("Näytetään 200 ensimmäistä").".";
      $tee       = 'Y';
    }

    if (count($rows) == 0) {
      $kentta      = 'tuoteno';
      $varaosavirhe   = t("VIRHE: Hakuehdolla ei löytynyt yhtään tuotetta")."!";
      $tuoteno    = '';
      $tee       = 'Y';
    }
    elseif (count($rows) == 1) {
      $trow = array_pop($rows);

      if ($konsernihaku == "KYLLA") {
        $tuoteno = $trow['tuoteno']."####$trow[yhtio]";
      }
      else {
        $tuoteno = $trow['tuoteno'];
      }
    }
    elseif (count($rows) != 1) {

      if ($multi == "" and $kutsuja == "tuoteperhe.php") {
        $ulos = "<select name='hakutuoteno2'>";
      }
      elseif ($multi == "" and $kutsuja != "yllapito.php") {
        $ulos = "<select name='tuoteno'>";
      }
      elseif ($kutsuja != "yllapito.php") {
        $ulos = "<select name='tuoteno_array[]' multiple='TRUE' size='6'>";
      }

      if (!empty($yhtiorow["saldo_kasittely"])) {
        $saldoaikalisa = date("Y-m-d");
      }
      else {
        $saldoaikalisa = "";
      }

      $ulos .= "<option value=''>".t("Valitse tuote")."</option>";

      // Loopataan tuotteet läpi
      foreach ($rows as $trow) {

        $lisakala1 = "";

        if ($trow["yhtio"].$trow["korvaavat"] == $edtuoteno) {
          $lisakala1 = "* ";
        }

        $kokonaismyytavissa = 0;

        if ($trow["tuoteperhe"] == $trow["tuoteno"]) {
          // Tuoteperheen isä
          $saldot = tuoteperhe_myytavissa($trow["tuoteno"], "", "", $laskurow["varasto"], "", "", "", "", "", $laskurow["toim_maa"], $saldoaikalisa);

          foreach ($saldot as $varasto => $myytavissa) {
            $kokonaismyytavissa += $myytavissa;
          }
        }
        elseif ($trow['ei_saldoa'] != '') {
          $kokonaismyytavissa = t("On");
        }
        else {
          foreach ($konsyhtiot as $yhtio) {
            list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($trow["tuoteno"], '', $laskurow["varasto"], $yhtio, '', '', '', '', '', $saldoaikalisa);
            $kokonaismyytavissa += $myytavissa;
          }
        }

        if ($kukarow['extranet'] != '' and $kokonaismyytavissa > 0) {
          $kokonaismyytavissa = t("On");
        }
        elseif ($kukarow['extranet'] != '') {
          $kokonaismyytavissa = t("Ei");
        }

        $lisakala2 = "";
        $lisakala3 = "";

        if ($konsernihaku == "KYLLA") {
          $lisakala2 = " $trow[yhtio] - ";
          $lisakala3 = "####$trow[yhtio]";
        }

        // katotaan onko tällä tuotteella joku variaatiorypäs
        $query = "SELECT *
                  FROM tuotteen_avainsanat
                  WHERE yhtio = '$trow[yhtio]'
                  AND laji    = 'parametri_variaatio'
                  AND kieli   = '$yhtiorow[kieli]'
                  AND tuoteno = '$trow[tuoteno]'
                  LIMIT 1";
        $parametrit_res = pupe_query($query);
        $parametritrivi = mysql_fetch_assoc($parametrit_res);

        if (trim($parametritrivi["selite"]) != "" and $parametritrivi["selite"] != $edvariaatio) {
          $ulos .= "<option value='$parametritrivi[selite]'> ** $parametritrivi[selite] ** </option>";
        }

        if (empty($kukarow["extranet"])) {
          $epakuranttiteksti = $trow['epakurantti'] != '' ? " ({$trow['epakurantti']}% " . t("epäkurantti") . ")" : "";
        }
        else {
          $epakuranttiteksti = "";
        }

        $ulos .= "<option value='{$trow['tuoteno']}{$lisakala3}'>{$lisakala2} {$lisakala1} {$trow['tuoteno']} ".t_tuotteen_avainsanat($trow, 'nimitys')." ({$kokonaismyytavissa}){$epakuranttiteksti}</option>";

        $edtuoteno = $trow["yhtio"].$trow["korvaavat"];
        $edvariaatio = $parametritrivi["selite"];
      }

      if ($kutsuja != "yllapito.php") {
        $ulos .= "</select>";
        $tee = 'Y';
      }
    }
  }
  elseif ((substr($tuoteno, 0, 1) == '*' or substr($tuoteno, -1) == '*' or substr($tuoteno, 0, 1) == '?' or substr($tuoteno, 0, 1) == '@') and trim(strlen($tuoteno)) < 2) {
    $kentta      = 'tuoteno';
    $varaosavirhe   = t("VIRHE: Hakusanan on sisällettävä vähintään kaksi merkkiä")."!<br>";
    $tuoteno    = '';
    $tee       = 'Y';
  }

  if ($ulos == '' and $tee != 'Y') {

    ///* Viivakoodinlukija-special *///
    ///* Konffaa lukija näin: prefiksiksi #-merkki ja suffiksiksi RETURN*///
    if (substr($tuoteno, 0, 1) == '#') {

      $th_lisa1 = "and tuote.eankoodi='".substr($tuoteno, 1)."'";

      $query = "SELECT tuote.tuoteno
                FROM tuote
                WHERE tuote.yhtio = '$kukarow[yhtio]'
                and (tuote.status not in ('P','X') or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0)
                $th_lisa1";
      $tresult = pupe_query($query);

      if (mysql_num_rows($tresult) > 1) {
        $kentta='tuoteno';
        $varaosavirhe = t("Useita tuotteita samalla koodilla")."!<br>";
        $tuoteno='';
        $tee = 'Y';
      }
      elseif (mysql_num_rows($tresult) == 0) {
        $kentta='tuoteno';
        $varaosavirhe = t("VIRHE: Hakuehdolla ei löytynyt yhtään tuotetta")."!<br>";
        $tuoteno='';
        $tee = 'Y';
      }
      elseif (mysql_num_rows($tresult) == 1) {
        //Tehdään tuotenumero ja kpl
        $trivi = mysql_fetch_assoc($tresult);
        $tuoteno = $trivi['tuoteno'];
      }
    }

    if (strpos($tuoteno, '####') !== FALSE and $konsernihaku == "KYLLA") {
      $hakyhtio  = substr($tuoteno, strpos($tuoteno, '####')+4);
      $tuoteno   = substr($tuoteno, 0, strpos($tuoteno, '####'));
    }
    else {
      $hakyhtio   = $kukarow["yhtio"];
    }

    $query = "SELECT *
              FROM tuote
              WHERE tuote.yhtio = '$hakyhtio'
              and tuote.tuoteno = '$tuoteno'
              {$poistetulisa}";
    $result = pupe_query($query);

    if (mysql_num_rows($result) != 1) {
      $kentta = 'tuoteno';

      if (strpos($_SERVER['SCRIPT_NAME'], "tilaus_myynti.php") === FALSE or $kukarow['extranet'] != '') {
        $varaosavirhe .= t("VIRHE: Tuotenumeroa")." $tuoteno ".t("ei löydy järjestelmästä")."!<br>";
      }

      $tee = "Y";
    }
    else {
      $trow = mysql_fetch_assoc($result);

      if ($konsernihaku == "KYLLA") {
        $tuoteno = $trow['tuoteno']."####".$trow["yhtio"];
      }
      else {
        $tuoteno = $trow['tuoteno'];
      }
    }
  }
}

pupeslave_stop();
