<?php

require "inc/parametrit.inc";

echo "<font class='head'>".t('Lis‰‰ hyv‰ksytt‰v‰ dokumentti')."</font><hr><br>";

if (!empty($tee) and $tee == "lisaa_tiedosto") {

  $virhe = "";

  if (is_uploaded_file($_FILES['userfile1']['tmp_name']) === TRUE) {

    if ($_FILES['userfile1']['size'] == 0) {
      $virhe = "<font class='error'><br>".t("Tiedosto on tyhj‰")."!</font>";
    }

    if (!$file = fopen($_FILES['userfile1']['tmp_name'], "r")) {
      $virhe = "<font class='error'>".t("VIRHE: Tiedoston avaus ep‰onnistui!")."</font>";
    }
  }
  else {
    echo "<font class='error'>".t("VIRHE: Tiedosto puuttuu!")."</font>";
  }


  if (empty($virhe)) {
    $tila = "M";
    $hyvak[5] = trim($hyvak[5]);
    $hyvak[4] = trim($hyvak[4]);
    $hyvak[3] = trim($hyvak[3]);
    $hyvak[2] = trim($hyvak[2]);
    $hyvak[1] = trim($hyvak[1]);

    if (strlen($hyvak[5]) > 0) {
      $hyvaksyja_nyt=$hyvak[5];
      $tila = "H";
    }
    if (strlen($hyvak[4]) > 0) {
      $hyvaksyja_nyt=$hyvak[4];
      $tila = "H";
    }
    if (strlen($hyvak[3]) > 0) {
      $hyvaksyja_nyt=$hyvak[3];
      $tila = "H";
    }
    if (strlen($hyvak[2]) > 0) {
      $hyvaksyja_nyt=$hyvak[2];
      $tila = "H";
    }
    if (strlen($hyvak[1]) > 0) {
      $hyvaksyja_nyt=$hyvak[1];
      $tila = "H";
    }

    // Kirjoitetaan doumentti kantaan
    $query = "INSERT into hyvaksyttavat_dokumentit set
              yhtio         = '{$kukarow['yhtio']}',
              nimi          = '{$nimi}',
              kuvaus        = '{$kuvaus}',
              hyvak1        = '{$hyvak[1]}',
              hyvak2        = '{$hyvak[2]}',
              hyvak3        = '{$hyvak[3]}',
              hyvak4        = '{$hyvak[4]}',
              hyvak5        = '{$hyvak[5]}',
              hyvaksyja_nyt = '{$hyvaksyja_nyt}',
              tila          = '{$tila}',
              laatija       = '{$kukarow['kuka']}',
              luontiaika    = now(),
              muuttaja      = '{$kukarow['kuka']}',
              muutospvm     = now()";
    $result = pupe_query($query);
    $tunnus = mysql_insert_id($GLOBALS["masterlink"]);

    for ($k=1; $k<=3; $k++) {
      tallenna_liite("userfile{$k}", "hyvaksyttavat_dokumentit", $tunnus, $nimi);
    }

    echo "<br>";
    echo t("Dokumentti lis‰tty!");
    echo "<br><br><br>";

    $hyvak = array();
    $tee = "";
  }
}


echo "<form method='post' name='sendfile' enctype='multipart/form-data'>";
echo "<input type='hidden' name='tee' value='lisaa_tiedosto'>";

echo "<table>";

echo "<tr><th>".t("Nimi").":</th>
      <td><input name='nimi' type='text' size='30'></td></tr>";

echo "<tr><th>".t("Kuvaus").":</th>
      <td><textarea cols='40' rows='5' name='kuvaus'></textarea></td></tr>";

echo "<tr><th>".t("Tiedosto").":</th>
      <td><input name='userfile1' type='file'></td></tr>";

echo "<tr><th>".t("Tiedosto").":</th>
      <td><input name='userfile2' type='file'></td></tr>";

echo "<tr><th>".t("Tiedosto").":</th>
      <td><input name='userfile3' type='file'></td></tr>";

echo "<tr><th class='ptop'>".t("Hyv‰ksyj‰t")."</th><td>";

$query = "SELECT DISTINCT kuka.kuka, kuka.nimi
          FROM kuka
          JOIN oikeu ON oikeu.yhtio = kuka.yhtio and oikeu.kuka = kuka.kuka and oikeu.nimi like '%dokumenttien_hyvaksynta.php'
          WHERE kuka.yhtio    = '$kukarow[yhtio]'
          AND kuka.aktiivinen = 1
          AND kuka.extranet   = ''
          ORDER BY kuka.nimi";
$vresult = pupe_query($query);

// T‰ytet‰‰n 5 hyv‰ksynt‰kentt‰‰
for ($i=1; $i<6; $i++) {
  $ulos = '';

  while ($vrow = mysql_fetch_assoc($vresult)) {
    $sel = "";
    if ($hyvak[$i] == $vrow['kuka']) {
      $sel = "selected";
    }
    $ulos .= "<option value ='$vrow[kuka]' $sel>$vrow[nimi]";
  }

  // K‰yd‰‰n sama data l‰pi uudestaan
  mysql_data_seek($vresult, 0);

  echo "<select name='hyvak[$i]'>
        <option value = ' '>".t("Ei kukaan")."
        $ulos
        </select>";

  echo "<br>";
}

echo "</td></tr>";

echo "</table>";

echo "<br><br><input type='submit' value='".t("Tallenna tiedosto")."'>";
echo "</form>";

require "inc/footer.inc";
