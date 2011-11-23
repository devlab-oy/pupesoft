<?php

require ("inc/parametrit.inc");

echo "<font class='head'>".t("Tyhj� rahtikirja").":</font><hr><br>";

if (isset($_POST['tee']) && $_POST['tee'] == 'Valmis') {

    // tallennetaan rahtikirja
    $clean = array(
        'yhtio'        => $kukarow['yhtio'],
		'merahti'      => (isset($_POST['merahti']) && $_POST['merahti'] == '1') ? 'K' : 'E',
        'toimitustapa' => strip_tags($_POST['toimitustapa']),
        'otsikkonro'   => null, //tunnus,
		'viitelah'     => strip_tags($_POST['viitelah']),
		'viitevas'     => strip_tags($_POST['viitevas']),
        'rahtisopimus' => (isset($_POST['rahtisopimus'])) ? $_POST['rahtisopimus'] : '',
    );

	$count = 0;

	// k�yd��n jokainen pakkaustyypii l�pi (yksi rivi rahtikirjassa)
	for ($i = 0; $i < count($pakkaus); $i++) {

		// jotain sy�tettiin
		$_POST['kilot'][$i]		= str_replace(',', '.', $_POST['kilot'][$i]);
		$_POST['kollit'][$i]	= str_replace(',', '.', $_POST['kollit'][$i]);
		$_POST['kuutiot'][$i]	= str_replace(',', '.', $_POST['kuutiot'][$i]);
		$_POST['lavametri'][$i]	= str_replace(',', '.', $_POST['lavametri'][$i]);

		if((isset($_POST['kilot'][$i]) && is_numeric($_POST['kilot'][$i]))
		|| (isset($_POST['kollit'][$i]) && is_numeric($_POST['kollit'][$i]))
		|| (isset($_POST['kuutiot'][$i]) && is_numeric($_POST['kuutiot'][$i]))
		|| (isset($_POST['lavametri'][$i]) && is_numeric($_POST['lavametri'][$i]))) {

			$count++;

			$data = array(
				'pakkaus'           => strip_tags($_POST['pakkaus'][$i]),
				'pakkauskuvaus'     => strip_tags($_POST['pakkauskuvaus'][$i]),
				'kilot'             => $_POST['kilot'][$i],
	        	'kollit'            => $_POST['kollit'][$i],
	        	'kuutiot'           => $_POST['kuutiot'][$i],
	        	'lavametri'         => $_POST['lavametri'][$i],
				'pakkauskuvaustark' => $_POST['pakkauskuvaustark'][$i]);

			$data = array_merge($clean, $data);

			if ($count === 1) {
				// eka rivi, insertoidaan ja otetaan tunnus
				$otsikkonro = pupe_rahtikirja_insert($data);
			}
			else {
				$data['otsikkonro'] 	= $otsikkonro * -1;
				$data['rahtikirjanro'] 	= $otsikkonro * -1;
				pupe_rahtikirja_insert($data);
			}
		}
	}

	// korjataan ensimm�inen rivi jossa on v��r� otsikkonro sek� rahtikirjanro
	$query = sprintf(
		"UPDATE rahtikirjat set otsikkonro = '%s', rahtikirjanro = '%s' where tunnus = '%s'",
		(int) $otsikkonro * -1,
		(int) $otsikkonro * -1,
		(int) $otsikkonro
	);

	pupe_query($query);

	// --------------------------------------------------------
	//
	// TULOSTUS!!!

	$data = pupe_rahtikirja_fetch(($otsikkonro * -1));

	$GLOBALS['lotsikot']  = $data['lotsikot'];
	$GLOBALS['pakkaus']   = $data['pakkaus'];
	$GLOBALS['kilot']     = $data['kilot'];
	$GLOBALS['kollit']    = $data['kollit'];
	$GLOBALS['kuutiot']   = $data['kuutiot'];
	$GLOBALS['lavametri'] = $data['lavametri'];

    $GLOBALS['kilotyht']  = $data['kilotyht'];
    $GLOBALS['kollityht'] = $data['kolliyht'];

	$GLOBALS['rtunnus']   = abs($otsikkonro);

	// pistet��n kaikki globaaleiksi
	$GLOBALS = array_merge($GLOBALS, $data);

	// kerrotaan ett� t�m� on custom rahtikirja == ei haeta laskulta mit��n
	$GLOBALS['tyhja'] = 1;

	if ($data['merahti'] == 'K') {
		$rahdinmaksaja = 'L�hett�j�';
		$toitarow = array(
		    'sopimusnro'       => $data['rahtisopimus'],
		    'selite'           => $data['toimitustapa'],
		    'rahdinkuljettaja' => '',
		);
	}
	else {
		$rahdinmaksaja = 'Vastaanottaja';
		$toitarow = array(
		    'selite'           => $data['toimitustapa'],
		    'rahdinkuljettaja' => '',
		);
	}

	$query = "	SELECT *
				FROM asiakas
				WHERE yhtio = '$kukarow[yhtio]'
				AND ytunnus = '$ytunnus'";
	$asres = pupe_query($query);
	$asiakasrow = mysql_fetch_assoc($asres);

	$osoitelappurow["tunnus"] = $otsikkonro;

	if ($tnimi != '') {
		$osoitelappurow["toim_postino"] = $tpostino;
		$osoitelappurow["toim_nimi"] 	= $tnimi;
		$osoitelappurow["toim_nimitark"]= $tnimitark;
		$osoitelappurow["toim_postitp"] = $tpostitp;
		$osoitelappurow["toim_osoite"] 	= $tosoite;
		$osoitelappurow["toim_maa"] 	= $asiakasrow["toim_maa"];
	}
	elseif ($asiakasrow["toim_nimi"] != '') {
		$osoitelappurow["toim_postino"] = $asiakasrow["toim_postino"];
		$osoitelappurow["toim_nimi"] 	= $asiakasrow["toim_nimi"];
		$osoitelappurow["toim_nimitark"]= $asiakasrow["toim_nimitark"];
		$osoitelappurow["toim_postitp"] = $asiakasrow["toim_postitp"];
		$osoitelappurow["toim_maa"] 	= $asiakasrow["toim_maa"];
		$osoitelappurow["toim_osoite"] 	= $asiakasrow["toim_osoite"];
	}
	else {
		$osoitelappurow["toim_postino"] = $asiakasrow["postino"];
		$osoitelappurow["toim_nimi"] 	= $asiakasrow["nimi"];
		$osoitelappurow["toim_nimitark"]= $asiakasrow["nimitark"];
		$osoitelappurow["toim_postitp"] = $asiakasrow["postitp"];
		$osoitelappurow["toim_maa"] 	= $asiakasrow["maa"];
		$osoitelappurow["toim_osoite"] 	= $asiakasrow["osoite"];
	}

	$osoitelappurow["ytunnus"] 			= $asiakasrow["ytunnus"];
	$osoitelappurow["nimi"] 			= $asiakasrow["nimi"];
	$osoitelappurow["nimitark"] 		= $asiakasrow["nimitark"];
	$osoitelappurow["osoite"] 			= $asiakasrow["osoite"];
	$osoitelappurow["postino"] 			= $asiakasrow["postino"];
	$osoitelappurow["postitp"] 			= $asiakasrow["postitp"];
	$osoitelappurow["viesti"] 			= "";
	$osoitelappurow["liitostunnus"] 	= $asiakasrow["tunnus"];
	$osoitelappurow["toimitustapa"] 	= $data['toimitustapa'];
	$osoitelappurow["maksuehto"] 		= $asiakasrow["maksuehto"];
	$osoitelappurow["yhteyshenkilo"] 	= $kukarow["tunnus"];
	$osoitelappurow["sisviesti1"] 		= $asiakasrow["sisviesti1"];
	$osoitelappurow["merahti"] 			= $data['merahti'];
	$osoitelappurow["laatija"] 			= $kukarow['kuka'];


	// yhti�n tiedot
	$osoitelappurow['yhtio']			= $yhtiorow["yhtio"];
	$osoitelappurow['yhtio_nimi'] 		= $yhtiorow["nimi"];
	$osoitelappurow['yhtio_osoite']		= $yhtiorow["osoite"];
	$osoitelappurow['yhtio_postino']	= $yhtiorow["postino"];
	$osoitelappurow['yhtio_postitp']	= $yhtiorow["postitp"];

	// poikkeava toimipaikka,otetaan sen ositetiedot
	$alhqur = "	SELECT *
				from yhtion_toimipaikat
				WHERE yhtio = '$kukarow[yhtio]'
				and tunnus = '$kukarow[toimipaikka]'";
	$alhire = pupe_query($alhqur);

	if (mysql_num_rows($alhire) == 1) {
		$apualvrow = mysql_fetch_assoc($alhire);

		$osoitelappurow['yhtio_nimi'] 		= $apualvrow["nimi"];
		$osoitelappurow['yhtio_nimitark']	= $apualvrow["nimitark"];
		$osoitelappurow['yhtio_osoite']		= $apualvrow["osoite"];
		$osoitelappurow['yhtio_postino']	= $apualvrow["postino"];
		$osoitelappurow['yhtio_postitp']	= $apualvrow["postitp"];
		$osoitelappurow["yhtio_maa"]      	= $apualvrow["maa"];
		$osoitelappurow['yhtio_toimipaikka']= $apualvrow["tunnus"];
	}

	// haetaan varaston osoitetiedot, k�ytet��n niit� l�hetystietoina
	$query = "	SELECT nimi, nimitark, osoite, postino, postitp, maa
				FROM varastopaikat
				WHERE yhtio = '$kukarow[yhtio]'
				AND tunnus = '$varasto'";
	$tempr = pupe_query($query);
	$postirow_varasto = mysql_fetch_assoc($tempr);

	// jos varastolle on annettu joku osoite, k�ytet��n sit�
	if ($postirow_varasto["nimi"] != "") {
		$postirow["yhtio_nimi"]     = $postirow_varasto["nimi"];
		$postirow['yhtio_nimitark']	= $postirow_varasto["nimitark"];
		$postirow["yhtio_osoite"]   = $postirow_varasto["osoite"];
		$postirow["yhtio_postino"]  = $postirow_varasto["postino"];
		$postirow["yhtio_postitp"]  = $postirow_varasto["postitp"];
		$postirow["yhtio_maa"]      = $postirow_varasto["maa"];
	}

	$rahtikirjanrostring = mysql_real_escape_string(serialize($osoitelappurow));

	$query  = "	SELECT *
				FROM toimitustapa
				WHERE yhtio = '{$GLOBALS['kukarow']['yhtio']}'
				AND selite = '$toimitustapa'
				ORDER BY jarjestys,selite";
	$result = pupe_query($query);
	$toitarow = mysql_fetch_assoc($result);

	if ((int) $tulostin > 0 and $oslappkpl > 0) {
		$query = "	SELECT komento
					from kirjoittimet
					where tunnus 	= '$tulostin'
					AND yhtio		= '$kukarow[yhtio]'";
		$res = pupe_query($query);

		$k = mysql_fetch_assoc($res);

	    $kirjoitin = $k['komento'];
		$tulostuskpl = $oslappkpl;

		include ("tilauskasittely/$toitarow[rahtikirja]");

		echo "<p>".t("Tulostetaan rahtikirja")."...</p><br>";
	}

	//Tallennetaan customrahtikirjan tiedot j�rjestelm��n
	if ((int) $otsikkonro != 0) {
		$query  = "	UPDATE rahtikirjat
					SET rahtikirjanro = '$rahtikirjanro',
					tyhjanrahtikirjan_otsikkotiedot = '$rahtikirjanrostring',
					tulostettu = now()
					where yhtio='$kukarow[yhtio]' and otsikkonro=($otsikkonro*-1) and rahtikirjanro=($otsikkonro*-1)";
		$kirres = pupe_query($query);
	}

	if ((int) $valittu_oslapp_tulostin > 0 and $oslappkpl > 0) {

		//haetaan osoitelapun tulostuskomento
		$query  = "	SELECT *
					from kirjoittimet
					where yhtio	= '$kukarow[yhtio]'
					and tunnus	= '$valittu_oslapp_tulostin'";
		$kirres = pupe_query($query);
		$kirrow = mysql_fetch_assoc($kirres);
		$oslapp = $kirrow['komento'];

		// Tulostetaan osoitelappu
		if ($oslapp != "email") $oslapp .= " -#$oslappkpl ";

		if ($toitarow['osoitelappu'] == 'intrade') {
			require('tilauskasittely/osoitelappu_intrade_pdf.inc');
		}
		else {
			require ("tilauskasittely/osoitelappu_pdf.inc");
		}

		echo "<p>".t("Tulostetaan osoitelappu")."...</p><br>";
	}

	$asiakasid = false;
}

if (isset($_POST['ytunnus']) && $asiakasid !== false) {
    require 'inc/asiakashaku.inc';
}

if (!$asiakasid) {

	if (isset($_POST['ytunnus'])) {
	   echo "<br><br>";
	}

	echo "<table><form action='' method='POST' name='haku'>
	    	<tr><th>".t('Hae asiakas')."</th><td><input type='text' name='ytunnus' value=''></td>
	        <td class='back'><input type='submit' value=".t('Etsi')."></td>
	    	</tr></form></table>";

	$formi = "haku";
	$kentta = "ytunnus";

	if (!isset($_POST['ytunnus'])) {
		$query  = "	SELECT max(rahtikirjanro) rahtikirjanro, max(tulostettu) tulostettu, max(tyhjanrahtikirjan_otsikkotiedot) tyhjanrahtikirjan_otsikkotiedot, sum(kilot) paino
					FROM rahtikirjat
					where yhtio = '$kukarow[yhtio]'
					and otsikkonro < 0
					and tulostettu >= date_sub(now(), INTERVAL 180 DAY)
					GROUP BY rahtikirjanro
					ORDER BY tulostettu desc";
		$kirres = pupe_query($query);

		if (mysql_num_rows($kirres) > 0) {

			$query = "	SELECT *
						FROM kirjoittimet
						WHERE yhtio = '$kukarow[yhtio]'
						ORDER by kirjoitin";
			$kirre = pupe_query($query);

			echo "<br><br>".t("Uusimmat tyhj�t rahtikirjat").":<br>";
			echo "<table>";
			echo "<tr>";
			echo "<th>".t("Rahtikirjanro")."</th>";
			echo "<th>".t("Tulostettu")."</th>";
			echo "<th>".t("Asiakas")."</th>";
			echo "<th>".t("Osoite")."</th>";
			echo "<th>".t("Postino")."</th>";
			echo "<th>".t("Paino KG")."</th>";
			echo "</tr>";

			while ($rakir_row = mysql_fetch_assoc($kirres)) {

				$osoitelappurow = unserialize($rakir_row["tyhjanrahtikirjan_otsikkotiedot"]);

				if ($rakir_row['rahtikirjanro'] != '') {
					echo "<tr>";
					echo "<td>$rakir_row[rahtikirjanro]</td>";
					echo "<td>".tv1dateconv($rakir_row["tulostettu"], "P")."</td>";
					echo "<td>$osoitelappurow[toim_nimi] $osoitelappurow[toim_snimitark]</td>";
					echo "<td>$osoitelappurow[toim_osoite]</td>";
					echo "<td>$osoitelappurow[toim_postino] $osoitelappurow[toim_postitp]</td>";
					echo "<td style='text-align: right;'>" . round($rakir_row['paino'], 2) . "</td>";
					echo "</tr>";
				}
			}

			echo "</table><br>";
		}
	}
}

if ($asiakasid) {

	if (empty($asiakasrow['toim_postitp'])) {
		$asiakasrow['toim_postitp']  = $asiakasrow['postitp'];
		$asiakasrow['toim_postino']  = $asiakasrow['postino'];
		$asiakasrow['toim_osoite']   = $asiakasrow['osoite'];
		$asiakasrow['toim_nimitark'] = $asiakasrow['nimitark'];
		$asiakasrow['toim_nimi']     = $asiakasrow['nimi'];
	}

	if (isset($tnimi) and trim($tnimi) != '') {
		$asiakasrow['toim_postitp']	= $tpostitp;
		$asiakasrow['toim_postino'] = $tpostino;
		$asiakasrow['toim_osoite']  = $tosoite;
		$asiakasrow['toim_nimitark'] = $tnimitark;
		$asiakasrow['toim_nimi']    = $tnimi;
	}

    echo "<form action='' method='post' name='rahtikirja'><table>";
	echo "<tr>
			<th colspan='2' align='left' valign='top'>&nbsp; ".t("Asiakkaan tiedot").":</td></tr>";
	echo "<tr>
			<td valign='top'> ".t("Nimi").": </td>
			<td><input type='text' name='tnimi' size='35' value='$asiakasrow[toim_nimi]'></td></tr>";
	echo "<tr>
			<td></td>
			<td><input type='text' name='tnimitark' size='35' value='$asiakasrow[toim_nimitark]'></td></tr>";
	echo "<tr>
			<td valign='top'>".t("Osoite").": </td>
			<td><input type='text' name='tosoite' size='35' value='$asiakasrow[toim_osoite]'></td></tr>";
	echo "<tr>
			<td valign='top'>".t("Postitp").": </td>
			<td><input type='text' name='tpostino' size='10' value='$asiakasrow[toim_postino]'> <input type='text' name='tpostitp' size='21' value='$asiakasrow[toim_postitp]'></td></tr>";

?>

<tr><th><?php echo t('Varasto') ?></th><td><select name='varasto' onChange='document.rahtikirja.submit();'>
	<?php
		foreach (pupe_varasto_fetch_all() as $key => $val) {

			if ($varasto == $key or !isset($varasto)) {
				$sel = "SELECTED";
				$varasto = $key;
			}
			else {
				$sel = "";
			}

			echo "<option value='$key' $sel>$val</option>";
		}
	?>
	</select></td>
</tr>
<tr>
	<th><?php echo t('Toimitustapa') ?></th>
	<td><select name='toimitustapa' onchange='document.rahtikirja.submit();'>
	    <?php
			$toimitustapa_val = "";
	        $toimtavat = pupe_toimitustapa_fetch_all();

		    foreach ($toimtavat as $toimt): ?>
			    <?php

    			// onko t�m� valittu
    			$sel = '';
    			if ((isset($_POST['toimitustapa']) && $_POST['toimitustapa'] == $toimt['selite'])
    			or (!isset($_POST['toimitustapa']) and $asiakasrow['toimitustapa'] == $toimt['selite'])) {
    				$sel = "selected";
    				$toimitustapa_val = $toimt['selite'];
    			}

			    ?>
			<option <?php echo $sel ?> value="<?php echo $toimt['selite'] ?>"><?php echo t_tunnus_avainsanat($toimt, "selite", "TOIMTAPAKV") ?></option>
		<?php endforeach; ?>
		</select>
		<input type="hidden" name="ytunnus" value="<?php echo $asiakasrow['ytunnus'] ?>">
	</td>
</tr>

<?php

// jos toimitustapaa EI submitattu niin haetaan kannasta
if (! isset($_POST['toimitustapa'])) {
    $merahti = true;
    $sel 	 = '';

    // haetaan toimitustavan tiedot tarkastuksia varten
    $apuqu2 = "SELECT * from toimitustapa where yhtio='$kukarow[yhtio]' and selite='$toimitustapa_val'";
    $meapu2 = pupe_query($apuqu2);
    $meapu2row = mysql_fetch_assoc($meapu2);

    if ($meapu2row["merahti"] == "") {
    	$merahti = false;
    	$sel = "selected";
    }
}
else {
    $sel = '';

    if (isset($_POST['merahti']) && $_POST['merahti'] === '0') {
        $sel = 'selected';
    }
}
?>

<tr><th><?php echo t('Rahti') ?></th><td><select name='merahti' onChange='document.rahtikirja.submit();'>
	<option value='1'>L�hett�j�</option>
	<option <?php echo $sel ?> value='0'>Vastaanottaja</option>
	</select></td>
</tr>
<tr>
	<th><?php echo t('Rahtisopimus') ?></th>
	<?php
	$toimitustapa = $toimitustapa_val;
	if (isset($_POST['toimitustapa'])) {
		$toimitustapa = $_POST['toimitustapa'];
	}
	?>
	<td><input type="text" name="rahtisopimus" value="<?php echo pupe_rahtisopimus($merahti, $toimitustapa, $asiakasrow['ytunnus']) ?>"></td>
</tr>

<?php
	echo "<tr><th>".t('L�hett�j�n viite')."</th><td><input type=hidden name='asiakas' value='$asiakasrow[ytunnus]'><input type='text' name='viitelah'></td></tr>";
	echo "<tr><th>".t('Vastaanottajan viite')."</th><td><input type='text' name='viitevas'></td></tr>";
    echo "<tr><th>".t('Rahtikirja')."</th><td><select name='tulostin'>";
	echo "<option value=''>".t("Ei tulosteta")."</option>";

	// Hetaan varaston tulostimet
	if ($varasto > 0) {
		$query = "	SELECT *
					from varastopaikat
					where yhtio	= '$kukarow[yhtio]'
					and tunnus	= '$varasto'
					order by alkuhyllyalue, alkuhyllynro";
	}
	$kirre = pupe_query($query);

	if (mysql_num_rows($kirre) > 0) {

		$prirow = mysql_fetch_assoc($kirre);

		$sel_lahete[$prirow['printteri1']] = "SELECTED";
		$sel_oslapp[$prirow['printteri3']] = "SELECTED";
	}
	else {
		$sel_lahete[$tulostin] 					= "SELECTED";
		$sel_oslapp[$valittu_oslapp_tulostin]	= "SELECTED";
	}

    $query = "	SELECT *
				from kirjoittimet
				where yhtio = '$kukarow[yhtio]'
				ORDER BY kirjoitin";
	$kires = pupe_query($query);

	while ($kirow = mysql_fetch_assoc($kires)) {
		echo "<option value='$kirow[tunnus]' ".$sel_lahete[$kirow["tunnus"]].">$kirow[kirjoitin]</option>\n";
	}

	echo "</select></td></tr>";

	echo "<tr><th>".t("Osoitelappu")."</th>";
	echo "<td>";

	echo "<select name='valittu_oslapp_tulostin'>";
	echo "<option value=''>".t("Ei tulosteta")."</option>";

	mysql_data_seek($kires, 0);

	while ($kirow = mysql_fetch_assoc($kires)) {
		echo "<option value='$kirow[tunnus]' ".$sel_oslapp[$kirow["tunnus"]].">$kirow[kirjoitin]</option>";
	}

	echo "</select></td></tr>";

	if (!isset($oslappkpl)) $oslappkpl = 1;

	echo "<tr><th>".t("Tulostusm��r�").":</th>";
	echo "<td><input type='text' size='4' name='oslappkpl' value='$oslappkpl'></td>";

	echo "</tr></table><br><br>";

	echo "<table>";

	$query  = "	SELECT *
				FROM pakkaus
				WHERE yhtio = '$kukarow[yhtio]'
				ORDER BY jarjestys";
	$result = pupe_query($query);

	echo "<tr><th>".t("Kollia")."</th><th>".t("Kg")."</th><th>m&sup3;</th><th>m</th><th align='left' colspan='3'>".t("Pakkaus")."</th></tr>";

	$i = 0;
	while ($row = mysql_fetch_assoc($result)) {
    	echo "<tr>
            <td>
                <input type='hidden' name='pakkaus[$i]' value='$row[pakkaus]'>
    		    <input type='hidden' name='pakkauskuvaus[$i]' value='$row[pakkauskuvaus]'>
    	        <input type='text' size='3' value='' name='kollit[$i]'>
    	    </td>
    	    <td><input type='text' size='3' value='' name='kilot[$i]'></td>
    	    <td><input type='text' size='3' value='' name='kuutiot[$i]'></td>
    	    <td><input type='text' size='3' value='' name='lavametri[$i]'></td>
    	    <td>".t_tunnus_avainsanat($row, "pakkaus", "PAKKAUSKV")."</td>
			<td>".t_tunnus_avainsanat($row, "pakkauskuvaus", "PAKKAUSKV")."</td>";

		echo "<td><input type='text' size='10' name='pakkauskuvaustark[$i]'></td>";

    	$i++;
    }

	echo "</table><input type='hidden' name='asiakasid' value='{$asiakasid}'><input type='submit' name='tee' value='".t("Valmis")."'></form>";
}

/**
 *
 * Lis�� uuden rahtikirjan
 *
 * @param array $data Kent�t arrayn keyn�
 *
 * @return void
 *
 */
function pupe_rahtikirja_insert($data) {
	// alustetaan tiedot jotka insertoidaan
	$alustus = array(
		'yhtio'         	=> $GLOBALS['yhtiorow']['yhtio'],
		'merahti'       	=> null,
		'rahtisopimus'  	=> null,
		'pakkaus'       	=> null,
		'pakkauskuvaus' 	=> null,
		'toimitustapa'  	=> null,
		'otsikkonro'    	=> 0,
		'rahtikirjanro' 	=> null,
		'viitelah'      	=> null,
		'viitevas'      	=> null,
        'kilot'         	=> 0,
        'kollit'        	=> 0,
        'kuutiot'       	=> 0,
        'lavametri'     	=> 0,
		'pakkauskuvaustark' => null,
	);

	$data = array_merge($alustus, $data);

	foreach($data as $key => &$val) {
		$val = mysql_real_escape_string($val, $GLOBALS['link']);
	}

	$query = sprintf(
		"INSERT INTO rahtikirjat (yhtio, merahti, rahtisopimus, pakkaus, pakkauskuvaus, toimitustapa, otsikkonro, rahtikirjanro, viitelah, viitevas, kilot, kollit, kuutiot, lavametri, pakkauskuvaustark)
		values('%s')",
		implode("','", array_values($data))
	);
	pupe_query($query);

	return mysql_insert_id();
}

/**
 *
 * undocumented function
 *
 * arrayt:
 * toitarow, lotsikot, pakkaus, kilot, kollit, kuutiot, lavametri, vakit
 * $rakir_row:sta l�ytyy asiakkaan tiedot
 *
 * muuttujat:
 * otunnukset, rahdinmaksaja, pvm, toimitustapa, kolliyht, kilotyht, kuutiotyht, kirjoitin
 * mehto sis�lt�� maksuehdon tiedot
 * jv tapauksissa on my�s yhteensa, summa, jvhinta, lasno ja viite muuttujat
 *
 * @return void
 *
 */
function pupe_rahtikirja_fetch($otsikkonro) {
    $query = sprintf("SELECT * from rahtikirjat where otsikkonro=%d", (int) $otsikkonro);
    $result = mysql_query($query);

	$data = array(
		'lotsikot'  => array(),
		'pakkaus'   => array(),
		'kilot'     => array(),
		'kollit'    => array(),
		'kuutiot'   => array(),
		'lavametri' => array(),
	);

	$i = 0;
    while ($rahtikirja = mysql_fetch_assoc($result)) {

		if ($i == 0) {
			$data = array_merge($rahtikirja, $data);
		}

		// asetetaan rivitiedot
		$data['lotsikot'][$i]  = abs($rahtikirja['rahtikirjanro']);
		$data['pakkaus'][$i]   = $rahtikirja['pakkaus'];
		$data['kilot'][$i]     = $rahtikirja['kilot'];
		$data['kollit'][$i]    = $rahtikirja['kollit'];
		$data['kuutiot'][$i]   = $rahtikirja['kuutiot'];
		$data['lavametri'][$i] = $rahtikirja['lavametri'];

		// lis�t��n totaaleja
		$data['kilotyht']     += $rahtikirja['kilot'];
		$data['kolliyht']     += $rahtikirja['kollit'];
		$data['kuutiotyht']   += $rahtikirja['kuutiot'];
		$data['lavametriyht'] += $rahtikirja['lavametri'];

		$i++;
    }
	return $data;
}

/**
 *
 * Hakee kaikki varastopaikat yhtiolle
 *
 * @return array tunnus => nimitys
 *
 */
function pupe_varasto_fetch_all() {
	$query = sprintf("SELECT tunnus, nimitys
				FROM varastopaikat
				WHERE yhtio = '%s'
				ORDER BY nimitys", mysql_real_escape_string($GLOBALS['kukarow']['yhtio']));

	$result = pupe_query($query);

	$varastot = array();
	while ($row = mysql_fetch_assoc($result)) {
		$varastot[$row['tunnus']] = $row['nimitys'];
	}

	return $varastot;
}

/**
 *
 * undocumented function
 *
 * @return void
 *
 */
function pupe_toimitustapa_fetch_all() {
	// haetaan kaikki toimitustavat
	$query  = "SELECT * FROM toimitustapa WHERE yhtio='{$GLOBALS['kukarow']['yhtio']}' order by jarjestys,selite";
	$result = pupe_query($query);

	$data = array();

	while ($row = mysql_fetch_assoc($result)) {
		$data[] = $row;
	}

	return $data;
}

/**
 *
 * undocumented function
 *
 * @return void
 *
 */
function pupe_rahtisopimus($merahti, $toimitustapa, $ytunnus = null) {
	if ($merahti) {
		$query = "SELECT merahti,sopimusnro from toimitustapa where selite='{$toimitustapa}' and yhtio='{$GLOBALS['kukarow']['yhtio']}'";
		$res = pupe_query($query);
		$merahti = mysql_fetch_assoc($res);

		if ($merahti['merahti'] == 'K') {
			return $merahti['sopimusnro'];
		}
	}


	// kokeillaan l�ytyyk� rahtisopimusta asiakkaalle sek� toimitustavalle
	$query = "SELECT * from rahtisopimukset where toimitustapa='$toimitustapa' and ytunnus='$ytunnus' and yhtio='{$GLOBALS['kukarow']['yhtio']}'";
	$res = pupe_query($query);

	if (mysql_num_rows($res) === 1) {
		$sopimus = mysql_fetch_assoc($res);
		return $sopimus['rahtisopimus'];
	}

	return false;
}

require ("inc/footer.inc");

?>