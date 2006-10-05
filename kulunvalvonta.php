<?php
	require ("inc/parametrit.inc");

	echo "<font class='head'>".t("Kulunvalvonta")."</font><hr>";
	
	if ($tee == "viivakoodi") {
		// viivakoodista tulee kukarow tunnuksen md5 summa		
		$query  = "select * from kuka where md5(tunnus)='$viivakoodi'";
		$result = mysql_query($query);
		
		if (mysql_num_rows($result) != 1) {
			echo "<font class='message'>".t("Virhe! K‰ytt‰j‰‰ ei lˆytynyt!")."</font>";
			$tee = "";
		}
		else {
			$rivi = mysql_fetch_array($result);

			// haetaan k‰ytt‰j‰n vika kirjaus
			$query  = "select * from kulunvalvonta where kukatunnus='$rivi[tunnus]' order by aika desc limit 1";		
			$result = mysql_query($query);
			$kulu   = mysql_fetch_array($result);

			// tehd‰‰n selkokielinen suunta
			if ($kulu["suunta"] == "I") $suunta = t("Sis‰ll‰");
			else $suunta = t("Ulkona");

			// n‰ytet‰‰n k‰ytt‰j‰n tietoja
			echo "<table>";
			echo "<tr><th>".t("Nimi")."</th><td>$rivi[nimi]</td></tr>";
			echo "<tr><th>".t("Tila")."</th><td>$suunta</td></tr>";
			echo "<tr><th>".t("Kirjattu")."</th><td>$kulu[aika]</td></tr>";
			echo "<tr><th>".t("Aika nyt")."</th><td>".date("Y-m-d H:i:s")."</td></tr>";
			echo "</table>";

			// tehd‰‰n k‰yttˆliittym‰napit
			echo "<br><font class='head'>".t("Valitse kirjaus")."</font><hr>";

			echo "<form name='napit' action='$PHP_SELF' method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='napit'>";
			echo "<input type='hidden' name='kukatunnus' value='$rivi[tunnus]'>";

			// jos ollaan viimeks kirjattu ulos, niin n‰ytet‰‰n vaan sis‰‰n nappeja
			if ($kulu["suunta"] == "O") {
				echo "<table>";
				echo "<tr><td width='200' class='back' valign='top'>";
				echo "<input type='submit' accesskey='1' name='normin' value='".t("Sis‰‰n")."' style='font-size: 25px;'><br>";
				echo "</td><td class='back'>";
				echo "<input type='submit' name='matkain' value='".t("Matka Sis‰‰n")."'><br>";
				echo "<input type='submit' name='sickin' value='".t("Sairasloma Sis‰‰n")."'><br>";
				echo "</td></tr>";
				echo "</table>";
				
				$formi  = "napit";
				$kentta = "normin";
			}

			// jos ollaan viimeks kirjattu sis‰‰n, niin n‰ytet‰‰n vaan ulos nappeja
			if ($kulu["suunta"] == "I") {
				echo "<table>";
				echo "<tr><td width='200' class='back' valign='top'>";
				echo "<input type='submit' accesskey='1' name='normout' value='".t("Ulos")."' style='font-size: 25px;'><br>";
				echo "</td><td class='back'>";
				echo "<input type='submit' name='matkaout' value='".t("Matka Ulos")."'><br>";
				echo "<input type='submit' name='sickout' value='".t("Sairasloma Ulos")."'><br>";
				echo "<input type='submit' name='lomaout' value='".t("Loma Ulos")."'><br>";
				echo "</td></tr>";
				echo "</table>";

				$formi  = "napit";
				$kentta = "normout";
			}
						
			echo "<br><br><br>";	
			echo "<input type='submit' name='peruuta' value='".t("Peruuta kirjaus")."'>";
			echo "</form>";
		}
	}

	if ($tee == "napit") {

		if ($peruuta == "") {
			// haetaan kukarow
			$query  = "select * from kuka where tunnus='$kukatunnus'";
			$result = mysql_query($query);
			
			if (mysql_num_rows($result) != 1) {
				die ("holy fuck! you went missing!");
			}
			
			// fetchataan rivi
			$rivi = mysql_fetch_array($result);
		
			// jos ollaan klikattu jotain kolmesta IN nappulasta suunta I muuten O
			if ($normin != "" or $matkain != "" or $sickin != "") $suunta = "I";
			else $suunta = "O";

			// katotaan mik‰ on kirjauksen tyyppi
			$tyyppi = "";
			if ($lomain  != "" or $lomaout  != "") $tyyppi = "L"; // loma
			if ($sickin  != "" or $sickout  != "") $tyyppi = "S"; // sairaus
			if ($matkain != "" or $matkaout != "") $tyyppi = "M"; // matka

			// lis‰t‰‰n tapahtuma kantaan
			$query  = "insert into kulunvalvonta (yhtio, kukatunnus, aika, suunta, tyyppi) values ('$rivi[yhtio]','$kukatunnus',now(),'$suunta','$tyyppi')";
			$result = mysql_query($query);
			
			// kirjotetaan v‰h‰ feedb‰kki‰ k‰ytt‰j‰lle ruudulle
			echo "<br><font class='message'>".t("Tapahtuma kirjattu!")."</font><br><br>";

			echo "<table>";
			echo "<tr><th>".t("K‰ytt‰j‰")."</th><td>$rivi[nimi]</td></tr>";
			echo "<tr><th>".t("Aika")."</th><td>".date("Y-m-d H:i:s")."</td></tr>";
			echo "<tr><th>".t("Tyyppi")."</th><td>$normin $normout $sickin $sickout $matkain $matkaout $lomaout</td></tr>";
			echo "</table>";
		}
			
		// takasin perusn‰kym‰‰n
		$tee = "";
	}

	if ($tee == "") {
		echo "<br>";

		echo "<font class='head'>".t("Laita kortti lukijaan")."</font><br>";

		echo "<br>";

		echo "<form name='lukija' action='$PHP_SELF' method='post' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='viivakoodi'>";
		echo "<input size='50' type='password' name='viivakoodi' value=''>";
		echo "</form>";		

		// kursorinohjausta
		$formi  = "lukija";
		$kentta = "viivakoodi";
	}
	
	require ("inc/footer.inc");
?>