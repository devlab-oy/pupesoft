<?php

	require ("inc/parametrit.inc");
	
	$query = "SELECT distinct yhtio FROM yhtio WHERE (konserni = '$yhtiorow[konserni]' and konserni != '') or (yhtio = '$yhtiorow[yhtio]')";
	$result = mysql_query($query) or pupe_error($query);
	$konsyhtiot = "";

	while ($row = mysql_fetch_array($result)) {
		$konsyhtiot .= " '".$row["yhtio"]."' ,";
	}
	$konsyhtiot = " in (".substr($konsyhtiot, 0, -1).") ";

	if (isset($messenger)) {
		$query = "	INSERT INTO messenger 
					SET yhtio='$kukarow[yhtio]', kuka='$kukarow[kuka]', vastaanottaja='$vastaanottaja', viesti='$message', status='$status', luontiaika=now()";
		$messenger_result = mysql_query($query) or pupe_error($query);
	}

	if (!isset($kpl)) {
		$kpl = 20;
	}

	echo "<table>";
	echo "<form action='' method='post' name='messenger_form'>";
	echo "<input type='hidden' name='messenger' value='X'>";
	echo "<input type='hidden' name='status' value='X'>";
	echo "<tr><th>".t("Vastaanottaja").": <select name='vastaanottaja'>";

	$query = "	SELECT DISTINCT nimi, kuka FROM kuka WHERE kuka.yhtio $konsyhtiot AND extranet='' ORDER BY nimi, kuka";

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

	if (!isset($kuka) or $kuka == "vastaanotettua") {
		$kuka = "vastaanottaja";
		$sel2 = "selected";
		$sel3 = "";
	}
	else {
		$kuka = "kuka";
		$sel2 = "";
		$sel3 = "selected";
	}

	$query = "	SELECT messenger.tunnus, messenger.viesti, (SELECT nimi FROM kuka WHERE kuka.yhtio $konsyhtiot AND kuka.kuka = messenger.vastaanottaja LIMIT 1) vastaanottaja, kuka.nimi, messenger.luontiaika
				FROM messenger
				JOIN kuka ON (kuka.yhtio=messenger.yhtio AND kuka.kuka=messenger.kuka)
				WHERE messenger.yhtio $konsyhtiot AND messenger.$kuka='$kukarow[kuka]' AND extranet='' LIMIT $kpl";
	$result = mysql_query($query) or pupe_error($query);
	
	echo "<br>Näytä ";
	echo "	<form method='post' action=''>
				<select name='kpl' onChange='javascript:submit()'>";
					
					$sel = "";
					$y = 5;
					
					for ($i = 0; $i <= 3; $i++) {
						
						if ($y == $kpl) {
							$sel = "selected";
						}
						else {
							$sel = "";
						}
						
						echo "<option value='$y' $sel>$y</option>";
						$y = $y * 2;
					}

 	echo "		</select> viimeisintä 
				<select name='kuka' onChange='javascript:submit()'>
					<option value='vastaanotettua' $sel2>".t("vastaanotettua")."</option>
					<option value='lähetettyä' $sel3>".t("lähetettyä")."</option>
				</select>
			viestiä:
			</form><br><br>";

	while ($row = mysql_fetch_array($result)) {
		
		echo "<div id='$row[tunnus]'>";
		echo "<font class='info'>";

		if ($kuka == "vastaanottaja") {
			echo $row['nimi'];
		}
		else {
			echo $row['vastaanottaja'];
		}

		echo " @ ".tv1dateconv($row['luontiaika'],'yes').": ";
		echo "<b>{$row['viesti']}</b></font><br>";
		echo "</div>";
	}

	require("inc/footer.inc");

?>