<?php
require('inc/parametrit.inc');
include_once('inc/functiot_dynaaminen_puu.inc');
// error_reporting(E_ALL | E_STRICT);
// ini_set('display_errors', 1);
GLOBAL $kukarow;

// T‰m‰ luo p‰‰kategorian
if (isset($KatNimi) AND trim($KatNimi) AND $tee == 'paakat' AND isset($laji) AND trim($laji)){
	LisaaPaaKat($kukarow,$KatNimi, $laji);
	//unset($tee);
	$tee = '';
} 
// lis‰t‰‰n kategorialle lapsi    
if (isset($Lnimi) AND trim($Lnimi) AND isset($laji) AND trim($laji)){
	LisaaLapsi($ISI,$laji, $kukarow, $Lnimi,$plft, $subd,$koodi);
	//unset($tee);
	$tee = '';
} 
// poistaa ja upgradettaa alemmat lapset isommaksi.
if (isset($tee) and $tee == 'poista' and isset($ISI) AND trim($laji)){
	PoistaLapset($ISI, $plft, $laji,$kukarow);
	//unset($tee);
	$tee = '';
} 
// muutetaan kategorian nime‰ uusiksi
if (isset($tee) and $tee == 'muokkaa' and isset($uusinimi) and isset($ISI) AND isset($laji) AND trim($laji)) {
	
	if (trim($uusinimi) == "") {
		echo "<font class='error'> Et voi laittaa tyhj‰‰ arvoa uudeksi arvoksi !</font>";
	}
	else {
		paivitakat($ISI, $uusinimi,$laji,$kukarow,$koodi);		
	}
	//unset($tee);
	$tee = '';
} 

// Lisˆt‰‰n uusi taso ja tarkistetaan ettei nimi ole tyhj‰.
if (isset($tee) and $tee == 'taso' and isset($tasonimi) and isset($ISI) AND trim($laji) AND isset($tkoodi) and trim($tkoodi)) {
	
	if (trim($tasonimi) == "") {
		echo "<font class='error'> Et voi antaa tyhj‰‰ arvoa uudeksi tason nimeksi !!</font>";
	}
	else {
		LisaaTaso($ISI, $tasonimi ,$plft, $laji,$kukarow, $tkoodi);
	}
	//unset($tee);
	$tee = '';
}

//  T‰m‰ luo lomakkeen alikategorian lis‰‰miseen
if (isset($ISI) AND trim($ISI) != "" AND isset($tee) and $tee == 'lisaa') {
	
	echo "<form method='POST' autocomplete='off'>";
	echo "<table><tr><th>",t('Ylemm‰n Kategorian nimi'),":</th><td>".$ISI."</td></tr>";
	echo "<th>",t('Kirjoita kategorian nimi'),":</th><td><input type='text' size='30' name='Lnimi' /></td></tr>";
	echo "<th>",t('Kirjoita alakategorian koodi'),":</th><td><input type='text' size='30' name='koodi' /></td></tr>";	
	echo "<tr><td><input type='submit' value='",t('Tallenna Alakategoria'),"' /></td></tr></table>";
	echo "<input type='hidden' name='tee' value='lisaa' />";
	echo "<input type='hidden' name='laji' value='$laji' />";
	echo "<input type='hidden' name='plft' value='".$plft."' />";
	echo "</form><br />";
	
}

	// t‰m‰ tulostaa nimen-muutos lomakkeen
if (isset($ISI) AND trim($ISI) != "" AND isset($tee) and $tee == 'muokkaa') {
	
	echo "<form method='POST' autocomplete='off'>";
	echo "<table><tr><th>",t('Kategorian nimi'),":</th><td>".$ISI."</td></tr>";
	echo "<tr><th>",t('Kirjoita kategorian uusinimi'),":</th><td><input type='text' size='30' name='uusinimi' /></td></tr>";
	echo "<tr><th>",t('Muokkaa kategorian koodia'),":</th><td><input type='text' size='30' name='koodi' value='".$koodi."'/></td></tr>";
	echo "<tr><td><input type='submit' value='",t('Tallenna Alakategoria'),"' /></td></tr></table>";
	echo "<input type='hidden' name='tee' value='muokkaa' />";
	echo "<input type='hidden' name='ISI' value='".$ISI."' />";
	echo "<input type='hidden' name='laji' value='".$laji."' />";
	echo "</form><br />";

}

	// t‰m‰ tulostaa Tason-lis‰ys lomakkeen
if (isset($ISI) AND trim($ISI) != "" AND isset($tee) and $tee == 'taso') {
	
	echo "<form method='POST' autocomplete='off'>";
	echo "<table><tr><th>",t('Kategorian nimi'),":</th><td>".$ISI."</td></tr>";
	echo "<tr><th>",t('Kirjoita uusi tason nimi'),":</th><td><input type='text' size='30' name='tasonimi' /></td></tr>";
	echo "<th>",t('Kirjoita tason koodi'),":</th><td><input type='text' size='30' name='tkoodi' /></td></tr>";
	echo "<tr><td><input type='submit' value='",t('Tallenna taso'),"' /></td></tr></table>";
	echo "<input type='hidden' name='tee' value='taso' />";
	echo "<input type='hidden' name='ISI' value='".$ISI."' />";
	echo "<input type='hidden' name='plft' value='".$plft."' />";
	echo "<input type='hidden' name='laji' value='".$laji."' />";
	echo "</form><br />";       

}

if (isset($laji) AND trim($laji) == '' ) {
	unset($tee);
}

if (!isset($tee)) {
	
	$avainsanaresult = t_avainsana('dynaaminen_puu');
	
	echo "<form method='POST'>";
	echo "<table><tr><th>",t('Valitse p‰‰luokka'),"</th>";
	echo "<td>";
	
	echo "<select name='laji'><option value='' >Valitse Kategoriasi</option>";
	while ($rou = mysql_fetch_assoc($avainsanaresult)) {
		echo "<option value='{$rou['selite']}'>{$rou['selitetark']}</option>";
	} 
	echo "</select>";
	
	echo "</td></tr>";
	echo "<tr><td></td><td><input type='submit' value='HAE' /></td></tr>";
	echo "<input type='hidden' name='tee' value='haetaa' />";
	echo "</table></form>";	
	
}

else {
	
$query = "SELECT
			node.lft AS lft,
			node.rgt AS rgt,
			node.nimi AS node_nimi,
			node.koodi AS node_koodi,
			node.lft AS plft,
			(COUNT(node.nimi) - 1)AS sub_dee,
			node.lft AS parent_lft
		FROM
			dynaaminen_puu AS node,
			dynaaminen_puu AS parent
		WHERE
			node.lft BETWEEN parent.lft
		AND parent.rgt
		AND node.laji = '{$laji}'
		AND parent.laji = '{$laji}'
		AND node.yhtio = '{$kukarow[yhtio]}'
		GROUP BY
			node.lft
		ORDER BY
			node.lft";
			
//	echo $query;
						
	$result = mysql_query($query) or pupe_error($query);

		// Mik‰li sivulle tullaan ensimm‰isen kerran ja p‰‰kategoriaa ei ole niin t‰m‰ luo kyseisen kategorian.
	if (mysql_num_rows($result) == 0) {
				
		echo "<form method='POST'>";
		echo "<table><th>",t('Anna P‰‰luokan : '.$laji.' <br />vaihtoehtoinen nimi'),"</th>";
		echo "<td><input type='text' size='30' name='KatNimi' value='$laji'/></td></tr>";
		echo "<tr><td></td><td><input type='submit' value='Tallenna P‰‰kategoria' /></td></tr>";
		echo "<input type='hidden' name='tee' value='paakat' />";
		echo "<input type='hidden' name='laji' value='$laji' />";
		echo "</table></form>";	
		
	} 
	else {
		
		echo "<table>";

		while($row = mysql_fetch_assoc($result)) {
	
			echo "\n<tr>";
					
			for ($i = 0; $i < $row['sub_dee']; $i++) {
				echo "\n<td width='0' class='back'>&nbsp;</td>"; // tulostaa taulun syvyytt‰
			}

			if ($row['plft'] == 1) {
				echo "\n<td nowrap rowspan='",lapset($row['node_nimi'],$row['parent_lft'],$laji,$kukarow)+2,"'>{$row['node_nimi']}";
				echo "\n<br /><a href='?laji=$laji&ISI=".$row['node_nimi']."&tee=lisaa&plft=".$row['plft']."&subd=".$row['sub_dee']."'><img src='{$palvelin2}pics/lullacons/doc-option-add.png' /></a>";
			 	echo "\n&nbsp;<a href='?laji=$laji&ISI=".$row['node_nimi']."&tee=poista&plft=".$row['plft']."'><img src='{$palvelin2}pics/lullacons/doc-option-remove.png' /></a>";	
			 	echo "\n&nbsp;<a href='?laji=$laji&ISI=".$row['node_nimi']."&tee=muokkaa&plft=".$row['plft']."'><img src='{$palvelin2}pics/lullacons/doc-option-edit.png' /></a>";
				echo "\n&nbsp;<a href='?laji=$laji&ISI=".$row['node_nimi']."&tee=taso&plft=".$row['plft']."'><img src='{$palvelin2}pics/lullacons/database-option-add.png' /></a>";
				echo "</td></tr>\n";
				echo "\n<tr><td class='back'>&nbsp;</td></tr>";
				// tulostaa p‰‰kategorian viereen tyhj‰n ruuduun niin nˆytt‰‰ paremmalta.
			}
			else {

				echo "\n<td nowrap rowspan='",lapset($row['node_nimi'],$row['parent_lft'],$laji,$kukarow)+1,"'>",$row['node_koodi'] ,' ',str_replace(' # ', '<br />',  ucwords(strtolower(str_replace('/', ' # ', $row['node_nimi']))));
				echo "\n<br /><a href='?koodi=".$row['node_koodi']."&laji=$laji&ISI=".$row['node_nimi']."&tee=lisaa&plft=".$row['plft']."&subd=".$row['sub_dee']."'><img src='{$palvelin2}pics/lullacons/doc-option-add.png' /></a>";
			 	echo "\n&nbsp;<a href='?koodi=".$row['node_koodi']."&laji=$laji&ISI=".$row['node_nimi']."&tee=poista&plft=".$row['plft']."'><img src='{$palvelin2}pics/lullacons/doc-option-remove.png' /></a>";	
			 	echo "\n&nbsp;<a href='?koodi=".$row['node_koodi']."&laji=$laji&ISI=".$row['node_nimi']."&tee=muokkaa&plft=".$row['plft']."'><img src='{$palvelin2}pics/lullacons/doc-option-edit.png' /></a>";
		
				if (lapset($row['node_nimi'],$row['parent_lft'],$laji) > 0) {
					echo "\n&nbsp;<a href='?koodi=".$row['node_koodi']."&laji=$laji&ISI=".$row['node_nimi']."&tee=taso&plft=".$row['plft']."'><img src='{$palvelin2}pics/lullacons/database-option-add.png' /></a>";
				}
				echo "</td></tr>";
			
			}
	
		}
	
	
	
		echo "</tr></table><br /><br />";

		mysql_data_seek($result, 0); // rollataan tulokset alkuun.
	
/*
		echo "<table>";
		
		while($row = mysql_fetch_assoc($result)) {
	
		 	echo "<tr><th>Nimi:</th><td>".$row['node_nimi']."</td><!-- <th>Sub-Depth</th><td>".$row['sub_dee']."<td>".lapset($row['node_nimi'])."<th>kat numeron v&auml;li </th><td>".$row['lft']." - ".$row['rgt']."</td>-->";
		 	echo "<td><a href='?ISI=".$row['node_nimi']."&tee=lisaa&plft=".$row['plft']."&subd=".$row['sub_dee']."'>",t('Lis‰‰ AlaKategoria'),"</a></td>";
		 	echo "<td><a href='?ISI=".$row['node_nimi']."&tee=poista&plft=".$row['plft']."'>",t('Poista Kategoria'),"</a></td>";	
		 	echo "<td><a href='?ISI=".$row['node_nimi']."&tee=muokkaa&plft=".$row['plft']."'>",t('Muokkaa nime‰'),"</a></td>";	
		
			// tulostetaan taulukkoon lis‰‰, poisto ja muokkaa linkit.

		}
		echo "</table>";
*/
	}
}

// Lis‰t‰‰n uusi childi kategoriaan Portable electronic !
echo "<br />";


require('../inc/footer.inc');
?>