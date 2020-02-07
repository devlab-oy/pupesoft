#!/usr/bin/perl

use Socket;
use DBI;
use FileHandle;

open(STDOUT, ">pupe-autolink-tkysy.log") || die "Can't redirect stdout";
open(STDERR, ">&STDOUT") || die "Can't dup stdout";
open(STDIN,  ">&STDOUT") || die "Can't dup stdout";

select(STDERR); $| = 1;    # make unbuffered
select(STDOUT); $| = 1;    # make unbuffered
select(STDIN);  $| = 1;    # make unbuffered

$SIG{CHLD} = 'IGNORE';

($port) = @ARGV;
$port = 2153 unless $port;

($name, $aliases, $protocol) = getprotobyname('tcp');

if ($port !~ /^\d+$/) {
     ($name, $aliases, $port) = getservbyport($port, 'tcp');
}

print "Server starting.. Listening on port $port.\n\n";

socket(S,AF_INET,SOCK_STREAM,$protocol) || die "socket : $!";

$sockaddr = 'S n a4 x8';
$this = pack($sockaddr, AF_INET, $port, "\0\0\0\0");
bind(S, $this) || die "bind : $!";

listen(S,10) || die "listen: $!";

select(S);
$| = 1;
select(STDOUT);

for ($con = 1; ; $con++) {

  #printf("Waiting for connection %d....\n", $con);

  ($addr = accept(NS,S)) || die $!;
  select(NS);
  $| = 1;
  select(STDOUT);

  if (($child = fork()) == 0) {
  
    ($af,$port, $inetaddr) = unpack($sockaddr, $addr);
    @inetaddr = unpack('C4', $inetaddr);

    print "Con $con from @inetaddr\n";

    while (<NS>) {

      ### ETSITÄÄN SAATU TUOTENUMERO ###

      $messu = $_; # saatu strigi $messuun

      $ityyppi = substr($messu,0,2);
      $ituono  = substr($messu,31,20);
      $isaldo  = substr($messu,51,5)/100;
      $firma   = substr($messu,56,4);

      $firma = "artr";

      $ituono =~tr/a-zA-Z0-9\-\.\// /cs;  # kaikki tuntemattomat spaceks
      $ituono =~tr/ //d;          # spacet pois
      $ituono =~tr/./-/;          # pisteet viivoiksi

      print "Con $con tkysy: $ituono\n";

      $tuoteno = $ituono;

      ##### DB CONNECT #####

      $dbh=DBI->connect("DBI:mysql:database=pupesoft:host=JOKUIPTAHAN","pupesoft","pupe1") || die;

      #Tuote
      $statement="select myyntihinta, tuoteno from tuote where tuoteno='$tuoteno' and yhtio='$firma'";
      $ste = $dbh->prepare($statement) or die $dbh->errstr;
      $rv = $ste->execute or die $ste->errstr;
      @row = $ste->fetchrow_array;
      $ste->finish;

      if ($row[1] ne '') {
      
        #Ennakkopoistot
        $statement="select sum(varattu) from tilausrivi where yhtio = '$firma' and tuoteno = '$tuoteno' and varattu > 0 and tyyppi = 'L'";
        $ste = $dbh->prepare($statement) or die $dbh->errstr;
        $rv = $ste->execute or die $ste->errstr;
        @enn = $ste->fetchrow_array;
        $ste->finish;

        #Saldo
        $statement="select sum(saldo) from tuotepaikat where yhtio = '$firma' and tuoteno = '$tuoteno'";
        $ste = $dbh->prepare($statement) or die $dbh->errstr;
        $rv = $ste->execute or die $ste->errstr;
        @sal = $ste->fetchrow_array;
        $ste->finish;

        $saldo = $sal[0] - $enn[0];

        $sald  = sprintf("%-5s", $saldo*100);
        $space = sprintf("%-32s"," ");
        $tnro  = sprintf("%-20s",$row[1]);
        $hin   = sprintf("%-18s",$row[0]*100);
        print NS "01".$space.$tnro.$sald.$hin."\n";

        $statement="select myyntihinta, tuoteno from tuote where tuoteno='$tuoteno' and yhtio='$firma'";
        $ste = $dbh->prepare($statement) or die $dbh->errstr;
        $rv = $ste->execute or die $ste->errstr;
        @row = $ste->fetchrow_array;
        $ste->finish;

        #Korvaavat
        $statement="select id from korvaavat where yhtio = '$firma' and tuoteno = '$tuoteno'";
        $ste = $dbh->prepare($statement) or die $dbh->errstr;
        $rv = $ste->execute or die $ste->errstr;
        @kor = $ste->fetchrow_array;
        $ste->finish;
      
        if ($kor[0] ne '0') {
        
          $statement="select tuoteno from korvaavat where yhtio = '$firma' and id = '$kor[0]'";
          $sth = $dbh->prepare($statement) or die $dbh->errstr;
          $rv = $sth->execute or die $sth->errstr;

          while (@kor = $sth->fetchrow_array) {

            #Korvaavatuote
            $statement="select myyntihinta, tuoteno from tuote where tuoteno='$kor[0]' and yhtio='$firma'";
            $ste = $dbh->prepare($statement) or die $dbh->errstr;
            $rv = $ste->execute or die $ste->errstr;
            @row = $ste->fetchrow_array;
            $ste->finish;

            #Ennakkopoistot
            $statement="select sum(varattu) from tilausrivi where yhtio = '$firma' and tuoteno = '$kor[0]' and varattu > 0 and tyyppi = 'L'";
            $ste = $dbh->prepare($statement) or die $dbh->errstr;
            $rv = $ste->execute or die $ste->errstr;
            @enn = $ste->fetchrow_array;
            $ste->finish;

            #Saldo
            $statement="select sum(saldo) from tuotepaikat where yhtio = '$firma' and tuoteno = '$kor[0]'";
            $ste = $dbh->prepare($statement) or die $dbh->errstr;
            $rv = $ste->execute or die $ste->errstr;
            @sal = $ste->fetchrow_array;
            $ste->finish;
          
            $saldo = $sal[0] - $enn[0];
          
            if ($saldo > 0) {
              $sald  = sprintf("%-5s", $saldo*100);
              $space = sprintf("%-32s"," ");
              $tnro  = sprintf("%-20s",$row[1]);
              $hin   = sprintf("%-18s",$row[0]*100);
              print NS "02".$space.$tnro.$sald.$hin."\n";
            }
          }

          $sth->finish;
        }

        print NS "99\n";

        $rc=$dbh->disconnect;
      }
      else {
        $space = sprintf("%-32s"," ");
        $tnro  = sprintf("%-20s",$tuoteno);
        print NS "95\n";
      }

    } # end while

    close(NS);
    print "Con $con went away..\n";
    exit;
    
  } # end if

  close(NS);

} # end for

close(STDOUT);
close(STDERR);
close(STDIN);
