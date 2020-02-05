<?php

require "inc/parametrit.inc";

echo "<font class='head'>Sis‰‰nlue arwivertailuja</font><hr>";

if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {

  $path_parts = pathinfo($_FILES['userfile']['name']);
  $name  = strtoupper($path_parts['filename']);
  $ext  = strtoupper($path_parts['extension']);

  if ($ext != "TXT" and $ext != "CSV") {
    die ("<font class='error'><br>".t("Ainoastaan .txt ja .cvs tiedostot sallittuja")."!</font>");
  }

  if ($_FILES['userfile']['size']==0) {
    die ("<font class='error'><br>".t("Tiedosto on tyhj‰")."!</font>");
  }

  $file = fopen($_FILES['userfile']['tmp_name'], "r") or die (t("Tiedoston avaus ep‰onnistui")."!");

  echo "<font class='message'>File ok.. nyt p‰ivitet‰‰n.<br><br>";
  flush();

  $insert = 0; // laskuri
  $tyhjaa = 0; // laskuri
  $query  = "";

  $arwidson = array();
  $bosh     = array();
  $kaha     = array();
  $sn       = array();
  $orum     = array();
  $hl       = array();
  $atoy     = array();
  $koivunen = array();
  $motoral  = array();

  // luetaan tiedostoa...
  $rivi = fgets($file);

  while (!feof($file)) {

    if (strlen(trim($rivi)) == 0) {
      $tyhjaa++;
    }
    else {
      $tyhjaa = 0;

      list($tukkuri, $tuote) = explode("\t", $rivi);

      $tukkuri = trim($tukkuri);
      $tuote   = trim($tuote);

      $nono    = array("'", "\"");  // kiellettyj‰ merkkej‰ kaksois- ja yksˆishipsut
      $tuote   = str_replace($nono, "", $tuote);
      $tukkuri = str_replace($nono, "", $tukkuri);

      // lis‰ill‰‰n tuotteita arraysiin
      if ($tukkuri == "arwidson") $arwidson[]=$tuote;
      if ($tukkuri == "bosh")     $bosh[]=$tuote;
      if ($tukkuri == "kaha")     $kaha[]=$tuote;
      if ($tukkuri == "sn")       $sn[]=$tuote;
      if ($tukkuri == "orum")     $orum[]=$tuote;
      if ($tukkuri == "hl")       $hl[]=$tuote;
      if ($tukkuri == "atoy")     $atoy[]=$tuote;
      if ($tukkuri == "koivunen") $koivunen[]=$tuote;
      if ($tukkuri == "motoral")  $motoral[]=$tuote;
    }

    if ($tyhjaa == 2) {

      $query = "INSERT INTO vertailu SET
                arwidson = '$arwidson[0]',
                bosh     = '$bosh[0]',
                kaha     = '$kaha[0]',
                sn       = '$sn[0]',
                orum     = '$orum[0]',
                hl       = '$hl[0]',
                atoy     = '$atoy[0]',
                koivunen = '$koivunen[0]',
                motoral  = '$motoral[0]',
                yhtio    = '$kukarow[yhtio]'";
      $result = mysql_query($query) or pupe_error($query);

      $insert++;
      //echo "$query<br>";

      // lis‰ill‰‰n korvaavat tuotteet...
      if (count($arwidson) > 1) {
        for ($i=1; $i < count($arwidson); $i++) {
          $query = "INSERT INTO vertailu_korvaavat SET tukkuri='arwidson', tuote1='$arwidson[0]', tuote2='$arwidson[$i]', yhtio='$kukarow[yhtio]'";
          $result = mysql_query($query) or pupe_error($query);
          //echo "$query<br>";
        }
      }

      if (count($bosh) > 1) {
        for ($i=1; $i < count($bosh); $i++) {
          $query = "INSERT INTO vertailu_korvaavat SET tukkuri='bosh', tuote1='$bosh[0]', tuote2='$bosh[$i]', yhtio='$kukarow[yhtio]'";
          $result = mysql_query($query) or pupe_error($query);
          //echo "$query<br>";
        }
      }

      if (count($kaha) > 1) {
        for ($i=1; $i < count($kaha); $i++) {
          $query = "INSERT INTO vertailu_korvaavat SET tukkuri='kaha', tuote1='$kaha[0]', tuote2='$kaha[$i]', yhtio='$kukarow[yhtio]'";
          $result = mysql_query($query) or pupe_error($query);
          //echo "$query<br>";
        }
      }

      if (count($sn) > 1) {
        for ($i=1; $i < count($sn); $i++) {
          $query = "INSERT INTO vertailu_korvaavat SET tukkuri='sn', tuote1='$sn[0]', tuote2='$sn[$i]', yhtio='$kukarow[yhtio]'";
          $result = mysql_query($query) or pupe_error($query);
          //echo "$query<br>";
        }
      }

      if (count($orum) > 1) {
        for ($i=1; $i < count($orum); $i++) {
          $query = "INSERT INTO vertailu_korvaavat SET tukkuri='orum', tuote1='$orum[0]', tuote2='$orum[$i]', yhtio='$kukarow[yhtio]'";
          $result = mysql_query($query) or pupe_error($query);
          //echo "$query<br>";
        }
      }

      if (count($hl) > 1) {
        for ($i=1; $i < count($hl); $i++) {
          $query = "INSERT INTO vertailu_korvaavat SET tukkuri='hl', tuote1='$hl[0]', tuote2='$hl[$i]', yhtio='$kukarow[yhtio]'";
          $result = mysql_query($query) or pupe_error($query);
          //echo "$query<br>";
        }
      }

      if (count($atoy) > 1) {
        for ($i=1; $i < count($atoy); $i++) {
          $query = "INSERT INTO vertailu_korvaavat SET tukkuri='atoy', tuote1='$atoy[0]', tuote2='$atoy[$i]', yhtio='$kukarow[yhtio]'";
          $result = mysql_query($query) or pupe_error($query);
          //echo "$query<br>";
        }
      }

      if (count($koivunen) > 1) {
        for ($i=1; $i < count($koivunen); $i++) {
          $query = "INSERT INTO vertailu_korvaavat SET tukkuri='koivunen', tuote1='$koivunen[0]', tuote2='$koivunen[$i]', yhtio='$kukarow[yhtio]'";
          $result = mysql_query($query) or pupe_error($query);
          //echo "$query<br>";
        }
      }

      if (count($motoral) > 1) {
        for ($i=1; $i < count($motoral); $i++) {
          $query = "INSERT INTO vertailu_korvaavat SET tukkuri='motoral', tuote1='$motoral[0]', tuote2='$motoral[$i]', yhtio='$kukarow[yhtio]'";
          $result = mysql_query($query) or pupe_error($query);
          //echo "$query<br>";
        }
      }

      $arwidson = array();
      $bosh     = array();
      $kaha     = array();
      $sn       = array();
      $orum     = array();
      $hl       = array();
      $atoy     = array();
      $koivunen = array();
      $motoral  = array();
    }

    // luetaan seuraava rivi failista
    $rivi = fgets($file);

  } // end while eof

  $query = "INSERT INTO vertailu SET
            arwidson = '$arwidson[0]',
            bosh     = '$bosh[0]',
            kaha     = '$kaha[0]',
            sn       = '$sn[0]',
            orum     = '$orum[0]',
            hl       = '$hl[0]',
            atoy     = '$atoy[0]',
            koivunen = '$koivunen[0]',
            motoral  = '$motoral[0]',
            yhtio    = '$kukarow[yhtio]'";
  $result = mysql_query($query) or pupe_error($query);

  $insert++;
  //echo "$query<br>";

  // lis‰ill‰‰n korvaavat tuotteet...
  if (count($arwidson) > 1) {
    for ($i=1; $i<count($arwidson); $i++) {
      $query = "INSERT INTO vertailu_korvaavat SET tukkuri='arwidson', tuote1='$arwidson[0]', tuote2='$arwidson[$i]', yhtio='$kukarow[yhtio]'";
      $result = mysql_query($query) or pupe_error($query);
      //echo "$query<br>";
    }
  }

  if (count($bosh) > 1) {
    for ($i=1; $i<count($bosh); $i++) {
      $query = "INSERT INTO vertailu_korvaavat SET tukkuri='bosh', tuote1='$bosh[0]', tuote2='$bosh[$i]', yhtio='$kukarow[yhtio]'";
      $result = mysql_query($query) or pupe_error($query);
      //echo "$query<br>";
    }
  }

  if (count($kaha) > 1) {
    for ($i=1; $i < count($kaha); $i++) {
      $query = "INSERT INTO vertailu_korvaavat SET tukkuri='kaha', tuote1='$kaha[0]', tuote2='$kaha[$i]', yhtio='$kukarow[yhtio]'";
      $result = mysql_query($query) or pupe_error($query);
      //echo "$query<br>";
    }
  }

  if (count($sn) > 1) {
    for ($i=1; $i < count($sn); $i++) {
      $query = "INSERT INTO vertailu_korvaavat SET tukkuri='sn', tuote1='$sn[0]', tuote2='$sn[$i]', yhtio='$kukarow[yhtio]'";
      $result = mysql_query($query) or pupe_error($query);
      //echo "$query<br>";
    }
  }

  if (count($orum) > 1) {
    for ($i=1; $i < count($orum); $i++) {
      $query = "INSERT INTO vertailu_korvaavat SET tukkuri='orum', tuote1='$orum[0]', tuote2='$orum[$i]', yhtio='$kukarow[yhtio]'";
      $result = mysql_query($query) or pupe_error($query);
      //echo "$query<br>";
    }
  }

  if (count($hl) > 1) {
    for ($i=1; $i < count($hl); $i++) {
      $query = "INSERT INTO vertailu_korvaavat SET tukkuri='hl', tuote1='$hl[0]', tuote2='$hl[$i]', yhtio='$kukarow[yhtio]'";
      $result = mysql_query($query) or pupe_error($query);
      //echo "$query<br>";
    }
  }

  if (count($atoy) > 1) {
    for ($i=1; $i < count($atoy); $i++) {
      $query = "INSERT INTO vertailu_korvaavat SET tukkuri='atoy', tuote1='$atoy[0]', tuote2='$atoy[$i]', yhtio='$kukarow[yhtio]'";
      $result = mysql_query($query) or pupe_error($query);
      //echo "$query<br>";
    }
  }

  if (count($koivunen) > 1) {
    for ($i=1; $i < count($koivunen); $i++) {
      $query = "INSERT INTO vertailu_korvaavat SET tukkuri='koivunen', tuote1='$koivunen[0]', tuote2='$koivunen[$i]', yhtio='$kukarow[yhtio]'";
      $result = mysql_query($query) or pupe_error($query);
      //echo "$query<br>";
    }
  }

  if (count($motoral) > 1) {
    for ($i=1; $i < count($motoral); $i++) {
      $query = "INSERT INTO vertailu_korvaavat SET tukkuri='motoral', tuote1='$motoral[0]', tuote2='$motoral[$i]', yhtio='$kukarow[yhtio]'";
      $result = mysql_query($query) or pupe_error($query);
      //echo "$query<br>";
    }
  }

  $arwidson = array();
  $bosh     = array();
  $kaha     = array();
  $sn       = array();
  $orum     = array();
  $hl       = array();
  $atoy     = array();
  $koivunen = array();
  $motoral  = array();

  fclose($file);

  echo "Lis‰ttiin $insert vertailua.</font>";

}
else {
  echo "<form method='post' name='sendfile' enctype='multipart/form-data'>
      <table>
      <tr><th>Valitse tiedosto:</th>
        <td><input name='userfile' type='file'></td>
        <td class='back'><input type='submit' value='L‰het‰'></td>
      </tr>
      </table>
      </form>";
}

require "inc/footer.inc"
