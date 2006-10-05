#!/usr/bin/perl
#
# Verkokauppa projektin koodia.. kovasti muokattuna.
# 
# Luetaan viitemaksuja...
# Automaattikohdistetaan viitemaksuja...
# Tehd‰‰n kohdistamattomista laskuja...
#

use DBI;
use Data::Dumper;
use IO::File;

# otetaan database connectioni..

$dirri1 = "/home/viitteet/";			# mist‰ dirikasta haetaan faileja
$dirri2 = "/home/viitteet/done/";		# minne siirret‰‰n kun k‰sitelty
$dbhost = '';		#pupesoft hosti
$dbuser = '';		#pupesoft database k‰ytt‰j‰
$dbpass = '';			#pupesoft database salasana
$dbname = 'pupesoft';		#pupesoft databasen nimi

opendir($hakemisto, $dirri1);

while ($file = readdir($hakemisto))
{
	$nimi = $dirri1.$file;

	if (-f $nimi)
	{
		my $dbh = DBI->connect("DBI:mysql:database=$dbname;host=$dbhost", $dbuser, $dbpass)  or die("Tietokantayhteys ep‰onnistui: database=$dbname, host=$dbhost, user=$dbuser, passwd=$dbpass\n\n");
		
		
		################################
		#
		# luetaan viitemaksut sis‰‰n...
		#
		################################
		
		print "Luetaan viitemaksuja...\n";
		
	
		open(F,"<$nimi") or die "tiedoston $file avaaminen ep‰onnistui $!";
		
		my $readcnt;
		my $insertcnt=0;
		my $errcnt;
		
		my $sth_yriti=$dbh->prepare("SELECT yhtio, oletus_rahatili FROM yriti WHERE tilino = ?");
		my $sth_yhtio=$dbh->prepare("SELECT myyntisaamiset, myynti FROM yhtio WHERE yhtio = ?");
		my $sth_last=$dbh->prepare("SELECT last_insert_id()");
		
		my $sth_lasku=$dbh->prepare("INSERT into lasku set yhtio = ?, tapvm = now(), tila = 'X', laatija = 'viitesiirrot', luontiaika = now()");
		my $sth_last_id=$dbh->prepare("SELECT last_insert_id()");
		my $sth_tilioi=$dbh->prepare("INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, ltunnus, tilino, summa, selite) values (?,'viitesiirrot',now(),?,?,?,?,?)");
		
		my $sth_aineisto=$dbh->prepare("SELECT max(aineisto)+1 FROM tiliotedata");
		my $sth_tiliotedata=$dbh->prepare("INSERT into tiliotedata (yhtio, aineisto, tilino, alku, loppu, tyyppi, tiliointitunnus, tieto, kasitelty) values (?, ?, ?, ?, ?, ?, ?, ?, now())");
		
		my $sth_lukko=$dbh->prepare("LOCK TABLE tiliotedata WRITE, tiliointi WRITE, lasku WRITE, yriti READ, yhtio READ");
		my $sth_lukkoauki=$dbh->prepare("UNLOCK TABLES");
		
		
		$sth_lukko->execute();
		$sth_lukko->finish;
		
		$sth_aineisto->execute();
		my (@aineisto)=$sth_aineisto->fetchrow_array();
		$sth_aineisto->finish;
		
		if (!@aineisto) {
			die "En saanut aineistonrota luoduksi!";
		}
		
		$tamaaineisto = $aineisto[0];
		
		while (<F>) {
			#tietuetunnus on ensimm‰inen merkki
			# 0 -> er‰tietue
			# 3 -> viitesiirto
			# 5 -> suoraveloitus
			# 9 -> summatietue
		
			my %row;
			my $type=substr($_,0,1);
			if ($type eq "0") {
				print "Otsikko ok!\n";
				print "Aineston luontipv => ".substr($_,1,6)."\n";
				print "Aineston luontiaika => ".substr($_,7,4)."\n";
				#print "Rahalaitostunnus => ".substr($_,11,2)."\n";
				#print "Laskuttajan palvelutunnus => ".substr($_,13,9)."\n";
				#print "Rahayksikˆn koodi => ".substr($_,22,1)."\n";
				#print "Varalla => ".substr($_,23,67)."\n";
				$otsikko=$_;
			} elsif ($type eq "3") {
				$readcnt++;
		
				#viitesiirto
				#print "Oikaisutunnus => ".substr($_,87,1)."\n";
				#print "V‰litystapa => ".substr($_,88,1)."\n";
				#print "Palautekoodi => ".substr($_,89,1)."\n";
		
				$account=substr($_,1,14);
				my $payer_name=substr($_,63,12);
				#print "Maksajan nimen l‰hde => ".substr($_,76,1)."\n";
		
				my $payer_name=substr($_,63,12);
				#print "Arkistointitunnus => ".substr($_,27,16)."\n";
		
				my $sum=substr($_,77,10);
				my $sum=$sum/100; #XXX summa on senttein‰, etunollat‰ytˆll‰.
				my $currency_code=substr($_,75,1);
				if ($currency_code != 1) {
					warn "valuutta ei euroissa! not implemented.";
					$errcnt++;
					next;
				}
		
				my $entry_date_raw=substr($_,15,6);
				$entry_date_raw =~ /^(..)(..)(..)$/;
				$entry_date="20$1-$2-$3";
		
				my $payment_date_raw=substr($_,21,6);
				$payment_date_raw =~ /^(..)(..)(..)$/;
				$payment_date="20$1-$2-$3";
		
				$sth_yriti->execute($account);
				my (@company)=$sth_yriti->fetchrow_array();
				$sth_yriti->finish;
		
				if (!@company) {
					warn "tilille $account ei lˆytynyt yrityst‰.";
					$errcnt++;
					next;
				}
				
				$yhtio=$company[0];
		
				$sth_yhtio->execute($yhtio);
				my (@yhtio)=$sth_yhtio->fetchrow_array();
				$sth_yhtio->finish;
		
				if (!@yhtio) {
					warn "Yhtiˆlle $yhtio ei lˆytynyt yrityst‰.";
					$errcnt++;
					next;
				}
		
		
				$sth_lasku->execute($yhtio);
				$sth_lasku->finish;
				$sth_last_id->execute();
				my ($last_id)=scalar($sth_last_id->fetchrow_array());
				$sth_last_id->finish;
				#warn "luotiin lasku yhtiˆlle $yhtio";
				
				my $rahatili=int($company[1]);
				my $myynti = int($yhtio[0]);
				my $selite = $payer_name . " maksoi viitteell‰ $reference";
				$sth_tilioi->execute($yhtio, $payment_date, $last_id, $rahatili, $sum, $selite);
				$sth_tilioi->finish;
				#warn "luotiin tiliointi yhtiˆlle $yhtio";
		
				$sum = $sum * -1;
				$sth_tilioi->execute($yhtio, $payment_date, $last_id, $myynti, $sum, $selite);
				$sth_tilioi->finish;
				$sth_last_id->execute();
				my ($tilioi_id)=scalar($sth_last_id->fetchrow_array());
				$sth_last_id->finish;
				
				#warn "luotiin tiliointi yhtiˆlle $yhtio";
				if ($otsikko ne "") {
					$sth_tiliotedata->execute($yhtio, $tamaaineisto, $account, $payment_date, $payment_date, 3, 0, $otsikko);
					$sth_tiliotedata->finish;
					$otsikko = "";
				}		
				$sth_tiliotedata->execute($yhtio, $tamaaineisto, $account, $payment_date, $payment_date, 3, $tilioi_id, $_);
				$sth_tiliotedata->finish;
		
			} elsif ($type eq "9") {
				print "Yhteenveto ok!\n";
				print "Tapahtumien kpl => ".substr($_,1,6)."\n";
				print "Tapahtumien m‰‰r‰ => ".substr($_,7,11)."\n";
				#print "Oikaisutapahtumien kpl => ".substr($_,18,6)."\n";
				#print "Oikaisutapahtumien m‰‰r‰ => ".substr($_,24,11)."\n";
				#print "Varalla => ".substr($_,35,5)."\n";
				#print "done, insert/read/error: $insertcnt/$readcnt/$errcnt\n";
				$sth_tiliotedata->execute($yhtio, $tamaaineisto, $account, $payment_date, $payment_date, 3, 0, $_);
				$sth_tiliotedata->finish;
			} else {
				warn "Tuntematon tietuetunnus $type";
				$errcnt++;
				next;
			}
			#print "\n\n";
		
		}

		print("Done. $nimi\n");
		$cmd = "mv -f $nimi $dirri2";
		system($cmd);
	}
}
