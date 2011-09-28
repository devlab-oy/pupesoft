<?php

	$pupe_DataTables = "asiakaslista";

	require "inc/parametrit.inc";
	require "inc/asiakas.inc";

	echo "<font class='head'>".t("Kopioi asiakas").":</font><hr>";

	if ($tee == "write") {

		// Luodaan puskuri, jotta saadaan taulukot kuntoon
		$query = "	SELECT *
					FROM asiakas
					WHERE tunnus = '$id'";
		$result = mysql_query($query) or pupe_error($query);
		$trow = mysql_fetch_array($result);

		// Tarkistetaan
		$errori = '';
		require "inc/asiakastarkista.inc";

		for ($i=1; $i < mysql_num_fields($result)-1; $i++) {
			asiakastarkista (&$t, $i, $result, $tunnus, &$virhe, $trow);
			if($virhe[$i] != "") {
				$errori = 1;
			}
		}

		if ($errori != '') {
			echo "<font class='error'>".t("Jossain oli jokin virhe! Ei voitu paivittaa!")."</font>";
		}

		// Luodaan tietue
		if ($errori == '') {
			// Taulun ensimmäinen kenttä on aina yhtiö
			$query = "INSERT into asiakas values ('$kukarow[yhtio]'";
				for ($i=1; $i < mysql_num_fields($result); $i++) {
				$query .= ",'" . $t[$i] . "'";
			}
			$query .= ")";

			$result = mysql_query($query) or pupe_error($query);
			$uusiidee = mysql_insert_id();

			//	Tämä funktio tekee myös oikeustarkistukset!
			synkronoi($kukarow["yhtio"], "asiakas", $uusiidee, "", "");

			if (isset($tapahtumat) !== FALSE) {
				$query = "SELECT ytunnus FROM asiakas WHERE yhtio = '$kukarow[yhtio]' AND tunnus = '$uusiidee'";
				$result = mysql_query($query) or pupe_error($query);
				$ytrow = mysql_fetch_array($result);


				$query = "UPDATE kalenteri SET liitostunnus = '$uusiidee', asiakas = '$ytrow[ytunnus]' WHERE yhtio = '$kukarow[yhtio]' AND liitostunnus = '$id' ORDER BY tunnus;";
				$result = mysql_query($query) or pupe_error($query);
			}

			unset($tapahtumat);
			$tee = '';

		}
		else {
			$tee = 'edit';
		}
	}

	if ($tee == "edit") {

		echo "<form action = '$PHP_SELF' method = 'post' id='mainform'>";
		echo "<input type = 'hidden' name = 'tee' value ='write'>";
		echo "<input type = 'hidden' name = 'id' value ='$id'>";

		// Kokeillaan geneeristä
		$query = "	SELECT *
					FROM asiakas
					WHERE tunnus='$id' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or die ("Kysely ei onnistu $query");
		$trow = mysql_fetch_array($result);
		echo "<table>";

		for ($i=0; $i < mysql_num_fields($result) - 1; $i++) {
			$nimi = "t[$i]";

			require "inc/asiakasrivi.inc";

			if (mysql_field_name($result, $i) == "muutospvm") {
				$tyyppi = 0;
				$jatko = 0;
				$ulos = '';
			}

			if (mysql_field_name($result, $i) == "muuttaja") {
				$tyyppi = 0;
				$jatko = 0;
				$ulos = '';
			}

			if(mysql_field_name($result, $i) == 'laatija') {	//speciaali tapaukset
				$tyyppi = 2;
				$trow[$i] = $kukarow["kuka"];
			}
			if(mysql_field_name($result, $i) == 'luontiaika') {	//speciaali tapaukset
				$tyyppi = 2;
				$trow[$i] = date('Y-m-d H:i:s');
			}

			if 	(mysql_field_len($result,$i)>10) $size='35';
			elseif	(mysql_field_len($result,$i)<5)  $size='5';
			else	$size='10';

			if ($tyyppi > 0) {
				echo "<tr>";
			}

			if ($tyyppi > 0) {
		 		echo "<th align='left'>".t(mysql_field_name($result, $i))."</th>";
			}

			if ($jatko == 0) {
				echo $ulos;
			}
			else {
				$mita = 'text';
				if ($tyyppi != 1)
				{
					$mita='hidden';
					echo "<td class='back'>";
				}
				else
				{
					echo "<td>";
				}

				echo "<input type = '$mita' name = '$nimi'";

				if ($errori == '') {
					echo " value = '$trow[$i]' size='$size' maxlength='" . mysql_field_len($result,$i) ."'>";
				}
				else {
					echo " value = '$t[$i]' size='$size' maxlength='" . mysql_field_len($result,$i) ."'>";
				}

				if($tyyppi == 2) {
					echo "$trow[$i]";
				}

				echo "</td>";
			}

			if ($tyyppi > 0) {
				echo "<td class='back'><font class='error'>$virhe[$i]</font></td></tr>\n";
			}
		}
		echo "</table>";

		if (isset($tapahtumat) !== FALSE) {
			$chk = 'CHECKED';
		}

		echo "<table>";
		echo "<tr><td>";
		echo t("Siirrä kalenteritapahtumat ja asiakasmemot")." <input type = 'checkbox' name = 'tapahtumat' $chk>";
		echo "</td></tr>";
		echo "<tr><td class='back'>";
		echo "<input type = 'submit' value = '".t("Perusta")."'>";
		echo "</td></tr>";
		echo "</table>";
		echo "</form>";
	}

	if ($tee == '') {

		enable_ajax();
	    pupe_DataTables($pupe_DataTables, 7, 7, '', 'true');

        $query = "	SELECT
					asiakas.tunnus,
					concat(if(asiakas.nimi='', '**N/A**', asiakas.nimi), '<br>', asiakas.toim_nimi) nimi,
					concat(asiakas.nimitark, '<br>', asiakas.toim_nimitark) nimitark,
					concat(asiakas.postitp, '<br>', asiakas.toim_postitp) postitp,
					concat(asiakas.ytunnus) ytunnus,
					concat(asiakas.ovttunnus, '<br>', asiakas.toim_ovttunnus) ovttunnus,
					asiakas.asiakasnro
					FROM asiakas
					WHERE yhtio = '$kukarow[yhtio]'
					AND laji != 'P'
					ORDER by nimi";
		$result = mysql_query ($query) or pupe_error("Kysely ei onnistu $query");

		echo "<table class='display' id='$pupe_DataTables'>";
		echo "<thead>";
		echo "<tr>";
		echo "<th>".t("Nimi")."</th>";
		echo "<th>".t("Nimitark")."</th>";
		echo "<th>".t("Postitp")."</th>";
		echo "<th>".t("Ytunnus")."</th>";
		echo "<th>".t("Ovttunnus")."</th>";
		echo "<th>".t("Asiakasnro")."</th>";
		echo "<th>".t("Asiakastunnus")."</th>";
		echo "</tr>";
		echo "<tr>";
		echo "<td><input type='text' name='search_nimi'></td>";
		echo "<td><input type='text' name='search_nimitark'></td>";
		echo "<td><input type='text' name='search_postitp'></td>";
		echo "<td><input type='text' name='search_ytunnus'></td>";
		echo "<td><input type='text' name='search_ovttunnus'></td>";
		echo "<td><input type='text' name='search_asiakasnro'></td>";
		echo "<td><input type='text' name='search_tunnus'></td>";
		echo "</tr>";
		echo "</thead>";
		echo "<tbody>";

		while ($row = mysql_fetch_array($result)) {
			echo "<tr class='aktiivi'>";
			echo "<td><a href='$PHP_SELF?id=$row[tunnus]&tee=edit'>$row[nimi]</a></td>";
			echo "<td>$row[nimitark]</td>";
			echo "<td>$row[postitp]</td>";
			echo "<td>$row[ytunnus]</td>";
			echo "<td>$row[ovttunnus]</td>";
			echo "<td>$row[asiakasnro]</td>";
			echo "<td>$row[tunnus]</td>";
			echo "</tr>";
		}

		echo "</tbody>";
		echo "</table>";
	}

	require ("inc/footer.inc");

?>