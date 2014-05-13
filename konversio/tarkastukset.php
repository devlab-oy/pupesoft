<?php

require_once('TarkastuksetCSVDumper.php');

$filepath = dirname(__FILE__);
require_once("{$filepath}/../inc/functions.inc");
require_once("{$filepath}/../inc/connect.inc");

ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(__FILE__)));

ini_set("memory_limit", "5G");

$kukarow = hae_kukarow('admin', $argv[1]);

if ($argv[1] == '') {
  echo "Anna tiedosto\n";
  die;
}

$filepaths = TarkastuksetCSVDumper::split_file($argv[2]);
var_dump($filepaths);
foreach ($filepaths as $filepath) {
//  echo "{$filepath}<br/><br/>";
  $dumper = new TarkastuksetCSVDumper($kukarow, $filepath);
  $dumper->aja();
}
