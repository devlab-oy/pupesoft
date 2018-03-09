<?php

// Kutsutaanko CLI:stä
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
  $php_cli = TRUE;
}

$ok = 0;

if (!function_exists("kasittele_factoring_viite")) {
  function kasittele_factoring_viite($aineistotunnus, $kassatili) {
    global $kukarow, $yhtiorow, $palvelin2, $lopp_pvm, $lopp_tilino, $lopp_tyyppi, $kuitattu_checked;

    $tee_selvittely = false;
    $tee_viitemaksu_kohdistus = false;
    $vastavienti = 0;
    $vastavienti_valuutassa = 0;

    $tilioteselailu_oikrow = tarkista_oikeus("tilioteselailu.php", "%", "", "OK");

    // Otetaan talteen, koska täällä muutellaan
    $kukarow_talteen = $kukarow;

    // Käsitellään uudet tietueet
    $query = "SELECT *
              FROM tiliotedata
              WHERE aineisto = {$aineistotunnus}
              ORDER BY aineisto, tunnus";
    $tiliotedataresult = pupe_query($query);

    $tilioterivilaskuri = 1;
    $tilioterivimaara = mysql_num_rows($tiliotedataresult);

    // Haetaan kannan isoin lasku.tunnus, nin voidaan tehdän sanity-checki EndToEndId:lle.
    $query = "SELECT max(tunnus) maxEndToEndId
              FROM lasku";
    $meteidres = pupe_query($query);
    $meteidrow = mysql_fetch_assoc($meteidres);

    $tiliotedatarow = mysql_fetch_assoc($tiliotedataresult);
    mysql_data_seek($tiliotedataresult, 0);

    $query = "SELECT *
              FROM yriti
              WHERE tilino = '{$tiliotedatarow['tilino']}'";
    $yriti_result = pupe_query($query);
    $yritirow = mysql_fetch_assoc($yriti_result);
    $yhtiorow = hae_yhtion_parametrit($yritirow['yhtio']);
 
    while ($tiliotedatarow = mysql_fetch_assoc($tiliotedataresult)) {
      $tietue = $tiliotedatarow['tieto'];

      // Setataan kukarow-yhtiö
      $kukarow["yhtio"] = $yritirow["yhtio"];

      if ($tiliotedatarow['tyyppi'] == 3) {
        $tee_viitemaksu_kohdistus = true;
        require "inc/viitemaksut.inc";
      }

      // merkataan tämä tiliotedatarivi käsitellyksi
      $query = "UPDATE tiliotedata
                SET kasitelty = now()
                WHERE tunnus = '$tiliotedatarow[tunnus]'";
      $updatekasitelty = pupe_query($query);

      $tilioterivilaskuri++;
    }

    if ($tee_selvittely) {
      $tkesken = 0;
      $maara = $vastavienti;
      $kohdm = $vastavienti_valuutassa;

      echo "<tr><td colspan = '6'>";
      require "inc/teeselvittely.inc";
      echo "</td></tr>";
      echo "</table><br>\n<br>\n";
    }

    if ($tee_viitemaksu_kohdistus) {
      // Tässä tarvitaan kukarow[yhtio], joten ajetaan tämä kaikille firmoille
      $query = "SELECT yhtio from yhtio";
      $yhtiores = pupe_query($query);

      while ($yhtiorow = mysql_fetch_assoc($yhtiores)) {
        // Setataan yhtiorow
        $yhtiorow = hae_yhtion_parametrit($yhtiorow['yhtio']);

        // Setataan kukarow-yhtiö
        $kukarow["yhtio"] = $yhtiorow["yhtio"];

        require "inc/factoring_viitteet_kohdistus.inc";
        require "myyntires/suoritus_asiakaskohdistus_kaikki.php";
      }

      echo "<br><br>";
    }

    // Palautetan kukarow
    $kukarow = $kukarow_talteen;
  }
}


// tehdään tällänen häkkyrä niin voidaan scriptiä kutsua vaikka perlistä..
if ($php_cli) {

  if (!isset($argv[1]) or $argv[1] != 'perl') {
    echo "Parametri väärin!!!\n";
    die;
  }

  if (!isset($argv[2]) or $argv[2] == '') {
    echo "Anna tiedosto!!!\n";
    die;
  }

  // otetaan includepath aina rootista
  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear");
  error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
  ini_set("display_errors", 0);

  require "inc/connect.inc";
  require "inc/functions.inc";

  // Logitetaan ajo
  cron_log($argv[2]);

  $userfile = trim($argv[2]);
  $filenimi = $userfile;
  $ok = 1;
  $palvelin2 = "";

  ob_start();
}
else {
  require "inc/parametrit.inc";

  echo "<font class='head'>Factoring viitemaksujen käsittely</font><hr><br>\n<br>\n";

  echo "<form enctype='multipart/form-data' name='sendfile' method='post'>";
  echo "<table>";
  echo "  <tr>
        <th>".t("Pankin aineisto").":</th>
        <td><input type='file' name='userfile'></td></tr>
        <tr><th>".t("Oletusrahatili").":</th>
        <td><input type='text' name='kassatili'></td>
        <td class='back'><input type='submit' value='".t("Käsittele tiedosto")."'></td>
      </tr>";
  echo "</table>";
  echo "</form><br>\n<br>\n";

  echo "  <script type='text/javascript' language='JavaScript'>
      <!--
        function verify() {
          msg = '".t("Oletko varma?")."';

          if (confirm(msg)) {
            return true;
          }
          else {
            skippaa_tama_submitti = true;
            return false;
          }
        }
      -->
      </script>";
}

$forceta = false;

// Napataan alkuperäinen kukarow
$tiliote_kukarow = $kukarow;

// katotaan onko faili uploadattu
if (isset($_FILES['userfile']['tmp_name']) and is_uploaded_file($_FILES['userfile']['tmp_name'])) {
  $userfile  = $_FILES['userfile']['name'];
  $filenimi  = $_FILES['userfile']['tmp_name'];
  $ok      = 1;
}
elseif (isset($virhe_file) and file_exists("/tmp/".basename($virhe_file))) {
  $userfile  = "/tmp/".basename($virhe_file);
  $filenimi  = "/tmp/".basename($virhe_file);
  $ok      = 1;
  $forceta   = true;
}
if (isset($kassatili)) {
  $query = "SELECT tilino
            FROM tili
            WHERE yhtio  = '$kukarow[yhtio]'
            and tilino = $kassatili";
  $result = pupe_query($query);
  
  if (mysql_num_rows($result) != 1) {
    $ok == 0;
  }    
}
    
if ($ok == 1) {

  $fd = fopen($filenimi, "r");

  if (!($fd)) {
    echo "<font class='message'>Tiedosto '$filenimi' ei auennut!</font>";
    exit;
  }

  $tietue = fgets($fd);

  // Tiliote- tai viiteaineisto
  fclose($fd);

  $aineistotunnus = tallenna_tiliote_viite($filenimi, $forceta);

  if ($aineistotunnus !== false) {
    
    $query = "SELECT *
              FROM tiliotedata
              WHERE aineisto = {$aineistotunnus}
              ORDER BY aineisto, tunnus LIMIT 1";
    $tdataresult = pupe_query($query);
    $tdatarow = mysql_fetch_assoc($tdataresult);
    
    if ($tdatarow['tyyppi'] == 3) {
      kasittele_factoring_viite($aineistotunnus, $kassatili);
    }    
  }

}

if (!$php_cli) {
  // Palautetaan alkuperäinen kukarow
  $kukarow = $tiliote_kukarow;

  require "inc/footer.inc";
}
else {
  $ulosputti = ob_get_contents();
  $ulosputti = str_ireplace(array("<br>", "<br/>", "</tr>"), "\n", $ulosputti);
  $ulosputti = strip_tags($ulosputti);
  ob_end_clean();

  echo $ulosputti;
}

