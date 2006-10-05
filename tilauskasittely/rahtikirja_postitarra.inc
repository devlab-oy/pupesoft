<?php

$query = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus in ($otunnukset) order by tunnus limit 1";
$tempr = mysql_query($query) or pupe_error($query);
$postirow = mysql_fetch_array($tempr);

if ($postirow['rahtisopimus']=='') $postirow['rahtisopimus']="000000";
if ($toitarow['sopimusnro']=='')   $toitarow['sopimusnro']="000000";

$x = array();
if ($yhteensa!='')					$x[]='1'; // postiennakko
if ($kollityht>1)    				$x[]='2'; // monipakettilähetys
if ($rakir_row['merahti']!='K')		$x[]='3'; // vastaanottaja maksaa
if (count($vakit)>0)				$x[]='4'; // erikoiskäsiteltävää
if ($toitarow['lauantai']!='')		$x[]='6'; // lauantaijakelu
if ($toitarow['kuljyksikko']!='')	$x[]='7'; // kuljetusyksikkökuljetus

$keku = "16";
if (strpos($toitarow['selite'], "9"))  $keku="9";
if (strpos($toitarow['selite'], "00")) $keku="00";
if (strpos($toitarow['selite'], "14")) $keku="14";
if (strpos($toitarow['selite'], "21")) $keku="21";

$keltainen = "Keltainen";
$kuljetus  = "Kuljetus";

if ($keku=="00") {
	$keltainen = "";
	$kuljetus  = "EXP";
}

// konvertoidaan keku numero postin koodiksi
switch ($keku) {
	case "00":
		$tuoteviiva1="2W2124";
		$tuoteviiva2=">62W>52124";
		break;
	case "9":
		$tuoteviiva1="2W2101";
		$tuoteviiva2=">62W>52101";
		break;
	case "14":
		$tuoteviiva1="2W2102";
		$tuoteviiva2=">62W>52102";
		break;
	case "16":
		$tuoteviiva1="2W2103";
		$tuoteviiva2=">62W>52103";
		break;
	case "21":
		$tuoteviiva1="2W2104"; // tämä on varamaan väärin, mutta en jaksa etsiä mikä on oikea 
		$tuoteviiva2=">62W>52104";
		break;
}

// postiennakko
if ($rakir_row["jv"] != '') {
	$postiennakkomaara  = "$yhteensa";
	$postiennakkotilino = "$yhtiorow[pankkitili1]";
	$postiennakkoviite  = "$viite";
}
else {
	$postiennakkomaara  = "";
	$postiennakkotilino = "";
	$postiennakkoviite  = "";
}

if ($rahdinmaksaja == "Lähettäjä") { 
	$rahdinmaksaja = "";
}

if ($tulostuskpl == 0) {
	$tulostakolli = 1;
}
else {
	$tulostakolli = $tulostuskpl;
}

// tulostetaan niin monta lappua kun on kollejakin
for ($tulostuskpl=1; $tulostuskpl<=$tulostakolli; $tulostuskpl++) {
	$kuka    = sprintf("%02d",$tulostuskpl); 		// laitetaan tulostuskpl alkuun niin jokalapulla on uniikki viiva
	$oma     = sprintf("%09d",$rtunnus); 			// 9, pitkä numero tulee rahtikirja taulun tunnuksesta, pitää olaa uniikki
		
	$toitarow['sopimusnro'] = sprintf("%06d",$toitarow['sopimusnro']);	// sopimunumeron tulee olla kuus pitkä

	// tehdään viivakoodi (jonne laitetaan aina arwin sopimusnumero)
	$viiva1  = ">6JJFI";	
	$viiva1 .= substr($toitarow['sopimusnro'],0,1);
	$viiva1 .= ">5";
	$viiva1 .= substr($toitarow['sopimusnro'],1);
	
	$viiva2  = "JJFI";
	$viiva2 .= $toitarow['sopimusnro'];
	
	$sopnro  = $toitarow['sopimusnro']; // käytetään edi sanomassa

	$viiva1 .= $kuka.$oma;
	$viiva2 .= $kuka.$oma;

	// erotellaan spacella jokainen rahtikirjanumero
	$rahtikirjanro .= "$viiva2 ";
	
	$sivu = "
*** ETIKETIN KOKO ***
{D2280,1040,2250|}
{AX;+000,+000,+00|}
{AY;+05,0|}
{C|}

*** LOGO ***
{PC085;0005,0005,1,1,01,00,B|}
{RC085;!|}

*** KIINTEÄÄ TEKSTIÄ JA VIIVOJA ***
{PC004;0060,0168,05,05,H,00,B|}
{RC004;Lähettäjä Avsändare From|}
{PC005;0060,0369,05,05,H,00,B|}
{RC005;Vastaanottaja Adressat To   Puh.|}
{PC006;0810,0359,05,05,A,00,B|}
{RC006;Jätetty Postin kulje-|}
{PC007;0810,0379,05,05,A,00,B|}
{RC007;tettavaksi|}
{PC008;0810,0409,05,05,A,00,B|}
{RC008;Overlämnat till|}
{PC009;0810,0429,05,05,A,00,B|}
{RC009;Posten|}
{PC010;0975,0529,05,05,H,00,B|}
{RC010;kg|}
{PC011;0975,0589,05,05,H,00,B|}
{RC011;m3|}
{PC012;0057,0631,05,10,G,00,B|}
{RC012;Lisäpalvelut  Tilläggstjänster|}
{PC013;0340,0626,05,10,G,00,B|}
{RC013;Maksaja muu kuin lähettäjä  Betalaren annan än avsändaren|}
{PC014;0887,0633,05,05,H,00,B|}
{RC014;MPS|}
{PC015;0057,0709,05,10,G,00,B|}
{RC015;Postiennakkomäärä  Postförskottsbelopp|}
{PC016;0380,0738,05,05,H,00,B|}
{RC016;|}
{PC017;0480,0709,05,10,G,00,B|}
{RC017;Tilinumero Kontonummer|}
{PC018;0057,0801,05,10,G,00,B|}
{RC018;Viitenumero Referensnummer|}
{PC019;0062,1205,05,05,D,00,B|}
{RC019;SAAPUMISILMOITUS  ANKOMSTAVI|}
{PC020;0060,1235,05,10,G,00,B|}
{RC020;Lähetys on noudettavissa Postistanne, olkaa hyvä ja ottakaa tämä ilmoitus mukaanne. Lisätietoja 9800 7100.|}
{PC021;0060,1261,05,10,G,00,B|}
{RC021;Ni kan hämta förs'ndelsen på Ert postkontor. Ta med Er denna avi. Mera information 9800 27100.|}
{PC022;0060,1287,05,10,G,00,B|}
{RC022;Säilytysaika on 2 täyttä kalenteriviikkoa. Kvarligger 2 hela kalenderveckor.|}
{PC023;0060,1319,05,05,H,00,B|}
{RC023;Lähettäjä Avsändare From|}
{PC123;0690,1319,05,05,H,00,B|}
{RC123;Lisätiedot Tilläggsuppgifter|}
{PC024;0060,1389,05,05,H,00,B|}
{RC024;Vastaanottaja Adressat To   Puh.|}
{PC025;0975,1620,05,05,H,00,B|}
{RC025;kg|}
{PC026;0815,1524,05,05,H,00,B|}
{RC026;Pvm Dat.|}
{PC027;0815,1588,05,05,H,00,B|}
{RC027;MPS|}
{PC028;0055,1677,05,10,G,00,B|}
{RC028;Sisältö Innehåll|}
{PC029;0055,1709,05,10,G,00,B|}
{RC029;Postiennakkomäärä   Postförskottsbelopp|}
{PC030;0375,1739,05,05,H,00,B|}
{RC030;|}
{PC031;0465,1709,05,10,G,00,B|}
{RC031;Tilinumero Kontonummer|}
{PC032;0055,1797,05,10,G,00,B|}
{RC032;Viitenumero Referensnummer|}
{PC033;0055,1830,05,10,G,00,B|}
{RC033;Kuittaus ja nimen selvennys Kvittering och namnförtydligande|}
{PC035;0340,0651,05,10,G,00,B|}
{RC035;Maksajan sopimustunnus|}
{PC036;0341,0677,05,10,G,00,B|}
{RC036;Betalarens avtalskod|}
{PC086;0285,1890,05,05,H,00,B|}
{RC086;klo kl.|}
{LC;0050,0748,1000,0748,0,2|}
{LC;0050,1293,1000,1293,0,2|}
{LC;0050,1628,1000,1628,0,2|}
{LC;0050,1683,1000,1683,0,2|}
{LC;0050,1748,1000,1748,0,2|}
{LC;0050,1803,1000,1803,0,2|}
{LC;0050,0683,1000,0683,0,2|}
{LC;0050,0603,1000,0603,0,2|}
{LC;0050,0808,1000,0808,0,2|}
{LC;0335,0606,0335,0681,0,2|}
{LC;0880,0606,0880,0681,0,2|}
{LC;0880,1565,0880,1628,0,2|}
{LC;0470,0684,0470,0749,0,2|}
{LC;0810,0543,1000,0543,0,2|}
{LC;0810,0478,1000,0478,0,2|}
{LC;0050,1177,1000,1177,0,2|}
{LC;0810,1563,1000,1563,0,2|}
{LC;0810,1498,1000,1498,0,2|}
{LC;0460,1685,0460,1750,0,2|}
{XR;0050,0637,0101,0683,B|}
{XR;0053,0639,0099,0681,B|}
{XR;0115,0637,0166,0683,B|}
{XR;0118,0639,0164,0681,B|}
{XR;0180,0637,0231,0683,B|}
{XR;0183,0639,0229,0681,B|}
{XR;0245,0637,0297,0683,B|}
{XR;0248,0639,0294,0681,B|}

*** SEURANTA VIIVAKOODI ***
{XB00;0058,0845,A,3,04,0,0300,+0000000000,000,0,00|}
{RB00;$viiva1|}
{PC037;0345,0837,05,05,H,00,B|}
{RC037;$viiva2|}
{PC038;0345,1172,05,05,H,00,B|}
{RC038;$viiva2|}

*** PALVELUN NIMI JA NOPEUS ***
{PC000;0180,0068,05,05,K,00,B|}
{RC000;$keltainen|}
{PC001;0180,0108,05,05,K,00,B|}
{RC001;$kuljetus|}
{PC003;0355,0108,2,2,H,00,B|}
{RC003;$keku|}
{PC115;0700,1205,05,05,D,00,B|}
{RC115;$keltainen $kuljetus $keku|}

{PC078;0055,1890,05,05,K,00,B|}
{RC078;   .   .|}


*** TUOTEVIIVAKOODI (2W) ***
{XB01;0548,0028,A,3,04,0,0150,+0000000000,000,0,00|}
{RB01;$tuoteviiva2|}
{PC090;0705,0208,05,05,H,00,B|}
{RC090;$tuoteviiva1|}


*** LISÄTIETO-OSA EDI/2W (Otsikko) ***
{PC034;0060,1930,05,05,H,00,B|}
{RC034;Lisätiedot Tilläggsuppgifter|}

*** MUUTTUVAA TIETOA ***
{PC039;0060,0204,05,05,I,00,B|}
{RC039;$yhtiorow[nimi]|}
{PC040;0060,0236,05,05,I,00,B|}
{RC040;$yhtiorow[osoite]|}
{PC041;0060,0266,05,05,I,00,B|}
{RC041;$yhtiorow[postino] $yhtiorow[postitp]|}
{PC042;0060,0296,05,05,I,00,B|}
{RC042;Puh. $yhtiorow[puhelin]|}
{PC043;0060,0326,05,05,I,00,B|}
{RC043;                      |}
{PC045;0060,0403,05,05,I,00,B|}
{RC045;$rakir_row[toim_nimi]|}
{PC046;0060,0440,05,05,I,00,B|}
{RC046;$rakir_row[toim_nimitark]|}
{PC047;0060,0478,05,05,I,00,B|}
{RC047;$rakir_row[toim_osoite]|}
{PC048;0060,0517,05,05,I,00,B|}
{RC048;                                |}
{PC049;0060,0554,05,05,I,00,B|}
{RC049;                                |}
{PC050;0060,0597,05,05,K,00,B|}
{RC050;$rakir_row[toim_postino] $rakir_row[toim_postitp]|}
{PC051;0815,0468,05,05,I,00,B|}
{RC051;".date("d-m-Y")."|}
{PC052;0815,0528,05,05,I,00,B|}
{RC052;$kilotyht|}
{PC053;0815,0588,05,05,I,00,B|}
{RC053;$kuutiotyht|}
{PC054;0065,0672,05,05,K,00,B|}
{RC054;$x[0]|}
{PC055;0130,0672,05,05,K,00,B|}
{RC055;$x[1]|}
{PC056;0195,0672,05,05,K,00,B|}
{RC056;$x[2]|}
{PC057;0260,0672,05,05,K,00,B|}
{RC057;$x[3]|}
{PC084;0606,0667,05,05,I,00,B|}
{RC084;$rahdinmaksaja|}
{PC058;0890,0673,05,05,K,00,B|}
{RC058;M|}
{PC059;0060,0737,05,05,I,00,B|}
{RC059;".$postiennakkomaara."|}
{PC060;0425,0738,05,05,H,00,B|}
{RC060;eur|}
{PC061;0480,0737,05,05,I,00,B|}
{RC061;".$postiennakkotilino."|}
{PC062;0350,0792,05,05,I,00,B|}
{RC062;".$postiennakkoviite."|}
{PC063;0060,1353,05,05,I,00,B|}
{RC063;$yhtiorow[nimi]|}
{PC064;0060,1428,05,05,I,00,B|}
{RC064;$rakir_row[toim_nimi]|}
{PC065;0060,1465,05,05,I,00,B|}
{RC065;$rakir_row[toim_nimitark]|}
{PC066;0060,1504,05,05,I,00,B|}
{RC066;$rakir_row[toim_osoite]|}
{PC067;0060,1542,05,05,I,00,B|}
{RC067;                                  |}
{PC068;0060,1579,05,05,I,00,B|}
{RC068;                                  |}
{PC069;0060,1617,05,05,K,00,B|}
{RC069;$rakir_row[toim_postino] $rakir_row[toim_postitp]|}
{PC080;0650,1540,05,05,K,00,B|}
{RC080;|}
{PC070;0893,1615,05,05,I,00,B|}
{RC070;$kilotyht|}
{PC071;0810,1553,05,05,I,00,B|}
{RC071;|}
{PC072;0815,1618,05,05,K,00,B|}
{RC072;M|}
{PC073;0220,1674,05,05,H,00,B|}
{RC073;".$tahan_tulisi_paketin_sisalto."|}
{PC074;0060,1738,05,05,I,00,B|}
{RC074;".$postiennakkomaara."|}
{PC075;0415,1739,05,05,H,00,B|}
{RC075;eur|}
{PC076;0470,1738,05,05,I,00,B|}
{RC076;".$postiennakkotilino."|}
{PC077;0350,1793,05,05,I,00,B|}
{RC077;".$postiennakkoviite."|}

*** LISÄTIETO-OSA EDI/2W (Ei Viivakoodia) ***
{PC079;0060,1980,05,05,H,00,B|}
{RC079;$vakit[0]|}
{PC080;0060,2010,05,05,H,00,B|}
{RC080;$vakit[1]|}
{PC081;0060,2040,05,05,H,00,B|}
{RC081;$vakit[2]|}
{PC082;0060,2070,05,05,H,00,B|}
{RC082;$vakit[3]|}
{PC083;0060,2100,05,05,H,00,B|}
{RC083;$vakit[4]|}
{PC084;0060,2130,05,05,H,00,B|}
{RC084;$vakit[5]|}
{PC085;0060,2160,05,05,H,00,B|}
{RC085;$vakit[6]|}

***EDI ***
{PC121;0850,0300,1,1,K,00,B|}
{RC121;EDI|}

*** TULOSTUS  ***
{XS;I,0001,0002C3210|}

".chr(12);

	$sivu = escapeshellarg($sivu);
	$line = exec(" echo \"$sivu\" | $kirjoitin");

	//lähetetään postin edisanoma
	if ($tulostakopio == "") {
		require("postiedi.inc");
	}	

} // end for looppi

?>
