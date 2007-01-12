<?php
require ("inc/parametrit.inc");

if (is_array($luvut)) {
	$paiv=0;
	$lisaa=0;
	foreach ($luvut as $rind => $rivi) {
		foreach ($rivi as $sind => $solu) {
			$solu = str_replace ( ",", ".", $solu);
			if ($solu == '!' or $solu = (float) $solu) {
				if ($solu == '!') $solu = 0;
				$solu = (float) $solu;
				$query="SELECT summa from budjetti where yhtio='$kukarow[yhtio]' and kausi = '$sind' and taso = '$rind' and kustannuspaikka='$vkustp' and kohde='$vkohde' and projekti='$vproj'";
				$result = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($result) == 1) {
					$budjrow=mysql_fetch_array($result);
					if ($budjrow['summa'] != $solu) {
						if ($solu == 0.00) 
							$query="DELETE from budjetti where yhtio='$kukarow[yhtio]' and kausi = '$sind' and taso = '$rind' and kustannuspaikka='$vkustp' and kohde='$vkohde' and projekti='$vproj'";
						else $query="UPDATE budjetti set summa = $solu where yhtio='$kukarow[yhtio]' and kausi = '$sind' and taso = '$rind' and kustannuspaikka='$vkustp' and kohde='$vkohde' and projekti='$vproj'";
						$result = mysql_query($query) or pupe_error($query);
						$paiv++;
					}
				}
				else {
					$query="INSERT into budjetti set summa = $solu, yhtio='$kukarow[yhtio]', kausi = '$sind', taso = '$rind', kustannuspaikka='$vkustp', kohde='$vkohde', projekti='$vproj'";
					$result = mysql_query($query) or pupe_error($query);
					$lisaa++;
				}
			}
		}
	}
	echo "<font class='message'>".t("Päivitin ").$paiv.t(" Lisäsin ").$lisaa."</font><br>";
}

if (isset($tyyppi)) {
	$sel1="";
	$sel2="";
	$sel3="";
	$sel4="";
	switch ($tyyppi) {
		case (1): $sel1 = 'selected';
		case (2): $sel2 = 'selected';
		case (3): $sel3 = 'selected';
		case (4): $sel4 = 'selected';
	}
}
echo "<font class='head'>".t("Budjetin ylläpito")."<hr></font>";
echo "<table>";
echo "<form action = '' method='post'>
		<table><tr>
		<th>".t("Tyyppi")."</th>
		<td><select name = 'tyyppi'>
		<option value='4' $sel4>".t("Tuloslaskelma")." 
		<option value='2' $sel2>".t("Vastattavaa")." 
		<option value='1' $sel1>".t("Vastaavaa")."
		</select></td></tr>
		<tr>";
echo "<tr><th>".t("Tilikausi");
$query = "SELECT *
	FROM tilikaudet
	WHERE yhtio = '$kukarow[yhtio]'
	ORDER BY tilikausi_alku";
$vresult = mysql_query($query) or pupe_error($query);
echo "</td><td><select name='tkausi'>";
while ($vrow=mysql_fetch_array($vresult)) {
	$sel="";
	if ($tkausi == $vrow['tunnus']) {
		$sel = "selected";
	}
echo "<option value = '$vrow[tunnus]' $sel>$vrow[tilikausi_alku] - $vrow[tilikausi_loppu]";
}
echo "</select></th></tr>";
echo "<tr><th>".t("Kustannuspaikka")."</th>";
$query = "SELECT tunnus, nimi
			FROM kustannuspaikka
			WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'K'
			ORDER BY nimi";
$vresult = mysql_query($query) or pupe_error($query);

echo "<td><select name='kustp'><option value=' '>".t("Ei valintaa")."";
while ($vrow=mysql_fetch_array($vresult)) {
	$sel="";
	if ($kustp == $vrow['tunnus']) {
		$sel = "selected";
	}
	echo "<option value = '$vrow[tunnus]' $sel>$vrow[tunnus] $vrow[nimi]";
}
echo "</select></td>";
echo "</tr>";
echo "<tr><th>".t("Kohde")."</th>";
$query = "SELECT tunnus, nimi
			FROM kustannuspaikka
			WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'O'
			ORDER BY nimi";
$vresult = mysql_query($query) or pupe_error($query);
echo "<td><select name='kohde'><option value=' '>Ei valintaa";
while ($vrow=mysql_fetch_array($vresult)) {
	$sel="";
	if ($kohde == $vrow['tunnus']) {
		$sel = "selected";
	}
	echo "<option value = '$vrow[tunnus]' $sel>$vrow[nimi]";
}
echo "</select></td>";
echo "</tr>";
echo "<tr><th>".t("Projekti")."</th>";
$query = "SELECT tunnus, nimi
			FROM kustannuspaikka
			WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'P'
			ORDER BY nimi";
$vresult = mysql_query($query) or pupe_error($query);
echo "<td><select name='proj'><option value=' '>".t("Ei valintaa")."";
while ($vrow=mysql_fetch_array($vresult)) {
	$sel="";
	if ($proj == $vrow['tunnus']) {
		$sel = "selected";
	}
	echo "<option value = '$vrow[tunnus]' $sel>$vrow[nimi]";
}
echo "</select></td></tr>";
echo "<td><input type='submit' VALUE='valmis'></td><td>".t("Budjettiluvun voi poistaa huutomerkillä (!)")."</td></tr>";
echo "</table>";

		
if (isset($tkausi)) {
	$query = "SELECT *
			FROM tilikaudet
			WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tkausi'";
	$vresult = mysql_query($query) or pupe_error($query);
	if (mysql_num_rows($vresult) == 1)
		$tilikaudetrow=mysql_fetch_array($vresult);
}

if (is_array($tilikaudetrow)) {
	//Parametrit mihin tämä taulukko liittyy
	echo "<input type='hidden' name = 'vkustp' value='$kustp'>";
	echo "<input type='hidden' name = 'vkohde' value='$kohde'>";
	echo "<input type='hidden' name = 'vproj' value='$proj'>";
	echo "<table>";
	echo "<tr><td></td>";
	$raja = '0000-00';
	$rajataulu = array();
	$j=0;
	while ($raja < substr($tilikaudetrow['tilikausi_loppu'],0,7)) {
		$vuosi=substr($tilikaudetrow['tilikausi_alku'],0,4);
		$kk=substr($tilikaudetrow['tilikausi_alku'],5,2);
		$kk += $j;
		if ($kk > 12) {
			$vuosi++;
			$kk-=12;
		}
		if ($kk<10) $kk= '0'.$kk;
		
		$raja = $vuosi . "-" . $kk;
		$rajataulu[$j] = $vuosi.$kk;
		
	 	echo "<th>$raja</th>";
	 	$j++;
	}
	echo "</tr>";

	$tasotyyppi = "U";
	if ($tyyppi == 4) { // Sisäinen tuloslaskelma!!!
		$tasotyyppi = "S";
		$tyyppi = 3;
	}
	$query = "SELECT *
			FROM taso
			WHERE yhtio = '$kukarow[yhtio]' and taso like '$tyyppi%' and tyyppi='$tasotyyppi'";
	$vresult = mysql_query($query) or pupe_error($query);
	while ($tasorow=mysql_fetch_array($vresult)) {
		echo "<tr><td>$tasorow[taso] $tasorow[nimi]</td>";
		for ($k=0;$k<$j;$k++) {
			$itaso = $tasorow['taso'];
			$ik = $rajataulu[$k];
			$query="SELECT * from budjetti where yhtio='$kukarow[yhtio]' and kausi = '$ik' and taso = '$itaso' and kustannuspaikka='$kustp' and kohde='$kohde' and projekti='$proj'";
			$xresult = mysql_query($query) or pupe_error($query);
			$nro='';
			if (mysql_num_rows($xresult) == 1) {
				$brow = mysql_fetch_array($xresult);
				$nro = $brow['summa'];
			}			
			echo "<td><input type='text' name = 'luvut[$itaso][$ik]' value='$nro' size='8'></td>";
		}
		echo "<tr>";
	}
	echo "</table>";
}
?>
