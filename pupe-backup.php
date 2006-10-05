#!/usr/bin/php
<?php

	// tässä scp komennon host ja host dir muuttujat, muuta nämä sopiviksi muuhun ei tarvitse koskea
	$scp_host = "root@d90.arwidson.fi";
	$scp_dir  = "/backup/mysql-backup";

	if ($argc == 0) die ("Tätä scriptiä voi ajaa vain komentoriviltä!");

	require ("inc/connect.inc");

	echo date("d.m.Y @ G:i:s")." - Backup $dbkanta.\n";

	$filename = "$dbkanta-backup-".date("Y-m-d").".zip";

	// backupataan kaikki failit
	passthru("/usr/bin/mysqlhotcopy -q -u $dbuser --password=$dbpass $dbkanta /tmp");

	echo date("d.m.Y @ G:i:s")." - Copy done.\n";

	// siirrytään temppidirriin
	chdir("/tmp/$dbkanta");

	// pakataan failit
	system("/usr/bin/zip -9q $filename *");

	echo date("d.m.Y @ G:i:s")." - Zip done.\n";

	// kopsataan faili
	$scpma = "scp $filename $scp_host:$scp_dir";
	system($scpma);

	echo date("d.m.Y @ G:i:s")." - Transfer done.\n";

	// dellataan pois tempit
	system("rm -rf /tmp/$dbkanta");

	echo date("d.m.Y @ G:i:s")." - All done.\n";

?>