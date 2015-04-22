<?php
/*require_once ("../inc/salasanat.php");

if (!@mysql_ping())
{
	$link = @mysql_connect($dbhost, $dbuser, $dbpass) or die ("Tietokantapalvelimeen ei saada yhteyttä. Se on pois päältä tai käyttäjätietosi ovat virheelliset.");
	mysql_select_db($dbkanta) or die ("Tietokantaa $dbkanta ei löydy palvelimelta $dbhost! (parametrit.inc)");
}*/

echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';

$query = "SET SQL_SAFE_UPDATES=0;";
$result = mysql_query($query) or die("ERROR: ".mysql_error());

$query = "SELECT yriti.yhtio, yriti.tilino as Yriti, yhtio.pankkitili1 as Pankkiti
		  FROM yriti, yhtio
		  WHERE yriti.yhtio = yhtio.yhtio
		  AND yriti.tilino NOT LIKE 'FI%' 
		  AND yhtio.pankkitili1 LIKE 'FI%';";
		  
$result = mysql_query($query) or die("ERROR: ".mysql_error());

$i = 0;

while($taulu = mysql_fetch_assoc($result))
{
	$query = "UPDATE IGNORE yriti
			  SET tilino = '$taulu[Pankkiti]'
			  WHERE yhtio = '$taulu[yhtio]' AND tilino = '$taulu[Yriti]';";
	
	$resultt = mysql_query($query) or die("ERROR $i: ".mysql_error());
	
	$i++;
}

$query = "SET SQL_SAFE_UPDATES=1;";
$result = mysql_query($query) or die("ERROR: ".mysql_error());

mysql_close($link);

echo "<h1>Muutettiin $i riviä</h1>";

?>

