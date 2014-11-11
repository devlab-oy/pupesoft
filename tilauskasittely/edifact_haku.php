<?php

require "../inc/parametrit.inc";
require "../inc/edifact_functions.inc";

if ($task == 'nollaa') {
  $taulut = array(
      "tilausrivi",
      "tilausrivin_lisatiedot",
      "lasku",
      "laskun_lisatiedot",
      "sarjanumeroseuranta",
      "liitetiedostot"
  );

  foreach ($taulut as $taulu) {
    $query = "TRUNCATE TABLE {$taulu}";
    pupe_query($query);
  }
}

if ($task == 'hae') {

  $host = $ftp_info['host'];
  $user = $ftp_info['user'];
  $pass = $ftp_info['pass'];

  // Connect to host
  $yhteys = ftp_connect($host);

  // Open a session to an external ftp site
  $login = ftp_login($yhteys, $user, $pass);

  // Check open
  if ((!$yhteys) || (!$login)) {
    echo t("Ftp-yhteyden muodostus epaonnistui! Tarkista salasanat."); die;
  }
  else {
    echo t("Ftp-yhteys muodostettu.")."<br/>";
  }

  ftp_chdir($yhteys, 'out-prod');

  ftp_pasv($yhteys, true);

  $files = ftp_nlist($yhteys, ".");

  foreach ($files as $file) {

    if (substr($file, -3) == 'IFF') {
      $bookkaukset[] = $file;
    }

    if (substr($file, -3) == 'DAD') {
      $rahtikirjat[] = $file;
    }

    if (substr($file, -3) == 'IFS') {
      $iftstat[] = $file;
    }

  }

  foreach ($bookkaukset as $bookkaus) {
    $temp_file = tempnam("/tmp", "IFF-");
    ftp_get($yhteys, $temp_file, $bookkaus, FTP_ASCII);
    $edi_data = file_get_contents($temp_file);
    kasittele_bookkaussanoma($edi_data);
    unlink($temp_file);
  }

  foreach ($rahtikirjat as $rahtikirja) {
    $temp_file = tempnam("/tmp", "DAD-");
    ftp_get($yhteys, $temp_file, $rahtikirja, FTP_ASCII);
    $edi_data = file_get_contents($temp_file);
    kasittele_rahtikirjasanoma($edi_data);
    unlink($temp_file);
  }

  foreach ($iftstat as $iftsta) {
    $temp_file = tempnam("/tmp", "IFT-");
    ftp_get($yhteys, $temp_file, $iftsta, FTP_ASCII);
    $edi_data = file_get_contents($temp_file);
    kasittele_iftsta($edi_data);
    unlink($temp_file);
  }

  ftp_close($yhteys);

}
else{

  echo "
  <font class='head'>".t("Sanomien haku")."</font>  <br><hr>
  <form action='' method='post'>
    <input type='hidden' name='task' value='hae' />
    <input type='submit' value='".t("Hae sanomat (ftp)")."'>
  </form>

  <br><hr><br>

  <form action='' method='post'>
    <input type='hidden' name='task' value='nollaa' />
    <input type='submit' value='".t("Nollaa tilanne")."'>
  </form>

  ";

}

require "inc/footer.inc";

