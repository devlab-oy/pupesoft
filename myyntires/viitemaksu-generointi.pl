#!/usr/bin/perl
#
# Verkokauppa projekti, viitemaksutiedostojen generoiminen
# ja vastaavien laskujen tietokantaan syöttäminen.
#
# HUOM! tulostaa vain tapahtumatietueita tyyppi 3 (==viitesiirto)
#
# USAGE: luo sata uutta laskua tiedostoon: 
# repeat 100 ./viitemaksu-generointi.pl >> viitemaksu-sample.txt
#
# $Id$

use strict;
use DBI;
use Data::Dumper;
use IO::File;

my $LOG = new IO::File(">>error-log.txt");

my $kuinkamonta=100;
my $eimaksettu=5;
my $reference_prefix = 123;


#------------------


# Käyttö: kutsutaan aliohjelmaa, argumenttina viite ilman tarkistetta
# Paluuarvo -1 merkitsee viallista viitettä

sub laske_viite {
        my ($viite) = @_;
        my $laskuri = 0;
        my $summa = 0;

# Viitteessä ilman tarkistetta saa olla max. 19 numeroa.

        my @kertoimet = (7, 3, 1);
        my $pituus=length($viite);
        my $alkuper=$viite;

        if ($viite =~ /\D/ || $pituus > 19 || $pituus < 1) {
                return -1;
        }

        while ($laskuri < $pituus) {
                $summa += ($viite % 10) * $kertoimet[$laskuri % 3];
                $viite = int($viite / 10);
                $laskuri++;
        }

        my $tarkiste = (10 - ($summa % 10)) % 10;

        return $alkuper.$tarkiste;
}



#------------------

#otetaan yhteys pupen kantaan

my $dbh=DBI->connect(
		"dbi:mysql:pupesoft",
		"root",
		undef,
		{
			RaiseError => 1,
			PrintError => 0,
			AutoCommit => 0,
			ShowErrorStatement => 1
		}) or die "error connecting to DB $DBI::errstr\n";

open(F,">viitemaksu-sample.txt");

while ($kuinkamonta>0) {

#Valitaan laskuttava yritys
	my $sth_y=$dbh->prepare("SELECT yhtio, tilino FROM yriti WHERE yhtio is not null and tilino is not null and tilino <> '000000000' ORDER BY rand() LIMIT 1");
	$sth_y->execute();
	my($company,$account)=($sth_y->fetchrow_array());
	$sth_y->finish;

#Luetaan tilanneen asiakkaan tiedot
	my $sth_a=$dbh->prepare("SELECT yhtio,ytunnus,nimi FROM asiakas where yhtio is not null and ytunnus is not null and ytunnus <> ? ORDER BY rand() LIMIT 1");
	$sth_a->execute("");
	my($yhtio,$ytunnus,$nimi)=($sth_a->fetchrow_array());
	$sth_a->finish;

#Luodaan viite ja summa
	my $reference = $reference_prefix.int(99999*rand());
	$reference = &laske_viite($reference);
	next if ($reference == -1);
	my $sum=sprintf("%.2f",rand()*200);

#luodaan lasku
	my $sth_l=$dbh->prepare("INSERT INTO lasku (yhtio,tila,alatila,ytunnus,summa,tapvm,erpcm,olmapvm,luontiaika,laatija,viite,nimi) VALUES (?,'L','X',?,?,from_days(to_days(now())-100*rand()),from_days(to_days(now())+10*rand()-3),from_days(to_days(now())+10*rand()-3),current_date,'automaattitesti',?,?)");
	$sth_l->execute($yhtio,$ytunnus,$sum,$reference,$nimi);
	$sth_l->finish;
	$dbh->commit;

#ei maksettuja laskuja
	next if (--$eimaksettu > 0);

#aikajutskia
	my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time());
	$year = sprintf("%02d", $year % 100);
	$mon = sprintf("%02d", $mon);
	$mday = sprintf("%02d", $mday);

	my $viitesiirto_now = "$year$mon$mday";

#luodaan viitesiirtorivi
	my $out = " " x 90;

	substr($out,0,1)	= "3";			#tietuetunnus
	substr($out,1,14)	= sprintf('%-14.14s',$account);		#hyvitetty tili (<- company)
	substr($out,15,6)	= $viitesiirto_now;	#kirjauspaiva
	substr($out,21,6)	= $viitesiirto_now;	#maksupaiva
	substr($out,43,20)	= sprintf("%-20.20s",$reference);		#viite
	substr($out,63,12)      = sprintf("%-12.12s",$nimi);
	substr($out,75,1)	= "1";
	substr($out,77,10)	= sprintf("%010.10d",$sum*100); #XXX summa on sentteinä, etunollatäytöllä.

	print $out."\n";
	print F $out."\n";
	$kuinkamonta--;
}

close F;
#lopuksi yhteys katkaistaan.
$dbh->disconnect;

