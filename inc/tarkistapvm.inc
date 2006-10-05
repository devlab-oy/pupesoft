<?php
	$query = "SELECT TO_DAYS('$pvm')"; // huh huh mikä häkki!!!
	$result = mysql_query($query) or pupe_error($query);
	$pvmrow=mysql_fetch_array($result);
	$pvm=$pvmrow[0] + 0;
	$query = "SELECT FROM_DAYS($pvm)"; // huh huh mikä häkki!!!
	$result = mysql_query($query) or pupe_error($query);
	$pvmrow=mysql_fetch_array($result);
	$pvm=$pvmrow[0];

?>

