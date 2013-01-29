<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Verottomat korvaukset")."</font><hr><br>";

	echo "<form method='post'>";
	echo "<input type='hidden' name='tee' value ='NAYTA'>";

	echo "<table>";
	echo "<tr>";
	echo "<th>".t("Valitse vuosi")."</th>";
	echo "<td>";

	$sel = array();
	$sel[$vv] = "SELECTED";

	if (!isset($vv)) $vv = date("Y");

	echo "<select name='vv'>";
	for ($i = date("Y"); $i >= date("Y")-4; $i--) {
		echo "<option value='$i' $sel[$i]>$i</option>";
	}
	echo "</select>";
	echo "</td>";

	echo "<td class='back'><input type='submit' value='".t("Näytä")."'></td>";
	echo "</tr>";

	echo "</table>";

	echo "</form>";
	echo "<br>";

	if ($tee == "NAYTA") {

		$query = "	SELECT
					toimi.tunnus,
					toimi.ytunnus,
					if(kuka.nimi IS NULL, concat('*POISTETTU* ', toimi.nimi), kuka.nimi) nimi,
					tuote.kuvaus,
					avg(tilausrivi.hinta) hinta,
					sum(tilausrivi.kpl) kpl,
					sum(tilausrivi.rivihinta) yhteensa
					FROM lasku
					JOIN toimi on (toimi.yhtio = lasku.yhtio and toimi.tunnus = lasku.liitostunnus)
					LEFT JOIN kuka ON (kuka.yhtio = lasku.yhtio and kuka.kuka = toimi.nimi)
					JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus)
					JOIN tuote ON (tuote.yhtio = lasku.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.tuotetyyppi IN ('A', 'B') and tuote.kuvaus in ('50', '56'))
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					AND lasku.tila = 'Y'
					AND lasku.tilaustyyppi = 'M'
					AND lasku.tapvm >= '$vv-01-01'
					AND lasku.tapvm <= '$vv-12-31'
					GROUP BY 1,2,3,4
					ORDER BY nimi";
		$result = pupe_query($query);

		if (mysql_num_rows($result) > 0) {

			echo "<table>";

			echo "<tr>";
			echo "<th>".t("Kuka")."</th>";
			echo "<th>".t("Verokodi")." / ".t("Korvaus")."</th>";
			echo "<th>".t("Kappaletta")."</th>";
			echo "<th>".t("Hinta")."</th>";
			echo "<th>".t("Yhteensä")."</th>";
			echo "</tr>";

			$ednimi    = "";
			$summat    = array();
			$kappaleet = array();
			$file 	   = "";
			$lask 	   = 1;
			$vspserie  = array();

			while ($row = mysql_fetch_assoc($result)) {

				if ($row["kuvaus"] == '50') {
					$kuvaus = t("Päivärahat ja ateriakorvaukset");
				}
				else {
					$kuvaus = t("Verovapaa kilometrikorvaus");
				}

				if ($ednimi == "" or $ednimi != $row["nimi"]) {
					$nimi = $row["nimi"];

					if ($ednimi != "") {
						echo "<tr><td class='back' colspan='5'></td></tr>";
					}
				}
				else {
					$nimi = "";
				}

				echo "<tr class='aktiivi'>";

				if ($nimi != "") {
					echo "<td>$nimi</td>";
				}
				else {
					echo "<td class='back'></td>";
				}

				echo "<td>$row[kuvaus] $kuvaus</td>";
				echo "<td align='right'>".number_format($row["kpl"], 0, ',', ' ')."</td>";
				echo "<td align='right'>".number_format($row["hinta"], 2, ',', ' ')."</td>";
				echo "<td align='right'>".number_format($row["yhteensa"], 2, ',', ' ')."</td>";
				echo "</tr>";

				if ($row['kuvaus'] == 50) {
					$vspserie[$row["ytunnus"]]["paivarahat"] = $row["kpl"];
				}

				if ($row['kuvaus'] == 56) {
					$vspserie[$row["ytunnus"]]["kilsat"] = $row["yhteensa"];
				}

				// erittely
				$query = "	SELECT tilausrivi.tuoteno, tuote.nimitys, avg(tilausrivi.hinta) hinta, sum(tilausrivi.kpl) kpl, sum(tilausrivi.rivihinta) yhteensa
							FROM lasku
							JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus)
							JOIN tuote ON (tuote.yhtio = lasku.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.tuotetyyppi IN ('A','B') and tuote.kuvaus = '$row[kuvaus]')
							LEFT JOIN kuka ON (kuka.yhtio = lasku.yhtio and kuka.kuka = lasku.toim_ovttunnus)
							WHERE lasku.yhtio = '$kukarow[yhtio]'
							AND lasku.tila = 'Y'
							AND lasku.tilaustyyppi = 'M'
							AND lasku.tapvm >= '$vv-01-01'
							AND lasku.tapvm <= '$vv-12-31'
							AND lasku.liitostunnus = '$row[tunnus]'
							GROUP BY tuote.tuoteno";
				$eres = pupe_query($query);

				while ($erow = mysql_fetch_assoc($eres)) {

					if ($row['kuvaus'] == 56) {
						$vspserie[$row["ytunnus"]]["kotimaanpaivat"] = 1;
					}

					if ($row['kuvaus'] == 56) {
						$vspserie[$row["ytunnus"]]["kotimaanpuolipaivat"] = 1;
					}

					if ($row['kuvaus'] == 56) {
						$vspserie[$row["ytunnus"]]["ulkomaanpaivat"] = 1;
					}

					echo "<tr class='aktiivi'>";
					echo "<td class='back'></td>";
					echo "<td class='spec'><font class='info'>&raquo; $erow[tuoteno] - $erow[nimitys]</font></td>";
					echo "<td class='spec' align='right'>".number_format($erow["kpl"], 0, ',', ' ')."</td>";
					echo "<td class='spec' align='right'>".number_format($erow["hinta"], 2, ',', ' ')."</td>";
					echo "<td class='spec' align='right'>".number_format($erow["yhteensa"], 2, ',', ' ')."</td>";
					echo "</tr>";
				}

				$lask++;
				$ednimi = $row["nimi"];
				$summat[$row["kuvaus"]] += $row["yhteensa"];
				$kappaleet[$row["kuvaus"]] += $row["kpl"];
			}

			echo "<tr><td class='back' colspan='5'></td></tr>";

			echo "<tr class='aktiivi'>";
			echo "<th colspan='2'>50 ".t("Päivärahat ja ateriakorvaukset")."</th>";
			echo "<td align='right'>".number_format($kappaleet[50], 2, ',', ' ')."</td>";
			echo "<td colspan='2' align='right'>".number_format($summat[50], 2, ',', ' ')."</td>";
			echo "</tr>";

			echo "<tr class='aktiivi'>";
			echo "<th colspan='2'>56 ".t("Verovapaa kilometrikorvaus")."</th>";
			echo "<td align='right'>".number_format($kappaleet[56], 2, ',', ' ')."</td>";
			echo "<td colspan='2' align='right'>".number_format($summat[56], 2, ',', ' ')."</td>";
			echo "</tr>";

			echo "</table>";


			$file .= "000:VSPSERIE";
			$file .= "101:0";
			$file .= "110:P";
			$file .= "109:$vv";
			$file .= "102:{$yhtiorow['ytunnus']}";
			$file .= "111:{$row['ytunnus']}";
			$file .= "114:0";
			$file .= "115:0";
			$file .= "150:0";
			$file .= "151:0";
			$file .= "152:0";
			$file .= "153:0";
			$file .= "154:0";
			$file .= "155:404";
			$file .= "156:18584";
			$file .= "157:0";
			$file .= "999:$lask";


			$filenimi = "VSPSERIE-$kukarow[yhtio]-".date("dmy-His").".txt";
			file_put_contents("dataout/".$filenimi, $file);

			echo "	<form method='post' class='multisubmit'>
						<input type='hidden' name='tee' value='lataa_tiedosto'>
						<input type='hidden' name='lataa_tiedosto' value='1'>
						<input type='hidden' name='kaunisnimi' value='".t("arvonlisaveroilmoitus")."-$ilmoituskausi.txt'>
						<input type='hidden' name='filenimi' value='$filenimi'>
						<input type='submit' name='tallenna' value='".t("Tallenna tiedosto")."'>
					</form><br><br>";

		}
	}

	require ("inc/footer.inc");
