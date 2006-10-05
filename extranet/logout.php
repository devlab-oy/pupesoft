<?php
	require ("parametrit.inc");

	$query = "UPDATE kuka set session='' where session='$session' and extranet!=''";
	$result = mysql_query($query) or pupe_error($query);

	setcookie("pupesoft_session", "", time()-432000);

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
	else
	{
		echo "<script>
				setTimeout(\"parent.location.href='$palvelin2'\",0);
			</script>";
	}
?>