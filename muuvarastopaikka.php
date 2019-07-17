<?php

if (strpos($_SERVER['SCRIPT_NAME'], "muuvarastopaikka.php")  !== FALSE) {
  require "inc/parametrit.inc";
}

if (php_sapi_name() != 'cli') {
  // Enaboidaan ajax kikkare
  enable_ajax();
}

if ($tee != '') {
  $query  = "LOCK TABLE tuotepaikat WRITE,
             tuotteen_toimittajat READ,
             toimi READ,
             tiliointi WRITE,
             tapahtuma WRITE,
             sanakirja WRITE,
             tilausrivin_lisatiedot WRITE,
             tuote READ,
             varastopaikat READ,
             varaston_hyllypaikat READ,
             tilausrivi WRITE,
             tilausrivi as t WRITE,
             tilausrivi as tilausrivi_osto READ,
             tilausrivin_lisatiedot AS tt WRITE,
             sarjanumeroseuranta WRITE,
             sarjanumeroseuranta_arvomuutos READ,
             lasku WRITE,
             asiakas READ,
             avainsana as a1 READ,
             avainsana as a2 READ,
             avainsana as a3 READ,
             avainsana as a4 READ,
             varastopaikat as v READ,
             yhtion_toimipaikat AS yt READ,
             yhtio READ,
             yhtion_parametrit READ,
             yhtion_toimipaikat READ,
             yhtion_toimipaikat_parametrit READ";
  $result = pupe_query($query);
}
else {

  if ($livesearch_tee == "TUOTEHAKU") {
    livesearch_tuotehaku();
    exit;
  }
}

if (!isset($lopetus)) $lopetus = "";

if (strtolower($toim) == 'oletusvarasto' and $kukarow['oletus_varasto'] != '' and $kukarow['oletus_varasto'] != 0) {
  $oletusvarasto_chk = $kukarow['oletus_varasto'];
}
else {
  $oletusvarasto_chk = '';
}

if (strpos($_SERVER['SCRIPT_NAME'], "muuvarastopaikka.php")  !== FALSE) {
  echo "<font class='head'>".t("Tuotteen varastopaikat")."</font><hr>";
}

// Seuraava ja edellinen napit
if ($tee == 'S' or $tee == 'E') {
  if ($tee == 'S') {
    $oper = '>';
    $suun = '';
  }
  else {
    $oper = '<';
    $suun = 'desc';
  }

  $query = "SELECT tuote.tuoteno, sum(saldo) saldo, status
            FROM tuote
            LEFT JOIN tuotepaikat ON tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.yhtio=tuote.yhtio
            WHERE tuote.yhtio = '$kukarow[yhtio]'
            and tuote.tuoteno " . $oper . " '$tuoteno'
            GROUP BY tuote.tuoteno
            HAVING status NOT IN ('P','X') or saldo > 0
            ORDER BY tuote.tuoteno " . $suun . "
            LIMIT 1";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    $trow = mysql_fetch_array($result);
    $tuoteno   = $trow['tuoteno'];
    $tee    ='M';
  }
  else {
    $varaosavirhe = t("Yht‰‰n tuotetta ei lˆytynyt")."!";
    $tuoteno   = '';
    $tee    ='Y';
  }
}

// Itse varastopaikkoja muutellaan
if ($tee == 'MUUTA' and $toim != "VAINSIIRTO") {
  $query = "SELECT *
            FROM tuotepaikat
            WHERE tuoteno  = '$tuoteno'
            and yhtio      = '$kukarow[yhtio]'
            and oletus    != ''";
  $oletus_result = pupe_query($query);
  $oletusrow = mysql_fetch_array($oletus_result);

  // Saldot per varastopaikka
  $query = "SELECT tuotepaikat.*,
            varastopaikat.nimitys, if (varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi,
            concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta
             FROM tuotepaikat
            LEFT JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
              AND varastopaikat.tunnus = tuotepaikat.varasto)
            WHERE tuotepaikat.yhtio    = '$kukarow[yhtio]'
            and tuotepaikat.tuoteno    = '$tuoteno'
            ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
  $paikatresult1 = pupe_query($query);

  $saldot = array();

  if (mysql_num_rows($paikatresult1) > 0) {
    while ($saldorow = mysql_fetch_array($paikatresult1)) {
      $saldot[$saldorow["tunnus"]]   = $saldorow["saldo"];
      $hyllyalue[$saldorow["tunnus"]] = $saldorow["hyllyalue"];
      $hyllynro[$saldorow["tunnus"]]   = $saldorow["hyllynro"];
      $hyllyvali[$saldorow["tunnus"]] = $saldorow["hyllyvali"];
      $hyllytaso[$saldorow["tunnus"]] = $saldorow["hyllytaso"];
    }
  }

  //Kun tuotteen statusta muutetaa, poistetaan tuotteelta myˆs automaattisesti perustetut tuotepaikat, jos tuotteelle ei ole perustettu manuaalisesti yht‰‰n paikkaa.
  //Haetaan edellisen tuote statuksen oletuspaikat
  if ($kutsuja == 'tuotetarkista.inc') {
    $kaikki_oletuspaikat = hae_kaikki_oletuspaikat_try_tai_status($tem_try_vanha, $tem_status_vanha);

    foreach ($poista as $poistettava) {
      //Jos poistettavaa paikkaa ei lˆydy kaikista_oletuspaikoista,
      //tarkoittaa se, ett‰ kyseess‰ on manuaalisesti lis‰tty tuotepaikka.
      //T‰llˆin mit‰‰n paikkaa ei voida poistaa.
      $oletuspaikka = in_array($poistettava['hylly'], $kaikki_oletuspaikat);

      if (!$oletuspaikka) {
        $poista = array();
        break;
      }
    }
  }

  // K‰yd‰‰n l‰pi poistettavat paikat
  if (count($poista) > 0) {
    foreach ($poista as $poistettava) {
      if (is_array($poistettava)) {
        $poistetaan = $poistettava['tunnus'];
      }
      else {
        $poistetaan = $poistettava;
      }

      if ($poistetaan == $oletusrow["tunnus"] and ($saldot[$poistetaan] != 0 or count($saldot) > 1)) {
        echo "<font class='error'>".t("Et voi poistaa oletuspaikkaa, koska sill‰ on saldoa tai tuotteella on muitakin paikkoja")."</font><br><br>";
      }
      elseif ($saldot[$poistetaan] != 0) {
        echo "<font class='error'>".t("Et voi poistaa paikkaa jolla on saldoa")."</font><br><br>";
      }
      else {
        if ($hyllyalue[$poistetaan] == "!!M") {
          $asiakkaan_tunnus = (int) $hyllynro[$poistetaan].$hyllyvali[$poistetaan].$hyllytaso[$poistetaan];
          $query = "SELECT if(nimi = toim_nimi OR toim_nimi = '', nimi, concat(nimi, ' / ', toim_nimi)) asiakkaan_nimi
                    FROM asiakas
                    WHERE yhtio = '{$kukarow["yhtio"]}'
                    AND tunnus  = '$asiakkaan_tunnus'";
          $asiakasresult = pupe_query($query);
          $asiakasrow = mysql_fetch_assoc($asiakasresult);
          $poisto_texti = t("Poistettiin myyntitili-varastopaikka")." ".$asiakasrow["asiakkaan_nimi"];
        }
        else {
          $poisto_texti = t("Poistettiin varastopaikka")." $hyllyalue[$poistetaan] $hyllynro[$poistetaan] $hyllyvali[$poistetaan] $hyllytaso[$poistetaan]<br/>";
        }

        if ($kutsuja != "vastaanota.php" and $kutsuja != 'tuotetarkista.inc') {
          echo "<font class='message'>$poisto_texti</font>";
        }

        // Tarkistetaan onko paikalla avoimia JT-rivej‰
        // ja p‰ivitet‰‰n avoimet JT-rivit toiselle paikalle, mik‰li niit‰ lˆytyy
        $query = "SELECT tilausrivi.varasto,
                  tilausrivi.tunnus
                  FROM tilausrivi
                  WHERE tilausrivi.yhtio   = '{$kukarow['yhtio']}'
                  AND tilausrivi.tyyppi    = 'L'
                  AND tilausrivi.var       = 'J'
                  AND tilausrivi.hyllyalue = '{$hyllyalue[$poistetaan]}'
                  AND tilausrivi.hyllynro  = '{$hyllynro[$poistetaan]}'
                  AND tilausrivi.hyllyvali = '{$hyllyvali[$poistetaan]}'
                  AND tilausrivi.hyllytaso = '{$hyllytaso[$poistetaan]}'
                  AND tilausrivi.tuoteno   = '{$tuoteno}'";
        $rivires = pupe_query($query);

        while ($jtrivi = mysql_fetch_assoc($rivires)) {
          // Haetaan ensin nykyisen paikan tunnus,
          // jotta voidaan helposti varmistaa ettei olla laittamassa takaisin samalle paikalle
          $query = "SELECT tunnus
                    FROM tuotepaikat
                    WHERE yhtio   = '{$kukarow['yhtio']}'
                    AND varasto   = '{$jtrivi['varasto']}'
                    AND hyllyalue = '{$hyllyalue[$poistetaan]}'
                    AND hyllynro  = '{$hyllynro[$poistetaan]}'
                    AND hyllyvali = '{$hyllyvali[$poistetaan]}'
                    AND hyllytaso = '{$hyllytaso[$poistetaan]}'";
          $nykyvarasto_tunnus = mysql_fetch_assoc(pupe_query($query));

          // Laitetaan ensisijaisesti avoin JT-rivi saman varaston vanhimmalle paikalle
          $query = "SELECT varasto,
                    hyllyalue,
                    hyllynro,
                    hyllyvali,
                    hyllytaso
                    FROM tuotepaikat
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND varasto = '{$jtrivi["varasto"]}'
                    AND tunnus != '{$nykyvarasto_tunnus["tunnus"]}'
                    AND tuoteno   = '{$tuoteno}'
                    ORDER BY tunnus";
          $uusivarasto_res = pupe_query($query);

          // Jos samasta varastosta ei paikkaa lˆydy, niin laitetaan JT-rivi oletuspaikalle
          if (mysql_num_rows($uusivarasto_res) == 0) {
            $query = "SELECT varasto,
                      hyllyalue,
                      hyllynro,
                      hyllyvali,
                      hyllytaso
                      FROM tuotepaikat
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND oletus != ''
                      AND tuoteno = '{$tuoteno}'";
            $uusivarasto_res = pupe_query($query);
          }

          // P‰ivitet‰‰n uusi paikka avoimelle JT-riville
          if ($uusivarasto = mysql_fetch_assoc($uusivarasto_res)) {
            $query = "UPDATE tilausrivi
                      SET varasto = '{$uusivarasto["varasto"]}',
                      hyllyalue   = '{$uusivarasto["hyllyalue"]}',
                      hyllynro    = '{$uusivarasto["hyllynro"]}',
                      hyllyvali   = '{$uusivarasto["hyllyvali"]}',
                      hyllytaso   = '{$uusivarasto["hyllytaso"]}'
                      WHERE yhtio = '{$kukarow["yhtio"]}'
                      AND tunnus  = '{$jtrivi["tunnus"]}'";
            pupe_query($query);

          }

        }

        $query = "INSERT into tapahtuma set
                  yhtio     = '$kukarow[yhtio]',
                  tuoteno   = '$tuoteno',
                  kpl       = '0',
                  kplhinta  = '0',
                  hinta     = '0',
                  laji      = 'poistettupaikka',
                  hyllyalue = '$hyllyalue[$poistetaan]',
                  hyllynro  = '$hyllynro[$poistetaan]',
                  hyllyvali = '$hyllyvali[$poistetaan]',
                  hyllytaso = '$hyllytaso[$poistetaan]',
                  selite    = '$poisto_texti',
                  laatija   = '$kukarow[kuka]',
                  laadittu  = now()";
        pupe_query($query);

        $query = "DELETE FROM tuotepaikat
                  WHERE tuoteno = '$tuoteno'
                  and yhtio     = '$kukarow[yhtio]'
                  and tunnus    = '$poistetaan'";
        pupe_query($query);

        unset($saldot[$poistetaan]);
      }
    }
    echo "<br/>";
  }

  if (count($flagaa_poistettavaksi_undo) > 0) {
    foreach ($flagaa_poistettavaksi_undo as $poistetaan => $undoataan) {

      if ($undoataan != "" and !isset($flagaa_poistettavaksi[$poistetaan])) {
        $query = "UPDATE tuotepaikat
                  SET poistettava = ''
                  WHERE tuoteno = '$tuoteno'
                  and yhtio     = '$kukarow[yhtio]'
                  and tunnus    = '$poistetaan'";
        pupe_query($query);
      }
    }
  }

  if (count($flagaa_poistettavaksi) > 0) {
    foreach ($flagaa_poistettavaksi as $poistetaan) {
      $query = "UPDATE tuotepaikat
                SET poistettava = 'D'
                WHERE tuoteno = '$tuoteno'
                and yhtio     = '$kukarow[yhtio]'
                and tunnus    = '$poistetaan'";
      pupe_query($query);
    }
  }

  // Oletuspaikka vaihdettiin
  if (isset($oletus) and $oletus != $oletusrow["tunnus"]) {
    $query = "SELECT *
              FROM tuotepaikat
              WHERE tuoteno = '$tuoteno'
              and yhtio     = '$kukarow[yhtio]'
              and tunnus    = '$oletus'";
    $oletus_result = pupe_query($query);

    if (mysql_num_rows($oletus_result) == 1) {

      $uusi_oletusrow = mysql_fetch_assoc($oletus_result);

      // Tehd‰‰n p‰ivitykset
      echo "<font class='message'>".t("Siirret‰‰n oletuspaikka")."</font><br><br>";

      $hylly = array(
        "hyllyalue" => $uusi_oletusrow['hyllyalue'],
        "hyllynro" => $uusi_oletusrow['hyllynro'],
        "hyllytaso" => $uusi_oletusrow['hyllytaso'],
        "hyllyvali" => $uusi_oletusrow['hyllyvali']
      );
      $upd_result = paivita_oletuspaikka($tuoteno, $hylly);

      if ($upd_result["paivitetyt_ostorivit"] > 0) {
        echo "<font class='message'>".t("P‰ivitettiin %s ostotilausrivin varastopaikkaa.", '', $upd_result["paivitetyt_ostorivit"])."</font><br><br>";
      }
    }
    else {
      echo "<font class='error'>".t("Uusi oletuspaikka on kadonnut")."</font><br><br>";
    }
  }

  if (count($halyraja2) > 0) {
    foreach ($halyraja2 as $tunnus => $halyraja) {
      $query = "UPDATE tuotepaikat
                SET halytysraja = '$halyraja',
                muuttaja    = '$kukarow[kuka]',
                muutospvm   = now()
                WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tunnus'";
      pupe_query($query);
    }
  }

  if (count($tilausmaara2) > 0) {
    foreach ($tilausmaara2 as $tunnus => $tilausmaara) {
      $query = "UPDATE tuotepaikat
                SET tilausmaara = '$tilausmaara',
                muuttaja    = '$kukarow[kuka]',
                muutospvm   = now()
                WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tunnus'";
      pupe_query($query);
    }
  }

  if (count($prio2) > 0) {
    foreach ($prio2 as $tunnus => $prio) {
      $query = "UPDATE tuotepaikat
                SET prio = '{$prio}',
                muuttaja    = '{$kukarow['kuka']}',
                muutospvm   = now()
                WHERE yhtio = '{$kukarow['yhtio']}' and tunnus = '{$tunnus}'";
      pupe_query($query);
    }
  }

  $ahyllyalue  = '';
  $ahyllynro  = '';
  $ahyllyvali  = '';
  $ahyllytaso  = '';

  if ($kutsuja == "vastaanota.php" or $kutsuja == "tuotetarkista.inc" ) {
    $tee = "OK";
  }
  else {
    $tee = 'M';
  }

  if ($kutsuja == "varastopaikka_aineistolla.php") $tee = 'MEGALOMAANINEN_ONNISTUMINEN';
}

// Siirret‰‰n saldo, jos se on viel‰ olemassa
if ($tee == 'N') {
  if ($mista == $minne) {
    echo "<font class='error'>".t("Kummatkin paikat ovat samat")."!</font><br><br>";

    if ($kutsuja == 'vastaanota.php') {
      $tee = 'X';
    }
    else {
      $tee = 'M';
    }
  }
  $asaldo = (float) str_replace( ",", ".", $asaldo);

  if ($asaldo == 0) {
    echo "<font class='error'>".t("Anna siirrett‰v‰ m‰‰r‰")."!</font><br><br>";

    if ($kutsuja == 'vastaanota.php') {
      $tee = 'X';
    }
    else {
      $tee = 'M';
    }
  }
  if ($kutsuja == "varastopaikka_aineistolla.php") $tee = 'N';
}

if ($tee == 'N') {

  // Tarvitsemme
  // $asaldo  = siirrett‰v‰ m‰‰r‰
  // $mista   = tuotepaikan tunnus josta otetaan
  // $minne   = tuotepaikan tunnus jonne siirret‰‰n
  // $tuoteno = tuotenumero jota siirret‰‰n

  if ($kutsuja == "vastaanota.php") {
    $uusitee = "X";
  }
  else {
    $uusitee = "M";
  }

  $tuotteet    = array(0 => $tuoteno);
  $kappaleet    = array(0 => $asaldo);
  $lisavaruste = array(0 => "");
  $otetaan   = array(0 => $mista);
  $siirretaan   = array(0 => $minne);

  // Mist‰ varastosta otetaan?
  $query = "SELECT *
            FROM tuotepaikat
            WHERE tuoteno = '$tuoteno' and yhtio = '$kukarow[yhtio]' and tunnus='$otetaan[0]'";
  $result = pupe_query($query);
  $mistarow = mysql_fetch_array($result);

  if (mysql_num_rows($result) == 0) {
    echo "<font class='error'>".t("T‰m‰ varastopaikka katosi tuotteelta")." $otetaan[0]</font><br><br>";
    $tee = $uusitee;
  }

  // Minne varastoon vied‰‰n?
  $query = "SELECT *
            FROM tuotepaikat
            WHERE tuoteno = '$tuoteno' and yhtio = '$kukarow[yhtio]' and tunnus='$siirretaan[0]'";
  $result = pupe_query($query);
  $minnerow = mysql_fetch_array($result);

  if (mysql_num_rows($result) == 0) {
    echo "<font class='error'>".t("T‰m‰ varastopaikka katosi tuotteelta")." $siirretaan[0]</font><br><br>";
    $tee = $uusitee;
  }

  // Tarkistetaan sarjanumeroseuranta
  // S = Sarjanumeroseuranta. Osto-Myynti / In-Out varastonarvo
  // T = Sarjanumeroseuranta. Myynti / Keskihinta-varastonarvo
  // V = Sarjanumeroseuranta. Osto-Myynti / Keskihinta-varastonarvo
  // E = Er‰numeroseuranta. Osto-Myynti / Keskihinta-varastonarvo
  // F = Er‰numeroseuranta parasta-ennen p‰iv‰ll‰. Osto-Myynti / Keskihinta-varastonarvo
  // G = Er‰numeroseuranta. Osto-Myynti / In-Out varastonarvo
  $query = "SELECT sum(if(tuote.sarjanumeroseuranta in ('S','T','V'), 1, 0)) sarjat,
            sum(if(tuote.sarjanumeroseuranta in ('E','F','G'), 1, 0)) erat
            FROM tuote
            WHERE tuoteno            = '$tuoteno'
            and yhtio                = '$kukarow[yhtio]'
            and sarjanumeroseuranta != ''";
  $sarjaresult = pupe_query($query);
  $sarjacheck_row = mysql_fetch_array($sarjaresult);

  if ($sarjacheck_row["sarjat"] > 0 and (!is_array($sarjano_array) or count($sarjano_array) != $asaldo)) {
    echo "<font class='error'>".t("Tarkista sarjanumerovalintasi")."</font><br><br>";
    $tee = $uusitee;
  }
  elseif ($sarjacheck_row["erat"] > 0) {

    $query = "SELECT *
              FROM sarjanumeroseuranta
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  = '$sarjano_array[0]'";
    $siirrettava_era_res = pupe_query($query);
    $siirrettava_era_row = mysql_fetch_assoc($siirrettava_era_res);

    if (!is_array($sarjano_array) or $sarjano_kpl_array[$sarjano_array[0]] < $asaldo) {
      echo "<font class='error'>".t("Tarkista er‰numerovalintasi")."</font><br><br>";
      $tee = $uusitee;
    }

    if ($siirrettava_era_row['hyllyalue'] != $mistarow['hyllyalue'] or $siirrettava_era_row['hyllynro'] != $mistarow['hyllynro'] or $siirrettava_era_row['hyllyvali'] != $mistarow['hyllyvali'] or $siirrettava_era_row['hyllytaso'] != $mistarow['hyllytaso']) {
      echo "<font class='error'>", t("Siirrett‰v‰ er‰ ei ole l‰hdevarastossa"), "!</font><br><br>";
      $tee = $uusitee;
    }
  }
  elseif ($sarjacheck_row["sarjat"] > 0) {

    foreach ($sarjano_array as $sarjatun) {
      //Tutkitaan lis‰varusteita
      $query = "SELECT tilausrivi_osto.perheid2
                FROM sarjanumeroseuranta
                JOIN tilausrivi tilausrivi_osto use index (PRIMARY) ON tilausrivi_osto.yhtio = sarjanumeroseuranta.yhtio and tilausrivi_osto.tunnus = sarjanumeroseuranta.ostorivitunnus and tilausrivi_osto.tunnus = tilausrivi_osto.perheid2
                WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
                and sarjanumeroseuranta.tunnus  = '$sarjatun'";
      $sarjares = pupe_query($query);

      if (mysql_num_rows($sarjares) == 1) {
        $sarjarow = mysql_fetch_array($sarjares);

        // Ostorivi
        $query = "SELECT if (kpl != 0, kpl, tilkpl) kpl
                  FROM tilausrivi
                  WHERE yhtio   = '$kukarow[yhtio]'
                  and tunnus    = '$sarjarow[perheid2]'
                  and perheid2 != 0";
        $sarjares = pupe_query($query);
        $ostorow = mysql_fetch_array($sarjares);

        // Haetaan muut lis‰varusteet
        $query = "SELECT tilausrivi.tuoteno, round(tilausrivi.kpl/$ostorow[kpl]) kpl, round(tilausrivi.tilkpl/$ostorow[kpl]) tilkpl, tilausrivi.tilkpl sistyomaarayskpl,
                  tilausrivi.var, tilausrivi.tyyppi, concat_ws('#', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso) paikka,
                  if (tuote.sarjanumeroseuranta = 'S', tilausrivin_lisatiedot.sistyomaarays_sarjatunnus, 0) sarjatunnus
                  FROM tilausrivi
                  JOIN tuote ON tilausrivi.tuoteno=tuote.tuoteno and tilausrivi.yhtio=tuote.yhtio
                  LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
                  WHERE tilausrivi.yhtio   = '$kukarow[yhtio]'
                  and tilausrivi.perheid2  = '$sarjarow[perheid2]'
                  and tilausrivi.tyyppi   != 'D'
                  and tilausrivi.tunnus   != '$sarjarow[perheid2]'
                  and tilausrivi.perheid2 != 0";
        $sarjares = pupe_query($query);

        while ($lisavarrow = mysql_fetch_array($sarjares)) {
          if ($lisavarrow["tyyppi"] == 'G' and $lisavarrow["sistyomaarayskpl"] > 0) {
            $tuotteet[]     = $lisavarrow["tuoteno"];
            $kappaleet[]   = $lisavarrow["sistyomaarayskpl"];
            $lisavaruste[]  = "LISAVARUSTE";
            $lisavar_sarj[]  = $lisavarrow["sarjatunnus"];
          }
          elseif ($lisavarrow["var"] != 'P' and $lisavarrow["kpl"] > 0) {
            $tuotteet[]     = $lisavarrow["tuoteno"];
            $kappaleet[]   = $lisavarrow["kpl"];
            $lisavaruste[]  = "LISAVARUSTE";
            $lisavar_sarj[]  = $lisavarrow["sarjatunnus"];
          }
        }
      }
    }
  }

  //t‰h‰n erroriin tullaan vain jos kyseess‰ ei ole siirtolista
  //koska jos meill‰ on ker‰tty siirtolista niin ne m‰‰r‰t myˆs halutaan siirt‰‰ vaikka saldo menisikin nollille tai miinukselle
  $saldook = 0;

  for ($iii=0; $iii< count($tuotteet); $iii++) {
    //siirret‰‰nkˆ varattua saldoa
    $siirretaan_varattua = false;

    // Tutkitaan lis‰varusteiden tuotepaikkoja
    $query = "SELECT *
              FROM tuotepaikat
              WHERE tuoteno = '$tuotteet[$iii]'
              and yhtio     = '$kukarow[yhtio]'
              and hyllyalue = '$mistarow[hyllyalue]'
              and hyllynro  = '$mistarow[hyllynro]'
              and hyllyvali = '$mistarow[hyllyvali]'
              and hyllytaso = '$mistarow[hyllytaso]'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 1) {
      list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($tuotteet[$iii], 'JTSPEC', '', '', $mistarow["hyllyalue"], $mistarow["hyllynro"], $mistarow["hyllyvali"], $mistarow["hyllytaso"]);

      $lisavartprow = mysql_fetch_array($result);

      //Tutkitaan siirret‰‰nkˆ tietty‰ jo varattua lis‰varustetta
      if ($lisavaruste[$iii] == "LISAVARUSTE") {
        // Tuotepaikka josta lis‰varuste otetaan
        $otetaan[$iii] = $lisavartprow["tunnus"];

        $myytavissa += $kappaleet[$iii];
      }

      if ($kappaleet[$iii] == $hyllyssa and $myytavissa < $kappaleet[$iii] and strpos($_SERVER['SCRIPT_NAME'], "muuvarastopaikka.php") !== false) {
        $siirretaan_varattua = true;
      }
      elseif ($kappaleet[$iii] > $myytavissa and !in_array($kutsuja, array('varastopaikka_aineistolla.php', 'vastaanota.php'))) {
        echo "Tuotetta ei voida siirt‰‰. Saldo ei riitt‰nyt. $tuotteet[$iii] $kappaleet[$iii] ($mistarow[hyllyalue] $mistarow[hyllynro] $mistarow[hyllyvali] $mistarow[hyllytaso])<br>";
        $saldook++;
      }
    }
    elseif ($kutsuja != "vastaanota.php") {
      echo t("Tuotetta ei voida siirt‰‰. Tuotetta ei lˆytynyt paikalta").": $tuotteet[$iii] ($mistarow[hyllyalue] $mistarow[hyllynro] $mistarow[hyllyvali] $mistarow[hyllytaso])<br>";
      $saldook++;
    }
  }

  if ($saldook > 0 and ($kappaleet[$iii] < $myytavissa or $kappaleet[$iii] < $hyllyssa)) {
    echo "<font class='error'>".t("Voit siirt‰‰ vain myyt‰viss‰ olevaa m‰‰r‰‰ tai koko hyllyss‰ olevan m‰‰r‰n")."</font><br><br>";
    $tee = $uusitee;
  }
  elseif ($saldook > 0) { //Taravat myytiin alta!
    echo "<font class='error'>".t("Siirett‰v‰ m‰‰r‰ on liian iso")."</font><br><br>";
    $tee = $uusitee;
  }

  // Varmistetaan, ett‰ vastaanottavat paikat lˆytyy
  if ($saldook == 0) {

    for ($iii=0; $iii< count($tuotteet); $iii++) {
      $query = "SELECT *
                FROM tuotepaikat
                WHERE tuoteno = '$tuotteet[$iii]'
                and yhtio     = '$kukarow[yhtio]'
                and hyllyalue = '$minnerow[hyllyalue]'
                and hyllynro  = '$minnerow[hyllynro]'
                and hyllyvali = '$minnerow[hyllyvali]'
                and hyllytaso = '$minnerow[hyllytaso]'";
      $result = pupe_query($query);

      // Vastaanottavaa paikkaa ei lˆydy, perustetaan se
      if (mysql_num_rows($result) == 0) {
        $lisatty_paikka = lisaa_tuotepaikka($tuotteet[$iii], $minnerow["hyllyalue"], $minnerow["hyllynro"], $minnerow["hyllyvali"], $minnerow["hyllytaso"], "Varastopaikkojen muutosessa", "", 0, 0, 0);

        // Tuotepaikka jonne lis‰varuste vied‰‰n
        if ($lisavaruste[$iii] == "LISAVARUSTE") {
          $siirretaan[$iii] = $lisatty_paikka["tuotepaikan_tunnus"];
        }

        echo t("Uusi varastopaikka luotiin tuotteelle").": $tuotteet[$iii] ($minnerow[hyllyalue]-$minnerow[hyllynro]-$minnerow[hyllyvali]-$minnerow[hyllytaso])<br>";
      }
      else {
        $lisavartprow = mysql_fetch_array($result);

        // Tuotepaikka jonne lis‰varuste vied‰‰n
        if ($lisavaruste[$iii] == "LISAVARUSTE") {
          $siirretaan[$iii] = $lisavartprow["tunnus"];
        }
      }

      // P‰ivitet‰‰n uusi paikka varatuille tilausriveille
      if ($siirretaan_varattua) {
        $query = "UPDATE tilausrivi
                  SET hyllyalue = '$minnerow[hyllyalue]',
                    hyllynro      = '$minnerow[hyllynro]',
                    hyllyvali     = '$minnerow[hyllyvali]',
                    hyllytaso     = '$minnerow[hyllytaso]'
                  WHERE tuoteno   = '$tuotteet[$iii]'
                    AND yhtio     = '$kukarow[yhtio]'
                    AND tyyppi    IN ('L','G','V')
                    AND varattu   <> 0
                    AND hyllyalue = '$mistarow[hyllyalue]'
                    AND hyllynro  = '$mistarow[hyllynro]'
                    AND hyllyvali = '$mistarow[hyllyvali]'
                    AND hyllytaso = '$mistarow[hyllytaso]'
                    AND keratty   = ''";
        pupe_query($query);
      }
    }
  }
  if ($kutsuja == "varastopaikka_aineistolla.php") $tee = 'N';
}

if ($tee == 'N') {

  if ($kutsuja == "vastaanota.php") {
    $uusitee = "OK";
  }
  else {
    $uusitee = "M";
  }

  if (!isset($_poikkeavalaskutuspvm)) $_poikkeavalaskutuspvm = "";
  if (!isset($kohdepaikasta_oletuspaikka)) $kohdepaikasta_oletuspaikka = "";

  for ($iii=0; $iii< count($tuotteet); $iii++) {

    $params = array(
      'kappaleet' => $kappaleet[$iii],
      'lisavaruste' => $lisavaruste[$iii],
      'tuoteno' => $tuotteet[$iii],
      'tuotepaikat_tunnus_otetaan' => $otetaan[$iii],
      'tuotepaikat_tunnus_siirretaan' => $siirretaan[$iii],
      'mistarow' => $mistarow,
      'minnerow' => $minnerow,
      'sarjano_array' => !isset($sarjano_array) ? array() : $sarjano_array,
      'selite' => !isset($selite) ? '' : $selite,
      'tun' => !isset($tun) ? 0 : $tun,
      'poikkeavalaskutuspvm' => $_poikkeavalaskutuspvm,
      'kohdepaikasta_oletuspaikka' => $kohdepaikasta_oletuspaikka,
    );

    hyllysiirto($params);

    if (strpos($_SERVER['SCRIPT_NAME'], "muuvarastopaikka.php")  !== FALSE) {
      echo "<br><font class='message'>".t("Tuotesiirto onnistui. Paikalle %s siirrettiin %s tuotetta", "", "$minnerow[hyllyalue]-$minnerow[hyllynro]-$minnerow[hyllyvali]-$minnerow[hyllytaso]", $kappaleet[$iii])."!</font><br><br>";
    }
  }

  //P‰ivitet‰‰n sarjanumerot
  if ($sarjacheck_row["sarjat"] > 0 and count($sarjano_array) > 0) {
    foreach ($sarjano_array as $sarjano) {
      if ($sarjano > 0) {
        $query = "UPDATE sarjanumeroseuranta
                  set hyllyalue  = '$minnerow[hyllyalue]',
                  hyllynro    = '$minnerow[hyllynro]',
                  hyllyvali   = '$minnerow[hyllyvali]',
                  hyllytaso   = '$minnerow[hyllytaso]',
                  muuttaja    = '$kukarow[kuka]',
                  muutospvm   = now()
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$sarjano'";
        $result = pupe_query($query);
      }
    }
  }
  elseif ($sarjacheck_row["erat"] > 0 and count($sarjano_array) > 0) {
    foreach ($sarjano_array as $sarjano) {
      if ($sarjano > 0) {
        $query = "SELECT *
                  FROM sarjanumeroseuranta
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND tunnus  = '$sarjano'";
        $sarrr_res = pupe_query($query);
        $sarrr_row = mysql_fetch_assoc($sarrr_res);

        $sarjaquerylisa = '';

        // jos er‰ loppuu, poistetaa sen n‰kyvyys
        if ($sarrr_row['era_kpl'] - $asaldo == 0) {
          $sarjaquerylisa = "myyntirivitunnus = '-1', siirtorivitunnus = '-1', ";
        }

        $query = "UPDATE sarjanumeroseuranta
                  set era_kpl    = era_kpl - $asaldo,
                  $sarjaquerylisa
                  muuttaja    = '$kukarow[kuka]',
                  muutospvm   = now()
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$sarjano'";
        $result = pupe_query($query);

        $query = "SELECT *
                  FROM sarjanumeroseuranta
                  WHERE yhtio           = '$kukarow[yhtio]'
                  AND tuoteno           = '$tuoteno'
                  AND tunnus           != '$sarjano'
                  AND sarjanumero       = '$sarrr_row[sarjanumero]'
                  AND hyllyalue         = '$minnerow[hyllyalue]'
                  AND hyllynro          = '$minnerow[hyllynro]'
                  AND hyllyvali         = '$minnerow[hyllyvali]'
                  AND hyllytaso         = '$minnerow[hyllytaso]'
                  AND myyntirivitunnus  = 0
                  AND era_kpl          != 0";
        $sarrr_res2 = pupe_query($query);

        if (mysql_num_rows($sarrr_res2) == 1) {
          $sarrr_row2 = mysql_fetch_assoc($sarrr_res2);

          $query = "UPDATE sarjanumeroseuranta
                    set era_kpl    = era_kpl + $asaldo,
                    muuttaja    = '$kukarow[kuka]',
                    muutospvm   = now()
                    WHERE yhtio = '$kukarow[yhtio]'
                    AND tunnus  = '$sarrr_row2[tunnus]'";
          $result = pupe_query($query);
        }
        else {
          $query = "INSERT INTO sarjanumeroseuranta SET
                    yhtio          = '$kukarow[yhtio]',
                    tuoteno        = '$tuoteno',
                    sarjanumero    = '$sarrr_row[sarjanumero]',
                    ostorivitunnus = '$sarrr_row[ostorivitunnus]',
                    era_kpl        = $asaldo,
                    takuu_alku     = '$sarrr_row[takuu_alku]',
                    takuu_loppu    = '$sarrr_row[takuu_loppu]',
                    parasta_ennen  = '$sarrr_row[parasta_ennen]',
                    hyllyalue      = '$minnerow[hyllyalue]',
                    hyllynro       = '$minnerow[hyllynro]',
                    hyllyvali      = '$minnerow[hyllyvali]',
                    hyllytaso      = '$minnerow[hyllytaso]',
                    muuttaja       = '$kukarow[kuka]',
                    muutospvm      = now(),
                    laatija        = '$kukarow[kuka]',
                    luontiaika     = now()";
          $result = pupe_query($query);
        }
      }
    }
  }


  // P‰ivitet‰‰n lis‰vausteiden sarjanumerot
  if (isset($lisavar_sarj) and count($lisavar_sarj) > 0) {
    foreach ($lisavar_sarj as $sarjano) {
      if ($sarjano > 0) {
        $query = "UPDATE sarjanumeroseuranta
                  set hyllyalue  = '$minnerow[hyllyalue]',
                  hyllynro    = '$minnerow[hyllynro]',
                  hyllyvali   = '$minnerow[hyllyvali]',
                  hyllytaso   = '$minnerow[hyllytaso]',
                  muuttaja    = '$kukarow[kuka]',
                  muutospvm   = now()
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$sarjano'";
        $result = pupe_query($query);
      }
    }
  }

  $ahyllyalue = '';
  $ahyllynro  = '';
  $ahyllyvali = '';
  $ahyllytaso = '';
  $asaldo     = '';
  $tee        = $uusitee;

  if ($kutsuja == "varastopaikka_aineistolla.php") $tee = 'MEGALOMAANINEN_ONNISTUMINEN';
}

// Uusi varstopaikka
if ($tee == 'UUSIPAIKKA' and $toim != "VAINSIIRTO") {

  $ahyllyalue = trim($ahyllyalue);
  $ahyllynro  = trim($ahyllynro);
  $ahyllyvali = trim($ahyllyvali);
  $ahyllytaso = trim($ahyllytaso);

  //Tarkistetaan onko paikka validi
  $query = "SELECT oletus
            FROM tuotepaikat
            WHERE yhtio   = '$kukarow[yhtio]'
            and tuoteno   = '$tuoteno'
            and hyllyalue = '$ahyllyalue'
            and hyllynro  = '$ahyllynro'
            and hyllytaso = '$ahyllytaso'
            and hyllyvali = '$ahyllyvali'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {

    $_mihin_varastoon = kuuluukovarastoon($ahyllyalue, $ahyllynro);

    if ($_mihin_varastoon != 0 and $ahyllyalue != '' and $ahyllynro != '' and $ahyllyvali != '' and $ahyllytaso != '' and $ahyllyalue != "!!M") {

      $kaikki_ok = true;

      if ($yhtiorow['toimipaikkakasittely'] == "L") {
        // Haetaan varaston toimipaikan parametrit
        $_var_tp = hae_varaston_toimipaikka($_mihin_varastoon);
        $_var_tp = (!empty($_var_tp) and is_array($_var_tp)) ? $_var_tp['tunnus'] : null;

        $yhtiorow_alkuperainen = $yhtiorow;
        $yhtiorow = hae_yhtion_parametrit($kukarow['yhtio'], $_var_tp);
      }

      if ($yhtiorow['kerayserat'] == 'K') {

        $ahyllyalue = strtoupper($ahyllyalue);
        $ahyllynro  = strtoupper($ahyllynro);
        $ahyllyvali = strtoupper($ahyllyvali);
        $ahyllytaso = strtoupper($ahyllytaso);

        $kaikki_ok = tarkista_varaston_hyllypaikka($ahyllyalue, $ahyllynro, $ahyllyvali, $ahyllytaso);
      }

      if ($yhtiorow['varastontunniste'] != '') {
        if (!isset($select_varastontunniste) or trim($select_varastontunniste) == "") $kaikki_ok = false;
      }

      // Palautetaan yhtiˆn parametrit
      if (!empty($yhtiorow_alkuperainen)) {
        $yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);
      }

      if ($kaikki_ok) {
        echo "<font class='message'>".("Uusi varastopaikka luotiin tuotteelle").": $tuoteno ($ahyllyalue-$ahyllynro-$ahyllyvali-$ahyllytaso)</font><br>";

        $query = "SELECT oletus
                  FROM tuotepaikat
                  WHERE yhtio  = '$kukarow[yhtio]'
                  and tuoteno  = '$tuoteno'
                  and oletus  != ''";
        $result = pupe_query($query);

        if (mysql_num_rows($result) > 0) {
          $oletus = "";
        }
        else {
          $oletus = "X";
        }

        if (!isset($ahalytysraja)) $ahalytysraja = 0;
        if (!isset($atilausmaara)) $atilausmaara = 0;

        $ahalytysraja = (float) $ahalytysraja;
        $atilausmaara = (float) $atilausmaara;

        lisaa_tuotepaikka($tuoteno, $ahyllyalue, $ahyllynro, $ahyllyvali, $ahyllytaso, "Varastopaikkojen muutoksessa", $oletus, $ahalytysraja, $atilausmaara, 0);
      }
      else {
        echo "<font class='error'>", ("Uusi varastopaikka ei lˆydy tai ei kuulu mihink‰‰n varastoon"), ": {$tuoteno} ({$ahyllyalue}, {$ahyllynro}, {$ahyllyvali}, {$ahyllytaso})</font><br />";
        $failure = "Y";
      }
    }
    else {
      echo "<font class='error'>".("Uusi varastopaikka ei kuulu mihink‰‰n varastoon").": $tuoteno ($ahyllyalue, $ahyllynro, $ahyllyvali, $ahyllytaso)</font><br>";
      $failure = "Y";
    }
  }
  else {
    echo "<font class='error'>".("Uusi varastopaikka lˆytyy jo tuotteelta").": $tuoteno ($ahyllyalue, $ahyllynro, $ahyllyvali, $ahyllytaso)</font><br>";
  }
  $tee = 'M';
  if ($kutsuja == "varastopaikka_aineistolla.php") $tee = "PALATTIIN_MUUSTA";
}

if ($tee != "") {
  $query  = "UNLOCK TABLES";
  $result = pupe_query($query);
}

if ($tee == 'M' or $tee == 'Q') {

  require "inc/tuotehaku.inc";

  if ($ulos != "") {
    $formi  = 'hakua';
    echo "<form method='post' name='$formi' autocomplete='off'>";
    echo "<input type='hidden' name='tee' value='Q'>";
    echo "<input type='hidden' name='tulostakappale' value='$tulostakappale'>";
    echo "<input type='hidden' name='kirjoitin' value='$kirjoitin'>";
    echo "<input type='hidden' name='toim' value='$toim'>";
    echo "<input type='hidden' name='malli' value='$malli'>";
    echo "<table><tr>";
    echo "<td>".t("Valitse listasta").":</td>";
    echo "<td>$ulos</td>";
    echo "<td class='back'><input type='submit' value='".t("Valitse")."'></td>";
    echo "</tr></table>";
    echo "</form>";

    $tee = 'Q';
  }
  elseif ($ulos == '' and $tee != 'Y') {
    $tee  = "M";
  }
}

if ($tee == 'Y') {
  echo "<font class='error'>$varaosavirhe</font>";
  $tee = '';
}

if ($tee == 'M') {

  echo "<table><tr>";

  //Jos ei haettu, annetaan 'edellinen' & 'seuraava'-nappi
  echo "<form method='post'>";
  echo "<input type='hidden' name='tee' value='E'>";
  echo "<input type='hidden' name='tyyppi' value='$tyyppi'>";
  echo "<input type = 'hidden' name = 'toim' value = '{$toim}' />";
  echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";

  echo "<th>$tuoteno - ".t_tuotteen_avainsanat($trow, 'nimitys')."</th>";
  echo "<td>";
  echo "<input type='submit' value='".t("Edellinen tuote")."'>";
  echo "</td>";
  echo "</form>";

  echo "<form method='post'>";
  echo "<input type='hidden' name='tyyppi' value='$tyyppi'>";
  echo "<input type='hidden' name='tee' value='S'>";
  echo "<input type = 'hidden' name = 'toim' value = '{$toim}' />";
  echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";
  echo "<td>";
  echo "<input type='submit' value='".t("Seuraava tuote")."'>";
  echo "</td>";
  echo "</form>";
  echo "</tr>";
  echo "</table><br>";

  echo "<table>";
  echo "<tr>";

  echo "  <form name = 'valinta' method='post'>
      <input type = 'hidden' name = 'tuoteno' value = '$tuoteno'>
      <input type='hidden' name='toim' value='{$toim}' />
      <input type = 'hidden' name = 'tee' value ='N'>
      <tr>
      <th>".t("L‰hett‰v‰")."<br>".t("varastopaikka").":</th>
      <th>".t("Vastaanottava")."<br>".t("varastopaikka").":</th>
      <th>".t("Siirrett‰v‰")."<br>".t("m‰‰r‰").":</th>";

  if ($trow["sarjanumeroseuranta"] != '') {
    echo "<th>".t("Valitse")."<br>".t("sarjanumerot").":</th>";
  }

  echo "</tr>";

  // Saldot per varastopaikka LEFT JOIN
  $query = "SELECT tuotepaikat.*,
            inventointilistarivi.tunnus as inventointilistatunnus,
            varastopaikat.nimitys,
            if (varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi,
            concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0')) sorttauskentta
            FROM tuotepaikat
            LEFT JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
              AND varastopaikat.tunnus                    = tuotepaikat.varasto)
            LEFT JOIN inventointilistarivi ON (inventointilistarivi.yhtio = tuotepaikat.yhtio
              AND inventointilistarivi.tuotepaikkatunnus  = tuotepaikat.tunnus
              AND inventointilistarivi.tila               = 'A')
            WHERE tuotepaikat.yhtio                       = '$kukarow[yhtio]'
            and tuotepaikat.tuoteno                       = '$tuoteno'
            and tuotepaikat.hyllyalue                    != '!!M'
            ORDER BY sorttauskentta";
  $paikatresult1 = pupe_query($query);

  echo "<tr>";
  echo "<td valign='top'><select name='mista'>";

  if (mysql_num_rows($paikatresult1) > 0) {
    while ($saldorow = mysql_fetch_array($paikatresult1)) {

      if ($oletusvarasto_chk != '' and kuuluukovarastoon($saldorow["hyllyalue"], $saldorow["hyllynro"], $oletusvarasto_chk) == 0) continue;

      list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($tuoteno, 'JTSPEC', '', '', $saldorow["hyllyalue"], $saldorow["hyllynro"], $saldorow["hyllyvali"], $saldorow["hyllytaso"]);

      if ($saldorow["inventointilistatunnus"] !== null) {
        $invalisa1 = "DISABLED";
        $invalisa2 = " (".t("Lukittu, inventointi kesken").")";
        $saldorow["tunnus"] = "";
      }
      else {
        $invalisa1 = "";
        $invalisa2 = "";
      }

      if ($myytavissa > 0 or $hyllyssa > $myytavissa) {
        echo "<option value='$saldorow[tunnus]' $invalisa1>$saldorow[nimitys]: $saldorow[hyllyalue] $saldorow[hyllynro] $saldorow[hyllyvali] $saldorow[hyllytaso] ($hyllyssa) $invalisa2</option>";
      }
    }
  }
  echo "</select></td>";
  echo "<td valign='top'><select name='minne'>";

  // Saldot per varastopaikka JOIN
  $query = "SELECT tuotepaikat.*,
            varastopaikat.nimitys,
            inventointilistarivi.tunnus as inventointilistatunnus,
            if (varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi,
            concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0')) sorttauskentta
            FROM tuotepaikat
            LEFT JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
              AND varastopaikat.tunnus                    = tuotepaikat.varasto)
            LEFT JOIN inventointilistarivi ON (inventointilistarivi.yhtio = tuotepaikat.yhtio
              AND inventointilistarivi.tuotepaikkatunnus  = tuotepaikat.tunnus
              AND inventointilistarivi.tila               = 'A')
            WHERE tuotepaikat.yhtio                       = '$kukarow[yhtio]'
            and tuotepaikat.tuoteno                       = '$tuoteno'
            and tuotepaikat.hyllyalue                    != '!!M'
            ORDER BY sorttauskentta";
  $paikatresult2 = pupe_query($query);

  if (mysql_num_rows($paikatresult2) > 0) {
    while ($saldorow = mysql_fetch_array($paikatresult2)) {

      if ($oletusvarasto_chk != '' and kuuluukovarastoon($saldorow["hyllyalue"], $saldorow["hyllynro"], $oletusvarasto_chk) == 0) continue;

      list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($tuoteno, 'JTSPEC', '', '', $saldorow["hyllyalue"], $saldorow["hyllynro"], $saldorow["hyllyvali"], $saldorow["hyllytaso"]);

      if ($saldorow["inventointilistatunnus"] !== null) {
        $invalisa1 = "DISABLED";
        $invalisa2 = " (".t("Lukittu, inventointi kesken").")";
        $saldorow["tunnus"] = "";
      }
      else {
        $invalisa1 = "";
        $invalisa2 = "";
      }

      echo "<option value='$saldorow[tunnus]' $invalisa1>$saldorow[nimitys]: $saldorow[hyllyalue] $saldorow[hyllynro] $saldorow[hyllyvali] $saldorow[hyllytaso] ($hyllyssa) $invalisa2</option>";
    }
  }
  echo "</select></td>";
  echo "<td valign='top'><input type = 'text' name = 'asaldo' size = '3' value ='$asaldo'></td>";

  if ($trow["sarjanumeroseuranta"] != '') {

    if ($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F" or $trow["sarjanumeroseuranta"] == "G") {
      $query = "SELECT sarjanumeroseuranta.tuoteno,
                tilausrivi_osto.nimitys nimitys,
                sarjanumeroseuranta.sarjanumero,
                sarjanumeroseuranta.tunnus,
                sarjanumeroseuranta.era_kpl,
                tuote.yksikko,
                concat_ws(' ', sarjanumeroseuranta.hyllyalue, sarjanumeroseuranta.hyllynro,
                sarjanumeroseuranta.hyllyvali, sarjanumeroseuranta.hyllytaso) tuotepaikka
                 FROM tuote
                JOIN tuotepaikat ON (tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno)
                JOIN sarjanumeroseuranta ON sarjanumeroseuranta.yhtio = tuote.yhtio
                and sarjanumeroseuranta.tuoteno           = tuote.tuoteno
                and sarjanumeroseuranta.hyllyalue         = tuotepaikat.hyllyalue
                and sarjanumeroseuranta.hyllynro          = tuotepaikat.hyllynro
                and sarjanumeroseuranta.hyllyvali         = tuotepaikat.hyllyvali
                and sarjanumeroseuranta.hyllytaso         = tuotepaikat.hyllytaso
                and sarjanumeroseuranta.myyntirivitunnus  = 0
                and sarjanumeroseuranta.era_kpl          != 0
                JOIN tilausrivi tilausrivi_osto use index (PRIMARY) ON (tilausrivi_osto.yhtio = sarjanumeroseuranta.yhtio and tilausrivi_osto.tunnus = sarjanumeroseuranta.ostorivitunnus)
                WHERE tuote.yhtio                         = '$kukarow[yhtio]'
                and tuote.tuoteno                         = '$trow[tuoteno]'";
    }
    else {
      $query  = "SELECT sarjanumeroseuranta.tuoteno, tilausrivi_osto.nimitys nimitys, sarjanumeroseuranta.sarjanumero, sarjanumeroseuranta.tunnus,
                 concat_ws(' ', sarjanumeroseuranta.hyllyalue, sarjanumeroseuranta.hyllynro, sarjanumeroseuranta.hyllyvali, sarjanumeroseuranta.hyllytaso) tuotepaikka
                 FROM sarjanumeroseuranta
                 JOIN tilausrivi tilausrivi_osto use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
                 WHERE sarjanumeroseuranta.yhtio           = '$kukarow[yhtio]'
                 and sarjanumeroseuranta.tuoteno           = '$trow[tuoteno]'
                 and sarjanumeroseuranta.myyntirivitunnus  = 0
                 and tilausrivi_osto.laskutettuaika       != '0000-00-00'";
    }

    $sarjares = pupe_query($query);

    if (mysql_num_rows($sarjares) > 0) {
      echo "<td valign='top' style='padding:0px;'>";

      if (mysql_num_rows($sarjares) > 10) {
        echo "<div style='height:265px;overflow:auto;'>";
      }

      echo "<table width='100%'>";

      while ($sarjarow = mysql_fetch_array($sarjares)) {
        echo "<tr>";
        echo "<td nowrap>".t_tuotteen_avainsanat($sarjarow, 'nimitys')."</td>";
        echo "<td nowrap>$sarjarow[sarjanumero]</td>";
        echo "<td nowrap>$sarjarow[tuotepaikka]</td>";

        if ($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F" or $trow["sarjanumeroseuranta"] == "G") {
          echo "<td>$sarjarow[era_kpl] ".t_avainsana("Y", "", "and avainsana.selite='$sarjarow[yksikko]'", "", "", "selite")."</td>";
          echo "<td>";
          echo "<input type='radio' name='sarjano_array[]' value='$sarjarow[tunnus]'>";
          echo "<input type='hidden' name='sarjano_kpl_array[$sarjarow[tunnus]]' value='$sarjarow[era_kpl]'>";
          echo "<input type='hidden' name='sarjano_nimi_array[$sarjarow[tunnus]]' value='$sarjarow[sarjanumero]'>";
          echo "</td>";
        }
        else {
          echo "<td><input type='checkbox' name='sarjano_array[]' value='$sarjarow[tunnus]'></td>";
        }

        echo "</tr>";
      }
      echo "</table>";

      if (mysql_num_rows($sarjares) > 10) {
        echo "</div>";
      }

      echo "</td>";
    }
    else {
      echo "<td valign='top'></td>";
    }

    $sncspan = 3;
  }
  else {
    $sncspan = 2;
  }

  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Selite")."</th>";
  echo "<td colspan='$sncspan' ><input type='text' name='selite' size='50' /></td>";

  echo "<td class='back'><input type = 'submit' value = '".t("Siirr‰")."'></td>
      </tr></table></form><br>";

  if ($toim != "VAINSIIRTO") {
    // Tehd‰‰n k‰yttˆliittym‰ paikkojen muutoksille (otetus tai pois)
    echo "  <form name = 'valinta' method='post'>
        <input type = 'hidden' name = 'tee' value ='MUUTA'>
        <input type = 'hidden' name = 'toim' value = '{$toim}' />
        <input type = 'hidden' name = 'tuoteno' value = '$tuoteno'>";

    echo "<table>";
    echo "<tr>";
    echo "<th>", t("Varasto"), "</th>";
    echo "<th>", t("Varastopaikka"), "</th>";
    echo "<th>", t("Saldo"), "</th>";
    echo "<th>", t("Hyllyss‰"), "</th>";
    echo "<th>", t("Myyt‰viss‰"), "</th>";
    echo "<th>", t("Oletuspaikka"), "</th>";
    echo "<th>", t("H‰lyraja"), "</th>";
    echo "<th>", t("Tilausm‰‰r‰"), "</th>";
    echo "<th>", t("Prio"), "</th>";
    echo "<th>", t("Poista"), "</th>";
    echo "</tr>";

    if (mysql_num_rows($paikatresult1) > 0) {
      $query = "SELECT *
                FROM tuotepaikat
                WHERE tuoteno  = '$tuoteno'
                and yhtio      = '$kukarow[yhtio]'
                and oletus    != ''";
      $result = pupe_query($query);
      $oletusrow = mysql_fetch_array($result);

      mysql_data_seek($paikatresult1, 0);

      while ($saldorow = mysql_fetch_array($paikatresult1)) {

        if ($oletusvarasto_chk != '' and kuuluukovarastoon($saldorow["hyllyalue"], $saldorow["hyllynro"], $oletusvarasto_chk) == 0) continue;

        if ($saldorow["tunnus"] == $oletusrow["tunnus"]) {
          $checked = "CHECKED";
        }
        else {
          $checked = "";
        }

        list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($tuoteno, 'JTSPEC', '', '', $saldorow["hyllyalue"], $saldorow["hyllynro"], $saldorow["hyllyvali"], $saldorow["hyllytaso"]);

        if ($saldorow["saldo"] == 0 and $hyllyssa == 0 and $myytavissa == 0) {
          //Tarkistetaan varaako reklamaatio tuotepaikkaa
          $query = "SELECT *
                    FROM lasku
                    JOIN tilausrivi ON (
                      tilausrivi.yhtio       = lasku.yhtio AND
                      tilausrivi.otunnus     = lasku.tunnus
                    )
                    WHERE lasku.yhtio        = '{$kukarow['yhtio']}'
                    AND lasku.tila           = 'C'
                    AND lasku.alatila        IN ('', 'A', 'B', 'C')
                    AND tilausrivi.hyllyalue = '{$saldorow['hyllyalue']}'
                    AND tilausrivi.hyllynro  = '{$saldorow['hyllynro']}'
                    AND tilausrivi.hyllyvali = '{$saldorow['hyllyvali']}'
                    AND tilausrivi.hyllytaso = '{$saldorow['hyllytaso']}'
                    AND tilausrivi.tuoteno   = '{$saldorow["tuoteno"]}'";
          $reklares = pupe_query($query);

          $reklacheck = (mysql_num_rows($reklares) > 0);
        }
        else {
          $reklacheck = false;
        }

        echo "<tr>";
        echo "<td>$saldorow[nimitys]</td>";
        echo "<td>";

        if (tarkista_oikeus('inventoi.php', '', 1)) {
          echo "<a href='{$palvelin2}inventoi.php?tee=INVENTOI&tuoteno=".urlencode($saldorow["tuoteno"])."&lopetus=$lopetus/SPLIT/muuvarastopaikka.php////tee=M//tuoteno=".urlencode($saldorow["tuoteno"])."'>$saldorow[hyllyalue] $saldorow[hyllynro] $saldorow[hyllyvali] $saldorow[hyllytaso]</a>";
        }
        elseif (tarkista_oikeus('inventoi.php', 'OLETUSVARASTO', 1)) {
          echo "<a href='{$palvelin2}inventoi.php?toim=OLETUSVARASTO&tee=INVENTOI&tuoteno=".urlencode($saldorow["tuoteno"])."&lopetus=$lopetus/SPLIT/muuvarastopaikka.php////toim=OLETUSVARASTO//tee=M//tuoteno=".urlencode($saldorow["tuoteno"])."'>$saldorow[hyllyalue] $saldorow[hyllynro] $saldorow[hyllyvali] $saldorow[hyllytaso]</a>";
        }
        else {
          echo "$saldorow[hyllyalue] $saldorow[hyllynro] $saldorow[hyllyvali] $saldorow[hyllytaso]";
        }

        echo "</td><td align='right'>$saldorow[saldo]</td><td align='right'>$hyllyssa</td><td align='right'>$myytavissa</td>";

        if (kuuluukovarastoon($saldorow["hyllyalue"], $saldorow["hyllynro"])) {

          echo "<td>";

          if ($oletusvarasto_chk == '' or ($oletusvarasto_chk != '' and kuuluukovarastoon($oletusrow["hyllyalue"], $oletusrow["hyllynro"], $oletusvarasto_chk) != 0)) {
            echo "<input type = 'radio' name='oletus' value='$saldorow[tunnus]' $checked>";
          }

          echo "</td>";
          echo "<td><input type='text' size='6' name='halyraja2[$saldorow[tunnus]]'  value='$saldorow[halytysraja]'></td>
            <td><input type='text' size='6' name='tilausmaara2[$saldorow[tunnus]]' value='$saldorow[tilausmaara]'></td>
            <td><input type='text' size='6' name='prio2[{$saldorow['tunnus']}]'    value='{$saldorow['prio']}'></td>";
        }
        else {
          echo "<td></td><td></td><td></td><td></td>";
        }

        $chk = $poistoteksti = "";

        if ($saldorow["poistettava"] != "") {
          $chk = "CHECKED";
          $poistoteksti = "(".t("Poistetaan kun saldo loppuu/myyt‰viss‰ nolla, eik‰ tuotepaikalle ole avoimia rivej‰").")";
        }

        // Ei n‰ytet‰ boxia, jos sit‰ ei saa k‰ytt‰‰
        if ($saldorow["saldo"] != 0 and $saldorow["oletus"] != "") {
          echo "<td></td>";
        }
        elseif ($saldorow["saldo"] != 0 or $hyllyssa != 0 or $myytavissa != 0 or $reklacheck or !empty($saldorow["inventointilistatunnus"])) {

          if ($reklacheck) {
            $poistoteksti .= "<br>(".t("Reklamaatio varaa tuotepaikkaa").")";
          }

          if (!empty($saldorow["inventointilistatunnus"])) {
            $poistoteksti .= "<br>(".t("Tuotepaikka k‰sittelem‰ttˆm‰n‰ inventointilistalla").")";
          }

          echo "<td><input type = 'checkbox' name='flagaa_poistettavaksi[$saldorow[tunnus]]' value='$saldorow[tunnus]' $chk> {$poistoteksti}
              <input type = 'hidden' name='flagaa_poistettavaksi_undo[$saldorow[tunnus]]' value='$saldorow[poistettava]'></td>";
        }
        else {

          if ($saldorow["poistettava"] != "") {
            $poistoteksti .= "<br>(".t("Voit myˆs poistaa tuotepaikan t‰st‰ heti").")";
          }

          echo "<td><input type = 'checkbox' name='poista[$saldorow[tunnus]]' value='$saldorow[tunnus]'> {$poistoteksti}</td>";
        }

        echo "</tr>";
      }
    }
    echo "<tr><td colspan='10'><input type = 'submit' value = '".t("P‰ivit‰")."'></td></table></form><br>";

    $ahyllyalue  = '';
    $ahyllynro  = '';
    $ahyllyvali  = '';
    $ahyllytaso  = '';

    echo "<table><form name = 'valinta' method='post'>
        <input type='hidden' name='tee' value='UUSIPAIKKA'>
        <input type = 'hidden' name = 'toim' value = '{$toim}' />
        <input type='hidden' name='tuoteno' value='$tuoteno'>
        <tr><th>".t("Lis‰‰ uusi varastopaikka")."</th></tr>
        <tr><td>
        ".t("Alue")." ", hyllyalue('ahyllyalue', $ahyllyalue), "
        ".t("Nro")."  <input type = 'text' name = 'ahyllynro'  size = '5' maxlength='5' value = '$ahyllynro'>
        ".t("V‰li")." <input type = 'text' name = 'ahyllyvali' size = '5' maxlength='5' value = '$ahyllyvali'>
        ".t("Taso")." <input type = 'text' name = 'ahyllytaso' size = '5' maxlength='5' value = '$ahyllytaso'>";

    echo "  </td></tr>
        <tr><td><input type = 'submit' value = '".t("Lis‰‰")."'></td></tr>
        </table></form>";
  }
  echo "<br><hr><form name = 'valinta' method='post'>
        <input type='hidden' name='tee' value=''>
        <input type = 'hidden' name = 'toim' value = '{$toim}' />
        <input type = 'submit' value = '".t("Palaa tuotteen valintaan")."'>";
}

if ($tee == '') {
  // T‰ll‰ ollaan, jos olemme vasta valitsemassa tuotetta
  echo "<form name = 'valinta' method='post'>
      <input type='hidden' name='tee' value='M'>
      <input type = 'hidden' name = 'toim' value = '{$toim}' />
      <table>
      <tr><th>".t("Anna tuotenumero")."</th><td>".livesearch_kentta("valinta", "TUOTEHAKU", "tuoteno", 210)."</td></tr>
      </table><br>
      <input type = 'submit' value = '".t("Hae")."'>
      </form>";

  $kentta = 'tuoteno';
  $formi = 'valinta';
}

if ($kutsuja == '') {
  require "inc/footer.inc";
}
