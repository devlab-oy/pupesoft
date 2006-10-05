<?php
	require "inc/parametrit.inc";

	echo "<font class='head'>".t("Manuaaliset menot")."</font><hr>";

	if ($tee == 'P') {
// Olemassaolevaa maksua muutetaan, joten poistetaan rivi ja annetaan perustettavaksi
                $query = "SELECT tapvm, summa, selite
                          FROM maksu
                          WHERE tunnus = '$tunnus'";
                $result = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($result) == 0) {
			echo "".t("Maksua ei löydy")."!";
			exit;
		}
		$maksurow=mysql_fetch_array($result);
		$tapvm = $maksurow[0];
		$summa = $maksurow[1];
		$selite = $maksurow[2];
		$ok = 1;

		$query = "DELETE from maksu
			  WHERE tunnus = '$tunnus'";
		$result = mysql_query($query) or pupe_error($query);
	}

	if ($tee == 'M') {

		$query = "UPDATE maksu set maksettu = '1'
                          WHERE tunnus = '$tunnus'";
		$result = mysql_query($query) or pupe_error($query);
	}

	if ($tee == 'U') {
// Lisätään maksurivi
		if ($ok != 1) {
			$query = "SELECT konserni
						FROM yhtio
						WHERE yhtio = '$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($result) == 0) {
				echo "".t("Yritystä ei löydy")."!";
				exit;
			}
			$yrow=mysql_fetch_array($result);

		        $query = "INSERT into maksu values (
					'$kukarow[yhtio]',
					'$yrow[0]',
					'$kukarow[kuka]',
					'$tapvm',
					'MU',
					'$summa',
					'$selite',
					'',
					'')";
		        $result = mysql_query($query) or pupe_error($query);
		}
	}

	$query = "SELECT tapvm, summa, selite, tunnus
				FROM maksu
				WHERE yhtio ='$kukarow[yhtio]' and tyyppi = 'MU' and maksettu <> '1'
				ORDER BY tapvm";

	$result = mysql_query($query) or pupe_error($query);

	echo "<table><tr><th></th>";

	for ($i = 0; $i < mysql_num_fields($result)-1; $i++) {
		echo "<th>" . mysql_field_name($result,$i)."</th>";
	}
	echo "<th></th></tr>";

	while ($maksurow=mysql_fetch_array ($result)) {
		echo "<td>
				<form action = 'maksu.php' method='post'>
				<input type='hidden' name='tunnus' value = '$maksurow[3]'>
				<input type='hidden' name='tee' value = 'M'>
				<input type='Submit' value = '".t("Maksa")."'></td></form>";

		for ($i=0; $i<mysql_num_fields($result)-1; $i++) {
			echo "<td>$maksurow[$i]</td>";
		}

		echo "<td>
				<form action = 'maksu.php' method='post'>
				<input type='hidden' name='tunnus' value = '$maksurow[3]'>
				<input type='hidden' name='tee' value = 'P'>
				<input type='Submit' value = '".t("Muuta")."'>
				</td></tr></form>";
	}

// Annetaan mahdollisuus tehdä uusi maksu
	if ($ok != 1) {
// Annetaan tyhjät tiedot, jos rivi oli virheetön
		$tapvm = '';
		$summa = '';
		$selite = '';
	}

	echo "<tr><td></td>
		<td>
		<form action = 'maksu.php' method='post'>
		<input type='hidden' name='tee' value = 'U'>
		<input type='text' name='tapvm' value = '$tapvm'></td>
		<td><input type='text' name='summa' value = '$summa'></td>
		<td><input type='text' name='selite' value = '$selite'></td>
		<td><input type='Submit' value = '".t("Lisää")."'></td>
		</tr></form>";
	echo "</table>";
	echo "</body></html>";
?>