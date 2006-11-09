<?php
require ("inc/parametrit.inc");

if (is_array($luvut)) {
	foreach ($luvut as $rind => $rivi) {
		foreach ($rivi as $sind => $solu) {
			$solu = (float) $solu;
			if ($solu <> 0) {
				$query="SELECT * from budjetti where yhtio='$kukarow[yhtio]' and kausi = '$sind' and taso = '$rind'";
				echo "$query<br>";
				$query="UPDATE budjetti set summa = $solu where yhtio='$kukarow[yhtio]' and kausi = '$sind' and taso = '$rind'";
				echo "$query<br>";
			}
		}
	}
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
	if ($trow[$i] == $vrow['tunnus']) {
		$sel = "selected";
	}
	echo "<option value = '$vrow[tunnus]' $sel>$vrow[nimi]";
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
	if ($trow[$i] == $vrow['tunnus']) {
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
	if ($trow[$i] == $vrow['tunnus']) {
		$sel = "selected";
	}
	echo "<option value = '$vrow[tunnus]' $sel>$vrow[nimi]";
}
echo "</select></td></tr>";
echo "<td><input type='submit' VALUE='valmis'></td>";
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
			$query="SELECT * from budjetti where yhtio='$kukarow[yhtio]' and kausi = '$ik' and taso = '$itaso'";
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
