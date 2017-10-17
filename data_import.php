<?php

// Enabloidaan, ett‰ Apache flushaa kaiken mahdollisen ruudulle kokoajan.
ini_set('implicit_flush', 1);
ob_implicit_flush(1);

// Ei k‰ytet‰ pakkausta
$compression = FALSE;

// Ladataan tiedosto
if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

require "inc/parametrit.inc";

// Ladataan tai poistetaan tiedosto
if (isset($tee) and ($tee == "lataa_tiedosto" or $tee == "poista_file")) {

  // Tarkistetaan eka, ett‰ t‰m‰ on t‰m‰n k‰ytt‰j‰n file
  // Filename on muotoa: lue-data#username#yhtio#taulu#randombit#alkuperainen_filename#jarjestys.DATAIMPORT.LOG
  $filen_tiedot = explode("#", $datain_filenimi);
  $kuka = $filen_tiedot[1];
  $yhtio = $filen_tiedot[2];

  $datain_filenimi = basename($datain_filenimi);

  if ($kuka != $kukarow["kuka"] or $yhtio != $kukarow["yhtio"]) {
    echo "<font class='error'>".t("Virheellinen tiedostonimi")."!</font><br>";
  }
  elseif ($tee == "lataa_tiedosto") {
    readfile($pupe_root_polku."/datain/".$datain_filenimi);
    exit;
  }
  elseif ($tee == "poista_file") {
    unlink($pupe_root_polku."/datain/".$datain_filenimi);
    unlink($pupe_root_polku."/datain/".substr($datain_filenimi, 0, -3)."ERR");
  }
}

if (isset($tee) and $tee == "poistakaikki_filetsut") {
  if ($files = scandir($pupe_root_polku."/datain")) {
    foreach ($files as $file) {
      // T‰m‰ file on valmis lue-data file
      if (substr($file, 0, 11+strlen($kukarow["kuka"])+strlen($kukarow["yhtio"])) == "lue-data#{$kukarow["kuka"]}#{$kukarow["yhtio"]}#" and substr($file, -4) == ".LOG") {
        unlink($pupe_root_polku."/datain/".$file);
        unlink($pupe_root_polku."/datain/".substr($file, 0, -3)."ERR");
      }
    }
  }
}

echo "<font class='head'>".t("Datan sis‰‰nluku")." ".t("er‰ajo")."</font><hr>";

// Muuttujat
$tee       = isset($tee) ? trim($tee) : "";
$table     = isset($table) ? trim($table) : "";
$laheta    = isset($laheta) ? trim($laheta) : "";
$tablelisa = "";

// K‰sitell‰‰n file
if ($tee == "file" and $laheta != "") {

  $kasitellaan_tiedosto = TRUE;
  $kasitellaan_tiedosto_tyyppi = "";

  if (in_array($table, array("yhteyshenkilo", "asiakkaan_avainsanat", "kalenteri"))) {
    $tablelisa .= "..ytunnustarkkuus.$ytunnustarkkuus";
  }

  if (in_array($table, array("puun_alkio_asiakas", "puun_alkio_tuote"))) {
    $tablelisa .= "..dynaamisen_taulun_liitos.$dynaamisen_taulun_liitos";
  }

  if (in_array($table, array("asiakasalennus", "asiakashinta"))) {
    $tablelisa .= "..segmenttivalinta.$segmenttivalinta";
    $tablelisa .= "..asiakkaanvalinta.$asiakkaanvalinta";
  }

  if ($table == "extranet_kayttajan_lisatiedot") {
    $tablelisa .= "..liitostunnusvalinta.$liitostunnusvalinta";
  }

  if ($table == "tuotteen_toimittajat_tuotenumerot") {
    $tablelisa .= "..toimitunnusvalinta.$toimitunnusvalinta";
  }

  if ($table == "tuotteen_toimittajat_pakkauskoot") {
    $tablelisa .= "..toimitunnusvalinta.$toimitunnusvalinta";
  }

  if (isset($_FILES['userfile']) and is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {

    echo "<font class='message'>".t("Tarkastetaan l‰hetetty tiedosto")."...</font><br><br>\n";

    $alkuperainen_filenimi = $_FILES['userfile']['name'];
    $kasiteltava_tiedosto_path = $_FILES['userfile']['tmp_name'];

    if ($_FILES['userfile']['size'] == 0) {
      echo "<font class='error'>".t("Tiedosto on tyhj‰")."!</font><br>\n";
      $kasitellaan_tiedosto = FALSE;
    }

    $path_parts = pathinfo($_FILES['userfile']['name']);
    $kasitellaan_tiedosto_tyyppi = strtoupper($path_parts['extension']);

    $sallitut_tyypit = array("XLSX", "DATAIMPORT");

    // Jos meill‰ on ssconvert asennettuna, voidaan k‰sitell‰ myˆs XLS tiedostoja
    if (is_executable("/usr/bin/ssconvert")) {
      $sallitut_tyypit[] = "XLS";
    }

    // Vain Excel!
    $return = tarkasta_liite("userfile", $sallitut_tyypit);

    if ($return !== TRUE) {
      echo "<font class='error'>$return</font>\n";
      $kasitellaan_tiedosto = FALSE;
    }

    if (!is_executable("/usr/bin/split")) {
      echo "<font class='error'>".t("Split komento ei ole asennettu")."!</font><br>\n";
      $kasitellaan_tiedosto = FALSE;
    }

    if (preg_match('/[^A-Za-z0-9\. \-\_]/', $alkuperainen_filenimi)) {
      echo "<font class='error'>".t("Tiedostonimess‰ kiellettyj‰ merkkej‰").". ".t("Sallitut merkit").": A-Z 0-9</font><br>\n";
      $kasitellaan_tiedosto = FALSE;
    }

    // Tehd‰‰n Excel -> CSV konversio
    if ($kasitellaan_tiedosto === TRUE and $kasitellaan_tiedosto_tyyppi == "XLS") {

      $kasiteltava_tiedosto_path_csv = $kasiteltava_tiedosto_path.".DATAIMPORT";

      /**
       * M‰‰ritell‰‰n importattavan tiedoston tyyppi. Kaikki vaihtoehdot saa komentorivilt‰: ssconvert --list-importers *
       */


      $import_type = "--import-type=Gnumeric_Excel:excel";

      $return_string = system("/usr/bin/ssconvert --export-type=Gnumeric_stf:stf_csv $import_type ".escapeshellarg($kasiteltava_tiedosto_path)." ".escapeshellarg($kasiteltava_tiedosto_path_csv), $return);

      if ($return != 0 or strpos($return_string, "CRITICAL") !== FALSE) {
        echo "<font class='error'>".t("Tiedoston konversio ep‰onnistui")."!</font><br>\n";
        $kasitellaan_tiedosto = FALSE;
      }
      else {
        $kasitellaan_tiedosto_tyyppi = "DATAIMPORT";
      }

      // Poistetaan orig uploadfile
      unset($kasiteltava_tiedosto_path);

      // Otetaan uusi file muuttujaan
      $kasiteltava_tiedosto_path = $kasiteltava_tiedosto_path_csv;
    }

    // Tehd‰‰n XLSX -> CSV konversio
    if ($kasitellaan_tiedosto === TRUE and $kasitellaan_tiedosto_tyyppi == "XLSX") {

      // Tallennetaan XLSX faili CSV muotoon
      $kasiteltava_tiedosto_path_csv = $kasiteltava_tiedosto_path.".DATAIMPORT";

      // pupeFileReader palauttaa tidostonimen
      $kasiteltava_tiedosto_path_csv = pupeFileReader($kasiteltava_tiedosto_path, "XLSX", $kasiteltava_tiedosto_path_csv);

      // Poistetaan orig uploadfile
      unset($kasiteltava_tiedosto_path);

      // Otetaan uusi file muuttujaan
      $kasiteltava_tiedosto_path   = $kasiteltava_tiedosto_path_csv;
      $kasitellaan_tiedosto_tyyppi = "DATAIMPORT";
    }

    // Generoidaan uusi k‰ytt‰j‰kohtainen filenimi datain -hakemistoon. Konversion j‰lkeen filename on muotoa: lue-data#username#yhtio#taulu#randombit#alkuperainen_filename#jarjestys.DATAIMPORT
    $kasiteltava_filenimi = "lue-data#".$kukarow["kuka"]."#".$kukarow["yhtio"]."#".$table.$tablelisa."#".md5(uniqid(microtime(), TRUE) . $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT'])."#".$alkuperainen_filenimi;
    $kasiteltava_filepath = $pupe_root_polku."/datain/";
    $kasiteltava_kokonimi = $kasiteltava_filepath.$kasiteltava_filenimi;

    // Siirret‰‰n tiedosto datain -hakemistoon
    if (!rename($kasiteltava_tiedosto_path, $kasiteltava_kokonimi)) {
      echo "<font class='error'>".t("Tiedoston kopiointi ep‰onnistui")."! $kasiteltava_tiedosto_path &raquo; $kasiteltava_kokonimi</font><br>\n";
      $kasitellaan_tiedosto = FALSE;
    }
  }
  else {
    echo "<font class='error'>".t("Et valinnut tiedostoa")."!</font><br>\n";
    $kasitellaan_tiedosto = FALSE;
  }

  // File saatu palvelimelle OK
  if ($kasitellaan_tiedosto === TRUE and $kasitellaan_tiedosto_tyyppi == "DATAIMPORT") {

    // Otetaan tiedostosta ensimm‰inen rivi talteen, siin‰ on headerit
    $file = fopen($kasiteltava_kokonimi, "r") or die (t("Tiedoston avaus ep‰onnistui")."!");
    $header_rivi = fgets($file);
    fclose($file);

    // Laitetaan header fileen, koska filejen mergett‰minen on nopeempaa komentorivilt‰
    $header_file = $kasiteltava_filepath.md5(uniqid(microtime(), TRUE) . $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
    file_put_contents($header_file, $header_rivi);

    // Splitataan tiedosto 10000 rivin osiin datain -hakemistoon
    chdir($kasiteltava_filepath);
    system("/usr/bin/split -l 10000 ".escapeshellarg($kasiteltava_kokonimi)." ".escapeshellarg($kasiteltava_filenimi."#"));

    // Poistetaan alkuper‰inen
    unlink($kasiteltava_kokonimi);

    $montako_osaa = 0;

    // Loopataan l‰pi kaikki splitatut tiedostot
    if ($handle = opendir($kasiteltava_filepath)) {
      while (false !== ($file = readdir($handle))) {

        // T‰m‰ file t‰m‰n k‰ytt‰j‰n t‰m‰n session file
        if (substr($file, 0, strlen($kasiteltava_filenimi)) == $kasiteltava_filenimi) {

          // Jos kyseess‰ on eka file (loppuu "aa"), ei laiteta headeri‰
          if (substr($file, -2) == "aa") {
            // Renametaan alkuper‰iseksi plus DATAIMPORT p‰‰te
            rename($file, $file.".DATAIMPORT");
          }
          else {
            // Keksit‰‰n temp file
            $temp_file = $kasiteltava_filepath.md5(uniqid(microtime(), TRUE) . $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);

            // Concatenoidaan headerifile ja t‰m‰ file temppi fileen
            system("cat ".escapeshellarg($header_file)." ".escapeshellarg($file)." > ".escapeshellarg($temp_file));

            // Poistetaan alkuper‰inen file
            unlink($file);

            // Renametaan temppifile alkuper‰iseksi plus DATAIMPORT p‰‰te
            rename($temp_file, $file.".DATAIMPORT");
          }

          $montako_osaa++;
        }
      }
      closedir($handle);
    }

    // Poistetaan headerifile
    unlink($header_file);

    if ($montako_osaa > 1) {
      echo "<font class='message'>".t("Tiedostosi")." $alkuperainen_filenimi ".t("jaettiin")." $montako_osaa ".t("osaan").".</font>\n";
      echo "<font class='message'>".t("Jokainen osa sis‰lt‰‰ 10000 rivi‰").".</font><br><br>\n";
    }

    echo "<font class='message'>".t("Tiedosto siirretty k‰sittelyjonoon.")." ".t("Voit nyt poistua t‰st‰ ohjelmasta.")."</font><br>";
    echo "<font class='message'>".t("Tiedosto sis‰‰nluetaan automaattisesti.")." ".t("Palaa t‰h‰n ohjelmaan n‰hd‰ksesi ajon tuloksen.")."</font><br><br>\n";

    // Laukaistaan itse sis‰‰najo
    exec("/usr/bin/php {$pupe_root_polku}/data_import_ajo.php > /dev/null 2>/dev/null &");

  }
  else {
    echo "<font class='error'>".t("Dataa ei k‰sitelty")."!</font><br><br>\n";
  }
}

// Katsotaan onko k‰ytt‰j‰ll‰ tiedostoja k‰sittelyss‰
$tiedostoja_jonossa = 0;
$omia_tiedostoja_jonossa = 0;

if ($handle = opendir($pupe_root_polku."/datain")) {
  while (false !== ($file = readdir($handle))) {
    // T‰m‰ file on valmis lue-data file

    if (substr($file, 0, 9) == "lue-data#" and substr($file, -11) == ".DATAIMPORT") {
      $tiedostoja_jonossa++;

      // T‰m‰ on t‰m‰n k‰ytt‰j‰n file
      if (substr($file, 0, 11+strlen($kukarow["kuka"])+strlen($kukarow["yhtio"])) == "lue-data#{$kukarow["kuka"]}#{$kukarow["yhtio"]}#") {
        $omia_tiedostoja_jonossa++;
      }
    }
  }
  closedir($handle);
}

if ($tiedostoja_jonossa > 0) {
  echo "<br>";
  echo "<font class='message'>".t("Sinulla")." ".t("on")." $omia_tiedostoja_jonossa ".t("tiedostoa")." ".t("odottamassa k‰sittely‰").".</font><br>";
  echo "<font class='message'>".t("Palvelimella")." ".t("on")." ".t("yhteens‰")." $tiedostoja_jonossa ".t("tiedostoa")." ".t("odottamassa k‰sittely‰").".</font><br>";
  echo "<br>";
}

// Taulut, jota voidaan k‰sitell‰
$taulut = array(
  'abc_parametrit'                  => 'ABC-parametrit',
  'asiakas'                         => 'Asiakas',
  'asiakasalennus'                  => 'Asiakasalennukset',
  'asiakashinta'                    => 'Asiakashinnat',
  'asiakaskommentti'                => 'Asiakaskommentit',
  'asiakkaan_avainsanat'            => 'Asiakkaan avainsanat',
  'avainsana'                       => 'Avainsanat',
  'budjetti'                        => 'Budjetti',
  'etaisyydet'                      => 'Et‰isyydet varastosta',
  'extranet_kayttajan_lisatiedot'   => 'Extranet-k‰ytt‰j‰n lis‰tietoja',
  'hinnasto'                        => 'Hinnasto',
  'kalenteri'                       => 'Kalenteritietoja',
  'kuka'                            => 'K‰ytt‰j‰tietoja',
  'kustannuspaikka'                 => 'Kustannuspaikat',
  'lahdot'                    => 'L‰hdˆt',
  'laite'                           => 'Laiterekisteri',
  'laitteen_sopimukset'             => 'Laitteen sopimukset',
  'liitetiedostot'                  => 'Liitetiedostot',
  'maksuehto'                       => 'Maksuehto',
  'pakkaus'                         => 'Pakkaustiedot',
  'perusalennus'                    => 'Perusalennukset',
  'puun_alkio_asiakas'              => 'Asiakas-segmenttiliitokset',
  'puun_alkio_tuote'                => 'Tuote-segmenttiliitokset',
  'rahtikirjanumero'          => 'LOGY-rahtikirjanumerot',
  'rahtimaksut'                     => 'Rahtimaksut',
  'rahtisopimukset'                 => 'Rahtisopimukset',
  'rekisteritiedot'                 => 'Rekisteritiedot',
  'sanakirja'                       => 'Sanakirja',
  'sarjanumeron_lisatiedot'         => 'Sarjanumeron lis‰tiedot',
  'taso'                            => 'Tilikartan rakenne',
  'tili'                            => 'Tilikartta',
  'todo'                            => 'Todo-lista',
  'toimi'                           => 'Toimittaja',
  'toimittajaalennus'               => 'Toimittajan alennukset',
  'toimittajahinta'                 => 'Toimittajan hinnat',
  'toimitustapa'                    => 'Toimitustavat',
  'toimitustavan_lahdot'            => 'Toimitustavan l‰hdˆt',
  'tullinimike'                     => 'Tullinimikkeet',
  'tuote'                           => 'Tuote',
  'tuotepaikat'                     => 'Tuotepaikat',
  'tuoteperhe'                      => 'Tuoteperheet',
  'tuotteen_alv'                    => 'Tuotteiden ulkomaan ALV',
  'tuotteen_avainsanat'             => 'Tuotteen avainsanat',
  'tuotteen_orginaalit'             => 'Tuotteiden originaalit',
  'tuotteen_toimittajat'            => 'Tuotteen toimittajat',
  'tuotteen_toimittajat_pakkauskoot' => 'Tuotteen toimittajan pakkauskoot',
  'tuotteen_toimittajat_tuotenumerot' => 'Tuotteen toimittajan vaihtoehtoiset tuotenumerot',
  'vak'                             => 'VAK-tietoja',
  'varaston_hyllypaikat'            => 'Varaston hyllypaikat',
  'yhteyshenkilo'                   => 'Yhteyshenkilˆt',
);

// Yhtiˆkohtaisia
if (table_exists('auto_vari_tuote')) {
  $taulut['auto_vari']              = 'Autov‰ri-datat';
  $taulut['auto_vari_tuote']        = 'Autov‰ri-v‰rikirja';
  $taulut['auto_vari_korvaavat']    = 'Autov‰ri-korvaavat';
}

if (table_exists('yhteensopivuus_tuote')) {
  $taulut['autodata']                        = 'Autodatatiedot';
  $taulut['autodata_tuote']                  = 'Autodata tuotetiedot';
  $taulut['yhteensopivuus_auto']             = 'Yhteensopivuus automallit';
  $taulut['yhteensopivuus_auto_2']           = 'Yhteensopivuus automallit 2';
  $taulut['yhteensopivuus_mp']               = 'Yhteensopivuus mp-mallit';
  $taulut['yhteensopivuus_rekisteri']        = 'Yhteensopivuus rekisterinumerot';
  $taulut['yhteensopivuus_tuote']            = 'Yhteensopivuus tuotteet';
  $taulut['yhteensopivuus_tuote_lisatiedot'] = 'Yhteensopivuus tuotteet lis‰tiedot';
  $taulut['yhteensopivuus_tuote_sensori']    = 'Yhteensopivuus tuotteet sensorit';
  $taulut['yhteensopivuus_tuote_sensori_lisatiedot'] = 'Yhteensopivuus tuotteet sensorit lis‰tiedot';
  $taulut['rekisteritiedot_lisatiedot']      = 'Rekisteritiedot lisatiedot';
  $taulut['yhteensopivuus_valmistenumero']   = 'Yhteensopivuus valmistenumero';
}

// Taulut aakkosj‰rjestykseen
asort($taulut);

// Selectoidaan aktiivi
$sel = array_fill_keys(array($table), " selected") + array_fill_keys(array_keys($taulut), '');

echo "<form method='post' name='sendfile' enctype='multipart/form-data'>";
echo "<input type='hidden' name='tee' value='file'>";
echo "<table>";
echo "<tr>";
echo "<th>".t("Valitse tietokannan taulu").":</th>";
echo "<td>";
echo "<select name='table' onchange='submit();'>";

$_taulu = '';

foreach ($taulut as $taulu => $nimitys) {
  echo "<option value='$taulu' {$sel[$taulu]}>".t($nimitys)."</option>";
  if (trim($sel[$taulu]) == 'selected') {
    $_taulu = $taulu;
  }
}

echo "</select>";
echo "</td>";
echo "</tr>";

// Tiettyjen taulujen spessuvalinnat
require "inc/luedata_ja_dataimport_spessuvalinnat.inc";

// Taulujen pakolliset sarakkeet ym kuvauksia.
require "inc/pakolliset_sarakkeet.inc";

if (empty($_taulu)) {
  $taulut = array_flip($taulut);
  $_taulu = array_shift($taulut);
}

list($pakolliset, $kielletyt, $wherelliset, $eiyhtiota, $joinattavat, $saakopoistaa, $oletukset, $eisaaollatyhja) = pakolliset_sarakkeet($_taulu);

$wherelliset = array_merge($wherelliset, $eisaaollatyhja);
$wherelliset = array_unique($wherelliset);

echo "<tr><th>".t("Tietokantataulun pakolliset tiedot").":<br>".t("(N‰it‰ tietoja ei voi muuttaa)")."</th>";
echo "<td><ul><li>".strtolower(implode("</li><li>", $pakolliset))."</li></ul></td></tr>";

if (!empty($wherelliset)) {
  echo "<tr><th>".t("Sarakkeet jotka pit‰‰ aineistossa kertoa").":</td>";
  echo "<td><ul><li>".strtolower(implode("</li><li>", $wherelliset))."</li></ul></td></tr>";
}

if (!empty($kielletyt)) {
  echo "<tr><th>".t("Sarakkeet joita ei saa aineistossa kertoa").":</th>";
  echo "<td><ul><li>".strtolower(implode("</li><li>", $kielletyt))."</li></ul></td></tr>";
}

echo "  <tr><th>".t("Valitse tiedosto").":</th>
    <td><input name='userfile' type='file'></td>
    <td class='back'><input type='submit' name='laheta' value='".t("L‰het‰")."'></td>
  </tr>

  </table>
  </form>
  <br>";

exec("/usr/bin/php {$pupe_root_polku}/data_import_ajo.php > /dev/null 2>/dev/null &");

// N‰ytet‰‰n k‰ytt‰j‰n kaikki LOG filet
$kasitelty = array();
$kasitelty_i = 0;

if ($files = scandir($pupe_root_polku."/datain")) {
  foreach ($files as $file) {
    // T‰m‰ file on valmis lue-data file
    if (substr($file, 0, 11+strlen($kukarow["kuka"])+strlen($kukarow["yhtio"])) == "lue-data#{$kukarow["kuka"]}#{$kukarow["yhtio"]}#" and substr($file, -4) == ".LOG") {

      $log = file_get_contents($pupe_root_polku."/datain/".$file);

      // T‰m‰ logi on jo k‰sitelty
      if (strpos($log, "## LUE-DATA-EOF ##") !== FALSE) {

        // Filename on muotoa: lue-data#username#yhtio#taulu#randombit#alkuperainen_filename#jarjestys.DATAIMPORT.LOG
        $filen_tiedot = explode("#", $file);
        $kuka = $filen_tiedot[1];
        $taulu = $filen_tiedot[3];

        // T‰‰lt‰ voi tulla ties mit‰ lis‰parameja, unohdetaan ne t‰ss‰
        if (strpos($taulu, ".") !== FALSE) {
          $cleantaulu = substr($taulu, 0, strpos($taulu, "."));
        }
        else {
          $cleantaulu = $taulu;
        }

        $orig_file = $filen_tiedot[5];

        $kasitelty[$kasitelty_i]["filename"] = $file;
        $kasitelty[$kasitelty_i]["errfilename"] = substr($file, 0, -3)."ERR";
        $kasitelty[$kasitelty_i]["orig_file"] = $orig_file;
        $kasitelty[$kasitelty_i]["taulu"] = $taulut[$cleantaulu];
        $kasitelty[$kasitelty_i]["aika"] = date("d.m.Y H:i:s", filemtime($pupe_root_polku."/datain/".$file));
        $kasitelty[$kasitelty_i]["lognimi"] = "$kuka-$taulu-".date("Ymd-His", filemtime($pupe_root_polku."/datain/".$file)).".txt";
        $kasitelty[$kasitelty_i]["errnimi"] = "$kuka-$taulu-".date("Ymd-His", filemtime($pupe_root_polku."/datain/".$file)).".xls";
        $kasitelty_i++;
      }
    }
  }
}

if (count($kasitelty) > 0) {

  echo "<font class='head'>".t("Sinun k‰sitellyt ajot").":</font><hr>";

  echo "<table>";
  echo "<tr>";
  echo "<th>".t("Tiedosto")."</th>";
  echo "<th>".t("Taulu")."</th>";
  echo "<th>".t("K‰sitelty")."</th>";
  echo "<th>".t("Lokitiedosto")."</th>";
  echo "<th>".t("Virheelliset rivit")."</th>";
  echo "<th>".t("Poista lokitiedosto")."</th>";
  echo "</tr>";

  foreach ($kasitelty as $file) {
    echo "<tr class='aktiivi'>";
    echo "<td>{$file["orig_file"]}</td>";
    echo "<td>{$file["taulu"]}</td>";
    echo "<td>{$file["aika"]}</td>";
    echo "<td><form method='post' class='multisubmit'>";
    echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
    echo "<input type='hidden' name='kaunisnimi' value='{$file["lognimi"]}'>";
    echo "<input type='hidden' name='datain_filenimi' value='{$file["filename"]}'>";
    echo "<input type='submit' value='".t("Tallenna")."'>";
    echo "</form></td>";
    echo "<td><form method='post' class='multisubmit'>";
    echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
    echo "<input type='hidden' name='kaunisnimi' value='{$file["errnimi"]}'>";
    echo "<input type='hidden' name='datain_filenimi' value='{$file["errfilename"]}'>";
    echo "<input type='submit' value='".t("Tallenna")."'>";
    echo "</form></td>";
    echo "<td><form method='post' onsubmit=\"return confirm('".t("Oletko varma, ett‰ haluat poistaa lokitiedoston?")."')\">";
    echo "<input type='hidden' name='tee' value='poista_file'>";
    echo "<input type='hidden' name='datain_filenimi' value='{$file["filename"]}'>";
    echo "<input type='submit' value='".t("Poista")."'>";
    echo "</form></td>";
    echo "</tr>";
  }

  echo "</table><br><br>";

  echo "<form method='post' onsubmit=\"return confirm('".t("Oletko varma, ett‰ haluat poistaa lokitiedoston?")."')\">";
  echo "<input type='hidden' name='tee' value='poistakaikki_filetsut'>";
  echo "<input type='submit' value='".t("Poista kaikki lokitedostot")."'>";
  echo "</form>";

}

require "inc/footer.inc";
