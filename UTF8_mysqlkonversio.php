<?php

require 'inc/connect.inc';
require 'inc/functions.inc';

$hname      = php_uname('n');
$timeparts  = explode(" ", microtime());
$starttime  = $timeparts[1].substr($timeparts[0], 1);

echo "\nSTART: $hname :$dbkanta\n";

$sql = "ALTER DATABASE $dbkanta CHARACTER SET utf8 COLLATE utf8_unicode_ci;";
pupe_query($sql);

$query  = "SHOW FULL TABLES FROM `$dbkanta` WHERE Table_Type = 'BASE TABLE'";
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

        $sql .= "MODIFY `$fields[0]` $fields[1] CHARACTER SET utf8 COLLATE utf8_unicode_ci{$nullornot} DEFAULT '$fields[4]', ";
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

// Dropataan ja luodaan triggerit uudestaan, niin niihinkin saadaan unicode-kollaatio
pupe_query("DROP trigger sarjanumeroseuranta_insert_trigger");
pupe_query("DROP trigger sarjanumeroseuranta_update_trigger");
pupe_query("DROP trigger tapahtuma_insert_trigger");
pupe_query("DROP trigger tapahtuma_update_trigger");
pupe_query("DROP trigger tilausrivi_insert_trigger");
pupe_query("DROP trigger tilausrivi_update_trigger");
pupe_query("DROP trigger tuotepaikat_insert_trigger");
pupe_query("DROP trigger tuotepaikat_update_trigger");

pupe_query("CREATE trigger sarjanumeroseuranta_insert_trigger before insert on sarjanumeroseuranta for each row set new.varasto = (select varastopaikat.tunnus from varastopaikat where varastopaikat.yhtio = new.yhtio and concat(rpad(upper(varastopaikat.alkuhyllyalue), 5, '0'), lpad(upper(varastopaikat.alkuhyllynro), 5, '0')) <= concat(rpad(upper(new.hyllyalue), 5, '0'), lpad(upper(new.hyllynro), 5, '0')) and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'), lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(new.hyllyalue), 5, '0'), lpad(upper(new.hyllynro), 5, '0')))");
pupe_query("CREATE trigger sarjanumeroseuranta_update_trigger before update on sarjanumeroseuranta for each row set new.varasto = (select varastopaikat.tunnus from varastopaikat where varastopaikat.yhtio = new.yhtio and concat(rpad(upper(varastopaikat.alkuhyllyalue), 5, '0'), lpad(upper(varastopaikat.alkuhyllynro), 5, '0')) <= concat(rpad(upper(new.hyllyalue), 5, '0'), lpad(upper(new.hyllynro), 5, '0')) and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'), lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(new.hyllyalue), 5, '0'), lpad(upper(new.hyllynro), 5, '0')))");
pupe_query("CREATE trigger tapahtuma_insert_trigger before insert on tapahtuma for each row set new.varasto = (select varastopaikat.tunnus from varastopaikat where varastopaikat.yhtio = new.yhtio and concat(rpad(upper(varastopaikat.alkuhyllyalue), 5, '0'), lpad(upper(varastopaikat.alkuhyllynro), 5, '0')) <= concat(rpad(upper(new.hyllyalue), 5, '0'), lpad(upper(new.hyllynro), 5, '0')) and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'), lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(new.hyllyalue), 5, '0'), lpad(upper(new.hyllynro), 5, '0')))");
pupe_query("CREATE trigger tapahtuma_update_trigger before update on tapahtuma for each row set new.varasto = (select varastopaikat.tunnus from varastopaikat where varastopaikat.yhtio = new.yhtio and concat(rpad(upper(varastopaikat.alkuhyllyalue), 5, '0'), lpad(upper(varastopaikat.alkuhyllynro), 5, '0')) <= concat(rpad(upper(new.hyllyalue), 5, '0'), lpad(upper(new.hyllynro), 5, '0')) and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'), lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(new.hyllyalue), 5, '0'), lpad(upper(new.hyllynro), 5, '0')))");
pupe_query("CREATE trigger tilausrivi_insert_trigger before insert on tilausrivi for each row set new.varasto = (select varastopaikat.tunnus from varastopaikat where varastopaikat.yhtio = new.yhtio and concat(rpad(upper(varastopaikat.alkuhyllyalue), 5, '0'), lpad(upper(varastopaikat.alkuhyllynro), 5, '0')) <= concat(rpad(upper(new.hyllyalue), 5, '0'), lpad(upper(new.hyllynro), 5, '0')) and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'), lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(new.hyllyalue), 5, '0'), lpad(upper(new.hyllynro), 5, '0')))");
pupe_query("CREATE trigger tilausrivi_update_trigger before update on tilausrivi for each row set new.varasto = (select varastopaikat.tunnus from varastopaikat where varastopaikat.yhtio = new.yhtio and concat(rpad(upper(varastopaikat.alkuhyllyalue), 5, '0'), lpad(upper(varastopaikat.alkuhyllynro), 5, '0')) <= concat(rpad(upper(new.hyllyalue), 5, '0'), lpad(upper(new.hyllynro), 5, '0')) and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'), lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(new.hyllyalue), 5, '0'), lpad(upper(new.hyllynro), 5, '0')))");
pupe_query("CREATE trigger tuotepaikat_insert_trigger before insert on tuotepaikat for each row set new.varasto = (select varastopaikat.tunnus from varastopaikat where varastopaikat.yhtio = new.yhtio and concat(rpad(upper(varastopaikat.alkuhyllyalue), 5, '0'), lpad(upper(varastopaikat.alkuhyllynro), 5, '0')) <= concat(rpad(upper(new.hyllyalue), 5, '0'), lpad(upper(new.hyllynro), 5, '0')) and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'), lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(new.hyllyalue), 5, '0'), lpad(upper(new.hyllynro), 5, '0'))), new.hyllypaikka = concat(new.hyllyalue, new.hyllynro, new.hyllyvali, new.hyllytaso)");
pupe_query("CREATE trigger tuotepaikat_update_trigger before update on tuotepaikat for each row set new.varasto = (select varastopaikat.tunnus from varastopaikat where varastopaikat.yhtio = new.yhtio and concat(rpad(upper(varastopaikat.alkuhyllyalue), 5, '0'), lpad(upper(varastopaikat.alkuhyllynro), 5, '0')) <= concat(rpad(upper(new.hyllyalue), 5, '0'), lpad(upper(new.hyllynro), 5, '0')) and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'), lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(new.hyllyalue), 5, '0'), lpad(upper(new.hyllynro), 5, '0'))), new.hyllypaikka = concat(new.hyllyalue, new.hyllynro, new.hyllyvali, new.hyllytaso)");
