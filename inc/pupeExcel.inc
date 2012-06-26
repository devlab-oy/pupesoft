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

	function writeNumber($excelrivi, $excelsarake, $number, $style = array()) {
		// Create the line if it does not exist yet
		$row = $this->_sheetData->sheetData->addChild('row', $excelrivi);
		
		// Cell coordinates
		$cellcoordinates = "A1";
		
		// Create the cell		
		$cell = $row->addChild("c");
		$cell->addAttribute('r', $cellcoordinates);	
		
		// Cell value
		$cell->addChild("v", $number);
	}

	function writeString($excelrivi, $excelsarake, $string, $style = array()) {
		$xml 		= $this->_sheetData;

		$row 		= $this->_sheetData->sheetData->addChild('row', $excelrivi);
		$cell 		= $row->addChild("c");
		$cellvalue 	= $cell->addChild("v", $string);
	}

	function write($excelrivi, $excelsarake, $string, $style = array()) {
		$this->writeString($excelrivi, $excelsarake, $string, $style);
	}

	function dumpxml() {
		file_put_contents("/tmp/KALA.xml", $this->_sheetData->asXML());
	}
}
