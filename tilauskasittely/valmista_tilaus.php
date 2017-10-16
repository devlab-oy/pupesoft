<?php

if (!isset($from_kaikkikorj)) {
  require "../inc/parametrit.inc";
  require 'valmistuslinjat.inc';
  require 'validation/Validation.php';

  if (!isset($toim)) $toim = "";
  if (!isset($tee)) $tee = "";

  if ($toim == 'KORJAA') {
    echo "<font class='head'>".t("Korjaa valmistus").":</font><hr>";
  }
  elseif ($toim == 'TUOTE') {
    echo "<font class='head'>".t("Valmista tuotteita").":</font><hr>";
  }
  else {
    echo "<font class='head'>".t("Valmista tilaus").":</font><hr>";
  }

  if ($tee == 'NAYTATILAUS') {
    require "raportit/naytatilaus.inc";
    echo "<hr>";
    $tee = "VALITSE";
  }

  if ($tee == "SYOTARIVI") {
    echo t("Tee uusi rivi").":<br>";

    echo "  <form method='post' autocomplete='off'>";
    echo "  <input type='hidden' name='tee' value='LISAARIVI'>
        <input type='hidden' name='valmistettavat' value='$valmistettavat'>
        <input type='hidden' name='toim'  value='$toim'>
        <input type='hidden' name='perheid'  value='$perheid'>
        <input type='hidden' name='otunnus'  value='$otunnus'>";

    require 'syotarivi.inc';
    require 'inc/footer.inc';
    exit;
  }

  if ($tee == "LISAARIVI") {

    $query  = "SELECT *
               from tuote
               where tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) > 0) {
      //Tuote lˆytyi
      $trow = mysql_fetch_assoc($result);

      $laskurow["tila"]   = 'V';
      $kukarow["kesken"]  = $otunnus;
      $perheid       = $perheid;

      // Kun ollaan korjaamassa valmistusta niin pakkohyv‰ksyt‰‰n rivi,
      // koska raaka-aine voidaan olla jo kulutettu.
      // Joten vaikka tuotetta ei myyt‰viss‰ olisikaa tulee raaka-aine voida
      // valmistuksen korjauksella valmistukselle lis‰t‰.
      if ($toim == "KORJAA") {
        $var = 'H';
      }

      require 'lisaarivi.inc';

      $lisatyt_rivit = array_merge($lisatyt_rivit1, $lisatyt_rivit2);

      if (count($lisatyt_rivit) > 0) $valmistettavat .= ",".implode(",", $lisatyt_rivit);

      if ($toim == "KORJAA") {
        if ($lisatyt_rivit[0] > 0) {
          $query = "UPDATE tilausrivi
                    SET toimitettu  = '$kukarow[kuka]',
                    toimitettuaika = now(),
                    keratty        = '$kukarow[kuka]',
                    kerattyaika    = now()
                    WHERE yhtio    = '$kukarow[yhtio]'
                    and tunnus     = '$lisatyt_rivit[0]'";
          $result = pupe_query($query);
        }
      }
    }
    else {
      echo t("Tuotetta ei lˆydy")."!<br>";
    }

    $tee = "VALMISTA";
  }

  if ($tee == 'alakorjaa') {
    //P‰ivitet‰‰n lasku niin, ett‰ se on takaisin tilassa valmistettu
    $query = "SELECT distinct lasku.tunnus, lasku.tila
              FROM tilausrivi, lasku
              WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
              and  tilausrivi.tunnus in ($valmistettavat)
              and lasku.tunnus=tilausrivi.otunnus
              and lasku.yhtio=tilausrivi.yhtio";
    $result = pupe_query($query);

    while ($row = mysql_fetch_assoc($result)) {
      if ($row["tila"] == "L") {
        $kalatila = "X";
      }
      else {
        $kalatila = "V";
      }

      $query = "UPDATE lasku
                SET alatila  = '$kalatila'
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  = '$row[tunnus]'
                and tila    = '$row[tila]'
                and alatila = 'K'";
      $chkresult4 = pupe_query($query);

      $query = "UPDATE tilausrivi
                SET varattu  = 0
                WHERE yhtio = '$kukarow[yhtio]'
                and otunnus = '$row[tunnus]'
                and tyyppi  in ('V','W','M')";
      $chkresult4 = pupe_query($query);
    }

    $valmistettavat = "";
    $tee = "";
  }
}

if (!function_exists("onkokaikkivalmistettu")) {
  function onkokaikkivalmistettu($valmkpllat) {
    global $kukarow, $tee, $valmistettavat;

    $valmistetut_tilaukset = array();

    //katotaan onko en‰‰ mit‰‰n valmistettavaa
    foreach ($valmkpllat as $rivitunnus => $tuoteno) {
      //Haetaan tilausrivi
      $query = "SELECT otunnus, uusiotunnus
                FROM tilausrivi
                WHERE yhtio = '{$kukarow["yhtio"]}'
                and tunnus  = '{$rivitunnus}'
                and tyyppi  in ('W','M')";
      $roxresult = pupe_query($query);
      $tilrivirow = mysql_fetch_assoc($roxresult);

      $otun_uusiotun = $tilrivirow["otunnus"]."|".$tilrivirow["uusiotunnus"];

      if (!in_array($otun_uusiotun, $valmistetut_tilaukset)) {
        $valmistetut_tilaukset[] = $otun_uusiotun;
      }
    }

    foreach ($valmistetut_tilaukset as $otun_uusiotun) {

      list($tilrivirow["otunnus"], $tilrivirow["uusiotunnus"]) = explode("|", $otun_uusiotun);

      // Oletetaan ett‰ tilaus on kokonaan valmistettu,
      // alla tarkastetaan onko tilaus valmistettu kokonaan vai ei
      $valmistettu = TRUE;

      //Katsotaan onko yht‰‰n valmistamatonta rivi‰ t‰ll‰ tilauksella/jobilla
      $query = "SELECT tunnus
                FROM tilausrivi
                WHERE yhtio        = '$kukarow[yhtio]'
                AND tyyppi         IN ('W','M')
                AND otunnus        = '{$tilrivirow["otunnus"]}'
                AND toimitettuaika = '0000-00-00 00:00:00'";
      $chkresult1 = pupe_query($query);

      // Eli tilaus ei ole viel‰ kokonaan valmistettu, jos rivi lˆytyy
      // ei siis tulla laittamaan valmistusta viel‰ valmiiksi
      if (mysql_num_rows($chkresult1) != 0) {
        $valmistettu = FALSE;
      }

      //jos rivit oli siirretty toiselta otsikolta niin siirret‰‰n ne nyt takaisin
      if ($tilrivirow["uusiotunnus"] != 0 and mysql_num_rows($chkresult1) == 0) {
        $query = "UPDATE tilausrivi
                  SET otunnus = uusiotunnus,
                  uusiotunnus      = 0
                  WHERE yhtio      = '$kukarow[yhtio]'
                  and otunnus      = $tilrivirow[otunnus]
                  and uusiotunnus  = $tilrivirow[uusiotunnus]
                  and uusiotunnus != 0";
        $chkresult3 = pupe_query($query);

        //tutkitaan pit‰‰kˆ alkuper‰isen otsikon tilat p‰ivitt‰‰
        $query = "SELECT tunnus
                  FROM tilausrivi
                  WHERE yhtio        = '$kukarow[yhtio]'
                  and (otunnus = $tilrivirow[uusiotunnus] or uusiotunnus = $tilrivirow[uusiotunnus])
                  and tyyppi         in ('W','M')
                  and tunnus         = perheid
                  and toimitettuaika = '0000-00-00 00:00:00'";
        $selres = pupe_query($query);

        //eli alkuper‰inen tilaus on kokonaan valmistettu
        if (mysql_num_rows($selres) == 0) {
          //Jos kyseess‰ on varastovalmistus
          $query = "UPDATE lasku
                    SET alatila  = 'V'
                    WHERE yhtio      = '$kukarow[yhtio]'
                    and tunnus       = '$tilrivirow[uusiotunnus]'
                    and tila         = 'V'
                    and alatila      in ('C','Y')
                    and tilaustyyppi = 'W'";
          $chkresult4 = pupe_query($query);

          //Jos kyseess‰ on asiakaalle valmistus
          $query = "UPDATE lasku
                    SET tila  = 'L',
                    alatila          = 'C'
                    WHERE yhtio      = '$kukarow[yhtio]'
                    and tunnus       = '$tilrivirow[uusiotunnus]'
                    and tila         = 'V'
                    and alatila      in ('C','Y')
                    and tilaustyyppi = 'V'";
          $chkresult4 = pupe_query($query);
        }
      }

      // Laitetaan valmistus valmis tilaan vain,
      // jos kaikki on jo valmistettu
      if ($valmistettu and isset($tilrivirow["otunnus"])) {
        //Jos kyseess‰ on varastovalmistus
        $query = "UPDATE lasku
                  SET alatila  = 'V'
                  WHERE yhtio      = '$kukarow[yhtio]'
                  AND tunnus       = $tilrivirow[otunnus]
                  AND tila         = 'V'
                  AND alatila      = 'C'
                  AND tilaustyyppi = 'W'";
        $chkresult2 = pupe_query($query);

        //Jos kyseess‰ on asiakaalle valmistus
        $query = "UPDATE lasku
                  SET tila  = 'L',
                  alatila          = 'C'
                  WHERE yhtio      = '$kukarow[yhtio]'
                  and tunnus       = $tilrivirow[otunnus]
                  and tila         = 'V'
                  and alatila      = 'C'
                  and tilaustyyppi = 'V'";
        $chkresult2 = pupe_query($query);

        $tee       = "";
        $valmistettavat = "";
      }
      else {
        if (!$tilrivirow["otunnus"]) {
          echo "<font class='error'>".t("VIRHE: Valmistukselta puuttuu valmisterivi. Tarkista valmistuksen rivit")."! </font><br>";
        }

        $tee = "VALMISTA";
      }
    }
  }
}

if ($tee == "TEEVALMISTUS" and $era_new_paikka != "") {
  $paivitetty = 0;

  foreach ($era_new_paikka as $rivitunnus => $uusipaikka) {

    //  Jos meill‰ on uusipaikka ja se on eri kuin vanhapaikka kohdistetaan er‰
    if ($uusipaikka != $era_old_paikka[$rivitunnus]) {

      $query = "SELECT *
                FROM tilausrivi
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  = '$rivitunnus'
                and tyyppi  = 'V'";
      $toimres = pupe_query($query);

      if (mysql_num_rows($toimres) > 0) {
        $toimrow = mysql_fetch_array($toimres);

        list($myy_hyllyalue, $myy_hyllynro, $myy_hyllyvali, $myy_hyllytaso, $myy_era) = explode("#!°!#", $era_new_paikka[$toimrow["tunnus"]]);

        $query = "DELETE FROM sarjanumeroseuranta
                  WHERE yhtio          = '$kukarow[yhtio]'
                  and tuoteno          = '$toimrow[tuoteno]'
                  and myyntirivitunnus = '$toimrow[tunnus]'";
        $sarjares2 = pupe_query($query);

        if ($uusipaikka != "") {
          if ($toimrow["varattu"] > 0) {
            $tunken = "myyntirivitunnus";

            $query = "SELECT *
                      FROM sarjanumeroseuranta
                      WHERE yhtio          = '$kukarow[yhtio]'
                      and tuoteno          = '$toimrow[tuoteno]'
                      and hyllyalue        = '$myy_hyllyalue'
                      and hyllynro         = '$myy_hyllynro'
                      and hyllytaso        = '$myy_hyllytaso'
                      and hyllyvali        = '$myy_hyllyvali'
                      and sarjanumero      = '$myy_era'
                      and myyntirivitunnus = 0
                      and ostorivitunnus   > 0
                      LIMIT 1";
            $lisa_res = pupe_query($query);

            if (mysql_num_rows($lisa_res) > 0) {
              $lisa_row = mysql_fetch_array($lisa_res);
              $oslisa = " ostorivitunnus ='$lisa_row[ostorivitunnus]', ";
            }
            else {
              $oslisa = " ostorivitunnus ='', ";
            }

          }
          else {
            $tunken = "ostorivitunnus";
            $oslisa = "";
          }

          $query = "INSERT into sarjanumeroseuranta
                    SET yhtio     = '$kukarow[yhtio]',
                    tuoteno       = '$toimrow[tuoteno]',
                    lisatieto     = '$lisa_row[lisatieto]',
                    $tunken     = '$toimrow[tunnus]',
                    $oslisa
                    kaytetty      = '$lisa_row[kaytetty]',
                    era_kpl       = '',
                    laatija       = '$kukarow[kuka]',
                    luontiaika    = now(),
                    takuu_alku    = '$lisa_row[takuu_alku]',
                    takuu_loppu   = '$lisa_row[takuu_loppu]',
                    parasta_ennen = '$lisa_row[parasta_ennen]',
                    hyllyalue     = '$myy_hyllyalue',
                    hyllynro      = '$myy_hyllynro',
                    hyllytaso     = '$myy_hyllytaso',
                    hyllyvali     = '$myy_hyllyvali',
                    sarjanumero   = '$myy_era'";
          $lisa_res = pupe_query($query);

          $query = "UPDATE tilausrivi
                    SET hyllyalue   = '$myy_hyllyalue',
                    hyllynro    = '$myy_hyllynro',
                    hyllytaso   = '$myy_hyllytaso',
                    hyllyvali   = '$myy_hyllyvali'
                    WHERE yhtio = '$kukarow[yhtio]'
                    and tunnus  = '$toimrow[tunnus]'";
          $lisa_res = pupe_query($query);

          $paivitetty++;
        }
      }
      else {
        echo "Rivi‰ ei voi liitt‰‰ $query<br>";
      }
    }
  }

  if ($paivitetty > 0) {
    $tee = "VALMISTA";
  }
}

if ($tee == 'TEEVALMISTUS' and count($valmkpllat) == 0 and count($tilkpllat) == 0) {
  //katotaan onko en‰‰ mit‰‰n valmistettavaa
  onkokaikkivalmistettu ($valmisteet_chk);
}

if ($tee == 'TEEVALMISTUS') {

  //tarkistetaan, ettei usean valmisteen reseptiss‰ valmisteta samaa tuotetta monta kertaa (t‰t‰ ei osata)
  $valmduplistek = array();

  foreach ($valmisteet_chk as $rivitunnus => $tuoteno) {

    $query = "SELECT perheid
              FROM tilausrivi
              WHERE yhtio = '$kukarow[yhtio]'
              and tunnus  = '$rivitunnus'";
    $roxresult = pupe_query($query);
    $roxrow = mysql_fetch_assoc($roxresult);

    if (!isset($valmduplistek[$roxrow["perheid"]][$tuoteno])) {
      $valmduplistek[$roxrow["perheid"]][$tuoteno] = $tuoteno;
    }
    else {
      echo "<font class='error'>".t("VIRHE: Usean valmisteen reseptiss‰ ei voida valmistaa samaa tuotetta monta kertaa")."! $tuoteno</font><br>";
      $tee = "VALMISTA";
      break;
    }
  }

  if (isset($tuotenumerot) and is_array($tuotenumerot) and count($tuotenumerot) > 0) {
    $saldot = array();
    $saldot_valm = array();

    // Tarkistetaan ettei ole orpoja raaka-aineita
    foreach ($tuotenumerot as $rivitunnus => $tuotenumero) {
      $query = "SELECT tunnus
                FROM tilausrivi
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  = '$rivitunnus'
                and tyyppi  = 'V'
                and perheid = 0";
      $roxresult = pupe_query($query);

      if (mysql_num_rows($roxresult) > 0) {
        echo "<font class='error'>".t("VIRHE: Raaka-aine ei kuulu mihink‰‰n reseptiin")."! $tuotenumero</font><br>";
        $tee = "VALMISTA";
      }

      $query = "SELECT tunnus
                FROM tuotepaikat
                WHERE yhtio = '$kukarow[yhtio]'
                and tuoteno = '$tuotenumero'";
      $roxresult = pupe_query($query);

      if (mysql_num_rows($roxresult) == 0) {
        echo "<font class='error'>".t("VIRHE: Tuotteella ei ole yht‰‰n tuotepaikkaa")."! $tuotenumero</font><br>";
        $tee = "VALMISTA";
      }
    }

    // T‰n verran on saldoilla
    foreach ($tuotenumerot as $tuotenumero) {

      list ($saldo, $hyllyssa, $myytavissa, $true) = saldo_myytavissa($tuotenumero);

      $saldot[$tuotenumero] = $saldo;
      $saldot_valm[$tuotenumero] = $saldo;
    }

    // T‰n verran tuotetaan lis‰‰ saldoja t‰ll‰ ajolla, otetaan nekin huomioon
    foreach ($valmkpllat as $rivitunnus => $valmkpl) {

      $valmkpl = str_replace(',', '.', $valmkpl);

      //Haetaan tilausrivi
      $query = "SELECT *, trim(concat_ws(' ', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso)) paikka
                FROM tilausrivi
                WHERE yhtio        = '$kukarow[yhtio]'
                and tunnus         = '$rivitunnus'
                and tyyppi         in ('W','M')
                and toimitettuaika = '0000-00-00 00:00:00'";
      $roxresult = pupe_query($query);

      if (mysql_num_rows($roxresult) == 1) {
        $tilrivirow = mysql_fetch_assoc($roxresult);

        if (($valmkpl > 0 and $valmkpl <= $tilrivirow["varattu"]) or ($kokopros > 0 and $kokopros <= 100) or isset($kokovalmistus)) {

          if ($valmkpl > 0) {
            $atil = round($valmkpl, 2);
          }
          elseif ($valmkpl == "") {
            $atil =  $tilrivirow["varattu"];
          }

          if ($kokopros > 0) {
            $atil = round($kokopros / 100 * $tilrivirow["varattu"], 2);
          }

          $saldot_valm[$tilrivirow["tuoteno"]] += $atil;
        }
      }
    }

    // Tehd‰‰n saldotsekit
    foreach ($valmkpllat as $rivitunnus => $valmkpl) {

      $valmkpl = str_replace(',', '.', $valmkpl);

      //Haetaan valmisteet
      $query = "SELECT tilausrivi.*, trim(concat_ws(' ', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso)) paikka, tuote.sarjanumeroseuranta, tuote.epakurantti25pvm
                FROM tilausrivi
                JOIN tuote ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
                WHERE tilausrivi.yhtio        = '$kukarow[yhtio]'
                and tilausrivi.perheid        = '$rivitunnus'
                and tilausrivi.tyyppi         in ('W','M')
                and tilausrivi.toimitettuaika = '0000-00-00 00:00:00'
                ORDER BY tilausrivi.tunnus";
      $roxresult = pupe_query($query);

      if (mysql_num_rows($roxresult) > 0) {
        while ($tilrivirow = mysql_fetch_assoc($roxresult)) {

          if ($tilrivirow["epakurantti25pvm"] != '0000-00-00') {
            echo "<font class='error'>".t("VIRHE: Tuote %s on ep‰kurantti, sit‰ ei saa valmistaa", "", $tilrivirow["tuoteno"])."!</font><br>";
            $tee = "VALMISTA";
          }

          if ($valmkpl < 0 or $tilrivirow["varattu"] < 0) {
            echo "<font class='error'>".t("VIRHE: Negatiivista kappalem‰‰r‰‰ ei voi valmistaa")."!</font><br>";
            $tee = "VALMISTA";
          }
          elseif (in_array($tilrivirow["sarjanumeroseuranta"], array("S", "T", "V")) and $valmkpl != (int) $valmkpl) {
            //  Tarkastetaan, ett‰ sarjanumeroseurattuja tuotteita valmistetaan tasam‰‰r‰.
            echo "<font class='error'>".t("VIRHE: Sarjanumeroseurattua tuotetta ei voi valmistaa vain osittain!")."</font><br>";
            $tee = "VALMISTA";
          }
          elseif (($valmkpl > 0 and $valmkpl <= $tilrivirow["varattu"]) or ($kokopros > 0 and $kokopros <= 100) or isset($kokovalmistus)) {

            if ($valmkpl > 0) {
              $atil = round($valmkpl, 2);
            }
            elseif ($valmkpl == "") {
              $atil =  $tilrivirow["varattu"];
            }

            if ($kokopros > 0) {
              $atil = round($kokopros / 100 * $tilrivirow["varattu"], 2);
            }

            $akerroin = $atil / $tilrivirow["varattu"];

            // Tarkistetaan valmisteiden sarjanumerot
            if ($tilrivirow["sarjanumeroseuranta"] != "") {
              // katotaan aluks onko yht‰‰n tuotetta sarjanumeroseurannassa t‰ll‰ listalla
              if (in_array($tilrivirow["sarjanumeroseuranta"], array("S", "T", "V"))) {

                $query = "SELECT count(*) kpl
                          FROM sarjanumeroseuranta
                          WHERE yhtio          = '$kukarow[yhtio]'
                          AND tuoteno          = '$tilrivirow[tuoteno]'
                          AND ostorivitunnus   = '$tilrivirow[tunnus]'
                          AND myyntirivitunnus = 0";
                $sarjares = pupe_query($query);
                $sarjarow = mysql_fetch_assoc($sarjares);

                if ($sarjarow["kpl"] != abs($tilrivirow["varattu"])) {
                  echo "<font class='error'>".t("Sarjanumeroseurannassa oleville tuotteille on liitett‰v‰ sarjanumero ennen valmistusta")."! ".t("Valmiste").": $tilrivirow[tuoteno].</font><br>";
                  $tee = "VALMISTA";
                }
              }
              else {
                $query = "SELECT sum(era_kpl) kpl
                          FROM sarjanumeroseuranta
                          WHERE yhtio          = '$kukarow[yhtio]'
                          and tuoteno          = '$tilrivirow[tuoteno]'
                          and ostorivitunnus   = '$tilrivirow[tunnus]'
                          AND myyntirivitunnus = 0";
                $sarjares = pupe_query($query);
                $sarjarow = mysql_fetch_assoc($sarjares);

                if ($sarjarow["kpl"] != $tilrivirow["varattu"]) {
                  echo "<font class='error'>".t("Er‰numeroseurannassa oleville tuotteille on liitett‰v‰ er‰numero ennen valmistusta")."! ".t("Valmiste").": $tilrivirow[tuoteno].</font><br>";
                  $tee = "VALMISTA";
                }
              }
            }

            // Haetaan k‰ytett‰v‰t raaka-aineet
            $query = "SELECT tilausrivi.*, trim(concat_ws(' ', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso)) paikka, tuote.ei_saldoa, tuote.sarjanumeroseuranta
                      FROM tilausrivi
                      JOIN tuote ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
                      WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                      and tilausrivi.otunnus = '$tilrivirow[otunnus]'
                      and tilausrivi.perheid = '$tilrivirow[perheid]'
                      and tilausrivi.tyyppi  = 'V'
                      ORDER BY tilausrivi.tuoteno";
            $perheresult = pupe_query($query);

            if (mysql_num_rows($perheresult) > 0) {
              while ($perherow = mysql_fetch_assoc($perheresult)) {
                if ($perherow["ei_saldoa"] == "") {

                  // N‰in paljon haluamme k‰ytt‰‰ raaka-ainetta
                  if ($kulukpllat[$perherow["tunnus"]] != 0) {
                    $varataankpl = $kulukpllat[$perherow["tunnus"]];
                  }
                  else {
                    $varataankpl = $perherow['varattu'] * $akerroin;
                  }

                  if ($varataankpl < 0) {
                    echo "<font class='error'>".t("VIRHE: Raaka-aineen")." ".$perherow["tuoteno"]." ".t("kulutus ei voi olla negatiivinen")."!</font><br>";
                    $tee = "VALMISTA";
                  }

                  $apuapuapua1 =  (int) $saldot_valm[$perherow["tuoteno"]] * 100;
                  $apuapuapua2 =  (int) $varataankpl * 100;

                  // katotaan kanssa, ett‰ perheenj‰senet lˆytyy kannasta ja niit‰ on riitt‰v‰sti (jos reseptiss‰ on useampi valmiste, niin summataan $varataankpl $saldot_valm-muuttujaan kun loopataan toista tat kolmatta valmistetta)
                  if ((!isset($vakisinhyvaksy) or $vakisinhyvaksy == '') and (($tilrivirow['perheid2'] != -100 and $apuapuapua1 < $apuapuapua2) or ($tilrivirow['perheid2'] == -100 and ($apuapuapua1 + $apuapuapua2) < $apuapuapua2))) {
                    echo "<font class='error'>".t("Saldo")." ".$saldot[$perherow["tuoteno"]]." ".t("ei riit‰")."! ".t("Tuotetta")." $perherow[tuoteno] ".t("kulutetaan")." $varataankpl ".t_avainsana("Y", $kieli, "and avainsana.selite='$perherow[yksikko]'", "", "", "selite").".</font><br>";
                    $virheitaoli = "JOO";
                    $tee = "VALMISTA";
                  }

                  // T‰n verran saldoa k‰ytet‰‰n (jos reseptiss‰ on useampi valmiste, niin raaka-aineet kulutetaan kuitenkin vain kerran)
                  if ($tilrivirow['perheid2'] != -100) {
                    $saldot_valm[$perherow["tuoteno"]] -= $varataankpl;
                  }

                  //  Tarkistetaan raaka-aineiden sarjanumerot
                  if ($perherow["sarjanumeroseuranta"] != "") {
                    // katotaan aluks onko yht‰‰n tuotetta sarjanumeroseurannassa t‰ll‰ listalla
                    if (in_array($perherow["sarjanumeroseuranta"], array("S", "T", "V"))) {

                      $query = "SELECT count(*) kpl
                                FROM sarjanumeroseuranta
                                WHERE yhtio          = '$kukarow[yhtio]'
                                AND tuoteno          = '$perherow[tuoteno]'
                                AND myyntirivitunnus = '$perherow[tunnus]'";
                      $sarjares = pupe_query($query);
                      $sarjarow = mysql_fetch_assoc($sarjares);

                      if ($sarjarow["kpl"] != abs($perherow["varattu"])) {
                        echo "<font class='error'>".t("Sarjanumeroseurannassa oleville tuotteille on liitett‰v‰ sarjanumero ennen valmistusta")."! ".t("Raaka-aine").": $perherow[tuoteno].</font><br>";
                        $tee = "VALMISTA";
                      }
                    }
                    else {
                      $query = "SELECT count(*) kpl
                                FROM sarjanumeroseuranta
                                WHERE yhtio          = '$kukarow[yhtio]'
                                and tuoteno          = '$perherow[tuoteno]'
                                and myyntirivitunnus = '$perherow[tunnus]'";
                      $sarjares = pupe_query($query);
                      $sarjarow = mysql_fetch_assoc($sarjares);

                      if ($sarjarow["kpl"] != 1) {
                        echo "<font class='error'>".t("Er‰numeroseurannassa oleville tuotteille on liitett‰v‰ er‰numero ennen valmistusta")."! ".t("Raaka-aine").": $perherow[tuoteno].</font><br>";
                        $tee = "VALMISTA";
                      }
                    }
                  }
                }
              }
            }
          }
          elseif ($valmkpl > 0) {
            echo "<font class='error'>".t("VIRHE: Syˆtit liian ison kappalem‰‰r‰n")."!</font><br>";
            $tee = "VALMISTA";
          }
        }
      }
    }
    echo "<br>";
  }
}

if ($tee == 'TEEVALMISTUS' and isset($osatoimitus)) {
  // Osatoimitetaan valitut rivit
  if (count($osatoimitetaan) > 0) {
    $tilrivilisa = implode(',', $osatoimitetaan);

    require "tilauksesta_myyntitilaus.inc";

    $query = "SELECT otunnus, group_concat(tunnus) tunnukset
              FROM tilausrivi
              WHERE yhtio = '$kukarow[yhtio]'
              and tunnus  in ($tilrivilisa)
              GROUP BY otunnus";
    $copresult = pupe_query($query);

    while ($coprow = mysql_fetch_assoc($copresult)) {

      $tillisa = " and tilausrivi.tunnus in ($coprow[tunnukset]) ";

      $tilauksesta_myyntitilaus = tilauksesta_myyntitilaus($coprow["otunnus"], $tillisa, "", "", "", "JOO");

      echo "$tilauksesta_myyntitilaus<br>";

      $query = "UPDATE tilausrivi
                SET tyyppi = 'D'
                WHERE yhtio = '$kukarow[yhtio]'
                $tillisa
                and tyyppi  = 'L'";
      $chkresult4 = pupe_query($query);
    }
  }

  $tee = "VALMISTA";
}

if ($tee == 'TEEVALMISTUS') {
  //K‰yd‰‰n l‰pi rivien kappalem‰‰r‰t ja tehd‰‰n samalla pieni tsekki, ett‰ onko rivi jo valmistettu
  foreach ($tilkpllat as $rivitunnus => $tilkpl) {

    $tilkpl = str_replace(',', '.', $tilkpl);

    // Tarkistetaan ettei tilaus ole jo toimitettu/laskutettu
    if ($toim == "KORJAA") {
      $ylatilat  = " 'V','L' ";
      $alatilat   = " 'K' ";
    }
    else {
      $ylatilat  = " 'V' ";
      $alatilat   = " 'C' ";
    }

    $query = "SELECT distinct lasku.tunnus
              FROM tilausrivi, lasku
              WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
              and tilausrivi.tunnus  = '$rivitunnus'
              and lasku.tunnus       = tilausrivi.otunnus
              and lasku.tila         in ($ylatilat)
              and lasku.alatila      in ($alatilat)";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 1) {
      $lasrow = mysql_fetch_assoc($result);

      //Haluaako k‰ytt‰j‰ muuttaa rivill‰ olevaa m‰‰r‰‰
      if ($edtilkpllat[$rivitunnus] != $tilkpl) {

        //  Lasketaan kerroin jos teemme rekursiivisesti
        if ($rekru[$rivitunnus] != "") {
          //  Varmistetaan, ett‰ t‰m‰ on perheen is‰
          $query = "SELECT tunnus
                    FROM tilausrivi
                    WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$rivitunnus' and tunnus = perheid";
          $result = pupe_query($query);

          if (mysql_num_rows($result) <> 1) {
            $virhe[$rivitunnus] = "<font class='message'>".t("Tuote ei ole perheen is‰. Rekursiivinen p‰ivitys ei onnistu.")."!</font>";
          }
          else {

            if ((float) $edtilkpllat[$rivitunnus] != 0) {
              $perhekerroin = $tilkpl/$edtilkpllat[$rivitunnus];
            }
            else {
              $perhekerroin = 1;
            }

            $query = "UPDATE tilausrivi
                      SET varattu = (varattu * $perhekerroin)
                      WHERE yhtio = '$kukarow[yhtio]'
                      and otunnus = '$lasrow[tunnus]'
                      and perheid = '$rivitunnus' and perheid > 0
                      and tyyppi  = 'V'";
            $updresult = pupe_query($query);
            $virhe[$rivitunnus] = "<font class='message'>".t("Perheen lapset kerrottiin %s:lla.", $kieli, round($perhekerroin, 4))."!</font><br>";
          }
        }

        $laskurow = mysql_fetch_assoc($result);

        // Pit‰‰ tiet‰‰ onko tuote er‰numeroseurannassa,
        // koska er‰numeroseurattavien tuotteiden rivim‰‰r‰‰ ei saa lis‰t‰
        $query =  "SELECT tuote.sarjanumeroseuranta
                   FROM tuote
                   JOIN tilausrivi ON (tilausrivi.yhtio = tuote.yhtio AND tilausrivi.tuoteno = tuote.tuoteno)
                   WHERE tuote.yhtio              = '{$kukarow['yhtio']}'
                   AND tilausrivi.tunnus          = $rivitunnus
                   AND tilausrivi.laskutettuaika != '0000-00-00'";
        $_sarja = mysql_fetch_assoc(pupe_query($query));

        if (in_array($_sarja["sarjanumeroseuranta"], array("E", "G", "F"))) {
          if ($tilkpl <= $edtilkpllat[$rivitunnus]) {
            $query = "UPDATE tilausrivi
                      SET varattu = '$tilkpl'
                      WHERE yhtio = '$kukarow[yhtio]'
                      and tunnus  = '$rivitunnus'";
            $updresult = pupe_query($query);

            $tee = "VALMISTA";
            $virhe[$rivitunnus] .= "<font class='message'>".t("M‰‰r‰ p‰ivitetty")."!</font>";
          }
          else {
            $tee = "VALMISTA";
            $virhe[$rivitunnus] .= "<font class='error'>".t("Er‰numerollisen tuotteen m‰‰r‰‰ ei saa lis‰t‰, jos rivi on jo k‰ytetty")."!</font>";
          }

        }
        else {
          $query = "UPDATE tilausrivi
                    SET varattu = '$tilkpl'
                    WHERE yhtio = '$kukarow[yhtio]'
                    and tunnus  = '$rivitunnus'";
          $updresult = pupe_query($query);

          $tee = "VALMISTA";
          $virhe[$rivitunnus] .= "<font class='message'>".t("M‰‰r‰ p‰ivitetty")."!</font>";

        }
      }
    }
    else {
      echo "<font class='error'>".t("Valmistus on jo korjattu")."!</font><br><br>";
      $tee = "XXX";
      break;
    }
  }

  // Jatketaan valmistusta
  if ($tee == "TEEVALMISTUS") {
    if ($toim == "KORJAA") {

      $peruttiinko = FALSE;

      foreach ($valmkpllat as $rivitunnus => $valmkpl) {

        $valmkpl = str_replace(',', '.', $valmkpl);

        $query = "SELECT tilausrivi.*,
                  tuote.sarjanumeroseuranta, tuote.ei_saldoa
                  FROM tilausrivi
                  JOIN tuote ON (tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno)
                  WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                  and tilausrivi.tunnus  = '$rivitunnus'
                  and tilausrivi.tyyppi  in ('W','M')";
        $roxresult = pupe_query($query);

        if (mysql_num_rows($roxresult) == 1) {
          $tilrivirow = mysql_fetch_assoc($roxresult);

          $tuoteno     = $tilrivirow["tuoteno"];
          $tee      = "UV";

          // Perheid sen takia, ett‰ perutaan myˆs useat valmisteet perutaan
          if (isset($perutamakorj[$tilrivirow["perheid"]]) and $perutamakorj[$tilrivirow["perheid"]] != "") {
            $perutaan = "JOO";
            $peruttiinko = TRUE;
          }
          else {
            $perutaan = "";
          }

          require "korjaa_valmistus.inc";
        }
      }

      //P‰ivitet‰‰n lasku niin, ett‰ se on takaisin tilassa valmistettu/laskutettu
      $query = "SELECT distinct lasku.tunnus, lasku.tila
                FROM tilausrivi, lasku
                WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                and  tilausrivi.tunnus in ($valmistettavat)
                and lasku.tunnus=tilausrivi.otunnus
                and lasku.yhtio=tilausrivi.yhtio";
      $result = pupe_query($query);

      while ($row = mysql_fetch_assoc($result)) {
        if ($row["tila"] == "L") {
          $kalatila = "X";
        }
        else {
          if ($peruttiinko) {
            $kalatila = "C";
          }
          else {
            $kalatila = "V";
          }
        }

        $query = "UPDATE lasku
                  SET alatila  = '$kalatila'
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$row[tunnus]'
                  and tila    = '$row[tila]'
                  and alatila = 'K'";
        $chkresult4 = pupe_query($query);

        $query = "UPDATE tilausrivi
                  SET varattu  = 0
                  WHERE yhtio = '$kukarow[yhtio]'
                  and otunnus = '$row[tunnus]'
                  and tyyppi  in ('V','W','M')";
        $chkresult4 = pupe_query($query);

        // P‰ivitet‰‰n er‰-/sarjanumerot laskutetuiksi, jotta ne poistuvat varastonarvosta oikein
        $query = "UPDATE tilausrivi
                  JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.sarjanumeroseuranta != '')
                  SET tilausrivi.laskutettu = '$kukarow[kuka]',
                  tilausrivi.laskutettuaika     = now()
                  WHERE tilausrivi.yhtio        = '$kukarow[yhtio]'
                  AND tilausrivi.otunnus        = '$row[tunnus]'
                  AND tilausrivi.tyyppi         = 'V'
                  AND tilausrivi.laskutettuaika = '0000-00-00'";
        $updresult = pupe_query($query);
      }

      echo "<br><br><font class='message'>Valmistus korjattu!</font><br>";
      $tee = "";
    }
    else {
      foreach ($valmkpllat as $rivitunnus => $valmkpl) {

        $valmkpl = str_replace(',', '.', $valmkpl);

        //Haetaan tilausrivi
        $query = "SELECT *
                  FROM tilausrivi
                  WHERE yhtio        = '$kukarow[yhtio]'
                  and tunnus         = '$rivitunnus'
                  and tyyppi         in ('W','M')
                  and toimitettuaika = '0000-00-00 00:00:00'";
        $roxresult = pupe_query($query);

        if (mysql_num_rows($roxresult) == 1) {
          $tilrivirow = mysql_fetch_assoc($roxresult);

          // Otetaan tiedot alkuper‰iselt‰ tilaukselta
          if ($tilrivirow["uusiotunnus"] != 0) {
            $slisa = " and lasku.tunnus = tilausrivi.uusiotunnus";
          }
          else {
            $slisa = " and lasku.tunnus = tilausrivi.otunnus ";
          }

          $query = "SELECT lasku.*
                    FROM tilausrivi, lasku
                    WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                    and tilausrivi.tunnus  = '$rivitunnus'
                    $slisa
                    and lasku.tila         = 'V'";
          $result = pupe_query($query);
          $laskurow = mysql_fetch_assoc($result);

          if (($valmkpl > 0 and $valmkpl <= $tilrivirow["varattu"]) or ($kokopros > 0 and $kokopros <= 100) or isset($kokovalmistus)) {

            if ($valmkpl > 0) {
              $atil = round($valmkpl, 2);
            }
            elseif ($valmkpl == "") {
              $atil =  $tilrivirow["varattu"];
            }

            if ($kokopros > 0) {
              $atil = round($kokopros / 100 * $tilrivirow["varattu"], 2);
            }

            $akerroin     = $atil / $tilrivirow["varattu"];
            $tuoteno     = $tilrivirow["tuoteno"];
            $tee      = "UV";
            $jaljella_tot  = 0;

            require "valmistatuotteita.inc";

            //jos valmistus meni ok, niin palautuu $tee == UV
            if (round($jaljella_tot, 2) == 0 and $tee == "UV") {
              //p‰ivitet‰‰n t‰m‰ perhe valmistetuksi
              $query = "UPDATE tilausrivi
                        SET toimitettu = '$kukarow[kuka]',
                        toimitettuaika = now(),
                        varattu        = 0
                        WHERE yhtio    = '$kukarow[yhtio]'
                        and otunnus    = '$tilrivirow[otunnus]'
                        and tyyppi     in ('W','V','M','O')
                        and perheid    = '$tilrivirow[perheid]'";
              $updresult = pupe_query($query);

              // P‰ivitet‰‰n er‰-/sarjanumerot laskutetuiksi, jotta ne poistuvat varastonarvosta oikein
              $query = "UPDATE tilausrivi
                        JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno AND tuote.sarjanumeroseuranta != '')
                        SET tilausrivi.laskutettu = '$kukarow[kuka]',
                        tilausrivi.laskutettuaika = now()
                        WHERE tilausrivi.yhtio    = '$kukarow[yhtio]'
                        and tilausrivi.otunnus    = '$tilrivirow[otunnus]'
                        and tilausrivi.tyyppi     = 'V'
                        and tilausrivi.perheid    = '$tilrivirow[perheid]'";
              $updresult = pupe_query($query);
            }

            if ($tee != "UV") {
              $virheitaoli = "JOO";
              $valmkpllat2[$rivitunnus] = $valmkpl;
            }
          }
          elseif ($valmkpl == 0 or $valmkpl == "") {
            $virhe[$rivitunnus] = "<font class='message'>".t("Kappalem‰‰r‰ ei syˆtetty")."!</font>";
            $tee = "VALMISTA";
            $valmkpllat2[$rivitunnus] = $valmkpl;
          }
          else {
            $virhe[$rivitunnus] = "<font class='error'>".t("VIRHE: Syˆtit liian ison kappalem‰‰r‰n")."!</font>";
            $tee = "VALMISTA";
            $valmkpllat2[$rivitunnus] = $valmkpl;
          }
        }
      }

      //katotaan onko en‰‰ mit‰‰n valmistettavaa
      onkokaikkivalmistettu($valmkpllat);
    }
  }
}

if (!isset($from_kaikkikorj)) {
  if ($tee == "VALMISTA" and $valmistettavat != "") {
    // Haetaan otsikoiden tiedot
    $query = "SELECT
              GROUP_CONCAT(DISTINCT lasku.tunnus SEPARATOR ', ') 'Tilaus',
              GROUP_CONCAT(DISTINCT lasku.nimi SEPARATOR ', ') 'Asiakas/Nimi',
              GROUP_CONCAT(DISTINCT lasku.ytunnus SEPARATOR ', ') 'Ytunnus',
              GROUP_CONCAT(DISTINCT lasku.tilaustyyppi SEPARATOR ', ') 'Tilaustyyppi'
              FROM tilausrivi
              JOIN lasku ON (lasku.tunnus = tilausrivi.otunnus and lasku.yhtio = tilausrivi.yhtio)
              WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
              and tilausrivi.tunnus in ($valmistettavat)
              and tilausrivi.tyyppi != 'D'";
    $result = pupe_query($query);
    $row = mysql_fetch_assoc($result);

    if (empty($row["Tilaus"])) {
      echo "<font class='error'>".t("Yht‰‰n tilausta ei lˆytynyt")."</font><br>";
      $tee = "";
    }
  }

  if ($tee == "VALMISTA" and $valmistettavat != "") {


    //P‰ivitet‰‰n lasku niin, ett‰ se on tilassa korjataan
    if ($toim == "KORJAA") {
      $query = "UPDATE lasku
                SET alatila  = 'K'
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  in ($row[Tilaus])
                and tila    in ('V','L')
                and alatila in ('V','X')";
      $chkresult4 = pupe_query($query);

      $korjataan = " and (tilausrivi.toimitettu != '' or tilausrivi.tyyppi in ('D','O')) ";
    }
    else {
      $korjataan = "";
    }

    //Jos valmistetaan per tuote niin valinnasta/hausta tulee vain valmisteiden tunnukset, tarvitsemme kuitenkin myˆs raaka-aineiden tunnukset joten haetaan ne t‰ss‰
    if ($toim == "TUOTE" and $tulin == "VALINNASTA") {
      $query = "SELECT
                GROUP_CONCAT(DISTINCT tilausrivi.tunnus SEPARATOR ',') valmistettavat
                FROM tilausrivi
                WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
                and tilausrivi.otunnus  in ($row[Tilaus])
                and  tilausrivi.perheid in ($valmistettavat)";
      $ktres = pupe_query($query);
      $ktrow = mysql_fetch_assoc($ktres);

      $valmistettavat = $ktrow["valmistettavat"];
    }

    echo "<table>";

    for ($i=0; $i < mysql_num_fields($result)-1; $i++) {
      echo "<tr><th align='left'>" . t(mysql_field_name($result, $i)) ."</th><td>{$row[mysql_field_name($result, $i)]}</td></tr>";
    }

    //  Voidaan hyp‰t‰ suoraan muokkaamaan tilausta
    if ($toim == "" and strpos($row["Tilaus"], ",") === FALSE and $row["Tilaustyyppi"] == "W") {
      echo "  <tr>
            <td class='back' colspan='2'>
            <form method='post' action='tilaus_myynti.php'>
            <input type='hidden' name='toim' value='VALMISTAVARASTOON'>
            <input type='hidden' name='tilausnumero' value='$row[Tilaus]'>
            <input type='hidden' name='lopetus' value='$PHP_SELF////tee=VALMISTA//tulin=VALINNASTA//valmistettavat=$valmistettavat'>
            <input type='submit' value='".t("Muokkaa tilausta")."'>
            </form>
            </td>
          </tr>";
    }
    echo "</table><br>";

    $_jarj = $yhtiorow['tilauksen_jarjestys_suunta'];

    if (empty($row["Tilaus"]) or empty($valmistettavat)) {
      echo "<font class='error'>".t("Yht‰‰n valmistettavaa tilausta/tuotetta ei lˆytynyt")."!</font>";
      exit;
    }

    // Haetaan valmistettavat valmisteet ja k‰ytett‰v‰t raaka-aineet
    $query = "SELECT tilausrivi.nimitys,
              tilausrivi.tuoteno,
              tilkpl tilattu,
              if (tyyppi not in ('L','D','O'), varattu, 0) valmistetaan,
              if (tyyppi in ('L','D','O'), varattu, 0) valmistettu,
              if (toimitettu!='', if (varattu!=0, varattu, kpl), 0) korjataan,
              if (toimitettu!='', kpl, 0) valmistettu_valmiiksi,
              if (tyyppi='V', kpl, 0) kaytetty,
              toimaika,
              kerayspvm,
              tilausrivi.tunnus tunnus,
              tilausrivi.perheid,
              tilausrivi.tyyppi,
              tilausrivi.toimitettuaika,
              tilausrivi.otunnus otunnus,
              tilausrivi.uusiotunnus laskutettu,
              tilausrivi.kommentti,
              tuote.ei_saldoa,
              tilausrivi.kommentti,
              tuote.sarjanumeroseuranta,
              tuote.yksikko,
              tilausrivi.varattu,
              tilausrivi.var,
              tilausrivi.hyllyalue,
              tilausrivi.hyllyvali,
              tilausrivi.hyllytaso,
              tilausrivi.hyllynro
              FROM tilausrivi, tuote
              WHERE tilausrivi.otunnus in ($row[Tilaus])
              and tilausrivi.tunnus    in ($valmistettavat)
              and tilausrivi.yhtio     = '$kukarow[yhtio]'
              and tuote.yhtio          = tilausrivi.yhtio
              and tuote.tuoteno        = tilausrivi.tuoteno
              and tyyppi               in ('V','W','M','L','D','O')
              $korjataan
              ORDER BY IF(tilausrivi.perheid=0, tilausrivi.tunnus, tilausrivi.perheid) {$_jarj},
              CASE tilausrivi.tyyppi
                WHEN 'W' THEN 1
                WHEN 'M' THEN 2
                WHEN 'L' THEN 3
                WHEN 'D' THEN 4
                WHEN 'O' THEN 5
                WHEN 'V' THEN 6
              END,
              tunnus";
    $presult = pupe_query($query);
    $riveja = mysql_num_rows($presult);

    echo "<table><tr>";
    $miinus = 3;

    echo "<th>#</th>";

    echo "<th>".t("Nimitys")."</th>";
    echo "<th>".t("Tuoteno")."</th>";
    echo "<th>".t("Tilattu")."</th>";
    echo "<th>".t("Valmistetaan")."</th>";
    echo "<th>".t("Valmistettu")."</th>";
    echo "<th>".t("Kommentti")."</th>";
    echo "<th>".t("Toimaika")."</th>";
    echo "<th>".t("Ker‰ysaika")."</th>";

    if ($toim != 'KORJAA' and $toim != 'TUTKAA') {
      echo "<th>".t("Valmista")."</th>";
    }

    echo "</tr>";

    if ($yhtiorow["tilauksen_jarjestys_suunta"] == "ASC") {
      $rivkpl = 0;
    }
    else {
      $rivkpl = mysql_num_rows($presult)+1;
    }

    $voikokorjata = 0;

    $vanhaid = "KALA";

    js_popup();

    echo "  <form method='post' autocomplete='off'>";
    echo "  <input type='hidden' name='tee' value='TEEVALMISTUS'>
        <input type='hidden' name='valmistettavat' value='$valmistettavat'>
        <input type='hidden' name='toim'  value='$toim'>";

    $lopetus = $palvelin2;
    $lopetus .= "tilauskasittely/valmista_tilaus.php";
    $lopetus .= "////toim=$toim";
    $lopetus .= "//tee=$tee";
    $lopetus .= "//tulin=$tulin";
    $lopetus .= "//valmistettavat=$valmistettavat";
    $lopetus .= "//otunnus=$row[Tilaus]";

    while ($prow = mysql_fetch_assoc($presult)) {

      $class = '';

      if ($vanhaid != $prow["perheid"] and $vanhaid != 'KALA') {
        echo "<tr><td class='back' colspan='7'><br></td></tr>";
      }

      if ($yhtiorow["tilauksen_jarjestys_suunta"] == "ASC") {
        $rivkpl++;
      }
      else {
        $rivkpl--;
      }

      if ($prow["tyyppi"] == 'W' or $prow["tyyppi"] == 'M') {
        // N‰m‰ ovat valmisteita
        $class = "spec";

        echo "<input type='hidden' name='valmisteet_chk[$prow[tunnus]]' value='$prow[tuoteno]'>";
        echo "<input type='hidden' name='tuotenumerot[$prow[tunnus]]' value='$prow[tuoteno]'>";
      }
      elseif ($prow["tyyppi"] == 'D' or $prow["tyyppi"] == 'O') {
        // N‰m‰ ovat jo valmistettu
        $class = "green";
      }
      else {
        // T‰ss‰ kaikki raaka-aineet

        // tehd‰‰n saldotsekki vain saldollisille raaka-aineille
        if ($prow["ei_saldoa"] == "") {
          echo "<input type='hidden' name='tuotenumerot[$prow[tunnus]]' value='$prow[tuoteno]'>";
        }
      }

      echo "<tr>";
      echo "<td valign='top' class='$class'>$rivkpl</td>";

      $sarjavalinta = "";

      if (in_array($prow["sarjanumeroseuranta"], array("S", "T", "V")) and $prow["toimitettuaika"] == "0000-00-00 00:00:00") {

        if ($prow["tyyppi"] == "V") {
          $tunken = "myyntirivitunnus";
        }
        else {
          $tunken = "ostorivitunnus";
        }

        $query = "SELECT count(*) kpl
                  FROM sarjanumeroseuranta
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND tuoteno = '$prow[tuoteno]'
                  AND $tunken = '$prow[tunnus]'";
        $sarjares = pupe_query($query);
        $sarjarow = mysql_fetch_assoc($sarjares);

        if ($sarjarow["kpl"] == abs($prow["valmistetaan"]+$prow["valmistettu"]+$prow["valmistettu_valmiiksi"])) {
          $sarjavalinta = " (<a href='sarjanumeroseuranta.php?tuoteno=".urlencode($prow["tuoteno"])."&$tunken=$prow[tunnus]&otunnus=$row[Tilaus]&muut_siirrettavat=$valmistettavat&from=valmistus&aputoim=$toim' class='green'>".t("S:nro ok")."</font></a>)";
        }
        else {
          $sarjavalinta = " (<a href='sarjanumeroseuranta.php?tuoteno=".urlencode($prow["tuoteno"])."&$tunken=$prow[tunnus]&otunnus=$row[Tilaus]&muut_siirrettavat=$valmistettavat&from=valmistus&aputoim=$toim'>".t("S:nro")."</a>)";
        }
      }
      elseif (in_array($prow["sarjanumeroseuranta"], array("E", "F", "G")) and $prow["tyyppi"] == "V" and $prow["toimitettuaika"] == "0000-00-00 00:00:00") {
        $sarjavalinta = "<span style='float: left;'>†";
        $sarjavalinta .= t("Er‰").": ";
        $sarjavalinta .= "<select name='era_new_paikka[$prow[tunnus]]' onchange='submit();'>";

        if ($prow["sarjanumeroseuranta"] == "F") {
          $pepvmlisa1 = " sarjanumeroseuranta.parasta_ennen, ";
          $pepvmlisa2 = ", 18";
        }
        else {
          $pepvmlisa1 = "";
          $pepvmlisa2 = "";
        }

        $query = "SELECT
                  sarjanumeroseuranta.sarjanumero era,
                  tuote.ei_saldoa,
                  tuote.tuoteno,
                  tuote.vakkoodi,
                  tuote.yhtio,
                  tuotepaikat.hyllyalue,
                  tuotepaikat.hyllynro,
                  tuotepaikat.hyllytaso,
                  tuotepaikat.hyllyvali,
                  tuotepaikat.oletus,
                  varastopaikat.erikoistoimitus_alarajasumma,
                  varastopaikat.maa varastomaa,
                  varastopaikat.nimitys,
                  varastopaikat.tunnus varasto,
                  varastopaikat.tyyppi varastotyyppi,
                  concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0')) sorttauskentta,
                  if(varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi,
                  $pepvmlisa1
                  group_concat(sarjanumeroseuranta.ostorivitunnus) ostorivitunnus
                   FROM tuote
                  JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
                  JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
                    AND varastopaikat.tunnus                = tuotepaikat.varasto)
                  JOIN sarjanumeroseuranta ON sarjanumeroseuranta.yhtio = tuote.yhtio
                  and sarjanumeroseuranta.tuoteno           = tuote.tuoteno
                  and sarjanumeroseuranta.hyllyalue         = tuotepaikat.hyllyalue
                  and sarjanumeroseuranta.hyllynro          = tuotepaikat.hyllynro
                  and sarjanumeroseuranta.hyllyvali         = tuotepaikat.hyllyvali
                  and sarjanumeroseuranta.hyllytaso         = tuotepaikat.hyllytaso
                  and sarjanumeroseuranta.myyntirivitunnus  = 0
                  and sarjanumeroseuranta.era_kpl          != 0
                  WHERE tuote.yhtio                         = '$kukarow[yhtio]'
                  and tuote.tuoteno                         = '$prow[tuoteno]'
                  GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17 $pepvmlisa2
                  ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
        $omavarastores = pupe_query($query);

        $sarjavalinta .= "<option value=''>".t("Valitse er‰")."</option>";
        $selpaikka   = "";

        $query  = "SELECT sarjanumeroseuranta.sarjanumero era, sarjanumeroseuranta.parasta_ennen
                   FROM sarjanumeroseuranta
                   WHERE yhtio          = '$kukarow[yhtio]'
                   and tuoteno          = '$prow[tuoteno]'
                   and myyntirivitunnus = '$prow[tunnus]'
                   LIMIT 1";
        $sarjares = pupe_query($query);
        $sarjarow = mysql_fetch_assoc($sarjares);

        while ($alkurow = mysql_fetch_assoc($omavarastores)) {
          if ($alkurow["hyllyalue"] != "!!M" and
            ($alkurow["varastotyyppi"] != "E" or
              $laskurow["varasto"] == $alkurow["varasto"] or
              ($alkurow["hyllyalue"] == $prow["hyllyalue"] and $alkurow["hyllynro"] == $prow["hyllynro"] and $alkurow["hyllyvali"] == $prow["hyllyvali"] and $alkurow["hyllytaso"] == $prow["hyllytaso"]))) {

            if (!empty($yhtiorow["saldo_kasittely"])) {
              $saldoaikalisa = date("Y-m-d");
            }
            else {
              $saldoaikalisa = "";
            }

            list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($prow["tuoteno"], '', '', '', $alkurow["hyllyalue"], $alkurow["hyllynro"], $alkurow["hyllyvali"], $alkurow["hyllytaso"], $laskurow["toim_maa"], $saldoaikalisa, $alkurow["era"]);

            $myytavissa = (float) $myytavissa;
            $lisa_row   = array();

            if ($alkurow["ostorivitunnus"] != "" and in_array($prow["sarjanumeroseuranta"], array("E", "F", "G"))) {
              //Jos er‰ on keksitty k‰sin t‰‰lt‰ ker‰yksest‰
              $query = "SELECT tyyppi, (varattu+kpl+jt) kpl, tunnus, laskutettu
                        FROM tilausrivi
                        WHERE yhtio = '$kukarow[yhtio]'
                        and tuoteno = '$prow[tuoteno]'
                        and tunnus  in ($alkurow[ostorivitunnus])";
              $lisa_res = pupe_query($query);
              $lisa_row = mysql_fetch_assoc($lisa_res);
            }

            // varmistetaan, ett‰ t‰m‰ er‰ on k‰ytett‰viss‰, eli ostorivitunnus pointtaa ostoriviin, hyvitysriviin tai laskutettuun myyntiriviin tai t‰h‰n riviin itsess‰‰n
            if (($lisa_row["tyyppi"] == "O" or $lisa_row["kpl"] < 0 or $lisa_row["laskutettu"] != "" or $lisa_row["tunnus"] == $prow["tunnus"]) and
              ($myytavissa >= $prow["varattu"] or
                ($alkurow["hyllyalue"] == $prow["hyllyalue"]
                  and $alkurow["hyllynro"] == $prow["hyllynro"]
                  and $alkurow["hyllyvali"] == $prow["hyllyvali"]
                  and $alkurow["hyllytaso"] == $prow["hyllytaso"]
                  and $sarjarow["era"] == $alkurow["era"]))) {

              $sel = "";

              if ($sarjarow["era"] == $alkurow["era"] and $alkurow["hyllyalue"] == $prow["hyllyalue"] and $alkurow["hyllynro"] == $prow["hyllynro"] and $alkurow["hyllyvali"] == $prow["hyllyvali"] and $alkurow["hyllytaso"] == $prow["hyllytaso"]) {
                $sel = "SELECTED";

                $selpaikka = "$alkurow[hyllyalue]#!°!#$alkurow[hyllynro]#!°!#$alkurow[hyllyvali]#!°!#$alkurow[hyllytaso]#!°!#$alkurow[era]";
              }
              elseif (isset($_POST["era_new_paikka"][$prow["tunnus"]]) and $_POST["era_new_paikka"][$prow["tunnus"]] == "$alkurow[hyllyalue]#!°!#$alkurow[hyllynro]#!°!#$alkurow[hyllyvali]#!°!#$alkurow[hyllytaso]#!°!#$alkurow[era]") {
                $sel = "SELECTED";

                $selpaikka = "$alkurow[hyllyalue]#!°!#$alkurow[hyllynro]#!°!#$alkurow[hyllyvali]#!°!#$alkurow[hyllytaso]#!°!#$alkurow[era]";
              }

              $sarjavalinta .= "<option value='$alkurow[hyllyalue]#!°!#$alkurow[hyllynro]#!°!#$alkurow[hyllyvali]#!°!#$alkurow[hyllytaso]#!°!#$alkurow[era]' $sel>";

              if (strtoupper($alkurow['varastomaa']) != strtoupper($yhtiorow['maa'])) {
                $sarjavalinta .= strtoupper($alkurow['varastomaa'])." ";
              }

              $sarjavalinta .= "$alkurow[hyllyalue] $alkurow[hyllynro] $alkurow[hyllyvali] $alkurow[hyllytaso], $alkurow[era]";
              $sarjavalinta .= " ($myytavissa)";

              if ($prow["sarjanumeroseuranta"] == "F") {
                $sarjavalinta .= " ".tv1dateconv($alkurow["parasta_ennen"]);
              }

              $sarjavalinta .= "</option>";
            }
          }
        }

        $sarjavalinta .= "</select>";
        $sarjavalinta .= "<input type='hidden' name='era_old_paikka[$prow[tunnus]]' value='$selpaikka'>";
        $sarjavalinta .= " (<a href='sarjanumeroseuranta.php?tuoteno=".urlencode($prow["tuoteno"])."&myyntirivitunnus=$prow[tunnus]&otunnus=$row[Tilaus]&muut_siirrettavat=$valmistettavat&from=valmistus&aputoim=$toim'>".t("E:nro")."</a>)";
        $sarjavalinta .= "</span>";
      }
      elseif (in_array($prow["sarjanumeroseuranta"], array("E", "F", "G")) and (($prow["tyyppi"] != "V" and $prow["toimitettuaika"] == "0000-00-00 00:00:00") or ($prow["tyyppi"] == "V" and $prow["toimitettuaika"] != "0000-00-00 00:00:00"))) {

        if ($prow["tyyppi"] == "V") {
          $tunken = "myyntirivitunnus";
        }
        else {
          $tunken = "ostorivitunnus";
        }

        $query = "SELECT sum(era_kpl) kpl
                  FROM sarjanumeroseuranta
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND tuoteno = '$prow[tuoteno]'
                  AND $tunken = '$prow[tunnus]'";
        $sarjares = pupe_query($query);
        $sarjarow = mysql_fetch_assoc($sarjares);

        if ($toim == "KORJAA" and $prow["tyyppi"] == 'V') {
          $era_maara = $prow["korjataan"];
        }
        else {
          $era_maara = $prow["valmistetaan"]+$prow["valmistettu"]+$prow["valmistettu_valmiiksi"];
        }

        if ($sarjarow["kpl"] == abs($era_maara)) {
          $sarjavalinta = " (<a href='sarjanumeroseuranta.php?tuoteno=".urlencode($prow["tuoteno"])."&$tunken=$prow[tunnus]&otunnus=$row[Tilaus]&muut_siirrettavat=$valmistettavat&from=valmistus&aputoim=$toim' class='green'>".t("E:nro ok")."</font></a>)";
        }
        else {
          $sarjavalinta = " (<a href='sarjanumeroseuranta.php?tuoteno=".urlencode($prow["tuoteno"])."&$tunken=$prow[tunnus]&otunnus=$row[Tilaus]&muut_siirrettavat=$valmistettavat&from=valmistus&aputoim=$toim'>".t("E:nro")."</a>)";
        }
      }

      echo "<td class='$class' valign='top'>".t_tuotteen_avainsanat($prow, 'nimitys')."</td>";
      echo "<td class='$class' valign='top'><a href='{$palvelin2}tuote.php?tee=Z&tuoteno=".urlencode($prow["tuoteno"])."&lopetus=$lopetus'>$prow[tuoteno]</a></td>";
      echo "<td class='$class' valign='top' align='right'>$sarjavalinta <span style='float: right; width: 80px;'>$prow[tilattu]".strtolower($prow["yksikko"])."</span></td>";

      if ($toim == "KORJAA" and  $prow["tyyppi"] == 'V') {
        echo "<td valign='top' class='$class' align='left'>
          <input type='hidden' name='edtilkpllat[$prow[tunnus]]'  value='$prow[korjataan]'>
          <input type='text' size='8' name='tilkpllat[$prow[tunnus]]' value='$prow[korjataan]'>";
        echo "</td>";

        echo "<td valign='top' class='$class' align='left'>$prow[valmistettu_valmiiksi]</td>";
      }
      elseif ($toim == "KORJAA" and  $prow["tyyppi"] == 'W') {
        echo "<td valign='top' class='$class' align='left'>
            <input type='hidden' name='edtilkpllat[$prow[tunnus]]'  value='$prow[valmistetaan]'>
            <input type='text' size='8' name='tilkpllat[$prow[tunnus]]' value='$prow[valmistetaan]'></td>";
        echo "<td valign='top' class='$class'></td>";
      }
      elseif ($prow["tyyppi"] == 'L' or $prow["tyyppi"] == 'D' or $prow["tyyppi"] == 'O' or $prow["perheid"] == 0) {
        echo "<td valign='top' class='$class' align='left'></td>";
        echo "<td valign='top' class='$class' align='left'>$prow[valmistettu]</td>";
      }
      elseif ($prow["toimitettuaika"] == "0000-00-00 00:00:00") {
        echo "<td valign='top' class='$class' align='left'>
            <input type='hidden' name='edtilkpllat[$prow[tunnus]]'  value='$prow[valmistetaan]'>
            <input type='text' size='8' name='tilkpllat[$prow[tunnus]]' value='$prow[valmistetaan]'>";

        if ($prow["tyyppi"] == "W" or $prow["tyyppi"] == "M") {
          // Is‰tuotteet, p‰ivitet‰‰nkˆ valmistettavat kappaleet koko reseptille
          echo "<br>R: <input class='tooltip' id='rekruohje' type='checkbox' name='rekru[$prow[tunnus]]' checked> ";
          echo "<div id='div_rekruohje' class='popup' style='width: 200px'>R: ".t("P‰ivitet‰‰nkˆ valmisteen kappalemuutos myˆs raaka-aineille").".</div>";
        }

        echo "</td>";
        echo "<td valign='top' class='$class'></td>";
      }
      elseif ($prow["tyyppi"] == 'V') {
        echo "<td valign='top' class='$class' align='right'></td>";
        echo "<td valign='top' class='$class' align='right'>$prow[kaytetty]</td>";
      }
      else {
        echo "<td valign='top' class='$class' align='right'></td>";
        echo "<td valign='top' class='$class'></td>";
      }

      echo "<td valign='top' class='$class' align='left'> $prow[kommentti]</td>";
      echo "<td valign='top' class='$class' align='right'>".tv1dateconv($prow["toimaika"])."</td>";
      echo "<td valign='top' class='$class' align='right'>".tv1dateconv($prow["kerayspvm"])."</td>";

      if ($prow["perheid"] > 0 and $prow["tyyppi"] == "V" and $prow["toimitettuaika"] == "0000-00-00 00:00:00" and $toim != "KORJAA") {

        if ($valmkpllat2[$prow["perheid"]] != 0) {
          $lapsivalue = $kulukpllat[$prow["tunnus"]];
        }
        else {
          $lapsivalue = "";
        }

        echo "<td valign='top' align='center'><input type='text' name='kulukpllat[$prow[tunnus]]' value='$lapsivalue' size='5'></td>";
      }

      if (in_array($prow["tyyppi"], array('W', 'M')) and $prow["toimitettuaika"] == "0000-00-00 00:00:00" and $toim != "KORJAA") {
        echo "<td valign='top' class='$class' align='center'><input type='text' name='valmkpllat[$prow[tunnus]]' value='".$valmkpllat2[$prow["tunnus"]]."' size='5'></td><td class='back'>".$virhe[$prow["tunnus"]];
        echo "<br><a href='$PHP_SELF?toim=$toim&tee=SYOTARIVI&valmistettavat=$valmistettavat&perheid=$prow[perheid]&otunnus=$prow[otunnus]'>".t("Lis‰‰ raaka-aine")."</a>";
        echo  "</td>";
      }
      elseif ($prow["tunnus"] != $prow["perheid"] and ($prow["tyyppi"] == "W" or $prow["tyyppi"] == "M") and $prow["toimitettuaika"] == "0000-00-00 00:00:00" and $toim != "KORJAA") {
        echo "<td valign='top' align='center'>".t("Usea valmiste")."</td>";
      }
      elseif (($prow["tyyppi"] == "W" or $prow["tyyppi"] == "M") and $prow["toimitettuaika"] != "0000-00-00 00:00:00" and $toim == "KORJAA") {

        //tutkitaan kuinka paljon t‰t‰ nyt oli valmistettu
        $query = "SELECT sum(kpl) valmistetut
                  FROM tilausrivi
                  WHERE yhtio        = '$kukarow[yhtio]'
                  and otunnus        = '$prow[otunnus]'
                  and perheid        = '$prow[perheid]'
                  and tuoteno        = '$prow[tuoteno]'
                  and tyyppi         in ('D','O')";
        $sumres = pupe_query($query);
        $sumrow = mysql_fetch_assoc($sumres);

        $query = "SELECT count(*) laskuja
                  FROM lasku
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$prow[laskutettu]'
                  and tila    = 'U'
                  and alatila = 'X'";
        $slres = pupe_query($query);
        $slrow = mysql_fetch_assoc($slres);

        echo "<td valign='top'>";

        if ($prow["tunnus"] == $prow["perheid"]) {
          if ($sumrow["valmistetut"] != 0 and $slrow["laskuja"] == 0) {

            if ((float) $prow["valmistetaan"] > 0) {
              echo "<input type='hidden' name='valmkpllat[$prow[tunnus]]' value='$prow[valmistetaan]'>";
            }
            else {
              echo "<input type='hidden' name='valmkpllat[$prow[tunnus]]' value='$sumrow[valmistetut]'>";
            }

            echo "<input type='checkbox' name='perutamakorj[$prow[tunnus]]' value='$prow[tunnus]'> ".t("Peru t‰m‰ valmistus").".";
            $voikokorjata++;
          }
          elseif ($sumrow["valmistetut"] != 0 and $slrow["laskuja"] > 0) {

            if ((float) $prow["valmistetaan"] > 0) {
              echo "<input type='hidden' name='valmkpllat[$prow[tunnus]]' value='$prow[valmistetaan]'>";
            }
            else {
              echo "<input type='hidden' name='valmkpllat[$prow[tunnus]]' value='$sumrow[valmistetut]'>";
            }

            echo "<font class='error'>".t("Rivi‰ ei voida perua")."!</font>";
            $voikokorjata++;
          }
          else {
            echo "<font class='error'>".t("Rivi‰ ei voida korjata")."!</font>";
          }

          echo "<br><a href='$PHP_SELF?toim=$toim&tee=SYOTARIVI&valmistettavat=$valmistettavat&perheid=$prow[perheid]&otunnus=$prow[otunnus]'>".t("Lis‰‰ raaka-aine")."</a>";
          echo "</td>";
          echo "<td valign='top' class='back'>".$virhe[$prow["tunnus"]]."</td>";
        }
        else {
          if (($sumrow["valmistetut"] != 0 and $slrow["laskuja"] == 0) or ($sumrow["valmistetut"] != 0 and $slrow["laskuja"] > 0)) {

            if ((float) $prow["valmistetaan"] > 0) {
              echo "<input type='hidden' name='valmkpllat[$prow[tunnus]]' value='$prow[valmistetaan]'>";
            }
            else {
              echo "<input type='hidden' name='valmkpllat[$prow[tunnus]]' value='$sumrow[valmistetut]'>";
            }
          }

          echo t("Usea valmiste")."</td>";
          echo "</td>";
          echo "<td valign='top' class='back'>".$virhe[$prow["tunnus"]]."</td>";
        }
      }

      if ($prow["tyyppi"] == "L" and $toim != "KORJAA" and $toim != 'TUTKAA') {
        echo "<td valign='top' align='center'><input type='checkbox' name='osatoimitetaan[$prow[tunnus]]' value='$prow[tunnus]'></td>";
      }

      if ($prow["tyyppi"] == "V") {
        echo "<td valign='top' class='back'>".$virhe[$prow["tunnus"]]."</td>";
      }

      echo "</tr>";

      $vanhaid = $prow["perheid"];
    }

    echo "<tr><td colspan='9' class='back'><br></td></tr>";

    if (isset($virheitaoli) and $virheitaoli == "JOO") {

      $forcenap = "<tr><td colspan='9' class='back'>".t("V‰kisinvalmista vaikka raaka-aineiden saldo ei riit‰").". <input type='checkbox' name='vakisinhyvaksy' value='OK'></td></tr>";

      if (isset($kokovalmistus))                 $kokovalmistus_force = $forcenap;
      elseif (isset($osavalmistus) and (int) $kokopros > 0)  $osavalmistuspros_force = $forcenap;
      elseif (isset($osavalmistus))                $osavalmistus_force = $forcenap;
      elseif (isset($osatoimitus))              $osatoimitus_force = $forcenap;
    }

    if ($toim != 'KORJAA' and $toim != 'TUTKAA') {
      echo "<tr><td colspan='8'>", t("Valmista syˆtetyt kappaleet"), ":</td><td><input type='submit' name='osavalmistus' id='osavalmistus' value='".t("Valmista")."'></td>$osavalmistus_force</tr>";
      echo "<tr><td colspan='4'>", t("Valmista prosentti koko tilauksesta"), ":</td><td colspan='4' align='right'><input type='text' name='kokopros' size='5'> % </td><td><input type='submit' name='osavalmistus' id='osavalmistus' value='".t("Valmista")."'></td>$osavalmistuspros_force</tr>";
      echo "<tr><td colspan='8'>", t("Siirr‰ valitut valmisteet uudelle tilaukselle"), ":</td><td><input type='submit' name='osatoimitus' id='osatoimitus' value='".t("Osatoimita")."'></td>$osatoimitus_force</tr>";
      echo "<tr><td colspan='8'>", t("Valmista koko tilaus"), ":</td><td><input type='submit' name='kokovalmistus' id='kokovalmistus' value='".t("Valmista")."'></td>$kokovalmistus_force";
    }
    elseif ($toim == 'KORJAA' and $voikokorjata > 0) {
      echo "<tr><td colspan='8'>", t("Korjaa koko valmistus"), ":</td><td class='back'><input type='submit' name='' value='".t("Korjaa")."'></td>";
    }

    echo "</tr>";
    echo "</form>";
    echo "</table><br><br>";

    echo "  <form method='post' autocomplete='off'>";
    echo "<input type='hidden' name='toim'  value='$toim'>";

    if ($toim != 'KORJAA') {
      echo "<input type='hidden' name='tee' value=''>";
      echo "<input type='submit' name='' value='".t("Valmis")."'>";
    }
    else {
      echo "<input type='hidden' name='tee' value='alakorjaa'>";
      echo "<input type='hidden' name='valmistettavat' value='$valmistettavat'>";
      echo "<input type='submit' name='' value='".t("ƒl‰ korjaa")."'>";
    }
    echo "</form>";
  }

  if ($tee == "") {
    $formi="find";
    $kentta="etsi";

    $valmistuslinjat = hae_valmistuslinjat();

    // tehd‰‰n etsi valinta
    echo "<br><form name='find' action='valmista_tilaus.php' method='post'>";
    echo "<input type='hidden' name='toim'  value='$toim'>";

    if ($toim == "TUOTE") {
      echo "<table>";

      echo "<tr>";
      echo "<th>".t('Valmiste')."/".t('raaka-aine')."</th>";
      echo "<td>";
      echo "<input type='text' name='etsi' value='{$etsi}' />";
      echo "</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<th>".t('Ker‰ysp‰iv‰')."</th>";
      echo "<td>";
      echo "  <input type='text' name='pp' value='{$pp}' size='3'>
          <input type='text' name='kk' value='{$kk}' size='3'>
          <input type='text' name='vv' value='{$vv}' size='5'>";
      echo "</td>";
      echo "</tr>";

      if (!empty($valmistuslinjat)) {
        echo "<tr>";
        echo "<th>".t('Valmistuslinja')."</th>";
        echo "<td>";
        echo "<select name='valmistuslinja'>";
        echo "<option value='' >".t('Ei valintaa')."</option>";
        foreach ($valmistuslinjat as $_valmistuslinja) {
          $sel = "";
          if ($_valmistuslinja['selite'] == $valmistuslinja) {
            $sel = "SELECTED";
          }
          echo "<option value='{$_valmistuslinja['selite']}' {$sel}>{$_valmistuslinja['selitetark']}</option>";
        }
        echo "</select>";
        echo "</td>";
        echo "</tr>";
      }

      echo "<tr>";
      echo "<th>".t('Valmistenumero')."</th>";
      echo "<td>";
      echo "<input type='text' name='tilausnumero' value='{$tilausnumero}' />";
      echo "</td>";
      echo "</tr>";

      echo "</table>";
    }
    else {
      echo t("Etsi asiakasta/valmistusta").": ";
      echo "<input type='text' name='etsi'>";
    }

    echo "<input type='submit' class='hae_btn' value='".t("Etsi")."'></form>";

    $haku = "";
    $laskuindex = "tila_index";

    $kerayspvm = "{$vv}-{$kk}-{$pp}";
    $where = "";
    if (FormValidator::validateContent($kerayspvm, 'paiva')) {
      $where .= "  AND lasku.kerayspvm = '{$kerayspvm}'";
    }

    if ($valmistuslinja != '') {
      $where .= " AND lasku.kohde = '{$valmistuslinja}'";
    }

    if (!empty($tilausnumero)) {
      $where .= "  AND lasku.tunnus = '{$tilausnumero}'";
    }

    if ($toim == "TUOTE" and isset($etsi) and $etsi != "") {
      $haku = " and tilausrivi.tuoteno = '$etsi' ";
    }
    else {
      if (isset($etsi) and is_string($etsi) and $etsi != '') {
        $haku = " and match (lasku.nimi) against ('$etsi*' IN BOOLEAN MODE) ";
        $laskuindex = "asiakasnimi";
      }
      if (isset($etsi) and is_numeric($etsi)) {
        $haku = " and lasku.tunnus = '$etsi' ";
        $laskuindex = "PRIMARY";
      }
    }

    if ($toim == "TUOTE") {
      $query     = "SELECT tilausrivi.tuoteno,
                    sum(tilausrivi.varattu) varattu,
                    GROUP_CONCAT(DISTINCT lasku.tunnus ORDER BY lasku.tunnus SEPARATOR '<br>') tunnus,
                    GROUP_CONCAT(DISTINCT lasku.ytunnus SEPARATOR '<br>') ytunnus,
                    GROUP_CONCAT(DISTINCT lasku.nimi SEPARATOR '<br>') nimi,
                    GROUP_CONCAT(DISTINCT tilausrivi.tunnus SEPARATOR ',') valmistettavat";

      $grouppi  = " GROUP BY tilausrivi.tuoteno";
      $orderby   = " order by tuoteno, lasku.tunnus, varattu";
      $ylatilat  = " 'V' ";
      $alatilat   = " 'C','B' ";
      $lisa     = "";
      $rivilisat  = " and tilausrivi.tyyppi in ('W','M')
              and tilausrivi.toimitettu = ''
              and tilausrivi.varattu != 0";
    }
    elseif ($toim == "KORJAA") {
      $query     = "SELECT lasku.ytunnus, lasku.tila, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp,
                    lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp,
                    lasku.maksuehto, lasku.tunnus, lasku.viesti, count(tilausrivi.tunnus) riveja,
                    GROUP_CONCAT(DISTINCT tilausrivi.tunnus SEPARATOR ',') valmistettavat";

      $grouppi  = " GROUP BY lasku.tunnus";
      $ylatilat  = " 'V', 'L' ";
      $alatilat   = " 'V', 'K', 'X' ";
      $orderby   = " order by lasku.tunnus desc";
      $lisa     = " and lasku.tilaustyyppi in ('V','W') ";
      $rivilisat  = " and (tilausrivi.toimitettu != '' or tilausrivi.tyyppi in ('D','O')) ";

      if ($haku == "") {
        $lisa .= " and lasku.luontiaika >= date_sub(curdate(), INTERVAL 90 DAY) ";
        $laskuindex = "yhtio_tila_luontiaika";
      }
    }
    elseif ($toim == "TUTKAA") {
      $query     = "SELECT lasku.ytunnus, lasku.tila, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp,
                    lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp,
                    lasku.maksuehto, lasku.tunnus, lasku.viesti, count(tilausrivi.tunnus) riveja,
                    GROUP_CONCAT(DISTINCT tilausrivi.tunnus SEPARATOR ',') valmistettavat";

      $grouppi  = " GROUP BY lasku.tunnus";
      $ylatilat  = " 'V','L' ";
      $alatilat   = " 'V','K','X' ";
      $orderby   = " order by lasku.tunnus desc";
      $lisa     = " and lasku.tilaustyyppi in ('V','W') ";
      $rivilisat  = " ";

      if ($haku == "") {
        $lisa .= " and lasku.luontiaika >= date_sub(curdate(), INTERVAL 90 DAY) ";
        $laskuindex = "yhtio_tila_luontiaika";
      }
    }
    else {
      $query     = "SELECT lasku.ytunnus, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp,
                    lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp,
                    lasku.maksuehto, lasku.tunnus, lasku.viesti, count(tilausrivi.tunnus) riveja,
                    GROUP_CONCAT(DISTINCT tilausrivi.tunnus SEPARATOR ',') valmistettavat";

      $grouppi  = " GROUP BY lasku.tunnus";
      $ylatilat  = " 'V' ";
      $alatilat   = " 'C','B' ";
      $orderby   = " order by lasku.tunnus";
      $lisa     = " ";
      $rivilisat  = " ";

      if ($haku == "") {
        $lisa .= " and lasku.luontiaika >= date_sub(curdate(), INTERVAL 180 DAY) ";
      }

      // Jos valmistuksessa k‰ytet‰‰n tilakoodeja, n‰ytet‰‰n pelk‰st‰‰n tarkistettu
      // tilassa olevat valmistukset
      if ($yhtiorow['valmistuksessa_kaytetaan_tilakoodeja'] == 'K') {
        $valmistuksen_tila = "and lasku.valmistuksen_tila = 'TA'";
      } else {
        $valmistuksen_tila = '';
      }
    }

    $query .= "  FROM lasku use index ($laskuindex)
          JOIN tilausrivi ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus $rivilisat)
          WHERE lasku.yhtio = '$kukarow[yhtio]'
          and lasku.tila   in ($ylatilat)
          and lasku.alatila  in ($alatilat)
          $where
          $lisa
          $haku
          $valmistuksen_tila
          $grouppi
          $orderby
          LIMIT 100";
    $tilre = pupe_query($query);

    if (mysql_num_rows($tilre) > 0) {
      echo "<br><br><table>";

      if ($toim == "KORJAA") {
        echo "<tr><th>".t("Valmistus")."</th><th>".t("Tyyppi")."</th><th>".t("Asiakas/Varasto")."</th><th>".t("Ytunnus")."</th><th>".t("Valmiste")."</th><tr>";
      }
      elseif ($toim == "TUOTE") {
        echo "<tr><th>".t("Valmistus")."</th><th>".t("Asiakas/Varasto")."</th><th>".t("Ytunnus")."</th><th>".t("Valmiste")."</th><th>".t("M‰‰r‰")."</th><tr>";
      }
      else {
        echo "<tr><th>".t("Valmistus")."</th><th>".t("Asiakas/Varasto")."</th><th>".t("Ytunnus")."</th><th>".t("Viesti")."</th><tr>";
      }

      while ($tilrow = mysql_fetch_assoc($tilre)) {

        echo "<tr><td valign='top'>$tilrow[tunnus]</td>";

        if ($toim == "KORJAA" and $tilrow["tila"] == "L") {
          echo "<td valign='top'>".t("Asiakkaallevalmistus")."</td>";
        }
        elseif ($toim == "KORJAA" and $tilrow["tila"] == "V") {
          echo "<td valign='top'>".t("Varastoonvalmistus")."</td>";
        }

        echo "<td valign='top'>$tilrow[nimi] $tilrow[nimitark]</td><td valign='top'>$tilrow[ytunnus]</td>";


        if ($toim == "TUOTE") {
          echo "<td valign='top'>$tilrow[tuoteno]</td><td valign='top'>$tilrow[varattu]</td>";
        }
        else {
          echo "<td valign='top'>$tilrow[viesti]</td>";
        }

        echo "  <form method='post'><td class='back'>
            <input type='hidden' name='tee' value='VALMISTA'>
            <input type='hidden' name='tulin' value='VALINNASTA'>
            <input type='hidden' name='toim'  value='$toim'>
            <input type='hidden' name='valmistettavat' value='$tilrow[valmistettavat]'>
            <input type='submit' value='".t("Valitse")."'></td></tr></form>";
      }
      echo "</table>";
    }
    else {
      echo "<br><br><font class='message'>".t("Yht‰‰n valmistettavaa tilausta/tuotetta ei lˆytynyt")."...</font>";
    }
  }

  require "inc/footer.inc";
}
