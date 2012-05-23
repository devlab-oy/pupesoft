<?php 

	require ("../inc/parametrit.inc");

	if ($tee != 'osamaksusoppari' and $tee != 'vakuutushakemus') {
		echo "<font class='head'>".t("Lis�tietojen korjaus").":</font><hr><br>";
	}
	
	if ($tee == 'osamaksusoppari') {
		// Tehd��n rahoituslaskuelma
		require('osamaksusoppari.inc');
	}
	elseif ($tee == 'vakuutushakemus') {
		// Tehd��n vakuutushakemus
		require('vakuutushakemus.inc');
	}
	

	if ($tee == "TULOSTA") {
		$tulostimet[0] = 'Tarjous';
		if ($kappaleet > 0 and $komento["Tarjous"] != '' and $komento["Tarjous"] != 'email') {
			$komento["Tarjous"] .= " -# $kappaleet ";
		}
	
		$tulostimet[1] = 'Myyntisopimus';
		if ($kappaleet > 0 and $komento["Myyntisopimus"] != '' and $komento["Myyntisopimus"] != 'email') {
			$komento["Myyntisopimus"] .= " -# $kappaleet ";
		}
	
		$tulostimet[2] = 'Osamaksusopimus';
		if ($kappaleet > 0 and $komento["Osamaksusopimus"] != '' and $komento["Osamaksusopimus"] != 'email') {
			$komento["Osamaksusopimus"] .= " -# $kappaleet ";
		}
	
		$tulostimet[3] = 'Luovutustodistus';
		if ($kappaleet > 0 and $komento["Luovutustodistus"] != '' and $komento["Luovutustodistus"] != 'email') {
			$komento["Luovutustodistus"] .= " -# $kappaleet ";
		}
	
		$tulostimet[4] = 'Vakuutushakemus';
		if ($kappaleet > 0 and $komento["Vakuutushakemus"] != '' and $komento["Vakuutushakemus"] != 'email') {
			$komento["Vakuutushakemus"] .= " -# $kappaleet ";
		}

		$tulostimet[5] = 'Rekister�inti_ilmoitus';
		if ($kappaleet > 0 and $komento["Rekister�inti_ilmoitus"] != '' and $komento["Rekister�inti_ilmoitus"] != 'email') {
			$komento["Rekister�inti_ilmoitus"] .= " -# $kappaleet ";
		}
		
		if (count($komento) == 0 and $tee == 'TULOSTA') {
			require("../inc/valitse_tulostin.inc");
		}
	}
	
	if ($tee == "TULOSTA") {
		if ($komento["Tarjous"] != "") {			
			require_once ("tulosta_tarjous.inc");
			tulosta_tarjous($otunnus, $komento["Tarjous"], $kieli, $tee);
		}

		if ($komento["Myyntisopimus"] != "") {
			require_once ("tulosta_myyntisopimus.inc");
			tulosta_myyntisopimus($otunnus, $komento["Myyntisopimus"], $kieli, $tee);
		}

		if ($komento["Osamaksusopimus"] != "") {
			require_once ("tulosta_osamaksusoppari.inc");
			tulosta_osamaksusoppari($otunnus, $komento["Osamaksusopimus"], $kieli, $tee);
		}

		if ($komento["Luovutustodistus"] != "") {
			require_once ("tulosta_luovutustodistus.inc");
			tulosta_luovutustodistus($otunnus, $komento["Luovutustodistus"], $kieli, $tee);
		}

		if ($komento["Vakuutushakemus"] != "") {
			require_once ("tulosta_vakuutushakemus.inc");
			tulosta_vakuutushakemus($otunnus, $komento["Vakuutushakemus"], $kieli, $tee);
		}

		if ($komento["Rekister�inti_ilmoitus"] != "") {
			require_once ("tulosta_rekisteriilmoitus.inc");
			tulosta_rekisteriilmoitus($otunnus, $komento["Rekister�inti_ilmoitus"], $kieli, $tee);
		}
		$otunnus	= "";
		$tee 		= "";
	}
	
		
	if ($tee == 'NAYTAHTML' or $tee == 'NAYTATILAUS') {
		echo "<font class='head'>".t("Tilaus")." $tunnus:</font><hr>";
		require ("raportit/naytatilaus.inc");
		echo "<br><br>";
		$tee = "ETSILASKU";
	}
	
	if ($tee == "" or $tee == 'ETSILASKU'){
		if ($ytunnus != '') {
			require ("inc/asiakashaku.inc");
		}
		if ($ytunnus != '') {
			$tee = "ETSILASKU";
		}
		else {
			$tee = "";
		}

		if ($laskunro > 0) {
			$tee = "ETSILASKU";
		}

		if ($otunnus > 0) {
			$tee = 'ETSILASKU';
		}
	}

	if ($tee == "ETSILASKU") {
		echo "<form method='post' autocomplete='off'>
			<input type='hidden' name='ytunnus' value='$ytunnus'>
			<input type='hidden' name='asiakasid' value='$asiakasid'>
			<input type='hidden' name='tee' value='ETSILASKU'>";

		echo "<table>";

		if (!isset($kka))
			$kka = date("m",mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
		if (!isset($vva))
			$vva = date("Y",mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
		if (!isset($ppa))
			$ppa = date("d",mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));

		if (!isset($kkl))
			$kkl = date("m");
		if (!isset($vvl))
			$vvl = date("Y");
		if (!isset($ppl))
			$ppl = date("d");

		echo "<tr><th>".t("Sy�t� alkup�iv�m��r� (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppa' value='$ppa' size='3'></td>
				<td><input type='text' name='kka' value='$kka' size='3'></td>
				<td><input type='text' name='vva' value='$vva' size='5'></td>
				</tr><tr><th>".t("Sy�t� loppup�iv�m��r� (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppl' value='$ppl' size='3'></td>
				<td><input type='text' name='kkl' value='$kkl' size='3'></td>
				<td><input type='text' name='vvl' value='$vvl' size='5'></td>";
		echo "<td class='back'><input type='submit' value='".t("Hae")."'></td></tr></form></table><br>";
	
		$where1 = "";
		$where2 = "";
			
		//myyntilasku. T�lle oliolle voidaan tulostaa laskun kopio
		$where1 = " lasku.tila in ('L','N') ";

		if ($ytunnus{0} == '�') {
			$where2 = " and lasku.nimi      = '$asiakasrow[nimi]'
						and lasku.nimitark  = '$asiakasrow[nimitark]'
						and lasku.osoite    = '$asiakasrow[osoite]'
						and lasku.postino   = '$asiakasrow[postino]'
						and lasku.postitp   = '$asiakasrow[postitp]' ";
		}
		else {
			$where2 = " and lasku.liitostunnus  = '$asiakasid'";
		}

		$where2 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
					 and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59' ";

		if (!isset($jarj)) $jarj = " lasku.tunnus desc";
		
		$use = " use index (yhtio_tila_luontiaika) ";
		
		if ($laskunro > 0) {
			$where2 = " and lasku.laskunro = '$laskunro' ";
			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (lasno_index) ";
		}

		if ($otunnus > 0) {
			//katotaan l�ytyyk� lasku ja sen kaikki tilaukset
			$query = "  SELECT laskunro
						FROM lasku
						WHERE tunnus = '$otunnus' and lasku.yhtio = '$kukarow[yhtio]'";
			$laresult = mysql_query($query) or pupe_error($query);
			$larow = mysql_fetch_array($laresult);

			if ($larow["laskunro"] > 0) {
				$where2 = " and lasku.laskunro = '$larow[laskunro]' ";
				if (!isset($jarj)) $jarj = " lasku.tunnus desc";
				$use = " use index (lasno_index) ";
			}
			else {
				$where1 = " lasku.tunnus = '$otunnus' ";
				$where2 = "";
				if (!isset($jarj)) $jarj = " lasku.tunnus desc";
				$use = " use index (PRIMARY) ";
			}
		}

		$query_ale_lisa = generoi_alekentta('M');

		// Etsit��n muutettavaa tilausta
		$query = "  SELECT lasku.tunnus Tilaus, if(lasku.laskunro=0, '', laskunro) Laskunro, 
					concat_ws(' ', lasku.nimi, lasku.nimitark) Asiakas, lasku.ytunnus Ytunnus, 
					if(lasku.tapvm='0000-00-00', DATE_FORMAT(lasku.luontiaika, '%e.%c.%Y'), DATE_FORMAT(lasku.tapvm, '%e.%c.%Y')) Pvm, 
					if(kuka.nimi!=''and kuka.nimi is not null, kuka.nimi, lasku.laatija) Laatija, 
					if(lasku.summa=0, (SELECT round(sum(hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}), 2) FROM tilausrivi WHERE tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus), lasku.summa) Summa, 
					lasku.tila, lasku.alatila
					FROM lasku $use
					LEFT JOIN kuka ON kuka.yhtio=lasku.yhtio and kuka.kuka=lasku.laatija
					WHERE $where1 $where2
					and lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila != 'D'
					ORDER BY $jarj";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {

			echo "<table border='0' cellpadding='2' cellspacing='1'>";
			echo "<tr>";

			for ($i=0; $i < mysql_num_fields($result)-2; $i++) {
				$jarj = $i+1;
				echo "<th align='left'><a href='$PHP_SELF?tee=$tee&ppl=$ppl&vvl=$vvl&kkl=$kkl&ppa=$ppa&vva=$vva&kka=$kka&ytunnus=$ytunnus&asiakasid=$asiakasid&toimittajaid=$toimittajaid&jarj=$jarj'>".t(mysql_field_name($result,$i))."</a></th>";
			}
			echo "<th>".t("Tyyppi")."</th>";

			echo "</tr>";

			while ($row = mysql_fetch_array($result)) {
				echo "<tr>";
				$ero="td";

				if ($tunnus==$row['Tilaus']) $ero="th";

				echo "<tr>";

				for ($i=0; $i<mysql_num_fields($result)-2; $i++) {
					
					
					echo "<$ero>$row[$i]</$ero>";
				}

				$laskutyyppi = $row["tila"];
				$alatila     = $row["alatila"];

				//tehd��n selv�kielinen tila/alatila
				require "../inc/laskutyyppi.inc";

				echo "<$ero>".t("$laskutyyppi")." ".t("$alatila")."</$ero>";

				echo "	<td class='back'>
						<form method='post'>
						<input type='hidden' name='tee' value='NAYTAHTML'>
						<input type='hidden' name='tunnus' value='$row[Tilaus]'>
						<input type='hidden' name='ytunnus' value='$ytunnus'>
						<input type='hidden' name='asiakasid' value='$asiakasid'>
						<input type='hidden' name='toimittajaid' value='$toimittajaid'>
						<input type='hidden' name='laskunro' value='$laskunro'>
						<input type='hidden' name='ppa' value='$ppa'>
						<input type='hidden' name='kka' value='$kka'>
						<input type='hidden' name='vva' value='$vva'>
						<input type='hidden' name='ppl' value='$ppl'>
						<input type='hidden' name='kkl' value='$kkl'>
						<input type='hidden' name='vvl' value='$vvl'>
						<input type='submit' value='".t("N�yt� ruudulla")."'></form>
						<form method='post'>
						<input type='hidden' name='tee' value='osamaksusoppari'>
						<input type='hidden' name='tilausnumero' value='$row[Tilaus]'>
						<input type='Submit' value='".t("Rahoituslaskelma")."'>
						</form>
						<form method='post'>
						<input type='hidden' name='tee' value='vakuutushakemus'>
						<input type='hidden' name='tilausnumero' value='$row[Tilaus]'>
						<input type='Submit' value='".t("Vakuutushakemus/Rekisteri-ilmoitus")."'></form>
						<form method='post'>
						<input type='hidden' name='tee' value='TULOSTA'>
						<input type='hidden' name='otunnus' value='$row[Tilaus]'>
						<input type='Submit' value='".t("Tulosta Lomakkeita")."'></form>
						</td>";

				echo "</tr>";
			}
			echo "</table>";
		}
		else {
			echo t("Ei tilauksia")."...<br><br>";
		}
	}

	if ($tee == '') {
		//sy�tet��n tilausnumero
		echo "<br><table>";
		echo "<form method = 'post' name='hakuformi'>";

		
		echo "<tr><th>".t("Asiakkaan nimi")."</th><td><input type='text' size='10' name='ytunnus'></td></tr>";
		echo "<tr><th>".t("Tilausnumero")."</th><td><input type='text' size='10' name='otunnus'></td></tr>";
		echo "<tr><th>".t("Laskunumero")."</th><td><input type='text' size='10' name='laskunro'></td></tr>";
		echo "</table>";

		echo "<br><input type='submit' value='".t("Jatka")."'>";
		echo "</form>";
		
		$formi  = 'hakuformi';
		$kentta = 'ytunnus';
	}

	require ('../inc/footer.inc');
?>