<?php

	require ("inc/parametrit.inc");

	if (isset($messenger)) {
		$query = "	INSERT INTO messenger 
					SET yhtio='$kukarow[yhtio]', kuka='$kukarow[kuka]', vastaanottaja='$vastaanottaja', viesti='$message', status='$status', luontiaika=now()";
		$messenger_result = mysql_query($query) or pupe_error($query);
	}

	echo "<table>";
	echo "<form action='' method='post' name='messenger_form'>";
	echo "<input type='hidden' name='messenger' value='X'>";
	echo "<input type='hidden' name='status' value='X'>";
	echo "<tr><th>".t("Vastaanottaja").": <select name='vastaanottaja'>";

	$query = "	SELECT nimi, kuka FROM kuka WHERE yhtio='allr' AND extranet='' ORDER BY nimi, kuka";

	$result = mysql_query($query) or pupe_error($query);
	while ($userrow = mysql_fetch_array($result)) {
		if ($userrow["nimi"] != '') {
			echo "<option value='{$userrow['kuka']}'>{$userrow['nimi']}</option>";
		}
	}
	echo "</select></th></tr>";
	
	echo "<tr><td><textarea rows='20' cols='50' name='message'>";
	echo "</textarea></td></tr>";
	
	echo "<tr><td class='back' align='right'><input type='submit' name='submit' value='".t("Lähetä")."'></td></tr>";
	
	echo "</form></table>";

	$query = "	SELECT messenger.tunnus, messenger.viesti, kuka.nimi, messenger.luontiaika 
				FROM messenger JOIN kuka ON (kuka.yhtio=messenger.yhtio AND kuka.kuka=messenger.kuka) 
				WHERE messenger.yhtio='$kukarow[yhtio]' AND messenger.vastaanottaja='$kukarow[kuka]' LIMIT 20";
	$result = mysql_query($query) or pupe_error($query);
	
	echo "<br>20 viimeistä saapunutta viestiä:<br><br>";
	while ($row = mysql_fetch_array($result)) {
		echo "<div id='$row[tunnus]'>";
		echo "<font class='info'> {$row['nimi']} @ ".tv1dateconv($row['luontiaika'],'yes').": ";
		echo "<b>{$row['viesti']}</b></font><br>";
		echo "</div>";
	}

	require("inc/footer.inc");

?>