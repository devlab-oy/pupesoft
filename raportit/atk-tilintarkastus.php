<?php

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
$useslave = 2;

// Ei k‰ytet‰ pakkausta
$compression = FALSE;

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

require "../inc/parametrit.inc";

ini_set("memory_limit", "5G");

$tee = isset($tee) ? trim($tee) : "";
$tositetyyppi_mukaan = isset($tositetyyppi_mukaan) ? trim($tositetyyppi_mukaan) : "";

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}

echo "<script type=\"text/javascript\" charset=\"utf-8\">

    $(document).ready(function() {
      $('.disabloitu').each(function() {
        $(this).attr('disabled', false);
      });
      });

     </script>";

// tehd‰‰n valtiontilintarkastajille atk-tarkistusmateriaalia
echo "<font class='head'>".t("Atk-tilintarkastus")."</font><hr>";

// default arvot yhtiorowlta
if (!isset($alku))  $alku  = substr($yhtiorow['tilikausi_alku'], 0, 4)  - 1 . substr($yhtiorow['tilikausi_alku'], 4);
if (!isset($loppu)) $loppu = substr($yhtiorow['tilikausi_loppu'], 0, 4) - 1 . substr($yhtiorow['tilikausi_loppu'], 4);

if ($alku != '') {
  list($vv, $kk, $pp) = explode("-", $alku);
  if (!checkdate($kk, $pp, $vv)) {
    echo "<font class='error'>Virheellinen alkupvm $alku</font><br><br>";
    $tee = "";
  }
}

if ($loppu != '') {
  list($vv, $kk, $pp) = explode("-", $loppu);
  if (!checkdate($kk, $pp, $vv)) {
    echo "<font class='error'>Virheellinen loppupvm $loppu</font><br><br>";
    $tee = "";
  }
}

echo "<form name='vero' method='post' autocomplete='off'>";
echo "<input type='hidden' name='tee' value='raportti'>";

echo "<table>";
echo "<tr>";
echo "<th>".t("Alkup‰iv‰m‰‰r‰")."</th>";
echo "<td><input type='text' name='alku' value='$alku'></td>";
echo "</tr><tr>";
echo "<th>".t("Loppup‰iv‰m‰‰r‰")."</th>";
echo "<td><input type='text' name='loppu' value='$loppu'></td>";
echo "</tr>";
echo "</table>";

$chk1 = $chk2 = $chk3 = $chk4 = $chk5 = $chk6 = $chk7 = "";

if (!empty($tilikartta_mukaan)) {
  $chk1 = "CHECKED";
}

if (!empty($tarkenteet_mukaan)) {
  $chk2 = "CHECKED";
}

if (!empty($tilioinnit_mukaan)) {
  $chk3 = "CHECKED";
}

if (!empty($myntitilaukset_mukaan)) {
  $chk4 = "CHECKED";
}

if (!empty($myyntilaskut_mukaan)) {
  $chk5 = "CHECKED";
}

if (!empty($ostolaskut_mukaan)) {
  $chk6 = "CHECKED";
}

if (!empty($tositetyyppi_mukaan)) {
  $chk7 = "CHECKED";
}

echo "<br><br>".t("Pakettiin lis‰tt‰v‰t aineistot").":<br>";
echo "<table>";
echo "<tr>";
echo "<th>".t("Tilikartta")."</th>";
echo "<td><input type='checkbox' name='tilikartta_mukaan' $chk1></td>";
echo "</tr><tr>";
echo "<th>".t("Tilikartan tarkenteet")."</th>";
echo "<td><input type='checkbox' name='tarkenteet_mukaan' $chk2></td>";
echo "</tr><tr>";
echo "<th>".t("Tiliˆinnit")."</th>";
echo "<td><input type='checkbox' name='tilioinnit_mukaan' $chk3></td>";
echo "</tr><tr>";
echo "<th>".t("Toimitetut myyntitilaukset")."</th>";
echo "<td><input type='checkbox' name='myntitilaukset_mukaan' $chk4></td>";
echo "</tr><tr>";
echo "<th>".t("Myyntilaskut")."</th>";
echo "<td><input type='checkbox' name='myyntilaskut_mukaan' $chk5></td>";
echo "</tr><tr>";
echo "<th>".t("Ostolaskut")."</th>";
echo "<td><input type='checkbox' name='ostolaskut_mukaan' $chk6></td>";
echo "</tr><tr>";
echo "<td class='back'><br></td>";
echo "</tr><tr>";
echo "<th>".t("Lis‰‰ tositetyyppi kirjanpitoainestoon")."</th>";
echo "<td><input type='checkbox' name='tositetyyppi_mukaan' $chk7></td>";
echo "</tr>";
echo "</table>";

echo "<br><input name='ajonappi' type='submit' value='".t("Aja")."'>";
echo "</form><br><br>";

if ($tee == "raportti") {

  require_once 'inc/ProgressBar.class.php';

  // Tilikartta
  if (!empty($tilikartta_mukaan)) {
    // keksit‰‰n uudelle failille joku varmasti uniikki nimi:
    $file2 = "tilikartta-".md5(uniqid(rand(), true)).".txt";

    // avataan faili
    $fh = fopen("/tmp/".$file2, "w");

    // haetaan kaikki tilit
    $query  = "SELECT *
               FROM tili
               WHERE yhtio = '$kukarow[yhtio]'
               ORDER BY tilino";
    $result = pupe_query($query);

    echo "<br>Haetaan tilikartta....<br>";
    $bar = new ProgressBar();
    $elements = mysql_num_rows($result); // total number of elements to process
    $bar->initialize($elements); // print the empty bar

    while ($row = mysql_fetch_assoc($result)) {

      $bar->increase();

      $rivi  = sprintf("%-6.6s",   $row['tilino']);    // tilinumero 6 merkki‰
      $rivi .= sprintf("%-35.35s", $row['nimi']);      // selite 35 merkki‰
      $rivi .= "\n";                  // windows rivinvaihto (cr lf)

      fwrite($fh, $rivi);
    }

    // suljetaan tiedosto
    fclose($fh);
  }

  // Kustannuspaikat, projektit ja kohteet
  if (!empty($tarkenteet_mukaan)) {
    // keksit‰‰n uudelle failille joku varmasti uniikki nimi:
    $file3 = "kustprojkoht-".md5(uniqid(rand(), true)).".txt";

    // avataan faili
    $fh = fopen("/tmp/".$file3, "w");

    // haetaan kaikki kustannuspaikat
    $query  = "SELECT *
               FROM kustannuspaikka
               WHERE yhtio   = '$kukarow[yhtio]'
               AND kaytossa != 'E'
               ORDER BY tyyppi, koodi+0, koodi, nimi";
    $result = pupe_query($query);

    echo "<br>Haetaan tarkenteet....<br>";
    $bar = new ProgressBar();
    $elements = mysql_num_rows($result); // total number of elements to process
    $bar->initialize($elements); // print the empty bar

    while ($row = mysql_fetch_assoc($result)) {

      $bar->increase();

      if ($row['tyyppi'] == 'K') {
        $tyyppi = "Kustannuspaikka";
      }
      elseif ($row['tyyppi'] == 'O') {
        $tyyppi = "Kohde";
      }
      elseif ($row['tyyppi'] == 'P') {
        $tyyppi = "Projekti";
      }
      else {
        $tyyppi = "";
      }

      $rivi  = sprintf("%-15.15s", $tyyppi);        // tyyppi 15 merkki‰
      $rivi .= sprintf("%11.11s",  $row['tunnus']);    // tunnus 11 merkki‰
      $rivi .= sprintf("%-35.35s", $row['nimi']);      // nimi 35 merkki‰
      $rivi .= "\n";                  // windows rivinvaihto (cr lf)

      fwrite($fh, $rivi);
    }

    fclose($fh);
  }

  // Tiliˆinnit
  if (!empty($tilioinnit_mukaan)) {
    // keksit‰‰n uudelle failille joku varmasti uniikki nimi:
    $file1 = "tilioinnit-".md5(uniqid(rand(), true)).".txt";

    // avataan faili
    $fh = fopen("/tmp/".$file1, "w");

    // haetaan kaikki vuoden tapahtumat.. uh
    $query  = "SELECT tiliointi.tapvm, tiliointi.tilino, tiliointi.kustp, tiliointi.kohde, tiliointi.projekti,
               tiliointi.summa, tiliointi.vero, tiliointi.selite, tiliointi.laatija,
               tiliointi.laadittu, lasku.ytunnus, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.osoitetark, lasku.postino, lasku.postitp, lasku.alatila, lasku.tila
               FROM tiliointi
               JOIN lasku ON lasku.yhtio=tiliointi.yhtio and lasku.tunnus=tiliointi.ltunnus
               where tiliointi.yhtio  = '$kukarow[yhtio]'
               and tiliointi.tapvm    >= '$alku'
               and tiliointi.tapvm    <= '$loppu'
               and tiliointi.korjattu = ''";
    $result = pupe_query($query);


    echo "<br>Haetaan tiliˆinnit....<br>";
    $bar = new ProgressBar();
    $elements = mysql_num_rows($result); // total number of elements to process
    $bar->initialize($elements); // print the empty bar

    while ($row = mysql_fetch_assoc($result)) {

      $bar->increase();

      $rivi  = sprintf("%-10.10s", $row['tapvm']);    // p‰iv‰m‰‰r‰ 10 merkki‰ (vvvv-kk-pp)
      $rivi .= sprintf("%6.6s",    $row['tilino']);    // tilinumero 6 merkki‰
      $rivi .= sprintf("%6.6s",    $row['kustp']);    // kustannuspaikka 6 merkki‰
      $rivi .= sprintf("%6.6s",    $row['kohde']);    // kohde 6 merkki‰
      $rivi .= sprintf("%6.6s",    $row['projekti']);    // projekti 6 merkki‰
      $rivi .= sprintf("%13.13s",  $row['summa']);    // summa 13 merkki‰ (desimaalierotin piste)
      $rivi .= sprintf("%7.7s",    $row['vero']);      // vero 7 merkki‰ (desimaalierotin piste)
      $rivi .= sprintf("%-50.50s", $row['selite']);    // selite 50 merkki‰
      $rivi .= sprintf("%-10.10s", $row['laatija']);    // laatijan nimi 10 merkki‰
      $rivi .= sprintf("%-19.19s", $row['laadittu']);    // laadittuaika 19 merkki‰ (vvvv-kk-pp hh:mm:ss)
      $rivi .= sprintf("%-15.15s", $row['ytunnus']);    // asiakkaan/toimittajan tunniste 15 merkki‰
      $rivi .= sprintf("%-45.45s", $row['nimi']);      // asiakkaan/toimittajan nimi 45 merkki‰
      $rivi .= sprintf("%-45.45s", $row['nimitark']);    // asiakkaan/toimittajan nimitarkenne 45 merkki‰
      $rivi .= sprintf("%-45.45s", $row['osoite']);    // asiakkaan/toimittajan osoite 45 merkki‰
      $rivi .= sprintf("%-45.45s", $row['osoitetark']);  // asiakkaan/toimittajan osoitetarkenne 45 merkki‰
      $rivi .= sprintf("%-15.15s", $row['postino']);    // asiakkaan/toimittajan postinumero 15 merkki‰
      $rivi .= sprintf("%-45.45s", $row['postitp']);    // asiakkaan/toimittajan postitoimipaikka 45 merkki‰

      if ($tositetyyppi_mukaan != "") {
        // Laitetaan tositetyyppi mukaan selkokielisen‰
        if ($row["tila"] == "U") {
          $rivi .= sprintf("%-11.11s", "Myynti");
        }
        elseif (in_array($row["tila"], array("H", "Y", "M", "P", "Q"))) {
          $rivi .= sprintf("%-11.11s", "Osto");
        }
        elseif ($row["tila"] == "X") {
          switch ($row["alatila"]) {
          case "E":
            $rivi .= sprintf("%-11.11s", "Ep‰kurantointi");
            break;
          case "I":
            $rivi .= sprintf("%-11.11s", "Inventointi");
            break;
          case "G":
            $rivi .= sprintf("%-11.11s", "Varastosiirto");
            break;
          case "K":
            $rivi .= sprintf("%-11.11s", "Kassalippaan t‰sm‰ytys");
            break;
          case "O":
            $rivi .= sprintf("%-11.11s", "K‰teisotto");
            break;
          case "T":
            $rivi .= sprintf("%-11.11s", "Tilikauden tulos");
            break;
          case "A":
            $rivi .= sprintf("%-11.11s", "Avaava tase");
            break;
          default :
            $rivi .= sprintf("%-11.11s", "Muistiotosite");
          }
        }
        else {
          $rivi .= sprintf("%-11.11s", $row["tila"]);
        }
      }

      $rivi .= "\n";

      fwrite($fh, $rivi);
    }

    // suljetaan tiedosto
    fclose($fh);
  }

  // Myyntitilaukset
  if (!empty($myntitilaukset_mukaan)) {
    // keksit‰‰n uudelle failille joku varmasti uniikki nimi:
    $file4 = "toimitukset-".md5(uniqid(rand(), true)).".txt";
    $file5 = "toimitukset_rivit-".md5(uniqid(rand(), true)).".txt";

    // avataan faili
    $fh = fopen("/tmp/".$file4, "w");
    $fhr = fopen("/tmp/".$file5, "w");

    fwrite($fh, "toimitus_tunnus|laskunro|luontiaika|pvm|verollinen_summa|veroton_summa|verollinen_summa_valuutassa|veroton_summa_valuutassa|valuutta|toimitusehto|asiakasnumero|hyvitysviesti\n");
    fwrite($fhr, "lasku_tunnus|toimitus_tunnus|tuoteno|nimitys|kpl|verollinen_rivihinta|veroton_rivihinta|vero|toimitettu\n");

    $query = "SELECT lasku.tunnus, lasku.laskunro, lasku.luontiaika, lasku.tapvm, lasku.summa, asiakas.asiakasnro,
              concat_ws(' ', lasku.nimi, lasku.nimitark) nimi, if(lasku.clearing = 'HYVITYS', lasku.viesti,'') viesti,
              lasku.summa_valuutassa, lasku.valkoodi, lasku.toimitusehto, lasku.arvo, lasku.arvo_valuutassa,
              avg(date_format(tilausrivi.toimitettuaika, '%Y%m%d%H%i%s')) toimitettuaika
              FROM tilausrivi
              JOIN lasku ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus)
              LEFT JOIN asiakas ON (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus)
              WHERE tilausrivi.yhtio        = '{$kukarow['yhtio']}'
              AND tilausrivi.tyyppi         = 'L'
              AND tilausrivi.toimitettuaika >= '$alku 00:00:00'
              AND tilausrivi.toimitettuaika <= '$loppu 23:59:59'
              GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13
              ORDER BY toimitettuaika";
    $result = pupe_query($query);

    echo "<br>Haetaan toimitukset....<br>";
    $bar = new ProgressBar();
    $elements = mysql_num_rows($result); // total number of elements to process
    $bar->initialize($elements); // print the empty bar

    while ($row = mysql_fetch_assoc($result)) {

      $bar->increase();

      fwrite($fh, "{$row['tunnus']}|{$row['laskunro']}|{$row['luontiaika']}|{$row['tapvm']}|{$row['summa']}|{$row['arvo']}|{$row['summa_valuutassa']}|{$row['arvo_valuutassa']}|{$row['valkoodi']}|{$row['toimitusehto']}|{$row['asiakasnro']}|{$row['viesti']}\n");

      $query = "SELECT tilausrivi.uusiotunnus, tilausrivi.otunnus, tilausrivi.tuoteno, tilausrivi.nimitys, tilausrivi.kpl, tilausrivi.rivihinta, tilausrivi.alv, tilausrivi.toimitettuaika
                FROM tilausrivi
                WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                AND tilausrivi.otunnus = '{$row['tunnus']}'
                AND tilausrivi.tyyppi  = 'L'";
      $rivires = pupe_query($query);

      while ($rivirow = mysql_fetch_assoc($rivires)) {
        $verollinen_rivihinta = sprintf('%.2f', round($rivirow['rivihinta'] * (1+($rivirow['alv']/100)), 2));

        fwrite($fhr, "{$rivirow['uusiotunnus']}|{$rivirow['otunnus']}|{$rivirow['tuoteno']}|{$rivirow['nimitys']}|{$rivirow['kpl']}|{$verollinen_rivihinta}|{$rivirow['rivihinta']}|{$rivirow['alv']}|{$rivirow['toimitettuaika']}\n");
      }
    }

    fclose($fh);
    fclose($fhr);
  }

  // Myyntilaskut
  if (!empty($myyntilaskut_mukaan)) {
    // keksit‰‰n uudelle failille joku varmasti uniikki nimi:
    $file6 = "laskut-".md5(uniqid(rand(), true)).".txt";
    $file7 = "laskut_rivit-".md5(uniqid(rand(), true)).".txt";

    // avataan faili
    $fh = fopen("/tmp/".$file6, "w");
    $fhr = fopen("/tmp/".$file7, "w");

    fwrite($fh, "lasku_tunnus|laskunro|luontiaika|pvm|verollinen_summa|veroton_summa|verollinen_summa_valuutassa|veroton_summa_valuutassa|valuutta|toimitusehto|asiakasnumero|hyvitysviesti\n");
    fwrite($fhr, "lasku_tunnus|toimitus_tunnus|tuoteno|nimitys|kpl|verollinen_rivihinta|veroton_rivihinta|vero|toimitettu\n");

    $query = "SELECT lasku.tunnus, lasku.laskunro, lasku.luontiaika, lasku.tapvm, lasku.summa, asiakas.asiakasnro,
              concat_ws(' ', lasku.nimi, lasku.nimitark) nimi, if(lasku.clearing = 'HYVITYS', lasku.viesti,'') viesti,
              lasku.summa_valuutassa, lasku.valkoodi, lasku.toimitusehto, lasku.arvo, lasku.arvo_valuutassa
              FROM lasku
              LEFT JOIN asiakas ON (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus)
              WHERE lasku.yhtio = '{$kukarow['yhtio']}'
              AND lasku.tila    = 'U'
              AND lasku.alatila = 'X'
              AND lasku.tapvm   >= '$alku'
              AND lasku.tapvm   <= current_date
              ORDER BY lasku.laskunro";
    $result = pupe_query($query);

    echo "<br>Haetaan myyntilaskut....<br>";
    $bar = new ProgressBar();
    $elements = mysql_num_rows($result); // total number of elements to process
    $bar->initialize($elements); // print the empty bar

    while ($row = mysql_fetch_assoc($result)) {

      $bar->increase();

      fwrite($fh, "{$row['tunnus']}|{$row['laskunro']}|{$row['luontiaika']}|{$row['tapvm']}|{$row['summa']}|{$row['arvo']}|{$row['summa_valuutassa']}|{$row['arvo_valuutassa']}|{$row['valkoodi']}|{$row['toimitusehto']}|{$row['asiakasnro']}|{$row['viesti']}\n");

      $query = "SELECT tilausrivi.uusiotunnus, tilausrivi.otunnus, tilausrivi.tuoteno, tilausrivi.nimitys, tilausrivi.kpl, tilausrivi.rivihinta, tilausrivi.alv, tilausrivi.toimitettuaika
                FROM tilausrivi
                WHERE tilausrivi.yhtio     = '{$kukarow['yhtio']}'
                AND tilausrivi.uusiotunnus = '{$row['tunnus']}'
                AND tilausrivi.tyyppi      = 'L'";
      $rivires = pupe_query($query);

      while ($rivirow = mysql_fetch_assoc($rivires)) {
        $verollinen_rivihinta = sprintf('%.2f', round($rivirow['rivihinta'] * (1+($rivirow['alv']/100)), 2));

        fwrite($fhr, "{$rivirow['uusiotunnus']}|{$rivirow['otunnus']}|{$rivirow['tuoteno']}|{$rivirow['nimitys']}|{$rivirow['kpl']}|{$verollinen_rivihinta}|{$rivirow['rivihinta']}|{$rivirow['alv']}|{$rivirow['toimitettuaika']}\n");
      }
    }

    fclose($fh);
    fclose($fhr);
  }

  // Ostolaskut
  if (!empty($ostolaskut_mukaan)) {
    // keksit‰‰n uudelle failille joku varmasti uniikki nimi:
    $file8 = "ostolaskut-".md5(uniqid(rand(), true)).".txt";
    $file9 = "ostolaskut_rivit-".md5(uniqid(rand(), true)).".txt";

    // avataan faili
    $fh = fopen("/tmp/".$file8, "w");
    $fhr = fopen("/tmp/".$file9, "w");

    fwrite($fh, "lasku_tunnus|laskunro|luontiaika|pvm|summa|valuutta|summa_{$yhtiorow['valkoodi']}|toimittajanro|nimi|toimittajatyyppi|laskun_tyyppi|laskun_tila\n");
    fwrite($fhr, "lasku_tunnus|tuoteno|nimitys|kpl|rivihinta|varastonarvo|saapuminen\n");

    $query = "SELECT lasku.tunnus, lasku.laskunro, lasku.luontiaika, lasku.tapvm,
              lasku.summa, round(lasku.summa * if(lasku.maksu_kurssi = 0, lasku.vienti_kurssi, lasku.maksu_kurssi), 2) kotisumma,
              toimi.toimittajanro, concat_ws(' ', lasku.nimi, lasku.nimitark) nimi, lasku.viesti,
              lasku.valkoodi, toimi.tyyppi, lasku.vienti, lasku.tila
              FROM lasku
              LEFT JOIN toimi ON (toimi.yhtio = lasku.yhtio and toimi.tunnus = lasku.liitostunnus)
              WHERE lasku.yhtio = '{$kukarow['yhtio']}'
              AND lasku.tila   in ('H','Y','M','P','Q')
              AND lasku.tapvm  >= '$alku'
              AND lasku.tapvm  <= '$loppu'
              ORDER BY lasku.tapvm, lasku.luontiaika";
    $result = pupe_query($query);

    echo "<br>Haetaan ostolaskut....<br>";
    $bar = new ProgressBar();
    $elements = mysql_num_rows($result); // total number of elements to process
    $bar->initialize($elements); // print the empty bar

    $query_ale_lisa = generoi_alekentta('O');

    // Haetaan
    $haetut_saapumiset = array();

    while ($row = mysql_fetch_assoc($result)) {

      $bar->increase();

      fwrite($fh, "{$row['tunnus']}|{$row['laskunro']}|{$row['luontiaika']}|{$row['tapvm']}|{$row['summa']}|{$row['valkoodi']}|{$row['kotisumma']}|{$row['toimittajanro']}|{$row['nimi']}|{$row['tyyppi']}|{$row['vienti']}|{$row['tila']}\n");

      // Haetaan saapumisen rivi vain jos on vaihto-omaisuuslasku kysesss‰
      if (in_array($row["vienti"], array('C', 'F', 'I', 'J', 'K', 'L'))) {
        // katotaan onko lasku liitetty saapumiseen
        $query = "SELECT keikka.laskunro, keikka.tunnus, keikka.vienti_kurssi, keikka.liitostunnus
                  FROM lasku
                  JOIN lasku keikka ON (keikka.yhtio = lasku.yhtio and keikka.laskunro = lasku.laskunro and keikka.tila = 'K' and keikka.vanhatunnus = 0)
                  WHERE lasku.yhtio     = '$kukarow[yhtio]'
                  AND lasku.tila        = 'K'
                  AND lasku.vanhatunnus = '$row[tunnus]'";
        $keikres = pupe_query($query);

        while ($keikrow = mysql_fetch_assoc($keikres)) {

          // Ostolaskut voivat olla liitetty useaan saapumiseen, tai yhteen saapumiseen voi olla liitetty useita laskuja.
          // Hateaan tietty saapuminen kuitenkin vain kerran
          if (!isset($haetut_saapumiset[$keikrow['tunnus']])) {

            $haetut_saapumiset[$keikrow['tunnus']] = TRUE;

            $query = "SELECT tilausrivi.uusiotunnus, tilausrivi.otunnus, tilausrivi.tuoteno, tilausrivi.nimitys, tilausrivi.kpl, tilausrivi.rivihinta, tilausrivi.alv, tilausrivi.toimitettuaika,
                      round((tilausrivi.varattu+tilausrivi.kpl)*tilausrivi.hinta*if($keikrow[vienti_kurssi]=0, 1, $keikrow[vienti_kurssi])*if(tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin)*{$query_ale_lisa}, 2) rivihinta,
                      round(tilausrivi.rivihinta, 2) varastonarvo
                      FROM tilausrivi
                      LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.liitostunnus='$keikrow[liitostunnus]'
                      WHERE tilausrivi.yhtio     = '{$kukarow['yhtio']}'
                      AND tilausrivi.uusiotunnus = '{$keikrow['tunnus']}'
                      AND tilausrivi.tyyppi      = 'O'";
            $rivires = pupe_query($query);

            while ($rivirow = mysql_fetch_assoc($rivires)) {
              fwrite($fhr, "{$row['tunnus']}|{$rivirow['tuoteno']}|{$rivirow['nimitys']}|{$rivirow['kpl']}|{$rivirow['rivihinta']}|{$rivirow['varastonarvo']}|{$keikrow['laskunro']}\n");
            }
          }
        }
      }
    }

    fclose($fh);
    fclose($fhr);
  }


  $zipfile = "Tilintarkastus-{$kukarow["yhtio"]}.zip";

  // tehd‰‰n failista zippi
  chdir("/tmp");

  // Dellataan vanha jos sellanen lˆytyy
  @unlink("/tmp/$zipfile");

  $komento  = "/usr/bin/zip $zipfile ";

  if (!empty($tilioinnit_mukaan)) $komento .= escapeshellarg($file1)." ";
  if (!empty($tilikartta_mukaan)) $komento .= escapeshellarg($file2)." ";
  if (!empty($tarkenteet_mukaan)) $komento .= escapeshellarg($file3)." ";
  if (!empty($myntitilaukset_mukaan)) $komento .= escapeshellarg($file4)." ";
  if (!empty($myntitilaukset_mukaan)) $komento .= escapeshellarg($file5)." ";
  if (!empty($myyntilaskut_mukaan)) $komento .= escapeshellarg($file6)." ";
  if (!empty($myyntilaskut_mukaan)) $komento .= escapeshellarg($file7)." ";
  if (!empty($ostolaskut_mukaan)) $komento .= escapeshellarg($file8)." ";
  if (!empty($ostolaskut_mukaan)) $komento .= escapeshellarg($file9)." ";

  exec($komento);

  if (!empty($tilioinnit_mukaan)) unlink($file1);
  if (!empty($tilikartta_mukaan)) unlink($file2);
  if (!empty($tarkenteet_mukaan)) unlink($file3);
  if (!empty($myntitilaukset_mukaan)) unlink($file4);
  if (!empty($myntitilaukset_mukaan)) unlink($file5);
  if (!empty($myyntilaskut_mukaan)) unlink($file6);
  if (!empty($myyntilaskut_mukaan)) unlink($file7);
  if (!empty($ostolaskut_mukaan)) unlink($file8);
  if (!empty($ostolaskut_mukaan)) unlink($file9);

  echo "<br><br><table>";
  echo "<tr><th>".t("Tallenna tulos").":</th>";
  echo "<form method='post' class='multisubmit'>";
  echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
  echo "<input type='hidden' name='kaunisnimi' value='Tilintarkastus-$kukarow[yhtio].zip'>";
  echo "<input type='hidden' name='tmpfilenimi' value='Tilintarkastus-$kukarow[yhtio].zip'>";
  echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
  echo "</table><br>";

}

// kursorinohjausta
$formi  = "vero";
$kentta = "alku";

require "inc/footer.inc";
