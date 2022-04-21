<?php

//* Tהmה skripti kהyttהה slave-tietokantapalvelinta *//
$useslave = 1;

if (@include "../inc/connect.inc") {
  require "../inc/functions.inc";
}
elseif (@include "../connect.inc") {
  require "../functions.inc";
}
else {
  exit;
}
ini_set('memory_limit', '4000M');

$session = mysql_real_escape_string($_COOKIE["pupesoft_session"]);

$query = "SELECT * 
          FROM kuka
          WHERE session = '$session'";
$result = pupe_query($query, $GLOBALS["masterlink"]);
$kuka_check_row = mysql_fetch_assoc($result);

if (mysql_num_rows($result) != 1) {
  Header("Location: /");
}

$yhtio = pupesoft_cleanstring($_GET['yhtio']);

$yhtiorow = hae_yhtion_parametrit($yhtio);
if (!$yhtiorow) {
  echo "Vההrה yhtio";
  exit;
}

$query = "SELECT * 
          FROM liitetiedostot
          where yhtio = '$yhtio' 
          AND liitos = 'tuote'";
$liiteres = pupe_query($query);
system("rm -rf ".getcwd()."/tuotekuvat/*");
while($liite = mysql_fetch_assoc($liiteres)) {
  $_path = getcwd()."/tuotekuvat/".$liite['filename'];
  file_put_contents($_path, $liite['data']);
}
system("tar -czvf ".getcwd()."/tuotekuvat.tar.gz ".getcwd()."/tuotekuvat");
system("rm -rf ".getcwd()."/tuotekuvat/*");
system("mv tuotekuvat.tar.gz ".getcwd()."/tuotekuvat/");