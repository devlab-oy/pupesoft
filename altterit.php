#!/usr/bin/php
<?php

  if (php_sapi_name() != 'cli') {
    die("1, Voidaan ajaa vain komentoriviltä\r\n\r\n");
  }

  require('inc/connect.inc');
  require('inc/functions.inc');

  $hname     = php_uname('n');
  $timeparts  = explode(" ",microtime());
  $starttime  = $timeparts[1].substr($timeparts[0],1);

  echo "\nSTART: $hname :$dbkanta\n";

  //$dbkanta --> tulee salasanat.php:stä
  $query  = "SHOW TABLES FROM $dbkanta";
  $tabresult = pupe_query($query);

  while ($tables = mysql_fetch_row($tabresult)) {
    $query  = "describe $tables[0]";
    $fieldresult = pupe_query($query);

    if (substr($tables[0], 0, 3) == "td_") continue;

    $sql = "ALTER TABLE $tables[0]\n";

    $paivitetaanko = FALSE;

    while ($fields = mysql_fetch_assoc($fieldresult)) {

        $column_type    = $fields["Type"];
        $column_name    = $fields["Field"];
      $column_default = $fields["Default"];
      $column_extra   = $fields["Extra"];
      $column_null    = $fields["Null"];

      if (is_null($column_default)) {
        $column_default = "null";
      }

      if ($column_null == "NO") {
        $column_null = "not null";
      }
      else {
        $column_null = "null";
      }

      $default = "";
      $null    = "not null";

      if ($tables[0] == "lasku" and $column_name == "muutospvm") {
        $default = "CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP ";
      }
      elseif ($tables[0] == "taso" and $column_name == "taso") {
        $null    = "CHARACTER SET latin1 COLLATE latin1_bin NOT NULL";
        $default = "''";
      }
      elseif ($column_default != "null" and $column_default != "" and $column_default != "0" and $column_default != "0000-00-00 00:00:00" and $column_default != "0000-00-00" and $column_default != "00:00:00") {
        $default = (!is_numeric($column_default) and $column_default != "CURRENT_TIMESTAMP") ? "'".$column_default."'" : $column_default;
      }
      else {
        $default = stripos($column_type, "char")    !== FALSE ? "''"   : $default;
          $default = stripos($column_type, "decimal") !== FALSE ? "0.0"  : $default;
          $default = stripos($column_type, "float")   !== FALSE ? "0.0"  : $default;
          $default = stripos($column_type, "int")     !== FALSE ? "0"    : $default;
          $default = stripos($column_type, "date")    !== FALSE ? "0"    : $default;
          $default = stripos($column_type, "text")    !== FALSE ? "null" : $default;
          $default = stripos($column_type, "blob")    !== FALSE ? "null" : $default;
        $default = $column_type == "timestamp"  ? "'0000-00-00 00:00:00'"   : $default;
        $default = $column_type == "datetime"  ? "'0000-00-00 00:00:00'"   : $default;
        $default = $column_type == "date"    ? "'0000-00-00'"   : $default;
        $default = $column_type == "time"    ? "'00:00:00'"   : $default;
      }

      if (stripos($column_type, "text") !== FALSE or stripos($column_type, "blob") !== FALSE) {
        $null = "null";
      }

      $default = $fields["Key"] == "PRI" ? "" : $default;

      // Päivitetään vain jos on päivitettävää
      if ($default != "" and ($column_null != $null or $column_default != str_replace("'", "", $default))) {
        #echo "$tables[0]: $column_name $column_type $column_null default $column_default -->  MODIFY COLUMN $column_name $column_type $null default $default\n";
        $sql .= " MODIFY COLUMN $column_name $column_type $null default $default,\n";
        $paivitetaanko = TRUE;
      }
    }

    if ($paivitetaanko) {
      $sql = substr($sql, 0, -2).";\n";
      #echo "$sql\n";
      $result = pupe_query($sql);
    }
  }

  $timeparts = explode(" ",microtime());
  $endtime   = $timeparts[1].substr($timeparts[0],1);
  $aika      = round($endtime-$starttime,4);

  echo "FINISH: $hname :$dbkanta: $aika\n";
