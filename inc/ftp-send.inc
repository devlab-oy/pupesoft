<?php

if (@include_once "inc/functions.inc");
elseif (@include_once "functions.inc");
else exit;

// Parametrit:
// $ftphost --> FTP-palvelin
// $ftpuser --> Käyttäjätunnus
// $ftppass --> Salasana
// $ftppath --> Kansio FTP-palvelimella jonne failit työnnetään
// $ftpfile --> Siirrettävä tiedosto
// $ftpport --> Custom portti, ei pakollinen
// $ftpfail --> Kansio jonne laitetaan failit jos siirto feilaa, ei pakollinen
// $ftpsucc --> Kansio jonne tallennetaan failit jos siirto onnistuu, ei pakollinen
// $ftptype --> FTP-palvelimen tyyppi esim "AS400", ei pakollinen
// $ftptmpr --> Käytetäänkö .TMP päätettä siirron aikana.

// Palautetaan:
// $palautus
// $syy

// Debug:
// echo "ftphost --> $ftphost<br>";
// echo "ftpuser --> $ftpuser<br>";
// echo "ftppass --> $ftppass<br>";
// echo "ftppath --> $ftppath<br>";
// echo "ftpfile --> $ftpfile<br>";
// echo "ftpport --> $ftpport<br>";
// echo "ftpfail --> $ftpfail<br>";
// echo "ftpsucc --> $ftpsucc<br>";

$syy             = "";
$palautus        = 0;
$tulos_ulos_ftp  = "";
$ncftplisa       = "";
$renameftpfile   = isset($renameftpfile) ? basename(trim($renameftpfile)) : "";
$palautus_pyynto = isset($palautus_pyynto) ? trim($palautus_pyynto) : "";
$filenimi        = basename($ftpfile);
$ftptmpr         = isset($ftptmpr) ? $ftptmpr : true;
$ftputf8         = isset($ftputf8) ? $ftputf8 : false;
$ftpbinary       = isset($ftpbinary) ? $ftpbinary : false;

// Lähetetään oletuksena ISO-8859-15 merkistömuodossa
if (PUPE_UNICODE and $ftputf8 === false) {
  $encoding = exec("file -b --mime-encoding '$ftpfile'");

  if (strtoupper($encoding) == 'UTF-8') {
    exec("recode --force UTF8..ISO-8859-15 '$ftpfile'");
  }
}

if ($ftphost == 'localhost' and $ftppath != '' and is_dir($ftppath) and is_writable($ftppath) and $ftpfile != '' and is_file($ftpfile) and is_writable($ftpfile)) {

  $ftppath = rtrim(realpath($ftppath), "/")."/";
  $file_from = realpath($ftpfile);
  $file_to = $ftppath.$filenimi;

  if (rename($file_from, $file_to)) {
    $tulos_ulos_ftp .= t("Tiedoston siirto onnistui")."<br>\n";
  }
  else {
    $tulos_ulos_ftp .= t("Tiedoston siirto epäonnistui")."<br>\n";
  }
}
elseif ($ftphost == '' or $ftpuser == '' or $ftppass == '' or $ftppath == '' or $ftpfile == '') {
  $tulos_ulos_ftp .= "<font class='error'>".t("Lähetykseen tarvittavia tietoja puuttuu")."! (host, user, pass, path, file)</font><br>\n";
  $palautus = 9;
  $syy = t("Lähetykseen tarvittavia tietoja puuttuu");
}
else {
  //lähetetään tiedosto

  // Onko käytössä custom portti
  if (isset($ftpport) and (int) $ftpport > 0) {
    $ftpport = (int) $ftpport;

    $ncftplisa = "-P $ftpport";

    $conn_id = ftp_connect($ftphost, $ftpport);
  }
  else {
    $conn_id = ftp_connect($ftphost);
  }

  // jos connectio ok, kokeillaan loginata
  if ($conn_id) {
    $login_result = ftp_login($conn_id, $ftpuser, $ftppass);
  }

  // jos viimeinen merkki pathissä ei ole kauttaviiva lisätään kauttaviiva...
  if (substr($ftppath, -1) != "/") {
    $ftppath .= "/";
  }

  if ($renameftpfile != "") {
    // ncftp:llä ei hanskata uudelleennimeämistä, joten laitetaan suoraan lopullisella nimellä...
    $cmd = "/usr/bin/ncftpput -u ".escapeshellarg($ftpuser)." -p ".escapeshellarg($ftppass)." $ncftplisa ".escapeshellarg($ftphost)." ".escapeshellarg($ftppath)." ".escapeshellarg($renameftpfile);
  }
  else {
    $cmd = "/usr/bin/ncftpput -u ".escapeshellarg($ftpuser)." -p ".escapeshellarg($ftppass)." $ncftplisa ".escapeshellarg($ftphost)." ".escapeshellarg($ftppath)." ".escapeshellarg($ftpfile);
  }

  // jos login ok kokeillaan uploadata
  if (isset($login_result) and $login_result) {

    // Tehdään NAMEFMT komento, jos kyseessä on iSeries/AS400 palvelin
    if (isset($ftptype) and $ftptype == "AS400") {
      $quote = ftp_site($conn_id, "NAMEFMT 1");
    }

    if ($ftptmpr) {
      // Käytetään aina .TMP päätettä kun siirto on kesken
      $ftpfile_tmp = $filenimi.".TMP";
    }
    else {
      // Ei käytetä .TMP päätettä
      $ftpfile_tmp = $filenimi;
    }

    $ftpmode = FTP_ASCII;

    if ($ftpbinary) {
      $ftpmode = FTP_BINARY;
    }

    // Kokeillaan passiivista siirtoa
    ftp_pasv($conn_id, TRUE);
    $upload = @ftp_put($conn_id, $ftppath.$ftpfile_tmp, realpath($ftpfile), $ftpmode);

    // Kokeillaan aktiivista siirtoa jos passiivi feilaa
    if ($upload === FALSE) {
      ftp_pasv($conn_id, FALSE);
      $upload = ftp_put($conn_id, $ftppath.$ftpfile_tmp, realpath($ftpfile), $ftpmode);
    }

    if ($ftptmpr) {
      // Otetaan .TMP pääte veks ku siirto on valmis
      if (isset($ftpport) and (int) $ftpport > 0 and $upload === TRUE) {
        // Jos $ftpport on käytössä niin käytetään näitä komentoja.
        // Näyttää siltä, että ftp_rename ei osaa hanskata renametusta kun käytetään custom porttia
        $rename = ftp_raw($conn_id, "RNFR {$ftppath}{$ftpfile_tmp}");
        $rename = ftp_raw($conn_id, "RNTO {$ftppath}{$filenimi}");

        if (stripos($rename[0], "command successful") === FALSE and stripos($rename[0], "file renamed") === FALSE) {
          $rename1 = FALSE;
        }
      }
      elseif ($upload === TRUE) {
        $rename1 = ftp_rename($conn_id, "{$ftppath}{$ftpfile_tmp}", "{$ftppath}{$filenimi}");

        // Jos rename epäonnistuu, koitetaan poistaa .TMP tiedosto
        if ($rename1 === FALSE) {
          $delete1 = ftp_delete($conn_id, "{$ftppath}{$ftpfile_tmp}");

          if ($delete1 === FALSE) {
            $raw = ftp_raw($conn_id, "quote dele {$ftppath}{$ftpfile_tmp}");
          }
        }
      }
    }

    // Pitääkö faili vielä nimetä kokonaan uudestaan (Finvoice iPost)
    if ($upload === TRUE and $renameftpfile != "") {
      $rename2 = ftp_rename($conn_id, "{$ftppath}{$filenimi}", "{$ftppath}{$renameftpfile}");

      // Jos rename epäonnistuu, koitetaan poistaa turha välitiedosto
      if ($rename2 === FALSE) {
        $rename2 = ftp_delete($conn_id, "{$ftppath}{$filenimi}");
        if ($rename2 === FALSE) {
          $raw = ftp_raw($conn_id, "quote dele {$ftppath}{$filenimi}");
        }
        $rename2 = FALSE;
      }
    }

    // Kokeillaan ncftp-ohjelmalla, uudelleenimeäminen ei onnaa joten laitetaan suoraan lopullisella nimellä
    if ($upload === FALSE) {
      exec($cmd, $skit, $upload_ncftp);

      if ($upload_ncftp == 0) {
        $upload = TRUE;
      }
    }
  }

  if ($conn_id) {
    ftp_close($conn_id);
  }

  // mikä feilas?
  if (isset($conn_id) and $conn_id === FALSE) {
    $palautus = 1;
  }
  if (isset($login_result) and $login_result === FALSE) {
    $palautus = 2;
  }
  if (isset($upload) and $upload === FALSE) {
    $palautus = 3;
  }
  if (isset($rename1) and $rename1 === FALSE) {
    $palautus = 4;
  }
  if (isset($rename2) and $rename2 === FALSE) {
    $palautus = 5;
  }
  if (isset($delete1) and $delete1 === FALSE) {
    $palautus = 6;
  }

  // Siirto onnistui
  if ($palautus == 0 and isset($ftpsucc) and $ftpsucc != "") {
    // Siirretään faili talteen jos siirto onnasi ja ok failit halutaan tallentaa
    rename($ftpfile, $ftpsucc."/".$filenimi);
  }
  elseif ($palautus != 0 and isset($ftpfail) and $ftpfail != "") {
    // Siirretään faili outboxiin odottamaan uutta yritystä jos siirto feilasi
    rename($ftpfile, $ftpfail."/".$filenimi);
  }

  // jos siirto epäonnistuu
  if ($palautus != 0) {
    switch ($palautus) {
    case  1:
      $syy = "Could not connect to remote host. ($ftphost)";
      break;
    case  2:
      $syy = "Could not login to remote host ($ftpuser, $ftppass)";
      break;
    case  3:
      $syy = "Transfer failed ($ftppath, ".realpath($ftpfile).")";
      break;
    case  4:
      $syy = "Rename failed ($ftppath, {$ftppath}{$ftpfile_tmp} --> {$ftppath}{$filenimi})";
      break;
    case  5:
      $syy = "Rename failed ($ftppath, {$ftppath}{$filenimi} --> {$ftppath}{$renameftpfile})";
      break;
    case  6:
      $syy = "Delete failed ({$ftppath}{$ftpfile_tmp})";
      break;
    default:
      $syy = t("Tuntematon errorkoodi")." ($palautus)!!";
    }

    pupesoft_log('ftp_send', "Tiedoston '{$ftpfile}' lähetys palvelimelle '{$ftphost}' epäonnistui: {$syy}");

    // jos faildirri ON setattu niin ei ekoteta eikä meilata mitään
    if (!empty($ftpfail)) {
      $rivi  = "{$_SERVER['SCRIPT_NAME']}\n";
      $rivi .= "\n";
      $rivi .= t("Tiedoston")." '$ftpfile' ".t("lähetys epäonnistui")."!\n";
      $rivi .= "\n";
      $rivi .= "$cmd\n";
      $rivi .= "\n";
      $rivi .= "$syy\n";

      $boob = mail($yhtiorow['alert_email'], mb_encode_mimeheader(t("Tiedostonsiirto epäonnistui")."!", "ISO-8859-1", "Q"), $rivi, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");

      if ($boob === FALSE) {
        $tulos_ulos_ftp .= "<font class='error'>\n";
        $tulos_ulos_ftp .= t("Tiedostonsiirto epäonnistui! Ja meilin lähettäminen")." $yhtiorow[alert_email] ".t("epäonnistui myös!")."\n";
        $tulos_ulos_ftp .= "</font><br>\n";

        $tulos_ulos_ftp .= "<br><pre>$rivi</pre><br>\n";
      }
      else {
        $tulos_ulos_ftp .= "<font class='error'>FAILED! (".t("lähetettiin sähköposti")." $yhtiorow[alert_email])</font><br>\n";
      }
    }
  }
  else {
    pupesoft_log('ftp_send', "Lähetettiin tiedosto '{$ftpfile}' palvelimelle '{$ftphost}'");

    if ($palautus_pyynto != "") {
      $rivi  = "{$_SERVER['SCRIPT_NAME']}\n";
      $rivi .= "\n";
      $rivi .= t("Tiedoston")." '$ftpfile' ".t("lähetys onnistui")."!\n";
      $rivi .= "\n";
      $rivi .= "$ftpuser @ $ftphost : $ftppath\n";
      $rivi .= "\n";
      $rivi .= "\n";

      $silari = mail($yhtiorow['alert_email'], mb_encode_mimeheader(t("Tiedostonsiirto onnistui")."!", "ISO-8859-1", "Q"), $rivi, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
    }
  }
}

//echotaan rudulle jos kyseessä ei ole batch-ajo
if (!isset($tulos_ulos) or $tulos_ulos == "") {
  echo $tulos_ulos_ftp;
}
elseif ($tulos_ulos == "edi") {
  $edi_ulos .= strip_tags($tulos_ulos_ftp);
}
