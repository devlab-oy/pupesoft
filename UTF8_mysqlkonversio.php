<?php

require('inc/connect.inc');
require('inc/functions.inc');

$hname 		  = php_uname('n');
$timeparts	= explode(" ",microtime());
$starttime	= $timeparts[1].substr($timeparts[0],1);

echo "\nSTART: $hname :$dbkanta\n";

$sql = "ALTER DATABASE $dbkanta CHARACTER SET utf8 COLLATE utf8_unicode_ci;";
pupe_query($sql);

$sql = "ALTER TABLE budjetti_tuote drop index tubu;";
pupe_query($sql);

$sql = "CREATE INDEX tubu on budjetti_tuote (yhtio, kausi, tuoteno, osasto(50), try(50));";
pupe_query($sql);

$query  = "SHOW TABLES FROM $dbkanta";
$tabresult = pupe_query($query);

while ($tables = mysql_fetch_row($tabresult)) {

  $query = "SELECT CCSA.character_set_name
            FROM information_schema.`TABLES` T
            JOIN information_schema.`COLLATION_CHARACTER_SET_APPLICABILITY` CCSA ON (CCSA.collation_name = T.table_collation)
            WHERE T.table_schema = '$dbkanta'
            AND T.table_name     = '$tables[0]'";
  $encres = pupe_query($query);
  $encrow = mysql_fetch_row($encres);

  echo "$tables[0] --> $encrow[0]\n";

  if ($encrow[0] != 'utf8') {

    $query  = "describe $tables[0]";
    $fieldresult = pupe_query($query);

    $sql = "";

    while ($fields = mysql_fetch_array($fieldresult)) {

      if (preg_match("/(char\(|varchar\(|text)/", $fields[1])) {
        $nullornot = "";

        if ($fields[2] == "NO") {
          $nullornot = " NOT NULL";
        }

        $sql .= "MODIFY $fields[0] $fields[1] CHARACTER SET utf8 COLLATE utf8_unicode_ci{$nullornot} DEFAULT '$fields[4]', ";
      }
    }

    if (!empty($sql)) {
      $sql = "ALTER TABLE $tables[0] ".substr($sql, 0, -2);
      pupe_query($sql);

      echo "$sql\n";
    }

    $sql = "ALTER TABLE $tables[0] DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;";
		pupe_query($sql);

  	echo "$sql\n";
  }
}
