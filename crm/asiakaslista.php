<?php

	// käsittämätön juttu, mutta ei voi muuta
	if ($_POST["voipcall"] != "") $_GET["voipcall"]  = "";

	require ("../inc/parametrit.inc");

	if (!isset($voipcall)) $voipcall = '';
	if (!isset($haku)) $haku = '';
	if (!isset($ojarj)) $ojarj = '';
	if (!isset($asos)) $asos = '';
	if (!isset($aspiiri)) $aspiiri = '';
	if (!isset($asryhma)) $asryhma = '';
	if (!isset($astila)) $astila = '';
	if (!isset($asmyyja)) $asmyyja = '';
	if (!isset($lisa)) $lisa = '';
	if (!isset($kaikki_tunnukset)) $kaikki_tunnukset = '';
	if (!isset($konserni)) $konserni = '';
	if (!isset($tee)) $tee = '';
	if (!isset($ryhma)) $ryhma = '';
	if (!isset($oper)) $oper = '';
	if (!isset($ulisa)) $ulisa = '';

	if ($voipcall == "call" and $o != "" and $d != "") {
		ob_start();
		$retval = @readfile($VOIPURL."&o=$o&d=$d");
		$retval = ob_get_contents();
		ob_end_clean();
		if ($retval != "OK") echo "<font class='error'>Soitto $o -&gt; $d epäonnistui!</font><br><br>";
	}

	$otsikko   = 'Asiakaslista';
	if ($yhtiorow['viikkosuunnitelma'] == '') {
		$kentat    = "asiakas.tunnus::asiakas.nimi::asiakas.asiakasnro::asiakas.ytunnus::if(asiakas.toim_postitp!='',asiakas.toim_postitp,asiakas.postitp)::asiakas.postino::asiakas.yhtio::asiakas.myyjanro::asiakas.email";
	}
	else {
		$kentat    = "asiakas.tunnus::asiakas.nimi::asiakas.myyjanro::asiakas.ytunnus::asiakas.asiakasnro::if(asiakas.toim_postitp!='',asiakas.toim_postitp,asiakas.postitp)::asiakas.yhtio";
	}

	$jarjestys = 'selaus, nimi';

	echo "<font class='head'>".t("$otsikko")."</font><hr>";

	$array = explode("::", $kentat);
	$count = count($array);

	for ($i = 0; $i <= $count; $i++) {
		if (isset($haku[$i]) and strlen($haku[$i]) > 0) {
			$lisa .= " and " . $array[$i] . " like '%" . $haku[$i] . "%'";
			$ulisa .= "&haku[" . $i . "]=" . $haku[$i];
		}
	}

	if (strlen($ojarj) > 0) {
		$jarjestys = $ojarj;
	}

	if ($asos != '') {
		$lisa .= " and asiakas.osasto = '$asos' ";
	}

	if ($aspiiri != '') {
		$lisa .= " and asiakas.piiri = '$aspiiri' ";
	}

	if ($asryhma != '') {
		$lisa .= " and asiakas.ryhma = '$asryhma' ";
	}

	if ($astila != '') {
		$lisa .= " and asiakas.tila = '$astila' ";
	}

	if ($asmyyja != '') {
		$lisa .= " and asiakas.myyjanro = '$asmyyja' ";
	}

	$lisa .= " and asiakas.laji != 'P' ";

	if (isset($dynaamiset_nimet)) {
		if (!is_array($dynaamiset_nimet)) {
			$dynaamiset_nimet = unserialize(urldecode($dynaamiset_nimet));
		}

		if (count($dynaamiset_nimet) > 0) {

			foreach ($dynaamiset_nimet as $nimi) {

				if (!isset(${$nimi}) or ${$nimi}[0] == 'PUPEKAIKKIMUUT' or trim(${$nimi}[0]) == '') continue;

				$kaikki_tunnukset = ${$nimi}[0];
				$ulisa .= "&{$nimi}[]=".${$nimi}[0];
				$ulisa .= "&dynaamiset_nimet[]={$nimi}";
			}
		}
	}

	$joinlisa = '';
	$selectlisa = '';

	if ($kaikki_tunnukset != '') {

		$kaikki_tunnukset = implode(",", array_unique(explode(",", $kaikki_tunnukset)));

		$query = "	SELECT GROUP_CONCAT(DISTINCT subnode.tunnus) tunnukset
					FROM dynaaminen_puu AS subnode 
					JOIN dynaaminen_puu AS subparent ON (subparent.tunnus IN ($kaikki_tunnukset)) 
					JOIN puun_alkio ON (puun_alkio.yhtio = subnode.yhtio AND puun_alkio.puun_tunnus = subnode.tunnus AND puun_alkio.laji = subnode.laji)
					WHERE subnode.yhtio = '{$kukarow['yhtio']}'
					AND subnode.laji = 'asiakas'
					AND (subnode.lft BETWEEN subparent.lft AND subparent.rgt)
					AND subnode.lft > 1
					ORDER BY subnode.lft";
		$kaikki_puun_tunnukset_res = mysql_query($query, $link) or pupe_error($query);
		$kaikki_puun_tunnukset_row = mysql_fetch_assoc($kaikki_puun_tunnukset_res);

		if (trim($kaikki_puun_tunnukset_row['tunnukset']) != '') {
			$joinlisa = " JOIN puun_alkio ON (puun_alkio.yhtio = asiakas.yhtio AND puun_alkio.laji = 'asiakas' AND puun_alkio.puun_tunnus IN ($kaikki_puun_tunnukset_row[tunnukset]) AND puun_alkio.liitos = asiakas.tunnus) ";
			$selectlisa = ", puun_alkio.puun_tunnus ";
		}
		else {
			$joinlisa = " JOIN puun_alkio ON (puun_alkio.yhtio = asiakas.yhtio AND puun_alkio.laji = 'asiakas' AND puun_alkio.puun_tunnus = asiakas.tunnus AND puun_alkio.liitos = asiakas.tunnus) ";
			$selectlisa = ", puun_alkio.puun_tunnus ";
		}

		$ulisa .= "&kaikki_tunnukset=$kaikki_tunnukset";
	}

	if (trim($konserni) != '') {
		$query = "SELECT distinct yhtio FROM yhtio WHERE (konserni = '$yhtiorow[konserni]' and konserni != '') or (yhtio = '$yhtiorow[yhtio]')";
		$result = mysql_query($query) or pupe_error($query);
		$konsernit = "";

		while ($row = mysql_fetch_array($result)) {
			$konsernit .= " '".$row["yhtio"]."' ,";
		}
		$konsernit = " asiakas.yhtio in (".substr($konsernit, 0, -1).") ";
	}
	else {
		$konsernit = " asiakas.yhtio = '$kukarow[yhtio]' ";
	}

	if ($yhtiorow['viikkosuunnitelma'] == '') {
		if ($tee == "lahetalista") {
			$query = "	SELECT asiakas.tunnus, asiakas.nimi, asiakas.postitp, asiakas.ytunnus, asiakas.yhtio, asiakas.asiakasnro, asiakas.nimitark, 
						asiakas.osoite, asiakas.postino, asiakas.postitp, asiakas.maa, asiakas.toim_nimi, asiakas.toim_nimitark, asiakas.toim_osoite, 
						asiakas.toim_postino, asiakas.toim_postitp, asiakas.toim_maa, asiakas.puhelin, asiakas.fax, asiakas.myyjanro, asiakas.email, 
						asiakas.osasto, asiakas.piiri, asiakas.ryhma, asiakas.fakta, asiakas.toimitustapa, asiakas.yhtio $selectlisa
						FROM asiakas
						$joinlisa
						WHERE $konsernit
						$lisa";
			$tiednimi = "asiakaslista.xls";
		}
		else {
			$query = "	SELECT asiakas.tunnus, if(asiakas.nimi != asiakas.toim_nimi, CONCAT(asiakas.nimi, '<br />', asiakas.toim_nimi), asiakas.nimi) nimi, 
						asiakas.asiakasnro, asiakas.ytunnus,  if(asiakas.toim_postitp != '', asiakas.toim_postitp, asiakas.postitp) postitp, 
						if(asiakas.toim_postino != 00000, asiakas.toim_postino, asiakas.postino) postino, 
						asiakas.yhtio, asiakas.myyjanro, asiakas.email, asiakas.puhelin $selectlisa
						FROM asiakas
						$joinlisa
						WHERE $konsernit
						$lisa";
			$tiednimi = "viikkosuunnitelma.xls";
		}
	}
	else {
		$query = "	SELECT asiakas.tunnus, asiakas.nimi, (SELECT concat_ws(' ', kuka.myyja, kuka.nimi) FROM kuka WHERE kuka.yhtio = '$kukarow[yhtio]' AND kuka.myyja = asiakas.myyjanro AND kuka.myyja > 0 LIMIT 1) myyja, 
					asiakas.ytunnus, asiakas.asiakasnro, if(asiakas.toim_postitp != '', asiakas.toim_postitp, asiakas.postitp) postitp, 
					asiakas.yhtio, asiakas.puhelin $selectlisa
					FROM asiakas
					$joinlisa
					WHERE $konsernit
					$lisa";
	}



	if ($lisa == "" and ($tee != 'laheta' or $tee != 'lahetalista')) {
		$limit = " LIMIT 200 ";
	}
	else {
		$limit = " ";
	}

	$query .= "$ryhma ORDER BY $jarjestys $limit";
	$result = mysql_query($query) or pupe_error($query);

	if ($oper == t("Vaihda listan kaikkien asiakkaiden tila")) {
		// Käydään lista läpi kertaalleen
		while ($trow = mysql_fetch_array ($result)) {
			$query_update = "	UPDATE asiakas
								SET tila = '$astila_vaihto'
								WHERE tunnus = '$trow[tunnus]'
								AND yhtio = '$yhtiorow[yhtio]'";
			$result_update = mysql_query($query_update) or pupe_error($query_update);
		}
		$result = mysql_query($query) or pupe_error($query);
	}

	if ($tee == 'laheta' or $tee == 'lahetalista') {

		if ($tee == "lahetalista") {
			if (@include('Spreadsheet/Excel/Writer.php')) {
				//keksitään failille joku varmasti uniikki nimi:
				list($usec, $sec) = explode(' ', microtime());
				mt_srand((float) $sec + ((float) $usec * 100000));
				$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

				$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
				$workbook->setVersion(8);
				$worksheet =& $workbook->addWorksheet('Sheet 1');

				$format_bold =& $workbook->addFormat();
				$format_bold->setBold();

				$excelrivi = 0;

				if(isset($workbook)) {
					$excelsarake = 0;

					for ($i=1; $i<mysql_num_fields($result)-1; $i++) {
						if(isset($workbook)) {
							$worksheet->write($excelrivi, $excelsarake, t(mysql_field_name($result,$i)) , $format_bold);
							$excelsarake++;
						}
					}


					$excelsarake = 0;
					$excelrivi++;

				}
			}
		}
		else {
			if (@include('Spreadsheet/Excel/Writer.php')) {
				//keksitään failille joku varmasti uniikki nimi:
				list($usec, $sec) = explode(' ', microtime());
				mt_srand((float) $sec + ((float) $usec * 100000));
				$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

				$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
				$workbook->setVersion(8);
				$worksheet =& $workbook->addWorksheet('Sheet 1');

				$format_bold =& $workbook->addFormat();
				$format_bold->setBold();

				$excelrivi = 0;

				if(isset($workbook)) {
					$excelsarake = 0;

					for ($i=1; $i<mysql_num_fields($result); $i++) {
						//$liite .= $trow[$i]."\t";
						if(isset($workbook)) {
							$worksheet->write($excelrivi, $excelsarake, t(mysql_field_name($result,$i)) , $format_bold);
							$excelsarake++;
						}
					}

					$worksheet->write($excelrivi, $excelsarake, t("pvm") , $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("kampanjat") , $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("pvm käyty") , $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("km") , $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("lähtö") , $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("paluu") , $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("pvraha") , $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("kommentti") , $format_bold);


					$excelsarake = 0;
					$excelrivi++;

				}
			}
		}
		while ($trow=mysql_fetch_array ($result)) {
			$excelsarake = 0;
			for ($i=1; $i<mysql_num_fields($result)-1; $i++) {
				if(isset($workbook)) {
					$worksheet->writeString($excelrivi, $excelsarake, $trow[$i], $format_bold);
					$excelsarake++;
				}
			}
			$excelrivi++;
		}

		if(isset($workbook)) {
			// We need to explicitly close the workbook
			$workbook->close();
		}

		$liite = "/tmp/".$excelnimi;

		$bound = uniqid(time()."_") ;

		$header  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n";
		$header .= "MIME-Version: 1.0\n" ;
		$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

		$content = "--$bound\n" ;

		$content .= "Content-Type: application/excel; name=\"".basename($liite)."\"\n" ;
		$content .= "Content-Transfer-Encoding: base64\n" ;
		$content .= "Content-Disposition: inline; filename=\"".basename($tiednimi)."\"\n\n";

		$handle  = fopen($liite, "r");
		$sisalto = fread($handle, filesize($liite));
		fclose($handle);

		$content .= chunk_split(base64_encode($sisalto));
		$content .= "\n" ;

		if ($tee == "lahetalista") {
			mail($kukarow['eposti'], mb_encode_mimeheader("Asiakkaiden tiedot", "ISO-8859-1", "Q"), $content, $header, "-f $yhtiorow[postittaja_email]");
			echo "<br><br><font class='message'>".t("Asiakkaiden tiedot sähköpostiisi")."!</font><br><br><br>";
		}
		else {
			mail($kukarow['eposti'], mb_encode_mimeheader("Viikkosunnitelmapohja", "ISO-8859-1", "Q"), $content, $header, "-f $yhtiorow[postittaja_email]");
			echo "<br><br><font class='message'>".t("Suunnitelmapohja lähetetty sähköpostiisi")."!</font><br><br><br>";
		}

		mysql_data_seek($result,0);
	}

	if ($yhtiorow['viikkosuunnitelma'] == '') {
		echo "<li><a href='$PHP_SELF?tee=laheta&asos=$asos&asryhma=$asryhma&astila=$astila&aspiiri=$aspiiri&konserni=$konserni&asmyyja=$asmyyja".$ulisa."'>".t("Lähetä viikkosuunnitelmapohja sähköpostiisi")."</a><br>";
		echo "<li><a href='$PHP_SELF?tee=lahetalista&asos=$asos&asryhma=$asryhma&astila=$astila&aspiiri=$aspiiri&konserni=$konserni&asmyyja=$asmyyja".$ulisa."'>".t("Lähetä asiakaslista sähköpostiisi")."</a><br>";
	}

	echo "<br><table>
			<form action='$PHP_SELF' method='post'>
			<input type='hidden' name='voipcall' value='kala'>";

	$asosresult = t_avainsana("ASIAKASOSASTO");

	echo "<tr><th>".t("Valitse asiakkaan osasto").":</th><td><select name='asos' onchange='submit();'>";
	echo "<option value=''>".t("Kaikki osastot")."</option>";

	while ($asosrow = mysql_fetch_array($asosresult)) {
		$sel2 = '';
		if ($asos == $asosrow["selite"]) {
			$sel2 = "selected";
		}
		echo "<option value='$asosrow[selite]' $sel2>$asosrow[selite] - $asosrow[selitetark]</option>";
	}
	echo "</select></td>";


	$chk = "";
	if (trim($konserni) != '') {
		$chk = "CHECKED";
	}

	echo "<th>".t("Näytä konsernin kaikki asiakkaat").":</th><td><input type='checkbox' name='konserni' $chk onclick='submit();'></td>";
	echo "</tr>\n\n";

	$asosresult = t_avainsana("PIIRI");

	echo "<tr><th>".t("Valitse asiakkaan piiri").":</th><td><select name='aspiiri' onchange='submit();'>";
	echo "<option value=''>".t("Kaikki piirit")."</option>";

	while ($asosrow = mysql_fetch_array($asosresult)) {
		$sel2 = '';
		if ($aspiiri == $asosrow["selite"]) {
			$sel2 = "selected";
		}
		echo "<option value='$asosrow[selite]' $sel2>$asosrow[selite] - $asosrow[selitetark]</option>";
	}
	echo "</select></td>";


	$asosresult = t_avainsana("ASIAKASRYHMA");

	echo "<th>".t("Valitse asiakkaan ryhmä").":</th><td><select name='asryhma' onchange='submit();'>";
	echo "<option value=''>".t("Kaikki ryhmät")."</option>";

	while ($asosrow = mysql_fetch_array($asosresult)) {
		$sel2 = '';
		if ($asryhma == $asosrow["selite"]) {
			$sel2 = "selected";
		}
		echo "<option value='$asosrow[selite]' $sel2>$asosrow[selite] - $asosrow[selitetark]</option>";
	}

	echo "</select></td></tr>\n\n";

	$query = "	SELECT distinct asiakas.myyjanro, kuka.nimi
				FROM asiakas
				LEFT JOIN kuka ON kuka.yhtio = asiakas.yhtio and kuka.myyja=asiakas.myyjanro and kuka.myyja > 0
				WHERE asiakas.yhtio = '$kukarow[yhtio]'
				and asiakas.myyjanro > 0
				order by myyjanro";
	$asosresult = mysql_query($query) or pupe_error($query);

	echo "<tr>";
	echo "<th>".t("Valitse asiakkaan myyjä").":</th><td><select name='asmyyja' onchange='submit();'>";
	echo "<option value=''>".t("Kaikki myyjät")."</option>";

	while ($asosrow = mysql_fetch_array($asosresult)) {
		$sel2 = '';
		if ($asmyyja == $asosrow["myyjanro"]) {
			$sel2 = "selected";
		}
		echo "<option value='$asosrow[myyjanro]' $sel2>$asosrow[myyjanro] - $asosrow[nimi]</option>";
	}

	echo "</select></td>\n\n";

	$asosresult = t_avainsana("ASIAKASTILA");

	echo "<th>".t("Valitse asiakkaan tila").":</th><td><select name='astila' onchange='submit();'>";
	echo "<option value=''>".t("Kaikki tilat")."</option>";

	while ($asosrow = mysql_fetch_array($asosresult)) {
		$sel2 = '';
		if ($astila == $asosrow["selite"]) {
			$sel2 = "selected";
		}
		echo "<option value='$asosrow[selite]' $sel2>$asosrow[selite] - $asosrow[selitetark]</option>";
	}

	echo "</select></td></tr>\n\n";
	echo "</table><br />";

	$avainsana_result = t_avainsana('DYNAAMINEN_PUU', '', " and selite='Asiakas' ");

	if (mysql_num_rows($avainsana_result) == 1) {
		$monivalintalaatikot = array("DYNAAMINEN_ASIAKAS");
		$monivalintalaatikot_normaali = array("DYNAAMINEN_ASIAKAS");

		require ("../tilauskasittely/monivalintalaatikot.inc");

		echo "<input type='hidden' name='dynaamiset_nimet' value='",urlencode(serialize($dynaamiset_nimet)),"' />";
	}

	echo "<br><table>";

	echo "<tr>";

	for ($i = 1; $i < mysql_num_fields($result)-1; $i++) {
		echo "<th><a href='$PHP_SELF?asos=$asos&asryhma=$asryhma&astila=$astila&aspiiri=$aspiiri&konserni=$konserni&asmyyja=$asmyyja&ojarj=".mysql_field_name($result,$i).$ulisa."'>" . t(mysql_field_name($result,$i)) . "</a>";

		if 	(mysql_field_len($result,$i)>10) $size='20';
		elseif	(mysql_field_len($result,$i)<5)  $size='5';
		else	$size='10';

		if (!isset($haku[$i])) $haku[$i] = '';

		echo "<br><input type='text' name='haku[$i]' value='$haku[$i]' size='$size' maxlength='" . mysql_field_len($result,$i) ."'>";
		echo "</th>";
	}

	echo "<td class='back'>&nbsp;&nbsp;<input type='Submit' value='".t("Etsi")."'></td></tr>\n\n";

	$kalalask = 1;

	while ($trow=mysql_fetch_array ($result)) {
		echo "<tr class='aktiivi'>";

		for ($i=1; $i<mysql_num_fields($result)-1; $i++) {
			if ($i == 1) {
				if (trim($trow[1]) == '') $trow[1] = "".t("*tyhjä*")."";
				echo "<td><a name='1_$kalalask' href='".$palvelin2."crm/asiakasmemo.php?ytunnus=$trow[ytunnus]&asiakasid=$trow[tunnus]&lopetus=".$palvelin2."crm/asiakaslista.php////asos=$asos//konserni=$konserni//aspiiri=$aspiiri//asryhma=$asryhma//asmyyja=$asmyyja//astila=$astila".str_replace("&", "//", $ulisa)."//ojarj=$ojarj///1_$kalalask'>$trow[1]</a></td>";
			}
			elseif(mysql_field_name($result,$i) == 'ytunnus') {
				echo "<td><a name='2_$kalalask' href='".$palvelin2."yllapito.php?toim=asiakas&tunnus=$trow[tunnus]&lopetus=".$palvelin2."crm/asiakaslista.php////asos=$asos//konserni=$konserni//aspiiri=$aspiiri//asryhma=$asryhma//asmyyja=$asmyyja//astila=$astila".str_replace("&", "//", $ulisa)."//ojarj=$ojarj///2_$kalalask'>$trow[$i]</a></td>";
			}
			else {
				echo "<td>$trow[$i]</td>";
			}
		}

		echo "<td class='back'>";

		if ($trow["puhelin"] != "" and $kukarow["puhno"] != "" and isset($VOIPURL)) {
			$d = ereg_replace("[^0-9]", "", $trow["puhelin"]);  // dest
			$o = ereg_replace("[^0-9]", "", $kukarow["puhno"]); // orig
			echo "<a href='$PHP_SELF?asos=$asos&asryhma=$asryhma&astila=$astila&aspiiri=$aspiiri&konserni=$konserni&ojarj=$ulisa&o=$o&d=$d&voipcall=call'>Soita $o -&gt; $d</a>";
		}

		echo "</td>";
		echo "</tr>\n\n";

		$kalalask++;
	}
	echo "</table>";

	echo "<br/>";

	$asosresult = t_avainsana("ASIAKASTILA");

	echo t("Vaihda asiakkaiden tila").": <select name='astila_vaihto'>";

	while ($asosrow = mysql_fetch_array($asosresult)) {
		$sel2 = '';
		if ($astila == $asosrow["selite"]) {
			$sel2 = "selected";
		}
		echo "<option value='$asosrow[selite]' $sel2>$asosrow[selite] - $asosrow[selitetark]</option>";
	}

	echo "</select></td></tr>\n\n";

	echo "<input type=\"submit\" name=\"oper\" value=\"".t("Vaihda listan kaikkien asiakkaiden tila")."\">";
	echo "</form>";

	require ("../inc/footer.inc");

?>