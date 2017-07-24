#!/usr/bin/perl

use FileHandle;
use Net::SMTP;
use Fcntl qw(:flock);
use Cwd 'abs_path';
use File::Basename;

# Kutsu tätä crontabista esimerkiksi:
# */5 * * * * root /usr/bin/perl /home/devlab/pupesoft/editilaus.pl "/home/editilaus/" "/home/editilaus/done/" "/home/editilaus/error/" "user@example.com" "pupesoft@example.com"

$dirri1    = $ARGV[0]; # mistä haetaan
$dirri2    = $ARGV[1]; # minne siirretään
$dirri3    = $ARGV[2]; # minne siirretään kun erroroi
$email     = $ARGV[3]; # kenelle meilataan jos on ongelma
$emailfrom = $ARGV[4]; # millä osoitteella meili lähetetään
$lisaparametri = $ARGV[5]; # mahdolliset lisäparametrit editilaus_in.inc:lle

$pupedir = abs_path(dirname($0)) . "/tilauskasittely/"; # pupesoftin tilauskäsittely hakemisto
$komento = "/usr/bin/php";                              # polku php -komentoon
$tmpfile = "/tmp/##pupesoft-edi-tmp";                   # lukkotiedosto

if ($dirri1 eq "" || $dirri2 eq "" || $dirri3 eq "" || $email eq "" || $emailfrom eq "") {
  print "invalid arguments!\n";
  exit;
}

if (! -d $dirri1) {
  print "invalid directory: $dirri1\n";
  exit;
}

if (! -d $dirri2) {
  print "invalid directory: $dirri2\n";
  exit;
}

if (! -d $dirri3) {
  print "invalid directory: $dirri3\n";
  exit;
}

sysopen("tmpfaili", $tmpfile, O_CREAT) or die("Failin $tmpfile avaus epäonnistui!");
flock("tmpfaili", LOCK_EX | LOCK_NB) or die("Lukkoa ei saatu fileen: $tmpfile");

opendir($hakemisto, $dirri1);

while ($file = readdir($hakemisto)) {

  my $extchk = substr $file, -4;

  $nimi = $dirri1.$file;
  $ok = 0;

  if (-f $nimi && $extchk ne '.tmp') {

    # loopataan tätä failia kunnes ok
    while ($ok < 1) {

      if ($vnimi eq $nimi) {
        $laskuri++;
      }
      else {
        $laskuri=0;
      }

      $vnimi=$nimi;

      open("faili", $nimi) or die("Failin $nimi avaus epäonnistui.");
      @rivit = <faili>;
      $ok=0;

      $whole_file="";

      foreach $rivi (@rivit) {

        $whole_file.=$rivi;

        if ($rivi=~m"ICHG__END") {
          $laskuri=0;
          $ok=1;  # loppumerkki löytyi file on ok!
          $edi_tyyppi="";
          last;
        }
        if ($rivi=~m"\*IE") {
          $laskuri=0;
          $ok=1;  # loppumerkki löytyi file on ok!
          $edi_tyyppi=" futursoft";  # tää on futurifaili, (huom. space tärkeä)
          last;
        }
        if ($rivi=~m"UNS\+S") {
          $laskuri=0;
          $ok=1;  # loppumerkki löytyi file on ok!
          $edi_tyyppi=" edifact911";  # tää on orderfaili, (huom. space tärkeä)
          last;
        }
      }

      close("faili");

      # edifact911 failit tulee 80 merkkiä pitkillä rivillä, joten 'UNS\+S'-tägi voi olla kahdella rivillä
      # otetaan tässä koko faili stringiin ja katotaan löytyykö haettu tägi
      if ($ok < 1) {

        $whole_file =~ s/\n//g;

        $result = index($whole_file, "'UNS+S'");

        if ($result >= 0) {
          $laskuri=0;
          $ok=1;  # loppumerkki löytyi file on ok!
          $edi_tyyppi=" edifact911";  # tää on orderfaili, (huom. space tärkeä)
        }
      }

      if ($ok > 0) {
        #print "pupesoft editilaus.pl v1.1\n--------------------------\n\n";
        #print "Edi-tilaus $file" . "\n";

        $cmd = "cd ".$pupedir.";".$komento. " ".$pupedir."editilaus_in.inc ".$nimi.$edi_tyyppi." ".$lisaparametri;
        system($cmd);

        $cmd = "mv -f $nimi $dirri2";
        system($cmd);

        # ulos loopista
        $ok=1;
      }

      # jos ollaan luupattu samaa failia 10 kertaa, ni siirreätän se pois...
      if ($laskuri > 10) {
        $smtp = Net::SMTP->new('localhost');
        $smtp->mail($emailfrom);
        $smtp->to($email);
        $smtp->data();
        $smtp->datasend("Subject: Editilaus ERROR!\n\n");
        $smtp->datasend("\nEditilaus: ".$nimi." taitaa olla viallinen. Siirrettiin faili $dirri3 hakemistoon. Tutki asia!");
        $smtp->dataend();
        $smtp->quit;

        $cmd = "mv -f $nimi $dirri3";
        system($cmd);

        # ulos loopista
        $ok=1;
      }

      # jos file ei ollu tällä loopilla ok, odotetaan 1 sec
      if ($ok < 1) {
        sleep(1);
      }
    }
  }
}

# siivotaan yli 90 päivää vanhat aineistot
system("find $dirri2 -type f -mtime +90 -delete");
