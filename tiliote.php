<?php

// Kutsutaanko CLI:stä
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
  $php_cli = TRUE;
}

$ok = 0;

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

  $userfile = trim($argv[2]);
  $filenimi = $userfile;
  $ok = 1;
  $palvelin2 = "";

  ob_start();
}
else {
  require "inc/parametrit.inc";

  echo "<font class='head'>Tiliotteen, LMP:n, kurssien, verkkolaskujen ja viitemaksujen käsittely</font><hr><br>\n<br>\n";

  echo "<form enctype='multipart/form-data' name='sendfile' method='post'>";
  echo "<table>";
  echo "  <tr>
        <th>".t("Pankin aineisto").":</th>
        <td><input type='file' name='userfile'></td>
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

if ($ok == 1) {

  $fd = fopen($filenimi, "r");

  if (!($fd)) {
    echo "<font class='message'>Tiedosto '$filenimi' ei auennut!</font>";
    exit;
  }

  $tietue = fgets($fd);

  if (substr($tietue, 0, 9) == "<SOAP-ENV" or substr($tietue, 0, 5) == "<?xml" or substr($tietue, 0, 9) == "<Invoice>") {
    // Finvoice verkkolasku
    fclose($fd);

    require "verkkolasku-in.php" ;
  }
  elseif (substr($tietue, 5, 12) == "Tilivaluutan") {
    // luetaanko kursseja

    lue_kurssit($filenimi, $fd);
    fclose($fd);
  }
  elseif (substr($tietue, 0, 7) == "VK01000") {
    // luetaanko kursseja? tyyppi kaks

    lue_kurssit($filenimi, $fd, 2);
    fclose($fd);
  }
  else {
    // Tiliote- tai viiteaineisto
    fclose($fd);

    $aineistotunnus = tallenna_tiliote_viite($filenimi, $forceta);

    if ($aineistotunnus !== false) {
      kasittele_tiliote_viite($aineistotunnus);
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
  $ulosputti = str_ireplace(array("<br>","<br/>","</tr>"), "\n", $ulosputti);
  $ulosputti = strip_tags($ulosputti);
  ob_end_clean();

  echo $ulosputti;
}
