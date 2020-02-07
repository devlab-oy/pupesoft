<?php

$extranet = 1;

require "parametrit.inc";

$query = "SELECT nimi
          FROM oikeu use index (sovellus_index)
          WHERE yhtio  = '$kukarow[yhtio]'
          AND kuka     = '$kukarow[kuka]'
          AND sovellus = 'Futursoft'
          AND nimi     = 'futursoft.php'";
$xresult = mysql_query($query) or pupe_error($query);

if (mysql_num_rows($xresult) > 0) {

  $go = $_REQUEST['go'];
  if ($go == "") $go = 'tervetuloa.php';

  echo "<html>";
  echo "<head>";
  echo "<title>$yhtiorow[nimi] Futursoft</title>";

  if (file_exists("pics/pupeicon.gif")) {
    echo "<link rel='shortcut icon' href='pics/pupeicon.gif'>\n";
  }
  else {
    echo "<link rel='shortcut icon' href='".$palvelin2."devlab-shortcut.png'>\n";
  }

  echo "<meta http-equiv='Pragma' content='no-cache'>";
  echo "<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1'>";
  echo "</head>";

  echo "<noscript>";
  echo "Your browser does not support Javascript!";
  echo "</noscript>";

  echo "<frameset cols='180,*' border='0' frameborder='no'>";
  echo "<frame noresize src='indexvas.php?ostoskori=".$ostoskori."&tultiin=futur' name='menu'>";
  echo "<frame src='$go' name='main'>";
  echo "<noframes>Your browser does not support frames!</noframes>";
  echo "</frameset>";
  echo "</html>";
}
else {
  $futuvanha = 1;
  require "yhteensopivuus.php";
}
