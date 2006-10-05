#===================================================================== 
# RiTe-Bank for SQL-Ledger 
# Copyright (c) 2002 
#  
#  Author: Juha Tepponen & Janne Richter 
#   Email: oh0jute$kyamk.fi oh0jari@kyamk.fi 
#   
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or   
# (at your option) any later version.
# 
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of 
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the  
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.  
#======================================================================
#
# 13.5.2002                                       
#                                                 
# Ohjelma joka lukee tiedostosta syötteen ja      
# Salaa sen käyttäen DES-salausta ja MAC-tarkis-   
# tuskenttämenetelmää!                            
#                                                 
#======================================================================
#
# Modified by Pupesoft-team 2006
#
#======================================================================

#!/usr/bin/perl -w

#Käytetty moduuli
use Crypt::DES;
use DBI;

#Muuttujamäärittelyt;
my @data;
my $i=0;
my $arg;
my $key;
my $tiedosto;
my $cipher;
my $file;
my $text;
my $apu;
my $tarkiste;
my $line;
my $ortext;


#Luetaan argumenteista:
#     minkä tiedoston tarkiste on kyseessä
#     tiedoston nimi
$arg = shift @ARGV;
$tiedosto = shift @ARGV;
$host = shift @ARGV;
$user = shift @ARGV;
$pass = shift @ARGV;
$database = shift @ARGV;
$tunnus = shift @ARGV;

# Avataan yhteys tietokantaan
my $dbh = DBI->connect("DBI:mysql:$database:$host",$user,$pass)or die "Can't connect to $database $host: $dbh->errstr\n"; 

# Haetaan käyttöavain tietokannasta
$sth = $dbh->prepare("SELECT kayttoavain FROM yriti WHERE tunnus='$tunnus'");
$rv = $sth->execute();
my $key = $sth->fetchrow_array;
#print "Avain '$key'\n";
#Alustetaan Kryptaus käyttöavaimella  
$key = pack("H*","$key");
$cipher = new Crypt::DES $key;


#Avataan tiedosto ja jaetaan 8 merkin lohkoiksi ja sijoitetaan taulukkoon
#ja poistetaan rivinvaihto merkit
open(IN,"$tiedosto");

while($line=<IN>){
	$file .= $line;
} 
close IN;

if($arg eq "VARa"){
	$file=~/>>VAR/;
	$alku=$`;
	$file=$&;
	$file.=$';
}
$file=~ s/\n//g;

while ($file =~/.{1,8}/sg) {
        $data[$i++] = $&;
}

#Jos viimeinen lohko jää vajaaksi täytetään se binääri nollilla
$data[$#data] .= "\0" x(8-length($data[$#data]));

        
#Salataan lohko, XOR:taan salattu lohko seuraavan lohkon kanssa ja 
#salataan se, ja XOR:taan seuraavan lohkon kanssa jne.
#print "\n'$data[0]'";
#print "\n'".unpack("H*",$data[0])."'";
$text=$data[0];
$text=$cipher->encrypt($text);

for($i=1;$i<=$#data;$i++){
#	print "\n'$data[$i]'";
#	print "\n'".unpack("H*",$data[$i])."'";
	$ortext=$text ^ $data[$i];
	$text = $cipher->encrypt($ortext);
}

# Muutetaan salattu teksti Heksoiksi
$tark =unpack("H*","$text");

$tark=~tr/a-z/A-Z/;

# Tulostellaan arvot
#print "\nSalaus: $tark";
# Tehdään tarvittavat temput tietyille tiedostotyypeille
if($arg eq "ESIa"||$arg eq "VARa"){
	open(OUT,">$tiedosto");
	if($arg eq "VARa"){
		print OUT "$alku";
	}
	for($i=0;$i<=$#data;$i++){
		print OUT "$data[$i]";
	}
	print OUT "$tark";
	close OUT;
	#open(IN,"$tiedosto.");

}	

if($arg eq "ESIp"||$arg eq "PTE"){
	$tarkiste = $data[18];
	$tarkiste.=$data[19];
	print "\nTarkiste: $tarkiste\n";
	if($tark eq $tarkiste){
		print "\nTarkisteet täsmäävät\n";
	}
	else{
		print "\nTarkisteet eivät täsmää!\n";
	}
}
