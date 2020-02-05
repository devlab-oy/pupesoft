<?php

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
$useslave = 1;

require "inc/parametrit.inc";

function idconv($id) {

  // k‰‰nnet‰‰n numerot tietokantakenttien nimiksi
  if     ($id == "koivunen")  $id = 1;
  elseif ($id == "atoy")      $id = 3;
  elseif ($id == "orum")      $id = 4;
  elseif ($id == "kaha")      $id = 5;
  elseif ($id == "hl")        $id = 6;
  elseif ($id == "arwidson")  $id = 9;
  elseif ($id == "bosh")      $id = 11;
  elseif ($id == "sn")        $id = 13;
  elseif ($id == "motoral")   $id = 16;
  elseif ($id == "sn")        $id = 30;
  else {
    $id = "";
  }

  return $id;
}


echo "<font class='head'>Futursoft export</font><hr>";
flush();

if ($try != "" and $kukarow["eposti"] != "") {

  // haetaan kaikki arwidsonin tuotteet tuoteryhm‰st‰
  $query = "select tuoteno from tuote use index (yhtio_try_index) where yhtio='$kukarow[yhtio]' and try='$try' order by tuoteno";
  $resul = mysql_query($query) or pupe_error($query);

  echo "<font class='message'>Tuoteryhm‰ss‰ on ".mysql_num_rows($resul)." tuotetta.</font><br>";
  flush();

  $ulos = "";

  // k‰yd‰‰n l‰pi tuotteet
  while ($kala = mysql_fetch_array($resul)) {

    // haetaan tuotteen vertailut
    $query = "select * from vertailu where arwidson='$kala[tuoteno]'";
    $res   = mysql_query($query) or pupe_error($query);

    while ($rivi = mysql_fetch_array($res)) {

      // jos arwilla on tuote, tehd‰‰n vertailurivi
      if ($rivi["arwidson"] != "") {

        // k‰yd‰‰n l‰pi jokainen columni
        for ($i=0; $i < mysql_num_fields($res)-1; $i++) {

          if ($rivi[$i] != "" and mysql_field_name($res, $i) != "arwidson" and idconv(mysql_field_name($res, $i)) != "") {
            $ulos.= sprintf('%-5.5s'  , idconv(mysql_field_name($res, $i)));  // kilpailijan id
            $ulos.= sprintf('%-20.20s', $rivi[$i]);              // kilpailijan tuoteno
            $ulos.= sprintf('%-5.5s'  , idconv("arwidson"));        // arwidsonin id
            $ulos.= sprintf('%-20.20s', $rivi["arwidson"]);          // arwidsonon tuoteno
            $ulos.= "\r\n";
          }

          // katotaan viel‰ korvaavatkin
          if ($rivi[$i] != "" and idconv(mysql_field_name($res, $i)) != "") {

            // katotaan korvaavat tuote1
            $query = "select * from vertailu_korvaavat where tukkuri='".mysql_field_name($res, $i)."' and tuote1='$rivi[$i]'";
            $fkore = mysql_query($query) or pupe_error($query);

            while ($fkori = mysql_fetch_array($fkore)) {
              $ulos.= sprintf('%-5.5s'  , idconv(mysql_field_name($res, $i)));  // tukkuri id
              $ulos.= sprintf('%-20.20s', $fkori["tuote1"]);          // tuoteno
              $ulos.= sprintf('%-5.5s'  , idconv(mysql_field_name($res, $i)));  // tukkuri id
              $ulos.= sprintf('%-20.20s', $fkori["tuote2"]);          // tuoteno
              $ulos.= "\r\n";
            }

            // katotaan korvaavat tuote2
            $query = "select * from vertailu_korvaavat where tukkuri='".mysql_field_name($res, $i)."' and tuote2='$rivi[$i]'";
            $fkore = mysql_query($query) or pupe_error($query);

            while ($fkori = mysql_fetch_array($fkore)) {
              $ulos.= sprintf('%-5.5s'  , idconv(mysql_field_name($res, $i)));  // tukkuri id
              $ulos.= sprintf('%-20.20s', $fkori["tuote1"]);          // tuoteno
              $ulos.= sprintf('%-5.5s'  , idconv(mysql_field_name($res, $i)));  // tukkuri id
              $ulos.= sprintf('%-20.20s', $fkori["tuote2"]);          // tuoteno
              $ulos.= "\r\n";
            }

          }
        } // end for looppi

      } // end if rivi arwidson

    } // end while rivi

  } // end while kala

  $bound = uniqid(time()."_") ;

  $header  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\r\n";
  $header .= "MIME-Version: 1.0\r\n" ;
  $header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\r\n" ;

  $content = "--$bound\r\n";

  $content .= "Content-Type: application/vnd.ms-excel; name=\"futursoft.txt\"\r\n" ;
  $content .= "Content-Transfer-Encoding: base64\r\n" ;
  $content .= "Content-Disposition: attachment; filename=\"futursoft.txt\"\r\n\r\n";

  $content .= chunk_split(base64_encode($ulos));
  $content .= "\r\n" ;

  $content .= "--$bound\r\n";

  $boob = mail($kukarow["eposti"], mb_encode_mimeheader("Futursoft vertailu try $try", "ISO-8859-1", "Q"), $content, $header, "-f $yhtiorow[postittaja_email]");

  echo "<font class='message'><br>";
  if ($boob===FALSE) echo "Email l‰hetys ep‰onnistui $kukarow[eposti]!";
  else echo "Meili l‰hetetty $kukarow[eposti]...";
  echo "</font>";
}
else {

  echo "<form method='post' name='sendfile'>";

  // tehd‰‰n avainsana query
  $res = t_avainsana("TRY");

  echo "<font class='message'>Export l‰hetet‰‰n s‰hkˆpostiisi $kukarow[eposti].<br><br>";

  print "<select name='try'>";
  print "<option value=''>Valitse tuoteryhm‰</option>";

  while ($rivi=mysql_fetch_array($res)) {
    $selected='';
    if ($try==$rivi["selite"]) $selected=' SELECTED';
    echo "<option value='$rivi[selite]'$selected>$rivi[selite] - $rivi[selitetark]</option>";
  }

  print "</select>";

  echo "<input type='submit' value='L‰het‰'></form>";
}

require "inc/footer.inc"
