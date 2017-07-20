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
$directdebityhtio = "NORDEA";
$valkoodi = "EUR";

$directdebit_tarkista_lisa = "";

if ($tee == 'TARKISTA') {
  $tee = "TULOSTA";

  // lisätään tämä queryyn alle, niin ei ikinä päästä eteenpäin, jos directdebit_id on virheellinen
  $directdebit_tarkista_lisa = " and tunnus = '$directdebit_id' ";
}

$query = "SELECT *
          FROM directdebit
          WHERE yhtio = '{$kukarow["yhtio"]}'
          and rahalaitos = '{$directdebityhtio}'
          {$directdebit_tarkista_lisa}";
$directdebit_result = pupe_query($query);

if (mysql_num_rows($directdebit_result) == 0) {
  echo t("%s Direct Debit-sopimusta ei ole perustettu!", null, $directdebityhtio);
  $tee = "ohita";
}
elseif (mysql_num_rows($directdebit_result) == 1) {
  // meillä on vaan yksi, ei tarvitse valita
  $vrow = mysql_fetch_assoc($directdebit_result);
  $directdebit_id = $vrow['tunnus'];
  $tee = isset($tee) ? $tee : 'TOIMINNOT';
}

if ($tee == '') {
  //Käyttöliittymä
  echo "<form method='post'>";
  echo "<input type='hidden' name='tee' value='TOIMINNOT'>";

  echo t("Valitse Direct Debit-sopimus");
  echo " <select name='directdebit_id' onchange='submit();'>";
  echo "<option value=''></option>";

  while ($vrow = mysql_fetch_assoc($directdebit_result)) {
    $sel = ($vrow['tunnus'] == $directdebit_id) ? "selected" : "";
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
  echo "<input type='hidden' name='directdebit_id' value='$directdebit_id'>";
  echo "<input type='hidden' name='tee' value='TARKISTA'>";

  $query = "SELECT *
            FROM directdebit
            WHERE yhtio = '$kukarow[yhtio]'
            and rahalaitos = '$directdebityhtio'
            and tunnus = '$directdebit_id'";
  $fres = pupe_query($query);
  $frow = mysql_fetch_assoc($fres);

  $query = "SELECT min(laskunro) eka, max(laskunro) vika
            FROM lasku use index (yhtio_tila_tapvm)
            JOIN maksuehto ON (lasku.yhtio = maksuehto.yhtio
              and lasku.maksuehto = maksuehto.tunnus
              and maksuehto.directdebit_id = '$directdebit_id')
            WHERE lasku.yhtio = '$kukarow[yhtio]'
            and lasku.tila = 'U'
            and lasku.tapvm > date_sub(CURDATE(), interval 6 month)
            and lasku.alatila = 'X'
            and lasku.summa != 0
            and lasku.directdebitsiirtonumero = 0
            and lasku.valkoodi = '$valkoodi'
            and lasku.summa > 0";
  $aresult = pupe_query($query);
  $arow = mysql_fetch_assoc($aresult);

  $query = "SELECT nimi, tunnus
            FROM valuu
            WHERE yhtio = '$kukarow[yhtio]'
            ORDER BY jarjestys";
  $vresult = pupe_query($query);

  echo "<tr><th>Palvelutunnus:</th><td>$frow[palvelutunnus]</td>";
  echo "<tr><th>Valitse valuutta:</th><td>EUR</td></tr>";

  echo "<tr>
      <th>Syötä laskuvälin alku:</th>
      <td><input type='text' name='ppa' value='$arow[eka]' size='10'></td>
      </tr>
      <tr>
      <th>Syötä laskuvälin loppu:</th>
      <td><input type='text' name='ppl' value='$arow[vika]' size='10'></td>
      </tr>";

  $query = "SELECT max(SUBSTRING(lasku.directdebitsiirtonumero, 5)) + 1 seuraava
            FROM lasku use index (yhtio_tila_tapvm)
            JOIN maksuehto ON (lasku.yhtio = maksuehto.yhtio
              and lasku.maksuehto = maksuehto.tunnus
              and maksuehto.directdebit_id = '$directdebit_id')
            WHERE lasku.yhtio = '$kukarow[yhtio]'
            and lasku.tila = 'U'
            and lasku.tapvm > date_sub(CURDATE(), interval 6 month)
            and year(lasku.tapvm) = year(curdate())
            and lasku.alatila = 'X'
            and lasku.summa != 0
            and lasku.directdebitsiirtonumero > 0
            and lasku.summa > 0";
  $aresult = pupe_query($query);
  $arow = mysql_fetch_assoc($aresult);

  if (empty($arow["seuraava"])) {
    $arow["seuraava"] = 1;
  }

  echo "<tr><th>Siirtoluettelon numero:</th>
      <td><input type='text' name='dd_siirtonumero' value='".date("Y")."$arow[seuraava]' size='10'></td>";

  echo "<td class='back'><input type='submit' value='Luo siirtoaineisto'></td></tr></form></table><br><br>";


  //Käyttöliittymä
  echo "<br>";
  echo "<form method='post'>";
  echo "Uudelleenluo siirtotiedosto<br>";
  echo "<table>";
  echo "<input type='hidden' name='toim' value='$toim'>";
  echo "<input type='hidden' name='directdebit_id' value='$directdebit_id'>";
  echo "<input type='hidden' name='tee' value='TARKISTA'>";
  echo "<input type='hidden' name='tee_u' value='UUDELLEENLUO'>";

  $query = "SELECT *
            FROM directdebit
            WHERE yhtio = '$kukarow[yhtio]'
            and rahalaitos = '$directdebityhtio'
            and tunnus = '$directdebit_id'";
  $fres = pupe_query($query);
  $frow = mysql_fetch_assoc($fres);

  echo "<tr><th>Palvelutunnus:</th><td>$frow[palvelutunnus]</td>";
  echo "<tr><th>Valitse valuutta:</th><td>EUR</td></tr>";

  echo "<tr><th>Siirtoluettelon numero:</th>
      <td><input type='text' name='dd_siirtonumero' value='$dd_siirtonumero' size='6'></td>";

  echo "<td class='back'><input type='submit' value='Uudelleenluo siirtoaineisto'></td></tr></form></table><br><br>";
}

if ($tee == 'TULOSTA') {

  $luontipvm  = date("ymd");
  $luontiaika  = date("Hi");

  $query = "SELECT *
            FROM directdebit
            WHERE yhtio = '$kukarow[yhtio]'
            and rahalaitos = '$directdebityhtio'
            and tunnus = '$directdebit_id'";
  $fres = pupe_query($query);
  $frow = mysql_fetch_assoc($fres);

  $xmlstr  = '<?xml version="1.0" encoding="UTF-8"?>';
  $xmlstr .= '<Document ';
  $xmlstr .= 'xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02" ';
  $xmlstr .= 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
  $xmlstr .= '</Document>';

  $xml = new SimpleXMLElement($xmlstr);

  $dd = $xml->addChild('CstmrDrctDbtInitn');
  $GrpHdr = $dd->addChild('GrpHdr');                                   // GroupHeader
  $GrpHdr->addChild('MsgId', $dd_siirtonumero);                        // MessageIdentification, Text, Pakollinen kenttä
  $GrpHdr->addChild('CreDtTm', date('Y-m-d')."T".date('H:i:s'));       // CreationDateTime, DateTime, Pakollinen kenttä
  $NumberOfTransactions = $GrpHdr->addChild('NbOfTxs', 0);             // NumberOfTransactions, Text, Pakollinen kenttä
  $ControlSum = $GrpHdr->addChild('CtrlSum', 0);                       // Total of all individual amounts included in the message.

  $InitgPty = $GrpHdr->addChild('InitgPty');                           // InitiatingParty, Pakollinen
  $InitgPty->addChild('Nm', sprintf("%-1.70s", $yhtiorow['nimi']));    // Name 1-70
  $Othr = $InitgPty->addChild('Id')->addChild('OrgId')->addChild('Othr'); // 'Palvelutunnus' or 'Intermediary code' informed by Nordea
  $Othr->addChild('Id', $frow["palvelutunnus"]);
  $Othr->addChild('SchmeNm')->addChild('Cd', 'CUST');

  if ($ppl == '') {
    $ppl = $ppa;
  }

  if ($tee_u != 'UUDELLEENLUO' and ($ppa == '' or $ppl == '' or $ppl < $ppa)) {
    echo "Huono laskunumeroväli!";
    exit;
  }

  if ($tee_u == 'UUDELLEENLUO') {
    $where = " and lasku.directdebitsiirtonumero = '$dd_siirtonumero' ";
  }
  else {
    $where = " and lasku.laskunro >= '$ppa'
               and lasku.laskunro <= '$ppl'
               and lasku.directdebitsiirtonumero = 0 ";
  }

  $dquery = "SELECT lasku.yhtio
             FROM lasku
             JOIN maksuehto ON (lasku.yhtio = maksuehto.yhtio
              and lasku.maksuehto = maksuehto.tunnus
              and maksuehto.directdebit_id = '$directdebit_id')
             WHERE lasku.yhtio = '$kukarow[yhtio]'
             and lasku.tila = 'U'
             and lasku.alatila = 'X'
             and lasku.summa != 0
             and lasku.valkoodi = '$valkoodi'
             and lasku.summa > 0
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
            lasku.summa,
            lasku.toim_nimi,
            lasku.toim_nimitark,
            lasku.toim_osoite,
            lasku.toim_postino,
            lasku.toim_postitp,
            lasku.toim_maa,
            lasku.maa,
            lasku.viite,
            DATE_FORMAT(lasku.tapvm, '%y%m%d') tapvm,
            lasku.erpcm,
            DATE_FORMAT(lasku.kapvm, '%y%m%d') kapvm,
            lasku.tunnus,
            lasku.valkoodi,
            lasku.liitostunnus
            FROM lasku
            JOIN maksuehto ON (lasku.yhtio = maksuehto.yhtio
              and lasku.maksuehto = maksuehto.tunnus
              and maksuehto.directdebit_id  = '$directdebit_id')
            WHERE lasku.yhtio = '$kukarow[yhtio]'
            and lasku.tila = 'U'
            and lasku.alatila = 'X'
            and lasku.summa != 0
            and lasku.valkoodi = '$valkoodi'
            and lasku.summa > 0
            $where
            ORDER BY laskunro";
  $laskures = pupe_query($query);

  if (mysql_num_rows($laskures) > 0) {

    $laskukpl = 0;
    $laskusum = 0;

    $hlaskukpl = 0;
    $hlaskusum = 0;
    $laskuvirh = 0;

    echo "<table>";
    echo "<tr><th>Päivämäärä:</th><td>".date("d.m.Y")."</td>";
    echo "<tr><th>Palvelutunnus:</th><td>{$frow["palvelutunnus"]}</td>";
    echo "<tr><th>Siirtoluettelon numero:</th><td>$dd_siirtonumero</td></tr></table><br>";

    echo "<table>";
    echo "<tr><th>Tyyppi</th><th>Laskunumero</th><th>Nimi</th><th>Summa</th><th>Valuutta</th></tr>";

    $y_vatnumero = tulosta_ytunnus(preg_replace("/^0037/", "", $yhtiorow["ovttunnus"]), "FI", "VATNUMERO");

    while ($laskurow = mysql_fetch_assoc($laskures)) {

      // Haetaan asiakkaan tiedot
      $query  = "SELECT *
                 FROM asiakas
                 WHERE yhtio = '$kukarow[yhtio]'
                 and tunnus  = '$laskurow[liitostunnus]'";
      $asires = pupe_query($query);
      $asirow = mysql_fetch_assoc($asires);

      $query  = "SELECT *
                 FROM directdebit_asiakas
                 WHERE yhtio = '$kukarow[yhtio]'
                 and liitostunnus = '$asirow[tunnus]'
                 and directdebit_id = '$frow[tunnus]'";
      $dd_asires = pupe_query($query);
      $dd_asirow = mysql_fetch_assoc($dd_asires);

      if (empty($dd_asirow["valtuutus_id"])) {
        $laskuvirh++;
      }

      echo "<tr>";

      $laskukpl++;
      $laskusum += $laskurow["summa"];

      echo "<td>Veloituslasku</td><td>$laskurow[laskunro]</td><td>$laskurow[nimi]</td><td align='right'>".sprintf('%.2f', $laskurow["summa"])."</td><td>$laskurow[valkoodi]</td>";

      if (empty($dd_asirow["valtuutus_id"])) {
        echo "<td><font class='error'>VIRHE: Valtuutus_id: {$dd_asirow["Valtuutus_id"]} ei kelpaa!</font> <a href='".$palvelin2."yllapito.php?ojarj=&toim=asiakas&tunnus=$laskurow[liitostunnus]'>Muuta asiakkaan tietoja</a></td>";
      }

      echo "</tr>";

      $PmtInf = $dd->addChild('PmtInf');
      $PmtInf->addChild('PmtInfId', "PMT/".$laskurow["tapvm"]."/".$laskurow["laskunro"]);
      $PmtInf->addChild('PmtMtd', 'DD');
      $PmtInf->addChild('NbOfTxs', '1');

      $PmtTpInf = $PmtInf->addChild('PmtTpInf');

      $SvcLvl = $PmtTpInf->addChild('SvcLvl');
      $SvcLvl->addChild('Cd', 'SEPA');

      $LclInstrm = $PmtTpInf->addChild('LclInstrm');
      $LclInstrm->addChild('Cd', 'B2B');

      $PmtTpInf->addChild('SeqTp', 'RCUR');

      $PmtInf->addChild('ReqdColltnDt', $laskurow["erpcm"]);

      $Cdtr = $PmtInf->addChild('Cdtr');
      $Cdtr->addChild('Nm', sprintf("%-1.70s", $yhtiorow['nimi']));

      $CdtrAcct = $PmtInf->addChild('CdtrAcct');
      $Id = $CdtrAcct->addChild('Id');
      $Id->addChild('IBAN', preg_replace("/[^a-z0-9]/i", "", $yhtiorow["pankkiiban1"]));

      $CdtrAgt = $PmtInf->addChild('CdtrAgt');
      $FinInstnId = $CdtrAgt->addChild('FinInstnId');
      $FinInstnId->addChild('BIC', $yhtiorow["pankkiswift1"]);

      $PmtInf->addChild('ChrgBr', 'SLEV');

      $CdtrSchmeId = $PmtInf->addChild('CdtrSchmeId');
      $Id = $CdtrSchmeId->addChild('Id');
      $PrvtId = $Id->addChild('PrvtId');
      $Othr = $PrvtId->addChild('Othr');
      $Othr->addChild('Id', $frow['suoraveloitusmandaatti']);
      $SchmeNm = $Othr->addChild('SchmeNm');
      $SchmeNm->addChild('Prtry', 'SEPA');

      $DrctDbtTxInf = $PmtInf->addChild('DrctDbtTxInf');
      $PmtId = $DrctDbtTxInf->addChild('PmtId');
      $PmtId->addChild('InstrId', "INV/".$laskurow["tapvm"]."/".$laskurow["laskunro"]);
      $PmtId->addChild('EndToEndId', "INV/".$laskurow["tapvm"]."/".$laskurow["laskunro"]);

      $InstdAmt = $DrctDbtTxInf->addChild('InstdAmt', sprintf("%.2f", $laskurow["summa"]));
      $InstdAmt->addAttribute('Ccy', 'EUR');

      $DrctDbtTx = $DrctDbtTxInf->addChild('DrctDbtTx');
      $MndtRltdInf = $DrctDbtTx->addChild('MndtRltdInf');

      $MndtRltdInf->addChild('MndtId', $dd_asirow['valtuutus_id']);
      $MndtRltdInf->addChild('DtOfSgntr', $dd_asirow['valtuutus_pvm']);
      $MndtRltdInf->addChild('AmdmntInd', 'false');
      #$MndtRltdInf->addChild('ElctrncSgntr', '');

      $DbtrAgt = $DrctDbtTxInf->addChild('DbtrAgt');
      $FinInstnId = $DbtrAgt->addChild('FinInstnId');
      $FinInstnId->addChild('BIC', $dd_asirow['maksajan_swift']);

      $Dbtr = $DrctDbtTxInf->addChild('Dbtr');
      $Dbtr->addChild('Nm', $laskurow["nimi"]);

      $DbtrAcct = $DrctDbtTxInf->addChild('DbtrAcct');
      $Id = $DbtrAcct->addChild('Id');
      $Id->addChild('IBAN', $dd_asirow['maksajan_iban']);

      $RmtInf = $DrctDbtTxInf->addChild('RmtInf');

      $Strd = $RmtInf->addChild('Strd');

      $CdtrRefInf = $Strd->addChild('CdtrRefInf');
      $Tp = $CdtrRefInf->addChild('Tp');
      $CdOrPrtry = $Tp->addChild('CdOrPrtry');
      $CdOrPrtry->addChild('Cd', 'SCOR');
      $CdtrRefInf->addChild('Ref', sprintf("%-1.35s", $laskurow['viite']));
    }

    if ($laskuvirh > 0) {
      echo "</table>";
      echo "<br><br>";
      echo "Aineistossa oli virheitä! Korjaa ne ja aja uudestaan!";
    }
    else {
      // Fix header values
      $NumberOfTransactions[0] = $laskukpl;
      $ControlSum[0] = $laskusum;

      if ($tee_u != 'UUDELLEENLUO') {
        $dquery = "UPDATE lasku, maksuehto
                   SET lasku.directdebitsiirtonumero = '$dd_siirtonumero'
                   WHERE lasku.yhtio                = '$kukarow[yhtio]'
                   and lasku.tila                   = 'U'
                   and lasku.alatila                = 'X'
                   and lasku.summa                 != 0
                   and lasku.laskunro               >= '$ppa'
                   and lasku.laskunro               <= '$ppl'
                   and lasku.directdebitsiirtonumero  = 0
                   and lasku.valkoodi               = '$valkoodi'
                   and lasku.yhtio                  = maksuehto.yhtio
                   and lasku.maksuehto              = maksuehto.tunnus
                   and maksuehto.directdebit_id       = '$directdebit_id'";
        pupe_query($dquery);
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

      echo "<tr><td class='back' colspan='2'></td><th>Yhteensä $laskukpl veloituslaskua</th><td align='right'>".sprintf('%.2f', $laskusum)."</td><td>$laskurow[valkoodi]</td></tr>";

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
