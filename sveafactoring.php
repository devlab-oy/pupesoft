<?php

if (isset($_POST["tee"])) $tee = $_POST["tee"];
else $tee = "";
if (isset($_POST["kaunisnimi"])) $kaunisnimi = $_POST["kaunisnimi"];
else $kaunisnimi = "";
if (isset($_POST["valkoodi"])) $valkoodi = $_POST["valkoodi"];
else $valkoodi = "";

if ($tee == 'lataa_tiedosto') $lataa_tiedosto = 1;
if ($tee == 'siirra_tiedosto') $siirra_tiedosto = 1;

require 'inc/parametrit.inc';

if ($tee == "lataa_tiedosto") {
  readfile("dataout/".$filenimi);
  exit;
}
elseif ($tee == "siirra_tiedosto") {
  //readfile("dataout/".$filenimi);

  // Connect to host
  $conn_id = ftp_connect($svea_ftp_host);

  // Open a session to an external ftp site
  $login_result = ftp_login($conn_id, $svea_ftp_user, $svea_ftp_pass);

  // Check open
  if ((!$conn_id) || (!$login_result)) {
    echo t("Ftp-yhteyden muodostus epaonnistui! Tarkista salasanat."); die;
  }
  else {
    echo t("Ftp-yhteys muodostettu.")."<br/>";
  }

  // turn on passive mode transfers
  ftp_pasv($conn_id, true) ;
  echo t("Kytketaan passive mode paalle.")."<br/>";

  // open some file for reading
  $file = "dataout/".$filenimi;
  $fp = fopen($file, 'r');

  // try to upload $file
  if (ftp_fput($conn_id, $filenimi, $fp, FTP_ASCII)) {
    echo t("Onnistuneesti siirrettiin tiedosto $file<br/>");
  }
  else {
    echo t("Tiedoston siirtamisessa oli ongelma: $file<br/>");
  }

  ftp_close($conn_id);
  echo t("Ftp-yhteys suljettu.")."<br/>";

  exit;
}
else {
  echo "<font class='head'>".t("Svea Factoring siirtotiedosto").":</font><hr><br>";
  $factoringyhtio = "SVEA";
}

$factoring_tarkista_lisa = "";

if ($tee == 'TARKISTA') {
  if (strtoupper($valkoodi) != strtoupper($ed_valkoodi)) {
    $tee = "";
  }
  else {
    $tee = "TULOSTA";
  }

  // lisätään tämä queryyn alle, niin ei ikinä päästä eteenpäin, jos factoring_id on virheellinen
  $factoring_tarkista_lisa = " and tunnus = '$factoring_id' ";
}

$query = "SELECT *
          FROM factoring
          WHERE yhtio        = '{$kukarow["yhtio"]}'
          and factoringyhtio = '{$factoringyhtio}'
          {$factoring_tarkista_lisa}";
$factoring_result = pupe_query($query);

if (mysql_num_rows($factoring_result) == 0) {
  echo t("%s factoring-sopimusta ei ole perustettu!", null, $factoringyhtio);

  $tee = "ohita";
}
elseif (mysql_num_rows($factoring_result) == 1) {
  // meillä on vaan yksi, ei tarvitse valita
  $vrow = mysql_fetch_assoc($factoring_result);
  $factoring_id = $vrow['tunnus'];
  $tee = isset($tee) ? $tee : 'TOIMINNOT';
}

if ($tee == '') {
  //Käyttöliittymä
  echo "<form method='post'>";
  echo "<input type='hidden' name='tee' value='TOIMINNOT'>";

  echo t("Valitse factoring-sopimus");
  echo " <select name='factoring_id' onchange='submit();'>";
  echo "<option value=''></option>";

  while ($vrow = mysql_fetch_assoc($factoring_result)) {
    $sel = ($vrow['tunnus'] == $factoring_id) ? "selected" : "";
    echo "<option value='{$vrow["tunnus"]}' $sel>{$vrow["nimitys"]}</option>";
  }

  echo "</select>";
  echo "</form>";
  echo "<br><br>";
}

if ($tee == 'TOIMINNOT') {
  //Käyttöliittymä
  echo "<br>";
  echo "<form method='post'>";
  echo "Luo uusi siirtotiedosto<br>";
  echo "<table>";
  echo "<input type='hidden' name='toim' value='$toim'>";
  echo "<input type='hidden' name='factoring_id' value='$factoring_id'>";
  echo "<input type='hidden' name='tee' value='TARKISTA'>";

  if ($valkoodi == '') {
    $valkoodi = $yhtiorow["valkoodi"];
  }

  echo "<input type='hidden' name='ed_valkoodi' value='$valkoodi'>";

  $query = "SELECT *
            FROM factoring
            WHERE yhtio        = '$kukarow[yhtio]'
            and factoringyhtio = '$factoringyhtio'
            and tunnus         = '$factoring_id'
            and valkoodi       = '$valkoodi'";
  $fres = pupe_query($query);
  $frow = mysql_fetch_array($fres);

  $query = "SELECT min(laskunro) eka, max(laskunro) vika
            FROM lasku use index (factoring)
            JOIN maksuehto ON (lasku.yhtio = maksuehto.yhtio
              and lasku.maksuehto            = maksuehto.tunnus
              and maksuehto.factoring_id     = '$factoring_id')
            WHERE lasku.yhtio                = '$kukarow[yhtio]'
            and lasku.tila                   = 'U'
            and lasku.alatila                = 'X'
            and lasku.summa                 != 0
            and lasku.factoringsiirtonumero  = 0
            and lasku.valkoodi               = '$valkoodi'";
  $aresult = pupe_query($query);
  $arow = mysql_fetch_array($aresult);

  $query = "SELECT nimi, tunnus
            FROM valuu
            WHERE yhtio = '$kukarow[yhtio]'
            ORDER BY jarjestys";
  $vresult = pupe_query($query);

  echo "<tr><th>Sopimusnumero:</th><td>$frow[sopimusnumero]</td>";
  echo "<tr><th>Valitse valuutta:</th><td><select name='valkoodi' onchange='submit();'>";

  while ($vrow = mysql_fetch_array($vresult)) {
    $sel="";
    if ($vrow['nimi'] == $valkoodi) {
      $sel = "selected";
    }
    echo "<option value = '$vrow[nimi]' $sel>$vrow[nimi]</option>";
  }

  echo "</select></td></tr>";

  echo "<tr>
      <th>Syötä laskuvälin alku:</th>
      <td><input type='text' name='ppa' value='$arow[eka]' size='10'></td>
      </tr>
      <tr>
      <th>Syötä laskuvälin loppu:</th>
      <td><input type='text' name='ppl' value='$arow[vika]' size='10'></td>
      </tr>";

  $query = "SELECT max(factoringsiirtonumero)+1 seuraava
            FROM lasku use index (factoring)
            WHERE  yhtio                     = '$kukarow[yhtio]'
            and lasku.tila                   = 'U'
            and lasku.alatila                = 'X'
            and lasku.summa                 != 0
            and lasku.factoringsiirtonumero  > 0";
  $aresult = pupe_query($query);
  $arow = mysql_fetch_array($aresult);

  echo "<tr><th>Siirtoluettelon numero:</th>
      <td><input type='text' name='factoringsiirtonumero' value='$arow[seuraava]' size='6'></td>";

  echo "<td class='back'><input type='submit' value='Luo siirtoaineisto'></td></tr></form></table><br><br>";


  //Käyttöliittymä
  echo "<br>";
  echo "<form method='post'>";
  echo "Uudelleenluo siirtotiedosto<br>";
  echo "<table>";
  echo "<input type='hidden' name='toim' value='$toim'>";
  echo "<input type='hidden' name='factoring_id' value='$factoring_id'>";
  echo "<input type='hidden' name='tee' value='TARKISTA'>";
  echo "<input type='hidden' name='tee_u' value='UUDELLEENLUO'>";

  if ($valkoodi == '') {
    $valkoodi = $yhtiorow["valkoodi"];
  }

  echo "<input type='hidden' name='ed_valkoodi' value='$valkoodi'>";

  $query = "SELECT *
            FROM factoring
            WHERE yhtio        = '$kukarow[yhtio]'
            and factoringyhtio = '$factoringyhtio'
            and tunnus         = '$factoring_id'
            and valkoodi       = '$valkoodi'";
  $fres = pupe_query($query);
  $frow = mysql_fetch_array($fres);

  echo "<tr><th>Sopimusnumero:</th><td>$frow[sopimusnumero]</td>";
  echo "<tr><th>Valitse valuutta:</th><td><select name='valkoodi' onchange='submit();'>";

  $query = "SELECT nimi, tunnus
            FROM valuu
            WHERE yhtio = '$kukarow[yhtio]'
            ORDER BY jarjestys";
  $vresult = pupe_query($query);

  while ($vrow = mysql_fetch_array($vresult)) {
    $sel="";
    if ($vrow['nimi'] == $valkoodi) {
      $sel = "selected";
    }
    echo "<option value = '$vrow[nimi]' $sel>$vrow[nimi]</option>";
  }

  echo "</select></td></tr>";

  echo "<tr><th>Siirtoluettelon numero:</th>
      <td><input type='text' name='factoringsiirtonumero' value='$factoringsiirtonumero' size='6'></td>";

  echo "<td class='back'><input type='submit' value='Uudelleenluo siirtoaineisto'></td></tr></form></table><br><br>";
}

if ($tee == 'TULOSTA') {

  $luontipvm  = date("Ymd");
  $luontiaika  = date("Hi");

  $query = "SELECT *
            FROM factoring
            WHERE yhtio        = '$kukarow[yhtio]'
            and factoringyhtio = '$factoringyhtio'
            and tunnus         = '$factoring_id'
            and valkoodi       = '$valkoodi'";
  $fres = pupe_query($query);
  $frow = mysql_fetch_array($fres);

  //Luodaan Start-tietue
  $ulos  = sprintf('%-2.2s', "00");                  //sovellustunnus
  $ulos .= sprintf('%07.7s', $frow["sopimusnumero"]);
  $ulos .= sprintf('%08.8s', $luontipvm);                //aineiston luontipvm
  $ulos .= "\r\n";

  if ($ppl == '') {
    $ppl = $ppa;
  }

  if ($tee_u != 'UUDELLEENLUO' and ($ppa == '' or $ppl == '' or $ppl < $ppa)) {
    echo "Huono laskunumeroväli!";
    exit;
  }

  if ($tee_u == 'UUDELLEENLUO') {
    $where = "  and lasku.factoringsiirtonumero = '$factoringsiirtonumero' ";
  }
  else {
    $where = "  and lasku.laskunro >= '$ppa'
          and lasku.laskunro <= '$ppl'
          and lasku.factoringsiirtonumero = 0 ";
  }

  $dquery = "SELECT lasku.yhtio
             FROM lasku
             JOIN maksuehto ON (lasku.yhtio = maksuehto.yhtio
              and lasku.maksuehto      = maksuehto.tunnus
              and maksuehto.factoring  = '$factoring_id')
             WHERE lasku.yhtio         = '$kukarow[yhtio]'
             and lasku.tila            = 'U'
             and lasku.alatila         = 'X'
             and lasku.summa          != 0
             and lasku.valkoodi        = '$valkoodi'
             $where";
  $dresult = pupe_query($dquery);

  if (mysql_num_rows($dresult) == 0) {
    echo "Huono laskunumeroväli! Yhtään siirettävää laskua ei löytynyt!";
    exit;
  }

  $query = "SELECT if(lasku.summa >= 0, '01', '02') tyyppi,
            lasku.ytunnus,
            lasku.nimi,
            lasku.nimitark,
            lasku.osoite,
            lasku.postino,
            lasku.postitp,
            lasku.maa,
            lasku.laskunro,
            round(lasku.viikorkopros*100,0) viikorkopros,
            round(abs(lasku.summa*100),0) summa,
            round(abs(lasku.arvo*100),0) arvo,
            round(abs(lasku.kasumma*100),0) kasumma,
            round(abs(lasku.summa_valuutassa*100),0) summa_valuutassa,
            round(abs(lasku.kasumma_valuutassa*100),0) kasumma_valuutassa,
            lasku.toim_nimi,
            lasku.toim_nimitark,
            lasku.toim_osoite,
            lasku.toim_postino,
            lasku.toim_postitp,
            lasku.toim_maa,
            lasku.maa,
            lasku.viite,
            lasku.kohde,
            lasku.sisviesti1,
            lasku.viesti,
            lasku.asiakkaan_tilausnumero,
            lasku.tilausyhteyshenkilo,
            DATE_FORMAT(lasku.tapvm, '%Y%m%d') tapvm,
            DATE_FORMAT(lasku.erpcm, '%Y%m%d') erpcm,
            DATE_FORMAT(lasku.kapvm, '%Y%m%d') kapvm,
            lasku.tunnus,
            lasku.valkoodi,
            lasku.liitostunnus
            FROM lasku
            JOIN maksuehto ON (lasku.yhtio = maksuehto.yhtio
              and lasku.maksuehto      = maksuehto.tunnus
              and maksuehto.factoring  = '$factoring_id')
            WHERE lasku.yhtio          = '$kukarow[yhtio]'
            and lasku.tila             = 'U'
            and lasku.alatila          = 'X'
            and lasku.summa           != 0
            and lasku.valkoodi         = '$valkoodi'
            $where
            ORDER BY laskunro";
  $laskures = pupe_query($query);
  if (mysql_num_rows($laskures) > 0) {
    $laskukpl  = 0;
    $vlaskukpl = 0;
    $vlaskusum = 0;
    $hlaskukpl = 0;
    $hlaskusum = 0;

    $laskuvirh = 0;

    echo "<table>";
    echo "<tr><th>Tyyppi</th><th>Laskunumero</th><th>Nimi</th><th>Summa</th><th>Valuutta</th></tr>";

    while ($laskurow = mysql_fetch_array($laskures)) {

      // Haetaan asiakkaan tiedot
      $query  = "SELECT *
                 FROM asiakas
                 WHERE yhtio = '$kukarow[yhtio]'
                 and tunnus  = '$laskurow[liitostunnus]'";
      $asires = pupe_query($query);
      $asirow = mysql_fetch_array($asires);

      // Valuuttalasku
      if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
        $laskurow["summa"]   = $laskurow["summa_valuutassa"];
        $laskurow["kasumma"] = $laskurow["kasumma_valuutassa"];
      }

      if ($asirow["asiakasnro"] == 0 or !is_numeric($asirow["asiakasnro"]) or strlen($asirow["asiakasnro"]) > 6) {
        $laskuvirh++;
      }

      //luodaan Main record
      $ulos .= sprintf('%2.2s', "01"); // Record type
      $ulos .= sprintf('%07.7s', $frow["sopimusnumero"]);
      $ulos .= sprintf('%010.10s', $laskurow["laskunro"]);
      $ulos .= sprintf('%010.10s', $asirow["asiakasnro"]);
      $ulos .= sprintf('%05.5s', 0); // kohde eli piiri
      $ulos .= sprintf('%-40.40s', $laskurow["nimi"]);
      $ulos .= sprintf('%-40.40s',    $laskurow["nimitark"]);
      $ulos .= sprintf('%-40.40s',   $laskurow["osoite"]);
      $ulos .= sprintf('%05.5s', $laskurow["postino"]);
      $ulos .= sprintf('%-20.20s', $laskurow["postitp"]);
      $ulos .= sprintf('%010.10s', 0);
      $ulos .= sprintf('%-18.18s', $asirow["puhelin"]);
      $ulos .= sprintf('%-3.3s', $laskurow["maa"]);
      $ulos .= sprintf('%08.8s', $laskurow["tapvm"]);
      $ulos .= sprintf('%08.8s', $laskurow["erpcm"]);

      $oma_viite = $laskurow["kohde"];
      if ($oma_viite != "" and $laskurow["sisviesti1"] != "") {
        $oma_viite .= ", ";
      }

      $oma_viite .= $laskurow["sisviesti1"];
      $oma_viite = str_replace("\r", "\n", $oma_viite);
      $oma_viite = str_replace("\n", " ", $oma_viite);
      $ulos .= sprintf('%-32.32s', $oma_viite); // Meidän viitteemme, tässä siis kohde + sisviesti1

      $laskun_viite = $laskurow["viesti"];
      $laskun_viite = str_replace("\r", "\n", $laskun_viite);
      $laskun_viite = str_replace("\n", ";", $laskun_viite);
      $ulos .= sprintf('%-32.32s', $laskun_viite); // Teidän viitteenne

      $tilausnumero = $laskurow["asiakkaan_tilausnumero"];
      $tilausnumero = str_replace("\r", "\n", $tilausnumero);
      $tilausnumero = str_replace("\n", ";", $tilausnumero);
      if ($tilausnumero != "" and $laskurow["tilausyhteyshenkilo"] != "") {
        $tilausnumero .= ", ";
      }
      $tilausnumero .= $laskurow["tilausyhteyshenkilo"];
      $ulos .= sprintf('%-32.32s', $tilausnumero); // Tilausnumero
      $ulos .= sprintf('%011.11s', abs($laskurow["summa"])); // Invoice amount

      if ($laskurow["tyyppi"] == "02") {
        $ulos .= "-";
      }
      else {
        $ulos .= "+";
      }

      $alvi = $laskurow["summa"] - $laskurow["arvo"];
      $ulos .= sprintf('%011.11s', abs($alvi));

      if ($laskurow["tyyppi"] == "02") {
        $ulos .= "-";
      }
      else {
        $ulos .= "+";
      }

      $ulos .= sprintf('%-3.3s', "");  // Currency code
      $ulos .= sprintf('%08.8s', 0);    // Currency
      $ulos .= sprintf('%-36.36s', "");  // Foreign address
      $ulos .= sprintf('%03.3s', 0);    // Customer group
      $ulos .= sprintf('%-1.1s', "");  // Not in use
      $ulos .= sprintf('%03.3s', 0);    // Sales number
      $ulos .= sprintf('%-50.50s', $asirow['email']);  // Customer email addr
      if ($asirow['chn'] == "666" and $asirow['email'] != "") {
        $ulos .= "02";
      }
      else {
        $ulos .= "01";
      }

      // Jos kyseessä on yksityishenkilö, ei laiteta Y-tunnusta
      if ($asirow['laji'] == "H") {
        $ytunnus = "";
      }
      else {
        $ytunnus = str_replace("-", "", $asirow['ytunnus']);
        $ytunnus = sprintf("%08.8s", $ytunnus);
        $ytunnus = substr($ytunnus, 0, 7)."-".substr($ytunnus, -1);
      }

      $ulos .= sprintf('%-11.11s', $ytunnus);
      $ulos .= sprintf('%-2.2s', strtoupper($asirow['kieli']));  // Language code
      $ulos .= sprintf('%025.25s', "");  // OCR Reference
      $ulos .= sprintf('%-1.1s', ""); // Invoice type
      $ulos .= "\r\n";

      //luodaan invoice row recordit

      $query = "SELECT * FROM tilausrivi WHERE uusiotunnus = '".$laskurow["tunnus"]."'";
      $laskurivires = pupe_query($query);

      if (mysql_num_rows($laskurivires) > 0) {
        while ($laskurivi = mysql_fetch_array($laskurivires)) {
          $ulos .= sprintf('%-2.2s', "11"); // Record type
          $ulos .= sprintf('%07.7s', $frow["sopimusnumero"]);
          $ulos .= sprintf('%010.10s', $laskurow["laskunro"]);
          $ulos .= sprintf('%03.3s', 0);
          $ulos .= sprintf('%010.10s', $asirow["asiakasnro"]);
          $ulos .= sprintf('%-1.1s', "A"); // Row type, A = Article row
          $ulos .= sprintf('%015.15s', 0);
          $ulos .= sprintf('%09.9s', abs($laskurivi['kpl'] * 100)); // Quantity

          if ($laskurivi['kpl'] < 0) {
            $ulos .= "-";
          }
          else {
            $ulos .= "+";
          }

          $nimitys = $laskurivi['nimitys'];
          $nimitys = str_replace("\r", "\n", $nimitys);
          $nimitys = str_replace("\n", ";", $nimitys);
          $ulos .= sprintf('%-40.40s', $nimitys);
          $ulos .= sprintf('%011.11s', abs($laskurivi['hinta'] * 100)); // Price w/o VAT
          $ulos .= sprintf('%02.2s', abs($laskurivi['ale1'])); // Discount percent
          $ulos .= sprintf('%011.11s', abs($laskurivi['rivihinta'] * 100)); // Row price

          if ($laskurivi['rivihinta'] < 0) {
            $ulos .= "-";
          }
          else {
            $ulos .= "+";
          }

          $ulos .= sprintf('%04.4s', $laskurivi['alv'] * 100); // VAT percentage
          $ulos .= sprintf('%011.11s', round($laskurivi['alv'] * abs($laskurivi['rivihinta']))); // VAT amount // Korjaus poistettiin 100 * ja lisättiin round

          if ($laskurivi['alv'] * $laskurivi['rivihinta'] < 0) {
            $ulos .= "-";
          }
          else {
            $ulos .= "+";
          }

          $ulos .= sprintf('%-4.4s', $laskurivi['yksikko']); // Units
          $ulos .= "\r\n";

          // Jos kyseessä on kommentti...
          if ($laskurivi['kommentti'] != "") {

            $kommentti = $laskurivi['kommentti'];

            $laskuri = 0;
            while ((strlen($kommentti) > 0) and ($laskuri < 10)) {
              $ulos .= sprintf('%-2.2s', "11"); // Record type
              $ulos .= sprintf('%07.7s', $frow["sopimusnumero"]);
              $ulos .= sprintf('%010.10s', $laskurow["laskunro"]);
              $ulos .= sprintf('%03.3s', 0);
              $ulos .= sprintf('%010.10s', $asirow["asiakasnro"]);
              $ulos .= sprintf('%-1.1s', "T"); // Row type, T = Text row
              $ulos .= sprintf('%-15.15s', "");
              $ulos .= sprintf('%-9.9s', ""); // Quantity
              $ulos .= " ";

              $kommentti = str_replace("\r", "\n", $kommentti);
              $kommentti = str_replace("\n", " ", $kommentti);

              $ulos .= sprintf('%-40.40s', $kommentti);
              if (strlen($kommentti) > 40)
                $kommentti = substr($kommentti, -40);
              else
                $kommentti = "";

              $ulos .= sprintf('%-11.11s', "");
              $ulos .= sprintf('%-2.2s', "");
              $ulos .= sprintf('%-11.11s', "");
              $ulos .= " ";

              $ulos .= sprintf('%-4.4s', ""); // VAT percentage
              $ulos .= sprintf('%-11.11s', "");
              $ulos .= " ";
              $ulos .= sprintf('%-4.4s', ""); // Units
              $ulos .= "\r\n";
              $laskuri++;
            }
          }
        }
      }

      echo "<tr>";

      $laskukpl++;
      if ($laskurow["tyyppi"] == "01") {
        $vlaskukpl++;
        $vlaskusum += $laskurow["summa"];

        echo "<td>Veloituslasku</td><td>$laskurow[laskunro]</td><td>$laskurow[nimi]</td><td align='right'>".sprintf('%.2f', $laskurow["summa"]/100)."</td><td>$laskurow[valkoodi]</td>";
      }
      if ($laskurow["tyyppi"] == "02") {
        $hlaskukpl++;
        $hlaskusum += $laskurow["summa"];

        echo "<td>Hyvityslasku:</td><td>$laskurow[laskunro]</td><td>$laskurow[nimi]</td><td align='right'>".sprintf('%.2f', $laskurow["summa"]/100)."</td><td>$laskurow[valkoodi]</td>";
      }

      if ($asirow["asiakasnro"] == 0 or !is_numeric($asirow["asiakasnro"]) or strlen($asirow["asiakasnro"]) > 6) {
        echo "<td><font class='error'>VIRHE: Asiakasnumero: $asirow[asiakasnro] ei kelpaa!</font><a href='".$palvelin2."yllapito.php?ojarj=&toim=asiakas&tunnus=$laskurow[liitostunnus]'>Muuta asiakkaan tietoja</a></td>";
      }
    }

    if ($laskuvirh > 0) {
      echo "</table>";
      echo "<br><br>";
      echo "Aineistossa oli virheitä! Korjaa ne ja aja uudestaan!";
    }
    else {
      if ($tee_u != 'UUDELLEENLUO') {
        $dquery = "UPDATE lasku, maksuehto
                   SET lasku.factoringsiirtonumero = '$factoringsiirtonumero'
                   WHERE lasku.yhtio                = '$kukarow[yhtio]'
                   and lasku.tila                   = 'U'
                   and lasku.alatila                = 'X'
                   and lasku.summa                 != 0
                   and lasku.laskunro               >= '$ppa'
                   and lasku.laskunro               <= '$ppl'
                   and lasku.factoringsiirtonumero  = 0
                   and lasku.valkoodi               = '$valkoodi'
                   and lasku.yhtio                  = maksuehto.yhtio
                   and lasku.maksuehto              = maksuehto.tunnus
                   and maksuehto.factoring_id       = '$factoring_id'";
        $dresult = pupe_query($dquery);
      }

      //Luodaan End-tietue

      $ulos .= sprintf('%-2.2s', "99");
      $ulos .= sprintf('%07.7s', $frow["sopimusnumero"]);
      $ulos .= sprintf('%08.8s', $luontipvm); //aineiston luontipvm
      $ulos .= sprintf('%09.9s', $laskukpl);

      $laskusum = ($vlaskusum + $hlaskusum);
      $ulos .= sprintf('%012.12s', abs($laskusum));
      if ($laskusum < 0) {
        $ulos .= "-";
      }
      else {
        $ulos .= "+";
      }

      $ulos .= "\r\n";

      // Annetaan tiedostolle nimi
      $numberi = "1";
      if ($numberi < 10) {
        $numb = "0".$numberi;
      }
      else {
        $numb = $numberi;
      }

      $svea_filename = "f".date("md").$numb.".".$frow["sopimusnumero"];

      while (file_exists("dataout/".$svea_filename)) {
        $numberi += 1;
        if ($numberi < 10) {
          $numb = "0".$numberi;
        }
        else {
          $numb = $numberi;
        }

        $svea_filename = "f".date("md").$numb.".".$frow["sopimusnumero"];
      }

      //kirjoitetaan faili levylle..
      $filenimi = $svea_filename;
      $fh = fopen("dataout/".$filenimi, "w");
      if (fwrite($fh, $ulos) === FALSE) die("Kirjoitus epäonnistui $filenimi");
      fclose($fh);

      echo "<tr><td class='back'><br></td></tr>";

      echo "<tr><td class='back' colspan='2'></td><th>Yhteensä $vlaskukpl veloituslaskua</th><td align='right'>".sprintf('%.2f', $vlaskusum/100)."</td><td>$laskurow[valkoodi]</td></tr>";
      echo "<tr><td class='back' colspan='2'></td><th>Yhteensä $hlaskukpl hyvityslaskua</th><td align='right'> ".sprintf('%.2f', $hlaskusum/100)."</td><td>$laskurow[valkoodi]</td></tr>";

      echo "</table>";
      echo "<br><br>";
      echo "<table>";
      echo "<tr><th>Tallenna siirtoaineisto levylle:</th>";
      echo "<form method='post' class='multisubmit'>";
      echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";

      if ($toim == "SVEA") {
        echo "<input type='hidden' name='kaunisnimi' value='".$svea_filename."'>";
      }
      echo "<input type='hidden' name='filenimi' value='$filenimi'>";
      echo "<input type='hidden' name='toim' value='$toim'>";
      echo "<td><input type='submit' value='Tallenna'></td></form>";
      echo "</tr>";
      echo "<tr><th>Siirrä siirtoaineisto SVEA:lle ftp:llä:</th>";
      echo "<form method='post'>";
      echo "<input type='hidden' name='tee' value='siirra_tiedosto'>";

      if ($toim == "SVEA") {
        echo "<input type='hidden' name='kaunisnimi' value='".$svea_filename."'>";
      }
      echo "<input type='hidden' name='filenimi' value='$filenimi'>";
      echo "<input type='hidden' name='toim' value='$toim'>";
      echo "<td><input type='submit' value='Suorita ftp -siirto'></td></form>";
      echo "</tr>";
      echo "</tr></table>";
    }
  }
  else {
    echo "<br><br>Yhtään siirrettävää laskua ei ole!<br>";
    $tee = "";
  }
}

if ($tee != "lataa_tiedosto") {
  require "inc/footer.inc";
}
