<?php

class pupeExcel {

	/**
    * Foldername for the Workbook
    * @var string
    */
    var $_foldername;

	/**
    * Shared strings
    * @var string
    */
    var $_sharedStrings;

	/**
    * Sheet data, rows and columns
    * @var string
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
    * Last row
    * @var array
    */
    var $_maxRow;

	/**
    * Rightmost cell per row
    * @var array
    */
    var $_rowCellMaxIndex;

	/**
    * String count
    * @var array
    */
    var $_stringCount;

	/**
    * Unique string count
    * @var array
    */
    var $_stringUniqueCount;


	function __construct() {
		global $pupe_root_polku;

		$foldername = md5(uniqid(rand(),true));

		// Luodaaan excel-tiedosto pupen templatesta
		exec("cp -r $pupe_root_polku/pupe_xslx_template /tmp/$foldername");

		$this->_foldername		  = $foldername;
		$this->_sheetDataFile 	  = "/tmp/".$this->_foldername."/xl/worksheets/sheet1.xml";
		$this->_sharedStringsFile = "/tmp/".$this->_foldername."/xl/sharedStrings.xml";

		$this->_sheetData = simplexml_load_file($this->_sheetDataFile);

		$namespaces = $this->_sheetData->getDocNamespaces();
		$this->_sheetData->registerXPathNamespace('__empty_ns', $namespaces['']);

		$this->_sharedStrings = simplexml_load_file($this->_sharedStringsFile);
	}

	function columnChar($n) {
		$n--;

		for ($r = ""; $n >= 0; $n = intval($n / 26) - 1) {
			$r = chr($n % 26 + 0x41) . $r;
		}

		return $r;
	}

	function returnRow($excelrivi) {
		// Create the line if it does not exist yet
		$row = array_shift($this->_sheetData->xpath('/__empty_ns:worksheet/__empty_ns:sheetData/__empty_ns:row[@r="'.$excelrivi.'"]'));

		if ($row === NULL) {
			$row = $this->_sheetData->sheetData->addChild('row');
			$row->addAttribute('r', $excelrivi);
			$row->addAttribute('spans', "");
			$row->addAttribute('x14ac:dyDescent', "0.25", "http://schemas.microsoft.com/office/spreadsheetml/2009/9/ac");

			$this->_rowCellMaxIndex[$excelrivi] = 0;

			if ($excelrivi > $this->_maxRow) {
				$this->_maxRow = $excelrivi;
			}
		}

		return $row;
	}

	function returnCell($row, $excelrivi, $excelsarake) {

		// Cell coordinates
		$cellcoordinates = $this->columnChar($excelsarake).$excelrivi;

		// Create the line if it does not exist yet
		$cell = array_shift($row->xpath('c[@r="'.$cellcoordinates.'"]'));

		if ($cell === NULL) {
			$cell = $row->addChild("c");
			$cell->addAttribute('r', $cellcoordinates);
			$cell->addChild("v", "");

			if ($excelsarake > $this->_rowCellMaxIndex[$excelrivi]) {
				$row->attributes()->spans = "1:$excelsarake";
				$this->_rowCellMaxIndex[$excelrivi] = $excelsarake;
			}
		}

		return $cell;
	}

	function returnSharedString($string) {
		// Check if this string is already in the shared strings xml
		$index = 0;

		foreach ($this->_sharedStrings->si as $sharedstring) {
			// Return shared string index if the string is already in the xml
			if ($string == $sharedstring->t) {
				$this->_stringCount++;
				return $index;
			}

			$index++;
		}

		// if shared string is not in the xml we add it and return its index
		$si = $this->_sharedStrings->addChild("si", "");

		if ($string == "") $string = NULL;

		$si->addChild("t", utf8_encode($string));

		$this->_stringCount++;
		$this->_stringUniqueCount++;

		return $index;
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

		// Row
		$row = $this->returnRow($excelrivi);

		// Cell
		$cell = $this->returnCell($row, $excelrivi, $excelsarake);

		// Add style attribute
		$this->setStyle($cell, $style);

		// Set cell value
		$cell->v = (float) $number;
	}

	function writeString($excelrivi, $excelsarake, $string, $style = array()) {

		// Fiilataan sharedStrings hommat kuntoon
		$stringindex = $this->returnSharedString($string);

		// Indeksit juoksee tässä maailmassa ykkösestä
		$excelrivi++;
		$excelsarake++;

		// Row
		$row = $this->returnRow($excelrivi);

		// Cell
		$cell = $this->returnCell($row, $excelrivi, $excelsarake);

		// Add style attribute
		$this->setStyle($cell, $style);

		// Add string attribute
		$cell->addAttribute('t', 's');

		// Set cell value
		$cell->v = $stringindex;
	}

	function write($excelrivi, $excelsarake, $string, $style = array()) {
		$this->writeString($excelrivi, $excelsarake, $string, $style);
	}

	function close() {

		// Write dimension
		$maxCellcoordinates = $this->columnChar($this->_rowCellMaxIndex[$this->_maxRow]).$this->_maxRow;

		$this->_sheetData->dimension->attributes()->ref = "A1:$maxCellcoordinates";

		// Update the count and uniqueCount attributes
		$this->_sharedStrings->attributes()->count = $this->_stringCount;
		$this->_sharedStrings->attributes()->uniqueCount = $this->_stringUniqueCount++;;

		file_put_contents($this->_sheetDataFile, trim(str_replace("\n", "\r\n", $this->_sheetData->asXML())));
		file_put_contents($this->_sharedStringsFile, trim(str_replace("\n", "\r\n", $this->_sharedStrings->asXML())));

		$xlsxfile = md5(uniqid(rand(),true)).".xlsx";

		exec("cd /tmp/{$this->_foldername}; find . -name .DS_Store -print0 | xargs -0 rm -rf; /usr/bin/zip -r /tmp/{$xlsxfile} *; rm -rf /tmp/{$this->_foldername};");
		
		return $xlsxfile;
	}
}
