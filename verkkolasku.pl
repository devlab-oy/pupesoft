#!/usr/bin/perl

use XML::XPath;
use XML::XPath::XMLParser;
use FileHandle;
use Net::SMTP;
use HTTP::Request;
use LWP::UserAgent;

print "\npupesoft verkkolasku.pl v2.2\n----------------------------\n\n";

$dirri			= "/home/jarmo/einvoice/"; 							# minne verkkolaskut tulee
$okdir			= "/home/jarmo/einvoice/ok/";						# minne onnistuneet siirretään
$errordir		= "/home/jarmo/einvoice/error/";					# minne epäonnistuneet siirretään
$emailserver	= "localhost";										# meiliserverin osote
$email			= "admin@domain.com";								# kenelle lähetetään meilit
$emailfrom		= "verkkolasku@domain.com";							# kuka lähettää meilin
$urlalku		= "http://localhost/pupesoft/verkkolasku-in.php?";	# urli missä on php jota kutsutaa

opendir($hakemisto, $dirri);

while ($file = readdir($hakemisto)) {

	$nimi = $dirri.$file;
	
	if (-f $nimi) {

		$ok=0;
		$errormsg="";
		$url = $urlalku;
		############ käsittele faili begin ############
		
		print "\n\nVerkkolasku $file" . "\n";
 		
		my $parser = XML::XPath::XMLParser->new(filename => $nimi);
		my $root_node = $parser->parse;

		if (!$root_node) {
			$errormsg = "No Root Node!?? Damn!";
			$ok=1;
		}

		if ($ok==0) {
			my $xp = XML::XPath->new(context => $root_node);
			my $msgcontext = $xp->find('Message')->get_node(1);

			if (!$msgcontext) {
				$errormsg = "No <Message> element found! Can't be!";
				$ok=1;
			}

			if ($ok==0) {
				
				my($dd)={
				    'yhtio'=>'Group2/NAD[@e3035="IV"]/@eC082.3039',
					'verkkotunnus_vas' => 'Group2/NAD[@e3035="MR"]/@eC082.3039',
				    'laskun_tyyppi'=>'BGM/@eC002.1001',
				    'laskun_numero'=>'BGM/@e1004',
				    'laskun_ebid' => 'Group1/RFF[@eC506.1153="ZEB"]/@eC506.1154',
				    'laskun_paiva'=>'DTM[@eC507.2005="3"]/@eC507.2380',
				    'laskun_erapaiva'=>'Group8[PAT/@e4279="1"]/DTM[@eC507.2005="13"]/@eC507.2380',
				    'laskuttajan_ovt' => 'Group2/NAD[@e3035="II"]/@eC082.3039',
				    'laskuttajan_nimi' => 'Group2/NAD[@e3035="II"]/@eC080.3036.1',
				    'laskun_pankkiviite' => 'Group1/RFF[@eC506.1153="PQ"]/@eC506.1154',
				    'laskun_summa_eur' =>'Group48/MOA[@eC516.5025="9" and @eC516.6345="EUR"]/@eC516.5004',
				    'laskun_asiakastunnus' => 'Group2[NAD/@e3035="IV"]/Group3/RFF/@eC506.1154'
				    };

				foreach $ddkey (keys %{$dd}) { 
					
				    $nodeset = $xp->find($dd->{$ddkey},$msgcontext);

				    foreach my $node ($nodeset->get_nodelist) {

						$arvo=$node->getData();
						$url = $url.$ddkey."=".$arvo."&";

						if ($ddkey eq "laskuttajan_nimi") {
							$laskuttaja=$arvo;
						}
						
						if ($ddkey eq "laskun_numero") {
							$laskunro=$arvo;
						}
				    }
				}

				print "Laskun perustiedot parseroitu!\n";

				my $tuotetiedot = $xp->find('Group25',$msgcontext);
				
				print "Tuotetiedot löydetty! Niitä on " . $tuotetiedot-> size() . "\n";
	
				my($dd) = {
					'tuoteno' => 'LIN/@eC212.7140',
					'rsumma'=>'Group26/MOA[@eC516.5025="203"]/@eC516.5004',
					'vat' =>'Group33/TAX[@eC241.5153="VAT"]/@eC243.5278',
					'info' => 'IMD/@eC273.7008.1'
				};

				# poistettiin: 'info' => 'IMD[@eC273.7008.2="."]/@eC273.7008.1'
				
				$i=0;
				foreach $tuotetieto ($tuotetiedot->get_nodelist) {	

					print "*";
					foreach $ddkey (keys %{$dd}) { 
						
						$nodeset = $xp->find($dd->{$ddkey},$tuotetieto);
			
						foreach my $node ($nodeset->get_nodelist) {
							$arvo=$node->getData();
							$arvo =~ s/&/ et /g ;
							$url = $url.$ddkey."[" .$i."]=".$arvo."&";
						}
					}
					$i++;
				}
				print "\nTuotetiedot parseroitu!\n";

				print $url . "\n\n";

				###### urli käsittelyyn #####

				$ua = LWP::UserAgent->new;
				$request = HTTP::Request->new(GET,$url);
				$response = $ua->request($request);

				print "response content ".length($response->content).": ".$response->content;

				if (length($response->content) eq 3) {
					$ok=0;
				}
				else {
					$errormsg="URL:\n".$url;
					$ok=1;
				}

				############ käsittele faili end ############
			}
		}

		$smtp = Net::SMTP->new($emailserver);
		$smtp->mail($emailfrom);
		$smtp->to($email);
		$smtp->data();
		$smtp->datasend("From: ".$emailfrom."\n");
		$smtp->datasend("To: ".$email."\n");

		if ($ok == 0) {
			$smtp->datasend("Subject: Pupesoft: $laskuttaja verkkolasku numero $laskunro\n\n");
			$smtp->datasend($response->content."\n\n");
			system("mv -f ".$nimi." ".$okdir);
		}
		else {
			$smtp->datasend("Subject: Pupesoft: ** Verkkolasku ERROR! **\n\n");
			$smtp->datasend("Pupesoft verkkolasku '".$file."' ERROR!\n\n");
			$smtp->datasend("Error message: '".$response->content."'\n\n");
			$smtp->datasend(length($response->content)."\n\n");
			$smtp->datasend($errormsg);
			system("mv -f ".$nimi." ".$errordir);
		}
		
		$smtp->dataend();
		$smtp->quit;		
	}
}
