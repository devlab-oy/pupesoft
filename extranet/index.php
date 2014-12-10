<?php

$extranet = 1;

require "parametrit.inc";

$go = $_POST['go'];
if ($go=='') $go = $_GET['go'];
if ($go=='') $go = 'tervetuloa.php';

echo "<html>
  <head>
  <title>$yhtiorow[nimi] ".t("Extranet")."</title>
  <link rel='shortcut icon' href='".$palvelin2."devlab-shortcut.png'>
  <meta http-equiv='Pragma' content='no-cache'>
  <meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1'>";

if ($kukarow["extranet"] != "") {
  echo $yhtiorow["web_seuranta"];
}

echo "</head>

  <noscript>
    Your browser does not support Javascript!
  </noscript>

  <frameset cols='180,*' frameborder='no'>
    <frame noresize src='indexvas.php' name='menuframe' id='menuframe'>
    <frame src='$go' name='mainframe' id='mainframe'>
    <noframes>Your browser does not support frames!</noframes>
  </frameset>
  </html>";
