<?php
	require ("../inc/parametrit.inc");
	
	echo "<font class='head'>".t("Lomaseuranta")."</font><hr>";
	

		
		$query = "SELECT distinct yhtio FROM yhtio WHERE (konserni = '$yhtiorow[konserni]' and konserni != '') or (yhtio = '$yhtiorow[yhtio]')";			
	$result = mysql_query($query) or pupe_error($query);
	$konsernit = "";
	
	while ($row = mysql_fetch_array($result)) {	
		$konsernit .= " '".$row["yhtio"]."' ,";
	}		
	$lisa2 = " yhtio in (".substr($konsernit, 0, -1).") ";
	
	echo "<table>";
	echo "<tr>";
	echo "<td>".t("Valitse käyttäjä").":</td>";
	
	
	echo "<form action='$PHP_SELF' method='POST'>";
	echo "<td><select name='kuka' onchange='submit()'><option value=''>".t("Valitse käyttäjä")."</option>";

	$query  = "SELECT distinct kuka, nimi FROM kuka WHERE $lisa2";
	$vares = mysql_query($query) or pupe_error($query);

	while ($varow = mysql_fetch_array($vares)) {
		$sel='';
		if ($varow['kuka']==$kuka) $sel = 'selected';
		echo "<option value='$varow[kuka]' $sel>$varow[nimi]</option>";
	}

	echo "</select></td></tr>";
	echo "</form></table><br><br>"; 

	if ($kuka != '') {
		//* listataan muistutukset *///
		$query = "	SELECT kalenteri.tunnus, left(pvmalku,10) pvmalku, right(pvmalku,8) aikaalku, 
					left(pvmloppu,10) pvmloppu, right(pvmloppu,8) aikaloppu, kuka.nimi, kalenteri.tapa, kentta01, kuka.osasto, kuittaus, kalenteri.yhtio,
					datediff(pvmalku,now()) ero
					FROM kalenteri, kuka
					where kalenteri.$lisa2
					and kuka.yhtio=kalenteri.yhtio
					and kalenteri.tyyppi = 'kalenteri'
					and kalenteri.tapa in ('Kesäloma','Talviloma') 
					and kalenteri.kuka=kuka.kuka
					and kuka.kuka='$kuka' 
					ORDER BY kalenteri.kuka, kalenteri.tapa, kalenteri.pvmalku";
		$result = mysql_query($query) or pupe_error($query);
					
		if (mysql_num_rows($result) > 0) {
			echo "<table>";
			echo "<tr>
					<th>".("Tyyppi")."</th><th>".("Nimi")."</th>
					<th>".("Osasto")."</th><th>".("Pvmalku")."</th>
					<th>".("Aikaalku")."</th><th>".("Pvmloppu")."</th>
					<th>".("Aikaloppu")."</th><th>".("Kommentti")."</th>";
			echo "<th>".t("Hyväksyjä")."</th>";
			echo "<th>".t("Yhtiö")."</th>";
			echo "</tr>";
			
			while ($row = mysql_fetch_array($result)) {			
				echo "<tr>
						<td>$row[tapa]</td><td>$row[nimi]</td>
						<td>$row[osasto]</td><td>$row[pvmalku]</td>
						<td>$row[aikaalku]</td><td>$row[pvmloppu]</td>
						<td>$row[aikaloppu]</td><td>$row[kentta01]</td>";
				echo "<td>$row[kuittaus]</td>";
				echo "<td>$row[yhtio]</td>";				
				echo "</tr>";
			}
			echo "</table>";
		}
	}

	require ("../inc/footer.inc");

?>