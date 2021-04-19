<?php

	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Etsi ja poista p�itt�in menev�t suoritukset")."</font><hr>";

	if ($toim == "SUPER") {
		$tilitselisa = "";
	}
	else {
		$tilitselisa = " and b.tilino = a.tilino ";
	}
	

	//$debug = 1;
	if ($tee == 'N') {

		$query  = "LOCK TABLES suoritus as a READ, suoritus as b READ, suoritus WRITE, tiliointi WRITE, sanakirja WRITE, avainsana as avainsana_kieli READ";
		$result = mysql_query($query) or pupe_error($query);

		//Etsit��n nolla suoritukset
		$query = "	SELECT a.nimi_maksaja, a.kirjpvm, a.summa, a.ltunnus, a.tunnus
					FROM suoritus a
					WHERE a.yhtio = '$kukarow[yhtio]' and
					a.kohdpvm = '0000-00-00' and
					a.summa = 0";
		$paaresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($paaresult) > 0) {

			while ($suoritusrow = mysql_fetch_array ($paaresult)) {

				$tapvm = $suoritusrow['kirjpvm'];

				//Kirjataan suoritukset k�ytetyksi
				$query = "UPDATE suoritus set kohdpvm = '$tapvm' where tunnus='$suoritusrow[tunnus]'";
				if ($debug == 1) echo "$query<br>";
				else $result = mysql_query($query) or pupe_error($query);

				echo "<font class='message'>".t("Suoritus")." $suoritusrow[nimi_maksaja] $suoritusrow[summa] ".t("poistettu")."!</font><br>";
			}

		}

		$query  = "UNLOCK TABLES";
		$result = mysql_query($query) or pupe_error($query);

	}

	if ($tee == 'T') {

		$query  = "LOCK TABLES suoritus as a READ, suoritus as b READ, suoritus WRITE, tiliointi WRITE, sanakirja WRITE, avainsana as avainsana_kieli READ";
		$result = mysql_query($query) or pupe_error($query);

		$query  = "	SELECT a.tunnus atunnus, b.tunnus btunnus, a.ltunnus altunnus, b.ltunnus bltunnus, a.kirjpvm akirjpvm, a.summa asumma, b.kirjpvm bkirjpvm, b.summa bsumma, a.nimi_maksaja
					FROM suoritus a
					JOIN suoritus b ON (b.yhtio = a.yhtio and b.kohdpvm = a.kohdpvm and b.asiakas_tunnus = a.asiakas_tunnus and b.valkoodi = a.valkoodi and b.summa * -1 = a.summa $tilitselisa)
					WHERE a.yhtio = '$kukarow[yhtio]' and
					a.kohdpvm = '0000-00-00' and
					a.summa < 0";
		$paaresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($paaresult) > 0) {

			while ($suoritusrow = mysql_fetch_array ($paaresult)) {

				// Onko tilioinnit veil� olemassa ja suoritus oikeassa tilassa
				$query  = "SELECT tunnus, kirjpvm from suoritus where tunnus in ('$suoritusrow[atunnus]', '$suoritusrow[btunnus]') and kohdpvm = '0000-00-00'";
				$result = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result) == 2) {

					$suoritus1row = mysql_fetch_array($result);
					$suoritus2row = mysql_fetch_array($result);

					$query  = "SELECT ltunnus, summa, tilino from tiliointi where tunnus='$suoritusrow[altunnus]'";
					$result = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($result) == 1) {

						$tiliointi1row = mysql_fetch_array ($result);

						$query  = "SELECT ltunnus, summa, tilino from tiliointi where tunnus='$suoritusrow[bltunnus]'";
						$result = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($result) == 1) {

							$tiliointi2row = mysql_fetch_array($result);

							if ($suoritus1row['kirjpvm'] < $suoritus2row['kirjpvm']) {
								$tapvm = $suoritus2row['kirjpvm'];
							}
							else {
								$tapvm = $suoritus1row['kirjpvm'];
							}

							// Nyt kaikki on hyvin ja voimme tehd� p�ivitykset
							// Kirjataan p�itt�inmeno selvittelytilin kautta
							// Tili�innilt� otetaan selvittelytilin vastatili

							$query = "INSERT INTO tiliointi (yhtio, ltunnus, tapvm, summa, tilino, selite, lukko, laatija, laadittu) 
										values ('$kukarow[yhtio]', '$tiliointi1row[ltunnus]', '$tapvm', $tiliointi1row[summa], '$yhtiorow[selvittelytili]', '".t('Suoritettu p�itt�in')."',1,'$kukarow[kuka]',now())";
							if ($debug == 1) echo "$query<br>";
							else $result = mysql_query($query) or pupe_error($query);

							$query = "INSERT INTO tiliointi (yhtio, ltunnus, tapvm, summa, tilino, selite, lukko, laatija, laadittu) 
										values ('$kukarow[yhtio]', '$tiliointi1row[ltunnus]', '$tapvm', $tiliointi1row[summa] * -1, '$tiliointi1row[tilino]', '".t('Suoritettu p�itt�in')."',1,'$kukarow[kuka]',now())";
							if ($debug == 1) echo "$query<br>";
							else $result = mysql_query($query) or pupe_error($query);

							$query = "INSERT INTO tiliointi (yhtio, ltunnus, tapvm, summa, tilino, selite, lukko, laatija, laadittu) 
										values ('$kukarow[yhtio]', '$tiliointi2row[ltunnus]', '$tapvm', $tiliointi2row[summa], '$yhtiorow[selvittelytili]', '".t('Suoritettu p�itt�in')."',1,'$kukarow[kuka]',now())";
							if ($debug == 1) echo "$query<br>";
							else $result = mysql_query($query) or pupe_error($query);

							$query = "INSERT INTO tiliointi (yhtio, ltunnus, tapvm, summa, tilino, selite, lukko, laatija, laadittu) 
										values ('$kukarow[yhtio]', '$tiliointi2row[ltunnus]', '$tapvm', $tiliointi2row[summa] * -1, '$tiliointi1row[tilino]', '".t('Suoritettu p�itt�in')."', 1,'$kukarow[kuka]',now())";
							if ($debug == 1) echo "$query<br>";
							else $result = mysql_query($query) or pupe_error($query);

							//Kirjataan suoritukset k�ytetyksi
							$query = "UPDATE suoritus set kohdpvm = '$tapvm', summa=0 where tunnus='$suoritus1row[tunnus]'";
							if ($debug == 1) echo "$query<br>";
							else $result = mysql_query($query) or pupe_error($query);

							$query = "UPDATE suoritus set kohdpvm = '$tapvm', summa=0 where tunnus='$suoritus2row[tunnus]'";
							if ($debug == 1) echo "$query<br>";
							else $result = mysql_query($query) or pupe_error($query);

							echo "<font class='message'>".t("Kohdistus ok!")." $suoritusrow[nimi_maksaja] ".($tiliointi2row["summa"]*1)." / ".($tiliointi2row["summa"]*-1)."</font><br>";
						}
						else {
							echo "J�rjestelm�virhe 1";
						}
					}
					else {
						echo "J�rjestelm�virhe 2";
					}
				}
				else {
					echo "<font class='error'>".t('Suoritus oli jo k�ytetty')."<br>";
				}
			}
		}

		$query  = "UNLOCK TABLES";
		$result = mysql_query($query) or pupe_error($query);
	}

	if ($tee == '') {

		//Etsit��n p�itt�in menev�t suoritukset
		$query = "	SELECT a.nimi_maksaja, a.kirjpvm, a.summa, b.nimi_maksaja, b.kirjpvm, b.summa
					FROM suoritus a
					JOIN suoritus b ON (b.yhtio = a.yhtio and b.kohdpvm = a.kohdpvm and b.asiakas_tunnus = a.asiakas_tunnus and b.valkoodi = a.valkoodi and b.summa * -1 = a.summa $tilitselisa)
					WHERE a.yhtio = '$kukarow[yhtio]' and
					a.kohdpvm = '0000-00-00' and
					a.summa < 0";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {

			echo "<table><tr>";

			for ($i = 0; $i < mysql_num_fields($result); $i++) {
				echo "<th>" . t(mysql_field_name($result,$i))."</th>";
			}
			echo "</tr>";

			while ($trow = mysql_fetch_array ($result)) {

				echo "<tr>";
				for ($i=0; $i<mysql_num_fields($result); $i++) {
					echo "<td>$trow[$i]</td>";
				}
				echo "</tr>";

			}
			echo "</table><br>";

			echo "	<form action = '$php_self' method='post'>
					<input type='hidden' name = 'toim' value='$toim'>
					<input type='hidden' name = 'tee' value='T'>
					<input type='Submit' value='".t('Kohdista n�m� tapahtumat p�itt�in')."'>
					</form><br>";
		}
		else {
			echo "<font class='message'>" . t("P�itt�in menevi� suorituksia ei l�ytynyt!") . "</font><br>";
		}

		//Etsit��n nolla suoritukset
		$query = "	SELECT a.nimi_maksaja, a.kirjpvm, a.summa
					FROM suoritus a
					WHERE a.yhtio = '$kukarow[yhtio]' and
					a.kohdpvm = '0000-00-00' and
					a.summa = 0";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {

			echo "<br><table><tr>";

			for ($i = 0; $i < mysql_num_fields($result); $i++) {
				echo "<th>" . t(mysql_field_name($result,$i))."</th>";
			}
			echo "</tr>";

			while ($trow = mysql_fetch_array ($result)) {

				echo "<tr>";
				for ($i=0; $i<mysql_num_fields($result); $i++) {
					echo "<td>$trow[$i]</td>";
				}
				echo "</tr>";

			}
			echo "</table><br>";

			echo "	<form action = '$php_self' method='post'>
					<input type='hidden' name = 'toim' value='$toim'>
					<input type='hidden' name = 'tee' value='N'>
					<input type='Submit' value='".t('Poista n�m� nollatapahtumat')."'>
					</form>";
		}

	}

	require ("../inc/footer.inc");

?>
