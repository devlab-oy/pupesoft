<?php

class pupeExcel {

	// Databaselink
	var $_exceldblink;

	function __construct() {
		global $masterlink, $link;

		if (isset($masterlink) and is_resource($masterlink)) {
			$this->_exceldblink = $masterlink;
		}
		else {
			$this->_exceldblink = $link;
		}

		// Tehdään Excel dataa varten temporary tietokantataulu
		$query = "	CREATE temporary table excel_data (
					excelrivi int(11) default null,
					excelsarake int(11) default null,
					type char(1) default null,
					value varchar(256),
					string_id int(11) default null,
					style varchar(4) default null)";
		$result = pupe_query($query, $this->_exceldblink);

		// Tehdään Excel stringejä varten temporary tietokantataulu
		$query = "	CREATE temporary table excel_sharedstrings (
					string text default null,
					id int(11) not null AUTO_INCREMENT,
					times_used int(11) not null,
					PRIMARY KEY (id),
					UNIQUE KEY stringind (string(256)))";
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
					excelrivi 	= $excelrivi,
					excelsarake = $excelsarake,
					type 		= 'N',
					value 		= '$number',
					style 		= '$style'";
		$result = pupe_query($query, $this->_exceldblink);
	}

	function writeString($excelrivi, $excelsarake, $string, $style = array()) {

		// Indeksit juoksee tässä maailmassa ykkösestä
		$excelrivi++;
		$excelsarake++;

		if ($string == "") $string = NULL;
		else $string = xmlentities($string);

		$query = "	INSERT INTO excel_data SET
					excelrivi 	= $excelrivi,
					excelsarake = $excelsarake,
					type 		= 'S',
					value 		= '$string',
					style 		= '$style'";
		$result = pupe_query($query, $this->_exceldblink);

		$query = "	INSERT INTO excel_sharedstrings SET
					string 		= '$string',
					times_used 	= 1
					ON DUPLICATE KEY UPDATE times_used = times_used + 1";
		$result = pupe_query($query, $this->_exceldblink);
	}

	function write($excelrivi, $excelsarake, $string, $style = array()) {
		$this->writeString($excelrivi, $excelsarake, $string, $style);
	}

	function close() {

		global $pupe_root_polku;

		// Luodaaan excel-tiedosto pupen templatesta
		$foldername = md5(uniqid(rand(),true));
		exec("cp -r $pupe_root_polku/pupe_xslx_template /tmp/$foldername");

		$sheetDataFile		= "/tmp/".$foldername."/xl/worksheets/sheet1.xml";
		$sharedStringsFile	= "/tmp/".$foldername."/xl/sharedStrings.xml";

		$sheetData 	   = simplexml_load_file($sheetDataFile);
		$sharedStrings = simplexml_load_file($sharedStringsFile);

		// Päivitetään exel dataan string_id:t
		$query = "	UPDATE excel_data
					JOIN excel_sharedstrings ON (excel_sharedstrings.string = excel_data.value)
					SET excel_data.string_id = (excel_sharedstrings.id-1)
					WHERE excel_data.type = 'S'";
		$result = pupe_query($query, $this->_exceldblink);

		// Tehdään Shared Strings XML
		$query = "	SELECT *
					FROM excel_sharedstrings";
		$result = pupe_query($query, $this->_exceldblink);

		$stringCount = 0;
		$stringUniqueCount = mysql_num_rows($result);

		while ($shared_row = mysql_fetch_assoc($result)) {
			$si = $sharedStrings->addChild("si", "");
			$si->addChild("t", utf8_encode($shared_row["string"]));

			$stringCount += $shared_row["times_used"];
		}

		// Tehdään Excel taulukko XML
		$query = "	SELECT *
					FROM excel_data
					ORDER BY excelrivi, excelsarake";
		$result = pupe_query($query, $this->_exceldblink);

		$rowCount = mysql_num_rows($result);
		$maxCell  = 0;

		$edellinen_excelrivi = -1;

		// Kirjoitetaan itse xml:ä
		while ($data_row = mysql_fetch_assoc($result)) {

			if ($data_row["excelrivi"] != $edellinen_excelrivi) {
				// Create the line
				$row = $sheetData->sheetData->addChild('row');
				$row->addAttribute('r', $data_row["excelrivi"]);
				$row->addAttribute('spans', "1:2");
				$row->addAttribute('x14ac:dyDescent', "0.25", "http://schemas.microsoft.com/office/spreadsheetml/2009/9/ac");

				$maxCell = 2;
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

			$edellinen_excelrivi = $data_row["excelrivi"];
		}

		// Write dimension attribute
		$maxCellcoordinates = $this->columnChar($maxCell).$rowCount;
		$sheetData->dimension->attributes()->ref = "A1:$maxCellcoordinates";

		// Update the count and uniqueCount attributes in sharedStrings XML
		$sharedStrings->attributes()->count = $stringCount;
		$sharedStrings->attributes()->uniqueCount = $stringUniqueCount;

		file_put_contents($sheetDataFile, trim(str_replace("\n", "\r\n", $sheetData->asXML())));
		file_put_contents($sharedStringsFile, trim(str_replace("\n", "\r\n", $sharedStrings->asXML())));

		$xlsxfile = md5(uniqid(rand(),true)).".xlsx";

		exec("cd /tmp/{$foldername}; find . -name .DS_Store -print0 | xargs -0 rm -rf; /usr/bin/zip -r /tmp/{$xlsxfile} *; rm -rf /tmp/{$foldername};");

		return $xlsxfile;
	}
}
