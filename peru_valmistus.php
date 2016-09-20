<?php

require "inc/parametrit.inc";

echo "<font class='head'>".t("Peru valmistus").":</font><hr>";

if ($id != 0) {
  $query = "SELECT *
            FROM lasku
            WHERE yhtio = '$kukarow[yhtio]'
            AND tunnus  = '$id'
            AND ((tila = 'V' and alatila  in ('V','C')) or (tila = 'L' and alatila  != 'X' and tilaustyyppi='V'))";
  $res = pupe_query($query);

  if (mysql_num_rows($res) != 1) {
    echo "Valmistusta ei löydy!";
    exit;
  }
  $laskurow = mysql_fetch_array($res);

  // Laskun tiedot
  echo t("Laskun tiedot");
  echo "<table>";
  echo "<tr><th>".t("Tunnus").":</th>  <td>$laskurow[tunnus]</td></tr>";
  echo "<tr><th>".t("Tila").":</th>  <td>$laskurow[tila]</td></tr>";
  echo "<tr><th>".t("Alatila").":</th><td>$laskurow[alatila]</td></tr>";
  echo "<tr><th>".t("Nimi").":</th>  <td>$laskurow[nimi]</td></tr>";
  echo "<tr><th>".t("Viite").":</th>  <td>$laskurow[viesti]</td></tr>";
  echo "</table><br><br>";

  // Näytetään tilausrivit
  $query = "SELECT *
            from tilausrivi
            where yhtio = '$kukarow[yhtio]'
            and otunnus = '$laskurow[tunnus]'
            and tyyppi  in ('V','W','M')
            ORDER BY perheid desc, tyyppi in ('W','M','L','D','V'), tunnus";
  $res = pupe_query($query);

  echo t("Tilausrivit, Tuote ja Tapahtumat").":<br>";
  echo "<table>";
  echo "<form method=POST'>";
  echo "<input type='hidden' name='id'  value='$laskurow[tunnus]'>";

  while ($rivirow = mysql_fetch_array($res)) {

    if ($tee != 'KORJAA' and $edtuote != $rivirow["perheid"]) {
      echo "<tr><td colspan='3' class='back'><hr></td></tr>";
    }

    $edtuote = $rivirow["perheid"];

    if ($rivirow["perheid"] == $rivirow["tunnus"]) {

      //Haetaan valmistetut tuotteet ja raaka-aineet
      $query = "SELECT tilausrivi.*, tuote.sarjanumeroseuranta
                from tilausrivi
                JOIN tuote ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno
                where tilausrivi.yhtio = '$kukarow[yhtio]'
                and tilausrivi.otunnus = '$laskurow[tunnus]'
                and tilausrivi.perheid = '$rivirow[perheid]'
                and tilausrivi.tyyppi  in ('L','D','O','V')
                order by tilausrivi.tunnus desc";
      $valm_res = pupe_query($query);

      $sarjanumeroita = FALSE;

      // onko sarjanumerollisia ?
      while ($valm_row = mysql_fetch_array($valm_res)) {
        if ($valm_row["sarjanumeroseuranta"] != "") {
          $sarjanumeroita = TRUE;
          break;
        }
      }

      if (!$sarjanumeroita and $tee != 'KORJAA') {
        echo "<tr><th colspan='1'><input type='checkbox' name='perutaan[$rivirow[tunnus]]' value='$rivirow[tunnus]'> ".t("Peru tuotteen valmistus")."</th></tr>";
      }

      mysql_data_seek($valm_res, 0);

      // Flägätään voidaanko isä poistaa
      $isa_voidaankopoistaa = "";
      $valmkpl = 0;

      while ($valm_row = mysql_fetch_array($valm_res)) {
        if ($valm_row["tyyppi"] == "L" or $valm_row["tyyppi"] == "D" or $valm_row["tyyppi"] == "O") {
          $valmkpl += $valm_row["kpl"];
        }

        if (($valm_row["tyyppi"] == "L" or $valm_row["tyyppi"] == "O") and ($valm_row["laskutettu"] != "" or $valm_row["laskutettuaika"] != "0000-00-00")) {
          $isa_voidaankopoistaa = "EI";
        }
      }
    }
    else {
      $valmkpl =   $rivirow["kpl"];
    }

    $query = "SELECT *
              from tuote
              where yhtio = '$kukarow[yhtio]'
              and tuoteno = '$rivirow[tuoteno]'";
    $tuoteres = pupe_query($query);
    $tuoterow = mysql_fetch_array($tuoteres);

    if ($tee != 'KORJAA') {
      echo "<tr>";
      echo "<td class='back' valign='top'><table>";
      echo "<tr><th>".t("Tuoteno").":</th>      <td><a href='".$palvelin2."tuote.php?tee=Z&tuoteno=".urlencode($rivirow["tuoteno"])."'>$rivirow[tuoteno]</a></td></tr>";
      echo "<tr><th>".t("Tilattu").":</th>      <td>$rivirow[tilkpl]</td></tr>";
      echo "<tr><th>".t("Varattu").":</th>      <td>$rivirow[varattu]</td></tr>";
      echo "<tr><th>".t("Valmistettu").":</th>    <td>$valmkpl</td></tr>";
      echo "<tr><th>".t("Valmistettu").":</th>    <td>$rivirow[toimitettuaika]</td></tr>";

      if ($rivirow["tyyppi"] == "W" or $rivirow["tyyppi"] == "L" or $rivirow["tyyppi"] == "M") {
        echo "<tr><th>".t("Kehahinta").":</th>    <td>$tuoterow[kehahin]</td></tr>";
        echo "<tr><th>".t("Otunnus").":</th>    <td>$rivirow[otunnus]</td></tr>";
      }

      echo "</table></td>";
    }

    $query = "SELECT count(*) kpl
              from tapahtuma
              where yhtio    = '$kukarow[yhtio]'
              and laji       = 'valmistus'
              and rivitunnus = '$rivirow[tunnus]'";
    $countres = pupe_query($query);
    $countrow = mysql_fetch_array($countres);

    //Montako kertaa tämä rivi on viety varastoon
    $vientikerrat = $countrow["kpl"];

    // Tässä toi DESC sana ja LIMIT 1 ovat erityisen tärkeitä
    $order = "";

    if ($tee == 'KORJAA') {
      $order = "DESC LIMIT 1";

      //Tässä aloitetaan ykkösestä jotta voimme poistaa vientikerran jos niitä on monta vaikka meillä on order by desc ja se joka poistetaan tulee ekana järjestyksessä
      $vientikerta = 1;
    }
    else {
      //Normaalisti eka vientikerta saa indeksin nolla
      $vientikerta = 0;
    }

    //Haetaan tapahtuman tiedot
    $query = "SELECT *
              from tapahtuma
              where yhtio    = '$kukarow[yhtio]'
              and laji       in ('valmistus','kulutus')
              and rivitunnus = '$rivirow[tunnus]'
              order by tunnus $order";
    $tapares = pupe_query($query);

    while ($taparow = mysql_fetch_array($tapares)) {

      if ($tee != 'KORJAA') {
        echo "<td class='back' valign='top'><table>";
        echo "<tr><th>".t("Tuoteno").":</th><td><a href='".$palvelin2."tuote.php?tee=Z&tuoteno=".urlencode($taparow["tuoteno"])."'>$taparow[tuoteno]</a></td></tr>";
        echo "<tr><th>".t("Kpl").":</th><td>$taparow[kpl]</td></tr>";
        echo "<tr><th>".t("Laatija")." ".t("Laadittu").":</th><td>$taparow[laatija] $taparow[laadittu]</td></tr>";
        echo "<tr><th>".t("Selite").":</th><td>$taparow[selite]</td></tr>";

        if ($rivirow["tyyppi"] == "W" or $rivirow["tyyppi"] == "L" or $rivirow["tyyppi"] == "M") {
          echo "<tr><th>Kehahinta:</th><td>$taparow[hinta]</td></tr>";
        }
      }

      // Tutkitaan onko tätä tuotetta runkslattu tän tulon jälkeen
      $query = "SELECT count(*) kpl
                from tapahtuma
                where yhtio     = '$kukarow[yhtio]'
                and laji        in ('laskutus','inventointi','epäkurantti','valmistus', 'kulutus')
                and tuoteno     = '$taparow[tuoteno]'
                and tunnus      > '$taparow[tunnus]'
                and rivitunnus != '$taparow[rivitunnus]'";
      $tapares2 = pupe_query($query);
      $taparow2 = mysql_fetch_array($tapares2);

      if ($rivirow["tyyppi"] == "W" or $rivirow["tyyppi"] == "L" or $rivirow["tyyppi"] == "M") {
        $voidaankopoistaa = "";

        if ($isa_voidaankopoistaa == "EI") {
          $voidaankopoistaa = "Ei";
        }
        elseif ($taparow2["kpl"] > 0) {
          $voidaankopoistaa = "Ei";
        }
        elseif ($taparow2["kpl"] == 0) {
          $voidaankopoistaa = "Kyllä";
        }
        else {
          $voidaankopoistaa = "Ei";
        }
      }

      if ($tee != 'KORJAA' and ($rivirow["tyyppi"] == "W" or $rivirow["tyyppi"] == "L" or $rivirow["tyyppi"] == "M")) {
        echo "<tr><th>".t("Voidaanko poistaa").":</th><td>$voidaankopoistaa</td></tr>";
      }

      $query = "SELECT *
                from tapahtuma
                where yhtio = '$kukarow[yhtio]'
                and laji    in ('tulo','valmistus')
                and tuoteno = '$taparow[tuoteno]'
                and tunnus  < '$taparow[tunnus]'
                ORDER BY tunnus DESC
                LIMIT 1";
      $tapares3 = pupe_query($query);

      if (mysql_num_rows($tapares3) > 0) {
        $taparow3 = mysql_fetch_array($tapares3);
      }
      else {
        $query = "SELECT *
                  from tapahtuma
                  where yhtio = '$kukarow[yhtio]'
                  and laji    in ('laskutus')
                  and tuoteno = '$taparow[tuoteno]'
                  and tunnus  < '$taparow[tunnus]'
                  ORDER BY tunnus DESC
                  LIMIT 1";
        $tapares3 = pupe_query($query);
        $taparow3 = mysql_fetch_array($tapares3);
      }

      if ($tee != 'KORJAA') {

        if ($rivirow["tyyppi"] == "W" or $rivirow["tyyppi"] == "L" or $rivirow["tyyppi"] == "M") {
          echo "<tr><th>".t("Kehahinta")." from --> to:</th><td>$taparow[hinta] --> $taparow3[hinta]</td></tr>";
        }
        echo "</table></td>";
      }

      if ($tee == 'KORJAA' and ($voidaankopoistaa == "Kyllä" or $vakisinpoista == 'Kyllä') and $perutaan[$rivirow["perheid"]] == $rivirow["perheid"] and $perutaan[$rivirow["perheid"]] != 0) {

        //Poistetaan tapahtuma
        $query = "DELETE from tapahtuma
                  where yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$taparow[tunnus]'";
        $korjres = pupe_query($query);

        echo "$rivirow[tuoteno]: ".t("Poistetaan tapahtuma")."!<br>";

        //Haetaan valmistetut tuotteet ja poistetaan informativinen valmistusrivi
        if ($taparow["laji"] == "valmistus") {
          $query = "SELECT *
                    from tilausrivi
                    where yhtio = '$kukarow[yhtio]'
                    and otunnus = '$laskurow[tunnus]'
                    and perheid = '$rivirow[perheid]'
                    and tyyppi  in ('L','D')
                    order by tunnus $order";
          $valm_res = pupe_query($query);

          while ($valm_row = mysql_fetch_array($valm_res)) {
            //Poistetaan tilausrivi
            $query = "DELETE from tilausrivi
                      where yhtio = '$kukarow[yhtio]'
                      and otunnus = '$laskurow[tunnus]'
                      and tunnus  = '$valm_row[tunnus]'";
            $korjres = pupe_query($query);

            echo "$valm_row[tuoteno]: ".t("Poistetaan informatiivinen valmistusrivi")."!<br>";
          }
        }

        //Laitetaan valmistetut kappaleet takaisin valmistukseen
        if ($taparow["laji"] == "valmistus") {
          $palkpl = $taparow["kpl"];
        }
        else {
          $palkpl = $taparow["kpl"] * -1;
        }

        $query = "UPDATE tilausrivi
                  set varattu = varattu+$palkpl,
                  kpl=kpl-$palkpl
                  where yhtio = '$kukarow[yhtio]'
                  and otunnus = '$laskurow[tunnus]'
                  and tunnus  = '$rivirow[tunnus]'";
        $korjres = pupe_query($query);

        echo "$rivirow[tuoteno]: ".t("Palautetaan")." $palkpl ".t("kappaletta")." ".t("takaisin valmistukseen")."!<br>";

        // Laitetaan takas saldoille
        if ($tuoterow["ei_saldoa"] == "") {
          $query = "UPDATE tuotepaikat
                    set saldo     = saldo - $taparow[kpl],
                    saldoaika     = now()
                    where yhtio   = '$kukarow[yhtio]'
                    and tuoteno   = '$rivirow[tuoteno]'
                    and hyllyalue = '$rivirow[hyllyalue]'
                    and hyllynro  = '$rivirow[hyllynro]'
                    and hyllyvali = '$rivirow[hyllyvali]'
                    and hyllytaso = '$rivirow[hyllytaso]'";
          $korjres = pupe_query($query);

          if (mysql_affected_rows() == 0) {
            echo "<font class='error'>".t("Varastopaikka")." $rivirow[hyllyalue]-$rivirow[hyllynro]-$rivirow[hyllytaso]-$rivirow[hyllyvali] ".t("ei löydy vaikka se on syötetty tilausriville! Koitetaan päivittää oletustapaikkaa!")."</font>";

            $query = "UPDATE tuotepaikat
                      set saldo = saldo - $taparow[kpl],
                      saldoaika    = now()
                      where yhtio  = '$kukarow[yhtio]' and
                      tuoteno      = '$rivirow[tuoteno]' and
                      oletus      != ''
                      limit 1";
            $korjres = pupe_query($query);

            if (mysql_affected_rows() == 0) {

              echo "<br><font class='error'>".t("Tuotteella")." $rivirow[tuoteno] ".t("ei ole oletuspaikkaakaan")."!!!</font><br><br>";

              // haetaan firman eka varasto, tökätään kama sinne ja tehdään siitä oletuspaikka
              $query = "SELECT alkuhyllyalue, alkuhyllynro from varastopaikat where yhtio='$kukarow[yhtio]' AND tyyppi != 'P' order by alkuhyllyalue, alkuhyllynro limit 1";
              $korjres = pupe_query($query);
              $hyllyrow =  mysql_fetch_array($korjres);

              echo "<br><font class='error'>".t("Tehtiin oletuspaikka")." $rivirow[tuoteno]: $rivirow[alkuhyllyalue] $rivirow[alkuhyllynro] 0 0</font><br><br>";

              // lisätään paikka
              $query = "INSERT INTO tuotepaikat set
                        yhtio     = '$kukarow[yhtio]',
                        tuoteno   = '$rivirow[tuoteno]',
                        oletus    = 'X',
                        saldo     = '$taparow[kpl]',
                        saldoaika = now(),
                        hyllyalue = '$hyllyrow[alkuhyllyalue]',
                        hyllynro  = '$hyllyrow[alkuhyllynro]',
                        hyllytaso = '0',
                        hyllyvali = '0'";
              $korjres = pupe_query($query);

              // tehdään tapahtuma
              $query = "INSERT into tapahtuma set
                        yhtio     = '$kukarow[yhtio]',
                        tuoteno   = '$taparow[tuoteno]',
                        kpl       = '0',
                        kplhinta  = '0',
                        hinta     = '0',
                        laji      = 'uusipaikka',
                        selite    = '".t("Lisättiin tuotepaikka")." $hyllyrow[alkuhyllyalue] $hyllyrow[alkuhyllynro] 0 0',
                        laatija   = '$kukarow[kuka]',
                        hyllyalue = '$hyllyrow[alkuhyllyalue]',
                        hyllynro  = '$hyllyrow[alkuhyllynro]',
                        hyllytaso = '0',
                        hyllyvali = '0',
                        laadittu  = now()";
              $korjres = pupe_query($query);
            }
            else {
              echo "<font class='error'>".t("Huh, se onnistui!")."</font><br>";
            }
          }

          echo "$rivirow[tuoteno]: ".t("Päivitetään saldo")." ($taparow[kpl]) ".t("pois tuotepaikalta")."!<br>";
        }

        if ($rivirow["tyyppi"] == "W" or $rivirow["tyyppi"] == "L" or $rivirow["tyyppi"] == "M") {
          //Päivitetään tuotteen kehahinta
          $query = "UPDATE tuote
                    SET kehahin   = '$taparow3[hinta]'
                    where yhtio = '$kukarow[yhtio]'
                    and tuoteno = '$rivirow[tuoteno]'";
          $korjres = pupe_query($query);

          echo "$rivirow[tuoteno]: ".t("Päivitetään tuotteen keskihankintahinta")." ($taparow[hinta] --> $taparow3[hinta]) ".t("takaisin edelliseen arvoon")."!<br>";
        }

        //Päivitetään rivit takaisin keskeneräiseksi ja siivotaan samalla hieman pyöristyseroja jotka on tapahtunut perumisprosessissa
        $query = "UPDATE tilausrivi
                  set toimitettu = '', toimitettuaika='0000-00-00 00:00:00'
                  where yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$rivirow[tunnus]'";
        $korjres = pupe_query($query);

        echo "$rivirow[tuoteno]: ".t("Päivitetään tilausrivi takaisin tilaan ennen valmistusta")."<br>";

        echo "<br><br>";

        $query = "SELECT tunnus
                  FROM tilausrivi
                  WHERE yhtio        = '$kukarow[yhtio]'
                  and otunnus        = $laskurow[tunnus]
                  and toimitettuaika = '0000-00-00 00:00:00'";
        $chkresult1 = pupe_query($query);

        //Eli tilaus on osittain valmistamaton
        if (mysql_num_rows($chkresult1) != 0) {
          //Jos kyseessä on varastovalmistus
          $query = "UPDATE lasku
                    SET tila = 'V', alatila  = 'C'
                    WHERE yhtio = '$kukarow[yhtio]'
                   and tunnus  = $laskurow[tunnus]";
          $chkresult2 = pupe_query($query);

          echo t("Päivitetään valmistus takaisin avoimeksi")."!<br>";
        }
      }
      $vientikerta++;
    }

    if ($tee != 'KORJAA') {
      echo "</tr>";
      echo "<tr><td colspan='2' class='back'><br></td></tr>";
    }
  }
  echo "</table><br><br>";

  if ($tee != 'KORJAA') {

    echo "<input type='hidden' name='tee' value='KORJAA'>";


    echo t("Väkisinkorjaa vaikka korjaaminen ei olisi järjevää").": <input type='checkbox' name='vakisinpoista' value='Kyllä'><br><br>";


    echo t("Näyttää hyvältä ja haluan korjata").":<br><br>";
    echo "<input type='submit' value='".t("Korjaa")."'><br><br>";

    echo "<font class='error'>".t("HUOM: Poistaa vain yhden valmistuksen/osavalmistuksen per tuote per korjausajo")."!</font>";

  }
  else {
    echo "<a href='$PHP_SELF?id=$id'>".t("Näytä valmistus uudestaan")."</a>";
  }

  echo "</form>";
}

if ($id == '') {
  echo "<br><table>";
  echo "<tr>";
  echo "<form method = 'post'>";
  echo "<th>".t("Syöta valmistuksen numero")."</th>";
  echo "<td><input type='text' size='30' name='id'></td>";
  echo "<td><input type='submit' value='".t("Jatka")."'></td></form></tr>";
  echo "</table>";
}


require "inc/footer.inc";
