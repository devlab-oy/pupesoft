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
# 20.5.2002                                           
#                                                     
# Ohjelma k‰yttˆavaimen luomiseen                     
#                                                     
# Ohjelma purkaa siirtoavaimen avulla pankin antaman   
# salatun k‰yttˆavaimen.			      
# K‰yttˆavain talletetaan tietokantaan ja sukupolvi   
# numero p‰ivitet‰‰n.			              
#                                                     
#======================================================================

#!/usr/bin/perl -w

# K‰ytetyt moduulit
use Crypt::DES;
use DBI;

$salattu = shift @ARGV;
print "\nSalattu: $salattu";

$host = shift @ARGV;
$user = shift @ARGV;
$pass = shift @ARGV;
$database = shift @ARGV;
$tunnus = shift @ARGV;

# Avataan yhteys tietokantaan
my $dbh = DBI->connect("DBI:mysql:$database:$host",$user,$pass)or die "Can't connect to $database $host: $dbh->errstr\n"; 

# Haetaan siirtoavain tietokannasta
$sth = $dbh->prepare("SELECT siirtoavain FROM yriti WHERE tunnus='$tunnus'");
$rv = $sth->execute();
my $siirto = $sth->fetchrow_array;
my $text;

# Alustetaan salaus siirtoavaimella
$siirto=pack("H*","$siirto");
my $cipher = new DES $siirto;

# Puretaan vakio muuttuja siirtoavaimella
$salattu=pack("H*","$salattu");
$text=$cipher->decrypt($salattu);

$text=unpack("H*","$text"); 

# Tarkistetaan pariteetti ja tulostetaan k‰yttˆavain
$kaytto = &pariteetti($text);
$kaytto=~tr/a-z/A-Z/;
print"\nK‰yttˆavain: $kaytto\n";

# Lis‰t‰‰n k‰yttˆavain tietokantaan 
$sth = $dbh->prepare("UPDATE yriti SET k‰yttˆavain=? WHERE tunnus='$tunnus'"); 
$rv =$sth->execute($kaytto); 


# Haetaan nykyinen sukupolvinumero tietokannasta ja kasvatetaan sit‰ yhdell‰
$sth = $dbh->prepare("SELECT kasukupolvi FROM yriti WHERE tunnus='$tunnus'");
$rv = $sth->execute();
$apu=$sth->fetchrow_array;
$apu++;

# Jos sukupolvi on suurempi kuin 9 siit‰ tuleekin 1
if($apu>9){
	$apu=1;
}
# lis‰t‰‰n uusi sukupolvi numero tietokantaan
$sth = $dbh->prepare("UPDATE yriti SET kasukupolvi=? WHERE tunnus='$tunnus'");
$rv = $sth->execute($apu);

                


# Lopetetaan yhteys tietokantaan
$rc  = $sth->finish;
$dbh->disconnect;



#***********************************************#
# Pariteetin asettava aliohjelma                #
# Argumenttina tarkistettava luku               #
#                                               #
# Jakaa luvun kahden tavun mittaisiin lohkoihin #
# ja tarkistaa jokaisen lohkon pariteetin       #
# Lopuksi yhdist‰‰ lohkot takaisin yhdeksi      #
# luvuksi                                       #
# Palauttaa tarkistetun luvun                   #
#                                               #
#***********************************************#
sub pariteetti($){
        $k=0;
        $jono = shift @_;

        # Jaetaan luku kahden tavun lohkoihin ja sijoitetaan lohkot
        # taulukkoon
        while($jono=~/.{2}/g){
                $data[$k]=$&;
                $k++;
        }
        $jono=""; 
        
        # Tutkitaan lohko kerrallaan lohkon pariteetit
        for($j=0;$j<=$#data;$j++){
                $i=0;
                $apu=$data[$j];
                chomp $apu;   
                
                # Muutetaan lohko oikeaan muotoon
                $apu=pack("H*","$apu");
                $apu=unpack("B*","$apu");
        
                # Lasketaan ykkˆsten m‰‰r‰
                while($apu=~/1/g){
                        $i++;
                }
                
                 # Laitetaan merkit taulukkoon     
                $w=0;
                while($apu=~/\d/g){
                        $viiminen[$w++]=$&;
                }

                # Jos ykkˆsi‰ parillinen m‰‰r‰
                # muutetaan viimeist‰ merkki‰
                if(!($i % 2)){

                        # Jos viimeinen on ykkˆnen niin muutetaan se
                        # nollaksi
                        if($viiminen[$#viiminen]==1){
                                $viiminen[$#viiminen]=0;
                        }
                        # Jos viimeinen on nolla muutetaan se ykkˆseksi
                        else{
                                $viiminen[$#viiminen]=1;

                        }
                }
                $apu="";
                for($i=0;$i<$#viiminen+1;$i++){
                        $apu.=$viiminen[$i];
                }

                        
                # Muutetaan lohko oikeaan muotoon
                $apu=pack("B*","$apu");  
                $apu=unpack("H*","$apu");
                $data[$j]=$apu;
        }

        # Yhdistet‰‰n lohkot takaisin luvuksi
        for($i=0;$i<=$#data;$i++){
                $jono.=$data[$i];
        }               
                
                
        # Palautetaan luku
        return $jono;
}



