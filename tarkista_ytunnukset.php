<?php

	require ("inc/parametrit.inc");

	js_popup();
	enable_ajax();

	echo "<font class='head'>".t("Tarkista asiakkaiden ja toimittajien ytunnukset")."</font><hr><br>";

	//	Mikäli asiakkaalla on jokin y-tunnustarkistus voimassa, tarkistetaan, että asiakkaiden ytunnukset ovat sitä mitä pitäisi..
	if ($yhtiorow["ytunnus_tarkistukset"] != "E") {

		$rajaus = "";

		$asiakasQuery = "	SELECT 'asiakas' taulu, ytunnus, nimi, tunnus
							FROM asiakas
							WHERE yhtio = '{$kukarow["yhtio"]}' and laji = ''";

		$toimittajaQuery = "	SELECT 'toimi' taulu, ytunnus, nimi, tunnus
								FROM toimi
								WHERE yhtio = '{$kukarow["yhtio"]}'";

		if ($yhtiorow["ytunnus_tarkistukset"] == "") {
			$query = "	(
							$asiakasQuery
						)
						UNION
						(
							$toimittajaQuery
						)
						ORDER BY taulu";
		}
		elseif ($yhtiorow["ytunnus_tarkistukset"] == "T") {
			$query = $toimittajaQuery;
		}
		elseif ($yhtiorow["ytunnus_tarkistukset"] == "A") {
			$query = $asiakasQuery;
		}

		echo "<font class='info'>".t("Tarkistetaan yritysasiakkaiden ja toimittajien ytunnukset yhtiön parametrien mukaisesti.")."</font><br><br>";

		$result = pupe_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {

			echo "<table>
					<tr>
						<th>".t("Rekisteri")."</th>
						<th>".t("Y-tunnus")."</th>
						<th>".t("Nimi")."</th>
						<td class='back'></td>
					</tr>";

			while ($row = mysql_fetch_assoc($result)) {

				$onHetu = tarkistahetu($row["ytunnus"]);
				$onYtunnus = tarkistaytunnus($row["ytunnus"]);

				if ($row["taulu"] == "asiakas" and $onYtunnus === FALSE or ($row["taulu"] == "toimi" and $onYtunnus === FALSE and $onHetu === FALSE)) {

					$muokkaanappi = "";

					if ($row["taulu"] == "asiakas") {
						$muokkaanappi = "
							<form action='$palvelin2/yllapito.php' method='post'>
								<input type='hidden' name='toim'		value='asiakas!!!TUKKUMYYNTI!!!true'>
								<input type='hidden' name='laji'		value=''>
								<input type='hidden' name='tunnus'		value='{$row["tunnus"]}'>
								<input type='hidden' name='lopetus'		value='$PHP_SELF?'>
								<input type='submit' value='".t("Muokkaa asiakasta")."'>
							</form>";
					}
					elseif ($row["taulu"] == "toimi") {
						$muokkaanappi = "
							<form action='$palvelin2/yllapito.php' method='post'>
								<input type='hidden' name='toim'		value='toimi'>
								<input type='hidden' name='laji'		value=''>
								<input type='hidden' name='tunnus'		value='{$row["tunnus"]}'>
								<input type='hidden' name='lopetus'		value='$PHP_SELF?'>
								<input type='submit' value='".t("Muokkaa toimittajaa")."'>
							</form>";
					}
					echo "	<tr>
								<td>{$row["taulu"]}</td>
								<td>{$row["ytunnus"]}</td>
								<td>{$row["nimi"]}</td>
								<td class='back'>$muokkaanappi</td>
							</tr>";
				}
			}

			echo "</table>";
		}
		else {
			echo "<font class='message'>".t("Tarkastettavia kohteita ei ole.")."</font>";
		}
	}
	else {
		echo "<font class='message'>".t("Yrityksellä ei ole y-tunnusten tarkistus käytössä.")."</font>";
	}

	require("inc/footer.inc");
