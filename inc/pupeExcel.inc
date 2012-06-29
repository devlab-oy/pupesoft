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
		global $pupe_root_polku;

		$foldername = md5(uniqid(rand(),true));

		// Luodaaan excel-tiedosto pupen templatesta
		exec("cp -r $pupe_root_polku/pupe_xslx_template /tmp/$foldername");

		$this->_foldername		  = $foldername;
		$this->_sheetDataFile 	  = "/tmp/".$this->_foldername."/xl/worksheets/sheet1.xml";
		$this->_sharedStringsFile = "/tmp/".$this->_foldername."/xl/sharedStrings.xml";

		$this->_sheetData = simplexml_load_file($this->_sheetDataFile);
		$this->_sharedStrings = simplexml_load_file($this->_sharedStringsFile);
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

		$this->_cellData[$excelrivi][$excelsarake] = array("NUMBER", $number, $style);
	}

	function writeString($excelrivi, $excelsarake, $string, $style = array()) {

		// Indeksit juoksee tässä maailmassa ykkösestä
		$excelrivi++;
		$excelsarake++;

		$string = xmlentities(utf8_encode($string));

		$this->_cellData[$excelrivi][$excelsarake] = array("STRING", $string, $style);

		// Shared strings array
		$this->sharedStringsList[md5($string)] = $string;
		$this->_stringCount++;
	}

	function write($excelrivi, $excelsarake, $string, $style = array()) {
		$this->writeString($excelrivi, $excelsarake, $string, $style);
	}

	function close() {

		$sharedStringsKeys = array();
		$keyIndex = 0;

		foreach ($this->sharedStringsList as $sharedstringkey => $sharedstring) {
			// if shared string is not in the xml we add it and return its index
			$si = $this->_sharedStrings->addChild("si", "");
			if ($sharedstring == "") $sharedstring = NULL;
			$si->addChild("t", $sharedstring);

			$sharedStringsKeys[$sharedstringkey] = $keyIndex;
			$keyIndex++;
		}

		// Kirjoitetaan itse xml:ä
		foreach ($this->_cellData as $rowindex => $cells) {

			// Create the line
			$row = $this->_sheetData->sheetData->addChild('row');
			$row->addAttribute('r', $rowindex);
			$row->addAttribute('spans', "1:".count($cells));
			$row->addAttribute('x14ac:dyDescent', "0.25", "http://schemas.microsoft.com/office/spreadsheetml/2009/9/ac");

			foreach ($cells as $cellindex => $celldata) {

				// Cell coordinates
				$cellcoordinates = $this->columnChar($cellindex).$rowindex;

				// Create the cell
				$cell = $row->addChild("c");
				$cell->addAttribute('r', $cellcoordinates);

				if ($celldata[0] == "STRING") {
					// Set cell value
					$cell->addChild("v", $sharedStringsKeys[md5($celldata[1])]);

					// Add style attribute
					$this->setStyle($cell, $celldata[2]);

					// Add string attribute
					$cell->addAttribute('t', 's');

				}
				if ($celldata[0] == "NUMBER") {

					// Set cell value
					$cell->addChild("v", (float) $celldata[1]);

					// Add style attribute
					$this->setStyle($cell, $celldata[2]);
				}
			}
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
