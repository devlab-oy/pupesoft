<?php

require ("inc/parametrit.inc");

echo "<font class='head'>".t("Maksettu ulkomaan arvonlisävero")."</font><hr><br>";

if ($tee == 'aja') {
	if (($plvv == 0 and $plvk != 0) or ($plvv != 0 and $plvk == 0)) {
		echo "<font class='error'>".t("Anna sekä kausi että vuosi")."</font><br>";
		$tee = '';
	}
}

if($tee == "aja") {
	$tkausi = (int) $tkausi;
	
	// Tutkitaan ensiksi, mille tilikaudelle pyydettävä lista löytyy, jos lista on sopiva
	if ($tkausi > 0) { 
		$query = "SELECT *
						FROM tilikaudet
						WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tkausi'";
		$result = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($result) != 1) {
			echo "<font class='error'>".t("Sopivaa yrityksen tilikautta ei löytynyt")."</font>";
			exit;
		
		}
		$tilikaudetrow=mysql_fetch_array($result);
		$alku = $tilikaudetrow["tilikausi_alku"];
		$loppu = $tilikaudetrow["tilikausi_loppu"];		
	}
	else {
		$alku = "$alvv-$alvk-01";
		$loppu = "$plvv-$plvk-".date("t",$plvk);
	}
	
	if($tkausi>0) {
		echo "<font class='message'>".t("Tililaukdella")." $alku - $loppu</font><br>";
	}
	else {
		echo "<font class='message'>".t("Ajalla")." $alku - $loppu</font><br>";
	}
	
	$lisa=array();
	if($maa!="") {
		$query = "select nimi from maat where koodi='$maa' and nimi!='' limit 1";
		$result=mysql_query($query) or pupe_error($query);
		$row=mysql_fetch_array($result);
		
		$lisa["lisatiedot"] .= " and tilausrivin_lisatiedot.kulun_kohdemaa='$maa'";
		echo "<font class='info'>".t("Maahan")." {$row["nimi"]}</font><br>";
	}
	if($kustp!="") {
		$query = "select nimi from kustannuspaikka where tunnus='$kustp'";
		$result=mysql_query($query) or pupe_error($query);
		$row=mysql_fetch_array($result);
		
		$lisa["where"] .= " and tiliointi.kustp='$kustp'";
		echo "<font class='info'>".t("kustannuspaikalle")." {$row["nimi"]}</font><br>";
	}
	if($kohde!="") {
		$query = "select nimi from kustannuspaikka where tunnus='$kohde'";
		$result=mysql_query($query) or pupe_error($query);
		$row=mysql_fetch_array($result);

		$lisa["where"] .= " and tiliointi.kohde='$kohde'";
		echo "<font class='info'>".t("Kohtelle")." {$row["nimi"]}</font><br>";		
	}
	if($proj!="") {
		$query = "select nimi from kustannuspaikka where tunnus='$proj'";
		$result=mysql_query($query) or pupe_error($query);
		$row=mysql_fetch_array($result);

		$lisa["where"] .= " and tiliointi.projekti='$proj'";
		echo "<font class='info'>".t("Projektille")." {$row["nimi"]}</font><br>";
	}
	
	echo "<hr>";
	$query = "	SELECT tiliointi.*,
				date_format(tiliointi.tapvm, '%d.%m.%Y') tapvm,
				round(summa*(1-(kulun_kohdemaan_alv/100)),2) veroton_osuus,
				concat_ws('/',kustp.nimi,kohde.nimi,projekti.nimi) kustannuspaikka,
				tilausrivin_lisatiedot.kulun_kohdemaa,
				tilausrivin_lisatiedot.kulun_kohdemaan_alv
				FROM tiliointi
				JOIN tilausrivin_lisatiedot ON tiliointi.yhtio=tilausrivin_lisatiedot.yhtio and tiliointi.tunnus=tilausrivin_lisatiedot.tiliointirivitunnus and kulun_kohdemaa!='{$yhtiorow["maa"]}' {$lisa["lisatiedot"]}
				LEFT JOIN kustannuspaikka kustp ON tiliointi.yhtio=kustp.yhtio and tiliointi.kustp=kustp.tunnus
				LEFT JOIN kustannuspaikka projekti ON tiliointi.yhtio=projekti.yhtio and tiliointi.projekti=projekti.tunnus
				LEFT JOIN kustannuspaikka kohde ON tiliointi.yhtio=kohde.yhtio and tiliointi.kohde=kohde.tunnus
				WHERE tiliointi.yhtio='$kukarow[yhtio]' and 
				tapvm>='$alku' and
				tapvm<='$loppu'
				{$lisa["where"]}
				and kulun_kohdemaa NOT IN ('','{$yhtiorow[maa]}')
				ORDER BY kulun_kohdemaa, tapvm";
	$result = mysql_query($query) or pupe_error($query);
	if(mysql_num_rows($result)>0) {
		$summat=array();
		echo "<table>
				<tr><th>".t("Kohdemaa")."</th>
					<th>".t("Kohdemaan ALV %")."</th>
					<th>".t("Tili")."</th>
					<th>".t("Tapvm")."</th>
					<th>".t("Kustannuspaikka")."</th>
					<th>".t("Kotisumma")."</th>
					<th>".t("Veroton summa")."</th>
					<th>".t("Perittävä vero")."</th>
					<th>".t("Selite")."</th>
				</tr>";
		while($row=mysql_fetch_array($result)) {
			
			$vero=($row["summa"]-$row["veroton_osuus"]);
			
			echo "<tr><td>{$row["kulun_kohdemaa"]}</td>
						<td align='right'>{$row["kulun_kohdemaan_alv"]}</td>
						<td>{$row["tilino"]}</td>
						<td>{$row["tapvm"]}</td>
						<td>{$row["kustannuspaikka"]}</td>												
						<td align='right'>{$row["summa"]}</td>
						<td align='right'>{$row["veroton_osuus"]}</td>
						<td align='right'>".number_format($vero ,2, '.', ' ')."</td>
						<td>{$row["selite"]}</td>
					</tr>";
			//	Summataan..		
			$summat[$row["kulun_kohdemaa"]]+=$vero;
		}
		
		echo "</table><br><br>";
		
		if(is_array($summat)) {
			echo "<font class='message'>".t("Takaisinperittävää maittain")."</font><br>";
			echo "<table><tr><th>".t("Maa")."</th><th>".t("Summa")."</th></tr>";
			foreach($summat as $kmaa => $summa) {
				echo "<tr><td>$kmaa</td><td>".number_format($summa ,2, '.', ' ')."</td></tr>";
			}
			echo "</table><br>";
		}
	}
	else {
		echo "<font class='message'>".t("Ei maksettua ulkomaanarvonlisäveroa valitulla jaksolla")."</font><br>";
	}
	$tee='';
}

if ($tee == '') {

	$sel = array();
	if ($tyyppi == "") $tyyppi = "4";
	$sel[$tyyppi] = "SELECTED";

	echo "<form action = '$PHP_SELF' method='post'>
			<input type = 'hidden' name = 'tee' value = 'aja'>

			<table>
			<tr>
				<th>".t("Ajalla")."</th>
				<td><select name='alvv'>";

	//	Oletetaan että halutaan selata viimeistä 12kk jaksoa
	$oalku=mktime(0, 0, 0, date("m")  , date("d"), date("Y")-1);
	$sel = array();
	if ($alvv == "") $alvv = date("Y",$oalku);
	$sel[$alvv] = "SELECTED";

	for ($i = date("Y")+1; $i >= date("Y")-4; $i--) {
		echo "<option value='$i' $sel[$i]>$i</option>";
	}

	$sel = array();
	if ($alvk == "") $alvk = date("m",$oalku);
	$sel[$alvk] = "SELECTED";

	echo "</select>
			<select name='alvk'>
			<option $sel[01] value = '01'>01</option>
			<option $sel[02] value = '02'>02</option>
			<option $sel[03] value = '03'>03</option>
			<option $sel[04] value = '04'>04</option>
			<option $sel[05] value = '05'>05</option>
			<option $sel[06] value = '06'>06</option>
			<option $sel[07] value = '07'>07</option>
			<option $sel[08] value = '08'>08</option>
			<option $sel[09] value = '09'>09</option>
			<option $sel[10] value = '10'>10</option>
			<option $sel[11] value = '11'>11</option>
			<option $sel[12] value = '12'>12</option>
			</select>";
	
	echo "&nbsp;-&nbsp;";
	
	echo "<select name='plvv'>";

	$sel = array();
	$sel[$plvv] = "SELECTED";

	$sel = array();
	if ($plvv == "") $plvv = date("Y");
	$sel[$plvv] = "SELECTED";

	for ($i = date("Y"); $i >= date("Y")-4; $i--) {
		echo "<option value='$i' $sel[$i]>$i</option>";
	}

	echo "</select>";

	$sel = array();
	if ($plvk == "") $plvk = date("m");
	$sel[$plvk] = "SELECTED";
	echo "<select name='plvk'>
			<option $sel[01] value = '01'>01</option>
			<option $sel[02] value = '02'>02</option>
			<option $sel[03] value = '03'>03</option>
			<option $sel[04] value = '04'>04</option>
			<option $sel[05] value = '05'>05</option>
			<option $sel[06] value = '06'>06</option>
			<option $sel[07] value = '07'>07</option>
			<option $sel[08] value = '08'>08</option>
			<option $sel[09] value = '09'>09</option>
			<option $sel[10] value = '10'>10</option>
			<option $sel[11] value = '11'>11</option>
			<option $sel[12] value = '12'>12</option>
			</select></td></tr>";

		echo "<tr><th>".t("tai koko tilikausi")."</th>";
	 	$query = "SELECT *
					FROM tilikaudet
					WHERE yhtio = '$kukarow[yhtio]'
					ORDER BY tilikausi_alku";
		$vresult = mysql_query($query) or pupe_error($query);
		echo "<td><select name='tkausi'><option value='0'>".t("Ei valintaa")."";
		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow[tunnus]) {
				$sel = "selected";
			}
			echo "<option value = '$vrow[tunnus]' $sel>$vrow[tilikausi_alku] - $vrow[tilikausi_loppu]";
		}
		echo "</select></td>";
		echo "</tr>";

	echo "<tr><th>".t("Vain kustannuspaikka")."</th>";

	$query = "SELECT tunnus, nimi
				FROM kustannuspaikka
				WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'K'
				ORDER BY nimi";
	$vresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='kustp'><option value=''>".t("Ei valintaa")."";

	while ($vrow=mysql_fetch_array($vresult)) {
		$sel="";
		if ($trow[$i] == $vrow['tunnus'] or $kustp == $vrow["tunnus"]) {
			$sel = "selected";
		}
		echo "<option value = '$vrow[tunnus]' $sel>$vrow[nimi]";
	}

	echo "</select></td>";
	echo "</tr>";
	echo "<tr><th>".t("Vain kohde")."</th>";

	$query = "SELECT tunnus, nimi
				FROM kustannuspaikka
				WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'O'
				ORDER BY nimi";
	$vresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='kohde'><option value=''>Ei valintaa";

	while ($vrow=mysql_fetch_array($vresult)) {
		$sel="";
		if ($trow[$i] == $vrow['tunnus'] or $kohde == $vrow["tunnus"]) {
			$sel = "selected";
		}
		echo "<option value = '$vrow[tunnus]' $sel>$vrow[nimi]";
	}

	echo "</select></td>";
	echo "</tr>";
	echo "<tr><th>".t("Vain projekti")."</th>";

	$query = "SELECT tunnus, nimi
				FROM kustannuspaikka
				WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'P'
				ORDER BY nimi";
	$vresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='proj'><option value=''>".t("Ei valintaa")."";

	while ($vrow=mysql_fetch_array($vresult)) {
		$sel="";
		if ($trow[$i] == $vrow['tunnus'] or $proj == $vrow["tunnus"]) {
			$sel = "selected";
		}
		echo "<option value = '$vrow[tunnus]' $sel>$vrow[nimi]";
	}

	echo "</select></td></tr>";

	echo "<tr><th>".t("Vain maa")."</th>";

	$query = "	SELECT distinct koodi, nimi
				FROM maat
				WHERE nimi != ''
				ORDER BY koodi";
	$vresult = mysql_query($query) or pupe_error($query);
	echo "<td><select name='maa'>";

	echo "<option value = '' >".t("Ei valintaa")."</option>";

	while ($vrow=mysql_fetch_array($vresult)) {
		$sel="";
		if (strtoupper($maa) == strtoupper($vrow[0])) {
			$sel = "selected";
		}

		echo  "<option value = '".strtoupper($vrow[0])."' $sel>".t($vrow[1])."</option>";
	}

	echo "</select></td></tr>";
	
	echo "</table><br>
	      <input type = 'submit' value = '".t("Näytä")."'></form>";
}

require("inc/footer.inc");
?>