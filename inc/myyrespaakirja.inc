<?php

	echo "<font class='head'>".t("Suorituspäiväkirja")."</font><hr>";

	if (!isset($alvk)) $alvk = date("m", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($alvp)) $alvp = date("d", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($alvv)) $alvv = date("Y", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

	$ksel = array();
	$psel = array();
	$vsel = array();

	$ksel[$alvk] = "SELECTED";
	$psel[$alvp] = "SELECTED";
	$vsel[$alvv] = "SELECTED";

	echo "	<form name = 'valinta' action = 'raportit.php' method='post'>
			<input type = 'hidden' name = 'toim' value = 'myyrespaakirja'>
			<input type = 'hidden' name = 'tee' value = '1'>

			<table>
			<tr>
			<th>".t("Ajalta")."</th>
			<td><select name='alvv'>
			";

	for ($i = date("Y")+2; $i >= date("Y")-4; $i--) {
		echo "<option value='$i' $vsel[$i]>$i</option>";
	}

	echo "
		</select>
		<select name='alvk'>
		<option value = '00'>".t("koko vuosi")."
		<option value = '01' $ksel[01]>01</option>
		<option value = '02' $ksel[02]>02</option>
		<option value = '03' $ksel[03]>03</option>
		<option value = '04' $ksel[04]>04</option>
		<option value = '05' $ksel[05]>05</option>
		<option value = '06' $ksel[06]>06</option>
		<option value = '07' $ksel[07]>07</option>
		<option value = '08' $ksel[08]>08</option>
		<option value = '09' $ksel[09]>09</option>
		<option value = '10' $ksel[10]>10</option>
		<option value = '11' $ksel[11]>11</option>
		<option value = '12' $ksel[12]>12</option>
		</select>
		<select name='alvp'>
		<option value = '00'>".t("koko kuukausi")."
		<option value = '01' $psel[01]>01</option>
		<option value = '02' $psel[02]>02</option>
		<option value = '03' $psel[03]>03</option>
		<option value = '04' $psel[04]>04</option>
		<option value = '05' $psel[05]>05</option>
		<option value = '06' $psel[06]>06</option>
		<option value = '07' $psel[07]>07</option>
		<option value = '08' $psel[08]>08</option>
		<option value = '09' $psel[09]>09</option>
		<option value = '10' $psel[10]>10</option>
		<option value = '11' $psel[11]>11</option>
		<option value = '12' $psel[12]>12</option>
		<option value = '13' $psel[13]>13</option>
		<option value = '14' $psel[14]>14</option>
		<option value = '15' $psel[15]>15</option>
		<option value = '16' $psel[16]>16</option>
		<option value = '17' $psel[17]>17</option>
		<option value = '18' $psel[18]>18</option>
		<option value = '19' $psel[19]>19</option>
		<option value = '20' $psel[20]>20</option>
		<option value = '21' $psel[21]>21</option>
		<option value = '22' $psel[22]>22</option>
		<option value = '23' $psel[23]>23</option>
		<option value = '24' $psel[24]>24</option>
		<option value = '25' $psel[25]>25</option>
		<option value = '26' $psel[26]>26</option>
		<option value = '27' $psel[27]>27</option>
		<option value = '28' $psel[28]>28</option>
		<option value = '29' $psel[29]>29</option>
		<option value = '30' $psel[30]>30</option>
		<option value = '31' $psel[31]>31</option>
		</select></td>
		</tr>
		<tr>";

		// haetaan kaikki yrityksen rahatilit mysql muodossa
		$query  = "	SELECT oletus_rahatili, nimi, tilino
					FROM yriti
					WHERE yhtio = '$kukarow[yhtio]'
					AND kaytossa = ''
					AND oletus_rahatili != ''";
		$ratire = mysql_query($query) or pupe_error($query);

		echo "<th>".t("Pankkitili")."</th>";
		echo "<td>";
		echo "<select name='rahatili'>";
		echo "<option value=''>".t("Kaikki")."</option>";

		$psel = array();
		$psel[$rahatili] = "SELECTED";

		while ($ratiro = mysql_fetch_array($ratire)) {
			$rahatilisel = $ratiro["oletus_rahatili"];
			echo "<option value='$ratiro[oletus_rahatili]' $psel[$rahatilisel]>$ratiro[nimi] ($ratiro[tilino])</option>";
		}

		echo "
		</td>
      	<td class='back'><input type = 'submit' value = '".t("Näytä")."'></td></tr></table></form><br><br>";

	if ($tee == "1") {

		if ($rahatili != "") {
			$rahatili_lisa = " and oletus_rahatili = '$rahatili' ";
		}
		else {
			$rahatili_lisa = "";
		}
		// haetaan kaikki yrityksen rahatilit mysql muodossa
		$query  = "	SELECT concat(group_concat(distinct concat('\'',oletus_rahatili) SEPARATOR '\', '),'\'') rahatilit
					FROM yriti
					WHERE yhtio = '$kukarow[yhtio]'
					and kaytossa = ''
					$rahatili_lisa
					and oletus_rahatili != ''";
		$ratire = mysql_query($query) or pupe_error($query);
		$ratiro = mysql_fetch_array($ratire);

		if ($ratiro["rahatilit"] != "") {

			$lisa = "";
			$jarj = "tiliointi.tapvm, tiliointi.summa";

			if ($alvk == 0) {
		        	echo "<font class='head'>".t("Suorituspäiväkirja vuodelta")." $alvv</font><hr>";
					$lisa = "LEFT(tiliointi.tapvm,4) = '$alvv'";
			}
			else {
				if ($alvp == 0) {
					echo "<font class='head'>".t("Suorituspäiväkirja kaudelta")." $alvv-$alvk</font><hr>";
					$lisa = "LEFT(tiliointi.tapvm,7) = '$alvv-$alvk'";
				}
				else {
					echo "<font class='head'>".t("Suorituspäiväkirja päivältä")." $alvv-$alvk-$alvp</font><hr>";
					$lisa = "tiliointi.tapvm = '$alvv-$alvk-$alvp'";
				}
			}

			$orderby = "o_pvm, o_sum";

			if ($order == "nimi") {
				$orderby = "nimi, $orderby";
			}
			elseif ($order == "laskunro") {
				$orderby = "laskunro, $orderby";
			}
			elseif ($order == "o_sum") {
				$orderby = "o_sum, $orderby";
			}

			$summa = 0;

			$query = "	SELECT
						min(lasku.nimi) nimi,
						min(lasku.laskunro) laskunro,
						group_concat(tiliointi.tapvm ORDER BY tiliointi.tunnus) tapvm,
						group_concat(tiliointi.summa ORDER BY tiliointi.tunnus) summa,
						sum(tiliointi.summa) sum_summa,
						min(tiliointi.ltunnus) ltunnus,
						min(tiliointi.tapvm) o_pvm,
						max(tiliointi.summa) o_sum
						FROM tiliointi
						JOIN lasku ON tiliointi.yhtio = lasku.yhtio and tiliointi.ltunnus = lasku.tunnus and lasku.tila = 'U' and lasku.alatila = 'X'
						WHERE tiliointi.yhtio  = '$kukarow[yhtio]'
						and tiliointi.tilino  IN ($ratiro[rahatilit])
						and tiliointi.korjattu = ''
						and	$lisa
						GROUP BY lasku.tunnus
						ORDER BY $orderby";

			$result = mysql_query($query) or pupe_error($query);

			echo "<table><tr>";

			echo "<th><a href='${palvelin2}raportit.php?toim=myyrespaakirja&tee=$tee&alvv=$alvv&alvk=$alvk&alvp=$alvp&order=nimi'>".t("Nimi")."</a></th>";
			echo "<th><a href='${palvelin2}raportit.php?toim=myyrespaakirja&tee=$tee&alvv=$alvv&alvk=$alvk&alvp=$alvp&order=laskunro'>".t("Laskunro")."</a></th>";
			echo "<th><a href='${palvelin2}raportit.php?toim=myyrespaakirja&tee=$tee&alvv=$alvv&alvk=$alvk&alvp=$alvp&order=o_pvm'>".t("Pvm")."</a></th>";
			echo "<th><a href='${palvelin2}raportit.php?toim=myyrespaakirja&tee=$tee&alvv=$alvv&alvk=$alvk&alvp=$alvp&order=o_sum'>".t("Summa")."</a></th>";

			while ($trow = mysql_fetch_assoc($result)) {

				echo "<tr class='aktiivi'>";
				echo "<td><a name='A_$trow[ltunnus]' href = 'muutosite.php?tee=E&tunnus=$trow[ltunnus]&lopetus=$PHP_SELF////toim=$toim//tee=$tee//alvv=$alvv//alvk=$alvk//alvp=$alvp//order=$order///A_$trow[ltunnus]'>$trow[nimi]</a></td>";
				echo "<td>$trow[laskunro]</td>";

				echo "<td>";

				foreach(explode(",", $trow["tapvm"]) as $a) {
					echo tv1dateconv($a)."<br>";
				}

				echo "</td>";

				echo "<td align='right'>";

				foreach(explode(",", $trow["summa"]) as $a) {
					echo $a."<br>";
				}

				echo "</td>";
				echo "</tr>";

				$summa+=$trow['sum_summa'];
			}

			echo "<tr><th colspan='3'>".t("Yhteensä")."</th>";
			echo "<th>";
			printf("%.2f", $summa);
			echo "</th></tr>";
			echo "</table><br>";
		}
		else {
			echo "Sopivia tilejä ei löytynyt!";
		}
	}
?>