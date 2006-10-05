#!/usr/bin/perl
#
# Haetaan kaikki uudet verkkolaskut elmasta kaikkille pupesoft yrityksille..
#
# K‰ytet‰‰n ftp apia..
#
# (c) joni 200309
#
# -joni@pupesoft.com

use Net::FTP;
use DBI;

#print "\npupesoft einvoice ftp-api v1.0\n------------------------------\n\n";

$dbhost = 'localhost';					# pupesoft hosti
$dbuser = 'pupesoft';					# pupesoft database k‰ytt‰j‰
$dbpass = 'pupe1';						# pupesoft database salasana
$dbname = 'pupesoft';					# pupesoft databasen nimi
$host   = "ftp.verkkolasku.net";		# elman ftp palvelimen osoite
$path   = "/home/verkkolaskut";			# mihin hakemistoon haetut laskut siirret‰‰n
$type   = "xml";						# mink‰ tyyppiset laskut haetaan

chdir($path) or die("Can't change to $path\n\n");

$statement = "select verkkotunnus_vas, verkkosala_vas, nimi from yhtio join yhtion_parametrit using (yhtio) where verkkotunnus_vas<>'' and verkkosala_vas<>''";

$dbh       = DBI->connect("DBI:mysql:database=$dbname;host=$dbhost", $dbuser, $dbpass)	or die("Connection failed: database=$dbname, host=$dbhost, user=$dbuser, passwd=$dbpass\n\n");
$sth       = $dbh->prepare($statement)	or die($dbh->errstr." \n\n $statement\n\n");
$rv        = $sth->execute				or die($sth->errstr." \n\n $statement\n\n");

while (@row = $sth->fetchrow_array) {

	$user = $row[0];
	$pass = $row[1];
	$nimi = $row[2];

	#print "Haetaan laskuja yritykselle: $nimi\n\n";

	$ftp = Net::FTP->new($host, ('Debug'=>0,'Passive'=>0)) or die "Net::FTP Initialization failed, host=$host\n\n";
	$ftp->login($user,$pass) or die "FTP login failed, host=$host, user=$user, passwd=$pass\n\n";

	@list=$ftp->dir("/bills-new/by-ebid");

	foreach $name (@list) {
		$file = $name."data.".$type;
		$ebid = substr($name,19,35);

		if ($ftp->get($file,$ebid)) {
			print "Haettiin yritykselle: $nimi lasku: $ebid\n";
			$ftp->delete($file) or die "Failed FTP command: delete\nFTP return code: ".$ftp->code."\nFTP return msg:  ".$ftp->message."\n\n";
		}
		else {
			die "Failed FTP command: get\nFTP return code: ".$ftp->code."\nFTP return msg:  ".$ftp->message."\n\n";
		}
	}
	$ftp->quit();
	#print "\n\n";
}