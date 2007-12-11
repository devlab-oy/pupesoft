<?php

require ("../inc/parametrit.inc");

echo "<font class='head'>TODO (tu-duu)</font><hr>";

$kesto_arvio = str_replace(',','.',$kesto_arvio);

if ($sort != '') {
	$sortlisa = "?sort=".$sort;
}

if ($tee == "muokkaa") {
	$query  = "SELECT * from todo where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
	$result = mysql_query($query) or pupe_error($query);
	$rivi   = mysql_fetch_array($result);

	$prio_sel = array();
	$prioriteetti = $rivi["prioriteetti"];
	$prio_sel[$prioriteetti] = "SELECTED";

	echo "<form method='post' action='todo.php?sort=$sort&kuvaus_haku=$kuvaus_haku&pyytaja_haku=$pyytaja_haku&projekti_haku=$projekti_haku&aika_haku=$aika_haku&deadline_haku=$deadline_haku&prioriteetti_haku=$prioriteetti_haku'>
	<input type='hidden' name='tee' value='paivita'>
	<input type='hidden' name='tunnus' value='$rivi[tunnus]'>
	<input type='hidden' name='vanhakuittaus' value='$rivi[kuittaus]'>
	<table>";

	echo "<tr>
			<th>kuvaus</th>
			<th>pyytäjä</th>
			<th>tekijä</th>
			<th>aika-arvio</th>
			<th>aika-tot.</th>
			<th>deadline</th>
			<th>projekti</th>
			<th>prio</th>
			<th>kuittaus</th>
			<th></th>
		</tr>
		<tr>
			<td><textarea name='kuvaus' cols='60' rows='4'>$rivi[kuvaus]</textarea></td>
			<td><input name='pyytaja' type='text' size='15' value='$rivi[pyytaja]'>";

			$query  = "	SELECT * FROM asiakas WHERE yhtio = '$kukarow[yhtio]' ORDER BY selaus, nimi";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) < 50) {
				echo "<br><select style='width: 150px;' name='asiakas'>";
				echo "<option value=''>Ei asiakasta</option>";

				while ($asiakas = mysql_fetch_array($result)) {
					$sel = "";
					if ($rivi["asiakas"] == $asiakas["tunnus"]) $sel = "SELECTED";
					echo "<option title='$asiakas[nimi] $asiakas[nimitark] ($asiakas[ytunnus])' value='$asiakas[tunnus]' $sel>$asiakas[nimi] $asiakas[nimitark] ($asiakas[ytunnus])</option>";
				}

				echo "</select>";
			}

			echo "</td>";

			$query  = "	SELECT kuka.*, count(distinct todo.tunnus) kpl, ifnull(sum(kesto_arvio),0) aika
						FROM kuka
						LEFT JOIN todo on (todo.tekija = kuka.tunnus)
						WHERE kuka.yhtio = '$kukarow[yhtio]'
						and kuka.myyja != 0
						GROUP BY kuka.tunnus
						ORDER BY aika desc";
			$result = mysql_query($query) or pupe_error($query);

			echo "<td><select style='width: 150px;' name='tekija'>";
			echo "<option value=''>Ei valittu</option>";

			while ($asiakas = mysql_fetch_array($result)) {
				$sel = "";
				if ($rivi["tekija"] == $asiakas["tunnus"]) $sel = "SELECTED";
				echo "<option title='$asiakas[nimi] ($asiakas[kpl] kpl / $asiakas[aika] h)' value='$asiakas[tunnus]' $sel>$asiakas[nimi] ($asiakas[kpl] kpl / $asiakas[aika] h)</option>";
			}

			echo "</select></td>";


			echo "<td><input name='kesto_arvio' type='text' size='6' value='$rivi[kesto_arvio]'></td>
			<td><input name='kesto_toteutunut' type='text' size='6' value='$rivi[kesto_toteutunut]'></td>
			<td><input name='deadline' type='text' size='10' value='$rivi[deadline]'></td>
			<td><input name='projekti' type='text' size='15' value='$rivi[projekti]'></td>

			<td>
				<input name='prioriteetti' type='text' size='5' value='$prioriteetti'></td>
			</td>

			<td><input name='kuittaus' type='text' size='15' value='$rivi[kuittaus]'></td>

			<td><input value='go' type='submit'></td>

		</tr>

	</table>
	</form>";
}

if (strtoupper($kuittaus) == "POIS") {
	if ($tee == "paivita" or $tee == "valmis") {
		$query  = "delete from todo where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
		$result = mysql_query($query) or pupe_error($query);
		$tee = "";
	}
}

if ($tee == "paivita") {

	$lisa = "";

	if ($kuittaus != "" and $vanhakuittaus == "") {
		$lisa = ", kuittaus = '$kuittaus', aika = now()";
	}
	elseif ($vanhakuittaus != "") {
		$lisa = ", kuittaus = '$kuittaus'";
	}

	$query  = "	UPDATE todo SET
				kuvaus           = '$kuvaus',
				prioriteetti     = '$prioriteetti',
				projekti         = '$projekti',
				pyytaja          = '$pyytaja',
				kesto_arvio      = '$kesto_arvio',
				tekija           = '$tekija',
				asiakas          = '$asiakas',
				kesto_toteutunut = '$kesto_toteutunut',
				muuttaja		 = '$kukarow[kuka]',
				muutospvm		 = now(),
				deadline         = '$deadline'
				$lisa
				WHERE yhtio = '$kukarow[yhtio]' and
				tunnus = '$tunnus'";
	$result = mysql_query($query) or pupe_error($query);
	$tee = "";
}

if ($tee == "uusi" and $kuvaus != "") {
	$query  = "insert into todo (yhtio, kuvaus, aika, prioriteetti, projekti, pyytaja, kesto_arvio, laatija, luontiaika, deadline, tekija, asiakas) values ('$kukarow[yhtio]', '$kuvaus', now(), '$prioriteetti', '$projekti', '$pyytaja', '$kesto_arvio', '$kukarow[kuka]', now(), '$deadline', '$tekija', '$asiakas')";
	$result = mysql_query($query) or pupe_error($query);
	$tee = "";
}

if ($tee == "uusi" and $kuvaus == "") {
	$tee = "";
}

if ($tee == "valmis") {
	$query  = "update todo set kuittaus='$kuittaus', aika=now(), muuttaja='$kukarow[kuka]', muutospvm=now() where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
	$result = mysql_query($query) or pupe_error($query);
	$tee = "";
}

if ($tee == "") {

	enable_ajax();
	echo "<a href=\"javascript:toggleGroup('uusidiv')\">Lisää uusi tehtävä</a><br><br>";


	echo "
	<div id='uusidiv' style='display:none'>
	<form name='uusi' method='post' action='todo.php?sort=$sort&kuvaus_haku=$kuvaus_haku&pyytaja_haku=$pyytaja_haku&projekti_haku=$projekti_haku&aika_haku=$aika_haku&deadline_haku=$deadline_haku&prioriteetti_haku=$prioriteetti_haku'>
	<input type='hidden' name='tee' value='uusi'>

	<table width='1000'>
		<tr>
			<th>kuvaus</th>
			<th>pyytäjä</th>
			<th>tekijä</th>
			<th>aika h</th>
			<th>deadline</th>
			<th>projekti</th>
			<th>prio</th>
			<th></th>
		</tr>
		<tr>
			<td><textarea name='kuvaus' cols='55' rows='4'></textarea></td>
			<td>";

		$query  = "	SELECT * FROM asiakas WHERE yhtio = '$kukarow[yhtio]' ORDER BY selaus, nimi";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) < 50) {
			echo "<select style='width: 100px;' name='asiakas'>";
			echo "<option value=''>Ei asiakasta</option>";

			while ($asiakas = mysql_fetch_array($result)) {
				$sel = "";
				if ($rivi["asiakas"] == $asiakas["tunnus"]) $sel = "SELECTED";
				echo "<option title='$asiakas[nimi] $asiakas[nimitark] ($asiakas[ytunnus])' value='$asiakas[tunnus]' $sel>$asiakas[nimi] $asiakas[nimitark] ($asiakas[ytunnus])</option>";
			}

			echo "</select><br>";
		}

		echo "<input name='pyytaja' type='text' size='15'></td>";

		$query  = "	SELECT kuka.*, count(distinct todo.tunnus) kpl, ifnull(sum(kesto_arvio),0) aika
					FROM kuka
					LEFT JOIN todo on (todo.tekija = kuka.tunnus)
					WHERE kuka.yhtio = '$kukarow[yhtio]'
					and kuka.myyja != 0
					GROUP BY kuka.tunnus
					ORDER BY aika desc";
		$result = mysql_query($query) or pupe_error($query);

		echo "<td><select style='width: 100px;' name='tekija'>";
		echo "<option value=''>Ei valittu</option>";

		while ($asiakas = mysql_fetch_array($result)) {
			$sel = "";
			if ($rivi["tekija"] == $asiakas["tunnus"]) $sel = "SELECTED";
			echo "<option title='$asiakas[nimi] ($asiakas[kpl] kpl / $asiakas[aika] h)' value='$asiakas[tunnus]' $sel>$asiakas[nimi] ($asiakas[kpl] kpl / $asiakas[aika] h)</option>";
		}

		echo "</select></td>";


			echo "</td>
			<td><input name='kesto_arvio' type='text' size='6'></td>
			<td><input name='deadline' type='text' size='10'</td>
			<td><input name='projekti' type='text' size='10'></td>

			<td>
				<select style='width: 50px;' name='prioriteetti'>
				<option value='-1'>tarjouspyyntö</option>
				<option value='0'>bug</option>
				<option value='1'>1</option>
				<option value='2'>2</option>
				<option value='3'>3</option>
				<option value='4'>4</option>
				<option value='5'>5</option>
				<option value='6'>6</option>
				<option value='7'>7</option>
				<option value='8'>8</option>
				<option value='9' selected>9</option>
				</select>
			</td>

			<td><input value='Lisää' type='submit'></td>

		</tr>

	</table>
	</form><br></div>";

	if ($sort == "pyytaja")			$sort = "order by sorttaus,pyytaja,prioriteetti,deadline,projekti,aika";
	elseif ($sort == "projekti")	$sort = "order by sorttaus,projekti,prioriteetti,deadline,aika";
	elseif ($sort == "kesto_arvio")	$sort = "order by sorttaus,kesto_arvio,prioriteetti,deadline,aika";
	elseif ($sort == "kuvaus")		$sort = "order by sorttaus,kuvaus,prioriteetti,deadline,aika";
	elseif ($sort == "deadline")	$sort = "order by sorttaus,deadline,prioriteetti,aika";
	else 							$sort = "order by sorttaus,prioriteetti,deadline,kesto_arvio desc,aika,projekti";

	$lisa = "";

	if ($kuvaus_haku != "") {
		$lisa .= " and kuvaus like '%$kuvaus_haku%' ";
	}
	if ($pyytaja_haku != "") {
		$lisa .= " and (pyytaja like '%$pyytaja_haku%' or asiakas.nimi like '%$pyytaja_haku%') ";
	}
	if ($projekti_haku != "") {
		$lisa .= " and projekti like '%$projekti_haku%' ";
	}
	if ($aika_haku != "") {
		$lisa .= " and kesto_arvio = '$aika_haku' ";
	}
	if ($deadline_haku != "") {
		$lisa .= " and deadline like '%$deadline_haku%' ";
	}
	if ($prioriteetti_haku != "") {
		$lisa .= " and prioriteetti = '$prioriteetti_haku' ";
	}
	if ($tekija_haku != "") {
		$lisa .= " and kuka.nimi like '%$tekija_haku%' ";
	}

	$query = "	SELECT kuka.nimi, todo.*, if(deadline='0000-00-00', '9999-99-99', deadline) deadline, if(tekija='$kukarow[tunnus]', prioriteetti-50,0) sorttaus, asiakas.nimi asiakasnimi
				FROM todo
				LEFT JOIN kuka on (kuka.yhtio = todo.yhtio and todo.tekija = kuka.tunnus)
				LEFT JOIN asiakas on (asiakas.yhtio = todo.yhtio and todo.asiakas = asiakas.tunnus)
				WHERE todo.yhtio  = '$kukarow[yhtio]'
				and kuittaus = ''
				$lisa
				$sort";
	$result = mysql_query($query) or pupe_error($query);

	$tunnit = 0;
	$numero = 0;

	echo "<table width='1000'>";

	echo "<tr>
		<th><a href='?sort=none&kuvaus_haku=$kuvaus_haku&pyytaja_haku=$pyytaja_haku&projekti_haku=$projekti_haku&aika_haku=$aika_haku&deadline_haku=$deadline_haku&prioriteetti_haku=$prioriteetti_haku'>#</a></th>
		<th><a href='?sort=kuvaus&kuvaus_haku=$kuvaus_haku&pyytaja_haku=$pyytaja_haku&projekti_haku=$projekti_haku&aika_haku=$aika_haku&deadline_haku=$deadline_haku&prioriteetti_haku=$prioriteetti_haku'>kuvaus</a></th>
		<th><a href='?sort=pyytaja&kuvaus_haku=$kuvaus_haku&pyytaja_haku=$pyytaja_haku&projekti_haku=$projekti_haku&aika_haku=$aika_haku&deadline_haku=$deadline_haku&prioriteetti_haku=$prioriteetti_haku'>pyytäjä</a></th>
		<th><a href='?sort=tekija&kuvaus_haku=$kuvaus_haku&pyytaja_haku=$pyytaja_haku&projekti_haku=$projekti_haku&aika_haku=$aika_haku&deadline_haku=$deadline_haku&prioriteetti_haku=$prioriteetti_haku'>tekijä</a></th>
		<th><a href='?sort=projekti&kuvaus_haku=$kuvaus_haku&pyytaja_haku=$pyytaja_haku&projekti_haku=$projekti_haku&aika_haku=$aika_haku&deadline_haku=$deadline_haku&prioriteetti_haku=$prioriteetti_haku'>projekti</a></th>
		<th><a href='?sort=kesto_arvio&kuvaus_haku=$kuvaus_haku&pyytaja_haku=$pyytaja_haku&projekti_haku=$projekti_haku&aika_haku=$aika_haku&deadline_haku=$deadline_haku&prioriteetti_haku=$prioriteetti_haku'>aika-arvio</a></th>
		<th><a href='?sort=deadline&kuvaus_haku=$kuvaus_haku&pyytaja_haku=$pyytaja_haku&projekti_haku=$projekti_haku&aika_haku=$aika_haku&deadline_haku=$deadline_haku&prioriteetti_haku=$prioriteetti_haku'>deadline</a></th>
		<th><a href='?sort=prioriteetti&kuvaus_haku=$kuvaus_haku&pyytaja_haku=$pyytaja_haku&projekti_haku=$projekti_haku&aika_haku=$aika_haku&deadline_haku=$deadline_haku&prioriteetti_haku=$prioriteetti_haku'>prio</a></th>
		<th>kuittaus</th>
	</tr>";

	 //Kursorinohjaus
	$formi	= "uusi";
	$kentta = "kuvaus";
	$omat = 0;
	echo "<form name='haku' action='todo.php' method='post'>";
	echo "<input type='hidden' name='sort' value = '$sort'>";
	echo "<tr>";
	echo "<td></td>";
	echo "<td><input type='text' size='10' name='kuvaus_haku' 		value='$kuvaus_haku'></td>";
	echo "<td><input type='text' size='10' name='pyytaja_haku' 		value='$pyytaja_haku'></td>";
	echo "<td><input type='text' size='10' name='tekija_haku' 		value='$tekija_haku'></td>";
	echo "<td><input type='text' size='10' name='projekti_haku' 	value='$projekti_haku'></td>";
	echo "<td><input type='text' size='10' name='aika_haku' 		value='$aika_haku'></td>";
	echo "<td><input type='text' size='10' name='deadline_haku' 	value='$deadline_haku'></td>";
	echo "<td><input type='text' size='10' name='prioriteetti_haku'	value='$prioriteetti_haku'></td>";
	echo "<td><input type='submit' value='Hae'></td>";
	echo "</tr>";
	echo "</form>";

	while ($rivi = mysql_fetch_array($result)) {

		$sel = array();
		$sel[$rivi["prioriteetti"]] = "SELECTED";

		$tunnit += $rivi["kesto_arvio"];
		$numero++;

		if ($rivi["prioriteetti"] == 0) {
			$rivi["prioriteetti"] = "bug";
		}

		if ($rivi["prioriteetti"] == -1) {
			$rivi["prioriteetti"] = "tarjouspyyntö";
		}

		if ($rivi["deadline"] == '9999-99-99') {
			$rivi["deadline"] = "";
		}

		if ($rivi["sorttaus"] != 0) {
			$omat++;
		}
		elseif ($rivi["sorttaus"] == 0 and $omat > 0) {
			echo "<tr><td colspan='9' class='back'>&nbsp;</td></tr>";
			$omat = 0;
		}

		echo "<tr class='aktiivi'>";

		echo "<form method='post' name='todo' action='todo.php?sort=$sort&kuvaus_haku=$kuvaus_haku&pyytaja_haku=$pyytaja_haku&projekti_haku=$projekti_haku&aika_haku=$aika_haku&deadline_haku=$deadline_haku&prioriteetti_haku=$prioriteetti_haku#ankkuri_$numero' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='valmis'>";
		echo "<input type='hidden' name='tunnus' value='$rivi[tunnus]'>";

		echo "<th><a href='?tunnus=$rivi[tunnus]&tee=muokkaa&sort=$sort&kuvaus_haku=$kuvaus_haku&pyytaja_haku=$pyytaja_haku&projekti_haku=$projekti_haku&aika_haku=$aika_haku&deadline_haku=$deadline_haku&prioriteetti_haku=$prioriteetti_haku' name='ankkuri_$numero'>$numero</a></th>";

		$rivi["kuvaus"] = str_replace("\n", "<br>", $rivi["kuvaus"]);

		echo "<td>$rivi[kuvaus]</td>";
		echo "<td>$rivi[asiakasnimi] $rivi[pyytaja]</td>";
		echo "<td>$rivi[nimi]</td>";
		echo "<td>$rivi[projekti]</td>";
        echo "<td>$rivi[kesto_arvio]</td>";
        echo "<td>$rivi[deadline]</td>";
        echo "<td>$rivi[prioriteetti]</td>";
		echo "<td><input type='text' size='7' name='kuittaus'></td>";

		echo "</form>";
		echo "</tr>\n";
	}

	echo "<tr><th colspan='5'>Aika-arvio yhteensä</th><th colspan='4'>$tunnit h = ".round($tunnit/8,0)." pv</th></tr>";

	echo "</table><br>";

	$query = "select * from todo where yhtio = '$kukarow[yhtio]' and kuittaus != '' order by aika desc limit 20";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {

		echo "<font class='head'>20 VIIMEKSI TEHTYÄ</font><hr>";
		echo "<table>";

		echo "
		<tr>
			<th>#</th>
			<th>kuvaus</th>
			<th>pyytäjä</th>
			<th>projekti</th>
			<th>prio</th>
			<th>aika-arvio</th>
			<th>aika-tot.</th>
			<th>kuittaus</th>
		</tr>
		";

		$numero = 0;

		while ($rivi = mysql_fetch_array($result)) {

			if ($rivi["prioriteetti"] == 0) {
				$rivi["prioriteetti"] = "bug";
			}

			if ($rivi["prioriteetti"] == -1) {
				$rivi["prioriteetti"] = "tarjouspyyntö";
			}

			$numero ++;

			echo "<tr class='aktiivi'>";
			echo "<th><a href='?tunnus=$rivi[tunnus]&tee=muokkaa&sort=$sort&kuvaus_haku=$kuvaus_haku&pyytaja_haku=$pyytaja_haku&projekti_haku=$projekti_haku&aika_haku=$aika_haku&deadline_haku=$deadline_haku&prioriteetti_haku=$prioriteetti_haku'>$numero</a></th>";
			echo "<td width='550'>$rivi[kuvaus]</td>";
			echo "<td>$rivi[pyytaja]</td>";
			echo "<td>$rivi[projekti]</td>";
			echo "<td>$rivi[prioriteetti]</td>";
			echo "<td>$rivi[kesto_arvio]</td>";
			echo "<td>$rivi[kesto_toteutunut]</td>";
			echo "<td>$rivi[kuittaus]</td>";
			echo "</tr>\n";
		}
	}

	echo "</table>";

}

require ("../inc/footer.inc");

?>