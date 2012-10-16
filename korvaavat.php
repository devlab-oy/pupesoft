<?php

	require ("inc/parametrit.inc");

	if ($toim == "vastaavat") {
		$taulu = "vastaavat";
		echo "<font class='head'>".t("Vastaavien yll‰pito")."</font><hr>";
	}
	else {
		$toim = "";
		$taulu = "korvaavat";
		echo "<font class='head'>".t("Korvaavien yll‰pito")."</font><hr>";
	}

	echo "<form action='korvaavat.php' method='post' name='etsituote' autocomplete='off'>
		  ".t("Etsi tuotetta")." <input type='text' name='tuoteno'>
		  <input type='submit' value='".t("Hae")."'>
		  <input type='hidden' value='$toim' name='toim'>
		  </form><br><br>";

	if ($tee == 'del') {
		//haetaan poistettavan tuotteen id.. k‰ytt‰j‰st‰v‰llist‰..
		$query  = "SELECT * FROM $taulu WHERE tunnus = '$tunnus' AND yhtio = '$kukarow[yhtio]'";
		$result = pupe_query($query);
		$row    = mysql_fetch_array($result);
		$id		= $row['id'];

		//poistetaan korvaava..
		$query  = "DELETE FROM $taulu WHERE tunnus = '$tunnus' AND yhtio = '$kukarow[yhtio]'";
		$result = pupe_query($query);

		//n‰ytet‰‰n silti loput.. kiltti‰.
		$query  = "SELECT * FROM $taulu WHERE id = '$id' AND yhtio = '$kukarow[yhtio]'";
		$result = pupe_query($query);
		$row    = mysql_fetch_array($result);
		$tuoteno = $row['tuoteno'];
	}

	if ($tee == 'muutaprio') {
		//haetaan poistettavan tuotteen id.. k‰ytt‰j‰st‰v‰llist‰..
		$query  = "SELECT * FROM $taulu WHERE tunnus = '$tunnus' AND yhtio = '$kukarow[yhtio]'";
		$result = pupe_query($query);
		$row    = mysql_fetch_array($result);
		$id		= $row['id'];

		if ($prio != 0) {
			# Siirret‰‰n ketjun muita eteenp‰in, jarjestys + 1
			$query = "UPDATE $taulu SET jarjestys=jarjestys+1, muuttaja='{$kukarow['kuka']}', muutospvm=now()
						WHERE jarjestys!=0 AND id='$id' AND yhtio='{$kukarow['yhtio']}' AND tunnus!=$tunnus AND jarjestys >= $prio";
			$result = pupe_query($query);
		}

		//muutetaan prio..
		$query  = "		UPDATE $taulu SET
						jarjestys = '$prio',
						muutospvm = now(),
						muuttaja = '$kukarow[kuka]'
						WHERE tunnus = '$tunnus' AND yhtio = '$kukarow[yhtio]'";
		$result = pupe_query($query);

		//n‰ytet‰‰n silti loput.. kiltti‰.
		$query  = "SELECT * FROM $taulu WHERE id = '$id' AND yhtio = '$kukarow[yhtio]'";
		$result = pupe_query($query);
		$row    = mysql_fetch_array($result);
		$tuoteno= $row['tuoteno'];
	}

	if ($tee == 'add') {
		// tutkitaan onko lis‰tt‰v‰ tuote oikea tuote...
		$query  = "SELECT * FROM tuote WHERE tuoteno = '$korvaava' AND yhtio = '$kukarow[yhtio]'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Lis‰ys ei onnistu! Tuotetta")." $korvaava ".t("ei lˆydy")."!</font><br><br>";
		}
		else {
			$query  = "SELECT * FROM $taulu WHERE tuoteno = '$tuoteno' AND yhtio = '$kukarow[yhtio]'";
			$result = pupe_query($query);

			// katotaan onko is‰ tuote lis‰ttty..
			if (mysql_num_rows($result) != 0) {
				//jos on, otetaan ID luku talteen...
				$row    = mysql_fetch_array($result);
				$fid	= $row['id'];
			}

			//katotaan onko korvaava jo lis‰tty
			$query  = "SELECT * FROM $taulu WHERE tuoteno = '$korvaava' AND yhtio = '$kukarow[yhtio]'";
			$result = pupe_query($query);

			if (mysql_num_rows($result) != 0) {
				//korvaava on jo lis‰tty.. otetaan senki id..
				$row    = mysql_fetch_array($result);
				$cid	= $row['id'];
			}

			//jos kumpaakaan ei lˆytynyt...
			if (($cid == "") and ($fid == "")) {
				//silloin t‰m‰ on eka korvaava.. etsit‰‰n sopiva ID.
				$query  = "SELECT max(id) FROM $taulu";
				$result = pupe_query($query);
				$row    = mysql_fetch_array($result);
				$id 	= $row[0]+1;

				//lis‰t‰‰n "is‰ tuote"...
				$query  = "	INSERT INTO $taulu (id, tuoteno, yhtio, laatija, luontiaika, muutospvm, muuttaja)
							VALUES ('$id', '$tuoteno', '$kukarow[yhtio]', '$kukarow[kuka]', now(), now(), '$kukarow[kuka]')";
				$result = pupe_query($query);

				// lis‰t‰‰n korvaava tuote...
				$query  = " INSERT INTO $taulu (id, tuoteno, yhtio, laatija, luontiaika, muutospvm, muuttaja)
							VALUES ('$id', '$korvaava', '$kukarow[yhtio]', '$kukarow[kuka]', now(), now(), '$kukarow[kuka]')";
				$result = pupe_query($query);
			}

			//lapsi on lˆytynyt, is‰‰ ei
			if (($cid != "") and ($fid == "")) {
				//lis‰t‰‰n "is‰ tuote"...
				$query  = " INSERT INTO $taulu (id, tuoteno, yhtio, laatija, luontiaika, muutospvm, muuttaja)
							VALUES ('$cid', '$tuoteno', '$kukarow[yhtio]', '$kukarow[kuka]', now(), now(), '$kukarow[kuka]')";
				$result = pupe_query($query);
			}

			//is‰ on lˆytynyt, lapsi ei
			if (($fid != "") and ($cid == "")) {
				# Siirret‰‰n ketjun muita eteenp‰in jarjestys + 1
				$query = "UPDATE korvaavat SET jarjestys=jarjestys+1
							WHERE jarjestys!=0 AND id='$fid' AND yhtio='{$kukarow['yhtio']}'";
				$result = pupe_query($query);

				# Lis‰t‰‰n uusi aina p‰‰tuotteeksi jarjestys=1
				//lis‰t‰‰n korvaava p‰‰tuotteeksi
				$query  = " INSERT INTO $taulu (id, tuoteno, yhtio, jarjestys, laatija, luontiaika, muutospvm, muuttaja)
							VALUES ('$fid', '$korvaava', '$kukarow[yhtio]', '1', '$kukarow[kuka]', now(), now(), '$kukarow[kuka]')";
				$result = pupe_query($query);
			}

			//kummatkin lˆytyiv‰t.. ja ne korvaa toisensa
			if ($fid != "" and $cid != "" and $fid == $cid) {
				if ($toim == "vastaavat") {
					echo "<font class='error'>".t("Tuotteet")." $korvaava <> $tuoteno ".t("ovat jo vastaavia")."!</font><br><br>";
				}
				else {
					echo "<font class='error'>".t("Tuotteet")." $korvaava <> $tuoteno ".t("korvaavat jo toisensa")."!</font><br><br>";
				}
			}
			elseif ($fid != "" and $cid != "" ) {
				if ($toim == "vastaavat") {
					echo "<font class='error'>".t("Tuotteet")." $korvaava, $tuoteno ".t("kuuluvat jo eri vastaavuusketjuihin")."!</font><br><br>";
				}
				else {
					echo "<font class='error'>".t("Tuotteet")." $korvaava, $tuoteno ".t("kuuluvat jo eri korvaavuusketjuihin")."!</font><br><br>";
				}
			}
		}
	}

	if ($tee == 'korvaa_vastaava') {
		echo "Korvataan vastaava";
		var_dump($_POST);
		$query  = "SELECT * FROM $taulu WHERE tunnus = '$tunnus' AND yhtio = '$kukarow[yhtio]'";
		$result = pupe_query($query);
		$row    = mysql_fetch_array($result);
		$id		= $row['id'];

		$query = "UPDATE vastaavat SET tuoteno='$korvaava' WHERE yhtio='{$kukarow['yhtio']}' and tuoteno='$korvattava'";
		$result = pupe_query($query);

		$query  = "SELECT * FROM $taulu WHERE id = '$id' AND yhtio = '$kukarow[yhtio]'";
		$result = pupe_query($query);
		$row    = mysql_fetch_array($result);
		$tuoteno = $row['tuoteno'];
	}

	if ($tuoteno != '') {
		$query  = "SELECT * FROM $taulu WHERE tuoteno = '$tuoteno' AND yhtio = '$kukarow[yhtio]'";
		$result = pupe_query($query);

		echo "<font class='head'>".t("Tuotenumero").": $tuoteno</font><hr>";

		if (mysql_num_rows($result) == 0) {
			$query = "SELECT * FROM tuote WHERE tuoteno = '$tuoteno' AND yhtio = '$kukarow[yhtio]'";
			$result = pupe_query($query);

			if (mysql_num_rows($result) == 0) {
				echo "<br><font class='error'>".t("Tuotenumeroa")." $tuoteno ".t("ei ole perustettu")."!</font><br>";
				$ok=1;
			}
			else {
				if ($toim == "vastaavat") {
					echo "<br><font class='message'>".t("Tuotteella ei ole vastaavia tuotteita")."!</font>";
				}
				else {
					echo "<br><font class='message'>".t("Tuotteella ei ole korvaavia tuotteita")."!</font>";
				}
			}
		}
		else {
			// tuote lˆytyi, joten haetaan sen id...
			$row    = mysql_fetch_array($result);
			$id		= $row['id'];

			$query = "SELECT * FROM $taulu WHERE id = '$id' AND yhtio = '$kukarow[yhtio]' ORDER BY if(jarjestys=0, 9999, jarjestys), tuoteno";
			$result = pupe_query($query);

			echo "<br><table>";
			echo "<tr>";
			if ($toim == "vastaavat") {
				echo "<th>".t("Vastaavia tuotteita")."</td>";
			}
			else {
				echo "<th>".t("Korvaavia tuotteita")."</td>";
			}

			echo "<th>".t("J‰rjestys")."</th>";
			echo "<td class='back'></td></tr>";

			while ($row = mysql_fetch_array($result)) {
				$error = "";
				$query = "SELECT * FROM tuote WHERE tuoteno = '$row[tuoteno]' AND yhtio = '$kukarow[yhtio]'";
				$res   = pupe_query($query);

				if (mysql_num_rows($res) == 0) {
					$error = "<font class='error'>(".t("Tuote ei en‰‰ rekisteriss‰")."!)</font>";
				}

				if ($row['jarjestys'] == 1) {
					$paatuote = $row;
				}

				echo "<tr>
					<td>$row[tuoteno] $error</td>

					<form action='korvaavat.php' method='post' autocomplete='off'>
					<td><input size='5' type='text' name='prio' value='$row[jarjestys]'></td>
					<input type='hidden' name='tunnus' value='$row[tunnus]'>
					<input type='hidden' name='tee' value='muutaprio'>
					<input type='hidden' value='$toim' name='toim'>
					</form>

					<form action='korvaavat.php' method='post'>
					<td class='back'>
					<input type='hidden' name='tunnus' value='$row[tunnus]'>
					<input type='hidden' name='tee' value='del'>
					<input type='hidden' value='$toim' name='toim'>
					<input type='submit' value='".t("Poista")."'>
					</td>
					</form>";

				if ($taulu == 'korvaavat') {
					# P‰‰tuotteen voi synkronoida vastaavat tauluun
					$query = "SELECT * FROM vastaavat WHERE yhtio='{$kukarow['yhtio']}' AND tuoteno='{$row['tuoteno']}'";
					$rresult = pupe_query($query);
					$vastaava = mysql_num_rows($rresult);

					# Tuotteella on vastaavat taulussa merkint‰ ja se ei ole jo valmiiksi p‰‰tuote
					if ($vastaava > 0 && $row['jarjestys'] != 1) {
						echo "<td class='back'>
							<form action='korvaavat.php' method='post'>
							<input type='hidden' name='tee' value='korvaa_vastaava'>
							<input type='hidden' name='tunnus' value='$row[tunnus]'>
							<input type='hidden' name='korvattava' value='$row[tuoteno]'>
							<input type='hidden' name='korvaava' value='$paatuote[tuoteno]'>
							<button>Korvaa</button>
							</form>
							</td>";
						echo "<td class='back'>Korvaa vastaava t‰m‰n ketjun p‰‰tuotteella</td>";
					}
				}

				echo "</tr>";
			}

			echo "</table>";
		}

		if ($ok != 1) {
			echo "<form action='korvaavat.php' method='post' autocomplete='off'>
					<input type='hidden' name='tuoteno' value='$tuoteno'>
					<input type='hidden' name='tee' value='add'>
					<input type='hidden' value='$toim' name='toim'>
					<hr>";

			if ($toim == "vastaavat") {
				echo t("Lis‰‰ vastaava tuote").": ";
			}
			else {
				echo t("Lis‰‰ korvaava tuote").": ";
			}

			echo "<input type='text' name='korvaava'>
					<input type='submit' value='".t("Lis‰‰")."'>
					</form>";
		}
	}

	$formi = 'etsituote';
	$kentta = 'tuoteno';

	require "inc/footer.inc";
?>