<?php

	//keneltä
	$from = 'mailer@pupesoft.com';
	//kenelle
	$to   = 'joni@pupesoft.com';
	
	//parametrit puuttuu
	if (trim($argv[1]) == '') exit;
	
	//$argv[1], on komentorivin argumentti jossa kuuluu olla ajaettava komento
	
	//lähetetään meili
	$header  = "From: <$from>\r\n";
	$header .= "MIME-Version: 1.0\r\n" ;
	$header .= "Content-Type: text/html\r\n" ;
	$header .= "Content-Transfer-Encoding: 7bit\r\n";
	
	//ajetaan skripti
	$content  = system(trim($argv[1]));
	$content .= "\r\n" ;
	
	mail($to, trim($argv[1]), $content, $header);
	
?>
