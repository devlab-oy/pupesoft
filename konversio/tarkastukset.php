<?php

require_once('TarkastuksetCSVDumper.php');

$filepath = dirname(__FILE__);
require_once("{$filepath}/../inc/functions.inc");
require_once("{$filepath}/../inc/connect.inc");

ini_set("include_path", ini_get("include_path") . PATH_SEPARATOR . dirname(dirname(__FILE__)));

ini_set("memory_limit", '5G');

$kukarow = hae_kukarow('admin', $argv[1]);

if ($argv[2] == '') {
  echo "Anna tiedosto\n";
  die;
}

$dumper = new TarkastuksetCSVDumper($kukarow, $argv[2]);
$dumper->aja();
