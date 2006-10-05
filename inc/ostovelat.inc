<?php

echo "<b>".t("Sidottup‰‰oma")."</b><hr>";

echo "<form action='$PHP_SELF' method='post'><input type='hidden' name='toim' value='ostovelat'>";
echo "<table><tr><th></th><th></th>";
for ($j=0; $j<12; $j++) {
	$vuosi=substr($yhtiorow['tilikausi_alku'],0,4);
	$kk=substr($yhtiorow['tilikausi_alku'],5,2);
	$kk += $j;
	if ($kk > 12) {
		$vuosi++;
		$kk-=12;
	}
	if ($kk<10) $kk= '0'.$kk;
 	echo "<th>$vuosi-$kk</th>";
}
echo "</tr>";

for ($i=1; $i<8; $i++) {

	
	$query = "	SELECT distinct nimi, taso
				FROM taso
				WHERE yhtio='$kukarow[yhtio]'
				and taso < '3' 
				ORDER BY taso";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<tr><td><select name='taso[$i]'>";
	echo "<option value=''>".t("Ei valintaa")."</option>";
	while ($srow = mysql_fetch_array($sresult)) {
		$sel = '';
		if ($taso[$i] == $srow[1]) {
			$sel = "selected";
		}
		echo "<option value='$srow[1]' $sel>$srow[0] ($srow[1])</option>";
	}
	echo "</select></td>";
		
		
	if (trim($taso[$i]) != '') {
		$lisa='';
		for ($j=0; $j<12; $j++) {
			$vuosi=substr($yhtiorow['tilikausi_alku'],0,4);
			$kk=substr($yhtiorow['tilikausi_alku'],5,2);
			$kk += $j;
			if ($kk > 12) {
				$vuosi++;
				$kk-=12;
			}
			if ($kk<10) $kk= '0'.$kk;
			
			if ($kumulatiivinen != '')
				$merkki = "<=";
			else
				$merkki = "=";
				
			if ($tuhat != '')
				$lisa.= ", round(sum(if(left(tapvm,7) $merkki '$vuosi-$kk', summa,0))/1000,0)";
			else
				$lisa.= ", sum(if(left(tapvm,7) $merkki '$vuosi-$kk', summa,0))";
	
		}
		$query = "SELECT taso.nimi $lisa
					FROM tili, tiliointi USE INDEX (tiliointi_index), taso
					WHERE tili.yhtio = '$kukarow[yhtio]'
						and tapvm >= '$yhtiorow[tilikausi_alku]'
						and tapvm <= '$yhtiorow[tilikausi_loppu]'
						and korjattu=''
						and tiliointi.yhtio=tili.yhtio
						and tiliointi.tilino=tili.tilino
						and tili.ulkoinen_taso like '$taso[$i]%'
						and tiliointi.yhtio=taso.yhtio
						and taso.taso = '$taso[$i]'
						and taso.tyyppi ='U'
					GROUP by 1";
		$result = mysql_query($query) or pupe_error($query);

		while ($trow=mysql_fetch_array ($result)) {
			for ($j=0; $j<mysql_num_fields($result); $j++) {
				echo "<td align = 'right'>";
				echo $trow[$j];
				echo "</td>";
				
				if ($j > 0) $col[$j]+=$trow[$j];
			}
		}
	}
	else 
		for ($j=0; $j<13; $j++) echo "<td></td>";
	
	echo "</tr>";
}
echo "<tr><th></th><th>".t('Yhteens‰')."</th>";
for ($j=1; $j<13; $j++) echo "<th>$col[$j]</th>";
echo "</tr>";
if ((!isset($kumulatiivinen)) and (!is_array($taso))) $kumulatiivinen='on';
if ($kumulatiivinen != '') $kumulatiivinen = "checked";
if ((!isset($tuhat)) and (!is_array($taso))) $tuhat='on';
if ($tuhat != '') $tuhat = "checked";
echo "</table>";
echo "<table><tr><td><input type='checkbox' name='kumulatiivinen' $kumulatiivinen> ".t("Kumulatiivinen")."</td>";
echo "<td><input type='checkbox' name='tuhat' $tuhat> ".t("Jaa luvut 1000:lla")."</td><td><input type='submit' value='".t("N‰yt‰")."'></td></tr></table></form>";

?>
