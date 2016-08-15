<?php

require "../../inc/parametrit.inc";
require "rajapinnat/logmaster/logmaster-functions.php";

if (!isset($tee)) $tee = '';

echo "<font class='head'>", t("Synkronoi tuotteet ulkoiseen j‰rjestelm‰‰n"), "</font><hr><br />";

if (empty($ulkoinen_jarjestelma)) {
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

$logmaster_itemnumberfield = logmaster_field('ItemNumber');
$logmaster_prodgroup1field = logmaster_field('ProdGroup1');
$logmaster_prodgroup2field = logmaster_field('ProdGroup2');

$query = "SELECT tuote.*, ta.selite AS synkronointi, ta.tunnus AS ta_tunnus
          FROM tuote
          LEFT JOIN tuotteen_avainsanat AS ta ON (ta.yhtio = tuote.yhtio AND ta.tuoteno = tuote.tuoteno AND ta.laji = 'synkronointi')
          WHERE tuote.yhtio   = '{$kukarow['yhtio']}'
          AND tuote.ei_saldoa = ''
          AND tuote.tuotetyyppi NOT IN ('A', 'B')
          AND tuote.{$logmaster_itemnumberfield} != ''
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
    if ($ulkoinen_jarjestelma == 'L') {
      $uj_nimi = "LogMaster";
    }
    else {
      $uj_nimi = "Posten";
    }

    $xml = simplexml_load_string("<?xml version='1.0' encoding='UTF-8'?><Message></Message>");

    $messageheader = $xml->addChild('MessageHeader');
    $messageheader->addChild('MessageType', 'MaterialMaster');
    $messageheader->addChild('Sender',      xml_cleanstring($yhtiorow['nimi']));
    $messageheader->addChild('Receiver',    $uj_nimi);

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
      // statuskoodi
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

      // tyyppi
      if (!is_null($row['synkronointi']) and $row['synkronointi'] == '') {
        $type = 'M';
      }
      else {
        $type = 'U';
      }

      $line = $items->addChild('Line');
      $line->addAttribute('No', $i);
      $line->addChild('Type',                  $type);
      $line->addChild('ItemNumber',            xml_cleanstring($row[$logmaster_itemnumberfield], 20));
      $line->addChild('ItemName',              xml_cleanstring($row['nimitys'], 50));
      $line->addChild('ProdGroup1',            xml_cleanstring($row[$logmaster_prodgroup1field], 6));
      $line->addChild('ProdGroup2',            xml_cleanstring($row[$logmaster_prodgroup2field], 6));
      $line->addChild('SalesPrice',            '');
      $line->addChild('Unit1',                 xml_cleanstring($row['yksikko'], 10));
      $line->addChild('Unit2',                 '');
      $line->addChild('Relation',              '');
      $line->addChild('Weight',                round($row['tuotemassa'], 3));
      $line->addChild('NetWeight',             '');
      $line->addChild('Volume',                '');
      $line->addChild('Height',                '');
      $line->addChild('Width',                 '');
      $line->addChild('Length',                '');
      $line->addChild('PackageSize',           '');
      $line->addChild('PalletSize',            '');
      $line->addChild('Status',                $status);
      $line->addChild('WholesalePackageSize',  '');
      $line->addChild('EANCode',               xml_cleanstring($row['eankoodi'], 20));
      $line->addChild('EANCode2',              '');
      $line->addChild('CustomsTariffNum',      '');
      $line->addChild('AlarmLimit',            '');
      $line->addChild('QualPeriod1',           '');
      $line->addChild('QualPeriod2',           '');
      $line->addChild('QualPeriod3',           '');
      $line->addChild('FactoryNum',            '');
      $line->addChild('UNCode',                '');
      $line->addChild('BBDateCollect',         '');
      $line->addChild('SerialNumbers',         '');
      $line->addChild('SerialNumInArrival',    '');
      $line->addChild('TaxCode',               '');
      $line->addChild('CountryofOrigin',       '');
      $line->addChild('PlatformQuantity',      '');
      $line->addChild('PlatformType',          '');
      $line->addChild('PurchasePrice',         '');
      $line->addChild('ConsumerPrice',         '');
      $line->addChild('OperRecommendation',    '');
      $line->addChild('FreeText',              xml_cleanstring($row['tuoteno'], 100));
      $line->addChild('PurchaseUnit',          '');
      $line->addChild('ManufactItemNum',       '');
      $line->addChild('InternationalItemNum',  '');
      $line->addChild('Flashpoint',            '');
      $line->addChild('SalesCurrency',         '');
      $line->addChild('PurchaseCurrency',      '');
      $line->addChild('Model',                 '');
      $line->addChild('ModelOrder',            '');
      $line->addChild('TransportTemperature',  '');

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

      $palautus = logmaster_send_file($filename);

      if ($palautus == 0) {
        pupesoft_log('logmaster_synchronize_products', "Siirretiin synkronointitiedosto {$_name}.xml.");
      }
      else {
        pupesoft_log('logmaster_synchronize_products', "Synkronointitiedoston {$_name}.xml siirt‰minen ep‰onnistui.");
      }
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
