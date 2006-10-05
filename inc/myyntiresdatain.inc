<?php
$filename = '/home/jarmo/public_html/ostoresk/datain/' . $inyhtio . 'myyres.txt';

if (file_exists($filename)) {
        $query = "DELETE from maksu WHERE yhtio = '$inyhtio' and tyyppi = 'MY'"; // Poistetaan vanhat myyntilaskut!
        $xresult = mysql_query($query) or pupe_error($query);
	$fp = fopen ($filename, "r");
	while (!feof ($fp)) {
		$line = fgets($fp, 4096);
		list($roskaa,$pvm,$summa) = split(" ", $line, 3);
		list($pv,$kk,$yy) = split("\.", $pvm, 3);
		$pvm = $yy . "-" . $kk . "-" . $pv;

		$query = "INSERT into maksu values (
				'$inyhtio',
				'$inkonserni',
				'External',
				'$pvm',
				'MY',
				'$summa',
				'Myyntireskontra',
				'',
				'')";
		$xresult = mysql_query($query) or pupe_error($query);
	}
	fclose ($fp);
} 

else {
    echo "$filename ".t("ei löydy")."<br>";
}
?>