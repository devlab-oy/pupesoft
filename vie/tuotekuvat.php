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

while($counter < 3000000) 
{
  $counter2 = $counter+10000;
  $query = "SELECT 
            liitetiedostot.filename, 
            liitetiedostot.data,
            tuote.tuotemerkki,
            tuote.tuoteno
           FROM liitetiedostot 
           JOIN tuote on (tuote.tunnus = liitetiedostot.liitostunnus) 
           WHERE liitetiedostot.yhtio = 'mergr' 
           AND liitetiedostot.liitos = 'tuote' 
           AND kayttotarkoitus = 'TK' 
           LIMIT $counter,$counter2
           ";
  $counter = $counter + 10000;
  $liiteres = pupe_query($link, $query);
  system("rm -rf ".getcwd()."/tuotekuvat/*");

  while($liite = mysql_fetch_assoc($liiteres)) {
    usleep(100);
    $liite['tuotemerkki'] = preg_replace("/[^a-zA-Z0-9]/", "", $liite['tuotemerkki']);
    $liite['tuoteno'] = preg_replace("/[^a-zA-Z0-9]/", '', $liite['tuoteno']);
    system("mkdir -p ".getcwd()."/tuotekuvat/".$liite['tuotemerkki']);
    $folde = getcwd()."/tuotekuvat/".$liite['tuotemerkki']."/".$liite['tuoteno'];
    system("mkdir -p ".$folde);
    $_path = $folde."/".str_replace(array("..", "/"), "", $liite['filename']);
    file_put_contents($_path, $liite['data']);
  }
  sleep(5);
}

system("tar -czvf ".getcwd()."/tuotekuvat.tar.gz ".getcwd()."/tuotekuvat");
system("rm -rf ".getcwd()."/tuotekuvat/*");
system("mv tuotekuvat.tar.gz ".getcwd()."/tuotekuvat/");