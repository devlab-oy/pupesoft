#!/usr/bin/perl

use FileHandle;
use Net::SMTP;

$dirri1    = "/home/elma/elma/edi/autolink_orders/";    # mist� haetaan
$dirri2    = "/home/elma/elma/edi/autolink_orders/done/"; # minne siirret��n
$dirri3    = "/home/elma/elma/edi/autolink_orders/errors/"; # minne siirret��n kun erroroi
$pupedir   = "/var/www/html/pupesoft/tilauskasittely/"; # pupesoftin tilausk�sittely hakemisto
$komento   = "/usr/bin/php"; # ajettava komento
$email     = "development\@devlab.fi"; # kenelle meilataan jos on ongelma
$emailfrom = "development\@devlab.fi"; # mill� osoitteella meili l�hetet��n
$tmpfile   = "/tmp/##edi-tmp";   # minne tehd��n lock file

# jos lukkofaili l�ytyy, mutta se on yli 15 minsaa vanha niin dellatan se
if (-f $tmpfile) {
	$mode = (stat($tmpfile))[9];
	$now = time();

	$smtp = Net::SMTP->new('localhost');
	$smtp->mail($emailfrom);
	$smtp->to($email);
	$smtp->data();
	$smtp->datasend("Subject: Editilaus HUOM!\n\n");
	$smtp->datasend("\nEditilausten sis��nluvussa saattaa olla ongelma. Lukkotiedosto oli yli 15 minuuttia vanha ja se poistettiin. Tutki asia!");
	$smtp->dataend();
	$smtp->quit;

	if ($now - $mode > 900) {
		system("rm -f $tmpfile");
	}
}

if (!-f $tmpfile) {

	system("touch $tmpfile");
	opendir($hakemisto, $dirri1);

	while ($file = readdir($hakemisto)) {

		my $extchk = substr $file, -4;

		$nimi = $dirri1.$file;
		$ok = 0;

		if (-f $nimi && $extchk ne '.tmp') {

			# loopataan t�t� failia kunnes ok
			while ($ok < 1) {

				if ($vnimi eq $nimi) {
					$laskuri++;
				}
				else {
					$laskuri=0;
				}

				$vnimi=$nimi;

				open("faili", $nimi) or die("Failin $nimi avaus ep�onnistui.");
				@rivit = <faili>;
				$ok=0;

				$whole_file="";

				foreach $rivi (@rivit) {

					$whole_file.=$rivi;

					if ($rivi=~m"ICHG__END") {
						$laskuri=0;
						$ok=1;  # loppumerkki l�ytyi file on ok!
						$edi_tyyppi="";
						last;
					}
					if ($rivi=~m"\*IE") {
						$laskuri=0;
						$ok=1;  # loppumerkki l�ytyi file on ok!
						$edi_tyyppi=" futursoft";  # t�� on futurifaili, (huom. space t�rke�)
						last;
					}
					if ($rivi=~m"UNS\+S") {
						$laskuri=0;
						$ok=1;  # loppumerkki l�ytyi file on ok!
						$edi_tyyppi=" edifact911";  # t�� on orderfaili, (huom. space t�rke�)
						last;
					}
				}

				close("faili");

				# edifact911 failit tulee 80 merkki� pitkill� rivill�, joten 'UNS\+S'-t�gi voi olla kahdella rivill�
				# otetaan t�ss� koko faili stringiin ja katotaan l�ytyyk� haettu t�gi
				if ($ok < 1) {

					$whole_file =~ s/\n//g;

					$result = index($whole_file, "'UNS+S'");

					if ($result >= 0) {
						$laskuri=0;
						$ok=1;  # loppumerkki l�ytyi file on ok!
						$edi_tyyppi=" edifact911";  # t�� on orderfaili, (huom. space t�rke�)
					}
				}

				if ($ok > 0) {
					#print "pupesoft editilaus.pl v1.1\n--------------------------\n\n";
					#print "Edi-tilaus $file" . "\n";

					$cmd = "cd ".$pupedir.";".$komento. " ".$pupedir."editilaus_in.inc ".$nimi.$edi_tyyppi;
					system($cmd);

					$cmd = "mv -f $nimi $dirri2";
					system($cmd);

					# ulos loopista
					$ok=1;
				}

				# jos ollaan luupattu samaa failia 10 kertaa, ni siirre�t�n se pois...
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

				# jos file ei ollu t�ll� loopilla ok, odotetaan 1 sec
				if ($ok < 1) {
					sleep(1);
				}

			} # end while ok < 1

		} # end if file

	} # end readdir while

	system("rm -f $tmpfile");

	# siivotaan yli 180 p�iv�� vanhat aineistot
	system("find $dirri2 -mtime +180 -delete");

} # end temp if
