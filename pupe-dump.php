#!/usr/bin/php
<?php

	// tässä muuttujat, muuta nämä sopiviksi muuhun ei tarvitse koskea
	$cp_dir  = "/backup/pupesoft-backup";
	$dbkanta = "pupesoft";
	$dbuser  = "pupesoft";
	$dbpass  = "pupesoft1";

	if ($argc == 0) die ("Tätä scriptiä voi ajaa vain komentoriviltä!");

	echo date("d.m.Y @ G:i:s")." - Backup $dbkanta.\n";

	$filename = "/tmp/$dbkanta-backup-".date("Y-m-d").".sql";

	// tehdään mysqldump
	system("mysqldump -u $dbuser --password=$dbpass $dbkanta > $filename");

	echo date("d.m.Y @ G:i:s")." - MySQL dump done.\n";

	// pakataan failit
	system("/usr/bin/pbzip2 $filename");

	echo date("d.m.Y @ G:i:s")." - Bzip2 done.\n";

	// siirretään faili
	system("mv $filename.bz2 $cp_dir");

	// Siivotaan yli 30pv vanhat backupit pois
	chdir($cp_dir);
	system("find $cp_dir -mtime +30 -exec rm -f {} \;");

	echo date("d.m.Y @ G:i:s")." - All done.\n";

?>