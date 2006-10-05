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
# Ohjelma tekee SUO-tai ESI-sanomia, riippuen 	
# argumentissa annetusta arvosta. Ohjelma hakee	
# tiedot tietokannasta. Argumentissa annetaan 	
# tiedoston nimi johon sanoma talletetaan.	
#						
#======================================================================
#
# Modified by Pupesoft-team 2006
#
#======================================================================

#!/usr/bin/perl -w

use DBI;
use Date::Calc qw(:all);


# Haetaan argumenteista luotavan tiedoston nimi ja tyyppi ja tietokantajutut;
$tiedosto = shift @ARGV;
$tyyppi = shift @ARGV;
chomp $tyyppi;
$host = shift @ARGV;
$user = shift @ARGV;
$pass = shift @ARGV;
$database = shift @ARGV;
$tunnus = shift @ARGV;

# Avataan yhteys tietokantaan
my $dbh = DBI->connect("DBI:mysql:$database:$host",$user,$pass)or die "Can't connect to $database $host: $dbh->errstr\n"; 

# Sanoma tunnus
$file=">>";
$file .= $tyyppi;

# Sanoma pituus
if($tyyppi eq "SUO"){
	$file.="128";
}
else{
	$file.="161";
}


# Versio
$file.="120";

# Onnistumiskoodi + ilmoituskoodi
$file.=" 0000";

# Ohjelmisto
# Nimi
$apu = "PUPESOFT    "; #Pituus 12

# Versio
$apu.= "0099"; #Pituus 4

$file.=$apu;

# Turvamenetelm‰
if($tyyppi eq "SUO"){
	$file.="SKH";
}
else{
	$file.="SMH";
}

# Vastaanottaja
$sth = $dbh->prepare("SELECT pankki FROM yriti WHERE tunnus='$tunnus'");
$rv = $sth->execute();
$apu = $sth->fetchrow_array;

# Jos pankki on antanut tarkenteen lis‰t‰‰n seuraavat rivit
#$sth = $dbh->prepare("SELECT pankkitarkenne FROM tunnukset");
#$rv = $sth->execute();
#$apu.= $sth->fetchrow_array;

for($i=length($apu);$i<25;$i++){
	$apu.=" ";
}
$file.=$apu;


# L‰hett‰j‰
$sth = $dbh->prepare("SELECT asiakas FROM yriti WHERE tunnus='$tunnus'");
$rv = $sth->execute();
$apu = $sth->fetchrow_array;

# Jos pankki on antanut tarkenteen lis‰t‰‰n seuraavat rivit
#$sth = $dbh->prepare("SELECT asiakastarkenne FROM tunnukset");
#$rv = $sth->execute();
#$apu.= $sth->fetchrow_array;

for($i=length($apu);$i<25;$i++){
        $apu.=" ";
}
$file.=$apu;

# Siirto-ja k‰yttˆavainten sukupolvi numerot
$sth = $dbh->prepare("SELECT sasukupolvi FROM yriti WHERE tunnus='$tunnus'");
$rv = $sth->execute();
$apu = $sth->fetchrow_array;

$sth = $dbh->prepare("SELECT kasukupolvi FROM yriti WHERE tunnus='$tunnus'");
$rv = $sth->execute();
$apu .= $sth->fetchrow_array;

$file.=$apu;



# Aikaleima ja leima numero
$file.=&aikaleima();
$file.="000";


# Suojausalue
if($tyyppi eq "ESI"){
	$file.=" ";
}
else{
	$file.="S";
}
$file.=" " x 9;


# Salattu kerta-avain
if($tyyppi eq "ESI"){
        $file.=" " x 16;
}
else{
	$sth = $dbh->prepare("SELECT salattukerta FROM yriti WHERE tunnus='$tunnus'");
	$rv = $sth->execute();
	$apu = $sth->fetchrow_array;
	$apu=~tr/a-z/A-Z/;
	$file.=$apu;        
}

#Tiivisteen paikka
if($tyyppi eq "ESI"){
	$file.=" " x 16;
}

open(OUT,">$tiedosto");
print OUT "$file\n";
close OUT;

#****************************************#
# Aliohjelma aikaleiman luomiseen        #
#                                        #
# Luo aikaleiman nykyisen p‰iv‰m‰‰r‰n ja #
# t‰m‰n hetkisen kellonajan mukaaan      #
# Lis‰‰ loppuun nollia, jotta saadaan    #
# 16 merkki‰ pitk‰ palaute               #
# Palauttaa aikaleiman                   #
#                                        #
#****************************************#
sub aikaleima(){
        # Haetaan p‰iv‰m‰‰r‰ ja kellonaika omiin taulukoihin
        @p=Today();
        @a=Now();
                        
        # Tehd‰‰n vuodesta kaksinumeroinen esitys
        $p[0]=$p[0]-2000;
                        
        # Tehd‰‰n jokaisesta aika-taulukon kohdasta kaksinumeroinen
        # ja sijoitetaan aika muuttujaan
        for($i=0;$i<@a;$i++){
                if($a[$i]<10){
                        $aika.=0;
                        $aika.=$a[$i];
               }
                else{
                        $aika.=$a[$i];
                }
        }

        # Tehd‰‰n jokaisesta p‰iv‰m‰‰r‰-taulukon kohdasta kaksinumeroinen
        # ja sijoitetaan p‰iv‰m‰‰r‰ muuttujaan
        for($i=0;$i<@p;$i++){
                if($p[$i]<10){
                        $paiva.=0;
                        $paiva.=$p[$i];
                }
                else{   
                        $paiva.=$p[$i];
                }
        }
                
        # Tehd‰‰n lopullinen aikaleima ja palautetaan se
        $leima.=$paiva;
        $leima.=$aika;
	                
        return $leima;
         
}
