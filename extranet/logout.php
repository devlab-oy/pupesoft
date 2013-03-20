<?php
	ob_start();
	require ("parametrit.inc");

	$query = "UPDATE kuka set session='' where session='$session'";
	$result = mysql_query($query) or pupe_error($query);
	$bool = setcookie("pupesoft_session", "", time()-43200, "/");

	ob_end_flush();

	if ($toim=='change')
	{
		echo "<form name='change' target='_top' action='$palvelin2' method='post'>";
		echo "<input type='hidden' name='user' value='$kukarow[kuka]'>";
		echo "<input type='hidden' name='salamd5' value='$kukarow[salasana]'>";
		echo "</form>";

		echo "<script>
				change.submit();
			</script>";
	}
	else {
		
		if($location != "") {
			echo "<script>
					setTimeout(\"parent.location.href='$location'\",0);
					</script>";
		}
		else {
			echo "<script>
					setTimeout(\"parent.location.href='$palvelin2'\",0);
					</script>";
		}
	}
?>
