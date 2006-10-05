#!/usr/bin/perl

###############################################################################
#
# Myyntitilien sisäänluku by joni 28-01-2003
# 
# Ottaa parametriksi tiedostonimen..
#
# Tiedoston formaatti:
#
# Yhtio 			le 4
# Kustannuspaikka		le 6
# Tilinumero			le 6
# Vero				le 1		K/E
# Summa 			le 15		desimaalierotin , (pilkku)
# Tapahtumapvm 			le 8		PPKKVVVV
#
###############################################################################

use DBI;
use FileHandle;

$dbhost = 'localhost';				#pupesoft hosti
$dbuser = 'pupesoft';				#pupesoft database käyttäjä
$dbpass = 'pupe1';				#pupesoft database salasana
$dbname = 'pupesoft';				#pupesoft databasen nimi

$file = $ARGV[0];

if ($file eq '')
{
	die ("Error! No parameters given!\n");
}
if (!-f $file)
{
	die ("Error! File $file not found!\n");
}

$alv  =0;
$eka  =0;
$rivit=0;

$pupe=DBI->connect("DBI:mysql:database=".$dbname.":host=".$dbhost, $dbuser, $dbpass) || die;

my $fh = new FileHandle ($file);

while ($line = <$fh>)
{
	$yhtio	= lc(substr($line,0,4));
	$kustp	= substr($line,4,6);
	$tili		= substr($line,10,6)+0;
	$lvv		= substr($line,16,1);
	$summa	= substr($line,17,15);
	$summa  =~s/,/./g;
	$summa	= $summa*-1;
	$tapvm	= substr($line,32,8);
	$vv			= substr($tapvm,4,4);
	$kk			= substr($tapvm,2,2);
	$pp			= substr($tapvm,0,2);
	$tapvm	= $vv."-".$kk."-".$pp;
		
	if ($lvv eq "K")
	{
		$vero  = "22";		
		$alvi  = $summa-($summa/(1+($vero/100)));
		$summa = sprintf("%.2f", $summa-$alvi);
		$alv   = $alv+$alvi;		
	}
	else 
	{
		$vero="0";		
	}

	$sumsum = $sumsum + $summa;	
	
	if ($eka==0)
	{
		$eka=1;
		$sql="insert into lasku (yhtio, tapvm, tila, laatija, luontiaika) values ('$yhtio', '$tapvm', 'X', 'Myyntikirja', now())";
		$apu=$pupe->prepare($sql) or die($pupe->errstr."$sql");
		$rv =$apu->execute or die($apu->errstr."$sql");
		$ltunnus=$pupe->{'mysql_insertid'};
	}
	
	if (($summa!=0) && ($tili!=0))
	{
		$sql="insert into tiliointi (yhtio, ltunnus, tilino, kustp, tapvm, summa, vero, selite, lukko, laatija, laadittu) values ('$yhtio', '$ltunnus', '$tili', '$kustp', '$tapvm', '$summa', '$vero', 'Myyntikirja', '', 'Myyntikirja', now())";
		$apu=$pupe->prepare($sql) or die($pupe->errstr."$sql");
		$rv =$apu->execute or die($apu->errstr."$sql");
	}
	
	$rivit++;
}

close($fh);

$sql = "select alv, pyoristys from yhtio where yhtio='$yhtio'";
$apu = $pupe->prepare($sql) or die($pupe->errstr."$sql");
$rv  = $apu->execute or die($apu->errstr."$sql");
@row = $apu->fetchrow_array;
$rv  = $apu->finish();

print "\nMyyntitilit - Yhtio $yhtio\n------------------------\nLaskutuspvm:  $tapvm\nRivimäärä:    $rivit\n";

# Alvit...
$alv=sprintf("%.2f", $alv);
if ($alv != 0)
{
	$sql="insert into tiliointi (yhtio, ltunnus, tilino, kustp, tapvm, summa, vero, selite, lukko, laatija, laadittu) values ('$yhtio', '$ltunnus', '$row[0]', '', '$tapvm', '$alv', '0', 'Myyntikirja', '', 'Myyntikirja', now())";
	$apu=$pupe->prepare($sql) or die($pupe->errstr."$sql");
	$rv =$apu->execute or die($apu->errstr."$sql");
	print "Alvit:        $alv\n";
}

# Pyöristykset...
$sumapu=sprintf("%.2f", ($sumsum+$alv)*-1);
if ($sumapu != 0)
{
	$sql="insert into tiliointi (yhtio, ltunnus, tilino, kustp, tapvm, summa, vero, selite, lukko, laatija, laadittu) values ('$yhtio', '$ltunnus', '$row[1]', '', '$tapvm', '$sumapu', '0', 'Myyntikirja', '', 'Myyntikirja', now())";
	$apu=$pupe->prepare($sql) or die($pupe->errstr."$sql");
	$rv =$apu->execute or die($apu->errstr."$sql");
	print "Pyöristykset: $sumapu\n";
}

$rv=$pupe->disconnect;

print "\n";

$cmd="mv -f $file /home/joni/pupesoft/datain/ok/";
system($cmd);
