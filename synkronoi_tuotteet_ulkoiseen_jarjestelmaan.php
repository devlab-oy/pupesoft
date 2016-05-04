<?php

// Kutsutaanko CLI:st‰
$php_cli = (php_sapi_name() == 'cli') ? true : false;

if ($php_cli) {
  // Pupesoft root include_pathiin
  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__));

  // Otetaan tietokanta connect
  require "inc/connect.inc";
  require "inc/functions.inc";

  // Logitetaan ajo
  cron_log();

  if (!isset($argv[1])) {
    echo "Anna yhtio!\n";
    die;
  }

  if (!isset($argv[2])) {
    echo "Anna ulkoinen j‰rjestelm‰!\n";
    die;
  }

  // Haetaan yhtiˆrow ja kukarow
  $yhtio = pupesoft_cleanstring($argv[1]);
  $yhtiorow = hae_yhtion_parametrit($yhtio);
  $kukarow = hae_kukarow('admin', $yhtiorow['yhtio']);
  $ulkoinen_jarjestelma = $argv[2];
}
else {
  require "inc/parametrit.inc";
}

class SynchronizeProducts {
  private $xml;
  private $i;
  private $ulkoinen_jarjestelma;
  private $encoding;
  private $yhtiorow;
  private $kukarow;

  function __construct($ulkoinen_jarjestelma, $yhtiorow, $kukarow) {
    $this->ulkoinen_jarjestelma = $ulkoinen_jarjestelma;
    $this->encoding = PUPE_UNICODE ? 'UTF-8' : 'ISO-8859-1';
    $this->yhtiorow = $yhtiorow;
    $this->kukarow = $kukarow;
  }

  public function asXML() {
    return $this->xml->asXML();
  }

  public function setHeader() {
    $this->xml = simplexml_load_string("<?xml version='1.0' encoding='{$this->encoding}'?><Message></Message>");

    $messageheader = $this->xml->addChild('MessageHeader');
    $messageheader->addChild('MessageType', 'MaterialMaster');
    $messageheader->addChild('Sender', utf8_encode($this->yhtiorow['nimi']));

    if ($this->ulkoinen_jarjestelma == 'L') {
      $uj_nimi = "LogMaster";
    }
    else {
      $uj_nimi = "Posten";
    }

    $messageheader->addChild('Receiver', $uj_nimi);

    $iteminformation = $this->xml->addChild('ItemInformation');
    $iteminformation->addChild('TransDate', date('d-m-Y'));
    $iteminformation->addChild('TransTime', date('H:i:s'));
    $iteminformation->addChild('Items');

    $this->i = 1;
  }

  public function addProduct($row) {
    $line = $this->xml->ItemInformation->Items->addChild('Line');
    $line->addAttribute('No', $this->i);

    if (!is_null($row['synkronointi']) and $row['synkronointi'] == '') {
      $type = 'M';
    }
    else {
      $type = 'U';
    }

    $line->addChild('Type', $type);

    $eankoodi = substr($row['eankoodi'], 0, 20);

    if ($this->ulkoinen_jarjestelma == "P") {
      $line->addChild('ItemNumber', utf8_encode($eankoodi));
    }
    else {
      $tuoteno = substr($row['tuoteno'], 0, 20);
      $line->addChild('ItemNumber', utf8_encode($tuoteno));
    }

    $nimitys = substr($row['nimitys'], 0, 50);
    $try = substr($row['try'], 0, 6);
    $yksikko = substr($row['yksikko'], 0, 10);
    $tuoteno = substr($row['tuoteno'], 0, 100);

    $line->addChild('ItemName', utf8_encode($nimitys));
    $line->addChild('ProdGroup1', utf8_encode($try));
    $line->addChild('ProdGroup2', '');
    $line->addChild('SalesPrice', '');
    $line->addChild('Unit1', utf8_encode($yksikko));
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
    $line->addChild('EANCode', utf8_encode($eankoodi));
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
    $line->addChild('FreeText', utf8_encode($tuoteno));
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
                yhtio      = '{$this->kukarow['yhtio']}',
                tuoteno    = '{$row['tuoteno']}',
                kieli      = '{$this->yhtiorow['kieli']}',
                laji       = 'synkronointi',
                selite     = 'x',
                laatija    = '{$this->kukarow['kuka']}',
                luontiaika = now(),
                muutospvm  = now(),
                muuttaja   = '{$this->kukarow['kuka']}'";
      pupe_query($query);

    }
    else {

      $query = "UPDATE tuotteen_avainsanat SET
                selite      = 'x'
                WHERE yhtio = '{$this->kukarow['yhtio']}'
                AND tuoteno = '{$row['tuoteno']}'
                AND laji    = 'synkronointi'";
      pupe_query($query);
    }

    $this->i += 1;
  }
}

if (!isset($tee)) $tee = '';

if (!$php_cli) echo "<font class='head'>", t("Synkronoi tuotteet ulkoiseen j‰rjestelm‰‰n"), "</font><hr><br />";

if (!$php_cli and (!isset($ulkoinen_jarjestelma) or empty($ulkoinen_jarjestelma))) {
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

    if ($php_cli) {
      echo "\n\nTarvittavat FTP-tunnukset ovat puutteelliset!\n";
    }
    else {
      echo "<br /><font class='error'>", t("Tarvittavat FTP-tunnukset ovat puutteelliset"), "!</font><br>";

      require "inc/footer.inc";
    }

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
            WHERE tuote.yhtio    = '{$kukarow['yhtio']}'
            AND tuote.ei_saldoa  = ''
            {$wherelisa}
            HAVING (ta.tunnus IS NOT NULL AND ta.selite = '') OR
                    # jos avainsanaa ei ole olemassa ja status P niin ei haluta n‰it‰ tuotteita jatkossakaan
                   (ta.tunnus IS NULL AND tuote.status != 'P')";
  $res = pupe_query($query);

  if (mysql_num_rows($res) > 0) {

    if (!$php_cli and $tee == '') {

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
      $_synkronoi = new SynchronizeProducts($ulkoinen_jarjestelma, $yhtiorow, $kukarow);
      $_synkronoi->setHeader();
    }

    while ($row = mysql_fetch_assoc($res)) {

      if (!$php_cli and $tee == '') {

        echo "<tr>";
        echo "<td>{$row['tuoteno']}</td>";
        echo "<td>{$row['nimitys']}</td>";
        echo "</tr>";
      }
      else {
        $_synkronoi->addProduct($row);
      }
    }

    if (!$php_cli and $tee == '') {
      echo "<tr><td class='back' colspan='2'>";
      echo "<input type='submit' name='tee' value='", t("L‰het‰"), "' />";
      echo "</td></tr>";
      echo "</table>";
      echo "</form>";
    }
    else {

      $_name = substr("tuote_".md5(uniqid()), 0, 25);
      $filename = $pupe_root_polku."/dataout/{$_name}.xml";

      if (file_put_contents($filename, $_synkronoi->asXML())) {

        if ($php_cli) {
          echo "\nTiedoston luonti onnistui\n";
        }
        else {
          echo "<br /><font class='message'>", t("Tiedoston luonti onnistui"), "</font><br />";
        }

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

          if ($php_cli) {
            echo "\nTarvittavat FTP-tunnukset ovat puutteelliset!\n";
          }
          else {
            echo "<br /><font class='error'>", t("Tarvittavat FTP-tunnukset ovat puutteelliset"), "!</font><br>";

            require "inc/footer.inc";
          }

          exit;
        }

        # L‰hetet‰‰n UTF-8 muodossa jos PUPE_UNICODE on true
        $ftputf8 = PUPE_UNICODE;

        require "inc/ftp-send.inc";
      }
      else {

        if ($php_cli) {
          echo "\nTiedoston luonti ep‰onnistui\n";
        }
        else {
          echo "<br /><font class='error'>", t("Tiedoston luonti ep‰onnistui"), "</font><br />";
        }
      }
    }
  }
  else {
    if ($php_cli) {
      echo "\nKaikki tuotteet ovat synkronoitu\n";
    }
    else {
      echo "<font class='message'>", t("Kaikki tuotteet ovat synkronoitu"), "</font><br />";
    }
  }

  if (!$php_cli) require "inc/footer.inc";
}
