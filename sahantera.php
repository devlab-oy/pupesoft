<?php

require "inc/parametrit.inc";

if ($livesearch_tee == "TUOTEHAKU") {
  livesearch_tuotehaku();
  exit;
}

echo "<font class='head'>".t("Etiketin tulostus")."</font><hr>";

if (!isset($malli)) $malli = '';

// Vakio lomake
$formi  = 'formi';
$kentta = 'tuoteno';

enable_ajax();

echo "<form method='post' name='formi' autocomplete='off'>";
echo "<input type='hidden' name='tee' value='hae'>";
echo "<input type='hidden' name='toim' value='$toim'>";
echo "<input type='hidden' name='malli' value='$malli'>";

echo "<table>";
echo "<tr>";
echo "<th>".t("Tuotenumero")."</th>";
echo "<th>".t("KPL")."</th>";
echo "<th>".t("Kirjoitin")."</th>";
echo "<th>".t("Malli") . "</th>";

echo "<tr>";
echo "<td>".livesearch_kentta("formi", "TUOTEHAKU", "tuoteno", 150, $tuoteno)."</td>";
echo "<td><input type='text' name='tulostakappale' size='3' value='$tulostakappale'></td>";
echo "<td><select name='kirjoitin'>";
echo "<option value=''>".t("Ei kirjoitinta")."</option>";

$query = "SELECT *
          FROM kirjoittimet
          WHERE yhtio  = '$kukarow[yhtio]'
          and komento != 'email'
          order by kirjoitin";
$kires = pupe_query($query);

while ($kirow = mysql_fetch_array($kires)) {
  if ($kirow['tunnus'] == $kirjoitin or $kirow['kirjoitin'] == "Lexmark tarratulostin") $select = 'SELECTED';
  else $select = '';
  echo "<option value='$kirow[tunnus]' $select>$kirow[kirjoitin]</option>";
}

echo "</select></td>";

echo "<td><select name='malli'>";
echo "<option value=''>" . t("Ei mallia") . "</option>";
echo "<option value='zebra'>" . t("Zebra") . "</option>";
echo "</select></td>";
echo "<td class='back'><input name='submit' type='submit' value='".t("Tulosta")."'></td>";
echo "</tr>";
echo "</table>";
echo "</form>";
echo "<br>";

// Virhetarkastukset
if ($tee == "hae") {

  $query = "SELECT *
            FROM tuote
            WHERE yhtio = '{$kukarow["yhtio"]}'
            and tuoteno = '{$tuoteno}'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 1) {
    $trow = mysql_fetch_assoc($result);
    $tee = "jatka";
  }
  else {
    echo "<font class='error'>".t("VIRHE: Tuotetta ei löydy")."</font><br>";
    $tee = "";
  }

  $tulostakappale = (int) $tulostakappale;

  if ($tulostakappale <= 0) {
    echo "<font class='error'>".t("VIRHE: Syötä kappalemäärä")."</font><br>";
    $tee = "";
  }

  if ($kirjoitin == "") {
    echo "<font class='error'>".t("VIRHE: Valitse kirjoitin")."</font><br>";
    $tee = "";
  }
}

if ($tee == "jatka" and $malli == '') {
  echo "TÄMÄ ON NYKYINEN HAARA";
  $filenimi = "/tmp/sahantera_tulostus.txt";
  $hammastus = t_tuotteen_avainsanat($trow, 'HAMMASTUS');

  // Mitat tulee olla millimetrejä, metreinä kannassa. Syvyys yhdellä desimaalilla, muut ilman desimaalia.
  $mitat = round($trow["tuotekorkeus"] * 1000, 0)." x ".round($trow["tuoteleveys"] * 1000, 0)." x ".round($trow["tuotesyvyys"] * 1000, 1);

  // Splitataan tuotteen nimitys spacesta
  $nimitys = split(" ", $trow["nimitys"]);

  $out = chr(10).chr(10).chr(10).chr(10);     // 5 rivinvaihtoa (Line feed)
  $out .= sprintf('%6s', ' ');          // 6 spacea
  $out .= sprintf('%-9.9s', $nimitys[1]);     // Nimityksestä toka sana, max 9 merkkiä
  $out .= sprintf('%1s', ' ');          // 1 space
  $out .= sprintf('%-25.25s', $mitat);       // Pituus x leveys x paksuus, max 25 merkkiä
  $out .= sprintf('%1s', ' ');          // 1 space
  $out .= sprintf('%-12.12s', $hammastus);    // Hammastus, max 12 merkkiä
  $out .= chr(10).chr(10).chr(13);        // 2 rivinvaihtoa (Line feed) + 1 Carriage return (= siirretään kirjoituspää rivin alkuun)
  $out .= sprintf('%16s', ' ');          // 16 spacea
  $out .= sprintf('%-40.40s', $trow["tuoteno"]);  // Tuotenumero, max 40 merkkiä
  $out .= chr(13).chr(12);            // Carriage return + Form feed

  $boob = file_put_contents($filenimi, $out);

  if ($boob === FALSE) {
    echo "<font class='error'>".t("VIRHE: Tiedoston kirjoittaminen ei onnistunut")."</font><br>";
    $tee = "";
  }
  else {
    $tee = "tulosta";
  }
}

if ($tee == "jatka" and $malli == 'zebra') {
  echo "TÄMÄ ON ZEBRA HAARA";
  // tämä haara on zebra tarralle
  // Toimii ja suunniteltu mallille GX420t
  // Tarrankoko xx mm x xx mm

  // Ohjelmointimanuaali löytyy : http://www.zebra.com/id/zebra/na/en/index/products/printers/desktop/gx420t.4.tabs.html
  // "Programmin guide"
  $hammastus = t_tuotteen_avainsanat($trow, 'HAMMASTUS');

  // Mitat tulee olla millimetrejä, metreinä kannassa. Syvyys yhdellä desimaalilla, muut ilman desimaalia.
  $mitat = round($trow["tuotekorkeus"] * 1000, 0)." x ".round($trow["tuoteleveys"] * 1000, 0)." x ".round($trow["tuotesyvyys"] * 1000, 1);

  // Splitataan tuotteen nimitys spacesta
  $nimitys = split(" ", $trow["nimitys"]);


  // tulostetaan $nimitys[1]);     // Nimityksestä toka sana, max 9 merkkiä
  // tulostetaan , $mitat);       // Pituus x leveys x paksuus, max 25 merkkiä
  // tulostetaan  $hammastus);    // Hammastus, max 12 merkkiä
  // tulostetaan  $trow["tuoteno"]);  // Tuotenumero, max 40 merkkiä


  $pituus = 50; // voidaan määrittää mistä kohdasta katkaistaan teksti.

  if (strlen($nimitys) > $pituus ) {
    if (strpos($nimitys, " ")) {
      $nimipalat = explode(' ', $nimitys);

      $merkkimaara = 0;
      $nimitys = "";
      $nimitys2 = "";

      foreach ($nimipalat as $nimipala) {
        if (strlen($nimitys)+strlen($nimipala) <= $pituus or $merkkimaara == 0) {
          $nimitys .= $nimipala." ";
        }
        else {
          $nimitys2 .= $nimipala." ";
        }
        $merkkimaara += strlen($nimipala);
      }
    }

    $nimitys = substr($nimitys, 0, $pituus);
    $nimitys2 = substr($nimitys2, 0, $pituus);
  }
    //  Format ^FOx,y,z

  $sivu  = "^XA\n";    // vakio alku, pakollinen
  $sivu .= "^LH50\n";  // offset vasemmasta
  $sivu .= "^LT000\n";  // offset ylhäältä
  $sivu .= "^POI\n";  // offset ylhäältä
  $sivu .= "^FO85,20\n^ASR,20,12\n^FDKeijo: $tuoteno\n^FS";
  $sivu .= "^FO85,65\n^AQR,18,8\n^FD$nimitys\n^FS";
  $sivu .= "^FO85,88\n^AQR,18,8\n^FD$mitat\n^FS";
  $sivu .= "^MD10";                        // TUMMUUS, vakio on 8 mutta se ei riitä viivakoodille.
  $sivu .= "^PQ$tkpl";                    // Tulostettavien lukumäärä
  $sivu .= "^FO80,120\n^AQR,18,8\n^FD$hammastus\n^FS";    // hammastus
  $sivu .= "^FO260,180\n^AQR,18,8\n^FD$yhtiorow[nimi]\n^FS";  // Tulostetaan Firma
  $sivu .= "\n^XZ";  // pakollinen lopetus

  //^XA
  //^FO50,50^ADN,36,20^FDxxxxxxxxxxx
  //^FS
  //^XZ
  // zebra blokki

  list($usec, $sec) = explode(' ', microtime());
  mt_srand((float) $sec + ((float) $usec * 100000));
  $filenimi = "/tmp/Zebra-tarrat-".md5(uniqid(mt_rand(), true)).".txt";
  $fh = file_put_contents($filenimi, $sivu);

  $tee = "tulosta";

}

if ($tee == "tulosta") {

  $query = "SELECT komento, kirjoitin
            FROM kirjoittimet
            WHERE yhtio = '{$kukarow["yhtio"]}'
            AND tunnus  = '{$kirjoitin}'";
  $result = pupe_query($query);
  $krivi = mysql_fetch_assoc($result);

  for ($i = 0; $i < $tulostakappale; $i++) {
    // Tulostetaan kirjoittimelle
    $line = exec("{$krivi["komento"]} $filenimi");
  }

  echo t("Tulostetaan")." {$tulostakappale} ".t("tarraa")."...<br>";

  // dellataan tmp file
  unlink($filenimi);
}

require "inc/footer.inc";
