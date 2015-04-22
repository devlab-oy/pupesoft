<?php

	require("inc/parametrit.inc");
	
	echo "<font class='head'>Opettajaraportit</font><hr><br>";

	// lomakkeella lähetettyjen tietojen käsittely
	if($_POST['valittu']){
		$valittu =  $_POST['valittu'];
		$valittuRaportti =  $_POST['raporttityyppi'];

		$path = "raportit/".$valittuRaportti.".php";	
		//echo "<p>$valittu -- $valittuRaportti <br/> $path</p>";

		$query = "UPDATE kuka set raportointiyhtio = '$valittu' WHERE yhtio = '$kukarow[yhtio]' AND kuka = '$kukarow[kuka]'";
		mysql_query($query) or pupe_error($query);
		$kukarow['raportointiyhtio'] = $valittu;

		$yhtiorow = hae_yhtion_parametrit($kukarow['raportointiyhtio']);
	}

	// yhtiön valinta
	$query = "	SELECT yhtio, nimi 
			FROM yhtio 
			ORDER BY nimi
		";

	$result = mysql_query($query) or pupe_error($query);

	echo "<form action='' method='post'><select name='valittu'>";
	while ($row=mysql_fetch_array($result)) {
		echo "<option value='$row[yhtio]' ";
			if($kukarow['raportointiyhtio']==$row['yhtio']) echo "selected='selected'";
		echo ">$row[nimi]</option>";
	}
	echo "</select>";

	/*/ raportin valinta
	echo "	<select name='raporttityyppi'>
			<option value='tilanneraportti'>Tilanneraportti</option>
			<option value='tuloslaskelma'>Tuloslaskelma</option>
			<option>Pääkirja/päiväkirja</option>
		</select>
	";	
	*/
	echo "<input type='submit' value='Valitse'></form>";
	
	if($_POST['valittu']){
	echo "<br/>Yritys vaihdettu: ".$yhtiorow['nimi'].".";
	}
	
	require("inc/footer.inc");

?>
