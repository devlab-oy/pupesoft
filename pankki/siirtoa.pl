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
# 14.5.2002					  
#						  
# Ohjelma joka muodostaa kahdesta siirtoavaimen   
# palasta varsinaisen siirtoavaimen ja tarkistaa  
# sen paikkansa pit‰vyyden! 	  		  
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

# Haetaan argumenteista:
#	Ensimm‰inen siirtoaavain
#	Toinen siirtoavain
#	Tarkiste
#	Siirtoavaimen sukupolvi numero
$p1=shift @ARGV;
$p2=shift @ARGV;
$tarkiste = shift @ARGV;
$suku = shift @ARGV;
$host = shift @ARGV;
$user = shift @ARGV;
$pass = shift @ARGV;
$database = shift @ARGV;
$tunnus = shift @ARGV;

# Tarkistetaan siirtoavaimien pariteetti aliohjelmassa
my $pa1;
my $pa2;
$pa1 = &tarkista($p1);
$pa2 = &tarkista($p2);


# Tarkastetaan pariteetit ja jos niiss‰ on vikaa
# lopetetaan ohjelman suoritus
if($pa1 ne $p1 ||$pa2 ne $p2){
	print "\n$pa1 $p1 $pa2 $p2";
	print "\nPariteetin tarkastus ei mennyt l‰pi. Pariteetti asetettu v‰‰rin!";
	die "\nPariteetin tarkastus ei mennyt l‰pi. Pariteetti asetettu v‰‰rin!";
}

# K‰‰nnet‰‰n avaimet XOR:ia varten ja XOR:taan ne jotta
# saadaan varsinainen siirtoavain
$pa1=pack("H*","$pa1");
$pa2=pack("H*","$pa2");

$pari = $pa1 ^ $pa2;
$pari=unpack("H*","$pari");


# Tarkistetaan siirtoavaimen pariteetit
$pari = &tarkista($pari);

#print "\nSiirtoavain: $pari";



# Salataan siirtoavaimella jono nollia
# jotta n‰hd‰‰n onko siirtoavain oikea
$par = pack("H*","$pari");
my $cipher = new Crypt::DES $par;

$text = pack("H*","0000000000000000");
$text = $cipher->encrypt($text);

$text=unpack("H*","$text");

# Otetaan salauksesta 6 ensimm‰ist‰ tavua ja verrataan niit‰
# Tarkisteeseen ja jos t‰sm‰‰ niin siirtoavain on oikea
$text=~/.{6}/g;
$text=$&;
#print "\nAvaintarkiste: $text\n";

# Avataan yhteys tietokantaan
my $dbh = DBI->connect("DBI:mysql:$database:$host",$user,$pass)or die "Can't connect to $database $host: $dbh->errstr\n"; 

if($text=$tarkiste){
	$sth = $dbh->prepare("SELECT sasukupolvi FROM yriti WHERE tunnus='$tunnus'");
        $rv = $sth->execute();  
        $apu=$sth->fetchrow_array;
	$apu++;
        if($apu>9){
                $apu=1;
        }


	if($suku==$apu){
		#print "\nSiirtoavain on oikea!\n";
		# Lis‰t‰‰n uusi siirtoavain tietokantaan
		$sth = $dbh->prepare("UPDATE yriti SET siirtoavain=? WHERE tunnus = ?");
		$rv = $sth->execute($pari, $tunnus);

		# Tallennetaan uusi siirtoavain sukupolvi numero tietokantaan
		$sth = $dbh->prepare("UPDATE yriti SET sasukupolvi=? WHERE tunnus = ?");
	        $rv = $sth->execute($apu, $tunnus);

	}
	elsif($suku==($apu-1)){
		#print "\nT‰m‰n sukupolven siirtoavain on jo k‰ytˆss‰\n";
		# Lis‰t‰‰n uusi siirtoavain tietokantaan
                $sth = $dbh->prepare("UPDATE yriti SET siirtoavain=? WHERE tunnus = ?");
                $rv = $sth->execute($pari, $tunnus);
        
                # Tallennetaan uusi siirtoavain sukupolvi numero tietokantaan
                $sth = $dbh->prepare("UPDATE yriti SET sasukupolvi=? WHERE tunnus = ?");
                $rv = $sth->execute($apu,$tunnus);
	}
	else{
		print "\nSukupolvi numero $suku, ei t‰sm‰‰ tietokannan kanssa!\n";
	}

}

# Lopetetaan yhteys tietokantaan
$rc  = $sth->finish;
$dbh->disconnect;




#***********************************************#
# Pariteetin asettava aliohjelma                #
# Argumenttina tarkistettava luku	        #
# 						#
# Jakaa luvun kahden tavun mittaisiin lohkoihin #
# ja tarkistaa jokaisen lohkon pariteetin	#
# Lopuksi yhdist‰‰ lohkot takaisin yhdeksi 	#
# luvuksi					#
# Palauttaa tarkistetun luvun			#
#***********************************************#
sub tarkista($){
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
