<?php

// verkkolasku_lv.php
//
// tarvitaan $kukarow ja $yhtiorow
//
// laskutetaan kaikki/valitut laskutusvalmiit tilaukset
// tämä skripti on tarkoitettu käytettäväksi silloin kun laskutus on kaatunut
// ja tilaukset on jäänyt laskutusvalmiiksi
// tämmöisissä tilauksissa on jo tehty laskutus.inc toimenpiteet
//
// lähetetään kaikki laskutetut tilaukset operaattorille tai tulostetaan ne paperille
//
// $laskutettavat   --> jos halutaan laskuttaa vain tietyt tilaukset niin silloin ne tulee muuttujassa
// $laskutakaikki   --> muutujan avulla voidaan ohittaa laskutusviikonpäivät
// $eiketjut        --> muuttujan avulla katotaan halutaanko laskuttaa vaan laskut joita *EI SAA* ketjuttaa
//
// jos ollaan saatu komentoriviltä parametrejä
// $yhtio ja $kieli --> komentoriviltä pitää tulla parametreinä
// $eilinen         --> optional parametri on jollon ajetaan laskutus eiliselle päivälle
//
// $silent muuttujalla voidaan hiljentää kaikki outputti

// jos chn = 999 se tarkoittaa että lasku on laskutuskiellossa eli ei saa käsitellä täällä!!!!!

//$silent = '';

// Tämä vaatii paljon muistia
ini_set("memory_limit", "5G");

// Kutsutaanko CLI:stä, jos setataan force_web saadaan aina "ei cli" -versio
$force_web = (isset($force_web) and $force_web === true);
$php_cli = ((php_sapi_name() == 'cli' or isset($editil_cli)) and $force_web === false);

if (!function_exists("laskunkieli")) {
  function laskunkieli($liitostunnus, $kieli) {
    global $kukarow, $yhtiorow;

    $asiakas_apu_query = "SELECT *
                          FROM asiakas
                          WHERE yhtio = '$kukarow[yhtio]'
                          AND tunnus  = '$liitostunnus'";
    $asiakas_apu_res = pupe_query($asiakas_apu_query);
    $asiakas_apu_row = mysql_fetch_assoc($asiakas_apu_res);

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

    return $laskun_kieli;
  }
}

if (!function_exists("vlas_dateconv")) {
  function vlas_dateconv($date) {
    //kääntää mysqln vvvv-kk-mm muodon muotoon vvvvkkmm
    return substr($date, 0, 4).substr($date, 5, 2).substr($date, 8, 2);
  }
}

if ($php_cli) {

  if (empty($argv[1])) {
    echo "Anna yhtiö!!!\n";
    exit(1);
  }

  // otetaan includepath aina rootista
  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(__FILE__)).PATH_SEPARATOR."/usr/share/pear");
  error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
  ini_set("display_errors", 0);

  // otetaan tietokanta connect
  require "inc/connect.inc";
  require "inc/functions.inc";

  // Logitetaan ajo
  cron_log();

  $_yhtio   = pupesoft_cleanstring($argv[1]);
  $yhtiorow = hae_yhtion_parametrit($_yhtio);

  // Kukarow setataan esim editilaus_in.inc:ssä
  if (!isset($kukarow)) {
    $kukarow = hae_kukarow('admin', $yhtiorow['yhtio']);

    // Komentoriviltä ku ajetaan, niin ei haluta posteja admin-käyttäjälle
    $kukarow["eposti"] = "";
  }

  if (!is_array($kukarow)) {
    exit(1);
  }

  if (isset($argv[2])) {
    $kieli = pupesoft_cleanstring($argv[2]);
  }

  // Pupeasennuksen root
  $pupe_root_polku = dirname(dirname(__FILE__));

  $laskkk   = "";
  $laskpp   = "";
  $laskvv   = "";
  $eilinen  = "";
  $eiketjut = "";

  // jos komentorivin kolmas arg on "eilinen" niin edelliselle laskutus päivälle, ohitetaan laskutusviikonpäivät
  if ($argv[3] == "eilinen") {
    $laskkk  = date("m", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
    $laskpp  = date("d", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
    $laskvv  = date("Y", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
  }

  // jos komentorivin kolmas arg on "eilinen" niin edelliselle laskutus päivälle
  if ($argv[3] == "eilinen_eikaikki") {
    $laskkk  = date("m", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
    $laskpp  = date("d", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
    $laskvv  = date("Y", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
    $eilinen = "eilinen_eikaikki";
  }

  // jos komentorivin kolmas arg on "eiketjut"
  if ($argv[3] == "eiketjut") {
    $eiketjut = "KYLLA";
  }

  // jos komentorivin kolmas arg on "kaikki"
  if ($argv[3] == "kaikki") {
    $laskutakaikki = "ON";
  }

  // jos kuukausilaskutus on päällä (cron.monthly), niin ei välttämättä haluta ajaa päivälaskutusta
  // kukauden vikana päivänä, koska silloin asiakkaalle saattaa mennä kaksi laskua vikana päivänä jos
  // laskutusviikonpäivät osuu sillai kivasti
  if ($argv[3] == "skippaa_kuukauden_vikapaiva" and date("d") == date("t")) {
    echo "HUOM: Päivälaskutusta ei ajeta kuukauden vikana päivänä!<br>\n";
    exit;
  }

  $tee = "TARKISTA";
}
elseif (strpos($_SERVER['SCRIPT_NAME'], "verkkolasku_lv.php") !== FALSE) {

  if (isset($_POST["tee"])) {
    if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto = 1;
    if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
  }

  require "../inc/parametrit.inc";
}

// Timeout in 5h
ini_set("mysql.connect_timeout", 18000);
ini_set("max_execution_time", 18000);

if ($kukarow["kuka"] != "admin") {
  die("Tätä ohjelmaa ei saa ajaa kuin admin");
}

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("$pupe_root_polku/dataout/".basename($filenimi));
  exit;
}
else {
  //Nollataan muuttujat
  $tulostettavat       = array();
  $tulostettavat_email = array();
  $tulos_ulos          = "";

  $verkkolaskuputkeen_pupevoice = array();
  $verkkolaskuputkeen_finvoice  = array();
  $verkkolaskuputkeen_suora     = array();
  $verkkolaskuputkeen_elmaedi   = array();
  $verkkolaskuputkeen_apix      = array();

  if (!isset($silent)) {
    $silent = "";
  }
  if (!isset($tee)) {
    $tee = "";
  }
  if (!isset($kieli)) {
    $kieli = "";
  }

  if ($silent == "") {
    $tulos_ulos .= "<font class='head'>".t("Laskutusajo")."</font><hr>\n";
  }

  if ($tee == 'TARKISTA') {

    $poikkeava_pvm = "";

    //syötetty päivämäärä
    if ($laskkk != '' or $laskpp != '' or $laskvv != '') {

      // Korjataan vuosilukua
      if ($laskvv < 1000) $laskvv += 2000;

      // Etunollat mukaan
      $laskkk = sprintf('%02d', $laskkk);
      $laskpp = sprintf('%02d', $laskpp);

      // Katotaan ensin, että se on ollenkaan validi
      if (checkdate($laskkk, $laskpp, $laskvv)) {

        //vertaillaan tilikauteen
        list($vv1, $kk1, $pp1) = explode("-", $yhtiorow["myyntireskontrakausi_alku"]);
        list($vv2, $kk2, $pp2) = explode("-", $yhtiorow["myyntireskontrakausi_loppu"]);

        $tilialku  = (int) date('Ymd', mktime(0, 0, 0, $kk1, $pp1, $vv1));
        $tililoppu = (int) date('Ymd', mktime(0, 0, 0, $kk2, $pp2, $vv2));
        $syotetty  = (int) date('Ymd', mktime(0, 0, 0, $laskkk, $laskpp, $laskvv));
        $tanaan    = (int) date('Ymd');

        if ($syotetty < $tilialku or $syotetty > $tililoppu) {
          $tulos_ulos .= "<br>\n".t("VIRHE: Syötetty päivämäärä ei sisälly kuluvaan tilikauteen!")."<br>\n<br>\n";
          $tee = "";
        }
        else {

          if ($syotetty > $tanaan and $yhtiorow['laskutus_tulevaisuuteen'] != 'S') {
            //tulevaisuudessa ei voida laskuttaa
            $tulos_ulos .= "<br>\n".t("VIRHE: Syötetty päivämäärä on tulevaisuudessa, ei voida laskuttaa!")."<br>\n<br>\n";
            $tee = "";
          }
          else {
            //homma on ok
            $poikkeava_pvm = $syotetty;

            //ohitetaan myös laskutusviikonpäivät jos poikkeava päivämäärä on syötetty
            if (!isset($eilinen) or $eilinen != "eilinen_eikaikki") {
              $laskutakaikki = "ON";
            }

            $tee = "LASKUTA";
          }
        }
      }
      else {
        $tulos_ulos .= "<br>\n".t("VIRHE: Syötetty päivämäärä on virheellinen, tarkista se!")."<br>\n<br>\n";
        $tee = "";
      }
    }
    else {
      //poikkeavaa päivämäärää ei ole, eli laskutetaan
      $tee = "LASKUTA";
    }
  }

  if ($tee == "LASKUTA") {

    if ($yhtiorow["koontilaskut_yhdistetaan"] == 'T' or $yhtiorow['koontilaskut_yhdistetaan'] == 'V') {
      $ketjutus_group = ", lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp, lasku.toim_maa ";
    }
    else {
      $ketjutus_group = "";
    }

    //Tiedostojen polut ja nimet
    //keksitään uudelle failille joku varmasti uniikki nimi:
    $nimixml = "$pupe_root_polku/dataout/laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(), true)).".xml";

    //  Itellan iPost vaatii siirtoon vähän oman nimen..
    if ($yhtiorow["verkkolasku_lah"] == "iPost") {
      $nimifinvoice = "$pupe_root_polku/dataout/TRANSFER_IPOST-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(), true))."_finvoice.xml";
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

    // lock tables
    $query = "LOCK TABLES
              asiakas READ,
              asiakasalennus READ,
              asiakasalennus as asale1 READ,
              asiakasalennus as asale2 READ,
              asiakasalennus as asale3 READ,
              asiakasalennus as asale4 READ,
              asiakashinta READ,
              asiakashinta as ashin1 READ,
              asiakashinta as ashin2 READ,
              asiakaskommentti READ,
              avainsana as a READ,
              avainsana as avainsana_kieli READ,
              avainsana as b READ,
              avainsana READ,
              directdebit READ,
              dynaaminen_puu AS node READ,
              dynaaminen_puu AS parent READ,
              etaisyydet READ,
              factoring READ,
              inventointilistarivi READ,
              hinnasto READ,
              kalenteri WRITE,
              kassalipas READ,
              kirjoittimet READ,
              kuka WRITE,
              lasku AS lx_otsikko WRITE,
              lasku AS ux_otsikko WRITE,
              lasku WRITE,
              laskun_lisatiedot WRITE,
              liitetiedostot READ,
              maat READ,
              maksuehto READ,
              pakkaus READ,
              pankkiyhteystiedot READ,
              panttitili WRITE,
              perusalennus READ,
              puun_alkio READ,
              rahtikirjat READ,
              rahtimaksut READ,
              sanakirja WRITE,
              sarjanumeroseuranta WRITE,
              sarjanumeroseuranta_arvomuutos READ,
              tapahtuma WRITE,
              tilausrivi as t READ,
              tilausrivi as t2 WRITE,
              tilausrivi as t3 READ,
              tilausrivi WRITE,
              tilausrivin_lisatiedot as tl2 WRITE,
              tilausrivin_lisatiedot as tlt2 WRITE,
              tilausrivin_lisatiedot as tlt3 WRITE,
              tilausrivin_lisatiedot t_lisa READ,
              tilausrivin_lisatiedot WRITE,
              tili READ,
              tiliointi WRITE,
              kustannuspaikka as kustp READ,
              kustannuspaikka as kohde READ,
              kustannuspaikka as projekti READ,
              toimitustapa READ,
              tullinimike READ,
              tuote READ,
              tuotepaikat WRITE,
              tuoteperhe READ,
              tuotteen_alv READ,
              tuotteen_avainsanat READ,
              tuotteen_toimittajat READ,
              tyomaarays READ,
              varastopaikat READ,
              yhteyshenkilo as kk READ,
              yhteyshenkilo as kt READ,
              yhtio READ,
              yhtion_parametrit READ,
              yhtion_toimipaikat READ,
              tilausrivin_lisatiedot AS tl READ,
              valuu READ,
              valuu_historia READ,
              varastopaikat AS v_lahdevarasto READ,
              varastopaikat AS v_kohdevarasto READ,
              korvaavat_kiellot READ,
              oikeu READ,
              toimi READ,
              yhtion_toimipaikat_parametrit READ,
              varaston_hyllypaikat READ";
    $locre = pupe_query($query);

    //Haetaan tarvittavat funktiot aineistojen tekoa varten
    require "verkkolasku_elmaedi.inc";

    if ($yhtiorow["finvoice_versio"] == "2") {
      require "verkkolasku_finvoice_201.inc";
    }
    else {
      require "verkkolasku_finvoice.inc";
    }

    require "verkkolasku_pupevoice.inc";

    // haetaan kaikki tilaukset jotka on toimitettu ja kuuluu laskuttaa tänään (tätä resulttia käytetään alhaalla lisää)
    $lasklisa = "";
    $lasklisa_eikateiset = "";

    // tarkistetaan tässä tuleeko laskutusviikonpäivät ohittaa
    // ohitetaan jos ruksi on ruksattu tai poikkeava laskutuspäivämäärä on syötetty
    if (!isset($laskutakaikki) or $laskutakaikki == "") {

      // Mikä viikonpäivä tänään on 1-7.. 1=sunnuntai, 2=maanantai, jne...
      if (isset($eilinen) and $eilinen == "eilinen_eikaikki") {
        $today = date("w", mktime(0, 0, 0, $laskkk, $laskpp, $laskvv)) +1;
        $vkopva_curdate = "'$laskvv-$laskkk-$laskpp'";
      }
      else {
        $today = date("w") + 1;
        $vkopva_curdate = "curdate()";
      }

      // Kuukauden eka päivä
      $eka_pv = laskutuspaiva("eka");

      // Kuukauden keskimmäinen päivä
      $keski_pv = laskutuspaiva("keski");

      // Kuukauden viimeinen päivä
      $vika_pv = laskutuspaiva("vika");

      $lasklisa .= " and (lasku.laskutusvkopv = 0 or
                 (lasku.laskutusvkopv = $today) or
                 (lasku.laskutusvkopv = -1 and {$vkopva_curdate} = '$vika_pv') or
                 (lasku.laskutusvkopv = -2 and {$vkopva_curdate} = '$eka_pv') or
                 (lasku.laskutusvkopv = -3 and {$vkopva_curdate} = '$keski_pv') or
                 (lasku.laskutusvkopv = -4 and {$vkopva_curdate} in ('$keski_pv','$vika_pv')) or
                 (lasku.laskutusvkopv = -5 and {$vkopva_curdate} in ('$eka_pv','$keski_pv'))) ";
    }

    // katotaan halutaanko laskuttaa vaan laskut joita *EI SAA* ketjuttaa
    if (isset($eiketjut) and $eiketjut == "KYLLA") {
      $lasklisa .= " and lasku.ketjutus != '' ";
    }

    if (isset($laskutettavat) and $laskutettavat != "") {
      // Laskutetaan vain tietyt tilausket
      $lasklisa .= " and lasku.tunnus in ($laskutettavat) ";
    }
    elseif (php_sapi_name() == 'cli') {
      // Komentoriviltä ei ikinä laskuteta käteismyyntejä ($php_cli ei kelpaa, koska $editil_cli virittää sen myös)
      $lasklisa_eikateiset = " JOIN maksuehto ON (lasku.yhtio=maksuehto.yhtio and lasku.maksuehto=maksuehto.tunnus and maksuehto.kateinen='')";
    }

    $tulos_ulos_maksusoppari = "";
    $tulos_ulos_sarjanumerot = "";

    // alustetaan muuttujia
    $laskutus_esto_saldot = array();

    $query_ale_lisa = generoi_alekentta('M');

    //haetaan kaikki laskutettavat tilaukset
    $query = "SELECT lasku.*
              FROM lasku
              WHERE lasku.yhtio  = '$kukarow[yhtio]'
              and lasku.tila     = 'L'
              and lasku.alatila  = 'V'
              and lasku.viite    = ''
              and lasku.tapvm   != '0000-00-00'
              $lasklisa";
    $res = pupe_query($query);

    if (mysql_num_rows($res) > 0) {

      //ketjutetaan laskut...
      $ketjut = array();

      //haetaan kaikki laskutusvalmiit tilaukset jotka saa ketjuttaa, viite pitää olla tyhjää muuten ei laskuteta
      $query  = "SELECT
                 if (lasku.ketjutus = '', '', if (lasku.vanhatunnus > 0, lasku.vanhatunnus, lasku.tunnus)) ketjutuskentta,
                 if ((((asiakas.koontilaskut_yhdistetaan = '' and ('{$yhtiorow['koontilaskut_yhdistetaan']}' = 'U' or '{$yhtiorow['koontilaskut_yhdistetaan']}' = 'V')) or asiakas.koontilaskut_yhdistetaan = 'U') and lasku.tilaustyyppi in ('R','U')), 1, 0) reklamaatiot_lasku,
                 lasku.ytunnus, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, lasku.maksuehto, lasku.erpcm, lasku.vienti, lasku.kolmikantakauppa,
                 lasku.lisattava_era, lasku.vahennettava_era, lasku.maa_maara, lasku.kuljetusmuoto, lasku.kauppatapahtuman_luonne,
                 lasku.sisamaan_kuljetus, lasku.aktiivinen_kuljetus, lasku.kontti, lasku.aktiivinen_kuljetus_kansallisuus,
                 lasku.sisamaan_kuljetusmuoto, lasku.poistumistoimipaikka, lasku.poistumistoimipaikka_koodi, lasku.chn, lasku.maa, lasku.valkoodi,
                 count(lasku.tunnus) yht,
                 group_concat(lasku.tunnus) tunnukset
                 FROM lasku
                 LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio and laskun_lisatiedot.otunnus = lasku.tunnus)
                 LEFT JOIN asiakas ON asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus
                 WHERE lasku.yhtio = '$kukarow[yhtio]'
                 and lasku.alatila = 'V'
                 and lasku.tila    = 'L'
                 and lasku.viite   = ''
                 and lasku.tapvm  != '0000-00-00'
                 $lasklisa
                 GROUP BY ketjutuskentta, reklamaatiot_lasku, lasku.ytunnus, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, lasku.maksuehto, lasku.erpcm, lasku.vienti, lasku.kolmikantakauppa,
                 lasku.lisattava_era, lasku.vahennettava_era, lasku.maa_maara, lasku.kuljetusmuoto, lasku.kauppatapahtuman_luonne,
                 lasku.sisamaan_kuljetus, lasku.aktiivinen_kuljetus, lasku.kontti, lasku.aktiivinen_kuljetus_kansallisuus,
                 lasku.sisamaan_kuljetusmuoto, lasku.poistumistoimipaikka, lasku.poistumistoimipaikka_koodi, lasku.chn, lasku.maa, lasku.valkoodi, lasku.laskutyyppi,
                 laskun_lisatiedot.laskutus_nimi, laskun_lisatiedot.laskutus_nimitark, laskun_lisatiedot.laskutus_osoite, laskun_lisatiedot.laskutus_postino, laskun_lisatiedot.laskutus_postitp, laskun_lisatiedot.laskutus_maa
                 $ketjutus_group
                 ORDER BY ketjutuskentta, reklamaatiot_lasku DESC, lasku.ytunnus, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, lasku.maksuehto, lasku.erpcm, lasku.vienti, lasku.kolmikantakauppa,
                 lasku.lisattava_era, lasku.vahennettava_era, lasku.maa_maara, lasku.kuljetusmuoto, lasku.kauppatapahtuman_luonne,
                 lasku.sisamaan_kuljetus, lasku.aktiivinen_kuljetus, lasku.kontti, lasku.aktiivinen_kuljetus_kansallisuus,
                 lasku.sisamaan_kuljetusmuoto, lasku.poistumistoimipaikka, lasku.poistumistoimipaikka_koodi, lasku.chn, lasku.maa, lasku.valkoodi,
                 laskun_lisatiedot.laskutus_nimi, laskun_lisatiedot.laskutus_nimitark, laskun_lisatiedot.laskutus_osoite, laskun_lisatiedot.laskutus_postino, laskun_lisatiedot.laskutus_postitp, laskun_lisatiedot.laskutus_maa";
      $result = pupe_query($query);

      if ($silent == "") {
        $tulos_ulos .= "<br><br>\n".t("Laskujen ketjutus:")."<br>\n<table>";
      }

      while ($row = mysql_fetch_assoc($result)) {

        if ($silent == "") {
          $tulos_ulos .= "<tr><td>$row[ytunnus]</td><td>$row[nimi]<br>$row[nimitark]</td><td>$row[osoite]</td><td>$row[postino]</td>\n
                  <td>$row[postitp]</td><td>$row[maksuehto]</td><td>$row[erpcm]</td><td>Ketjutettu $row[yht] kpl</td></tr>\n";
        }

        $ketjut[]  = $row["tunnukset"];
      }

      if ($silent == "") {
        $tulos_ulos .= "</table><br>\n";
      }

      //laskuri
      $lask       = 0;
      $edilask    = 0;

      // jos on jotain laskutettavaa ...
      if (count($ketjut) != 0) {

        //Timestamppi EDI-failiin alkuu ja loppuun
        $timestamppi = gmdate("YmdHis");

        //nyt meillä on $ketjut arrayssa kaikki yhteenkuuluvat tunnukset suoraan mysql:n IN-syntaksin muodossa!! jee!!
        foreach ($ketjut as $tunnukset) {

          // generoidaan laskulle viite ja lasno
          $query = "SELECT max(laskunro) laskunro FROM lasku WHERE yhtio = '$kukarow[yhtio]' and tila = 'U'";
          $result= pupe_query($query);
          $lrow  = mysql_fetch_assoc($result);

          $lasno = $lrow["laskunro"] + 1;

          if ($lasno < 100) {
            $lasno = 100;
          }

          // Tutkitaan onko ketju factorinkia
          // Ketju on groupattu maksuehdon mukaan, joten meillä ei ole kun yhden maksuehdon laskuja
          $query = "SELECT DISTINCT factoring.sopimusnumero, factoring.factoringyhtio, factoring.viitetyyppi
                    FROM lasku
                    JOIN maksuehto ON (maksuehto.yhtio = lasku.yhtio
                      and maksuehto.tunnus   = lasku.maksuehto
                      and maksuehto.factoring_id is not null)
                    JOIN factoring ON (factoring.yhtio = maksuehto.yhtio
                      and factoring.tunnus   = maksuehto.factoring_id
                      and factoring.valkoodi = lasku.valkoodi)
                    WHERE lasku.yhtio        = '$kukarow[yhtio]'
                    and lasku.tunnus         in ($tunnukset)";
          $fres = pupe_query($query);
          $frow = mysql_fetch_assoc($fres);

          // Nordean viitenumero rakentuu hieman eri lailla ku normaalisti
          if ($frow["sopimusnumero"] > 0 and $frow["factoringyhtio"] == 'NORDEA' and $frow["viitetyyppi"] == '') {
            $viite = $frow["sopimusnumero"]."0".sprintf('%08d', $lasno);
          }
          elseif ($frow["sopimusnumero"] > 0 and $frow["factoringyhtio"] == 'COLLECTOR' and $frow["viitetyyppi"] == '') {
            $viite = $frow["sopimusnumero"]."0".sprintf('%08d', $lasno);
          }
          elseif ($frow["sopimusnumero"] > 0 and $frow["factoringyhtio"] == 'OKO' and $frow["viitetyyppi"] == '') {
            $viite = $frow["sopimusnumero"]."001".sprintf('%09d', $lasno);
          }
          elseif ($frow["sopimusnumero"] > 0 and $frow["factoringyhtio"] == 'SAMPO' and $frow["viitetyyppi"] == '') {
            $viite = $frow["sopimusnumero"]."1".sprintf('%09d', $lasno);
          }
          elseif ($frow["sopimusnumero"] > 0 and $frow["factoringyhtio"] == 'AKTIA' and $frow["viitetyyppi"] == '') {
            $viite = str_pad($frow["sopimusnumero"], 6, '0', STR_PAD_RIGHT).sprintf("%010d", $lasno);
          }
          else {
            $viite = $lasno;
          }

          // Tutkitaan käytetäänkö maksuehdon pankkiyhteystietoja
          $query  = "SELECT pankkiyhteystiedot.viite
                     FROM lasku
                     JOIN maksuehto ON lasku.yhtio=maksuehto.yhtio and lasku.maksuehto=maksuehto.tunnus
                     JOIN pankkiyhteystiedot ON maksuehto.yhtio=pankkiyhteystiedot.yhtio and maksuehto.pankkiyhteystiedot = pankkiyhteystiedot.tunnus and pankkiyhteystiedot.viite = 'SE'
                     WHERE lasku.yhtio = '$kukarow[yhtio]'
                     and lasku.tunnus  in ($tunnukset)";
          $pankres = pupe_query($query);

          $seviite = "";

          if (mysql_num_rows($pankres) > 0) {
            $seviite = "SE";
          }

          //  Onko käsinsyötetty viite?
          $query = "SELECT kasinsyotetty_viite
                    FROM laskun_lisatiedot
                    WHERE yhtio              = '$kukarow[yhtio]'
                    AND otunnus              IN ($tunnukset)
                    AND kasinsyotetty_viite != ''";
          $tarkres = pupe_query($query);

          if (mysql_num_rows($tarkres) == 1) {
            $tarkrow = mysql_fetch_assoc($tarkres) or pupe_error($tarkres);
            $viite = $tarkrow["kasinsyotetty_viite"];

            if ($seviite != 'SE') {
              //  Jos viitenumero on väärin mennään oletuksilla!
              if (substr($viite, 0, 2) != "RF" and tarkista_viite($viite) === FALSE) {
                $viite = $lasno;
                $tulos_ulos .= "<font class='message'><br>\n".t("HUOM: laskun '%s' käsinsyotetty viitenumero '%s' on väärin! Laskulle annettii uusi viite '%s'", "", $lasno, $tarkrow["kasinsyotetty_viite"], $viite)."!</font><br>\n<br>\n";
                require 'inc/generoiviite.inc';
              }
              elseif (substr($viite, 0, 2) == "RF" and tarkista_rfviite($viite) === FALSE) {
                $viite = $lasno;
                $tulos_ulos .= "<font class='message'><br>\n".t("HUOM: laskun '%s' käsinsyotetty RF-viitenumero '%s' on väärin! Laskulle annettii uusi viite '%s'", "", $lasno, $tarkrow["kasinsyotetty_viite"], $viite)."!</font><br>\n<br>\n";
                require 'inc/generoiviite.inc';
              }
            }
          }
          else {
            if ($seviite == 'SE') {
              require 'inc/generoiviite_se.inc';
            }
            else {
              require 'inc/generoiviite.inc';
            }
          }

          // päivitetään ketjuun kuuluville laskuille sama laskunumero ja viite..
          $query  = "UPDATE lasku SET
                     laskunro    = '$lasno',
                     viite       = '$viite'
                     WHERE yhtio = '$kukarow[yhtio]'
                     AND tunnus  IN ($tunnukset)";
          $result = pupe_query($query);

          // tehdään U lasku ja tiliöinnit
          // tarvitaan $tunnukset mysql muodossa

          require "teeulasku.inc";

          // saadaan takaisin $laskurow
          $lasrow = $laskurow;

          // Luodaan tullausnumero jos sellainen tarvitaan
          // Jos on esim puhtaasti hyvitystä niin ei generoida tullausnumeroa
          if ($lasrow["vienti"] == 'K' and $lasrow["sisainen"] == "") {
            $query = "SELECT tilausrivi.yhtio
                      FROM tilausrivi
                      JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.ei_saldoa = '')
                      WHERE tilausrivi.uusiotunnus = '$lasrow[tunnus]'
                      and tilausrivi.kpl           > 0
                      and tilausrivi.yhtio         = '$kukarow[yhtio]'
                      and tilausrivi.tyyppi        = 'L'
                      and tilausrivi.var           not in ('P','J','O','S')";
            $cresult = pupe_query($query);

            $hyvitys = "";
            if (mysql_num_rows($cresult) == 0) {
              //laskulla on vain hyvitysrivejä, tai ei yhtään riviä --> ei tullata!
              $hyvitys = "ON";
            }
            else {
              $hyvitys = "EI";
            }

            $tullausnumero = '';

            if ($hyvitys == 'EI') {
              //generoidaan tullausnumero.
              $p = date('d');
              $k = date('m');
              $v = date('Y');
              $pvm = $v."-".$k."-".$p;

              $query = "SELECT count(*)+1 tullausnumero
                        FROM lasku use index (yhtio_tila_tapvm)
                        WHERE vienti       = 'K'
                        and tila           = 'U'
                        and alatila        = 'X'
                        and tullausnumero != ''
                        and tapvm          = '$pvm'
                        and yhtio          = '$kukarow[yhtio]'";
              $result= pupe_query($query);
              $lrow  = mysql_fetch_assoc($result);

              $pvanumero = date('z')+1;

              //tullausnumero muodossa Vuosi-Tullikamari-Päivännumero-Tullipääte-Juoksevanumeroperpäivä
              $tullausnumero = date('y') . "-". $yhtiorow["tullikamari"] ."-" . sprintf('%03d', $pvanumero) . "-" . $yhtiorow["tullipaate"] . "-" . sprintf('%03d', $lrow["tullausnumero"]);

              // päivitetään ketjuun kuuluville laskuille sama laskunumero ja viite..
              $query  = "UPDATE lasku set tullausnumero='$tullausnumero' WHERE vienti='K' and tila='U' and yhtio='$kukarow[yhtio]' and tunnus='$lasrow[tunnus]'";
              $result = pupe_query($query);

              $lasrow["tullausnumero"] = $tullausnumero;
            }
          }

          if ($silent == "") {
            $tulos_ulos .= $tulos_ulos_ulasku;
            $tulos_ulos .= $tulos_ulos_tiliointi;
          }

          // Haetaan maksuehdon tiedot
          $query  = "SELECT pankkiyhteystiedot.*, maksuehto.*
                     FROM maksuehto
                     LEFT JOIN pankkiyhteystiedot on (pankkiyhteystiedot.yhtio=maksuehto.yhtio and pankkiyhteystiedot.tunnus=maksuehto.pankkiyhteystiedot)
                     WHERE maksuehto.yhtio='$kukarow[yhtio]' and maksuehto.tunnus='$lasrow[maksuehto]'";
          $result = pupe_query($query);

          if (mysql_num_rows($result) == 0) {
            $masrow = array();

            if ($lasrow["erpcm"] == "0000-00-00") {
              $tulos_ulos .= "<font class='message'><br>\n".t("Maksuehtoa")." $lasrow[maksuehto] ".t("ei löydy!")." Tunnus $lasrow[tunnus] ".t("Laskunumero")." $lasrow[laskunro] ".t("epäonnistui pahasti")."!</font><br>\n<br>\n";
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
            $pankkitiedot["pankkinimi1"]  = $frow["pankkinimi1"];
            $pankkitiedot["pankkitili1"]  = $frow["pankkitili1"];
            $pankkitiedot["pankkiiban1"]  = $frow["pankkiiban1"];
            $pankkitiedot["pankkiswift1"] = $frow["pankkiswift1"];
            $pankkitiedot["pankkinimi2"]  = $frow["pankkinimi2"];
            $pankkitiedot["pankkitili2"]  = $frow["pankkitili2"];
            $pankkitiedot["pankkiiban2"]  = $frow["pankkiiban2"];
            $pankkitiedot["pankkiswift2"] = $frow["pankkiswift2"];
            $pankkitiedot["pankkinimi3"]  = "";
            $pankkitiedot["pankkitili3"]  = "";
            $pankkitiedot["pankkiiban3"]  = "";
            $pankkitiedot["pankkiswift3"] = "";

          }
          elseif ($masrow["pankkinimi1"] != "") {
            $pankkitiedot["pankkinimi1"]  = $masrow["pankkinimi1"];
            $pankkitiedot["pankkitili1"]  = $masrow["pankkitili1"];
            $pankkitiedot["pankkiiban1"]  = $masrow["pankkiiban1"];
            $pankkitiedot["pankkiswift1"] = $masrow["pankkiswift1"];
            $pankkitiedot["pankkinimi2"]  = $masrow["pankkinimi2"];
            $pankkitiedot["pankkitili2"]  = $masrow["pankkitili2"];
            $pankkitiedot["pankkiiban2"]  = $masrow["pankkiiban2"];
            $pankkitiedot["pankkiswift2"] = $masrow["pankkiswift2"];
            $pankkitiedot["pankkinimi3"]  = $masrow["pankkinimi3"];
            $pankkitiedot["pankkitili3"]  = $masrow["pankkitili3"];
            $pankkitiedot["pankkiiban3"]  = $masrow["pankkiiban3"];
            $pankkitiedot["pankkiswift3"] = $masrow["pankkiswift3"];
          }
          else {
            $pankkitiedot["pankkinimi1"]  = $yhtiorow["pankkinimi1"];
            $pankkitiedot["pankkitili1"]  = $yhtiorow["pankkitili1"];
            $pankkitiedot["pankkiiban1"]  = $yhtiorow["pankkiiban1"];
            $pankkitiedot["pankkiswift1"] = $yhtiorow["pankkiswift1"];
            $pankkitiedot["pankkinimi2"]  = $yhtiorow["pankkinimi2"];
            $pankkitiedot["pankkitili2"]  = $yhtiorow["pankkitili2"];
            $pankkitiedot["pankkiiban2"]  = $yhtiorow["pankkiiban2"];
            $pankkitiedot["pankkiswift2"] = $yhtiorow["pankkiswift2"];
            $pankkitiedot["pankkinimi3"]  = $yhtiorow["pankkinimi3"];
            $pankkitiedot["pankkitili3"]  = $yhtiorow["pankkitili3"];
            $pankkitiedot["pankkiiban3"]  = $yhtiorow["pankkiiban3"];
            $pankkitiedot["pankkiswift3"] = $yhtiorow["pankkiswift3"];
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

          $laskun_kieli = laskunkieli($lasrow['liitostunnus'], $kieli);

          if (verkkolaskuputkeen($lasrow, $masrow)) {

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
                         WHERE yhtio = '$kukarow[yhtio]'
                         and otunnus in ($tunnukset)
                         and tyyppi  = 'L'
                         and var     not in ('P','J','O','S')
                         and alv     >= 600";
            $alvresult = pupe_query($alvquery);

            if (mysql_num_rows($alvresult) > 0) {
              $komm .= t_avainsana("KAANTALVVIESTI", $laskun_kieli, "", "", "", "selitetark");
            }

            if (trim($lasrow['tilausyhteyshenkilo']) != '') {
              $komm .= "\n".t("Tilaaja", $laskun_kieli).": ".$lasrow['tilausyhteyshenkilo'];
            }

            if (trim($lasrow['asiakkaan_tilausnumero']) != '') {
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
              $lasrow["kasumma"]   = $lasrow["kasumma_valuutassa"];
              $lasrow["summa"]     = $lasrow["summa_valuutassa"];
              $lasrow["arvo"]      = $lasrow["arvo_valuutassa"];
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
                      and otunnus         in ($tunnukset)
                      and toimitettuaika != '0000-00-00 00:00:00'
                      and tyyppi          = 'L'
                      and var             not in ('P','J','O','S')";
            $toimaikares = pupe_query($query);
            $toimaikarow = mysql_fetch_assoc($toimaikares);

            if ($toimaikarow["mint"] == "0000-00-00") {
              $toimaikarow["mint"] = date("Y-m-d");
            }
            if ($toimaikarow["maxt"] == "0000-00-00") {
              $toimaikarow["maxt"] = date("Y-m-d");
            }

            // Laskun kaikki tilaukset
            $lasrow['tilausnumerot'] = $tunnukset;

            //Kirjoitetaan failiin laskun otsikkotiedot
            if ($lasrow["chn"] == "111") {
              elmaedi_otsik($tootedi, $lasrow, $masrow, $tyyppi, $timestamppi, $toimaikarow);
            }
            elseif ($lasrow["chn"] == "112") {
              finvoice_otsik($tootsisainenfinvoice, $lasrow, $kieli, $pankkitiedot, $masrow, $myyrow, $tyyppi, $toimaikarow, $tulos_ulos, $silent);
            }
            elseif (in_array($yhtiorow["verkkolasku_lah"], array("iPost", "finvoice", "maventa", "trustpoint", "ppg", "apix", "sepa", "talenom", "arvato"))) {
              finvoice_otsik($tootfinvoice, $lasrow, $kieli, $pankkitiedot, $masrow, $myyrow, $tyyppi, $toimaikarow, $tulos_ulos, $silent);
            }
            else {
              pupevoice_otsik($tootxml, $lasrow, $laskun_kieli, $pankkitiedot, $masrow, $myyrow, $tyyppi, $toimaikarow);
            }

            // Tarvitaan rivien eri verokannat
            $alvquery = "SELECT distinct alv
                         FROM tilausrivi
                         WHERE yhtio = '$kukarow[yhtio]'
                         and otunnus in ($tunnukset)
                         and tyyppi  = 'L'
                         and var     not in ('P','J','O','S')
                         ORDER BY alv";
            $alvresult = pupe_query($alvquery);

            while ($alvrow1 = mysql_fetch_assoc($alvresult)) {

              if ($alvrow1["alv"] >= 500) {
                $aquery = "SELECT '0' alv,
                           round(sum(tilausrivi.rivihinta/if (lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1)),2) rivihinta,
                           round(sum(0),2) alvrivihinta
                           FROM tilausrivi
                           JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
                           WHERE tilausrivi.uusiotunnus = '$lasrow[tunnus]'
                           and tilausrivi.yhtio         = '$kukarow[yhtio]'
                           and tilausrivi.alv           = '$alvrow1[alv]'
                           and tilausrivi.tyyppi        = 'L'
                           and tilausrivi.var           not in ('P','J','O','S')
                           GROUP BY alv";
              }
              else {
                $aquery = "SELECT tilausrivi.alv,
                           round(sum(tilausrivi.rivihinta/if (lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1)),2) rivihinta,
                           round(sum((tilausrivi.rivihinta/if (lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1))*(tilausrivi.alv/100)),2) alvrivihinta
                           FROM tilausrivi
                           JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
                           WHERE tilausrivi.uusiotunnus = '$lasrow[tunnus]'
                           and tilausrivi.yhtio         = '$kukarow[yhtio]'
                           and tilausrivi.alv           = '$alvrow1[alv]'
                           and tilausrivi.tyyppi        = 'L'
                           and tilausrivi.var           not in ('P','J','O','S')
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
              elseif (in_array($yhtiorow["verkkolasku_lah"], array("iPost", "finvoice", "maventa", "trustpoint", "ppg", "apix", "sepa", "talenom", "arvato"))) {
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
            elseif (in_array($yhtiorow["verkkolasku_lah"], array("iPost", "finvoice", "maventa", "trustpoint", "ppg", "apix", "sepa", "talenom", "arvato"))) {
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
                      WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
                      and (tilausrivi.perheid = 0 or tilausrivi.perheid=tilausrivi.tunnus or tilausrivin_lisatiedot.ei_nayteta !='E' or tilausrivin_lisatiedot.ei_nayteta is null)
                      and tilausrivi.kpl     != 0
                      and tilausrivi.tyyppi   = 'L'
                      and tilausrivi.otunnus  in ($tunnukset)
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
                    $lisataa = 1;
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

              // Käännetty arvonlisäverovelvollisuus ja käytetyn tavaran myynti
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
              $tilrow['nimitys']   = pupesoft_invoicestring($tilrow['nimitys']);

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
                  $rivinumero = (int) substr(sprintf("%06s", $tilrow["tilaajanrivinro"]), -6);
                }
                else {
                  $rivinumero = (int) substr(sprintf("%06s", $tilrow["tunnus"]), -6);
                }

                elmaedi_rivi($tootedi, $tilrow, $rivinumero);
              }
              elseif ($lasrow["chn"] == "112") {
                finvoice_rivi($tootsisainenfinvoice, $tilrow, $lasrow, $vatamount, $laskutyyppi);
              }
              elseif (in_array($yhtiorow["verkkolasku_lah"], array("iPost", "finvoice", "maventa", "trustpoint", "ppg", "apix", "sepa", "talenom", "arvato"))) {
                finvoice_rivi($tootfinvoice, $tilrow, $lasrow, $vatamount, $laskutyyppi);
              }
              else {
                pupevoice_rivi($tootxml, $tilrow, $vatamount);
              }

              $rivilaskuri++;
            }

            //Lopetetaan lasku
            if ($lasrow["chn"] == "111") {
              elmaedi_lasku_loppu($tootedi, $lasrow);

              //Nämä menee verkkolaskuputkeen
              $verkkolaskuputkeen_elmaedi[$lasrow["laskunro"]] = $lasrow["nimi"];

              $edilask++;
            }
            elseif ($lasrow["chn"] == "112") {
              finvoice_lasku_loppu($tootsisainenfinvoice, $lasrow, $pankkitiedot, $masrow);

              //Nämä menee verkkolaskuputkeen
              $verkkolaskuputkeen_suora[$lasrow["laskunro"]] = $lasrow["nimi"];
            }
            elseif (in_array($yhtiorow["verkkolasku_lah"], array("iPost", "finvoice", "maventa", "trustpoint", "ppg", "apix", "sepa", "talenom", "arvato"))) {
              $liitteet  = hae_liitteet_verkkolaskuun($yhtiorow["verkkolasku_lah"], $laskutettavat);
              $liitteita = !empty($liitteet);

              finvoice_lasku_loppu($tootfinvoice, $lasrow, $pankkitiedot, $masrow, $liitteita);

              if ($yhtiorow["verkkolasku_lah"] == "apix") {
                //Nämä menee verkkolaskuputkeen
                $verkkolaskuputkeen_apix[$lasrow["laskunro"]] = $lasrow["nimi"];
              }
              else {
                //Nämä menee verkkolaskuputkeen
                $verkkolaskuputkeen_finvoice[$lasrow["laskunro"]] = $lasrow["nimi"];
              }
            }
            else {
              pupevoice_lasku_loppu($tootxml);

              //Nämä menee verkkolaskuputkeen
              $verkkolaskuputkeen_pupevoice[$lasrow["laskunro"]] = $lasrow["nimi"];
            }

            // Otetaan talteen jokainen laskunumero joka lähetetään jotta voidaan tulostaa paperilaskut
            $tulostettavat[] = $lasrow["tunnus"];
            $lask++;
          }
          elseif ($lasrow["sisainen"] != '') {
            if ($silent == "") $tulos_ulos .= "<br>\n".t("Tehtiin sisäinen lasku")."! $lasrow[laskunro] $lasrow[nimi]<br>\n";

            // Sisäisiä laskuja ei normaaalisti tuloseta paitsi jos meillä on valittu_tulostin
            if ($valittu_tulostin != '') {
              $tulostettavat[] = $lasrow["tunnus"];
              $lask++;
            }
          }
          elseif ($masrow["kateinen"] != '') {
            if ($silent == "") {
              $tulos_ulos .= "<br>\n".t("Käteislaskua ei lähetetty")."! $lasrow[laskunro] $lasrow[nimi]<br>\n";
            }

            // Käteislaskuja ei lähetetä ulos mutta ne halutaan kuitenkin tulostaa itse
            $tulostettavat[] = $lasrow["tunnus"];
            $lask++;
          }
          elseif ($lasrow["vienti"] != '' or $masrow["itsetulostus"] != '' or $lasrow["chn"] == "666" or $lasrow["chn"] == '667') {
            if ($silent == "" or $silent == "VIENTI") {
              if ($lasrow["chn"] == "666" and $lasrow["summa"] != 0) {
                $tulos_ulos .= "<br>\n".t("Tämä lasku lähetetään suoraan asiakkaan sähköpostiin")."! $lasrow[laskunro] $lasrow[nimi]<br>\n";
              }
              elseif ($lasrow["chn"] == "667") {
                $tulos_ulos .= "<br>\n".t("Tehtiin sisäinen lasku")."! $lasrow[laskunro] $lasrow[nimi]<br>\n";
              }
              else {
                $tulos_ulos .= "<br>\n".t("Tämä lasku tulostetaan omalle tulostimelle")."! $lasrow[laskunro] $lasrow[nimi]<br>\n";
              }
            }

            // halutaan lähettää lasku suoraan asiakkaalle sähköpostilla.. mutta ei nollalaskua
            if ($lasrow["chn"] == "666" and $lasrow["summa"] != 0) {
              $tulostettavat_email[] = $lasrow["tunnus"];
            }

            // Halutaan tulostaa itse
            $tulostettavat[] = $lasrow["tunnus"];
            $lask++;
          }
          elseif ($silent == "") {
            $tulos_ulos .= "\n".t("Nollasummaista laskua ei lähetetty")."! $lasrow[laskunro] $lasrow[nimi]<br>\n";
          }

          // päivitetään kaikki laskut lähetetyiksi...
          $tquery = "UPDATE lasku SET alatila='X' WHERE (tunnus in ($tunnukset) or tunnus='$lasrow[tunnus]') and yhtio='$kukarow[yhtio]'";
          $tresult = pupe_query($tquery);

        } // end foreach ketjut...

        if ($silent == "") {
          $tulos_ulos .= "<br><br>\n\n";
        }

        //Aineistojen lopputägit
        elmaedi_aineisto_loppu($tootedi, $timestamppi);
        pupevoice_aineisto_loppu($tootxml);
      }
    }
    else {
      $tulos_ulos .= "<br>\n".t("Yhtään laskutettavaa tilausta ei löytynyt")."!<br><br>\n";
    }

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

    // poistetaan lukot
    $query = "UNLOCK TABLES";
    $locre = pupe_query($query);

    // jos laskutettiin jotain
    if (isset($lask) and $lask > 0) {

      if ($silent == "" or $silent == "VIENTI") {
        $tulos_ulos .= t("Luotiin")." $lask ".t("laskua").".<br>\n";
      }

      //jos verkkotunnus löytyy niin
      if ($yhtiorow['verkkotunnus_lah'] != '' and file_exists(realpath($nimixml))) {

        if ($silent == "") {
          $tulos_ulos .= "<br><br>\n".t("FTP-siirto pupevoice:")."<br>\n";
        }

        //siirretaan laskutiedosto operaattorille
        $ftphost = (isset($verkkohost_lah) and trim($verkkohost_lah) != '') ? $verkkohost_lah : "ftp.verkkolasku.net";
        $ftpuser = $yhtiorow['verkkotunnus_lah'];
        $ftppass = $yhtiorow['verkkosala_lah'];
        $ftppath = (isset($verkkopath_lah) and trim($verkkopath_lah) != '') ? $verkkopath_lah : "out/einvoice/data/";
        $ftpfile = realpath($nimixml);
        $ftpfail = "{$pupe_root_polku}/dataout/pupevoice_error/";

        $verkkolasmail  = t("Pvm").": ".date("Y-m-d H:i:s")."\n\n";
        $verkkolasmail .= t("Aineiston laskut").":\n";

        foreach ($verkkolaskuputkeen_pupevoice as $lasnoputk => $nimiputk) {
          $verkkolasmail .= "$lasnoputk - $nimiputk\n";
        }

        $verkkolasmail .= "\n\n";
        $verkkolasmail .= t("Aineisto liitteenä")."!\n\n\n\n";

        $_params = array(
          "to" => $yhtiorow["talhal_email"],
          "subject" => t("Pupevoice-aineiston siirto Itellaan"),
          "ctype" => "text",
          "body" => $verkkolasmail,
          "attachements" => array(
            array(
              "filename" => $ftpfile,
            ),
          ),
        );

        pupesoft_sahkoposti($_params);

        require "inc/ftp-send.inc";

        if ($silent == "") {
          $tulos_ulos .= $tulos_ulos_ftp;
        }
      }
      elseif ($yhtiorow["verkkolasku_lah"] == "apix" and file_exists(realpath($nimifinvoice))) {

        // Splitataan file ja lähetetään laskut sopivissa osissa
        $apix_laskuarray = explode("<SOAP-ENV:Envelope", file_get_contents($nimifinvoice));
        $apix_laskumaara = count($apix_laskuarray);

        if ($apix_laskumaara > 0) {
          require_once "tilauskasittely/tulosta_lasku.inc";

          for ($a = 1; $a < $apix_laskumaara; $a++) {
            preg_match("/\<InvoiceNumber\>(.*?)\<\/InvoiceNumber\>/i", $apix_laskuarray[$a], $invoice_number);

            $apix_finvoice = "<SOAP-ENV:Envelope".$apix_laskuarray[$a];

            // Laitetaan lasku lähetysjonoon
            $tulos_ulos .= apix_queue($apix_finvoice, $invoice_number[1], $kieli, $liitteet);
          }
        }
      }
      elseif ($yhtiorow["verkkolasku_lah"] == "maventa" and file_exists(realpath($nimifinvoice))) {
        // Splitataan file ja lähetetään YKSI lasku kerrallaan
        $maventa_laskuarray = explode("<SOAP-ENV:Envelope", file_get_contents($nimifinvoice));
        $maventa_laskumaara = count($maventa_laskuarray);

        if ($maventa_laskumaara > 0) {
          require_once "tilauskasittely/tulosta_lasku.inc";

          for ($a = 1; $a < $maventa_laskumaara; $a++) {
            preg_match("/\<InvoiceNumber\>(.*?)\<\/InvoiceNumber\>/i", $maventa_laskuarray[$a], $invoice_number);

            $status = maventa_invoice_put_file(NULL, NULL, $invoice_number[1], "<SOAP-ENV:Envelope".$maventa_laskuarray[$a], $kieli);

            $tulos_ulos .= "Maventa-lasku $invoice_number[1]: $status<br>\n";
          }
        }
      }
      elseif ($yhtiorow["verkkolasku_lah"] == "trustpoint" and file_exists(realpath($nimifinvoice))) {
        // Siirretään lähetysjonoon
        rename($nimifinvoice, "{$pupe_root_polku}/dataout/trustpoint_error/".basename($nimifinvoice));
        $tulos_ulos .= t("Lasku siirretty lähetysjonoon");
      }
      elseif ($yhtiorow["verkkolasku_lah"] == "ppg" and file_exists(realpath($nimifinvoice))) {
        // Splitataan file ja lähetetään YKSI lasku kerrallaan
        $ppg_laskuarray = explode("<SOAP-ENV:Envelope", file_get_contents($nimifinvoice));
        $ppg_laskumaara = count($ppg_laskuarray);

        if ($ppg_laskumaara > 0) {
          require_once "tilauskasittely/tulosta_lasku.inc";

          for ($a = 1; $a < $ppg_laskumaara; $a++) {
            preg_match("/\<InvoiceNumber\>(.*?)\<\/InvoiceNumber\>/i", $ppg_laskuarray[$a], $invoice_number);

            $status = ppg_queue($invoice_number[1], "<SOAP-ENV:Envelope".$ppg_laskuarray[$a], $kieli);

            $tulos_ulos .= "PPG-lasku $invoice_number[1]: $status<br>\n";
          }
        }
      }
      elseif ($yhtiorow["verkkolasku_lah"] == "arvato" and file_exists(realpath($nimifinvoice))) {
        // Splitataan file ja lähetetään YKSI lasku kerrallaan
        $arvato_laskuarray = explode("<SOAP-ENV:Envelope", file_get_contents($nimifinvoice));
        $arvato_laskumaara = count($arvato_laskuarray);

        if ($arvato_laskumaara > 0) {
          require_once "tilauskasittely/tulosta_lasku.inc";

          for ($a = 1; $a < $arvato_laskumaara; $a++) {
            preg_match("/\<InvoiceNumber\>(.*?)\<\/InvoiceNumber\>/i", $arvato_laskuarray[$a], $invoice_number);

            $status = arvato_queue($invoice_number[1], "<SOAP-ENV:Envelope".$arvato_laskuarray[$a], $kieli);

            $tulos_ulos .= "Arvato-lasku $invoice_number[1]: $status<br>\n";
          }
        }
      }
      elseif ($yhtiorow["verkkolasku_lah"] == "talenom" and file_exists(realpath($nimifinvoice))) {
        // Splitataan file ja lähetetään YKSI lasku kerrallaan
        $talenom_laskuarray = explode("<SOAP-ENV:Envelope", file_get_contents($nimifinvoice));
        $talenom_laskumaara = count($talenom_laskuarray);

        if ($talenom_laskumaara > 0) {
          for ($a = 1; $a < $talenom_laskumaara; $a++) {
            preg_match("/\<InvoiceNumber\>(.*?)\<\/InvoiceNumber\>/i", $talenom_laskuarray[$a], $invoice_number);

            $status = talenom_queue($invoice_number[1], "<SOAP-ENV:Envelope".$talenom_laskuarray[$a], $kieli);

            $tulos_ulos .= "Talenom-lasku $invoice_number[1]: $status<br>\n";
          }
        }
      }
      elseif (finvoice_pankki() && $yhtiorow["verkkolasku_lah"] == "sepa" and file_exists(realpath($nimifinvoice))) {
        rename($nimifinvoice, "{$pupe_root_polku}/dataout/" . finvoice_pankki() . "_error/" . basename($nimifinvoice));

        $tulos_ulos .= "SEPA-lasku siirretty lähetysjonoon";
      }
      elseif ($yhtiorow["verkkolasku_lah"] == "trustpoint" and !file_exists(realpath($nimifinvoice))) {
        // Tämä näytetään vain kun laskutetaan käsin ja lasku ei mene automaattiseen verkkolaskuputkeen
        if (strpos($_SERVER['SCRIPT_NAME'], "valitse_laskutettavat_tilaukset.php") !== FALSE) {
          js_openFormInNewWindow();

          foreach ($tulostettavat as $lasku) {

            $query = "SELECT laskunro
                      FROM lasku
                      WHERE yhtio = '$kukarow[yhtio]'
                      and tunnus  = '$lasku'";
            $laresult = pupe_query($query);
            $laskurow = mysql_fetch_assoc($laresult);

            echo "<form id='finvoice_$lasku' name='finvoice_$lasku' method='post' action='{$palvelin2}tilauskasittely/uudelleenluo_laskuaineisto.php' class='multisubmit'>
                <input type='hidden' name='laskunumerot' value='$laskurow[laskunro]'>
                <input type='hidden' name='tee' value='NAYTATILAUS'>
                <input type='hidden' name='nayta_ja_tallenna' value='TRUE'>
                <input type='hidden' name='kaunisnimi' value='Finvoice_$laskurow[laskunro].xml'>
                <input type='submit' value='".t("Tallenna Finvoice").": $laskurow[laskunro]' onClick=\"js_openFormInNewWindow('finvoice_$lasku', 'samewindow'); return false;\"></form>";
          }
        }
      }
      elseif ($yhtiorow["verkkolasku_lah"] == "iPost" and file_exists(realpath($nimifinvoice))) {
        if ($silent == "" or $silent == "VIENTI") {
          $tulos_ulos .= "<br><br>\n".t("FTP-siirto iPost Finvoice:")."<br>\n";
        }

        //siirretaan laskutiedosto operaattorille
        $ftphost     = "ftp.itella.net";
        $ftpuser     = $yhtiorow['verkkotunnus_lah'];
        $ftppass     = $yhtiorow['verkkosala_lah'];
        $ftppath     = "out/finvoice/data/";
        $ftpfile     = realpath($nimifinvoice);
        $renameftpfile   = str_replace("TRANSFER_IPOST", "DELIVERED_IPOST", basename($nimifinvoice));
        $ftpfail     = "{$pupe_root_polku}/dataout/ipost_error/";

        // Tehdään maili, että siirretään laskut operaattorille
        $bound = uniqid(time()."_") ;

        $verkkolasmail  = t("Pvm").": ".date("Y-m-d H:i:s")."\n\n";
        $verkkolasmail .= t("Aineiston laskut").":\n";

        foreach ($verkkolaskuputkeen_finvoice as $lasnoputk => $nimiputk) {
          $verkkolasmail .= "$lasnoputk - $nimiputk\n";
        }

        $verkkolasmail .= "\n\n";
        $verkkolasmail .= t("Aineisto liitteenä")."!\n\n\n\n";

        $_params = array(
          "to" => $yhtiorow["talhal_email"],
          "subject" => t("iPost Finvoice-aineiston siirto Itellaan"),
          "ctype" => "text",
          "body" => $verkkolasmail,
          "attachements" => array(
            array(
              "filename" => $ftpfile,
            ),
          ),
        );

        pupesoft_sahkoposti($_params);

        require "inc/ftp-send.inc";

        if ($silent == "" or $silent == "VIENTI") {
          $tulos_ulos .= $tulos_ulos_ftp;
        }
      }
      elseif ($yhtiorow["verkkolasku_lah"] == "finvoice" and file_exists(realpath($nimifinvoice))) {
        if (isset($verkkolaskut_out)) {
          if (is_writable($verkkolaskut_out)) {
            copy(realpath($nimifinvoice), $verkkolaskut_out."/".basename($nimifinvoice));
          }
          else {
            $tulos_ulos .= "<br><br>\n".t("Tiedoston kopiointi epäonnistui")."!<br>\n";
          }
        }
      }

      if ($edilask > 0 and $edi_ftphost != '' and file_exists(realpath($nimiedi))) {
        if ($silent == "") {
          $tulos_ulos .= "<br><br>\n".t("FTP-siirto Elma EDI-inhouse:")."<br>\n";
        }

        //siirretaan laskutiedosto operaattorille, EDI-inhouse muoto
        $ftphost = $edi_ftphost;
        $ftpuser = $edi_ftpuser;
        $ftppass = $edi_ftppass;
        $ftppath = $edi_ftppath;
        $ftpfile = realpath($nimiedi);
        $ftpfail = "{$pupe_root_polku}/dataout/elmaedi_error/";

        $verkkolasmail  = t("Pvm").": ".date("Y-m-d H:i:s")."\n\n";
        $verkkolasmail .= t("Aineiston laskut").":\n";

        foreach ($verkkolaskuputkeen_elmaedi as $lasnoputk => $nimiputk) {
          $verkkolasmail .= "$lasnoputk - $nimiputk\n";
        }

        $verkkolasmail .= "\n\n";
        $verkkolasmail .= t("Aineisto liitteenä")."!\n\n\n\n";

        $_params = array(
          "to" => $yhtiorow["talhal_email"],
          "subject" => t("EDI-inhouse-aineiston siirto Itellaan"),
          "ctype" => "text",
          "body" => $verkkolasmail,
          "attachements" => array(
            array(
              "filename" => $ftpfile,
            ),
          ),
        );

        pupesoft_sahkoposti($_params);

        require "inc/ftp-send.inc";

        if ($silent == "") {
          $tulos_ulos .= $tulos_ulos_ftp;
        }
      }

      if (isset($sisainenfoinvoice_ftphost) and $sisainenfoinvoice_ftphost != '' and file_exists(realpath($nimisisainenfinvoice))) {
        if ($silent == "") {
          $tulos_ulos .= "<br><br>\n".t("FTP-siirto Pupesoft-Finvoice:")."<br>\n";
        }

        //siirretaan laskutiedosto operaattorille, SisäinenFinvoice muoto
        $ftphost = $sisainenfoinvoice_ftphost;
        $ftpuser = $sisainenfoinvoice_ftpuser;
        $ftppass = $sisainenfoinvoice_ftppass;
        $ftppath = $sisainenfoinvoice_ftppath;
        $ftpfile = realpath($nimisisainenfinvoice);
        $ftpfail = "{$pupe_root_polku}/dataout/sisainenfinvoice_error/";

        $verkkolasmail  = t("Pvm").": ".date("Y-m-d H:i:s")."\n\n";
        $verkkolasmail .= t("Aineiston laskut").":\n";

        foreach ($verkkolaskuputkeen_suora as $lasnoputk => $nimiputk) {
          $verkkolasmail .= "$lasnoputk - $nimiputk\n";
        }

        $verkkolasmail .= "\n\n";
        $verkkolasmail .= t("Aineisto liitteenä")."!\n\n\n\n";

        $_params = array(
          "to" => $yhtiorow["talhal_email"],
          "subject" => t("Pupesoft-Finvoice-aineiston siirto eteenpäin"),
          "ctype" => "text",
          "body" => $verkkolasmail,
          "attachements" => "",
        );

        pupesoft_sahkoposti($_params);

        require "inc/ftp-send.inc";

        if ($silent == "") {
          $tulos_ulos .= $tulos_ulos_ftp;
        }
      }

      if ($yhtiorow['lasku_tulostin'] == -88 or (isset($valittu_tulostin) and $valittu_tulostin == "-88")) {
        // Tämä näytetään vain kun laskutetaan käsin.
        if (strpos($_SERVER['SCRIPT_NAME'], "valitse_laskutettavat_tilaukset.php") !== FALSE or strpos($_SERVER['SCRIPT_NAME'], "tilaus_myynti.php") !== FALSE) {
          js_openFormInNewWindow();

          foreach ($tulostettavat as $lasku) {

            $query = "SELECT laskunro
                      FROM lasku
                      WHERE yhtio = '$kukarow[yhtio]'
                      and tunnus  = '$lasku'";
            $laresult = pupe_query($query);
            $laskurow = mysql_fetch_assoc($laresult);

            echo "<br><form id='tulostakopioform_$lasku' name='tulostakopioform_$lasku' method='post' action='{$palvelin2}tilauskasittely/tulostakopio.php' autocomplete='off'>
                <input type='hidden' name='otunnus' value='$lasku'>
                <input type='hidden' name='toim' value='LASKU'>
                <input type='hidden' name='tee' value='NAYTATILAUS'>
                <input type='submit' value='".t("Näytä lasku").": $laskurow[laskunro]' onClick=\"js_openFormInNewWindow('tulostakopioform_$lasku', ''); return false;\"></form><br>";
          }
        }
      }
      elseif (($yhtiorow['lasku_tulostin'] > 0 or $yhtiorow['lasku_tulostin'] == -99) or (isset($valittu_tulostin) and $valittu_tulostin != "")) {
        // jos yhtiöllä on laskuprintteri on määritelty tai halutaan jostain muusta syystä tulostella laskuja paperille/sähköpostiin
        require_once "tilauskasittely/tulosta_lasku.inc";

        if ((!isset($valittu_tulostin) or $valittu_tulostin == "") and ($yhtiorow['lasku_tulostin'] > 0 or $yhtiorow['lasku_tulostin'] == -99)) {
          $valittu_tulostin = $yhtiorow['lasku_tulostin'];
        }

        if ($silent == "") $tulos_ulos .= "<br>\n".t("Tulostetaan paperilaskuja").":<br>\n";

        foreach ($tulostettavat as $lasku) {

          $vientierittelymail    = "";
          $vientierittelykomento = "";

          tulosta_lasku($lasku, $kieli, "VERKKOLASKU", "", $valittu_tulostin, $valittu_kopio_tulostin, $saatekirje);

          $query = "SELECT *
                    FROM lasku
                    WHERE yhtio = '$kukarow[yhtio]'
                    and tunnus  = '$lasku'";
          $laresult = pupe_query($query);
          $laskurow = mysql_fetch_assoc($laresult);

          if ($silent == "") $tulos_ulos .= t("Tulostetaan lasku").": $laskurow[laskunro]<br>\n";

          if (($laskurow["vienti"] == "E" or $laskurow["vienti"] == "K") and $yhtiorow["vienti_erittelyn_tulostus"] != "E") {
            $uusiotunnus = $laskurow["tunnus"];

            require 'tulosta_vientierittely.inc';

            // keksitään uudelle failille joku varmasti uniikki nimi:
            list($usec, $sec) = explode(' ', microtime());
            mt_srand((float) $sec + ((float) $usec * 100000));
            $pdffilenimi = "/tmp/Vientierittely-".md5(uniqid(mt_rand(), true)).".pdf";

            //kirjoitetaan pdf faili levylle..
            $fh = fopen($pdffilenimi, "w");
            if (fwrite($fh, $Xpdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
            fclose($fh);

            if ($vientierittelykomento == "email" or $vientierittelymail != "") {
              // lähetetään meili
              if ($vientierittelymail != "") {
                $komento = $vientierittelymail;
              }
              else {
                $komento = "";
              }

              $kutsu = t("Lasku", $kieli)." $laskurow[laskunro] ".t("Vientierittely", $kieli);

              if ($yhtiorow["liitetiedostojen_nimeaminen"] == "N") {
                $kutsu .= ", ".trim($laskurow["nimi"]);
              }

              $liite              = $pdffilenimi;
              $sahkoposti_cc      = "";
              $content_subject    = "";
              $content_body       = "";
              include "inc/sahkoposti.inc"; // sanotaan include eikä require niin ei kuolla
            }
            elseif ($vientierittelykomento != '' and $vientierittelykomento != 'edi') {
              // itse print komento...
              $line = exec("$vientierittelykomento $pdffilenimi");
            }

            if ($silent == "") $tulos_ulos .= t("Vientierittely tulostuu")."...<br>\n";

            unset($Xpdf);
          }
        }
      }

      // lähetetään saähköpostilaskut
      if ($yhtiorow['lasku_tulostin'] != -99 and count($tulostettavat_email) > 0) {

        require_once "tilauskasittely/tulosta_lasku.inc";

        if ($silent == "" or $silent == "VIENTI") $tulos_ulos .= "<br>\n".t("Tulostetaan sähköpostilaskuja").":<br>\n";

        foreach ($tulostettavat_email as $lasku) {

          $vientierittelymail    = "";
          $vientierittelykomento = "";

          tulosta_lasku($lasku, $kieli, "VERKKOLASKU", "", -99, "", $saatekirje);

          $query = "SELECT *
                    FROM lasku
                    WHERE yhtio = '$kukarow[yhtio]'
                    and tunnus  = '$lasku'";
          $laresult = pupe_query($query);
          $laskurow = mysql_fetch_assoc($laresult);

          if ($silent == "" or $silent == "VIENTI") $tulos_ulos .= t("Lähetetään lasku").": $laskurow[laskunro]<br>\n";

          if (($laskurow["vienti"] == "E" or $laskurow["vienti"] == "K") and $yhtiorow["vienti_erittelyn_tulostus"] != "E") {
            $uusiotunnus = $laskurow["tunnus"];

            require 'tulosta_vientierittely.inc';

            //keksitään uudelle failille joku varmasti uniikki nimi:
            list($usec, $sec) = explode(' ', microtime());
            mt_srand((float) $sec + ((float) $usec * 100000));
            $pdffilenimi = "/tmp/Vientierittely-".md5(uniqid(mt_rand(), true)).".pdf";

            //kirjoitetaan pdf faili levylle..
            $fh = fopen($pdffilenimi, "w");
            if (fwrite($fh, $Xpdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
            fclose($fh);

            if ($vientierittelykomento == "email" or $vientierittelymail != "") {
              // lähetetään meili
              if ($vientierittelymail != "") {
                $komento = $vientierittelymail;
              }
              else {
                $komento = "";
              }

              $kutsu = t("Lasku", $kieli)." $laskurow[laskunro] ".t("Vientierittely", $kieli);

              if ($yhtiorow["liitetiedostojen_nimeaminen"] == "N") {
                $kutsu .= ", ".trim($laskurow["nimi"]);
              }

              $liite              = $pdffilenimi;
              $sahkoposti_cc      = "";
              $content_subject    = "";
              $content_body       = "";
              include "inc/sahkoposti.inc"; // sanotaan include eikä require niin ei kuolla
            }

            if ($silent == "" or $silent == "VIENTI") $tulos_ulos .= t("Vientierittely lähetetään")."...<br>\n";

            unset($Xpdf);
          }
        }
      }
    }
    elseif ($silent == "") {
      $tulos_ulos .= t("Yhtään laskua ei siirretty/tulostettu!")."<br>\n";
    }

    // lähetetään meili vaan jos on jotain laskutettavaa ja ollaan tultu komentoriviltä
    if (isset($lask) and $lask > 0 and $php_cli) {

      $content  = "<html><body>\n";
      $content .= $tulos_ulos;
      $content .= "</body></html>\n";

      $_params = array(
        "to"      => $yhtiorow["talhal_email"],
        "subject" => "{$yhtiorow["nimi"]} - Laskutusajo",
        "ctype"   => "html",
        "body"    => $content,
      );

      pupesoft_sahkoposti($_params);
    }
  }

  if (!$php_cli) {
    echo "$tulos_ulos";

    // Annetaan mahdollisuus tallentaa finvoicetiedosto jos se on luotu..
    if (isset($nimifinvoice) and file_exists($nimifinvoice) and (strpos($_SERVER['SCRIPT_NAME'], "verkkolasku_lv.php") !== FALSE or strpos($_SERVER['SCRIPT_NAME'], "valitse_laskutettavat_tilaukset.php") !== FALSE) and $yhtiorow["verkkolasku_lah"] == "finvoice") {
      echo "<br><table><tr><th>".t("Tallenna finvoice-aineisto").":</th>";
      echo "<form method='post' class='multisubmit'>";
      echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
      echo "<input type='hidden' name='kaunisnimi' value='".basename($nimifinvoice)."'>";
      echo "<input type='hidden' name='filenimi' value='".basename($nimifinvoice)."'>";
      echo "<td><input type='submit' value='".t("Tallenna")."'></td></tr></form></table>";
    }
  }

  if ($tee == '' and strpos($_SERVER['SCRIPT_NAME'], "verkkolasku_lv.php") !== FALSE) {

    //päivämäärän tarkistus
    $tilalk = explode("-", $yhtiorow["myyntireskontrakausi_alku"]);
    $tillop = explode("-", $yhtiorow["myyntireskontrakausi_loppu"]);

    $tilalkpp = $tilalk[2];
    $tilalkkk = $tilalk[1]-1;
    $tilalkvv = $tilalk[0];

    $tilloppp = $tillop[2];
    $tillopkk = $tillop[1]-1;
    $tillopvv = $tillop[0];

    $tanaanpp = date("d");
    $tanaankk = date("m")-1;
    $tanaanvv = date("Y");

    echo "  <SCRIPT LANGUAGE=JAVASCRIPT>

          function verify(){
            var pp = document.lasku.laskpp;
            var kk = document.lasku.laskkk;
            var vv = document.lasku.laskvv;

            pp = Number(pp.value);
            kk = Number(kk.value)-1;
            vv = Number(vv.value);

            if (vv == 0 && pp == 0 && kk == -1) {
              var tanaanpp = $tanaanpp;
              var tanaankk = $tanaankk;
              var tanaanvv = $tanaanvv;

              var dateSyotetty = new Date(tanaanvv, tanaankk, tanaanpp);
            }
            else {
              if (vv > 0 && vv < 1000) {
                vv = vv+2000;
              }

              var dateSyotetty = new Date(vv,kk,pp);
            }

            var dateTallaHet = new Date();
            var ero = (dateTallaHet.getTime() - dateSyotetty.getTime()) / 86400000;

            var tilalkpp = $tilalkpp;
            var tilalkkk = $tilalkkk;
            var tilalkvv = $tilalkvv;
            var dateTiliAlku = new Date(tilalkvv,tilalkkk,tilalkpp);
            dateTiliAlku = dateTiliAlku.getTime();


            var tilloppp = $tilloppp;
            var tillopkk = $tillopkk;
            var tillopvv = $tillopvv;
            var dateTiliLoppu = new Date(tillopvv,tillopkk,tilloppp);
            dateTiliLoppu = dateTiliLoppu.getTime();

            dateSyotetty = dateSyotetty.getTime();

            if (dateSyotetty < dateTiliAlku || dateSyotetty > dateTiliLoppu) {
              var msg = '".t("VIRHE: Syötetty päivämäärä ei sisälly kuluvaan tilikauteen!")."';
              alert(msg);

              skippaa_tama_submitti = true;
              return false;
            }
            if (ero >= 2) {
              var msg = '".t("Oletko varma, että haluat päivätä laskun yli 2pv menneisyyteen?")."';

              if (confirm(msg)) {
                return true;
              }
              else {
                skippaa_tama_submitti = true;
                return false;
              }
            }
            if (ero < 0 && '{$yhtiorow['laskutus_tulevaisuuteen']}' != 'S') {
              var msg = '".t("VIRHE: Laskua ei voi päivätä tulevaisuuteen!")."';
              alert(msg);

              skippaa_tama_submitti = true;
              return false;
            }
          }
        </SCRIPT>";


    echo "<br>\n<table>";

    // Mikä viikonpäivä tänään on 1-7.. 1=sunnuntai, 2=maanantai, jne...
    $today = date("w") + 1;

    // Kuukauden eka päivä
    $eka_pv = laskutuspaiva("eka");

    // Kuukauden keskimmäinen päivä
    $keski_pv = laskutuspaiva("keski");

    // Kuukauden viimeinen päivä
    $vika_pv = laskutuspaiva("vika");

    $query = "SELECT
              sum(if (lasku.laskutusvkopv = '0', 1, 0)) normaali,
              sum(if (((lasku.laskutusvkopv = $today) or
                      (lasku.laskutusvkopv = -1 and curdate() = '$vika_pv') or
                      (lasku.laskutusvkopv = -2 and curdate() = '$eka_pv') or
                      (lasku.laskutusvkopv = -3 and curdate() = '$keski_pv') or
                      (lasku.laskutusvkopv = -4 and curdate() in ('$keski_pv','$vika_pv')) or
                      (lasku.laskutusvkopv = -5 and curdate() in ('$eka_pv','$keski_pv'))), 1, 0)) paiva,
              sum(if (maksuehto.factoring_id is not null, 1, 0)) factoroitavat,
              count(lasku.tunnus) kaikki
              from lasku
              LEFT JOIN maksuehto ON lasku.yhtio=maksuehto.yhtio and lasku.maksuehto=maksuehto.tunnus
              where lasku.yhtio  = '$kukarow[yhtio]'
              and lasku.tila     = 'L'
              and lasku.alatila  = 'V'
              and lasku.tapvm   != '0000-00-00'
              and lasku.viite    = ''
              and lasku.chn     != '999'";
    $res = pupe_query($query);
    $row = mysql_fetch_assoc($res);

    echo "<form method = 'post' name='lasku' onSubmit = 'return verify()'>
      <input type='hidden' name='tee' value='TARKISTA'>";

    echo "<tr><th>".t("Laskutettavia tilauksia joilla on laskutusviikonpäivä tänään").":</th><td colspan='3'>$row[paiva]</td></tr>\n";
    echo "<tr><th>".t("Laskutettavia tilauksia joiden laskutusviikonpäivä ei ole tänään").":</th><td colspan='3'>".($row["kaikki"]-$row["normaali"]-$row["paiva"])."</td></tr>\n";
    echo "<tr><th>".t("Laskutettavia tilauksia joilla EI ole laskutusviikonpäivää").":</th><td colspan='3'>$row[normaali]</td></tr>\n";
    echo "<tr><th>".t("Laskutettavia tilauksia jotka siirretään rahoitukseen").":</th><td colspan='3'>$row[factoroitavat]</td></tr>\n";
    echo "<tr><th>".t("Laskutettavia tilauksia kaikkiaan").":</th><td colspan='3'>$row[kaikki]</td></tr>\n";

    echo "<tr><th>".t("Syötä poikkeava laskutuspäivämäärä (pp-kk-vvvv)")."</th>
        <td><input type='text' name='laskpp' value='' size='3'></td>
        <td><input type='text' name='laskkk' value='' size='3'></td>
        <td><input type='text' name='laskvv' value='' size='5'></td></tr>\n";

    if ($yhtiorow["myyntilaskun_erapvmlaskenta"] == "K") {
      echo "<tr><th>".t("Laske eräpäivä").":</th>
          <td colspan='3'><select name='erpcmlaskenta'>";
      echo "<option value=''>".t("Eräpäivä lasketaan laskutuspäivästä")."</option>";
      echo "<option value='NOW'>".t("Eräpäivä lasketaan tästä hetkestä")."</option>";
      echo "</select></td></tr>\n";
    }

    echo "<tr><th>".t("Ohita laskujen laskutusviikonpäivät").":</th><td colspan='3'><input type='checkbox' name='laskutakaikki'></td></tr>\n";

    echo "<tr><th>".t("Laskuta vain tilaukset, lista pilkulla eroteltuna").":</th><td colspan='3'><textarea name='laskutettavat' rows='10' cols='60'></textarea></td></tr>";

    echo "</table>";

    echo "<br>\n<input type='submit' value='".t("Jatka")."'>";
    echo "</form>";
  }
}

if (!$php_cli and strpos($_SERVER['SCRIPT_NAME'], "verkkolasku_lv.php") !== FALSE) {
  require "inc/footer.inc";
}
