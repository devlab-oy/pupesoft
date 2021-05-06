<?php

	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Peru lasku").":<hr></font>";

	// sallitaan vain numerot 0-9
	$tunnus = ereg_replace("[^0-9]", "", $tunnus);
	

	if ($tunnus != "" and $tee == "vaihda") {

		$tila_query  = "	SELECT *
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]'
							AND tila in ('L','N','A','V','1')
							AND tunnus = '$tunnus'";
		$tila_result = mysql_query($tila_query) or pupe_error($tila_query);
		
		if (mysql_num_rows ( $tila_result ) == 1) {
		$tila_row = mysql_fetch_assoc ( $tila_result );
		$laskunnumero = $tila_row ["laskunro"];
			//Hae lasku taulukosta laskun tilaustunnus
			$tila2_query = "	SELECT *
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]'
							AND tila='U'
							AND laskunro = '$laskunnumero'";
		$tila2_result = mysql_query ( $tila2_query ) or pupe_error ( $tila2_query );
		$tila2_row = mysql_fetch_assoc ( $tila2_result );
		$laskuntunnus = $tila2_row ["tunnus"];
		
		// lock tables
		$query = "LOCK TABLES lasku WRITE, tilausrivi WRITE, rahtikirjat WRITE, tuote WRITE, tuotepaikat  WRITE, tapahtuma WRITE,
			tiliointi WRITE, laskun_lisatiedot WRITE, sarjanumeroseuranta WRITE, avainsana as avainsana_kieli READ";
		$locre = mysql_query ( $query ) or pupe_error ( $query );
		
		
		
		
		if ($tila == "999") {
			$greprivi="grep -lF 'InvoiceNumber>".$laskunnumero."'  /var/www/html/pupesoft/dataout/*";
			$poistaxml = system("grep -lF 'InvoiceNumber>$laskunnumero'  /var/www/html/pupesoft/dataout/*", $retval);
			if (unlink($poistaxml)){
				echo "<br>Lasku-xml $laskunnumero tuhottu.<br>";
			}
			// Palauta saldot tuotepaikat taulukossa
			$query = "	UPDATE tuotepaikat, tilausrivi
							SET tuotepaikat.saldo = (tuotepaikat.saldo + tilausrivi.kpl)
							where tuotepaikat.yhtio = '$kukarow[yhtio]'
							and tuotepaikat.tuoteno = tilausrivi.tuoteno 
							and tuotepaikat.hyllyalue='Z'
							and tuotepaikat.hyllynro='99'
							and tilausrivi.otunnus = '$tunnus'";
			$tila_result = mysql_query ( $query ) or pupe_error ( $query );
			echo "<br>Saldot palautettu tuotepaikat taulussa.<br>";			
			
			// Hävitä rivit tapahtuma taulukossa
 			$query = "	DELETE tapahtuma.* from tapahtuma, tilausrivi 
							WHERE tapahtuma.rivitunnus = tilausrivi.tunnus
							and otunnus = '$tunnus'";
			$tila_result = mysql_query ( $query ) or pupe_error ( $query );
			echo "<br>Tilausrivit hävitetty tapahtuma taulukosta.<br>";		
			
			
			// Palauta tilausrivi taulukossa
			
			$query = "	UPDATE tilausrivi set
							varattu = kpl,
							kpl =0,
							rivihinta_valuutassa=0, 
							rivihinta=0, 
							kate=0, 
							laskutettu='', 
							laskutettuaika='', 
							uusiotunnus=''
							where yhtio = '$kukarow[yhtio]'
							and otunnus = '$tunnus'";
			$tila_result = mysql_query ( $query ) or pupe_error ( $query );
			
			// Palauta tilaus lasku taulukossa
			$query = "	update lasku
							set erpcm='',
							summa_valuutassa=0, 
							summa=0, 
							kate=0,
							arvo=0,
							arvo_valuutassa=0,
							laskutettu='', 
							viite='',
							laskunro=0,
							alatila='D'
							where yhtio = '$kukarow[yhtio]'
							and tunnus = '$tunnus'";
			$tila_result = mysql_query ( $query ) or pupe_error ( $query );
			
			// Tuhoa laskurivi lasku taulukossa
			$query = "	delete from lasku
							where yhtio = '$kukarow[yhtio]'
							and laskunro = '$laskunnumero'
							and tila='U'";
			$tila_result = mysql_query ( $query ) or pupe_error ( $query );
			
			// Tuhoa laskutiedot tiliointi taulukossa
			$query = "	delete from tiliointi
							where yhtio = '$kukarow[yhtio]'
							and ltunnus = '$laskuntunnus'";
			$tila_result = mysql_query ( $query ) or pupe_error ( $query );
			
			// Tuhoa lisätiedot laskun_lisatiedot taulukossa
			$query = "	delete from laskun_lisatiedot
							where yhtio = '$kukarow[yhtio]'
							and otunnus = '$laskuntunnus'";
			$tila_result = mysql_query ( $query ) or pupe_error ( $query );
		
		}

			


			// poistetaan lukot
			$query = "UNLOCK TABLES";
			$locre = mysql_query($query) or pupe_error($query);
		}

		$tee = "valitse";
	}

	if ($tunnus != "" and $tee == "valitse") {

		$tila_query  = "	SELECT *
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]'
							AND tila in ('L','N','A','V','1')
							AND alatila in ('V','X','')
							AND tunnus = '$tunnus'";
		$tila_result = mysql_query($tila_query) or pupe_error($tila_query);

		if (mysql_num_rows($tila_result) == 1) {

			$tila_row = mysql_fetch_array($tila_result);

			// vain laskutetuille myyntitilauksille voi tehd� jotain
			if (	($tila_row["tila"] == "L" and ($tila_row["alatila"] == "V" or $tila_row["alatila"] == "X"))) {

				echo "<form method='post' action='$PHP_SELF'>";
				echo "<input type='hidden' name='parametrit' value='$parametrit'>";
				echo "<input type='hidden' name='tee' value='vaihda'>";
				echo "<input type='hidden' name='tunnus' value='$tila_row[tunnus]'>";

				echo "<table><tr>";
				echo "<th>".t("Vaihda tilauksen tila").": </th>";
				echo "<td><select name='tila'>";
				echo "<option value = ''>".t("Valitse uusi tila")."</option>";
				echo "<option value = '999'>".t("Peru lasku")."</option>";

				if (($tila_row["tila"] == "L" or $tila_row["tila"] == "V") and in_array($tila_row["alatila"], array('A','B','C','D'))) {
					echo "<option value = '2'>".t("Tilaus tulostusjonossa")."</option>";
				}
				if (in_array($tila_row["alatila"], array('B','C','D'))) {
					echo "<option value = '3'>".t("L�hete tulostettu")."</option>";
				}
				if (in_array($tila_row["alatila"], array('B','D'))) {
					echo "<option value = '4'>".t("Tilaus ker�tty")."</option>";
				}
				if (in_array($tila_row["alatila"], array('D'))) {
					echo "<option value = '5'>".t("Rahtikirjatiedot sy�tetty")."</option>";
				}
				echo "</select></td>";
				echo "<td class='back'><input type='submit' value='".t("Vaihda tila")."'></td>";
				echo "</form>";

				echo "</tr>";
				echo "</table><br>";
			}

			require ("raportit/naytatilaus.inc");

			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='parametrit' value='$parametrit'>";
			echo "<td class='back'><input type='submit' value='".t("Peruuta")."'></td>";
			echo "</form>";

		}
		else {
			echo "<font class='error'>".t("Tilausta ei löydy")."!</font>";
			$tee = "";
		}

	}

	if ($tee == "") {
		echo "<form method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='tee' value='valitse'>";
		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Anna tilausnumero").":</th>";
		echo "<td><input type='text' name='tunnus'></td>";
		echo "<td class='back'><input type='submit' value='".t("Hae")."'></td>";
		echo "</tr>";
		echo "</table>";
		echo "</form>";
	}

	require ("../inc/footer.inc");

?>
