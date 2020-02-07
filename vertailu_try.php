<?php

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
$useslave = 1;

require "inc/parametrit.inc";

echo "<font class='head'>Vertaa kilpailijoita tuoteryhmitt‰in</font><hr>";
flush();

if ($try != "" and $kukarow["eposti"] != "") {

  $query = "SELECT * from vertailu limit 1";
  $resul = mysql_query($query) or pupe_error($query);

  // tehd‰‰n headerit
  $ulos = "rivi\ttukkuri\ttuoteno\tmyyntihinta\tostohinta\tvaluutta\tmyydyt kpl 12kk\thinnastoon\ttahtituote\tnimitys\tepakurantti25pvm\tepakurantti50pvm\tepakurantti75pvm\tepakurantti100pvm\tmyyt‰viss‰ kpl\r\n";

  // haetaan kaikki arwidsonin tuotteet tuoteryhm‰st‰
  $query =  "SELECT tuote.tuoteno, myyntihinta, group_concat(ostohinta), group_concat(valuutta), osasto, try, hinnastoon, tahtituote, nimitys, epakurantti25pvm, epakurantti50pvm, epakurantti75pvm, epakurantti100pvm
             from tuote use index (yhtio_try_index), tuotteen_toimittajat use index (yhtio_tuoteno)
             where tuote.yhtio     = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno
             and tuote.yhtio='$kukarow[yhtio]'
             and tuote.try='$try'
             AND tuote.tuotetyyppi NOT IN ('A', 'B')
             group by tuote.tuoteno order by tuoteno";
  $resul = mysql_query($query) or pupe_error($query);

  // edvuosi, tarvitaan myynnin hakemisessa.. n‰in on varmaan nopeempi kun mysqll‰n date_sub(now(), interval 12 month)
  $edvuosi = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));

  echo "<font class='message'>Tuoteryhm‰ss‰ on ".mysql_num_rows($resul)." tuotetta.</font><br>";
  flush();

  $edtuote   = "";
  $korvaavat = array();
  $ulosapu   = array();
  $rivinro   = 0;

  // k‰yd‰‰n l‰pi tuotteet
  while ($kala = mysql_fetch_array($resul)) {

    // haetaan tuotteen vertailut
    $query = "SELECT * from vertailu where arwidson = '$kala[tuoteno]'";
    $res   = mysql_query($query) or pupe_error($query);

    while ($rivi = mysql_fetch_array($res)) {

      // k‰yd‰‰n l‰pi jokainen columni
      for ($i=0; $i<mysql_num_fields($res)-5; $i++) {

        // laitetaan alkuun kaks rivinvaihtoa jos arwidsonin tuote vaihtuu
        if ($edtuote != "" and $edtuote != $kala["tuoteno"]) {

          // sortataan apuarray, niin saadaan toimittajan mukaan listaus
          sort($ulosapu);

          // echotaan array ulos
          foreach ($ulosapu as $apukala) {
            $rivinro++;
            $ulos .= "$rivinro\t".$apukala;
          }

          // rivinvaihdoillekki oma rivinumero
          $rivinro++;
          $ulos .= "$rivinro\r\n";
          $rivinro++;
          $ulos .= "$rivinro\r\n";

          $korvaavat = array(); // reset array kun tuote vaihtui
          $ulosapu   = array();
        }
        $edtuote = $kala["tuoteno"];

        // jos joku tuotenumero tuli ja ei olla viel‰ t‰t‰ k‰sitelty
        if ($rivi[$i] != "" and !in_array($rivi[$i], $korvaavat)) {

          // lis‰t‰‰n arrayseen, ett‰ t‰m‰ tuote on jo n‰ytetty
          $korvaavat[]=$rivi[$i];

          // arwidson saa erikoiskohtelun..
          if (mysql_field_name($res, $i) == "arwidson") {

            // haetaan myydyt kappaleet tuotteelle
            $query = "SELECT sum(kpl) kpl from tilausrivi use index(yhtio_tyyppi_tuoteno_laskutettuaika) where yhtio='$kukarow[yhtio]' and tyyppi='L' and tuoteno='$kala[tuoteno]' and laskutettuaika >= '$edvuosi'";
            $myyre = mysql_query($query) or pupe_error($query);
            $myyro = mysql_fetch_array($myyre);

            if ($kala['hinnastoon'] == '') {
              $kala['hinnastoon'] = 'K';
            }

            // teh‰‰ rivi
            $ulosapu[] = "arwidson\t$rivi[$i]\t".str_replace(".", ",", $kala["myyntihinta"])."\t".str_replace(".", ",", $kala["ostohinta"])."\t$kala[valuutta]\t".str_replace(".", ",", $myyro["kpl"])."\t$kala[hinnastoon]\t$kala[tahtituote]\t$kala[nimitys]\t$kala[epakurantti25pvm]\t$kala[epakurantti50pvm]\t$kala[epakurantti75pvm]\t$kala[epakurantti100pvm]\t".saldo_myytavissa($rivi[$i])."\r\n";

            //katotaan korvaavat tuotteet
            $query  = "SELECT * from korvaavat where tuoteno='$rivi[$i]' and yhtio='$kukarow[yhtio]'";
            $korvaresult = mysql_query($query) or pupe_error($query);

            if (mysql_num_rows($korvaresult) != 0) {

              // tuote lˆytyi, joten haetaan sen id...
              $idrow  = mysql_fetch_array($korvaresult);
              $id    = $idrow['id'];

              $query = "SELECT * from korvaavat use index (yhtio_id) where id='$id' and tuoteno<>'$rivi[$i]' and yhtio='$kukarow[yhtio]' order by jarjestys, tuoteno";
              $korva2result = mysql_query($query) or pupe_error($query);

              while ($korvarow = mysql_fetch_array($korva2result)) {

                // sit haetaan viel‰ korvaaville hinta
                $query =  "SELECT tuote.tuoteno, myyntihinta, group_concat(ostohinta), group_concat(valuutta), osasto, try, hinnastoon, tahtituote, nimitys, epakurantti25pvm, epakurantti50pvm, epakurantti75pvm, epakurantti100pvm
                           from tuote use index (tuoteno_index), tuotteen_toimittajat use index (yhtio_tuoteno)
                           where tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno
                           and tuote.yhtio='$kukarow[yhtio]' and tuote.tuoteno='$korvarow[tuoteno]'
                           group by tuote.tuoteno order by tuoteno";
                $korrr = mysql_query($query) or pupe_error($query);
                $krrow = mysql_fetch_array($korrr);

                // haetaan myydyt kappaleet tuotteelle
                $query = "SELECT sum(kpl) kpl from tilausrivi use index(yhtio_tyyppi_tuoteno_laskutettuaika) where yhtio='$kukarow[yhtio]' and tyyppi='L' and tuoteno='$krrow[tuoteno]' and laskutettuaika >= '$edvuosi'";
                $myyre = mysql_query($query) or pupe_error($query);
                $myyro = mysql_fetch_array($myyre);

                // teh‰‰ rivi
                $ulosapu[] = "arwidson\t$krrow[tuoteno]\t".str_replace(".", ",", $krrow["myyntihinta"])."\t".str_replace(".", ",", $krrow["ostohinta"])."\t$krrow[valuutta]\t".str_replace(".", ",", $myyro["kpl"])."\t$kala[hinnastoon]\t$kala[tahtituote]\t$kala[nimitys]\t$kala[epakurantti25pvm]\t$kala[epakurantti50pvm]\t$kala[epakurantti75pvm]\t$kala[epakurantti100pvm]\t".saldo_myytavissa($krrow["tuoteno"])."\r\n";
              }
            }
          }
          elseif (mysql_field_name($res, $i) != "yhtio") {
            // haetaan hinta
            $query = "SELECT * from vertailu_hinnat where tukkuri='".mysql_field_name($res, $i)."' and tuote='$rivi[$i]'";
            $resve = mysql_query($query) or pupe_error($query);
            $resri = mysql_fetch_array($resve);

            // teh‰‰ rivi
            $ulosapu[] = "".mysql_field_name($res, $i)."\t$rivi[$i]\t".str_replace(".", ",", $resri["hinta"])."\r\n";

            // katotaan onko korvaavia t‰lle tuotteelle
            $query = "SELECT * from vertailu_korvaavat where tukkuri='".mysql_field_name($res, $i)."' and (tuote1='$rivi[$i]' or tuote2='$rivi[$i]')";
            $resve = mysql_query($query) or pupe_error($query);

            while ($korva = mysql_fetch_array($resve)) {

              // haetaan hinta
              $kortuote = "";
              if ($rivi[$i] != $korva["tuote1"]) $kortuote = $korva["tuote1"];
              if ($rivi[$i] != $korva["tuote2"]) $kortuote = $korva["tuote2"];

              // lis‰t‰‰n arrayseen, ett‰ t‰m‰ tuote on jo n‰ytetty
              $korvaavat[]=$kortuote;

              $query = "SELECT * from vertailu_hinnat where tukkuri='".mysql_field_name($res, $i)."' and tuote='$kortuote'";
              $ressy = mysql_query($query) or pupe_error($query);
              $resri = mysql_fetch_array($ressy);

              // teh‰‰ rivi
              $ulosapu[] = "".mysql_field_name($res, $i)."\t$kortuote\t".str_replace(".", ",", $resri["hinta"])."\r\n";
            }
          }
        }

      } // end for looppi

    } // end while rivi

  } // end while kala

  $bound = uniqid(time()."_") ;

  $header  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n";
  $header .= "MIME-Version: 1.0\n" ;
  $header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

  $content = "--$bound\n";

  $content .= "Content-Type: application/vnd.ms-excel; name=\"korvaavat.txt\"\n" ;
  $content .= "Content-Transfer-Encoding: base64\n" ;
  $content .= "Content-Disposition: attachment; filename=\"korvaavat.txt\"\n\n";

  $content .= chunk_split(base64_encode($ulos));
  $content .= "\n" ;

  $content .= "--$bound\n";

  $boob = mail($kukarow["eposti"], mb_encode_mimeheader("Korvaavat try $try", "ISO-8859-1", "Q"), $content, $header, "-f $yhtiorow[postittaja_email]");

  echo "<font class='message'><br>";
  if ($boob===FALSE) echo "Email l‰hetys ep‰onnistui $kukarow[eposti]!";
  else echo "Meili l‰hetetty $kukarow[eposti]...";
  echo "</font>";
}
else {

  echo "<form method='post' name='sendfile'>";

  // tehd‰‰n avainsana query
  $res = t_avainsana("TRY");

  echo "<font class='message'>Vertailu l‰hetet‰‰n s‰hkˆpostiisi $kukarow[eposti].<br><br>";

  print "<select name='try'>";
  print "<option value=''>Valitse tuoteryhm‰</option>";

  while ($rivi=mysql_fetch_array($res)) {
    $selected='';
    if ($try==$rivi["selite"]) $selected=' SELECTED';
    echo "<option value='$rivi[selite]'$selected>$rivi[selite] - $rivi[selitetark]</option>";
  }

  print "</select>";

  echo "<input type='submit' value='Aja vertailu'></form>";
}

require "inc/footer.inc"
