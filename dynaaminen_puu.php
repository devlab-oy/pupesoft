<?php
require('inc/parametrit.inc');

GLOBAL $kukarow;

// pi‰‰ jotenkin ottaa nodesta ne arvot johonkin talteen k‰sittely‰ varten...
$Xnodet = explode(",",$puun_tunnus);

//echo "<pre>".var_dump($_GET)."</pre>";


/// T‰m‰ lis‰‰ tiedot kantaa ja sen j‰lkeen passaa parametrej‰ yllapitoon samaan tuotteeseen mist‰ l‰hdettiin.... toivottavasti..
if (isset($tee) AND $tee == 'valitse' AND isset($laji) AND isset($tuoteno)) {

	$v = 1;	

	// k‰ytet‰‰n apumuuttujaa vika sille ett‰ saadaan vika pilkku pois...
	$vika = count($id);

	if ($mista_tulin == 'autodata_tuote') {
		$toim = $mista_tulin;
		$tunnus = $ttunnus;
	}
	else {

		foreach ($id as $selitekenttaan) {TuotteenAlkiot($kukarow, $tuoteno, $selitekenttaan, $laji,$ttunnus);}

//		TuotteenAlkiot($kukarow, $tuoteno, $selitekenttaan, $laji,$ttunnus);

		$q2 = "SELECT tunnus from tuote where tuoteno='$tuoteno'";
		$r2 = mysql_query($q2) or pupe_error($q2);
		$rivi = mysql_fetch_assoc($r2);
		$tunnus = $rivi['tunnus'];

		if ($mista_tulin == 'puun_alkio' AND $toim2 == ''){
			$toim = $mista_tulin;
			$tunnus = ''; 
		} 
		elseif ($mista_tulin == 'puun_alkio' AND $toim2 == 'asiakas'){
			$toim = $toim2;
			$tunnus = $tuoteno;
		//	echo "Tunnus: ". $tunnus. " ja Toim: ".$toim;
		} else{ 
			$toim = $toim;
			$tunnus = $tuoteno;
		}
	}
	
	require('yllapito.php');
	exit;
}

// T‰m‰ luo p‰‰kategorian
if (isset($KatNimi) AND trim($KatNimi) AND $tee == 'paakat' AND isset($laji) AND trim($laji)){
	LisaaPaaKat($kukarow,$KatNimi, $laji);
	$tee = '';
} 
// lis‰t‰‰n kategorialle lapsi    
if (isset($Lnimi) AND trim($Lnimi) AND isset($laji) AND trim($laji)){
	LisaaLapsi($ISI,$laji, $kukarow, $Lnimi,$plft, $subd,$koodi);
	$tee = ''; 
} 
// poistaa ja upgradettaa alemmat lapset isommaksi.
if (isset($tee) and $tee == 'poista' and isset($ISI) AND trim($laji)){
	PoistaLapset($ISI, $plft, $laji,$kukarow);
	$tee = '';
} 
// muutetaan kategorian nime‰ uusiksi
if (isset($tee) and $tee == 'muokkaa' and isset($uusinimi) and isset($ISI) AND isset($laji) AND trim($laji)) {
	
	if (trim($uusinimi) == "") {
		echo "<font class='error'>", t('Et voi laittaa tyhj‰‰ arvoa uudeksi arvoksi')," !</font>";
	}
	else {
		paivitakat($ISI, $uusinimi,$laji,$kukarow,$koodi, $kategoriaid);		
	}
	$tee = '';
} 

// Lisˆt‰‰n uusi taso ja tarkistetaan ettei nimi ole tyhj‰.
if (isset($tee) and $tee == 'taso' and isset($tasonimi) and isset($ISI) AND trim($laji) AND isset($tkoodi) and trim($tkoodi)) {
	
	if (trim($tasonimi) == "") {
		echo "<font class='error'>", t('Et voi antaa tyhj‰‰ arvoa uudeksi tason nimeksi')," !!</font>";
	}
	else {
		LisaaTaso($ISI, $tasonimi ,$plft, $laji,$kukarow, $tkoodi);
	}
	$tee = '';
}

//  T‰m‰ luo lomakkeen alikategorian lis‰‰miseen
if (isset($ISI) AND trim($ISI) != "" AND isset($tee) and $tee == 'lisaa') {
	
	echo "<form method='POST' autocomplete='off'>";
	echo "<table><tr><th>",t('Ylemm‰n Kategorian nimi'),":</th><td>".$ISI."</td></tr>";
	echo "<tr><th>",t('Kirjoita kategorian nimi'),":</th><td><input type='text' size='30' name='Lnimi' /></td></tr>";
	echo "<tr><th>",t('Kirjoita alakategorian koodi'),":</th><td><input type='text' size='30' name='koodi' /></td></tr>";	
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
	echo "<input type='hidden' name='kategoriaid' value='".$kategoriaid."' />";
	echo "</form><br />";

}

	// t‰m‰ tulostaa Tason-lis‰ys lomakkeen
if (isset($ISI) AND trim($ISI) != "" AND isset($tee) and $tee == 'taso') {
	
	echo "<form method='POST' autocomplete='off'>";
	echo "<table><tr><th>",t('Kategorian nimi'),":</th><td>".$ISI."</td></tr>";
	echo "<tr><th>",t('Kirjoita uusi tason nimi'),":</th><td><input type='text' size='30' name='tasonimi' /></td></tr>";
	echo "<tr><th>",t('Kirjoita tason koodi'),":</th><td><input type='text' size='30' name='tkoodi' /></td></tr>";
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
// Tarkistetaan ett‰ toim muuttujan sis‰ltˆ tulee t‰nne saakka ja haetaan tarkka lajinimi kannasta.
if(isset($toim)) {
	
	$toimiq = "select selite from avainsana where laji='dynaaminen_puu' AND selite like '$toim%' AND yhtio='$kukarow[yhtio]'";
	$toimir = mysql_query($toimiq) or pupe_error($toimiq);
	$toimirow = mysql_fetch_assoc($toimir);
	$laji = $toimirow['selite'];
}	
/*
if (!isset($tee) AND !isset($toim)) {
	
	$avainsanaresult = t_avainsana('dynaaminen_puu');
	
	echo "<form method='POST'>";
	echo "<table><tr><th>",t('Valitse p‰‰luokka'),"</th>";
	echo "<td>";

	echo "<select name='laji'><option value='' >",t('Valitse Kategoriasi'),"</option>";
	while ($rou = mysql_fetch_assoc($avainsanaresult)) {
		echo "<option value='{$rou['selite']}'>{$rou['selite']}</option>";
	} 
	echo "</select>";

	echo "</td></tr>";
	echo "<tr><td></td><td><input type='submit' value='",t('HAE'),"' /></td></tr>";
	echo "<input type='hidden' name='tee' value='haetaa' />";
	echo "</table></form>";	
}
*/
if (isset($toim)){
	
$query = "SELECT
			node.lft AS lft,
			node.rgt AS rgt,
			lower(node.nimi) AS node_nimi,
			node.koodi AS node_koodi,
			node.lft AS plft,
			(COUNT(node.nimi) - 1)AS sub_dee,
			node.lft AS parent_lft,
			node.tunnus AS node_tunnus
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
		echo "<table><tr><th>",t('Perusta '),"</th><th> $laji </th>";
		echo "<input type='hidden' size='30' name='KatNimi' value='$laji'/></tr>";
		echo "<tr><td></td><td><input type='submit' value='",t('Perusta kategoria'),"' /></td></tr>";
		echo "<input type='hidden' name='tee' value='paakat' />";
		echo "<input type='hidden' name='laji' value='$laji' />";
		echo "<input type='hidden' name='toim' value='$toim' />";
		echo "<input type='hidden' name='toim2' value='$toim2' />";
		echo "<input type='hidden' name='mista_tulin' value='$mista_tulin' />";
		echo "</table></form>";	
		
	} 
	elseif ($tee == 'tuotteet') {
		
			echo "<form method='GET'>";
			echo "<table>";

			while($row = mysql_fetch_assoc($result)) {

				echo "\n<tr>";

				for ($i = 0; $i < $row['sub_dee']; $i++) {
					echo "\n<td width='0' class='back'>&nbsp;</td>"; // tulostaa taulun syvyytt‰
				}

				if ($row['plft'] == 1) {
					echo "\n<td nowrap rowspan='",lapset($row['node_nimi'],$row['parent_lft'],$laji,$kukarow)+2,"'>{$row['node_nimi']}";

					echo "</td></tr>\n";
					echo "\n<tr><td class='back'>&nbsp;</td></tr>";
					// tulostaa p‰‰kategorian viereen tyhj‰n ruuduun niin nˆytt‰‰ paremmalta.
				}
				else {
					if ($row['node_koodi'] == 0) {
						$row['node_koodi']='';
					}

					$check = '';
					if (in_array($row['node_tunnus'], $Xnodet)) {
						$check = ' checked';
					}

					// jos arrayn joku sis‰ltˆ vastaa vastaa node_tunnusta, tulostetaan check-boxiin rasti, muussa tapauksessa tulostetaan tyhj‰ boksi
					echo "\n<td rowspan='",lapset($row['node_nimi'],$row['parent_lft'],$laji,$kukarow)+1,"'><input type='checkbox' name='id[]' value='{$row[node_tunnus]}' $check />&nbsp;",$row['node_koodi'] ,' ',ucwords(strtolower(str_replace('/', ', ', $row['node_nimi']))),"</td></tr>";

				}
			}
			echo "</table><br /><br />";
			echo "<input type='hidden' name='laji' value='".$laji."' />";
			echo "<input type='hidden' name='yhtio' value='".$kukarow['yhtio']."' />";
			echo "<input type='hidden' name='tuoteno' value='".$tuoteno."' />";
			echo "<input type='hidden' name='tee' value='valitse' />";
			echo "<input type='hidden' name='ttunnus' value='".$ttunnus."' />";
			echo "<input type='hidden' name='toim' value='".$toim."' />";
			echo "<input type='hidden' name='kategoriaid' value='".$row['node_tunnus']."' />";
			echo "<input type='hidden' name='mista_tulin' value='$mista_tulin' />";
			echo "<input type='hidden' name='toim2' value='$toim2' />";
			echo "<input type='submit' name='valitse' value='",t('Tallenna valinnat tuotteelle'),"'>";
			echo "</form>";
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
				echo "\n<br /><a href='?toim=$toim&laji=$laji&ISI=".$row['node_nimi']."&tee=lisaa&plft=".$row['plft']."&subd=".$row['sub_dee']."'><img src='{$palvelin2}pics/lullacons/doc-option-add.png' alt='",t('Lis‰‰ lapsikategoria'),"'/></a>";
			 	echo "\n&nbsp;<a href='?toim=$toim&laji=$laji&ISI=".$row['node_nimi']."&tee=poista&plft=".$row['plft']."'><img src='{$palvelin2}pics/lullacons/doc-option-remove.png' alt='",t('Poista lapsikategoria'),"'/></a>";	
			 	echo "\n&nbsp;<a href='?toim=$toim&laji=$laji&ISI=".$row['node_nimi']."&tee=muokkaa&plft=".$row['plft']."&kategoriaid=".$row['node_tunnus']."'><img src='{$palvelin2}pics/lullacons/doc-option-edit.png' alt='",t('Muokkaa lapsikategoriaa'),"'/></a>";
				echo "\n&nbsp;<a href='?toim=$toim&laji=$laji&ISI=".$row['node_nimi']."&tee=taso&plft=".$row['plft']."'><img src='{$palvelin2}pics/lullacons/database-option-add.png' alt='",t('Lis‰‰ taso'),"'/></a>";
				echo "</td></tr>\n";    
				echo "\n<tr><td class='back'>&nbsp;</td></tr>";
				// tulostaa p‰‰kategorian viereen tyhj‰n ruuduun niin nˆytt‰‰ paremmalta.
			}
			else {
				if ($row['node_koodi'] == 0) {$row['node_koodi']='';}
				echo "\n<td rowspan='",lapset($row['node_nimi'],$row['parent_lft'],$laji,$kukarow)+1,"'>",$row['node_koodi'] ,' ',ucwords(strtolower(str_replace('/', ', ', $row['node_nimi'])))," ($row[node_tunnus])";
				echo "\n<br /><a href='?toim=$toim&koodi=".$row['node_koodi']."&laji=$laji&ISI=".$row['node_nimi']."&tee=lisaa&plft=".$row['plft']."&subd=".$row['sub_dee']."'><img src='{$palvelin2}pics/lullacons/doc-option-add.png' alt='",t('Lis‰‰ lapsikategoria'),"'/></a>";
			 	echo "\n&nbsp;<a href='?toim=$toim&koodi=".$row['node_koodi']."&laji=$laji&ISI=".$row['node_nimi']."&tee=poista&plft=".$row['plft']."'><img src='{$palvelin2}pics/lullacons/doc-option-remove.png' alt='",t('Poista lapsikategoria'),"'/></a>";	
			 	echo "\n&nbsp;<a href='?toim=$toim&koodi=".$row['node_koodi']."&laji=$laji&ISI=".$row['node_nimi']."&tee=muokkaa&plft=".$row['plft']."&kategoriaid=".$row['node_tunnus']."'><img src='{$palvelin2}pics/lullacons/doc-option-edit.png' alt='",t('Muokkaa lapsikategoriaa'),"'/></a>";
		
				if (lapset($row['node_nimi'],$row['parent_lft'],$laji,$kukarow) > 0) {
					echo "\n&nbsp;<a href='?toim=$toim&koodi=".$row['node_koodi']."&laji=$laji&ISI=".$row['node_nimi']."&tee=taso&plft=".$row['plft']."'><img src='{$palvelin2}pics/lullacons/database-option-add.png' alt='",t('Lis‰‰ taso'),"'/></a>";
				}
				echo "</td></tr>";
			
			}
	
		}
		echo "</table><br /><br />";
	}
}

echo "<br />";
require('../inc/footer.inc');
?>