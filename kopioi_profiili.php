<?php
	require ("inc/parametrit.inc");
	
	echo "<font class='head'>".t("Kopioi käyttäjäprofiileja").":</font><hr>";

		if (isset($fromkuka)) {
			$fromkuka = split('##',$fromkuka);
			
			$fromyhtio = $fromkuka[1];
			$fromkuka  = $fromkuka[0];			
		}
		
		
		
		if ($copyready!='') // ollaan painettu kopio submittia
		{
			echo "<font class='message'>".t("Kopioitiin käyttäjäprofiili")." $fromkuka ($fromyhtio) --> Yhtiölle $tokuka</font><br><br>";

			//haetaan profiili kaikki tiedot
			$query = "SELECT * FROM oikeu where kuka='$fromkuka' and profiili='$fromkuka' and yhtio='$fromyhtio'";
			$kukar = mysql_query($query) or pupe_error($query);

			//poistetaan se uudelya yhtiöltä jos se on olemassa
			$query = "delete from oikeu where kuka='$fromkuka' and profiili='$fromkuka' and yhtio='$tokuka'";
			$delre = mysql_query($query) or pupe_error($query);

			while ($row = mysql_fetch_array($kukar)) {
				$query = "insert into oikeu values ('$fromkuka','$row[sovellus]','$row[nimi]','$row[alanimi]','$row[paivitys]','','$row[nimitys]','$row[jarjestys]','$row[jarjestys2]','$fromkuka','$tokuka','$row[hidden]',0)";
				$upres = mysql_query($query) or pupe_error($query);						
			}

			//päivitetään myös käyttäjien tiedot joilla on tämä profiili
			$query = "	SELECT *
						FROM kuka
						WHERE yhtio='$tokuka' and profiilit!=''";
			$kres = mysql_query($query) or pupe_error($query);
			while ($krow = mysql_fetch_array($kres)) {
				$profiilit = explode(',', $krow["profiilit"]);
				if (count($profiilit) > 0) {											
					//käydään läpi käyttäjän kaikki profiilit
					$triggeri = "";
					foreach($profiilit as $prof) {
						//jos tämä kyseinen profiili on ollut käyttäjällä aikaisemmin, niin joudumme päivittämään oikeudet		
						if (strtoupper($prof) == strtoupper($fromkuka)) {
							$triggeri = "HAPPY";
						}							
					}
					
					if ($triggeri == "HAPPY") {				
						//poistetaan käyttäjän vanhat 
						$query = "	DELETE FROM oikeu
									WHERE yhtio='$tokuka' and kuka='$krow[kuka]' and lukittu=''";
						$pres = mysql_query($query) or pupe_error($query);	
																
						//käydään uudestaan profiili läpi
						foreach($profiilit as $prof) {
							$query = "	SELECT *
										FROM oikeu
										WHERE yhtio='$tokuka' and kuka='$prof' and profiili='$prof'";
							$pres = mysql_query($query) or pupe_error($query);	
												
							while ($trow = mysql_fetch_array($pres)) {
								//joudumme tarkistamaan ettei tätä oikeutta ole jo tällä käyttäjällä. 
								//voi olla esim jos se on lukittuna annettu
								$query = "	SELECT yhtio
											FROM oikeu
											WHERE kuka		= '$krow[kuka]'
											and sovellus	= '$trow[sovellus]'
											and nimi		= '$trow[nimi]'
											and alanimi 	= '$trow[alanimi]'
											and paivitys	= '$trow[paivitys]'
											and nimitys		= '$trow[nimitys]'
											and jarjestys 	= '$trow[jarjestys]'
											and jarjestys2	= '$trow[jarjestys2]'
											and yhtio		= '$tokuka'";			
								$tarkesult = mysql_query($query) or pupe_error($query);
								
								if (mysql_num_rows($tarkesult) == 0) {
									$query = "	INSERT into oikeu 
												SET
												kuka		= '$krow[kuka]', 
												sovellus	= '$trow[sovellus]', 
												nimi		= '$trow[nimi]', 
												alanimi 	= '$trow[alanimi]', 
												paivitys	= '$trow[paivitys]',
												nimitys		= '$trow[nimitys]', 
												jarjestys 	= '$trow[jarjestys]',
												jarjestys2	= '$trow[jarjestys2]',
												yhtio		= '$tokuka'";			
									$rresult = mysql_query($query) or pupe_error($query);
								}																							
							}												
						}
						echo "<font class='message'>Päivitettiin käyttäjän $krow[kuka] profiili $prof</font><br>";
					}
				}			
			}

			$fromkuka='';
			$tokuka='';
			$fromyhtio='';
			$toyhtio='';
		}
		
		

		echo "<br><form action='$PHP_SELF' method='post'>";
		echo "<input type='hidden' name='tila' value='copy'>";

		echo "<font class='message'>".t("Kopioitava profiili").":</font>";

		$query	= "	SELECT distinct yhtio, nimi
					from yhtio
					where konserni = '$yhtiorow[konserni]'";
		$result = mysql_query($query) or pupe_error($query);
		
		$sovyhtiot = "";
		
		while ($prow = mysql_fetch_array($result)) {
			$sovyhtiot .= "'$prow[yhtio]',";
		}
		
		$sovyhtiot = substr($sovyhtiot,0,-1);

		

		// tehdään käyttäjälistaukset
		$query = "SELECT distinct kuka, profiili, yhtio FROM oikeu WHERE kuka=profiili and profiili!='' and yhtio in ($sovyhtiot)";
		$kukar = mysql_query($query) or pupe_error($query);

		echo "<table><tr><th align='left'>".t("Profiili").":</th><td>
		<select name='fromkuka' onchange='submit()'>
		<option value=''>".t("Valitse profiili")."</option>";

		while ($kurow=mysql_fetch_array($kukar)) {
			if ($fromkuka==$kurow["profiili"] and $fromyhtio == $kurow["yhtio"]) $select='selected';
			else $select='';

			echo "<option $select value='$kurow[profiili]##$kurow[yhtio]'>$kurow[profiili] ($kurow[yhtio])</option>";
		}

		echo "</select></td></tr>";
		echo "</table>";

		echo "<br><br><font class='message'>".t("Mille yhtiölle kopioidaan").":</font>";

		// tehdään käyttäjälistaukset

		$query = "SELECT distinct yhtio, nimi FROM yhtio WHERE yhtio in ($sovyhtiot) and yhtio!='$fromyhtio'";
		$kukar = mysql_query($query) or pupe_error($query);

		echo "<table><tr><th align='left'>".t("Yhtiö").":</th><td>
		<select name='tokuka' onchange='submit()'>
		<option value=''>".t("Valitse yhtiö")."</option>";

		while ($kurow=mysql_fetch_array($kukar))
		{
			if ($tokuka==$kurow["yhtio"]) {
				$select = 'selected';
				$tonimi = $kurow["nimi"]; 
			}
			else $select='';

			echo "<option $select value='$kurow[yhtio]'>$kurow[nimi] ($kurow[yhtio])</option>";
		}

		echo "</select></td></tr>";
		echo "</table>";

		if (($tokuka!='') and ($fromkuka!=''))
		{
			echo "<br><br>";
			echo "<input type='submit' name='copyready' value='".t("Kopioi käyttöprofiili")." $fromkuka --> Yhtiölle $tonimi'>";
		}

		echo "</form>";

	require("inc/footer.inc");
?>
