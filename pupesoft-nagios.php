<?php

require ("inc/connect.inc");

$STATE_OK		 = 0;
$STATE_WARNING	 = 1;
$STATE_CRITICAL	 = 2;
$STATE_UNKNOWN	 = 3;
$STATE_DEPENDENT = 4;

if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' or $_SERVER['REMOTE_ADDR'] !== '::1') {

	if ($_GET["tee"] == "CONNECTION_USAGE") {

		$query = "SHOW /*!50000 GLOBAL */ VARIABLES like 'max_connections'";
		$res = mysql_query($query) or die(mysql_error());
		$row = mysql_fetch_assoc($res);

		$max_connections = (int) $row["Value"];

		$query = "SHOW /*!50000 GLOBAL */ STATUS like 'max_used_connections'";
		$res = mysql_query($query) or die(mysql_error());
		$row = mysql_fetch_assoc($res);

		$max_used_connections = (int) $row["Value"];

		$used_percentage = round($max_used_connections / $max_connections * 100);

		// Nostetaan virhe jos tilanne on huolestuttava
		if ($used_percentage >= 60 and $used_percentage < 80) {
			echo "WARNING - Highest usage of available connections: {$used_percentage}% ({$max_used_connections}/{$max_connections})\n$STATE_WARNING";
			exit;
		}
		elseif ($used_percentage > 80) {
			echo "CRITICAL - Highest usage of available connections: {$used_percentage}% ({$max_used_connections}/{$max_connections})\n$STATE_CRITICAL";
			exit;
		}
		else {
			echo "OK - Highest usage of available connections: {$used_percentage}% ({$max_used_connections}/{$max_connections})\n$STATE_OK";
			exit;
		}
	}

	if ($_GET["tee"] == "MASTER_TO_SLAVE_CONNECTION") {

		if (isset($slavedb[1]) and $slavedb[1] != "" and isset($slaveuser[1]) and $slaveuser[1] != "" and isset($slavepass[1]) and $slavepass[1] != "") {
			$link = mysql_connect($slavedb[1], $slaveuser[1], $slavepass[1]) or die ("CRITICAL - Master cannot connect to slave\n$STATE_CRITICAL");
			mysql_select_db($dbkanta) or die ("CRITICAL - Pupesoft-database not found on slave\n$STATE_CRITICAL");

			if (is_resource($link)) {
				echo "OK - Master connection to slave OK\n$STATE_OK";
				exit;
			}
		}
		else {
			echo "CRITICAL - Slave username/password/database not set\n$STATE_CRITICAL";
			exit;
		}
	}

	if ($_GET["tee"] == "SLAVE_STATUS") {
		$query = "SHOW /*!50000 SLAVE */ STATUS";
		$res = mysql_query($query) or die(mysql_error());
		$row = mysql_fetch_assoc($res);

		if ($row["Slave_IO_Running"] != "Yes") {
			echo "CRITICAL - Slave IO Not running\n$STATE_CRITICAL";
			exit;
		}
		elseif ($row["Slave_SQL_Running"] != "Yes") {
			echo "CRITICAL - Slave SQL Not running\n$STATE_CRITICAL";
			exit;
		}
		elseif ($row["Seconds_Behind_Master"] == "NULL" or $row["Seconds_Behind_Master"] > 600) {
			echo "CRITICAL - Slave 10 minutes behind master\n$STATE_CRITICAL";
			exit;
		}
		elseif ($row["Seconds_Behind_Master"] == "NULL" or $row["Seconds_Behind_Master"] > 300) {
			echo "WARNING - Slave 5 minutes behind master\n$STATE_WARNING";
			exit;
		}
		else {
			echo "OK - Slave OK\n$STATE_OK";
			exit;
		}
	}
}

?>