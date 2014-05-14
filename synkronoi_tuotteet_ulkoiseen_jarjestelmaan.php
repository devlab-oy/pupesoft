<?php

require("inc/parametrit.inc");

if (!isset($tee)) $tee = '';

echo "<font class='head'>",t("Synkronoi tuotteet ulkoiseen j‰rjestelm‰‰n"),"</font><hr><br />";

if ($ftp_posten_logistik_host == '' or $ftp_posten_logistik_user == '' or $ftp_posten_logistik_pass == '' or $ftp_posten_logistik_path == '') {

  echo "<br /><font class='error'>",t("Tarvittavat FTP-tunnukset ovat puutteelliset"),"!</font><br>";

  require ("inc/footer.inc");
  exit;
}

$query = "  SELECT tuote.*, ta.selite AS synkronointi
      FROM tuote
      LEFT JOIN tuotteen_avainsanat AS ta ON (ta.yhtio = tuote.yhtio AND ta.tuoteno = tuote.tuoteno AND ta.laji = 'synkronointi' AND ta.selite != '')
      WHERE tuote.yhtio = '{$kukarow['yhtio']}'
      AND tuote.status != 'P'
      AND tuote.ei_saldoa = ''
      AND tuote.eankoodi != ''
      AND ta.selite IS NULL";
$res = pupe_query($query);

if (mysql_num_rows($res) > 0) {

  if ($tee == '') {

    echo "<font class='message'>",t("Tuotteet joita ei ole synkronoitu"),"</font><br />";
    echo "<font class='message'>",t("Yhteens‰ %d kappaletta", "", mysql_num_rows($res)),"</font><br /><br />";

    echo "<form action='' method='post'>";
    echo "<table>";
    echo "<tr><td class='back' colspan='2'>";
    echo "<input type='submit' name='tee' value='",t("L‰het‰"),"' />";
    echo "</td></tr>";
    echo "<tr>";
    echo "<th>",t("Tuotenumero"),"</th>";
    echo "<th>",t("Nimitys"),"</th>";
    echo "</tr>";
  }
  else {

    $xml = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><Message></Message>");

    $messageheader = $xml->addChild('MessageHeader');
    $messageheader->addChild('MessageType', 'MaterialMaster');
    $messageheader->addChild('Sender', 'Makia');
    $messageheader->addChild('Receiver', 'Posten');

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

      $line->addChild('Type', 'U');
      $line->addChild('ItemNumber', substr($row['eankoodi'], 0, 20));
      $line->addChild('ItemName', substr($row['nimitys'], 0, 50));
      $line->addChild('ProdGroup1', substr($row['try'], 0, 6));
      $line->addChild('ProdGroup2', 0);
      $line->addChild('SalesPrice', 0);
      $line->addChild('Unit1', substr($row['yksikko'], 0, 10));
      $line->addChild('Unit2', 0);
      $line->addChild('Relation', 0);
      $line->addChild('Weight', round($row['tuotemassa'], 3));
      $line->addChild('NetWeight', 0);
      $line->addChild('Volume', 0);
      $line->addChild('Height', 0);
      $line->addChild('Width', 0);
      $line->addChild('Length', 0);
      $line->addChild('PackageSize', 0);
      $line->addChild('PalletSize', 0);
      $line->addChild('Status', 0);
      $line->addChild('WholesalePackageSize', 0);
      $line->addChild('EANCode', substr($row['eankoodi'], 0, 20));
      $line->addChild('EANCode2', 0);
      $line->addChild('CustomsTariffNum', 0);
      $line->addChild('AlarmLimit', 0);
      $line->addChild('QualPeriod1', 0);
      $line->addChild('QualPeriod2', 0);
      $line->addChild('QualPeriod3', 0);
      $line->addChild('FactoryNum', 0);
      $line->addChild('UNCode', 0);
      $line->addChild('BBDateCollect', 0);
      $line->addChild('SerialNumbers', 0);
      $line->addChild('SerialNumInArrival', 0);
      $line->addChild('TaxCode', 0);
      $line->addChild('CountryofOrigin', 0);
      $line->addChild('PlatformQuantity', 0);
      $line->addChild('PlatformType', 0);
      $line->addChild('PurchasePrice', 0);
      $line->addChild('ConsumerPrice', 0);
      $line->addChild('OperRecommendation', 0);
      $line->addChild('FreeText', substr($row['tuoteno'], 0, 100));
      $line->addChild('PurchaseUnit', 0);
      $line->addChild('ManufactItemNum', 0);
      $line->addChild('InternationalItemNum', 0);
      $line->addChild('Flashpoint', 0);
      $line->addChild('SalesCurrency', 0);
      $line->addChild('PurchaseCurrency', 0);
      $line->addChild('Model', 0);
      $line->addChild('ModelOrder', 0);
      $line->addChild('TransportTemperature', 0);

      if (is_null($row['synkronointi'])) {

        $query = "  INSERT INTO tuotteen_avainsanat SET
              yhtio = '{$kukarow['yhtio']}',
              tuoteno = '{$row['tuoteno']}',
              kieli = '{$yhtiorow['kieli']}',
              laji = 'synkronointi',
              selite = 'x',
              laatija = '{$kukarow['kuka']}',
              luontiaika = now(),
              muutospvm = now(),
              muuttaja = '{$kukarow['kuka']}'";
        pupe_query($query);

      }
      else {

        $query = "  UPDATE tuotteen_avainsanat SET
              selite = 'x'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tuoteno = '{$row['tuoteno']}'
              AND laji = 'synkronointi'";
        pupe_query($query_dump());
      }

      $i++;
    }
  }

  if ($tee == '') {

    echo "<tr><td class='back' colspan='2'>";
    echo "<input type='submit' name='tee' value='",t("L‰het‰"),"' />";
    echo "</td></tr>";
    echo "</table>";
    echo "</form>";
  }
  else {

    $filename = $pupe_root_polku."/dataout/materialmaster_".md5(uniqid()).".xml";

    if (file_put_contents($filename, utf8_encode($xml->asXML()))) {
      echo "<br /><font class='message'>",t("Tiedoston luonti onnistui"),"</font><br />";

      $ftphost = $ftp_posten_logistik_host;
      $ftpuser = $ftp_posten_logistik_user;
      $ftppass = $ftp_posten_logistik_pass;
      $ftppath = $ftp_posten_logistik_path;
      $ftpfile = realpath($filename);

      require ("inc/ftp-send.inc");
    }
    else {
      echo "<br /><font class='error'>",t("Tiedoston luonti ep‰onnistui"),"</font><br />";
    }
  }
}
else {
  echo "<font class='message'>",t("Kaikki tuotteet ovat synkronoitu"),"</font><br />";
}

require ("inc/footer.inc");
