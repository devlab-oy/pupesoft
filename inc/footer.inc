<?php
	if ((strlen($formi) > 0) and (strlen($kentta) > 0)) {
		echo "<script LANGUAGE='JavaScript'>
			window.document.$formi.$kentta.focus();
		      </script>";
	}

$timeparts = explode(" ",microtime());
$endtime   = $timeparts[1].substr($timeparts[0],1);
$aika      = round($endtime-$starttime,4);

print "<br><font class='info'><br>$aika</font>";
echo "</body></html>";
?>
