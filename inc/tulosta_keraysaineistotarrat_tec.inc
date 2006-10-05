<?php
//Kun tullaan tänne tarvitaan $komento joka on se komento jolla tulostetaan esim. lpr -P tarrakirjoitin

$lasktarrat = 0;
$lisa = '';
$alatila = '';
$vanhatunnus = 0;

if ($tee == 'uudet') {
	$alatila = 'A';
}
elseif ($tee == 'vanhat') {
	$alatila = 'X';
	$lisa = "and laadittu >= '$vva-$kka-$ppa 00:00:00' and laadittu <= '$vvl-$kkl-$ppl 23:59:59'";
}

$query =	"select ytunnus, viitetxt tiltunnus, tilkpl, sisviesti2 astunnus, 
			DATE_FORMAT(laadittu, '%d-%m-%y') laadittu, tuote.eankoodi eankoodi, kommentti tuoteno, 
			aktiivinen_kuljetus suunnistus, lasku.tunnus xtunnus
			from lasku 
			join tilausrivi on lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus 
			left join tuote on lasku.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
			where lasku.yhtio = '$kukarow[yhtio]' and tila = 'Z' and alatila = '$alatila' and tyyppi = 'Z'
			$lisa
			order by tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso, tilausrivi.tuoteno, tilausrivi.tunnus";
$tarrares = mysql_query($query) or pupe_error($query);

if ($debug == 1) {
	echo "$query<br>";
	echo "".mysql_num_rows($tarrares)."<br>";
}

if (mysql_num_rows($tarrares) > 0) {
	while($tarrarow = mysql_fetch_array($tarrares)) {
		
		$toim_nimi		=$tarrarow['toim_nimi'];
		$tiltunnus		=ltrim($tarrarow['tiltunnus'],'0');
		$tilkpl			=$tarrarow['tilkpl'];
		$laadittu		=$tarrarow['laadittu'];
		$eankoodi		=$tarrarow['eankoodi'];
		$suunnistus		=$tarrarow['suunnistus'];
		$xtunnus		=$tarrarow['xtunnus'];
		
		list($tuoteno,$nimitys) = split("#",$tarrarow['tuoteno']);
		list($asnro,$asnimi) = split("#",$tarrarow['astunnus']);
		
		$asnimi = substr($asnimi,0,25);
		
		for($kerroin = 1; $kerroin < $tilkpl+1; $kerroin++) {
		
			$tukpl = "1/  ".round($kerroin,0);
		
			//tässä tehdään aineisto jota TEC tulostin tajuaa
			$sivu = "
	*** ETIKETIN KOKO ***
	{D0500,0900,0450|}
	{AX;+000,+000,+00|}
	{AY;+05,0|}
	{C|}

	*** TÄSTÄ SE ALKAA ***
	{PC000;0020,0050,10,10,G,00,B|}
	{RC000;$asnimi|}
	{PC001;0570,0050,10,10,G,00,B|}
	{RC001;$tiltunnus|}
	{PC002;0760,0050,10,10,G,00,B|}
	{RC002;$tukpl|}
	{PC003;0020,0100,10,10,G,00,B|}
	{RC003;$asnro|}
	{PC004;0360,0100,10,10,G,00,B|}
	{RC004;$laadittu|}
	{PC005;0020,0170,05,05,K,00,B|}
	{RC005;$eankoodi|}
	{PC006;0020,0240,10,10,G,00,B|}
	{RC006;$nimitys|}
	{PC007;0020,0330,05,05,K,00,B|}
	{RC007;$tuoteno|}
	{PC008;0020,0400,05,05,K,00,B|}
	{RC008;$suunnistus|}
	
	*** TULOSTUS  ***
	{XS;I,0001,0002C3210|}

	".chr(12);
			//konvertoidaan ääkköset printterin ymmärtämään muotoon
			$from = array ('ä','å','ö','Ä','Å','Ö');
			$to   = array (chr(132),chr(134),chr(148),chr(142),chr(143),chr(153)); // DOS charset
			$sivu = str_replace($from, $to, $sivu);											// Tehdään käännös

			$sivu = escapeshellarg($sivu);
			if ($debug == 1) {
				echo	"$asnimi<br>
						$tiltunnus<br>
						$tilkpl<br>
						$asnro<br>
						$laadittu<br>
						<b>$eankoodi</b><br>
						$nimitys<br>
						<b>$tuoteno</b><br>
						<b>$suunnistus</b><br><br>";
			}
			else {
				$line = exec(" echo \"$sivu\" | $komento");
			}
			$lasktarrat ++;

			if ($xtunnus != $vanhatunnus) {
				$query = "update lasku set alatila = 'X' where yhtio = '$kukarow[yhtio]' and tunnus = '$xtunnus'";
				$res = mysql_query($query) or pupe_error($query);
				$vanhatunnus = $xtunnus;
			}
		}
	}
	echo "".t("Tulostettiin")." $lasktarrat ".t("tarraa").".<br><br>";
}
else {
	if ($tee == 'uudet') {
		echo "<font class='error'>".t("Tiedot katosivat, joku taisi jo keretä tulostaa tarrat").".<br></font>";
	}
	elseif ($tee == 'vanhat') {
		echo "<font class='error'>".t("Aikavälille ei löytynyt yhtään tietuetta").".<br></font>";
	}
}
$tee = '';
?>