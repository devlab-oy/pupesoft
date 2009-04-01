<?php

require ("../inc/parametrit.inc");
js_showhide();

echo "<font class='head'>".t("Verottomat korvaukset")."</font><hr><br>";

echo "<form method='post'>
<input type='hidden' name='tee' value ='NAYTA'>";

echo "<table>";

if (!isset($vv)) $vv = date("Y");

echo "<tr>";
echo "<th>".t("Valitse vuosi")."</th>";
echo "<td>";

$sel = array();
$sel[$vv] = "SELECTED";

echo "<select name='vv'>";
for ($i = date("Y"); $i >= date("Y")-4; $i--) {
	echo "<option value='$i' $sel[$i]>$i</option>";
}
echo "</select>";

echo "<td class='back' style='text-align:bottom;'><input type = 'submit' value = '".t("Näytä")."'></td>";
echo "</tr>";

echo "</table>";

echo "</form><br>";

if($tee == "NAYTA") {
	
	$query = "	SELECT if(kuka.nimi IS NULL, lasku.laatija, kuka.nimi) nimi, if(tuote.tuotteen_parametrit=50, 'Päivärahat  ja ateriakorvaukset', 'Verovapaa kilometrikorvaus') laji, avg(tilausrivi.hinta) hinta, sum(tilausrivi.kpl) kpl, sum(tilausrivi.rivihinta) yhteensa, tuote.tuotteen_parametrit, lasku.laatija
				FROM lasku
				JOIN tilausrivi ON tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus
				LEFT JOIN kuka ON kuka.yhtio=lasku.yhtio and kuka.kuka=lasku.laatija
				JOIN tuote ON tuote.yhtio=lasku.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.tuotetyyppi IN ('A', 'B') and tuote.tuotteen_parametrit IN ('50', '56')
				WHERE lasku.yhtio='$kukarow[yhtio]' and tila = 'Y' and tilaustyyppi = 'M' and tapvm>= '$vv-01-01' and tapvm<= '$vv-12-31 59:59:59'
				GROUP BY lasku.laatija, tuote.tuotteen_parametrit";
	$result = mysql_query($query) or pupe_error($query);
	//echo $query;
	$edNimi = md5(uniqid());
	
	if(mysql_num_rows($result) > 0) {
		echo "	<table>
				<tr>
					<th>".t("Kuka")."</th><th>".t("Verokodi")."</th><th width='300'>".t("Korvaus")."</th><th>".t("Kappaletta")."</th><th>".t("Hinta")."</th><th>".t("Yhteensä")."</th>
				</tr>";
		$summat = array();
		$kappaleet = array();
		while($row = mysql_fetch_array($result)) {
			$nimi = "";
			if($edNimi != $row["nimi"]) {
				if(count($summat) > 0) {
					echo "	
							<tr>
								<td class='back'>&nbsp;</td>
							</tr>";
				}
				$nimi = "<font class='message'>$row[nimi]</font>";
			}
 			
			echo "	<tr>
						<td>$nimi</td>
						<td>$row[tuotteen_parametrit]</td>
						<td>$row[laji]</td>
						<td align='right'>".number_format($row["kpl"], 0, ',', ' ')."</td>
						<td align='right'>".number_format($row["hinta"], 2, ',', ' ')."</td>
						<td align='right'>".number_format($row["yhteensa"], 2, ',', ' ')."</td>
					</tr>";
			
			// erittely
			$query = "	SELECT tilausrivi.tuoteno, tuote.nimitys, avg(tilausrivi.hinta) hinta, sum(tilausrivi.kpl) kpl, sum(tilausrivi.rivihinta) yhteensa
						FROM lasku
						JOIN tilausrivi ON tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus
						LEFT JOIN kuka ON kuka.yhtio=lasku.yhtio and kuka.kuka=lasku.laatija
						JOIN tuote ON tuote.yhtio=lasku.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.tuotetyyppi IN ('A', 'B') and tuote.tuotteen_parametrit = '$row[tuotteen_parametrit]'
						WHERE lasku.yhtio='$kukarow[yhtio]' and tila = 'Y' and tilaustyyppi = 'M' and tapvm>= '$vv-01-01' and tapvm<= '$vv-12-31 59:59:59' and lasku.laatija = '$row[laatija]'
						GROUP BY tuote.tuoteno";
			$eres = mysql_query($query) or pupe_error($query);
			while($erow = mysql_fetch_array($eres)) {
				echo "	<tr>
							<td></td>
							<td></td>
							<td><font class='info'>$erow[tuoteno] - $erow[nimitys]</font></td>
							<td align='right'><font class='info'>".number_format($erow["kpl"], 0, ',', ' ')."</font></td>
							<td align='right'><font class='info'>".number_format($erow["hinta"], 2, ',', ' ')."</font></td>
							<td align='right'><font class='info'>".number_format($erow["yhteensa"], 2, ',', ' ')."</font></td>
						</tr>";
			}				
					
			$edNimi = $row["nimi"];
			$summat[$row["tuotteen_parametrit"]]+=$row["yhteensa"];
			$kappaleet[$row["tuotteen_parametrit"]]+=$row["kpl"];
			
		}		
		echo "		<tr>
					<td class='back' colspan='6'>&nbsp;</td>
				</tr>
					<tr>
					<td class='back'></td>
					<th class='back'>50</th>
					<th>".t("Päivärahat  ja ateriakorvaukset yhteensä")."</th>
					<td align='right'>".number_format($kappaleet[50], 2, ',', ' ')."</td>					
					<td colspan='2' align='right'>".number_format($summat[50], 2, ',', ' ')."</td>
				</tr>
				<tr>
					<td class='back'></td>
					<th class='back'>56</th>
					<th>".t("Verovapaa kilometrikorvaus yhteensä")."</th>
					<td align='right'>".number_format($kappaleet[56], 2, ',', ' ')."</td>
					<td colspan='2' align='right'>".number_format($summat[56], 2, ',', ' ')."</td>
				</tr>
				</table>";
	}
}

require ("../inc/footer.inc");

?>
