#!/usr/bin/php
<?php

// Kutsutaanko CLI:st�
if (php_sapi_name() != 'cli') {
  die ("T�t� scripti� voi ajaa vain komentorivilt�!");
}

// Laitetaan Puperoot includepathiin
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__));
error_reporting(E_ALL);
ini_set("display_errors", 1);

require "inc/connect.inc";
require "inc/functions.inc";

// Logitetaan ajo
cron_log();

function decho($string) {
  echo date("d.m.Y @ G:i:s").": {$string}\n";
}

// Ollaanko annettu --verbose komentorivilt�
$verbose_mode = (in_array("--verbose", $argv) !== false) ? true : false;

$query = "show tables from $dbkanta";
$result = pupe_query($query);

decho("Check tables from $dbkanta.");

while ($row = mysql_fetch_row($result)) {

  $table = $row[0];

  // check table for errors
  $query = "check table $table";
  $chkre = pupe_query($query);
  $chkro = mysql_fetch_assoc($chkre);

  $_table_broken = ($chkro["Msg_text"] != "OK");

  if ($_table_broken or $verbose_mode) {
    decho("$query -> $chkro[Msg_text]");
  }

  if ($_table_broken) {
    // repair table for errors
    $query = "repair table $table";
    $chkre = pupe_query($query);
    $chkro = mysql_fetch_assoc($chkre);

    decho("$query -> $chkro[Msg_text]");
  }

  // optimize table
  $query = "optimize table $table";
  $chkre = pupe_query($query);
  $chkro = mysql_fetch_assoc($chkre);

  $_table_broken = ($chkro["Msg_text"] != "OK" and $chkro["Msg_text"] != "Table is already up to date");

  if ($_table_broken or $verbose_mode) {
    decho("$query -> $chkro[Msg_text]");
  }

  // varmistetaan viel� indexien k�yt�ss�olo
  $query = "show index from $table";
  $chkre = pupe_query($query);

  while ($chkro = mysql_fetch_assoc($chkre)) {
    if (stripos($chkro["Comment"], "disabled") !== FALSE) {
      $query = "alter table $table enable keys";
      $chkre = pupe_query($query);
      decho("$query -> $chkro[Comment]");
      break;
    }
  }
}

decho("Check tables. Done.");
