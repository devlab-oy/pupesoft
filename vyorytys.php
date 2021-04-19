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

	while ($vrow=mysql_fetch_assoc($vresult)) {
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


	echo "<tr><th>".t("Tili")."</th><td width='200' valign='top'>".livesearch_kentta("tosite", "TILIHAKU", "tilino", 170, $tilino, "EISUBMIT")." $tilinimi</td></tr>";
	echo "<tr><th>".t("Tarkenne")."</th><td>";

	$monivalintalaatikot = array("KUSTP", "KOHDE", "PROJEKTI");
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

			echo "<font class='head'>".t("Tilin %s saldo tilikaudelta", "", $tilino)." ".tv1dateconv($tilikausirow["tilikausi_alku"])." - ".tv1dateconv($tilikausirow["tilikausi_loppu"])."</font><hr>";
		}
		else {
			echo "<font class='error'>".t("Tuntematon tilikausi")."</font>";
			exit;
		}

		$lisa1 = "";
		$where = "";

		if ($alvk != "") {
			$alvy = (int) substr($alvk, 0, 4);
			$alvm = (int) substr($alvk, 4, 2);
			$alvd = date("d", mktime(0, 0, 0, $alvm+1, 0, $alvy));

			echo "<font class='message'>".t("Vain kuukausi").": $alvy - ".$MONTH_ARRAY[$alvm]."</font><br>";

			$lisa1 = " sum(if(tiliointi.tapvm >= '$alvy-$alvm-01' and tiliointi.tapvm <= '$alvy-$alvm-$alvd', tiliointi.summa, 0)) Saldo";
			$where = " and tiliointi.tapvm <= '$alvy-$alvm-$alvd'";
		}
		else {
			$lisa1 = " sum(tiliointi.summa) Saldo";
		}

		$query = "	SELECT $lisa1, sum(tiliointi.summa) Kumulatiivinen
					FROM tiliointi
					WHERE tiliointi.yhtio  = '$kukarow[yhtio]'
					and tiliointi.korjattu = ''
					and tiliointi.tapvm   >= '$tilikausirow[tilikausi_alku]'
					and tiliointi.tapvm   <= '$tilikausirow[tilikausi_loppu]'
					and tiliointi.tilino   = '$tilino'
					$where
					$lisa";
		$result = mysql_query($query) or pupe_error($query);
		$trow = mysql_fetch_assoc($result);
		
		echo "<table>";
		echo "<tr><th>".t("Tili").":</th><td>$tilino</td></tr>";
		echo "<tr><th>".t("Saldo").":</th><td>$trow[Saldo]</td></tr>";
		echo "<tr><th>".t("Kumulatiivinen saldo").":</th><td>$trow[Kumulatiivinen]</td></tr>";
		echo "</table>";
		
		echo "<form name='tosite' action='tosite.php' method='post' autocomplete='off'>\n";
		echo "<input type='hidden' name='tee' value='I'>\n";
						
		echo "<input type='hidden' name='maara' value='5'>\n";
		echo "<input type='hidden' name='tpp' value='".date("d")."'>\n";
		echo "<input type='hidden' name='tpk' value='".date("m")."'>\n";
		echo "<input type='hidden' name='tpv' value='".date("Y")."'>\n";
		echo "<input type='hidden' name='summa' value='".($trow["Kumulatiivinen"])."'>\n";
		echo "<input type='hidden' name='itili[1]' value='$tilino'>\n";
		echo "<input type='hidden' name='isumma[1]' value='".($trow["Kumulatiivinen"]*-1)."'>\n";
					
		echo "<br><input type='submit' value='".t("Tee tosite")."'></form><br><br>";
		
	}

	require ("inc/footer.inc");
?>