<?php
	
	$faili = "";
	$ekapoint = fopen($faili, 'r') or die ("$faili ei auennut");

	while($rivi = fgets($ekapoint, 100)){
		$rivi = split("\t", $rivi);
		$huo = 1000;
		for($a=1; $a <= 5; $a++){
			if($a == 2){
				$huo = 6000;
			}
					
			$query = "INSERT into huollot set
				  malli = '$rivi[0]',
				  huolto = 'Huolto $huo km',
				  hinta = '$rivi[$a]'";
			$result = mysql_query ($query)
              			or die ("Kysely ei onnistu $query");
			echo "$query<br>";
			$huo = $huo + 6000;				
		}
	}
	fclose($ekapoint);

	$faili = "";
	$ekapoint = fopen($faili, 'r') or die ("$faili ei auennut");

	while($rivi = fgets($ekapoint, 100)){
		$rivi = split("\t", $rivi);
		$huo = 1000;
		for($a=1; $a <= 6; $a++){
			if($a == 2){
                                $huo = 10000;
			}
                        if($a == 6){
				$huo = 'Kausihuolto';
			}

                        $query = "INSERT into huollot set
                                  malli = '$rivi[0]', ";

			if($a != 6){
				$query .=" huolto = 'Huolto $huo km', ";
			}
			else{
				$query .=" huolto = 'Kausihuolto', ";	
			}

			$query .= "hinta = '$rivi[$a]'";
			$result = mysql_query ($query)
                                or die ("Kysely ei onnistu $query");
			echo "$query<br>";
			$huo = $huo + 10000;
		}
	}
	fclose($ekapoint);

?>