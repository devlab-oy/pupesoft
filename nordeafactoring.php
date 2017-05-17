<?php

if (isset($_REQUEST["tee"]) and $_REQUEST["tee"] == 'lataa_tiedosto') {
  $lataa_tiedosto = 1;
}

require 'inc/parametrit.inc';

if ($tee == "lataa_tiedosto") {
  readfile("dataout/".$filenimi);
  exit;
}

// Luodaan lähetettävä tiedosto ja siirrytään lähetysohjelmaan
if ($tee == "lahetasepa") {
  $filenimi = preg_replace("/^(.*?)siirto\-([0-9]*).txt/", "Factoringsiirto-\\1-\\2.txt", $pankkiyhteys_filenimi);
  rename("dataout/{$pankkiyhteys_filenimi}", "dataout/{$pankkiyhteys_pankki}_error/{$filenimi}");

  lopetus("{$palvelin2}pankkiyhteys.php////toim=laheta//tee=//pankkiyhteys_tunnus=$pankkiyhteys_tunnus", "META");
  exit;
}

if ($toim == "OKO") {
  echo "<font class='head'>".t("OKO Saatavarahoitus siirtotiedosto").":</font><hr><br>";
  $factoringyhtio = "OKO";
}
elseif ($toim == 'SAMPO') {
  echo "<font class='head'>".t("Sampo Factoring siirtotiedosto").":</font><hr><br>";
  $factoringyhtio = "SAMPO";
}
elseif ($toim == 'AKTIA') {
  echo "<font class='head'>".t("Aktia Factoring siirtotiedosto").":</font><hr><br>";
  $factoringyhtio = "AKTIA";
}
else {
  echo "<font class='head'>".t("Nordea Factoring siirtotiedosto").":</font><hr><br>";
  $factoringyhtio = "NORDEA";
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
  $frow = mysql_fetch_assoc($fres);

  $query = "SELECT min(laskunro) eka, max(laskunro) vika
            FROM lasku use index (yhtio_tila_tapvm)
            JOIN maksuehto ON (lasku.yhtio = maksuehto.yhtio
              and lasku.maksuehto            = maksuehto.tunnus
              and maksuehto.factoring_id     = '$factoring_id')
            WHERE lasku.yhtio                = '$kukarow[yhtio]'
            and lasku.tila                   = 'U'
            and lasku.tapvm                  > date_sub(CURDATE(), interval 6 month)
            and lasku.alatila                = 'X'
            and lasku.summa                 != 0
            and lasku.factoringsiirtonumero  = 0
            and lasku.valkoodi               = '$valkoodi'";
  $aresult = pupe_query($query);
  $arow = mysql_fetch_assoc($aresult);

  $query = "SELECT nimi, tunnus
            FROM valuu
            WHERE yhtio = '$kukarow[yhtio]'
            ORDER BY jarjestys";
  $vresult = pupe_query($query);

  echo "<tr><th>Sopimusnumero:</th><td>$frow[sopimusnumero]</td>";
  echo "<tr><th>Valitse valuutta:</th><td><select name='valkoodi' onchange='submit();'>";

  while ($vrow = mysql_fetch_assoc($vresult)) {
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
            FROM lasku use index (yhtio_tila_tapvm)
            WHERE lasku.yhtio                = '$kukarow[yhtio]'
            and lasku.tila                   = 'U'
            and lasku.tapvm                  > date_sub(CURDATE(), interval 6 month)
            and lasku.alatila                = 'X'
            and lasku.summa                 != 0
            and lasku.factoringsiirtonumero  > 0";
  $aresult = pupe_query($query);
  $arow = mysql_fetch_assoc($aresult);

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
  $frow = mysql_fetch_assoc($fres);

  echo "<tr><th>Sopimusnumero:</th><td>$frow[sopimusnumero]</td>";
  echo "<tr><th>Valitse valuutta:</th><td><select name='valkoodi' onchange='submit();'>";

  $query = "SELECT nimi, tunnus
            FROM valuu
            WHERE yhtio = '$kukarow[yhtio]'
             ORDER BY jarjestys";
  $vresult = pupe_query($query);

  while ($vrow = mysql_fetch_assoc($vresult)) {
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

  $luontipvm  = date("ymd");
  $luontiaika  = date("Hi");

  $query = "SELECT *
            FROM factoring
            WHERE yhtio        = '$kukarow[yhtio]'
            and factoringyhtio = '$factoringyhtio'
            and tunnus         = '$factoring_id'
            and valkoodi       = '$valkoodi'";
  $fres = pupe_query($query);
  $frow = mysql_fetch_assoc($fres);

  // Käännetään pois UTF-8 muodosta, jos Pupe on UTF-8:ssa
  if (PUPE_UNICODE) {
    // Tässä on "//NO_MB_OVERLOAD"-kommentti
    // jotta UTF8-konversio ei osu tähän riviin
    $yhtiorow["nimi"] =  utf8_decode($yhtiorow["nimi"]); //NO_MB_OVERLOAD
    $kukarow["nimi"] =  utf8_decode($kukarow["nimi"]); //NO_MB_OVERLOAD
  }

  //Luodaan erätietue
  if ($toim == "OKO") {
    $ulos  = sprintf('%-4.4s', "LA01"); //sovellustunnus
  }
  elseif ($toim == "SAMPO") {
    $ulos  = sprintf('%-4.4s', "SAFA"); //sovellustunnus, SAMPO factoring
  }
  elseif ($toim == "AKTIA") {
    $ulos  = sprintf('%-4.4s', "AKF1"); //sovellustunnus, AKTIA factoring
  }
  else {
    $ulos  = sprintf('%-4.4s', "KRFL"); //sovellustunnus
  }

  $ulos .= sprintf('%01.1s', 0); //tietuetunnus

  if (in_array($toim, array("SAMPO", "AKTIA"))) {
    $ulos .= sprintf('%017.17s', str_replace('-', '', $yhtiorow["ytunnus"])); //myyjän ytunnus etunollilla SAMPO!
  }
  else {
    $ulos .= sprintf('%-17.17s', str_replace('-', '', $yhtiorow["ytunnus"])); //myyjän ytunnus ilman väliviivaa OKO & NORDEA
  }

  $ulos .= sprintf('%06.6s', $luontipvm); //aineiston luontipvm
  $ulos .= sprintf('%04.4s', $luontiaika); //luontikaika
  $ulos .= sprintf('%06.6s', $frow["sopimusnumero"]); //sopimusnumero
  $ulos .= sprintf('%-3.3s', $valkoodi); //valuutta

  if ($toim == "OKO") {
    $ulos .= sprintf('%-2.2s', "OP"); //rahoitusyhtiön tunnus
  }
  elseif ($toim == "SAMPO") {
    $ulos .= sprintf('%-2.2s', "PR"); //rahoitusyhtiön tunnus SAMPO
  }
  elseif ($toim == "AKTIA") {
    $ulos .= sprintf('%-2.2s', "AF"); //rahoitusyhtiön tunnus AKTIA
  }
  else {
    $ulos .= sprintf('%-2.2s', "MR"); //rahoitusyhtiön tunnus
  }

  if (in_array($toim, array("OKO", "AKTIA"))) {
    $ulos .= sprintf('%-30.30s', $yhtiorow["nimi"]); //siirtäjän nimi
  }
  else {
    $ulos .= sprintf('%-30.30s', $kukarow["nimi"]); //siirtäjän nimi
  }

  $ulos .= sprintf('%06.6s', $factoringsiirtonumero); //siirtoluettelon numero
  $ulos .= sprintf('%-37.37s', ""); //
  $ulos .= sprintf('%-63.63s', ""); //
  $ulos .= sprintf('%-221.221s', ""); //
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
              and lasku.maksuehto         = maksuehto.tunnus
              and maksuehto.factoring_id  = '$factoring_id')
             WHERE lasku.yhtio            = '$kukarow[yhtio]'
             and lasku.tila               = 'U'
             and lasku.alatila            = 'X'
             and lasku.summa             != 0
             and lasku.valkoodi           = '$valkoodi'
             $where";
  $dresult = pupe_query($dquery);

  if (mysql_num_rows($dresult) == 0) {
    echo "Huono laskunumeroväli! Yhtään siirettävää laskua ei löytynyt!";
    exit;
  }

  $query = "SELECT if (lasku.summa >= 0, '01', '02') tyyppi,
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
            DATE_FORMAT(lasku.tapvm, '%y%m%d') tapvm,
            DATE_FORMAT(lasku.erpcm, '%y%m%d') erpcm,
            DATE_FORMAT(lasku.kapvm, '%y%m%d') kapvm,
            lasku.tunnus,
            lasku.valkoodi,
            lasku.liitostunnus
            FROM lasku
            JOIN maksuehto ON (lasku.yhtio = maksuehto.yhtio
              and lasku.maksuehto         = maksuehto.tunnus
              and maksuehto.factoring_id  = '$factoring_id')
            WHERE lasku.yhtio             = '$kukarow[yhtio]'
            and lasku.tila                = 'U'
            and lasku.alatila             = 'X'
            and lasku.summa              != 0
            and lasku.valkoodi            = '$valkoodi'
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
    echo "<tr><th>Päivämäärä:</th><td>".date("d.m.Y")."</td>";
    echo "<tr><th>Sopimusnumero:</th><td>{$frow["sopimusnumero"]}</td>";
    echo "<tr><th>Siirtoluettelon numero:</th><td>$factoringsiirtonumero</td></tr></table><br>";

    echo "<table>";
    echo "<tr><th>Tyyppi</th><th>Laskunumero</th><th>Nimi</th><th>Summa</th><th>Valuutta</th></tr>";

    // Käännetään pois UTF-8 muodosta, jos Pupe on UTF-8:ssa
    function nordea_decode(&$item1, $key) {
      // Tässä on "//NO_MB_OVERLOAD"-kommentti
      // jotta UTF8-konversio ei osu tähän riviin
      $item1 = utf8_decode($item1); //NO_MB_OVERLOAD
    }

    // Käännetään UTF-8 muoton, jos Pupe on UTF-8:ssa
    function nordea_encode(&$item1, $key) {
      // Tässä on "//NO_MB_OVERLOAD"-kommentti
      // jotta UTF8-konversio ei osu tähän riviin
      $item1 = utf8_encode($item1); //NO_MB_OVERLOAD
    }

    while ($laskurow = mysql_fetch_assoc($laskures)) {

      // Haetaan asiakkaan tiedot
      $query  = "SELECT *
                 FROM asiakas
                 WHERE yhtio = '$kukarow[yhtio]'
                 and tunnus  = '$laskurow[liitostunnus]'";
      $asires = pupe_query($query);
      $asirow = mysql_fetch_assoc($asires);

      // Valuuttalasku
      if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
        $laskurow["summa"]   = $laskurow["summa_valuutassa"];
        $laskurow["kasumma"] = $laskurow["kasumma_valuutassa"];
      }

      if ($asirow["asiakasnro"] == 0 or !is_numeric($asirow["asiakasnro"]) or strlen($asirow["asiakasnro"]) > 6) {
        $laskuvirh++;
      }

      // Käännetään pois UTF-8 muodosta, jos Pupe on UTF-8:ssa
      if (PUPE_UNICODE) {
        array_walk($laskurow, "nordea_decode");
        array_walk($asirow, "nordea_decode");
      }

      //luodaan ostajatietue
      if ($toim == "OKO") {
        $ulos .= sprintf('%-4.4s', "LA01"); //sovellustunnus
      }
      elseif ($toim == 'SAMPO') {
        $ulos .= sprintf('%-4.4s', "SAFA"); //sovellustunnus SAMPO
      }
      elseif ($toim == 'AKTIA') {
        $ulos .= sprintf('%-4.4s', "AKF1"); //sovellustunnus AKTIA
      }
      else {
        $ulos .= sprintf('%-4.4s', "KRFL"); //sovellustunnus
      }

      $ulos .= sprintf('%01.1s', 1); //tietuetunnus
      $ulos .= sprintf('%06.6s', $frow["sopimusnumero"]); //sopimusnumero

      if ($toim == "OKO") {
        $ulos .= sprintf('%-10.10s', $asirow["asiakasnro"]); //ostajan numero aka asiakasnumero
      }
      else {
        $ulos .= sprintf('%06.6s', $asirow["asiakasnro"]); //ostajan numero aka asiakasnumero
        $ulos .= sprintf('%-4.4s', "");
      }

      if ($toim == "AKTIA") {
        $ulos .= sprintf('%010.10s', str_replace('-', '', $laskurow["ytunnus"]));
      }
      else {
        $ulos .= sprintf('%-10.10s', str_replace('-', '', $laskurow["ytunnus"])); //ostajan ytunnus

      }

      if ($toim == "OKO") {
        $ulos .= sprintf('%-30.30s', strtoupper($laskurow["nimi"])); //ostajan nimi
      }
      else {
        $ulos .= sprintf('%-30.30s', $laskurow["nimi"]); //ostajan nimi
      }

      if ($toim == "OKO") {
        $ulos .= sprintf('%-30.30s', ""); //ostajan nimitark (Ei käytössä)
      }
      else {
        $ulos .= sprintf('%-30.30s', $laskurow["nimitark"]); //ostajan nimitark
      }

      if ($toim == "OKO") {
        $ulos .= sprintf('%-20.20s', strtoupper($laskurow["osoite"])); //ostajan osoite
      }
      else {
        $ulos .= sprintf('%-20.20s', $laskurow["osoite"]); //ostajan osoite
      }

      if ($toim == "OKO") {
        $ulos .= sprintf('%-20.20s', $laskurow["postino"]." ".strtoupper($laskurow["postitp"])); //ostajan postino ja postitp
      }
      else {
        $ulos .= sprintf('%-20.20s', $laskurow["postino"]." ".$laskurow["postitp"]); //ostajan postino ja postitp
      }

      $ulos .= sprintf('%-13.13s', "");
      $ulos .= sprintf('%-30.30s', "");
      $ulos .= sprintf('%-13.13s', "");

      if ($toim != "AKTIA") {
        $ulos .= sprintf('%-13.13s', "");
      }

      $ulos .= sprintf('%-2.2s', "FI"); //kieli
      $ulos .= sprintf('%-3.3s', $laskurow["valkoodi"]); //valuutta

      if ($toim == "OKO") {
        $ulos .= sprintf('%04.4s', ""); //viivastyskorko (Ei käytössä)
      }
      else {
        $ulos .= sprintf('%04.4s', $laskurow["viikorkopros"]);
      }

      $ulos .= sprintf('%03.3s', 0);
      $ulos .= sprintf('%06.6s', 0);

      if ($toim == "OKO") {
        $ulos .= sprintf('%03.3s', 1); //myyjän sopimustunnus
        $ulos .= sprintf('%-179.179s', 0);
      }
      elseif ($toim == 'SAMPO') {
        $ulos .= sprintf('%-182.182s', 0); // Sampo, tyhjää, Varalla..
      }
      else {
        if ($laskurow["maa"] != $yhtiorow["maa"] and $laskurow["maa"] != '') {
          $ulos .= sprintf('%-10.10s', $laskurow["maa"]);
        }
        else {
          $ulos .= sprintf('%-10.10s', "");
        }

        $ulos .= sprintf('%-172.172s', "");
      }

      if ($toim == "AKTIA") {
        $ulos .= sprintf('%-13.13s', "");
      }

      $ulos .= "\r\n";

      //luodaan laskutietue
      if ($toim == "OKO") {
        $ulos .= sprintf('%-4.4s', "LA01"); //sovellustunnus
      }
      elseif ($toim == 'SAMPO') {
        $ulos .= sprintf('%-4.4s', "SAFA"); //sovellustunnus SAMPO
      }
      elseif ($toim == 'AKTIA') {
        $ulos .= sprintf('%-4.4s', "AKF1"); //sovellustunnus AKTIA
      }
      else {
        $ulos .= sprintf('%-4.4s', "KRFL"); //sovellustunnus
      }

      $ulos .= sprintf('%01.1s', 3); //tietuetunnus
      $ulos .= sprintf('%06.6s', $frow["sopimusnumero"]); //sopimusnumero

      if ($toim == "OKO") {
        $ulos .= sprintf('%-10.10s', $asirow["asiakasnro"]); //ostajan numero aka asiakasnumero
      }
      else {
        $ulos .= sprintf('%06.6s', $asirow["asiakasnro"]); //ostajan numero aka asiakasnumero
        $ulos .= sprintf('%-4.4s', "");
      }

      if ($toim == 'SAMPO') {
        $ulos .= sprintf('%09.9s', $laskurow["laskunro"]); // Sampo
        $ulos .= sprintf('%-1.1s', "");
      }
      else {
        $ulos .= sprintf('%010.10s', $laskurow["laskunro"]); //laskunro
      }
      $ulos .= sprintf('%06.6s', $laskurow["tapvm"]); //laskun päiväys
      $ulos .= sprintf('%-3.3s', $laskurow["valkoodi"]); //valuutta
      $ulos .= sprintf('%06.6s', $laskurow["tapvm"]); //laskun arvopäivä
      $ulos .= sprintf('%02.2s', $laskurow["tyyppi"]); //laskun tyyppi 01-veloitus 02-hyvitys 03-viivästyskorkolasku jne...
      $ulos .= sprintf('%012.12s', $laskurow["summa"]); //summa etumerkitön, sentteinä
      $ulos .= sprintf('%06.6s', $laskurow["erpcm"]); //eräpäivä

      if ($laskurow["kasumma"] > 0) {
        $ulos .= sprintf('%06.6s', $laskurow["kapvm"]); //kassa-ale1 pvm
      }
      else {
        $ulos .= sprintf('%06.6s', 0);
      }

      $ulos .= sprintf('%06.6s', 0);
      $ulos .= sprintf('%06.6s', 0);
      $ulos .= sprintf('%06.6s', 0);

      if ($toim == 'SAMPO') {
        $ulos .= sprintf('%06.6s', 0);
        $ulos .= sprintf('%06.6s', 0); // Kassa-ale 6
      }
      elseif ($toim == 'AKTIA') {
        $ulos .= sprintf('%-12.12s', "");
      }
      else {
        $ulos .= sprintf('%012.12s', 0);
      }

      if ($laskurow["kasumma"] > 0) {
        $ulos .= sprintf('%012.12s', $laskurow["kasumma"]); //kassa-ale1 valuutassa
      }
      else {
        $ulos .= sprintf('%012.12s', 0);
      }

      $ulos .= sprintf('%012.12s', 0);
      $ulos .= sprintf('%012.12s', 0);
      $ulos .= sprintf('%012.12s', 0);

      if ($toim == 'SAMPO') {
        $ulos .= sprintf('%012.12s', 0);
        $ulos .= sprintf('%012.12s', 0); // Ale6 valuutta
      }
      elseif ($toim == 'AKTIA') {
        $ulos .= sprintf('%-24.24s', "");
      }
      else {
        $ulos .= sprintf('%024.24s', 0);
      }

      if ($laskurow["kasumma"] > 0 and $toim != "OKO") {
        $ulos .= sprintf('%01.1s', 1); //kassa-ale1 koodi 0-ei alennusta, 1-alennus
      }
      else {
        $ulos .= sprintf('%01.1s', 0);
      }

      $ulos .= sprintf('%01.1s', 0);
      $ulos .= sprintf('%01.1s', 0);
      $ulos .= sprintf('%01.1s', 0);

      if ($toim == 'SAMPO') {
        $ulos .= sprintf('%01.1s', 0);
        $ulos .= sprintf('%01.1s', 0); // Koodi 6 ...
      }
      elseif ($toim == 'AKTIA') {
        $ulos .= sprintf('%-2.2s', "");
      }
      else {
        $ulos .= sprintf('%02.2s', 0);
      }

      $ulos .= sprintf('%010.10s', 0);
      $ulos .= sprintf('%04.4s', 0); //alv (ei välitetä)


      if ($toim == "OKO") {
        $ulos .= sprintf('%-30.30s', ""); //toimituspaikan nimi
        $ulos .= sprintf('%06.6s', 0); //asiakasnro
        $ulos .= sprintf('%010.10s', 0); //toim  ytunnus
        $ulos .= sprintf('%-20.20s', ""); //toim osoite
        $ulos .= sprintf('%-20.20s', ""); //toim postitp ja postino
        $ulos .= sprintf('%-30.30s', "");
        $ulos .= sprintf('%-13.13s', "");
        $ulos .= sprintf('%-30.30s', "");
        $ulos .= sprintf('%06.6s', 0);
        $ulos .= sprintf('%03.3s', 1); //myyjän sopimustunnus
        $ulos .= sprintf('%-38.38s', "");
      }
      else {
        $ulos .= sprintf('%-30.30s', $laskurow["toim_nimi"]); //toimituspaikan nimi
        $ulos .= sprintf('%06.6s', $asirow["asiakasnro"]); //asiakasnro
        $ulos .= sprintf('%010.10s', str_replace('-', '', $laskurow["ytunnus"])); //toim  ytunnus
        $ulos .= sprintf('%-20.20s', $laskurow["toim_osoite"]); //toim osoite
        $ulos .= sprintf('%-20.20s', $laskurow["toim_postino"]." ".$laskurow["toim_postitp"]); //toim postitp ja postino
        $ulos .= sprintf('%-30.30s', "");

        if ($toim == 'AKTIA') {
          $ulos .= sprintf('%-13.13s', "");
        }
        else {
          $ulos .= sprintf('%013.13s', 0);
        }

        $ulos .= sprintf('%-30.30s', "");
        $ulos .= sprintf('%06.6s', 0);

        if ($toim == 'SAMPO') {
          $ulos .= sprintf('%-41.41s', ""); // Sampo, varalla
        }
        else {
          if ($laskurow["toim_maa"] != $yhtiorow["maa"] and $laskurow["toim_maa"] != '') {
            $ulos .= sprintf('%-10.10s', $laskurow["toim_maa"]);
          }
          else {
            $ulos .= sprintf('%-10.10s', "");
          }

          if ($toim == 'AKTIA') {
            $ulos .= sprintf('%-6.6s', ""); // Aktia Yritysrahoituksen ostajanumero, ei käytössä kotimaisilla asiakkailla
            $ulos .= sprintf('%-25.25s', "");
          }
          else {
            $ulos .= sprintf('%03.3s', 0);
            $ulos .= sprintf('%020.20s', $laskurow["viite"]);
            $ulos .= sprintf('%-8.8s', "");
          }
        }
      }

      $ulos .= "\r\n";

      // Käännetään takaisin UTF-8 muotoon
      if (PUPE_UNICODE) {
        array_walk($laskurow, "nordea_encode");
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
        echo "<td><font class='error'>VIRHE: Asiakasnumero: $asirow[asiakasnro] ei kelpaa!</font> <a href='".$palvelin2."yllapito.php?ojarj=&toim=asiakas&tunnus=$laskurow[liitostunnus]'>Muuta asiakkaan tietoja</a></td>";
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
      //luodaan summatietue
      //luodaan laskutietue
      if ($toim == "OKO") {
        $ulos .= sprintf('%-4.4s', "LA01"); //sovellustunnus
      }
      elseif ($toim == 'SAMPO') {
        $ulos .= sprintf('%-4.4s', "SAFA"); //sovellustunnus
      }
      elseif ($toim == 'AKTIA') {
        $ulos .= sprintf('%-4.4s', "AKF1"); //sovellustunnus
      }
      else {
        $ulos .= sprintf('%-4.4s', "KRFL"); //sovellustunnus
      }

      $ulos .= sprintf('%01.1s', 9);

      if (in_array($toim, array("SAMPO", "AKTIA"))) {
        $ulos .= sprintf('%017.17s', str_replace('-', '', $yhtiorow["ytunnus"]));
      }
      else {
        $ulos .= sprintf('%-17.17s', str_replace('-', '', $yhtiorow["ytunnus"]));
      }

      $ulos .= sprintf('%06.6s', $luontipvm);
      $ulos .= sprintf('%04.4s', $luontiaika);
      $ulos .= sprintf('%06.6s', $laskukpl);
      $ulos .= sprintf('%06.6s', $vlaskukpl);
      $ulos .= sprintf('%013.13s', $vlaskusum);
      $ulos .= sprintf('%06.6s', $hlaskukpl);
      $ulos .= sprintf('%013.13s', $hlaskusum);
      $ulos .= sprintf('%06.6s', 0);
      $ulos .= sprintf('%013.13s', 0);
      $ulos .= sprintf('%06.6s', 0);
      $ulos .= sprintf('%013.13s', 0);

      if ($toim == "OKO") {
        $ulos .= sprintf('%-286.286s', "");
      }
      elseif (in_array($toim, array("SAMPO", "AKTIA"))) {
        $ulos .= sprintf('%-286.286s', "");
      }
      else {
        $ulos .= sprintf('%013.13s', 0);
        $ulos .= sprintf('%-273.273s', "");
      }

      $ulos .= "\r\n";

      $sepayhteys = 0;
      $sepanimi = "";

      //keksitään uudelle failille joku hyvä nimi:
      if ($toim == "OKO") {
        $filenimi = "OKOsiirto-$factoringsiirtonumero.txt";
      }
      elseif ($toim == 'SAMPO') {
        $filenimi = "Samposiirto-$factoringsiirtonumero.txt";
      }
      elseif ($toim == 'AKTIA') {
        // Lähetetäänkö facotringaineisto Aktiaan?
        $pankkiyhteydet = hae_pankkiyhteydet();
        $tuetut_pankit = tuetut_pankit();

        foreach ($pankkiyhteydet as $pankkiyhteys) {
          if ($pankkiyhteys['pankki'] == "HELSFIHH") {
            $pankki = $tuetut_pankit[$pankkiyhteys['pankki']];

            $sepayhteys = $pankkiyhteys["tunnus"];
            $sepanimi = $pankki['lyhyt_nimi'];
            break;
          }
        }

        $filenimi = "Aktiasiirto-$factoringsiirtonumero.txt";
      }
      else {
        // Lähetetäänkö facotringaineisto Nordeaan?
        $pankkiyhteydet = hae_pankkiyhteydet();
        $tuetut_pankit = tuetut_pankit();

        foreach ($pankkiyhteydet as $pankkiyhteys) {
          if ($pankkiyhteys['pankki'] == "NDEAFIHH") {
            $pankki = $tuetut_pankit[$pankkiyhteys['pankki']];

            $sepayhteys = $pankkiyhteys["tunnus"];
            $sepanimi = $pankki['lyhyt_nimi'];
            break;
          }
        }

        $filenimi = "Nordeasiirto-$factoringsiirtonumero.txt";
      }

      //kirjoitetaan faili levylle..
      $fh = fopen("dataout/".$filenimi, "w");
      if (fwrite($fh, $ulos) === FALSE) die("Kirjoitus epäonnistui $filenimi");
      fclose($fh);

      echo "<tr><td class='back'><br></td></tr>";

      echo "<tr><td class='back' colspan='2'></td><th>Yhteensä $vlaskukpl veloituslaskua</th><td align='right'>".sprintf('%.2f', $vlaskusum/100)."</td><td>$laskurow[valkoodi]</td></tr>";
      echo "<tr><td class='back' colspan='2'></td><th>Yhteensä $hlaskukpl hyvityslaskua</th><td align='right'> ".sprintf('%.2f', $hlaskusum/100)."</td><td>$laskurow[valkoodi]</td></tr>";
      echo "<tr><td class='back' colspan='2'></td><th>Yhteensä</th><td align='right'> ".sprintf('%.2f', ($vlaskusum+($hlaskusum*-1))/100)."</td><td>$laskurow[valkoodi]</td></tr>";

      echo "</table>";
      echo "<br><br>";
      echo "<table>";
      echo "<tr><th>Tallenna siirtoaineisto levylle:</th>";
      echo "<form method='post' class='multisubmit'>";
      echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";

      if ($toim == "OKO") {
        echo "<input type='hidden' name='kaunisnimi' value='OKOMYSA.DAT'>";
      }
      elseif ($toim == "AKTIA") {
        echo "<input type='hidden' name='kaunisnimi' value='AKTIAMYSA.TXT'>";
      }
      else {
        echo "<input type='hidden' name='kaunisnimi' value='SOLOMYSA.DAT'>";
      }

      echo "<input type='hidden' name='filenimi' value='$filenimi'>";
      echo "<input type='hidden' name='toim' value='$toim'>";
      echo "<td><input type='submit' value='Tallenna'></td></form>";
      echo "</tr></table>";

      if ($sepayhteys and in_array($toim, array("", "AKTIA"))) {
        echo "<br><br><table>";
        echo "<tr><th>Siirrä aineisto pankkiin:</th>";
        echo "<form method='post' action=''>";
        echo "<input type='hidden' name='toim' value='$toim'/>";
        echo "<input type='hidden' name='tee' value='lahetasepa'/>";
        echo "<input type='hidden' name='pankkiyhteys_filenimi' value='$filenimi'>";
        echo "<input type='hidden' name='pankkiyhteys_pankki' value='$sepanimi'/>";
        echo "<input type='hidden' name='pankkiyhteys_tunnus' value='$sepayhteys'/>";
        echo "<td><input type='submit' value='Lähetä'></td></form>";
        echo "</tr></table>";
      }
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
