<?php
	require ("inc/parametrit.inc");

	if ($toim == "vastaavat") {
		$taulu = "vastaavat";
		echo "<font class='head'>".t("Vastaavien ylläpito")."</font><hr>";
	}
	else {
		$toim = "";
		$taulu = "korvaavat";
		echo "<font class='head'>".t("Korvaavien ylläpito")."</font><hr>";
	}

	echo "<form action='korvaavat.php' method='post' name='etsituote' autocomplete='off'>
		  ".t("Etsi tuotetta")." <input type='text' name='tuoteno'>
		  <input type='submit' value='".t("Hae")."'>
		  <input type='hidden' value='$toim' name='toim'>
		  </form><br><br>";

	if ($tee == 'del') {
		//haetaan poistettavan tuotteen id.. käyttäjästävällistä..
		$query  = "SELECT * FROM $taulu WHERE tunnus = '$tunnus' AND yhtio = '$kukarow[yhtio]'";
		$result = pupe_query($query);
		$row    = mysql_fetch_array($result);
		$id		= $row['id'];

		//poistetaan korvaava..
		$query  = "DELETE FROM $taulu WHERE tunnus = '$tunnus' AND yhtio = '$kukarow[yhtio]'";
		$result = pupe_query($query);

		//näytetään silti loput.. kilttiä.
		$query  = "SELECT * FROM $taulu WHERE id = '$id' AND yhtio = '$kukarow[yhtio]'";
		$result = pupe_query($query);
		$row    = mysql_fetch_array($result);
		$tuoteno = $row['tuoteno'];
	}

	if ($tee == 'muutaprio') {
		//haetaan poistettavan tuotteen id.. käyttäjästävällistä..
		$query  = "SELECT * FROM $taulu WHERE tunnus = '$tunnus' AND yhtio = '$kukarow[yhtio]'";
		$result = pupe_query($query);
		$row    = mysql_fetch_array($result);
		$id		= $row['id'];

		//muutetaan prio..
		$query  = "UPDATE $taulu SET jarjestys = '$prio' WHERE tunnus = '$tunnus' AND yhtio = '$kukarow[yhtio]'";
		$result = pupe_query($query);

		//näytetään silti loput.. kilttiä.
		$query  = "SELECT * FROM $taulu WHERE id = '$id' AND yhtio = '$kukarow[yhtio]'";
		$result = pupe_query($query);
		$row    = mysql_fetch_array($result);
		$tuoteno= $row['tuoteno'];
	}

	if ($tee == 'add') {
		// tutkitaan onko lisättävä tuote oikea tuote...
		$query  = "SELECT * FROM tuote WHERE tuoteno = '$korvaava' AND yhtio = '$kukarow[yhtio]'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Lisäys ei onnistu! Tuotetta")." $korvaava ".t("ei löydy")."!</font><br><br>";
		}
		else {
			$query  = "SELECT * FROM $taulu WHERE tuoteno = '$tuoteno' AND yhtio = '$kukarow[yhtio]'";
			$result = pupe_query($query);

			// katotaan onko isä tuote lisättty..
			if (mysql_num_rows($result) != 0) {
				//jos on, otetaan ID luku talteen...
				$row    = mysql_fetch_array($result);
				$fid	= $row['id'];
			}

			//katotaan onko korvaava jo lisätty
			$query  = "SELECT * FROM $taulu WHERE tuoteno = '$korvaava' AND yhtio = '$kukarow[yhtio]'";
			$result = pupe_query($query);

			if (mysql_num_rows($result) != 0) {
				//korvaava on jo lisätty.. otetaan senki id..
				$row    = mysql_fetch_array($result);
				$cid	= $row['id'];
			}

			//jos kumpaakaan ei löytynyt...
			if (($cid == "") and ($fid == "")) {
				//silloin tämä on eka korvaava.. etsitään sopiva ID.
				$query  = "SELECT max(id) FROM $taulu";
				$result = pupe_query($query);
				$row    = mysql_fetch_array($result);
				$id 	= $row[0]+1;

				//lisätään "isä tuote"...
				$query  = "INSERT INTO $taulu (id, tuoteno, yhtio) VALUES ('$id', '$tuoteno', '$kukarow[yhtio]')";
				$result = pupe_query($query);

				// lisätään korvaava tuote...
				$query  = "INSERT INTO $taulu (id, tuoteno, yhtio) VALUES ('$id', '$korvaava', '$kukarow[yhtio]')";
				$result = pupe_query($query);
			}

			//lapsi on löytynyt, isää ei
			if (($cid != "") and ($fid == "")) {
				//lisätään "isä tuote"...
				$query  = "INSERT INTO $taulu (id, tuoteno, yhtio) VALUES ('$cid', '$tuoteno', '$kukarow[yhtio]')";
				$result = pupe_query($query);
			}

			//isä on löytynyt, lapsi ei
			if (($fid != "") and ($cid == "")) {
				//lisätään korvaava...
				$query  = "INSERT INTO $taulu (id, tuoteno, yhtio) VALUES ('$fid', '$korvaava', '$kukarow[yhtio]')";
				$result = pupe_query($query);
			}

			//kummatkin löytyivät.. ja ne korvaa toisensa
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
			// tuote löytyi, joten haetaan sen id...
			$row    = mysql_fetch_array($result);
			$id		= $row['id'];

			$query = "SELECT * FROM $taulu WHERE id = '$id' AND yhtio = '$kukarow[yhtio]' ORDER BY jarjestys, tuoteno";
			$result = pupe_query($query);

			echo "<br><table>";
			echo "<tr>";
			if ($toim == "vastaavat") {
				echo "<th>".t("Vastaavia tuotteita")."</td>";
			}
			else {
				echo "<th>".t("Korvaavia tuotteita")."</td>";
			}

			echo "<th>".t("Järjestys")."</th>";
			echo "<td class='back'></td></tr>";

			while ($row = mysql_fetch_array($result)) {
				$error = "";
				$query = "SELECT * FROM tuote WHERE tuoteno = '$row[tuoteno]' AND yhtio = '$kukarow[yhtio]'";
				$res   = pupe_query($query);

				if (mysql_num_rows($res) == 0) {
					$error = "<font class='error'>(".t("Tuote ei enää rekisterissä")."!)</font>";
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

					</tr>
					</form>";
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
				echo t("Lisää vastaava tuote").": ";
			}
			else {
				echo t("Lisää korvaava tuote").": ";
			}

			echo "<input type='text' name='korvaava'>
					<input type='submit' value='".t("Lisää")."'>
					</form>";
		}
	}

	$formi = 'etsituote';
	$kentta = 'tuoteno';

	require "inc/footer.inc";
?>