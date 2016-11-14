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

if (!empty($liitteet_mukaan)) {
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
echo "<th>".t("Liitteet")."</th>";
echo "<td><input type='checkbox' name='liitteet_mukaan' $chk7></td>";
echo "</tr><tr>";
echo "<td class='back'><br></td>";
echo "</tr>";
echo "</table>";

echo "<br><input name='ajonappi' type='submit' value='".t("Aja")."'>";
echo "</form><br><br>";

if ($tee == "raportti") {

  require_once 'inc/ProgressBar.class.php';

  // Luodaan temppidirikka jonne tyˆnnet‰‰n t‰n kiekan kaikki tiedostot
  list($usec, $sec) = explode(' ', microtime());
  mt_srand((float) $sec + ((float) $usec * 100000));
  $tmpdirnimi = "/tmp/Atktilintarkastus-".md5(uniqid(mt_rand(), true))."/";
  mkdir($tmpdirnimi);

  // Tilikartta
  if (!empty($tilikartta_mukaan)) {
    // keksit‰‰n uudelle failille joku varmasti uniikki nimi:
    $file2 = "tilikartta.csv";

    // avataan faili
    $fh = fopen($tmpdirnimi.$file2, "w");

    $rivi  = "Tili;";
    $rivi .= "Nimi";
    $rivi .= "\n";
    fwrite($fh, $rivi);

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

      $rivi  = pupesoft_csvstring($row['tilino']).";";
      $rivi .= pupesoft_csvstring($row['nimi']);
      $rivi .= "\n";
      fwrite($fh, $rivi);
    }

    // suljetaan tiedosto
    fclose($fh);
  }

  // Kustannuspaikat, projektit ja kohteet
  if (!empty($tarkenteet_mukaan)) {
    // keksit‰‰n uudelle failille joku varmasti uniikki nimi:
    $file3 = "tarkenteet.csv";

    // avataan faili
    $fh = fopen($tmpdirnimi.$file3, "w");

    $rivi  = "Tyyppi;";
    $rivi .= "Tunnus;";
    $rivi .= "Nimi";
    $rivi .= "\n";
    fwrite($fh, $rivi);

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

      $rivi  = $tyyppi.";";
      $rivi .= $row['tunnus'].";";
      $rivi .= pupesoft_csvstring($row['nimi']);
      $rivi .= "\n";
      fwrite($fh, $rivi);
    }

    fclose($fh);
  }

  // Tiliˆinnit
  if (!empty($tilioinnit_mukaan)) {
    // keksit‰‰n uudelle failille joku varmasti uniikki nimi:
    $file1 = "tilioinnit.csv";
    $file11 = "muistiotositteet.csv";

    // avataan faili
    $fh = fopen($tmpdirnimi.$file1, "w");
    $fhr = fopen($tmpdirnimi.$file11, "w");

    $rivi  = "Lasku_tunnus;";
    $rivi .= "Pvm;";
    $rivi .= "Tili;";
    $rivi .= "Kustp;";
    $rivi .= "Kohde;";
    $rivi .= "Projekti;";
    $rivi .= "Summa;";
    $rivi .= "Vero;";
    $rivi .= "Selite;";
    $rivi .= "Laatija;";
    $rivi .= "Laadittu;";
    $rivi .= "Ytunnus;";
    $rivi .= "Nimi;";
    $rivi .= "Nimitark;";
    $rivi .= "Osoite;";
    $rivi .= "Osoitetark;";
    $rivi .= "Postino;";
    $rivi .= "Postitp;";
    $rivi .= "Tositetyyppi";
    $rivi .= "\n";
    fwrite($fh, $rivi);

    $rivi  = "Lasku_tunnus;";
    $rivi .= "Pvm;";
    $rivi .= "Tyyppi;";
    $rivi .= "Selite;";
    $rivi .= "Kommentti";
    $rivi .= "\n";
    fwrite($fhr, $rivi);

    // haetaan kaikki vuoden tapahtumat...
    $query  = "SELECT tiliointi.ltunnus, tiliointi.tapvm, tiliointi.tilino, tiliointi.kustp, tiliointi.kohde, tiliointi.projekti,
               tiliointi.summa, tiliointi.vero, tiliointi.selite, tiliointi.laatija, lasku.comments, kuka.nimi kukanimi,
               tiliointi.laadittu, lasku.ytunnus, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.osoitetark, lasku.postino, lasku.postitp, lasku.alatila, lasku.tila
               FROM tiliointi
               JOIN lasku ON (lasku.yhtio=tiliointi.yhtio and lasku.tunnus=tiliointi.ltunnus)
               LEFT JOIN kuka ON (tiliointi.yhtio=kuka.yhtio and tiliointi.laatija=kuka.kuka)
               where tiliointi.yhtio  = '$kukarow[yhtio]'
               and tiliointi.tapvm    >= '$alku'
               and tiliointi.tapvm    <= '$loppu'
               and tiliointi.korjattu = ''
               ORDER BY tiliointi.ltunnus, tiliointi.tapvm";
    $result = pupe_query($query);

    echo "<br>Haetaan tiliˆinnit....<br>";
    $bar = new ProgressBar();
    $elements = mysql_num_rows($result); // total number of elements to process
    $bar->initialize($elements); // print the empty bar

    $haetut_muikkarit = array();

    while ($row = mysql_fetch_assoc($result)) {

      $bar->increase();

      $laatija = empty($row['kukanimi']) ? $row['laatija'] : $row['kukanimi'];

      $rivi  = pupesoft_csvstring($row['ltunnus']).";";
      $rivi .= pupesoft_csvstring($row['tapvm']).";";
      $rivi .= pupesoft_csvstring($row['tilino']).";";
      $rivi .= pupesoft_csvstring($row['kustp']).";";
      $rivi .= pupesoft_csvstring($row['kohde']).";";
      $rivi .= pupesoft_csvstring($row['projekti']).";";
      $rivi .= pupesoft_csvstring($row['summa']).";";
      $rivi .= pupesoft_csvstring($row['vero']).";";
      $rivi .= pupesoft_csvstring($row['selite']).";";
      $rivi .= pupesoft_csvstring($laatija).";";
      $rivi .= pupesoft_csvstring($row['laadittu']).";";
      $rivi .= pupesoft_csvstring($row['ytunnus']).";";
      $rivi .= pupesoft_csvstring($row['nimi']).";";
      $rivi .= pupesoft_csvstring($row['nimitark']).";";
      $rivi .= pupesoft_csvstring($row['osoite']).";";
      $rivi .= pupesoft_csvstring($row['osoitetark']).";";
      $rivi .= pupesoft_csvstring($row['postino']).";";
      $rivi .= pupesoft_csvstring($row['postitp']).";";

      $tyyppi = "";

      // Laitetaan tositetyyppi mukaan selkokielisen‰
      if ($row["tila"] == "U") {
        $tyyppi = "Myynti";
      }
      elseif (in_array($row["tila"], array("H", "Y", "M", "P", "Q"))) {
        $tyyppi = "Osto";
      }
      elseif ($row["tila"] == "X") {
        switch ($row["alatila"]) {
        case "E":
          $tyyppi = "Ep‰kurantointi";
          break;
        case "I":
          $tyyppi = "Inventointi";
          break;
        case "G":
          $tyyppi = "Varastosiirto";
          break;
        case "K":
          $tyyppi = "Kassalippaan t‰sm‰ytys";
          break;
        case "O":
          $tyyppi = "K‰teisotto";
          break;
        case "T":
          $tyyppi = "Tilikauden tulos";
          break;
        case "A":
          $tyyppi = "Avaava tase";
          break;
        default :
          $tyyppi = "Muistiotosite";
        }
      }
      else {
        $tyyppi = $row["tila"];
      }

      $rivi .= pupesoft_csvstring($tyyppi);
      $rivi .= "\n";
      fwrite($fh, $rivi);

      if (empty($haetut_muikkarit[$row['ltunnus']]) and strtoupper($row['tila']) == "X") {
        $haetut_muikkarit[$row['ltunnus']] = TRUE;

        $rivi  = pupesoft_csvstring($row['ltunnus']).";";
        $rivi .= pupesoft_csvstring($row['tapvm']).";";
        $rivi .= pupesoft_csvstring($tyyppi).";";
        $rivi .= pupesoft_csvstring($row['nimi']).";";
        $rivi .= pupesoft_csvstring($row['comments']);
        $rivi .= "\n";
        fwrite($fhr, $rivi);
      }
    }

    // suljetaan tiedosto
    fclose($fh);
    fclose($fhr);
  }

  // Myyntitilaukset
  if (!empty($myntitilaukset_mukaan)) {
    // keksit‰‰n uudelle failille joku varmasti uniikki nimi:
    $file4 = "toimitukset.csv";
    $file5 = "toimitukset_rivit.csv";

    // avataan faili
    $fh = fopen($tmpdirnimi.$file4, "w");
    $fhr = fopen($tmpdirnimi.$file5, "w");

    $rivi  = "toimitus_tunnus;";
    $rivi .= "laskunro;";
    $rivi .= "luontiaika;";
    $rivi .= "pvm;";
    $rivi .= "verollinen_summa;";
    $rivi .= "veroton_summa;";
    $rivi .= "verollinen_summa_valuutassa;";
    $rivi .= "veroton_summa_valuutassa;";
    $rivi .= "valuutta;";
    $rivi .= "toimitusehto;";
    $rivi .= "asiakasnumero;";
    $rivi .= "hyvitysviesti";
    $rivi .= "\n";
    fwrite($fh, $rivi);

    $rivi  = "lasku_tunnus;";
    $rivi .= "toimitus_tunnus;";
    $rivi .= "tuoteno;";
    $rivi .= "nimitys;";
    $rivi .= "kpl;";
    $rivi .= "verollinen_rivihinta;";
    $rivi .= "veroton_rivihinta;";
    $rivi .= "vero;";
    $rivi .= "toimitettu";
    $rivi .= "\n";
    fwrite($fhr, $rivi);


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

      $rivi  = pupesoft_csvstring($row['tunnus']).";";
      $rivi .= pupesoft_csvstring($row['laskunro']).";";
      $rivi .= pupesoft_csvstring($row['luontiaika']).";";
      $rivi .= pupesoft_csvstring($row['tapvm']).";";
      $rivi .= pupesoft_csvstring($row['summa']).";";
      $rivi .= pupesoft_csvstring($row['arvo']).";";
      $rivi .= pupesoft_csvstring($row['summa_valuutassa']).";";
      $rivi .= pupesoft_csvstring($row['arvo_valuutassa']).";";
      $rivi .= pupesoft_csvstring($row['valkoodi']).";";
      $rivi .= pupesoft_csvstring($row['toimitusehto']).";";
      $rivi .= pupesoft_csvstring($row['asiakasnro']).";";
      $rivi .= pupesoft_csvstring($row['viesti']);
      $rivi .= "\n";
      fwrite($fh, $rivi);

      $query = "SELECT tilausrivi.uusiotunnus, tilausrivi.otunnus, tilausrivi.tuoteno, tilausrivi.nimitys, tilausrivi.kpl, tilausrivi.rivihinta, tilausrivi.alv, tilausrivi.toimitettuaika
                FROM tilausrivi
                WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                AND tilausrivi.otunnus = '{$row['tunnus']}'
                AND tilausrivi.tyyppi  = 'L'";
      $rivires = pupe_query($query);

      while ($rivirow = mysql_fetch_assoc($rivires)) {
        $verollinen_rivihinta = sprintf('%.2f', round($rivirow['rivihinta'] * (1+($rivirow['alv']/100)), 2));

        $rivi  = pupesoft_csvstring($rivirow['uusiotunnus']).";";
        $rivi .= pupesoft_csvstring($rivirow['otunnus']).";";
        $rivi .= pupesoft_csvstring($rivirow['tuoteno']).";";
        $rivi .= pupesoft_csvstring($rivirow['nimitys']).";";
        $rivi .= pupesoft_csvstring($rivirow['kpl']).";";
        $rivi .= pupesoft_csvstring($verollinen_rivihinta).";";
        $rivi .= pupesoft_csvstring($rivirow['rivihinta']).";";
        $rivi .= pupesoft_csvstring($rivirow['alv']).";";
        $rivi .= pupesoft_csvstring($rivirow['toimitettuaika']);
        $rivi .= "\n";
        fwrite($fhr, $rivi);
      }
    }

    fclose($fh);
    fclose($fhr);
  }

  // Myyntilaskut
  if (!empty($myyntilaskut_mukaan)) {
    // keksit‰‰n uudelle failille joku varmasti uniikki nimi:
    $file6 = "myyntilaskut.csv";
    $file7 = "myyntilaskut_rivit.csv";

    // avataan faili
    $fh = fopen($tmpdirnimi.$file6, "w");
    $fhr = fopen($tmpdirnimi.$file7, "w");

    $rivi  = "lasku_tunnus;";
    $rivi .= "laskunro;";
    $rivi .= "luontiaika;";
    $rivi .= "pvm;";
    $rivi .= "verollinen_summa;";
    $rivi .= "veroton_summa;";
    $rivi .= "verollinen_summa_valuutassa;";
    $rivi .= "veroton_summa_valuutassa;";
    $rivi .= "valuutta;";
    $rivi .= "toimitusehto;";
    $rivi .= "asiakasnumero;";
    $rivi .= "er‰p‰iv‰;";
    $rivi .= "viitenumero;";
    $rivi .= "hyvitysviesti";
    $rivi .= "\n";
    fwrite($fh, $rivi);

    $rivi  = "lasku_tunnus;";
    $rivi .= "toimitus_tunnus;";
    $rivi .= "tuoteno;";
    $rivi .= "nimitys;";
    $rivi .= "kpl;";
    $rivi .= "verollinen_rivihinta;";
    $rivi .= "veroton_rivihinta;";
    $rivi .= "vero;";
    $rivi .= "toimitettu";
    $rivi .= "\n";
    fwrite($fhr, $rivi);

    $query = "SELECT lasku.tunnus, lasku.laskunro, lasku.luontiaika, lasku.tapvm, lasku.summa, asiakas.asiakasnro,
              concat_ws(' ', lasku.nimi, lasku.nimitark) nimi, if(lasku.clearing = 'HYVITYS', lasku.viesti,'') viesti,
              lasku.summa_valuutassa, lasku.valkoodi, lasku.toimitusehto, lasku.arvo, lasku.arvo_valuutassa, lasku.erpcm, lasku.viite
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

      $rivi  = pupesoft_csvstring($row['tunnus']).";";
      $rivi .= pupesoft_csvstring($row['laskunro']).";";
      $rivi .= pupesoft_csvstring($row['luontiaika']).";";
      $rivi .= pupesoft_csvstring($row['tapvm']).";";
      $rivi .= pupesoft_csvstring($row['summa']).";";
      $rivi .= pupesoft_csvstring($row['arvo']).";";
      $rivi .= pupesoft_csvstring($row['summa_valuutassa']).";";
      $rivi .= pupesoft_csvstring($row['arvo_valuutassa']).";";
      $rivi .= pupesoft_csvstring($row['valkoodi']).";";
      $rivi .= pupesoft_csvstring($row['toimitusehto']).";";
      $rivi .= pupesoft_csvstring($row['asiakasnro']).";";
      $rivi .= pupesoft_csvstring($row['erpcm']).";";
      $rivi .= pupesoft_csvstring($row['viite']).";";
      $rivi .= pupesoft_csvstring($row['viesti']);
      $rivi .= "\n";
      fwrite($fh, $rivi);


      $query = "SELECT tilausrivi.uusiotunnus, tilausrivi.otunnus, tilausrivi.tuoteno, tilausrivi.nimitys, tilausrivi.kpl, tilausrivi.rivihinta, tilausrivi.alv, tilausrivi.toimitettuaika
                FROM tilausrivi
                WHERE tilausrivi.yhtio     = '{$kukarow['yhtio']}'
                AND tilausrivi.uusiotunnus = '{$row['tunnus']}'
                AND tilausrivi.tyyppi      = 'L'";
      $rivires = pupe_query($query);

      while ($rivirow = mysql_fetch_assoc($rivires)) {
        $verollinen_rivihinta = sprintf('%.2f', round($rivirow['rivihinta'] * (1+($rivirow['alv']/100)), 2));

        $rivi  = pupesoft_csvstring($rivirow['uusiotunnus']).";";
        $rivi .= pupesoft_csvstring($rivirow['otunnus']).";";
        $rivi .= pupesoft_csvstring($rivirow['tuoteno']).";";
        $rivi .= pupesoft_csvstring($rivirow['nimitys']).";";
        $rivi .= pupesoft_csvstring($rivirow['kpl']).";";
        $rivi .= pupesoft_csvstring($verollinen_rivihinta).";";
        $rivi .= pupesoft_csvstring($rivirow['rivihinta']).";";
        $rivi .= pupesoft_csvstring($rivirow['alv']).";";
        $rivi .= pupesoft_csvstring($rivirow['toimitettuaika']);
        $rivi .= "\n";
        fwrite($fhr, $rivi);
      }
    }

    fclose($fh);
    fclose($fhr);
  }

  // Ostolaskut
  if (!empty($ostolaskut_mukaan)) {
    // keksit‰‰n uudelle failille joku varmasti uniikki nimi:
    $file8 = "ostolaskut.csv";
    $file9 = "ostolaskut_rivit.csv";

    // avataan faili
    $fh = fopen($tmpdirnimi.$file8, "w");
    $fhr = fopen($tmpdirnimi.$file9, "w");

    $rivi  = "lasku_tunnus;";
    $rivi .= "laskunro;";
    $rivi .= "luontiaika;";
    $rivi .= "pvm;";
    $rivi .= "summa;";
    $rivi .= "valuutta;";
    $rivi .= "summa_{$yhtiorow['valkoodi']};";
    $rivi .= "toimittajanro;";
    $rivi .= "nimi;";
    $rivi .= "toimittajatyyppi;";
    $rivi .= "er‰p‰iv‰;";
    $rivi .= "laskun_tyyppi;";
    $rivi .= "laskun_tila;";
    $rivi .= "viite;";
    $rivi .= "viesti";
    $rivi .= "\n";
    fwrite($fh, $rivi);

    $rivi  = "lasku_tunnus;";
    $rivi .= "tuoteno;";
    $rivi .= "nimitys;";
    $rivi .= "kpl;";
    $rivi .= "rivihinta;";
    $rivi .= "varastonarvo;";
    $rivi .= "saapuminen";
    $rivi .= "\n";
    fwrite($fhr, $rivi);

    $query = "SELECT lasku.tunnus, lasku.laskunro, lasku.luontiaika, lasku.tapvm,
              lasku.summa, round(lasku.summa * if(lasku.maksu_kurssi = 0, lasku.vienti_kurssi, lasku.maksu_kurssi), 2) kotisumma,
              toimi.toimittajanro, concat_ws(' ', lasku.nimi, lasku.nimitark) nimi, lasku.viesti,
              lasku.valkoodi, toimi.tyyppi, lasku.vienti, lasku.tila, lasku.erpcm, lasku.viite, lasku.viesti
              FROM lasku
              LEFT JOIN toimi ON (toimi.yhtio = lasku.yhtio and toimi.tunnus = lasku.liitostunnus)
              WHERE lasku.yhtio = '{$kukarow['yhtio']}'
              AND lasku.tila    in ('H','Y','M','P','Q')
              AND lasku.tapvm   >= '$alku'
              AND lasku.tapvm   <= '$loppu'
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

      switch ($row["vienti"]) {
      case "A":
        $vienti = "Kotimaa kulu";
        break;
      case "B":
        $vienti = "Kotimaa huolinta/rahti";
        break;
      case "C":
        $vienti = "Kotimaa vaihto-omaisuus";
        break;
      case "J":
        $vienti = "Kotimaa Raaka-aine";
        break;
      case "D":
        $vienti = "EU kulu";
        break;
      case "E":
        $vienti = "EU huolinta/rahti";
        break;
      case "F":
        $vienti = "EU vaihto-omaisuus";
        break;
      case "K":
        $vienti = "EU Raaka-aine";
        break;
      case "G":
        $vienti = "ei-EU kulu";
        break;
      case "H":
        $vienti = "ei-EU huolinta/rahti";
        break;
      case "I":
        $vienti = "ei-EU vaihto-omaisuus";
        break;
      case "L":
        $vienti = "ei-EU Raaka-aine";
        break;
      default :
        $vienti = "";
      }

      switch ($row["tila"]) {
      case "H":
        $tila = "Hyv‰ksytt‰v‰n‰";
        break;
      case "Y":
        $tila = "Maksettu";
        break;
      case "M":
        $tila = "Valmis maksatukseen";
        break;
      case "P":
        $tila = "L‰hdˆss‰ pankkiin";
        break;
      case "Q":
        $tila = "Odottaa suoritusta";
        break;
      default :
        $tila = "";
      }

      $rivi  = pupesoft_csvstring($row['tunnus']).";";
      $rivi .= pupesoft_csvstring($row['laskunro']).";";
      $rivi .= pupesoft_csvstring($row['luontiaika']).";";
      $rivi .= pupesoft_csvstring($row['tapvm']).";";
      $rivi .= pupesoft_csvstring($row['summa']).";";
      $rivi .= pupesoft_csvstring($row['valkoodi']).";";
      $rivi .= pupesoft_csvstring($row['kotisumma']).";";
      $rivi .= pupesoft_csvstring($row['toimittajanro']).";";
      $rivi .= pupesoft_csvstring($row['nimi']).";";
      $rivi .= pupesoft_csvstring($row['tyyppi']).";";
      $rivi .= pupesoft_csvstring($row['erpcm']).";";
      $rivi .= pupesoft_csvstring($vienti).";";
      $rivi .= pupesoft_csvstring($tila).";";
      $rivi .= pupesoft_csvstring($row['viite']).";";
      $rivi .= pupesoft_csvstring($row['viesti']);
      $rivi .= "\n";
      fwrite($fh, $rivi);

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
              $rivi  = pupesoft_csvstring($row['tunnus']).";";
              $rivi .= pupesoft_csvstring($rivirow['tuoteno']).";";
              $rivi .= pupesoft_csvstring($rivirow['nimitys']).";";
              $rivi .= pupesoft_csvstring($rivirow['kpl']).";";
              $rivi .= pupesoft_csvstring($rivirow['rivihinta']).";";
              $rivi .= pupesoft_csvstring($rivirow['varastonarvo']).";";
              $rivi .= pupesoft_csvstring($keikrow['laskunro']);
              $rivi .= "\n";
              fwrite($fhr, $rivi);
            }
          }
        }
      }
    }

    fclose($fh);
    fclose($fhr);
  }

  // Liitetiedostot
  if (!empty($liitteet_mukaan)) {
    // Luodaan kansio tiedostoille
    $liitedir = "Liitteet/";

    mkdir($tmpdirnimi.$liitedir);
    chdir($tmpdirnimi);
    exec("zip -r Liitteet.zip {$liitedir};");

    $file10 = "liitteet.csv";

    // avataan faili
    $fh = fopen($tmpdirnimi.$file10, "w");

    $rivi  = "lasku_tunnus;";
    $rivi .= "tiedostonimi;";
    $rivi .= "alkuper‰inen nimi;";
    $rivi .= "selite";
    $rivi .= "\n";
    fwrite($fh, $rivi);

    $query = "SELECT lasku.tunnus laskutunnus, liitetiedostot.*
              FROM lasku
              JOIN liitetiedostot ON (liitetiedostot.yhtio = lasku.yhtio and liitetiedostot.liitos = 'lasku' AND liitetiedostot.liitostunnus = lasku.tunnus)
              WHERE lasku.yhtio = '{$kukarow['yhtio']}'
              AND lasku.tila    in ('H','Y','M','P','Q','X')
              AND lasku.tapvm   >= '$alku'
              AND lasku.tapvm   <= '$loppu'
              ORDER BY lasku.tapvm, lasku.luontiaika";
    $result = pupe_query($query);

    echo "<br>Haetaan liitetiedostot....<br>";
    $bar = new ProgressBar();
    $elements = mysql_num_rows($result); // total number of elements to process
    $bar->initialize($elements); // print the empty bar

    while ($row = mysql_fetch_assoc($result)) {
      $bar->increase();
      $path_parts = pathinfo($row["filename"]);

      if (empty($path_parts['extension'])) {
        list($devnull, $ext) = explode("/", $row["filetype"]);

        $path_parts['extension'] = $ext;
      }

      $kokonimi = $liitedir.$row['laskutunnus']."_".$row['tunnus'].".".$path_parts['extension'];

      file_put_contents($tmpdirnimi.$kokonimi, $row["data"]);
      exec("zip -g Liitteet.zip $kokonimi;");
      unlink($kokonimi);

      $rivi  = pupesoft_csvstring($row['laskutunnus']).";";
      $rivi .= pupesoft_csvstring($row['laskutunnus']."_".$row['tunnus'].".".$path_parts['extension']).";";
      $rivi .= pupesoft_csvstring($row['filename']).";";
      $rivi .= pupesoft_csvstring($row['selite']);
      $rivi .= "\n";
      fwrite($fh, $rivi);
    }

    fclose($fh);
  }

  $zipfile = "Tilintarkastus-{$kukarow["yhtio"]}.zip";

  // tehd‰‰n failista zippi
  chdir($tmpdirnimi);

  $komento  = "/usr/bin/zip $zipfile ";

  if (!empty($tilioinnit_mukaan)) $komento .= escapeshellarg($file1)." ";
  if (!empty($tilioinnit_mukaan)) $komento .= escapeshellarg($file11)." ";
  if (!empty($tilikartta_mukaan)) $komento .= escapeshellarg($file2)." ";
  if (!empty($tarkenteet_mukaan)) $komento .= escapeshellarg($file3)." ";
  if (!empty($myntitilaukset_mukaan)) $komento .= escapeshellarg($file4)." ";
  if (!empty($myntitilaukset_mukaan)) $komento .= escapeshellarg($file5)." ";
  if (!empty($myyntilaskut_mukaan)) $komento .= escapeshellarg($file6)." ";
  if (!empty($myyntilaskut_mukaan)) $komento .= escapeshellarg($file7)." ";
  if (!empty($ostolaskut_mukaan)) $komento .= escapeshellarg($file8)." ";
  if (!empty($ostolaskut_mukaan)) $komento .= escapeshellarg($file9)." ";

  if (!empty($liitteet_mukaan)) {
    $komento .= escapeshellarg($file10)." ";

    rename($tmpdirnimi."Liitteet.zip", "/tmp/Liitteet-{$kukarow["yhtio"]}.zip");
  }

  exec($komento);

  rename($tmpdirnimi.$zipfile, "/tmp/".$zipfile);

  exec("rm -rf $tmpdirnimi");

  echo "<br><br><table>";
  echo "<tr><th>".t("Tallenna aineisto").":</th>";
  echo "<form method='post' class='multisubmit'>";
  echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
  echo "<input type='hidden' name='kaunisnimi' value='Tilintarkastus-$kukarow[yhtio].zip'>";
  echo "<input type='hidden' name='tmpfilenimi' value='Tilintarkastus-$kukarow[yhtio].zip'>";
  echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
  echo "</table><br>";

  if (!empty($liitteet_mukaan)) {
    echo "<br><br><table>";
     echo "<tr><th>".t("Tallenna liitteet").":</th>";
     echo "<form method='post' class='multisubmit'>";
     echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
     echo "<input type='hidden' name='kaunisnimi' value='Liitteet-$kukarow[yhtio].zip'>";
     echo "<input type='hidden' name='tmpfilenimi' value='Liitteet-$kukarow[yhtio].zip'>";
     echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
     echo "</table><br>";
  }
}

// kursorinohjausta
$formi  = "vero";
$kentta = "alku";

require "inc/footer.inc";
