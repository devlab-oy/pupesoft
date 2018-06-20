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


$selmalli = "";

if (!empty($malli)) {
  $selmalli = "SELECTED";
}

echo "<td><select name='malli'>";
echo "<option value=''>" . t("Ei mallia") . "</option>";
echo "<option value='zebra' $selmalli>" . t("Zebra") . "</option>";
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
    echo "<font class='error'>".t("VIRHE: Tuotetta ei l�ydy")."</font><br>";
    $tee = "";
  }

  $tulostakappale = (int) $tulostakappale;

  if ($tulostakappale <= 0) {
    echo "<font class='error'>".t("VIRHE: Sy�t� kappalem��r�")."</font><br>";
    $tee = "";
  }

  if ($kirjoitin == "") {
    echo "<font class='error'>".t("VIRHE: Valitse kirjoitin")."</font><br>";
    $tee = "";
  }
}

if ($tee == "jatka" and $malli == '') {
  $filenimi = "/tmp/sahantera_tulostus.txt";
  $hammastus = t_tuotteen_avainsanat($trow, 'HAMMASTUS');

  // Mitat tulee olla millimetrej�, metrein� kannassa. Syvyys yhdell� desimaalilla, muut ilman desimaalia.
  $mitat = round($trow["tuotekorkeus"] * 1000, 0)." x ".round($trow["tuoteleveys"] * 1000, 0)." x ".round($trow["tuotesyvyys"] * 1000, 1);

  // Splitataan tuotteen nimitys spacesta
  $nimitys = split(" ", $trow["nimitys"]);

  $out = chr(10).chr(10).chr(10).chr(10);     // 5 rivinvaihtoa (Line feed)
  $out .= sprintf('%6s', ' ');          // 6 spacea
  $out .= sprintf('%-9.9s', $nimitys[1]);     // Nimityksest� toka sana, max 9 merkki�
  $out .= sprintf('%1s', ' ');          // 1 space
  $out .= sprintf('%-25.25s', $mitat);       // Pituus x leveys x paksuus, max 25 merkki�
  $out .= sprintf('%1s', ' ');          // 1 space
  $out .= sprintf('%-12.12s', $hammastus);    // Hammastus, max 12 merkki�
  $out .= chr(10).chr(10).chr(13);        // 2 rivinvaihtoa (Line feed) + 1 Carriage return (= siirret��n kirjoitusp�� rivin alkuun)
  $out .= sprintf('%16s', ' ');          // 16 spacea
  $out .= sprintf('%-40.40s', $trow["tuoteno"]);  // Tuotenumero, max 40 merkki�
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
  // t�m� haara on zebra tarralle
  // Toimii ja suunniteltu mallille GX420t
  // Tarrankoko 254 mm x 25,4 mm

  // Ohjelmointimanuaali l�ytyy : http://www.zebra.com/id/zebra/na/en/index/products/printers/desktop/gx420t.4.tabs.html
  // "Programmin guide"
  $hammastus = t_tuotteen_avainsanat($trow, 'HAMMASTUS');

  // Mitat tulee olla millimetrej�, metrein� kannassa. Syvyys yhdell� desimaalilla, muut ilman desimaalia.
  $mitat = round($trow["tuotekorkeus"] * 1000, 0)." x ".round($trow["tuoteleveys"] * 1000, 0)." x ".round($trow["tuotesyvyys"] * 1000, 1);

  // Splitataan tuotteen nimitys spacesta

  $nimitys = split(" ", $trow["nimitys"]);

  $sivu  = "^XA\n";    // vakio alku, pakollinen
  $sivu .= "^LH000\n";  // offset vasemmasta
  $sivu .= "^LT200\n";  // offset ylh��lt�
  // $sivu .= "^POI\n";  // offset ylh��lt�

  $sivu .= "^FO015,350^XGE:LENOX.GRF,1,1^FS";
  $sivu .= "^FO095,420^XGE:HANSKAT.GRF,1,1^FS";
  $sivu .= "^FO015,420^XGE:LASIT.GRF,1,1^FS";
  $sivu .= "^FO015,1750^XGE:TKP.GRF,1,1^FS";

  $sivu .= "^FO150,520\n^AQR,18,8\n^FDVAROITUS: VANNESAHANTER� J�NNITYKSESS�. K�YT� SUOJALASEJA JA -K�SINEIT�, KUN K�SITTELET TER��,\n^FS";  // Tulostetaan varoitusteksti
  $sivu .= "^FO120,680\n^AQR,18,8\n^FDASENNA TER� SAHAVALMISTAJAN OHJEIDEN MUKAISESTI\n^FS";  // Tulostetaan Firma
  $sivu .= "^FO95,520\n^AQR,18,8\n^FDTuote ja koodi\n^FS";
  $sivu .= "^FO75,520\n^AQR,24,14\n^FD$nimitys[1]\n^FS";
  $sivu .= "^FO50,520\n^AQR,24,14\n^FD$tuoteno\n^FS";
  $sivu .= "^FO95,1000\n^AQR,18,8\n^FDpituus x leveys x paksuus\n^FS";
  $sivu .= "^FO75,1000\n^AQR,24,14\n^FD$mitat\n^FS";
  $sivu .= "^MD10";                        // TUMMUUS, vakio on 8 mutta se ei riit� viivakoodille.
  $sivu .= "^PQ$tkpl";                    // Tulostettavien lukum��r�
  $sivu .= "^FO95,1500\n^AQR,18,8\n^FDHammastus:\n^FS";    // hammastus
  $sivu .= "^FO75,1500\n^AQR,24,14\n^FD$hammastus\n^FS";    // hammastus
  $sivu .= "^FO20,520\n^ADR,18,8\n^FD$yhtiorow[nimi]\n^FS";  // Tulostetaan Firma
  $sivu .= "^FO20,1000\n^ADR,18,8\n^FD$yhtiorow[www]\n^FS";  // Tulostetaan Firma
  $sivu .= "^FO20,1500\n^ADR,18,8\n^FDpuh: $yhtiorow[puhelin]\n^FS";  // Tulostetaan Firma
  $sivu .= "\n^XZ";  // pakollinen lopetus

  //konvertoidaan ��kk�set printterin ymm�rt�m��n muotoon
  $from = array('�', '�', '�', '�', '�', '�', '|');
  $to  = array(chr(132), chr(134), chr(148), chr(142), chr(143), chr(153), chr(179));      // DOS charset

  $sivu = str_replace($from, $to, $sivu);                  // Tehd��n k��nn�s

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
