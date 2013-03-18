<?php

require ("parametrit.inc");

echo "<font class='head'>$yhtiorow[nimi] Extranet</font><hr>";

if ($tee == 'TUOTE' and $kukarow['extranet'] != "") {

	// haetaan avoimen tilauksen otsikko
	if ($kukarow["kesken"] != 0) {
		$query    = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
		$laskures = pupe_query($query);
	}
	else {
		// Luodaan uusi myyntitilausotsikko
		require_once("luo_myyntitilausotsikko.inc");
		$tilausnumero = luo_myyntitilausotsikko("RIVISYOTTO", $kukarow["oletus_asiakas"]);
		$kukarow["kesken"] = $tilausnumero;
		$kaytiin_otsikolla = "NOJOO!";

		// haetaan avoimen tilauksen otsikko
		$query    = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
		$laskures = pupe_query($query);
	}

	if ($kukarow["kesken"] != 0 and $laskures != '') {
		// tilauksen tiedot
		$laskurow = mysql_fetch_array($laskures);
	}

	echo "<font class='message'>".t("Lisätään tuotteita tilaukselle")." $kukarow[kesken].</font><br>";

	$kpl = 1;

	// haetaan tuotteen tiedot
	$query    = "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
	$tuoteres = pupe_query($query);

	if (mysql_num_rows($tuoteres) == 0) {
		echo "<font class='error'>".t("Tuotetta")." $tuoteno ".t("ei löydy")."!</font><br>";
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
		$alv		     = "";
		$var			 = "";
		$varasto 	     = $laskurow["varasto"];
		$rivitunnus		 = "";
		$korvaavakielto	 = "";
		$jtkielto 		 = $laskurow['jtkielto'];
		$varataan_saldoa = "";
		$paikka	= "";

		for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
			${'ale'.$alepostfix} = "";
		}

		// jos meillä on ostoskori muuttujassa numero, niin halutaan lisätä tuotteita siihen ostoskoriin
		if (file_exists("../tilauskasittely/lisaarivi.inc")) {
			require ("../tilauskasittely/lisaarivi.inc");
		}
		else {
			require ("lisaarivi.inc");
		}

		echo "<font class='message'>".t("Lisättiin")." $kpl_echo ".t_avainsana("Y", "", "and avainsana.selite='$trow[yksikko]'", "", "", "selite")." ".t("tuotetta")." $tuoteno.</font><br>";


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
	$alv		     = "";
	$var			 = "";
	$varasto 	     = "";
	$rivitunnus		 = "";
	$korvaavakielto	 = "";
	$varataan_saldoa = "";
	$paikka			 = "";
	$tee 			 = "";

	for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
		${'ale'.$alepostfix} = "";
	}
}

if ($tee == '') {

	if ($kukarow['saatavat'] <= 1) {
		$query = "	SELECT ytunnus, tunnus
					FROM asiakas
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus  = '$kukarow[oletus_asiakas]'
					LIMIT 1";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 1) {
			$sytunnusrow = mysql_fetch_assoc($result);

			$sytunnus 	 	 = $sytunnusrow['ytunnus'];
			$sliitostunnus	 = $sytunnusrow['liitostunnus'];
			$eiliittymaa 	 = "ON";
			$luottorajavirhe = "";
			$jvvirhe 		 = "";
			$ylivito 		 = 0;
			$trattavirhe 	 = "";
			$laji 			 = "MA";
			$grouppaus       = "";

			require ("saatanat.php");
		}
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

	$result = pupe_query($query);

	if (mysql_num_rows($result)>0) {

		///* uutiset *///
		echo "<td class='back' valign='top' width='700'>";

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
				$kuva = "<img src='view.php?id=$uutinen[kentta03]' width='180'>";
			}

			if ((int) $yhtiorow["logo"] > 0 and $kuva == '') {
				$liite = hae_liite($yhtiorow["logo"], "Yllapito", "array");

				$kuva = "<img src='view.php?id=$liite[tunnus]' width='180'>";
			}
			elseif (@fopen($yhtiorow["logo"], "r") and $kuva == '') {
				$kuva = "<img src='$yhtiorow[logo]' width='180'>";
			}
			elseif (file_exists($yhtiorow["logo"]) and $kuva == '') {
				$kuva = "<img src='$yhtiorow[logo]' width='180'>";
			}

			if ($kuva == '') {
				$kuva = "<img src='{$pupesoft_scheme}api.devlab.fi/pupesoft.gif' width='180'>";
			}

			if ($uutinen['nimi'] == "") {
				$uutinen['nimi'] = $uutinen['toimittaja'];
			}

			// ##tuoteno##
			$search = "/#{2}(.*?)#{2}/s";
			preg_match_all($search, $uutinen["kentta02"], $matches, PREG_SET_ORDER);

			if (count($matches) > 0) {
				$search = array();
				$replace = array();

				foreach ($matches as $m) {

					//	Haetaan tuotenumero
					$query = "	SELECT *
					 			FROM tuote
								WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$m[1]'";
					$tres = pupe_query($query);

					//	Tämä me korvataan aina!
					$search[] = "/$m[0]/";

					if (mysql_num_rows($tres) <> 1) {
						$replace[]	= "";
					}
					else {
						$trow = mysql_fetch_array($tres);

						$query = "SELECT * FROM asiakas where yhtio = '$kukarow[yhtio]' and tunnus = '$kukarow[oletus_asiakas]'";
						$asiakastempres = mysql_query($query);
						$asiakastemprow = mysql_fetch_array($asiakastempres);

						$temp_laskurowwi['liitostunnus']	= $asiakastemprow['tunnus'];
						$temp_laskurowwi['ytunnus']			= $asiakastemprow['ytunnus'];
						$temp_laskurowwi['valkoodi']		= $asiakastemprow['valkoodi'];
						$temp_laskurowwi['maa']				= $asiakastemprow['maa'];

						list($hinta, $netto, $ale_kaikki, $alehinta_alv, $alehinta_val) = alehinta($temp_laskurowwi, $trow, 1, '', '', '');

						if ($temp_laskurowwi['valkoodi'] != "" and $temp_laskurowwi['valkoodi'] != $yhtiorow["valkoodi"]) {
							// katotaan onko tuotteelle maakohtaisia valuuttahintoja
							$query = "	SELECT *
										from hinnasto
										where yhtio = '$kukarow[yhtio]'
										and tuoteno = '$trow[tuoteno]'
										and valkoodi = '$temp_laskurowwi[valkoodi]'
										and maa = '$temp_laskurowwi[maa]'
										and laji = ''
										and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
										ORDER BY ifnull(to_days(current_date)-to_days(alkupvm),9999999999999)
										LIMIT 1";
							$hintaresult = pupe_query($query);

							if (mysql_num_rows($hintaresult) > 0) {
								$hintarow = mysql_fetch_array($hintaresult);
							}
							else {
								// katotaan onko tuotteelle valuuttahintoja
								$query = "	SELECT *
											from hinnasto
											where yhtio = '$kukarow[yhtio]'
											and tuoteno = '$trow[tuoteno]'
											and valkoodi = '$temp_laskurowwi[valkoodi]'
											and laji = ''
											and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
											ORDER BY ifnull(to_days(current_date)-to_days(alkupvm),9999999999999)
											LIMIT 1";
								$hintaresult = pupe_query($query);

								if (mysql_num_rows($hintaresult) > 0) {
									$hintarow = mysql_fetch_array($hintaresult);
								}
								else {
									$hintarow["hinta"] = $trow["myyntihinta"];
									$hintarow["valkoodi"] = $yhtiorow["valkoodi"];
								}
							}
						}
						else {
							$hintarow["hinta"] = $trow["myyntihinta"];
							$hintarow["valkoodi"] = $yhtiorow["valkoodi"];
						}

						if ($hinta != $hintarow["hinta"]) {
							$ashinta = sprintf('%.2f',$hinta);
						}
						else {
							$ashinta = "";
						}

						$replace[]	= "<a href = '$PHP_SELF?tee=TUOTE&toim=$toim&tuoteno=".urlencode($m[1])."'>$trow[tuoteno]</a> $trow[nimitys] $ashinta (".t("ovh").". ".hintapyoristys($hintarow["hinta"])." $hintarow[valkoodi])";
					}
				}

				$uutinen["kentta02"] = preg_replace($search, $replace, $uutinen["kentta02"]);
			}

			echo "
			<table width='100%'>
			<tr>
			<td colspan='2' class='back'><font class='head'>$uutinen[kentta01]</font><hr></td>
			</tr><tr>
			<td valign='top' align='center' width='180'><br>$kuva<br><br></td>
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
		$kuvaurl = "<img src='{$pupesoft_scheme}api.devlab.fi/pupesoft.gif' width='130'>";
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
				<B><SMALL>&nbsp;".t("Päivämäärä").": $paivays</SMALL></B>
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
