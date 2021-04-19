<?php

function tilinumero_print($tilino_data) {

	global $yhtiorow;

	if (strtoupper($yhtiorow["maa"]) != "FI") {
		return $tilino_data;
	}

	if (trim($tilino_data) == '') {
		return "";
	}
	else {
		if (substr($tilino_data, 0, 1) >= 0 and substr($tilino_data, 0, 1) <= '9') {
			$alkuosa = substr($tilino_data, 0, 6);
			if ($tilino_print[0] == "4" or $tilino_print[0] == "5") {
				$loppuosa = substr($tilino_data, 6, 1);
				$loppuindex = 7;
			}
			else {
				$loppuosa = "";
				$loppuindex = 6;
	  		}

			while ($tilino_data[$loppuindex] == "0") {
				$loppuindex++;
			}

	  		$loppuosa .= substr($tilino_data, $loppuindex);

	  		return "$alkuosa-$loppuosa";
	  	}
	  	else {
			return $tilino_data;
	  	}
	}

}

function pankkipvm($pvm) {
	return substr($pvm, 4, 2).".".substr($pvm, 2, 2).".20".substr($pvm, 0, 2);
}

?>
