<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if (mysql_field_name($result, $i) == "tunnus" or
	    mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "nouto") {

		$sel='';
		if ($trow[$i]!='') $sel = 'SELECTED';

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value=''>".t("Tilaukset toimitetaan asiakkaalle")."</option>";
		$ulos .= "<option value='o' $sel>".t("Asiakas noutaa tilaukset")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "lauantai") {

		$sel='';
		if ($trow[$i]!='') $sel = 'SELECTED';

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value=''>".t("Ei lauantaijakelua")."</option>";
		$ulos .= "<option value='o' $sel>".t("Itella lisäpalvelu: Lauantaijakelu")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kuljyksikko") {

		$sel='';
		if ($trow[$i]!='') $sel = 'SELECTED';

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value=''>".t("Ei kuljetusyksikkökuljetusta")."</option>";
		$ulos .= "<option value='o' $sel>".t("Itella lisäpalvelu: Kuljetusyksikkökuljetus")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "virallinen_selite") {

		$sel = '';
		if ($trow[$i] != '') $sel[$trow[$i]] = 'SELECTED';

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value=''>".t("Ei valintaa")."</option>";

		$ulos .= "<optgroup label='Pupesoft / Itella'>";
		$ulos .= "<option value='Express City 00' {$sel["Express City 00"]}>Itella Express City 00</option>";
		$ulos .= "<option value='Express Morning 9' {$sel["Express Morning 9"]}>Itella Express Morning 9</option>";
		$ulos .= "<option value='Express Business Day 14' {$sel["Express Business Day 14"]}>Itella Express Business Day 14</option>";
		$ulos .= "<option value='Express Point 00/16' {$sel["Express Point 00/16"]}>Itella Express Point 00/16</option>";
		$ulos .= "<option value='Express Flex 21' {$sel["Express Flex 21"]}>Itella Express Flex 21</option>";
		$ulos .= "<option value='Economy 16' {$sel["Economy 16"]}>Itella Economy 16</option>";
		$ulos .= "<option value='Customer Return' {$sel["Customer Return"]}>Itella Customer Return</option>";
		$ulos .= "<option value='Undelivered Shipment' {$sel["Undelivered Shipment"]}>Itella Undelivered Shipment</option>";
		$ulos .= "<option value='Lisäarvokuljetus' {$sel["Lisäarvokuljetus"]}>Itella Lisäarvokuljetus</option>";
		$ulos .= "<option value='Priority Ulkomaa' {$sel["Priority Ulkomaa"]}>Itella Priority Ulkomaan lähetys</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / Itella'>";
		$ulos .= "<option value='IT09' {$sel["IT09"]}>Itella Express Morning 09</option>";
		$ulos .= "<option value='IT14' {$sel["IT14"]}>Itella Express Business Day 14</option>";
		$ulos .= "<option value='IT14I' {$sel["IT14I"]}>Itella Express Business Day parcel (int.)</option>";
		$ulos .= "<option value='IT16' {$sel["IT16"]}>Economy 16</option>";
		$ulos .= "<option value='IT21' {$sel["IT21"]}>Express Flex 21</option>";
		$ulos .= "<option value='ITAP' {$sel["ITAP"]}>Asiakaspalautus</option>";
		$ulos .= "<option value='ITEXPC' {$sel["ITEXPC"]}>Express City 00</option>";
		$ulos .= "<option value='ITKY14' {$sel["ITKY14"]}>Business Day 14 kuljetusyksikkö</option>";
		$ulos .= "<option value='ITKY14I' {$sel["ITKY14I"]}>Itella Express Business Day pallet (int.)</option>";
		$ulos .= "<option value='ITKY21' {$sel["ITKY21"]}>Express Flex kuljetusyksikkö 21</option>";
		$ulos .= "<option value='ITKYEXPC' {$sel["ITKYEXPC"]}>Express City 00 kuljetusyksikkö</option>";
		$ulos .= "<option value='Priority Ulkomaa' {$sel["Priority"]}>Itella Priority Ulkomaan lähetys</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / Posten Logistik AB'>";
		$ulos .= "<option value='P13' {$sel["P13"]}>Postorderpaket</option>";
		$ulos .= "<option value='P14' {$sel["P14"]}>DPD Företagspaket 12.00</option>";
		$ulos .= "<option value='P15' {$sel["P15"]}>DPD Företagspaket 16.00</option>";
		$ulos .= "<option value='P19' {$sel["P19"]}>MyPack</option>";
		$ulos .= "<option value='P24' {$sel["P24"]}>MyPack Return</option>";
		$ulos .= "<option value='P25' {$sel["P25"]}>Postpaket Inrikes</option>";
		$ulos .= "<option value='P26' {$sel["P26"]}>Paket utan Kvittens</option>";
		$ulos .= "<option value='P29' {$sel["P29"]}>Kundretur</option>";
		$ulos .= "<option value='P31' {$sel["P31"]}>DPD Företagspaket 09.00</option>";
		$ulos .= "<option value='P32' {$sel["P32"]}>Hempaket</option>";
		$ulos .= "<option value='P42' {$sel["P42"]}>Expresspaket</option>";
		$ulos .= "<option value='P44' {$sel["P44"]}>Express - P.EX. Distribution</option>";
		$ulos .= "<option value='P50' {$sel["P50"]}>Express - Urntransport</option>";
		$ulos .= "<option value='P51' {$sel["P51"]}>Comeback</option>";
		$ulos .= "<option value='P52' {$sel["P52"]}>PALL.ETT</option>";
		$ulos .= "<option value='P52S' {$sel["P52S"]}>PALL.ETT Split Shipment</option>";
		$ulos .= "<option value='P53' {$sel["P53"]}>PALL.ETT Special</option>";
		$ulos .= "<option value='P54' {$sel["P54"]}>PALL.ETT+</option>";
		$ulos .= "<option value='P55' {$sel["P55"]}>DPD Företagspaket Lokalt</option>";
		$ulos .= "<option value='P57' {$sel["P57"]}>DPD Företagspaket Special</option>";
		$ulos .= "<option value='P59' {$sel["P59"]}>Posten Retail Delivery</option>";
		$ulos .= "<option value='P91' {$sel["P91"]}>Postpaket Utrikes</option>";
		$ulos .= "<option value='DPD' {$sel["DPD"]}>DPD Utrikes</option>";
		$ulos .= "<option value='EMSD' {$sel["EMSD"]}>Express Global (brev)</option>";
		$ulos .= "<option value='EMSP' {$sel["EMSP"]}>Express Global (paket)</option>";
		$ulos .= "<option value='BAGR' {$sel["BAGR"]}>Avtalsbrev</option>";
		$ulos .= "<option value='PAF' {$sel["PAF"]}>Brevpostförskott</option>";
		$ulos .= "<option value='BEXPD' {$sel["BEXPD"]}>Brev - Express (inrikes)</option>";
		$ulos .= "<option value='BEXPI' {$sel["BEXPI"]}>Brev - Express (utrikes)</option>";
		$ulos .= "<option value='BREV' {$sel["BREV"]}>Brev - Normal</option>";
		$ulos .= "<option value='BREVNO' {$sel["BREVNO"]}>Brev Norge - Normal</option>";
		$ulos .= "<option value='BREFI' {$sel["BREFI"]}>Brev Finland - Normal</option>";
		$ulos .= "<option value='BREKD' {$sel["BREKD"]}>Brev - Rekommenderat (inrikes)</option>";
		$ulos .= "<option value='BREKI' {$sel["BREKI"]}>Brev - Rekommenderat (utrikes)</option>";
		$ulos .= "<option value='PAG' {$sel["PAG"]}>Brev - Varubrev Klimatekonomisk</option>";
		$ulos .= "<option value='PAH' {$sel["PAH"]}>Brev - Varubrev Ekonomi</option>";
		$ulos .= "<option value='PAE' {$sel["PAE"]}>Brev - Varubrev 1:a klass</option>";
		$ulos .= "<option value='BVAL' {$sel["BVAL"]}>Brev - Värde</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / Bring Parcels AB (PNL) (SE and DK)'>";
		$ulos .= "<option value='PNL330' {$sel['PNL330']}>Bring CarryOn Business</option>";
		$ulos .= "<option value='PNL331' {$sel['PNL331']}>Bring CarryOn Business Return</option>";
		$ulos .= "<option value='PNL332' {$sel['PNL332']}>Bring CarryOn Business BulkSplit</option>";
		$ulos .= "<option value='PNL333' {$sel['PNL333']}>Bring CarryOn Business BulkReturn</option>";
		$ulos .= "<option value='PNL334' {$sel['PNL334']}>Bring CarryOn Business BulkSplit 09.00</option>";
		$ulos .= "<option value='PNL335' {$sel['PNL335']}>Bring CarryOn Business 09.00</option>";
		$ulos .= "<option value='PNL336' {$sel['PNL336']}>Bring CarryOn Business Pallet</option>";
		$ulos .= "<option value='PNL339' {$sel['PNL339']}>Bring CarryOn Business Pallet 09.00</option>";
		$ulos .= "<option value='PNL340' {$sel['PNL340']}>Bring CarryOn HomeShopping</option>";
		$ulos .= "<option value='PNL341' {$sel['PNL341']}>Bring CarryOn HomeShopping Return</option>";
		$ulos .= "<option value='PNL342' {$sel['PNL342']}>Bring CarryOn HomeShopping BulkSplit</option>";
		$ulos .= "<option value='PNL343' {$sel['PNL343']}>Bring CarryOn HomeShopping BulkReturn</option>";
		$ulos .= "<option value='PNL345' {$sel['PNL345']}>Bring CarryOn HomeShopping Bulksplit Mini</option>";
		$ulos .= "<option value='PNL359' {$sel['PNL359']}>Bring CarryOn Budget</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / Bring Parcels Norge (BP) (domestic)'>";
		$ulos .= "<option value='BPBDD' {$sel['BPBDD']}>Bedriftspakke Dør - Dør</option>";
		$ulos .= "<option value='BPBE07' {$sel['BPBE07']}>Bedriftspakke Ekspress over Natt - 07.00</option>";
		$ulos .= "<option value='BPBE09' {$sel['BPBE09']}>Bedriftspakke Ekspress over Natt - 09.00</option>";
		$ulos .= "<option value='BPPL' {$sel['BPPL']}>Postens Pallelaster</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / Bring Parcels Norge (PNLNO) (international)'>";
		$ulos .= "<option value='PNLNO330' {$sel['PNLNO330']}>Bring (NO) CarryOn Business</option>";
		$ulos .= "<option value='PNLNO331' {$sel['PNLNO331']}>Bring (NO) CarryOn Business Return</option>";
		$ulos .= "<option value='PNLNO332' {$sel['PNLNO332']}>Bring (NO) CarryOn Business BulkSplit</option>";
		$ulos .= "<option value='PNLNO333' {$sel['PNLNO333']}>Bring (NO) CarryOn Business BulkReturn</option>";
		$ulos .= "<option value='PNLNO334' {$sel['PNLNO334']}>Bring (NO) CarryOn Business BulkSplit 09.00</option>";
		$ulos .= "<option value='PNLNO336' {$sel['PNLNO336']}>Bring (NO) CarryOn Business Pallet</option>";
		$ulos .= "<option value='PNLNO340' {$sel['PNLNO340']}>Bring (NO) CarryOn Homeshopping</option>";
		$ulos .= "<option value='PNLNO341' {$sel['PNLNO341']}>Bring (NO) CarryOn Homeshopping Return</option>";
		$ulos .= "<option value='PNLNO342' {$sel['PNLNO342']}>Bring (NO) CarryOn Homeshopping BulkSplit</option>";
		$ulos .= "<option value='PNLNO343' {$sel['PNLNO343']}>Bring (NO) CarryOn Homeshopping BulkReturn</option>";
		$ulos .= "<option value='PNLNOWAY' {$sel['PNLNOWAY']}>Bring (NO) CarryOn Waybill</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / Bring Express (BOX)'>";
		$ulos .= "<option value='BOXHD' {$sel['BOXHD']}>Home Delivery</option>";
		$ulos .= "<option value='BOXMND' {$sel['BOXMND']}>MQB Next Day</option>";
		$ulos .= "<option value='BOXMSD' {$sel['BOXMSD']}>MQB Same Day</option>";
		$ulos .= "<option value='BOXSTD' {$sel['BOXSTD']}>Box Delivery</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / Bring Frigoscandia (FSD)'>";
		$ulos .= "<option value='FSDSTD' {$sel['FSDSTD']}>Bring Frigoscandia Inrikes Transport</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / Bring Logistics (BL)'>";
		$ulos .= "<option value='BLSGD' {$sel['BLSGD']}>Stykkgods</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / DB Schenker (SBTL)'>";
		$ulos .= "<option value='BBU' {$sel['BBU']}>DB Schenker Budget</option>";
		$ulos .= "<option value='BCF' {$sel['BCF']}>DB Schenker System (fd Comfort)</option>";
		$ulos .= "<option value='BCS' {$sel['BCS']}>DB SCHENKERcoldsped</option>";
		$ulos .= "<option value='BCSI' {$sel['BCSI']}>DB SCHENKERcoldsped International</option>";
		$ulos .= "<option value='BDI' {$sel['BDI']}>DB SCHENKERdirect</option>";
		$ulos .= "<option value='BFL' {$sel['BFL']}>DB SCHENKERflow</option>";
		$ulos .= "<option value='BHP' {$sel['BHP']}>DB SCHENKERprivpak Ombud Standard (t.o.m. 20 kg)</option>";
		$ulos .= "<option value='BLP' {$sel['BLP']}>DB Schenker Logistikpaket</option>";
		$ulos .= "<option value='BPA' {$sel['BPA']}>DB SCHENKERparcel</option>";
		$ulos .= "<option value='BPALL' {$sel['BPALL']}>DB SCHENKERpallet</option>";
		$ulos .= "<option value='BPHDAG' {$sel['BPHDAG']}>DB SCHENKERprivpak Hem Dag med avisering (gods)</option>";
		$ulos .= "<option value='BPHDAP' {$sel['BPHDAP']}>DB SCHENKERprivpak Hem Dag med avisering (paket)</option>";
		$ulos .= "<option value='BPHDP' {$sel['BPHDP']}>DB SCHENKERprivpak Hem Dag</option>";
		$ulos .= "<option value='BPHKAP' {$sel['BPHKAP']}>DB SCHENKERprivpak Hem Kväll med avisering</option>";
		$ulos .= "<option value='BPOEP' {$sel['BPOEP']}>DB SCHENKERprivpak Ombud Ekonomi</option>";
		$ulos .= "<option value='BPTJP' {$sel['BPTJP']}>DB SCHENKERprivpak Till Jobbet</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / DB Schenker Privpak AB (PP)'>";
		$ulos .= "<option value='PPSTDFI' {$sel['PPSTDFI']}>Schenker Express Oy (Finland)</option>";
		$ulos .= "<option value='PPSTDNO' {$sel['PPSTDNO']}>Schenker Privpak AB (Norge)</option>";
		$ulos .= "<option value='PPSTDSE' {$sel['PPSTDSE']}>Schenker Privpak AB (Sverige)</option>";
		$ulos .= "<option value='PPUFORETSE' {$sel['PPUFORETSE']}>Schenker Privpak AB (Sverige Retur)</option>";
		$ulos .= "<option value='PPUFORTRSE' {$sel['PPUFORTRSE']}>Schenker Privpak AB (Sverige Tur och Retur)</option>";
		$ulos .= "<option value='PPUFOSTDSE' {$sel['PPUFOSTDSE']}>Schenker Privpak AB</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / DB Schenker Norge (SBTLNO)'>";
		$ulos .= "<option value='SBTLNOSG' {$sel['SBTLNOSG']}>Schenker Stykkgods</option>";
		$ulos .= "<option value='SBTLNOPG' {$sel['SBTLNOPG']}>Schenker Partigods</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / DB Schenker Finland (SBTLFI)'>";
		$ulos .= "<option value='SBTLFIDI' {$sel['SBTLFIDI']}>Schenker Finland Direct</option>";
		$ulos .= "<option value='SBTLFISY' {$sel['SBTLFISY']}>Schenker Finland System</option>";
		$ulos .= "<option value='SBTLFIEXP' {$sel['SBTLFIEXP']}>Schenker Finland Express</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / Kiitolinja (KL)'>";
		$ulos .= "<option value='KLGRP' {$sel['KLGRP']}>KIITOLINJA Groupage</option>";
		$ulos .= "<option value='KLHOM' {$sel['KLHOM']}>KIITOLINJAhome</option>";
		$ulos .= "<option value='KLHOMP' {$sel['KLHOMP']}>KIITOLINJAhome+</option>";
		$ulos .= "<option value='KLDIR' {$sel['KLDIR']}>KIITOLINJAdirect</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / DHL (DHL/DHLAIR)'>";
		$ulos .= "<option value='ASU' {$sel['ASU']}>DHL Euroconnect</option>";
		$ulos .= "<option value='APA2' {$sel['APA2']}>DHL Europack</option>";
		$ulos .= "<option value='APL' {$sel['APL']}>DHL Economy Select</option>";
		$ulos .= "<option value='AEX' {$sel['AEX']}>DHL Paket</option>";
		$ulos .= "<option value='ASWS2' {$sel['ASWS2']}>DHL Stycke</option>";
		$ulos .= "<option value='ASWP2' {$sel['ASWP2']}>DHL Parti</option>";
		$ulos .= "<option value='ASP2' {$sel['ASP2']}>DHL Pall</option>";
		$ulos .= "<option value='ASPO' {$sel['ASPO']}>DHL ServicePoint</option>";
		$ulos .= "<option value='ASPOC' {$sel['ASPOC']}>DHL ServicePoint Efterkrav</option>";
		$ulos .= "<option value='ASPOR' {$sel['ASPOR']}>DHL ServicePoint Retur</option>";
		$ulos .= "<option value='DASECD' {$sel['DASECD']}>DHL Express 09:00 (med tulldeklaration)</option>";
		$ulos .= "<option value='DASE' {$sel['DASE']}>DHL Express 09:00 (utan tulldeklaration)</option>";
		$ulos .= "<option value='DAMECD' {$sel['DAMECD']}>DHL Express 12:00 (med tulldeklaration)</option>";
		$ulos .= "<option value='DAME' {$sel['DAME']}>DHL Express 12:00 (utan tulldeklaration)</option>";
		$ulos .= "<option value='DADOX' {$sel['DADOX']}>DHL Worldwide Document Express(utan tulldeklaration)</option>";
		$ulos .= "<option value='DAECX' {$sel['DAECX']}>DHL Express Worldwide (utan tulldeklaration inom EU) </option>";
		$ulos .= "<option value='DAWPX' {$sel['DAWPX']}>DHL Worldwide Parcel Express (med tulldeklaration)</option>";
		$ulos .= "<option value='DATDL' {$sel['DATDL']}>DHL Express 10:30 (utan tulldeklaration)</option>";
		$ulos .= "<option value='DATDM' {$sel['DATDM']}>DHL Express 10:30 (med tulldeklaration)</option>";
		$ulos .= "<option value='DAXPD' {$sel['DAXPD']}>DHL Express Envelope (utan tulldeklaration)</option>";
		$ulos .= "<option value='DADOM ' {$sel['DADOM ']}>DHL Express Worldwide (Inrikes) </option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / DHL Global Forwarding'>";
		$ulos .= "<option value='DGFDNG' {$sel['DGFDNG']}>DFG Dangerous Goods</option>";
		$ulos .= "<option value='DGFECO' {$sel['DGFECO']}>DFG Economy</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / DSV (DFDS)'>";
		$ulos .= "<option value='DSVDEPCL' {$sel['DSVDEPCL']}>DSV Road e-Parcel</option>";
		$ulos .= "<option value='DSVDEPL' {$sel['DSVDEPL']}>DSV Road e-Pallet</option>";
		$ulos .= "<option value='DSVDF10' {$sel['DSVDF10']}>DSV Road F10</option>";
		$ulos .= "<option value='DSVDPG' {$sel['DSVDPG']}>DSV Road Partigods</option>";
		$ulos .= "<option value='DSVDSG' {$sel['DSVDSG']}>DSV Road Styckegods</option>";
		$ulos .= "<option value='DSVIPG' {$sel['DSVIPG']}>DSV Road Partigods (utrikes)</option>";
		$ulos .= "<option value='DSVISG' {$sel['DSVISG']}>DSV Road Styckegods (utrikes)</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / Neutral etikett (FREE)'>";
		$ulos .= "<option value='FREEG' {$sel['FREEG']}>Neutralt Gods</option>";
		$ulos .= "<option value='FREEP' {$sel['FREEP']}>Neutral Etikett</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / ATS Cargo (ATSC)'>";
		$ulos .= "<option value='ATSCSTD' {$sel['ATSCSTD']}>ATS Cargo</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / BEST Transport (BEST)'>";
		$ulos .= "<option value='BESTBP' {$sel['BESTBP']}>BEST Transport - BEST PAC</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / Bussgods (CG)'>";
		$ulos .= "<option value='CGB' {$sel['CGB']}>Företag / Privat </option>";
		$ulos .= "<option value='CGG' {$sel['CGG']}>Garanti</option>";
		$ulos .= "<option value='CGP' {$sel['CGP']}>Prio</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / Consignment (CS)'>";
		$ulos .= "<option value='CSB' {$sel['CSB']}>Consignment Blå</option>";
		$ulos .= "<option value='CSG' {$sel['CSG']}>Consignment Grön</option>";
		$ulos .= "<option value='CSR' {$sel['CSR']}>Consignment Röd</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / DACHSER'>";
		$ulos .= "<option value='DACCLINE' {$sel['DACCLINE']}>classicline</option>";
		$ulos .= "<option value='DACFLINE' {$sel['DACFLINE']}>fixline</option>";
		$ulos .= "<option value='DACSLINE' {$sel['DACSLINE']}>sysline</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / DANX'>";
		$ulos .= "<option value='Code' {$sel['Code']}>Service</option>";
		$ulos .= "<option value='DANXSTD' {$sel['DANXSTD']}>DANX</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / Danske Fragtmaend (DFM)'>";
		$ulos .= "<option value='DFMBP' {$sel['DFMBP']}>Danske Fragtmaend</option>";
		$ulos .= "<option value='DFMEP' {$sel['DFMEP']}>Danske Fragtmaend</option>";
		$ulos .= "<option value='DFMPE' {$sel['DFMPE']}>Danske Fragtmaend</option>";
		$ulos .= "<option value='DFMSG' {$sel['DFMSG']}>Danske Fragtmaend</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / Delego (DLG)'>";
		$ulos .= "<option value='DLGG' {$sel['DLGG']}>Delego Gods</option>";
		$ulos .= "<option value='DLGILG' {$sel['DLGILG']}>Delego Internt Gods</option>";
		$ulos .= "<option value='DLGILP' {$sel['DLGILP']}>Delego Internt Paket</option>";
		$ulos .= "<option value='DLGP' {$sel['DLGP']}>Delego Paket</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / DPD UK (PLN)'>";
		$ulos .= "<option value='PLN11' {$sel['PLN11']}>Two Day</option>";
		$ulos .= "<option value='PLN12' {$sel['PLN12']}>Next Day</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / General Logistics Systems (GLS)'>";
		$ulos .= "<option value='GLSA' {$sel['GLSA']}>GLS Business Parcel</option>";
		$ulos .= "<option value='GLSE' {$sel['GLSE']}>GLS Business Parcel Express</option>";
		$ulos .= "<option value='GLSPS' {$sel['GLSPS']}>GLS Parcel Shop</option>";
		$ulos .= "<option value='GLSESDSG' {$sel['GLSESDSG']}>GLS Sameday Solution</option>";
		$ulos .= "<option value='GLSESDS' {$sel['GLSESDS']}>GLS Express Sameday Solution 10.00</option>";
		$ulos .= "<option value='GLSESDS16' {$sel['GLSESDS16']}>GLS Express Sameday Solution 16.00</option>";
		$ulos .= "<option value='GLSESDS22' {$sel['GLSESDS22']}>GLS Express Sameday Solution 22.00</option>";
		$ulos .= "<option value='GLSR' {$sel['GLSR']}>GLS Pick & Return</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / HIT (HIT)'>";
		$ulos .= "<option value='H48' {$sel['H48']}>HIT InNight</option>";
		$ulos .= "<option value='H48DK' {$sel['H48DK']}>HIT Danmark A/S</option>";
		$ulos .= "<option value='H49' {$sel['H49']}>HIT InNight Internationell</option>";
		$ulos .= "<option value='H69' {$sel['H69']}>HIT Systemtransporter</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / Jetpak Borg (JB)'>";
		$ulos .= "<option value='JBSTD' {$sel['JBSTD']}>Jetpak Borg</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / Kaukokiito (KK)'>";
		$ulos .= "<option value='KKSTD' {$sel['KKSTD']}>Kaukokiito</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / Matkahuolto (MH)'>";
		$ulos .= "<option value='MH20' {$sel['MH20']}>Pikarahti</option>";
		$ulos .= "<option value='MH10' {$sel['MH10']}>Matkahuolto Bussipaketti </option>";
		$ulos .= "<option value='MH40' {$sel['MH40']}>Matkahuolto Rahtipussi </option>";
		$ulos .= "<option value='MH42' {$sel['MH42']}>Matkahuolto Rahtipussi jakopaketti </option>";
		$ulos .= "<option value='MH43' {$sel['MH43']}>Matkahuolto Dokumenttikuori</option>";
		$ulos .= "<option value='MH57' {$sel['MH57']}>Matkahuolto Lavarahti</option>";
		$ulos .= "<option value='MH80' {$sel['MH80']}>Matkahuolto Lähellä-paketti </option>";
		$ulos .= "<option value='MH30' {$sel['MH30']}>Matkahuolto Jakopaketti/Paikallispaketti</option>";
		$ulos .= "<option value='MH63' {$sel['MH63']}>Baltian Paketti</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / Morgontidningarnas Tidningsdistribution (MTD)'>";
		$ulos .= "<option value='MTDSTD' {$sel['MTDSTD']}>Morgontidningarnas Tidningsdistribution</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / OnRoad (ONROAD)'>";
		$ulos .= "<option value='ONRILA' {$sel['ONRILA']}>OnRoad Ila</option>";
		$ulos .= "<option value='ONRSKICKA' {$sel['ONRSKICKA']}>OnRoad Skicka</option>";
		$ulos .= "<option value='ONRSKYNDA' {$sel['ONRSKYNDA']}>OnRoad Skynda</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / Post Danmark (PDK)'>";
		$ulos .= "<option value='PDKEP' {$sel['PDKEP']}>Erhvervspakke</option>";
		$ulos .= "<option value='PDKLG' {$sel['PDKLG']}>Letgods</option>";
		$ulos .= "<option value='PDKPP' {$sel['PDKPP']}>Privatpakke</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / Posten Norge (PNO)'>";
		$ulos .= "<option value='PN1000E' {$sel['PN1000E']}>Bedriftspakke Dør - Dør</option>";
		$ulos .= "<option value='PN1002E' {$sel['PN1002E']}>Bedriftspakke Ekspress over natt 09.00</option>";
		$ulos .= "<option value='PN1202E' {$sel['PN1202E']}>Servicepakke</option>";
		$ulos .= "<option value='PN1312E' {$sel['PN1312E']}>Hjemlevering Prosjekt</option>";
		$ulos .= "<option value='PN1736E' {$sel['PN1736E']}>På døren</option>";
		$ulos .= "<option value='PN2758E' {$sel['PN2758E']}>Home Delivery</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / Posten Åland (PAX)'>";
		$ulos .= "<option value='PAXMH82' {$sel['PAXMH82']}>Postaalipaketti</option>";
		$ulos .= "<option value='PAXPO16' {$sel['PAXPO16']}>PAX Keltainen Kuljetus 16</option>";
		$ulos .= "<option value='PAXPO21' {$sel['PAXPO21']}>PAX Keltainen Kuljetus 21</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / Småfrakt'>";
		$ulos .= "<option value='SFECO' {$sel['SFECO']}>Ekonomi</option>";
		$ulos .= "<option value='SFDIR' {$sel['SFDIR']}>Fyratimmars</option>";
		$ulos .= "<option value='SF4H' {$sel['SF4H']}>Direkt</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / time:matters (TM)'>";
		$ulos .= "<option value='TMSTD' {$sel['TMSTD']}>time:matters</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / TNT (TNT)'>";
		$ulos .= "<option value='TNT09' {$sel['TNT09']}>09:00 EXPRESS (Domestic)</option>";
		$ulos .= "<option value='TNT09D' {$sel['TNT09D']}>09:00 EXPRESS (Docs)</option>";
		$ulos .= "<option value='TNT09N' {$sel['TNT09N']}>09:00 EXPRESS (Non Docs)</option>";
		$ulos .= "<option value='TNT10' {$sel['TNT10']}>10:00 EXPRESS (Domestic)</option>";
		$ulos .= "<option value='TNT10D' {$sel['TNT10D']}>10:00 EXPRESS (Docs)</option>";
		$ulos .= "<option value='TNT10N' {$sel['TNT10N']}>10:00 EXPRESS (Non Docs)</option>";
		$ulos .= "<option value='TNT12' {$sel['TNT12']}>12:00 EXPRESS (Domestic)</option>";
		$ulos .= "<option value='TNT12D' {$sel['TNT12D']}>12:00 EXPRESS (Docs)</option>";
		$ulos .= "<option value='TNT12N' {$sel['TNT12N']}>12:00 EXPRESS (Non Docs)</option>";
		$ulos .= "<option value='TNT15' {$sel['TNT15']}>EXPRESS (Domestic)</option>";
		$ulos .= "<option value='TNT15D' {$sel['TNT15D']}>EXPRESS (Docs)</option>";
		$ulos .= "<option value='TNT15N' {$sel['TNT15N']}>EXPRESS (Non Docs)</option>";
		$ulos .= "<option value='TNT412' {$sel['TNT412']}>12:00 ECONOMY EXPRESS (Non Docs)</option>";
		$ulos .= "<option value='TNT48N' {$sel['TNT48N']}>ECONOMY EXPRESS (Non Docs)</option>";
		$ulos .= "<option value='TNTSS' {$sel['TNTSS']}>SDS (Domestic)</option>";
		$ulos .= "<option value='TNTSSD' {$sel['TNTSSD']}>SDS (Docs)</option>";
		$ulos .= "<option value='TNTSSN' {$sel['TNTSSN']}>SDS (Non Docs)</option>";
		$ulos .= "<option value='TNTDNE' {$sel['TNTDNE']}>TNT Innight Domestic</option>";
		$ulos .= "<option value='TNTDNI' {$sel['TNTDNI']}>TNT Innight IntraNordic</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / Tollpost-Globe AS (DTPG)'>";
		$ulos .= "<option value='DTPGPF' {$sel['DTPGPF']}>DFDS Tollpost-Globe AS (Partifrakt)</option>";
		$ulos .= "<option value='DTPGSG' {$sel['DTPGSG']}>DFDS Tollpost-Globe AS (Stykkgods)</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / VR Transpoint (TP)'>";
		$ulos .= "<option value='TPSTD' {$sel['TPSTD']}>VR Transpoint</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / Transportgruppen (TG)'>";
		$ulos .= "<option value='TGSTD' {$sel['TGSTD']}>Transportgruppen</option>";
		$ulos .= "</optgroup>";

		$ulos .= "<optgroup label='Unifaun / UPS'>";
		$ulos .= "<option value='UPSEXPDTD' {$sel['UPSEXPDTD']}>UPS Expedited (Docs) </option>";
		$ulos .= "<option value='UPSEXPDTP' {$sel['UPSEXPDTP']}>UPS Expedited (Non Docs)</option>";
		$ulos .= "<option value='UPSEXPDTG' {$sel['UPSEXPDTG']}>UPS Expedited (GNIFC) </option>";
		$ulos .= "<option value='UPSEXPG' {$sel['UPSEXPG']}>UPS Express (GNIFC) </option>";
		$ulos .= "<option value='UPSEXPD' {$sel['UPSEXPD']}>UPS Express (Docs) </option>";
		$ulos .= "<option value='UPSEXPP' {$sel['UPSEXPP']}>UPS Express (Non Docs) </option>";
		$ulos .= "<option value='UPSEXPPLUSD' {$sel['UPSEXPPLUSD']}> UPS Express Plus (Docs) </option>";
		$ulos .= "<option value='UPSEXPPLUSG' {$sel['UPSEXPPLUSG']}> UPS Express Plus (GNIFC) </option>";
		$ulos .= "<option value='UPSEXPPLUSP' {$sel['UPSEXPPLUSP']}> UPS Express Plus (Non Docs) </option>";
		$ulos .= "<option value='UPSSAVD' {$sel['UPSSAVD']}> UPS Express Saver (Docs) </option>";
		$ulos .= "<option value='UPSSAVG' {$sel['UPSSAVG']}> UPS Express Saver (GNIFC) </option>";
		$ulos .= "<option value='UPSSAVP' {$sel['UPSSAVP']}> UPS Express Saver (Non Docs) </option>";
		$ulos .= "<option value='UPSSTDD' {$sel['UPSSTDD']}> UPS Standard (Docs) </option>";
		$ulos .= "<option value='UPSSTDP' {$sel['UPSSTDP']}> UPS Standard (Non Docs) </option>";
		$ulos .= "</optgroup>";

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "rahdinkuljettaja") {

		$ulos = "<td><select name='{$nimi}'>";

		$query = "	SELECT *
					FROM rahdinkuljettajat
					WHERE yhtio = '{$kukarow['yhtio']}'
					ORDER BY nimi";
		$rahdinkuljettajat_res = pupe_query($query);

		$ulos .= "<option value=''>".t("Ei valintaa")."</option>";

		while ($rahdinkuljettajat_row = mysql_fetch_assoc($rahdinkuljettajat_res)) {
			$sel = (isset($trow[$i]) and $trow[$i] == $rahdinkuljettajat_row['koodi']) ? " selected" : "";

			$ulos .= "<option value='{$rahdinkuljettajat_row['koodi']}' {$sel}>{$rahdinkuljettajat_row['nimi']}</option>";
		}


		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "logy_rahtikirjanumerot") {

		$sel1='';
		$sel2='';

		if ($trow[$i]=='K')
			$sel2 = 'SELECTED';
		else
			$sel1 = 'SELECTED';

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value='' $sel1>".t("Ei käytetä LOGY:n rahtikirjanumeroita")."</option>";
		$ulos .= "<option value='K' $sel2>".t("Käytetään LOGY:n rahtikirjanumeroita")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "merahti") {
		$sel1='';
		$sel2='';

		if ($trow[$i]=='K')
			$sel1 = 'SELECTED';
		else
			$sel2 = 'SELECTED';

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value='K' $sel1>".t("Käytetään lähettäjän rahtisopimusta")."</option>";
		$ulos .= "<option value=''  $sel2>".t("Käytetään vastaanottajan rahtisopimusta")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tulostustapa") {
			$sel			= array();
			$sel[$trow[$i]]	= " selected";

			$ulos = "<td>
				<select name='$nimi'>
				<option value='E' $sel[E]>".t("Erätulostus")."</option>
				<option value='H' $sel[H]>".t("Hetitulostus")."</option>
				<option value='K' $sel[K]>".t("Koonti-hetitulostus")."</option>
				<option value='L' $sel[L]>".t("Koonti-erätulostus")."</option>
				<option value='X' $sel[X]>".t("Rahtikirjansyöttö ja -tulostus ohitetaan")."</option>
				</select></td>";
			$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "extranet") {
		$sel			= array();
		$sel[$trow[$i]]	= " selected";

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value=''>".t("Toimitustapa näytetään vain myynnissä")."</option>";
		$ulos .= "<option value='K' $sel[K]>".t("Toimitustapa näytetään vain extranetissä")."</option>";
		$ulos .= "<option value='M' $sel[M]>".t("Toimitustapa näytetään molemmissa")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "rahtikirja") {

		$vresult = t_avainsana("RAHTIKIRJA");

		$ulos = "<td><select name='$nimi'>";

		while($row = mysql_fetch_array($vresult)) {
			$sel = "";
			if ($row["selite"] == $trow[$i]) $sel = 'selected';
			$ulos .= "<option value='$row[selite]' $sel>$row[selitetark]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "osoitelappu") {
		$sel1 = '';
		$sel2 = '';
		$sel3 = '';

		if ($trow[$i] == 'intrade') {
			$sel2 = 'selected';
		}
		elseif ($trow[$i] == 'tiivistetty') {
			$sel3 = 'selected';
		}
		else {
			$sel1 = 'selected';
		}

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value='' 		  $sel1>".t("Normaali")."</option>";
		$ulos .= "<option value='intrade' $sel2>".t("Intrade")."</option>";
		$ulos .= "<option value='tiivistetty' $sel3>".t("Tiivistetty")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "sisamaan_kuljetusmuoto" or mysql_field_name($result, $i) == "kuljetusmuoto") {

		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value = '' >".t("Valitse")."</option>";

		$vresult = t_avainsana("KM");

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['selite']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[selite]' $sel>$vrow[selitetark]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}


	if (mysql_field_name($result, $i) == "kauppatapahtuman_luonne") {

		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value = '' >".t("Valitse")."</option>";

		$vresult = t_avainsana("KT");

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['selite']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[selite]' $sel>$vrow[selitetark]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "poistumistoimipaikka_koodi") {

		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value = ''>".t("Valitse")."</option>";

		$vresult = t_avainsana("TULLI");

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['selite']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[selite]' $sel>$vrow[selite] - $vrow[selitetark]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kontti") {

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value = ''>".t("Valitse")."</option>";

		$chk1 = '';
		$chk2 = '';

		if ($trow[$i] == "1") {
			$chk1 = 'selected';
		}
		if ($trow[$i] == "0") {
			$chk2 = 'selected';
		}

		$ulos .= "<option value='1' $chk1>".t("Kyllä")."</option>";
		$ulos .= "<option value='0' $chk2>".t("Ei")."</option>";

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "jvkielto") {

		$sel = $sela = '';
		if ($trow[$i]!='') $sel = 'SELECTED';
		else $sela = 'SELECTED';

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value='' $sela>".t("Toimitustavalla saa toimittaa jälkivaatimuksia")."</option>";
		$ulos .= "<option value='o' $sel>".t("Toimitustavalla ei saa toimittaa jälkivaatimuksia")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "vak_kielto") {

		$sel			= array();
		$sel[$trow[$i]]	= " selected";

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value=''>".t("Toimitustavalla saa toimittaa VAK-tuotteita")."</option>";
		$ulos .= "<option value='K' $sel[K]>".t("Toimitustavalla ei saa toimittaa VAK-tuotteita")."</option>";

		$query = "	SELECT DISTINCT selite
					FROM toimitustapa
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND vak_kielto = ''
					AND nouto = ''";
		$alt_toimitustapa_res = mysql_query($query) or pupe_error($query);

		while ($alt_toimitustapa_row = mysql_fetch_assoc($alt_toimitustapa_res)) {
			$ulos .= "<option value='$alt_toimitustapa_row[selite]' ".$sel[$alt_toimitustapa_row['selite']].">".t("VAK-tuotteet toimitetaan toimitustavalla")." $alt_toimitustapa_row[selite]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "erikoispakkaus_kielto") {

		$sel = $sela = '';
		if ($trow[$i] != '') {
			$sel = 'selected';
		}
		else {
			$sela = 'selected';
		}

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value='' $sela>".t("Toimitustavalla saa toimittaa erikoispakkauksia")."</option>";
		$ulos .= "<option value='K' $sel>".t("Toimitustavalla ei saa toimittaa erikoispakkauksia")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "ei_pakkaamoa") {

		$ulos  = "<td><select name='$nimi'>";
		$chk1 = '';
		$chk2 = '';

		if ($trow[$i] == "1") {
			$chk1 = 'selected';
		}
		if ($trow[$i] == "0") {
			$chk2 = 'selected';
		}

		$ulos .= "<option value='0' $chk2>".t("Toimitustavan tilaukset varaavat pakkaamolokeron")."</option>";
		$ulos .= "<option value='1' $chk1>".t("Toimitustavan tilaukaset eivät varaa pakkaamolokeroa")."</option>";

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "erittely") {

		$ulos  = "<td><select name='$nimi'>";
		$chk1 = '';
		$chk2 = '';

		if ($trow[$i] == "k") {
			$chk1 = 'selected';
		}
		if ($trow[$i] == "") {
			$chk2 = 'selected';
		}

		$ulos .= "<option value='' $chk2>".t("Toimitustavalle ei tulosteta rahtikirjaerittelyä")."</option>";
		$ulos .= "<option value='k' $chk1>".t("Toimitustavalle tulostetaan rahtikirjaerittely")."</option>";

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "uudet_pakkaustiedot") {

		$ulos  = "<td><select name='$nimi'>";

		$sel			= array();
		$sel[$trow[$i]]	= " selected";

		$ulos .= "<option value=''>".t("Koonti-erätulostuksessa ei voi syöttää uusia pakkaustietoja ennen tulostusta")."</option>";
		$ulos .= "<option value='K' $sel[K]>".t("Koonti-erätulostuksessa voi syöttää uudet pakkaustiedot ennen tulostusta")."</option>";

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "sallitut_alustat") {
		$ulos = "<td>";

		$sel = explode(",", $trow[$i]);

		$query = "	SELECT *
					FROM pakkaus
					WHERE yhtio = '{$kukarow['yhtio']}'";
		$pakkaus_result = mysql_query($query) or pupe_error($query);

		$ulos .= "<input type='hidden' name='{$nimi}[]' value='default' />";

		while ($pakkaus_row = mysql_fetch_assoc($pakkaus_result)) {

			$chk = in_array($pakkaus_row['tunnus'], $sel) ? 'checked' : '';

			$ulos .= "<input type='checkbox' name='{$nimi}[]' value='{$pakkaus_row['tunnus']}' {$chk}/> ".t_tunnus_avainsanat($pakkaus_row, "pakkaus", "PAKKAUSKV")." ".t_tunnus_avainsanat($pakkaus_row, "pakkauskuvaus", "PAKKAUSKV")."<br />";
		}

		$ulos .= "</td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "jvkulu" OR
		mysql_field_name($result, $i) == "kuluprosentti" OR
		mysql_field_name($result, $i) == "ulkomaanlisa" OR
		mysql_field_name($result, $i) == "kuljetusvakuutus") {

		if ($trow[$i] == 0) {
			$trow[$i] = "";
		}
	}

	if (mysql_field_name($result, $i) == "kuljetusvakuutus_tyyppi") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Yhtiön oletus")."</option>";
		$ulos .= "<option value = 'E'>".t("Ei kuljetusvakuutusta")."</option>";
		$ulos .= "<option value = 'A' $sel[A]>".t("Valuuttamääräinen kuljetusvakuutus lisätään")."</option>";
		$ulos .= "<option value = 'B' $sel[B]>".t("Prosentuaalinen kuljetusvakuutus lisätään")."</option>";
		$ulos .= "<option value = 'F' $sel[F]>".t("Valuuttamääräinen kuljetusvakuutus lisätään. Käytetään kuljetusvakuutustuotteen asiakashintaa ja alennusta")."</option>";
		$ulos .= "<option value = 'G' $sel[G]>".t("Prosentuaalinen kuljetusvakuutus lisätään. Käytetään kuljetusvakuutustuotteen asiakasalennusta")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}