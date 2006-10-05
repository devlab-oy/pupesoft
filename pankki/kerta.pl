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
# 15.5.2002					  
#						  
# Ohjelma kerta-avaimen luomiseen ja salaamiseen  
#						  
# Tietokannasta siemen ja avain,		  
# jos niit‰ ei ole ohjelma generoi luvut          
# satunnaisesti					  
#						  
# Ohjelma luo satunnaisluvun ja luo siit‰ kerta-  
# avaimen. Lis‰ksi ohjelma salaa kerta-avaimen    
# siirtoavaimella ja luo uuden siemenen.	  
#
#======================================================================

#!/usr/bin/perl -w

# K‰ytetyt moduulit
use Crypt::DES;
use Date::Calc qw(:all);
use DBI;

$host = shift @ARGV;
$user = shift @ARGV;
$pass = shift @ARGV;
$database = shift @ARGV;
$tunnus = shift @ARGV;

# Avataan yhteys tietokantaan
my $dbh = DBI->connect("DBI:mysql:$database:$host",$user,$pass)or die "Can't connect to $database $host: $dbh->errstr\n";

#Alustetaan satunnaisluku generaattori
srand (time ^ $$ ^ unpack "%L*", `ps axww | gzip`);  	

# Haetaan siirtoavain tietokannasta
$sth = $dbh->prepare("SELECT siirtoavain FROM yriti WHERE tunnus='$tunnus'");
$rv = $sth->execute();
my $siirtoavain = $sth->fetchrow_array;
print "\nSiirtoavain: $siirtoavain\n";
# Muodostetaan aikaleima aliohjelmalla
$aika = &aikaleima;

# Siemenen ja avaimen haku tietokannasta tai jos ei lˆydy sielt‰ niin 
# Muodostetaan satunnaislukuina
$sth = $dbh->prepare("SELECT siemen FROM yriti WHERE tunnus='$tunnus'");
$rv = $sth->execute();
my $siemen = $sth->fetchrow_array;

$sth = $dbh->prepare("SELECT generointiavain FROM yriti WHERE tunnus='$tunnus'");
$rv = $sth->execute();
my $avain = $sth->fetchrow_array;

print "\nSiemen: $siemen\nAvain: $avain";

if(!$siemen){
	# Siemenen muodostus satunnaislukuina
	for($i=0;$i<16;$i++){
		$apu = int(rand 16);
		if($apu<10){ $siemen.=$apu;}
		elsif($apu==10){$siemen.="a";}
		elsif($apu==11){$siemen.="b";}
		elsif($apu==12){$siemen.="c";}
       		elsif($apu==13){$siemen.="d";} 
		elsif($apu==14){$siemen.="e";}
	        elsif($apu==15){$siemen.="f";}
	}
}
if(!$avain){
	# Avaimen muodostus satunnaislukuina
	 for($i=0;$i<16;$i++){
                $apu = int(rand 16);
                if($apu==10){$avain.="a";}
                elsif($apu==11){$avain.="b";}
                elsif($apu==12){$avain.="c";}
                elsif($apu==13){$avain.="d";}
                elsif($apu==14){$avain.="e";}
                elsif($apu==15){$avain.="f";}
                else{
                $avain.=$apu;
                }
		$sth = $dbh->prepare("UPDATE yriti SET generointiavain=? WHERE tunnus='$tunnus'");
		$rv = $sth->execute($avain);

        }
	
}
print "\nSiemen: $siemen\nAvain: $avain";

# Muutetaan luvut oikeaan muotoon ja alustetaan salaus
$avain = pack("H*","$avain");
my $cipher = new Crypt::DES $avain;
$aika = pack("H*","$aika");

# Muodostetaan satunnaisluku
# Salaamalla aikaleima avaimella
$vali=$cipher->encrypt($aika);
$vali = unpack("H*","$vali");

# XOR:taan v‰litulos ja siemen
$vali = $vali ^ $siemen;
$vali = pack("H*","$vali");

# Salataan XOR avaimella 
$random = $cipher->encrypt($vali);
$random = unpack("H*","$random");

# Tarkistetaan pariteetti ja saadaan kerta-avain
$kerta = &pariteetti($random);
#print"\nKerta-avain: $kerta";

# Tallennetaan kerta-avain tietokantaan
$sth = $dbh->prepare("UPDATE yriti SET kertaavain=? WHERE tunnus='$tunnus'");
$rv = $sth->execute($kerta);

# Muodostetaan uusi siemen 
$siemen=$siemen ^ $random;
$siemen = pack("H*","$siemen");
$siemen = $cipher->encrypt($siemen);
$siemen = unpack("H*","$siemen");

# Tallennetaan uusi siemen tietokantaan
$sth = $dbh->prepare("UPDATE yriti SET siemen=? WHERE tunnus='$tunnus'");
$rv = $sth->execute($siemen);

# Salataan kerta-avain 
# Muutetaan luvut oikeaan muotoon
$siirtoavain = pack("H*","$siirtoavain");
$kerta = pack("H*","$kerta");

# Alustetaan salaus
$cipher = new Crypt::DES $siirtoavain;

# Salataan kerta-avain siirtoavaimella
$sal = $cipher->encrypt($kerta);
$sal = unpack("H*","$sal");
print "\nSalattu kerta-avain : $sal\n";

# Tallenneteaan salattu kerta-avain tietokantaan
$sth = $dbh->prepare("UPDATE yriti SET salattukerta=? WHERE tunnus='$tunnus'");
$rv = $sth->execute($sal);

$rc  = $sth->finish;
$dbh->disconnect;

#****************************************#
# Aliohjelma aikaleiman luomiseen	 #
# 					 #
# Luo aikaleiman nykyisen p‰iv‰m‰‰r‰n ja #
# t‰m‰n hetkisen kellonajan mukaaan	 #
# Lis‰‰ loppuun nollia, jotta saadaan    #
# 16 merkki‰ pitk‰ palaute		 #
# Palauttaa aikaleiman			 #
#					 #
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
	$leima.="0000";

	return $leima;

}




#***********************************************#
# Pariteetin asettava aliohjelma                #
# Argumenttina tarkistettava luku               #
#                                               #
# Jakaa luvun kahden tavun mittaisiin lohkoihin #
# ja tarkistaa jokaisen lohkon pariteetin       #
# Lopuksi yhdist‰‰ lohkot takaisin yhdeksi      #
# luvuksi                                       #
# Palauttaa tarkistetun luvun			#
#						#
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
                        



