<?php

if (@include_once "inc/functions.inc");
elseif (@include_once "functions.inc");
else exit;

// Parametrit:
// $ftphost --> FTP-palvelin
// $ftpuser --> K‰ytt‰j‰tunnus
// $ftppass --> Salasana
// $ftppath --> Kansio FTP-palvelimella jonne failit tyˆnnet‰‰n
// $ftpfile --> Siirrett‰v‰ tiedosto
// $ftpport --> Custom portti, ei pakollinen
// $ftpfail --> Kansio jonne laitetaan failit jos siirto feilaa, ei pakollinen
// $ftpsucc --> Kansio jonne tallennetaan failit jos siirto onnistuu, ei pakollinen
// $ftptype --> FTP-palvelimen tyyppi esim "AS400", ei pakollinen
// $ftptmpr --> K‰ytet‰‰nkˆ .TMP p‰‰tett‰ siirron aikana.

// Palautetaan:
// $palautus

// Debug:
// echo "ftphost --> $ftphost<br>";
// echo "ftpuser --> $ftpuser<br>";
// echo "ftppass --> $ftppass<br>";
// echo "ftppath --> $ftppath<br>";
// echo "ftpfile --> $ftpfile<br>";
// echo "ftpport --> $ftpport<br>";
// echo "ftpfail --> $ftpfail<br>";
// echo "ftpsucc --> $ftpsucc<br>";

$palautus = "";
$tulos_ulos_ftp  = "";
$sftpport = !empty($ftpport) ? $ftpport : 22;
$filenimi = basename($ftpfile);

// jos viimeinen merkki pathiss‰ ei ole kauttaviiva lis‰t‰‰n kauttaviiva...
if (substr($ftppath, -1) != "/") {
  $ftppath .= "/";
}

$connection = ssh2_connect($ftphost, $sftpport);

if (!$connection) {
  $palautus = "Could not connect to remote host. ($ftphost)";
}

if ($palautus == "" and !ssh2_auth_password($connection, $ftpuser, $ftppass)) {
  $palautus = "Could not login to remote host ($ftpuser, $ftppass)";
}

if ($palautus == "") {
  $sftp = ssh2_sftp($connection);

  if (!$sftp) {
    $palautus = "Could not initialize SFTP subsystem";
  }
}

if ($palautus == "") {
  $remotefile = $ftppath.basename($ftpfile);

  $stream = fopen("ssh2.sftp://$sftp/$remotefile", "w");

  if (!$stream) {
    $palautus = "Could not open file ($remotefile)";
  }

  if ($palautus == "") {
    $data_to_send = file_get_contents($ftpfile);

    if (fwrite($stream, $data_to_send) === false) {
      $palautus = "Could not send data from file: $ftpfile";
    }

    fclose($stream);
  }
}

// Siirto onnistui
if ($palautus == "" and isset($ftpsucc) and $ftpsucc != "") {
  // Siirret‰‰n faili talteen jos siirto onnasi ja ok failit halutaan tallentaa
  rename($ftpfile, $ftpsucc."/".$filenimi);
}
elseif ($palautus != "" and isset($ftpfail) and $ftpfail != "") {
  // Siirret‰‰n faili outboxiin odottamaan uutta yrityst‰ jos siirto feilasi
  rename($ftpfile, $ftpfail."/".$filenimi);
}

// logia talteen
if ($palautus == "") {
  pupesoft_log('sftp_send', "Tiedoston '{$ftpfile}' l‰hetys palvelimelle '{$ftphost}' onnistui");
}
else {
  pupesoft_log('sftp_send', "Tiedoston '{$ftpfile}' l‰hetys palvelimelle '{$ftphost}' ep‰onnistui: {$palautus}");
}

// jos siirto ep‰onnistuu ja faildirri ei oo setattu nin l‰hetet‰‰n maili
// jos faildirri ON setattu niin ei ekoteta eik‰ meilata mitt‰‰n
if ($palautus != "" and (!isset($ftpfail) or $ftpfail == "")) {

  $rivi  = "{$_SERVER['SCRIPT_NAME']}\n";
  $rivi .= "\n";
  $rivi .= t("Tiedoston")." '$ftpfile' ".t("l‰hetys ep‰onnistui")."!\n";
  $rivi .= "\n";
  $rivi .= "$cmd\n";
  $rivi .= "\n";
  $rivi .= "$palautus\n";

  $boob = mail($yhtiorow['alert_email'], mb_encode_mimeheader(t("Tiedostonsiirto ep‰onnistui")."!", "ISO-8859-1", "Q"), $rivi, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");

  if ($boob === FALSE) {
    $tulos_ulos_ftp .= "<font class='error'>\n";
    $tulos_ulos_ftp .= t("Tiedostonsiirto ep‰onnistui! Ja meilin l‰hett‰minen")." $yhtiorow[alert_email] ".t("ep‰onnistui myˆs!")."\n";
    $tulos_ulos_ftp .= "</font><br>\n";

    $tulos_ulos_ftp .= "<br><pre>$rivi</pre><br>\n";
  }
  else {
    $tulos_ulos_ftp .= "<font class='error'>FAILED! (".t("l‰hetettiin s‰hkˆposti")." $yhtiorow[alert_email])</font><br>\n";
  }
}

//echotaan rudulle jos kyseess‰ ei ole batch-ajo
if (!isset($tulos_ulos) or $tulos_ulos == "") {
  echo $tulos_ulos_ftp;
}
elseif ($tulos_ulos == "edi") {
  $edi_ulos .= strip_tags($tulos_ulos_ftp);
}
