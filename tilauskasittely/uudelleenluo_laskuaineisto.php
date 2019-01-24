<?php

if (isset($_REQUEST["tee"])) {
  if ($_REQUEST["tee"] == 'lataa_tiedosto') {
    $lataa_tiedosto = 1;
  }

  if (!empty($_REQUEST["kaunisnimi"])) {
    $_REQUEST["kaunisnimi"] = str_replace("/", "", $_REQUEST["kaunisnimi"]);
  }
}

if (isset($_REQUEST["tee"]) and $_REQUEST["tee"] == "NAYTATILAUS") {
  $no_head = "yes";

  if (!empty($_REQUEST["nayta_ja_tallenna"])) {
    $compression = FALSE;
  }
}

require "../inc/parametrit.inc";

// Timeout in 5h
ini_set("mysql.connect_timeout", 18000);
ini_set("max_execution_time", 18000);

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("$pupe_root_polku/dataout/".basename($filenimi));
  exit;
}

// siirretaan lasku toiseen pupesoftiin
if (isset($tee) and $tee == "kasittele_pupesoft_finvoice" and !empty($sisainenfoinvoice_ftphost)) {
  $ftphost = $sisainenfoinvoice_ftphost;
  $ftpuser = $sisainenfoinvoice_ftpuser;
  $ftppass = $sisainenfoinvoice_ftppass;
  $ftppath = $sisainenfoinvoice_ftppath;
  $ftpfile = "{$pupe_root_polku}/dataout/" . basename($filenimi);
  $ftpfail = "{$pupe_root_polku}/dataout/sisainenfinvoice_error/";

  require 'inc/ftp-send.inc';

  $tee = '';
}

if (!isset($tee) or $tee != "NAYTATILAUS") echo "<font class='head'>".t("Luo laskutusaineisto")."</font><hr>\n";

if (isset($tee) and $tee == "pupevoice_siirto") {
  $ftphost = (isset($verkkohost_lah) and trim($verkkohost_lah) != '') ? $verkkohost_lah : "ftp.verkkolasku.net";
  $ftpuser = $yhtiorow['verkkotunnus_lah'];
  $ftppass = $yhtiorow['verkkosala_lah'];
  $ftppath = (isset($verkkopath_lah) and trim($verkkopath_lah) != '') ? $verkkopath_lah : "out/einvoice/data/";
  $ftpfile = "{$pupe_root_polku}/dataout/".basename($filenimi);
  $ftpfail = "{$pupe_root_polku}/dataout/pupevoice_error/";

  $tulos_ulos = "";

  require "inc/ftp-send.inc";
}

if (isset($tee) and $tee == "trustpoint_siirto") {
  // Siirretään lähetysjonoon
  rename("{$pupe_root_polku}/dataout/".basename($filenimi), "{$pupe_root_polku}/dataout/trustpoint_error/".basename($filenimi));
  echo "Lasku siirretty lähetysjonoon";
}

if (isset($tee) and $tee == "ppg_siirto") {
  // Splitataan file ja lähetetään YKSI lasku kerrallaan
  $ppg_laskuarray = explode("<SOAP-ENV:Envelope", file_get_contents("{$pupe_root_polku}/dataout/".basename($filenimi)));
  $ppg_laskumaara = count($ppg_laskuarray);

  if ($ppg_laskumaara > 0) {
    require_once "tilauskasittely/tulosta_lasku.inc";

    for ($a = 1; $a < $ppg_laskumaara; $a++) {
      preg_match("/\<InvoiceNumber\>(.*?)\<\/InvoiceNumber\>/i", $ppg_laskuarray[$a], $invoice_number);

      $status = ppg_queue($invoice_number[1], "<SOAP-ENV:Envelope".$ppg_laskuarray[$a], $kieli);

      echo "PPG-lasku $invoice_number[1]: $status<br>\n";
    }
  }
}

if (isset($tee) and $tee == "talenom_siirto") {
  // Splitataan file ja lähetetään YKSI lasku kerrallaan
  $talenom_laskuarray = explode("<SOAP-ENV:Envelope", file_get_contents("{$pupe_root_polku}/dataout/".basename($filenimi)));
  $talenom_laskumaara = count($talenom_laskuarray);

  if ($talenom_laskumaara > 0) {
    require_once "tilauskasittely/tulosta_lasku.inc";

    for ($a = 1; $a < $talenom_laskumaara; $a++) {
      preg_match("/\<InvoiceNumber\>(.*?)\<\/InvoiceNumber\>/i", $talenom_laskuarray[$a], $invoice_number);

      $status = talenom_queue($invoice_number[1], "<SOAP-ENV:Envelope".$talenom_laskuarray[$a], $kieli);

      echo "Talenom-lasku $invoice_number[1]: $status<br>\n";
    }
  }
}

if (isset($tee) and $tee == "arvato_siirto") {
  // Splitataan file ja lähetetään YKSI lasku kerrallaan
  $arvato_laskuarray = explode("<SOAP-ENV:Envelope", file_get_contents("{$pupe_root_polku}/dataout/".basename($filenimi)));
  $arvato_laskumaara = count($arvato_laskuarray);

  if ($arvato_laskumaara > 0) {
    require_once "tilauskasittely/tulosta_lasku.inc";

    for ($a = 1; $a < $arvato_laskumaara; $a++) {
      preg_match("/\<InvoiceNumber\>(.*?)\<\/InvoiceNumber\>/i", $arvato_laskuarray[$a], $invoice_number);

      $status = arvato_queue($invoice_number[1], "<SOAP-ENV:Envelope".$arvato_laskuarray[$a], $kieli);

      echo "Arvato-lasku $invoice_number[1]: $status<br>\n";
    }
  }
}

if (isset($tee) and $tee == "fitek_siirto") {
  // Splitataan file ja lähetetään YKSI lasku kerrallaan
  $fitek_laskuarray = explode("<?xml version=", file_get_contents("{$pupe_root_polku}/dataout/".basename($filenimi)));
  $fitek_laskumaara = count($fitek_laskuarray);

  if ($fitek_laskumaara > 0) {
    require_once "tilauskasittely/tulosta_lasku.inc";

    for ($a = 1; $a < $fitek_laskumaara; $a++) {
      preg_match("/\<InvoiceNumber\>(.*?)\<\/InvoiceNumber\>/i", $fitek_laskuarray[$a], $invoice_number);

      $status = fitek_queue($invoice_number[1], "<?xml version=".$fitek_laskuarray[$a], $kieli);

      echo "Fitek-lasku $invoice_number[1]: $status<br>\n";
    }
  }
}
if (finvoice_pankki() && isset($tee) && $tee == 'sepa_siirto') {
  $file = "{$pupe_root_polku}/dataout/".basename($filenimi);

  rename($file, "{$pupe_root_polku}/dataout/" . finvoice_pankki() . "_error/" . basename($filenimi));

  echo "SEPA-lasku siirretty lähetysjonoon";
}

if (isset($tee) and $tee == "edi_siirto") {
  $ftphost = $edi_ftphost;
  $ftpuser = $edi_ftpuser;
  $ftppass = $edi_ftppass;
  $ftppath = $edi_ftppath;
  $ftpfile = "{$pupe_root_polku}/dataout/".basename($filenimi);
  $ftpfail = "{$pupe_root_polku}/dataout/elmaedi_error/";

  $tulos_ulos = "";

  require "inc/ftp-send.inc";
}

if (isset($tee) and $tee == "apix_siirto") {

  // Splitataan file ja lähetetään laskut sopivissa osissa
  $apix_laskuarray = explode("<SOAP-ENV:Envelope", file_get_contents("/tmp/".$filenimi));
  $apix_laskumaara = count($apix_laskuarray);

  if ($apix_laskumaara > 0) {
    require_once "tilauskasittely/tulosta_lasku.inc";

    for ($a = 1; $a < $apix_laskumaara; $a++) {
      preg_match("/\<InvoiceNumber\>(.*?)\<\/InvoiceNumber\>/i", $apix_laskuarray[$a], $invoice_number);

      $apix_finvoice = "<SOAP-ENV:Envelope".$apix_laskuarray[$a];

      $tilausnumerot = hae_tilausnumero($invoice_number[1]);
      $liitteet      = hae_liitteet_verkkolaskuun($yhtiorow["verkkolasku_lah"], $tilausnumerot);

      // Laitetaan lasku lähetysjonoon
      echo apix_queue($apix_finvoice, $invoice_number[1], $kieli, $liitteet);
    }
  }
}

if (isset($tee) and $tee == 'maventa_siirto') {
  // Splitataan file ja lähetetään YKSI lasku kerrallaan
  $maventa_laskuarray = explode("<SOAP-ENV:Envelope", file_get_contents("{$pupe_root_polku}/dataout/".basename($filenimi)));
  $maventa_laskumaara = count($maventa_laskuarray);

  if ($maventa_laskumaara > 0) {
    require_once "tilauskasittely/tulosta_lasku.inc";

    for ($a = 1; $a < $maventa_laskumaara; $a++) {
      preg_match("/\<InvoiceNumber\>(.*?)\<\/InvoiceNumber\>/i", $maventa_laskuarray[$a], $invoice_number);

      $status = maventa_invoice_put_file(NULL, NULL, $invoice_number[1], "<SOAP-ENV:Envelope".$maventa_laskuarray[$a], "");

      echo "Maventa-lasku $invoice_number[1]: $status<br>\n";
    }
  }
}

if (isset($tee) and $tee == 'ipost_siirto') {

  // siirretaan laskutiedosto operaattorille
  $ftphost     = "ftp.itella.net";
  $ftpuser     = $yhtiorow['verkkotunnus_lah'];
  $ftppass     = $yhtiorow['verkkosala_lah'];
  $ftppath     = "out/finvoice/data/";
  $ftpfile     = "{$pupe_root_polku}/dataout/".basename($filenimi);
  $renameftpfile   = str_replace("TRANSFER_IPOST", "DELIVERED_IPOST", basename($filenimi));
  $ftpfail     = "{$pupe_root_polku}/dataout/ipost_error/";

  $tulos_ulos     = "";

  require "inc/ftp-send.inc";
}

if (isset($tee) and ($tee == "GENEROI" or $tee == "NAYTATILAUS") and $laskunumerot != '') {

  $tulostettavat_apix  = array();
  $lask = 0;

  if (($tee == "NAYTATILAUS" and empty($nayta_ja_tallenna)) or ($yhtiorow["verkkolasku_lah"] == "fitek")) {
    $nosoap   = "NOSOAP";
  }
  else {
    $nosoap   = "";
  }

  if (!function_exists("vlas_dateconv")) {
    function vlas_dateconv($date) {
      //kääntää mysqln vvvv-kk-mm muodon muotoon vvvvkkmm
      return substr($date, 0, 4).substr($date, 5, 2).substr($date, 8, 2);
    }
  }

  //tehdään viitteestä SPY standardia eli 20 merkkiä etunollilla
  if (!function_exists("spyconv")) {
    function spyconv($spy) {
      return $spy = sprintf("%020.020s", $spy);
    }
  }

  //pilkut pisteiksi
  if (!function_exists("pp")) {
    function pp($muuttuja, $round="", $rmax="", $rmin="") {
      if (strlen($round)>0) {
        if (strlen($rmax)>0 and $rmax<$round) {
          $round = $rmax;
        }
        if (strlen($rmin)>0 and $rmin>$round) {
          $round = $rmin;
        }

        return $muuttuja = number_format($muuttuja, $round, ",", "");
      }
      else {
        return $muuttuja = str_replace(".", ",", $muuttuja);
      }
    }
  }

  //Tiedostojen polut ja nimet
  //keksitään uudelle failille joku varmasti uniikki nimi:
  $nimixml = "$pupe_root_polku/dataout/laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(), true)).".xml";

  //  Itellan iPost vaatii siirtoon vähän oman nimen..
  if ($yhtiorow["verkkolasku_lah"] == "iPost") {
    $nimiipost = "-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(), true))."_finvoice.xml";
    $nimifinvoice = "$pupe_root_polku/dataout/TRANSFER_IPOST".$nimiipost;
    $nimifinvoice_delivered = "DELIVERED_IPOST".$nimiipost;
  }
  elseif ($yhtiorow["verkkolasku_lah"] == "apix") {
    $nimifinvoice = "/tmp/laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(), true))."_finvoice.xml";
  }
  else {
    $nimifinvoice = "$pupe_root_polku/dataout/laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(), true))."_finvoice.xml";
  }

  $nimisisainenfinvoice = "$pupe_root_polku/dataout/laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(), true))."_sisainenfinvoice.xml";

  $nimiedi = "$pupe_root_polku/dataout/laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(), true)).".edi";

  //Pupevoice xml-dataa
  if (!$tootxml = fopen($nimixml, "w")) die("Filen $nimixml luonti epäonnistui!");

  //Finvoice xml-dataa
  if (!$tootfinvoice = fopen($nimifinvoice, "w")) die("Filen $nimifinvoice luonti epäonnistui!");

  //Elma-EDI-inhouse dataa (EIH-1.4.0)
  if (!$tootedi = fopen($nimiedi, "w")) die("Filen $nimiedi luonti epäonnistui!");

  //Sisäinenfinvoice xml-dataa
  if (!$tootsisainenfinvoice = fopen($nimisisainenfinvoice, "w")) die("Filen $nimisisainenfinvoice luonti epäonnistui!");

  //Haetaan tarvittavat funktiot aineistojen tekoa varten
  require "verkkolasku_elmaedi.inc";

  if ($yhtiorow["finvoice_versio"] == "2") {
    require "verkkolasku_finvoice_201.inc";
  }
  else {
    require "verkkolasku_finvoice.inc";
  }

  require "verkkolasku_pupevoice.inc";

  if (!isset($kieli)) {
    $kieli = "";
  }

  //Timestamppi EDI-failiin alkuu ja loppuun
  $timestamppi = gmdate("YmdHis");

  $laskunumerot = trim($laskunumerot);

  //Haetaan laskut jotka laitetaan aineistoon
  $query = "SELECT *
            from lasku
            where yhtio  = '$kukarow[yhtio]'
            and tila     = 'U'
            and alatila  = 'X'
            and laskunro in ($laskunumerot)";
  $res   = pupe_query($query);

  $lkm = count(explode(',', $laskunumerot));

  if (!isset($tee) or $tee != "NAYTATILAUS") {
    echo "<br><font class='message'>".t("Syötit")." $lkm ".t("laskua").".</font><br>";
    echo "<font class='message'>".t("Aineistoon lisätään")." ".mysql_num_rows($res)." ".t("laskua").".</font><br><br>";
  }

  while ($lasrow = mysql_fetch_assoc($res)) {
    // Haetaan maksuehdon tiedot
    $query  = "SELECT pankkiyhteystiedot.*, maksuehto.*
               FROM maksuehto
               LEFT JOIN pankkiyhteystiedot on (pankkiyhteystiedot.yhtio=maksuehto.yhtio and pankkiyhteystiedot.tunnus=maksuehto.pankkiyhteystiedot)
               WHERE maksuehto.yhtio='$kukarow[yhtio]' and maksuehto.tunnus='$lasrow[maksuehto]'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 0) {
      $masrow = array();

      if ($lasrow["erpcm"] == "0000-00-00") {
        echo "<font class='message'><br>\n".t("Maksuehtoa")." $lasrow[maksuehto] ".t("ei löydy!")." Tunnus $lasrow[tunnus] ".t("Laskunumero")." $lasrow[laskunro] ".t("epäonnistui pahasti")."!</font><br>\n<br>\n";
      }
    }
    else {
      $masrow = mysql_fetch_assoc($result);
    }

    //Haetaan factoringsopimuksen tiedot
    if (isset($masrow["factoring_id"])) {
      $query = "SELECT *
                FROM factoring
                WHERE yhtio  = '$kukarow[yhtio]'
                and tunnus   = '$masrow[factoring_id]'
                and valkoodi = '$lasrow[valkoodi]'";
      $fres = pupe_query($query);
      $frow = mysql_fetch_assoc($fres);
    }
    else {
      unset($frow);
    }

    $pankkitiedot = array();

    //Laitetaan pankkiyhteystiedot kuntoon
    if (isset($masrow["factoring_id"])) {
      $pankkitiedot["pankkinimi1"]  =  $frow["pankkinimi1"];
      $pankkitiedot["pankkitili1"]  =  $frow["pankkitili1"];
      $pankkitiedot["pankkiiban1"]  =  $frow["pankkiiban1"];
      $pankkitiedot["pankkiswift1"] =  $frow["pankkiswift1"];
      $pankkitiedot["pankkinimi2"]  =  $frow["pankkinimi2"];
      $pankkitiedot["pankkitili2"]  =  $frow["pankkitili2"];
      $pankkitiedot["pankkiiban2"]  =  $frow["pankkiiban2"];
      $pankkitiedot["pankkiswift2"] = $frow["pankkiswift2"];
      $pankkitiedot["pankkinimi3"]  =  "";
      $pankkitiedot["pankkitili3"]  =  "";
      $pankkitiedot["pankkiiban3"]  =  "";
      $pankkitiedot["pankkiswift3"] =  "";

    }
    elseif ($masrow["pankkinimi1"] != "") {
      $pankkitiedot["pankkinimi1"]  =  $masrow["pankkinimi1"];
      $pankkitiedot["pankkitili1"]  =  $masrow["pankkitili1"];
      $pankkitiedot["pankkiiban1"]  =  $masrow["pankkiiban1"];
      $pankkitiedot["pankkiswift1"] =  $masrow["pankkiswift1"];
      $pankkitiedot["pankkinimi2"]  =  $masrow["pankkinimi2"];
      $pankkitiedot["pankkitili2"]  =  $masrow["pankkitili2"];
      $pankkitiedot["pankkiiban2"]  =  $masrow["pankkiiban2"];
      $pankkitiedot["pankkiswift2"] = $masrow["pankkiswift2"];
      $pankkitiedot["pankkinimi3"]  =  $masrow["pankkinimi3"];
      $pankkitiedot["pankkitili3"]  =  $masrow["pankkitili3"];
      $pankkitiedot["pankkiiban3"]  =  $masrow["pankkiiban3"];
      $pankkitiedot["pankkiswift3"] =  $masrow["pankkiswift3"];
    }
    else {
      $pankkitiedot["pankkinimi1"]  =  $yhtiorow["pankkinimi1"];
      $pankkitiedot["pankkitili1"]  =  $yhtiorow["pankkitili1"];
      $pankkitiedot["pankkiiban1"]  =  $yhtiorow["pankkiiban1"];
      $pankkitiedot["pankkiswift1"] =  $yhtiorow["pankkiswift1"];
      $pankkitiedot["pankkinimi2"]  =  $yhtiorow["pankkinimi2"];
      $pankkitiedot["pankkitili2"]  =  $yhtiorow["pankkitili2"];
      $pankkitiedot["pankkiiban2"]  =  $yhtiorow["pankkiiban2"];
      $pankkitiedot["pankkiswift2"] = $yhtiorow["pankkiswift2"];
      $pankkitiedot["pankkinimi3"]  =  $yhtiorow["pankkinimi3"];
      $pankkitiedot["pankkitili3"]  =  $yhtiorow["pankkitili3"];
      $pankkitiedot["pankkiiban3"]  =  $yhtiorow["pankkiiban3"];
      $pankkitiedot["pankkiswift3"] =  $yhtiorow["pankkiswift3"];
    }

    $asiakas_apu_query = "SELECT *
                          FROM asiakas
                          WHERE yhtio = '$kukarow[yhtio]'
                          AND tunnus  = '$lasrow[liitostunnus]'";
    $asiakas_apu_res = pupe_query($asiakas_apu_query);

    if (mysql_num_rows($asiakas_apu_res) == 1) {
      $asiakas_apu_row = mysql_fetch_assoc($asiakas_apu_res);
    }
    else {
      $asiakas_apu_row = array();
    }

    if (strtoupper(trim($asiakas_apu_row["kieli"])) == "SE") {
      $laskun_kieli = "SE";
    }
    elseif (strtoupper(trim($asiakas_apu_row["kieli"])) == "EE") {
      $laskun_kieli = "EE";
    }
    elseif (strtoupper(trim($asiakas_apu_row["kieli"])) == "FI") {
      $laskun_kieli = "FI";
    }
    else {
      $laskun_kieli = trim(strtoupper($yhtiorow["kieli"]));
    }

    if ($kieli != "") {
      $laskun_kieli = trim(strtoupper($kieli));
    }

    // tässä pohditaan laitetaanko verkkolaskuputkeen
    if (($tee == 'NAYTATILAUS' and !empty($nayta_ja_tallenna)) or verkkolaskuputkeen($lasrow, $masrow)) {

      // Nyt meillä on:
      // $lasrow array on U-laskun tiedot
      // $yhtiorow array on yhtion tiedot
      // $masrow array maksuehdon tiedot

      // Etsitään myyjän nimi
      $mquery  = "SELECT nimi, puhno, eposti
                  FROM kuka
                  WHERE tunnus = '$lasrow[myyja]'
                  and yhtio    = '$kukarow[yhtio]'";
      $myyresult = pupe_query($mquery);
      $myyrow = mysql_fetch_assoc($myyresult);

      $lasrow['chn_orig'] = $lasrow['chn'];

      //HUOM: Tässä kaikki sallitut verkkopuolen chn:ät
      if (!in_array($lasrow['chn'], array("100", "010", "001", "020", "111", "112"))) {
        //Paperi by default
        $lasrow['chn'] = "100";
      }

      if ($lasrow['chn'] == "020") {
        $lasrow['chn'] = "010";
      }

      if ($lasrow['arvo'] >= 0) {
        //Veloituslasku
        $tyyppi='380';
      }
      else {
        //Hyvityslasku
        $tyyppi='381';
      }

      // Laskukohtaiset kommentit kuntoon
      // Tämä merkki | eli pystyviiva on rivinvaihdon merkki laskun kommentissa elmalla
      $komm = "";

      // Onko käänteistä verotusta
      $alvquery = "SELECT tunnus
                   FROM tilausrivi
                   WHERE yhtio     = '$kukarow[yhtio]'
                   and uusiotunnus in ($lasrow[tunnus])
                   and tyyppi      = 'L'
                   and alv         >= 600";
      $alvresult = pupe_query($alvquery);

      if (mysql_num_rows($alvresult) > 0) {
        $komm .= t_avainsana("KAANTALVVIESTI", $laskun_kieli, "", "", "", "selitetark");
      }

      if (trim($lasrow['tilausyhteyshenkilo']) != '') {
        $komm .= "\n".t("Tilaaja", $laskun_kieli).": ".$lasrow['tilausyhteyshenkilo'];
      }

      // Talenomilla tämä visualisoidaan muutenkin, eli ei tarvtse änkeä tähän kommenttiin.
      if ($yhtiorow["verkkolasku_lah"] != "talenom" and trim($lasrow['asiakkaan_tilausnumero']) != '') {
        $komm .= "\n".t("Tilauksenne", $laskun_kieli).": ".$lasrow['asiakkaan_tilausnumero'];
      }

      if (trim($lasrow['kohde']) != '') {
        $komm .= "\n".t("Kohde", $laskun_kieli).": ".$lasrow['kohde'];
      }

      if (trim($lasrow['sisviesti1']) != '') {
        $komm .= "\n".t("Kommentti", $laskun_kieli).": ".$lasrow['sisviesti1'];
      }

      if (trim($komm) != '') {
        $lasrow['sisviesti1'] = str_replace(array("\r\n", "\r", "\n"), "|", trim($komm));
      }

      // Hoidetaan pyöristys sekä valuuttakäsittely
      if ($lasrow["valkoodi"] != '' and trim(strtoupper($lasrow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
        $lasrow["kasumma"]    = $lasrow["kasumma_valuutassa"];
        $lasrow["summa"]    = $lasrow["summa_valuutassa"];
        $lasrow["arvo"]     = $lasrow["arvo_valuutassa"];
        $lasrow["pyoristys"] = $lasrow["pyoristys_valuutassa"];
      }

      if (strtoupper($laskun_kieli) != strtoupper($yhtiorow['kieli'])) {
        //Käännetään maksuehto
        $masrow["teksti"] = t_tunnus_avainsanat($masrow, "teksti", "MAKSUEHTOKV", $laskun_kieli);
      }

      $query = "SELECT
                ifnull(min(date_format(if('$yhtiorow[tilausrivien_toimitettuaika]' = 'X', toimaika, if('$yhtiorow[tilausrivien_toimitettuaika]' = 'K' and keratty = 'saldoton', toimaika, toimitettuaika)), '%Y-%m-%d')), '0000-00-00') mint,
                ifnull(max(date_format(if('$yhtiorow[tilausrivien_toimitettuaika]' = 'X', toimaika, if('$yhtiorow[tilausrivien_toimitettuaika]' = 'K' and keratty = 'saldoton', toimaika, toimitettuaika)), '%Y-%m-%d')), '0000-00-00') maxt
                FROM tilausrivi
                WHERE yhtio         = '$kukarow[yhtio]'
                and uusiotunnus     = '$lasrow[tunnus]'
                and toimitettuaika != '0000-00-00 00:00:00'";
      $toimaikares = pupe_query($query);
      $toimaikarow = mysql_fetch_assoc($toimaikares);

      if ($toimaikarow["mint"] == "0000-00-00") {
        $toimaikarow["mint"] = date("Y-m-d");
      }
      if ($toimaikarow["maxt"] == "0000-00-00") {
        $toimaikarow["maxt"] = date("Y-m-d");
      }

      // Laskun kaikki tilaukset
      $lasrow['tilausnumerot'] = hae_tilausnumero($lasrow["laskunro"]);

      //Kirjoitetaan failiin laskun otsikkotiedot
      if ($lasrow["chn"] == "111") {
        elmaedi_otsik($tootedi, $lasrow, $masrow, $tyyppi, $timestamppi, $toimaikarow);
      }
      elseif ($lasrow["chn"] == "112") {
        finvoice_otsik($tootsisainenfinvoice, $lasrow, $kieli, $pankkitiedot, $masrow, $myyrow, $tyyppi, $toimaikarow, "", "", $nosoap);
      }
      elseif (in_array($yhtiorow["verkkolasku_lah"], array("iPost", "finvoice", "maventa", "trustpoint", "ppg", "apix", "sepa", "talenom", "fitek"))) {
        finvoice_otsik($tootfinvoice, $lasrow, $kieli, $pankkitiedot, $masrow, $myyrow, $tyyppi, $toimaikarow, "", "", $nosoap);
      }
      else {
        pupevoice_otsik($tootxml, $lasrow, $laskun_kieli, $pankkitiedot, $masrow, $myyrow, $tyyppi, $toimaikarow);
      }

      // Tarvitaan rivien eri verokannat
      $alvquery = "SELECT distinct alv
                   FROM tilausrivi
                   WHERE yhtio     = '$kukarow[yhtio]'
                   and uusiotunnus = '$lasrow[tunnus]'
                   and tyyppi      = 'L'
                   ORDER BY alv";
      $alvresult = pupe_query($alvquery);

      while ($alvrow1 = mysql_fetch_assoc($alvresult)) {

        if ($alvrow1["alv"] >= 500) {
          $aquery = "SELECT '0' alv,
                     round(sum(tilausrivi.rivihinta/if (lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1)),2) rivihinta,
                     round(sum(0),2) alvrivihinta
                     FROM tilausrivi
                     JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
                     WHERE tilausrivi.uusiotunnus = '$lasrow[tunnus]' and tilausrivi.yhtio = '$kukarow[yhtio]' and tilausrivi.alv = '$alvrow1[alv]' and tilausrivi.tyyppi = 'L'
                     GROUP BY alv";
        }
        else {
          $aquery = "SELECT tilausrivi.alv,
                     round(sum(tilausrivi.rivihinta/if (lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1)),2) rivihinta,
                     round(sum((tilausrivi.rivihinta/if (lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1))*(tilausrivi.alv/100)),2) alvrivihinta
                     FROM tilausrivi
                     JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
                     WHERE tilausrivi.uusiotunnus = '$lasrow[tunnus]' and tilausrivi.yhtio = '$kukarow[yhtio]' and tilausrivi.alv = '$alvrow1[alv]' and tilausrivi.tyyppi = 'L'
                     GROUP BY alv";
        }
        $aresult = pupe_query($aquery);
        $alvrow = mysql_fetch_assoc($aresult);

        // Kirjotetaan failiin arvierittelyt
        if ($lasrow["chn"] == "111") {
          elmaedi_alvierittely($tootedi, $alvrow);
        }
        elseif ($lasrow["chn"] == "112") {
          finvoice_alvierittely($tootsisainenfinvoice, $lasrow, $alvrow);
        }
        elseif (in_array($yhtiorow["verkkolasku_lah"], array("iPost", "finvoice", "maventa", "trustpoint", "ppg", "apix", "sepa", "talenom", "arvato", "fitek"))) {
          finvoice_alvierittely($tootfinvoice, $lasrow, $alvrow);
        }
        else {
          pupevoice_alvierittely($tootxml, $alvrow);
        }
      }

      //Kirjoitetaan otsikkojen lopputiedot
      if ($lasrow["chn"] == "111") {
        elmaedi_otsikko_loput($tootedi, $lasrow);
      }
      elseif ($lasrow["chn"] == "112") {
        finvoice_otsikko_loput($tootsisainenfinvoice, $lasrow, $masrow, $pankkitiedot);
      }
      elseif (in_array($yhtiorow["verkkolasku_lah"], array("iPost", "finvoice", "maventa", "trustpoint", "ppg", "apix", "sepa", "talenom", "arvato", "fitek"))) {
        finvoice_otsikko_loput($tootfinvoice, $lasrow, $masrow, $pankkitiedot);
      }

      // katotaan miten halutaan sortattavan
      // haetaan asiakkaan tietojen takaa sorttaustiedot
      $order_sorttaus = '';

      if (mysql_num_rows($asiakas_apu_res) == 1) {
        $sorttauskentta = generoi_sorttauskentta($asiakas_apu_row["laskun_jarjestys"] != "" ? $asiakas_apu_row["laskun_jarjestys"] : $yhtiorow["laskun_jarjestys"]);
        $order_sorttaus = $asiakas_apu_row["laskun_jarjestys_suunta"] != "" ? $asiakas_apu_row["laskun_jarjestys_suunta"] : $yhtiorow["laskun_jarjestys_suunta"];
      }
      else {
        $sorttauskentta = generoi_sorttauskentta($yhtiorow["laskun_jarjestys"]);
        $order_sorttaus = $yhtiorow["laskun_jarjestys_suunta"];
      }

      // Asiakkaan / yhtiön laskutyyppi
      if ($lasrow['laskutyyppi'] == -9 or $lasrow['laskutyyppi'] == 0) {
        //jos laskulta löytyvät laskutyyppi on Oletus käytetään asiakkaan tai yhtiön oletus laskutyyppiä
        if (isset($asiakas_apu_row['laskutyyppi']) and $asiakas_apu_row['laskutyyppi'] != -9) {
          $laskutyyppi = $asiakas_apu_row['laskutyyppi'];
        }
        else {
          $laskutyyppi = $yhtiorow['laskutyyppi'];
        }
      }
      else {
        $laskutyyppi = $lasrow['laskutyyppi'];
      }

      if ($yhtiorow["laskun_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";
      else $pjat_sortlisa = "";

      $query_ale_lisa = generoi_alekentta('M');
      $ale_query_select_lisa = generoi_alekentta_select('yhteen', 'M');

      // Haetaan laskun kaikki rivit
      $query = "SELECT
                if ((tilausrivi.nimitys='Kuljetusvakuutus' OR '{$yhtiorow['yhdistetaan_identtiset_laskulla']}' = 'k'), tilausrivin_lisatiedot.vanha_otunnus, ifnull((SELECT vanha_otunnus from tilausrivin_lisatiedot t_lisa where t_lisa.yhtio=tilausrivi.yhtio and t_lisa.tilausrivitunnus=tilausrivi.perheid and t_lisa.omalle_tilaukselle != ''), tilausrivi.tunnus)) rivigroup,
                tilausrivi.ale1,
                tilausrivi.ale2,
                tilausrivi.ale3,
                $ale_query_select_lisa aleyhteensa,
                tilausrivi.alv,
                tuote.eankoodi,
                tuote.ei_saldoa,
                tilausrivi.erikoisale,
                tilausrivi.nimitys,
                tilausrivin_lisatiedot.osto_vai_hyvitys,
                tuote.sarjanumeroseuranta,
                tilausrivi.tuoteno,
                tilausrivi.uusiotunnus,
                tilausrivi.yksikko,
                tilausrivi.hinta,
                tilausrivi.netto,
                lasku.vienti_kurssi,
                lasku.viesti laskuviesti,
                lasku.asiakkaan_tilausnumero,
                lasku.luontiaika tilauspaiva,
                CONCAT(tuote.tullinimike1, IF(tuote.tullinimike2 NOT IN ('', '00', '0'), tuote.tullinimike2, '')) AS tullinimike,
                if (tuote.tuotetyyppi = 'K','2 Työt','1 Muut') tuotetyyppi,
                if (tilausrivi.var2 = 'EIOST', 'EIOST', '') var2,
                if (tuote.myyntihinta_maara = 0, 1, tuote.myyntihinta_maara) myyntihinta_maara,
                min(tilausrivi.hyllyalue) hyllyalue,
                min(tilausrivi.hyllynro) hyllynro,
                min(tilausrivi.keratty) keratty,
                min(if (tilausrivi.toimaika = '0000-00-00', date_format(now(), '%Y-%m-%d'), tilausrivi.toimaika)) toimaika,
                min(if (date_format(tilausrivi.toimitettuaika, '%Y-%m-%d') = '0000-00-00', date_format(now(), '%Y-%m-%d'), date_format(tilausrivi.toimitettuaika, '%Y-%m-%d'))) toimitettuaika,
                min(tilausrivi.otunnus) otunnus,
                min(tilausrivi.perheid) perheid,
                min(tilausrivi.tunnus) tunnus,
                min(tilausrivi.kommentti) kommentti,
                min(tilausrivi.tilaajanrivinro) tilaajanrivinro,
                min(tilausrivi.laadittu) laadittu,
                min(tilausrivin_lisatiedot.tiliointirivitunnus) tiliointirivitunnus,
                sum(tilausrivi.tilkpl) tilkpl,
                sum(tilausrivi.kpl) kpl,
                sum(tilausrivi.rivihinta) rivihinta,
                sum(round(tilausrivi.hinta * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa}, $yhtiorow[hintapyoristys])) rivihinta_verollinen,
                sum((tilausrivi.hinta / lasku.vienti_kurssi) / if ('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa}) rivihinta_valuutassa,
                sum((tilausrivi.hinta / lasku.vienti_kurssi) * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa}) rivihinta_valuutassa_verollinen,
                group_concat(tilausrivi.tunnus) rivitunnukset,
                group_concat(distinct tilausrivi.perheid) perheideet,
                count(*) rivigroup_maara,
                $sorttauskentta
                FROM tilausrivi
                JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
                JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
                LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
                WHERE tilausrivi.yhtio      = '$kukarow[yhtio]'
                and (tilausrivi.perheid = 0 or tilausrivi.perheid=tilausrivi.tunnus or tilausrivin_lisatiedot.ei_nayteta !='E' or tilausrivin_lisatiedot.ei_nayteta is null)
                and tilausrivi.kpl         != 0
                and tilausrivi.tyyppi      = 'L'
                and tilausrivi.uusiotunnus = '$lasrow[tunnus]'
                GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25
                ORDER BY tilausrivi.otunnus, if(tilausrivi.tuoteno in ('$yhtiorow[kuljetusvakuutus_tuotenumero]','$yhtiorow[laskutuslisa_tuotenumero]'), 2, 1), $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
      $tilres = pupe_query($query);

      $rivinumerot   = array(0 => 0);
      $rivilaskuri   = 1;
      $rivimaara     = mysql_num_rows($tilres);
      $rivigrouppaus = FALSE;
      $tilrows       = array();

      while ($tilrow = mysql_fetch_assoc($tilres)) {
        if ($yhtiorow["pura_osaluettelot"] != "") {
          // Korvataanko tilauksella oleva rivi osaluettelolla
          $tilrows = array_merge($tilrows, pura_osaluettelot($lasrow, $tilrow, $laskutyyppi));
        }
        else {
          $tilrows[] = $tilrow;
        }
      }

      foreach ($tilrows as $tilrow) {
        // Näytetään vain perheen isä ja summataan lasten hinnat isäriville
        if ($laskutyyppi == 2 or $laskutyyppi == 12) {
          if ($tilrow["perheid"] > 0) {
            // kyseessä on isä
            if ($tilrow["perheid"] == $tilrow["tunnus"]) {
              // lasketaan isätuotteen riville lapsien hinnat yhteen
              $query = "SELECT
                        sum(tilausrivi.rivihinta) rivihinta,
                        round(sum(tilausrivi.hinta
                            * tilausrivi.kpl
                            * {$query_ale_lisa})
                          / $tilrow[kpl], '$yhtiorow[hintapyoristys]') hinta,
                        sum(round(tilausrivi.hinta * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * tilausrivi.kpl * {$query_ale_lisa}, $yhtiorow[hintapyoristys])) rivihinta_verollinen,
                        sum((tilausrivi.hinta / lasku.vienti_kurssi) / if ('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * tilausrivi.kpl * {$query_ale_lisa}) rivihinta_valuutassa,
                        sum((tilausrivi.hinta / lasku.vienti_kurssi) * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * tilausrivi.kpl * {$query_ale_lisa}) rivihinta_valuutassa_verollinen
                        FROM tilausrivi
                        JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
                        WHERE tilausrivi.yhtio     = '$kukarow[yhtio]'
                        and tilausrivi.uusiotunnus = '$tilrow[uusiotunnus]'
                        and tilausrivi.perheid     in ($tilrow[perheideet])
                        and tilausrivi.perheid     > 0";
              $riresult = pupe_query($query);
              $perherow = mysql_fetch_assoc($riresult);

              $tilrow["hinta"] = $perherow["hinta"];
              $tilrow["rivihinta"] = $perherow["rivihinta"];
              $tilrow["rivihinta_verollinen"] = $perherow["rivihinta_verollinen"];
              $tilrow["rivihinta_valuutassa"] = $perherow["rivihinta_valuutassa"];
              $tilrow["rivihinta_valuutassa_verollinen"] = $perherow["rivihinta_valuutassa_verollinen"];

              // Nollataan alet, koska hinta lasketaan rivihinnasta jossa alet on jo huomioitu
              for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                $tilrow["ale{$alepostfix}"] = "";
              }

              $tilrow["erikoisale"] = "";
            }
            else {
              // lapsia ei lisätä
              continue;
            }
          }
        }

        if (strtolower($laskun_kieli) != strtolower($yhtiorow['kieli'])) {
          //Käännetään nimitys
          $tilrow['nimitys'] = t_tuotteen_avainsanat($tilrow, 'nimitys', $laskun_kieli);
        }

        // Rivin toimitusaika
        if ($yhtiorow["tilausrivien_toimitettuaika"] == 'K' and $tilrow["keratty"] == "saldoton") {
          $tilrow["toimitettuaika"] = $tilrow["toimaika"];
        }
        elseif ($yhtiorow["tilausrivien_toimitettuaika"] == 'X') {
          $tilrow["toimitettuaika"] = $tilrow["toimaika"];
        }
        else {
          $tilrow["toimitettuaika"] = $tilrow["toimitettuaika"];
        }

        if ($tilrow["rivigroup_maara"] > 1 and !$rivigrouppaus) {
          $rivigrouppaus = TRUE;
        }

        // Otetaan yhteensäkommentti pois jos summataan rivejä
        if ($rivigrouppaus) {
          // Trimmataan ja otetaan "yhteensäkommentti" pois
          $tilrow["kommentti"] = trim(poista_rivin_yhteensakommentti($tilrow["kommentti"]));
        }

        // Laitetaan alennukset kommenttiin, koska laskulla on vain yksi alekenttä
        if ($yhtiorow['myynnin_alekentat'] > 1 or $tilrow['erikoisale'] > 0) {

          $alekomm = "";

          for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
            if (trim($tilrow["ale{$alepostfix}"]) > 0) {
              $alekomm .= t("Ale")."{$alepostfix} ".($tilrow["ale{$alepostfix}"]*1)." %|";
            }
          }

          if ($tilrow['erikoisale'] > 0) {
            $alekomm .= t("Erikoisale")." ".($tilrow["erikoisale"]*1)." %|";
          }

          $tilrow["kommentti"] = $alekomm.$tilrow["kommentti"];
        }

        //Käänteinen arvonlisäverovelvollisuus ja Käytetyn tavaran myynti
        if ($tilrow["alv"] >= 600) {
          $tilrow["alv"] = 0;
          $tilrow["kommentti"] .= " Ei lisättyä arvonlisäveroa, ostajan käännetty verovelvollisuus.";
        }
        elseif ($tilrow["alv"] >= 500) {
          $tilrow["alv"] = 0;
          $tilrow["kommentti"] .= " Ei sisällä vähennettävää veroa.";
        }

        //Hetaan sarjanumeron tiedot
        if ($tilrow["kpl"] > 0) {
          $sarjanutunnus = "myyntirivitunnus";
        }
        else {
          $sarjanutunnus = "ostorivitunnus";
        }

        $query = "SELECT *
                  FROM sarjanumeroseuranta
                  WHERE yhtio      = '$kukarow[yhtio]'
                  and tuoteno      = '$tilrow[tuoteno]'
                  and $sarjanutunnus in ($tilrow[rivitunnukset])
                  and sarjanumero != ''";
        $sarjares = pupe_query($query);

        if ($tilrow["kommentti"] != '' and mysql_num_rows($sarjares) > 0) {
          $tilrow["kommentti"] .= " ";
        }
        while ($sarjarow = mysql_fetch_assoc($sarjares)) {
          $tilrow["kommentti"] .= "S:nro: $sarjarow[sarjanumero] ";
        }

        if ($laskutyyppi == 7) {

          if ($tilrow["eankoodi"] != "") {
            $tilrow["kommentti"] = "EAN: $tilrow[eankoodi]|$tilrow[kommentti]";
          }

          $query = "SELECT kommentti
                    FROM asiakaskommentti
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tuoteno = '{$tilrow['tuoteno']}'
                    AND ytunnus = '{$lasrow['ytunnus']}'
                    AND tyyppi  = ''
                    ORDER BY tunnus";
          $asiakaskommentti_res = pupe_query($query);

          if (mysql_num_rows($asiakaskommentti_res) > 0) {
            while ($asiakaskommentti_row = mysql_fetch_assoc($asiakaskommentti_res)) {
              $tilrow["kommentti"] .= "|".$asiakaskommentti_row['kommentti'];
            }
          }
        }

        if ($lasrow["valkoodi"] != '' and trim(strtoupper($lasrow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
          // Veroton rivihinta valuutassa
          $tilrow["rivihinta"] = $tilrow["rivihinta_valuutassa"];

          // Verollinen rivihinta valuutassa
          $tilrow["rivihinta_verollinen"] = $tilrow["rivihinta_valuutassa_verollinen"];

          // Yksikköhinta valuutassa
          $tilrow["hinta"] = laskuval($tilrow["hinta"], $tilrow["vienti_kurssi"]);
        }

        // Yksikköhinta on laskulla aina veroton
        if ($yhtiorow["alv_kasittely"] == '') {
          // Tuotteiden myyntihinnat sisältävät arvonlisäveron
          $tilrow["hinta"] = $tilrow["hinta"] / (1 + $tilrow["alv"] / 100);
          $tilrow["hinta_verollinen"] = $tilrow["hinta"];
        }
        else {
          // Tuotteiden myyntihinnat ovat arvonlisäverottomia
          $tilrow["hinta"] = $tilrow["hinta"];
          $tilrow["hinta_verollinen"] = $tilrow["hinta"] * (1 + $tilrow["alv"] / 100);
        }

        // Veron määrä
        $vatamount = $tilrow['rivihinta'] * $tilrow['alv'] / 100;

        // Pyöristetään ja formatoidaan lopuksi
        $tilrow["hinta"] = hintapyoristys($tilrow["hinta"]);
        $tilrow["rivihinta"] = hintapyoristys($tilrow["rivihinta"]);
        $tilrow["rivihinta_verollinen"] = hintapyoristys($tilrow["rivihinta_verollinen"]);
        $vatamount = hintapyoristys($vatamount);

        $tilrow['kommentti'] = pupesoft_invoicestring(str_replace("\n", "|", $tilrow['kommentti']));
        $tilrow['nimitys'] = pupesoft_invoicestring($tilrow['nimitys']);

        // Otetaan seuraavan rivin otunnus
        if ($rivilaskuri < $rivimaara) {
          $tilrow_seuraava = mysql_fetch_assoc($tilres);
          mysql_data_seek($tilres, $rivilaskuri);

          if ($tilrow_seuraava['tuoteno'] == $yhtiorow["kuljetusvakuutus_tuotenumero"] or $tilrow_seuraava['tuoteno'] == $yhtiorow["laskutuslisa_tuotenumero"]) {
            $tilrow['seuraava_otunnus'] = 0;
          }
          else {
            $tilrow['seuraava_otunnus'] = $tilrow_seuraava["otunnus"];
          }
        }
        else {
          $tilrow['seuraava_otunnus'] = 0;
        }

        if ($lasrow["chn"] == "111") {

          if ((int) substr(sprintf("%06s", $tilrow["tilaajanrivinro"]), -6) > 0 and !in_array((int) substr(sprintf("%06s", $tilrow["tilaajanrivinro"]), -6), $rivinumerot)) {
            $rivinumero  = (int) substr(sprintf("%06s", $tilrow["tilaajanrivinro"]), -6);
          }
          else {
            $rivinumero = (int) substr(sprintf("%06s", $tilrow["tunnus"]), -6);
          }

          elmaedi_rivi($tootedi, $tilrow, $rivinumero);
        }
        elseif ($lasrow["chn"] == "112") {
          finvoice_rivi($tootsisainenfinvoice, $tilrow, $lasrow, $vatamount, $laskutyyppi);
        }
        elseif (in_array($yhtiorow["verkkolasku_lah"], array("iPost", "finvoice", "maventa", "trustpoint", "ppg", "apix", "sepa", "talenom", "arvato", "fitek"))) {
          finvoice_rivi($tootfinvoice, $tilrow, $lasrow, $vatamount, $laskutyyppi);
        }
        else {
          pupevoice_rivi($tootxml, $tilrow, $vatamount);
        }

        $rivilaskuri++;
      }

      // Lopetetaan lasku
      if ($lasrow["chn"] == "111") {
        elmaedi_lasku_loppu($tootedi, $lasrow);

        $edilask++;
      }
      elseif ($lasrow["chn"] == "112") {
        finvoice_lasku_loppu($tootsisainenfinvoice, $lasrow, $pankkitiedot, $masrow);
      }
      elseif (in_array($yhtiorow["verkkolasku_lah"], array("iPost", "finvoice", "maventa", "trustpoint", "ppg", "apix", "sepa", "talenom", "arvato", "fitek"))) {
        $tilausnumerot                 = hae_tilausnumero($lasrow["laskunro"]);
        $liitteet[$lasrow["laskunro"]] = hae_liitteet_verkkolaskuun($yhtiorow["verkkolasku_lah"], $tilausnumerot);
        $liitteita                     = !empty($liitteet[$lasrow["laskunro"]]);

        finvoice_lasku_loppu($tootfinvoice, $lasrow, $pankkitiedot, $masrow, $liitteita);

        if ($yhtiorow["verkkolasku_lah"] == "apix") {
          $tulostettavat_apix[] = $lasrow["laskunro"];
        }
      }
      else {
        pupevoice_lasku_loppu($tootxml);
      }

      $lask++;
    }
    else {
      echo "\n".t("Tämä lasku ei mene verkkolaskuoperaattorille")."! $lasrow[laskunro] $lasrow[nimi]<br>\n";
    }
  }

  // Aineistojen lopputägit
  elmaedi_aineisto_loppu($tootedi, $timestamppi);
  pupevoice_aineisto_loppu($tootxml);

  //  Tulostetaan virheet jos niitä oli
  echo $tulos_ulos;

  // suljetaan faili
  fclose($tootxml);
  fclose($tootedi);
  fclose($tootfinvoice);
  fclose($tootsisainenfinvoice);

  //dellataan failit jos ne on tyhjiä
  if (filesize($nimixml) == 0) {
    unlink($nimixml);
  }
  elseif (PUPE_UNICODE) {
    // Muutetaan ISO-8859-15:ksi jos lasku on jossain toisessa merkistössä
    exec("recode --force UTF8..ISO-8859-15 '$nimixml'");
  }

  if (filesize($nimifinvoice) == 0) {
    unlink($nimifinvoice);
  }
  elseif (PUPE_UNICODE) {
    // Muutetaan ISO-8859-15:ksi jos lasku on jossain toisessa merkistössä
    exec("recode --force UTF8..ISO-8859-15 '$nimifinvoice'");
  }

  if (filesize($nimiedi) == 0) {
    unlink($nimiedi);
  }
  elseif (PUPE_UNICODE) {
    // Muutetaan ISO-8859-15:ksi jos lasku on jossain toisessa merkistössä
    exec("recode --force UTF8..ISO-8859-15 '$nimiedi'");
  }

  if (filesize($nimisisainenfinvoice) == 0) {
    unlink($nimisisainenfinvoice);
  }
  elseif (PUPE_UNICODE) {
    // Muutetaan ISO-8859-15:ksi jos lasku on jossain toisessa merkistössä
    exec("recode --force UTF8..ISO-8859-15 '$nimisisainenfinvoice'");
  }

  if (count($tulostettavat_apix) > 0) {
    require_once "tilauskasittely/tulosta_lasku.inc";
  }

  if ($tee == "NAYTATILAUS" and !empty($nayta_ja_tallenna)) {
    header("Pragma: public");
    header("Expires: 0");
    header("HTTP/1.1 200 OK");
    header("Status: 200 OK");
    header("Accept-Ranges: bytes");
    header("Content-Description: File Transfer");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: private", false);
    header("Content-Transfer-Encoding: binary");
    header("Content-Type: application/force-download");
    header('Content-Disposition: attachment; filename='.basename($kaunisnimi));
    header("Content-Length: ".filesize($nimifinvoice));
    readfile($nimifinvoice);
    exit;
  }
  elseif ($tee == "NAYTATILAUS") {
    header("Content-type: text/xml");
    header("Content-length: ".(filesize($nimifinvoice)));
    header("Content-Disposition: inline; filename=$nimifinvoice");
    header("Content-Description: Lasku");

    readfile($nimifinvoice);
  }
  else {
    if (file_exists(realpath($nimixml))) {
      //siirretaan laskutiedosto operaattorille
      $ftphost = (isset($verkkohost_lah) and trim($verkkohost_lah) != '') ? $verkkohost_lah : "ftp.verkkolasku.net";
      $ftpuser = $yhtiorow['verkkotunnus_lah'];
      $ftppass = $yhtiorow['verkkosala_lah'];
      $ftppath = (isset($verkkopath_lah) and trim($verkkopath_lah) != '') ? $verkkopath_lah : "out/einvoice/data/";
      $ftpfile = realpath($nimixml);

      // tätä ei ajata eikä käytetä, mutta jos tulee ftp errori niin echotaan tää meiliin, niin ei tartte käsin kirjotella resendiä
      echo "<pre>ncftpput -u $ftpuser -p $ftppass $ftphost $ftppath $ftpfile</pre>";

      echo "<table>";
      echo "<tr><th>".t("Tallenna pupevoice-aineisto").":</th>";
      echo "<form method='post' class='multisubmit'>";
      echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
      echo "<input type='hidden' name='kaunisnimi' value='".basename($nimixml)."'>";
      echo "<input type='hidden' name='filenimi' value='".basename($nimixml)."'>";
      echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
      echo "</table><br><br>";

      echo "<table>";
      echo "<tr><th>".t("Lähetä pupevoice-aineisto uudestaan Itellaan").":</th>";
      echo "<form method='post'>";
      echo "<input type='hidden' name='tee' value='pupevoice_siirto'>";
      echo "<input type='hidden' name='filenimi' value='".basename($nimixml)."'>";
      echo "<td class='back'><input type='submit' value='".t("Lähetä")."'></td></tr></form>";
      echo "</table>";
    }
    elseif ($yhtiorow["verkkolasku_lah"] == "finvoice" and file_exists(realpath($nimifinvoice))) {
      //siirretaan laskutiedosto operaattorille
      $ftphost = "ftp.itella.net";
      $ftpuser = $yhtiorow['verkkotunnus_lah'];
      $ftppass = $yhtiorow['verkkosala_lah'];
      $ftppath = "out/finvoice/data/";
      $ftpfile = realpath($nimifinvoice);
      $renameftpfile = $nimifinvoice_delivered;

      // tätä ei ajata eikä käytetä, mutta jos tulee ftp errori niin echotaan tää meiliin, niin ei tartte käsin kirjotella resendiä
      echo "<pre>mv $ftpfile ".str_replace("TRANSFER_", "DELIVERED_", $ftpfile)."\nncftpput -u $ftpuser -p $ftppass -T T $ftphost $ftppath ".str_replace("TRANSFER_", "DELIVERED_", $ftpfile)."</pre>";

      echo "<table>";
      echo "<tr><th>".t("Tallenna finvoice-aineisto").":</th>";
      echo "<form method='post' class='multisubmit'>";
      echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
      echo "<input type='hidden' name='kaunisnimi' value='".basename($nimifinvoice)."'>";
      echo "<input type='hidden' name='filenimi' value='".basename($nimifinvoice)."'>";
      echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
      echo "</table>";
    }
    elseif ($yhtiorow["verkkolasku_lah"] == "apix" and file_exists(realpath($nimifinvoice))) {

      $timestamp    = gmdate("YmdHis");
      $apixfinvoice  = basename($nimifinvoice);
      $apixzipfile  = "Apix_".$yhtiorow['yhtio']."_invoices_$timestamp.zip";

      // Luodaan temppidirikka jonne työnnetään tän kiekan kaikki apixfilet
      list($usec, $sec) = explode(' ', microtime());
      mt_srand((float) $sec + ((float) $usec * 100000));
      $apix_tmpdirnimi = "/tmp/apix-".md5(uniqid(mt_rand(), true));

      if (mkdir($apix_tmpdirnimi)) {

        // Kopsataan finvoiceaineisto dirikkaan
        if (!copy("/tmp/".$apixfinvoice, $apix_tmpdirnimi."/".$apixfinvoice)) {
          echo "APIX finvoicemove $apixfinvoice feilas!";
        }

        // Luodaan laskupdf:ät
        foreach ($tulostettavat_apix as $apixlasku) {
          $apixtmpfile = tulosta_lasku("LASKU:".$apixlasku, $kieli, "VERKKOLASKU_APIX", "", "", "", "");

          // Siirretään faili apixtemppiin
          if (!rename($apixtmpfile, $apix_tmpdirnimi."/Apix_invoice_$apixlasku.pdf")) {
            echo "APIX tmpmove Apix_invoice_$apixlasku.pdf feilas!";
          }

          if (!empty($liitteet[$apixlasku])) {
            $attachment_dir = "{$apix_tmpdirnimi}/attachments";

            mkdir($attachment_dir);

            foreach ($liitteet[$apixlasku] as $filename => $data) {
              file_put_contents("{$attachment_dir}/{$filename}", $data);
            }

            exec("cd {$attachment_dir}; zip ../Apix_attachments_{$apixlasku}.zip *;");

            exec("rm -rf {$attachment_dir}");
          }
        }

        // Tehdään apixzippi
        exec("cd $apix_tmpdirnimi; zip $apixzipfile *;");

        // Aineisto dataouttiin
        exec("cp $apix_tmpdirnimi/$apixzipfile $pupe_root_polku/dataout/");

        // Poistetaan apix-tmpdir
        exec("rm -rf $apix_tmpdirnimi");

        echo "<table>";
        echo "<tr><th>".t("Tallenna apix-aineisto").":</th>";
        echo "<form method='post' class='multisubmit'>";
        echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
        echo "<input type='hidden' name='kaunisnimi' value='".basename($apixzipfile)."'>";
        echo "<input type='hidden' name='filenimi' value='".basename($apixzipfile)."'>";
        echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
        echo "</table><br><br>";

        echo "<table>";
        echo "<tr><th>".t("Lähetä aineisto uudestaan APIX:lle").":</th>";
        echo "<form method='post'>";
        echo "<input type='hidden' name='tee' value='apix_siirto'>";
        echo "<input type='hidden' name='filenimi' value='".basename($nimifinvoice)."'>";
        echo "<td class='back'><input type='submit' value='".t("Lähetä")."'></td></tr></form>";
        echo "</table>";
      }
      else {
        echo "APIX tmpdirrin teko feilas!<br>";
      }
    }
    elseif ($yhtiorow["verkkolasku_lah"] == "maventa" and file_exists(realpath($nimifinvoice))) {
      // Täytetään api_keys, näillä kirjaudutaan Maventaan
      $virhe = 0;
      $api_keys = array();
      $api_keys["user_api_key"]   = $yhtiorow['maventa_api_avain'];
      $api_keys["vendor_api_key"] = $yhtiorow['maventa_ohjelmisto_api_avain'];

      // Vaihtoehtoinen company_uuid
      if ($yhtiorow['maventa_yrityksen_uuid'] != "") {
        $api_keys["company_uuid"] = $yhtiorow['maventa_yrityksen_uuid'];
      }

      if ($api_keys["user_api_key"] == "" or $api_keys["vendor_api_key"] == "") {
        echo "<p class='error'>".t("Ei voida lähettää materiaalia Maventalle, koska Maventa-avaimet puuttuu")."</p>";
        $virhe = 1;
      }

      echo "<table>";
      echo "<tr><th>".t("Tallenna Maventa-aineisto").":</th>";
      echo "<form method='post' class='multisubmit'>";
      echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
      echo "<input type='hidden' name='kaunisnimi' value='".basename($nimifinvoice)."'>";
      echo "<input type='hidden' name='filenimi' value='".basename($nimifinvoice)."'>";
      echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
      echo "</table><br><br>";

      if ($virhe == 0) {
        echo "<form method='post'>";
        echo "<input type='hidden' name='tee' value='maventa_siirto'>";
        echo "<input type='hidden' name='filenimi' value='".basename($nimifinvoice)."'>";
        echo "<table>";
        echo "<tr><th>".t("Lähetä aineisto uudestaan MAVENTA:lle").":</th>";
        echo "<td class='back'><input type='submit' value='".t("Lähetä")."'></td></tr>";
        echo "</table>";
        echo "</form>";
      }
    }
    elseif ($yhtiorow["verkkolasku_lah"] == "trustpoint" and file_exists(realpath($nimifinvoice))) {
      echo "<table>";
      echo "<tr><th>".t("Tallenna finvoice-aineisto").":</th>";
      echo "<form method='post' class='multisubmit'>";
      echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
      echo "<input type='hidden' name='kaunisnimi' value='".basename($nimifinvoice)."'>";
      echo "<input type='hidden' name='filenimi' value='".basename($nimifinvoice)."'>";
      echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
      echo "</table><br><br>";

      echo "<table>";
      echo "<tr><th>".t("Lähetä aineisto uudestaan Trust Point:iin").":</th>";
      echo "<form method='post'>";
      echo "<input type='hidden' name='tee' value='trustpoint_siirto'>";
      echo "<input type='hidden' name='filenimi' value='".basename($nimifinvoice)."'>";
      echo "<td class='back'><input type='submit' value='".t("Lähetä")."'></td></tr></form>";
      echo "</table>";
    }
    elseif ($yhtiorow["verkkolasku_lah"] == "ppg" and file_exists(realpath($nimifinvoice))) {
      echo "<table>";
      echo "<tr><th>".t("Tallenna finvoice-aineisto").":</th>";
      echo "<form method='post' class='multisubmit'>";
      echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
      echo "<input type='hidden' name='kaunisnimi' value='".basename($nimifinvoice)."'>";
      echo "<input type='hidden' name='filenimi' value='".basename($nimifinvoice)."'>";
      echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
      echo "</table><br><br>";

      echo "<table>";
      echo "<tr><th>".t("Lähetä aineisto uudestaan PPG Laskutuspalveluun").":</th>";
      echo "<form method='post'>";
      echo "<input type='hidden' name='tee' value='ppg_siirto'>";
      echo "<input type='hidden' name='filenimi' value='".basename($nimifinvoice)."'>";
      echo "<td class='back'><input type='submit' value='".t("Lähetä")."'></td></tr></form>";
      echo "</table>";
    }
    elseif ($yhtiorow["verkkolasku_lah"] == "talenom" and file_exists(realpath($nimifinvoice))) {
      echo "<table>";
      echo "<tr><th>".t("Tallenna finvoice-aineisto").":</th>";
      echo "<form method='post' class='multisubmit'>";
      echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
      echo "<input type='hidden' name='kaunisnimi' value='".basename($nimifinvoice)."'>";
      echo "<input type='hidden' name='filenimi' value='".basename($nimifinvoice)."'>";
      echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
      echo "</table><br><br>";

      echo "<table>";
      echo "<tr><th>".t("Lähetä aineisto uudestaan Talenom Myyntilaskutuspalveluun").":</th>";
      echo "<form method='post'>";
      echo "<input type='hidden' name='tee' value='talenom_siirto'>";
      echo "<input type='hidden' name='filenimi' value='".basename($nimifinvoice)."'>";
      echo "<td class='back'><input type='submit' value='".t("Lähetä")."'></td></tr></form>";
      echo "</table>";
    }
    elseif ($yhtiorow["verkkolasku_lah"] == "sepa" and file_exists(realpath($nimifinvoice))) {
      echo "<form method='post' class='multisubmit'>";
      echo "<table>";
      echo "<tr><th>".t("Tallenna finvoice-aineisto").":</th>";
      echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
      echo "<input type='hidden' name='kaunisnimi' value='".basename($nimifinvoice)."'>";
      echo "<input type='hidden' name='filenimi' value='".basename($nimifinvoice)."'>";
      echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr>";
      echo "</table><br><br>";
      echo "</form>";

      echo "<form method='post'>";
      echo "<input type='hidden' name='tee' value='sepa_siirto'>";
      echo "<input type='hidden' name='filenimi' value='".basename($nimifinvoice)."'>";
      echo "<table>";
      echo "<tr><th colspan='2'>".t("Lähetä aineisto uudestaan pankkiin").":</th>";
      echo "<td class='back'><input type='submit' value='".t("Lähetä")."'></td></tr>";
      echo "</table></form>";
    }
    elseif ($yhtiorow["verkkolasku_lah"] == "arvato" and file_exists(realpath($nimifinvoice))) {
      echo "<table>";
      echo "<tr><th>".t("Tallenna finvoice-aineisto").":</th>";
      echo "<form method='post' class='multisubmit'>";
      echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
      echo "<input type='hidden' name='kaunisnimi' value='".basename($nimifinvoice)."'>";
      echo "<input type='hidden' name='filenimi' value='".basename($nimifinvoice)."'>";
      echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
      echo "</table><br><br>";

      echo "<table>";
      echo "<tr><th>".t("Lähetä aineisto uudestaan Arvato Laskutuspalveluun").":</th>";
      echo "<form method='post'>";
      echo "<input type='hidden' name='tee' value='arvato_siirto'>";
      echo "<input type='hidden' name='filenimi' value='".basename($nimifinvoice)."'>";
      echo "<td class='back'><input type='submit' value='".t("Lähetä")."'></td></tr></form>";
      echo "</table>";
    }
    elseif ($yhtiorow["verkkolasku_lah"] == "fitek" and file_exists(realpath($nimifinvoice))) {
      echo "<table>";
      echo "<tr><th>".t("Tallenna finvoice-aineisto").":</th>";
      echo "<form method='post' class='multisubmit'>";
      echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
      echo "<input type='hidden' name='kaunisnimi' value='".basename($nimifinvoice)."'>";
      echo "<input type='hidden' name='filenimi' value='".basename($nimifinvoice)."'>";
      echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
      echo "</table><br><br>";

      echo "<table>";
      echo "<tr><th>".t("Lähetä aineisto uudestaan Fitekille").":</th>";
      echo "<form method='post'>";
      echo "<input type='hidden' name='tee' value='fitek_siirto'>";
      echo "<input type='hidden' name='filenimi' value='".basename($nimifinvoice)."'>";
      echo "<td class='back'><input type='submit' value='".t("Lähetä")."'></td></tr></form>";
      echo "</table>";
    }
    elseif (in_array($yhtiorow["verkkolasku_lah"], array("trustpoint", "ppg", "sepa", "talenom", "arvato", "fitek")) and !file_exists(realpath($nimifinvoice))) {
      echo "<br>".t("Tallenna finvoice-aineisto").":<br>";

      js_openFormInNewWindow();

      foreach (explode(',', $laskunumerot) as $lasku) {
        echo "<form id='finvoice_$lasku' name='finvoice_$lasku' method='post' action='{$palvelin2}tilauskasittely/uudelleenluo_laskuaineisto.php' class='multisubmit'>
            <input type='hidden' name='laskunumerot' value='$lasku'>
            <input type='hidden' name='tee' value='NAYTATILAUS'>
            <input type='hidden' name='nayta_ja_tallenna' value='TRUE'>
            <input type='hidden' name='kaunisnimi' value='Finvoice_$lasku.xml'>
            <input type='submit' value='".t("Tallenna Finvoice").": $lasku' onClick=\"js_openFormInNewWindow('finvoice_$lasku', 'samewindow'); return false;\"></form>";
      }
    }
    elseif ($yhtiorow["verkkolasku_lah"] == "iPost" and file_exists(realpath($nimifinvoice))) {
      echo "<table>";
      echo "<tr><th>".t("Tallenna finvoice-aineisto").":</th>";
      echo "<form method='post' class='multisubmit'>";
      echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
      echo "<input type='hidden' name='kaunisnimi' value='".basename($nimifinvoice)."'>";
      echo "<input type='hidden' name='filenimi' value='".basename($nimifinvoice)."'>";
      echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
      echo "</table><br><br>";

      echo "<table>";
      echo "<tr><th>".t("Lähetä iPost-aineisto uudestaan Itellaan").":</th>";
      echo "<form method='post'>";
      echo "<input type='hidden' name='tee' value='ipost_siirto'>";
      echo "<input type='hidden' name='filenimi' value='".basename($nimifinvoice)."'>";
      echo "<td class='back'><input type='submit' value='".t("Lähetä")."'></td></tr></form>";
      echo "</table>";
    }

    if (file_exists(realpath($nimiedi))) {
      //siirretaan laskutiedosto operaattorille
      $ftphost = $edi_ftphost;
      $ftpuser = $edi_ftpuser;
      $ftppass = $edi_ftppass;
      $ftppath = $edi_ftppath;
      $ftpfile = realpath($nimiedi);

      // tätä ei ajata eikä käytetä, mutta jos tulee ftp errori niin echotaan tää meiliin, niin ei tartte käsin kirjotella resendiä
      echo "<pre>ncftpput -u $ftpuser -p $ftppass $ftphost $ftppath $ftpfile</pre>";

      echo "<table>";
      echo "<tr><th>".t("Tallenna Elmaedi-aineisto").":</th>";
      echo "<form method='post' class='multisubmit'>";
      echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
      echo "<input type='hidden' name='kaunisnimi' value='".basename($nimiedi)."'>";
      echo "<input type='hidden' name='filenimi' value='".basename($nimiedi)."'>";
      echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
      echo "</table><br><br>";

      echo "<table>";
      echo "<tr><th>".t("Lähetä Elmaedi-aineisto uudelleen").":</th>";
      echo "<form method='post'>";
      echo "<input type='hidden' name='tee' value='edi_siirto'>";
      echo "<input type='hidden' name='filenimi' value='".basename($nimiedi)."'>";
      echo "<td class='back'><input type='submit' value='".t("Lähetä")."'></td></tr></form>";
      echo "</table>";
    }

    if (file_exists(realpath($nimisisainenfinvoice))) {
      echo "<table>";
      echo "<tr><th>".t("Tallenna Pupesoft-Finvoice-aineisto").":</th>";
      echo "<form method='post' class='multisubmit'>";
      echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
      echo "<input type='hidden' name='kaunisnimi' value='".basename($nimisisainenfinvoice)."'>";
      echo "<input type='hidden' name='filenimi' value='".basename($nimisisainenfinvoice)."'>";
      echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
      echo "</table>";

      if (!empty($sisainenfoinvoice_ftphost)) {
        echo "<table>";
        echo "<tr><th>".t("Siirrä laskut uudelleenkäsittelyyn Pupesoftiin").":</th>";
        echo "<form method='post' class='multisubmit'>";
        echo "<input type='hidden' name='tee' value='kasittele_pupesoft_finvoice'>";
        echo "<input type='hidden' name='filenimi' value='".basename($nimisisainenfinvoice)."'>";
        echo "<td class='back'><input type='submit' value='".t("Käsittele")."'></td></tr></form>";
        echo "</table>";
      }
    }
  }
}

if (!isset($tee) or $tee == "") {
  echo "<font class='message'>".t("Anna laskunumerot, pilkulla eroteltuina, joista aineisto muodostetaan:")."</font><br>";
  echo "<form method='post'>";
  echo "<input type='hidden' name='tee' value='GENEROI'>";
  echo "<textarea name='laskunumerot' rows='10' cols='60'></textarea>";
  echo "<input type='submit' value='Luo aineisto'>";
  echo "</form>";
}

if (!isset($tee) or $tee != "NAYTATILAUS") require "inc/footer.inc";

function hae_tilausnumero($laskunro) {
  global $kukarow;

  $query = "SELECT group_concat(tunnus) tunnukset
            FROM lasku
            WHERE laskunro = {$laskunro}
            AND yhtio      = '{$kukarow["yhtio"]}'
            AND tila       = 'L'
            AND alatila    = 'X'";
  $tilausnumerot = pupe_query($query);
  $tilausnumerot = mysql_fetch_assoc($tilausnumerot);
  $tilausnumerot = $tilausnumerot["tunnukset"];

  return $tilausnumerot;
}
