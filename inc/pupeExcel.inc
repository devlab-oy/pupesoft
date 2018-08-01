<?php

class pupeExcel {

  // Databaselink
  var $_exceldblink;

  function __construct() {
    global $temporarylink;

    $this->_exceldblink = $temporarylink;

    // Tehdään Excel dataa varten temporary tietokantataulu
    $query = "CREATE temporary table temporary_excel_data (
              excelrivi int(11) default null,
              excelsarake int(11) default null,
              excelsheet int(11) default null,
              sheetname varchar(100) default null,
              type char(1) default null,
              value varchar(100),
              string_id int(11) default null,
              style text default null)
              ENGINE=myisam";
    $result = pupe_query($query, $this->_exceldblink);

    // Tehdään Excel stringejä varten temporary tietokantataulu
    $query = "CREATE temporary table temporary_excel_sharedstrings (
              string_key varchar(100) not null,
              string text default null,
              id int(11) not null AUTO_INCREMENT,
              times_used int(11) not null,
              PRIMARY KEY (id),
              UNIQUE KEY string_key_ind (string_key))
              ENGINE=myisam";
    $result = pupe_query($query, $this->_exceldblink);
  }

  function columnChar($n) {
    $n--;

    for ($r = ""; $n >= 0; $n = intval($n / 26) - 1) {
      $r = chr($n % 26 + 65) . $r;
    }

    return $r;
  }

  function setStyle($cell, $style) {
    if (isset($style["date"]) and $style["date"]) {
      // s=1 viittaa styles.xml failin ykkös-styleen
      $cell->addAttribute('s', '1');
    }

    if (isset($style["bold"]) and $style["bold"]) {
      // s=2 viittaa styles.xml failin kakkos-styleen
      $cell->addAttribute('s', '2');
    }

    if (isset($style["strike"]) and $style["strike"]) {
      // s=3 viittaa styles.xml failin kolmos-styleen
      $cell->addAttribute('s', '3');
    }
  }

  function writeDate($excelrivi, $excelsarake, $date, $style = array(), $sheet = "1", $sheetname = "") {

    if ((string) $date == "" or substr($date, 0, 10) == "0000-00-00") {
      return;
    }

    // Indeksit juoksee tässä maailmassa ykkösestä
    $excelrivi++;
    $excelsarake++;

    // Hardcoodataan style
    $style = array("date" => TRUE);

    $query = "INSERT INTO temporary_excel_data SET
              excelrivi   = $excelrivi,
              excelsarake = $excelsarake,
              excelsheet  = $sheet,
              sheetname   = '$sheetname',
              type        = 'D',
              value       = '$date',
              style       = '".mysql_real_escape_string(serialize($style))."'";
    $result = pupe_query($query, $this->_exceldblink);
  }

  function writeNumber($excelrivi, $excelsarake, $number, $style = array(), $sheet = 1, $sheetname = "") {

    if ((string) $number == "") {
      return;
    }

    // Indeksit juoksee tässä maailmassa ykkösestä
    $excelrivi++;
    $excelsarake++;

    $query = "INSERT INTO temporary_excel_data SET
              excelrivi   = $excelrivi,
              excelsarake = $excelsarake,
              excelsheet  = $sheet,
              sheetname   = '$sheetname',
              type        = 'N',
              value       = '$number',
              style       = '".mysql_real_escape_string(serialize($style))."'";
    $result = pupe_query($query, $this->_exceldblink);
  }

  function writeFormula($excelrivi, $excelsarake, $number, $style = array(), $sheet = 1, $sheetname = "") {

    if ((string) $number == "") {
      return;
    }

    // Indeksit juoksee tässä maailmassa ykkösestä
    $excelrivi++;
    $excelsarake++;

    $query = "INSERT INTO temporary_excel_data SET
              excelrivi   = $excelrivi,
              excelsarake = $excelsarake,
              excelsheet  = $sheet,
              sheetname   = '$sheetname',
              type        = 'F',
              value       = '$number',
              style       = '".mysql_real_escape_string(serialize($style))."'";
    $result = pupe_query($query, $this->_exceldblink);
  }

  function writeString($excelrivi, $excelsarake, $string, $style = array(), $sheet = 1, $sheetname = "") {

    // Indeksit juoksee tässä maailmassa ykkösestä
    $excelrivi++;
    $excelsarake++;

    if ($string == "") $string = NULL;
    else $string = xmlentities($string);

    $string_key = md5($string);

    $query = "INSERT INTO temporary_excel_data SET
              excelrivi   = $excelrivi,
              excelsarake = $excelsarake,
              excelsheet  = $sheet,
              sheetname   = '$sheetname',
              type        = 'S',
              value       = '$string_key',
              style       = '".mysql_real_escape_string(serialize($style))."'";
    $result = pupe_query($query, $this->_exceldblink);

    $query = "INSERT INTO temporary_excel_sharedstrings SET
              string_key = '$string_key',
              string     = '$string',
              times_used = 1
              ON DUPLICATE KEY UPDATE times_used = times_used + 1";
    $result = pupe_query($query, $this->_exceldblink);
  }

  function write($excelrivi, $excelsarake, $string, $style = array(), $sheet = 1, $sheetname = "") {
    $this->writeString($excelrivi, $excelsarake, $string, $style, $sheet, $sheetname);
  }

  function close() {

    global $pupe_root_polku;

    // Luodaaan excel-tiedosto pupen templatesta
    $foldername = md5(uniqid(rand(), true));
    exec("cp -r $pupe_root_polku/pupe_xslx_template /tmp/$foldername");

    // Lasketaan sheettien määrä
    $query = "SELECT count(distinct excelsheet) count
              FROM temporary_excel_data";
    $result = pupe_query($query, $this->_exceldblink);
    $SheetCount = mysql_fetch_assoc($result);

    // Kurkataan onko välisivut erikseen nimetty
    $query = "SELECT distinct excelsheet, sheetname
              FROM temporary_excel_data
              WHERE sheetname != ''";
    $result = pupe_query($query, $this->_exceldblink);

    $sheetnames = array();
    while ($sheetrows = mysql_fetch_assoc($result)) {
      $sheetnames[$sheetrows['excelsheet']] = $sheetrows['sheetname'];
    }

    $content_types      = simplexml_load_file("/tmp/".$foldername."/[Content_Types].xml");
    $workbook_xml_rels  = simplexml_load_file("/tmp/".$foldername."/xl/_rels/workbook.xml.rels");
    $workbook_xml       = simplexml_load_file("/tmp/".$foldername."/xl/workbook.xml");
    $app_xml            = simplexml_load_file("/tmp/".$foldername."/docProps/app.xml");

    for ($i = 1; $i <= $SheetCount['count']; $i++) {

      if ($i == 1) {
        $sheetDataFile[$i] = "/tmp/".$foldername."/xl/worksheets/sheet1.xml";

        if (isset($sheetnames[$i])) {
          $workbook_xml->sheets->sheet->attributes()->name = $sheetnames[$i];
        }
      }
      else {
        // uusien sheettien osalta muutama kikka
        exec("cp -r $pupe_root_polku/pupe_xslx_template/xl/worksheets/sheet1.xml /tmp/$foldername/xl/worksheets/sheet{$i}.xml");
        $sheetDataFile[$i] = "/tmp/".$foldername."/xl/worksheets/sheet{$i}.xml";

        // Content_Types.xml
        $_ct = $content_types->addChild("Override");
        $_ct->addAttribute("ContentType", "application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml");
        $_ct->addAttribute("PartName", "/xl/worksheets/sheet{$i}.xml");

        // Sheet id alkaa vitosesta atm
        $e = $i + 3;

        // Workbook.xml
        if (isset($sheetnames[$i])) {
          $_sheetname = $sheetnames[$i];
        }
        else {
          $_sheetname = "Sheet {$i}";
        }
        $_wb = $workbook_xml->sheets->addChild("sheet");
        $_wb->addAttribute("name", "{$_sheetname}");
        $_wb->addAttribute("r:r:id", "rId{$e}");
        $_wb->addAttribute("sheetId", "{$e}");

        // Workbook.xml.rels
        $_wbr = $workbook_xml_rels->addChild("Relationship");
        $_wbr->addAttribute("Id", "rId{$e}");
        $_wbr->addAttribute("Target", "worksheets/sheet{$i}.xml");
        $_wbr->addAttribute("Type", "http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet");

        // app.xml
        $_ap = $app_xml->TitlesOfParts->children('vt', true)->vector->addChild("vt:lpstr", "Sheet {$i}");
      }

      $sheetData[$i]  = simplexml_load_file($sheetDataFile[$i]);
      $maxCell[$i]    = 0;
    }

    file_put_contents("/tmp/".$foldername."/[Content_Types].xml", trim(str_replace("\n", "\r\n", $content_types->asXML())));
    file_put_contents("/tmp/".$foldername."/xl/_rels/workbook.xml.rels", trim(str_replace("\n", "\r\n", $workbook_xml_rels->asXML())));
    file_put_contents("/tmp/".$foldername."/xl/workbook.xml", trim(str_replace("\n", "\r\n", $workbook_xml->asXML())));
    file_put_contents("/tmp/".$foldername."/docProps/app.xml", trim(str_replace("\n", "\r\n", $app_xml->asXML())));

    $sharedStringsFile  = "/tmp/".$foldername."/xl/sharedStrings.xml";
    $sharedStrings = simplexml_load_file($sharedStringsFile);

    // Päivitetään excel dataan string_id:t
    $query = "UPDATE temporary_excel_data
              JOIN temporary_excel_sharedstrings ON (temporary_excel_sharedstrings.string_key = temporary_excel_data.value)
              SET temporary_excel_data.string_id = (temporary_excel_sharedstrings.id-1)
              WHERE temporary_excel_data.type = 'S'";
    $result = pupe_query($query, $this->_exceldblink);

    // Tehdään Shared Strings XML
    $query = "SELECT *
              FROM temporary_excel_sharedstrings";
    $result = pupe_query($query, $this->_exceldblink);

    $stringCount = 0;
    $stringUniqueCount = mysql_num_rows($result);

    while ($shared_row = mysql_fetch_assoc($result)) {
      $si = $sharedStrings->addChild("si", "");
      $si->addChild("t", utf8_encode($shared_row["string"]));

      $stringCount += $shared_row["times_used"];
    }

    // Tehdään Excel taulukko XML
    $query = "SELECT *
              FROM temporary_excel_data
              ORDER BY excelsheet, excelrivi, excelsarake";
    $result = pupe_query($query, $this->_exceldblink);

    $edellinen_excelrivi = -1;

    // Kirjoitetaan itse xml:ä
    while ($data_row = mysql_fetch_assoc($result)) {

      if ($data_row["excelrivi"] != $edellinen_excelrivi) {
        // Create the line
        $row = $sheetData[$data_row["excelsheet"]]->sheetData->addChild('row');
        $row->addAttribute('r', $data_row["excelrivi"]);
        $row->addAttribute('spans', "1:2");
        $row->addAttribute('x14ac:dyDescent', "0.25", "http://schemas.microsoft.com/office/spreadsheetml/2009/9/ac");

        $maxCell[$data_row["excelsheet"]] = 2;
      }

      // Cell coordinates
      $cellcoordinates = $this->columnChar($data_row["excelsarake"]).$data_row["excelrivi"];

      // Create the cell
      $cell = $row->addChild("c");
      $cell->addAttribute('r', $cellcoordinates);

      // Unserialize style-array
      $data_row["style"] = unserialize($data_row["style"]);

      // String
      if ($data_row["type"] == "S") {
        // Set cell value
        $cell->addChild("v", $data_row["string_id"]);

        // Add style attribute
        $this->setStyle($cell, $data_row["style"]);

        // Add string attribute
        $cell->addAttribute('t', 's');
      }

      // Number
      if ($data_row["type"] == "N") {
        // Set cell value
        $cell->addChild("v", (float) $data_row["value"]);

        // Add style attribute
        $this->setStyle($cell, $data_row["style"]);
      }

      // Formula
      if ($data_row["type"] == "F") {

        $cell->addAttribute('t', 'n');

        // Set cell value
        $formula = $cell->addChild("f", $data_row["value"]);

        // Add formula attribute
        $formula->addAttribute('aca', 'false');

        // Add style attribute
        $this->setStyle($cell, $data_row["style"]);
      }

      // Date yyyy-mm-dd
      if ($data_row["type"] == "D") {
        list($vv, $kk, $pp) = explode("-", substr($data_row["value"], 0, 10));

        // Excelissä päivämäärät lasketaan päivissä päivästä 1.1.1900
        // 25569 päivää -> 1.1.1900 - 1.1.1970
        $dateExcel = (mktime(0, 0, 0, $kk, $pp, $vv)/86400)+25569+1;

        // Set cell value
        $cell->addChild("v", (int) $dateExcel);

        // Add style attribute
        $this->setStyle($cell, $data_row["style"]);
      }

      $edellinen_excelrivi = $data_row["excelrivi"];
    }

    // Write dimension attribute
    for ($i = 1; $i <= $SheetCount['count']; $i++) {

      $query = "SELECT count(*) rowcount
                FROM temporary_excel_data
                WHERE excelsheet = $i
                ORDER BY excelsheet, excelrivi, excelsarake";
      $result = pupe_query($query, $this->_exceldblink);
      $rowCount_row = mysql_fetch_assoc($result);
      $rowCount = $rowCount_row['rowcount'];

      $maxCellcoordinates = $this->columnChar($maxCell[$i]).$rowCount;
      $sheetData[$i]->dimension->attributes()->ref = "A1:$maxCellcoordinates";

      file_put_contents($sheetDataFile[$i], trim(str_replace("\n", "\r\n", $sheetData[$i]->asXML())));
    }

    // Update the count and uniqueCount attributes in sharedStrings XML
    $sharedStrings->attributes()->count = $stringCount;
    $sharedStrings->attributes()->uniqueCount = $stringUniqueCount;

    file_put_contents($sharedStringsFile, trim(str_replace("\n", "\r\n", $sharedStrings->asXML())));

    $xlsxfile = md5(uniqid(rand(), true)).".xlsx";

    exec("cd /tmp/{$foldername}; find . -name .DS_Store -delete; /usr/bin/zip -r /tmp/{$xlsxfile} *; rm -rf /tmp/{$foldername};");

    $query = "DROP TABLE temporary_excel_sharedstrings;";
    $result = pupe_query($query, $this->_exceldblink);

    $query = "DROP TABLE temporary_excel_data;";
    $result = pupe_query($query, $this->_exceldblink);

    return $xlsxfile;
  }
}
