#!/usr/bin/perl

$user  = 'username';
$pass  = 'password';
$host  = 'localhost';
$limit = 600;

use DBI;
use Data::Dumper;

$dbh = DBI->connect("DBI:mysql:mysql", $user, $pass) || die "Tietokantaan ei saatu yhteyttä: $DBI::errstr";

$sth = $dbh->prepare('SHOW SLAVE STATUS');
$sth->execute();
$result = $sth->fetchrow_hashref();

if ($result->{Seconds_Behind_Master} eq "") {
	print "ERROR: MySQL replikointi on jotenkin rikki, Seconds_Behind_Master parametriä ei löydy! SHOW SLAVE STATUS:\n";
	print Dumper($result);
}

if ($result->{Seconds_Behind_Master} >= $limit) {
	$minutes = $result->{Seconds_Behind_Master} / 60;
	printf("ERROR: MySQL Slave on %i minuuttia jäljessä Master Serveriä!\n", $minutes);
}

exit;