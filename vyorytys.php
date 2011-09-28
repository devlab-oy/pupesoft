	<?php

	require("inc/parametrit.inc");

	enable_ajax();

	if ($livesearch_tee == "TILIHAKU") {
		livesearch_tilihaku();
		exit;
	}

	$MONTH_ARRAY = array(1=>t('Tammikuu'),t('Helmikuu'),t('Maaliskuu'),t('Huhtikuu'),t('Toukokuu'),t('Kesäkuu'),t('Heinäkuu'),t('Elokuu'),t('Syyskuu'),t('Lokakuu'),t('Marraskuu'),t('Joulukuu'));

	echo "<font class='head'>".t("Tilisaldon vyörytys")."</font><hr>\n";

	$formi = 'tosite';
	$kentta = 'tpp';

	echo "<br>\n";

	echo "<form name='tosite' action='$PHP_SELF' method='post' autocomplete='off'>\n";
	echo "<input type='hidden' name='tee' value='TARKISTA'>\n";
	echo "<table>";

	echo "<tr><th>".t("Valitse tilikausi")."</th>";

 	$query = "	SELECT *
				FROM tilikaudet
				WHERE yhtio = '$kukarow[yhtio]'
				ORDER BY tilikausi_alku desc";
	$vresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='tkausi' onchange='submit();'>";

	while ($vrow = mysql_fetch_assoc($vresult)) {
		$sel="";

		if (!isset($savrow)) {
			$savrow	= $vrow;
		}

		if ($tkausi == $vrow["tunnus"]) {
			$sel 	= "selected";
			$savrow	= $vrow;
		}

		echo "<option value = '$vrow[tunnus]' $sel>".tv1dateconv($vrow["tilikausi_alku"])." - ".tv1dateconv($vrow["tilikausi_loppu"])."</option>";
	}
	echo "</select></td></tr>";

	$alku = str_replace("-", "", substr($savrow["tilikausi_alku"], 0, 7));
	$lopp = str_replace("-", "", substr($savrow["tilikausi_loppu"], 0, 7));

	echo "<tr><th>".t("Valitse kuukausi")."</th>
			<td><select name='alvk'>
			<option value=''>".t("Koko tilikausi")."</option>";

	for ($a=$alku; $a<=$lopp; $a++) {
		if (substr($a, -2) == "13") {
			$a+=88;
		}

		if ($alvk == $a) $sel = "SELECTED";
		else $sel = "";

		echo "<option value = '$a' $sel>".substr($a, 0, 4)." - ".$MONTH_ARRAY[(int) substr($a, -2)]."</option>";
	}


	echo "<tr><th>".t("Tilin alku")."</th><td width='200' valign='top'>".livesearch_kentta("tosite", "TILIHAKU", "tilinalku", 170, $tilinalku, "EISUBMIT")." $tilinimi</td></tr>";
	echo "<tr><th>".t("Tilin loppu")."</th><td width='200' valign='top'>".livesearch_kentta("tosite", "TILIHAKU", "tilinloppu", 170, $tilinloppu, "EISUBMIT")." $tilinimi</td></tr>";

	echo "<tr><th>".t("Tarkenne")."</th><td>";

	$monivalintalaatikot = array("KUSTP");
	$noautosubmit = TRUE;

	require ("tilauskasittely/monivalintalaatikot.inc");

	echo "</td></tr></table>\n";
	echo "<br><input type = 'submit' value = '".t("Näytä")."'></form><br><br>";

	if ($tee == "TARKISTA") {

		$tkausi = (int) $tkausi;

		$query = "	SELECT tilikausi_alku, tilikausi_loppu
					FROM tilikaudet
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus = $tkausi";
		$vresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($vresult) == 1) {
			$tilikausirow = mysql_fetch_assoc($vresult);
		}
		else {
			echo "<font class='error'>".t("Tuntematon tilikausi")."</font>";
			exit;
		}

		$lisa1 = "";
		$where = "";
		$kustp = "";
		$groupby = "";

		if ($alvk != "") {
			$alvy = (int) substr($alvk, 0, 4);
			$alvm = (int) substr($alvk, 4, 2);
			$alvd = date("d", mktime(0, 0, 0, $alvm+1, 0, $alvy));

			echo "<font class='message'>".t("Vain kuukausi").": $alvy - ".$MONTH_ARRAY[$alvm]."</font><br>";

			$lisa1 = " sum(if(tiliointi.tapvm >= '$alvy-$alvm-01' and tiliointi.tapvm <= '$alvy-$alvm-$alvd', tiliointi.summa, 0)) Saldo";
			$where = " and tiliointi.tapvm <= '$alvy-$alvm-$alvd'";

			$tilikausirow["tilikausi_alku"] =  $alvy. "-". sprintf("%02s",$alvm) . "-01";
			$tilikausirow["tilikausi_loppu"] =  $alvy. "-". sprintf("%02s",$alvm) . "-" . $alvd;
		}
		else {
			$lisa1 = " sum(tiliointi.summa) Saldo";
		}

		echo "<font class='head'>".t("Kumulatiivinen saldo")." ".tv1dateconv($tilikausirow["tilikausi_alku"])." - ".tv1dateconv($tilikausirow["tilikausi_loppu"])."</font><hr>";

		if ($tilinalku == '' and $tilinloppu == '') {
			echo "<font class='error'>".t("Pitää syöttää ainakin yksi tili")."</font>";
		}
		else {

			if (count($mul_kustp) > 0 and $mul_kustp[0] !='') {
				$where .= " and tiliointi.kustp in (";
				foreach ($mul_kustp as $kustp) {
					$where .= "'$kustp',";
				}
				$where = substr($where, 0, -1); // vika pilkku pois
				$where .= ")";
				$groupby = ", kustp";
			}

			if (trim($tilinloppu) == '') {
				$tilinloppu = $tilinalku;
			}

			if (trim($tilinalku) == '') {
				$tilinalku = $tilinloppu;
			}

			if (trim($tilinalku) > trim($tilinloppu)) {
				$swap = $tilinalku;
				$tilinalku = $tilinloppu;
				$tilinloppu = $swap;
			}

			$query = "	SELECT tiliointi.tilino, group_concat(distinct tiliointi.kustp) kustp, $lisa1, sum(tiliointi.summa) tilisaldo
						FROM tiliointi
						WHERE tiliointi.yhtio  = '$kukarow[yhtio]'
						AND tiliointi.korjattu = ''
						AND tiliointi.tapvm   >= '$tilikausirow[tilikausi_alku]'
						AND tiliointi.tapvm   <= '$tilikausirow[tilikausi_loppu]'
						AND tiliointi.tilino between '$tilinalku' AND '$tilinloppu'
						$where
						GROUP BY tilino $groupby";
			$result = mysql_query($query) or pupe_error($query);
			$laskuri = mysql_num_rows($result)+2;

			echo "<form name='tosite' action='tosite.php' method='post' autocomplete='off'>\n";
			echo "<input type='hidden' name='tee' value='I'>\n";
			echo "<input type='hidden' name='maara' value='$laskuri'>\n";

			$i=1;

			echo "<table>";
			echo "<tr>";
			echo "<th>".t("Tili")."</th>";
			echo "<th>".t("Saldo")."</th>";
			if ($kustp != '') {
				echo "<th>".t("Kustannuspaikka")."</th>";
			}
			echo "</tr>";

			while ($trow = mysql_fetch_array($result)) {

				echo "<tr>";
				echo "<td>$trow[tilino]</td>";
				echo "<td>$trow[Saldo]</td>";

				if ($kustp != '') {

					$query2 = "SELECT nimi, koodi FROM kustannuspaikka WHERE kustannuspaikka.yhtio = '$kukarow[yhtio]' AND kustannuspaikka.tunnus = '$trow[kustp]'";
					$result2 = mysql_query($query2) or pupe_error($query2);
					$tarkenne = mysql_fetch_assoc($result2);

					if ($tarkenne[nimi] == '') {
						$tarkenne[nimi] = t("Ei kustannuspaikkaa");
					}

					echo "<td>$tarkenne[koodi] $tarkenne[nimi]</td>";
				}

				echo "</tr>";

				$kumulus = $kumulus + $trow["Saldo"];

				echo "<input type='hidden' name='tpp' value='".date("d")."'>\n";
				echo "<input type='hidden' name='tpk' value='".date("m")."'>\n";
				echo "<input type='hidden' name='tpv' value='".date("Y")."'>\n";

				echo "<input type='hidden' name='itili[$i]' value='$trow[tilino]'>\n";
				echo "<input type='hidden' name='isumma[$i]' value='".($trow["tilisaldo"]*-1)."'>\n";
				echo "<input type='hidden' name='ikustp[$i]' value='$trow[kustp]'>\n";
				echo "<input type='hidden' name='iselite[$i]' value='".t("Vyörytys")."'>\n";
				$i++;
			}

			echo "<input type='hidden' name='summa' value='$kumulus'>\n";
			echo "</table>";
			echo "<br><input type='submit' value='".t("Tee tosite")."'></form><br><br>";
		}
	}

	require ("inc/footer.inc");
?>