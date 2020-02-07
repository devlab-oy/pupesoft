#!/usr/bin/php
<?php

// Kutsutaanko CLI:st�
if (php_sapi_name() != 'cli') {
  die ("T�t� scripti� voi ajaa vain komentorivilt�!");
}

date_default_timezone_set('Europe/Helsinki');

// otetaan tietokanta connect
$con = mysql_connect("193.185.248.50", "pupesoft", "pupe1") or die("Yhteys tietokantaan epaonnistui!");
mysql_select_db("pupesoft") or die ("Tietokanta ei l�ydy palvelimelta..");

$kukarow['yhtio'] = "artr";

$query = "SELECT *
          FROM yhtio
          WHERE yhtio = '{$kukarow['yhtio']}'";
$yhtiores = mysql_query($query) or die("\nQuery failed:\n$query");

if (mysql_num_rows($yhtiores) == 1) {
  $yhtiorow = mysql_fetch_assoc($yhtiores);
}
else {
  die ("Yhti� ei l�ydy!");
}

// tehd��n temppitiedostot
if ($kukarow["yhtio"] == "artr") {
  $elma1    = "/tmp/orum_kaikki";
  $elma2    = "/tmp/orum_muuttuneet";
  $elmazip1 = "orum_kaikki.zip";
  $elmazip2 = "orum_muuttuneet.zip";
}
else {
  die ("Virheellinen yhti�!");
}

if (!$handle1 = fopen($elma1, "w")) die("Filen $elma1 luonti ep�onnistui!");
if (!$handle2 = fopen($elma2, "w")) die("Filen $elma2 luonti ep�onnistui!");

$muutosraja = (int) date('Ymd', mktime(0, 0, 0, date('m')-1, date('d'), date('Y'))); // now - 1 kuukausi

// itte query
$query = "SELECT tuote.tuoteno, tuote.try, tuote.aleryhma, tuote.yksikko, tuote.nimitys, tuote.muutospvm, tuote.eankoodi, tuote.status,
          round(tuote.myyntihinta * (1+(tuote.alv/100)), 2) myyntihinta
          FROM tuote
          WHERE tuote.yhtio      = '{$kukarow['yhtio']}'
          AND tuote.hinnastoon  != 'E'
          AND tuote.tuotetyyppi  NOT IN ('A','B')
          AND (tuote.status != 'P' OR (SELECT sum(tuotepaikat.saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0)";
$res = mysql_query($query) or die("\nQuery failed:\n$query");

while ($row = mysql_fetch_assoc($res)) {

  // muutetaan yksik�t ISO-standardin mukaisiksi
  $yksikko = "";

  if ($row['yksikko'] == 'KPL' or $row['yksikko'] == 'PCE') {
    $yksikko = "PCE";
  }
  if ($row['yksikko'] == 'SRJ') {
    $yksikko = "SET";
  }
  if ($row['yksikko'] == 'PAR') {
    $yksikko = "PAR";
  }

  // Tietuekuvaus
  // ------------
  // vakio, D=datarivi, , Aakkosnumeerinen, 1 merkki
  // Toimittajan tuotekoodi, Aakkosnumeerinen, 35 merkki�
  // Tuoteryhm�, Aakkosnumeerinen, 10, merkki�
  // Alennusryhm�, Aakkosnumeerinen, 10, merkki�
  // Tuotteen nimi, Aakkosnumeerinen, 50, merkki�
  // Luett.hinnan verottomuus 0/1, Aakkosnumeerinen, 1 merkki
  // Luettelohinta, Desimaaliluku, 8 merkki� + pilkku + 2 desimaalia
  // Hinnan yksikk� (UN/ECE suositus No.20), Aakkosnumeerinen, 1 merkki
  // EAN-koodi, Aakkosnumeerinen, 35 merkki�
  // Tuotteen elinkaaren vaihe (1-9), Aakkosnumeerinen, 1 merkki
  // Minimitoimituser�, Desimaaliluku, 8 merkki� + pilkku + 2 desimaalia
  // Minimitoimituser�n yksikk� (UN/ECE suositus No.20), Aakkosnumeerinen, 3 merkki�
  // Pakkauskoko, Aakkosnumeerinen, 10 merkki�
  // Pakkauskoon yksikk� (UN/ECE suositus No.20), Aakkosnumeerinen, 3 merkki�
  // Hinnoittelujakaja, Aakkosnumeerinen, 3 merkki�
  // Tarjouskampanjan voimassaolo CCYYMMDDCCYYMMDD, Aakkosnumeerinen, 16 merkki�
  // Tarjouskampanjahinta, Desimaaliluku, 8 merkki� + pilkku + 2 desimaalia
  // Alennuksen voimassaolo CCYYMMDDCCYYMMDD, Aakkosnumeerinen, 16 merkki�
  // M��r�n minimi, Desimaaliluku, 16 merkki� + pilkku + 2 desimaalia
  // M��r�n maksimi, Desimaaliluku, 16 merkki� + pilkku + 2 desimaalia
  // Alennushinta, Desimaaliluku, 8 merkki� + pilkku + 2 desimaalia
  // Alennuksen voimassaolo CCYYMMDDCCYYMMDD, Aakkosnumeerinen, 16 merkki�
  // M��r�n minimi, Desimaaliluku, 16 merkki� + pilkku + 2 desimaalia
  // M��r�n maksimi, Desimaaliluku, 16 merkki� + pilkku + 2 desimaalia
  // Alennushinta, Desimaaliluku, 8 merkki� + pilkku + 2 desimaalia
  // Alennuksen voimassaolo CCYYMMDDCCYYMMDD, Aakkosnumeerinen, 16 merkki�
  // M��r�n minimi, Desimaaliluku, 16 merkki� + pilkku + 2 desimaalia
  // M��r�n maksimi, Desimaaliluku, 16 merkki� + pilkku + 2 desimaalia
  // Alennushinta, Desimaaliluku, 8 merkki� + pilkku + 2 desimaalia
  // Korvaavan tuotteen tuotekoodi (toimittajan), Aakkosnumeerinen, 35 merkki�
  // Korvaavan tuotteen EAN-tuotekoodi, Aakkosnumeerinen, 35 merkki�

  $row['nimitys'] = substr(str_replace(array("\n", "\r"), " ", trim($row['nimitys'])), 0, 50);

  if ($row['status'] == "T") {
    $row['nimitys'] = substr($row['nimitys'], 0, 45)." (TT)";
  }

  // tehd��n tietuetta
  $ulos   = sprintf("%-1.1s",   "D");                 // vakio, D=datarivi
  $ulos  .= sprintf("%-35.25s", $row['tuoteno']);            // tuotenumero
  $ulos  .= sprintf("%-10.10s", sprintf("%02s", $row['try']));    // tuotteen tuoteryhm� kahteen merkkiin asti zeropaddattuna
  $ulos  .= sprintf("%-10.10s", sprintf("%02s", $row['aleryhma']));  // tuotteen alennusryhm� kahteen merkkiin asti zeropaddattuna
  $ulos  .= sprintf("%-50.50s", $row['nimitys']);           // tuotteen nimitys
  $ulos  .= sprintf("%-1.1s",   "1");                    // verollisuus 1=verollinen 0=veroton
  $ulos  .= sprintf("%-11.11s", $row['myyntihinta']);             // tuotteen luettelohinta
  $ulos  .= sprintf("%-1.1s",   "");                     // hinnan yksikk�
  $ulos  .= sprintf("%-35.35s", $row['eankoodi']);             // tuotteen eankoodi
  $ulos  .= sprintf("%-1.1s",   "");                     // elinkaaren vaihe 1-9
  $ulos  .= sprintf("%-11.11s", "0");                     // minimitoimituser�
  $ulos  .= sprintf("%-3.3s",   $yksikko);                 // minimitoimituksen yksikk�
  $ulos  .= sprintf("%-3.3s",   "");                     // hinnoittelujakaja
  $ulos  .= sprintf("%-16.16s", "");                     // tarjouskampanjan voimassaolo CCYYMMDDCCYYMMDD
  $ulos  .= sprintf("%-11.11s", "");                     // tarjouskampanjahinta
  $ulos  .= sprintf("%-16.16s", "");                     // 1. alennuksen voimassaolo CCYYMMDDCCYYMMDD
  $ulos  .= sprintf("%-19.19s", "");                     // m��r�n minimi
  $ulos  .= sprintf("%-19.19s", "");                     // m��r�n maksimi
  $ulos  .= sprintf("%-11.11s", "");                     // alennushinta
  $ulos  .= sprintf("%-16.16s", "");                     // 2. alennuksen voimassaolo CCYYMMDDCCYYMMDD
  $ulos  .= sprintf("%-19.19s", "");                     // m��r�n minimi
  $ulos  .= sprintf("%-19.19s", "");                     // m��r�n maksimi
  $ulos  .= sprintf("%-11.11s", "");                     // alennushinta
  $ulos  .= sprintf("%-16.16s", "");                     // 3. alennuksen voimassaolo CCYYMMDDCCYYMMDD
  $ulos  .= sprintf("%-19.19s", "");                     // m��r�n minimi
  $ulos  .= sprintf("%-19.19s", "");                     // m��r�n maksimi
  $ulos  .= sprintf("%-11.11s", "");                     // alennushinta

  // haetaan korvaavia tuotteita
  $query = "SELECT *
            FROM korvaavat use index (yhtio_tuoteno)
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tuoteno = '{$row['tuoteno']}'";
  $kores = mysql_query($query) or die("\nQuery failed:\n$query");

  if (mysql_num_rows($kores) > 0) {

    $kkrow = mysql_fetch_assoc($kores);
    $query = "SELECT korvaavat.tuoteno, eankoodi
              FROM korvaavat use index (yhtio_id)
              JOIN tuote use index (tuoteno_index) on (tuote.yhtio = korvaavat.yhtio and tuote.tuoteno = korvaavat.tuoteno)
              WHERE korvaavat.yhtio = '{$kukarow['yhtio']}'
              AND korvaavat.id      = '{$kkrow['id']}'
              ORDER BY korvaavat.jarjestys, korvaavat.tuoteno";
    $kores = mysql_query($query) or die("\nQuery failed:\n$query");
    $nexti = 0;

    while ($korow = mysql_fetch_assoc($kores)) {
      if ($nexti == 1) {
        $ulos .= sprintf("%-35.35s", $korow['tuoteno']);  // korvaava tuote
        $ulos .= sprintf("%-35.35s", $korow['eankoodi']); // korvaavan eankoodi
        $nexti = 2; // muutetaan lippu niin tiedet��n ett� seuraava l�yty
        break;
      }

      if ($korow['tuoteno'] == $row['tuoteno']) {
        $nexti = 1; // meid�n tulee ottaa seuraava tuote, koska se on t�m�n tuotteen j�lkeen seuraava korvaava
      }
    }

    // ei l�ydetty nexti� vaikka ois pit�ny, oltiin ilmeisesti sitte vikassa tuotteessa, haetaan eka korvaava
    if ($nexti == 1) {
      $query = "SELECT korvaavat.tuoteno, eankoodi
                FROM korvaavat use index (yhtio_id)
                JOIN tuote use index (tuoteno_index) on (tuote.yhtio = korvaavat.yhtio AND tuote.tuoteno = korvaavat.tuoteno)
                WHERE korvaavat.yhtio  = '{$kukarow['yhtio']}'
                AND korvaavat.id       = '{$kkrow['id']}'
                AND korvaavat.tuoteno != '{$row['tuoteno']}'
                ORDER BY korvaavat.jarjestys, korvaavat.tuoteno
                LIMIT 1";
      $kores = mysql_query($query) or die("\nQuery failed:\n$query");

      if (mysql_num_rows($kores) == 1) {
        $korow = mysql_fetch_assoc($kores);
        $ulos .= sprintf("%-35.35s", $korow['tuoteno']);  // korvaava tuote
        $ulos .= sprintf("%-35.35s", $korow['eankoodi']); // korvaavan eankoodi
      }
    }
  }

  $ulos  .= "\n";

  // kirjotetaan tietue failiin
  if (fwrite($handle1, $ulos) === FALSE) die("failin kirjoitus ep�onnistui");

  // verrataan v�h�n p�iv�m��ri�. onpa ik�v�� PHP:ss�!
  list($vv, $kk, $pp) = explode("-", substr($row["muutospvm"], 0, 10));
  $muutospvm  = (int) date('Ymd', mktime(0, 0, 0, $kk, $pp, $vv));

  // kirjotetaan tietue muutosfailiin jos se on tarpeeksi uusi
  if ($muutospvm >= $muutosraja) {
    if (fwrite($handle2, $ulos) === FALSE) die("failin kirjoitus ep�onnistui");
  }
}

// faili kiinni
fclose($handle1);
fclose($handle2);

//pakataan failit
system("cd /tmp; /usr/bin/zip -q $elmazip1 ".basename($elma1));
system("cd /tmp; /usr/bin/zip -q $elmazip2 ".basename($elma2));

//siirret��n ne my�s t�nne
system("cp -f /tmp/$elmazip1 /tmp/$elmazip2 /var/www/downloads/varaosa/");

// loopataan l�pi home dirikka
$handle1 = opendir("/home") or die("/home feilas\n");

while ($file = readdir($handle1)) {

  // katotaan l�ytyyk� homedirikasta hinnastot -hakemisto
  if (is_writable("/home/$file/hinnastot")) {

    // jos l�yty kopsataan hinnastot sinne jos kyseess� on oikee lafka
    if (substr($file, 0, 4) == $kukarow["yhtio"]) {
      system("cp -f /tmp/$elmazip1 /tmp/$elmazip2 /home/$file/hinnastot");
    }
  }
}

// poistetaan failit
unlink($elma1);
unlink($elma2);
unlink("/tmp/$elmazip1");
unlink("/tmp/$elmazip2");
