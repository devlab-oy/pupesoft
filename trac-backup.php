#!/usr/bin/php
<?php

	// Kutsutaanko CLI:st�
	if (php_sapi_name() != 'cli') {
		die ("T�t� scripti� voi ajaa vain komentorivilt�!");
	}

	// t�ss� scp komennon host ja host dir muuttujat, muuta n�m� sopiviksi muuhun ei tarvitse koskea
	$scp_host = "root@d90.arwidson.fi";
	$scp_dir  = "/backup/mysql-backup";

	echo date("d.m.Y @ G:i:s")." - Backup trac.\n";

	$filename = "trac-backup-".date("Y-m-d").".zip";

	// backupataan kaikki failit
	passthru("/usr/bin/trac-admin /usr/trac hotcopy /tmp/tracbackup &> /dev/null");

	echo date("d.m.Y @ G:i:s")." - Copy done.\n";

	// siirryt��n temppidirriin
	chdir("/tmp/tracbackup");

	// pakataan failit
	system("/usr/bin/zip -9qr $filename *");

	echo date("d.m.Y @ G:i:s")." - Zip done.\n";

	// kopsataan faili
	$scpma = "scp $filename $scp_host:$scp_dir";
	system($scpma);

	echo date("d.m.Y @ G:i:s")." - Transfer done.\n";

	// dellataan pois tempit
	system("rm -rf /tmp/tracbackup");

	echo date("d.m.Y @ G:i:s")." - All done.\n";

?>