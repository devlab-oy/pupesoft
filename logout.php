<?php
	
	ob_start();
	require ("inc/parametrit.inc");

	$query = "UPDATE kuka set session='', kesken='' where session='$session'";
	$result = mysql_query($query) or pupe_error($query);

	//$_SESSION = array();
	//session_destroy();
	$bool = setcookie("pupesoft_session", "", time()-432000);

	ob_end_flush();

	if ($bool === TRUE) {
	
		if ($toim == 'change') {
			echo "<form name='change' target='_top' action='$palvelin2' method='post'>";
			echo "<input type='hidden' name='user' value='$kukarow[kuka]'>";
			echo "<input type='hidden' name='salamd5' value='$kukarow[salasana]'>";
			echo "<input type='hidden' name='mikayhtio' value='$kukarow[yhtio]'>";
			echo "</form>";

			echo "<script>
					change.submit();
				</script>";
		}
		else {
			echo "<script>
					setTimeout(\"parent.location.href='$palvelin2'\",0);
					</script>";
		}
	}
	else {
		echo t("Selaimesi ei ilmeisesti tue cookieta").".";
	}

?>