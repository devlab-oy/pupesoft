#!/usr/bin/php
<?php

	// tässä scp komennon host ja host dir muuttujat, muuta nämä sopiviksi muuhun ei tarvitse koskea
	$scp_host = "root@d90.arwidson.fi";
	$scp_dir  = "/backup/mysql-backup";

	if ($argc == 0) die ("Tätä scriptiä voi ajaa vain komentoriviltä!");

	echo date("d.m.Y @ G:i:s")." - Backup svn.\n";

	$filename = "svn-backup-".date("Y-m-d").".zip";

	// siirrytään svn-dirriin
	chdir("/usr/svn");

	// pakataan failit
	system("/usr/bin/zip -9qr $filename *");

	echo date("d.m.Y @ G:i:s")." - Zip done.\n";

	// kopsataan faili
	$scpma = "scp $filename $scp_host:$scp_dir";
	system($scpma);

	echo date("d.m.Y @ G:i:s")." - Transfer done.\n";

	// dellataan pois
	system("rm -f $filename");

	echo date("d.m.Y @ G:i:s")." - All done.\n";

?>