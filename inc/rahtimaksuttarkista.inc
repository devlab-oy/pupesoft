<?php

	//muutetaan pilkut pisteiksi
	if ((mysql_field_name($result, $i) == "kilotalku") or
		(mysql_field_name($result, $i) == "kilotloppu") or
		(mysql_field_name($result, $i) == "rahtihinta")) {
			$t[$i]=str_replace(",",".",$t[$i]);
	}

?>