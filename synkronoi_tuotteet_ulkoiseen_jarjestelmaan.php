<?php

require "inc/parametrit.inc";

if (!isset($tee)) $tee = '';

echo "<font class='head'>", t("Synkronoi tuotteet ulkoiseen j‰rjestelm‰‰n"), "</font><hr><br />";

if (!isset($ulkoinen_jarjestelma) or empty($ulkoinen_jarjestelma)) {
  echo "<form action='' method='post'>";
  echo "<table>";
  echo "<tr>";
  echo "<th>", t("Valitse ulkoinen j‰rjestelm‰"), "</th>";
  echo "<td>";
  echo "<select name='ulkoinen_jarjestelma'>";
  echo "<option value='P'>PostNord</option>";
  echo "<option value='L'>Helsingin Hyllyvarasto</option>";
  echo "</select>";
  echo "</td>";
  echo "<td>";
  echo "<button type='submit' name='tee' value=''>", t("L‰het‰"), "</button>";
  echo "</td>";
  echo "</tr>";
  echo "</table>";
  echo "</form>";

  require "inc/footer.inc";
  exit;
}
else {

  if (((empty($ftp_posten_logistik_host) or empty($ftp_posten_logistik_user) or empty($ftp_posten_logistik_pass) or empty($ftp_posten_logistik_path)) and $ulkoinen_jarjestelma == 'P') or
    ((empty($ftp_logmaster_host) or empty($ftp_logmaster_user) or empty($ftp_logmaster_pass) or empty($ftp_logmaster_path)) and $ulkoinen_jarjestelma == 'L')) {

    echo "<br /><font class='error'>", t("Tarvittavat FTP-tunnukset ovat puutteelliset"), "!</font><br>";

    require "inc/footer.inc";
    exit;
  }

  if ($ulkoinen_jarjestelma == "P") {
    $wherelisa = "AND tuote.eankoodi  != ''";
  }
  else {
    $wherelisa = "";
  }

  $query = "SELECT tuote.*, ta.selite AS synkronointi, ta.tunnus AS ta_tunnus
            FROM tuote
            LEFT JOIN tuotteen_avainsanat AS ta ON (ta.yhtio = tuote.yhtio AND ta.tuoteno = tuote.tuoteno AND ta.laji = 'synkronointi')
            WHERE tuote.yhtio   = '{$kukarow['yhtio']}'
            AND tuote.ei_saldoa = ''
            AND tuote.tuotetyyppi NOT IN ('A', 'B')
            {$wherelisa}
            HAVING (ta.tunnus IS NOT NULL AND ta.selite = '') OR
                    # jos avainsanaa ei ole olemassa ja status P niin ei haluta n‰it‰ tuotteita jatkossakaan
                   (ta.tunnus IS NULL AND tuote.status != 'P')";
  $res = pupe_query($query);

  if (mysql_num_rows($res) > 0) {

    if ($tee == '') {

      echo "<font class='message'>", t("Tuotteet joita ei ole synkronoitu"), "</font><br />";
      echo "<font class='message'>", t("Yhteens‰ %d kappaletta", "", mysql_num_rows($res)), "</font><br /><br />";

      echo "<form action='' method='post'>";
      echo "<input type='hidden' name='ulkoinen_jarjestelma' value='{$ulkoinen_jarjestelma}' />";
      echo "<table>";
      echo "<tr><td class='back' colspan='2'>";
      echo "<input type='submit' name='tee' value='", t("L‰het‰"), "' />";
      echo "</td></tr>";
      echo "<tr>";
      echo "<th>", t("Tuotenumero"), "</th>";
      echo "<th>", t("Nimitys"), "</th>";
      echo "</tr>";
    }
    else {

      $xml = simplexml_load_string("<?xml version='1.0' encoding='UTF-8'?><Message></Message>");

      $messageheader = $xml->addChild('MessageHeader');
      $messageheader->addChild('MessageType', 'MaterialMaster');
      $messageheader->addChild('Sender', xml_cleanstring($yhtiorow['nimi']));

      if ($ulkoinen_jarjestelma == 'L') {
        $uj_nimi = "LogMaster";
      }
      else {
        $uj_nimi = "Posten";
      }

      $messageheader->addChild('Receiver', $uj_nimi);

      $iteminformation = $xml->addChild('ItemInformation');
      $iteminformation->addChild('TransDate', date('d-m-Y'));
      $iteminformation->addChild('TransTime', date('H:i:s'));

      $items = $iteminformation->addChild('Items');

      $i = 1;
    }

    while ($row = mysql_fetch_assoc($res)) {

      if ($tee == '') {

        echo "<tr>";
        echo "<td>{$row['tuoteno']}</td>";
        echo "<td>{$row['nimitys']}</td>";
        echo "</tr>";
      }
      else {

        $line = $items->addChild('Line');
        $line->addAttribute('No', $i);

        if (!is_null($row['synkronointi']) and $row['synkronointi'] == '') {
          $type = 'M';
        }
        else {
          $type = 'U';
        }

        $line->addChild('Type', $type);

        $posten_itemnumberfield = posten_field('ItemNumber');
        $posten_prodgroup1field = posten_field('ProdGroup1');
        $posten_prodgroup2field = posten_field('ProdGroup2');

        $line->addChild('ItemNumber', xml_cleanstring($row[$posten_itemnumberfield], 20));
        $line->addChild('ItemName',   xml_cleanstring($row['nimitys'], 50));
        $line->addChild('ProdGroup1', xml_cleanstring($row[$posten_prodgroup1field], 6));
        $line->addChild('ProdGroup2', xml_cleanstring($row[$posten_prodgroup2field], 6));
        $line->addChild('SalesPrice', '');
        $line->addChild('Unit1', xml_cleanstring($row['yksikko'], 10));
        $line->addChild('Unit2', '');
        $line->addChild('Relation', '');
        $line->addChild('Weight', round($row['tuotemassa'], 3));
        $line->addChild('NetWeight', '');
        $line->addChild('Volume', '');
        $line->addChild('Height', '');
        $line->addChild('Width', '');
        $line->addChild('Length', '');
        $line->addChild('PackageSize', '');
        $line->addChild('PalletSize', '');

        switch ($row['status']) {
        case 'A':
          $status = 1;
          break;
        case 'P':
          $status = 9;
          break;
        default:
          $status = 0;
          break;
        }

        $line->addChild('Status', $status);
        $line->addChild('WholesalePackageSize', '');
        $line->addChild('EANCode', xml_cleanstring($row['eankoodi'], 20));
        $line->addChild('EANCode2', '');
        $line->addChild('CustomsTariffNum', '');
        $line->addChild('AlarmLimit', '');
        $line->addChild('QualPeriod1', '');
        $line->addChild('QualPeriod2', '');
        $line->addChild('QualPeriod3', '');
        $line->addChild('FactoryNum', '');
        $line->addChild('UNCode', '');
        $line->addChild('BBDateCollect', '');
        $line->addChild('SerialNumbers', '');
        $line->addChild('SerialNumInArrival', '');
        $line->addChild('TaxCode', '');
        $line->addChild('CountryofOrigin', '');
        $line->addChild('PlatformQuantity', '');
        $line->addChild('PlatformType', '');
        $line->addChild('PurchasePrice', '');
        $line->addChild('ConsumerPrice', '');
        $line->addChild('OperRecommendation', '');
        $line->addChild('FreeText', xml_cleanstring($row['tuoteno'], 100));
        $line->addChild('PurchaseUnit', '');
        $line->addChild('ManufactItemNum', '');
        $line->addChild('InternationalItemNum', '');
        $line->addChild('Flashpoint', '');
        $line->addChild('SalesCurrency', '');
        $line->addChild('PurchaseCurrency', '');
        $line->addChild('Model', '');
        $line->addChild('ModelOrder', '');
        $line->addChild('TransportTemperature', '');

        if (is_null($row['synkronointi'])) {

          $query = "INSERT INTO tuotteen_avainsanat SET
                    yhtio      = '{$kukarow['yhtio']}',
                    tuoteno    = '{$row['tuoteno']}',
                    kieli      = '{$yhtiorow['kieli']}',
                    laji       = 'synkronointi',
                    selite     = 'x',
                    laatija    = '{$kukarow['kuka']}',
                    luontiaika = now(),
                    muutospvm  = now(),
                    muuttaja   = '{$kukarow['kuka']}'";
          pupe_query($query);

        }
        else {

          $query = "UPDATE tuotteen_avainsanat SET
                    selite      = 'x'
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tuoteno = '{$row['tuoteno']}'
                    AND laji    = 'synkronointi'";
          pupe_query($query);
        }

        $i++;
      }
    }

    if ($tee == '') {
      echo "<tr><td class='back' colspan='2'>";
      echo "<input type='submit' name='tee' value='", t("L‰het‰"), "' />";
      echo "</td></tr>";
      echo "</table>";
      echo "</form>";
    }
    else {

      $_name = substr("tuote_".md5(uniqid()), 0, 25);
      $filename = $pupe_root_polku."/dataout/{$_name}.xml";

      if (file_put_contents($filename, $xml->asXML())) {
        echo "<br /><font class='message'>", t("Tiedoston luonti onnistui"), "</font><br />";

        switch ($ulkoinen_jarjestelma) {
        case 'L':
          $ftphost = $ftp_logmaster_host;
          $ftpuser = $ftp_logmaster_user;
          $ftppass = $ftp_logmaster_pass;
          $ftppath = $ftp_logmaster_path;
          $ftpfile = realpath($filename);
          break;
        case 'P':
          $ftphost = $ftp_posten_logistik_host;
          $ftpuser = $ftp_posten_logistik_user;
          $ftppass = $ftp_posten_logistik_pass;
          $ftppath = $ftp_posten_logistik_path;
          $ftpfile = realpath($filename);
          break;
        default:
          echo "<br /><font class='error'>", t("Tarvittavat FTP-tunnukset ovat puutteelliset"), "!</font><br>";

          require "inc/footer.inc";
          exit;
          break;
        }

        // L‰hetet‰‰n aina UTF-8 muodossa
        $ftputf8 = true;

        require "inc/ftp-send.inc";
      }
      else {
        echo "<br /><font class='error'>", t("Tiedoston luonti ep‰onnistui"), "</font><br />";
      }
    }
  }
  else {
    echo "<font class='message'>", t("Kaikki tuotteet ovat synkronoitu"), "</font><br />";
  }

  require "inc/footer.inc";
}
