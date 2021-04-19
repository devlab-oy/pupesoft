<?php

	$teksti .= "\n\n".sprintf('%67s', $id).sprintf('%8s', $sivu)."\n\n";
	$teksti .= 
	sprintf('%20s', 'Laskutusosoite:').sprintf('%-20s', '')	.sprintf('%21s', 'Toimitusosoite:').sprintf('%-15s', '')."\n".
	sprintf('%-8s', '').sprintf('%-31s', $row[0])	.sprintf('%-10s', '').sprintf('%-31s', $row[5])."\n".
	sprintf('%-8s', '').sprintf('%-31s', $row[1])	.sprintf('%-10s', '').sprintf('%-31s', $row[6])."\n".
	sprintf('%-8s', '').sprintf('%-31s', $row[2])	.sprintf('%-10s', '').sprintf('%-31s', $row[7])."\n".
	sprintf('%-8s', '').sprintf('%-31s', $row[3])	.sprintf('%-10s', '').sprintf('%-31s', $row[8])."\n".
	sprintf('%-8s', '').sprintf('%-31s', $row[4])	.sprintf('%-10s', '').sprintf('%-31s', $row[9])."\n\n".
	sprintf('%-13s', 'Maksuehto:')	.sprintf('%-26s', $row[10])	.sprintf('%-15s', 'Toimtapa:')		.sprintf('%-26s', $row[11])."\n".
	sprintf('%-13s', 'ALV:')      	.sprintf('%-26s', $row[12])	.sprintf('%-15s', 'Myyjä:')   		.sprintf('%-26s', $row[13])."\n";
	$rivi = $rivi + 13;
?>