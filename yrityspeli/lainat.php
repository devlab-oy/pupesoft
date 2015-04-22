<?php
/// TAMKin oppimisymp‰ristˆ, lainatapahtumien tarkastelu
/// lainat.php
/// Author: Jarmo Kortetj‰rvi
/// created: 01.12.2010
/// modified: 01.12.2010

require ("../inc/parametrit.inc");

// Kaikki lainat
function kaikkiLainat(){
	print "	<font class='head'>Lainojen tiedot</font><hr><br/>";

		$query = "	SELECT 	TAMK_pankkitapahtuma.saaja 				AS 'Saajan tilinumero'
							, TAMK_pankkitapahtuma.saajanNimi		AS 'Saajan nimi'
							, TAMK_pankkitapahtuma.summa				AS 'Lainan m‰‰r‰ (&euro;)'
							, TAMK_pankkitapahtuma.arkistotunnus 	AS 'Lainan tunnus'
							, TAMK_pankkitapahtuma.viite				AS 'Viite'
					FROM TAMK_lainantiedot
					JOIN TAMK_pankkitapahtuma on TAMK_lainantiedot.arkistotunnus = TAMK_pankkitapahtuma.arkistotunnus
					WHERE TAMK_pankkitapahtuma.saaja != 99912300000518 -- ei Ainopankki
					AND TAMK_pankkitapahtuma.saaja != 99912300014493 -- ei Myyr‰
					ORDER BY TAMK_pankkitapahtuma.saajanNimi ASC
					";
		$lainares = mysql_query($query) or pupe_error($query);
		
		// Tulostetaan taulu
		echo "<table>";
			
			// Header-rivi
			echo "<tr>";
			for ($i=0; $i<mysql_num_fields($lainares); $i++) {
				echo "<th>".mysql_field_name($lainares,$i)."</th>";
			}
			echo "</tr>";
			
			// Datarivit
			while ($row = mysql_fetch_assoc($lainares)) {
				echo "<tr>";
					foreach($row as $key=>$value) {
						if($key=='Viite'){
							echo "<td><a href='lainat.php?laina=$value'>$value</a></td>";
						}
						else{
							echo "<td>$value</td>";
						}
					}
				echo "</tr>";
			}
		
		echo "</table>";
}
// Tietty laina
function naytaLaina($viite){
	$query = "	SELECT 	maksaja AS 'Maksajan tilinro'
						, maksajanNimi				 					AS 'Maksajan nimi'
						, DATE_FORMAT(tapvm, '%d.%m.%Y') 				AS 'Tapahtumapvm'
						, IF(eiVaikutaSaldoon = 'l', summa, summa*-1) 	AS 'summa'
						, selite										AS 'Selite'
						, IF(selite REGEXP '[0-9]{2}\/[0-9]{4}$', SUBSTRING(selite, -7), ' ') AS 'eranro'
				FROM TAMK_pankkitapahtuma
				WHERE viite = '$viite'
				ORDER BY tapvm ASC, eranro ASC, eiVaikutaSaldoon ASC
			";
	$lainares = mysql_query($query) or pupe_error($query);

	print "	<font class='head'>Laina $viite</font><hr><br/>";
	
		// Tulostetaan taulu
		echo "<table>";
			
		// Header-rivi
		echo "<tr>";
		for ($i=0; $i<mysql_num_fields($lainares); $i++) {
			echo "<th>".mysql_field_name($lainares,$i)."</th>";
		}
		echo "</tr>";
		
		// Datarivit
		while ($row = mysql_fetch_assoc($lainares)) {
			echo "<tr>";
				foreach($row as $key=>$value) {
						echo "<td>$value</td>";
				}
			echo "</tr>";
		}
		
		echo "</table>";
	
	// Takaisin-painike
	echo "<p><a href='lainat.php'>&larr Takaisin</a></p>";
}
// Laina valittu
if($laina){
	naytaLaina($laina);
}

// N‰ytet‰‰n kaikki
else{
	kaikkiLainat();
}

require ("../inc/footer.inc");