<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

if (@include "inc/connect.inc") {
  require "inc/functions.inc";
}
elseif (@include "connect.inc") {
  require "functions.inc";
}
else {
  exit;
}

$id = (int) $_GET["id"];

$query = "SELECT *
          from liitetiedostot
          where tunnus = '$id'
          and liitos   in ('kalenteri','tuote','sarjanumeron_lisatiedot','yllapito', 'asiakas')";
$liiteres = pupe_query($query);

if (mysql_num_rows($liiteres) > 0) {
  $liiterow = mysql_fetch_assoc($liiteres);

  header("Content-type: $liiterow[filetype]");
  header("Content-length: $liiterow[filesize]");
  header("Content-Disposition: inline; filename=$liiterow[filename]");
  header("Content-Description: $liiterow[selite]");

  echo $liiterow["data"];
}
