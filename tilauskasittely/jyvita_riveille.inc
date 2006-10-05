<?php
	//tänne tarvitaan $jyvsumma joka on loppusumma joka tilaukselle halutaan ja $tilausnumero
	if ($jyvsumma > 0) {
		$jerotus = $arvo-$jyvsumma;
		$jpros = $jyvsumma/$arvo;

		$query ="SELECT tunnus, tuoteno, hinta, tilkpl, ale FROM tilausrivi where yhtio = '$kukarow[yhtio]' and otunnus = '$tilausnumero'";
		$result = mysql_query($query) or pupe_error($query);
		$jarvo = 0;
		while ($tirow=mysql_fetch_array($result)) {
			$uushinta = round($tirow['hinta']*$jpros,2);
			
			$query = "UPDATE tilausrivi SET hinta = '$uushinta' WHERE yhtio = '$kukarow[yhtio]' and tunnus='$tirow[tunnus]'";
			$upresult = mysql_query($query) or pupe_error($query);
		}
	}
	$jyvsumma='';
	$tila='';
	$tee='';
?>