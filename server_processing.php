<?php

$pupe_DataTables = "sstesti";

if (isset($_GET["dtss"]) and $_GET["dtss"] == "TRUE") {
	$no_head = "yes";
}

require ("inc/parametrit.inc");

if (isset($_GET["dtss"]) and $_GET["dtss"] == "TRUE") {
	require("server_processing_getdata.php");
	exit;
}

echo "<font class='head'>Datatables Server Side Test</font><hr><br>";

pupe_DataTables(array(array($pupe_DataTables, 5, 5)));

echo "<table class='display dataTable' id='$pupe_DataTables'>
	<thead>
		<tr>
			<th>Ytunnus</th>
			<th>Nimi</th>
			<th>Nimitark</th>
			<th>Toimitustapa</th>
			<th>Toim_nimi</th>
		</tr>
		<tr>
			<td><input type='text' class='search_field' name='search_ytunnus'></td>
			<td><input type='text' class='search_field' name='search_nimi'></td>
			<td><input type='text' class='search_field' name='search_nimitark'></td>
			<td><input type='text' class='search_field' name='search_toimitustapa'></td>
			<td><input type='text' class='search_field' name='search_toim_nimi'></td>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td colspan='5' class='dataTables_empty'>Loading data from server</td>
		</tr>
	</tbody>
	<tfoot>
		<tr>
			<th>Rendering engine</th>
			<th>Browser</th>
			<th>Platform(s)</th>
			<th>Engine version</th>
			<th>CSS grade</th>
		</tr>
	</tfoot>
</table>";