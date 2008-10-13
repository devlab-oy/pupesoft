<?php

//echo "data:<pre>".print_r($_REQUEST, true)."</pre>";	
//parametrit
require('inc/parametrit.inc');

js_popup();
js_showhide();
enable_ajax();

require('inc/kalenteri.inc');

if($projekti > 0) {
	//	T‰m‰ on siis kalenteri jota meid‰n pit‰isi k‰sitell‰
	$kaleDIV = "projektikalenteri_$projekti";

	//	Jos kalenteria ei ole viel‰ m‰‰ritetty niin se pit‰‰ tehd‰ uudestaan
	if($kaleID != $kaleDIV) {
		$otunnus = $projekti;
		
		$kaleID 							= $kaleDIV;
		$kalenteri["div"] 					= $kaleDIV;
		$kalenteri["URL"] 					= "projektikalenteri.php";
		$kalenteri["url_params"]			= array("projekti", "otunnus");
		$kalenteri["liitostunnus"] 			= $liitostunnus;
		$kalenteri["nakyma"]				= "RIVINAKYMA_VIIKKO";
		$kalenteri["tunnusnippu"]			= $projekti;	
		$kalenteri["sallittu_nakyma"]		= array("KUUKAUSINAKYMA", "VIIKKONAKYMA", "PAIVANAKYMA", "RIVINAKYMA_PAIVA", "RIVINAKYMA_VIIKKO");
		$kalenteri["laskutilat"]			= "'L','R','N'";
		

		$kalenteri["kalenteri_tyypit"]				= array("kalenteri", "Muistutus", "projektitapahtuma", );
		$kalenteri["kalenteri_nayta_tyyppi"]		= array("projektitapahtuma");
		
		$kalenteri["kalenteri_tilausdata"]			= array("tilaus", "kerays", "toimitus");
		$kalenteri["kalenteri_nayta_tilausdata"]	= array("kerays", "toimitus");
		
		$kalenteri["kalenteri_ketka"]		= array("kaikki");
		$kalenteri["kalenteri_nayta_kuka"]	= array("");
		
		$kalenteri["kalenteri_jako"]    	= array("tilaus");
		
		alusta_kalenteri($kalenteri);
		$tee_div = "JOO";
	}
	
		
	//	Liitet‰‰n t‰m‰n k‰ytt‰j‰n tekem‰t memot yms aina mukaan.
	$data = kalequery();
	//echo "data:<pre>".print_r($data, true)."</pre>";

	if($tee_div == "JOO") {
		echo "<div id='$kaleDIV'>".kalenteri($data)."</div>";
	}
	else {
		echo kalenteri($data);
	}	
}
else {
	echo "<font class='head'>".t("Projektikalenterien muokkaus")."</font><hr><br><br>";
	
	$query = "	SELECT lasku.tunnus, concat_ws(' ', lasku.nimi, lasku.nimitark) asiakas, laskun_lisatiedot.seuranta, laskun_lisatiedot.projektipaallikko, asiakkaan_kohde.kohde kohde
				FROM lasku
				LEFT JOIN laskun_lisatiedot ON laskun_lisatiedot.yhtio=lasku.yhtio and laskun_lisatiedot.otunnus=lasku.tunnus
				LEFT JOIN kuka pp ON pp.yhtio=lasku.yhtio and pp.tunnus=laskun_lisatiedot.projektipaallikko
				LEFT JOIN asiakkaan_kohde ON asiakkaan_kohde.yhtio=lasku.yhtio and asiakkaan_kohde.tunnus=laskun_lisatiedot.asiakkaan_kohde
				WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila = 'R' and lasku.alatila != 'X'";
	$result = mysql_query($query) or pupe_error($query);
	if(mysql_num_rows($result) > 0) {
		echo "	<table>
					<tr>
						<th>".t("Projekti")."</th>
						<th>".t("Seuranta")."</th>
						<th>".t("Asiakas")."</th>
						<th>".t("Kohde")."</th>
						<th>".t("Projektip‰‰llikkˆ")."</th>
					</tr>";
		while($row = mysql_fetch_array($result)) {
			echo "<tr>
						<td>$row[tunnus]</td>
						<td>$row[seuranta]</td>
						<td>$row[asiakas]</td>
						<td>$row[kohde]</td>
						<td>$row[ppaall]</td>
						<td class='back'>
							<form action='$PHP_SELF' method='post'>
								<input type='hidden' name='projekti' value='$row[tunnus]'>
								<input type='submit' value='".t("Muokkaa aikataulua")."'>
							</form>
						</td>
					</tr>";
		}

		echo "</table>";
	}
	
}

$ei_kelloa = 1;
require ("inc/footer.inc");

?>