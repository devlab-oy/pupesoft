<?php

//* T�m� skripti k�ytt�� slave-tietokantapalvelinta *//
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

$session = mysql_real_escape_string($_COOKIE["pupesoft_session"]);

$query = "SELECT *
          FROM kuka
          WHERE session = '$session'";
$result = pupe_query($query, $GLOBALS["masterlink"]);
$kuka_check_row = mysql_fetch_assoc($result);

if (mysql_num_rows($result) != 1) {
  exit;
}

$id = (int) $_GET["id"];

$query = "SELECT *
          FROM liitetiedostot
          where tunnus = '$id'";
$liiteres = pupe_query($query);
$liiterow = mysql_fetch_assoc($liiteres);

if ($kuka_check_row['yhtio'] != $liiterow['yhtio'] and $liiterow['liitos'] != 'kalenteri') {
  exit;
}

$liiterow["selite"] = preg_replace("/[^a-zA-Z0-9]/", "", $liiterow["selite"]);

if (mysql_num_rows($liiteres) > 0) {
  header("Content-type: $liiterow[filetype]");
  header("Content-length: $liiterow[filesize]");
  header("Content-Disposition: inline; filename=$liiterow[filename]");
  header("Content-Description: $liiterow[selite]");

  echo $liiterow["data"];
}
