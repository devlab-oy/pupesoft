<?php
	echo "<font class='head'>".t("Laskut per tila")."</font><hr>";
	if (!isset($erittely)) {
		$query = "	SELECT tila, alatila, round(sum(summa * valuu.kurssi),2) summa, count(*) kpl
					FROM lasku use index (tila_index)
					LEFT JOIN valuu ON valuu.yhtio = lasku.yhtio and lasku.valkoodi	= valuu.nimi
					WHERE lasku.yhtio	= '$kukarow[yhtio]' 
					and lasku.tila in ('H','M','P','Q')
					GROUP BY tila, alatila";
		$result = mysql_query($query) or pupe_error($query);
		
		echo "<table>";
		echo "<tr><th>".t("Tila")."</th><th>".t("Summa")."</th><th>".t("Kpl")."</th></tr>";

		while ($trow=mysql_fetch_array($result)) {
			$laskutyyppi = $trow['tila'];
			$alatila     = $trow['alatila'];
			require "inc/laskutyyppi.inc";

			echo "<tr>";
			echo "<td>".t("$laskutyyppi")." ".t("$alatila")."</td>";
			echo "<td style='text-align: right;'><a href='raportit.php?toim=laskutila&erittely=$trow[tila]&alatila=$trow[alatila]'>$trow[summa]</a></td>";
			echo "<td style='text-align: right;'>$trow[kpl]</td>";
			echo "</tr>";
		}

		echo "</table><br>";
	}
	if (isset($erittely)) {
		$query = "SELECT tapvm, kapvm, erpcm, if (comments!='',concat_ws('<br>', nimi, comments),nimi) nimi, postitp, round(summa * vienti_kurssi, 2) 'kotisumma', summa, valkoodi, ebid, tunnus
			FROM lasku
			WHERE yhtio = '$kukarow[yhtio]' and tila = '$erittely' and alatila = '$alatila'
			ORDER BY tapvm";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table><tr>";
		for ($i = 1; $i < mysql_num_fields($result)-1; $i++) {
			echo "<th>" . t(mysql_field_name($result,$i))."</th>";
		}
		echo "</tr>";

		while ($trow=mysql_fetch_array ($result)) {

			echo "<tr>";			
			echo "<td>".tv1dateconv($trow["tapvm"])."</td>";
			
			if ($trow["kapvm"] != "0000-00-00") {
				echo "<td>".tv1dateconv($trow["kapvm"])."</td>";
			}
			else {
				echo "<td></td>";
			}
		
			echo "<td>".tv1dateconv($trow["erpcm"])."</td>";
			echo "<td>$trow[nimi]</td>";
			echo "<td>$trow[postitp]</td>";
			echo "<td style='text-align: right;'>$trow[kotisumma] $yhtiorow[valkoodi]</td>";
			echo "<td style='text-align: right;'>$trow[summa] $trow[valkoodi]</td>";
			
			// tehdään lasku linkki
			echo "<td>". ebid($trow['tunnus']) ."</td>";			
		}
		echo "</table><br>";
	}
?>
