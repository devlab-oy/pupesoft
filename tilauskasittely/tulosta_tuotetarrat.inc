<?php

	//Ohjelma vatii muuttujan $otunnus joka viittaa ostolaskuun johon rivit on kohdistettu.

	$query = "	SELECT tuote.tuoteno, tuote.yksikko, if(tuote.tarrakpl>0,tuote.tarrakpl,1) tarrakpl, tilausrivi.varattu+tilausrivi.kpl varattu, tuote.tarrakerroin
				FROM tilausrivi, tuote
				WHERE tilausrivi.uusiotunnus = '$otunnus' and tilausrivi.tuoteno=tuote.tuoteno
				and tuote.yhtio='$kukarow[yhtio]' and tilausrivi.yhtio='$kukarow[yhtio]'
				ORDER BY tuote.tuoteno";
	$res= mysql_query($query) or pupe_error($query);
echo "$query<br>";
	list($usec, $sec) = explode(' ', microtime());
	mt_srand((float) $sec + ((float) $usec * 100000));
	$filenimi = "/tmp/Tuotetarrat-".md5(uniqid(mt_rand(), true)).".txt";

	$fh = fopen($filenimi, "w+");
	$sisalto1 = '';
	$sisalto2 = '';
	$sisalto3 = '';
	$laskuri  = 0;

	$sisalto = "\r\n";
	fputs($fh, $sisalto);

	while ($row = mysql_fetch_array($res)) {
		
		if ($row["tarrakerroin"] == 0) $row["tarrakerroin"] = 1;
		
		$tarrakpl = $row["varattu"] * $row["tarrakerroin"];
		
		//mahtuu 7 tarraa per rivi
		for($a = 0; $a < $tarrakpl; $a++) {
			if ($laskuri % 7 == 0 and $laskuri > 0) {
				$sisalto  = sprintf ('%-75.75s',$sisalto1);
    			$sisalto .= sprintf ('%-75.75s',$sisalto2);
     			$sisalto .= sprintf ('%-75.75s',$sisalto3);
     			$sisalto .= "\r\n"."\r\n";

				if ($laskuri % 112 == 0 && $laskuri > 0) {
					$sisalto .= "\r\n";
				}

				fputs($fh, $sisalto);
				$sisalto1 = '';
				$sisalto2 = '';
				$sisalto3 = '';
			}

			$sisalto1 .= sprintf ('%-11.9s',substr($row["tuoteno"],0,9));
			$sisalto2 .= sprintf ('%-11.9s',substr($row["tuoteno"],9,9));
			$sisalto3 .= sprintf ('%-11.9s',$row["tarrakpl"]." ".$row["yksikko"]);
			$laskuri++;
		}
	}

	if ($sisalto1 != "") {
		$sisalto  = sprintf ('%-75.75s',$sisalto1);
		$sisalto .= sprintf ('%-75.75s',$sisalto2);
		$sisalto .= sprintf ('%-75.75s',$sisalto3);
		$sisalto .= "\r\n"."\r\n";
		fputs($fh, $sisalto);
	}
	fclose($fh);

	//paperilista pit‰‰ saada kauniiksi
	$line = exec("a2ps -o ".$filenimi.".ps --no-header --columns=1 -R --medium=a4 --chars-per-line=75 --margin=0 --borders=0 $filenimi");

	//itse print komento tulee valitse_tulosin.incilt‰...
	$line = exec("$komento[Tuotetarrat] ".$filenimi.".ps");

	system("rm -f ".$filenimi.".ps");
	system("rm -f $filenimi");

	//p‰‰dyt‰‰n taas selailemaan toimittajia
	echo t("Tuotetarrat tulostuu")."...<br>";

?>
