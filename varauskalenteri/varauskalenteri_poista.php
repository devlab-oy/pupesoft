<?php
	$p = date("d");
	$k = date("m");
	$CurDate = getdate();
	$y = $CurDate['year'];
	
	$Y = sprintf('%02d',$year);
	$K = sprintf('%02d',$month);
	$P = sprintf('%02d',$day);
	
	if($Y.$K.$P < $y.$k.$p){
		echo "<br><br>".t("Vanhoja tapahtumia ei saa poistaa")."!";
		exit;
	}
	
	$query = "	select kuka, tapa, year(pvmalku), month(pvmalku), dayofmonth(pvmalku)
				from kalenteri
				where tunnus='$tunnus' and yhtio='$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);
	$row = mysql_fetch_array($result);

	if ($row["kuka"] == $kukarow["kuka"]) {
		$query = "	delete 
					from kalenteri
					where tunnus='$tunnus' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		echo "<br><br>Tapahtuma poistettu!";
		$jatko = 1;
	
		if($row[1] == "Sauna") {
			$meili = "$kukarow[nimi] on PERUNUT  saunavarauksen ajalta:\n\n##################################################\n$row[4].$row[3].$row[2] Klo: $row[5] --> Klo: $row[6]\n##################################################\n";
			$tulos = mail("$yhtiorow[varauskalenteri_email]", "Saunavarauksen peruutus", $meili,"From: ".$kukarow["nimi"]."<".$kukarow["eposti"].">\nReply-To: ".$kukarow["nimi"]."<".$row["eposti"].">\n");		
		}
	
	}
	else {
		echo "<br><br>".t("Ethän poista muitten varauksia")."!";
		$jatko = 0;
	}
?>