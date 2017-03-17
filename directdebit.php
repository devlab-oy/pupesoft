<?php

if (isset($_REQUEST["tee"]) and $_REQUEST["tee"] == 'lataa_tiedosto') {
  $lataa_tiedosto = 1;
}

require 'inc/parametrit.inc';

if ($tee == "lataa_tiedosto") {
  readfile("dataout/".$filenimi);
  exit;
}

echo "<font class='head'>".t("Direct Debit siirtotiedosto").":</font><hr><br>";

// Nordea SEPA Direct Debit (toimii vain EUR-valuutalla)
$factoringyhtio = "NORDEA_DD";
$valkoodi = "EUR";

$factoring_tarkista_lisa = "";

if ($tee == 'TARKISTA') {
  $tee = "TULOSTA";

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
  echo t("%s Direct Debit-sopimusta ei ole perustettu!", null, $factoringyhtio);

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

  echo t("Valitse Direct Debit-sopimus");
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
  echo "<tr><th>Valitse valuutta:</th><td>EUR</td></tr>";

  echo "<tr>
      <th>Syötä laskuvälin alku:</th>
      <td><input type='text' name='ppa' value='$arow[eka]' size='10'></td>
      </tr>
      <tr>
      <th>Syötä laskuvälin loppu:</th>
      <td><input type='text' name='ppl' value='$arow[vika]' size='10'></td>
      </tr>";

  $query = "SELECT max(lasku.factoringsiirtonumero)+1 seuraava
            FROM lasku use index (yhtio_tila_tapvm)
            JOIN maksuehto ON (lasku.yhtio = maksuehto.yhtio
              and lasku.maksuehto            = maksuehto.tunnus
              and maksuehto.factoring_id     = '$factoring_id')
            WHERE lasku.yhtio                = '$kukarow[yhtio]'
            and lasku.tila                   = 'U'
            and lasku.tapvm                  > date_sub(CURDATE(), interval 6 month)
            and lasku.alatila                = 'X'
            and lasku.summa                 != 0
            and lasku.factoringsiirtonumero  > 0";
  $aresult = pupe_query($query);
  $arow = mysql_fetch_assoc($aresult);

  echo "<tr><th>Siirtoluettelon numero:</th>
      <td><input type='text' name='dd_siirtonumero' value='$arow[seuraava]' size='6'></td>";

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

  $query = "SELECT *
            FROM factoring
            WHERE yhtio        = '$kukarow[yhtio]'
            and factoringyhtio = '$factoringyhtio'
            and tunnus         = '$factoring_id'
            and valkoodi       = '$valkoodi'";
  $fres = pupe_query($query);
  $frow = mysql_fetch_assoc($fres);

  echo "<tr><th>Sopimusnumero:</th><td>$frow[sopimusnumero]</td>";
  echo "<tr><th>Valitse valuutta:</th><td>EUR</td></tr>";

  echo "<tr><th>Siirtoluettelon numero:</th>
      <td><input type='text' name='dd_siirtonumero' value='$dd_siirtonumero' size='6'></td>";

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

  $xmlstr  = '<?xml version="1.0" encoding="UTF-8"?>';
  $xmlstr .= '<Document ';
  $xmlstr .= 'xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02" ';
  $xmlstr .= 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
  $xmlstr .= '</Document>';

  $xml = new SimpleXMLElement($xmlstr);

  $dd = $xml->addChild('CstmrDrctDbtInitn');
  $GrpHdr = $dd->addChild('GrpHdr');                                                   // GroupHeader
  $GrpHdr->addChild('MsgId', date('Ymd')."/".$dd_siirtonumero);                        // MessageIdentification, Text, Pakollinen kenttä
  $GrpHdr->addChild('CreDtTm', date('Y-m-d')."T".date('H:i:s'));                       // CreationDateTime, DateTime, Pakollinen kenttä
  $GrpHdr->addChild('NbOfTxs', 1);                                                     // NumberOfTransactions, Text, Pakollinen kenttä
  $GrpHdr->addChild('CtrlSum', 123456);                                                // Total of all individual amounts included in the message.

  $InitgPty = $GrpHdr->addChild('InitgPty');                                           // InitiatingParty, Pakollinen
  $InitgPty->addChild('Nm', sprintf("%-1.70s", $yhtiorow['nimi']));                    // Name 1-70
  $InitgPty->addChild('Id')->addChild('OrgId')->addChild('Othr')->addChild('id', $frow["sopimusnumero"]); // 'Palvelutunnus' or 'Intermediary code' informed by Nordea

  if ($ppl == '') {
    $ppl = $ppa;
  }

  if ($tee_u != 'UUDELLEENLUO' and ($ppa == '' or $ppl == '' or $ppl < $ppa)) {
    echo "Huono laskunumeroväli!";
    exit;
  }

  if ($tee_u == 'UUDELLEENLUO') {
    $where = " and lasku.factoringsiirtonumero = '$dd_siirtonumero' ";
  }
  else {
    $where = " and lasku.laskunro >= '$ppa'
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
    echo "<tr><th>Siirtoluettelon numero:</th><td>$dd_siirtonumero</td></tr></table><br>";

    echo "<table>";
    echo "<tr><th>Tyyppi</th><th>Laskunumero</th><th>Nimi</th><th>Summa</th><th>Valuutta</th></tr>";

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
                   SET lasku.factoringsiirtonumero = '$dd_siirtonumero'
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


      //keksitään uudelle failille joku hyvä nimi:
      $filenimi = "NordeaDirectDebit-$dd_siirtonumero.xml";

      //kirjoitetaan faili levylle..
      $fh = fopen("dataout/".$filenimi, "w");

      // Kirjoitetaaan XML, tehdään tästä jäsennelty aineisto. Tämä toimii paremmin mm OPn kanssa
      $dom = new DOMDocument('1.0');
      $dom->preserveWhiteSpace = true;
      $dom->formatOutput = true;
      $dom->loadXML(utf8_encode($xml->asXML()));
      fwrite($fh, ($dom->saveXML()));
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
      echo "<input type='hidden' name='filenimi' value='$filenimi'>";
      echo "<input type='hidden' name='kaunisnimi' value='$filenimi'>";
      echo "<td><input type='submit' value='Tallenna'></td></form>";
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
