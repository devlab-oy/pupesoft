<?php

require "inc/parametrit.inc";

echo "<font class='head'>Sis‰‰nlue kilpailijahintoja</font><hr>";

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

  // luetaan tiedostoa...
  $rivi = fgets($file);

  while (!feof($file)) {

    list($id, $tuote, $hinta) = explode("\t", $rivi);

    $id    = (int) $id;
    $tuote = trim($tuote);
    $hinta = str_replace(",", ".", trim($hinta));

    $nono  = array("'", "\"");  // kiellettyj‰ merkkej‰ kaksois- ja yksˆishipsut
    $tuote = str_replace($nono, "", $tuote);
    $hinta = str_replace($nono, "", $hinta);

    // k‰‰nnet‰‰n numerot nimiksi...
    $id = idconv($id);

    if ($id != "" and $tuote != "" and $hinta != "") {

      $query = "select * from vertailu_hinnat where tukkuri='$id' and tuote='$tuote'";
      $res   = mysql_query($query);

      if (mysql_num_rows($res) == 0) {
        // ei ollut duplikaatti, lis‰t‰‰n
        $query = "insert into vertailu_hinnat set tukkuri='$id', tuote='$tuote', hinta='$hinta'";
        $res   = mysql_query($query);
        $insert++;
      }
      else {
        // lˆytyi, p‰ivitet‰‰n hinta
        $query = "update vertailu_hinnat set hinta='$hinta' where tukkuri='$id' and tuote='$tuote'";
        $res   = mysql_query($query);
        $update++;
      }
    } // end eka if
    else {
      echo "Virheellinen rivi! tukkuri: '$id' tuote: '$tuote' hinta: '$hinta'<br>";
    }

    // luetaan seuraava rivi failista
    $rivi = fgets($file);

  } // end while eof

  fclose($file);

  echo "<li>Lis‰ttiin $insert";
  echo "<li>P‰ivitettiin $update";
  echo "<br><br></font>";

}
else {
  echo "<font class='message'>Luetaan sit‰‰n tab-eroteltu tekstitiedosto (tukkuri, tuote, hinta).</font><br><br>

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
