<?php

	require ("inc/parametrit.inc");

	echo "<font class='head'>Korjaa keskeytynyt vientilaskutus:</font><hr>";
	
	if ($id != 0) {				
		$query = "	select * 
					from lasku 
					where yhtio	 = '$kukarow[yhtio]' 
					and tila	 = 'L'  
					and vienti	!= '' 
					and tunnus	 = '$id'";
		$res = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($res) != 1) {
			echo "Laskua ei löydy!";
			exit;
		}
		$laskurow = mysql_fetch_array($res);
	

		// Löytyykö U-laskua?		
		if ($laskurow["laskunro"] != 0) {
			$query = "	select * 
						from lasku 
						where yhtio	 = '$kukarow[yhtio]' 
						and laskunro = '$laskurow[laskunro]'";
			$res = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($res) != 1) {
				echo "Laskunumerolla löytyy monta laskua, korjausta ei voida suorittaa!<br><br>";
				exit;
			}			
		}
		

		if ($tee == 'KORJAA') {
			$query = "	update lasku
						set alatila   = 'E',
						laskuttaja    = '',
						laskutettu	  = '0000-00-00 00:00:00',
						tapvm 		  = '0000-00-00',
						laskunro      = 0						 
						where yhtio	  = '$kukarow[yhtio]' 
						and tila	  = 'L'  
						and vienti	 != '' 
						and tunnus	  = '$laskurow[tunnus]'";
			$korjres = mysql_query($query) or pupe_error($query);			
		}
												
		
		// Laskun tiedot		
		echo "Laskun tiedot";
		echo "<table>";
		echo "<tr><th>Tunnus:</th>			<td>$laskurow[tunnus]</td></tr>";
		echo "<tr><th>Laskunro:</th>		<td>$laskurow[laskunro]</td></tr>";
		echo "<tr><th>Tila:</th>			<td>$laskurow[tila]</td></tr>";
		echo "<tr><th>Alatila:</th>			<td>$laskurow[alatila]</td></tr>";
		echo "<tr><th>Nimi:</th>			<td>$laskurow[nimi]</td></tr>";
		echo "<tr><th>Vienti:</th>			<td>$laskurow[vienti]</td></tr>";
		echo "<tr><th>Laskutettu:</th>		<td>$laskurow[laskuttaja]</td></tr>";
		echo "<tr><th>Laskutettuaika:</th>	<td>$laskurow[laskutettu]</td></tr>";		
		echo "</table><br><br>";
		
		
		// Näytetään tilausrivit		
		$query = "	select * 
					from tilausrivi 
					where yhtio = '$kukarow[yhtio]' 
					and otunnus = '$laskurow[tunnus]'";
		$res = mysql_query($query) or pupe_error($query);
				
		echo "Tilausrivit";
		echo "<table>";
		while ($rivirow = mysql_fetch_array($res)) {
			echo "<tr><th>Tuoteno:</th>			<td>$rivirow[tuoteno]</td></tr>";
			echo "<tr><th>Varattu:</th>			<td>$rivirow[varattu]</td></tr>";
			echo "<tr><th>Kpl:</th>				<td>$rivirow[kpl]</td></tr>";
			echo "<tr><th>Laskutettu:</th>		<td>$rivirow[laskutettu]</td></tr>";
			echo "<tr><th>Laskutettuaika:</th>	<td>$rivirow[laskutettuaika]</td></tr>";
			echo "<tr><th>Uusiotunnus:</th>		<td>$rivirow[uusiotunnus]</td></tr>";
			echo "<tr><th>Otunnus:</th>			<td>$rivirow[otunnus]</td></tr>";
			echo "<tr><td colspan='2' class='back'><br></td></tr>";		
		
		
			if ($tee == 'KORJAA') {
				$query = "	update tilausrivi
							set varattu 	  = kpl,
							kpl			  	  = 0
							where yhtio	  	  = '$kukarow[yhtio]'
							and kpl 		 != 0 
							and tunnus	  	  = '$rivirow[tunnus]'";
				$korjres = mysql_query($query) or pupe_error($query);
							
				$query = "	update tilausrivi
							set laskutettu    = '',
							laskutettuaika	  = '0000-00-00',							
							uusiotunnus	  	  = 0							
							where yhtio	  	  = '$kukarow[yhtio]' 
							and tunnus	  	  = '$rivirow[tunnus]'";
				$korjres = mysql_query($query) or pupe_error($query);
							
			}						
		}
		echo "</table><br><br>";
		
		
		
		$query = "	select *, if (kpl != 0, kpl, varattu) pilu 
					from tilausrivi 
					where yhtio='$kukarow[yhtio]' 
					and otunnus='$laskurow[tunnus]'";
		$res = mysql_query($query) or pupe_error($query);
		
		echo "Tapahtumat";
		echo "<table>";
		while ($rivirow = mysql_fetch_array($res)) {
			// Löytyykö tapahtumia
			$query = "	select * 
						from tapahtuma 
						where yhtio	 = '$kukarow[yhtio]' 
						and laji 	 = 'laskutus'
						and tuoteno	 = '$rivirow[tuoteno]'
						and laatija  = '$laskurow[laskuttaja]' 
						and laadittu = '$laskurow[laskutettu]'
						and kpl 	 = $rivirow[pilu] * -1";
			$tapares = mysql_query($query) or pupe_error($query);
			$taparow = mysql_fetch_array($tapares);
			
			echo "<tr><th>Tuoteno:</th><td>$taparow[tuoteno]</td></tr>";
			echo "<tr><th>Kpl:</th><td>$taparow[kpl]</td></tr>";
			echo "<tr><th>Laatija:</th><td>$taparow[laatija]</td></tr>";
			echo "<tr><th>Laadittu:</th><td>$taparow[laadittu]</td></tr>";
			echo "<tr><th>Selite:</th><td>$taparow[selite]</td></tr>";
			echo "<tr><td colspan='2' class='back'><br></td></tr>";
			
			if ($tee == 'KORJAA' and mysql_num_rows($tapares) > 0) {
				$query = "	delete from tapahtuma							
							where yhtio	  = '$kukarow[yhtio]' 
							and tunnus	  = '$taparow[tunnus]'";
				$korjres = mysql_query($query) or pupe_error($query);
							
				//laitetaan takas saldoille
				$query = "	update tuotepaikat
							set saldo = saldo - $taparow[kpl]										
							where yhtio	  	  = '$kukarow[yhtio]' 
							and tuoteno	  	  = '$rivirow[tuoteno]'
							and hyllyalue 	  = '$rivirow[hyllyalue]'
							and hyllynro	  = '$rivirow[hyllynro]'
							and hyllyvali	  = '$rivirow[hyllyvali]'
							and hyllytaso	  = '$rivirow[hyllytaso]'";
				$korjres = mysql_query($query) or pupe_error($query);			
			
				echo "<br><br><br>";
			}						
		}
		echo "</table><br><br>";
	
		
		echo "<a href='$PHP_SELF?tee=KORJAA&id=$id'>".t("Näyttää hyvältä ja haluan korjata")."</a>";
	
	}
	
	if ($id == '') {
		echo "<br><table>";
		echo "<tr><th>".t("Tunnus")."</th><td class='back'></td>
				<td><form action = '$PHP_SELF' method = 'post'>
				<input type='text' size='30' name='id'>
				</td></tr>";
		echo "</table>";
		echo "<br><input type='submit' value='".t("Jatka")."'></form>";
	}
	
	
	require("inc/footer.inc");
?>
