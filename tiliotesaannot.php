<?php
	require "inc/parametrit.inc";

	echo "<font class='head'>".t("Tiliotteen tili�intis��nn�t")."</font><hr>";

	// Jos $pankkitili = 'x' niin kyseess� on viiteaineiston s��nt�

	if ($tee == 'P') {
		// Olemassaolevaa s��nt�� muutetaan, joten poistetaan rivi ja annetaan perustettavaksi
		$query = "	SELECT *
					FROM tiliotesaanto
					WHERE tunnus = '$tunnus' and yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo t("S��nt�� ei l�ydy")."! $query";
			exit;
		}

		$tiliointirow = mysql_fetch_array($result);
		$koodi = $tiliointirow['koodi'];
		$koodiselite= $tiliointirow['koodiselite'];
		$nimitieto = $tiliointirow['nimitieto'];
		$selite = $tiliointirow['selite'];
		$tilino = $tiliointirow['tilino'];
		$tilino2 = $tiliointirow['tilino2'];
		$kustp = $tiliointirow['kustp'];
		$kustp2 = $tiliointirow['kustp2'];
		$pankkitili = $tiliointirow['pankkitili'];
		$erittely = $tiliointirow['erittely'];
		$ok = 1;

		$query = "DELETE from tiliotesaanto WHERE tunnus = '$tunnus' and yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
	}

	if ($tee == 'U') {
		// Tarkistetaan s��nt�
		if ($erittely == '') {
			$virhe="";
			$query = "	SELECT tilino
						FROM tili
						WHERE tilino = '$tilino' and yhtio = '$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($result) == 0) {
				$virhe .= t("Tili� ei l�ydy")."<br>";
				$ok = 1;
				$tee = '';
			}

			$nimitieto=strtoupper($nimitieto);

			if (($nimitieto=="LUOTTOKUNTA-KREDITLAGET") or ($nimitieto=="LUOTTOKUNTA") or ($nimitieto=="LUOTTOKUNTA/VISA")) {
				$query = "	SELECT tilino
							FROM tili
							WHERE tilino = '$tilino2' and yhtio = '$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result) == 0) {
					$virhe .= t("Palkkiotili� ei l�ydy")."<br>";
					$ok = 1;
					$tee = '';
				}

				if ($kustp2 != 0) {
					$query = "	SELECT tunnus
								FROM kustannuspaikka
								WHERE tunnus = '$kustp2'
								and yhtio = '$kukarow[yhtio]'
								and kaytossa != 'E'
								and tyyppi = 'K'";
					$result = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($result) == 0) {
						$virhe.= t("Kustannuspaikkaa ei l�ydy")."<br>";
						$ok = 1;
						$tee = '';
					}
				}
			}
			else {
				if ($tilino2 != 0) {
					$virhe.= t("Vain maksajalle LUOTTOKUNTA-KREDITLAGET tai LUOTTOKUNTA tai LUOTTOKUNTA/VISA voi antaa palkkiotilin")."<br>";
					$ok = 1;
					$tee = '';
				}
				if ($kustp2 != 0) {
					$virhe.= t("Vain maksajalle LUOTTOKUNTA-KREDITLAGET voi antaa palkkiokustannuspaikan")."<br>";
					$ok = 1;
					$tee = '';
				}
			}
		}
		else {
			if ($tilino !='') {
				$virhe.= t("Erittelyn ohitusriville ei voi antaa tilinumeroa")."<br>";
				$ok = 1;
				$tee = '';
			}
		}

		if ($pankkitili !='x') {
			$query = "	SELECT tilino
						FROM yriti
						WHERE tilino = '$pankkitili'
						and yhtio = '$kukarow[yhtio]'
						and yriti.kaytossa = ''";
			$result = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($result) == 0) {
				$virhe.= t("Pankkitili� ei en�� l�ydy")."<br>";
				$ok = 1;
				$tee = '';
			}
		}
	}

	if ($tee == 'U') {
// Lis�t��n s��nt�
		$query = "INSERT into tiliotesaanto
			(yhtio, pankkitili, koodi, koodiselite, nimitieto, selite,  tilino, tilino2, kustp, kustp2, erittely)
						 VALUES ('$kukarow[yhtio]', '$pankkitili', '$koodi', '$koodiselite', '$nimitieto', '$selite', '$tilino', '$tilino2', '$kustp', '$kustp2', '$erittely')";
		$result = mysql_query($query) or pupe_error($query);
	}

	if (strlen($pankkitili) != 0) {
		// Pankkitili on valittu ja sille annetaan s��nt�j�
		if ($pankkitili != 'x') {
			$query = "	SELECT nimi, tilino, tunnus
						FROM yriti
						WHERE tilino='$pankkitili'
						and yhtio = '$kukarow[yhtio]'
						and yriti.kaytossa = ''";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) != 1) {
				echo "<b>".t("Pankkitili")." $pankkitili ".t("katosi")."</b><br>";
				exit;
			}
			echo "<table><tr>";
			for ($i = 0; $i < mysql_num_fields($result)-1; $i++) {
				echo "<th>" . t(mysql_field_name($result,$i))."</th>";
			}
			echo "</tr>";

			$yritirow=mysql_fetch_array ($result);
			for ($i=0; $i<mysql_num_fields($result)-1; $i++) {
				echo "<td>$yritirow[$i]</td>";
			}
		}
		else {
			echo "<font class='message'>Viiteaineistos��nn�t</font><br>";
		}

		echo "</tr></table><br>";

		echo "<font class='head'>".t("S��nn�t")."</font><hr><table>";

		// N�ytet��n vanhat s��nn�t muutosta varten (viites��nn�ille himan eri pohja)
		if ($pankkitili != 'x') {
			$query = "SELECT tunnus, koodi, koodiselite, nimitieto, selite, erittely, tilino, kustp, tilino2, kustp2
				  FROM tiliotesaanto
				  WHERE yhtio='$kukarow[yhtio]' and pankkitili='$pankkitili'
				  ORDER BY 2,3,4";
		}
		else {
			$query = "SELECT tunnus, selite, tilino
				  FROM tiliotesaanto
				  WHERE yhtio='$kukarow[yhtio]' and pankkitili='$pankkitili'
				  ORDER BY 2";
		}
		$result = mysql_query($query) or pupe_error($query);

		echo "<tr>";
		for ($i = 1; $i < mysql_num_fields($result); $i++) {
			echo "<th>" . t(mysql_field_name($result,$i))."</th>";
		}
		echo "</tr>";

		while ($tiliointirow=mysql_fetch_array ($result)) {
			echo "<tr>";
			for ($i = 1; $i<mysql_num_fields($result); $i++) {
				if (mysql_field_name($result,$i) == 'kustp') {
					echo "<td>";
					if ($tiliointirow[$i] != 0) { // Meill� on kustannuspaikka
						$query = "	SELECT nimi
									FROM kustannuspaikka
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus = '$tiliointirow[$i]'
									and kaytossa != 'E'
									and tyyppi = 'K'";
						$xresult = mysql_query($query) or pupe_error($query);
						$xrow = mysql_fetch_array($xresult);
						echo "$xrow[0]";
					}
					echo "</td>";
				}
				elseif (mysql_field_name($result,$i) == 'kustp2') {
					echo "<td>";
					if ($tiliointirow[$i] != 0) { // Meill� on kustannuspaikka
						$query = "	SELECT nimi
									FROM kustannuspaikka
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus = '$tiliointirow[$i]'
									and kaytossa != 'E'
									and tyyppi = 'K'";
						$xresult = mysql_query($query) or pupe_error($query);
						$xrow = mysql_fetch_array ($xresult);

						echo "$xrow[0]";
					}
					echo "</td>";
				}
				else {
					echo "<td>$tiliointirow[$i]</td>";
				}
			}
			echo "<td align='center'>
					<form action = '$PHP_SELF' method='post'>
					<input type='hidden' name='lopetus' value = '$lopetus'>
					<input type='hidden' name='pankkitili' value = '$pankkitili'>
					<input type='hidden' name='tunnus' value = '$tiliointirow[0]'>
					<input type='hidden' name='tee' value = 'P'>
					<input type='Submit' value = '".t("Muuta")."'>
				</td></tr></form>";
		}

		// Annetaan mahdollisuus tehd� uusi s��nt�
		if ($ok != 1) {
			// Annetaan tyhj�t tiedot, jos rivi oli virheet�n
			$koodi = '';
			$koodiselite= '';
			$nimitieto = '';
			$tilino = '';
			$selite = '';
			$erittely = '';
			$tilino2 = '';
			$kustp = '';
			$kustp2 = '';
		}

		$query = "	SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi = 'K'
					and kaytossa != 'E'
					ORDER BY koodi+0, koodi, nimi";
		$result = mysql_query($query) or pupe_error($query);

		$ulos = "<select name = 'kustp'><option value = ' '>Ei kustannuspaikkaa";

		while ($kustannuspaikkarow=mysql_fetch_array ($result)) {
			$valittu = "";
			if ($kustannuspaikkarow[0] == $kustp) {
				$valittu = "selected";
			}
			$ulos .= "<option value = '$kustannuspaikkarow[0]' $valittu>$kustannuspaikkarow[1]";
		}
		$ulos .= "</select>";

		mysql_data_seek($result,0);

		$ulos2 = "<select name = 'kustp2'><option value = ' '>Ei kustannuspaikkaa";

		while ($kustannuspaikkarow=mysql_fetch_array ($result)) {
			$valittu = "";
			if ($kustannuspaikkarow[0] == $kustp2) {
				$valittu = "selected";
			}
			$ulos2 .= "<option value = '$kustannuspaikkarow[0]' $valittu>$kustannuspaikkarow[1]";
		}

		$ulos2 .= "</select>";

		if ($pankkitili != 'x') {
			if (substr($erittely,0,1)=='o') $erittely='checked'; else $erittely='';

			echo "<tr>
					<td><form action = '$PHP_SELF' method='post'>
						<input type='hidden' name='lopetus' value = '$lopetus'>
						<input type='hidden' name='tee' value = 'U'>
						<input type='hidden' name='pankkitili' value = '$pankkitili'>
						<input type='text' name='koodi' size='3' value = '$koodi'>
					</td>
					<td><input type='text' name='koodiselite' size='15' value = '$koodiselite'></td>
					<td><input type='text' name='nimitieto' size='15' value = '$nimitieto'></td>
					<td><input type='text' name='selite' size='15' value = '$selite'></td>
					<td><input type='checkbox' name='erittely' $erittely></td>
					<td><input type='text' name='tilino' size='6' value = '$tilino'></td>
					<td>$ulos</td>
					<td><input type='text' name='tilino2' size='6' value = '$tilino2'></td>
					<td>$ulos2</td>
					<td>$virhe <input type='Submit' value = '".t("Lis��")."'>
					</td>
				</tr></form></table>";
		}
		else {
			echo "<tr>
					<td><form action = '$PHP_SELF' method='post'>
						<input type='hidden' name='lopetus' value = '$lopetus'>
						<input type='hidden' name='tee' value = 'U'>
						<input type='hidden' name='pankkitili' value = '$pankkitili'>
						<input type='text' name='selite' size='15' value = '$selite'></td>
					<td><input type='text' name='tilino' size='6' value = '$tilino'></td>
					<td>$virhe <input type='Submit' value = '".t("Lis��")."'>
					</td>
				</tr></form></table>";
		}
	}
	else {
		// T�ll� ollaan, jos olemme vasta valitsemassa pankkitili�
		$query = "	SELECT *
					FROM yriti
					WHERE yhtio	 = '$kukarow[yhtio]'
					and kaytossa = ''
					ORDER BY nimi";
		$result = mysql_query($query) or pupe_error($query);

		echo "<form name = 'valinta' action = '$PHP_SELF' method='post'>
				<input type='hidden' name='lopetus' value = '$lopetus'>
				<table>
				<td>
				<select name = 'pankkitili'><option value = 'x'>".t("Viiteaineisto")."";

		while ($yritirow=mysql_fetch_array ($result)) {
			$valittu = "";
			if ($yritirow['tilino'] == $pankkitili) {
				$valittu = "selected";
			}
			echo "<option value = '$yritirow[tilino]' $valittu>$yritirow[nimi] ($yritirow[tilino])";
		}
		echo "</select></td>
				<td><input type = 'submit' value = '".t("Valitse")."'></td>
				</tr></table></form>";
	}

	require "inc/footer.inc";
?>
