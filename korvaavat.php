<?php

require ("inc/parametrit.inc");

echo "<font class='head'>".t("Korvaavien ylläpito")."</font><hr>";

echo "<form action='korvaavat.php' method='post' name='etsituote' autocomplete='off'>
    <input type='hidden' value='$lopetus' name='lopetus'>
    ".t("Etsi tuotetta")." <input type='text' name='tuoteno'>
    <input type='submit' value='".t("Hae")."'>
    </form><br><br>";

if ($tee == 'del') {
  //haetaan poistettavan tuotteen id.. käyttäjästävällistä..
  $query  = "SELECT * FROM korvaavat WHERE tunnus = '$tunnus' AND yhtio = '$kukarow[yhtio]'";
  $result = pupe_query($query);
  $row    = mysql_fetch_array($result);
  $id    = $row['id'];

  //poistetaan korvaava..
  $query  = "DELETE FROM korvaavat WHERE tunnus = '$tunnus' AND yhtio = '$kukarow[yhtio]'";
  $result = pupe_query($query);

  //näytetään silti loput.. kilttiä.
  $query  = "SELECT * FROM korvaavat WHERE id = '$id' AND yhtio = '$kukarow[yhtio]'";
  $result = pupe_query($query);
  $row    = mysql_fetch_array($result);
  $tuoteno = $row['tuoteno'];
}

if ($tee == 'muutaprio') {
  //haetaan poistettavan tuotteen id.. käyttäjästävällistä..
  $query  = "SELECT * FROM korvaavat WHERE tunnus = '$tunnus' AND yhtio = '$kukarow[yhtio]'";
  $result = pupe_query($query);
  $row    = mysql_fetch_array($result);
  $id    = $row['id'];

  if ($prio != 0) {
    # Siirretään ketjun muita eteenpäin, jarjestys + 1
    $query = "UPDATE korvaavat SET jarjestys=jarjestys+1, muuttaja='{$kukarow['kuka']}', muutospvm=now()
              WHERE jarjestys!=0 AND id='$id' AND yhtio='{$kukarow['yhtio']}' AND tunnus!=$tunnus AND jarjestys >= $prio";
    $result = pupe_query($query);
  }

  //muutetaan prio..
  $query  = "UPDATE korvaavat SET
             jarjestys    = '$prio',
             muutospvm    = now(),
             muuttaja     = '$kukarow[kuka]'
             WHERE tunnus = '$tunnus' AND yhtio = '$kukarow[yhtio]'";
  $result = pupe_query($query);

  // Haetaan koko ketju
  $query = "SELECT * FROM korvaavat WHERE yhtio='{$kukarow['yhtio']}' AND id='$id' AND jarjestys!=0 ORDER BY jarjestys";
  $result = pupe_query($query);

  while($tuote = mysql_fetch_assoc($result)) {
      // Aloitetaan pienimmästä ei nollasta (useimmiten 1)
      // Pienimpään ei kosketa ja muut järkätään siitä eteenpäin
      if (!isset($edellinen)) {
          $edellinen = $tuote['jarjestys'];
          continue;
      }

      // Päivitetään järjestykseksi edellinen+1
      $uusi_jarjestys = ($edellinen + 1);

      // Ei päivitetä turhaan
      if ($tuote['jarjestys'] <> $uusi_jarjestys) {
          $query = "UPDATE korvaavat
                    SET jarjestys = $uusi_jarjestys, muutospvm = now()
                    WHERE yhtio='{$kukarow['yhtio']}' AND tunnus={$tuote['tunnus']}";
          if( ! pupe_query($query) ) {
              exit("Virhe ketjujen uudelleenjärjestelyssä");
          }
      }

      $edellinen = $uusi_jarjestys;
  }

  //näytetään silti loput.. kilttiä.
  $query  = "SELECT * FROM korvaavat WHERE id = '$id' AND yhtio = '$kukarow[yhtio]'";
  $result = pupe_query($query);
  $row    = mysql_fetch_array($result);
  $tuoteno= $row['tuoteno'];
}

if ($tee == 'add') {
  // tutkitaan onko lisättävä tuote oikea tuote...
  $query  = "SELECT * FROM tuote WHERE tuoteno = '$korvaava' AND yhtio = '$kukarow[yhtio]'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    echo "<font class='error'>".t("Lisäys ei onnistu! Tuotetta")." $korvaava ".t("ei löydy")."!</font><br><br>";
  }
  else {
    $query  = "SELECT * FROM korvaavat WHERE tuoteno = '$tuoteno' AND yhtio = '$kukarow[yhtio]'";
    $result = pupe_query($query);

    // katotaan onko isä tuote lisättty..
    if (mysql_num_rows($result) != 0) {
      //jos on, otetaan ID luku talteen...
      $row    = mysql_fetch_array($result);
      $fid  = $row['id'];
    }

    //katotaan onko korvaava jo lisätty
    $query  = "SELECT * FROM korvaavat WHERE tuoteno = '$korvaava' AND yhtio = '$kukarow[yhtio]'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) != 0) {
      //korvaava on jo lisätty.. otetaan senki id..
      $row    = mysql_fetch_array($result);
      $cid  = $row['id'];
    }

    //jos kumpaakaan ei löytynyt...
    if (($cid == "") and ($fid == "")) {
      //silloin tämä on eka korvaava.. etsitään sopiva ID.
      $query  = "SELECT max(id) FROM korvaavat";
      $result = pupe_query($query);
      $row    = mysql_fetch_array($result);
      $id   = $row[0]+1;

      //lisätään "isä tuote"...
      $query  = "INSERT INTO korvaavat (id, tuoteno, yhtio, laatija, luontiaika, muutospvm, muuttaja)
                 VALUES ('$id', '$tuoteno', '$kukarow[yhtio]', '$kukarow[kuka]', now(), now(), '$kukarow[kuka]')";
      $result = pupe_query($query);

      // lisätään korvaava tuote...
      $query  = "INSERT INTO korvaavat (id, tuoteno, yhtio, laatija, luontiaika, muutospvm, muuttaja)
                 VALUES ('$id', '$korvaava', '$kukarow[yhtio]', '$kukarow[kuka]', now(), now(), '$kukarow[kuka]')";
      $result = pupe_query($query);
    }

    //lapsi on löytynyt, isää ei
    if (($cid != "") and ($fid == "")) {
      //lisätään "isä tuote"...
      $query  = "INSERT INTO korvaavat (id, tuoteno, yhtio, laatija, luontiaika, muutospvm, muuttaja)
                 VALUES ('$cid', '$tuoteno', '$kukarow[yhtio]', '$kukarow[kuka]', now(), now(), '$kukarow[kuka]')";
      $result = pupe_query($query);
    }

    //isä on löytynyt, lapsi ei
    if (($fid != "") and ($cid == "")) {
      # Siirretään ketjun muita eteenpäin jarjestys + 1
      $query = "UPDATE korvaavat SET jarjestys=jarjestys+1
                WHERE jarjestys!=0 AND id='$fid' AND yhtio='{$kukarow['yhtio']}'";
      $result = pupe_query($query);

      # Lisätään uusi aina päätuotteeksi jarjestys=1
      //lisätään korvaava päätuotteeksi
      $query  = "INSERT INTO korvaavat (id, tuoteno, yhtio, jarjestys, laatija, luontiaika, muutospvm, muuttaja)
                 VALUES ('$fid', '$korvaava', '$kukarow[yhtio]', '1', '$kukarow[kuka]', now(), now(), '$kukarow[kuka]')";
      $result = pupe_query($query);
    }

    //kummatkin löytyivät.. ja ne korvaa toisensa
    if ($fid != "" and $cid != "" and $fid == $cid) {
      echo "<font class='error'>".t("Tuotteet")." $korvaava <> $tuoteno ".t("korvaavat jo toisensa")."!</font><br><br>";
    }
    elseif ($fid != "" and $cid != "" ) {
      echo "<font class='error'>".t("Tuotteet")." $korvaava, $tuoteno ".t("kuuluvat jo eri korvaavuusketjuihin")."!</font><br><br>";
    }
  }
}

if ($tee == 'korvaa_vastaava') {

  $query  = "SELECT * FROM korvaavat WHERE tunnus = '$tunnus' AND yhtio = '$kukarow[yhtio]'";
  $result = pupe_query($query);
  $row    = mysql_fetch_array($result);
  $id    = $row['id'];

  $query = "UPDATE vastaavat SET tuoteno='$korvaava' WHERE yhtio='{$kukarow['yhtio']}' and tuoteno='$korvattava'";
  $result = pupe_query($query);

  $query  = "SELECT * FROM korvaavat WHERE id = '$id' AND yhtio = '$kukarow[yhtio]'";
  $result = pupe_query($query);
  $row    = mysql_fetch_array($result);
  $tuoteno = $row['tuoteno'];
}

if ($tuoteno != '') {
  $query  = "SELECT * FROM korvaavat WHERE tuoteno = '$tuoteno' AND yhtio = '$kukarow[yhtio]'";
  $result = pupe_query($query);

  echo "<font class='head'>".t("Tuotenumero").": $tuoteno</font><hr>";

  if (mysql_num_rows($result) == 0) {
    $query = "SELECT * FROM tuote WHERE tuoteno = '$tuoteno' AND yhtio = '$kukarow[yhtio]'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 0) {
      echo "<br><font class='error'>".t("Tuotenumeroa")." $tuoteno ".t("ei ole perustettu")."!</font><br>";
      $ok=1;
    }
    else {
      echo "<br><font class='message'>".t("Tuotteella ei ole korvaavia tuotteita")."!</font>";
    }
  }
  else {
    // tuote löytyi, joten haetaan sen id...
    $row    = mysql_fetch_array($result);
    $id    = $row['id'];

    $query = "SELECT * FROM korvaavat WHERE id = '$id' AND yhtio = '$kukarow[yhtio]' ORDER BY if(jarjestys=0, 9999, jarjestys), tuoteno";
    $result = pupe_query($query);

    echo "<br><table>";
    echo "<tr>";
    echo "<th>".t("Korvaavia tuotteita")."</td>";
    echo "<th>".t("Järjestys")."</th>";
    echo "<td class='back'></td></tr>";

    // Korvausketjussa pitää olla ensiksi järjestysnumerot (ei järjestys 0),
    // ennen kuin voidaan sallia vastaavien korvaaminen vastaavat ketjussa.
    $_query = "SELECT * FROM korvaavat WHERE id = '$id' AND yhtio='{$kukarow['yhtio']}' AND jarjestys=0";
    $_result = pupe_query($_query);
    if (mysql_num_rows($_result) > 0) {
      $disable_korvaa_nappi = "disabled";
    }

    // Loopataan ketjun tuotteet ja piirretään valikot
    while ($row = mysql_fetch_array($result)) {
      $error = "";
      $query = "SELECT * FROM tuote WHERE tuoteno = '$row[tuoteno]' AND yhtio = '$kukarow[yhtio]'";
      $res   = pupe_query($query);

      // Tuotetta ei löydy
      if (mysql_num_rows($res) == 0) {
        $error = "<font class='error'>(".t("Tuote ei enää rekisterissä")."!)</font>";
      }

      // Tuote on päätuote
      if ($row['jarjestys'] == 1) {
        $paatuote = $row;
      }

      echo "<tr>
        <td>$row[tuoteno] $error</td>
        <td>
        <form action='korvaavat.php' method='post' autocomplete='off'>
        <input type='hidden' value='$lopetus' name='lopetus'>
        <input size='5' type='text' name='prio' value='$row[jarjestys]'></td>
        <input type='hidden' name='tunnus' value='$row[tunnus]'>
        <input type='hidden' name='tee' value='muutaprio'>
        </form>
        </td>
        <td class='back'>
        <form action='korvaavat.php' method='post'>
        <input type='hidden' value='$lopetus' name='lopetus'>
        <input type='hidden' name='tunnus' value='$row[tunnus]'>
        <input type='hidden' name='tee' value='del'>
        <input type='submit' value='".t("Poista")."'>
        </form>
        </td>";

      // Päätuotteen synkronointi vastaavat ketjuun
      // Tarkistetaan voidaanko päätuote synkronoida vastaava tauluun
      $query = "SELECT * FROM vastaavat WHERE yhtio='{$kukarow['yhtio']}' AND tuoteno='{$row['tuoteno']}'";
      $rresult = pupe_query($query);
      $vastaava = mysql_num_rows($rresult);

      // Jos tuotteella on vastaavat taulussa merkintä ja se ei ole korvaavuusketjun päätuote
      if ($vastaava > 0 and $row['jarjestys'] != 1) {
        echo "<td class='back'>
          <form action='korvaavat.php' method='post'>
          <input type='hidden' value='$lopetus' name='lopetus'>
          <input type='hidden' name='tee' value='korvaa_vastaava'>
          <input type='hidden' name='tunnus' value='$row[tunnus]'>
          <input type='hidden' name='korvattava' value='$row[tuoteno]'>
          <input type='hidden' name='korvaava' value='$paatuote[tuoteno]'>
          <input type='submit' value='Korvaa' $disable_korvaa_nappi/>
          </form>
          </td>";
        echo "<td class='back'>".t("Korvaa vastaava tämän ketjun päätuotteella")."</td>";
      }

      echo "</tr>";
    }

    echo "</table>";
  }

  if ($ok != 1) {
    echo "<form action='korvaavat.php' method='post' autocomplete='off'>
        <input type='hidden' value='$lopetus' name='lopetus'>
        <input type='hidden' name='tuoteno' value='$tuoteno'>
        <input type='hidden' name='tee' value='add'>
        <hr>";

    echo t("Lisää korvaava tuote").": ";

    echo "<input type='text' name='korvaava'>
        <input type='submit' value='".t("Lisää")."'>
        </form>";
  }
}

$formi = 'etsituote';
$kentta = 'tuoteno';

require "inc/footer.inc";
