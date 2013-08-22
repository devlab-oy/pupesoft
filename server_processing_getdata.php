<?php

	/*
	 * Paging
	 */
	$sLimit = "";

	if (isset($_GET['iDisplayStart']) and $_GET['iDisplayLength'] != '-1') {
		$sLimit = "LIMIT ".intval($_GET['iDisplayStart']).", ".intval($_GET['iDisplayLength']);
	}

	/*
	 * Ordering
	 */
	$sOrder = "";

	if (isset($_GET['iSortCol_0'])) {
		$sOrder = "ORDER BY  ";

		for ($i = 0; $i < intval($_GET['iSortingCols']); $i++) {
			if ($_GET['bSortable_'.intval($_GET['iSortCol_'.$i])] == "true") {
				$sOrder .= $aColumns[ intval($_GET['iSortCol_'.$i]) ]." ".($_GET['sSortDir_'.$i]==='asc' ? 'asc' : 'desc') .", ";
			}
		}

		$sOrder = substr_replace($sOrder, "", -2);

		if ($sOrder == "ORDER BY") {
			$sOrder = "";
		}
	}

	/*
	 * Filtering
	 * NOTE this does not match the built-in DataTables filtering which does it
	 * word by word on any field. It's possible to do here, but concerned about efficiency
	 * on very large tables, and MySQL's regex functionality is very limited
	 */
	$sWhere = "";

	if (isset($_GET['sSearch']) and $_GET['sSearch'] != "") {
		$sWhere = " AND (";

		for ($i=0; $i<count($aColumns); $i++) {
			$sWhere .= "{$aColumns[$i]} LIKE '%".mysql_real_escape_string($_GET['sSearch'])."%' OR ";
		}

		$sWhere = substr_replace($sWhere, "", -3);
		$sWhere .= ')';
	}

	/*
	 * Individual column filtering
	 */
	for ($i=0; $i<count($aColumns); $i++) {
		if (isset($_GET['bSearchable_'.$i]) and $_GET['bSearchable_'.$i] == "true" and $_GET['sSearch_'.$i] != '') {
			$sWhere .= " AND {$aColumns[$i]} LIKE '%".mysql_real_escape_string($_GET['sSearch_'.$i])."%' ";
		}
	}

	/*
	 * Use specific index
	 */
	$sIndex = "";

	if (isset($sUseIndex) and $sUseIndex != "") {
		$sIndex = ($sUseIndex);
	}

	/*
	* Static where
	*/
	if (isset($sInitialWhere) and $sInitialWhere != "") {
		$sWhere .= " AND ".($sInitialWhere);
	}

	/*
	* Initial sorting
	*/
	if ($sOrder == "" and isset($sInitialOrder) and $sInitialOrder != "") {
		$sOrder = "ORDER BY ".($sInitialOrder);
	}

	/*
	 * SQL queries
	 * Get data to display
	 */
	$sQuery = "	SELECT SQL_CALC_FOUND_ROWS ".str_replace(" , ", " ", implode(", ", $aColumns))."
				FROM $sTable {$sIndex}
				WHERE yhtio = '{$kukarow['yhtio']}'
				$sWhere
				$sOrder
				$sLimit";
	$rResult = pupe_query($sQuery);

	error_log($sQuery);

	/* Data set length after filtering */
	$sQuery = " SELECT FOUND_ROWS() ";
	$rResultFilterTotal = pupe_query($sQuery);
	$aResultFilterTotal = mysql_fetch_array($rResultFilterTotal);

	$iFilteredTotal = $aResultFilterTotal[0];

	/* Total data set length */
	$sQuery = " SELECT COUNT('".$sIndexColumn."')
				FROM $sTable";
	$rResultTotal = pupe_query($sQuery);
	$aResultTotal = mysql_fetch_array($rResultTotal);

	$iTotal = $aResultTotal[0];

	/*
	 * Output
	 */
	$output = array(
		"sEcho" => intval($_GET['sEcho']),
		"iTotalRecords" => $iTotal,
		"iTotalDisplayRecords" => $iFilteredTotal,
		"aaData" => array()
	);

	while ($aRow = mysql_fetch_array($rResult)) {
		$row = array();

		for ($i=0 ; $i<count($aColumns); $i++) {
			$row[] = utf8_encode($aRow[$aColumns[$i]]);
		}

		$output['aaData'][] = $row;
	}

	echo json_encode($output);
