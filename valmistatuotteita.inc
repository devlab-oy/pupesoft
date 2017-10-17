<?php

if (file_exists("tulosta_tilaustuotetarrat.inc")) {
  require_once "tulosta_tilaustuotetarrat.inc";
}
else {
  require_once "tilauskasittely/tulosta_tilaustuotetarrat.inc";
}

//v‰h‰n joutuu laitaa talteen n‰it‰ alkuper‰isi‰
$isavarattu   = $atil;
$isatuoteno   = $tuoteno;
$useavalmiste = "";

if ($tee == 'UV') {
  if ($atil <= 0) {
    $tee = '';
    echo "<font class='error'>".t("Anna valmistettava m‰‰r‰")."</font><br>";
  }
}

// Katsotaan onko useita valmisteita?
if ($tee == 'UV') {
  $query = "SELECT tilausrivi.*, tuote.ei_saldoa, tuote.kehahin
            FROM tilausrivi
            JOIN tuote ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
            WHERE tilausrivi.yhtio        = '$kukarow[yhtio]'
            and tilausrivi.otunnus        = '$tilrivirow[otunnus]'
            and tilausrivi.perheid        = '$tilrivirow[perheid]'
            and tilausrivi.tyyppi         in ('W','M')
            and tilausrivi.toimitettuaika = '0000-00-00 00:00:00'
            ORDER by tilausrivi.tuoteno";
  $uvresult = pupe_query($query);

  if (mysql_num_rows($uvresult) > 1) {
    $useavalmiste = "ON";
  }
}

if ($tee == 'UV') {

  unset($tuoteresult);

  // k‰ytet‰‰n rivill‰ olevaa paikkaa
  $query = "SELECT tuotepaikat.saldo, tuote.ei_saldoa, tuote.yksikko, tuote.kehahin, tuotepaikat.tunnus tunnus, tuote.sarjanumeroseuranta,
            tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
            FROM tuote
            JOIN tuotepaikat ON (tuote.yhtio=tuotepaikat.yhtio and tuote.tuoteno=tuotepaikat.tuoteno)
            WHERE tuote.yhtio         = '$kukarow[yhtio]'
            and tuote.tuoteno         = '$isatuoteno'
            and tuotepaikat.hyllyalue = '$tilrivirow[hyllyalue]'
            and tuotepaikat.hyllynro  = '$tilrivirow[hyllynro]'
            and tuotepaikat.hyllytaso = '$tilrivirow[hyllytaso]'
            and tuotepaikat.hyllyvali = '$tilrivirow[hyllyvali]'";
  $tuoteresult = pupe_query($query);

  if (mysql_num_rows($tuoteresult) != 1) {
    //jos ei lˆytynyt yksiselitteist‰ rivi‰ yritet‰‰n oletuspaikkaa
    $query = "SELECT tuotepaikat.saldo, tuote.ei_saldoa, tuote.yksikko, tuote.kehahin, tuotepaikat.tunnus tunnus, tuote.sarjanumeroseuranta,
              tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
              FROM tuote
              JOIN tuotepaikat ON (tuote.yhtio=tuotepaikat.yhtio and tuote.tuoteno=tuotepaikat.tuoteno)
              WHERE tuote.yhtio       = '$kukarow[yhtio]'
              and tuote.tuoteno       = '$isatuoteno'
              and tuotepaikat.oletus != ''
              LIMIT 1";
    $tuoteresult = pupe_query($query);
  }

  if (mysql_num_rows($tuoteresult) != 1) {
    //jos ei lˆytynyt yksiselitteist‰ rivi‰ yritet‰‰n mit‰ tahansa paikkaa
    $query = "SELECT tuotepaikat.saldo, tuote.ei_saldoa, tuote.yksikko, tuote.kehahin, tuotepaikat.tunnus tunnus, tuote.sarjanumeroseuranta,
              tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
              FROM tuote
              JOIN tuotepaikat ON (tuote.yhtio=tuotepaikat.yhtio and tuote.tuoteno=tuotepaikat.tuoteno)
              WHERE tuote.yhtio = '$kukarow[yhtio]'
              and tuote.tuoteno = '$isatuoteno'
              LIMIT 1";
    $tuoteresult = pupe_query($query);
  }

  if (mysql_num_rows($tuoteresult) != 1) {
    $tee = '';
    echo "<font class='error'>$isatuoteno ".t("Varastopaikkaa ei lˆydy")."</font><br>";
  }
  else {
    // Valmisteen kokonaissaldo
    $query = "SELECT sum(saldo) kokonaissaldo
              FROM tuotepaikat
              WHERE yhtio = '$kukarow[yhtio]'
              and tuoteno = '$isatuoteno'";
    $saldores = pupe_query($query);
    $saldorow = mysql_fetch_assoc($saldores);

    $valmisterow = mysql_fetch_assoc($tuoteresult);
    $valmisterow['kokonaissaldo'] = $saldorow['kokonaissaldo'];

    // P‰ivitet‰‰n sarjanumerolle ja riville lˆytynyt paikka
    $query = "UPDATE tilausrivi SET
              hyllyalue   = '$valmisterow[hyllyalue]',
              hyllynro    = '$valmisterow[hyllynro]',
              hyllytaso   = '$valmisterow[hyllytaso]',
              hyllyvali   = '$valmisterow[hyllyvali]'
              WHERE yhtio = '$kukarow[yhtio]'
              and tunnus  = '$tilrivirow[tunnus]'";
    $lisa_res = pupe_query($query);

    $tilrivirow['hyllyalue'] = $valmisterow['hyllyalue'];
    $tilrivirow['hyllynro']  = $valmisterow['hyllynro'];
    $tilrivirow['hyllytaso'] = $valmisterow['hyllytaso'];
    $tilrivirow['hyllyvali'] = $valmisterow['hyllyvali'];

    if ($valmisterow['sarjanumeroseuranta'] != "") {
      $query = "UPDATE sarjanumeroseuranta SET
                hyllyalue          = '$valmisterow[hyllyalue]',
                hyllynro           = '$valmisterow[hyllynro]',
                hyllytaso          = '$valmisterow[hyllytaso]',
                hyllyvali          = '$valmisterow[hyllyvali]'
                WHERE yhtio        = '$kukarow[yhtio]'
                and ostorivitunnus = '$tilrivirow[tunnus]'";
      $lisa_res = pupe_query($query);
    }
  }

  // raaka-aineet
  $query = "SELECT tilausrivi.*,
            tuote.ei_saldoa, tuote.sarjanumeroseuranta
            FROM tilausrivi
            JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
            WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
            and tilausrivi.otunnus = '$tilrivirow[otunnus]'
            and tilausrivi.perheid = '$tilrivirow[perheid]'
            and tilausrivi.tyyppi  = 'V'
            ORDER BY tilausrivi.tuoteno";
  $raakaaineres = pupe_query($query);

  //jos tuoteperhe on olemassa
  if (mysql_num_rows($raakaaineres) > 0) {
    while ($raakaainerow = mysql_fetch_assoc($raakaaineres)) {
      if ($raakaainerow["ei_saldoa"] == "") {

        unset($tuoteresult);

        $varataankpl = 0;

        //N‰in paljon haluamme k‰ytt‰‰ raaka-ainetta
        if ($kulukpllat[$raakaainerow["tunnus"]] != 0) {
          $varataankpl = $kulukpllat[$raakaainerow["tunnus"]];
        }
        else {
          $varataankpl = $raakaainerow['varattu'] * $akerroin;
        }

        //k‰ytet‰‰n rivill‰ olevaa paikkaa
        $query = "SELECT tuotepaikat.saldo, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
                  FROM tuote
                  JOIN tuotepaikat ON (tuote.yhtio=tuotepaikat.yhtio and tuote.tuoteno=tuotepaikat.tuoteno)
                  WHERE tuote.yhtio         = '$kukarow[yhtio]'
                  and tuote.tuoteno         = '$raakaainerow[tuoteno]'
                  and tuotepaikat.hyllyalue = '$raakaainerow[hyllyalue]'
                  and tuotepaikat.hyllynro  = '$raakaainerow[hyllynro]'
                  and tuotepaikat.hyllytaso = '$raakaainerow[hyllytaso]'
                  and tuotepaikat.hyllyvali = '$raakaainerow[hyllyvali]'";
        $tuoteresult = pupe_query($query);

        if (mysql_num_rows($tuoteresult) != 1) {
          //jos ei lˆytynyt yksiselitteist‰ rivi‰ yritet‰‰n oletuspaikkaa
          $query = "SELECT tuotepaikat.saldo, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
                    FROM tuote
                    JOIN tuotepaikat ON (tuote.yhtio=tuotepaikat.yhtio and tuote.tuoteno=tuotepaikat.tuoteno)
                    WHERE tuote.yhtio       = '$kukarow[yhtio]'
                    and tuote.tuoteno       = '$raakaainerow[tuoteno]'
                    and tuotepaikat.oletus != ''
                    LIMIT 1";
          $tuoteresult = pupe_query($query);
        }

        if (mysql_num_rows($tuoteresult) != 1) {
          //jos ei lˆytynyt yksiselitteist‰ rivi‰ yritet‰‰n mit‰ tahansa paikkaa
          $query = "SELECT tuotepaikat.saldo, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
                    FROM tuote
                    JOIN tuotepaikat ON (tuote.yhtio=tuotepaikat.yhtio and tuote.tuoteno=tuotepaikat.tuoteno)
                    WHERE tuote.yhtio = '$kukarow[yhtio]'
                    and tuote.tuoteno = '$raakaainerow[tuoteno]'
                    LIMIT 1";
          $tuoteresult = pupe_query($query);
        }

        //katotaan kanssa, ett‰ perheenj‰senet lˆytyy kannasta ja niit‰ on riitt‰v‰sti
        if (mysql_num_rows($tuoteresult) == 1) {
          $trow = mysql_fetch_assoc($tuoteresult);

          // P‰ivitet‰‰n sarjanumerolle ja riville lˆytynyt paikka
          $query = "UPDATE tilausrivi SET
                    hyllyalue   = '$trow[hyllyalue]',
                    hyllynro    = '$trow[hyllynro]',
                    hyllytaso   = '$trow[hyllytaso]',
                    hyllyvali   = '$trow[hyllyvali]'
                    WHERE yhtio = '$kukarow[yhtio]'
                    and tunnus  = '$raakaainerow[tunnus]'";
          $lisa_res = pupe_query($query);

          if ($raakaainerow['sarjanumeroseuranta'] != "") {
            $query = "UPDATE sarjanumeroseuranta SET
                      hyllyalue            = '$trow[hyllyalue]',
                      hyllynro             = '$trow[hyllynro]',
                      hyllytaso            = '$trow[hyllytaso]',
                      hyllyvali            = '$trow[hyllyvali]'
                      WHERE yhtio          = '$kukarow[yhtio]'
                      and myyntirivitunnus = '$raakaainerow[tunnus]'";
            $lisa_res = pupe_query($query);
          }

          if ($trow['saldo'] < $varataankpl and $vakisinhyvaksy == "") {
            $tee = ''; //Raaka-aineet eiv‰t riit‰!
            echo "<font class='error'>$raakaainerow[tuoteno] ".t("saldo")." $trow[saldo] ".t("ei riit‰ valmistukseen")." ($varataankpl)</font><br>";
          }
        }
        else {
          $tee = '';
          echo "<font class='error'>$raakaainerow[tuoteno] ".t("Varastopaikkaa ei lˆydy")."</font><br>";
        }
      }
    }
  }
  else {
    $tee = '';
    echo "<font class='error'>$tuoteno ".t("ei ole rakenne/resepti")."</font>";
  }

  echo "<br><br>";
}

if ($tee == 'UV') { //Tehd‰‰n tuotteet!

  $uusiarvo   = 0;
  $kpl     = 0;

  $query = "SELECT tilausrivi.*,
            tuote.ei_saldoa, tuote.sarjanumeroseuranta
            FROM tilausrivi
            JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
            WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
            and tilausrivi.otunnus = '$tilrivirow[otunnus]'
            and tilausrivi.perheid = '$tilrivirow[perheid]'
            and tilausrivi.tyyppi  = 'V'
            ORDER BY tilausrivi.tuoteno";
  $raakaaineres = pupe_query($query);

  while ($raakaainerow = mysql_fetch_assoc($raakaaineres)) {

    unset($tuoteresult);

    if ($raakaainerow["ei_saldoa"] == "") {
      $query = "SELECT tuotepaikat.saldo, tuote.kehahin, tuote.ei_saldoa, tuote.yksikko, tuotepaikat.tunnus tunnus,
                tuote.epakurantti25pvm, tuote.epakurantti50pvm, tuote.epakurantti75pvm, tuote.epakurantti100pvm,
                tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
                FROM tuote
                JOIN tuotepaikat ON (tuote.yhtio=tuotepaikat.yhtio and tuote.tuoteno=tuotepaikat.tuoteno)
                WHERE tuote.yhtio         = '$kukarow[yhtio]'
                and tuote.tuoteno         = '$raakaainerow[tuoteno]'
                and tuotepaikat.hyllyalue = '$raakaainerow[hyllyalue]'
                and tuotepaikat.hyllynro  = '$raakaainerow[hyllynro]'
                and tuotepaikat.hyllytaso = '$raakaainerow[hyllytaso]'
                and tuotepaikat.hyllyvali = '$raakaainerow[hyllyvali]'";
    }
    else {
      //sittenh‰n tuote on saldoton
      $query = "SELECT tuote.kehahin, tuote.ei_saldoa, tuote.yksikko,
                tuote.epakurantti25pvm, tuote.epakurantti50pvm, tuote.epakurantti75pvm, tuote.epakurantti100pvm
                FROM tuote
                WHERE tuote.tuoteno = '$raakaainerow[tuoteno]'
                and tuote.yhtio     = '$kukarow[yhtio]'";
    }

    $tuoteresult = pupe_query($query);
    $tuoterow = mysql_fetch_assoc($tuoteresult);

    // Poistetaan raaka-aineet varastosta
    if ($kulukpllat[$raakaainerow["tunnus"]] != 0) {
      $kpl = str_replace(',', '.', $kulukpllat[$raakaainerow["tunnus"]]);
    }
    else {
      $kpl = $raakaainerow['varattu'] * str_replace(',', '.', $akerroin);
    }

    $kpl = str_replace(',', '.', $kpl);

    if    ($tuoterow['epakurantti100pvm'] != '0000-00-00') $tuoterow['kehahin'] = 0;
    elseif ($tuoterow['epakurantti75pvm'] != '0000-00-00') $tuoterow['kehahin'] = round($tuoterow['kehahin'] * 0.25, 6);
    elseif ($tuoterow['epakurantti50pvm'] != '0000-00-00') $tuoterow['kehahin'] = round($tuoterow['kehahin'] * 0.5,  6);
    elseif ($tuoterow['epakurantti25pvm'] != '0000-00-00') $tuoterow['kehahin'] = round($tuoterow['kehahin'] * 0.75, 6);

    if (in_array($raakaainerow["sarjanumeroseuranta"], array("S", "G"))) {
      $arvo = sarjanumeron_ostohinta("myyntirivitunnus", $raakaainerow["tunnus"])*$kpl;
    }
    else {
      $arvo = $tuoterow['kehahin'] * $kpl;
    }

    $uusiarvo += $arvo;

    ///* Tehd‰‰n tapahtuma *///
    $query = "INSERT into tapahtuma set
              yhtio      = '$kukarow[yhtio]',
              tuoteno    = '$raakaainerow[tuoteno]',
              laji       = 'kulutus',
              kpl        =  round($kpl*-1 ,2),
              kplhinta   = '$tuoterow[kehahin]',
              hinta      = '$tuoterow[kehahin]',
              rivitunnus = '$raakaainerow[tunnus]',
              hyllyalue  = '$tuoterow[hyllyalue]',
              hyllynro   = '$tuoterow[hyllynro]',
              hyllytaso  = '$tuoterow[hyllytaso]',
              hyllyvali  = '$tuoterow[hyllyvali]',
              selite     = '".t("K‰ytettiin tuotteen")." $isatuoteno ".t("valmistamiseen")." ".t("tyˆm‰‰r‰yksell‰")." $tilrivirow[otunnus]',
              laatija    = '$kukarow[kuka]',
              laadittu   = now()";
    $result = pupe_query($query);

    if ($tuoterow['ei_saldoa'] == "") {
      $query = "UPDATE tuotepaikat
                SET saldo  = round(saldo-$kpl,2)
                WHERE yhtio = '$kukarow[yhtio]'
                and tuoteno = '$raakaainerow[tuoteno]'
                and tunnus  = '$tuoterow[tunnus]'";
      $result = pupe_query($query);

      //  Merkataan er‰ kulutetuksi
      if (in_array($raakaainerow["sarjanumeroseuranta"], array("E", "F", "G"))) {

        // Haetaan eka raaka-aineriviin liitetyn er‰n tiedot
        $query = "SELECT *
                  FROM sarjanumeroseuranta
                  WHERE yhtio          = '$kukarow[yhtio]'
                  and tuoteno          = '$raakaainerow[tuoteno]'
                  and myyntirivitunnus = '$raakaainerow[tunnus]'
                  LIMIT 1";
        $lisa_res = pupe_query($query);
        $lisa_row = mysql_fetch_assoc($lisa_res);

        // luodaan muuttuja jota pienennet‰‰n luupissa
        $eraayht = $kpl;
        $ederaostorivi = 0;

        // koitetaan lˆyt‰‰ vapaita ostettuja eri‰ mit‰ kuluttaa
        $query = "SELECT era_kpl, tunnus, ostorivitunnus
                  FROM sarjanumeroseuranta
                  WHERE yhtio          = '$kukarow[yhtio]'
                  and tuoteno          = '$raakaainerow[tuoteno]'
                  and ostorivitunnus   > 0
                  and myyntirivitunnus = 0
                  and sarjanumero      = '$lisa_row[sarjanumero]'
                  and era_kpl          > 0
                  and hyllyalue        = '$tuoterow[hyllyalue]'
                  and hyllynro         = '$tuoterow[hyllynro]'
                  and hyllyvali        = '$tuoterow[hyllyvali]'
                  and hyllytaso        = '$tuoterow[hyllytaso]'
                  ORDER BY era_kpl DESC, tunnus";
        $erajaljella_res = pupe_query($query);

        // jos lˆytyy ostettuja eri‰ myyt‰v‰ks niin menn‰‰n t‰nne
        while ($erajaljella_row = mysql_fetch_assoc($erajaljella_res)) {

          // luodaan muuttuja jossa annetaan kyselyille m‰‰r‰muutoksen
          $eravahennetaan = 0;

          // jos lˆydetyn er‰n m‰‰r‰ riitt‰‰ koko tarvittavaan m‰‰r‰‰n niin otetaan sen verran ku tarvitaan
          if ($erajaljella_row["era_kpl"] >= $eraayht) {
            $eravahennetaan = $eraayht;
          }
          // muuten vaan lˆydetyn er‰n verran
          else {
            $eravahennetaan = $erajaljella_row["era_kpl"];
          }

          // v‰hennet‰‰n ostorivilt‰ tarvittava m‰‰r‰
          $query = "UPDATE sarjanumeroseuranta
                    SET era_kpl = era_kpl-$eravahennetaan,
                    muutospvm   = now(),
                    muuttaja    = '$kukarow[kuka]'
                    WHERE yhtio = '$kukarow[yhtio]'
                    and tunnus  = '$erajaljella_row[tunnus]'";
          $lisa_res = pupe_query($query);

          $raakaerarow = array('tunnus' => 0, 'ostorivitunnus' => 0);

          if ($ederaostorivi == 0 and $lisa_row['era_kpl'] == 0) {
            // T‰m‰ on loopin eka kierros, silloin p‰ivitet‰‰n aina alunperin raaka-aineriviin liitetty er‰numerorivi
            $raakaerarow = $lisa_row;
          }
          else {
            // Onko n‰ill‰ tiedoilla jo er‰ t‰ll‰ raaka-ainerivill‰? (Raaka-ainerivill‰ voi olla useampi er‰numerorivi jos joudutaan k‰ytt‰‰ useampaa er‰‰)
            $query = "SELECT *
                      FROM sarjanumeroseuranta
                      WHERE yhtio          = '$kukarow[yhtio]'
                      and tuoteno          = '$raakaainerow[tuoteno]'
                      and myyntirivitunnus = '$raakaainerow[tunnus]'
                      and ostorivitunnus   = '$erajaljella_row[ostorivitunnus]'
                      LIMIT 1";
            $lisa_res = pupe_query($query);
            $raakaerarow = mysql_fetch_assoc($lisa_res);
          }

          if ($raakaerarow['ostorivitunnus'] == $erajaljella_row['ostorivitunnus']) {
            // lis‰t‰‰n raaka-aineriviin liitetylle er‰lle m‰‰r‰, ja samalla p‰ivitet‰‰n ostorivitunnus oikeaksi
            $query = "UPDATE sarjanumeroseuranta
                      SET era_kpl      = era_kpl+$eravahennetaan,
                      ostorivitunnus = '$erajaljella_row[ostorivitunnus]',
                      muutospvm      = now(),
                      muuttaja       = '$kukarow[kuka]'
                      WHERE yhtio    = '$kukarow[yhtio]'
                      and tunnus     = '$raakaerarow[tunnus]'";
            $lisa_res = pupe_query($query);
          }
          else {
            // lis‰t‰‰n er‰ oikealla ostorivitunnuksella ja er‰n m‰‰r‰ll‰
            $query = "INSERT into sarjanumeroseuranta
                      SET yhtio      = '$kukarow[yhtio]',
                      tuoteno          = '$lisa_row[tuoteno]',
                      lisatieto        = '$lisa_row[lisatieto]',
                      myyntirivitunnus = '$lisa_row[myyntirivitunnus]',
                      ostorivitunnus   = '$erajaljella_row[ostorivitunnus]',
                      kaytetty         = '$lisa_row[kaytetty]',
                      era_kpl          = $eravahennetaan,
                      laatija          = '$kukarow[kuka]',
                      luontiaika       = now(),
                      takuu_alku       = '$lisa_row[takuu_alku]',
                      takuu_loppu      = '$lisa_row[takuu_loppu]',
                      parasta_ennen    = '$lisa_row[parasta_ennen]',
                      hyllyalue        = '$lisa_row[hyllyalue]',
                      hyllynro         = '$lisa_row[hyllynro]',
                      hyllytaso        = '$lisa_row[hyllytaso]',
                      hyllyvali        = '$lisa_row[hyllyvali]',
                      sarjanumero      = '$lisa_row[sarjanumero]'";
            $lisa_res = pupe_query($query);
          }

          // v‰hennet‰‰n luupattavaa m‰‰r‰‰
          $eraayht -= $eravahennetaan;

          // t‰t‰ ostorivitunnaria k‰ytettiin
          $ederaostorivi = $erajaljella_row["ostorivitunnus"];

          // Jos kaikki on jo allokoitu niin breikataan
          if ($eraayht <= 0) {
            break;
          }
        }

        // Ei lˆytynyt riitt‰v‰sti ostettuja eri‰, eli "myyty pakkaselle" niin luodaan uusi er‰rivi jossa ostorivitunnus on nolla jonne laitetaan loppum‰‰r‰.
        if ($eraayht != 0) {

          $query = "INSERT into sarjanumeroseuranta
                    SET yhtio      = '$kukarow[yhtio]',
                    tuoteno          = '$raakaainerow[tuoteno]',
                    myyntirivitunnus = $raakaainerow[tunnus],
                    ostorivitunnus   = 0,
                    era_kpl          = 0,
                    laatija          = '$kukarow[kuka]',
                    luontiaika       = now(),
                    hyllyalue        = '$tuoterow[hyllyalue]',
                    hyllynro         = '$tuoterow[hyllynro]',
                    hyllyvali        = '$tuoterow[hyllyvali]',
                    hyllytaso        = '$tuoterow[hyllytaso]',
                    sarjanumero      = '$lisa_row[sarjanumero]'";
          //$lisa_res = pupe_query($query);

          $query = "UPDATE sarjanumeroseuranta
                    SET era_kpl     = $eraayht,
                    ostorivitunnus       = '$raakaainerow[tunnus]',
                    muutospvm            = now(),
                    muuttaja             = '$kukarow[kuka]'
                    WHERE yhtio          = '$kukarow[yhtio]'
                    and tuoteno          = '$raakaainerow[tuoteno]'
                    and myyntirivitunnus = '$raakaainerow[tunnus]'
                    and era_kpl          = 0
                    LIMIT 1";
          //$lisa_res = pupe_query($query);
        }
      }

      echo "<font class='message'>".t("Varastosta k‰ytettiin tuotetta")." $raakaainerow[tuoteno] $kpl ".t_avainsana("Y", "", "and avainsana.selite='$tuoterow[yksikko]'", "", "", "selite").", $tuoterow[kehahin]</font><br>";

    }
    else {
      echo "<font class='message'>".t("K‰ytettiin tuotetta")." $raakaainerow[tuoteno] $kpl ".t_avainsana("Y", "", "and avainsana.selite='$tuoterow[yksikko]'", "", "", "selite").", $tuoterow[kehahin]</font><br>";
    }

    //p‰ivitet‰‰n tilausrivi k‰ytetyksi
    $query = "UPDATE tilausrivi
              SET varattu = round(varattu-$kpl,2),
              kpl         = round(kpl+$kpl,2)
              WHERE yhtio = '$kukarow[yhtio]'
              and otunnus = '$tilrivirow[otunnus]'
              and tunnus  = '$raakaainerow[tunnus]'
              and tyyppi  = 'V'";
    $upparesult = pupe_query($query);
  }

  if (!function_exists("valmista_isa")) {
    function valmista_isa($valmisterow, $tilrivirow, $isavarattu, $isatuoteno, $uusiarvo, $kaikenkaikkiaan, $kaikenkaikkiaan_arvot) {
      global $yhtiorow, $kukarow, $laskurow, $valmistettavat, $jaljella_tot;

      // Jos on useampi valmiste
      if ((float) $kaikenkaikkiaan > 1) {

        if ($yhtiorow["varastonarvon_jako_usealle_valmisteelle"] == "K" and !empty($tilrivirow['valmistus_painoarvo'])) {
          // jyvitet‰‰n $uusiarvo valmisteille kappaleiden ja painoarvon mukaan
          $uusiarvo *= $tilrivirow['valmistus_painoarvo'];
        }
        else {
          if ((float) $kaikenkaikkiaan_arvot > 0) {
            //jyvitet‰‰n $uusiarvo valmisteille kappaleiden ja kehahintojen mukaan
            $uusiarvo = $uusiarvo * (($isavarattu * $valmisterow["kehahin"]) / $kaikenkaikkiaan_arvot);
          }
          else {
            //jyvitet‰‰n $uusiarvo valmisteille valmisteiden m‰‰r‰n mukaan
            $uusiarvo = $uusiarvo / $kaikenkaikkiaan;
          }
        }

        $uusiarvo = round($uusiarvo, 6);
      }

      if ($valmisterow["sarjanumeroseuranta"] == "S" or $valmisterow["sarjanumeroseuranta"] == "G") {
        $kehahin = 0;
        $selite = t("Valmistettiin tuotetta tyˆm‰‰r‰yksell‰")." $tilrivirow[otunnus] ($uusiarvo)";
      }
      else {
        if ($valmisterow['kokonaissaldo'] + $isavarattu > 0 and $valmisterow['kokonaissaldo'] > 0) {
          // kehahin matikka (tuotteella pit‰‰ olla saldoa ennen ja j‰lkeen, ett‰ kehahin lasketaan)
          $kehahin = round(($valmisterow['kokonaissaldo'] * $valmisterow['kehahin'] + $uusiarvo) / ($valmisterow['kokonaissaldo'] + $isavarattu), 6);
        }
        else {
          // jos j‰‰d‰‰n saldossa t‰m‰nkin tulon j‰lkeen miinukselle tai nollille, laitetaan kehahin suoraan t‰m‰n valmistuksen hinnasta
          $kehahin = round($uusiarvo/$isavarattu, 6);
        }

        $selite = t("Valmistettiin tuotetta tyˆm‰‰r‰yksell‰")." $tilrivirow[otunnus] ($valmisterow[kehahin] --> $kehahin)";
      }

      ///* Tehd‰‰n tapahtuma *///
      $query = "INSERT into tapahtuma set
                yhtio      = '$kukarow[yhtio]',
                tuoteno    = '$isatuoteno',
                rivitunnus = '$tilrivirow[tunnus]',
                laji       = 'valmistus',
                kpl        = '$isavarattu',
                kplhinta   =  round($uusiarvo/$isavarattu, 6),
                hinta      = '$kehahin',
                hyllyalue  = '$valmisterow[hyllyalue]',
                hyllynro   = '$valmisterow[hyllynro]',
                hyllytaso  = '$valmisterow[hyllytaso]',
                hyllyvali  = '$valmisterow[hyllyvali]',
                selite     = '$selite',
                laatija    = '$kukarow[kuka]',
                laadittu   = now()";
      $result = pupe_query($query);
      $tapahtumaid = mysql_insert_id($GLOBALS["masterlink"]);

      $query = "UPDATE tuote
                SET kehahin  = $kehahin,
                vihahin     = round($uusiarvo/$isavarattu, '$yhtiorow[hintapyoristys]'),
                vihapvm     = now()
                WHERE yhtio = '$kukarow[yhtio]'
                and tuoteno = '$isatuoteno'";
      $result = pupe_query($query);

      $query = "UPDATE tuotepaikat
                SET saldo = saldo+$isavarattu
                WHERE yhtio = '$kukarow[yhtio]'
                and tuoteno = '$isatuoteno'
                and tunnus  = '$valmisterow[tunnus]'";
      $result = pupe_query($query);

      // p‰ivitet‰‰n tilausrivi k‰ytetyksi ja splitataan se...
      // osavalmistus tai kokovalmistus
      if ($isavarattu <= $tilrivirow["varattu"]) {

        $ale_query_insert_lisa = '';

        for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
          $ale_query_insert_lisa .= " ale{$alepostfix} = '".$tilrivirow["ale{$alepostfix}"]."',";
        }

        // Asiakkaallevalmistus
        if ($laskurow["tilaustyyppi"] == "V" and $tilrivirow["tyyppi"] == "W") {

          //luodaan toimitettu valmisterivi
          $query = "INSERT into tilausrivi set
                    laatija     = '$kukarow[kuka]',
                    laadittu    = now(),
                    yhtio       = '$kukarow[yhtio]',
                    tuoteno     = '$tilrivirow[tuoteno]',
                    varattu     = '$isavarattu',
                    tilkpl      = '$isavarattu',
                    kpl         = '',
                    {$ale_query_insert_lisa}
                    erikoisale  = '$tilrivirow[erikoisale]',
                    netto       = '$tilrivirow[netto]',
                    jt          = '',
                    yksikko     = '$tilrivirow[yksikko]',
                    try         = '$tilrivirow[try]',
                    osasto      = '$tilrivirow[osasto]',
                    alv         = '$tilrivirow[alv]',
                    hinta       = '$tilrivirow[hinta]',
                    kerayspvm   = '$tilrivirow[kerayspvm]',
                    nimitys     = '$tilrivirow[nimitys]',
                    otunnus     = '$tilrivirow[otunnus]',
                    uusiotunnus = '$tilrivirow[uusiotunnus]',
                    tyyppi      = 'L',
                    keratty     = '$kukarow[kuka]',
                    kerattyaika = now(),
                    toimaika    = '$tilrivirow[toimaika]',
                    var         = '$tilrivirow[var]',
                    perheid     = 0,
                    kommentti   = '$tilrivirow[kommentti]',
                    hyllyalue   = '$tilrivirow[hyllyalue]',
                    hyllynro    = '$tilrivirow[hyllynro]',
                    hyllytaso   = '$tilrivirow[hyllytaso]',
                    hyllyvali   = '$tilrivirow[hyllyvali]'";
          $insresult = pupe_query($query);
          $valmistettavat .= ",".mysql_insert_id($GLOBALS["masterlink"]);
        }

        //  Kaikille sarjanumeroille tehd‰‰n varastoon saavutettu O-rivi, muuten mm ker‰ys ei toimi ja ehk‰ se on myˆs loogisempaa?
        //  P‰ivitet‰‰n valmistetulle riville laskutettuaika, jotta se n‰kyy kaikkialla oikein
        if ($valmisterow["sarjanumeroseuranta"] != "") {
          $tilausriviTyyppi = "O";
          $laskutettuLisa = ", laskutettu = '$kukarow[kuka]', laskutettuaika = now()";
        }
        else {
          $tilausriviTyyppi = "D";
          $laskutettuLisa = "";
        }

        // luodaan toimitettu valmisterivi
        $query = "INSERT into tilausrivi set
                  laatija     = '$kukarow[kuka]',
                  laadittu    = now(),
                  yhtio       = '$kukarow[yhtio]',
                  tuoteno     = '$tilrivirow[tuoteno]',
                  varattu     = '$isavarattu',
                  tilkpl      = '$isavarattu',
                  kpl         = '$isavarattu',
                  {$ale_query_insert_lisa}
                  erikoisale  = '$tilrivirow[erikoisale]',
                  netto       = '$tilrivirow[netto]',
                  jt          = '',
                  yksikko     = '$tilrivirow[yksikko]',
                  try         = '$tilrivirow[try]',
                  osasto      = '$tilrivirow[osasto]',
                  alv         = '$tilrivirow[alv]',
                  hinta       = '$tilrivirow[hinta]',
                  rivihinta   = '$uusiarvo',
                  kerayspvm   = '$tilrivirow[kerayspvm]',
                  nimitys     = '$tilrivirow[nimitys]',
                  otunnus     = '$tilrivirow[otunnus]',
                  uusiotunnus = '$tilrivirow[uusiotunnus]',
                  tyyppi      = '$tilausriviTyyppi',
                  keratty     = '$kukarow[kuka]',
                  kerattyaika = now(),
                  toimaika    = '$tilrivirow[toimaika]',
                  var         = '$tilrivirow[var]',
                  perheid     = '$tilrivirow[perheid]',
                  kommentti   = '$tilrivirow[kommentti]',
                  hyllyalue   = '$tilrivirow[hyllyalue]',
                  hyllynro    = '$tilrivirow[hyllynro]',
                  hyllytaso   = '$tilrivirow[hyllytaso]',
                  hyllyvali   = '$tilrivirow[hyllyvali]'
                  $laskutettuLisa";
        $insresult = pupe_query($query);
        $uusrivitunnus = mysql_insert_id($GLOBALS["masterlink"]);
        $valmistettavat .= ",".$uusrivitunnus;

        $jaljella = round($tilrivirow["varattu"] - $isavarattu, 2);
        $jaljella_tot += $jaljella;

        //p‰ivitet‰‰n alkuper‰inen rivi
        $query = "UPDATE tilausrivi
                  SET varattu = '$jaljella',
                  kpl         = round(kpl+$isavarattu,2)
                  WHERE yhtio = '$kukarow[yhtio]'
                  and otunnus = '$tilrivirow[otunnus]'
                  and tunnus  = '$tilrivirow[tunnus]'
                  and tyyppi  in ('W','M')";
        $upparesult = pupe_query($query);

        // Siirret‰‰n sarjanumerot valmistetulle tilausriville
        if (in_array($valmisterow["sarjanumeroseuranta"], array("S", "T", "V"))) {

          $int_isavarattu = (int) $isavarattu;

          //  Siirret‰‰n ostorivin tunnus valmistetulle riville
          $query = "SELECT tunnus
                    FROM sarjanumeroseuranta
                    WHERE yhtio        = '$kukarow[yhtio]'
                    and ostorivitunnus = '$tilrivirow[tunnus]'
                    LIMIT $int_isavarattu";
          $sarjares = pupe_query($query);

          while ($sarjarow = mysql_fetch_assoc($sarjares)) {
            $query = "UPDATE sarjanumeroseuranta SET
                      ostorivitunnus = $uusrivitunnus
                      WHERE yhtio    = '$kukarow[yhtio]'
                      and tunnus     = '$sarjarow[tunnus]'";
            $result = pupe_query($query);
          }

          // Generoidaan loput jos jotain puuttuu!
          $puuttuva_sarjanumerot = $isavarattu - mysql_num_rows($sarjares);

          if ($puuttuva_sarjanumerot > 0) {
            for ($i=1; $i<=$puuttuva_sarjanumerot; $i++) {
              //  Arvotaan uniikki sarjanumero
              $query = "SELECT count(distinct(sarjanumero)) kpl
                        FROM sarjanumeroseuranta
                        WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tilrivirow[tuoteno]'";
              $sarjares = pupe_query($query);
              $sarjarow = mysql_fetch_assoc($sarjares);
              $sarja = $sarjarow["kpl"]+1;

              // varmistetaan, ett‰ t‰m‰ on uniikki!
              $onUniikki = false;

              while ($onUniikki === false) {
                $sarjanro = "$tilrivirow[tuoteno]-$sarja";

                $query = "SELECT tunnus
                          FROM sarjanumeroseuranta
                          WHERE yhtio     = '$kukarow[yhtio]'
                          and tuoteno     = '$tilrivirow[tuoteno]'
                          and sarjanumero = '$sarjanro'";
                $sarjares = pupe_query($query);

                if (mysql_num_rows($sarjares) == 0) {
                  $onUniikki = true;
                }
                else {
                  $sarja++;
                }
              }

              $query = "INSERT INTO sarjanumeroseuranta
                        (yhtio, tuoteno, sarjanumero, ostorivitunnus, myyntirivitunnus, laatija, luontiaika, hyllyalue, hyllynro, hyllyvali, hyllytaso)
                        VALUES ('$kukarow[yhtio]', '$tilrivirow[tuoteno]', '$sarjanro', '$uusrivitunnus', 0, '$kuka[kuka]', now(), '$tilrivirow[hyllyalue]', '$tilrivirow[hyllynro]', '$tilrivirow[hyllyvali]', '$tilrivirow[hyllytaso]')";
              $sarjares1 = pupe_query($query);
              $uusisarjatunnus = mysql_insert_id($GLOBALS["masterlink"]);
            }
          }

          tulosta_tilaustuotetarrat(0, $uusrivitunnus, "OPTIMOI");
        }
        elseif (in_array($valmisterow["sarjanumeroseuranta"], array("E", "F", "G"))) {

          // Siirret‰‰n ostorivin tunnus valmistetulle riville
          $query = "SELECT tunnus, era_kpl, sarjanumero, lisatieto
                    FROM sarjanumeroseuranta
                    WHERE yhtio        = '$kukarow[yhtio]'
                    and ostorivitunnus = '$tilrivirow[tunnus]'";
          $sarjares = pupe_query($query);

          // Generoidaan loput jos jotain puuttuu!
          $eraa_tarvitaan_viela = $isavarattu;

          $v_eranro = "";
          $v_listie = "";

          while ($sarjarow = mysql_fetch_assoc($sarjares)) {

            $v_eranro = $sarjarow['sarjanumero'];
            $v_listie = $sarjarow['lisatieto'];

            // Valmistetaan v‰hemm‰n ku er‰ss‰ ois kamaa, eli osavalmistus
            if ($sarjarow["era_kpl"] > $isavarattu) {
              $eraa_tarvitaan_viela = $sarjarow["era_kpl"]-$isavarattu;

              $query = "UPDATE sarjanumeroseuranta SET
                        ostorivitunnus = $uusrivitunnus,
                        era_kpl        = $isavarattu
                        WHERE yhtio    = '$kukarow[yhtio]'
                        and tunnus     = '$sarjarow[tunnus]'";
              $result = pupe_query($query);

              break;
            }
            else {
              $eraa_tarvitaan_viela -= $sarjarow["era_kpl"];

              $query = "UPDATE sarjanumeroseuranta SET
                        ostorivitunnus = $uusrivitunnus
                        WHERE yhtio    = '$kukarow[yhtio]'
                        and tunnus     = '$sarjarow[tunnus]'";
              $result = pupe_query($query);
            }
          }

          if ($eraa_tarvitaan_viela != 0) {
            $query = "INSERT into sarjanumeroseuranta
                      SET yhtio      = '$kukarow[yhtio]',
                      tuoteno          = '$tilrivirow[tuoteno]',
                      myyntirivitunnus = 0,
                      ostorivitunnus   = $tilrivirow[tunnus],
                      era_kpl          = $eraa_tarvitaan_viela,
                      laatija          = '$kukarow[kuka]',
                      luontiaika       = now(),
                      hyllyalue        = '$valmisterow[hyllyalue]',
                      hyllynro         = '$valmisterow[hyllynro]',
                      hyllyvali        = '$valmisterow[hyllyvali]',
                      hyllytaso        = '$valmisterow[hyllytaso]',
                      sarjanumero      = '$v_eranro',
                      lisatieto        = '$v_listie'";
            $result = pupe_query($query);
          }

          tulosta_tilaustuotetarrat(0, $uusrivitunnus, "OPTIMOI");
        }
      }

      preg_match("/\((.*)\)/", $selite, $selite_2);
      //printataan pikku raporttia mit‰ tulikaan tehty‰
      echo "<font class='message'>".t("Valmistettiin tuotetta")." $isatuoteno $isavarattu ".t_avainsana("Y", $kieli, "and avainsana.selite='$valmisterow[yksikko]'", "", "", "selite")."</font> ".t("Kehahintamuutos")." ($selite_2[1])<br>";

      $query = "SELECT paivitys
                FROM oikeu
                WHERE yhtio = '$kukarow[yhtio]'
                and kuka    = '$kukarow[kuka]'
                and nimi    = 'tilauskasittely/jtselaus.php'
                and alanimi = ''";
      $jtoikeudetres = pupe_query($query);

      if ((mysql_num_rows($jtoikeudetres) <> 0 and $yhtiorow["automaattinen_jt_toimitus_valmistus"] != "") or $yhtiorow["automaattinen_jt_toimitus_valmistus"] == "J") {
        $jtoikeudetrow  = mysql_fetch_assoc($jtoikeudetres);
        $jtrivit     = array();
        $jtrivit_paikat = array();

        echo "<br>";

        //  Kohdistetaan vain oikeille asiakkaille n‰iss‰ caseissa
        if ($yhtiorow['automaattinen_jt_toimitus_valmistus'] == 'S' or $yhtiorow["automaattinen_jt_toimitus_valmistus"] == "V") {
          $suoralisa = "";

          if ($yhtiorow['automaattinen_jt_toimitus_valmistus'] == 'S') {
            $suoralisa = "AND tilausrivin_lisatiedot.suoraan_laskutukseen = 'o'";
          }

          //Tutkitaan lˆytyykˆ JT-rivi joka m‰pp‰ytyy t‰h‰n valmistusriviin
          $query = "SELECT ifnull(if((tilausrivi.perheid != 0 and tilausrivi.tunnus != tilausrivi.perheid), tilausrivi.perheid, tilausrivi.tunnus), 0) jtrivi
                    FROM tilausrivin_lisatiedot
                    JOIN tilausrivi ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus)
                    WHERE tilausrivin_lisatiedot.yhtio          = '$kukarow[yhtio]'
                    AND tilausrivin_lisatiedot.tilausrivilinkki = '$tilrivirow[tunnus]'
                    $suoralisa";
          $varastoon_result = pupe_query($query);

          while ($varastoon_row = mysql_fetch_assoc($varastoon_result)) {
            // Mitk‰ suoratoimitukset valmistettiin t‰ll‰ keikalla
            $jtrivit[$varastoon_row["jtrivi"]] = $varastoon_row["jtrivi"];
            // Katotaan mille paikalle n‰‰ meni, jotta suoratoimitus voidaan laukasta t‰lt‰ paikalta
            $jtrivit_paikat[$varastoon_row["jtrivi"]] = $tapahtumaid;
          }
        }

        $varastosta = array(kuuluukovarastoon($tilrivirow["hyllyalue"], $tilrivirow["hyllynro"]));

        // parametri: automaattinen_jt_toimitus_valmistus
        // ''  = Valmistusrivej‰ ei toimiteta eik‰ kohdisteta automaattisesti
        // 'K' = J‰lkitoimitusrivit kohdistetaan automaattisesti valmistuksen yhteydess‰
        // 'J' = J‰lkitoimitusrivit kohdistetaan ja toimitetaan automaattisesti valmistuksen yhteydess‰
        // 'T' = J‰lkitoimitusrivit kohdistetaan ja toimitetaan automaattisesti valmistuksen yhteydess‰, jos k‰ytt‰j‰ll‰ on p‰ivitysoikeus JT-selaukseen
        // 'S' = Ainoastaan asiakkaalle valmistetut j‰lkitoimitusrivit kohdistetaan ja siirret‰‰n tulostusjonoon automaattisesti valmistuksen yhteydess‰
        // 'V' = Asiakkaalle sek‰ myyntitilaukselta varastoon valmistetut j‰lkitoimitusrivit kohdistetaan asiakkaalle ja toimitetaan automaattisesti valmistuksen yhteydess‰
        $param        = $yhtiorow["automaattinen_jt_toimitus_valmistus"];
        $kohdistetaan = in_array($param, array('K', 'J'));
        $toimitetaan  = ($param == 'J');

        // T parametrin poikkeukset
        if ($param == 'T') {
          $kayttajalla_oikeus = ($jtoikeudetrow["paivitys"] == 1);

          $kohdistetaan = $kayttajalla_oikeus;
          $toimitetaan  = $kayttajalla_oikeus;
        }

        // S ja V parametrin poikkeukset, oikeantyyppiset rivitlinkit katsotaan parametrin mukaan ylemp‰n‰
        if (in_array($param, array('S', 'V'))) {
          $riveja_linkattu = (count($jtrivit) > 0);

          $kohdistetaan = $riveja_linkattu;
          $toimitetaan  = $riveja_linkattu;
        }

        // Kohdistetaan JT rivit
        if ($kohdistetaan) {
          // Toimitetaan vain valmistettua is‰tuotetta, ei KAIKKIA firman j‰lkitoimitusrivej‰
          jt_toimita("", "", $varastosta, $jtrivit, $jtrivit_paikat, "tosi_automaaginen", "JATKA", "", $isatuoteno, '', "TULLAAN_VALMISTUKSESTA");
        }

        // Toimitetaan JT rivit
        if ($toimitetaan) {
          jt_toimita("", "", "", "", "", "dummy", "TOIMITA");
        }
      }
    }
  }

  // Tehd‰‰n uusi tuote $uusiarvo on k‰ytettyjen raaka-aineiden arvo.
  if ($useavalmiste == "ON") {

    // Katsotaan eka kuinka monta eri valmistetta syntyy
    $kaikenkaikkiaan = 1;

    // Katsotaan eka kanssa valmisteiden nykyinen varastonarvo
    if ($valmisterow["kehahin"]  > 0) {
      $kaikenkaikkiaan_arvot = $isavarattu * $valmisterow["kehahin"];
    }
    else {
      unset($kaikenkaikkiaan_arvot);
    }

    while ($uvrow = mysql_fetch_assoc($uvresult)) {
      $kaikenkaikkiaan++;

      if (isset($kaikenkaikkiaan_arvot)) {
        if ($uvrow["kehahin"] == 0) {
          // Jos yksikin valmiste on nollakehalla, niin jyvitet‰‰n $uusiarvo valmisteille vain kappalem‰‰rien mukaan
          unset($kaikenkaikkiaan_arvot);
        }
        else {
          $kaikenkaikkiaan_arvot += ($uvrow["varattu"]*$uvrow["kehahin"]);
        }
      }
    }

    mysql_data_seek($uvresult, 0);

    while ($uvrow = mysql_fetch_assoc($uvresult)) {

      // k‰ytet‰‰n rivill‰ olevaa paikkaa
      $query = "SELECT tuotepaikat.saldo, tuote.ei_saldoa, tuote.yksikko, tuote.kehahin, tuotepaikat.tunnus tunnus, tuote.sarjanumeroseuranta,
                tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
                FROM tuote
                JOIN tuotepaikat ON (tuote.yhtio=tuotepaikat.yhtio and tuote.tuoteno=tuotepaikat.tuoteno)
                WHERE tuote.yhtio         = '$kukarow[yhtio]'
                and tuote.tuoteno         = '$uvrow[tuoteno]'
                and tuotepaikat.hyllyalue = '$uvrow[hyllyalue]'
                and tuotepaikat.hyllynro  = '$uvrow[hyllynro]'
                and tuotepaikat.hyllytaso = '$uvrow[hyllytaso]'
                and tuotepaikat.hyllyvali = '$uvrow[hyllyvali]'";
      $tuoteresult = pupe_query($query);

      if (mysql_num_rows($tuoteresult) != 1) {
        //jos ei lˆytynyt yksiselitteist‰ rivi‰ yritet‰‰n oletuspaikkaa
        $query = "SELECT tuotepaikat.saldo, tuote.ei_saldoa, tuote.yksikko, tuote.kehahin, tuotepaikat.tunnus tunnus, tuote.sarjanumeroseuranta,
                  tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
                  FROM tuote
                  JOIN tuotepaikat ON (tuote.yhtio=tuotepaikat.yhtio and tuote.tuoteno=tuotepaikat.tuoteno)
                  WHERE tuote.yhtio       = '$kukarow[yhtio]'
                  and tuote.tuoteno       = '$uvrow[tuoteno]'
                  and tuotepaikat.oletus != ''
                  LIMIT 1";
        $tuoteresult = pupe_query($query);
      }

      if (mysql_num_rows($tuoteresult) != 1) {
        //jos ei lˆytynyt yksiselitteist‰ rivi‰ yritet‰‰n mit‰ tahansa paikkaa
        $query = "SELECT tuotepaikat.saldo, tuote.ei_saldoa, tuote.yksikko, tuote.kehahin, tuotepaikat.tunnus tunnus, tuote.sarjanumeroseuranta,
                  tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
                  FROM tuote
                  JOIN tuotepaikat ON (tuote.yhtio=tuotepaikat.yhtio and tuote.tuoteno=tuotepaikat.tuoteno)
                  WHERE tuote.yhtio = '$kukarow[yhtio]'
                  and tuote.tuoteno = '$uvrow[tuoteno]'
                  LIMIT 1";
        $tuoteresult = pupe_query($query);
      }

      if (mysql_num_rows($tuoteresult) != 1) {
        echo "<font class='error'>$uvrow[tuoteno] ".t("Varastopaikkaa ei lˆydy")."</font><br>";
      }
      else {

        // Valmisteen kokonaissaldo
        $query = "SELECT sum(saldo) kokonaissaldo
                  FROM tuotepaikat
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tuoteno = '$uvrow[tuoteno]'";
        $saldores = pupe_query($query);
        $saldorow = mysql_fetch_assoc($saldores);

        $uvisarow = mysql_fetch_assoc($tuoteresult);
        $uvisarow['kokonaissaldo'] = $saldorow['kokonaissaldo'];

        // P‰ivitet‰‰n sarjanumerolle ja riville lˆytynyt paikka
        $query = "UPDATE tilausrivi SET
                  hyllyalue   = '$uvisarow[hyllyalue]',
                  hyllynro    = '$uvisarow[hyllynro]',
                  hyllytaso   = '$uvisarow[hyllytaso]',
                  hyllyvali   = '$uvisarow[hyllyvali]'
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$uvrow[tunnus]'";
        $lisa_res = pupe_query($query);

        if ($uvisarow['sarjanumeroseuranta'] != "") {
          $query = "UPDATE sarjanumeroseuranta SET
                    hyllyalue            = '$uvisarow[hyllyalue]',
                    hyllynro             = '$uvisarow[hyllynro]',
                    hyllytaso            = '$uvisarow[hyllytaso]',
                    hyllyvali            = '$uvisarow[hyllyvali]'
                    WHERE yhtio          = '$kukarow[yhtio]'
                    and myyntirivitunnus = '$uvrow[tunnus]'";
          $lisa_res = pupe_query($query);
        }

        valmista_isa($uvisarow, $uvrow, $uvrow["varattu"], $uvrow["tuoteno"], $uusiarvo, $kaikenkaikkiaan, $kaikenkaikkiaan_arvot);
      }
    }
  }
  else {
    valmista_isa($valmisterow, $tilrivirow, $isavarattu, $isatuoteno, $uusiarvo, 1, '');
  }

  echo "<br>";
}
