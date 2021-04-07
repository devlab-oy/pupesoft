<?php

ini_set("memory_limit", "5G");
ini_set("post_max_size", "100M");
ini_set("upload_max_filesize", "100M");
ini_set("mysql.connect_timeout", 600);
ini_set("max_execution_time", 18000);

require "inc/parametrit.inc";

echo "<font class='head'>".t("TecDoc-tietojen sis‰‰nluku")."</font><hr>";

if ($oikeurow['paivitys'] != '1') { // Saako p‰ivitt‰‰
  if ($uusi == 1) {
    echo "<b>", t("Sinulla ei ole oikeutta lis‰t‰ t‰t‰ tietoa"), "</b><br>";
    $uusi = '';
  }
  if ($del == 1) {
    echo "<b>", t("Sinulla ei ole oikeutta poistaa t‰t‰ tietoa"), "</b><br>";
    $del = '';
    $tunnus = 0;
  }
  if ($upd == 1) {
    echo "<b>", t("Sinulla ei ole oikeutta muuttaa t‰t‰ tietoa"), "</b><br>";
    $upd = '';
    $uusi = 0;
    $tunnus = 0;
  }
}

flush();
$kasitellaan_tiedosto = FALSE;

if (isset($_FILES['userfile']) and is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {

  $kasitellaan_tiedosto = TRUE;

  if ($_FILES['userfile']['size'] == 0) {
    echo "<font class='error'><br>".t("Tiedosto on tyhj‰")."!</font>";
    $kasitellaan_tiedosto = FALSE;
  }

  $path_parts = pathinfo($_FILES['userfile']['name']);
  $ext = strtoupper($path_parts['extension']);

  echo "<font class='message'>".t("Tarkastetaan l‰hetetty tiedosto")."...<br><br></font>";

  $retval = tarkasta_liite("userfile", array("XLSX", "XLS", "ODS", "SLK", "XML", "GNUMERIC", "CSV", "TXT", "DATAIMPORT"));

  if ($retval !== TRUE) {
    echo "<font class='error'><br>".t("V‰‰r‰ tiedostomuoto")."!</font>";
    $kasitellaan_tiedosto = FALSE;
  }
}

if ($kasitellaan_tiedosto) {

  /**
   * K‰sitelt‰v‰n filen nimi *
   */
  $kasiteltava_tiedoto_path = $_FILES['userfile']['tmp_name'];

  $excelrivit = pupeFileReader($kasiteltava_tiedoto_path, $ext);

  /**
   * Otetaan tiedoston otsikkorivi *
   */
  $headers = $excelrivit[0];
  $headers = array_map('trim', $headers);
  $headers = array_map('strtoupper', $headers);

  // Unsetataan tyhj‰t sarakkeet
  for ($i = (count($headers)-1); $i > 0 ; $i--) {
    if ($headers[$i] != "") {
      break;
    }
    else {
      unset($headers[$i]);
    }
  }

  // haetaan valitun taulun sarakkeet
  $query = "SHOW KEYS FROM $table WHERE Key_name = 'PRIMARY'";
  $fres  = pupe_query($query);
  $row = mysql_fetch_assoc($fres);
  $primary_key = $row['Column_name'];
  $primary_key_ind = 0;

  // haetaan valitun taulun sarakkeet
  $query = "SHOW COLUMNS FROM $table";
  $fres  = pupe_query($query);

  while ($row = mysql_fetch_array($fres)) {
    //pushataan arrayseen kaikki sarakenimet ja tietuetyypit
    $trows[] = strtoupper($row[0]);
    $ttype[] = $row[1];
  }

  // $trows sis‰lt‰‰ kaikki taulun sarakkeet tietokannasta
  // $headers sis‰lt‰‰ kaikki sarakkeet saadusta tiedostosta
  $saraketsek = array_flip($trows);
  $keyind = 0;

  foreach ($headers as $column) {
    $column = strtoupper(trim($column));

    if ($column != '') {

      if ($column == strtoupper($primary_key)) {
        $primary_key_ind = $keyind;
      }

      if (!in_array($column, $trows)) {
        echo "<br><font class='message'>", t("Saraketta"), " \"<b>", strtoupper($column), "</b>\" ", t("ei lˆydy"), " $table-taulusta!</font>";
        $vikaa++;
      }

      // tarkistetaan, ett‰ kaikki sarakkeet on tiedostossa
      if (isset($saraketsek[$column])) {
        unset($saraketsek[$column]);
      }
      $keyind++;
    }
  }

  if (empty($primary_key)) {
    die("<br><br><font class='error'>".t("VIRHE: Taulun avain puuttuu")."!<br></font>");
  }

  // oli virheellisi‰ sarakkeita tai pakollisia ei lˆytynyt..
  if ($vikaa != 0 or !empty($saraketsek)) {
    die("<br><br><font class='error'>".t("VIRHE: Sarakkeita puuttuu! Ei voida jatkaa")."!<br></font>");
  }

  echo "<font class='message'>", t("Tiedosto ok, aloitellaan p‰ivitys"), "...<br><br></font>";
  flush();

  // rivim‰‰r‰ exceliss‰
  $excelrivimaara = count($excelrivit);

  // sarakem‰‰r‰ exceliss‰
  $excelsarakemaara = count($headers);

  // Luetaan tiedosto loppuun ja tehd‰‰n taulukohtainen array koko datasta, t‰ss‰ kohtaa putsataan jokaisen solun sis‰ltˆ pupesoft_cleanstring -funktiolla
  for ($excei = 1; $excei < $excelrivimaara; $excei++) {
    for ($excej = 0; $excej < $excelsarakemaara; $excej++) {
      $taulunrivit[$excei-1][] = pupesoft_cleanstring($excelrivit[$excei][$excej]);
    }
  }

  /*
  echo "<table>";
  echo "<tr>";

  foreach ($headers as $key => $column) {
    echo "<th>$key => $column</th>";
  }

  echo "</tr>";

  foreach ($taulunrivit as $rivi) {
    echo "<tr>";

    for ($eriviindex = 0; $eriviindex < count($rivi); $eriviindex++) {
      echo "<td>$eriviindex => $rivi[$eriviindex]</td>";
    }
  }

  echo "</table><br>";
  */

  $inslask = 0;
  $updlask = 0;

  // luetaan tiedosto l‰pi...
  foreach ($taulunrivit as $rivinumero => $rivi) {
    $query = "SELECT {$primary_key}
              FROM {$table}
              WHERE {$primary_key} = '{$rivi[$primary_key_ind]}'";
    $selres = mysql_query($query) or pupe_error($query);

    $insupd = "";
    for ($j = 0; $j < count($rivi); $j++) {
      $insupd .= "{$headers[$j]} = '{$rivi[$j]}', ";
    }

    $insupd = substr($insupd, 0, -2);

    if (mysql_num_rows($selres)) {
      // p‰ivitet‰‰n
      $query = "UPDATE {$table}
                SET {$insupd}
                WHERE {$primary_key} = '{$rivi[$primary_key_ind]}'";
      mysql_query($query);
      $updlask++;
    }
    else {
      // luodaan uusi
      $query = "INSERT INTO {$table}
                SET {$insupd}";
      mysql_query($query);
      $inslask++;
    }
  }

  echo t("P‰ivitettiin"), " $updlask -  $table ", t("tietuetta"), "!<br>";
  echo t("Lis‰ttiin"), " $inslask -  $table ", t("tietuetta"), "!<br>";
}

echo "<br><br>
    <form method='post' name='sendfile' enctype='multipart/form-data'>
    <table>
    <tr>
      <th>", t("Valitse tietokannan taulu"), ":</th>
      <td><select name='table'>
        <option value='td_eng'>td_eng</option>
        <option value='td_manu'>td_manu</option>
        <option value='td_model'>td_model</option>
        <option value='td_pc'>td_pc</option>
        <option value='td_pc_add'>td_pc_add</option>
        <option value='td_pc_eng'>td_pc_eng</option>
      </select></td>
    </tr>

    <input type='hidden' name='tee' value='file'>

    <tr><th>", t("Valitse tiedosto"), ":</th>
      <td><input name='userfile' type='file'></td>
      <td class='back'><input type='submit' value='", t("lue tiedot"), "'></td>
    </tr>

    </table>
    </form>";


require "inc/footer.inc";
