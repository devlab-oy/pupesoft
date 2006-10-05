#!/usr/bin/perl

use FileHandle;

print "\npupesoft tiliote.pl v0.1a\n-------------------------\n\n";

$dirri  = "/tiliotteet/uudet/";				# dirri mistä etsitään tiliotefaileja
$done   = "/tiliotteet/valmiit/";			# dirri minne käsitellyt filet siirretään
$php    = "/usr/bin/php";				# php executable
$script = "/home/pupesoft/public_html/tiliote.php";	# polku mistä tiliote.php löytyy

opendir($hakemisto, $dirri);

while ($file = readdir($hakemisto))
{
	$nimi = $dirri.$file;
	
	if (-f $nimi)
	{
		system("$php $script perl $nimi");
		system("mv -f $nimi $done");
	}

}
