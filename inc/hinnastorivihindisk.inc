<?php

function hinnastorivi($tuote) {
    global $kukarow;
    
    //haetaan aleryhmän tunnus
	$query = "	SELECT b.tunnus, b.ryhma
				FROM tuote a
				LEFT JOIN perusalennus b use index (yhtio_ryhma) ON a.yhtio = b.yhtio and a.aleryhma = b.ryhma
				WHERE a.tuoteno = '{$tuote['tuoteno']}' and a.yhtio = '{$kukarow['yhtio']}'";
	$aleresult = mysql_query($query) or pupe_error($query);
	$alerow = mysql_fetch_array($aleresult);

	$aletun = $alerow['tunnus'];
	$tunpit = strlen($aletun);

	if ($aletun == '') {
		$aletun = '000';
	}
	else if ($tunpit == '2'){
		$aletun = '0'.$aletun;
	}
	else if ($tunpit == '1'){
		$aletun = '00'.$aletun;
	}
	//echo "$aletun <br>";

	//korvaavat tuotteet
	$koti 		= '';
	$edellinen 	= '';
	$seuraava 	= '';
	$korvaavat	= array();

	if ($tuote["id"] > 0) {
		$query = "	SELECT * 
					FROM korvaavat use index (yhtio_id) 
					WHERE id='{$tuote['id']}' 
					AND yhtio='{$kukarow['yhtio']}' 
					ORDER BY jarjestys, tuoteno";
		$korvaresult = mysql_query($query) or pupe_error($query);

		$lask = 0;
		while ($korvarow = mysql_fetch_array($korvaresult)){
			$korvaavat[$lask] = $korvarow["tuoteno"];
			if ($korvarow["tuoteno"] == $tuote["tuoteno"]) {
				$koti = $lask;
			}
			$lask++;
		}
		//tässä listan viimeinen indeksi
		$lask--;

		//edellinen ja seuraava korvaava
		if ($koti == 0) {
			$edellinen	= '';
			$seuraava	= $korvaavat[$koti+1];
		}
		elseif($koti == $lask) {
			$edellinen	= $korvaavat[$koti-1];
			$seuraava	= '';
		}
		else{
			$edellinen	= $korvaavat[$koti-1];
			$seuraava	= $korvaavat[$koti+1];
		}
	}
	
    // tehdään tietuetta
	$out  = sprintf('%-20.20s'	,$tuote["tuoteno"]);
	$out .= sprintf('%-60.60s'	,asana('nimitys_',$tuote['tuoteno'],$tuote['nimitys']));
	$out .= sprintf('%-10.10s'	,$tuote["yksikko"]);
	$out .= sprintf('%03d'		,$aletun);
	$out .= sprintf('%08d'		,str_replace('.','',$tuote["myyntihinta"]));
	$out .= sprintf('%08d'		,str_replace('.','',$tuote["myyntihinta"]));
	$out .= sprintf('%-20.20s'	,$seuraava);
	$out .= sprintf('%-20.20s'	,$edellinen);
	$out .= sprintf('%-3.3s'	,'');
	$out .= "\r\n";
	
    return $out;
}
?>