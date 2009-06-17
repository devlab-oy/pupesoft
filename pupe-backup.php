#!/usr/bin/php
<?php

	// tässä muuttujat, muuta nämä sopiviksi muuhun ei tarvitse koskea
	$cp_dir   = "/backup/pupesoft-backup";
	$dbkanta  = "pupesoft";
	$dbuser   = "kayttajanimi";
	$dbpass   = "salasana";
          
	if ($argc == 0) die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
            
	echo date("d.m.Y @ G:i:s")." - Backup $dbkanta.\n";
              
	$filename = "$dbkanta-backup-".date("Y-m-d").".bz2";
                
	// backupataan kaikki failit
	passthru("/usr/bin/mysqlhotcopy -q -u $dbuser --password=$dbpass $dbkanta /tmp");
                    
	echo date("d.m.Y @ G:i:s")." - Copy done.\n";
                      
	// siirrytään temppidirriin
	chdir("/tmp/$dbkanta");
                          
	// pakataan failit
	system("/bin/tar -cjf $filename *");
                              
	echo date("d.m.Y @ G:i:s")." - Bzip2 done.\n";
                                
	// kopsataan faili
	$scpma = "cp $filename $cp_dir";
	system($scpma);
                                      
	echo date("d.m.Y @ G:i:s")." - Copy done.\n";
                                        
	// dellataan pois tempit
	system("rm -rf /tmp/$dbkanta");
	
	// Siivotaan yli 30pv vanhat backupit pois
	system("find $cp_dir -mtime +30 -exec rm -f {} \;");
	                                           
	echo date("d.m.Y @ G:i:s")." - All done.\n";                                              
?>