<?php
		echo "".t("Laskujen summahaku")."<hr>";
		$summa1 = str_replace( ",", ".", $summa1);
                $summa2 = str_replace( ",", ".", $summa2);

		if (strlen($summa2) == 0) {
			$summa2 = $summa1;
		}


		$summa1 += 0;
		$summa2 += 0;

		if ($summa1 == $summa2) {
			$ehto = "summa = " . $summa1;
			$jarj = "tapvm desc";
		}
		else {
			$ehto = "summa >= " . $summa1 . " and summa <= " . $summa2;
			$jarj = "summa, tapvm";
		}

		$query = "SELECT tunnus, tapvm, CONCAT_WS(' ', nimi, nimitark, postino, postitp) Nimi, summa, tila
                          FROM lasku
                          WHERE yhtio = '$kukarow[yhtio]' and $ehto
                          ORDER BY $jarj";

		$result = mysql_query($query) or pupe_error($query);


		if (mysql_num_rows($result) > 100) {
			echo "<font class='message'>".t("Tuloksesksi tuli yli 100 laskua. Anna pienempi väli")."</font>";
			exit;
		}
		echo "<table><tr>";
		for ($i = 1; $i < mysql_num_fields($result); $i++) {
                        echo "<th>" . t(mysql_field_name($result,$i)) . "</th>";
                }
                while ($trow=mysql_fetch_array ($result)) {
			echo "<tr>";
                        for ($i=1; $i<mysql_num_fields($result); $i++) {
				if ($i == 1) { // Linkki laskuntietoihin
					echo "<td><a href='muutosite.php?tee=E&tunnus=$trow[tunnus]'>";
                                        echo "$trow[$i]</td>";
				}
				else {
					if ($i == 4) { // Laskun tila
						$laskutyyppi = $trow[$i];
						require "inc/laskutyyppi.inc";
						echo "<td>".t("$laskutyyppi")."</td>";
					}
					else {
						echo "<td>$trow[$i]</td>";
					}
				}
                        }
                        echo "</tr>";
                }
		echo "</table><br>";
?>