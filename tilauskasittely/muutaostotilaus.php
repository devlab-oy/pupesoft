<?php
	require "../inc/parametrit.inc";

	if ($tee == '') {
		echo "<font class='head'>".t("Valitse ostotilaus").":</font><hr>";

		// Annetaan mahdollisuus aktivoida edellinen tilaus, jos se on olemassa
		if ($kukarow["kesken"] != 0) {
		
			$query   = "SELECT * 
						FROM lasku 
						WHERE tunnus='$kukarow[kesken]' and tila='O'";
			$gresult = mysql_query($query) or pupe_error($query);
			$grow    = mysql_fetch_array ($gresult);

			if ($grow["tila"] == 'O') {
				echo "<table>
						<tr><th>".t("Sinulla on kesken tilaus")." $grow[tunnus]: $grow[nimi] ($grow[luontiaika])</th>
						<form action = 'tilaus_osto.php' method='post'>
						<input type='hidden' name='tee' value = 'Y'>
						<input type='hidden' name='aktivoinnista' value='true'>
						<input type='hidden' name='tilausnumero' value='$grow[tunnus]'>
						<td><input type='submit' value = '".t("Muokkaa tilausta")."'></td></tr></form>
						</table>";
			}
		}

		// Tehd‰‰n popup k‰ytt‰j‰n lep‰‰m‰ss‰ olevista tilauksista
		$query = "	SELECT tunnus, concat_ws(' ', nimi, nimitark, luontiaika) asiakas, tila
					FROM lasku use index (tila_index)
					WHERE lasku.yhtio = '$kukarow[yhtio]' and laatija='$kukarow[kuka]' and alatila='' and tila = 'O'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result)!=0) {
			$row = mysql_fetch_array($result);

			echo "<br><table>
					<tr><th>".t("Lep‰‰v‰t tilauksesi")."</th>
					<form method='post' action='tilaus_osto.php'>
					<input type='hidden' name='tee' value='Y'>
					<input type='hidden' name='aktivoinnista' value='true'>
					<td><select name='tilausnumero'>";

			echo "<option value='$row[tunnus]'>$row[asiakas]";

			while ($row = mysql_fetch_array($result)) {
					echo "<option value='$row[tunnus]'>$row[asiakas]";
			}
			echo "</select></td>
					<td class='back'><input type='submit' name='tila' value='".t("Muokkaa tilausta")."'></td></tr></form></table>";
		}


		// N‰ytet‰‰n muuten vaan sopivia tilauksia
		echo "<br><font class='head'>".t("Etsi tilauksia").":<hr></font>";
		echo "<form action='$PHP_SELF' method='post'>
				  ".t("Syˆt‰ tilausnumero tai nimen osa").":
				  <input type='hidden' name='toim' value='$toim'>
				  <input type='text' name='etsi'>
				  <input type='Submit' value = '".t("Etsi")."'>
				  </form>".t("Vain 50 ensimm‰ist‰ tilausta n‰ytet‰‰n. K‰yt‰ hakua rajataksesi tilauksia").".";

		  $haku='';
		  if (is_string($etsi))  $haku="and nimi like '%$etsi%'";
		  if (is_numeric($etsi)) $haku="and lasku.tunnus like '$etsi%'";



			if ($toim=='super') {
				// Etsit‰‰n muutettavaa tilausta
				$query = "	SELECT lasku.tunnus 'tilaus', concat_ws(' ', nimi, nimitark) asiakas, ytunnus, luontiaika, lasku.laatija, alatila, tila
							FROM lasku use index (tila_index), tilausrivi use index (yhtio_otunnus)
							WHERE lasku.yhtio = '$kukarow[yhtio]' 
							and lasku.tila = 'O' 
							and lasku.alatila != ''
							and tilausrivi.yhtio = lasku.yhtio
							and tilausrivi.otunnus = lasku.tunnus
							and tilausrivi.uusiotunnus = 0 
							$haku
							GROUP by 1
							ORDER by luontiaika desc
							LIMIT 50";
		  }
			else {
				// Etsit‰‰n muutettavaa tilausta
				$query = "	SELECT tunnus 'tilaus', concat_ws(' ', nimi, nimitark) asiakas, ytunnus, luontiaika, laatija, alatila, tila
							FROM lasku use index (tila_index)
							WHERE lasku.yhtio = '$kukarow[yhtio]' and tila='O' and alatila='' $haku
							ORDER by luontiaika desc
							LIMIT 50";
			}

			$result = mysql_query($query) or pupe_error($query);

		  if (mysql_num_rows($result)!=0) {

			echo "<table border='0' cellpadding='2' cellspacing='1'>";

			echo "<tr>";
			for ($i=0; $i < mysql_num_fields($result)-2; $i++)
			{
				echo "<th align='left'>".t(mysql_field_name($result,$i))."</th>";
			}
			echo "<th align='left'>".t("tyyppi")."</th><td class='back'></td></tr>";

			while ($row = mysql_fetch_array($result))
			{
				echo "<tr>";
				for ($i=0; $i<mysql_num_fields($result)-2; $i++)
				{
					echo "<td>$row[$i]</td>";
				}

				$laskutyyppi=$row["tila"];
				$alatila=$row["alatila"];

				//tehd‰‰n selv‰kielinen tila/alatila
				require "../inc/laskutyyppi.inc";

				if ($row['tila']!='O') $laskutyyppi = "Ostolasku ".$laskutyyppi;

				echo "<td>".t("$laskutyyppi")." ".t("$alatila")."</td>";

				echo "	<form method='post' action='tilaus_osto.php'><td class='back'>
						<input type='hidden' name='tee' value='Y'>
						<input type='hidden' name='tilausnumero' value='$row[tilaus]'>
						<input type='hidden' name='aktivoinnista' value='true'>
						<input type='submit' name='tila' value='".t("Aktivoi")."'></td></tr></form>";

				echo "</tr>";
			}
			echo "</table>";
		}
		else
		{
		  echo "".t("Ei tilauksia")."...";
		}


	}

	require ("../inc/footer.inc");
?>
