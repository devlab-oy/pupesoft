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
# 21.5.2002						
#							
# Ohjelma joka luo SUO-sanoman ja aineiston sis‰lt‰-
# v‰‰n tiedostoon VAR-sanoman. Ohjelma laskee VAR-	
# sanomaan tiivisteen kerta-avaimella.			
#							
#======================================================================
#
# Modified by Pupesoft-team 2006
#
#======================================================================


#!/usr/bin/perl -w

# K‰ytetyt moduulit
use Crypt::DES;
use DBI;

# Haetaan argumenteista tiedoston nimi ja muut
$tiedosto=shift @ARGV;
$host = shift @ARGV;
$user = shift @ARGV;
$pass = shift @ARGV;
$database = shift @ARGV;
$tunnus = shift @ARGV;

$i=0;

# Avataan yhteys tietokantaan
my $dbh = DBI->connect("DBI:mysql:$database:$host",$user,$pass)or die "Can't connect to $database $host: $dbh->errstr\n";

# Avataan tiedosto
#print "\nTiedosto on '$tiedosto'\n";
open(IN,"$tiedosto");

# Luetan tiedostosta rivit
$line=<IN>;
$file.=$line;
#$line=<IN>;
#$file.=$line;


# Poistetaan rivien lopuista v‰lilyˆnnit
while($line=<IN>){
        $apu = chop $line;
	push @a, $apu;  
	while($apu=~/\s/){
        	$apu=chop $line;
        	push @a,$apu;
	}
	$line.=pop @a;    
	$file .= $line;
}
close IN;

# Jaetaan merkit kahdeksan merkin lohkoiksi ja sijoitetaan taulukkoon
while ($file =~/.{1,8}/sg) {
	$data[$i++] = $&;
}
 

#Jos viimeinen lohko j‰‰ vajaaksi t‰ytet‰‰n se bin‰‰ri nollilla
#for($i=length($data[$#data]);$i<8;$i++){
#	$data[$#data] .= "0"
#}


# Haetaan kerta-avain tietokannasta
$sth = $dbh->prepare("SELECT kertaavain FROM yriti WHERE tunnus='$tunnus'");
$rv = $sth->execute();
$kerta = $sth->fetchrow_array;
#print"\nKerta-avain: $kerta\n";


# Alustetaan salaus kerta-avaimella
$kerta = pack("H*","$kerta");
$cipher = new Crypt::DES $kerta;

# Lasketaan tiiviste
$text=$cipher->encrypt($data[16]);
for($i=17;$i<=$#data;$i++){
        $ortext=$text ^ $data[$i];
        $text = $cipher->encrypt($ortext);
}

#Muutetaan salattu teksti Heksoiksi
$tiiviste =unpack("H*","$text");
$tiiviste=~tr/a-z/A-Z/;
#print "\nTiiviste: $tiiviste\n";



# Sijoitetaan SUO sanoman merkit VAR-taulukkoon
$VAR[0]=">>VAR161";
for($i=1;$i<16;$i++){
	$VAR[$i]=$data[$i];
}
$i=0;

# Muutetaan SKH SMH:si
while($data[4]=~/./g){
	$taul[$i++]=$&;
}
$taul[1]="M";
$VAR[4]="";
for($i=0;$i<=$#taul;$i++){
	$VAR[4].=$taul[$i];
}
$i=16;

# Lis‰t‰‰n VAR-sanomaan tiiviste
while($tiiviste=~/.{1,8}/sg){
	$VAR[$i++]=$&;	
}

# Avataan tiedosto
open(OUT,">>$tiedosto");


# Lis‰t‰‰n VAR-sanoma tiedoston loppuun
for($i=0;$i<=$#VAR;$i++){
	print OUT "$VAR[$i]";
#	print  "*$VAR[$i]\n";
#	if($i==9){ print OUT"\n"; }
}
$rc  = $sth->finish;
$dbh->disconnect;

close OUT;

#$apu="perl sala.pl VARa $tiedosto";
#system ($apu);
