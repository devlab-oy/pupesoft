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
#					
# Ohjelma joka tekee siirtopyynnˆn.	
# Argumenttina annetaan aineistotyyppi,	
# tiedostonimi ja mahdollinen salasana.	
#					
#======================================================================

#!/usr/bin/perl -w

use Date::Calc qw(:all);

$tyyppi = shift @ARGV;
$tiedosto = shift @ARGV;
$palvelu = shift @ARGV;
$sala = shift @ARGV;
$paivays = shift @ARGV;


open(OUT,">$tiedosto");

$apu="SIIRTOPYYNTO";
$apu.=" " x (17-length($apu) );

print OUT "$apu";

print OUT" "x10;

# Aineistotyypin lis‰ys
$apu=$tyyppi;
$apu.=" " x (10-length($apu) );

print OUT "$apu";

# T‰h‰n tulee palvelutunnus eli tilinumero jos sit‰ tarvitaan
if($palvelu eq"0"){
	print OUT " " x 18;
}
else{
	$palvelu.=" " x (18-length($palvelu));
	print OUT $palvelu;
}

# Salasanan lis‰ys
$apu=$sala;
$apu.=" " x (10-length($apu) );

print OUT "$apu";

if($paivays eq "0"){
	print OUT &aikaleima;
}
else{
	print OUT $paivays;
}

print OUT " 9979 ";

print OUT "999";








#****************************************#
# Aliohjelma aikaleiman luomiseen        #
#                                        #
# Luo aikaleiman nykyisen p‰iv‰m‰‰r‰n    #
# mukaaan. 			         #               
# Palauttaa aikaleiman                   #
#                                        #
#****************************************#
sub aikaleima(){
        # Haetaan p‰iv‰m‰‰r‰ taulukkoon
        @p=Today();
    
                        
        # Tehd‰‰n vuodesta kaksinumeroinen esitys
        $p[0]=$p[0]-2000;
                       

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
                        
        return $leima;
         
}
