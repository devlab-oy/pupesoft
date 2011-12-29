<?php

	require ("../inc/parametrit.inc");
    require_once ("inc/tilinumero.inc");

	$lisa = "";

	echo "	<script language=javascript>
			function verify1() {
				msg = '".t("Haluatko todella poistaa suorituksen? Suorituksen summa siirret��n yhti�n selvittelytilille").".';
				return confirm(msg);
			}
			function verify2() {
				msg = '".t("Haluatko todella poistaa suorituksen? K�sinsy�tetyn suorituksen summa poistetaan kirjanpidosta").".';
				return confirm(msg);
			}
			function verify3(tili) {
				msg = 'Haluatko todella poistaa suorituksen? Suorituksen summa siirret��n tilille '+tili;
				return confirm(msg);
			}
			</script>";

	if ($tila == 'poistasuoritus' or $tila == 'siirrasuoritus' or $tila == "siirrasuoritus_tilille") {

		if ($tila == "siirrasuoritus_tilille" and isset($suoritustunnukset_kaikki) and $suoritustunnukset_kaikki != "") {
			$suoritustunnukset = $suoritustunnukset_kaikki;
		}

		if ($suoritustunnukset == "") {
			$suoritustunnukset = 0;
		}

		if ($tila == 'poistasuoritus') {
			$suorilisa = " and viite = '' ";
		}
		else {
			$suorilisa = " ";
		}

		// Haetaan itse suoritus
		$query = "	SELECT *
					FROM suoritus
					WHERE yhtio	= '$kukarow[yhtio]'
					AND tunnus in ($suoritustunnukset)
					and kohdpvm	= '0000-00-00'
					$suorilisa";
		$suoritus_res = pupe_query($query);

		while ($suoritus_row = mysql_fetch_assoc($suoritus_res)) {

			// Haetaan suorituksen pankkitili
			$query = "	SELECT oletus_rahatili
						FROM yriti
						WHERE yhtio 	= '$kukarow[yhtio]'
						AND kaytossa   != 'E'
						and tilino		= '$suoritus_row[tilino]'";
			$yriti_res = pupe_query($query);
			$yriti_row = mysql_fetch_assoc($yriti_res);

			// Haetaan suorituksen saamiset-tili�inti
			$query = "	SELECT *
						FROM tiliointi
						WHERE yhtio 	= '$kukarow[yhtio]'
						AND tunnus 		= '$suoritus_row[ltunnus]'
						AND korjattu 	= ''";
			$tiliointi1_res = pupe_query($query);
			$tiliointi1_row = mysql_fetch_assoc($tiliointi1_res);

			if (mysql_num_rows($tiliointi1_res) == 1) {
				// Haetaan suorituksen pankkitili-tili�inti
				$query = "	SELECT tilino
							FROM tiliointi
							WHERE yhtio 	= '$kukarow[yhtio]'
							and ltunnus 	= '$tiliointi1_row[ltunnus]'
							and tilino 		= '$yriti_row[oletus_rahatili]'
							and summa 		=  $tiliointi1_row[summa] * -1
							and korjattu 	= ''
							LIMIT 1";
				$tiliointi2_res = pupe_query($query);
				$tiliointi2_row = mysql_fetch_assoc($tiliointi2_res);
			}

			// Jos kaikki l�ytyy, niin ok. Else majork�k
			if (mysql_num_rows($yriti_res) == 1 and mysql_num_rows($tiliointi1_res) == 1 and mysql_num_rows($tiliointi2_res) == 1 and $tiliointi2_row["tilino"] != "" and (int) $tiliointi1_row["ltunnus"] > 0 and $yhtiorow["selvittelytili"] != "") {

				if ($tila == "siirrasuoritus_tilille") {
					$stili  = $siirtotili;
					$tapvm  = $tiliointi1_row["tapvm"];
					$selite = "Suoritus siirretty tilille $stili";
				}
				elseif ($tila == "siirrasuoritus") {
					$stili  = $yhtiorow["selvittelytili"];
					$tapvm  = $tiliointi1_row["tapvm"];
					$selite = t("Suoritus siirretty selvittelytilille");
				}
				else {
					$stili  = $tiliointi2_row["tilino"];
					$tapvm  = $tiliointi1_row["tapvm"];
					$selite = t('Suoritus poistettu');
				}

				//vertaillaan tilikauteen
				list($vv1,$kk1,$pp1) = explode("-", $yhtiorow["myyntireskontrakausi_alku"]);
				list($vv2,$kk2,$pp2) = explode("-", $yhtiorow["myyntireskontrakausi_loppu"]);

				$myrealku  = (int) date('Ymd', mktime(0,0,0,$kk1,$pp1,$vv1));
				$myreloppu = (int) date('Ymd', mktime(0,0,0,$kk2,$pp2,$vv2));

				$tsekpvm = str_replace("-", "", $tapvm);

				if ($tsekpvm < $myrealku or $tsekpvm > $myreloppu) {
					echo "<br><font class='error'>".t("HUOM: Suorituksen p�iv�m��r� oli suljetulla kaudella. Tili�inti tehtiin t�lle p�iv�lle")."!</font><br><br>";

					$tapvm  = date("Y-m-d");
				}

				$query = "	INSERT INTO tiliointi SET
							yhtio		= '$kukarow[yhtio]',
							ltunnus		= '$tiliointi1_row[ltunnus]',
							tapvm		= '$tapvm',
							summa		=  $tiliointi1_row[summa],
							tilino		= '$stili',
							kustp 		= '$tiliointi1_row[kustp]',
							kohde 		= '$tiliointi1_row[kohde]',
							projekti 	= '$tiliointi1_row[projekti]',
							selite		= '$selite',
							lukko		= 0,
							laatija		= '$kukarow[kuka]',
							laadittu	= now(),
							summa_valuutassa = $tiliointi1_row[summa_valuutassa],
							valkoodi	= '$tiliointi1_row[valkoodi]'";
				$result = pupe_query($query);

				$query = "	INSERT INTO tiliointi SET
							yhtio		= '$kukarow[yhtio]',
							ltunnus		= '$tiliointi1_row[ltunnus]',
							tapvm		= '$tapvm',
							summa		=  $tiliointi1_row[summa] * -1,
							tilino		= '$tiliointi1_row[tilino]',
							kustp 		= '$tiliointi1_row[kustp]',
							kohde 		= '$tiliointi1_row[kohde]',
							projekti 	= '$tiliointi1_row[projekti]',
							selite		= '$selite',
							lukko		= 1,
							laatija		= '$kukarow[kuka]',
							laadittu	= now(),
							summa_valuutassa = $tiliointi1_row[summa_valuutassa] * -1,
							valkoodi	= '$tiliointi1_row[valkoodi]'";
				$result = pupe_query($query);

				$query = "UPDATE suoritus set kohdpvm = '$tapvm', summa=0 where tunnus='$suoritus_row[tunnus]'";
				$result = pupe_query($query);
			}
		}

		$tila = '';
	}

	if ($tila == 'suoritus_asiakaskohdistus_kaikki') {
		//kohdistetaan t�st� kaikki helpot
		require ("suoritus_asiakaskohdistus_kaikki.php");
		$tila = "";
	}

	if ($tila == 'komm') {
		$query = "UPDATE suoritus set viesti = '$komm' WHERE tunnus='$tunnus' and yhtio='$kukarow[yhtio]'";
		$result = pupe_query($query);
		$tila = 'tarkenna';
	}

	if ($tila == 'tulostakuitti') {

		//Haetaan kirjoitin
		$query  = "	SELECT komento
					FROM kirjoittimet
					WHERE yhtio = '$kukarow[yhtio]'
					AND tunnus = '$kukarow[kirjoitin]'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Sinulla ei ole oletuskirjoitinta").".</font><br>";
		}
		else {
			$kirjoitinrow = mysql_fetch_assoc($result);
			$tulostakuitti = $kirjoitinrow["komento"];

			$query = "	SELECT *
						FROM asiakas
						WHERE yhtio = '$kukarow[yhtio]'
						AND tunnus = '$asiakas_tunnus'";
			$result = pupe_query($query);
			$asiakasrow = mysql_fetch_assoc($result);

			require ("../tilauskasittely/tulosta_kuitti.inc");

			// pdff�n piirto
			$firstpage = alku();
			rivi($firstpage);
			loppu($firstpage);

			$pdffilenimi = "/tmp/kuitti-".md5(uniqid(mt_rand(), true)).".pdf";

			//kirjoitetaan pdf faili levylle..
			$fh = fopen($pdffilenimi, "w");
			if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus ep�onnistui $pdffilenimi");
			fclose($fh);

			if ($tulostakuitti == "email") {
				$liite = $pdffilenimi;
				$kutsu = "Suoritus $asiakasrow[nimi]";
				require ("inc/sahkoposti.inc");
				echo "<font class='message'>".t("Kuittikopio l�hetetty").".</font><br>";
			}
			else {
				// itse print komento...Koska ei ole luotettavaa tapaa tehd� kahta kopiota, niin printataan kahdesti
				$line = exec("$tulostakuitti $pdffilenimi");
	    		$line = exec("$tulostakuitti $pdffilenimi");
				echo "<font class='message'>".t("Kuittikopio (2 kpl) tulostettu").".</font><br>";
			}

			//poistetaan tmp file samantien kuleksimasta...
			$line = exec("rm -f $pdffilenimi");
		}

		// nollataan muuttujat niin ei mene mik��n sekasin
		$tila			= "";
		$summa			= "";
		$selite			= "";
		$asiakas_tunnus	= "";
	}

	if ($tila == "kohdista") {
			$myyntisaamiset = 0;

			// katotaan l�ytyyk� tili
			$query = "	SELECT tilino
						from tili
						where yhtio = '$kukarow[yhtio]'
						and tilino  = '$vastatili'";
			$result = pupe_query($query);

			if (mysql_num_rows($result) == 0) {
				echo t("Virheellinen vastatilitieto")."!";
				exit;
			}

			$query = "	SELECT *
						FROM suoritus
						WHERE tunnus = '$tunnus'
						and yhtio = '$kukarow[yhtio]'";
			$result = pupe_query($query);

			if (mysql_num_rows($result) == 1) {

				$suoritus = mysql_fetch_assoc($result);

				// Suoritus kuntoon
				$query = "	UPDATE suoritus
							SET asiakas_tunnus = '$atunnus'
							WHERE tunnus = '$tunnus'
							AND yhtio = '$kukarow[yhtio]'";
				$result = pupe_query($query);

				// Tili�inti on voinut muuttua
				$query = "	UPDATE tiliointi
							SET tilino = '$vastatili'
							WHERE yhtio = '$kukarow[yhtio]'
							AND tunnus = '$suoritus[ltunnus]'
							AND korjattu = ''";
				$result = pupe_query($query);

				echo "<font class='message'>".t("Suoritus kohdistettu")."!</font><br><br>";
			}
			else {
				echo "<font class='error'>".t("Suoritus kateissa")."!</font><br><br>";
				exit;
			}
			$tila = '';
	}

	if ($tila == 'tarkenna') {

		echo "<font class='head'>".t("Suorituksen kohdistaminen asiakkaaseen")."<hr></font>";

		$query = "	SELECT suoritus.yhtio,
					concat_ws(' ',yriti.oletus_rahatili, yriti.nimi) tilino,
					tilino_maksaja,
					nimi_maksaja,
					viite,
					viesti,
					suoritus.summa,
					maksupvm,
					kirjpvm,
					concat_ws(' ',tili.tilino, tili.nimi) vastatili,
					asiakas_tunnus,
					tili.tilino ttilino,
					yriti.oletus_selvittelytili,
					tiliointi.summa * -1 kotisumma,
					suoritus.valkoodi
					FROM suoritus
					LEFT JOIN tiliointi ON (tiliointi.yhtio = suoritus.yhtio AND tiliointi.tunnus = suoritus.ltunnus AND tiliointi.korjattu = '')
					LEFT JOIN tili ON (tili.yhtio = suoritus.yhtio and tili.tilino = tiliointi.tilino)
					LEFT JOIN yriti ON (yriti.yhtio = suoritus.yhtio and yriti.tilino = suoritus.tilino)
					WHERE suoritus.tunnus = $tunnus AND suoritus.yhtio = '$kukarow[yhtio]'";
		$result = pupe_query($query);

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("rahatili")." / ".t("vastatili")."</th>";
		echo "<th>".t("nimi_maksaja")."</th>";
		echo "<th>".t("summa")."</th>";
		echo "<th>".t("maksupvm")."</th>";
		echo "<th>".t("kirjpvm")."</th>";
		echo "</tr>";

		if (mysql_num_rows($result) > 0) {
			$suoritus = mysql_fetch_assoc ($result);

			if (!isset($haku["nimi"])) $haku["nimi"] = $suoritus['nimi_maksaja'];

			$asiakas_tunnus	= $suoritus['asiakas_tunnus'];
			$suoritus_summa	= $suoritus['summa'];
			$komm			= $suoritus['viesti'];

			echo "<tr>";
			echo "<td>$suoritus[tilino]<br>$suoritus[vastatili]</td>";
			echo "<td valign='top'>$suoritus[nimi_maksaja]</td>";
			echo "<td valign='top'>$suoritus[summa] $suoritus[valkoodi]";
			if (strtoupper($suoritus['valkoodi']) != strtoupper($yhtiorow['valkoodi'])) {
				echo "<br>$suoritus[kotisumma] $yhtiorow[valkoodi]";
			}
			echo "</td>";
			echo "<td valign='top'>".tv1dateconv($suoritus["maksupvm"])."</td>";
			echo "<td valign='top'>".tv1dateconv($suoritus["kirjpvm"])."</td>";
			echo "</tr>";

			echo "<tr>";
			echo "<th>".t("viite")."</th>";
			echo "<th colspan='4'>".t("viesti")."</th>";
			echo "</tr>";

			echo "<tr>";
			echo "<td valign='top'>$suoritus[viite]</td>";
			echo "<td valign='top' colspan='4'>$suoritus[viesti]</td>";
			echo "</tr>";
		}

		echo "</table>";

		// Mahdollisuus muuttaa viesti�
		echo "	<form action = '$PHP_SELF' method='post'>
				<input type = 'hidden' name='tunnus' value='$tunnus'>
				<input type = 'hidden' name='tila' value='komm'>
				<table>
					<tr>
						<th>".t("Lis�� kommentti suoritukselle")."</th>
						<td><input type = 'text' name = 'komm' size='40' value = '$komm'></td>
						<td class='back'><input type = 'submit' value = '".t("Lis��")."'></td>
					</tr>
				</table>
				</form><br>";

		foreach ($haku as $key => $value) {
			$old   = array("[","{","\\","|","]","}");
			$new   = array("�","�", "�","�","�","�");
			$siivottu = preg_replace('/\b(oy|ab)\b/i', '', strtolower($value));
			$siivottu = preg_replace('/^\s*/', '', $siivottu);
			$siivottu = preg_replace('/\s*$/', '', $siivottu);
			$siivottu = str_replace($old, $new, $siivottu);

			$lisa  .= " and $key like '%$siivottu%'";
			$ulisa .= "&haku[$key]=".urlencode($siivottu);
		}

		//haetaan omat asiakkaat
		$query = "	SELECT *
					FROM asiakas
					WHERE yhtio = '$kukarow[yhtio]'
					$lisa
					ORDER BY nimi";
		$result = pupe_query($query);

		echo "<form action = '$PHP_SELF?tunnus=$tunnus&tila=$tila' method = 'post'>";

		echo "<table>";
		echo "<tr>";

		echo "<th>".t("ytunnus")."	<br><input type='text' name = 'haku[ytunnus]' value = '$haku[ytunnus]' size='15'></th>";
		echo "<th>".t("nimi")."		<br><input type='text' name = 'haku[nimi]'    value = '$haku[nimi]' size='15'></th>";
		echo "<th>".t("postino")."	<br><input type='text' name = 'haku[postino]' value = '$haku[postino]' size='8'></th>";
		echo "<th>".t("postitp")."	<br><input type='text' name = 'haku[postitp]' value = '$haku[postitp]' size='12'></th>";
		echo "<th valign='top'>".t("avoimia")."<br>".t("laskuja")."</th>";
		echo "<th valign='top'>".t("saamisettili")."</th>";
		echo "<th valign='bottom'><input type='submit' value = '".t("Etsi")."'></th>";
		echo "</tr>";

		echo "</form>";

		while ($trow = mysql_fetch_assoc ($result)) {

			echo "<form action='$PHP_SELF' method='post'>
					<input type='hidden' name='tila' value='kohdista'>
					<input type='hidden' name='atunnus' value='$trow[tunnus]'>
					<input type='hidden' name='tunnus' value='$tunnus'>";

			echo "<tr>";
			echo "<td valign='top'>$trow[ytunnus]</td>";
			echo "<td valign='top'><a href='myyntilaskut_asiakasraportti.php?ytunnus=$trow[ytunnus]&asiakasid=$trow[tunnus]&alatila=Y&tila=tee_raportti&lopetus=$PHP_SELF////tunnus=$tunnus//tila=tarkenna'>$trow[nimi] $trow[nimitark]</a><br>$trow[toim_nimi] $trow[toim_nimitark]</td>";
			echo "<td valign='top'>$trow[postino]<br>$trow[toim_postino]</td>";
			echo "<td valign='top'>$trow[postitp]<br>$trow[toim_postitp]</td>";

			// Onko asiakkaalla avoimia laskuja
			$query = "	SELECT count(*) maara
						FROM lasku USE INDEX (yhtio_tila_mapvm)
						WHERE yhtio = '$kukarow[yhtio]'
						and mapvm = '0000-00-00'
						and tila = 'U'
						and (ytunnus = '$trow[ytunnus]' or nimi = '$trow[nimi]' or liitostunnus = '$trow[tunnus]')";
			$lresult = pupe_query($query);
			$lasku = mysql_fetch_assoc ($lresult);

			echo "<td valign='top'>$lasku[maara]</td>";

			$sel1 = '';
			$sel2 = '';
			$sel3 = '';
			$sel4 = '';
			$sel5 = '';

			if ($suoritus['ttilino'] == $yhtiorow["myyntisaamiset"]) {
				$sel1 = "selected";
			}
			if ($suoritus['ttilino'] == $yhtiorow['factoringsaamiset']) {
				$sel2 = "selected";
			}
			if ($suoritus['ttilino'] == $yhtiorow["selvittelytili"]) {
				$sel3 = "selected";
			}
			if ($suoritus['ttilino'] == $suoritus["oletus_selvittelytili"]) {
				$sel4 = "selected";
			}
			if ($suoritus['ttilino'] == $yhtiorow["konsernimyyntisaamiset"]) {
				$sel5 = "selected";
			}

			echo "<td valign='top'><select name='vastatili'>";
			echo "<option value='$yhtiorow[myyntisaamiset]' $sel1>"		.t("Myyntisaamiset").		" ($yhtiorow[myyntisaamiset])</option>";
			echo "<option value='$yhtiorow[factoringsaamiset]' $sel2>"	.t("Factoringsaamiset").	" ($yhtiorow[factoringsaamiset])</option>";

			if ($suoritus["oletus_selvittelytili"] != "") {
				echo "<option value='$suoritus[oletus_selvittelytili]' $sel4>".t("Pankkitilin selvittelytili")." ($suoritus[oletus_selvittelytili])</option>";
			}
			if ($trow['konserniyhtio'] != "") {
				echo "<option value='$yhtiorow[konsernimyyntisaamiset]' $sel5>".t("Konsernimyyntisaamiset")." ($yhtiorow[konsernimyyntisaamiset])</option>";
			}
			echo "</select></td>";

			echo "<td valign='top'><input type='submit' value='".t("kohdista")."'></td>";
			echo "</tr>";
			echo "</form>";
		}

		echo "</table>";
	}

	if ($tila == '') {

		echo "<font class='head'>".t("Kohdistamattomien suorituksien selaus")."</font><hr>";

		echo "<form action = '$PHP_SELF?tila=$tila' method = 'post'>";

		$query = "	SELECT distinct suoritus.tilino, nimi, yriti.valkoodi
					FROM suoritus, yriti
					WHERE suoritus.yhtio = '$kukarow[yhtio]'
					AND kohdpvm = '0000-00-00'
					and yriti.yhtio=suoritus.yhtio
					and yriti.tilino=suoritus.tilino
					$lisa
					ORDER BY nimi";
		$result = pupe_query($query);

		echo "<table>";
		echo "<tr><th>".t("N�yt� vain tapahtumat tililt�")."</th>";
		echo "<td><select name='tilino' onchange='submit()'>";
		echo "<option value=''>".t("Kaikki")."</option>\n";

		while ($row = mysql_fetch_assoc($result)) {
			$sel = '';
			if (isset($tilino) and $tilino == $row['tilino']) $sel = 'selected';
			echo "<option value='$row[tilino]' $sel>$row[nimi] ".tilinumero_print($row['tilino'])." $row[valkoodi]</option>\n";
		}
		echo "</select></td></tr>";

		$query = "	SELECT distinct valkoodi
					FROM suoritus
					WHERE yhtio = '$kukarow[yhtio]'
					AND kohdpvm = '0000-00-00'
					$lisa
					ORDER BY valkoodi";
		$vresult = pupe_query($query);

		echo "<tr><th>".t("N�yt� vain tapahtumat valuutassa")."</th>";
		echo "<td><select name='valuutta' onchange='submit()'>";
		echo "<option value=''>".t("Kaikki")."</option>\n";

		while ($vrow = mysql_fetch_assoc($vresult)) {
			$sel = "";
			if ($valuutta == $vrow["valkoodi"]) $sel = "selected";
			echo "<option value = '$vrow[valkoodi]' $sel>$vrow[valkoodi]</option>";
		}

		echo "</select></td></tr>";
		echo "</table>";

		echo "<br><font class='message'>".t("Valitse x kohdistaaksesi suorituksia asiakkaisiin tai")." <a href='$PHP_SELF?tila=suoritus_asiakaskohdistus_kaikki'>".t("t�st�")."</a> ".t("kaikki helpot").".</font><br><br>";

		$tila = '';

		if (count($haku) > 0) {
			foreach ($haku as $kentta => $arvo) {
				if (strlen($arvo) > 0) {
					$lisa  .= " and $kentta like '%$arvo%'";
					$ulisa .= "&haku[$kentta]=$arvo";
				}
			}
		}

		if (strlen($ojarj) > 0) {
			$jarjestys = $ojarj;
		}
		else{
			$jarjestys = 'kirjpvm';
		}

		if ($tilino != "") {
			$lisa .= " and suoritus.tilino = '$tilino' ";
		}

		if ($valuutta != "") {
			$lisa .= " and suoritus.valkoodi = '$valuutta' ";
		}

		$query = "	SELECT suoritus.nimi_maksaja, suoritus.kirjpvm, suoritus.summa, suoritus.valkoodi,
					suoritus.tilino, suoritus.viite, suoritus.viesti, suoritus.tunnus, suoritus.asiakas_tunnus,
					asiakas.ytunnus,
					asiakas.nimi,
					asiakas.nimitark,
					asiakas.osoite,
					asiakas.postitp,
					asiakas.toim_nimi,
					asiakas.toim_nimitark,
					asiakas.toim_osoite,
					asiakas.toim_postitp,
					tiliointi.tilino ttilino,
					tiliointi.ltunnus tltunnus
					FROM suoritus
					LEFT JOIN asiakas ON (asiakas.yhtio = suoritus.yhtio AND asiakas.tunnus = suoritus.asiakas_tunnus)
					LEFT JOIN tiliointi ON (tiliointi.yhtio = suoritus.yhtio and tiliointi.tunnus = suoritus.ltunnus)
					WHERE suoritus.yhtio = '$kukarow[yhtio]'
					AND suoritus.kohdpvm = '0000-00-00'
					$lisa
				 	ORDER BY $jarjestys";
		$result = pupe_query($query);

        echo "<table><tr><th>x</th>";
        echo "<th><a href='$PHP_SELF?tila=$tila&ojarj=nimi_maksaja".$ulisa."'>".t("Maksaja")."<br>".t("Asiakas")."</a></th>";
		echo "<th><a href='$PHP_SELF?tila=$tila&ojarj=kirjpvm".$ulisa."'>".t("Pvm")."</a></th>";
		echo "<th><a href='$PHP_SELF?tila=$tila&ojarj=summa".$ulisa."'>".t("Summa")."</a></th>";
		echo "<th><a href='$PHP_SELF?tila=$tila&ojarj=valkoodi".$ulisa."'>".t("Valuutta")."</a></th>";
		echo "<th><a href='$PHP_SELF?tila=$tila&ojarj=tilino".$ulisa."'>".t("Tilino")."</a></th>";
		echo "<th><a href='$PHP_SELF?tila=$tila&ojarj=viite".$ulisa."'>".t("Viite")."<br>".t("Viesti")."</a></th>";
		echo "<th><a href='$PHP_SELF?tila=$tila&ojarj=ttilino".$ulisa."'>".t("Tili")."</a></th>";
		echo "</tr>";

		echo "<tr><td></td>";
		echo "<td valign='top'><input type='text' size='10' name='haku[suoritus.nimi_maksaja]' value='".$haku["suoritus.nimi_maksaja"]."'><br><input type='text' size='10' name='haku[asiakas.nimi]' value='".$haku["asiakas.nimi"]."'></td>";
		echo "<td valign='top'><input type='text' size='10' name='haku[suoritus.kirjpvm]' value='".$haku["suoritus.kirjpvm"]."'></td>";
		echo "<td valign='top'><input type='text' size='5'  name='haku[suoritus.summa]' value='".$haku["suoritus.summa"]."'></td>";
		echo "<td valign='top'><input type='text' size='5'  name='haku[suoritus.valkoodi]' value='".$haku["suoritus.valkoodi"]."'></td>";
		echo "<td valign='top'><input type='text' size='5'  name='haku[suoritus.tilino]' value='".$haku["suoritus.tilino"]."'></td>";
		echo "<td valign='top'><input type='text' size='15' name='haku[suoritus.viite]' value='".$haku["suoritus.viite"]."'><br><input type='text' size='15' name='haku[suoritus.viesti]' value='".$haku["suoritus.viesti"]."'></td>";
		echo "<td valign='top'><input type='text' size='5'  name='haku[tiliointi.tilino]' value='".$haku["tiliointi.tilino"]."'></td>";
		echo "<td valign='top' class='back'><input type='submit' value='".t("Etsi")."'></td></tr>";
		echo "</form>";

		$row = 0;

		// scripti balloonien tekemiseen
		js_popup();

		$suoritustunnukset_kaikki = array();

	    while ($maksurow = mysql_fetch_assoc($result)) {

			echo "<tr class='aktiivi'>";

			if ($maksurow["asiakas_tunnus"]!=0) {
				echo "<td valign='top'></td><td><a href='$PHP_SELF?tunnus=$maksurow[tunnus]&tila=tarkenna'>$maksurow[nimi_maksaja]</a>";
			}
			else {
				echo "<td valign='top'><a href='$PHP_SELF?tunnus=$maksurow[tunnus]&tila=tarkenna'>x</a></td><td>$maksurow[nimi_maksaja]";
			}

			echo "<div id='div_$maksurow[tunnus]' class='popup' style='width: 500px;'>
					$maksurow[ytunnus]<br>
					$maksurow[nimi] $maksurow[nimitark]<br>$maksurow[osoite] $maksurow[postitp]<br><br>
					$maksurow[toim_nimi] $maksurow[toim_nimitark]<br>$maksurow[toim_osoite] $maksurow[toim_postitp]
					</div>";

			echo "<br><a class='tooltip' id='$maksurow[tunnus]'>$maksurow[ytunnus]</a> $maksurow[nimi] $maksurow[nimitark]</td>";

			echo "<td valign='top'>".tv1dateconv($maksurow["kirjpvm"])."</td>";

			echo "<td valign='top' align='right'>$maksurow[summa]</td>";
			echo "<td valign='top'>$maksurow[valkoodi]</td>";

			echo "<td valign='top'>".tilinumero_print($maksurow["tilino"])."</td>";

			echo "<td valign='top'>$maksurow[viite]<br>$maksurow[viesti]</td>";
			echo "<td valign='top'><a href='../muutosite.php?tee=E&tunnus=$maksurow[tltunnus]'>$maksurow[ttilino]</a></td>";

			// tehd��n nappi kuitin tulostukseen
			echo "<td valign='top' class='back'>";
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tila' value='tulostakuitti'>";
			echo "<input type='hidden' name='asiakas_tunnus' value='$maksurow[asiakas_tunnus]'>";
			echo "<input type='hidden' name='summa' value='$maksurow[summa]'>";
			echo "<input type='hidden' name='selite' value='$maksurow[viesti]'>";
			echo "<input type='submit' value='".t("Tulosta kuitti")."'>";
			echo "</form>";
			echo "</td>";

			if ($kukarow['taso'] == 2 or $kukarow['taso'] == 3) {
				// tehd��n nappi suorituksen poistamiseen
				echo "<td valign='top' class='back'>";

				if (trim($maksurow["viite"]) != "") {

					if (isset($siirrasuoritustilille[$kukarow["yhtio"]]) and count($siirrasuoritustilille[$kukarow["yhtio"]]) > 0) {

						$suoritustunnukset_kaikki[] = $maksurow["tunnus"];

						foreach ($siirrasuoritustilille[$kukarow["yhtio"]] as $siirtotili) {
							echo "<form method='post' action='$PHP_SELF?$ulisa'>";
							echo "<input type='hidden' name='tila' value='siirrasuoritus_tilille'>";
							echo "<input type='hidden' name='siirtotili' value='$siirtotili'>";
							echo "<input type='hidden' name='suoritustunnukset' value='$maksurow[tunnus]'>";
							echo "<input type='submit' value='Siirr� $siirtotili-tilille' onClick='return verify3($siirtotili);'>";
							echo "</form>";
						}
					}

					echo "<form method='post' action='$PHP_SELF?$ulisa'>";
					echo "<input type='hidden' name='tila' value='siirrasuoritus'>";
					echo "<input type='hidden' name='suoritustunnukset' value='$maksurow[tunnus]'>";
					echo "<input type='submit' value='".t("Siirr� selvittelytilille")."' onClick='return verify1();'>";
					echo "</form>";
				}
				else {
					echo "<form method='post' action='$PHP_SELF?$ulisa'>";
					echo "<input type='hidden' name='tila' value='poistasuoritus'>";
					echo "<input type='hidden' name='suoritustunnukset' value='$maksurow[tunnus]'>";
					echo "<input type='submit' value='".t("Poista suoritus")."' onClick='return verify2();'>";
					echo "</form>";
				}

				echo "</td>";
			}

			echo "</tr>";

			$row++;
		}

		echo "</table>";

		if (($kukarow['taso'] == 2 or $kukarow['taso'] == 3) and isset($siirrasuoritustilille[$kukarow["yhtio"]]) and count($siirrasuoritustilille[$kukarow["yhtio"]]) > 0 and count($suoritustunnukset_kaikki) > 0) {

			echo "<br>";

			foreach ($siirrasuoritustilille[$kukarow["yhtio"]] as $siirtotili) {
				echo "<form method='post' action='$PHP_SELF?$ulisa'>";
				echo "<input type='hidden' name='tila' value='siirrasuoritus_tilille'>";
				echo "<input type='hidden' name='siirtotili' value='$siirtotili'>";
				echo "<input type='hidden' name='suoritustunnukset_kaikki' value='".implode(",", $suoritustunnukset_kaikki)."'>";
				echo "<input type='submit' value='Siirr� kaikki suoritukset $siirtotili-tilille' onClick='return verify3($siirtotili);'>";
				echo "</form><br>";
			}
		}
	}

	require ("inc/footer.inc");
?>