<?php
//Luodaan kaikille yrityksille Suomalaiset alv-prosentit
//Tarkoitettu ajettavaksi ihan vaan php-käskyllä

require ('inc/connect.inc');

$vero[]=0;
$vero[]=8;
$vero[]=17;
$vero[]=22;

$query = "SELECT * FROM yhtio";
$result = mysql_query($query) or die($query);
echo mysql_num_rows($result) . " yritystä löytyi\n";

while ($row=mysql_fetch_array($result)) {
	$las = 10;
	echo "$row[nimi] käsitellään\n";
	foreach ($vero as $prosentti) {
		$query = "INSERT INTO avainsana (yhtio, laji, selite, jarjestys) values ('$row[yhtio]', 'ALV', '$prosentti', '$las')";
		$xresult = mysql_query($query) or die($query);
		$las += 10;
	}
}

$query = "UPDATE avainsana SET selitetark = 'o' where laji='ALV' and selite = '22'";
$result = mysql_query($query) or die($query);
echo "*** valmis ***\n";
?>
