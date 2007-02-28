<?php

	if (strtoupper($toim) == "ASIAKAS") {
		echo "<font class='head'>".t("Asiakkaan yhteyshenkilöt")."</font><hr>";
		$tyyppilisa = " tyyppi = 'A', ";
	}
	else {
		echo "<font class='head'>".t("Toimittajan yhteyshenkilöt")."</font><hr>";
		$tyyppilisa = " tyyppi = 'T', ";	
	}
	
	
	if ($oikeurow['paivitys'] != '1') { // Saako päivittää
		echo "<br><font class='error'>".t("Sun natsat ei riitä tähä hommaan poika")."!</font><br>";
		exit;
	}
	
	if ($tunnus == "" and $uusi == 1) {
		echo "	<form action='#' method='POST'>
				<input type='button' onclick='javascript:document.mainform.submit();' name='kikkeli' value='".t("Lisää uusi yhteyshenkilö")."'>
				</form>";
	}
	elseif ($tunnus == "") {
		echo "<font class='error'>".t("Tietue katosi")."!</font>";
	}
	else {

		// päivitetään toimittaja
		if ($tee == "update") {
			
			$query = "	update yhteyshenkilo set
						$tyyppilisa
						nimi 			= '$henkilonimi',
						titteli		 	= '$titteli',
						email 			= '$email',
						puh 			= '$puh',
						gsm 			= '$gsm',
						fax 			= '$fax',
						www 			= '$www',
						fakta 			= '$fakta'
						where yhtio = '$kukarow[yhtio]' 
						and tunnus  = '$henkilotunnus' 
						and liitostunnus='$tunnus'";
			$result = mysql_query($query) or pupe_error($query);
			$tee = "";
		}

		// poistetaan toimittaja
		if ($tee == "poista") {
			$query = "	delete from yhteyshenkilo 
						where yhtio		= '$kukarow[yhtio]' 
						and tunnus		= '$henkilotunnus' 
						and liitostunnus= '$tunnus'";
			$result = mysql_query($query) or pupe_error($query);
			$tee = "";
		}

		// lisätään toimittaja
		if ($tee == "insert") {
			$query = "	insert into yhteyshenkilo set
						yhtio			= '$kukarow[yhtio]',
						$tyyppilisa
						liitostunnus 	= '$tunnus',
						nimi 			= '$henkilonimi',
						titteli		 	= '$titteli',
						email 			= '$email',
						puh 			= '$puh',
						gsm 			= '$gsm',
						fax 			= '$fax',
						www 			= '$www',
						fakta 			= '$fakta'";
			$result = mysql_query($query) or pupe_error($query);
			$tee = "";
		}

		// uuden syöttö
		if ($tee == "uusi") {

			echo "<form action='yllapito.php?ojarj=$ojarj$ulisa' method='post'>";

			echo "<table>";

			echo "<tr><th>".t("nimi")."</th>			<td><input type='text' name='henkilonimi' 		value='$henkilonimi'></tr>";
			echo "<tr><th>".t("titteli")."</th>			<td><input type='text' name='titteli' 			value='$titteli'></tr>";
			echo "<tr><th>".t("email")."</th>			<td><input type='text' name='email' 			value='$email'></tr>";
			echo "<tr><th>".t("puh")."</th>				<td><input type='text' name='puh' 				value='$puh'></tr>";
			echo "<tr><th>".t("gsm")."</th>				<td><input type='text' name='gsm' 				value='$gsm'></tr>";
			echo "<tr><th>".t("fax")."</th>				<td><input type='text' name='fax' 				value='$fax'></tr>";
			echo "<tr><th>".t("www")."</th>				<td><input type='text' name='www' 				value='$www'></tr>";
			echo "<tr><th>".t("fakta")."</th>			<td><input type='text' name='fakta'			 	value='$fakta'></tr>";

			echo "</table><hr>";

			echo "<input type='hidden' name='tee' value='insert'>";
			echo "<input type='hidden' name='lopetus' value = '$lopetus'>";
			echo "<input type='hidden' name='toim' value='$aputoim'>";
			echo "<input type='hidden' name='tunnus' value='$tunnus'>";
			echo "<input type='submit' value='".t("Perusta yhteyshenkilö")."'></form>";
		}

		// selataan vanhoja
		if ($tee == "") {


			$query  = "select * from yhteyshenkilo where yhtio='$kukarow[yhtio]' and liitostunnus='$tunnus'";
			$result = mysql_query($query) or pupe_error($query);

			while ($trow = mysql_fetch_array($result)) {

				echo "<form action='yllapito.php?ojarj=$ojarj$ulisa' method='post'>";
				echo "<table>";
				
				echo "<tr><th>".t("nimi")."</th>			<td><input type='text' name='henkilonimi' 		value='$trow[nimi]'></tr>";
				echo "<tr><th>".t("titteli")."</th>			<td><input type='text' name='titteli'		 	value='$trow[titteli]'></tr>";
				echo "<tr><th>".t("email")."</th>			<td><input type='text' name='email'			 	value='$trow[email]'></tr>";
				echo "<tr><th>".t("puh")."</th>				<td><input type='text' name='puh'		 		value='$trow[puh]'></tr>";
				echo "<tr><th>".t("gsm")."</th>				<td><input type='text' name='gsm' 				value='$trow[gsm]'></tr>";
				echo "<tr><th>".t("fax")."</th>				<td><input type='text' name='fax'		 		value='$trow[fax]'></tr>";
				echo "<tr><th>".t("www")."</th>				<td><input type='text' name='www' 				value='$trow[www]'></tr>";
				echo "<tr><th>".t("fakta")."</th>			<td><input type='text' name='fakta'			 	value='$trow[fakta]'></tr>";

				echo "<tr><td class='back'>";
				echo "<input type='hidden' name='tee' value='update'>";
				echo "<input type='hidden' name='toim' value='$aputoim'>";
				echo "<input type='hidden' name='lopetus' value = '$lopetus'>";
				echo "<input type='hidden' name='tunnus' value='$tunnus'>";
				echo "<input type='hidden' name='henkilotunnus'  value='$trow[tunnus]'>";
				echo "<input type='submit' value='".t("Päivitä yhteyshenkilö")."'></td></form>";

				echo "<form action='yllapito.php?ojarj=$ojarj$ulisa' method='post'>";

				echo "<td class='back'>";
				echo "<input type='hidden' name='tee' value='poista'>";
				echo "<input type='hidden' name='toim' value='$aputoim'>";
				echo "<input type='hidden' name='lopetus' value = '$lopetus'>";
				echo "<input type='hidden' name='tunnus' value='$tunnus'>";
				echo "<input type='hidden' name='henkilotunnus'  value='$trow[tunnus]'>";
				echo "<input type='submit' value='".t("Poista yhteyshenkilö")."'></td></form>";
				echo "</tr>";

				echo "</table>";
				echo "<hr>";
			}

			echo "<br><form action='yllapito.php?ojarj=$ojarj$ulisa' method='post'>
			<input type='hidden' name='tee' value='uusi'>
			<input type='hidden' name='toim' value='$aputoim'>
			<input type='hidden' name='lopetus' value = '$lopetus'>
			<input type='hidden' name='tunnus' value='$tunnus'>
			<input type='submit' value='".t("Lisää uusi yhteyshenkilö")."'></form>";

		}

	}

?>