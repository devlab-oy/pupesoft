#!/usr/bin/perl

use FileHandle;
use Net::SMTP;

$dirri1    = "/home/elma/elma/edi/autolink_orders/";    # mistä haetaan
$dirri2    = "/home/elma/elma/edi/autolink_orders/done/"; # minne siirretään
$pupedir   = "/var/www/html/pupesoft/tilauskasittely/"; # pupesoftin tilauskäsittely hakemisto
$komento   = "/usr/bin/php"; # ajettava komento
$email     = "atk\@arwidson.fi"; # kenelle meilataan jos on ongelma
$emailfrom = "atk\@arwidson.fi"; # millä osoitteella meili lähetetään
$tmpfile   = "/tmp/##edi-tmp";   # minne tehdään lock file

# jos lukkofaili löytyy, mutta se on yli 15 minsaa vanha niin dellatan se
if (-f $tmpfile) {
	$mode = (stat($tmpfile))[9];
	$now = time();

	$smtp = Net::SMTP->new('localhost');
	$smtp->mail($emailfrom);
	$smtp->to($email);
	$smtp->data();
	$smtp->datasend("Subject: Editilaus HUOM!\n\n");
	$smtp->datasend("\nEditilausten sisäänluvussa saattaa olla ongelma. Lukkotiedosto oli yli 15 minuuttia vanha ja se poistettiin. Tutki asia!");
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

			# loopataan tätä failia kunnes ok
			while ($ok < 1) {

				if ($vnimi==$nimi) {
					$laskuri++;
				}
				else {
					$laskuri=0;
				}

				$vnimi=$nimi;

				open(faili, $nimi) or die("Failin $nimi avaus epäonnistui.");
				@rivit = <faili>;
				$ok=0;

				foreach $rivi (@rivit) {											
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

				close(faili);

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

				# jos ollaan luupattu samaa failia 10 kertaa, ni siirreätän se pois...
				if ($laskuri > 10) {
					$smtp = Net::SMTP->new('localhost');
					$smtp->mail($emailfrom);
					$smtp->to($email);
					$smtp->data();
					$smtp->datasend("Subject: Editilaus ERROR!\n\n");
					$smtp->datasend("\nEditilaus: ".$nimi." taitaa olla viallinen. Siirrettiin faili $dirri2 hakemistoon. Tutki asia!");
					$smtp->dataend();
					$smtp->quit;
					
					$cmd = "mv -f $nimi $dirri2";
					system($cmd);

					# ulos loopista
					$ok=1;
				}

				# jos file ei ollu tällä loopilla ok, odotetaan 1 sec
				if ($ok < 1) {
					sleep(1);
				}

			} # end while ok < 1

		} # end if file

	} # end readdir while

	system("rm -f $tmpfile");
	
	# siivotaan yli 180 päivää vanhat aineistot
	system("find $dirri2 -mtime +180 -delete");

} # end temp if