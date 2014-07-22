<?php

require_once('TarkastuksetCSVDumper.php');

$filepath = dirname(__FILE__);
require_once("{$filepath}/../inc/functions.inc");
require_once("{$filepath}/../inc/connect.inc");

ini_set("include_path", ini_get("include_path") . PATH_SEPARATOR . dirname(dirname(__FILE__)));

ini_set("memory_limit", '5G');

if ($argv[2] == '') {
  echo "Anna tiedosto\n";
  die;
}

$filepaths = TarkastuksetCSVDumper::split_file($argv[2]);
foreach ($filepaths as $filepath) {
  $output = exec("php /Users/joonas/Dropbox/Sites/pupesoft/konversio/tarkastukset.php {$argv[1]} {$filepath}", $arr, $ret);
}
