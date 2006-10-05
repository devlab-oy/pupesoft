<?php
	$useslave = 1;
	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Myynnit konserniyhtiöille")."</font><hr>";
	$summa=0;
	$kate=0;
	if ($tee=='X') {
		$query = "SELECT l.ytunnus, l.nimi, sum(arvo) summa, sum(kate) kate from asiakas a, lasku l
					WHERE a.yhtio = '$kukarow[yhtio]' and a.konserniyhtio!= '' and
						a.yhtio=l.yhtio and a.ytunnus=l.ytunnus and
						l.tila='U' and l.alatila='X' and
						l.tapvm <= '$vv-$kk-$pp' and l.tapvm >= '$yhtiorow[tilikausi_alku]'
					GROUP BY 1,2";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table><tr>";
		for ($i = 0; $i < mysql_num_fields($result); $i++) {
			echo "<th>" . t(mysql_field_name($result,$i))."</th>";
		}
		echo "</tr>";
		while ($trow = mysql_fetch_array($result)) {
			echo "<tr>";
			for ($i=0; $i<mysql_num_fields($result); $i++) {
				echo "<td>$trow[$i]</td>";
			}
			echo "</tr>";
			
			$summa += $trow["summa"];
			$kate += $trow["kate"];
		}
		echo "<tr><td></td><td></td><td>$summa</td><td>$kate</td></tr>";
		echo "</table>";
	}
	if ($tee == '') {
		// mikä kuu/vuosi nyt on
		$year = date("Y");
		$kuu  = date("n");
		// poimitaan erikseen edellisen kuun viimeisen päivän vv,kk,pp raportin oletuspäivämääräksi
		$ek_vv = date("Y",mktime(0,0,0,$kuu-1,1,$year));
		$ek_kk = date("n",mktime(0,0,0,$kuu-1,1,$year));
		$ek_pp = date("j",mktime(0,0,0,$kuu,0,$year));
		
		echo "<form name = 'valinta' action = '$PHP_SELF' method='post'>
				<input type = 'hidden' name = 'tee' value = 'X'>
				<table>
				<tr>
				<th>".t("Anna laskentapäivä pp-kk-vvvv")."</th>
				<td><input type = 'text' name = 'pp' value='$ek_pp' size=2> 
				<input type = 'text' name = 'kk' value='$ek_kk' size=2>
				<input type = 'text' name = 'vv' value='$ek_vv' size=4></td>
				</tr>
				<tr>
				<td><input type = 'submit' value = '".t("Laske")."'></td><td></td>
				</tr>
				</table>
				</form>";
		$formi = 'valinta';
		$kentta = 'kk';
	}
	require("../inc/footer.inc");
?>
