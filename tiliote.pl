#!/usr/bin/perl

use FileHandle;
use POSIX;

$dirri1		= "/joku/polku/tiliotteet/";    		# mist� haetaan
$dirri2		= "/joku/polku/tiliotteet/done/";		# minne siirret��n
$pupeote	= "/joku/polku/pupesoft/tiliote.php";	# pupesoft-hakemisto
$komento	= "/usr/bin/php"; 						# ajettava komento
$email		= "pupesoft\@devlab.fi"; 				# kenelle meilataan jos on ongelma
$emailfrom	= "pupesoft\@devlab.fi"; 				# mill� osoitteella meili l�hetet��n
$tmpfile	= "/tmp/##tiliote-tmp";  	 			# minne tehd��n lock file

# jos lukkofaili l�ytyy, mutta se on yli 15 minsaa vanha niin dellatan se
if (-f $tmpfile) {
	$mode = (stat($tmpfile))[9];
	$now = time();

	$smtp = Net::SMTP->new('localhost');
	$smtp->mail($emailfrom);
	$smtp->to($email);
	$smtp->data();
	$smtp->datasend("Subject: Tiliotteiden sis��nluku HUOM:\n\n");
	$smtp->datasend("\nTiliotteiden sis��nluvussa saattaa olla ongelma. Lukkotiedosto oli yli 15 minuuttia vanha ja se poistettiin. Tutki asia!");
	$smtp->dataend();
	$smtp->quit;

	if ($now - $mode > 900) {
		system("rm -f $tmpfile");
	}
}

if (!-f $tmpfile) {

	system("touch $tmpfile");
	opendir($hakemisto, $dirri1);

	while ($file = readdir($hakemisto)) {

		$timestamp = strftime("%Y%d%m-%H%M%S", localtime);
		$nimi = $dirri1.$file;
		$nimi_to = $dirri2.$timestamp."_".$file;

		if (-f $nimi) {
			system("$komento $pupeote perl $nimi");
			system("mv -f $nimi $nimi_to");
		}
	}

	system("rm -f $tmpfile");
}