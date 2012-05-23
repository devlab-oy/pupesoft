<?php
	require ("../inc/parametrit.inc");
	
	echo "<font class='head'>".t("Kuittaamattomat lomat")."</font><hr>";
	
	if($tee == "kuittaa") {       //lisataan tapahtuma kalenteriin	
		$query = "	UPDATE kalenteri
					SET kuittaus = '$kukarow[nimi]'
					WHERE tunnus='$tunnus'
					and yhtio='$yhtio'";
		$result = mysql_query($query) or pupe_error($query);

		echo t("Loma kuitattu hyv�ksytyksi")."!<br><br>";				
		$tee = "";
	}
	
	if($tee == "ei_kuittaa") {       //lisataan tapahtuma kalenteriin	
		$query = "	UPDATE kalenteri
					SET kuittaus = ''
					WHERE tunnus='$tunnus'
					and yhtio='$yhtio'";
		$result = mysql_query($query) or pupe_error($query);

		echo t("Loman hyv�ksynt� peruttu")."!<br><br>";				
		$tee = "";
	}
	
	if ($tee == "") {
		
		echo "<table>";
		echo "<tr>";
		echo "<td>".t("Valitse osasto").":</td>";
		
		echo "<form method='POST'>";
		echo "<td><select name='osasto' onchange='submit()'><option value=''>".t("Ei osastoa")."</option>";

		$vares = t_avainsana("HENKILO_OSASTO");

		while ($varow = mysql_fetch_array($vares)) {
			$sel='';
			if ($varow['selite']==$osasto) $sel = 'selected';
			echo "<option value='$varow[selite]' $sel>$varow[selite] - $varow[selitetark]</option>";
		}

		echo "</select></td></tr>";
	
		if ($kuitatut != '')  {
			$chk1 = "CHECKED";
			$lisa1 = "";
		}
		else {
			$chk1 = "";
			$lisa1 = " and kalenteri.kuittaus = '' ";
		}
	
		if ($konserni != '')  {
			$chk2 = "CHECKED";
			
			$query = "SELECT distinct yhtio FROM yhtio WHERE (konserni = '$yhtiorow[konserni]' and konserni != '') or (yhtio = '$yhtiorow[yhtio]')";			
			$result = mysql_query($query) or pupe_error($query);
			$konsernit = "";
			
			while ($row = mysql_fetch_array($result)) {	
				$konsernit .= " '".$row["yhtio"]."' ,";
			}		
			$lisa2 = " kalenteri.yhtio in (".substr($konsernit, 0, -1).") ";						
		}
		else {
			$chk2 = "";
			
			$lisa2 = " kalenteri.yhtio='$kukarow[yhtio]' ";			
		}
	
	
		echo "<tr><td>N�yt� my�s kuitatut lomat:</td><td><input type='checkbox' $chk1 name='kuitatut' onclick='submit()'></td>";
		echo "<tr><td>N�yt� konserniyritysten lomat:</td><td><input type='checkbox' $chk2 name='konserni' onclick='submit()'></td>";
	
		echo "</tr></form></table><br>"; 
	
		
		if ($osasto != '') {
			//* listataan muistutukset *///
			$query = "	SELECT kalenteri.tunnus, left(pvmalku,10) pvmalku, right(pvmalku,8) aikaalku, 
						left(pvmloppu,10) pvmloppu, right(pvmloppu,8) aikaloppu, kuka.nimi, kalenteri.tapa, kentta01, kuka.osasto, kuittaus, kalenteri.yhtio,
						datediff(pvmalku,now()) ero
						FROM kalenteri, kuka
						where $lisa2
						and kuka.yhtio=kalenteri.yhtio
						and kalenteri.tyyppi = 'kalenteri'
						and kalenteri.tapa in ('Palkaton vapaa','Sairasloma','Kes�loma','Talviloma','Ylity�vapaa') 
						and kalenteri.kuka=kuka.kuka
						$lisa1
						and kuka.osasto='$osasto' 
						ORDER BY kalenteri.kuka, kalenteri.tapa, kalenteri.pvmalku";
			$result = mysql_query($query) or pupe_error($query);
						
			if (mysql_num_rows($result) > 0) {
				echo "<table>";		
				
				echo "<tr>
						<th>".("Tyyppi")."</th><th>".("Nimi")."</th>
						<th>".("Osasto")."</th><th>".("Pvmalku")."</th>
						<th>".("Aikaalku")."</th><th>".("Pvmloppu")."</th>
						<th>".("Aikaloppu")."</th><th>".("Kommentti")."</th>";
				
				if ($kuitatut != '')  {		
					echo "<th>".t("Hyv�ksyj�")."</th>";
				}
				if ($konserni != '') {
					echo "<th>".t("Yhti�")."</th>";
				}
						
				echo "</tr>";
				
				while ($row = mysql_fetch_array($result)) {			
					echo "<tr>
							<td>$row[tapa]</td><td>$row[nimi]</td>
							<td>$row[osasto]</td><td>$row[pvmalku]</td>
							<td>$row[aikaalku]</td><td>$row[pvmloppu]</td>
							<td>$row[aikaloppu]</td><td>$row[kentta01]</td>";
					
					if ($kuitatut != '')  {		
						echo "<td>$row[kuittaus]</td>";
					}
					if ($konserni != '') {
						echo "<td>$row[yhtio]</td>";
					}
					 
					if ($row["kuittaus"] == "") {
						echo "<form method='POST'>";
						echo "<input type='hidden' name='osasto' value='$osasto'>";
						echo "<input type='hidden' name='konserni' value='$konserni'>";
						echo "<input type='hidden' name='kuitatut' value='$kuitatut'>";						
						echo "<input type='hidden' name='tunnus' value='$row[tunnus]'>";
						echo "<input type='hidden' name='yhtio' value='$row[yhtio]'>";
						echo "<input type='hidden' name='tee' value='kuittaa'>";					
						echo "<td><input type='submit' value='".t("Hyv�ksy")."'></td>";
						echo "</form>";
					}
					
					if ($row["kuittaus"] != "" and $row["ero"] >= 0) {
						echo "<form method='POST'>";
						echo "<input type='hidden' name='osasto' value='$osasto'>";
						echo "<input type='hidden' name='konserni' value='$konserni'>";
						echo "<input type='hidden' name='kuitatut' value='$kuitatut'>";						
						echo "<input type='hidden' name='tunnus' value='$row[tunnus]'>";
						echo "<input type='hidden' name='yhtio' value='$row[yhtio]'>";
						echo "<input type='hidden' name='tee' value='ei_kuittaa'>";					
						echo "<td><input type='submit' value='".t("Peru hyv�ksynt�")."'></td>";
						echo "</form>";
					}
					
					
					echo "</tr>";
				}
				
				echo "</table>";
			}
			else {
				echo "<font class='message'>".t("Yht��n kuitattavaa lomaa ei l�ydy")."!</font>";
			}
		}
	}

	require ("../inc/footer.inc");

?>