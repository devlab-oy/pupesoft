<?php

if (strpos($_SERVER['SCRIPT_NAME'], "uutiset.php")  !== FALSE) {
	$otsikko_apu = $_POST["otsikko"];
	$uutinen_apu = $_POST["uutinen"];

	require ("inc/parametrit.inc");

	$otsikko = $otsikko_apu;
	$uutinen = $uutinen_apu;
}

if(!isset($toim)) 	$toim = "";
if(!isset($tee)) 	$tee = "";
if(!isset($limit)) 	$limit = "";

if ($toim == "") {
	if (strpos($_SERVER['SCRIPT_NAME'], "uutiset.php")  !== FALSE) echo "<font class='head'>".t("Intra Uutiset")."</font><hr>";
	$tyyppi = "uutinen";
}
elseif ($toim == "EXTRANET") {
	echo "<font class='head'>".t("Extranet Uutiset")."</font><hr>";
	$tyyppi = "extranet_uutinen";
}
elseif ($toim == "AUTOMANUAL") {
	echo "<font class='head'>".t("Automanual Uutiset")."</font><hr>";
	$tyyppi = "extranet_uutinen";
}
elseif ($toim == "VIIKKOPALAVERI") {
	echo "<font class='head'>".t("Viikkopalaveri")."</font><hr>";
	$tyyppi = "viikkopalaveri";
}
elseif ($toim == "ASIAKASPALVELU") {
	echo "<font class='head'>".t("Asiakaspalvelu")."</font><hr>";
	$tyyppi = "asiakaspalvelu";
}
elseif ($toim == "RYJO") {
	echo "<font class='head'>".t("Ryjo")."</font><hr>";
	$tyyppi = "ryjo";
}
elseif ($toim == "VERKKOKAUPPA") {
	echo "<font class='head'>".t("Verkkokaupan Uutiset")."</font><hr>";
	$tyyppi = $toim;
}

if ($toim == "VIIKKOPALAVERI" or $toim == "ASIAKASPALVELU" or $toim == "RYJO") {
	$kulisa = "";
}
else {
	$kulisa = " and kuka='$kukarow[kuka]' ";
}

if ($tee == 'LISAA') {

	if ($kukarow['yhtio'] == 'artr' and $toim == 'EXTRANET' and $automanual_uutinen == '' and $extranet_uutinen == '') {
		echo "<font class='error'>".t("Uutisen n�kyvyys on valittava! (Extranet tai Automanual)")."</font><br><br>";
		$rivi["kentta01"]  = $otsikko;
		$rivi["kentta02"]  = $uutinen;
		$rivi["kentta08"]  = $kentta08;
		$rivi["kentta09"]  = $kentta09;
		$rivi["konserni"]  = $konserni;
		$rivi["kokopaiva"] = $kokopaiva;
		$tee = "SYOTA";
	}
	elseif ($kukarow['yhtio'] == 'artr' and $toim == 'AUTOMANUAL' and $automanual_uutinen == '') {
		echo "<font class='error'>".t("Uutisen n�kyvyys on valittava!")."</font><br><br>";
		$rivi["kentta01"]  = $otsikko;
		$rivi["kentta02"]  = $uutinen;
		$rivi["kentta09"]  = $kentta09;
		$rivi["konserni"]  = $konserni;
		$rivi["kokopaiva"] = $kokopaiva;
		$tee = "SYOTA";
	}
	elseif (strlen($otsikko) > 0 and strlen($uutinen) > 0 and count($lang) > 0) {

		$liitostunnus = 0;

		$retval = tarkasta_liite("userfile");

		if($retval !== true) {
			echo $retval;
		}
		else {
			$kuva = tallenna_liite("userfile", "kalenteri", 0, $selite);
		}

		$uutinen = nl2br(strip_tags($uutinen, '<a>'));
		$otsikko = nl2br(strip_tags($otsikko, '<a>'));
		$uutinen = mysql_real_escape_string($uutinen);

		// ollaanko valittu konsernitasoinen uutinen
		if ($konserni != '') $konserni = $yhtiorow['konserni'];

		$tapa = "";

		if ($automanual_uutinen != '' and $extranet_uutinen != '' and $toim == 'EXTRANET') {
			$tapa = "automanual_ext_uutinen";
		}
		elseif ($automanual_uutinen != '' and $extranet_uutinen == '' and ($toim == 'EXTRANET' or $toim == 'AUTOMANUAL')) {
			$tapa = "automanual_uutinen";
		}
		elseif ($automanual_uutinen == '' and $extranet_uutinen != '' and $toim == 'EXTRANET') {
			$tapa = "extranet_uutinen";
		}
		else {
			$tapa = $tyyppi;
		}

		for ($i=0; $i < count($lang); $i++) {

			if ($tunnus != 0) {
				$query = "	UPDATE kalenteri SET ";
				$postquery = " WHERE tunnus = '$tunnus' ";
			}
			else {
				$query = "	INSERT INTO kalenteri
							SET
							kuka 		= '$kukarow[kuka]',
							tyyppi 		= '$tyyppi',
							yhtio 		= '$kukarow[yhtio]',
							pvmalku 	= now(),
							luontiaika	= now(),";
					$postquery = "";
			}

			$query .= "	kentta01 	= '$otsikko',
						kentta02 	= '$uutinen',";
			if ($kuva != '') {
				$query .= "kentta03 	= '$kuva',";
			}

			if ($kentta08 == 'X') {
				$query .= "kentta08 = '$kentta08',";
			}

			$query .=  "kentta09 	= '$kentta09',
						konserni 	= '$konserni',
						kieli 		= '$lang[$i]',
						kokopaiva	= '$kokopaiva',
						kuittaus	= '$lukittu',
						tapa		= '$tapa'";
			$query .= $postquery;
			$result = pupe_query($query);
			$katunnus = mysql_insert_id();

			if ($liitostunnus != 0 && $kuva != '') {
				// p�ivitet��n kuvalle viel� linkki toiseensuuntaa
				$query = "UPDATE liitetiedostot set liitostunnus='$katunnus' where tunnus='$liitostunnus'";
				$result = pupe_query($query);
			}
		}
		$tee = "";
	}
	else {
		echo "<font class='error'>".t("Sek� otsikko ett� uutinen on sy�tett�v�!")."</font><br><br>";
		$rivi["kentta01"]  = $otsikko;
		$rivi["kentta02"]  = $uutinen;
		$rivi["kentta08"]  = $kentta08;
		$rivi["kentta09"]  = $kentta09;
		$rivi["konserni"]  = $konserni;
		$rivi["kokopaiva"] = $kokopaiva;
		$tee = "SYOTA";
	}
}

if ($tee == "SYOTA") {

	$rivi["pvmalku"] = date('Y-m-d');

	if ($tunnus > 0) {
		$query  = "	SELECT *
					from kalenteri
					where tyyppi='$tyyppi' and tunnus='$tunnus' and yhtio='$kukarow[yhtio]' $kulisa";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 1) {
			$rivi = mysql_fetch_array($result);
		}
		else {
			echo "<br><br>".t("VIRHE: Et voi muokata uutista!")."<br>";
			exit;
		}
	}

	echo "<form enctype='multipart/form-data' name='sendfile' action='$PHP_SELF' method='post'>
		<input type='hidden' name='tee' value='LISAA'>
		<input type='hidden' name='toim' value='$toim'>
		<input type='hidden' name='tunnus' value='$rivi[tunnus]'>
		<table width='400'>
		<tr>
			<th>".t("Otsikko")."</th>
			<td><input type='text' size='40' name='otsikko' value='$rivi[kentta01]'></td>
		</tr>
		<tr>
			<th>".t("Uutinen")."</th>
			<td><textarea wrap='none' name='uutinen' cols='40' rows='15'>$rivi[kentta02]</textarea></td>
		</tr>";

	if ($tunnus > 0) {

		if ($rivi["kentta03"] != '') {
			echo "
				<tr>
					<th>".t("Nykyinen kuva")."</th>
					<td><img src=view.php?id=$rivi[kentta03]' width='130'></td>
				</tr>";

			echo "<input type='hidden' name='kuva' value='$rivi[kentta03]'>";
		}

		echo "
			<tr>
				<th>".t("Sy�t� uusi kuva")."</th>
				<td><input type='file' name='userfile'></td>
			</tr>";
	}
	else {
		echo "
			<tr>
				<th>".t("Kuva")."</th>
				<td><input type='file' name='userfile'></td>
			</tr>";
	}

	echo "<tr>
			<th>".t("Toimittaja")."</th>
			<td>$kukarow[nimi]</td>
		 </tr>
		 <tr>
			<th>".t("P�iv�m��r�")."</th>
			<td>$rivi[pvmalku]</td>
		 </tr>";

	echo "<tr><th>".t("Kieli").":&nbsp;</th><td>";

	$query  = "show columns from sanakirja";
	$fields =  mysql_query($query);

	if (!isset($lang)) $lang = array();

	while ($apurow = mysql_fetch_array($fields)) {
		$sel = "";

		if ($tunnus == 0 and $apurow[0] != "tunnus" and $apurow[0] != "aikaleima" and $apurow[0] != "kysytty" and $apurow[0] != "laatija" and $apurow[0] != "luontiaika" and $apurow[0] != "muutospvm" and $apurow[0] != "muuttaja" and $apurow[0] != "synkronoi") {
			if ($rivi["kieli"] == $apurow[0] or ($rivi["kieli"] == "" and $apurow[0] == $yhtiorow["kieli"]) and count($lang) == 0) $sel = "CHECKED";
			if (in_array($apurow[0], $lang)) $sel = "CHECKED";

			$query = "select distinct nimi from maat where koodi='$apurow[0]'";
			$maare = mysql_query($query);
			$maaro = mysql_fetch_array($maare);
			$maa   = strtolower($maaro["nimi"]);
			if ($maa=="") $maa = $apurow[0];
			echo "<input type='checkbox' name='lang[]' value='$apurow[0]' $sel>".t($maa)."<br>";
		}
		elseif($tunnus > 0 and $apurow[0] != "tunnus" and $apurow[0] != "aikaleima" and $apurow[0] != "kysytty" and $apurow[0] != "laatija" and $apurow[0] != "luontiaika" and $apurow[0] != "muutospvm" and $apurow[0] != "muuttaja" and $apurow[0] != "synkronoi") {
			if ($rivi["kieli"] == $apurow[0]) {
				$sel = "CHECKED";
			}

			$query = "select distinct nimi from maat where koodi='$apurow[0]'";
			$maare = mysql_query($query);
			$maaro = mysql_fetch_array($maare);
			$maa   = strtolower($maaro["nimi"]);
			if ($maa=="") $maa = $apurow[0];
			echo "<input type='radio' name='lang[]' value='$apurow[0]' $sel>".t($maa)."<br>";
		}
	}
	echo "</td>";

	if ($toim == "VERKKOKAUPPA") {
		echo "<tr><th>".t("Osasto")."</th><td>";

		echo "<select name='kentta09'>";

		$result = t_avainsana("VERKKOKAULINKKI");

		if (mysql_num_rows($result) > 0) {
			while ($orow = mysql_fetch_array($result)) {
				if($rivi["kentta09"] == $orow["selite"]) $sel = "SELECTED";
				else $sel = "";
				echo "<option value='$orow[selite]' $sel>$orow[selitetark]</option>";
			}
		}

		$result = t_avainsana("OSASTO", "", " and avainsana.jarjestys < 10000 ");

		if (mysql_num_rows($result) > 0) {
			while ($orow = mysql_fetch_array($result)) {
				if($rivi["kentta09"] == $orow["selite"]) $sel = "SELECTED";
				else $sel = "";
				echo "<option value='$orow[selite]' $sel>$orow[selite] - $orow[selitetark]</option>";
			}

		}

		echo "</select>";
		echo "</td></tr>";
	}

	if ($rivi['kokopaiva'] != "") $check = "CHECKED";
	else $check = "";

	echo "<tr><th>".t("Prioriteetti")."</th><td><input type='checkbox' name='kokopaiva' $check> ".t("N�ytet��nk� uutinen aina p��llimm�isen�")."</td></tr>";

	if ($yhtiorow['konserni'] != '') {

		if ($rivi['konserni'] != "") $check = "CHECKED";
		else $check = "";

		echo "<tr>
			<th>".t("Konserni")."</th>
			<td><input type='checkbox' name='konserni' $check> ".t("N�ytet��nk� uutinen konsernin kaikilla yrityksill�")."</td>
		</tr>";
	}
	if (($toim == "VIIKKOPALAVERI" or $toim == "ASIAKASPALVELU" or $toim == "RYJO") and ($rivi["kuka"] == $kukarow["kuka"])) {
		if ($rivi['kuittaus'] != "") $check = "CHECKED";
		else $check = "";

		echo "<tr>
				<th>".t("Lukko")."</th>
				<td><input type='checkbox' name='lukittu' value='L' $check>".t("Lukitse palaveri. Lukittua palaveria ei voi muokata eik� poistaa.")."</td>
			</tr>";
	}

	if (($kukarow['yhtio'] == 'artr' and ($toim == 'EXTRANET' or $toim == 'AUTOMANUAL')) or ($toim == 'EXTRANET')) {
		if ($rivi['tapa'] == "automanual_ext_uutinen" and $rivi['tyyppi'] == "extranet_uutinen") {
			$check1 = $check2 = "CHECKED";
		}
		elseif ($rivi['tapa'] == "automanual_uutinen" and $rivi['tyyppi'] == "extranet_uutinen") {
			$check1 = "CHECKED";
			$check2 = "";
		}
		elseif ($rivi['tapa'] == "extranet_uutinen" and $rivi['tyyppi'] == "extranet_uutinen") {
			$check1 = "";
			$check2 = "CHECKED";
		}
		else {
			if ($toim == 'AUTOMANUAL') {
				$check1 = "CHECKED";
			}
			else {
				$check1 = "";
			}
			if ($toim == "EXTRANET") {
				$check2 = "CHECKED";
			}
			else {
				$check2 = "";
			}
		}

		if ($kukarow['yhtio'] == 'artr' and ($toim == 'EXTRANET' or $toim == 'AUTOMANUAL')) {
			echo "<tr>
				<th>".t("Automanual")."</th>
				<td><input type='checkbox' name='automanual_uutinen' $check1> ".t("N�ytet��nk� uutinen Automanualissa")."</td>
			</tr>";
		}
		if ($toim == 'EXTRANET') {
			echo "<tr>
				<th>".t("Extranet")."</th>
				<td><input type='checkbox' name='extranet_uutinen' $check2> ".t("N�ytet��nk� uutinen Extranetiss�")."</td>
			</tr>";

			$check3 = "";
			if ($rivi['kentta08'] == 'X') {
				$check3 = "CHECKED";
			}

			echo "<tr>
				<th>".t("Extranet")."</th>
				<td><input type='checkbox' name='kentta08' value='X' $check3> ".t("Ei n�ytet� asiakkaan asiakkaille")."</td>
			</tr>";
		}
	}

	echo "
		</table>

		<br><input type='submit' value='".t("Sy�t�")."'>

		</form>";
}

if ($tee == "POISTA") {
	$query  = "	UPDATE kalenteri
				SET tyyppi = concat('DELETED ',tyyppi)
				WHERE tyyppi='$tyyppi' and tunnus='$tunnus' and kuka='$kukarow[kuka]' and yhtio='$kukarow[yhtio]'";
	$result = pupe_query($query);

	$tee = "";
}

if ($tee == "PRINTTAA") {

	echo "
		<script language=\"JavaScript\">
		<!--
    	    function printtaa() {
				window.print();
				window.location = \"$PHP_SELF?toim=$toim\";
    	    }
		//-->
		</script>";

	$query = "	SELECT *, kalenteri.tunnus tun, kalenteri.kuka toimittaja
				from kalenteri
				left join kuka on kuka.yhtio=kalenteri.yhtio and kuka.kuka=kalenteri.kuka
				where tyyppi='$tyyppi'
				and kalenteri.yhtio='$kukarow[yhtio]'
				and kalenteri.tunnus='$tun'";
	$result = pupe_query($query);
	$row = mysql_fetch_array($result);

	/*
	toimittaja = kuka
	paivays    = pvmalku
	otsikko    = kentta01
	uutinen    = kentta02
	kuvaurl    = kentta03
	*/

	$kuvaurl = "";

	if ($row["kentta03"] != "") {
		$kuvaurl = "<img src='view.php?id=$row[kentta03]' width='130'>";
	}

	if ($yhtiorow["logo"] != '' and $kuvaurl == '') {
		$kuvaurl = "<img src='$yhtiorow[logo]' width='130'>";
	}

	if ($kuvaurl == '') {
		$kuvaurl = "<img src='http://www.pupesoft.com/pupesoft.gif' width='130'>";
	}

	$otsikko        = $row["kentta01"];
	$uutinen        = $row["kentta02"];
	$paivays        = $row["pvmalku"];
	$toimittaja     = $row["kuka"];

	echo "<TITLE>$otsikko - $paivays</TITLE>
		<BODY BGCOLOR='#FFFFFF' TEXT='#000000' LINK='#336699' VLINK='#336699' ALINK='#336699' onLoad='printtaa();'>

		<CENTER>
		<BR><BR>

		<TABLE CELLSPACING='0' CELLPADDING='5' WIDTH='400' BORDER='1' BORDERCOLOR='#000000' BGCOLOR='#FFFFFF'><TR><TD colspan=2>
				<FONT FACE='Lucida,Verdana,Helvetica,Arial' SIZE='+2'>
						<B>$otsikko</B><BR>
				</FONT>
				<HR COLOR='GRAY'>

				<TABLE BORDER='0' CELLSPACING='5' CELLPADDING='4'><TR>
						<TD VALIGN='TOP'>
								$kuvaurl
						</TD><TD VALIGN='TOP'>
								<FONT FACE='Lucida,Verdana,Helvetica,Arial' SIZE='-1'>
								$uutinen
								</FONT>
						</TD>
				</TR></TABLE>

		</TD></TR>

		<TR><TD VALIGN=middle bgcolor='#000000'>
				<FONT FACE='Lucida,Verdana,Helvetica,Arial' COLOR='#FFFFFF'>
				<B><SMALL>&nbsp;".t("Toimittaja").": $toimittaja</SMALL></B><BR>
				<B><SMALL>&nbsp;".t("P�iv�m��r�").": $paivays</SMALL></B>
				</FONT>
		<!--
			</TD><TD ALIGN=right bgcolor='#000000'>
			<IMG SRC='print.gif' WIDTH='21' HEIGHT='21' BORDER='0' ALT='".t("tulosta uutinen")."'>&nbsp;
			<IMG SRC='koti.gif' WIDTH='21' HEIGHT='21' BORDER='0' ALT='".t("koti")."'>&nbsp;
		-->
		</TD></TR></TABLE>

		</CENTER>
		</BODY>

		";
}

if ($tee == '') {

	if (strpos($_SERVER['SCRIPT_NAME'], "uutiset.php")  !== FALSE) {
		echo "<form method='post' action='$PHP_SELF'>
			<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' name='tee' value='SYOTA'>";
		echo "<input type='submit' value='".t("Lis�� uusi uutinen")."'>";
		echo "</form><br><br>";
	}

	if ($limit=="all") $limit = "";
	elseif ($limit=="50") $limit = "limit 50";
	elseif ($limit=="10") $limit = "limit 10";
	else $limit = "limit 5";

	if ($yhtiorow['konserni'] != "") {
		$ehto = "(kalenteri.yhtio='$kukarow[yhtio]' or kalenteri.konserni='$yhtiorow[konserni]')";
	}
	else {
		$ehto = "kalenteri.yhtio='$kukarow[yhtio]'";
	}

	if ($kukarow['kieli'] != "" and $kukarow['kieli'] == $yhtiorow['kieli']) {
		$lisa = " and (kalenteri.kieli = '$kukarow[kieli]' or kalenteri.kieli = '') ";
	}
	elseif ($kukarow['kieli'] != "") {
		$lisa = " and kalenteri.kieli = '$kukarow[kieli]' ";
	}
	else {
		$lisa = "";
	}

	$querylisa_tapa = "";

	if ($toim == 'AUTOMANUAL') {
		$querylisa_tapa = " and tapa in ('automanual_uutinen', 'automanual_ext_uutinen') ";
	}
	else {
		$querylisa_tapa = "	and tapa != 'automanual_uutinen' ";
	}

	$query = "	SELECT *, kalenteri.tunnus tun, kalenteri.kuka toimittaja
				from kalenteri
				left join kuka on kuka.yhtio=kalenteri.yhtio and kuka.kuka=kalenteri.kuka
				where tyyppi='$tyyppi' $lisa and $ehto
				$querylisa_tapa
				order by kokopaiva desc, pvmalku desc, kalenteri.tunnus desc
				$limit";
	$result = pupe_query($query);

	if (mysql_num_rows($result) > 0) {

		echo "<table width='600'>";

		while ($uutinen = mysql_fetch_array($result)) {

			/*
			toimittaja = kuka
			paivays    = pvmalku
			otsikko    = kentta01
			uutinen    = kentta02
			kuvaurl    = kentta03
			*/

			$kuva = "";

			if ($uutinen["kentta03"] != "") {

				$query  = "	SELECT *
							from liitetiedostot
							where tunnus = '$uutinen[kentta03]'";
				$lisatietores = pupe_query($query);

				if (mysql_num_rows($lisatietores) > 0) {
					$lisatietorow = mysql_fetch_array($lisatietores);

					if ($lisatietorow["image_width"] > 130 or $lisatietorow["image_width"] == 0) {
						// Tehd��n nyt t�h�n t�llanen convert juttu niin k�ytt�j�ien megakokoiset kuvat eiv�t j�� niin isoina kantaan
						$nimi1 = "/tmp/".md5(uniqid(rand(),true)).".jpg";

						$fh = fopen($nimi1, "w");
						if (fwrite($fh, $lisatietorow["data"]) === FALSE) die("Kirjoitus ep�onnistui $nimi1");
						fclose($fh);

						$nimi2 = "/tmp/".md5(uniqid(rand(),true)).".jpg";

						// Haetaan kuvan v�riprofiili
						exec("nice -n 20 identify -format %[colorspace] \"$nimi1\"", $identify);

						$colorspace = "sRGB";
						if ($identify[0] != "") $colorspace = $identify[0];

						passthru("nice -n 20 convert -resize 130x -quality 90 -colorspace $colorspace -strip \"$nimi1\" \"$nimi2\"", $palautus);

						// Tallennetaa skeilattu kuva
						$ltsc = tallenna_liite($nimi2, "kalenteri", 0, $lisatietorow["selite"], '', $lisatietorow["tunnus"]);

						//dellataan tmp filet kuleksimasta
						system("rm -f $nimi1 $nimi2");

						$kuva = "<img src='view.php?id=$uutinen[kentta03]' width='130'>";
					}
					else {
						$kuva = "<img src='view.php?id=$uutinen[kentta03]' width='130'>";
					}
				}
			}

			if((int) $yhtiorow["logo"] > 0 and $kuva == '') {
				$liite = hae_liite($yhtiorow["logo"], "Yllapito", "array");

				$kuva = "<img src='view.php?id=$liite[tunnus]' width='130'>";
			}
			elseif(@fopen($yhtiorow["logo"], "r") and $kuva == '') {
				$kuva = "<img src='$yhtiorow[logo]' width='130'>";
			}
			elseif(file_exists($yhtiorow["logo"]) and $kuva == '') {
				$kuva = "<img src='$yhtiorow[logo]' width='130'>";
			}

			if ($kuva == '') {
				$kuva = "<img src='http://www.pupesoft.com/pupesoft.gif' width='130'>";
			}

			if ($uutinen['nimi'] == "") {
				$uutinen['nimi'] = $uutinen['toimittaja'];
			}

			if ($toim == "EXTRANET") {
				// ##tuoteno##
				$search = "/#{2}(.*?)#{2}/s";
				preg_match_all($search, $uutinen["kentta02"], $matches, PREG_SET_ORDER);

				if (count($matches) > 0) {
					$search = array();
					$replace = array();

					foreach($matches as $m) {

						//	Haetaan tuotenumero
						$query = "	SELECT tuoteno, nimitys
						 			FROM tuote
									WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$m[1]'";
						$tres = pupe_query($query);

						//	T�m� me korvataan aina!
						$search[] = "/$m[0]/";

						if (mysql_num_rows($tres) > 1) {
							$replace[]	= "";
						}
						else {
							$trow = mysql_fetch_array($tres);

							$replace[]	= "<a href = '$PHP_SELF?toim=$toim'>$trow[tuoteno]</a> $trow[nimitys]";
						}
					}

					$uutinen["kentta02"] = preg_replace($search, $replace, $uutinen["kentta02"]);
				}
			}

			echo "	<tr><td colspan='2' class='back'><font class='head'>$uutinen[kentta01]</font><hr></td></tr>
					<tr>
					<td valign='top' align='center' width='140'><br>$kuva<br><br></td>
					<td valign='top'>$uutinen[kentta02]</font><br><a href='$PHP_SELF?tee=PRINTTAA&toim=$toim&tun=$uutinen[tun]'>".t("Tulosta")."</a></td>
					</tr>";

			echo"<tr><th colspan='2'>";
			echo "Toimittaja: $uutinen[nimi]<br>P�iv�m��r�: $uutinen[pvmalku]";

			if ($toim == "VERKKOKAUPPA") {
				echo "<br>Osasto: $uutinen[kentta09]";
			}

			if (strpos($_SERVER['SCRIPT_NAME'], "uutiset.php")  !== FALSE) {
				if (($toim == "VIIKKOPALAVERI" or $toim == "ASIAKASPALVELU" or $toim == "RYJO") and ($uutinen["kuittaus"] == "")) {
					echo "<br><br><form method='post' action='$PHP_SELF'>
						<input type='hidden' name='toim' value='$toim'>";
					echo "<input type='hidden' name='tee' value='SYOTA'>";
					echo "<input type='hidden' name='tunnus' value='$uutinen[tun]'>";
					echo "<input type='submit' value='".t("Muokkaa")."'>";
					echo "</form> ";

					if ($uutinen["kuka"] == $kukarow["kuka"] and $uutinen["yhtio"] == $kukarow["yhtio"]) {
						echo " <form method='post' action='$PHP_SELF'><input type='hidden' name='toim' value='$toim'>";
						echo "<input type='hidden' name='tee' value='POISTA'>";
						echo "<input type='hidden' name='tunnus' value='$uutinen[tun]'>";
						echo "<input type='submit' value='".t("Poista")."'>";
						echo "</form>";
					}
				}
				elseif ($toim != "VIIKKOPALAVERI" and $toim != "ASIAKASPALVELU" and $toim != "RYJO" and $uutinen["kuka"] == $kukarow["kuka"] and $uutinen["yhtio"] == $kukarow["yhtio"]) {
					echo "<br><br><form method='post' action='$PHP_SELF'>
						<input type='hidden' name='toim' value='$toim'>";
					echo "<input type='hidden' name='tee' value='SYOTA'>";
					echo "<input type='hidden' name='tunnus' value='$uutinen[tun]'>";
					echo "<input type='submit' value='".t("Muokkaa")."'>";
					echo "</form> ";
					echo " <form method='post' action='$PHP_SELF'>
						<input type='hidden' name='toim' value='$toim'>";
					echo "<input type='hidden' name='tee' value='POISTA'>";
					echo "<input type='hidden' name='tunnus' value='$uutinen[tun]'>";
					echo "<input type='submit' value='".t("Poista")."'>";
					echo "</form>";
				}
			}
			echo "</th></tr>";
			echo"<tr><td colspan='2' class='back'><br></td></tr>";

		}
		echo "</table>";

		echo "<a href='$PHP_SELF?limit=10&toim=$toim'>".t("N�yt� viimeiset 10 uutista")."</a><br>";
		echo "<a href='$PHP_SELF?limit=50&toim=$toim'>".t("N�yt� viimeiset 50 uutista")."</a><br>";
		echo "<a href='$PHP_SELF?limit=all&toim=$toim'>".t("N�yt� kaikki uutiset")."</a><br>";
	}
}

if (strpos($_SERVER['SCRIPT_NAME'], "uutiset.php")  !== FALSE) {
	require("inc/footer.inc");
}

?>