<?php

require "inc/parametrit.inc";

echo "<font class='head'>".t('Lis‰‰ hyv‰ksytt‰v‰ dokumentti')."</font><hr><br>";

echo "<script type='text/javascript'>
      $(function() {

        $('#tiedostotyyppi').on('change', function(event) {
          ttyyppi = $('select#tiedostotyyppi option:checked').val();

          $('.hyvaksyjat').each(function(){

            $(this).find('option').each(function(){
              if (!$(this).hasClass('donthide')) {
               $(this).hide()
             }
            });

            $(this).find('option').each(function(){
              if ($(this).hasClass('_'+ttyyppi)) {
                $(this).show()
              }
            });
          });
        });

      });
    </script>";

if (!empty($tee) and $tee == "lisaa_tiedosto" and !empty($sub_button)) {

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
    $virhe = "<font class='error'>".t("VIRHE: Tiedosto puuttuu!")."</font>";
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
              yhtio          = '{$kukarow['yhtio']}',
              tiedostotyyppi = '{$tiedostotyyppi}',
              nimi           = '{$nimi}',
              kuvaus         = '{$kuvaus}',
              hyvak1         = '{$hyvak[1]}',
              hyvak2         = '{$hyvak[2]}',
              hyvak3         = '{$hyvak[3]}',
              hyvak4         = '{$hyvak[4]}',
              hyvak5         = '{$hyvak[5]}',
              hyvaksyja_nyt  = '{$hyvaksyja_nyt}',
              tila           = '{$tila}',
              laatija        = '{$kukarow['kuka']}',
              luontiaika     = now(),
              muuttaja       = '{$kukarow['kuka']}',
              muutospvm      = now()";
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
    $nimi = "";
    $tiedostotyyppi = "";
    $kuvaus = "";
    $hyvak = "";
    $hyvaksyja_nyt = "";
    $tila = "";
  }
  else {
    echo "$virhe<br>";
  }
}

echo "<form method='post' name='sendfile' enctype='multipart/form-data'>";
echo "<input type='hidden' name='tee' value='lisaa_tiedosto'>";

echo "<table>";

echo "<tr><th>".t("Nimi").":</th>
      <td><input name='nimi' type='text' size='30' value='$nimi'></td></tr>";

echo "<tr><th>".t("Kuvaus").":</th>
      <td><textarea cols='40' rows='5' name='kuvaus'>$kuvaus</textarea></td></tr>";

echo "<tr><th>".t("Tiedostotyyppi").":</th><td>";

echo "<select name='tiedostotyyppi' id='tiedostotyyppi'>";
echo "<option value ='' $sel>".t("Valitse tiedostotyyppi")."</option>";

$query = "SELECT *
          FROM hyvaksyttavat_dokumenttityypit
          WHERE yhtio = '$kukarow[yhtio]'
          ORDER BY tyyppi";
$vresult = pupe_query($query);

$doku_tyyppi_tunnus = 0;

while ($vrow = mysql_fetch_assoc($vresult)) {
  $sel = "";
  if ($tiedostotyyppi == $vrow["tyyppi"]) {
    $sel = "SELECTED";
    $doku_tyyppi_tunnus = $vrow["tunnus"];
  }

  echo "<option value ='$vrow[tunnus]' $sel>$vrow[tyyppi]</option>";
}

echo "</select>";
echo "</td></tr>";

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

    $query = "SELECT group_concat(concat('_', hyvaksyttavat_dokumenttityypit.tunnus) SEPARATOR ' ') tyypit
              FROM hyvaksyttavat_dokumenttityypit_kayttajat hdk
              JOIN hyvaksyttavat_dokumenttityypit ON (hdk.yhtio=hyvaksyttavat_dokumenttityypit.yhtio
                and hdk.doku_tyyppi_tunnus=hyvaksyttavat_dokumenttityypit.tunnus)
              WHERE hdk.yhtio = '$kukarow[yhtio]'
              AND hdk.kuka = '$vrow[kuka]'";
    $hvresult = pupe_query($query);
    $hvrow = mysql_fetch_assoc($hvresult);

    $ulos .= "<option class='$hvrow[tyypit]' value ='$vrow[kuka]' $sel>$vrow[nimi]</option>";
  }

  // K‰yd‰‰n sama data l‰pi uudestaan
  mysql_data_seek($vresult, 0);

  echo "<select class='hyvaksyjat' name='hyvak[$i]'>
        <option class='donthide' value = ' '>".t("Ei kukaan")."</option>
        $ulos
        </select>";
  echo "<br>";
}

echo "</td></tr>";
echo "</table>";

echo "<br><br><input type='submit' name='sub_button' value='".t("Tallenna tiedosto")."'>";
echo "</form>";

require "inc/footer.inc";
