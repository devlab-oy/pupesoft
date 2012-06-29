<?php

class pupeExcel {

	/**
    * Foldername for the Workbook
    * @var string
    */
    var $_foldername;

	/**
    * Shared strings
    * @var object
    */
    var $_sharedStrings;

	/**
    * Sheet data, rows and columns
    * @var object
    */
    var $_sheetData;

	/**
    * Filename sheet data
    * @var string
    */
    var $_sheetDataFile;

	/**
    * Filename shared strings
    * @var string
    */
    var $_sharedStringsFile;

	/**
    * String count
    * @var int
    */
    var $_stringCount;

	/**
    * Shared strings
    * @var array
    */
    var $_sharedStringsList;

	/**
    * Celldata
    * @var array
    */
    var $_cellData;

	function __construct() {
		
		// Tehdään Excel dataa varten temporary tietokantataulu
		$query = "	CREATE temporary table excel_data (
					excelrivi int(11) default null,
					excelsarake int(11) default null,
					type char(1) default null,
					value varchar(256),
					string_id int(11) default null,
					style varchar(4) default null)";
		$result = pupe_query($query);

		// Tehdään Excel stringejä varten temporary tietokantataulu
		$query = "	CREATE temporary table excel_sharedstrings (
					string varchar(256) default null,
					id int(11) not null,
					times_used int(11) not null,
					primary key (id),
					unique key string_index (string))";
		$result = pupe_query($query);

		$query = "LOCK TABLES excel_data WRITE, excel_sharedstrings WRITE";
		$result = pupe_query($query);
	}

	function columnChar($n) {
		$n--;

		for ($r = ""; $n >= 0; $n = intval($n / 26) - 1) {
			$r = chr($n % 26 + 0x41) . $r;
		}

		return $r;
	}

	function setStyle($cell, $style) {
		if (isset($style["bold"]) and $style["bold"]) {
			#$cell->addAttribute('s', '1');
		}
	}

	function writeNumber($excelrivi, $excelsarake, $number, $style = array()) {

		if ((string) $number == "") {
			return;
		}

		// Indeksit juoksee tässä maailmassa ykkösestä
		$excelrivi++;
		$excelsarake++;

		$query = "	INSERT INTO excel_data SET
					excelrivi = $excelrivi,
					excelsarake = $excelsarake,
					type = 'N',
					value = '$number',
					style = '$style'";
		$result = pupe_query($query);
	}

	function writeString($excelrivi, $excelsarake, $string, $style = array()) {

		// Indeksit juoksee tässä maailmassa ykkösestä
		$excelrivi++;
		$excelsarake++;

		$string = xmlentities($string);

		$query = "	INSERT INTO excel_data SET
					excelrivi = $excelrivi,
					excelsarake = $excelsarake,
					type = 'S',
					value = '$string',
					style = '$style'";
		$result = pupe_query($query);

		$query = "	INSERT INTO excel_sharedstrings SET
					string = '$string',
					times_used = 1
					ON DUPLICATE KEY UPDATE times_used = times_used + 1";
		$result = pupe_query($query);
	}

	function write($excelrivi, $excelsarake, $string, $style = array()) {
		$this->writeString($excelrivi, $excelsarake, $string, $style);
	}

	function close() {

		global $pupe_root_polku;

		unset($excelrivi);

		// Luodaaan excel-tiedosto pupen templatesta
		$foldername = md5(uniqid(rand(),true));		
		exec("cp -r $pupe_root_polku/pupe_xslx_template /tmp/$foldername");

		$this->_foldername		  = $foldername;
		$this->_sheetDataFile 	  = "/tmp/".$this->_foldername."/xl/worksheets/sheet1.xml";
		$this->_sharedStringsFile = "/tmp/".$this->_foldername."/xl/sharedStrings.xml";

		$this->_sheetData = simplexml_load_file($this->_sheetDataFile);
		$this->_sharedStrings = simplexml_load_file($this->_sharedStringsFile);

		// Päivitetään exel dataan string_id:t
		$query = "	UPDATE excel_data
					JOIN excel_sharedstrings ON (excel_sharedstrings.string = excel_data.value)
					SET excel_data.string_id = excel_sharedstrings.id
					WHERE excel_data.type = 'S'";
		$result = pupe_query($query);

		// Tehdään Shared Strings XML
		$query = "	SELECT * 
					FROM excel_sharedstrings";
		$result = pupe_query($query);
	
		while ($shared_row = mysql_fetch_assoc($result)) {
			$si = $this->_sharedStrings->addChild("si", "");
			$si->addChild("t", $shared_row["string"]);
		}

		// unlock
		$query = "UNLOCK TABLES";
		$result = pupe_query($query);

		// Tehdään Excel taulukko XML
		$query = "	SELECT *, max(excelsarake) cells, max(excelrivi) columns
					FROM excel_data
					ORDER BY excelrivi, excelsarake";
		$result = pupe_query($query);

		// Kirjoitetaan itse xml:ä
		while ($data_row = mysql_fetch_assoc($result)) {

			if (!isset($excelrivi) or $excelrivi != $edellinen_excelrivi) {
				// Create the line
				$row = $this->_sheetData->sheetData->addChild('row');
				$row->addAttribute('r', $data_row["excelrivi"]);
				$row->addAttribute('spans', "1:".$data_row["cells"]);
				$row->addAttribute('x14ac:dyDescent', "0.25", "http://schemas.microsoft.com/office/spreadsheetml/2009/9/ac");
			}

			// Cell coordinates
			$cellcoordinates = $this->columnChar($data_row["excelsarake"]).$data_row["excelrivi"];

			// Create the cell
			$cell = $row->addChild("c");
			$cell->addAttribute('r', $cellcoordinates);

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

			$edellinen_excelrivi = $excelrivi;
		}

		// Write dimension attribute
		$maxRowIndex = count($this->_cellData);
		$maxCellIndex = count(array_pop($this->_cellData));

		$maxCellcoordinates = $this->columnChar($maxCellIndex).$maxRowIndex;
		$this->_sheetData->dimension->attributes()->ref = "A1:$maxCellcoordinates";

		// Update the count and uniqueCount attributes
		$this->_sharedStrings->attributes()->count = $this->_stringCount;
		$this->_sharedStrings->attributes()->uniqueCount = $this->_stringUniqueCount++;;

		file_put_contents($this->_sheetDataFile, trim(str_replace("\n", "\r\n", $this->_sheetData->asXML())));
		file_put_contents($this->_sharedStringsFile, trim(str_replace("\n", "\r\n", $this->_sharedStrings->asXML())));

		$xlsxfile = md5(uniqid(rand(),true)).".xlsx";

		exec("cd /tmp/{$this->_foldername}; find . -name .DS_Store -print0 | xargs -0 rm -rf; /usr/bin/zip -r /tmp/{$xlsxfile} *;");

		#rm -rf /tmp/{$this->_foldername};

		return $xlsxfile;

	}
}
