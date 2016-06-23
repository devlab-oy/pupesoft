<?php

// haetaan keikan tiedot
$query    = "SELECT * FROM lasku WHERE tunnus='$otunnus' AND yhtio ='$kukarow[yhtio]'";
$result   = pupe_query($query);
$laskurow = mysql_fetch_array($result);

// yhdistet‰‰n ruksatut keikat "is‰saapumiseen" $otunnus
if ($tee == "teeyhdistys") {

  if (count($yhdistakeikka) > 0) {

    foreach ($yhdistakeikka as $keikkatunnus) {

      $query = "select laskunro from lasku where yhtio='$kukarow[yhtio]' and tila='K' and tunnus='$keikkatunnus'";
      $result = pupe_query($query);

      if (mysql_num_rows($result) == 1) {
        $keikkarow = mysql_fetch_assoc($result);

        if ($yhtiorow['suuntalavat'] != '') {

          // Siirret‰‰n myˆs suuntalavat
          $query = "SELECT GROUP_CONCAT(DISTINCT suuntalava) AS suuntalavat
                    FROM tilausrivi
                    WHERE yhtio      = '{$kukarow['yhtio']}'
                    AND tyyppi       = 'O'
                    AND uusiotunnus  = '{$keikkatunnus}'
                    AND suuntalava  != 0";
          $suuntalava_res = pupe_query($query);
          $suuntalava_row = mysql_fetch_assoc($suuntalava_res);

          if (trim($suuntalava_row['suuntalavat']) != '') {
            foreach (explode(",", $suuntalava_row['suuntalavat']) as $_suuntalava) {

              // Tsekataan ettei jo ole
              $query = "SELECT tunnus
                        FROM suuntalavat_saapuminen
                        WHERE yhtio    = '{$kukarow['yhtio']}'
                        AND suuntalava = '{$_suuntalava}'
                        AND saapuminen = '{$otunnus}'";
              $check_res = pupe_query($query);

              // Jos relaatiota ei ole niin luodaan se, muuten poistetaan
              // koska suuntalavat_saapuminen relaatiota ei saa olla kuin kerran
              // per saapuminen ja suuntalava
              if (mysql_num_rows($check_res) == 0) {
                $query = "UPDATE suuntalavat_saapuminen SET
                          saapuminen     = '{$otunnus}',
                          muuttaja       = '{$kukarow['kuka']}',
                          muutospvm      = now()
                          WHERE yhtio    = '{$kukarow['yhtio']}'
                          AND suuntalava = '{$_suuntalava}'
                          AND saapuminen = '{$keikkatunnus}'";
                $upd_res = pupe_query($query);
              }
              else {
                $query = "DELETE FROM suuntalavat_saapuminen
                          WHERE yhtio    = '{$kukarow['yhtio']}'
                          AND suuntalava = '{$_suuntalava}'
                          AND saapuminen = '{$keikkatunnus}'";
                $del_res = pupe_query($query);
              }
            }
          }
        }

        //p‰ivitet‰‰n otunnus jo kohdistetuille riveille jotta ne ev‰t j‰isi ilman otsikkoa
        $query = "update tilausrivi set otunnus='$otunnus' where yhtio='$kukarow[yhtio]' and tyyppi='O' and uusiotunnus='$keikkatunnus' and otunnus=uusiotunnus order by yhtio";
        $result = pupe_query($query);

        //p‰ivitet‰‰n otunnus niille riveille joiden otunnus pointtaa dellattavaan otsikkoon jotta ne ev‰t j‰isi ilman otsikkoa
        $query = "update tilausrivi set otunnus='$otunnus' where yhtio='$kukarow[yhtio]' and tyyppi='O' and otunnus='$keikkatunnus' order by yhtio";
        $result = pupe_query($query);

        //liitet‰‰n jo liitetyt rivit uuteen otsikkoon
        $query = "update tilausrivi set uusiotunnus='$otunnus' where yhtio='$kukarow[yhtio]' and tyyppi='O' and uusiotunnus='$keikkatunnus' order by yhtio";
        $result = pupe_query($query);

        //poistetaan keikat, mutta ei en‰‰ sanota delete from
        $komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mit‰tˆitiin ohjelmassa ostotilausten_rivien_yhdistys.inc (1)")."<br>";
        $query = "update lasku set alatila='K', tila='D',  comments = '$komm' where yhtio='$kukarow[yhtio]' and tila='K' and laskunro='$keikkarow[laskunro]' and vanhatunnus = 0";
        $result = pupe_query($query);

        //liitet‰‰n liitosotsikot uudestaan
        $query = "update lasku set laskunro = '$laskurow[laskunro]' where yhtio='$kukarow[yhtio]' and tila='K' and laskunro='$keikkarow[laskunro]' and vanhatunnus != 0";
        $result = pupe_query($query);

        //Varmistetaan ett‰ keikka ei ole merkitty kohdistetuks
        $query = "update lasku set kohdistettu = '' where yhtio='$kukarow[yhtio]' and tila='K' and tunnus = '$otunnus'";
        $result = pupe_query($query);
      }
      else {
        echo "<font class='error'>".t("Saapuminen katosi, mit‰‰n ei tehty")."!</font><br><br>";
      }
    }

    // takaisin selailuun
    $toiminto = "";
  }
  else {
    echo "<font class='error'>".t("Et valinnut yht‰‰n saapumista")."!</font><br><br>";
    $tee = "";
  }
}

if ($tee == "") {

  // n‰ytet‰‰n v‰h‰ toimittajan tietoja
  echo "<table>";
  echo "<tr>";
  echo "<th>".t("Saapuminen")."</th>";
  echo "<th colspan='5'>".t("Toimittaja")."</th>";
  echo "</tr><tr>";
  echo "<td>$laskurow[laskunro]</td>";
  echo "<td>$laskurow[ytunnus]</td>";
  echo "<td>$laskurow[nimi]</td>";
  echo "<td>$laskurow[osoite]</td>";
  echo "<td>$laskurow[postino]</td>";
  echo "<td>$laskurow[postitp]</td>";
  echo "</tr></table><br>";

  // etsit‰‰n vanhoja keikkoja, vanhatunnus pit‰‰ olla tyhj‰‰ niin ei listata liitettyj‰ laskuja ja tunnus eri kun t‰‰ meid‰n nykynen
  $query = "  select *
        from lasku
        where yhtio='$kukarow[yhtio]' and liitostunnus='$toimittajaid' and tila='K' and alatila='' and kohdistettu in ('', 'K') and vanhatunnus=0
        order by laskunro asc";

  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {

    echo "<font class='head'>".t("Toimittajan saapumiset")."</font><hr>";

    echo "<form name='formi' method='post'>";
    echo "<input type='hidden' name='toiminto' value='yhdista'>";
    echo "<input type='hidden' name='otunnus' value='$otunnus'>";
    echo "<input type='hidden' name='tee' value='teeyhdistys'>";
    echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
    echo "<input type='hidden' name='ytunnus' value='$laskurow[ytunnus]'>";

    echo "<table>";
    echo "<tr>";
    echo "<th>".t("saapuminen")."</th>";
    echo "<th>".t("ytunnus")."</th>";
    echo "<th>".t("nimi")."</th>";
    echo "<th>".t("kohdistettu")."</th>";
    echo "<th>".t("varastossa")."</th>";
    echo "<th>".t("kululaskuja")."</th>";
    echo "<th>".t("ostolaskuja")."</th>";
    echo "<th>".t("yhdist‰")."</th>";
    echo "</tr>";

    $keikkakesken = 0;

    $lock_params = array(
      "locktime" => 0,
      "lockfile" => "$kukarow[yhtio]-keikka.lock",
      "return"   => TRUE
    );

    if (!pupesoft_flock($lock_params)) {
      list($keikkakesken, $_kuka, $_timestamp) = explode(";", file_get_contents("/tmp/$kukarow[yhtio]-keikka.lock"));
    }

    while ($row = mysql_fetch_array($result)) {

      // tutkitaan onko kaikilla tuotteilla on joku varastopaikka
      $query  = "select * from tilausrivi where yhtio='$kukarow[yhtio]' and uusiotunnus='$row[tunnus]' and tyyppi='O'";
      $tilres = pupe_query($query);

      $kplyhteensa = 0;  // apumuuttuja
      $kplvarasto  = 0;  // apumuuttuja

      while ($rivirow = mysql_fetch_array($tilres)) {
        $query = "select * from tuote where tuoteno='$rivirow[tuoteno]' and yhtio='$kukarow[yhtio]'";
        $tuore = pupe_query($query);
        $tuote = mysql_fetch_array($tuore);

        $kplyhteensa++; // lasketaan montako tilausrivi‰ on kohdistettu

        if ($rivirow["kpl"] != 0 and $rivirow["varattu"] == 0) {
          $kplvarasto++; // lasketaan montako tilausrivi‰ on viety varastoon
        }
      }

      // katotaan onko liitettyj‰ laskuja
      // ('C','F','I','J','K','L') // vaihto-omaisuus ja raaka-aine
      // ('B','C','J','E','F','K','H','I','L') // kaikki

      $query = "  select count(*) num,
            sum(if(vienti='C' or vienti='F' or vienti='I' or vienti='J' or vienti='K' or vienti='L',1,0)) volasku,
            sum(if(vienti!='C' and vienti!='F' and vienti!='I' and vienti!='J' and vienti!='K' and vienti!='L',1,0)) kulasku,
            sum(if(vienti='C' or vienti='F' or vienti='I' or vienti='J' or vienti='K' or vienti='L',summa,0)) vosumma,
            sum(if(vienti!='C' and vienti!='F' and vienti!='I' and vienti!='J' and vienti!='K' and vienti!='L',arvo,0)) kusumma
            from lasku
            where yhtio='$kukarow[yhtio]' and tila='K' and vanhatunnus<>0 and laskunro='$row[laskunro]'";
      $llres = pupe_query($query);
      $llrow = mysql_fetch_array($llres);

      if ($llrow["vosumma"] != 0) $llrow["vosumma"] = "($llrow[vosumma])"; else $llrow["vosumma"]=""; // kaunistellaan summa sulkuihin
      if ($llrow["kusumma"] != 0) $llrow["kusumma"] = "($llrow[kusumma])"; else $llrow["kusumma"]=""; // kaunistellaan summa sulkuihin
      if ($llrow["volasku"] == "") $llrow["volasku"] = 0; // kaunistellaan tyhj‰t nollaks
      if ($llrow["kulasku"] == "") $llrow["kulasku"] = 0; // kaunistellaan tyhj‰t nollaks

      if ($row["tunnus"] == $otunnus) {
        echo "<tr>";
        echo "<td class='spec'>$row[laskunro]</td>";
        echo "<td class='spec'>$row[ytunnus]</td>";
        echo "<td class='spec'>$row[nimi]</td>";
        echo "<td class='spec'>$kplyhteensa</td>";
        echo "<td class='spec'>$kplvarasto</td>";
        echo "<td class='spec'>$llrow[kulasku] $llrow[kusumma]</td>";
        echo "<td class='spec'>$llrow[volasku] $llrow[vosumma]</td>";
        echo "<td class='spec'>".t("kohde")."</td>";
        echo "</tr>";
      }
      else {
        echo "<tr>";
        echo "<td>$row[laskunro]</td>";
        echo "<td>$row[ytunnus]</td>";
        echo "<td>$row[nimi]</td>";
        echo "<td>$kplyhteensa</td>";
        echo "<td>$kplvarasto</td>";
        echo "<td>$llrow[kulasku] $llrow[kusumma]</td>";
        echo "<td>$llrow[volasku] $llrow[vosumma]</td>";

        // keikkaa ollaan viem‰ss‰ varastoon
        if ($keikkakesken == $row["tunnus"]) {
          echo "<td>".t("Varastoonvienti kesken")." ".t("k‰ytt‰j‰ll‰")." {$_kuka} @ {$_timestamp}</td>";
        }
        else {
          echo "<td><input type='checkbox' name='yhdistakeikka[]' value='$row[tunnus]'></td>";
        }
        echo "</tr>";
      }
    }

    echo "</table>";

    echo "<br><input type='submit' value='".t("Liit‰ valitut saapumiset")."'>";
    echo "</form>";

    if ($toiminto != "") {
      echo "<hr>";
      echo "<form method='post'>";
      echo "<input type='hidden' name='toiminto' value=''>";
      echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
      echo "<input type='hidden' name='ytunnus' value='$laskurow[ytunnus]'>";
      echo "<input type='submit' value='".t("Takaisin saapumiselle")."'>";
      echo "</form>";
    }

  }
  else {
    echo "<font class='message'>".t("Ei yht‰‰n liitett‰v‰ksi sopivaa tilausta").".</font>";
  }

}
