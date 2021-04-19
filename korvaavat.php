<?php
	require ("inc/parametrit.inc");

	echo "<font class='head'>".t("Korvaavien ylläpito")."</font><hr>";

	echo "<form action='korvaavat.php' method='post' autocomplete='off'>
		  ".t("Etsi tuotetta")." <input type='text' name='tuoteno'>
		  <input type='submit' value='".t("Hae")."'>
		  </form>";

	if ($tee=='del')
	{
		//haetaan poistettavan tuotteen id.. käyttäjästävällistä..
		$query  = "select * from korvaavat where tunnus='$tunnus' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		$row    = mysql_fetch_array($result);
		$id		= $row['id'];

		//poistetaan korvaava..
		$query  = "delete from korvaavat where tunnus='$tunnus' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		//näytetään silti loput.. kilttiä.
		$query  = "select * from korvaavat where id='$id' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		$row    = mysql_fetch_array($result);
		$tuoteno= $row['tuoteno'];
	}

	if ($tee=='muutaprio')
	{
		//haetaan poistettavan tuotteen id.. käyttäjästävällistä..
		$query  = "select * from korvaavat where tunnus='$tunnus' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		$row    = mysql_fetch_array($result);
		$id		= $row['id'];

		//muutetaan prio..
		$query  = "update korvaavat set jarjestys='$prio' where tunnus='$tunnus' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		//näytetään silti loput.. kilttiä.
		$query  = "select * from korvaavat where id='$id' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		$row    = mysql_fetch_array($result);
		$tuoteno= $row['tuoteno'];
	}

	if ($tee=='add')
	{
		// tutkitaan onko lisättävä tuote oikea tuote...
		$query  = "select * from tuote where tuoteno='$korvaava' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result)==0)
		{
			echo "<font class='error'>".t("Lisäys ei onnistu! Tuotetta")." $korvaava ".t("ei löydy")."!</font><br><br>";
		}
		else
		{
			$query  = "select * from korvaavat where tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			// katotaan onko isä tuote lisättty..
			if (mysql_num_rows($result)!=0)
			{
				//jos on, otetaan ID luku talteen...
				$row    = mysql_fetch_array($result);
				$fid	= $row['id'];
			}

			//katotaan onko korvaava jo lisätty
			$query  = "select * from korvaavat where tuoteno='$korvaava' and yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result)!=0)
			{
				//korvaava on jo lisätty.. otetaan senki id..
				$row    = mysql_fetch_array($result);
				$cid	= $row['id'];
			}

			//jos kumpaakaan ei löytynyt...
			if (($cid=="") and ($fid==""))
			{
				//silloin tämä on eka korvaava.. etsitään sopiva ID.
				$query  = "select max(id) from korvaavat";
				$result = mysql_query($query) or pupe_error($query);
				$row    = mysql_fetch_array($result);
				$id 	= $row[0]+1;

				//lisätään "isä tuote"...
				$query  = "insert into korvaavat (id, tuoteno, yhtio) values ('$id', '$tuoteno', '$kukarow[yhtio]')";
				$result = mysql_query($query) or pupe_error($query);

				// lisätään korvaava tuote...
				$query  = "insert into korvaavat (id, tuoteno, yhtio) values ('$id', '$korvaava', '$kukarow[yhtio]')";
				$result = mysql_query($query) or pupe_error($query);
			}

			//lapsi on löytynyt, isää ei
			if (($cid!="") and ($fid==""))
			{
				//lisätään "isä tuote"...
				$query  = "insert into korvaavat (id, tuoteno, yhtio) values ('$cid', '$tuoteno', '$kukarow[yhtio]')";
				$result = mysql_query($query) or pupe_error($query);
			}

			//isä on löytynyt, lapsi ei
			if (($fid!="") and ($cid==""))
			{
				//lisätään korvaava...
				$query  = "insert into korvaavat (id, tuoteno, yhtio) values ('$fid', '$korvaava', '$kukarow[yhtio]')";
				$result = mysql_query($query) or pupe_error($query);
			}

			//kummatkin löytyivät.. ja ne korvaa toisensa
			if ($fid != "" and $cid != "" and $fid == $cid)
			{
				echo "<font class='error'>".t("Tuotteet")." $korvaava <> $tuoteno ".t("korvaavat jo toisensa")."!</font><br><br>";
			}
			elseif ($fid != "" and $cid != "" ) {
				echo "<font class='error'>".t("Tuotteet")." $korvaava, $tuoteno ".t("kuuluvat jo eri korvaavuusketjuihin")."!</font><br><br>";
			}

		}
	}



	if ($tuoteno!='')
	{
		$query  = "select * from korvaavat where tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<font class='head'>".t("Tuotenumero").": $tuoteno</font><hr>";

		if (mysql_num_rows($result)==0)
		{
			$query = "select * from tuote where tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result)==0)
			{
				echo "<br><font class='error'>".t("Tuotenumeroa")." $tuoteno ".t("ei ole perustettu")."!</font><br>";
				$ok=1;
			}
			else
			{
				echo "<br><font class='message'>".t("Tuotteella ei ole korvaavia tuotteita")."!</font>";
			}
		}
		else
		{
			// tuote löytyi, joten haetaan sen id...
			$row    = mysql_fetch_array($result);
			$id		= $row['id'];

			$query = "select * from korvaavat where id='$id' and yhtio='$kukarow[yhtio]' order by jarjestys, tuoteno";
			$result = mysql_query($query) or pupe_error($query);

			echo "<br><table>";
			echo "<tr>";
			echo "<th>".t("Korvaavia tuotteita")."</td>";
			echo "<th>".t("Järjestys")."</th>";
			echo "<td class='back'></td></tr>";

			while ($row = mysql_fetch_array($result))
			{
				$error = "";
				$query = "select * from tuote where tuoteno='$row[tuoteno]' and yhtio='$kukarow[yhtio]'";
				$res   = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($res) == 0) {
					$error = "<font class='error'>(".t("Tuote ei enää rekisterissä")."!)</font>";
				}

				echo "<tr>
					<td>$row[tuoteno] $error</td>

					<form action='korvaavat.php' method='post'>
					<td><input size='5' type='text' name='prio' value='$row[jarjestys]'></td>
					<input type='hidden' name='tunnus' value='$row[tunnus]'>
					<input type='hidden' name='tee' value='muutaprio'>
					</form>

					<form action='korvaavat.php' method='post'>
					<td class='back'>
					<input type='hidden' name='tunnus' value='$row[tunnus]'>
					<input type='hidden' name='tee' value='del'>
					<input type='submit' value='".t("Poista")."'>
					</td>

					</tr>
					</form>";
			}

			echo "</table>";
		}

		if ($ok!=1)
		{
			echo "<form action='korvaavat.php' method='post' autocomplete='off'>
			<input type='hidden' name='tuoteno' value='$tuoteno'>
			<input type='hidden' name='tee' value='add'>
			<hr>
			".t("Lisää korvaava tuote").": <input type='text' name='korvaava'>
			<input type='submit' value='".t("Lisää")."'>
			</form>";
		}
	}
	require "inc/footer.inc";
?>
