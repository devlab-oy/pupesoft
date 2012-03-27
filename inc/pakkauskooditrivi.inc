<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if (mysql_field_name($result, $i) == "tunnus" or
	    mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "pakkaus") {
		if ($lukitse_avaimeen != "") {
			$tyyppi = 4;
			if ($lukitse_avaimeen != "") {
				$ulos = "<input type='hidden' name='{$nimi}' value='{$lukitse_avaimeen}'>";
				$jatko = 0;
			}
		}
		else {
			$tyyppi = 2;
		}
	}

	if (mysql_field_name($result, $i) == "rahdinkuljettaja") {

		$sel = '';
		if ($trow[$i] != '') $sel[$trow[$i]] = 'SELECTED';

		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value=''>".t("Ei valintaa")."</option>";
		$ulos .= "<option value='ATSC' {$sel['ATSC']}>ATS Cargo</option>";
		$ulos .= "<option value='BEST' {$sel['BEST']}>Best Transport</option>";
		$ulos .= "<option value='PNL' {$sel['PNL']}>Bring (fd Pan Nordic Logistics)</option>";
		$ulos .= "<option value='BOX' {$sel['BOX']}>Bring Express</option>";
		$ulos .= "<option value='FSD' {$sel['FSD']}>Bring Frigoscandia</option>";
		$ulos .= "<option value='BL'{$sel['BL']}>Bring Logistics</option>";
		$ulos .= "<option value='PNLNO' {$sel['PNLNO']}>Bring Parcels Norge (international)</option>";
		$ulos .= "<option value='BP'{$sel['BP']}>Bring Parcels</option>";
		$ulos .= "<option value='CG'{$sel['CG']}>Bussgods</option>";
		$ulos .= "<option value='CS' {$sel['CS']}>Consignment</option>";
		$ulos .= "<option value='DACHSER' {$sel['DACHSER']}>Dachser</option>";
		$ulos .= "<option value='DFM' {$sel['DFM']}>Danske Fragtmaend</option>";
		$ulos .= "<option value='DANX' {$sel['DANX']}>DANX</option>";
		$ulos .= "<option value='SBTLNO' {$sel['SBTLNO']}>DB Schenker Norge</option>";
		$ulos .= "<option value='SBTL' {$sel['SBTL']}>DB Schenker</option>";
		$ulos .= "<option value='SBTLFI' {$sel['SBTLFI']}>DB Schenker Finland</option>";
		$ulos .= "<option value='DLG' {$sel['DLG']}>Delego</option>";
		$ulos .= "<option value='ASG' {$sel['ASG']}>DHL Day Definite</option>";
		$ulos .= "<option value='DGF' {$sel['DGF']}>DHL Global Forwarding</option>";
		$ulos .= "<option value='DHLAIR' {$sel['DHLAIR']}>DHL Time Definite</option>";
		$ulos .= "<option value='DSV' {$sel['DSV']}>DSV</option>";
		$ulos .= "<option value='GLS' {$sel['GLS']}>General Logistics Systems</option>";
		$ulos .= "<option value='HIT' {$sel['HIT']}>HIT</option>";
		$ulos .= "<option value='POSTI' {$sel['POSTI']}>Itella (Posti)</option>";
		$ulos .= "<option value='JB' {$sel['JB']}>Jetpak Borg</option>";
		$ulos .= "<option value='KK' {$sel['KK']}>Kaukokiito</option>";
		$ulos .= "<option value='KL' {$sel['KL']}>Kiitolinja</option>";
		$ulos .= "<option value='MH' {$sel['MH']}>Matkahuolto</option>";
		$ulos .= "<option value='MTD' {$sel['MTD']}>Morgontidningarnas Tidningsdistribution</option>";
		$ulos .= "<option value='FREE' {$sel['FREE']}>Neutral</option>";
		$ulos .= "<option value='ONROAD' {$sel['ONROAD']}>OnRoad</option>";
		$ulos .= "<option value='PLN' {$sel['PLN']}>Parceline</option>";
		$ulos .= "<option value='PDK' {$sel['PDK']}>Post Danmark</option>";
		$ulos .= "<option value='PLAB' {$sel['PLAB']}>Posten Logistik AB</option>";
		$ulos .= "<option value='PNO' {$sel['PNO']}>Posten Norge</option>";
		$ulos .= "<option value='PAX' {$sel['PAX']}>Posten Ålandiin</option>";
		$ulos .= "<option value='SF' {$sel['SF']}>Småfrakt</option>";
		$ulos .= "<option value='TM' {$sel['TM']}>time:matters</option>";
		$ulos .= "<option value='TNT' {$sel['TNT']}>TNT</option>";
		$ulos .= "<option value='DTPG' {$sel['DTPG']}>Tollpost-Globe AS</option>";
		$ulos .= "<option value='TP' {$sel['TP']}>Transpoint</option>";
		$ulos .= "<option value='TG' {$sel['TG']}>Transportgruppen</option>";

		$uf_rahkul_avainsanat_result = t_avainsana('UNIFAUN_RAHKULJ', '', " and selite != '' ");

		while ($uf_rahkulj_avainsanat_row = mysql_fetch_assoc($uf_rahkul_avainsanat_result)) {
			$ulos .= "<option value='{$uf_rahkulj_avainsanat_row['selite']}' {$sel[$uf_rahkulj_avainsanat_row['selite']]}>{$uf_rahkulj_avainsanat_row['selitetark']}</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}
