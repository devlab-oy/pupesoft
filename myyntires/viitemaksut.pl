#!/usr/bin/perl
#
# Verkokauppa projektin koodia.. kovasti muokattuna.
#
# Luetaan viitemaksuja dirikasta...
# Automaattikohdistetaan viitemaksuja...
# Tehdään kohdistamattomista laskuja...
#

use DBI;
use Data::Dumper;
use IO::File;
use Getopt::Long;

GetOptions( "user=s" => \$dbuser,
            "pass=s"  => \$dbpass,
 			"host=s"  => \$dbhost,
			"db=s"  => \$dbname,
			"file=s" => \$nimi);

# Meillä voi siis olla nämä tiedot jo salasana.php:stä
# Jos ei tule kutsussa, niin lisää tähän
if(!$dbhost) {
	$dbhost = "";
}
if(!$dbuser) {
	$dbuser = "";
}
if(!$dbpass) {
	$dbpass = "";
}
if(!$dbname) {
	$dbname = "pupesoft";
}

print "Luetaan viitemaksuja...\n";

if (-f $nimi) {

	# otetaan databasde connectioni
	my $dbh = DBI->connect("DBI:mysql:database=$dbname;host=$dbhost", $dbuser, $dbpass)  or die("Tietokantayhteysepäonnistui: database=$dbname, host=$dbhost, user=$dbuser, passwd=$dbpass\n\n");

	################################
	#
	# luetaan viitemaksut sisään...
	#
	################################



	open(F,"<$nimi") or die "tiedoston $nimi avaaminen epäonnistui $!";

	my $readcnt;
	my $insertcnt=0;
	my $errcnt;

	my $sth_yriti=$dbh->prepare("SELECT yhtio FROM yriti WHERE tilino = ?");
	my $sth_last=$dbh->prepare("SELECT last_insert_id()");
	my $sth_select_at=$dbh->prepare("SELECT min(a.tunnus),max(a.tunnus) from suoritus s, lasku l, asiakas a where l.viite=s.viite and l.summa=s.summa and l.ytunnus=a.ytunnus and a.yhtio=l.yhtio and s.tunnus=l.tunnus and s.tunnus=?");
	my $sth_update_at=$dbh->prepare("UPDATE suoritus SET asiakas_tunnus=? WHERE tunnus=?");

	my $sth=$dbh->prepare("INSERT INTO suoritus (yhtio,tilino,nimi_maksaja,viite,summa,maksupvm,kirjpvm) VALUES (?,?,?,?,?,?,?)");

	my $sth_aineisto=$dbh->prepare("SELECT max(aineisto) FROM tiliotedata");
	my $sth_tiliotedata=$dbh->prepare("INSERT into tiliotedata (yhtio, aineisto, tilino, alku, loppu, tyyppi, tiliointitunnus, tieto, kasitelty) values (?, ?, ?, ?, ?, ?, ?, ?, now())");

	$sth_aineisto->execute();
	my (@aineisto)=$sth_aineisto->fetchrow_array();
	$sth_aineisto->finish;

	if (!@aineisto) {
		die "En saanut aineistonrota luoduksi!";
	}

	$tamaaineisto = $aineisto[0];

	while (<F>) {
		#tietuetunnus on ensimmäinen merkki
		# 0 -> erätietue
		# 3 -> viitesiirto
		# 5 -> suoraveloitus
		# 9 -> summatietue

		my %row;
		my $type=substr($_,0,1);
		if ($type == 0) {
			#print "Tietuetunnus => $type\n";
			#print "Aineston luontipv => ".substr($_,1,6)."\n";
			#print "Aineston luontiaika => ".substr($_,7,4)."\n";
			#print "Rahalaitostunnus => ".substr($_,11,2)."\n";
			#print "Laskuttajan palvelutunnus => ".substr($_,13,9)."\n";
			#print "Rahayksikön koodi => ".substr($_,22,1)."\n";
			#print "Varalla => ".substr($_,23,67)."\n";
			$otsikko=$_;
		} elsif ($type == 3) {
			$readcnt++;

			#viitesiirto
			#print "Oikaisutunnus => ".substr($_,87,1)."\n";
			#print "Välitystapa => ".substr($_,88,1)."\n";
			#print "Palautekoodi => ".substr($_,89,1)."\n";

			$account=substr($_,1,14);
			my $payer_name=substr($_,63,12);
			#print "Maksajan nimen lähde => ".substr($_,76,1)."\n";

			my $reference=substr($_,43,20);
			#print "Arkistointitunnus => ".substr($_,27,16)."\n";

			my $sum=substr($_,77,10);
			my $sum=$sum/100; #XXX summa on sentteinä, etunollatäytöllä.
			my $currency_code=substr($_,75,1);
			if ($currency_code != 1) {
				warn "valuutta ei euroissa! not implemented.";
				$errcnt++;
				next;
			}

			my $entry_date_raw=substr($_,15,6);
			$entry_date_raw =~ /^(..)(..)(..)$/;
			$entry_date="20$1-$2-$3";

			my $payment_date_raw=substr($_,21,6);
			$payment_date_raw =~ /^(..)(..)(..)$/;
			$payment_date="20$1-$2-$3";

			$sth_yriti->execute($account);
			$company=scalar($sth_yriti->fetchrow_array());
			$sth_yriti->finish;

			if (!$company) {
				warn "tilille $account ei löytynyt yritystä.";
				$errcnt++;
				next;
			}

			#arkistointitunnus
			#
			$sth->execute($company,int($account),$payer_name,int($reference),$sum,$payment_date,$entry_date);
			$sth->finish;
			$sth_last->execute;
			my $last_id=scalar($sth_last->fetchrow_array());
			$sth_last->finish;
			$insertcnt++;

			if ($last_id) {
				$sth_select_at->execute($last_id);
				my ($min,$at)=$sth_select_at->fetchrow_array();
				$sth_select_at->finish;
				if ($min && $at && $min==$at) {
					#warn "last id was $last_id and asiakkaan_tunnus is ",$at;
					$sth_update_at->execute($at,$last_id);
					$sth_update_at->finish;
				}
			}
			if ($otsikko ne "") {
				$tamaaineisto++;
				print "Luon aineiston nro $tamaaineisto $entry_date $account ($company)\n";
				$sth_tiliotedata->execute($company, $tamaaineisto, $account, $entry_date, $payment_date, 3, 0, $otsikko);
				$sth_tiliotedata->finish;
				$otsikko = "";
			}
			$sth_tiliotedata->execute($company, $tamaaineisto, $account, $entry_date, $payment_date, 3, $last_id, $_);
			$sth_tiliotedata->finish

		} elsif ($type == 9) {
			#print "Tietuetunnus => $type\n";
			#print "Tapahtumien kpl => ".substr($_,1,6)."\n";
			#print "Tapahtumien määrä => ".substr($_,7,11)."\n";
			#print "Oikaisutapahtumien kpl => ".substr($_,18,6)."\n";
			#print "Oikaisutapahtumien määrä => ".substr($_,24,11)."\n";
			#print "Varalla => ".substr($_,35,5)."\n";
			#print "done, insert/read/error: $insertcnt/$readcnt/$errcnt\n";
			$sth_tiliotedata->execute($company, $tamaaineisto, $account, $entry_date, $payment_date, 3, 0, $_);
			$sth_tiliotedata->finish;
		} else {
			warn "Tuntematon tietuetunnus $type";
			$errcnt++;
			next;
		}
		#print "\n\n";

	}

	######################################
	#
	# automaattikohdistetaan viitemaksut
	#
	######################################

	print "Automaattikohdistetaan viitemaksuja...\n";


	$matchstatement = "	SELECT
						suoritus.tunnus,
						lasku.tunnus,
						suoritus.yhtio,
						yhtio.myyntisaamiset,
						suoritus.summa,
						lasku.summa-lasku.saldo_maksettu,
						yhtio.myynninkassaale,
						suoritus.kirjpvm,
						yriti.oletus_rahatili,
						yhtio.alv,
						yhtio.varasto,
						yhtio.varastonmuutos,
						yhtio.pyoristys,
						lasku.tapvm,
						lasku.summa,
						yhtio.konsernimyyntisaamiset
						FROM yhtio, lasku use index (yhtio_tila_mapvm), suoritus use index (yhtio_viite), yriti
						WHERE yhtio.yhtio=lasku.yhtio and
						yriti.yhtio=lasku.yhtio and
						suoritus.yhtio=lasku.yhtio and
						lasku.viite=suoritus.viite and
						suoritus.tilino=yriti.tilino and
						suoritus.kohdpvm = '0000-00-00' and
						lasku.mapvm = '0000-00-00' and
						lasku.tila = 'U' and
						lasku.alatila = 'X' and
						suoritus.viite > 0 and
						((lasku.kapvm >= adddate(suoritus.kirjpvm,-4) and
						abs(lasku.summa-lasku.saldo_maksettu-lasku.kasumma-suoritus.summa) < 0.01) or
						(abs(lasku.summa-lasku.saldo_maksettu-suoritus.summa) < 0.01))";
	$sth = $dbh->prepare($matchstatement) or die($dbh->errstr." \n\n $matchstatement\n\n");
	$rv  = $sth->execute or die($sth->errstr." \n\n $matchstatement\n\n");

	while (@row = $sth->fetchrow_array()) {

		$suorite	 	= $row[0];
		$lasku 			= $row[1];
		$yhtio 			= $row[2];
		$myyntisaamiset	= $row[3];
		$suoritettu 	= $row[4];
		$laskutettu 	= $row[5];
		$alennustili	= $row[6];
		$kirjpvm 		= $row[7];
		$kassatili		= $row[8];
		$alvtili		= $row[9];
		$varasto		= $row[10];
		$varastomuu		= $row[11];
		$pyoristys		= $row[12];
		$tapvm			= $row[13];
		$laskusumma		= $row[14];
		$konsernimyyntisaamiset	= $row[15];
		$alennus		= $laskutettu-$suoritettu;

		#Katsotaan ensin, ettei tätä laskua ole jo suoritettu/maksettu
		#Eli keissi jossa asiakas maksaa saman laskun kahteen kertaan samassa viiteaineistossa
		$query = "	SELECT tunnus, ytunnus
					FROM lasku
					WHERE tunnus = '$lasku'
					and mapvm = '0000-00-00'";
		$masth = $dbh->prepare($query) or die($dbh->errstr." \n\n $query\n\n");
		$marv  = $masth->execute or die($masth->errstr." \n\n $query\n\n");

		$num_rows  = $masth->rows;

		if ($num_rows == 1) {
			#Etsitään asiakas, jos se olisi konsernin jäsen
			@malaskurow = $masth->fetchrow_array();
			$malasytunnus = $malaskurow[1];
			$query = "	SELECT konserniyhtio
						FROM asiakas
						WHERE ytunnus 	 = '$malasytunnus'
						and yhtio   	 = '$yhtio'";
			$asiakassth = $dbh->prepare($query) or die($dbh->errstr." \n\n $query\n\n");
			$asiakasrv  = $asiakassth->execute or die($asiakassth->errstr." \n\n $query\n\n");
			$num_rows  = $asiakassth->rows;
			if ($num_rows != 0) {
				@asiakasrow = $asiakassth->fetchrow_array();
				if ($asiakasrow[0] eq "o") {
					$myyntisaamiset = $konsernimyyntisaamiset;
				}
			}

			#Myyntisaamiset
			$statement="INSERT INTO tiliointi(yhtio, laatija,laadittu,tapvm,ltunnus,tilino,summa,selite) values ('$yhtio','automaattikohdistus',now(),'$kirjpvm','$lasku','$myyntisaamiset',-$laskutettu,'Automaattikohdistettu asiakkaan suoritus')";
			$stmt_tilioi_myyntisaamisiin=$dbh->prepare($statement);
			#print("\nMyyntisaamiset:\n$statement");
			$stmt_tilioi_myyntisaamisiin->execute or die($dbh->errstr." \n\n $stmt_tilioi_myyntisaamisiin\n\n");

			#Kassatili
			$statement="INSERT INTO tiliointi(yhtio, laatija,laadittu,tapvm,ltunnus,tilino,summa,selite) values ('$yhtio','automaattikohdistus',now(),'$kirjpvm','$lasku','$kassatili',$suoritettu,'Automaattikohdistettu asiakkaan suoritus')";
			$stmt_tilioi_kassaan=$dbh->prepare($statement);
			#print("\nKassa:\n$statement");
			$stmt_tilioi_kassaan->execute or die($dbh->errstr." \n\n $stmt_tilioi_kassaan\n\n");


			#Mahdollinen kassa-alennus
			if($alennus != 0) {
				#$alennus muuttujassa on kassa-alennus mahdollisine arvonlisäveroineen

				#Etsitään myynti-tiliöinnit
				$query = "	SELECT summa, vero
							FROM tiliointi use index (tositerivit_index)
							WHERE ltunnus 	 = '$lasku'
							and yhtio   	 = '$yhtio'
							and tapvm   	 = '$tapvm'
							and abs(summa)  <> 0
							and tilino 		<> '$myyntisaamiset'
							and tilino 		<> '$alvtili'
							and tilino 		<> '$varasto'
							and tilino 		<> '$varastomuu'
							and tilino 		<> '$pyoristys'
							and tilino 		<> '$alennustili'
							and korjattu = ''";
				$tilisth = $dbh->prepare($query) or die($dbh->errstr." \n\n $query\n\n");
				$tilirv  = $tilisth->execute or die($tilisth->errstr." \n\n $query\n\n");

				$num_rows  = $tilisth->rows;

				# jos löytyy vaan yksi myynti-tiliöinti tehdään kassa-ale kirjaus.. mitä jos löytyy useampi!?!?!?
				if ($num_rows == 1) {

					while (@tiliointirow = $tilisth->fetchrow_array()) {
						$alv = 0;
						$summa = sprintf('%.2f', $tiliointirow[0] * -1 * (1+$tiliointirow[1]/100) / $laskusumma * $alennus);

						if ($tiliointirow[1] != 0) {
							#Netotetaan alvi
							#$alv:ssa on alennuksen alv:n maara
							$alv = sprintf('%.2f', $summa - $summa / (1 + ($tiliointirow[1]/100)));

							#$summa on alviton alennus
							$summa -= $alv;
						}

						# Etsitään korjattava vienti
						if ($alv != 0) {

							#Lisätään myynnin kassa-alennuskirjaus ilman veroa
							$statement="INSERT INTO tiliointi
										SET yhtio	= '$yhtio',
										laatija 	= 'automaattikohdistus',
										laadittu 	= now(),
										tapvm 		= '$kirjpvm',
										ltunnus 	= '$lasku',
										tilino 		= '$alennustili',
										summa 		= '$summa',
										vero		= '$tiliointirow[1]',
										selite 		= 'Automaattikohdistettu asiakkaan suoritus (Kassa-alennus)'";
							$stmt_tilioi_alennuksiin=$dbh->prepare($statement);
							$stmt_tilioi_alennuksiin->execute or die($dbh->errstr." \n\n $stmt_tilioi_alennuksiin\n\n");


							my $sth_last_id=$dbh->prepare("SELECT last_insert_id()");
							$sth_last_id->execute();
							my ($aputunnus) = scalar($sth_last_id->fetchrow_array());
							$sth_last_id->finish;


							#Kirjataan myös kassa-alennuksen arvonlisäverot
							$statement="INSERT into tiliointi
										SET yhtio 	= '$yhtio',
										ltunnus 	= '$lasku',
										tilino 		= '$alvtili',
										tapvm 		= '$kirjpvm',
										summa 		= '$alv',
										vero 		= '',
										selite 		= 'Automaattikohdistettu asiakkaan suoritus (Kassa-alennuksen alv)',
										lukko 		= '1',
										laatija 	= 'automaattikohdistus',
										laadittu 	= now(),
										aputunnus 	= '$aputunnus'";
							$stmt_tilioi_alennuksiin=$dbh->prepare($statement);
							$stmt_tilioi_alennuksiin->execute or die($dbh->errstr." \n\n $stmt_tilioi_alennuksiin\n\n");

						}
						else {

							#Lisätään verottoman myynnin kassa-alennuskirjaus
							$statement="INSERT INTO tiliointi
										SET yhtio	= '$yhtio',
										laatija 	= 'automaattikohdistus',
										laadittu 	= now(),
										tapvm 		= '$kirjpvm',
										ltunnus 	= '$lasku',
										tilino 		= '$alennustili',
										summa 		= '$alennus',
										selite 		= 'Automaattikohdistettu asiakkaan suoritus (Kassa-Alennus)'";
							$stmt_tilioi_alennuksiin=$dbh->prepare($statement);
							$stmt_tilioi_alennuksiin->execute or die($dbh->errstr." \n\n $stmt_tilioi_alennuksiin\n\n");

						}
					}
				}
			}

			#Merkitään lasku maksetuksi
			$statement="UPDATE lasku SET mapvm='$kirjpvm' WHERE tunnus='$lasku'";
			$stmt_lasku_maksetuksi=$dbh->prepare($statement);
			#print("\nLasku maksetuksi:\n$statement");
			$stmt_lasku_maksetuksi->execute or die($dbh->errstr." \n\n $stmt_lasku_maksetuksi\n\n");

			#Ja suoritus kirjatuksi
			$statement="UPDATE suoritus SET kohdpvm=now(), ltunnus='$lasku', summa='0' WHERE tunnus='$suorite'";
			$stmt_suorite_kirjatuksi=$dbh->prepare($statement);
			#print("\nSuoritus kirjatuksi:\n$statement");
			$stmt_suorite_kirjatuksi->execute or die($dbh->errstr." \n\n $stmt_suorite_kirjatuksi\n\n");

			#print("Kohdistettu suoritus $suorite ja lasku $lasku\n");
		}
	}



	#####################################
	#
	# siirretaan feilanneet laskuiksi...
	#
	#####################################

	print "Tehdään kohdistamattomista laskuja...\n";

	$matchstatement = "	SELECT suoritus.tunnus tunnus, suoritus.yhtio yhtio, yhtio.myyntisaamiset, suoritus.summa summa, suoritus.kirjpvm, yriti.oletus_rahatili,suoritus.nimi_maksaja
						FROM yhtio, suoritus, yriti
						WHERE suoritus.kohdpvm = '0000-00-00'
						AND suoritus.tilino=yriti.tilino
						AND yriti.yhtio=yhtio.yhtio
						AND suoritus.yhtio=yhtio.yhtio
						AND suoritus.ltunnus = 0
						ORDER BY yhtio";
	$sth       = $dbh->prepare($matchstatement) or die($dbh->errstr." \n\n $matchstatement\n\n");
	$rv        = $sth->execute or die($sth->errstr." \n\n $matchstatement\n\n");

	my %lasKut;
	my $sth_lasku=$dbh->prepare("INSERT into lasku set yhtio = ?, tapvm = now(), tila = 'X', laatija = 'viitesiirrot', luontiaika = now()");
	my $sth_last_id=$dbh->prepare("SELECT last_insert_id()");

	while (@row = $sth->fetchrow_array())
	{

		$suorite 		= $row[0];
		$yhtio 			= $row[1];
		$myyntisaamiset	= $row[2];
		$suoritettu 	= $row[3];
		$kirjpvm 		= $row[4];
		$kassatili		= $row[5];
		$maksaja		= $row[6];

		if (!$laskut{$yhtio}) {
			$sth_lasku->execute($yhtio);
			$sth_lasku->finish;
			$sth_last_id->execute();
			my ($last_id)=scalar($sth_last_id->fetchrow_array());
			$sth_last_id->finish;
			$laskut{$yhtio}=$last_id;
			#warn "luotiin lasku yhtiölle $yhtio";
		}
		my $lasku=$laskut{$yhtio} or die;
		#warn "lasku $lasku";


		#Myyntisaamiset
		$statement="INSERT INTO tiliointi(yhtio, laatija,laadittu,tapvm,ltunnus,tilino,summa,selite,lukko) values ('$yhtio','automaattikohdistus',now(),'$kirjpvm','$lasku','$myyntisaamiset',-$suoritettu,' maksoi viitteellä väärin','1')";
		$stmt_tilioi_myyntisaamisiin=$dbh->prepare($statement);
		#warn("\nMyyntisaamiset:\n$statement");
		$stmt_tilioi_myyntisaamisiin->execute or die($dbh->errstr." \n\n $stmt_tilioi_myyntisaamisiin\n\n");

		$sth_last_id->execute();
		my ($tlast_id)=scalar($sth_last_id->fetchrow_array());
		$sth_last_id->finish;

		#Kassatili
		$statement="INSERT INTO tiliointi(yhtio, laatija,laadittu,tapvm,ltunnus,tilino,summa,selite,aputunnus,lukko) values ('$yhtio','automaattikohdistus',now(),'$kirjpvm','$lasku','$kassatili','$suoritettu',' maksoi viitteellä väärin','$tlast_id','1')";
		$stmt_tilioi_kassaan=$dbh->prepare($statement);
		#warn("\nKassa:\n$statement");
		$stmt_tilioi_kassaan->execute or die($dbh->errstr." \n\n $stmt_tilioi_kassaan\n\n");

		$statement="UPDATE suoritus SET ltunnus='$tlast_id' WHERE tunnus='$suorite'";
		$stmt_suorite_kirjatuksi=$dbh->prepare($statement);
		#warn("\nSuoritus \n$statement");
		$stmt_suorite_kirjatuksi->execute or die($dbh->errstr." \n\n $stmt_suorite_kirjatuksi\n\n");

		#print("Kohdistettu suoritus $suorite ja lasku $lasku\n");
	}

	print("Done.\n");

	$cmd = "rm -f $nimi";
	system($cmd);

} # end if onko file
else {
	print "Tiedosto $tiedosto ei ole tiedosto tai se ei löydy!\n";
}
