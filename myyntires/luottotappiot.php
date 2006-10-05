<?php
require ("../inc/parametrit.inc");
echo "<font class='head'>".t("Myyntisaamisten kirjaus luottotappioiksi")."</font><hr>";

if ($tila=='K') {

	$tpk = (int) $tpk;
	$tpp = (int) $tpp;
	$tpv = (int) $tpv;
	if ($tpv < 1000) $tpv += 2000;
	
	if (!checkdate($tpk, $tpp, $tpv)) {
		echo "<font class='error'>".t("Virheellinen tapahtumapvm")."</font><br>";
		$tila = 'N';
	}
}

if ($tila=='K') {
	$query = "	SELECT tiliointi.ltunnus, tiliointi.tilino, tiliointi.summa, tiliointi.vero
				FROM lasku, tiliointi
				WHERE mapvm		= '0000-00-00' 
				AND tila		= 'U'
				AND lasku.yhtio		= '$kukarow[yhtio]'
				AND ytunnus 	= '$ytunnus'
				AND lasku.yhtio=tiliointi.yhtio
				AND lasku.tunnus= tiliointi.ltunnus
				AND lasku.tapvm = tiliointi.tapvm
				AND tiliointi.tilino not in ('$yhtiorow[varasto]', '$yhtiorow[varastonmuutos]', '$yhtiorow[raaka_ainevarasto]', '$yhtiorow[raaka_ainevarastonmuutos]', '$yhtiorow[alv]')
				ORDER BY 1";

	$laskuresult = mysql_query($query) or pupe_error($query);

	while ($lasku=mysql_fetch_array($laskuresult))
		if ($lasku['tilino'] != $yhtiorow['myyntisaamiset']) {
			// Hoidetaan alv
			$alv = round($lasku['summa'] * $lasku['vero'] / 100, 2);

			$query = "INSERT into tiliointi set
					yhtio ='$kukarow[yhtio]',
					ltunnus = '$lasku[ltunnus]',
					tilino = '$yhtiorow[luottotappiot]',
					kustp = '$lasku[kustp]',
					kohde = '$lasku[kohde]',
					projekti = '$lasku[projekti]',
					tapvm = '$tpv-$tpk-$tpp',
					summa = $lasku[summa] * -1,
					vero = '$lasku[vero]',
					selite = '$lasku[selite]',
					lukko = '',
					tosite = '$lasku[tosite]',
					laatija = '$kukarow[kuka]',
					laadittu = now()";
	
			$result = mysql_query($query) or pupe_error($query);
			if ($lasku['vero'] != 0) { // Tiliöidään alv
				$isa = mysql_insert_id ($link); // Näin löydämme tähän liittyvät alvit....
				$query = "INSERT into tiliointi set
							yhtio ='$kukarow[yhtio]',
							ltunnus = '$lasku[ltunnus]',
							tilino = '$yhtiorow[alv]',
							kustp = '',
							kohde = '',
							projekti = '',
							tapvm = '$tpv-$tpk-$tpp',
							summa = $alv * -1,
							vero = '',
							selite = '$lasku[selite]',
							lukko = '1',
							tosite = '$lasku[tosite]',
							laatija = '$kukarow[kuka]',
							laadittu = now(),
							aputunnus = '$isa'";
				$result = mysql_query($query) or pupe_error($query);
			}
		}
		else {
			$query = "INSERT into tiliointi set
					yhtio ='$kukarow[yhtio]',
					ltunnus = '$lasku[ltunnus]',
					tilino = '$lasku[tilino]',
					kustp = '$lasku[kustp]',
					kohde = '$lasku[kohde]',
					projekti = '$lasku[projekti]',
					tapvm = '$tpv-$tpk-$tpp',
					summa = $lasku[summa] * -1,
					vero = '',
					selite = '$lasku[selite]',
					lukko = '',
					tosite = '$lasku[tosite]',
					laatija = '$kukarow[kuka]',
					laadittu = now()";
			$result = mysql_query($query) or pupe_error($query);
			$query = "UPDATE lasku set mapvm = '$tpv-$tpk-$tpp' where yhtio ='$kukarow[yhtio]' and tunnus = '$lasku[ltunnus]'";
			$result = mysql_query($query) or pupe_error($query);
			
		}
		echo "<font class='message'>".t("Laskut on tiliöity luottotappioksi")."</font><br>";
}

if ($tila == 'N') {
	echo "<table><tr>";
	$query = "	SELECT ytunnus, concat_ws(' ', nimi, nimitark, '<br>', osoite, '<br>', postino, postitp) asiakas, sum(summa-saldo_maksettu) summa, count(*) kpl
				FROM lasku
				WHERE mapvm		= '0000-00-00' 
				AND tila		= 'U' 
				AND yhtio		= '$kukarow[yhtio]'
				AND ytunnus 	= '$ytunnus'
				GROUP BY ytunnus
				ORDER BY ytunnus";
	$result = mysql_query($query) or pupe_error($query);
	for ($i=0; $i < mysql_num_fields($result); $i++) {
		echo "<th>" . t(mysql_field_name($result,$i)) . "</th>";
	}
	echo "</tr>";
		
	while ($asiakas=mysql_fetch_array ($result)) {	
		for ($i=0; $i<mysql_num_fields($result); $i++) {
			echo "<td>$asiakas[$i]</td>";
		}
		echo "</tr>";
	}
	echo "</table>";
	
	echo "<br><font class='message'>".t("Erittely:")."</font><br>";
	echo "<table><tr>";
	$query = "	SELECT laskunro, tapvm, erpcm, summa-saldo_maksettu summa
				FROM lasku
				WHERE mapvm		= '0000-00-00' 
				AND tila		= 'U' 
				AND yhtio		= '$kukarow[yhtio]'
				AND ytunnus 	= '$ytunnus'
				ORDER BY 1";
	$result = mysql_query($query) or pupe_error($query);
	for ($i=0; $i < mysql_num_fields($result); $i++) {
		echo "<th>" . t(mysql_field_name($result,$i)) . "</th>";
	}
	echo "</tr>";

	while ($lasku=mysql_fetch_array ($result)) {
		for ($i=0; $i<mysql_num_fields($result); $i++) {
			echo "<td>$lasku[$i]</td>";
		}
		echo "</tr>";
	}
	echo "</table><br>";
	
	echo "<form action = '$PHP_SELF' method = 'post' name='pvm'><table><tr><td>".t("Kirjaa luottotappioksi")."</td>";
	echo "<input type='hidden' name='tila' value='K'>";
	echo "<input type='hidden' name='ytunnus' value='$ytunnus'>";
	echo "<td>".t("pvm")."</td><td>
				<input type='text' name='tpp' maxlength='2' size=2>
				<input type='text' name='tpk' maxlength='2' size=2>
				<input type='text' name='tpv' maxlength='4' size=4></td>";
	
	echo "<td><input type='submit' value='".t("Luottotappio")."'></td></tr></table></form>";
	$formi='pvm';
	$kentta='tpp';
}

if ($tila=='') {
	echo "<table><tr>";
	$query = "	SELECT ytunnus, concat_ws(' ', nimi, nimitark, '<br>', osoite, '<br>', postino, postitp) asiakas, sum(summa-saldo_maksettu) summa, count(*) kpl
				FROM lasku
				WHERE mapvm		= '0000-00-00' 
				AND tila		= 'U' 
				AND yhtio		= '$kukarow[yhtio]'
				GROUP BY ytunnus
				ORDER BY ytunnus";
	$result = mysql_query($query) or pupe_error($query);
	for ($i=0; $i < mysql_num_fields($result); $i++) {
		echo "<th>" . t(mysql_field_name($result,$i)) . "</th>";
	}
	echo "<th>".t("valitse")."</th></tr>";
	
	while ($asiakas=mysql_fetch_array ($result)) {
		echo "<form action = '$PHP_SELF' method = 'post'>";
		echo "<input type='hidden' name='tila' value='N'>";
		echo "<input type='hidden' name='ytunnus' value='$asiakas[ytunnus]'>";		
		for ($i=0; $i<mysql_num_fields($result); $i++) {
			echo "<td>$asiakas[$i]</td>";
		}

		echo "<td><input type='submit' value='".t("Luottotappio")."'></td></tr></form>";

	}

	echo "</table>";
}

require ("../inc/footer.inc");
?>
