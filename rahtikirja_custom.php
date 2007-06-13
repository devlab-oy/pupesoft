<?php
require ("inc/parametrit.inc");
echo "<font class='head'>".t("Tyhj‰ rahtikirja").":</font><hr><br>";

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
	
	// k‰yd‰‰n jokainen pakkaustyypii l‰pi (yksi rivi rahtikirjassa)
	for ($i = 0; $i < count($_POST['kilot']); $i++) {
		
		
		if((isset($_POST['kilot'][$i]) && is_numeric($_POST['kilot'][$i]))
		|| (isset($_POST['kollit'][$i]) && is_numeric($_POST['kollit'][$i]))
		|| (isset($_POST['kuutiot'][$i]) && is_numeric($_POST['kuutiot'][$i]))
		|| (isset($_POST['lavametri'][$i]) && is_numeric($_POST['lavametri'][$i]))) {
			
			$count++;
			
			// jotain syˆtettiin
			$data = array(
				'pakkaus'           => strip_tags($_POST['pakkaus'][$i]),
				'pakkauskuvaus'     => strip_tags($_POST['pakkauskuvaus'][$i]),
				'kilot'             => (int) $_POST['kilot'][$i],
	        	'kollit'            => (int) $_POST['kollit'][$i],
	        	'kuutiot'           => (int) $_POST['kuutiot'][$i],
	        	'lavametri'         => (int) $_POST['lavametri'][$i],
			);
			
			$data = array_merge($clean, $data);

			if ($count === 1) {
				// eka rivi, insertoidaan ja otetaan tunnus
				$otsikkonro = pupe_rahtikirja_insert($data);
			} else {
				$data['otsikkonro'] = $otsikkonro;
				$data['rahtikirjanro'] = $otsikkonro;
				pupe_rahtikirja_insert($data);
			}
		}
	}
	
	// korjataan ensimm‰inen rivi jossa on v‰‰r‰ otsikkonro sek‰ rahtikirjanro
	$query = sprintf(
		"UPDATE rahtikirjat set otsikkonro = '%s', rahtikirjanro = '%s' where tunnus = '%s'",
		(int) $otsikkonro,
		(int) $otsikkonro,
		(int) $otsikkonro
	);
	
	mysql_query($query) or pupe_error($query);
	
	// --------------------------------------------------------
	// 
	// TULOSTUS!!!
	
	$data = pupe_rahtikirja_fetch($otsikkonro);
	
	$GLOBALS['lotsikot']  = $data['lotsikot'];
	$GLOBALS['pakkaus']   = $data['pakkaus'];
	$GLOBALS['kilot']     = $data['kilot'];
	$GLOBALS['kollit']    = $data['kollit'];
	$GLOBALS['kuutiot']   = $data['kuutiot'];
	$GLOBALS['lavametri'] = $data['lavametri'];
    
    $GLOBALS['kilotyht']  = $data['kilotyht'];
    $GLOBALS['kollityht']  = $data['kolliyht'];
	
	// pistet‰‰n kaikki globaaleiksi
	$GLOBALS = array_merge($GLOBALS, $data);
	
	// kerrotaan ett‰ t‰m‰ on custom rahtikirja == ei haeta laskulta mit‰‰n
	$GLOBALS['tyhja'] = 1;
	
	if ($data['merahti'] == 'K') {
		$rahdinmaksaja = 'L‰hett‰j‰';
		$toitarow = array(
		    'sopimusnro'       => $data['rahtisopimus'],
		    'selite'           => $data['toimitustapa'],
		    'rahdinkuljettaja' => '',
		);
	} else {
		$rahdinmaksaja = 'Vastaanottaja';
		$rahsoprow = array('rahtisopimus' => $data['rahtisopimus']);

		$toitarow = array(
		    'selite'           => $data['toimitustapa'],
		    'rahdinkuljettaja' => '',
		);
		
	}
	
	$query = "SELECT komento from kirjoittimet where tunnus=". (int) $_POST['tulostin']
	        . " AND yhtio='{$kukarow['yhtio']}'";
	$res = mysql_query($query) or pupe_error($query);
	
	$k = mysql_fetch_array($res);
	
    $kirjoitin = $k['komento'];
	include 'tilauskasittely/rahtikirja_pdf.inc';
	
	$asiakasid = false;
	echo "<p>Tulostetaan rahtikirja.</p>";
}

if (isset($_POST['ytunnus']) && $asiakasid !== false) {
    require 'inc/asiakashaku.inc';
} else {
?>
<table>
    <tr><th><?php echo t('Hae asiakas') ?></th><td><form action='' method='POST' name='haku'><input type="text" name="ytunnus" value=""></td>
        <td><input type="submit" value="<?php echo t('Etsi') ?>"></td>
    </tr>
    </form>
</table>

<?php

	$formi = "haku";
	$kentta = "ytunnus";

}

if ($asiakasid) {
	
	if (empty($asiakasrow['toim_postitp'])) {
		$asiakasrow['toim_postitp'] = $asiakasrow['postitp'];
		$asiakasrow['toim_postino'] = $asiakasrow['postino'];
		$asiakasrow['toim_osoite']  = $asiakasrow['osoite'];
		$asiakasrow['toim_nimi']    = $asiakasrow['nimi'];
	}
	
    echo "<form action='' method='post' name='rahtikirja'><table>";
	echo "<tr><th align='left'>".t("Asiakas")."</th><td>{$asiakasrow['nimi']} {$asiakasrow['nimitark']}<br>{$asiakasrow['osoite']}<br>{$asiakasrow['postino']} {$asiakasrow['postitp']}</td>";
	echo "<th>".t("Toimitusosoite")."</th><td>{$asiakasrow['toim_nimi']} {$asiakasrow['toim_nimitark']}<br />
		{$asiakasrow['toim_osoite']}<br />
		{$asiakasrow['toim_postino']} {$asiakasrow['toim_postitp']}
		</td>
	</tr>";
?>

</tr>
<tr><th><?php echo t('Varasto') ?></th><td><select name='varasto'>
	<?php foreach (pupe_varasto_fetch_all() as $key => $val): ?>
		<option value="<?php echo $key ?>"><?php echo $val ?></option>
	<?php endforeach; ?>
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
			
			// onko t‰m‰ valittu
			$sel = '';
			if ((isset($_POST['toimitustapa']) && $_POST['toimitustapa'] == $toimt['selite']) or (!isset($_POST['toimitustapa']) and $asiakasrow['toimitustapa'] == $toimt['selite'])) {
				$sel = "selected";
				$toimitustapa_val = $toimt['selite'];
			}
			 
			?>
			<option <?php echo $sel ?> value="<?php echo $toimt['selite'] ?>"><?php echo asana('TOIMITUSTAPA_', $toimt['selite']) ?></option>
		<?php endforeach; ?>
		</select>
		<input type="hidden" name="ytunnus" value="<?php echo $asiakasrow['ytunnus'] ?>">
	</td>
</tr>

<?php

$merahti = true;
$sel 	 = '';

// haetaan toimitustavan tiedot tarkastuksia varten
$apuqu2 = "select * from toimitustapa where yhtio='$kukarow[yhtio]' and selite='$toimitustapa_val'";
$meapu2 = mysql_query($apuqu2) or pupe_error($apuqu2);
$meapu2row = mysql_fetch_array($meapu2);

if ($meapu2row["merahti"] == "") {
	$merahti = false;
	$sel = "selected";
}
?>

<tr><th><?php echo t('Rahti') ?></th><td><select name='merahti'>
	<option value='1'>L‰hett‰j‰</option>
	<option <?php echo $sel ?> value='0'>Vastaanottaja</option>
	</select></td>
</tr>
<tr>
	<th><?php echo t('Rahtisopimus') ?></th>
	<?php
	$toimitustapa = $toimtavat[0]['selite'];
	if (isset($_POST['toimitustapa'])) {
		$toimitustapa = $_POST['toimitustapa'];
	}
	?>
	<td><input type="text" name="rahtisopimus" value="<?php echo pupe_rahtisopimus($merahti, $toimitustapa, $asiakasrow['ytunnus']) ?>"></td>
</tr>

<?php
	echo "<tr><th>". t('L‰hett‰j‰n viite') . "</th><td><input type=hidden name='asiakas' value='{$asiakasrow['ytunnus']}'><input type='text' name='viitelah'></td></tr>";
	echo "<tr><th>Vastaanottajan viite</th><td><input type='text' name='viitevas'></td></tr>";
    echo "<tr><th>" . t('Tulostin') . "</th><td><select name='tulostin'>";
    
    $query = "	select *
				from kirjoittimet
				where yhtio='$kukarow[yhtio]'
				ORDER BY kirjoitin";
	$kires = mysql_query($query) or pupe_error($query);

	while ($kirow = mysql_fetch_array($kires)) {
		if ($kirow["tunnus"] == $_POST['tulostin']) {
			$sel = "SELECTED";
		}
		else {
			$sel = "";
		}

		echo "<option value='{$kirow['tunnus']}' $sel>{$kirow['kirjoitin']}</option>\n";
	}

	echo "</select></td></tr></table>";
	
	echo "<table>";
	
	$query  = "	SELECT avainsana.selite, ".avain('select')."
				FROM avainsana
				".avain('join','PAKKAUS_')."
				WHERE avainsana.yhtio	= '". mysql_real_escape_string($kukarow['yhtio']) . "'
				and avainsana.laji	= 'pakkaus'
				order by avainsana.jarjestys";
	
	$result = mysql_query($query) or pupe_error($query);

	echo "<tr><th>".t("Kollia")."</th><th>".t("Kg")."</th><th>m&sup3;</th><th>m</th><th align='left' colspan='3'>".t("Pakkaus")."</th></tr>";

	$i = 0;
	while ($row = mysql_fetch_array($result)) {
    	echo "<tr>
            <td>
                <input type='hidden' name='pakkaus[$i]' value='{$row['selite']}'>
    		    <input type='hidden' name='pakkauskuvaus[$i]' value='{$row['selitetark']}'>
    	        <input type='text' size='3' value='' name='kollit[$i]'>
    	    </td>
    	    <td><input type='text' size='3' value='' name='kilot[$i]'></td>
    	    <td><input type='text' size='3' value='' name='kuutiot[$i]'></td>
    	    <td><input type='text' size='3' value='' name='lavametri[$i]'></td>
    	    <td>{$row['selite']}</td>
			<td>{$row['selitetark']}</td>";
        
    	$i++;
    }
    
	echo "</table><input type='hidden' name='asiakasid' value='{$asiakasid}'><input type='submit' name='tee' value='Valmis'></form>";
	
}


/**
 * 
 * Lis‰‰ uuden rahtikirjan
 * 
 * @param array $data Kent‰t arrayn keyn‰
 * 
 * @return void
 * 
 */
function pupe_rahtikirja_insert($data)
{
	// alustetaan tiedot jotka insertoidaan
	$alustus = array(
		'yhtio'         => $GLOBALS['yhtiorow']['yhtio'],
		'merahti'       => null,
		'rahtisopimus'  => null,
		'pakkaus'       => null,
		'pakkauskuvaus' => null,
		'toimitustapa'  => null,
		'otsikkonro'    => 0,
		'rahtikirjanro' => null,
		'viitelah'      => null,
		'viitevas'      => null,
        'kilot'         => 0,
        'kollit'        => 0,
        'kuutiot'       => 0,
        'lavametri'     => 0,
	);
	
	$data = array_merge($alustus, $data);
	
	foreach($data as $key => &$val) {
		$val = mysql_real_escape_string($val, $GLOBALS['link']);
	}
	
	$query = sprintf(
		"INSERT INTO rahtikirjat (yhtio, merahti, rahtisopimus, pakkaus, pakkauskuvaus, toimitustapa, otsikkonro, rahtikirjanro, viitelah, viitevas, kilot, kollit, kuutiot, lavametri)
		values('%s')",
		implode("','", array_values($data))
	);
	mysql_query($query) or pupe_error($query);
	
	return mysql_insert_id();
}

/**
 * 
 * undocumented function
 * 
 * arrayt:
 * toitarow, lotsikot, pakkaus, kilot, kollit, kuutiot, lavametri, vakit
 * $rakir_row:sta lˆytyy asiakkaan tiedot
 * 
 * muuttujat:
 * otunnukset, rahdinmaksaja, pvm, toimitustapa, kolliyht, kilotyht, kuutiotyht, kirjoitin
 * mehto sis‰lt‰‰ maksuehdon tiedot
 * jv tapauksissa on myˆs yhteensa, summa, jvhinta, lasno ja viite muuttujat
 * 
 * @return void
 * 
 */
function pupe_rahtikirja_fetch($otsikkonro)
{
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
		$data['lotsikot'][$i]  = $rahtikirja['rahtikirjanro'];
		$data['pakkaus'][$i]   = $rahtikirja['pakkaus'];
		$data['kilot'][$i]     = $rahtikirja['kilot'];
		$data['kollit'][$i]    = $rahtikirja['kollit'];
		$data['kuutiot'][$i]   = $rahtikirja['kuutiot'];
		$data['lavametri'][$i] = $rahtikirja['lavametri'];
		
		// lis‰t‰‰n totaaleja
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
function pupe_varasto_fetch_all()
{
	$query = sprintf("SELECT tunnus, nimitys
				FROM varastopaikat
				WHERE yhtio = '%s'
				ORDER BY nimitys", mysql_real_escape_string($GLOBALS['kukarow']['yhtio']));
	
	$result = mysql_query($query) or pupe_error($query);
	
	$varastot = array();
	while ($row = mysql_fetch_array($result)) {
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
function pupe_toimitustapa_fetch_all()
{
	// haetaan kaikki toimitustavat
	$query  = "SELECT * FROM toimitustapa WHERE yhtio='{$GLOBALS['kukarow']['yhtio']}' order by jarjestys,selite";
	$result = mysql_query($query) or pupe_error($query);
	
	$data = array();
	
	while ($row = mysql_fetch_array($result)) {
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
function pupe_rahtisopimus($merahti, $toimitustapa, $ytunnus = null)
{
	if ($merahti) {
		$query = "SELECT merahti,sopimusnro from toimitustapa where selite='{$toimitustapa}' and yhtio='{$GLOBALS['kukarow']['yhtio']}'";
		$res = mysql_query($query) or pupe_error($query);

		$merahti = mysql_fetch_array($res);
		if ($merahti['merahti'] == 'K') {
			return $merahti['sopimusnro'];
		}
	}
	
	
	// kokeillaan lˆytyykˆ rahtisopimusta asiakkaalle sek‰ toimitustavalle
	$query = "select * from rahtisopimukset where toimitustapa='$toimitustapa' and ytunnus='$ytunnus' and yhtio='{$GLOBALS['kukarow']['yhtio']}'";
	$res = mysql_query($query) or pupe_error($query);
	
	if (mysql_num_rows($res) === 1) {
		$sopimus = mysql_fetch_array($res);
		return $sopimus['rahtisopimus'];
	}
	
	return false;
}

include 'inc/footer.inc';
?>