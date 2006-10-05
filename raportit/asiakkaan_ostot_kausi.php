<?php
///* Tämä skripti käyttää slave-tietokantapalvelinta *///
$useslave = 1;
require ("../inc/parametrit.inc");

echo "<font class='head'>".t("Asiakkaan/Osaston ostot annetulta kaudelta")."</font><hr>";
echo "<i>".t("Kopioi raportin tulos Exceliin. MUISTA muuttaa ensimmäinen sarake tekstiksi jotta tuotenumeroiden mahdolliset etunollat ei katoa.")."<br><br></i>";
echo "<i>".t("Ed sarakkeet ovat annettun pvm-rajauksen edellisen vastaavan kauden lukuja")."<br><br></i>";
// hehe, näin on helpompi verrata päivämääriä
$query  = "SELECT TO_DAYS('$vvl-$kkl-$ppl')-TO_DAYS('$vva-$kka-$ppa') ero";
$result = mysql_query($query) or pupe_error($query);
$row    = mysql_fetch_array($result);

if ($row["ero"] > 366) {
	echo "<font class='error'>".t("Jotta homma ei menisi liian hitaaksi, niin vuosi on pisin mahdollinen laskentaväli!")."</font><br>";
	echo "".t("Annetut rajaukset").": $ytunnus $asosasto $apvm $lpvm<br>";
	$tee = '';
}

if ($tee == 'go') {
	
	if (isset($muutparametrit)) {
		list($vva,$kka,$ppa,$vvl,$kkl,$ppl) = explode('#', $muutparametrit);
	}

	$muutparametrit = $vva."#".$kka."#".$ppa."#".$vvl."#".$kkl."#".$ppl."#";
	
	$evva = $vva-1;
	$evvl	= $vvl-1;
	$eapvm = $evva."-".$kka."-".$ppa;
	$elpvm = $evvl."-".$kkl."-".$ppl;

	$apvm = $vva."-".$kka."-".$ppa;
	$lpvm = $vvl."-".$kkl."-".$ppl;

	echo "<br>".t("Annetut rajaukset").": $ytunnus $asosasto $apvm $lpvm<br><br>";
	
	$ok = '';

	if ($ytunnus != '') {
		if ($asosasto == '') {
			require ("../inc/asiakashaku.inc");
		}
		$ok++;
	}
	
	if ($asosasto != '') {
		$ok++;
	}
	
	if ($ytunnus != '' and $ok == 1 and $asiakasid != '') {
		$query = "SELECT t.tuoteno, t.nimitys,
					sum(if(t.laskutettuaika >= '$apvm' and t.laskutettuaika <= '$lpvm',t.rivihinta,0)) summa,
					sum(if(t.laskutettuaika >= '$eapvm' and t.laskutettuaika <= '$elpvm',t.rivihinta,0)) edsumma,
					sum(if(t.laskutettuaika >= '$apvm' and t.laskutettuaika <= '$lpvm',t.kate,0)) kate,
					sum(if(t.laskutettuaika >= '$eapvm' and t.laskutettuaika <= '$elpvm',t.kate,0)) edkate,
					sum(if(t.laskutettuaika >= '$apvm' and t.laskutettuaika <= '$lpvm',t.kpl,0)) kpl,
					sum(if(t.laskutettuaika >= '$eapvm' and t.laskutettuaika <= '$elpvm',t.kpl,0)) edkpl
					FROM lasku l use index (yhtio_tila_liitostunnus_tapvm), tilausrivi t
					WHERE l.yhtio = t.yhtio and l.tunnus = t.uusiotunnus and l.yhtio = '$kukarow[yhtio]'
					and l.tila = 'u' and l.alatila = 'x'
					and l.liitostunnus = '$asiakasid'
					and ((l.tapvm >= '$apvm' and l.tapvm <= '$lpvm') or (l.tapvm >= '$eapvm' and l.tapvm <= '$elpvm'))
					group by t.tuoteno
					order by tuoteno";
	}
	if ($asosasto != '' and $ok == 1) {
		$query = "SELECT t.tuoteno, t.nimitys,
					sum(if(t.laskutettuaika >= '$apvm' and t.laskutettuaika <= '$lpvm',t.rivihinta,0)) summa,
					sum(if(t.laskutettuaika >= '$eapvm' and t.laskutettuaika <= '$elpvm',t.rivihinta,0)) edsumma,
					sum(if(t.laskutettuaika >= '$apvm' and t.laskutettuaika <= '$lpvm',t.kate,0)) kate,
					sum(if(t.laskutettuaika >= '$eapvm' and t.laskutettuaika <= '$elpvm',t.kate,0)) edkate,
					sum(if(t.laskutettuaika >= '$apvm' and t.laskutettuaika <= '$lpvm',t.kpl,0)) kpl,
					sum(if(t.laskutettuaika >= '$eapvm' and t.laskutettuaika <= '$elpvm',t.kpl,0)) edkpl
					FROM asiakas a use index (yhtio_osasto_ryhma), lasku l use index (yhtio_tila_liitostunnus_tapvm), tilausrivi t
					WHERE a.yhtio = l.yhtio and a.yhtio = t.yhtio and l.tunnus = t.uusiotunnus
					and l.yhtio = '$kukarow[yhtio]'
					and a.osasto = '$asosasto'
					and l.tila = 'u' and l.alatila = 'x'
					and l.liitostunnus = a.tunnus
					and ((l.tapvm >= '$apvm' and l.tapvm <= '$lpvm') or (l.tapvm >= '$eapvm' and l.tapvm <= '$elpvm'))
					group by t.tuoteno
					order by tuoteno";
	}

	if ($ok == 1 and ($asiakasid != '' or $asosasto != '')) {
		$result = mysql_query($query) or pupe_error($query);
		//echo "$query<br>";
		//$rivit = mysql_num_rows($result);
		//echo "Rivejä $rivit<br>";
		echo "<pre>";
		echo "".t("tuoteno")."\t";
		echo "".t("nimitys")."\t";
		echo "".t("Summa alv0")."\t";
		echo "".t("Kate")."\t";
		echo "".t("KPL")."\t";
		echo "".t("Ed.Summa alv0")."\t";
		echo "".t("Ed.Kate")."\t";
		echo "".t("Ed.KPL")."\t";
		echo "\n";
		while ($row = mysql_fetch_array($result)) {
			echo "$row[tuoteno]\t";
			echo "$row[nimitys]\t";
			echo "".str_replace('.',',',$row['summa'])."\t";
			echo "".str_replace('.',',',$row['kate'])."\t";
			echo "".str_replace('.',',',$row['kpl'])."\t";
			echo "".str_replace('.',',',$row['edsumma'])."\t";
			echo "".str_replace('.',',',$row['edkate'])."\t";
			echo "".str_replace('.',',',$row['edkpl'])."\t";
			echo "\n";
		}
		echo "<br><br><br><br>";
	}
	elseif ($ok != 1) {
		echo "<font class='error'>".t("Virheitä löytyi!!!!")."</font><br>";
	}
}




echo "<table><form name='piiri' method='post' action='$PHP_SELF'>";

if (!isset($kka))
	$kka = date("m",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($vva))
	$vva = date("Y",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($ppa))
	$ppa = date("d",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));

if (!isset($kkl))
	$kkl = date("m");
if (!isset($vvl))
	$vvl = date("Y");
if (!isset($ppl))
	$ppl = date("d");


echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
		<td><input type='text' name='ppa' value='$ppa' size='3'></td>
		<td><input type='text' name='kka' value='$kka' size='3'></td>
		<td><input type='text' name='vva' value='$vva' size='5'></td>
		</tr><tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
		<td><input type='text' name='ppl' value='$ppl' size='3'></td>
		<td><input type='text' name='kkl' value='$kkl' size='3'></td>
		<td><input type='text' name='vvl' value='$vvl' size='5'></td></tr>";

		$query = "	SELECT distinct if(osasto = '','TYHJÄ', osasto)
					FROM asiakas
					WHERE yhtio='$kukarow[yhtio]'
					order by 1";
		$sresult = mysql_query($query) or pupe_error($query);

		$ulos2 = "<select name='asosasto'>";
		$ulos2 .= "<option value=''>".t("Osasto")."</option>";

		while ($srow = mysql_fetch_array($sresult)) {
			$sel = '';
			if ($osasto == $srow[0]) {
				$sel = "selected";
			}
			$ulos2 .= "<option value='$srow[0]' $sel>$srow[0]</option>";
		}
		$ulos2 .= "</select>";

echo "<tr><th>".t("Valitse osasto")."</th>
		<td colspan='3'>$ulos2</td>
		<tr><th>".t("tai syötä ytunnus").":</th>
		<td colspan='3'><input type='text' name='ytunnus' value='$ytunnus' size='15'></td></tr>
		<input type='hidden' name='tee' value='go' size='15'>
	</table>";

echo "<br><input type='submit' value='".t("Aja raportti")."'>";
echo "</form>";

require ("../inc/footer.inc");
?>