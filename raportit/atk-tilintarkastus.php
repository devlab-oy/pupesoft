<?php

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
$useslave = 1;

// Ei k‰ytet‰ pakkausta
$compression = FALSE;

if (isset($_POST["tee"])) {
  if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
}

require("../inc/parametrit.inc");

ini_set("memory_limit", "5G");

$tee = isset($tee) ? trim($tee) : "";
$tositetyyppi_mukaan = isset($tositetyyppi_mukaan) ? trim($tositetyyppi_mukaan) : "";

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  unlink("/tmp/".$tmpfilenimi);
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
if (!isset($alku))  $alku  = substr($yhtiorow['tilikausi_alku'],0,4)  - 1 . substr($yhtiorow['tilikausi_alku'],4);
if (!isset($loppu)) $loppu = substr($yhtiorow['tilikausi_loppu'],0,4) - 1 . substr($yhtiorow['tilikausi_loppu'],4);

$chk = "";
if ($tositetyyppi_mukaan != "") {
  $chk = "CHECKED";
}

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
echo "<th>Anna alkupvm</th>";
echo "<td><input disabled='true' class='disabloitu'type='text' name='alku' value='$alku'></td>";
echo "</tr><tr>";
echo "<th>Anna alkupvm</th>";
echo "<td><input disabled='true' class='disabloitu' type='text' name='loppu' value='$loppu'></td>";
echo "</tr><tr>";
echo "<th>Lis‰‰ tositetyyppi ainestoon</th>";
echo "<td><input disabled='true' class='disabloitu' type='checkbox' name='tositetyyppi_mukaan' $chk></td>";
echo "</tr>";
echo "</table>";
echo "<br><input name='ajonappi' type='submit' value='Aja' disabled='true' class='disabloitu'>";
echo "</form><br><br>";

if ($tee == "raportti") {

  require_once ('inc/ProgressBar.class.php');

  /* tilikartta */

  // keksit‰‰n uudelle failille joku varmasti uniikki nimi:
  $file2 = "tilikartta-".md5(uniqid(rand(),true)).".txt";

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

  /* tiliˆinnit */

  // keksit‰‰n uudelle failille joku varmasti uniikki nimi:
  $file1 = "tilioinnit-".md5(uniqid(rand(),true)).".txt";

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
        switch($row["alatila"]) {
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

  /* kustannuspaikat, projektit ja kohteet */

  // keksit‰‰n uudelle failille joku varmasti uniikki nimi:
  $file3 = "kustprojkoht-".md5(uniqid(rand(),true)).".txt";

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

  // Tilaukset

  // keksit‰‰n uudelle failille joku varmasti uniikki nimi:
  $file4 = "toimitukset-".md5(uniqid(rand(),true)).".txt";
  $file5 = "toimitukset_rivit-".md5(uniqid(rand(),true)).".txt";

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

  // Laskut

  // keksit‰‰n uudelle failille joku varmasti uniikki nimi:
  $file6 = "laskut-".md5(uniqid(rand(),true)).".txt";
  $file7 = "laskut_rivit-".md5(uniqid(rand(),true)).".txt";

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

  echo "<br>Haetaan laskut....<br>";
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

  // tehd‰‰n failista zippi
  chdir("/tmp");
  exec("/usr/bin/zip Tilintarkastus-{$kukarow["yhtio"]}.zip ".escapeshellarg($file1)." ".escapeshellarg($file2)." ".escapeshellarg($file3)." ".escapeshellarg($file4)." ".escapeshellarg($file5)." ".escapeshellarg($file6)." ".escapeshellarg($file7));

  unlink($file1);
  unlink($file2);
  unlink($file3);
  unlink($file4);
  unlink($file5);
  unlink($file6);
  unlink($file7);

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

require ("inc/footer.inc");
