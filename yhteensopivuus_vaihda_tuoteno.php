<?php

require "inc/parametrit.inc";

if ($toim == "MP") {
  $tyyppi = "MP";
}
elseif ($toim == "MO") {
  $tyyppi = "MO";
}
elseif ($toim == "MK") {
  $tyyppi = "MK";
}
elseif ($toim == "MX") {
  $tyyppi = "MX";
}
elseif ($toim == "AT") {
  $tyyppi = "AT";
}
else {
  $tyyppi = "HA";
}

echo "<font class='head'>".t("Yhteensopivuuksien tuotenumeroitten vaihto")."</font><hr>";

$error = "X";

if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE and $tee == "file") {

  $error = 0;

  //Tuotenumerot tulevat tiedostosta
  $path_parts = pathinfo($_FILES['userfile']['name']);
  $name  = strtoupper($path_parts['filename']);
  $ext  = strtoupper($path_parts['extension']);

  if ($ext != "TXT" and $ext != "CSV") {
    die ("<font class='error'><br>".t("Ainoastaan .txt ja .cvs tiedostot sallittuja")."!</font>");
  }

  if ($_FILES['userfile']['size']==0) {
    die ("<font class='error'><br>".t("Tiedosto on tyhjä")."!</font>");
  }

  $file = fopen($_FILES['userfile']['tmp_name'], "r") or die (t("Tiedoston avaus epäonnistui")."!");

  echo "<font class='message'>".t("Tutkaillaan mitä olet lähettänyt").".<br></font>";
  flush();

  while (!feof($file)) {

    // luetaan rivi tiedostosta..
    $poista    = array("'", "\\", "\"");
    $rivi    = str_replace($poista, "", $rivi);
    $rivi    = explode("\t", $rivi);

    if (trim($rivi[0]) != '' and trim($rivi[1]) != '') {

      $vantuoteno = strtoupper(trim($rivi[0]));
      $uustuoteno = strtoupper(trim($rivi[1]));

      $query  = "SELECT tunnus from tuote where yhtio = '$kukarow[yhtio]' and tuoteno = '$vantuoteno'";
      $tuoteresult = mysql_query($query) or pupe_error($query);
      if (mysql_num_rows($tuoteresult) != 1) {
        $error++;
        echo "<font class='message'>".t("VANHAA TUOTENUMEROA EI LÖYDY").": $vantuoteno</font><br>";
      }

      $query  = "SELECT tunnus from tuote where yhtio = '$kukarow[yhtio]' and tuoteno = '$uustuoteno'";
      $tuoteuresult = mysql_query($query) or pupe_error($query);
      if (mysql_num_rows($tuoteuresult) != 1) {
        $error++;
        echo "<font class='message'>".t("UUSI TUOTENUMERO EI LÖYDY").": $uustuoteno</font><br>";
      }

    }
    else {
      if (trim($rivi[0]) == '' and trim($rivi[1]) != '') {
        $error++;
        echo "<font class='message'>".t("Vanha tuotenumero puuttuu tiedostosta").": (tyhjä) --> $rivi[1]</font><br>";
      }
      elseif (trim($rivi[1]) == '' and trim($rivi[0]) != '') {
        $error++;
        echo "<font class='message'>".t("Uusi tuotenumero puuttuu tiedostosta").": $rivi[0] --> (tyhjä)</font><br>";
      }
    }
    $rivi = fgets($file, 4096);
  }

  $failista = "JOO";
  fclose($file);
}
elseif (is_uploaded_file($_FILES['userfile']['tmp_name']) !== TRUE and $tee == "file") {

  $error = 0;

  //Tuotenumerot tulevat käyttöliittymästä
  $query  = "SELECT tunnus from tuote where yhtio = '$kukarow[yhtio]' and tuoteno = '$vantuoteno'";
  $tuoteresult = mysql_query($query) or pupe_error($query);
  if (mysql_num_rows($tuoteresult) != 1) {
    $error++;
    echo "<font class='message'>".t("VANHAA TUOTENUMEROA EI LÖYDY").": $vantuoteno</font><br>";
  }

  $query  = "SELECT tunnus from tuote where yhtio = '$kukarow[yhtio]' and tuoteno = '$uustuoteno'";
  $tuoteuresult = mysql_query($query) or pupe_error($query);
  if (mysql_num_rows($tuoteuresult) != 1) {
    $error++;
    echo "<font class='message'>".t("UUSI TUOTENUMERO EI LÖYDY").": $uustuoteno</font><br>";
  }

  $failista   = "EI";
}

if ($error == 0 and $tee == "file") {

  echo "<font class='message'>".t("Syötetyt tiedot ovat ok")."</font><br><br>";
  flush();

  if ($failista == "JOO") {
    $file = fopen($_FILES['userfile']['tmp_name'], "r") or die (t("Tiedoston avaus epäonnistui")."!");
    $rivi = fgets($file, 4096);
    $lask_kaks = 0;
  }
  else {
    $rivi = "$vantuoteno\t$uustuoteno";
    $lask_kaks = 0;
  }

  while (!@feof($file) and $lask_kaks == 0) {
    // luetaan rivi tiedostosta..
    $poista    = array("'", "\\", "\"");
    $rivi    = str_replace($poista, "", $rivi);
    $rivi    = explode("\t", trim($rivi));

    if (trim($rivi[0]) != '' and trim($rivi[1]) != '') {

      $vantuoteno = strtoupper(trim($rivi[0]));
      $uustuoteno = strtoupper(trim($rivi[1]));

      $query = "SELECT *
                FROM yhteensopivuus_tuote
                where yhtio = '$kukarow[yhtio]'
                and tuoteno = '$vantuoteno'
                and tyyppi  = '$tyyppi'
                order by tunnus";
      $vanres = mysql_query($query) or pupe_error($query);

      while ($vanrow = mysql_fetch_array($vanres)) {
        $query = "UPDATE yhteensopivuus_tuote
                  SET tuoteno = '$uustuoteno'
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tyyppi  = '$tyyppi'
                  and atunnus = '$vanrow[atunnus]'
                  and tuoteno = '$vantuoteno'";

        if ($result = mysql_query($query)) {
          echo "<font class='message'>".t("Päivitettiin yhteensopivuudet")." ".t("atunnus").": $vanrow[atunnus], $vantuoteno --> $uustuoteno<br></font>";
        }
        else {
          echo "<font class='message'>".t("Uusi tuote löytyy jo ajoneuvosta")." ".t("atunnus").": $vanrow[atunnus], $uustuoteno<br></font>";
          echo "<font class='message'>".t("Poistetaan vanha tuote ajoneuvosta")." ".t("atunnus").": $vanrow[atunnus], $vantuoteno<br></font>";

          $query = "DELETE from yhteensopivuus_tuote
                    WHERE yhtio = '$kukarow[yhtio]'
                    and tyyppi  = '$tyyppi'
                    and atunnus = '$vanrow[atunnus]'
                    and tuoteno = '$vantuoteno'";
          $result = mysql_query($query) or pupe_error($query);
        }
      }
    }

    if ($failista == "JOO") {
      $rivi = fgets($file, 4096);
    }
    else {
      $lask_kaks++;
    }
  }
}

$formi  = 'performi';
$kentta = 'vantuoteno';


echo  "<br><font class='message'>".t("Tiedostomuoto").":</font><br>
      <table>
      <tr><th colspan='2'>".t("Tabulaattorilla eroteltu tekstitiedosto").".</th></tr>
      <tr><td>".t("VANHA tuotenumero")."</td><td>".t("UUSI tuotenumero")."</td></tr>";

echo "<form method='post' name='$formi' enctype='multipart/form-data'>
      <input type='hidden' name='tee' value='file'>
      <input type='hidden' name='toim' value='$toim'>
      <tr>
        <th>".t("Valitse tiedosto").":</th>
        <td><input name='userfile' type='file'></td>
      </tr>
      <tr>
        <td class='back'><br></td>
      </tr>
        <th colspan='2'>".t("Tai syöta tuotenumerot").":</th>
      </tr>

      <tr>
        <th>".t("Vanha tuotenumero").":</th>
        <td><input type='text' name='vantuoteno' size='22' maxlength='20' value='$vantuoteno'></td>
      </tr>
      <tr>
        <th>".t("Uusi tuotenumero").":</th>
        <td><input type='text' name='uustuoteno' size='22' maxlength='20' value='$uustuoteno'></td>
      </tr>
      </table>
      <br>

      <input type='submit' value='".t("Vaihda tuotenumero")."'>
      </form>";

require "inc/footer.inc";
