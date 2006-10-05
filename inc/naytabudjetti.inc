<?php
        echo "<b>".t("Budjetti")."</b><hr>";
	if ($tee == '') { // Tämä on käyttöliittymä
		echo "<form action = 'raportit.php' method='post'>
				<input type = 'hidden' name = 'toim' value = 'naytabudjetti'>
				<input type = 'hidden' name = 'tee' value = '1'>
				<table>
				<tr>
				<td>".t("Ajalta")."</td>
				<td><select name='alvv'>
				";

								for ($i = date("Y"); $i >= date("Y")-4; $i--) {
									if ($i == date("Y")) $sel = "selected";
									else $sel = "";
									echo "<option value='$i' $sel>$i</option>";
								}

				echo "
				</select></td></tr>";

		echo "<tr><td>".t("Vain kustannuspaikka")."</td>";
		$query = "SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'K'
					ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);

		echo "<td><select name='kustp'><option value=' '>".t("Ei valintaa")."";
		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow[0]) {
				$sel = "selected";
			}
			echo "<option value = '$vrow[0]' $sel>$vrow[1]";
		}
		echo "</select></td>";
		echo "</tr>";
		echo "<tr><td>".t("Vain kohde")."</td>";
		$query = "SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'O'
					ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);
		echo "<td><select name='kohde'><option value=' '>".t("Ei valintaa")."";
		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow[0]) {
				$sel = "selected";
			}
			echo "<option value = '$vrow[0]' $sel>$vrow[1]";
		}
		echo "</select></td>";
		echo "</tr>";
		echo "<tr><td>".t("Vain projekti")."</td>";
		$query = "SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'P'
					ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);
		echo "<td><select name='proj'><option value=' '>".t("Ei valintaa")."";
		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow[0]) {
				$sel = "selected";
			}
			echo "<option value = '$vrow[0]' $sel>$vrow[1]";
		}
		echo "</select></td></tr>";
		echo "<tr><td></td>
		      <td><input type = 'submit' value = '".t("Näytä")."'></form></td></table>";
		exit;
	}

	$timeparts = explode(" ",microtime());
	$phptime = $timeparts[1].substr($timeparts[0],1);

// Proto tase/tuloslaskelma problematiikkaan

// Nythän on kuitenkin niin, että tuo kausi rajoittaa meitä tätä tilikautta enemmän, eli

	$aalvk = 1 + $alvk;
	$aalvv = $alvv;

	if ($aalvk > 12) {
		$aalvv += 1;
		$aalvk -= 12;
	}

	$yhtiorow["tilikausi_loppu"] = $aalvv . "-" . sprintf("%02d",$aalvk) . "-01";

// Luetaan sopivat budjettiluvut tarvittavassa järjestyksessä

	if (strlen(trim($kustp)) > 0) {
		$lisa .= " and kustp = '" . $kustp . "'";
	}
	if (strlen(trim($proj)) > 0) {
		$lisa .= " and projekti = '" . $proj . "'";
	}
	if (strlen(trim($kohde)) > 0) {
		$lisa .= " and kohde = '" . $kohde . "'";
	}
	$query = "SELECT taso
				FROM budjetti
				WHERE yhtio='$kukarow[yhtio]' and left(kausi,4) = '$alvv' $lisa
				ORDER BY taso";

	$result = mysql_query($query) or pupe_error($query);

	$timeparts = explode(" ",microtime());
	$valitime = $timeparts[1].substr($timeparts[0],1);
	$aika1= round($valitime-$phptime,4);

	echo "$query<br>";


// Nyt lasketaan budjettiluvut

	echo "<table>";
	echo "<th></th>";
	echo "<th>".t("ka")."</th>";
	echo "<th>".t("va")."</th>";
	echo "<th>".t("edka")."</th>";
	echo "<th>".t("edva")."</th>";
	echo "<th>".t("ka ind")."</th>";
	echo "<th>".t("va ind")."</th></tr>";

	$tamataso = "9999";
	$taso1 = "Z";
	$taso2 = "Z";
	$taso3 = "Z";
	$taso31 = 0;
	$taso32 = 0;

	while ($budjrow=mysql_fetch_array ($result)) {

		$query = "SELECT summa
					 FROM budjetti
					 WHERE yhtio='$kukarow[yhtio]' and taso = '$budjrow[0]' and left(kausi,4) = '$alvv' $lisa
					 GROUP BY kausi";
		$aresult = mysql_query($query) or pupe_error($query);
// 		echo "$query<br>";

// Vähän otsikoita peliin...
		$query = "SELECT nimi
					FROM taso
					WHERE yhtio='$kukarow[yhtio]' and taso = '$budjrow[0]' and
					laji = 'O' and tyyppi = 'S'";
		$xresult = mysql_query($query) or pupe_error($query);
		$tasorow[0] = "Taso '$hakutaso'";
		if (mysql_num_rows($xresult) != 0) {
			$tasorow=mysql_fetch_array($xresult);
		}
		echo "<tr><th>$tasorow[0]</font></th>";

		while ($summarow=mysql_fetch_array ($aresult)) {
			echo "<td align = 'right'>" . sprintf("%.2f",$summarow[0]) . "</td>";
		}
		echo "</tr>";
	}

	echo "</table>";

	$timeparts = explode(" ",microtime());
	$endtime = $timeparts[1].substr($timeparts[0],1);
	$aika2= round($endtime-$phptime,4);

	print "<br>".t("Väliaika")." $aika1, ".t("kokoaika")." $aika2.";
?>
