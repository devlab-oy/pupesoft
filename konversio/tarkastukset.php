<?php

require_once('TarkastuksetCSVDumper.php');
require ("/Users/joonas/Dropbox/Sites/pupesoft/inc/connect.inc");
require ("/Users/joonas/Dropbox/Sites/pupesoft/inc/functions.inc");

ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(__FILE__)));

$kukarow = hae_kukarow('joonas', 'lpk');

if ($argv[1] == '') {
	echo "Anna tiedosto ja akedatan nimi!!! ja yhtiö(ide)n koodi(t)\n";
	die;
}

$dumper = new TarkastuksetCSVDumper($kukarow, $argv[1]);

$dumper->aja();