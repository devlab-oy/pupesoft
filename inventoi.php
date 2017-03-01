<?php

if (strpos($_SERVER['SCRIPT_NAME'], "inventoi.php") !== FALSE) {
  require "inc/parametrit.inc";

  if (!empty($_POST['ajax_toiminto']) and $_POST['ajax_toiminto'] == 'hae_inventointiselite') {

    $_selite = mysql_real_escape_string(utf8_decode($_POST['selite']));

    echo t_avainsana("INVEN_LAJI", '', " and avainsana.selite = '{$_selite}'", '', '', "selitetark_4");
    exit;
  }
}

$laaja_inventointilista = ($yhtiorow['laaja_inventointilista'] != '' and !empty($lista));

if (!isset($fileesta))       $fileesta = "";
if (!isset($filusta))        $filusta = "";
if (!isset($livesearch_tee)) $livesearch_tee = "";
if (!isset($mobiili))        $mobiili = "";
if (!isset($laadittuaika))   $laadittuaika = "";
if (!isset($enarifocus))     $enarifocus = "";
if (!isset($toim))           $toim = "";

if (!isset($jarjestys)) {
  $jarjestys = $laaja_inventointilista ? "rivinro" : "";
}

$validi_kasinsyotetty_inventointipaivamaara = 0;

if (stripos($toim, "oletusvarasto") !== FALSE) {
  if ($kukarow['oletus_varasto'] == 0) {
    echo "<font class='error'>", t("Oletusvarastoa ei ole asetettu käyttäjälle"), ".</font><br />";

    if ($mobiili != "YES") {
      require "inc/footer.inc";
    }
    exit;
  }

  $oletusvarasto_chk = $kukarow['oletus_varasto'];
}
else {
  $oletusvarasto_chk = 0;
}

if (stripos($toim, "paivamaara") !== FALSE) {
  $paivamaaran_kasisyotto = "JOO";
}
else {
  $paivamaaran_kasisyotto = "";
}

// Kevyt tuloutusprosessi
if (substr($toim, 0, 4) == "OSTO") {
  $paivamaaran_kasisyotto = "";
  $laaja_inventointilista = FALSE;
  $lista = "";
}

if ($livesearch_tee == "TUOTEHAKU") {
  livesearch_tuotehaku();
  exit;
}

// Enaboidaan ajax kikkare
enable_ajax();

if (strpos($_SERVER['SCRIPT_NAME'], "inventoi.php") !== FALSE) {

  $koko_url = $_SERVER['SCRIPT_NAME'].'?'.$_SERVER['QUERY_STRING'];

  echo "  <script type='text/javascript'>
        $(function() {
          $('#inven_laji').on('change', function() {
            var select_value = $(this).val();

            $.ajax({
              async: false,
              type: 'POST',
              data: {
                selite: select_value,
                ajax_toiminto: 'hae_inventointiselite',
                no_head: 'yes',
                ohje: 'off'
              },
              url: '{$koko_url}'
            }).done(function(data) {
              $('#lisaselite').val(data);
            });
          });
        });
      </script>";

  if ($enarifocus != '') {
    echo "  <script type='text/javascript'>
          $(function() {
            $('#ean_koodi').focus();
          });
        </script>";
  }
}

if (substr($toim, 0, 4) == "OSTO") {
  echo "<font class='head'>".t("Tavaran tuloutus")."</font><hr>";
}
elseif ($mobiili != "YES") {
  echo "<font class='head'>".t("Inventointi")."</font><hr>";
}

if ($oikeurow["paivitys"] != '1') { // Saako päivittää
  if ($uusi == 1) {
    echo "<b>".t("Sinulla ei ole oikeutta lisätä tätä tietoa")."</b><br>";
    $uusi = '';
  }
  if ($del == 1) {
    echo "<b>".t("Sinulla ei ole oikeutta poistaa tätä tietoa")."</b><br>";
    $del = '';
    $tunnus = 0;
  }
  if ($upd == 1) {
    echo "<b>".t("Sinulla ei ole oikeutta muuttaa tätä tietoa")."</b><br>";
    $upd = '';
    $uusi = 0;
    $tunnus = 0;
    exit;
  }
}

if ($rivimaara == '') {
  $rivimaara = $laaja_inventointilista ? '16' : '17';
}

//katotaan onko tiedosto ladattu
if ($tee == "FILE") {
  if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {

    $path_parts = pathinfo($_FILES['userfile']['name']);
    $name  = strtoupper($path_parts['filename']);
    $ext  = strtoupper($path_parts['extension']);

    if ($_FILES['userfile']['size']==0) {
      die ("<font class='error'><br>".t("Tiedosto on tyhjä")."!</font>");
    }

    $retval = tarkasta_liite("userfile", array("XLSX", "XLS", "ODS", "SLK", "XML", "GNUMERIC", "CSV", "TXT", "DATAIMPORT"));

    if ($retval !== TRUE) {
      die ("<font class='error'><br>".t("Väärä tiedostomuoto")."!</font>");
    }

    $excelrivit = pupeFileReader($_FILES['userfile']['tmp_name'], $ext);

    $tuote = array();
    $maara = array();
    $selis = array();
    $lajis = array();

    $oletusvarasto_err = 0;

    for ($excei = 0; $excei < count($excelrivit); $excei++) {
      // luetaan rivi tiedostosta..
      $tuo    = mysql_real_escape_string(trim($excelrivit[$excei][0]));
      $hyl    = mysql_real_escape_string(trim($excelrivit[$excei][1]));
      $maa    = str_replace(",", ".", trim($excelrivit[$excei][2]));
      $lisaselite  = mysql_real_escape_string(trim($excelrivit[$excei][3]));
      $inven_laji = "";

      if (strpos($lisaselite, "/") !== FALSE) {
        list($inven_laji, $lisaselite) = explode("/", $lisaselite);
        $inven_laji = trim($inven_laji);
        $lisaselite = trim($lisaselite);
      }

      if ($tuo != '' and $hyl != '' and $maa != '') {
        $hylp = explode("-", $hyl);

        if ($oletusvarasto_chk > 0 and kuuluukovarastoon($hylp[0], $hylp[1], $oletusvarasto_chk) == 0) {
          $oletusvarasto_err++;
          continue;
        }

        $_kerayserat = ($yhtiorow['kerayserat'] == 'K');
        $hylp0 = $hylp[0];
        $hylp1 = $hylp[1];
        $hylp2 = $hylp[2];
        $hylp3 = $hylp[3];

        if ($_kerayserat and !tarkista_varaston_hyllypaikka($hylp0, $hylp1, $hylp2, $hylp3)) {
          echo "<font class='error'>";
          echo t("VIRHE: Varastopaikkaa ei ole olemassa")."! ";
          echo t("Rivi %d", "", $excei + 1), " {$tuo} {$hyl}";
          echo "</font><br />";
          continue;
        }

        $tuote[] = $tuo."###".$hylp[0]."###".$hylp[1]."###".$hylp[2]."###".$hylp[3];
        $maara[] = $maa;
        $selis[] = $lisaselite;
        $lajis[] = $inven_laji;
      }
    }

    if (count($tuote) > 0) {
      $tee     = "VALMIS";
      $valmis   = "OK";
      $fileesta   = "ON";
    }
    else {
      $tee = '';
      echo "<font class='error'>", t("Yhtään tuotetta ei inventoitu"), "!</font><br />";
    }

    if ($oletusvarasto_chk > 0 and $oletusvarasto_err > 0) {
      $plural = $oletusvarasto_err > 1 ? "tuotetta" : "tuote";
      echo " <font class='error'>", t("%d %s ei löytynyt oletusvarastosta", "", $oletusvarasto_err, $plural), ".</font><br />";
    }
  }
  else {
    $tee = "";
  }
}

if ($tee == "EROLISTA" and $lista != '' and $komento["Inventointierolista"] != '') {

  $query = "SELECT inventointilista.vapaa_teksti,
            invrivi.rivinro,
            invrivi.hyllyalue,
            invrivi.hyllynro,
            invrivi.hyllyvali,
            invrivi.hyllytaso,
            invrivi.tuoteno,
            tuote.nimitys,
            tuote.yksikko,
            invrivi.hyllyssa,
            invrivi.laskettu,
            tuote.kehahin,
            GROUP_CONCAT(DISTINCT tuotteen_toimittajat.toim_tuoteno) toim_tuoteno
            FROM inventointilista
            JOIN inventointilistarivi AS invrivi ON (invrivi.yhtio = inventointilista.yhtio
              AND invrivi.otunnus              = inventointilista.tunnus)
            JOIN tuote ON (tuote.yhtio = invrivi.yhtio AND tuote.tuoteno = invrivi.tuoteno)
            LEFT JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = invrivi.yhtio
              AND tuotteen_toimittajat.tuoteno = invrivi.tuoteno)
            WHERE inventointilista.yhtio       = '{$kukarow['yhtio']}'
            AND inventointilista.tunnus        = '{$lista}'
            AND invrivi.laskettu - invrivi.hyllyssa != 0
            GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12";
  $res = pupe_query($query);
  $row = mysql_fetch_assoc($res);

  mysql_data_seek($res, 0);

  $vapaa_teksti = $row['vapaa_teksti'];

  list($usec, $sec) = explode(' ', microtime());
  mt_srand((float) $sec + ((float) $usec * 100000));
  $filenimi  = "/tmp/".preg_replace("/[^a-z0-9\-_]/i", "", t("Inventointierolista")."-".md5(uniqid(mt_rand(), true))).".txt";
  $fh = fopen($filenimi, "w+");

  //rivinleveys default
  $rivinleveys = 147;
  $maxrivit = 17;

  $pp = date('d');
  $kk = date('m');
  $vv = date('Y');
  $kello = date('H:i:s');

  $ots  = t("Inventointierolista")."\t".t("Sivu")." <SIVUNUMERO>\n";
  $ots .= t("Listanumero").": {$lista}\t\t{$yhtiorow['nimi']}\t\t{$pp}.{$kk}.{$vv} - {$kello}\n\n";
  $ots .= t("Vapaa teksti").": {$vapaa_teksti}\n\n";
  $ots .= sprintf('%-5.5s', "#");
  $ots .= sprintf('%-18.14s', t("Paikka"));
  $ots .= sprintf('%-21.21s', t("Tuoteno"));
  $ots .= sprintf('%-21.21s', t("Toim.Tuoteno"));
  $ots .= sprintf('%-26.23s', t("Nimitys"));
  $ots .= sprintf('%-10.10s', t("Hyllyssä"));
  $ots .= sprintf('%-10.10s', t("Laskettu"));
  $ots .= sprintf('%-7.5s', t("Vapaa"));
  $ots .= sprintf('%-5.5s', t("Yks."));
  $ots .= sprintf('%-10.10s', t("Poikkeama"));
  $ots .= sprintf('%-8.8s', t("Poik.EUR"));
  $ots .= sprintf('%-5.5s', str_pad("#", 5, " ", STR_PAD_LEFT));

  $ots .= "\n";
  $ots .= "__________________________________________________________________________________";
  $ots .= "_________________________________________________________________\n";

  $kokonaissivumaara = ceil(mysql_num_rows($res) / 16);

  fwrite($fh, str_replace("<SIVUNUMERO>", "1 / {$kokonaissivumaara}", $ots));
  $ots = chr(12).$ots;

  $rivit = 1;
  $sivulaskuri = 1;

  $_poikkeama_yht = 0;
  $_poikkeama_yht_eur = 0;

  while ($row = mysql_fetch_assoc($res)) {

    if ($rivit >= $maxrivit) {
      $sivulaskuri++;
      fwrite($fh, str_replace("<SIVUNUMERO>", "{$sivulaskuri} / {$kokonaissivumaara}", $ots));
      $rivit = 1;
    }

    $prn = "\n";

    if ($rivit > 1) $prn .= "\n";

    $prn .= sprintf('%-5.5s', $row['rivinro']);

    $_paikka = "{$row['hyllyalue']}-{$row['hyllynro']}-{$row['hyllyvali']}-{$row['hyllytaso']}";
    $prn .= sprintf('%-18.14s', $_paikka);
    $prn .= sprintf('%-21.20s', $row['tuoteno']);
    $prn .= sprintf('%-21.20s', $row['toim_tuoteno']);
    $prn .= sprintf('%-26.23s', $row['nimitys']);
    $prn .= sprintf('%-10.09s', $row['hyllyssa']);
    $prn .= sprintf('%-10.09s', $row['laskettu']);
    $prn .= sprintf('%-7.5s', "_____");
    $prn .= sprintf('%-6.6s', $row['yksikko']);

    $_poikkeama = $row['laskettu'] - $row['hyllyssa'];
    $_poikkeama_eur = $_poikkeama * $row['kehahin'];

    $_poikkeama_yht += $_poikkeama;
    $_poikkeama_yht_eur += $_poikkeama_eur;

    $prn .= sprintf('%-9.7s', $_poikkeama);
    $prn .= sprintf('%-8.6s', round($_poikkeama_eur, 1));

    $prn .= sprintf('%-5.5s', str_pad($row['rivinro'], 5, " ", STR_PAD_LEFT));

    $prn .= "\n";
    $prn .= "____________________________________________________________________________";
    $prn .= "_______________________________________________________________________";

    fwrite($fh, $prn);
    $rivit++;
    $rivinro++;
  }

  if (($rivit + 3) >= $maxrivit) {
    $sivulaskuri++;
    fwrite($fh, str_replace("<SIVUNUMERO>", "{$sivulaskuri} / {$kokonaissivumaara}", $ots));
    $rivit = 1;
  }

  $prn = "\n\n";
  $prn .= sprintf('%-100.100s', t("Poikkeama yhteensä").": {$_poikkeama_yht}")."\n";
  $prn .= sprintf('%-100.100s',
    t("Poikkeama yhteensä EUR").": ".round($_poikkeama_yht_eur, $yhtiorow['hintapyoristys']));

  fwrite($fh, $prn);

  fclose($fh);

  $params = array(
    'chars'    => $rivinleveys,
    'filename' => $filenimi,
    'margin'   => 0,
    'mode'     => 'landscape',
  );

  // konveroidaan postscriptiksi
  $filenimi_ps = pupesoft_a2ps($params);

  if ($komento["Inventointierolista"] == 'email') {

    system("ps2pdf -sPAPERSIZE=a4 {$filenimi_ps} ".$filenimi.".pdf");

    $liite = $filenimi.".pdf";
    $kutsu = t("Inventointierolista")."_$lista";

    require "inc/sahkoposti.inc";
  }
  else {
    // itse print komento...
    $line = exec("{$komento['Inventointierolista']} {$filenimi_ps}");
  }

  echo "<font class='message'>", t("Inventointierolista tulostuu!"), "</font><br><br>";

  //poistetaan tmp file samantien kuleksimasta...
  unlink($filenimi);
  unlink($filenimi_ps);

  $tee = "INVENTOI";
}

//tuotteen varastostatus
if ($tee == 'VALMIS') {

  $virhe = 0;

  // Inventoidaan EAN-koodilla
  if (isset($tuoteno_ean) and $tuoteno_ean == "EAN") {
    $tuoteno_ean_kentta = "eankoodi";
  }
  else {
    $tuoteno_ean_kentta = "tuoteno";
  }

  $_tallenna = (isset($tallenna_laskettu_hyllyssa) or isset($prev) or isset($next));

  if (!$_tallenna or !$laaja_inventointilista) {
    $_mennaan = true;
  }

  if ($laaja_inventointilista and $_tallenna and count($tuote) > 0) {

    foreach ($tuote as $i => $tuotteet) {

      $tuotetiedot = explode("###", $tuotteet);

      $tuoteno           = $tuotetiedot[0];
      $hyllyalue         = $tuotetiedot[1];
      $hyllynro          = $tuotetiedot[2];
      $hyllyvali         = $tuotetiedot[3];
      $hyllytaso         = $tuotetiedot[4];
      $kpl               = str_replace(",", ".", $maara[$i]);

      if ($kpl != '' and is_numeric($kpl)) {

        $query = "UPDATE inventointilistarivi SET
                  laskettu      = '{$kpl}'
                  WHERE yhtio   = '{$kukarow['yhtio']}'
                  AND otunnus   = '{$lista}'
                  AND tuoteno   = '{$tuoteno}'
                  AND hyllyalue = '{$hyllyalue}'
                  AND hyllynro  = '{$hyllynro}'
                  AND hyllyvali = '{$hyllyvali}'
                  AND hyllytaso = '{$hyllytaso}'";
        $updres = pupe_query($query);
      }
    }

    $_mennaan = false;
  }

  if ($laaja_inventointilista and count($tuote) > 0 and $_mennaan) {

    $query = "SELECT *
              FROM inventointilistarivi
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND otunnus = '{$lista}'";
    $_lasketut_res = pupe_query($query);

    while ($lrow = mysql_fetch_assoc($_lasketut_res)) {

      $_paikka  = "{$lrow['tuoteno']}###{$lrow['hyllyalue']}###{$lrow['hyllynro']}###";
      $_paikka .= "{$lrow['hyllyvali']}###{$lrow['hyllytaso']}";

      if (!array_key_exists($lrow['tuotepaikkatunnus'], $tuote)) {
        $tuote[$lrow['tuotepaikkatunnus']] = $_paikka;
        $maara[$lrow['tuotepaikkatunnus']] = $lrow['laskettu'];
      }
    }
  }

  if (count($tuote) > 0 and $_mennaan) {

    // lukitaan tableja
    $query = "LOCK TABLES
              avainsana READ,
              inventointilista WRITE,
              inventointilistarivi WRITE,
              lasku WRITE,
              sanakirja WRITE,
              sarjanumeroseuranta WRITE,
              sarjanumeroseuranta_arvomuutos READ,
              tapahtuma WRITE,
              tilausrivi WRITE,
              tili READ,
              tiliointi WRITE,
              tuote WRITE,
              tuotepaikat WRITE,
              tuotteen_toimittajat WRITE,
              varastopaikat READ,
              varaston_hyllypaikat READ,
              yhtion_toimipaikat READ";
    pupe_query($query);

    foreach ($tuote as $i => $tuotteet) {

      $tuotetiedot = explode("###", $tuotteet);

      $tuoteno              = $tuotetiedot[0];
      $hyllyalue            = $tuotetiedot[1];
      $hyllynro             = $tuotetiedot[2];
      $hyllyvali            = $tuotetiedot[3];
      $hyllytaso            = $tuotetiedot[4];
      $kpl                  = str_replace(",", ".", $maara[$i]);
      $poikkeama            = 0;
      $skp                  = 0;
      $inven_laji_tilino    = "";
      $laadittuaika         = "now()";

      if ($fileesta == "ON") {
        $inven_laji = $lajis[$i];
        $lisaselite = $selis[$i];
      }

      if (substr($toim, 0, 4) == "OSTO") {

        $kpl = (float) preg_replace("/[^0-9\.]/", "", $kpl);
        $ostohinta = str_replace(",", ".", $ostohinnat[$i]);
        $ostohinta = (float) preg_replace("/[^0-9\.]/", "", $ostohinta);
        $tuotteen_toimittaja  = $tuotteen_toimittajat[$i];

        if (!empty($kpl)) {
          $kpl = "+$kpl";
        }
      }

      if ($kpl != '' and is_numeric($kpl)) {

        $query = "SELECT *
                  FROM tuote
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND $tuoteno_ean_kentta = '$tuoteno'";
        $tuote_res = pupe_query($query);
        $tuote_row = mysql_fetch_assoc($tuote_res);

        if (mysql_num_rows($tuote_res) != 1) {
          echo "<font class='error'>".t("VIRHE: Tuotetta ei löydy")."! ($tuoteno)</font><br>";
          $virhe = 1;
        }
        else {
          $tuoteno = $tuote_row["tuoteno"];
        }

        if ($inven_laji != "") {
          $query = "SELECT selitetark_2, selitetark_4
                    FROM avainsana
                    WHERE yhtio = '$kukarow[yhtio]'
                    and laji    = 'INVEN_LAJI'
                    and kieli   in ('$yhtiorow[kieli]', '')
                    and selite  = '$inven_laji'";
          $avain_res = pupe_query($query);

          if (mysql_num_rows($avain_res) == 0) {
            echo "<font class='error'>".t("VIRHE: valittua inventointilajia %s ei löydy!", "", $inven_laji).": $tuoteno!</font><br>";
            $virhe = 1;
          }
          else {
            $avain_row = mysql_fetch_assoc($avain_res);
            $inven_laji_tilino = $avain_row["selitetark_2"];
            if ($lisaselite == '') $lisaselite = $avain_row["selitetark_4"];
          }
        }

        if ($tuote_row['sarjanumeroseuranta'] != '' and !is_array($sarjanumero_kaikki[$i]) and !is_array($eranumero_kaikki[$i]) and (substr($kpl, 0, 1) == '+' or substr($kpl, 0, 1) == '-' or (float) $kpl != 0)) {
          echo "<font class='error'>".t("VIRHE: Et valinnut yhtään sarja- tai eränumeroa").": $tuoteno!</font><br>";
          $virhe = 1;
        }

        // Jos lajit on käytössä niin myös selite on syötettävä
        if ($inven_laji != "" and trim($lisaselite) == "") {
          echo "<font class='error'>".t("VIRHE: Inventointiselite on syötettävä")."!: $tuoteno</font><br>";
          $virhe = 1;
        }

        // Jos on syötetty käsin inventointipvm pp, kk tai vvvv kokorimpsun pitää olla validi
        if ($paivamaaran_kasisyotto == "JOO" and (!empty($inventointipvm_pp) or !empty($inventointipvm_kk) or !empty($inventointipvm_vv))) {

          if (!is_numeric($inventointipvm_pp)) $virhe = 1;
          if (!is_numeric($inventointipvm_kk)) $virhe = 1;
          if (!is_numeric($inventointipvm_vv)) $virhe = 1;

          $mm   = $inventointipvm_kk;
          $dd   = $inventointipvm_pp;
          $yyyy = $inventointipvm_vv;

          if (!checkdate($mm, $dd, $yyyy)) {
            echo "<font class='error'>".t("VIRHE: Virheellinen inventointipäivämäärä")."!: $tuoteno ".t("Anna päivämäärä muodossa pp-kk-vvvv")."</font><br>";
            $virhe = 1;
          }
          elseif (strtotime("{$yyyy}-{$mm}-{$dd} 23:59:59") > strtotime(date('Y-m-d'))) {
            echo "<font class='error'>".t("VIRHE: Virheellinen inventointipäivämäärä")."!: $tuoteno ".t("Käsinsyötetyn päivämäärän tulee olla pienempi kuin nykyinen päivämäärä")."</font><br>";
            $virhe = 1;
          }
          elseif (strtotime("{$yyyy}-{$mm}-{$dd}") < strtotime($yhtiorow['tilikausi_alku']) or strtotime("{$yyyy}-{$mm}-{$dd}") > strtotime($yhtiorow['tilikausi_loppu'])) {
            echo "<font class='error'>".t("VIRHE: Virheellinen inventointipäivämäärä")."!: $tuoteno ".t("Päivämäärä ei ole avoimella tilikaudella")."</font><br>";
            $virhe = 1;
          }

          if ($virhe == 0) {
            $laadittuaika = "{$yyyy}-{$mm}-{$dd} 23:59:59";

            // Inventointipvm käsisyöttöfallbacki - ei sallita päivämäärää jos sen jälkeen on tuloja, valmistuksia tai epäkuranttiajoja
            $query = "SELECT *
                      FROM tapahtuma
                      WHERE yhtio  = '$kukarow[yhtio]'
                      and tuoteno  = '$tuote_row[tuoteno]'
                      and laji     IN ('tulo', 'valmistus', 'epäkurantti')
                      and laadittu >= '{$laadittuaika}'";
            $ressu = pupe_query($query);

            if (mysql_num_rows($ressu) > 0) {
              echo "<font class='error'>".t("VIRHE: Virheellinen inventointipäivämäärä")."!: $tuoteno ".t("Tuotteella on varastonarvoon vaikuttavia tapahtumia annetun päivämäärän jälkeen")."</font><br>";
              $virhe = 1;
            }
            else {
              $validi_kasinsyotetty_inventointipaivamaara = 1;
            }
          }
        }

        // käydään kaikki ruudulla näkyvät läpi ja katsotaan onko joku niistä uusi
        $onko_uusia = 0;

        if (isset($sarjanumero_kaikki[$i])) {
          foreach ($sarjanumero_kaikki[$i] as $snro => $schk) {
            if ($sarjanumero_uudet[$i][$snro] == '0000-00-00') {
              $onko_uusia++;
            }
          }
        }

        // käydään kaikki valitut checkboxit läpi ja katsotaan onko joku niistä vanha
        $onko_vanhoja = 0;

        if (isset($sarjanumero_valitut[$i])) {
          foreach ($sarjanumero_valitut[$i] as $snro => $schk) {
            if ($sarjanumero_uudet[$i][$snro] != '0000-00-00') {
              $onko_vanhoja++;
            }
          }
        }

        if (in_array($tuote_row["sarjanumeroseuranta"], array("S", "G")) and $onko_vanhoja > 0 and $onko_uusia > 0) {
          echo "<font class='error'>".t("VIRHE: Voit lisätä / poistaa vain uuden sarjanumeron")."!</font><br>";
          $virhe = 1;
        }

        //Sarjanumerot
        if ($tuote_row["sarjanumeroseuranta"] == "S" and is_array($sarjanumero_kaikki[$i]) and substr($kpl, 0, 1) != '+' and substr($kpl, 0, 1) != '-' and (int) $kpl != count($sarjanumero_kaikki[$i]) and ($onko_uusia > 0 or $hyllyssa[$i] < $kpl)) {
          echo "<font class='error'>".t("VIRHE: Sarjanumeroita ei voi lisätä kuin relatiivisella määrällä")."! (+1)</font><br>";
          $virhe = 1;
        }
        elseif ($tuote_row["sarjanumeroseuranta"] == "S" and substr($kpl, 0, 1) == '+' and is_array($sarjanumero_kaikki[$i]) and count($sarjanumero_valitut[$i]) != (int) substr($kpl, 1)) {
          echo "<font class='error'>".t("VIRHE: Sarjanumeroiden määrä on oltava sama kuin laskettu syötetty määrä")."! $tuoteno $kpl</font><br>";
          $virhe = 1;
        }
        elseif (substr($kpl, 0, 1) == '+' and is_array($sarjanumero_kaikki[$i]) and $onko_vanhoja > 0) {
          echo "<font class='error'>".t("VIRHE: Et voi lisätä kuin uusia sarjanumeroita relatiivisella määrällä")."! $tuoteno $kpl</font><br>";
          $virhe = 1;
        }
        elseif (substr($kpl, 0, 1) == '-' and is_array($sarjanumero_kaikki[$i]) and count($sarjanumero_valitut[$i]) != (int) substr($kpl, 1)) {
          echo "<font class='error'>".t("VIRHE: Sarjanumeroiden määrä on oltava sama kuin laskettu syötetty määrä")."! $tuoteno $kpl</font><br>";
          $virhe = 1;
        }
        elseif (substr($kpl, 0, 1) != '-' and substr($kpl, 0, 1) != '+' and is_array($sarjanumero_kaikki[$i]) and count($sarjanumero_valitut[$i]) != (int) $kpl) {
          echo "<font class='error'>".t("VIRHE: Sarjanumeroiden määrä on oltava sama kuin laskettu syötetty määrä")."! $tuoteno $kpl</font><br>";
          $virhe = 1;
        }

        if (isset($eranumero_kaikki[$i]) and is_array($eranumero_kaikki[$i])) {
          if (is_array($eranumero_valitut[$i])) {

            $erasyotetyt = 0;
            $_uudet = array();

            foreach ($eranumero_valitut[$i] as $enro => $ekpl) {
              $ekpl = str_replace(",", ".", $ekpl);

              if ($ekpl != '' and (substr($ekpl, 0, 1) == '+' or substr($ekpl, 0, 1) == '-' or !is_numeric($ekpl))) {
                echo "<font class='error'>".t("VIRHE: Erien määrät oltava absoluuttisia arvoja")."!</font><br>";
                $virhe = 1;
                break;
              }

              if ((substr($kpl, 0, 1) == '+' or substr($kpl, 0, 1) == '-') and (float) $ekpl == 0 and $ekpl != '' and $onko_uusia == 0) {
                echo "<font class='error'>".t("VIRHE: Et voi nollata erää, jos olet syöttänyt relatiivisen määrän")."!</font><br>";
                $virhe = 1;
                break;
              }

              $erasyotetyt += (float) $ekpl;

              if ($eranumero_uudet[$i][$enro] == '0000-00-00') {
                $onko_uusia++;
                $_uudet[$i][$enro] = $eranumero_valitut[$i][$enro];
              }
            }

            // Katsotaan ettei olla yritetty muokata vanhoja eriä
            // vanhojen erien muokkaus sekoittaa uusien erien lisäyksen
            $eravirhe = 0;
            if ($onko_uusia > 0) {
              foreach ($eranumero_valitut[$i] as $enro => $ekpl) {
                if (!empty($ekpl)) {
                  if (!array_key_exists($enro, $_uudet[$i])) {
                    $eravirhe = 1;
                    $virhe = 1;
                  }
                }
              }
            }

            if ($eravirhe == 1) {
              echo "<font class='error'>".t("VIRHE: Uusia eriä syötettäessä ei voi muokata vanhoja eriä")."!</font><br>";
            }

            $erasyotetyt = round($erasyotetyt, 2);

            if (is_array($eranumero_kaikki[$i]) and substr($kpl, 0, 1) != '+' and substr($kpl, 0, 1) != '-' and $onko_uusia > 0) {
              echo "<font class='error'>".t("VIRHE: Eränumeroita ei voi lisätä kuin relatiivisella määrällä")."! (+1)</font><br>";
              $virhe = 1;
            }
            elseif (substr($kpl, 0, 1) == '+' and is_array($eranumero_kaikki[$i]) and $erasyotetyt != substr($kpl, 1)) {
              echo "<font class='error'>".t("VIRHE: Eränumeroiden määrä on oltava sama kuin laskettu syötetty määrä")."! $tuoteno $kpl</font><br>";
              $virhe = 1;
            }
            elseif (substr($kpl, 0, 1) == '-' and is_array($eranumero_kaikki[$i]) and $erasyotetyt != substr($kpl, 1)) {
              echo "<font class='error'>".t("VIRHE: Eränumeroiden määrä on oltava sama kuin laskettu syötetty määrä")."! $tuoteno $kpl</font><br>";
              $virhe = 1;
            }
            elseif (substr($kpl, 0, 1) != '-' and substr($kpl, 0, 1) != '+' and is_array($eranumero_kaikki[$i]) and $erasyotetyt != $kpl) {
              echo "<font class='error'>".t("VIRHE: Eränumeroiden määrä on oltava sama kuin laskettu syötetty määrä")."! $tuoteno $kpl</font><br>";
              $virhe = 1;
            }
          }
          else {
            echo "<font class='error'>".t("VIRHE: Eränumeroiden määrä on oltava sama kuin laskettu syötetty määrä")."! $tuoteno $kpl</font><br>";
            $virhe = 1;
          }
        }

        if ($fileesta == "ON" and $virhe == 1) {
          $virhe = 0;
          continue;
        }

        //Haetaan tuotepaikan tiedot
        $query = "SELECT tuotepaikat.*, tuote.*, inventointilistarivi.luontiaika AS inventointilista_aika
                  FROM tuotepaikat
                  JOIN tuote ON (tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno)
                  LEFT JOIN inventointilistarivi ON (inventointilistarivi.yhtio = tuotepaikat.yhtio
                    AND inventointilistarivi.tuotepaikkatunnus = tuotepaikat.tunnus
                    AND inventointilistarivi.tila              = 'A')
                  WHERE tuotepaikat.yhtio                      = '$kukarow[yhtio]'
                  and tuotepaikat.tuoteno                      = '$tuoteno'
                  and tuotepaikat.hyllyalue                    = '$hyllyalue'
                  and tuotepaikat.hyllynro                     = '$hyllynro'
                  and tuotepaikat.hyllyvali                    = '$hyllyvali'
                  and tuotepaikat.hyllytaso                    = '$hyllytaso'";
        $result = pupe_query($query);

        if (mysql_num_rows($result) == 0 and $virhe != 1) {

          if ($yhtiorow['kerayserat'] == 'K') {
            $hyllyalue = strtoupper($hyllyalue);
            $hyllynro  = strtoupper($hyllynro);
            $hyllyvali = strtoupper($hyllyvali);
            $hyllytaso = strtoupper($hyllytaso);

            if (!tarkista_varaston_hyllypaikka($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso)) {
              echo "<font class='error'>".t("VIRHE: Varastopaikkaa ei ole olemassa")."! $tuoteno $hyllyalue-$hyllynro-$hyllyvali-$hyllytaso</font><br>";
              $virhe = 1;
            }
          }

          if (($lisaselite == "PERUSTA" or $fileesta == "ON") and  $virhe != 1) {
            // PERUSTETAAN tuotepaikka

            // katotaa löytyykö tuote
            $query = "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
            $result = pupe_query($query);

            if (mysql_num_rows($result) == 1) {

              // katotaan onko tuotteella jo oletuspaikka
              $query = "SELECT *
                        FROM tuotepaikat
                        WHERE yhtio  = '$kukarow[yhtio]'
                        AND tuoteno  = '$tuoteno'
                        AND oletus  != ''";
              $result = pupe_query($query);

              if (mysql_num_rows($result) > 0) {
                $oletus = "";
              }
              else {
                $oletus = "X";
              }

              lisaa_tuotepaikka($tuoteno, $hyllyalue, $hyllynro, $hyllyvali, $hyllytaso, 'Inventoidessa', $oletus, 0, 0, 0);

              // haetaan perustettu resultti (sama query ku ylhäällä)
              $query = "SELECT *
                        FROM tuotepaikat
                        JOIN tuote ON (tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno)
                        WHERE tuotepaikat.yhtio   = '$kukarow[yhtio]'
                        and tuotepaikat.tuoteno   = '$tuoteno'
                        and tuotepaikat.hyllyalue = '$hyllyalue'
                        and tuotepaikat.hyllynro  = '$hyllynro'
                        and tuotepaikat.hyllyvali = '$hyllyvali'
                        and tuotepaikat.hyllytaso = '$hyllytaso'";
              $result = pupe_query($query);

              if (mysql_num_rows($result) == 1) {
                //echo "<font class='error'>".t("Perustettiin varastopaikka tuotteelle")." $tuoteno $hyllyalue-$hyllynro-$hyllyvali-$hyllytaso</font><br>";
              }
              else {
                echo "<font class='error'>(".mysql_num_rows($result).") ".t("Varastopaikan perustus epäonnistui")." $tuoteno $hyllyalue-$hyllynro-$hyllyvali-$hyllytaso $query</font><br>";
              }
            }
            else {
              echo "<font class='error'>".t("Tuotetta ei löydy")." $tuoteno</font><br>";
            }
          }
          else {
            echo "<font class='error'>".t("Varastopaikka ei löydy tuotteelta")." $tuoteno $hyllyalue-$hyllynro-$hyllyvali-$hyllytaso</font><br>";
          }
        }

        if (mysql_num_rows($result) == 1 and $virhe != 1) {
          $row = mysql_fetch_assoc($result);

          if (($lista != '' and $row["inventointilista_aika"] !== null) or
            ($validi_kasinsyotetty_inventointipaivamaara) or
            ($lista == '' and $row["inventointilista_aika"] === null)) {

            if ($validi_kasinsyotetty_inventointipaivamaara) {
              $row['inventointilista_aika'] = $laadittuaika;
            }

            //jos invataan raportin avulla niin tehdään päivämäärätsekit ja lasketaan saldo takautuvasti
            $saldomuutos = 0;
            $kerattymuut = 0;
            $saldomuutoskeratty = 0;

            if ($row["sarjanumeroseuranta"] == "" and $row["inventointilista_aika"] !== null and $mobiili != "YES") {
              //katotaan paljonko saldot on muuttunut listan ajoajankohdasta
              //paljonko rivejä on kerätty listan ajohetkellä, jotka ovat nyt laskutettu
              $query = "SELECT
                        ifnull(sum(tapahtuma.kpl), 0) muutos,
                        ifnull(sum(if(tilausrivi.tyyppi in ('L','G','V') and tilausrivi.kerattyaika < '{$row['inventointilista_aika']}' and tilausrivi.kerattyaika > 0, tapahtuma.kpl * -1, 0)), 0) keratty
                        FROM tapahtuma
                        JOIN tilausrivi ON (tapahtuma.yhtio = tilausrivi.yhtio
                          and tapahtuma.rivitunnus  = tilausrivi.tunnus
                          and tilausrivi.hyllyalue  = '$hyllyalue'
                          and tilausrivi.hyllynro   = '$hyllynro'
                          and tilausrivi.hyllyvali  = '$hyllyvali'
                          and tilausrivi.hyllytaso  = '$hyllytaso')
                        WHERE tapahtuma.yhtio       = '$kukarow[yhtio]'
                        and tapahtuma.tuoteno       = '$tuoteno'
                        and tapahtuma.laadittu      >= '{$row['inventointilista_aika']}'
                        and tapahtuma.kpl           <> 0
                        and tapahtuma.laji         != 'Inventointi'";
              $result = pupe_query($query);
              $trow = mysql_fetch_assoc($result);

              if ($trow["muutos"] != 0) {
                $saldomuutos = $trow["muutos"];
              }

              if ($trow["keratty"] != 0) {
                $saldomuutoskeratty = $trow["keratty"];
              }

              // kuinka monta kerättyä oli listan ajohetkellä, mutta ovat vielä laskuttamatta
              $query = "SELECT ifnull(sum(varattu), 0) keratty
                        FROM tilausrivi
                        WHERE yhtio        = '$kukarow[yhtio]'
                        and tyyppi         in ('L','G','V')
                        and tuoteno        = '$tuoteno'
                        and varattu        <> 0
                        and kerattyaika    < '{$row['inventointilista_aika']}'
                        and kerattyaika    > 0
                        and laskutettuaika = 0
                        and hyllyalue      = '$hyllyalue'
                        and hyllynro       = '$hyllynro'
                        and hyllyvali      = '$hyllyvali'
                        and hyllytaso      = '$hyllytaso'";
              $hylresult = pupe_query($query);
              $hylrow = mysql_fetch_assoc($hylresult);

              if ($hylrow['keratty'] != 0) {
                $kerattymuut = $hylrow['keratty'];
              }
            }
            elseif ($row["sarjanumeroseuranta"] == "") {
              //Haetaan kerätty määrä
              $query = "SELECT ifnull(sum(if(keratty!='', varattu, 0)), 0) keratty
                        FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
                        WHERE yhtio    = '$kukarow[yhtio]'
                        and tyyppi     in ('L','G','V')
                        and tuoteno    = '$tuoteno'
                        and varattu    <> 0
                        and laskutettu = ''
                        and hyllyalue  = '$hyllyalue'
                        and hyllynro   = '$hyllynro'
                        and hyllyvali  = '$hyllyvali'
                        and hyllytaso  = '$hyllytaso'";
              $hylresult = pupe_query($query);
              $hylrow = mysql_fetch_assoc($hylresult);

              if ($hylrow['keratty'] != 0) {
                $kerattymuut = $hylrow['keratty'];
              }
            }

            if (substr($kpl, 0, 1) == '+') {
              $kpl = substr($kpl, 1);
              $skp = $kpl;
              $kpl = $row['saldo'] + $kpl;

            }
            elseif (substr($kpl, 0, 1) == '-') {
              $kpl = substr($kpl, 1);
              $skp = $kpl*-1;
              $kpl = $row['saldo'] - $kpl;
            }
            else {
              //$kpl on käyttäjän syöttämä hyllysäoleva määrä joka muutetaan saldoksi lisäämällä siihen kerätyt kappaleet
              //ja ottamalla huomioon $saldomuutos joka on saldon muutos listan ajohetkestä
              $kpl = $kpl + $kerattymuut + $saldomuutos + $saldomuutoskeratty;
              $skp = 0;
            }

            $nykyinensaldo = $row['saldo'];
            $erotus = $kpl - $row['saldo'];
            $cursaldo = $nykyinensaldo + $erotus;

            // triggeröidään erohälytys
            if (abs($erotus) > 10 and $lista != '') {
              $virhe = 2;
            }

            //echo "Tuoteno: $tuoteno Saldomuutos: $saldomuutos Kerätty: $kerattymuut Syötetty: $kpl Hyllyssä: $hyllyssa Nykyinen: $nykyinensaldo Erotus: $erotus<br>";

            ///* Inventointipoikkeama prosenteissa *///
            if ($nykyinensaldo != 0) {
              $poikkeama = ($erotus/$nykyinensaldo)*100;
            }
            ///* Tehdään jonkinlainen arvaus jos saldo on nolla *///
            else {
              $poikkeama = ($erotus/1)*100;
            }

            // Lasketaan varastonarvon muutos
            // S = Sarjanumeroseuranta. Osto-Myynti / In-Out varastonarvo
            // T = Sarjanumeroseuranta. Myynti / Keskihinta-varastonarvo
            // V = Sarjanumeroseuranta. Osto-Myynti / Keskihinta-varastonarvo
            // E = Eränumeroseuranta. Osto-Myynti / Keskihinta-varastonarvo
            // F = Eränumeroseuranta parasta-ennen päivällä. Osto-Myynti / Keskihinta-varastonarvo
            // G = Eränumeroseuranta. Osto-Myynti / In-Out varastonarvo
            if ($row["sarjanumeroseuranta"] == "S" or $row["sarjanumeroseuranta"] == "G") {

              $varvo_ennen = 0;
              $varvo_jalke = 0;
              $varvo_muuto = 0;

              // ollaan syötetty absoluuttinen määrä
              if ((float) $skp == 0) {
                if ($row["sarjanumeroseuranta"] == "G") {
                  foreach ($eranumero_kaikki[$i] as $enro_tun => $enro_arvo) {
                    $varvo_ennen += (sarjanumeron_ostohinta("tunnus", $enro_tun) * $enro_arvo);
                  }

                  foreach ($eranumero_valitut[$i] as $enro_tun => $enro_arvo) {
                    $enro_arvo = (float) str_replace(",", ".", $enro_arvo);

                    $varvo_jalke += (sarjanumeron_ostohinta("tunnus", $enro_tun) * $enro_arvo);
                  }
                }
                else {
                  // Ei ruksatut sarjanumerot poistetaan
                  foreach ($sarjanumero_kaikki[$i] as $snro_tun => $snro_arvo) {
                    $varvo_ennen += sarjanumeron_ostohinta("tunnus", $snro_tun);
                  }

                  foreach ($sarjanumero_valitut[$i] as $snro_tun => $snro_arvo) {
                    $varvo_jalke += sarjanumeron_ostohinta("tunnus", $snro_tun);
                  }
                }

                $summa = round($varvo_jalke - $varvo_ennen, 6);
              }
              // ollaan syötetty relatiivinen määrä
              elseif ((float) $skp != 0) {
                if ($row["sarjanumeroseuranta"] == "G") {
                  foreach ($eranumero_valitut[$i] as $enro_tun => $enro_arvo) {
                    $enro_arvo = (float) str_replace(",", ".", $enro_arvo);

                    // katsotaan varastonarvo vain, jos ollaan lisäämässä tai kyseessä on vanha tuote
                    if ($eranumero_uudet[$i][$enro_tun] != '0000-00-00' or $skp > 0) {
                      $varvo_muuto += (sarjanumeron_ostohinta("tunnus", $enro_tun) * $enro_arvo);
                    }
                    else {
                      // ollaan poistamatta uutta eränumeroa, kplmäärä nollataan joten ei tapahtu varastonmuutosta!!
                      $erotus = 0;
                      break;
                    }
                  }
                }
                else {
                  foreach ($sarjanumero_valitut[$i] as $snro_tun => $snro_arvo) {
                    // katsotaan varastonarvo vain, jos ollaan lisäämässä tai kyseessä on vanha tuote
                    if ($sarjanumero_uudet[$i][$snro_tun] != '0000-00-00' or $skp > 0) {
                      $varvo_muuto += sarjanumeron_ostohinta("tunnus", $snro_tun);
                    }
                    else {
                      // ollaan poistamatta uutta eränumeroa, kplmäärä nollataan joten ei tapahtu varastonmuutosta!!
                      $erotus = 0;
                      break;
                    }
                  }
                }

                // ruksatut on varastonmuutos
                if ($skp < 0) {
                  $summa = round($varvo_muuto * -1, 6);
                }
                else {
                  $summa = round($varvo_muuto, 6);
                }
              }
              else {
                echo "<font class='error'>".t("VIRHE: Tänne ei pitäisi päästä")."! $tuoteno $kpl</font><br>";
                exit;
              }

              $row['kehahin'] = round(abs($summa) / abs($erotus), 6);
            }
            else {
              if     ($row['epakurantti100pvm'] != '0000-00-00') $row['kehahin'] = 0;
              elseif   ($row['epakurantti75pvm']  != '0000-00-00') $row['kehahin'] = round($row['kehahin'] * 0.25, 6);
              elseif   ($row['epakurantti50pvm']  != '0000-00-00') $row['kehahin'] = round($row['kehahin'] * 0.5, 6);
              elseif  ($row['epakurantti25pvm']  != '0000-00-00') $row['kehahin'] = round($row['kehahin'] * 0.75, 6);

              if ($row['sarjanumeroseuranta'] == 'T' or $row['sarjanumeroseuranta'] == 'V') {
                foreach ($sarjanumero_valitut[$i] as $snro_tun => $snro_arvo) {
                  // katsotaan varastonarvo vain, jos ollaan lisäämässä tai kyseessä on vanha tuote
                  if ($sarjanumero_uudet[$i][$snro_tun] == '0000-00-00' and $skp < 0) {
                    // ollaan poistamatta uutta sarjanumeroa, kplmäärä nollataan joten ei tapahtu varastonmuutosta!!
                    $erotus = 0;
                    break;
                  }
                }
              }
              elseif ($row['sarjanumeroseuranta'] == 'E' or $row['sarjanumeroseuranta'] == 'F') {
                foreach ($eranumero_valitut[$i] as $enro_tun => $enro_arvo) {
                  $enro_arvo = (float) str_replace(",", ".", $enro_arvo);

                  if ($eranumero_uudet[$i][$enro_tun] == '0000-00-00' and $skp < 0) {
                    // ollaan poistamatta uutta eränumeroa, kplmäärä nollataan joten ei tapahtu varastonmuutosta!!
                    $erotus = 0;
                    break;
                  }
                }
              }

              $summa = round($erotus * $row['kehahin'], 2);
              $tarkistus_summa = round($cursaldo * $row['kehahin'], 2);
            }

            // jos loppusumma on isompi kuin tietokannassa oleva tietuen koko (10 numeroa + 2 desimaalia), niin herjataan
            if (($summa != '' and abs($summa) > 0) or ($tarkistus_summa != '' and abs($tarkistus_summa) > 0)) {
              // Katsotaan kumpi tarkistusluvuista on isompi
              // tiliöitävä summa vai tarkistussumma
              if ($summa >= $tarkistus_summa) {
                $ylitettava_summa_chk = $summa;
              }
              else {
                $ylitettava_summa_chk = $tarkistus_summa;
              }

              if (abs($ylitettava_summa_chk) > 9999999999.99) {
                echo "<font class='error'>".t("VIRHE: liian iso loppusumma")."!<br/>", t("Tuote"), ": $tuoteno ", t("lopullinen kappalemäärä"), " $kpl (", t("loppusumma"), ": $ylitettava_summa_chk)</font><br>";
                $virhe = 1;
                break;
              }
            }

            $_selitelisa = (stripos($lisaselite, t("Inv.lista")." {$lista}") === FALSE);

            if ($laaja_inventointilista and $_selitelisa) {

              if ($lisaselite != "") {
                $lisaselite .= " - ";
              }

              $lisaselite .=  t("Inv.lista")." {$lista}";
            }

            if (substr($toim, 0, 4) == "OSTO") {

              $query = "SELECT *, if (jarjestys = 0, 9999, jarjestys) sorttaus
                        FROM tuotteen_toimittajat
                        WHERE yhtio = '$kukarow[yhtio]'
                        and tuoteno = '$row[tuoteno]'
                        ORDER BY sorttaus
                        LIMIT 1";
              $otres = pupe_query($query);
              $otrow = mysql_fetch_assoc($otres);

              if ($fileesta != 'ON' and $ostohinta != 0 and $otrow['ostohinta'] != $ostohinta and $tuotteen_toimittaja != '') {
                $query = "UPDATE tuotteen_toimittajat set
                          ostohinta     = $ostohinta
                          WHERE yhtio = '$kukarow[yhtio]'
                          and tuoteno = '$row[tuoteno]'
                          and liitostunnus = $tuotteen_toimittaja";
                pupe_query($query);

                $otrow['ostohinta'] = $ostohinta;
              }

              $query = "SELECT sum(saldo) kokonaissaldo
                        FROM tuotepaikat
                        WHERE yhtio = '$kukarow[yhtio]'
                        and tuoteno = '$row[tuoteno]'";
              $ksres = pupe_query($query);
              $ksrow = mysql_fetch_assoc($ksres);

              // kehahin matikka, tuotteella pitää olla saldoa ennen ja jälkeen että edes tehdään matikkaa, sekä jakolaskun osoittaja pitää olla positiivinen
              $kehahin = round(($ksrow['kokonaissaldo'] * $row['kehahin'] + $otrow['ostohinta'] * $erotus) / ($ksrow['kokonaissaldo'] + $erotus), 6);

              $query = "UPDATE tuote set
                        kehahin     = $kehahin,
                        vihahin     = round('$otrow[ostohinta]','$yhtiorow[hintapyoristys]'),
                        vihapvm     = now()
                        WHERE yhtio = '$kukarow[yhtio]'
                        and tuoteno = '$row[tuoteno]'";
              pupe_query($query);

              $laji = "tulo";
              $_kplhinta  = $otrow["ostohinta"];
              $_kehahinta = $row["kehahin"];

              $summa = round($otrow["ostohinta"] * $erotus, 2);

              $selite = "Tuloutus: $erotus kappaletta. Ostohinta: $otrow[ostohinta] Varastopaikka: $hyllyalue $hyllynro $hyllyvali $hyllytaso";
              $laadittuaikalisa = "now()";
            }
            else {

              $laji = "Inventointi";
              $_kplhinta  = $row["kehahin"];
              $_kehahinta = $row["kehahin"];

              if ($erotus > 0) {
                $selite = t("Saldoa")." ($nykyinensaldo) ".t("paikalla")." $hyllyalue-$hyllynro-$hyllyvali-$hyllytaso ".t("lisättiin")." $erotus ".t("kappaleella. Saldo nyt")." $cursaldo. <br>$lisaselite<br>$inven_laji";
              }
              elseif ($erotus < 0) {
                $selite = t("Saldoa")." ($nykyinensaldo) ".t("paikalla")." $hyllyalue-$hyllynro-$hyllyvali-$hyllytaso ".t("vähennettiin")." ".abs($erotus)." ".t("kappaleella. Saldo nyt")." $cursaldo. <br>$lisaselite<br>$inven_laji";
              }
              else {
                $selite = t("Saldo")." ($nykyinensaldo) ".t("paikalla")." $hyllyalue-$hyllynro-$hyllyvali-$hyllytaso ".t("täsmäsi").".<br>$lisaselite<br>$inven_laji";
              }

              $laadittuaikalisa = $laadittuaika != "now()" ? "'{$laadittuaika}'" : $laadittuaika;
            }

            ///* Tehdään tapahtuma *///
            $query = "INSERT into tapahtuma set
                      yhtio     = '$kukarow[yhtio]',
                      tuoteno   = '$tuoteno',
                      laji      = '$laji',
                      kpl       = '$erotus',
                      kplhinta  = '$_kplhinta',
                      hinta     = '$_kehahinta',
                      hyllyalue = '$hyllyalue',
                      hyllynro  = '$hyllynro',
                      hyllyvali = '$hyllyvali',
                      hyllytaso = '$hyllytaso',
                      selite    = '$selite',
                      laatija   = '$kukarow[kuka]',
                      laadittu  = {$laadittuaikalisa}";
            $result = pupe_query($query);

            // otetaan tapahtuman tunnus, laitetaan se tiliöinnin otsikolle
            $tapahtumaid = mysql_insert_id($GLOBALS["masterlink"]);

            $query = "UPDATE inventointilistarivi SET
                      aika            = now(),
                      tila            = 'I',
                      tapahtumatunnus = '{$tapahtumaid}'
                      WHERE yhtio     = '{$kukarow['yhtio']}'
                      AND tuoteno     = '{$tuoteno}'
                      AND hyllyalue   = '{$hyllyalue}'
                      AND hyllynro    = '{$hyllynro}'
                      AND hyllyvali   = '{$hyllyvali}'
                      AND hyllytaso   = '{$hyllytaso}'
                      AND tila        = 'A'";
            $_chk_res = pupe_query($query);

            // Päivitetään tuotepaikka
            $query = "UPDATE tuotepaikat";

            if ($erotus > 0) {
              $query .= " SET saldo = saldo+$erotus, ";
            }
            elseif ($erotus < 0) {
              $query .= " SET saldo = saldo-".abs($erotus).", ";
            }
            else {
              $query .= " SET saldo = saldo, ";
            }

            $query .= " saldoaika             = now(),
                        inventointiaika       = {$laadittuaikalisa},
                        inventointipoikkeama  = '$poikkeama',
                        muuttaja              = '$kukarow[kuka]',
                        muutospvm             = now()
                        WHERE yhtio           = '$kukarow[yhtio]'
                        and tuoteno           = '$tuoteno'
                        and hyllyalue         = '$hyllyalue'
                        and hyllynro          = '$hyllynro'
                        and hyllyvali         = '$hyllyvali'
                        and hyllytaso         = '$hyllytaso'";
            $result = pupe_query($query);

            // Jos pävitettiin saldoa, tehdään kirjanpito. Vaikka summa olisi nolla. Muuten jälkilaskenta ei osaa korjata tätä, jos tiliöintejä ei tehdä.
            if (mysql_affected_rows() > 0) {

              // Päivämäärällä inventoitaessa laitetaan tämäpäivämäärä,
              // jos eri päivämäärä ei ole syötetty,
              // mutta jos päivämäärä on syötetty laitetaan se luotavan laskun tapahtumapäivämääräksi
              $lasku_tapvm = date('Y-m-d');

              if ($paivamaaran_kasisyotto == "JOO" and (!empty($inventointipvm_pp) and !empty($inventointipvm_kk) and !empty($inventointipvm_vv))) {
                $lasku_tapvm = "$inventointipvm_vv-$inventointipvm_kk-$inventointipvm_pp";
              }

              $query = "INSERT into lasku set
                        yhtio      = '$kukarow[yhtio]',
                        tapvm      = '{$lasku_tapvm}',
                        tila       = 'X',
                        alatila    = 'I',
                        laatija    = '$kukarow[kuka]',
                        viite      = '$tapahtumaid',
                        luontiaika = now()";
              $result = pupe_query($query);
              $laskuid = mysql_insert_id($GLOBALS["masterlink"]);

              // Seuraako myyntitiliöinti tuotteen tyyppiä ja onko kyseessä raaka-aine?
              $raaka_aine_tiliointi = $yhtiorow["raaka_aine_tiliointi"];
              $raaka_ainetililta = ($raaka_aine_tiliointi == "Y" and $row["tuotetyyppi"] == "R");

              // Määritetään varastonmuutostili
              if ($raaka_ainetililta) {
                $varastonmuutos_tili = $yhtiorow["raaka_ainevarastonmuutos"];
              }
              elseif ($yhtiorow["varastonmuutos_inventointi"] != "") {
                if ($inven_laji_tilino != "") {
                  $varastonmuutos_tili = $inven_laji_tilino;
                }
                else {
                  $varastonmuutos_tili = $yhtiorow["varastonmuutos_inventointi"];
                }
              }
              else {
                $varastonmuutos_tili = $yhtiorow["varastonmuutos"];
              }

              // Määritetään varastotili
              if ($raaka_ainetililta) {
                $varastotili = $yhtiorow["raaka_ainevarasto"];
              }
              else {
                $varastotili = $yhtiorow["varasto"];
              }

              if ($yhtiorow["tarkenteiden_prioriteetti"] == "T") {

                $query = "SELECT toimipaikka
                          FROM varastopaikat
                          WHERE yhtio = '$kukarow[yhtio]'
                          AND concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper('$hyllyalue'), 5, '0'),lpad(upper('$hyllynro'), 5, '0'))
                          AND concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper('$hyllyalue'), 5, '0'),lpad(upper('$hyllynro'), 5, '0'))";
                $varcheckres = pupe_query($query);
                $varcheckrow = mysql_fetch_assoc($varcheckres);

                unset($toimipaikkarow);

                if ($varcheckrow['toimipaikka'] > 0) {
                  $query = "SELECT kustp, kohde, projekti
                            FROM yhtion_toimipaikat
                            WHERE yhtio = '{$kukarow['yhtio']}'
                            AND tunnus  = {$varcheckrow['toimipaikka']}";
                  $toimipaikkares = pupe_query($query);
                  $toimipaikkarow = mysql_fetch_assoc($toimipaikkares);
                }

                // Otetaan ensisijaisesti kustannuspaikka toimipaikan takaa
                $kustp_ins     = (isset($toimipaikkarow) and $toimipaikkarow["kustp"] > 0) ? $toimipaikkarow["kustp"] : $tuote_row["kustp"];
                $kohde_ins     = (isset($toimipaikkarow) and $toimipaikkarow["kohde"] > 0) ? $toimipaikkarow["kohde"] : $tuote_row["kohde"];
                $projekti_ins  = (isset($toimipaikkarow) and $toimipaikkarow["projekti"] > 0) ? $toimipaikkarow["projekti"] : $tuote_row["projekti"];
              }
              else {
                // Otetaan ensisijaisesti kustannuspaikka tuotteen takaa
                $kustp_ins     = $tuote_row["kustp"];
                $kohde_ins     = $tuote_row["kohde"];
                $projekti_ins   = $tuote_row["projekti"];
              }

              // Kokeillaan varastonmuutos tilin oletuskustannuspaikalle
              list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($varastonmuutos_tili, $kustp_ins, $kohde_ins, $projekti_ins);

              // Toissijaisesti kokeillaan vielä varasto-tilin oletuskustannuspaikkaa
              list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yhtiorow["varasto"], $kustp_ins, $kohde_ins, $projekti_ins);

              $tiliointisumma = round($summa, 2);

              $tapvm = date('Y-m-d');

              if ($paivamaaran_kasisyotto == "JOO" and (!empty($inventointipvm_pp) and !empty($inventointipvm_kk) and !empty($inventointipvm_vv))) {
                $tapvm = "$inventointipvm_vv-$inventointipvm_kk-$inventointipvm_pp";
              }

              $query = "INSERT into tiliointi set
                        yhtio    = '$kukarow[yhtio]',
                        ltunnus  = '$laskuid',
                        tilino   = '{$varastotili}',
                        kustp    = '{$kustp_ins}',
                        kohde    = '{$kohde_ins}',
                        projekti = '{$projekti_ins}',
                        tapvm    = '{$tapvm}',
                        summa    = $tiliointisumma,
                        vero     = 0,
                        lukko    = '',
                        selite   = 'Inventointi: ".t("Tuotteen")." {$row["tuoteno"]} $selite',
                        laatija  = '$kukarow[kuka]',
                        laadittu = now()";
              $result = pupe_query($query);

              $query = "INSERT into tiliointi set
                        yhtio    = '$kukarow[yhtio]',
                        ltunnus  = '$laskuid',
                        tilino   = '$varastonmuutos_tili',
                        kustp    = '{$kustp_ins}',
                        kohde    = '{$kohde_ins}',
                        projekti = '{$projekti_ins}',
                        tapvm    = '{$tapvm}',
                        summa    = $tiliointisumma * -1,
                        vero     = 0,
                        lukko    = '',
                        selite   = 'Inventointi: ".t("Tuotteen")." {$row["tuoteno"]} $selite',
                        laatija  = '$kukarow[kuka]',
                        laadittu = now()";
              $result = pupe_query($query);
            }

            // SARJANUMEROIDEN KÄSITTELY
            if (is_array($sarjanumero_kaikki[$i]) and count($sarjanumero_kaikki[$i]) > 0) {
              if ((float) $skp == 0) {
                // Ei ruksatut sarjanumerot poistetaan
                foreach ($sarjanumero_kaikki[$i] as $snro_tun) {
                  if (!is_array($sarjanumero_valitut[$i]) or !in_array($snro_tun, $sarjanumero_valitut[$i])) {
                    $query = "UPDATE sarjanumeroseuranta
                              SET myyntirivitunnus = '-1',
                              siirtorivitunnus  = '-1',
                              muuttaja          = '$kukarow[kuka]',
                              muutospvm         = now(),
                              inventointitunnus = $tapahtumaid
                              WHERE yhtio       = '$kukarow[yhtio]'
                              and tunnus        = $snro_tun";
                    $sarjares = pupe_query($query);
                  }
                  elseif (isset($sarjanumero_uudet[$i])) {
                    foreach ($sarjanumero_uudet[$i] as $snro_key => $snro_val) {

                      $query = "SELECT ostorivitunnus
                                FROM sarjanumeroseuranta
                                WHERE yhtio = '$kukarow[yhtio]'
                                AND tunnus  = $snro_key";
                      $sarjares = pupe_query($query);
                      $sarjarow_x = mysql_fetch_assoc($sarjares);

                      $query = "UPDATE tilausrivi
                                SET laskutettuaika = now()
                                WHERE yhtio        = '$kukarow[yhtio]'
                                AND tunnus         = '$sarjarow_x[ostorivitunnus]'
                                AND laskutettuaika = '0000-00-00'";
                      $sarjares = pupe_query($query);
                    }
                  }
                }
              }
              elseif ((float) $skp < 0) {
                // Muutetaan $skp-verrran miinus etumerkeillä poistetaan
                foreach ($sarjanumero_valitut[$i] as $snro_tun) {
                  $query = "UPDATE sarjanumeroseuranta
                            SET myyntirivitunnus = '-1',
                            siirtorivitunnus  = '-1',
                            muuttaja          = '$kukarow[kuka]',
                            muutospvm         = now(),
                            inventointitunnus = $tapahtumaid
                            WHERE yhtio       = '$kukarow[yhtio]'
                            and tunnus        = $snro_tun";
                  $sarjares = pupe_query($query);
                }
              }
              elseif ((float) $skp > 0 and $onko_uusia > 0) {
                foreach ($sarjanumero_uudet[$i] as $snro_key => $snro_val) {

                  $query = "SELECT ostorivitunnus
                            FROM sarjanumeroseuranta
                            WHERE yhtio = '$kukarow[yhtio]'
                            AND tunnus  = $snro_key";
                  $sarjares = pupe_query($query);
                  $sarjarow_x = mysql_fetch_assoc($sarjares);

                  $query = "UPDATE tilausrivi
                            SET laskutettuaika = now()
                            WHERE yhtio        = '$kukarow[yhtio]'
                            AND tunnus         = '$sarjarow_x[ostorivitunnus]'
                            AND laskutettuaika = '0000-00-00'";
                  $sarjares = pupe_query($query);
                }
              }
            }

            //ERÄNUMEROIDEN KÄSITTELY
            if (is_array($eranumero_kaikki[$i]) and count($eranumero_kaikki[$i]) > 0) {

              // Ollaan syötetty absoluuttinen määrä ($skp:ssa relatiivinen määrä)
              if ((float) $skp == 0) {

                foreach ($eranumero_valitut[$i] as $enro_key => $enro_val) {
                  $enro_val = (float) str_replace(",", ".", $enro_val);

                  $sarjaquerylisa = '';

                  // jos erä loppuu, niin poistetaan kyseinen erä
                  if ((float) $enro_val == 0) {
                    $sarjaquerylisa = "myyntirivitunnus = '-1', siirtorivitunnus = '-1', ";
                  }

                  $query = "UPDATE sarjanumeroseuranta
                            SET era_kpl = '$enro_val',
                            $sarjaquerylisa
                            muuttaja    = '$kukarow[kuka]',
                            muutospvm   = now()
                            WHERE yhtio = '$kukarow[yhtio]'
                            and tunnus  = $enro_key";
                  $sarjares = pupe_query($query);
                }
              }
              elseif ((float) $skp < 0 or (float) $skp > 0) {

                // Ollaan syötetty relatiivinen määrä
                foreach ($eranumero_valitut[$i] as $enro_key => $enro_val) {
                  $enro_val = (float) str_replace(",", ".", $enro_val);

                  if ((float) $enro_val > 0) {

                    if ($skp < 0) {
                      $mita_jaa = $eranumero_kaikki[$i][$enro_key] - $enro_val;
                    }
                    elseif ($skp > 0 and $onko_uusia == 0) {
                      $mita_jaa = $eranumero_kaikki[$i][$enro_key] + $enro_val;
                    }
                    else {
                      $mita_jaa = $enro_val;
                    }

                    $sarjaquerylisa = '';

                    // jos erä loppuu niin poistetaan kyseinen erä
                    if ($mita_jaa == 0) {
                      $sarjaquerylisa = "myyntirivitunnus = '-1', siirtorivitunnus = '-1', ";
                    }

                    $query = "UPDATE sarjanumeroseuranta
                              SET era_kpl = '$mita_jaa',
                              $sarjaquerylisa
                              muuttaja    = '$kukarow[kuka]',
                              muutospvm   = now()
                              WHERE yhtio = '$kukarow[yhtio]'
                              and tunnus  = $enro_key";
                    $sarjares = pupe_query($query);
                  }
                  elseif ($enro_val != '' and (float) $enro_val == 0) {
                    $query = "UPDATE sarjanumeroseuranta
                              SET myyntirivitunnus = '-1',
                              siirtorivitunnus = '-1',
                              muuttaja         = '$kukarow[kuka]',
                              muutospvm        = now()
                              WHERE yhtio      = '$kukarow[yhtio]'
                              and tunnus       = $enro_key";
                    $sarjares = pupe_query($query);
                  }
                }

                // päivitetään uusille sarjanumeroille laskutettuaika
                if ($onko_uusia > 0) {
                  foreach ($eranumero_uudet[$i] as $enro_key => $enro_val) {

                    $query = "SELECT ostorivitunnus
                              FROM sarjanumeroseuranta
                              WHERE yhtio = '$kukarow[yhtio]'
                              AND tunnus  = $enro_key";
                    $sarjares = pupe_query($query);
                    $sarjarow_x = mysql_fetch_assoc($sarjares);

                    $query = "UPDATE tilausrivi
                              SET laskutettuaika = now()
                              WHERE yhtio        = '$kukarow[yhtio]'
                              AND tunnus         = '$sarjarow_x[ostorivitunnus]'
                              AND laskutettuaika = '0000-00-00'";
                    $sarjares = pupe_query($query);
                  }
                }
              }
            }
          }

          if ($fileesta == "ON") {

            if (substr($toim, 0, 4) == "OSTO") {
              $_laji = t("tuloutettu");
            }
            else {
              $_laji = t("inventoitu");
            }

            echo "<font class='message'>".t("Tuote")."   $tuoteno $hyllyalue $hyllynro $hyllyvali $hyllytaso {$_laji}!</font><br>";
          }
        }
      }
      else {
        //echo "Tuote $tuoteno $hyllyalue $hyllynro $hyllyvali $hyllytaso Kappalemäärää ei syötetty!<br>";
      }
    }

    $query = "UNLOCK TABLES";
    pupe_query($query);
  }

  if ($virhe == 0 and $mobiili != "YES") {
    if (isset($prev)) {
      $alku = $alku-$rivimaara;
      $tee = "INVENTOI";
    }
    elseif (isset($next)) {
      $alku = $alku+$rivimaara;
      $tee = "INVENTOI";
    }
    elseif (isset($tallenna_laskettu_hyllyssa)) {
      $tee = "INVENTOI";
    }
    elseif (isset($valmis)) {
      $tee = "";
      $tmp_tuoteno = "";

      if ($lista == '' and $filusta == '') {
        $tmp_tuoteno = $tuoteno;
      }
    }

    // seuraava sivu
    $tuoteno    = "";
    $hyllyalue  = "";
    $hyllynro   = "";
    $hyllyvali  = "";
    $hyllytaso  = "";
    $kpl        = "";
    $poikkeama  = "";
  }
  elseif ($mobiili == "YES") {
    $tee = "MOBIILI";
  }
  else {
    $tee = "INVENTOI";
  }

  $_param_paalla = ($yhtiorow['inventointi_yhteenveto'] == "K");

  if ($_param_paalla and empty($tee) and empty($virhe) and $lista != '') {

    $lista = (int) $lista;

    $query = "SELECT inventointilistarivi.*
              FROM inventointilista
              JOIN inventointilistarivi ON (inventointilistarivi.yhtio = inventointilista.yhtio
                AND inventointilistarivi.otunnus = inventointilista.tunnus)
              JOIN tuotepaikat ON (tuotepaikat.yhtio = inventointilistarivi.yhtio
                AND tuotepaikat.tunnus           = inventointilistarivi.tuotepaikkatunnus)
              WHERE inventointilista.yhtio       = '{$kukarow['yhtio']}'
              AND inventointilista.tunnus        = '{$lista}'";
    $listares = pupe_query($query);

    $_loytyyko = false;

    while ($listarow = mysql_fetch_assoc($listares)) {

      $query = "SELECT *
                FROM tapahtuma
                WHERE yhtio   = '{$kukarow['yhtio']}'
                and tuoteno   = '{$listarow['tuoteno']}'
                and laji      = 'Inventointi'
                and hyllyalue = '{$listarow['hyllyalue']}'
                and hyllynro  = '{$listarow['hyllynro']}'
                and hyllyvali = '{$listarow['hyllyvali']}'
                and hyllytaso = '{$listarow['hyllytaso']}'
                and laadittu  = '{$listarow['aika']}'
                ORDER BY tunnus desc
                LIMIT 1";
      $tapresult = pupe_query($query);
      $taptrow = mysql_fetch_assoc($tapresult);

      if (!empty($taptrow['selite']) and strpos($taptrow['selite'], t("täsmäsi")) === false) {

        $_loytyyko = true;

        $taptrow["selite"] = preg_replace("/".t("paikalla")." .*?\-.*?\-.*?\-.*? /", "", $taptrow["selite"]);

        $_taulukko .= "<tr>
          <td valign='top'>$listarow[tuoteno]</td>
          <td>$listarow[hyllyalue] $listarow[hyllynro] $listarow[hyllyvali] $listarow[hyllytaso]</td>
          <td>".tv1dateconv($taptrow['laadittu'], "PITKA")."</td>
          <td>{$taptrow['selite']}</td>
          </tr>";
      }
    }

    if ($_loytyyko) {
      echo "<font class='message'>".t("Inventointi yhteenveto")."</font>";
      echo "<table>";
      echo "<tr>";
      echo "<th>".t("Tuoteno")."</th>";
      echo "<th>".t("Varastopaikka")."</th>";
      echo "<th>".t("Inventointiaika")."</th>";
      echo "<th>".t("Selite")."</th>";
      echo "</tr>";
      echo $_taulukko;
      echo "</table>";
    }
    else {
      echo t("Inventoinnissa kaikki täsmäsi");
    }

    echo "<br />";
  }

  if ($tee == "" and $lopetus != '') {
    lopetus($lopetus, "META");
  }
}

if ($tee == 'POISTAERANUMERO') {
  $query = "SELECT sarjanumeroseuranta.tunnus, tilausrivi_myynti.tunnus myyntitunnus, tilausrivi_osto.tunnus ostotunnus
            FROM sarjanumeroseuranta
            JOIN tuote USING (yhtio,tuoteno)
            LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
            JOIN tilausrivi tilausrivi_osto use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
            WHERE sarjanumeroseuranta.yhtio           = '$kukarow[yhtio]'
            and sarjanumeroseuranta.tuoteno           = '$tuoteno'
            and sarjanumeroseuranta.myyntirivitunnus != -1
            and tilausrivi_myynti.tunnus is null
             and tilausrivi_osto.laatija              = 'Invent'
            and tilausrivi_osto.laskutettuaika        = '0000-00-00'
            and (tuote.sarjanumeroseuranta not in ('E','F','G') or era_kpl != 0)
            and sarjanumeroseuranta.tunnus            = $sarjatunnus";
  $sarjares = pupe_query($query);

  if (mysql_num_rows($sarjares) == 1) {

    $sarjarow = mysql_fetch_assoc($sarjares);

    $query = "UPDATE tilausrivi
              SET tyyppi = 'D'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$sarjarow['ostotunnus']}'";
    pupe_query($query);

    $query = "DELETE FROM sarjanumeroseuranta
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$sarjarow['tunnus']}'";
    pupe_query($query);

    echo "<br>".t("Erä poistettu")."!<br><br>";
  }

  $tee = 'INVENTOI';
}

if ($tee == 'INVENTOI') {

  if (isset($tmp_tuoteno) and $tmp_tuoteno != '') {
    $tuoteno = $tmp_tuoteno;
  }

  //hakulause, tämä on sama kaikilla vaihtoehdoilla
  $select = " tuote.kehahin, tuote.sarjanumeroseuranta, tuotepaikat.oletus, tuotepaikat.tunnus tptunnus, tuote.tuoteno, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso, tuote.nimitys, tuote.yksikko, concat_ws(' ',tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) varastopaikka, inventointiaika, tuotepaikat.saldo, inventointilistarivi.tila as inventointilista_tila, inventointilistarivi.otunnus as inventointilista, inventointilistarivi.laskettu as inventointilista_laskettu, inventointilistarivi.hyllyssa as inventointilista_hyllyssa, inventointilistarivi.rivinro as inventointilista_rivinro, inventointilistarivi.luontiaika as inventointilista_aika, concat(lpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0')) sorttauskentta";

  if ($tuoteno != "" and $lista == "") {
    ///* Inventoidaan tuotenumeron perusteella *///
    $kutsu = " ".t("Tuote")." $tuoteno ";

    $query = "SELECT $select
              FROM tuote use index (tuoteno_index)
              JOIN tuotepaikat use index (tuote_index) USING (yhtio, tuoteno)
              LEFT JOIN inventointilistarivi ON (inventointilistarivi.yhtio = tuotepaikat.yhtio
                AND inventointilistarivi.tuotepaikkatunnus = tuotepaikat.tunnus
                AND inventointilistarivi.tila              = 'A')
              WHERE tuote.yhtio                            = '$kukarow[yhtio]'
              and tuote.tuoteno                            = '$tuoteno'
              and tuote.ei_saldoa                          = ''
              ORDER BY sorttauskentta, tuoteno";
    $saldoresult = pupe_query($query);

    if (mysql_num_rows($saldoresult) == 0) {
      echo "<font class='error'>".t("Tuote")." '$tuoteno' ".t("ei löydy!")." ".t("Onko tuote saldoton tuote")."? ".t("Onko tuotteella varastopaikka")."?</font><br><br>";
      $tee='';
    }
  }
  elseif ($ean_koodi != "" and $lista == "") {
    ///* Inventoidaan tuotenumeron perusteella *///
    $kutsu = " ".t("EAN-koodi")." $ean_koodi ";

    $query = "SELECT $select
              FROM tuote use index (tuoteno_index)
              JOIN tuotepaikat use index (tuote_index) USING (yhtio, tuoteno)
              LEFT JOIN inventointilistarivi ON (inventointilistarivi.yhtio = tuotepaikat.yhtio
                AND inventointilistarivi.tuotepaikkatunnus = tuotepaikat.tunnus
                AND inventointilistarivi.tila              = 'A')
              LEFT JOIN inventointilista ON (inventointilista.yhtio = inventointilistarivi.yhtio
                AND inventointilista.tunnus                = inventointilistarivi.otunnus)
              WHERE tuote.yhtio                            = '$kukarow[yhtio]'
              and tuote.eankoodi                           = '$ean_koodi'
              and tuote.ei_saldoa                          = ''
              ORDER BY sorttauskentta, tuoteno";
    $saldoresult = pupe_query($query);

    if (mysql_num_rows($saldoresult) == 0) {
      echo "<font class='error'>".t("EAN-koodi")." '$ean_koodi' ".t("ei löydy!")." ".t("Onko tuote saldoton tuote")."? ".t("Onko tuotteella varastopaikka")."?</font><br><br>";
      $tee='';
    }
    else {
      $tuoterow = mysql_fetch_assoc($saldoresult);
      $tuoteno = $tuoterow["tuoteno"];
      mysql_data_seek($saldoresult, 0);
    }
  }
  elseif ($lista != "") {
    ///* Inventoidaan listan perusteella *///
    $kutsu = " ".t("Inventointilista")." $lista ";

    if ($alku == '' or $alku < 0) {
      $alku = 0;
    }

    if ($laaja_inventointilista) {
      $loppu = "16";

      if ($rivimaara != "16" and $rivimaara != '') {
        $loppu = $rivimaara;
      }
    }
    else {
      $loppu = "17";

      if ($rivimaara != "17" and $rivimaara != '') {
        $loppu = $rivimaara;
      }
    }

    if ($jarjestys == 'tuoteno') {
      $order = " tuoteno, sorttauskentta ";
    }
    elseif ($jarjestys == 'osastotrytuoteno') {
      $order = " osasto, try, tuoteno, sorttauskentta ";
    }
    elseif ($jarjestys == 'nimityssorttaus') {
      $order = " nimitys, sorttauskentta ";
    }
    elseif ($jarjestys == 'rivinro') {
      $order = " inventointilista_rivinro ";
    }
    else {
      $order = " sorttauskentta, tuoteno ";
    }

    $limit = "LIMIT {$alku}, {$loppu}";

    $query = "SELECT {$select}
              FROM inventointilista
              JOIN inventointilistarivi ON (inventointilistarivi.yhtio = inventointilista.yhtio
                AND inventointilistarivi.otunnus = inventointilista.tunnus)
              JOIN tuotepaikat ON (tuotepaikat.yhtio = inventointilistarivi.yhtio
                AND tuotepaikat.tunnus           = inventointilistarivi.tuotepaikkatunnus)
              JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tuotepaikat.yhtio
                AND tuote.tuoteno                = tuotepaikat.tuoteno
                AND tuote.ei_saldoa              = '' {$joinon})
              WHERE inventointilista.yhtio       = '{$kukarow['yhtio']}'
              AND inventointilista.tunnus        = '{$lista}'
              ORDER BY {$order}";
    $saldoresult = pupe_query($query);

    if (mysql_num_rows($saldoresult) > $alku) {
      $query .= " {$limit}";

      $saldoresult = pupe_query($query);
    }

    if (mysql_num_rows($saldoresult) == 0) {
      echo "<font class='error'>".t("Listaa")." '$lista' ".t("ei löydy, tai se on jo inventoitu")."!</font><br><br>";
      $tee='';
    }
  }
  else {
    echo "<font class='error'>".t("VIRHE: Tarkista syötetyt tiedot")."!</font><br><br>";
    $tee='';
  }
}

if ($tee == 'INVENTOI') {

  echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
    <!--

    function toggleAll(toggleBox, toggleBoxBoxes) {

      var currForm  = toggleBox.form;
      var isChecked = toggleBox.checked;
      var nimi      = toggleBoxBoxes;

      for (var elementIdx=0; elementIdx < currForm.elements.length; elementIdx++) {
        if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].name == nimi) {
          currForm.elements[elementIdx].checked = isChecked;
        }
      }
    }

    function verify(){
      msg = '".t("Oletko varma? Olet inventoimassa koko listaa, haluatko jatkaa?")."';

      if (confirm(msg)) {
        return true;
      }
      else {
        skippaa_tama_submitti = true;
        return false;
      }
    }

    //-->
    </script>";

  if ($laaja_inventointilista) {
    $sel1rivi=$sel16rivi=$sel160rivi="";

    if ($rivimaara == '1') {
      $sel1rivi = "SELECTED";
    }
    elseif ($rivimaara == '16') {
      $sel16rivi = "SELECTED";
    }
    else {
      $sel160rivi = "SELECTED";
    }
  }
  else {
    $sel1rivi=$sel17rivi=$sel170rivi="";

    if ($rivimaara == '1') {
      $sel1rivi = "SELECTED";
    }
    elseif ($rivimaara == '17') {
      $sel17rivi = "SELECTED";
    }
    else {
      $sel170rivi = "SELECTED";
    }
  }

  $seljarj = array(
    'tuoteno' => '',
    'osastotrytuoteno' => '',
    'nimityssorttaus' => '',
    'rivinro' => '',
  );

  $seljarj = array($jarjestys => 'selected') + $seljarj;

  if ($lista != "") {
    echo "<form method='post'>";
    echo "<input type='hidden' name='toim' value='$toim'>";
    echo "<input type='hidden' name='lopetus' value='$lopetus'>";
    echo "<select name='rivimaara' onchange='submit()'>";

    if ($laaja_inventointilista) {
      echo "<option value='160' $sel160rivi>".t("Näytetään 160 riviä")."</option>";
      echo "<option value='16' $sel16rivi>".t("Näytetään 16 riviä")."</option>";
    }
    else {
      echo "<option value='170' $sel170rivi>".t("Näytetään 170 riviä")."</option>";
      echo "<option value='17' $sel17rivi>".t("Näytetään 17 riviä")."</option>";
    }

    echo "<option value='1' $sel1rivi>".t("Näytetään 1 rivi")."</option>";
    echo "</select>";
    echo "<select name='jarjestys' onchange='submit()'>";
    echo "<option value=''>".t("Tuotepaikkajärjestys")."</option>";
    echo "<option value='tuoteno' {$seljarj['tuoteno']}>".t("Tuotenumerojärjestys")."</option>";
    echo "<option value='nimityssorttaus' {$seljarj['nimityssorttaus']}>";
    echo t("Nimitysjärjestykseen");
    echo "</option>";
    echo "<option value='osastotrytuoteno' {$seljarj['osastotrytuoteno']}>";
    echo t("Osasto/Tuoteryhmä/Tuotenumerojärjestykseen");
    echo "</option>";

    if ($laaja_inventointilista) {
      echo "<option value='rivinro' {$seljarj['rivinro']}>", t("Rivinumerojärjestys"), "</option>";
    }

    echo "</select>";
    echo "<input type='hidden' name='tee' value='INVENTOI'>";
    echo "<input type='hidden' name='lista' value='$lista'>";
    echo "<input type='hidden' name='lista_aika' value='$lista_aika'>";
    echo "<input type='hidden' name='alku' value='$alku'>";
    echo "<input type='hidden' name='inventointipvm_pp' value='$inventointipvm_pp'>";
    echo "<input type='hidden' name='inventointipvm_kk' value='$inventointipvm_kk'>";
    echo "<input type='hidden' name='inventointipvm_vv' value='$inventointipvm_vv'>";
    echo "</form>";
  }

  if ($laaja_inventointilista) {
    echo "<br /><br />";
    echo "<font class='message'>";
    echo t("Inventointilista"), ": {$lista}";
    echo "</font>";
    echo "<br /><br />";

    $tuoterow = mysql_fetch_assoc($saldoresult);

    mysql_data_seek($saldoresult, 0);

    $query = "SELECT *
              FROM inventointilista
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$tuoterow['inventointilista']}'";
    $vapaa_teksti_res = pupe_query($query);
    $vapaa_teksti_row = mysql_fetch_assoc($vapaa_teksti_res);

    echo t("Vapaa teksti"), ": {$vapaa_teksti_row['vapaa_teksti']}<br /><br />";
  }

  echo "<form name='inve' method='post' autocomplete='off'>";
  echo "<input type='hidden' name='toim' value='$toim'>";
  echo "<input type='hidden' name='lopetus' value='$lopetus'>";
  echo "<input type='hidden' name='tee' value='VALMIS'>";
  echo "<input type='hidden' name='lista' value='$lista'>";
  echo "<input type='hidden' name='lista_aika' value='$lista_aika'>";
  echo "<input type='hidden' name='alku' value='$alku'>";
  echo "<input type='hidden' name='rivimaara' value='$rivimaara'>";
  echo "<input type='hidden' name='jarjestys' value='$jarjestys'>";
  echo "<input type='hidden' name='inventointipvm_pp' value='$inventointipvm_pp'>";
  echo "<input type='hidden' name='inventointipvm_kk' value='$inventointipvm_kk'>";
  echo "<input type='hidden' name='inventointipvm_vv' value='$inventointipvm_vv'>";

  echo "<table>";

  if (substr($toim, 0, 4) != "OSTO") {
    echo "<tr><td colspan='7' class='back'>".t("Syötä joko hyllyssä oleva määrä, tai lisättävä määrä + etuliitteellä, tai vähennettävä määrä - etuliitteellä")."</td></tr>";
  }

  echo "<tr>";

  if ($laaja_inventointilista) {
    echo "<th>#</th>";
  }

  echo "<th>".t("Tuoteno")."</th><th>".t("Nimitys")."</th><th>".t("Varastopaikka")."</th><th>".t("Inventointiaika")."</th><th>".t("Varastosaldo")."</th><th>".t("Ennpois")."/".t("Kerätty")."</th><th>".t("Hyllyssä")."</th>";

  if (substr($toim, 0, 4) == "OSTO") {
    echo "<th>".t("Tuloutettava määrä")."</th>";
    echo "<th>".t("Ostohinta")."</th>";
    echo "<th>".t("Toimittaja")."</th>";
  }
  else {
    echo "<th>".t("Laskettu hyllyssä")."</th>";
  }

  if ($laaja_inventointilista) {
    echo "<th>#</th>";
  }

  echo "</tr>";

  $rivilask = 0;

  while ($tuoterow = mysql_fetch_assoc($saldoresult)) {

    if ($oletusvarasto_chk > 0 and kuuluukovarastoon($tuoterow["hyllyalue"], $tuoterow["hyllynro"], $oletusvarasto_chk) == 0) continue;

    // Haetaan kerätty määrä
    $query = "SELECT ifnull(sum(if(keratty!='',tilausrivi.varattu,0)),0) keratty,  ifnull(sum(tilausrivi.varattu),0) ennpois
              FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
              WHERE yhtio    = '$kukarow[yhtio]'
              and tyyppi     in ('L','G','V')
              and tuoteno    = '$tuoterow[tuoteno]'
              and varattu    <> 0
              and laskutettu = ''
              and hyllyalue  = '$tuoterow[hyllyalue]'
              and hyllynro   = '$tuoterow[hyllynro]'
              and hyllyvali  = '$tuoterow[hyllyvali]'
              and hyllytaso  = '$tuoterow[hyllytaso]'";
    $hylresult = pupe_query($query);
    $hylrow = mysql_fetch_assoc($hylresult);

    $hyllyssa = sprintf('%.2f', $tuoterow['saldo']-$hylrow['keratty']);

    if ($laaja_inventointilista) {
      $hyllyssa = $tuoterow['inventointilista_hyllyssa'];
    }

    if ($tuoterow["sarjanumeroseuranta"] != "") {
      $query = "SELECT sarjanumeroseuranta.sarjanumero, sarjanumeroseuranta.tunnus, tilausrivi_myynti.otunnus myyntitunnus, tilausrivi_myynti.varattu myyntikpl,
                round(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl, 2) ostohinta, era_kpl, tilausrivi_osto.yksikko, tilausrivi_osto.laskutettuaika
                FROM sarjanumeroseuranta
                LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
                LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
                WHERE sarjanumeroseuranta.yhtio           = '$kukarow[yhtio]'
                and sarjanumeroseuranta.tuoteno           = '$tuoterow[tuoteno]'
                and sarjanumeroseuranta.myyntirivitunnus != -1
                and (  (sarjanumeroseuranta.hyllyalue    = '$tuoterow[hyllyalue]'
                     and sarjanumeroseuranta.hyllynro     = '$tuoterow[hyllynro]'
                     and sarjanumeroseuranta.hyllyvali    = '$tuoterow[hyllyvali]'
                     and sarjanumeroseuranta.hyllytaso    = '$tuoterow[hyllytaso]')
                   or ('$tuoterow[oletus]' != '' and
                    (  SELECT tunnus
                      FROM tuotepaikat tt
                      WHERE sarjanumeroseuranta.yhtio     = tt.yhtio and sarjanumeroseuranta.tuoteno = tt.tuoteno and sarjanumeroseuranta.hyllyalue = tt.hyllyalue
                      and sarjanumeroseuranta.hyllynro    = tt.hyllynro and sarjanumeroseuranta.hyllyvali = tt.hyllyvali and sarjanumeroseuranta.hyllytaso = tt.hyllytaso) is null))
                and ((tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00') and (tilausrivi_osto.laskutettuaika != '0000-00-00' or (tilausrivi_osto.laatija = 'Invent' and tilausrivi_osto.laskutettuaika = '0000-00-00')))
                and ('$tuoterow[sarjanumeroseuranta]' not in ('E','F','G') or era_kpl != 0)
                ORDER BY sarjanumero";
      $sarjares = pupe_query($query);
    }

    if (($tuoterow["inventointilista_aika"] === null and $lista == '') or ($tuoterow["inventointilista"] == $lista and $tuoterow["inventointilista_aika"] !== null and $tuoterow['inventointilista_tila'] != 'I')) {

      echo "<tr>";

      if ($laaja_inventointilista) {
        echo "<td>{$tuoterow['inventointilista_rivinro']}</td>";
      }

      echo "<td valign='top'>$tuoterow[tuoteno]</td><td valign='top' nowrap>".t_tuotteen_avainsanat($tuoterow, 'nimitys');

      if (in_array($tuoterow["sarjanumeroseuranta"], array("S", "T", "V"))) {
        if (mysql_num_rows($sarjares) > 0) {
          echo "<br><table width='100%'>";

          $sarjalaskk = 1;

          while ($sarjarow = mysql_fetch_assoc($sarjares)) {
            if ($sarjanumero[$tuoterow["tptunnus"]][$sarjarow["tunnus"]] != '') {
              $chk = "CHECKED";
            }
            else {
              $chk = "";
            }

            echo "<tr><td>$sarjalaskk. $sarjarow[sarjanumero]</td><td align='right'>";

            if ($tuoterow['sarjanumeroseuranta'] == 'T' or $tuoterow['sarjanumeroseuranta'] == 'V') {
              echo sprintf("%.02f", $tuoterow['kehahin']);
            }
            else {
              echo sprintf("%.02f", sarjanumeron_ostohinta("tunnus", $sarjarow["tunnus"]));
            }

            echo "</td><td>";
            echo "  <input type='hidden' name='sarjanumero_kaikki[$tuoterow[tptunnus]][$sarjarow[tunnus]]' value='$sarjarow[tunnus]'>
                <input type='hidden' name='sarjanumero_uudet[$tuoterow[tptunnus]][$sarjarow[tunnus]]' value = '$sarjarow[laskutettuaika]'>";

            if ($sarjarow['laskutettuaika'] == '0000-00-00') {
              echo "<input type='hidden' name='sarjanumero_valitut[$tuoterow[tptunnus]][$sarjarow[tunnus]]' value='$sarjarow[tunnus]'>";
              echo "<font class='message'>**", t("UUSI"), "**</font>";
            }
            else {
              echo "<input type='checkbox' name='sarjanumero_valitut[$tuoterow[tptunnus]][$sarjarow[tunnus]]' value='$sarjarow[tunnus]' $chk></td>";
            }

            echo "</td>";

            if ($sarjarow["myyntitunnus"] != 0) {
              echo "<td><font class='message'>", t("Tilauksella"), " $sarjarow[myyntitunnus]</font></td>";
            }
            echo "</tr>";

            $sarjalaskk++;
          }
          echo "</table>";
        }
        echo "<br><a href='tilauskasittely/sarjanumeroseuranta.php?tuoteno=".urlencode($tuoterow["tuoteno"])."&toiminto=luouusitulo&hyllyalue=$tuoterow[hyllyalue]&hyllynro=$tuoterow[hyllynro]&hyllyvali=$tuoterow[hyllyvali]&hyllytaso=$tuoterow[hyllytaso]&from=INVENTOINTI&lopetus=", $palvelin2, "inventoi.php////toim=$toim//tee=INVENTOI//tuoteno=$tuoteno//lista=$lista//lista_aika=$lista_aika//alku=$alku'>".t("Uusi sarjanumero")."</a>";
      }
      elseif (in_array($tuoterow["sarjanumeroseuranta"], array("E", "F", "G"))) {
        if (mysql_num_rows($sarjares) > 0) {
          echo "<br><table width='100%'>";

          $sarjalaskk = 1;

          // Katsotaan onko uusia eriä syötetty,
          // koska jos uusia eriä on syötetty tyhjennetään vanhojen erien kpl kentät
          $_onko_uusia = FALSE;

          while ($sarjarow = mysql_fetch_assoc($sarjares)) {
            if ($sarjarow['laskutettuaika'] == '0000-00-00') {
              $_onko_uusia = TRUE;
            }
          }

          mysql_data_seek($sarjares, 0);

          while ($sarjarow = mysql_fetch_assoc($sarjares)) {
            echo "<tr><td>$sarjalaskk. $sarjarow[sarjanumero]</td>
                <td>$sarjarow[era_kpl] ".t_avainsana("Y", "", "and avainsana.selite='$sarjarow[yksikko]'", "", "", "selite")."</td>
                <td>";

            if ($tuoterow["sarjanumeroseuranta"] == "E" or $tuoterow["sarjanumeroseuranta"] == "F") {
              echo sprintf('%.02f', $tuoterow['kehahin']);
            }
            else {
              echo sprintf('%.02f', $sarjarow['ostohinta']);
            }

            echo "</td>
                <td>
                <input type='hidden' name='eranumero_kaikki[$tuoterow[tptunnus]][$sarjarow[tunnus]]' value='$sarjarow[era_kpl]'>
                <input type='hidden' name='eranumero_uudet[$tuoterow[tptunnus]][$sarjarow[tunnus]]' value = '$sarjarow[laskutettuaika]'>";

            if ($sarjarow['laskutettuaika'] == '0000-00-00' or $sarjarow["myyntitunnus"] != 0) {
              if ($sarjarow['laskutettuaika'] == '0000-00-00') {
                echo "<input type='hidden' name='eranumero_valitut[$tuoterow[tptunnus]][$sarjarow[tunnus]]' value='$sarjarow[era_kpl]'>";
                echo "<font class='message'>**", t("UUSI"), "**</font>";
                echo " <a href='inventoi.php?tee=POISTAERANUMERO&tuoteno=$tuoteno&lista=$lista&lista_aika=$lista_aika&alku=$alku&toiminto=poistaeranumero&sarjatunnus=$sarjarow[tunnus]&toim=$toim&paivamaaran_kasisyotto=$paivamaaran_kasisyotto&inventointipvm_pp=$inventointipvm_pp&inventointipvm_kk=$inventointipvm_kk&inventointipvm_vv=$inventointipvm_vv'>".t("Poista")."</a>";
              }
              else {
                echo "<input type='hidden' name='eranumero_valitut[$tuoterow[tptunnus]][$sarjarow[tunnus]]' value='$sarjarow[era_kpl]'>";
                echo "<font class='message'>", t("Tilauksella"), " $sarjarow[myyntitunnus] $sarjarow[myyntikpl]</font>";
              }
            }
            else {
              if ($onko_uusia > 0 or $_onko_uusia) {
                $apu_era_kpl = "";
              }
              else {
                $apu_era_kpl = $sarjarow["era_kpl"];
              }
              echo "<input type='text' size='5' name='eranumero_valitut[$tuoterow[tptunnus]][$sarjarow[tunnus]]' value='$apu_era_kpl'>";
            }
            echo "</td>";
            echo "</tr>";

            $sarjalaskk++;
          }

          echo "</table>";
        }
        echo "<br><a href='tilauskasittely/sarjanumeroseuranta.php?tuoteno=".urlencode($tuoterow["tuoteno"])."&toiminto=luouusitulo&hyllyalue=$tuoterow[hyllyalue]&hyllynro=$tuoterow[hyllynro]&hyllyvali=$tuoterow[hyllyvali]&hyllytaso=$tuoterow[hyllytaso]&from=INVENTOINTI&lopetus=", $palvelin2, "inventoi.php////toim=$toim//tee=INVENTOI//tuoteno=$tuoteno//lista=$lista//lista_aika=$lista_aika//alku=$alku//paivamaaran_kasisyotto=$paivamaaran_kasisyotto//inventointipvm_pp=$inventointipvm_pp//inventointipvm_kk=$inventointipvm_kk//inventointipvm_vv=$inventointipvm_vv'>".t("Uusi eränumero")."</a>";
      }

      echo "</td><td valign='top'>";

      if ($tuoterow["hyllyalue"] == "!!M") {
        $asiakkaan_tunnus = (int) $tuoterow["hyllynro"].$tuoterow["hyllyvali"].$tuoterow["hyllytaso"];

        $query = "SELECT if(nimi = toim_nimi OR toim_nimi = '', nimi, concat(nimi, ' / ', toim_nimi)) asiakkaan_nimi
                  FROM asiakas
                  WHERE yhtio = '{$kukarow["yhtio"]}'
                  AND tunnus  = '$asiakkaan_tunnus'";
        $asiakasresult = pupe_query($query);
        $asiakasrow = mysql_fetch_assoc($asiakasresult);

        echo t("Myyntitili"), " ", $asiakasrow["asiakkaan_nimi"];
      }
      else {
        echo "$tuoterow[hyllyalue] $tuoterow[hyllynro] $tuoterow[hyllyvali] $tuoterow[hyllytaso]";
      }

      echo "</td>";

      echo "<td>".tv1dateconv($tuoterow['inventointiaika'], "P")."</td>";

      if ($tuoterow["sarjanumeroseuranta"] != "S") {
        echo "<td valign='top'>$tuoterow[saldo]</td><td valign='top'>$hylrow[ennpois]/$hylrow[keratty]</td><td valign='top'>".$hyllyssa."</td>";
      }
      else {
        echo "<td valign='top'>$tuoterow[saldo]</td><td valign='top'></td><td valign='top'>$tuoterow[saldo]</td>";
      }

      echo "<input type='hidden' name='hyllyssa[$tuoterow[tptunnus]]' value='$tuoterow[saldo]'>";
      echo "<input type='hidden' name='tuote[$tuoterow[tptunnus]]' value='$tuoterow[tuoteno]###$tuoterow[hyllyalue]###$tuoterow[hyllynro]###$tuoterow[hyllyvali]###$tuoterow[hyllytaso]'>";

      if ($laaja_inventointilista and $tuoterow['inventointilista_laskettu'] !== null) {
        $maara[$tuoterow['tptunnus']] = $tuoterow['inventointilista_laskettu'];
      }

      echo "<td valign='top'><input type='text' size='7' name='maara[$tuoterow[tptunnus]]' id='maara_$tuoterow[tptunnus]' value='".$maara[$tuoterow["tptunnus"]]."'></td>";

      if (substr($toim, 0, 4) == "OSTO") {

        $query = "SELECT tuotteen_toimittajat.*, if (tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys) sorttaus, toimi.nimi toimittajannimi
                  FROM tuotteen_toimittajat
                  JOIN toimi on (toimi.yhtio = tuotteen_toimittajat.yhtio and toimi.tunnus = tuotteen_toimittajat.liitostunnus)
                  WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
                  and tuotteen_toimittajat.tuoteno = '$tuoterow[tuoteno]'
                  ORDER BY sorttaus
                  LIMIT 1";
        $otres = pupe_query($query);
        $otrow = mysql_fetch_assoc($otres);

        if (mysql_num_rows($otres) == 0) {

          if (($toikrow = tarkista_oikeus("yllapito.php", "tuotteen_toimittajat%", "", "OK")) !== FALSE) {
            $_toimiecho = "<td valign='top'><a href='yllapito.php?toim=".$toikrow['alanimi']."&haku[1]=$tuoterow[tuoteno]&uusi=1&t[1]=$tuoterow[tuoteno]&lopetus=". $palvelin2. "inventoi.php////toim=$toim//tee=INVENTOI//tuoteno=$tuoteno'>".t("Perusta tuotteen toimittajat ennen tuloutusta")."</a><br>(".t("jotta voit antaa ostohinnan").")</td>";
          }
          else {
            $_toimiecho = "<td valign='top'>".t("Tuotteen toimittajaa ei löydy")."</td>";
          }

          $_hinta = "<td>0</td>";
          $_liitostunnus = "<input type='hidden' name='tuotteen_toimittajat[$tuoterow[tptunnus]]' value=''>";
        }
        else {
          $_hinta = "<td valign='top'><input type='text' size='7' name='ostohinnat[$tuoterow[tptunnus]]' value='".round($otrow['ostohinta'], $yhtiorow['hintapyoristys'])."'></td>";
          $_toimiecho = "<td valign='top'>$otrow[toimittajannimi]</td>";
          $_liitostunnus = "<input type='hidden' name='tuotteen_toimittajat[$tuoterow[tptunnus]]' value='$otrow[liitostunnus]'>";
        }

        echo $_hinta;
        echo $_toimiecho;
        echo $_liitostunnus;
      }

      if (in_array($tuoterow["sarjanumeroseuranta"], array("S", "T", "V"))) {
        echo "<td valign='top' class='back'>".t("Tuote on sarjanumeroseurannassa").". ".t("Inventoidaan varastosaldoa")."!</td>";
      }
      elseif (in_array($tuoterow["sarjanumeroseuranta"], array("E", "F", "G"))) {
        echo "<td valign='top' class='back'>".t("Tuote on eränumeroseurannassa").". ".t("Inventoidaan varastosaldoa")."!</td>";
      }

      if ($laaja_inventointilista) {
        echo "<td>{$tuoterow['inventointilista_rivinro']}</td>";
      }

      echo "</tr>";

      if ($rivilask == 0) {
        echo "<script LANGUAGE='JavaScript'>document.getElementById('maara_$tuoterow[tptunnus]').focus();</script>";
        $kentta = "";
        $rivilask++;
      }

    }
    elseif ($tuoterow['inventointilista_tila'] == 'I' and $tuoterow["inventointilista"] == $lista) {

      echo "<tr>";

      if ($laaja_inventointilista) {
        echo "<td>{$tuoterow['inventointilista_rivinro']}</td>";
      }

      echo "<td valign='top'>$tuoterow[tuoteno]</td><td valign='top' nowrap>".t_tuotteen_avainsanat($tuoterow, 'nimitys');

      if ($tuoterow["sarjanumeroseuranta"] == "S") {
        if (mysql_num_rows($sarjares) > 0) {
          echo "<br><table>";

          while ($sarjarow = mysql_fetch_assoc($sarjares)) {
            echo "<tr><td>$sarjarow[sarjanumero]</td><td>$sarjarow[ostohinta]</td></tr>";
          }

          echo "</table>";
        }
      }

      echo "</td><td valign='top'>$tuoterow[hyllyalue] $tuoterow[hyllynro] $tuoterow[hyllyvali] $tuoterow[hyllytaso]</td>$tdlisa";

      $query = "SELECT *
                FROM tapahtuma
                WHERE yhtio   = '$kukarow[yhtio]'
                and tuoteno   = '$tuoterow[tuoteno]'
                and laji      = 'Inventointi'
                and laadittu  >= '$lista_aika'
                and hyllyalue = '$tuoterow[hyllyalue]'
                and hyllynro  = '$tuoterow[hyllynro] '
                and hyllyvali = '$tuoterow[hyllyvali]'
                and hyllytaso = '$tuoterow[hyllytaso]'
                ORDER BY tunnus desc
                LIMIT 1";
      $tapresult = pupe_query($query);
      $taptrow = mysql_fetch_assoc($tapresult);

      $taptrow["selite"] = preg_replace("/".t("paikalla")." .*?\-.*?\-.*?\-.*? /", "", $taptrow["selite"]);

      echo "<td valign='top' class='green' colspan='5'>".t("Tuote on inventoitu!")." $taptrow[selite]";

      //Jos invauserohälytys on triggeröity
      $query = "SELECT abs(kpl) kpl
                FROM tapahtuma
                  WHERE yhtio = '$kukarow[yhtio]'
                and tuoteno   = '$tuoterow[tuoteno]'
                and laji      = 'Inventointi'
                and laadittu  >= '$lista_aika'
                and kpl       <> 0
                ORDER BY tunnus desc
                LIMIT 1";
      $tapresult = pupe_query($query);
      $taptrow = mysql_fetch_assoc($tapresult);

      if ($taptrow["kpl"] > 10) {
        echo "<br><font class='error'>".t("HUOM: Tuotteen saldo muuttui yli 10 kappaletta! Tarkista inventointi!")."</font>";
      }

      echo "</td>";

      if (in_array($tuoterow["sarjanumeroseuranta"], array("S", "T", "V"))) {
        echo "<td valign='top' class='back'>".t("Tuote on sarjanumeroseurannassa").". ".t("Inventoidaan varastosaldoa")."!</td>";
      }
      elseif (in_array($tuoterow["sarjanumeroseuranta"], array("E", "F", "G"))) {
        echo "<td valign='top' class='back'>".t("Tuote on eränumeroseurannassa").". ".t("Inventoidaan varastosaldoa")."!</td>";
      }

      if ($laaja_inventointilista) {
        echo "<td>{$tuoterow['inventointilista_rivinro']}</td>";
      }

      echo "</tr>";
    }
    else {
      echo "<tr>";

      if ($laaja_inventointilista) {
        echo "<td>{$tuoterow['inventointilista_rivinro']}</td>";
      }

      echo "<td valign='top'>$tuoterow[tuoteno]</td><td valign='top' nowrap>".t_tuotteen_avainsanat($tuoterow, 'nimitys')." </td><td valign='top'>$tuoterow[hyllyalue] $tuoterow[hyllynro] $tuoterow[hyllyvali] $tuoterow[hyllytaso]</td>$tdlisa";
      echo "<td colspan='5' valign='top'>".sprintf(t("Tätä tuotetta inventoidaan listalla %s. Inventointi estetty"), $tuoterow['inventointilista']).".</td>";

      if ($laaja_inventointilista) {
        echo "<td>{$tuoterow['inventointilista_rivinro']}</td>";
      }

      echo "</tr>";
    }
  }

  echo "</table><br>";
  echo "<table>";

  $lisaselite = !isset($lisaselite) ? '' : $lisaselite;
  $tresult = t_avainsana("INVEN_LAJI");

  if (mysql_num_rows($tresult) > 0) {
    echo "<tr><th>".t("Inventoinnin laji").":</th>";

    echo "<td><select id='inven_laji' name='inven_laji'>";

    while ($itrow = mysql_fetch_assoc($tresult)) {

      if (!isset($inven_laji) and !isset($sel)) {
        $sel = 'selected';
        $lisaselite = $itrow["selitetark_4"];
      }
      else {
        $sel = "";
      }

      if ($itrow["selite"] == $inven_laji) {
        $sel = 'selected';
        $lisaselite = $itrow["selitetark_4"];
      }

      echo "<option value='$itrow[selite]' $sel>$itrow[selite]</option>";
    }
    echo "</select></td></tr>";
  }

  if (substr($toim, 0, 4) != "OSTO") {
    echo "<tr><th>".t("Syötä inventointiselite:")."</th>";
    echo "<td><input type='text' size='50' id='lisaselite' name='lisaselite' value='$lisaselite'></td></tr>";
  }

  if ($paivamaaran_kasisyotto == "JOO") {
    echo "<tr><th>".t("Syötä inventointipäivämäärä (pp-kk-vvvv)").":</th>";
    echo "<td><input type='text' size='2' maxlength='2' name='inventointipvm_pp' value='$inventointipvm_pp'>";
    echo "<input type='text' size='2' maxlength='2' name='inventointipvm_kk' value='$inventointipvm_kk'>";
    echo "<input type='text' size='4' maxlength='4' name='inventointipvm_vv' value='$inventointipvm_vv'></td></tr>";
  }
  echo "</table><br><br>";
  if ($ean_koodi != '') {
    echo "<input type='hidden' name='enarifocus' value='1'>";
  }

  if ($laaja_inventointilista) {
    echo "<input type='submit' name='tallenna_laskettu_hyllyssa' value='", t("Tallenna laskettu hyllyssä"), "' />&nbsp;";
    echo "<input type='submit' name='prev' value='".t("Edellinen sivu")."'>&nbsp;";
    echo "<input type='submit' name='next' value='".t("Seuraava sivu")."'>&nbsp;";
    echo "<input type='submit' name='valmis' value='".t("Inventoi/Valmis")."' onClick='return verify();' >";
  }
  else {
    if ($lista != "" and mysql_num_rows($saldoresult) == $rivimaara) {
      echo "<input type='submit' name='next' value='".t("Inventoi/Seuraava sivu")."'>";
    }
    elseif (substr($toim, 0, 4) == "OSTO") {
      echo "<input type='submit' name='valmis' value='".t("Tulouta")."'>";
    }
    else {
      echo "<input type='submit' name='valmis' value='".t("Inventoi/Valmis")."'>";
    }
  }

  echo "</form>";

  if ($laaja_inventointilista) {
    echo "<br><br>";

    echo "<form name='inve' method='post' autocomplete='off'>";
    echo "<input type='hidden' name='toim' value='$toim'>";
    echo "<input type='hidden' name='lopetus' value='$lopetus'>";
    echo "<input type='hidden' name='tee' value='EROLISTA'>";
    echo "<input type='hidden' name='lista' value='$lista'>";
    echo "<input type='hidden' name='lista_aika' value='$lista_aika'>";
    echo "<input type='hidden' name='alku' value='$alku'>";
    echo "<input type='hidden' name='rivimaara' value='$rivimaara'>";
    echo "<input type='hidden' name='jarjestys' value='$jarjestys'>";
    echo "<input type='hidden' name='inventointipvm_pp' value='$inventointipvm_pp'>";
    echo "<input type='hidden' name='inventointipvm_kk' value='$inventointipvm_kk'>";
    echo "<input type='hidden' name='inventointipvm_vv' value='$inventointipvm_vv'>";

    $query = "SELECT *
              FROM kirjoittimet
              WHERE yhtio = '{$kukarow['yhtio']}'
              ORDER BY kirjoitin";
    $kires = pupe_query($query);

    echo "<select name='komento[Inventointierolista]'>";

    while ($kirow = mysql_fetch_assoc($kires)) {
      echo "<option value='{$kirow['komento']}'>{$kirow['kirjoitin']}</option>";
    }

    echo "</select>";

    echo "<input type='submit' name='erolista' value='", t("Erolista"), "'>";
    echo "</form>";
  }
}

if ($tee == 'MITATOI') {
  $query = "UPDATE inventointilistarivi SET
            tila        = 'I'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND otunnus = '{$lista}'";
  $result = pupe_query($query);

  echo t("Inventointilista")." $lista ".t("kuitattu pois")."!<br>";

  $lista = "";
  $tee   = "";
}

if ($tee == '') {

  $formi  = "inve";
  $kentta = "tuoteno";

  if (isset($tmp_tuoteno) and $tmp_tuoteno != '') {
    $query = "SELECT tuoteno
              FROM tuote use index (tuoteno_index)
              JOIN tuotepaikat use index (tuote_index) USING (yhtio, tuoteno)
              WHERE tuote.yhtio   = '$kukarow[yhtio]'
              and tuote.tuoteno   < '$tmp_tuoteno'
              and tuote.ei_saldoa = ''
              ORDER BY tuoteno desc
              LIMIT 1";
    $noperes = pupe_query($query);
    $noperow = mysql_fetch_assoc($noperes);

    echo "<table>";
    echo "<form method='post' autocomplete='off'>";
    echo "<input type='hidden' name='toim' value='$toim'>";
    echo "<input type='hidden' name='lopetus' value='$lopetus'>";
    echo "<input type='hidden' name='tee' value='INVENTOI'>";
    echo "<input type='hidden' name='seuraava_tuote' value='nope'>";
    echo "<input type='hidden' name='tuoteno' value='$noperow[tuoteno]'>";
    echo "<input type='hidden' name='inventointipvm_pp' value='$inventointipvm_pp'>";
    echo "<input type='hidden' name='inventointipvm_kk' value='$inventointipvm_kk'>";
    echo "<input type='hidden' name='inventointipvm_vv' value='$inventointipvm_vv'>";
    echo "<tr><td class='back'><input type='submit' value='".t("Edellinen tuote")."'></td>";
    echo "</form>";

    $query = "SELECT tuoteno
              FROM tuote use index (tuoteno_index)
              JOIN tuotepaikat use index (tuote_index) USING (yhtio, tuoteno)
              WHERE tuote.yhtio   = '$kukarow[yhtio]'
              and tuote.tuoteno   > '$tmp_tuoteno'
              and tuote.ei_saldoa = ''
              ORDER BY tuoteno
              LIMIT 1";
    $yesres = pupe_query($query);
    $yesrow = mysql_fetch_assoc($yesres);

    echo "<form method='post' autocomplete='off'>";
    echo "<input type='hidden' name='toim' value='$toim'>";
    echo "<input type='hidden' name='lopetus' value='$lopetus'>";
    echo "<input type='hidden' name='tee' value='INVENTOI'>";
    echo "<input type='hidden' name='seuraava_tuote' value='yes'>";
    echo "<input type='hidden' name='tuoteno' value='$yesrow[tuoteno]'>";
    echo "<input type='hidden' name='inventointipvm_pp' value='$inventointipvm_pp'>";
    echo "<input type='hidden' name='inventointipvm_kk' value='$inventointipvm_kk'>";
    echo "<input type='hidden' name='inventointipvm_vv' value='$inventointipvm_vv'>";
    echo "<td class='back'><input type='submit' value='".t("Seuraava tuote")."'></td></tr>";
    echo "</form>";
    echo "</table>";
  }

  echo "<form name='inve' method='post' autocomplete='off'>";
  echo "<input type='hidden' name='toim' value='$toim'>";
  echo "<input type='hidden' name='lopetus' value='$lopetus'>";
  echo "<input type='hidden' name='tee' value='INVENTOI'>";
  echo "<input type='hidden' name='inventointipvm_pp' value='$inventointipvm_pp'>";
  echo "<input type='hidden' name='inventointipvm_kk' value='$inventointipvm_kk'>";
  echo "<input type='hidden' name='inventointipvm_vv' value='$inventointipvm_vv'>";
  echo "<br><table>";
  echo "<tr><th>".t("Tuotenumero:")."</th><td>";

  echo livesearch_kentta("inve", "TUOTEHAKU", "tuoteno", 210);

  echo "</td></tr>";

  echo "<tr><th>".t("EAN-koodi:")."</th><td><input type='text' size='15' id='ean_koodi' name='ean_koodi'></td></tr>";

  if (substr($toim, 0, 4) != "OSTO") {
    echo "<tr><th>".t("Inventointilistan numero:")."</th><td><input type='text' size='6' name='lista'></td>";
    echo "<td class='back'><input type='submit' value='".t("Inventoi")."'></td>";
  }
  else {
    echo "<tr><td class='back'><input type='submit' value='".t("Tulouta")."'></td>";
  }

  echo "</tr>";
  echo "</table>";
  echo "</form>";
  echo "<br><br>";

  if (substr($toim, 0, 4) == "OSTO") {
    echo "<br><br><font class='head'>".t("Tulouta tiedostosta")."</font><hr>";
  }
  else {
    echo "<br><br><font class='head'>".t("Inventoi tiedostosta")."</font><hr>";
  }

  echo "<form method='post' enctype='multipart/form-data'>
      <input type='hidden' name='toim' value='$toim'>
      <input type='hidden' name='lopetus' value='$lopetus'>
      <input type='hidden' name='tee' value='FILE'>
      <input type='hidden' name='filusta' value='yep'>
      <input type='hidden' name='inventointipvm_pp' value='$inventointipvm_pp'>
      <input type='hidden' name='inventointipvm_kk' value='$inventointipvm_kk'>
      <input type='hidden' name='inventointipvm_vv' value='$inventointipvm_vv'>
      <table>
      <tr><th colspan='4'>".t("Tiedostomuoto").".</th></tr>
      <tr>";
  echo "  <td>".t("Tuoteno")." / ".t("EAN")."</td><td>".t("Hyllyalue-Hyllynro-Hyllyväli-Hyllytaso")."</td><td>".t("Määrä")."</td><td>".t("Laji")." / ".t("Selite")."</td>";
  echo "  </tr>";

  echo "  <tr><td class='back'><br></td></tr>";

  echo "  <tr><th>".t("Valitse tiedosto").":</th>
      <td colspan='3'><input name='userfile' type='file'></td></tr>
      <tr><th>".t("Valitse tyyppi").":</th>
      <td colspan='3'>
      <select name='tuoteno_ean'>
      <option value=''>".t("Tiedosto tuotenumerolla")."</option>
      <option value='EAN' $tuoteno_ean_sel>".t("Tiedosto EAN-koodilla")."</option>
      </select>
      </td>

      <td class='back'><input type='submit' value='".t("Inventoi")."'></td>
      </tr>
      </form>
      </table>";
  echo "<br><br>";

  //haetaan inventointilista numero tässä vaiheessa
  $query = "SELECT DISTINCT otunnus as inventointilista, luontiaika as inventointilista_aika
            FROM inventointilistarivi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tila    = 'A'
            GROUP BY 1
            ORDER BY 1";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    echo "<font class='message'>".t("Avoimet inventointilistat").":</font><br>";
    echo "<table>";
    echo "<tr>";
    echo "<th>".t("Numero")."</th>";
    echo "<th>".t("Luontiaika")."</th>";

    if ($laaja_inventointilista) {
      echo "<th>", t("Vapaa teksti"), "</th>";
    }

    echo "<th colspan='2'></th>";
    echo "</tr>";

    while ($lrow = mysql_fetch_assoc($result)) {

      echo "<tr>";
      echo "<td>$lrow[inventointilista]</td>";
      echo "<td>".tv1dateconv($lrow["inventointilista_aika"], "PITKA")."</td>";

      if ($laaja_inventointilista) {
        $query = "SELECT vapaa_teksti
                  FROM inventointilista
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  = '{$lrow['inventointilista']}'";
        $vapaa_teksti_res = pupe_query($query);
        $vapaa_teksti_row = mysql_fetch_assoc($vapaa_teksti_res);

        echo "<td>{$vapaa_teksti_row['vapaa_teksti']}</td>";
      }

      echo "<td>
            <form action='inventoi.php' method='post'>
            <input type='hidden' name='toim' value='$toim'>
            <input type='hidden' name='lopetus' value='$lopetus'>
            <input type='hidden' name='tee' value='INVENTOI'>
            <input type='hidden' name='lista' value='$lrow[inventointilista]'>
            <input type='hidden' name='lista_aika' value='$lrow[inventointilista_aika]'>
            <input type='hidden' name='inventointipvm_pp' value='$inventointipvm_pp'>
            <input type='hidden' name='inventointipvm_kk' value='$inventointipvm_kk'>
            <input type='hidden' name='inventointipvm_vv' value='$inventointipvm_vv'>
            <input type='submit' value='".t("Inventoi")."'>
            </form>
          </td>
          <td>
            <form action='inventoi.php' method='post'>
            <input type='hidden' name='toim' value='$toim'>
            <input type='hidden' name='lopetus' value='$lopetus'>
            <input type='hidden' name='tee' value='MITATOI'>
            <input type='hidden' name='lista' value='$lrow[inventointilista]'>
            <input type='hidden' name='lista_aika' value='$lrow[inventointilista_aika]'>
            <input type='hidden' name='inventointipvm_pp' value='$inventointipvm_pp'>
            <input type='hidden' name='inventointipvm_kk' value='$inventointipvm_kk'>
            <input type='hidden' name='inventointipvm_vv' value='$inventointipvm_vv'>
            <input type='submit' value='".t("Mitätöi lista")."'>
            </form>
          </td>";
      echo "</tr>";
    }
    echo "</table>";
  }
}

if ($mobiili != "YES") {
  require "inc/footer.inc";
}
