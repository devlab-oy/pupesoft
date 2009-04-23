<?php

require ("parametrit.inc");

echo "<font class='head'>$yhtiorow[nimi] Extranet</font><hr>";

if ($tee == 'TUOTE' and $kukarow['extranet'] != "") {
	
	// haetaan avoimen tilauksen otsikko
	if ($kukarow["kesken"] != 0) {
		$query    = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
		$laskures = mysql_query($query);
	}
	else {
		// Luodaan uusi myyntitilausotsikko
		require_once("luo_myyntitilausotsikko.inc");
		$tilausnumero = luo_myyntitilausotsikko($kukarow["oletus_asiakas"]);
		$kukarow["kesken"] = $tilausnumero;
		$kaytiin_otsikolla = "NOJOO!";

		// haetaan avoimen tilauksen otsikko
		$query    = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
		$laskures = mysql_query($query);
	}

	if ($kukarow["kesken"] != 0 and $laskures != '') {
		// tilauksen tiedot
		$laskurow = mysql_fetch_array($laskures);
	}

	echo "<font class='message'>Lisätään tuotteita tilaukselle $kukarow[kesken].</font><br>";


	$kpl = 1;
	
	// haetaan tuotteen tiedot
	$query    = "select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
	$tuoteres = mysql_query($query);

	if (mysql_num_rows($tuoteres) == 0) {
		echo "<font class='error'>Tuotetta $tuoteno ei löydy!</font><br>";
	}
	else {
		// tuote löytyi ok, lisätään rivi
		$trow = mysql_fetch_array($tuoteres);

		$ytunnus         = $laskurow["ytunnus"];
		$kpl             = (float) $kpl;
		$kpl_echo 		 = (float) $kpl;
		$tuoteno         = $trow["tuoteno"];
		$toimaika 	     = $laskurow["toimaika"];
		$kerayspvm	     = $laskurow["kerayspvm"];
		$hinta 		     = "";
		$netto 		     = "";
		$ale 		     = "";
		$alv		     = "";
		$var			 = "";
		$varasto 	     = "";
		$rivitunnus		 = "";
		$korvaavakielto	 = "";
		$jtkielto 		 = $laskurow['jtkielto'];
		$varataan_saldoa = "";
		$paikka	= "";
		

		// jos meillä on ostoskori muuttujassa numero, niin halutaan lisätä tuotteita siihen ostoskoriin
		if (file_exists("../tilauskasittely/lisaarivi.inc")) {
			require ("../tilauskasittely/lisaarivi.inc");
		}
		else {
			require ("lisaarivi.inc");
		}

		echo "<font class='message'>".t("Lisättiin")." $kpl_echo ".ta($kieli, "Y", $trow["yksikko"])." ".t("tuotetta")." $tuoteno.</font><br>";

		
	} // tuote ok else

	echo "<br>";

	$trow			 = "";
	$ytunnus         = "";
	$kpl             = "";
	$tuoteno         = "";
	$toimaika 	     = "";
	$kerayspvm	     = "";
	$hinta 		     = "";
	$netto 		     = "";
	$ale 		     = "";
	$alv		     = "";
	$var			 = "";
	$varasto 	     = "";
	$rivitunnus		 = "";
	$korvaavakielto	 = "";
	$varataan_saldoa = "";
	$paikka			 = "";
	$tee 			 = "";
	
}

if ($tee == '') {

	if ($kukarow['saatavat'] <= 1) {
//		echo "<font class='head'>".t("Laskutilanne")."</font><hr>";

		$query = "	SELECT ytunnus
					FROM asiakas
					WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$kukarow[oletus_asiakas]' LIMIT 1";
		$result = mysql_query($query) or pupe_error($query);
		$sytunnusrow = mysql_fetch_array($result);

		$sytunnus = $sytunnusrow['ytunnus'];
		$eiliittymaa = 'ON';

		require ("saatanat.php");
//		echo "<hr><br>";
	}

	echo "<table width='100%'>";
	echo "<tr>";

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

	if ($kukarow['kieli'] == $yhtiorow['kieli']) {
		$lisa = " and (kalenteri.kieli = '$kukarow[kieli]' or kalenteri.kieli = '') ";
	}
	else {
		$lisa = " and kalenteri.kieli = '$kukarow[kieli]' ";
	}
	
	//katsotaan saako uutista näyttää asiakkaan asiakkaalle
	if ($kukarow['oletus_asiakas'] != $kukarow['oletus_asiakastiedot'] and $kukarow['oletus_asiakastiedot'] != "") {
		$ehto .= " and kentta08 != 'X' ";
	}

	$query = "	SELECT *, kalenteri.tunnus tun, kalenteri.kuka toimittaja
				from kalenteri
				left join kuka on kuka.yhtio=kalenteri.yhtio and kuka.kuka=kalenteri.kuka
				where tyyppi='extranet_uutinen' $lisa and $ehto
				and tapa != 'automanual_uutinen'
				order by kokopaiva desc, pvmalku desc, kalenteri.tunnus desc
				$limit";

	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result)>0) {

		///* uutiset *///
		echo "<td class='back' valign='top' width=350'>";

		while($uutinen = mysql_fetch_array($result)) {

			/*
			toimittaja = kuka
			paivays    = pvmalku
			otsikko    = kentta01
			uutinen    = kentta02
			kuvaurl    = kentta03
			*/

			$kuva = "";

			if ($uutinen["kentta03"] != "") {
				$kuva = "<img src='view.php?id=$uutinen[kentta03]' width='130'>";
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

			// ##tuoteno##
			$search = "/#{2}(.*?)#{2}/s";
			preg_match_all($search, $uutinen["kentta02"], $matches, PREG_SET_ORDER);
			//echo "<pre>".print_r($matches, true)."</pre>";

			if(count($matches) > 0) {
				$search = array();
				$replace = array();
				foreach($matches as $m) {

					//	Haetaan tuotenumero
					$query = "	SELECT tuoteno, nimitys
					 			FROM tuote
								WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$m[1]'";
					$tres = mysql_query($query) or pupe_error($query);

					//	Tämä me korvataan aina!
					$search[] = "/$m[0]/";

					if(mysql_num_rows($tres) <> 1) {
						$replace[]	= "";
					}
					else {
						$trow = mysql_fetch_array($tres);

						$replace[]	= "<a href = '$PHP_SELF?tee=TUOTE&toim=$toim&tuoteno=$m[1]'>$trow[tuoteno]</a> $trow[nimitys]";
					}
				}

				$uutinen["kentta02"] = preg_replace($search, $replace, $uutinen["kentta02"]);
			}

			echo "
			<table width='400'>
			<tr>
			<td colspan='2' class='back'><font class='head'>$uutinen[kentta01]</font><hr></td>
			</tr><tr>
			<td valign='top' align='center' width='140'><br>$kuva<br><br></td>
			<td valign='top'>$uutinen[kentta02]<br>
			<a href='$PHP_SELF?tee=PRINTTAA&tun=$uutinen[tun]'>".t("Tulosta")."</a>
			</font></td>
			</tr><tr>
			<th colspan='2'>".t("Toimittaja").": $uutinen[nimi]<br>".t("Päivämäärä").": $uutinen[pvmalku]</th>
			</tr>
			</table><br>\n";
		}

		echo "<a href='tervetuloa.php?limit=10'>".t("Näytä viimeiset 10 uutista")."</a><br>";
		echo "<a href='tervetuloa.php?limit=50'>".t("Näytä viimeiset 50 uutista")."</a><br>";
		echo "<a href='tervetuloa.php?limit=all'>".t("Näytä kaikki uutiset")."</a><br>";

		echo "</td>";
	}

	// oikea palkki extra contentille
	echo "<td class='back' align='right' valign='top'>";
	if (file_exists("$kukarow[yhtio]_extranet.html")) {
		require ("$kukarow[yhtio]_extranet.html");
	}
	echo "</td>";

	echo "</tr>";
	echo "</table>";
}

if ($tee == "PRINTTAA") {

	echo "
		<script language=\"JavaScript\">
		<!--
    	    function printtaa() {
				window.print();
				window.location = \"$PHP_SELF\";
    	    }
		//-->
		</script>";

	$query = "	SELECT *, kalenteri.tunnus tun, kalenteri.kuka toimittaja
				from kalenteri
				left join kuka on kuka.yhtio=kalenteri.yhtio and kuka.kuka=kalenteri.kuka
				where tyyppi='uutinen'
				and kalenteri.yhtio='$kukarow[yhtio]'
				and kalenteri.tunnus='$tun'";
	$result = mysql_query($query) or pupe_error($query);
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

	print "

		<TITLE>$otsikko - $paivays</TITLE>
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
				<B><SMALL>&nbsp;".t("P&auml;iv&auml;m&auml;&auml;r&auml;").": $paivays</SMALL></B>
				</FONT>
		<!--
			</TD><TD ALIGN=right bgcolor='#000000'>
			<IMG SRC='print.gif' WIDTH='21' HEIGHT='21' BORDER='0' ALT='tulosta uutinen'>&nbsp;
			<IMG SRC='koti.gif' WIDTH='21' HEIGHT='21' BORDER='0' ALT='koti'>&nbsp;
		-->
		</TD></TR></TABLE>

		</CENTER>
		</BODY>

		";
}


require("footer.inc");

?>
