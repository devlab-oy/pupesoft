<?php

if (strpos($_SERVER['SCRIPT_NAME'], "ulask.php")  !== FALSE) {
  require "inc/parametrit.inc";
}

if (isset($_POST['ajax_toiminto']) and trim($_POST['ajax_toiminto']) != '') {
  require "inc/tilioinnin_toiminnot.inc";
}

enable_ajax();

if ($livesearch_tee == "TILIHAKU") {
  livesearch_tilihaku();
  exit;
}

if (!isset($nayta_skannattu_lasku)) $nayta_skannattu_lasku = "";

function listdir($start_dir = '.') {

  $files = array();
  $ohitetut_laskut = array();

  if (!is_dir($start_dir)) {
    return false;
  }

  $start_dir = rtrim($start_dir, '/');
  $file_list = explode("\n", trim(`ls $start_dir/ | sort`));

  foreach ($file_list as $file) {

    if ($file == "") {
      continue;
    }

    $filepath = $start_dir.'/'.$file;

    if (is_file($filepath)) {
      array_push($files, $filepath);
    }
  }

  return $files;
}

function hae_skannattu_lasku($kasittele_seuraava = "") {

  global $kukarow, $yhtiorow;

  $dir = $yhtiorow['skannatut_laskut_polku'];

  if (!is_dir($dir) or !is_writable($dir)) {
    return false;
  }

  // käydään läpi ensin käsiteltävät kuvat
  $files = listdir($dir);
  $files_count = count($files);

  // Jos ei ole yhtään tiedostoa, palautetaan false
  if ($files_count == 0) {
    return false;
  }

  // Tehdään palautusta varten array, palautetaan aina laskujen lukumäärä
  $palautus = array("lukumaara" => $files_count);

  // Katsotaan onko laskuja, jota käyttäjä voisi käsitellä
  $query = "SELECT kesken
            FROM kuka
            WHERE yhtio  = '{$kukarow["yhtio"]}'
            AND extranet = ''";
  $kesken_chk_res = pupe_query($query);

  foreach ($files as $file) {

    $path_parts = pathinfo($file);
    mysql_data_seek($kesken_chk_res, 0);

    while ($kesken_chk_row = mysql_fetch_assoc($kesken_chk_res)) {
      // Jos tiedosto on tällä käyttäjällä kesken
      if (is_numeric($path_parts['filename']) and $path_parts['filename'] == $kukarow['kesken']) {
        // Palautetaan kesken -key, jos tämä on meillä kesken
        $palautus["kesken"] = $path_parts["basename"];
        $palautus["seuraava"] = $path_parts["basename"];
        return $palautus;
      }
      // Jos tiedosto on jollain muulla käyttäjällä kesken, ohitetaan
      elseif ($path_parts['filename'] == $kesken_chk_row['kesken']) {
        continue 2;
      }
    }

    // Jos halutaan ottaa seuraava lasku käsittelyyn, päivitetään kuka -tiedot
    if ($kasittele_seuraava != "") {
      // Tämän tiedoston voi käsitellä seuraavaksi, laitetaan se käyttäjälle keskeneräiseksi
      list($micro, $timestamp) = explode(" ", microtime());
      $file_basename = substr($timestamp.substr(($micro * 10), 0, 1), 2);

      $kukarow['kesken'] = $file_basename;
      $file_new = $dir."/".$file_basename.".".$path_parts['extension'];

      $query = "UPDATE kuka SET kesken = '{$kukarow["kesken"]}'
                WHERE yhtio = '{$kukarow["yhtio"]}'
                AND kuka    = '{$kukarow["kuka"]}'";
      $kesken_upd_res = pupe_query($query);

      if (!rename($file, $file_new)) {
        echo t("Ei pystytä nimeämään tiedostoa")."<br>";
        return false;
      }

      // Otetaan path parts uudesta tiedostonimestä
      $path_parts = pathinfo($file_new);
    }

    // Tämän laskun voi käsitellä
    $palautus["seuraava"] = $path_parts["basename"];
    return $palautus;
  }

  // Ei löytynyt yhtään sopivaa laskua
  return false;
}

if (trim($muutparametrit) != '') {
  list($nayta_skannattu_lasku) = explode('#', $muutparametrit);
}

echo "<font class='head'>".t("Uuden laskun perustus")."</font><hr>";

// Ohitetaan lasku
if ($tee == 'ohita_lasku') {

  $skannatut_laskut_polku = rtrim($yhtiorow['skannatut_laskut_polku'], '/').'/';
  $ohitettava_lasku = realpath($skannatut_laskut_polku.$ohitettava_lasku);
  $path_parts = pathinfo($ohitettava_lasku);
  // Nimetään ohitettu lasku "ZZ" etuliitteellä. Pitää olla pikku z, koska isot kirjaimet tulee sorttauksessa aina ennen pieniä
  $ohitettava_lasku_new = realpath($skannatut_laskut_polku)."/zz".$kukarow['kesken'].".".$path_parts['extension'];

  if (!rename($ohitettava_lasku, $ohitettava_lasku_new)) {
    echo "Ei pystytä nimeämään tiedostoa.<br>";
    exit;
  }

  // Nollataan kesken ollut
  $query = "UPDATE kuka SET
            kesken      = 0
            WHERE yhtio = '$kukarow[yhtio]'
            AND kuka    = '$kukarow[kuka]'";
  $kesken_upd_res = pupe_query($query);
  $kukarow['kesken'] = 0;

  // Haetaan seuraava lasku
  $silent = 'ei näytetä käyttöliittymää';
  $seuraava_lasku = hae_skannattu_lasku("kasittele_seuraava");

  if ($seuraava_lasku === FALSE) {
    echo "<br/>", t("Skannatut laskut loppuivat"), ".<br/><br/>";
    $nayta_skannattu_lasku = "";
  }
  else {
    $nayta_skannattu_lasku = $seuraava_lasku["seuraava"];
  }

  $tee = "";
}

if ($tee == 'poista_lasku') {

  // Lähetetään lasku sähköpostilla
  $skannatut_laskut_polku = rtrim($yhtiorow['skannatut_laskut_polku'], '/').'/';

  $poistettava_lasku_tmpname = "/tmp/".md5(uniqid(mt_rand(), true))."_".$poistettava_lasku;

  // Siirretään lasku temppiin
  rename(realpath($skannatut_laskut_polku.$poistettava_lasku), $poistettava_lasku_tmpname);

  // Sähköpostin lähetykseen parametrit
  $parametri = array(
    "to"           => $kukarow["eposti"],
    "cc"           => $yhtiorow["talhal_email"],
    "subject"      => t("Poistettu lasku"),
    "ctype"        => "text",
    "body"         => t("Liitteenä Pupesoftista poistettu skannattu lasku.")."\n\n",
    "attachements" => array(0   => array(
        "filename"    => $poistettava_lasku_tmpname,
        "newfilename" => $poistettava_lasku,
        "ctype"       => ""),
    )
  );

  pupesoft_sahkoposti($parametri);

  // Haetaan seuraava lasku
  $silent = 'ei näytetä käyttöliittymää';
  $seuraava_lasku = hae_skannattu_lasku("kasittele_seuraava");

  if ($seuraava_lasku === FALSE) {
    echo "<br/>", t("Skannatut laskut loppuivat"), ".<br/><br/>";
    $nayta_skannattu_lasku = "";
  }
  else {
    $nayta_skannattu_lasku = $seuraava_lasku["seuraava"];
  }

  $tee = "";
}

if ($tee == 'VIIVA') {

  // tällästä on laskun viivakoodi:
  // 2
  // 15753000000064
  // 00004600
  // 00000007702554380108
  // 041215
  // 00008

  if (strlen($nimi) == 54 and ( substr($nimi, 0, 1) == '2' or substr($nimi, 0, 1) == '4' or substr($nimi, 0, 1) == '5') ) {
    $versio = substr($nimi, 0, 1);
    $tee2  = "";

    if ($versio == '2') {
      $tilino = substr($nimi, 1, 14);
      $summa  = substr($nimi, 15, 8) / 100;
      $viite  = ltrim(substr($nimi, 23, 20), "0"); // etunollat pois
      $erv    = substr($nimi, 43, 2);
      $erk    = substr($nimi, 45, 2);
      $erp    = substr($nimi, 47, 2);
    }
    elseif ($versio == '4') {
      $tilino  = substr($nimi, 1, 16);
      $summa  = substr($nimi, 17, 8) / 100;
      $viite   = ltrim(substr($nimi, 28, 20), "0"); // etunollat pois
      $erv   = substr($nimi, 48, 2);
      $erk   = substr($nimi, 50, 2);
      $erp   = substr($nimi, 52, 2);
    }
    elseif ($versio == '5') {
      $tilino  = substr($nimi, 1, 16);
      $summa  = substr($nimi, 17, 8) / 100;
      $viite   = "RF".substr($nimi, 25, 2).ltrim(substr($nimi, 27, 21), "0"); // Tarkistenumeron jälkeen tulevat täytenollat pois
      $erv   = substr($nimi, 48, 2);
      $erk   = substr($nimi, 50, 2);
      $erp   = substr($nimi, 52, 2);
    }

    // Toistaiseksi osataan vaan tarkistaa suomalaisten pankkitilien oikeellisuutta
    if (strtoupper($yhtiorow['maa']) == 'FI') {
      if ($versio == '2') {
        $pankkitili = $tilino;
        require "inc/pankkitilinoikeellisuus.php";
        $tilino = $pankkitili;
        $hakuehto = "tilinumero";
      }
      else {
        $hakuehto = "ultilno";
        $tilino = tarkista_iban("FI".$tilino);
      }
    }

    $query = "SELECT tunnus
              FROM toimi
              WHERE yhtio = '$kukarow[yhtio]'
              and $hakuehto = '$tilino'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) != 1) {
      echo "<font class='error'>".t("Toimittajaa")." $tilino ".t("ei löytynytkään")."!</font><br><br>";
      $tee = "";
    }
    else {
      $trow = mysql_fetch_assoc($result);

      // Ei näytetä henkilötunnusta
      $trow["ytunnus_clean"] = tarkistahetu($trow["ytunnus"]);

      $toimittajaid   = $trow["tunnus"];
      $tee       = "P";
      $tee2       = "V"; // Meillä on eroja virheentarkastuksissa, jos tiedot tuli viivakoodista
    }
  }
  else {
    echo "<font class='error'>".t("Virheellinen viivakoodi!")."</font><br><br>";
    $tee = "";
  }
}

// Tarkistetetaan syötteet perustusta varten
if ($tee == 'I') {

  $errormsg = "";
  // Talletetaan käyttäjän nimellä tositteen/liitteen kuva, jos sellainen tuli
  // koska, jos tulee virheitä tiedosto katoaa. Kun kaikki on ok, annetaan sille oikea nimi
  for ($k=1; $k<=3; $k++) {
    if (is_uploaded_file($_FILES['userfile'.$k]['tmp_name'])) {
      ${"kuva".$k} = false;

      // otetaan file extensio
      $path_parts = pathinfo($_FILES['userfile'.$k]['name']);
      $ext = $path_parts['extension'];
      if (strtoupper($ext) == "JPEG") $ext = "jpg";

      // extensio pitää olla oikein
      if (strtoupper($ext) != "JPG" and strtoupper($ext) != "PNG" and strtoupper($ext) != "GIF" and strtoupper($ext) != "PDF") {
        $errormsg .= "<font class='error'>".t("Ainoastaan .jpg .gif .png .pdf tiedostot sallittuja")."!</font>";
        $tee = "E";
        $fnimi = "";
      }
      // ja file jonkun kokonen
      elseif ($_FILES['userfile'.$k]['size'] == 0) {
        $errormsg .= "<font class='error'>".t("Tiedosto on tyhjä")."!</font>";
        $tee = "E";
        $fnimi = "";
      }

      $query = "SHOW variables like 'max_allowed_packet'";
      $result = pupe_query($query);
      $varirow = mysql_fetch_row($result);

      if ($filesize > $varirow[1]) {
        $errormsg .= "<font class='error'>".t("Liitetiedosto on liian suuri")."! (mysql: $varirow[1]) </font>";
        $tee = "E";
      }

      // jos ei virheitä..
      if ($tee == "I") {
        ${"kuva".$k} = tallenna_liite("userfile".$k, "lasku", 0, "", "", 0, 0, "");
      }
    }
    elseif (isset($_FILES['userfile'.$k]['error']) and $_FILES['userfile'.$k]['error'] != 4) {
      // nelonen tarkoittaa, ettei mitään fileä uploadattu.. eli jos on joku muu errori niin ei päästetä eteenpäin
      if ($_FILES['userfile'.$k]['error'] == 1) {
        $errormsg .=  "<font class='error'>".t("Liitetiedosto on liian suuri")."! (php: (".ini_get("upload_max_filesize")."))</font><br>";
      }
      else {
        $errormsg .=  "<font class='error'>".t("Laskun kuvan lähetys epäonnistui")."! (Error: ".$_FILES['userfile'.$k]['error'].")</font><br>";
      }
      $tee = "E";
    }
  }

  if ($yhtiorow['skannatut_laskut_polku'] != '' and $nayta_skannattu_lasku != "") {
    $skannatut_laskut_polku = rtrim($yhtiorow['skannatut_laskut_polku'], '/').'/';

    // lisätään kuva
    $kuva1 = tallenna_liite($skannatut_laskut_polku.$nayta_skannattu_lasku, "lasku", 0, '');
  }

  if (isset($toitilinumero)) {
    $query = "SELECT *
              FROM toimi
              WHERE tunnus = '$toimittajaid'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) != 1) {
      echo t("Toimittajaa")." $ytunnus ".t("ei löytynytkään")."!";
      exit;
    }

    $trow = mysql_fetch_assoc($result);

    // Ei näytetä henkilötunnusta
    $trow["ytunnus_clean"] = tarkistahetu($trow["ytunnus"]);

    // Vaaditaan isot kirjaimet
    $toitilinumero = trim(strtoupper($toitilinumero));
    $toiswift = trim(strtoupper($toiswift));

    if ($trow['ultilno_maa'] != "") $ibanmaa = $trow['ultilno_maa'];
    else $ibanmaa = $trow['maa'];

    // Kotimainen tilinumero
    if (isset($toitilinumero) and strtoupper($trow['maa']) == 'FI') {
      $pankkitili = $toitilinumero;

      require "inc/pankkitilinoikeellisuus.php";

      if ($pankkitili != '') {
        $iban = luoiban($pankkitili);

        $trow['tilinumero'] = $pankkitili;
        $trow['ultilno']    = $iban['iban'];
        $trow['swift']      = $iban['swift'];

        $query = "UPDATE toimi set tilinumero='{$trow['tilinumero']}', ultilno='{$trow['ultilno']}', swift='{$trow['swift']}' where yhtio='$kukarow[yhtio]' and tunnus='$toimittajaid'";
        $result = pupe_query($query);
      }
    }

    // Jos SEPA-maa, tarkistetaan IBAN
    if (isset($toitilinumero) and tarkista_sepa($ibanmaa) and tarkista_iban($toitilinumero) == $toitilinumero and $toiswift != '') {
      $trow['ultilno'] = $toitilinumero;
      $trow['swift']   = $toiswift;

      $query = "UPDATE toimi set ultilno='{$trow['ultilno']}', swift='{$trow['swift']}' where yhtio='$kukarow[yhtio]' and tunnus='$toimittajaid'";
      $result = pupe_query($query);

    }
    // Jos ei SEPA-maa, niin BBAN-tsekki
    elseif (isset($toitilinumero) and !tarkista_sepa($ibanmaa) and tarkista_bban($toitilinumero) === FALSE and $toiswift != '') {
      $trow['ultilno'] = $toitilinumero;
      $trow['swift']   = $toiswift;

      $query = "UPDATE toimi set ultilno='{$trow['ultilno']}', swift='{$trow['swift']}' where yhtio='$kukarow[yhtio]' and tunnus='$toimittajaid'";
      $result = pupe_query($query);
    }
  }

  // Hoidetaan pilkut pisteiksi....
  $kassaale = str_replace(",", ".", trim($kassaale));
  $summa    = str_replace(",", ".", trim($summa));
  $osto_rahti    = str_replace(",", ".", trim($osto_rahti));
  $osto_kulu    = str_replace(",", ".", trim($osto_kulu));
  $osto_rivi_kulu    = str_replace(",", ".", trim($osto_rivi_kulu));

  for ($i=1; $i<$maara; $i++) {
    $isumma[$i] = str_replace(",", ".", trim($isumma[$i]));
  }

  if ($summa != "" and !is_numeric($summa)) {
    $errormsg .= "<font class='error'>".t("Summa ei ole numeerinen")."!</font><br>";
    $tee = 'E';
  }

  if ($kassaale != "" and !is_numeric($kassaale)) {
    $errormsg .= "<font class='error'>".t("Kassa-ale ei ole numeerinen")."!</font><br>";
    $tee = 'E';
  }

  if (isset($osto_rahti) and trim($osto_rahti) != '' and !is_numeric($osto_rahti)) {
    $errormsg .= "<font class='error'>".t("Vaihto-omaisuuslaskun rahdin summa ei ole numeerinen")."!</font><br>";
    $tee = 'E';
  }

  if (isset($osto_kulu) and trim($osto_kulu) != '' and !is_numeric($osto_kulu)) {
    $errormsg .= "<font class='error'>".t("Vaihto-omaisuuslaskun kulun summa ei ole numeerinen")."!</font><br>";
    $tee = 'E';
  }

  if (isset($osto_rivi_kulu) and trim($osto_rivi_kulu) != '' and !is_numeric($osto_rivi_kulu)) {
    $errormsg .= "<font class='error'>".t("Vaihto-omaisuuslaskun tuoterivikohtaisen kulun summa ei ole numeerinen")."!</font><br>";
    $tee = 'E';
  }

  if ($errormsg == '' and isset($summa) and (isset($osto_rahti) or isset($osto_kulu) or isset($osto_rivi_kulu))) {

    if (!isset($osto_rahti)) {
      $osto_rahti = 0;
    }
    else {
      $osto_rahti = round($osto_rahti, 2);
    }
    if (!isset($osto_kulu)) {
      $osto_kulu = 0;
    }
    else {
      $osto_kulu = round($osto_kulu, 2);
    }
    if (!isset($osto_rivi_kulu)) {
      $osto_rivi_kulu = 0;
    }
    else {
      $osto_rivi_kulu = round($osto_rivi_kulu, 2);
    }
    if (abs($summa) - abs($osto_rahti) - abs($osto_kulu) - abs($osto_rivi_kulu) < 0) {
      $errormsg .= "<font class='error'>".t("Kulut ylittävät laskun summan")."!</font><br>";
      $tee = 'E';
    }

  }

  for ($i=1; $i<$maara; $i++) {
    if ($isumma[$i] != "" and !is_numeric($isumma[$i])) {
      $errormsg .= "<font class='error'>".t("Jokin tiliöinneistä ei ole numeerinen")."!</font><br>";
      $tee = 'E';
      break;
    }
  }

  // muutetaan numeroiksi
  $tpk = (int) $tpk;
  $tpp = (int) $tpp;
  $tpv = (int) $tpv;
  $vpk = (int) $vpk;
  $vpp = (int) $vpp;
  $vpv = (int) $vpv;

  if ($tpv < 1000 and $tpv != 0) $tpv += 2000;

  if ($yhtiorow['ostolaskujen_paivays'] == "1") {
    if ($vpv < 1000 and $vpv != 0) $vpv += 2000;
  }

  if ((int) $kopioi > 12) {
    $errormsg .= "<font class='error'>".t("Laskun voi kopioida korkeintaan 12 kertaa")."</font><br>";
    $tee = 'E';
  }

  if ($yhtiorow['ostolaskujen_paivays'] == "1") {
    if (!checkdate($vpk, $vpp, $vpv)) {
      $errormsg .= "<font class='error'>".t("Virheellinen laskun päiväys")."</font><br>";
      $tee = 'E';
    }
    if (!checkdate($tpk, $tpp, $tpv)) {
      $errormsg .= "<font class='error'>".t("Virheellinen kirjauspäivä")."</font><br>";
      $tee = 'E';
    }
  }
  elseif (!checkdate($tpk, $tpp, $tpv)) {
    $errormsg .= "<font class='error'>".t("Virheellinen laskun päiväys")."</font><br>";
    $tee = 'E';
  }

  // jos ollaan syötetty relatiivinen eräpäivä
  if ($err > 0 and $tee != 'E') {
    if ($erp > 0) {
      $errormsg .= "<font class='error'>".t("Kaksi eräpvmää")."</font><br>";
      $tee = 'E';
    }
    else {
      if ($yhtiorow['ostolaskujen_paivays'] == "1") {
        $erp = date("d", mktime(0, 0, 0, $vpk, $vpp+$err, $vpv));
        $erk = date("m", mktime(0, 0, 0, $vpk, $vpp+$err, $vpv));
        $erv = date("Y", mktime(0, 0, 0, $vpk, $vpp+$err, $vpv));
        $err = "";
      }
      else {
        $erp = date("d", mktime(0, 0, 0, $tpk, $tpp+$err, $tpv));
        $erk = date("m", mktime(0, 0, 0, $tpk, $tpp+$err, $tpv));
        $erv = date("Y", mktime(0, 0, 0, $tpk, $tpp+$err, $tpv));
        $err = "";
      }
    }
  }

  // ollaan syötetty relatiivinen kassa-alennus päivä
  if ($kar > 0 and $tee != 'E') {
    if ($kap > 0) {
      $errormsg .= "<font class='error'>".t("Kaksi kassa-alepvmää")."</font><br>";
      $tee = 'E';
    }
    else {
      if ($yhtiorow['ostolaskujen_paivays'] == "1") {
        $kap = date("d", mktime(0, 0, 0, $vpk, $vpp+$kar, $vpv));
        $kak = date("m", mktime(0, 0, 0, $vpk, $vpp+$kar, $vpv));
        $kav = date("Y", mktime(0, 0, 0, $vpk, $vpp+$kar, $vpv));
        $kar = "";
      }
      else {
        $kap = date("d", mktime(0, 0, 0, $tpk, $tpp+$kar, $tpv));
        $kak = date("m", mktime(0, 0, 0, $tpk, $tpp+$kar, $tpv));
        $kav = date("Y", mktime(0, 0, 0, $tpk, $tpp+$kar, $tpv));
        $kar = "";
      }
    }
  }

  // muutetaan numeroiksi
  $erk = (int) $erk;
  $erp = (int) $erp;
  $erv = (int) $erv;
  if ($erv < 1000 and $erv != 0) $erv += 2000;

  if (!checkdate($erk, $erp, $erv)) {
    $errormsg .= "<font class='error'>".t("Virheellinen eräpvm")."</font><br>";
    $tee = 'E';

    if ($erv == 0 and $erk == 0 and $erp == 0) {
      $erk = "";
      $erp = "";
      $erv = "";
    }
  }

  if ($kapro != 0) {
    if ($kassaale > 0) {
      $errormsg .= "<font class='error'>".t("Kaksi kassa-alesummaa")."</font><br>";
      $tee = 'E';
    }
    else {
      $kassaale = $summa * $kapro / 100;
      $kapro = 0;
    }
  }

  $kassaale = round($kassaale, 2);

  if ($kak > 0) {
    $kak += 0;
    $kap += 0;
    $kav += 0;
    if ($kav < 1000) $kav += 2000;

    if (!checkdate($kak, $kap, $kav)) {
      $errormsg .= "<font class='error'>".t("Virheellinen kassaeräpvm")."</font><br>";
      $tee = 'E';
    }
    else {
      if ($kassaale == 0) {
        $errormsg .= "<font class='error'>".t("Kassapvm on, mutta kassa-ale puuttu")."</font><br>";
        $tee = 'E';
      }
      $kassa_alepvmcheck = (int) date('Ymd', mktime(0, 0, 0, $kak, $kap, $kav));
      $erapvmcheck = (int) date('Ymd', mktime(0, 0, 0, $erk, $erp, $erv));

      if ($kassa_alepvmcheck > $erapvmcheck) {
        $errormsg .= "<font class='error'>".t("Kassapvm ei voi olla eräpäivän jälkeen")."</font><br>";
        $tee = 'E';
      }
    }
  }

  if (!is_numeric(trim($toimittajan_laskunumero)) and trim($toimittajan_laskunumero) != "") {
    $errormsg .= "<font class='error'>".t("Laskunumero on oltava numeerinen")."</font><br/>";
    $tee = 'E';
  }

  if (trim($hyvak[1]) == "" and laskun_hyvaksyjia()) {
    $errormsg .= "<font class='error'>".t("Laskulla on pakko olla ensimmäinen hyväksyjä")."!</font><br>";
    $tee = 'E';
  }

  // poistetaan spacet ja tehdään uniikki
  $apu_hyvak = array();

  foreach (array_unique($hyvak) as $apu_hyvakrivi) {
    if ($apu_hyvakrivi != " ") {
      $apu_hyvak[] = $apu_hyvakrivi;
    }
  }

  if (count($apu_hyvak) == 1 and in_array($kukarow["kuka"], $apu_hyvak)) {
    $errormsg .= "<font class='error'>".t("Laskun syöttäjä ei saa olla ainoa hyväksyjä")."!</font><br>";
    $tee = 'E';
  }

  if ($luouusikeikka == "LUO" and $vienti != "C" and $vienti != "J" and $vienti != "F" and $vienti != "K" and $vienti != "I" and $vienti != "L") {
    $errormsg .= "<font class='error'>".t("Saapumista ei voi perustaa kululaskulle")."</font><br>";
    $tee = 'E';
  }

  if (strlen($viite) == 0 and strlen($viesti) == 0 and strlen($toimittajan_laskunumero) == 0) {
    $errormsg .= "<font class='error'>".t("Anna viite, viesti tai laskunumero")."</font><br>";
    $tee = 'E';
  }

  if (strlen($viite) > 0 and substr($viite, 0, 2) != "RF" and tarkista_viite($viite) === FALSE) {
    $errormsg .= "<font class='error'>".t("Viite on väärin")."</font><br>";
    $tee = 'E';
  }

  if (strlen($viite) > 0 and substr($viite, 0, 2) == "RF" and tarkista_rfviite($viite) === FALSE) {
    $errormsg .= "<font class='error'>".t("RF-Viite on väärin")."</font><br>";
    $tee = 'E';
  }

  if (strlen($viite) > 0 and strlen($viesti) > 0) {
    $errormsg .= "<font class='error'>".t("Viitettä ja viestiä ei voi antaa yhtaikaa")."</font><br>";
    $tee = 'E';
  }

  // Tällöin ei tarvitse erikseen syöttää summaa
  if ($maara == 2 and strlen($isumma[1]) == 0) {
    $isumma[1] = $summa;
  }

  if ($maara > 1) {
    if ($syottotyyppi == 'prosentti') {
      $viimeinensumma=0;
      for ($i=1; $i<$maara; $i++) {
        $viimeinensumma += (float) $isumma[$i];
      }
      if ($viimeinensumma != 100) {
        $errormsg .= "<font class='error'>".t("Prosenttien yhteisumma ei ole 100")." $viimeinensumma</font><br>";
        $tee = 'E';
      }
      else {
        for ($i=1; $i<$maara; $i++) {
          if ($isumma[$i] != 0) {
            $isumma[$i] = round((float) $summa * (float) $isumma[$i] / 100, 2);
            $summatotaali += (float) $isumma[$i];
            $viimeinensumma = $i;
          }
        }
        if (abs($summatotaali - $summa) >= 0.01) {
          $isumma[$viimeinensumma] += $summatotaali - $summa;
        }
        $syottotyyppi='saldo';
      }
    }
  }

  if ((is_array($trow) and strtoupper($trow['maa']) == strtoupper($yhtiorow['maa'])) or (!is_array($trow) and $tyyppi == strtoupper($yhtiorow['maa']))) {
    $ohjeitapankille = '';
  }
  else {
    if (strlen($ohjeitapankille) > 350) {
      $errormsg .= "<font class='error'>".t("Ohjeita pankille-kentän pituus 350 ylittyi")."</font><br>";
      $tee = 'E';
    }
  }

  // Käydään tiliöinnit läpi
  for ($i = 1; $i < $maara; $i++) {
    // Käsitelläänkö rivi??
    if (strlen($itili[$i]) > 0) {
      $turvasumma   = $summa;
      $virhe       = "";
      $tili       = $itili[$i];
      $summa       = (float) $isumma[$i];
      $selausnimi    = "itili['.$i.']"; // Minka niminen mahdollinen popup on?
      $mistatullaan  = "ulask.php"; // koska nykyään on sallittua syöttää nollalasku, eli tässä tapauksessa ei sallita että kaadutaan tilioinnin summan puuttumiseen
      $ulos      = ""; // Mahdollinen popup tyhjennetaan
      $tositetila   = "U";
      $tositeliit    = $trow["tunnus"];
      $kustp_tark    = $ikustp[$i];
      $kohde_tark    = $ikohde[$i];
      $projekti_tark  = $iprojekti[$i];

      require "inc/tarkistatiliointi.inc";

      // Sieltä kenties tuli päivitys tilinumeroon
      if ($ok == 0) {
        // Annetaan käyttäjän päättää onko ok
        if ($itili[$i] != $tili) {
          $itili[$i] = $tili;
          $gok = 1; // Tositetta ei kirjoiteta kantaan vielä
        }
      }
      else {
        $gok = $ok; // Nostetaan virhe ylemmälle tasolle
      }

      $ivirhe[$i]  = $virhe;
      $iulos[$i]    = $ulos;
      $yleissumma += $isumma[$i];
      $summa      = $turvasumma;
    }
  }

  // Jossain tapahtui virhe
  if ($gok == 1) {
    $errormsg .= "<font class='error'>".t("Jossain tiliöinnissä oli virheitä tai muutoksia")."!</font><br>";
    $tee = 'E';
  }

  if (abs($yleissumma - $summa) >= 0.01 ) {
    $errormsg .= "<font class='error'>".t("Tiliöinti heittää")." $summa != $yleissumma</font><br>";
    $tee = 'E';
  }

  // Jos toimittaja löytyy se haetaan, muuten tiedot tulee formista
  if ($toimittajaid > 0) {
    $query = "SELECT * FROM toimi WHERE tunnus = '$toimittajaid'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) != 1) {
      echo t("Toimittajaa")." $ytunnus ".t("ei löytynytkään")."!";
      exit;
    }

    $trow = mysql_fetch_assoc($result);

    // Ei näytetä henkilötunnusta
    $trow["ytunnus_clean"] = tarkistahetu($trow["ytunnus"]);
  }

  if (strlen($trow['ytunnus']) == 0) {
    $errormsg .= "<font class='error'>".t("Ytunnus puuttuu")."</font><br>";
    $tee = 'E';
  }

  if (strlen($trow['nimi']) == 0) {
    $errormsg .= "<font class='error'>".t("Toimittajan nimi puuttuu")."</font><br>";
    $tee = 'E';
  }

  $pankkitiliok = FALSE;

  if ($trow['ultilno_maa'] != "") $ibanmaa = $trow['ultilno_maa'];
  else $ibanmaa = $trow['maa'];

  // Kotimainen tilinumero
  if (strtoupper($ibanmaa) == "FI") {
    $pankkitili = $trow['tilinumero'];

    require 'inc/pankkitilinoikeellisuus.php';

    if ($pankkitili != '') {
      $iban = luoiban($pankkitili);

      $trow['tilinumero'] = $pankkitili;
      $trow['ultilno']    = $iban['iban'];
      $trow['swift']      = $iban['swift'];
      $pankkitiliok     = TRUE;
    }
  }
  elseif (strtoupper($yhtiorow['maa']) == 'SE' and $trow['tilinumero'] != '') {
    // Ruotsin keississä ei tehdä Bankgirolle eikä Plusgirolle tsekkiä
    $pankkitiliok = TRUE;
  }

  // IBAN / BBAN
  if ($trow['ultilno'] != "") {
    $swift_ok = TRUE;

    if (strtoupper($ibanmaa) == "FI") {
      // Haetaan swift tarkistusta varten tai jos sitä ei ole syötetty ollenkaan
      include "inc/pankkitiedot.inc";
      $vastaus = pankkitiedot($trow['ultilno'], '');

      // Jos swift on tyhjä niin laitetaan haettu tilalle
      if (empty($trow["swift"])) {
        $trow["swift"] = $vastaus['swift'];
      }

      if ($trow["swift"] != $vastaus['swift']) {
        $swift_ok = FALSE;
      }
    }

    // Vaaditaan isot kirjaimet
    $trow['ultilno'] = strtoupper($trow['ultilno']);
    $trow['swift']   = strtoupper($trow['swift']);

    // Jos SEPA-maa, tarkistetaan IBAN ja SWIFT-koodi
    if (tarkista_sepa($ibanmaa) and tarkista_iban($trow['ultilno']) == $trow['ultilno'] and $swift_ok) {
      $pankkitiliok = TRUE;
    }
    elseif (!tarkista_sepa($ibanmaa) and tarkista_bban($trow['ultilno']) !== FALSE) {
      $pankkitiliok = TRUE;
    }
  }

  if (!$pankkitiliok) {
    $errormsg .= "<font class='error'>".t("Pankkitili puuttuu tai on virheellinen")."!</font><br>";
    $errormsg .= "<font class='error'>".t("Tarkista myös SWIFT-koodi")."!</font><br>";
    $tee = 'E';
  }
}

if ($tee == 'Y') {

  if ($yhtiorow['skannatut_laskut_polku'] != '' and $nayta_skannattu_lasku != "") {
    $muutparametrit = $nayta_skannattu_lasku;
  }

  require "inc/kevyt_toimittajahaku.inc";

  // Toimittaja löytyi
  if ($toimittajaid != 0) {
    $tee   = "P";
    $trow   = $toimittajarow;
  }
}

// Annetaan käyttäjälle esitäytetty formi, jos toimittaja on tai sitten täytettävät kentät
if ($tee == 'P' or $tee == 'E') {

  //päivämäärän tarkistus
  $tilalk = explode("-", $yhtiorow["ostoreskontrakausi_alku"]);
  $tillop = explode("-", $yhtiorow["ostoreskontrakausi_loppu"]);

  $tilalkpp = $tilalk[2];
  $tilalkkk = $tilalk[1]-1;
  $tilalkvv = $tilalk[0];

  $tilloppp = $tillop[2];
  $tillopkk = $tillop[1]-1;
  $tillopvv = $tillop[0];

  $toimittajan_kaikki_laskunumerot = "";

  $query = "SELECT ifnull(group_concat(distinct laskunro), 0) laskut
            FROM lasku USE INDEX (yhtio_tila_liitostunnus_tapvm)
            WHERE yhtio       = '$kukarow[yhtio]'
            AND liitostunnus  = '$trow[tunnus]'
            AND tila          IN ('H','M','P','Q','Y')
            AND laskunro     != 0
            AND tapvm         >= date_sub(now(), INTERVAL 12 MONTH)";
  $tarkres = pupe_query($query);
  $tarkrow = mysql_fetch_assoc($tarkres);

  if ($tarkrow["laskut"] != 0) {
    $toimittajan_kaikki_laskunumerot = $tarkrow["laskut"];
  }

  echo "  <script type='text/javascript'>

        function oc(a) {
          var o = {};
          for (var i = 0; i < a.length; i++) {
            o[a[i]] = '';
          }
          return o;
        }

        function verify() {
          var pp = document.lasku.tpp;
          var kk = document.lasku.tpk;
          var vv = document.lasku.tpv;
          var laskunumerot = '{$toimittajan_kaikki_laskunumerot}'.split(',');
          var laskunumero = document.lasku.toimittajan_laskunumero.value;

          if (Number(laskunumero) > 0) {
            if (laskunumero in oc(laskunumerot)) {
              var msg = '".t("Oletko varma, että haluat syöttää tämän laskun? Toimittajalle on perustettu lasku samalla numerolla viimeisen vuoden sisällä.")."';

              if (confirm(msg)) {
                return true;
              }
              else {
                skippaa_tama_submitti = true;
                return false;
              }
            }
          }

          pp = Number(pp.value);
          kk = Number(kk.value)-1;
          vv = Number(vv.value);

          if (vv < 1000) {
            vv = vv+2000;
          }

          var dateSyotetty = new Date(vv,kk,pp);
          var dateTallaHet = new Date();
          var ero = (dateTallaHet.getTime() - dateSyotetty.getTime()) / 86400000;

          var era_vv = Number(document.lasku.erv.value);
          var era_kk = Number(document.lasku.erk.value);
          var era_pp = Number(document.lasku.erp.value);

          if (era_vv < 1000 && era_vv > 0) {
            era_vv = era_vv+2000;
          }

          if (era_vv > 0 && era_kk > 0 && era_pp > 0) {
            var erapvm = new Date(era_vv, era_kk, era_pp);
            var erapvm_ero = (dateTallaHet.getTime() - erapvm.getTime()) / 86400000;

            if (erapvm_ero > 365 || erapvm_ero < -365) {
              var msg = '".t("Oletko varma, että haluat syöttää eräpäivän yli vuoden menneisyyteen/tulevaisuuteen?")."';

              if (confirm(msg)) {
                return true;
              }
              else {
                skippaa_tama_submitti = true;
                return false;
              }
            }
          }

          var tilalkpp = {$tilalkpp};
          var tilalkkk = {$tilalkkk};
          var tilalkvv = {$tilalkvv};
          var dateTiliAlku = new Date(tilalkvv,tilalkkk,tilalkpp);
          dateTiliAlku = dateTiliAlku.getTime();

          var tilloppp = {$tilloppp};
          var tillopkk = {$tillopkk};
          var tillopvv = {$tillopvv};
          var dateTiliLoppu = new Date(tillopvv,tillopkk,tilloppp);
          dateTiliLoppu = dateTiliLoppu.getTime();

          dateSyotetty = dateSyotetty.getTime();

          if (dateSyotetty < dateTiliAlku || dateSyotetty > dateTiliLoppu) {
            var msg = '".t("VIRHE: Syötetty päivämäärä ei sisälly kuluvaan tilikauteen!")."';
            alert(msg);

            skippaa_tama_submitti = true;
            return false;
          }

          if (ero >= 30) {
            var msg = '".t("Oletko varma, että haluat päivätä laskun yli 30pv menneisyyteen?")."';

            if (confirm(msg)) {
              return true;
            }
            else {
              skippaa_tama_submitti = true;
              return false;
            }
          }
          if (ero <= -14) {
            var msg = '".t("Oletko varma, että haluat päivätä laskun yli 14pv tulevaisuuteen?")."';

            if (confirm(msg)) {
              return true;
            }
            else {
              skippaa_tama_submitti = true;
              return false;
            }
          }

          if (vv < dateTallaHet.getFullYear()) {
            if (5 < dateTallaHet.getDate()) {
              var msg = '".t("Oletko varma, että haluat päivätä laskun menneisyyteen")."?';

              if (confirm(msg)) {
                return true;
              }
              else {
                skippaa_tama_submitti = true;
                return false;
              }
            }
          }
          else if (vv == dateTallaHet.getFullYear()) {
            if (kk < dateTallaHet.getMonth() && 5 < dateTallaHet.getDate()) {
              var msg = '".t("Oletko varma, että haluat päivätä laskun menneisyyteen")."?';

              if (confirm(msg)) {
                return true;
              }
              else {
                skippaa_tama_submitti = true;
                return false;
              }
            }
          }
        }

        $(function() {

          var maara = $('#maara').val();

          for (var i = 1; i < maara; i++) {
            $('#tiliointirivi_'+i).show();
            $('#tiliointirivi_hr_'+i).show();
          }

          $('#lisaa_uusi_tiliointirivi').on('click', function(event) {
            event.preventDefault();

            var maara = $('#maara').val();

            $('#tiliointirivi_hr_'+maara).show();
            $('#tiliointirivi_'+maara).show();

            maara++;


            $('#maara').val(maara);
          });

          $('#summa, #osto_rahti, #osto_kulu, #osto_rivi_kulu').on('keyup', function() {

            var summa_echotus = $('#summa').val(),
            osto_rahti = $('#osto_rahti').val(),
            osto_kulu = $('#osto_kulu').val(),
            osto_rivi_kulu = $('#osto_rivi_kulu').val();

            summa_echotus = parseFloat(summa_echotus.replace(',', '.')),
            osto_rahti = parseFloat(osto_rahti.replace(',', '.')),
            osto_kulu = parseFloat(osto_kulu.replace(',', '.')),
            osto_rivi_kulu = parseFloat(osto_rivi_kulu.replace(',', '.'));

            if ($('#osto_rahti').is(':visible') && $.isNumeric(osto_rahti)) {
              if (summa_echotus < 0) {
                summa_echotus = Math.abs(osto_rahti) - Math.abs(summa_echotus);
              }
              else {
                summa_echotus = Math.abs(summa_echotus) - Math.abs(osto_rahti);
              }
            }

            if ($('#osto_kulu').is(':visible') && $.isNumeric(osto_kulu)) {
              if (summa_echotus < 0) {
                summa_echotus = Math.abs(osto_kulu) - Math.abs(summa_echotus);
              }
              else {
                summa_echotus = Math.abs(summa_echotus) - Math.abs(osto_kulu);
              }
            }

            if ($('#osto_rivi_kulu').is(':visible') && $.isNumeric(osto_rivi_kulu)) {
              if (summa_echotus < 0) {
                summa_echotus = Math.abs(osto_rivi_kulu) - Math.abs(summa_echotus);
              }
              else {
                summa_echotus = Math.abs(summa_echotus) - Math.abs(osto_rivi_kulu);
              }
            }

            if (summa_echotus == '' || $.isNumeric(summa_echotus)) {
              $('#summa_echotus').html(summa_echotus);
            }
          });

          $('#vienti').on('change', function() {

            var val = $(this).val();

            if (val == 'C' || val == 'F' || val == 'I') {
              $('.ostolaskun_kulutilit').show();
              $('#summa').trigger('keyup');
            }
            else {
              $('.ostolaskun_kulutilit').hide();
              $('#summa_echotus').html($('#summa').val());
            }

            $.ajax({
              async: true,
              dataType: 'JSON',
              type: 'POST',
              data: {
                ajax_toiminto: 'hae_ostolaskun_tyypin_oletustili',
                vienti: $('#vienti').val(),
                toimittaja_tunnus: $('#toimittajaid').val()
              },
              url: 'ulask.php?no_head=yes'
            }).done(function(data) {
              //Jos vain yksi tiliointi rivi on näkyvissä, muutetaan sen arvo, jotta manuaalisesti tehdyn monen rivin ostolaskun tiliöinnit eivät ylikirjoitu
              if ($(\"tr[class='tiliointirivi']:visible\").length == 1) {
                var nakyvissa_oleva_tiliointirivi = $(\"tr[class='tiliointirivi']:visible\");
                $(nakyvissa_oleva_tiliointirivi[0]).find('input.tilinumero').val(data);
              }
            });
          });
        });
      </script>";

  if ($yhtiorow['skannatut_laskut_polku'] != '' and $nayta_skannattu_lasku != "") {
    echo "<table><tr><td class='back ptop'>";
  }

  if ($toimittajaid > 0) {

    $query = "SELECT * FROM toimi WHERE tunnus = '$toimittajaid'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) != 1) {
      echo t("Toimittajaa")." $ytunnus ".t("ei löytynytkään")."!";
      exit;
    }

    $trow = mysql_fetch_assoc($result);

    // Ei näytetä henkilötunnusta
    $trow["ytunnus_clean"] = tarkistahetu($trow["ytunnus"]);

    // Oletusarvot toimittajalta, jos ekaaa kertaa täällä
    if ($tee == 'P') {

      // Katsotaan onko meillä "tuuraajia" hyväksynnässä
      for ($tuuraaja_i = 1; $tuuraaja_i < 6; $tuuraaja_i++) {
        $query = "SELECT if (tuuraaja != '', tuuraaja, kuka) kuka
                  FROM kuka
                  WHERE yhtio  = '{$kukarow['yhtio']}'
                  AND kuka     = '{$trow['oletus_hyvak'.$tuuraaja_i]}'
                  AND kuka    != ''";
        $result = pupe_query($query);
        $hyvak_row = mysql_fetch_assoc($result);
        $hyvak[$tuuraaja_i] = $hyvak_row['kuka'];
      }

      $valkoodi       = $trow['oletus_valkoodi'];
      $kar            = $trow['oletus_kapvm'];
      $kapro          = $trow['oletus_kapro'];
      if ($tee2 != 'V')   $err = $trow['oletus_erapvm']; // Viivakoodilla on aina erapvm ja sitä käytetään
      $oltil          = $trow['tilino'];
      $olkustp        = $trow['kustannuspaikka'];
      $olkohde        = $trow['kohde'];
      $olprojekti     = $trow['projekti'];
      $osuoraveloitus    = $trow['oletus_suoraveloitus'];
      $ohyvaksynnanmuutos  = $trow['oletus_hyvaksynnanmuutos'];
      $vienti        = $trow['oletus_vienti'];
      $ohjeitapankille    = $trow['ohjeitapankille'];
    }

    // Tehdään konversio checkboxseja varten
    if ($osuoraveloitus != '') $osuoraveloitus = 'checked';
    if ($ohyvaksynnanmuutos != '') $ohyvaksynnanmuutos = 'checked';

    $fakta = "";
    if (trim($trow["fakta"]) != "") {
      $fakta = "<br><br><div style='font-weight: bold;'>$trow[fakta]</div>";
    }

    echo "<form name = 'lasku' action = '?tee=I&toimittajaid=$toimittajaid' method='post' enctype='multipart/form-data' onSubmit = 'return verify()'>";
    echo "<input type='hidden' name='lopetus' value='$lopetus'>";

    echo "<table><tr><td class='ptop' style='padding: 0px;'>";
    echo "<table>";
    echo "<tr><th colspan='2'>".t("Toimittaja")."</th></tr>";
    echo "<tr><td colspan='2'>$trow[nimi] $trow[nimitark] ($trow[ytunnus_clean])</td></tr>";
    echo "<tr><td colspan='2'>$trow[osoite] $trow[osoitetark], $trow[maa]-$trow[postino] $trow[postitp], $trow[maa] $fakta</td></tr>";

    $mlaskulias = "";

    if ($trow["tyyppi"] == "K") {
      $mlaskulias = "!!!KAYTTAJA!!!true";
    }

    echo "<tr><td><a href='yllapito.php?toim=toimi{$mlaskulias}&tunnus=$toimittajaid&lopetus=ulask.php////tee=$tee//toimittajaid=$toimittajaid//maara=$maara//nayta_skannattu_lasku=$nayta_skannattu_lasku'>".t("Muuta toimittajan tietoja")."</a></td></tr>";
    echo "</table>";
    echo "</td>";

    // eri tilitiedot riippuen onko suomalainen vai ei
    echo "<td class='ptop' style='padding: 0px;'>";
    echo "<table>";
    echo "<tr><th colspan='2'>".t("Tilitiedot")."</th></tr>";

    if (strtoupper($trow['maa']) != strtoupper($yhtiorow['maa'])) {

      $pankki = $trow['pankki1'];

      if ($trow['pankki2']!='') $pankki .= "<br>$trow[pankki2]";
      if ($trow['pankki3']!='') $pankki .= "<br>$trow[pankki3]";
      if ($trow['pankki4']!='') $pankki .= "<br>$trow[pankki4]";

      if ($trow['ultilno'] == '') { //Toimittajan tilinumero puuttuu. Annetaan sen syöttö
        echo "<tr><td>".t("Tilinumero")."</td><td><input type='text' name='toitilinumero' size=20 value='$toitilinumero'></td></tr>";
        echo "<tr><td>".t("BIC")."</td><td><input type='text' name='toiswift' size=10 value='$toiswift'></td></tr>";
      }
      else {
        echo "<tr><td>".t("Tilinumero")."</td><td>$trow[ultilno]</td></tr>";
        echo "<tr><td>".t("BIC")."</td><td>$trow[swift]</td></tr>";
        echo "<tr><td>".t("Pankki")."</td><td>$pankki</td></tr>";
      }
    }
    else {
      if ($trow['tilinumero'] == '' and $trow['ultilno'] == '') { //Toimittajan tilinumero puuttuu. Annetaan sen syöttö
        echo "<tr><td>".t("Tilinumero")."</td><td><input type='text' name='toitilinumero' size=20 value='$toitilinumero'></td></tr>";
        echo "<tr><td>".t("BIC")."</td><td><input type='text' name='toiswift' size=10 value='$toiswift'></td></tr>";
      }
      else {
        echo "<tr><td>".t("Tilinumero")."</td><td>$trow[tilinumero]</td></tr>";
        echo "<tr><td>".t("IBAN")."</td><td>$trow[ultilno]</td></tr>";
        echo "<tr><td>".t("BIC")."</td><td>$trow[swift]</td></tr>";
      }
    }
    echo "</table>";
    echo "</td>";

    // tulostetaan mahdolliset errorimessaget
    if ($errormsg != '') {
      echo "<td class='back' valign='top'>$errormsg</td>";
    }
    else {
      // Näytetään neljä viimeisintä laskua, jotta vähennetään duplikaattien tallennusta
      $query = "SELECT tapvm, summa
                FROM lasku
                WHERE yhtio = '$kukarow[yhtio]'
                and tila    in ('H', 'M')
                and ytunnus = '$trow[ytunnus]'
                and tapvm   > date_sub(now(), interval 30 day)
                ORDER BY tapvm desc
                LIMIT 4";
      $vikatlaskutres = pupe_query($query);

      if (mysql_num_rows($vikatlaskutres) > 0) {
        echo "<td valign='top' style='padding: 0px;'>";
        echo "<table><tr>";
        echo "<th>".t("Pvm")."</th>";
        echo "<th>".t("Summa")."</th>";
        echo "</tr>";

        while ($vikatlaskutrow = mysql_fetch_assoc($vikatlaskutres)) {
          echo "<tr>";
          echo "<td>".tv1dateconv($vikatlaskutrow["tapvm"])."</td>";
          echo "<td>$vikatlaskutrow[summa]</td>";
          echo "</tr>";
        }

        echo "</table>";
        echo "</td>";
      }
    }

    echo "</tr></table>";
  }
  else {

    // jaaha, ei ollut toimittajaa, joten pyydetään syöttämään tiedot
    echo "<form name = 'lasku' action = '?tee=I&toimittajaid=$toimittajaid' method='post' enctype='multipart/form-data' onSubmit = 'return verify()'>";
    echo "<input type='hidden' name='lopetus' value='$lopetus'>";
    echo "<input type='hidden' name='oma' value='1'>";
    echo "<input type='hidden' name='tyyppi' value='$tyyppi'>";

    if ($errormsg != '') echo "$errormsg<br>";

    if ($tyyppi != strtoupper($yhtiorow['maa'])) {
      echo "
        <font class='message'>".t("Ulkomaalaisen toimittajan tiedot")."</font>
        <table><tr><td class='back' valign='top'>

        <table>
        <tr><th>".t("ytunnus")."</th>  <td><input type='text' name='trow[ytunnus]'    maxlength='16'  size=18 value='$trow[ytunnus]'></td></tr>
        <tr><th>".t("nimi")."</th>    <td><input type='text' name='trow[nimi]'       maxlength='45' size=45 value='$trow[nimi]'></td></tr>
        <tr><th>".t("nimitark")."</th>  <td><input type='text' name='trow[nimitark]'   maxlength='45' size=45 value='$trow[nimitark]'></td></tr>
        <tr><th>".t("osoite")."</th>    <td><input type='text' name='trow[osoite]'     maxlength='45' size=45 value='$trow[osoite]'></td></tr>
        <tr><th>".t("osoitetark")."</th>  <td><input type='text' name='trow[osoitetark]' maxlength='45' size=45 value='$trow[osoitetark]'></td></tr>
        <tr><th>".t("postino")."</th>  <td><input type='text' name='trow[postino]'    maxlength='15' size=10 value='$trow[postino]'></td></tr>
        <tr><th>".t("postitp")."</th>  <td><input type='text' name='trow[postitp]'    maxlength='45' size=45 value='$trow[postitp]'></td></tr>
        </table>

        </td><td class='back'>

        <table>";

      $query = "SELECT DISTINCT koodi, nimi
                FROM maat
                WHERE nimi != ''
                ORDER BY koodi";
      $vresult = pupe_query($query);

      echo "<tr><th valign='top'> ".t("Maa").": </th>
            <td><select name='trow[maa]' ".js_alasvetoMaxWidth("maa", 400).">";

      while ($vrow = mysql_fetch_assoc($vresult)) {
        $sel = "";
        if (strtoupper($trow["maa"]) == strtoupper($vrow["koodi"])) {
          $sel = "selected";
        }
        echo "<option value = '".strtoupper($vrow["koodi"])."' $sel>".t($vrow["nimi"])."</option>";
      }

      echo "</select></td></tr>";

      echo "
        <tr><th>".t("IBAN")."</th>    <td><input type='text' name='trow[ultilno]'  maxlength='35' size=45 value='$trow[ultilno]'></td></tr>
        <tr><th>".t("SWIFT")."</th>    <td><input type='text' name='trow[swift]'    maxlength='11' size=45 value='$trow[swift]'></td></tr>
        <tr><th>".t("pankki1")."</th>  <td><input type='text' name='trow[pankki1]'  maxlength='35' size=45 value='$trow[pankki1]'></td></tr>
        <tr><th>".t("pankki2")."</th>  <td><input type='text' name='trow[pankki2]'  maxlength='35' size=45 value='$trow[pankki2]'></td></tr>
        <tr><th>".t("pankki3")."</th>  <td><input type='text' name='trow[pankki3]'  maxlength='35' size=45 value='$trow[pankki3]'></td></tr>
        <tr><th>".t("pankki4")."</th>  <td><input type='text' name='trow[pankki4]'  maxlength='35' size=45 value='$trow[pankki4]'></td></tr>
        </table>

        </td></tr></table>";
    }
    else {
      echo "
        <font class='message'>".t("Kotimaisen toimittajan tiedot")."</font>
        <input type='hidden' name = 'trow[maa]' value = ".strtoupper($yhtiorow['maa']).">
        <table>
        <tr><th>".t("ytunnus")."</th>  <td><input type='text' name='trow[ytunnus]'    maxlength='8'  size=10 value='$trow[ytunnus]'></td></tr>
        <tr><th>".t("nimi")."</th>    <td><input type='text' name='trow[nimi]'       maxlength='45' size=45 value='$trow[nimi]'></td></tr>
        <tr><th>".t("nimitark")."</th>  <td><input type='text' name='trow[nimitark]'   maxlength='45' size=45 value='$trow[nimitark]'></td></tr>
        <tr><th>".t("osoite")."</th>  <td><input type='text' name='trow[osoite]'     maxlength='45' size=45 value='$trow[osoite]'></td></tr>
        <tr><th>".t("osoitetark")."</th><td><input type='text' name='trow[osoitetark]' maxlength='45' size=45 value='$trow[osoitetark]'></td></tr>
        <tr><th>".t("postino")."</th>  <td><input type='text' name='trow[postino]'    maxlength='5'  size=10 value='$trow[postino]'></td></tr>
        <tr><th>".t("postitp")."</th>  <td><input type='text' name='trow[postitp]'    maxlength='45' size=45 value='$trow[postitp]'></td></tr>
        <tr><th>".t("Tilinumero")."</th><td><input type='text' name='trow[tilinumero]' maxlength='45' size=45 value='$trow[tilinumero]'></td></tr>
        <tr><th>".t("IBAN")."</th>    <td><input type='text' name='trow[ultilno]'    maxlength='35' size=45 value='$trow[ultilno]'></td></tr>
        <tr><th>".t("SWIFT")."</th>    <td><input type='text' name='trow[swift]'      maxlength='11' size=45 value='$trow[swift]'></td></tr>
        </table>";
    }

    echo "<br>";
  }

  // Kursorin oletuspaikka
  $formi = 'lasku';
  if ($yhtiorow['ostolaskujen_paivays'] == "1") {
    $kentta = 'vpp';
  }
  else {
    $kentta = 'tpp';
  }

  echo "<table>";

  if ($yhtiorow['ostolaskujen_paivays'] == "1") {

    if ($tpp == '') {
      $tpp = date('d');
    }
    if ($tpk == '') {
      $tpk = date('m');
    }
    if ($tpv == '') {
      $tpv = date('Y');
    }

    echo "  <tr>";
    echo "  <td>".t("Laskun päiväys")."</td>
        <td>
        <input type='text' name='vpp' maxlength='2' size=2 value='$vpp' tabindex='1'>
        <input type='text' name='vpk' maxlength='2' size=2 value='$vpk' tabindex='2'>
        <input type='text' name='vpv' maxlength='4' size=4 value='$vpv' tabindex='3'> ".t("ppkkvvvv")."</td>";
    echo "  </tr>";
    echo "  <tr>";
    echo "  <td>".t("Kirjauspäivä")."</td>";
  }
  else {
    echo "  <tr>";
    echo "  <td>".t("Laskun päiväys")."</td>";
  }

  if ($yhtiorow['ostolaskujen_paivays'] == "1") {
    echo "  <td>
        <input type='text' name='tpp' maxlength='2' size=2 value='$tpp' tabindex='-1'>
        <input type='text' name='tpk' maxlength='2' size=2 value='$tpk' tabindex='-1'>
        <input type='text' name='tpv' maxlength='4' size=4 value='$tpv' tabindex='-1'> ".t("ppkkvvvv")."</td>";

    echo "  </tr>";
  }
  else {
    echo "  <td>
        <input type='text' name='tpp' maxlength='2' size=2 value='$tpp' tabindex='1'>
        <input type='text' name='tpk' maxlength='2' size=2 value='$tpk' tabindex='2'>
        <input type='text' name='tpv' maxlength='4' size=4 value='$tpv' tabindex='3'> ".t("ppkkvvvv")."</td>";

    echo "  </tr>";
  }
  echo "<tr>
      <td>".t("Eräpvm")."</td><td><input type='text' name='erp' maxlength='2' size=2 value='$erp' tabindex='4'>
      <input type='text' name='erk' maxlength='2' size=2 value='$erk' tabindex='5'>
      <input type='text' name='erv' maxlength='4' size=4 value='$erv' tabindex='6'> ".t("ppkkvvvv tai")."
      <input type='text' name='err' maxlength='3' size=2 value='$err' tabindex='7'> ".t("päivää tai suoraveloitus")."
      <input tabindex='-1' type='checkbox' name='osuoraveloitus' $osuoraveloitus>
      </td>
      </tr>";

  echo "<tr><td>".t("Laskun summa")."</td>";
  echo "<td><input type='text' name='summa' id='summa' value='{$summa}' tabindex='9'>";

  //Tehdään valuuttapopup, jos ulkomainen toimittaja muuten kirjoitetaan vain $yhtiorow[valkoodi]
  if ((is_array($trow) and strtoupper($trow['maa']) != strtoupper($yhtiorow['maa'])) or (!is_array($trow) and $tyyppi != strtoupper($yhtiorow['maa']))) {

    $query = "SELECT nimi
              FROM valuu
              WHERE yhtio = '$kukarow[yhtio]'
              ORDER BY jarjestys";
    $vresult = pupe_query($query);

    echo "<select name='valkoodi'>";

    while ($vrow = mysql_fetch_assoc($vresult)) {
      $sel = "";
      if ($valkoodi == $vrow['nimi']) {
        $sel = "selected";
      }
      echo "<option value ='$vrow[nimi]' $sel>$vrow[nimi]</option>";
    }
    echo "</select>";
  }
  else {
    echo "<input type='hidden' name='valkoodi' value='$yhtiorow[valkoodi]'> $yhtiorow[valkoodi]";
  }

  echo "&nbsp;&nbsp;&nbsp;<font id='summa_color'><span id='summa_echotus'></span></font>";
  echo "</td></tr>";

  echo "<tr>
      <td>".t("Viite")."</td><td><input type='text'  maxlength='20' size='25' name='viite' value='$viite' tabindex='9'>
    </tr>
    <tr>
      <td>".t("Kassaeräpvm")."</td><td>
      <input type='text' name='kap' maxlength='2' size=2 value='$kap' tabindex='10'>
      <input type='text' name='kak' maxlength='2' size=2 value='$kak' tabindex='11'>
      <input type='text' name='kav' maxlength='4' size=4 value='$kav' tabindex='12'> ".t("ppkkvvvv tai")."
      <input type='text' name='kar' maxlength='3' size=2 value='$kar' tabindex='13'> ".t("päivää")."
      </td>
    </tr>
    <tr>
      <td>".t("Kassa-alennus")."</td><td><input type='text' name='kassaale' value='$kassaale' tabindex='14'>
      <input type='text' name='kapro' maxlength='6' size=6 value='$kapro' tabindex='15'>%</td>
    </tr>
    <tr>
      <td>".t("Viesti")."</td><td><input type='text' maxlength='70' size='60' name='viesti' value='$viesti' tabindex='16'></td>
    </tr>

    <tr>
      <td>".t("Kommentti")."</td><td><input type='text' name='komm' size='60' value='$komm' tabindex='17'></td>
    </tr>
    <tr>
      <td>".t("Laskunumero")."</td><td><input type='text' name='toimittajan_laskunumero' value='$toimittajan_laskunumero' size='60' tabindex='18'></td>
    </tr>";

  if ((is_array($trow) and strtoupper($trow['maa']) != strtoupper($yhtiorow['maa'])) or (!is_array($trow) and $tyyppi != strtoupper($yhtiorow['maa']))) {
    echo "<tr>
        <td>".t("Ohjeita pankille")."</td><td><textarea name='ohjeitapankille' rows='2' cols='58'>$ohjeitapankille</textarea></td>
       </tr>";
  }

  if ($yhtiorow['toimipaikkakasittely'] == "L" and $toimipaikat_res = hae_yhtion_toimipaikat($kukarow['yhtio']) and mysql_num_rows($toimipaikat_res) > 0) {

    echo "<tr>";
    echo "<td>", t("Toimipaikka"), "</td>";
    echo "<td>";
    echo "<select name='toimipaikka'>";
    echo "<option value='0'>", t("Ei toimipaikkaa"), "</option>";

    while ($toimipaikat_row = mysql_fetch_assoc($toimipaikat_res)) {

      $sel = '';

      if (isset($toimipaikka)) {
        if ($toimipaikka == $toimipaikat_row['tunnus']) {
          $sel = ' selected';
          $toimipaikka = $toimipaikat_row['tunnus'];
        }
      }
      else {
        if ($kukarow['toimipaikka'] == $toimipaikat_row['tunnus']) {
          $sel = ' selected';
          $toimipaikka = $toimipaikat_row['tunnus'];
        }
      }

      echo "<option value='{$toimipaikat_row['tunnus']}'{$sel}>{$toimipaikat_row['nimi']}</option>";
    }

    echo "</select>";
    echo "</td>";
    echo "</tr>";
  }

  if ($vienti == 'A') $vientia = 'selected';
  if ($vienti == 'B') $vientib = 'selected';
  if ($vienti == 'C') $vientic = 'selected';
  if ($vienti == 'D') $vientid = 'selected';
  if ($vienti == 'E') $vientie = 'selected';
  if ($vienti == 'F') $vientif = 'selected';
  if ($vienti == 'G') $vientig = 'selected';
  if ($vienti == 'H') $vientih = 'selected';
  if ($vienti == 'I') $vientii = 'selected';
  if ($vienti == 'J') $vientij = 'selected';
  if ($vienti == 'K') $vientik = 'selected';
  if ($vienti == 'L') $vientil = 'selected';

  echo "
    <tr>
      <td>".t("Laskun tyyppi")."</td><td>
        <select name='vienti' id='vienti' tabindex='19'>
          <option value='A' $vientia>".t("Kotimaa")."</option>
          <option value='B' $vientib>".t("Kotimaa huolinta/rahti")."</option>
          <option value='C' $vientic>".t("Kotimaa vaihto-omaisuus")."</option>
          <option value='J' $vientij>".t("Kotimaa raaka-aine")."</option>

          <option value='D' $vientid>".t("EU")."</option>
          <option value='E' $vientie>".t("EU huolinta/rahti")."</option>
          <option value='F' $vientif>".t("EU vaihto-omaisuus")."</option>
          <option value='K' $vientik>".t("EU raaka-aine")."</option>

          <option value='G' $vientig>".t("ei-EU")."</option>
          <option value='H' $vientih>".t("ei-EU huolinta/rahti")."</option>
          <option value='I' $vientii>".t("ei-EU vaihto-omaisuus")."</option>
          <option value='L' $vientil>".t("ei-EU raaka-aine")."</option>
        </select>
      </td>
    </tr>";

  if ($yhtiorow['ostolaskun_kulutilit'] != '' and ($yhtiorow['osto_rahti'] != '' or $yhtiorow['osto_kulu'] != '' or $yhtiorow['osto_rivi_kulu'] != '') and ($vienti == 'C' or $vienti == 'F' or $vienti == 'I')) {

    if (strtoupper($trow['maa']) == strtoupper($yhtiorow['maa'])) {
      $osto_rahti_alv = $osto_kulu_alv = $osto_rivi_kulu_alv = alv_oletus();
    }
    else {
      $osto_rahti_alv = $osto_kulu_alv = $osto_rivi_kulu_alv = 0;
    }

    for ($i = 1; $i <= $yhtiorow['ostolaskun_kulutilit']; $i++) {
      echo "<tr class='ostolaskun_kulutilit'>";

      switch ($i) {
      case '1':
        if (!isset($osto_rahti)) $osto_rahti = '';

        echo "<td>", t("Rahdit"), "</td>";
        echo "<td>";
        echo "<select name='osto_rahti_alv_verollisuus'>";
        echo "<option value=''>", t("Verollinen arvo"), "</option>";
        echo "<option value='X'>", t("Veroton arvo"), "</option>";
        echo "</select>&nbsp;";
        echo "<input type='text' name='osto_rahti' id='osto_rahti' tabindex='20' value='{$osto_rahti}' />";
        echo "&nbsp;".alv_popup('osto_rahti_alv', $osto_rahti_alv)."</td>";
        break;
      case '2':
        if (!isset($osto_kulu)) $osto_kulu = '';

        echo "<td>", t("Kulut"), "</td>";
        echo "<td>";
        echo "<select name='osto_kulu_alv_verollisuus'>";
        echo "<option value=''>", t("Verollinen arvo"), "</option>";
        echo "<option value='X'>", t("Veroton arvo"), "</option>";
        echo "</select>&nbsp;";
        echo "<input type='text' name='osto_kulu' id='osto_kulu' tabindex='21' value='{$osto_kulu}' />";
        echo "&nbsp;".alv_popup('osto_kulu_alv', $osto_kulu_alv)."</td>";
        break;
      case '3':
        if (!isset($osto_rivi_kulu)) $osto_rivi_kulu = '';

        echo "<td>", t("Rivikohtaiset kulut"), "</td>";
        echo "<td>";
        echo "<select name='osto_rivi_kulu_alv_verollisuus'>";
        echo "<option value=''>", t("Verollinen arvo"), "</option>";
        echo "<option value='X'>", t("Veroton arvo"), "</option>";
        echo "</select>&nbsp;";
        echo "<input type='text' name='osto_rivi_kulu' id='osto_rivi_kulu' tabindex='22' value='{$osto_rivi_kulu}' />";
        echo "&nbsp;".alv_popup('osto_rivi_kulu_alv', $osto_rivi_kulu_alv)."</td>";
        break;
      }

      echo "</tr>";
    }

  }

  // tutkitaan ollaanko jossain toimipaikassa alv-rekisteröity
  $query = "SELECT *
            FROM yhtion_toimipaikat
            WHERE yhtio     = '$kukarow[yhtio]'
            and maa        != ''
            and vat_numero != ''
            and toim_alv   != ''";
  $alhire = pupe_query($query);

  $tilino_alv_hidden = "";

  // ollaan alv-rekisteröity
  if (mysql_num_rows($alhire) >= 1) {

    if ($tilino_alv == "") {
      $tilino_alv = $trow["tilino_alv"];
    }

    echo "<tr>";
    echo "<td>".t("Alv tili")."</td><td>";
    echo "<select name='tilino_alv' tabindex='23'>";
    echo "<option value='$yhtiorow[alv]'>$yhtiorow[alv] - $yhtiorow[nimi], $yhtiorow[kotipaikka], $yhtiorow[maa]</option>";

    while ($vrow = mysql_fetch_assoc($alhire)) {
      $sel = "";
      if ($tilino_alv == $vrow['toim_alv']) {
        $sel = "selected";
      }
      echo "<option value='$vrow[toim_alv]' $sel>$vrow[toim_alv] - $vrow[nimi], $vrow[kotipaikka], $vrow[maa]</option>";
    }

    echo "</select>";
    echo "</td>";
    echo "</tr>";
  }
  else {
    $tilino_alv = $yhtiorow["alv"];
    $tilino_alv_hidden = "<input type='hidden' name='tilino_alv' value='$tilino_alv'>";
  }

  echo "<tr>";
  echo "<td>".t("Laskun kuva")."$tilino_alv_hidden</td>";

  if ($kuva1 or $kuva2 or $kuva3) {
    echo "<td>".t("Kuva jo tallessa")."!";

    if ($kuva1) {
      echo "<input name='kuva1' type='hidden' value = '$kuva1'>";
    }
    if ($kuva2) {
      echo "<input name='kuva2' type='hidden' value = '$kuva2'>";
    }
    if ($kuva3) {
      echo "<input name='kuva3' type='hidden' value = '$kuva3'>";
    }

    echo "</td>";
  }
  elseif ($yhtiorow['skannatut_laskut_polku'] != '' and $nayta_skannattu_lasku != "") {
    echo "<td>".t("Kts. oikealle")."!</td>";
  }
  else {
    echo "<td><input type='hidden' name='MAX_FILE_SIZE' value='50000000'>
            <input name='userfile1' type='file' tabindex='-1'><br>
            <input name='userfile2' type='file' tabindex='-1'><br>
            <input name='userfile3' type='file' tabindex='-1'>
          </td>";
  }

  echo "</tr>";

  if (laskun_hyvaksyjia()) {
    
    echo "<tr><td colspan='2'><hr></td></tr>";
    echo "<tr><td valign='top'>".t("Hyväksyjät")."</td><td>";

    $query = "SELECT DISTINCT kuka.kuka, kuka.nimi
              FROM kuka
              JOIN oikeu ON oikeu.yhtio = kuka.yhtio and oikeu.kuka = kuka.kuka and oikeu.nimi like '%hyvak.php'
              WHERE kuka.yhtio    = '$kukarow[yhtio]'
              AND kuka.aktiivinen = 1
              AND kuka.extranet   = ''
              and kuka.hyvaksyja  = 'o'
              ORDER BY kuka.nimi";
    $vresult = pupe_query($query);

    $ulos = '';
    // Täytetään 5 hyväksyntäkenttää
    for ($i=1; $i<6; $i++) {

      while ($vrow = mysql_fetch_assoc($vresult)) {
        $sel = "";
        if ($hyvak[$i] == $vrow['kuka']) {
          $sel = "selected";
        }
        $ulos .= "<option value ='$vrow[kuka]' $sel>$vrow[nimi]";
      }

      // Käydään sama data läpi uudestaan
      if (!mysql_data_seek($vresult, 0)) {
        echo "mysql_data_seek failed!";
        exit;
      }

      echo "<select name='hyvak[$i]' tabindex='24'>
          <option value = ' '>".t("Ei kukaan")."
          $ulos
          </select>";
      $ulos="";

      // Tehdään checkbox, jolla annetaan lupa muuttaa hyväksyntälistaa myöhemmin
      if ($i == 1) {
        echo " ".t("Listaa saa muuttaa")." <input type='checkbox' name='ohyvaksynnanmuutos' $ohyvaksynnanmuutos tabindex='-1'>";
      }
      echo "<br>";
    }

    echo "</td></tr>";
  }

  echo "<tr><td colspan='2'>";

  $uusiselke = "";

  if ($luouusikeikka != '') {
    $uusiselke = "CHECKED";
  }

  echo "<hr><table>";
  echo "<tr><td>".t("Luo uusi saapuminen laskulle").":</td><td><input type='checkbox' name='luouusikeikka' value='LUO' $uusiselke tabindex='-1'></td>";
  echo "<td>".t("Kopio lasku")."</td><td><input type='input' name='kopioi' value='$kopioi' size='3' maxlength='2' tabindex='-1'></td><td>".t("kertaa")."</td></tr>";
  echo "</table>";
  echo "</td>";
  echo "</tr>";

  echo "<tr><td colspan='2'>";

  // Hoidetaan oletukset!
  for ($i = 1; $i < 50; $i++) {
    if ($i == 1 and strlen($itili[$i]) == 0) {
      $itili[$i] = $oltil;
    }
    if ($i == 1 and strlen($ikohde[$i]) == 0) {
      $ikohde[$i] = $olkohde;
    }
    if ($i == 1 and strlen($iprojekti[$i]) == 0) {
      $iprojekti[$i] = $olprojekti;
    }
    if ($i == 1 and strlen($ikustp[$i]) == 0) {
      $ikustp[$i] = $olkustp;
    }
    if (strlen($ivero[$i]) == 0) {
      if (strtoupper($trow['maa']) == strtoupper($yhtiorow['maa'])) {
        $ivero[$i] = alv_oletus($oltil);
      }
      else {
        $ivero[$i] = 0;
      }
    }
  }

  // ykköstasolla ei saa tehdä tiliöintejä, laitetaan oletukset
  if ($kukarow['taso'] < '2') {

    // Jos toimittajalla ei ollut oletustiliä, haetaan se yritykseltä
    if ($itili[1] == '' or $itili[1] == 0) $itili[1] = $yhtiorow['muutkulut'];

    echo "<input type='hidden' value='$itili[1]'    name='itili[1]'>
        <input type='hidden' value='$ikohde[1]'    name='ikohde[1]'>
        <input type='hidden' value='$iprojekti[1]'  name='iprojekti[1]'>
        <input type='hidden' value='$ikustp[1]'    name='ikustp[1]'>
        <input type='hidden' value='$ivero[1]'    name='ivero[1]'>";
  }
  else {

    // Tehdään haluttu määrä tiliöintirivejä
    $syottotyyppisaldo='checked';
    $syottotyyppiprosentti='';
    if (isset($syottotyyppi)) {
      if ($syottotyyppi=='prosentti') $syottotyyppiprosentti = 'checked';
    }

    echo "<hr>
      <table>
        <tr>
          <th>".t("Tili")."</th>
          <th>".t("Kustannuspaikka")."</th>
          <th><input type='radio' name='syottotyyppi' value='summa' $syottotyyppisaldo tabindex='-1'>".t("Summa")."
          <input type='radio' name='syottotyyppi' value='prosentti' $syottotyyppiprosentti tabindex='-1'>".t("Prosentti")."</th>
          <th style='text-align:right;'>".t("Vero")."</th>
        </tr>";

    for ($i = 1; $i < 50; $i++) {

      echo "<tr id='tiliointirivi_{$i}' class='tiliointirivi' style='display:none;'><td valign='top'>";

      // Tehaan kentta tai naytetaan popup
      if ($iulos[$i] == '') {
        echo livesearch_kentta("lasku", "TILIHAKU", "itili[$i]", 170, $itili[$i], "EISUBMIT", "ivero[$i]", 'tilinumero');
      }
      else {
        echo "$iulos[$i]";
      }

      // Etsitään selväkielinen tilinnimi, jos sellainen on
      if (strlen($itili[$i]) != 0) {
        $query = "SELECT nimi
                  FROM tili
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tilino  = '$itili[$i]'";
        $vresult = pupe_query($query);

        if (mysql_num_rows($vresult) != 0) {
          $vrow = mysql_fetch_assoc($vresult);
          echo "<br>$vrow[nimi]";
        }
      }

      echo "</td>";

      // Tehdään kustannuspaikkapopup
      $query = "SELECT tunnus, nimi, koodi
                FROM kustannuspaikka
                WHERE yhtio   = '$kukarow[yhtio]'
                and tyyppi    = 'K'
                and kaytossa != 'E'
                ORDER BY koodi+0, koodi, nimi";
      $vresult = pupe_query($query);

      echo "<td valign='top'>";

      if (mysql_num_rows($vresult) > 0) {
        echo "<select name='ikustp[$i]'>";
        echo "<option value =' '>".t("Ei kustannuspaikkaa");

        while ($vrow = mysql_fetch_assoc($vresult)) {
          $sel = "";
          if ($ikustp[$i] == $vrow['tunnus']) {
            $sel = "selected";
          }
          echo "<option value ='$vrow[tunnus]' $sel>$vrow[koodi] $vrow[nimi]</option>";
        }

        echo "</select><br>";
      }

      // Tehdään kohdepopup
      $query = "SELECT tunnus, nimi, koodi
                FROM kustannuspaikka
                WHERE yhtio   = '$kukarow[yhtio]'
                and tyyppi    = 'O'
                and kaytossa != 'E'
                ORDER BY koodi+0, koodi, nimi";
      $vresult = pupe_query($query);

      if (mysql_num_rows($vresult) > 0) {
        echo "<select name='ikohde[$i]'>";
        echo "<option value =' '>".t("Ei kohdetta");

        while ($vrow = mysql_fetch_assoc($vresult)) {
          $sel = "";
          if ($ikohde[$i] == $vrow['tunnus']) {
            $sel = "selected";
          }
          echo "<option value ='$vrow[tunnus]' $sel>$vrow[koodi] $vrow[nimi]</option>";
        }
        echo "</select><br>";
      }

      // Tehdään projektipopup
      $query = "SELECT tunnus, nimi, koodi
                FROM kustannuspaikka
                WHERE yhtio   = '$kukarow[yhtio]'
                and tyyppi    = 'P'
                and kaytossa != 'E'
                ORDER BY koodi+0, koodi, nimi";
      $vresult = pupe_query($query);

      if (mysql_num_rows($vresult) > 0) {

        echo "<select name='iprojekti[$i]'>";
        echo "<option value =' '>".t("Ei projektia");

        while ($vrow = mysql_fetch_assoc($vresult)) {
          $sel = "";
          if ($iprojekti[$i] == $vrow['tunnus']) {
            $sel = "selected";
          }
          echo "<option value ='$vrow[tunnus]' $sel>$vrow[koodi] $vrow[nimi]</option>";
        }
        echo "</select>";
      }

      echo "</td>";

      echo "<td valign='top'><input type='text' name='isumma[$i]' value='$isumma[$i]'></td>";
      echo "<td valign='top'>" . alv_popup('ivero['.$i.']', $ivero[$i]);
      echo " $ivirhe[$i]";
      echo "</td></tr>";
      echo "<tr id='tiliointirivi_hr_{$i}' style='display:none;'><td colspan='4'><hr></td></tr>";
    }

    echo "<tr><td colspan='4'><a href='#' id='lisaa_uusi_tiliointirivi'>", t("Lisää tiliöintirivi"), "</a></td></tr>";

    echo "</table>";

  } // end taso < 2

  echo "</td></tr>
    </table>";

  if ($yhtiorow['skannatut_laskut_polku'] != '' and $nayta_skannattu_lasku != "") {
    $skannatut_laskut_polku = rtrim($yhtiorow['skannatut_laskut_polku'], '/').'/';

    echo "</td><td class='back' width='100%'><font class='message'>", t("Skannattu lasku"), "</font>";
    echo "<div style='height: 100%; overflow: auto; width: 100%;'>";
    echo "<input type='hidden' name='nayta_skannattu_lasku' value='$nayta_skannattu_lasku'>";
    echo "<iframe src='{$skannatut_laskut_polku}{$nayta_skannattu_lasku}' style='width:100%; height: 800px; border: 0px; display: block;'></iFrame>";
    echo "</div>";
    echo "</td></tr></table>";
  }

  echo "<br>
    <input type = 'hidden' id='toimittajaid' name = 'toimittajaid' value = '$toimittajaid'>
    <input type = 'hidden' name = 'maara' id='maara' value = '$maara'>
    <input type = 'submit' value = '".t("Perusta")."' tabindex='-1'></form>";

} // end if tee = 'P'

if ($tee == 'I') {

  // Kurssiksi halutaan tämän päivän kurssi
  if ($yhtiorow["ostolaskujen_kurssipaiva"] == 0 or strtoupper($yhtiorow["valkoodi"]) == strtoupper($valkoodi)) {
    $query = "SELECT kurssi FROM valuu WHERE nimi = '$valkoodi' AND yhtio = '$kukarow[yhtio]'";
  }
  else {
    // Jos käytetään kirjauksessa laskun päiväystä sekä kirjauspäivää
    if ($yhtiorow['ostolaskujen_paivays'] == 1) {
      // Kurssiksi halutaan kirjauspäivä
      if ($yhtiorow["ostolaskujen_kurssipaiva"] == 1) {
        $ostolaskun_valuuttalaskujen_kurssipaiva = "$tpv-$tpk-$tpp";
      }
      // Kurssiksi halutaan laskun päivä
      else {
        $ostolaskun_valuuttalaskujen_kurssipaiva = "$vpv-$vpk-$vpp";
      }
    }
    else {
      // Kurssiksi halutaan laskunpäivä (tai ehkä halutaan kirjauspäivä, mutta käytössä ei ole erillisiä laskun kirjauspäivä/laskunpäivä)
      $ostolaskun_valuuttalaskujen_kurssipaiva = "$tpv-$tpk-$tpp";
    }

    // koitetaan hakea oikean päivän kurssi
    $query = "SELECT *
              FROM valuu_historia
              WHERE kotivaluutta = '$yhtiorow[valkoodi]'
              AND valuutta       = '$valkoodi'
              AND kurssipvm      <= '$ostolaskun_valuuttalaskujen_kurssipaiva'
              ORDER BY kurssipvm DESC
              LIMIT 1";
  }

  $result = pupe_query($query);

  if (mysql_num_rows($result) != 1) {
    echo t("Valuuttaa")." $valkoodi ".t("ei löytynytkään")."!";
    exit;
  }

  $vrow = mysql_fetch_assoc($result);

  $tila = "M";
  $hyvak[5] = trim($hyvak[5]);
  $hyvak[4] = trim($hyvak[4]);
  $hyvak[3] = trim($hyvak[3]);
  $hyvak[2] = trim($hyvak[2]);
  $hyvak[1] = trim($hyvak[1]);

  if (strlen($hyvak[5]) > 0) {
    $hyvaksyja_nyt=$hyvak[5];
    $tila = "H";
  }
  if (strlen($hyvak[4]) > 0) {
    $hyvaksyja_nyt=$hyvak[4];
    $tila = "H";
  }
  if (strlen($hyvak[3]) > 0) {
    $hyvaksyja_nyt=$hyvak[3];
    $tila = "H";
  }
  if (strlen($hyvak[2]) > 0) {
    $hyvaksyja_nyt=$hyvak[2];
    $tila = "H";
  }
  if (strlen($hyvak[1]) > 0) {
    $hyvaksyja_nyt=$hyvak[1];
    $tila = "H";
  }

  $olmapvm = $erv . "-" . $erk . "-" . $erp;

  if (strlen(trim($kap)) > 0) {
    $olmapvm = $kav . "-" . $kak . "-" . $kap;
  }

  // Jotkut maat (kaikki paitsi Suomi) vaativat toistaiseksi toistenroita :( Taklataan sitä tässä
  $tositenro=0;

  if ($kpexport == 1 or strtoupper($yhtiorow['maa']) != 'FI') {
    $query = "LOCK TABLE tiliointi WRITE, lasku WRITE, sanakirja WRITE, liitetiedostot WRITE, tili READ";
    $result = pupe_query($query);

    $alaraja = 41000000;
    $ylaraja = 42000000;

    $query  = "SELECT max(tosite) + 1 nro
               FROM tiliointi
               WHERE yhtio = '$kukarow[yhtio]'
               and tosite  > $alaraja
               and tosite  < $ylaraja";
    $tresult = pupe_query($query);
    $tositenrorow = mysql_fetch_assoc($tresult);

    if ($tositenrorow['nro'] < $alaraja) $tositenrorow['nro'] = 41000001;
    $tositenro=$tositenrorow['nro'];
  }

  if ($komm != "") {
    $komm = "(" . $kukarow['nimi'] . "@" . date('Y-m-d') .") " . trim($komm);
  }

  if ($kuva1 or $kuva2 or $kuva3) {
    $ebid = '';
  }

  // Jos viitettä ei syötetty ja laskunumero on syötetty lisätään se viestiin
  if ($viite == "" and $toimittajan_laskunumero != "") {
    $viesti = $toimittajan_laskunumero." ".$viesti;
  }

  if (!isset($osto_rahti))    $osto_rahti    = 0;
  if (!isset($osto_kulu))    $osto_kulu    = 0;
  if (!isset($osto_rivi_kulu)) $osto_rivi_kulu = 0;

  $osto_rahti   = (float) $osto_rahti;
  $osto_kulu     = (float) $osto_kulu;
  $osto_rivi_kulu = (float) $osto_rivi_kulu;

  $osto_rahti_alv   = (float) $osto_rahti_alv;
  $osto_kulu_alv     = (float) $osto_kulu_alv;
  $osto_rivi_kulu_alv = (float) $osto_rivi_kulu_alv;

  if (isset($osto_kulu_alv_verollisuus) and $osto_kulu_alv_verollisuus == 'X') {
    $osto_kulu = $osto_kulu * (1 + ($osto_kulu_alv / 100));
  }

  if (isset($osto_rahti_alv_verollisuus) and $osto_rahti_alv_verollisuus == 'X') {
    $osto_rahti = $osto_rahti * (1 + ($osto_rahti_alv / 100));
  }

  if (isset($osto_rivi_kulu_alv_verollisuus) and $osto_rivi_kulu_alv_verollisuus == 'X') {
    $osto_rivi_kulu = $osto_rivi_kulu * (1 + ($osto_rivi_kulu_alv / 100));
  }

  $toimipaikka = isset($toimipaikka) ? $toimipaikka : 0;

  // Kirjoitetaan lasku
  $query = "INSERT into lasku set
            yhtio              = '$kukarow[yhtio]',
            yhtio_toimipaikka  = '{$toimipaikka}',
            summa              = '$summa',
            kasumma            = '$kassaale',
            lapvm              = '$vpv-$vpk-$vpp',
            erpcm              = '$erv-$erk-$erp',
            kapvm              = '$kav-$kak-$kap',
            olmapvm            = '$olmapvm',
            valkoodi           = '$valkoodi',
            hyvak1             = '$hyvak[1]',
            hyvak2             = '$hyvak[2]',
            hyvak3             = '$hyvak[3]',
            hyvak4             = '$hyvak[4]',
            hyvak5             = '$hyvak[5]',
            hyvaksyja_nyt      = '$hyvaksyja_nyt',
            ytunnus            = '$trow[ytunnus]',
            tilinumero         = '$trow[tilinumero]',
            nimi               = '$trow[nimi]',
            nimitark           = '$trow[nimitark]',
            osoite             = '$trow[osoite]',
            osoitetark         = '$trow[osoitetark]',
            postino            = '$trow[postino]',
            postitp            = '$trow[postitp]',
            maa                = '$trow[maa]',
            toim_maa           = '$trow[verovelvollinen]',
            ultilno_maa        = '$trow[ultilno_maa]',
            viite              = '$viite',
            viesti             = '$viesti',
            vienti             = '$vienti',
            tapvm              = '$tpv-$tpk-$tpp',
            ebid               = '$ebid',
            tila               = '$tila',
            ultilno            = '$trow[ultilno]',
            pankki_haltija     = '$trow[pankki_haltija]',
            swift              = '$trow[swift]',
            pankki1            = '$trow[pankki1]',
            pankki2            = '$trow[pankki2]',
            pankki3            = '$trow[pankki3]',
            pankki4            = '$trow[pankki4]',
            vienti_kurssi      = '$vrow[kurssi]',
            laatija            = '$kukarow[kuka]',
            liitostunnus       = '$toimittajaid',
            hyvaksynnanmuutos  = '$ohyvaksynnanmuutos',
            suoraveloitus      = '$osuoraveloitus',
            luontiaika         = now(),
            comments           = '$komm',
            laskunro           = '$toimittajan_laskunumero',
            sisviesti1         = '$ohjeitapankille',
            alv_tili           = '$tilino_alv',
            osto_rahti         = '{$osto_rahti}',
            osto_kulu          = '{$osto_kulu}',
            osto_rivi_kulu     = '{$osto_rivi_kulu}',
            osto_rahti_alv     = '{$osto_rahti_alv}',
            osto_kulu_alv      = '{$osto_kulu_alv}',
            osto_rivi_kulu_alv = '{$osto_rivi_kulu_alv}'";
  $result = pupe_query($query);
  $tunnus = mysql_insert_id($GLOBALS["masterlink"]);

  if ($kuva1 or $kuva2 or $kuva3) {
    $tunnarit = "";

    if ($kuva1) $tunnarit.= $kuva1.",";
    if ($kuva2) $tunnarit.= $kuva2.",";
    if ($kuva3) $tunnarit.= $kuva3.",";

    $tunnarit = substr($tunnarit, 0, -1);

    // päivitetään kuvalle vielä linkki toiseensuuntaa
    $query = "UPDATE liitetiedostot set liitostunnus='$tunnus', selite='$trow[nimi] $summa $valkoodi' where tunnus in ($tunnarit)";
    pupe_query($query);
  }

  // Tehdään oletustiliöinnit
  $omasumma = round($summa * $vrow['kurssi'], 2);
  $omasumma_valuutassa = $summa;

  $vassumma = -1 * $omasumma;
  $vassumma_valuutassa = -1 * $omasumma_valuutassa;

  // Nyt on saatava pyöristykset ok
  $veroton        = 0;
  $veroton_valuutassa  = 0;

  $muusumma        = 0;
  $muusumma_valuutassa = 0;
  $maksimisumma      = 0;
  $maksimisumma_i    = 0;

  $laskun_kulut = array();

  for ($i=1; $i<$maara; $i++) {
    $ivero[$i]        = (float) $ivero[$i];
    $isumma_valuutassa[$i]  = (float) $isumma[$i];
    $isumma[$i]       = (float) round($isumma[$i] * $vrow['kurssi'], 2);

    // Laitetaan oletuskustannuspaikat kuntoon
    list($ikustp[$i], $ikohde[$i], $iprojekti[$i]) = kustannuspaikka_kohde_projekti($itili[$i], $ikustp[$i], $ikohde[$i], $iprojekti[$i]);

    if ($yhtiorow["kirjanpidon_tarkenteet"] == "K" and (($isumma[$i] > $maksimisumma and $omasumma >= 0) or ($isumma[$i] < $maksimisumma and $omasumma < 0))) {
      $maksimisumma = $isumma[$i];
      $maksimisumma_i = $i;
    }

    // Netotetaan alvi
    if ($ivero[$i] != 0) {
      $ialv[$i] = round($isumma[$i] - $isumma[$i] / (1 + ($ivero[$i] / 100)), 2);
      $ialv_valuutassa[$i] = round($isumma_valuutassa[$i] - $isumma_valuutassa[$i] / (1 + ($ivero[$i] / 100)), 2);

      $isumma[$i] -= $ialv[$i];
      $isumma_valuutassa[$i] -= $ialv_valuutassa[$i];

      $muusumma += $isumma[$i] + $ialv[$i];
      $muusumma_valuutassa += $isumma_valuutassa[$i] + $ialv_valuutassa[$i];
    }
    else {
      $muusumma += $isumma[$i];
      $muusumma_valuutassa += $isumma_valuutassa[$i];
    }

    $veroton += $isumma[$i];
    $veroton_valuutassa += $isumma_valuutassa[$i];
  }

  // Valitaan otsovelkatili
  if ($trow["konserniyhtio"] != '') {
    $ostovelat = $yhtiorow["konserniostovelat"];
  }
  else {
    $ostovelat = $yhtiorow["ostovelat"];
  }

  if ($yhtiorow["kirjanpidon_tarkenteet"] == "K") {
    list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($ostovelat, $ikustp[$maksimisumma_i], $ikohde[$maksimisumma_i], $iprojekti[$maksimisumma_i]);
  }
  else {
    list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($ostovelat);
  }

  // Ostovelka
  $query = "INSERT INTO tiliointi SET
            yhtio            = '$kukarow[yhtio]',
            ltunnus          = '$tunnus',
            tilino           = '$ostovelat',
            kustp            = '{$kustp_ins}',
            kohde            = '{$kohde_ins}',
            projekti         = '{$projekti_ins}',
            tapvm            = '$tpv-$tpk-$tpp',
            summa            = '$vassumma',
            summa_valuutassa = '$vassumma_valuutassa',
            valkoodi         = '$valkoodi',
            vero             = 0,
            lukko            = '1',
            tosite           = '$tositenro',
            laatija          = '$kukarow[kuka]',
            laadittu         = now()";
  $result = pupe_query($query);

  if ($muusumma != $omasumma) {
    echo "<font class='message'>".t("Valuuttapyöristystä")." " . round($muusumma-$omasumma, 2) . "</font><br>";
    for ($i=1; $i<$maara; $i++) {
      if ($isumma[$i] != 0) {
        $isumma[$i] += $omasumma-$muusumma;
        $isumma_valuutassa[$i] += $omasumma_valuutassa-$muusumma_valuutassa;
        $i=$maara;
      }
    }
  }

  $muusumma = 0;
  $muusumma_valuutassa = 0;

  for ($i=1; $i<$maara; $i++) {
    $muusumma += $isumma[$i] + $ialv[$i];
    $muusumma_valuutassa += $isumma_valuutassa[$i] + $ialv_valuutassa[$i];
  }

  if (round($muusumma, 2) != round($omasumma, 2)) {
    echo t("Valuuttapyöristyksen jälkeenkin heittää")." $omasumma <> $muusumma<br>";
    echo t("Tästä ei selvitty! Kulutiliöintiä ei tehty")."<br>";
    exit;
  }

  $isumma_varasto = $isumma;

  for ($i = 1; $i < $maara; $i++) {
    if (strlen($itili[$i]) > 0) {

      $ikustp_ins   = $ikustp[$i] == 0 ? $ikustp[$maksimisumma_i] : $ikustp[$i];
      $ikohde_ins   = $ikohde[$i] == 0 ? $ikohde[$maksimisumma_i] : $ikohde[$i];
      $iprojekti_ins   = $iprojekti[$i] == 0 ? $iprojekti[$maksimisumma_i] : $iprojekti[$i];

      if ($yhtiorow['ostolaskun_kulutilit'] != '' and ($yhtiorow['osto_rahti'] != '' or $yhtiorow['osto_kulu'] != '' or $yhtiorow['osto_rivi_kulu'] != '') and ($vienti == 'C' or $vienti == 'F' or $vienti == 'I') and (trim($osto_rahti != '') or trim($osto_kulu) != '' or trim($osto_rivi_kulu) != '')) {

        list($isumma[$i], $_arr) = ostolaskun_kulujen_tiliointi($summa, $isumma[$i]+$ialv[$i], $osto_rahti, $osto_kulu, $osto_rivi_kulu);

        $ialv[$i] = round($isumma[$i] - $isumma[$i] / (1 + ($ivero[$i] / 100)), 2);
        $ialv_valuutassa[$i] = round($isumma_valuutassa[$i] - $isumma_valuutassa[$i] / (1 + ($ivero[$i] / 100)), 2);

        $isumma[$i] -= $ialv[$i];

        if ($yhtiorow['ostolaskun_kulutilit_kayttaytyminen'] == '') $isumma_varasto[$i] = $isumma[$i];

        foreach ($_arr as $_k => $_v) {
          if (!isset($laskun_kulut[${"{$_k}_alv"}][$_k])) $laskun_kulut[${"{$_k}_alv"}][$_k] = 0;
          $laskun_kulut[${"{$_k}_alv"}][$_k] += $_v;
        }
      }

      // Kulutili
      $query = "INSERT INTO tiliointi SET
                yhtio            = '$kukarow[yhtio]',
                ltunnus          = '$tunnus',
                tilino           = '$itili[$i]',
                kustp            = '{$ikustp_ins}',
                kohde            = '{$ikohde_ins}',
                projekti         = '{$iprojekti_ins}',
                tapvm            = '$tpv-$tpk-$tpp',
                summa            = '$isumma[$i]',
                summa_valuutassa = '$isumma_valuutassa[$i]',
                valkoodi         = '$valkoodi',
                vero             = '$ivero[$i]',
                selite           = '$iselite[$i]',
                lukko            = '',
                tosite           = '$tositenro',
                laatija          = '$kukarow[kuka]',
                laadittu         = now()";
      $result = pupe_query($query);

      // Tiliöidään alv
      if ($ivero[$i] != 0) {
        $isa = mysql_insert_id($GLOBALS["masterlink"]); // Näin löydämme tähän liittyvät alvit....

        // Kulun alv
        $query = "INSERT INTO tiliointi SET
                  yhtio            = '$kukarow[yhtio]',
                  ltunnus          = '$tunnus',
                  tilino           = '$tilino_alv',
                  kustp            = 0,
                  kohde            = 0,
                  projekti         = 0,
                  tapvm            = '$tpv-$tpk-$tpp',
                  summa            = '$ialv[$i]',
                  summa_valuutassa = '$ialv_valuutassa[$i]',
                  valkoodi         = '$valkoodi',
                  vero             = 0,
                  selite           = '$iselite[$i]',
                  lukko            = '1',
                  tosite           = '$tositenro',
                  laatija          = '$kukarow[kuka]',
                  laadittu         = now(),
                  aputunnus        = '$isa'";
        $result = pupe_query($query);
      }

      // jos kyseessä on vaihto-omaisuutta tai rahti/huolintakuluja, tiliöidään varastonarvoon
      if ($vienti != 'A' and $vienti != 'D' and $vienti != 'G' and $vienti != '') {

        $varastotili = $yhtiorow['varasto'];

        if ($vienti == 'C' or $vienti == 'F' or $vienti == 'I' or $vienti == 'J' or $vienti == 'K' or $vienti == 'L') {
          $varastotili = $yhtiorow['matkalla_olevat'];
        }

        $varastonmuutostili = $yhtiorow["varastonmuutos"];

        if ($yhtiorow['raaka_ainevarastonmuutos'] != "" and ($vienti == 'J' or $vienti == 'K' or $vienti == 'L')) {
          $varastonmuutostili = $yhtiorow["raaka_ainevarastonmuutos"];
        }

        if ($yhtiorow['varastonmuutos_rahti'] != "" and ($vienti == 'B' or $vienti == 'E' or $vienti == 'H')) {
          $varastonmuutostili = $yhtiorow["varastonmuutos_rahti"];
        }

        // Tiliöidään ensisijaisesti varastonmuutos tilin oletuskustannuspaikalle
        list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($varastonmuutostili, $ikustp_ins, $ikohde_ins, $iprojekti_ins);

        // Toissijaisesti kokeillaan vielä varasto-tilin oletuskustannuspaikkaa
        list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($varastotili, $kustp_ins, $kohde_ins, $projekti_ins);

        // Varasto
        $query = "INSERT INTO tiliointi SET
                  yhtio            = '$kukarow[yhtio]',
                  ltunnus          = '$tunnus',
                  tilino           = '$varastotili',
                  kustp            = '{$kustp_ins}',
                  kohde            = '{$kohde_ins}',
                  projekti         = '{$projekti_ins}',
                  tapvm            = '$tpv-$tpk-$tpp',
                  summa            = $isumma_varasto[$i],
                  summa_valuutassa = $isumma_valuutassa[$i],
                  valkoodi         = '$valkoodi',
                  vero             = 0,
                  lukko            = '',
                  tosite           = '$tositenro',
                  laatija          = '$kukarow[kuka]',
                  laadittu         = now()";
        $result = pupe_query($query);

        // Varastonmuutos
        $query = "INSERT INTO tiliointi SET
                  yhtio            = '$kukarow[yhtio]',
                  ltunnus          = '$tunnus',
                  tilino           = '$varastonmuutostili',
                  kustp            = '{$ikustp_ins}',
                  kohde            = '{$ikohde_ins}',
                  projekti         = '{$iprojekti_ins}',
                  tapvm            = '$tpv-$tpk-$tpp',
                  summa            = $isumma_varasto[$i] * -1,
                  summa_valuutassa = $isumma_valuutassa[$i] * -1,
                  valkoodi         = '$valkoodi',
                  vero             = 0,
                  lukko            = '',
                  tosite           = '$tositenro',
                  laatija          = '$kukarow[kuka]',
                  laadittu         = now()";
        $result = pupe_query($query);
      }
    }
  }

  if (count($laskun_kulut) > 0) {

    foreach ($laskun_kulut as $vero => $_array) {
      foreach ($_array as $key => $value) {

        if ($value == 0) continue;

        $tili = $yhtiorow[$key];
        $summa = $value;

        // Laitetaan kustannuspaikaksi/kohteeksi/projektiksi aina ensimmäisen tiliöintirivin tiedot.
        // Jos menevät nollana, funktio osaa ottaa oletuksen tilin takaa.
        list($kustp, $kohde, $projekti) = kustannuspaikka_kohde_projekti($tili, $ikustp[1], $ikohde[1], $iprojekti[1]);

        require "inc/teetiliointi.inc";
      }
    }
  }

  // Jos meillä on suoraveloitus
  if ($osuoraveloitus != '') {

    echo "<font class='message'>".t('Suoraveloitus');

    //Toimittajalla on pankkitili, teemme eräpäivälle suorituksen valmiiksi
    if ($trow['oletus_suoravel_pankki'] > 0) {

      echo " ".t('oletuspankkitilille').".</font><br>";

      list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($ostovelat);

      // Oletustiliöinnit
      // Ostovelat
      $query = "INSERT INTO tiliointi SET
                yhtio            = '$kukarow[yhtio]',
                ltunnus          = '$tunnus',
                tilino           = '$ostovelat',
                kustp            = '{$kustp_ins}',
                kohde            = '{$kohde_ins}',
                projekti         = '{$projekti_ins}',
                tapvm            = '$erv-$erk-$erp',
                summa            = '$omasumma',
                summa_valuutassa = '$omasumma_valuutassa',
                valkoodi         = '$valkoodi',
                vero             = 0,
                lukko            = '',
                tosite           = '$tositenro',
                laatija          = '$kukarow[kuka]',
                laadittu         = now()";
      $xresult = pupe_query($query);

      list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yhtiorow["selvittelytili"]);

      // Rahatili
      $query = "INSERT INTO tiliointi SET
                yhtio            = '$kukarow[yhtio]',
                ltunnus          = '$tunnus',
                tilino           = '$yhtiorow[selvittelytili]',
                kustp            = '{$kustp_ins}',
                kohde            = '{$kohde_ins}',
                projekti         = '{$projekti_ins}',
                tapvm            = '$erv-$erk-$erp',
                summa            = '$vassumma',
                summa_valuutassa = '$vassumma_valuutassa',
                valkoodi         = '$valkoodi',
                vero             = 0,
                lukko            = '',
                tosite           = '$tositenro',
                laatija          = '$kukarow[kuka]',
                laadittu         = now()";
      $xresult = pupe_query($query);

      if ($tila == 'M') {
        $query = "UPDATE lasku set
                  tila         = 'Y',
                  mapvm        = '$erv-$erk-$erp',
                  maksu_kurssi = 1
                  WHERE tunnus = '$tunnus'";
        $xresult = pupe_query($query);
        echo "<font class='message'>".t('Lasku merkittiin suoraan maksetuksi')."</font><br>";
      }
    }
    else {
      // Tämä on vain suoraveloitus
      if ($tila == 'M') {
        echo " ".t('ilman oletuspankkitiliä').".</font><br>";

        $query = "UPDATE lasku set tila = 'Q' WHERE tunnus = '$tunnus'";
        $xresult = pupe_query($query);

        echo "<font class='message'>".t('Lasku merkittiin odottamaan suoritusta')."</font><br>";
      }
    }
  }

  // Kopioidaan laskua tarvittaessa ollaan todella tyhmiä ja kopioidaan vaan surutta
  $kopioi = (int) $kopioi;

  if ($kopioi > 0) {
    //Etsitään tehty lasku
    $query = "SELECT *
              FROM lasku
              WHERE tunnus = '$tunnus'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) != 1) {
      echo t("VIRHE: Kopioitava lasku katosi! Laskua ei kopioitu")."!<br>";
    }
    else {
      $laskurow = mysql_fetch_assoc($result);

      $query = "SELECT *
                FROM tiliointi
                WHERE yhtio = '$kukarow[yhtio]'
                and ltunnus = '$tunnus'
                order by tunnus";
      $tilresult = pupe_query($query);

      if (mysql_num_rows($tilresult) == 0) {
        echo t("Kopioitavat tiliöinnit katosi! Tiliöintejä ei kopioitu")."!<br>";
      }

      for ($kopio=1; $kopio<=$kopioi; $kopio++) {
        $kopiotpv = $tpv;
        $kopiotpk = $tpk;
        $kopiotpp = $tpp;
        $kopioerv = $erv;
        $kopioerk = $erk;
        $kopioerp = $erp;
        $kopiokav = $kav;
        $kopiokak = $kak;
        $kopiokap = $kap;

        $query = "INSERT into lasku set ";

        for ($i=0; $i<mysql_num_fields($result); $i++) {

          $fieldname = mysql_field_name($result, $i);

          if (!in_array($fieldname, array('tunnus', 'olmapvm', 'kate_korjattu'))) {

            if ($fieldname == 'tapvm') {
              $kopiotpk = $tpk + $kopio;

              if ($kopiotpk > 12) {
                $kopiotpk=1;
                $kopiotpv++;
              }

              if (!checkdate($kopiotpk, $kopiotpp, $kopiotpv)) {
                $kopiotpp = date("d", mktime(0, 0, 0, $kopiotpk + 1, 0, $kopiotpv));
              }

              $query .= "tapvm ='$kopiotpv-$kopiotpk-$kopiotpp',";
            }
            elseif ($fieldname == 'erpcm') {

              $kopioerk = $erk + $kopio;

              if ($kopioerk > 12) {
                $kopioerk=1;
                $kopioerv++;
              }

              if (!checkdate($kopioerk, $kopioerp, $kopioerv)) {
                $kopioerp = date("d", mktime(0, 0, 0, $kopioerk + 1, 0, $kopioerv));
              }

              $query .= "erpcm ='$kopioerv-$kopioerk-$kopioerp',";

              if ($laskurow['kapvm'] == '0000-00-00') {
                $query .= "olmapvm ='$kopioerv-$kopioerk-$kopioerp',";
              }
            }
            elseif ($fieldname == 'kapvm') {

              if ($laskurow['kapvm'] != '0000-00-00') {

                $kopiokak = $kak + $kopio;

                if ($kopiokak > 12) {
                  $kopiokak=1;
                  $kopiokav++;
                }

                if (!checkdate($kopiokak, $kopiokap, $kopiokav)) {
                  $kopiokap = date("d", mktime(0, 0, 0, $kopiokak + 1, 0, $kopiokav));
                }

                $query .= "kapvm ='$kopiokav-$kopiokak-$kopiokap',";
                $query .= "olmapvm ='$kopiokav-$kopiokak-$kopiokap',";
              }
            }
            else {
              $query .= $fieldname . "='{$laskurow[$fieldname]}',";
            }
          }
        }

        $query       = substr($query, 0, -1);
        $insresult   = pupe_query($query);
        $kopiotunnus = mysql_insert_id($GLOBALS["masterlink"]);

        //Kopioidaan tiliöinnit
        mysql_data_seek($tilresult, 0);

        while ($tiliointirow = mysql_fetch_assoc($tilresult)) {

          $query = "INSERT INTO tiliointi SET ";

          for ($i=0; $i<mysql_num_fields($tilresult); $i++) {

            if (mysql_field_name($tilresult, $i) != 'tunnus') {

              if (mysql_field_name($tilresult, $i) == 'tapvm') {
                $query .= "tapvm ='$kopiotpv-$kopiotpk-$kopiotpp',";
              }
              elseif (mysql_field_name($tilresult, $i) == 'ltunnus') {
                $query .= "ltunnus ='$kopiotunnus',";
              }
              elseif (mysql_field_name($tilresult, $i) == 'aputunnus') {
                if ($tiliointirow['aputunnus'] != 0) {
                  $query .= "aputunnus ='$kopiotiltunnus',";
                }
              }
              else {
                $query .= mysql_field_name($tilresult, $i) . "='" . $tiliointirow[mysql_field_name($tilresult, $i)] . "',";
              }
            }
          }

          $query         = substr($query, 0, -1);
          $insresult     = pupe_query($query);
          $kopiotiltunnus= mysql_insert_id($GLOBALS["masterlink"]);
        }
        echo "<font class='message'>".t("Tehtiin kopio päivälle")." $kopiotpv-$kopiotpk-$kopiotpp</font><br>";
      }
    }
  }

  $tee    = "";
  $selite = "";

  if ($kpexport == 1 or strtoupper($yhtiorow['maa']) != 'FI') {
    $query = "UNLOCK TABLES";
    $result = pupe_query($query);
    echo "<font class='message'>".t("Lasku perustettiin toimittajalle")." $trow[nimi]<br>";
    echo t("Summa")." $yleissumma $valkoodi = $omasumma $yhtiorow[valkoodi]".t('Tositenro on')." $tositenro<br></font><hr>";
  }
  else {
    echo "<font class='message'>".t("Lasku perustettiin toimittajalle")." $trow[nimi]<br>";
    echo t("Summa")." $yleissumma $valkoodi = $omasumma $yhtiorow[valkoodi]<br></font><hr>";
  }

  // Näytettään käyttöliittymä
  $tee = '';

  if ($yhtiorow['skannatut_laskut_polku'] != '' and $nayta_skannattu_lasku != "") {
    unset($kuva1);

    $skannatut_laskut_polku = rtrim($yhtiorow['skannatut_laskut_polku'], '/').'/';

    unlink($skannatut_laskut_polku.$nayta_skannattu_lasku);

    $silent = 'ei näytetä käyttöliittymää';
    $seuraava_lasku = hae_skannattu_lasku("kasittele_seuraava");

    if ($seuraava_lasku === FALSE) {
      echo "<br/>", t("Skannatut laskut loppuivat"), ".<br/><br/>";
      $nayta_skannattu_lasku = "";
    }
    else {
      $nayta_skannattu_lasku = $seuraava_lasku["seuraava"];
    }
  }

  //Luodaan uusi keikka jos käyttäjä ruksasi keikkaruksin
  if ($luouusikeikka == "LUO") {

    //Luodaan uusi keikka
    $aladellaa = "En haluu dellata!";

    require "inc/verkkolasku-in-luo-keikkafile.inc";

    echo "$laskuvirhe<br><br>";

    if ($autokohdistus == "AUTO") {

      $ale_query_lisa = generoi_alekentta_select('erikseen', 'O');

      //Tehdään keikka ja varastoonvienti automaattisesti
      $query = "UPDATE tilausrivi SET
                hinta       = hinta * {$ale_query_lisa},
                uusiotunnus = '$keikantunnus',
                tyyppi      = 'O',
                varattu     = varattu * -1,
                tilkpl      = tilkpl * -1
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  in ($ostorow[tunnukset])";
      $liittos = pupe_query($query);

      // tämän keikan voi viedä saldoille...
      $otunnus = $keikantunnus;

      $query = "SELECT * FROM lasku WHERE yhtio = '$kukarow[yhtio]' AND tunnus = '$otunnus'";
      $result = pupe_query($query);
      $laskurow = mysql_fetch_assoc($result);

      // viedään varastoon...
      require "tilauskasittely/varastoon.inc";

      $tee = "KALA";
    }
    else {
      //Sitten mennään suoraan keikalle
      $otunnus = $keikantunnus;
      $tila = "";
      $tee = "";
      $PHP_SELF = "tilauskasittely/keikka.php";
      $alku = "";
      $toimittajaid = $trow["tunnus"];

      require "tilauskasittely/ostotilausten_rivien_kohdistus.inc";
      exit;
    }
  }
}

if ($tee == "") {

  $formi  = 'viivat';
  $kentta = 'nimi';
  $hiddenit = "<input type='hidden' name='lopetus' value='$lopetus'>";

  if ($yhtiorow['skannatut_laskut_polku'] != '' and $nayta_skannattu_lasku != "") {
    echo "<table><tr><td class='back'>";
    $hiddenit .= "<input type='hidden' name='nayta_skannattu_lasku' value='$nayta_skannattu_lasku'>";
  }

  echo "<br><table>";

  echo "<tr><th nowrap><form name = 'viivat' action = '?tee=VIIVA' method='post'>$hiddenit".t("Perusta lasku viivakoodilukijalla")."</th>";
  echo "<td><input type = 'text' name = 'nimi' size='8'></td>
    <td>".t("tiliöintirivejä").":</td>
    <td><select name='maara'><option value ='2'>1
    <option value ='4'>3
    <option value ='8'>7
    <option value ='16'>15
    <option value ='31'>30
    <option value ='41'>40
    <option value ='51'>50
    </select></td>
    <td><input type = 'submit' value = '".t("Perusta")."'></td></tr></form>";

  echo "<tr><th nowrap><form action = '?tee=Y' method='post'>$hiddenit".t("Perusta lasku toimittajan Y-tunnuksen/nimen perusteella")."</th>";
  echo "<td><input type = 'text' name = 'ytunnus' size='8' maxlength='15'></td>
    <td>".t("tiliöintirivejä").":</td>
    <td><select name='maara'><option value ='2'>1
    <option value ='4'>3
    <option value ='8'>7
    <option value ='16'>15
    <option value ='31'>30
    <option value ='41'>40
    <option value ='51'>50
    </select></td>
    <td><input type = 'submit' value = '".t("Perusta")."'></td></tr></form>";

  echo "<th nowrap><form action = '?tee=P' method='post'>$hiddenit".t("Perusta lasku ilman toimittajatietoja")."</th>";

  echo "<td>
    <select name='tyyppi'>
    <option value =".strtoupper($yhtiorow['maa']).">".t("Kotimaa")."
    <option value ='nonfi'>".t("Ulkomaa")."
    </select></td>
    <td>".t("tiliöintirivejä").":</td>
    <td><select name='maara'><option value ='2'>1
    <option value ='4'>3
    <option value ='8'>7
    <option value ='16'>15
    <option value ='31'>30
    <option value ='41'>40
    <option value ='51'>50
    </select></td>
    <td><input type = 'submit' value = '".t("Perusta")."'></td></tr>";
  echo "</form>";

  if ($toimittajaid > 0) {
    $query = "SELECT nimi
              FROM toimi
              WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$toimittajaid'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) != 0) {

      $row = mysql_fetch_assoc($result);

      echo "<th><form action = '?tee=Y' method='post'>$hiddenit".t("Perusta lasku toimittajalle")." $row[nimi]</th>";

      echo "<td><input type='hidden'  id='toimittajaid' name='toimittajaid' value='$toimittajaid'></td>
      <td>".t("tiliöintirivejä").":</td>
      <td><select name='maara'><option value ='2'>1
      <option value ='4'>3
      <option value ='8'>7
      <option value ='16'>15
      <option value ='31'>30
      <option value ='41'>40
      <option value ='51'>50
      </select></td>
      <td><input type = 'submit' value = '".t("Perusta")."'></td></tr></table></form>";
    }
  }
  else {
    echo "</table>";
  }

  if (trim($yhtiorow['skannatut_laskut_polku']) != '' and $silent == '') {

    echo "<br/><table>";

    $seuraava_lasku = hae_skannattu_lasku();

    if ($seuraava_lasku === FALSE) {
      echo "<tr>";
      echo "<td nowrap>", t("Yhtään skannattua laskua ei löytynyt"), ".</td>";
      echo "</tr>";
    }
    else {

      echo "<tr>";
      echo "<td nowrap>";
      echo t("Skannattuja laskuja löytyi")." ", $seuraava_lasku["lukumaara"], " ", t("kappaletta").".";
      if (isset($seuraava_lasku["kesken"])) {
        echo "<br>".t("Sinulla on skannatun laskunsyöttö kesken").".";
      }
      echo "</td>";

      echo "<td class='back'>";
      echo "<form action='ulask.php' method='post'>";
      echo "<input type='hidden' name='nayta_skannattu_lasku' value='{$seuraava_lasku["seuraava"]}'>";
      echo "<input type='hidden' name='lopetus' value='$lopetus'>";

      if (isset($seuraava_lasku["kesken"])) {
        echo "<input type='submit' value='".t("Jatka syöttöä")."'>";
      }
      else {
        echo "<input type='submit' value='".t("Perusta")."'>";
      }

      echo "</form>";
      echo "</td>";
      echo "</tr>";
    }

    echo "</table>";
  }

  // Näytetään skannattu lasku
  if ($yhtiorow['skannatut_laskut_polku'] != '' and $nayta_skannattu_lasku != "") {

    echo "<table><tr><td class='back'>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='poistettava_lasku' value='$nayta_skannattu_lasku'>";
    echo "<input type='hidden' name='tee' value='poista_lasku'>";
    echo "<input type='submit' value='".t("Poista skannattu")." lasku' name='Poista'/></form></td>";

    echo "<td class='back'>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='ohitettava_lasku' value='$nayta_skannattu_lasku'>";
    echo "<input type='hidden' name='tee' value='ohita_lasku'>";
    echo "<input type='submit' value='".t("Ohita lasku")."' name='ohita_lasku'/></form></td></tr></table>";

    $skannatut_laskut_polku = rtrim($yhtiorow['skannatut_laskut_polku'], '/').'/';

    echo "</td><td class='back' width='100%'><font class='message'>", t("Skannattu lasku"), "</font>";
    echo "<div style='height: 100%; overflow: auto; width: 100%;'>";
    echo "<iframe src='{$skannatut_laskut_polku}{$nayta_skannattu_lasku}' style='width:100%; height: 800px; border: 0px; display: block;'></iFrame>";
    echo "</div>";
    echo "</td></tr></table>";
  }

}

if (strpos($_SERVER['SCRIPT_NAME'], "ulask.php")  !== FALSE) {
  require "inc/footer.inc";
}
