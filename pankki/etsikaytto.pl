#=====================================================================
# RiTe-Bank for SQL-Ledger
# Copyright (c) 2002
#
#  Author: Juha Tepponen & Janne Richter
#   Email: oh0jute$kyamk.fi oh0jari@kyamk.fi
#
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
# Ohjelma tutkii onko ESIp- tai PTE-sanomassa   
# uutta avainta. Jos uusi avain löytyy lähete-  
# tään se kaytto.pl ohjelmaan.			
#						
#======================================================================

#!/usr/bin/perl -w

# Haetaan tiedoston nimi argumenteista
$tiedosto= shift @ARGV;
$host = shift @ARGV;
$user = shift @ARGV;
$pass = shift @ARGV;
$database = shift @ARGV;
$tunnus = shift @ARGV;

#Avataan tiedosto ja jaetaan 8 merkin lohkoiksi ja sijoitetaan taulukkoon
#ja poistetaan rivinvaihtomerkit
open(IN,"$tiedosto");   

while($line=<IN>){
        $file .= $line;
}
close IN;
$file=~ s/\n//g;
$i=0;
while ($file =~/.{1,8}/sg) {
        $data[$i++] = $&;
}

# Tutkitaan tehdäänkö avainvaihto
$data[20]=~s/.//;
if($&==1){
	# Avainvaihto tapahtuu
	print "\nAvainvaihto!";
	# Haetaan avain
	$avain=$data[20]; 
	$avain.=$data[21];
	$data[22]=~/./;
	$avain.=$&;
	# Laitetaan se purettavaksi Kaytto.pl ohjelmaan               
	$apu="perl kaytto.pl $avain $dbhost $dbuser $dbpass $dbkanta $tunnus";
	system($apu);

}
else{
	# Ei vaihtoa, joten lopetetaan ohjelman suoritus 
	die "\nEi avainvaihtoa";
}

