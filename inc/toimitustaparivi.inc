<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali k‰sittely
	$tyyppi = 1; // oletetaan rivin n‰kyvyys

	if (mysql_field_name($result, $i) == "tunnus" or
	    mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "nouto") {

		$sel='';
		if ($trow[$i]!='') $sel = 'SELECTED';

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value=''>".t("Tilaukset toimitetaan asiakkaalle")."</option>";
		$ulos .= "<option value='o' $sel>".t("Asiakas noutaa tilaukset")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "lauantai") {

		$sel='';
		if ($trow[$i]!='') $sel = 'SELECTED';

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value=''>".t("Ei lauantaijakelua")."</option>";
		$ulos .= "<option value='o' $sel>".t("Itella lis‰palvelu: Lauantaijakelu")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kuljyksikko") {

		$sel='';
		if ($trow[$i]!='') $sel = 'SELECTED';

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value=''>".t("Ei kuljetusyksikkˆkuljetusta")."</option>";
		$ulos .= "<option value='o' $sel>".t("Itella lis‰palvelu: Kuljetusyksikkˆkuljetus")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "virallinen_selite") {

		$sel = '';
		if ($trow[$i] != '') $sel[$trow[$i]] = 'SELECTED';

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value=''>".t("Ei valintaa")."</option>";
		$ulos .= "<option value='Express City 00' {$sel["Express City 00"]}>Itella Express City 00</option>";
		$ulos .= "<option value='Express Morning 9' {$sel["Express Morning 9"]}>Itella Express Morning 9</option>";
		$ulos .= "<option value='Express Business Day 14' {$sel["Express Business Day 14"]}>Itella Express Business Day 14</option>";
		$ulos .= "<option value='Express Point 00/16' {$sel["Express Point 00/16"]}>Itella Express Point 00/16</option>";
		$ulos .= "<option value='Express Flex 21' {$sel["Express Flex 21"]}>Itella Express Flex 21</option>";
		$ulos .= "<option value='Economy 16' {$sel["Economy 16"]}>Itella Economy 16</option>";
		$ulos .= "<option value='Customer Return' {$sel["Customer Return"]}>Itella Customer Return</option>";
		$ulos .= "<option value='Undelivered Shipment' {$sel["Undelivered Shipment"]}>Itella Undelivered Shipment</option>";
		$ulos .= "<option value='Lis‰arvokuljetus' {$sel["Lis‰arvokuljetus"]}>Itella Lis‰arvokuljetus</option>";
		$ulos .= "<option value='Priority Ulkomaa' {$sel["Priority Ulkomaa"]}>Itella Priority Ulkomaan l‰hetys</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "logy_rahtikirjanumerot") {

		$sel1='';
		$sel2='';

		if ($trow[$i]=='K')
			$sel2 = 'SELECTED';
		else
			$sel1 = 'SELECTED';

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value='' $sel1>".t("Ei k‰ytet‰ LOGY:n rahtikirjanumeroita")."</option>";
		$ulos .= "<option value='K' $sel2>".t("K‰ytet‰‰n LOGY:n rahtikirjanumeroita")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "merahti") {
		$sel1='';
		$sel2='';

		if ($trow[$i]=='K')
			$sel1 = 'SELECTED';
		else
			$sel2 = 'SELECTED';

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value='K' $sel1>".t("K‰ytet‰‰n l‰hett‰j‰n rahtisopimusta")."</option>";
		$ulos .= "<option value=''  $sel2>".t("K‰ytet‰‰n vastaanottajan rahtisopimusta")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tulostustapa") {
			$sel			= array();
			$sel[$trow[$i]]	= " selected";

			$ulos = "<td>
				<select name='$nimi'>
				<option value='E' $sel[E]>".t("Er‰tulostus")."</option>
				<option value='H' $sel[H]>".t("Hetitulostus")."</option>
				<option value='K' $sel[K]>".t("Koonti-hetitulostus")."</option>
				<option value='L' $sel[L]>".t("Koonti-er‰tulostus")."</option>
				<option value='X' $sel[X]>".t("Rahtikirjansyˆttˆ ja -tulostus ohitetaan")."</option>
				</select></td>";
			$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "extranet") {
		$sel			= array();
		$sel[$trow[$i]]	= " selected";

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value=''>".t("Toimitustapa n‰ytet‰‰n vain myynniss‰")."</option>";
		$ulos .= "<option value='K' $sel[K]>".t("Toimitustapa n‰ytet‰‰n vain extranetiss‰")."</option>";
		$ulos .= "<option value='M' $sel[M]>".t("Toimitustapa n‰ytet‰‰n molemmissa")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "rahtikirja") {

		$vresult = t_avainsana("RAHTIKIRJA");

		$ulos = "<td><select name='$nimi'>";

		while($row = mysql_fetch_array($vresult)) {
			$sel = "";
			if ($row["selite"] == $trow[$i]) $sel = 'selected';
			$ulos .= "<option value='$row[selite]' $sel>$row[selitetark]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "osoitelappu") {
		$sel1 = '';
		$sel2 = '';
		$sel3 = '';

		if ($trow[$i] == 'intrade') {
			$sel2 = 'selected';
		}
		elseif ($trow[$i] == 'tiivistetty') {
			$sel3 = 'selected';
		}
		else {
			$sel1 = 'selected';
		}

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value='' 		  $sel1>".t("Normaali")."</option>";
		$ulos .= "<option value='intrade' $sel2>".t("Intrade")."</option>";
		$ulos .= "<option value='tiivistetty' $sel3>".t("Tiivistetty")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "sisamaan_kuljetusmuoto" or mysql_field_name($result, $i) == "kuljetusmuoto") {

		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value = '' >".t("Valitse")."</option>";

		$vresult = t_avainsana("KM");

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['selite']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[selite]' $sel>$vrow[selitetark]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}


	if (mysql_field_name($result, $i) == "kauppatapahtuman_luonne") {

		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value = '' >".t("Valitse")."</option>";

		$vresult = t_avainsana("KT");

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['selite']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[selite]' $sel>$vrow[selitetark]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "poistumistoimipaikka_koodi") {

		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value = ''>".t("Valitse")."</option>";

		$vresult = t_avainsana("TULLI");

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['selite']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[selite]' $sel>$vrow[selite] - $vrow[selitetark]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kontti") {

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value = ''>".t("Valitse")."</option>";

		$chk1 = '';
		$chk2 = '';

		if ($trow[$i] == "1") {
			$chk1 = 'selected';
		}
		if ($trow[$i] == "0") {
			$chk2 = 'selected';
		}

		$ulos .= "<option value='1' $chk1>".t("Kyll‰")."</option>";
		$ulos .= "<option value='0' $chk2>".t("Ei")."</option>";

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "jvkielto") {

		$sel = $sela = '';
		if ($trow[$i]!='') $sel = 'SELECTED';
		else $sela = 'SELECTED';

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value='' $sela>".t("Toimitustavalla saa toimittaa j‰lkivaatimuksia")."</option>";
		$ulos .= "<option value='o' $sel>".t("Toimitustavalla ei saa toimittaa j‰lkivaatimuksia")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "vak_kielto") {

		$sel			= array();
		$sel[$trow[$i]]	= " selected";

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value=''>".t("Toimitustavalla saa toimittaa VAK-tuotteita")."</option>";
		$ulos .= "<option value='K' $sel[K]>".t("Toimitustavalla ei saa toimittaa VAK-tuotteita")."</option>";

		$query = "	SELECT DISTINCT selite
					FROM toimitustapa
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND vak_kielto = ''
					AND nouto = ''";
		$alt_toimitustapa_res = mysql_query($query) or pupe_error($query);

		while ($alt_toimitustapa_row = mysql_fetch_assoc($alt_toimitustapa_res)) {
			$ulos .= "<option value='$alt_toimitustapa_row[selite]' ".$sel[$alt_toimitustapa_row['selite']].">".t("VAK-tuotteet toimitetaan toimitustavalla")." $alt_toimitustapa_row[selite]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "erikoispakkaus_kielto") {

		$sel = $sela = '';
		if ($trow[$i] != '') {
			$sel = 'selected';
		}
		else {
			$sela = 'selected';
		}

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value='' $sela>".t("Toimitustavalla saa toimittaa erikoispakkauksia")."</option>";
		$ulos .= "<option value='K' $sel>".t("Toimitustavalla ei saa toimittaa erikoispakkauksia")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "ei_pakkaamoa") {

		$ulos  = "<td><select name='$nimi'>";
		$chk1 = '';
		$chk2 = '';

		if ($trow[$i] == "1") {
			$chk1 = 'selected';
		}
		if ($trow[$i] == "0") {
			$chk2 = 'selected';
		}

		$ulos .= "<option value='0' $chk2>".t("Toimitustavan tilaukset varaavat pakkaamolokeron")."</option>";
		$ulos .= "<option value='1' $chk1>".t("Toimitustavan tilaukaset eiv‰t varaa pakkaamolokeroa")."</option>";

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "erittely") {

		$ulos  = "<td><select name='$nimi'>";
		$chk1 = '';
		$chk2 = '';
		$chk3 = '';

		if ($trow[$i] == "k") {
			$chk1 = 'selected';
		}
		if ($trow[$i] == "") {
			$chk2 = 'selected';
		}
		if ($trow[$i] == "t") {
			$chk3 = 'selected';
		}

		$ulos .= "<option value='' $chk2>".t("Toimitustavalle ei tulosteta rahtikirjaerittely‰")."</option>";
		$ulos .= "<option value='k' $chk1>".t("Toimitustavalle tulostetaan rahtikirjaerittely per asiakas")."</option>";
		$ulos .= "<option value='t' $chk3>".t("Toimitustavalle tulostetaan rahtikirjaerittely per toimitustapa")."</option>";

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "uudet_pakkaustiedot") {

		$ulos  = "<td><select name='$nimi'>";

		$sel			= array();
		$sel[$trow[$i]]	= " selected";

		$ulos .= "<option value=''>".t("Koonti-er‰tulostuksessa ei voi syˆtt‰‰ uusia pakkaustietoja ennen tulostusta")."</option>";
		$ulos .= "<option value='K' $sel[K]>".t("Koonti-er‰tulostuksessa voi syˆtt‰‰ uudet pakkaustiedot ennen tulostusta")."</option>";

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "sallitut_alustat") {
		$ulos = "<td>";

		$sel = explode(",", $trow[$i]);

		$query = "	SELECT *
					FROM pakkaus
					WHERE yhtio = '{$kukarow['yhtio']}'";
		$pakkaus_result = mysql_query($query) or pupe_error($query);

		$ulos .= "<input type='hidden' name='{$nimi}[]' value='default' />";

		while ($pakkaus_row = mysql_fetch_assoc($pakkaus_result)) {

			$chk = in_array($pakkaus_row['tunnus'], $sel) ? 'checked' : '';

			$ulos .= "<input type='checkbox' name='{$nimi}[]' value='{$pakkaus_row['tunnus']}' {$chk}/> ".t_tunnus_avainsanat($pakkaus_row, "pakkaus", "PAKKAUSKV")." ".t_tunnus_avainsanat($pakkaus_row, "pakkauskuvaus", "PAKKAUSKV")."<br />";
		}

		$ulos .= "</td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "jvkulu" OR
		mysql_field_name($result, $i) == "kuluprosentti" OR
		mysql_field_name($result, $i) == "ulkomaanlisa" OR
		mysql_field_name($result, $i) == "kuljetusvakuutus") {

		if ($trow[$i] == 0) {
			$trow[$i] = "";
		}
	}

	if (mysql_field_name($result, $i) == "kuljetusvakuutus_tyyppi") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Yhtiˆn oletus")."</option>";
		$ulos .= "<option value = 'E'>".t("Ei kuljetusvakuutusta")."</option>";
		$ulos .= "<option value = 'A' $sel[A]>".t("Valuuttam‰‰r‰inen kuljetusvakuutus lis‰t‰‰n")."</option>";
		$ulos .= "<option value = 'B' $sel[B]>".t("Prosentuaalinen kuljetusvakuutus lis‰t‰‰n")."</option>";
		$ulos .= "<option value = 'F' $sel[F]>".t("Valuuttam‰‰r‰inen kuljetusvakuutus lis‰t‰‰n. K‰ytet‰‰n kuljetusvakuutustuotteen asiakashintaa ja alennusta")."</option>";
		$ulos .= "<option value = 'G' $sel[G]>".t("Prosentuaalinen kuljetusvakuutus lis‰t‰‰n. K‰ytet‰‰n kuljetusvakuutustuotteen asiakasalennusta")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}