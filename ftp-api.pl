#!/usr/bin/perl
#
# Haetaan kaikki uudet verkkolaskut elmasta kaikkille pupesoft yrityksille..
#
# Käytetään ftp apia..
#
# (c) joni 200309 & 201409
#
# -joni@pupesoft.com

use Net::FTP;
use DBI;
use FileHandle;
use Fcntl qw(:flock);

#print "\npupesoft einvoice ftp-api v1.1\n------------------------------\n\n";

$dbhost  = $ARGV[0]; # 'localhost';           # pupesoft hosti
$dbuser  = $ARGV[1]; # 'pupesoft';            # pupesoft database käyttäjä
$dbpass  = $ARGV[2]; # 'pupe1';               # pupesoft database salasana
$dbname  = $ARGV[3]; # 'pupesoft';            # pupesoft databasen nimi
$path    = $ARGV[4]; # '/home/verkkolaskut';  # mihin hakemistoon haetut laskut siirretään

$host    = "ftp.verkkolasku.net";             # mistä haetaan
$type    = "xml";                             # minkä tyyppiset laskut haetaan
$tmpfile = "/tmp/##pupesoft-ftp-api-tmp";     # lukkotiedosto

if ($dbhost eq "" || $dbuser eq "" || $dbpass eq "" || $dbname eq "" || $path eq "") {
  print "invalid arguments!\n";
  exit;
}

if (! -d $path) {
  print "invalid directory: $path\n";
  exit;
}

sysopen("tmpfaili", $tmpfile, O_CREAT) or die("failed to open $tmpfile");
flock("tmpfaili", LOCK_EX) or die("lock failed: $tmpfile");

# Siirrytään hakemistoon
chdir($path) or die("directory permission denied: $path");

$statement = "SELECT verkkotunnus_vas, verkkosala_vas, nimi FROM yhtio JOIN yhtion_parametrit USING (yhtio) WHERE verkkotunnus_vas  != '' AND verkkosala_vas != ''";

$dbh = DBI->connect("DBI:mysql:database=$dbname;host=$dbhost", $dbuser, $dbpass) or die("Connection failed: database=$dbname, host=$dbhost, user=$dbuser, passwd=$dbpass\n\n");
$sth = $dbh->prepare($statement) or die($dbh->errstr." \n\n $statement\n\n");
$rv  = $sth->execute or die($sth->errstr." \n\n $statement\n\n");

while (@row = $sth->fetchrow_array) {
  $user = $row[0];
  $pass = $row[1];
  $nimi = $row[2];

  $ftp = Net::FTP->new($host, ('Debug' => 0, 'Passive' => 1)) or die "Net::FTP Initialization failed, host=$host\n\n";
  $ftp->login($user, $pass) or die "FTP login failed, host=$host, user=$user, passwd=$pass\n\n";

  @list=$ftp->dir("/bills-new/by-ebid");

  foreach $name (@list) {
    $file = $name."data.".$type;
    $ebid = substr($name, 19, 35);

    if ($ftp->get($file, $ebid)) {
      print "Haettiin yritykselle: $nimi lasku: $ebid\n";
      $ftp->delete($file) or die "Failed FTP command: delete\nFTP return code: ".$ftp->code."\nFTP return msg:  ".$ftp->message."\n\n";
    }
    else {
      die "Failed FTP command: get\nFTP return code: ".$ftp->code."\nFTP return msg:  ".$ftp->message."\n\n";
    }
  }

  $ftp->quit();
}
