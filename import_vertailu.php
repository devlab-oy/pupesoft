<?php

require "inc/parametrit.inc";

echo "<font class='head'>Sis‰‰nlue kilpailijavertailuja</font><hr>";

function idconv($id) {

  // k‰‰nnet‰‰n numerot tietokantakenttien nimiksi
  if     ($id == 1)   $id = "koivunen";
  elseif ($id == 3)   $id = "atoy";
  elseif ($id == 4)   $id = "orum";
  elseif ($id == 5)   $id = "kaha";
  elseif ($id == 6)   $id = "hl";
  elseif ($id == 9)   $id = "arwidson";
  elseif ($id == 11)  $id = "bosh";
  elseif ($id == 13)  $id = "sn";
  elseif ($id == 16)  $id = "motoral";
  elseif ($id == 30)  $id = "sn";      // sama kuin 13!? mit‰ hemmetti‰...
  else {
    $id = "";
  }

  return $id;
}


if (is_uploaded_file($_FILES['userfile']['tmp_name'])==TRUE) {

  $path_parts = pathinfo($_FILES['userfile']['name']);
  $name  = strtoupper($path_parts['filename']);
  $ext  = strtoupper($path_parts['extension']);

  if ($ext != "TXT" and $ext != "CSV") {
    die ("<font class='error'><br>".t("Ainoastaan .txt ja .cvs tiedostot sallittuja")."!</font>");
  }

  if ($_FILES['userfile']['size']==0) {
    die ("<font class='error'><br>".t("Tiedosto on tyhj‰")."!</font>");
  }

  $file=fopen($_FILES['userfile']['tmp_name'], "r") or die (t("Tiedoston avaus ep‰onnistui")."!");

  echo "<font class='message'>File ok.. nyt p‰ivitet‰‰n.<br><br>";
  flush();

  $update = 0; // laskuri
  $insert = 0; // laskuri
  $loytyi = 0; // laskuri
  $korvaa = 0; // laskuri

  // luetaan tiedostoa...
  $rivi = fgets($file);

  while (!feof($file)) {

    $pos = strpos($rivi, "\t"); // katotaan lˆytyykˆ tabi

    // jos ei lˆydy, kyseess‰ on varmaan m‰‰r‰mittainen tiedosto
    if ($pos === false) {
      $id1    = (int)substr($rivi, 0 , 5);
      $id2    = (int)substr($rivi, 25, 5);
      $tuote1 = trim(substr($rivi, 5 , 20));
      $tuote2 = trim(substr($rivi, 30, 20));
    }
    else {
      list($id1, $tuote1, $id2, $tuote2) = explode("\t", $rivi);
      $id1    = (int) $id1;
      $id2    = (int) $id2;
      $tuote1 = trim($tuote1);
      $tuote2 = trim($tuote2);
    }

    $nono = array("'", "\"");  // kiellettyj‰ merkkej‰ kaksois- ja yksˆishipsut
    $tuote1 = str_replace($nono, "", $tuote1);
    $tuote2 = str_replace($nono, "", $tuote2);

    // k‰‰nnet‰‰n numerot nimiksi...
    $id1 = idconv($id1);
    $id2 = idconv($id2);
    $paiv = 0; // t‰h‰n summaillaan mysql_affected_rows()

    if ($id1 != "" and $id2 != "" and $id1 != $id2 and $tuote1 != "" and $tuote2 != "") {

      // jos t‰m‰ yhteys on jo, ei tehd‰ mit‰‰n...
      $query = "select * from vertailu where $id1='$tuote1' and $id2='$tuote2'";
      $res   = mysql_query($query);

      if (mysql_num_rows($res) == 0) {

        $query = "select * from vertailu where $id1='$tuote1' and $id2=''";
        $res   = mysql_query($query);

        if (mysql_num_rows($res) != 0) {
          // kasvatetaan p‰ivitettyj‰
          $paiv += mysql_num_rows($res);
          // lˆytyi, p‰ivitet‰‰n... vaan yks.
          $query = "update vertailu set $id2='$tuote2' where $id1='$tuote1' and $id2='' limit 1";
          $res   = mysql_query($query);
        }

        $query = "select * from vertailu where $id2='$tuote2' and $id1=''";
        $res   = mysql_query($query);

        if (mysql_num_rows($res) != 0) {
          // kasvatetaan p‰ivitettyj‰
          $paiv += mysql_num_rows($res);
          // lˆytyi, p‰ivitet‰‰n... vaan yks.
          $query = "update vertailu set $id1='$tuote1' where $id2='$tuote2' and $id1='' limit 1";
          $res   = mysql_query($query);
        }

        $update += $paiv; // lasketaan p‰ivitettyj‰ rivej‰

        // ei p‰ivittynyt yksk‰‰n rivi kummallakaan tuotteella..
        if ($paiv == 0) {
          // ja jos ei ole, niin lis‰t‰‰n se
          $query = "insert into vertailu ($id1, $id2) values ('$tuote1', '$tuote2')";
          $res   = mysql_query($query);
          $insert++; // lasketaan lis‰ttyj‰ rivej‰
        }
      }
      else {
        $loytyi++;
      }

    } // end eka if
    elseif ($id1 == $id2) {

      // jos vertaillaan saman tukkurin tuotteita
      $query = "select * from vertailu_korvaavat where tukkuri='$id1' and tuote1 in ('$tuote1', '$tuote2') and tuote2 in ('$tuote1', '$tuote2')";
      $res   = mysql_query($query);

      if (mysql_num_rows($res) == 0) {
        $query = "insert into vertailu_korvaavat (tukkuri, tuote1, tuote2) values ('$id1', '$tuote1', '$tuote2')";
        $res   = mysql_query($query);
        $korvaa++;
      }

    }
    // luetaan seuraava rivi failista
    $rivi = fgets($file);

  } // end while eof

  fclose($file);

  echo "<li>Vanhoja $loytyi";
  echo "<li>Lis‰ttiin $insert";
  echo "<li>P‰ivitettiin $update";
  echo "<li>Korvaavia lis‰ttiin $korvaa";
  echo "<br><br></font>";

}
else {
  echo "<font class='message'>Luetaan sis‰‰n Futursoft-muodossa oleva positiopohjainen tekstitiedosto (tukkuri 5 merkki‰, tuote 20 merkki‰, tukkuri 5 merkki‰, tuote 20 merkki‰).</font><br><br>

      <form method='post' name='sendfile' enctype='multipart/form-data'>
      <table>
      <tr><th>Valitse tiedosto:</th>
        <td><input name='userfile' type='file'></td>
        <td class='back'><input type='submit' value='L‰het‰'></td>
      </tr>
      </table>
      </form>";

  echo "<font class='message'>Tukkurit:<br>";
  for ($i=0; $i<31; $i++) {
    $apu = idconv($i);
    if ($apu != "") echo "$i = $apu<br>";
  }
  echo "</font>";
}

require "inc/footer.inc"
