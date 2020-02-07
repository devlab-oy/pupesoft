<?php

require "inc/parametrit.inc";

echo "<font class='head'>", t("Malliselain sis��nlue dataa"), "</font><hr>";

if (is_uploaded_file($_FILES['userfile']['tmp_name']) == TRUE) {

  list($name, $ext) = split("\.", $_FILES['userfile']['name']);

  if (strtoupper($ext) !="TXT" and strtoupper($ext)!="CSV") {
    die ("<font class='error'><br>".t("Ainoastaan .txt ja .cvs tiedostot sallittuja")."!</font>");
  }

  if ($_FILES['userfile']['size']==0) {
    die ("<font class='error'><br>".t("Tiedosto on tyhj�")."!</font>");
  }

  $file = fopen($_FILES['userfile']['tmp_name'], "r") or die (t("Tiedoston avaus ep�onnistui")."!");

  echo "<font class='message'>".t("Tutkaillaan mit� olet l�hett�nyt").".<br></font>";

  // luetaan eka rivi tiedostosta..
  $rivi    = fgets($file);
  $otsikot = explode("\t", strtoupper(trim($rivi)));

  // haetaan valitun taulun sarakkeet
  $query = "SHOW COLUMNS FROM $table";
  $fres  = mysql_query($query) or pupe_error($query);

  while ($row=mysql_fetch_array($fres)) {
    //pushataan arrayseen kaikki sarakenimet ja tietuetyypit
    $trows[] = strtoupper($row[0]);
    $ttype[] = $row[1];
  }

  // $trows     sis�lt�� kaikki taulun sarakkeet tietokannasta
  // $otsikot   sis�lt�� kaikki sarakkeet saadusta tiedostosta

  $postoiminto = 'X';
  $vikaa = 0;

  foreach ($otsikot as $column) {

    $column = strtoupper(trim($column));

    if ($column != '') {

      //laitetaan kaikki paitsi valintasarake talteen.
      if ($column != "TOIMINTO") {
        if (!in_array($column, $trows)) {
          echo "<br><font class='message'>".t("Saraketta")." \"<b>".strtoupper($column)."</b>\" ".t("ei l�ydy")."!</font>";
          $vikaa++;
        }
      }
      else {
        //TOIMINTO sarakkeen positio tiedostossa
        $postoiminto = (string) array_search($column, $otsikot);
      }

      // yhtio ja tunnus kentti� ei saa koskaan muokata...
      if ($column == 'YHTIO') {
        echo "<br><font class='message'>yhti�t� ei saa muuttaa!</font><br>";
        $vikaa++;
      }

    }

  }

  // oli vikaa
  if ($vikaa > 0 or $postoiminto == 'X') {
    fclose($file); // suljetaan avattu faili.. kiltti�!
    die ("teit virheen, ei jatketa"); // ja kuollaan pois
  }

  echo "<br>", t("Tiedosto ok, aloitetaan p�ivitys"), "...<br><br>";
  flush();

  // luetaan tiedosto loppuun...
  $rivi = fgets($file);

  while (!feof($file)) {

    $rivi = explode("\t", trim($rivi));

    $infot = "";
    $infot2= "";
    $where = "";
    $type  = "";

    foreach ($rivi as $i => $sarake) {

      if ($otsikot[$i] == 'TOIMINTO') {
        if (strtoupper($sarake) == "MUUTA") {
          $type = "update $table set ";
        }
        if (strtoupper($sarake) == "LISAA") {
          $type = "insert into $table set ";
        }
        if (strtoupper($sarake) == "POISTA") {
          $type = "delete from $table ";
          $infot .= " xxx "; // sekotetaan pakkaa
        }
      }
      elseif (($otsikot[$i] == 'ATUNNUS' and $table == "yhteensopivuus_tuote") or
        ($otsikot[$i] == 'TUOTENO' and $table == "yhteensopivuus_tuote")) {
        $where .= " $otsikot[$i] = '$sarake' and ";

        if ($table == "yhteensopivuus_tuote") {
          $infot2 .= "$otsikot[$i] = '$sarake', ";
        }
      }
      elseif ($otsikot[$i] != "TUNNUS") {
        $infot .= "$otsikot[$i] = '$sarake', ";
      }

    }

    if ($type != "" and $infot != "" and $where != "") {

      if ($table == "yhteensopivuus_tuote") {
        $infot .= $infot2;
      }

      $infot = substr($infot, 0, -2);
      $query = "";

      // insertit
      if (substr($type, 0, 1) == "i") {
        $query = "$type $infot , yhtio='$kukarow[yhtio]', laatija='$kukarow[kuka]', luontiaika=now(), muutospvm=now(), muuttaja='$kukarow[kuka]' ";
      }

      // updatet
      if (substr($type, 0, 1) == "u") {
        $query = "$type $infot , muutospvm=now(), muuttaja='$kukarow[kuka]' where $where yhtio='$kukarow[yhtio]'";
      }

      // poistot
      if (substr($type, 0, 1) == "d") {
        $query = "$type where $where yhtio='$kukarow[yhtio]'";
      }

      if ($query != "") {
        query_dump($query);
        if (!mysql_query($query)) echo "Tietue l�ytyy jo kannasta tai muu virhe: ".mysql_error()."<br>";
      }

    }
    else {
      echo "Riviss� oli ongelmia, ei p�ivitetty $type $infot $where<br><br>";
    }

    // luetaan seuraava rivi failista
    $rivi = fgets($file);

  } // end while eof

  fclose($file);

  echo "<br>", t("p�ivitys ok"), "";

}
else {

  echo "<form method='post' name='sendfile' enctype='multipart/form-data'>
      <table>

      <tr>
        <td>", t("Valitse tietokannan taulu"), ":</td>
        <td><select name='table'>
          <option value='yhteensopivuus_tuote'>", t("Tuoteyhteensopivuudet"), "</option>
          </select></td>
      </tr>

      <tr><td>", t("Valitse tiedosto"), ":</td>
        <td><input name='userfile' type='file'></td>
        <td class='back'><input type='submit' value='".t("L�het�")."'></td>
      </tr>

      </table>
      </form>";
}

require "inc/footer.inc";
