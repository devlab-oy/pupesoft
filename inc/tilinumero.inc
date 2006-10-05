<?php

// Bug 590

function tilinumero_print($tilino_data) {
  if ((substr($tilino_data, 0, 1) >= 0) and (substr($tilino_data, 0, 1) <= '9')) { 
	  $alkuosa = substr($tilino_data, 0, 6);
	  if ($tilino_print[0] == "4" || $tilino_print[0] == "5") {
	    $loppuosa = substr($tilino_data, 6, 1);
	    $loppuindex = 7;
	  } else {
	    $loppuosa = "";
	    $loppuindex = 6;
	  }
	  while ($tilino_data[$loppuindex] == "0") $loppuindex++;
	  $loppuosa .= substr($tilino_data, $loppuindex);
      return "$alkuosa-$loppuosa";
   }
   else {
   	  return "$tilino_data";
   }
}

function tilinumero_data($tilino_print) {
  if ((substr($tilino_print, 0, 1) >= 0) and (substr($tilino_print, 0, 1) <= '9')) {  
	  $viiva = strpos($tilino_print, "-");
	  $alkuosa = substr($tilino_print, 0, $viiva);
	  $loppuosa = substr($tilino_print, $viiva + 1);
	  if ($tilino_print[0] == "4" || $tilino_print[0] == "5") {
	    $alkuosa .= $loppuosa[0];
	    $loppuosa = substr($loppuosa, 1);
	  }

	  $stuffing = "";
	  $i = 14;
	  while ($i > strlen($alkuosa) + strlen($loppuosa)) { 
	    $stuffing .= "0"; $i--;
	  }

	  return "$alkuosa$stuffing$loppuosa";
  }
  else {
  	return "$tilino_print";
  }
}
?>
