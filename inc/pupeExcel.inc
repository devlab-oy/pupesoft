<?php

class pupeExcel {

	/**
    * Filename for the Workbook
    * @var string
    */
    var $_filename;

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

	function __construct() {
		global $pupe_root_polku;

		$filenimi = md5(uniqid(rand(),true));

		// Luodaaan excel-tiedosto pupen templatesta
		exec("cp -r $pupe_root_polku/pupe_xslx_template /tmp/$filenimi");

		$this->_filename = $filenimi;

		$this->_sheetData = simplexml_load_file("/tmp/".$this->_filename."/xl/worksheets/sheet1.xml");
		$this->_sharedStrings = simplexml_load_file("/tmp/".$this->_filename."/xl/sharedStrings.xml");
	}

	function columnChar($num) {

		$str 	= "";
		$start	= 65;
		$end	= 90;
		$cache  = ($end-$start);

		while ($num != 0) {
			$str = chr(($num%$cache)+$start-1).$str;
			$num = ($num-($num%$cache))/$cache;
		}

	    return $str;
	}

	function returnRow($excelrivi) {
		// Create the line if it does not exist yet
		$row = array_shift($this->_sheetData->xpath('/worksheet/sheetData/row[@r="'.$excelrivi.'"]'));

		if ($row === NULL) {
			$row = $this->_sheetData->sheetData->addChild('row');
			$row->addAttribute('r', $excelrivi);
		}

		return $row;
	}

	function returnCell($row, $cellcoordinates) {
		// Create the line if it does not exist yet
		$cell = array_shift($row->xpath('c[@r="'.$cellcoordinates.'"]'));

		if ($cell === NULL) {
			$cell = $row->addChild("c");
			$cell->addAttribute('r', $cellcoordinates);
			$cell->addChild("v", "");
		}

		return $cell;
	}
	
	function writeNumber($excelrivi, $excelsarake, $number, $style = array()) {
		// Indeksit juoksee tässä maailmassa ykkösestä
		$excelrivi++;
		$excelsarake++;

		// Cell coordinates
		$cellcoordinates = $this->columnChar($excelsarake).$excelrivi;

		// Row
		$row = $this->returnRow($excelrivi);

		// Cell
		$cell = $this->returnCell($row, $cellcoordinates);

		// Set cell value
		$cell->v = $number;
	}

	function writeString($excelrivi, $excelsarake, $string, $style = array()) {
		// Indeksit juoksee tässä maailmassa ykkösestä
		$excelrivi++;
		$excelsarake++;

		// Cell coordinates
		$cellcoordinates = $this->columnChar($excelsarake).$excelrivi;

		// Row
		$row = $this->returnRow($excelrivi);

		// Cell
		$cell = $this->returnCell($row, $cellcoordinates);

		// Add string attribute
		#$cell->addAttribute('t', 's');

		// Fiilataan sharedStrings hommat kuntoon
		#$sharedString = returnSharedString($string);

		// Set cell value
		$cell->v = 123;
	}

	function write($excelrivi, $excelsarake, $string, $style = array()) {
		$this->writeString($excelrivi, $excelsarake, $string, $style);
	}

	function saveXML() {
		file_put_contents("/tmp/".$this->_filename."/xl/worksheets/sheet1.xml", $this->_sheetData->asXML());
		file_put_contents("/tmp/KALA.xml", $this->_sheetData->asXML());
	}
}
