<?php

	require("../inc/parametrit.inc");

    echo "<font class='head'>".t("Tase/tuloslaskelma")."</font><hr>";

	if ($tee == "aja") {

		// Desimaalit
		$muoto = "%.". (int) $desi . "f";

		// Onko meillä lisärajoitteita??
		$lisa  = "";
		$lisa2 = "";

		if ($kustp != "") {
			$lisa .= " and kustp = '$kustp'";
			$lisa2 .= " and kustannuspaikka = '$kustp'";
		}
		if ($proj != "") {
			$lisa .= " and projekti = '$proj'";
			$lisa2 .= " and projekti = '$proj'";
		}
		if ($kohde != "") {
			$lisa .= " and kohde = '$kohde'";
			$lisa2 .= " and kohde = '$kohde'";
		}
		if ($plvk == '' or $plvv == '') {
			$plvv = substr($yhtiorow['tilikausi_alku'], 0, 4);
			$plvk = substr($yhtiorow['tilikausi_alku'], 5, 2);
		}

		if ($tyyppi == "1") {
			// Vastaavaa Varat
			$kirjain = "U";
			$aputyyppi = 1;
			$tilikarttataso = "ulkoinen_taso";
		}
		elseif ($tyyppi == "2") {
			// Vastattavaa Velat
			$kirjain = "U";
			$aputyyppi = 2;
			$tilikarttataso = "ulkoinen_taso";
		}
		elseif ($tyyppi == "3") {
			// Ulkoinen tuloslaskelma
			$kirjain = "U";
			$aputyyppi = 3;
			$tilikarttataso = "ulkoinen_taso";
		}
		else {
			// Sisäinen tuloslaskelma
			$kirjain = "S";
			$aputyyppi = 3;
			$tilikarttataso = "sisainen_taso";
		}

		$query = "	SELECT *
					FROM taso
					WHERE yhtio = '$kukarow[yhtio]' AND
					tyyppi = '$kirjain' AND
					LEFT(taso, 1) = '$aputyyppi' AND
					taso != ''
					ORDER BY taso";
		$tasores = mysql_query($query) or pupe_error($query);

		// edellinen taso
		$taso     = array();
		$tasonimi = array();
		$summa    = array();
		$kaudet   = array();

		$startmonth	= date("Ymd", mktime(0, 0, 0, $plvk, 1, $plvv));
		$endmonth 	= date("Ymd", mktime(0, 0, 0, $alvk, 1, $alvv));
		$annettualk = date("Y-m-d", mktime(0, 0, 0, $plvk, 1, $plvv));
		$annettuabu = date("Ym", mktime(0, 0, 0, $plvk, 1, $plvv));
		$totalloppu = date("Y-m-d", mktime(0, 0, 0, $alvk+1, 1, $alvv));

		if ($vertailued != "") {
			$totalalku  = date("Y-m-d", mktime(0, 0, 0, $plvk, 1, $plvv-1));
		}
		else {
			$totalalku = date("Y-m-d", mktime(0, 0, 0, $plvk, 1, $plvv));
		}

		$alkuquery = "";
		$budjejoin = "";

		for ($i = $startmonth;  $i <= $endmonth;) {

			$alku    = date("Y-m-d", mktime(0, 0, 0, substr($i,4,2), substr($i,6,2),  substr($i,0,4)));
			$loppu   = date("Y-m-d", mktime(0, 0, 0, substr($i,4,2), date("t", mktime(0, 0, 0, substr($i,4,2), substr($i,6,2),  substr($i,0,4))),  substr($i,0,4)));
			$bukausi = date("Ym", mktime(0, 0, 0, substr($i,4,2), substr($i,6,2),  substr($i,0,4)));

			$headny = date("Y/m",   mktime(0, 0, 0, substr($i,4,2), substr($i,6,2),  substr($i,0,4)));

			$alkuquery .= " sum(if(tiliointi.tapvm >= '$alku' and tiliointi.tapvm <= '$loppu', tiliointi.summa, 0)) '$headny', \n";
			$kaudet[] = $headny;

			if ($vertailued != "") {
				$alku_ed  = date("Y-m-d", mktime(0, 0, 0, substr($i,4,2), substr($i,6,2),  substr($i,0,4)-1));
				$loppu_ed = date("Y-m-d", mktime(0, 0, 0, substr($i,4,2), date("t", mktime(0, 0, 0, substr($i,4,2), substr($i,6,2),  substr($i,0,4))),  substr($i,0,4)-1));
				$headed   = date("Y/m",   mktime(0, 0, 0, substr($i,4,2), substr($i,6,2),  substr($i,0,4)-1));

				$alkuquery .= " sum(if(tiliointi.tapvm >= '$alku_ed' and tiliointi.tapvm <= '$loppu_ed', tiliointi.summa, 0)) '$headed', \n";
				$kaudet[] = $headed;
			}

			// sisäisessä tuloslaskelmassa voidaan joinata budjetti
			if ($vertailubu != "" and $kirjain == "S") {
				//$alkuquery .= " (SELECT sum(budjetti.summa) 'budj $headny' FROM budjetti USE INDEX (yhtio_taso_kausi) WHERE budjetti.yhtio = tili.yhtio and budjetti.taso = tili.$tilikarttataso and budjetti.kausi = '$bukausi' $lisa2), \n";
				$alkuquery .= " if(budjetti.kausi = '$bukausi', min(budjetti.summa), 0) 'budj $headny', ";
//				$alkuquery .= " if budjetti.kausi 'budj $headny', ";

				$budjejoin = " LEFT JOIN budjetti USE INDEX (yhtio_taso_kausi) ON (budjetti.yhtio = tili.yhtio and budjetti.taso = tili.$tilikarttataso and budjetti.kausi >= '$annettuabu' $lisa2) ";
				$kaudet[] = "budj $headny";
			}

			$i = date("Ymd",mktime(0, 0, 0, substr($i,4,2)+1, 1,  substr($i,0,4)));
		}

		// yhteensäotsikkomukaan
		$kaudet[] = "Total";

		while ($tasorow = mysql_fetch_array($tasores)) {

			// millä tasolla ollaan (1,2,3,4,5,6)
			$tasoluku = strlen($tasorow["taso"]);

			// tasonimi talteen (rightpäddätään Ö:llä, niin saadaan oikeaan järjestykseen)
			$apusort = str_pad($tasorow["taso"], 6, "Ö");
			$tasonimi[$apusort] = $tasorow["nimi"];

			// pilkotaan taso osiin
			$taso = array();
			for ($i=0; $i < $tasoluku; $i++) {
				$taso[$i] = substr($tasorow["taso"], 0, $i+1);
			}

			$query = "	SELECT $alkuquery
						sum(if(tiliointi.tapvm >= '$annettualk' and tiliointi.tapvm <= '$totalloppu', tiliointi.summa, 0)) 'Total'
					 	FROM tili
						LEFT JOIN tiliointi USE INDEX (yhtio_tilino_tapvm) ON (tiliointi.yhtio = tili.yhtio and tiliointi.tilino = tili.tilino and tiliointi.korjattu = '' and tiliointi.tapvm >= '$totalalku' and tiliointi.tapvm < '$totalloppu' $lisa)
						$budjejoin
						WHERE tili.yhtio = '$kukarow[yhtio]' AND
						tili.$tilikarttataso = '$tasorow[taso]'
						group by tili.$tilikarttataso";
			$tilires = mysql_query($query) or pupe_error($query);

			while ($tilirow = mysql_fetch_array ($tilires)) {
				// summataan kausien saldot
				foreach ($kaudet as $kausi) {
					// summataan kaikkia pienempiä summaustasoja
					for ($i = $tasoluku - 1 ; $i >= 0; $i--) {
						$summa[$kausi][$taso[$i]] += $tilirow[$kausi];
					}
				}
			}

		}


		echo "<table>";

		// printataan headerit
		echo "<tr><td class='back' colspan='2'></td>";

		foreach ($kaudet as $kausi) {
			echo "<td class='tumma' align='right' valign='bottom'>$kausi</td>";
		}
		echo "</tr>\n";

		// sortataan array indexin (tason) mukaan
		ksort($tasonimi);

		// loopataan tasot läpi
		foreach ($tasonimi as $key => $value) {

			$key = str_replace("Ö", "", $key); // Ö-kirjaimet pois

			// tulostaan rivi vain jos se kuuluu rajaukseen
			if (strlen($key) <= $rtaso) {

				$class = "";
				$tulos = 0;

				// laitetaan ykkös ja kakkostason rivit tummalla selkeyden vuoksi
				if (strlen($key) < 3) $class = "tumma";

				$rivi  = "<tr>";

				//$rivi .= "<th nowrap><a href='".$palvelin2."tasomuutos.php?taso=$key&tyyppi=$mty&tee=muuta'>$key</a></th>";
				//$rivi .= "<th nowrap><a href='".$palvelin2."tasomuutos.php?taso=$key&edtaso=$edkey&tee=lisaa'>Uusi taso</a></th>";

				$rivi .= "<th nowrap>$key</th>";
				$rivi .= "<th nowrap>$value</th>";

				foreach ($kaudet as $kausi) {

					$query = "select summattava_taso from taso where yhtio = '$kukarow[yhtio]' and taso = '$key' and summattava_taso != '' and tyyppi = '$kirjain'";
					$summares = mysql_query($query) or pupe_error($query);

					if ($summarow = mysql_fetch_array ($summares)) {
						$summa[$kausi][$key] = $summa[$kausi][$key] + $summa[$kausi][$summarow["summattava_taso"]];
					}

					// formatoidaan luku toivottuun muotoon
					$apu = sprintf($muoto, $summa[$kausi][$key] * -1 / $tarkkuus);

					if ($apu == 0) {
						$apu = ""; // nollat spaseiks
					}
					else {
						$tulos++; // summaillaan tätä jos meillä oli rivillä arvo niin osataan tulostaa
					}

					$rivi .= "<td class='$class' align='right' nowrap>$apu</td>";
				}
				$rivi .= "</tr>\n";

				// kakkostason jälkeen aina yks tyhjä rivi.. paitsi jos otetaan vain kakkostason raportti
				if (strlen($key) == 2 and $rtaso > 2) {
					$rivi .= "<tr><td class='back'>&nbsp;</td></tr>";
				}

				// jos jollain kaudella oli summa != 0 niin tulostetaan rivi
				if ($tulos > 0) {
					echo $rivi;
				}
			}

			$edkey = $key;
		}

		echo "</table>";

	}

	// tehdään käyttöliittymä, näytetään aina
	$sel = array();
	if ($tyyppi == "") $tyyppi = "4";
	$sel[$tyyppi] = "SELECTED";

	echo "<br>";
	echo "	<form action = 'tuloslaskelma.php' method='post'>
			<input type = 'hidden' name = 'tee' value = 'aja'>
			<table>";

	echo "	<tr>
			<th>".t("Tyyppi")."</th>
			<td>";

	echo "	<select name = 'tyyppi'>
			<option $sel[4] value='4'>".t("Sisäinen tuloslaskelma")."</option>
			<option $sel[3] value='3'>".t("Ulkoinen tuloslaskelma")."</option>
			<option $sel[1] value='1'>".t("Vastaavaa")." (".t("Varat").")</option>
			<option $sel[2] value='2'>".t("Vastattavaa")." (".t("Velat").")</option>
			</select>";

	echo "</td>
			</tr>";

	if (!isset($plvv)) $plvv = substr($yhtiorow['tilikausi_alku'], 0, 4);
	if (!isset($plvk)) $plvk = substr($yhtiorow['tilikausi_alku'], 5, 2);

	echo "	<th>".t("Alkukausi")."</th>
			<td><select name='plvv'>";

	$sel = array();
	$sel[$plvv] = "SELECTED";

	for ($i = date("Y"); $i >= date("Y")-4; $i--) {
		echo "<option value='$i' $sel[$i]>$i</option>";
	}

	echo "</select>";

	$sel = array();
	$sel[$plvk] = "SELECTED";

	echo "<select name='plvk'>
			<option $sel[1] value = '1'>01</option>
			<option $sel[2] value = '2'>02</option>
			<option $sel[3] value = '3'>03</option>
			<option $sel[4] value = '4'>04</option>
			<option $sel[5] value = '5'>05</option>
			<option $sel[6] value = '6'>06</option>
			<option $sel[7] value = '7'>07</option>
			<option $sel[8] value = '8'>08</option>
			<option $sel[9] value = '9'>09</option>
			<option $sel[10] value = '10'>10</option>
			<option $sel[11] value = '11'>11</option>
			<option $sel[12] value = '12'>12</option>
			</select></td></tr>";

	echo "<tr>
		<th>".t("Loppukausi")."</th>
		<td><select name='alvv'>";

	$sel = array();
	if ($alvv == "") $alvv = date("Y");
	$sel[$alvv] = "SELECTED";

	for ($i = date("Y")+1; $i >= date("Y")-4; $i--) {
		echo "<option value='$i' $sel[$i]>$i</option>";
	}

	$sel = array();
	if ($alvk == "") $alvk = date("n");
	$sel[$alvk] = "SELECTED";

	echo "</select>";

	echo "<select name='alvk'>
			<option $sel[1] value = '1'>01</option>
			<option $sel[2] value = '2'>02</option>
			<option $sel[3] value = '3'>03</option>
			<option $sel[4] value = '4'>04</option>
			<option $sel[5] value = '5'>05</option>
			<option $sel[6] value = '6'>06</option>
			<option $sel[7] value = '7'>07</option>
			<option $sel[8] value = '8'>08</option>
			<option $sel[9] value = '9'>09</option>
			<option $sel[10] value = '10'>10</option>
			<option $sel[11] value = '11'>11</option>
			<option $sel[12] value = '12'>12</option>
			</select></td></tr>";

	echo "<tr><th>".t("Vain kustannuspaikka")."</th>";

	$query = "	SELECT tunnus, nimi
				FROM kustannuspaikka
				WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'K'
				ORDER BY nimi";
	$vresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='kustp'><option value=''>".t("Ei valintaa")."</option>";

	while ($vrow=mysql_fetch_array($vresult)) {
		$sel="";
		if ($trow[$i] == $vrow['tunnus'] or $kustp == $vrow["tunnus"]) {
			$sel = "selected";
		}
		echo "<option value = '$vrow[tunnus]' $sel>$vrow[nimi]</option>";
	}

	echo "</select></td>";
	echo "</tr>";
	echo "<tr><th>".t("Vain kohde")."</th>";

	$query = "	SELECT tunnus, nimi
				FROM kustannuspaikka
				WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'O'
				ORDER BY nimi";
	$vresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='kohde'><option value=''>Ei valintaa</option>";

	while ($vrow=mysql_fetch_array($vresult)) {
		$sel="";
		if ($trow[$i] == $vrow['tunnus'] or $kohde == $vrow["tunnus"]) {
			$sel = "selected";
		}
		echo "<option value = '$vrow[tunnus]' $sel>$vrow[nimi]</option>";
	}

	echo "</select></td>";
	echo "</tr>";
	echo "<tr><th>".t("Vain projekti")."</th>";

	$query = "	SELECT tunnus, nimi
				FROM kustannuspaikka
				WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'P'
				ORDER BY nimi";
	$vresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='proj'><option value=''>".t("Ei valintaa")."</option>";

	while ($vrow=mysql_fetch_array($vresult)) {
		$sel="";
		if ($trow[$i] == $vrow['tunnus'] or $proj == $vrow["tunnus"]) {
			$sel = "selected";
		}
		echo "<option value = '$vrow[tunnus]' $sel>$vrow[nimi]</option>";
	}

	echo "</select></td></tr>";

	$sel = array();
	if ($rtaso == "") $rtaso = "1";
	$sel[$rtaso] = "SELECTED";

	echo "<tr><th>".t("Raportointitaso")."</th>
			<td><select name='rtaso'>";

	$query = "select max(length(taso)) taso from taso where yhtio = '$kukarow[yhtio]'";
	$vresult = mysql_query($query) or pupe_error($query);
	$vrow = mysql_fetch_array($vresult);

	for ($i=$vrow["taso"]-1; $i >= 0; $i--) {
		echo "<option ".$sel[$i+2]." value='".($i+2)."'>".t("Taso %s",'',$i+1)."</option>\n";
	}

//	echo "<option ".$sel[$i+2]." value='".($i+2)."'>".t("Tili taso")."</option>\n";

	echo "</select></td></tr>";

	$sel = array();
	if ($tarkkuus == "") $tarkkuus = 1;
	$sel[$tarkkuus] = "SELECTED";

	echo "<tr><th>".t("Lukujen taarkkuus")."</th>
			<td><select name='tarkkuus'>
				<option $sel[1]    value='1'>".t("Älä jaa lukuja")."</option>
				<option $sel[1000] value='1000'>".t("Jaa 1000:lla")."</option>
				<option $sel[10000] value='10000'>".t("Jaa 10 000:lla")."</option>
				<option $sel[100000] value='100000'>".t("Jaa 100 000:lla")."</option>
				<option $sel[1000000] value='1000000'>".t("Jaa 1 000 000:lla")."</option>
				</select>";

	$sel = array();
	if ($desi == "") $desi = "0";
	$sel[$desi] = "SELECTED";

	echo "<select name='desi'>
			<option $sel[0] value='0'>0 ".t("desimaalia")."</option>
			<option $sel[1] value='1'>1 ".t("desimaalia")."</option>
			<option $sel[2] value='2'>2 ".t("desimaalia")."</option>
			</select></td></tr>";


	$vchek = $bchek = "";
	if ($vertailued != "") $vchek = "CHECKED";
	if ($vertailubu != "") $bchek = "CHECKED";

	echo "<tr><th>".t("Vertailu")."</th>
			<td>
			<input type='checkbox' name='vertailued' $vchek> ".t("Edellinen vastaava")."
			<input type='checkbox' name='vertailubu' $bchek DISABLED> ".t("Budjetti")."</td></tr>";

	echo "</table><br>
	      <input type = 'submit' value = '".t("Näytä")."'></form>";

	require("../inc/footer.inc");

?>