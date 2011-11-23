<?php

require ("inc/salasanat.php");

$STATE_OK		 = 0;
$STATE_WARNING	 = 1;
$STATE_CRITICAL	 = 2;
$STATE_UNKNOWN	 = 3;
$STATE_DEPENDENT = 4;

if ($_SERVER['REMOTE_ADDR'] == '127.0.0.1' or $_SERVER['REMOTE_ADDR'] == '::1') {

	if ($_GET["tee"] == "MYSQL") {
		$link = mysql_connect($dbhost, $dbuser, $dbpass) or die ("CRITICAL - mysql_connect() failed $STATE_CRITICAL");
		mysql_select_db($dbkanta) or die ("CRITICAL - mysql_select_db() failed $STATE_CRITICAL");

		echo "OK - Mysql connection ok $STATE_OK";
		exit;
	}

	if ($_GET["tee"] == "CONNECTION_USAGE" or $_GET["tee"] == "CONNECTION_USAGE_SLAVE") {

		if ($_GET["tee"] == "CONNECTION_USAGE_SLAVE") {
			if (isset($slavedb[1]) and $slavedb[1] != "" and isset($slaveuser[1]) and $slaveuser[1] != "" and isset($slavepass[1]) and $slavepass[1] != "") {
				$link = mysql_connect($slavedb[1], $slaveuser[1], $slavepass[1]) or die ("CRITICAL - mysql_connect() failed on slave $STATE_CRITICAL");
				mysql_select_db($dbkanta) or die ("CRITICAL - mysql_select_db() failed on slave $STATE_CRITICAL");
			}
			else {
				echo "CRITICAL - Slave username/password/database not set $STATE_CRITICAL";
				exit;
			}
		}
		else {
			$link = mysql_connect($dbhost, $dbuser, $dbpass) or die ("CRITICAL - mysql_connect() failed $STATE_CRITICAL");
			mysql_select_db($dbkanta) or die ("CRITICAL - mysql_select_db() failed $STATE_CRITICAL");
		}

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
			echo "WARNING - Highest usage of available connections: {$used_percentage}% ({$max_used_connections}/{$max_connections}) $STATE_WARNING";
			exit;
		}
		elseif ($used_percentage > 80) {
			echo "CRITICAL - Highest usage of available connections: {$used_percentage}% ({$max_used_connections}/{$max_connections}) $STATE_CRITICAL";
			exit;
		}
		else {
			echo "OK - Highest usage of available connections: {$used_percentage}% ({$max_used_connections}/{$max_connections}) $STATE_OK";
			exit;
		}
	}

	if ($_GET["tee"] == "SLAVE_STATUS") {

		if (isset($slavedb[1]) and $slavedb[1] != "" and isset($slaveuser[1]) and $slaveuser[1] != "" and isset($slavepass[1]) and $slavepass[1] != "") {

			$link = mysql_connect($slavedb[1], $slaveuser[1], $slavepass[1]) or die ("CRITICAL - mysql_connect() failed on slave $STATE_CRITICAL");
			mysql_select_db($dbkanta) or die ("CRITICAL - mysql_select_db() failed on slave $STATE_CRITICAL");

			$query = "SHOW /*!50000 SLAVE */ STATUS";
			$res = mysql_query($query) or die(mysql_error());
			$row = mysql_fetch_assoc($res);

			if ($row["Slave_IO_Running"] != "Yes") {
				echo "CRITICAL - Slave IO Not running $STATE_CRITICAL";
				exit;
			}
			elseif ($row["Slave_SQL_Running"] != "Yes") {
				echo "CRITICAL - Slave SQL Not running $STATE_CRITICAL";
				exit;
			}
			elseif ($row["Seconds_Behind_Master"] == "NULL" or $row["Seconds_Behind_Master"] > 600) {
				echo "CRITICAL - Slave 10 minutes behind master $STATE_CRITICAL";
				exit;
			}
			elseif ($row["Seconds_Behind_Master"] == "NULL" or $row["Seconds_Behind_Master"] > 300) {
				echo "WARNING - Slave 5 minutes behind master $STATE_WARNING";
				exit;
			}
			else {
				echo "OK - Slave OK $STATE_OK";
				exit;
			}
		}
		else {
			echo "CRITICAL - Slave username/password/database not set $STATE_CRITICAL";
			exit;
		}
	}
}
else {
	echo "CRITICAL - Permission denied $STATE_CRITICAL";
	exit;
}

?>